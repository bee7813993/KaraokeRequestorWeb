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
</head>
<body>

<?php

shownavigatioinbar();

if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
}

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}



print("現在の登録状況<br>");
try{
    $sql = "SELECT * FROM requesttable WHERE id = $l_id ORDER BY id DESC";
    $select = $db->query($sql);

    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        echo "<hr />";
        echo "<form action=\"update.php\" >";
        echo '<table  class="modifytable">';
        echo '<tr>';
        echo '<td>';
        echo 'id';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="id" id="id" value="';
        echo $row['id'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'songfile';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="songfile" id="songfile" maxlength="4096" value="';
        echo $row['songfile'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'singer';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="singer" id="singer" value="';
        echo $row['singer'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'comment';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="comment" id="comment" value="';
        echo $row['comment'];
        echo '" />';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>';
        echo 'kind';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="kind" id="kind" value="';
        echo $row['kind'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'fullpath';
        echo '</td>';
        echo '<td>';
        echo '<input type="test" name="fullpath" id="fullpath" maxlength="4096" value="';
        echo $row['fullpath'];
        echo '" />';
//        echo '<input type="file" name="fullpath_mod" id="fullpath" value="';
//        echo $row['fullpath'];
//        echo '" />';
        echo '</td>';
        echo '</tr>';
 
         echo '<tr>';
        echo '<td>';
        echo 'nowplaying';
        echo '</td>';
        echo '<td>';
        echo '<select  name="nowplaying" id="nowplaying" >';
        $v='未再生';
        echo '<option value='.$v.' '.selectedcheck($v,$row['nowplaying']).' >'. $v .'</option>';
        $v='再生済';
        echo '<option value='.$v.' '.selectedcheck($v,$row['nowplaying']).' >'. $v .'</option>';
        $v='再生中';
        echo '<option value='.$v.' '.selectedcheck($v,$row['nowplaying']).' >'. $v .'</option>';
        $v='再生済？';
        echo '<option value='.$v.' '.selectedcheck($v,$row['nowplaying']).' >'. $v .'</option>';
//        echo '<input type="text" name="nowplaying" id="nowplaying" value="';
        echo $row['nowplaying'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'reqorder';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="reqorder" id="reqorder" value="';
        echo $row['reqorder'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'status';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="status" id="status" value="';
        echo $row['status'];
        echo '" />';
        echo '</td>';
        echo '</tr>';
        

        echo '<tr>';
        echo '<td>';
        echo 'IP';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="clientip" id="clientip" value="';
        echo $row['clientip'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'UserAgent';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="clientua" id="clientua" value="';
        echo $row['clientua'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'playtimes';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="playtimes" id="playtimes" value="';
        echo $row['playtimes'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
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