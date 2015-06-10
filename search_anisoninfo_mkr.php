<?php
// 変数チェック
require_once 'modules/simple_html_dom.php';
require_once 'search_anisoninfo_common.php';
require_once 'commonfunc.php';

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

$l_order = null;
if(array_key_exists("order", $_REQUEST)) {
    $l_order = urldecode($_REQUEST["order"]);
}

// 検索ワード候補表示画面


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


?>

<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">

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
<INPUT type=radio <?php print selectedcheck("pro",$l_m)=='selected'?'checked':' '; ?> value=pro name=m id="pro" onclick="dsp(1)"><label for="pro">作品</label>
<!---
<INPUT type=radio <?php print selectedcheck("song",$l_m)=='selected'?'checked':' '; ?> value=song name=m id="song" onclick="dsp(2)"><label for="song">曲</label>
--->
<INPUT type=radio <?php print selectedcheck("person",$l_m)=='selected'?'checked':' '; ?> value=person name=m id="person" onclick="dsp(3)"><label for="person">人物</label>
<INPUT type=radio <?php print selectedcheck("mkr",$l_m)=='selected'?'checked':' '; ?> value=mkr name=m id="mkr" onclick="dsp(5)"><label for="mkr">制作(ブランド)</label>
<!---
<INPUT type=radio value=rec name=m id="rec" onclick="dsp(4)"><label for="rec">音源</label>
<INPUT type=radio value=pgrp name=m id="pgrp" onclick="dsp(6)"><label for="pgrp">関連情報</label>
--->
<BR>
<INPUT  name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?> class="searchtextbox" >
  <div> 結果表示順(同じ検索ワード内) <br>
  <select name="order" class="searchtextbox" >
  <option value="sort=size&ascending=0" <?php print selectedcheck("sort=size&ascending=0",$l_order); ?> >サイズ順(大きい順)</option>
  <option value="sort=path&ascending=1" <?php print selectedcheck("sort=path&ascending=1",$l_order); ?> >フォルダ名(降順 A→Z)</option>
  <option value="sort=path&ascending=0" <?php print selectedcheck("sort=path&ascending=0",$l_order); ?> >フォルダ名(昇順 Z→A)</option>
  <option value="sort=name&ascending=1" <?php print selectedcheck("sort=name&ascending=1",$l_order); ?> >ファイル名(降順 A→Z)</option>
  <option value="sort=name&ascending=0" <?php print selectedcheck("sort=name&ascending=0",$l_order); ?> >ファイル名(昇順 Z→A)</option>
  <option value="sort=date_modified&ascending=0" <?php print selectedcheck("sort=date_modified&ascending=0",$l_order); ?> >日付(新しい順)</option>
  <option value="sort=date_modified&ascending=1" <?php print selectedcheck("sort=date_modified&ascending=1",$l_order); ?> >日付(古い順)</option>
  </select>
  </div>
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
    
    anisoninfo_display_middlelist($list,$l_m,$l_q,$l_order);


   //var_dump($list);

}
?>
</body>
</html>
