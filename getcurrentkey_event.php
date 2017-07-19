<?php
   /* 現在のキー変更状態をリアルタイムで表示するための Server Sent Event 処理 */
   header('Content-Type: text/event-stream');
   header('Cache-Control: no-cache');
   
   require_once 'func_keychange.php';
   require_once 'func_readconfig.php';
   
   $readconfig = new ReadConfig();
   $cfg = $readconfig->read_config();
   
   $status=false;
   $firstflg = true;

   if($cfg["usekeychange"] == 1) {
       $kc = new EasyKeychanger();
       while (1) {
           $newstatus = $kc->getstatus();
           if( $newstatus != $status) {
             $status = $newstatus;
             
             if($status){
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
             }else {
               print "data:"."None"."\n\n";
             }
           }
           if($firstflg){
             if($status){
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
             }else {
               print "data:"."None"."\n\n";
             }
           }
           //print "1";
           ob_flush();
           flush();
           usleep(500000); /* サーバー側では0.5秒おきにチェック */
       }
   }
?>
