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

function get_everything_ipvar() {
    global $everythinghost;
    return isIPv4($everythinghost) ? 4 : 6;
}

function decode_everything_json($json, $url = '') {
    if ($json === false || $json === '') {
        error_log('Everything JSON request failed: ' . $url);
        return null;
    }
    $result = json_decode($json, true);
    if (!is_array($result)) {
        error_log('Everything JSON decode failed: ' . $url);
        return null;
    }
    return $result;
}

// 検索ワードからeverything検索件数だけ取得
function count_onepriority($word)
{
    global $everythinghost;
    $jsonurl = 'http://' . $everythinghost . ':81/?search=' . urlencode($word) . '&json=1&count=5';
    $ipvar = get_everything_ipvar();
    $json = file_get_html_with_retry($jsonurl, 5, 30, $ipvar);
    $result_array = decode_everything_json($json, $jsonurl);
    if (!is_array($result_array) || !isset($result_array['totalResults'])) {
        return 0;
    }
    return (int) $result_array['totalResults'];
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
function search_order_priority($word,$start,$length,$order = 'sort=size&ascending=0')
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
            $jsonurl = 'http://' . $everythinghost . ':81/?search=' . urlencode($kerwords) . '&'. $order . '&path=1&path_column=3&size_column=4&case=0&json=1&count=' . $c_length . '&offset=' .$c_start.'';
//print $jsonurl;
            $json = file_get_html_with_retry($jsonurl, 5, 30, get_everything_ipvar());
            $result_array = decode_everything_json($json, $jsonurl);
            // print '###   P:'.$prioritylistone['prioritynum'].' W:'.$prioritylistone['priorityword']."\n";
            // print '##### P:'.$prioritylistone['prioritynum'].' offset:'.$c_start.' count'.$c_length."\n";
            // priority番号追加
            $resultslist_withp = array();
            if (is_array($result_array) && isset($result_array['results']) && is_array($result_array['results'])) {
            foreach($result_array['results'] as $v) {
               $resultslist_withp[] =  ( $v + array("pcount" => $count_p ) );
               $count_p++;
            }
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
function searchlocalfilename_part($kerwords, &$result_array,$start = 0, $length = 10, $order = null, $path = null, $use_priority = true)
{

		global $everythinghost;
		global $config_ini;
        global $priority_db;

        $prioritylist = $use_priority ? prioritydb_get($priority_db) : [];
		
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
		  }else if(is_string($order)){
		    $orderstr = $order;
		  }else if(isset($order[0]['column']) && $order[0]['column']==4  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=size&ascending=1';
		    }else {
		       $orderstr='sort=size&ascending=0';
		    }
		  }else if(isset($order[0]['column']) && $order[0]['column']==2  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=name&ascending=1';
		    }else {
		       $orderstr='sort=name&ascending=0';
		    }
		  }else if(isset($order[0]['column']) && $order[0]['column']==5  ){
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
		  }else if(is_string($order)){
		    $result_array = search_order_priority($kerwords,$start,$length,$order);
		    return $result_array;
		  }else if(isset($order[0]['column']) && $order[0]['column']==4  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=size&ascending=1';
		    }else {
		       $orderstr='sort=size&ascending=0';
		    }
		  }else if(isset($order[0]['column']) && $order[0]['column']==2  ){
		    if($order[0]['dir']=='asc'){
		       $orderstr='sort=name&ascending=1';
		    }else {
		       $orderstr='sort=name&ascending=0';
		    }
		  }else if(isset($order[0]['column']) && $order[0]['column']==5  ){
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
      { "data": "worker", "className":"worker"},
      { "data": "filesize", "className":"filesize"},
      { "data": "filepath", "className":"filepath"},
  ],
  "sDom": '<"H"lrip>t<"F"ip>',
  columnDefs: [
  { type: 'currency', targets: [4] },
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
<th>動画制作者 </th>
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
if( is_url($filepath) ){
    return "mpc";
}
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
        // kind に「URL指定」を含む場合は mpc 固定
        if( mb_stristr($currentsong[0]['kind'], 'URL指定') !== FALSE ){
            return "mpc";
        }
        // fullpath が空の場合は songfile で判定
        $path = !empty($currentsong[0]['fullpath']) ? $currentsong[0]['fullpath'] : $currentsong[0]['songfile'];
        $player=selectplayerfromextension($path);
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
    // iPhone の safe area まで背景・UI を拡張できるよう viewport-fit=cover を付与する。
    print '<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover" />';
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
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_TIMEOUT, 2);
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
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_TIMEOUT, 2);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
    $output= curl_exec($curl);

    if($output === false){
        return false;
    }else{
        return true;
    }
}

function notify_requestlist_update(){
    ob_start();
    try {
        require_once __DIR__.'/function_updatenotice.php';
        $un = new UpdateNotice(); $un->initdb();
        if ($un->db !== null){ $un->updaterequestlist(); $un->closedb(); }
    } catch (Throwable $e){ error_log('requestlist update notice failed: '.$e->getMessage()); }
    $out = ob_get_clean();
    if ($out !== '') error_log('requestlist update notice output: '.trim($out));
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
    if($page == 'playerctrl_portal.php' || $page == 'playerctrl_portal_bs5.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'playerctrl_portal_top.php" >Player</a></li>';
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
    print_bg_style_block();
    if(array_key_exists("bgcolor",$config_ini)){
        // Bootstrap 3の古いpages用にJS注入も維持
        print '<script type="text/javascript">document.body.style.backgroundColor="'
            . htmlspecialchars(urldecode($config_ini["bgcolor"]), ENT_QUOTES, 'UTF-8')
            . '";</script>';
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
    $page_css = ['css/themes/search.css'];
    if(!empty($extra_css)){
        $page_css[] = $extra_css;
    }
    print_bs5_head_core($page_css, ['jquery' => true]);
}

/**
 * css/themes/skins/ 配下の外観プリセット(テーマ)定義ファイルを走査し、一覧を返す。
 * 各ファイルは CSS変数の上書きのみで構成する（詳細は css/themes/skins/*.css 参照）。
 * ファイル先頭の "Theme Name: xxx" コメントを表示名として使う（省略時はファイル名）。
 * ファイルを1つ追加するだけで一覧・設定画面に反映され、コード変更は不要。
 *
 * @return array スラッグ => ['name' => 表示名, 'file' => 相対パス]
 */
function get_ui_skin_list(){
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $list = [];
    $paths = glob(__DIR__ . '/css/themes/skins/*.css');
    if (is_array($paths)) {
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $slug = basename($path, '.css');
            if ($slug === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
                continue; // ファイル名がスラッグとして安全でないものは除外
            }
            $name = $slug;
            $head = @file_get_contents($path, false, null, 0, 2048);
            if ($head !== false && preg_match('/Theme\s*Name\s*:\s*([^\r\n*]+)/u', $head, $m)) {
                $name = trim($m[1]);
            }
            $list[$slug] = ['name' => $name, 'file' => 'css/themes/skins/' . $slug . '.css'];
        }
    }
    return $cache = $list;
}

/**
 * 現在の外観プリセット(スキン)のスラッグを返す。
 * 'default'（標準・上書きなし）は css/themes/skins/ に対応ファイルを置かない特別値。
 * 未設定・空欄・存在しないスラッグは自動的に 'default' へフォールバックする。
 */
function get_ui_skin_preset($value = null){
    if ($value === null) {
        global $config_ini;
        $value = $config_ini['ui_skin_preset'] ?? 'default';
    }

    if (is_array($value)) {
        return 'default';
    }

    $preset = trim(urldecode((string)$value));
    if ($preset === '' || $preset === 'default') {
        return 'default';
    }
    if (!array_key_exists($preset, get_ui_skin_list())) {
        return 'default';
    }

    return $preset;
}

function get_ui_skin_card_opacity($value = null){
    if ($value === null) {
        global $config_ini;
        $value = $config_ini['ui_skin_card_opacity'] ?? 100;
    }

    if (is_array($value) || $value === '') {
        return 100;
    }

    $opacity = (int)$value;
    if ($opacity < 0) $opacity = 0;
    if ($opacity > 100) $opacity = 100;
    return $opacity;
}

/**
 * BS5ページ共通の <head> 核を出力する。
 * テーマ初期化スクリプト（FOUC防止）+ Bootstrap5 + テーマ変数 + テーマ切替 を
 * 一括出力し、ページ固有のCSSばらつきを排除する。
 *
 * @param array $page_css ページ固有CSSのhref配列（search.css / player.css / style.css 等）。
 *                        テーマ変数の後・theme-toggle.css の前に挿入される。
 * @param array $opts     'jquery' => true で jQuery を読み込む（既定: 読み込まない）。
 */
function print_bs5_head_core($page_css = [], $opts = []){
    // テーマ初期適用: フラッシュ防止のため head の最初に実行する
    print '<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}})();</script>';
    print '<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">';
    print '<link rel="stylesheet" href="css/themes/_variables.css">';
    foreach ((array)$page_css as $href){
        if($href === '') continue;
        print '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
    }
    print '<link rel="stylesheet" href="css/themes/theme-toggle.css">';
    // 外観プリセット: 'default'（標準）は何も読み込まず、既存の見た目のまま。
    // 非標準プリセットのときだけ、テーマ本体（変数定義）+ 共通コンポーネント適用ルールを読み込む。
    // theme-toggle.css より後に置くこと（theme-toggle の [data-theme="dark"] 既定パレットを
    // スキン側のダークモード変数が上書きできるようにするため）。
    // id はプレビューJS（init.php）が <link> を差し替えるために使う。
    $skin = get_ui_skin_preset();
    if ($skin !== 'default') {
        $skin_list = get_ui_skin_list();
        if (isset($skin_list[$skin]['file'])) {
            print '<style>:root{--ykr-skin-card-opacity:' . (get_ui_skin_card_opacity() / 100) . ';}</style>';
            print '<link rel="stylesheet" id="ykr-skin-css" href="' . htmlspecialchars($skin_list[$skin]['file'], ENT_QUOTES, 'UTF-8') . '">';
            print '<link rel="stylesheet" id="ykr-skin-components-css" href="css/themes/skin-components.css">';
        }
    }
    if(!empty($opts['jquery'])){
        print '<script src="js/jquery.js"></script>';
    }
    print '<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>';
    print '<script src="js/theme-toggle.js"></script>';
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
    $icon_list    = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>';
    $icon_upload  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path fill-rule="evenodd" d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>';
    $icon_nico    = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V1zm4 0v6h8V1H4zm8 8H4v6h8V9zM1 1v2h2V1H1zm2 3H1v2h2V4zM1 7v2h2V7H1zm2 3H1v2h2v-2zm-2 3v2h2v-2H1zM15 1h-2v2h2V1zm-2 3v2h2V4h-2zm2 3h-2v2h2V7zm-2 3v2h2v-2h-2zm2 3h-2v2h2v-2z"/></svg>';

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
                'href'  => $pfx . 'request_confirm_bs5.php?shop_karaoke=1' . $sid,
            ];
        }
        if (configbool("useuserpause", false) || (isset($user) && $user == 'admin')) {
            $tabs[] = [
                'id'    => 'pause',
                'label' => '小休止',
                'icon'  => $icon_pause,
                'href'  => $pfx . 'request_confirm_bs5.php?pause=1' . $sid,
            ];
        }
    }

    $ci = isset($connectinternet) ? (int)$connectinternet : (isset($config_ini['connectinternet']) ? (int)$config_ini['connectinternet'] : 0);
    if ($ci == 1) {
        $tabs[] = [
            'id'    => 'url',
            'label' => 'URL指定',
            'icon'  => $icon_url,
            'href'  => $pfx . 'request_confirm_url_bs5.php?shop_karaoke=0&set_directurl=1' . $sid,
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

    // ピックアップ曲リスト（旧「いろいろ予約」より移動。設定された数だけタブを追加）
    if (!empty($config_ini["limitlistname"][0])) {
        for ($i = 0; $i < count($config_ini["limitlistname"]); $i++) {
            if (empty($config_ini["limitlistname"][$i])) continue;
            $tabs[] = [
                'id'    => 'limitlist' . $i,
                'label' => $config_ini["limitlistname"][$i],
                'icon'  => $icon_list,
                'href'  => $pfx . 'limitlist.php?data=' . rawurlencode($config_ini["limitlistfile"][$i]),
            ];
        }
    }

    // ファイル転送（旧「いろいろ予約」より移動）
    // kara_config.php が未設定時に $_SERVER["TMP"] をデフォルト挿入するため、
    // TMP 値と一致する場合は「未設定」とみなし表示しない。
    $df_decoded = !empty($config_ini["downloadfolder"]) ? urldecode($config_ini["downloadfolder"]) : '';
    $df_is_explicit = $df_decoded !== '' && rtrim($df_decoded, '\\') !== rtrim($_SERVER["TMP"], '\\');
    if ($df_is_explicit && function_exists('check_access_from_online') && (check_access_from_online() === false)) {
        $tabs[] = [
            'id'    => 'upload',
            'label' => 'ファイル転送',
            'icon'  => $icon_upload,
            'href'  => $pfx . 'file_uploader.php',
        ];
    }

    // ニコニコ動画（旧「いろいろ予約」より移動）
    if (function_exists('nicofuncenabled') && nicofuncenabled() === true) {
        $tabs[] = [
            'id'    => 'nico',
            'label' => 'ニコニコ動画',
            'icon'  => $icon_nico,
            'href'  => $pfx . 'nicodownload_post.php',
        ];
    }

    if (count($tabs) <= 1) return '';

    $html = '<div class="reservation-tabs" role="tablist" aria-label="リクエスト方法">';
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

    // print_bs5_search_head() を使わないページ向けに CSS/JS を動的注入（重複読込を防ぐガード付き）
    $pfx_js = addslashes(htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'));
    print '<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}var p="' . $pfx_js . '";if(!document.querySelector("link[href*=\'theme-toggle.css\']")){var l=document.createElement("link");l.rel="stylesheet";l.href=p+"css/themes/theme-toggle.css";document.head.appendChild(l);}if(!document.getElementById("yk-theme-js")){var s=document.createElement("script");s.id="yk-theme-js";s.src=p+"js/theme-toggle.js";document.head.appendChild(s);}})();</script>';

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

    // リクエスト一覧
    $rl_active = ($page == 'requestlist_only.php' || $page == 'requestlist_swipe.php' || $page == 'requestlist_top.php') ? ' active' : '';
    print '<li class="nav-item"><a class="nav-link' . $rl_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'requestlist_top.php">リクエスト一覧</a></li>';

    // Player
    $pl_active = ($page == 'playerctrl_portal.php' || $page == 'playerctrl_portal_bs5.php') ? ' active' : '';
    print '<li class="nav-item"><a class="nav-link' . $pl_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'playerctrl_portal_top.php">Player</a></li>';

    // コメント
    if (commentenabledcheck()) {
        $cm_active = ($page == 'comment.php') ? ' active' : '';
        print '<li class="nav-item"><a class="nav-link' . $cm_active . '" href="' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . 'comment.php">コメント</a></li>';
    }

    if (isset($user) && $user === 'admin') {
        print '<li class="nav-item d-flex align-items-center px-2 text-white-50" style="font-size:12px;">管理者ログイン中</li>';
        if ($page === 'init.php') {
            print '<li class="nav-item">'
                . '<button type="button" class="btn btn-success btn-sm"'
                . ' onclick="document.allconfig.submit();">設定反映</button>'
                . '</li>';
        }
    }

    print '</ul>'; // me-auto

    // 右側
    print '<ul class="navbar-nav ms-auto align-items-center gap-1">';

    // ダークモード切り替えボタン
    print '<li class="nav-item">';
    print '<button id="yk-theme-btn" type="button" class="navbar-theme-btn"'
        . ' title="ダークモードに切り替え" aria-label="ダークモードに切り替え" aria-pressed="false">';
    // 初期アイコン（JS が上書きするが、JS 無効環境向けに月アイコンを設置）
    print '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/></svg>';
    print '</button>';
    print '</li>';

    // 文字サイズ切り替えボタン
    print '<li class="nav-item">';
    print '<button id="yk-fontsize-btn" type="button" class="navbar-theme-btn"'
        . ' title="文字を大きくする" aria-label="文字を大きくする" aria-pressed="false">';
    print '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.244 13.081l.943-2.803H6.66l.944 2.803H8.86L5.54 3.75H4.322L1 13.081h1.244zm2.7-7.923L6.34 9.314H3.51l1.4-4.156h.034zm9.146 7.027h.035v.896h1.128V8.125c0-1.51-1.114-2.345-2.646-2.345-1.736 0-2.59.916-2.666 2.174h1.108c.068-.718.595-1.19 1.517-1.19.971 0 1.518.52 1.518 1.464v.731H12.19c-1.647.007-2.522.8-2.522 2.058 0 1.319.957 2.18 2.345 2.18 1.06 0 1.75-.56 2.078-1.133zm-1.763.035c-.752 0-1.456-.397-1.456-1.244 0-.65.424-1.115 1.408-1.115h1.805v.834c0 .896-.752 1.525-1.757 1.525z"/></svg>';
    print '</button>';
    print '</li>';

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
    print_bg_style_block(true);
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
    print '<input type="submit" name="UPL"   value="手元のファイルを転送してリクエストする場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
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

function hex_to_rgb_triplet($hex, $fallback = '248, 236, 224') {
    if (!is_string($hex)) return $fallback;
    $h = ltrim(trim($hex), '#');
    if (strlen($h) === 3) {
        $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $h)) return $fallback;
    return hexdec(substr($h,0,2)).', '.hexdec(substr($h,2,2)).', '.hexdec(substr($h,4,2));
}

function print_bg_style_block($is_bs5 = false) {
    global $config_ini;

    $vars = [];
    $bgcolor_hex = '#F8ECE0';
    if (array_key_exists("bgcolor", $config_ini)) {
        $bgcolor_hex = urldecode($config_ini["bgcolor"]);
        $bg = htmlspecialchars($bgcolor_hex, ENT_QUOTES, 'UTF-8');
        $vars[] = '--bg-page:' . $bg . ';';
    }
    $vars[] = '--bg-page-rgb:' . hex_to_rgb_triplet($bgcolor_hex) . ';';

    // 背景画像機能は BS5 ページのみ対象。BS3 ページでは $has_bgimage を false のまま維持する。
    $has_bgimage = false;
    $bgimg_url = '';
    $bgimg_mobile_url = '';
    if ($is_bs5 && array_key_exists("bgimage", $config_ini) && !empty($config_ini["bgimage"])) {
        $bgimg_url = htmlspecialchars(urldecode($config_ini["bgimage"]), ENT_QUOTES, 'UTF-8');
        $vars[] = '--bg-page-image:url(\'' . $bgimg_url . '\');';
        $has_bgimage = true;
    }
    if ($is_bs5 && array_key_exists("bgimage_mobile", $config_ini) && !empty($config_ini["bgimage_mobile"])) {
        $bgimg_mobile_url = htmlspecialchars(urldecode($config_ini["bgimage_mobile"]), ENT_QUOTES, 'UTF-8');
    }
    // スマホ用が未設定なら PC 用画像で代替する。
    if ($has_bgimage) {
        $mobile_css_url = ($bgimg_mobile_url !== '') ? $bgimg_mobile_url : $bgimg_url;
        $vars[] = '--bg-page-image-mobile:url(\'' . $mobile_css_url . '\');';
    }

    $overlay_alpha = 1.0;
    if (array_key_exists("bg_overlay_opacity", $config_ini)) {
        $v = (int)$config_ini["bg_overlay_opacity"];
        if ($v < 0) $v = 0; if ($v > 100) $v = 100;
        $overlay_alpha = $v / 100.0;
    }
    $card_alpha = 1.0;
    if (array_key_exists("bg_card_opacity", $config_ini)) {
        $v = (int)$config_ini["bg_card_opacity"];
        if ($v < 0) $v = 0; if ($v > 100) $v = 100;
        $card_alpha = $v / 100.0;
    }
    $vars[] = '--bg-overlay-alpha:' . $overlay_alpha . ';';
    $vars[] = '--bg-card-alpha:' . $card_alpha . ';';

    print '<style>:root{' . implode('', $vars) . '}';

    if ($has_bgimage) {
        // html::before = 固定背景画像レイヤー。
        // iOS Safari は body の background-attachment:fixed をサポートしていないため、
        // position:fixed の疑似要素で代替することで全ブラウザ・全デバイスで背景を固定する。
        // iPhone Safari はブラウザUI背面に root 背景色を使うことがあるため、
        // html 側には常にページ背景色を持たせて白抜けを防ぐ。body は透過のままにする。
        print 'html{background-color:var(--bg-page, #F8ECE0) !important;}body{background-color:transparent !important;}';
        // iOS Safari は visual viewport の高さ変化(URLバー表示/非表示)に合わせて
        // inset:0 の fixed 要素を伸縮させるため、background-size:cover が再計算されて
        // 背景が拡大縮小して見える。100lvh ベースの固定高に切り替える。
        // 注意: env(safe-area-inset-bottom) はバー表示中=0px ↔ 格納時=約34px と
        // 動的に変わるため、サイズ計算に含めると伸縮が再発する。オーバースキャン分は
        // env() を使わず固定マージン(上下80px/左右40px)で確保する。
        print 'html::before{content:"";position:fixed;'
            . 'top:-80px;left:-40px;'
            . 'width:calc(100vw + 80px);'
            . 'height:calc(100vh + 160px);'
            . 'height:calc(100lvh + 160px);'
            . 'z-index:-2;pointer-events:none;filter:none !important;'
            . 'background-image:var(--bg-page-image);background-repeat:no-repeat;'
            . 'background-size:cover;background-position:center center;'
            . 'transform:translate3d(0,0,0);-webkit-transform:translate3d(0,0,0);'
            . '-webkit-backface-visibility:hidden;backface-visibility:hidden;}';
        // スマホ縦持ち(縦長表示)のときだけ専用画像に切り替える。
        // orientation:portrait を条件に加えることで、小型端末を横持ちにした際
        // (幅が 768px 以下のままでも)は PC 用の横長画像が使われるようにする。
        print '@media (max-width:768px) and (orientation:portrait){html::before{background-image:var(--bg-page-image-mobile);}}';
        // body や外部 CSS (search.css / player.css / mypage インラインスタイル) が
        // body に background-image を設定すると html::before を隠してしまうため、
        // body 側の画像指定は明示的に無効化して html::before レイヤーに一本化する。
        print 'body{background-image:none !important;}';
        // body::before = オーバーレイ色レイヤー。画像レイヤー(z-index:-2)の上に重ねる。
        // ダークモードの brightness フィルタが背景画像に影響しないよう filter:none を指定。
        print 'body::before{content:"";position:fixed;'
            . 'top:-80px;left:-40px;'
            . 'width:calc(100vw + 80px);'
            . 'height:calc(100vh + 160px);'
            . 'height:calc(100lvh + 160px);'
            . 'z-index:-1;pointer-events:none;background-image:none !important;'
            . 'background-color:rgba(var(--bg-page-rgb, 248, 236, 224), var(--bg-overlay-alpha, 1));'
            . 'filter:none !important;}';
        // ダークモード時はユーザー指定の明るい bgcolor をそのままオーバーレイに使うと
        // 違和感が出るため、暗色オーバーレイに置き換える。透過率は --bg-overlay-alpha を共用。
        print '[data-theme="dark"] body::before{'
            . 'background-color:rgba(18, 18, 30, var(--bg-overlay-alpha, 1));}';
        // 下記の計測スクリプトが固定px高を確定できた場合は vh/lvh 指定より優先する。
        // (JS無効時は data-ykr-bgfix が付かず、上の lvh フォールバックがそのまま使われる)
        print 'html[data-ykr-bgfix="1"]::before{height:var(--ykr-bg-fixed-h);}';
        print 'html[data-ykr-bgfix="1"] body::before{height:var(--ykr-bg-fixed-h);}';
        // === バー開閉時に動くビューポート辺はブラウザごとに異なるため、
        //     アンカー辺を UA に応じて切り替える(下記スクリプトが属性を付与) ===
        // - iOS Safari: 下辺のみ動く → 上端アンカー(既定)で完全静止
        // - Android Chrome 等: 上辺のみ動く(下端=画面下端で不動) → 下端アンカーで静止
        // - iOS Chrome 系: 上下両方動くためどのアンカーでも多少残る。実機比較の結果、
        //   上端アンカー(既定)が最小だったため専用分岐は持たない。
        //   ?bganchor=top|center|bottom で上書きして再比較できる。
        print 'html[data-ykr-bganchor="center"]::before,'
            . 'html[data-ykr-bganchor="center"] body::before{'
            . 'top:50%;'
            . 'transform:translate3d(0,-50%,0);-webkit-transform:translate3d(0,-50%,0);}';
        print 'html[data-ykr-bganchor="bottom"]::before,'
            . 'html[data-ykr-bganchor="bottom"] body::before{'
            . 'top:auto;bottom:-80px;}';
    }

    if ($has_bgimage && $card_alpha < 1.0) {
        // カード系コンポーネントを透過させる(BS3 + BS5 + 本アプリ独自クラス)。
        // .request-card は requestlist_swipe.php 側で per-status の rgba を持つため除外。
        // .modal-content は別途不透明で上書きするためここには含めない。
        // .badge は小さいステータス表示のため除外(.bg-info:not(.badge) 等で限定)。
        // フォールバック値を持たせて _variables.css 未ロード時(BS3)でも有効化。
        $card_bg = 'rgba(var(--bg-card-rgb, 255, 255, 255), var(--bg-card-alpha, 1))';
        print '.panel,.panel-default,.panel-body,.panel-heading,.panel-footer,'
            . '.bg-info:not(.badge),.bg-light:not(.badge),.bg-white,.well,.alert,'
            . '.card,.card-body,.card-header,.card-footer,'
            . '.list-group-item,.dropdown-menu,'
            . '.accordion-item,.accordion-body,.accordion-button,'
            . '.player-nowplaying,#stats-bar,'
            . '.dataTables_wrapper > .dataTable, table.dataTable tbody tr{'
            . 'background-color:' . $card_bg . ' !important;'
            . 'background-image:none !important;}';
        // 背景色をインラインで持つ既存スタイル(bg-info の青 等)を直接上書きするための補強。
        // バッジは除外して常に Bootstrap 本来の色を維持する。
        print '.bg-info:not(.badge){background-color:' . $card_bg . ' !important;}';
        // モーダル・ダイアログは透過させない。背景画像が透けると文字が読みにくくなるため、
        // カード透過度の設定にかかわらず常に不透明な背景で表示する。
        $card_bg_opaque = 'rgba(var(--bg-card-rgb, 255, 255, 255), 1)';
        print '.modal-content{'
            . 'background-color:' . $card_bg_opaque . ' !important;'
            . 'background-image:none !important;}';

        // BS5 ページのみ: テーブル・フォームコントロール等の白抜き要素も透過対象に含める。
        // BS3 ページで .table や .form-control が登場するページに副作用が出るのを避けるため、
        // 呼び出し元 (shownavigatioinbar_bs5) で $is_bs5=true を渡したときのみ出力する。
        if ($is_bs5) {
            // BS5 の .table は --bs-table-bg を介して背景色を決めるため、変数を上書きする。
            // セル/行/縞模様もまとめて rgba に置き換え。
            print '.table{--bs-table-bg:' . $card_bg . ';'
                . '--bs-table-striped-bg:' . $card_bg . ';'
                . '--bs-table-hover-bg:' . $card_bg . ';'
                . 'background-color:' . $card_bg . ' !important;}';
            print '.table > :not(caption) > * > *,'
                . '.table-striped > tbody > tr:nth-of-type(odd) > *,'
                . '.table-hover > tbody > tr:hover > *{'
                . 'background-color:' . $card_bg . ' !important;}';
            // フォームコントロール (BS5 標準 + 当アプリ独自テーマ)
            print '.form-control,.form-select,textarea.form-control,'
                . 'select.form-select,.form-control-themed,'
                . '.input-group-text{'
                . 'background-color:' . $card_bg . ' !important;}';

            // 独自カードクラス(.search-section, .search-hero, .notice-box 等)が
            // var(--bg-card) / var(--bg-card-alt) を直接参照しているため、
            // 変数自体を rgba に置き換えて一括で透過させる。
            // --bg-card-rgb / --bg-card-alt-rgb はテーマ別に正しく設定済みなので、
            // 値解決時にライト/ダーク両モードで適切な色になる。
            print ':root{'
                . '--bg-card:rgba(var(--bg-card-rgb,255,255,255),var(--bg-card-alpha,1));'
                . '--bg-card-alt:rgba(var(--bg-card-alt-rgb,248,244,240),var(--bg-card-alpha,1));}';
        }
    }

    print '</style>';

    if ($has_bgimage) {
        // Chrome iOS(CriOS)や各種アプリ内ブラウザは、ツールバー開閉時に WebView
        // 自体をリサイズするため、vh も lvh もすべて動的に変わり、CSS 単体では
        // 背景疑似要素の伸縮(=cover 再計算によるズーム)を止められない。
        // (Safari はレイアウトビューポート固定方式なので lvh 指定だけで足りる)
        // そこで実測した最大ビューポート高を固定 px として CSS 変数に焼き付け、
        // ビューポート単位の再計算そのものを背景の高さから排除する。
        // - 同一幅の間は「観測した最大の高さ」のみ採用(ツールバー格納時の値に収束)
        // - 幅が変わったとき(画面回転・ウィンドウリサイズ)は測り直す
        // - キーボード表示などの高さ減少では更新しない
        print '<script>(function(){'
            . 'var doc=document.documentElement,maxH=0,lastW=0;'
            . 'var ua=navigator.userAgent,anchor="";'
            . 'if(/Android/i.test(ua)){anchor="bottom";}'
            . 'var am=location.search.match(/[?&]bganchor=(top|center|bottom)/);'
            . 'if(am){anchor=(am[1]==="top")?"":am[1];}'
            . 'if(anchor){doc.setAttribute("data-ykr-bganchor",anchor);}'
            . 'function apply(){'
            . 'var w=window.innerWidth,h=window.innerHeight;'
            . 'if(!w||!h)return;'
            . 'if(w!==lastW){lastW=w;maxH=0;}'
            . 'if(h>maxH){maxH=h;'
            . 'doc.style.setProperty("--ykr-bg-fixed-h",(maxH+160)+"px");'
            . 'doc.setAttribute("data-ykr-bgfix","1");}'
            . '}'
            . 'apply();'
            . 'window.addEventListener("resize",apply);'
            . 'window.addEventListener("orientationchange",function(){setTimeout(apply,350);});'
            . '})();</script>';
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

    if (file_exists('version')) {
        $localversion = trim(file_get_contents('version'));
    }

    $gitversion = trim((string)get_git_version());
    $baseversion = empty($gitversion) ? $localversion : $gitversion;

    if ($baseversion === '') {
        return '';
    }

    return $baseversion . '-つぼはち改良';
}

function get_git_command_version() {
    global $config_ini;
    if (!array_key_exists('gitcommandpath', $config_ini)) return null;
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    if (!file_exists($gitcmd)) return null;
    $ver = trim(exec($gitcmd . ' --version 2>&1'));
    return ($ver !== '') ? $ver : null;
}

function get_current_git_branch() {
    global $config_ini;
    if (!array_key_exists('gitcommandpath', $config_ini)) return null;
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    if (!file_exists($gitcmd)) return null;
    $branch = trim(exec($gitcmd . ' rev-parse --abbrev-ref HEAD 2>&1'));
    if ($branch === '' || $branch === 'HEAD' || mb_strpos($branch, 'fatal') !== false) return null;
    return $branch;
}

// $do_fetch=false を指定すると fetch をスキップ（get_gittaglist の直後に呼ぶ場合など）
function get_gitbranchlist(&$errmsg = '', $do_fetch = true) {
    global $config_ini;
    $branchlist = [];
    if (!array_key_exists('gitcommandpath', $config_ini)) return $branchlist;
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    if (!file_exists($gitcmd)) return $branchlist;

    if ($do_fetch) {
        exec($gitcmd . ' config --global core.autoCRLF false');
        set_time_limit(900);
        exec($gitcmd . ' fetch --prune origin 2>&1', $out);
        $out = [];
    }

    // git for-each-ref は git 1.x から使えるため branch --sort より互換性が高い
    exec($gitcmd . ' for-each-ref --sort=-committerdate --format=%(refname:short) refs/remotes/origin/ 2>&1', $lines, $ret);
    if ($ret === 0 && count($lines) > 0) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || mb_strpos($line, 'origin/HEAD') !== false) continue;
            if (mb_strpos($line, 'origin/') === 0) {
                $branchlist[] = mb_substr($line, mb_strlen('origin/'));
            }
        }
    } else {
        // フォールバック: 古い git で for-each-ref も使えない場合
        $lines = [];
        exec($gitcmd . ' branch -r 2>&1', $lines);
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strpos($line, '->') !== false) continue;
            if (mb_strpos($line, 'origin/') === 0) {
                $branchlist[] = mb_substr($line, mb_strlen('origin/'));
            }
        }
    }
    return $branchlist;
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
          $execcmd = $gitcmd.' fetch --prune origin';
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
          
          $execcmd = $gitcmd.' fetch --prune origin';
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

          // origin/ プレフィックスあり → ブランチ切り替え (checkout -B)
          // タグ / ハッシュ → 現在ブランチのまま reset --hard
          $result_str = [];
          if (strpos($version_str, 'origin/') === 0) {
              $branch_name = substr($version_str, strlen('origin/'));
              $execcmd = $gitcmd . ' checkout -B ' . escapeshellarg($branch_name) . ' ' . escapeshellarg($version_str);
          } else {
              $execcmd = $gitcmd . ' reset --hard ' . $version_str;
          }
          exec($execcmd, $result_str);
          foreach($result_str as $line){
              $err_str_pos = mb_strpos($line, "unknown revision");
              if( $err_str_pos  !== false) {
                  $errmsg .= "no version : $version_str";
                  $errorcnt ++;
              }else if (mb_strstr($line, "fatal") !== false) {
                  $errmsg .= "checkout/reset unknown error: $line";
                  $errorcnt ++;
              }
          }

          if ($errorcnt === 0) {
              // タグを最新化してから version ファイルに書き込む
              // shallow clone では git describe が失敗する場合があるため GitHub API でフォールバック
              exec($gitcmd . ' fetch --tags origin 2>&1');
              $desc = trim(exec($gitcmd . ' describe --tags 2>&1'));
              $app_root = realpath(__DIR__);
              if ($desc !== '' && mb_substr($desc, 0, 1) === 'v' && is_numeric(mb_substr($desc, 1, 1))) {
                  file_put_contents($app_root . DIRECTORY_SEPARATOR . 'version', $desc);
              } else {
                  // shallow clone 等で describe 失敗 → GitHub API で取得
                  $sha = trim(exec($gitcmd . ' rev-parse HEAD 2>&1'));
                  if (preg_match('/^[0-9a-f]{40}$/', $sha)) {
                      $api_ver = _kara_github_describe_version(get_update_repo(), $sha);
                      if ($api_ver !== null) {
                          file_put_contents($app_root . DIRECTORY_SEPARATOR . 'version', $api_ver);
                      }
                  }
              }
          }
      }
    }

    if($errorcnt > 0) {
        return false;
    }

    return true;
}

// ---- ZIPアーカイブ方式アップデート ----

function _kara_http_get($url, &$errmsg) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KaraokeRequestorWeb-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data === false || $httpcode !== 200) {
            $errmsg = 'HTTP取得失敗 (HTTP ' . $httpcode . '): ' . $url;
            return false;
        }
        return $data;
    } elseif (ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: KaraokeRequestorWeb-Updater\r\n",
            'timeout' => 300,
        ]]);
        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            $errmsg = 'HTTP取得失敗: ' . $url;
            return false;
        }
        return $data;
    }
    $errmsg = 'curl または allow_url_fopen が有効である必要があります';
    return false;
}

function check_zip_update_available() {
    if (!zip_extract_available()) {
        return 'ZIP 展開手段がありません (php.ini で extension=zip を有効化するか、PowerShell が利用できる Windows 環境が必要です)';
    }
    if (!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
        return 'curl または allow_url_fopen が必要です';
    }
    return true;
}

// ZIP 展開が可能か（ZipArchive または PowerShell Expand-Archive）
function zip_extract_available() {
    if (class_exists('ZipArchive')) return true;
    return is_powershell_available();
}

// PowerShell が利用可能か（Windows 環境を想定）
function is_powershell_available() {
    if (stripos(PHP_OS, 'WIN') !== 0) return false;
    if (!function_exists('exec')) return false;
    @exec('powershell -NoProfile -Command "exit 0" 2>&1', $out, $ret);
    return ($ret === 0);
}

// ZIP ファイルを展開先へ展開。ZipArchive 優先、無ければ PowerShell にフォールバック。
function extract_zip_archive($zip_file, $extract_dir, &$errmsg = '') {
    if (!is_dir($extract_dir)) {
        @mkdir($extract_dir, 0700, true);
    }
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_file) !== true) {
            $errmsg = 'ZIP の展開に失敗しました (ZipArchive)';
            return false;
        }
        $zip->extractTo($extract_dir);
        $zip->close();
        return true;
    }
    if (is_powershell_available()) {
        // Expand-Archive は .zip 拡張子が必須。一時ファイルが .tmp 等の場合は .zip にコピーして渡す。
        if (strtolower(pathinfo($zip_file, PATHINFO_EXTENSION)) !== 'zip') {
            $zip_copy = $zip_file . '.zip';
            if (!@copy($zip_file, $zip_copy)) {
                $errmsg = 'ZIP の展開に失敗しました (PowerShell: 一時ファイルのコピーに失敗)';
                return false;
            }
        } else {
            $zip_copy = null;
        }
        $ps_src = addslashes($zip_copy ?? $zip_file);
        $ps_dst = addslashes($extract_dir);
        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command '
             . escapeshellarg("Expand-Archive -LiteralPath '$ps_src' -DestinationPath '$ps_dst' -Force");
        @exec($cmd . ' 2>&1', $out, $ret);
        if ($zip_copy !== null) @unlink($zip_copy);
        if ($ret !== 0) {
            $errmsg = 'ZIP の展開に失敗しました (PowerShell): ' . implode(' / ', $out);
            return false;
        }
        return true;
    }
    $errmsg = 'ZIP を展開する手段がありません';
    return false;
}

// ZIP 作成が可能か（ZipArchive または PowerShell Compress-Archive）
function zip_create_available() {
    if (class_exists('ZipArchive')) return true;
    return is_powershell_available();
}

// ファイルを ZIP に圧縮する。ZipArchive 優先、無ければ PowerShell にフォールバック。
// $files_map: ['追加するフルパス' => 'ZIP内パス', ...] または
//             [['dir' => $dir, 'prefix' => $zip_prefix], ...]  ← ディレクトリ一括追加は呼び元で展開済みを渡す
// $output_file: 出力ZIPのフルパス（上書き）
function create_zip_archive($files_map, $output_file, &$errmsg = '') {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($output_file, ZipArchive::OVERWRITE) !== true) {
            $errmsg = 'ZIPの作成に失敗しました (ZipArchive::open)';
            return false;
        }
        foreach ($files_map as $src => $dst) {
            if (is_file($src)) {
                $zip->addFile($src, $dst);
            }
        }
        $zip->close();
        return true;
    }
    if (is_powershell_available()) {
        // 一時ディレクトリにZIP内パス構造でファイルをコピーしてから Compress-Archive する
        $tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ykbak_' . uniqid();
        if (!@mkdir($tmpdir, 0700, true)) {
            $errmsg = '一時ディレクトリの作成に失敗しました';
            return false;
        }
        foreach ($files_map as $src => $dst) {
            if (!is_file($src)) continue;
            $dstfull = $tmpdir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dst);
            $dstdir  = dirname($dstfull);
            if (!is_dir($dstdir)) @mkdir($dstdir, 0777, true);
            @copy($src, $dstfull);
        }
        if (@file_exists($output_file)) @unlink($output_file);
        $ps_src = addslashes($tmpdir);
        $ps_dst = addslashes($output_file);
        $ps_cmd = "Get-ChildItem -Path '$ps_src' | Compress-Archive -DestinationPath '$ps_dst' -Force";
        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($ps_cmd);
        @exec($cmd . ' 2>&1', $out, $ret);
        // 一時ディレクトリ削除
        _kara_update_cleanup($tmpdir);
        if ($ret !== 0) {
            $errmsg = 'ZIPの作成に失敗しました (PowerShell): ' . implode(' / ', $out);
            return false;
        }
        return true;
    }
    $errmsg = 'ZIP を作成する手段がありません (php.ini で extension=zip を有効化するか、PowerShell が利用できる Windows 環境が必要です)';
    return false;
}

// アップデート取得元リポジトリ（owner/repo）を config から取得。未設定時は既定値。
function get_update_repo() {
    global $config_ini;
    if (array_key_exists('update_repo', $config_ini)) {
        $repo = trim(urldecode($config_ini['update_repo']));
        if ($repo !== '') return $repo;
    }
    return 'bee7813993/KaraokeRequestorWeb';
}

function get_archive_taglist(&$errmsg = '') {
    $taglist = [];
    $url = 'https://api.github.com/repos/' . get_update_repo() . '/tags';
    $data = _kara_http_get($url, $errmsg);
    if ($data === false) {
        return $taglist;
    }
    $items = json_decode($data, true);
    if (!is_array($items)) {
        $errmsg = 'GitHub API レスポンスの解析失敗';
        return $taglist;
    }
    foreach ($items as $item) {
        $name = $item['name'] ?? '';
        if (mb_substr($name, 0, 1) === 'v' && is_numeric(mb_substr($name, 1, 1))) {
            $taglist[] = $name;
        }
    }
    return $taglist;
}

// GitHub API を使い git describe --tags 相当の文字列を返す。失敗時は null。
function _kara_github_describe_version($repo, $ref) {
    $dummy = '';
    $data = _kara_http_get('https://api.github.com/repos/' . $repo . '/commits/' . rawurlencode($ref), $dummy);
    if ($data === false) return null;
    $commit_info = json_decode($data, true);
    if (!is_array($commit_info) || empty($commit_info['sha'])) return null;

    $full_sha  = $commit_info['sha'];
    $short_sha = substr($full_sha, 0, 7);

    $taglist = get_archive_taglist($dummy);
    if (empty($taglist)) return null;

    foreach ($taglist as $tag) {
        $cdata = _kara_http_get(
            'https://api.github.com/repos/' . $repo . '/compare/' . rawurlencode($tag) . '...' . $full_sha,
            $dummy
        );
        if ($cdata === false) continue;
        $compare = json_decode($cdata, true);
        if (!is_array($compare)) continue;
        $status   = $compare['status']    ?? '';
        $ahead_by = (int)($compare['ahead_by'] ?? -1);
        if ($status === 'identical') return $tag;
        if ($status === 'ahead')     return $tag . '-' . $ahead_by . '-g' . $short_sha;
        // behind / diverged は次のタグを試す
    }
    return null;
}

function _kara_update_copy_recursive($src, $dst, $exclude_list, $relative = '') {
    $entries = scandir($src);
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;

        $rel = $relative === '' ? $entry : $relative . '/' . $entry;

        foreach ($exclude_list as $excl) {
            if ($rel === $excl || strpos($rel, $excl . '/') === 0) {
                continue 2;
            }
        }

        $src_path = $src . DIRECTORY_SEPARATOR . $entry;
        $dst_path = $dst . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($src_path)) {
            if (!is_dir($dst_path)) {
                mkdir($dst_path, 0755, true);
            }
            _kara_update_copy_recursive($src_path, $dst_path, $exclude_list, $rel);
        } else {
            copy($src_path, $dst_path);
        }
    }
}

function _kara_update_cleanup($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        is_dir($path) ? _kara_update_cleanup($path) : unlink($path);
    }
    rmdir($dir);
}

function update_fromarchive($version_str, &$errmsg) {
    global $config_ini;

    $check = check_zip_update_available();
    if ($check !== true) {
        $errmsg = $check;
        return false;
    }

    $repo = get_update_repo();
    $vs = trim($version_str);
    if ($vs === 'master' || $vs === 'origin/master') {
        $zip_url = 'https://github.com/' . $repo . '/archive/refs/heads/master.zip';
    } else {
        // origin/ プレフィックスは除去（ブランチ指定との統一）
        if (strpos($vs, 'origin/') === 0) {
            $vs = substr($vs, strlen('origin/'));
        }
        $vs = ltrim($vs, '/');
        // archive/<ref>.zip は ref にタグ・ブランチ・コミットハッシュのいずれも指定可。
        // ブランチ名のスラッシュ(feature/x 等)は保持しつつ各セグメントをエンコード。
        $encoded_ref = implode('/', array_map('rawurlencode', explode('/', $vs)));
        $zip_url = 'https://github.com/' . $repo . '/archive/' . $encoded_ref . '.zip';
    }

    $app_root = realpath(__DIR__);
    $tmp_dir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kara_update_' . bin2hex(random_bytes(8));

    if (!mkdir($tmp_dir, 0700, true)) {
        $errmsg = '一時ディレクトリの作成に失敗しました';
        return false;
    }

    $zip_file = $tmp_dir . DIRECTORY_SEPARATOR . 'update.zip';
    set_time_limit(900);

    $data = _kara_http_get($zip_url, $errmsg);
    if ($data === false) {
        // errmsg は _kara_http_get が設定済み（存在しないタグ/ブランチ/ハッシュなら HTTP 404）
        _kara_update_cleanup($tmp_dir);
        return false;
    }
    if (strlen($data) === 0) {
        _kara_update_cleanup($tmp_dir);
        $errmsg = 'ダウンロードしたファイルが空です';
        return false;
    }
    file_put_contents($zip_file, $data);
    unset($data);

    $extract_dir = $tmp_dir . DIRECTORY_SEPARATOR . 'extracted';
    if (!extract_zip_archive($zip_file, $extract_dir, $errmsg)) {
        _kara_update_cleanup($tmp_dir);
        return false;
    }

    // アーカイブ内のトップレベルディレクトリを探す
    $source_dir = null;
    foreach (scandir($extract_dir) as $entry) {
        if ($entry !== '.' && $entry !== '..' && is_dir($extract_dir . DIRECTORY_SEPARATOR . $entry)) {
            $source_dir = $extract_dir . DIRECTORY_SEPARATOR . $entry;
            break;
        }
    }

    if ($source_dir === null ||
        !file_exists($source_dir . DIRECTORY_SEPARATOR . 'commonfunc.php') ||
        !file_exists($source_dir . DIRECTORY_SEPARATOR . 'kara_config.php')) {
        _kara_update_cleanup($tmp_dir);
        $errmsg = 'アーカイブの構造が不正です (commonfunc.php / kara_config.php が見つかりません)';
        return false;
    }

    // ユーザーデータ保護: 上書き除外リスト (相対パス, / 区切り)
    $exclude_list = ['config.ini', '.git', 'version'];
    if (array_key_exists('dbname', $config_ini)) {
        $dbname = basename(urldecode($config_ini['dbname']));
        if ($dbname !== '' && !in_array($dbname, $exclude_list)) {
            $exclude_list[] = $dbname;
        }
    } else {
        $exclude_list[] = 'request.db';
    }
    $exclude_list[] = 'images/bg';

    _kara_update_copy_recursive($source_dir, $app_root, $exclude_list);

    // バージョンファイルを更新（git describe 相当の形式を GitHub API で取得）
    $describe_ver = _kara_github_describe_version($repo, $vs);
    file_put_contents($app_root . DIRECTORY_SEPARATOR . 'version', $describe_ver !== null ? $describe_ver : $version_str);

    _kara_update_cleanup($tmp_dir);
    return true;
}

// ---- ZIPアーカイブ方式ここまで ----

// ---- Git メンテナンス / 初期化 ----

function format_filesize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

function get_git_dir_size() {
    $git_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . '.git';
    if (!is_dir($git_dir)) return null;
    $size = 0;
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($git_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
    } catch (Exception $e) {
        return null;
    }
    return $size;
}

function run_git_gc(&$errmsg, $aggressive = false) {
    global $config_ini;
    if (!array_key_exists('gitcommandpath', $config_ini)) {
        $errmsg = 'gitcommandpath が設定されていません';
        return false;
    }
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    if (!file_exists($gitcmd)) {
        $errmsg = 'git コマンドが見つかりません: ' . $gitcmd;
        return false;
    }
    set_time_limit(600);
    $flag = $aggressive ? ' --aggressive' : '';
    exec($gitcmd . ' gc' . $flag . ' --prune=all 2>&1', $out, $ret);
    if ($ret !== 0) {
        $errmsg = 'git gc 失敗: ' . implode(' / ', $out);
        return false;
    }
    return true;
}

function init_git_repo(&$errmsg) {
    global $config_ini;
    $app_root = realpath(__DIR__);

    if (!array_key_exists('gitcommandpath', $config_ini)) {
        $errmsg = 'gitcommandpath が設定されていません';
        return false;
    }
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    if (!file_exists($gitcmd)) {
        $errmsg = 'git コマンドが見つかりません: ' . $gitcmd;
        return false;
    }
    if (is_dir($app_root . DIRECTORY_SEPARATOR . '.git')) {
        $errmsg = '.git フォルダが既に存在します';
        return false;
    }

    set_time_limit(900);
    $origin = 'https://github.com/' . get_update_repo() . '.git';

    $steps = [
        $gitcmd . ' init',
        $gitcmd . ' remote add origin ' . $origin,
        $gitcmd . ' config --global core.autoCRLF false',
        $gitcmd . ' fetch --depth=1 origin master',
        $gitcmd . ' reset --hard FETCH_HEAD',
        // タグ情報だけ取得（コミット本体なし）→ git describe --tags が動作するようになる
        $gitcmd . ' fetch --tags origin',
    ];

    foreach ($steps as $cmd) {
        exec($cmd . ' 2>&1', $out, $ret);
        if ($ret !== 0) {
            $errmsg = 'コマンド失敗 [' . $cmd . ']: ' . implode(' / ', $out);
            return false;
        }
        $out = [];
    }
    return true;
}

// ---- Git メンテナンス / 初期化ここまで ----

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
 * requesttable の reqorder を連番（1始まり昇順）に正規化する。
 * 現在の reqorder の大小関係を保持したまま隙間・重複を解消する。
 * 同じ reqorder 値を持つ行は id の昇順で並べる。
 * @param PDO  $db
 * @param bool $in_transaction 呼び出し元が既にトランザクションを開始している場合は true。
 *                             true の場合は BEGIN/COMMIT/ROLLBACK を行わない。
 */
function normalize_reqorder($db, $in_transaction = false) {
    if (!$in_transaction) {
        $db->beginTransaction();
    }
    try {
        $select = $db->query("SELECT id, reqorder FROM requesttable ORDER BY reqorder ASC, id ASC");
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();

        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :req WHERE id = :id");
        $pos = 1;
        foreach ($rows as $row) {
            if ((int)$row['reqorder'] !== $pos) {
                $stmt->bindValue(':req', $pos, PDO::PARAM_INT);
                $stmt->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                $stmt->execute();
            }
            $pos++;
        }
        if (!$in_transaction) {
            $db->commit();
        }
    } catch (Exception $e) {
        if (!$in_transaction) {
            $db->rollBack();
        }
        throw $e;
    }
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
