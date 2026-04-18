<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<META http-equiv="refresh" content="1; url=requestlist_top.php">
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
 * @param PDO $db
 */
function dbup($id, $db)
{
	global $tmpid;
// 対象のreqorderを取得

$stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row === false) {
    print("id={$id} のレコードが見つかりません。<br>");
    return;
}
$targetorder = $row['reqorder'];

 $nextorder = $targetorder + 1 ;


 $stmt = $db->prepare("SELECT * FROM requesttable WHERE reqorder = :nextorder");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->execute();
 $row = $stmt->fetch(PDO::FETCH_ASSOC);

 if(  $row !== false ){
 // 移動先のreqorder値の項目があったら
 $db->beginTransaction();
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :tmpid WHERE reqorder = :nextorder");
 $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("$nextorder から $tmpid への移動にしっぱいしました。<br>");
	die();
 }
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :nextorder WHERE id = :id");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("{$id} の $nextorder への移動にしっぱいしました。<br>");
	die();
 }
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :targetorder WHERE reqorder = :tmpid");
 $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
 $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("$tmpid から $targetorder への移動にしっぱいしました。<br>");
	die();
 }
 $db->commit();
 }else{
 $db->beginTransaction();
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :nextorder WHERE id = :id");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("{$id} から $nextorder への移動にしっぱいしました。<br>");
	die();
 }
 $db->commit();
 }
}

/**
 * 行を下に移動
 * @param integer $id
 * @param PDO $db
 */
function dbdown($id, $db)
{
	global $tmpid;


// 対象のreqorderを取得
$stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row === false) {
    print("id={$id} のレコードが見つかりません。<br>");
    return;
}
$targetorder = $row['reqorder'];

 $nextorder = $targetorder - 1 ;

 if ($targetorder <= 1 ){
    print("すでに一番下<br>");
 }else{


 $stmt = $db->prepare("SELECT * FROM requesttable WHERE reqorder = :nextorder");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->execute();
 $row = $stmt->fetch(PDO::FETCH_ASSOC);
// var_dump($row);
 if(  $row !== false ){
 // 移動先のreqorder値の項目があったら
 $db->beginTransaction();
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :tmpid WHERE reqorder = :nextorder");
 $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("$targetorder から $tmpid  への移動にしっぱいしました。<br>");
	return false;
 }
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :nextorder WHERE id = :id");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("{$id} の $nextorder への移動にしっぱいしました。<br>");
	return false;
 }
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :targetorder WHERE reqorder = :tmpid");
 $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
 $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("$tmpid から $targetorder への移動にしっぱいしました。<br>");
	return false;
 }
 $db->commit();
 }else{
 $db->beginTransaction();
 $stmt = $db->prepare("UPDATE requesttable SET reqorder = :nextorder WHERE id = :id");
 $stmt->bindValue(':nextorder', $nextorder, PDO::PARAM_INT);
 $stmt->bindValue(':id', $id, PDO::PARAM_INT);
 $ret = $stmt->execute();
 if (! $ret ) {
	$db->rollBack();
	print("{$id} の  $nextorder への移動にしっぱいしました。<br>");
	return false;
 }
 $db->commit();
 }
 }
}

/**
 * 未再生の直後まで移動
 * @param integer $id
 * @param PDO $db
 */
function warikomi($id, $db)
{
    global $tmpid;
    $ret = true;
    while($ret){
        // 対象のreqorderを取得
        $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            print("id={$id} のレコードが見つかりません。<br>");
            return;
        }
        $targetorder = $row['reqorder'];
        $stmt->closeCursor();

        // 自分より優先が早いリクエストを2つ取得する
        $select = $db->prepare("SELECT * FROM requesttable WHERE reqorder < :targetorder ORDER BY reqorder DESC");
        $select->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
        $select->execute();
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


if( !empty($_POST['resetstatus']) ){
     $db->beginTransaction();
     $ret = $db->exec("UPDATE requesttable SET nowplaying = '未再生'");
     $db->commit();

}else{
    // id は整数のみ受け付ける
    $l_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($l_id === false || $l_id === null) {
        $l_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    }
    if ($l_id === false || $l_id === null) {
        http_response_code(400);
        print("不正なIDです。<br>");
        die();
    }

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

        $stmt = $db->prepare("DELETE FROM requesttable WHERE id = :id");
        $stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (! $ret ) {
        	print("{$l_id} を削除に失敗しました。<br>");
        	die();
        }

        print("{$l_id} を削除しました。<br>");
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


