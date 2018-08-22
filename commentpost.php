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

$c_col = "";
if(array_key_exists("c_col", $_REQUEST)) {
    $c_col = $_REQUEST["c_col"];
}


$msg = "";
if(array_key_exists("msg", $_REQUEST)) {
    $msg = $_REQUEST["msg"];
}

$size = "";
if(array_key_exists("sz", $_REQUEST)) {
    $size = $_REQUEST["sz"];
}


// param check
if( strcmp($col, 'CUSTOM') == 0 ){
    $pos=0;
    for($i = 0; $i < strlen($c_col); $i++) {
      if($c_col[$i] == '#' ) continue;
      $col[$pos] = $c_col[$i];
      if($pos > 6 ) break;
      $pos++;
    }
}


// build POST request

if(!empty($nm) ) {
   $msg = $msg . ' by'.$nm;
}
$output=commentpost_v3($nm,$col,$size,$msg,$commenturl);


if($output === false){
    print "failed";
}

?>
