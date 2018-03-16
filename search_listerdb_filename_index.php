<html>
<head>
<?php 

$lister_dbpath = "list\List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

// アルファベット配列
$alpha_list = array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );

// 数字配列
$num_list = array( '1' ,'2' ,'3' ,'4' ,'5' ,'6' ,'7' ,'8' ,'9' ,'0' );

// かな配列
$kana_list = array( 'あ' ,'い' ,'う' ,'え' ,'お' ,'か' ,'き' ,'く' ,'け' ,'こ' ,'さ' ,'し' ,'す' ,'せ' ,'そ' ,'た' ,'ち' ,'つ' ,'て' ,'と' ,'な' ,'に' ,'ぬ' ,'ね' ,'の' ,'は' ,'ひ' ,'ふ' ,'へ' ,'の' ,'ま' ,'み' ,'む' ,'め' ,'も' ,'や' ,'ゐ' ,'ゆ' ,'ゑ' ,'よ' ,'ら' ,'り' ,'る' ,'れ' ,'ろ' ,'わ' ,'を' ,'ん');

function checkandbuild_headerlink( $oneheader, $headerlist ) {
    global $lister_dbpath;
    foreach($headerlist['data']  as $key => $value) {
    //print $oneheader.$value["found_head"];
        if( $oneheader === $value["found_head"] ) {
            $searchcategory = $headerlist["program_category"];
            if($headerlist["program_category"] === 'ISNULL' ) {
                $searchcategory = 'ISNULL';
            }
        
            // URL Sample http://localhost/search_listerdb_programlist_fromhead.php?start=0&length=10&category=%E3%82%B2%E3%83%BC%E3%83%A0&header=%E3%82%89
            $whereword = urlencode('found_head='.$value["found_head"]) ;
            $url='<a href="search_listerdb_programlist_fromhead.php?start=0&length=50&category='.$searchcategory.'&header='.$oneheader.'&lister_dbpath='.$lister_dbpath.'"> '. $oneheader .'</a>';
            return $url;
        }
    }
    return $oneheader;
}

?>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>リスターDB検索画面</title>
  <link type="text/css" rel="stylesheet" href="/css/style.css" />
  <script type="text/javascript" charset="utf8" src="/js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
   $errmsg = "";
?>

</head>
<body>
<div class="container  ">
  <div class="row ">
    <div class="col-xs-4 col-md-4  ">
      <a href="search_listerdb_program_index.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-default center-block" >作品名 </a>
    </div>
    <div class="col-xs-4 col-md-4">
      <a href="search_listerdb_artist.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-default center-block" >歌手名 </a>
    </div>
    <div class="col-xs-4 col-md-4 ">
      <a href="search_listerdb_filename_index.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-primary center-block" >検索（ファイル名など） </a>
    </div>
  </div>
</div>

<div class="container  ">
<h1> ファイル名検索 </h1>
<div class="bg-info" >

<form action="search_listerdb_songlist.php" method="GET" >
  <div class="form-group">
    <label>検索ワード （ファイル名の一部）</label>
    <input type="test" name="filename" id="filename" class="form-control" placeholder="ファイル名の一部">
  </div>
  <button type="submit" class="btn btn-default">検索</button>
</form>

</div>
</div>

<hr />
<div class="container  ">
<h1> 詳細検索 </h1>
<div class="bg-info" >
<form action="search_listerdb_songlist.php" method="GET" >
  <div class="form-group">
    <label>ファイル名</label>
    <input type="test" name="filename" id="filename" class="form-control" placeholder="ファイル名">
    <div class="form-group"><label>作品名</label><input type="text" class="form-control" name="program_name" value="" /></div>
    <div class="form-group"><label>歌手名</label><input type="text" class="form-control" name="artist" value="" /></div>
    <div class="form-group"><label>動画製作者</label><input type="text" class="form-control" name="worker" value="" /></div>
    <div class="form-group">
    <label>更新日範囲 始め</label>
    <input type="date"  class="form-control" name="datestart" value="0" />
    </div>
    <div class="form-group">
    <label>更新日範囲 終わり</label>
    <input type="date"  class="form-control" name="dateend" value="0" />
    </div>
  </div>
    <div class="btn-group" data-toggle="buttons">
	<label class="btn btn-default active">
		<input type="radio" name="match" value="part" autocomplete="off" checked> 部分一致
	</label>
	<label class="btn btn-default">
		<input type="radio" name="match" value="full" autocomplete="off"> 完全一致
	</label>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-default">検索</button>
    </div>

</form>


</div>
</div>

</body>
</html>


<?php

?>