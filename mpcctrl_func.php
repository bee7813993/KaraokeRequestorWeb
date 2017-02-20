<?php

require_once 'commonfunc.php';

$MPCCMDURL='http://localhost:13579/command.html';
$MPCSTATUSURL='http://localhost:13579/status.html';

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
    return $res;
}

function delay_plus100_mpc(){
    global $MPCCMDURL;
    
    for($i=0;$i<10;$i++){
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=905';
    $res = file_get_html_with_retry($requesturl);
    usleep(50000);
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


?>