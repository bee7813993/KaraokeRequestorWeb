<?php

include 'kara_config.php';

if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}
date_default_timezone_set('Asia/Tokyo');

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

if(empty($dbname)){
  $dbname = 'data';
}

  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename='.$dbname.'.csv');
  
  $stream = fopen('php://output', 'w');
  foreach($allrequest as $row){
    mb_convert_variables ("SJIS","UTF-8",$row);
    fputcsv($stream,$row);
  }

?>