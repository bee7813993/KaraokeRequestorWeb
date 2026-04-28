<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

// --- パラメータ ---
$word = array_key_exists("searchword", $_REQUEST) ? trim($_REQUEST["searchword"]) : '';
if (!empty($word) && $historylog == 1) {
    searchwordhistory('file:' . $word);
}
$l_order  = array_key_exists("order", $_REQUEST) ? $_REQUEST["order"] : '';
$selectid = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$is_swap  = is_numeric($selectid) && $selectid !== '';

// 差し替え元の曲名取得
$swap_song = '';
if ($is_swap) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT songfile, singer FROM requesttable WHERE id = :id");
        $stmt->execute([':id' => (int)$selectid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $swap_song = $row['songfile'] . ($row['singer'] ? '　/ ' . $row['singer'] : '');
        }
    } catch (Exception $e) {
        // DB取得失敗はサイレントに無視
    }
}

// 検索結果件数の先行取得
$result_count = false;
if (!empty($word)) {
    $result_count = searchresultcount_fromkeyword($word);
}

// --- 関数定義 ---

function _section_open($id, $title, $first) {
    $collapsed     = $first ? '' : ' collapsed';
    $expanded_attr = $first ? 'true' : 'false';
    $show_class    = $first ? ' show' : '';
    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>';
    print '<div class="search-section">';
    print '<div class="search-section-header" data-bs-toggle="collapse" data-bs-target="#' . $id . '"'
        . ' aria-expanded="' . $expanded_attr . '" aria-controls="' . $id . '"'
        . ' role="button">';
    print $title;
    print '<span class="collapse-icon">' . $icon_svg . '</span>';
    print '</div>';
    print '<div id="' . $id . '" class="collapse' . $show_class . '">';
    print '<div class="search-section-body">';
}

function _section_close() {
    print '</div></div></div>'; // body / collapse / section
}

function print_listerdb_search() {
    global $config_ini;
    $includepage = 1;
    if (array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = $config_ini['listerDBPATH'];
    }
    require 'search_listerdb_program_index.php';
}

function print_listerdb_fileonly() {
    global $config_ini;
    $includepage = 1;
    if (array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = $config_ini['listerDBPATH'];
    }
    $filesearch = 1;
    require 'search_listerdb_anysearch_index.php';
}

function print_everything_filenamesearch($first = false) {
    global $config_ini, $word, $selectid, $result_count, $l_order, $connectinternet;

    _section_open('sec-filesearch', 'ファイル名（曲名）検索', $first);

    print '<form action="search.php" method="GET">';
    if ($is_swap = (is_numeric($selectid) && $selectid !== '')) {
        print '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />';
    }
    print '<div class="search-hero" style="box-shadow:none;padding:0;margin-bottom:0;">';
    print '<div class="search-input-wrap">';
    print '<input type="text" name="searchword" id="filenamesearchword" class="form-control-themed"'
        . ' placeholder="曲名・歌手名・作品名などで検索"'
        . ' value="' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '"'
        . ' aria-label="検索ワード" autocomplete="off" />';
    print '<button type="submit" class="btn-search-submit" id="filenamesearchsubmit">検索</button>';
    print '</div>';
    print '</div>';

    print '<div class="search-tips mt-2">';
    print 'AND: スペース区切り &nbsp; OR: <code>|</code> 区切り &nbsp; NOT: 先頭に <code>!</code> &nbsp; 全件: <code>*</code>';
    print '</div>';
    print '</form>';

    if (!empty($word)) {
        print '<div class="search-result-count mt-3"><strong>' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '</strong> の検索結果: '
            . '<strong>' . (int)$result_count . '</strong> 件</div>';
        PrintLocalFileListfromkeyword_ajax($word, $l_order, 'searchresult', 0, $selectid);
    } elseif ($result_count === 0) {
        print '<div class="notice-box mt-3">検索結果が見つかりませんでした。</div>';
    }

    _section_close();

    if ($connectinternet != 1) {
        print '<p class="mt-3"><a href="requestlist_top.php">トップに戻る</a></p>';
    }
}

function print_everything_anisoninfosearch($first = false) {
    global $config_ini, $l_q, $selectid;

    _section_open('sec-anisoninfo', '歌手名・作品名・ブランド名検索（anison.info連携）', $first);

    print '<form name="f" action="search_anisoninfo_list.php" method="get">';
    if (!empty($selectid)) {
        print '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />';
    }

    print '<div class="mb-3">';
    print '<label class="form-label-sm">検索対象</label>';
    print '<div class="d-flex flex-wrap gap-3">';
    foreach (['song' => '曲', 'pro' => '作品', 'person' => '人物', 'mkr' => '制作(ブランド)'] as $val => $lbl) {
        $checked = ($val === 'pro') ? ' checked' : '';
        print '<label class="d-flex align-items-center gap-1" style="cursor:pointer;">'
            . '<input type="radio" name="m" value="' . $val . '"' . $checked . '> ' . $lbl . '</label>';
    }
    print '</div>';
    print '</div>';

    $value_attr = isset($l_q) ? ' value="' . htmlspecialchars($l_q, ENT_QUOTES, 'UTF-8') . '"' : '';
    print '<div class="mb-3">';
    print '<label class="form-label-sm" for="anison-q">検索ワード <small>（よみがなの一部でOK）</small></label>';
    print '<input type="text" name="q" id="anison-q" class="form-control-themed"' . $value_attr . '>';
    print '</div>';

    print '<div class="d-flex flex-wrap gap-3 mb-3">';
    print '<div>';
    print '<label class="form-label-sm" for="anison-year">放映/発売年指定</label>';
    print '<select name="year" id="anison-year" class="form-control-themed" style="width:auto;">';
    print '<option value="">年指定なし</option>';
    $year = date('Y');
    for ($i = $year + 1; $i >= 1953; $i--) {
        print '<option value="' . $i . '">' . $i . '</option>';
    }
    print '</select>';
    print '</div>';

    print '<div>';
    print '<label class="form-label-sm" for="anison-genre">ジャンル指定</label>';
    print '<select name="genre" id="anison-genre" class="form-control-themed" style="width:auto;">';
    print '<option value="" selected>ジャンル指定なし</option>';
    $genres = [
        'anison' => 'アニメ/特撮/ゲーム', 'anime' => 'アニメーション', 'tv' => '　テレビアニメ',
        'vd' => '　ビデオアニメ', 'mv' => '　劇場アニメ', 'wa' => '　Webアニメ',
        'sfx' => 'テレビ特撮', 'game' => 'ゲーム', 'radio' => 'ラジオ',
        'wradio' => 'Webラジオ', 'other' => 'その他'
    ];
    foreach ($genres as $val => $lbl) {
        print '<option value="' . $val . '">' . $lbl . '</option>';
    }
    print '</select>';
    print '</div>';
    print '</div>';

    print '<button type="submit" class="btn-secondary-themed">検索</button>';
    print '</form>';

    _section_close();
}

function print_everything_banditsearch($first = false) {
    global $selectid;

    _section_open('sec-bandit', '歌手名・ゲームタイトル検索（banditの隠れ家連携）', $first);

    $sid_field = !empty($selectid) ? '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />' : '';
    $searches  = [['label' => '歌手名', 'col' => '2'], ['label' => 'ゲームタイトル', 'col' => '3'], ['label' => 'ゲームブランド', 'col' => '1']];
    foreach ($searches as $s) {
        print '<div class="d-flex align-items-center gap-2 mb-3">';
        print '<label class="form-label-sm mb-0" style="white-space:nowrap;min-width:6em;">' . $s['label'] . '</label>';
        print '<form action="searchbandit.php" method="GET" class="d-flex gap-2 flex-grow-1">';
        print $sid_field;
        print '<input type="hidden" name="column" value="' . $s['col'] . '">';
        print '<input type="text" name="searchword" class="form-control-themed flex-grow-1">';
        print '<button type="submit" class="btn-secondary-themed" style="white-space:nowrap;">検索</button>';
        print '</form>';
        print '</div>';
    }

    print '<p class="search-tips mt-2">'
        . 'インターネット上のDBから曲名を検索し、ローカルにファイルがあるかを確認します。<br>'
        . '登録されていない曲や特殊文字を含む曲名は見つからない場合があります。'
        . '</p>';

    _section_close();
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php
if (!empty($config_ini['roomurl'])) {
    $roomnames = array_keys($config_ini['roomurl']);
    echo htmlspecialchars($roomnames[0], ENT_QUOTES, 'UTF-8') . '：';
}
?>動画検索</title>
<?php print_bs5_search_head(); ?>
<link rel="stylesheet" href="css/jquery.dataTables.css">
<script src="js/jquery.dataTables.js"></script>
<script src="js/currency.js"></script>
</head>
<body>

<?php shownavigatioinbar('searchreserve.php'); ?>

<div class="container py-3">

<?php
// --- 差し替えバナー ---
if ($is_swap):
    $cancel_href = 'requestlist_top.php';
    $song_disp   = !empty($swap_song) ? htmlspecialchars($swap_song, ENT_QUOTES, 'UTF-8') : '（ID: ' . (int)$selectid . '）';
?>
<div class="swap-banner" role="alert">
  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" style="flex-shrink:0;"><path d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 0 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/></svg>
  <span>曲差し替え中: <?php echo $song_disp; ?></span>
  <a href="<?php echo htmlspecialchars($cancel_href, ENT_QUOTES, 'UTF-8'); ?>" class="swap-cancel-btn btn-secondary-themed" style="font-size:0.8rem;padding:5px 12px;">キャンセル</a>
</div>
<?php endif; ?>

<?php
// --- 予約方法タブ ---
echo build_reservation_tabs($selectid, 'search');
?>

<?php
// --- 未発見曲リクエストボタン ---
if ($usenfrequset == 1):
    $nflink = 'notfoundrequest/notfoundrequest.php' . (!empty($word) ? '?searchword=' . rawurlencode($word) : '');
?>
<div class="mb-3">
  <a href="<?php echo htmlspecialchars($nflink, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">
    探して見つからなかった曲を報告する
  </a>
</div>
<?php endif; ?>

<?php
// --- 検索画面に表示するメッセージ（設定画面から） ---
// searchitem_o 未設定の旧互換
if (!array_key_exists("searchitem_o", $config_ini) || !array_key_exists("searchitem", $config_ini)):
    print_everything_filenamesearch(true);
    print_everything_anisoninfosearch(false);
    print_everything_banditsearch(false);
else:
    // 検索完了後はファイル名検索結果だけ表示（旧来の動作）
    if (!empty($word) && $result_count !== 0):
        print_everything_filenamesearch(true);
    else:
        // searchitem_o マイグレーション
        if (!isset($config_ini['searchitem_o'][5])) {
            $config_ini['searchitem_o'][5] = 0;
            if (!in_array('searchmessage', $config_ini['searchitem'])) {
                $config_ini['searchitem'][] = 'searchmessage';
            }
        }

        // 表示順の解決
        $disp_search_order = [];
        $o_srt = $config_ini['searchitem_o'];
        asort($o_srt);
        foreach ($o_srt as $value) {
            foreach ($config_ini['searchitem_o'] as $k => $v) {
                if ($value == $v) {
                    $disp_search_order[] = $k;
                }
            }
        }

        $first = true;
        foreach ($disp_search_order as $v) {
            switch ($v) {
                case 0:
                    if (checkbox_check($config_ini['searchitem'], "listerDB_file")) {
                        // キーワード検索 (listerDB) はそのまま include
                        _section_open('sec-listerdb-file', 'キーワード検索', $first);
                        print_listerdb_fileonly();
                        _section_close();
                        $first = false;
                    }
                    break;
                case 1:
                    if (checkbox_check($config_ini['searchitem'], "listerDB")) {
                        _section_open('sec-listerdb', '作品名インデックス検索', $first);
                        print_listerdb_search();
                        _section_close();
                        $first = false;
                    }
                    break;
                case 2:
                    if (checkbox_check($config_ini['searchitem'], "filesearch_e")) {
                        print_everything_filenamesearch($first);
                        $first = false;
                    }
                    break;
                case 3:
                    if (checkbox_check($config_ini['searchitem'], "anisoninfo_e")) {
                        print_everything_anisoninfosearch($first);
                        $first = false;
                    }
                    break;
                case 4:
                    if (checkbox_check($config_ini['searchitem'], "bandit_e")) {
                        print_everything_banditsearch($first);
                        $first = false;
                    }
                    break;
                case 5:
                    if (checkbox_check($config_ini['searchitem'], "searchmessage")) {
                        if (!empty($config_ini["noticeof_searchpage"])) {
                            print '<div class="notice-box">';
                            print str_replace('#yukarihost#', $_SERVER["HTTP_HOST"], urldecode($config_ini["noticeof_searchpage"]));
                            print '</div>';
                        }
                    }
                    break;
            }
        }
    endif;
endif;
?>

</div><!-- /container -->

</body>
</html>
