<?php

if(array_key_exists("filename", $_REQUEST)) {
    $word = $_REQUEST["filename"];
}

include 'kara_config.php';
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>カラオケ動画リクエスト</title>
<!-- <script type='text/javascript' src='jwplayer/jwplayer.js'></script> -->

<style type="text/css">

// script from http://proto.sabi-an.com/18/
body { background-image: url(http://sabi-an.com/img/kamon.png); background-repeat: no-repeat; background-position: right top; }
#drop_area { height:150px; padding:10px; border:3px solid; margin: 30px; }
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

<a href="search.php"> ファイル検索画面 </a>

<section>
    <div id="drop_area">ここにカラオケ動画ファイルをドロップしてファイル名を自動入力することができます。</div>
    <div id="disp_area">
    <?php
    if (empty($word)){
      echo "ファイル名";
    }else{
      echo $word;
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
    if (empty($word)){
      echo "曲名";
    }else{
      echo "\"$word\"";
    } ?> /> </td>

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

$sql = "SELECT * FROM requesttable ORDER BY id DESC";
$select = $db->query($sql);

if($select !== false ){


print "<table border=\"2\">\n";
print "<caption> 現在の登録状況 </caption>\n";
print "<thead>\n";
print "<tr>\n";
print "<th>No. </th>\n";
print "<th>ファイル名 </th>\n";
print "<th>登録者 </th>\n";
print "<th>コメント </th>\n";
print "<th>再生方法 </th>\n";
print "<th>アクション </th>\n";
print "</tr>\n";
print "<tbody>\n";

while($row = $select->fetch(PDO::FETCH_ASSOC)){
print "<tr>\n";
print "<td>";
print $row['id'];
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
print "<input type=\"submit\" name=\"delete\" value=\"削除\"/>";
print "<input type=\"submit\" name=\"up\"     value=\"上へ\"/>";
print "<input type=\"submit\" name=\"down\"   value=\"下へ\"/>";
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
<input type="submit" value="初期化" />
</form>

</body>
</html>
