<?php
require_once 'commonfunc.php';
require_once 'configauth_class.php';
require_once 'easyauth_class.php';
require_once 'function_setlist_stats.php';

$configauth = new ConfigAuth();
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'admin' || !$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = setlist_stats_get_data(true);
$ok = empty($data['error']);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'ok' => $ok,
    'updated_at' => $data['updated_at'] ?? '',
    'categories' => $data['categories'] ?? [],
    'total_categories' => $data['total_categories'] ?? count($data['categories'] ?? []),
    'search_backend' => $data['search_backend'] ?? 'listerdb',
    'cache' => $data['cache'] ?? '',
    'error' => $data['error'] ?? null,
    'warning' => $data['warning'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
