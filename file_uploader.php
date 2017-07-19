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

<title>ローカルファイル転送指定</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
require_once 'commonfunc.php';
shownavigatioinbar('searchreserve.php');
$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}
ini_set('memory_limit', '1536M');
ini_set('post_max_size', '1024M');
ini_set('upload_max_filesize', '1024M');
?>
<!--
<div class="container">
  <form enctype="multipart/form-data" action="file_uploader_recv.php" method="POST" onsubmit="saveFilename()";>
  <input type="hidden" name="MAX_FILE_SIZE" value="900000000" />
  <input type="hidden" name="uploaddfilename" id="upldfilename" >
<? php
  if(is_numeric($selectid)){
      print '<input type="hidden" name="selectid" class="searchtextbox" value='.$selectid.' />';
  }
?>
  <div class="form-group">
    <label for="InputFile">手元の曲ファイルを指定</label>
    <input name="userfile" type="file" id="InputFile" class="form-control" />
  </div>
<input type="submit" value="ファイルを送信" class="btn btn-default" />
  </form>
</div>
-->

<div class="container">
  <div class="form-group">
    <label for="InputFile">手元の曲ファイルを指定</label>
<input type="file" name="file" id="file" class="form-control">
<button type="button" id="post" class="btn btn-default" >ファイルを送信</button>
  </div>
<div class="progress">
  <div class="progress-bar" role="progressbar" style="width: 0%;" id = "divprogress" >
  </div>
</div>

<script>
function updateProgress(e) {
  if (e.lengthComputable) {
    var percent = e.loaded / e.total;
    var divprogress = document.getElementById("divprogress");
    divprogress.style.width = (percent * 100) + '%'; 
    divprogress.innerHTML = e.loaded  + "byte";
  }
}

$("#post").on("click", function() {
  var formData = new FormData();
  formData.append("userfile", document.getElementById("file").files[0]);
  formData.append("jspost", true);
  formData.append("MAX_FILE_SIZE", 900000000);
<?php
  if(is_numeric($selectid)){
      print 'formData.append("selectid", '.$selectid.');';
  }
?>
  
  var request = new XMLHttpRequest();
  request.upload.addEventListener("progress", updateProgress, false);
  request.open("POST", "./file_uploader_recv.php");
  request.onreadystatechange = function() {
    if(request.readyState == 4) {
      if (request.status === 200) {
        temp = document.getElementById("uploadresult");
        resulttext = request.responseText;
        temp.innerHTML = resulttext;
      }
    }
  }
  request.send(formData);
});
</script>
  
</div>
<div class="container" id="uploadresult">
</div>

</body>
</html>
