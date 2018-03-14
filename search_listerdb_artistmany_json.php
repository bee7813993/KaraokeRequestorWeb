<?php

$displayfrom=0;
$displaynum=50;
$draw = 1;
$allcount = 0;

require_once('function_search_listerdb.php');

$lister_dbpath = 'list\List.sqlite3';
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

$program_category = "";
if(array_key_exists("program_category", $_REQUEST)) {
    $program_category = $_REQUEST["program_category"];
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



// DB初期化
$lister = new ListerDB();
$lister->listerdbfile = $lister_dbpath;
$listerdb = $lister->initdb();
if( !$listerdb ) {
    die();
}


  // 検索条件
  $select_where = "";
  $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;

// 総件数のみ取得
$sql = 'select COUNT(DISTINCT song_artist) from t_found;';
$alldbdata =  $lister->select($sql);
if(!$alldbdata){
     print $sql;
     die();
}
$totalrequest = $alldbdata[0]["COUNT(DISTINCT song_artist)"];

  $sql = 'select song_artist,COUNT(*) AS COUNT from t_found GROUP BY song_artist ORDER BY COUNT DESC '. $select_where_limit.';';
  $alldbdata =  $lister->select($sql);
  if(!$alldbdata){
     print $sql;
     die();
  }

  $returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
  $json = json_encode($returnarray,JSON_PRETTY_PRINT);


// print '<pre>';
print $json;
// print '</pre>';
?>