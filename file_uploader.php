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
<script type="text/javascript">
function saveFilename()
{
document.all.upldfilename.value = document.all.InputFile.value;
}
</script>

<title>ファイルアップロード／ダウンロード指定</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
require_once 'commonfunc.php';
shownavigatioinbar('searchreserve.php');
ini_set('memory_limit', '1536M');
ini_set('post_max_size', '1024M');
ini_set('upload_max_filesize', '1024M');
?>
<div class="container">
  <form enctype="multipart/form-data" action="file_uploader_recv.php" method="POST" onsubmit="saveFilename()";>
  <input type="hidden" name="MAX_FILE_SIZE" value="900000000" />
  <input type="hidden" name="uploaddfilename" id="upldfilename" >
  <div class="form-group">
    <label for="InputFile">アップロードするファイルを指定</label>
    <input name="userfile" type="file" id="InputFile" class="form-control" />
  </div>
<input type="submit" value="ファイルを送信" class="btn btn-default" />
</div>
</body>
</html>
