<?php

require_once 'commonfunc.php';

$user=null;

if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}




$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$json = json_encode($allrequest,JSON_PRETTY_PRINT);

$requsetlisttable = array();
$reqcount = count($allrequest);

foreach($allrequest as $value ){
    $playingid = null;
    if($value['nowplaying'] === '再生中'){
        $playingid = 'id="nowplayinghere"';
    }
    $onerequset = array();
    $onerequset += array("no" => $reqcount);
    $reqcount -= 1;
    if( ($value['secret'] == 1 ) && strcmp($value['nowplaying'],'未再生') == 0){
        $onerequset += array("filename" => '<div '.$playingid.' >'.  nl2br(htmlspecialchars(' ヒ・ミ・ツ♪(シークレット予約) ')).'</div>');
    }else{
        $onerequset += array("filename" => '<div '.$playingid.' >'.  nl2br(htmlspecialchars($value['songfile'])).'</div>');
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
<div >
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

    $playstatus = sprintf($playstatus_pf, $value['nowplaying'], $value['id'], $value['songfile']);
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
<div class="">
<form method="post" class="requestmove" action="delete.php">
<input type="hidden" name="id" class="requestid" value="%s" />
<input type="hidden" name="songfile" id="requestsongfile" value="%s" />
<div class="acition" >
<button class="btn btn-default requestmove" type="button" name="up"  id="requestup" value="up" onClick='moverequestlist(this,%s,"up","%s")' >上へ</button>
<button class="btn btn-default requestmove" type="button" name="down" id="requestdown"  value="down" onClick='moverequestlist(this,%s,"down","%s")' > 下へ</button>
<button class="btn btn-default requestmove" type="button" name="warikomi" id="requesttonext" value="warikomi" onClick='moverequestlist(this,%s,"warikomi","%s")' > 次に再生</button>
<!--
<button type="submit" name="delete" id="requestdelete" value="delete" > 削除</button>
-->
</div>
</form>
<button class="btn btn-default" data-toggle="modal" data-target="#act_modal_%s">削除</button>
</div>
<!-- 2.モーダルの配置 -->
<div class="modal" id="act_modal_%s" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
         <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modal-label">%sを削除します</h4>
      </div>
      <div class="modal-footer">
        <form method="post" action="delete.php">
        <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
          <input type="hidden" name="id" class="requestid" value="%s" />
          <input type="hidden" name="songfile" id="requestsongfile" value="%s" />
          <button class="btn btn-primary" type="submit" name="delete" id="requestdelete" value="delete" > 削除</button>
        </form>
      </div>
    </div>
  </div>
</div>
%s

<div class="clear" >
</div>
EOD;
    
    if($connectinternet == 1){
    if($value['nowplaying'] === '再生中'){
            $tweet_message = sprintf("「%s」は「%s」を歌っています",$value['singer'],$value['songfile']);
    }
    elseif($value['nowplaying'] === '未再生'){
            $tweet_message = sprintf("「%s」は「%s」を歌います",$value['singer'],$value['songfile']);
    }
    else{
            $tweet_message = sprintf("「%s」は「%s」を歌いました",$value['singer'],$value['songfile']);
    }
    $tweet_link = sprintf('<a href="https://twitter.com/intent/tweet?text=%s" TARGET="_blank" > Tweetする </a>',nl2br(htmlspecialchars($tweet_message)));
    }else {
    $tweet_link = ' ';
    }
    // シークレット予約時の曲対応
    // 条件：表示している人が本人かどうか
    $myname = returnusername_self();
    if($value['singer'] === $myname ){
       $dialogsongname='「'.$value['songfile'].'」';
    }else{
       if($value['secret'] == 1 ){
           $dialogsongname='<span class="text-danger">'.$value['singer'].'さん</span>が歌う【シークレット予約曲】';
       }else{
           $dialogsongname='<span class="text-danger">'.$value['singer'].'さん</span>が歌う「'.$value['songfile'].'」';
       }
    }
    
    $action = sprintf($action_pf,$value['id'],htmlspecialchars($value['songfile']),$value['id'],urlencode($value['songfile']),$value['id'],urlencode($value['songfile']),$value['id'],urlencode($value['songfile']),$value['id'],$value['id'],$dialogsongname,$value['id'],htmlspecialchars($value['songfile']), $tweet_link);
    $onerequset += array("action" => $action);
    
    if($user === "admin"){
    $change_entry_pf = <<<EOD
<form method="post" action="change.php">
<input type="hidden" name="id" value="%s" />
<input type="hidden" name="songfile" value="%s" />
<input type="submit" name="変更"   value="変更"/>
</form>
EOD;
    
    $change_entry = sprintf($change_entry_pf,$value['id'],htmlspecialchars($value['songfile']));
    $onerequset += array("change" => $change_entry);
    }
    
    array_push ($requsetlisttable, $onerequset);
}

$json = json_encode($requsetlisttable,JSON_PRETTY_PRINT);

print $json;

?>
