<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'function_updatenotice.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$ids = $input['ids'] ?? [];

// Validate: positive integers only
$ids = array_values(array_filter($ids, function ($v) {
    return is_numeric($v) && (int)$v > 0;
}));

if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid IDs']);
    exit;
}

$total = count($ids);
try {
    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE requesttable SET reqorder = :reqorder WHERE id = :id");
    foreach ($ids as $index => $id) {
        // First item gets highest reqorder (displayed first, ORDER BY reqorder DESC)
        $reqorder = ($total - $index) * 10;
        $stmt->bindValue(':reqorder', $reqorder, PDO::PARAM_INT);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
    }
    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
    exit;
}

$un = new UpdateNotice();
$un->initdb();
$un->updaterequestlist();
$un->closedb();

echo json_encode(['status' => 'ok']);
