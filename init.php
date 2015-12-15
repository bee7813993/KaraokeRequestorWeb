<?php

if (!isset($_SERVER['PHP_AUTH_USER'])){
    header('WWW-Authenticate: Basic realm="Please use username admin to open Configuration page. "');
    die('このページを見るにはログインが必要です');
}

if ($_SERVER['PHP_AUTH_USER'] !== 'admin'){
    header('WWW-Authenticate: Basic realm="You can use username only admin."');
    die('このページを見るにはログインが必要です');
}

require_once 'commonfunc.php';

if(array_key_exists("filename", $_REQUEST)) {
    $newdb = $_REQUEST["filename"];
}

if(array_key_exists("playmode", $_REQUEST)) {
    $newplaymode = $_REQUEST["playmode"];
}

if(array_key_exists("playerpath_any", $_REQUEST)) {
    $newplayerpath = $_REQUEST["playerpath_any"];
    // echo "set newplayerpath from any".$newplayerpath."\n";
    if(empty($newplayerpath)){
        $newplayerpath = $_REQUEST["playerpath"];
        // echo "set newplayerpath from tmpl".$newplayerpath."\n";
    }    
}

if(array_key_exists("foobarpath", $_REQUEST)) {
    $newfoobarpath = $_REQUEST["foobarpath"];
}

if(array_key_exists("requestcomment", $_REQUEST)) {
    $newrequestcomment = $_REQUEST["requestcomment"];
}

if(array_key_exists("usenfrequset", $_REQUEST)) {
    $newusenfrequset = $_REQUEST["usenfrequset"];
}

if(array_key_exists("usevideocapture", $_REQUEST)) {
    $newusevideocapture = $_REQUEST["usevideocapture"];
}


if(array_key_exists("historylog", $_REQUEST)) {
    $newhistorylog = $_REQUEST["historylog"];
}

if(array_key_exists("connectinternet", $_REQUEST)) {
    $newconnectinternet = $_REQUEST["connectinternet"];
}

if(array_key_exists("waitplayercheckstart", $_REQUEST)) {
    $newwaitplayercheckstart = $_REQUEST["waitplayercheckstart"];
}

if(array_key_exists("playerchecktimes", $_REQUEST)) {
    $newplayerchecktimes = $_REQUEST["playerchecktimes"];
}

if(array_key_exists("commenturl", $_REQUEST)) {
    $newcommenturl = $_REQUEST["commenturl"];
}

if(array_key_exists("commenturl_base", $_REQUEST)) {
    $newcommenturl_base = $_REQUEST["commenturl_base"];
}

if(array_key_exists("commentroom", $_REQUEST)) {
    $newcommentroom = $_REQUEST["commentroom"];
}


if(array_key_exists("moviefullscreen", $_REQUEST)) {
    $newmoviefullscreen = $_REQUEST["moviefullscreen"];
}

if(array_key_exists("helpurl", $_REQUEST)) {
    $newhelpurl = $_REQUEST["helpurl"];
}

if(array_key_exists("autoplay_exec", $_REQUEST)) {
    $newautoplay_exec = $_REQUEST["autoplay_exec"];
}

if(array_key_exists("autoplay_show", $_REQUEST)) {
    $newautoplay_show = $_REQUEST["autoplay_show"];
}

if(array_key_exists("nonamerequest", $_REQUEST)) {
    $newnonamerequest = $_REQUEST["nonamerequest"];
}
if(array_key_exists("nonameusername", $_REQUEST)) {
    $newnonameusername = $_REQUEST["nonameusername"];
}

if(array_key_exists("downloadfolder", $_REQUEST)) {
    $newdownloadfolder = $_REQUEST["downloadfolder"];
}



if(array_key_exists("clearauth", $_REQUEST)) {
    header('HTTP/1.0 401 Unauthorized');
}

//include 'kara_config.php';
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">

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


<title>設定画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
shownavigatioinbar();
?>

<?php
if (! empty($newdb)){
    $dbname = $newdb;
    $config_ini = array_merge($config_ini,array("dbname" => $dbname));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "DB ファイル名を".$dbname."に変更しました。";
}

if (! empty($newplaymode)){
    $playmode = $newplaymode;
    $config_ini = array_merge($config_ini,array("playmode" => $playmode));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "動作モードを".$playmode."に変更しました。<br><br>";
}

if (! empty($newplayerpath)){
    $playerpath = $newplayerpath;
    $config_ini = array_merge($config_ini,array("playerpath" => urlencode($playerpath)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "MPCのPATHを".$playerpath."に変更しました。<br><br>";
}

if (! empty($newfoobarpath)){
    $foobarpath = $newfoobarpath;
    $config_ini = array_merge($config_ini,array("foobarpath" => urlencode($foobarpath)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "foobar2000のPATHを".$foobarpath."に変更しました。<br><br>";
}

if (! empty($newrequestcomment)){
    $requestcomment = $newrequestcomment;
    $config_ini = array_merge($config_ini,array("requestcomment" => urlencode($requestcomment)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "リクエスト画面のコメント欄の説明を変更しました。<br><br>";
}else {
    // $requestcomment = "雑談とかどうぞ。その他見つからなかった曲とか、ダウンロードしておいてほしいカラオケ動画のURLとかあれば書いておいてもらえるとそのうち増えてるかも";
}

if (! empty($newusenfrequset)){
    $usenfrequset = $newusenfrequset;
    $config_ini = array_merge($config_ini,array("usenfrequset" => $usenfrequset));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "見つからなかった曲リスト使用フラグを".$usenfrequset."に変更しました。<br><br>";
}


if (! empty($newusevideocapture)){
    $usevideocapture = $newusevideocapture;
    $config_ini = array_merge($config_ini,array("usevideocapture" => $usevideocapture));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "カラオケ配信ビデオキャプチャ使用フラグを".$usevideocapture."に変更しました。<br><br>";
}

if (! empty($newhistorylog)){
    $historylog = $newhistorylog;
    $config_ini = array_merge($config_ini,array("historylog" => $historylog));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "検索ログ保存フラグを".$historylog."に変更しました。<br><br>";
}

if (! empty($newconnectinternet)){
    $connectinternet = $newconnectinternet;
    $config_ini = array_merge($config_ini,array("connectinternet" => $connectinternet));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "インターネット使用フラグを".$connectinternet."に変更しました。<br><br>";
}

if (! empty($newwaitplayercheckstart)){
    $waitplayercheckstart = $newwaitplayercheckstart;
    $config_ini = array_merge($config_ini,array("waitplayercheckstart" => $waitplayercheckstart));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視開始待ち時間を".$waitplayercheckstart."に変更しました。<br><br>";
}

if (! empty($newplayerchecktimes)){
    $playerchecktimes = $newplayerchecktimes;
    $config_ini = array_merge($config_ini,array("playerchecktimes" => $playerchecktimes));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$playerchecktimes."に変更しました。<br><br>";
}

if (! empty($newcommenturl)){
    $commenturl = $newcommenturl;
    $config_ini = array_merge($config_ini,array("commenturl" => urlencode($commenturl)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$commenturl."に変更しました。<br><br>";
}

if (isset($newcommenturl_base)){
    $commenturl_base = $newcommenturl_base;
    $config_ini = array_merge($config_ini,array("commenturl_base" => urlencode($commenturl_base)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$commenturl_base."に変更しました。<br><br>";
}

if (! empty($newcommentroom)){
    $commentroom = $newcommentroom;
    $config_ini = array_merge($config_ini,array("commentroom" => urlencode($commentroom)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$commentroom."に変更しました。<br><br>";
}


if (! empty($newmoviefullscreen)){
    $moviefullscreen = $newmoviefullscreen;
    $config_ini = array_merge($config_ini,array("moviefullscreen" => $moviefullscreen));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$moviefullscreen."に変更しました。<br><br>";
}

if (isset($newhelpurl)){
    $helpurl = $newhelpurl;
    $config_ini = array_merge($config_ini,array("helpurl" => urlencode($helpurl)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$helpurl."に変更しました。<br><br>";
}

if (isset($newautoplay_exec)){
    $autoplay_exec = $newautoplay_exec;
    $config_ini = array_merge($config_ini,array("autoplay_exec" => urlencode($autoplay_exec)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    // print "プレイヤー動作監視チェック回数を".$autoplay_exec."に変更しました。<br><br>";
}
if (isset($newautoplay_show)){
    $autoplay_show = $newautoplay_show;
    $config_ini = array_merge($config_ini,array("autoplay_show" => $autoplay_show));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}

if (isset($newnonamerequest)){
    $nonamerequest = $newnonamerequest;
    $config_ini = array_merge($config_ini,array("nonamerequest" => $nonamerequest));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}

if (isset($newnonameusername)){
    $nonameusername = $newnonameusername;
    $config_ini = array_merge($config_ini,array("nonameusername" => urlencode($nonameusername)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}

if (isset($newdownloadfolder)){
    $downloadfolder = $newdownloadfolder;
    $config_ini = array_merge($config_ini,array("downloadfolder" => urlencode($downloadfolder)));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}



?>



<div class="container bg-info">
  <h3>動作設定 </h3>
  <form method="post" action="init.php">
  <div class="form-group">
    <label>DBファイル名</label>
    <input type="text" name="filename" id="filename" class="form-control" value=<?php echo  $config_ini["dbname"]; ?> >
  </div>
  
<!----<input type="submit" value="OK" />  </form>  ---->
<!---- </form> ---->
  <div class="form-group">
    <label for="playmode">動作モード選択</label>
<!---- <form method="post" action="init.php">  ---->
    <select name="playmode" id="playmode" class="form-control" >  
      <option value="1" <?php print selectedcheck("1",$config_ini["playmode"]); ?> >自動再生開始モード</option>
      <option value="2" <?php print selectedcheck("2",$config_ini["playmode"]); ?> >手動再生開始モード</option>
      <option value="3" <?php print selectedcheck("3",$config_ini["playmode"]); ?> >手動プレイリスト登録モード</option>
      <option value="4" <?php print selectedcheck("4",$config_ini["playmode"]); ?> >BGMモード(ジュークボックスモード)</option>
      <option value="5" <?php print selectedcheck("5",$config_ini["playmode"]); ?> >BGMモード(フルランダムモード)</option>
    </select>
<!---- <input type="submit" value="OK" /> ---->
<!----     </form> ---->
  </div>
  <div class="form-group">
    <label for="playerpath">MediaPlayerClassic PATH設定</label>
<!---- <form method="post" action="init.php"> ---->
    <select  class="form-control" name="playerpath" id="playerpath" >  
      <option <?php print selectedcheck("C:\Program Files (x86)\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath"])); ?> value="C:\Program Files (x86)\MPC-BE\mpc-be.exe" >C:\Program Files (x86)\MPC-BE\mpc-be.exe (MPC-BE:64bitOSで32bit版)</option>
      <option <?php print selectedcheck("C:\Program Files\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath"])); ?> value="C:\Program Files\MPC-BE\mpc-be.exe" >C:\Program Files\MPC-BE\mpc-be.exe (32bitOSでMPC-BE32bit版 or MPC-BE64bit版)</option>
    </select>
    <label > (任意のPATH選択) </label>
    <input class="form-control" type="text" name="playerpath_any" size="100" class="playerpath_any" 
<?php
if( urldecode($config_ini["playerpath"]) !== 'C:\Program Files (x86)\MPC-BE\mpc-be.exe' && $playerpath !== 'C:\Program Files\MPC-BE\mpc-be.exe' )
{
    print 'value="'.urldecode($config_ini["playerpath"]).'" ';
}
?>
/>
<!----  <input type="submit" value="OK" /> ---->
<!----  </form> ---->
  </div>
  <div class="form-group">
    <label for="foobarpath"> foobar2000 PATH設定　</label>
<!----  <form method="post" action="init.php">  ---->
    <label > 任意のPATH選択  </label>
    <input type="text" name="foobarpath" class="form-control" id="foobarpath" value="<?php echo urldecode($config_ini["foobarpath"]); ?>" />
<!----  <input type="submit" value="OK" />
</form> ---->
  </div>
  <div class="form-group">
    <label for="comment"> リクエスト画面の説明書き </label>
<!----   <form method="post" action="init.php"> ---->
    <textarea class="form-control" name="requestcomment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php print htmlspecialchars(urldecode($config_ini["requestcomment"])); ?>
    </textarea>
<!----   <input type="submit" value="OK" />
</form> ---->
  </div>

<!----   <form method="post" action="init.php"> ---->
  <div class="form-group">
    <label class="radio control-label">見つからなかった曲リストの使用 </label>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="1" <?php print ($config_ini["usenfrequset"]==1)?'checked':' ' ?> /> 使用する </label>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="2" <?php print ($config_ini["usenfrequset"]!=1)?'checked':' ' ?> /> 使用しない </label>
  </div>
  <div class="form-group">
    <label class="radio control-label">配信曲にビデオキャプチャデバイスを使用 </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="1" <?php print ($config_ini["usevideocapture"]==1)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="2" <?php print ($config_ini["usevideocapture"]!=1)?'checked':' ' ?> /> 使用しない
    </label>
  </div>
  <div class="form-group">
    <label class="radio control-label"> 検索ログの保存 </label>
    <label class="radio-inline">
<!----    <form method="post" action="init.php"> ---->
      <input type="radio" name="historylog" value="1" <?php print ($config_ini["historylog"]==1)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="historylog" value="2" <?php print ($config_ini["historylog"]!=1)?'checked':' ' ?> /> 使用しない
    </label>
  </div>
  <div class="form-group">
    <label class="radio control-label"> インターネット接続 <br /><small>(使用しないにするとインターネット接続が前提の機能を無効にします)</small> </label>
    <label class="radio-inline">
      <input type="radio" name="connectinternet" value="1" <?php print ($config_ini["connectinternet"]==1)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="connectinternet" value="2" <?php print ($config_ini["connectinternet"]!=1)?'checked':' ' ?> /> 使用しない
    </label>
  </div>
  <div class="form-group">
    <label >
    コメントサーバー設定 <br />
    <small> ローカルサーバー http://localhost/cms/r.php ,リモートサーバー http://xsd.php.xdomain.jp/r2.php </small>
    </label>

<!---- <form method="post" action="init.php"> ---->
    <select  class="form-control" name="commenturl_base" >  
      <option value="notset" > 使用しない </option>
      <option <?php print selectedcheck("http://localhost/cms/r.php",urldecode($config_ini["commenturl_base"])); ?> value="http://localhost/cms/r.php" > http://localhost/cms/r.php </option>
      <option <?php print selectedcheck("http://xsd.php.xdomain.jp/r2.php",urldecode($config_ini["commenturl_base"])); ?> value="http://xsd.php.xdomain.jp/r2.php" > http://xsd.php.xdomain.jp/r2.php </option>
    </select>

<!----    <input type="text"   class="form-control"  value="<?php echo urldecode($config_ini["commenturl_base"]); ?>" /> ---->
    <label > ルーム名 (半角英数字8文字まで) <br />
    <input type="text" name="commentroom" MAXLENGTH="24" class="form-control" value="<?php echo urldecode($config_ini["commentroom"]); ?>" />
  </div>
  <div class="form-group">
    <label class="radio control-label"> MPC-BEのフルスクリーンボタン </label>
    <label class="radio-inline">
      <input type="radio" name="moviefullscreen" value="1" <?php print ($config_ini["moviefullscreen"]==1)?'checked':' ' ?> /> 有効
    </label>
    <label class="radio-inline">
      <input type="radio" name="moviefullscreen" value="2" <?php print ($config_ini["moviefullscreen"]!=1)?'checked':' ' ?> /> 無効
    </label>
  </div>  
  <div class="form-group">
    <label >
      ヘルプURL <small>(https://www.evernote.com/shard/s213/sh/c0e87185-314f-446d-ac12-fd13f25f6cb9/78f03652cc14e2ae 等, 使用しないときは空で)</small>
    </label>
    <input type="text" name="helpurl" size="100" class="form-control"
<?php
if(array_key_exists("helpurl",$config_ini)) {
print 'value="'.urldecode($config_ini["helpurl"]).'"';
}
?>
/>
  <div class="form-group">
    <label class="radio control-label"> 名無しでのリクエスト許可 </label>
    <label class="radio-inline">
      <input type="radio" name="nonamerequest" value="1" 
      <?php 
      if(array_key_exists("nonamerequest",$config_ini)){
          print ($config_ini["nonamerequest"]==1)?'checked':' ';
      }else {
          print 'checked';
      }
      ?> /> 許可
    </label>
    <label class="radio-inline">
      <input type="radio" name="nonamerequest" value="2" 
      <?php 
      if(array_key_exists("nonamerequest",$config_ini)){
          print ($config_ini["nonamerequest"]!=1)?'checked':' ';
      }
       
      ?> /> 不許可
    </label>
    <label class="radio control-label"> 名無しリクエスト時の表示名 </label>
    <input type="text" name="nonameusername" class="form-control"
<?php
if(array_key_exists("nonameusername",$config_ini)) {
print 'value="'.urldecode($config_ini["nonameusername"]).'"';
}else {
print 'value="名無しさん"';}
?>
/>    
    <label class="radio control-label"> アップ／ダウンロード先フォルダ <small> 要Everythingの検索対象</small> </label>
    <input type="text" name="downloadfolder" class="form-control"
<?php
if(array_key_exists("downloadfolder",$config_ini)) {
    print 'value="'.urldecode($config_ini["downloadfolder"]).'"';
}else {
    print 'value="'.$_SERVER["TMP"].'"';
}
?>
/>    
  </div>  
  </div>  
  <h3>自動再生設定 </h3>

  <div class="form-group">
    <label> 自動再生プログラムPATH設定 <br /> 
    <small>
     例）xampp環境 : autoplaystart_mpc_xampp.bat, <Strike> nginx環境: autoplaystart_mpc.bat</Strike>
    </small>
    </label>
    <input type="text" name="autoplay_exec" size="100" class="form-control" 
<?php
if(array_key_exists("autoplay_exec",$config_ini)) {
print 'value="'.urldecode($config_ini["autoplay_exec"]).'"';
}
?> />
    <label class="radio control-label"> 自動再生制御の一般ユーザーへの公開 <small>プレイヤーコントローラー画面 </small></label>
    <label class="checkbox-inline">
      <input type="radio" name="autoplay_show" value="1" 
<?php 
if(array_key_exists("autoplay_show",$config_ini)) {
  print ($config_ini["autoplay_show"]==1)?'checked':' ' ;
}
?>
 />
      有効 
    </label>
    <label class="checkbox-inline">
      <input type="radio" name="autoplay_show" value="2" 
<?php 
if(array_key_exists("autoplay_show",$config_ini)) {
print ($config_ini["autoplay_show"]!=1)?'checked':' ' ;
}else{
print 'checked';
}
?>
 /> 
      無効
    </label>
<!----
<input type="submit" value="OK" />
</form>
------>
  </div>
<?php
if(array_key_exists("autoplay_exec",$config_ini)) {
print '<button type="button" class="btn btn-default btn-lg" onclick="location.href=\'autoplayctrl.php\'" >自動実行開始、停止ページへ</button>';
}
?>
<!----
<form method="post" action="init.php">
------>
  <div class="form-group">
    <label > プレイヤー動作監視開始待ち時間(秒) </label>
      
    <input type="text" name="waitplayercheckstart" size="100" class="form-control" value="<?php echo $config_ini["waitplayercheckstart"]; ?>" />
<!----
<input type="submit" value="OK" />
</form>

<form method="post" action="init.php">
------>
    <label > プレイヤー動作監視チェック回数(回)  </label>
    <input type="text" name="playerchecktimes" size="100" class="form-control" value="<?php echo $config_ini["playerchecktimes"]; ?>" />
<!----
<input type="submit" value="OK" />
</form>
------>
  </div>

  <input type="submit" class="btn btn-default btn-lg" value="設定" />
  </form>
</div>
  <hr />
<div class="container bg-info">
  <h3> リクエストリスト操作 </h3>
  <a href ="listexport.php"  class="btn btn-default" > リクエストリストのダウンロード </a>
  <form action="listimport.php" method="post" enctype="multipart/form-data">
    <label > リクエストリストのインポート(csvより)
      <input type="file" name="dbcsv" accept="text/comma-separated-values" />
      <select name="importtype" id="importtype" class="form-control" > 
        <option value="new" >新規</option>
        <option value="add" >追加</option>
      </select>
    </label>
    <input type="submit" value="Send" />  
  </form>
  <a href ="listclear.php" class="btn btn-default" > リクエストリストの全消去 </a>

  <form method="post" action="delete.php">
    <input type="submit" name="resettsatus" value="全て未再生化" class="btn btn-default" />
  </form>

  <label > BGMモード用 </label>
  <li>
    <a href ="listtimesclear.php?times=0" class="btn btn-default" > 再生回数0クリア </a>【BGMモード(ジュークボックスモード)にて次から全て順番に再生】
  </li>
  <li>
    <a href ="listtimesclear.php?times=1" class="btn btn-default" > 再生回数1クリア </a>【BGMモード(ジュークボックスモード)にて次から全てランダムに再生】
  </li>
</div>

<hr />

<div class="container bg-info">
  <h3> ユーザー接続用DDNS登録 </h3>

  <label> pcgame-r18.jp (アカウントを持っている人用) </label>
  <form method="post"  action="https://pcgame-r18.jp/ddns/adddns.php">
      <div class="form-group">
        <label class="control-label"> Hostname </label>
        <div class="row">
          <div class="col-xs-8">
            <input type="text" name="host" class="form-control" style=”width: 40%;” value=" " />
          </div>
          <div class="col-xs-4">
          .pcgame-r18.jp
          </div>
        </div>
        <label class="control-label"> IP</label>
        <div >
          <input type="text" name="ip" size="10" class="form-control" value="<?php echo $_SERVER["SERVER_ADDR"];?>" />
        </div>
        <input type="hidden" name="ttl" size="10" class="ttl" value="30" />
        <input type="hidden" name="autoreturn" size="10" class="autoreturn" value="1" />
      <input type="submit" class="btn btn-default" value="更新" />
      </div>
  </form>

  <label> <a href="http://jpn.www.mydns.jp/" >mydns.jp </a></label>
  <form method="post"  action="http://www.mydns.jp/directip.html">
  <div class="form-group">
    <label class="control-label" >マスターID</label>
    <input type="text" name="MID" class="form-control"  value=" " />

    <label class="control-label ">パスワード</label>
    <input type="text" name="PWD" class="form-control"  value=" " />

    <label class="control-label ">IP</label>
    <input type="text" name="IPV4ADDR" size="10" class="form-control" value="<?php echo $_SERVER["SERVER_ADDR"];?>" />
  <input type="submit" class="btn btn-default" value="更新" />
  </div>
  </form>


  <hr />
  <p>
    <a href ="init.php?clearauth=1" class="btn btn-default" > ログイン情報クリア (対応ブラウザのみ)</a>
  </p>
  <p>
    <a href="edit_priority.php" class="btn btn-default" > 表示優先度設定 </a>
  </p>

  <a href="requestlist_only.php" class="btn btn-default" > リクエストTOP画面に戻る　</a>
</div>
</body>
</html>

