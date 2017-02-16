<?php
require_once 'commonfunc.php';
require_once 'binngo_func.php';
mb_language("Japanese");

?>

<!doctype html>
<html lang="ja">
<head>
<?php 
print_meta_header();
?>
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

<title>ビンゴ条件入力</title>

<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>


<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
shownavigatioinbar();
?>
<?php
$bingoinfo = new SongBingo();
$bingoinfo->initbingodb('songbingo.db'); 
$textlist = null;
/* ファイルがアップロードされたかチェック */
if(array_key_exists('bingolistfile',$_FILES)){
   /* */
   $tmp = fopen($_FILES['bingolistfile']['tmp_name'], "r");
   $textlist = fread($tmp, 1024*1024*10 ); // 最大10Mまで読み込む
   mb_convert_variables("UTF-8", "ASCII,JIS,UTF-8,CP51932,SJIS-win", $textlist);
   //print '<pre>' . $textlist . '</pre>';
}

/* textareaに入力されたかチェック */
if(array_key_exists('bingolisttext',$_REQUEST)){
   /* */
   $textlist = $_REQUEST['bingolisttext'];
   //print '<pre>' . $textlist . '</pre>';
}

if(!empty($textlist) ){
   $listarray = $bingoinfo->text2array($textlist);
   $bingoinfo->registbingodb($listarray);
   $bingoinfo->savearray2newdb();
   
}

?>
<div class="container">

  <form action="bingo_input.php" method="post" enctype="multipart/form-data">
    <label > テキストファイル(改行区切り）の転送
      <input type="file" name="bingolistfile" accept="text/comma-separated-values" />
      <select name="importtype" id="importtype" class="form-control" > 
        <option value="new" >新規</option>
      </select>
    </label>
    <input type="submit" value="Send" />  
  </form>

  <form action="bingo_input.php" method="post" >
  <div class="form-group">
  <label>コピペで入力</label>
  <textarea name="bingolisttext" class="form-control" rows="75" id="comment"></textarea>
  <button type="submit" class="btn btn-primary">Submit</button>
  </form>
  

<?php
// debug $bingoinfo->showalldbtable();
?>
</div>
</body>
</html>
