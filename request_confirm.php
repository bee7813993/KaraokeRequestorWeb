<?php
$filename = "";
if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}

$shop_karaoke = 0;
if(array_key_exists("shop_karaoke", $_REQUEST)) {
    $shop_karaoke = $_REQUEST["shop_karaoke"];
}

$set_directurl = 0;
if(array_key_exists("set_directurl", $_REQUEST)) {
    $set_directurl = $_REQUEST["set_directurl"];
}
    

$forcebgv = 0;
if(array_key_exists("forcebgv", $_REQUEST)) {
    $forcebgv = $_REQUEST["forcebgv"];
}

$set_pause = 0;
if(array_key_exists("pause", $_REQUEST)) {
    $set_pause = $_REQUEST["pause"];
}


$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    if(!is_numeric($selectid)){
        $selectid = 'none';
    }
}

$bgvfile = "";
if(array_key_exists("bgvfile", $_REQUEST)) {
    $bgvfile = $_REQUEST["bgvfile"];
    if($forcebgv == 1) $fullpath=$bgvfile;
}

$lister_dbpath = '';
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

/** リクエスト者を毎回新規入力にするかどうか（共有端末用とか） **/
/** (今のところハードコーディング) **/
$blank_username = false;

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'func_audiotracklist.php';

$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

if($shop_karaoke == 1 && is_numeric($selectid)){
    $forcebgv = 1;
}

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

if(is_numeric($selectid)){
    $sql = "SELECT * FROM requesttable where id = ". $selectid;
    $select = $db->query($sql);
    $selectrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
}

function pickupsinger($rt, $moreuser = "")
{
   $singerlist = array();
   if(!empty($moreuser)){
       $singerlist[] = $moreuser;
   }
   foreach($rt as $row)
   {
       $foundflg = 0;
       foreach($singerlist as $esinger ){
           if( $esinger === $row['singer']){
               $foundflg = 1;
               break;
           }
       }
       if($foundflg === 0){
           $singerlist[] = $row['singer'];
       }
   }
   
   return $singerlist;
}

function selectedcheck_rc($rt,$singer,$beforesinger = 'none' ){
    $rt_i = array_reverse($rt);

    if($beforesinger == 'none'){
      foreach($rt as $row){
//      print '<script type="text/javascript">';
 //     print 'alart("'.var_dump($row).'")';
 //     print '</script>';
      
          if($row['singer'] === $singer){
            if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
              if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return TRUE;
              }
            }
          }
      }
    }else{
        if($singer === $beforesinger){
            return TRUE;
        }
    }
    
//    $singerlist = pickupsinger($rt);
//    if ($singerlist[count($singerlist) - 1] === $singer )
//        return TRUE;
    return FALSE;
}

function json_safe_encode($data){
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function extention_musiccheck($fn){
    if(empty($fn)) return 0;
    $extension = pathinfo($fn, PATHINFO_EXTENSION);
    if( empty($extension) ){
        logtocmd ("ERROR : File of $id has no extension : $filepath");
        return false;
    // Audio File
    }elseif( strcasecmp($extension,"mp3") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"m4a") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"wav") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"ogg") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"flac") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"wma") == 0 ){
        return 2;
    }elseif(strcasecmp($extension,"aac") == 0 ){
        return 2;
    // Movie File
    }elseif(strcasecmp($extension,"mp4") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"avi") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"mkv") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"mpg") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"flv") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"webm") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"wmv") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"ogm") == 0 ){
        return 1;
    }elseif(strcasecmp($extension,"mov") == 0 ){
        return 1;
    }else{
    // unknown file set to movie
        return 1;
    }    
}
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
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

<title>リクエスト確認画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript">

function check(selectf){
flg=(document.getElementById('singer').selectedIndex==0);
if (!flg) document.getElementById('freesinger').value='';
document.getElementById('freesinger').parentNode.style.visibility=flg?'visible':'hidden';
}


window.onload = function(){
document.getElementById("requestconfirm").onsubmit = function(){
var newname = document.getElementById("freesinger").value;
var existname = document.getElementById("singer").value;

if (newname == "" && existname =="<?php print(urldecode($config_ini['nonameusername']));?>") {
<?php
if($config_ini['nonamerequest'] != 1){
print 'alert("リクエスト者が空欄です。名前を入れてください\n(次からはドロップダウンで選べます)");';
print 'return false;';
}
?>

}
}
}

</script>
<?php 
$nanasyname = $config_ini["nonameusername"];
?>
<script id="nanasycheck" type="text/javascript" charset="utf8" src="js/requsetlist_confirm.js" 
     data-nanasy ='<?php echo json_safe_encode(urldecode($nanasyname)); ?>'
     data-nanasyflg ='<?php echo json_safe_encode($config_ini['nonamerequest']); ?>'
> </script>

</head>
<body>
<?php 
$YkariUsername = "";
if(array_key_exists("YkariUsername", $_COOKIE)) {
    $YkariUsername = $_COOKIE["YkariUsername"];
}
?>
<?php
shownavigatioinbar();
?>
<div class="container">
<form method="post" action="exec.php" id="requestconfirm">
<div class="form-group">
<label>
曲名(ファイル名)
</label>
<textarea name="filename" id="filename" class="form-control" rows="4" wrap="soft" style="width:100%" 
<?php
if(is_numeric($selectid) && $selectrequest[0]['kind'] == "カラオケ配信"){
    echo "> ";
    print $selectrequest[0]['songfile'];
    echo "</textarea> ";
}else if($shop_karaoke == 1){ 
    print 'placeholder="後でセットリスト作成の参考のためにできれば曲名を入れておいてください" >';

    if (empty($filename)){
      echo "";
    }else{
      echo "$filename";
    }  
    
    echo "</textarea> ";
}else if($set_directurl == 1 ){
    print 'placeholder="直接再生できるURLを指定を入れてください(youtubeのURLもOK)" >';
    if (empty($filename)){
      echo "";
    }else{
      echo "$filename";
    }  
    echo "</textarea> ";
}else if($set_pause == 1 ){
    print 'placeholder="小休止時のリストに表示するメッセージ" >';
    if (empty($filename)){
      echo "";
    }else{
      echo "$filename";
    }  
    echo "</textarea> ";
}else {
    print 'placeholder="曲名" disabled >';

    if (empty($filename)){
      echo "";
    }else{
      echo "$filename";
    }
    echo "</textarea> ";
    print '<input type="hidden" name="filename" id="filename" style="width:100%" value="'.$filename.'"  />';
    }
?>

    <input type="hidden" name="fullpath" id="fullpath" style="width:100%" value=<?php echo '"'.$fullpath.'"'; ?> />
<?php
if(is_numeric($selectid) && $selectrequest[0]['kind'] == "カラオケ配信"){
    print '<dt> BGV曲名 </dt>';
    print '<dd>'. $filename.' <dd>';
}
?>
</div>

<div CLASS="form-group">
<label>リクエスト者</label>
<select name="singer" onchange="check(this.form)" onfocus="check(this.form)" id="singer" class="form-control">
<option value="<?php print(urldecode($config_ini['nonameusername']));?>">新規入力↓</option>
<?php
$num = 1;

$beforesinger = 'none';
if(is_numeric($selectid)){
  $beforesinger = $selectrequest[0]['singer'];
}
$selectedcounter = 0;
$singerlist = pickupsinger($allrequest,$YkariUsername);
$pausecount = 0;
foreach($singerlist as $singer)
{
  print "<option value=\"";
  print $singer;
  print "\"";
  if($blank_username){
  }else if(!empty($YkariUsername)){
      if($singer === $YkariUsername){
         print " selected ";
         $selectedcounter = $selectedcounter + 1 ;
      }
  }else if( selectedcheck_rc($allrequest,$singer,$beforesinger) && $selectedcounter === 0 ) 
  {
      print " selected ";
      $selectedcounter = $selectedcounter + 1 ;
  }
  print "> ";
  print htmlspecialchars($singer);
  print "</option>";
  if($singer == '小休止'){
      $pausecount++;
  }
}
if($set_pause && $pausecount == 0) {
print '<option value="小休止">小休止</option>';
}

?>



</select>
<?php
if($selectedcounter === 0){
print('<span style="visibility:visible;">');
}else{
print('<span style="visibility:hidden;">');
}
?>
<input type="text" name="freesinger" id="freesinger" class="form-control" placeholder="名前を書いてね。２回目からは上のドロップダウンから選べるようになります。" value="" >
</span>
</div>

<div CLASS="form-group">
<label>コメント</label>
<textarea name="comment" id="comment" class="form-control" rows="4" wrap="soft" placeholder="<?php print htmlspecialchars($requestcomment);?>" style="width:100%" >
<?php
if(is_numeric($selectid) ){
print htmlspecialchars($selectrequest[0]['comment']);
}
?>
</textarea>
</div>

<div CLASS="form-group">
<dl>
<dt>再生方法</dt>
<dd>
<?php 
  if(is_numeric($selectid) && $selectrequest[0]['kind'] == "カラオケ配信"){
      print $selectrequest[0]['kind'];
      print '<input type="hidden" name="kind" id="kind"  value="'.$selectrequest[0]['kind'].'" />'."\n";
      $forcebgv = 1;
  }else if($shop_karaoke == 1){
      print 'カラオケ配信'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="カラオケ配信" />'."\n";
  }else if($set_directurl == 1){
      print 'URL指定'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="URL指定" />'."\n";
  }else if($set_pause == 1){
      print '小休止'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="小休止" />'."\n";
  }else{
      print 'ファイル再生(動画/音楽)'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="動画" />'."\n";
  }
?>
</dd>
</dl>
<?php

/* ファイルの存在チェック */
$fullpath_utf8 = "";
$audiotracklist = array();
if($shop_karaoke != 1 ){
    get_fullfilename($fullpath,$filename,$fullpath_utf8,$lister_dbpath);
    $filetype = extention_musiccheck($fullpath_utf8);
    if(!empty($fullpath_utf8) && $filetype == 1 ) {
        $audiotracklist = getaudiotracklist($fullpath_utf8);
    }
}

/* キー変更が有効かどうかのチェック */
/* 配信→無効 */
/* 設定で無効 → 無効 */
/* 設定で有効 → 有効 */
function keychangecheck($config_ini, $shop_karaoke){
    if($shop_karaoke == 1) return false;
    if(array_key_exists('usekeychange' ,$config_ini )) {
        if( $config_ini['usekeychange'] == 1 ) return true;
    }
    return false;
}

/* キー変更 */
if(keychangecheck($config_ini, $shop_karaoke) && $filetype == 1){
    print <<<EOT
<dl>
<dt>キー変更</dt>
<dd>
<div class="btn-group" data-toggle="buttons">
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-6" autocomplete="off" > -6
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-5" autocomplete="off" > -5
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-4" autocomplete="off" > -4
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-3" autocomplete="off" > -3
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-2" autocomplete="off" > -2
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="-1" autocomplete="off" > -1
    </label>
    <label class="btn btn-default active">
        <input type="radio" name="keychange" value="0" autocomplete="off" checked > 原曲
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="1" autocomplete="off" > 1
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="2" autocomplete="off" > 2
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="3" autocomplete="off" > 3
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="4" autocomplete="off" > 4
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="5" autocomplete="off" > 5
    </label>
    <label class="btn btn-default">
        <input type="radio" name="keychange" value="6" autocomplete="off" > 6
    </label>
</div>
</dd>
</dl>
EOT;

}

if( $shop_karaoke != 1 && $filetype == 1){

    print <<<EOT
<dl>
<dt>トラック選択</dt>
<dd>
EOT;
    if(empty($audiotracklist)){
        print '<div >オーディオトラックが判別できなかったのでとりあえず3トラック表示しています </div>';
        print '<select name="track" class="form-control">';
        $maxtrack = 3;
        for($c = 0; $c < $maxtrack ; $c++ ){
          print '  <option value="'.$c.'" >'.($c+1).'トラック目'.'</option>'."\n";
        }
    } else {
        $maxtrack = count($audiotracklist);
        if(  $maxtrack == 1  &&   (  strlen($audiotracklist[0][1]) == 0 || strpos( $audiotracklist[0][1] , 'Sound Media Handler' ) !== false || strpos( $audiotracklist[0][1] , 'GPAC ISO Audio Handler' ) !== false || strpos( $audiotracklist[0][1] , 'SoundHandler' ) !== false )){
//        print "<pre>".strlen($audiotracklist[0][1]).$audiotracklist[0][1].$maxtrack."</pre>";
        print "<pre> 1トラックのみ </pre>";
        }else {
//        print "<pre>".$audiotracklist[0][1].$maxtrack."</pre>";
        print '<select size="'. $maxtrack .'" name="track"  class="form-control">';
        for($c = 0; $c < $maxtrack ; $c++ ){
          if($c == 0 ){
              print '  <option value="'.$c.'" selected >'.($c+1).'トラック目：'.$audiotracklist[$c][1].'</option>'."\n";
          }else {
              print '  <option value="'.$c.'" >'.($c+1).'トラック目：'.$audiotracklist[$c][1].'</option>'."\n";
          }
        }
        }
        
    }
    print <<<EOT
</select>
</dd>
</dl>
EOT;
}

?>


<!-----
<select name="kind">
 <option value="動画" <?php if($shop_karaoke == 0) print 'selected';?> >動画 </option>
 <option value="カラオケ配信" <?php if($shop_karaoke == 1) print 'selected';?> >カラオケ配信 </option>
 <option value="URL指定" <?php if($set_directurl == 1) print 'selected';?> >URL指定 </option>
 </select>
------>
</div>
<div class="checkbox">
<label>
<input type="checkbox" name="secret" value="1" /> シークレット予約(歌うまで曲名を表示しません)
</label>
</div>
<?php
if($config_ini['usebgv'] == 1 && $shop_karaoke != 1 && $filetype == 1){
print '<div class="checkbox">';
print "<label>";
print '<input type="checkbox" name="loop" value="1" ';
if($forcebgv == 1 ){
    print 'checked';
}
print ' /> BGVモード <small> この動画をカラオケ配信のBGVとして予約します。</small>';
print '</label>';
print '</div>';
}

$typecheckfn = "";
if(empty($fullpath)){
  if(!empty($filename))
      $typecheckfn = $filename;
}else{
$typecheckfn = $fullpath;
}

if($config_ini['useotherplayer'] == 1 && $shop_karaoke != 1 && extention_musiccheck($typecheckfn) == 1){
print '<div class="checkbox">';
print "<label>";
print '<input type="checkbox" name="otherplayer" value="1" />';
if(empty($config_ini["otherplayer_disc"])){
print '別プレイヤー再生';
}else{
print urldecode($config_ini["otherplayer_disc"]);
}
print '</label>';
print '</div>';
}

if(configbool("useuserpause", false) || $user == 'admin' ){
print '<div class="checkbox">';
print "<label>";
print '<input type="checkbox" name="pause" value="1" ';
if($set_pause == 1 ){
print 'checked';
}
print '/>';
print '小休止リクエスト';
print '</label>';
print '</div>';
}
if(is_numeric($selectid)){
print '<input type="hidden" name="selectid" id="selectid"  value='.$selectid.' />'."\n";
}

if($shop_karaoke == 1){
print '<div class="well">';
print '<li>自分の番が回ってきたらカラオケ配信に切り替わるので、「デンモク」から歌いたい曲をリクエストしてください</li>';
print '<li>歌い終わったらメニュー「Player」の中にある「曲終了」ボタンを押してください</li>';
if($config_ini['usebgv'] == 1 ){
  print '<li>このカラオケ配信予約の後、「リスト操作」ボタンを押した後に出てくる「BGV選択」から配信曲の字幕の裏に流す動画を選ぶことができます</li>';
}
print '</div>';
}
?>
<div CLASS="row" >
<div CLASS="pushbtn col-xs-12 col-sm-8">
<input type="submit" value="実行" name="requestnow" class="requestconfirm btn btn-default btn-lg" />
</div>
</div>

</form>
<div CLASS="row" >
<button type="button" onclick="location.href='search.php' " class="btn btn-default " >
通常検索に戻る
</button> 

<button type="button" onclick="location.href='requestlist_only.php' " class="btn btn-default " >
トップに戻る
</button> 
</div>
</body>
</html>
