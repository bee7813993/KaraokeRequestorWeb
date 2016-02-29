
<?php
require_once 'commonfunc.php';
$word = '';
if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
    if($historylog == 1){
        searchwordhistory('file:'.$word);
    }
}
if(empty($word)){
$searchword = "";
}else{
$searchword = $word.' '.urldecode($config_ini["BGVfolder"]);
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

  <title>BGV検索</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php
shownavigatioinbar('searchreserve.php');

?>


<hr />
<h2>ＢＧＶファイル名(曲名)検索 </h2>
  <form action="search_bgv.php" method="GET">

<div class="col-xs-12 col-sm-12" >    検索ワード(ファイル名) </div>
  <div class="col-xs-12 col-sm-9" >
  <input type="text" name="searchword" class="searchtextbox" placeholder="背景動画を検索します"
  <?php
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
  ?>
  >
  </div>
  

  <div>
  </select>
  <div class="col-xs-12 col-sm-3" >
  <input type="submit" value="検索" class="btn btn-default ">
  </div>
  </div>
  <div class="clearleftfloat"> 
  </form>
  and検索は スペース 区切り<br>
  or検索は |(半角) 区切り<br>
  not検索はnotにしたい単語の先頭に!(半角)<br>
  
  全件検索は*(半角)でいけるっぽい。<br><br>
  </div>
  <?php
  	if ( empty ($searchword)){
  		
  	}else {
  	    //$result_count =  searchresultcount_fromkeyword(urlencode($searchword));
  	    //echo $word."の検索結果 : "; //.$result_count.'件';
  	    
    PrintLocalFileListfromkeyword_ajax(($searchword),$l_order,'searchresult',1);
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



</body>
</html>
