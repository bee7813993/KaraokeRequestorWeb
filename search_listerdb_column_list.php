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

$lister_dbpath = "List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}
    $myrequestarray["lister_dbpath"] = $lister_dbpath;

if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
    $myrequestarray["header"] = $header;
}

if(array_key_exists("searchcolumn", $_REQUEST)) {
    $searchcolumn = $_REQUEST["searchcolumn"];
    $myrequestarray["searchcolumn"] = $searchcolumn;
}

$category = "";
if(array_key_exists("sqlwhere", $_REQUEST)) {
    $sqlwhere = $_REQUEST["sqlwhere"];
    $myrequestarray["sqlwhere"] = $sqlwhere;
}

if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = $_REQUEST["start"];
    $myrequestarray["start"] = $displayfrom;
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = $_REQUEST["length"];
    $myrequestarray["length"] = $displaynum;
}

if(array_key_exists("draw", $_REQUEST)) {
    $draw = $_REQUEST["draw"];
    $myrequestarray["draw"] = $draw;
}
$selectid = '';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    $myrequestarray["selectid"] = $selectid;
}

$searchitem = '';
if(array_key_exists("searchitem", $_REQUEST)) {
    $searchitem = $_REQUEST["searchitem"];
    $myrequestarray["searchitem"] = $searchitem;
}

$maker_name = "";
if(array_key_exists("maker_name", $_REQUEST)) {
    $maker_name = $_REQUEST["maker_name"];
    $myrequestarray["maker_name"] = $maker_name;
}

$tie_up_group_name = "";
if(array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
    $myrequestarray["tie_up_group_name"] = $tie_up_group_name;
}

$nextsonglistflg = true;
if( $searchcolumn == 'maker_name' || $searchcolumn == 'tie_up_group_name' ) {
   // 制作会社検索かシリーズ検索の場合、次は作品名リストになる処理をここに書く
    $nextsonglistflg = false;
    if(!empty($maker_name)) {
    }
}


// build query url
$sqlcolumn = $searchcolumn;
$getqueries = array();
$getqueries['start'] = $displayfrom;
$getqueries['length'] = $displaynum;
$getqueries['column'] = $sqlcolumn;
$getqueries['sqlwhere'] = $sqlwhere;
if(!empty($category)){
$getqueries['category'] = $category;
}
if(!empty($maker_name)){
$getqueries['maker_name'] = $maker_name;
}
if(!empty($tie_up_group_name)){
$getqueries['tie_up_group_name'] = $tie_up_group_name;
}
$getqueries['lister_dbpath'] = $lister_dbpath;


buildgetquery($getqueries);
$url = 'http://localhost/search_listerdb_column_json.php?'.buildgetquery($getqueries);
//print $url;
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
  <title>リストFromHeader</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
   $errmsg = "";
   
   $columnlist_json = file_get_contents($url);
   if(!$columnlist_json) {
      $errmsg = '項目の取得に失敗';
   }else {
      $columnlist = json_decode($columnlist_json,true);
   }
   if(!$columnlist ){
   print $url;
   var_dump($columnlist_json);
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

// var_dump($columnlist);
print '<div class="container">';
$searchtitle = "";
if(!empty($header)){
  $searchtitle = $searchtitle.'「'.$header.'」で始まる';
}
if(!empty($tie_up_group_name)){
  $searchtitle = $searchtitle.'「'.$tie_up_group_name.'」の作品';
}
if(!empty($maker_name)){
  $searchtitle = $searchtitle.'「'.$maker_name.'」の作品';
}
if(!empty($searchitem)){
  $searchtitle = $searchtitle.'「'.$searchitem.'」一覧';
}else {
  $searchtitle = $searchtitle.'一覧';
}
print '<h2>'. $searchtitle . '</h2>';

print '  <div class="row bg-info">';
foreach ($columnlist['data'] as $column ){

//var_dump($column);
print '    <div class="col-xs-12 col-md-6" >';
print '    <div class="btn-toolbar" style="margin-bottom: 5px" >';
if($nextsonglistflg){
  $linkurl = 'search_listerdb_songlist.php?'.$searchcolumn.'='.urlencode($column[$searchcolumn]).'&category='.urlencode($category).'&'.$linkoption;
}else {
  $sqlwhere=$searchcolumn.'=\''.$column[$searchcolumn].'\'';
  $linkurl = 'search_listerdb_column_list.php?searchcolumn=program_name&'.$searchcolumn.'='.urlencode($column[$searchcolumn]).'&sqlwhere='.urlencode($sqlwhere).'&'.'&category='.urlencode($category).'&'.$linkoption;
}
print '<a class="btn btn-primary btn-block indexbtnstr" href="'.$linkurl.'">';
print $column[$searchcolumn];
print '（'.$column['COUNT(DISTINCT song_name)'].'）';
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
    $myrequestarray["start"] = $nextstart;
    $myrequestarray["length"] = $displaynum;
    
    print '      <a href="search_listerdb_column_list.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4">';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($columnlist['recordsTotal'] > ($displayfrom + $displaynum) ) {
    $myrequestarray["start"] = $displaynum+$displayfrom;
    $myrequestarray["length"] = $displaynum;
//    print '      <a href="search_listerdb_column_list.php?'.$urlparams.'&start='.($displaynum+$displayfrom).'&length='.$displaynum.'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
    print '      <a href="search_listerdb_column_list.php?'.buildgetquery($myrequestarray).'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';


print '</div>';
?>

</body>
</html>

