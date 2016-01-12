<html>
<head>
<?php 
require_once 'commonfunc.php';
print_meta_header();
?>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>検索予約ツール接続情報</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
// 処理記述部

$change_counter = 0;
if(array_key_exists("SSID", $_REQUEST)) {
    $SSID = urlencode($_REQUEST["SSID"]);
    $config_ini = array_merge($config_ini,array("SSID" => $SSID));
    $change_counter ++;
}

if(array_key_exists("wifipass", $_REQUEST)) {
    $wifipass = urlencode($_REQUEST["wifipass"]);
    $config_ini = array_merge($config_ini,array("wifipass" => $wifipass));
    $change_counter ++;
}

if(  $change_counter > 0 ){
    writeconfig2ini($config_ini,$configfile);
}

?>


<div class="container">
 <form method="GET" >
 <div class="form-group">
  <label>接続用WiFI SSID</label>
  <div class="row">
   <div class="col-xs-10">
    <input type="text" name="SSID" size="100" class="form-control input-lg toolinfo" 
<?php
require_once 'commonfunc.php';
if(array_key_exists("SSID", $config_ini)){
   print 'value="'.urldecode($config_ini["SSID"])."\"\n";
}
?>
/>
   </div>
   <div class="col-xs-2">
    <input type="submit" value="変更" class="btn btn-default btn-lg"/>
   </div>
  </div>
 </div>
 </form>
 <form method="GET" >
 <div class="form-group">
 
  <label>接続用WiFI パスワード</label>
  <div class="row">
   <div class="col-xs-10">
    <input type="text" name="wifipass" size="100" class="form-control input-lg toolinfo" 
<?php
require_once 'commonfunc.php';
if(array_key_exists("wifipass", $config_ini)){
   print 'value="'.urldecode($config_ini["wifipass"])."\"\n";
}
?>
/>
   </div>
   <div class="col-xs-2">
    <input type="submit" value="変更" class="btn btn-default btn-lg"/>
   </div>
  </div>
 </div>
 </form>
 <br />

 <div class="form-group">
<label>接続先 URL</label>
<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value=<?php echo 'http://'.$_SERVER["HTTP_HOST"];?>/>
<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="<?php echo 'http://'.$_SERVER["SERVER_ADDR"].'/';?>" />
</div>
<hr />
<a href="requestlist_only.php" > リクエストTOPに戻る </a>
</div>
</body>
</html>