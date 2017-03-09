<?php

// URLを叩いて検索ワード候補リクエスト用URL生成
function ansoninfo_gettitlelisturl($m,$q,$fullparam,$year = '', $genre=''){
    if(isset($fullparam)){
        $url="http://anison.info/data/".trim($fullparam,". \t\n\r\0\x0B");
    }else {
        $urlbase="http://anison.info/data/n.php?m=%s&q=%s&year=%s&genre=%s";
        $url=sprintf($urlbase,urlencode($m),$q,$year,$genre);
    }
    return $url;
}

// 人物URLからローカルリスト出力URLの生成
// 歌手名 http://localhost/search_anisoninfo.php?url=person/16363.html&kind=artist&order=
function ansoninfo_getlocalurl_artist($link,$q = null, $selectid = null,$year='',$genre=""){
    $url="search_anisoninfo.php?url=".trim($link,". \t\n\r\0\x0B");
    if(!empty($q)){
      $url=$url.'&q='.$q;
    }
    if(!empty($selectid)){
      $url=$url.'&selectid='.$selectid;
    }
      $url=$url."&kind=artist&order=&year=".$year."&genre=".$genre;
    return $url;
}

// 作品名URLからローカルリスト出力URLの生成
// 歌手名 http://localhost/search_anisoninfo.php?url=person/16363.html&kind=artist&order=
function ansoninfo_getlocalurl_program($link,$q = null, $selectid = null){
    $url="search_anisoninfo.php?url=".trim($link,". \t\n\r\0\x0B");
    if(!empty($q)){
      $url=$url.'&q='.$q;
    }
    if(!empty($selectid)){
      $url=$url.'&selectid='.$selectid;
    }
      $url=$url."&kind=program&order=";
    return $url;
}

function search_maker_from_titlepage($html_dom){
    foreach(  $html_dom->find( 'a' ) as $link){
        if( strpos($link->href,'/maker/') !== false){
            return array( 'maker' => $link -> plaintext, 'makerurl' => $link->href);
        }
    }
}

function get_plaintext_parentonly($html_dom){

$node=$html_dom;

// とりあえず「outertext」を取得
foreach( $node as $v ){
 $all[] = $v->outertext;
}
 
// 親要素の番号を取得
for( $i=0; $i<count($all); $i++ ){
 if( $i === 0){
  $compare = $all[$i];
  $reps[] = $i;
 }else{
  $j = count($reps) - 1;
  // 一つ前の「outertext」内に含まれるかチェック
  if( !strstr( $compare, $all[$i] ) ){
   $compare = $all[$i];
   $reps[] = $i;
  }else{
   $pattern = "/" . $all[$i] . "/u";
   $compare = preg_replace($pattern, "", $compare);
  }
 }
}
 
// 必要な親要素のみ取得
foreach( $reps as $v ){
 $arys[] = $node[$v];
}

// 必要なものを取得：例えば「plaintext」を取得
foreach( $arys as $v ){
 $plaintext[] = $v->plaintext;
}

return $plaintext[0];

}


// URLを叩いて検索ワード候補をarrayで返す。
function ansoninfo_gettitlelist($url,$l_kind,$selectid = null){
    global $l_q;
    $results = array();
    $searchinfo = array();
    
    for($checktimes=0; $checktimes<3; $checktimes++){
        $html = file_get_html_with_retry($url);
        if($html !== FALSE) break;
    }
    if($html === FALSE) return; 
    $result_dom=str_get_html($html);
    
    if(strcmp("program",$l_kind) == 0){
      $title = $result_dom->find( 'div.subject' )[0]->plaintext;
      $searchinfo['title'] = $title;
      $searchinfo['maker'] = search_maker_from_titlepage($result_dom);
      foreach( $result_dom->find( 'table.sorted' ) as $list ){
        foreach( $list->find( 'tr' ) as $tr ){
        
            $oped = null;
            $songtitle = null;
            $artist = null;
            $lyrics = null;
            $compose = null;
            $arrange = null;
            
            $value=$tr->find('td[headers=song]',0);
            if(isset($value)){
                $songtitle = $value->plaintext;
                $songurl = $value->find( 'a' )[0]->href;
            }else{
                continue;
            }
            if(empty($songtitle))continue;
            $value=$tr->find('td[headers=oped]',0);
            if(isset($value)){
                $oped = $value->plaintext;
            }
            $value=$tr->find('td[headers=vocal]',0);
            if(isset($value)){
              
            $artist = $value->plaintext;
            // $artist = get_plaintext_parentonly($value);
            $artistinfo = $value->find( 'a' );
            $artisturl ='';
            foreach($artistinfo as $ainfo){
                $artisturl = $artisturl.'&nbsp;<a href="'.ansoninfo_getlocalurl_artist($ainfo->href,$ainfo->plaintext,$selectid).'">'.$ainfo->plaintext.'</a>';
                $artist = preg_replace('/'.preg_quote($ainfo->plaintext,'/').'/','',$artist);
            }
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
                                   'songurl' => $songurl , 
                                   'artist' => $artist , 
                                   'lyrics' => $lyrics,
                                   'compose' => $compose,
                                   'arrange' => $arrange,
                                   'title' => $title,
                                   'titleurl' => null,
                                   'artisturl' => $artisturl
                                   );
            $results[]=$result_one;            
        }
      }
    }elseif(strcmp("artist",$l_kind) == 0){
      $artist = $result_dom->find( 'div.subject' )[0]->plaintext;
      $searchinfo['artist'] = $artist;
      $artisturl = '';
      foreach( $result_dom->find( 'table.sorted' ) as $list ){
        foreach( $list->find( 'tr' ) as $tr ){
            $songtitle = null;
            $songurl = null;
            $genre = null;
            $program = null;
            $titleurl = null;
            $oped = null;
            $date = null;
            $value=$tr->find('td[headers=song]',0);
            if(isset($value)){
                $songtitle = $value->plaintext;
                $songurl = $value->find( 'a' )[0]->href;
            }
            
            $value=$tr->find('td[headers=genre]',0);
            if(isset($value)){
                $genre = $value->plaintext;
            }
            $value=$tr->find('td[headers=program]',0);
            if(isset($value)){
                $program = $value->plaintext;
                $titleurl ='';
                $titleinfo = $value->find( 'a' );
                foreach($titleinfo as $ainfo){
                    $titleurl = $titleurl.'&nbsp;<a href="'.ansoninfo_getlocalurl_program($ainfo->href,$ainfo->plaintext,$selectid ).'">'.$ainfo->plaintext.'</a>';
                    $program = preg_replace('/'.preg_quote($ainfo->plaintext,'/').'/','',$program);
                }
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
                                   'songurl' => $songurl , 
                                   'genre' => $genre , 
                                   'program' => $program,
                                   'oped' => $oped,
                                   'date' => $date,
                                   'artist' => $artist , 
                                   'artisturl' => $artisturl,
                                   'title' => $program,
                                   'titleurl' => $titleurl
                                   );
            $results[]=$result_one;            
        }
      }        
    }
    $result_dom->clear();
    return array( 'result' => $results, 'searchinfo' => $searchinfo) ;
}




function anisoninfo_display_middlelist($list,$l_m,$l_q,$l_order = NULL,$selectid = NULL,$year='',$genre='')
{
   if(strcmp ('pro',$l_m) == 0)
   {    
    $nexturlbase = 'http://anison.info/data/';
// 曲名からのファイル検索結果表示部分
    if(isset($list['searchword'])){
        print "<p> ".$list['searchword'];
    }else{
        print "<p> $l_q ";
    }
    if(!empty($year))
      print $year."年";
    if(!empty($genre))
      print "ジャンル：".$genre;
    print " の検索結果 </p>\n";

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
       echo '<a href="'.ansoninfo_getlocalurl_program($l,$item['word'],$selectid).urlencode($l_order);
       if(!empty($selectid) ) print '&selectid='.$selectid;
       echo '">'."\n";
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
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    }elseif(strcmp ('person',$l_m) == 0)
    {
        print "<p> $l_q ";
        if(!empty($year))
          print $year."年";
        if(!empty($genre))
          print "ジャンル：".$genre;
        print " の検索結果 </p>\n";
        
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


           echo '<a href="'.ansoninfo_getlocalurl_artist($item['link'],$item['word'],$selectid,$year,$genre).urlencode($l_order);
           if(!empty($selectid) ) print '&selectid='.$selectid;
           echo '">'."\n";
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
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    } elseif(strcmp ('mkr',$l_m) == 0)
    {
        print "<p> $l_q の検索結果 </p>\n";
        print "<dl class=\"dl-horizontal\"  >\n";
        print "<dt style=width:300px; >制作会社 </dt>\n";
        print "<dd>anison.info情報 </dd>\n";
        foreach($list as $item){
           if(!isset($item['word'])) continue;
           echo '<dt class="searchname" style=width:300px; >'."\n";
           echo '<a href="search_anisoninfo_mkr.php?url='.urlencode($item['link']).'&q='.$l_q.'&order='.urlencode($l_order);
           if(!empty($selectid) ) print '&selectid='.$selectid;
           echo '">'."\n";
           echo htmlspecialchars($item['word'])."\n";
           echo '</a>'."\n";
           echo "</dt>"."\n";
           // 詳細ページ
           echo '<dd >'."\n";
           $url="http://anison.info/data/".$item['link'];
           echo '[<a href="'.$url.'" target="_blank">'."\n";
           echo '詳細情報'."\n";
           echo '</a>]'."\n";
           echo "</dd>"."\n";
        }
        print "</dl\">\n";
        echo "<hr />\n";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '前の50件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '次の50件';
            echo '</a> &nbsp;';
        }
    } elseif(strcmp ('song',$l_m) == 0)
    {
    $result_a = null;
        print "<p> $l_q の曲名候補 </p>\n";
        echo "<table id=\"searchlistresult\" class=\"table\" >";
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
            $songtitles = variation_titlelist($item);
            if(count($songtitles) == 0)continue;
            foreach($songtitles as $checktitle){
                global $config_ini;
                searchlocalfilename($checktitle,$result_a);
                $resulturl='search.php?searchword='.urlencode($checktitle);
                if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                if(strlen($checktitle) == 0 ) continue;
                print "<tr>\n";
                echo '<td class="resultcount" >'."\n";
                if( $result_a["totalResults"] == 0){
                    echo ' <div >'.$checktitle.'<br>検索結果  ⇒'.$result_a["totalResults"]."件</div>";
                }else{
                    echo ' <div ><a href="'.$resulturl.'" >'.$checktitle.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                }
                $limno = 15;
                if(array_key_exists('anisoninfomanynumber',$config_ini) ){
                   $limno = $config_ini['anisoninfomanynumber'];
                }
                if ( $result_a["totalResults"] > $limno ){
                    $sword = $checktitle.' '.$item["title"];
                    searchlocalfilename($sword,$result_a);
                    $resulturl='search.php?searchword='.urlencode($sword);
                    if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                    if( $result_a["totalResults"] > 0){
                        echo ' <div ><a href="'.$resulturl.'" >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                    }else {
                        echo ' <div >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </div>';
                    }
                    $sword = $checktitle.' '.$item["artist"];
                    searchlocalfilename($sword,$result_a);
                    $resulturl='search.php?searchword='.urlencode($sword);
                    if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                    if( $result_a["totalResults"] > 0){
                        echo ' <div ><a href="'.$resulturl.'" >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                    }else {
                        echo ' <div >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </div>';
                    }
                }
                echo "</td>"."\n";
/*****
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
******/
                // 詳細ページ
                echo '<td class="searchname" >'."\n";
                $url="http://anison.info/data/".$item['songlink'];
                echo '<a href="'.$url.'" target="_blank">'."\n";
                echo '詳細情報'."\n";
                echo '</a>'."\n";
                echo "</td>"."\n";
                // 歌手名 http://localhost/search_anisoninfo.php?url=person/16363.html&kind=artist&order=
                echo '<td class="searchname" >'."\n";
                $url=ansoninfo_getlocalurl_artist($item['artistlink'],$item['artist'],$selectid); // "search_anisoninfo.php?url=".$item['artistlink']."&kind=artist&q=".$l_q."&order=";
                echo '<a href="'.$url.'" >'."\n";
                echo $item['artist']."\n";
                echo '</a>'."\n";
                echo "</td>"."\n";
                // 作品名 http://localhost/search_anisoninfo.php?url=program/16198.html&kind=program&order=
                echo '<td class="searchname" >'."\n";
                if(empty($item['titlelink'])){
                  print $item['title'].' '.$item['oped']."\n";;
                }else{
                  $url=ansoninfo_getlocalurl_program($item['titlelink'],$item['title'],$selectid);
                  echo '<a href="'.$url.'" >'."\n";
                  echo $item['title'].' '.$item['oped']."\n";
                  echo '</a>'."\n";
                }
                echo "</td>"."\n";
                print "</tr>\n";
             }
        }
        print "</tbody>\n";
        echo "</table>"."\n";
        echo "<hr />\n";
        print "<p>";
        if(isset($list["prevlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["prevlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '前の100件';
            echo '</a> &nbsp;';
        }
        if(isset($list["nextlink"])){
            echo '<a href="search_anisoninfo_list.php?fullparam='.urlencode($list["nextlink"]).'&m='.$l_m.'&q='.$l_q.'&order='.urlencode($l_order).'&year='.$year.'&genre='.$genre.'">'."\n";
            echo '次の100件';
            echo '</a> &nbsp;';
        }
        print "</p>";
    }
}

function variation_titlelist ($onelist){
  $songtitles = array();
  if(!isset($onelist["songtitle"]) ) return $songtitles;
  $orgsongtitle = $onelist["songtitle"];

  // 元のワード
  $songtitles[]=$orgsongtitle;
  // 記号文字を排除
  $songtitle_tmp= replace_obscure_words($orgsongtitle);
  $same = 0;
  foreach($songtitles as $checktitle){
      if(strcmp($checktitle ,$songtitle_tmp) == 0) $same = 1;
  }
  if($same === 0) $songtitles[] = $songtitle_tmp;
  // 全部全角にしたときのチェック
  $songtitle_tmp = mb_convert_kana($orgsongtitle,"A");
  $same = 0;
  foreach($songtitles as $checktitle){
      if(strcmp($checktitle ,$songtitle_tmp) == 0) $same = 1;
  }
  if($same === 0) $songtitles[] = $songtitle_tmp;
  // 全部半角にしたときのチェック
  $songtitle_tmp = mb_convert_kana($orgsongtitle,"a");
  $same = 0;
  foreach($songtitles as $checktitle){
      if(strcmp($checktitle ,$songtitle_tmp) == 0) $same = 1;
  }
  if($same === 0) $songtitles[] = $songtitle_tmp;
  return $songtitles;
}

function anisoninfo_display_finallist($list,$nexturlbase,$selectid = NULL)
{
    // Table header
    echo "<table id=\"searchlistresult\" class=\"table\" >";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>曲名検索件数 </th>\n";
    print "<th>曲名 </th>\n";
    print "<th>作品名 </th>\n";
    print "<th>歌手名 </th>\n";
    print "<th>anison.info情報 </th>\n";
    print "</tr>\n";
    print "</thead>\n"; 
    print "<tbody>\n";  

    foreach($list as $value){
        $songtitles = variation_titlelist($value);
        if(count($songtitles) == 0)continue;

/****
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
*****/

        foreach($songtitles as $checktitle){
            if(strlen($checktitle) == 0 ) continue;
            if(empty($showallresult)){
                global $config_ini;
                
                searchlocalfilename($checktitle,$result_a);
                $resulturl='search.php?searchword='.urlencode($checktitle);
                if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                if(strlen($checktitle) == 0 ) continue;
                print "<tr>\n";
                echo '<td class="resultcount" >'."\n";
                if( $result_a["totalResults"] == 0){
                    echo ' <div >'.$checktitle.'<br>検索結果  ⇒'.$result_a["totalResults"]."件</div>";
                }else{
                    echo ' <div ><a href="'.$resulturl.'" >'.$checktitle.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                }
                $limno = 15;
                if(array_key_exists('anisoninfomanynumber',$config_ini) ){
                   $limno = $config_ini['anisoninfomanynumber'];
                }
                if ( $result_a["totalResults"] > $limno ){
                    $sword = $checktitle.' '.$value["title"];
                    searchlocalfilename($sword,$result_a);
                    $resulturl='search.php?searchword='.urlencode($sword);
                    if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                    if( $result_a["totalResults"] > 0){
                        echo ' <div ><a href="'.$resulturl.'" >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                    }else {
                        echo ' <div >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </div>';
                    }
                    $sword = $checktitle.' '.$value["artist"];
                    searchlocalfilename($sword,$result_a);
                    $resulturl='search.php?searchword='.urlencode($sword);
                    if(!empty($selectid)) $resulturl=$resulturl.'&selectid='.$selectid;
                    if( $result_a["totalResults"] > 0){
                        echo ' <div ><a href="'.$resulturl.'" >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </a></div>';
                    }else {
                        echo ' <div >'.$sword.'<br>検索結果  ⇒'.$result_a["totalResults"].'件 </div>';
                    }
                }
                echo "</td>"."\n";
                echo '<td class="songtitle" >'."\n";
                print ' '. $checktitle;
                echo "</td>"."\n";
                echo '<td class="title" >'."\n";
                print ' '. $value["title"];
                print $value["titleurl"];
                if(array_key_exists("oped",$value)){
                     print $value["oped"];
                }
                echo "</td>"."\n";
                echo '<td class="artist" >'."\n";
                print ' '. $value["artist"];
                print $value["artisturl"];
                echo "</td>"."\n";
                echo '<td class="anisoninfo" >'."\n";
                if(array_key_exists("songurl",$value)){
                  print '<a href="http://anison.info/data/'.trim($value["songurl"],". \t\n\r\0\x0B").'" >詳細情報 </a>';
                }
                echo "</td>"."\n";
                print "</tr>\n";

            }
        }
    }
    print "</tbody>\n";
    echo "</table>"."\n";


}


?>