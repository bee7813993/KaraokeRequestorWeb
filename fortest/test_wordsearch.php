<?php

require_once('commonfunc.php');

$word="mp4";

if(!empty($argv[1])){
    $word=$argv[1];
}

$url="http://localhost/searchfilefromkeyword_json_part.php?keyword=".$word."&length=100&bgvmode=0&start=100";

$response = urldecode(file_get_html_with_retry($url, 5, 30));

print $response;

?>