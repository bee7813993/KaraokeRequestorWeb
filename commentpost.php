<?php
require_once 'commonfunc.php';

$nm = "";
if(array_key_exists("nm", $_REQUEST)) {
    $nm = $_REQUEST["nm"];
}

$col = "";
if(array_key_exists("col", $_REQUEST)) {
    $col = $_REQUEST["col"];
}

$msg = "";
if(array_key_exists("msg", $_REQUEST)) {
    $msg = $_REQUEST["msg"];
}

// param check (perhaps later write)

// build POST request


$output=commentpost($nm,$col,$msg,$commenturl);


if($output === false){
    print "failed";
}

?>
