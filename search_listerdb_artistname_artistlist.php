<html>
<head>
<?php 


$displayfrom=0;
$displaynum=50;
$draw = 1;
$allcount = 0;

$lister_dbpath = "List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}


if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
}

if(array_key_exists("category", $_REQUEST)) {
    $category = $_REQUEST["category"];
}

$artist = "";
if(array_key_exists("artist", $_REQUEST)) {
    $artist = $_REQUEST["artist"];
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

// build query url
$url = 'http://localhost/search_listerdb_artistlist_json.php?start='.$displayfrom.'&length='.$displaynum.'&artist='.$artist.'&match='.$match.'&lister_dbpath='.$lister_dbpath;

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
  <title>歌い手名リスト</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
   $errmsg = "";
   
   $list_json = file_get_contents($url);
   if(!$list_json) {
      $errmsg = '作品の取得に失敗';
   }else {
      $list = json_decode($list_json,true);
   }
   if(!$list ){
   print $url;
   var_dump($list);
   die();
   }  

?>

</head>
<body>

<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

// var_dump($list);
print '<div class="container">';
print '<h2>「'.$artist.'」の歌い手名一覧 </h2>';
print '  <div class="row bg-info">';
foreach ($list['data'] as $artist ){

// var_dump($artist);

print '    <div class="col-xs-12 col-md-6" >';
print '    <div class="btn-toolbar" style="margin-bottom: 5px" >';
if(empty($artist['song_artist'])){
    print '<a class="btn btn-primary btn-block" href="search_listerdb_songlist.php?artist='.'ISNULL'.'&match="full"&lister_dbpath='.$lister_dbpath.'">';
    print '【歌手名なし】';
}else {
    print '<a class="btn btn-primary btn-block" href="search_listerdb_songlist.php?artist='.urlencode($artist['song_artist']).'&match="full"&lister_dbpath='.$lister_dbpath.'">';
    print $artist['song_artist'];
}
print '</a>';
print '    </div>';
print '    </div>';

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
    $urlparams = $urlparams.'match='.$match;
}
if( !empty($category) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'artist='.$artist;
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
    print '      <a href="search_listerdb_artistname_artistlist.php?'.$urlparams.'&start='.$nextstart.'&length='.$displaynum.'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4">';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($list['recordsTotal'] > ($displayfrom + $displaynum) ) {
    print '      <a href="search_listerdb_artistname_artistlist.php?'.$urlparams.'&start='.($displaynum+$displayfrom).'&length='.$displaynum.'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';


print '</div>';
?>

</body>
</html>
<?php

