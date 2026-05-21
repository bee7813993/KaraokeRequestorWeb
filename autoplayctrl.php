<?php
require_once 'commonfunc.php';

// --- pfwd 情報を先行初期化（AJAX ハンドラでも使用するため） ---
require_once 'pfwdctl.php';
$pfwdavailable = false;
$pfwdinfo = new pfwd();
if (array_key_exists('pfwdplace', $config_ini) && !empty($config_ini['pfwdplace'])) {
    $pfwdinfo->pfwdpath = urldecode($config_ini['pfwdplace']);
    ob_start();
    $pfwdavailable = $pfwdinfo->readpfwdcfg();
    ob_end_clean();
}

// --- AJAX: 接続診断（IPv4/IPv6/TCP を個別に試行して原因を特定する） ---
if (isset($_GET['action']) && $_GET['action'] === 'check_online_debug') {
    header('Content-Type: application/json; charset=utf-8');

    $host_raw = array_key_exists('globalhost', $config_ini) && !empty($config_ini['globalhost'])
        ? urldecode($config_ini['globalhost']) : '';
    // host:port を分割
    $host_parts = explode(':', $host_raw, 2);
    $h_host = $host_parts[0];
    $h_port = isset($host_parts[1]) ? (int)$host_parts[1] : 80;
    $http_url = 'http://' . $host_raw;
    $timeout  = 8;

    $results = [];

    // 1. DNS: IPv4 アドレス解決
    $ipv4 = @gethostbyname($h_host);
    $results['dns_v4'] = ($ipv4 !== $h_host) ? $ipv4 : '解決失敗';

    // 2. DNS: IPv6 アドレス解決
    $aaaa = @dns_get_record($h_host, DNS_AAAA);
    $results['dns_v6'] = (!empty($aaaa)) ? $aaaa[0]['ipv6'] : '解決失敗(またはAAAAなし)';

    // 3. TCP fsockopen (IPv4 解決済みアドレス直接)
    if ($ipv4 !== $h_host) {
        $fp = @fsockopen($ipv4, $h_port, $e, $es, $timeout);
        if ($fp) { fclose($fp); $results['tcp_v4_direct'] = "OK ({$ipv4}:{$h_port})"; }
        else { $results['tcp_v4_direct'] = "NG ({$e}): {$es}"; }
    } else {
        $results['tcp_v4_direct'] = 'スキップ(DNS解決失敗)';
    }

    // 4. TCP fsockopen (ホスト名・OS任せ)
    $fp = @fsockopen($h_host, $h_port, $e, $es, $timeout);
    if ($fp) { fclose($fp); $results['tcp_hostname'] = "OK"; }
    else { $results['tcp_hostname'] = "NG ({$e}): {$es}"; }

    // 5. curl: IPRESOLVE 指定なし（OS が IPv4/IPv6 を自動選択）
    $ch = curl_init($http_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => false, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'KaraokeRequestor/1.0',
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ip   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    $en   = curl_errno($ch); $es2  = curl_strerror($en);
    curl_close($ch);
    $results['curl_auto'] = $code > 0
        ? "OK HTTP {$code} (接続先IP: {$ip})"
        : "NG curl({$en}): {$es2}";

    // 6. curl: IPv4 強制
    $ch = curl_init($http_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => false, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT => 'KaraokeRequestor/1.0',
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ip   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    $en   = curl_errno($ch); $es2  = curl_strerror($en);
    curl_close($ch);
    $results['curl_v4'] = $code > 0
        ? "OK HTTP {$code} (接続先IP: {$ip})"
        : "NG curl({$en}): {$es2}";

    // 7. curl: IPv6 強制
    $ch = curl_init($http_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => false, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V6,
        CURLOPT_USERAGENT => 'KaraokeRequestor/1.0',
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ip   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    $en   = curl_errno($ch); $es2  = curl_strerror($en);
    curl_close($ch);
    $results['curl_v6'] = $code > 0
        ? "OK HTTP {$code} (接続先IP: {$ip})"
        : "NG curl({$en}): {$es2}";

    echo json_encode(['host' => $host_raw, 'timeout' => $timeout, 'results' => $results]);
    exit;
}

// --- AJAX: オンライン接続確認 ---
if (isset($_GET['action']) && $_GET['action'] === 'check_online') {
    header('Content-Type: application/json; charset=utf-8');

    $internet_enabled = array_key_exists('connectinternet', $config_ini) && $config_ini['connectinternet'] == 1;
    $host_configured  = array_key_exists('globalhost', $config_ini) && !empty($config_ini['globalhost']);

    if (!$internet_enabled) {
        echo json_encode(['status' => 'disabled', 'host' => '', 'check_url' => '',
            'detail' => 'インターネット接続設定が無効 (connectinternet=0)']);
        exit;
    }
    if (!$host_configured) {
        echo json_encode(['status' => 'disabled', 'host' => '', 'check_url' => '',
            'detail' => 'オンライン接続用ホスト (globalhost) が未設定']);
        exit;
    }

    $host      = urldecode($config_ini['globalhost']);
    $check_url = 'http://' . $host;
    $timeout   = (int)(array_key_exists('onlinechecktimeout', $config_ini) ? $config_ini['onlinechecktimeout'] : 5);
    if ($timeout < 5) $timeout = 5;

    // ykr.moe は IPv6 のみのため IPRESOLVE_V4 を強制しない。
    // OS に IPv4/IPv6 の選択を任せることで IPv6 only ホストにも対応する。
    // FOLLOWLOCATION=false: 3xx が返った時点で到達確認済みとする（リダイレクト先まで追わない）
    $ch = curl_init($check_url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KaraokeRequestor/1.0');
    curl_exec($ch);
    $curl_errno  = curl_errno($ch);
    $curl_error  = curl_strerror($curl_errno);
    $http_code   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $primary_ip  = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    curl_close($ch);

    if ($http_code > 0) {
        $status = 'ok';
        $detail = "HTTP {$http_code} [{$primary_ip}] (timeout:{$timeout}s)";
    } else {
        $status = 'ng';
        $detail = "curl({$curl_errno}): {$curl_error} (timeout:{$timeout}s)";
    }

    echo json_encode(['status' => $status, 'host' => $host, 'check_url' => $check_url, 'detail' => $detail]);
    exit;
}

// --- 自動再生制御関数 ---
function stopautoplay()
{
    exec('taskkill /FI "WINDOWTITLE eq karaokeautorun*"');
}

function startautoplay()
{
    if (checkautoplay() == 0) {
        global $config_ini;
        $execcmd = 'start "karaokeautorun" ' . urldecode($config_ini['autoplay_exec']);
        exec($execcmd);
    }
}

function checkautoplay()
{
    exec('tasklist /FI "WINDOWTITLE eq karaokeautorun*" | find /c ".exe"', $psresult);
    return (int)($psresult[0] ?? 0);
}

function stopautoplaywithcheck()
{
    if (checkautoplay() != 0) {
        stopautoplay();
    }
}

// --- WireGuard 確認 ---
function check_wireguard_running()
{
    exec('tasklist /fi "imagename eq wireguard.exe" 2>nul', $result);
    foreach ($result as $line) {
        if (stripos($line, 'wireguard.exe') !== false) {
            return true;
        }
    }
    return false;
}

// --- アクション処理 ---
$l_karaokeautorunaction = $_REQUEST['karaokeautorunaction'] ?? 'none';
$l_nextpage = $_REQUEST['nextpage'] ?? null;

if ($l_karaokeautorunaction === 'start') {
    $org_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 3);
    @file_get_contents('http://localhost/autoplayctrl.php?karaokeautorunaction=start_exec');
    ini_set('default_socket_timeout', $org_timeout);
}
if ($l_karaokeautorunaction === 'start_exec') {
    startautoplay();
}
if ($l_karaokeautorunaction === 'stop') {
    stopautoplaywithcheck();
}

// --- ステータス取得 ---
$pfwd_running = $pfwdavailable ? $pfwdinfo->statpfwdcmd() : false;
$ap           = checkautoplay();
$wg_running   = check_wireguard_running();

$globalhost_display = '';
if (array_key_exists('globalhost', $config_ini) && !empty($config_ini['globalhost'])) {
    $globalhost_display = urldecode($config_ini['globalhost']);
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php if (!empty($l_nextpage)): ?>
<meta http-equiv="refresh" content="1; url=<?= htmlspecialchars($l_nextpage, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<title>自動起動プログラム制御</title>
<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}})();</script>
<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">
<link rel="stylesheet" href="css/themes/_variables.css">
<link rel="stylesheet" href="css/themes/theme-toggle.css">
<link rel="stylesheet" href="css/themes/player.css">
<script src="js/jquery.js"></script>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
<script src="js/theme-toggle.js"></script>
</head>
<body>
<?php shownavigatioinbar_bs5('playerctrl_portal_bs5.php'); ?>

<div class="container" style="max-width:540px; padding-bottom:32px;">

  <h5 class="mb-3">自動起動プログラム制御</h5>

  <!-- 自動再生コントロール -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">自動再生コントロール</div>
    <div class="card-body">
      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="text-muted small">ステータス</span>
        <?php if ($ap != 0): ?>
          <span class="badge bg-success fs-6">実行中</span>
        <?php else: ?>
          <span class="badge bg-secondary fs-6">停止中</span>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2">
        <form method="GET" class="flex-fill">
          <input type="hidden" name="karaokeautorunaction" value="start">
          <button type="submit" class="btn btn-success btn-lg w-100">▶ Start</button>
        </form>
        <form method="GET" class="flex-fill">
          <input type="hidden" name="karaokeautorunaction" value="stop">
          <button type="submit" class="btn btn-danger btn-lg w-100">■ Stop</button>
        </form>
      </div>
    </div>
  </div>

  <!-- 接続ステータス -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">接続ステータス</div>
    <div class="card-body">

      <!-- オンライン接続確認 -->
      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <span class="text-nowrap">オンライン接続:</span>
          <span id="online-status" class="badge bg-secondary">確認中...</span>
          <button type="button" class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="check-online-btn">再確認</button>
        </div>
        <div id="online-detail" class="small text-muted" style="word-break:break-all;">&nbsp;</div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="check-debug-btn">詳細診断</button>
        <div id="debug-result" class="mt-2 d-none">
          <table class="table table-sm table-bordered small mb-0">
            <tbody id="debug-tbody"></tbody>
          </table>
        </div>
      </div>

      <!-- WireGuard トンネル確認 -->
      <div class="d-flex align-items-center gap-2">
        <span class="text-nowrap">WireGuard:</span>
        <?php if ($wg_running): ?>
          <span class="badge bg-success">実行中</span>
        <?php else: ?>
          <span class="badge bg-secondary">停止中</span>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- pfwd (SSH転送) -->
  <?php if ($pfwdavailable): ?>
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">pfwd (SSH転送)</div>
    <div class="card-body">

      <!-- WireGuard 実行中かつ pfwd 停止中: 注意（pfwd が接続元でない可能性） -->
      <div id="pfwd-online-alert" class="alert alert-warning py-2 small <?= ($wg_running && !$pfwd_running) ? '' : 'd-none' ?>" role="alert">
        WireGuard 実行中のためオンライン接続済みです。pfwd は起動しないでください。
      </div>

      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="text-muted small">ステータス</span>
        <span id="pfwdstatus" class="badge fs-6 <?= $pfwd_running ? 'bg-success' : 'bg-secondary' ?>">
          <?= $pfwd_running ? '起動中' : '停止中' ?>
        </span>
      </div>
      <div class="d-flex gap-2">
        <button type="button" id="pfwd-start-btn" class="btn btn-success btn-lg flex-fill"
          <?= ($wg_running && !$pfwd_running) ? 'disabled' : '' ?> onclick="start_pfwdcmd()">起動</button>
        <button type="button" class="btn btn-danger btn-lg flex-fill" onclick="stop_pfwdcmd()">停止</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- プレイヤーへ戻る -->
  <div class="d-grid mt-2">
    <a href="playerctrl_portal_bs5.php" class="btn btn-outline-secondary btn-sm player-refresh-btn">
      プレイヤーコントローラーへ戻る
    </a>
  </div>

</div><!-- /container -->

<?php print_bg_style_block(true); ?>

<script>
var pfwdRunning = <?= $pfwdavailable && $pfwd_running ? 'true' : 'false' ?>;
var wgRunning   = <?= $wg_running ? 'true' : 'false' ?>;

function applyPfwdRestriction(pfwdIsRunning) {
    var startBtn    = document.getElementById('pfwd-start-btn');
    var onlineAlert = document.getElementById('pfwd-online-alert');
    if (!startBtn) return;
    if (wgRunning && !pfwdIsRunning) {
        // WireGuard 実行中で pfwd 未起動 → オンライン接続済みなので pfwd 起動禁止
        startBtn.disabled = true;
        if (onlineAlert) onlineAlert.classList.remove('d-none');
    } else {
        // pfwd 起動中 (pfwd が接続元) または WireGuard 未実行 → 制限なし
        startBtn.disabled = false;
        if (onlineAlert) onlineAlert.classList.add('d-none');
    }
}

function updatePfwdStatus(data) {
    var el = document.getElementById('pfwdstatus');
    if (!el) return;
    pfwdRunning = !!data.pfwdstat;
    if (pfwdRunning) {
        el.textContent = '起動中';
        el.className = 'badge fs-6 bg-success';
    } else {
        el.textContent = '停止中';
        el.className = 'badge fs-6 bg-secondary';
    }
    applyPfwdRestriction(pfwdRunning);
}

function start_pfwdcmd() {
    fetch('pfwd_exec.php?pfwdstart=1')
        .then(function(r) { return r.ok ? fetch('pfwdstat.php') : null; })
        .then(function(r) { return r ? r.json() : null; })
        .then(function(data) { if (data) updatePfwdStatus(data); });
}

function stop_pfwdcmd() {
    fetch('pfwd_exec.php?pfwdstop=1')
        .then(function(r) { return r.ok ? fetch('pfwdstat.php') : null; })
        .then(function(r) { return r ? r.json() : null; })
        .then(function(data) { if (data) updatePfwdStatus(data); });
}

function checkOnline() {
    var el       = document.getElementById('online-status');
    var detailEl = document.getElementById('online-detail');
    if (!el) return;
    el.textContent = '確認中...';
    el.className = 'badge bg-secondary';
    if (detailEl) detailEl.textContent = '確認中...';
    fetch('autoplayctrl.php?action=check_online')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var urlText    = data.check_url ? '確認先: ' + data.check_url : '';
            var detailText = data.detail || '';
            var infoText   = urlText + (detailText ? '  [' + detailText + ']' : '');
            if (data.status === 'ok') {
                el.textContent = 'OK';
                el.className = 'badge bg-success';
            } else if (data.status === 'ng') {
                el.textContent = 'NG';
                el.className = 'badge bg-danger';
            } else {
                el.textContent = '無効';
                el.className = 'badge bg-secondary';
            }
            if (detailEl) detailEl.textContent = infoText || detailText;
        })
        .catch(function() {
            el.textContent = 'エラー';
            el.className = 'badge bg-danger';
            if (detailEl) detailEl.textContent = 'AJAX通信エラー';
        });
}

document.getElementById('check-online-btn').addEventListener('click', checkOnline);

document.getElementById('check-debug-btn').addEventListener('click', function() {
    var btn    = this;
    var box    = document.getElementById('debug-result');
    var tbody  = document.getElementById('debug-tbody');
    btn.disabled = true;
    btn.textContent = '診断中...';
    box.classList.add('d-none');
    tbody.innerHTML = '';
    fetch('autoplayctrl.php?action=check_online_debug')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var labels = {
                dns_v4:       'DNS (IPv4解決)',
                dns_v6:       'DNS (IPv6解決)',
                tcp_v4_direct:'TCP fsockopen IPv4直接',
                tcp_hostname: 'TCP fsockopen ホスト名',
                curl_auto:    'curl (IP自動選択)',
                curl_v4:      'curl (IPv4強制)',
                curl_v6:      'curl (IPv6強制)',
            };
            var row = '<tr><td colspan="2" class="fw-bold">確認先: ' +
                data.host + ' (timeout:' + data.timeout + 's)</td></tr>';
            tbody.innerHTML = row;
            Object.keys(labels).forEach(function(key) {
                var val = data.results[key] || '—';
                var ok  = val.startsWith('OK');
                tbody.innerHTML += '<tr><td>' + labels[key] + '</td>' +
                    '<td class="' + (ok ? 'text-success' : 'text-danger') + '">' +
                    val + '</td></tr>';
            });
            box.classList.remove('d-none');
        })
        .catch(function() {
            tbody.innerHTML = '<tr><td colspan="2" class="text-danger">診断通信エラー</td></tr>';
            box.classList.remove('d-none');
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = '詳細診断';
        });
});

checkOnline();
</script>

</body>
</html>
