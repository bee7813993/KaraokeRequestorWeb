<html>
<head>
<?php 

$lister_dbpath = "\list\List.sqlite3";
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
      <a href="search_listerdb.php" class="btn btn-default center-block" >作品名 </a>
    </div>
    <div class="col-xs-4 col-md-4">
      <a href="search_listerdb_artist.php" class="btn btn-default center-block" >歌手名 </a>
    </div>
    <div class="col-xs-4 col-md-4 ">
      <a href="search_listerdb_filename_index.php" class="btn btn-primary center-block" >ファイル名 </a>
    </div>
  </div>
</div>

<div class="container  ">
<h1> ファイル名検索 </h1>

<form action="search_listerdb_filename_songlist.php" method="GET" >
  <div class="form-group">
    <label>検索ワード （ファイル名の一部）</label>
    <input type="test" name="filename" id="filename" class="form-control" placeholder="ファイル名の一部">
  </div>
  <button type="submit" class="btn btn-default">Submit</button>
</form>

</div>

</body>
</html>


<?php

?>