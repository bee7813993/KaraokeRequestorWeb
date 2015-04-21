<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>検索予約ツール接続情報</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>

<div class="toolinfo">
接続用WiFI SSID <input type="text" name="ssid" size="100" class="toolinfo" />
&nbsp;<br />
接続用WiFI パスワード <input type="text" name="wifipass" size="100" class="toolinfo" /><br />
</div>
<br />
<divclass="toolinfo" >
接続先 URL : <input type="text" name="toolurl" class="toolinfo" size="100" value=<?php echo 'http://'.$_SERVER["HTTP_HOST"];?>/>
<input type="text" name="toolurl" class="toolinfo" size="100" value="<?php echo 'http://'.$_SERVER["SERVER_ADDR"].'/';?>" />
</div>

<a href="/request.php" > リクエストTOPに戻る </a>
</body>
</html>