<?php
/**
 * /api/mypage.php
 *
 * マイページのアプリ連携 API (mypage_class.php の JSON ファサード)。
 * Web 版は cookie (YkariUserID) でユーザーを識別するが、アプリはデバイスリンクの
 * ペアコード (mypage_link_device.php で発行) から取得した userid をクエリで渡す。
 * userid (UUID) を知っていること自体が認可となる (Web 版 cookie と同じモデル)。
 *
 * アクション (action):
 *   pair_apply      code=XXXXXX  → { userid } コードを消費して userid を返す
 *   pair_generate   userid=      → { code } このユーザーのペアコードを発行 (5分有効)
 *   summary         userid=      → 表示名・各リスト件数・Google 連携有無
 *   history         userid= [sort=date|name|filedate] [order=asc|desc]
 *   history_add     userid= fullpath= songfile= [kind=]
 *   later           userid= / later_add (同上) / later_remove userid= fullpath=
 *   favorite        userid= / favorite_add (同上) / favorite_remove userid= fullpath=
 *   keyword         userid=
 *   keyword_add     userid= keyword= [search_type=] [search_params=]
 *   keyword_remove  userid= id=
 *   import          userid= data= (POST) → Web 版エクスポート形式 (version 1) の JSON を
 *                   マージ取り込み。重複はスキップされるため何度実行しても安全
 *
 * 書き込み系アクションの成功時は、Google 連携済み+自動同期オンなら Drive へも
 * 自動保存する (Web 版 mypage_api.php と同じ挙動)。
 * ※ アプリの Google 同期はアプリ自身が relay 経由で認証して Drive を直接読み書きする
 *    方式のため、この API に Google 系アクションは無い (トークンを返す API を持たない)。
 *
 * 応答: { "ok":true, "data":{...} } / { "ok":false, "error":"..." }
 */
require_once __DIR__ . '/_common.php';

if (!configbool('usemypage', true)) {
    api_error('マイページ機能が無効です', 503);
}
require_once __DIR__ . '/../mypage_class.php';

$action = (string)api_param('action', '');

// ---- ペアコード適用 (userid 不要。'' を渡して cookie を使わないモードにする) ----
if ($action === 'pair_apply') {
    $code = (string)api_param('code', '');
    $mypage = new MypageUser($db, '');
    $userid = $mypage->lookupPairingCode($code);
    if ($userid === null) {
        api_error('コードが無効または有効期限切れです (有効期限は5分です)', 404);
    }
    api_ok(['userid' => $userid]);
}

// ---- 以降は userid 必須 ----
$userid = (string)api_param('userid', '');
$mypage = new MypageUser($db, $userid);
if ($mypage->getUserId() === '') {
    api_error('invalid userid');
}

/** 追加系アクションの共通パラメータを取り出す (fullpath / songfile 必須)。 */
function mypage_song_params()
{
    $fullpath = (string)api_param('fullpath', '');
    $songfile = (string)api_param('songfile', '');
    if ($fullpath === '' || $songfile === '') {
        api_error('fullpath and songfile are required');
    }
    return [$fullpath, $songfile, (string)api_param('kind', '')];
}

/** 書き込み成功後の自動 Drive 同期。同期失敗で書き込み自体の応答は壊さない。 */
function mypage_auto_sync($mypage)
{
    try {
        $mypage->autoSyncToDrive();
    } catch (Exception $e) {
        // 自動同期は best effort (次回の書き込みまたは手動同期で追い付く)
    }
}

switch ($action) {
    case 'pair_generate':
        api_ok(['code' => $mypage->generatePairingCode()]);
        break;

    case 'summary':
        $link = $mypage->getGoogleLink();
        api_ok([
            'displayname'   => $mypage->getDisplayName(),
            'history'       => count($mypage->getHistory()),
            'later'         => count($mypage->getLaterList()),
            'favorite'      => count($mypage->getFavoriteSongs()),
            'keyword'       => count($mypage->getFavoriteKeywords()),
            'google_linked' => $link !== null,
        ]);
        break;

    case 'history':
        api_ok(['items' => $mypage->getHistory(
            (string)api_param('sort', 'date'), (string)api_param('order', 'desc'))]);
        break;

    case 'history_add':
        list($fullpath, $songfile, $kind) = mypage_song_params();
        $mypage->addHistory($fullpath, $songfile, $kind);
        mypage_auto_sync($mypage);
        api_ok(['added' => true]);
        break;

    case 'history_remove':
        $mypage->removeHistory((string)api_param('fullpath', ''));
        mypage_auto_sync($mypage);
        api_ok(['removed' => true]);
        break;

    case 'later':
        api_ok(['items' => $mypage->getLaterList()]);
        break;

    case 'later_add':
        list($fullpath, $songfile, $kind) = mypage_song_params();
        $mypage->addLater($fullpath, $songfile, $kind);
        mypage_auto_sync($mypage);
        api_ok(['added' => true]);
        break;

    case 'later_remove':
        $mypage->removeLater((string)api_param('fullpath', ''));
        mypage_auto_sync($mypage);
        api_ok(['removed' => true]);
        break;

    case 'favorite':
        api_ok(['items' => $mypage->getFavoriteSongs()]);
        break;

    case 'favorite_add':
        list($fullpath, $songfile, $kind) = mypage_song_params();
        $mypage->addFavoriteSong($fullpath, $songfile, $kind);
        mypage_auto_sync($mypage);
        api_ok(['added' => true]);
        break;

    case 'favorite_remove':
        $mypage->removeFavoriteSong((string)api_param('fullpath', ''));
        mypage_auto_sync($mypage);
        api_ok(['removed' => true]);
        break;

    case 'keyword':
        api_ok(['items' => $mypage->getFavoriteKeywords()]);
        break;

    case 'keyword_add':
        $ok = $mypage->addFavoriteKeyword(
            (string)api_param('keyword', ''),
            (string)api_param('search_type', ''),
            (string)api_param('search_params', ''));
        if (!$ok) {
            api_error('keyword is required');
        }
        mypage_auto_sync($mypage);
        api_ok(['added' => true]);
        break;

    case 'keyword_remove':
        // id 指定 (Web と同じ) または条件指定 (id を持たないアプリからの解除)
        $id = (int)api_param('id', 0);
        if ($id > 0) {
            $mypage->removeFavoriteKeyword($id);
        } else {
            $mypage->removeFavoriteKeywordByCondition(
                (string)api_param('keyword', ''),
                (string)api_param('search_type', ''),
                (string)api_param('search_params', ''));
        }
        mypage_auto_sync($mypage);
        api_ok(['removed' => true]);
        break;

    case 'import':
        // 端末内データの一括統合 (アプリのデバイスリンク時・再接続時に使う)
        $data = json_decode((string)api_param('data', ''), true);
        if (!is_array($data)) {
            api_error('data (JSON) is required');
        }
        $result = $mypage->importData($data, false);
        if (empty($result['ok'])) {
            api_error(isset($result['error']) ? $result['error'] : 'import failed');
        }
        mypage_auto_sync($mypage); // 統合結果も Drive へ反映
        api_ok(['counts' => $result['counts']]);
        break;

    default:
        api_error('unknown action: ' . $action);
}
