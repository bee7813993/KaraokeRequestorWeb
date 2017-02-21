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
    $playingid = 'id="id_'.$value['id'].'" ';
    
       // $playingid = 'id="nowplayinghere"';
    
    $onerequset = array();
    $onerequset += array("no" => $reqcount);
    $reqcount -= 1;
    $songfilename = '';
    if( ($value['secret'] == 1 ) && strcmp($value['nowplaying'],'未再生') == 0){
        $songfilename = nl2br(htmlspecialchars(' ヒ・ミ・ツ♪(シークレット予約) '));
    }else{
        $songfilename = nl2br(htmlspecialchars($value['songfile']));
    }
    if($value['loop'] == 1){
        $songfilename = '<b>'.htmlspecialchars('【ＢＧＶ】').'</b><br>'.$songfilename;
    }
    if($value['nowplaying'] === '再生中'){
    $onerequset += array("filename" => '<div '.$playingid.' class="idmove" > '.  $songfilename.'<a name="nowplayinghere" class="nowplayinghere" >&nbsp</a></div>');
    }else{
    $onerequset += array("filename" => '<div '.$playingid.' class="idmove" > '.  $songfilename.'</div>');
    }
    $onerequset += array("singer" =>  nl2br(htmlspecialchars($value['singer'])));

$showcommentblock = "";
if(strlen($value['comment']) === 0){
//    $showcommentblock = '<a class="btn btn-default" data-toggle="modal" data-target="#comment_modal_'.$value['id'].'">修正orレス</a>';
    $showcommentblock = '<a href="#" data-toggle="modal" class="commentmodallink" data-target="#comment_modal_'.$value['id'].'"  title="このエリアを押すことでコメントにレスを付けたり編集したりできます">'.nl2br(htmlspecialchars($value['comment']))."</a>\n";
} else {
    $showcommentblock = '<a href="#" data-toggle="modal" class="commentmodallink" data-target="#comment_modal_'.$value['id'].'"  title="このエリアを押すことでコメントにレスを付けたり編集したりできます">'.nl2br(htmlspecialchars($value['comment']))."</a>\n";
}    
    $comment_pf = <<<EOD
<div style="position: relative;width:100%%;min-height: 1em;">
%s 
<!-- 2.モーダルの配置 -->
<div class="modal" id="comment_modal_%s" tabindex="-1" >
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
         <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modal-label">コメントへのレス＆編集</h4>
      </div>
      <form method="GET" action="update.php" class="sendnomove" >\n
      <div class="form-group">
      <textarea class="form-control" name="comment"  >%s</textarea>
      <input type="hidden" name="id" value="%s" />\n
      <input type="submit" class="btn btn-default pull-right" name="edit"   value="修正"  />\n
      </div>
      </form>\n
      <form method="GET" action="commentedit.php" class="sendnomove" >\n
      <label class="control-label">コメント <small>再生中にコメントするとその場で流れます</small></label>
      <div class="form-group btn-toolbar">
      <label  class="col-sm-2 control-label">レス</label>
      <div class="col-sm-10">
      <input type="text" class="form-control" name="addcomment"  value="" placeholder="レス(コメントへの)"/>\n
      </div>
      <label  class="col-sm-2 control-label">名前</label>
      <div class="col-sm-10">
        <input type="text" name="name" class="form-control" value="%s" placeholder="名前" />\n
      </div>
      <input type="hidden" name="id" value="%s" />\n
      <input type="submit" name="add"  class="btn btn-primary pull-right" value="送信"/>\n
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
      </div>
    </form>
    </div>
  </div>
</div>
</div>
EOD;


    $comment = sprintf($comment_pf, $showcommentblock, $value['id'], $value['comment'], $value['id'],   nl2br(htmlspecialchars(returnusername($allrequest))), $value['id']);

if($usebingo &&  $value['nowplaying'] !== '未再生'){
    require_once 'binngo_func.php';
    $bingoinfo = new SongBingo();
    $bingoinfo->initbingodb('songbingo.db'); 
    $bingoidlist = $bingoinfo->getnumfromid( $value['id'] );
    $bingoidtxt = "";
    foreach($bingoidlist as $valueids ){
       if(strlen($bingoidtxt)>0){
           $bingoidtxt = $bingoidtxt.', ';
       }
       $bingoidtxt = $bingoidtxt.$valueids;
    }
    if(!empty($bingoidlist)){
       $comment=$comment.'<div align="right">ビンゴ番号：'.$bingoidtxt.'</div>';
    }
    $bingoinfo = null;
}

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
<input type="submit" name="update" value="変更" onClick='changerequeststatus(this);return false;' />
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

$sasikaemenu = '';
if($value['kind'] == 'カラオケ配信' && $config_ini['usebgv'] == 1 ) {
  $sasikaemenu = '<li> <a class="requestmove" name="changesong" id="changesong" href="searchreserve.php?id='.$value['id'].'" value="changesong"  > BGV選択</a> </li>';
} else {
  $sasikaemenu = '<li> <a class="requestmove" name="changesong" id="changesong" href="searchreserve.php?id='.$value['id'].'" value="changesong"  > 曲差し替え</a> </li>';
}
if($user === "admin"){
    if($usebingo){
        $list_bingoinput='<li> <a class="requestmove" name="bingoinput" id="bingoinput" href="bingo_openinput.php?id='.$value['id'].'" value="bingoinput"  > ビンゴ入力</a> </li>';
        $sasikaemenu = $sasikaemenu.$list_bingoinput;
    }
}

$action_pf = <<<EOD
<div class="dropdown">
<form method="post" class="requestmove" action="delete.php">
<input type="hidden" name="id" class="requestid" value="%s" />
<input type="hidden" name="songfile" id="requestsongfile" value="%s" />
<div class="acition" >
<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
リスト操作
<span class="caret"></span>
</button>
<ul class="dropdown-menu">
<li> <a class="requestmove" name="up"  id="requestup" value="up" onClick='moverequestlist(this,%s,"up","%s")' >上へ</a> </li>
<li> <a class="requestmove" name="down" id="requestdown"  value="down" onClick='moverequestlist(this,%s,"down","%s")' > 下へ</a> </li>
<li> <a class="requestmove" name="warikomi" id="requesttonext" value="warikomi" onClick='moverequestlist(this,%s,"warikomi","%s")' > 次に再生</a> </li>
%s
<li> <a href="#" class="" data-toggle="modal" data-target="#act_modal_%s">削除</a> </li>
</ul>
</div>
<!--
<button type="submit" name="delete" id="requestdelete" value="delete" > 削除</button>
-->
</form>
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
        <form method="post" class="sendnomove" action="delete.php">
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
    // 曲終了ボタン
    $songstop = "";
    if($value['nowplaying'] === '再生中'){
        if($value['kind'] === 'カラオケ配信' || $value['kind'] === '動画_別プ' ){ 
        $songstop ='<button type="button" class="btn btn-default" onClick=\'song_end(this,'.$value["id"].');return false;\' >曲停止</button>';
        }
    }
    // 曲開始ボタン
    if($value['nowplaying'] === '再生開始待ち'){
        if($value['kind'] === '動画'  ){ 
        $songstop ='<button type="button" class="btn btn-default" onClick=\'song_start(this,'.$value["id"].');return false;\' >曲開始</button>';
        }
    }
    
    // シークレット予約時の曲対応
    // 条件：表示している人が本人かどうか
    $public_songname=$value['songfile'];
    $myname = returnusername_self();
    if($value['singer'] === $myname ){
       $dialogsongname='「'.$value['songfile'].'」';
    }else{
       if($value['secret'] == 1 ){
           $dialogsongname='<span class="text-danger">'.$value['singer'].'さん</span>が歌う【シークレット予約曲】';
           $public_songname='シークレットで予約した曲';
       }else{
           $dialogsongname='<span class="text-danger">'.$value['singer'].'さん</span>が歌う「'.$value['songfile'].'」';
       }
    }
    if($connectinternet == 1){
    if($value['nowplaying'] === '再生中'){
            $tweet_message = sprintf("「%s」は「%s」を歌っています",$value['singer'],$value['songfile']);
    }
    elseif($value['nowplaying'] === '未再生'){
            $tweet_message = sprintf("「%s」は「%s」を歌います",$value['singer'],$public_songname);
    }
    else{
            $tweet_message = sprintf("「%s」は「%s」を歌いました",$value['singer'],$value['songfile']);
    }
    $tweet_link = sprintf('<a href="https://twitter.com/intent/tweet?text=%s" TARGET="_blank" > Tweetする </a>',urlencode($tweet_message));
    }else {
    $tweet_link = ' ';
    }
    $tweet_link = $songstop.$tweet_link;
    
    $action = sprintf($action_pf,$value['id'],htmlspecialchars($value['songfile']),$value['id'],urlencode($value['songfile']),$value['id'],urlencode($value['songfile']),$value['id'],urlencode($value['songfile']),$sasikaemenu,$value['id'],$value['id'],$dialogsongname,$value['id'],htmlspecialchars($value['songfile']), $tweet_link);
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
