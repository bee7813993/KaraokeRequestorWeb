<?php

require_once 'commonfunc.php';

$MPCCMDURL='http://localhost:13579/command.html';

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
          $ret = $db->exec($sql);
      }
}

function songstart(){
      global  $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生開始待ち' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      if($select === false) return;
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small>  </small>'."\n";
          command_mpc(887);
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = ".$currentsong[0]['id'];
          $ret = $db->exec($sql);
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

function go_end_mpc(){
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=-1&percent=99.5';
    $res = file_get_html_with_retry($requesturl);
    return $res;
}


?>