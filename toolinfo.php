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

<?php
// アコーディオン初期開閉状態
$online_open = $online_available;
$local_open  = !$online_available;
$wifi_open   = true;
?>

  <div class="accordion mb-3" id="infoAccordion">

    <!-- オンライン接続URL -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingOnline">
        <button class="accordion-button <?= $online_open ? '' : 'collapsed' ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#collapseOnline"
                aria-expanded="<?= $online_open ? 'true' : 'false' ?>"
                aria-controls="collapseOnline">
          オンライン接続URL
          <?php if ($online_available): ?>
            <span class="badge bg-success ms-2">接続可</span>
          <?php else: ?>
            <span class="badge bg-secondary ms-2">未接続</span>
          <?php endif; ?>
        </button>
      </h2>
      <div id="collapseOnline"
           class="accordion-collapse collapse <?= $online_open ? 'show' : '' ?>"
           aria-labelledby="headingOnline">
        <div class="accordion-body">
          <?php if ($online_available): ?>
            <p class="text-muted small mb-2">WiFiに接続しなくてもアクセスできるURLです</p>
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
          <?php elseif (!empty($globalhost)): ?>
            <p class="text-muted small mb-0">設定されたホスト（<?= htmlspecialchars($globalhost, ENT_QUOTES, 'UTF-8') ?>）に接続できませんでした。</p>
          <?php else: ?>
            <p class="text-muted small mb-0">オンライン接続URLは設定されていません。管理画面（init.php）で設定できます。</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ローカル接続URL -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingLocal">
        <button class="accordion-button <?= $local_open ? '' : 'collapsed' ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#collapseLocal"
                aria-expanded="<?= $local_open ? 'true' : 'false' ?>"
                aria-controls="collapseLocal">
          ローカル接続URL
          <small class="text-muted fw-normal ms-2">同じWiFi内からアクセス</small>
        </button>
      </h2>
      <div id="collapseLocal"
           class="accordion-collapse collapse <?= $local_open ? 'show' : '' ?>"
           aria-labelledby="headingLocal">
        <div class="accordion-body">
          <?php if ($has_local_url): ?>
          <div class="row g-3 justify-content-center">
            <?php if ($localname_valid): ?>
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
            <?php endif; ?>
            <?php if ($localip_valid): ?>
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
            <?php endif; ?>
          </div>
          <?php else: ?>
          <?php /* ホスト名・IPアドレスがいずれも対象外のとき */ ?>
          <div class="alert alert-info mb-3">
            IPアドレスまたはホスト名を直接指定してアクセスしてください。
          </div>
          <?php print_iplist($config_ini, 'local-ipv6-list'); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- WiFi接続情報 -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingWifi">
        <button class="accordion-button <?= $wifi_open ? '' : 'collapsed' ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#collapseWifi"
                aria-expanded="<?= $wifi_open ? 'true' : 'false' ?>"
                aria-controls="collapseWifi">
          WiFi接続情報
        </button>
      </h2>
      <div id="collapseWifi"
           class="accordion-collapse collapse <?= $wifi_open ? 'show' : '' ?>"
           aria-labelledby="headingWifi">
        <div class="accordion-body">
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
    </div>

  </div><!-- /accordion -->

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
