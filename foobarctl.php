
<?php

$foobarctrlurl = "http://localhost:82/karaokectrl/";


function songstart(){
      require_once 'commonfunc.php';
      global  $foobarctrlurl;
      global $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生開始待ち' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      if($select === false) return;
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $starturl = $foobarctrlurl."?cmd=Start&param1=0";
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small>  </small>'."\n";
          $res = file_get_html_with_retry($starturl);
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = ".$currentsong[0]['id'];
          $ret = $db->exec($sql);
          sleep(2);
          $res = file_get_html_with_retry($starturl);
      }
}

  if( !empty($_REQUEST['songnext']) ){
      require_once 'kara_config.php';
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
      $ret = $db->exec($sql);
  }

  if( array_key_exists('songstart',$_REQUEST) ){
      songstart();
  }  

?>
<script type="text/javascript" src="foobarctrl.js"></script>
<p align="center" style="margin-bottom: 0;" > 音楽再生用(foobar2000) </p>
<div align="center" class="playercontrol" >
<div class="col-xs-12">
<form method="post"action="playerctrl_portal.php" style="display: inline" >
<input type="submit" value="再生開始" name="songstart" class="playstart btn btn-default" onClick="song_play()" />
</form>
</div >
<br>
<div class="col-xs-4">
<button type="submit" value="曲の最初から" class="pcbuttom btn btn-default" onClick="song_startfirst()" >曲の最初から</button>
</div >
<div class="col-xs-4">
<button type="submit" value="一時停止"  class="pcbuttom btn btn-default" onClick="song_pause()" >一時停止</button>
</div >
<div class="col-xs-4">
<button type="submit" value="曲終了" name="songnext" class="pcbuttom  btn btn-default" onClick="song_next()" />曲終了</button>
</div >
<br>
<div class="col-xs-6">
<button type="submit" value="ボリュームDOWN" class="pcvolume btn btn-default" onClick="song_vdown()" >ボリュームDOWN</button>
</div >
<div class="col-xs-6">
<button type="submit" value="ボリュームUP" class="pcvolume btn btn-default" onClick="song_vup()" >ボリュームUP</button>
</div >
<br>

</div>


