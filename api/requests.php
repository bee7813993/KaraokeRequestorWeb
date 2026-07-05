<?php
/**
 * /api/requests.php
 *
 * 予約一覧の取得 (requestlist_swipe_json.php のエンベロープ版)。
 * モバイルアプリのポーリング基点。データ構築は function_requestlist_json.php を共用する。
 *
 * パラメータ:
 *   limit  (任意) 取得件数 (省略時は全件)
 *   offset (任意) 取得開始位置
 *
 * 応答: { "ok":true, "data": { "items":[...], "total":N, "has_more":bool,
 *         "remaining_count":N, "remaining_seconds":N } }
 *
 * - items は reqorder 降順 (リストの先頭 = 最後に再生される曲)
 * - シークレット予約は display_name が伏せ字テキストになる
 * - fullpath は含まれない (必要なら change.php?format=json&id=N)
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../function_requestlist_json.php';

$limit  = api_param('limit');
$offset = api_param('offset');
$limit  = ($limit  !== null && ctype_digit((string)$limit))  ? (int)$limit  : 0;
$offset = ($offset !== null && ctype_digit((string)$offset)) ? (int)$offset : 0;

try {
    $data = build_requestlist_data($db, $config_ini, $limit, $offset);
} catch (PDOException $e) {
    api_error('DB error', 500);
}

api_ok($data);
