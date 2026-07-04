<?php
/**
 * /api/request_move.php
 *
 * 予約1件の個別移動 (delete.php の up / down / warikomi パスの JSON 版)。
 * 移動ロジック本体は function_requestops.php (delete.php と共用) を使用する。
 *
 * パラメータ:
 *   id     (必須) 対象のリクエストID (正整数)
 *   action (必須) up = 上へ / down = 下へ / warikomi = 次に再生
 *
 * 応答: { "ok":true, "data": { "id":N, "action":"up", "message":"..." } }
 *       message には移動関数の出力 (「すでに一番上です。」等) をタグ除去して格納。
 *       空文字なら正常に移動した。
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../function_requestops.php';

$id = api_param('id');
if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
    api_error('invalid id');
}
$id = (int)$id;

$action = (string)api_param('action', '');
if (!in_array($action, ['up', 'down', 'warikomi'], true)) {
    api_error('invalid action (up|down|warikomi)');
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

    // 移動関数は結果を print するため、出力を捕捉して JSON の message に載せる
    ob_start();
    switch ($action) {
        case 'up':
            dbup($id, $db);
            break;
        case 'down':
            dbdown($id, $db);
            break;
        case 'warikomi':
            warikomi($id, $db);
            break;
    }
    $message = trim(strip_tags(ob_get_clean()));

    normalize_reqorder($db);
} catch (PDOException $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    api_error('DB error', 500);
}

notify_requestlist_update();

api_ok(['id' => $id, 'action' => $action, 'message' => $message]);
