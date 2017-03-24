<?php

require_once 'commonfunc.php';
require_once 'pfwdctl.php';


$pfwdinfo = new pfwd();
$pfwdinfo->readpfwdcfg();
if(array_key_exists("pfwdplace",$config_ini)) {
    $pfwdinfo->pfwdpath=urldecode($config_ini["pfwdplace"]);
}else {
    die();
}

$runmode = 0;
if(array_key_exists("pfwdstart", $_REQUEST)) {
    $runmode = 1;
}

if(array_key_exists("pfwdstop", $_REQUEST)) {
    
    $runmode = 2;
}

if(array_key_exists("pfwdrestart", $_REQUEST)) {
    $runmode = 3;
}

if(array_key_exists("pfwdcheck", $_REQUEST)) {
    $runmode = 4;
}
switch($runmode) {
    case 1:
        // start
        $pfwdinfo->startpfwdcmd();
        break;
    case 2:
        // stop
        $pfwdinfo->stoppfwdcmd();
        break;
    case 3:
        // restart
        $pfwdinfo->stoppfwdcmd();
        sleep(4);
        $pfwdinfo->startpfwdcmd();
        break;
    case 4:
        // check
        break;
}

if(array_key_exists("pfwdserverhost", $_REQUEST)) {
    $pfwdserverhostport = $_REQUEST["pfwdserverhost"];
    $hostport = explode(":",$pfwdserverhostport);
    if($hostport !== FALSE){
        if(!empty($hostport[0])){
            $pfwdinfo->set_pfwdhost($hostport[0]);
        }
        $serverport = 22;
        if(!empty($hostport[1])){
            $serverport = $hostport[1];
        }
        $pfwdinfo->set_pfwdport($serverport);
        $pfwdinfo->save_pfwdconfig($pfwdinfo->pfwdpath.'\\pfwd.ini');
    }
}

if(array_key_exists("pfwdserveropenport", $_REQUEST)) {
    $pfwdserveropenport = $_REQUEST["pfwdserveropenport"];
    if(!empty($pfwdserveropenport)){
        $pfwdinfo->set_pfwdopenport($pfwdserveropenport);
        $pfwdinfo->save_pfwdconfig($pfwdinfo->pfwdpath.'\\pfwd.ini');
    }
}


?>
