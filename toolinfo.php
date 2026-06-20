<?php
require_once 'commonfunc.php';
require_once 'ipconfig.php';
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

// --- グローバルURL（5秒タイムアウトで1回だけ試行）---
$globalhost       = isset($config_ini['globalhost']) ? urldecode($config_ini['globalhost']) : '';
$online_available = false;
$globalurl        = '';
if (!empty($globalhost) && $config_ini['connectinternet'] == 1) {
    $checkurl = 'http://' . $globalhost;
    $ret = file_get_html_with_retry($checkurl, 2, 3);
    if ($ret !== false) {
        $online_available = true;
    }
    $globalurl = 'http://' . $globalhost . '/';
    if ($config_ini['useeasyauth'] == 1) {
        $globalurl .= '?easypass=' . urlencode($config_ini['useeasyauth_word']);
    }
}

// --- ローカルURL ---
$easypass_q = ($config_ini['useeasyauth'] == 1)
    ? '?easypass=' . urlencode($config_ini['useeasyauth_word'])
    : '';

// ホスト名（localhost は除外）
$http_host      = $_SERVER['HTTP_HOST'];
$host_only      = strtolower(explode(':', $http_host)[0]);
$localname_valid = ($host_only !== 'localhost');
$localhosturl   = $localname_valid ? 'http://' . $http_host . '/' . $easypass_q : '';

// IPv6・ループバックを除外した IPv4 アドレスのみ表示
$server_addr   = $_SERVER['SERVER_ADDR'];
$localip_valid = (strpos($server_addr, ':') === false)   // IPv6除外
              && ($server_addr !== '127.0.0.1');           // ループバック除外
$localipurl    = $localip_valid
    ? 'http://' . $server_addr . '/' . $easypass_q
    : '';

// ホスト名とIPが同じURLになる場合はどちらか一方を無効化
if ($localname_valid && $localip_valid && $localhosturl === $localipurl) {
    $localip_valid = false;
}

$has_local_url = $localname_valid || $localip_valid;

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
.card-header .hd-short { display: none; }
.card-vtab { cursor: pointer; }
@media (min-width: 992px) {
  .card-vtab { height: 100%; }
  .card-vtab .card-header {
    writing-mode: vertical-rl;
    min-height: 5rem;
    padding: .5rem !important;
    justify-content: center;
    user-select: none;
    letter-spacing: .05em;
  }
  .card-vtab .card-header .hd-full { display: none !important; }
  .card-vtab .card-header .hd-short { display: block; }
}
</style>
</head>
<body>
<?php shownavigatioinbar_bs5('toolinfo.php'); ?>

<div class="container py-3">
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

  <!-- lg以上: 3カラム横並び / lg未満: 縦積み -->
  <div class="row g-3 mb-3">

    <!-- オンライン接続URL -->
    <div id="col-online" class="col-12 <?= $online_available ? 'col-lg order-lg-1' : 'col-lg-auto order-lg-last' ?>">
      <div class="<?= $online_available ? 'card' : 'card card-vtab' ?>">
        <div class="card-header fw-bold d-flex align-items-center"
             style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#onlineUrlBody"
             aria-expanded="<?= $online_available ? 'true' : 'false' ?>" aria-controls="onlineUrlBody">
          <span class="hd-full d-flex align-items-center w-100">
            オンライン接続URL
            <span class="badge <?= $online_available ? 'bg-success' : 'bg-secondary' ?> ms-2">
              <?= $online_available ? '接続可' : '未接続' ?>
            </span>
          </span>
          <span class="hd-short">オンライン接続URL</span>
        </div>
        <div id="onlineUrlBody" class="collapse <?= $online_available ? 'show' : '' ?>">
        <div class="card-body">
          <?php if ($online_available): ?>
            <div class="text-center mb-2">
              <div class="qr-wrap mx-auto"><?= qr_img($globalurl, $l_qrsize) ?></div>
            </div>
            <div class="input-group input-group-sm">
              <input type="text" class="form-control url-display"
                     value="<?= htmlspecialchars($globalurl, ENT_QUOTES, 'UTF-8') ?>" readonly>
              <button class="btn btn-outline-secondary btn-sm" type="button"
                      onclick="navigator.clipboard.writeText(<?= json_encode($globalurl) ?>);this.textContent='✓'">
                コピー
              </button>
            </div>
          <?php elseif (!empty($globalhost)): ?>
            <p class="text-muted small mb-0">設定されたホスト（<?= htmlspecialchars($globalhost, ENT_QUOTES, 'UTF-8') ?>）に接続できませんでした。</p>
          <?php else: ?>
            <p class="text-muted small mb-0">オンライン接続URLは設定されていません。管理画面（init.php）で設定できます。</p>
          <?php endif; ?>
        </div>
        </div><!-- /#onlineUrlBody -->
      </div>
    </div>

    <!-- ローカル接続URL -->
    <div id="col-local" class="col-12 <?= $online_available ? 'col-lg-auto order-lg-last' : 'col-lg order-lg-2' ?>">
      <div class="<?= $online_available ? 'card card-vtab' : 'card' ?>">
        <div class="card-header fw-bold d-flex align-items-center"
             style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#localUrlBody"
             aria-expanded="<?= $online_available ? 'false' : 'true' ?>" aria-controls="localUrlBody">
          <span class="hd-full d-flex align-items-center w-100">
            ローカル接続URL <small class="text-muted fw-normal ms-2">同じWiFi内</small>
          </span>
          <span class="hd-short">ローカル接続URL</span>
        </div>
        <div id="localUrlBody" class="collapse <?= !$online_available ? 'show' : '' ?>">
        <div class="card-body">
          <?php if ($has_local_url): ?>
            <!-- QRコード（上部） -->
            <div class="d-flex justify-content-center gap-4 mb-3">
              <?php if ($localname_valid): ?>
                <div class="text-center">
                  <div class="qr-wrap"><?= qr_img($localhosturl, $l_qrsize) ?></div>
                  <div class="small text-muted mt-1">ホスト名</div>
                </div>
              <?php endif; ?>
              <?php if ($localip_valid): ?>
                <div class="text-center">
                  <div class="qr-wrap"><?= qr_img($localipurl, $l_qrsize) ?></div>
                  <div class="small text-muted mt-1">IPアドレス</div>
                </div>
              <?php endif; ?>
            </div>
            <!-- URLコピー（下部） -->
            <?php if ($localname_valid): ?>
              <p class="fw-bold small mb-1">ホスト名</p>
              <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control url-display"
                       value="<?= htmlspecialchars($localhosturl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        onclick="navigator.clipboard.writeText(<?= json_encode($localhosturl) ?>);this.textContent='✓'">
                  コピー
                </button>
              </div>
            <?php endif; ?>
            <?php if ($localip_valid): ?>
              <p class="fw-bold small mb-1">IPアドレス</p>
              <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control url-display"
                       value="<?= htmlspecialchars($localipurl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        onclick="navigator.clipboard.writeText(<?= json_encode($localipurl) ?>);this.textContent='✓'">
                  コピー
                </button>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-info mb-3">
              IPアドレスまたはホスト名を直接指定してアクセスしてください。
            </div>
            <?php print_iplist($config_ini, 'local-ipv6-list'); ?>
          <?php endif; ?>
        </div>
        </div><!-- /#localUrlBody -->
      </div>
    </div>

    <!-- WiFi接続情報 -->
    <div id="col-wifi" class="col-12 <?= !empty($wifi_ssid) ? 'col-lg order-lg-3' : 'col-lg-auto order-lg-last' ?>">
      <div class="<?= !empty($wifi_ssid) ? 'card' : 'card card-vtab' ?>">
        <div class="card-header fw-bold d-flex align-items-center"
             style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#wifiUrlBody"
             aria-expanded="<?= !empty($wifi_ssid) ? 'true' : 'false' ?>" aria-controls="wifiUrlBody">
          <span class="hd-full">WiFi接続情報</span>
          <span class="hd-short">WiFi接続情報</span>
        </div>
        <div id="wifiUrlBody" class="collapse <?= !empty($wifi_ssid) ? 'show' : '' ?>">
        <div class="card-body">
          <!-- QRコード（上部） -->
          <?php if (!empty($wifi_ssid)): ?>
            <div class="text-center mb-3">
              <div class="qr-wrap mx-auto"><?= qr_img($wifi_qr_data, $l_qrsize) ?></div>
              <p class="small text-muted mt-2 mb-0">
                SSID: <strong><?= htmlspecialchars($wifi_ssid, ENT_QUOTES, 'UTF-8') ?></strong><br>
                パスワード: <strong><?= htmlspecialchars($wifi_pass, ENT_QUOTES, 'UTF-8') ?></strong>
              </p>
              <p class="small text-muted mb-0">カメラで読み取るとWiFiに自動接続できます</p>
            </div>
            <hr class="my-3">
          <?php else: ?>
            <p class="text-muted small mb-3">SSIDを入力して保存すると、WiFi自動接続用QRコードが表示されます</p>
          <?php endif; ?>
          <!-- フォーム（下部） -->
          <form method="GET">
            <input type="hidden" name="qrsize" value="<?= $l_qrsize ?>">
            <div class="mb-2">
              <label class="form-label small mb-1">WiFi SSID</label>
              <input type="text" name="SSID" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($wifi_ssid, ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="例: MyWifi">
            </div>
            <div class="mb-2">
              <label class="form-label small mb-1">WiFi パスワード</label>
              <input type="text" name="wifipass" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($wifi_pass, ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="パスワード">
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">保存</button>
          </form>
        </div>
        </div><!-- /#wifiUrlBody -->
      </div>
    </div>

  </div><!-- /row -->

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
<script>
(function () {
  function setupCol(bodyId, colId, expandOrder) {
    var body = document.getElementById(bodyId);
    var col  = document.getElementById(colId);
    if (!body || !col) return;
    var card = col.querySelector('.card');
    body.addEventListener('show.bs.collapse', function () {
      col.classList.remove('col-lg-auto', 'order-lg-last');
      col.classList.add('col-lg', 'order-lg-' + expandOrder);
      if (card) card.classList.remove('card-vtab');
    });
    body.addEventListener('hide.bs.collapse', function () {
      col.classList.remove('col-lg', 'order-lg-' + expandOrder);
      col.classList.add('col-lg-auto', 'order-lg-last');
      if (card) card.classList.add('card-vtab');
    });
  }
  setupCol('onlineUrlBody', 'col-online', 1);
  setupCol('localUrlBody',  'col-local',  2);
  setupCol('wifiUrlBody',   'col-wifi',   3);
})();
</script>
</body>
</html>
