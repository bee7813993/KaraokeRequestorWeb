<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
    if($historylog == 1){
        searchwordhistory('file:'.$word);
    }
}

if(array_key_exists("order", $_REQUEST)) {
    $l_order = $_REQUEST["order"];
}else{
    $l_order = '';
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
<link href="js/video-js.min.css" rel="stylesheet">
<script src="js/video.min.js"></script>
<script>
  videojs.options.flash.swf = "js/video-js.swf"
</script>
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

print '<div class="container">';


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
}else {
    print_everything_filenamesearch();
    die();
}
?>
<?php


function print_listerdb_search(){
    global $config_ini;


    $includepage = 1;
    if(array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = $config_ini['listerDBPATH'];
    }
    require 'search_listerdb_program_index.php';
}


function print_listerdb_fileonly(){
    global $config_ini;


    $includepage = 1;
    if(array_key_exists("listerDBPATH", $config_ini)) {
        $lister_dbpath = $config_ini['listerDBPATH'];
    }
    $filesearch = 1;
    require 'search_listerdb_filename_index.php';
}
?>

<?php

function print_everything_filenamesearch() {
    global $config_ini;
    global $word;
    global $selectid;
    global $result_count;
    global $l_order;
    global $connectinternet;


print <<<EOM
<hr />
<h2>ファイル名(曲名)検索 </h2>
  <form action="search.php" method="GET">
  <div class="container">
  <label>検索ワード(ファイル名) </label>
  
  <input type="text" name="searchword" id="filenamesearchword" class="searchtextbox" placeholder="歌手名作品名検索は下の外部DB連携で検索できます"
EOM;
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
print '   />';
  if(is_numeric($selectid)){
      print '<input type="hidden" name="selectid" class="searchtextbox" value='.$selectid.' />';
  }
print <<<EOM
  <input type="submit" value="検索" id="filenamesearchsubmit" class="btn btn-default " />
  </div>
  </form>
  <div class="clearleftfloat"> 
  and検索は スペース 区切り<br />
  or検索は |(半角) 区切り<br />
  not検索はnotにしたい単語の先頭に!(半角)<br />
  全件検索は*(半角)でいけるっぽい。<br/ ><br />
  </div>
EOM;
  	if ( empty ($word)){
  		
  	}else {
  	    // $result_count =  searchresultcount_fromkeyword($word);
  	    echo $word."の検索結果 : ".$result_count.'件';
  	    
    PrintLocalFileListfromkeyword_ajax($word,$l_order,'searchresult',0,$selectid);
  	}
print '<hr />';
if($connectinternet != 1){
print <<<EOM
<a href="requestlist_only.php" >トップに戻る </a>

</body>
</html>
EOM;
die();
}
} 
?>
<?php
function print_everything_anisoninfosearch() {
    global $config_ini;
    global $l_q;
    global $selectid;


print <<< EOM
<div class="extsearch"> 
  <h2>歌手名、作品名、ブランド名検索 <small>外部データベース連携検索</small> </h2>
  <h3 id="searchanisoninfo" >anison.info連携検索モード </h3>
 
<FORM name=f action=search_anisoninfo_list.php method=get>
  <div class="form-group">
   <div class="form-check form-check-inline">
    <label class="form-check-label">
        <INPUT class="form-check-input" type=radio value=song name=m id="song" >曲 
    </label>
    <label class="form-check-label">
        <INPUT class="form-check-input" type=radio checked value=pro name=m id="pro" >作品
    </label>
    <label class="form-check-label">
        <INPUT  class="form-check-input" type=radio value=person name=m id="person" >人物
    </label>
    <label class="form-check-label">
        <INPUT  class="form-check-input" type=radio value=mkr name=m id="mkr" >制作(ブランド)
    </label>
<!---
  <div class="form-check form-check-inline">
    <label class="form-check-label">
        <INPUT  class="form-check-input" type=radio value=rec name=m id="rec" "><label for="rec">音源</label>
    </label>
  </div>
  <div class="form-check form-check-inline">
    <label class="form-check-label">
        <INPUT  class="form-check-input" type=radio value=pgrp name=m id="pgrp" "><label for="pgrp">関連情報</label>
    </label>
  </div>
--->
   </div>
  </div>
  <div class="form-group">
      <label >検索ワード (よみがなの一部でOK)<small>空白で年代指定でその年全検索</small> </label>
EOM;
$value_name = '';
if(isset($l_q)) {
    $value_name = 'value="'.$l_q.'"';
}
print '      <INPUT name=q '.$value_name .' class="searchtextbox" >';
print <<< EOM
  </div>
EOM;

if(!empty($selectid) ) {
  print '<input type="hidden" name="selectid" value="';
  print $selectid;
  print '" />';
}
print <<< EOM
<div class="form-group form-inline">
  <div class="form-group ">
      <label > 放映／発売年指定 </label>
      <select class="form-control" name="year" >
          <option value="" > 放映／発売年指定 </option>
EOM;

  $year = date('Y');
  for( $i = $year + 1; $i >= 1953 ; $i-- ){
      print '<option value="'.$i.'"> '.$i.' </option>';
  }

print <<< EOM
      </select>
  </div>
  <div class="form-group">
      <label > ジャンル指定 </label>
      <select class="form-control" name="genre" >';
      <option value="" selected>ジャンル指定 </option>
      <option value="anison">アニメーション/特撮/ゲーム</option>
      <option value="anime">アニメーション</option>
      <option value="tv">　テレビアニメーション</option>
      <option value="vd">　ビデオアニメーション</option>
      <option value="mv">　劇場アニメーション</option>
      <option value="wa">　Webアニメーション</option>
      <option value="sfx">テレビ特撮'</option>
      <option value="game">ゲーム</option>
      <option value="radio">ラジオ</option>
      <option value="wradio">Webラジオ</option>
      <option value="other">その他</option>
      </select>
  </div>
</div>

<INPUT type=submit value="検索" class="btn btn-default "><BR/ ><BR />

<span id="selectTag">
</span>

</FORM>
</div> <!--- extsearch ---> 

EOM;
}
?>
<?php 
function print_everything_banditsearch() {

print <<< EOM
<div class="extsearch"> 

  <h3 id="searchbandit" >banditの隠れ家連携検索モード </h3>
  

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
</div> <!--- extsearch ---> 

EOM;
}
?>


<?php 

if(!array_key_exists("searchitem_o", $config_ini) || !array_key_exists("searchitem", $config_ini)) {
    print_everything_filenamesearch();
    print_everything_anisoninfosearch();
    print_everything_banditsearch();
    
} else {
$disp_search_order = array();
$o_srt=$config_ini['searchitem_o'];
asort($o_srt);
foreach ( $o_srt as $value){

  foreach ($config_ini['searchitem_o'] as $k => $v){
      if($value == $v) {
         $disp_search_order[] =  $k ;
      }
  }
}


foreach ($disp_search_order  as $v){

    switch($v) {
        case 0:
            if(checkbox_check($config_ini['searchitem'], "listerDB_file" )) {
                print_listerdb_fileonly();
                print '<hr />';
            }
            break;
        case 1:
            if(checkbox_check($config_ini['searchitem'], "listerDB" )) {
                print_listerdb_search();
            }
            break;
        case 2:
            if(checkbox_check($config_ini['searchitem'], "filesearch_e" )) {
                print_everything_filenamesearch();
            }
            break;
        case 3:
            if(checkbox_check($config_ini['searchitem'], "anisoninfo_e" )) {
                print_everything_anisoninfosearch();
            }
            break;
        case 4:
            if(checkbox_check($config_ini['searchitem'], "bandit_e" )) {
                print_everything_banditsearch();
            }
            break;
    }
}
}


/****
    if(checkbox_check($config_ini['searchitem'], "listerDB" )) {
        print_listerdb_search();
    }

    if(checkbox_check($config_ini['searchitem'], "filesearch_e" )) {
        print_everything_filenamesearch();
    }
    if(checkbox_check($config_ini['searchitem'], "anisoninfo_e" )) {
        print_everything_anisoninfosearch();
    }
    if(checkbox_check($config_ini['searchitem'], "bandit_e" )) {
        print_everything_banditsearch();
    }
****/
?>
</div>


<hr>

</body>
</html>
