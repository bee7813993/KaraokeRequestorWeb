<?php
require_once 'commonfunc.php';
require_once('function_search_listerdb.php');
require_once('search_listerdb_commonfunc.php');

$displayfrom = 0;
$displaynum  = 80000;
$draw = 1;

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
    $draw = (int)$_REQUEST["draw"];
}

$valid_columns = array(
    'maker_name', 'tie_up_group_name', 'program_name',
    'maker_ruby', 'found_artist_ruby', 'song_ruby', 'tie_up_group_ruby',
    'substr(maker_ruby, 1, 1)', 'substr(found_artist_ruby, 1, 1)',
    'substr(song_ruby, 1, 1)', 'substr(tie_up_group_ruby, 1, 1)',
);
$column = "";
if(array_key_exists("column", $_REQUEST)) {
    $column = $_REQUEST["column"];
}
if(!in_array($column, $valid_columns)) {
    http_response_code(400);
    die();
}

$header = "";
if(array_key_exists("header", $_REQUEST)) {
    $header = $_REQUEST["header"];
}

$maker_name = "";
if(array_key_exists("maker_name", $_REQUEST)) {
    $maker_name = $_REQUEST["maker_name"];
}

$tie_up_group_name = "";
if(array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
}

// DB初期化
$lister = new ListerDB();
$lister->listerdbfile = $lister_dbpath;
$listerdb = $lister->initdb();
if( !$listerdb ) {
    die();
}

// 検索条件を構造化パラメータから構築
$where_conditions = array();
if(!empty($header)) {
    $where_conditions[] = $column . ' LIKE ' . $listerdb->quote($header . '%');
}
if(!empty($maker_name)) {
    $where_conditions[] = 'maker_name = ' . $listerdb->quote($maker_name);
}
if(!empty($tie_up_group_name)) {
    $where_conditions[] = '(tie_up_group_ruby LIKE ' . $listerdb->quote('%'.kanabuild($tie_up_group_name).'%') .
                          ' OR tie_up_group_name LIKE ' . $listerdb->quote('%'.$tie_up_group_name.'%') . ')';
}
$select_where = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
$select_where_limit = $select_where . ' GROUP BY '.$column. ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;

// 総件数のみ取得
$countword = 'COUNT(DISTINCT  '.$column.')';
$sql = 'SELECT '.$countword.' FROM t_found '. $select_where.';';
$alldbdata = $lister->select($sql);
if($alldbdata === false){
    die();
}
$totalrequest = $alldbdata[0][$countword];

$sql = 'select '.$column.',COUNT(DISTINCT song_name) from t_found '. $select_where_limit.';';
$alldbdata = $lister->select($sql);
if($alldbdata === false){
    die();
}

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

print $json;

?>
