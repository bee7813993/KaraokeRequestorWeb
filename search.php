
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
$selectid = '';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}


?>
<!doctype html>
<html lang="ja">
<head>
<?php 
print_meta_header();
?>
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

<?php
/*
if(!empty($word)){

print <<<EOD
  <script type="text/javascript">
$(document).ready(function(){
  $('#searchresult').dataTable({
  "ajax": {
      "url": "searchfilefromkeyword_json.php",
      "type": "GET",
      "data": { keyword:"
EOD;
echo $word;
print <<<EOD
" },
      "dataType": 'json',
      "dataSrc": "",
  },
  "bPaginate" : true,
  "lengthMenu": [[50, 10, -1], [50, 10, "ALL"]],
  "bStateSave" : true,
  "autoWidth": false,
  "columns" : [
      { "data": "no", "className":"no"},
      { "data": "reqbtn", "className":"reqbtn"},
      { "data": "filename", "className":"filename"},
      { "data": "filesize", "className":"filesize"},
      { "data": "filepath", "className":"filepath"},
  ],
  columnDefs: [
  { type: 'currency', targets: [3] }
   ]
   }
  );
});
  </script>
EOD;
}
*/
?>
  <title>動画検索TOP</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php
shownavigatioinbar('searchreserve.php');
?>
<div class="container">
<?php
 if(isset($word) ) {
 $nflink = "notfoundrequest/notfoundrequest.php?searchword=$word";
 }else {
 $nflink = "notfoundrequest/notfoundrequest.php";
 }
 if($usenfrequset == 1) {
    print '<button type="button" onclick="location.href=\''.$nflink.'\' " class="btn btn-default btn-sm" > 探して見つからなかった曲があったら教えてください。 </button>';
 }
 $result_count = false;
 if(!empty($word) ) {
     $result_count =  searchresultcount_fromkeyword($word);
 }
 
 if(empty($word) || $result_count === 0 ) {
// トップページメッセージ表示
if(array_key_exists("noticeof_searchpage",$config_ini)) {
    if(!empty($config_ini["noticeof_searchpage"])){
      print '<div class="well">';
      print str_replace('#yukarihost#',$_SERVER["HTTP_HOST"],urldecode($config_ini["noticeof_searchpage"]));
      print '</div>';
    }
 }
 }
?>


<hr />
<h2>ファイル名(曲名)検索 </h2>
  <form action="search.php" method="GET">
  <div class="container">
  <label>検索ワード(ファイル名) </label>
  
  <input type="text" name="searchword" class="searchtextbox" placeholder="歌手名作品名検索は下の外部DB連携で検索できます"

  <?php
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
  ?>
   />
  <?php
  if(is_numeric($selectid)){
      print '<input type="hidden" name="selectid" class="searchtextbox" value='.$selectid.' />';
  }
  ?>

  <input type="submit" value="検索" class="btn btn-default " />
  </div>
  </form>
  <div class="clearleftfloat"> 
  and検索は スペース 区切り<br />
  or検索は |(半角) 区切り<br />
  not検索はnotにしたい単語の先頭に!(半角)<br />
  全件検索は*(半角)でいけるっぽい。<br/ ><br />
  </div>
  <?php
  	if ( empty ($word)){
  		
  	}else {
  	    // $result_count =  searchresultcount_fromkeyword($word);
  	    echo $word."の検索結果 : ".$result_count.'件';
  	    
    PrintLocalFileListfromkeyword_ajax($word,$l_order,'searchresult',0,$selectid);
  	}
  	?>
<hr />
<?php
if($connectinternet != 1){
print <<<EOM
<a href="requestlist_only.php" >トップに戻る </a>

</body>
</html>
EOM;
die();
}
?>
  <h2>歌手名、作品名、ブランド名検索 <small>外部データベース連携検索</small> </h2>
  <h3>anison.info連携検索モード </h3>
 
<FORM name=f action=search_anisoninfo_list.php method=get>
<INPUT type=radio value=song name=m id="song" "><label for="song">曲 (よみがなの一部でOK)</label>
<INPUT type=radio checked value=pro name=m id="pro" "><label for="pro">作品</label>
<INPUT type=radio value=person name=m id="person" "><label for="person">人物</label>
<INPUT type=radio value=mkr name=m id="mkr" "><label for="mkr">制作(ブランド)</label>
<!---
<INPUT type=radio value=rec name=m id="rec" "><label for="rec">音源</label>
<INPUT type=radio value=pgrp name=m id="pgrp" "><label for="pgrp">関連情報</label>
--->
<BR />
<INPUT name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?> class="searchtextbox" >
<!---
  <div> 結果表示順(同じ検索ワード内) <br />
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
<INPUT type=submit value="検索" class="btn btn-default "><BR/ ><BR />

<span id="selectTag">
</span>

</FORM>
  <h3>banditの隠れ家連携検索モード </h3>
  

  歌手名検索 
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="2" />
  <input type="submit" value="検索" class="btn btn-default ">
  </form>
  <br />
  ゲームタイトル検索 
  <form action="searchbandit.php" method="GET" style="display: inline"/>
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="3" />
  <input type="submit" value="検索" class="btn btn-default ">
  </form>
  <br />
  ゲームブランド検索 
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox">
  <input type="hidden" name="column" value="1" />
  <input type="submit" value="検索" class="btn btn-default ">
  </form>
  <br />

  
  (キーワードでインターネット上のデータベースサイトから曲名を検索し、その曲名でローカルにファイルがあるかを検索)<br />
  (登録されてない曲は見つけられません。)<br />
  (曲名の一部を含む別の曲とかも検索結果に出ちゃいます。ありがちな1単語の曲名だとたくさん結果に出てきてしまうので注意してね)<br />
  (網羅されてない新しい曲とか、特殊文字（★とか）が曲名に入っていると見つからない可能性があるので改めてファイル名検索してみて)
</div>


<hr>



</body>
</html>
