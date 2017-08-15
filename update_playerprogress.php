<?php
   require_once 'function_updatenotice.php';
   
   $un = new UpdateNotice();
   $un->initdb();
   $un->updateplayerprogress();
   $un->closedb();
?>
