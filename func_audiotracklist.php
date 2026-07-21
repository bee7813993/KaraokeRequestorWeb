<?php
require_once('modules/getid3/getid3.php');

// ==================== NAS I/O 停滞の連鎖ガード ====================
// NAS/SMB の I/O 停滞中に同じファイルへアクセスした後続リクエストが次々ブロックし、
// PHP ワーカーを食い潰す事故 (確認画面が開かない・予約の応答が返らない) を防ぐ。
// ファイル単位の非ブロッキングロックを取り、取れないとき (= 別プロセスが同じファイルの
// I/O で停滞している可能性) は待たずに「情報なし」で即応答させる。
// ロックはリクエスト終了時に自動解放される (明示解放しないことで、同一リクエスト内の
// 存在チェック → getID3 解析の間もロックを保持し続ける)。

$GLOBALS['_fileguard_handles'] = array();

// ファイルへの I/O を始めてよければ true。false ならこのファイルには触らないこと。
function _fileguard_enter($path) {
    if (empty($path)) return true;
    $key = md5($path);
    // 同一リクエスト内の再入 (存在チェック → 解析) は許可
    if (isset($GLOBALS['_fileguard_handles'][$key])) return true;

    $lock_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_getid3_cache';
    if (!is_dir($lock_dir) && !@mkdir($lock_dir, 0755, true)) {
        return true; // ロックを用意できない環境ではガードなしで従来どおり動作
    }
    $fp = @fopen($lock_dir . DIRECTORY_SEPARATOR . $key . '.lock', 'c');
    if ($fp === false) {
        return true; // 同上
    }
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return false;
    }
    $GLOBALS['_fileguard_handles'][$key] = $fp; // リクエスト終了まで保持
    return true;
}

function getphpversion_fa(){
  if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
  }
  return PHP_VERSION_ID;
}

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

    $typeafalse=0;
    
    $udtakey = array_search_key('name','udta',$trackinfo['subatoms'] );
    if($udtakey === false ) $typeafalse = 1;
    
    if($typeafalse == 0 ){
        $namekey = array_search_key('name','name',$trackinfo['subatoms'][$udtakey]['subatoms']);
        if($namekey === false ) $typeafalse = 1;
    }

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
    if(!array_key_exists('audio_channels',$mediainfo )) return false;
    
    if($mediainfo['audio_channels'] == 0 && $mediainfo['width'] > 0 &&  $mediainfo['height'] > 0 ) {
        return array(1, NULL);
    }
    // トラック名: udta/name があれば優先、なければ mdia/hdlr の component_name
    if($typeafalse == 0 ){
        $trackname = $trackinfo['subatoms'][$udtakey]["subatoms"][$namekey]['data'];
    }else {
        $hdlrkey = array_search_key('name', 'hdlr', $trackinfo['subatoms'][$mdiakey]['subatoms']);
        $trackname = ($hdlrkey !== false) ? $trackinfo['subatoms'][$mdiakey]['subatoms'][$hdlrkey]['component_name'] : '';
    }

    // audio check
    if($mediainfo['audio_channels'] > 0  ) {
        return array(2, $trackname);
    }
    return false;
}

// --- 内部ヘルパー: analyze済み $info からオーディオトラックリストを抽出 ---
function _audiotracklist_from_info($info) {
    $audiotracklist = array();
    if (!array_key_exists('quicktime', $info)) return $audiotracklist;
    // ④ moov がない場合（断片化MP4・破損ファイル等）は空リストを返す
    if (!isset($info['quicktime']['moov']['subatoms'])) return $audiotracklist;
    $tracklist = $info['quicktime']['moov']['subatoms'];
    foreach ($tracklist as $trackinfo) {
        if ($trackinfo['name'] !== 'trak') continue;
        $tracktype = checktracktype($trackinfo);
        if (($tracktype != false) && ($tracktype[0] == 2)) {
            $audiotracklist[] = $tracktype;
        }
    }
    return $audiotracklist;
}

// --- 内部ヘルパー: analyze済み $info から動画詳細を抽出 ---
function _videodetails_from_info($info) {
    $details = array();
    if (!empty($info['playtime_seconds'])) {
        $total_seconds = (int)round($info['playtime_seconds']);
        $details['duration_seconds'] = $total_seconds;
        $minutes = (int)($total_seconds / 60);
        $secs    = $total_seconds % 60;
        $details['duration'] = sprintf('%d:%02d', $minutes, $secs);
    }
    if (!empty($info['video']['frame_rate'])) {
        $details['frame_rate'] = round($info['video']['frame_rate'], 2);
    }
    if (!empty($info['video']['resolution_x']) && !empty($info['video']['resolution_y'])) {
        $details['resolution'] = $info['video']['resolution_x'] . 'x' . $info['video']['resolution_y'];
    }
    if (!empty($info['video']['codec'])) {
        $details['video_codec'] = $info['video']['codec'];
    } elseif (!empty($info['video']['fourcc_lookup'])) {
        $details['video_codec'] = $info['video']['fourcc_lookup'];
    }
    if (!empty($info['audio']['codec'])) {
        $details['audio_codec'] = $info['audio']['codec'];
    }
    if (!empty($info['audio']['channels'])) {
        $ch = (int)$info['audio']['channels'];
        $details['audio_channels'] = $ch === 1 ? 'モノラル' : ($ch === 2 ? 'ステレオ' : $ch . 'ch');
    }
    if (!empty($info['audio']['sample_rate'])) {
        $details['audio_sample_rate'] = number_format($info['audio']['sample_rate']) . ' Hz';
    }
    if (!empty($info['bitrate'])) {
        $details['bitrate'] = round($info['bitrate'] / 1000) . ' kbps';
    }
    return $details;
}

// --- 内部ヘルパー: ファイルパスをホストエンコーディングに変換して存在確認し analyze する ---
// $with_atom_data=true のときのみ QuickTime アトムデータを全量読み込む（重い）。
// 成功時は getID3 の $info 配列を返す。失敗時は false を返す。
function _getid3_analyze($filename, $with_atom_data = false) {
    // 別プロセスが同じファイルの I/O で停滞中なら解析しない (情報なし扱い)
    if (!_fileguard_enter($filename)) return false;
    $getID3 = new getID3();
    if ($with_atom_data) {
        $getID3->options_audiovideo_quicktime_ReturnAtomData = true;
    }
    $workencode = (getphpversion_fa() < 70100) ? 'SJIS-win' : 'UTF-8';
    $filename_host = mb_convert_encoding($filename, $workencode, 'UTF-8');
    setlocale(LC_CTYPE, 'Japanese_Japan.932');
    if (!file_exist_check_japanese($filename_host)) return false;
    $error_level = error_reporting();
    error_reporting($error_level & ~E_WARNING); // ⑤ 算術減算ではなくビット演算で除外
    $info = $getID3->analyze($filename_host);
    error_reporting($error_level);
    getid3_lib::CopyTagsToComments($info);
    return $info;
}

// オーディオトラックリストと動画詳細を1回の analyze で取得する（request_confirm_bs5 用）
// $need_tracklist=true のときだけ QuickTime アトムデータを読み込む。
// 結果はファイル更新日時ベースでディスクキャッシュされる（2回目以降は getID3 解析不要）。
// 戻り値: ['audiotracklist' => array, 'videodetails' => array]
function getfileinfo($filename, $need_tracklist = false) {
    // 別プロセスが同じファイルの I/O で停滞中なら待たずに「情報なし」で返す
    // (filemtime も NAS アクセスのためブロックし得る)
    if (!_fileguard_enter($filename)) {
        return array('audiotracklist' => array(), 'videodetails' => array());
    }
    $cache_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_getid3_cache';

    // ファイル更新日時取得（Windows パスのエンコーディング対応）
    $workencode = (getphpversion_fa() < 70100) ? 'SJIS-win' : 'UTF-8';
    $filename_host = mb_convert_encoding($filename, $workencode, 'UTF-8');
    $mtime = @filemtime($filename_host);

    // ③ filemtime が失敗（false）した場合はキャッシュを使わない
    //    空の結果を永続キャッシュしてしまうと、ファイルが復旧しても手動削除まで回復できない
    $use_cache = ($mtime !== false);

    // キャッシュキー: ファイルパス + アトムデータ有無 + ファイル更新日時
    $cache_key  = md5($filename . '|' . (int)$need_tracklist . '|' . $mtime);
    $cache_file = $cache_dir . DIRECTORY_SEPARATOR . $cache_key;

    // キャッシュヒット確認
    if ($use_cache && is_file($cache_file)) {
        $cached = @unserialize(file_get_contents($cache_file));
        if ($cached !== false) {
            return $cached;
        }
    }

    // getID3 解析
    $info = _getid3_analyze($filename, $need_tracklist);
    $result = array(
        'audiotracklist' => ($need_tracklist && $info !== false) ? _audiotracklist_from_info($info) : array(),
        'videodetails'   => ($info !== false) ? _videodetails_from_info($info) : array(),
    );

    // キャッシュ書き込み（filemtime が取れた場合のみ）
    if ($use_cache) {
        // ① キャッシュディレクトリが存在しない場合は作成
        if (!is_dir($cache_dir)) {
            if (!@mkdir($cache_dir, 0755, true)) {
                // ② mkdir 失敗をログに記録してキャッシュをスキップ
                error_log('[getfileinfo] cache dir creation failed: ' . $cache_dir);
                return $result;
            }
        }
        // ① .htaccess が存在しない場合は書き込む（再デプロイ後も確実に保護）
        $htaccess = $cache_dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
        // ② キャッシュ書き込み失敗をログに記録
        if (@file_put_contents($cache_file, serialize($result)) === false) {
            error_log('[getfileinfo] cache write failed: ' . $cache_file);
        }
    }

    return $result;
}

function getaudiotracklist($filename){
    $info = _getid3_analyze($filename, true);
    if ($info === false) return array();
    return _audiotracklist_from_info($info);
}

function getvideodetails($filename) {
    $info = _getid3_analyze($filename, false);
    if ($info === false) return array();
    return _videodetails_from_info($info);
}

// function from manage-mpc.php

function file_exist_check_japanese($filename){
  $filename_check = $filename;
  if(getphpversion_fa() < 70100 ){
   setlocale(LC_CTYPE, 'Japanese_Japan.932');
   $filename_check =addslashes($filename);
  }
 $fileinfo = @fopen($filename_check,'r');
 if($fileinfo != FALSE){
     fclose($fileinfo);
     // logtocmd 'DEBUG : Success fopen' ;
     return TRUE;
 }
 
 return FALSE;
}

function get_fullfilename($l_fullpath,$word,&$filepath_utf8,$lister_dbpath=''){
    $filepath_utf8 = "";
    // ファイル名のチェック
    if(empty($l_fullpath) && empty($word) ) return "";
    // 別プロセスが同じファイルの I/O で停滞中なら触らない
    // (存在チェックの fopen 自体が NAS 停滞でブロックするため)。
    // 空を返すと呼び出し元は「情報なし」のまま予約フローを継続できる
    if (!_fileguard_enter($l_fullpath)) {
        logtocmd("fileguard: busy file skipped: $l_fullpath");
        return "";
    }
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
      // ニコカラりすたーで検索
      if(!empty($lister_dbpath) ){
         logtocmd ("fullpass file $l_fullpath is not found. Search from NicokaraLister DB.: $songbasename\r\n");
         require_once('function_search_listerdb.php');
         // DB初期化
         $lister = new ListerDB();
         $lister->listerdbfile = $lister_dbpath;
         $listerdb = $lister->initdb();
         if( $listerdb ) {
              $select_where = ' WHERE found_path LIKE ' . $listerdb->quote('%'.$songbasename.'%');
              $sql = 'select * from t_found '. $select_where.';';
              $alldbdata = $lister->select($sql);
              if($alldbdata){
                  $filepath_utf8 = $alldbdata[0]['found_path'];
                  $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
                  logtocmd ($songbasename.'代わりに「'.$filepath_utf8.'」を再生します'."\n");
                  return $filepath;
              }
              // 曲名で再検索
              $select_where = ' WHERE found_path LIKE ' . $listerdb->quote('%'.$word.'%');
              $sql = 'select * from t_found '. $select_where.';';
              $alldbdata = $lister->select($sql);
              if($alldbdata){
                  $filepath_utf8 = $alldbdata[0]['found_path'];
                  $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
                  logtocmd ($word.'代わりに「'.$filepath_utf8.'」を再生します'."\n");
                  return $filepath;
              }
              
         }         
         
      }
      // Everythingで検索
      // logtocmd ("fullpass file $winfillpath is not found. Search from Everything DB.: $songbasename\r\n");
      $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($songbasename) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
      $json = file_get_html_with_retry($jsonurl, 5);
      if($json != false){
          $decode = json_decode($json, true);
          if($decode != NULL && isset($decode['results']['0'])){
            if(array_key_exists('path',$decode['results']['0']) && array_key_exists('name',$decode['results']['0'])){
                $filepath_utf8 = $decode['results']['0']['path'] . "\\" . $decode['results']['0']['name'];
                $filepath = mb_convert_encoding($filepath_utf8,"cp932","UTF-8");
            }
          }
      }
      if(empty($filepath)){
      // 曲名で再検索
          logtocmd ("fullpass basename $songbasename is not found. Search from Everything DB.: $word\r\n");
          $jsonurl = "http://" . "localhost" . ":81/?search=" . urlencode($word) . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
          // logtocmd $jsonurl;
          $json = file_get_html_with_retry($jsonurl, 5);
          $decode = json_decode($json, true);
          if( !isset($decode['results']['0']['name']) ) return false;
          $filepath = $decode['results']['0']['path'] . "\\" . $decode['results']['0']['name'];
          $filepath_utf8= $filepath;
          $filepath = mb_convert_encoding($filepath,"cp932");
          logtocmd ('代わりに「'.$filepath_utf8.'」を再生します'."\n");
      }
    }
    return $filepath;
}
function logtocmd($msg){
  //print(mb_convert_encoding("$msg\n","SJIS-win"));
  error_log($msg."\n", 3, 'ykrdebug.log');
}

?>
