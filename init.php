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
<title>DBファイル名設定画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>


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

?>

<!----
現在のDBファイル名 : 
<?php
print $dbname;
?>
<br>
現在の動作モード(1: 自動再生開始モード, 2: 手動再生開始モード, 3: 手動プレイリスト登録モード, 4: BGMモード(ジュークボックスモード), 5: BGMモード(フルランダムモード) ) : 
<?php
print $playmode;
?>
<br>
現在のMediaPlayerClassic PATH :
<?php
print $playerpath;
?>
<br>
現在のfoobar2000 PATH :
<?php
print $foobarpath;
?>

<br>
<?php
print $requestcomment;
?>
---->
<br>

<hr>

DBファイル名　
<form method="post" action="init.php">
<input type="text" size=20 name="filename" id="filename" value=<?php echo $dbname; ?> >
<input type="submit" value="OK" />
</form>

動作モード選択　
<form method="post" action="init.php">
<select name="playmode" id="playmode" >  
<option value="1" <?php print selectedcheck("1",$playmode); ?> >自動再生開始モード</option>
<option value="2" <?php print selectedcheck("2",$playmode); ?> >手動再生開始モード</option>
<option value="3" <?php print selectedcheck("3",$playmode); ?> >手動プレイリスト登録モード</option>
<option value="4" <?php print selectedcheck("4",$playmode); ?> >BGMモード(ジュークボックスモード)</option>
<option value="5" <?php print selectedcheck("5",$playmode); ?> >BGMモード(フルランダムモード)</option>
</select>
<input type="submit" value="OK" />
</form>

MediaPlayerClassic PATH設定　
<form method="post" action="init.php">
<select name="playerpath" id="playerpath" >  
<option <?php print selectedcheck("C:\Program Files (x86)\MPC-BE\mpc-be.exe",$playerpath); ?> value="C:\Program Files (x86)\MPC-BE\mpc-be.exe" >C:\Program Files (x86)\MPC-BE\mpc-be.exe (MPC-BE:64bitOSで32bit版)</option>
<option <?php print selectedcheck("C:\Program Files\MPC-BE\mpc-be.exe",$playerpath); ?> value="C:\Program Files\MPC-BE\mpc-be.exe" >C:\Program Files\MPC-BE\mpc-be.exe (32bitOSでMPC-BE32bit版 or MPC-BE64bit版)</option>
</select>
<br />
&nbsp;(任意のPATH選択):
<input type="text" name="playerpath_any" size="100" class="playerpath_any" 
<?php
if( $playerpath !== 'C:\Program Files (x86)\MPC-BE\mpc-be.exe' && $playerpath !== 'C:\Program Files\MPC-BE\mpc-be.exe' )
{
    print 'value="'.$playerpath.'" ';
}
?>
/>
<input type="submit" value="OK" />
</form>

foobar2000 PATH設定　
<form method="post" action="init.php">
任意のPATH選択 :
<input type="text" name="foobarpath" size="100" class="foobarpath" value="<?php echo $foobarpath; ?>" />
<input type="submit" value="OK" />
</form>

リクエスト画面の説明書き
<form method="post" action="init.php">
<textarea name="requestcomment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php print htmlspecialchars($requestcomment); ?>
</textarea>
<input type="submit" value="OK" />
</form>

<hr />

<form method="post" action="init.php">
<div>
見つからなかった曲リストの使用
<input type="radio" name="usenfrequset" value="1" <?php print ($usenfrequset==1)?'checked':' ' ?> /> 使用する
<input type="radio" name="usenfrequset" value="2" <?php print ($usenfrequset!=1)?'checked':' ' ?> /> 使用しない
</div>
<div>
配信曲にビデオキャプチャデバイスを使用
<input type="radio" name="usevideocapture" value="1" <?php print ($usevideocapture==1)?'checked':' ' ?> /> 使用する
<input type="radio" name="usevideocapture" value="2" <?php print ($usevideocapture!=1)?'checked':' ' ?> /> 使用しない
</div>
<div>
検索ログの保存
<form method="post" action="init.php">
<input type="radio" name="historylog" value="1" <?php print ($historylog==1)?'checked':' ' ?> /> 使用する
<input type="radio" name="historylog" value="2" <?php print ($historylog!=1)?'checked':' ' ?> /> 使用しない
</div>
<div>

インターネット接続 (使用しないにするとインターネット接続が前提の機能を無効にします)
<input type="radio" name="connectinternet" value="1" <?php print ($connectinternet==1)?'checked':' ' ?> /> 使用する
<input type="radio" name="connectinternet" value="2" <?php print ($connectinternet!=1)?'checked':' ' ?> /> 使用しない
<br>
コメントサーバー設定 <br />
URL (http://xsd.php.xdomain.jp/r2.php  等, 使用しないときは空で)
<input type="text" name="commenturl_base" size="100" class="commenturl_base" value="<?php echo $commenturl_base; ?>" />
<br />
ルーム名 (半角英数字8文字まで)
<input type="text" name="commentroom" MAXLENGTH="24" size="36" class="commentroom" value="<?php echo $commentroom; ?>" />
<br>
MPC-BEのフルスクリーンボタン
<input type="radio" name="moviefullscreen" value="1" <?php print ($moviefullscreen==1)?'checked':' ' ?> /> 有効
<input type="radio" name="moviefullscreen" value="2" <?php print ($moviefullscreen!=1)?'checked':' ' ?> /> 無効
<br>
ヘルプURL (https://www.evernote.com/shard/s213/sh/c0e87185-314f-446d-ac12-fd13f25f6cb9/78f03652cc14e2ae 等, 使用しないときは空で)
<input type="text" name="helpurl" size="100" class="commenturl" value="<?php echo $helpurl; ?>" /><br />
<input type="submit" value="OK" />
</div>
</form>

<hr />
<a href ="listexport.php" > リクエストリストのダウンロード </a>
<form action="listimport.php" method="post" enctype="multipart/form-data">
リクエストリストのインポート(csvより)
<input type="file" name="dbcsv" accept="text/comma-separated-values" />
<select name="importtype" id="importtype" > 
<option value="new" >新規</option>
<option value="add" >追加</option>
</select>

<input type="submit" value="Send" />  
</form>
<a href ="listclear.php" > リクエストリストの全消去 </a>

<hr />
<form method="post" action="delete.php">
<input type="submit" name="resettsatus" value="全て未再生化" />
</form>
<hr />
BGMモード用
<li>
<a href ="listtimesclear.php?times=0" > 再生回数0クリア </a>【BGMモード(ジュークボックスモード)にて次から全て順番に再生】
</li>
<li>
<a href ="listtimesclear.php?times=1" > 再生回数1クリア </a>【BGMモード(ジュークボックスモード)にて次から全てランダムに再生】
</li>

<hr />
<form method="post" action="init.php">
プレイヤー動作監視開始待ち時間(秒) :
<input type="text" name="waitplayercheckstart" size="100" class="waitplayercheckstart" value="<?php echo $waitplayercheckstart; ?>" />
<input type="submit" value="OK" />
</form>

<form method="post" action="init.php">
プレイヤー動作監視チェック回数(回) :
<input type="text" name="playerchecktimes" size="100" class="playerchecktimes" value="<?php echo $playerchecktimes; ?>" />
<input type="submit" value="OK" />
</form>

<hr />
DDNS登録
<li> pcgame-r18.jp (アカウントを持っている人用) </li>
<form method="post" action="https://pcgame-r18.jp/ddns/adddns.php">
Hostname :<input type="text" name="host" class="host" style=”width: 40%;” value=" " />.pcgame-r18.jp
&nbsp;
IP:<input type="text" name="ip" size="10" class="ip" value="<?php echo $_SERVER["SERVER_ADDR"];?>" />
<input type="hidden" name="ttl" size="10" class="ttl" value="30" />
<input type="hidden" name="autoreturn" size="10" class="autoreturn" value="1" />
<input type="submit" value="更新" />
</form>

<li> <a href="http://jpn.www.mydns.jp/" >mydns.jp </a></li>
<form method="post" action="http://www.mydns.jp/directip.html">
マスターID :<input type="text" name="MID" class="host" style=”width: 20%;” value=" " />
&nbsp;
パスワード :<input type="text" name="PWD" class="host" style=”width: 20%;” value=" " />
&nbsp;
IP:<input type="text" name="IPV4ADDR" size="10" class="ip" value="<?php echo $_SERVER["SERVER_ADDR"];?>" />
<input type="submit" value="更新" />
</form>


<hr />
<p>
<a href ="init.php?clearauth=1" > ログイン情報クリア (対応ブラウザのみ)</a>
</p>
<p>
<a href="edit_priority.php" > 表示優先度設定 </a>
</p>

<a href="request.php" > リクエストTOP画面に戻る　</a>

</body>
</html>

