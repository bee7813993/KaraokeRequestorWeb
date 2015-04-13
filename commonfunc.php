<?php

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

    $errno = 0;

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutsec);
        $contents = curl_exec($ch);
        
        if( $contents !== FALSE) {
            curl_close($ch);
            break;
        }
        $errno = curl_errno($ch);
        curl_close($ch);
    }
    if ($loopcount === $retrytimes) {
        $error_message = curl_strerror($errno);
        print 'http connection error : '.$error_message . ' url : ' . $url . "\n";
    }
    return $contents;

}

?>
