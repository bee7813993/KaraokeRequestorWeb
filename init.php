<?php

if(array_key_exists("filename", $_REQUEST)) {
    $newdb = $_REQUEST["filename"];
}

include 'kara_config.php';
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DBファイル名設定画面</title>
</head>
<body>


<?php
if (! empty($newdb)){
    $dbname = $newdb;
    $config_ini = array_merge($config_ini,array("dbname" => $dbname));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    print "DB ファイル名を".$dbname."に変更しました。";
}

?>
 
現在のDBファイル名 : 
<?php
print $dbname;
?>
<br>

新しいファイル名　
<form method="post" action="init.php">
<input type="text" size=20 name="filename" id="filename">
<input type="submit" value="OK" />
</form>

<a href="request.php" > リクエスト画面に戻る　</a>

</body>
</html>

