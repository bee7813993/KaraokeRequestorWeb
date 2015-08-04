<?php

$priority_db = null;

$kind_num = array ('dir' => 1, 'file' => 2);

function prioritydb_init($dbname = 'prioritydb.db')
{

try {
	$priority_db = new PDO('sqlite:'. $dbname);
} catch(PDOException $e) {
	printf("new PDO Error: %s\n", $e->getMessage());
	die();
} 
$sql = "create table IF NOT EXISTS prioritytable (
 id INTEGER PRIMARY KEY AUTOINCREMENT, 
 kind INTEGER, ". // 1 : dir, 2: file
" priorityword text,
 prioritynum INTEGER
)";
$stmt = $priority_db->query($sql);
if ($stmt === false ){
	print("Create table 失敗しました。<br>");
	print_r($priority_db->errorInfo());
	die();
}
 return($priority_db);

}

function prioritydb_add($priority_db, $kind, $priorityword, $prioritynum){
    global $kind_num;
    
    $res = true;
    $sql = sprintf("INSERT INTO prioritytable (kind, priorityword, prioritynum) VALUES (%d, '%s', %d)",$kind_num[$kind], $priorityword, $prioritynum);
    $res = $priority_db->query($sql);
    if($res === false ){
        print("INSERT 失敗しました。<br>");
        print("sql : ".$sql);
        print_r($priority_db->errorInfo());
        //die();
    }
    return $res;
}

function prioritydb_delete($priority_db, $id){
    global $kind_num;
    
    $res = true;
    $sql = sprintf("DELETE FROM prioritytable where id = %d",$id);
    $res = $priority_db->query($sql);
    if($res === false ){
        print("DELETE 失敗しました。<br>");
        print("sql : ".$sql);
        print_r($priority_db->errorInfo());
        //die();
    }
    return $res;
}

function prioritydb_get($priority_db){
    $sql = "SELECT * FROM prioritytable ORDER BY prioritynum DESC";
    $select = $priority_db->query($sql);
    $allpriority = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();

    return $allpriority;
}

$priority_db = prioritydb_init();

?>