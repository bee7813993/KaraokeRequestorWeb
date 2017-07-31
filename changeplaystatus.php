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
if(array_key_exists("nowplaying", $_REQUEST)) {
    $l_nowplaying = $_REQUEST["nowplaying"];
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

if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
}

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}


$db = null;

require_once 'commonfunc.php';
shownavigatioinbar();


$sql = "UPDATE requesttable set nowplaying = \"$l_nowplaying\" WHERE id = $l_id ";
print $sql;
 $db->beginTransaction();
 $stmt = $db->prepare($sql);
 $ret = $db->query($sql);
 $db->commit();
 if (! $ret ) {
	print("$l_nowplaying への変更に失敗しました。<br>");
	print("順番入れ替えと競合した可能性があるのでもう一度試してください<br>");
	die();
 }

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


