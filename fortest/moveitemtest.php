<?php

require_once('../commonfunc.php');
require_once('../function_moveitem.php');

$list = new MoveItem;

$turnlist = $list->getturnlist($db);

print "<pre>";
print "turnlist:\n";
 var_dump($list->turnlist);
print "</pre>";

$id = 24;
if(array_key_exists("id", $_REQUEST)) {
    $id = $_REQUEST["id"];
}



$newreq = $list->get_new_reqorder($id);
print 'NewReqNo:'.$newreq.', id:'.$id."<br>\n";
$list->insertreqorder($id,$newreq);

print "<pre>";
// var_dump($list->allrequest_new);
print "</pre>";

$list->save_allrequest($db);

$turnlist = $list->getturnlist($db);
print "<pre>";
var_dump($list->turnlist);
print "</pre>";

?>