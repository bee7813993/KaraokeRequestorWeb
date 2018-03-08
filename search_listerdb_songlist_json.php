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
if( !empty($program_name ) && !empty($category ) ) {
    if($category === 'ISNULL' ){
        $select_where = $select_where . ' WHERE program_name =' . $listerdb->quote($program_name) . ' AND program_category IS NULL';
    }else {
        $select_where = $select_where . ' WHERE program_name =' . $listerdb->quote($program_name) . ' AND program_category = '. $listerdb->quote($category);
    }
}else if( !empty($header ) ){
    $select_where = $select_where . ' WHERE found_head =' . $listerdb->quote($header);
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
//var_dump($alldbdata);
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