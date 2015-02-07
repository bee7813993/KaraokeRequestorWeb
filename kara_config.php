<?php
$configfile = 'config.ini';

if(file_exists($configfile)){
    $config_ini = parse_ini_file($configfile);
    $dbname = $config_ini["dbname"];
    $playmode = $config_ini["playmode"];
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

if(empty($playmode)){
    $playmode = 3;
    $config_ini = array_merge($config_ini,array("playmode" => $playmode));
    $fp = fopen($configfile, 'w');
    foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
    fclose($fp);
}


function initdb(&$db,$dbname)
{

try {
	$db = new PDO('sqlite:'. $dbname);
} catch(PDOException $e) {
	printf("new PDO Error: %s\n", $e->getMessage());
	die();
} 
$sql = "create table IF NOT EXISTS requesttable (
 id INTEGER PRIMARY KEY AUTOINCREMENT, 
 songfile  varchar(1024), 
 singer varchar(512), 
 comment text, 
 kind text,
 reqorder INTEGER,
 fullpath text,
 nowplaying text,
 status text
)";
$stmt = $db->query($sql);
if ($stmt === false ){
	print("Create table ¸”s‚µ‚Ü‚µ‚½B<br>");
	die();
}
 return($db);

}

initdb($db,$dbname);

?>

