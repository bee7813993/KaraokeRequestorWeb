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


// 移動処理 (dbup/dbdown/warikomi) は /api/request_move.php と共用するため
// function_requestops.php へ切り出した
require_once 'function_requestops.php';


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
        normalize_reqorder($db);
    }elseif ( $l_action === 'down' )
    {
        dbdown($l_id,$db);
        normalize_reqorder($db);
    }elseif ( $l_action === 'warikomi' )
    {
        warikomi($l_id,$db);
        normalize_reqorder($db);
    }else {

        $stmt = $db->prepare("DELETE FROM requesttable WHERE id = :id");
        $stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (! $ret ) {
        	print("{$l_id} を削除に失敗しました。<br>");
        	die();
        }

        print("{$l_id} を削除しました。<br>");
        normalize_reqorder($db);
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


