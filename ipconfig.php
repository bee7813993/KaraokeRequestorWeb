<?php
function getiplist() {
  $result_ipconfig = array();
  
//  exec("C:\\Windows\\System32\\calc.exe",$result_ipconfig);
  exec("chcp 437&ipconfig&chcp 932",$result_ipconfig);
//  exec("chcp 932");
  //var_dump($result_ipconfig);
  
  $kind = 0;
  $iplist = array();
  $oneip = array();
  
  foreach($result_ipconfig as $l){
     //print "len:".strlen($l)."\n";
     if(strlen($l) == 0) continue;
     $match_res=stristr($l,"Windows IP Configuration") ;
     //print "match:".$match_res."\n";
     if($match_res !== false ) continue;
     $match_res=stristr($l,"Active code page") ;
     if($match_res !== false ) continue;
     $match_res=stristr($l,"ipconfig.exe") ;
     if($match_res !== false ) continue;
     $match_res=stristr($l,"現在のコード ページ") ;
     if($match_res !== false ) continue;
     //print $l."\n";
     
         
         if( preg_match('/^ +/',$l ) === 1 ){
             $match_res_4=stristr($l,"IPv4 Address");
             $match_res_6=stristr($l,"IPv6 Address");
             if($match_res_4 === false && $match_res_6 === false) continue;
             
             $pos = strrchr($l,' ');
             if($pos !== false){
                 $oneip[]=trim($pos);
             }
         }else{
             if(count($oneip) > 0) {
                 $iplist[] = $oneip;
                 $oneip = array();
             }
             $oneip[]=$l;
         }
  }
  if(count($oneip) > 0) {
      $iplist[] = $oneip;
  }
  // var_dump($iplist);
  return $iplist;
}
  
?>

