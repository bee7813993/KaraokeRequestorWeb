<?php 
if(!isset($lister_dbpath) )
$lister_dbpath = "list\List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

    http_response_code( 301 ) ;
    header( "Location: ./search_listerdb_program_index.php?".$_SERVER['QUERY_STRING'] ) ;
    exit;
?>
<html>
<head>
</body>
</html>
