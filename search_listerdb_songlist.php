<html>
<head>
<?php 
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc.php';

print_meta_header();

$displayfrom=0;
$displaynum=50;
$draw = 1;
$allcount = 0;

$myrequestarray=array();

$lister_dbpath = 'list\List.sqlite3';
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}
$myrequestarray["lister_dbpath"] = $lister_dbpath;


if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
    $myrequestarray["header"] = $header;
}

if(array_key_exists("category", $_REQUEST)) {
    $category = $_REQUEST["category"];
    $myrequestarray["category"] = $category;
}

if(array_key_exists("program_name", $_REQUEST)) {
    $program_name = $_REQUEST["program_name"];
    $myrequestarray["program_name"] = $program_name;
}

$artist = "";
if(array_key_exists("artist", $_REQUEST)) {
    $artist = $_REQUEST["artist"];
    $myrequestarray["song_artist"] = $artist;
}

if(empty($artist)){
if(array_key_exists("song_artist", $_REQUEST)) {
    $artist = $_REQUEST["song_artist"];
    $myrequestarray["song_artist"] = $artist;
}
}


$worker = "";
if(array_key_exists("worker", $_REQUEST)) {
    $worker = $_REQUEST["worker"];
    $myrequestarray["worker"] = $worker;
}

$filename = "";
if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
    $myrequestarray["filename"] = $filename;
}

$datestart = "";
if(array_key_exists("datestart", $_REQUEST)) {
    $datestart = $_REQUEST["datestart"];
    $myrequestarray["datestart"] = $datestart;
}

$dateend = "";
if(array_key_exists("dateend", $_REQUEST)) {
    $dateend = $_REQUEST["dateend"];
    $myrequestarray["dateend"] = $dateend;
}

$maker_name = "";
if(array_key_exists("maker_name", $_REQUEST)) {
    $maker_name = $_REQUEST["maker_name"];
    $myrequestarray["maker_name"] = $maker_name;
}

$song_name = "";
if(array_key_exists("song_name", $_REQUEST)) {
    $song_name = $_REQUEST["song_name"];
    $myrequestarray["song_name"] = $song_name;
}

$tie_up_group_name = "";
if(array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
    $myrequestarray["tie_up_group_name"] = $tie_up_group_name;
}


$select_orderby_str ="song_name asc, found_file_size desc";

$select_orderby = "";
/*
if (isset($_COOKIE['YukariListerDBOrderby'])) {
    $select_orderby = $_COOKIE['YukariListerDBOrderby'];
}
*/
if(array_key_exists("orderby", $_REQUEST)) {
    $select_orderby = $_REQUEST["orderby"];
//    setcookie("YukariListerDBOrderby",  $select_orderby);
}
// $select_scending = 'ASC';
$select_scending ="";
 /*
if (isset($_COOKIE['YukariListerDBScending'])) {
    $select_scending = $_COOKIE['YukariListerDBScending'];
}
*/
if(array_key_exists("scending", $_REQUEST)) {
    $select_scending = $_REQUEST["scending"];
//    setcookie("YukariListerDBScending",  $select_scending);
}

if(!empty($select_orderby) && !empty($select_scending) ) {
   $select_orderby_str = $select_orderby . ' ' . $select_scending;
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

$selectid = '';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}

$linkoption = 'lister_dbpath='.$lister_dbpath;
if(!empty($selectid) ) $linkoption = $linkoption.'&selectid='.$selectid;



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

if(!empty($maker_name) ){
    $url = add_get_query($url , 'maker_name='.urlencode($maker_name) );
    $myformvalue = $myformvalue.'<input type="hidden" name="maker_name" value="'.($maker_name).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>製作会社</label><input type="text" class="form-control" name="maker_name" value="'.($maker_name).'" /></div>';
}

if(!empty($song_name) ){
    $url = add_get_query($url , 'song_name='.urlencode($song_name) );
    $myformvalue = $myformvalue.'<input type="hidden" name="song_name" value="'.($song_name).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>曲名</label><input type="text" class="form-control" name="song_name" value="'.($song_name).'" /></div>';
}

if(!empty($tie_up_group_name) ){
    $url = add_get_query($url , 'tie_up_group_name='.urlencode($tie_up_group_name) );
    $myformvalue = $myformvalue.'<input type="hidden" name="tie_up_group_name" value="'.($tie_up_group_name).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>シリーズ</label><input type="text" class="form-control" name="tie_up_group_name" value="'.($tie_up_group_name).'" /></div>';
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


if(!empty($select_orderby_str) ){
//    $url = add_get_query($url , 'orderby='.$select_orderby_str.'&scending='.$select_scending );
    $url = add_get_query($url , 'orderby='.urlencode($select_orderby_str) );
}


if(!empty($url)){
    $url = add_get_query($url , 'start='.$displayfrom.'&length='.$displaynum.'&'.$linkoption);
    $url = 'http://localhost/search_listerdb_songlist_json.php'.$url;
}
?>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


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
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
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


function create_filelistlink($songinfo) {
  global $linkoption;

$querystr = "";

$clm = 'song_name';

if(!empty($songinfo[$clm])){
   if( !empty($querystr)){
   $querystr = $querystr .'&';
   }
   $querystr = $querystr . $clm . '=' . urlencode($songinfo[$clm]);
}

$clm = 'program_name';

if(!empty($songinfo[$clm])){
   if( !empty($querystr)){
   $querystr = $querystr .'&';
   }
   $querystr = $querystr . $clm . '=' . urlencode($songinfo[$clm]);
}

   if( !empty($querystr)){
   $querystr = $querystr .'&';
   }
   $querystr = $querystr . $linkoption;

   if( !empty($querystr)){
   $querystr = $querystr .'&';
   }
   $querystr = $querystr . "match=full";


$fullpath = $songinfo['found_path'];
$filename = basename_jp($fullpath);


$link = 'search_listerdb_filelist.php?'.$querystr;
return $link;

}

function create_requestconfirmlink($songinfo) {
  global $linkoption;

$fullpath = $songinfo['found_path'];
$filename = basename_jp($fullpath);

$link = 'request_confirm.php?filename='.urlencode($filename).'&fullpath='.urlencode($fullpath).'&'.$linkoption;
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
shownavigatioinbar('searchreserve.php');
showuppermenu("",$linkoption);

?>

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
if(!empty($lister_dbpath))
    print '<input type="hidden" name="lister_dbpath" value="'.$lister_dbpath.'" />';
if(!empty($selectid))
    print '<input type="hidden" name="selectid" value="'.$selectid.'" />';
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
if(!empty($lister_dbpath)){
    print '<input type="hidden" name="lister_dbpath" value="'.($lister_dbpath).'" />';
}
if(!empty($selectid)){
    print '<input type="hidden" name="selectid" value="'.($selectid).'" />';
}

print '<button type="submit" class="btn btn-default mb-2">並び替え</button>';
print '</form>';

if(($displayfrom + $displaynum) < $programlist['recordsTotal']) {
  $displaylast = $displayfrom + $displaynum;
}else {
  $displaylast = $programlist['recordsTotal'];
}
print '    <div class="text-right">';
print $displayfrom.'-'.($displaylast).'（全'.$programlist['recordsTotal'].'件）';
print '    </div>';
print '  <div class="row">';
foreach ($programlist['data'] as $program ){
$display_songname = $program['song_name'];
if(empty($display_songname)) $display_songname = '未分類';

print '    <div class="col-xs-12 col-md-6" >';
print '    <div class="btn-toolbar" style="margin-bottom: 5px" >';
  $linkurl = create_filelistlink($program);
print '<a class="btn btn-primary btn-block indexbtnstr" href="'.$linkurl.'">';
print $display_songname;
print '（'.$program['COUNT(*)'].'）';
print '</a>';
print '    </div>';
print '    </div>';


/**** [OLD] Display All song Info 

print '<div class="container bg-info">';
//var_dump($program);
$display_songname = $program['song_name'];
if( empty($program['song_name']) ){
   $display_songname = basename_jp($program['found_path']);
}
print '    <div class="col-xs-12 col-md-12 bg-success" > ';
print '<a href='.create_filelistlink($program).' class="btn btn-primary btn-lg btn-block"  style="white-space: normal;" ><strong> '. $display_songname.'</strong> ';
if(!empty($program['found_comment'])){
print '<br />【'.$program['found_comment'].'】';
}
print '</a>';
print '    </div>';
print '    <div class="col-xs-12 col-md-12" >';
print '    <dl class="dl-horizontal">';
print '    <dt>';
print '作品名';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?program_name='.urlencode($program['program_name']).'&'.$linkoption.'">' . $program['program_name'] .' </a>';
if(!empty( $program['song_op_ed'])){
    print '&nbsp;'.$program['song_op_ed'];
}
if(!empty($program['maker_name'])){
print '&nbsp;&nbsp; <a href ="search_listerdb_songlist.php?maker_name='.urlencode($program['maker_name']).'&'.$linkoption.'">【' . $program['maker_name'] .'】 </a>';
}

if(!empty($program['tie_up_group_name'])){
print '&nbsp;&nbsp; <a href ="search_listerdb_songlist.php?tie_up_group_name='.urlencode($program['tie_up_group_name']).'&'.$linkoption.'">[' . $program['tie_up_group_name'] .'] </a>シリーズ';
}


// http://localhost/search_listerdb_songlist.php?program_name=作品名&category=ISNULL&lister_dbpath=List.sqlite3
print '    </dd>';
print '    <dt>';
print '歌い手';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?artist='.urlencode($program['song_artist']).'&'.$linkoption.'">' . $program['song_artist'] .' </a>';
// http://localhost/search_listerdb_songlist.php?artist=歌手名&lister_dbpath=List.sqlite3
print '    </dd>';
if(!empty($program['found_track'])){
print '    <dt>';
print 'トラック情報';
print '    </dt>';
print '    <dd>';
if( $program['found_smart_track_on'] == 1) {
print '&nbsp;<span class="label label-success" >OnVocal</span>';
}
if( $program['found_smart_track_off'] == 1) {
print '&nbsp;<span class="label label-success" >OffVocal</span>';
}
print '&nbsp; 情報：'.$program['found_track'];
}
print '    </dd>';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dl>';
print '    <dt>';
print 'ファイルサイズ';
print '    </dt>';
print '    <dd>';
print formatBytes($program['found_file_size']);
print '    </dd>';
print '    </dl>';
print '    </div > ';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dl>';
print '    <dt>';
print '最終更新日';
print '    </dt>';
print '    <dd>';
$updatetime = cal_from_jd($program['found_last_write_time'],CAL_GREGORIAN);
//print strftime('%F %X', cal_from_jd($program['found_last_write_time'],CAL_GREGORIAN));
if($updatetime['year'] < 0 ) $updatetime = cal_from_jd(($program['found_last_write_time']+2400000.5),CAL_GREGORIAN);
print $updatetime['year'].'/'.$updatetime['month'].'/'.$updatetime['day'];
print '    </dd>';
print '    </dl>';
print '    </div > ';
print '    <div class="col-xs-4 col-md-4 " > ';
print '    <dl>';
print '    <dt>';
print '動画制作者';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?worker='.urlencode($program['found_worker']).'&'.$linkoption.'">' . $program['found_worker'] .' </a>';
// print $program['found_worker'];
print '    </dd>';
print '    </div > ';
print '    </dl>';
print '    <dt>';
print 'ファイル名';
print '    </dt>';
print '    <dd> <font size="-1" >';
print $program['found_path'];
print '    </font></dd>';
print '    </dl>';
print '    </div>';
print '  </div>';
********/
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
    $urlparams = $urlparams.'category='.urlencode($category);
}
if( !empty($program_name) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'program_name='.urlencode($program_name);
}
if( !empty($artist) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'artist='.urlencode($artist);
}
if( !empty($worker) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'worker='.urlencode($worker);
}

if( !empty($datestart) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'datestart='.urlencode($datestart);
}

if( !empty($dateend) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'dateend='.urlencode($dateend);
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
    $urlparams = $urlparams.$linkoption;
}


print '<div class="container  ">';
print '  <div class="row ">';
print '    <div class="col-xs-4 col-md-4  ">';
if($displayfrom > 0 ) {
    $nextstart = (($displayfrom - $displaynum ) <= 0) ? 0 : $displayfrom - $displaynum;
    $myrequestarray["start"] = $nextstart;
    $myrequestarray["length"] = $displaynum;
    print '      <a href="search_listerdb_songlist.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4 text-center">';
print $displayfrom.'-'.($displaylast).'（全'.$programlist['recordsTotal'].'件）';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($programlist['recordsTotal'] > ($displayfrom + $displaynum) ) {
    $myrequestarray["start"] = $displaynum+$displayfrom;
    $myrequestarray["length"] = $displaynum;
    print '      <a href="search_listerdb_songlist.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
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