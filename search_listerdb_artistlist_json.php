<?php
require_once 'commonfunc.php';
require_once('function_search_listerdb.php');

$displayfrom=0;
$displaynum=80000;
$draw = 1;
$allcount = 0;


$lister_dbpath = 'list\List.sqlite3';
if(array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = (int)$_REQUEST["start"];
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = (int)$_REQUEST["length"];
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

if(array_key_exists("program_name", $_REQUEST)) {
    $program_name = $_REQUEST["program_name"];
}

$filename = '*';
if(array_key_exists("filename", $_REQUEST)) {
    $filename = urldecode($_REQUEST["filename"]);
}

$artist = "";
if(array_key_exists("artist", $_REQUEST)) {
    $artist = $_REQUEST["artist"];
}

$match = "";
if(array_key_exists("match", $_REQUEST)) {
    $match = $_REQUEST["match"];
}



$select_orderby ="";
$select_scending ="";



// DB初期化
$lister = new ListerDB();
$lister->listerdbfile = $lister_dbpath;
$listerdb = $lister->initdb();
if( !$listerdb ) {
    die();
}

// 検索条件
$select_where = "";

if ( $match === 'full' ) {
    if( $artist === '*'  ){
        $select_where = $select_where ;
    }else if( empty($artist) ){
        $select_where = $select_where ;
    }else {
        $select_where = $select_where . ' WHERE song_artist = ' . $listerdb->quote($artist);
    }
} else {
    if( $artist === '*'  ){
        $select_where = $select_where ;
    }else if( empty($artist) ){
        $select_where = $select_where ;
    }else {
        $select_where = $select_where . ' WHERE song_artist LIKE ' . $listerdb->quote('%'.$artist.'%');
    }
}
if (!empty($select_orderby) ){
    $select_where = $select_where .  ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}
    $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;

// 総件数のみ取得
$sql = 'SELECT COUNT(DISTINCT song_artist) FROM t_found '. $select_where.';';
$alldbdata = $lister->select($sql);
if(!$alldbdata){
     print $sql;
     die();
}
//var_dump($alldbdata);
$totalrequest = $alldbdata[0]["COUNT(DISTINCT song_artist)"];
// print '<pre>';
// print $totalrequest;
// print '</pre>';



$sql = 'select DISTINCT song_artist from t_found '. $select_where_limit.';';
$alldbdata = $lister->select($sql);
if(!$alldbdata){
     print $sql;
}

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

// print '<pre>';
print $json;
// print '</pre>';

?>