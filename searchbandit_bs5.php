<?php
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$l_searchword = array_key_exists("searchword", $_REQUEST) ? $_REQUEST["searchword"] : '';
if (!empty($l_searchword) && $historylog == 1) {
    searchwordhistory('bandit:' . $l_searchword);
}
$l_column = array_key_exists("column", $_REQUEST) ? $_REQUEST["column"] : '2';
$selectid = array_key_exists("selectid", $_REQUEST) ? $_REQUEST["selectid"] : '';
$linkoption = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>bandit検索モード</title>
<?php print_bs5_search_head(); ?>
<link rel="stylesheet" href="css/jquery.dataTables.css">
<script src="js/jquery.dataTables.js"></script>
<script src="js/currency.js"></script>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu('', $linkoption); ?>

<div class="container py-3">

<div class="search-section mb-4">
  <div class="search-section-body">
    <h2 class="h5 mb-3">banditの隠れ家連携検索モード</h2>
    <p class="small text-muted mb-3">
      キーワードでbanditの隠れ家のサイトから曲名を検索し、その曲名でローカルにファイルがあるかを検索します。<br>
      banditさんに登録されていない曲は見つけられません。網羅されていない新しい曲や特殊文字が含まれる曲名は見つからない場合があります。
    </p>
    <div class="d-flex flex-column gap-2">
      <form action="searchbandit.php" method="GET" class="d-flex gap-2 align-items-center">
        <span class="form-label-sm mb-0" style="white-space:nowrap;">歌手名</span>
        <input type="hidden" name="column" value="2">
        <?php if (!empty($selectid)): ?><input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <input type="text" name="searchword" class="form-control-themed"
               value="<?php echo ($l_column === '2' ? htmlspecialchars($l_searchword, ENT_QUOTES, 'UTF-8') : ''); ?>">
        <button type="submit" class="btn-search-submit">検索</button>
      </form>
      <form action="searchbandit.php" method="GET" class="d-flex gap-2 align-items-center">
        <span class="form-label-sm mb-0" style="white-space:nowrap;">ゲームタイトル</span>
        <input type="hidden" name="column" value="3">
        <?php if (!empty($selectid)): ?><input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <input type="text" name="searchword" class="form-control-themed"
               value="<?php echo ($l_column === '3' ? htmlspecialchars($l_searchword, ENT_QUOTES, 'UTF-8') : ''); ?>">
        <button type="submit" class="btn-search-submit">検索</button>
      </form>
      <form action="searchbandit.php" method="GET" class="d-flex gap-2 align-items-center">
        <span class="form-label-sm mb-0" style="white-space:nowrap;">ゲームブランド</span>
        <input type="hidden" name="column" value="1">
        <?php if (!empty($selectid)): ?><input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
        <input type="text" name="searchword" class="form-control-themed"
               value="<?php echo ($l_column === '1' ? htmlspecialchars($l_searchword, ENT_QUOTES, 'UTF-8') : ''); ?>">
        <button type="submit" class="btn-search-submit">検索</button>
      </form>
    </div>
  </div>
</div>

<?php if (!empty($l_searchword)): ?>
<h2 class="h5 mb-3">検索結果</h2>
<?php
$everythinghost = $_SERVER["SERVER_NAME"];

$arr = array(
    'column'          => $l_column,
    'keyword'         => utf8_encode($l_searchword),
    'method'          => '1',
    'exclude_keyword' => '',
    'exclude_method'  => '2',
    'year'            => '',
    'year_type'       => '1',
    'option_year'     => true,
    'option_common'   => true,
);
$reqdata = json_encode($arr);
$url     = 'http://eroge.no-ip.org/search.cgi';
$header  = array(
    "Content-Type: application/json; charset=utf-8",
    "Referer: http://eroge.no-ip.org/search.html",
);
$options  = array('http' => array('method' => 'POST', 'header' => implode("\r\n", $header), 'content' => $reqdata));
$contents = file_get_contents($url, false, stream_context_create($options));
$songlist = json_decode($contents, true, 4096);

$songnum = 0;
foreach ($songlist["result"] as $value) {
    $songtitle  = replace_obscure_words($value["title"]);
    $songtitles = array($songtitle);

    $t2 = mb_convert_kana($songtitle, "A");
    if (!in_array($t2, $songtitles)) $songtitles[] = $t2;
    $t3 = mb_convert_kana($songtitle, "a");
    if (!in_array($t3, $songtitles)) $songtitles[] = $t3;

    foreach ($songtitles as $checktitle) {
        echo '<a name="song_' . $songnum . '">「' . htmlspecialchars($checktitle, ENT_QUOTES, 'UTF-8') . '」の検索結果 : </a>'
           . '&nbsp;&nbsp;<a href="#song_' . ($songnum + 1) . '">次の曲へ</a>';
        PrintLocalFileListfromkeyword($checktitle, 'sort=size&ascending=0', 'searchresult' . $songnum);
        print "<script type=\"text/javascript\">\n";
        print "$(document).ready(function(){\n";
        print "  $('#searchresult{$songnum}').dataTable(\n";
        print "  { \"bPaginate\" : false,\n";
        print "  columnDefs:[{\n";
        print "    'type': 'currency',\n";
        print "    'targets': [3] \n";
        print "  }],\n";
        print " });\n";
        print "});  </script> ";
        $songnum++;
    }
}
?>
<?php endif; ?>

</div>
</body>
</html>
