<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'func_audiotracklist.php';

$lister_dbpath=urldecode($config_ini['listerDBPATH']);

$uid=0;
if(array_key_exists("uid", $_REQUEST)) {
    $uid = $_REQUEST["uid"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}

$filename = "";
if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}else {
    if(!empty($fullpath) ) {
    $filename = basename_jp($fullpath);
    }
}


function extention_musiccheck($fn){
    if(empty($fn)) return 0;
    $extension = pathinfo($fn, PATHINFO_EXTENSION);
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

// 曲情報取得
$url = 'http://localhost/search_listerdb_filelist_json.php?uid='.$uid;
$programlist_json = file_get_contents($url);
   if(!$programlist_json) {
      $errmsg = '曲情報取得に失敗';
   }else {
      $programlist = json_decode($programlist_json,true);
   }
   $v=$programlist["data"][0];

      $displayname = $v["song_name"];
      if (empty($v["song_name"]) )
          $displayname = basename_jp($v["found_path"]);

$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

if(empty($fullpath) ){
    $fullpath=$v["found_path"];
    if(!empty($fullpath) ) {
        $filename = basename_jp($fullpath);
    }
}

$fullpath_utf8 = "";
$audiotracklist = array();
  get_fullfilename($fullpath,$filename,$fullpath_utf8,$lister_dbpath);
  $filetype = extention_musiccheck($fullpath_utf8);
  if(!empty($fullpath_utf8) && $filetype == 1 ) {
    $audiotracklist = getaudiotracklist($fullpath_utf8);
  }
  print json_encode($audiotracklist);


?>