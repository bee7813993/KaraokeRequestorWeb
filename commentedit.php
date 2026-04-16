<?php
require_once 'commonfunc.php';
//include 'kara_config.php';




$addcommentsuccessflg = 0;
$c_comment = "";

// id は整数のみ受け付ける
$l_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($l_id === false || $l_id === null) {
    $l_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
if ($l_id === false || $l_id === null) {
    print "wrong id";
    die();
}

if(array_key_exists("addcomment", $_REQUEST)) {
    $l_addcomment = $_REQUEST["addcomment"];
}

if(array_key_exists("name", $_REQUEST)) {
    $l_name = $_REQUEST["name"];
}

$stmt = $db->prepare("SELECT comment,singer FROM requesttable WHERE id = :id ORDER BY id DESC");
$stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
$stmt->execute();
$allrequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if(count($allrequest) == 0){
print "nodata";
}else{
    $c_comment = $allrequest[0]['comment'];

    if(!empty($l_addcomment) ){
        $newcomment = $c_comment ."\n>> ".$l_addcomment;
        if(!empty($l_name) ){
            $newcomment = $newcomment." by ".$l_name;
        }else{
            $newcomment = $newcomment." by 名無しさん";
        }
        try{
            $stmt_u = $db->prepare("UPDATE requesttable SET comment = :comment WHERE id = :id");
            $stmt_u->bindValue(':comment', $newcomment, PDO::PARAM_STR);
            $stmt_u->bindValue(':id', $l_id, PDO::PARAM_INT);
            $ret = $stmt_u->execute();
            if (! $ret) {
                print("コメントの更新に失敗しました。<br>");
                die();
            }
        }catch(PDOException $e) {
    		printf("Error: %s\n", $e->getMessage());
    		die();
        }
        $addcommentsuccessflg = 1;
        // レスコメント時コメント表示
        $playingid = getcurrentid();
        if(isset($commenturl) && ($playingid == $l_id )){
              $nm=$allrequest[0]['singer'];
              $msg=$l_addcomment;
              $col = '808080';
              $size = 3;

              commentpost_v3($nm,$col,$size,$msg,$commenturl);
        //      print("コメントポスト実行");
        }else{
        //      print("コメントポスト実行されず $commenturl,$playingid,$l_id ");
        }
    }
}


?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
$db = null;

print_meta_header();
if($addcommentsuccessflg == 1){
  print '<META http-equiv="refresh" content="1; url=requestlist_only.php">';
}
?>
<link type="text/css" rel="stylesheet" href="css/style.css" />

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

<title>コメント編集</title>

</head>
<body>
<?php
shownavigatioinbar();
?>
<br>
コメント編集
<form action="update.php" >
<input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars($l_id, ENT_QUOTES, 'UTF-8'); ?>" />
<textarea name="comment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php
echo htmlspecialchars($c_comment, ENT_QUOTES, 'UTF-8');
?>
</textarea>
<input type="submit" name="update" value="変更"/>
</form>
</body>
</html>
