<?php
   /* 現在のキー変更状態をリアルタイムで表示するための Server Sent Event 処理 */
   header('Content-Type: text/event-stream');
   header('Cache-Control: no-cache');
   
   /* キーチェンジャーが無効だと判定するアクセスチェック回数 */
   $keychanger_checktimes_max = 4;
   $keychanger_checktimes = 0;
   
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
       
           $newstatus = $kc->getstatus();
           if( $newstatus == 'failed' ){
             if($firstflg){
               print "data:"."None"."\n\n";
             }else if( $keychanger_checktimes++ > $keychanger_checktimes_max ){
                $status = $newstatus;
                print "data:"."None"."\n\n";
             }
           }else if( $newstatus != $status) {
               $status = $newstatus;
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
               $keychanger_checktimes = 0;
           }else if($firstflg){
               print "data:".$status["currentkey"]."\n\n";
               $firstflg = false;
               $keychanger_checktimes = 0;
           }
           //print "1";
           ob_flush();
           flush();
           usleep(500000); /* サーバー側では0.5秒おきにチェック */
       }
       set_time_limit(300);
   }
?>
