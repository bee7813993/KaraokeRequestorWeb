<?php

if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}

$user='normal';

if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}


require_once 'commonfunc.php';

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

?>

<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header();?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">

<title>カラオケ動画リクエスト</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<!-- <script type='text/javascript' src='jwplayer/jwplayer.js'></script> -->

<script type="text/javascript" src="mpcctrl.js"></script>

<style type="text/css">

// script from http://proto.sabi-an.com/18/
body { background-image: url(http://sabi-an.com/img/kamon.png); background-repeat: no-repeat; background-position: right top; }
#drop_area { height:10px; padding:10px; border:3px solid; margin: 30px; font-size: small; }
#disp_area { padding:10px; border:3px solid; margin: 30px; }
.thumb { height: 75px; border: 1px solid #000; margin: 10px 5px 0 0; }
</style>

<script type="text/javascript">

function check(selectf){
flg=(document.getElementById('singer').selectedIndex==0);
if (!flg) document.getElementById('freesinger').value='';
document.getElementById('freesinger').parentNode.style.visibility=flg?'visible':'hidden';
}


// File API が使えるかチェック
window.onload = function() {
  var objDropArea = document.getElementById("drop_area");
  if ( window.File && window.FileReader ) {
    // ドロップ時のアクションを設定
    objDropArea.addEventListener("drop", function(event) { fileRead(event); }, false);
    // ブラウザが実装している処理を止める関数を設定
    objDropArea.addEventListener("dragover", function(event) { preventDefault(event); }, false);
  } else {
    // ブラウザが対応していない場合の処理
    objDropArea.innerHTML = 'お使いのブラウザは対応していません。';
    var objDispArea = document.getElementById("disp_area");
    objDispArea.parentNode.removeChild(objDispArea);
  }
}

// ドロップ時のアクション
function fileRead(event)
{
  preventDefault(event);

  var files = event.dataTransfer.files;
  var objDispArea = document.getElementById("disp_area");

  objDispArea.innerHTML = '';

  // ドロップされたファイルの処理
  for ( var i = 0; i < files.length; i++ ) {

    var f = files[i];

    var objFileReader = new FileReader();
    objFileReader.onerror = function(evt) {
      objDispArea.innerHTML = '【' + f.name + '】 ファイル読み込み時にエラーが発生しました。';
      return;
    }

    // テキストの処理
    objDispArea.innerHTML = f.name;

    document.getElementById("filename").value = f.name;

  }
}

// ブラウザが実装している処理を止める
function preventDefault(event)
{
  event.preventDefault();
}

// プレーヤーコントローラーの切り替え
function selectPlayerctrl()
{
var nowplayingurl = "http://" + location.hostname + "/playingsong.php"

var statusRequest = new XMLHttpRequest();
statusRequest.open("GET", nowplayingurl);
statusRequest.send();
statusRequest.onload=function(ev){
   stat = JSON.parse(statusRequest.responseText);
   
   if("foobar" == stat.player)
   {
        document.getElementById( 'parentplayerarea' ).src ="foobarctl.php";
   }else {
        document.getElementById( 'parentplayerarea' ).src ="mpcctrl.php";
   }
};

}

</script>
</head>
<body>
<?php
if ($user === 'admin'){
    print '管理者ログイン中<br>';
}
?>
<div  align="center" >
<form method="GET" action="search.php" >
<input type="submit" name="曲検索はこちら"   value="曲検索はこちら" class="topbtn"/>
</form>
</div>
<div align="center" >
<form method="GET" action="request_confirm.php?shop_karaoke=1" >
<input type="hidden" name="shop_karaoke" value="1" />
<?php
if ($playmode != 4 && $playmode != 5){
print '<input type="submit" name="配信"   value="カラオケ配信曲を歌いたい場合はこちらから" class="topbtn"/> ';
}
?>
</form>
<?php
if($usenfrequset == 1) {
    print '<form method="GET" action="notfoundrequest/notfoundrequest.php" >';
    print '<input type="submit" name="noffoundsong"   value="見つからなかった曲があればこちらから教えてください" class="topbtn"/>';
    print '</form>';
}
?>
</div>
<br />

<div align="center" >
現在の動作モード
<?php
     if($playmode == 1){
     print ("自動再生開始モード: 自動で次の曲の再生を開始します。");
     }elseif ($playmode == 2){
     print ("手動再生開始モード: 再生開始を押すと、次の曲が始まります。(歌う人が押してね)");
     }elseif ($playmode == 4){
     print ("BGMモード: 自動で次の曲の再生を開始します。すべての再生が終わると再生済みの曲をランダムに流します。");
     }elseif ($playmode == 5){
     print ("BGMモード(ランダムモード): 順番は関係なくリストの中からランダムで再生します。");
     }else{
     print ("手動プレイリスト登録モード: 機材係が手動でプレイリストに登録しています。");
     }
?>
</div>

<div align="center" >
<p onclick=selectPlayerctrl() > プレイヤーコントローラー </p>
<iframe src="playerctrl_portal.php"  class="pcarea"  id="parentplayerarea"  onmouseover=selectPlayerctrl() ontouchstart=selectPlayerctrl() >
ブラウザが対応してないかもです。
<a href="mpcctrl.php" >こちらのリンク先</a>を使ってみてください。
</iframe>
</div>



<?php

print "<div align=\"center\" id=\"content\" >";


if(!count($allrequest) == 0 ){


print "<table border=\"2\" id=\"table\">\n";
print '<caption> 現在の登録状況 <button type="submit" value="" class="topbtn"  onclick=location.reload() >更新</button></caption>'."\n";
print "<thead>\n";
print "<tr>\n";
print "<th>ファイル名 </th>\n";
print "<th>登録者 </th>\n";
print "<th>コメント </th>\n";
print "<th>再生方法 </th>\n";

     if($playmode == 1){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 2){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 4){
     print "<th>再生回数 </th>\n";
     }else{
     print "<th>順番 </th>\n";
     }
print "<th>アクション </th>\n";
if($user === "admin"){
print "<th>変更 </th>\n";
}
print "</tr>\n";
print "<tbody>\n";

foreach($allrequest as  $row) {
print "<tr>\n";
print "<th class=\"filename\">";
if( ($row['secret'] == 1 ) && strcmp($row['nowplaying'],'未再生') == 0){
print '<b> ヒ・ミ・ツ♪(シークレット予約) </b>';
}else{
print nl2br(htmlspecialchars($row['songfile']));
}
print "</th>\n";

print "<td class=\"singer\">";
print nl2br(htmlspecialchars($row['singer']));
print "</td>\n";
print "<td class=\"comment\">";
print nl2br(htmlspecialchars($row['comment']));
print "</td>\n";
print "<td class=\"kind\">";
print $row['kind'];
print "</td>\n";

print "<td class=\"nowplaying\">";
print "<div>";
     if($playmode == 1){  // 自動再生開始モード
     print $row['nowplaying']."<br />";
print "<form method=\"post\" action=\"changeplaystatus.php\" style=\"display: inline\" >";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print "<select name=\"nowplaying\">";
print " <option value=\"未再生\" selected >未再生 </option>";
print " <option value=\"再生済\">再生済 </option>";
print "</select>";
print "<input type=\"submit\" name=\"update\" value=\"変更\"/>";
print "</form>";
     }elseif ($playmode == 2){  // 手動再生開始モード
     print $row['nowplaying'];
print "<form method=\"post\" action=\"changeplaystatus.php\" style=\"display: inline\" >";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print "<select name=\"nowplaying\">";
print " <option value=\"未再生\" selected >未再生 </option>";
print " <option value=\"再生済\">再生済 </option>";
print "</select>";
print "<input type=\"submit\" name=\"update\" value=\"変更\"/>";
print "</form>";
     }elseif ($playmode == 4){ // BGMモード
     print $row['playtimes'];
     }else{
     print $row['reqorder'];
     }
print "</div>";

print "</td>\n";

print "<td class=\"action\">";
print "<form method=\"post\" action=\"delete.php\">";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print '<div class="acition" >';
print "<input type=\"submit\" name=\"up\"     value=\"上へ\"/>";
print "<input type=\"submit\" name=\"down\"   value=\"下へ\"/>";
print '</div>';
print '<div class="acition2" >';
print "<input type=\"submit\" name=\"delete\" value=\"削除\"/>";
print "<input type=\"submit\" name=\"warikomi\"   value=\"次に再生\"/>";
print '</div>';
print '<div class="clear" >';
print '</div>';
print "</form>";
print "</td>\n";
if($user === "admin"){
print "<td class=\"change\">";
print "<form method=\"post\" action=\"change.php\">";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print "<input type=\"submit\" name=\"変更\"   value=\"変更\"/>";
print "</form>";
print "</td>\n";
}

print "</tr>\n";
}
}
$db = null;
?>
</tbody>
</table>
</div>


<form method="post" action="init.php">
<input type="submit" value="設定" />
</form>
<a href="toolinfo.php" > 接続情報表示 </a>



</body>
</html>
