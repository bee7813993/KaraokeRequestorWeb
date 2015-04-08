<?php
// 変数チェック
require_once 'modules/simple_html_dom.php';

$l_m = 'pro';
if(array_key_exists("m", $_REQUEST)) {
    $l_m = $_REQUEST["m"];
}
$l_q = null;
if(array_key_exists("q", $_REQUEST)) {
    $l_q = $_REQUEST["q"];
}
$l_fullparam = null;
if(array_key_exists("url", $_REQUEST)) {
    $l_url = urldecode($_REQUEST["url"]);
}


// 検索ワード候補表示画面

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
function ansoninfo_gettitlelist($url,$l_m){
    $results = array();
    
    $result_dom=file_get_html($url);
    
    if(strcmp ('pro',$l_m) == 0)
    {
        foreach( $result_dom->find( 'table.sorted' ) as $list ){
            foreach( $list->find( 'tr' ) as $tr ){
              // genre get
              $genre = $tr->find('td[headers=genre]',0);
              if(empty($genre)){
                  $genre = "";
              }
              //$genre->plaintext;
              foreach( $tr->find( 'a' ) as $list_a )
              {
                  $linkpath=$list_a->href;
                  $foundword=$list_a->plaintext;
              }
              $onair = $tr->find('td[headers=year]',0);
              if(!isset($foundword)) continue;
              $result_one = array ('genre' => $genre->plaintext,
                                   'word' => $foundword , 
                                   'link' => $linkpath , 
                                   'onair' => $onair->plaintext);
              $results[]=$result_one;
            }
        }
        $prevlink_td = $result_dom->find( 'td.seekPrev',0 );
        if(isset($prevlink_td))
        {
            $prevlink = $prevlink_td->find('a' ,0)->href;
            $results['prevlink']= $prevlink;
        }
        $nextlink_td = $result_dom->find( 'td.seekNext',0 );
        if(isset($nextlink_td))
        {
            $nextlink=$nextlink_td->find('a' ,0)->href;
            $results['nextlink']=$nextlink;
        }
        $searchword = $result_dom->find( 'div.subject',0 );
        if(isset($searchword))
        {
            $results['searchword']=$searchword->plaintext;
        }
        
    }elseif(strcmp ('person',$l_m) == 0)
    {
        foreach( $result_dom->find( 'table.list' ) as $list ){
            foreach( $list->find( 'td.list' ) as $p_lists ){
                $foundword = $p_lists->plaintext;
                $linkpath  = $p_lists->find('a',0)->href;
                
                $result_one = array (
                                'word' => $foundword , 
                                'link' => $linkpath  
                                );
                $results[]=$result_one;
            }
        }
        $prevlink_td = $result_dom->find( 'td.seekPrev',0 );
        if(isset($prevlink_td))
        {
            $prevlink = $prevlink_td->find('a' ,0)->href;
            $results['prevlink']= $prevlink;
        }
        $nextlink_td = $result_dom->find( 'td.seekNext',0 );
        if(isset($nextlink_td))
        {
            $nextlink=$nextlink_td->find('a' ,0)->href;
            $results['nextlink']=$nextlink;

        }
    } elseif(strcmp ('mkr',$l_m) == 0)
    {
        foreach( $result_dom->find( 'table.list' ) as $list ){
            foreach( $list->find( 'tr' ) as $m_lists ){
             $mkrinfo = $m_lists->find('a', 0 );
             if(!isset($mkrinfo)) continue;
                $linkpath  = $mkrinfo->href;
                $foundword = $mkrinfo->plaintext;
                $result_one = array (
                                'word' => $foundword , 
                                'link' => $linkpath  
                                );
                $results[]=$result_one;
            }
        }
        $prevlink_td = $result_dom->find( 'td.seekPrev',0 );
        if(isset($prevlink_td))
        {
            $prevlink = $prevlink_td->find('a' ,0)->href;
            $results['prevlink']= $prevlink;
        }
        $nextlink_td = $result_dom->find( 'td.seekNext',0 );
        if(isset($nextlink_td))
        {
            $nextlink=$nextlink_td->find('a' ,0)->href;
            $results['nextlink']=$nextlink;
        }
    } 
    
    return $results;
}

// URLを叩いて曲名リストをarrayで返す。
function ansoninfo_getsongtitles(){
    $result = array();
    
    return $result;
}

?>

<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=width,initial-scale=1.0,minimum-scale=1.0">

<title>anison.info検索メーカータイトル一覧</title>
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
    echo "<p> メーカーURLが指定されていません </p>";
}else {
// 検索ワード候補取得部分
   $nexturlbase = 'http://anison.info/data/';
    $list = ansoninfo_gettitlelist($nexturlbase.$l_url,$l_m);

   //var_dump($list);

   if(strcmp ('pro',$l_m) == 0)
   {    
// 曲名からのファイル検索結果表示部分
    if(isset($list['searchword'])){
      print "<p> ".$list['searchword']." の検索結果 </p>\n";
    }
    echo "<table id=\"searchlistresult\">";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>名前 </th>\n";
    print "<th>ジャンル </th>\n";
    print "<th>時期 </th>\n";
    print "</tr>\n";
    print "</thead>\n"; 
    print "<tbody>\n";  
   
   foreach($list as $item){
       if(!isset($item['word'])) continue;
       print "<tr>\n";
       echo '<td class="searchname" >'."\n";
       echo '<a href="search_anisoninfo.php?url=/maker/'.$item['link'].'&kind=program">'."\n";
       echo $item['word']."\n";
       echo '</a>'."\n";
       echo "</td>"."\n";
       echo '<td class="genre" >'."\n";
       echo $item['genre']."\n";
       echo "</td>"."\n";
       echo '<td class="onair" >'."\n";
       echo $item['onair']."\n";
       echo "</td>"."\n";
       print "</tr>\n";
   }
   print "</tbody>\n";
   echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    }elseif(strcmp ('person',$l_m) == 0)
    {
//        print "<p> $l_q の検索結果 </p>\n";
        echo "<table id=\"searchlistresult\">";
        print "<thead>\n";
        print "<tr>\n";
        print "<th>人物 </th>\n";
        print "</tr>\n";
        print "</thead>\n"; 
        print "<tbody>\n";
        foreach($list as $item){
           if(!isset($item['word'])) continue;
           print "<tr>\n";
           echo '<td class="searchname" >'."\n";
           echo '<a href="search_anisoninfo.php?url='.$item['link'].'&kind=artist">'."\n";
           echo $item['word']."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           print "</tr>\n";
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    } elseif(strcmp ('mkr',$l_m) == 0)
    {
        print "<p> $l_q の検索結果 </p>\n";
        echo "<table id=\"searchlistresult\">";
        print "<thead>\n";
        print "<tr>\n";
        print "<th>制作会社 </th>\n";
        print "</tr>\n";
        print "</thead>\n"; 
        print "<tbody>\n";
        foreach($list as $item){
           if(!isset($item['word'])) continue;
           print "<tr>\n";
           echo '<td class="searchname" >'."\n";
           echo '<a href="search_anisoninfo_mkr.php?url='.$item['link'].'">'."\n";
           echo $item['word']."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           print "</tr>\n";
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    }
}


?>
