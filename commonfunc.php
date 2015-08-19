<?php

require_once 'kara_config.php';
require_once 'prioritydb_func.php';
require_once("getid3/getid3.php");

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
} else {
    $everythinghost = 'localhost';
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

function file_get_html_with_retry($url, $retrytimes = 5, $timeoutsec = 1){

    $errno = 0;

    for($loopcount = 0 ; $loopcount < $retrytimes ; $loopcount ++){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutsec);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $contents = curl_exec($ch);
        //var_dump($contents); //debug
        if( $contents !== false) {
            curl_close($ch);
            break;
        }
        $errno = curl_errno($ch);
        print $timeoutsec;
        curl_close($ch);
    }
    if ($loopcount === $retrytimes) {
        $error_message = curl_strerror($errno);
        print 'http connection error : '.$error_message . ' url : ' . $url . "\n";
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
    $exp   = 2;  // MB固定
    $unit  = $units[$exp];
    $bytes = $bytes / pow(1024, floor($exp));
    $bytes = sprintf('%.'.$precision.'f', $bytes);
    return $sign.$bytes.' '.$unit;
}

// 検索ワードから検索結果一覧を取得する処理
function searchlocalfilename($kerwords, &$result_array,$order = null)
{

		global $everythinghost;
		if(empty($order)){
		    $order = 'sort=size&ascending=0';
		}
  		$jsonurl = "http://" . $everythinghost . ":81/?search=" . urlencode($kerwords) . "&". $order . "&path=1&path_column=3&size_column=4&json=1";
//  		echo $jsonurl;
  		$json = file_get_html_with_retry($jsonurl, 5, 30);
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

		$getID3 = new getID3();
		$getID3->setOption(array('encoding' => 'UTF-8'));
		
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
    		echo "<Div Align=\"right\"><A HREF = \"preview.php?movieurl=" . $previewpath . "\" >";
    		echo "プレビュー";
    		echo " </A></Div>";
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
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = \"再生中\" ORDER BY reqorder ASC ";
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


function commentpost($nm,$col,$msg,$commenturl)
{

    $commentmax=18;
    $msgarray = array();
    if(mb_strlen($msg) >= $commentmax){
         $lfarray = explode("\n", $msg);
         $lfarray = array_map('trim', $lfarray);
         $lfarray = array_filter($lfarray, 'strlen');
         $lfarray = array_values($lfarray);
         foreach($lfarray as $msgline)
         {
            for($i=0; ;$i=$i+$commentmax)
            {
                $tmpmsgline = mb_substr($msgline,$i,$commentmax);
                $msgarray[] = $tmpmsgline;
//                print mb_strlen($tmpmsgline);
                if(mb_strlen($tmpmsgline) < $commentmax){
                print mb_strlen($tmpmsgline);
                    break;
                }
            }
         }
    }else {
        $msgarray[] = $msg;
    }
    
    foreach($msgarray as $msgline)
    {
    
    $POST_DATA = array(
        'nm' => $nm,
        'col' => $col,
        'msg' => $msgline

    );
    //    print "$commenturl";

    $curl=curl_init($commenturl);
    curl_setopt($curl,CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
    $output= curl_exec($curl);
   
    usleep(100000);
    }

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
?>
