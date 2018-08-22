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
if(!isset($cmsver)){ 
    $cmsver="";
    if(array_key_exists("v", $_REQUEST)) {$cmsver = $_REQUEST['v']; }
}
?>

<?php
	if(strlen($room) === 0){
	   print('no room number');
	   die();
	}
	
    commentdb_init($commentdb,'comment.db');
    
	$sql = "select * from dkniko where chk = 0 and room = '".$room."' order by regdate limit 1";
	$select  = $commentdb->query($sql);
	$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
	$select->closeCursor();
	$buf = "";
	if (count($allrequest) != 0) {
		$buf = make_comment_buf($allrequest[0], $cmsver);
	}

	if (strlen($buf) === 0) {
		$buf = "nothing";
	} else {
		$sql = "update dkniko set chk = 1 where id=".$allrequest[0]['id'];
		$result_flag  = $commentdb->exec($sql);
	}

	print $buf;
?>
