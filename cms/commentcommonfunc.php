<?php

function commentdb_init(&$db,$dbname)
{

try {
	$db = new PDO('sqlite:'. $dbname);
} catch(PDOException $e) {
	printf("new PDO Error: %s\n", $e->getMessage());
	die();
} 
$sql = "create table IF NOT EXISTS dkniko (
 room  varchar(8) default '00000000', 
 name varchar(20) default NULL, 
 msg text, 
 size int default '0',
 col varchar(6) default '0',
 regdate text default CURRENT_TIMESTAMP,
 chk int default '0', 
 id INTEGER PRIMARY KEY AUTOINCREMENT
)";
$stmt = $db->query($sql);
if ($stmt === false ){
	print("Create table 失敗しました。<br>");
	var_dump($db->errorInfo(), true);
	die();
}
 return($db);

}

function make_comment_buf($data, $cmsver)
{
	$buf = "";

	if (strlen($cmsver) === 0) {
		$buf = $data['size'].$data['col'].mb_convert_encoding($cmsver.$data['msg'], "SJIS", "auto")."\t";
	} else if ($cmsver == '3') {
		$buf = 'X3'.$data['size'].$data['col'].$data['regdate'].mb_convert_encoding($data['msg'], "UTF-8", "auto")."\t";
	} else if ($cmsver == '4') {
		$buf = 'X4'.$data['cmd'].$data['regdate'].mb_convert_encoding($data['msg'], "UTF-8", "auto")."\t";
	}
	
	return $buf;
}

?>