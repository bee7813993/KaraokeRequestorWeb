<html>
<head>
<?php 
require_once 'commonfunc.php';

print_meta_header();

// リスト表示名をファイル名とするか (今のところハードコーディング)
$displayfilename_flg = true;

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
  <title>ピックアップ曲表示</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
</head>
<body>

<?php
shownavigatioinbar();
?>


<div class="container">

<?php

function logtocmd($msg){
  //print(mb_convert_encoding("$msg\n","SJIS-win"));
  error_log($msg."\n", 3, 'ykrdebug.log');
}

$limitfilename = "";

if( !empty($_REQUEST['data']) ){
   $limitfilename = $_REQUEST['data'];
}else {
   print "曲リストの指定がありません";
   die();
}


if( is_valid_url($limitfilename) ){
    $url = $limitfilename;
}else {
    $url = 'http://localhost/'.$limitfilename;
}
$json = file_get_html_with_retry($url);
if($json === NULL){
   print "<p> 曲リストが見つかりませんでした </p>";
   die();
}

$limitlist_array = json_decode($json,true);
if($limitlist_array === NULL){
   print "<p> 曲リストが見つかりませんでした </p>";
   die();
}
/*
print "<pre>";
print $json;

var_dump($limitlist_array);
print "</pre>";
*/


print "<h1>";
print $limitlist_array["title"];
print "</h1>";

print '<a class="btn btn-default" data-toggle="collapse" href=".llsonginfo"> 全曲表示 </a>';
$catcount = 0;
foreach($limitlist_array["category"] as $category1 ){
    $catid = 'catid_'.$catcount;
    $catcount++;
    print "<h2>";
    print '<button type="button" class="btn btn-primary btn-block returnbtn" data-toggle="collapse" data-target="#'.$catid.'">';
    print "<strong>";
    print($category1["name"]);
    print "</strong>";
    print '</button>';
    print "</h2>";
    print '<div id='.$catid.' class="collapse llsonginfo">';
    print '   <div class="panel panel-default">';
    print '      <div class="panel-body">';
    
    if(!empty($category1["song"])){
        foreach($category1["song"] as $songinfo){
            print '<div class="divid0 panel panel-primary"> ';
            print '<div class="panel-heading " ><strong>';
            print '<a class="btn-primary" href="search_listerdb_filelist.php?anyword='.urlencode($songinfo["title"]).'"  >';
            print $songinfo["title"];
            print '</a >';

            print "</strong></div>";
            print '<div class="panel-body">';
            print '<div class="container">';
            if(!empty($songinfo["artist"])){
                print '<div class="col-xs-12 col-sm-6 " >';
                print $songinfo["artist"];
                print '</div>';
            }
            if(!empty($songinfo["songinfo"])){
                foreach($songinfo["songinfo"] as $songinfo_d){
                    print '<div class="col-xs-12 col-sm-6 " >';
                    print $songinfo_d;
                    print '</div>';
                }
            }
            print '</div> ';//container
            if(!empty($songinfo["file"])){
                //  print '<div class="list-group">';
                foreach($songinfo["file"] as $files){
                    // Check File exists 
                    $fullpath = fileexistcheck($files["filename"]);
                    $classdisabled = "";
                    if(empty($fullpath)) {
                       $classdisabled = ' disabled ';
                    }
                // print '<pre>'.var_dump($files).'</pre>';
                    $displayfilename = urlencode($songinfo["title"]);
                    if($displayfilename_flg) {
                        if(array_key_exists("filename", $files)) {
                            $displayfilename = urlencode($files["filename"]);
                        }
                    }
                    $link = 'request_confirm.php?filename='.$displayfilename;
                    if(array_key_exists("filename", $files)) {
                        $link = $link.'&fullpath='.urlencode($files["filename"]);
                    }
                    // flags check
                    foreach ($files["flags"] as $flagname ){
                       if( $flagname === "shop_karaoke" || $flagname === "配信" ){
                           $link = $link.'&shop_karaoke=1';
                           $classdisabled = "";
                       }
                       if( $flagname === "BGV"  ){
                           $link = $link.'&forcebgv=1';
                           if(array_key_exists("bgvfile", $files)) {
                              $link = $link.'&bgvfile='.urlencode($files["bgvfile"]);
                           }
                       }
                       
                    }
                    if(empty($classdisabled)) {
                    print '<a href='.$link.' class="list-group-item divid10" style="overflow: auto;" >';
                    }else {
                    print '<a  class="list-group-item divid10 disabled" style="overflow: auto;" >';
                    }
//                    print '<span class="col-xs-1 col-sm-1  " >'.$files["kind"].'</span>';
                    print '<span class="label label-primary " >'.$files["kind"].'</span>';
                    // print '<div class="col-xs-11 col-sm-11  divid11" ><span><a href='.($link).' class="btn btn-primary">リクエスト</a></span> ';
                    print '<span>';
                    foreach($files["flags"] as $key => $fileinfo){
                            print '<span class="label label-success">';
                            print $fileinfo;
                            print '</span> ';
                    }
                    print '</span>';
                    if(array_key_exists("filename", $files) ) {
                        print '<span>';
                        print 'ファイル名：'.$files["filename"];
                        print '</span>';
                    }
                    print '</span>'; //divid11
                    print '</a>'; //divid10
                }
                //print '</div>'; // "list-group"
                
                
            }
            
            print '</div> ';//panel-body
            print '</div> ';//divid0
        
        }
    }
    print '</div>';
    print '</div>';
    print '</div>';
}

?>



</body>
</html>