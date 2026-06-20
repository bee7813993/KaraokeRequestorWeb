<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'search_listerdb_commonfunc_bs5.php';
require_once 'function_search_listerdb.php';
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

// ページネーション
$displayfrom = array_key_exists("start", $_REQUEST) ? max(0, (int)$_REQUEST["start"]) : 0;
$displaynum  = 20;

// ソート（Cookie保存: YukariEverythingOrderby / YukariEverythingScending）
$_valid_ev_ob = ['date_modified', 'size', 'name', 'path'];
$select_orderby  = 'date_modified';
$select_scending = 'desc';
if (array_key_exists("orderby", $_REQUEST) && in_array($_REQUEST["orderby"], $_valid_ev_ob)) {
    $select_orderby = $_REQUEST["orderby"];
    setcookie("YukariEverythingOrderby", $select_orderby);
} elseif (isset($_COOKIE['YukariEverythingOrderby']) && in_array($_COOKIE['YukariEverythingOrderby'], $_valid_ev_ob)) {
    $select_orderby = $_COOKIE['YukariEverythingOrderby'];
}
if (array_key_exists("scending", $_REQUEST) && in_array(strtolower($_REQUEST["scending"]), ['asc', 'desc'])) {
    $select_scending = strtolower($_REQUEST["scending"]);
    setcookie("YukariEverythingScending", $select_scending);
} elseif (isset($_COOKIE['YukariEverythingScending']) && in_array(strtolower($_COOKIE['YukariEverythingScending']), ['asc', 'desc'])) {
    $select_scending = strtolower($_COOKIE['YukariEverythingScending']);
}
$everything_order_query = 'sort=' . $select_orderby . '&ascending=' . ($select_scending === 'asc' ? '1' : '0');

// おすすめ順（優先度）Cookie: YukariEverythingRecommendation
$recommendation = 'on';
if (array_key_exists("recommendation", $_REQUEST) && in_array($_REQUEST["recommendation"], ['on', 'off'])) {
    $recommendation = $_REQUEST["recommendation"];
    setcookie("YukariEverythingRecommendation", $recommendation);
} elseif (isset($_COOKIE['YukariEverythingRecommendation']) && in_array($_COOKIE['YukariEverythingRecommendation'], ['on', 'off'])) {
    $recommendation = $_COOKIE['YukariEverythingRecommendation'];
}

// ListerDB パス（config から取得）
$everything_lister_dbpath = '';
if (array_key_exists("listerDBPATH", $config_ini)) {
    $everything_lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

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

// 検索実行（ページネーション対応）
$everything_results = null;
$result_count = false;
if (!empty($word)) {
    searchlocalfilename_part($word, $everything_results, $displayfrom, $displaynum, $everything_order_query, null, $recommendation !== 'off');
    $result_count = isset($everything_results['totalResults']) ? (int)$everything_results['totalResults'] : 0;
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

function _make_preview_modal_ev_bs5($filepath, $modalid) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $ftmap = ['mp4' => 'video/mp4', 'flv' => 'video/x-flv'];
    if (!isset($ftmap[$ext])) return null;
    $filetype = $ftmap[$ext];
    $furl    = 'preview_video_stream.php?path=' . urlencode($filepath);
    $sources = '<source src="' . htmlspecialchars($furl, ENT_QUOTES, 'UTF-8') . '" type="' . $filetype . '">';
    $btn = '<a href="#" data-bs-toggle="modal" data-bs-target="#' . $modalid . '" class="btn-secondary-themed" style="font-size:0.8rem;padding:4px 10px;">プレビュー</a>';
    $js  = '<script>document.addEventListener("DOMContentLoaded",function(){'
         . 'var el=document.getElementById("' . $modalid . '");'
         . 'if(!el)return;'
         . 'var vid=document.getElementById("preview_video_' . $modalid . 'a");'
         . 'el.addEventListener("hidden.bs.modal",function(){'
         .   'if(vid){vid.pause();vid.currentTime=0;}'
         . '});'
         . '});</script>';
    $modal = '<div class="modal fade" id="' . $modalid . '" tabindex="-1">'
           . '<div class="modal-dialog modal-lg"><div class="modal-content">'
           . '<div class="modal-header"><h5 class="modal-title">動画プレビュー</h5>'
           . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
           . '<div class="modal-body p-0"><video id="preview_video_' . $modalid . 'a" controls muted playsinline preload="none" style="width:100%;max-height:70vh;display:block;">' . $sources . '</video></div>'
           . '<div class="modal-footer"><button type="button" class="btn-secondary-themed" data-bs-dismiss="modal">閉じる</button></div>'
           . '</div></div></div>';
    return $btn . $js . $modal;
}

function render_everything_results_bs5($results, $selectid, $lister_dbpath) {
    $listerpreviewportenable = !check_access_from_online() || configbool('online_preview', false);
    $k = 0;
    foreach ($results as $v) {
        if ($v['size'] <= 1) continue;
        $fullpath = $v['path'] . '\\' . $v['name'];
        $fname    = $v['name'];

        // ListerDB 照合
        $linfo = null;
        if (!empty($lister_dbpath)) {
            $linfo = listerdb_lookup_songinfo($fullpath, $lister_dbpath);
        }

        $display_name = (!empty($linfo['song_name'])) ? $linfo['song_name'] : makesongnamefromfilename($fname);

        $req_href = 'request_confirm_bs5.php?filename=' . urlencode($fname) . '&fullpath=' . urlencode($fullpath);
        if (!empty($selectid)) $req_href .= '&selectid=' . urlencode($selectid);

        print '<div class="file-item">';
        print '<a href="' . htmlspecialchars($req_href, ENT_QUOTES, 'UTF-8') . '" class="btn-request flex-shrink-0">リクエスト</a>';
        print '<div class="flex-grow-1" style="min-width:0;">';
        print '<div class="fw-semibold text-break mb-1">' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '</div>';

        if ($linfo) {
            print '<div class="d-flex flex-wrap gap-2 mb-1" style="font-size:0.82rem;">';
            if (!empty($linfo['lister_artist'])) {
                foreach (explode(',', $linfo['lister_artist']) as $a) {
                    $a = trim($a);
                    $sid_q = !empty($selectid) ? '&selectid=' . urlencode($selectid) : '';
                    print '<a href="search_listerdb_filelist_bs5.php?artist=' . urlencode($a) . '&match=part' . $sid_q . '"'
                        . ' class="text-muted text-decoration-none">' . htmlspecialchars($a, ENT_QUOTES, 'UTF-8') . '</a>';
                }
            }
            if (!empty($linfo['lister_work'])) {
                $work_disp = htmlspecialchars($linfo['lister_work'], ENT_QUOTES, 'UTF-8');
                if (!empty($linfo['lister_op_ed'])) $work_disp .= '&nbsp;' . htmlspecialchars($linfo['lister_op_ed'], ENT_QUOTES, 'UTF-8');
                $sid_q = !empty($selectid) ? '&selectid=' . urlencode($selectid) : '';
                print '<a href="search_listerdb_songlist_bs5.php?program_name=' . urlencode($linfo['lister_work']) . $sid_q . '"'
                    . ' class="text-muted text-decoration-none">' . $work_disp . '</a>';
            }
            if (!empty($linfo['lister_comment'])) {
                print '<span class="text-secondary">【' . htmlspecialchars($linfo['lister_comment'], ENT_QUOTES, 'UTF-8') . '】</span>';
            }
            print '</div>';
        }

        print '<div class="d-flex flex-wrap align-items-center gap-2" style="font-size:0.78rem;color:var(--color-text-muted);">';
        print '<span>' . htmlspecialchars(formatBytes($v['size']), ENT_QUOTES, 'UTF-8') . '</span>';
        print mypage_action_links($fullpath, $display_name);
        print '</div>';
        print '<div class="text-muted mt-1 filename-toggle" style="font-size:0.7rem;word-break:break-all;cursor:pointer;" title="タップでフルパス表示"'
            . ' data-filename="' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-fullpath="' . htmlspecialchars($fullpath, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '</div>';
        print '</div>';

        if ($listerpreviewportenable) {
            $pm = _make_preview_modal_ev_bs5($fullpath, 'pm_ev_' . $k);
            if ($pm) print '<div class="flex-shrink-0 mt-1">' . $pm . '</div>';
        }
        print '</div>';
        $k++;
    }
}

function print_listerdb_search() {
    global $config_ini;
    $includepage = 1;
    if (array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = $config_ini['listerDBPATH'];
    }
    require 'search_listerdb_program_index_bs5.php';
}

function print_listerdb_fileonly() {
    global $config_ini, $selectid;
    $lister_dbpath = '';
    if (array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = urldecode($config_ini['listerDBPATH']);
    }
    $sid_field = !empty($selectid)
        ? '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />'
        : '';
    print '<div class="search-hero">';
    print '  <p class="form-label-sm mb-2">検索ワード <small>（ふりがな・作品名・曲名・歌手名・ファイル名の一部）</small></p>';
    print '  <form action="search_listerdb_filelist.php" method="GET" id="anyword-form">';
    print '    <div class="search-history-wrap">';
    print '      <div class="search-input-wrap">';
    print '        <input type="text" name="anyword" id="anyword" class="form-control-themed"';
    print '               placeholder="作品名、曲名、歌手名、ファイル名の一部" autocomplete="off" />';
    print '        <button type="submit" class="btn-search-submit">検索</button>';
    print '      </div>';
    print '      <div id="search-history-dropdown" hidden></div>';
    print '    </div>';
    print      $sid_field;
    print '  </form>';
    print '</div>';
}

function print_listerdb_detailsearch() {
    global $config_ini, $selectid;
    $lister_dbpath = '';
    if (array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = urldecode($config_ini['listerDBPATH']);
    }
    $sid_field = !empty($selectid)
        ? '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />'
        : '';
    $fields = [
        ['name' => 'song_name',         'label' => '曲名'],
        ['name' => 'program_name',      'label' => '作品名'],
        ['name' => 'artist',            'label' => '歌手名'],
        ['name' => 'maker_name',        'label' => '制作会社'],
        ['name' => 'tie_up_group_name', 'label' => 'シリーズ名'],
        ['name' => 'worker',            'label' => '動画製作者'],
    ];

    print '<form action="search_listerdb_songlist.php" method="GET">';
    print  $sid_field;
    print '<div class="row g-3 mb-3">';
    foreach ($fields as $f) {
        print '<div class="col-md-4">';
        print '  <label class="form-label-sm" for="' . $f['name'] . '">' . $f['label'] . '</label>';
        print '  <input type="text" name="' . $f['name'] . '" id="' . $f['name'] . '" class="form-control-themed" value="">';
        print '</div>';
    }
    print '</div>';
    print '<div class="row g-3 mb-3">';
    print '  <div class="col-md-4">';
    print '    <label class="form-label-sm" for="datestart">更新日（開始）</label>';
    print '    <input type="date" name="datestart" id="datestart" class="form-control-themed">';
    print '  </div>';
    print '  <div class="col-md-4">';
    print '    <label class="form-label-sm" for="dateend">更新日（終了）</label>';
    print '    <input type="date" name="dateend" id="dateend" class="form-control-themed">';
    print '  </div>';
    print '</div>';
    print '<div class="d-flex gap-3 mb-3">';
    print '  <label class="d-flex align-items-center gap-1" style="cursor:pointer;">';
    print '    <input type="radio" name="match" value="part" checked> 部分一致';
    print '  </label>';
    print '  <label class="d-flex align-items-center gap-1" style="cursor:pointer;">';
    print '    <input type="radio" name="match" value="full"> 完全一致';
    print '  </label>';
    print '</div>';
    print '<button type="submit" class="btn-secondary-themed">検索</button>';
    print '</form>';
}

function print_everything_filenamesearch($first = false) {
    global $config_ini, $word, $selectid, $result_count, $connectinternet;
    global $displayfrom, $displaynum, $select_orderby, $select_scending, $everything_results, $everything_lister_dbpath;

    _section_open('sec-filesearch', 'ファイル名検索（Everything）', $first);

    print '<form action="search_bs5.php" method="GET">';
    if (is_numeric($selectid) && $selectid !== '') {
        print '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />';
    }

    // ソート選択
    $sort_options = [
        ['ob' => 'date_modified', 'sc' => 'desc', 'label' => '更新日 新→旧'],
        ['ob' => 'date_modified', 'sc' => 'asc',  'label' => '更新日 旧→新'],
        ['ob' => 'size',          'sc' => 'desc', 'label' => 'サイズ大→小'],
        ['ob' => 'size',          'sc' => 'asc',  'label' => 'サイズ小→大'],
        ['ob' => 'name',          'sc' => 'asc',  'label' => 'ファイル名 A→Z'],
        ['ob' => 'path',          'sc' => 'asc',  'label' => 'パス A→Z'],
    ];
    print '<div class="d-flex flex-wrap gap-2 mb-2 align-items-center">';
    print '<label class="form-label-sm mb-0" for="_ev_sort">並び順:</label>';
    print '<select name="_ev_sort_dummy" id="_ev_sort" class="form-control-themed w-auto"'
        . ' onchange="var p=this.value.split(\'|\');'
        . 'document.getElementById(\'_ev_ob\').value=p[0];'
        . 'document.getElementById(\'_ev_sc\').value=p[1];'
        . 'this.form.submit();">';
    foreach ($sort_options as $opt) {
        $key = $opt['ob'] . '|' . $opt['sc'];
        $selected = ($select_orderby === $opt['ob'] && $select_scending === $opt['sc']) ? ' selected' : '';
        print '<option value="' . $key . '"' . $selected . '>' . htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    print '</select>';
    print '<input type="hidden" id="_ev_ob" name="orderby" value="' . htmlspecialchars($select_orderby, ENT_QUOTES, 'UTF-8') . '">';
    print '<input type="hidden" id="_ev_sc" name="scending" value="' . htmlspecialchars($select_scending, ENT_QUOTES, 'UTF-8') . '">';
    print '<label class="form-label-sm mb-0 ms-2" for="_ev_reco">おすすめ順</label>';
    print '<select name="recommendation" id="_ev_reco" class="form-control-themed w-auto" onchange="this.form.submit();">';
    print '<option value="on"'  . ($recommendation === 'on'  ? ' selected' : '') . '>有効</option>';
    print '<option value="off"' . ($recommendation === 'off' ? ' selected' : '') . '>無効</option>';
    print '</select>';
    print '</div>';

    print '<div class="search-hero search-hero--bare">';
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

    if (!empty($word) && $result_count !== false) {
        print '<div class="search-result-count mt-3"><strong>' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '</strong> の検索結果: '
            . '<strong>' . (int)$result_count . '</strong> 件</div>';
        if ($result_count > 0 && !empty($everything_results['results'])) {
            render_everything_results_bs5($everything_results['results'], $selectid, $everything_lister_dbpath);
            $pg_req = ['searchword' => $word, 'orderby' => $select_orderby, 'scending' => $select_scending, 'recommendation' => $recommendation];
            if (!empty($selectid)) $pg_req['selectid'] = $selectid;
            build_pagination_bs5($displayfrom, $displaynum, $result_count, $pg_req, 'search_bs5.php');
        }
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
    print '<select name="year" id="anison-year" class="form-control-themed w-auto">';
    print '<option value="">年指定なし</option>';
    $year = date('Y');
    for ($i = $year + 1; $i >= 1953; $i--) {
        print '<option value="' . $i . '">' . $i . '</option>';
    }
    print '</select>';
    print '</div>';

    print '<div>';
    print '<label class="form-label-sm" for="anison-genre">ジャンル指定</label>';
    print '<select name="genre" id="anison-genre" class="form-control-themed w-auto">';
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
        print '<label class="form-label-sm mb-0 search-label-nowrap">' . $s['label'] . '</label>';
        print '<form action="searchbandit.php" method="GET" class="d-flex gap-2 flex-grow-1">';
        print $sid_field;
        print '<input type="hidden" name="column" value="' . $s['col'] . '">';
        print '<input type="text" name="searchword" class="form-control-themed flex-grow-1">';
        print '<button type="submit" class="btn-secondary-themed text-nowrap">検索</button>';
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
</head>
<body>

<?php shownavigatioinbar_bs5('searchreserve.php'); ?>

<div class="container py-3">

<?php
// --- 差し替えバナー ---
if ($is_swap):
    $cancel_href = 'requestlist_top.php';
    $song_disp   = !empty($swap_song) ? htmlspecialchars($swap_song, ENT_QUOTES, 'UTF-8') : '（ID: ' . (int)$selectid . '）';
?>
<div class="swap-banner" role="alert">
  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" class="flex-shrink-0"><path d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 0 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/></svg>
  <span>曲差し替え中: <?php echo $song_disp; ?></span>
  <a href="<?php echo htmlspecialchars($cancel_href, ENT_QUOTES, 'UTF-8'); ?>" class="swap-cancel-btn btn-secondary-themed">キャンセル</a>
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
                        // アコーディオンなしで常時表示
                        print_listerdb_fileonly();
                    }
                    break;
                case 1:
                    if (checkbox_check($config_ini['searchitem'], "listerDB")) {
                        // 作品名インデックス検索は常時開いた状態にする
                        _section_open('sec-listerdb', '作品名インデックス検索', true);
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
                case 6:
                    if (checkbox_check($config_ini['searchitem'], "listerDB_detail")) {
                        _section_open('sec-listerdb-detail', 'キーワード詳細検索', $first);
                        print_listerdb_detailsearch();
                        _section_close();
                        $first = false;
                    }
                    break;
            }
        }
    endif;
endif;
?>

</div><!-- /container -->

<script>
(function () {
  var STORAGE_KEY = "krw_search_history";
  var MAX_HISTORY = 10;

  function getHistory() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]"); }
    catch (e) { return []; }
  }
  function saveHistory(hist) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(hist));
  }
  function addToHistory(word) {
    word = word.trim();
    if (!word) return;
    var hist = getHistory().filter(function (h) { return h !== word; });
    hist.unshift(word);
    saveHistory(hist.slice(0, MAX_HISTORY));
  }
  function removeFromHistory(word) {
    saveHistory(getHistory().filter(function (h) { return h !== word; }));
  }
  function escHtml(s) {
    return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
  }

  function renderDropdown() {
    var dropdown = document.getElementById("search-history-dropdown");
    if (!dropdown) return;
    var hist = getHistory();
    // 入力中の文字で履歴を部分一致フィルタ（大文字小文字を区別しない）
    var input = document.getElementById("anyword");
    var filter = input ? input.value.trim().toLowerCase() : "";
    if (filter) {
      hist = hist.filter(function (h) {
        return h.toLowerCase().indexOf(filter) !== -1;
      });
    }
    if (hist.length === 0) { dropdown.hidden = true; dropdown.innerHTML = ""; return; }

    var html = "<ul class=\"search-history-list\">";
    hist.forEach(function (word) {
      var e = escHtml(word);
      html += "<li class=\"search-history-item\">"
            + "<button type=\"button\" class=\"search-history-word\" data-word=\"" + e + "\">" + e + "</button>"
            + "<button type=\"button\" class=\"search-history-del\" data-word=\"" + e + "\" aria-label=\"削除\">×</button>"
            + "</li>";
    });
    html += "</ul>";
    html += "<div class=\"search-history-footer\"><button type=\"button\" id=\"search-history-clear\">履歴をクリア</button></div>";
    dropdown.innerHTML = html;
    dropdown.hidden = false;

    dropdown.querySelectorAll(".search-history-word").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var input = document.getElementById("anyword");
        input.value = btn.dataset.word;
        dropdown.hidden = true;
        addToHistory(btn.dataset.word);
        input.form.submit();
      });
    });
    dropdown.querySelectorAll(".search-history-del").forEach(function (btn) {
      btn.addEventListener("mousedown", function (e) { e.preventDefault(); });
      btn.addEventListener("click", function () {
        removeFromHistory(btn.dataset.word);
        renderDropdown();
      });
    });
    var clearBtn = document.getElementById("search-history-clear");
    if (clearBtn) {
      clearBtn.addEventListener("mousedown", function (e) { e.preventDefault(); });
      clearBtn.addEventListener("click", function () {
        saveHistory([]);
        renderDropdown();
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var input = document.getElementById("anyword");
    var form  = document.getElementById("anyword-form");
    if (!input || !form) return;

    form.addEventListener("submit", function () {
      if (input.value.trim()) addToHistory(input.value.trim());
    });

    input.addEventListener("focus", function () {
      if (getHistory().length > 0) renderDropdown();
    });
    input.addEventListener("input", function () {
      if (getHistory().length > 0) renderDropdown();
    });
    input.addEventListener("blur", function () {
      setTimeout(function () {
        var dropdown = document.getElementById("search-history-dropdown");
        if (dropdown) dropdown.hidden = true;
      }, 180);
    });
  });
})();

document.addEventListener("click", function (e) {
  var el = e.target.closest(".filename-toggle");
  if (!el) return;
  if (el.dataset.showing === "fullpath") {
    el.textContent = el.dataset.filename;
    el.dataset.showing = "filename";
  } else {
    el.textContent = el.dataset.fullpath;
    el.dataset.showing = "fullpath";
  }
});
</script>

</body>
</html>
