<?php 

require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc.php';

$lister_dbpath = "list\List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

$displayfrom=0;
$displaynum=50;
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


// アルファベット配列
$alpha_list = array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );

// 数字配列
$num_list = array( '1' ,'2' ,'3' ,'4' ,'5' ,'6' ,'7' ,'8' ,'9' ,'0' );

// かな配列
$kana_list = array( 'あ' ,'い' ,'う' ,'え' ,'お' ,'か' ,'き' ,'く' ,'け' ,'こ' ,'さ' ,'し' ,'す' ,'せ' ,'そ' ,'た' ,'ち' ,'つ' ,'て' ,'と' ,'な' ,'に' ,'ぬ' ,'ね' ,'の' ,'は' ,'ひ' ,'ふ' ,'へ' ,'ほ' ,'ま' ,'み' ,'む' ,'め' ,'も' ,'や' ,'ゐ' ,'ゆ' ,'ゑ' ,'よ' ,'ら' ,'り' ,'る' ,'れ' ,'ろ' ,'わ' ,'を' ,'ん');

function checkandbuild_headerlink( $oneheader, $headerlist ) {
    global $lister_dbpath;
    global $linkoption;
    
    foreach($headerlist['data']  as $key => $value) {
    //print $oneheader.$value["found_head"];
        if( $oneheader === $value["found_head"] ) {
            $searchcategory = $headerlist["program_category"];
            if($headerlist["program_category"] === 'ISNULL' ) {
                $searchcategory = 'ISNULL';
            }
        
            // URL Sample http://localhost/search_listerdb_programlist_fromhead.php?start=0&length=10&category=%E3%82%B2%E3%83%BC%E3%83%A0&header=%E3%82%89
            $whereword = urlencode('found_head='.$value["found_head"]) ;
            $url='<a class="btn btn-primary center-block" href="search_listerdb_programlist_fromhead.php?start=0&length=50&category='.urlencode($searchcategory).'&header='.urlencode($oneheader).'&'.$linkoption.'"> '. $oneheader .'</a>';
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
  <title>リスターDB歌い手検索画面</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>

<?php
   $errmsg = "";
   $geturl = 'http://localhost/search_listerdb_artistmany_json.php?list=1&lister_dbpath='.$lister_dbpath.'&start='.$displayfrom.'&length='.$displaynum;
   $artistmanylist_json = file_get_contents($geturl);
   if(!$artistmanylist_json) {
      $errmsg = '歌手名リストの取得に失敗';
   }else {
      $artistmany = json_decode($artistmanylist_json,true);
   }
   if(!$artistmany) {
      $errmsg = '歌手名リストのJSON parse 失敗';
      print $geturl;
      print $artistmanylist_json;
   }

?>

</head>
<body>
<?php
shownavigatioinbar('searchreserve.php');
showuppermenu('song_artist',$linkoption);
?>
<div class="container  ">



<h1> 歌手名検索 </h1>

<form action="search_listerdb_artistname_artistlist.php" method="GET" >
  <div class="form-group">
    <label>検索ワード （歌手名）</label>
    <input type="test" name="artist" id="artist" class="form-control" placeholder="歌手名">
    <div class="btn-group" data-toggle="buttons">
	<label class="btn btn-default active">
		<input type="radio" name="match" value="part" autocomplete="off" checked> 部分一致
	</label>
	<label class="btn btn-default">
		<input type="radio" name="match" value="full" autocomplete="off"> 完全一致
	</label>
    </div>
  </div>
<?php
if(!empty($lister_dbpath))
    print '<input type="hidden" name="lister_dbpath" value="'.$lister_dbpath.'" />';
if(!empty($selectid))
    print '<input type="hidden" name="selectid" value="'.$selectid.'" />';
?>
    <button type="submit" class="btn btn-default">検索</button>
</form>

</div>

<div class="container  ">
<h1> 歌手名で曲数の多い順 </h1>
<?php
if(!empty($errmsg)){
  print $errmsg;
  die();
}
print '<div class="container">';
print '  <div class="row bg-info">';

foreach ($artistmany['data'] as $artistname ){

print '    <div class="col-xs-12 col-md-6" >';
print '    <div class="btn-toolbar" style="margin-bottom: 5px" >';
if(empty($artistname['song_artist']) ){
    print '<a class="btn btn-primary btn-block indexbtnstr" href="search_listerdb_songlist.php?artist='.'ISNULL'.'&'.$linkoption.'">';
    print '【歌手名未登録】'.'（'.$artistname['COUNT'].'）' ;
}else {
    print '<a class="btn btn-primary btn-block indexbtnstr_lg" href="search_listerdb_songlist.php?artist='.urlencode($artistname['song_artist']).'&'.$linkoption.'">';
    print $artistname['song_artist'].'（'.$artistname['COUNT'].'）' ;
}
print '</a>';
print '    </div>';
print '    </div>';
//search_listerdb_songlist.php?artist=Duca%2CRita%2C%E8%8C%B6%E5%A4%AA&match=
}
print '  </div>';
print '  </div>';



$urlparams = "";
if( !empty($filename) ){
    $urlparams = $urlparams.'filename='.urlencode($filename);
}
if( !empty($header) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'header='.$header;
}
if( !empty($category) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'category='.urlencode($category);
}
if( !empty($draw) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.'draw='.$draw;
}
if( !empty($lister_dbpath) ){
    if(strlen($urlparams) > 0) {
         $urlparams = $urlparams.'&';
    }
    $urlparams = $urlparams.$linkoption;
}


print '<div class="container  ">';
print '  <div class="row ">';
print '    <div class="col-xs-4 col-md-4  ">';
if($displayfrom > 0 ) {
    $nextstart = (($displayfrom - $displaynum ) <= 0) ? 0 : $displayfrom - $displaynum;
    print '      <a href="search_listerdb_artist.php?'.$urlparams.'&start='.$nextstart.'&length='.$displaynum.'" class="btn btn-default center-block" >前の'.$displaynum.'件 </a>';
}
print '    </div>';
print '    <div class="col-xs-4 col-md-4">';
print '    </div>';
print '    <div class="col-xs-4 col-md-4 ">';
if($artistmany['recordsTotal'] > ($displayfrom + $displaynum) ) {
    print '      <a href="search_listerdb_artist.php?'.$urlparams.'&start='.($displaynum+$displayfrom).'&length='.$displaynum.'" class="btn btn-default center-block" >次の'.$displaynum.'件</a>';
}
print '    </div>';
print '  </div>';
print '</div>';

?>
</div>
</body>
</html>
<?php

?>