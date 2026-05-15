<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$displayfrom = 0;
$displaynum  = 50;
$draw        = 1;
$myrequestarray = [];

$lister_dbpath = 'list\List.sqlite3';
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

$header      = array_key_exists("header",            $_REQUEST) ? $_REQUEST["header"]            : '';
$category    = array_key_exists("category",           $_REQUEST) ? $_REQUEST["category"]           : '';
$program_name= array_key_exists("program_name",       $_REQUEST) ? $_REQUEST["program_name"]       : '';
$artist      = array_key_exists("artist",             $_REQUEST) ? $_REQUEST["artist"]             : '';
if (empty($artist) && array_key_exists("song_artist", $_REQUEST)) { $artist = $_REQUEST["song_artist"]; }
$worker      = array_key_exists("worker",             $_REQUEST) ? $_REQUEST["worker"]             : '';
$anyword     = array_key_exists("anyword",            $_REQUEST) ? $_REQUEST["anyword"]            : '';
$filename    = array_key_exists("filename",           $_REQUEST) ? $_REQUEST["filename"]           : '';
$datestart   = array_key_exists("datestart",          $_REQUEST) ? $_REQUEST["datestart"]          : '';
$dateend     = array_key_exists("dateend",            $_REQUEST) ? $_REQUEST["dateend"]            : '';
$maker_name  = array_key_exists("maker_name",         $_REQUEST) ? $_REQUEST["maker_name"]         : '';
$song_name   = array_key_exists("song_name",          $_REQUEST) ? $_REQUEST["song_name"]          : '';
$tie_up_group_name = array_key_exists("tie_up_group_name", $_REQUEST) ? $_REQUEST["tie_up_group_name"] : '';
$match       = array_key_exists("match",              $_REQUEST) ? $_REQUEST["match"]              : '';
if (array_key_exists("start",  $_REQUEST)) $displayfrom = (int)$_REQUEST["start"];
if (array_key_exists("length", $_REQUEST)) $displaynum  = (int)$_REQUEST["length"];
if (array_key_exists("draw",   $_REQUEST)) $draw        = $_REQUEST["draw"];

$valid_orderby = ['found_file_size', 'found_last_write_time', 'song_name', 'song_artist'];
$select_orderby  = '';
$select_scending = '';
if (array_key_exists("orderby",  $_REQUEST) && in_array($_REQUEST["orderby"], $valid_orderby)) {
    $select_orderby = $_REQUEST["orderby"];
    $myrequestarray["orderby"] = $select_orderby;
    setcookie("YukariListerDBOrderby", $select_orderby);
} elseif (isset($_COOKIE['YukariListerDBOrderby']) && in_array($_COOKIE['YukariListerDBOrderby'], $valid_orderby)) {
    $select_orderby = $_COOKIE['YukariListerDBOrderby'];
    $myrequestarray["orderby"] = $select_orderby;
}
if (array_key_exists("scending", $_REQUEST) && in_array(strtoupper($_REQUEST["scending"]), ['ASC','DESC'])) {
    $select_scending = strtoupper($_REQUEST["scending"]);
    $myrequestarray["scending"] = $select_scending;
    setcookie("YukariListerDBScending", $select_scending);
} elseif (isset($_COOKIE['YukariListerDBScending']) && in_array(strtoupper($_COOKIE['YukariListerDBScending']), ['ASC','DESC'])) {
    $select_scending = strtoupper($_COOKIE['YukariListerDBScending']);
    $myrequestarray["scending"] = $select_scending;
}
if (empty($select_orderby))  $select_orderby  = 'found_last_write_time';
if (empty($select_scending)) $select_scending = 'desc';
$select_orderby_str = (!empty($select_orderby) && !empty($select_scending))
    ? $select_orderby . ' ' . $select_scending
    : 'found_last_write_time desc';

$recommendation = 'on';
if (array_key_exists("recommendation", $_REQUEST) && in_array($_REQUEST["recommendation"], ['on','off'])) {
    $recommendation = $_REQUEST["recommendation"];
}
$myrequestarray["recommendation"] = $recommendation;

$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? '&selectid=' . rawurlencode($selectid) : '';
$linkoptionbare = ltrim($linkoption, '&');

function _fl_add($url, $q) { return empty($url) ? '?' . $q : $url . '&' . $q; }

$url = '';
$myformvalue       = '';
$myformvalue_shown = '';

$ffields = [
    ['val' => $anyword,     'name' => 'anyword',     'label' => 'キーワード'],
    ['val' => $category,    'name' => 'category',    'label' => 'カテゴリー'],
    ['val' => $program_name,'name' => 'program_name','label' => '作品名'],
    ['val' => $artist,      'name' => 'artist',      'label' => '歌手名'],
    ['val' => $worker,      'name' => 'worker',      'label' => '動画製作者'],
    ['val' => $filename,    'name' => 'filename',    'label' => 'ファイル名'],
    ['val' => $maker_name,  'name' => 'maker_name',  'label' => '制作会社'],
    ['val' => $song_name,   'name' => 'song_name',   'label' => '曲名'],
    ['val' => $tie_up_group_name, 'name' => 'tie_up_group_name', 'label' => 'シリーズ'],
    ['val' => $datestart,   'name' => 'datestart',   'label' => '更新日（開始）', 'type' => 'date'],
    ['val' => $dateend,     'name' => 'dateend',     'label' => '更新日（終了）', 'type' => 'date'],
];
foreach ($ffields as $f) {
    if (empty($f['val'])) continue;
    $url = _fl_add($url, $f['name'] . '=' . urlencode($f['val']));
    $myrequestarray[$f['name']] = $f['val'];
    $type = isset($f['type']) ? $f['type'] : 'text';
    $myformvalue       .= '<input type="hidden" name="' . $f['name'] . '" value="' . htmlspecialchars($f['val'], ENT_QUOTES, 'UTF-8') . '">';
    $myformvalue_shown .= '<div class="col-md-4"><label class="form-label-sm">' . $f['label'] . '</label>'
        . '<input type="' . $type . '" name="' . $f['name'] . '" class="form-control-themed" value="' . htmlspecialchars($f['val'], ENT_QUOTES, 'UTF-8') . '"></div>';
}
if (!empty($match)) { $url = _fl_add($url, 'match=' . urlencode($match)); }
if (!empty($select_orderby_str)) $url = _fl_add($url, 'orderby=' . urlencode($select_orderby_str));
if (!empty($url)) {
    $url = _fl_add($url, 'start=' . $displayfrom . '&length=' . $displaynum . $linkoption);
    $url = 'http://localhost/search_listerdb_filelist_json.php' . $url;
}

function isSocketListening_bs5($host, $port, $timeout = 1) {
    $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$sock) return false;
    fclose($sock);
    return true;
}
$listerpreviewportenable = (!check_access_from_online() && isSocketListening_bs5($_SERVER["SERVER_NAME"], 13582, 1));

function make_preview_modal_bs5($filepath, $modalid) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $ftmap = ['mp4' => 'video/mp4', 'flv' => 'video/x-flv'];
    if (!isset($ftmap[$ext])) return null;
    $filetype = $ftmap[$ext];
    $furl1 = 'http://' . $_SERVER["SERVER_NAME"] . ':13582/' . urlencode($filepath);
    $furl2 = 'http://' . $_SERVER["SERVER_NAME"] . ':13582/' . str_replace('\\', '/', $filepath);
    $sources = '<source src="' . htmlspecialchars($furl1, ENT_QUOTES, 'UTF-8') . '" type="' . $filetype . '"><source src="' . htmlspecialchars($furl2, ENT_QUOTES, 'UTF-8') . '" type="' . $filetype . '">';
    $btn = '<a href="#" data-bs-toggle="modal" data-bs-target="#' . $modalid . '" class="btn-secondary-themed" style="font-size:0.8rem;padding:4px 10px;">プレビュー</a>';
    $js  = '<script>document.addEventListener("DOMContentLoaded",function(){'
         . 'var el=document.getElementById("' . $modalid . '");'
         . 'if(el)el.addEventListener("hidden.bs.modal",function(){'
         . 'if(typeof videojs!=="undefined"){try{videojs("preview_video_' . $modalid . 'a").pause();}catch(e){}}'
         . '});});</script>';
    $modal = '<div class="modal fade" id="' . $modalid . '" tabindex="-1">'
           . '<div class="modal-dialog"><div class="modal-content">'
           . '<div class="modal-header"><h5 class="modal-title">動画プレビュー</h5>'
           . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
           . '<div class="modal-body"><video id="preview_video_' . $modalid . 'a" class="video-js vjs-default-skin" controls muted playsinline preload="none" data-setup="{}" style="width:100%;max-width:480px;height:auto;">' . $sources . '</video></div>'
           . '<div class="modal-footer"><button type="button" class="btn-secondary-themed" data-bs-dismiss="modal">閉じる</button></div>'
           . '</div></div></div>';
    return $btn . $js . $modal;
}

function get_first_clminfo_bs5($filelist, $clm) {
    foreach ($filelist as $fi) { if (!empty($fi[$clm])) return $fi[$clm]; }
    return '';
}

function fmt_date_bs5($jd) {
    $d = cal_from_jd((int)$jd, CAL_GREGORIAN);
    if ($d['year'] < 0) $d = cal_from_jd((int)($jd + 2400000.5), CAL_GREGORIAN);
    return $d['year'] . '/' . $d['month'] . '/' . $d['day'];
}

function create_requestconfirmlink_bs5($songinfo, $linkoption) {
    $fp = $songinfo['found_path'];
    return 'request_confirm.php?filename=' . urlencode(basename_jp($fp)) . '&fullpath=' . urlencode($fp) . $linkoption;
}

function filelistfromsong_bs5($filelist, $linkoption, $listerpreviewportenable) {
    $song_name = htmlspecialchars(($filelist[0]['song_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $artist    = get_first_clminfo_bs5($filelist, 'song_artist');
    $program   = get_first_clminfo_bs5($filelist, 'program_name');
    $op_ed     = get_first_clminfo_bs5($filelist, 'song_op_ed');
    $maker     = get_first_clminfo_bs5($filelist, 'maker_name');
    $series    = get_first_clminfo_bs5($filelist, 'tie_up_group_name');

    echo '<div class="search-section mb-3">';
    echo '<div class="search-section-body">';

    // Song header
    echo '<div class="mb-3">';
    echo '<h5 class="mb-1">' . $song_name . '</h5>';
    echo '<div class="d-flex flex-wrap gap-x-3 gap-2" style="font-size:0.85rem;">';
    if (!empty($artist)) {
        foreach (explode(',', $artist) as $v) {
            $v = trim($v);
            echo '<a href="search_listerdb_filelist.php?artist=' . urlencode($v) . $linkoption . '&match=part" class="text-decoration-none text-muted">' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
    if (!empty($program)) {
        $prog_disp = htmlspecialchars($program, ENT_QUOTES, 'UTF-8') . (!empty($op_ed) ? '&nbsp;' . htmlspecialchars($op_ed, ENT_QUOTES, 'UTF-8') : '');
        echo '<a href="search_listerdb_filelist.php?program_name=' . urlencode($program) . $linkoption . '" class="text-decoration-none text-muted">' . $prog_disp . '</a>';
    }
    if (!empty($maker))  echo '<a href="search_listerdb_column_list.php?searchcolumn=program_name&maker_name=' . urlencode($maker) . '" class="text-decoration-none text-muted">【' . htmlspecialchars($maker, ENT_QUOTES, 'UTF-8') . '】</a>';
    if (!empty($series)) echo '<a href="search_listerdb_column_list.php?searchcolumn=program_name&tie_up_group_name=' . urlencode($series) . '" class="text-decoration-none text-muted">[' . htmlspecialchars($series, ENT_QUOTES, 'UTF-8') . ']シリーズ</a>';
    echo '</div></div>';

    // File rows
    foreach ($filelist as $k => $fi) {
        $comment = preg_replace('/\,\/\/.*/', '', $fi['found_comment'] ?? '');
        $fname   = basename_jp($fi['found_path']);
        echo '<div class="d-flex align-items-start gap-3 py-2 border-top">';
        echo '<a href="' . htmlspecialchars(create_requestconfirmlink_bs5($fi, $linkoption), ENT_QUOTES, 'UTF-8') . '" class="btn-request flex-shrink-0">予約</a>';
        echo '<div class="flex-grow-1" style="min-width:0;">';
        if (!empty($comment)) echo '<div class="text-secondary mb-1" style="font-size:0.8rem;">【' . htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') . '】</div>';
        echo '<div class="fw-semibold text-break mb-1">' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="d-flex flex-wrap align-items-center gap-2" style="font-size:0.78rem;color:var(--color-text-muted);">';
        if (!empty($fi['found_track'])) {
            if ($fi['found_smart_track_on']  == 1) echo '<span class="badge bg-success">OnVocal</span>';
            if ($fi['found_smart_track_off'] == 1) echo '<span class="badge bg-secondary">OffVocal</span>';
        }
        echo '<span>' . formatBytes($fi['found_file_size']) . '</span>';
        echo '<span>' . fmt_date_bs5($fi['found_last_write_time']) . '</span>';
        if (!empty($fi['found_worker'])) echo '<a href="search_listerdb_filelist.php?worker=' . urlencode($fi['found_worker']) . $linkoption . '" class="text-decoration-none text-muted">' . htmlspecialchars($fi['found_worker'], ENT_QUOTES, 'UTF-8') . '</a>';
        echo '</div>';
        echo '<div class="text-muted mt-1" style="font-size:0.7rem;word-break:break-all;">' . htmlspecialchars($fi['found_path'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</div>';
        echo mypage_action_links($fi['found_path'], $fname);
        if ($listerpreviewportenable) {
            $pm = make_preview_modal_bs5($fi['found_path'], 'pm_' . $k);
            if ($pm) echo '<div class="flex-shrink-0">' . $pm . '</div>';
        }
        echo '</div>';
    }

    echo '</div></div>';
}

function song_name_num_bs5($filelist) {
    $cur = ''; $n = 0;
    foreach ($filelist as $fi) {
        if ($fi['song_name'] === null) continue;
        if ($cur !== $fi['song_name']) { $cur = $fi['song_name']; $n++; }
    }
    return $n;
}

function custom_sort_bs5($a, $b) {
    global $custom_sort_priorities;
    $pa = empty($a['found_worker']) ? 9999 : 999;
    $pb = empty($b['found_worker']) ? 9999 : 999;
    if (!empty($a['found_worker'])) {
        foreach ($custom_sort_priorities as $r) { if ($a['found_worker'] === $r['keyword']) { $pa = $r['priority']; break; } }
    }
    if (!empty($b['found_worker'])) {
        foreach ($custom_sort_priorities as $r) { if ($b['found_worker'] === $r['keyword']) { $pb = $r['priority']; break; } }
    }
    if ($pa !== $pb) return $pa - $pb;
    return $a['_sort_idx'] - $b['_sort_idx'];
}

function selected_check_fl($a, $b) { return $a === $b ? 'selected' : ''; }

$errmsg      = '';
$programlist = null;
$json        = !empty($url) ? @file_get_contents($url) : false;
if ($json === false) {
    $errmsg = '検索結果リストの取得に失敗しました';
} else {
    $programlist = json_decode($json, true);
    if (!$programlist) $errmsg = '検索結果の JSON parse に失敗しました';
}

$custom_sort_priorities = [];
$pf = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'search_sort_priority.json';
if (file_exists($pf)) {
    $loaded = json_decode(file_get_contents($pf), true);
    if (is_array($loaded)) $custom_sort_priorities = $loaded;
}

if ($programlist && $recommendation === 'on') {
    foreach ($programlist['data'] as $idx => &$item) { $item['_sort_idx'] = $idx; }
    unset($item);
    usort($programlist['data'], 'custom_sort_bs5');
}

mypage_action_script();
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>ファイル一覧</title>
<?php print_bs5_search_head(); ?>
<link href="js/video-js.min.css" rel="stylesheet">
<script src="js/video.min.js"></script>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu('filename', $linkoptionbare); ?>

<div class="container py-3">
<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

<?php if (!empty($program_name) && !empty($category)): ?>
  <h2 class="h5 mb-3">「<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>」「<?php echo htmlspecialchars($program_name, ENT_QUOTES, 'UTF-8'); ?>」の曲一覧</h2>
<?php elseif (!empty($artist)): ?>
  <h2 class="h5 mb-3">「<?php echo htmlspecialchars($artist, ENT_QUOTES, 'UTF-8'); ?>」の曲一覧</h2>
<?php endif; ?>

<!-- 再検索 -->
<?php if (!empty($myformvalue_shown)): ?>
<div class="search-section mb-4">
  <div class="search-section-header" data-bs-toggle="collapse" data-bs-target="#sec-fl-refilter"
       aria-expanded="false" role="button">
    再検索・絞り込み
    <span class="collapse-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg></span>
  </div>
  <div id="sec-fl-refilter" class="collapse">
    <div class="search-section-body">
      <form method="GET" action="search_listerdb_filelist.php">
        <div class="row g-3 mb-3"><?php echo $myformvalue_shown; ?></div>
        <div class="d-flex gap-3 mb-3">
          <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="part" <?php echo ($match === 'full' ? '' : 'checked'); ?>> 部分一致</label>
          <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="full" <?php echo ($match === 'full' ? 'checked' : ''); ?>> 完全一致</label>
        </div>
        <?php if (!empty($lister_dbpath)): ?><input type="hidden" name="lister_dbpath" value="<?php echo htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <?php if (!empty($selectid)):       ?><input type="hidden" name="selectid"     value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <button type="submit" class="btn-secondary-themed">再検索</button>
      </form>
      <?php
      if      (!empty($anyword))     { $_kp = 'anyword';     $_kv = $anyword; }
      elseif  (!empty($song_name))   { $_kp = 'song_name';   $_kv = $song_name; }
      elseif  (!empty($filename))    { $_kp = 'filename';    $_kv = $filename; }
      elseif  (!empty($artist))      { $_kp = 'artist';      $_kv = $artist; }
      elseif  (!empty($program_name)){ $_kp = 'program_name';$_kv = $program_name; }
      elseif  (!empty($maker_name))  { $_kp = 'maker_name';  $_kv = $maker_name; }
      else                           { $_kp = '';             $_kv = ''; }
      if (!empty($_kv)) {
          $sp = !empty($lister_dbpath) ? 'lister_dbpath=' . urlencode($lister_dbpath) : '';
          $kw_sp = 'param=' . $_kp . (!empty($sp) ? '&' . $sp : '') . (!empty($match) ? '&match=' . urlencode($match) : '');
          echo mypage_save_keyword_link($_kv, 'listerdb_filelist', $kw_sp);
      }
      ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 並び替え & おすすめ -->
<form method="GET" action="search_listerdb_filelist.php" class="d-flex flex-wrap align-items-end gap-3 mb-3">
  <div>
    <label class="form-label-sm" for="fl-reco">おすすめ順</label>
    <select class="form-control-themed" id="fl-reco" name="recommendation" style="width:auto;">
      <option value="on"  <?php echo selected_check_fl('on',  $recommendation); ?>>有効</option>
      <option value="off" <?php echo selected_check_fl('off', $recommendation); ?>>無効</option>
    </select>
  </div>
  <div>
    <label class="form-label-sm" for="fl-orderby">項目</label>
    <select class="form-control-themed" id="fl-orderby" name="orderby" style="width:auto;">
      <option value="found_file_size"       <?php echo selected_check_fl('found_file_size',      $select_orderby); ?>>ファイルサイズ</option>
      <option value="found_last_write_time" <?php echo selected_check_fl('found_last_write_time',$select_orderby); ?>>更新日</option>
      <option value="song_name"             <?php echo selected_check_fl('song_name',            $select_orderby); ?>>曲名</option>
      <option value="song_artist"           <?php echo selected_check_fl('song_artist',          $select_orderby); ?>>歌手名</option>
    </select>
  </div>
  <div>
    <label class="form-label-sm" for="fl-scending">順番</label>
    <select class="form-control-themed" id="fl-scending" name="scending" style="width:auto;">
      <option value="ASC"  <?php echo selected_check_fl('ASC',  $select_scending); ?>>昇順</option>
      <option value="DESC" <?php echo selected_check_fl('DESC', $select_scending); ?>>降順</option>
    </select>
  </div>
  <?php echo $myformvalue; ?>
  <?php if (!empty($lister_dbpath)): ?><input type="hidden" name="lister_dbpath" value="<?php echo htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <?php if (!empty($selectid)):       ?><input type="hidden" name="selectid"     value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <button type="submit" class="btn-secondary-themed">並び替え</button>
</form>

<?php if ($programlist['recordsTotal'] == 0): ?>
  <div class="notice-box">検索の結果ファイルが見つかりませんでした。</div>
<?php else: ?>

<?php
$displaylast = min($displayfrom + $displaynum, $programlist['recordsTotal']);
?>
<div class="search-result-count mb-3"><?php echo $displayfrom; ?>–<?php echo $displaylast; ?>（全<?php echo $programlist['recordsTotal']; ?>件）</div>

<?php if (song_name_num_bs5($programlist['data']) == 1): ?>
  <?php filelistfromsong_bs5($programlist['data'], $linkoption, $listerpreviewportenable); ?>
<?php else: ?>
  <?php foreach ($programlist['data'] as $k => $program): ?>
    <?php
    $display = $program['song_name'];
    if (empty($display)) $display = '未分類';
    $comment = preg_replace('/\,\/\/.*/', '', $program['found_comment'] ?? '');
    ?>
    <div class="d-flex align-items-start gap-3 py-2 border-bottom">
      <a href="<?php echo htmlspecialchars(create_requestconfirmlink_bs5($program, $linkoption), ENT_QUOTES, 'UTF-8'); ?>"
         class="btn-request flex-shrink-0">予約</a>
      <div class="flex-grow-1" style="min-width:0;">
        <div class="fw-semibold text-break"><?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if (!empty($comment)): ?>
          <div class="text-secondary" style="font-size:0.8rem;">【<?php echo htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'); ?>】</div>
        <?php endif; ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-1" style="font-size:0.78rem;color:var(--color-text-muted);">
          <?php if (!empty($program['program_name'])): ?>
            <a href="search_listerdb_songlist.php?program_name=<?php echo urlencode($program['program_name']); ?><?php echo $linkoption; ?>" class="text-muted text-decoration-none"><?php echo htmlspecialchars($program['program_name'], ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endif; ?>
          <?php if (!empty($program['song_artist'])): ?>
            <?php foreach (explode(',', $program['song_artist']) as $v): ?>
              <?php $v = trim($v); ?>
              <a href="search_listerdb_songlist.php?artist=<?php echo urlencode($v); ?><?php echo $linkoption; ?>&match=part" class="text-muted text-decoration-none"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (!empty($program['found_track'])): ?>
            <?php if ($program['found_smart_track_on']  == 1) echo '<span class="badge bg-success">OnVocal</span>'; ?>
            <?php if ($program['found_smart_track_off'] == 1) echo '<span class="badge bg-secondary">OffVocal</span>'; ?>
          <?php endif; ?>
          <span><?php echo formatBytes($program['found_file_size']); ?></span>
          <span><?php echo fmt_date_bs5($program['found_last_write_time']); ?></span>
        </div>
      </div>
      <?php echo mypage_action_links($program['found_path'], $display); ?>
      <?php if (!check_access_from_online()): ?>
        <?php $pm = make_preview_modal_bs5($program['found_path'], 'pm_m_' . $k); ?>
        <?php if ($pm): ?><div class="flex-shrink-0 mt-1"><?php echo $pm; ?></div><?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- ページネーション -->
<?php
$myrequestarray['lister_dbpath'] = $lister_dbpath;
if (!empty($selectid)) $myrequestarray['selectid'] = $selectid;
?>
<div class="d-flex gap-3 mt-4">
  <?php if ($displayfrom > 0): ?>
    <?php $prev = max(0, $displayfrom - $displaynum); $myrequestarray['start'] = $prev; $myrequestarray['length'] = $displaynum; ?>
    <a href="search_listerdb_filelist.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed">← 前の<?php echo $displaynum; ?>件</a>
  <?php endif; ?>
  <span class="ms-auto me-auto align-self-center text-muted" style="font-size:0.85rem;"><?php echo $displayfrom; ?>–<?php echo $displaylast; ?>（全<?php echo $programlist['recordsTotal']; ?>件）</span>
  <?php if ($programlist['recordsTotal'] > ($displayfrom + $displaynum)): ?>
    <?php $myrequestarray['start'] = $displayfrom + $displaynum; $myrequestarray['length'] = $displaynum; ?>
    <a href="search_listerdb_filelist.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed">次の<?php echo $displaynum; ?>件 →</a>
  <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
