<?php

if (setlocale(LC_ALL, 'ja_JP.UTF-8','Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}

require_once 'commonfunc.php';

if(array_key_exists("nicoid", $config_ini)) {
    $nicologinid = urldecode($config_ini["nicoid"]);
}

if(array_key_exists("nicopass", $config_ini)) {
    $nicopass = $config_ini["nicopass"];
}

$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}

$errmsg = '';
if(nicofuncenabled()) {

  if(array_key_exists("nicoid", $_REQUEST)) {
    $nicoid = $_REQUEST["nicoid"];
    
    require_once 'nicodownloader.php';
    
    $nd = new NicoDownload();
    $nd->LoginEmail = $nicologinid;
    $nd->LoginPassword = $nicopass;
    $nd->WorkDir = $_SERVER["TMP"].'\\';
    $nd->DownloadDir = urldecode($config_ini["downloadfolder"]);
    
    //var_dump($nd);
    
    if(substr( $nd->CheckVideoID($nicoid),0,2) == 'nm' ){
        $errmsg = 'nmで始まるIDは動画ではないので対応していません。（ニコニコムービーメーカー）';
    }else
    {
        $rtDl = $nd->Download($nicoid);
    //print $rtDl;
        if($rtDl !== false){
          $downfilename = mb_convert_encoding($rtDl["filePath"],'UTF-8','auto'); 
        }
    }
  }
}
?>
<html lang="ja">
<head>
<?php 
print_meta_header();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">


    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>    

<link href="video-js/video-js.css" rel="stylesheet" type="text/css">
<script src="video-js/video.js"></script>
<script>
videojs.options.flash.swf = "video-js/video-js.swf";
</script>

<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php

shownavigatioinbar('searchreserve.php');

if (setlocale(LC_ALL, 'Japanese_Japan.65001') === false) {
    // print('Locale not found: Japanese_Japan.65001');
    //exit(1);
}


if(!empty($downfilename) ){
  $downfilename_base=basename_jp($downfilename);

  print $downfilename_base.'のダウンロードに成功<br />';
  print "time : ".$nd->DownloadStatus["total_time"].", size : ".$nd->DownloadStatus["size_download"];

  
  $previewpath = $previewpath = "http://" . $everythinghost . ":81/" . urlencode($downfilename);
  
  $dlpathinfo = pathinfo($downfilename_base);
  
  $filetype = '';
  if($dlpathinfo['extension'] === 'mp4'){
      $filetype = ' type="video/mp4"';
  }else if($dlpathinfo['extension'] === 'flv'){
      $filetype = ' type="video/x-flv"';
  }else {
      print "この動画形式は対応していません";
      die;
  }
  print "<div>\n";
  print make_preview_modal($downfilename,'preview_modal');
  print "</div>\n";

  print<<<EOD
<p>
このファイルをリクエスト 
</p>
<form action="request_confirm.php" method="post">
<input type="hidden" name="filename" id="filename" value="
EOD;

echo $downfilename_base;
  print<<<EOD
">
<input type="hidden" name="fullpath" id="fullpath" value="
EOD;

  echo $downfilename.'" />';
  if(is_numeric($selectid)){
      print '<input type="hidden" name="selectid" class="searchtextbox" value='.$selectid.' />';
  }
  print<<<EOD
<input type="submit" value="リクエスト" class="btn btn-default" />
</form>
<hr />
EOD;

if($dlpathinfo['extension'] === 'mp4'){
  print '<form enctype="multipart/form-data" action="mp4mix_audio.php" method="POST";>';
  print '<input type="hidden" name="MAX_FILE_SIZE" value="900000000" />';
  print '<input type="hidden" name="basefilename" id="basefilename"  value='.urlencode($downfilename).'>';
  print '<div class="form-group">';
  print '  <label>音源差し替え<small>手元の端末内にあるm4aやmp3の音楽ファイルに音源を差し替えることが出来ます</small></label>';
  print '  <input name="userfile" type="file" id="InputFile" class="form-control" />';
  if(is_numeric($selectid)){
      print '<input type="hidden" name="selectid" class="searchtextbox" value='.$selectid.' />';
  }
  print '  <input type="submit" class="btn btn-default" value="音源差替" />';
  print '</div>';

}

}else{
  if(!empty($nicoid))
    echo $nicoid."のダウンロードに失敗しました\n";
    
  if(!empty($errmsg)){
    echo '<p>'.$errmsg.'</p>';
  }


  if(nicofuncenabled()){
    //echo "<p>再試行</p>\n";
    print<<<EOD
<div class="container ">
  <form name="nicodownload" method="post" action="nicodownload_recv.php">
  <div class="form-group">
    <label>ニコニコ動画ID (smXXXXXX等)</label>
    <input class="form-control" type="text" name="nicoid"  value="" placeholder="sm000000等の動画ID"/>
    <input type="submit" class="btn btn-default btn-lg" value="Download (押すとダウンロードが終わるまでしばらく待ちます)" />
  </div>
</div>
EOD;
  }else {
    print "ニコニコ動画ダウンロード機能は有効になっていません";
  }
}
?>
</body>
</html>