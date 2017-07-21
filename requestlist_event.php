<?php
   /* リクエスト一覧やプレーヤー種類の変更状態をリアルタイムで表示するための Server Sent Event 処理 */
   header('Content-Type: text/event-stream');
   header('Cache-Control: no-cache');
   
   
   $checkkind = "requestlist";
   if(array_key_exists("kind", $_REQUEST)) {
       $checkkind = $_REQUEST["kind"];
   }
   
   require_once 'func_readconfig.php';
   require_once 'function_updatenotice.php';
   
   $readconfig = new ReadConfig();
   $cfg = $readconfig->read_config();
   
   $status=false;
   $firstflg = true;

   $un = new UpdateNotice();
   $un->initdb();


   if(1 == 1) {   // config check
       while (1) {
           $statusarray =  $un->show_all();
           if(array_key_exists($checkkind, $statusarray[0])) {
               $newstatus = $statusarray[0][$checkkind];
           }else {
               break;
           }
           
           if( $newstatus != $status) {
             $status = $newstatus;
             
             if($status){
               print "data:".$status."\n\n";
               $firstflg = false;
             }else {
               print "data:"."None"."\n\n";
             }
           }
           if($firstflg){
             if($status){
               print "data:".$status."\n\n";
               $firstflg = false;
             }else {
               print "data:"."None"."\n\n";
             }
           }
           ob_flush();
           flush();
           usleep(500000); /* サーバー側では0.5秒おきにチェック */
       }
   }
?>
