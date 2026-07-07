<?php
   require_once 'func_keychange.php';
   require_once 'func_readconfig.php';
   
   $readconfig = new ReadConfig();
   $cfg = $readconfig->read_config();

   if($cfg["usekeychange"] == 1) {
       $kc = new EasyKeychanger();
       /* キーチェンジャー不達時に接続試行が積み上がらないようリトライは2回まで */
       $status = $kc->getstatus(2);
       if($status){
           print $status["currentkey"];
       }else {
               print "None";
       }
   }
?>
