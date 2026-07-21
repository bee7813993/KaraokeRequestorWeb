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


   if(array_key_exists("requestlistactivereload", $cfg) && $cfg["requestlistactivereload"] == 1) {   // config check
   set_time_limit(120); /* 下の自発的な接続終了 (90秒) が効かない異常時の保険 */
   $sse_started = time();
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
           print ": ping\n\n"; /* SSEコメント行。切断済みクライアントへの書き込み失敗で即終了させる */
           ob_flush();
           flush();
           if (connection_aborted()) break;
           /* 接続は90秒で自発的に閉じる (クライアントの EventSource は自動で再接続する)。
              長時間の接続保持がブラウザの同一サーバー接続枠 (HTTP/1.1 は最大6本) を
              占有し続け、他ページの表示や予約送信が止まる不具合の対策 */
           if (time() - $sse_started >= 90) break;
           usleep(500000); /* サーバー側では0.5秒おきにチェック */
       }
   set_time_limit(30);
   }
?>
