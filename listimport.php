<?php
setlocale(LC_ALL, 'ja_JP.UTF-8');

include 'kara_config.php';

if(array_key_exists("importtype", $_REQUEST)) {
    $l_importtype = $_REQUEST["importtype"];
}else {
    $l_importtype = 'new';
}

$tmp = fopen($_FILES['dbcsv']['tmp_name'], "r");
while ($csv[] = fgetcsv($tmp, "4096")) {}
// 配列 $csv の文字コードをSJIS-winからUTF-8に変換
mb_convert_variables("UTF-8", "ASCII,JIS,UTF-8,CP51932,SJIS-win", $csv);


if(empty($dbname)){
  $dbname = 'data';
}

if( strcmp($l_importtype,'add' ) === 0 ){

    try {
        $sql = "INSERT INTO requesttable (songfile, singer, comment, kind, reqorder, fullpath, nowplaying, status, clientip, clientua, playtimes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
    } catch (PDOException $e) {
    	echo 'Connection failed: ' . $e->getMessage();
    }
    if ($stmt === false ){
    	print("add INSERT prepare 失敗しました。<br>");
    	die();
    }

}else {

    $sql = "DELETE FROM requesttable";
    $retval = $db->exec($sql);
    if (! $retval ) {
            echo "\nPDO::errorInfo():\n";
            print_r($db->errorInfo());
    }

    try {
        $sql = "INSERT INTO requesttable  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
    } catch (PDOException $e) {
    	echo 'Connection failed: ' . $e->getMessage();
    }
    if ($stmt === false ){
    	print("new INSERT prepare 失敗しました。<br>");
    	die();
    }
}

    var_dump($csv);echo "<br>\n";
    foreach($csv as $row){
        if($row === array(null)) {
            continue;
        }
        if($row === false) {
            continue;
        }
        
        if( strcmp($l_importtype,'add' ) === 0 ){
            unset($row[0]);
            $row = array_values($row);
            var_dump($row);echo "<br>\n";
        }

        // var_dump($row);echo "<br>\n";
        $ret = $stmt->execute($row);
        if (! $ret ) {
            echo "\nPDO::errorInfo():\n";
	        print_r($db->errorInfo());
	        print("insert を追加にしっぱいしました。 : $row[0]\n");
	        die();
        }
    }

?>
<!doctype html>
<html lang="ja">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">
  

  <title>DBインポート</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<p> dbインポート完了 </p>
<a href="request.php" >トップに戻る </a>
</body>
</html>
