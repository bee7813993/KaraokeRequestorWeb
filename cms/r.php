<?php
require_once('commentcommonfunc.php');

//===================初期設定====================
$debug_file = 'debug.dat'; //データファイル名
//===============================================

//スーパーグローバル変数対策
if(!isset($PHP_SELF)){ $PHP_SELF = $_SERVER["PHP_SELF"]; }

if(!isset($msg)){ 
    $msg="";
    if(array_key_exists("msg", $_REQUEST)) {$msg = $_REQUEST['msg']; }
}

if(!isset($nm)){ 
    $nm="";
    if(array_key_exists("nm", $_REQUEST)) {$nm = $_REQUEST['nm']; }
}

if(!isset($col)){
    $col="";
    if(array_key_exists("col", $_REQUEST)) { $col = $_REQUEST['col']; }
}

if(!isset($sz)){
    $sz="";
    if(array_key_exists("sz", $_REQUEST)) {$sz = $_REQUEST['sz']; }
}

if(!isset($p)){
    $p="";
    if(array_key_exists("p", $_REQUEST)) {$p = $_REQUEST['p']; }
}

if(!isset($room)){
    $room="";
    if(array_key_exists("r", $_REQUEST)) { $room = $_REQUEST['r']; }
}

$msg = stripslashes($msg);
$nm = stripslashes($nm);
$col = stripslashes($col);
$sz = stripslashes($sz);
$room = stripslashes($room);
$p = stripslashes($p);

?>
<?php
	$chk = array(0 => '',1 => 'checked="checked"',);
	if($col == "") $col=0;
	if($sz == "") $sz=0;
	
	
	commentdb_init($commentdb,'comment.db');

//	$link = mysql_connect('接続先のMySQLサーバ', 'username', 'password');
//	$db_selected = mysql_select_db('使用するDB名', $link);

	if($msg != ""){
	//MYSQLに接続
		$sql = "insert into dkniko values('".$room."','".$nm."','".$msg."',$sz,'".$col."',datetime('now', 'localtime'),0,null)";
//		$result_flag = mysql_query($sql);
        $result_flag = $commentdb->exec($sql);
        if( $result_flag === FALSE){
          print "コメント追加失敗  <br>\n";
          var_dump($commentdb->errorInfo(), true);
        }
	}
	if(isset($_POST['NEXT'])){
		$p=$p+1;
	}elseif(isset($_POST['PREV'])){
		$p=$p-1;
	}elseif(isset($_POST['SUBMIT'])){
		$p=0;
	}
	$sql = "select * from dkniko where chk = 1 and room = '".$room."' order by regdate desc limit 15 OFFSET ".$p*15;
	$select  = $commentdb->query($sql);
	$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
	// var_dump($allrequest);
	$select->closeCursor();
	$cnt=count($allrequest);
	//$result_flag = mysql_query($sql);
	//$cnt = mysql_num_rows($result_flag);

	//while ($row = mysql_fetch_assoc($result_flag)) {
	//	$buf = $buf.substr($row['regdate'],11,5)." ".$row['name'].":".$row['msg']."<br/>";
	//}
	//mysql_close($link);
	$buf = "";
	foreach($allrequest as $row ){
	   $buf = $buf.$row['regdate']." ".$row['name'].":".$row['msg']."<br/>";
	}
	

	print '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
	print '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>';
	print '<META name=format-detection content=telephone=no></head><body onload="document.getElementById(msg).focus();">';
	print '<form name=forms action="r.php?r='.$room.'&p='.$p.'" method="post"><b>';
	print 'Room:'.$room.'　名前<input type=text name="nm" style="font-size:1em;WIDTH:35%;" fontsize=7 MAXLENGTH="4" value='.$nm.'><br/>';
	print '<font size=-2></font>';
	print 'コメント<br>';
	print '<table border="0.5" cellspacing = "0" cellpadding = "0"><tr>';
	print '<th bgcolor="white"><input type="radio" name="col" value="FFFFFF" '.$chk[($col=="FFFFFF")].'></th>';
	print '<th bgcolor="gray"><input type="radio" name="col" value="808080" '.$chk[($col=="808080")].'></th>';
	print '<th bgcolor="pink"><input type="radio" name="col" value="FFC0CB" '.$chk[($col=="FFC0CB")].'></th>';
	print '<th bgcolor="red"><input type="radio" name="col" value="FF0000" '.$chk[($col=="FF0000")].'></th>';
	print '<th bgcolor="orange"><input type="radio" name="col" value="FFA500" '.$chk[($col=="FFA500")].'></th>';
	print '<th bgcolor="yellow"><input type="radio" name="col" value="FFFF00" '.$chk[($col=="FFFF00")].'></th>';
	print '<th bgcolor="lime"><input type="radio" name="col" value="00FF00" '.$chk[($col=="00FF00")].'></th>';
	print '<th bgcolor="aqua"><input type="radio" name="col" value="00FFFF" '.$chk[($col=="00FFFF")].'></th>';
	print '<th bgcolor="blue"><input type="radio" name="col" value="0000FF" '.$chk[($col=="0000FF")].'></th>';
	print '<th bgcolor="purple"><input type="radio" name="col" value="800080" '.$chk[($col=="800080")].'></th>';
	print '<th bgcolor="black"><input type="radio" name="col" value="111111" '.$chk[($col=="111111")].'></th>';
	print '<th>小<input type="radio" name="sz" value="0"></th>';
	print '<th>中<input type="radio" name="sz" value="3" checked="checked"></th>';
	print '<th>大<input type="radio" name="sz" value="6"></th>';
	print '</tr></table>';
	print '<input type="text" style="font-size:1em;WIDTH:100%;" name="msg" fontsize=8 MAXLENGTH="24" tabindex="1"><br><font size=-1>';
	print '<input type="submit" name="SUBMIT" style="WIDTH:100%; HEIGHT:30;" align="right" value="送信/更新"><br/>';
	if($msg != "")print $msg.'をコメントしました';
	print '<br/><br/></font>';
	print '<DIV style="text-align:left;"><DIV style="text-align:right;float:right;">';
	if($p==0){
		print '<input type="submit" align="right" value="PREV" disabled>　';
	}else{
		print '<input type="submit" name = "PREV" align="right" value="PREV">　';
	}
	if($cnt<15){
		print '<input type="submit" align="right" value="NEXT" disabled>　';
	}else{
		print '<input type="submit" name = "NEXT" align="right" value="NEXT">　';
	}
	print '</form></div>コメント一覧</div>';
	
	print '<font size = 1>'.$buf.'</font></b><br/><br/><br/>
<div id="xdomain_ad_468x60"></div></html>';
?>	