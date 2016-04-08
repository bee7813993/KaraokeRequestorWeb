<?php
$user=null;
require_once 'commonfunc.php';

global $showsonglengthflag;


if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}

if(array_key_exists("keyword", $_REQUEST)) {
    $keyword = $_REQUEST["keyword"];
}
$bgvmode = 0;
if(array_key_exists("bgvmode", $_REQUEST)) {
    $bgvmode = $_REQUEST["bgvmode"];
}

$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
}

$path = null;
if(array_key_exists("path", $_REQUEST)) {
    $path = $_REQUEST["path"];
}

if(!isset($keyword)){
    die();
}


$order = null;
if(array_key_exists("order", $_REQUEST)) {
    $order = $_REQUEST["order"];
}
searchlocalfilename($keyword,$result_a,$order,$path);

if( $result_a["totalResults"] >= 1) {
    //build search result 
    $result_withp = sortpriority($priority_db,$result_a);
    $resultlisttable = array();
    foreach($result_withp["results"] as $k=>$v ){
        $oneresult = array();
        
        
        //var_dump($v);
        if($v['size'] <= 1 ) continue;

    	if($showsonglengthflag == 1 ){
    	  try{
    		$sjisfilename = addslashes(mb_convert_encoding($v['path'] . "\\" . $v['name'], "cp932", "utf-8"));
    		//print $sjisfilename."\n";
    		$music_info = @$getID3->analyze($sjisfilename);
    		getid3_lib::CopyTagsToComments($music_info); 
    	  }catch (Exception $e) {
    	    print $sjisfilename."\n";
    	  }	
			if(empty($music_info['playtime_string'])){
			   $length_str = 'Unknown';
			}else {
			   $length_str = $music_info['playtime_string'];
			}
		}
		 $oneresult += array("no" => $k);
		 
		 $reqbtn = '<form action="request_confirm.php" method="post" >';
		 $reqbtn = $reqbtn . "\n" . '<input type="hidden" name="filename" id="filename" value="'. $v['name'] . '" />';
		 $reqbtn = $reqbtn . "\n" . '<input type="hidden" name="fullpath" id="fullpath" value="'. $v['path'] . '\\' . $v['name'] . '" />';
		 if($bgvmode == 1){
		     $reqbtn = $reqbtn . "\n" . '<input type="hidden" name="forcebgv" id="forcebgv" value="1" />';
		 }
		 if(is_numeric($selectid)){
		     $reqbtn = $reqbtn . "\n" . '<input type="hidden" name="selectid" id="selectid" value='.$selectid.' />';
		 }
		 $reqbtn = $reqbtn . "\n" . '<input type="submit" value="リクエスト" />';
		 $reqbtn = $reqbtn . "\n" . '</form>';
		 $oneresult += array("reqbtn" => $reqbtn);
		 
		 $fn = htmlspecialchars($v['name']);
		 if($user == 'admin' ) {
		     $fn = $fn . "\n" . '<br/>おすすめ度 :'.$v['priority'];
		 }
//		 $previewpath = "http://" . $everythinghost . ":81/" . $v['path'] . "/" . $v['name'];
		 $previewpath = $v['path'] . "/" . $v['name'];
		 $previewmodal = make_preview_modal($previewpath,$k); 
		 $fn = $fn . "\n" . '<div Align="right">';
		 $fn = $fn . $previewmodal;
		 $fn = $fn . '</div>';
		 $oneresult += array("filename" => $fn);
		 
		 $fs = formatBytes($v['size']);
		 $oneresult += array("filesize" => $fs);
		 
		 if($showsonglengthflag == 1 ){
		     $oneresult += array("length" => $length_str);
		 }
		 
		 $oneresult += array("filepath" => htmlspecialchars($v['path']));
		 
		 array_push ($resultlisttable, $oneresult);
    }
    $json = json_encode($resultlisttable,JSON_PRETTY_PRINT);
	print $json;
}


?>

