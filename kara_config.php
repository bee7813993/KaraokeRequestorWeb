<?php
$configfile = 'config.ini';
$config_ini = array ();

function readconfig_array()
{
    global $configfile;
    $config_ini = array ();
    
    $configinifile = $configfile."";
    
    if(file_exists($configinifile)){
        $config_ini = parse_ini_file($configinifile);
    }else {
       // set initial value
    }
    // set initial value
    if(!array_key_exists("dbname", $config_ini)){
        $dbname = 'request.db';
        $config_ini = array_merge($config_ini,array("dbname" => urldecode($dbname)));
    }
    if(!array_key_exists("playmode", $config_ini)){
        $playmode = 3;
        $config_ini = array_merge($config_ini,array("playmode" => $playmode));
    }
    if(!array_key_exists("playerpath_select", $config_ini)){
        $playerpath_select = 'C:\Program Files\MPC-BE\mpc-be64.exe';
        $config_ini = array_merge($config_ini,array("playerpath_select" => urlencode($playerpath_select)));
    }
    if(!array_key_exists("playerpath_any", $config_ini)){
        $playerpath_any = '';
        $config_ini = array_merge($config_ini,array("playerpath_any" => urlencode($playerpath_any)));
    }
    if(!array_key_exists("foobarpath", $config_ini)){
        $foobarpath = '.\foobar2000\foobar2000.exe';
        $config_ini = array_merge($config_ini,array("foobarpath" => urlencode($foobarpath)));
    }
    if(!array_key_exists("requestcomment", $config_ini)){
        $requestcomment = "曲への思い入れとか雑談とかどうぞ";
        $config_ini = array_merge($config_ini,array("requestcomment" => urlencode($requestcomment)));
    }
    if(!array_key_exists("usenfrequset", $config_ini)){
        $usenfrequset = 0;
        $config_ini = array_merge($config_ini,array("usenfrequset" => urlencode($usenfrequset)));
    }
    if(!array_key_exists("usevideocapture", $config_ini)){
        $usevideocapture = 0;
        $config_ini = array_merge($config_ini,array("usevideocapture" => urlencode($usevideocapture)));
    }
    if(!array_key_exists("historylog", $config_ini)){
        $historylog = 0;
        $config_ini = array_merge($config_ini,array("historylog" => urlencode($historylog)));
    }
    if(!array_key_exists("waitplayercheckstart", $config_ini)){
        $waitplayercheckstart = 2;
        $config_ini = array_merge($config_ini,array("waitplayercheckstart" => $waitplayercheckstart));            
    }
    if(!array_key_exists("playerchecktimes", $config_ini)){
        $playerchecktimes = 3;
        $config_ini = array_merge($config_ini,array("playerchecktimes" => $playerchecktimes));
    }
    if(!array_key_exists("connectinternet", $config_ini)){
        $connectinternet = 1;
        $config_ini = array_merge($config_ini,array("connectinternet" => $connectinternet));
    }
    if(!array_key_exists("commenturl_base", $config_ini)){
        $commenturl_base = "http://localhost/cms/r.php";
        $config_ini = array_merge($config_ini,array("commenturl_base" => urlencode($commenturl_base)));
    }
    if(!array_key_exists("commentroom", $config_ini)){
        $commentroom = "1000";
        $config_ini = array_merge($config_ini,array("commentroom" => urlencode($commentroom)));            
    }
    if(!array_key_exists("moviefullscreen", $config_ini)){
        $moviefullscreen = "";
        $config_ini = array_merge($config_ini,array("moviefullscreen" => $moviefullscreen));            
    }
    if(!array_key_exists("helpurl", $config_ini)){
        $helpurl = "";
        $config_ini = array_merge($config_ini,array("helpurl" => urlencode($helpurl)));            
    }
    if(!array_key_exists("autoplay_exec", $config_ini)){
        $autoplay_exec = "autoplaystart_mpc_xampp.bat";
        $config_ini = array_merge($config_ini,array("autoplay_exec" => urlencode($autoplay_exec)));            
    }
    if(!array_key_exists("nonamerequest", $config_ini)){
        $nonamerequest = "2";
        $config_ini = array_merge($config_ini,array("nonamerequest" => ($nonamerequest)));            
    }
    if(!array_key_exists("nonameusername", $config_ini)){
        $nonameusername = "名無しさん";
        $config_ini = array_merge($config_ini,array("nonameusername" => urlencode($nonameusername)));            
    }

    if(!array_key_exists("roomurl", $config_ini)){
        $roominfo = array();
        if(array_key_exists("HTTP_HOST",$_SERVER)){
          $url = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
          $urlpath = pathinfo($url);
          $roominfo[]=$urlpath['dirname'];
        
          $config_ini = array_merge($config_ini,array("roomurl" => $roominfo));
        }
    }


    if(array_key_exists("commenturl_base", $config_ini) && array_key_exists("commentroom", $config_ini))
    {
        if($config_ini["commenturl_base"]==='notset'){
            $commenturl = "";
        }else{
            $commenturl = urldecode(sprintf("%s?r=%s",$config_ini["commenturl_base"],$config_ini["commentroom"]));
        }
        $config_ini = array_merge($config_ini,array("commenturl" => urlencode($commenturl))); 
     }    
    if(!array_key_exists("downloadfolder", $config_ini)){
        $downloadfolder = $_SERVER["TMP"];
        $config_ini = array_merge($config_ini,array("downloadfolder" => urlencode($downloadfolder)));            
    }
    //print mb_substr(urldecode($config_ini["downloadfolder"]),-1);
    if(strlen($config_ini["downloadfolder"]) != 0 &&mb_substr(urldecode($config_ini["downloadfolder"]),-1) !== '\\'){
        $config_ini["downloadfolder"] = urlencode(urldecode($config_ini["downloadfolder"]).'\\');
    }

    if(!array_key_exists("gitcommandpath", $config_ini)){
        $gitcommandpath = "gitcmd\\cmd\\git.exe";
        $config_ini = array_merge($config_ini,array("gitcommandpath" => urlencode($gitcommandpath)));            
    }    
    if(!array_key_exists("max_filesize", $config_ini)){
        $max_filesize = 800;
        $config_ini = array_merge($config_ini,array("max_filesize" => $max_filesize));
    }    
    if(!array_key_exists("usebgv", $config_ini)){
        $usebgv = 2;
        $config_ini = array_merge($config_ini,array("usebgv" => $usebgv));
    }    

    if($config_ini["playerpath_select"] == urlencode("その他PATH指定" )) {
        $config_ini = array_merge($config_ini,array("playerpath" => ($config_ini["playerpath_any"])));
    }else{
        $config_ini = array_merge($config_ini,array("playerpath" => ($config_ini["playerpath_select"])));
    }
    // $playerpath =$config_ini["playerpath"];
    
    return $config_ini;

}


function readconfig(
&$dbname,
&$playmode,
&$playerpath,
&$foobarpath,
&$requestcomment = 'none', 
&$usenfrequset = 'none', 
&$historylog = 'none', 
&$waitplayercheckstart = 'none', 
&$playerchecktimes = 'none', 
&$connectinternet = 'none', 
&$usevideocapture = 'none', 
&$moviefullscreen='none',
&$helpurl='none', 
&$commenturl_base='none', 
&$commentroom='none',&$commenturl='none'){

    global $configfile;
    global $config_ini;
    
    
    $config_ini = readconfig_array();
    
    if ( $dbname !== 'none' )
       $dbname = urldecode($config_ini["dbname"]);
    if ( $playmode !== 'none' )
        $playmode = $config_ini["playmode"];
    if ( $playerpath !== 'none' )
        $playerpath = urldecode($config_ini["playerpath"]);
    if ( $foobarpath !== 'none' )
        $foobarpath = urldecode($config_ini["foobarpath"]);
    if ( $requestcomment !== 'none' )
        $requestcomment = urldecode($config_ini["requestcomment"]);
    if ( $usenfrequset !== 'none' )
        $usenfrequset = $config_ini["usenfrequset"];
    if ( $historylog !== 'none' )
        $historylog = $config_ini["historylog"];
    if ( $waitplayercheckstart !== 'none' )
        $waitplayercheckstart = $config_ini["waitplayercheckstart"];
    if ( $playerchecktimes !== 'none' )
        $playerchecktimes = $config_ini["playerchecktimes"];
    if ( $connectinternet !== 'none' )
        $connectinternet = $config_ini["connectinternet"];
    if ( $usevideocapture !== 'none' )
        $usevideocapture = $config_ini["usevideocapture"];
    if ( $moviefullscreen !== 'none' )
        $moviefullscreen = $config_ini["moviefullscreen"];
    if ( $helpurl !== 'none' )
        $helpurl = urldecode($config_ini["helpurl"]);
    if ( $commenturl_base !== 'none' )
        $commenturl_base = urldecode($config_ini["commenturl_base"]);
    if ( $commentroom !== 'none' )
        $commentroom = urldecode($config_ini["commentroom"]);
    if ( $commenturl !== 'none' )
        if(array_key_exists("commenturl", $config_ini) ){
            $commenturl = urldecode($config_ini["commenturl"]);
        }else{
            $commenturl = urldecode(sprintf("%s?r=%s",$commenturl_base,$commentroom));
        }
    //var_dump($config_ini);
}

function updatedb($db){
    /* 追加した項目一覧 */
    $newcolumnlist=array(
                  array ( "name" => "fullpath" , "type" =>  "text") ,
                  array ( "name" => "nowplaying" , "type" =>  "text") ,
                  array ( "name" => "status" , "type" =>  "text") ,
                  array ( "name" => "clientip" , "type" =>  "text") ,
                  array ( "name" => "clientua" , "type" =>  "text") ,
                  array ( "name" => "playtimes" , "type" =>  "INTEGER") ,
                  array ( "name" => "secret" , "type" =>  "INTEGER") ,
                  array ( "name" => "loop" , "type" =>  "text") ,
                  array ( "name" => "keychange" , "type" =>  "INTEGER default 0") ,
                  array ( "name" => "track" , "type" =>  "INTEGER default 0") ,
                  array ( "name" => "pause" , "type" =>  "INTEGER default 0") 
                  );
    /* 現在の項目一覧取得 */
    try {
        $rowsdb = $db->query('PRAGMA table_info(requesttable)');
        $rows = $rowsdb->fetchAll(PDO::FETCH_ASSOC);
        $rowsdb->closeCursor();
    } catch(PDOException $e) {
        printf("DB PDO Error: %s\n", $e->getMessage());
        return false;
    }
    
    /* 追加項目がすでにあるかチェック */
    foreach ($newcolumnlist as $nc ){
        $foundflg = false;
        foreach ($rows as $row) {
            if(  $row['name'] == $nc['name'] ) {
              //echo $row['name']."\n";
                $foundflg = true;
            }
        }
        if( ! $foundflg ){
            $addcolumnsql = "ALTER TABLE requesttable ADD COLUMN ".$nc['name'].'['.$nc['type'].']';
            echo $addcolumnsql;
            echo "\n";
            try {
                $res = $db->exec($addcolumnsql);
            } catch(PDOException $e) {
                printf("DB PDO Error: %s\n", $e->getMessage());
                return false;
            }
        }
    }    
    return true;
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
 secret INTEGER,
 loop INTEGER,
 keychange INTEGER default 0,
 track INTEGER default 0,
 pause INTEGER default 0
)";
$stmt = $db->query($sql);
if ($stmt === false ){
	print("Create table 失敗しました。<br>");
	die();
}
 updatedb($db);
 return($db);

}

readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog,$waitplayercheckstart,$playerchecktimes,$connectinternet,$usevideocapture,$moviefullscreen,$helpurl,$commenturl_base,$commentroom,$commenturl);
$res=initdb($db,$dbname);

// cache control
@header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
@header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT' );

// HTTP/1.1
@header( 'Cache-Control: no-store, no-cache, must-revalidate' );
@header( 'Cache-Control: post-check=0, pre-check=0', FALSE );

// HTTP/1.0
@header( 'Pragma: no-cache' );


?>
