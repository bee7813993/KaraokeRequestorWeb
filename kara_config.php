<?php
$configfile = 'config.ini';
$config_ini = array ();

function readconfig_array()
{
    global $configfile;
    $config_ini = array ();
    
    if(file_exists($configfile)){
        $config_ini = parse_ini_file($configfile);
    }else {
       // set initial value
    }
    // set initial value
    if(!array_key_exists("dbname", $config_ini)){
        $dbname = 'request.db';
        $config_ini = array_merge($config_ini,array("dbname" => $dbname));
    }
    if(!array_key_exists("playmode", $config_ini)){
        $playmode = 3;
        $config_ini = array_merge($config_ini,array("playmode" => $playmode));
    }
    if(!array_key_exists("playerpath", $config_ini)){
        $playerpath = 'C:\Program Files (x86)\MPC-BE\mpc-be.exe';
        $config_ini = array_merge($config_ini,array("playerpath" => urlencode($playerpath)));
    }
    if(!array_key_exists("foobarpath", $config_ini)){
        $foobarpath = '..\..\foobar2000\foobar2000.exe';
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
        $commenturl_base = "";
        $config_ini = array_merge($config_ini,array("commenturl_base" => urlencode($commenturl_base)));
    }
    if(!array_key_exists("commentroom", $config_ini)){
        $commentroom = "";
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
    if(!array_key_exists("nonamerequest", $config_ini)){
        $nonamerequest = "2";
        $config_ini = array_merge($config_ini,array("nonamerequest" => ($nonamerequest)));            
    }
    if(!array_key_exists("nonameusername", $config_ini)){
        $nonameusername = "名無しさん";
        $config_ini = array_merge($config_ini,array("nonameusername" => urlencode($nonameusername)));            
    }
    if(array_key_exists("commenturl_base", $config_ini) && array_key_exists("commentroom", $config_ini))
    {
        $commenturl = sprintf("%s?r=%s",$config_ini["commenturl_base"],$config_ini["commentroom"]);
        $config_ini = array_merge($config_ini,array("commenturl" => urlencode($commenturl))); 
    }    
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
       $dbname = $config_ini["dbname"];
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
            $commenturl = sprintf("%s?r=%s",$commenturl_base,$commentroom);
        }

//    if(file_exists($configfile)){
//        $config_ini = parse_ini_file($configfile);
//    //    var_dump($config_ini);
//        if(array_key_exists("dbname", $config_ini) ){
//            $dbname = $config_ini["dbname"];
//        }
//        if(array_key_exists("playmode", $config_ini) ){
//            $playmode = $config_ini["playmode"];
//        }
//        if(array_key_exists("playerpath", $config_ini) ){
//            $playerpath = urldecode($config_ini["playerpath"]);
//        }
//        if(array_key_exists("foobarpath", $config_ini) ){
//            $foobarpath = urldecode($config_ini["foobarpath"]);
//        }
//        if(strcmp($requestcomment,'none') != 0){
//            if(array_key_exists("requestcomment", $config_ini) ){
//                $requestcomment = urldecode($config_ini["requestcomment"]);
//            }
//        }
//        if(strcmp($usenfrequset,'none') != 0){
//            if(array_key_exists("usenfrequset", $config_ini) ){
//                $usenfrequset = $config_ini["usenfrequset"];
//            }
//        }
//        if(strcmp($usevideocapture,'none') != 0){
//            if(array_key_exists("usevideocapture", $config_ini) ){
//                $usevideocapture = $config_ini["usevideocapture"];
//            }
//        }
//        
//        if(strcmp($historylog,'none') != 0){
//            if(array_key_exists("historylog", $config_ini) ){
//                $historylog = $config_ini["historylog"];
//            }
//        }
//        if(strcmp($waitplayercheckstart,'none') != 0){
//            if(array_key_exists("waitplayercheckstart", $config_ini) ){
//                $waitplayercheckstart = $config_ini["waitplayercheckstart"];
//            }
//        }
//        if(strcmp($playerchecktimes,'none') != 0){
//            if(array_key_exists("playerchecktimes", $config_ini) ){
//                $playerchecktimes = $config_ini["playerchecktimes"];
//            }
//        }
//        if(strcmp($connectinternet,'none') != 0){
//            if(array_key_exists("connectinternet", $config_ini) ){
//                $connectinternet = $config_ini["connectinternet"];
//            }
//        }
//        if(strcmp($commenturl,'none') != 0){
//            if(array_key_exists("commenturl", $config_ini) ){
//                $commenturl = urldecode($config_ini["commenturl"]);
//            }
//        }
//
//        if(strcmp($commenturl_base,'none') != 0){
//            if(array_key_exists("commenturl_base", $config_ini) ){
//                $commenturl_base = urldecode($config_ini["commenturl_base"]);
//            }
//        }
//
//        if(strcmp($commentroom,'none') != 0){
//            if(array_key_exists("commentroom", $config_ini) ){
//                $commentroom = urldecode($config_ini["commentroom"]);
//            }
//        }
//        if(strcmp($moviefullscreen,'none') != 0){
//            if(array_key_exists("moviefullscreen", $config_ini) ){
//                $moviefullscreen = urldecode($config_ini["moviefullscreen"]);
//            }
//        }
//        if(strcmp($helpurl,'none') != 0){
//            if(array_key_exists("helpurl", $config_ini) ){
//                $helpurl = urldecode($config_ini["helpurl"]);
//            }
//        }
//        
//    } else {
//        $fp = fopen($configfile, 'w');
//        fclose($fp);
//    }
//
//    if(empty($dbname)){
//        $dbname = 'request.db';
//        $config_ini = array_merge($config_ini,array("dbname" => $dbname));
//        $fp = fopen($configfile, 'w');
//        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "dbname $dbname";
//        fclose($fp);
//    }
//
//    if(empty($playmode)){
//        $playmode = 3;
//        $config_ini = array_merge($config_ini,array("playmode" => $playmode));
//        $fp = fopen($configfile, 'w');
//        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "playmode $playmode";
//        fclose($fp);
//    }
//
//    if(empty($playerpath)){
//        $playerpath = 'C:\Program Files (x86)\MPC-BE\mpc-be.exe';
//        $config_ini = array_merge($config_ini,array("playerpath" => urlencode($playerpath)));
//        $fp = fopen($configfile, 'w');
//        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "playerpath $playerpath";
//        fclose($fp);
//    }
//
//    if(empty($foobarpath)){
//        $foobarpath = '..\..\foobar2000\foobar2000.exe';
//        $config_ini = array_merge($config_ini,array("foobarpath" => urlencode($foobarpath)));
//        $fp = fopen($configfile, 'w');
//        foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "foobarpath $foobarpath";
//        fclose($fp);
//    }
//
//    if(!strcmp($requestcomment,'none') == 0 ){
//        if(empty($requestcomment)){
//            $requestcomment = "雑談とかどうぞ。その他見つからなかった曲とか、ダウンロードしておいてほしいカラオケ動画のURLと/か/あれば書いておいてもらえるとそのうち増えてるかも";
//            $config_ini = array_merge($config_ini,array("requestcomment" => urlencode($requestcomment)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "requestcomment $requestcomment";
//            fclose($fp);
//        }
//    }
//
//    if(!strcmp($usenfrequset,'none') == 0 ){
//        if(!isset($usenfrequset)){
//            $usenfrequset = 0;
//            $config_ini = array_merge($config_ini,array("usenfrequset" => urlencode($usenfrequset)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "usenfrequset $usenfrequset";
//            fclose($fp);
//        }
//    }
//    if(!strcmp($usevideocapture,'none') == 0 ){
//        if(!isset($usevideocapture)){
//            $usevideocapture = 0;
//            $config_ini = array_merge($config_ini,array("usevideocapture" => urlencode($usevideocapture)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
//            fclose($fp);
//        }
//    }    
//
//    if(!strcmp($historylog,'none') == 0 ){
//        if(!isset($historylog)){
//            $historylog = 0;
//            $config_ini = array_merge($config_ini,array("historylog" => urlencode($historylog)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "historylog $historylog";
//            fclose($fp);
//        }
//    }
//
//    if(!strcmp($waitplayercheckstart,'none') == 0){
//        if(empty($waitplayercheckstart)){
//            $waitplayercheckstart = 2;
//            $config_ini = array_merge($config_ini,array("waitplayercheckstart" => $waitplayercheckstart));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "waitplayercheckstart $waitplayercheckstart";
//            fclose($fp);
//        }
//    }
//
//    if(!strcmp($playerchecktimes,'none') == 0){
//        if(empty($playerchecktimes)){
//            $playerchecktimes = 3;
//            $config_ini = array_merge($config_ini,array("playerchecktimes" => $playerchecktimes));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "playerchecktimes $playerchecktimes";
//            fclose($fp);
//        }
//    }
//    if(!strcmp($connectinternet,'none') == 0){
//        if(empty($connectinternet)){
//            $connectinternet = 1;
//            $config_ini = array_merge($config_ini,array("connectinternet" => $connectinternet));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "connectinternet $connectinternet";
//            fclose($fp);
//        }
//    }
//
//    if(!strcmp($commenturl_base,'none') == 0){
//        if(empty($commenturl_base)){
//            $commenturl_base = "";
//            $config_ini = array_merge($config_ini,array("commenturl_base" => urlencode($commenturl_base)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "commenturl_base $commenturl_base";
//            fclose($fp);
//            //echo "commenturl_base empty";
//            $commenturl = "";
//        }
//    }
//
//    if(!strcmp($commentroom,'none') == 0){
//        if(empty($commentroom)){
//            $commentroom = "";
//            $config_ini = array_merge($config_ini,array("commentroom" => urlencode($commentroom)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "commentroom $commentroom";
//            fclose($fp);
//        }
//        if(!empty($commenturl_base) && !empty($commentroom) ){
//            if(!strcmp($commenturl,'none') == 0){
//                $commenturl = sprintf("%s?r=%s",$commenturl_base,$commentroom);
//            }
//        }
//    }
//    
//    
//    
//    if(!strcmp($moviefullscreen,'none') == 0){
//        if(empty($moviefullscreen)){
//            $moviefullscreen = "";
//            $config_ini = array_merge($config_ini,array("moviefullscreen" => $moviefullscreen));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "moviefullscreen $moviefullscreen";
//            fclose($fp);
//        }
//    }
//    if(!strcmp($helpurl,'none') == 0){
//        if(empty($helpurl)){
//            $helpurl = "";
//            $config_ini = array_merge($config_ini,array("helpurl" => urlencode($helpurl)));
//            $fp = fopen($configfile, 'w');
//            foreach ($config_ini as $k => $i) fputs($fp, "$k=$i\n");
////        print "helpurl $helpurl";
//            fclose($fp);
//        }
//    }
        

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

readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog,$waitplayercheckstart,$playerchecktimes,$connectinternet,$usevideocapture,$moviefullscreen,$helpurl,$commenturl_base,$commentroom,$commenturl);
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

