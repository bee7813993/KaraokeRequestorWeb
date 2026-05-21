<?php
require_once 'commonfunc.php';
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'admin' || !$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="pfwd Settings"');
    die('管理者ログインが必要です');
}

require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

// pfwd 初期化
require_once 'pfwdctl.php';
$pfwdavailable = false;
$pfwdinfo = new pfwd();
if (array_key_exists('pfwdplace', $config_ini) && !empty($config_ini['pfwdplace'])) {
    $pfwdinfo->pfwdpath = urldecode($config_ini['pfwdplace']);
    ob_start();
    $pfwdavailable = $pfwdinfo->readpfwdcfg();
    ob_end_clean();
}

// --- AJAX: 接続診断 ---
if (isset($_GET['action']) && $_GET['action'] === 'check_online_debug') {
    header('Content-Type: application/json; charset=utf-8');
    $host_raw = array_key_exists('globalhost', $config_ini) && !empty($config_ini['globalhost'])
        ? urldecode($config_ini['globalhost']) : '';
    $host_parts = explode(':', $host_raw, 2);
    $h_host = $host_parts[0];
    $h_port = isset($host_parts[1]) ? (int)$host_parts[1] : 80;
    $http_url = 'http://' . $host_raw;
    $timeout  = 8;
    $results  = [];

    $ipv4 = @gethostbyname($h_host);
    $results['dns_v4'] = ($ipv4 !== $h_host) ? $ipv4 : '解決失敗';

    $aaaa = @dns_get_record($h_host, DNS_AAAA);
    $results['dns_v6'] = (!empty($aaaa)) ? $aaaa[0]['ipv6'] : '解決失敗(またはAAAAなし)';

    if ($ipv4 !== $h_host) {
        $fp = @fsockopen($ipv4, $h_port, $e, $es, $timeout);
        if ($fp) { fclose($fp); $results['tcp_v4_direct'] = "OK ({$ipv4}:{$h_port})"; }
        else { $results['tcp_v4_direct'] = "NG ({$e}): {$es}"; }
    } else {
        $results['tcp_v4_direct'] = 'スキップ(DNS解決失敗)';
    }

    $fp = @fsockopen($h_host, $h_port, $e, $es, $timeout);
    if ($fp) { fclose($fp); $results['tcp_hostname'] = "OK"; }
    else { $results['tcp_hostname'] = "NG ({$e}): {$es}"; }

    foreach ([null, CURL_IPRESOLVE_V4, CURL_IPRESOLVE_V6] as $resolve) {
        $ch = curl_init($http_url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FAILONERROR => false, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'KaraokeRequestor/1.0',
        ];
        if ($resolve !== null) $opts[CURLOPT_IPRESOLVE] = $resolve;
        curl_setopt_array($ch, $opts);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ip   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $en   = curl_errno($ch); $es2 = curl_strerror($en);
        curl_close($ch);
        $val = $code > 0 ? "OK HTTP {$code} (接続先IP: {$ip})" : "NG curl({$en}): {$es2}";
        if ($resolve === null)              $results['curl_auto'] = $val;
        elseif ($resolve === CURL_IPRESOLVE_V4) $results['curl_v4'] = $val;
        else                                $results['curl_v6'] = $val;
    }

    $timeout_ms = $timeout * 1000;
    $ping_cmd = "cmd /c \"chcp 437 >nul && ping -n 4 -w {$timeout_ms} {$h_host}\"";
    @exec($ping_cmd, $ping_output);
    $ping_str = implode("\n", $ping_output);
    if (preg_match('/Sent\s*=\s*(\d+).*Received\s*=\s*(\d+)/', $ping_str, $m)) {
        $sent = (int)$m[1]; $recv = (int)$m[2];
        $loss = $sent > 0 ? (int)(($sent - $recv) / $sent * 100) : 100;
        $results['ping_loss'] = "Loss {$loss}% ({$sent}回送信)";
    } else {
        $results['ping_loss'] = "実行失敗";
    }

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
    $ch = curl_init($check_url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KaraokeRequestor/1.0');
    curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $http_code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $primary_ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    curl_close($ch);
    if ($http_code > 0) {
        $status = 'ok';
        $detail = "HTTP {$http_code} [{$primary_ip}] (timeout:{$timeout}s)";
    } else {
        $status = 'ng';
        $detail = "curl({$curl_errno}): " . curl_strerror($curl_errno) . " (timeout:{$timeout}s)";
    }
    echo json_encode(['status' => $status, 'host' => $host, 'check_url' => $check_url, 'detail' => $detail]);
    exit;
}

// --- 設定保存 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $str_keys  = ['pfwdplace', 'onlinechecktimeout'];
    $int_keys  = ['usepfwdcheck'];
    foreach ($str_keys as $k) {
        if (isset($_POST[$k])) $config_ini[$k] = urlencode($_POST[$k]);
    }
    foreach ($int_keys as $k) {
        if (isset($_POST[$k])) $config_ini[$k] = (int)$_POST[$k];
    }

    // globalhost = ホスト名:ユーザー接続ポート として組み合わせて保存
    $post_hostname = isset($_POST['globalhost']) ? trim($_POST['globalhost']) : '';
    $post_openport = isset($_POST['pfwdserveropenport']) ? trim($_POST['pfwdserveropenport']) : '';
    if (!empty($post_hostname)) {
        $config_ini['globalhost'] = urlencode(
            !empty($post_openport) ? $post_hostname . ':' . $post_openport : $post_hostname
        );
    }

    writeconfig2ini($config_ini, $configfile);

    // pfwd.ini 更新：ホスト名・ユーザー接続ポート・pfwd接続ポート
    $pfwdplace_post = isset($_POST['pfwdplace']) && $_POST['pfwdplace'] !== ''
        ? $_POST['pfwdplace']
        : (array_key_exists('pfwdplace', $config_ini) ? urldecode($config_ini['pfwdplace']) : 'pfwd_forykr\\');
    $pfwdinfo_save = new pfwd();
    $pfwdinfo_save->pfwdpath = $pfwdplace_post;
    ob_start();
    $ok = $pfwdinfo_save->readpfwdcfg();
    ob_end_clean();
    if ($ok) {
        if (!empty($post_hostname)) {
            $pfwdinfo_save->set_pfwdhost($post_hostname);
        }
        if (!empty($post_openport) && ctype_digit($post_openport)) {
            $pfwdinfo_save->set_pfwdopenport($post_openport);
        }
        $post_pfwdport = isset($_POST['pfwdport']) ? trim($_POST['pfwdport']) : '';
        if (!empty($post_pfwdport) && ctype_digit($post_pfwdport)) {
            $pfwdinfo_save->set_pfwdport($post_pfwdport);
        }
        $pfwdinfo_save->save_pfwdconfig();
    }

    header('Location: pfwd_settings.php?saved=1');
    exit;
}

// --- WireGuard 確認 ---
function check_wireguard_running()
{
    exec('tasklist /fi "imagename eq wireguard.exe" 2>nul', $result);
    foreach ($result as $line) {
        if (stripos($line, 'wireguard.exe') !== false) return true;
    }
    return false;
}

// --- ステータス取得 ---
$pfwd_running = $pfwdavailable ? $pfwdinfo->statpfwdcmd() : false;
$wg_running   = check_wireguard_running();

$usepfwdcheck = array_key_exists('usepfwdcheck', $config_ini) && $config_ini['usepfwdcheck'] == 1;
$onlinechecktimeout_val = array_key_exists('onlinechecktimeout', $config_ini)
    ? urldecode($config_ini['onlinechecktimeout']) : '2';
$pfwdplace_val = array_key_exists('pfwdplace', $config_ini)
    ? urldecode($config_ini['pfwdplace']) : 'pfwd_forykr\\';
$globalhost_val = array_key_exists('globalhost', $config_ini)
    ? urldecode($config_ini['globalhost']) : '';

// globalhost のホスト名部分のみ抽出（入力欄の初期値）
$colonIdx2 = strrpos($globalhost_val, ':');
$globalhost_hostname = ($colonIdx2 !== false) ? substr($globalhost_val, 0, $colonIdx2) : $globalhost_val;

// ユーザー接続ポート（pfwd.ini から直接取得）
$pfwdopenport_display = $pfwdavailable ? (string)$pfwdinfo->get_pfwdopenport() : '';

// pfwd接続ポート＝SSH ポート（pfwd.ini から取得）
$pfwdport_display = $pfwdavailable ? (string)$pfwdinfo->get_pfwdport() : '10090';

// Auto-detect local IP for DDNS（ローカル接続用：IPv4のみ・ループバック除外）
function get_first_non_loopback_ip() {
    require_once 'ipconfig.php';
    $iplist = getiplist();
    foreach ($iplist as $ifinfo) {
        foreach ($ifinfo as $idx => $ip) {
            if ($idx == 0) continue; // skip interface name
            $ip = trim($ip);
            if (empty($ip)) continue;
            // IPv6は除外（コロンを含む）
            if (strpos($ip, ':') !== false) continue;
            // ループバック除外
            if ($ip === '127.0.0.1') continue;
            return $ip;
        }
    }
    return '';
}
$local_ip_for_ddns = get_first_non_loopback_ip();
?>
<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>オンライン接続設定</title>
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
<?php shownavigatioinbar_bs5('init.php'); ?>

<div class="container" style="max-width:560px; padding-bottom:32px;">

  <h5 class="mb-3">オンライン接続設定</h5>

  <?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    設定を保存しました。
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- 接続ステータス -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">接続ステータス</div>
    <div class="card-body">
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

  <!-- pfwd 制御 -->
  <?php if ($pfwdavailable): ?>
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">pfwd 制御</div>
    <div class="card-body">
      <div id="pfwd-online-alert" class="alert alert-warning py-2 small d-none" role="alert">
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
          onclick="start_pfwdcmd()">起動</button>
        <button type="button" class="btn btn-danger btn-lg flex-fill" onclick="stop_pfwdcmd()">停止</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 設定フォーム開始 -->
  <form method="post" id="pfwd-main-form">
    <input type="hidden" name="save_config" value="1">

    <!-- オンライン接続設定 -->
    <div class="card mb-3">
      <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">オンライン接続設定</div>
      <div class="card-body">
        <!-- ユーザー接続ポート -->
        <div class="mb-3">
          <label class="form-label fw-semibold">ユーザー接続ポート</label>
          <input type="text" name="pfwdserveropenport" id="pfwdserveropenport-input"
            class="form-control font-monospace" style="width:140px;"
            value="<?= htmlspecialchars($pfwdopenport_display, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="例: 11002" oninput="updateAddressDisplay()" />
          <div class="form-text">外部から接続するためのポート番号です。</div>
        </div>

        <!-- オンライン接続用ホスト名（ホスト名のみ）-->
        <div class="mb-3">
          <label class="form-label fw-semibold">オンライン接続用ホスト名</label>
          <input type="text" name="globalhost" id="globalhost-input"
            class="form-control font-monospace"
            value="<?= htmlspecialchars($globalhost_hostname, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="例: ykr.moe" oninput="updateAddressDisplay()" />
          <div class="form-text">グローバルIPまたはDDNSホスト名（ポートは除く）。</div>
        </div>

        <!-- オンライン接続用アドレス（表示のみ）-->
        <div class="mb-3">
          <label class="form-label fw-semibold text-muted small">オンライン接続用アドレス（自動生成）</label>
          <input type="text" id="online-address-display" class="form-control font-monospace"
            readonly tabindex="-1"
            value="<?= htmlspecialchars($globalhost_hostname . (!empty($pfwdopenport_display) ? ':' . $pfwdopenport_display : ''), ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <!-- 接続確認タイムアウト -->
        <div class="mb-3">
          <label class="form-label fw-semibold">接続確認タイムアウト (秒)</label>
          <input type="number" name="onlinechecktimeout" class="form-control" style="width:120px;"
            value="<?= htmlspecialchars($onlinechecktimeout_val, ENT_QUOTES, 'UTF-8') ?>" min="1" max="30" />
          <div class="form-text">ブラウザでは接続できるのに確認がNGになる場合は増やしてください。</div>
        </div>
      </div>
    </div>

    <!-- pfwd 設定 -->
    <div class="card mb-3">
      <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">pfwd 設定</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">pfwd.exe インストールフォルダ</label>
          <input type="text" name="pfwdplace" class="form-control font-monospace"
            value="<?= htmlspecialchars($pfwdplace_val, ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <!-- pfwd接続ポート（SSH ポート）-->
        <div class="mb-3">
          <label class="form-label fw-semibold">pfwd接続ポート</label>
          <input type="text" name="pfwdport" id="pfwdport-input"
            class="form-control font-monospace" style="width:140px;"
            value="<?= htmlspecialchars($pfwdport_display, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="例: 10090" oninput="updateAddressDisplay()" />
          <div class="form-text">pfwd がリレーサーバーに接続する SSH ポート番号です。</div>
        </div>

        <!-- pfwd接続アドレス（表示のみ）-->
        <div class="mb-3">
          <label class="form-label fw-semibold text-muted small">pfwd接続アドレス（自動生成）</label>
          <input type="text" id="pfwd-address-display" class="form-control font-monospace"
            readonly tabindex="-1"
            value="<?= htmlspecialchars($globalhost_hostname . (!empty($pfwdport_display) ? ':' . $pfwdport_display : ''), ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <!-- pfwd 自動再起動 -->
        <div class="mb-3">
          <label class="form-label fw-semibold">pfwd 自動再起動</label>
          <div class="form-text mb-1">通常時オンライン接続確認がOKになる環境で使用します。</div>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="usepfwdcheck" value="1" id="pfwdcheck_yes"
                <?= $usepfwdcheck ? 'checked' : '' ?>>
              <label class="form-check-label" for="pfwdcheck_yes">使用する</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="usepfwdcheck" value="2" id="pfwdcheck_no"
                <?= !$usepfwdcheck ? 'checked' : '' ?>>
              <label class="form-check-label" for="pfwdcheck_no">使用しない</label>
            </div>
          </div>
        </div>
      </div>
    </div>

  <!-- DDNS 設定 -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">DDNS 登録（オンライン用）</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold small">pcgame-r18.jp</label>
        <form method="post" action="https://pcgame-r18.jp/ddns/adddns.php" class="d-flex gap-2 align-items-end flex-wrap">
          <div>
            <label class="form-label small">ホスト名</label>
            <div class="input-group">
              <input type="text" name="host" class="form-control form-control-sm font-monospace" style="width:140px;" value="" />
              <span class="input-group-text small">.pcgame-r18.jp</span>
            </div>
          </div>
          <div>
            <label class="form-label small">IP</label>
            <input type="text" name="ip" class="form-control form-control-sm font-monospace" style="width:130px;"
              value="<?= htmlspecialchars(get_globalipv4(), ENT_QUOTES, 'UTF-8') ?>" />
          </div>
          <input type="hidden" name="ttl" value="30">
          <input type="hidden" name="autoreturn" value="1">
          <button type="submit" class="btn btn-sm btn-secondary mb-0">更新</button>
        </form>
      </div>
      <div>
        <label class="form-label fw-semibold small"><a href="http://jpn.www.mydns.jp/" target="_blank">mydns.jp</a></label>
        <form method="post" action="http://www.mydns.jp/directip.html" class="d-flex gap-2 align-items-end flex-wrap">
          <div>
            <label class="form-label small">マスターID</label>
            <input type="text" name="MID" class="form-control form-control-sm" style="width:110px;" value="" />
          </div>
          <div>
            <label class="form-label small">パスワード</label>
            <input type="text" name="PWD" class="form-control form-control-sm" style="width:110px;" value="" />
          </div>
          <div>
            <label class="form-label small">IP</label>
            <input type="text" name="IPV4ADDR" class="form-control form-control-sm font-monospace" style="width:120px;"
              value="<?= htmlspecialchars(get_globalipv4(), ENT_QUOTES, 'UTF-8') ?>" />
          </div>
          <button type="submit" class="btn btn-sm btn-secondary mb-0">更新</button>
        </form>
      </div>
    </div>
  </div>

  <!-- DDNS 設定（ローカル用）-->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">DDNS 登録（ローカル接続用）</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold small">pcgame-r18.jp</label>
        <form method="post" action="https://pcgame-r18.jp/ddns/adddns.php" class="d-flex gap-2 align-items-end flex-wrap">
          <div>
            <label class="form-label small">ホスト名</label>
            <div class="input-group">
              <input type="text" name="host" class="form-control form-control-sm font-monospace" style="width:140px;" value="" />
              <span class="input-group-text small">.pcgame-r18.jp</span>
            </div>
          </div>
          <div>
            <label class="form-label small">IP</label>
            <input type="text" name="ip" class="form-control form-control-sm font-monospace" style="width:130px;"
              value="<?= htmlspecialchars($local_ip_for_ddns, ENT_QUOTES, 'UTF-8') ?>" />
          </div>
          <input type="hidden" name="ttl" value="30">
          <input type="hidden" name="autoreturn" value="1">
          <button type="submit" class="btn btn-sm btn-secondary mb-0">更新</button>
        </form>
      </div>
      <div>
        <label class="form-label fw-semibold small"><a href="http://jpn.www.mydns.jp/" target="_blank">mydns.jp</a></label>
        <form method="post" action="http://www.mydns.jp/directip.html" class="d-flex gap-2 align-items-end flex-wrap">
          <div>
            <label class="form-label small">マスターID</label>
            <input type="text" name="MID" class="form-control form-control-sm" style="width:110px;" value="" />
          </div>
          <div>
            <label class="form-label small">パスワード</label>
            <input type="text" name="PWD" class="form-control form-control-sm" style="width:110px;" value="" />
          </div>
          <div>
            <label class="form-label small">IP</label>
            <input type="text" name="IPV4ADDR" class="form-control form-control-sm font-monospace" style="width:120px;"
              value="<?= htmlspecialchars($local_ip_for_ddns, ENT_QUOTES, 'UTF-8') ?>" />
          </div>
          <button type="submit" class="btn btn-sm btn-secondary mb-0">更新</button>
        </form>
      </div>
    </div>
  </div>

  <!-- 自IP一覧 -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">自IP一覧</div>
    <div class="card-body">
      <div style="font-family: monospace; white-space: pre-wrap; word-break: break-all; font-size: 0.875rem;"><?php
      require_once 'ipconfig.php';
      $result_ipconfig = getiplist();
      $ips_to_show = array(); // 重複排除用

      // すべてのインターフェースからIPを抽出（ループバック除外）
      foreach ($result_ipconfig as $ifinfo) {
          foreach ($ifinfo as $idx => $ip_str) {
              if ($idx === 0) continue; // インターフェース名をスキップ
              $ip = trim($ip_str);
              if (empty($ip)) continue;

              // IPv6 zone ID を削除
              if (strpos($ip, '%') !== false) {
                  $ip = substr($ip, 0, strpos($ip, '%'));
              }

              // ループバック判定
              if ($ip === '127.0.0.1' || $ip === '::1') {
                  continue;
              }

              // 重複排除
              if (in_array($ip, $ips_to_show, true)) {
                  continue;
              }

              $ips_to_show[] = $ip;
          }
      }

      // リンク生成（バッファに集める）
      $link_output = '';
      foreach ($ips_to_show as $ip) {
          $is_ipv6 = (strpos($ip, ':') !== false);
          $url_ip = $is_ipv6 ? '[' . $ip . ']' : $ip;
          $link = 'http://' . $url_ip . '/';
          if (array_key_exists('useeasyauth_word', $config_ini) && !empty($config_ini['useeasyauth_word'])) {
              $link = $link . '?easypass=' . $config_ini['useeasyauth_word'];
          }
          $link_output .= '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a>' . "\n";
      }
      echo $link_output;
      ?></div>
    </div>
  </div>

  <!-- 操作ボタン -->
  <div class="card mb-3">
    <div class="card-header fw-semibold py-2 px-3" style="font-size:1rem;">操作ボタン</div>
    <div class="card-body">
      <div class="d-grid">
        <a href="init.php" class="btn btn-outline-secondary">
          設定ページへ戻る
        </a>
      </div>
    </div>
  </div>

  <!-- 設定反映ボタン -->
  <div class="d-grid mb-3">
    <button type="submit" class="btn btn-primary btn-lg">設定反映</button>
  </div>

  </form><!-- /設定フォーム -->

</div><!-- /container -->

<?php print_bg_style_block(true); ?>

<script>
var pfwdRunning  = <?= $pfwdavailable && $pfwd_running ? 'true' : 'false' ?>;
var wgRunning    = <?= $wg_running ? 'true' : 'false' ?>;
var onlineStatus = 'unknown'; // 'ok' | 'ng' | 'disabled' | 'unknown'

function applyPfwdRestriction(pfwdIsRunning) {
    var startBtn       = document.getElementById('pfwd-start-btn');
    var onlineAlert    = document.getElementById('pfwd-online-alert');
    var autoRestartYes = document.getElementById('pfwdcheck_yes');

    // pfwd 自動再起動「使用する」: オンライン接続OKの時のみ有効
    if (autoRestartYes) {
        autoRestartYes.disabled = (onlineStatus !== 'ok');
    }

    if (!startBtn) return;
    // pfwd起動制限: オンラインOK かつ WireGuard実行中 かつ pfwd停止中の場合のみ
    if (onlineStatus === 'ok' && wgRunning && !pfwdIsRunning) {
        startBtn.disabled = true;
        if (onlineAlert) onlineAlert.classList.remove('d-none');
    } else {
        startBtn.disabled = false;
        if (onlineAlert) onlineAlert.classList.add('d-none');
    }
}

function updatePfwdStatus(data) {
    var el = document.getElementById('pfwdstatus');
    if (!el) return;
    pfwdRunning = !!data.pfwdstat;
    el.textContent = pfwdRunning ? '起動中' : '停止中';
    el.className   = 'badge fs-6 ' + (pfwdRunning ? 'bg-success' : 'bg-secondary');
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
    fetch('pfwd_settings.php?action=check_online')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var infoText = (data.check_url ? '確認先: ' + data.check_url : '') +
                           (data.detail ? '  [' + data.detail + ']' : '');
            if (data.status === 'ok') {
                onlineStatus = 'ok';
                el.textContent = 'OK'; el.className = 'badge bg-success';
            } else if (data.status === 'ng') {
                onlineStatus = 'ng';
                el.textContent = 'NG'; el.className = 'badge bg-danger';
            } else {
                onlineStatus = 'disabled';
                el.textContent = '無効'; el.className = 'badge bg-secondary';
            }
            if (detailEl) detailEl.textContent = infoText || data.detail;
            applyPfwdRestriction(pfwdRunning);
        })
        .catch(function() {
            onlineStatus = 'unknown';
            el.textContent = 'エラー'; el.className = 'badge bg-danger';
            if (detailEl) detailEl.textContent = 'AJAX通信エラー';
            applyPfwdRestriction(pfwdRunning);
        });
}

document.getElementById('check-online-btn').addEventListener('click', checkOnline);

document.getElementById('check-debug-btn').addEventListener('click', function() {
    var btn   = this;
    var box   = document.getElementById('debug-result');
    var tbody = document.getElementById('debug-tbody');
    btn.disabled = true; btn.textContent = '診断中...';
    box.classList.add('d-none'); tbody.innerHTML = '';
    fetch('pfwd_settings.php?action=check_online_debug')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var labels = {
                dns_v4: 'DNS (IPv4解決)', dns_v6: 'DNS (IPv6解決)',
                tcp_v4_direct: 'TCP fsockopen IPv4直接', tcp_hostname: 'TCP fsockopen ホスト名',
                curl_auto: 'curl (IP自動選択)', curl_v4: 'curl (IPv4強制)', curl_v6: 'curl (IPv6強制)',
                ping_loss: 'ping パケットロス率',
            };
            tbody.innerHTML = '<tr><td colspan="2" class="fw-bold">確認先: ' +
                data.host + ' (timeout:' + data.timeout + 's)</td></tr>';
            Object.keys(labels).forEach(function(key) {
                var val = data.results[key] || '—';
                var ok  = val.startsWith('OK') || val.startsWith('Loss 0%');
                tbody.innerHTML += '<tr><td>' + labels[key] + '</td>' +
                    '<td class="' + (ok ? 'text-success' : 'text-danger') + '">' + val + '</td></tr>';
            });
            box.classList.remove('d-none');
        })
        .catch(function() {
            tbody.innerHTML = '<tr><td colspan="2" class="text-danger">診断通信エラー</td></tr>';
            box.classList.remove('d-none');
        })
        .finally(function() { btn.disabled = false; btn.textContent = '詳細診断'; });
});

// 入力変化に合わせてアドレス表示を更新
function updateAddressDisplay() {
    var hostname = (document.getElementById('globalhost-input').value || '').trim();
    var openport = (document.getElementById('pfwdserveropenport-input').value || '').trim();
    var pfwdport = (document.getElementById('pfwdport-input').value || '').trim();

    var onlineAddr = hostname + (openport ? ':' + openport : '');
    var pfwdAddr   = hostname + (pfwdport  ? ':' + pfwdport  : '');

    var onlineEl = document.getElementById('online-address-display');
    var pfwdEl   = document.getElementById('pfwd-address-display');
    if (onlineEl) onlineEl.value = onlineAddr;
    if (pfwdEl)   pfwdEl.value   = pfwdAddr;
}

checkOnline();
</script>

</body>
</html>
