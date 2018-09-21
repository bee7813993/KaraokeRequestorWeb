<html>
<head>
<?php 
require_once 'commonfunc.php';
print_meta_header();
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

$category='';
if(array_key_exists("category", $_REQUEST)) {
    $category = $_REQUEST["category"];
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

// build query url
$url = 'http://localhost/search_listerdb_programlist_json.php?start='.$displayfrom.'&length='.$displaynum.'&header='.urlencode($header).'&category='.urlencode($category).'&lister_dbpath='.$lister_dbpath;

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
      $errmsg = '作品の取得に失敗';
   }else {
      $programlist = json_decode($programlist_json,true);
   }
   if(!$programlist ){
   print $url;
   var_dump($programlist_json);
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
shownavigatioinbar('searchreserve.php');

$linkoption = 'lister_dbpath='.$lister_dbpath;
if(!empty($selectid) ) $linkoption = $linkoption.'&selectid='.$selectid;

// var_dump($programlist);
print '<div class="container">';
print '<h2>「'.$header.'」で始まる「'.$category.'」の作品名一覧 </h2>';
print '  <div class="row bg-info">';
foreach ($programlist['data'] as $program ){

//var_dump($program);
print '    <div class="col-xs-12 col-md-6" >';
print '    <div class="btn-toolbar" style="margin-bottom: 5px" >';
  $linkurl = 'search_listerdb_songlist.php?program_name='.urlencode($program['program_name']).'&category='.urlencode($category).'&'.$linkoption;
print '<a class="btn btn-primary btn-block indexbtnstr" href="'.$linkurl.'">';
if($program['program_name'] === 'その他'){
  $displaygname = '未分類';
} else  {
  $displaygname = $program['program_name'];
}
print $displaygname;
print '（'.$program['COUNT(DISTINCT song_name)'].'）';
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
    $urlparams = $urlparams.'header='.$header;
}
if( !empty($category) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'category='.urlencode($category);
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
    print '      <a href="search_listerdb_programlist_fromhead.php?'.$urlparams.'&start='.$nextstart.'&length='.$displaynum.'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4">';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($programlist['recordsTotal'] > ($displayfrom + $displaynum) ) {
    print '      <a href="search_listerdb_programlist_fromhead.php?'.$urlparams.'&start='.($displaynum+$displayfrom).'&length='.$displaynum.'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';


print '</div>';
?>

</body>
</html>
<?php

