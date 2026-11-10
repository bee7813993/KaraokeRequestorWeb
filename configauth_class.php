<?php
class ConfigAuth {
    public function showinputpass() {
        print <<<EOT
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>設定画面パスワード入力</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.min.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
  <div class="container">
    <form  method="POST" >
    <div class="form-group">
    <label>認証キーワード入力</h1>
    <input type="text" name="configpass" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary" >ログイン</button>
    </form>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="js/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
EOT;
    }

    /* 
        認証キーワードが渡されていればチェックし、
        なければcookieに記録されているパスワードを確認する。
    */
    public function check_auth($checkpass) {
        $password = false;
        if(!empty($checkpass)){
            $password = $checkpass;
        }
        else if(array_key_exists("YkariConfigPass", $_COOKIE)) {
            $password = base64_decode($_COOKIE["YkariConfigPass"]);
        }
        if( $this->check_configpassword($password) ){
            // 認証OK
            // Cookieはブラウザを閉じるまで
            setcookie("YkariConfigPass", base64_encode($password), 0 );
            return true;
        }
        // サーバー PC 上のブラウザからのアクセスはパスワードを省略する。
        // 判定に 0.3 秒ほどかかるので、パスワードが合わなかったときだけ調べる。
        if (self::is_local_console_access()) return true;
        // 認証NG
        return false;
    }

    /*
        サーバーを動かしている PC 上で操作しているブラウザ等からのアクセスかを判定する。
        pfwd (SSH リモートフォワード) 経由の通信は、PC 上の pfwd.exe がループバックで
        接続し直して届くため、接続元アドレスだけでは区別できない。Host ヘッダーは
        クライアントが自由に指定できるので判定に使わない。
        そこで接続元ソケットを持つプログラムを netstat / tasklist で調べ、
        トンネル (pfwd.exe) とサーバー自身が出した要求 (httpd.exe) は除外する。
        プログラムを特定できないときはローカル扱いしない。
    */
    public static function is_local_console_access() {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $server = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        $port   = isset($_SERVER['REMOTE_PORT']) ? (string)$_SERVER['REMOTE_PORT'] : '';
        if ($remote === '' || !ctype_digit($port)) return false;
        if (!in_array($remote, array('127.0.0.1', '::1', $server), true)) return false;

        $image = self::find_client_process_name($remote, $port, $server);
        if ($image === null) return false;
        // pfwd.exe は pfwdctl.php の $pfwdcmd と同じ名前
        $excluded = array('pfwd.exe', 'httpd.exe');
        return !in_array(strtolower($image), $excluded, true);
    }

    // 接続元 (このサーバーへ接続しているクライアント側ソケット) を持つプロセス名を返す。見つからなければ null
    private static function find_client_process_name($remote, $port, $server) {
        if (!function_exists('exec')) return null;
        $is_v6 = (strpos($remote, ':') !== false);
        $local = $is_v6 ? '[' . $remote . ']:' . $port : $remote . ':' . $port;
        $peer  = ($server === '') ? '' : ($is_v6 ? '[' . $server . ']:' : $server . ':');

        $pid = 0;
        $lines = array();
        @exec('netstat -ano -p ' . ($is_v6 ? 'TCPv6' : 'TCP'), $lines);
        foreach ($lines as $line) {
            // TCP  <ローカルアドレス>  <外部アドレス>  <状態>  <PID>
            $cols = preg_split('/\s+/', trim($line));
            if (count($cols) < 5 || $cols[1] !== $local) continue;
            if ($peer !== '' && strpos($cols[2], $peer) !== 0) continue;
            $pid = (int)end($cols);
            if ($pid > 0) break;
        }
        if ($pid <= 0) {
            // netstat の一覧は接続の出入りが多いと行が欠けることがある (1 割弱)。
            // 見つからないときだけ、遅いが確実な Get-NetTCPConnection (0.8 秒ほど) で引き直す
            $filter = '-LocalAddress ' . $remote . ' -LocalPort ' . $port
                    . ($server === '' ? '' : ' -RemoteAddress ' . $server);
            $out = array();
            @exec('powershell -NoProfile -NonInteractive -Command "(Get-NetTCPConnection ' . $filter
                . ' -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess"', $out);
            $pid = (int)trim(implode('', $out));
        }
        if ($pid <= 0) return null;

        $out = array();
        @exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH', $out);
        if (!empty($out[0]) && preg_match('/^"([^"]+)"/', trim($out[0]), $m)) return $m[1];
        return null;
    }
    public function check_configpassword($password) {
        global $config_ini;
        // 設定が読み込まれていない場合は拒否する (PHP 7 では array_key_exists() が
        // 配列以外に null を返し、下の else に進んで認証が素通りになるため)
        if( !is_array($config_ini) ) return false;
        if( array_key_exists('configpass',$config_ini) ) {
          if( $password === $config_ini["configpass"] ) return true;
          if (empty($config_ini["configpass"] )) return true;
        } else {
          return true;
        }
        $masterpasswordhash = '$2y$10$BPgxcUvbYFX8lXfVBm1wKO71tdfEhpnIUTkkyQ7XxpEQla0A2NGgO';
        if (password_verify( $password , $masterpasswordhash)) {
            return true;
        }
        return false;
    }
    public function gen_masterpassword($password) {
        print password_hash($password,PASSWORD_DEFAULT);
    }
    public function show_password() {
        global $config_ini;
        if( array_key_exists('configpass',$config_ini) ) {
            return $config_ini["configpass"];
        }else {
            return '';
        }
    }
}
?>