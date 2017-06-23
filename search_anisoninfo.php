<?php
// 変数チェック
require_once 'modules/simple_html_dom.php';
require_once 'commonfunc.php';
require_once 'search_anisoninfo_common.php';

$l_kind = null;
if(array_key_exists("kind", $_REQUEST)) {
    $l_kind = urldecode($_REQUEST["kind"]);
}

$l_url = null;
if(array_key_exists("url", $_REQUEST)) {
    $l_url = urldecode($_REQUEST["url"]);
}

$l_order = null;
if(array_key_exists("order", $_REQUEST)) {
    $l_order = urldecode($_REQUEST["order"]);
}

$selectid = '';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}

$l_q = null;
if(array_key_exists("q", $_REQUEST)) {
    $l_q = $_REQUEST["q"];
}


?>

<!doctype html>
<html lang="ja">
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

<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="js/currency.js"></script>

<script src="js/bootstrap.min.js"></script>

<title>anison.info検索：曲タイトル検索結果</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>

<body>
<?php
shownavigatioinbar('searchreserve.php');
?>


<FORM name=f action=search_anisoninfo_list.php method=get>
<INPUT type=radio value=song name=m id="song" "><label for="song">曲 (よみがなの一部でOK)</label>
<INPUT type=radio checked value=pro name=m id="pro" onclick="dsp(1)"><label for="pro">作品</label>
<INPUT type=radio value=person name=m id="person" onclick="dsp(3)"><label for="person">人物</label>
<INPUT type=radio value=mkr name=m id="mkr" onclick="dsp(5)"><label for="mkr">制作(ブランド)</label>
<!---
<INPUT type=radio value=rec name=m id="rec" onclick="dsp(4)"><label for="rec">音源</label>
<INPUT type=radio value=pgrp name=m id="pgrp" onclick="dsp(6)"><label for="pgrp">関連情報</label>
--->
<BR>

<INPUT  name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?> class="searchtextbox" >
<!---
  <div> 結果表示順(同じ検索ワード内) <br>
  <select name="order" class="searchtextbox" >
  <option value="sort=size&ascending=0" <?php print selectedcheck("sort=size&ascending=0",$l_order); ?> >サイズ順(大きい順)</option>
  <option value="sort=path&ascending=1" <?php print selectedcheck("sort=path&ascending=1",$l_order); ?> >フォルダ名(降順 A→Z)</option>
  <option value="sort=path&ascending=0" <?php print selectedcheck("sort=path&ascending=0",$l_order); ?> >フォルダ名(昇順 Z→A)</option>
  <option value="sort=name&ascending=1" <?php print selectedcheck("sort=name&ascending=1",$l_order); ?> >ファイル名(降順 A→Z)</option>
  <option value="sort=name&ascending=0" <?php print selectedcheck("sort=name&ascending=0",$l_order); ?> >ファイル名(昇順 Z→A)</option>
  <option value="sort=date_modified&ascending=0" <?php print selectedcheck("sort=date_modified&ascending=0",$l_order); ?> >日付(新しい順)</option>
  <option value="sort=date_modified&ascending=1" <?php print selectedcheck("sort=date_modified&ascending=1",$l_order); ?> >日付(古い順)</option>
  </select>
  </div>
--->
<?php
if(!empty($selectid) ) {
  print '<input type="hidden" name="selectid" value="';
  print $selectid;
  print '" />';
}
?>
<INPUT type=submit value=検索><BR><BR>

<span id="selectTag">
</span>

</FORM>

<div class="well">
ここに表示される検索結果の件数は、曲名で検索しなおして見つかった件数になります。
同じ曲名が含まれる別の曲も見つかりますので、リンク先でファイル名を見て、目的の曲かどうか確認してリクエストしてください。

</div>

<?php
// リクエストに種類もワードもなかった場合のチェック

if(!isset($l_url)  ) {
    echo "<p> 曲情報URLが指定されていません </p>";
}else {
// 検索ワード候補取得部分
   $nexturlbase = 'http://anison.info/data/';
    $list = ansoninfo_gettitlelist($nexturlbase.$l_url,$l_kind,$selectid);
    if(!empty($l_q )){
       print '<h1 > ';
       print $l_q;
       print ' の検索結果 </h1 > ';
    }
    // var_dump($list['searchinfo']);
    // maker表示
    if(array_key_exists("maker", $list['searchinfo'])) {
       $url = 'search_anisoninfo_list.php?m=mkr&q='.$list['searchinfo']['maker']['maker'];
        print '<dt>ブランド </dt><dd> <a href="'.$url.'" >'.$list['searchinfo']['maker']['maker'].'</a></dd>';
    }
    anisoninfo_display_finallist2($list['result'],$nexturlbase,$selectid);
}
?>

<button type="button" onclick="location.href='search.php' " class="btn btn-default " >
通常検索に戻る
</button> 

<button type="button" onclick="location.href='requestlist_only.php' " class="btn btn-default " >
トップに戻る
</button> 
</body>
</html>
