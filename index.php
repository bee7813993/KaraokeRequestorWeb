<?php
$password = false;
$locationurl='requestlist_only.php';
if(array_key_exists("easypass", $_REQUEST)){
    $password = $_REQUEST["easypass"];
    $locationurl = $locationurl.'?easypass='.$password;
}
 header('location: '.$locationurl);
 exit();
?>