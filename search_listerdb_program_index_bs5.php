<?php
require_once 'commonfunc.php';
if (!isset($includepage)) $includepage = '';
if (array_key_exists("includepage", $_REQUEST)) $includepage = $_REQUEST["includepage"];

if (!isset($lister_dbpath)) $lister_dbpath = "list\\List.sqlite3";
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}
$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';

require_once 'search_listerdb_commonfunc_bs5.php';

$alpha_list = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
$num_list   = ['1','2','3','4','5','6','7','8','9','0'];
$kana_list  = ['あ','い','う','え','お','か','き','く','け','こ','さ','し','す','せ','そ','た','ち','つ','て','と',
                'な','に','ぬ','ね','の','は','ひ','ふ','へ','ほ','ま','み','む','め','も','や','ゐ','ゆ','ゑ','よ',
                'ら','り','る','れ','ろ','わ','を','ん'];

function checkandbuild_headerlink($oneheader, $headlist, $lister_dbpath)
{
    global $selectid;
    foreach ($headlist['data'] as $value) {
        if ($oneheader === $value["found_head"]) {
            $searchcategory = $headlist["program_category"];
            if ($headlist["program_category"] === 'ISNULL') {
                $searchcategory = 'ISNULL';
            }
            $linkurl = 'search_listerdb_programlist_fromhead.php?start=0&length=50'
                . '&category=' . urlencode($searchcategory)
                . '&header=' . urlencode($oneheader);
            if (!empty($selectid)) $linkurl .= '&selectid=' . rawurlencode($selectid);
            return '<a class="index-btn has-data" href="' . htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($oneheader, ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
    return '<span class="index-btn no-data">' . htmlspecialchars($oneheader, ENT_QUOTES, 'UTF-8') . '</span>';
}

function sortcategorylist($categorylist)
{
    $lister_config = parse_ini_file("listerdb_config.ini", true);
    if ($lister_config === false) return $categorylist;
    if (!array_key_exists("category_order", $lister_config)) return $categorylist;
    if (!array_key_exists("category_name", $lister_config["category_order"])) return $categorylist;

    $newcategorylist     = [];
    $nullcategory_exists = 0;
    $allcategory_exists  = 0;
    foreach ($lister_config["category_order"]["category_name"] as $ordercat) {
        if ($ordercat === '全部') {
            $newcategorylist[] = ['program_category' => $ordercat];
            $allcategory_exists++;
            continue;
        }
        if ($ordercat === 'その他') {
            $nullcategory_exists++;
            $ordercat = null;
        }
        $foundkey = false;
        foreach ($categorylist as $key => $value) {
            if ($value["program_category"] == $ordercat) { $foundkey = $key; break; }
        }
        if ($foundkey !== false) {
            $newcategorylist[] = ['program_category' => $ordercat];
            array_splice($categorylist, $foundkey, 1);
        }
    }
    if (count($categorylist) > 0) {
        $newcategorylist = array_merge($newcategorylist, $categorylist);
    }
    if ($nullcategory_exists == 0) {
        $foundkey = false;
        foreach ($newcategorylist as $key => $value) {
            if ($value["program_category"] == null) { $foundkey = $key; break; }
        }
        if ($foundkey !== false) array_splice($newcategorylist, $foundkey, 1);
        $newcategorylist[] = ['program_category' => null];
    }
    if ($allcategory_exists == 0) {
        $newcategorylist[] = ['program_category' => '全部'];
    }
    return $newcategorylist;
}

function any_header_in_chars($chars, $headlist)
{
    if (empty($headlist['data'])) return false;
    foreach ($headlist['data'] as $row) {
        if (isset($row['found_head']) && in_array($row['found_head'], $chars, true)) return true;
    }
    return false;
}

function print_index_grid($headlist, $lists, $lister_dbpath)
{
    foreach ($lists as $item) {
        ['label' => $label, 'chars' => $chars, 'always' => $always] = $item;
        // かなは常時表示。アルファベット・数字はデータが存在する場合のみ。
        if (!$always && !any_header_in_chars($chars, $headlist)) continue;
        print '<div class="index-section-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
        print '<div class="index-btn-grid">';
        foreach ($chars as $c) {
            print checkandbuild_headerlink($c, $headlist, $lister_dbpath);
        }
        print '</div>';
    }
    print '<div class="index-section-label">その他</div>';
    print '<div class="index-btn-grid">';
    print checkandbuild_headerlink('その他', $headlist, $lister_dbpath);
    print '</div>';
}

// --- カテゴリリスト取得 ---
$errmsg     = '';
$categorylist = [];
$geturl = 'http://localhost/search_listerdb_head_json.php?list=1';
$categorylist_json = @file_get_contents($geturl);
if (!$categorylist_json) {
    $errmsg = 'カテゴリーリストの取得に失敗';
} else {
    $categorylist = json_decode($categorylist_json, true);
    if (!$categorylist) $errmsg = 'カテゴリーリストのJSON parse 失敗';
}
$categorylist = sortcategorylist($categorylist);

if (empty($includepage)) {
    print '<!doctype html><html lang="ja"><head>';
    print_meta_header();
    print_bs5_search_head();
    print '<title>作品名インデックス検索</title>';
    print '</head><body>';
    shownavigatioinbar_bs5('searchreserve.php');
}
showuppermenu('program_name', $linkoption);
?>

<div class="container py-3">

<?php if (!empty($errmsg)): ?>
  <div class="notice-box" role="alert"><?php echo htmlspecialchars($errmsg, ENT_QUOTES, 'UTF-8'); ?></div>
<?php else: ?>

  <!-- 更新日検索 -->
  <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="form-label-sm mb-0">新しく更新された動画:</span>
    <?php foreach ([1 => '過去1か月', 2 => '過去2か月', 3 => '過去3か月'] as $mo => $lbl): ?>
      <a class="reservation-tab-btn"
         href="search_listerdb_songlist.php?datestart=<?php echo date('Y-m-d', strtotime("-{$mo} month")); ?>&<?php echo htmlspecialchars($linkoption, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo $lbl; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- 作品名テキスト検索 -->
  <div class="search-hero mb-4">
    <p class="form-label-sm mb-2">作品名キーワード検索</p>
    <form action="search_listerdb_songlist.php" method="GET">
      <?php if (!empty($lister_dbpath)): ?>
        <input type="hidden" name="lister_dbpath" value="<?php echo htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php if (!empty($selectid)): ?>
        <input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <div class="search-input-wrap">
        <input type="text" name="program_name" id="program_name" class="form-control-themed"
               placeholder="作品名の一部を入力" autocomplete="off">
        <button type="submit" class="btn-search-submit">検索</button>
      </div>
      <div class="d-flex gap-3 mt-2">
        <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
          <input type="radio" name="match" value="part" checked> 部分一致
        </label>
        <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
          <input type="radio" name="match" value="full"> 完全一致
        </label>
      </div>
    </form>
  </div>

  <!-- 作品名インデックス -->
  <h2 class="h5 mb-3">作品名インデックス検索</h2>

  <?php
  $char_groups = [
      ['label' => 'かな', 'chars' => $kana_list, 'always' => true],
      ['label' => 'アルファベット', 'chars' => $alpha_list, 'always' => false],
      ['label' => '数字', 'chars' => $num_list, 'always' => false],
  ];

  $nullcategory_exists = 0;
  $allcategory_exists  = 0;

  foreach ($categorylist as $category):
      $cur_category = $category["program_category"];
      if ($cur_category === '全部') {
          $url = 'http://localhost/search_listerdb_head_json.php';
          $allcategory_exists++;
      } elseif ($cur_category === null) {
          $nullcategory_exists++;
          $cur_category = 'その他';
          $url = 'http://localhost/search_listerdb_head_json.php?program_category=ISNULL';
      } else {
          $url = 'http://localhost/search_listerdb_head_json.php?program_category=' . urlencode($cur_category);
      }

      $headlist_json = @file_get_contents($url);
      if (!$headlist_json) continue;
      $headlist = json_decode($headlist_json, true);
      if (empty($headlist['data'])) continue;
  ?>
  <div class="search-section mb-3">
    <div class="search-section-body">
      <h3 class="h6 mb-2"><?php echo htmlspecialchars($cur_category, ENT_QUOTES, 'UTF-8'); ?></h3>
      <?php print_index_grid($headlist, $char_groups, $lister_dbpath); ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($nullcategory_exists == 0): ?>
    <?php
    $headlist_json = @file_get_contents('http://localhost/search_listerdb_head_json.php?program_category=ISNULL');
    if ($headlist_json):
        $headlist = json_decode($headlist_json, true);
        if (!empty($headlist['data'])):
    ?>
    <div class="search-section mb-3">
      <div class="search-section-body">
        <h3 class="h6 mb-2">その他</h3>
        <?php print_index_grid($headlist, $char_groups, $lister_dbpath); ?>
      </div>
    </div>
    <?php endif; endif; ?>
  <?php endif; ?>

  <?php if ($allcategory_exists == 0): ?>
    <?php
    $headlist_json = @file_get_contents('http://localhost/search_listerdb_head_json.php?' . $linkoption);
    if ($headlist_json):
        $headlist = json_decode($headlist_json, true);
        if ($headlist):
    ?>
    <div class="search-section mb-3">
      <div class="search-section-body">
        <h3 class="h6 mb-2">全部</h3>
        <?php print_index_grid($headlist, $char_groups, $lister_dbpath); ?>
      </div>
    </div>
    <?php endif; endif; ?>
  <?php endif; ?>

<?php endif; // errmsg ?>
</div>

<?php
if (empty($includepage)) {
    print '</body></html>';
}
?>
