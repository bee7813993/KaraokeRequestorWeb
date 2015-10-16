<?php
// 変数チェック
require_once 'modules/simple_html_dom.php';
require_once 'commonfunc.php';

$l_kind = null;
if(array_key_exists("kind", $_REQUEST)) {
    $l_kind = urldecode($_REQUEST["kind"]);
}

$l_url = null;
if(array_key_exists("url", $_REQUEST)) {
    $l_url = urldecode($_REQUEST["url"]);
}

$l_order = null;
if(array_key_exists("order", $_REQUEST)) {
    $l_order = urldecode($_REQUEST["order"]);
}



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


?>

<!doctype html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">

<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="js/currency.js"></script>

<script src="js/bootstrap.min.js"></script>

<title>anison.info検索：曲タイトル検索結果</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
</head>

<body>
<?php
shownavigatioinbar('searchreserve.php');
?>


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

<INPUT  name=q <?php if(isset($l_q)) echo 'value="'.$l_q.'"'; ?> class="searchtextbox" >
<!---
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
--->
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
            if(strlen($checktitle) == 0 ) continue;
            if(empty($showallresult)){
                searchlocalfilename($checktitle,$result_a);
                $resulturl='search.php?searchword='.urlencode($checktitle);
                echo '<dl class="dl-horizontal resultwordlist">';
                if(  $result_a["totalResults"] == 0){
                echo ' <dt class="resultwordlist">「'.$checktitle.'」の検索結果 </dt> <dd> ⇒'.$result_a["totalResults"]."件</dd>";
                }else{
                echo ' <dt class="resultwordlist">「'.$checktitle.'」の検索結果 </dt> <dd> <a href="'.$resulturl.'" >⇒'.$result_a["totalResults"].'件 </a></dd>';
                }
                echo '</dl>';

            }else {
                echo "<a name=\"song_".(string)$songnum."\">「".$checktitle."」の検索結果 : </a>&nbsp; &nbsp;  <a href=\"#song_".(string)($songnum + 1)."\" > 次の曲へ </a>";
                PrintLocalFileListfromkeyword_ajax($checktitle,$l_order, 'searchresult'.$songnum);
                echo "<br />";
/*              print "  <script type=\"text/javascript\"> $(document).ready(function(){  $('#".'searchresult'.$songnum."').dataTable({  \"bPaginate\" : false    ,  columnDefs: [  { type: 'currency', targets: [3] }   ] });});  </script> ";  */
/*
            searchlocalfilename($checktitle,$l_order,$result_a);
            echo $result_a["totalResults"]."件<br />";
            if( $result_a["totalResults"] >= 1) {
                printsonglists($result_a);
            }
            //  var_dump($result_a);
*/            
            $songnum = $songnum + 1;
            }
        }
    }
}
?>

<button type="button" onclick="location.href='search.php' " class="btn btn-default " >
通常検索に戻る
</button> 

<button type="button" onclick="location.href='requestlist_only.php' " class="btn btn-default " >
トップに戻る
</button> 
</body>
</html>
