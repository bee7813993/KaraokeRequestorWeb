<?php

require_once 'commonfunc.php';
require_once 'binngo_func.php';
mb_language("Japanese");

$requirement = "";
if(array_key_exists("requirement", $_REQUEST)) {
    $requirement = $_REQUEST["requirement"];
}
$toopened = 1;
if(array_key_exists("toopened", $_REQUEST)) {
    $toopened = $_REQUEST["toopened"];
}
$id = 99999;
if(array_key_exists("id", $_REQUEST)) {
    $id = $_REQUEST["id"];
}


if(empty($requirement)){
     print "解放条件が指定されていません";
     die();
}

$bingoinfo = new SongBingo();
$bingoinfo->initbingodb('songbingo.db'); 
$bingoinfo->updateopened($requirement,$toopened,$id); 

?>

