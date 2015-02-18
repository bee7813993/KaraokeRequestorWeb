<?php

if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}
    


include 'kara_config.php';
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />

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
</script>
</head>
<body>

<div align="center" >
<a href="search.php"> ファイル検索画面 </a>
</div>
<br />

<div align="center" >
現在の動作モード
<?php
     if($playmode == 1){
     print ("自動再生開始モード: 自動で次の曲の再生を開始します。");
     }elseif ($playmode == 2){
     print ("手動再生開始モード: 再生開始を押すと、次の曲が始まります。(歌う人が押してね)");
     }else{
     print ("手動プレイリスト登録モード: 機材係が手動でプレイリストに登録しています。");
     }
?>
</div>
<br />

<div align="center" >
プレイヤーコントローラー
<iframe src="mpcctrl.php" width="95%" height="100">
ブラウザが対応してないかもです。
<a href="mpcctrl.php" >こちらのリンク先</a>を使ってみてください。
</iframe>
</div>

<section>
    <div id="drop_area">ここにカラオケ動画ファイルをドロップしてファイル名を自動入力することができます。</div>
    <div id="disp_area">
    <?php
    if (empty($filename)){
      echo "ファイル名";
    }else{
      echo $filename;
    }
    ?></div>
</section>

<script type="text/javascript">
var output = [];
output.push(escape(f.name));
</script>

<form method="post" action="exec.php">
<table border="0" width="95%">
<tr>
<td>曲名(ファイル名)</td>

<td width="150" Align="right" >リクエスト者</td>

<td width="150">
<span style="visibility:hidden;">
新規メンバー
</span>
</td>
<td>コメント</td>
<td width="100">再生方法</td>
</select>
<td>ボタン</td>
</tr>
<tr>
<td><input type="text" name="filename" id="filename" style="width:100%" value=
    <?php
    if (empty($filename)){
      echo "曲名";
    }else{
      echo "\"$filename\"";
    } ?> /> 
    <input type="hidden" name="fullpath" id="fullpath" style="width:100%" value=<?php echo '"'.$fullpath.'"'; ?> />
    </td>

<?php
$sql = "SELECT COUNT(DISTINCT singer) FROM requesttable ORDER BY id DESC";
$select = $db->query($sql);
if( $select !== false ){
$num_all=$select->fetchColumn();
}else{
print ("SELECT COUNT FAILED" );
}
?>

<td Align="right">

<select name="singer" onchange="check(this.form)" onfocus="check(this.form)" id="singer">
<option value="0">新規入力⇒

<?php
$sql = "SELECT DISTINCT singer FROM requesttable ORDER BY id DESC";
$select = $db->query($sql);
$num = 1;
if( $select !== false ){
while($row = $select->fetch(PDO::FETCH_ASSOC))
{
  print "<option value=\"";
  print $row['singer'];
  print "\"";
  if( $num == $num_all) 
  {
      print " selected ";
  }
  print "> ";
  print $row['singer'];
  print "\n";
  $num = $num + 1;
}
}

?>

</select>
</td>
<td>
<?php
if($num_all == 0){
print('<span style="visibility:visible;">');
}else{
print('<span style="visibility:hidden;">');
}
?>
<input type="text" name="freesinger" id="freesinger" style="width:100%" >
</span>
</td>
<td>
<input type="text" name="comment" id="comment" style="width:100%">
</td>
<td><select name="kind">
 <option value="動画" selected >動画 </option>
 <option value="カラオケ配信">カラオケ配信 </option>
 </select>
</td>
<td><input type="submit" value="実行"/></td>
</table>
</form>



<?php

print "<div align=\"center\">";

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);

if($select !== false ){


print "<table border=\"2\">\n";
print "<caption> 現在の登録状況 </caption>\n";
print "<thead>\n";
print "<tr>\n";
     if($playmode == 1){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 2){
     print "<th>再生状況 </th>\n";
     }else{
     print "<th>順番 </th>\n";
     }

print "<th>ファイル名 </th>\n";
print "<th>登録者 </th>\n";
print "<th>コメント </th>\n";
print "<th>再生方法 </th>\n";
print "<th>アクション </th>\n";
print "<th>変更 </th>\n";
print "</tr>\n";
print "<tbody>\n";

while($row = $select->fetch(PDO::FETCH_ASSOC)){
print "<tr>\n";
print "<td>";
     if($playmode == 1){
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
     }elseif ($playmode == 2){
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
     }else{
     print $row['reqorder'];
     }

print "</td>\n";
print "<td>";
print $row['songfile'];
print "</td>\n";
print "<td>";
print $row['singer'];
print "</td>\n";
print "<td>";
print $row['comment'];
print "</td>\n";
print "<td>";
print $row['kind'];
print "</td>\n";
print "<form method=\"post\" action=\"delete.php\">";
print "<td>";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print "<input type=\"submit\" name=\"delete\" value=\"削除\"/>";
print "<input type=\"submit\" name=\"up\"     value=\"上へ\"/>";
print "<input type=\"submit\" name=\"down\"   value=\"下へ\"/>";
print "</td>\n";
print "</form>";
print "<td>";
print "<form method=\"post\" action=\"change.php\">";
print "<input type=\"hidden\" name=\"id\" value=\"";
print $row['id'];
print "\" />";
print "<input type=\"hidden\" name=\"songfile\" value=\"";
print $row['songfile'];
print "\" />";
print "<input type=\"submit\" name=\"変更\"   value=\"変更\"/>";
print "</td>\n";
print "</form>";
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

</body>
</html>
