<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>MediaPlayerClassic コントローラー</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" src="foobarctrl.js"></script>
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
<p align="center" style="margin-bottom: 0;" > 音楽再生用(foobar2000) </p>
<div align="center" class="playercontrol" >
<input type="submit" value="再生開始" class="playstart" onClick="song_play()" />
<br>
<button type="submit" value="曲の最初から" class="pcbuttom" onClick="song_startfirst()" >曲の最初から</button>
<button type="submit" value="一時停止"  class="pcbuttom" onClick="song_pause()" >一時停止</button>
<button type="submit" value="曲終了" name="songnext" class="pcbuttom" onClick="song_next()" />曲終了</button>
<br>
<button type="submit" value="ボリュームDOWN" class="pcvolume" onClick="song_vdown()" >ボリュームDOWN</button>
<button type="submit" value="ボリュームUP" class="pcvolume" onClick="song_vup()" >ボリュームUP</button>
<br>

</div>
</body>
</html>

