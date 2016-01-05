<?php

require_once 'kara_config.php';
require_once 'prioritydb_func.php';
//require_once("getid3/getid3.php");

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

// 検索ワードから検索結果一覧を取得する処理
function searchlocalfilename($kerwords, &$result_array,$order = null)
{

		global $everythinghost;
		if(empty($order)){
		    $order = 'sort=size&ascending=0';
		}
		// IPv6check
		$c_count = substr_count($everythinghost,':');
		if ( $c_count > 1 ) {
		    $askeverythinghost = '['.$everythinghost.']';
		}else {
		    $askeverythinghost = $everythinghost;
		}
  		$jsonurl = "http://" . $askeverythinghost . ":81/?search=" . urlencode($kerwords) . "&". $order . "&path=1&path_column=3&size_column=4&case=0&json=1";
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

function PrintLocalFileListfromkeyword_ajax($word,$order = null, $tableid='searchresult')
{
    global $priority_db;
    searchlocalfilename($word,$result_a,$order);
    
    if( $result_a["totalResults"] >= 1) {
        $result_withp = sortpriority($priority_db,$result_a);
        echo $result_withp["totalResults"]."件<br />";
        // print javascript
        $printjs = <<<EOD
  <script type="text/javascript">
$(document).ready(function(){
  $('#%s').dataTable({
  "ajax": {
      "url": "searchfilefromkeyword_json.php",
      "type": "GET",
      "data": { keyword:"%s" },
      "dataType": 'json',
      "dataSrc": "",
  },
  "bPaginate" : true,
  "lengthMenu": [[50, 10, -1], [50, 10, "ALL"]],
  "bStateSave" : true,
  "autoWidth": false,
  "columns" : [
      { "data": "no", "className":"no"},
      { "data": "reqbtn", "className":"reqbtn"},
      { "data": "filename", "className":"filename"},
      { "data": "filesize", "className":"filesize"},
      { "data": "filepath", "className":"filepath"},
  ],
  columnDefs: [
  { type: 'currency', targets: [3] }
   ]
   }
  );
});
</script>
EOD;
        echo sprintf($printjs,$tableid,$word);
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



function commentpost_v1($nm,$col,$msg,$commenturl)
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

function commentpost_v2($nm,$col,$size,$msg,$commenturl)
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
        'sz' => $size,
        'msg' => $msgline

    );
    //    print "$commenturl";

    $curl=curl_init(($commenturl));
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


function shownavigatioinbar($page = 'none', $prefix = '' ){
    global $helpurl;
    global $user;
    global $config_ini;
    
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
  </div>
EOD;

    print '<div id="gnavi" class="collapse navbar-collapse">';
    print '    <ul class="nav navbar-nav">';
    if (multiroomenabled()){
        reset($config_ini["roomurl"]);
        $roominfo = each($config_ini["roomurl"]);
        
        print '    <p class="navbar-text ">'.$roominfo["key"] .'部屋</p>';
        reset($config_ini["roomurl"]);
    }
    print '     <li ';
    if($page == 'requestlist_only.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'requestlist_only.php">予約一覧 </a></li>';
    print '     <li ';
    if($page == 'searchreserve.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'searchreserve.php">検索＆予約</a></li>';
    print '     <li ';
    if($page == 'playerctrl_portal.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'playerctrl_portal.php">PlayerController</a></li>';
    // comment 
    if(commentenabledcheck()){
        print '     <li ';
        if($page == 'comment.php')
        {
            print 'class="active" ';
        }
        print '><a href="'.$prefix.'comment.php">コメント</a></li>';
    }
    if(multiroomenabled()){
         print '    <li class="dropdown navbar-right">';
         print '    <a href="#" class="dropdown-toggle" data-toggle="dropdown" href="">別部屋情報  <b class="caret"></b></a>';
         print '    <ul class="dropdown-menu">';
         reset($config_ini["roomurl"]);
         while($roominfo = each($config_ini["roomurl"])){
             if(!empty($roominfo["value"])) {
                 print '      <li><a href="'.$roominfo["value"].'">'.$roominfo["key"].'</a></li>';
             }
         }
         print '    </ul>';
         print '    </li>';         
    }
    
    if ($user === 'admin'){
        print '    <p class="navbar-text "> <small>管理者ログイン中</small></p>';
    }
    
    if($page == 'init.php'){
    print '     <p class="navbar-text "';
    print '><button type="button" class="btn btn-success" onclick="document.allconfig.submit();" >設定反映</button></p>';
    }
    
    print '    <li class="dropdown navbar-right">';
    print '    <a href="#" class="dropdown-toggle" data-toggle="dropdown" href="">Help等  <b class="caret"></b></a>';

    print '    <ul class="dropdown-menu">';
    if(!empty($helpurl)){
        print '      <li><a href="'.$helpurl.'">ヘルプ</a></li>';
    }
    print '      <li><a href="'.$prefix.'init.php">設定</a></li>';
    print '      <li><a href="'.$prefix.'toolinfo.php">接続情報表示</a></li>';
    print '     <li ';
    if($page == 'request.php')
    {
        print 'class="active" ';
    }
    print '><a href="'.$prefix.'request.php">全部</a></li>';
    print '    </ul>';
    print '    </li>';
    print '    </ul>';
    
//    print '    <p class="navbar-text navbar-right"> <a href="'.$helpurl.'" class="navbar-link">ヘルプ</a> </p>';
    print '</div>';
    print '</nav>';
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

function selectrequestkind(){

    global $playmode;
    global $connectinternet;
    global $usenfrequset;
    global $config_ini;
    
print <<<EOD
<div  align="center" >
<form method="GET" action="search.php" >
<input type="submit" name="曲検索はこちら"   value="曲検索はこちら" class="topbtn btn btn-default btn-lg"/>
</form>
</div>
EOD;

if ($playmode != 4 && $playmode != 5){
    print '<div align="center" >';
    print '<form method="GET" action="request_confirm.php?shop_karaoke=1" >';
    print '<input type="hidden" name="shop_karaoke" value="1" />';
    print '<input type="submit" name="配信"   value="カラオケ配信曲を歌いたい場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
}

if (!empty($config_ini["downloadfolder"])){
    print '<div align="center" >';
    print '<form method="GET" action="file_uploader.php" >';
    print '<input type="hidden" name="set_directurl" value="1" />';
    print '<input type="submit" name="URL"   value="ファイルをアップロードして予約する場合はこちらから" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
}

if( $connectinternet == 1){
    print '<div align="center" >';
    print '<form method="GET" action="request_confirm_url.php?shop_karaoke=1" >';
    print '<input type="hidden" name="set_directurl" value="1" />';
    print '<input type="submit" name="URL"   value="インターネット直接再生はこちらから(Youtube等)" class="topbtn btn btn-default btn-lg"/> ';
    print '</form>';
    print '</div>';
}

if($usenfrequset == 1) {
    print '<div align="center" >';
    print '<form method="GET" action="notfoundrequest/notfoundrequest.php" >';
    print '<input type="submit" name="noffoundsong"   value="見つからなかった曲があればこちらから教えてください" class="topbtn btn btn-default btn-lg"/>';
    print '</form>';
    print '</div>';

}

}

function writeconfig2ini($config_ini,$configfile)
{
  $fp = fopen($configfile, 'w');
  foreach ($config_ini as $k => $i){
      if(is_array($i)){
          foreach ($i as $key2 => $item2){
              fputs($fp, $k.'['.$key2.']='.$item2."\n");
          }
      }else {
          fputs($fp, "$k=$i\n");
      }
  } 
  fclose($fp);
  inieolchange();
  
}

function multiroomenabled(){

 global $config_ini;
 
 $roomcounter = 0;
 foreach($config_ini["roomurl"] as $k => $i){
   if(!empty($i)){
     $roomcounter++;
   }

 }
 if($roomcounter > 1) return true;
 
 return false;

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

// ini.iniファイル room no 変更
function iniroomchange($roomno,$file = 'ini.ini'){

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
    $ini_a[0] = $roomno;
    
    fseek($fd, 0, SEEK_SET);
    
    foreach($ini_a as $oneline){
        fwrite($fd,$oneline."\r\n");
    }
    fclose($fd);
}


?>
