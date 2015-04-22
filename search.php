
<?php
if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
}


if(array_key_exists("order", $_REQUEST)) {
    $l_order = $_REQUEST["order"];
}else{
    $l_order = 'sort=size&ascending=0';
}

require_once 'commonfunc.php';

?>
<!doctype html>
<html lang="ja">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">
  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>動画検索TOP</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<a href="request.php" >トップに戻る </a>



<?php
//echo $word;
?>
<hr />
<h2>ファイル名検索 </h2>
  <form action="search.php" method="post">

  <div class="searchtextbox" >
  検索ワード(ファイル名) <br>
  <input type="text" name="searchword" class="searchtextbox" 
  <?php
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
  ?>
  >
  </div>
  
  <div> 結果表示順 <br>
  <select name="order" class="searchtextbox" >
  <option value="sort=size&ascending=0" selected >サイズ順(大きい順)</option>
  <option value="sort=path&ascending=1">フォルダ名(昇順)</option>
  <option value="sort=path&ascending=0">フォルダ名(降順)</option>
  <option value="sort=name&ascending=1">ファイル名(昇順)</option>
  <option value="sort=name&ascending=0">ファイル名(降順)</option>
  <option value="sort=date_modified&ascending=1">日付(古い順)</option>
  <option value="sort=date_modified&ascending=0">日付(新しい順)</option>
  </select>
  <input type="submit" value="検索">
  </div>
  <div class="clearleftfloat"> 
  </form>
  and検索は スペース 区切りでいけるっぽい。<br>
  全件検索は*(半角)でいけるっぽい。<br><br>
  歌手名とかゲーム名では見つからないことが多いので曲名での検索推奨<br><br>
  </div>
  <?php
  	if ( empty ($word)){
  		
  	}else {
  	    echo $word."の検索結果 : ";
        PrintLocalFileListfromkeyword($word);

  	}
  	?>
<hr />
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
<INPUT type=submit value=検索><BR><BR>

<span id="selectTag">
</span>

</FORM>
  <h3>banditの隠れ家連携検索モード </h3>
  

  歌手名検索 
  <form action="searchbandit.php" method="post" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="2" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームタイトル検索 
  <form action="searchbandit.php" method="post" style="display: inline"/>
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="3" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームブランド検索 
  <form action="searchbandit.php" method="post" style="display: inline" />
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
