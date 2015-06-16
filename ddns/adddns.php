<?php

require_once 'userlist.php';


if(!function_exists('getallheaders'))
{
    function getallheaders() 
    {
        $headers = '';
        foreach ($_SERVER as $name => $value)
        {
            if(substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
* ダイジェスト認証をかける
*
* @param array $auth_list ユーザー情報(複数ユーザー可) array("ユーザ名" => "パスワード") の形式
* @param string $realm レルム文字列
* @param string $failed_text 認証失敗時のエラーメッセージ
*/
function digest_auth($auth_list,$realm="Restricted Area",$failed_text="認証に失敗しました"){

/*
    if (!$_SERVER['PHP_AUTH_DIGEST']){
        $headers = getallheaders();
        if ($headers['Authorization']){
            $_SERVER['PHP_AUTH_DIGEST'] = $headers['Authorization'];
        }
    }
*/   
    if ($_SERVER['PHP_AUTH_DIGEST']){
        // PHP_AUTH_DIGEST 変数を精査する
        // データが失われている場合への対応
        $needed_parts = array(
                    'nonce' => true,
                    'nc' => true,
                    'cnonce' => true,
                    'qop' => true,
                    'username' => true,
                    'uri' => true,
                    'response' => true
                    );
        $data = array();
       
        $matches = array();
        preg_match_all('/(\w+)=("([^"]+)"|([a-zA-Z0-9=.\/\_-]+))/',$_SERVER['PHP_AUTH_DIGEST'],$matches,PREG_SET_ORDER);
       
        foreach ($matches as $m){
            if ($m[3]){
                $data[$m[1]] = $m[3];
            }else{
                $data[$m[1]] = $m[4];
            }
            unset($needed_parts[$m[1]]);
        }
       
        if ($needed_parts){
            $data = array();
        }
       
        if ($auth_list[$data['username']]){
            // 有効なレスポンスを生成する
            $A1 = md5($data['username'].':'.$realm.':'.$auth_list[$data['username']]);
            $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
            $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
           
            if ($data['response'] != $valid_response){
                unset($_SERVER['PHP_AUTH_DIGEST']);
            }else{
                return $data['username'];
            }
        }
    }
   
    //認証データが送信されているか
    header('HTTP/1.1 401 Authorization Required');
    header('WWW-Authenticate: Digest realm="'.$realm.'", nonce="'.uniqid(rand(),true).'", algorithm=MD5, qop="auth"');
    header('Content-type: text/html; charset='.mb_internal_encoding());
   
    die($failed_text);
}


digest_auth($userlist);


$command = "time /T >> C:\ProgramData\Temp\aaa.txt";

if(array_key_exists("ip", $_REQUEST)) {
    $ip = $_REQUEST["ip"];
}

if(array_key_exists("ttl", $_REQUEST)) {
    $ttl = $_REQUEST["ttl"];
}

if(array_key_exists("host", $_REQUEST)) {
    $host = trim($_REQUEST["host"]);
}


?>

<!doctype html>
<html lang="ja">
<head>
<title>DDNS登録画面</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
</head>
<body>

<?php

$denyhostlist = array('www', 'mail', 'ns01', 'ns02', 'home');

//print "Debug: ip: $ip, ttl: $ttl, host: $host <br>\n";
if(empty($ttl)){
 $ttl = 30;
}



if(!empty($host) && !empty($ttl) && !empty($ip) ){


    $validhost = false;
    foreach($denyhostlist as $word){
       //print "DEBUG: denyhostlist: $host:".bin2hex($host).", word: $word:".bin2hex($word).", validhost:$validhost <br>";
       
       if( ($res = strcasecmp($word, $host)) === 0 ){
           $validhost = $word;
       }
    }

    if($validhost === false ){

        //print "Debug:trying update ddns <br>\n";
        $ddnsline[] = sprintf("update delete %s.pcgame-r18.jp A\n",$host);
        $ddnsline[] = sprintf("update add %s.pcgame-r18.jp %d A %s\n",$host,$ttl,$ip);
        $ddnsline[] = sprintf("send\n");


        $tmpfname=tempnam(" /tmp","dns");
        $handle = fopen($tmpfname, "w");
        foreach($ddnsline as $line){
        fwrite($handle,$line);
        }
        fclose($handle);

        $command = "/usr/bin/nsupdate $tmpfname";
        exec($command);
        unlink($tmpfname);
        print "DDNS の設定を更新しました。長くて60秒後以降使用できるはずです。HOST:$host.pcgame-r18.jp, IP:$ip  <br>\n";
    }else {
        print "Hostname $host は使用できません<br>";
        $host = " ";
    }

}

if(empty($ip)){
 $ip = " ";
}
if(empty($host)){
 $host = " ";
}

?>

<form method="post" action="adddns.php">
Hostname :
<input type="text" name="host" class="host" style=”width: 40%;” value="<?php echo $host; ?>" />.pcgame-r18.jp
&nbsp;<br>
TTL :
<input type="text" name="ttl" size="10" class="ttl" value="<?php echo $ttl; ?>" />
&nbsp;<br>
IP :
<input type="text" name="ip" size="10" class="ip" value="<?php echo $ip; ?>" />
&nbsp;<br>
<input type="submit" name="OK" value="OK" />
</form>
<INPUT type="button" value="戻る" onClick="history.back()">

</body>
</html>


