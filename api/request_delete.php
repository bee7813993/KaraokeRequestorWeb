<?php
/**
 * /api/request_delete.php
 *
 * 予約1件の削除 (delete.php の削除パスの JSON 版)。
 *
 * パラメータ:
 *   id (必須) 削除対象のリクエストID (正整数)
 *
 * 応答: { "ok":true, "data": { "id":N, "deleted":true } }
 *       不在は 404、id 不正は 400
 */
require_once __DIR__ . '/_common.php';

$id = api_param('id');
if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
    api_error('invalid id');
}
$id = (int)$id;

try {
    $stmt = $db->prepare("SELECT id FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $found = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($found === false) {
        api_error('not found', 404);
    }

    $stmt = $db->prepare("DELETE FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stmt->execute()) {
        api_error('delete failed', 500);
    }

    normalize_reqorder($db);
} catch (PDOException $e) {
    api_error('DB error', 500);
}

notify_requestlist_update();

api_ok(['id' => $id, 'deleted' => true]);
