<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="refresh" content="1; url=requestlist_only.php" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>項目修正実行</title>
</head>
<body>


<a href="requestlist_only.php" > リクエストページに戻る <a><br>

<?php
$db = null;

include 'kara_config.php';

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}else {
    printf("No ID");
    die();
}
$updatestring = "";

if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' songfile = '. $db->quote($l_songfile) . ' ';
}

if(array_key_exists("singer", $_REQUEST)) {
    $l_singer = $_REQUEST["singer"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' singer = '. $db->quote($l_singer) . ' ';
}

if(array_key_exists("comment", $_REQUEST)) {
    $l_comment = $_REQUEST["comment"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' comment = '. $db->quote($l_comment) . ' ';
}

if(array_key_exists("kind", $_REQUEST)) {
    $l_kind = $_REQUEST["kind"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' kind = '. $db->quote($l_kind) . ' ';
}

if(array_key_exists("reqorder", $_REQUEST)) {
    $l_reqorder = $_REQUEST["reqorder"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' reqorder = '. $db->quote($l_reqorder) . ' ';
}
if(array_key_exists("fullpath", $_REQUEST)) {
    $l_fullpath = $_REQUEST["fullpath"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' fullpath = '. $db->quote($l_fullpath) . ' ';
}

if(array_key_exists("nowplaying", $_REQUEST)) {
    $l_nowplaying = $_REQUEST["nowplaying"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' nowplaying = '. $db->quote($l_nowplaying) . ' ';
}

if(array_key_exists("status", $_REQUEST)) {
    $l_status = $_REQUEST["status"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' status = '. $db->quote($l_status) . ' ';
}

if(array_key_exists("clientip", $_REQUEST)) {
    $l_clientip = $_REQUEST["clientip"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' clientip = '. $db->quote($l_clientip) . ' ';
}

if(array_key_exists("clientua", $_REQUEST)) {
    $l_clientua = $_REQUEST["clientua"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' clientua = '. $db->quote($l_clientua) . ' ';
}

if(array_key_exists("playtimes", $_REQUEST)) {
    $l_playtimes = $_REQUEST["playtimes"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' playtimes = '. $db->quote($l_playtimes) . ' ';
}


$l_value = "";
if(array_key_exists("secret", $_REQUEST)) {
    $l_value = $_REQUEST["secret"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' secret = '. $db->quote($l_value) . ' ';
}

$l_value = "";
if(array_key_exists("loop", $_REQUEST)) {
    $l_value = $_REQUEST["loop"];
    if(strlen($updatestring) > 0 ){  // 2項目目以降コンマが必要
        $updatestring = $updatestring.' ,';
    }
    $updatestring = $updatestring.' loop = '. $db->quote($l_value) . ' ';
}
print  $updatestring;
if(strlen($updatestring) > 0){
    try{
    $sql_u = 'UPDATE requesttable set '. $updatestring . ' WHERE id = '. $l_id;
    print  "SQL\n<br />".$sql_u."<br />\n";
if(!empty($DEBUG))
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
<a href="requestlist_only.php" >トップに戻る </a>

</body>
</html>