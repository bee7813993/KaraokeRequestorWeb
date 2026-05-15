<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

$selectid = '';
if (array_key_exists("id", $_REQUEST)) $selectid = $_REQUEST["id"];
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>検索＆予約TOP</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<div class="container py-4">
<?php
showmode();
echo build_reservation_tabs($selectid, '');
?>
</div>
</body>
</html>
