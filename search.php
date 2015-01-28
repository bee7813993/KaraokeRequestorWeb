
<?php
if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
}

?>
<html>
<head>
  <script type="text/javascript" src="path/to/jquery.js"></script>
  <script type="text/javascript">

  ?>
    // ここに処理を記述します。
  </script>
</head>
<body>

<?php
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
?>

<?php
//echo $word;
?>

  <form action="search.php" method="post">
  <input type="text" name="searchword">
  <input type="submit" value="検索">
  </form>
  and検索は+区切りでいけるっぽい。<br>

  <?php
  	if ( empty ($word)){
  		
  	}else {
  		$jsonurl = "http://" . $_SERVER["SERVER_NAME"] . ":81/?search=" . $word . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
  		// echo $jsonurl;
  		$json = file_get_contents($jsonurl);
  		$decode = json_decode($json, true);
  		echo "<table>";
print "<tr>\n";
print "<th>No. </th>\n";
print "<th>リクエスト </th>\n";
print "<th>ファイル名(プレビューリンク) </th>\n";
print "<th>サイズ </th>\n";
print "<th>パス </th>\n";
print "</tr>\n";
print "<tbody>\n";
		foreach($decode["results"] as $k=>$v)
		{
//			foreach($decode2 as $k=>$v)
    		echo "<tr><td>$k</td>";
    		echo "<td>";
    		echo "<form action=\"request.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"hidden\" name=\"fullpath\" id=\"fullpath\" value=\"". $v['path'] . "\\" . $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td>";
        $previewpath = "http://" . $_SERVER["HTTP_HOST"] . ":81/" . $v['path'] . "/" . $v['name'];
    		echo "<A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
    		echo $v['name'];
    		echo " </A>";
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
  	echo "<pre>";
//  	var_dump($decode);
  	echo "</pre>";

  	}
  	?>


</body>
</html>
