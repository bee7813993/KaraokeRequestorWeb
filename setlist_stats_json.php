<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'function_setlist_stats.php';

$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$data = setlist_stats_get_data($force_refresh);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
