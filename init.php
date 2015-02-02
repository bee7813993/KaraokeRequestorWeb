<?php

if(array_key_exists("filename", $_REQUEST)) {
    $newdb = $_REQUEST["filename"];
}

if(array_key_exists("playmode", $_REQUEST)) {
    $newplaymode = $_REQUEST["playmode"];
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

if (! empty($newplaymode)){
    $playmode = $newplaymode;
    $config_ini = array_merge($config_ini,array("playmode" => $playmode));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
    print "動作モードを".$playmode."に変更しました。<br><br>";
}

?>
 
現在のDBファイル名 : 
<?php
print $dbname;
?>
<br>
現在の動作モード(1: 自動再生開始モード, 2: 手動再生開始モード, 3: 手動プレイリスト登録モード ) : 
<?php
print $playmode;
?>

<br>

新しいファイル名　
<form method="post" action="init.php">
<input type="text" size=20 name="filename" id="filename" value=<?php echo $dbname; ?> >
<input type="submit" value="OK" />
</form>

動作モード選択　
<form method="post" action="init.php">
<select name="playmode" id="playmode" >  
<option value="1" >自動再生開始モード</option>
<option value="2" >手動再生開始モード</option>
<option value="3" >手動プレイリスト登録モード</option>
</select>
<input type="submit" value="OK" />
</form>
<a href="request.php" > リクエスト画面に戻る　</a>

</body>
</html>

