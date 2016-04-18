<?php
require("./qrcode_img.php");

if(array_key_exists("data", $_REQUEST)) {
    $l_data = $_REQUEST["data"];
}else{
    $l_data = false;
}

$l_qrsize = 5;
if(array_key_exists("qrsize", $_REQUEST)) {
    $l_qrsize = $_REQUEST["qrsize"];
}

if($l_data === false)
{
    print "No QR Data\n";
    die();
}

Header("Content-type: image/png");

$z=new Qrcode_image;

#$z->set_qrcode_version(1);           # set qrcode version 1
#$z->set_qrcode_error_correct("H");   # set ecc level H
$z->set_module_size($l_qrsize);              # set module size 3pixel
#$z->set_quietzone(5);                # set quietzone width 5 modules


$z->qrcode_image_out($l_data,"png");

#$z->image_out($z->cal_qrcode($data),"png");   #old style

?>
