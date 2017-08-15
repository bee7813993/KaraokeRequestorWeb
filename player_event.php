<?php
   /* 現在のPlayer変更状態をリアルタイムで表示するための Server Sent Event 処理 */
   header('Content-Type: text/event-stream');
   header('Cache-Control: no-cache');
   
   require_once 'func_keychange.php';
   require_once 'func_readconfig.php';
   require_once 'func_playerprogress.php';
   require_once 'function_updatenotice.php';
   
   $readconfig = new ReadConfig();
   $cfg = $readconfig->read_config();
   $playstat = new PlayerProgress;
   $playerstatus_old = false;
   
   $status_progress=false;
   $status_kind=false;
   $firstflg = true;
   
   $un = new UpdateNotice();
   $un->initdb();
      
   set_time_limit(600);  // set timeout 600sec
   while (1) {
       $response_array = array();
       
       // keychange
       // update player

       $statusarray =  $un->show_all();
       //var_dump($statusarray);
       if(array_key_exists('playerprogress', $statusarray[0])) {
           $newstatus_progress = $statusarray[0]['playerprogress'];
           if( $newstatus_progress != $status_progress) {
               $status_progress = $newstatus_progress;
               $response_array += array('playerprogress' => $status_progress );
           }
       }

       $playerstatus_json = $playstat->getplaystatus_json();
       if( $playerstatus_json ) {
           $playerstatus_array = json_decode($playerstatus_json,true);
           if(!empty($playerstatus_array['status'])) {
               if($playerstatus_old != $playerstatus_array['status'] ){
                   $playerstatus_old = $playerstatus_array['status'];
                   $response_array += array('playerstatus' => $playerstatus_array['status'] );
               }
           }
       }
       
       // change player
       if(array_key_exists('playerkind', $statusarray[0])) {
           $newstatus_kind = $statusarray[0]['playerkind'];
           if( $newstatus_kind != $status_kind) {
               $status_kind = $newstatus_kind;
               $response_array += array('playerkind' => $newstatus_kind );
           }
       }
       
       // output stream
       if(!empty($response_array)) {
           $response_array_json = json_encode($response_array);
           if($response_array_json)
               print "data:".$response_array_json."\n\n";
           ob_flush();
           flush();
       }
       
       usleep(500000); /* サーバー側では0.5秒おきにチェック */
       
   }
   

//   if($cfg["usekeychange"] == 1) {
//       set_time_limit(300);
//       $kc = new EasyKeychanger();
//       while (1) {
//           $newstatus = $kc->getstatus();
//           if( $newstatus != $status) {
//             $status = $newstatus;
//             
//             if($status){
//               print "data:".$status["currentkey"]."\n\n";
//               $firstflg = false;
//             }else {
//               print "data:"."None"."\n\n";
//             }
//           }
//           if($firstflg){
//             if($status){
//               print "data:".$status["currentkey"]."\n\n";
//               $firstflg = false;
//             }else {
//               print "data:"."None"."\n\n";
//             }
//           }
//           //print "1";
//           ob_flush();
//           flush();
//           usleep(500000); /* サーバー側では0.5秒おきにチェック */
//       }
//       set_time_limit(300);
//   }
?>
