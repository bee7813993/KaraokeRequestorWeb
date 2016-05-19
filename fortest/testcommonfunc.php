<?php

require_once '../commonfunc.php';

$result_array = array();

$num = searchlocalfilename_part('*',$result_array,10,100);

var_dump( $num);
?>