<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';

$mypage = null;
$msg = '';
$msg_type = 'success';
$result = null;

if (configbool("usemypage", true)) {
    $mypage = new MypageUser($db);

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
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>インポート - マイページ</title>
<?php print_bs5_head_core(); ?>
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }</style>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage_import.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}
// $mypage/$msg/$msg_type/$result は冒頭の PHP ブロックで設定済み
?>
<div class="container py-3">
  <h2 class="mb-2">データのインポート</h2>
  <p class="mb-3"><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if ($msg): ?>
  <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">インポートファイルを選択</h5></div>
    <div class="card-body">
      <form method="POST" action="mypage_import.php" enctype="multipart/form-data"
            id="importForm">
        <div class="mb-3">
          <label class="form-label">エクスポートファイル（.json）</label>
          <input type="file" name="import_file" class="form-control" accept=".json,application/json" required />
        </div>
        <div class="mb-3">
          <label class="form-label">インポートモード</label>
          <div class="form-check">
            <input type="radio" name="mode" value="merge" id="modeMerge" class="form-check-input" checked />
            <label class="form-check-label" for="modeMerge">
              <strong>追加（マージ）</strong> &mdash; 既存データを保持し、ファイルにのみ存在するエントリを追加します。
            </label>
          </div>
          <div class="form-check">
            <input type="radio" name="mode" value="overwrite" id="modeOverwrite" class="form-check-input" />
            <label class="form-check-label" for="modeOverwrite">
              <strong>上書き（リセット）</strong> &mdash; 既存のデータをすべて削除してからインポートします。表示名も復元されます。
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" id="importBtn">インポート実行</button>
      </form>
    </div>
  </div>

  <div class="card border-info mb-3">
    <div class="card-header text-bg-info"><h5 class="card-title mb-0">インポートされる内容</h5></div>
    <div class="card-body">
      <ul class="mb-0">
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
