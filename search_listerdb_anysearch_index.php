<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc.php';

if (!isset($includepage)) $includepage = '';
if (array_key_exists("includepage", $_REQUEST)) $includepage = $_REQUEST["includepage"];

$filesearch = '';
if (array_key_exists("filesearch", $_REQUEST)) $filesearch = $_REQUEST["filesearch"];

if (!empty($filesearch)) {
    print printfilenamesearch();
    return;
}

$lister_dbpath = "list\\List.sqlite3";
if (array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}
$selectid   = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';

// アルファベット配列（未使用だが他のincludeとの互換性のため維持）
$alpha_list = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
$num_list   = ['1','2','3','4','5','6','7','8','9','0'];
$kana_list  = ['あ','い','う','え','お','か','き','く','け','こ','さ','し','す','せ','そ','た','ち','つ','て','と',
                'な','に','ぬ','ね','の','は','ひ','ふ','へ','ほ','ま','み','む','め','も','や','ゐ','ゆ','ゑ','よ',
                'ら','り','る','れ','ろ','わ','を','ん'];

if (empty($includepage)) {
    print '<!doctype html><html lang="ja"><head>';
    print_meta_header();
    print_bs5_search_head();
    print '<title>りすたーDB検索</title>';
    print '</head><body>';
    if (empty($filesearch)) {
        shownavigatioinbar('searchreserve.php');
    }
}

showuppermenu('filename', $linkoption);

function printfilenamesearch()
{
    global $lister_dbpath, $selectid;

    $sid_field = !empty($selectid)
        ? '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />'
        : '';
    $db_field = !empty($lister_dbpath)
        ? '<input type="hidden" name="lister_dbpath" value="' . htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8') . '" />'
        : '';

    print '<div class="container py-3">';
    print '<div class="search-hero">';
    print '  <p class="form-label-sm mb-2">検索ワード <small>（ふりがな・作品名・曲名・歌手名・ファイル名の一部）</small></p>';
    print '  <form action="search_listerdb_filelist.php" method="GET">';
    print '    <div class="search-input-wrap">';
    print '      <input type="text" name="anyword" id="anyword" class="form-control-themed"';
    print '             placeholder="作品名、曲名、歌手名、ファイル名の一部" autocomplete="off" />';
    print '      <button type="submit" class="btn-search-submit">検索</button>';
    print '    </div>';
    print    $db_field . $sid_field;
    print '  </form>';
    print '</div>';
    print '</div>';
}

printfilenamesearch();

// --- 詳細検索フォーム ---
$sid_field = !empty($selectid)
    ? '<input type="hidden" name="selectid" value="' . htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8') . '" />'
    : '';
$db_field = !empty($lister_dbpath)
    ? '<input type="hidden" name="lister_dbpath" value="' . htmlspecialchars($lister_dbpath, ENT_QUOTES, 'UTF-8') . '" />'
    : '';
?>
<div class="container py-2">
  <div class="search-section">
    <div class="search-section-header" data-bs-toggle="collapse" data-bs-target="#sec-detail-search"
         aria-expanded="false" aria-controls="sec-detail-search" role="button">
      詳細検索（複数項目絞り込み）
      <span class="collapse-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
          <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
        </svg>
      </span>
    </div>
    <div id="sec-detail-search" class="collapse">
      <div class="search-section-body">
        <form action="search_listerdb_songlist.php" method="GET">
          <?php echo $db_field . $sid_field; ?>
          <div class="row g-3 mb-3">
            <?php
            $fields = [
                ['name' => 'song_name',         'label' => '曲名'],
                ['name' => 'program_name',       'label' => '作品名'],
                ['name' => 'artist',             'label' => '歌手名'],
                ['name' => 'maker_name',         'label' => '制作会社'],
                ['name' => 'tie_up_group_name',  'label' => 'シリーズ名'],
                ['name' => 'worker',             'label' => '動画製作者'],
            ];
            foreach ($fields as $f):
            ?>
            <div class="col-md-4">
              <label class="form-label-sm" for="<?php echo $f['name']; ?>"><?php echo $f['label']; ?></label>
              <input type="text" name="<?php echo $f['name']; ?>" id="<?php echo $f['name']; ?>"
                     class="form-control-themed" value="">
            </div>
            <?php endforeach; ?>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-sm" for="datestart">更新日（開始）</label>
              <input type="date" name="datestart" id="datestart" class="form-control-themed">
            </div>
            <div class="col-md-4">
              <label class="form-label-sm" for="dateend">更新日（終了）</label>
              <input type="date" name="dateend" id="dateend" class="form-control-themed">
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
              <input type="radio" name="match" value="part" checked> 部分一致
            </label>
            <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
              <input type="radio" name="match" value="full"> 完全一致
            </label>
          </div>
          <button type="submit" class="btn-secondary-themed">検索</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
if (empty($includepage)) {
    print '</body></html>';
}
?>
