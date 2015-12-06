<?php
//setlocale(LC_ALL,  'ja_JP.UTF-8','ja_JP.Shift_JIS');
if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    logtocmd('Locale not found: ja_JP.UTF-8');
    exit(1);
}

date_default_timezone_set('Asia/Tokyo');
include 'kara_config.php';
require_once 'commonfunc.php';
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
$MPCCMDURL='http://localhost:13579/command.html';
$MPCFILEOPENURL='http://localhost:13579/browser.html?path=';
$FOOBARSTATURL='http://localhost:82/karaokectrl/';

function file_exist_check_japanese($filename){

 $fileinfo=file_exists($filename);
 if($fileinfo != FALSE){
     return TRUE;
 }

 $fileinfo = fopen(addslashes($filename),'r');
 if($fileinfo != FALSE){
     fclose($fileinfo);
     // logtocmd 'DEBUG : Success fopen' ;
     return TRUE;
 }
 
 return FALSE;
}

function logtocmd($msg){
  print(mb_convert_encoding("$msg\n","SJIS-win"));
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
   $pscheck_cmd='tasklist /fi "imagename eq "'.$playercommandname;
   //logtocmd $pscheck_cmd;
   exec($pscheck_cmd, $psresult );
   sleep(1);
   
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




function mpcplaylocalfile($playerpath,$playfilepath,$playmode,$waittime = 1){
     global $MPCCMDURL;
     global $MPCFILEOPENURL;

    if(mpcrunningcheck($playerpath)===FALSE){
        startmpcandwait($playerpath,$waittime);
    }

   // wait mpc web 
   file_get_html_with_retry($MPCCMDURL);

    for($loopcount = 0 ; $loopcount < 1 ; $loopcount ++){
       $requesturl = $MPCFILEOPENURL.rawurlencode($playfilepath);
       // logtocmd $requesturl;
       $mpcstat = file_get_contents($requesturl, 1);
       
       if( $mpcstat === FALSE) {
           //var_dump($mpcstat);
           sleep(1);
           continue;
       }else{
           break;
       }
    }
    if( $playmode == 2) {
        //一時停止する
        sleep($waittime);
        file_get_html_with_retry($MPCCMDURL."?wm_command=888");
    }
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
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' || $currentstatus[0]['nowplaying'] === '再生済'){
           logtocmd (date("H.i.s")."Status is change to ".$currentstatus[0]['nowplaying']." at id: ".$id."\n");
           $exit = 0;
           break;
       }
       //logtocmd "DEBUG: Endtime: " . date("H.i.s", $endtime) . ", Now: ".date("H.i.s", time());
       sleep(2);
   }
}

function runningcheck_audio($db,$id,$playerchecktimes){

   $exit = 1;
   while($exit == 1)
   {
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' ){
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

function runningcheck_mpc($db,$id,$playerchecktimes){

   global $MPCSTATURL;
   // get MPC status
   $exit = 1;
   while($exit == 1)
   {
       // db statusを確認
       $sql = "SELECT nowplaying FROM requesttable  WHERE id = $id ORDER BY reqorder ASC ";
       $select = $db->query($sql);
       $currentstatus = $select->fetchAll(PDO::FETCH_ASSOC);
       $select->closeCursor();
       //var_dump($currentstatus);
       if( $currentstatus[0]['nowplaying'] === '停止中' ){
           break;
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
       if($playtime > ($totaltime - 4) ){
       print ($mpsctat_array[2]);
           echo ', ';
           print ($playtime);
           echo ':';
           print ($totaltime);
           echo "\n";
           break;
       }
       // logtocmd "DEBUG : $mpsctat_array[2], $playtime : $totaltime \n";

       sleep(2);
   }
}


while(1){

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
/*                    if(count($ptarray) == 1 && $playid == $lastplayid && count($ptarray) != 1 ){
                        $nosong = 1;
                        $nextplayingtimes = minimum_playtimescheck_withoutme($allrequest,$playid) - 1 ;
                        $sql = "UPDATE requesttable set  playtimes = $nextplayingtimes WHERE id = $playid ";
                        $ret = $db->exec($sql);
                        if (! $ret ) {
                            logtocmd("id : $playid の再生回数 $nextplayingtimes への変更にしっぱいしました。<br>\n");
                        }
                        break;
                    }
                    if($playid == $lastplayid && count($allrequest) != 1 ){
                        $nextplayingtimes = minimum_playtimescheck_withoutme($allrequest,$playid) - 1 ;
                        $sql = "UPDATE requesttable set  playtimes = $nextplayingtimes WHERE id = $playid ";
                        $ret = $db->exec($sql);
                        if (! $ret ) {
                            logtocmd("id : $playid の再生回数 $nextplayingtimes への変更にしっぱいしました。<br>\n");
                        }
                        continue;
                    }
*/
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
     

       
       if( strcmp ($l_kind , "カラオケ配信") === 0 )
       {
          if($l_nowplaying === '再生中' ){
              logtocmd("再生中(カラオケ配信)を検出。終了待ち\n");
          }else{
              if( $usevideocapture == 1 ) {
                  mpcdevicestart($playerpath,1);
              }
              $db->beginTransaction();
              $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
              $ret = $db->exec($sql);
              if (! $ret ) {
              	logtocmd("再生中 への変更にしっぱいしました。<br>");
              }
              $db->commit(); 
          }        
          // カラオケ配信になっている場合、リクエストのリストで再生済みに変更されるまで待機する
          logtocmd("カラオケ配信終了待ち。「曲終了」ボタンを押すか、再生状況が「再生済」に変更されるまで停止\n");
          runningcheck_shop_karaoke($db,$l_id);
       }else
       {
       
       if(strcmp ($l_kind , "URL指定") !== 0){
           // ファイル名のチェック
    //logtocmd "Debug l_fullpath: $l_fullpath\r\n";
           $winfillpath = mb_convert_encoding($l_fullpath,"SJIS-win");
           $fileinfo=file_exist_check_japanese($winfillpath);
           if($fileinfo !== FALSE){
               $filepath = $winfillpath;
               $filepath_utf8=$l_fullpath;
           }else{
     logtocmd ("fullpass file $winfillpath is not found. Search from Everything DB.: $word\r\n");
             $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
             // logtocmd $jsonurl;
             $json = file_get_html_with_retry($jsonurl, 5);
             $decode = json_decode($json, true);
             $filepath = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
             $filepath_utf8= $filepath;
             $filepath = mb_convert_encoding($filepath,"cp932");
             logtocmd ('代わりに「'.$filepath_utf8.'」を再生します'."\n");
           }
    //logtocmd "Debug filepath: $filepath\r\n";
        }else {
            // logtocmd $l_kind;
            $filepath = $l_fullpath;
            $filepath_utf8= $filepath;
        }
           
           // 拡張子をチェックしてPlayerを選択
           $extension = pathinfo($filepath, PATHINFO_EXTENSION);
           if( (strcasecmp($extension,"mp3") == 0 
           || strcasecmp($extension,"m4a") == 0 
           || strcasecmp($extension,"wav") == 0 ) && (strcmp ($l_kind , "URL指定") !== 0) ){
               // audio file
               if($l_nowplaying === '再生中' ){
                   logtocmd("再生中(foobar再生)を検出。現在の曲の終了待ち\n");
               }else{
                   if($playmode == 1 || $playmode == 4 || $playmode == 5){
                       $execcmd="start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " \"$filepath\"\n";
                   }elseif ($playmode == 2){
                       $execcmd="start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " \"$filepath\"\n";
                   }else{
                       logtocmd(" Debug : now auto play is off : $playmode\n");
                       sleep(30);
                       continue;
                   }
                 //  logtocmd(" Debug : execcmd : $execcmd\n");
                   
                   // 再生長取得
                   /* foo_http_controlを使用するようにしたので無効化
                   */
                   if($nextplayingtimes === 0){
                       $l_playtimes = $l_playtimes + 1;
                   }else {
                       $l_playtimes = $nextplayingtimes;
                   }
                   $db->beginTransaction();
                   $sql = "UPDATE requesttable set nowplaying = \"再生中\", playtimes = $l_playtimes WHERE id = $l_id ";
                   $ret = $db->exec($sql);
                   if (! $ret ) {
                   	logtocmd("再生中 への変更に失敗しました。<br>");
                   }
                   $db->commit();
                   // とりあえず動画Playerを終了する。
                   mpcstop();

                   sleep(1);
                   startfoobarandwait($filepath,3);
                   if ($playmode == 2){
                      sleep(1);
                      exec("start  \"\" \"".mb_convert_encoding($FOOBARPATH,"SJIS")."\"" . " /pause \n");
                   }
                   sleep($waitplayercheckstart); // Player 起動待ち

                   // 再生時コメント表示
                   if(isset($commenturl)){
                       $nm=$row['singer'];
                       $msg=$row['comment'];
                       $col = 'FFFFFF';
                       $size = 3;

                       //commentpost_v1($nm,$col,$msg,$commenturl);
                       commentpost_v2($nm,$col,$size,$msg,$commenturl);
                   }
               }
               runningcheck_audio($db,$l_id,$playerchecktimes);
               
           }else {
               if($l_nowplaying === '再生中' ){
                   logtocmd("再生中(MPC再生)を検出。現在の曲の終了待ち\n");
               }else{
//                 // video file

                   if($playmode == 1 || $playmode == 4 || $playmode == 5){
                   $execcmd="start /b \"\" \"".$MPCPATH."\"" . " /play \"$filepath\"\n";
                   // MPC起動チェック
                   if(mpcrunningcheck($playerpath)===FALSE){
                       startmpcandwait($playerpath,1);
                   }
                   }elseif ($playmode == 2){
                   $execcmd="start  \"\" \"".$MPCPATH."\"" . " /open \"$filepath\"\n";
                   // MPC起動チェック
                   if(mpcrunningcheck($playerpath)===FALSE){
                       startmpcandwait($playerpath,1);
                   }
                   //logtocmd ('MPC running check finished '."\n");
                   
                   }else{
                       logtocmd(" now auto play is off : $playmode\n");
                       sleep(30);
                       continue;
                   }
                   // logtocmd(" Debug : execcmd : $execcmd\n");
                   if($nextplayingtimes === 0){
                       $l_playtimes = $l_playtimes + 1;
                   }else {
                       $l_playtimes = $nextplayingtimes;
                   }
                   $db->beginTransaction();
                   $sql = "UPDATE requesttable set nowplaying = \"再生中\", playtimes = $l_playtimes  WHERE id = $l_id ";
                   $ret = $db->exec($sql);
                   if (! $ret ) {
                     logtocmd("再生中 への変更に失敗しました。<br>");
                   }
                   $db->commit();
                   sleep(1);
                   // web経由でファイル再生
                   //logtocmd 'MPC fileopen start '."\n";
                   if(strcmp ($l_kind , "URL指定") == 0){
                       exec($execcmd);
                   }else{
                       mpcplaylocalfile($playerpath,$filepath_utf8,$playmode,1);
                   }
                   //logtocmd 'MPC fileopen end '."\n";
                   
                   // logtocmd mb_convert_encoding("DEBUG : Player 起動完了を $waitplayercheckstart 秒待っています\n","SJIS-win");
                   if(strcmp ($l_kind , "URL指定") === 0){
                       sleep(5); // URL指定はさらに5秒待ち 
                   } 
                   sleep($waitplayercheckstart); // Player 起動待ち
                   // 再生時コメント表示
                   if(isset($commenturl)){
                       $nm=$row['singer'];
                       $msg=$row['comment'];
                       $col = 'FFFFFF';
                       $size = 3;

                       //commentpost_v1($nm,$col,$msg,$commenturl);
                       commentpost_v2($nm,$col,$size,$msg,$commenturl);
                   }
               }
               runningcheck_mpc($db,$l_id,$playerchecktimes);
               //logtocmd 'running check finished 終了'."\n";
           }
       
        }



        $sql = "UPDATE requesttable set nowplaying = \"再生済\" WHERE id = $l_id ";
//     $sql = "UPDATE requesttable set nowplaying = \"未再生\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
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
//     $db=null;
//     sleep(1);
        $played=1;

//        break;
    }
    
    if( $played === 5)
    logtocmd("no next song, waiting...<br>\n");

    sleep($played);
//     break;

}
?>