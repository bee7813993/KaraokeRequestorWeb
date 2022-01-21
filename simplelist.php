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
require_once('commonfunc.php');
require_once('function_search_listerdb.php');

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

function getsonginfofromfilename($filename){
  global $config_ini;

  if(empty($filename)) return false;
  // ListerDBのファイルの設定があるかどうかのチェック
  $res = array_key_exists("listerDBPATH",$config_ini);
  if ($res === false ) {
     print( "config not found");
     return false;
  }
  $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
  // ListerDBのファイルのがあるかどうかのチェック
  if(!file_exists($lister_dbpath) ){
     print( "Listerdb file :". $lister_dbpath." not found");
     return false;
  }

  // DB初期化
  $lister = new ListerDB();
  $lister->listerdbfile = $lister_dbpath;
  $listerdb = $lister->initdb();
  if( !$listerdb ) {
       return false;
  }

  $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.$filename.'%');
  $sql = 'SELECT * FROM t_found '. $select_where.';';
  @$songdbdata = $lister->select($sql);
  if(!$songdbdata){
      $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.basename($filename).'%');
      $sql = 'SELECT * FROM t_found '. $select_where.';';
      @$songdbdata = $lister->select($sql);
      if(!$songdbdata){
//     print $sql;
         return false;
      }
}
return $songdbdata;
}

// 1つでもlisterDBに登録情報があるかどうかのチェック
function listerdbfoundcheck($alldata){
   foreach($alldata as $row){
     $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
     // if( $songdataarray_all === false ) return false;
     $songdataarray = $songdataarray_all[0];
     if(!empty($songdataarray["song_name"]) ) {
       return true;
     }
   }
   return false;
}
?>

<?php
date_default_timezone_set('Asia/Tokyo');
if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}

        $sql = "SELECT * FROM requesttable WHERE nowplaying != '未再生' ORDER BY reqorder ASC";
        $select = $db->query($sql);
        $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();


$listerdbenabled = false;
if(array_key_exists("listerDBPATH",$config_ini) ) {
    $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
    if(file_exists($lister_dbpath) ){
        $listerdbenabled = true;
    }
}

if($listerdbenabled && listerdbfoundcheck($allrequest) ){
// りすたーDBに登録された情報が1つでもある

  print<<<EOL
  <div class="container">
  <table class="table table-hover table-striped">
  <thead  class="thead-inverse" >
    <tr>
      <th>順番</th>
      <th>曲名（ファイル名）</th>
      <th>作品名</th>
      <th>歌手名</th>
      <th>歌った人</th>
      <th>コメント</th>
    </tr>
  </thead>
    <tbody>
EOL;

  $num = 1;
  $csvarray = array();
  
  /* print csv header */
  $csvarray[] = array( "順番" , "曲名（ファイル名）" , "作品名" ,"歌手名" , "歌った人" ,  "コメント" );
  
  foreach($allrequest as $row){
    $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
    if(isset($songdataarray)) $songdataarray = $songdataarray_all[0];
    print '<tr>';
    print ' <td>';
    print   $num;
    print ' </td>';
    print ' <td>';
    if(!empty($songdataarray["song_name"] ) ){
    print $songdataarray["song_name"] ;
    }else {
    print   $row["songfile"];
    }
    if(!empty($songdataarray["found_comment"] ) ){
    $showcomment=preg_replace('#//\.\+\$#', "",$songdataarray["found_comment"]);
    print '【'.$showcomment.'】' ;
    }    
if($row['keychange'] > 0){
    print '<br><div style="text-align: right;;font-weight: normal;"> キー変更：+'.$row['keychange'].'</div>';
}else if($row['keychange'] < 0){
    print '<br><div style="text-align: right;;font-weight: normal;"> キー変更： '.$row['keychange'].'</div>';
}
    print ' </td>';
    print ' <td>';
    if(!empty($songdataarray["program_name"] ) ){
      if( $songdataarray["program_name"] == "その他" ) {
        print '-';
      } else {
        print $songdataarray["program_name"] ;
      }
    }else {
    }
    if(!empty($songdataarray["song_op_ed"] ) ){
    print '&nbsp;'.$songdataarray["song_op_ed"] ;
    }    print ' </td>';
    print ' <td>';
    if(!empty($songdataarray["song_artist"] ) ){
    print $songdataarray["song_artist"] ;
    }else {
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
}else {

  print<<<EOL
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
EOL;
  $num = 1;

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
