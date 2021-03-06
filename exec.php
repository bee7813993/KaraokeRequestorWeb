<?php
$db = null;

require_once 'commonfunc.php';
//var_dump($_REQUEST);
$l_filename=$_REQUEST['filename'];
$l_singer=$_REQUEST['singer'];
$l_freesinger=$_REQUEST['freesinger'];
$l_comment=$_REQUEST['comment'];
$l_kind=$_REQUEST['kind'];
$l_fullpath= "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $l_fullpath = $_REQUEST["fullpath"];
}
$l_secret = 0;
if(array_key_exists("secret", $_REQUEST)) {
    $l_secret = $_REQUEST["secret"];
}
$l_urlreq = 0;
if( mb_stristr($l_kind , "URL指定") !== FALSE ){
    $l_urlreq = 1;
}
$l_loop = 0;
if(array_key_exists("loop", $_REQUEST)) {
    $l_loop = $_REQUEST["loop"];
    $l_kind="カラオケ配信";
}

$l_clientip = $_SERVER['REMOTE_ADDR'];
if(array_key_exists("clientip", $_REQUEST)) {
    $l_clientip = $_REQUEST["clientip"];
}

$l_clientua = $_SERVER['HTTP_USER_AGENT'];
if(array_key_exists("clientua", $_REQUEST)) {
    $l_clientua = $_REQUEST["clientua"];
}

$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    if(!is_numeric($selectid)){
        $selectid = 'none';
    }
}

if(array_key_exists("urlreq", $_REQUEST)) {
    $l_urlreq = $_REQUEST["urlreq"];
}

$l_otherplayer = 0;
if(array_key_exists("otherplayer", $_REQUEST)) {
    $l_otherplayer = $_REQUEST["otherplayer"];
    if($l_kind === '動画' ){
        $l_kind = '動画_別プ';
    }
}

$l_keychange = 0;
if(array_key_exists("keychange", $_REQUEST)) {
    $l_keychange = $_REQUEST["keychange"];
    if(!is_numeric($l_keychange)){
        $l_keychange = 0;
    }
}

$l_track = 0;
if(array_key_exists("track", $_REQUEST)) {
    $l_track = $_REQUEST["track"];
    if(!is_numeric($l_track)){
        $l_track = 0;
    }
}

$l_pause = 0;
if(array_key_exists("pause", $_REQUEST)) {
    $l_pause = $_REQUEST["pause"];
    if(!is_numeric($l_pause)){
        $l_pause = 0;
    }
}


if(!empty($l_freesinger)){
$l_singer=$l_freesinger;
}

function getnewid($db){
    $sql = 'select count(*) from requesttable';
    $select = $db->query($sql);
    $count = $select->fetchColumn();
    $select->closeCursor();
    
    return $count + 1;
}

// 書式チェック
switch($l_kind) {
     case '動画':
     case 'カラオケ配信':
     case '小休止':
     case 'URL指定':
     case '動画_別プ':
         break;
     default:
         die();
}

if(mb_strlen($l_comment) > 512 ) die();
if(mb_strlen($l_filename) > 2048 ) die();
if(mb_strlen($l_singer) > 256 ) die();
if(mb_strlen($l_freesinger) > 256 ) die();


// print("${l_singer} さんの ${l_filename} を追加する予定。<br>");
?>

<?php
if(mb_stristr($l_kind , "URL指定") !== FALSE  || $l_urlreq == 1 ){
//  $l_fullpath = $l_filename;
  $displayfilename = $l_filename;
}else {
  $displayfilename = makesongnamefromfilename($l_filename) ;
}
if(is_numeric($selectid)){

/*** 既存登録の差し替え処理 ***/
// print($displayfilename);

try {
    $sql = "SELECT * FROM requesttable where id = '".$selectid."' ORDER BY id DESC";
    $select = $db->query($sql);
    $request = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}

if($request[0]['nowplaying'] === '再生中' || $request[0]['nowplaying'] === '再生開始待ち' ) {
   $new_nowplaying = '変更中';
}else{
   $new_nowplaying = $request[0]['nowplaying'];

}

try {
    $sql = "UPDATE requesttable set songfile=:fn, singer=:sing, comment=:comment, kind=:kind, fullpath=:fp, secret=:secret, loop=:loop, nowplaying=:nowplaying, keychange=:keychange, track=:track, pause=:pause where id = ".$selectid;
    $stmt = $db->prepare($sql);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}
	if ($stmt === false ){
		print("UPDATE　 prepare 失敗しました。<br>");
	}

$arg = array(
	':fn' =>  $displayfilename,
	':sing' => $l_singer,
	':comment' => $l_comment,
	':kind' => $l_kind,
	':fp' => $l_fullpath,
	':secret' => $l_secret,
	':loop' => $l_loop,
	':nowplaying' => $new_nowplaying,
	':keychange' => $l_keychange,
	':track' => $l_track,
	':pause' => $l_pause
	);
}else {
  try {
    $sql = "INSERT INTO requesttable (songfile, singer, comment, kind, fullpath, nowplaying, status, clientip, clientua, playtimes, secret, loop, keychange, track, pause) VALUES (:fn, :sing, :comment, :kind, :fp, :np, :status, :ip, :ua, 0 ,:secret,:loop, :keychange, :track, :pause)";
    $stmt = $db->prepare($sql);
  } catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
  }
	if ($stmt === false ){
		print("INSERT　 prepare 失敗しました。<br>");
	}

  $arg = array(
	':fn' =>  $displayfilename,
	':sing' => $l_singer,
	':comment' => $l_comment,
	':kind' => $l_kind,
	':fp' => $l_fullpath,
	':np' => "未再生",
	':status' => 'new',
	':ip' => $l_clientip  ,
	':ua' => $l_clientua ,
	':secret' => $l_secret,
	':loop' => $l_loop,
	':keychange' => $l_keychange,
	':track' => $l_track,
	':pause' => $l_pause
	);
}
$ret = $stmt->execute($arg);
if (! $ret ) {
	print("${l_filename} を追加にしっぱいしました。");
	die();
}

$sql = "SELECT * FROM requesttable where status = 'new' ORDER BY id DESC";
try {
if(!empty($DEBUG))
    print $sql.'<br />';
    $select = $db->query($sql);
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
    $newid=$row['id'];
    $sql_u = 'UPDATE requesttable set reqorder = '. $newid . ', status = \'OK\' WHERE id = '. $newid;
if(!empty($DEBUG))
    print $sql_u.'<br />';
    $ret = $db->query($sql_u);
    }

} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}
  if($config_ini["request_automove"] == 1){
    require_once('function_moveitem.php');
    $db->exec("BEGIN DEFERRED;");
    $list = new MoveItem;
    $turnlist = $list->getturnlist($db);
    $newreq = $list->get_new_reqorder($newid);
    $list->insertreqorder($newid,$newreq);
    $list->save_allrequest($db);
    $db->exec("COMMIT;");
  }
file_get_contents("http://localhost/updaterequestlist.php");

if(!empty($DEBUG)){
    print("${l_filename} を追加しました。<br>");
    print("1秒後に登録ページに移動します<br>");
}

//print "きぬ";
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
   $newidnotice = '{"newid": '.$newid.'}';
   print $newidnotice;
}else {
   print<<<EOT
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="refresh" content="1; url=requestlist_only.php" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DB登録中</title>
</head>
<body>
<pre >
EOT;
// var_dump( $_REQUEST);
   print<<<EOT
</pre>

<a href="requestlist_only.php" > リクエストページに戻る <a><br>
EOT;
    $sql = "SELECT * FROM requesttable ORDER BY id DESC";
    if(!empty($DEBUG)){
        print '現在の登録状況<br>';

        try{

            $select = $db->query($sql);

            while($row = $select->fetch(PDO::FETCH_ASSOC)){
	            echo implode("|", $row) . "\n<br>";
            }
            $db = null;

        }catch(PDOException $e) {
		    printf("Error: %s\n", $e->getMessage());
		    die();
        }
    }
    print<<<EOT
</body>
</html>
EOT;
}

?>
