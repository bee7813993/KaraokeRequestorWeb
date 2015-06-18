<?php

require_once 'commonfunc.php';

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$json = json_encode($allrequest,JSON_PRETTY_PRINT);

print $json;


?>
