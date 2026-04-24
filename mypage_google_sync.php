<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
require_once 'mypage_google_drive.php';
print_meta_header();
?>
<title>Google同期 - マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

global $config_ini, $db;
$relay_url    = $config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php';
$relay_secret = $config_ini['google_relay_secret'] ?? '';
$client_id    = $config_ini['google_client_id'] ?? '';
$is_configured = (!empty($client_id) && !empty($relay_secret));

$mypage = new MypageUser($db);
$link   = $mypage->getGoogleLink();

$msg      = '';
$msg_type = 'success';

// エラー / 完了メッセージ
$error_map = [
    'not_configured' => 'Google同期が設定されていません。管理者に設定を依頼してください。',
    'no_payload'     => 'コールバックパラメーターがありません。',
    'invalid_payload'=> 'コールバックデータが不正です。',
    'hmac_mismatch'  => '署名の検証に失敗しました。',
    'payload_expired'=> 'コールバックの有効期限が切れています。再度お試しください。',
    'nonce_mismatch' => 'セキュリティトークンが一致しません。再度お試しください。',
    'missing_token'  => 'Googleからトークンを取得できませんでした。',
    'sync_failed'    => '同期に失敗しました。しばらく経ってから再度お試しください。',
    'unlinked'       => 'Google連携を解除しました。',
];
if (!empty($_GET['error'])) {
    $msg      = $error_map[$_GET['error']] ?? ('エラーが発生しました: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
    $msg_type = 'danger';
} elseif (!empty($_GET['linked'])) {
    $msg = 'Googleアカウントと連携しました。';
} elseif (!empty($_GET['synced'])) {
    $msg = '同期が完了しました。';
}

// ---- アクション処理 ----

// 連携解除
if (isset($_POST['action']) && $_POST['action'] === 'unlink') {
    $mypage->unlinkGoogle();
    header('Location: mypage_google_sync.php?error=unlinked');
    exit;
}

// 手動同期（Drive → ローカル merge）
if (isset($_POST['action']) && $_POST['action'] === 'sync_from_drive') {
    $link = $mypage->getGoogleLink();
    if ($link) {
        $drive = new GoogleDriveHelper(
            $link['access_token'],
            $link['refresh_token'],
            $link['token_expires_at'],
            $relay_url,
            $relay_secret
        );
        $drive_data = $drive->readData();
        if ($drive_data) {
            $mypage->importData($drive_data, false);
        }
        [$new_at, $new_exp, $refreshed] = $drive->getNewTokens();
        if ($refreshed) $mypage->updateGoogleTokens($new_at, $new_exp);
        $mypage->updateGoogleSyncTime();
        header('Location: mypage_google_sync.php?synced=1');
        exit;
    }
}

// 手動同期（ローカル → Drive）
if (isset($_POST['action']) && $_POST['action'] === 'sync_to_drive') {
    $link = $mypage->getGoogleLink();
    if ($link) {
        $drive = new GoogleDriveHelper(
            $link['access_token'],
            $link['refresh_token'],
            $link['token_expires_at'],
            $relay_url,
            $relay_secret
        );
        $ok = $drive->writeData($mypage->exportData());
        [$new_at, $new_exp, $refreshed] = $drive->getNewTokens();
        if ($refreshed) $mypage->updateGoogleTokens($new_at, $new_exp);
        if ($ok) {
            $mypage->updateGoogleSyncTime();
            header('Location: mypage_google_sync.php?synced=1');
        } else {
            header('Location: mypage_google_sync.php?error=sync_failed');
        }
        exit;
    }
}

$link = $mypage->getGoogleLink(); // 最新状態を再取得
?>
<div class="container" style="margin-top:80px;">
  <h2>Google同期</h2>
  <p><a href="mypage.php">&laquo; マイページに戻る</a></p>

  <?php if ($msg): ?>
  <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <?php if (!$is_configured): ?>
  <div class="alert alert-warning">
    Google同期は設定されていません。<br>
    管理者は <a href="init.php">設定画面</a> で Google Client ID と中継シークレットを設定してください。
  </div>
  <?php elseif (!$link): ?>
  <!-- 未連携 -->
  <div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">Googleアカウント連携</h4></div>
    <div class="panel-body">
      <p>Googleアカウントと連携すると、マイページデータ（選曲履歴・お気に入りなど）をクラウドに保存し、複数の端末・サーバーで共有できます。</p>
      <a href="mypage_google_auth.php" class="btn btn-danger btn-lg">
        <span class="glyphicon glyphicon-link"></span>
        Googleアカウントで連携する
      </a>
    </div>
  </div>
  <?php else: ?>
  <!-- 連携済み -->
  <div class="panel panel-success">
    <div class="panel-heading"><h4 class="panel-title">連携済み</h4></div>
    <div class="panel-body">
      <p>
        <strong>メールアドレス:</strong>
        <?php echo htmlspecialchars($link['google_email'], ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php if ($link['last_synced_at'] > 0): ?>
      <p>
        <strong>最終同期:</strong>
        <?php echo date('Y/m/d H:i:s', $link['last_synced_at']); ?>
      </p>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <form method="POST" action="mypage_google_sync.php" style="display:inline;">
          <input type="hidden" name="action" value="sync_to_drive" />
          <button type="submit" class="btn btn-primary">
            <span class="glyphicon glyphicon-upload"></span>
            このサーバーのデータをDriveに保存
          </button>
        </form>
        &nbsp;
        <form method="POST" action="mypage_google_sync.php" style="display:inline;">
          <input type="hidden" name="action" value="sync_from_drive" />
          <button type="submit" class="btn btn-default">
            <span class="glyphicon glyphicon-download-alt"></span>
            DriveのデータをこのサーバーにMerge
          </button>
        </form>
      </div>

      <hr>
      <form method="POST" action="mypage_google_sync.php"
            onsubmit="return confirm('Google連携を解除します。Driveのデータは削除されません。よろしいですか？');">
        <input type="hidden" name="action" value="unlink" />
        <button type="submit" class="btn btn-danger btn-sm">
          <span class="glyphicon glyphicon-remove"></span>
          連携を解除する
        </button>
      </form>
    </div>
  </div>

  <div class="alert alert-info">
    <strong>ヒント:</strong>
    別のサーバー・端末でも同じGoogleアカウントで連携すると、「DriveのデータをMerge」でデータを取り込めます。
  </div>
  <?php endif; ?>

</div>
</body>
</html>
