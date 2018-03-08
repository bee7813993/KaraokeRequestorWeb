<?php

$displayfrom=0;
$displaynum=80000;
$draw = 1;
$allcount = 0;
if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = $_REQUEST["start"];
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = $_REQUEST["length"];
}

if(array_key_exists("draw", $_REQUEST)) {
    $draw = $_REQUEST["draw"];
}

$select_column ="";
if(array_key_exists("column", $_REQUEST)) {
    $select_column = $_REQUEST["column"];
}

$select_word ="";
if(array_key_exists("word", $_REQUEST)) {
    $select_word = $_REQUEST["word"];
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
$listerdbfile = "List.sqlite3";
$listerdb = new PDO('sqlite:'. $listerdbfile);

// 検索条件
$select_where = "";
if( !empty($select_word ) && !empty($select_column ) ) {
    $select_where = $select_where . ' WHERE ' . $select_column . '='. $listerdb->quote($select_word);
}else if( !empty($select_word ) ){
    $select_where = $select_where . ' WHERE ' .  $listerdb->quote($select_word);
}
if (!empty($select_orderby) ){
    $select_where = $select_where .  ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}
    $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;


// 総件数のみ取得
$sql = 'SELECT COUNT(*) FROM t_found '. $select_where.';';
$select = $listerdb->query($sql);
if(!$select){
     print_r($listerdb->errorInfo());
     print $sql;
     die();
}
$alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();
if(!$alldbdata){
     print_r($db->errorInfo());
     print $sql;
     die();
}
$totalrequest = $alldbdata[0]["COUNT(*)"];
// print '<pre>';
// print $totalrequest;
// print '</pre>';



$sql = 'select * from t_found '. $select_where_limit.';';
$select = $listerdb->query($sql);
if(!$select){
     print_r($listerdb->errorInfo());
}
$alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();
if(!$alldbdata){
     print_r($db->errorInfo());
}

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

// print '<pre>';
print $json;
// print '</pre>';

?>