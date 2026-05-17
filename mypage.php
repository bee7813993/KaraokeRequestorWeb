<!doctype html>
<html lang="ja">
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>マイページ</title>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<link href="css/themes/_variables.css" rel="stylesheet">
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; }</style>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php shownavigatioinbar_bs5('mypage.php'); ?>

<?php
if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);
$displayname = $mypage->getDisplayName();

$msg = '';
$msg_type = 'success';
if (isset($_POST['action']) && $_POST['action'] === 'update_name') {
    $newname = isset($_POST['displayname']) ? $_POST['displayname'] : '';
    if ($newname !== '') {
        $mypage->updateDisplayName($newname);
        $displayname = htmlspecialchars(mb_substr(trim($newname), 0, 64), ENT_QUOTES, 'UTF-8');
        $msg = '表示名を更新しました。';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'update_icon') {
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
        $result = $mypage->updateIconPath($_FILES['icon_file']);
        if ($result) {
            $msg = 'アイコンを更新しました。';
        } else {
            $msg = 'アイコンの更新に失敗しました。画像ファイル（JPEG/PNG/GIF/SVG/WebP）を選択してください。';
            $msg_type = 'danger';
        }
    } else {
        $msg = '画像ファイルを選択してください。';
        $msg_type = 'warning';
    }
}

$icon_path = $mypage->getIconPath();
?>

<div class="container py-3">
  <h2 class="mb-3">マイページ</h2>

  <?php if ($msg): ?>
  <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible" role="alert">
    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">あなたの情報</h5></div>
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-12 col-sm-3 col-md-2 text-center mb-2 mb-sm-0">
          <img src="<?php echo htmlspecialchars($icon_path, ENT_QUOTES, 'UTF-8'); ?>"
               alt="マイページアイコン"
               style="width:80px;height:80px;border-radius:50%;border:2px solid #ddd;object-fit:cover;" />
        </div>
        <div class="col-12 col-sm-9 col-md-10">
          <form method="POST" action="mypage.php" class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <input type="hidden" name="action" value="update_name" />
            <label class="form-label mb-0">表示名:</label>
            <input type="text" name="displayname" class="form-control"
                   style="max-width:240px;"
                   value="<?php echo htmlspecialchars($displayname, ENT_QUOTES, 'UTF-8'); ?>"
                   maxlength="64" placeholder="名前を入力" />
            <button type="submit" class="btn btn-outline-secondary btn-sm">変更</button>
          </form>
          <p class="text-muted small mb-0">
            ユーザーID: <?php echo htmlspecialchars($mypage->getUserId(), ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">アイコン変更</h5></div>
    <div class="card-body">
      <form method="POST" action="mypage.php" enctype="multipart/form-data"
            class="d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="action" value="update_icon" />
        <input type="file" name="icon_file" accept="image/*" class="form-control" style="max-width:280px;" />
        <button type="submit" class="btn btn-outline-secondary btn-sm">アイコンを更新</button>
      </form>
      <p class="text-muted small mt-2 mb-0">
        JPEG / PNG / GIF / SVG / WebP が使用できます。
      </p>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-md-3">
      <a href="mypage_history.php" class="btn btn-primary btn-lg w-100">選曲履歴</a>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <a href="mypage_later.php" class="btn btn-success btn-lg w-100">後で歌う</a>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <a href="mypage_favorite_song.php" class="btn btn-warning btn-lg w-100">お気に入り曲</a>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
      <a href="mypage_favorite_keyword.php" class="btn btn-info btn-lg w-100">お気に入り検索ワード</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">データのバックアップ / 復元</h5></div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2">
        <a href="mypage_export.php" class="btn btn-outline-secondary">
          &#x2B07; エクスポート（JSONダウンロード）
        </a>
        <a href="mypage_import.php" class="btn btn-outline-secondary">
          &#x2B06; インポート
        </a>
      </div>
      <p class="text-muted small mt-2 mb-0">
        エクスポートで選曲履歴・お気に入り等をJSONファイルとして保存できます。<br>
        インポートで別の端末やバックアップからデータを復元できます。
      </p>
    </div>
  </div>

  <?php
  global $config_ini;
  $google_link_row = null;
  if (configbool("usemypage", true)) {
      $google_link_row = $mypage->getGoogleLink();
  }
  $google_configured = (!empty($config_ini['google_client_id']) && !empty($config_ini['google_relay_secret']));
  if ($google_configured):
  ?>
  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Google同期</h5></div>
    <div class="card-body">
      <?php if ($google_link_row): ?>
      <p class="mb-2">
        &#x2713; Googleアカウント（<?php echo htmlspecialchars($google_link_row['google_email'], ENT_QUOTES, 'UTF-8'); ?>）と連携中
      </p>
      <?php else: ?>
      <p class="text-muted mb-2">未連携</p>
      <?php endif; ?>
      <a href="mypage_google_sync.php" class="btn btn-outline-secondary btn-sm">
        &#x2601; Google同期の設定
      </a>
    </div>
  </div>
  <?php endif; ?>

  <hr>
  <p>
    <a href="mypage_link_device.php">別の端末でも同じマイページを使う（デバイスリンク）</a>
  </p>
</div>
</body>
</html>
