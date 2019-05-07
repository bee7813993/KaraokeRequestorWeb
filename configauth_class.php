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
        // 認証NG
        return false;
    }
    public function check_configpassword($password) {
        global $config_ini;
        if( array_key_exists('configpass',$config_ini) ) {
          if( $password === $config_ini["configpass"] ) return true;
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