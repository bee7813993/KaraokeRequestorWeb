<?php

include 'kara_config.php';

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
  fwrite($stream, pack('C*',0xEF,0xBB,0xBF));//BOM書き込み
  foreach($allrequest as $row){
    fputcsv($stream, $row);
  }

?>