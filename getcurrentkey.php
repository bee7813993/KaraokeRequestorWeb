<?php
   require_once 'func_keychange.php';
   require_once 'func_readconfig.php';
   
   $readconfig = new ReadConfig();
   $cfg = $readconfig->read_config();

   if($cfg["usekeychange"] == 1) {
       $kc = new EasyKeychanger();
       $status = $kc->getstatus();
       if($status){
           print $status["currentkey"];
       }else {
               print "None";
       }
   }
?>
