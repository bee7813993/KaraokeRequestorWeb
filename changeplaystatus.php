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
// 許可する再生状況の値 (ホワイトリスト)
$allowed_nowplaying = array('未再生','再生中','停止中','再生済','再生済？','再生開始待ち','変更中');

$l_nowplaying = null;
if(array_key_exists("nowplaying", $_REQUEST)) {
    // 再生状況、数値対応
    switch($_REQUEST["nowplaying"]) {
        case 1:
            $l_nowplaying = '未再生';
            break;
        case 2:
            $l_nowplaying = '再生中';
            break;
        case 3:
            $l_nowplaying = '停止中';
            break;
        case 4:
            $l_nowplaying = '再生済';
            break;
        case 5:
            $l_nowplaying = '再生済？';
            break;
        case 6:
            $l_nowplaying = '再生開始待ち';
            break;
        case 7:
            $l_nowplaying = '変更中';
            break;
        default:
            $l_nowplaying = $_REQUEST["nowplaying"];
    }
}

// ホワイトリストチェック
if ($l_nowplaying === null || !in_array($l_nowplaying, $allowed_nowplaying, true)) {
    http_response_code(400);
    print("不正な再生状況です。<br>");
    die();
}

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


$db = null;

require_once 'commonfunc.php';
shownavigatioinbar();


$db->beginTransaction();
$stmt = $db->prepare("UPDATE requesttable SET nowplaying = :nowplaying WHERE id = :id");
$stmt->bindValue(':nowplaying', $l_nowplaying, PDO::PARAM_STR);
$stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
$ret = $stmt->execute();
if (! $ret ) {
	$db->rollBack();
	print(htmlspecialchars($l_nowplaying, ENT_QUOTES, 'UTF-8')." への変更に失敗しました。<br>");
	print("順番入れ替えと競合した可能性があるのでもう一度試してください<br>");
	die();
}
$db->commit();

print("1秒後に登録ページに移動します<br>");

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
?>
</body>
</html>


