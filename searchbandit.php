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
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="2" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームタイトル検索 
  <form action="searchbandit.php" method="GET" style="display: inline"/>
  <input type="text" name="searchword" class="searchtextbox" >
  <input type="hidden" name="column" value="3" />
  <input type="submit" value="検索">
  </form>
  <br />
  ゲームブランド検索 
  <form action="searchbandit.php" method="GET" style="display: inline" />
  <input type="text" name="searchword" class="searchtextbox" >
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
              PrintLocalFileListfromkeyword($checktitle,'sort=size&ascending=0');
/*
              searchlocalfilename($checktitle,'sort=size&ascending=0',$result_a);
              echo $result_a["totalResults"]."件<br />";
              if( $result_a["totalResults"] >= 1) {
                printsonglists($result_a);
              }
            //  var_dump($result_a);
*/
              $songnum = $songnum + 1;
        }
    }



?>

<a href="search.php" >通常検索に戻る </a>
&nbsp; 
<a href="request.php" >トップに戻る </a>
</body>
</html>
