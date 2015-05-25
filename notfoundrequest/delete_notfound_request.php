<!doctype html>
<html lang="ja">
<head>
  
<META http-equiv="refresh" content="1; url=notfoundrequest.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>見つからなかったリスト更新中</title>
<link type="text/css" rel="stylesheet" href="../css/style.css" />
</head>
<body>


<a href="notfoundrequest.php" > 見つからなかったリストに戻る </a><br>

<?php
$db = null;
include 'notfound_commonfunc.php';
init_notfounddb($db,"notfoundrequest.db");

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}else {
    printf("No ID");
    die();
}
        $sql = "DELETE FROM notfoundtable where id = $l_id";
        $ret = $db->query($sql);
        if (! $ret ) {
        	print("${l_id} を削除に失敗しました。<br>");
        	die();
        }

?>
&nbsp;
<a href="../request.php" >トップに戻る </a>

</body>
</html>