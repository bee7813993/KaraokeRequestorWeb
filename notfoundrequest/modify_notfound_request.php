<?php
$db = null;
require_once '../commonfunc.php';
require_once 'notfound_commonfunc.php';
init_notfounddb($db,"notfoundrequest.db");
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
print_meta_header();
?>

  <title>見つからなかった回答中</title>
  <link type="text/css" rel="stylesheet" href="../css/style.css" />
</head>
<body>

<p>
<a href="notfoundrequest.php" > 見つからなかったリストに戻る </a>
</p>

<?php

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}




print("現在の登録状況<br>");
try{
    $sql = "SELECT * FROM notfoundtable WHERE id = $l_id ORDER BY id DESC";
    $select = $db->query($sql);

    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        echo "<hr />";
        echo "<form action=\"update_notfound_request.php\" >";
        echo '<table class="modifytable">';
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
        echo 'request内容';
        echo '</td>';
        echo '<td>';
        print '<textarea name="requesttext" id="requesttext" rows="4" wrap="soft" style="width:100%" >';
        print $row['requesttext'];
        print '</textarea>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo '状況';
        echo '</td>';
        echo '<td>';
        echo '<select  name="status" id="status" >';
        $v='未確認';
        echo '<option value=0 '. (($row['status'] == 0) ? 'selected' : ' ') .' >'. $v .'</option>';
        $v='発見！リクエスト可能';
        echo '<option value=1 '. (($row['status'] == 1) ? 'selected' : ' ') .' >'. $v .'</option>';
        $v='今回は無理';
        echo '<option value=2 '. (($row['status'] == 2) ? 'selected' : ' ') .' >'. $v .'</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo 'コメント';
        echo '</td>';
        echo '<td>';
        print '<textarea name="reply" id="reply" rows="4" wrap="soft" style="width:100%" >';
        print $row['reply'];
        print '</textarea>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>';
        echo '検索ワード';
        echo '</td>';
        echo '<td>';
        print '<textarea name="searchword" id="searchword" rows="4" wrap="soft" style="width:100%" >';
        print $row['searchword'];
        print '</textarea>';
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
<a href="../requestlist_only.php" > リクエストページに戻る <a><br>

</body>
</html>