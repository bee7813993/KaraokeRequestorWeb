<?php
//setlocale(LC_ALL, 'ja_JP.Shift_JIS');
date_default_timezone_set('Asia/Tokyo');
include 'kara_config.php';
require_once 'commonfunc.php';
require_once("getid3/getid3.php");

if(empty($playerpath)){
    $MPCPATH='C:\Program Files (x86)\MPC-BE\mpc-be.exe';
}else{
    $MPCPATH=$playerpath;
}

if(empty($foobarpath)){
    $FOOBARPATH='C:\Program Files (x86)\foobar2000\foobar2000.exe';
}else{
    $FOOBARPATH=$foobarpath;
}
$MPCSTATURL='http://localhost:13579/info.html';
$MPCCMDURL='http://localhost:13579/command.html';

function mpcstop(){
   global $MPCCMDURL;
   $pscheck_cmd='tasklist /fi "imagename eq mpc-be.exe"';
   exec($pscheck_cmd, $psresult );
   sleep(1);
   
   $process_found = 0;
   
   foreach( $psresult as $psline ){
     $pos = strpos($psline,"mpc-be.exe");
     if ( $pos !== FALSE) {
        $process_found = 1;
     }
   }
   
   if($process_found == 1){
       for($loopcount = 0 ; $loopcount < 2 ; $loopcount ++){
//          $org_timeout = ini_get('default_socket_timeout');
//          ini_set('default_socket_timeout', 2);
          $mpcstat = file_get_html_with_retry($MPCCMDURL."?wm_command=816", 5);
//          ini_set('default_socket_timeout', $org_timeout);
          if( $mpcstat === FALSE) {
              sleep(1);
              continue;
          }else{
              break;
          }
       }
   }
}

function runningcheck_shop_karaoke($db,$id){

   $exit = 1;
   while($exit == 1)
   {
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' || $currentstatus[0]['nowplaying'] === '再生済'){
           print date("H.i.s")."Status is change to ".mb_convert_encoding($currentstatus[0]['nowplaying'],"SJIS")." at id: ".$id."\n";
           $exit = 0;
           break;
       }
       //print "DEBUG: Endtime: " . date("H.i.s", $endtime) . ", Now: ".date("H.i.s", time());
       sleep(2);
   }
}

function runningcheck_audio($db,$id,$endtime){

   $exit = 1;
   while($exit == 1)
   {
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' ){
           $exit = 0;
           break;
       }
       
       if( time() > $endtime ){
          print "DEBUG: Endtime: " . date("H.i.s", $endtime) . ", Now: ".date("H.i.s", time())."\n";
          $exit = 0;
          break;
       }
       //print "DEBUG: Endtime: " . date("H.i.s", $endtime) . ", Now: ".date("H.i.s", time())."\n";
       sleep(2);
   }
}

function runningcheck_mpc($db,$id){

   global $MPCSTATURL;
   // get MPC status
   $exit = 1;
   while($exit == 1)
   {
       // db statusを確認
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' ){
           break;
       }
       
       // MPCの状態取得3回チャレンジする
       for($loopcount = 0 ; $loopcount < 3 ; $loopcount ++){
//          $org_timeout = ini_get('default_socket_timeout');
//          ini_set('default_socket_timeout', 5);
          $mpcstat = file_get_html_with_retry($MPCSTATURL, 5);
//          ini_set('default_socket_timeout', $org_timeout);
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
       if($playtime > ($totaltime - 4) ){
       print $mpsctat_array[2];
           echo ', ';
           print $playtime;
           echo ':';
           print $totaltime;
           echo "\n";
           break;
       }

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
     $l_kind=$row['kind'];
     $select->closeCursor();

     //config 再読込
     readconfig($dbname,$playmode,$playerpath,$fooobarpath);
     if(! empty($playerpath)){
        $MPCPATH = $playerpath;
     }



       
       if( strcmp ($l_kind , "カラオケ配信") === 0 )
       {
          $db->beginTransaction();
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
          $ret = $db->exec($sql);
          if (! $ret ) {
          	print("再生中 への変更にしっぱいしました。<br>");
          }
          $db->commit();         
          // カラオケ配信になっている場合、リクエストのリストで再生済みに変更されるまで待機する
          print(mb_convert_encoding("カラオケ配信終了待ち。「曲終了」ボタンを押すか、再生状況が「再生済」に変更されるまで停止\n","SJIS"));
          runningcheck_shop_karaoke($db,$l_id);
       }else
       {
       // ファイル名のチェック
//print "Debug l_fullpath: $l_fullpath\r\n";
       $winfillpath = mb_convert_encoding($l_fullpath,"SJIS");
       if(file_exists($winfillpath )){
         $filepath = $winfillpath;
       }else{
//print "Debug word: $word\r\n";
         $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
         print $jsonurl;
         $json = file_get_html_with_retry($jsonurl, 5);
         $decode = json_decode($json, true);
         $filepath = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
         $filepath = mb_convert_encoding($filepath,"cp932");
       }
//print "Debug filepath: $filepath\r\n";
           
           // 拡張子をチェックしてPlayerを選択
           $extension = pathinfo($filepath, PATHINFO_EXTENSION);
           if( strcasecmp($extension,"mp3") == 0 
           || strcasecmp($extension,"m4a") == 0 
           || strcasecmp($extension,"wav") == 0 ){
               // audio file
               
               if($playmode == 1){
               $execcmd="start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " \"$filepath\"\n";
               }elseif ($playmode == 2){
               $execcmd="start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " \"$filepath\"\n";
               }else{
                   print(" Debug : now auto play is off : $playmode\n");
                   sleep(30);
                   continue;
               }
//               print(" Debug : execcmd : $execcmd\n");
               
               try{
               
               $cmd = "copy /Y \"".$filepath."\" temp.".$extension;
               echo $cmd."\n";
               exec($cmd);
               
    //           echo mb_ereg_replace("\\x5c","/",$filepath);
               $getID3 = new getID3();
               $music_info = $getID3->analyze("temp.".$extension);
               getid3_lib::CopyTagsToComments($music_info); 

               }catch (Exception $e) {
               echo 'error: '.$e->getMessage()."\n";
               }
               exec("del temp.".$extension);

               // var_dump($music_info);

               
               $db->beginTransaction();
               $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
               $ret = $db->exec($sql);
               if (! $ret ) {
               	print("再生中 への変更にしっぱいしました。<br>");
               }
               $db->commit();
               // とりあえず動画Playerを終了する。
               mpcstop();

               sleep(1);
               exec($execcmd);
               sleep(2); // Player 起動待ち
               echo "song length: ".$music_info["playtime_seconds"]." ".$music_info["playtime_string"]."\n";
               $endtime=time() + (int)$music_info["playtime_seconds"] + 3;
               runningcheck_audio($db,$l_id,$endtime);
               
           }else {
               // video file
               if($playmode == 1){
               $execcmd="start  \"\" \"".$MPCPATH."\"" . " /play \"$filepath\"\n";
               }elseif ($playmode == 2){
               $execcmd="start  \"\" \"".$MPCPATH."\"" . " /open \"$filepath\"\n";
               }else{
                   print(" Debug : now auto play is off : $playmode\n");
                   sleep(30);
                   continue;
               }
//               print(" Debug : execcmd : $execcmd\n");

               
               $db->beginTransaction();
               $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
               $ret = $db->exec($sql);
               if (! $ret ) {
               	print("再生中 への変更にしっぱいしました。<br>");
               }
               $db->commit();
               sleep(1);
               exec($execcmd);
               sleep(2); // Player 起動待ち
               runningcheck_mpc($db,$l_id);
               
           }
       
       }



     $sql = "UPDATE requesttable set nowplaying = \"再生済\" WHERE id = $l_id ";
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