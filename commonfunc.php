<?php

require_once 'kara_config.php';
require_once("getid3/getid3.php");

$showsonglengthflag = 0;

if (isset($_SERVER) && isset($_SERVER["SERVER_NAME"]) ){
    //var_dump($_SERVER);
    $everythinghost = $_SERVER["SERVER_NAME"];
} else {
    $everythinghost = 'localhost';
}

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

    $errno = 0;

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $contents = curl_exec($ch);
        
        if( $contents !== FALSE) {
            curl_close($ch);
            break;
        }
        $errno = curl_errno($ch);
        print $timeoutsec;
        curl_close($ch);
    }
    if ($loopcount === $retrytimes) {
        $error_message = curl_strerror($errno);
        print 'http connection error : '.$error_message . ' url : ' . $url . "\n";
    }
    return $contents;

}

/** あいまいな文字を+に置換する
*/
function replace_obscure_words($word)
{
  // 括弧削除 "/[ ]*\(.*?\)[ ]*/u";
  $resultwords = preg_replace("/[ ]*\(.*?\)[ ]*/u",' ',$word);
  // あいまい単語リスト
  $obscure_list = array(
                     "★",
                     "☆",
                     "？",
                     "?",
                     "×"
                     );
  // あいまい単語置換(スペースに)
  $resultwords = str_replace($obscure_list,' ',$resultwords);

  // 最後がスペースだったら取り除き
  $resultwords = rtrim($resultwords);

  // 単語が6文字以下の場合クォーテーションをつける
  if(strlen($word) <= 6){
      $resultwords = '"'.$resultwords.'"';
  }
  return $resultwords;
  
}

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

// 検索ワードから検索結果一覧を取得する処理
function searchlocalfilename($kerwords, &$result_array,$order = null)
{

		global $everythinghost;
		if(empty($order)){
		    $order = 'sort=size&ascending=0';
		}
  		$jsonurl = "http://" . $everythinghost . ":81/?search=" . urlencode($kerwords) . "&". $order . "&path=1&path_column=3&size_column=4&json=1";
//  		echo $jsonurl;
  		$json = file_get_html_with_retry($jsonurl, 5, 30);
//  		echo $json;
  		$result_array = json_decode($json, true);

}

//検索結果一覧を表示する処理
function printsonglists($result_array)
{
		global $everythinghost;
		global $showsonglengthflag;
		$getID3 = new getID3();
		$getID3->setOption(array('encoding' => 'UTF-8'));
		
  		echo "<table id=\"searchresult\">";
print "<thead>\n";
print "<tr>\n";
print "<th>No. </th>\n";
print "<th>リクエスト </th>\n";
print "<th>ファイル名(プレビューリンク) </th>\n";
print "<th>サイズ </th>\n";
if($showsonglengthflag == 1 ){
	print "<th>再生時間 </th>\n";
}
print "<th>パス </th>\n";
print "</tr>\n";
print "</thead>\n";
print "<tbody>\n";
		foreach($result_array["results"] as $k=>$v)
		{
		if($v['size'] <= 1 ) continue;
		
    	if($showsonglengthflag == 1 ){
    	  try{
    		$sjisfilename = addslashes(mb_convert_encoding($v['path'] . "\\" . $v['name'], "cp932", "utf-8"));
    		//print $sjisfilename."\n";
    		$music_info = @$getID3->analyze($sjisfilename);
    		getid3_lib::CopyTagsToComments($music_info); 
    	  }catch (Exception $e) {
    	    print $sjisfilename."\n";
    	  }	
			if(empty($music_info['playtime_string'])){
			   $length_str = 'Unknown';
			}else {
			   $length_str = $music_info['playtime_string'];
			}
		}
    		echo "<tr><td class=\"no\">$k</td>";
    		echo "<td class=\"reqbtn\">";
    		echo "<form action=\"request_confirm.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"hidden\" name=\"fullpath\" id=\"fullpath\" value=\"". $v['path'] . "\\" . $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td class=\"filename\">";
    		echo htmlspecialchars($v['name']);
        $previewpath = "http://" . $everythinghost . ":81/" . $v['path'] . "/" . $v['name'];
    		echo "<Div Align=\"right\"><A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
    		echo "プレビュー";
    		echo " </A></Div>";
    		echo "</td>";
    		echo "<td class=\"filesize\">";
    		echo formatBytes($v['size']);
    		echo "</td>";
			if($showsonglengthflag == 1 ){
    			echo "<td class=\"length\">";
    			echo $length_str;
    			echo "</td>";
    		}
    		echo "<td class=\"filepath\">";
    		echo htmlspecialchars($v['path']);
    		echo "</td>";
    		echo "</tr>";
    	}
print "</tbody>\n";
		echo "</table>";


  	echo "\n\n";
}

// 検索ワードからファイル一覧を表示するまでの処理
function PrintLocalFileListfromkeyword($word,$order = null)
{
    searchlocalfilename($word,$result_a,$order);
    echo $result_a["totalResults"]."件<br />";
    if( $result_a["totalResults"] >= 1) {
        printsonglists($result_a);
    }
}

function selectplayerfromextension($filepath)
{
$extension = pathinfo($filepath, PATHINFO_EXTENSION);
if( strcasecmp($extension,"mp3") == 0 
    || strcasecmp($extension,"m4a") == 0 
    || strcasecmp($extension,"wav") == 0 ){
    $player="foobar";
}else {
    $player="mpc";
}
return $player;
}

function getcurrentplayer(){
    global $db;
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = \"再生中\" ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    //var_dump($currentsong);
    if(count($currentsong) == 0){
        return "none";
    }else{
        $player=selectplayerfromextension($currentsong[0]['songfile']);
    }
    return $player;
}

function selectedcheck($definevalue, $checkvalue){
    if(strcmp($definevalue,$checkvalue) == 0) {
        return 'selected';
    }
    return ' ';
}

function print_meta_header(){
    print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    print "\n";
    print '<meta http-equiv="Content-Style-Type" content="text/css" />';
    print "\n";
    print '<meta http-equiv="Content-Script-Type" content="text/javascript" />';
    print "\n";
    print '<meta name="viewport" content="width=device-width,initial-scale=1.0" />';
    print "\n";
}

function makesongnamefromfilename($filename){
   // 【ニコカラ* 】を外す
   $patstr="\(ニコカラ.*?\)|（ニコカラ.*?）|【ニコカラ.*?】|\[ニコカラ.*?\]";
   $repstr="";
   $str=mb_ereg_replace($patstr, $repstr, $filename);
   // 拡張子を外す
   $patstr="/(.+)(\.[^.]+$)/";
   return preg_replace($patstr, "$1", $str);
}

?>
