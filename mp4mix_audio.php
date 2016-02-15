<?php

if (setlocale(LC_ALL, 'ja_JP.UTF-8','Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}

require_once 'commonfunc.php';

$remixerpath = 'l-smash\remuxer.exe';

$basefilename = '';
if(array_key_exists("basefilename",$_REQUEST)) {
   $basefilename = urldecode($_REQUEST["basefilename"]);
}
if(array_key_exists("userfile",$_FILES)){
    $filename=basename($_FILES['userfile']['name']);
    $filename = str_replace(array('/','\\', '?', ':', '*', '\"', '>', '<', '|'),array('／','￥','？','：','＊','”','＞','＜','｜'),$filename);
}


$uploaddir = urldecode($config_ini["downloadfolder"]);
$uploadfile = $uploaddir  .$filename;

if(empty($basefilename)){
   print "元の動画ファイルがありません";
   die;
}

$bfinfo = pathinfo($basefilename);
$basefilename_mod = $bfinfo['dirname'].'\\'.$bfinfo['filename'].'_changeaudio.'.$bfinfo['extension'];
$execcmd = $remixerpath.' -i "'.mb_convert_encoding($basefilename,"SJIS-win","UTF8").'"?1:alternate-group=1 -i "'.$_FILES['userfile']['tmp_name'].'"?1:alternate-group=2 -i "'.mb_convert_encoding($basefilename,"SJIS-win","UTF8").'"?2:alternate-group=3 -o "'.mb_convert_encoding($basefilename_mod,"SJIS-win","UTF8").'" 2>&1';
$execcmd_utf8 = $remixerpath.' -i "'.$basefilename.'"?1:alternate-group=1 -i "'.$_FILES['userfile']['tmp_name'].'"?1:alternate-group=2 -i "'.$basefilename.'"?2:alternate-group=3 -o "'.$basefilename_mod.'"';
// print "execute : $execcmd_utf8 <br>\n";
exec($execcmd, $retstr);

$res = false;
foreach($retstr as $value){
if(strpos($value,"completed") !== FALSE) {
$res = true;
}
}
if($res === false){
print '<p>音声合成に失敗しました</p>';
print '<pre>';
var_dump($retstr);
print '</pre>';
die();
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

<title>音源差し替え確認</title>
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


if(!empty($basefilename_mod) ){
  $basefilename_mod_base=basename_jp($basefilename_mod);

  print $basefilename.'の音源差し替えに成功<br />';

  
  $previewpath = $previewpath = "http://" . $everythinghost . ":81/" . urlencode($basefilename_mod);
  
  $dlpathinfo = pathinfo($basefilename_mod);
  
  $filetype = '';
  if($dlpathinfo['extension'] === 'mp4'){
      $filetype = ' type="video/mp4"';
  }else if($dlpathinfo['extension'] === 'flv'){
      $filetype = ' type="video/x-flv"';
  }else {
      print "この動画形式は対応していません";
  }
  
  print "<hr>\n";
  
  print make_preview_modal($basefilename_mod,'preview_modal');

  print<<<EOD
<p>
このファイルをリクエスト 
</p>
<form action="request_confirm.php" method="post">
<input type="hidden" name="filename" id="filename" value="
EOD;

echo $basefilename_mod_base;
  print<<<EOD
">
<input type="hidden" name="fullpath" id="fullpath" value="
EOD;

echo $basefilename_mod;
  print<<<EOD
">
<input type="submit" value="リクエスト" class="btn btn-default">
</form>
EOD;

}else{
}
?>
</body>
</html>