<?php
require_once 'commonfunc.php';
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Please use username admin."');
    die('設定画面の表示にはログインが必要です');
}
if ($_SERVER['PHP_AUTH_USER'] !== 'admin') {
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('設定画面の表示にはログインが必要です');
}
if (!$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('設定画面の表示にはログインが必要です');
}

require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

require_once 'func_audiotracklist.php';

$lister_dbpath = '';
if (array_key_exists('listerDBPATH', $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

$stmt = $db->query("SELECT id, songfile, fullpath, kind FROM requesttable ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$updated = 0;
$skipped = 0;
$update_stmt = $db->prepare("UPDATE requesttable SET duration = :duration WHERE id = :id");

foreach ($rows as $row) {
    if (empty($row['fullpath'])) {
        $skipped++;
        continue;
    }

    $fullpath_utf8 = '';
    get_fullfilename($row['fullpath'], $row['songfile'], $fullpath_utf8, $lister_dbpath);

    if (empty($fullpath_utf8)) {
        $skipped++;
        continue;
    }

    $details = getvideodetails($fullpath_utf8);
    if (!isset($details['duration_seconds']) || $details['duration_seconds'] <= 0) {
        $skipped++;
        continue;
    }

    $update_stmt->execute([
        ':duration' => $details['duration_seconds'],
        ':id'       => (int)$row['id'],
    ]);
    $updated++;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="refresh" content="3; url=init.php">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>曲の長さ一括更新</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php shownavigatioinbar(); ?>
<div class="container" style="margin-top:20px;">
  <h3>曲の長さを一括更新しました</h3>
  <p>更新：<strong><?php echo $updated; ?></strong> 件 ／ スキップ：<strong><?php echo $skipped; ?></strong> 件</p>
  <p class="text-muted">（ファイルが見つからない・長さが取得できない場合はスキップされます）</p>
  <p>3秒後に設定画面に戻ります。</p>
  <a href="init.php" class="btn btn-default">すぐに戻る</a>
</div>
</body>
</html>
