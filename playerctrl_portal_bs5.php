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
<?php print_bs5_head_core(['css/themes/player.css'], ['jquery' => true]); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('playerctrl_portal_bs5.php'); ?>

<div class="container pt-3 pb-4" style="max-width:540px;">

<?php
if (strcmp('foobar', $playerkind) === 0) {
    include('foobarctl_bs5.php');
} else {
    include('mpcctrl_bs5.php');
}

if (array_key_exists('autoplay_exec', $config_ini) && !empty($config_ini['autoplay_exec'])) {
    $is_localhost = (isset($_SERVER['REMOTE_ADDR']) &&
        ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1'));
    $is_admin = ($user === 'admin');
    $autoplay_show_enabled = (array_key_exists('autoplay_show', $config_ini) && $config_ini['autoplay_show'] == 1);

    if ($is_admin || $is_localhost || $autoplay_show_enabled) {
        echo '<div class="d-grid mt-2">';
        echo '<a href="autoplayctrl.php" class="btn btn-secondary btn-lg">自動実行開始・停止ページへ</a>';
        echo '</div>';
    }
}
?>

</div><!-- /container -->
</body>
</html>
