<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
$mypage = null;
$msg = '';
$msg_type = 'info';
$pair_code = '';
if (configbool("usemypage", true)) {
    $mypage = new MypageUser($db);
    // コード発行
    if (isset($_POST['action']) && $_POST['action'] === 'generate') {
        $pair_code = $mypage->generatePairingCode();
    }
    // コード適用（applyPairingCode() が setcookie() を呼ぶため HTML 出力前に処理）
    if (isset($_POST['action']) && $_POST['action'] === 'apply') {
        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        if (empty($code)) {
            $msg = 'コードを入力してください。';
            $msg_type = 'danger';
        } else {
            $result = $mypage->applyPairingCode($code);
            if ($result) {
                $msg = 'デバイスのリンクに成功しました。マイページのデータが引き継がれました。';
                $msg_type = 'success';
            } else {
                $msg = 'コードが無効または有効期限切れです。もう一度お試しください（コードの有効期限は5分です）。';
                $msg_type = 'danger';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>デバイスリンク - マイページ</title>
<?php print_bs5_head_core(); ?>
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }</style>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage_link_device.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}
// $mypage/$msg/$msg_type/$pair_code は冒頭の PHP ブロックで設定済み
?>
<div class="container py-3">
  <h2 class="mb-2">デバイスリンク</h2>
  <p class="mb-3"><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if ($msg): ?>
  <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- コード発行 (このデバイスのデータを新端末に引き継がせる) -->
    <div class="col-12 col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">このデバイスのデータを別端末に引き継ぐ</h5>
        </div>
        <div class="card-body">
          <p>「コードを発行」を押すと6文字のコードが表示されます。<br>
            新しい端末でそのコードを入力してください。<br>
            <strong>コードの有効期限は5分です。</strong>
          </p>
          <form method="POST" action="mypage_link_device.php">
            <input type="hidden" name="action" value="generate" />
            <button type="submit" class="btn btn-primary">コードを発行</button>
          </form>
          <?php if (!empty($pair_code)): ?>
          <div class="alert alert-secondary text-center mt-3 mb-1" style="font-size:2em; letter-spacing:0.3em;">
            <strong><?php echo htmlspecialchars($pair_code, ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <p class="text-muted small">新端末で上記コードを入力してください（5分以内）。</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- コード入力 (別端末のデータをこのデバイスに引き継ぐ) -->
    <div class="col-12 col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">別端末のデータをこのデバイスに引き継ぐ</h5>
        </div>
        <div class="card-body">
          <p>別端末で発行したコードをここに入力してください。<br>
            入力後、この端末のマイページが別端末のデータに切り替わります。
          </p>
          <form method="POST" action="mypage_link_device.php">
            <input type="hidden" name="action" value="apply" />
            <div class="mb-3">
              <input type="text" name="code" class="form-control"
                     maxlength="6" placeholder="XXXXXX"
                     style="font-size:1.5em; letter-spacing:0.3em; text-transform:uppercase;"
                     autocomplete="off" />
            </div>
            <button type="submit" class="btn btn-success">引き継ぐ</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-4">
  <p class="text-muted small">
    ※ 引き継ぎ後、古い端末のデータ（履歴・お気に入り等）は引き継ぎ元のデータに統合されません。
    引き継ぎ先のユーザーIDに切り替わります。
  </p>
</div>
</body>
</html>
