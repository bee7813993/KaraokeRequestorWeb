<?php

require_once 'kara_config.php';
require_once 'prioritydb_func.php';

date_default_timezone_set('Asia/Tokyo');

$showsonglengthflag = 0;


$user='normal';

if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}


if (isset($_SERVER) && isset($_SERVER["SERVER_ADDR"]) ){
    //var_dump($_SERVER);
    $everythinghost = $_SERVER["SERVER_ADDR"];
    $count_semi = substr_count($everythinghost, ':');
    $count_dot = substr_count($everythinghost, '.');
    if($count_semi > 0 && $count_dot == 0) {
      $everythinghost = addipv6blanket($everythinghost);
    }
} else {
    $everythinghost = 'localhost';
}


function addipv6blanket($ipv6addr){

    if( (mb_substr($ipv6addr,0,1) == '[' ) and (mb_substr($ipv6addr,-1,1) == ']' ) ) {
    return $ipv6addr;
    } else {
    return '['.$ipv6addr.']';
    }

}

/* ビンゴ機能が有効かどうか */
$usebingo=false;
if(array_key_exists("usebingo",$config_ini)){
    if($config_ini["usebingo"]==1 ){
       $usebingo=true;
    }
}

/**
 * createUri
 * 相対パスから絶対URLを返します
 *
 * @param string $base ベースURL（絶対URL）
 * @param string $relational_path 相対パス
 * @return string 相対パスの絶対URL
 * @link http://blog.anoncom.net/2010/01/08/295.html/comment-page-1
 */
function createUri( $base, $relationalPath )
{
     $parse = array(
          "scheme" => null,
          "user" => null,
          "pass" => null,
          "host" => null,
          "port" => null,
          "query" => null,
          "fragment" => null
     );
     $parse = parse_url( $base );
     
     //var_dump($parse);

     if( strpos($parse["path"], "/", (strlen($parse["path"])-1)) !== false ){
          $parse["path"] .= ".";
     }

     if( preg_match("#^https?://#", $relationalPath) ){
          return $relationalPath;
     }else if( preg_match("#^/.*$#", $relationalPath) ){
          return $parse["scheme"] . "://" . $parse["host"] . $relationalPath;
     }else{
          $basePath = explode("/", str_replace('\\', '/', dirname($parse["path"])));
          //var_dump($basePath);
          if(empty($basePath[1] )) {
              unset($basePath[1]);
          }
          $relPath = explode("/", $relationalPath);
          //var_dump($relPath);
          foreach( $relPath as $relDirName ){
               if( $relDirName == "." ){
                    array_shift( $basePath );
                    array_unshift( $basePath, "" );
               }else if( $relDirName == ".." ){
                    array_pop( $basePath );
                    if( count($basePath) == 0 ){
                         $basePath = array("");
                    }
               }else{
                    array_push($basePath, $relDirName);
               }
          }
          //var_dump($basePath);
          $path = implode("/", $basePath);
          //print $path;
          return $parse["scheme"] . "://" . $parse["host"] . $path;
     }

}

function is_valid_url($url)
{
    return false !== filter_var($url, FILTER_VALIDATE_URL) && preg_match('@^https?+://@i', $url);
}

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1, $ipvar = 4 ,$timeoutsec_ms = 0){

    $errno = 0;

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko");
        if($timeoutsec_ms == 0 ){
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutsec);
        }else {
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeoutsec_ms);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeoutsec_ms);
        }
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        //リダイレクト先追従
        //Locationをたどる
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        //最大何回リダイレクトをたどるか
        curl_setopt($ch,CURLOPT_MAXREDIRS,4);
        //リダイレクトの際にヘッダのRefererを自動的に追加させる
        curl_setopt($ch,CURLOPT_AUTOREFERER,true);
        if($ipvar == 6){
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6 );
        }else if($ipvar == 4){
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        }else{
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        }
        $contents = curl_exec($ch);
        //var_dump($contents); //debug
        if( $contents !== false) {
            curl_close($ch);
            break;
        }
        $errno = curl_errno($ch);
        // print $timeoutsec;
        curl_close($ch);
        usleep(100000);
    }
    if ($loopcount === $retrytimes) {
        $error_message = curl_strerror($errno);
        #throw new ErrorException( 'http connection error : '.$error_message . ' url : ' . $url . "\n");
        
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
/*** 外部ファイル ignorecharlist.txt に移動 
  $obscure_list = array(
                     "★",
                     "☆",
                     "？",
                     "?",
                     "×",
                     "!",
                     "！",
                     ':',
                     '：',
                     '~',
                     '・',
                     '*',
                     '_',
                     '&quot;',
                     '&amp;'
                     );
***/                     
  $obscure_list = file ( "ignorecharlist.txt" , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
  // あいまい単語置換(スペースに)
  $resultwords = str_replace($obscure_list,' ',$resultwords);

  // 最後がスペースだったら取り除き
  $resultwords = rtrim($resultwords);

  // 単語が6文字以下の場合クォーテーションをつける
  if(strlen($word) <= 6){
      //$resultwords = '"'.$resultwords.'"';
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
    $exp   = 2;  // MB固定
    $unit  = $units[$exp];
    $bytes = $bytes / pow(1024, floor($exp));
    $bytes = sprintf('%.'.$precision.'f', $bytes);
    return $sign.$bytes.' '.$unit;
}



// プライオリティテーブルの取得
function get_prioritytable()
{
    $rearchedlist_addpriority  = array();
    foreach($rearchedlist["results"] as $k=>$v){
        $onefileinfo = array();
        $onefileinfo += array('path' => $v['path']);
        $onefileinfo += array('name' => $v['name']);
        $onefileinfo += array('size' => $v['size']);
        
        $c_priority = -1;
        foreach($prioritylist as $pk=>$pv){
            $searchres = false;
            if($pv['kind'] == 2 ) {
                $searchres = mb_strstr($v['name'],$pv['priorityword']);
            }else{
                $searchres = mb_strstr($v['path'],$pv['priorityword']);
            }
            if ( $searchres != false ){
                if($c_priority < $pv['prioritynum'] ){
                   $c_priority = $pv['prioritynum'];
                }
            }
        }
        if($c_priority == -1) $c_priority = 50;
        $onefileinfo += array('priority' => $c_priority);
        
        $rearchedlist_addpriority[] = $onefileinfo ;
    }
    
}

function get_globalipv4(){
    // IP取得URL （外部サイト2つ、閉鎖されたら別のものを探す）
    $checkglobakurl = 'http://icanhazip.com';
    
    $myv4ip=file_get_html_with_retry($checkglobakurl,1,1);
    if( $myv4ip !== false)
    return $myv4ip;
    
    $checkglobakurl = 'http://ifconfig.me/ip';
    return file_get_html_with_retry($checkglobakurl,1,1);
}

function check_online_available($host,$timeout = 2){
    global $config_ini;
    if($config_ini["connectinternet"] == 1){
      $checkurl = 'http://'.urldecode($host);
      $ret = file_get_html_with_retry($checkurl,2,4);
      if($ret === false)
        return 'NG';
      else
        return 'OK';
    }
    return "now disabled online";
}

function check_access_from_online(){
    global $config_ini;
    if(array_key_exists("globalhost", $config_ini)) {
        if(strpos ($config_ini["globalhost"],$_SERVER["SERVER_NAME"])!==false){
          return true;
        }
    }
    return false;
    
}

function check_json_available_fromurl($url,$timeout = 10){
    $jsonbase = file_get_html_with_retry($url,5,$timeout);
    $result = json_decode($jsonbase);
    if($result === null ) return false;
    else return true;
}

// 検索ワードからeverything検索件数だけ取得
function count_onepriority($word)
{
    global $everythinghost;
    $jsonurl = 'http://' . $everythinghost . ':81/?search=' . urlencode($word) . '&json=1&count=5';
//print     $jsonurl.'<br/>';
    $json = file_get_html_with_retry($jsonurl, 5, 30);
    $result_array = json_decode($json, true);
    return $result_array['totalResults'];
}

// プライオリティリストからプライオリティ順にしてプライオリティ無指定50を追加
function orderprioritylist($prioritylist){
    array_multisort(array_column($prioritylist, 'prioritynum' ),SORT_DESC,$prioritylist);
    $otherstr = "";
    foreach($prioritylist as $prioritylistone){
        if(empty($otherstr)){
            if($prioritylistone["kind"] == 2 ) {  // file
                $otherstr = '!file:"'.$prioritylistone['priorityword'].'"';
            }else {
                $otherstr = '!path:"'.$prioritylistone['priorityword'].'"';
            }
        }else {
            if($prioritylistone["kind"] == 2 ) {  // file
                $otherstr = $otherstr. ' !file:"'.$prioritylistone['priorityword'].'"';
            }else {
                $otherstr = $otherstr. ' !path:"'.$prioritylistone['priorityword'].'"';
            }
        }
    }
    $i = 0;
    $c_priority = null;
    $newpriorityword = '';
    $newprioritylist = array();
    foreach($prioritylist as $prioritylistone){
        if($prioritylistone['prioritynum'] < 50 ){
            break;
        }
        $i++;
    }
    if(empty($otherstr)){
    return($prioritylist);
    }
    $ndarray = array( 'id' => 999, 'kind' => 1, 'priorityword' => $otherstr, 'prioritynum' => 50 );
    
    //array_splice($prioritylist, $i, 0, array($ndarray));

    $c_priority = null;
    $newpriorityword = '';
    $newprioritylist = array();
    foreach($prioritylist as $prioritylistone){
    
        if($c_priority == $prioritylistone['prioritynum']){
            if($prioritylistone["kind"] == 2 ) {  // file
                $newpriorityword = $newpriorityword.'|file:'.$prioritylistone['priorityword'].'';
            }else {
                $newpriorityword = $newpriorityword.'|path:'.$prioritylistone['priorityword'].'';
            }
        }else {
            if(!empty($newpriorityword)){
                $newprioritylist[] = array( 'prioritynum' => $c_priority, 'priorityword' => '<'.$newpriorityword.'>' );
            }
            $c_priority = $prioritylistone['prioritynum'];
            if($prioritylistone["kind"] == 2 ) {  // file
                $newpriorityword = 'file:'.$prioritylistone['priorityword'].'';
            }else{
                $newpriorityword = 'path:'.$prioritylistone['priorityword'].'';
            }
        }
    }
    $newprioritylist[] = array( 'prioritynum' => $c_priority, 'priorityword' => '<'.$newpriorityword.'>' );
    array_splice($newprioritylist, $i, 0, array($ndarray));
    return $newprioritylist;
}

// 検索ワードからプライオリティ順にして$start件目から$length件取得
function search_order_priority($word,$start,$length)
{
    global $priority_db;
    global $everythinghost;
    
    $currentnum = 0;
    $pickup_array = array();
    $return_array = array();
    $totalcount = count_onepriority($word);
    
    $prioritylist = prioritydb_get($priority_db);
    
    $prioritylist=orderprioritylist($prioritylist);
    
    //  var_dump($prioritylist);
//    die();
    
    $r_length = $length;  // 残要求件数
    $r_start = $start;    // 残件開始位置
    $count_p = $start + 1 ;   // 
    
    $a = 0;
//var_dump($word);
    
    foreach($prioritylist as $prioritylistone){
        $kerwords = ''.$word.' '.$prioritylistone['priorityword'];
        $pcount = count_onepriority($kerwords);  //そのプライオリティの件数
        if($pcount <= 0 ){
            // print '### non P:'.$prioritylistone['prioritynum'].' W:'.$prioritylistone['priorityword']."\n";
            continue;
        }

            // print '#### P:'.$prioritylistone['prioritynum'].' currentnum:'.$currentnum.' r_start:'.$r_start.' pcount:'.$pcount.' r_length:'.$r_length."\n";
        if( ($currentnum <= $r_start ) && ( $currentnum + $pcount ) > $r_start ){
            $c_start = $r_start - $currentnum;
            
            if( ($r_start + $r_length) > ($currentnum + $pcount) ){  // 要求件数が残件を超えている場合
                $c_length = $currentnum + $pcount - $r_start;  // 現在の位置からその優先度の数
                    $r_length = $r_length - $c_length;
                    $currentnum = $currentnum + $pcount;
                    $r_start = $currentnum;
                    
            }else {
                $c_length = $r_length;
                $r_length = 0;
            }
            $order = 'sort=size&ascending=0';
            
            $jsonurl = 'http://' . $everythinghost . ':81/?search=' . urlencode($kerwords) . '&'. $order . '&path=1&path_column=3&size_column=4&case=0&json=1&count=' . $c_length . '&offset=' .$c_start.'';
//print $jsonurl;
            $json = file_get_html_with_retry($jsonurl, 5, 30);
            $result_array = json_decode($json, true);
            // print '###   P:'.$prioritylistone['prioritynum'].' W:'.$prioritylistone['priorityword']."\n";
            // print '##### P:'.$prioritylistone['prioritynum'].' offset:'.$c_start.' count'.$c_length."\n";
            // priority番号追加
            $resultslist_withp = array();
            foreach($result_array['results'] as $v) {
               $resultslist_withp[] =  ( $v + array("pcount" => $count_p ) );
               $count_p++;
            }
            $pickup_array = array_merge ($pickup_array,$resultslist_withp);
            // var_dump($resultslist_withp);
            if($r_length == 0) break;
        }else {
            $currentnum = $currentnum + $pcount;
        }
        
    }
    $return_array = array( "totalResults" => $totalcount , "results" => $pickup_array );
    return $return_array;
    
}



// 検索ワードから検索結果一覧を取得する処理
function searchlocalfilename_part($kerwords, &$result_array,$start = 0, $length = 10, $order = null, $path = null)
{

		global $everythinghost;
		global $config_ini;
        global $priority_db;

        $prioritylist = prioritydb_get($priority_db);
		
		// IPv6check
		// IPv6check
		if(isIPv4($everythinghost)){
			$ipvar = 4;
		}else{
			$ipvar = 6;
		}
		
		$askeverythinghost = $everythinghost;
		
		if(array_key_exists("max_filesize", $config_ini)){
		  if( $config_ini["max_filesize"] > 0 ){
		      $filesizebyte = $config_ini["max_filesize"] * 1024 * 1024;
		      $kwlist=preg_split('/[\s|\x{3000}]+/u', $kerwords);
		      $wordpart = "";
		      foreach($kwlist as $wd){
		          if(!empty($wordpart)) {
		          $wordpart = $wordpart.' ';
		          }
		          $wordpart = $wordpart.'path:'.$wd;
		      }
		      if( !empty($wordpart)) {
		      $kerwords = $wordpart.' size:<='.$filesizebyte;
		      }else {
		      $kerwords = 'path:'.$kerwords.' size:<='.$filesizebyte;
		      }

		      }
		}
		
		$orderstr = 'sort=size&ascending=0';
		//var_dump($order);
		if(empty($prioritylist)){
		    $orderstr = 'sort=size&ascending=0';
		  if(empty($order)){
		    $orderstr = 'sort=size&ascending=0';
		  }else if($order[0]['column']==3  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=size&ascending=1';
		    }else {
		       $orderstr='sort=size&ascending=0';
		    }
		  }else if($order[0]['column']==2  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=name&ascending=1';
		    }else {
		       $orderstr='sort=name&ascending=0';
		    }
		  }else if($order[0]['column']==4  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=path&ascending=1';
		    }else {
		       $orderstr='sort=path&ascending=0';
		    }
		  }
		}else {
		  if(empty($order)){
		    $result_array = search_order_priority($kerwords,$start,$length);
		    return $result_array;
		  }else if($order[0]['column']==3  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=size&ascending=1';
		    }else {
		       $orderstr='sort=size&ascending=0';
		    }
		  }else if($order[0]['column']==2  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=name&ascending=1';
		    }else {
		       $orderstr='sort=name&ascending=0';
		    }
		  }else if($order[0]['column']==4  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=path&ascending=1';
		    }else {
		       $orderstr='sort=path&ascending=0';
		    }
		  }else {
		    $result_array = search_order_priority($kerwords,$start,$length);
		    return $result_array;
		  }
		}
		
        $jsonurl = 'http://' . $everythinghost . ':81/?search=' . urlencode($kerwords) . '&'. $orderstr . '&path=1&path_column=3&size_column=4&case=0&json=1&count=' . $length . '&offset=' .$start.'';

        $json = file_get_html_with_retry($jsonurl, 5, 30, $ipvar);
        $result_array = json_decode($json, true);		
}

function isIPv4($ip) {
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return true;
    }
    return false;
}

function isIPv6($ip) {
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return true;
    }
    return false;
}

// 検索ワードから検索結果一覧を取得する処理
function searchlocalfilename($kerwords, &$result_array,$order = null, $path = null)
{

		global $everythinghost;
		global $config_ini;
		//var_dump($config_ini);
		
		if(empty($order)){
		    $order = 'sort=size&ascending=0';
		}
		// IPv6check
		if(isIPv4($everythinghost)){
			$ipvar = 4;
		}else{
			$ipvar = 6;
		}
	    $askeverythinghost = $everythinghost;
		
		if(array_key_exists("max_filesize", $config_ini)){
		  if( $config_ini["max_filesize"] > 0 ){
		      $filesizebyte = $config_ini["max_filesize"] * 1024 * 1024;
		      $kerwords = $kerwords.' size:<='.$filesizebyte;
		  }
		}
  		$jsonurl = "http://" . $askeverythinghost . ":81/?search=" . urlencode($kerwords) . "&". $order . "&path=1&path_column=3&size_column=4&case=0&json=1";
//  		echo $jsonurl;
//		echo $ipvar;
  		$json = file_get_html_with_retry($jsonurl, 5, 30,$ipvar);
//  		echo $json;
  		$result_array = json_decode($json, true);

}

//検索結果一覧を表示する処理
function printsonglists($result_array, $tableid)
{
		global $everythinghost;
		global $showsonglengthflag;

		$user='normal';
if (isset($_SERVER['PHP_AUTH_USER'])){
    if ($_SERVER['PHP_AUTH_USER'] === 'admin'){
        // print '管理者ログイン中<br>';
        $user=$_SERVER['PHP_AUTH_USER'];
    }
}		
    	if($showsonglengthflag == 1 ){
		$getID3 = new getID3();
		$getID3->setOption(array('encoding' => 'UTF-8'));
		}
		
  		print "<table id=\"$tableid\" class=\"searchresult\" >";
print "<thead>\n";
print "<tr>\n";
print '<th>No. <font size="-2" class="searchresult_comment">(おすすめ順)</font></th>'."\n";
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
    		echo "<tr><td class=\"no\">$k "."</td>";
    		echo "<td class=\"reqbtn\">";
    		echo "<form action=\"request_confirm.php\" method=\"post\" >";
    		echo "<input type=\"hidden\" name=\"filename\" id=\"filename\" value=\"". $v['name'] . "\" />";
    		echo "<input type=\"hidden\" name=\"fullpath\" id=\"fullpath\" value=\"". $v['path'] . "\\" . $v['name'] . "\" />";
    		echo "<input type=\"submit\" value=\"リクエスト\" />";
    		echo "</form>";
    		echo "</td>";
    		echo "<td class=\"filename\">";
    		echo htmlspecialchars($v['name']);
    		if($user == 'admin' ) {
    		    echo "<br/>おすすめ度 :".$v['priority'];
    		}
        $previewpath = "http://" . $everythinghost . ":81/" . $v['path'] . "/" . $v['name'];
            echo "<div Align=\"right\">";
            print make_preview_modal($previewpath,$k);
            echo "</div>";
//    		echo "<Div Align=\"right\"><A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
//    		echo "プレビュー";
//    		echo " </A></Div>";
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

function addpriority($priority_db,$rearchedlist)
{
    $prioritylist = prioritydb_get($priority_db);
    $rearchedlist_addpriority  = array();
    // var_dump($rearchedlist["results"]);
    foreach($rearchedlist["results"] as $k=>$v){
    //print "<br>";
    // var_dump($v);
        $onefileinfo = array();
        $onefileinfo += array('path' => $v['path']);
        $onefileinfo += array('name' => $v['name']);
        $onefileinfo += array('size' => $v['size']);
        if(array_key_exists("pcount", $v)) {
            $onefileinfo += array('pcount' => $v['pcount']);
        }
        
        
        $c_priority = -1;
        foreach($prioritylist as $pk=>$pv){
            $searchres = false;
            if($pv['kind'] == 2 ) {
                $searchres = mb_strstr($v['name'],$pv['priorityword']);
            }else{
                $searchres = mb_strstr($v['path'],$pv['priorityword']);
            }
            if ( $searchres != false ){
                if($c_priority < $pv['prioritynum'] ){
                   $c_priority = $pv['prioritynum'];
                }
            }
        }
        if($c_priority == -1) $c_priority = 50;
        $onefileinfo += array('priority' => $c_priority);
        
        $rearchedlist_addpriority[] = $onefileinfo ;
        //print "<br>";
        //var_dump($rearchedlist_addpriority);
    }
    //print "<br>";
    //var_dump($rearchedlist_addpriority);
    foreach ($rearchedlist_addpriority as $key => $row) {
    //var_dump($key);
    //var_dump($row);
        $priority_s[$key] = $row['priority'];
        $size_s[$key] = $row['size'];
    }
    
    return( array( 'totalResults' => $rearchedlist['totalResults'], 'results' => $rearchedlist_addpriority));
}

function sortpriority($priority_db,$rearchedlist)
{
    $prioritylist = prioritydb_get($priority_db);
    $rearchedlist_addpriority  = array();
    // var_dump($rearchedlist["results"]);
    foreach($rearchedlist["results"] as $k=>$v){
    //print "<br>";
     //var_dump($v);
        $onefileinfo = array();
        $onefileinfo += array('path' => $v['path']);
        $onefileinfo += array('name' => $v['name']);
        $onefileinfo += array('size' => $v['size']);
        
        $c_priority = -1;
        foreach($prioritylist as $pk=>$pv){
            $searchres = false;
            if($pv['kind'] == 2 ) {
                $searchres = mb_strstr($v['name'],$pv['priorityword']);
            }else{
                $searchres = mb_strstr($v['path'],$pv['priorityword']);
            }
            if ( $searchres != false ){
                if($c_priority < $pv['prioritynum'] ){
                   $c_priority = $pv['prioritynum'];
                }
            }
        }
        if($c_priority == -1) $c_priority = 50;
        $onefileinfo += array('priority' => $c_priority);
        
        $rearchedlist_addpriority[] = $onefileinfo ;
        //print "<br>";
        //var_dump($rearchedlist_addpriority);
    }
    //print "<br>";
    //var_dump($rearchedlist_addpriority);
    foreach ($rearchedlist_addpriority as $key => $row) {
    //var_dump($key);
    //var_dump($row);
        $priority_s[$key] = $row['priority'];
        $size_s[$key] = $row['size'];
    }
    // priorityとsizeでsortする。
    array_multisort($priority_s,SORT_DESC,$size_s,SORT_DESC,$rearchedlist_addpriority);
    
    return( array( 'totalResults' => $rearchedlist['totalResults'], 'results' => $rearchedlist_addpriority));
}

// 検索ワードからファイル一覧を表示するまでの処理
function PrintLocalFileListfromkeyword($word,$order = null, $tableid='searchresult')
{
    global $priority_db;
    searchlocalfilename($word,$result_a,$order);
    echo $result_a["totalResults"]."件<br />";
    if( $result_a["totalResults"] >= 1) {
        $result_withp = sortpriority($priority_db,$result_a);
        printsonglists($result_withp,$tableid);
    }
}

function PrintLocalFileListfromkeyword_ajax($word,$order = null, $tableid='searchresult',$bgvmode = 0, $selectid = '')
{
    global $priority_db;
//    searchlocalfilename($word,$result_a,$order);
    searchlocalfilename_part($word,$result_a,0,10,$order);
    if(empty($bgvmode)){
        $bgvmode = 0;
    }
    
    if( $result_a["totalResults"] >= 1) {
       // $result_withp = sortpriority($priority_db,$result_a);
       // echo $result_a["totalResults"]."件<br />";
        // print javascript
//  
  

        $printjs = <<<EOD
  <script type="text/javascript">
$(document).ready(function(){
  var element = document.getElementById( "%s" ) ;
  var rect = element.getBoundingClientRect() ;

  $('#%s').dataTable({
  "processing": true,
  "serverSide": true,
  "ajax": {
      "url": "searchfilefromkeyword_json_part.php",
      "type": "POST",
      "data": { keyword:"%s", bgvmode:%s, selectid:%s },
      "dataType": 'json',
      "dataSrc": "data",
  },
  "drawCallback": function( settings ) {
      $("html,body").animate({scrollTop:rect.top},100);
  },
  "bPaginate" : true,
  "lengthMenu": [[50, 10, 100, 1000], [50, 10, 100, 1000]],
  "bStateSave" : true,
  "stateSaveParams" : function (settings, data) {
    data.start = 0;
  },
  "autoWidth": false,
  "columns" : [
      { "data": "no", "className":"no"},
      { "data": "reqbtn", "className":"reqbtn"},
      { "data": "filename", "className":"filename"},
      { "data": "filesize", "className":"filesize"},
      { "data": "filepath", "className":"filepath"},
  ],
  "sDom": '<"H"lrip>t<"F"ip>',
  columnDefs: [
  { type: 'currency', targets: [3] },
  { "orderable": false , targets: [1]} 
   ],
   }
  );
});
</script>
EOD;
        if(empty($selectid)){
            $selectid = '"none"';
        }
        echo sprintf($printjs,$tableid,$tableid,addslashes($word),$bgvmode,$selectid);
        // print table_base
        $printtablebase = <<<EOD
<table id="%s" class="searchresult">
<thead>
<tr>
<th>No. <font size="-2" class="searchresult_comment">(おすすめ順)</font></th>
<th>リクエスト </th>
<th>ファイル名(プレビューリンク) </th>
<th>サイズ </th>
<th>パス </th>
</tr>
</thead>
<tbody>
</tbody>
</table>
EOD;
        echo sprintf($printtablebase,$tableid);
    }
}


// 検索結果の件数だけ表示する処理
function searchresultcount_fromkeyword($word)
{
    global $priority_db;
    searchlocalfilename($word,$result_a);
    return $result_a["totalResults"];
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
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = \"再生中\" OR nowplaying = \"再生開始待ち\" ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    //var_dump($currentsong);
    if(count($currentsong) == 0){
        return "none";
    }else{
        $player=selectplayerfromextension($currentsong[0]['fullpath']);
    }
    return $player;
}

function getcurrentid(){
    global $db;
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = \"再生中\" ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    //var_dump($currentsong);
    if(count($currentsong) == 0){
        return "none";
    }else{
        $nowid=$currentsong[0]['id'];
    }
    return $nowid;
}

function countafterplayingitem(){
    global $db;
    $curid = getcurrentid();
    if($curid === 'none') return 0;
    $sql = 'SELECT * FROM requesttable  WHERE reqorder >= (SELECT reqorder FROM requesttable WHERE id = '.$curid.');' ;
    $select = $db->query($sql);
    $items = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    //var_dump($currentsong);
    return count($items);
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

function searchwordhistory($word,$filename = 'history.log'){
    date_default_timezone_set('Asia/Tokyo');
    $fp = fopen($filename, 'a');
    $logword = date('r').' '.$word."\r\n";
    fwrite($fp,$logword);
    fclose($fp);
}

// return singer from IP
function singerfromip($rt)
{
    $rt_i = array_reverse($rt);
    foreach($rt_i as $row){
          if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
            if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return $row['singer'];
            }
          }
    }
    return " ";
}

function commentpost_v3($nm,$col,$size,$msg,$commenturl)
{
    $commentmax=256;
    if(mb_strlen($msg) > $commentmax){
         $msg = mb_substr($msg,0,$commentmax);
    }
    
    $POST_DATA = array(
        'nm' => $nm,
        'col' => $col,
        'sz' => $size,
        'msg' => $msg
    );

    $curl=curl_init(($commenturl));
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
    $output= curl_exec($curl);
   
    if($output === false){
        return false;
    }else{
        return true;
    }    
}

function commentpost_v4($cmd,$msg,$commenturl)
{
    // $cmd は 5 文字固定でなくてはならない
    if(mb_strlen($cmd) != 5) {
        return false;
    }

    $POST_DATA = array(
        'cmd' => $cmd,
        'msg' => $msg
    );

    $curl=curl_init(($commenturl));
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
    $output= curl_exec($curl);
   
    if($output === false){
        return false;
    }else{
        return true;
    }    
}

function getallrequest_array(){
    global $db;
    $sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
    $select = $db->query($sql);
    $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    
    return $allrequest;
}

function returnusername($rt){
    if(empty($rt)){
    return "";
    }

    $rt_i = array_reverse($rt);
    foreach($rt_i as $row){
          if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
            if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return $row['singer'];
            }
          }
    }
    
    return "";
}


function returnusername_self(){

    $allrequest = getallrequest_array ();
    return returnusername($allrequest);
}


function shownavigatioinbar($page = 'none', $prefix = '' ){
    global $helpurl;
    global $user;
    global $config_ini;
    global $usebingo;
    
    if($page == 'none') {
        $page = basename($_SERVER["PHP_SELF"]);
    }
    
    print '<nav class="navbar navbar-inverse navbar-fixed-top">';
    


print <<<EOD
  <div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#gnavi">
      <span class="sr-only">メニュー</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
EOD;
    if(multiroomenabled()){
// リンクが存在するかチェック用javascript
         print <<<EOD
<script type="text/javascript">
    function createXMLHttpRequest() {
      if (window.XMLHttpRequest) {
        return new XMLHttpRequest();
      } else if (window.ActiveXObject) {
        try {
          return new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
          try {
            return new ActiveXObject("Microsoft.XMLHTTP");
          } catch (e2) {
            return null;
          }
        }
      } else {
        return null;
      }
    }
    
function _delete_element( id_name ){
    var dom_obj = document.getElementById(id_name);
    var dom_obj_parent = dom_obj.parentNode;
    dom_obj_parent.removeChild(dom_obj);
}

function check_yukari_available ( yukarihost , id ) {
    var http = new createXMLHttpRequest();;
    url = '' + yukarihost + '/check.html';
    http.open("GET", url, false);
    http.send();
    if( http.status != 200 ) {
        _delete_element(id);
    }
    
}

</script>
EOD;
         print '  <ul class="nav navbar-nav navbar-brand-dropdown">';
         print '    <li class="dropdown">';
         
         $displayonece = 0;
         foreach($config_ini["roomurl"] as $key => $value ) {
             if( $displayonece == 0) {
                 print '    <a href="#" class="navbar-brand dropdown-toggle" data-toggle="dropdown" href="">'.$key .'部屋  <b class="caret"></b></a>';
                 print '    <ul class="dropdown-menu">';
                 $displayonece = 1;
             }
             if(!empty($value)  ) {
                 if(array_key_exists("roomurlshow",$config_ini) && array_key_exists($key,$config_ini["roomurlshow"]) &&  $config_ini["roomurlshow"][$key] == 1) {
                 print '      <li id="'.$key.'room" ><a href="'.urldecode($value).'">'.$key.'</a></li>'."\n";
                 }
             }
         }

         print '    </ul>';
         print '    </li>';
         print '    <a class="navbar-brand navbar-search-btn" href="search.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg> 検索</a>';
         print '</ul>';
    }else{
         print '    <a class="navbar-brand navbar-search-btn" href="search.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg> 検索</a>';
    }
    print <<<EOD
  </div>
EOD;
    
    print '<div id="gnavi" class="collapse navbar-collapse">';
    // マイページアイコン変数を準備
    $mypage_active_border = '';
    $mypage_icon_url = 'images/mypage_icon_default.svg';
    if (configbool("usemypage", true)) {
        $mypage_active_border = (strpos($page, 'mypage') === 0) ? 'border:3px solid #fff;' : '';
        $mypage_icon_path = 'images/mypage_icon_default.svg';
        if (!empty($_COOKIE['YkariUserIcon'])) {
            $c = $_COOKIE['YkariUserIcon'];
            // 安全なパスのみ許可
            if (preg_match('#^images/mypage_icons/[a-f0-9\-]+\.\w{2,5}$#', $c) && @file_exists($c)) {
                $mypage_icon_path = $c;
            }
        }
        $mypage_icon_url = htmlspecialchars($mypage_icon_path, ENT_QUOTES, 'UTF-8');
    }
    print '    <ul class="nav navbar-nav">';
    // スマホ用：コラプスメニューの先頭にマイページリンクを表示
    if (configbool("usemypage", true)) {
        print '    <li class="visible-xs">'
            . '<a href="'.$prefix.'mypage.php" style="padding:8px 15px;">'
            . '<img src="'.$mypage_icon_url.'" alt="マイページ" '
            . 'style="width:40px;height:40px;border-radius:50%;vertical-align:middle;'.$mypage_active_border.'">'
            . '&nbsp;マイページ'
            . '</a></li>';
    }



    print '     <li ';
    if($page == 'requestlist_only.php' || $page == 'requestlist_swipe.php' || $page == 'requestlist_top.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'requestlist_top.php">予約一覧 </a></li>';
//    print '     <li ';
    print '     <li class="dropdown "';
    if($page == 'searchreserve.php')
    {
        print 'class="active" ';
    }
        //selectrequestkind();
//    print '><a href="'.$prefix.'searchreserve.php">検索＆予約</a></li>';
    print '><a href="#" class="dropdown-toggle" data-toggle="dropdown" >いろいろ予約 <b class="caret"></b></a>';
         print '    <ul class="dropdown-menu">';
         selectrequestkind($kind='dd',$prefix);
         print '    </ul>';
    print '</li>';

    print '     <li  ';
    if($page == 'playerctrl_portal.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'playerctrl_portal.php" >Player</a></li>';
    // comment 
    if(commentenabledcheck()){
        print '     <li ';
        if($page == 'comment.php')
        {
            print 'class="active" ';
        }
        print '><a href="'.$prefix.'comment.php">コメント</a></li>';
    }
    
    if ($user === 'admin'){
        print '    <p class="navbar-text "> <small>管理者ログイン中</small><br>';
    
        if($page == 'init.php'){
            print '<button type="button" class="btn btn-success" onclick="document.allconfig.submit();" >設定反映</button>';
        }
        print '    </p>';
    }
    
    print '    </ul>';
    print '<style>@media(min-width:768px){.navbar-nav.navbar-right{margin-right:0!important;}}</style>';
    print '    <ul class="nav navbar-nav navbar-right">';
    // PC用：Help等ドロップダウンの右にマイページアイコンを表示（hidden-xs でスマホでは非表示）
    print '    <li class="dropdown">';
    print '    <a href="#" class="dropdown-toggle" data-toggle="dropdown" href="">Help等  <b class="caret"></b></a>';

    print '    <ul class="dropdown-menu">';
    if(!empty($helpurl)){
        print '      <li><a href="'.$helpurl.'">ヘルプ</a></li>';
    }
    print '      <li><a href="'.$prefix.'init.php">設定</a></li>';
    print '      <li><a href="'.$prefix.'toolinfo.php">接続情報表示</a></li>';
    if($usebingo){
        print '      <li><a href="'.$prefix.'bingo_showresult.php">ビンゴ結果表示</a></li>';
    }
    print '     <li ';
    if($page == 'request.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'request.php">全部</a></li>';
    print '      <li class="dropdown-header" > ';
    print get_version();
    print '      </li>';
    print '    </ul>';
    print '    </li>';
    if (configbool("usemypage", true)) {
        print '    <li class="hidden-xs"><a href="'.$prefix.'mypage.php" title="マイページ" class="navbar-mypage-icon">'
            . '<img src="'.$mypage_icon_url.'" alt="マイページ" '
            . 'style="width:40px;height:40px;border-radius:50%;display:inline-block;'.$mypage_active_border.'">'
            . '</a></li>';
    }
    print '    </ul>';
    
//    print '    <p class="navbar-text navbar-right"> <a href="'.$helpurl.'" class="navbar-link">ヘルプ</a> </p>';
    print '</div>';
    print '</nav>';
    
    // 背景色変更 + CSS変数注入
    $injected_vars = [];
    if(array_key_exists("bgcolor",$config_ini)){
        $bg = htmlspecialchars(urldecode($config_ini["bgcolor"]), ENT_QUOTES, 'UTF-8');
        $injected_vars[] = '--bg-page:' . $bg . ';';
    }
    if(array_key_exists("bgimage",$config_ini) && !empty($config_ini["bgimage"])){
        $bgimg = htmlspecialchars(urldecode($config_ini["bgimage"]), ENT_QUOTES, 'UTF-8');
        $injected_vars[] = '--bg-page-image:url(\'' . $bgimg . '\');';
    }
    if(!empty($injected_vars)){
        print '<style>:root{' . implode('', $injected_vars) . '}</style>';
        if(array_key_exists("bgcolor",$config_ini)){
            // Bootstrap 3の古いpages用にJS注入も維持
            print '<script type="text/javascript">document.body.style.backgroundColor="'
                . htmlspecialchars(urldecode($config_ini["bgcolor"]), ENT_QUOTES, 'UTF-8')
                . '";</script>';
        }
    }
}


function shownavigatioinbar_c1($page = 'none'){

    shownavigatioinbar($page, '../');
    
    return true;
    
}

function commentenabledcheck(){

   global $config_ini;

   if(empty($config_ini['commenturl'])) return false;
   if(strcmp($config_ini['commenturl_base'],'notset') == 0 ) return false;

   return true;
}

/**
 * Bootstrap 5 対応の検索画面用 <head> 内リソースを出力する。
 * search.php 等、BS5へ移行した画面からのみ呼び出す。
 * @param string $extra_css 追加で読み込むCSSファイルのパス（相対）
 */
function print_bs5_search_head($extra_css = ''){
    print '<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">';
    print '<link rel="stylesheet" href="css/themes/_variables.css">';
    print '<link rel="stylesheet" href="css/themes/search.css">';
    if(!empty($extra_css)){
        print '<link rel="stylesheet" href="' . htmlspecialchars($extra_css, ENT_QUOTES, 'UTF-8') . '">';
    }
    print '<script src="js/jquery.js"></script>';
    print '<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>';
}

/**
 * searchreserve 相当の予約方法タブHTMLを返す。
 * selectid が指定されている場合は各リンクに引き継ぐ。
 * $current には現在のページ ('search' | 'karaoke' | 'url' | 'pause' | 'bgv') を渡す。
 */
function build_reservation_tabs($selectid = '', $current = 'search', $prefix = ''){
    global $config_ini, $playmode, $user, $connectinternet, $usenfrequset;

    $sid = (is_numeric($selectid) && $selectid !== '') ? '&selectid=' . rawurlencode($selectid) : '';
    $pfx = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');

    $icon_search  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg>';
    $icon_karaoke = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/><path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z"/><path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 1 9.025 8 3.49 3.49 0 0 1 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/></svg>';
    $icon_pause   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/></svg>';
    $icon_url     = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/><path d="M9 5a3 3 0 0 0 0 6h3a3 3 0 0 0 0-6H9zm0 1h3a2 2 0 1 1 0 4H9a2 2 0 0 1 0-4z"/></svg>';
    $icon_bgv     = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6.79 5.093A.5.5 0 0 0 6 5.5v5a.5.5 0 0 0 .79.407l3.5-2.5a.5.5 0 0 0 0-.814l-3.5-2.5z"/><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm15 0a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/></svg>';
    $icon_notfound = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>';

    $tabs = [];

    $tabs[] = [
        'id'    => 'search',
        'label' => 'ファイル検索',
        'icon'  => $icon_search,
        'href'  => $pfx . 'search.php' . ($sid ? '?' . ltrim($sid, '&') : ''),
    ];

    $pm = isset($playmode) ? (int)$playmode : 0;
    if ($pm != 4 && $pm != 5) {
        if (configbool("usehaishin", true)) {
            $tabs[] = [
                'id'    => 'karaoke',
                'label' => 'カラオケ配信',
                'icon'  => $icon_karaoke,
                'href'  => $pfx . 'request_confirm.php?shop_karaoke=1' . $sid,
            ];
        }
        if (configbool("useuserpause", false) || (isset($user) && $user == 'admin')) {
            $tabs[] = [
                'id'    => 'pause',
                'label' => '小休止',
                'icon'  => $icon_pause,
                'href'  => $pfx . 'request_confirm.php?pause=1' . $sid,
            ];
        }
    }

    $ci = isset($connectinternet) ? (int)$connectinternet : (isset($config_ini['connectinternet']) ? (int)$config_ini['connectinternet'] : 0);
    if ($ci == 1) {
        $tabs[] = [
            'id'    => 'url',
            'label' => 'URL指定',
            'icon'  => $icon_url,
            'href'  => $pfx . 'request_confirm_url.php?shop_karaoke=0&set_directurl=1' . $sid,
        ];
    }

    if (isset($config_ini['usebgv']) && $config_ini['usebgv'] == 1 && !empty($config_ini['BGVfolder'])) {
        $tabs[] = [
            'id'    => 'bgv',
            'label' => 'BGV選択',
            'icon'  => $icon_bgv,
            'href'  => $pfx . 'search_bgv.php' . ($sid ? '?' . ltrim($sid, '&') : ''),
        ];
    }

    if (count($tabs) <= 1) return '';

    $html = '<div class="reservation-tabs" role="tablist" aria-label="予約方法">';
    foreach ($tabs as $tab) {
        $active = ($tab['id'] === $current) ? ' active' : '';
        $aria   = ($tab['id'] === $current) ? ' aria-current="page"' : '';
        $html .= '<a href="' . htmlspecialchars($tab['href'], ENT_QUOTES, 'UTF-8') . '"'
               . ' class="reservation-tab-btn' . $active . '"'
               . $aria . '>'
               . $tab['icon'] . ' ' . htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8')
               . '</a>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Bootstrap 5 用ナビバー出力。BS5へ移行した画面（search 系等）から呼ぶ。
 * 既存の shownavigatioinbar(BS3) と並行運用。
 */
function shownavigatioinbar_bs5($page = 'none', $prefix = '') {
    global $helpurl, $user, $config_ini, $usebingo;

    if ($page == 'none') {
        $page = basename($_SERVER["PHP_SELF"]);
    }

    $search_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg>';

    // マイページアイコン準備
    $mypage_active_border = '';
    $mypage_icon_url = 'images/mypage_icon_default.svg';
    if (configbool("usemypage", true)) {
        $mypage_active_border = (strpos($page, 'mypage') === 0) ? 'border:3px solid #fff;' : '';
        $mypage_icon_path = 'images/mypage_icon_default.svg';
        if (!empty($_COOKIE['YkariUserIcon'])) {
            $c = $_COOKIE['YkariUserIcon'];
            if (preg_match('#^images/mypage_icons/[a-f0-9\-]+\.\w{2,5}$#', $c) && @file_exists($c)) {
                $mypage_icon_path = $c;
            }
        }
        $mypage_icon_url = htmlspecialchars($mypage_icon_path, ENT_QUOTES, 'UTF-8');
    }

    print '<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">';
    print '<div class="container-fluid">';

    // 部屋ドロップダウン（マルチルーム時）
    if (multiroomenabled()) {
        print '<ul class="navbar-nav me-2">';
        print '<li class="nav-item dropdown">';
        $displayonece = 0;
        $room_items = '';
        foreach ($config_ini["roomurl"] as $key => $value) {
            if ($displayonece == 0) {
                print '<a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown" aria-expanded="false">'
                    . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '部屋</a>';
                $displayonece = 1;
            }
            if (!empty($value)) {
                if (array_key_exists("roomurlshow", $config_ini) && array_key_exists($key, $config_ini["roomurlshow"]) && $config_ini["roomurlshow"][$key] == 1) {
                    $room_items .= '<li id="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . 'room">'
                                . '<a class="dropdown-item" href="' . htmlspecialchars(urldecode($value), ENT_QUOTES, 'UTF-8') . '">'
                                . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '</a></li>';
                }
            }
        }
        print '<ul class="dropdown-menu">' . $room_items . '</ul>';
        print '</li>';
        print '</ul>';
    }

    // 検索ボタン（常時表示）
    print '<a class="navbar-brand navbar-search-btn" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'search.php">'
        . $search_icon . ' 検索</a>';

    // ハンバーガー
    print '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#gnavi" aria-controls="gnavi" aria-expanded="false" aria-label="メニュー">';
    print '<span class="navbar-toggler-icon"></span>';
    print '</button>';

    print '<div id="gnavi" class="collapse navbar-collapse">';
    print '<ul class="navbar-nav me-auto">';

    // スマホ：先頭にマイページ
    if (configbool("usemypage", true)) {
        print '<li class="nav-item d-md-none">';
        print '<a class="nav-link" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'mypage.php">';
        print '<img src="' . $mypage_icon_url . '" alt="マイページ" style="width:32px;height:32px;border-radius:50%;vertical-align:middle;' . $mypage_active_border . '"> マイページ';
        print '</a></li>';
    }

    // 予約一覧
    $rl_active = ($page == 'requestlist_only.php' || $page == 'requestlist_swipe.php' || $page == 'requestlist_top.php') ? ' active' : '';
    print '<li class="nav-item"><a class="nav-link' . $rl_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'requestlist_top.php">予約一覧</a></li>';

    // いろいろ予約ドロップダウン
    print '<li class="nav-item dropdown">';
    print '<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">いろいろ予約</a>';
    print '<ul class="dropdown-menu">';
    selectrequestkind_bs5_dd($prefix);
    print '</ul>';
    print '</li>';

    // Player
    $pl_active = ($page == 'playerctrl_portal.php') ? ' active' : '';
    print '<li class="nav-item"><a class="nav-link' . $pl_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'playerctrl_portal.php">Player</a></li>';

    // コメント
    if (commentenabledcheck()) {
        $cm_active = ($page == 'comment.php') ? ' active' : '';
        print '<li class="nav-item"><a class="nav-link' . $cm_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'comment.php">コメント</a></li>';
    }

    if (isset($user) && $user === 'admin') {
        print '<li class="nav-item d-flex align-items-center px-2 text-white-50"><small>管理者ログイン中</small></li>';
    }

    print '</ul>'; // me-auto

    // 右側
    print '<ul class="navbar-nav ms-auto align-items-center">';
    print '<li class="nav-item dropdown">';
    print '<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">Help等</a>';
    print '<ul class="dropdown-menu dropdown-menu-end">';
    if (!empty($helpurl)) {
        print '<li><a class="dropdown-item" href="' . htmlspecialchars($helpurl, ENT_QUOTES, 'UTF-8') . '">ヘルプ</a></li>';
    }
    print '<li><a class="dropdown-item" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'init.php">設定</a></li>';
    print '<li><a class="dropdown-item" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'toolinfo.php">接続情報表示</a></li>';
    if (!empty($usebingo)) {
        print '<li><a class="dropdown-item" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'bingo_showresult.php">ビンゴ結果表示</a></li>';
    }
    $rq_active = ($page == 'request.php') ? ' active' : '';
    print '<li><a class="dropdown-item' . $rq_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'request.php">全部</a></li>';
    print '<li><hr class="dropdown-divider"></li>';
    print '<li><span class="dropdown-item-text small text-muted">' . get_version() . '</span></li>';
    print '</ul>';
    print '</li>';

    // PC用マイページアイコン
    if (configbool("usemypage", true)) {
        print '<li class="nav-item d-none d-md-flex align-items-center">';
        print '<a class="nav-link p-1" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'mypage.php" title="マイページ">';
        print '<img src="' . $mypage_icon_url . '" alt="マイページ" style="width:36px;height:36px;border-radius:50%;display:inline-block;' . $mypage_active_border . '">';
        print '</a></li>';
    }

    print '</ul>';
    print '</div>'; // collapse
    print '</div>'; // container-fluid
    print '</nav>';

    // CSS変数注入（BS3版と同じ）
    $injected_vars = [];
    if (array_key_exists("bgcolor", $config_ini)) {
        $bg = htmlspecialchars(urldecode($config_ini["bgcolor"]), ENT_QUOTES, 'UTF-8');
        $injected_vars[] = '--bg-page:' . $bg . ';';
    }
    if (array_key_exists("bgimage", $config_ini) && !empty($config_ini["bgimage"])) {
        $bgimg = htmlspecialchars(urldecode($config_ini["bgimage"]), ENT_QUOTES, 'UTF-8');
        $injected_vars[] = '--bg-page-image:url(\'' . $bgimg . '\');';
    }
    if (!empty($injected_vars)) {
        print '<style>:root{' . implode('', $injected_vars) . '}</style>';
    }
}

/**
 * BS5用「いろいろ予約」ドロップダウンの中身を出力する。
 * 既存 selectrequestkind('dd') と同じ並びを BS5 の dropdown-item クラスで出す。
 */
function selectrequestkind_bs5_dd($prefix = '', $id = '') {
    global $playmode, $connectinternet, $usenfrequset, $config_ini, $user;

    $pfx = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');
    $sid = !empty($id) ? '&selectid=' . rawurlencode($id) : '';

    $items = [];
    $items[] = ['href' => $pfx . 'searchreserve.php' . ($sid ? '?' . ltrim($sid, '&') : ''), 'label' => '検索＆予約MENU'];
    $items[] = ['type' => 'divider'];

    if (!empty($config_ini["limitlistname"][0])) {
        for ($i = 0; $i < count($config_ini["limitlistname"]); $i++) {
            if (empty($config_ini["limitlistname"][$i])) continue;
            $items[] = [
                'href' => $pfx . 'limitlist.php?data=' . rawurlencode($config_ini["limitlistfile"][$i]),
                'label' => $config_ini["limitlistname"][$i],
            ];
        }
        $items[] = ['type' => 'divider'];
    }

    if (!empty($config_ini["usebgv"]) && $config_ini["usebgv"] == 1 && !empty($config_ini["BGVfolder"])) {
        $items[] = ['href' => $pfx . 'search_bgv.php', 'label' => 'BGV選択'];
    }
    $items[] = ['href' => $pfx . 'search.php', 'label' => 'ファイル検索'];

    $pm = isset($playmode) ? (int)$playmode : 0;
    if ($pm != 4 && $pm != 5) {
        if (configbool("usehaishin", true)) {
            $items[] = ['href' => $pfx . 'request_confirm.php?shop_karaoke=1', 'label' => 'カラオケ配信'];
        }
        if (configbool("useuserpause", false) || (isset($user) && $user == 'admin')) {
            $items[] = ['href' => $pfx . 'request_confirm.php?pause=1', 'label' => '小休止'];
        }
    }

    if (!empty($config_ini["downloadfolder"]) && function_exists('check_access_from_online') && (check_access_from_online() === false)) {
        $items[] = ['href' => $pfx . 'file_uploader.php', 'label' => 'ファイル転送'];
    }
    if (function_exists('nicofuncenabled') && nicofuncenabled() === true) {
        $items[] = ['href' => $pfx . 'nicodownload_post.php', 'label' => 'ニコニコ動画'];
    }
    $ci = isset($connectinternet) ? (int)$connectinternet : 0;
    if ($ci == 1) {
        $items[] = ['href' => $pfx . 'request_confirm_url.php?shop_karaoke=0&set_directurl=1', 'label' => 'URL(youtube等)'];
    }
    if (isset($usenfrequset) && $usenfrequset == 1) {
        $items[] = ['href' => $pfx . 'notfoundrequest/notfoundrequest.php', 'label' => '未発見曲報告'];
    }

    foreach ($items as $it) {
        if (isset($it['type']) && $it['type'] === 'divider') {
            print '<li><hr class="dropdown-divider"></li>';
            continue;
        }
        print '<li><a class="dropdown-item" href="' . htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
}


function showmode(){

     global $playmode;

    print '<div align="center" >';
    print '<h4> 現在の動作モード </h4>';

     if($playmode == 1){
     print ("自動再生開始モード: 自動で次の曲の再生を開始します。");
     }elseif ($playmode == 2){
     print ("手動再生開始モード: 再生開始を押すと、次の曲が始まります。(歌う人が押してね)");
     }elseif ($playmode == 4){
     print ("BGMモード: 自動で次の曲の再生を開始します。すべての再生が終わると再生済みの曲をランダムに流します。");
     }elseif ($playmode == 5){
     print ("BGMモード(ランダムモード): 順番は関係なくリストの中からランダムで再生します。");
     }else{
     print ("手動プレイリスト登録モード: 機材係が手動でプレイリストに登録しています。");
     }
     print '</div>';
}

function selectrequestkind($kind='button',$prefix = '', $id='' ){

    global $playmode;
    global $connectinternet;
    global $usenfrequset;
    global $config_ini;

if($kind == 'button'){
print <<<EOD
<div  align="center" >
<form method="GET" action="search.php" >
EOD;
if(!empty($id)){
print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
}
print <<<EOD
<input type="submit" name="曲検索はこちら"   value="曲検索はこちら" class="topbtn btn btn-default btn-lg"/>
</form>
</div>
EOD;
}else if($kind == 'dd'){
print '      <li><a href="'.$prefix.'searchreserve.php">検索＆予約MENU</a></li>';
print '      <li role="separator" class="divider"></li>';
if( !empty($config_ini["limitlistname"][0]) ){
    for($i = 0 ;  $i<count($config_ini["limitlistname"]) ; $i++){
        if(empty($config_ini["limitlistname"][$i])) continue; 
        print '      <li><a href="'.$prefix.'limitlist.php?data='.$config_ini["limitlistfile"][$i].'">'.$config_ini["limitlistname"][$i].'</a></li>';
    }
    print '      <li role="separator" class="divider"></li>';
}

if( $config_ini["usebgv"] == 1 && !empty($config_ini["BGVfolder"]) ){
print '      <li><a href="'.$prefix.'search_bgv.php">BGV選択</a></li>';
}
print '      <li><a href="'.$prefix.'search.php">ファイル検索</a></li>';
}

if ($playmode != 4 && $playmode != 5){
  if (configbool("usehaishin", true) ) {
      if($kind == 'button'){
        print '<div align="center" >';
        print '<form method="GET" action="request_confirm.php?shop_karaoke=1" >';
        print '<input type="hidden" name="shop_karaoke" value="1" />';
        if(!empty($id)){
            print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
        }
        print '<input type="submit" name="配信"   value="カラオケ配信曲を歌いたい場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
        print '</form>';
        print '</div>';
      }else if($kind == 'dd'){
        print '      <li><a href="'.$prefix.'request_confirm.php?shop_karaoke=1">カラオケ配信</a></li>';
      }
  }
  global $user;
  if (configbool("useuserpause", false) || ($user == 'admin' )) {
      if($kind == 'button'){
        print '<div align="center" >';
        print '<form method="GET" action="request_confirm.php?shop_karaoke=1" >';
        print '<input type="hidden" name="pause" value="1" />';
        if(!empty($id)){
            print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
        }
        print '<input type="submit" name="小休止"   value="小休止リクエスト" class="topbtn btn btn-default btn-lg"/> ';
        print '</form>';
        print '</div>';
      }else if($kind == 'dd'){
        print '      <li><a href="'.$prefix.'request_confirm.php?pause=1">小休止</a></li>';
      }
  }
}

if (!empty($config_ini["downloadfolder"]) && (check_access_from_online() === false) ){
  if($kind == 'button'){
    print '<div align="center" >';
    print '<form method="GET" action="file_uploader.php" >';
    if(!empty($id)){
        print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
    }
    print '<input type="submit" name="UPL"   value="手元のファイルを転送して予約する場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
  }else if($kind == 'dd'){
    print '      <li><a href="'.$prefix.'file_uploader.php">ファイル転送</a></li>';
  }
}

if( nicofuncenabled() === true){
  if($kind == 'button'){
    print '<div align="center" >';
    print '<form method="GET" action="nicodownload_post.php" >';
    if(!empty($id)){
        print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
    }
    print '<input type="submit" name="nico"   value="ニコニコ動画ダウンロード予約はこちら" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
  }else if($kind == 'dd'){
    print '      <li><a href="'.$prefix.'nicodownload_post.php">ニコニコ動画</a></li>';
  }
}


if( $connectinternet == 1){
  if($kind == 'button'){
    print '<div align="center" >';
    print '<form method="GET" action="request_confirm_url.php?shop_karaoke=0" >';
    print '<input type="hidden" name="set_directurl" value="1" />';
    if(!empty($id)){
        print '<input type="hidden" name="selectid" value="'.$id.'" />'."\n";
    }
    print '<input type="submit" name="URL"   value="インターネット直接再生はこちらから(Youtube等)" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
  }else if($kind == 'dd'){
    print '      <li><a href="'.$prefix.'request_confirm_url.php?shop_karaoke=0&set_directurl=1">URL(youtube等)</a></li>';
  }
}



if($usenfrequset == 1) {
  if($kind == 'button'){
    print '<div align="center" >';
    print '<form method="GET" action="notfoundrequest/notfoundrequest.php" >';
    print '<input type="submit" name="noffoundsong"   value="見つからなかった曲があればこちらから教えてください" class="topbtn btn btn-default btn-lg"/>';
    print '</form>';
    print '</div>';
  }else if($kind == 'dd'){
    print '      <li role="separator" class="divider"></li>';
    print '      <li><a href="'.$prefix.'notfoundrequest/notfoundrequest.php">未発見曲報告</a></li>';
  }
}

}

function writeconfig2ini($config_ini,$configfile)
{
  $fp = fopen($configfile, 'w');
  foreach ($config_ini as $k => $i){
      if(is_array($i)){
          foreach ($i as $key2 => $item2){
              if(!empty($item2) ) {
              fputs($fp, $k.'['.$key2.']='.$item2."\n");
              }
          }
      }else {
          fputs($fp, "$k=$i\n");
      }
  } 
  fclose($fp);
  if( $configfile == "config.ini" ){
  inieolchange();
  iniroomchange($config_ini);
  }
}

function multiroomenabled(){

 global $config_ini;
 
 $roomcounter = 0;
 foreach($config_ini["roomurl"] as $k => $i){
   if(!empty($i)){
     if(array_key_exists("roomurlshow",$config_ini) && array_key_exists($k,$config_ini["roomurlshow"]) &&  $config_ini["roomurlshow"][$k] == 1) {
        $roomcounter++;
     }
   }

 }
 if($roomcounter > 1) return true;
 
 return false;

}

function nicofuncenabled(){

  global $config_ini;
  global $connectinternet;
  
  if($connectinternet != 1) {
    return false;
  };
  
  if(array_key_exists("nicoid", $config_ini)) {
    $nicologinid = urldecode($config_ini["nicoid"]);
  }
  
  if(array_key_exists("nicopass", $config_ini)) {
    $nicopass = $config_ini["nicopass"];
  }
  
  if(!empty($nicologinid) && !empty($nicopass)) {
    return true;
  }else {
    return false;
  }

}

// 改行コード変換
function convertEOL($string, $to = "\n")
{
    return strtr($string, array(
        "\r\n" => $to,
        "\r" => $to,
        "\n" => $to,
    ));
}

// ini.iniファイル 改行コード変換
function inieolchange($file = 'ini.ini'){

    $fd = fopen($file,'r+');
    if($fd === false ){
        print "ini.ini open failed";
        return;
    }
    
    $str = fread($fd,8192);
    
    $str = convertEOL($str,"\r\n");
    fseek($fd, 0, SEEK_SET);
    fwrite($fd,$str);
    fclose($fd);
}

function is_url($text) {
    if (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $text)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function commenturl_mod($commenturl = 'http://'.'localhost'.'/cms/r.php' ){
    if(! is_url($commenturl)) {
        $commenturl = 'http://'.$_SERVER["SERVER_ADDR"].'/cms/r.php'.$commenturl.' is not commenturl' ;
    }
    $commenturl = preg_replace('/\/r.*\.php/','',$commenturl);
    
    if( $commenturl === null ){
          $commenturl = 'http://'.$_SERVER["SERVER_ADDR"].'/cms/r.php'.'not commenturl';
    }
    
    return $commenturl;

}

// ini.iniファイル room no 変更
function iniroomchange($config_ini,$file = 'ini.ini'){

    $ini_a = array();
    
    $fd = fopen($file,'r+');
    if($fd === false ){
        print "ini.ini open failed";
        return;
    }
    
    while (($buffer = fgets($fd, 4096)) !== false) {
       $ini_a[] = trim($buffer);
    }
    if (!feof($fd)) {
       echo "Error: unexpected fgets() fail\n";
       fclose($fd);
       return;
    }
    $ini_a[0] = $config_ini["commentroom"];
    $ini_a[2] = commenturl_mod(urldecode($config_ini["commenturl_base"]));
    
    fseek($fd, 0, SEEK_SET);
    
    $writebyte = 0;
    
    foreach($ini_a as $oneline){
        $res = fwrite($fd,$oneline."\r\n");
        $writebyte = $writebyte + $res;
    }
    ftruncate($fd,$writebyte);
    fclose($fd);
}

function get_git_version(){
    global $config_ini;
    $result_str = null;
    
    if(array_key_exists("gitcommandpath", $config_ini)){
      $gitcmd = urldecode($config_ini["gitcommandpath"]);
      if(file_exists($gitcmd)){
          $execcmd = $gitcmd.' describe --tags';
      
          $result_str = exec($execcmd);
          if( mb_substr($result_str ,0 ,1) === 'v' ){
              if(is_numeric( mb_substr($result_str ,1 ,1))){
                  $git_version = $result_str;
              }
          }
      }
    }
    
    return $result_str;
}


// バージョン情報
function get_version(){
    
    $localversion = '';

    if(file_exists('version')){
        $localversion = file_get_contents('version');
    }
    
    $gitversion = get_git_version();
    
    if(empty($gitversion)){
        return $localversion;
    }else {
        return $gitversion;
    }
}

function get_gittaglist(&$errmsg = 'none'){

    global $config_ini;
    $taglist = array();
    $errorcnt = 0;
    if(array_key_exists("gitcommandpath", $config_ini)){
      $gitcmd = urldecode($config_ini["gitcommandpath"]);
      if(file_exists($gitcmd)){
          $execcmd = $gitcmd.' config --global core.autoCRLF false';
          exec($execcmd);
          $execcmd = $gitcmd.' fetch origin';
          set_time_limit (900);
          exec($execcmd,$result_str);
          foreach($result_str as $line){
              $err_str_pos = mb_strstr($line, "unable to access");
              if( !$err_str_pos ) {
                  $errmsg .= "network access failed";
                  $errorcnt ++;
              }else if (mb_strstr($line, "fatal") !== false) {
                  $errmsg .= "fetch unknown error: $line";
                  $errorcnt ++;
              }
          }
          if($errorcnt > 0){
              return $taglist;
          }
          
          $execcmd = $gitcmd.' tag';
          exec($execcmd, $result_str);
          foreach($result_str as $line){
            if( mb_substr($line ,0 ,1) === 'v' ){
              if(is_numeric( mb_substr($line ,1 ,1))){
                  $taglist[] = $line;
              }
            }
          }
      }
    }
    
    return $taglist;
}

// memo
// cd c:\xampp\htdocs
// gitcmd\cmd\git config --global core.autoCRLF false
// gitcmd\cmd\git fetch origin
// gitcmd\cmd\git reset --hard origin/master 

function update_fromgit($version_str, &$errmsg){

    global $config_ini;
    $taglist = array();
    $errorcnt = 0;
    if(array_key_exists("gitcommandpath", $config_ini)){
      $gitcmd = urldecode($config_ini["gitcommandpath"]);
      if(file_exists($gitcmd)){
          $execcmd = $gitcmd.' config --global core.autoCRLF false';
          exec($execcmd);
          
          $execcmd = $gitcmd.' fetch origin';
          set_time_limit (900);
          exec($execcmd,$result_str);
          foreach($result_str as $line){
              $err_str_pos = mb_strpos($line, "unable to access");
              if( $err_str_pos !== false ) {
                  $errmsg .= "network access failed";
                  $errorcnt ++;
              }else if (mb_strstr($line, "fatal") !== false) {
                  $errmsg .= "fetch unknown error: $line";
                  $errorcnt ++;
              }
          }
          if($errorcnt > 1){
              return false;
          }

          $execcmd = $gitcmd.' reset --hard '.$version_str;
          exec($execcmd,$result_str);
          foreach($result_str as $line){
              $err_str_pos = mb_strpos($line, "unknown revision");
              if( $err_str_pos  !== false) {
                  $errmsg .= "no version : $version_str";
                  $errorcnt ++;
              }else if (mb_strstr($line, "fatal") !== false) {
                  $errmsg .= "reset --hard unknown error: $line";
                  $errorcnt ++;
              }
          }
      }
    }
    
    if($errorcnt > 0) {
        return false;
    }
    
    return true;
}

function make_preview_modal($filepath, $modalid) {
  global $everythinghost;
  
//  print $filepath;
  
  $dlpathinfo = pathinfo($filepath);
  if(array_key_exists('extension',$dlpathinfo)){
  $filetype = '';
  if($dlpathinfo['extension'] === 'mp4'){
      $filetype = ' type="video/mp4"';
  }else if($dlpathinfo['extension'] === 'flv'){
      $filetype = ' type="video/x-flv"';
  }else if($dlpathinfo['extension'] === 'avi'){
      $filetype = ' type="video/x-msvideo"';
      return null;
  }else {
      return null;
      return "この動画形式はプレビューできません";
  }  
  }else {
      return null;
  }

  $previewpath[] = "http://" . $everythinghost . ":81/" . urlencode($filepath);
  $filepath_url = str_replace('\\', '/', $filepath);
  $previewpath[] = "http://" . $everythinghost . ":81/" . ($filepath_url);
  $button='<a href="#" data-toggle="modal" class="previewmodallink btn btn-default" data-target="#'.$modalid.'" > プレビュー </a>';
  
  $previewsource = "";
   foreach($previewpath as $previewurl ){
     $previewsource = $previewsource.'<source src="'.$previewurl.'" '.$filetype.' />';
   }

$modaljs='<script>
$(function () {
$(\'#'.$modalid.'\').on(\'hidden.bs.modal\', function (event) {
var myPlayer = videojs("preview_video_'.$modalid.'a");
myPlayer.pause();
});
});</script>';

  $modaldg='<!-- 2.モーダルの配置 -->'.
'<div class="modal" id="'.$modalid.'" tabindex="-1">'.
'  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
         <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modal-label">動画プレビュー</h4>
      </div>
      <div class="modal-body">
        <video id="preview_video_'.$modalid.'a" class="video-js vjs-default-skin" controls muted preload="none"  data-setup="{}" style="width: 320px; height: 180px;" >'.$previewsource.'
        </video>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
      </div>
    </div>
  </div>
</div>';

return $button."\n".$modaljs.$modaldg;

}

function basename_jp($path){
    $p_info = explode('\\', $path);
    return end($p_info);
}

/*
 * BR タグを改行コードに変換する
 */
function br2nl($string)
{
    // 大文字・小文字を区別しない
    return preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/i', "\n", $string);
}

function configbool($keyword, $defaultvalue){
    global $config_ini;
    
    $retval = null;
    if(array_key_exists($keyword,$config_ini ) ){
        if( $config_ini[$keyword] == 1 ) {
            $retval = true;
        }else {
            $retval = false;
        }
    }else {
        $retval = $defaultvalue;
    }
    return $retval;
}

function checkbox_check($arr,$word){
    $res = 0;
    foreach($arr as $value){
        if($value === $word) {
            $res = 1;
        }
    }
    return $res;
}

function getphpversion(){
  if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
  }
  return PHP_VERSION_ID;
}

function file_exist_check_japanese_cf($filename){
  $filename_check = $filename;
  if(getphpversion() < 70100 ){
   setlocale(LC_CTYPE, 'Japanese_Japan.932');
   $filename_check =addslashes($filename);
  }
 $fileinfo = @fopen($filename_check,'r');
 if($fileinfo != FALSE){
     fclose($fileinfo);
     // logtocmd 'DEBUG : Success fopen' ;
     return TRUE;
 }
 
 return FALSE;
}

function fileexistcheck($filebasename){
    // ニコカラりすたーで検索
    global $config_ini;
    $lister_dbpath='';
    if (file_exist_check_japanese_cf(urldecode($config_ini['listerDBPATH'])) ){
        $lister_dbpath=urldecode($config_ini['listerDBPATH']);
    }
    require_once('function_search_listerdb.php');
    if(!empty($lister_dbpath) ){
         // DB初期化
         $lister = new ListerDB();
         $lister->listerdbfile = $lister_dbpath;
         $listerdb = $lister->initdb();
         if( $listerdb ) {
             $select_where = ' WHERE found_path LIKE ' . $listerdb->quote('%'.$filebasename.'%');
             $sql = 'select * from t_found '. $select_where.';';
             $alldbdata = $lister->select($sql);
             if($alldbdata){
                  $filepath_utf8 = $alldbdata[0]['found_path'];
                  return $filepath_utf8;
             }
         }
    }
    // Everythingで検索
      $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($filebasename) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
      $json = file_get_html_with_retry($jsonurl, 5);
      if($json != false){
          $decode = json_decode($json, true);
          if($decode != NULL && isset($decode['results']['0'])){
            if(array_key_exists('path',$decode['results']['0']) && array_key_exists('name',$decode['results']['0'])){
                $filepath_utf8 = $decode['results']['0']['path'] . "\\" . $decode['results']['0']['name'];
                return $filepath_utf8;
            }
          }
      }
      return false;

}


function get_fullfilename2($l_fullpath,$word,&$filepath_utf8){
    $filepath_utf8 = "";
    // 引数チェック
    if(empty($l_fullpath) && empty($word) ) return "";
    // ファイル名のチェック
    // logtocmd ("Debug l_fullpath: $l_fullpath\r\n");
    global $config_ini;
    $lister_dbpath='';
    if (file_exist_check_japanese_cf(urldecode($config_ini['listerDBPATH'])) ){
        $lister_dbpath=urldecode($config_ini['listerDBPATH']);
    }
    $winfillpath = mb_convert_encoding($l_fullpath,"SJIS-win");
    $fileinfo=file_exist_check_japanese_cf($winfillpath);
    // logtocmd ("Debug#".$fileinfo);
    if($fileinfo !== FALSE){
        $filepath = $winfillpath;
        $filepath_utf8=$l_fullpath;
    }else{
      $filepath = null;
      // まず フルパス中のbasenameで再検索
      $songbasename = basename($l_fullpath);
      // ニコカラりすたーで検索
      if(!empty($lister_dbpath) ){
         logtocmd ("fullpass file $l_fullpath is not found. Search from NicokaraLister DB.: $songbasename\r\n");
         require_once('function_search_listerdb.php');
         // DB初期化
         $lister = new ListerDB();
         $lister->listerdbfile = $lister_dbpath;
         $listerdb = $lister->initdb();
         if( $listerdb ) {
              $select_where = ' WHERE found_path LIKE ' . $listerdb->quote('%'.$songbasename.'%');
              $sql = 'select * from t_found '. $select_where.';';
              $alldbdata = $lister->select($sql);
              if($alldbdata){
                  $filepath_utf8 = $alldbdata[0]['found_path'];
                  $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
                  logtocmd ($songbasename.'代わりに「'.$filepath_utf8.'」を再生します'."\n");
                  return $filepath;
              }
              // 曲名で再検索
              $select_where = ' WHERE found_path LIKE ' . $listerdb->quote('%'.$word.'%');
              $sql = 'select * from t_found '. $select_where.';';
              $alldbdata = $lister->select($sql);
              if($alldbdata){
                  $filepath_utf8 = $alldbdata[0]['found_path'];
                  $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
                  logtocmd ($word.'代わりに「'.$filepath_utf8.'」を再生します'."\n");
                  return $filepath;
              }
              
         }         
         
      }
      // Everythingで検索
      // logtocmd ("fullpass file $winfillpath is not found. Search from Everything DB.: $songbasename\r\n");
      $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($songbasename) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
      $json = file_get_html_with_retry($jsonurl, 5);
      if($json != false){
          $decode = json_decode($json, true);
          if($decode != NULL && isset($decode['results']['0'])){
            if(array_key_exists('path',$decode['results']['0']) && array_key_exists('name',$decode['results']['0'])){
                $filepath_utf8 = $decode['results']['0']['path'] . "\\" . $decode['results']['0']['name'];
                $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
            }
          }
      }
      if(empty($filepath)){
      // 曲名で再検索
          logtocmd ("fullpass basename $songbasename is not found. Search from Everything DB.: $word\r\n");
          $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
          // logtocmd_cf $jsonurl;
          $json = file_get_html_with_retry($jsonurl, 5);
          if (empty($json)) return false;
          $decode = json_decode($json, true);
          if (empty($decode) || !isset($decode['results']['0']['name'])) return false;
          $filepath = $decode['results']['0']['path'] . "\\" . $decode['results']['0']['name'];
          $filepath_utf8= $filepath;
          $filepath = mb_convert_encoding($filepath,"cp932");
          logtocmd ('代わりに「'.$filepath_utf8.'」を再生します'."\n");
      }
    }
    return $filepath;
}
function logtocmd_cf($msg){
  //print(mb_convert_encoding("$msg\n","SJIS-win"));
  error_log($msg."\n", 3, 'ykrdebug.log');
}

/**
 * マイページ: 「後で歌う」「お気に入り」登録リンクを出力する
 * $fullpath, $songfile は htmlspecialchars せずに渡す (内部でエスケープ)
 */
function mypage_action_links($fullpath, $songfile, $kind = '') {
    global $config_ini;
    if (!configbool("usemypage", true)) return '';

    $fp_enc  = urlencode($fullpath);
    $sf_enc  = urlencode($songfile);
    $k_enc   = urlencode($kind);

    $links = '<span class="mypage-actions" style="font-size:small;">';
    $links .= ' [<a href="mypage_api.php?action=add_later'
            . '&fullpath=' . $fp_enc
            . '&songfile=' . $sf_enc
            . '&kind=' . $k_enc
            . '" onclick="mypageAction(this,\'later\');return false;"'
            . '>後で歌う</a>]';
    $links .= ' [<a href="mypage_api.php?action=add_favorite_song'
            . '&fullpath=' . $fp_enc
            . '&songfile=' . $sf_enc
            . '&kind=' . $k_enc
            . '" onclick="mypageAction(this,\'fav\');return false;"'
            . '>お気に入り</a>]';
    $links .= '</span>';
    return $links;
}

/**
 * マイページ: お気に入り検索ワード保存リンクを出力する
 */
function mypage_save_keyword_link($keyword, $search_type, $search_params = '') {
    global $config_ini;
    if (!configbool("usemypage", true)) return '';
    if (empty(trim($keyword))) return '';

    $url = 'mypage_api.php?action=add_favorite_keyword'
         . '&keyword=' . urlencode($keyword)
         . '&search_type=' . urlencode($search_type)
         . '&search_params=' . urlencode($search_params);

    return ' [<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
         . ' onclick="mypageSaveKeyword(this);return false;"'
         . '>検索ワードを保存</a>]';
}

/**
 * マイページ用 JS (fetch + フィードバック表示) を出力する
 * <head> または <body> 内に一度だけ出力する
 */
function mypage_action_script() {
    if (!configbool("usemypage", true)) return;
    echo <<<'JS'
<script>
function mypageSaveKeyword(el) {
    var url = el.getAttribute('href');
    fetch(url, {method:'GET'})
      .then(function(r){return r.json();})
      .then(function(d){
        var notice = document.createElement('span');
        notice.style.color = '#090';
        notice.textContent = ' (保存しました)';
        el.parentNode.appendChild(notice);
        setTimeout(function(){if(notice.parentNode)notice.parentNode.removeChild(notice);}, 3000);
      })
      .catch(function(){});
}
function mypageAction(el, type) {
    var url = el.getAttribute('href');
    fetch(url, {method:'GET'})
      .then(function(r){return r.json();})
      .then(function(d){
        var msg = (type === 'later') ? '後で歌うに追加しました' : 'お気に入りに追加しました';
        if (d.status === 'removed') {
            msg = (type === 'later') ? '後で歌うから削除しました' : 'お気に入りから削除しました';
        }
        var span = el.parentNode;
        var notice = document.createElement('span');
        notice.style.color = '#090';
        notice.textContent = ' (' + msg + ')';
        span.appendChild(notice);
        setTimeout(function(){if(notice.parentNode)notice.parentNode.removeChild(notice);}, 3000);
      })
      .catch(function(){});
}
</script>
JS;
}
?>
