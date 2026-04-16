<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
$db = null;
require_once 'commonfunc.php';
print_meta_header();
?>

<title>項目修正</title>
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
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<div class="container">
<?php

shownavigatioinbar();

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



print("現在の登録状況<br>");
try{
    $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id ORDER BY id DESC");
    $stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
    $stmt->execute();
    $allrequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (count($allrequest) === 0) {
        print("id={$l_id} のレコードが見つかりません。<br>");
    } else {
        echo "<form action=\"update.php\" >";

        foreach($allrequest[0] as $key => $value ){

            $esc_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            print '<div class="form-group">';
            if($key === 'nowplaying' ) {
                print "<label>{$esc_key}</label>";
                echo '<select  name="nowplaying" id="nowplaying" >';
                $v='未再生';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                $v='再生中';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                $v='再生開始中';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                $v='停止中';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                $v='再生済';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                $v='再生済？';
                echo '<option value='.$v.' '.selectedcheck($v,$value).' >'. $v .'</option>';
                echo '</select>';
            }else {
                $esc_value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                print "<label>{$esc_key}</label>";
                print '<input type="text" name="'.$esc_key.'" class="form-control" value="'.$esc_value.'" />';
            }
            print '</div>';

        }
        print "<input type=\"submit\" name=\"update\" value=\"変更\"/>";
        echo '</form>';
    }


    }catch(PDOException $e) {
		printf("Error: %s\n", $e->getMessage());
		die();
    }
    $db = null;


?>

</body>
</html>
