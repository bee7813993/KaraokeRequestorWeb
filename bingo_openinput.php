<?php
// id は整数のみ受け付ける
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
if ($id === false || $id === null) {
    $id = 99999;
}

require_once 'commonfunc.php';
require_once 'binngo_func.php';
mb_language("Japanese");

global $db;
$stmt = $db->prepare('SELECT * FROM requesttable WHERE id = :id ORDER BY reqorder DESC');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$allrequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

?>

<!doctype html>
<html lang="ja">
<head>
<?php 
print_meta_header();
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

<title>ビンゴ結果入力</title>

<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" charset="utf8" src="js/bingoctrl.js"></script>
</head>
<body>
<?php
shownavigatioinbar();
?>
<?php
$bingoinfo = new SongBingo();
$bingoinfo->initbingodb('songbingo.db'); 
$list = $bingoinfo->wordkey_readdata();

print '<p>';
if (!empty($allrequest)) {
    print '「'.htmlspecialchars((string)$allrequest[0]['songfile'], ENT_QUOTES, 'UTF-8').'」のビンゴ番号入力';
} else {
    print '(該当するリクエストが見つかりません)';
}
print '</p>';

?>
<div class="container panel panel-default">
    <div class="panel-heading">
    ビンゴ解放条件リスト
    </div>
    <div class="panel-body">
<?php
//var_dump($list);
print '<table  class="table">';
print '<thead>';
print '<th> 解放条件 </th>';
print '<th> 番号 </th>';
print '<th> 解放 </th>';
print '</thead>';
print '<tbody>';
foreach( $list as $oneword ){
    //現在のIDでオープンされた項目かどうかのチェック
    $thisidflg = false;
    foreach ($oneword[3] as $oneid ){
        if($id == $oneid ) $thisidflg = true;
    }
    
    $esc_requirement = htmlspecialchars((string)$oneword[0], ENT_QUOTES, 'UTF-8');
    print '<tr ';
    if($thisidflg) print 'class="success" ' ;
    print '> ';
    print '  <td> ';
    print $esc_requirement;
     if($thisidflg) print '&nbsp;（現在の曲で開放） ' ;
    print '  </td>';
    print '  <td> ';
    if($oneword[2] == 1){
      foreach( $oneword[1] as $onenum ){
        print htmlspecialchars((string)$onenum, ENT_QUOTES, 'UTF-8').' ';
      }
    }else{
        print "未開放";
    }
    print '  </td>';
    print '  <td> ';
    print '  <form action="bingo_opennum.php" method="POST" class="bingoinput" >';
    print '    <input type="hidden" name="requirement" value="'.$esc_requirement.'" class="form-control">';
    $togglevalue = $oneword[2]==0 ? 1 : 0;
    $toggleword = $oneword[2]==0 ? 'open' : 'close';
    print '    <input type="hidden" name="toopened" value="'.$togglevalue.'" class="form-control">';
    print '    <input type="hidden" name="id" value="'.(int)$id.'" class="form-control">';
    print '    <button type="submit">'.$toggleword.'</button>';
    print '  </form>';
    print '  </td>';
    print '</tr> ';
}
print '</tbody>';
print '</table>';

?>
    </div>
</div>

</body>
</html>
