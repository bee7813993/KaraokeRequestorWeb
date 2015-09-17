<?php
setlocale(LC_ALL, 'ja_JP.UTF-8');

include 'kara_config.php';

if(empty($dbname)){
  $dbname = 'data';
}


$sql = "DELETE FROM requesttable";
$retval = $db->exec($sql);
if (! $retval ) {
        echo "\nPDO::errorInfo():\n";
        print_r($db->errorInfo());
        print "<br>dbname : $dbname \n<br>";
}


?>
<!doctype html>
<html lang="ja">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">
<?php
if($retval){
print '<META http-equiv="refresh" content="1; url=requestlist_only.php">';
}
?>  

  <title>DB消去</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<p> DB消去完了 </p>
<a href="request.php" >トップに戻る </a>
</body>
</html>
