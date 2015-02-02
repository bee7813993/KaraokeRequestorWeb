<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="refresh" content="60"; url=request.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>1項目移動・削除</title>
</head>
<body>

<a href="request.php" > リクエストページに戻る <a><br>

<?php
if(array_key_exists("nowplaying", $_REQUEST)) {
    $l_nowplaying = $_REQUEST["nowplaying"];
}

if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
}

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}


$db = null;

include 'kara_config.php';


$sql = "UPDATE requesttable set nowplaying = \"$l_nowplaying\" WHERE id = $l_id AND songfile = \"$l_songfile\"";
print $sql;
 $stmt = $db->prepare($sql);
 $ret = $db->query($sql);
 if (! $ret ) {
	print("$l_nowplaying への変更に失敗しました。<br>");
	print("順番入れ替えと競合した可能性があるのでもう一度試してください<br>");
	die();
 }

print("1秒後に登録ページに移動します<br>");

print("現在の登録状況<br>");
try{
    $sql = "SELECT * FROM requesttable ORDER BY id DESC";
    $select = $db->query($sql);

    while($row = $select->fetch(PDO::FETCH_ASSOC)){
	    echo implode("|", $row) . "\n<br>";
    }


    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    } 
    $db = null;
?>
</body>
</html>


