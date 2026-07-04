<?php
// 設定一覧をjsonで返す。管理者認証必須。
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Configuration"');
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized');
}

if ($_SERVER['PHP_AUTH_USER'] !== 'admin' || !$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Configuration"');
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized');
}

// 認証後もレスポンスに含めない機微キー
$sensitive_keys = [
    'useeasyauth_word',
    'google_client_secret',
    'configpass',
];

$configfile = 'config.ini';
$config_ini = [];

if (file_exists($configfile)) {
    $config_ini = parse_ini_file($configfile);

    foreach ($sensitive_keys as $key) {
        if (isset($config_ini[$key])) {
            $config_ini[$key] = '***';
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    print json_encode($config_ini, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    header('HTTP/1.0 404 Not Found');
    print json_encode(['error' => "no $configfile"]);
}
