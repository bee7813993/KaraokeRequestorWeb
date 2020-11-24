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

$anyword = "";
if(array_key_exists("anyword", $_REQUEST)) {
    $anyword = $_REQUEST["anyword"];
    $myrequestarray["anyword"] = $anyword;
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


$select_orderby_str ="found_last_write_time desc";

$select_orderby = "";
if(array_key_exists("orderby", $_REQUEST)) {
    $select_orderby = $_REQUEST["orderby"];
    $myrequestarray["orderby"] = $select_orderby;
    setcookie("YukariListerDBOrderby",  $select_orderby);
}
else if (isset($_COOKIE['YukariListerDBOrderby'])) {
    $select_orderby = $_COOKIE['YukariListerDBOrderby'];
    $myrequestarray["orderby"] = $select_orderby;
}

// $select_scending = 'ASC';
$select_scending ="";
if(array_key_exists("scending", $_REQUEST)) {
    $select_scending = $_REQUEST["scending"];
    $myrequestarray["scending"] = $select_scending;
    setcookie("YukariListerDBScending",  $select_scending);
}

else if (isset($_COOKIE['YukariListerDBScending'])) {
    $select_scending = $_COOKIE['YukariListerDBScending'];
    $myrequestarray["orderby"] = $select_orderby;
}


if(!empty($select_orderby) && !empty($select_scending) ) {
   $select_orderby_str = $select_orderby . ' ' . $select_scending;
}else {
   $select_orderby  = "found_last_write_time";
   $select_scending = "desc";
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


function isSocketListening($host, $port, $timeout = 30){
	$sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
	if(!$sock){
		return false;
	}
	fclose($sock);
	return true;
}

// Listerのプレビューポートが空いているかの確認
$listerpreviewportenable = false;
$listerpreviewportenable = (!check_access_from_online()  && isSocketListening($_SERVER["SERVER_NAME"],13582,1));

function make_preview_modal_listerdb($filepath, $modalid) {
  global $_SERVER;
  
  $dlpathinfo = pathinfo($filepath);
  if(array_key_exists('extension',$dlpathinfo)){
  $filetype = '';
  if($dlpathinfo['extension'] === 'mp4'){
      $filetype = ' type="video/mp4"';
  }else if($dlpathinfo['extension'] === 'flv'){
      $filetype = ' type="video/x-flv"';
  }else if($dlpathinfo['extension'] === 'avi'){
      $filetype = ' type="video/x-msvideo"';
      return null;
  }else {
      return null;
      return "この動画形式はプレビューできません";
  }  
  }else {
      return null;
  }
//var_dump ($_SERVER);
//print  $_SERVER["SERVER_NAME"];
  $previewpath[] = "http://" . $_SERVER["SERVER_NAME"] . ":13582/" . urlencode($filepath);
//print $previewpath[0];
  $filepath_url = str_replace('\\', '/', $filepath);
  $previewpath[] = "http://" . $_SERVER["SERVER_NAME"] . ":13582/" . ($filepath_url);
  $button='<a href="#" data-toggle="modal" class="previewmodallink btn btn-default" data-target="#'.$modalid.'" > プレビュー </a>';
  
  $previewsource = "";
   foreach($previewpath as $previewurl ){
     $previewsource = $previewsource.'<source src="'.$previewurl.'" '.$filetype.' />';
   }

$modaljs='<script>
$(function () {
$(\'#'.$modalid.'\').on(\'hidden.bs.modal\', function (event) {
var myPlayer = videojs("preview_video_'.$modalid.'a");
myPlayer.pause();
//var v = document.getElementById("preview_video_'.$modalid.'a");
//v.pause();
});
});</script>';

  $modaldg='<!-- 2.モーダルの配置 -->'.
'<div class="modal" id="'.$modalid.'" tabindex="-1">'.
'  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
         <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modal-label">動画プレビュー</h4>
      </div>
      <div class="modal-body">
        <video id="preview_video_'.$modalid.'a" class="video-js vjs-default-skin" controls muted playsinline preload="none"  data-setup="{}"  style="width: 320px; height: 180px;" >'.$previewsource.'
        </video>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
      </div>
    </div>
  </div>
</div>';

return $button."\n".$modaljs.$modaldg;

}

function get_first_clminfo($filelist , $clm){

    $ret = "";
    foreach($filelist as $fileinfo){
        if(!empty($fileinfo[$clm])) return $fileinfo[$clm];
    }
    return $ret;

}

function filelistfromsong($filelist){
    global $linkoption ;
    global $listerpreviewportenable;
  print '<div class="divid0 panel panel-primary"> ';
    print '<div class="panel-heading " ><strong>';
      print $filelist[0]['song_name'];
    print "</strong></div>";
    print '<div class="panel-body bg-info">';
      print '<div class="container">';
        print '<p>';
      $str =   get_first_clminfo($filelist,'song_artist');
      if(!empty($str)){
          print '<strong>';
      print '歌手名：';
          print '</strong>';
      print '<a href ="search_listerdb_songlist.php?artist='.urlencode($str).'&'.$linkoption.'">' . $str .' </a>';
      }
      $str =   get_first_clminfo($filelist,'program_name');
      if(!empty($str)){
      print '&nbsp;';
          print '<strong>';
      print '作品名：';
          print '</strong>';
      print '<a href ="search_listerdb_songlist.php?program_name='.urlencode($str).'&'.$linkoption.'">' . $str .' </a>';
      }
      $str =   get_first_clminfo($filelist,'song_op_ed');
      if(!empty($str)){
      print '&nbsp;';
      print $str;
      }
      $str =   get_first_clminfo($filelist,'maker_name');
      if(!empty($str)){
      $sqlwhere = 'maker_name=\''.$str.'\'';
      print '&nbsp;&nbsp; <a href ="search_listerdb_column_list.php?searchcolumn=program_name&sqlwhere='.urlencode($sqlwhere).'&maker_name='.urlencode($str).'&'.$linkoption.'">【' . $str .'】 </a>';
      }
      $str =   get_first_clminfo($filelist,'tie_up_group_name');
      if(!empty($str)){
      $sqlwhere = 'tie_up_group_name=\''.$str.'\'';
      print '&nbsp;&nbsp; <a href ="search_listerdb_column_list.php?searchcolumn=program_name&sqlwhere='.urlencode($sqlwhere).'&tie_up_group_name='.urlencode($str).'&'.$linkoption.'">[' . $str .'] </a>シリーズ';
      }
        print '</p>';
      print '</div> ';//container
      
      foreach($filelist as $k => $fileinfo){
      
       print '<div class="list-group-item" >';
        print '<div class="row">';
		if($listerpreviewportenable )
        print '<div class="col-md-10 col-xs-12">';
        else
        print '<div class="col-md-12 col-xs-12">';
        print '<a href="'.create_requestconfirmlink($fileinfo).'" class=" divid10 btn btn-primary btn-lg btn-block" style="white-space: normal; overflow: auto; text-align: left; font-size: medium;" >';
        if(!empty($fileinfo['found_comment'])){
          print '<strong class="text-center">【';
          print $fileinfo['found_comment'];
          print '】</strong>';
        print '<br />';
        }
          print '<strong>';
        print ''.basename_jp($fileinfo['found_path']);
          print '</strong>';
        print '</a>'; //divid10
        $fileintoexilts = false;
        print '</div>'; //class="col-sm-10"
		if($listerpreviewportenable ){
        print '<div class="col-md-2 col-xs-12">';
        // preview 設置
		     $fn = "";
			 $previewpath = $fileinfo['found_path'];
			 $previewmodal = make_preview_modal_listerdb($previewpath,$k); 
			 $fn = $fn . "\n" . '<div Align="right">';
			 $fn = $fn . $previewmodal;
			 $fn = $fn . '</div>';
		     print($fn);
        print '</div>'; //class="col-sm-2"
		}
        print '</div>'; //class="row"

        if(!empty($fileinfo['found_track'])){
          print '<strong>';
        print 'トラック情報：';
          print '</strong>';
        if( $fileinfo['found_smart_track_on'] == 1) {
        print '&nbsp;<span class="label label-success" >OnVocal</span>';
        }
        if( $fileinfo['found_smart_track_off'] == 1) {
        print '&nbsp;<span class="label label-success" >OffVocal</span>';
        }
        print '&nbsp; 情報：'.$fileinfo['found_track'];
        $fileintoexilts = true;
        }
          print '<strong>';
          if($fileintoexilts) print '&nbsp;&nbsp;';
        print 'ファイルサイズ：';
          print '</strong>';
        print formatBytes($fileinfo['found_file_size']);
          print '<strong>';
        print '&nbsp;&nbsp;最終更新日：';
          print '</strong>';
        $updatetime = cal_from_jd($fileinfo['found_last_write_time'],CAL_GREGORIAN);
        if($updatetime['year'] < 0 ) $updatetime = cal_from_jd(($fileinfo['found_last_write_time']+2400000.5),CAL_GREGORIAN);
        print $updatetime['year'].'/'.$updatetime['month'].'/'.$updatetime['day'];
        if(!empty($fileinfo['found_worker']) ){
          print '<strong>';
        print '&nbsp;&nbsp;';
        print '動画制作者：';
          print '</strong>';
        print '<a href ="search_listerdb_filelist.php?worker='.urlencode($fileinfo['found_worker']).'&'.$linkoption.'">' . $fileinfo['found_worker'] .' </a>';
        }
        print '<div style="font-size: small;">';
          print '<strong>';
        print 'ファイルパス：';
          print '</strong>';
        print   $fileinfo["found_path"];
        print '</div>';
	   print '</div>'; //bg-info
      }
    print '</div> ';//panel-body
    print '</div> ';//divid0
}


// build query url
$url = "";
$myformvalue = "";
$myformvalue_shown = "";

if(!empty($anyword) ){
    $url = add_get_query($url , 'anyword='.urlencode($anyword) );
    $myformvalue = $myformvalue.'<input type="hidden" name="anyword" value="'.($anyword).'" />';
    $myformvalue_shown = $myformvalue_shown.'<div class="form-group"><label>なんでも検索</label><input type="text" class="form-control" name="anyword" value="'.($anyword).'" /></div>';
}

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
    $url = 'http://localhost/search_listerdb_filelist_json.php'.$url;
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
<link href="js/video-js.min.css" rel="stylesheet">
<script src="js/video.min.js"></script>
<script>
  videojs.options.flash.swf = "js/video-js.swf"
</script>
<?php
   $errmsg = "";
   
// print $url;
   $programlist_json = file_get_contents($url);
// print $programlist_json;
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

function song_name_num($filelist){
$curfilename = "";
$songcounter = 0;
foreach($filelist as $fileinfo)
{
    if($fileinfo['song_name'] === NULL) continue;
    if($curfilename !== $fileinfo['song_name']){
        $curfilename = $fileinfo['song_name'];
        $songcounter++;
    }
}
return $songcounter;
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

//var_dump($programlist);
print '<div class="container">';
if( !empty($program_name ) && !empty($category ) ) {
    print '<h2>「'.$category.'」「'.$program_name.'」の曲一覧 </h2>';
} else if(!empty($artist) ){
    print '<h2>「'.$artist.'」の曲一覧 </h2>';
} else {
}

// 再検索フォーム設置 
print '<div class="bg-info">';
print '<form method="GET" action="search_listerdb_filelist.php" >';
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

print '<form method="GET" action="search_listerdb_filelist.php" class="form-inline" >';
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

// 曲名が単一かどうかの判別
if (song_name_num($programlist['data']) == 1 ){
print (filelistfromsong($programlist['data']));
} else {

foreach ($programlist['data'] as $k => $program ){
print '<div class="container bg-info">';
//var_dump($program);
$display_songname = $program['song_name'];
if( empty($program['song_name']) ){
   $display_songname = basename_jp($program['found_path']);
}
print '    <div class="col-xs-12 col-md-12 bg-success" > ';
print '<a href='.create_requestconfirmlink($program).' class="btn btn-primary btn-lg btn-block"  style="white-space: normal;" ><strong> '. $display_songname.'</strong> ';
if(!empty($program['found_comment'])){
print '<br />【'.$program['found_comment'].'】';
}
print '</a>';
		
		 if(!check_access_from_online()){
		     $fn = "";
			 $previewpath = $program['found_path'];
			 $previewmodal = make_preview_modal_listerdb($previewpath,$k); 
			 $fn = $fn . "\n" . '<div Align="right">';
			 $fn = $fn . $previewmodal;
			 $fn = $fn . '</div>';
		     print($fn);
		 }

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
$sqlwhere = 'maker_name=\''.$program['maker_name'].'\'';
print '&nbsp;&nbsp; <a href ="search_listerdb_column_list.php?searchcolumn=program_name&sqlwhere='.urlencode($sqlwhere).'&maker_name='.urlencode($program['maker_name']).'&'.$linkoption.'">【' . $program['maker_name'] .'】 </a>';
}

if(!empty($program['tie_up_group_name'])){
$sqlwhere = 'tie_up_group_name=\''.$program['tie_up_group_name'].'\'';
print '&nbsp;&nbsp; <a href ="search_listerdb_column_list.php?searchcolumn=program_name&sqlwhere='.urlencode($sqlwhere).'&tie_up_group_name='.urlencode($program['tie_up_group_name']).'&'.$linkoption.'">[' . $program['tie_up_group_name'] .'] </a>シリーズ';
//print '&nbsp;&nbsp; <a href ="search_listerdb_filelist.php?tie_up_group_name='.urlencode($program['tie_up_group_name']).'&'.$linkoption.'">[' . $program['tie_up_group_name'] .'] </a>シリーズ';
}


// http://localhost/search_listerdb_filelist.php?program_name=作品名&category=ISNULL&lister_dbpath=List.sqlite3
print '    </dd>';
print '    <dt>';
print '歌い手';
print '    </dt>';
print '    <dd>';
print '<a href ="search_listerdb_songlist.php?artist='.urlencode($program['song_artist']).'&'.$linkoption.'">' . $program['song_artist'] .' </a>';
// http://localhost/search_listerdb_filelist.php?artist=歌手名&lister_dbpath=List.sqlite3
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
print '<a href ="search_listerdb_filelist.php?worker='.urlencode($program['found_worker']).'&'.$linkoption.'">' . $program['found_worker'] .' </a>';
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

}
} // if song_name_num
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
    print '      <a href="search_listerdb_filelist.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4 text-center">';
print $displayfrom.'-'.($displaylast).'（全'.$programlist['recordsTotal'].'件）';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($programlist['recordsTotal'] > ($displayfrom + $displaynum) ) {
    $myrequestarray["start"] = $displaynum+$displayfrom;
    $myrequestarray["length"] = $displaynum;
    print '      <a href="search_listerdb_filelist.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';



print '</div>';

?>
<div class="container">&emsp;  </div>

</body>
</html>
<?php

?>