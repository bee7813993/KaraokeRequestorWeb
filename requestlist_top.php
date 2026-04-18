<?php
$config = file_exists('config.ini') ? parse_ini_file('config.ini') : [];
$useNew = isset($config['usenewrequestlist']) && $config['usenewrequestlist'] == '1';
$target = $useNew ? 'requestlist_swipe.php' : 'requestlist_only.php';

$params = [];
if (array_key_exists('easypass', $_REQUEST)) {
    $params[] = 'easypass=' . urlencode($_REQUEST['easypass']);
}
if (array_key_exists('showid', $_REQUEST) && ctype_digit($_REQUEST['showid'])) {
    $params[] = 'showid=' . $_REQUEST['showid'];
}
if (array_key_exists('username', $_REQUEST)) {
    $params[] = 'username=' . urlencode($_REQUEST['username']);
}
if (!empty($params)) {
    $target .= '?' . implode('&', $params);
}

header('Location: ' . $target);
exit();
