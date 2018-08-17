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

$filename = '';
if(array_key_exists("filename", $_REQUEST)) {
    $filename = urldecode($_REQUEST["filename"]);
}

if(array_key_exists("program_name", $_REQUEST)) {
    $program_name = $_REQUEST["program_name"];
}

$artist = "";
if(array_key_exists("artist", $_REQUEST)) {
    $artist = $_REQUEST["artist"];
}

$worker = "";
if(array_key_exists("worker", $_REQUEST)) {
    $worker = $_REQUEST["worker"];
}

$datestart = "";
if(array_key_exists("datestart", $_REQUEST)) {
    $datestart = $_REQUEST["datestart"];
    $startdate_u =  strtotime($datestart);
    $datestart = unixtojd($startdate_u);
    $datestart -= 2400000.5; // 修正ユリウス日化
}

$dateend = "";
if(array_key_exists("dateend", $_REQUEST)) {
    $dateend = $_REQUEST["dateend"];
    $startdate_u =  strtotime($dateend);
    $dateend = unixtojd($startdate_u);
    $dateend -= 2400000.5; // 修正ユリウス日化
}

$maker_name = "";
if(array_key_exists("maker_name", $_REQUEST)) {
    $maker_name = $_REQUEST["maker_name"];
}

$tie_up_group_name = "";
if(array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
}


$select_orderby ="";
if(array_key_exists("orderby", $_REQUEST)) {
    $select_orderby = $_REQUEST["orderby"];
}

$select_scending = 'ASC';
$select_scending ="";
if(array_key_exists("scending", $_REQUEST)) {
    if( strcasecmp($_REQUEST["scending"]  , "DESC" ) == 0){
        $select_scending = $_REQUEST["scending"];
    }
}

$match = "";
if(array_key_exists("match", $_REQUEST)) {
    $match = $_REQUEST["match"];
}

function make_select_andsearch($db, $clm, $str){
    $str_splitbase = mb_convert_kana($str ,'s');
    $str_list = explode(' ', $str_splitbase );
    $wherefilesearch = "";
    foreach($str_list as $searchstr){
        if(!empty($wherefilesearch) ){
            $wherefilesearch = $wherefilesearch . ' AND ';
        }
        $wherefilesearch = $wherefilesearch . ' '. $clm .' LIKE ' .  $db->quote('%'.$searchstr.'%');
    }
    return $wherefilesearch;
}

function add_select_cond($baseselect, $addselect){
    $return_select = "";
    if(empty($baseselect) ){
        $return_select = $addselect;
    }else {
        $return_select = $baseselect . ' AND ' . $addselect;
    }
    return $return_select;
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

// 作品名とカテゴリ名で検索
if( !empty($category ) ) {
    if($category === 'ISNULL' ){
        $select_where = add_select_cond($select_where,  ' program_category IS NULL');
//        $select_where = $select_where . ' program_category IS NULL';
    }else {
        $select_where = add_select_cond($select_where,  ' program_category = '. $listerdb->quote($category));
//        $select_where = $select_where . ' program_category = '. $listerdb->quote($category);
    }
// 作品名で検索
}
if( !empty($program_name ) ){
    if($program_name === 'ISNULL' ){
        $select_where = add_select_cond($select_where,  ' program_name IS NULL ');
    }else {
        if ( $match === 'part' ) {
            $wherefilesearch = make_select_andsearch($listerdb,'program_name', $program_name);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        } else {
            $select_where = add_select_cond($select_where,  ' program_name = ' . $listerdb->quote($program_name));
        }
    }
// 作品名headerで検索
}
if( !empty($header ) ){
    // header 検索
    $select_where = add_select_cond($select_where,  ' found_head = ' . $listerdb->quote($header));
// 歌手名で検索
}
if( !empty($artist ) ){
    // artist 検索
    if($artist === 'ISNULL' ){
        $select_where = add_select_cond($select_where, ' song_artist IS NULL');
//        $select_where = $select_where . ' song_artist IS NULL';
    } else {
        if ( $match === 'part' ) {
            $wherefilesearch = make_select_andsearch($listerdb,'song_artist', $artist);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }else {
            // defaultは完全一致
            $select_where = add_select_cond($select_where, ' song_artist = '.$listerdb->quote($artist));
        }
    }
}
// 製作者名で検索
  if(!empty($worker) ){
        if ( $match === 'part' ) {
            $wherefilesearch = make_select_andsearch($listerdb,'found_worker', $worker);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }else {
            // defaultは完全一致
            $select_where = add_select_cond($select_where,  ' found_worker = ' . $listerdb->quote($worker));
        }
  }
// ファイル名で検索
  if(!empty($filename) ){
        if ( $match === 'full' ) {
            // defaultは部分一致
            $select_where = add_select_cond($select_where,  ' found_path = ' . $listerdb->quote($filename));
        }else {
            $wherefilesearch = make_select_andsearch($listerdb,'found_path', $filename);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }
  }
// 更新日で検索
  if(!empty($datestart) ){
      $select_where = add_select_cond($select_where,  ' found_last_write_time >= ' . $listerdb->quote($datestart));
  }
  if(!empty($dateend) ){
      $select_where = add_select_cond($select_where,  ' found_last_write_time <= ' . $listerdb->quote($dateend));
  }

// 製作会社で検索
  if(!empty($maker_name) ){
        if ( $match === 'full' ) {
            // defaultは部分一致
            $select_where = add_select_cond($select_where,  ' maker_name = ' . $listerdb->quote($maker_name));
        }else {
            $wherefilesearch = make_select_andsearch($listerdb,'maker_name', $maker_name);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }
  }

// 製作会社で検索
  if(!empty($tie_up_group_name) ){
        if ( $match === 'full' ) {
            // defaultは部分一致
            $select_where = add_select_cond($select_where,  ' tie_up_group_name = ' . $listerdb->quote($tie_up_group_name));
        }else {
            $wherefilesearch = make_select_andsearch($listerdb,'tie_up_group_name', $tie_up_group_name);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }
  }



if (!empty($select_orderby) ){
    $select_where = $select_where .  ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}

if(!empty($select_where) ){
    $select_where = ' WHERE ' . $select_where;
}


    $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;


// 総件数のみ取得
$sql = 'SELECT COUNT(*) FROM t_found '. $select_where.';';
$alldbdata = $lister->select($sql);
if(!$alldbdata){
     print $sql;
     die();
}
//var_dump($alldbdata);
$totalrequest = $alldbdata[0]["COUNT(*)"];
if($totalrequest == 0 ){
    $returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
    $json = json_encode($returnarray,JSON_PRETTY_PRINT);
    print $json;
    die();
}
// print '<pre>';
// print $totalrequest;
// print '</pre>';



$sql = 'select * from t_found '. $select_where_limit.';';
// print $sql;
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