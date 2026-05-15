<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$lister_dbpath = 'list\List.sqlite3';
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

$displayfrom = 0;
$displaynum  = 5000;
if (array_key_exists("start",  $_REQUEST)) $displayfrom = (int)$_REQUEST["start"];
if (array_key_exists("length", $_REQUEST)) $displaynum  = (int)$_REQUEST["length"];

$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';

$target = array_key_exists("target", $_REQUEST) ? $_REQUEST["target"] : '';

switch ($target) {
    case 'maker_name':
        $column     = 'substr(maker_ruby, 1, 1)';
        $columnname = 'maker_ruby';
        $searchitem = '制作会社名';
        break;
    case 'song_artist':
        $column     = 'substr(found_artist_ruby, 1, 1)';
        $columnname = 'found_artist_ruby';
        $searchitem = '歌手名';
        break;
    case 'song_name':
        $column     = 'substr(song_ruby, 1, 1)';
        $columnname = 'song_ruby';
        $searchitem = '曲名';
        break;
    case 'tie_up_group_name':
        $column     = 'substr(tie_up_group_ruby, 1, 1)';
        $columnname = 'tie_up_group_ruby';
        $searchitem = 'シリーズ';
        break;
    default:
        $column     = 'substr(maker_ruby, 1, 1)';
        $columnname = 'maker_ruby';
        $searchitem = '制作会社名';
        break;
}

$alpha_list = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
$num_list   = ['1','2','3','4','5','6','7','8','9','0'];
$kana_rows  = [
    ['あ','い','う','え','お'],
    ['か','き','く','け','こ'],
    ['さ','し','す','せ','そ'],
    ['た','ち','つ','て','と'],
    ['な','に','ぬ','ね','の'],
    ['は','ひ','ふ','へ','ほ'],
    ['ま','み','む','め','も'],
    ['や','ゐ','ゆ','ゑ','よ'],
    ['ら','り','る','れ','ろ'],
    ['わ','を','ん'],
];

function checkandbuild_column_headerlink($oneheader, $columnmany, $target, $columnname_ruby, $linkoption, $searchitem)
{
    $headerkey = 'substr(' . $columnname_ruby . ', 1, 1)';
    foreach ($columnmany['data'] as $value) {
        $kataheader = mb_convert_kana($oneheader, 'C');
        if ($oneheader === $value[$headerkey] || $kataheader === $value[$headerkey]) {
            $params = 'start=0&length=50'
                . '&header=' . urlencode($value[$headerkey])
                . '&searchcolumn=' . urlencode($target)
                . '&searchitem=' . urlencode($searchitem);
            if (!empty($linkoption)) $params .= '&' . $linkoption;
            return '<a class="index-btn has-data" href="search_listerdb_column_list.php?' . htmlspecialchars($params, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($oneheader, ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
    return '<span class="index-btn no-data">' . htmlspecialchars($oneheader, ENT_QUOTES, 'UTF-8') . '</span>';
}

// ヘッダリスト取得
$errmsg    = '';
$columnmany = null;
$geturl    = 'http://localhost/search_listerdb_column_json.php?start=' . $displayfrom . '&length=' . $displaynum . '&column=' . urlencode($column);
$json      = @file_get_contents($geturl);
if (!$json) {
    $errmsg = '項目リストの取得に失敗しました';
} else {
    $columnmany = json_decode($json, true);
    if (!$columnmany) $errmsg = '項目リストの JSON parse に失敗しました';
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title><?php echo htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8'); ?>検索</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu($target, $linkoption); ?>

<div class="container py-3">
<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

<!-- キーワード検索フォーム -->
<div class="search-hero mb-4">
  <p class="form-label-sm mb-2"><?php echo htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8'); ?>キーワード検索</p>
  <?php
  $form_action = 'search_listerdb_songlist.php';
  $input_name  = 'artist';
  if ($target === 'maker_name')       { $form_action = 'search_listerdb_songlist.php'; $input_name = 'maker_name'; }
  elseif ($target === 'song_name')    { $form_action = 'search_listerdb_songlist.php'; $input_name = 'song_name'; }
  elseif ($target === 'tie_up_group_name') { $form_action = 'search_listerdb_column_list.php'; $input_name = 'tie_up_group_name'; }
  ?>
  <form action="<?php echo $form_action; ?>" method="GET">
    <?php if ($target === 'tie_up_group_name'): ?>
      <input type="hidden" name="searchcolumn" value="tie_up_group_name">
      <input type="hidden" name="searchitem" value="シリーズ">
    <?php endif; ?>
    <?php if (!empty($selectid)): ?>
      <input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <div class="search-input-wrap">
      <input type="text" name="<?php echo $input_name; ?>" class="form-control-themed"
             placeholder="<?php echo htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8'); ?>の一部を入力" autocomplete="off">
      <button type="submit" class="btn-search-submit">検索</button>
    </div>
    <?php if ($target !== 'tie_up_group_name'): ?>
    <div class="d-flex gap-3 mt-2">
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="part" checked> 部分一致</label>
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;"><input type="radio" name="match" value="full"> 完全一致</label>
    </div>
    <?php endif; ?>
  </form>
</div>

<!-- インデックス -->
<h2 class="h5 mb-3"><?php echo htmlspecialchars($searchitem, ENT_QUOTES, 'UTF-8'); ?>インデックス検索</h2>

<div class="search-section mb-3">
  <div class="search-section-body">

    <div class="index-section-label">かな</div>
    <div class="index-btn-rows">
      <?php foreach ($kana_rows as $row): ?>
      <div class="index-btn-row">
        <?php foreach ($row as $c): ?>
          <?php echo checkandbuild_column_headerlink($c, $columnmany, $target, $columnname, $linkoption, $searchitem); ?>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (headerlistcheck_column($alpha_list, $columnmany['data'], $column) != 0): ?>
    <div class="index-section-label">アルファベット</div>
    <div class="index-btn-grid">
      <?php foreach ($alpha_list as $c): ?>
        <?php echo checkandbuild_column_headerlink($c, $columnmany, $target, $columnname, $linkoption, $searchitem); ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (headerlistcheck_column($num_list, $columnmany['data'], $column) != 0): ?>
    <div class="index-section-label">数字</div>
    <div class="index-btn-grid">
      <?php foreach ($num_list as $c): ?>
        <?php echo checkandbuild_column_headerlink($c, $columnmany, $target, $columnname, $linkoption, $searchitem); ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($target === 'song_artist'): ?>
  <a href="search_listerdb_artist.php<?php echo !empty($linkoption) ? '?' . $linkoption : ''; ?>"
     class="btn-secondary-themed d-inline-block mt-2">登録数の多い順 歌手名リスト</a>
<?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
