<?php
  //日本語
  //setlocale(LC_ALL,  'ja_JP.UTF-8');

$YkariUsername = "";
if(array_key_exists("YkariUsername", $_COOKIE)) {
    $YkariUsername = $_COOKIE["YkariUsername"];
}else if(array_key_exists("YkariUsername", $_REQUEST)) {
    $YkariUsername = $_REQUEST["YkariUsername"];
}

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'func_audiotracklist.php';

function pickupsinger($rt, $moreuser = "")
{
   $singerlist = array();
   if(!empty($moreuser)){
       $singerlist[] = $moreuser;
   }
   foreach($rt as $row)
   {
       $foundflg = 0;
       foreach($singerlist as $esinger ){
           if( $esinger === $row['singer']){
               $foundflg = 1;
               break;
           }
       }
       if($foundflg === 0){
           $singerlist[] = $row['singer'];
       }
   }
   
   return $singerlist;
}

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$singerlist = pickupsinger($allrequest,$YkariUsername);
$returnsinger = array();
foreach($singerlist as $singerone) {
    $singerme = 0;
    if( $singerone === $YkariUsername) {
       $singerme = 1;
    }
    $returnsinger[] = array('singer' => $singerone , 'singerme' => $singerme );
//    $returnsinger[] = array('singerme' => 1, 'singer' => $singerone );
}

print json_encode($returnsinger);
?>