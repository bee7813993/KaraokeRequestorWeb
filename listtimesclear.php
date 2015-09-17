<?php
setlocale(LC_ALL, 'ja_JP.UTF-8');

include 'kara_config.php';

if(empty($dbname)){
  $dbname = 'data';
}


$l_times = 0;

if(array_key_exists("times", $_REQUEST)) {
    $l_times = $_REQUEST["times"];
}


$sql = "UPDATE requesttable set playtimes = $l_times";
$retval = $db->exec($sql);
if (! $retval ) {
        echo "\nPDO::errorInfo():\n";
        print_r($db->errorInfo());
}


?>
<!doctype html>
<html lang="ja">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">
  <META http-equiv="refresh" content="1; url=requestlist_only.php">
  

  <title>再生回数クリア</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<p> 再生回数<?php echo $l_times; ?>クリア完了 </p>
<a href="requestlist_only.php" >トップに戻る </a>
</body>
</html>
