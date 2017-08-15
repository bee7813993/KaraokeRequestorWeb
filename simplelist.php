<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>再生曲リスト</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.min.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
<?php
include 'kara_config.php';
$usesimplelist = false;
if(array_key_exists("usesimplelist",$config_ini)){
    if($config_ini["usesimplelist"]==1 ){
        $usesimplelist=true;
    }
}
if( $usesimplelist == false ){
    print "<p>リスト表示機能が無効になっています</p>";
    print "</body>";
    print "</html>";
    die();
}
?>
  <div class="container">
  <table class="table table-hover table-striped">
  <thead  class="thead-inverse" >
    <tr>
      <th>順番</th>
      <th>曲名（ファイル名）</th>
      <th>歌った人</th>
      <th>コメント</th>
    </tr>
  </thead>
    <tbody>
<?php
//die();

if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}
date_default_timezone_set('Asia/Tokyo');

$sql = "SELECT * FROM requesttable WHERE nowplaying != '未再生' ORDER BY reqorder ASC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

  $num = 1;
  $csvarray = array();
  
  /* print csv header */
  $csvarray[] = array( "順番" , "曲名（ファイル名）" ,  "歌った人" ,  "コメント" );
  
  foreach($allrequest as $row){
    print '<tr>';
    print ' <td>';
    print   $num;
    print ' </td>';
    print ' <td>';
    print   $row["songfile"];
if($row['keychange'] > 0){
    print '<br><div style="text-align: right;;font-weight: normal;"> キー変更：+'.$row['keychange'].'</div>';
}else if($row['keychange'] < 0){
    print '<br><div style="text-align: right;;font-weight: normal;"> キー変更： '.$row['keychange'].'</div>';
}
    print ' </td>';
    print ' <td>';
    print   $row["singer"];
    print ' </td>';
    print ' <td>';
    print   $row["comment"];
    print ' </td>';
    print '</tr>';
    //$csvarray[] = array( $num, $row["songfile"] ,  $row["singer"] ,  $row["comment"] );
    $num++;
  }

?>
    </tbody>
    </table>
  </div>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="js/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
