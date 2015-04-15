<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>MediaPlayerClassic コントローラー</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" src="mpcctrl.js"></script>
</head>
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
<body>

<div align="center" class="playercontrol" id="playercontrol">
<input type="submit" value="再生開始" class="playstart" onClick="song_play()" />
<br>
<button type="submit" value="曲の最初から" class="pcbuttom" onClick="song_startfirst()" >曲の最初から</button>
<button type="submit" value="一時停止"  class="pcbuttom" onClick="song_pause()" >一時停止</button>
<form method="post"action="mpcctrl.php" style="display: inline" >
<input type="submit" value="曲終了" name="songnext" class="pcbuttom" onClick="song_next()" />
</form>
<br>
<button type="submit" value="ボリュームUP" class="pcvolume" onClick="song_vup()" >ボリュームUP</button>
<button type="submit" value="ボリュームDOWN" class="pcvolume" onClick="song_vdown()" >ボリュームDOWN</button>
<br>
<button type="submit" value="字幕ONOFF(ソフトサブのみ)" class="pcmorefunc" onClick="song_subtitleonnoff()" >字幕ONOFF(ソフトサブのみ)</button>
<button type="submit" value="音声トラック変更" class="pcmorefunc" onClick="song_changeaudio()" >音声トラック変更</button>
<button type="submit" value="フルスクリーンON/OFF" class="pcmorefunc" onClick="song_fullscreen()" >フルスクリーンON/OFF</button>
<br>
<button type="submit" value="(-100ms)音ズレ修正" class="pcdelay" onClick="song_audiodelay_m100()" >(-100ms)音ズレ修正</button>
<button type="submit" value="(-10ms)音ズレ修正" class="pcdelay" onClick="song_audiodelay_m10()" >(-10ms)音ズレ修正</button>
<button type="submit" value="(+10ms)音ズレ修正" class="pcdelay" onClick="song_audiodelay_p10()" >(+10ms)音ズレ修正</button>
<button type="submit" value="(+100ms)音ズレ修正" class="pcdelay" onClick="song_audiodelay_p100()" >(+100ms)音ズレ修正</button>
<br>

</div>
</body>
</html>

