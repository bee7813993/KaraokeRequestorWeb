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
$useV2 = isset($config['usenewrequestlist']) && $config['usenewrequestlist'] == '1';
$target = $useV2 ? 'playerctrl_portal_bs5.php' : 'playerctrl_portal.php';

$params = [];
if (array_key_exists('easypass', $_REQUEST)) {
    $params[] = 'easypass=' . urlencode($_REQUEST['easypass']);
}
if (!empty($params)) {
    $target .= '?' . implode('&', $params);
}

header('Location: ' . $target);
exit();
