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

$anyword = '';
if(array_key_exists("anyword", $_REQUEST)) {
    $anyword = urldecode($_REQUEST["anyword"]);
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

$song_name = "";
if(array_key_exists("song_name", $_REQUEST)) {
    $song_name = $_REQUEST["song_name"];
}

$tie_up_group_name = "";
if(array_key_exists("tie_up_group_name", $_REQUEST)) {
    $tie_up_group_name = $_REQUEST["tie_up_group_name"];
}

$uid = "";
if(array_key_exists("uid", $_REQUEST)) {
    $uid = $_REQUEST["uid"];
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



// 項目名とその読み仮名の対応表
$column_kanatable = array();
$column_kanatable['song_name'] = 'song_ruby';
$column_kanatable['song_artist'] = 'found_artist_ruby';
$column_kanatable['found_lyrist_name'] = 'found_lyrist_ruby';
$column_kanatable['found_composer_name'] = 'found_composer_ruby';
$column_kanatable['found_arranger_name'] = 'found_arranger_ruby';
$column_kanatable['program_name'] = 'tie_up_ruby';
$column_kanatable['tie_up_group_name'] = 'tie_up_group_ruby';
$column_kanatable['maker_name'] = 'maker_ruby';


function mb_strtr() {
    $args = func_get_args();
    if (!is_array($args[1])) {
        list($str, $from, $to) = $args;
        $encoding = isset($args[3]) ? $args[3] : mb_internal_encoding(); 
        $replace_pairs = array();
        $len = mb_strlen($from, $encoding);
        for ($i =0; $i < $len; $i++) {
            $k = mb_substr($from, $i, 1, $encoding);
            $v = mb_substr($to, $i, 1, $encoding);
            $replace_pairs[$k] = $v;
        }
        return $replace_pairs ? mb_strtr($str, $replace_pairs, $encoding) : $str;
    }
    list($str, $replace_pairs) = $args;
    $tmp = mb_regex_encoding();
    mb_regex_encoding(isset($args[2]) ? $args[2] : mb_internal_encoding());
    uksort($replace_pairs, function ($a, $b) {
        return strlen($b) - strlen($a);
    });
    $from = $to = array();
    foreach ($replace_pairs as $f => $t) {
        if ($f !== '') {
            $from[] = '(' . mb_ereg_replace('[.\\\\+*?\\[^$(){}|]', '\\\\0', $f) . ')';
            $to[] = $t;
        }
    }
    $pattern = implode('|', $from);
    $ret = mb_ereg_replace_callback($pattern, function ($from) use ($to) {
        foreach ($to as $i => $t) {
            if ($from[$i + 1] !== '') {
                return $t;
            }
        }
    }, $str);
    mb_regex_encoding($tmp);
    return $ret;
}

// 濁点外し＆小文字大文字化
function kanabuild ($str) {
   $from = 'ガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポァィゥェォャュョッ';
   $to   = 'カキクケコサシスセソタチツテトハヒフヘホハヒフヘホアイウエオヤユヨツ';
   
   //ひらがなをカタカナに
   $temp = mb_convert_kana($str,"C");
   //濁点、小文字をカタカナに
   $temp = mb_strtr($temp,$from,$to);
   return $temp;
}

function make_select_andsearch($db, $clm, $str){
    global $column_kanatable;
    
    $str_splitbase = mb_convert_kana($str ,'s');
    $str_list = explode(' ', $str_splitbase );
    $wherefilesearch = "";
    foreach($str_list as $searchstr){
        if(!empty($wherefilesearch) ){
            $wherefilesearch = $wherefilesearch . ' AND ';
        }
        $wherefilesearch = $wherefilesearch . ' ('. $clm .' LIKE ' .  $db->quote('%'.$searchstr.'%');
        if(!empty($column_kanatable[$clm]) ){
            $wherefilesearch = $wherefilesearch . ' OR ';
            $wherefilesearch = $wherefilesearch . ' '. $column_kanatable[$clm] .' LIKE ' .  $db->quote('%'.kanabuild($searchstr).'%');
        }
        $wherefilesearch = $wherefilesearch . ') ';
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

function add_select_cond_or($baseselect, $addselect){
    $return_select = "";
    if(empty($baseselect) ){
        $return_select = $addselect;
    }else {
        $return_select = $baseselect . ' OR ' . $addselect;
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

// なんでも検索
if( !empty($anyword ) ) {
    // 作品名
    $wherefilesearch = make_select_andsearch($listerdb,'program_name', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);
    
    // 歌手名
    $wherefilesearch = make_select_andsearch($listerdb,'song_artist', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);

    // 製作者名
    // → 不要

    // ファイル名
    $wherefilesearch = make_select_andsearch($listerdb,'found_path', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);

    // 製作会社
    $wherefilesearch = make_select_andsearch($listerdb,'maker_name', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);

    // 曲名
    if($song_name == 'isnull' ) {
        $wherefilesearch = $wherefilesearch . ' AND song_name IS NULL ';
//        $select_where = $select_where . ' AND song_name IS NULL ';
    }else {
    $wherefilesearch = make_select_andsearch($listerdb,'song_name', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);
    }

    // シリーズ
    $wherefilesearch = make_select_andsearch($listerdb,'tie_up_group_name', $anyword);
    $select_where = add_select_cond_or($select_where, $wherefilesearch);

    // isnull追加
    if($song_name == 'isnull' ) {
       $select_where = '( ' . $select_where .' ) AND song_name IS NULL ';
    }
}else {

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
//        $select_where = $select_where . ' AND '.'song_artist IS NULL ';
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
  
// 曲名で検索
  if(!empty($song_name) ){
        if ( $match === 'full' ) {
            // defaultは部分一致
            $select_where = add_select_cond($select_where,  ' song_name = ' . $listerdb->quote($song_name));
        }else {
            $wherefilesearch = make_select_andsearch($listerdb,'song_name', $song_name);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }
  }

// シリーズで検索
  if(!empty($tie_up_group_name) ){
        if ( $match === 'full' ) {
            // defaultは部分一致
            $select_where = add_select_cond($select_where,  ' tie_up_group_name = ' . $listerdb->quote($tie_up_group_name));
        }else {
            $wherefilesearch = make_select_andsearch($listerdb,'tie_up_group_name', $tie_up_group_name);
            $select_where = add_select_cond($select_where, $wherefilesearch);
        }
  }

// uidで検索
  if(!empty($uid) ){
  // uidは完全一致のみ
            $wherefilesearch = make_select_andsearch($listerdb,'found_uid', $uid);
            $select_where = add_select_cond($select_where, $wherefilesearch);
  }
} //else なんでも検索

//add groupby 
$select_where = $select_where . ' GROUP BY song_name,found_file_size ';


if (!empty($select_orderby) ){
    $select_where = $select_where .  ' ORDER BY '. $select_orderby . ' ' . $select_scending ;
}

if(!empty($select_where) ){
    $select_where = ' WHERE ' . $select_where;
}


    $select_where_limit = $select_where . ' LIMIT '. $displaynum .' OFFSET '. $displayfrom;

$sqlall = 'select * from t_found '. $select_where;
// 総件数のみ取得
//$sql = 'SELECT COUNT(*) FROM t_found '. $select_where.';';
$sql = 'SELECT COUNT(*) FROM ( '.$sqlall .' ) dummy ;';
$alldbdata = $lister->select($sql);
if($alldbdata === false){
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
if($alldbdata === false){
     print $sql;
}

$returnarray = array( "draw" => $draw, "recordsTotal" => $totalrequest,  "recordsFiltered" => $totalrequest, "data" => $alldbdata);
$json = json_encode($returnarray,JSON_PRETTY_PRINT);

// print '<pre>';
print $json;
// print '</pre>';

?>