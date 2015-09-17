<?php
require_once 'commonfunc.php';

?>

<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">


    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<title>リクエスト一覧</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script src="js/bootstrap.min.js"></script>

<script type="text/javascript">
$(function(requestTable) { $("#request_table").dataTable({
     "ajax": {
         "url": "requestlist_table_json.php",
         "dataType": 'json',
         "dataSrc": "",
     },
     "columns" : [
          { "data": "no", "className":"no"},
          { "data": "filename",className:"filename"},
          { "data": "singer",className:"singer"},
          { "data": "comment",className:"comment"},
          { "data": "method",className:"kind"},
          { "data": "playstatus",className:"nowplaying"},
          { "data": "action",className:"action"},
<?php
if($user === "admin"){
          print '{ "data": "change", className:"change" },';
}
?>
     ],
     "bPaginate" : false,
     "order" : [[0, 'desc']],
     bDeferRender: true,
      "autoWidth": false,
     });
} );
</script>
</head>
<body>
<?php
shownavigatioinbar();
?>
<?php
showmode();
?>

<table id="request_table" class="cell-border">
<caption> <h4>現在の登録状況 <button type="submit" value="" class="topbtn btn btn-default btn-xs"  onclick=location.reload() >更新</button></h4></caption>
<thead>
<tr>
<th>No.</th>
<th>ファイル名</th>
<th>登録者</th>
<th>コメント</th>
<th>再生方法</th>
<?php
     if($playmode == 1){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 2){
     print "<th>再生状況 </th>\n";
     }elseif ($playmode == 4){
     print "<th>再生回数 </th>\n";
     }else{
     print "<th>順番 </th>\n";
     }
?>
<th>アクション</th>
<?php
if($user === "admin"){
          print '<th>変更</th>';
}
?>

</tr>
</thead>
<tbody>
</tbody>
</table>

<script type="text/javascript" charset="utf8" src="js/requsetlist_ctrl.js"></script>
<hr>
<form method="get" action="init.php">
<input type="submit" value="設定" class=" btn btn-default " />
</form>
<a href="toolinfo.php" > 接続情報表示 </a>

</body>
</html>


