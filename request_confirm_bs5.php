<?php
$filename = "";
if(array_key_exists("filename", $_REQUEST)) {
    $filename = $_REQUEST["filename"];
}

$fullpath = "";
if(array_key_exists("fullpath", $_REQUEST)) {
    $fullpath = $_REQUEST["fullpath"];
}

$shop_karaoke = 0;
if(array_key_exists("shop_karaoke", $_REQUEST)) {
    $shop_karaoke = $_REQUEST["shop_karaoke"];
}

$set_directurl = 0;
if(array_key_exists("set_directurl", $_REQUEST)) {
    $set_directurl = $_REQUEST["set_directurl"];
}


$forcebgv = 0;
if(array_key_exists("forcebgv", $_REQUEST)) {
    $forcebgv = $_REQUEST["forcebgv"];
}

$set_pause = 0;
if(array_key_exists("pause", $_REQUEST)) {
    $set_pause = $_REQUEST["pause"];
}


$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    if(!is_numeric($selectid)){
        $selectid = 'none';
    }
}

$bgvfile = "";
if(array_key_exists("bgvfile", $_REQUEST)) {
    $bgvfile = $_REQUEST["bgvfile"];
    if($forcebgv == 1) $fullpath=$bgvfile;
}

/** リクエスト者を毎回新規入力にするかどうか（共有端末用とか） **/
/** (今のところハードコーディング) **/
$blank_username = false;

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'func_audiotracklist.php';

$lister_dbpath = '';
if(array_key_exists("listerDBPATH", $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}

$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

if($shop_karaoke == 1 && is_numeric($selectid)){
    $forcebgv = 1;
}

$stmt = $db->prepare("SELECT * FROM requesttable ORDER BY reqorder DESC");
$stmt->execute();
$allrequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$selectrequest = array();
if(is_numeric($selectid)){
    $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', (int)$selectid, PDO::PARAM_INT);
    $stmt->execute();
    $selectrequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
}

function pickupsinger($rt, $moreuser = "")
{
   $singerlist = array();
   if(!empty($moreuser)){
       $singerlist[] = $moreuser;
   }
   foreach($rt as $row)
   {
       $foundflg = 0;
       foreach($singerlist as $esinger ){
           if( $esinger === $row['singer']){
               $foundflg = 1;
               break;
           }
       }
       if($foundflg === 0){
           $singerlist[] = $row['singer'];
       }
   }

   return $singerlist;
}

function selectedcheck_rc($rt,$singer,$beforesinger = 'none' ){
    if($beforesinger == 'none'){
      foreach($rt as $row){

          if($row['singer'] === $singer){
            if($row['clientip'] === $_SERVER["REMOTE_ADDR"] ) {
              if($row['clientua'] === $_SERVER["HTTP_USER_AGENT"] ) {
                return TRUE;
              }
            }
          }
      }
    }else{
        if($singer === $beforesinger){
            return TRUE;
        }
    }

    return FALSE;
}

function json_safe_encode($data){
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function extention_musiccheck($fn){
    if(empty($fn)) return 0;
    $extension = pathinfo($fn, PATHINFO_EXTENSION);
    if( empty($extension) ){
        logtocmd ("ERROR : File has no extension : $fn");
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
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<?php print_bs5_head_core([], ['jquery' => true]); ?>
<title>リクエスト確認画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript">

function check(selectf){
flg=(document.getElementById('singer').selectedIndex==0);
if (!flg) document.getElementById('freesinger').value='';
document.getElementById('freesinger').parentNode.style.visibility=flg?'visible':'hidden';
}


window.onload = function(){
document.getElementById("requestconfirm").onsubmit = function(){
var newname = document.getElementById("freesinger").value;
var existname = document.getElementById("singer").value;

if (newname == "" && existname =="<?php print(urldecode($config_ini['nonameusername']));?>") {
<?php
if($config_ini['nonamerequest'] != 1){
print 'alert("リクエスト者が空欄です。名前を入れてください\n(次からはドロップダウンで選べます)");';
print 'return false;';
}
?>

}
}
}

</script>
<?php
$nanasyname = $config_ini["nonameusername"];
?>
<script id="nanasycheck" type="text/javascript" charset="utf8" src="js/requsetlist_confirm.js"
     data-nanasy ='<?php echo json_safe_encode(urldecode($nanasyname)); ?>'
     data-nanasyflg ='<?php echo json_safe_encode($config_ini['nonamerequest']); ?>'
> </script>

</head>
<body>
<?php
$YkariUsername = "";
if(array_key_exists("YkariUsername", $_COOKIE)) {
    $YkariUsername = $_COOKIE["YkariUsername"];
}
?>
<?php
shownavigatioinbar_bs5();
?>
<div class="container py-3">
<form method="post" action="exec.php" id="requestconfirm">
<div class="mb-3">
<label class="form-label">
曲名(ファイル名)
</label>
<textarea name="filename" id="filename" class="form-control" rows="4" wrap="soft" style="width:100%"
<?php
if(is_numeric($selectid) && !empty($selectrequest) && $selectrequest[0]['kind'] == "カラオケ配信"){
    echo "> ";
    print htmlspecialchars($selectrequest[0]['songfile'], ENT_QUOTES, 'UTF-8');
    echo "</textarea> ";
}else if($shop_karaoke == 1){
    print 'placeholder="後でセットリスト作成の参考のためにできれば曲名を入れておいてください" >';

    if (empty($filename)){
      echo "";
    }else{
      echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }

    echo "</textarea> ";
}else if($set_directurl == 1 ){
    print 'placeholder="直接再生できるURLを指定を入れてください(youtubeのURLもOK)" >';
    if (empty($filename)){
      echo "";
    }else{
      echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }
    echo "</textarea> ";
}else if($set_pause == 1 ){
    print 'placeholder="小休止時のリストに表示するメッセージ" >';
    if (!empty($filename)){
      echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }else if(!empty($config_ini['pause_default_filename'])){
      echo htmlspecialchars(urldecode($config_ini['pause_default_filename']), ENT_QUOTES, 'UTF-8');
    }
    echo "</textarea> ";
}else {
    print 'placeholder="曲名" disabled >';

    if (empty($filename)){
      echo "";
    }else{
      echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }
    echo "</textarea> ";
    print '<input type="hidden" name="filename" id="filename" style="width:100%" value="'.htmlspecialchars($filename, ENT_QUOTES, 'UTF-8').'"  />';
    }
?>

    <input type="hidden" name="fullpath" id="fullpath" style="width:100%" value="<?php echo htmlspecialchars($fullpath, ENT_QUOTES, 'UTF-8'); ?>" />
<?php
if(is_numeric($selectid) && !empty($selectrequest) && $selectrequest[0]['kind'] == "カラオケ配信"){
    print '<dt> BGV曲名 </dt>';
    print '<dd>'. htmlspecialchars($filename, ENT_QUOTES, 'UTF-8').' <dd>';
}
?>
</div>

<div class="mb-3">
<label class="form-label">リクエスト者</label>
<select name="singer" onchange="check(this.form)" onfocus="check(this.form)" id="singer" class="form-select">
<option value="<?php print(urldecode($config_ini['nonameusername']));?>">新規入力↓</option>
<?php
$num = 1;

$beforesinger = 'none';
if(is_numeric($selectid) && !empty($selectrequest)){
  $beforesinger = $selectrequest[0]['singer'];
}
$selectedcounter = 0;
$singerlist = pickupsinger($allrequest,$YkariUsername);
$pausecount = 0;
foreach($singerlist as $singer)
{
  print "<option value=\"";
  print htmlspecialchars($singer, ENT_QUOTES, 'UTF-8');
  print "\"";
  if($set_pause) {
      // 小休止モード: 「小休止」のみ選択
      if($singer === '小休止') {
          print " selected ";
          $selectedcounter = $selectedcounter + 1;
      }
  } else if(is_numeric($selectid) && !empty($selectrequest)) {
      // 差し替えモード: 元のリクエスト者を優先
      if($singer === $beforesinger && $selectedcounter === 0) {
          print " selected ";
          $selectedcounter = $selectedcounter + 1;
      }
  } else if($blank_username){
  } else if(!empty($YkariUsername)){
      if($singer === $YkariUsername){
         print " selected ";
         $selectedcounter = $selectedcounter + 1 ;
      }
  } else if( selectedcheck_rc($allrequest,$singer,$beforesinger) && $selectedcounter === 0 )
  {
      print " selected ";
      $selectedcounter = $selectedcounter + 1 ;
  }
  print "> ";
  print htmlspecialchars($singer);
  print "</option>";
  if($singer == '小休止'){
      $pausecount++;
  }
}
if($set_pause && $pausecount == 0) {
print '<option value="小休止" selected>小休止</option>';
$selectedcounter = $selectedcounter + 1;
}

?>



</select>
<?php
if($selectedcounter === 0){
print('<span style="visibility:visible;">');
}else{
print('<span style="visibility:hidden;">');
}
?>
<input type="text" name="freesinger" id="freesinger" class="form-control mt-2" placeholder="名前を書いてね。２回目からは上のドロップダウンから選べるようになります。" value="" >
</span>
</div>

<div class="mb-3">
<label class="form-label">コメント</label>
<textarea name="comment" id="comment" class="form-control" rows="4" wrap="soft" placeholder="<?php print htmlspecialchars($requestcomment);?>" style="width:100%" >
<?php
if(is_numeric($selectid) && !empty($selectrequest) ){
    print htmlspecialchars($selectrequest[0]['comment']);
}else if($set_pause == 1 && !empty($config_ini['pause_default_comment'])){
    print htmlspecialchars(urldecode($config_ini['pause_default_comment']), ENT_QUOTES, 'UTF-8');
}
?>
</textarea>
</div>

<div class="mb-3">
<dl>
<dt>再生方法</dt>
<dd>
<?php
  if(is_numeric($selectid) && !empty($selectrequest) && $selectrequest[0]['kind'] == "カラオケ配信"){
      print htmlspecialchars($selectrequest[0]['kind'], ENT_QUOTES, 'UTF-8');
      print '<input type="hidden" name="kind" id="kind"  value="'.htmlspecialchars($selectrequest[0]['kind'], ENT_QUOTES, 'UTF-8').'" />'."\n";
      $forcebgv = 1;
  }else if($shop_karaoke == 1){
      print 'カラオケ配信'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="カラオケ配信" />'."\n";
  }else if($set_directurl == 1){
      print 'URL指定'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="URL指定" />'."\n";
  }else if($set_pause == 1){
      print '小休止'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="小休止" />'."\n";
  }else{
      print 'ファイル再生(動画/音楽)'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="動画" />'."\n";
  }
?>
</dd>
</dl>
<?php

/* ファイルの存在チェック */
$fullpath_utf8 = "";
$audiotracklist = array();
if($shop_karaoke != 1 ){
    get_fullfilename($fullpath,$filename,$fullpath_utf8,$lister_dbpath);
    $filetype = extention_musiccheck($fullpath_utf8);
    if(!empty($fullpath_utf8) && $filetype == 1 ) {
        $audiotracklist = getaudiotracklist($fullpath_utf8);
    }
}

/* キー変更が有効かどうかのチェック */
/* 配信→無効 */
/* 設定で無効 → 無効 */
/* 設定で有効 → 有効 */
function keychangecheck($config_ini, $shop_karaoke){
    if($shop_karaoke == 1) return false;
    if(array_key_exists('usekeychange' ,$config_ini )) {
        if( $config_ini['usekeychange'] == 1 ) return true;
    }
    return false;
}

/* キー変更 */
if(keychangecheck($config_ini, $shop_karaoke) && $filetype == 1){
    print <<<EOT
<dl>
<dt>キー変更</dt>
<dd>
<div class="btn-group flex-wrap" role="group" aria-label="キー変更">
    <input type="radio" class="btn-check" name="keychange" id="key_m6" value="-6" autocomplete="off">
    <label class="btn btn-secondary" for="key_m6">-6</label>
    <input type="radio" class="btn-check" name="keychange" id="key_m5" value="-5" autocomplete="off">
    <label class="btn btn-secondary" for="key_m5">-5</label>
    <input type="radio" class="btn-check" name="keychange" id="key_m4" value="-4" autocomplete="off">
    <label class="btn btn-secondary" for="key_m4">-4</label>
    <input type="radio" class="btn-check" name="keychange" id="key_m3" value="-3" autocomplete="off">
    <label class="btn btn-secondary" for="key_m3">-3</label>
    <input type="radio" class="btn-check" name="keychange" id="key_m2" value="-2" autocomplete="off">
    <label class="btn btn-secondary" for="key_m2">-2</label>
    <input type="radio" class="btn-check" name="keychange" id="key_m1" value="-1" autocomplete="off">
    <label class="btn btn-secondary" for="key_m1">-1</label>
    <input type="radio" class="btn-check" name="keychange" id="key_0" value="0" autocomplete="off" checked>
    <label class="btn btn-secondary" for="key_0">原曲</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p1" value="1" autocomplete="off">
    <label class="btn btn-secondary" for="key_p1">1</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p2" value="2" autocomplete="off">
    <label class="btn btn-secondary" for="key_p2">2</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p3" value="3" autocomplete="off">
    <label class="btn btn-secondary" for="key_p3">3</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p4" value="4" autocomplete="off">
    <label class="btn btn-secondary" for="key_p4">4</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p5" value="5" autocomplete="off">
    <label class="btn btn-secondary" for="key_p5">5</label>
    <input type="radio" class="btn-check" name="keychange" id="key_p6" value="6" autocomplete="off">
    <label class="btn btn-secondary" for="key_p6">6</label>
</div>
</dd>
</dl>
EOT;

}

if( $shop_karaoke != 1 && $filetype == 1){

    print <<<EOT
<dl>
<dt>トラック選択</dt>
<dd>
EOT;
    if(empty($audiotracklist)){
        print '<div >オーディオトラックが判別できなかったのでとりあえず3トラック表示しています </div>';
        print '<select name="track" class="form-select">';
        $maxtrack = 3;
        for($c = 0; $c < $maxtrack ; $c++ ){
          print '  <option value="'.$c.'" >'.($c+1).'トラック目'.'</option>'."\n";
        }
    } else {
        $maxtrack = count($audiotracklist);
        if(  $maxtrack == 1  &&   (  strlen($audiotracklist[0][1]) == 0 || strpos( $audiotracklist[0][1] , 'Sound Media Handler' ) !== false || strpos( $audiotracklist[0][1] , 'GPAC ISO Audio Handler' ) !== false || strpos( $audiotracklist[0][1] , 'SoundHandler' ) !== false )){
        print "<pre> 1トラックのみ </pre>";
        }else {
        print '<select size="'. $maxtrack .'" name="track"  class="form-select">';
        for($c = 0; $c < $maxtrack ; $c++ ){
          if($c == 0 ){
              print '  <option value="'.$c.'" selected >'.($c+1).'トラック目：'.$audiotracklist[$c][1].'</option>'."\n";
          }else {
              print '  <option value="'.$c.'" >'.($c+1).'トラック目：'.$audiotracklist[$c][1].'</option>'."\n";
          }
        }
        }

    }
    print <<<EOT
</select>
</dd>
</dl>
EOT;
}

$videodetails = array();
$duration_seconds = 0;
if ($shop_karaoke != 1 && !empty($fullpath_utf8) && ($filetype == 1 || $filetype == 2)) {
    $videodetails = getvideodetails($fullpath_utf8);
    if (isset($videodetails['duration_seconds'])) {
        $duration_seconds = $videodetails['duration_seconds'];
    }
}
if ($shop_karaoke != 1 && $filetype == 1 && !empty($fullpath_utf8) && !empty($videodetails)) {
        print '<div class="card mt-2">'."\n";
        print '<div class="card-header" id="videoDetailsHeading">'."\n";
        print '<button class="btn btn-link btn-sm text-start p-0 collapsed" type="button"'
              . ' data-bs-toggle="collapse" data-bs-target="#videoDetailsCollapse"'
              . ' aria-expanded="false" aria-controls="videoDetailsCollapse">'."\n";
        print '動画詳細情報 ▼'."\n";
        print '</button>'."\n";
        print '</div>'."\n";
        print '<div id="videoDetailsCollapse" class="collapse" aria-labelledby="videoDetailsHeading">'."\n";
        print '<div class="card-body py-2">'."\n";
        print '<dl class="row mb-0">'."\n";
        if (isset($videodetails['duration'])) {
            print '<dt class="col-sm-4">曲の長さ</dt><dd class="col-sm-8">'.htmlspecialchars($videodetails['duration']).'</dd>'."\n";
        }
        if (isset($videodetails['resolution'])) {
            print '<dt class="col-sm-4">解像度</dt><dd class="col-sm-8">'.htmlspecialchars($videodetails['resolution']).'</dd>'."\n";
        }
        if (isset($videodetails['frame_rate'])) {
            print '<dt class="col-sm-4">フレームレート</dt><dd class="col-sm-8">'.htmlspecialchars($videodetails['frame_rate']).' fps</dd>'."\n";
        }
        if (isset($videodetails['video_codec'])) {
            print '<dt class="col-sm-4">映像コーデック</dt><dd class="col-sm-8">'.htmlspecialchars($videodetails['video_codec']).'</dd>'."\n";
        }
        if (isset($videodetails['audio_codec'])) {
            $audio_info = htmlspecialchars($videodetails['audio_codec']);
            if (isset($videodetails['audio_channels'])) {
                $audio_info .= ' / ' . htmlspecialchars($videodetails['audio_channels']);
            }
            if (isset($videodetails['audio_sample_rate'])) {
                $audio_info .= ' / ' . htmlspecialchars($videodetails['audio_sample_rate']);
            }
            print '<dt class="col-sm-4">音声コーデック</dt><dd class="col-sm-8">'.$audio_info.'</dd>'."\n";
        }
        if (isset($videodetails['bitrate'])) {
            print '<dt class="col-sm-4">ビットレート</dt><dd class="col-sm-8">'.htmlspecialchars($videodetails['bitrate']).'</dd>'."\n";
        }
        print '</dl>'."\n";
        print '</div>'."\n";
        print '</div>'."\n";
        print '</div>'."\n";
}
echo '<input type="hidden" name="duration" value="' . (int)$duration_seconds . '" />' . "\n";

// 制作者別音ズレ・音量デフォルト値を取得（ListerDB の found_worker と完全一致で判定）
$audiodelay_init = 0;
$volume_init = 0; // 0 = 変更なし（全体設定の戻す音量をそのまま使用）
if ($shop_karaoke != 1 && $filetype == 1 && !empty($fullpath_utf8)) {
    $delay_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'creator_audiodelay.json';
    if (file_exists($delay_file)) {
        $delay_rules = json_decode(file_get_contents($delay_file), true);
        if (is_array($delay_rules) && !empty($delay_rules)) {
            // ListerDB から found_worker を取得
            $found_worker = '';
            if (!empty($lister_dbpath)) {
                require_once('function_search_listerdb.php');
                $lister = new ListerDB();
                $lister->listerdbfile = $lister_dbpath;
                $listerdb = $lister->initdb();
                if ($listerdb) {
                    $songbasename = basename($fullpath_utf8);
                    $sql = 'SELECT found_worker FROM t_found WHERE found_path LIKE '
                         . $listerdb->quote('%' . $songbasename . '%') . ' LIMIT 1';
                    $rows = $lister->select($sql);
                    if (!empty($rows) && isset($rows[0]['found_worker'])) {
                        $found_worker = $rows[0]['found_worker'];
                    }
                }
            }
            if ($found_worker !== '') {
                $vd_fps = isset($videodetails['frame_rate']) ? floatval($videodetails['frame_rate']) : null;
                foreach ($delay_rules as $drule) {
                    $dk = isset($drule['keyword']) ? $drule['keyword'] : '';
                    if ($dk === '') continue;
                    if ($found_worker !== $dk) continue;
                    if (!empty($drule['fps']) && $vd_fps !== null) {
                        $rule_fps  = floatval($drule['fps']);
                        $fps_cond  = isset($drule['fps_cond']) ? $drule['fps_cond'] : '以下';
                        if ($fps_cond === '以上') {
                            if ($vd_fps < $rule_fps) continue;
                        } else {
                            if ($vd_fps > $rule_fps) continue;
                        }
                    }
                    $audiodelay_init = isset($drule['delay']) ? intval($drule['delay']) : 0;
                    if (isset($drule['volume']) && $drule['volume'] !== '') {
                        $v = intval($drule['volume']);
                        if ($v >= -100 && $v <= 100) $volume_init = $v;
                    }
                    break;
                }
            }
        }
    }
}
// 差し替えモードの場合は元のリクエストの音ズレ・音量値を優先
if (is_numeric($selectid) && !empty($selectrequest)) {
    $audiodelay_init = intval($selectrequest[0]['audiodelay']);
    if (array_key_exists('volume', $selectrequest[0]) && $selectrequest[0]['volume'] !== null && $selectrequest[0]['volume'] !== '') {
        $v = intval($selectrequest[0]['volume']);
        if ($v >= -100 && $v <= 100) $volume_init = $v;
    }
}
?>
<?php if ($shop_karaoke != 1 && $filetype == 1): ?>
<div class="mt-2 mb-1 text-secondary">
  <small>音ズレ補正：
  <button type="button" class="btn btn-secondary btn-sm" onclick="changeAudioDelay(-100)">-100ms</button>
  <span id="audiodelay_disp" style="display:inline-block; min-width:65px; text-align:center;"><?php echo intval($audiodelay_init); ?> ms</span>
  <button type="button" class="btn btn-secondary btn-sm" onclick="changeAudioDelay(100)">+100ms</button>
  <input type="hidden" name="audiodelay" id="audiodelay_val" value="<?php echo intval($audiodelay_init); ?>" />
  </small>
</div>
<div class="mt-1 mb-1 text-secondary">
  <small>音量増減：
  <button type="button" class="btn btn-secondary btn-sm" onclick="changeVolume(-5)">-5%</button>
  <span id="volume_disp" style="display:inline-block; min-width:80px; text-align:center;"><?php $vi = intval($volume_init); echo ($vi > 0 ? '+' : '') . $vi . ' %'; ?></span>
  <button type="button" class="btn btn-secondary btn-sm" onclick="changeVolume(5)">+5%</button>
  <button type="button" class="btn btn-secondary btn-sm" onclick="resetVolume()">±0に戻す</button>
  <input type="hidden" name="volume" id="volume_val" value="<?php echo intval($volume_init); ?>" />
  </small>
</div>
<script>
function changeAudioDelay(delta){
  var inp = document.getElementById('audiodelay_val');
  var disp = document.getElementById('audiodelay_disp');
  var v = parseInt(inp.value, 10) + delta;
  if(v < -9900) v = -9900;
  if(v > 9900) v = 9900;
  inp.value = v;
  disp.textContent = v + ' ms';
}
function changeVolume(delta){
  var inp = document.getElementById('volume_val');
  var disp = document.getElementById('volume_disp');
  var cur = parseInt(inp.value, 10);
  if(isNaN(cur)) cur = 0;
  cur = cur + delta;
  if(cur < -100) cur = -100;
  if(cur > 100) cur = 100;
  inp.value = cur;
  disp.textContent = (cur > 0 ? '+' : '') + cur + ' %';
}
function resetVolume(){
  var inp = document.getElementById('volume_val');
  var disp = document.getElementById('volume_disp');
  inp.value = 0;
  disp.textContent = '0 %';
}
</script>
<?php endif; ?>

</div>
<div class="mb-2 form-check">
<input type="checkbox" class="form-check-input" name="secret" value="1" id="chk_secret" />
<label class="form-check-label" for="chk_secret">シークレットリクエスト(歌うまで曲名を表示しません)</label>
</div>
<?php
if($config_ini['usebgv'] == 1 && $shop_karaoke != 1 && $filetype == 1){
print '<div class="mb-2 form-check">';
print '<input type="checkbox" class="form-check-input" name="loop" value="1" id="chk_bgv" ';
if($forcebgv == 1 ){
    print 'checked';
}
print ' />';
print '<label class="form-check-label" for="chk_bgv">BGVモード <small class="text-muted"> この動画をカラオケ配信のBGVとしてリクエストします。</small></label>';
print '</div>';
}

$typecheckfn = "";
if(empty($fullpath)){
  if(!empty($filename))
      $typecheckfn = $filename;
}else{
$typecheckfn = $fullpath;
}

if($config_ini['useotherplayer'] == 1 && $shop_karaoke != 1 && extention_musiccheck($typecheckfn) == 1){
print '<div class="mb-2 form-check">';
print '<input type="checkbox" class="form-check-input" name="otherplayer" value="1" id="chk_other" />';
print '<label class="form-check-label" for="chk_other">';
if(empty($config_ini["otherplayer_disc"])){
print '別プレイヤー再生';
}else{
print urldecode($config_ini["otherplayer_disc"]);
}
print '</label>';
print '</div>';
}

if(configbool("useuserpause", false) || $user == 'admin' ){
print '<div class="mb-2 form-check">';
print '<input type="checkbox" class="form-check-input" name="pause" value="1" id="chk_pause" ';
if($set_pause == 1 ){
print 'checked';
}
print '/>';
print '<label class="form-check-label" for="chk_pause">小休止リクエスト</label>';
print '</div>';
}
if(is_numeric($selectid)){
print '<input type="hidden" name="selectid" id="selectid"  value='.$selectid.' />'."\n";
}

if($shop_karaoke == 1){
print '<div class="alert alert-info mt-2">';
print '<ul class="mb-0">';
print '<li>自分の番が回ってきたら、「デンモク」から歌いたい曲をリクエストしてください</li>';
print '<li>歌い終わったら、「リクエスト一覧」から「曲終了」ボタンを押してください</li>';
if($config_ini['usebgv'] == 1 ){
  print '<li>このカラオケ配信リクエストの後、「リスト操作」ボタンを押した後に出てくる「BGV選択」から配信曲の字幕の裏に流す動画を選ぶことができます</li>';
}
print '</ul>';
print '</div>';
}
?>
<div class="row mt-3">
<div class="col-12 col-sm-8">
<input type="submit" value="実行" name="requestnow" class="requestconfirm btn btn-primary btn-lg w-100" />
</div>
</div>

</form>
<div class="mt-3 d-flex gap-2">
<button type="button" onclick="location.href='search.php' " class="btn btn-secondary">
通常検索に戻る
</button>

<button type="button" onclick="location.href='requestlist_top.php' " class="btn btn-secondary">
トップに戻る
</button>
</div>
</div>
</body>
</html>
