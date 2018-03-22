<?php 
require_once 'commonfunc.php';
if(!isset($includepage) )
$includepage = "";

if(array_key_exists("includepage", $_REQUEST)) {
    $includepage = $_REQUEST["includepage"];
}
if(!isset($lister_dbpath) )
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

function checkandbuild_headerlink( $oneheader, $headerlist ,$lister_dbpath) {
//    global $lister_dbpath;
    foreach($headerlist['data']  as $key => $value) {
    //print $oneheader.$value["found_head"];
        if( $oneheader === $value["found_head"] ) {
            $searchcategory = $headerlist["program_category"];
            if($headerlist["program_category"] === 'ISNULL' ) {
                $searchcategory = 'ISNULL';
            }
        
            // URL Sample http://localhost/search_listerdb_programlist_fromhead.php?start=0&length=10&category=%E3%82%B2%E3%83%BC%E3%83%A0&header=%E3%82%89
            $whereword = urlencode('found_head='.$value["found_head"]) ;
            $url='<a class="btn btn-primary center-block" href="search_listerdb_programlist_fromhead.php?start=0&length=50&category='.urlencode($searchcategory).'&header='.$oneheader.'&lister_dbpath='.$lister_dbpath.'"> '. $oneheader .'</a>';
            return $url;
        }
    }
    $nolinkbtn = '<button type="button" class="btn btn-default btn-block" disabled="disabled" >'.$oneheader.'</button>';
    return $nolinkbtn;
}

   $errmsg = "";
   $geturl = 'http://localhost/search_listerdb_head_json.php?list=1&lister_dbpath='.$lister_dbpath;
   $categorylist_json = file_get_contents($geturl);
   if(!$categorylist_json) {
      $errmsg = 'カテゴリーリストの取得に失敗';
   }else {
      $categorylist = json_decode($categorylist_json,true);
   }
   if(!$categorylist) {
      $errmsg = 'カテゴリーリストのJSON parse 失敗';
      print $geturl;
      print $categorylist_json;
   }


if(empty($includepage)){
print <<<EOM
<html>
<head>
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
</head>
<body>
EOM;
shownavigatioinbar('searchreserve.php');
}
?>
<div class="container ">
  <div class="row ">
    <div class="col-xs-4 col-md-4  ">
      <a href="search_listerdb_program_index.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-primary center-block" >作品名 </a>
    </div>
    <div class="col-xs-4 col-md-4">
      <a href="search_listerdb_artist.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-default center-block" >歌手名 </a>
    </div>
    <div class="col-xs-4 col-md-4 ">
      <a href="search_listerdb_filename_index.php?lister_dbpath=<?php echo $lister_dbpath;?>" class="btn btn-default center-block" >検索（ファイル名など） </a>
    </div>
  </div>
</div>
<div class="container  ">

<h2> 新しく更新された動画 </h2>
<div class="form-group">
<a class="btn btn-primary" href="search_listerdb_songlist.php?datestart=<?php echo date('Y-m-d', strtotime("-1 month"));?>&lister_dbpath=<?php echo $lister_dbpath;?>" role="button">過去1か月</a>
<a class="btn btn-primary" href="search_listerdb_songlist.php?datestart=<?php echo date('Y-m-d', strtotime("-2 month"));?>&lister_dbpath=<?php echo $lister_dbpath;?>" role="button">過去2か月</a>
<a class="btn btn-primary" href="search_listerdb_songlist.php?datestart=<?php echo date('Y-m-d', strtotime("-3 month"));?>&lister_dbpath=<?php echo $lister_dbpath;?>" role="button">過去3か月</a>
</div>

<h1> 作品名インデックス検索 </h1>
<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

$nullcategory_exists = 0;
foreach ($categorylist as $category ){
$cur_category = $category["program_category"];
$url = 'http://localhost/search_listerdb_head_json.php?program_category='.urlencode($category["program_category"]).'&lister_dbpath='.$lister_dbpath;
if($cur_category === NULL ) {
  $nullcategory_exists++;
  continue;
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
  print '    <div class="col-xs-2 col-md-1 center-block">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 center-block btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
$count = 1;
foreach ($alpha_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';

print '  <div class="row">';
$count = 1;
foreach ($num_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink('その他', $headlist, $lister_dbpath).'</div>';
print '  </div>';

print '</div>';
}

// その他のカテゴリーは最後
if($nullcategory_exists > 0 ){
  $cur_category = 'その他';
  $url = 'http://localhost/search_listerdb_head_json.php?program_category=ISNULL'.'&lister_dbpath='.$lister_dbpath;

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
  print '    <div class="col-xs-2 col-md-1 center-block">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 center-block btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
$count = 1;
foreach ($alpha_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';

print '  <div class="row">';
$count = 1;
foreach ($num_list as $kana) {
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink($kana, $headlist, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 btn">&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
print '  <div class="row">';
  print '    <div class="col-xs-2 col-md-1">'.checkandbuild_headerlink('その他', $headlist, $lister_dbpath).'</div>';
print '  </div>';

print '</div>';


}
?>
</div>
<?php
if(empty($includepage)){
print <<<EOM
</body>
</html>
EOM;
}

?>