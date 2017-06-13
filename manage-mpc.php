<?php
//setlocale(LC_ALL,  'ja_JP.UTF-8','ja_JP.Shift_JIS');
if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    logtocmd('Locale not found: ja_JP.UTF-8');
    exit(1);
}

date_default_timezone_set('Asia/Tokyo');
require_once 'commonfunc.php';
require_once 'mpcctrl_func.php';
//require_once("getid3/getid3.php");

if(empty($playerpath)){
    $MPCPATH='C:\Program Files (x86)\MPC-BE\mpc-be.exe';
}else{
    $MPCPATH=$playerpath;
}
$playercommandname = basename($playerpath);
// logtocmd $playercommandname;
if(empty($foobarpath)){
    $FOOBARPATH='C:\Program Files (x86)\foobar2000\foobar2000.exe';
}else{
    $FOOBARPATH=$foobarpath;
}
$MPCSTATURL='http://localhost:13579/info.html';
$MPCBESTATURL='http://localhost:13579/status.html';
$MPCCMDURL='http://localhost:13579/command.html';
$MPCFILEOPENURL='http://localhost:13579/browser.html?path=';
$FOOBARSTATURL='http://localhost:82/karaokectrl/';
$MPCKEYCHANGEURL='http://localhost/mpcctrl.php?key=';

function file_exist_check_japanese($filename){
/*
 $fileinfo=file_exists($filename);
 if($fileinfo != FALSE){
     return TRUE;
 }
*/
 $fileinfo = @fopen(addslashes($filename),'r');
 if($fileinfo != FALSE){
     fclose($fileinfo);
     // logtocmd 'DEBUG : Success fopen' ;
     return TRUE;
 }
 
 return FALSE;
}

function workcheck_pfwd(){
   global $config_ini;
   $usepfwdcheck = false;
   
   if(array_key_exists("usepfwdcheck",$config_ini)) {
       $usepfwdcheck = $config_ini["usepfwdcheck"];
   }
   
   /* disable check? */
   if(array_key_exists("onlinechecktimeout",$config_ini)) {
   }else {
     return true;
   }
   if($config_ini["onlinechecktimeout"] == 0 ) return true;
   /* 設定が2以下の場合2秒に設定する */
   if($config_ini["onlinechecktimeout"] < 2 ) $config_ini["onlinechecktimeout"] = 2;
   
   $checkresult = check_online_available($config_ini['globalhost'], $config_ini["onlinechecktimeout"]);
   if( $checkresult === "OK" ){
       return true;
   }
   if( $checkresult === "now disabled online") {
       return true;
   }

    logtocmd ('pfwdの通信断を検出。pfwdを再起動します:'.$checkresult) ;
    $url = 'http://localhost/pfwd_exec.php?pfwdstop=1';
    file_get_html_with_retry($url,1,5);
    sleep(2);
    $url = 'http://localhost/pfwd_exec.php?pfwdstart=1';
    file_get_html_with_retry($url,1,5);
    
}

function workcheck_andrestartxampp(){

    $xamppstop_cmd = '..\xampp_stop.exe';
    $xamppstart_cmd = '.\start_xampp_fromphp.bat';
//    $xamppstart_cmd = '..\xampp_start.exe';
    $url = 'http://localhost/requestlist_table_json.php';
    
    $workedcheck = true;
    $stopstartsleeptime = 0;

    while($workedcheck){
    if( check_json_available_fromurl($url)){
       $workedcheck = false;
       // print 'xampp working'."\n";
    } else {
       if($stopstartsleeptime < 10 ){
           $stopstartsleeptime = $stopstartsleeptime + 1;
       }else{
           logtocmd('xamppに不具合発生中。自動再生を停止した後、xamppコントロールパネルでApacheのStop、Startしてみてください'."\n"); 
       }
       print 'apache happens some error'."\n";
       print 'now stopping xampp'."\n";
       exec($xamppstop_cmd);
       sleep($stopstartsleeptime);
       print getcwd();
       print 'now starting xampp'."\n";
       $cmd = 'start "" /I /MIN  '.$xamppstart_cmd.'';
       $fp = popen($cmd,'r');
       pclose($fp);
    }
    check_json_available_fromurl($url);
    }
}

function logtocmd($msg){
  print(mb_convert_encoding("$msg\n","SJIS-win"));
}

function logtocmd_dbg($msg){
  //print(mb_convert_encoding("$msg\n","SJIS-win"));
}

function mpcstop(){
   global $MPCCMDURL;
   global $playercommandname;
   $pscheck_cmd='tasklist /fi "imagename eq '.$playercommandname;
   exec($pscheck_cmd, $psresult );
   sleep(1);
   
   $process_found = 0;
   
   foreach( $psresult as $psline ){
     $pos = strpos($psline,$playercommandname);
     if ( $pos !== FALSE) {
        $process_found = 1;
     }
   }
   
   if($process_found == 1){
       for($loopcount = 0 ; $loopcount < 2 ; $loopcount ++){
//          $org_timeout = ini_get('default_socket_timeout');
//          ini_set('default_socket_timeout', 2);
          $mpcstat = file_get_html_with_retry($MPCCMDURL."?wm_command=816", 5);
//          ini_set('default_socket_timeout', $org_timeout);
          if( $mpcstat === FALSE) {
              sleep(1);
              continue;
          }else{
              break;
          }
       }
   }
}

function mpcrunningcheck($playerpath){

   global $MPCCMDURL;
   global $playercommandname;
   $pscheck_cmd='tasklist /fi "imagename eq '.$playercommandname.'"';
   //logtocmd $pscheck_cmd;
   exec($pscheck_cmd, $psresult );
   sleep(0.1);
   
   $process_found = FALSE;
   
   foreach( $psresult as $psline ){
     $pos = strpos($psline,$playercommandname);
     if ( $pos !== FALSE) {
        $process_found = TRUE;
        // logtocmd 'DEBUG: mpc is running';
     }
   }
   if(!$process_found)
   // logtocmd 'DEBUG: mpc is not running';
   return $process_found;
}

function startmpcandwait($playerpath,$waittime = 1){
   // start mpc
   global $MPCCMDURL;
   $execcmd="start  \"\" \"".$playerpath."\" > NUL \n";
   // logtocmd $execcmd;
   $fp = popen($execcmd,'r');
   //exec($execcmd);
   pclose($fp);
   // logtocmd 'DEBUG: now start mpc';
   sleep($waittime);
   
   // wait mpc web 
   // logtocmd 'DEBUG: now mpc first check start';
   file_get_html_with_retry($MPCCMDURL);
   // logtocmd 'DEBUG: now mpc first check end';
}

function mpc_waiting_start($db, $id){

// status を 再生開始待ちに
   $db->beginTransaction();
   $sql = "UPDATE requesttable set nowplaying = \"再生開始待ち\" WHERE id = $id ";
   $ret = $db->exec($sql);
   if (! $ret ) {
       logtocmd("再生開始待ち への変更にしっぱいしました。<br>");
   }
   // logtocmd("DEBUG:再生開始待ち への変更。$id<br>");
   $db->commit(); 
// status が 再生中になるのを待つ
   $state = 6;
   while($state == 6){
     $state = check_nowplaying_state ($db,$id);
     if($state != 6) break;
     sleep(0.5);
   }
   // logtocmd("DEBUG:再生中への変更確認。$id<br>");


}


function mpcplaylocalfile($playerpath,$playfilepath,$playmode,$waittime = 1, $db, $id){
     global $MPCCMDURL;
     global $MPCFILEOPENURL;

    if( $playmode == 2) {
            mpc_waiting_start($db, $id);
    }
    if(mpcrunningcheck($playerpath)===FALSE){
        startmpcandwait($playerpath,$waittime);
    }

   // wait mpc web 
   file_get_html_with_retry($MPCCMDURL);

    for($loopcount = 0 ; $loopcount < 1 ; $loopcount ++){
       
       $requesturl = $MPCFILEOPENURL.rawurlencode($playfilepath);
       // logtocmd $requesturl;
       $mpcstat = file_get_html_with_retry($requesturl, 1,2);
       
       if( $mpcstat === FALSE) {
           //var_dump($mpcstat);
           //sleep(0.1);
           continue;
       }else{
           break;
       }
    }
/*
    if( $playmode == 2) {
        //一時停止する
        sleep($waittime);
        file_get_html_with_retry($MPCCMDURL."?wm_command=888");
    }
*/
}

// Capture viewerソフトを終了させる
function captureviewstop(){
   global $config_ini;
   
   if(array_key_exists("captureapli_path", $config_ini)) {
     $capviewercommandname = basename_jp(urldecode($config_ini["captureapli_path"])); 

     if(!empty($capviewercommandname)){
       $pscheck_cmd='tasklist /fi "imagename eq '.$capviewercommandname.'"';
       exec($pscheck_cmd, $psresult );
       $process_found = 0;
       foreach( $psresult as $psline ){
         $pos = strpos($psline,$capviewercommandname);
         if ( $pos !== FALSE) {
            $process_found = 1;
         }
       }
       if($process_found == 1){
         //$pscheck_cmd='taskkill  /im '.$capviewercommandname.' -f';
         $pscheck_cmd='stopcapture.vbs '.$capviewercommandname;
         // logtocmd('DEBUG:'.$pscheck_cmd);
         exec($pscheck_cmd, $psresult );
         return true;
       }
     }
   }

   return false;
   
}

// Capture viewerソフトを起動する。(設定がなければMPCのデバイス再生)
function captureviewstart($playerpath,$waittime = 1){

  global $config_ini;
  if(array_key_exists("usevideocapture", $config_ini)) {
     if($config_ini["usevideocapture"]==3 ){
       if(array_key_exists("captureapli_path", $config_ini)) {
         if(!empty($config_ini["captureapli_path"])) {
           global $MPCCMDURL;
           mpcstop();
           $execcmd="start  \"\" \"".urldecode($config_ini["captureapli_path"])."\" > NUL \n";
           logtocmd ($execcmd);
           $fp = popen($execcmd,'r');
           //exec($execcmd);
           pclose($fp);
           // logtocmd 'DEBUG: now start capture_viewer';
           sleep(1);
           return true;
         }
       }
     }else if($config_ini["usevideocapture"]==1 ){
       $toggled3dfullscreen = false;
       if(array_key_exists("toggled3dfullscreen", $config_ini)) {
           if($config_ini["toggled3dfullscreen"] == 1){
               $toggled3dfullscreen = true;
           }
       }
       if($toggled3dfullscreen) {
           global $MPCCMDURL;
           $mpcstat = file_get_contents($MPCCMDURL."?wm_command=1023");
       }
     
       mpcdevicestart($playerpath, $waittime);
       
       // ビデオキャプチャーデバイスでは、画面を画面いっぱいに引きのばす(GV-USB2では縦長になるので)
       $stretchvideo = true;
       if(array_key_exists("stretchvideo", $config_ini)) {
           if($config_ini["stretchvideo"] != 1){
               $stretchvideo = false;
           }
       }
       if($stretchvideo) {
           global $MPCCMDURL;
           $mpcstat = file_get_contents($MPCCMDURL."?wm_command=838");
       }

       return true;
     }
  }

  
  return false;
}

function mpcdevicestart($playerpath,$waittime = 1){
     global $MPCCMDURL;

    if(mpcrunningcheck($playerpath)===FALSE){
        startmpcandwait($playerpath,$waittime);
    }

    for($loopcount = 0 ; $loopcount < 2 ; $loopcount ++){
       $mpcstat = file_get_contents($MPCCMDURL."?wm_command=802", 5);
       
       if( $mpcstat === FALSE) {
           //var_dump($mpcstat);
           sleep(1);
           continue;
       }else{
           break;
       }
    }
}

function mpc_keychange($changekey){
    global $MPCKEYCHANGEURL;
    global $config_ini;
    
    if(is_null($changekey)) return;
    if($config_ini['usekeychange'] != 1){
     file_get_html_with_retry('http://localhost/mpcctrl.php');
     return;
    }
    $mpccmd = $MPCKEYCHANGEURL.$changekey;
    file_get_html_with_retry($mpccmd);
}

function mpc_trackchange($changetrack){
    global $MPCCMDURL;
    global $config_ini;
    
    if(is_null($changetrack)) return;
    if(!is_numeric ($changetrack)) return;
    $mpccmd = $MPCCMDURL."?wm_command=952";
    for($changecount=0 ; $changecount<$changetrack ; $changecount++){
        file_get_contents($mpccmd);
    }
}

function startfoobarandwait($arg1,$waittime = 1){
   // start mpc
   global $FOOBARSTATURL;
   global $FOOBARPATH;
   $execcmd="start  \"\" \"".$FOOBARPATH."\" \"".$arg1."\" > NUL \n";
   // logtocmd $execcmd;
   $fp = popen($execcmd,'r');
   //exec($execcmd);
   pclose($fp);
   // logtocmd 'DEBUG: now start mpc';
   sleep($waittime);
   
   // wait mpc web 
   // logtocmd 'DEBUG: now mpc first check start';
   file_get_html_with_retry($FOOBARSTATURL);
   // logtocmd 'DEBUG: now mpc first check end';
}

function runningcheck_shop_karaoke($db,$id){

   $exit = 1;
   while($exit == 1)
   {
       // db statusを確認
       $stat = check_nowplaying_state ($db,$id);
       if($stat === 3  || $stat === 4 || $stat === 5){
           logtocmd (date("H.i.s")."Status is change to ".$stat." at id: ".$id."\n");
           $exit = 0;
           break;
       }
       //logtocmd "DEBUG: Endtime: " . date("H.i.s", $endtime) . ", Now: ".date("H.i.s", time());
       workcheck_pfwd();
       workcheck_andrestartxampp();
       sleep(2);
   }
}

function runningcheck_audio($db,$id,$playerchecktimes){

   $exit = 1;
   while($exit == 1)
   {
       // db statusを確認
       $stat = check_nowplaying_state ($db,$id);
       if($stat === 3 ){
           $exit = 0;
           break;
       }
       
       global $FOOBARSTATURL;
       $playerstat = file_get_html_with_retry($FOOBARSTATURL, (5 * $playerchecktimes ));
       if( $playerstat === FALSE) {
           logtocmd("maybe stop player\n");
           break;
       }
       //logtocmd $playerstat;
       $statusarray = json_decode(mb_convert_encoding($playerstat,"UTF-8"),true,4096);
       if( $statusarray === null ){
        logtocmd (json_last_error_msg());
        break;
       }
       // var_dump($statusarray);
       
       
       if($statusarray["IS_PLAYING"] == 0 && $statusarray["IS_PAUSED"] == 0 ){
           //finish playing
           //logtocmd "DEBUG: ".$statusarray["IS_PLAYING"]. ' '. $statusarray["IS_PAUSED"] . "\n";
           break;
           
       }
       if($statusarray["ITEM_PLAYING_POS"] >= ( $statusarray["ITEM_PLAYING_LEN"] - 2 )){
           //finish playing
           //logtocmd "DEBUG: ".$statusarray["ITEM_PLAYING_POS"]. '/'. $statusarray["ITEM_PLAYING_LEN"] . "\n";
           break;
       }
       workcheck_pfwd();
       workcheck_andrestartxampp();

       sleep(2);
   }
}

function minimum_playtimescheck_withoutme($all, $myid)
{
    $m_value = 4096; // max value of playtimes
    for($i=0; $i<count($all); $i++) {
        if($all[$i]['id'] == $myid){
             $mycount = $all[$i]['playtimes'];
        }
    }
    for($i=0; $i<count($all); $i++) {
        if($all[$i]['playtimes'] == $mycount )
            continue;
        $m_value = min($all[$i]['playtimes'], $m_value);
        //logtocmd "debug : $m_value ". '=min' . $all[$i]['playtimes'] . ':' . $m_value . "\n";
    }
    if($m_value >= 4096) {
        $m_value = $mycount + 1;
    }
    // logtocmd $m_value;
    return $m_value;
}


/**
 * @fn
 * check_filetype
 * @brief 指定したIDのファイルタイプを返す
 * @param ($db) DB
 * @param ($id) ID
 * @return 1: movie, 2 : audio, 3, URL, false :error
 */
function check_filetype ($db,$id){

        $sql = "SELECT songfile,fullpath,kind FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
        $select = $db->query($sql);
        $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        
        if($rowall === false) {
            logtocmd ("ERROR : Filename of $id is none");
            return false;
        }
        if( mb_stristr($rowall[0]['kind'], 'URL指定') !== FALSE ){
            return 3;
        }
        
        if( is_url($rowall[0]['songfile']) ){
            return 3;
        }

        if( is_url($rowall[0]['fullpath']) ){
            return 3;
        }
        
        $filepath = $rowall[0]['fullpath'];

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        if( empty($extension) ){
            logtocmd ("ERROR : File of $id has no extension : $filepath");
            return false;
        // Audio File
        }elseif( strcasecmp($extension,"mp3") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"m4a") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"wav") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"ogg") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"flac") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"wma") == 0 ){
            return 2;
        }elseif(strcasecmp($extension,"aac") == 0 ){
            return 2;
        // Movie File
        }elseif(strcasecmp($extension,"mp4") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"avi") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"mkv") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"mpg") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"flv") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"webm") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"wmv") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"ogm") == 0 ){
            return 1;
        }elseif(strcasecmp($extension,"mov") == 0 ){
            return 1;
        }else{
        // unknown file set to movie
            return 1;
        }
}


/**
 * @fn
 * check_nowplaying_state
 * @brief 
 * @param ($db) DB
 * @param ($id) ID
 * @return 
 * 1:未再生, 
 * 2:再生中, 
 * 3:停止中, 
 * 4:再生済, 
 * 5:再生済？, 
 * 6:再生開始待ち, 
 * 7:変更中, 
 * その他：設定されている文字列
 */
function check_nowplaying_state ($db,$id){
    $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    if($select === false){
        logtocmd ("ERROR : nowplaying of $id is none");
        return false;
    }
    $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    
    if($currentstatus === false) {
        logtocmd ("ERROR : nowplaying of $id is none");
        return false;
    }
    $c_status = $currentstatus[0]['nowplaying'];
    
    if($c_status === '未再生'){
        return 1;
    }else if($c_status === '再生中'){
        return 2;
    }else if($c_status === '停止中'){
        return 3;
    }else if($c_status === '再生済'){
        return 4;
    }else if($c_status === '再生済？'){
        return 5;
        
    }else if($c_status === '再生開始待ち'){
        return 6;
    }else if($c_status === '変更中'){
        return 7;
    }else{
        return $c_status;
    }
        
}

function song_start_again($db,$id){

    $kind = check_filetype ($db,$id);
    if( $kind === 1 || $kind === 3 ){
        // case mpc
        global $MPCCMDURL;
        $requesturl=$MPCCMDURL.'?wm_command=-1&percent=0';
        $res = file_get_html_with_retry($requesturl);
        sleep(0.5);
        $requesturl=$MPCCMDURL.'?wm_command=887';
        $res = file_get_html_with_retry($requesturl);
    }else if( $kind === 2) {
        // case foobar
        global $FOOBARSTATURL;
        $requesturl=$MPCCMDURL.'?cmd=SeekSecond&param1=0';
        $res = file_get_html_with_retry($requesturl);
    }else {
//        break;
    }
}

function song_stop($kind, $stat = 'none'){
    global $MPCCMDURL;
    global $FOOBARSTATURL;
    global $config_ini;
    if( $kind === 1 || $kind === 3){
        // case mpc
        
        if($stat == 3)
          $volume = volume_fadeout();
        
        $requesturl=$MPCCMDURL.'?wm_command=888';
        $res = file_get_html_with_retry($requesturl);
        
        if($stat == 3){
          set_volume($volume);
          }
        if(array_key_exists('startvolume50',$config_ini)){
            if($config_ini['startvolume50'] != 1)  {
            }else{
                set_volume(50);
            }
        }else{
            set_volume(50);
        }
        
        
    }else if( $kind === 2) {
        // case foobar
        $requesturl=$FOOBARSTATURL.'?cmd=PlayOrPause&param1=0';
        $res = file_get_html_with_retry($requesturl);
    }else {
//        break;
    }
}

/**
 * @fn
 * check_request_loop
 * @brief 
 * @param ($db) DB
 * @param ($id) ID
 * @return 1:Set loop, other:not set loop
 */

function check_request_loop($db,$id){
    $sql = "SELECT loop FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    if($select === false) return false;
    $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    
    if($currentstatus === false) {
        logtocmd ("ERROR : nowplaying of $id is none");
        return false;
    }
    $c_status = $currentstatus[0]['loop'];
    return $c_status;
    
}

function onerequestinfo_fromid($db,$id){
    $sql = "SELECT * FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
    $select = $db->query($sql);
    if($select === false) return false;
    $currentrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    
    if($currentrequest === false) {
        logtocmd ("ERROR : nowplaying of $id is none");
        return false;
    }
    return $currentrequest[0];
}

function get_fullfilename($l_fullpath,$word,&$filepath_utf8){
    // ファイル名のチェック
    // logtocmd ("Debug l_fullpath: $l_fullpath\r\n");
    $winfillpath = mb_convert_encoding($l_fullpath,"SJIS-win");
    $fileinfo=file_exist_check_japanese($winfillpath);
    // logtocmd ("Debug#".$fileinfo);
    if($fileinfo !== FALSE){
        $filepath = $winfillpath;
        $filepath_utf8=$l_fullpath;
    }else{
      $filepath = null;
      // まず フルパス中のbasenameで再検索
      $songbasename = basename($l_fullpath);
      // logtocmd ("fullpass file $winfillpath is not found. Search from Everything DB.: $songbasename\r\n");
      $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($songbasename) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
      $json = file_get_html_with_retry($jsonurl, 5);
      if($json != false){
          $decode = json_decode($json, true);
          if($decode != NULL && isset($decode{'results'}{'0'})){
            if(array_key_exists('path',$decode{'results'}{'0'}) && array_key_exists('name',$decode{'results'}{'0'})){
                $filepath_utf8 = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
                $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
            }
          }
      }
      if(empty($filepath)){
      // 曲名で再建策
          logtocmd ("fullpass basename $songbasename is not found. Search from Everything DB.: $word\r\n");
          $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
          // logtocmd $jsonurl;
          $json = file_get_html_with_retry($jsonurl, 5);
          $decode = json_decode($json, true);
          if( !isset($decode{'results'}{'0'}{'name'}) ) return false;
          $filepath = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
          $filepath_utf8= $filepath;
          $filepath = mb_convert_encoding($filepath,"cp932");
          logtocmd ('代わりに「'.$filepath_utf8.'」を再生します'."\n");
      }
    }
    return $filepath;
}

function start_song($db,$id,$addplaytimes = 0){

    global $FOOBARPATH;
    global $MPCPATH;
    global $config_ini;


    readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog,$waitplayercheckstart,$playerchecktimes,$connectinternet,$usevideocapture,$moviefullscreen,$helpurl,$commenturl_base,$commentroot,$commenturl);

    $row = onerequestinfo_fromid($db,$id);
    if( $row === false){
        return $row;
    }
    
    $filepath = get_fullfilename($row["fullpath"],$row["songfile"],$filepath_utf8);
    if( $filepath === false){
       logtocmd($word."<start_song>ファイルが見つかりませんでした、Skipします");
       return false;
    }
    // var_dump($row);
    $filetype = check_filetype ($db,$id);
    if( $filetype == 2 ){
    
        // とりあえず動画Playerを終了する。
        mpcstop();
        // audio file
        startfoobarandwait(mb_convert_encoding($filepath,"SJIS","SJIS, UTF-8"),3);
        if ($config_ini['playmode'] == 2){
            sleep(0.5);
            exec("start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " /pause \n");
        }
    }
    else{
        // video file
        //logtocmd 'MPC fileopen start '."\n";
        if(mb_strstr($row['kind'],'動画_別プ') != FALSE){
        // web経由でファイル再生
            if(array_key_exists("otherplayer_path",$config_ini) && !empty($config_ini["otherplayer_path"]) ){
                // とりあえず動画Playerを終了する。
                mpcstop();
                // 別Playerを指定している場合、実行を試みる。
                $execcmd="start /b \"\" \"".mb_convert_encoding(urldecode($config_ini["otherplayer_path"]),"SJIS-win")."\"" . " \"$filepath\"\n";
                exec($execcmd);
            }
        }else{
            if($filetype == 3){
              // とりあえず動画Playerを終了する。
              mpcstop();
              sleep(2);
              if ($config_ini['playmode'] == 2){
                $execcmd="start  \"\" \"".$MPCPATH."\"" . " /open \"$filepath\"\n";
              }else {
                $execcmd="start  \"\" \"".$MPCPATH."\"" . " /play \"$filepath\"\n";
              }
              //exec($execcmd);
              $fp = popen($execcmd,'r');
              pclose($fp);
            }else{
              // MPC起動チェック
              if(mpcrunningcheck($playerpath)===FALSE){
                  startmpcandwait($playerpath,1);
              }
              mpcplaylocalfile($config_ini['playerpath'],$filepath_utf8,$config_ini['playmode'],1,$db,$id);
            }
            /**** ここに再生前にPlayer操作を行う処理を入れる ****/
            /* key change */
            global $MPCCMDURL;
            global $MPCSTATURL;
            // 一時停止
            file_get_html_with_retry('http://localhost/mpcctrl.php?cmd=888');
            file_get_contents('http://localhost/mpcctrl.php'); // なぜか数回Webアクセスしないと次のアクセスが空振りするのでその対策
            file_get_contents($MPCSTATURL); // なぜか数回Webアクセスしないと次のアクセスが空振りするのでその対策
            file_get_html_with_retry('http://localhost/mpcctrl.php?cmd=888');
//            file_get_html_with_retry($MPCCMDURL."?wm_command=888");
            //sleep(5.5);
            if(array_key_exists("keychange" , $row)){
                mpc_keychange($row["keychange"]);
            }
            if(array_key_exists("track" , $row)){
                mpc_trackchange($row["track"]);
            }
            /* track change */
            // BGVモードではmuteにする。
            $loop = check_request_loop($db,$id);
            if($loop == 1) {
                global $config_ini;

                toggle_mute_mpc();
                // BGV スタートコマンドを実行する。
                if( array_key_exists('BGVCMDSTART',$config_ini) && !empty($config_ini['BGVCMDSTART']) ){
                    $cmd = urldecode($config_ini['BGVCMDSTART']);
                    exec($cmd);
                }
            }
            // 再生開始
            file_get_html_with_retry($MPCCMDURL."?wm_command=887");

        }
    }
    $db->beginTransaction();
    $sql = "UPDATE requesttable set nowplaying = \"再生中\", playtimes = ".($row["playtimes"] + $addplaytimes )."  WHERE id = $id ";
    $ret = $db->exec($sql);
    if (! $ret ) {
      logtocmd("再生中 への変更に失敗しました。<br>");
    }
    $db->commit();
    return true;
}

function check_end_song($db,$id,$playerchecktimes,$playmode){

    $exit = 1;
    $kind = check_filetype ($db,$id);
    $loopflg = check_request_loop($db,$id);
    $stat = 2;
    while($exit == 1)
    {
       echo ".";
       // db statusを確認
       $stat = check_nowplaying_state ($db,$id);
       if($stat === 3 ){  // 停止中
           break;
       }else if( $stat === 7) { // 変更中
           logtocmd("再生中曲差し替えを検出\n");
           start_song($db,$id,0);
       }
       
       if( $kind === 1 || $kind === 3){
           runningcheck_mpc($db,$id,$playerchecktimes);
       }else if( $kind === 2) {
           runningcheck_audio($db,$id,$playerchecktimes);
       }else {
           break;
       }
       $stat = check_nowplaying_state ($db,$id);
       if( $stat === 7) { // 変更中
           logtocmd("再生中曲差し替えを検出\n");
           start_song($db,$id,0);
           continue;
       }
       if($loopflg == 1) {
           song_start_again($db,$id);
       }else {
           break;
       }
       workcheck_pfwd();
       workcheck_andrestartxampp();
    }
    $stat = check_nowplaying_state ($db,$id);
    song_stop( $kind , $stat);
    // BGVモードではmuteを解除する。
    if($loopflg == 1) {
        global $config_ini;

        toggle_mute_mpc();
        // BGV ストップコマンドを実行する。
        if( array_key_exists('BGVCMDEND',$config_ini) && !empty($config_ini['BGVCMDEND']) ){
            $cmd = urldecode($config_ini['BGVCMDEND']);
            exec($cmd);
        }
    }

}

function runningcheck_mpc($db,$id,$playerchecktimes){

   global $MPCSTATURL;
   // get MPC status
   $exit = 1;
   $startonce = false;
   while($exit == 1)
   {
       // db statusを確認
       $stat = check_nowplaying_state ($db,$id);
       if($stat === 3 ){
           break;
       }
       if($stat === 7 ){
           break;
       }else if($stat === 4 ){
           break; //再生済
       }
       
       // MPCの状態取得3回チャレンジする
       for($loopcount = 0 ; $loopcount < $playerchecktimes ; $loopcount ++){
          $mpcstat = file_get_html_with_retry($MPCSTATURL, 5);
          if( $mpcstat === FALSE) {
              sleep(1);
              continue;
          }else{
              break;
          }
       }
       if($loopcount >= $playerchecktimes ){
           logtocmd("maybe stop player\n");
           break;
       }
       $mpsctat_array = explode('&bull', $mpcstat );
       // var_dump($mpsctat_array);
       $etime_a =  explode('/', trim($mpsctat_array[2], ' ;') );
       // var_dump($etime_a);
       $playtime_a =  explode(':', $etime_a[0] );
       $totaltime_a =  explode(':', $etime_a[1] );
       $playtime = $playtime_a[0]*60*60 + $playtime_a[1]*60 + $playtime_a[2];
       $totaltime = $totaltime_a[0]*60*60 + $totaltime_a[1]*60 + $totaltime_a[2];
       if($startonce && ( $playtime > ($totaltime - 4) ) ){
       print ($mpsctat_array[2]);
           echo ', ';
           print ($playtime);
           echo ':';
           print ($totaltime);
           echo "\n";
           if($totaltime != 0 ){
               sleep(4);
               break;
           }
       }
       
       if($playtime > 1 ) $startonce = true;
       // logtocmd "DEBUG : $mpsctat_array[2], $playtime : $totaltime \n";
       workcheck_pfwd();
       workcheck_andrestartxampp();

       sleep(1.0);
   }
}

/******** ここからメイン処理 ********/
while(1){
    logtocmd_dbg( '全体ループ開始'."\n");
     //config 再読込
     readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog,$waitplayercheckstart,$playerchecktimes,$connectinternet,$usevideocapture,$moviefullscreen,$helpurl,$commenturl_base,$commentroot,$commenturl);
     if(! empty($playerpath)){
        $MPCPATH = $playerpath;
     }
     if( empty($waitplayercheckstart)){
        $waitplayercheckstart = 5;
     }
     if( empty($playerchecktimes)){
        $playerchecktimes = 3;
     }
     $playercommandname = basename($playerpath);
    logtocmd_dbg( '全体ループ開始:config読み込み完了'."\n");
    
    
    $played = 5;  // 5:no next song wait sec. 1: played and next song wait sec.
    
    $nosong = 0;
    $nextplayingtimes=0;
    
    
    // 手動再生開始の場合、ボタンが押されるまで待つ処理をこのあたりに入れる予定。
    
    if( $playmode == 5 ){
        $sql = "SELECT * FROM requesttable  WHERE NOT kind = 'カラオケ配信' ORDER BY reqorder ASC ";
        $select = $db->query($sql);
        $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        $playid = -1;
        
        if(count($allrequest) == 0 ){
            $nosong = 1;
        }else {
            $playid = $allrequest[mt_rand(0, (count($allrequest)))]['id'];
            //logtocmd "DEBUG : id: $playid, cmd :[mt_rand(0, (".(count($allrequest)).")]['id']\n";
            $sql = "SELECT * FROM requesttable  WHERE id = $playid ORDER BY reqorder ASC ";
            $select = $db->query($sql);
            $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
            $select->closeCursor();        }
    }
    elseif( $playmode == 4 ){
        
        $sql = "SELECT * FROM requesttable  WHERE NOT kind = 'カラオケ配信' ORDER BY reqorder ASC ";
        $select = $db->query($sql);
        $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        $lastplayid = -1;
        if(isset($playid)){
            $lastplayid = $playid;
        }
        $playid = -1;
        
        if(count($allrequest) == 0 ){
            $nosong = 1;
        }else {
            for($check_playtimes = 0; $check_playtimes<4096 ; $check_playtimes++ ){
                $ptcounter = 0;
                $ptarray = array();
                for($i=0; $i<count($allrequest); $i++)
                {
                    $a=$allrequest[$i]['playtimes'];
                      //logtocmd("DEBUG : i: $i, check_playtimes: $check_playtimes, row[pt]: $a, lastplayid: $lastplayid \n");
                    if($allrequest[$i]['playtimes'] == $check_playtimes) {
                        $ptarray[] = $allrequest[$i];
                        //logtocmd('add ptarray\n');
                        //var_dump($ptarray);
                    }
                }
                if(count($ptarray) === 0) continue;
                
                if($check_playtimes === 0){
                    // if playtimes is 0, use oldest request
                    $playid = $ptarray[0]['id'];
                    break;
                }else {
                    // if playtimes isnot 0, use random request
                    $playid = $ptarray[mt_rand(0, (count($ptarray)-1))]['id'];
                    if($playid == $lastplayid ){ // ランダムで選んだ結果前回と同じ曲だった
                        if(count($allrequest) <= 1 ) break; // リストが1曲だけの場合同じ曲を繰り返す
                        if(count($ptarray) > 1 ){  // 同一プレイ回数の曲がある場合
                            $check_playtimes--;
                            continue;  // もう一度ランダムに選択してみる
                        }else {                    // 同一プレイ回数の曲がない場合
                            // プレイ回数を更新して再チェック
                            $nextplayingtimes = minimum_playtimescheck_withoutme($allrequest,$playid) ;
                            $sql = "UPDATE requesttable set  playtimes = $nextplayingtimes WHERE id = $playid ";
                            $ret = $db->exec($sql);
                            if (! $ret ) {
                                logtocmd("id : $playid の再生回数 $nextplayingtimes への変更に失敗しました。<br>\n");
                            }
                            continue;
                        }
                    }
                    break;
                }
            }
            if( $check_playtimes >= 4096 ){
                logtocmd(" internal error, check_playtimes becomes 4096 : $check_playtimes\n");
                var_dump($ptarray);
            }
            if( $playid != -1){
                $sql = "SELECT * FROM requesttable  WHERE id = $playid ORDER BY reqorder ASC ";
                $select = $db->query($sql);
                $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
                $select->closeCursor();
            }
            $nextplayingtimes = minimum_playtimescheck_withoutme($allrequest,$playid);
        }
        
    }else {
        // 再生中となっている項目があるかチェック
        $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
        $select = $db->query($sql);
        $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        if(count($rowall) == 0){
            // 未再生の項目を検索
            $sql = "SELECT * FROM requesttable  WHERE nowplaying = '未再生' ORDER BY reqorder ASC ";
            $select = $db->query($sql);
            $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
            $select->closeCursor();
        }
    }
    logtocmd_dbg( 'ファイル再生準備開始'."\n");

    if(count($rowall) > 0 ) {
    $row=$rowall[0];
    if ($nosong == 1 ) {
        //break;
    }
     $word=$row['songfile'];
     $l_id=$row['id'];
     $l_fullpath=$row['fullpath'];
     $l_kind=$row['kind'];
     $l_playtimes=$row['playtimes'];
     $l_nowplaying=$row['nowplaying'];
     //$select->closeCursor();

     //config 再読込
     readconfig($dbname,$playmode,$playerpath,$foobarpath,$requestcomment,$usenfrequset,$historylog,$waitplayercheckstart,$playerchecktimes,$connectinternet,$usevideocapture,$moviefullscreen,$helpurl,$commenturl_base,$commentroot,$commenturl);
     if(! empty($playerpath)){
        $MPCPATH = $playerpath;
     }
     if( empty($waitplayercheckstart)){
        $waitplayercheckstart = 5;
     }
     if( empty($playerchecktimes)){
        $playerchecktimes = 3;
     }
       
       $loopflg = check_request_loop($db,$l_id);
       if( (strcmp ($l_kind , "カラオケ配信") === 0)  && ($loopflg != 1 ))
       {
          global $config_ini;

          if($l_nowplaying === '再生中' ){
              logtocmd("再生中(カラオケ配信)を検出。終了待ち\n");
          }else{
              if( array_key_exists('DeliveryCMD',$config_ini) && !empty($config_ini['DeliveryCMD']) ){
                  $cmd = urldecode($config_ini['DeliveryCMD']);
                  exec($cmd);
              }
              if( array_key_exists('usevideocapture',$config_ini) && ( $config_ini['usevideocapture'] == 1 || $config_ini['usevideocapture'] == 3)){ 
                  captureviewstart($playerpath,1);
              }
              $db->beginTransaction();
              $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
              $ret = $db->exec($sql);
              if (! $ret ) {
              	logtocmd("再生中 への変更に失敗しました。<br>");
              }
              $db->commit(); 
          }        
          // カラオケ配信になっている場合、リクエストのリストで再生済みに変更されるまで待機する
          logtocmd("カラオケ配信終了待ち。「曲終了」ボタンを押すか、再生状況が「再生済」に変更されるまで停止\n");
          runningcheck_shop_karaoke($db,$l_id);
          if( array_key_exists('DeliveryCMD',$config_ini) && !empty($config_ini['DeliveryCMD']) && array_key_exists('DeliveryCMDEND',$config_ini) && !empty($config_ini['DeliveryCMDEND']) ){
              $cmd = urldecode($config_ini['DeliveryCMDEND']);
              exec($cmd);
          } 
          if (array_key_exists('usevideocapture',$config_ini) )  {
              if($config_ini['usevideocapture'] == 3 ){
                 captureviewstop();
              } else if($config_ini['usevideocapture'] == 1 ){
                  // ビデオキャプチャーデバイスでは、画面を画面いっぱいに引きのばしたのを戻す(GV-USB2では縦長になるので)
                  $stretchvideo = true;
                  if(array_key_exists("stretchvideo", $config_ini)) {
                      if($config_ini["stretchvideo"] != 1){
                          $stretchvideo = false;
                      }
                  }
                  if($stretchvideo) {
                      global $MPCCMDURL;
                      $mpcstat = file_get_html_with_retry($MPCCMDURL."?wm_command=839",5,1);
                  }
                  $toggled3dfullscreen = false;
                  if(array_key_exists("toggled3dfullscreen", $config_ini)) {
                      if($config_ini["toggled3dfullscreen"] == 1){
                          $toggled3dfullscreen = true;
                      }
                  }
                  if($toggled3dfullscreen) {
                      global $MPCCMDURL;
                      $mpcstat = file_get_contents($MPCCMDURL."?wm_command=1023");
                  }
              }
          }
       }else
       {
    logtocmd_dbg( 'カラオケ配信ではないファイル再生準備開始'."\n");
       $ft = check_filetype ($db,$l_id);
       if( $ft !== 3){
           $filepath = get_fullfilename($l_fullpath,$word,$filepath_utf8);
    //logtocmd "Debug filepath: $filepath\r\n";
       }else {
            // logtocmd $l_kind;
            $filepath = $l_fullpath;
            $filepath_utf8= $filepath;
       }
       if($filepath === false ){
           logtocmd($word."ファイルが見つかりませんでした、Skipします\nファイルが入ったHDDが認識しなくなっているかもしれません");
       }else{
           
           // 拡張子をチェックしてPlayerを選択
           $filetype = check_filetype ($db,$l_id);
           if($l_nowplaying === '再生中' ){
                   logtocmd("再生中を検出。現在の曲の終了待ち\n");
           }else{
    logtocmd_dbg( 'ID指定ファイル再生開始'."\n");
               start_song($db,$l_id,1);
    logtocmd_dbg( 'ID指定ファイル再生開始完了'."\n");
               if($filetype == 3){
                   sleep(10); // URL指定はさらに10秒待ち 
               } 
               sleep($waitplayercheckstart); // Player 起動待ち
               // 再生時コメント表示
               if(commentenabledcheck()){
                   $nm=$row['singer'];
                   $msg=$row['comment'];
                   $col = 'FFFFFF';
                   $size = 3;

                   //commentpost_v1($nm,$col,$msg,$commenturl);
                   commentpost_v2($nm,$col,$size,$msg,$commenturl);
               }
           }
           if(mb_strstr($l_kind,'動画_別プ') != FALSE){
               // カラオケ配信になっている場合、リクエストのリストで再生済みに変更されるまで待機する
               logtocmd("別Player終了待ち。「曲終了」ボタンを押すか、再生状況が「再生済」に変更されるまで停止\n");
               runningcheck_shop_karaoke($db,$l_id);
               if(array_key_exists("otherplayer_path",$config_ini) && !empty($config_ini["otherplayer_path"]) ){
                   $opcmd = mb_convert_encoding(basename(urldecode($config_ini["otherplayer_path"])),"SJIS-win");
//                   print $opcmd;
                   $pscheck_cmd='taskkill  /im '.$opcmd.' -f';
//                   print $pscheck_cmd;
                   exec($pscheck_cmd);
               }
           }else{
               logtocmd_dbg( 'Enter check_end_song'."\n");
               check_end_song($db,$l_id,$playerchecktimes,$playmode);
               logtocmd_dbg( 'Exit check_end_song'."\n");
           }
           //logtocmd 'running check finished 終了'."\n";
       
        } // if($filepath === false )
        } // if( (strcmp ($l_kind , "カラオケ配信") === 0)  && ($loopflg != 1 ))



        $sql = "UPDATE requesttable set nowplaying = \"再生済\" WHERE id = $l_id ";
//     $sql = "UPDATE requesttable set nowplaying = \"未再生\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
        logtocmd_dbg( '再生済みに状態変更開始:'.$sql."\n");
        $ret = $db->exec($sql);
        if (! $ret ) {
            logtocmd("再生済への変更に失敗しました。<br>");
        }
        // 現在再生中だったもの以外に再生中となっていたものがあれば再生済？に変更する。(これで再生中になるのは常に0～1件になるはず)
        $sql = "UPDATE requesttable set nowplaying = \"再生済？\" WHERE nowplaying = \"再生中\" ";
        $ret = $db->query($sql);
        if (! $ret ) {
            logtocmd("再生済？ への変更に失敗しました。<br>");
        }
        logtocmd_dbg( '再生済みに状態変更終了:'.$sql."\n");
//     $db=null;
//     sleep(1);
        $played=1;

//        break;
    }
    
    if( $played === 5)
    logtocmd("no next song, waiting...<br>\n");
    logtocmd_dbg( '全体ループラスト（プロセスチェック開始)'."\n");
    workcheck_pfwd();
    workcheck_andrestartxampp();
    logtocmd_dbg( '全体ループラスト:'.$played."秒待ち\n");
    sleep($played);
//     break;

}
?>