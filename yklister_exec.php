<?php
require_once 'commonfunc.php';
require_once 'function_search_listerdb.php';

header('Content-Type: application/json; charset=utf-8');

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

$result = ['ok' => true, 'msg' => ''];

switch($runmode) {
    case 1:
        $listerapi->startyklistercmd();
        break;
    case 2:
        $listerapi->stopyklistercmd();
        break;
    case 3:
        $listerapi->stopyklistercmd();
        sleep(4);
        $listerapi->startyklistercmd();
        break;
    case 4:
        break;
    case 5:
        $result = $listerapi->startyklistercmd_store();
        break;
    case 6:
        $result = $listerapi->startYukkoView2cmd();
        break;
    default:
        $result = ['ok' => false, 'msg' => '不明なコマンドです'];
        break;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
