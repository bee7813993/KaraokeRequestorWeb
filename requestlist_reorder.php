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

// 再生済み・再生中の曲も含めて、誰でも並べ替えられる (記録として正しい順に直せるようにする)。
// 設定 reorder_played_adminonly=1 のときだけ、管理者以外は未再生の曲どうしの並べ替えに限る。
$restrict_played = configbool('reorder_played_adminonly', false) && $user !== 'admin';

try {
    $db->exec("BEGIN IMMEDIATE;");

    // DB上の現在の状態を取得
    $state = [];
    $rows = $db->query("SELECT id, nowplaying, reqorder FROM requesttable")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $state[(int)$row['id']] = $row;
    }

    // 送られてきた順序 (画面の上から順) のうち、動かせる曲だけを取り出す
    $sent_ids   = [];
    $target_ids = [];
    foreach ($ids as $id) {
        $iid = (int)$id;
        if (!isset($state[$iid]) || in_array($iid, $sent_ids, true)) continue;
        $sent_ids[] = $iid;
        if ($restrict_played && $state[$iid]['nowplaying'] !== '未再生') continue;
        $target_ids[] = $iid;
    }

    // 動かす曲が今使っている reqorder の枠を、送られてきた順に割り当て直す。
    // 画面には一部の曲だけが表示されている (「もっと見る」で追加読み込み) ことがあり、
    // 送られてこなかった曲や動かせない曲の位置は変えない。
    // 一覧は reqorder の大きい順に表示されるので、先頭の曲に一番大きい枠を割り当てる。
    $slots = [];
    foreach ($target_ids as $iid) {
        $slots[] = (int)$state[$iid]['reqorder'];
    }
    rsort($slots, SORT_NUMERIC);

    $new_order = [];
    foreach ($sent_ids as $iid) {
        $new_order[$iid] = (int)$state[$iid]['reqorder'];
    }
    $stmt = $db->prepare("UPDATE requesttable SET reqorder = :reqorder WHERE id = :id");
    foreach ($target_ids as $index => $iid) {
        $new_order[$iid] = $slots[$index];
        if ((int)$state[$iid]['reqorder'] === $slots[$index]) continue;
        $stmt->bindValue(':reqorder', $slots[$index], PDO::PARAM_INT);
        $stmt->bindValue(':id', $iid, PDO::PARAM_INT);
        $stmt->execute();
    }

    // 管理者限定の設定で動かせない曲があり、送られてきた順序どおりにならなかったか
    arsort($new_order, SORT_NUMERIC);
    $adjusted = (array_keys($new_order) !== $sent_ids);

    // 全レコードを連番に正規化する
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

// adjusted: 管理者限定の設定で再生済み・再生中の曲を動かせず、指定どおりの順にならなかった (画面で理由を表示する)
echo json_encode(['status' => 'ok', 'adjusted' => $adjusted]);
