<!doctype html>
<html lang="ja">
<head>
  
<META http-equiv="refresh" content="1; url=notfoundrequest.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>見つからなかったリスト更新中</title>
<link type="text/css" rel="stylesheet" href="../css/style.css" />
</head>
<body>


<a href="notfoundrequest.php" > 見つからなかったリストに戻る </a><br>

<?php
$db = null;
include 'notfound_commonfunc.php';
init_notfounddb($db,"notfoundrequest.db");

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}else {
    printf("No ID");
    die();
}
$itemcount = 0;
    $sql_u = 'UPDATE notfoundtable set ';

if(array_key_exists("requesttext", $_REQUEST)) {
    $l_requesttext = $_REQUEST["requesttext"];
    $sql_u = $sql_u .' requesttext = \''. $l_requesttext . '\'';
    $itemcount++;
}

if(array_key_exists("status", $_REQUEST)) {
    $l_status = $_REQUEST["status"];
    if($itemcount > 0) {
        $sql_u = $sql_u . ', ';
    }
    $sql_u = $sql_u .' status = '.$l_status;
    $itemcount++;
}

if(array_key_exists("reply", $_REQUEST)) {
    $l_reply = $_REQUEST["reply"];
    if($itemcount > 0) {
        $sql_u = $sql_u . ', ';
    }
    $sql_u = $sql_u .' reply = \''. $l_reply . '\'';
    $itemcount++;
}

if(array_key_exists("searchword", $_REQUEST)) {
    $l_searchword = $_REQUEST["searchword"];
    if($itemcount > 0) {
        $sql_u = $sql_u . ', ';
    }
    $sql_u = $sql_u .' searchword = \''. $l_searchword . '\'';
    $itemcount++;
}


if($itemcount > 0 ){
     $sql_u = $sql_u . ' WHERE id = '. $l_id;
     // print "DEBUG : $sql_u";
     try{
         $ret = $db->query($sql_u);
     }catch(PDOException $e) {
         printf("Error: %s\n", $e->getMessage());
         die();
     }
     print('更新完了。');

}else {
   print('更新する項目はありません。');
}


?>
&nbsp;
<a href="../requestlist_only.php" >トップに戻る </a>

</body>
</html>