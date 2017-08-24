<?php
require_once 'pfwdctl.php';

$pfwdinfo = new pfwd();
$pfwdinfo->readpfwdcfg();

print '{"pfwdstat": ';
if($pfwdinfo->statpfwdcmd()){
print 'true';
}else {
print 'false';
}
print '}';
//var_dump($pfwdinfo->statpfwdcmd());
?>