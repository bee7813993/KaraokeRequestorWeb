<?php
require_once('commonfunc.php');
require_once('function_search_listerdb.php');

function arr2csv($arr) {
    $fp = fopen('php://temp', 'r+b');
    foreach ($arr as $fields) {
        fputcsv($fp, $fields);
    }
    rewind($fp);
    $tmp = str_replace("\n", "\r\n", stream_get_contents($fp));
    return $tmp;
}

if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}
date_default_timezone_set('Asia/Tokyo');

$sql = "SELECT * FROM requesttable ORDER BY reqorder ASC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

if(empty($dbname)){
  $dbname = 'data';
}

// 列定義（id => ヘッダーラベル）
$all_columns_def = [
    'num'          => '順番',
    'songfile'     => '曲名（ファイル名）',
    'keychange'    => 'キー',
    'program_name' => '作品名',
    'artist'       => '歌手名',
    'singer'       => '歌った人',
    'comment'      => 'コメント',
    'worker'       => '動画制作者',
];

// 列設定ファイル読み込み
function load_export_columns($config_file, $all_columns_def) {
    if (file_exists($config_file)) {
        $json = file_get_contents($config_file);
        $saved = json_decode($json, true);
        if ($saved !== null) {
            $active = [];
            foreach ($saved as $col) {
                if (!empty($col['enabled']) && isset($all_columns_def[$col['id']])) {
                    $active[] = $col['id'];
                }
            }
            if (!empty($active)) return $active;
        }
    }
    return array_keys($all_columns_def);
}

$active_columns = load_export_columns('csv_export_columns.json', $all_columns_def);

function getsonginfofromfilename($filename){
  global $config_ini;

  if(empty($filename)) return false;
  $res = array_key_exists("listerDBPATH",$config_ini);
  if ($res === false ) {
     print( "config not found");
     return false;
  }
  $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
  if(!file_exists($lister_dbpath) ){
     print( "Listerdb file :". $lister_dbpath." not found");
     return false;
  }

  $lister = new ListerDB();
  $lister->listerdbfile = $lister_dbpath;
  $listerdb = $lister->initdb();
  if( !$listerdb ) {
       return false;
  }

  $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.$filename.'%');
  $sql = 'SELECT * FROM t_found '. $select_where.';';
  @$songdbdata = $lister->select($sql);
  if(!$songdbdata){
      $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.basename($filename).'%');
      $sql = 'SELECT * FROM t_found '. $select_where.';';
      @$songdbdata = $lister->select($sql);
      if(!$songdbdata){
         return false;
      }
  }
  return $songdbdata;
}

function listerdbfoundcheck($alldata){
   foreach($alldata as $row){
     $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
     if( $songdataarray_all === false ) continue;
     $songdataarray = $songdataarray_all[0];
     if(!empty($songdataarray["song_name"]) ) {
       return true;
     }
   }
   return false;
}

$listerdbenabled = false;
if(array_key_exists("listerDBPATH",$config_ini) ) {
    $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
    if(file_exists($lister_dbpath) ){
        $listerdbenabled = true;
    }
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=list_'.$dbname.'.csv');

$num = 1;
$csvarray = array();

// ヘッダー行
$header = [];
foreach ($active_columns as $col_id) {
    $header[] = $all_columns_def[$col_id];
}
$csvarray[] = $header;

$use_listerdb = $listerdbenabled && listerdbfoundcheck($allrequest);

foreach ($allrequest as $row) {
    // ListerDB情報の取得
    $songdataarray = [];
    if ($use_listerdb) {
        $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
        if (isset($songdataarray_all[0])) $songdataarray = $songdataarray_all[0];
    }

    // 曲名
    if (!empty($songdataarray["song_name"])) {
        $songname = $songdataarray["song_name"];
        if (!empty($songdataarray["found_comment"])) {
            $showcomment = preg_replace('/\,\/\/.*/', "", $songdataarray["found_comment"]);
            if (!empty($showcomment))
                $songname = $songname . '【' . $showcomment . '】';
        }
    } else {
        $songname = $row["songfile"];
    }

    // 作品名
    $program_name = '';
    if (!empty($songdataarray["program_name"])) {
        if ($songdataarray["program_name"] == "その他") {
            $program_name = '-';
        } else {
            $program_name = $songdataarray["program_name"];
            if (!empty($songdataarray["song_op_ed"])) {
                $program_name = $program_name . ' ' . $songdataarray["song_op_ed"];
            }
        }
    }

    // 歌手名
    $artist = '';
    if (!empty($songdataarray["song_artist"])) {
        $artist = $songdataarray["song_artist"];
    }

    // 動画制作者
    $worker = '';
    if (!empty($songdataarray["found_worker"])) {
        $worker = $songdataarray["found_worker"];
    }

    // 列設定に従って行データを生成
    $row_data = [];
    foreach ($active_columns as $col_id) {
        switch ($col_id) {
            case 'num':          $row_data[] = $num; break;
            case 'songfile':     $row_data[] = $songname; break;
            case 'keychange':    $row_data[] = $row["keychange"]; break;
            case 'program_name': $row_data[] = $program_name; break;
            case 'artist':       $row_data[] = $artist; break;
            case 'singer':       $row_data[] = $row["singer"]; break;
            case 'comment':      $row_data[] = $row["comment"]; break;
            case 'worker':       $row_data[] = $worker; break;
        }
    }
    $csvarray[] = $row_data;
    $num++;
}

$stream = fopen('php://output', 'wb');
fwrite($stream, pack('C*', 0xEF, 0xBB, 0xBF)); // BOM
fwrite($stream, arr2csv($csvarray));
fclose($stream);
?>
