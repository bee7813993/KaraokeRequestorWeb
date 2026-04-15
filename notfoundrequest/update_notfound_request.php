<?php
$db = null;
include 'notfound_commonfunc.php';
init_notfounddb($db,"notfoundrequest.db");

$l_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($l_id === false || $l_id === null) {
    $l_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
if ($l_id === false || $l_id === null) {
    http_response_code(400);
    printf("No ID");
    die();
}

$set_clauses = array();
$params = array();

if(array_key_exists("requesttext", $_REQUEST)) {
    $set_clauses[] = 'requesttext = :requesttext';
    $params[':requesttext'] = $_REQUEST["requesttext"];
}

if(array_key_exists("status", $_REQUEST)) {
    $l_status = filter_var($_REQUEST["status"], FILTER_VALIDATE_INT);
    if ($l_status === false) $l_status = 0;
    $set_clauses[] = 'status = :status';
    $params[':status'] = $l_status;
}

if(array_key_exists("reply", $_REQUEST)) {
    $set_clauses[] = 'reply = :reply';
    $params[':reply'] = $_REQUEST["reply"];
}

if(array_key_exists("searchword", $_REQUEST)) {
    $set_clauses[] = 'searchword = :searchword';
    $params[':searchword'] = $_REQUEST["searchword"];
}

if(count($set_clauses) > 0){
    $sql_u = 'UPDATE notfoundtable SET ' . implode(', ', $set_clauses) . ' WHERE id = :id';
    $params[':id'] = $l_id;
    try{
        $stmt = $db->prepare($sql_u);
        $stmt->execute($params);
    }catch(PDOException $e) {
        printf("Error: %s\n", $e->getMessage());
        die();
    }
}

$db = null;

header('Location: notfoundrequest.php');
exit;
