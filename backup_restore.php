<?php
/**
 * 設定バックアップ復元
 * 管理者専用 (HTTP Basic Auth 必須)
 * ZIPをアップロードして設定ファイルを上書き復元する。
 */
require_once 'commonfunc.php';
require_once 'configauth_class.php';

$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Backup restore - admin only"');
    die('認証が必要です');
}
if ($_SERVER['PHP_AUTH_USER'] !== 'admin') {
    header('WWW-Authenticate: Basic realm="Backup restore - admin only"');
    die('認証が必要です');
}
if (!$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Backup restore - admin only"');
    die('認証が必要です');
}

// 復元を許可するパス（パストラバーサル防止）
$allowed_files = [
    'config.ini',
    'listerdb_config.ini',
    'search_sort_priority.json',
    'search_sort_priority_auth.json',
];
$allowed_prefix_images = 'images/bg/';

$base = realpath(__DIR__);

$result_msg  = '';
$result_type = 'success'; // 'success' | 'danger'
$restored    = [];
$skipped     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('ZipArchive')) {
        $result_msg  = 'ZipArchive が利用できません。PHP zip 拡張を有効にしてください。';
        $result_type = 'danger';
    } elseif (!isset($_FILES['backup_zip']) || $_FILES['backup_zip']['error'] !== UPLOAD_ERR_OK) {
        $result_msg  = 'ファイルのアップロードに失敗しました。';
        $result_type = 'danger';
    } else {
        $tmpfile = $_FILES['backup_zip']['tmp_name'];

        // MIMEチェック（簡易）
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmpfile);
        if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
            $result_msg  = 'ZIPファイル以外はアップロードできません（検出されたMIME: ' . htmlspecialchars($mime) . '）。';
            $result_type = 'danger';
        } else {
            $zip = new ZipArchive();
            if ($zip->open($tmpfile) !== true) {
                $result_msg  = 'ZIPファイルを開けませんでした。';
                $result_type = 'danger';
            } else {
                $restore_db = isset($_POST['restore_db']) && $_POST['restore_db'] === '1';

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);

                    // パストラバーサル防止
                    if (strpos($entry, '..') !== false || strpos($entry, "\0") !== false) {
                        $skipped[] = $entry . ' (不正なパス)';
                        continue;
                    }

                    $dest = $base . '/' . $entry;

                    // 許可チェック
                    $allowed = false;
                    if (in_array($entry, $allowed_files)) {
                        $allowed = true;
                    } elseif ($restore_db) {
                        // DBは拡張子 .db かつ / を含まないファイル名のみ
                        if (!strpos($entry, '/') && substr($entry, -3) === '.db') {
                            $allowed = true;
                        }
                    }
                    if (!$allowed && strpos($entry, $allowed_prefix_images) === 0) {
                        $basename = basename($entry);
                        // 画像ファイルのみ許可
                        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $basename)) {
                            $allowed = true;
                        }
                    }

                    if (!$allowed) {
                        $skipped[] = htmlspecialchars($entry);
                        continue;
                    }

                    // 書き込み先ディレクトリを作成
                    $destdir = dirname($dest);
                    if (!is_dir($destdir)) {
                        @mkdir($destdir, 0777, true);
                    }

                    // realpath チェック（書き込み前にディレクトリが確定してから）
                    if (!is_dir($destdir) || strpos(realpath($destdir), $base) !== 0) {
                        $skipped[] = htmlspecialchars($entry) . ' (ディレクトリ作成失敗)';
                        continue;
                    }

                    $data = $zip->getFromIndex($i);
                    if ($data === false) {
                        $skipped[] = htmlspecialchars($entry) . ' (読み取り失敗)';
                        continue;
                    }
                    if (file_put_contents($dest, $data) === false) {
                        $skipped[] = htmlspecialchars($entry) . ' (書き込み失敗)';
                        continue;
                    }
                    $restored[] = htmlspecialchars($entry);
                }
                $zip->close();

                if (count($restored) > 0) {
                    $result_msg = '復元しました: ' . implode(', ', $restored);
                    if (count($skipped) > 0) {
                        $result_msg .= '<br>スキップ: ' . implode(', ', $skipped);
                    }
                } else {
                    $result_msg  = '復元できるファイルがZIP内に見つかりませんでした。';
                    $result_type = 'danger';
                    if (count($skipped) > 0) {
                        $result_msg .= '<br>スキップ: ' . implode(', ', $skipped);
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>設定バックアップ復元</title>
<?php print_bs5_head_core(['css/style.css']); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('init.php'); ?>
<div style="height:80px;"></div>
<div class="container my-4">
  <h1>設定バックアップ復元</h1>
  <p class="text-muted">バックアップZIPファイルをアップロードして設定を復元します。</p>

<?php if ($result_msg): ?>
  <div class="alert alert-<?php echo $result_type; ?>"><?php echo $result_msg; ?></div>
<?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label fw-bold">バックアップZIPファイル</label>
          <input type="file" name="backup_zip" class="form-control" accept=".zip" required>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="restore_db" name="restore_db" value="1">
          <label class="form-check-label" for="restore_db">データベース (.db) も復元する</label>
          <div class="form-text text-danger">現在のリクエストデータが上書きされます。</div>
        </div>
        <button type="submit" class="btn btn-warning">復元する</button>
        <a href="init.php" class="btn btn-secondary ms-2">設定画面に戻る</a>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5>復元されるファイル</h5>
      <ul>
        <li><code>config.ini</code> — メイン設定</li>
        <li><code>listerdb_config.ini</code> — りすたーDB設定</li>
        <li><code>search_sort_priority.json</code> — 検索ソート優先度</li>
        <li><code>search_sort_priority_auth.json</code> — 検索ソート優先度（認証）</li>
        <li><code>images/bg/</code> — 背景画像</li>
        <li><code>*.db</code> — データベース（チェック時のみ）</li>
      </ul>
    </div>
  </div>
</div>
<?php print_bg_style_block(true); ?>
</body>
</html>
