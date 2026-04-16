<?php
require_once 'commonfunc.php';

$displayfrom = 0;
$displaynum  = 80000;
$draw = 1;

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
    'song_name', 'song_ruby', 'song_artist', 'found_artist_ruby',
    'found_lyrist_name', 'found_lyrist_ruby',
    'found_composer_name', 'found_composer_ruby',
    'found_arranger_name', 'found_arranger_ruby',
    'program_name', 'tie_up_ruby',
    'tie_up_group_name', 'tie_up_group_ruby',
    'maker_name', 'maker_ruby',
    'found_head', 'program_category',
    'found_file_size', 'found_last_write_time', 'found_filename',
);

$select_column = "";
if(array_key_exists("column", $_REQUEST) && in_array($_REQUEST["column"], $valid_columns)) {
    $select_column = $_REQUEST["column"];
}

$select_word = "";
if(array_key_exists("word", $_REQUEST)) {
    $select_word = $_REQUEST["word"];
}

$valid_orderby = array(
    'song_name', 'song_artist', 'found_artist_ruby',
    'program_name', 'maker_name',
    'found_file_size', 'found_last_write_time', 'found_filename',
);
$select_orderby = "";
if(array_key_exists("orderby", $_REQUEST) && in_array($_REQUEST["orderby"], $valid_orderby)) {
    $select_orderby = $_REQUEST["orderby"];
}

$select_scending = "";
if(array_key_exists("scending", $_REQUEST) && in_array(strtoupper($_REQUEST["scending"]), array('ASC', 'DESC'))) {
    $select_scending = strtoupper($_REQUEST["scending"]);
}


// DB初期化
$listerdbfile = "List.sqlite3";
if(array_key_exists("listerDBPATH", $config_ini)) {
    $listerdbfile = urldecode($config_ini['listerDBPATH']);
}
$listerdb = new PDO('sqlite:'. $listerdbfile);

// 検索条件
$select_where = "";
if(!empty($select_word) && !empty($select_column)) {
    $select_where = ' WHERE ' . $select_column . ' = ' . $listerdb->quote($select_word);
}
if(!empty($select_orderby)) {
    $select_where = $select_where . ' ORDER BY ' . $select_orderby . ' ' . $select_scending;
}
$select_where_limit = $select_where . ' LIMIT ' . $displaynum . ' OFFSET ' . $displayfrom;


// 総件数のみ取得
$sql = 'SELECT COUNT(*) FROM t_found ' . $select_where . ';';
$select = $listerdb->query($sql);
if(!$select){
     die();
}
$alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();
if(!$alldbdata){
     die();
}
$totalrequest = $alldbdata[0]["COUNT(*)"];


$sql = 'SELECT * FROM t_found ' . $select_where_limit . ';';
$select = $listerdb->query($sql);
if(!$select){
     die();
}
$alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$returnarray = array("draw" => $draw, "recordsTotal" => $totalrequest, "recordsFiltered" => $totalrequest, "data" => $alldbdata);
print json_encode($returnarray, JSON_PRETTY_PRINT);

?>
