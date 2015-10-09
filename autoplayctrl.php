<?php

require_once 'kara_config.php';

function stopautoplay()
{
    $execcmd='taskkill /FI "WINDOWTITLE eq karaokeautorun*"';
    exec($execcmd);
}

function startautoplay()
{
    $execcmd='start "karaokeautorun" autoplaystart_mpc_xampp.bat';
    exec($execcmd);

}

function checkautoplay()
{
  $pscheck_cmd='tasklist /FI "WINDOWTITLE eq karaokeautorun*" | find /c "cmd.exe"';
  exec($pscheck_cmd, $psresult );
  return $psresult[0];
}

function stopautoplaywithcheck()
{
    $result=checkautoplay();
    if($result != 0 ){
        stopautoplay();
    }
}

$l_karaokeautorunaction = 'none';
if(array_key_exists("karaokeautorunaction", $_REQUEST)) {
    $l_karaokeautorunaction = $_REQUEST["karaokeautorunaction"];
}

$l_nextpage = null;
if(array_key_exists("nextpage", $_REQUEST)) {
    $l_nextpage = $_REQUEST["nextpage"];
}

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
if(!empty($l_nextpage)){
    print '<META http-equiv="refresh" content="1; url='.$l_nextpage.'">'."\n";
    }
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
    
<title>自動起動プログラム制御</title>
</head>
<body>
<?php

if($l_karaokeautorunaction == 'start'){
    $org_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 1);
    @file_get_contents('http://localhost/autoplayctrl.php?karaokeautorunaction=start_exec');
    ini_set('default_socket_timeout', $org_timeout);
    
}
if($l_karaokeautorunaction == 'start_exec'){
    startautoplay();
}
if($l_karaokeautorunaction == 'stop'){
    stopautoplaywithcheck();
}

$ap = checkautoplay();
//var_dump ($ap);
if($ap == 0){
    print '自動再生停止中<br>';
}else{
    print '自動再生実行中<br>';
}


?>

<form method="GET" >
<input type="hidden" name="karaokeautorunaction" id="karaokeautorunaction"  value="start" />
<input type="submit" value="Start" class="requestconfirm btn btn-default btn-lg"/>
</form>

<form method="GET" >
<input type="hidden" name="karaokeautorunaction" id="karaokeautorunaction"  value="stop" />
<input type="submit" value="Stop" class="requestconfirm btn btn-default btn-lg"/>
</form>

</body>
</html>

