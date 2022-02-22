<?php
/*** 仮作成 ***/
require_once('commonfunc.php');
require_once('function_search_listerdb.php');
//die();
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



function getsonginfofromfilename($filename){
  global $config_ini;

  if(empty($filename)) return false;
  // ListerDBのファイルの設定があるかどうかのチェック
  $res = array_key_exists("listerDBPATH",$config_ini);
  if ($res === false ) {
     print( "config not found");
     return false;
  }
  $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
  // ListerDBのファイルのがあるかどうかのチェック
  if(!file_exists($lister_dbpath) ){
     print( "Listerdb file :". $lister_dbpath." not found");
     return false;
  }

  // DB初期化
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
//     print $sql;
         return false;
      }

}
return $songdbdata;
}

// 1つでもlisterDBに登録情報があるかどうかのチェック
function listerdbfoundcheck($alldata){
   foreach($alldata as $row){
     $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
     // if( $songdataarray_all === false ) return false;
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

  if($listerdbenabled && listerdbfoundcheck($allrequest) ){
// りすたーDBに登録された情報が1つでもある
    $csvarray[] = array( "順番" , "曲名（ファイル名）" ,"キー", "作品名" ,"歌手名" , "歌った人" ,  "コメント" );
    foreach($allrequest as $row){
    $songdataarray = array();
    $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
    if(isset($songdataarray_all[0])) $songdataarray = $songdataarray_all[0];
    if(!empty($songdataarray["song_name"] ) ){
        $songname=$songdataarray["song_name"] ;
        if(!empty($songdataarray["found_comment"] ) ){
            $showcomment=preg_replace('#//\.\+\$#', "",$songdataarray["found_comment"]);
            $songname=$songname.'【'.$showcomment.'】' ;
        }
    }else{
        $songname=$row["songfile"];
    }
    
    $program_name='';
    if(!empty($songdataarray["program_name"] ) ){
      if( $songdataarray["program_name"] == "その他" ) {
        $program_name='-';
      } else {
        $program_name=$songdataarray["program_name"];
        if(!empty($songdataarray["song_op_ed"] ) ){
          $program_name=$program_name.' '.$songdataarray["song_op_ed"];
        }
      }
    }
    
    $artist = '';
    if(!empty($songdataarray["song_artist"] ) ){
        $artist = $songdataarray["song_artist"];
    }
    

      $csvarray[] = array( $num, $songname , $row["keychange"],$program_name,$artist ,$row["singer"] ,  $row["comment"] );
      $num++;
    }
      

  }else {
  
    $num = 1;
    /* print csv header */
    $csvarray[] = array( "順番" , "曲名（ファイル名）" ,"キー",  "歌った人" ,  "コメント" );
    
    foreach($allrequest as $row){
      $csvarray[] = array( $num, $row["songfile"] , $row["keychange"] ,$row["singer"] ,  $row["comment"] );
      $num++;
    }
  }
  $stream = fopen('php://output', 'wb');
  fwrite($stream, pack('C*',0xEF,0xBB,0xBF));//BOM書き込み
    // rewind($stream);
    // var_dump($csvarray);
    fwrite ($stream, arr2csv($csvarray));
  fclose($stream);

?>