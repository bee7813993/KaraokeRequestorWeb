<?php
require_once 'commonfunc.php';
//include 'kara_config.php';




$failflg = 0;
$addcommentsuccessflg = 0;
$c_comment = "";
if(array_key_exists("songfile", $_REQUEST)) {
    $l_songfile = $_REQUEST["songfile"];
}

if(array_key_exists("id", $_REQUEST)) {
    $l_id = $_REQUEST["id"];
}

if(array_key_exists("addcomment", $_REQUEST)) {
    $l_addcomment = $_REQUEST["addcomment"];
}

if(array_key_exists("name", $_REQUEST)) {
    $l_name = $_REQUEST["name"];
}

if(empty($l_id)){
print "wrong id";
$failflg = 1;
die();
}else{
    $sql = "SELECT comment,singer FROM requesttable WHERE id = $l_id ORDER BY id DESC";
    $select = $db->query($sql);
    $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    
    if(count($allrequest) == 0){
    print "nodata";
    $failflg = 1;
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
                $sql_u = 'UPDATE requesttable set comment = \''. $newcomment . '\' WHERE id = '. $l_id;
                print "DEBUG:".$sql_u.'<br />';
                $ret = $db->query($sql_u);
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
                  
                  commentpost_v2($nm,$col,$size,$msg,$commenturl);
            //      print("コメントポスト実行");
            }else{
            //      print("コメントポスト実行されず $commenturl,$playingid,$l_id ");
            }
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
<input type="hidden" name="id" id="id" value="
<?php
print $l_id;
?>
" />
<textarea name="comment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php
print $c_comment;
?>
</textarea>
<input type="submit" name="update" value="変更"/>
</form>
</body>
</html>
