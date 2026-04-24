<?php
/**
 * Google OAuth 連携開始
 * ローカルサーバーから呼び出し → 中継サーバー経由で Google OAuth を開始する
 */
require_once 'kara_config.php';
require_once 'mypage_class.php';

if (!configbool("usemypage", true)) {
    header('Location: mypage.php');
    exit;
}

global $config_ini;
$relay_url    = $config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php';
$relay_secret = $config_ini['google_relay_secret'] ?? '';
$client_id    = $config_ini['google_client_id'] ?? '';

if (empty($client_id) || empty($relay_secret)) {
    header('Location: mypage_google_sync.php?error=not_configured');
    exit;
}

// return_url: このサーバーのコールバック先
$return_url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://')
            . $_SERVER['HTTP_HOST']
            . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')
            . '/mypage_google_callback.php';

// state を作成: JSON をシリアライズして HMAC 署名
$nonce    = bin2hex(random_bytes(16));
$state_payload = json_encode([
    'nonce'      => $nonce,
    'return_url' => $return_url,
    'iat'        => time(),
]);
$state_b64  = rtrim(strtr(base64_encode($state_payload), '+/', '-_'), '=');
$state_hmac = hash_hmac('sha256', $state_payload, $relay_secret);
$state      = $state_b64 . '.' . $state_hmac;

// nonce を Cookie に保存（10分）
setcookie('YkariGoogleNonce', $nonce, time() + 600, '/', '', !empty($_SERVER['HTTPS']), true);

// 中継サーバーへ redirect（中継がGoogleにリダイレクトする）
$params = http_build_query([
    'action'    => 'auth',
    'client_id' => $client_id,
    'state'     => $state,
]);
header('Location: ' . $relay_url . '?' . $params);
exit;
