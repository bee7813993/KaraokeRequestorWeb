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

// --- AJAX: オンライン接続確認 ---
if (isset($_GET['action']) && $_GET['action'] === 'check_online') {
    header('Content-Type: application/json; charset=utf-8');

    $timeout = (int)(array_key_exists('onlinechecktimeout', $config_ini) ? $config_ini['onlinechecktimeout'] : 5);
    if ($timeout < 5) $timeout = 5;

    // pfwd 起動中: HTTP ループバックを避け SSH サーバーへの TCP 接続で確認
    // （pfwd の逆トンネルを介して HTTP チェックすると同一 Apache に折り返しデッドロックになる）
    $pfwd_running_req = isset($_GET['pfwd_running']) && $_GET['pfwd_running'] === '1';
    if ($pfwd_running_req && $pfwdavailable) {
        $ssh_host  = $pfwdinfo->get_pfwdhost();
        $ssh_port  = (int)$pfwdinfo->get_pfwdport();
        $check_url = "tcp://{$ssh_host}:{$ssh_port}";
        if ($ssh_host && $ssh_port) {
            $fp = @fsockopen($ssh_host, $ssh_port, $sock_errno, $sock_errstr, $timeout);
            if ($fp) {
                fclose($fp);
                $status = 'ok';
                $detail = "SSH({$ssh_host}:{$ssh_port}) 到達OK (timeout:{$timeout}s)";
            } else {
                $status = 'ng';
                $detail = "({$sock_errno}): {$sock_errstr} (timeout:{$timeout}s)";
            }
            echo json_encode(['status' => $status, 'host' => $ssh_host, 'check_url' => $check_url, 'detail' => $detail]);
            exit;
        }
    }

    // 通常の HTTP チェック（pfwd 停止中）
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

    // FOLLOWLOCATION=false: 3xx レスポンスが返った時点で到達確認済みとする
    $ch = curl_init($check_url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KaraokeRequestor/1.0');
    curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_strerror($curl_errno);
    $http_code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code > 0) {
        $status = 'ok';
        $detail = "HTTP {$http_code} (timeout:{$timeout}s)";
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
    <div class="card-header fw-bold">自動再生コントロール</div>
    <div class="card-body">
      <p class="mb-3">
        ステータス：
        <?php if ($ap != 0): ?>
          <span class="badge bg-success fs-6">実行中</span>
        <?php else: ?>
          <span class="badge bg-secondary fs-6">停止中</span>
        <?php endif; ?>
      </p>
      <div class="d-flex gap-2">
        <form method="GET">
          <input type="hidden" name="karaokeautorunaction" value="start">
          <button type="submit" class="btn btn-success">▶ Start</button>
        </form>
        <form method="GET">
          <input type="hidden" name="karaokeautorunaction" value="stop">
          <button type="submit" class="btn btn-danger">■ Stop</button>
        </form>
      </div>
    </div>
  </div>

  <!-- 接続ステータス -->
  <div class="card mb-3">
    <div class="card-header fw-bold">接続ステータス</div>
    <div class="card-body">

      <!-- オンライン接続確認 -->
      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <span class="text-nowrap">オンライン接続:</span>
          <span id="online-status" class="badge bg-secondary">確認中...</span>
          <button type="button" class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="check-online-btn">再確認</button>
        </div>
        <div id="online-detail" class="small text-muted" style="word-break:break-all;">&nbsp;</div>
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
    <div class="card-header fw-bold">pfwd (SSH転送)</div>
    <div class="card-body">

      <!-- WireGuard 実行中かつ pfwd も起動中: 危険警告 -->
      <div id="pfwd-danger-alert" class="alert alert-danger py-2 <?= ($wg_running && $pfwd_running) ? '' : 'd-none' ?>" role="alert">
        <strong>⚠ 警告:</strong> WireGuard 接続中にもかかわらず pfwd が起動しています。<br>
        pfwd は WireGuard オフ時のみ使用してください。直ちに停止してください。
      </div>

      <!-- WireGuard 実行中かつ pfwd 停止中: 注意 -->
      <div id="pfwd-online-alert" class="alert alert-warning py-2 <?= ($wg_running && !$pfwd_running) ? '' : 'd-none' ?>" role="alert">
        WireGuard 接続中です。pfwd は起動しないでください。
      </div>

      <p class="mb-3">
        ステータス：
        <span id="pfwdstatus" class="badge fs-6 <?= $pfwd_running ? 'bg-success' : 'bg-secondary' ?>">
          <?= $pfwd_running ? '起動中' : '停止中' ?>
        </span>
      </p>
      <div class="d-flex gap-2">
        <button type="button" id="pfwd-start-btn" class="btn btn-success"
          <?= $wg_running ? 'disabled' : '' ?> onclick="start_pfwdcmd()">起動</button>
        <button type="button" class="btn btn-danger" onclick="stop_pfwdcmd()">停止</button>
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
    var dangerAlert = document.getElementById('pfwd-danger-alert');
    var onlineAlert = document.getElementById('pfwd-online-alert');
    if (!startBtn) return;
    if (wgRunning) {
        startBtn.disabled = true;
        if (pfwdIsRunning) {
            if (dangerAlert) dangerAlert.classList.remove('d-none');
            if (onlineAlert) onlineAlert.classList.add('d-none');
        } else {
            if (dangerAlert) dangerAlert.classList.add('d-none');
            if (onlineAlert) onlineAlert.classList.remove('d-none');
        }
    } else {
        startBtn.disabled = false;
        if (dangerAlert) dangerAlert.classList.add('d-none');
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
    // pfwd 起動中を渡すことで PHP 側が SSH TCP チェックに切り替える
    fetch('autoplayctrl.php?action=check_online&pfwd_running=' + (pfwdRunning ? '1' : '0'))
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
checkOnline();
</script>

</body>
</html>
