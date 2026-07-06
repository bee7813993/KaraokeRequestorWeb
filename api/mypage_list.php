<?php
/**
 * /api/mypage_list.php
 *
 * マイページの一覧取得 (履歴 / あとで歌う / お気に入り曲)。
 * 追加・削除は既存の mypage_api.php を使う (本 API は取得系のみ)。
 *
 * ユーザー識別は Cookie `YkariUserID` (UUID)。Web 版と同じ UUID を送れば
 * データを共有できる。Cookie が無い場合は新規ユーザーとして UUID が発行され
 * Set-Cookie で返る (一覧は空)。
 *
 * パラメータ:
 *   kind  (必須) history | later | favorite_song
 *   sort  (任意) date (既定) | count (history のみ) | filedate
 *   order (任意) desc (既定) | asc
 *   limit (任意) history の取得上限 (既定 200)
 *
 * 応答:
 * {
 *   "ok": true,
 *   "data": {
 *     "kind": "history",
 *     "userid": "UUID",
 *     "displayname": "...",
 *     "items": [
 *       // history: { fullpath, songfile, kind, times, last_requested_at, first_id }
 *       // later / favorite_song: { fullpath, songfile, kind, added_at }
 *     ]
 *   }
 * }
 *
 * usemypage=0 のサーバーでは 404 を返す。
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../mypage_class.php';

if (!configbool('usemypage', false)) {
    api_error('mypage is disabled on this server', 404);
}

$kind = (string)api_param('kind', '');
if (!in_array($kind, ['history', 'later', 'favorite_song'], true)) {
    api_error('invalid kind (history|later|favorite_song)');
}

$sort = (string)api_param('sort', 'date');
if (!in_array($sort, ['date', 'count', 'filedate'], true)) {
    $sort = 'date';
}
$order = (string)api_param('order', 'desc');
if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'desc';
}
$limit = api_param('limit');
$limit = ($limit !== null && ctype_digit((string)$limit) && (int)$limit > 0) ? (int)$limit : 200;

try {
    $mypage = new MypageUser($db);

    switch ($kind) {
        case 'history':
            $items = $mypage->getHistory($sort, $order, $limit);
            break;
        case 'later':
            $items = $mypage->getLaterList($sort, $order);
            break;
        default:
            $items = $mypage->getFavoriteSongs($sort, $order);
            break;
    }

    api_ok([
        'kind'        => $kind,
        'userid'      => $mypage->getUserId(),
        'displayname' => $mypage->getDisplayName(),
        'items'       => $items,
    ]);
} catch (Exception $e) {
    api_error('DB error', 500);
}
