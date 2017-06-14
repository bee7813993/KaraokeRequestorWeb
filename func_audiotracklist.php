<?php
require_once('modules/getid3/getid3.php');

function array_search_key($key,$value,$checkarray){
    foreach($checkarray as $k => $v ){
        if($v[$key] === $value ) return $k;
    }
    return false;
}
/*
   return 
   1 : video
   2 : audio
   0 : other
*/
function checktracktype($trackinfo){
    $mdiakey = array_search_key('name','mdia',$trackinfo['subatoms'] );
    if($mdiakey === false ) return array(false, NULL);
    $minfkey = array_search_key('name','minf',$trackinfo['subatoms'][$mdiakey]['subatoms'] );
    if($minfkey === false ) return array(false, NULL);
    $stblkey = array_search_key('name','stbl',$trackinfo['subatoms'][$mdiakey]['subatoms'][$minfkey]['subatoms'] );
    if($stblkey === false ) return array(false, NULL);
    $stsdkey = array_search_key('name','stsd',$trackinfo['subatoms'][$mdiakey]['subatoms'][$minfkey]['subatoms'][$stblkey]['subatoms'] );
    if($stsdkey === false ) return array(false, NULL);
    
    $mediainfo = $trackinfo['subatoms'][$mdiakey]['subatoms'][$minfkey]['subatoms'][$stblkey]['subatoms'][$stsdkey]['sample_description_table'][0];
    // var_dump($mediainfo );
    
    // video check
    if($mediainfo['audio_channels'] == 0 && $mediainfo['width'] > 0 &&  $mediainfo['height'] > 0 ) {
        return array(1, NULL);
    }
    // audio check
    if($mediainfo['audio_channels'] > 0  ) {
        $trackname = $mediainfo = $trackinfo['subatoms'][$mdiakey]['subatoms'][1]['component_name'];
        // print mb_convert_encoding($trackname, 'SJIS-win');
        
        return array(2, $trackname);
    }
    return 0;
}

function getaudiotracklist($filename){
    $getID3 = new getID3();
    $filename_host= mb_convert_encoding($filename, 'SJIS-win', 'UTF-8');
    $res = file_exists($filename_host);
    if($res){
       $info = $getID3->analyze($filename_host);
    }
    getid3_lib::CopyTagsToComments($info);
    $audiotracklist = array();
    
    if(!array_key_exists('quicktime',$info) ){
       return $audiotracklist;
    }

    $tracklist = $info['quicktime']['moov']['subatoms'];
    foreach ($tracklist as $trackinfo){
       if($trackinfo['name'] !== 'trak' ) continue;
       $tracktype = checktracktype($trackinfo);
       if($tracktype[0] == 2 ){
           $audiotracklist[] = $tracktype;
       }
    }
    return $audiotracklist;
}

// for test
//$audiotracklist = getaudiotracklist('C:\Users\y.higashi\Videos\[Aqours]ユメ語るよりユメ歌おう_ラブライブ！サンシャイン！！アニメED_[11tr]（On_Off_第2話_第4話_第5話_第6話_第7話_第8話_第10話_第11話_第3話＆第12話）.mp4');
//$tracknum = 1;
//foreach ($audiotracklist as $tracknameinfo){
//    print mb_convert_encoding('track'.$tracknum.':'.$tracknameinfo[1]."\n", 'SJIS-win');
//    $tracknum++;
//}


// function from manage-mpc.php

function file_exist_check_japanese($filename){
 $fileinfo = @fopen(addslashes($filename),'r');
 if($fileinfo != FALSE){
     fclose($fileinfo);
     // logtocmd 'DEBUG : Success fopen' ;
     return TRUE;
 }
 
 return FALSE;
}

function get_fullfilename($l_fullpath,$word,&$filepath_utf8){
    $filepath_utf8 = "";
    // ファイル名のチェック
    if(empty($l_fullpath) && empty($word) ) return "";
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
function logtocmd($msg){
  //print(mb_convert_encoding("$msg\n","SJIS-win"));
}

?>
