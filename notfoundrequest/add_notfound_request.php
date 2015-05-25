<?php
$db = null;

require_once 'notfound_commonfunc.php';

init_notfounddb($db,"notfoundrequest.db");

if(array_key_exists("requesttext", $_REQUEST)) {
    $l_requesttext = $_REQUEST["requesttext"];
} 

try {
    $sql = "INSERT INTO notfoundtable (requesttext, status) VALUES (:requesttext, 0 )";
    $stmt = $db->prepare($sql);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}
	if ($stmt === false ){
		print("INSERT　 prepare 失敗しました。<br>");
	}

$arg = array(
	':requesttext' => $l_requesttext );
$ret = $stmt->execute($arg);
if (! $ret ) {
	print("${l_requesttext} を追加にしっぱいしました。");
	die();
}

?>
<!doctype html>
<html lang="ja">
<head>
  <title>見つからなかった報告登録中</title>
  <link type="text/css" rel="stylesheet" href="../css/style.css" />
  <META http-equiv="refresh" content="1; url=notfoundrequest.php">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  
</head>
<body>
  <a href="notfoundrequest.php" > 戻る </a>

</body>
</html>