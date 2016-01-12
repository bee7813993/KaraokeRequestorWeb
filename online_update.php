<html>
<head>
<?php 
// 処理記述部
require_once 'commonfunc.php';
print_meta_header();
$res = true;
if(array_key_exists("UPDATEVERSION", $_REQUEST)) {
  $res = update_fromgit(urldecode($_REQUEST["UPDATEVERSION"]),$errmsg);
}

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
  <title>オンラインアップデート</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar();
?>

<?php
if($res === false){
   print "<p> $errmsg </p>" ;
}

$curver = get_git_version();
if(!is_null($curver)) {
print "<p> 現在のバージョン $curver </p>";
}

$taglist = get_gittaglist();
if(count($taglist) > 0){
  print '<dl class="dl-horizontal">';
    print '<dt > 最新版(リリース前) </dt>';
    print '<dd > <a href=online_update.php?UPDATEVERSION='.urlencode("origin/master").' class="btn btn-primary" > 更新 </a></dd> ';
  foreach(array_reverse($taglist) as $tag){
    if(strcmp($tag,'v0.09.5-alpha') == 0 ){
      print '<dt > '.$tag.' </dt>';
      print '<dd > これ以前のバージョンはコマンドプロンプトでのコマンド実行が必要です </dd> ';
      break;
    }
    print '<dt > '.$tag.' </dt>';
    print '<dd > <a href=online_update.php?UPDATEVERSION='.urlencode($tag).' class="btn btn-primary" > 更新 </a></dd> ';
  }
  print '</dl >';
    print '  <div class="container">';
    print '<div class="form-group">';
    print '<form method="GET" class="form-inline">';
    print '<label> 任意バージョンハッシュ </label>';
    print '<input type="text" name="UPDATEVERSION" class="form-control toolinfo" />';
    print '<input type="submit" value="実行" class="btn btn-primary "/>';
    print '</form>';
    print '  </div>';
    print '  </div>';
  

}else {
  print "Now no tag\n";
}

?>

<p> バージョン情報 <a href="https://github.com/bee7813993/KaraokeRequestorWeb/commits/master" target="_blank" > https://github.com/bee7813993/KaraokeRequestorWeb/commits/master </a>

</body>
</html>