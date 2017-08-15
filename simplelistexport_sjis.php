<?php
/*** 仮作成 ***/
include 'kara_config.php';
//die();
function arr2csv($arr) {
    $fp = fopen('php://temp', 'r+b');
    foreach ($arr as $fields) {
        fputcsv($fp, $fields);
    }
    rewind($fp);
    $tmp = str_replace("\n", "\r\n", stream_get_contents($fp));
    return mb_convert_encoding($tmp, 'SJIS-win', 'UTF-8');
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



  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=list_'.$dbname.'.csv');
  
  $num = 1;
  $csvarray = array();
  
  /* print csv header */
  $csvarray[] = array( "順番" , "曲名（ファイル名）" ,"キー",  "歌った人" ,  "コメント" );
  
  foreach($allrequest as $row){
    $csvarray[] = array( $num, $row["songfile"] , $row["keychange"] ,$row["singer"] ,  $row["comment"] );
    $num++;
  }
  $stream = fopen('php://output', 'wb');
    // rewind($stream);
    // var_dump($csvarray);
    fwrite ($stream, arr2csv($csvarray));
  fclose($stream);

?>