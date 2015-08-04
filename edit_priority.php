<?php
require_once 'commonfunc.php';
require_once 'prioritydb_func.php';

?>

<?php
$wordkind = 'none';
$priorityword = 'none';
$prioritynum = 'none';


if(array_key_exists("action", $_REQUEST)) {
    $action = $_REQUEST["action"];
    if( $action === 'delete'){
        if(array_key_exists("id", $_REQUEST)) {
            prioritydb_delete($priority_db,$_REQUEST["id"]);
        }else{
            print 'no id value';
        }
    }
}

if(array_key_exists("kind", $_REQUEST)) {
    $wordkind = $_REQUEST["kind"];
}
if(array_key_exists("priorityword", $_REQUEST)) {
    $priorityword = $_REQUEST["priorityword"];
}
if(array_key_exists("prioritynum", $_REQUEST)) {
    $prioritynum = $_REQUEST["prioritynum"];
}

if( $wordkind != 'none' || $priorityword != 'none' || $prioritynum != 'none' ){
   // add priority DB
   prioritydb_add($priority_db, $wordkind, $priorityword, $prioritynum);
   
}

?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header();?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">

<title>動画優先度設定</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>

<script type="text/javascript">
$(function(priority_list) { $("#priority_list").dataTable({
     "ajax": {
         "url": "prioritylist_json.php",
         "dataType": 'json',
         "dataSrc": "",
         },
         "columns" : [
             { "data": "id", "className":"no"},
             { "data": "kind", "className":"kind"},
             { "data": "prioritynum", "className":"prioritynum"},
             { "data": "priorityword", "className":"priorityword"},
             { "data": "action", "className":"action"},
             ],
     "bPaginate" : false,
     "order" : [[0, 'asc']],
     bDeferRender: true,
      "autoWidth": false,
     });
} );
</script>


</head>
<body>
<?php 
//現在のリスト表示
//var_dump(prioritydb_get($priority_db));
?>
<table id="priority_list" class="cell-border">
<thead>
<tr>
<th>No.</th>
<th>種別</th>
<th>優先度</th>
<th>キーワード</th>
<th>アクション</th>
</tr>
</thead>
<tbody>
</tbody>
</table>


<li>項目追加</li>
<form method="get" action="edit_priority.php">

<input type="radio" name="kind" value="dir" checked="checked" /> ディレクトリ
<input type="radio" name="kind" value="file" /> ファイル
キーワード :
<input type="text"  name="priorityword" >
優先度　：
<input type="text"  name="prioritynum" >
<input type="submit" value="登録">
</form>
<p>
<a href="init.php" >設定画面に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
</p>
</body>
</html>
