<?php
require_once('commentcommonfunc.php');
//===================初期設定====================
$debug_file = 'debug.dat'; //データファイル名
//===============================================

//スーパーグローバル変数対策
if(!isset($PHP_SELF)){ $PHP_SELF = $_SERVER["PHP_SELF"]; }
if(!isset($room)){ 
    $room="";
    if(array_key_exists("r", $_REQUEST)) {$room = $_REQUEST['r']; }
}
$room = stripslashes($room);
?>

<?php
	//MYSQLに接続
	
	if(strlen($room) === 0){
	   print('no room number');
	   die();
	}
	
//	$link = mysql_connect('接続先のMySQLサーバ', 'username', 'password');
//	$db_selected = mysql_select_db('使用するDB名', $link);
    commentdb_init($commentdb,'comment.db');
    
	$sql = "select * from dkniko where chk = 0 and room = '".$room."' order by regdate limit 1";
	$select  = $commentdb->query($sql);
	$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
	$select->closeCursor();
//	$result_flag = mysql_query($sql);
	$buf = "";	

//	if (mysql_num_rows($result_flag) != 0) {
	if (count($allrequest) != 0) {
		// $row = mysql_fetch_assoc($result_flag);
		// $buf = $row['size'].$row['col'].mb_convert_encoding($row['msg'], "SJIS", "auto");
		$buf = $allrequest[0]['size'].$allrequest[0]['col'].mb_convert_encoding($allrequest[0]['msg'], "SJIS", "auto")."\t";
		$sql = "update dkniko set chk = 1 where id=".$allrequest[0]['id'];
		// $result_flag = mysql_query($sql);
		$result_flag  = $commentdb->exec($sql);
		
	}else{
		$buf = "nothing";
	}

	//print $buf.'                    <body><div id="xdomain_ad_468x60"></div>';
	print $buf;
	//mysql_close($link);
?>
