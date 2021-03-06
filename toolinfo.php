<html>
<head>
<?php 
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

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


<?php 

?>
</head>
<body>
<?php
    // 背景色変更
    if(array_key_exists("bgcolor",$config_ini)){
         print '<script type="text/javascript" >';
         print 'document.body.style.backgroundColor = "'.urldecode($config_ini["bgcolor"]).'";';
         print '</script>';
    }
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
$globalurl = "";
if(!empty($globalhost)){
    $ret = check_online_available($globalhost);
    if( $ret == 'OK' ){
        $online_avaiavle = 1;
    }
    $globalurl = 'http://'.urldecode($globalhost).'/';
}


print '<div class="container">';
// 認証キーワード表示
if(  $config_ini['useeasyauth'] == 1 ){
    print '<div class="form-group">';
    print '<label for="useeasyauth_word">認証キーワード <small> もし聞かれたらこれを入力してください </small></label>';
    print '<input type="text" class="form-control input-lg toolinfo" id="useeasyauth_word"  value="'.$config_ini['useeasyauth_word'].'">';
    print '</div>';
    if(isset($globalhost)) {
        $globalurl = 'http://'.urldecode($globalhost).'/'.'?easypass='.$config_ini['useeasyauth_word'];
    }
}


if( $online_avaiavle === 1 ){
    
    print '<label>オンライン版接続先 URL<small>Wifiに接続しなくてもアクセスできるURLです</small></label>';
    print '<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="'.$globalurl.'" />';


// オンライン版
print '<label>QRコード</label>';
print <<<EOT
  <div class="row">
  <div class="col-sm-12" class="text-center">
    <div class="text-center">
EOT;
      print $globalurl.' <br />';
print <<<EOT
    </div>
<img src="qrcode_php/outputqrimg.php?data=
EOT;
print urlencode($globalurl).'&qrsize='.$l_qrsize;
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
$localipurl = 'http://'.$_SERVER["SERVER_ADDR"].'/';
$localhosturl = 'http://'.$_SERVER["HTTP_HOST"].'/';
if(  $config_ini['useeasyauth'] == 1 ){
    $localipurl = $localipurl.'?easypass='.$config_ini['useeasyauth_word'];
    $localhosturl = $localhosturl.'?easypass='.$config_ini['useeasyauth_word'];
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
<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value=<?php echo $localhosturl;?> />
<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="<?php echo $localipurl;?>" />
</div>
<?php
  if(!empty($globalhost)){
      $ret = check_online_available($globalhost);
      if( $ret == 'OK' ){
          $online_avaiavle = 1;
          print '<label>オンライン版接続先 URL<small>Wifiに接続しなくてもアクセスできるURLです</small></label>';
          print '<input type="text" name="toolurl" class="form-control input-lg toolinfo" size="100" value="'.$globalurl.'" />';
      }
  }
?>

<div class="row">
  <div class="col-sm-<?php print $online_avaiavle ? 6 : 6 ;?>" class="text-center">
    <div class="text-center">
    <?php
      print $localhosturl.' <br />'
    ?>
    </div>
<img src="qrcode_php/outputqrimg.php?data=
<?php
print urlencode($localhosturl).'&qrsize='.$l_qrsize;
?>
" alt="QRコード" class="img-responsive center-block" /> <br />
  </div>
  <div class="col-sm-<?php print $online_avaiavle ? 6 : 6 ;?>" >
    <div class="text-center">
  <?php
    print $localipurl.' <br />'
  ?>
    </div>
<img src="qrcode_php/outputqrimg.php?data=
<?php
print urlencode($localipurl).'&qrsize='.$l_qrsize;
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