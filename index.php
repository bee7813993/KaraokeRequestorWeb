<?php
$locationurl = 'requestlist_top.php';
if (array_key_exists("easypass", $_REQUEST)) {
    $locationurl .= '?easypass=' . urlencode($_REQUEST["easypass"]);
}
header('location: ' . $locationurl);
exit();
?>
