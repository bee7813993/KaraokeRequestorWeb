<?php
$configfile = 'config.ini';
$config = file_exists($configfile) ? parse_ini_file($configfile) : [];

$locationurl = (isset($config['usenewrequestlist']) && $config['usenewrequestlist'] == '1')
    ? 'requestlist_swipe.php'
    : 'requestlist_only.php';

if (array_key_exists("easypass", $_REQUEST)) {
    $locationurl .= '?easypass=' . urlencode($_REQUEST["easypass"]);
}
header('location: ' . $locationurl);
exit();
?>
