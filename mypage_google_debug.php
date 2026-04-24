<?php
/**
 * Google同期デバッグ用（確認後は削除してください）
 */
require_once 'commonfunc.php';
require_once 'kara_config.php';
require_once 'mypage_class.php';
require_once 'mypage_google_drive.php';

global $config_ini, $db;
$relay_url    = $config_ini['google_relay_url']    ?? '';
$relay_secret = $config_ini['google_relay_secret'] ?? '';

$mypage = new MypageUser($db);
$link   = $mypage->getGoogleLink();

header('Content-Type: text/plain; charset=UTF-8');

echo "=== 環境チェック ===\n";
echo "cURL:              " . (function_exists('curl_init') ? 'OK' : 'なし') . "\n";
echo "allow_url_fopen:   " . (ini_get('allow_url_fopen') ? 'on' : 'off') . "\n";
echo "openssl:           " . (extension_loaded('openssl') ? 'OK' : 'なし') . "\n";
echo "\n";

echo "=== 設定値 ===\n";
echo "google_client_id:     " . ($config_ini['google_client_id']     ? '設定済み' : '未設定') . "\n";
echo "google_relay_url:     " . ($config_ini['google_relay_url']     ?: '未設定') . "\n";
echo "google_relay_secret:  " . ($config_ini['google_relay_secret']  ? '設定済み' : '未設定') . "\n";
echo "\n";

if (!$link) {
    echo "Google 未連携です。\n";
    exit;
}

echo "=== 連携情報 ===\n";
echo "email:           " . $link['google_email'] . "\n";
echo "token_expires_at:" . date('Y-m-d H:i:s', $link['token_expires_at']) . "\n";
echo "access_token:    " . substr($link['access_token'], 0, 20) . "...\n";
echo "\n";

// ---- cURL で Drive API を直接叩く ----
function debug_curl($method, $url, $body, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $info  = curl_getinfo($ch);
    curl_close($ch);
    return ['resp' => $resp, 'errno' => $errno, 'error' => $error, 'http_code' => $info['http_code']];
}

$token = $link['access_token'];

echo "=== テスト1: ファイル一覧 (findFileId) ===\n";
$url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
    'spaces' => 'appDataFolder',
    'q'      => "name='mypage_data.json'",
    'fields' => 'files(id)',
]);
$r = debug_curl('GET', $url, null, ['Authorization: Bearer ' . $token]);
echo "HTTP: " . $r['http_code'] . "\n";
echo "cURL errno: " . $r['errno'] . " " . $r['error'] . "\n";
echo "Response: " . $r['resp'] . "\n\n";

$file_id = null;
$data = json_decode($r['resp'], true);
if (!empty($data['files'][0]['id'])) {
    $file_id = $data['files'][0]['id'];
    echo "既存ファイルID: " . $file_id . "\n\n";
} else {
    echo "既存ファイルなし → 新規作成テストへ\n\n";
}

echo "=== テスト2: データ書き込み ===\n";
$json = json_encode(['version' => 1, 'test' => true, 'exported_at' => time()], JSON_UNESCAPED_UNICODE);

if ($file_id) {
    echo "方式: PATCH (既存更新)\n";
    $r2 = debug_curl(
        'PATCH',
        'https://www.googleapis.com/upload/drive/v3/files/' . urlencode($file_id) . '?uploadType=media',
        $json,
        ['Authorization: Bearer ' . $token, 'Content-Type: application/json']
    );
} else {
    echo "方式: POST multipart (新規作成)\n";
    $boundary = 'testboundary123';
    $meta     = json_encode(['name' => 'mypage_data.json', 'parents' => ['appDataFolder']]);
    $body     = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$meta}\r\n"
              . "--{$boundary}\r\nContent-Type: application/json\r\n\r\n{$json}\r\n"
              . "--{$boundary}--";
    $r2 = debug_curl(
        'POST',
        'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
        $body,
        ['Authorization: Bearer ' . $token, "Content-Type: multipart/related; boundary={$boundary}"]
    );
}
echo "HTTP: " . $r2['http_code'] . "\n";
echo "cURL errno: " . $r2['errno'] . " " . $r2['error'] . "\n";
echo "Response: " . $r2['resp'] . "\n";
