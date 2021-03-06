<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<META http-equiv="refresh" content="1; url=requestlist_only.php">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<title>1項目移動・削除</title>
</head>
<body>

<?php
$db = null;

require_once 'commonfunc.php';
shownavigatioinbar();


$tmpid=9999;
/**
 * 行を上に移動
 * @param integer $id
 * @param db $db
 */
function dbup($id, $db)
{
	global $tmpid;
// 対象のreqorderを取得

$sql = "SELECT * FROM requesttable where id = $id ";
$select = $db->query($sql);
$row = $select->fetch(PDO::FETCH_ASSOC);
$targetorder = $row['reqorder'];

 $nextorder = $targetorder + 1 ;
 
 
 $sql = "SELECT * FROM requesttable where reqorder = $nextorder ";
 $select = $db->query($sql);
 $row = $select->fetch(PDO::FETCH_ASSOC);

 if(  $row !== false ){
 // 移動先のreqorder値の項目があったら
 $sql = "UPDATE requesttable set reqorder = $tmpid WHERE reqorder = $nextorder ";
 $db->beginTransaction();
 $ret = $db->query($sql);
 if (! $ret ) {
	print("$nextorder から $tmpid への移動にしっぱいしました。<br>");
	$db->commit();
	die();
 }
 $sql = "UPDATE requesttable set reqorder = $nextorder WHERE id = $id ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} の $nextorder への移動にしっぱいしました。<br>");
	$db->commit();
	die();
 }
 $sql = "UPDATE requesttable set reqorder = $targetorder WHERE reqorder = $tmpid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("$tmpid から $targetorder への移動にしっぱいしました。<br>");
	$db->commit();
	die();
 }
 $db->commit();
 }else{
 print "ここきた";

 $sql = "UPDATE requesttable set reqorder = $nextorder WHERE id = $id ";
 $db->beginTransaction();
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} から $nextid への移動にしっぱいしました。<br>");
	$db->commit();
	die();
 }
 $db->commit();
 }
}

/**
 * 行を下に移動
 * @param integer $id
 * @param db $db
 */
function dbdown($id, $db)
{
	global $tmpid;


// 対象のreqorderを取得
$sql = "SELECT * FROM requesttable where id = $id ";
$select = $db->query($sql);
$row = $select->fetch(PDO::FETCH_ASSOC);
$targetorder = $row['reqorder'];

 $nextorder = $targetorder - 1 ;

 if ($targetorder <= 1 ){
    print("すでに一番下<br>");
 }else{

 
 $sql = "SELECT * FROM requesttable where reqorder = $nextorder ";
 $select = $db->query($sql);
 $row = $select->fetch(PDO::FETCH_ASSOC);
// var_dump($row);
 if(  $row !== false ){
 // 移動先のreqorder値の項目があったら
 $sql = "UPDATE requesttable set reqorder = $tmpid WHERE reqorder = $nextorder ";
 $db->beginTransaction();
 $ret = $db->query($sql);
 if (! $ret ) {
	print("$targetorder から $tmpid  への移動にしっぱいしました。<br>");
	$db->commit();
	return false;
	die();
 }
 $sql = "UPDATE requesttable set reqorder = $nextorder WHERE id = $id ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} の $nextorder への移動にしっぱいしました。<br>");
	$db->commit();
	return false;
	die();
 }
 $sql = "UPDATE requesttable set reqorder = $targetorder WHERE reqorder = $tmpid ";
 $ret = $db->query($sql);
 if (! $ret ) {
	print("$tmpid から $targetorder への移動にしっぱいしました。<br>");
	$db->commit();
	return false;
	die();
 }
 $db->commit();
 }else{
 $sql = "UPDATE requesttable set reqorder = $nextorder WHERE id = $id ";
 $db->beginTransaction();
 $stmt = $db->prepare($sql);
 $ret = $db->query($sql);
 if (! $ret ) {
	print("${id} の  $nextorder への移動にしっぱいしました。<br>");
	$db->commit();
	return false;
	die();
 }
 $db->commit();
 }
 } 
}

/**
 * 未再生の直後まで移動
 * @param integer $id
 * @param db $db
 */
function warikomi($id, $db)
{
    global $tmpid;
    $ret = true;
    while($ret){
        // 対象のreqorderを取得
        $sql = "SELECT * FROM requesttable where id = $id ";
        $select = $db->query($sql);
        $row = $select->fetch(PDO::FETCH_ASSOC);
        $targetorder = $row['reqorder'];
        $select->closeCursor();
        
        // 自分より優先が早いリクエストを2つ取得する
        $sql = "SELECT * FROM requesttable where reqorder < $targetorder ORDER BY reqorder DESC ";
        $select = $db->query($sql);
        while($ret){
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if($row === FALSE){
                //現在自分が最優先
                // print 'DEBUG : 現在自分が最優先'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if( $row['nowplaying'] === '再生中'){
                //ちょうど次の番
                // print 'DEBUG : ちょうど次回再生[再生中]'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if($row['nowplaying'] === '未再生' ){
                //未再生を発見
                // print 'DEBUG : 1つ目の未再生を見つけた'.$row['reqorder'].'<br>';
                // print 'DEBUG : call dbdown from warikomi'.$row['reqorder'].'<br>';
                dbdown($id, $db);  // 1つずらす
                $ret = 'continue';
                break;
            }
        }
        if($ret === 'continue') {
            $ret = true;
            continue;
        }
        if($ret === false) break;
        
        //2つ目を探す
        while($ret){
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if($row === FALSE){
                //ちょうど次の番
                // print 'DEBUG : ちょうど次回再生'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if($row['nowplaying'] === '未再生' || $row['nowplaying'] === '再生中'){
                // 2つ未再生があるので移動する
                $select->closeCursor();
                // print 'DEBUG : call dbdown from warikomi'.$row['reqorder'].'<br>';
                dbdown($id, $db);  // 1つずらす
                break;
            }
        }
        
        
        
    }
    
}


if( !empty($_POST['resettsatus']) ){
     $sql = "UPDATE requesttable set nowplaying = \"未再生\" ";
     $db->beginTransaction();
     $ret = $db->query($sql);
     $db->commit();

}else{
    $l_id=$_REQUEST['id'];
    $l_action='delete';
    if( !empty($_REQUEST['up']) )
     {$l_action = 'up';}
    if( !empty($_REQUEST['down']) )
     {$l_action = 'down';}
    if( !empty($_REQUEST['warikomi']) )
     {$l_action = 'warikomi';}

    if ( $l_action === 'up' )
    {
        dbup($l_id,$db);
    }elseif ( $l_action === 'down' )
    {
        dbdown($l_id,$db);
    }elseif ( $l_action === 'warikomi' )
    {
        warikomi($l_id,$db);
    }else {

        $sql = "DELETE FROM requesttable where id = $l_id";
        $ret = $db->query($sql);
        if (! $ret ) {
        	print("${l_id} を削除に失敗しました。<br>");
        	die();
        }

        print("${l_id} を削除しました。<br>");
    }
}
print("1秒後に登録ページに移動します<br>");
if(!empty($DEBUG)){
print("現在の登録状況<br>");
try{
    $sql = "SELECT * FROM requesttable ORDER BY id DESC";
    $select = $db->query($sql);

    while($row = $select->fetch(PDO::FETCH_ASSOC)){
	    echo implode("|", $row) . "\n<br>";
    }


    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    } 
    $db = null;
}
?>
</body>
</html>


