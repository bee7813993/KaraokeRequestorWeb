<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">

<title>bandit検索モード検索結果</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
<br />

  <h3>banditの隠れ家連携検索モード </h3>
  (キーワードでbanditの隠れ家のサイトから曲名を検索し、その曲名でローカルにファイルがあるかを検索)<br>
  (banditさんに登録されてない曲は見つけられません。[新しめのマイナーな曲とか])<br>
  (曲名の一部を含む別の曲とかも検索結果に出ちゃいます。ありがちな1単語の曲名だとたくさん結果に出てきてしまうので注意してね)<br>
  (網羅されてない新しい曲とか、特殊文字（★とか）が曲名に入っていると見つからない可能性があるので改めてファイル名検索してみて)
  
  <br>
  歌手名検索 
  <form action="searchbandit.php" method="post" style="display: inline" />
  <input type="text" name="searchword">
  <input type="hidden" name="column" value="2" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームタイトル検索 
  <form action="searchbandit.php" method="post" style="display: inline"/>
  <input type="text" name="searchword">
  <input type="hidden" name="column" value="3" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームブランド検索 
  <form action="searchbandit.php" method="post" style="display: inline" />
  <input type="text" name="searchword">
  <input type="hidden" name="column" value="1" />
  <input type="submit" value="検索">
  </form>
  <br />

<hr />
  <h3>検索結果 </h3>


<?php

if(array_key_exists("searchword", $_REQUEST)) {
    $l_searchword = $_REQUEST["searchword"];
}

if(array_key_exists("column", $_REQUEST)) {
    $l_column = $_REQUEST["column"];
}

$everythinghost = $_SERVER["SERVER_NAME"];
//$everythinghost = 'localhost';

require_once 'commonfunc.php';

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

function searchlocalfilename($kerwords, &$result_array)
{
		global $everythinghost;
  		$jsonurl = "http://" . $everythinghost . ":81/?search=" . urlencode($kerwords) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
//  		echo $jsonurl;
  		$json = file_get_html_with_retry($jsonurl, 5);
//  		echo $json;
  		$result_array = json_decode($json, true);

}

function printsonglists($result_array)
{
		global $everythinghost;
		
  		echo "<table id=\"searchresult\">";
print "<thead>\n";
print "<tr>\n";
print "<th>No. </th>\n";
print "<th>リクエスト </th>\n";
print "<th>ファイル名(プレビューリンク) </th>\n";
print "<th>サイズ </th>\n";
print "<th>パス </th>\n";
print "</tr>\n";
print "</thead>\n";
print "<tbody>\n";
		foreach($result_array["results"] as $k=>$v)
		{
		if($v['size'] <= 1 ) continue;
    		echo "<tr><td class=\"no\">$k</td>";
    		echo "<td class=\"reqbtn\">";
    		echo "<form action=\"request_confirm.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td class=\"filename\">";
    		echo $v['name'];
        $previewpath = "http://" . $everythinghost . ":81/" . $v['path'] . "/" . $v['name'];
    		echo "<Div Align=\"right\"><A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
    		echo "プレビュー";
    		echo " </A></Div>";
    		echo "</td>";
    		echo "<td class=\"filesize\">";
    		echo formatBytes($v['size']);
    		echo "</td>";
    		echo "<td class=\"filepath\">";
    		echo $v['path'];
    		echo "</td>";
    		echo "</tr>";
    	}
print "</tbody>\n";
		echo "</table>";


  	echo "\n\n";
}


// bandit検索
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
$songnum = 0;
    foreach($songlist["result"] as $value){
        $songtitle = replace_obscure_words($value["title"]);
        $songtitles = array();
        $songtitles[] = $songtitle;

        // 全部全角にしたときのチェック
        $songtitle_tmp = mb_convert_kana($songtitle,"A");
        $same = 0;
        foreach($songtitles as $checktitle){
          if(strcmp($checktitle ,$songtitle_tmp) == 0){
            $same = 1;
          }
        }
        if($same === 0) {
           $songtitles[] = $songtitle_tmp;
        }
        // 全部半角にしたときのチェック
        $songtitle_tmp = mb_convert_kana($songtitle,"a");
        $same = 0;
        foreach($songtitles as $checktitle){
          if(strcmp($checktitle ,$songtitle_tmp) == 0){
            $same = 1;
          }
        }
        if($same === 0) {
           $songtitles[] = $songtitle_tmp;
        }
        
        foreach($songtitles as $checktitle){
                  
              echo "<a name=\"song_".(string)$songnum."\">「".$checktitle."」の検索結果 : </a>&nbsp; &nbsp;  <a href=\"#song_".(string)($songnum + 1)."\" > 次の曲へ </a>";
              searchlocalfilename($checktitle,$result_a);
              echo $result_a["totalResults"]."件<br />";
              if( $result_a["totalResults"] >= 1) {
                printsonglists($result_a);
              }
            //  var_dump($result_a);
              $songnum = $songnum + 1;
        }
    }



?>

<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
</body>
</html>
