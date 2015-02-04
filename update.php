<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- <META http-equiv="refresh" content="1; url=request.php"> -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>項目修正実行</title>
</head>
<body>


<a href="request.php" > リクエストページに戻る <a><br>

<?php
$db = null;

include 'kara_config.php';

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}else {
    printf("No ID");
    die();
}

if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
    
    try{
    $sql_u = 'UPDATE requesttable set songfile = \''. $l_songfile . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
}

if(array_key_exists("singer", $_REQUEST)) {
    $l_singer = $_REQUEST["singer"];

    try{
    $sql_u = 'UPDATE requesttable set singer = \''. $l_singer . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }

}

if(array_key_exists("comment", $_REQUEST)) {
    $l_comment = $_REQUEST["comment"];

    try{
    $sql_u = 'UPDATE requesttable set comment = \''. $l_comment . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
}
if(array_key_exists("reqorder", $_REQUEST)) {
    $l_reqorder = $_REQUEST["reqorder"];

    try{
    $sql_u = 'UPDATE requesttable set reqorder = '. $l_reqorder . ' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
}
if(array_key_exists("fullpath", $_REQUEST)) {
    $l_fullpath = $_REQUEST["fullpath"];

    try{
    $sql_u = 'UPDATE requesttable set fullpath = \''. $l_fullpath . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
}
if(array_key_exists("nowplaying", $_REQUEST)) {
    $l_nowplaying = $_REQUEST["nowplaying"];

    try{
    $sql_u = 'UPDATE requesttable set nowplaying = \''. $l_nowplaying . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
}
if(array_key_exists("status", $_REQUEST)) {
    $l_status = $_REQUEST["status"];

    try{
    $sql_u = 'UPDATE requesttable set status = \''. $l_status . '\' WHERE id = '. $l_id;
print "DEBUG:".$sql_u.'<br />';
    $ret = $db->query($sql_u);
    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }

}

print("現在の登録状況<br>");

$sql = "SELECT * FROM requesttable ORDER BY id DESC";
try{
    $select = $db->query($sql);

    while($row = $select->fetch(PDO::FETCH_ASSOC)){
	    echo implode("|", $row) . "\n<br>";
    }

    $db = null;

}catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
} 

print "<a href=\"change.php?id=$l_id\" > 戻る </a>";

?>
&nbsp;
<a href="request.php" >トップに戻る </a>

</body>
</html>