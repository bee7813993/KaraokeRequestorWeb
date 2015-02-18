<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>MediaPlayerClassic コントローラー</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" src="mpcctrl.js"></script>
</head>
<body>

<div align="center" >

<input type="submit" value="曲の最初から" onClick="song_startfirst()" />
<input type="submit" value="再生開始" onClick="song_play()" />
<input type="submit" value="一時停止" onClick="song_pause()" />
<input type="submit" value="曲終了" onClick="song_next()" />
<br>
<input type="submit" value="ボリュームUP" onClick="song_vup()" />
<input type="submit" value="ボリュームDOWN" onClick="song_vdown()" />
<br>
<input type="submit" value="音声トラック変更" onClick="song_changeaudio()" />
<input type="submit" value="字幕ONOFF(ソフトサブのみ)" onClick="song_subtitleonnoff()" />
<br>
<input type="submit" value="音ズレ修正(-10ms)" onClick="song_audiodelay_m10()" />
<input type="submit" value="音ズレ修正(+10ms)" onClick="song_audiodelay_p10()" />

</div>
</body>
</html>

