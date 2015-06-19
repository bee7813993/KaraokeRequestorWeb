<?php

require_once 'commonfunc.php';

if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}

$configfile = 'config.ini';
$config_ini = array ();

if(file_exists($configfile)){
    $config_ini = parse_ini_file($configfile);
    //var_dump($config_ini);

}else{
    print "no $configfile";
}


$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$json = json_encode($allrequest,JSON_PRETTY_PRINT);

$requsetlisttable = array();
$reqcount = count($allrequest);

foreach($allrequest as $value ){
    $onerequset = array();
    $onerequset += array("No. " => $reqcount);
    $reqcount -= 1;

    $onerequset += array("ファイル名" => $value['songfile']);
    
    $onerequset += array("登録者" => $value['singer']);
    
    $comment_pf = <<<EOD
<div>\n %s </div>\n
<form method="GET" action="commentedit.php">\n
<input type="hidden" name="id" value="%s" />\n
<input type="submit" name="edit"   value="修正"/>\n
</form>\n
<form method="GET" action="commentedit.php">\n
<input type="text" name="addcomment" id="addcomment" value="" placeholder="レス(コメントへの)"/>\n
<input type="text" name="name" id="name" value="%s" "  placeholder="名前" />\n
<input type="hidden" name="id" value="%s" />\n
<input type="submit" name="add"   value="送信"/>\n
</form>
EOD;
    $comment = sprintf($comment_pf, $value['comment'], $value['id'],  $value['singer'], $value['id']);
    $onerequset += array("コメント" => $comment);

    $onerequset += array("再生方法" => $value['kind']);

$playstatus_pf = <<<EOD
<div>
%s
<form method="post" action="changeplaystatus.php" style="display: inline" >
<input type="hidden" name="id" value="%s" />
<input type="hidden" name="songfile" value="%s" />
<select name="nowplaying">
 <option value="未再生" selected >未再生 </option>
 <option value="再生済">再生済 </option>
</select>
<input type="submit" name="update" value="変更"/>
</form>
</div>
EOD;
    $playstatus = sprintf($playstatus_pf,  $value['nowplaying'], $value['id'], $value['songfile']);
    if($config_ini['playmode'] == 1){
        $onerequset += array("再生状況" => $playstatus);
    }elseif($config_ini['playmode'] == 2){
        $onerequset += array("再生状況" => $playstatus);
    }elseif($config_ini['playmode'] == 2){
        $onerequset += array("再生回数" => $value['playtimes']);
    }else{
        $onerequset += array("順番" => $value['reqorder']);
    }

$action_pf = <<<EOD
<form method="post" action="delete.php">
<input type="hidden" name="id" value="%s" />
<input type="hidden" name="songfile" value="%s" />
<div class="acition" >
<input type="submit" name="up"     value="上へ"/>
<input type="submit" name="down"   value="下へ"/>
<input type="submit" name="warikomi"   value="次に再生"/>
<input type="submit" name="delete" value="削除"/>
</div>
<div class="clear" >
</div>
EOD;
    $action = sprintf($action_pf,$value['id'],$value['songfile']);
    $onerequset += array("アクション" => $action);
    
    if($user === "admin"){
    $change_entry_pf = <<<EOD
<td class="change">
<form method="post" action="change.php">
<input type="hidden" name="id" value="%s" />
<input type="hidden" name="songfile" value="%s" />
<input type="submit" name="変更"   value="変更"/>
</form>
EOD;
    $change_entry = sprintf($change_entry_pf,$value['id'],$value['songfile']);
    $onerequset += array("変更" => $change_entry);
    }
    
    array_push ($requsetlisttable, $onerequset);
}

$json = json_encode(array("aaData" => $requsetlisttable),JSON_PRETTY_PRINT);

print $json;

?>
