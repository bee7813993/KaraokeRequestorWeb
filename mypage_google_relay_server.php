<?php
/**
 * mypage_google_relay_server.php
 * ykr.moe に mypage_google_callback.php としてリネームして配置する中継スクリプト
 *
 * 担当する処理:
 *   GET  ?action=auth&client_id=XXX&state=YYY  → Google OAuth へリダイレクト
 *   GET  ?code=XXX&state=YYY                   → コード交換 → ローカルサーバーへリダイレクト
 *   GET  ?error=XXX&state=YYY                  → キャンセル・拒否の案内表示 (Web版はエラーコードを持ち帰り)
 *   POST ?action=refresh  (JSON body)           → トークンリフレッシュ API
 *
 * アプリ (ゆかナビ) 用の直接認証フロー:
 *   GET  ?action=app_auth&session=<64hex>       → Google OAuth へ (アプリがブラウザで開く)
 *   GET  ?code=XXX&state=<app用state>           → トークンを一時保存し「認証完了」を表示
 *   GET  ?action=app_poll&session=<64hex>       → 保存済みトークンを返して削除 (ワンタイム)
 *   POST ?action=app_refresh (JSON body)        → アプリ用トークンリフレッシュ (HMAC 不要)
 *
 * どちらのフローも、きめ細かい同意 (granular consent) で Google ドライブの
 * チェックが外されたまま「続行」された場合は成功にせず、再試行を案内する
 * (そのまま通すとログイン成功後の Drive 同期が 403 になるため)。
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
define('GOOGLE_REVOKE_URL', 'https://oauth2.googleapis.com/revoke');
define('RELAY_REDIRECT_URI', 'https://ykr.moe/mypage_google_callback.php');
define('DRIVE_APPDATA_SCOPE', 'https://www.googleapis.com/auth/drive.appdata');

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
// POST ?action=app_refresh — アプリ用トークンリフレッシュ
// (refresh_token 自体が資格情報のため HMAC は要求しない)
// ============================================================
if ($method === 'POST' && $action === 'app_refresh') {
    header('Content-Type: application/json');

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (empty($data['refresh_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_params']);
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
// GET ?action=app_auth — アプリからの OAuth 開始
// (session はアプリが生成した乱数。state は relay 自身が署名するため
//  アプリ側に共有シークレットを持たせる必要がない)
// ============================================================
if ($method === 'GET' && $action === 'app_auth') {
    $session = $_GET['session'] ?? '';
    if (!app_session_valid($session)) {
        http_response_code(400);
        exit('session が不正です。');
    }

    $state = make_signed(json_encode([
        'app_session' => $session,
        'iat'         => time(),
    ]), $RELAY_SECRET);

    $params = http_build_query([
        'client_id'     => $RELAY_CLIENT_ID,
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
// GET ?action=app_poll — アプリが認証完了を取りに来る (ワンタイム)
// ============================================================
if ($method === 'GET' && $action === 'app_poll') {
    header('Content-Type: application/json');

    $session = $_GET['session'] ?? '';
    if (!app_session_valid($session)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_session']);
        exit;
    }

    $file = app_session_file($session);
    if (!file_exists($file)) {
        echo json_encode(['status' => 'pending']);
        exit;
    }
    if (time() - filemtime($file) > 600) {
        @unlink($file);
        echo json_encode(['status' => 'expired']);
        exit;
    }
    $payload = file_get_contents($file);
    @unlink($file);
    echo json_encode(['status' => 'ok', 'token' => json_decode($payload, true)]);
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
// GET ?error=XXX — Google からのコールバック (キャンセル・拒否)
// 同意画面で「キャンセル」を押す等でここに来る。以前はどのハンドラにも
// 当たらず素の 400「不正なリクエストです。」が表示されていた
// (App Store 審査 2026-07-24 で「ログイン時にエラーが表示される」と指摘された画面)
// ============================================================
if ($method === 'GET' && !empty($_GET['error']) && empty($_GET['code'])) {
    $state_json = decode_signed($_GET['state'] ?? '', $RELAY_SECRET);
    $state_data = $state_json !== false ? json_decode($state_json, true) : null;

    // Web 版のフロー: エラーコードをローカルサーバーへ持ち帰って画面に表示させる
    if (!empty($state_data['return_url'])
        && preg_match('#^https?://#i', $state_data['return_url'])) {
        redirect_error($state_data['return_url'], 'access_denied');
    }

    // アプリのフロー (state が無い・不正な場合も同じ)。セッションを ok にしない
    // だけで充分 — アプリはシートを閉じた時点で「中止」扱いになるため、
    // ここではエラー画面ではなく案内だけを表示する
    exit_cancel_page();
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

    // ---- アプリ (ゆかナビ) の直接認証: トークンを一時保存してブラウザに完了表示 ----
    if (!empty($state_data['app_session'])) {
        if (!app_session_valid($state_data['app_session'])
            || abs(time() - (int)($state_data['iat'] ?? 0)) > 600) {
            exit_no_redirect('セッションが無効か期限切れです。アプリからやり直してください。');
        }
        $token_json = app_exchange_code($code, $RELAY_CLIENT_ID, $RELAY_CLIENT_SECRET);
        if ($token_json === null) {
            exit_no_redirect('Google からトークンを取得できませんでした。アプリからやり直してください。');
        }
        // きめ細かい同意で Drive のチェックが外されたまま「続行」された場合は
        // 成功にしない (このまま通すとアプリはログイン成功後の同期で 403 になる)
        $token_data = json_decode($token_json, true);
        if (strpos($token_data['scope'] ?? '', DRIVE_APPDATA_SCOPE) === false) {
            relay_revoke_token($token_data['access_token'] ?? ''); // 使えない権限は残さない
            exit_drive_scope_page();
        }
        file_put_contents(app_session_file($state_data['app_session']), $token_json);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="ja"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>ゆかナビ - 認証完了</title></head>'
           . '<body style="font-family:sans-serif; text-align:center; padding-top:4em;">'
           . '<h2>✅ Google 認証が完了しました</h2>'
           . '<p>このページを閉じて、ゆかナビに戻ってください。</p>'
           . '<p style="color:#888; font-size:0.85em; max-width:32em; margin:2em auto 0;">'
           . 'ゆかナビの「Google にログイン」操作中でない場合は、誰かに送られた URL から'
           . 'この画面を開いた可能性があります。その場合はこの認証を使用せず、'
           . 'Google アカウントの「サードパーティ製のアプリとサービス」から'
           . 'アクセス権を削除してください。</p>'
           . '</body></html>';
        exit;
    }

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

    // きめ細かい同意で Drive のチェックが外されたまま「続行」された場合 (アプリのフローと同じ)
    if (strpos($token['scope'] ?? '', DRIVE_APPDATA_SCOPE) === false) {
        relay_revoke_token($token['access_token']); // 使えない権限は残さない
        redirect_error($return_url, 'drive_scope_denied');
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
 * 案内ページの共通レイアウト (認証完了ページと同じ見た目)
 */
function exit_info_page($title, $heading, $lines) {
    header('Content-Type: text/html; charset=utf-8');
    $body = '';
    foreach ($lines as $line) {
        $body .= '<p>' . $line . '</p>';
    }
    echo '<!doctype html><html lang="ja"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . $title . '</title></head>'
       . '<body style="font-family:sans-serif; text-align:center; padding-top:4em;">'
       . '<h2>' . $heading . '</h2>' . $body
       . '</body></html>';
    exit;
}

/**
 * キャンセル・拒否時の案内 (エラー画面にはしない。アプリはシートを
 * 閉じた時点で「中止」扱いになる)
 */
function exit_cancel_page() {
    exit_info_page('ゆかナビ - ログイン中止',
        'ログインは完了しませんでした',
        [
            'Google のログインがキャンセルされたか、アクセスが許可されませんでした。',
            'このページを閉じて、ゆかナビ (または Web 版マイページ) から'
                . 'もう一度お試しください。',
        ]);
}

/**
 * Drive スコープのチェックが外されたまま「続行」された場合の案内
 */
function exit_drive_scope_page() {
    exit_info_page('ゆかナビ - 許可が必要です',
        'Google ドライブへのアクセス許可が必要です',
        [
            'マイページのバックアップには「Google ドライブでのアプリ独自の設定データの'
                . '参照、作成、削除」の許可が必要です。',
            'このページを閉じてもう一度ログインし、同意画面のチェックボックスを'
                . 'オンにしてから「続行」を押してください。',
        ]);
}

/**
 * 使わないトークンの失効 (ベストエフォート。失敗しても続行)
 */
function relay_revoke_token($access_token) {
    if (empty($access_token)) {
        return;
    }
    relay_http_post(GOOGLE_REVOKE_URL,
        http_build_query(['token' => $access_token]),
        ['Content-Type: application/x-www-form-urlencoded']);
}

/**
 * アプリセッション ID の形式チェック (32〜64桁の16進のみ。ファイル名に使うため)
 */
function app_session_valid($session) {
    return (bool)preg_match('/^[0-9a-f]{32,64}$/', $session);
}

/**
 * アプリセッションのトークン一時保存先
 */
function app_session_file($session) {
    return sys_get_temp_dir() . '/yukanavi_gauth_' . $session . '.json';
}

/**
 * 認可コードをトークンに交換し、アプリへ渡す JSON を作る。失敗時 null
 */
function app_exchange_code($code, $client_id, $client_secret) {
    $resp = relay_http_post(GOOGLE_TOKEN_URL, http_build_query([
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => RELAY_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]), ['Content-Type: application/x-www-form-urlencoded']);

    $token = json_decode($resp, true);
    if (empty($token['access_token'])) {
        return null;
    }
    // id_token から sub / email を取り出す (アプリの表示・識別用)
    $google_sub   = '';
    $google_email = '';
    $id_parts = explode('.', $token['id_token'] ?? '');
    if (count($id_parts) >= 2) {
        $id_payload = json_decode(
            base64_decode(strtr($id_parts[1], '-_', '+/')
                . str_repeat('=', (4 - strlen($id_parts[1]) % 4) % 4)),
            true
        );
        $google_sub   = $id_payload['sub']   ?? '';
        $google_email = $id_payload['email'] ?? '';
    }
    return json_encode([
        'google_sub'    => $google_sub,
        'google_email'  => $google_email,
        'access_token'  => $token['access_token'],
        'refresh_token' => $token['refresh_token'] ?? '',
        'expires_at'    => time() + (int)($token['expires_in'] ?? 3600),
        // 実際に付与されたスコープ (スペース区切り)。呼び出し側の検証用で、
        // アプリはこのフィールドを無視する
        'scope'         => $token['scope'] ?? '',
    ]);
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
