<?php

if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
}

$db = null;

require_once 'notfound_commonfunc.php';

init_notfounddb($db,"notfoundrequest.db");


$sql = "SELECT * FROM notfoundtable ORDER BY id DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

?>

<!doctype html>
<html lang="ja">
<head>
  <title>見つからなかった曲報告TOP</title>
  <link type="text/css" rel="stylesheet" href="../css/style.css" />
</head>
<body>
<a href="../request.php" >トップに戻る </a>

<!--- 入力フォーム --->
<form method="GET" action="add_notfound_request.php">
<div CLASS="itemname">
<p>内容</p>
<textarea name="requesttext" id="requesttext" rows="4" wrap="soft" style="width:100%" placeholder="見つからなかった曲を教えてください。ニコニコ動画などのURLでもOK。オフ会中もしくは次回までに用意できるかもしれません">
<?php  if(isset($word)) print($word); ?>
</textarea>
</div>
<div CLASS="pushbtn">
<input type="submit" value="送信"/>
</div>
</form>
<?php
if(!count($allrequest) == 0 ){
    // <!--- 一覧 --->
    print "<table border=\"2\" id=\"notfoundtable\">\n";
    print '<caption> 見つからなかった曲報告リスト <button type="submit" value="" class="reloadbtn"  onclick=location.reload() >更新</button></caption>'."\n";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>リクエスト内容 </th>\n";
    print "<th>状況 </th>\n";
    print "<th>コメント </th>\n";
    print "<th>リンク </th>\n";
    print "<th>アクション </th>\n";
    print "</tr>\n";
    print "</thead>\n";
    print "<tbody>\n";

    foreach($allrequest as  $row) {
        print "<tr>\n";
        print "<th class=\"filename\">";
        print nl2br(htmlspecialchars($row['requesttext']));
        print "</th>\n";
        print "<td class=\"kind\">";
        if( $row['status'] == 0 ){
            print "未確認";
        }else if( $row['status'] == 1 ){
            print "発見！リクエスト可能";
        }else if( $row['status'] == 2 ){
            print "今回は無理";
        }else{
            print "未確認";
        }
        print "</td>\n";
        print "<td class=\"comment\">";
            print nl2br(htmlspecialchars($row['reply']));
        print "</td>\n";
        print "<td class=\"link\">";
            if(!empty($row['searchword'])){
            print '<a href="../search.php?searchword=';
            print urlencode($row['searchword']);
            print ' "> 検索結果へのリンク <a>';
            }
        print "</td>\n";
        print "<td class=\"action\">";
        print "<form method=\"GET\" action=\"modify_notfound_request.php\">";
        print "<input type=\"hidden\" name=\"id\" value=\"";
        print $row['id'];
        print "\" />";
        print "<input type=\"submit\" name=\"変更\"   value=\"変更\"/>";
        print "</form>\n";
        print "<form method=\"GET\" action=\"delete_notfound_request.php\">";
        print "<input type=\"hidden\" name=\"id\" value=\"";
        print $row['id'];
        print "\" />";
        print "<input type=\"submit\" name=\"削除\"   value=\"削除\"/>";
        print "</form>\n";
        print "</td>\n";
        print "</tr>\n";
    }
}
?>
</tbody>
</table>
<a href="../request.php" >トップに戻る </a>

</body>
</html>
