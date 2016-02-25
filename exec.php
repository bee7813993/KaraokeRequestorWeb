<?php
$db = null;

require_once 'commonfunc.php';

$l_filename=$_POST['filename'];
$l_singer=$_POST['singer'];
$l_freesinger=$_POST['freesinger'];
$l_comment=$_POST['comment'];
$l_kind=$_POST['kind'];
$l_fullpath= "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $l_fullpath = $_REQUEST["fullpath"];
}
$l_secret = 0;
if(array_key_exists("secret", $_REQUEST)) {
    $l_secret = $_REQUEST["secret"];
}

$l_loop = 0;
if(array_key_exists("loop", $_REQUEST)) {
    $l_loop = $_REQUEST["loop"];
}

$l_clientip = $_SERVER['REMOTE_ADDR'];
if(array_key_exists("clientip", $_REQUEST)) {
    $l_clientip = $_REQUEST["clientip"];
}

$l_clientua = $_SERVER['HTTP_USER_AGENT'];
if(array_key_exists("clientua", $_REQUEST)) {
    $l_clientua = $_REQUEST["clientua"];
}

if(!empty($l_freesinger)){
$l_singer=$l_freesinger;
}

// print("${l_singer} さんの ${l_filename} を追加する予定。<br>");
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="refresh" content="1; url=requestlist_only.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DB登録中</title>
</head>
<body>


<?php
if($l_kind === "URL指定"){
  $l_fullpath = $l_filename;
  $displayfilename = $l_filename;
}else {
  $displayfilename = makesongnamefromfilename($l_filename) ;
}

try {
    $sql = "INSERT INTO requesttable (songfile, singer, comment, kind, fullpath, nowplaying, status, clientip, clientua, playtimes, secret, loop) VALUES (:fn, :sing, :comment, :kind, :fp, :np, :status, :ip, :ua, 0 ,:secret,:loop)";
    $stmt = $db->prepare($sql);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}
	if ($stmt === false ){
		print("INSERT　 prepare 失敗しました。<br>");
	}

$arg = array(
	':fn' =>  $displayfilename,
	':sing' => $l_singer,
	':comment' => $l_comment,
	':kind' => $l_kind,
	':fp' => $l_fullpath,
	':np' => "未再生",
	':status' => 'new',
	':ip' => $l_clientip  ,
	':ua' => $l_clientua ,
	':secret' => $l_secret,
	':loop' => $l_loop
	);
$ret = $stmt->execute($arg);
if (! $ret ) {
	print("${l_filename} を追加にしっぱいしました。");
	die();
}

$sql = "SELECT * FROM requesttable where status = 'new' ORDER BY id DESC";
try {
if(!empty($DEBUG))
    print $sql.'<br />';
    $select = $db->query($sql);
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
    $newid=$row['id'];
    $sql_u = 'UPDATE requesttable set reqorder = '. $newid . ', status = \'OK\' WHERE id = '. $newid;
if(!empty($DEBUG))
    print $sql_u.'<br />';
    $ret = $db->query($sql_u);
    }

} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}


print("${l_filename} を追加しました。<br>");

print("1秒後に登録ページに移動します<br>");

?>

<a href="requestlist_only.php" > リクエストページに戻る <a><br>


<?php
$sql = "SELECT * FROM requesttable ORDER BY id DESC";
if(!empty($DEBUG)){
print '現在の登録状況<br>';

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
}
?>
</body>
</html>

