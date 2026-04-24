<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>インポート - マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage_import.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);
$msg = '';
$msg_type = 'success';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $overwrite = (isset($_POST['mode']) && $_POST['mode'] === 'overwrite');

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'ファイルのアップロードに失敗しました。';
        $msg_type = 'danger';
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $msg = 'ファイルサイズが大きすぎます（上限 10MB）。';
        $msg_type = 'danger';
    } else {
        $json = file_get_contents($file['tmp_name']);
        $data = json_decode($json, true);
        if ($data === null) {
            $msg = 'JSONの解析に失敗しました。正しいエクスポートファイルを選択してください。';
            $msg_type = 'danger';
        } else {
            $result = $mypage->importData($data, $overwrite);
            if ($result['ok']) {
                $c = $result['counts'];
                $msg = 'インポートが完了しました。'
                     . '（選曲履歴: ' . $c['history'] . '件、'
                     . '後で歌う: ' . $c['later'] . '件、'
                     . 'お気に入り曲: ' . $c['favorite_songs'] . '件、'
                     . 'お気に入り検索ワード: ' . $c['favorite_keywords'] . '件）';
                $msg_type = 'success';
            } else {
                $msg = 'インポート中にエラーが発生しました: ' . htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8');
                $msg_type = 'danger';
            }
        }
    }
}
?>
<div class="container" style="margin-top:80px;">
  <h2>データのインポート</h2>
  <p><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if ($msg): ?>
  <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">インポートファイルを選択</h4></div>
    <div class="panel-body">
      <form method="POST" action="mypage_import.php" enctype="multipart/form-data"
            id="importForm">
        <div class="form-group">
          <label>エクスポートファイル（.json）</label>
          <input type="file" name="import_file" accept=".json,application/json" required />
        </div>
        <div class="form-group">
          <label>インポートモード</label>
          <div class="radio">
            <label>
              <input type="radio" name="mode" value="merge" checked />
              <strong>追加（マージ）</strong> &mdash; 既存データを保持し、ファイルにのみ存在するエントリを追加します。
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="mode" value="overwrite" id="modeOverwrite" />
              <strong>上書き（リセット）</strong> &mdash; 既存のデータをすべて削除してからインポートします。表示名も復元されます。
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" id="importBtn">インポート実行</button>
      </form>
    </div>
  </div>

  <div class="panel panel-info">
    <div class="panel-heading"><h4 class="panel-title">インポートされる内容</h4></div>
    <div class="panel-body">
      <ul>
        <li>選曲履歴</li>
        <li>後で歌うリスト</li>
        <li>お気に入り曲</li>
        <li>お気に入り検索ワード</li>
        <li>表示名（上書きモードのみ）</li>
      </ul>
    </div>
  </div>
</div>
<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    var overwrite = document.getElementById('modeOverwrite').checked;
    if (overwrite) {
        if (!confirm('上書きモードでは既存のデータがすべて削除されます。\n本当に実行しますか？')) {
            e.preventDefault();
        }
    }
});
</script>
</body>
</html>
