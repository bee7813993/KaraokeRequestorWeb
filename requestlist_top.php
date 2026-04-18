<?php
$config = file_exists('config.ini') ? parse_ini_file('config.ini') : [];
$useNew = isset($config['usenewrequestlist']) && $config['usenewrequestlist'] == '1';
$target = $useNew ? 'requestlist_swipe.php' : 'requestlist_only.php';

$params = [];
if (array_key_exists('easypass', $_REQUEST)) {
    $params[] = 'easypass=' . urlencode($_REQUEST['easypass']);
}
if (!empty($params)) {
    $target .= '?' . implode('&', $params);
}

header('Location: ' . $target);
exit();
