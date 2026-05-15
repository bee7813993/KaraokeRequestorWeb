<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$displayfrom = 0;
$displaynum  = 50;
$draw        = 1;
$myrequestarray = [];

$lister_dbpath = 'List.sqlite3';
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

$header    = array_key_exists("header",    $_REQUEST) ? $_REQUEST["header"]    : '';
$category  = array_key_exists("category",  $_REQUEST) ? $_REQUEST["category"]  : '';
if (!empty($header))   $myrequestarray["header"]   = $header;
if (!empty($category)) $myrequestarray["category"] = $category;

$valid_searchcolumns = ['maker_name', 'tie_up_group_name', 'program_name', 'song_artist', 'song_name',
                        'maker_ruby', 'found_artist_ruby', 'song_ruby', 'tie_up_group_ruby'];
$searchcolumn = '';
if (array_key_exists("searchcolumn", $_REQUEST) && in_array($_REQUEST["searchcolumn"], $valid_searchcolumns)) {
    $searchcolumn = $_REQUEST["searchcolumn"];
    $myrequestarray["searchcolumn"] = $searchcolumn;
}

if (array_key_exists("start",  $_REQUEST)) { $displayfrom = (int)$_REQUEST["start"];  $myrequestarray["start"]  = $displayfrom; }
if (array_key_exists("length", $_REQUEST)) { $displaynum  = (int)$_REQUEST["length"]; $myrequestarray["length"] = $displaynum; }
if (array_key_exists("draw",   $_REQUEST)) { $draw = $_REQUEST["draw"];               $myrequestarray["draw"]   = $draw; }

$selectid = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
if (!empty($selectid)) $myrequestarray["selectid"] = $selectid;

$searchitem = array_key_exists("searchitem", $_REQUEST) ? $_REQUEST["searchitem"] : '';
if (!empty($searchitem)) $myrequestarray["searchitem"] = $searchitem;

$maker_name = array_key_exists("maker_name", $_REQUEST) ? $_REQUEST["maker_name"] : '';
if (!empty($maker_name)) $myrequestarray["maker_name"] = $maker_name;

$tie_up_group_name = '';
if (array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
    $myrequestarray["tie_up_group_name"] = $tie_up_group_name;
}
if (array_key_exists("searchword", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["searchword"];
    $myrequestarray["searchword"] = $tie_up_group_name;
}

$rubycolumn = '';
if (array_key_exists("rubycolumn", $_REQUEST)) {
    $rubycolumn = $_REQUEST["rubycolumn"];
    $myrequestarray["rubycolumn"] = $rubycolumn;
}

$nextsonglistflg = !($searchcolumn === 'maker_name' || $searchcolumn === 'tie_up_group_name');

$getqueries = ['start' => $displayfrom, 'length' => $displaynum, 'column' => $searchcolumn];
if (!empty($category))          $getqueries['category']          = $category;
if (!empty($header))            $getqueries['header']            = $header;
if (!empty($rubycolumn))        $getqueries['headercolumn']      = $rubycolumn;
if (!empty($maker_name))        $getqueries['maker_name']        = $maker_name;
if (!empty($tie_up_group_name)) $getqueries['tie_up_group_name'] = $tie_up_group_name;

$url = 'http://localhost/search_listerdb_column_json.php?' . buildgetquery($getqueries);

$linkoption     = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';
$linkoptionamp  = !empty($selectid) ? '&selectid=' . rawurlencode($selectid) : '';

$errmsg     = '';
$columnlist = null;
$json = @file_get_contents($url);
if (!$json) {
    $errmsg = '項目リストの取得に失敗しました';
} else {
    $columnlist = json_decode($json, true);
    if (!$columnlist) $errmsg = '項目リストの JSON parse に失敗しました';
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title><?php echo htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8'); ?>一覧</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu($searchcolumn, $linkoption); ?>

<div class="container py-3">
<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

<?php
$searchtitle = '';
if (!empty($header))           $searchtitle .= '「' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '」で始まる';
if (!empty($tie_up_group_name)) $searchtitle .= '「' . htmlspecialchars($tie_up_group_name, ENT_QUOTES, 'UTF-8') . '」の作品';
if (!empty($maker_name))       $searchtitle .= '「' . htmlspecialchars($maker_name, ENT_QUOTES, 'UTF-8') . '」の作品';
$searchtitle .= !empty($searchitem) ? '「' . htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8') . '」一覧' : '一覧';
?>
<h2 class="h5 mb-3"><?php echo $searchtitle; ?></h2>

<div class="d-flex flex-wrap gap-2 mb-4">
<?php foreach ($columnlist['data'] as $column): ?>
  <?php
  $val = $column[$searchcolumn];
  $cnt = (int)$column['COUNT(DISTINCT song_name)'];
  if ($nextsonglistflg) {
      $linkurl = 'search_listerdb_songlist.php?' . $searchcolumn . '=' . urlencode($val) . '&category=' . urlencode($category);
  } else {
      $linkurl = 'search_listerdb_column_list.php?searchcolumn=program_name&' . $searchcolumn . '=' . urlencode($val) . '&category=' . urlencode($category);
  }
  if (!empty($linkoption)) $linkurl .= '&' . $linkoption;
  ?>
  <a class="reservation-tab-btn" href="<?php echo htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>（<?php echo $cnt; ?>）
  </a>
<?php endforeach; ?>
</div>

<!-- ページネーション -->
<div class="d-flex gap-3">
  <?php if ($displayfrom > 0): ?>
    <?php
    $prev = max(0, $displayfrom - $displaynum);
    $myrequestarray['start'] = $prev; $myrequestarray['length'] = $displaynum;
    ?>
    <a href="search_listerdb_column_list.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed">← 前の<?php echo $displaynum; ?>件</a>
  <?php endif; ?>
  <?php if ($columnlist['recordsTotal'] > ($displayfrom + $displaynum)): ?>
    <?php $myrequestarray['start'] = $displayfrom + $displaynum; $myrequestarray['length'] = $displaynum; ?>
    <a href="search_listerdb_column_list.php?<?php echo buildgetquery($myrequestarray); ?>" class="btn-secondary-themed ms-auto">次の<?php echo $displaynum; ?>件 →</a>
  <?php endif; ?>
</div>

<?php endif; ?>
</div>
</body>
</html>
