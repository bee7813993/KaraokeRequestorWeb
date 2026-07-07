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
       set_time_limit(300);
       $kc = new EasyKeychanger();
       while (1) {

           /* ループ自体が0.5秒周期で再試行するため、1回の確認での内部リトライは1回に抑える */
           $newstatus = $kc->getstatus(1);
           if( $newstatus === false ){
             /* キーチェンジャー不達 */
             if($firstflg || $status !== false){
               print "data:"."None"."\n\n";
               $status = false;
               $firstflg = false;
             }
           }else if( $newstatus != $status) {
               $status = $newstatus;
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
           }else if($firstflg){
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
           }
           print ": ping\n\n"; /* SSEコメント行。切断済みクライアントへの書き込み失敗で即終了させる */
           ob_flush();
           flush();
           if (connection_aborted()) break;
           /* 不達時は確認間隔を5秒に広げて接続試行を抑える */
           usleep($status === false ? 5000000 : 500000); /* サーバー側では0.5秒おきにチェック */
       }
       set_time_limit(300);
   }
?>
