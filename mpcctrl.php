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
      include 'kara_config.php';
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
      $ret = $db->exec($sql);
  }

?>
<body>

<div align="center" class="playercontrol" >
<input type="submit" value="再生開始" class="playstart" onClick="song_play()" />
<br>
<input type="submit" value="曲の最初から" class="pcbuttom" onClick="song_startfirst()" />
<input type="submit" value="一時停止"  class="pcbuttom" onClick="song_pause()" />
<form method="post"action="mpcctrl.php" style="display: inline" >
<input type="submit" value="曲終了" name="songnext" class="pcbuttom" onClick="song_next()" />
</form>
<br>
<input type="submit" value="ボリュームUP" class="pcbuttom" onClick="song_vup()" />
<input type="submit" value="ボリュームDOWN" class="pcbuttom" onClick="song_vdown()" />
<br>
<input type="submit" value="音声トラック変更" class="pcbuttom" onClick="song_changeaudio()" />
<input type="submit" value="字幕ONOFF(ソフトサブのみ)" class="pcbuttom" onClick="song_subtitleonnoff()" />
<br>
<input type="submit" value="音ズレ修正(-100ms)" class="pcbuttom" onClick="song_audiodelay_m100()" />
<input type="submit" value="音ズレ修正(-10ms)" class="pcbuttom" onClick="song_audiodelay_m10()" />
<input type="submit" value="音ズレ修正(+10ms)" class="pcbuttom" onClick="song_audiodelay_p10()" />
<input type="submit" value="音ズレ修正(+100ms)" class="pcbuttom" onClick="song_audiodelay_p100()" />
<br>
<input type="submit" value="フルスクリーンON/OFF" class="pcbuttom" onClick="song_fullscreen()" />

</div>
</body>
</html>

