<?php
/**
 * mypage_google_relay_server.php
 * ykr.moe に mypage_google_callback.php としてリネームして配置する中継スクリプト
 *
 * 担当する処理:
 *   GET  ?action=auth&client_id=XXX&state=YYY  → Google OAuth へリダイレクト
 *   GET  ?code=XXX&state=YYY                   → コード交換 → ローカルサーバーへリダイレクト
 *   POST ?action=refresh  (JSON body)           → トークンリフレッシュ API
 *
 * 設定: mypage_google_relay_config.php を同ディレクトリに置く
 */

$config_file = __DIR__ . '/mypage_google_relay_config.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    exit('設定ファイル (mypage_google_relay_config.php) が見つかりません。');
}
require $config_file;
// 以下の変数が定義されていること:
//   $RELAY_CLIENT_ID     : Google OAuth クライアントID
//   $RELAY_CLIENT_SECRET : Google OAuth クライアントシークレット
//   $RELAY_SECRET        : ローカルサーバーと共有する HMAC シークレット

define('GOOGLE_AUTH_URL',   'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL',  'https://oauth2.googleapis.com/token');
define('RELAY_REDIRECT_URI', 'https://ykr.moe/mypage_google_callback.php');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// POST ?action=refresh  — トークンリフレッシュ API
// ============================================================
if ($method === 'POST' && $action === 'refresh') {
    header('Content-Type: application/json');

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (empty($data['refresh_token']) || empty($data['hmac'])) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_params']);
        exit;
    }

    $expected = hash_hmac('sha256', $data['refresh_token'], $RELAY_SECRET);
    if (!hash_equals($expected, $data['hmac'])) {
        http_response_code(403);
        echo json_encode(['error' => 'hmac_mismatch']);
        exit;
    }

    $resp = relay_http_post(GOOGLE_TOKEN_URL, http_build_query([
        'client_id'     => $RELAY_CLIENT_ID,
        'client_secret' => $RELAY_CLIENT_SECRET,
        'refresh_token' => $data['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]), ['Content-Type: application/x-www-form-urlencoded']);

    $token = json_decode($resp, true);
    if (empty($token['access_token'])) {
        http_response_code(502);
        echo json_encode(['error' => 'refresh_failed']);
        exit;
    }

    echo json_encode([
        'access_token' => $token['access_token'],
        'expires_in'   => (int)($token['expires_in'] ?? 3600),
    ]);
    exit;
}

// ============================================================
// GET ?action=auth  — OAuth 開始（ローカルサーバーから呼ばれる）
// ============================================================
if ($method === 'GET' && $action === 'auth') {
    $state     = $_GET['state']     ?? '';
    $client_id = $_GET['client_id'] ?? '';

    if (empty($state) || empty($client_id)) {
        http_response_code(400);
        exit('パラメーターが不足しています。');
    }

    // state の HMAC を検証（登録済みローカルサーバーからのリクエストか確認）
    if (!verify_signed($state, $RELAY_SECRET)) {
        http_response_code(403);
        exit('state の署名検証に失敗しました。');
    }

    $params = http_build_query([
        'client_id'     => $client_id,
        'redirect_uri'  => RELAY_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile https://www.googleapis.com/auth/drive.appdata',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
        'state'         => $state,
    ]);
    header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
    exit;
}

// ============================================================
// GET ?code=XXX&state=YYY  — Google からのコールバック
// ============================================================
if ($method === 'GET' && !empty($_GET['code']) && !empty($_GET['state'])) {
    $code  = $_GET['code'];
    $state = $_GET['state'];

    // state を検証して中身を取り出す
    $state_json = decode_signed($state, $RELAY_SECRET);
    if ($state_json === false) {
        exit_no_redirect('state の署名検証に失敗しました。');
    }
    $state_data = json_decode($state_json, true);
    if (empty($state_data['return_url']) || empty($state_data['nonce'])) {
        exit_no_redirect('state の内容が不正です。');
    }

    $return_url = $state_data['return_url'];
    if (!preg_match('#^https?://#i', $return_url)) {
        exit_no_redirect('return_url が不正です。');
    }

    // iat チェック（10分以内）
    if (abs(time() - (int)($state_data['iat'] ?? 0)) > 600) {
        redirect_error($return_url, 'state_expired');
    }

    // コードをトークンに交換
    $resp = relay_http_post(GOOGLE_TOKEN_URL, http_build_query([
        'code'          => $code,
        'client_id'     => $RELAY_CLIENT_ID,
        'client_secret' => $RELAY_CLIENT_SECRET,
        'redirect_uri'  => RELAY_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]), ['Content-Type: application/x-www-form-urlencoded']);

    $token = json_decode($resp, true);
    if (empty($token['access_token'])) {
        redirect_error($return_url, 'token_exchange_failed');
    }

    // id_token からユーザー情報を取得（署名検証は省略、sub/email のみ使用）
    $id_parts = explode('.', $token['id_token'] ?? '');
    if (count($id_parts) < 2) {
        redirect_error($return_url, 'invalid_id_token');
    }
    $id_payload = json_decode(
        base64_decode(strtr($id_parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($id_parts[1]) % 4) % 4)),
        true
    );
    $google_sub   = $id_payload['sub']   ?? '';
    $google_email = $id_payload['email'] ?? '';
    if (empty($google_sub)) {
        redirect_error($return_url, 'missing_sub');
    }

    // ペイロードを署名してローカルサーバーへリダイレクト
    $payload_json = json_encode([
        'google_sub'    => $google_sub,
        'google_email'  => $google_email,
        'access_token'  => $token['access_token'],
        'refresh_token' => $token['refresh_token'] ?? '',
        'expires_at'    => time() + (int)($token['expires_in'] ?? 3600),
        'nonce'         => $state_data['nonce'],
        'iat'           => time(),
    ]);
    $payload = make_signed($payload_json, $RELAY_SECRET);

    header('Location: ' . $return_url . '?payload=' . urlencode($payload));
    exit;
}

// ============================================================
// その他は 400
// ============================================================
http_response_code(400);
exit('不正なリクエストです。');

// ---- ヘルパー関数 ----------------------------------------

/**
 * base64url(json) + "." + HMAC を作る
 */
function make_signed($json, $secret) {
    $b64  = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $hmac = hash_hmac('sha256', $json, $secret);
    return $b64 . '.' . $hmac;
}

/**
 * 署名付きトークンを検証して JSON 文字列を返す。失敗時は false
 */
function decode_signed($token, $secret) {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    [$b64, $hmac_received] = $parts;
    $json = base64_decode(strtr($b64, '-_', '+/') . str_repeat('=', (4 - strlen($b64) % 4) % 4));
    if ($json === false) return false;
    $hmac_expected = hash_hmac('sha256', $json, $secret);
    if (!hash_equals($hmac_expected, $hmac_received)) return false;
    return $json;
}

/**
 * 署名だけ検証（true/false）
 */
function verify_signed($token, $secret) {
    return decode_signed($token, $secret) !== false;
}

/**
 * ローカルサーバーへエラーリダイレクト
 */
function redirect_error($return_url, $error) {
    header('Location: ' . $return_url . '?error=' . urlencode($error));
    exit;
}

/**
 * リダイレクト先不明の場合のエラー表示
 */
function exit_no_redirect($msg) {
    http_response_code(400);
    exit(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
}

/**
 * HTTP POST ヘルパー
 */
function relay_http_post($url, $content, $headers = []) {
    $opts = [
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $content,
            'ignore_errors' => true,
            'timeout'       => 10,
        ],
        'ssl' => ['verify_peer' => true],
    ];
    return @file_get_contents($url, false, stream_context_create($opts));
}
