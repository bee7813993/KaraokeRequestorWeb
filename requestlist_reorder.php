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

try {
    // ⑭ fix: 未再生アイテムのみ並び替え対象とし、再生中・再生済みの順番は保持する
    $db->exec("BEGIN IMMEDIATE;");

    // DB上の現在の状態を取得
    $state = [];
    $rows = $db->query("SELECT id, nowplaying, reqorder FROM requesttable")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $state[(int)$row['id']] = $row;
    }

    // 再生済み・再生中の最大reqorder（未再生アイテムはこれより大きい値を割り当てる）
    $played_max = 0;
    foreach ($state as $row) {
        if ($row['nowplaying'] !== '未再生') {
            $played_max = max($played_max, (int)$row['reqorder']);
        }
    }

    // ユーザーが指定した順序から未再生のみ抽出
    $unplayed_ids = [];
    foreach ($ids as $id) {
        $iid = (int)$id;
        if (isset($state[$iid]) && $state[$iid]['nowplaying'] === '未再生') {
            $unplayed_ids[] = $iid;
        }
    }

    $count = count($unplayed_ids);
    $stmt = $db->prepare("UPDATE requesttable SET reqorder = :reqorder WHERE id = :id");
    foreach ($unplayed_ids as $index => $id) {
        // 先頭アイテム(index=0)が最大reqorder（ORDER BY reqorder DESC で先頭表示）
        $reqorder = $played_max + ($count - $index);
        $stmt->bindValue(':reqorder', $reqorder, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // 全レコードを連番に正規化する（再生済みとの隙間も吸収）
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
