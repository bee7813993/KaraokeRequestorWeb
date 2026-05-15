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
if (empty($artist) && array_key_exists("song_artist", $_REQUEST)) $artist = $_REQUEST["song_artist"];
$worker      = array_key_exists("worker",             $_REQUEST) ? $_REQUEST["worker"]             : '';
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
if (array_key_exists("orderby",   $_REQUEST) && in_array($_REQUEST["orderby"],   $valid_orderby))         $select_orderby  = $_REQUEST["orderby"];
if (array_key_exists("scending",  $_REQUEST) && in_array(strtoupper($_REQUEST["scending"]), ['ASC','DESC'])) $select_scending = strtoupper($_REQUEST["scending"]);
$select_orderby_str = (!empty($select_orderby) && !empty($select_scending))
    ? $select_orderby . ' ' . $select_scending
    : 'song_name asc, found_file_size desc';

$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? '&selectid=' . rawurlencode($selectid) : '';
$linkoptionbare = ltrim($linkoption, '&');

// Build API URL
function _sq_add($url, $q) { return empty($url) ? '?' . $q : $url . '&' . $q; }

$url = '';
$myformvalue       = '';
$myformvalue_shown = '';

$fields = [
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
foreach ($fields as $f) {
    if (empty($f['val'])) continue;
    $url = _sq_add($url, $f['name'] . '=' . urlencode($f['val']));
    $myrequestarray[$f['name']] = $f['val'];
    $type  = isset($f['type']) ? $f['type'] : 'text';
    $myformvalue       .= '<input type="hidden" name="' . $f['name'] . '" value="' . htmlspecialchars($f['val'], ENT_QUOTES, 'UTF-8') . '">';
    $myformvalue_shown .= '<div class="col-md-4"><label class="form-label-sm">' . $f['label'] . '</label>'
        . '<input type="' . $type . '" name="' . $f['name'] . '" class="form-control-themed" value="' . htmlspecialchars($f['val'], ENT_QUOTES, 'UTF-8') . '"></div>';
}
if (!empty($match)) { $url = _sq_add($url, 'match=' . urlencode($match)); $myrequestarray['match'] = $match; }
if (!empty($select_orderby_str)) $url = _sq_add($url, 'orderby=' . urlencode($select_orderby_str));
if (!empty($url)) {
    $url = _sq_add($url, 'start=' . $displayfrom . '&length=' . $displaynum . $linkoption);
    $url = 'http://localhost/search_listerdb_songlist_json.php' . $url;
}

function create_filelistlink_bs5($songinfo, $linkoption, $match) {
    $q = '';
    if (!empty($songinfo['song_name']))    $q .= 'song_name='    . urlencode($songinfo['song_name'])    . '&';
    if (!empty($songinfo['program_name'])) $q .= 'program_name=' . urlencode($songinfo['program_name']) . '&';
    $q .= ltrim($linkoption, '&') . '&match=full';
    return 'search_listerdb_filelist.php?' . $q;
}

function selected_check_sq($a, $b) { return $a === $b ? 'selected' : ''; }

$errmsg      = '';
$programlist = null;
$json        = @file_get_contents($url);
if (!$json) {
    $errmsg = '検索結果リストの取得に失敗しました';
} else {
    $programlist = json_decode($json, true);
    if (!$programlist) $errmsg = '検索結果の JSON parse に失敗しました';
}

mypage_action_script();
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>曲名一覧</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu('', $linkoptionbare); ?>

<div class="container py-3">
<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

<?php if (!empty($program_name) && !empty($category)): ?>
  <h2 class="h5 mb-3">「<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>」「<?php echo htmlspecialchars($program_name, ENT_QUOTES, 'UTF-8'); ?>」の曲一覧</h2>
<?php elseif (!empty($artist)): ?>
  <h2 class="h5 mb-3">「<?php echo htmlspecialchars($artist, ENT_QUOTES, 'UTF-8'); ?>」の曲一覧</h2>
<?php endif; ?>

<!-- 再検索フォーム -->
<?php if (!empty($myformvalue_shown)): ?>
<div class="search-section mb-4">
  <div class="search-section-header" data-bs-toggle="collapse" data-bs-target="#sec-refilter"
       aria-expanded="false" role="button">
    再検索・絞り込み
    <span class="collapse-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg></span>
  </div>
  <div id="sec-refilter" class="collapse">
    <div class="search-section-body">
      <form method="GET" action="search_listerdb_songlist.php">
        <div class="row g-3 mb-3"><?php echo $myformvalue_shown; ?></div>
        <div class="d-flex gap-3 mb-3">
          <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="part" <?php echo ($match === 'full' ? '' : 'checked'); ?>> 部分一致</label>
          <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="full" <?php echo ($match === 'full' ? 'checked' : ''); ?>> 完全一致</label>
        </div>
        <?php if (!empty($lister_dbpath)): ?><input type="hidden" name="lister_dbpath" value="<?php echo htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <?php if (!empty($selectid)): ?><input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <button type="submit" class="btn-secondary-themed">再検索</button>
      </form>
      <?php
      if      (!empty($song_name))    { $_kp = 'song_name';    $_kv = $song_name; }
      elseif  (!empty($artist))       { $_kp = 'artist';       $_kv = $artist; }
      elseif  (!empty($program_name)) { $_kp = 'program_name'; $_kv = $program_name; }
      elseif  (!empty($maker_name))   { $_kp = 'maker_name';   $_kv = $maker_name; }
      else                            { $_kp = ''; $_kv = ''; }
      if (!empty($_kv)) {
          $sp = !empty($lister_dbpath) ? 'lister_dbpath=' . urlencode($lister_dbpath) : '';
          $kw_sp = 'param=' . $_kp . (!empty($sp) ? '&' . $sp : '') . (!empty($match) ? '&match=' . urlencode($match) : '');
          echo mypage_save_keyword_link($_kv, 'listerdb_songlist', $kw_sp);
      }
      ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 並び替え -->
<form method="GET" action="search_listerdb_songlist.php" class="d-flex flex-wrap align-items-end gap-3 mb-3">
  <div>
    <label class="form-label-sm" for="sq-orderby">並び順</label>
    <select class="form-control-themed" id="sq-orderby" name="orderby" style="width:auto;">
      <option value="found_file_size"      <?php echo selected_check_sq('found_file_size',      $select_orderby); ?>>ファイルサイズ</option>
      <option value="found_last_write_time"<?php echo selected_check_sq('found_last_write_time',$select_orderby); ?>>更新日</option>
      <option value="song_name"            <?php echo selected_check_sq('song_name',            $select_orderby); ?>>曲名</option>
      <option value="song_artist"          <?php echo selected_check_sq('song_artist',          $select_orderby); ?>>歌手名</option>
    </select>
  </div>
  <div>
    <label class="form-label-sm" for="sq-scending">順番</label>
    <select class="form-control-themed" id="sq-scending" name="scending" style="width:auto;">
      <option value="ASC"  <?php echo selected_check_sq('ASC',  $select_scending); ?>>昇順</option>
      <option value="DESC" <?php echo selected_check_sq('DESC', $select_scending); ?>>降順</option>
    </select>
  </div>
  <?php echo $myformvalue; ?>
  <?php if (!empty($lister_dbpath)): ?><input type="hidden" name="lister_dbpath" value="<?php echo htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <?php if (!empty($selectid)):       ?><input type="hidden" name="selectid"     value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <button type="submit" class="btn-secondary-themed">並び替え</button>
</form>

<?php
if ($programlist['recordsTotal'] == 0):
?>
  <div class="notice-box">検索の結果が見つかりませんでした。</div>
<?php else: ?>

<?php
$displaylast = min($displayfrom + $displaynum, $programlist['recordsTotal']);
?>
<div class="search-result-count mb-3"><?php echo $displayfrom; ?>–<?php echo $displaylast; ?>（全<?php echo $programlist['recordsTotal']; ?>件）</div>

<div class="d-flex flex-wrap gap-2 mb-4">
<?php foreach ($programlist['data'] as $program): ?>
  <?php
  $display = $program['song_name'];
  if (empty($display)) $display = '未分類';
  $cnt     = $program['COUNT(*)'];
  $linkurl = create_filelistlink_bs5($program, $linkoption, $match);
  ?>
  <a class="reservation-tab-btn" href="<?php echo htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?>（<?php echo (int)$cnt; ?>）
  </a>
<?php endforeach; ?>
</div>

<!-- ページネーション -->
<?php
$myrequestarray['lister_dbpath'] = $lister_dbpath;
if (!empty($selectid)) $myrequestarray['selectid'] = $selectid;
?>
<div class="d-flex gap-3">
  <?php if ($displayfrom > 0): ?>
    <?php $prev = max(0, $displayfrom - $displaynum); $myrequestarray['start'] = $prev; $myrequestarray['length'] = $displaynum; ?>
    <a href="search_listerdb_songlist.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed">← 前の<?php echo $displaynum; ?>件</a>
  <?php endif; ?>
  <?php if ($programlist['recordsTotal'] > ($displayfrom + $displaynum)): ?>
    <?php $myrequestarray['start'] = $displayfrom + $displaynum; $myrequestarray['length'] = $displaynum; ?>
    <a href="search_listerdb_songlist.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed ms-auto">次の<?php echo $displaynum; ?>件 →</a>
  <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
