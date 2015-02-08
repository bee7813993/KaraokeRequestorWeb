<?php
include 'kara_config.php';

$MPCPATH="C:\Program Files (x86)\MPC-BE\mpc-be.exe";

$MPCSTATURL='http://localhost:13579/info.html';

function runningcheck(){

   global $MPCSTATURL;
   // get MPC status
   $exit = 1;
   while($exit == 1)
   {
       // MPCの状態取得3回チャレンジする
       for($loopcount = 0 ; $loopcount < 3 ; $loopcount ++){
       $mpcstat = file_get_contents($MPCSTATURL);
       if( $mpcstat === FALSE) {
           sleep(1);
           continue;
       }else{
           break;
       }
       }
       if($loopcount === 3){
           print("maybe stop player\n");
           break;
       }
       $mpsctat_array = explode('&bull', $mpcstat );
       // var_dump($mpsctat_array);
       $etime_a =  explode('/', trim($mpsctat_array[2], ' ;') );
       // var_dump($etime_a);
       $playtime_a =  explode(':', $etime_a[0] );
       $totaltime_a =  explode(':', $etime_a[1] );
       $playtime = $playtime_a[0]*60*60 + $playtime_a[1]*60 + $playtime_a[2];
       $totaltime = $totaltime_a[0]*60*60 + $totaltime_a[1]*60 + $totaltime_a[2];
       if($playtime > ($totaltime - 4) )
         break;
       print $mpsctat_array[2];
       echo ', ';
       print $playtime;
       echo ':';
       print $totaltime;
       echo "\n";

       sleep(2);
   }
}


while(1){
    
    $played = 5;  // 5:no next song wait sec. 1: played and next song wait sec.
    
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = '未再生' ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
     $word=$row['songfile'];
     $l_id=$row['id'];
     $l_fullpath=$row['fullpath'];
     $select->closeCursor();
print "Debug l_fullpath: $l_fullpath\r\n";
     $winfillpath = mb_convert_encoding($l_fullpath,"SJIS");
     if(file_exists($winfillpath )){
         $filepath = $winfillpath;
     }else{
print "Debug word: $word\r\n";
         $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
         print $jsonurl;
         $json = file_get_contents($jsonurl);
         $decode = json_decode($json, true);
         $filepath = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
         $filepath = mb_convert_encoding($filepath,"SJIS");
     }
print "Debug filepath: $filepath\r\n";
     if($playmode == 1){
     $execcmd="start  \"\" \"".$MPCPATH."\"" . " /play \"$filepath\"\n";
     }elseif ($playmode == 2){
     $execcmd="start /w \"\" \"".$MPCPATH."\"" . " /open \"$filepath\"\n";
     }else{
         print(" Debug : now auto play is off : $playmode\n");
         sleep(30);
         continue;
     }
     print(" Debug : execcmd : $execcmd\n");

//     initdb($db,$dbname);
     
     $db->beginTransaction();
     $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
     $ret = $db->exec($sql);
if (! $ret ) {
	print("再生中 への変更にしっぱいしました。<br>");
}
     $db->commit();
//     $db=null;
     sleep(1);
     exec($execcmd);
     runningcheck();
     
//     initdb($db,$dbname);
     
     $sql = "UPDATE requesttable set nowplaying = \"再生済\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
//     $sql = "UPDATE requesttable set nowplaying = \"未再生\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
     $ret = $db->exec($sql);
 if (! $ret ) {
	print("再生済への変更にしっぱいしました。<br>");
 }
     $sql = "UPDATE requesttable set nowplaying = \"再生済？\" WHERE nowplaying = \"再生中\" ";
     $ret = $db->query($sql);
if (! $ret ) {
	print("再生済？ への変更にしっぱいしました。<br>");
}
//     $db=null;
//     sleep(1);
    $played=1;

     break;
    }
    
    if( $played === 5)
    print("no next song, waiting...<br>\n");

    sleep($played);
//     break;

}
?>