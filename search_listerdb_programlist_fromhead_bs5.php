<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$displayfrom = 0;
$displaynum  = 50;
$draw        = 1;
$header      = '';
$category    = '';

$lister_dbpath = 'List.sqlite3';
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}
if (array_key_exists("header",   $_REQUEST)) $header      = $_REQUEST["header"];
if (array_key_exists("category", $_REQUEST)) $category    = $_REQUEST["category"];
if (array_key_exists("start",    $_REQUEST)) $displayfrom = (int)$_REQUEST["start"];
if (array_key_exists("length",   $_REQUEST)) $displaynum  = (int)$_REQUEST["length"];
if (array_key_exists("draw",     $_REQUEST)) $draw        = $_REQUEST["draw"];

$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? '&selectid=' . rawurlencode($selectid) : '';

$url = 'http://localhost/search_listerdb_programlist_json.php?start=' . $displayfrom
    . '&length=' . $displaynum
    . '&header=' . urlencode($header)
    . '&category=' . urlencode($category);

$errmsg      = '';
$programlist = null;
$json        = @file_get_contents($url);
if (!$json) {
    $errmsg = '作品の取得に失敗しました';
} else {
    $programlist = json_decode($json, true);
    if (!$programlist) $errmsg = '作品一覧の JSON parse に失敗しました';
}

$_home_url = 'searchreserve.php' . (!empty($selectid) ? '?selectid=' . rawurlencode($selectid) : '');
$crumbs = [
    ['label' => 'ホーム',  'url' => $_home_url],
    ['label' => '作品名', 'url' => 'search_listerdb_program_index.php' . (!empty($selectid) ? '?selectid=' . rawurlencode($selectid) : '')],
];
if (!empty($header)) {
    $crumbs[] = ['label' => '「' . $header . '」で始まる作品名'];
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>作品名一覧</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu('program_name', ltrim($linkoption, '&')); ?>

<div class="container py-3">
<?php build_breadcrumbs_bs5($crumbs); ?>
<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

<h2 class="h5 mb-3">
  「<?php echo htmlspecialchars($header, ENT_QUOTES, 'UTF-8'); ?>」で始まる
  「<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>」の作品名一覧
</h2>

<div class="d-flex flex-wrap gap-2 mb-4">
<?php foreach ($programlist['data'] as $program): ?>
  <?php
  $pname   = $program['program_name'];
  $display = ($pname === 'その他') ? '未分類' : $pname;
  $cnt     = $program['COUNT(DISTINCT song_name)'];
  $linkurl = 'search_listerdb_songlist.php?program_name=' . urlencode($pname)
           . '&category=' . urlencode($category) . $linkoption;
  ?>
  <a class="reservation-tab-btn" href="<?php echo htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?>（<?php echo (int)$cnt; ?>）
  </a>
<?php endforeach; ?>
</div>

<?php
$pagi_req = ['header' => $header, 'category' => $category, 'draw' => $draw];
if (!empty($selectid)) $pagi_req['selectid'] = $selectid;
build_pagination_bs5($displayfrom, $displaynum, $programlist['recordsTotal'], $pagi_req, 'search_listerdb_programlist_fromhead.php');
?>

<?php endif; ?>
</div>
</body>
</html>
