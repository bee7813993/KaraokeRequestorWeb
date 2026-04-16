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

// id は整数のみ受け付ける
$l_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($l_id === false || $l_id === null) {
    $l_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
if ($l_id === false || $l_id === null) {
    http_response_code(400);
    printf("No ID");
    die();
}

$stmt = $db->prepare("DELETE FROM notfoundtable WHERE id = :id");
$stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
$ret = $stmt->execute();
if (! $ret ) {
    print(htmlspecialchars((string)$l_id, ENT_QUOTES, 'UTF-8')." を削除に失敗しました。<br>");
    die();
}

?>
&nbsp;
<a href="../requestlist_only.php" >トップに戻る </a>

</body>
</html>