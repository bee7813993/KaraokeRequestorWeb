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
    // BEGIN IMMEDIATE で書き込みロックを先取りし、連番割り当て〜正規化を分断されずに一括処理する
    $db->exec("BEGIN IMMEDIATE;");
    $stmt = $db->prepare("UPDATE requesttable SET reqorder = :reqorder WHERE id = :id");
    foreach ($ids as $index => $id) {
        // 先頭アイテム(index=0)が最大reqorder（ORDER BY reqorder DESC で先頭表示）
        // 10倍間隔ではなく連番で割り当てることで他の処理との採番方式を統一する
        $reqorder = $total - $index;
        $stmt->bindValue(':reqorder', $reqorder, PDO::PARAM_INT);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
    }
    // ドラッグ&ドロップ対象外のレコード（再生中など）も含めて連番に正規化する
    // 同一トランザクション内で実行することで、normalize と連番割り当ての間に
    // 別プロセスが割り込んで reqorder を変更する競合状態を防ぐ
    normalize_reqorder($db, true);
    $db->exec("COMMIT;");
} catch (Exception $e) {
    $db->exec("ROLLBACK;");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
    exit;
}

$un = new UpdateNotice();
$un->initdb();
$un->updaterequestlist();
$un->closedb();

echo json_encode(['status' => 'ok']);
