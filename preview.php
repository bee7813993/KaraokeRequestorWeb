<?php

$movie = $_REQUEST["movieurl"];

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>動画プレビュー画面</title>
<link href="video-js/video-js.css" rel="stylesheet" type="text/css">
<script src="video-js/video.js"></script>
<script>
videojs.options.flash.swf = "video-js/video-js.swf";
</script>
</head>
<body>

<br>
もしかしたらプレビューできるかもしれない画面。<br>
ブラウザがHTML5でその動画形式を再生できるか次第<br>
<video id="preview_video" class="video-js vjs-default-skin" controls preload="none"  data-setup="{}" width="90%" height="90%" >
    <source src="<?php echo $movie ?>" type='video/mp4' />
</video>

<FORM>
<INPUT type="button" value="戻る" onClick="history.back()">
</FORM>

</body>
</html>

