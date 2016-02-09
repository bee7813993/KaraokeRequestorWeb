<?php

function anisoninfo_display_middlelist($list,$l_m,$l_q,$l_order = NULL)
{
   if(strcmp ('pro',$l_m) == 0)
   {    
    $nexturlbase = 'http://anison.info/data/';
// 曲名からのファイル検索結果表示部分
    if(isset($list['searchword'])){
        print "<p> ".$list['searchword']." の検索結果 </p>\n";
    }else{
        print "<p> $l_q の検索結果 </p>\n";
    }
    echo "<table id=\"searchlistresult\">";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>名前 </th>\n";
    print "<th>ジャンル </th>\n";
    print "<th>時期 </th>\n";
    print "<th>anison.info情報 </th>\n";
    print "</tr>\n";
    print "</thead>\n"; 
    print "<tbody>\n";  
   
   foreach($list as $item){
       if(!isset($item['word'])) continue;
       print "<tr>\n";
       echo '<td class="searchname" >'."\n";
       $l = str_replace('../',"",$item['link']);
       echo '<a href="search_anisoninfo.php?url='.$l.'&kind=program&order='.urlencode($l_order).'">'."\n";
       echo htmlspecialchars($item['word'])."\n";
       echo '</a>'."\n";
       echo "</td>"."\n";
       echo '<td class="genre" >'."\n";
       echo htmlspecialchars($item['genre'])."\n";
       echo "</td>"."\n";
       echo '<td class="onair" >'."\n";
       echo htmlspecialchars($item['onair'])."\n";
       echo "</td>"."\n";
           // 詳細ページ
           echo '<td class="searchname" >'."\n";
           $url="http://anison.info/data/".$l;
           echo '<a href="'.$url.'" target="_blank">'."\n";
           echo '詳細情報'."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
       print "</tr>\n";
   }
   print "</tbody>\n";
   echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    }elseif(strcmp ('person',$l_m) == 0)
    {
        print "<p> $l_q の検索結果 </p>\n";
        echo "<table id=\"searchlistresult\">";
        print "<thead>\n";
        print "<tr>\n";
        print "<th>人物 </th>\n";
        print "<th>anison.info情報 </th>\n";
        print "</tr>\n";
        print "</thead>\n"; 
        print "<tbody>\n";
        foreach($list as $item){
           if(!isset($item['word'])) continue;
           print "<tr>\n";
           echo '<td class="searchname" >'."\n";
           echo '<a href="search_anisoninfo.php?url='.$item['link'].'&kind=artist&order='.urlencode($l_order).'" >'."\n";
           echo htmlspecialchars($item['word'])."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           // 詳細ページ
           echo '<td class="searchname" >'."\n";
           $url="http://anison.info/data/".$item['link'];
           echo '<a href="'.$url.'" target="_blank">'."\n";
           echo '詳細情報'."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           print "</tr>\n";
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
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
        print "<th>anison.info情報 </th>\n";
        print "</tr>\n";
        print "</thead>\n"; 
        print "<tbody>\n";
        foreach($list as $item){
           if(!isset($item['word'])) continue;
           print "<tr>\n";
           echo '<td class="searchname" >'."\n";
           echo '<a href="search_anisoninfo_mkr.php?url='.urlencode($item['link']).'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
           echo htmlspecialchars($item['word'])."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           // 詳細ページ
           echo '<td class="searchname" >'."\n";
           $url="http://anison.info/data/".$item['link'];
           echo '<a href="'.$url.'" target="_blank">'."\n";
           echo '詳細情報'."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           print "</tr>\n";
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    } elseif(strcmp ('song',$l_m) == 0)
    {
    $result_a = null;
        print "<p> $l_q の曲名候補 </p>\n";
        echo "<table id=\"searchlistresult\">";
        print "<thead>\n";
        print "<tr>\n";
        print "<th>曲名 </th>\n";
        print "<th>anison.info情報 </th>\n";
        print "<th>歌手名 </th>\n";
        print "<th>作品名 </th>\n";
        print "</tr>\n";
        print "</thead>\n"; 
        print "<tbody>\n";
        foreach($list as $item){
           if(!isset($item['songtitle'])) continue;
           searchlocalfilename($item['songtitle'],$result_a);
           $resulturl='search.php?searchword='.urlencode($item['songtitle']);
           print "<tr>\n";
           echo '<td class="searchname" >'."\n";
           if(  $result_a["totalResults"] == 0){
             echo ' <div >'.$item['songtitle'].'<br>検索結果  ⇒'.$result_a["totalResults"]."件</div>";
           }else{
                echo ' <div ><a href="'.$resulturl.'" >'.$item['songtitle'].'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
           }
           echo "</td>"."\n";
           // 詳細ページ
           echo '<td class="searchname" >'."\n";
           $url="http://anison.info/data/".$item['songlink'];
           echo '<a href="'.$url.'" target="_blank">'."\n";
           echo '詳細情報'."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           // 歌手名 http://localhost/search_anisoninfo.php?url=person/16363.html&kind=artist&order=
           echo '<td class="searchname" >'."\n";
           $url="search_anisoninfo.php?url=".$item['artistlink']."&kind=artist&order=";
           echo '<a href="'.$url.'" >'."\n";
           echo $item['artist']."\n";
           echo '</a>'."\n";
           echo "</td>"."\n";
           // 作品名 http://localhost/search_anisoninfo.php?url=program/16198.html&kind=program&order=
           echo '<td class="searchname" >'."\n";
           if(empty($item['titlelink'])){
             print $item['title'].' '.$item['oped']."\n";;
           }else{
             $url="search_anisoninfo.php?url=".$item['titlelink']."&kind=program&order=";
             echo '<a href="'.$url.'" >'."\n";
             echo $item['title'].' '.$item['oped']."\n";
             echo '</a>'."\n";
           }
           echo "</td>"."\n";
           print "</tr>\n";
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        print "<p>";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '前の100件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'">'."\n";
            echo '次の100件';
            echo '</a> &nbsp;';
        }
        print "</p>";
    }
}

?>