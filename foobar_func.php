<?php

require_once 'commonfunc.php';

$foobarctrlurl = "http://localhost:82/karaokectrl/";
$nowplayingurl = "http://localhost/playingsong.php";

function foobar_songstart(){
      require_once 'commonfunc.php';
      global  $foobarctrlurl;
      global $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生開始待ち' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      if($select === false) return;
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $starturl = $foobarctrlurl."?cmd=Start&param1=0";
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small>  </small>'."\n";
          $res = file_get_html_with_retry($starturl);
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = ".$currentsong[0]['id'];
          $ret = $db->exec($sql);
          sleep(2);
          $res = file_get_html_with_retry($starturl);
      }
}


function songnext(){
      require_once 'kara_config.php';
      global  $foobarctrlurl;
      global $db;
      
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
      $ret = $db->exec($sql);
}

function foobar_song_play(){
      global  $foobarctrlurl;
      $requesturl=$foobarctrlurl."?cmd=Start&param1=0";
      $res = file_get_html_with_retry($requesturl);
      return $res;
}

function foobar_song_pause(){
      global  $foobarctrlurl;
      $requesturl=$foobarctrlurl."?cmd=PlayOrPause&param1=0";
      $res = file_get_html_with_retry($requesturl);
      return $res;
}

function foobar_song_vup(){
      global  $foobarctrlurl;
      global  $nowplayingurl;

      $status = file_get_html_with_retry($nowplayingurl);
      $status_array = json_decode($status,true);
      if(array_key_exists('volume',$status_array)){
          $newvolume = $status_array['volume'] + 5;
          $requesturl=$foobarctrlurl."?cmd=Volume&param1=".$newvolume;
          $res = file_get_html_with_retry($requesturl);
          return $res;
      }
}

function foobar_song_vdown(){
      global  $foobarctrlurl;
      global  $nowplayingurl;

      $status = file_get_html_with_retry($nowplayingurl);
      $status_array = json_decode($status,true);
      if(array_key_exists('volume',$status_array)){
          $newvolume = $status_array['volume'] - 5;
          if($newvolume < 0 ) $newvolume = 0;
          $requesturl=$foobarctrlurl."?cmd=Volume&param1=".$newvolume;
          $res = file_get_html_with_retry($requesturl);
          return $res;
      }
}

function foobar_song_stop(){
      global  $foobarctrlurl;
      
      songnext();
      $requesturl=$foobarctrlurl."?cmd=PlayOrPause&param1=0";
      // $res = file_get_html_with_retry($requesturl,1);
      // error_log(print_r($res,true),"3","C:/xampp/log/debug.log");
      return $res;
}

function foobar_song_restart(){
      global  $foobarctrlurl;
      $requesturl=$foobarctrlurl."?cmd=SeekSecond&param1=0";
      $res = file_get_html_with_retry($requesturl);
      return $res;
}



?>