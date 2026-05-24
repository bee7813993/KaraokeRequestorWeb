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
        // 先頭アイテム(index=0)が最大reqorder（ORDER BY reqorder DESC で先頭表示）
        // 10倍間隔ではなく連番で割り当てることで他の処理との採番方式を統一する
        $reqorder = $total - $index;
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

// ドラッグ&ドロップ対象外のレコード（再生中など）も含めて連番に正規化する
normalize_reqorder($db);

$un = new UpdateNotice();
$un->initdb();
$un->updaterequestlist();
$un->closedb();

echo json_encode(['status' => 'ok']);
