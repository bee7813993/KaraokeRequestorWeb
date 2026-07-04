<?php
/**
 * /api/playstatus.php
 *
 * 予約1件の再生状況変更 (changeplaystatus.php の JSON 版)。
 *
 * パラメータ:
 *   id         (必須) 対象のリクエストID (正整数)
 *   nowplaying (必須) 再生状況。数値 (1〜7) または文字列
 *                     1=未再生 2=再生中 3=停止中 4=再生済 5=再生済？ 6=再生開始待ち 7=変更中
 *
 * 応答: { "ok":true, "data": { "id":N, "nowplaying":"再生済" } }
 */
require_once __DIR__ . '/_common.php';

// 許可する再生状況の値 (ホワイトリスト。changeplaystatus.php と同一)
$allowed_nowplaying = ['未再生', '再生中', '停止中', '再生済', '再生済？', '再生開始待ち', '変更中'];

$id = api_param('id');
if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
    api_error('invalid id');
}
$id = (int)$id;

// 再生状況、数値対応 (changeplaystatus.php と同じ変換)
$nowplaying_map = [
    '1' => '未再生',
    '2' => '再生中',
    '3' => '停止中',
    '4' => '再生済',
    '5' => '再生済？',
    '6' => '再生開始待ち',
    '7' => '変更中',
];
$l_nowplaying = (string)api_param('nowplaying', '');
if (array_key_exists($l_nowplaying, $nowplaying_map)) {
    $l_nowplaying = $nowplaying_map[$l_nowplaying];
}

if (!in_array($l_nowplaying, $allowed_nowplaying, true)) {
    api_error('invalid nowplaying');
}

try {
    $stmt = $db->prepare("SELECT id FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $found = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($found === false) {
        api_error('not found', 404);
    }

    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE requesttable SET nowplaying = :nowplaying WHERE id = :id");
    $stmt->bindValue(':nowplaying', $l_nowplaying, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stmt->execute()) {
        $db->rollBack();
        api_error('update failed', 500);
    }
    $db->commit();
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    api_error('DB error', 500);
}

notify_requestlist_update();

api_ok(['id' => $id, 'nowplaying' => $l_nowplaying]);
