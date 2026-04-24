<?php
/**
 * Google OAuth コールバック（ローカルサーバー側）
 *
 * 中継サーバーからリダイレクトされてきた payload を検証し、
 * Google アカウントをローカルユーザーに紐付ける。
 */
require_once 'commonfunc.php';
require_once 'kara_config.php';
require_once 'mypage_class.php';
require_once 'mypage_google_drive.php';

function redirect_error($msg) {
    header('Location: mypage_google_sync.php?error=' . urlencode($msg));
    exit;
}

if (!configbool("usemypage", true)) {
    header('Location: mypage.php');
    exit;
}

global $config_ini, $db;
$relay_secret = $config_ini['google_relay_secret'] ?? '';
$relay_url    = $config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php';

if (empty($relay_secret)) {
    redirect_error('not_configured');
}

// ---- payload を検証 ----
$raw_payload = $_GET['payload'] ?? '';
if (empty($raw_payload)) {
    redirect_error('no_payload');
}

$parts = explode('.', $raw_payload, 2);
if (count($parts) !== 2) {
    redirect_error('invalid_payload');
}
[$b64, $hmac_received] = $parts;

$json = base64_decode(strtr($b64, '-_', '+/') . str_repeat('=', (4 - strlen($b64) % 4) % 4));
if ($json === false) {
    redirect_error('invalid_payload');
}

$hmac_expected = hash_hmac('sha256', $json, $relay_secret);
if (!hash_equals($hmac_expected, $hmac_received)) {
    redirect_error('hmac_mismatch');
}

$data = json_decode($json, true);
if (empty($data)) {
    redirect_error('invalid_payload');
}

// iat チェック（10分以内）
if (abs(time() - (int)($data['iat'] ?? 0)) > 600) {
    redirect_error('payload_expired');
}

// nonce を Cookie と照合
$nonce_cookie = $_COOKIE['YkariGoogleNonce'] ?? '';
if (empty($nonce_cookie) || !hash_equals($nonce_cookie, $data['nonce'] ?? '')) {
    redirect_error('nonce_mismatch');
}
// nonce Cookie を削除
setcookie('YkariGoogleNonce', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);

// ---- 必須フィールドを確認 ----
$google_sub    = $data['google_sub']    ?? '';
$google_email  = $data['google_email']  ?? '';
$access_token  = $data['access_token']  ?? '';
$refresh_token = $data['refresh_token'] ?? '';
$expires_at    = (int)($data['expires_at'] ?? 0);

if (empty($google_sub) || empty($access_token)) {
    redirect_error('missing_token');
}

// ---- ユーザー紐付け ----
$mypage = new MypageUser($db);
$mypage->linkGoogle($google_sub, $google_email, $access_token, $refresh_token, $expires_at);

// ---- 初回同期: Drive からデータを取得して merge ----
$drive = new GoogleDriveHelper(
    $access_token,
    $refresh_token,
    $expires_at,
    $relay_url,
    $relay_secret
);

$drive_data = $drive->readData();
if ($drive_data) {
    $mypage->importData($drive_data, false); // merge
}

// トークンが自動更新された場合は保存
[$new_access_token, $new_expires_at, $refreshed] = $drive->getNewTokens();
if ($refreshed) {
    $mypage->updateGoogleTokens($new_access_token, $new_expires_at);
}

$mypage->updateGoogleSyncTime();

header('Location: mypage_google_sync.php?linked=1');
exit;
