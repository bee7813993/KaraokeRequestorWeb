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

if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = $_REQUEST["start"];
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = $_REQUEST["length"];
}

if(array_key_exists("draw", $_REQUEST)) {
    $draw = $_REQUEST["draw"];
}

// build query url
$url = 'http://localhost/search_listerdb_songlist_json.php?start='.$displayfrom.'&length='.$displaynum.'&category='.$category.'&program_name='.urlencode($program_name).'&lister_dbpath='.$lister_dbpath;;

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
      $errmsg = '作品の取得に失敗';
   }else {
      $programlist = json_decode($programlist_json,true);
   }
   if(!$programlist ){
      print $url;
      print $programlist_json;
      var_dump($programlist);
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
?>

</head>
<body>

<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

// var_dump($programlist);
print '<div class="container">';
print '<h2>「'.$category.'」「'.$program_name.'」の曲一覧 </h2>';
print '  <div class="row">';
foreach ($programlist['data'] as $program ){
print '<div class="container bg-info">';
//var_dump($program);
$display_songname = $program['song_name'];
if( empty($program['song_name']) ){
   $display_songname = basename_jp($program['found_path']);
}
print '    <div class="col-xs-12 col-md-12 bg-success" > ';
print '<a href=/'.create_requestconfirmlink($program).' class="btn btn-primary btn-lg btn-block" ><strong> '. $display_songname.'</strong> </a>';
print '    </div>';
print '    <div class="col-xs-12 col-md-12" >';
print '    <dl class="dl-horizontal">';
print '    <dt>';
print '作品名';
print '    </dt>';
print '    <dd>';
print $program['program_name'];
print '    </dd>';
print '    <dt>';
print '歌い手';
print '    </dt>';
print '    <dd>';
print $program['song_artist'];
print '    </dd>';
print '    <dt>';
print 'ファイルサイズ';
print '    </dt>';
print '    <dd>';
print $program['found_file_size'];
print '    </dd>';
print '    <dt>';
print '最終更新日';
print '    </dt>';
print '    <dd>';
print $program['found_last_write_time'];
print '    </dd>';
print '    <dt>';
print '動画制作者';
print '    </dt>';
print '    <dd>';
print $program['found_worker'];
print '    </dd>';
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
print '    <div class="col-xs-4 col-md-4">';
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