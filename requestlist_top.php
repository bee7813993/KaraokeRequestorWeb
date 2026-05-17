<?php
header('Pragma: no-cache');
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');

$config = [];
if (file_exists('config.ini')) {
    $config = @parse_ini_file('config.ini');
    if ($config === false) {
        $config = [];
    }
}
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
