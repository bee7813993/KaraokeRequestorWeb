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
$online_avaiavle = 0;
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
if(array_key_exists("globalhost", $config_ini)) {
    $globalhost = $config_ini["globalhost"];
}

if(array_key_exists("globalhost", $_REQUEST)) {
    $globalhost = urlencode($_REQUEST["globalhost"]);
    $config_ini = array_merge($config_ini,array("globalhost" => $globalhost));
    $change_counter ++;
}


$l_qrsize = 3;
if(array_key_exists("qrsize", $_REQUEST)) {
    $l_qrsize = $_REQUEST["qrsize"];
}

if(  $change_counter > 0 ){
    writeconfig2ini($config_ini,$configfile);
}

if(!empty($globalhost)){
    $ret = check_online_available($globalhost);
    if( $ret == 'OK' ){
        $online_avaiavle = 1;
    }
}


print '<div class="container">';

if( $online_avaiavle === 1 ){
    print '<label>オンライン版接続先 URL<small>Wifiに接続しなくてもアクセスできるURLです</small></label>';
    print '<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="http://'.urldecode($globalhost).'/" />';


// オンライン版
print '<label>QRコード</label>';
print <<<EOT
  <div class="row">
  <div class="col-sm-12" class="text-center">
    <div class="text-center">
EOT;
      print 'http://'.urldecode($globalhost).'/ <br />';
print <<<EOT
    </div>
<img src="qrcode_php/outputqrimg.php?data=
EOT;
print 'http://'.urldecode($globalhost).'/&qrsize='.$l_qrsize;
print <<<EOT
" alt="QRコード" class="img-responsive center-block" /> <br />
  </div>
EOT;
print '</div>'; //row
print <<<EOT
<div class="panel-group" id="localnetinfo" role="tablist" aria-multiselectable="true">
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOne">
      <h4 class="panel-title">
       <a role="button" data-toggle="collapse" data-parent="#localnetinfo" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne" class="">
        ローカル接続情報表示
       </a>
      </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
EOT;
}else{
    print '<div id="localnetinfo" ><div><div>';
}
?>


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
<?php
  if(!empty($globalhost)){
      $ret = check_online_available($globalhost);
      if( $ret == 'OK' ){
          $online_avaiavle = 1;
          print '<label>オンライン版接続先 URL<small>Wifiに接続しなくてもアクセスできるURLです</small></label>';
          print '<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="http://'.urldecode($globalhost).'/" />';
      }
  }
?>

<div class="row">
  <div class="col-sm-<?php print $online_avaiavle ? 6 : 6 ;?>" class="text-center">
    <div class="text-center">
    <?php
      print 'http://'.$_SERVER["HTTP_HOST"].'/ <br />'
    ?>
    </div>
<img src="qrcode_php/outputqrimg.php?data=
<?php
print 'http://'.$_SERVER["HTTP_HOST"].'/&qrsize='.$l_qrsize;
?>
" alt="QRコード" class="img-responsive center-block" /> <br />
  </div>
  <div class="col-sm-<?php print $online_avaiavle ? 6 : 6 ;?>" >
    <div class="text-center">
  <?php
    print 'http://'.$_SERVER["SERVER_ADDR"].'/ <br />'
  ?>
    </div>
<img src="qrcode_php/outputqrimg.php?data=
<?php
print 'http://'.$_SERVER["SERVER_ADDR"].'/&qrsize='.$l_qrsize;
?>
" alt="QRコード" class="img-responsive center-block" /> <br />
  </div>

</div>

</div> </div> 
<hr />
<div class="row">
    <div class="btn-group btn-group-justified" role="group">
	  <a href="?qrsize=3" class="btn btn-default" role="button">Small</a>
	  <a href="?qrsize=5" class="btn btn-default" role="button">Middum</a>
	  <a href="?qrsize=8" class="btn btn-default" role="button">Large</a>
    </div>
</div>
<a href="requestlist_only.php" > リクエストTOPに戻る </a>
</div>
</body>
</html>