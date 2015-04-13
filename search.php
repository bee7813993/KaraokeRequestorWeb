
<?php
if(array_key_exists("searchword", $_REQUEST)) {
    $word = $_REQUEST["searchword"];
}


if(array_key_exists("order", $_REQUEST)) {
    $l_order = $_REQUEST["order"];
}else{
    $l_order = 'sort=size&ascending=0';
}

require_once 'commonfunc.php';

?>
<!doctype html>
<html lang="ja">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">
  <script type="text/javascript">

    // ここに処理を記述します。
  </script>
  <title>動画検索TOP</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
</head>
<body>
<a href="request.php" >トップに戻る </a>

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
<hr />
<h2>ファイル名検索 </h2>
  <form action="search.php" method="post">
  <table>
  <tr>
  <td> 検索ワード(ファイル名)</td>
  <td> 結果表示順</td>
  </tr>
  <tr>
  <td>
  <input type="text" name="searchword"
  <?php
     if(!empty ($word)){
     print 'value="' . $word . '"';
     }
  ?>
  >
  <td>
  <select name="order">
  <option value="sort=size&ascending=0" selected >サイズ順(大きい順)</option>
  <option value="sort=path&ascending=1">フォルダ名(昇順)</option>
  <option value="sort=path&ascending=0">フォルダ名(降順)</option>
  <option value="sort=name&ascending=1">ファイル名(昇順)</option>
  <option value="sort=name&ascending=0">ファイル名(降順)</option>
  <option value="sort=date_modified&ascending=1">日付(昇順)</option>
  <option value="sort=date_modified&ascending=0">日付(降順)</option>
  </select>
  </td>
  </tr>
  </table>
  <input type="submit" value="検索">

  </form>
  and検索は スペース 区切りでいけるっぽい。<br>
  全件検索は*(半角)でいけるっぽい。<br><br>
  歌手名とかゲーム名では見つからないことが多いので曲名での検索推奨<br>

  <?php
  	if ( empty ($word)){
  		
  	}else {
  		$jsonurl = "http://" . $_SERVER["SERVER_NAME"] . ":81/?search=" . urlencode($word) . "&" . $l_order . "&path=1&path_column=3&size_column=4&json=1";
  		// echo $jsonurl;
  		$json = file_get_html_with_retry($jsonurl, 5);
  		$decode = json_decode($json, true);
        echo "<hr />";
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
		foreach($decode["results"] as $k=>$v)
		{
		if($v['size'] <= 1 ) continue;
//			foreach($decode2 as $k=>$v)
    		echo "<tr><td class=\"no\" >$k</td>";
    		echo "<td class=\"reqbtn\">";
    		echo "<form action=\"request_confirm.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"hidden\" name=\"fullpath\" id=\"fullpath\" value=\"". $v['path'] . "\\" . $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td class=\"filename\">";
    		echo $v['name'];
        $previewpath = "http://" . $_SERVER["HTTP_HOST"] . ":81/" . $v['path'] . "/" . $v['name'];
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
  	echo "<pre>";
//  	var_dump($decode);
  	echo "</pre>";

  	}
  	?>
<hr />
  <h2>外部データベース連携検索 </h2>
  (キーワードでインターネット上のデータベースサイトから曲名を検索し、その曲名でローカルにファイルがあるかを検索)<br>
  (登録されてない曲は見つけられません。)<br>
  (曲名の一部を含む別の曲とかも検索結果に出ちゃいます。ありがちな1単語の曲名だとたくさん結果に出てきてしまうので注意してね)<br>
  (網羅されてない新しい曲とか、特殊文字（★とか）が曲名に入っていると見つからない可能性があるので改めてファイル名検索してみて)
<hr />
  <h3>anison.info連携検索モード </h3>
 
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
<INPUT name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?>>
<INPUT type=submit value=検索><BR><BR>

<span id="selectTag">
</span>

</FORM>
<hr />
  <h3>banditの隠れ家連携検索モード </h3>
  
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
  


<hr>

<a href="request.php" >トップに戻る </a>

</body>
</html>
