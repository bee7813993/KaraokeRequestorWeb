<?php

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
    


include 'kara_config.php';

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

function pickupsinger($rt)
{
   $singerlist = array();
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

function selectedcheck($rt,$singer){
    $rt_i = array_reverse($rt);
    foreach($rt_i as $row){
        if($row['singer'] === $singer){
          if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
            if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return TRUE;
            }
          }
        }
    }
    
//    $singerlist = pickupsinger($rt);
//    if ($singerlist[count($singerlist) - 1] === $singer )
//        return TRUE;
    return FALSE;
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
</head>
<body>

<div class="container">
<form method="post" action="exec.php" id="requestconfirm">
<div class="form-group">
<label>
URL
</label>
<textarea name="filename" id="filename" class="form-control" rows="4" wrap="soft" style="width:100%" 
<?php
if($shop_karaoke == 1){ 
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
</div>

<div CLASS="form-group">
<label>リクエスト者</label>
<select name="singer" onchange="check(this.form)" onfocus="check(this.form)" id="singer" class="form-control">
<option value="<?php print(urldecode($config_ini['nonameusername']));?>">新規入力↓</option>
<?php
$num = 1;

$selectedcounter = 0;
$singerlist = pickupsinger($allrequest);
foreach($singerlist as $singer){
{
  print "<option value=\"";
  print $singer;
  print "\"";
  if( selectedcheck($allrequest,$singer) && $selectedcounter === 0 ) 
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
<label>コメント<small> セトリ記録のため曲名とか書いてもらえると助かります </small></label>
<textarea name="comment" id="comment" class="form-control" rows="4" wrap="soft" placeholder="<?php print htmlspecialchars($requestcomment);?>" style="width:100%" >
</textarea>
</div>

<div CLASS="form-group">
<dl>
<dt>再生方法</dt>
<dd>
<?php 
  if($shop_karaoke == 1){
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
<input type="checkbox" name="secret" value="1" > シークレット予約(歌うまで曲名を表示しません)
</label>
</div>
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