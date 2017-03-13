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

$newconfig = $_REQUEST;


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
<script type="text/javascript" >
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>



<title>設定画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
shownavigatioinbar();
?>

<?php
$change_counter = 0;

/*** for debug 
print '<pre>';
print "_REQUEST\n";
var_dump($_REQUEST);
print "config_ini\n";
var_dump($config_ini);
print '</pre>';
*****/


// to urlencode
$new_roomurlglobal = array();
foreach($newconfig as $key => $value){
    if(is_array($value)){
    $roomcount = 0;
        foreach ($value as $roomno => $roomurl){
            if($key === 'roomurl') {
                if(!empty($newconfig['roomno'][$roomno])){
                    $newconfig['roomurl'][$newconfig['roomno'][$roomno]]=$roomurl;
                }else{
                }
                if(array_key_exists("roomurlglobal", $newconfig)){
                  foreach($newconfig["roomurlglobal"] as $rv ){
                    if( $rv == $roomcount ){
                        print "now setting newconfig['roomurlglobal'][".$newconfig['roomno'][$roomno].']';
                        $new_roomurlglobal += array( $newconfig['roomno'][$roomno] => 1 );
                    }else{
                    }
                  }
                }
            unset($newconfig['roomno'][$roomno]);
            unset($newconfig['roomurl'][$roomno]);
            }
            $roomcount++;
        }
    }else {
        $newconfig[$key] = urlencode($value);
    }
}

if(!empty($newconfig) ) $newconfig['roomurlglobal'] = $new_roomurlglobal ;
// $config_ini['roomurl'] = array();
$config_ini_new = array_merge($config_ini,$newconfig);

if(!empty($config_ini_new) ){
    $change_counter++;
}

if(  $change_counter > 0 ){

/**** for debug
print '<pre>';
print "config_ini_mod\n";
var_dump($config_ini_new);
print '</pre>';
*****/

    writeconfig2ini($config_ini_new,$configfile);
    $config_ini = $config_ini_new;
}

?>

<?php 
//echo '<pre>';
// var_dump($config_ini); 

// print "form input\n";
// var_dump($_REQUEST); 
// if( multiroomenabled()){print "enabled";}
//echo '</pre>';
?>

<div class="container bg-info">
  <h3> リクエストリスト操作 </h3>
  <a href ="listexport_sjis.php"  class="btn btn-default" > リクエストリストのダウンロード(SJIS) </a>
  <a href ="listexport.php"  class="btn btn-default" > リクエストリストのダウンロード(UTF-8) </a>
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
  <h3>動作設定 </h3>
  <form name="allconfig" method="post" action="init.php">
  <div class="form-group">
    <label>DBファイル名</label>
    <input type="text" name="dbname" id="dbname" class="form-control" value=<?php echo  urldecode($config_ini["dbname"]); ?> >
  </div>
  
  <div class="form-group">
    <label for="playmode">動作モード選択</label>
    <select name="playmode" id="playmode" class="form-control" >  
      <option value="1" <?php print selectedcheck("1",$config_ini["playmode"]); ?> >自動再生開始モード</option>
      <option value="2" <?php print selectedcheck("2",$config_ini["playmode"]); ?> >手動再生開始モード</option>
      <option value="3" <?php print selectedcheck("3",$config_ini["playmode"]); ?> >手動プレイリスト登録モード</option>
      <option value="4" <?php print selectedcheck("4",$config_ini["playmode"]); ?> >BGMモード(ジュークボックスモード)</option>
      <option value="5" <?php print selectedcheck("5",$config_ini["playmode"]); ?> >BGMモード(フルランダムモード)</option>
    </select>
  </div>

  <h3>自動再生設定 </h3>
<?php
if(array_key_exists("autoplay_exec",$config_ini) && strlen($config_ini["autoplay_exec"]) > 0) {
print '<button type="button" class="btn btn-default btn-lg" onclick="location.href=\'autoplayctrl.php\'" >自動実行開始、停止ページへ</button>';
}
?>


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
  </div>

<!---- トップ画面メッセージの設定 ----->
  <div class="form-group">
    <label >
    トップ画面メッセージの設定 
    
    </label>

    <div class="form-group">
    <label for=> 予約一覧（トップ）画面表示メッセージ <small> HTML記述OK、「#yukarihost#」はホスト名に置換 </small> </label>
     <textarea name="noticeof_listpage" class="form-control" id="noticeof_listpage" >
<?php
if(array_key_exists("noticeof_listpage",$config_ini)) {
    print urldecode($config_ini["noticeof_listpage"]);
}else {
    print '';
}
?></textarea>
    </div>  
    <div class="form-group">
    <label for=> 検索画面表示メッセージ <small> HTML記述OK 「#yukarihost#」はホスト名に置換 </small> </label>
     <textarea name="noticeof_searchpage" class="form-control" id="noticeof_searchpage" >
<?php
if(array_key_exists("noticeof_searchpage",$config_ini)) {
    print urldecode($config_ini["noticeof_searchpage"]);
}else {
    print '';
}
?></textarea>
    </div>  
  </div>  

<!---- トップ画面リクエストリストリロード時間 ----->
  <div class="form-group">
    <label for="reloadtime"> リクエスト一覧リロード時間 <small> 0 でリロード無効。数値を大きくするとオンライン接続時の通信量を減らせます </small> </label>
    </label>
    <input type="text" name="reloadtime" size="100" class="form-control"
<?php
if(array_key_exists("reloadtime",$config_ini)) {
print ' value="'.urldecode($config_ini["reloadtime"]).'"';
}else {
print ' value="20" '; 
}

?>
/>
  </div>

  <div class="form-group">
    <label for="playerpath_select">MediaPlayerClassic PATH設定</label>
    <select  class="form-control" name="playerpath_select" id="playerpath_select" >  
      <option <?php print selectedcheck("C:\Program Files (x86)\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files (x86)\MPC-BE\mpc-be.exe" >C:\Program Files (x86)\MPC-BE\mpc-be.exe (MPC-BE:64bitOSで32bit版)</option>
      <option <?php print selectedcheck("C:\Program Files\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files\MPC-BE\mpc-be.exe" >C:\Program Files\MPC-BE\mpc-be.exe (32bitOSでMPC-BE32bit版)</option>
      <option <?php print selectedcheck("C:\Program Files\MPC-BE x64\mpc-be64.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files\MPC-BE x64\mpc-be64.exe" >C:\Program Files\MPC-BE x64\mpc-be64.exe (64bitOSでMPC-BE64bit版)</option>
      <option <?php print selectedcheck("その他PATH指定",urldecode($config_ini["playerpath_select"])); ?> value="その他PATH指定" >その他PATH指定</option>
    </select>
    <label > (任意のPATH選択) </label>
    <input class="form-control" type="text" name="playerpath_any" size="100" class="playerpath_any" 
<?php
if( urldecode($config_ini["playerpath_select"]) == 'その他PATH指定' )
{
    print 'value="'.urldecode($config_ini["playerpath_any"]).'" ';
}
?>
/>
  </div>
  <div class="form-group">
    <label class="radio control-label"><span data-toggle="tooltip" data-placement="top" title="通常使用するプレイヤーとは別のプレイヤーを使えるようにします。予約確認画面に項目を追加。次の曲に行くには曲終了時に「曲終了」ボタンを押す必要があります" > 別プレーヤー指定 </span> </label>
    <label class="radio-inline" data-toggle="tooltip" data-placement="top" title="予約確認画面に項目を追加するかどうか">
      <input type="radio" name="useotherplayer" value="1" <?php 
      if(array_key_exists("useotherplayer",$config_ini)){
          print ($config_ini["useotherplayer"]==1)?'checked':' ';
      }
      ?> /> 使用する
    </label>
    <label class="radio-inline"> <input type="radio" name="useotherplayer" value="2" <?php 
    if(array_key_exists("useotherplayer",$config_ini)){
        print ($config_ini["useotherplayer"]!=1)?'checked':' ';
    }else{
        print "checked";
    }
    ?> /> 使用しない </label></br>
    <label > <span data-toggle="tooltip" data-placement="top" title="リクエスト確認画面でのこの項目に対する説明文">リクエスト確認画面での説明文 </span></label>
    <input type="text" name="otherplayer_disc" class="form-control" 
<?php
if(array_key_exists("otherplayer_disc",$config_ini)) {
print 'value="'.urldecode($config_ini["otherplayer_disc"]).'"';
}
?>
/>
    <label > <span data-toggle="tooltip" data-placement="top" title="起動する別プログラム コマンドプロンプトから「 ＜コマンド名＞ ＜ファイル名＞ 」で起動させる">別プレーヤーのPATH（空白で手動実行) </span></label>
    <input type="text" name="otherplayer_path" class="form-control" 
<?php
if(array_key_exists("otherplayer_path",$config_ini)) {
print 'value="'.urldecode($config_ini["otherplayer_path"]).'"';
}
?>
/>

  </div>


  <div class="form-group">
    <label for="foobarpath"> foobar2000 PATH設定　</label>
    <label > 任意のPATH選択  </label>
    <input type="text" name="foobarpath" class="form-control" id="foobarpath" value="<?php echo urldecode($config_ini["foobarpath"]); ?>" />
  </div>
  <div class="form-group">
    <label for="comment"> リクエスト画面の説明書き </label>
    <textarea class="form-control" name="requestcomment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php print htmlspecialchars(urldecode($config_ini["requestcomment"])); ?>
    </textarea>
  </div>

  <div class="form-group">
    <label class="radio control-label">見つからなかった曲リストの使用 </label>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="1" <?php print ($config_ini["usenfrequset"]==1)?'checked':' ' ?> /> 使用する </label>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="2" <?php print ($config_ini["usenfrequset"]!=1)?'checked':' ' ?> /> 使用しない </label>
  </div>
  <div class="form-group">
    <label for="max_filesize"> 検索結果に表示する最大ファイルサイズ(MB)<small> 0 で無制限 </small> </label>
    </label>
    <input type="text" name="max_filesize" size="100" class="form-control"
<?php
if(array_key_exists("max_filesize",$config_ini)) {
print 'value="'.urldecode($config_ini["max_filesize"]).'"';
}
?>
/>
  </div>
  <div class="form-group">
    <label class="radio control-label">配信曲にビデオキャプチャデバイスを使用 </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="2" <?php print ($config_ini["usevideocapture"]!=1 && $config_ini["usevideocapture"]!=3)?'checked':' ' ?> /> 使用しない
    </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="1" <?php print ($config_ini["usevideocapture"]==1)?'checked':' ' ?> /> MPC-BEを使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="3" <?php print ($config_ini["usevideocapture"]==3)?'checked':' ' ?> /> 別のアプリを使用する
    </label>
  </div>
  <div class="form-group">
    <label >
      配信表示アプリ <small>(「別アプリ使用」の設定の時のみ有効)</small>
    </label>
    <input type="text" name="captureapli_path" size="100" class="form-control"
<?php
if(array_key_exists("captureapli_path",$config_ini)) {
print 'value="'.urldecode($config_ini["captureapli_path"]).'"';
}
?>
/>
  </div>
  <div class="form-group">
    <label >
      配信開始時実行コマンド 
    </label>
    <input type="text" name="DeliveryCMD" size="100" class="form-control"
<?php
if(array_key_exists("DeliveryCMD",$config_ini)) {
print 'value="'.urldecode($config_ini["DeliveryCMD"]).'"';
}
?>
/>
  </div>
  <div class="form-group">
    <label >
      配信終了時実行コマンド 
    </label>
    <input type="text" name="DeliveryCMDEND" size="100" class="form-control"
<?php
if(array_key_exists("DeliveryCMDEND",$config_ini)) {
print 'value="'.urldecode($config_ini["DeliveryCMDEND"]).'"';
}
?>
/>
  </div>

  <div class="form-group">
    <label class="radio control-label">BGVモード </label>
    <label class="radio-inline"> <input type="radio" name="usebgv" value="1" <?php print ($config_ini["usebgv"]==1)?'checked':' ' ?> /> 使用する </label>
    <label class="radio-inline"> <input type="radio" name="usebgv" value="2" <?php print ($config_ini["usebgv"]!=1)?'checked':' ' ?> /> 使用しない </label>

    <div class="form-group">
    <label class="radio control-label"> BGVフォルダ <small> 空でBGV検索画面無効 </small> </label>
    <input type="text" name="BGVfolder" class="form-control"
<?php
if(array_key_exists("BGVfolder",$config_ini)) {
    print 'value="'.urldecode($config_ini["BGVfolder"]).'"';
}else {
    print 'value=""';
}
?>
/>
    </div>
    <div class="form-group">
    <label >
      BGV開始時実行コマンド 
    </label>
    <input type="text" name="BGVCMDSTART" size="100" class="form-control"
<?php
if(array_key_exists("BGVCMDSTART",$config_ini)) {
print 'value="'.urldecode($config_ini["BGVCMDSTART"]).'"';
}
?>
/>
    </div>
    <div class="form-group">
    <label >
      BGV終了時実行コマンド 
    </label>
    <input type="text" name="BGVCMDEND" size="100" class="form-control"
<?php
if(array_key_exists("BGVCMDEND",$config_ini)) {
print 'value="'.urldecode($config_ini["BGVCMDEND"]).'"';
}
?>
/>
    </div>
  </div>


  <div class="form-group">
    <label class="radio control-label"> 検索ログの保存 </label>
    <label class="radio-inline">
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
    <label title="検索結果がこの数を超えた場合、作品名や歌手名と一緒に検索結果を表示する" > anison.info 詳細検索表示件数 <small> 0で1件でもあれば表示</small> </label>
    <?php 
        $anisoninfomanynumber = 15;
        if(array_key_exists("anisoninfomanynumber",$config_ini)) {
           $anisoninfomanynumber = $config_ini["anisoninfomanynumber"];
        }
    ?>
    <input type="text" name="anisoninfomanynumber" size="100" class="form-control" value="<?php echo $anisoninfomanynumber; ?>" />
  </div>
  <div class="form-group">
    <label >
    コメントサーバー設定 <br />
    <small> ローカルサーバー http://localhost/cms/r.php ,リモートサーバー http://xsd.php.xdomain.jp/r2.php </small>
    </label>

    <select  class="form-control" name="commenturl_base" >  
      <option value="notset" > 使用しない </option>
      <option <?php print selectedcheck("http://localhost/cms/r.php",urldecode($config_ini["commenturl_base"])); ?> value="http://localhost/cms/r.php" > http://localhost/cms/r.php </option>
      <option <?php print selectedcheck("http://xsd.php.xdomain.jp/r2.php",urldecode($config_ini["commenturl_base"])); ?> value="http://xsd.php.xdomain.jp/r2.php" > http://xsd.php.xdomain.jp/r2.php </option>
    </select>

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
  </div> 
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
  </div> 


  <div class="form-group">
    <label >
    ニコニコ動画ダウンロード設定 
    
    </label>

    <div class="form-group">
      <label > ログインID(メールアドレス) <br />
      <input type="text" name="nicoid"  class="form-control" 
<?php
if(array_key_exists("nicoid",$config_ini)) {
print 'value="'.urldecode($config_ini["nicoid"]).'"';
}
?>    
    />
      <label > パスワード <br />
      <input type="password" name="nicopass"  class="form-control" 
<?php
if(array_key_exists("nicopass",$config_ini)) {
print 'value="'.urldecode($config_ini["nicopass"]).'"';
}
?>      />
    </div>
    <div class="form-group">
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


  <div class="form-group">
    <label > プレイヤー動作監視開始待ち時間(秒) </label>
      
    <input type="text" name="waitplayercheckstart" size="100" class="form-control" value="<?php echo $config_ini["waitplayercheckstart"]; ?>" />
    <label > プレイヤー動作監視チェック回数(回)  </label>
    <input type="text" name="playerchecktimes" size="100" class="form-control" value="<?php echo $config_ini["playerchecktimes"]; ?>" />
  </div>

  <label class="radio control-label"> 別部屋URL設定 </label>
  <table class="table table-striped table-bordered table-condensed">
  <thead>
    <tr>
      <th class="col-xs-3" >部屋番号</th>
      <th class="col-xs-8" >URL</th>
      <th class="col-xs-1" >グローバル</th>
    </tr>
  </thead>
<?php 
  $roomcount = 0;
  foreach ( $config_ini["roomurl"] as $key => $value ){
      if( empty($key) || empty($value) ){
          continue;
      }
      print '  <tr>'."\n";
      print '    <td><input type="text" class="form-control input-normal" placeholder="部屋'.$roomcount.'の部屋番号" name="roomno[]"';
      print 'value="'.$key.'"' ;
      print '    ></td>'."\n";
      print '    <td><input type="text" class="form-control input-normal" placeholder="部屋'.$roomcount.'のURL" name="roomurl[]"';
      print 'value="'.$value.'"' ;
      print '    ></td>'."\n";
      print '    <td><input type="checkbox" class="form-control input-normal" placeholder="オンライン用URLかどうか？" name="roomurlglobal[]" value='.$roomcount;
      if(array_key_exists("roomurlglobal", $config_ini) && array_key_exists($key, $config_ini["roomurlglobal"]) && $config_ini["roomurlglobal"][$key] == 1){
        print ' checked' ;
      }
      print '    ></td>'."\n";
      $roomcount ++;
  }
?>  
  <tr>
    <td><input type="text" class="form-control input-normal" placeholder="部屋<?php echo $roomcount;?>の部屋番号" name="roomno[]"
    ></td>
    <td><input type="text" class="form-control input-normal" placeholder="この部屋のURL" name="roomurl[]"
    ></td>
    <td><input type="checkbox" class="form-control input-normal" placeholder="オンライン用URLかどうか？" name="roomurlglobal[]" value="<?php echo $roomcount;?>"
    ></td>
  </tr>
  </table>

  <div class="form-group">
    <label class="radio control-label"  > <span data-toggle="tooltip" data-placement="top" title="曲予約をしたとき今までの順番を考慮した場所に自動移動します。Offでは一番上に登録されます" >リクエスト時_順番ピッタリ移動 </span ></label> 
    <label class="checkbox-inline">
      <input type="radio" name="request_automove" value="1" 
<?php 
if(array_key_exists("request_automove",$config_ini)) {
  print ($config_ini["request_automove"]==1)?'checked':' ' ;
}else{
  print 'checked';
}
?>
 />
      有効 
    </label>
    <label class="checkbox-inline">
      <input type="radio" name="request_automove" value="2" 
<?php 
if(array_key_exists("request_automove",$config_ini)) {
  print ($config_ini["request_automove"]!=1)?'checked':' ' ;
}
?>
 /> 
      無効
    </label>
  </div>

<!---- 縛り曲リストの設定 ----->
  <h3> <span data-toggle="tooltip" data-placement="top" title="検索予約メニューの中に特定の曲をピックアップした一覧を表示させることができます" > ピックアップ曲リスト </span> </h3>
  <?php 

  for($i = 0 ;  $i<count($config_ini["limitlistname"]) ; $i++){
      if(empty($config_ini["limitlistname"][$i])) continue; 
      print '<div class="form-group">';
      print '  <label > 縛り曲リスト名 '.$i.' </label>';
      print '  <input type="text" name="limitlistname[]" size="100" class="form-control" value="';
      if(array_key_exists($i,$config_ini["limitlistname"]))
       { 
         echo $config_ini["limitlistname"][$i];
       }
      print '" />';
      print '  <label > 縛り曲リストファイル名（json形式）'.$i.' </label>';
      print '  <input type="text" name="limitlistfile[]" size="100" class="form-control" value="';
      if(array_key_exists($i,$config_ini["limitlistfile"])) 
       { 
         echo $config_ini["limitlistfile"][$i];
       }
      print '" />';
      print '</div>';
  
  }
  ?>
  <div class="form-group">
    <label > 縛り曲リスト名(new)  </label>
    <input type="text" name="limitlistname[]" size="100" class="form-control" value="" />
    <label > 縛り曲リストファイル名(new)（json形式） </label>
    <input type="text" name="limitlistfile[]" size="100" class="form-control" value="" />
  </div>

  <!---- ビンゴ表示機能の設定 ----->
  <div class="form-group">
  <?php
      $usebingo=false;
      if(array_key_exists("usebingo",$config_ini)){
          if($config_ini["usebingo"]==1 ){
             $usebingo=true;
          }
      }
  ?>
    <label class="radio control-label"> ビンゴ表示機能 <br /><small></small> </label>
    <label class="radio-inline">
      <input type="radio" name="usebingo" value="1" <?php print ($usebingo)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usebingo" value="2" <?php print (!$usebingo)?'checked':' ' ?> /> 使用しない
    </label>
    <a href ="bingo_input.php" class="btn btn-default" > ビンゴ項目入力画面 </a>
  </div>


  <input type="submit" class="btn btn-default btn-lg" value="設定" />
  </form>
  </div>
</div>
  <hr />
<hr />

<div class="container bg-info">
  <h2> ホスト名設定（オンライン接続およびDDNSの設定） </h2>
  <h3> オンライン接続用 <small>現状グローバルIPを持つ回線を使用してルーターにてポート開放する必要があります </small></h3>
<script type="text/javascript">
$(document).ready(function(){
  $('#onlinehost').on('submit', function(event) {
    event.preventDefault();
    $.post(
    $(this).attr('action'),
    $(this).serializeArray(),
    function(result) {
        window.location.href='init.php';
    });
    return false;
  });
});
</script>
  <label> オンライン接続用ホスト名 </label>
  <form id="onlinehost" method="post"  action="toolinfo.php">
  <div class="form-group">
    <label class="control-label" >ホスト名</label>
    <input type="text" name="globalhost" class="form-control"  value="<?php
    if(array_key_exists("globalhost",$config_ini)) {
        print urldecode($config_ini["globalhost"]);
    }
    ?>" />
<?php
    if(array_key_exists("globalhost",$config_ini)) {
      if(!empty($config_ini['globalhost'])){
        print '<dl class="dl-horizontal">';
        print '<dt> オンライン版接続確認 </dt>';
        $ret = check_online_available($config_ini['globalhost']);
       
        if( $ret == 'OK' ){
           print '<dd > <div class="bg-success" >'.$ret .'</div></dd>';
        }else {
           print '<dd > <div class="alert-danger" > NG : '.$ret .'</div></dd>';
        }
        print '</dl>';
      }
    }
?>
  <input type="submit" class="btn btn-default" value="更新" />
  </div>
  </form>
  <h4> DDNS登録 </h4>
  <label> pcgame-r18.jp (アカウントを持っている人用) </label>
  <form id="onlinehostpcg" method="post"  action="https://pcgame-r18.jp/ddns/adddns.php">
      <div class="form-group">
        <label class="control-label"> Hostname </label>
        <div class="row">
          <div class="col-xs-8">
            <input type="text" name="host" class="form-control" style=”width: 40%;” value="" />
          </div>
          <div class="col-xs-4">
          .pcgame-r18.jp
          </div>
        </div>
        <label class="control-label"> IP</label>
        <div >
          <input type="text" name="ip" size="10" class="form-control" value="<?php echo get_globalipv4();?>" />
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
    <input type="text" name="IPV4ADDR" size="10" class="form-control" value="<?php echo get_globalipv4();?>" />
  <input type="submit" class="btn btn-default" value="更新" />
  </div>
  </form>
  <hr />
  <h3> ローカル接続用DDNS登録 </h3>

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
</div>

  <hr />

<div class="container bg-info">
  <p>
    <a href ="init.php?clearauth=1" class="btn btn-default" > ログイン情報クリア (対応ブラウザのみ)</a>
  </p>
  <p>
    <a href="edit_priority.php" class="btn btn-default" > 表示優先度設定 </a>
  </p>

  <p>
    <a href ="online_update.php" class="btn btn-default" > オンラインアップデート画面 </a>
  </p>


  <a href="requestlist_only.php" class="btn btn-default" > リクエストTOP画面に戻る　</a>
</div>

<hr />

<div class="container bg-info">
  <h3> 自IP一覧 </h3>
  <pre>
  <?php
  require_once("ipconfig.php");
  $result_ipconfig=getiplist();
  
  //var_dump($result_ipconfig);
  foreach($result_ipconfig as $ifinfo){
     $count= 0;
     foreach($ifinfo as $ips){
        if($count != 0){
        if(strchr($ips,':') !== false){
          $ips = '['.strchr($ips,'%',$before_needle=true).']';
        }
        if(isset($ips)){
           $link = 'http://'.$ips.'/';
           print '<a href='.$link.' > '.$link.'</a><br />';
        }
        }
        $count ++;
     }
     
  }
  
  ?>
  </pre>
  
  
</div>

<hr />  

</body>
</html>

