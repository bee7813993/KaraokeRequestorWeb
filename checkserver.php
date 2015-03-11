<?php

function webcheck(){
    $WEBSTATURL = "http://localhost/check.html";
    $org_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 5);
    $webstat = file_get_contents($WEBSTATURL);
    ini_set('default_socket_timeout', $org_timeout);
    if( $webstat === FALSE) {
        return FALSE;
    }
    if(strstr($webstat,'nginx is running.')){
    return TRUE;
    }
    return FALSE;
    
}

function phpcheck(){
    $PHPSTATURL = "http://localhost/phpinfo.php";
    $org_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 5);
    $webstat = file_get_contents($PHPSTATURL);
    ini_set('default_socket_timeout', $org_timeout);
    if( $webstat === FALSE) {
        return FALSE;
    }
    return TRUE;
}

// processcheck
// retval
// TRUE exist process
// FALSE not exist process
function processcheck($processname)
{
   $execcmd = 'tasklist | find /i "' . $processname . '"';
   $ret = exec($execcmd);
   if($ret){
     print $ret;
     return TRUE;
   }
   print $ret;
   return FALSE;
}

function nginx_restart(){
//    echo exec("cd");
//    echo "\n";
    chdir('..');
//    echo exec("cd");
//    echo "\n";
//    sleep(1);
    if(processcheck('nginx.exe')){
    $execcmd = 'nginx -c conf\nginx.conf -s stop';
    exec($execcmd);
    sleep(1);
    }

    if(processcheck('nginx.exe')){
    $execcmd = 'Taskkill /IM nginx.exe /F';
    exec($execcmd);
    sleep(1);
    }
    
//    echo "停止完了\n";
    sleep(2);
    $execcmd = 'start /b ""  nginx.exe -c conf\nginx.conf ';
    exec($execcmd);
//    echo "開始完了\n";
    chdir('html');
//    echo exec("cd");
//    echo "\n";
}

function nginx_checkrestart(){
    date_default_timezone_set('Asia/Tokyo');
    while(true) {
        $result = webcheck();
        if( $result === FALSE ){
             echo "nginx is down. restart".date(DATE_ATOM)."\n";
             nginx_restart();
             sleep(2);
        }else {
//            echo "DEBUG: nginx is running.".date(DATE_ATOM)."\n";
            sleep(10);
        }
    }
}

function php_restart(){
    chdir('..\..\php');
    sleep(1);
    if(processcheck('php-cgi.exe')){
    $execcmd = 'Taskkill /IM php-cgi.exe /F';
    exec($execcmd);
    sleep(1);
    }

//    echo "停止完了\n";
    sleep(2);
    $execcmd = 'start /b "" php-cgi.exe -b 127.0.0.1:9123 -c php.ini';
    exec($execcmd);
//    echo "開始完了\n";
    chdir('..\nginx\html');

}

function php_checkrestart(){
    date_default_timezone_set('Asia/Tokyo');
    while(true) {
        $result = phpcheck();
        if( $result === FALSE ){
             echo "php is down. restart. ".date(DATE_ATOM)."\n";
             php_restart();
             sleep(2);
        }else {
//            echo "DEBUG: php is running. ".date(DATE_ATOM)."\n";
            sleep(10);
        }
    }
}

function service_checkrestart(){
    date_default_timezone_set('Asia/Tokyo');
    while(true) {
        $result = webcheck();
        if( $result === FALSE ){
             echo "nginx is down. restart".date(DATE_ATOM)."\n";
             nginx_restart();
             sleep(2);
        }else {
//             echo "DEBUG: nginx is running.".date(DATE_ATOM)."\n";
        }
        $result = phpcheck();
        if( $result === FALSE ){
             echo "php is down. restart".date(DATE_ATOM)."\n";
             php_restart();
             sleep(2);
        }else {
//            echo "DEBUG: php is running.".date(DATE_ATOM)."\n";
            sleep(10);
        }
	}

}

?>
