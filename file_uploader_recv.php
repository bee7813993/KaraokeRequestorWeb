<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">

<meta http-equiv="X-UA-Compatible" content="IE=edge">
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

<title>ファイルアップロード確認</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<div class="container">
<p>
<?php
if (setlocale(LC_ALL, 'ja_JP.UTF-8','Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}

require_once 'commonfunc.php';
shownavigatioinbar('searchreserve.php');

if(array_key_exists("uploaddfilename",$_REQUEST)) {
    $filename=basename($_REQUEST["uploaddfilename"]);
    // print '移動予定先ファイル:'.$filename;
}else if(array_key_exists("userfile",$_FILES)){
    $filename=basename($_FILES['userfile']['name']);
}
    $filename=basename($_FILES['userfile']['name']);

$uploaddir = urldecode($config_ini["downloadfolder"]);
$uploadfile = $uploaddir  .$filename;

//$res = rename($_FILES['userfile']['tmp_name'], mb_convert_encoding($uploadfile,"SJIS-win","UTF8"));
$res = move_uploaded_file($_FILES['userfile']['tmp_name'],mb_convert_encoding($uploadfile,"SJIS-win","UTF8"));
if($res)
{
    echo " ";
}else {
    echo "アップロードに失敗しました\n";
    print '<pre>';
    print_r($_FILES);
    print '移動予定先ファイル:'.$uploadfile;
    print '</pre>';
    print '<a  class="btn btn-default" href="file_uploader.php" > 戻る </a>';
    die();
}


print $_FILES['userfile']['name'].'のアップロードに成功';
?>
</p>
<p>
このファイルをリクエスト
</p>
<form action="request_confirm.php" method="post">
<input type="hidden" name="filename" id="filename" value="<?php echo makesongnamefromfilename($_FILES['userfile']['name']); ?>">
<input type="hidden" name="fullpath" id="fullpath" value="<?php echo $uploadfile; ?>">
<input type="submit" value="リクエスト" class="btn btn-default">
</form>
</div>

</body>
</html>
