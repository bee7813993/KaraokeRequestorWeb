<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>bandit検索モード検索結果</title>
</head>
<body>
<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
<br />
<?php

if(array_key_exists("searchword", $_REQUEST)) {
    $l_searchword = $_REQUEST["searchword"];
}

if(array_key_exists("column", $_REQUEST)) {
    $l_column = $_REQUEST["column"];
}

$everythinghost = $_SERVER["SERVER_NAME"];
$everythinghost = 'localhost';

/**
 * バイト数をフォーマットする
 * @param integer $bytes
 * @param integer $precision
 * @param array $units
 */
function formatBytes($bytes, $precision = 2, array $units = null)
{
    if ( abs($bytes) < 1024 )
    {
        $precision = 0;
    }

    if ( is_array($units) === false )
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    }

    if ( $bytes < 0 )
    {
        $sign = '-';
        $bytes = abs($bytes);
    }
    else
    {
        $sign = '';
    }

    $exp   = floor(log($bytes) / log(1024));
    $unit  = $units[$exp];
    $bytes = $bytes / pow(1024, floor($exp));
    $bytes = sprintf('%.'.$precision.'f', $bytes);
    return $sign.$bytes.' '.$unit;
}

function searchlocalfilename($kerwords, &$result_array)
{
		global $everythinghost;
  		$jsonurl = "http://" . $everythinghost . ":81/?search=" . urlencode($kerwords) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
//  		echo $jsonurl;
  		$json = file_get_contents($jsonurl);
//  		echo $json;
  		$result_array = json_decode($json, true);

}

function printsonglists($result_array)
{
		global $everythinghost;
		
  		echo "<table>";
print "<tr>\n";
print "<th>No. </th>\n";
print "<th>リクエスト </th>\n";
print "<th>ファイル名(プレビューリンク) </th>\n";
print "<th>サイズ </th>\n";
print "<th>パス </th>\n";
print "</tr>\n";
print "<tbody>\n";
		foreach($result_array["results"] as $k=>$v)
		{
    		echo "<tr><td>$k</td>";
    		echo "<td>";
    		echo "<form action=\"request.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td>";
    		echo $v['name'];
        $previewpath = "http://" . $everythinghost . ":81/" . $v['path'] . "/" . $v['name'];
    		echo "<Div Align=\"right\"><A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
    		echo "プレビュー";
    		echo " </A></Div>";
    		echo "</td>";
    		echo "<td>";
    		echo formatBytes($v['size']);
    		echo "</td>";
    		echo "<td>";
    		echo $v['path'];
    		echo "</td>";
    		echo "</tr>";
    	}
print "</tbody>\n";
		echo "</table>";


  	echo "\n\n";
}


// 歌手検索
$arr = array('column' => $l_column ,  // 歌手
             'keyword' => utf8_encode($l_searchword) , 
             'method' => '1', // AND 
             'exclude_keyword' => '', 
             'exclude_method' => '2',
             'year' => '',
             'year_type' => '1',
             'option_year' => true,
             'option_common' => true
             );
$reqdata = json_encode($arr);          

//echo  $reqdata;

$url = 'http://eroge.no-ip.org/search.cgi';

$header = array(
        "Content-Type: application/json; charset=utf-8",
        "Referer: http://eroge.no-ip.org/search.html"
//        "Content-Length: ".strlen($reqdata)."\r\n"
    );
             
$options = array('http' => array(
    'method' => 'POST',
    'header' => implode("\r\n", $header),
    'content' => $reqdata,
));

$contents =file_get_contents($url, false, stream_context_create($options));
$songlist = json_decode($contents,true,4096);
//var_dump($songlist["result"]);

//echo $contents;

foreach($songlist["result"] as $value){
  echo $value["title"]."の検索結果 : ";
  searchlocalfilename($value["title"],$result_a);
  echo $result_a["totalResults"]."件<br />";
  if( $result_a["totalResults"] > 1) {
    printsonglists($result_a);
  }
//  var_dump($result_a);
}


?>

<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
</body>
</html>
