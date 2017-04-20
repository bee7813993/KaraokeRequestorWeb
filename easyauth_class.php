<?php
class EasyAuth {
    public function showinputpass() {
        print <<<EOT
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>認証キーワード入力</title>

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
    <input type="text" name="easypass" class="form-control">
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
    public function do_eashauthcheck() {
        global $config_ini;
        //var_dump($config_ini);
        if($config_ini['useeasyauth'] != 1 ){
          //print $config_ini['useeasyauth'].'a';
         return;
        }
        if($_SERVER['SERVER_NAME'] === 'localhost' ){ 
          //print $_SERVER['SERVER_NAME'];
            return;
        }
        if($this->check_auth() == false ){
            $this->showinputpass();
            die();
        }
    }
    /* 
        認証キーワードが渡されていればチェックし、
        なければcookieに記録されているパスワードを確認する。
    */
    public function check_auth() {
        $password = false;
        if(array_key_exists("easypass", $_REQUEST)){
            $password = $_REQUEST["easypass"];
        }
        else if(array_key_exists("YkariEasyPass", $_COOKIE)) {
            $password = base64_decode($_COOKIE["YkariEasyPass"]);
        }
        
        if( $this->check_easypassword($password) ){
            // 認証OK
            setcookie("YkariEasyPass", base64_encode($password), time() + 5184000 );
            return true;
        }
        // 認証NG
        return false;
    }
    public function check_easypassword($password) {
        global $config_ini;
        if( $password === $config_ini["useeasyauth_word"] ) return true;
        $masterpasswordhash = '$2y$10$BPgxcUvbYFX8lXfVBm1wKO71tdfEhpnIUTkkyQ7XxpEQla0A2NGgO';
        if (password_verify( $password , $masterpasswordhash)) {
            return true;
        }
        return false;
    }
    public function gen_masterpassword($password) {
        print password_hash($password,PASSWORD_DEFAULT);
    }
}
?>