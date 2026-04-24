<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';

if (!configbool("usemypage", true)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$mypage = new MypageUser($db);
$data   = $mypage->exportData();
$json   = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$fname  = 'mypage_export_' . date('Ymd_His') . '.json';

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Content-Length: ' . strlen($json));
header('Cache-Control: no-cache');
echo $json;
