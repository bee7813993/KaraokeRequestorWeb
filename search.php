
<?php
require_once 'commonfunc.php';

if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
    if($historylog == 1){
        searchwordhistory('file:'.$word);
    }
}


if(array_key_exists("order", $_REQUEST)) {
    $l_order = $_REQUEST["order"];
}else{
    $l_order = 'sort=size&ascending=0';
}


?>
<!doctype html>
<html lang="ja">
<head>
<?php 
print_meta_header();
?>
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="js/currency.js"></script>

  <script type="text/javascript">
$(document).ready(function(){
  $('#searchresult').dataTable({
  "bPaginate" : false,
  "bStateSave" : true,
  "autoWidth": false,
  columnDefs: [
  { type: 'currency', targets: [3] }
   ]
   }
  );
});
  </script>
  <title>動画検索TOP</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<a href="request.php" >トップに戻る </a> &nbsp; 
<?php
 if(isset($word) ) {
 $nflink = "notfoundrequest/notfoundrequest.php?searchword=$word";
 }else {
 $nflink = "notfoundrequest/notfoundrequest.php";
 }
 if($usenfrequset == 1) {
    print '<a href="'.$nflink.'" >探して見つからなかった曲があったら教えてください。 </a>';
 }
?>


<hr />
<h2>ファイル名検索 </h2>
  <form action="search.php" method="GET">

    検索ワード(ファイル名) <br>
  <div class="searchtextbox" >
  <input type="text" name="searchword" class="searchtextbox" placeholder="曲名の一部での検索推奨。それ以外は下の外部DB連携も使えます"
  <?php
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
  ?>
  >
  </div>
  

  <div>
  <!--- 結果表示順 <br>
  <select name="order" class="searchtextbox" >
  <option value="sort=size&ascending=0" <?php print selectedcheck("sort=size&ascending=0",$l_order); ?> >サイズ順(大きい順)</option>
  <option value="sort=path&ascending=1" <?php print selectedcheck("sort=path&ascending=1",$l_order); ?> >フォルダ名(降順 A→Z)</option>
  <option value="sort=path&ascending=0" <?php print selectedcheck("sort=path&ascending=0",$l_order); ?> >フォルダ名(昇順 Z→A)</option>
  <option value="sort=name&ascending=1" <?php print selectedcheck("sort=name&ascending=1",$l_order); ?> >ファイル名(降順 A→Z)</option>
  <option value="sort=name&ascending=0" <?php print selectedcheck("sort=name&ascending=0",$l_order); ?> >ファイル名(昇順 Z→A)</option>
  <option value="sort=date_modified&ascending=0" <?php print selectedcheck("sort=date_modified&ascending=0",$l_order); ?> >日付(新しい順)</option>
  <option value="sort=date_modified&ascending=1" <?php print selectedcheck("sort=date_modified&ascending=1",$l_order); ?> >日付(古い順)</option>
--->
  </select>
  <input type="submit" value="検索">
  </div>
  <div class="clearleftfloat"> 
  </form>
  and検索は スペース 区切りでいけるっぽい。<br>
  全件検索は*(半角)でいけるっぽい。<br><br>
  </div>
  <?php
  	if ( empty ($word)){
  		
  	}else {
  	    echo $word."の検索結果 : ";
        PrintLocalFileListfromkeyword($word,$l_order);

  	}
  	?>
<hr />
<?php
if($connectinternet != 1){
print <<<EOM
<a href="request.php" >トップに戻る </a>

</body>
</html>
EOM;
die();
}
?>
  <h2>外部データベース連携検索 </h2>
  <h3>anison.info連携検索モード </h3>
 
<FORM name=f action=search_anisoninfo_list.php method=get>
<INPUT type=radio checked value=pro name=m id="pro" onclick="dsp(1)"><label for="pro">作品</label>
<!---
<INPUT type=radio value=song name=m id="song" onclick="dsp(2)"><label for="song">曲</label>
--->
<INPUT type=radio value=person name=m id="person" onclick="dsp(3)"><label for="person">人物</label>
<INPUT type=radio value=mkr name=m id="mkr" onclick="dsp(5)"><label for="mkr">制作(ブランド)</label>
<!---
<INPUT type=radio value=rec name=m id="rec" onclick="dsp(4)"><label for="rec">音源</label>
<INPUT type=radio value=pgrp name=m id="pgrp" onclick="dsp(6)"><label for="pgrp">関連情報</label>
--->
<BR>
<INPUT name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?> class="searchtextbox" >
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
<INPUT type=submit value=検索><BR><BR>

<span id="selectTag">
</span>

</FORM>
  <h3>banditの隠れ家連携検索モード </h3>
  

  歌手名検索 
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="2" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームタイトル検索 
  <form action="searchbandit.php" method="GET" style="display: inline"/>
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="3" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームブランド検索 
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox">
  <input type="hidden" name="column" value="1" />
  <input type="submit" value="検索">
  </form>
  <br />

  
  (キーワードでインターネット上のデータベースサイトから曲名を検索し、その曲名でローカルにファイルがあるかを検索)<br>
  (登録されてない曲は見つけられません。)<br>
  (曲名の一部を含む別の曲とかも検索結果に出ちゃいます。ありがちな1単語の曲名だとたくさん結果に出てきてしまうので注意してね)<br>
  (網羅されてない新しい曲とか、特殊文字（★とか）が曲名に入っていると見つからない可能性があるので改めてファイル名検索してみて)


<hr>

<a href="request.php" >トップに戻る </a>

</body>
</html>
