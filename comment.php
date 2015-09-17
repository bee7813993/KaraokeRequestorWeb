<?php

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

<title>コメントポスト</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<!-- <script type='text/javascript' src='jwplayer/jwplayer.js'></script> -->
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar();
?>

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


<?php
if(!empty($commenturl)) {
print '<div align="center" class="commentpost" >';
//    print '<input type="button" onclick="location.href=\''.$commenturl.'\'" value="こちらから画面にコメントを出せます(ニコ生風に)" class="topbtn"/>';
    print "<h4>こちらから画面にコメントを出せます(ニコ生風に)</h4>";
    print <<<EOD
<form name=forms action="commentpost.php" class="sendcomment" method="post">

<div class="row" >
<div class="col-xs-12 col-sm-12" ><b>文字色</b> </div>
<div class="col-xs-2 col-sm-push-11 col-sm-1" >その他 <input type="radio" name="col" value="CUSTOM" > <input type="color" name="c_col" value="#FFFFFF" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:white;"><input type="radio" name="col" value="FFFFFF" checked="checked" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:gray;"><input type="radio" name="col" value="808080" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:pink"><input type="radio" name="col" value="FFC0CB" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:red;"><input type="radio" name="col" value="FF0000" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:orange;"><input type="radio" name="col" value="FFA500" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:yellow;"><input type="radio" name="col" value="FFFF00" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:lime;"><input type="radio" name="col" value="00FF00" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:aqua;"><input type="radio" name="col" value="00FFFF" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:blue;"><input type="radio" name="col" value="0000FF" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:purple;"><input type="radio" name="col" value="800080" ></div>
<div class="col-xs-2 col-sm-pull-1 col-sm-1" style="background-color:black;"><input type="radio" name="col" value="111111" ></div>
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

<br />



</body>
</html>

