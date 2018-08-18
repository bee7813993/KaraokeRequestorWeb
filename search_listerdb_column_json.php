<?php
require_once('function_search_listerdb.php');

$displayfrom=0;
$displaynum=80000;
$draw = 1;
$allcount = 0;


$lister_dbpath = 'list\List.sqlite3';
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
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

$column = "";
if(array_key_exists("column", $_REQUEST)) {
    $column = $_REQUEST["column"];
}

$sqlwhere ="";
if(array_key_exists("sqlwhere", $_REQUEST)) {
    $sqlwhere = $_REQUEST["sqlwhere"];
}

//部分一致 完全一致
$match = "";
if(array_key_exists("match", $_REQUEST)) {
    $match = $_REQUEST["match"];
}

$select_orderby ="";
if(array_key_exists("orderby", $_REQUEST)) {
    $select_orderby = $_REQUEST["orderby"];
}

$select_scending = 'ASC';
$select_scending ="";
if(array_key_exists("scending", $_REQUEST)) {
    $select_scending = $_REQUEST["scending"];
}


// DB初期化
$lister = new ListerDB();
$lister->listerdbfile = $lister_dbpath;
$listerdb = $lister->initdb();
if( !$listerdb ) {
    die();
}

// 検索条件
 $select_where ="";
if(!empty($sqlwhere)) {
 $select_where = 'where '.$sqlwhere;
}

    $select_where = $select_where ;
if (!empty($select_orderby) ){
    $select_where = $select_where . ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}
    $select_where_limit = $select_where . ' GROUP BY '.$column. ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;
// 総件数のみ取得
$countword = 'COUNT(DISTINCT  '.$column.')';
$sql = 'SELECT '.$countword.' FROM t_found '. $select_where.';';
$alldbdata = $lister->select($sql);
if($alldbdata === false){
     print $sql;
     die();
}
//var_dump($alldbdata);
$totalrequest = $alldbdata[0][$countword];
// print '<pre>';
// print $totalrequest;
// print '</pre>';



$sql = 'select '.$column.',COUNT(found_path) from t_found '. $select_where_limit.';';
$alldbdata = $lister->select($sql);
if($alldbdata === false){
     print $sql;
}

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

// print '<pre>';
print $json;
// print '</pre>';

?>