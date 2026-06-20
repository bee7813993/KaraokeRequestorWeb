<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

// --- フォーム処理 ---
$change_counter = 0;
foreach (['SSID', 'wifipass', 'globalhost'] as $_key) {
    if (isset($_REQUEST[$_key])) {
        $config_ini[$_key] = urlencode(trim($_REQUEST[$_key]));
        $change_counter++;
    }
}
if ($change_counter > 0) {
    writeconfig2ini($config_ini, $configfile);
}

// --- QRサイズ ---
$l_qrsize = 8;
if (isset($_REQUEST['qrsize'])) {
    $l_qrsize = max(1, min(16, (int)$_REQUEST['qrsize']));
}

// --- グローバルURL ---
$globalhost      = isset($config_ini['globalhost']) ? urldecode($config_ini['globalhost']) : '';
$online_available = false;
$globalurl       = '';
if (!empty($globalhost)) {
    if (check_online_available($globalhost) === 'OK') {
        $online_available = true;
    }
    $globalurl = 'http://' . $globalhost . '/';
    if ($config_ini['useeasyauth'] == 1) {
        $globalurl .= '?easypass=' . urlencode($config_ini['useeasyauth_word']);
    }
}

// --- ローカルURL ---
$server_addr = $_SERVER['SERVER_ADDR'];
$server_addr_url = (strpos($server_addr, ':') !== false)
    ? addipv6blanket($server_addr)
    : $server_addr;
$localhosturl = 'http://' . $_SERVER['HTTP_HOST'] . '/';
$localipurl   = 'http://' . $server_addr_url . '/';
if ($config_ini['useeasyauth'] == 1) {
    $easypass_q    = '?easypass=' . urlencode($config_ini['useeasyauth_word']);
    $localhosturl .= $easypass_q;
    $localipurl   .= $easypass_q;
}

// --- WiFi情報 ---
$wifi_ssid = isset($config_ini['SSID'])    ? urldecode($config_ini['SSID'])    : '';
$wifi_pass = isset($config_ini['wifipass']) ? urldecode($config_ini['wifipass']) : '';

function wifi_qr_escape(string $s): string {
    return str_replace(
        ['\\',   ';',   ',',   '"',   ':'],
        ['\\\\', '\\;', '\\,', '\\"', '\\:'],
        $s
    );
}
$wifi_qr_data = 'WIFI:T:WPA;S:' . wifi_qr_escape($wifi_ssid)
              . ';P:'            . wifi_qr_escape($wifi_pass) . ';;';

// --- QR img タグ出力ヘルパー ---
function qr_img(string $data, int $size): string {
    $src = 'qrcode_php/outputqrsvg.php?data=' . urlencode($data) . '&qrsize=' . $size;
    return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
         . ' alt="QRコード" class="img-fluid d-block mx-auto" style="max-width:320px">';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>接続情報</title>
<?php print_bs5_search_head(); ?>
<style>
.qr-wrap { background:#fff; display:inline-block; padding:4px; border-radius:4px; }
.url-display { font-family: monospace; font-size:.85rem; word-break:break-all; }
</style>
</head>
<body>
<?php shownavigatioinbar_bs5('toolinfo.php'); ?>

<div class="container py-3" style="max-width:800px">
  <h4 class="mb-3">接続情報</h4>

<?php if ($config_ini['useeasyauth'] == 1): ?>
  <!-- 認証キーワード -->
  <div class="card mb-3">
    <div class="card-header fw-bold">認証キーワード</div>
    <div class="card-body">
      <p class="text-muted small mb-2">接続時に入力が必要なキーワードです</p>
      <input type="text" class="form-control form-control-lg fw-bold text-center"
             value="<?= htmlspecialchars($config_ini['useeasyauth_word'], ENT_QUOTES, 'UTF-8') ?>" readonly>
    </div>
  </div>
<?php endif; ?>

  <!-- WiFi接続情報 -->
  <div class="card mb-3">
    <div class="card-header fw-bold">WiFi接続情報</div>
    <div class="card-body">
      <form method="GET" class="mb-3">
        <input type="hidden" name="qrsize" value="<?= $l_qrsize ?>">
        <div class="row g-3 align-items-end">
          <div class="col-sm-5">
            <label class="form-label">WiFi SSID</label>
            <input type="text" name="SSID" class="form-control"
                   value="<?= htmlspecialchars($wifi_ssid, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="例: MyWifi">
          </div>
          <div class="col-sm-5">
            <label class="form-label">WiFi パスワード</label>
            <input type="text" name="wifipass" class="form-control"
                   value="<?= htmlspecialchars($wifi_pass, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="パスワード">
          </div>
          <div class="col-sm-2">
            <button type="submit" class="btn btn-primary w-100">保存</button>
          </div>
        </div>
      </form>

<?php if (!empty($wifi_ssid)): ?>
      <hr class="my-2">
      <div class="text-center mt-2">
        <div class="qr-wrap mx-auto">
          <?= qr_img($wifi_qr_data, $l_qrsize) ?>
        </div>
        <p class="small text-muted mt-2 mb-0">
          SSID: <strong><?= htmlspecialchars($wifi_ssid, ENT_QUOTES, 'UTF-8') ?></strong>
          &nbsp;/&nbsp;
          パスワード: <strong><?= htmlspecialchars($wifi_pass, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <p class="small text-muted">スマートフォンのカメラで読み取るとWiFiに自動接続できます</p>
      </div>
<?php else: ?>
      <p class="text-muted small mb-0">SSIDを入力して保存すると、WiFi自動接続用QRコードが表示されます</p>
<?php endif; ?>
    </div>
  </div>

<?php if ($online_available): ?>
  <!-- オンライン接続URL -->
  <div class="card mb-3 border-success">
    <div class="card-header fw-bold text-success">
      オンライン接続URL
      <small class="text-muted fw-normal">（WiFiなしでもアクセス可）</small>
    </div>
    <div class="card-body">
      <div class="input-group mb-3">
        <input type="text" class="form-control url-display"
               value="<?= htmlspecialchars($globalurl, ENT_QUOTES, 'UTF-8') ?>" readonly>
        <button class="btn btn-outline-secondary" type="button"
                onclick="navigator.clipboard.writeText(<?= json_encode($globalurl) ?>);this.textContent='コピー済'">
          コピー
        </button>
      </div>
      <div class="text-center">
        <div class="qr-wrap mx-auto">
          <?= qr_img($globalurl, $l_qrsize) ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

  <!-- ローカル接続URL -->
  <div class="card mb-3">
    <div class="card-header fw-bold">
      ローカル接続URL
      <small class="text-muted fw-normal">（同じWiFi内からアクセス）</small>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <p class="fw-bold small mb-2">ホスト名</p>
              <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control url-display"
                       value="<?= htmlspecialchars($localhosturl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        onclick="navigator.clipboard.writeText(<?= json_encode($localhosturl) ?>);this.textContent='✓'">
                  コピー
                </button>
              </div>
              <div class="qr-wrap mx-auto">
                <?= qr_img($localhosturl, $l_qrsize) ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <p class="fw-bold small mb-2">IPアドレス</p>
              <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control url-display"
                       value="<?= htmlspecialchars($localipurl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        onclick="navigator.clipboard.writeText(<?= json_encode($localipurl) ?>);this.textContent='✓'">
                  コピー
                </button>
              </div>
              <div class="qr-wrap mx-auto">
                <?= qr_img($localipurl, $l_qrsize) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- QRサイズ切り替え -->
  <div class="d-flex gap-2 justify-content-center mb-4">
    <span class="text-muted small align-self-center">QRサイズ:</span>
    <a href="?qrsize=5"  class="btn btn-sm <?= $l_qrsize == 5  ? 'btn-secondary' : 'btn-outline-secondary' ?>">小</a>
    <a href="?qrsize=8"  class="btn btn-sm <?= $l_qrsize == 8  ? 'btn-secondary' : 'btn-outline-secondary' ?>">標準</a>
    <a href="?qrsize=12" class="btn btn-sm <?= $l_qrsize == 12 ? 'btn-secondary' : 'btn-outline-secondary' ?>">大</a>
  </div>

  <div class="text-center">
    <a href="requestlist_top.php" class="btn btn-secondary">リクエストTOPに戻る</a>
  </div>
</div>

<?php print_bg_style_block(true); ?>
</body>
</html>
