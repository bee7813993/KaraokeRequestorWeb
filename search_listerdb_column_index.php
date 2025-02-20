<?php 

require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc.php';

$lister_dbpath = "list\List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

$displayfrom=0;
$displaynum=5000;
if(array_key_exists("start", $_REQUEST)) {
    $displayfrom = $_REQUEST["start"];
}

if(array_key_exists("length", $_REQUEST)) {
    $displaynum = $_REQUEST["length"];
}

$selectid = '';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}

$linkoption = 'lister_dbpath='.$lister_dbpath;
if(!empty($selectid) ) $linkoption = $linkoption.'&selectid='.$selectid;

$target="";
if(array_key_exists("target", $_REQUEST)) {
    $target = $_REQUEST["target"];
}

switch ($target){
    case "maker_name":
        $column = 'substr(maker_ruby, 1, 1)';
        $columnname = "maker_ruby";
        $searchitem = '制作会社名';
    break;
    case "song_artist":
        $column = 'substr(found_artist_ruby, 1, 1)';
        $columnname = "found_artist_ruby";
        $searchitem = '歌手名';
    break;
    case "song_name":
        $column = 'substr(song_ruby, 1, 1)';
        $columnname = "song_ruby";
        $searchitem = '曲名';
    break;
    case "tie_up_group_name":
        $column = 'substr(tie_up_group_ruby, 1, 1)';
        $columnname = "tie_up_group_ruby";
        $searchitem = 'シリーズ';
    break;
    default:
        $column = 'substr(maker_ruby, 1, 1)';
        $columnname = "maker_ruby";
        $searchitem = '制作会社名';
    break;
}



// アルファベット配列
$alpha_list = array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );

// 数字配列
$num_list = array( '1' ,'2' ,'3' ,'4' ,'5' ,'6' ,'7' ,'8' ,'9' ,'0' );

// かな配列
$kana_list = array( 'あ' ,'い' ,'う' ,'え' ,'お' ,'か' ,'き' ,'く' ,'け' ,'こ' ,'さ' ,'し' ,'す' ,'せ' ,'そ' ,'た' ,'ち' ,'つ' ,'て' ,'と' ,'な' ,'に' ,'ぬ' ,'ね' ,'の' ,'は' ,'ひ' ,'ふ' ,'へ' ,'ほ' ,'ま' ,'み' ,'む' ,'め' ,'も' ,'や' ,'ゐ' ,'ゆ' ,'ゑ' ,'よ' ,'ら' ,'り' ,'る' ,'れ' ,'ろ' ,'わ' ,'を' ,'ん');

function checkandbuild_headerlink( $oneheader, $headerlist, $columnname, $columnname_ruby , $lister_dbpath ) {
    global $lister_dbpath;
    global $linkoption;
    global $searchitem;
    
    //substr(maker_ruby, 1, 1)
    $headerkey='substr('.$columnname_ruby.', 1, 1)';
    foreach($headerlist['data']  as $key => $value) {
    //print $oneheader.$value[$headerkey];
    $katakana_oneheader = mb_convert_kana($oneheader,'C');
        if( $oneheader === $value[$headerkey] || $katakana_oneheader === $value[$headerkey]) {
            // URL Sample http://localhost/search_listerdb_programlist_fromhead.php?start=0&length=10&category=%E3%82%B2%E3%83%BC%E3%83%A0&header=%E3%82%89
            // search_listerdb_column_json.php?list=1&lister_dbpath='.$lister_dbpath.'&start='.$displayfrom.'&length='.$displaynum.'&searchcolumn='.urlencode($column);
            $whereword = urlencode($columnname_ruby.' like \''.$value[$headerkey].'%\'');
            $url='<a class="btn btn-primary center-block indexbtnstr" href="search_listerdb_column_list.php?start=0&length=50&header='.urlencode($value[$headerkey]).'&searchcolumn='.$columnname.'&sqlwhere='.$whereword.'&searchitem='.urlencode($searchitem).'&'.$linkoption.'"> '. $oneheader .'</a>';
            return $url;
        }
    }
    $nolinkbtn = '<button type="button" class="btn btn-default btn-block" disabled="disabled" >'.$oneheader.'</button>';
    return $nolinkbtn;
}
print '<html>';
print '<head>';
print_meta_header();
?>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>リスターDB項目検索画面</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
?>


</head>
<body>
<?php
shownavigatioinbar('searchreserve.php');
?>

<?php
showuppermenu($target,$linkoption);
?>

<div class="container  ">

<h1> <?php echo $searchitem;?> 検索 </h1>

<div >
<label>検索ワード (<?php echo $searchitem;?>)</label>

<?php
    $linecounter = 0;
    $formtext = "";
    if($target == "maker_name" ){
        $formtext = $formtext . ' <input type="text" name="maker_name" id="maker_name" class="form-control" placeholder="'.$searchitem.'">';
        $linecounter++;
    }
    if($target == "song_artist" ){
        $formtext = $formtext . ' <input type="text" name="artist" id="artist" class="form-control" placeholder="'.$searchitem.'">';
        $linecounter++;
    }
    if($target == "song_name" ){
        $formtext = $formtext . ' <input type="text" name="filename" id="song_name" class="form-control" placeholder="'.$searchitem.'">';
        $linecounter++;
    }
    if($linecounter){
        print '<form action="search_listerdb_songlist.php" method="GET" >';
        print ' <div class="form-group"> ';
        print $formtext;
print <<<EOT
    <div class="btn-group" data-toggle="buttons">
	<label class="btn btn-default active">
		<input type="radio" name="match" value="part" autocomplete="off" checked> 部分一致
	</label>
	<label class="btn btn-default">
		<input type="radio" name="match" value="full" autocomplete="off"> 完全一致
	</label>
    </div>
  </div>
EOT;
if(!empty($lister_dbpath))
    print '<input type="hidden" name="lister_dbpath" value="'.$lister_dbpath.'" />';
if(!empty($selectid))
    print '<input type="hidden" name="selectid" value="'.$selectid.'" />';
print <<<EOT
    <button type="submit" class="btn btn-default">検索</button>
</form>
EOT;
    }
?>

<?php
    if($target == "tie_up_group_name" ){
print <<<EOT
<form action="search_listerdb_column_list.php" method="GET" >
  <div class="form-group">
EOT;
    print '<input type="text" name="tie_up_group_name" id="tie_up_group_name" class="form-control" placeholder="'.$searchitem.'">';
    print '<input type="hidden" name="searchcolumn" value="tie_up_group_name" />';
    print '<input type="hidden" name="searchitem" value="シリーズ" />';
print <<<EOT
  </div>
EOT;
if(!empty($lister_dbpath))
    print '<input type="hidden" name="lister_dbpath" value="'.$lister_dbpath.'" />';
if(!empty($selectid))
    print '<input type="hidden" name="selectid" value="'.$selectid.'" />';
print <<<EOT
    <button type="submit" class="btn btn-default">検索</button>
</form>
EOT;
}
?>

</div>

<div class="container  ">
<h1>  <?php echo $searchitem;?> インデックス検索 </h1>
<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}

// 項目ヘッダリスト取得
   $errmsg = "";
   $geturl = 'http://localhost/search_listerdb_column_json.php?list=1&lister_dbpath='.$lister_dbpath.'&start='.$displayfrom.'&length='.$displaynum.'&column='.urlencode($column);
   $columnlist_json = file_get_contents($geturl);
   if(!$columnlist_json) {
      $errmsg = '項目リストの取得に失敗';
   }else {
      $columnmany = json_decode($columnlist_json,true);
   }
   if(!$columnmany) {
      $errmsg = '項目リストのJSON parse 失敗';
      print $geturl;
      print $columnlist_json;
   }
//    print '<pre>';
// var_dump($columnlist_json);
//    print '</pre>';
// die();

print '<div class="container bg-info ">';
print '  <div class="row">';
$count = 1;
foreach ($kana_list as $kana) {
  print '    <div class="col-xs-2 col-md-1 indexbtn center-block" >'.checkandbuild_headerlink($kana, $columnmany, $target, $columnname, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 indexbtn center-block btn indexbtnstr" >&nbsp; </div>';
   if( ($count % 10) == 0 ) {
     $count = 1;
     print '  </div>';
     print '  <div class="row">';
   }else{
     $count++;
   }
  }else{
    $count++;
  }
}
print '  </div>';
if(headerlistcheck_column($alpha_list,$columnmany['data'],$column) != 0 ){
print '  <hr />';
print '  <div class="row">';
$count = 1;
foreach ($alpha_list as $kana) {
  print '    <div class="col-xs-2 col-md-1 indexbtn" >'.checkandbuild_headerlink($kana, $columnmany, $target, $columnname, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 indexbtn btn indexbtnstr" >&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
}
if(headerlistcheck_column($num_list,$columnmany['data'],$column) != 0 ){
print '  <hr />';
print '  <div class="row">';
$count = 1;
foreach ($num_list as $kana) {
  print '    <div class="col-xs-2 col-md-1 indexbtn" >'.checkandbuild_headerlink($kana, $columnmany, $target, $columnname, $lister_dbpath).'</div>';
// print $count;
  if( ($count % 5) == 0 ) {
    print '    <div class="col-xs-2 col-md-1 indexbtn btn indexbtnstr" >&nbsp; </div>';
    $count = 1;
  }else{
    $count++;
  }
}
print '  </div>';
}

print '</div>';

if($target == "song_artist" ){
  if(empty($linkoption))
  print '<a href="search_listerdb_artist.php" class="btn btn-primary" > 登録数の多い順歌手名リスト </a>';
  else 
  print '<a href="search_listerdb_artist.php?'.$linkoption.'" class="btn btn-primary" > 登録数の多い順歌手名リスト </a>';
  
}


?>
</div>
</body>
</html>
<?php

?>