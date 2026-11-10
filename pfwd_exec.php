<?php

require_once 'commonfunc.php';
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

// pfwd (オンライン公開の転送) の起動・停止は、設定画面 (pfwd_settings.php) と同じ管理者認証を通す
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'admin' || !$configauth->check_auth($_SERVER['PHP_AUTH_PW'] ?? '')) {
    header('WWW-Authenticate: Basic realm="pfwd Settings"');
    die('管理者ログインが必要です');
}

require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

require_once 'pfwdctl.php';

$pfwdinfo = new pfwd();
if(array_key_exists("pfwdplace",$config_ini)) {
    $pfwdinfo->pfwdpath = urldecode($config_ini["pfwdplace"]);
} else {
    die();
}
ob_start();
$pfwdinfo->readpfwdcfg();
ob_end_clean();

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
// 接続先ホストやポートの変更は pfwd_settings.php で行う


?>
