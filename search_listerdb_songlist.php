<html>
<head>
<?php 

$displayfrom=0;
$displaynum=50;
$draw = 1;
$allcount = 0;

$lister_dbpath = 'list\List.sqlite3';
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
}

if(array_key_exists("category", $_REQUEST)) {
    $category = $_REQUEST["category"];
}

if(array_key_exists("program_name", $_REQUEST)) {
    $program_name = $_REQUEST["program_name"];
}

$artist = "";
if(array_key_exists("artist", $_REQUEST)) {
    $artist = $_REQUEST["artist"];
}

$worker = "";
if(array_key_exists("worker", $_REQUEST)) {
    $worker = $_REQUEST["worker"];
}

$filename = "";
if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$datestart = "";
if(array_key_exists("datestart", $_REQUEST)) {
    $datestart = $_REQUEST["datestart"];
}

$dateend = "";
if(array_key_exists("dateend", $_REQUEST)) {
    $dateend = $_REQUEST["dateend"];
}


$select_orderby ="";
if (isset($_COOKIE['YukariListerDBOrderby'])) {
    $select_orderby = $_COOKIE['YukariListerDBOrderby'];
}
if(array_key_exists("orderby", $_REQUEST)) {
    $select_orderby = $_REQUEST["orderby"];
    setcookie("YukariListerDBOrderby",  $select_orderby);
}

$select_scending = 'ASC';
$select_scending ="";
if (isset($_COOKIE['YukariListerDBScending'])) {
    $select_scending = $_COOKIE['YukariListerDBScending'];
}
if(array_key_exists("scending", $_REQUEST)) {
    $select_scending = $_REQUEST["scending"];
    setcookie("YukariListerDBScending",  $select_scending);
}

$match = "";
if(array_key_exists("match", $_REQUEST)) {
    $match = $_REQUEST["match"];
}


if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = $_REQUEST["start"];
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = $_REQUEST["length"];
}

if(array_key_exists("draw", $_REQUEST)) {
    $draw = $_REQUEST["draw"];
}


/**
 * バイト数をフォーマットする
 * @param integer $bytes
 * @param integer $precision
 * @param array $units
 */
function formatBytes($bytes, $precision = 2, array $units = null)
{
    if ( abs($bytes) < 1024 )
    {
        $precision = 0;
    }

    if ( is_array($units) === false )
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    }

    if ( $bytes < 0 )
    {
        $sign = '-';
        $bytes = abs($bytes);
    }
    else
    {
        $sign = '';
    }

    $exp   = floor(log($bytes) / log(1024));
    $exp   = 2;  // MB固定
    $unit  = $units[$exp];
    $bytes = $bytes / pow(1024, floor($exp));
    $bytes = sprintf('%.'.$precision.'f', $bytes);
    return $sign.$bytes.' '.$unit;
}


function add_get_query($baseurl,$addquery){
    if( empty($baseurl) ){
        return '?'.$addquery;
    }else {
        return $baseurl.'&'.$addquery;
    }
}

// build query url
$url = "";
$myformvalue = "";
$myformvalue_shown = "";
if(!empty($category ) ) {
    $url = add_get_query($url , 'category='.urlencode($category) );
    $myformvalue = $myformvalue.'<input type="hidden" name="category" value="'.($category).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>カテゴリー</label><input type="text" class="form-control" name="category" value="'.($category).'" /></div>';
}
if(!empty($program_name) ){
    $url = add_get_query($url , 'program_name='.urlencode($program_name) );
    $myformvalue = $myformvalue.'<input type="hidden" name="program_name" value="'.($program_name).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>作品名</label><input type="text" class="form-control" name="program_name" value="'.($program_name).'" /></div>';
}
if(!empty($artist) ){
    $url = add_get_query($url , 'artist='.urlencode($artist) );
    $myformvalue = $myformvalue.'<input type="hidden" name="artist" value="'.($artist).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>歌手名</label><input type="text" class="form-control" name="artist" value="'.($artist).'" /></div>';
}
if(!empty($worker) ){
    $url = add_get_query($url , 'worker='.urlencode($worker) );
    $myformvalue = $myformvalue.'<input type="hidden" name="worker" value="'.($worker).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>動画製作者</label><input type="text" class="form-control" name="worker" value="'.($worker).'" /></div>';
}
if(!empty($filename) ){
    $url = add_get_query($url , 'filename='.urlencode($filename) );
    $myformvalue = $myformvalue.'<input type="hidden" name="filename" value="'.($filename).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>ファイル名</label><input type="text" class="form-control" name="filename" value="'.($filename).'" /></div>';
}

if(!empty($datestart) ){
    $url = add_get_query($url , 'datestart='.$datestart );
    $myformvalue = $myformvalue.'<input type="hidden" name="datestart" value="'.($datestart).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>更新日範囲 始め</label><input type="date" class="form-control" name="datestart" value="'.($datestart).'" /></div>';
}

if(!empty($dateend) ){
    $url = add_get_query($url , 'dateend='.$dateend );
    $myformvalue = $myformvalue.'<input type="hidden" name="dateend" value="'.($dateend).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>更新日範囲 終わり</label><input type="date" class="form-control" name="dateend" value="'.($dateend).'" /></div>';
}


if(!empty($match) ){
    $url = add_get_query($url , 'match='.urlencode($match) );
}


if(!empty($select_orderby) ){
    $url = add_get_query($url , 'orderby='.$select_orderby.'&scending='.$select_scending );
}


if(!empty($url)){
    $url = add_get_query($url , 'start='.$displayfrom.'&length='.$displaynum.'&lister_dbpath='.$lister_dbpath);
    $url = 'http://localhost/search_listerdb_songlist_json.php'.$url;
}
?>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>作品名リストFromHeader</title>
  <link type="text/css" rel="stylesheet" href="/css/style.css" />
  <script type="text/javascript" charset="utf8" src="/js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
   $errmsg = "";
   
   $programlist_json = file_get_contents($url);
   if(!$programlist_json) {
      $errmsg = '検索結果リストの取得に失敗';
   }else {
      $programlist = json_decode($programlist_json,true);
   }
   if(!$programlist ){
   print '<pre>';
   print "url:\n";
      print $url;
   print "\nprogramlist_json:\n";
      print $programlist_json;
   print "\nprogramlist_dump:\n";
      var_dump($programlist);
   print '</pre>';
   die();
   }  


function basename_jp($path){
    $p_info = explode('\\', $path);
    return end($p_info);
}


function create_requestconfirmlink($songinfo) {
  global $lister_dbpath;

$fullpath = $songinfo['found_path'];
$filename = basename_jp($fullpath);

$link = 'request_confirm.php?filename='.urlencode($filename).'&fullpath='.urlencode($fullpath).'&lister_dbpath='.$lister_dbpath;
return $link;

}

function selected_check($checkstr, $selectedstr){
   if( $checkstr === $selectedstr ) {
       return 'selected';
   }
   return "";
}
?>

</head>
<body>
<?php
?>

<div class="container  ">
  <div class="row ">
    <div class="col-xs-4 col-md-4  ">
      <a href="search_listerdb.php" class="btn btn-default center-block" >作品名 </a>
    </div>
    <div class="col-xs-4 col-md-4">
      <a href="search_listerdb_artist.php" class="btn btn-default center-block" >歌手名 </a>
    </div>
    <div class="col-xs-4 col-md-4 ">
      <a href="search_listerdb_filename_index.php" class="btn btn-default center-block" >ファイル名 </a>
    </div>
  </div>
</div>
<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

// var_dump($programlist);
print '<div class="container">';
if( !empty($program_name ) && !empty($category ) ) {
    print '<h2>「'.$category.'」「'.$program_name.'」の曲一覧 </h2>';
} else if(!empty($artist) ){
    print '<h2>「'.$artist.'」の曲一覧 </h2>';
} else {
}

// 再検索フォーム設置 
print '<div class="bg-info">';
print '<form method="GET" action="search_listerdb_songlist.php" >';
print $myformvalue_shown;
print '<div class="form-group">';
if(!empty($myformvalue_shown)) {
    print '<div class="btn-group" data-toggle="buttons">';
    print '	<label class="btn btn-default ';
    if($match === 'part') print 'active';
    print '">';
    print '		<input type="radio" name="match" value="part" autocomplete="off" ';
    if($match === 'part') print 'checked';
    print '> 部分一致';
    print '	</label>';
    print '	<label class="btn btn-default ';
    if($match === 'full') print 'active';
    print '">';
    print '		<input type="radio" name="match" value="full" autocomplete="off" ';
    if($match === 'full') print 'checked';
    print '> 完全一致';
    print '	</label>';
print '</div>';
}
print '</div>';
print '<button type="submit" class="btn btn-default mb-2">再検索</button>';
print '</form>';
print '</div>';

if($programlist['recordsTotal'] == 0) {
    print '<h3 class="text-warning">';
    print '検索の結果ファイルが見つかりませんでした';
    print '</h3>';
    print '</body> </html>';
    die();
}

print '<form method="GET" action="search_listerdb_songlist.php" class="form-inline" >';
print '<div class="form-group">';
print '<label for="orderby">項目</label>';
print '<select class="form-control" id="orderby"  name="orderby" >';
print '<option value="found_file_size" ';
print selected_check("found_file_size", $select_orderby );
print '>ファイルサイズ</option>';
print '<option value="found_last_write_time" ';
print selected_check("found_last_write_time", $select_orderby );
print '>更新日</option>';
print '<option value="song_name" ';
print selected_check("song_name", $select_orderby );
print '>曲名</option>';
print '<option value="song_artist"';
print selected_check("song_artist", $select_orderby );
print '>歌手名</option>';
print '</select>';
print '</div>';
print '<div class="form-group">';
print '<label for="scending">順番</label>';
print '<select class="form-control" id="scending" name="scending" >';
print '<option value="ASC" ';
print selected_check("ASC", $select_scending );
print '>昇順</option>';
print '<option value="DESC" ';
print selected_check("DESC", $select_scending );
print '>降順</option>';
print '</select>';
print $myformvalue;
print '</div>';
print '<button type="submit" class="btn btn-default mb-2">並び替え</button>';
print '</form>';


print '    <div class="text-right">';
print $displayfrom.'-'.($displayfrom+$displaynum).'（全'.$programlist['recordsTotal'].'件）';
print '    </div>';
print '  <div class="row">';
foreach ($programlist['data'] as $program ){
print '<div class="container bg-info">';
//var_dump($program);
$display_songname = $program['song_name'];
if( empty($program['song_name']) ){
   $display_songname = basename_jp($program['found_path']);
}
print '    <div class="col-xs-12 col-md-12 bg-success" > ';
print '<a href=/'.create_requestconfirmlink($program).' class="btn btn-primary btn-lg btn-block"  style="white-space: normal;" ><strong> '. $display_songname.'</strong> </a>';
print '    </div>';
print '    <div class="col-xs-12 col-md-12" >';
print '    <dl class="dl-horizontal">';
print '    <dt>';
print '作品名';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?program_name='.urlencode($program['program_name']).'&lister_dbpath='.$lister_dbpath.'">' . $program['program_name'] .' </a>';
if(!empty( $program['song_op_ed'])){
    print '&nbsp;'.$program['song_op_ed'];
}
// http://localhost/search_listerdb_songlist.php?program_name=作品名&category=ISNULL&lister_dbpath=List.sqlite3
print '    </dd>';
print '    <dt>';
print '歌い手';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?artist='.urlencode($program['song_artist']).'&lister_dbpath='.$lister_dbpath.'">' . $program['song_artist'] .' </a>';
// http://localhost/search_listerdb_songlist.php?artist=歌手名&lister_dbpath=List.sqlite3
print '    </dd>';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dt>';
print 'ファイルサイズ';
print '    </dt>';
print '    <dd>';
print formatBytes($program['found_file_size']);
print '    </dd>';
print '    </div > ';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dt>';
print '最終更新日';
print '    </dt>';
print '    <dd>';
$updatetime = cal_from_jd($program['found_last_write_time'],CAL_GREGORIAN);
//print strftime('%F %X', cal_from_jd($program['found_last_write_time'],CAL_GREGORIAN));
print $updatetime['year'].'/'.$updatetime['month'].'/'.$updatetime['day'];
print '    </dd>';
print '    </div > ';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dt>';
print '動画制作者';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?worker='.urlencode($program['found_worker']).'&lister_dbpath='.$lister_dbpath.'">' . $program['found_worker'] .' </a>';
// print $program['found_worker'];
print '    </dd>';
print '    </div > ';
print '    <dt>';
print 'ファイル名';
print '    </dt>';
print '    <dd>';
print $program['found_path'];
print '    </dd>';
print '    </dl>';
print '    </div>';
print '  </div>';

}
print '  </div>';


$urlparams = "";
if( !empty($filename) ){
    $urlparams = $urlparams.'filename='.urlencode($filename);
}
if( !empty($header) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'header='.$header;
}
if( !empty($category) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'category='.$category;
}
if( !empty($draw) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'draw='.$draw;
}
if( !empty($lister_dbpath) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'lister_dbpath='.$lister_dbpath;
}


print '<div class="container  ">';
print '  <div class="row ">';
print '    <div class="col-xs-4 col-md-4  ">';
if($displayfrom > 0 ) {
    $nextstart = (($displayfrom - $displaynum ) <= 0) ? 0 : $displayfrom - $displaynum;
    print '      <a href="search_listerdb_songlist.php?'.$urlparams.'&start='.$nextstart.'&length='.$displaynum.'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4 text-center">';
print $displayfrom.'-'.($displayfrom+$displaynum).'（全'.$programlist['recordsTotal'].'件）';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($programlist['recordsTotal'] > ($displayfrom + $displaynum) ) {
    print '      <a href="search_listerdb_songlist.php?'.$urlparams.'&start='.($displaynum+$displayfrom).'&length='.$displaynum.'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';



print '</div>';

?>

</body>
</html>
<?php

?>