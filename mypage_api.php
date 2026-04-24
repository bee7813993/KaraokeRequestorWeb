<?php
/**
 * mypage_api.php
 * 後で歌う・お気に入り曲・お気に入り検索ワードの追加/削除 AJAX API
 * GET: action, fullpath, songfile, kind, keyword, search_type, search_params, kw_id
 * Response: JSON {"status":"added"|"removed"|"error", "message":"..."}
 */
require_once 'commonfunc.php';
require_once 'mypage_class.php';

header('Content-Type: application/json; charset=utf-8');

if (!configbool("usemypage", true)) {
    echo json_encode(['status' => 'error', 'message' => 'mypage disabled']);
    exit;
}

$mypage = new MypageUser($db);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add_later':
        $fullpath = isset($_GET['fullpath']) ? $_GET['fullpath'] : '';
        $songfile = isset($_GET['songfile']) ? $_GET['songfile'] : '';
        $kind     = isset($_GET['kind'])     ? $_GET['kind']     : '';
        if (empty($fullpath) && empty($songfile)) {
            echo json_encode(['status' => 'error', 'message' => 'missing params']);
            exit;
        }
        if ($mypage->isInLater($fullpath)) {
            $mypage->removeLater($fullpath);
            echo json_encode(['status' => 'removed']);
        } else {
            $mypage->addLater($fullpath, $songfile, $kind);
            echo json_encode(['status' => 'added']);
        }
        break;

    case 'remove_later':
        $fullpath = isset($_GET['fullpath']) ? $_GET['fullpath'] : '';
        $mypage->removeLater($fullpath);
        echo json_encode(['status' => 'removed']);
        break;

    case 'add_favorite_song':
        $fullpath = isset($_GET['fullpath']) ? $_GET['fullpath'] : '';
        $songfile = isset($_GET['songfile']) ? $_GET['songfile'] : '';
        $kind     = isset($_GET['kind'])     ? $_GET['kind']     : '';
        if (empty($fullpath) && empty($songfile)) {
            echo json_encode(['status' => 'error', 'message' => 'missing params']);
            exit;
        }
        if ($mypage->isInFavoriteSong($fullpath)) {
            $mypage->removeFavoriteSong($fullpath);
            echo json_encode(['status' => 'removed']);
        } else {
            $mypage->addFavoriteSong($fullpath, $songfile, $kind);
            echo json_encode(['status' => 'added']);
        }
        break;

    case 'remove_favorite_song':
        $fullpath = isset($_GET['fullpath']) ? $_GET['fullpath'] : '';
        $mypage->removeFavoriteSong($fullpath);
        echo json_encode(['status' => 'removed']);
        break;

    case 'add_favorite_keyword':
        $keyword      = isset($_GET['keyword'])       ? $_GET['keyword']       : '';
        $search_type  = isset($_GET['search_type'])   ? $_GET['search_type']   : 'search';
        $search_params = isset($_GET['search_params']) ? $_GET['search_params'] : '';
        if ($mypage->addFavoriteKeyword($keyword, $search_type, $search_params)) {
            echo json_encode(['status' => 'added']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'empty keyword']);
        }
        break;

    case 'remove_favorite_keyword':
        $kw_id = isset($_GET['kw_id']) ? (int)$_GET['kw_id'] : 0;
        $mypage->removeFavoriteKeyword($kw_id);
        echo json_encode(['status' => 'removed']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'unknown action']);
        break;
}
