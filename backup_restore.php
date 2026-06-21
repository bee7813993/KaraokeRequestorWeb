<?php
/**
 * 設定バックアップ / 復元ページ
 * 管理者専用 (HTTP Basic Auth 必須)
 */
require_once 'commonfunc.php';
require_once 'configauth_class.php';

$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Backup/Restore - admin only"');
    die('認証が必要です');
}
if ($_SERVER['PHP_AUTH_USER'] !== 'admin') {
    header('WWW-Authenticate: Basic realm="Backup/Restore - admin only"');
    die('認証が必要です');
}
if (!$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Backup/Restore - admin only"');
    die('認証が必要です');
}

$zip_ok = zip_create_available();  // バックアップ・復元両方に必要

// ----- バックアップダウンロード処理 -----
if (isset($_GET['action']) && $_GET['action'] === 'download' && $zip_ok) {
    $include_db    = isset($_GET['db'])    && $_GET['db']    === '1';
    $include_bgimg = isset($_GET['bgimg']) && $_GET['bgimg'] === '1';

    $base = __DIR__;
    $files_map = [];

    $candidates = [
        'config.ini',
        'listerdb_config.ini',
        'search_sort_priority.json',
        'search_sort_priority_auth.json',
        'pfwd_forykr/pfwd.ini',
    ];
    foreach ($candidates as $f) {
        if (file_exists($base . '/' . $f)) {
            $files_map[$base . '/' . $f] = $f;
        }
    }

    if ($include_db) {
        $dbname = $config_ini['dbname'] ?? 'request.db';
        if (file_exists($base . '/' . $dbname)) {
            $files_map[$base . '/' . $dbname] = $dbname;
        }
    }

    if ($include_bgimg) {
        $bgdir = $base . '/images/bg';
        if (is_dir($bgdir)) {
            foreach (glob($bgdir . '/*') as $imgfile) {
                if (is_file($imgfile)) {
                    $files_map[$imgfile] = 'images/bg/' . basename($imgfile);
                }
            }
        }
    }

    $tmpfile = tempnam(sys_get_temp_dir(), 'ykbak_') . '.zip';
    $errmsg  = '';
    if (!create_zip_archive($files_map, $tmpfile, $errmsg)) {
        http_response_code(500);
        die('バックアップZIPの作成に失敗しました: ' . htmlspecialchars($errmsg));
    }

    $filename = 'ykari_backup_' . date('Ymd_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpfile));
    header('Pragma: no-cache');
    readfile($tmpfile);
    @unlink($tmpfile);
    exit;
}

// ----- 復元処理 -----
$restore_msg  = '';
$restore_type = 'success';

// 復元を許可するエントリのホワイトリスト
$allowed_files = [
    'config.ini',
    'listerdb_config.ini',
    'search_sort_priority.json',
    'search_sort_priority_auth.json',
    'pfwd_forykr/pfwd.ini',
];
$allowed_images_prefix = 'images/bg/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    if (!$zip_ok) {
        $restore_msg  = 'ZIP機能が利用できないため復元できません。';
        $restore_type = 'danger';
    } elseif (!isset($_FILES['backup_zip']) || $_FILES['backup_zip']['error'] !== UPLOAD_ERR_OK) {
        $restore_msg  = 'ファイルのアップロードに失敗しました (エラーコード: '
                      . ($_FILES['backup_zip']['error'] ?? '不明') . ')。';
        $restore_type = 'danger';
    } else {
        $tmpfile = $_FILES['backup_zip']['tmp_name'];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpfile);
        $allowed_mimes = ['application/zip', 'application/x-zip-compressed',
                          'application/octet-stream', 'application/x-zip'];
        if (!in_array($mime, $allowed_mimes)) {
            $restore_msg  = 'ZIPファイル以外はアップロードできません（検出MIME: '
                          . htmlspecialchars($mime) . '）。';
            $restore_type = 'danger';
        } else {
            // 展開先となる一時ディレクトリ
            $extract_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ykrest_' . uniqid();
            $ex_errmsg   = '';
            if (!extract_zip_archive($tmpfile, $extract_dir, $ex_errmsg)) {
                $restore_msg  = 'ZIPの展開に失敗しました: ' . htmlspecialchars($ex_errmsg);
                $restore_type = 'danger';
            } else {
                $restore_db = isset($_POST['restore_db']) && $_POST['restore_db'] === '1';
                $base       = realpath(__DIR__);
                $restored   = [];
                $skipped    = [];

                // 展開済みディレクトリをスキャン
                $iter = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extract_dir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iter as $file) {
                    $abs     = $file->getPathname();
                    $relpath = ltrim(str_replace('\\', '/', substr($abs, strlen($extract_dir))), '/');

                    // パストラバーサル防止
                    if (strpos($relpath, '..') !== false || strpos($relpath, "\0") !== false) {
                        $skipped[] = htmlspecialchars($relpath) . ' (不正なパス)';
                        continue;
                    }

                    // 許可チェック
                    $allowed = in_array($relpath, $allowed_files);
                    if (!$allowed && strpos($relpath, $allowed_images_prefix) === 0) {
                        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', basename($relpath))) {
                            $allowed = true;
                        }
                    }
                    if (!$allowed && $restore_db) {
                        if (strpos($relpath, '/') === false && substr($relpath, -3) === '.db') {
                            $allowed = true;
                        }
                    }
                    if (!$allowed) {
                        $skipped[] = htmlspecialchars($relpath);
                        continue;
                    }

                    $dest    = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relpath);
                    $destdir = dirname($dest);
                    if (!is_dir($destdir)) @mkdir($destdir, 0777, true);

                    if (!is_dir($destdir) || strpos(realpath($destdir), $base) !== 0) {
                        $skipped[] = htmlspecialchars($relpath) . ' (ディレクトリ作成失敗)';
                        continue;
                    }

                    if (@copy($abs, $dest)) {
                        $restored[] = htmlspecialchars($relpath);
                    } else {
                        $skipped[] = htmlspecialchars($relpath) . ' (書き込み失敗)';
                    }
                }

                // 一時展開ディレクトリを削除
                _kara_update_cleanup($extract_dir);

                if (count($restored) > 0) {
                    $restore_msg = '復元しました: ' . implode(', ', $restored);
                    if (count($skipped) > 0) {
                        $restore_msg .= '<br>スキップ: ' . implode(', ', $skipped);
                    }
                } else {
                    $restore_msg  = '復元できるファイルがZIP内に見つかりませんでした。';
                    $restore_type = 'danger';
                    if (count($skipped) > 0) {
                        $restore_msg .= '<br>スキップ: ' . implode(', ', $skipped);
                    }
                }
            }
        }
    }
}

$dbname = $config_ini['dbname'] ?? 'request.db';
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>設定バックアップ / 復元</title>
<?php print_bs5_head_core(['css/style.css']); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('init.php'); ?>
<div style="height:80px;"></div>
<div class="container my-4" style="max-width:800px;">
  <h1>設定バックアップ / 復元</h1>
  <p class="text-muted">
    設定ファイル一式をZIPでバックアップ・復元します。<br>
    クリーンインストール後の設定復元にご利用ください。
  </p>

<?php if (!$zip_ok): ?>
  <div class="alert alert-warning">
    <strong>ZIP機能が利用できません。</strong><br>
    バックアップ・復元を行うには、以下のいずれかが必要です。
    <ul class="mb-0 mt-2">
      <li>PHP の <code>extension=zip</code> を有効にする（<code>php.ini</code> で設定）</li>
      <li>Windows 環境で PowerShell が利用可能な状態にする</li>
    </ul>
  </div>
<?php endif; ?>

  <!-- バックアップダウンロード -->
  <div class="card mb-4">
    <div class="card-header fw-bold">バックアップのダウンロード</div>
    <div class="card-body">
      <p class="text-muted" style="font-size:0.9rem;">
        以下のファイルを ZIP にまとめてダウンロードします。
      </p>
      <ul style="font-size:0.9rem;" class="mb-3">
        <li><code>config.ini</code> — メイン設定</li>
        <li><code>listerdb_config.ini</code> — りすたーDB設定</li>
        <li><code>search_sort_priority.json</code> — 検索ソート優先度</li>
        <li><code>search_sort_priority_auth.json</code> — 検索ソート優先度（認証）</li>
        <li><code>pfwd_forykr/pfwd.ini</code> — オンライン接続設定</li>
      </ul>
      <form method="get" action="backup_restore.php">
        <input type="hidden" name="action" value="download">
        <div class="form-check mb-2">
          <input type="checkbox" class="form-check-input" id="bak_bgimg" name="bgimg" value="1" checked>
          <label class="form-check-label" for="bak_bgimg">背景画像 (<code>images/bg/</code>) を含める</label>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="bak_db" name="db" value="1">
          <label class="form-check-label" for="bak_db">データベース (<code><?php echo htmlspecialchars($dbname); ?></code>) を含める</label>
          <div class="form-text">リクエスト履歴も含まれます。ファイルサイズが大きくなる場合があります。</div>
        </div>
        <button type="submit" class="btn btn-primary" <?php echo $zip_ok ? '' : 'disabled'; ?>>
          バックアップをダウンロード
        </button>
      </form>
    </div>
  </div>

  <!-- 復元 -->
  <div class="card mb-4">
    <div class="card-header fw-bold">バックアップから復元</div>
    <div class="card-body">
<?php if ($restore_msg): ?>
      <div class="alert alert-<?php echo $restore_type; ?>"><?php echo $restore_msg; ?></div>
<?php endif; ?>
      <p class="text-muted" style="font-size:0.9rem;">
        ダウンロードしたバックアップZIPをアップロードして設定を上書き復元します。
      </p>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="restore">
        <div class="mb-3">
          <label class="form-label fw-bold">バックアップZIPファイル</label>
          <input type="file" name="backup_zip" class="form-control" accept=".zip"
                 <?php echo $zip_ok ? 'required' : 'disabled'; ?>>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="restore_db" name="restore_db" value="1"
                 <?php echo $zip_ok ? '' : 'disabled'; ?>>
          <label class="form-check-label" for="restore_db">データベース (.db) も復元する</label>
          <div class="form-text text-danger">現在のリクエストデータが上書きされます。</div>
        </div>
        <button type="submit" class="btn btn-warning" <?php echo $zip_ok ? '' : 'disabled'; ?>>
          復元する
        </button>
      </form>
    </div>
  </div>

  <a href="init.php" class="btn btn-secondary">設定画面に戻る</a>
</div>
<?php print_bg_style_block(true); ?>
</body>
</html>
