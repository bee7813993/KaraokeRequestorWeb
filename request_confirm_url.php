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

$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    if(!is_numeric($selectid)){
        $selectid = 'none';
    }
}    

if($shop_karaoke == 1 && is_numeric($selectid)){
    $forcebgv = 1;
}

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

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

function selectedcheck_request($rt,$singer,$beforesinger = 'none' ){
    $rt_i = array_reverse($rt);

    if($beforesinger == 'none'){
      foreach($rt_i as $row){
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
URL
</label>
    <input type="text" name="fullpath" id="fullpath" style="width:100%" placeholder="直接再生できるURLを指定を入れてください(youtubeのURLもOK)" value=<?php echo '"'.$filename.'"'; ?> />
<label>
曲名
</label>
<textarea name="filename" id="filename" class="form-control" rows="4" wrap="soft" style="width:100%" placeholder="後でセットリスト作成の参考のためにできれば曲名を入れておいてください" >
</textarea>

<?php
if(is_numeric($selectid) && $selectrequest[0]['kind'] == "カラオケ配信"){
//    print '<dt> BGV曲名 </dt>';
//    print '<dd>'. $filename.' <dd>';
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
foreach($singerlist as $singer){
{
  print "<option value=\"";
  print $singer;
  print "\"";
  if( selectedcheck_request($allrequest,$singer,$beforesinger) && $selectedcounter === 0 ) 
  {
      print " selected ";
      $selectedcounter = $selectedcounter + 1 ;
  }
  print "> ";
  print htmlspecialchars($singer);
  print "</option>";
}
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
    if($selectrequest[0]['kind'] == "カラオケ配信"){
      print "\n";
      print $selectrequest[0]['songfile'];
    }
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
      print $selectrequest[0]['kind'].'＋URL指定';
      print '<input type="hidden" name="kind" id="kind"  value="'.$selectrequest[0]['kind'].'＋URL指定" />'."\n";
      $forcebgv = 1;
  }else if($shop_karaoke == 1){
      print 'カラオケ配信'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="カラオケ配信" />'."\n";
  }else if($set_directurl == 1){
      print 'URL指定'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="URL指定" />'."\n";
  }else{
      print 'ファイル再生(動画/音楽)'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="動画" />'."\n";
  }
?>
</dd>
<dl>
<?php
/* キー変更 */
if($config_ini['usekeychange'] ){
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
/*
    print <<<EOT
<dl>
<dt>トラック選択</dt>
<dd>
<select name="track" class="form-control">
EOT;
$maxtrack = 10;
for($c = 0; $c < $maxtrack ; $c++ ){
  print '  <option value="'.$c.'" >'.($c+1).'トラック目</option>'."\n";
}
    print <<<EOT
</select>
</dd>
</dl>
EOT;
*/

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
if($config_ini['usebgv'] == 1 ){
print '<div class="checkbox">';
print "<label>";
print '<input type="checkbox" name="loop" value="1" ';
if($forcebgv == 1 ){
    print 'checked';
}
print ' /> BGVモード';
print '</label>';
print '</div>';
}
if(is_numeric($selectid)){
print '<input type="hidden" name="selectid" id="selectid"  value='.$selectid.' />'."\n";
print '<input type="hidden" name="urlreq" id="urlreq"  value=1 />'."\n";
}
?>
<div CLASS="row" >
<div CLASS="pushbtn col-xs-12 col-sm-8">
<input type="submit" value="実行" class="requestconfirm btn btn-default btn-lg" />
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
