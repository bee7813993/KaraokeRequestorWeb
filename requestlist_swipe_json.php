<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'function_requestlist_json.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

header('Content-Type: application/json; charset=utf-8');

$limit  = isset($_GET['limit'])  && ctype_digit($_GET['limit'])  ? (int)$_GET['limit']  : 0;
$offset = isset($_GET['offset']) && ctype_digit($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $data = build_requestlist_data($db, $config_ini, $limit, $offset);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
