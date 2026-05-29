<?php
require_once 'commonfunc.php';
require_once 'function_search_listerdb.php';

header('Content-Type: application/json; charset=utf-8');

$listerapi= new ListerDB();

// デバッグモード: yklister_exec.php?debug=1 でステップごとの動作確認
if (array_key_exists("debug", $_REQUEST)) {
    $steps = [];

    // Step 1: exec() が使えるか
    $steps['exec_enabled'] = function_exists('exec') ? 'yes' : 'no (disable_functions)';

    // Step 2: 簡単なコマンド（echoのみ）
    if (function_exists('exec')) {
        $out = []; $ret = -1;
        exec('cmd /c echo hello 2>&1', $out, $ret);
        $steps['cmd_echo'] = ['ret' => $ret, 'out' => $out];
    }

    // Step 3: PowerShell バージョン確認（Get-AppxPackage より軽い）
    if (function_exists('exec')) {
        $out = []; $ret = -1;
        exec('powershell -NoProfile -NonInteractive -Command "$PSVersionTable.PSVersion.Major" 2>&1', $out, $ret);
        $steps['ps_version'] = ['ret' => $ret, 'out' => $out];
    }

    // Step 4: Get-AppxPackage（YukaLister）
    if (function_exists('exec')) {
        $out = []; $ret = -1;
        exec('powershell -NoProfile -NonInteractive -Command "(Get-AppxPackage -Name \'*YukaLister*\' -ErrorAction SilentlyContinue | Select-Object -First 1).PackageFamilyName" 2>&1', $out, $ret);
        $steps['pkg_yukalister'] = ['ret' => $ret, 'out' => $out];
    }

    // Step 5: Get-AppxPackage（YukkoView）
    if (function_exists('exec')) {
        $out = []; $ret = -1;
        exec('powershell -NoProfile -NonInteractive -Command "(Get-AppxPackage -Name \'*YukkoView*\' -ErrorAction SilentlyContinue | Select-Object -First 1).PackageFamilyName" 2>&1', $out, $ret);
        $steps['pkg_yukkoview'] = ['ret' => $ret, 'out' => $out];
    }

    echo json_encode(['debug' => true, 'steps' => $steps], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

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
