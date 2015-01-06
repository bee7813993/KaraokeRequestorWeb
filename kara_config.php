<?php
$configfile = 'config.ini';

if(file_exists($configfile)){
    $config_ini = parse_ini_file($configfile);
    $dbname = $config_ini["dbname"];
} else {
    $fp = fopen($configfile, 'w');
    fclose($fp);
}

if(empty($dbname)){
    $dbname = 'request.db';
    $config_ini = array_merge($config_ini,array("dbname" => $dbname));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}

try {
	$db = new PDO('sqlite:'. $dbname);
} catch(PDOException $e) {
	printf("Error: %s\n", $e->getMessage());
	die();
} 

$sql = "create table IF NOT EXISTS requesttable (
 id INTEGER PRIMARY KEY AUTOINCREMENT, 
 songfile  varchar(1024), 
 singer varchar(512), 
 comment text, 
 kind text,
 reqorder INTEGER
)";
$stmt = $db->query($sql);
if ($stmt === false ){
	print("Create table ¸”s‚µ‚Ü‚µ‚½B<br>");
	die();
}
?>

