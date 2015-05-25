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

function print_meta_header(){
    print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    print "\n";
    print '<meta http-equiv="Content-Style-Type" content="text/css" />';
    print "\n";
    print '<meta http-equiv="Content-Script-Type" content="text/javascript" />';
    print "\n";
    print '<meta name="viewport" content="width=device-width,initial-scale=1.0" />';
    print "\n";
}


?>

