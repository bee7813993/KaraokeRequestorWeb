<?php

require_once 'commonfunc.php';

$user=null;

if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}


function returnusername($rt){
    $rt_i = array_reverse($rt);
    foreach($rt_i as $row){
          if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
            if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return $row['singer'];
            }
          }
    }
    
    return "";
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
    $onerequset += array("no" => $reqcount);
    $reqcount -= 1;
    if( ($value['secret'] == 1 ) && strcmp($value['nowplaying'],'未再生') == 0){
        $onerequset += array("filename" =>  nl2br(htmlspecialchars(' ヒ・ミ・ツ♪(シークレット予約) ')));
    }else{
        $onerequset += array("filename" =>  nl2br(htmlspecialchars($value['songfile'])));
    }
    
    $onerequset += array("singer" =>  nl2br(htmlspecialchars($value['singer'])));
    
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
    $comment = sprintf($comment_pf,  nl2br(htmlspecialchars($value['comment'])), $value['id'],   nl2br(htmlspecialchars(returnusername($allrequest))), $value['id']);
    $onerequset += array("comment" => $comment);

    $onerequset += array("method" => $value['kind']);

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
        $onerequset += array("playstatus" => $playstatus);
    }elseif($config_ini['playmode'] == 2){
        $onerequset += array("playstatus" => $playstatus);
    }elseif($config_ini['playmode'] == 4){
        $onerequset += array("playstatus" => $value['playtimes']);
    }else{
        $onerequset += array("playstatus" => $value['reqorder']);
    }

$action_pf = <<<EOD
<form method="post" action="delete.php">
<input type="hidden" name="id" class="requestid" value="%s" />
<input type="hidden" name="songfile" id="requestsongfile" value="%s" />
<div class="acition" >
<input type="submit" name="up"  id="requestup"   value="上へ"/>
<input type="submit" name="down" id="requestdown"  value="下へ"/>
<input type="submit" name="warikomi" id="requesttonext"  value="次に再生"/>
<input type="submit" name="delete" id="requestdelete" value="削除"/>
</div>
</form>
%s

<div class="clear" >
</div>
EOD;
    
    if($connectinternet == 1){
    $tweet_message = sprintf("「%s」は「%s」を歌っています",$value['singer'],$value['songfile']);
    $tweet_link = sprintf('<a href="http://twitter.com/?status=%s" > Tweetする </a>',nl2br(htmlspecialchars($tweet_message)));
    }else {
    $tweet_link = ' ';
    }
    $action = sprintf($action_pf,$value['id'],$value['songfile'], $tweet_link);
    $onerequset += array("action" => $action);
    
    if($user === "admin"){
    $change_entry_pf = <<<EOD
<form method="post" action="change.php">
<input type="hidden" name="id" value="%s" />
<input type="hidden" name="songfile" value="%s" />
<input type="submit" name="変更"   value="変更"/>
</form>
EOD;
    
    $change_entry = sprintf($change_entry_pf,$value['id'],$value['songfile']);
    $onerequset += array("change" => $change_entry);
    }
    
    array_push ($requsetlisttable, $onerequset);
}

$json = json_encode($requsetlisttable,JSON_PRETTY_PRINT);

print $json;

?>
