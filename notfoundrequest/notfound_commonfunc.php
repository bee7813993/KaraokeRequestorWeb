<?php
function init_notfounddb(&$nfdb,$nfdbname)
{

try {
	$nfdb = new PDO('sqlite:'. $nfdbname);
} catch(PDOException $e) {
	printf("new PDO Error: %s\n", $e->getMessage());
	die();
} 
$sql = "create table IF NOT EXISTS notfoundtable (
 id INTEGER PRIMARY KEY AUTOINCREMENT, 
 requesttext text, 
 status INTEGER, 
 reply text, 
 searchword text 
)";
$stmt = $nfdb->query($sql);
if ($stmt === false ){
	print("Create table 失敗しました。<br>");
	die();
}
 return($nfdb);

}




?>

