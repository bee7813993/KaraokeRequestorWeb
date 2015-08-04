<?php

require_once 'prioritydb_func.php';

$allprioritylist = prioritydb_get($priority_db);
$allprioritylist_table = array();

foreach($allprioritylist as $value ){
    $onerequset = array();
    $onerequset += array("id" => $value['id']);
    $onerequset += array("kind" => $value['kind']);
    $onerequset += array("priorityword" => $value['priorityword']);
    $onerequset += array("prioritynum" => $value['prioritynum']);
    
    $deletelink_pf = <<<EOD
<form method="get" action="edit_priority.php" style="display: inline" >
<input type="hidden" name="action" value="delete" />
<input type="hidden" name="id" value="%s" />
<input type="submit" name="update" value="削除"/>
</form>
EOD;
    $deletelink = sprintf($deletelink_pf,$value['id']);
    $onerequset += array("action" => $deletelink);
    
    array_push ($allprioritylist_table, $onerequset);
}

$json = json_encode($allprioritylist_table,JSON_PRETTY_PRINT);

print $json;
?>