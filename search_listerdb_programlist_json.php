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

$category ="";
if(array_key_exists("category", $_REQUEST)) {
    $category = $_REQUEST["category"];
}

$header ="";
if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
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
$select_where = "";
if( !empty($header ) && !empty($category ) ) {
    if($category === 'ISNULL' ){
        $select_where = $select_where . ' WHERE found_head =' . $listerdb->quote($header) . ' AND program_category IS NULL';
    }else {
        $select_where = $select_where . ' WHERE found_head =' . $listerdb->quote($header) . ' AND program_category = '. $listerdb->quote($category);
    }
}else if( !empty($header ) ){
    $select_where = $select_where . ' WHERE found_head =' . $listerdb->quote($header);
}
if (!empty($select_orderby) ){
    $select_where = $select_where .  ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}
    $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;
    $select_limit = ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;


// 総件数のみ取得
$sql = 'SELECT COUNT(DISTINCT program_name) FROM t_found '. $select_where.';';
$alldbdata =  $lister->select($sql);
if(!$alldbdata){
     print $sql;
     die();
}
//var_dump($alldbdata);
$totalrequest = $alldbdata[0]["COUNT(DISTINCT program_name)"];
// print '<pre>';
// print $totalrequest;
// print '</pre>';



$sql = 'select DISTINCT program_name , COUNT(DISTINCT song_name) from t_found  '. $select_where.' GROUP BY program_name '.$select_limit.';';
// $sql = 'select program_name , COUNT(program_name) from t_found WHERE found_head =\'あ\' AND program_category = \'ゲーム\' GROUP BY program_name LIMIT 50 OFFSET 0 ;';
//$sql = 'select DISTINCT program_name  from t_found '. $select_where_limit.';';
$alldbdata =  $lister->select($sql);
if(!$alldbdata){
     print $sql;
     die();
}
$lister->closedb();
//var_dump($alldbdata);

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

// print '<pre>';
print $json;
// print '</pre>';

?>