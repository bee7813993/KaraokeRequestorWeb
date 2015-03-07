<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>項目修正</title>
</head>
<body>


<a href="request.php" > リクエストページに戻る <a><br>

<?php
$db = null;

include 'kara_config.php';

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
        echo '<table>';
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
        echo '<input type="text" name="nowplaying" id="nowplaying" value="';
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
        echo '<input type="text" name="status" id="status" value="';
        echo $row['clientip'];
        echo '" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'UserAgent';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="status" id="status" value="';
        echo $row['clientua'];
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