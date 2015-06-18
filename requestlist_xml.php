<?php

require_once 'commonfunc.php';

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$xmlstr = "<?xml version=\"1.0\" ?><root></root>";
$xml = new SimpleXMLElement($xmlstr);


foreach($allrequest as $arr){
    $xmlitem = $xml -> addChild("item");
    foreach($arr as $key => $value){
        $xmlitem -> addChild($key, $value);
    }
}
print $xml -> asXML();


?>
