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


//// File API が使えるかチェック
//window.onload = function() {
//  var objDropArea = document.getElementById("drop_area");
//  if ( window.File && window.FileReader ) {
//    // ドロップ時のアクションを設定
//    objDropArea.addEventListener("drop", function(event) { fileRead(event); }, false);
//    // ブラウザが実装している処理を止める関数を設定
//    objDropArea.addEventListener("dragover", function(event) { preventDefault(event); }, false);
//  } else {
//    // ブラウザが対応していない場合の処理
//    objDropArea.innerHTML = 'お使いのブラウザは対応していません。';
//    var objDispArea = document.getElementById("disp_area");
//    objDispArea.parentNode.removeChild(objDispArea);
//  }
//}

//// ドロップ時のアクション
//function fileRead(event)
//{
//  preventDefault(event);
//
//  var files = event.dataTransfer.files;
//  var objDispArea = document.getElementById("disp_area");
//
//  objDispArea.innerHTML = '';
//
//  // ドロップされたファイルの処理
//  for ( var i = 0; i < files.length; i++ ) {
//
//    var f = files[i];
//
//    var objFileReader = new FileReader();
//    objFileReader.onerror = function(evt) {
//      objDispArea.innerHTML = '【' + f.name + '】 ファイル読み込み時にエラーが発生しました。';
//      return;
//    }
//
//    // テキストの処理
//    objDispArea.innerHTML = f.name;
//
//    document.getElementById("filename").value = f.name;
//
//  }
//}

//// ブラウザが実装している処理を止める
//function preventDefault(event)
//{
//  event.preventDefault();
//}

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


//$(function() {
//    $('#requsetlisttable').dataTable({
//        "ajax": {
//          "url": "requestlist_table_json.php",
//          "dataSrc": "",
//          "type": "GET"
//          },
//        "columns" : [
//        { "data": "1"},
//        { "data": "2"},
//        { "data": "3"},
//        { "data": "4"},
//        { "data": "5"},
//        { "data": "6"},
//        { "data": "7"},
//        { "data": "8"}
//        ],
//        "bPaginate" : false,
//        "order" : [[0, 'desc']],
//        "bDeferRender": true
//        
//    });
//} );


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
