<?php
// 変数チェック
require_once 'modules/simple_html_dom.php';

$l_kind = null;
if(array_key_exists("kind", $_REQUEST)) {
    $l_kind = urldecode($_REQUEST["kind"]);
}

$l_url = null;
if(array_key_exists("url", $_REQUEST)) {
    $l_url = urldecode($_REQUEST["url"]);
}

$everythinghost = $_SERVER["SERVER_NAME"];
//$everythinghost = 'localhost';

// URLを叩いて検索ワード候補リクエスト用URL生成
function ansoninfo_gettitlelisturl($m,$q,$fullparam){
    if(isset($fullparam)){
        $url="http://anison.info/data/".$fullparam;
    }else {
        $urlbase="http://anison.info/data/n.php?m=%s&q=%s&year=&genre=";
        $url=sprintf($urlbase,urlencode($m),$q);
    }
    return $url;
}

// URLを叩いて検索ワード候補をarrayで返す。
function ansoninfo_gettitlelist($url,$l_kind){
    $results = array();
    
    
    $result_dom=file_get_html($url);
    
    if(strcmp("program",$l_kind) == 0){
      foreach( $result_dom->find( 'table.sorted' ) as $list ){
        foreach( $list->find( 'tr' ) as $tr ){
            $oped = null;
            $songtitle = null;
            $artist = null;
            $lyrics = null;
            $compose = null;
            $arrange = null;
            
            $value=$tr->find('td[headers=oped]',0);
            if(isset($value)){
                $oped = $value->plaintext;
            }
            $value=$tr->find('td[headers=song]',0);
            if(isset($value)){
            $songtitle = $value->plaintext;
            }
            $value=$tr->find('td[headers=vocal]',0);
            if(isset($value)){
            $artist = $value->plaintext;
            }
            $value=$tr->find('td[headers=lyrics]',0);
            if(isset($value)){
            $lyrics = $value->plaintext;
            }
            $value=$tr->find('td[headers=compose]',0);
            if(isset($value)){
            $compose = $value->plaintext;
            }
            $value=$tr->find('td[headers=arrange]',0);
            if(isset($value)){
            $arrange = $value->plaintext;
            }

            $result_one = array (
                                   'oped' => $oped,
                                   'songtitle' => $songtitle , 
                                   'artist' => $artist , 
                                   'lyrics' => $lyrics,
                                   'compose' => $compose,
                                   'arrange' => $arrange
                                   );
            $results[]=$result_one;            
        }
      }
    }elseif(strcmp("artist",$l_kind) == 0){
      foreach( $result_dom->find( 'table.sorted' ) as $list ){
        foreach( $list->find( 'tr' ) as $tr ){
            $songtitle = null;
            $genre = null;
            $program = null;
            $oped = null;
            $date = null;
            $value=$tr->find('td[headers=song]',0);
            if(isset($value)){
                $songtitle = $value->plaintext;
            }
            $value=$tr->find('td[headers=genre]',0);
            if(isset($value)){
                $genre = $value->plaintext;
            }
            $value=$tr->find('td[headers=program]',0);
            if(isset($value)){
                $program = $value->plaintext;
            }
            $value=$tr->find('td[headers=oped]',0);
            if(isset($value)){
                $oped = $value->plaintext;
            }
            $value=$tr->find('td[headers=date]',0);
            if(isset($value)){
                $date = $value->plaintext;
            }

            $result_one = array ('oped' => $oped,
                                   'songtitle' => $songtitle , 
                                   'genre' => $genre , 
                                   'program' => $program,
                                   'oped' => $oped,
                                   'date' => $date
                                   );
            $results[]=$result_one;            
        }
      }        
    }
    return $results;
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

?>

<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">

<title>anison.info検索：曲タイトル検索結果</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>

<body>
<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
<br />
<hr />

<FORM name=f action=search_anisoninfo_list.php method=get>
<INPUT type=radio checked value=pro name=m id="pro" onclick="dsp(1)"><label for="pro">作品</label>
<!---
<INPUT type=radio value=song name=m id="song" onclick="dsp(2)"><label for="song">曲</label>
--->
<INPUT type=radio value=person name=m id="person" onclick="dsp(3)"><label for="person">人物</label>
<INPUT type=radio value=mkr name=m id="mkr" onclick="dsp(5)"><label for="mkr">制作(ブランド)</label>
<!---
<INPUT type=radio value=rec name=m id="rec" onclick="dsp(4)"><label for="rec">音源</label>
<INPUT type=radio value=pgrp name=m id="pgrp" onclick="dsp(6)"><label for="pgrp">関連情報</label>
--->
<BR>
<INPUT  name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?>>
<INPUT type=submit value=検索><BR><BR>

<span id="selectTag">
</span>

</FORM>

<?php
// リクエストに種類もワードもなかった場合のチェック

if(!isset($l_url)  ) {
    echo "<p> 曲情報URLが指定されていません </p>";
}else {
// 検索ワード候補取得部分
   $nexturlbase = 'http://anison.info/data/';
    $list = ansoninfo_gettitlelist($nexturlbase.$l_url,$l_kind);

   //var_dump($list);
    $songnum = 0;
    foreach($list as $value){
        if(!isset($value["songtitle"]) ) continue;
        $songtitles = array();
        $songtitle = replace_obscure_words($value["songtitle"]);
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
}
?>

<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
</body>
</html>
