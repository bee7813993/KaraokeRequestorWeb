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
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">


    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<title>カラオケ動画リクエスト</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<!-- <script type='text/javascript' src='jwplayer/jwplayer.js'></script> -->
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script src="js/bootstrap.min.js"></script>
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
<input type="submit" name="曲検索はこちら"   value="曲検索はこちら" class="topbtn btn btn-default btn-lg"/>
</form>
</div>
<div align="center" >
<form method="GET" action="request_confirm.php?shop_karaoke=1" >
<input type="hidden" name="shop_karaoke" value="1" />
<?php
if ($playmode != 4 && $playmode != 5){
print '<input type="submit" name="配信"   value="カラオケ配信曲を歌いたい場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
}
?>
</form>
<?php
if($usenfrequset == 1) {
    print '<form method="GET" action="notfoundrequest/notfoundrequest.php" >';
    print '<input type="submit" name="noffoundsong"   value="見つからなかった曲があればこちらから教えてください" class="topbtn btn btn-default btn-lg"/>';
    print '</form>';
}
?>
</div>


<br />

<div align="center" >
<h4> 現在の動作モード </h4>
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
<h4 onclick=selectPlayerctrl() > プレイヤーコントローラー </h4>
<iframe src="playerctrl_portal.php"  class="pcarea"  id="parentplayerarea"  onmouseover=selectPlayerctrl() ontouchstart=selectPlayerctrl() >
ブラウザが対応してないかもです。
<a href="mpcctrl.php" >こちらのリンク先</a>を使ってみてください。
</iframe>
</div>

<?php
if(!empty($commenturl)) {
print '<div align="center" class="commentpost" >';
//    print '<input type="button" onclick="location.href=\''.$commenturl.'\'" value="こちらから画面にコメントを出せます(ニコ生風に)" class="topbtn"/>';
    print "<h4>こちらから画面にコメントを出せます(ニコ生風に)</h4>";
    print <<<EOD
<form name=forms action="commentpost.php" class="sendcomment" method="post">

<div class="row" >
<div class="col-xs-12 col-sm-12" ><b>文字色</b> </div>
<div class="col-xs-2 col-sm-1" >その他 <input type="radio" name="col" value="CUSTOM" > <input type="color" name="c_col" value="#FFFFFF" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:white;"><input type="radio" name="col" value="FFFFFF" checked="checked" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:gray;"><input type="radio" name="col" value="808080" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:pink"><input type="radio" name="col" value="FFC0CB" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:red;"><input type="radio" name="col" value="FF0000" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:orange;"><input type="radio" name="col" value="FFA500" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:yellow;"><input type="radio" name="col" value="FFFF00" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:lime;"><input type="radio" name="col" value="00FF00" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:aqua;"><input type="radio" name="col" value="00FFFF" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:blue;"><input type="radio" name="col" value="0000FF" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:purple;"><input type="radio" name="col" value="800080" ></div>
<div class="col-xs-2 col-sm-1" style="background-color:black;"><input type="radio" name="col" value="111111" ></div>
</div>
<div class="row" >
<div class="col-xs-12 col-sm-2"><b>文字サイズ</b></div>
<div class="col-xs-3 col-sm-2">小<input type="radio" name="sz" value="0"></div>
<div class="col-xs-3 col-sm-2">中<input type="radio" name="sz" value="3" checked="checked"></div>
<div class="col-xs-3 col-sm-2">大<input type="radio" name="sz" value="6"></div>
<div class="col-xs-3 col-sm-2">特大<input type="radio" name="sz" value="9"></div>
</div>
<div class="col-xs-12 col-sm-1" ><b>名前</b></div>
<div class="col-xs-12 col-sm-2" >
<input type=text name="nm" style="font-size:1em;WIDTH:100%;" fontsize=9 MAXLENGTH="32" value="
EOD;
print returnusername_self();
    print <<<EOD
" > 
</div>
<div class="col-xs-12 col-sm-2" ><b>コメント</b></div>
<div class="col-xs-12 col-sm-7" >
<input type="text" style="font-size:1em;WIDTH:100%;" name="msg" fontsize=8 MAXLENGTH="256" tabindex="1">
</div>
<br><font size=-1><input type="submit" name="SUBMIT" style="WIDTH:100%; HEIGHT:30;" align="right" value="コメント送信" class="btn btn-default ">
</form>
EOD;
print '</div>';
}
?>


<table id="request_table" class="cell-border">
<caption> <h4>現在の登録状況 <button type="submit" value="" class="topbtn btn btn-default btn-xs"  onclick=location.reload() >更新</button></h4></caption>
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
<input type="submit" value="設定" class=" btn btn-default " />
</form>
<a href="toolinfo.php" > 接続情報表示 </a>

</body>
</html>
