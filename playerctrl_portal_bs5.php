<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();
$playerkind = getcurrentplayer();
?>
<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php
if (!empty($config_ini['roomurl'])) {
    $roomnames = array_keys($config_ini['roomurl']);
    echo htmlspecialchars($roomnames[0], ENT_QUOTES, 'UTF-8') . '：';
}
?>プレイヤーコントローラー</title>
<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">
<link rel="stylesheet" href="css/themes/_variables.css">
<link rel="stylesheet" href="css/themes/player.css">
<script src="js/jquery.js"></script>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php shownavigatioinbar_bs5('playerctrl_portal_bs5.php'); ?>

<div class="container" style="max-width:540px; padding-top:16px; padding-bottom:32px;">

<?php
if (strcmp('foobar', $playerkind) === 0) {
    include('foobarctl_bs5.php');
} else {
    include('mpcctrl_bs5.php');
}

if (
    array_key_exists('autoplay_exec', $config_ini) && !empty($config_ini['autoplay_exec']) &&
    array_key_exists('autoplay_show', $config_ini) && $config_ini['autoplay_show'] == 1
) {
    echo '<div class="d-grid mt-2">';
    echo '<a href="autoplayctrl.php" class="btn btn-outline-secondary btn-lg">自動実行開始・停止ページへ</a>';
    echo '</div>';
}
?>

</div><!-- /container -->
</body>
</html>
