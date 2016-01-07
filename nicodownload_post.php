<?php

require_once 'commonfunc.php';

if(array_key_exists("nicoid", $config_ini)) {
    $nicologinid = urldecode($config_ini["nicoid"]);
}

if(array_key_exists("nicopass", $config_ini)) {
    $nicopass = $config_ini["nicopass"];
}

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
    $rtDl = $nd->Download($nicoid);
    //print $rtDl;
    if($rtDl !== false){
       $downfilename = mb_convert_encoding($rtDl["filePath"],'UTF-8','auto'); 
    }
}
}
?>
<html lang="ja">
<head>
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
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php
shownavigatioinbar('searchreserve.php');

if(nicofuncenabled()){
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
?>
</body>
</html>