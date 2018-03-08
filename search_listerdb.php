<html>
<head>
<?php 

$lister_dbpath = "List.sqlite3";
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
   
   $categorylist_json = file_get_contents('http://localhost/search_listerdb_head_json.php?list=1&lister_dbpath='.$lister_dbpath);
   if(!$categorylist_json) {
      $errmsg = 'カテゴリーリストの取得に失敗';
   }else {
      $categorylist = json_decode($categorylist_json,true);
   }

?>

</head>
<body>

<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

foreach ($categorylist as $category ){
$cur_category = $category["program_category"];
$url = 'http://localhost/search_listerdb_head_json.php?program_category='.$category["program_category"].'&lister_dbpath='.$lister_dbpath;
if($cur_category === NULL ) {
  $cur_category = 'その他';
  $url = 'http://localhost/search_listerdb_head_json.php?program_category=ISNULL'.'&lister_dbpath='.$lister_dbpath;
}

print '<h2> ' . $cur_category . '</h2>';
$headlist_json = file_get_contents($url);
if(!$headlist_json) {
    '作品名Headerの取得に失敗';
    die();
}else {
    
}
$headlist = json_decode($headlist_json,true);

//var_dump($headlist);
print '<div class="container bg-info ">';
print '  <div class="row">';
$count = 1;
foreach ($kana_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
$count = 1;
foreach ($alpha_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';

print '  <div class="row">';
$count = 1;
foreach ($num_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink('その他', $headlist).'</div>';
print '  </div>';

print '</div>';
}
?>

</body>
</html>
<?php

?>