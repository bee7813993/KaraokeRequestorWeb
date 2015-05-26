<?php
$configfile = 'config.ini';
$config_ini = array ();

function readconfig(&$dbname,&$playmode,&$playerpath,&$foobarpath,&$requestcomment = 'none',&$usenfrequset = 'none',&$historylog = 'none'){

    global $configfile;
    global $config_ini;

    if(file_exists($configfile)){
        $config_ini = parse_ini_file($configfile);
    //    var_dump($config_ini);
        if(array_key_exists("dbname", $config_ini) ){
            $dbname = $config_ini["dbname"];
        }
        if(array_key_exists("playmode", $config_ini) ){
            $playmode = $config_ini["playmode"];
        }
        if(array_key_exists("playerpath", $config_ini) ){
            $playerpath = urldecode($config_ini["playerpath"]);
        }
        if(array_key_exists("foobarpath", $config_ini) ){
            $foobarpath = urldecode($config_ini["foobarpath"]);
        }
        if(strcmp($requestcomment,'none') != 0){
            if(array_key_exists("requestcomment", $config_ini) ){
                $requestcomment = urldecode($config_ini["requestcomment"]);
            }else{
                $requestcomment = "雑談とかどうぞ。その他見つからなかった曲とか、ダウンロードしておいてほしいカラオケ動画のURLとかあれば書いておいてもらえるとそのうち増えてるかも";
            }
        }
        if(strcmp($usenfrequset,'none') != 0){
            if(array_key_exists("usenfrequset", $config_ini) ){
                $usenfrequset = $config_ini["usenfrequset"];
            }
        }
        if(strcmp($historylog,'none') != 0){
            if(array_key_exists("historylog", $config_ini) ){
                $historylog = $config_ini["historylog"];
            }
        }
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

    if(empty($playerpath)){
        $playerpath = 'C:\Program Files (x86)\MPC-BE\mpc-be.exe';
        $config_ini = array_merge($config_ini,array("playerpath" => urlencode($playerpath)));
        $fp = fopen($configfile, 'w');
        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
        fclose($fp);
    }

    if(empty($foobarpath)){
        $foobarpath = '..\..\foobar2000\foobar2000.exe';
        $config_ini = array_merge($config_ini,array("foobarpath" => urlencode($foobarpath)));
        $fp = fopen($configfile, 'w');
        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
        fclose($fp);
    }

    if(!strcmp($requestcomment,'none')){
        if(empty($requestcomment)){
            $requestcomment = "雑談とかどうぞ。その他見つからなかった曲とか、ダウンロードしておいてほしいカラオケ動画のURLとかあれば書いておいてもらえるとそのうち増えてるかも";
            $config_ini = array_merge($config_ini,array("requestcomment" => urlencode($requestcomment)));
            $fp = fopen($configfile, 'w');
            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
            fclose($fp);
        }
    }

    if(!strcmp($usenfrequset,'none')){
        if(empty($usenfrequset)){
            $usenfrequset = 0;
            $config_ini = array_merge($config_ini,array("usenfrequset" => urlencode($usenfrequset)));
            $fp = fopen($configfile, 'w');
            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
            fclose($fp);
        }
    }

    if(!strcmp($historylog,'none')){
        if(empty($historylog)){
            $historylog = 0;
            $config_ini = array_merge($config_ini,array("historylog" => urlencode($historylog)));
            $fp = fopen($configfile, 'w');
            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
            fclose($fp);
        }
    }
//    $playerpath = "'".$playerpath."'";
    //var_dump($config_ini);
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
 status text,
 clientip text,
 clientua text,
 playtimes INTEGER,
 secret INTEGER
)";
$stmt = $db->query($sql);
if ($stmt === false ){
	print("Create table 失敗しました。<br>");
	die();
}
 return($db);

}

readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog);
initdb($db,$dbname);

// cache control
header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT' );

// HTTP/1.1
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', FALSE );

// HTTP/1.0
header( 'Pragma: no-cache' );


?>

