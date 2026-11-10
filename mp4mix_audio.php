<?php
// 音源差し替え機能 (ダウンロードした動画の音声を、アップロードした音楽ファイルに差し替える) は廃止した。
// ファイルを削除しないのは、ZIP 方式のオンラインアップデートが既存のファイルを消さず、
// 古い版がそのまま残ってしまうため。中身をこの内容に置き換えて無効にしている。
http_response_code(410);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>音源差し替え</title>
</head>
<body>
<p>音源差し替え機能は廃止しました。</p>
<p><a href="requestlist_top.php">トップに戻る</a></p>
</body>
</html>
