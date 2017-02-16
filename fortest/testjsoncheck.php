<?php

require_once 'commonfunc.php';

$xamppstop_cmd = '..\xampp_stop.exe';
$xamppstart_cmd = '..\xampp_start.exe';


$url = 'http://localhost/requestlist_table_json.php';
if( check_json_available_fromurl($url)){
   print 'json available';
} else {
   print 'json not available'."\n";
   print 'now stopping xampp'."\n";
   exec($xamppstop_cmd);
   print 'now starting xampp'."\n";
   $cmd = 'start "" /MIN '.$xamppstart_cmd.' > NUL';
   $fp = popen($cmd,'r');
   pclose($fp);
   sleep(1);
}
if( check_json_available_fromurl($url)){
   print 'json available';
} else {
   print 'json not available'."\n";
}

?>