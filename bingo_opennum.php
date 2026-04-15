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
    $toopened = filter_var($_REQUEST["toopened"], FILTER_VALIDATE_INT);
    if ($toopened === false || !in_array($toopened, array(0, 1), true)) {
        $toopened = 1;
    }
}
$id = 99999;
if(array_key_exists("id", $_REQUEST)) {
    $tmp = filter_var($_REQUEST["id"], FILTER_VALIDATE_INT);
    if ($tmp !== false && $tmp !== null) {
        $id = $tmp;
    }
}


if(empty($requirement)){
     print "解放条件が指定されていません";
     die();
}

$bingoinfo = new SongBingo();
$bingoinfo->initbingodb('songbingo.db'); 
$bingoinfo->updateopened($requirement,$toopened,$id); 

?>

