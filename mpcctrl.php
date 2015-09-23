
<?php
      require_once 'kara_config.php';
  if( !empty($_POST['songnext']) ){
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
      $ret = $db->exec($sql);
  }

?>
<script type="text/javascript" src="mpcctrl.js"></script>

<p align="center" style="margin-bottom: 0;"> 動画再生用(Media Player Classic) </p>
<div align="center" class="playercontrol" id="playercontrol">
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
<form method="post"action="playerctrl_portal.php" style="display: inline" >
<input type="submit" value="曲終了" name="songnext" class=" pcbuttom btn btn-default" onClick="song_next()" />
</form>
</div >
<br>
<div class="col-xs-6">
<button type="submit" value="ボリュームDOWN" class=" pcvolume btn btn-default" onClick="song_vdown()" >ボリュームDOWN</button>
</div >
<div class="col-xs-6">
<button type="submit" value="ボリュームUP" class="pcvolume btn btn-default" onClick="song_vup()" >ボリュームUP</button>
</div >
<br>
<?php
if($moviefullscreen == 1){
print '<div class="col-xs-4">';
print '<button type="submit" value="字幕ONOFF(ソフトサブのみ)" class=" pcmorefunc btn btn-default" onClick="song_subtitleonnoff()" >字幕ONOFF(ソフトサブのみ)</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="音声トラック変更" class=" pcmorefunc btn btn-default" onClick="song_changeaudio()" >音声トラック変更</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="フルスクリーンON/OFF" class=" pcmorefunc btn btn-default" onClick="song_fullscreen()" >フルスクリーンON/OFF</button>';
print '</div >';
}else{
print '<div class="col-xs-6">';
print '<button type="submit" value="字幕ONOFF(ソフトサブのみ)" class=" pcmorefunc btn btn-default" onClick="song_subtitleonnoff()" >字幕ONOFF(ソフトサブのみ)</button>';
print '</div >';
print '<div class="col-xs-6">';
print '<button type="submit" value="音声トラック変更" class=" pcmorefunc btn btn-default" onClick="song_changeaudio()" >音声トラック変更</button>';
print '</div >';
}
?>
<div class="col-xs-3">
<button type="submit" value="(-100ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_m100()" >(-100ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(-10ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_m10()" >(-10ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(+10ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_p10()" >(+10ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(+100ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_p100()" >(+100ms)<br>音ズレ修正</button>
</div >
<br>

</div>


