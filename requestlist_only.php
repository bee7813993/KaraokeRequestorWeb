<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();
if(array_key_exists("showid", $_REQUEST)) {
    $showid = $_REQUEST["showid"];
}
?>

<!doctype html>
<html lang="ja">
<head>
<?php 
print_meta_header();

$showid='none';
if(array_key_exists("username", $_REQUEST)) {
    $username = $_REQUEST["username"];
    setcookie("YkariUsername", $username, time() + 5184000 );
}
?>
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

<title>
<?php 
//print($config_ini['roomurl']);
if(!empty($config_ini['roomurl']))
{
$roomnames=array_keys($config_ini['roomurl']);
print($roomnames[0])."：";
}?>リクエスト一覧</title>
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script src="js/bootstrap.min.js"></script>

<script type="text/javascript">
<!--
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})

<?php
$adddnum="";
if(array_key_exists( "requestlist_num", $config_ini) ) {
    if( $config_ini["requestlist_num"] !== 0 ){
        $adddnum = '&num='.$config_ini["requestlist_num"];
    }
}
?>

var showfirst = 1;
$(function(requestTable_t) { $("#request_table").dataTable({
  "processing": true,
  "serverSide": true,
     "ajax": {
         "url": "requestlist_table_json.php",
         "type": "POST",
         "dataType": 'json',
         "dataSrc": "data",
         "data": {},
<?php
$currentplay = countafterplayingitem();
$displayStart = 0;
if($currentplay !== 0 ){
    $diffnoplayitem = $currentplay - $config_ini["requestlist_num"];
    if($diffnoplayitem > 0 ){
        $displayStart = $diffnoplayitem;
    }
}


if($showid != 'none' ) {
print '             "complete" : function(settings) {'."\n";
             //alert( 'DataTables has redrawn the table' );
print '             if( showfirst ){';
print '               var element = document.getElementById( "id_'.$showid.'" ) ;'."\n";
print '               var rect = element.getBoundingClientRect() ;'."\n";
print '               var positionX = rect.left + window.pageXOffset ;	// 要素のX座標'."\n";
print '               var positionY = rect.top + window.pageYOffset ;	// 要素のY座標'."\n";
print '               window.scrollTo( positionX, positionY ) ;'."\n";
print '               showfirst = 0';
print '             }';
print '         },'."\n";
}
?>
     },
     "bPaginate" : true,
     "lengthMenu": [[<?php echo $config_ini["requestlist_num"];?>, -1], [<?php echo $config_ini["requestlist_num"];?>, "ALL"]],
     "displayStart": <?php echo $displayStart;?>,
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
$reload_interval = 20*1000;
if(array_key_exists("reloadtime",$config_ini) ){
    $reload_interval = $config_ini["reloadtime"] * 1000;
}
?>
     ],
     "order" : [[0, 'desc']],
     bDeferRender: false,
      "autoWidth": false,
      "searching": false,
     }); } );

         
    //タイマーをセット
    function tm(){
        tm = setInterval( function() {
            if($("[name=autoreload]").prop("checked")){
                var table = $('#request_table').DataTable();
                table.ajax.reload(null,false);
            }
            if($("[name=autoplayingsong]").prop("checked")){
                location.href = "#nowplayinghere";
            }

        },<?php echo $reload_interval; ?>);
    }

// イベントキーで画面更新
var EventSource = window.EventSource || window.MozEventSource;
function event_initial(){
    var reloadmethod = <?php 
      if( configbool("requestlistactivereload",true)){
         echo "true" ;
         } else {
         echo "false" ;
         } ?>
    
    if (reloadmethod){

      if (!EventSource){
          // alert("EventSourceが利用できません。");
          return;
      }
      var source = new EventSource('requestlist_event.php?kind=requestlist');
      var lastkey = 0;
      source.onmessage = function(event){
          if (event.data == "Bye"){
              event.target.close();
              // alert('終了しました。');
          }
          nowkey = event.data;
          if( nowkey ) {
            if( nowkey == "None" ) {
            }
            else {
                if(lastkey != nowkey){
                    if($("[name=autoreload]").prop("checked")){
                        var table = $('#request_table').DataTable();
                        table.ajax.reload(null,false);
                    }
                    if($("[name=autoplayingsong]").prop("checked")){
                            location.href = "#nowplayinghere";
                    }
                    lastkey = nowkey;
                }
            }
          }
      };
    }
    else {
      tm();
    }
}    

function reloadtable () {
    var table = $('#request_table').DataTable();
    table.ajax.reload(null,false);
}
//-->
</script>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body <?php if($reload_interval != 0 ) print 'onLoad="event_initial()"'; ?> >
<div class="container">
<?php
shownavigatioinbar();
?>
<?php
showmode();
?>
<?php
// トップページメッセージ表示
if(array_key_exists("noticeof_listpage",$config_ini)) {
    if(!empty($config_ini["noticeof_listpage"])){
        print '<div class="well">';
        print str_replace('#yukarihost#',$_SERVER["HTTP_HOST"],urldecode($config_ini["noticeof_listpage"]));
        print '</div>';
    }
}

if($reload_interval != 0){
print <<<EOT
<div class="checkbox">
 <label class="checkbox-inline"  data-toggle="tooltip" data-placement="top" title="コピペとかする時はチェックを外してください" >
 <input type="checkbox" name="autoreload" id="autoreload" value="1" checked /> 自動リロード 
 </label>
 <label class="checkbox-inline">
 <input type="checkbox" name="autoplayingsong" id="autoplayingsong" value="1" /> 自動再生中移動
 </label>
</div>
EOT;
}
?>
<hr />

<table id="request_table" class="cell-border">
<caption> <h4>現在の登録状況 <button type="submit" value="" class="topbtn btn btn-default btn-xs"  onclick=reloadtable() >更新</button></h4></caption>
<thead>
<tr>
<th>No.</th>
<th>ファイル名</th>
<th>登録者</th>
<th >
  <lavel data-toggle="tooltip" data-placement="top" title="コメント欄を押すとレスを付けたりできます">コメント </lavel>
</th>
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

<form method="get" action="simplelistexport_utf8.php">
<input type="submit" class="btn btn-primary" value="リクエストリスト(CSV)のダウンロード" class=" btn btn-default " />
&nbsp;
<a href="simplelist.php" class="btn btn-primary" > シンプルリクエストリスト表示(コピペ・公開用) </a>
</form>
</div>
</body>
</html>


