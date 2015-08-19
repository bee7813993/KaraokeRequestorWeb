<?php

if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}
 


require_once 'commonfunc.php';

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
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>

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

$(document).ready(function(){
  $('#table').dataTable({
  "bPaginate" : false,
  "order" : [[0, 'desc']]
   }
  );
});



$(function(requestTable) { $("#request_table").dataTable({
     "ajax": {
         "url": "requestlist_table_json.php",
         "dataType": 'json',
         "dataSrc": "",
     },
     "columns" : [
          { "data": "no", "className":"no"},
          { "data": "filename",className:"filename"},
          { "data": "singer",className:"singer"},
          { "data": "comment",className:"comment"},
          { "data": "method",className:"kind"},
          { "data": "playstatus",className:"nowplaying"},
          { "data": "action",className:"action"},
<?php
if($user === "admin"){
          print '{ "data": "change", className:"change" },';
}
?>
     ],
     "bPaginate" : false,
     "order" : [[0, 'desc']],
     bDeferRender: true,
      "autoWidth": false,
     });
} );
     
     
$("#sample_table").dataTable();
</script>
</head>
<body>
<?php
if(isset($helpurl)){

print '<div align="right">';
print '<a href="'.$helpurl. '" TARGET="_blank" > 使用方法 </a>';

print '</div>';
}

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
print '<div align="center" >';
if(isset($commenturl)) {
//    print '<input type="button" onclick="location.href=\''.$commenturl.'\'" value="こちらから画面にコメントを出せます(ニコ生風に)" class="topbtn"/>';
    print "こちらから画面にコメントを出せます(ニコ生風に)";
    print <<<EOD
<form name=forms action="commentpost.php" class="sendcomment" method="post">
<b>名前<input type=text name="nm" style="font-size:1em;WIDTH:35%;" fontsize=9 MAXLENGTH="32" value="
EOD;
print returnusername_self();
    print <<<EOD
" > 
<table border="0.5" cellspacing = 0 cellpadding = 0 bordercolor="#333333">
<tr>
<th >文字色 </th>
<th bgcolor="white"><input type="radio" name="col" value="0" ></th>
<th bgcolor="gray"><input type="radio" name="col" value="1" checked="checked" ></th>
<th bgcolor="red"><input type="radio" name="col" value="2" ></th>
<th bgcolor="orange"><input type="radio" name="col" value="3" ></th>
<th bgcolor="yellow"><input type="radio" name="col" value="4" ></th>
<th bgcolor="lime"><input type="radio" name="col" value="5" ></th>
<th bgcolor="aqua"><input type="radio" name="col" value="6" ></th>
<th bgcolor="blue"><input type="radio" name="col" value="7" ></th>
<th bgcolor="purple"><input type="radio" name="col" value="8" ></th>
<th bgcolor="black"><input type="radio" name="col" value="9" ></th>
</tr>
</table><input type="text" style="font-size:1em;WIDTH:100%;" name="msg" fontsize=8 MAXLENGTH="256" tabindex="1">
<br><font size=-1><input type="submit" name="SUBMIT" style="WIDTH:100%; HEIGHT:30;" align="right" value="送信">
</form>
EOD;
print '</div>';
}
?>


<table id="request_table" class="cell-border">
<caption> 現在の登録状況 <button type="submit" value="" class="topbtn"  onclick=location.reload() >更新</button></caption>
<thead>
<tr>
<th>No.</th>
<th>ファイル名</th>
<th>登録者</th>
<th>コメント</th>
<th>再生方法</th>
<?php
     if($playmode == 1){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 2){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 4){
     print "<th>再生回数 </th>\n";
     }else{
     print "<th>順番 </th>\n";
     }
?>
<th>アクション</th>
<?php
if($user === "admin"){
          print '<th>変更</th>';
}
?>

</tr>
</thead>
<tbody>
</tbody>
</table>

<script type="text/javascript" charset="utf8" src="js/requsetlist_ctrl.js"></script>
<hr>
<form method="get" action="init.php">
<input type="submit" value="設定" />
</form>
<a href="toolinfo.php" > 接続情報表示 </a>

</body>
</html>
