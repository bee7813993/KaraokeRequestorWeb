<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="refresh" content="1; url=request.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>1項目移動・削除</title>
</head>
<body>

<a href="request.php" > リクエストページに戻る <a><br>

<?php
$db = null;

include 'kara_config.php';


$tmpid=9999;
/**
 * 行を上に移動
 * @param integer $id
 * @param db $db
 */
function dbup($id, $db)
{
	global $tmpid;
 $nextid = $id + 1 ;
 $sql = "SELECT * FROM requesttable where id = $nextid ";
 $select = $db->query($sql);
 $row = $select->fetch(PDO::FETCH_ASSOC);

 if(  $row !== false ){
 $sql = "UPDATE requesttable set id = $tmpid WHERE id = $nextid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${nextid} から 0 への移動にしっぱいしました。<br>");
	die();
 }
 $sql = "UPDATE requesttable set id = $nextid WHERE id = $id ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} から ${nextid} への移動にしっぱいしました。<br>");
	die();
 }
 $sql = "UPDATE requesttable set id = $id WHERE id = $tmpid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("0 から ${id} への移動にしっぱいしました。<br>");
	die();
 }
 }else{
 $sql = "UPDATE requesttable set id = $nextid WHERE id = $id ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} から $nextid への移動にしっぱいしました。<br>");
	die();
 }
 }
}

/**
 * 行を下に移動
 * @param integer $id
 * @param db $db
 */
function dbdown($id, $db)
{
	global $tmpid;
 if ($id <= 1 ){
    print("すでに一番下<br>");
 }else{
 
 $nextid = $id - 1 ;
 $sql = "SELECT * FROM requesttable where id = $nextid ";
 $select = $db->query($sql);
 $row = $select->fetch(PDO::FETCH_ASSOC);
// var_dump($row);
 if(  $row !== false ){
 $sql = "UPDATE requesttable set id = $tmpid WHERE id = $nextid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${nextid} から 0 への移動にしっぱいしました。<br>");
	die();
 }
 $sql = "UPDATE requesttable set id = $nextid WHERE id = $id ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} から ${nextid} への移動にしっぱいしました。<br>");
	die();
 }
 $sql = "UPDATE requesttable set id = $id WHERE id = $tmpid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("0 から ${id} への移動にしっぱいしました。<br>");
	die();
 }
 }else{
 $sql = "UPDATE requesttable set id = $nextid WHERE id = $id ";
 $stmt = $db->prepare($sql);
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} から $nextid への移動にしっぱいしました。<br>");
	die();
 }
 }
 } 
}


$l_id=$_POST['id'];
$l_action='delete';
if( !empty($_POST['up']) )
 {$l_action = 'up';}
if( !empty($_POST['down']) )
 {$l_action = 'down';}

if ( $l_action === 'up' )
{
    dbup($l_id,$db);
}elseif ( $l_action === 'down' )
{
    dbdown($l_id,$db);
}else {

$sql = "DELETE FROM requesttable where id = $l_id";
$ret = $db->query($sql);
if (! $ret ) {
	print("${l_id} を削除に失敗しました。<br>");
	die();
}

print("${l_id} を削除しました。<br>");
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


