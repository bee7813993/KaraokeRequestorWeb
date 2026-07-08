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
 *   google_status   userid=      → 連携有無・メール・自動同期・最終同期時刻
 *   google_sync     userid= direction=to_drive|from_drive → Google Drive と同期
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
        api_ok(['added' => true]);
        break;

    case 'later':
        api_ok(['items' => $mypage->getLaterList()]);
        break;

    case 'later_add':
        list($fullpath, $songfile, $kind) = mypage_song_params();
        $mypage->addLater($fullpath, $songfile, $kind);
        api_ok(['added' => true]);
        break;

    case 'later_remove':
        $mypage->removeLater((string)api_param('fullpath', ''));
        api_ok(['removed' => true]);
        break;

    case 'favorite':
        api_ok(['items' => $mypage->getFavoriteSongs()]);
        break;

    case 'favorite_add':
        list($fullpath, $songfile, $kind) = mypage_song_params();
        $mypage->addFavoriteSong($fullpath, $songfile, $kind);
        api_ok(['added' => true]);
        break;

    case 'favorite_remove':
        $mypage->removeFavoriteSong((string)api_param('fullpath', ''));
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
        api_ok(['added' => true]);
        break;

    case 'keyword_remove':
        $mypage->removeFavoriteKeyword((int)api_param('id', 0));
        api_ok(['removed' => true]);
        break;

    case 'google_status':
        $link = $mypage->getGoogleLink();
        if ($link === null) {
            api_ok(['linked' => false]);
        }
        api_ok([
            'linked'         => true,
            'email'          => $link['google_email'],
            'auto_sync'      => (int)$link['auto_sync'] === 1,
            'last_synced_at' => (int)$link['last_synced_at'],
        ]);
        break;

    case 'google_sync':
        global $config_ini;
        $relay_url    = $config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php';
        $relay_secret = $config_ini['google_relay_secret'] ?? '';
        $client_id    = $config_ini['google_client_id'] ?? '';
        if (empty($client_id) || empty($relay_secret)) {
            api_error('Google同期が設定されていません (管理者設定が必要です)', 503);
        }
        $link = $mypage->getGoogleLink();
        if ($link === null) {
            api_error('Google アカウントと連携されていません', 404);
        }
        require_once __DIR__ . '/../mypage_google_drive.php';
        $drive = new GoogleDriveHelper(
            $link['access_token'],
            $link['refresh_token'],
            $link['token_expires_at'],
            $relay_url,
            $relay_secret
        );
        $direction = (string)api_param('direction', 'to_drive');
        if ($direction === 'from_drive') {
            // Drive → サーバー (マージ)
            $drive_data = $drive->readData();
            if (!$drive_data) {
                api_error('Drive からデータを取得できませんでした', 502);
            }
            $mypage->importData($drive_data, false);
        } else {
            // サーバー → Drive
            if (!$drive->writeData($mypage->exportData())) {
                api_error('Drive への書き込みに失敗しました', 502);
            }
        }
        list($new_at, $new_exp, $refreshed) = $drive->getNewTokens();
        if ($refreshed) {
            $mypage->updateGoogleTokens($new_at, $new_exp);
        }
        $mypage->updateGoogleSyncTime();
        api_ok(['synced' => true, 'direction' => $direction]);
        break;

    default:
        api_error('unknown action: ' . $action);
}
