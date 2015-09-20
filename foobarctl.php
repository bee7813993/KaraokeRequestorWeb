
<?php
  if( !empty($_POST['songnext']) ){
      require_once 'kara_config.php';
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
      $ret = $db->exec($sql);
  }

?>
<script type="text/javascript" src="foobarctrl.js"></script>
<p align="center" style="margin-bottom: 0;" > 音楽再生用(foobar2000) </p>
<div align="center" class="playercontrol" >
<div class="col-xs-12">
<input type="submit" value="再生開始" class="playstart btn btn-default" onClick="song_play()" />
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


