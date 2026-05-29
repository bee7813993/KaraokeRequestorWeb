<?php
require_once 'commonfunc.php';
require_once 'function_search_listerdb.php';

$listerapi= new ListerDB();

$runmode = 0;
if(array_key_exists("start", $_REQUEST)) {
    $runmode = 1;
}

if(array_key_exists("stop", $_REQUEST)) {
    
    $runmode = 2;
}

if(array_key_exists("restart", $_REQUEST)) {
    $runmode = 3;
}

if(array_key_exists("check", $_REQUEST)) {
    $runmode = 4;
}

if(array_key_exists("start_store", $_REQUEST)) {
    $runmode = 5;
}

if(array_key_exists("start_yukkoview2", $_REQUEST)) {
    $runmode = 6;
}

switch($runmode) {
    case 1:
        // start
        $listerapi->startyklistercmd();
        break;
    case 2:
        // stop
        $listerapi->stopyklistercmd();
        break;
    case 3:
        // restart
        $listerapi->stopyklistercmd();
        sleep(4);
        $listerapi->startyklistercmd();
        break;
    case 4:
        // check
        break;
    case 5:
        // start Windows Store版ゆかりすたー
        $listerapi->startyklistercmd_store();
        break;
    case 6:
        // start Windows Store版ゆっこビュー2
        $listerapi->startYukkoView2cmd();
        break;
}

?>