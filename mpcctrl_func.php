<?php

require_once 'commonfunc.php';

$MPCCMDURL='http://localhost:13579/command.html';
$MPCSTATUSURL='http://localhost:13579/status.html';
$EASYKEYCHANGERURL='http://localhost:13580/command.html';

  function timestring(){
      $now = \DateTime::createFromFormat('U.u', sprintf('%6F', microtime(true)));
      return $now->format('Y-m-d H:i:s.u');
  }

  function clientipue(){
      return $_SERVER["HTTP_USER_AGENT"].$_SERVER["REMOTE_ADDR"];
  }
  
  function mkclienthash($count = 0){
      $hashbaseword = timestring().clientipue().$count;
      // print 'DEBUG:'.$hashbaseword.'<br>';
      $hashword = hash("md5", $hashbaseword, $raw_output = false);
      // print 'DEBUG:'.$hashword.'<br>';
      return substr($hashword,0,8);
  }

function songnext(){
      global  $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small> 曲終了ボタンを押されましたが、再生中の曲はありませんでした </small>'."\n";
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
          $db->beginTransaction();
          $ret = $db->exec($sql);
          $db->commit();
      }
}

function songstart(){
      global  $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生開始待ち' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      if($select === false) {
          command_mpc(887);
          return;
      }
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small>  </small>'."\n";
          command_mpc(887);
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = ".$currentsong[0]['id'];
          $db->beginTransaction();
          $ret = $db->exec($sql);
          $db->commit();
          sleep(2);
          command_mpc(887);
      }
}

function command_mpc($num){
    
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command='.$num;
    $res = file_get_html_with_retry($requesturl);
    switch($num) {
        case 901:
        case 902:
        case 903:
        case 904:
           $requesturl = createUri((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . ':'. $_SERVER["SERVER_PORT"]. $_SERVER["REQUEST_URI"], 'update_playerprogress.php' );
           $res = file_get_html_with_retry($requesturl);
           print $requesturl ;
           print $res;
        break;
    }
    return $res;
}

function delay_plus100_mpc(){
    global $MPCCMDURL;
    
    for($i=0;$i<10;$i++){
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=905';
    $res = file_get_html_with_retry($requesturl);
    usleep(30000);
    }
}

function delay_minus100_mpc(){
    global $MPCCMDURL;
    
    for($i=0;$i<10;$i++){
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=906';
    $res = file_get_html_with_retry($requesturl);
    usleep(50000);
    }
}

function start_first_mpc(){
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=-1&percent=0';
    $res = file_get_html_with_retry($requesturl);

    $requesturl = createUri((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . ':'. $_SERVER["SERVER_PORT"]. $_SERVER["REQUEST_URI"], 'update_playerprogress.php' );
    $res = file_get_html_with_retry($requesturl);

    return $res;
}

function toggle_mute_mpc(){
    return command_mpc(909);;
}

function go_end_mpc(){
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=-1&percent=99.5';
    $res = file_get_html_with_retry($requesturl);
    return $res;
}

function set_volume($c_volume){

    global $MPCCMDURL;

    $requesturl=$MPCCMDURL.'?wm_command=-2&volume='.trim($c_volume);
//    print $requesturl;
    file_get_html_with_retry($requesturl);
}

function get_volume(){

    global $MPCSTATUSURL;
    $statusformat = 'OnStatus(\'%s\', \'%s\', %d, \'%s\', %d, \'%s\', %d, %d, \'%s\')';

    $status = file_get_html_with_retry($MPCSTATUSURL);
    if($status === FALSE) return $status;
    // print $status;
    $status_array = explode(',', $status);
    // var_dump($status_array);
    return $status_array[7];
}

function volume_fadeout(){
    global $MPCCMDURL;

    $volume = get_volume();
    // print $volume;
    $delta_volume=round($volume/10);
    if($delta_volume < 4 ) $delta_volume = 4;
    for($c_volume = $volume ; $c_volume > 0 ; $c_volume -= $delta_volume){
    // print $c_volume;
        set_volume($c_volume);
        usleep(100000);
    }
    return $volume;
}

function keychange($keycmd){
    global $EASYKEYCHANGERURL;
    
    $clienttoken=mkclienthash();

    $res = TRUE;
    $requesturl=$EASYKEYCHANGERURL.'?key='.$keycmd.'&token='.$clienttoken;
    $res = file_get_html_with_retry($requesturl,1,1);
    update_requestdb_key();
    return $res;
}



function update_requestdb_key(){
    global $db;
    
    $keyinfourl = createUri((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . ':'. $_SERVER["SERVER_PORT"]. $_SERVER["REQUEST_URI"],"getcurrentkey.php");

    $res = file_get_html_with_retry($keyinfourl,2,2);

    if( $res === false ) return;
    if( $res == "None" ) return;
    if( !is_numeric($res) ) return;

    $sql = 'UPDATE requesttable SET keychange='.$res.' WHERE nowplaying="再生中";';
    $db->beginTransaction();
    $ret = $db->exec($sql);
    $db->commit();
    
}
?>