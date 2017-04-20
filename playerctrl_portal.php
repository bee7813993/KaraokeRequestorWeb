<script type="text/javascript" language="javascript">
</script>
<?php
$nomenu = 0;

if(array_key_exists("nomenu", $_REQUEST)) {
    $nomenu = $_REQUEST["nomenu"];
}

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();
$playerkind = getcurrentplayer();

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />

<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,user-scalable=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<title>プレイヤーコントローラー</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>


<?php
if($nomenu == 0 ){
    shownavigatioinbar();
    print '<button type="" value="" class="btn btn-default btn-xs"  onclick="location.href=\'playerctrl_portal.php\'" >再生中の曲用に更新</button>';
}

if( strcmp('foobar', $playerkind) === 0 ) {
    include('foobarctl.php');
}else {
    include('mpcctrl.php');
}

if(array_key_exists("autoplay_exec",$config_ini)) {
    if(!empty($config_ini["autoplay_exec"])){
        if(array_key_exists("autoplay_show",$config_ini)) {
            if($config_ini["autoplay_show"]==1){
            print '<div align="center">';
            print '<button type="button" class="btn btn-default btn-lg" onclick="location.href=\'autoplayctrl.php\'" >自動実行開始、停止ページへ</button>';
            print '</div>';
            }
        }
    }
}

?>
</body>
</html>
