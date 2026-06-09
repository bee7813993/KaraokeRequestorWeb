<?php
// setcookie()/header() は HTML 出力前に呼ぶ必要があるため、
// MypageUser の初期化と POST ハンドラ・リダイレクトをすべてここで処理する。
require_once 'commonfunc.php';
require_once 'mypage_class.php';
require_once 'mypage_google_drive.php';

$mypage = null;
$link   = null;
$msg      = '';
$msg_type = 'success';
$is_configured = false;
$relay_url    = '';

if (configbool("usemypage", true)) {
    global $config_ini, $db;
    $relay_url    = $config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php';
    $relay_secret = $config_ini['google_relay_secret'] ?? '';
    $client_id    = $config_ini['google_client_id'] ?? '';
    $is_configured = (!empty($client_id) && !empty($relay_secret));

    $mypage = new MypageUser($db);
    $link   = $mypage->getGoogleLink();

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

    // 自動同期 オン/オフ切り替え
    if (isset($_POST['action']) && $_POST['action'] === 'set_auto_sync') {
        $mypage->setGoogleAutoSync(!empty($_POST['auto_sync']));
        header('Location: mypage_google_sync.php');
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
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>Google同期 - マイページ</title>
<?php print_bs5_head_core(); ?>
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }</style>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}
// $mypage/$link/$msg/$msg_type/$is_configured は冒頭の PHP ブロックで設定済み
?>
<div class="container py-3">
  <h2 class="mb-2">Google同期</h2>
  <p class="mb-3"><a href="mypage.php">&laquo; マイページへ戻る</a></p>

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
  <div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Googleアカウント連携</h5></div>
    <div class="card-body">
      <p>Googleアカウントと連携すると、マイページデータ（選曲履歴・お気に入りなど）をクラウドに保存し、複数の端末・サーバーで共有できます。</p>
      <a href="mypage_google_auth.php" class="btn btn-danger btn-lg">
        Googleアカウントで連携する
      </a>
    </div>
  </div>
  <?php else: ?>
  <!-- 連携済み -->
  <div class="card border-success mb-3">
    <div class="card-header text-bg-success"><h5 class="card-title mb-0">連携済み</h5></div>
    <div class="card-body">
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

      <div class="d-flex flex-wrap gap-2 mt-3">
        <form method="POST" action="mypage_google_sync.php">
          <input type="hidden" name="action" value="sync_to_drive" />
          <button type="submit" class="btn btn-primary">
            このサーバーのデータをDriveに保存
          </button>
        </form>
        <form method="POST" action="mypage_google_sync.php">
          <input type="hidden" name="action" value="sync_from_drive" />
          <button type="submit" class="btn btn-outline-secondary">
            DriveのデータをこのサーバーにMerge
          </button>
        </form>
      </div>

      <div class="mt-3">
        <form method="POST" action="mypage_google_sync.php">
          <input type="hidden" name="action" value="set_auto_sync" />
          <?php if (!empty($link['auto_sync'])): ?>
            <p><span class="badge text-bg-success">自動同期: ON</span>
            &nbsp;お気に入り・検索ワードを追加・削除するたびに自動でDriveに保存されます。</p>
            <button type="submit" class="btn btn-outline-secondary btn-sm">自動同期を無効にする</button>
          <?php else: ?>
            <p><span class="badge text-bg-secondary">自動同期: OFF</span></p>
            <button type="submit" name="auto_sync" value="1" class="btn btn-success btn-sm">
              自動同期を有効にする
            </button>
            <p class="text-muted small mt-2">
              有効にすると、お気に入り・検索ワードの追加・削除のたびに自動でDriveに保存されます。
            </p>
          <?php endif; ?>
        </form>
      </div>

      <hr>
      <form method="POST" action="mypage_google_sync.php"
            onsubmit="return confirm('Google連携を解除します。Driveのデータは削除されません。よろしいですか？');">
        <input type="hidden" name="action" value="unlink" />
        <button type="submit" class="btn btn-danger btn-sm">
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
