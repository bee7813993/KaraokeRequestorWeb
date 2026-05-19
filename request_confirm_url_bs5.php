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

$selectid = 'none';
if(array_key_exists("selectid", $_REQUEST)) {
    $selectid = $_REQUEST["selectid"];
    if(!is_numeric($selectid)){
        $selectid = 'none';
    }
}

if($shop_karaoke == 1 && is_numeric($selectid)){
    $forcebgv = 1;
}

require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

$sql = "SELECT * FROM requesttable ORDER BY reqorder DESC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

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

function selectedcheck_request($rt,$singer,$beforesinger = 'none' ){
    $rt_i = array_reverse($rt);

    if($beforesinger == 'none'){
      foreach($rt_i as $row){
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

?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
<script src="js/jquery.js"></script>
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
<label class="form-label">URL</label>
<input type="text" name="fullpath" id="fullpath" class="form-control"
    placeholder="直接再生できるURLを指定を入れてください(youtubeやニコニコ動画のURLもOK)"
    value="<?php echo htmlspecialchars((string)$filename, ENT_QUOTES, 'UTF-8'); ?>" />
</div>
<div class="mb-3">
<label class="form-label">曲名</label>
<textarea name="filename" id="filename" class="form-control" rows="4" wrap="soft"
    placeholder="後でセットリスト作成の参考のためにできれば曲名を入れておいてください"></textarea>
</div>

<div class="mb-3">
<label class="form-label">リクエスト者</label>
<select name="singer" onchange="check(this.form)" onfocus="check(this.form)" id="singer" class="form-select">
<option value="<?php print(urldecode($config_ini['nonameusername']));?>">新規入力↓</option>
<?php
$beforesinger = 'none';
if(is_numeric($selectid) && !empty($selectrequest)){
  $beforesinger = $selectrequest[0]['singer'];
}
$selectedcounter = 0;
$singerlist = pickupsinger($allrequest,$YkariUsername);
foreach($singerlist as $singer){
{
  print "<option value=\"";
  print htmlspecialchars((string)$singer, ENT_QUOTES, 'UTF-8');
  print "\"";
  if( selectedcheck_request($allrequest,$singer,$beforesinger) && $selectedcounter === 0 )
  {
      print " selected ";
      $selectedcounter = $selectedcounter + 1 ;
  }
  print "> ";
  print htmlspecialchars((string)$singer, ENT_QUOTES, 'UTF-8');
  print "</option>";
}
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
<textarea name="comment" id="comment" class="form-control" rows="4" wrap="soft"
    placeholder="<?php print htmlspecialchars((string)$requestcomment, ENT_QUOTES, 'UTF-8');?>" style="width:100%">
<?php
if(is_numeric($selectid) && !empty($selectrequest)){
    print htmlspecialchars((string)$selectrequest[0]['comment'], ENT_QUOTES, 'UTF-8');
    if($selectrequest[0]['kind'] == "カラオケ配信"){
      print "\n";
      print htmlspecialchars((string)$selectrequest[0]['songfile'], ENT_QUOTES, 'UTF-8');
    }
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
      $esc_kind = htmlspecialchars((string)$selectrequest[0]['kind'], ENT_QUOTES, 'UTF-8');
      print $esc_kind.'＋URL指定';
      print '<input type="hidden" name="kind" id="kind"  value="'.$esc_kind.'＋URL指定" />'."\n";
      $forcebgv = 1;
  }else if($shop_karaoke == 1){
      print 'カラオケ配信'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="カラオケ配信" />'."\n";
  }else if($set_directurl == 1){
      print 'URL指定'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="URL指定" />'."\n";
  }else{
      print 'ファイル再生(動画/音楽)'."\n";
      print '<input type="hidden" name="kind" id="kind"  value="動画" />'."\n";
  }
?>
</dd>
</dl>
<?php
if(isset($config_ini['usekeychange']) && $config_ini['usekeychange'] == 1 ){
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
?>
</div>

<div class="mb-2 form-check">
<input type="checkbox" class="form-check-input" name="secret" value="1" id="chk_secret" />
<label class="form-check-label" for="chk_secret">シークレット予約(歌うまで曲名を表示しません)</label>
</div>
<?php
if($config_ini['usebgv'] == 1 ){
print '<div class="mb-2 form-check">';
print '<input type="checkbox" class="form-check-input" name="loop" value="1" id="chk_bgv" ';
if($forcebgv == 1 ){
    print 'checked';
}
print ' />';
print '<label class="form-check-label" for="chk_bgv">BGVモード</label>';
print '</div>';
}
if(is_numeric($selectid)){
print '<input type="hidden" name="selectid" id="selectid"  value="'.(int)$selectid.'" />'."\n";
print '<input type="hidden" name="urlreq" id="urlreq"  value="1" />'."\n";
}
?>
<div class="row mt-3">
<div class="col-12 col-sm-8">
<input type="submit" value="実行" class="requestconfirm btn btn-primary btn-lg w-100" />
</div>
</div>

</form>
<div class="mt-3 d-flex gap-2">
<button type="button" onclick="location.href='search.php'" class="btn btn-secondary">
通常検索に戻る
</button>

<button type="button" onclick="location.href='requestlist_top.php'" class="btn btn-secondary">
トップに戻る
</button>
</div>
</div>
</body>
</html>
