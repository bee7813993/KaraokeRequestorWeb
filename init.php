<?php

require_once 'commonfunc.php';
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])){
    header('WWW-Authenticate: Basic realm="Please use username admin to open Configuration page. "');
    die('設定画面の表示にはログインが必要です');
}

if ($_SERVER['PHP_AUTH_USER'] !== 'admin'){
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('設定画面の表示にはログインが必要です');
}

if (! $configauth -> check_auth($_SERVER['PHP_AUTH_PW']) ){
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('設定画面の表示にはログインが必要です');
}


require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth -> do_eashauthcheck();

$newconfig = $_REQUEST;

// 背景画像のアップロード/削除処理
// クロップ済み画像はクライアントから data URL (base64) で送信される。
$bgimage_upload_msg = '';

// data URL を images/bg/ に保存し、相対パスを返す。失敗時は null。
function save_bg_dataurl($dataurl, &$msg, $label) {
    if (!is_string($dataurl) || $dataurl === '') return null;
    if (!preg_match('#^data:image/(jpeg|png|webp|gif);base64,#', $dataurl, $m)) {
        $msg = $label . 'の画像形式が不正です';
        return null;
    }
    $ext_map = ['jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp', 'gif' => 'gif'];
    $ext = $ext_map[$m[1]];
    $bin = base64_decode(substr($dataurl, strpos($dataurl, ',') + 1), true);
    if ($bin === false) { $msg = $label . 'のデコードに失敗しました'; return null; }
    if (@getimagesizefromstring($bin) === false) { $msg = $label . 'の画像を認識できませんでした'; return null; }
    $bgdir = __DIR__ . '/images/bg';
    if (!is_dir($bgdir)) { @mkdir($bgdir, 0777, true); }
    $name = 'bg_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    if (@file_put_contents($bgdir . '/' . $name, $bin) === false) {
        $msg = $label . 'の保存に失敗しました';
        return null;
    }
    return 'images/bg/' . $name;
}

// PC用・スマホ用のクロップ結果を保存
foreach ([
    'bgimage_data'        => ['bgimage',        'PC用背景画像'],
    'bgimage_mobile_data' => ['bgimage_mobile', 'スマホ用背景画像'],
] as $field => $meta) {
    if (isset($_REQUEST[$field]) && $_REQUEST[$field] !== '') {
        $saved = save_bg_dataurl($_REQUEST[$field], $bgimage_upload_msg, $meta[1]);
        if ($saved !== null) {
            $newconfig[$meta[0]] = $saved;
            $bgimage_upload_msg = $meta[1] . 'を保存しました';
        }
    }
    // 一時フィールドは config.ini に書き込まない
    unset($newconfig[$field]);
}

// 削除処理
foreach ([
    'bgimage_delete'        => ['bgimage',        'PC用背景画像'],
    'bgimage_mobile_delete' => ['bgimage_mobile', 'スマホ用背景画像'],
] as $field => $meta) {
    if (isset($_REQUEST[$field]) && $_REQUEST[$field] == '1') {
        $newconfig[$meta[0]] = '';
        $bgimage_upload_msg = $meta[1] . 'をクリアしました';
    }
    unset($newconfig[$field]);
}


if(array_key_exists("clearauth", $_REQUEST)) {
    header('HTTP/1.0 401 Unauthorized');
}

//include 'kara_config.php';
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<title>設定画面</title>
<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}})();</script>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/themes/_variables.css" rel="stylesheet">
<link rel="stylesheet" href="css/themes/theme-toggle.css">
<link type="text/css" rel="stylesheet" href="css/style.css">
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
<script src="js/theme-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<style>
/* BS3 互換: radio-inline / checkbox-inline は BS5 に存在しないため独自定義 */
.radio-inline, .checkbox-inline {
  display: inline-flex; align-items: center; gap: 4px;
  margin-right: 12px; cursor: pointer;
}
.radio-inline input, .checkbox-inline input { margin: 0; }
/* 固定ナビバー分のアンカースクロールオフセット */
:target { scroll-margin-top: 80px; }
/* 説明ラベル（.radio-inline/.checkbox-inline 以外）をブロック化して
   ラジオボタンが次の行に来るようにする */
.mb-3 > label:not(.radio-inline):not(.checkbox-inline) {
  display: block;
  margin-bottom: 0.25rem;
}
/* 設定セクションのカード化 */
.cfg-card {
  margin-bottom: 1.25rem;
  border-left: 4px solid var(--bs-primary, #0d6efd);
  background-color: rgba(var(--bg-card-rgb, 255, 255, 255), var(--bg-card-alpha, 1));
  color: var(--color-text, #212529);
}
.cfg-card > .card-body { padding: 1rem 1.25rem; }
.cfg-card .menulink { scroll-margin-top: 80px; }
/* セクション見出し(h1/h3 を問わず)を統一書式に */
.cfg-card h1.menulink,
.cfg-card h3.menulink {
  font-size: 1.3rem;
  font-weight: 700;
  margin-top: 0;
}
/* 背景画像クロッパー */
.bgimg-thumb { max-width: 160px; max-height: 120px; border: 1px solid #ccc; }
.bgimg-viewport {
  position: relative; overflow: hidden; background: #222;
  touch-action: none; margin: 6px 0; border: 1px solid #ccc;
  max-width: 100%;
}
.bgimg-block[data-target="pc"]     .bgimg-viewport { width: 320px; height: 180px; }
.bgimg-block[data-target="mobile"] .bgimg-viewport { width: 180px; height: 320px; }
.bgimg-cropimg {
  position: absolute; top: 0; left: 0;
  max-width: none; user-select: none; -webkit-user-drag: none; pointer-events: none;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Tooltip (BS5 native)
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
    new bootstrap.Tooltip(el);
  });
  // スムーススクロール（アンカーリンク）
  document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var id = this.getAttribute('href');
      if (!id || id === '#') return;
      var target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      var top = target.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top: top, behavior: 'smooth' });
    });
  });
});
</script>
</head>
<body>
<?php
shownavigatioinbar_bs5('init.php');
?>
<div style="height:100px;">
</div>
<?php
$change_counter = 0;

/**** for DEBUG
print '<pre>';
print "_REQUEST\n";
var_dump($_REQUEST);
print "config_ini\n";
var_dump($config_ini);
print '</pre>';
***/


// to urlencode
$new_roomurlshow = array();
$search_show_order = array();

foreach($newconfig as $key => $value){
    if(is_array($value)){
      if($key == 'searchitem' ){
           $newconfig['searchitem'] = $value;
      }else if($key == 'searchitem_o' ){
           $ordervalue = 0;
           foreach($value as $k => $ordervalue){
             if( ! is_numeric($ordervalue) ) {
               if(empty($search_show_order) ){
//             print "comea";
                   $ordervalue = 1;
               }else {
                   $ordervalue = max($search_show_order) + 1;
               }
             }else {
              if(in_array($ordervalue, $search_show_order)){
                if(empty($search_show_order) ){
//             print "comeb";
                   $ordervalue = 1;
                }else {
                   $ordervalue = max($search_show_order) + 1;
                }
              }
             }
//             print $ordervalue;
             $value[$k] = $ordervalue;
             $search_show_order[] = $ordervalue;
           }
           $newconfig['searchitem_o'] = $value;
      }else {
        $roomcount = 0;
        foreach ($value as $roomno => $roomurl){
            if($key === 'roomurl') {
                if(!empty($newconfig['roomno'][$roomno])){
                    $newconfig['roomurl'][$newconfig['roomno'][$roomno]]=urlencode($roomurl);
                }else{
                }
                if(array_key_exists("roomurlshow", $newconfig)){
                  foreach($newconfig["roomurlshow"] as $rv ){
                    if( $rv == $roomcount ){
//                        print "now setting newconfig['roomurlshow'][".$newconfig['roomno'][$roomno].']';
                        $new_roomurlshow += array( $newconfig['roomno'][$roomno] => 1 );
                    }else{
                    }
                  }
                }
            unset($newconfig['roomno'][$roomno]);
            unset($newconfig['roomurl'][$roomno]);
            }
            $roomcount++;
        }
      }
    }else {
        $newconfig[$key] = urlencode($value);
    }
}

if(!empty($newconfig) ) $newconfig['roomurlshow'] = $new_roomurlshow ;

// usev2ui（統合設定）を個別キーに展開
if (isset($newconfig['usev2ui'])) {
    $v2 = ($newconfig['usev2ui'] == '1');
    $newconfig['usenewrequestlist'] = $v2 ? '1' : '0';
    $newconfig['usenewsearchui']    = $v2 ? '1' : '2';
    unset($newconfig['usev2ui']);
}

// $config_ini['roomurl'] = array();
$config_ini_new = array_merge($config_ini,$newconfig);

if(!empty($config_ini_new) ){
    $change_counter++;
}

if(  $change_counter > 0 ){

/**** for debug
print '<pre>';
print "config_ini_mod\n";
var_dump($config_ini_new);
print '</pre>';
*****/

    writeconfig2ini($config_ini_new,$configfile);
    $config_ini = $config_ini_new;
}

?>

<?php 
//echo '<pre>';
// var_dump($config_ini); 

// print "form input\n";
// var_dump($_REQUEST); 
// if( multiroomenabled()){print "enabled";}
//echo '</pre>';
?>

<div class="container menulink">
<div class="row">
<div class="col-lg-3 order-lg-last col-12 mb-3">
<!--- メニュー --->
<div class="card sticky-top" style="top:80px;z-index:100;">
  <div class="card-header fw-bold">もくじ</div>
  <div class="card-body p-2">
    <ul>
    <li class="menu">
     <a href="#listctrl" class="menulink" > リクエストリスト操作 </a>
    </li>
    <li class="menu">
     <a href="#opbuttom" class="menulink" > 操作ボタン（設定・アップデート） </a>
    </li>
    <li class="menu">
     <a href="#workconfig" class="menulink" > 動作設定 </a>
    </li>
    <ul>
    <li class="menu">
     <a href="#autoplay" class="menulink" > 自動再生設定 </a>
    </li>
    <li class="menu">
     <a href="#topmessage" class="menulink" > トップ画面メッセージ </a>
    </li>
    <li class="menu">
     <a href="#requestlist" class="menulink" > リクエスト一覧画面 </a>
    </li>
    <li class="menu">
     <a href="#bgcolor_t" class="menulink" > ページ背景色 </a>
    </li>
    <li class="menu">
     <a href="#bgimage_t" class="menulink" > 背景画像 </a>
    </li>
    <li class="menu">
     <a href="#movieplayer" class="menulink" > 動画プレーヤー </a>
    </li>
    <li class="menu">
     <a href="#searchscreen" class="menulink" > 検索画面 </a>
    </li>
    <li class="menu">
     <a href="#useinternet" class="menulink" > インターネット接続 </a>
    </li>
    <li class="menu">
     <a href="#commentserver" class="menulink" > コメントサーバー </a>
    </li>
    <li class="menu">
     <a href="#otherroom" class="menulink" > 別部屋URL設定 </a>
    </li>
    <li class="menu">
     <a href="pfwd_settings.php" class="menulink" > オンライン接続設定 </a>
    </li>
    <li class="menu">
     <a href="#googlesync" class="menulink" > Google同期設定 </a>
    </li>
    </ul>
    <li class="menu">
     <a href="#myiplist" class="menulink" > 自IP一覧 </a>
    </li>
    </ul>
  </div>
</div>
</div>
<div class="col-lg-9 order-lg-first col-12 menulink">
<div class="card cfg-card mb-4"><div class="card-body">
  <h1 id="listctrl" class="menulink" > リクエストリスト操作 </h1>
  <h3> リストのダウンロード </h3>
  <a href ="listexport.php"  class="btn btn-secondary" > リクエストリストのダウンロード(UTF-8) </a>
  <a href ="listexport_sjis.php"  class="btn btn-secondary" > (SJIS) </a>
  <h3> リストのインポート(csvより) </h3>
  <form action="listimport.php" method="post" enctype="multipart/form-data">
    <label > 
      <input type="file" name="dbcsv" accept="text/comma-separated-values" />
      <select name="importtype" id="importtype" class="form-control" > 
        <option value="new" >新規</option>
        <option value="add" >追加</option>
      </select>
    </label>
    <input type="submit" value="Send" />  
  </form>
  <h3> リスト消去 </h3>
  <a href ="listclear.php" class="btn btn-secondary" > リクエストリストの全消去 </a>

  <h3> リスト未再生化 </h3>
  <form method="post" action="delete.php">
    <input type="submit" name="resetstatus" value="全て未再生化" class="btn btn-secondary" />
  </form>

  <h3> 曲の長さ一括更新 </h3>
  <a href="update_duration_all.php" class="btn btn-secondary" onclick="return confirm('リクエストリスト内の全曲の長さを取得し直します。曲数が多い場合は時間がかかります。実行しますか？')">
    曲の長さを全件登録し直す
  </a>

  <h3> BGMモード用再生回数操作 </h3>
  <li>
    <a href ="listtimesclear.php?times=0" class="btn btn-secondary" > 再生回数0クリア </a>【BGMモード(ジュークボックスモード)にて次から全て順番に再生】
  </li>
  <li>
    <a href ="listtimesclear.php?times=1" class="btn btn-secondary" > 再生回数1クリア </a>【BGMモード(ジュークボックスモード)にて次から全てランダムに再生】
  </li>
</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <p>
  <h1 id="opbuttom" class="menulink">各種操作ボタン </h1>
  <h3>ログイン情報クリア </h3>
    <a href ="init.php?clearauth=1" class="btn btn-secondary" > ログイン情報クリア (対応ブラウザのみ)</a>
  </p>
  <p>
  <h3>CSV出力列設定</h3>
    <a href="edit_csv_columns.php" class="btn btn-secondary" > CSV出力列設定 </a>
  </p>
  <p>
  <h3>製作者別設定 <small>（りすたーDB検索のみ有効）</small></h3>
    <a href="edit_search_sort_priority.php" class="btn btn-secondary" > 製作者優先表示設定 </a>
    &nbsp;
    <a href="edit_creator_audiodelay.php" class="btn btn-secondary" > 制作者別音ズレ・音量初期値設定 </a>
  </p>
  <p>
  <h3>表示優先度設定 <small>（Everything検索のみ有効）</small></h3>
    <a href="edit_priority.php" class="btn btn-secondary" > 表示優先度設定 (Everything) </a>
  </p>

  <h3>オンラインアップデート画面 </h3>
  <p>
    <a href ="online_update.php" class="btn btn-secondary" > オンラインアップデート画面 </a>
  </p>
<script type="text/javascript">
function start_yklistercmd(){
var request = createXMLHttpRequest();
url="yklister_exec.php?start=1";
request.open("GET", url, true);
request.onreadystatechange = function() {
    if(request.readyState == 4) {
        if (request.status === 200) {
        }
    }
}
request.send("");
}

function storeAppLaunch(url, btnId, label) {
    var btn = document.getElementById(btnId);
    if (btn) { btn.disabled = true; btn.textContent = '起動中…'; }
    var request = createXMLHttpRequest();
    request.open("GET", url, true);
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            if (btn) { btn.disabled = false; btn.textContent = label; }
            if (request.status === 200) {
                try {
                    var res = JSON.parse(request.responseText);
                    if (!res.ok) {
                        alert('起動に失敗しました:\n' + res.msg);
                    }
                } catch(e) {}
            } else {
                alert('通信エラー: ' + request.status);
            }
        }
    };
    request.send("");
}

function start_yklisterstore_cmd(){
    storeAppLaunch("yklister_exec.php?start_store=1", "listerbt", "ゆかりすたー起動");
}
function start_yukkoview2_cmd(){
    storeAppLaunch("yklister_exec.php?start_yukkoview2=1", "yukkoview2bt", "ゆっこビュー 2 起動");
}
</script>
  <p>
<?php
  require_once 'function_search_listerdb.php';
  $listerapi_btn = new ListerDB();

  $yukalisterpath= 'YukaLister\YukaLister.exe';
  $addattr = '';
  if (file_exist_check_japanese_cf($yukalisterpath) ){
    $addattr = ' onClick="start_yklistercmd()"';
  } elseif ($listerapi_btn->isInstalledYkListerStore()) {
    $addattr = ' onClick="start_yklisterstore_cmd()"';
  } else {
    $addattr = ' disabled ';
  }
print '<button type="button" class="btn btn-secondary" id="listerbt" '.$addattr.'> ゆかりすたー起動 </button>';

  $yukkoattr = $listerapi_btn->isInstalledYukkoView2() ? ' onClick="start_yukkoview2_cmd()"' : ' disabled ';
print '<button type="button" class="btn btn-secondary" id="yukkoview2bt" '.$yukkoattr.'> ゆっこビュー 2 起動 </button>';
?>
  </p>

  <p>
  <h3>オンライン接続設定</h3>
    <a href="pfwd_settings.php" class="btn btn-secondary" > オンライン接続設定 </a>
  </p>

  <a href="requestlist_top.php" class="btn btn-secondary" > リクエストTOP画面に戻る　</a>
</div></div>

  <form name="allconfig" method="post" action="init.php" enctype="multipart/form-data">
<div class="card cfg-card mb-4"><div class="card-body">
  <h1  id="workconfig"  class="menulink" >動作設定 </h1>

  <div class="mb-3">
    <h3 title="未設定でパスワードチェックを省略">設定画面パスワード</h3>
    <input type="password" name="configpass" id="configpass" class="form-control" placeholder="未設定でパスワードチェックを省略"  value=<?php echo  $configauth -> show_password(); ?> >
  </div>

  <div class="mb-3">
    <h3>DBファイル名</h3>
    <div class="input-group">
      <input type="text" name="dbname" id="dbname" class="form-control" value="<?php echo htmlspecialchars(urldecode($config_ini["dbname"]), ENT_QUOTES); ?>" >
      <button type="button" class="btn btn-outline-secondary" id="dbname_gen">日付ファイル名を生成</button>
    </div>
    <div class="form-text">「request_YYYYMMDD.db」形式のファイル名を生成します。</div>
  </div>
  <script>
  (function(){
    var btn = document.getElementById('dbname_gen');
    if (!btn) return;
    btn.addEventListener('click', function(){
      var d = new Date();
      var y = d.getFullYear();
      var m = String(d.getMonth() + 1).padStart(2, '0');
      var day = String(d.getDate()).padStart(2, '0');
      document.getElementById('dbname').value = 'request_' + y + m + day + '.db';
    });
  })();
  </script>
  
  <div class="mb-3">
    <h3 for="playmode">動作モード選択</h3>
    <select name="playmode" id="playmode" class="form-control" >  
      <option value="1" <?php print selectedcheck("1",$config_ini["playmode"]); ?> >自動再生開始モード</option>
      <option value="2" <?php print selectedcheck("2",$config_ini["playmode"]); ?> >手動再生開始モード</option>
      <option value="3" <?php print selectedcheck("3",$config_ini["playmode"]); ?> >手動プレイリスト登録モード</option>
      <option value="4" <?php print selectedcheck("4",$config_ini["playmode"]); ?> >BGMモード(ジュークボックスモード)</option>
      <option value="5" <?php print selectedcheck("5",$config_ini["playmode"]); ?> >BGMモード(フルランダムモード)</option>
    </select>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <h3 id="autoplay" class="menulink" >自動再生設定 </h3>
<?php
if(array_key_exists("autoplay_exec",$config_ini) && strlen($config_ini["autoplay_exec"]) > 0) {
print '<button type="button" class="btn btn-secondary btn-lg" onclick="location.href=\'autoplayctrl.php\'" >自動実行開始、停止ページへ</button>';
}
?>


  <div class="mb-3">
    <h4> 自動再生プログラムPATH設定 
    </h4>
    <label>
    <small>
     例）xampp環境 : autoplaystart_mpc_xampp.bat, <Strike> nginx環境: autoplaystart_mpc.bat</Strike>
    </small>
    <label>
    <input type="text" name="autoplay_exec" size="100" class="form-control" 
<?php
if(array_key_exists("autoplay_exec",$config_ini)) {
print 'value="'.urldecode($config_ini["autoplay_exec"]).'"';
}
?> />
    <h4 class="radio form-label"> 自動再生制御の一般ユーザーへの公開</h4>
    <label class="form-label"> <small>プレイヤーコントローラー画面 </small></label>
    <label class="checkbox-inline">
      <input type="radio" name="autoplay_show" value="1" 
<?php 
if(array_key_exists("autoplay_show",$config_ini)) {
  print ($config_ini["autoplay_show"]==1)?'checked':' ' ;
}
?>
 />
      有効 
    </label>
    <label class="checkbox-inline">
      <input type="radio" name="autoplay_show" value="2" 
<?php 
if(array_key_exists("autoplay_show",$config_ini)) {
print ($config_ini["autoplay_show"]!=1)?'checked':' ' ;
}else{
print 'checked';
}
?>
 /> 
      無効
    </label>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
<!---- トップ画面メッセージの設定 ----->
  <div class="mb-3">
    <h3 id="topmessage" class="menulink" >
    トップ画面メッセージの設定 
    
    </h3>

    <div class="mb-3">
    <h4 for=> 予約一覧（トップ）画面表示メッセージ  </h4>
    <label for=> <small> HTML記述OK、「#yukarihost#」はホスト名に置換 </small> </label>
     <textarea name="noticeof_listpage" class="form-control" id="noticeof_listpage" >
<?php
if(array_key_exists("noticeof_listpage",$config_ini)) {
    print urldecode($config_ini["noticeof_listpage"]);
}else {
    print '';
}
?></textarea>
    </div>  
    <div class="mb-3">
    <h4 for="noticeof_searchpage" > 検索画面表示メッセージ  </h4>
    <label for="noticeof_searchpage" >  <small> HTML記述OK 「#yukarihost#」はホスト名に置換 </small> </label>
     <textarea name="noticeof_searchpage" class="form-control" id="noticeof_searchpage" >
<?php
if(array_key_exists("noticeof_searchpage",$config_ini)) {
    print urldecode($config_ini["noticeof_searchpage"]);
}else {
    print '';
}
?></textarea>
    </div>  
  </div>  

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <!---- トップ画面即時リロードの設定 ----->
  <div class="mb-3">
  <?php
      $requestlistactivereload=true;
      if(array_key_exists("requestlistactivereload",$config_ini)){
          if($config_ini["requestlistactivereload"]!=1 ){
             $requestlistactivereload=false;
          }
      }
  ?>
    <h3 id="requestlist" class="radio form-label menulink"> リクエスト一覧画面設定  </h3>
    <h4 class="radio form-label"> リクエスト一覧即時リロード  </h4>
    <label class="form-label">  <br /><small>リクエスト一覧がリスト更新時のみに即時リロードされます。定期的なリロードより通信量が削減できます。有効にすると定期的なリロードは無効になり、下の設定は無視されます。</small> </label>
    <label class="radio-inline">
      <input type="radio" name="requestlistactivereload" value="1" <?php print ($requestlistactivereload)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="requestlistactivereload" value="2" <?php print (!$requestlistactivereload)?'checked':' ' ?> /> 使用しない
    </label>
  </div>


<!---- トップ画面リクエストリストリロード時間 ----->
  <div class="mb-3">
    <h4 for="reloadtime"> リクエスト一覧リロード時間  </h4>
    <label for="reloadtime">  <small> 0 でリロード無効。数値を大きくするとオンライン接続時の通信量を減らせます </small> </label>
    </label>
    <input type="text" name="reloadtime" size="100" class="form-control"
<?php
if(array_key_exists("reloadtime",$config_ini)) {
print ' value="'.urldecode($config_ini["reloadtime"]).'"';
}else {
print ' value="20" '; 
}

?>
/>
  </div>
<!---- トップ画面リクエスト一覧表示件数 ----->
  <div class="mb-3">
    <h4 for="requestlist_num"> リクエスト一覧表示件数  </h4>
    <label for="requestlist_num">  <small> 0 全件表示。数値を小さくするとオンライン接続時の通信量を減らせます </small> </label>
    <input type="text" name="requestlist_num" size="100" class="form-control"
<?php
if(array_key_exists("requestlist_num",$config_ini)) {
print ' value="'.urldecode($config_ini["requestlist_num"]).'"';
}else {
print ' value="10" '; 
}

?>
/>
  </div>

<!---- 公開用シンプルリクエスト一覧表示 ----->
  <?php
      $usesimplelist=true;
      if(array_key_exists("usesimplelist",$config_ini)){
          if($config_ini["usesimplelist"]!=1 ){
             $usesimplelist=false;
          }
      }
  ?>
  <div class="mb-3">
    <h4 class="radio form-label"> 公開用シンプルリクエスト一覧表示 <br /><small></small> </h4>
    <label class="radio-inline">
      <input type="radio" name="usesimplelist" value="1" <?php print ($usesimplelist)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usesimplelist" value="2" <?php print (!$usesimplelist)?'checked':' ' ?> /> 使用しない
    </label>
  <label>
  <a href="simplelist.php" > 公開用シンプルリクエストへのリンク </a>
  </label>
  </div>

<!---- UI v2 ----->
  <?php
      $usev2ui = (isset($config_ini["usenewrequestlist"]) && $config_ini["usenewrequestlist"] == 1)
              || (isset($config_ini["usenewsearchui"])    && $config_ini["usenewsearchui"]    == 1);
  ?>
  <div class="mb-3">
    <h4 class="radio form-label"> UI v2 </h4>
    <label class="form-label"><small>リクエスト一覧・検索画面をまとめてv2デザイン（モバイル対応）に切り替えます。</small></label>
    <label class="radio-inline">
      <input type="radio" name="usev2ui" value="1" <?php print $usev2ui ? 'checked' : '' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usev2ui" value="0" <?php print !$usev2ui ? 'checked' : '' ?> /> 使用しない
    </label>
    <label>
      <a href="requestlist_swipe.php"> リクエスト一覧UI v2 へのリンク </a>
      　<a href="search_bs5.php"> 検索画面UI v2 へのリンク </a>
    </label>
  </div>

<!---- シークレット予約の表示テキスト ----->
  <div class="mb-3">
    <h4 class="radio form-label"> シークレット予約の表示テキスト </h4>
    <label class="form-label"><small>未再生のシークレット予約の曲名の代わりに表示するテキストです。</small></label>
    <input type="text" name="secret_display_text" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)')), ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="ヒ・ミ・ツ♪(シークレット予約)" />
  </div>

<!---- ページ背景色設定 ----->
  <?php
      $bgcolor='#F8ECE0';
      if(array_key_exists("bgcolor",$config_ini)){
             $bgcolor=urldecode($config_ini["bgcolor"]);
      }
  ?>
</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3">
    <h3 id="bgcolor_t" class="radio form-label menulink"> ページ背景色  </h3>
    <input type="color" name="bgcolor" id="bgcolor" list="colors" value="<?php print $bgcolor ?>" />
		<datalist id="colors">
			<option value="#F8ECE0"></option>
			<option value="#b7dbff"></option>
			<option value="#ffddee"></option>
			<option value="#ceffce"></option>
		</datalist>
  </div>
  <script>
  (function(){
      var bgEl = document.getElementById('bgcolor');
      function applyBgColor(){
          // 実際のページ背景は body::before の var(--bg-page) で描画されるため
          // body 直下の background-color ではなく CSS 変数を更新する。
          document.documentElement.style.setProperty('--bg-page', bgEl.value);
          document.body.style.backgroundColor = bgEl.value;
      }
      bgEl.addEventListener('input', applyBgColor);
      bgEl.addEventListener('change', applyBgColor);
  })();
  </script>

<!---- 背景画像 + 透過度設定 ----->
  <?php
      $bgimage_path = '';
      if (array_key_exists("bgimage", $config_ini)) {
          $bgimage_path = urldecode($config_ini["bgimage"]);
      }
      $bgimage_mobile_path = '';
      if (array_key_exists("bgimage_mobile", $config_ini)) {
          $bgimage_mobile_path = urldecode($config_ini["bgimage_mobile"]);
      }
      $bg_card_opacity = 100;
      if (array_key_exists("bg_card_opacity", $config_ini)) {
          $bg_card_opacity = (int)$config_ini["bg_card_opacity"];
      }
      $bg_overlay_opacity = 100;
      if (array_key_exists("bg_overlay_opacity", $config_ini)) {
          $bg_overlay_opacity = (int)$config_ini["bg_overlay_opacity"];
      }
  ?>
</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3">
    <h3 id="bgimage_t" class="radio form-label menulink"> 背景画像 </h3>
    <label><small>画面全体の背景に画像を表示します。PC(横長)とスマホ(縦長)で別々の画像を登録でき、アップロード時に切り抜き範囲を調整できます。スマホ用が未設定のときはPC用画像が使われます。</small></label>
    <?php if (!empty($bgimage_upload_msg)) { ?>
      <div class="alert alert-info" style="margin-top:6px;"><?php echo htmlspecialchars($bgimage_upload_msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>

    <div class="row">
      <?php
        // PC用・スマホ用の2ブロックを共通テンプレートで描画
        $bg_blocks = [
            ['target' => 'pc',     'aspect' => 16/9, 'title' => 'PC用背景画像 (横長)',  'path' => $bgimage_path,        'field' => 'bgimage'],
            ['target' => 'mobile', 'aspect' => 9/16, 'title' => 'スマホ用背景画像 (縦長)', 'path' => $bgimage_mobile_path, 'field' => 'bgimage_mobile'],
        ];
        foreach ($bg_blocks as $blk):
          $cur = htmlspecialchars($blk['path'], ENT_QUOTES, 'UTF-8');
      ?>
      <div class="col-md-6 mb-3 bgimg-block" data-target="<?php echo $blk['target']; ?>" data-aspect="<?php echo round($blk['aspect'], 4); ?>">
        <h4 style="font-size:1rem;font-weight:600;"><?php echo htmlspecialchars($blk['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
        <?php if (!empty($blk['path'])) { ?>
          <div class="bgimg-current" style="margin:4px 0;">
            <small>現在: <code><?php echo $cur; ?></code></small><br>
            <img src="<?php echo $cur; ?>" alt="現在の背景画像" class="bgimg-thumb">
          </div>
        <?php } else { ?>
          <div class="bgimg-current" style="margin:4px 0;"><small>未設定</small></div>
        <?php } ?>

        <div style="margin:6px 0;">
          <input type="file" class="bgimg-file form-control form-control-sm" accept="image/png,image/jpeg,image/gif,image/webp" />
        </div>

        <div class="bgimg-cropper" hidden>
          <div class="bgimg-viewport">
            <img class="bgimg-cropimg" alt="" />
          </div>
          <div style="margin:6px 0;">
            <label><small>拡大</small></label>
            <input type="range" class="bgimg-zoom" min="1" max="4" step="0.01" value="1" style="width:100%;" />
          </div>
          <button type="button" class="btn btn-primary btn-sm bgimg-ok">この範囲で切り抜く</button>
          <button type="button" class="btn btn-secondary btn-sm bgimg-cancel">キャンセル</button>
          <div><small class="text-muted">画像をドラッグして位置を調整、スライダーで拡大できます。</small></div>
        </div>

        <div class="bgimg-result" style="margin:6px 0;" hidden>
          <small class="text-success">切り抜き済み (設定反映で保存):</small><br>
          <img alt="切り抜きプレビュー" class="bgimg-thumb" />
        </div>

        <?php if (!empty($blk['path'])) { ?>
          <div style="margin:6px 0;">
            <label class="checkbox-inline">
              <input type="checkbox" class="bgimg-delete" name="<?php echo $blk['field']; ?>_delete" value="1" /> クリアする (設定反映で適用)
            </label>
          </div>
        <?php } ?>

        <input type="hidden" class="bgimg-data" name="<?php echo $blk['field']; ?>_data" value="" />
        <input type="hidden" name="<?php echo $blk['field']; ?>" value="<?php echo $cur; ?>" />
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mb-3" style="margin-top:10px;">
      <label for="bg_card_opacity"><small>カード透過度: <span id="bg_card_opacity_val"><?php echo $bg_card_opacity; ?></span>%</small></label>
      <input type="range" name="bg_card_opacity" id="bg_card_opacity" min="0" max="100" step="1" value="<?php echo $bg_card_opacity; ?>" />
      <small>低くするほどカード(各パネル)が透けて背景画像が見えます。</small>
    </div>
    <div class="mb-3">
      <label for="bg_overlay_opacity"><small>背景オーバーレイ透過度: <span id="bg_overlay_opacity_val"><?php echo $bg_overlay_opacity; ?></span>%</small></label>
      <input type="range" name="bg_overlay_opacity" id="bg_overlay_opacity" min="0" max="100" step="1" value="<?php echo $bg_overlay_opacity; ?>" />
      <small>背景画像の上にページ背景色をかぶせる強さ。100%で背景色のみ(画像非表示)、0%で画像がそのまま見えます。</small>
    </div>
  </div>
  <script>
  (function(){
      function hexToRgb(h){
          h = (h||'').replace('#','');
          if(h.length===3){ h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2]; }
          if(!/^[0-9a-fA-F]{6}$/.test(h)) return '248, 236, 224';
          return parseInt(h.substr(0,2),16)+', '+parseInt(h.substr(2,2),16)+', '+parseInt(h.substr(4,2),16);
      }
      function updateBgPreview(){
          var co = parseInt(document.getElementById('bg_card_opacity').value,10)/100;
          var oo = parseInt(document.getElementById('bg_overlay_opacity').value,10)/100;
          document.documentElement.style.setProperty('--bg-card-alpha', co);
          document.documentElement.style.setProperty('--bg-overlay-alpha', oo);
          document.documentElement.style.setProperty('--bg-page-rgb', hexToRgb(document.getElementById('bgcolor').value));
          document.getElementById('bg_card_opacity_val').textContent = document.getElementById('bg_card_opacity').value;
          document.getElementById('bg_overlay_opacity_val').textContent = document.getElementById('bg_overlay_opacity').value;
      }
      ['bg_card_opacity','bg_overlay_opacity','bgcolor'].forEach(function(id){
          document.getElementById(id).addEventListener('input', updateBgPreview);
          document.getElementById(id).addEventListener('change', updateBgPreview);
      });
      updateBgPreview();
  })();
  </script>

  <script>
  // 背景画像クロッパー: 固定アスペクト比のビューポート内で画像をパン/ズームし、
  // 確定時に canvas で切り抜いて data URL を hidden フィールドに格納する。
  (function(){
      function initCropper(block){
          var aspect    = parseFloat(block.dataset.aspect);
          var fileInput = block.querySelector('.bgimg-file');
          var cropWrap  = block.querySelector('.bgimg-cropper');
          var viewport  = block.querySelector('.bgimg-viewport');
          var imgEl     = block.querySelector('.bgimg-cropimg');
          var zoom      = block.querySelector('.bgimg-zoom');
          var btnOk     = block.querySelector('.bgimg-ok');
          var btnCancel = block.querySelector('.bgimg-cancel');
          var dataInput = block.querySelector('.bgimg-data');
          var resultBox = block.querySelector('.bgimg-result');
          var resultImg = resultBox ? resultBox.querySelector('img') : null;
          var deleteChk = block.querySelector('.bgimg-delete');

          var natW = 0, natH = 0, minScale = 1, scale = 1, offX = 0, offY = 0;
          var vw = 0, vh = 0, dragging = false, lastX = 0, lastY = 0, objUrl = null;

          function clamp(){
              var dispW = natW * scale, dispH = natH * scale;
              if (offX > 0) offX = 0;
              if (offX < vw - dispW) offX = vw - dispW;
              if (offY > 0) offY = 0;
              if (offY < vh - dispH) offY = vh - dispH;
          }
          function render(){
              imgEl.style.width  = (natW * scale) + 'px';
              imgEl.style.height = (natH * scale) + 'px';
              imgEl.style.left   = offX + 'px';
              imgEl.style.top    = offY + 'px';
          }
          fileInput.addEventListener('change', function(){
              var f = fileInput.files && fileInput.files[0];
              if (!f) return;
              if (!/^image\//.test(f.type)) { alert('画像ファイルを選択してください'); return; }
              if (objUrl) URL.revokeObjectURL(objUrl);
              objUrl = URL.createObjectURL(f);
              var im = new Image();
              im.onload = function(){
                  natW = im.naturalWidth; natH = im.naturalHeight;
                  imgEl.src = objUrl;
                  cropWrap.hidden = false; // 計測前に表示しないと幅が 0 になる
                  vw = viewport.clientWidth; vh = viewport.clientHeight;
                  minScale = Math.max(vw / natW, vh / natH);
                  scale = minScale;
                  zoom.value = 1;
                  offX = (vw - natW * scale) / 2;
                  offY = (vh - natH * scale) / 2;
                  clamp(); render();
              };
              im.src = objUrl;
          });
          zoom.addEventListener('input', function(){
              var cx = (vw / 2 - offX) / scale, cy = (vh / 2 - offY) / scale;
              scale = minScale * parseFloat(zoom.value);
              offX = vw / 2 - cx * scale;
              offY = vh / 2 - cy * scale;
              clamp(); render();
          });
          viewport.addEventListener('pointerdown', function(e){
              dragging = true; lastX = e.clientX; lastY = e.clientY;
              viewport.setPointerCapture(e.pointerId);
          });
          viewport.addEventListener('pointermove', function(e){
              if (!dragging) return;
              offX += e.clientX - lastX; offY += e.clientY - lastY;
              lastX = e.clientX; lastY = e.clientY;
              clamp(); render();
          });
          function endDrag(){ dragging = false; }
          viewport.addEventListener('pointerup', endDrag);
          viewport.addEventListener('pointercancel', endDrag);

          btnCancel.addEventListener('click', function(){
              cropWrap.hidden = true;
              fileInput.value = '';
              imgEl.removeAttribute('src');
          });
          btnOk.addEventListener('click', function(){
              var sx = -offX / scale, sy = -offY / scale, sw = vw / scale, sh = vh / scale;
              var outW, outH;
              if (aspect >= 1) { outW = Math.min(1920, Math.round(sw)); outH = Math.round(outW / aspect); }
              else             { outH = Math.min(1920, Math.round(sh)); outW = Math.round(outH * aspect); }
              var c = document.createElement('canvas');
              c.width = outW; c.height = outH;
              c.getContext('2d').drawImage(imgEl, sx, sy, sw, sh, 0, 0, outW, outH);
              var data = c.toDataURL('image/jpeg', 0.85);
              dataInput.value = data;
              if (resultImg) resultImg.src = data;
              if (resultBox) resultBox.hidden = false;
              cropWrap.hidden = true;
              if (deleteChk) deleteChk.checked = false; // 新規画像を設定したのでクリアは解除
          });
      }
      document.querySelectorAll('.bgimg-block').forEach(initCropper);
  })();
  </script>

<!---- twitter投稿リンク ----->
  <div class="mb-3">
  <?php
      $useposttwitter = configbool("useposttwitter", true);
  ?>
    <label class="form-label"> twitter投稿リンク </label>
    <label class="radio-inline">
      <input type="radio" name="useposttwitter" value="1" <?php print ($useposttwitter)?'checked':' ' ?> /> 表示する
    </label>
    <label class="radio-inline">
      <input type="radio" name="useposttwitter" value="2" <?php print (!$useposttwitter)?'checked':' ' ?> /> 表示しない
    </label>
  </div>



</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3">
    <h3 id="movieplayer" class="menulink" > 動画プレーヤー設定 </h3>
    <h4 for="playerpath_select">MediaPlayerClassic PATH設定</h4>
    <select  class="form-control" name="playerpath_select" id="playerpath_select" >  
      <option <?php print selectedcheck("C:\Program Files\MPC-BE\mpc-be64.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files\MPC-BE\mpc-be64.exe" >C:\Program Files\MPC-BE\mpc-be64.exe (現在のMPC-BE:64bit版のデフォルト)</option>
      <option <?php print selectedcheck("C:\Program Files (x86)\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files (x86)\MPC-BE\mpc-be.exe" >C:\Program Files (x86)\MPC-BE\mpc-be.exe (MPC-BE:64bitOSで32bit版)</option>
      <option <?php print selectedcheck("C:\Program Files\MPC-BE\mpc-be.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files\MPC-BE\mpc-be.exe" >C:\Program Files\MPC-BE\mpc-be.exe (32bitOSでMPC-BE32bit版)</option>
      <option <?php print selectedcheck("C:\Program Files\MPC-BE x64\mpc-be64.exe",urldecode($config_ini["playerpath_select"])); ?> value="C:\Program Files\MPC-BE x64\mpc-be64.exe" >C:\Program Files\MPC-BE x64\mpc-be64.exe (64bitOSでMPC-BE64bit版)</option>
      <option <?php print selectedcheck("その他PATH指定",urldecode($config_ini["playerpath_select"])); ?> value="その他PATH指定" >その他PATH指定</option>
    </select>
    <label > (任意のPATH選択) </label>
    <input class="form-control" type="text" name="playerpath_any" size="100" class="playerpath_any" 
<?php
if( urldecode($config_ini["playerpath_select"]) == 'その他PATH指定' )
{
    print 'value="'.urldecode($config_ini["playerpath_any"]).'" ';
}
?>
/>
  </div>
  <div class="mb-3">
    <h4 class="radio form-label"> MPCのフルスクリーンボタン </h4>
    <label class="radio-inline">
      <input type="radio" name="moviefullscreen" value="1" <?php print ($config_ini["moviefullscreen"]==1)?'checked':' ' ?> /> 有効
    </label>
    <label class="radio-inline">
      <input type="radio" name="moviefullscreen" value="2" <?php print ($config_ini["moviefullscreen"]!=1)?'checked':' ' ?> /> 無効
    </label>
  </div>  

<!---- 音量戻し ----->
  <?php
      $startvolume50=true;
      if(array_key_exists("startvolume50",$config_ini)){
          if($config_ini["startvolume50"]!=1 ){
             $startvolume50=false;
          }
      }
  ?>
  <div class="mb-3">
<!--★削除★
    <h4 class="radio form-label"> <span data-bs-toggle="tooltip" data-bs-placement="top" title=" MPCの動画再生開始時に音量を５０％に戻す" >
        MPCの再生開始時に音量を５０％に戻す <small></small> </span>
★-->
<!--★追加★-->
    <h4 class="radio form-label"> <span data-bs-toggle="tooltip" data-bs-placement="top" title="MPCの動画再生開始時に音量を戻す" >
        MPCの再生開始時に音量を戻す <small></small> </span>
<!--★追加★-->
    </h4>
        
    <label class="radio-inline">
      <input type="radio" name="startvolume50" value="1" <?php print ($startvolume50)?'checked':' ' ?> /> 有効
    </label>
    <label class="radio-inline">
      <input type="radio" name="startvolume50" value="2" <?php print (!$startvolume50)?'checked':' ' ?> /> 無効
    </label>
<!--★追加★-->
    <h5> <span data-bs-toggle="tooltip" data-bs-placement="top">戻す音量 (％) </span></h5>
    <input type="number" name="startvolume" min="0" max="100" class="startvolume" 
<?php
if(array_key_exists("startvolume",$config_ini)) {
print 'value="'.urldecode($config_ini["startvolume"]).'"';
}
?>
/>
<!--★追加★-->
  </div>


<!---- キーチェンジ機能 ----->
  <?php
      $usekeychange=false;
      if(array_key_exists("usekeychange",$config_ini)){
          if($config_ini["usekeychange"]==1 ){
             $usekeychange=true;
          }
      }
  ?>
  <div class="mb-3">
    <h4 class="radio form-label"> <span data-bs-toggle="tooltip" data-bs-placement="top" title="Player画面にキー変更ボタンを表示します" >
        MPCのキーチェンジ機能 </span> </h4>
    <label class="form-label">
        <small>『要<a href="http://shinta.coresv.com/soft/EasyKeyChanger_JPN.html" > 簡易キーチェンジャー </a>のセットアップ』</small>
    </label>
        
    <label class="radio-inline">
      <input type="radio" name="usekeychange" value="1" <?php print ($usekeychange)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usekeychange" value="2" <?php print (!$usekeychange)?'checked':' ' ?> /> 使用しない
    </label>
  </div>

  <div class="mb-3">
    <h4 class="radio form-label"><span data-bs-toggle="tooltip" data-bs-placement="top" title="通常使用するプレイヤーとは別のプレイヤーを使えるようにします。予約確認画面に項目を追加。次の曲に行くには曲終了時に「曲終了」ボタンを押す必要があります" > 別プレーヤー指定 </span> </h4>
    <label class="radio-inline" data-bs-toggle="tooltip" data-bs-placement="top" title="予約確認画面に項目を追加するかどうか">
      <input type="radio" name="useotherplayer" value="1" <?php 
      if(array_key_exists("useotherplayer",$config_ini)){
          print ($config_ini["useotherplayer"]==1)?'checked':' ';
      }
      ?> /> 使用する
    </label>
    <label class="radio-inline"> <input type="radio" name="useotherplayer" value="2" <?php 
    if(array_key_exists("useotherplayer",$config_ini)){
        print ($config_ini["useotherplayer"]!=1)?'checked':' ';
    }else{
        print "checked";
    }
    ?> /> 使用しない </label></br>
    <h4 > <span data-bs-toggle="tooltip" data-bs-placement="top" title="リクエスト確認画面でのこの項目に対する説明文">リクエスト確認画面での説明文 </span></h4>
    <input type="text" name="otherplayer_disc" class="form-control" 
<?php
if(array_key_exists("otherplayer_disc",$config_ini)) {
print 'value="'.urldecode($config_ini["otherplayer_disc"]).'"';
}
?>
/>
    <h4 > <span data-bs-toggle="tooltip" data-bs-placement="top" title="起動する別プログラム コマンドプロンプトから「 ＜コマンド名＞ ＜ファイル名＞ 」で起動させる">別プレーヤーのPATH（空白で手動実行) </span></h4>
    <input type="text" name="otherplayer_path" class="form-control" 
<?php
if(array_key_exists("otherplayer_path",$config_ini)) {
print 'value="'.urldecode($config_ini["otherplayer_path"]).'"';
}
?>
/>

  </div>
  <div class="mb-3">
    <h4 for="foobarpath"> foobar2000 PATH設定　</h4>
    <label > 任意のPATH選択  </label>
    <input type="text" name="foobarpath" class="form-control" id="foobarpath" value="<?php echo urldecode($config_ini["foobarpath"]); ?>" />
  </div>


  <?php
      $usehaishin=false;
      if(array_key_exists("usehaishin",$config_ini)){
          if($config_ini["usehaishin"]==1 ){
             $usehaishin=true;
          }
      }else {
          $usehaishin=true;
      }
  ?>


    <div class="mb-3">
    <h4 class="radio form-label">カラオケ配信リクエストを受け付ける </h4>
    <label class="radio-inline">
      <input type="radio" name="usehaishin" value="1" <?php print ($usehaishin)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usehaishin" value="2" <?php print (!$usehaishin)?'checked':' ' ?> /> 使用しない
    </label>
  </div>
  <div class="mb-3">
    <h4 class="radio form-label">配信曲にビデオキャプチャデバイスを使用 </h4>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="2" <?php print ($config_ini["usevideocapture"]!=1 && $config_ini["usevideocapture"]!=3)?'checked':' ' ?> /> 使用しない
    </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="1" <?php print ($config_ini["usevideocapture"]==1)?'checked':' ' ?> /> MPC-BEを使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usevideocapture" value="3" <?php print ($config_ini["usevideocapture"]==3)?'checked':' ' ?> /> 別のアプリを使用する
    </label>
  </div>
  <div class="mb-3">
    <h4 >
      配信表示アプリ <small>(「別アプリ使用」の設定の時のみ有効)</small>
    </h4>
    <input type="text" name="captureapli_path" size="100" class="form-control"
<?php
if(array_key_exists("captureapli_path",$config_ini)) {
print 'value="'.urldecode($config_ini["captureapli_path"]).'"';
}
?>
/>
  </div>
  <div class="mb-3">
    <h4 >
      配信開始時実行コマンド 
    </h4>
    <input type="text" name="DeliveryCMD" size="100" class="form-control"
<?php
if(array_key_exists("DeliveryCMD",$config_ini)) {
print 'value="'.urldecode($config_ini["DeliveryCMD"]).'"';
}
?>
/>
  </div>
  <div class="mb-3">
    <h4 >
      配信終了時実行コマンド 
    </h4>
    <input type="text" name="DeliveryCMDEND" size="100" class="form-control"
<?php
if(array_key_exists("DeliveryCMDEND",$config_ini)) {
print 'value="'.urldecode($config_ini["DeliveryCMDEND"]).'"';
}
?>
/>
  </div>

<!---- キャプチャー時Direct3Dフルスクリーン切り替え ----->
  <?php
      $toggled3dfullscreen=false;
      if(array_key_exists("toggled3dfullscreen",$config_ini)){
          if($config_ini["toggled3dfullscreen"] == 1 ){
             $toggled3dfullscreen=true;
          }
      }
  ?>
  <div class="mb-3">
    <h4 class="radio form-label"> <span data-bs-toggle="tooltip" data-bs-placement="top" title=" 通常再生時Direct3Dフルスクリーンを有効にしている際に有効にする" >
        ビデオキャプチャデバイスを使用時にDirect3Dフルスクリーンの切り替え <small></small> </span>
    </h4>
        
    <label class="radio-inline">
      <input type="radio" name="toggled3dfullscreen" value="1" <?php print ($toggled3dfullscreen)?'checked':' ' ?> /> 有効
    </label>
    <label class="radio-inline">
      <input type="radio" name="toggled3dfullscreen" value="2" <?php print (!$toggled3dfullscreen)?'checked':' ' ?> /> 無効
    </label>
  </div>

  <div class="mb-3">
    <h4 class="radio form-label">BGVモード </h4>
    <label class="radio-inline"> <input type="radio" name="usebgv" value="1" <?php print ($config_ini["usebgv"]==1)?'checked':' ' ?> /> 使用する </label>
    <label class="radio-inline"> <input type="radio" name="usebgv" value="2" <?php print ($config_ini["usebgv"]!=1)?'checked':' ' ?> /> 使用しない </label>

    <div class="mb-3">
    <h4 class="radio form-label"> BGVフォルダ  </h4>
    <label class="form-label"> <small> 空でBGV検索画面無効 </small> </label>
    <input type="text" name="BGVfolder" class="form-control"
<?php
if(array_key_exists("BGVfolder",$config_ini)) {
    print 'value="'.urldecode($config_ini["BGVfolder"]).'"';
}else {
    print 'value=""';
}
?>
/>
    </div>
    <div class="mb-3">
    <h4 >
      BGV開始時実行コマンド 
    </h4>
    <input type="text" name="BGVCMDSTART" size="100" class="form-control"
<?php
if(array_key_exists("BGVCMDSTART",$config_ini)) {
print 'value="'.urldecode($config_ini["BGVCMDSTART"]).'"';
}
?>
/>
    </div>
    <div class="mb-3">
    <h4 >
      BGV終了時実行コマンド 
    </h4>
    <input type="text" name="BGVCMDEND" size="100" class="form-control"
<?php
if(array_key_exists("BGVCMDEND",$config_ini)) {
print 'value="'.urldecode($config_ini["BGVCMDEND"]).'"';
}
?>
/>
    </div>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
<h3 id=searchscreen class="menulink" > 検索画面設定 </h3>
  <div class="mb-3">
    <h4 for="comment"> リクエスト画面、コメント欄の説明書き </h4>
    <textarea class="form-control" name="requestcomment" id="comment" rows="4" wrap="soft" style="width:100%" >
<?php print htmlspecialchars(urldecode($config_ini["requestcomment"])); ?>
    </textarea>
  </div>

  <div class="mb-3">
    <h4 class="radio form-label">見つからなかった曲リストの使用 </h4>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="1" <?php print ($config_ini["usenfrequset"]==1)?'checked':' ' ?> /> 使用する </label>
    <label class="radio-inline"> <input type="radio" name="usenfrequset" value="2" <?php print ($config_ini["usenfrequset"]!=1)?'checked':' ' ?> /> 使用しない </label>
  </div>
  <div class="mb-3">
    <h4 for="max_filesize"> 検索結果に表示する最大ファイルサイズ(MB) </h4>
    <label for="max_filesize"> <small> 0 で無制限 </small> 
    </label>
    <input type="text" name="max_filesize" size="100" class="form-control"
<?php
if(array_key_exists("max_filesize",$config_ini)) {
print 'value="'.urldecode($config_ini["max_filesize"]).'"';
}
?>
/>
  </div>
<?php

if(!array_key_exists('searchitem', $config_ini )){
    $config_ini['searchitem'] = array("searchmessage", "listerDB_file", "listerDB", "filesearch_e");
}

if(!array_key_exists('searchitem_o', $config_ini )){
    // 表示順: searchmessage=1位, listerDB_file=2位, listerDB=3位, filesearch_e=4位, anisoninfo_e=5位, bandit_e=6位
    $config_ini['searchitem_o'] = array("2", "3", "4", "5", "6", "1");
}

// 既存設定へのマイグレーション: searchmessageが未追加の場合、先頭に有効状態で追加
if(!isset($config_ini['searchitem_o'][5])) {
    $config_ini['searchitem_o'][5] = 0;
    if(!in_array('searchmessage', $config_ini['searchitem'])) {
        $config_ini['searchitem'][] = 'searchmessage';
    }
}
if(!isset($config_ini['searchitem_o'][6])) {
    // 詳細検索フォームは既存ユーザーではデフォルト非表示（順序0）
    $config_ini['searchitem_o'][6] = 0;
}

$searchitem_defs = array(
    array('id' => 'listerDB_file',   'label' => 'キーワード検索（りすたー）'),
    array('id' => 'listerDB',        'label' => 'りすたーDB検索'),
    array('id' => 'filesearch_e',    'label' => 'ファイル名検索（Everything）'),
    array('id' => 'anisoninfo_e',    'label' => '外部検索（anison.info）（Everything）'),
    array('id' => 'bandit_e',        'label' => '外部検索（banditの隠れ家）（Everything）'),
    array('id' => 'searchmessage',   'label' => '検索画面表示メッセージ'),
    array('id' => 'listerDB_detail', 'label' => 'キーワード詳細検索（りすたー）'),
);

$si_order_map = array();
foreach ($searchitem_defs as $idx => $def) {
    $si_order_map[$idx] = isset($config_ini['searchitem_o'][$idx]) ? (int)$config_ini['searchitem_o'][$idx] : ($idx + 1);
}
asort($si_order_map);
$si_sorted_indices = array_keys($si_order_map);

?>

  <div class="mb-3">
     <h4 class=""> 検索画面に表示する項目 </h4>
     <small>ドラッグで表示順を変更できます</small>

  <div id="searchitem-sortable" style="max-width:600px; margin-top:8px;">
<?php foreach ($si_sorted_indices as $si_sorted_pos => $idx) {
    $def = $searchitem_defs[$idx];
    $checked = checkbox_check($config_ini['searchitem'], $def['id']) ? 'checked' : '';
?>
    <div class="searchitem-row" data-index="<?php echo $idx; ?>" style="display:flex; align-items:center; padding:6px 10px; margin-bottom:4px; border:1px solid #ddd; background:#f9f9f9; border-radius:3px;">
      <span class="searchitem-drag-handle" style="cursor:grab; color:#aaa; font-size:20px; padding:0 10px 0 0; line-height:1; user-select:none; touch-action:none;">&#8942;</span>
      <input type="checkbox" name="searchitem[]" value="<?php echo $def['id']; ?>" <?php echo $checked; ?> style="margin-right:8px;">
      <span><?php echo $def['label']; ?></span>
      <input type="hidden" name="searchitem_o[<?php echo $idx; ?>]" value="<?php echo $si_sorted_pos + 1; ?>" class="searchitem-order-input">
    </div>
<?php } ?>
  </div>
  </div>

  <div class="mb-3">
    <h4  > りすたーDBファイルパス  </h4>
    <?php 
        $listerDBPATH = 'list\List.sqlite3';
        if(array_key_exists("listerDBPATH",$config_ini)) {
           $listerDBPATH = urldecode($config_ini["listerDBPATH"]);
        }
    ?>
    <input type="text" name="listerDBPATH" size="100" class="form-control" value="<?php echo $listerDBPATH; ?>" />
  </div>

  <div class="mb-3">
    <?php $online_preview = configbool("online_preview", false); ?>
    <h4 class="radio form-label"> オンラインからの動画プレビュー </h4>
    <label class="form-label"><small>インターネット経由でアクセスした場合も動画プレビューを使用できるようにします</small></label>
    <label class="radio-inline">
      <input type="radio" name="online_preview" value="1" <?php print ($online_preview) ? 'checked' : ' '; ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="online_preview" value="2" <?php print (!$online_preview) ? 'checked' : ' '; ?> /> 使用しない
    </label>
  </div>

  <div class="mb-3">
    <h4 class="radio form-label"> 検索ログの保存 </h4>
    <label class="radio-inline">
      <input type="radio" name="historylog" value="1" <?php print ($config_ini["historylog"]==1)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="historylog" value="2" <?php print ($config_ini["historylog"]!=1)?'checked':' ' ?> /> 使用しない
    </label>
  </div>

  <div class="mb-3">
    <h4 title="検索結果がこの数を超えた場合、作品名や歌手名と一緒に検索結果を表示する" > anison.info 詳細検索表示件数 <small> 0で1件でもあれば表示</small> </h4>
    <label title="検索結果がこの数を超えた場合、作品名や歌手名と一緒に検索結果を表示する" >  <small> 0で1件でもあれば表示</small> </label>
    <?php 
        $anisoninfomanynumber = 15;
        if(array_key_exists("anisoninfomanynumber",$config_ini)) {
           $anisoninfomanynumber = $config_ini["anisoninfomanynumber"];
        }
    ?>
    <input type="text" name="anisoninfomanynumber" size="100" class="form-control" value="<?php echo $anisoninfomanynumber; ?>" />
  </div>
  <!---- 小休止の設定 ----->
  <div class="mb-3">
  <?php
      $useuserpause = configbool("useuserpause", false);
  ?>
    <h3 class="radio form-label"> 小休止リクエストを管理者以外に許可 </h3>
    <label class="form-label"> <small>一般ユーザーにも小休止リクエストができるようにします</small> </label>
    <label class="radio-inline">
      <input type="radio" name="useuserpause" value="1" <?php print ($useuserpause)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="useuserpause" value="2" <?php print (!$useuserpause)?'checked':' ' ?> /> 使用しない
    </label>
  </div>
  <div class="mb-3">
    <h4>小休止リクエスト時のファイル名初期値</h4>
    <input type="text" name="pause_default_filename" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['pause_default_filename']), ENT_QUOTES, 'UTF-8'); ?>" />
  </div>
  <div class="mb-3">
    <h4>小休止リクエスト時のコメント初期値</h4>
    <input type="text" name="pause_default_comment" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['pause_default_comment']), ENT_QUOTES, 'UTF-8'); ?>" />
  </div>


</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3 ">
    <h3 id="useinternet" class="radio form-label menulink"> インターネット接続  </h3>
    <label class="form-label"> <small>(使用しないにするとインターネット接続が前提の機能を無効にします)</small> </label>
    <label class="radio-inline">
      <input type="radio" name="connectinternet" value="1" <?php print ($config_ini["connectinternet"]==1)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="connectinternet" value="2" <?php print ($config_ini["connectinternet"]!=1)?'checked':' ' ?> /> 使用しない
    </label>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3">
    <h3 id="commentserver" class="menulink">
    コメントサーバー設定
    </h3>
    <label >
    <small> ローカルサーバー http://localhost/cms/r.php ,リモートサーバー http://xsd.php.xdomain.jp/r2.php </small>
    </label>

    <select  class="form-control" name="commenturl_base" >  
      <option value="notset" > 使用しない </option>
      <option <?php print selectedcheck("http://localhost/cms/r.php",urldecode($config_ini["commenturl_base"])); ?> value="http://localhost/cms/r.php" > http://localhost/cms/r.php </option>
      <option <?php print selectedcheck("http://xsd.php.xdomain.jp/r2.php",urldecode($config_ini["commenturl_base"])); ?> value="http://xsd.php.xdomain.jp/r2.php" > http://xsd.php.xdomain.jp/r2.php </option>
    </select>

    <h4 > ルーム名 (半角英数字8文字まで) </h4>
    <input type="text" name="commentroom" MAXLENGTH="24" class="form-control" value="<?php echo urldecode($config_ini["commentroom"]); ?>" />
  </div>

  <div class="mb-3">
    <h3 >
      ヘルプURL 
    </h3>
    <label >
      <small>(https://www.evernote.com/shard/s213/sh/c0e87185-314f-446d-ac12-fd13f25f6cb9/78f03652cc14e2ae 等, 使用しないときは空で)</small>
    </label>
    <input type="text" name="helpurl" size="100" class="form-control"
<?php
if(array_key_exists("helpurl",$config_ini)) {
print 'value="'.urldecode($config_ini["helpurl"]).'"';
}
?>
/>
  </div> 
  <div class="mb-3">
    <h3 class="radio form-label"> 名無しでのリクエスト許可 </h3>
    <label class="radio-inline">
      <input type="radio" name="nonamerequest" value="1" 
      <?php 
      if(array_key_exists("nonamerequest",$config_ini)){
          print ($config_ini["nonamerequest"]==1)?'checked':' ';
      }else {
          print 'checked';
      }
      ?> /> 許可
    </label>
    <label class="radio-inline">
      <input type="radio" name="nonamerequest" value="2" 
      <?php 
      if(array_key_exists("nonamerequest",$config_ini)){
          print ($config_ini["nonamerequest"]!=1)?'checked':' ';
      }
       
      ?> /> 不許可
    </label>
    <h3 class="radio form-label"> 名無しリクエスト時の表示名 </h3>
    <input type="text" name="nonameusername" class="form-control"
<?php
if(array_key_exists("nonameusername",$config_ini)) {
print 'value="'.urldecode($config_ini["nonameusername"]).'"';
}else {
print 'value="名無しさん"';}
?>
/>    
  </div> 


  <div class="mb-3">
    <h3 >
    ニコニコ動画ダウンロード設定 
    </h3>
	<label >
    <small>(ゆかりでの設定は不要になりました。MPC-BEにyoutube-dlの設定をしてください。<a href="https://github.com/bee7813993/KaraokeRequestorWeb/wiki/urlrequest"> https://github.com/bee7813993/KaraokeRequestorWeb/wiki/urlrequest </a> </small>
	</label >
  </div>
<!---
    <div class="mb-3">
      <h4 > ログインID(メールアドレス) </h4>
      <input type="text" name="nicoid"  class="form-control" 
<?php
if(array_key_exists("nicoid",$config_ini)) {
print 'value="'.urldecode($config_ini["nicoid"]).'"';
}
?>    
    />
      <h4 > パスワード </h4>
      <input type="password" name="nicopass"  class="form-control" 
<?php
if(array_key_exists("nicopass",$config_ini)) {
print 'value="'.urldecode($config_ini["nicopass"]).'"';
}
?>      />
    </div>
    <div class="mb-3">
    <h4 class="radio form-label"> アップ／ダウンロード先フォルダ <small> 要Everythingの検索対象</small> </h4>
    <label class="form-label"> <small> 要Everythingの検索対象</small> </label>
    <input type="text" name="downloadfolder" class="form-control"
<?php
if(array_key_exists("downloadfolder",$config_ini)) {
    print 'value="'.urldecode($config_ini["downloadfolder"]).'"';
}else {
    print 'value="'.$_SERVER["TMP"].'"';
}
?>
/>
    </div>  
  </div>  
---!>

  <div class="mb-3">
    <h3 > プレイヤー動作監視開始待ち時間(秒) </h3>
      
    <input type="text" name="waitplayercheckstart" size="100" class="form-control" value="<?php echo $config_ini["waitplayercheckstart"]; ?>" />
    <h3 > プレイヤー動作監視チェック回数(回)  </h3>
    <input type="text" name="playerchecktimes" size="100" class="form-control" value="<?php echo $config_ini["playerchecktimes"]; ?>" />
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <h3 id="otherroom" class="radio form-label menulink"> 別部屋URL設定 </h3>
  <small class="text-muted">ドラッグハンドル（⋮⋮）で行の順番を入れ替えられます</small>
  <table class="table table-striped table-bordered table-sm mt-1">
  <thead>
    <tr>
      <th style="width:32px"></th>
      <th class="col-3">部屋番号</th>
      <th class="col-7">URL</th>
      <th class="col-1 text-center">表示</th>
    </tr>
  </thead>
  <tbody id="roomurl-sortable">
<?php
  $roomcount = 0;
  foreach ( $config_ini["roomurl"] as $key => $value ){
      if( empty($key) || empty($value) ){
          continue;
      }
      $show_checked = (array_key_exists("roomurlshow", $config_ini) && array_key_exists($key, $config_ini["roomurlshow"]) && $config_ini["roomurlshow"][$key] == 1) ? ' checked' : '';
      print '  <tr class="roomurl-row">'."\n";
      print '    <td class="roomurl-drag-handle text-center align-middle" style="cursor:grab; color:#aaa; font-size:18px; user-select:none; touch-action:none;">&#8942;&#8942;</td>'."\n";
      print '    <td><input type="text" class="form-control form-control-sm" placeholder="部屋番号" name="roomno[]"';
      print ' value="'.htmlspecialchars($key).'"' ;
      print '    ></td>'."\n";
      print '    <td><input type="text" class="form-control form-control-sm" placeholder="部屋'.($roomcount+1).'のURL" name="roomurl[]"';
      print ' value="'.htmlspecialchars(urldecode($value)).'"' ;
      print '    ></td>'."\n";
      print '    <td class="text-center align-middle"><input type="checkbox" class="form-check-input" name="roomurlshow[]" value="'.$roomcount.'"'.$show_checked.'></td>'."\n";
      print '  </tr>'."\n";
      $roomcount ++;
  }
?>
  </tbody>
  <tfoot>
  <tr>
    <td></td>
    <td><input type="text" class="form-control form-control-sm" placeholder="新しい部屋番号" name="roomno[]"
    ></td>
    <td><input type="text" class="form-control form-control-sm" placeholder="新しい部屋のURL" name="roomurl[]"
    ></td>
    <td class="text-center align-middle"><input type="checkbox" class="form-check-input" name="roomurlshow[]" value="<?php echo $roomcount;?>"
    ></td>
  </tr>
  </tfoot>
  </table>

  <div class="mb-3">
    <h3 class="radio form-label"  > <span data-bs-toggle="tooltip" data-bs-placement="top" title="曲予約をしたとき今までの順番を考慮した場所に自動移動します。Offでは一番上に登録されます" >リクエスト時_順番ピッタリ移動 </span ></h3> 
    <label class="checkbox-inline">
      <input type="radio" name="request_automove" value="1" 
<?php 
if(array_key_exists("request_automove",$config_ini)) {
  print ($config_ini["request_automove"]==1)?'checked':' ' ;
}else{
  print 'checked';
}
?>
 />
      有効 
    </label>
    <label class="checkbox-inline">
      <input type="radio" name="request_automove" value="2" 
<?php 
if(array_key_exists("request_automove",$config_ini)) {
  print ($config_ini["request_automove"]!=1)?'checked':' ' ;
}
?>
 /> 
      無効
    </label>
  </div>

  <div class="mb-3">
    <h3 class="radio form-label"  > <span data-bs-toggle="tooltip" data-bs-placement="top" title="曲予約をしたとき今までの順番を考慮した場所に自動移動します。Offでは一番上に登録されます" >ピッタリ移動_小休止時リセット </span ></h3> 
    <label class="checkbox-inline">
      <input type="radio" name="request_automove_reset" value="1" 
<?php 
if(array_key_exists("request_automove_reset",$config_ini)) {
  print ($config_ini["request_automove_reset"]==1)?'checked':' ' ;
}else{
  print 'checked';
}
?>
 />
      有効 
    </label>
    <label class="checkbox-inline">
      <input type="radio" name="request_automove_reset" value="2" 
<?php 
if(array_key_exists("request_automove_reset",$config_ini)) {
  print ($config_ini["request_automove_reset"]!=1)?'checked':' ' ;
}
?>
 /> 
      無効
    </label>
  </div>


<!---- 縛り曲リストの設定 ----->
  <h3> <span data-bs-toggle="tooltip" data-bs-placement="top" title="検索予約メニューの中に特定の曲をピックアップした一覧を表示させることができます" > ピックアップ曲リスト </span> </h3>
  <?php 
  if(array_key_exists("limitlistname",$config_ini)) {
  for($i = 0 ;  $i<count($config_ini["limitlistname"]) ; $i++){
      if(empty($config_ini["limitlistname"][$i])) continue; 
      print '<div class="mb-3">';
      print '  <h4 > 縛り曲リスト名 '.$i.' </h4>';
      print '  <input type="text" name="limitlistname[]" size="100" class="form-control" value="';
      if(array_key_exists($i,$config_ini["limitlistname"]))
       { 
         echo $config_ini["limitlistname"][$i];
       }
      print '" />';
      print '  <h4 > 縛り曲リストファイル名（json形式）'.$i.' </h4>';
      print '  <input type="text" name="limitlistfile[]" size="100" class="form-control" value="';
      if(array_key_exists($i,$config_ini["limitlistfile"])) 
       { 
         echo $config_ini["limitlistfile"][$i];
       }
      print '" />';
      print '</div>';
  
  }
  }
  ?>
  <div class="mb-3">
    <h4 > 縛り曲リスト名(new)  </h4>
    <input type="text" name="limitlistname[]" size="100" class="form-control" value="" />
    <h4 > 縛り曲リストファイル名(new)（json形式） </h4>
    <input type="text" name="limitlistfile[]" size="100" class="form-control" value="" />
  </div>

  <!---- ビンゴ表示機能の設定 ----->
  <div class="mb-3">
  <?php
      $usebingo=false;
      if(array_key_exists("usebingo",$config_ini)){
          if($config_ini["usebingo"]==1 ){
             $usebingo=true;
          }
      }
  ?>
    <h3 class="radio form-label"> ビンゴ表示機能 <br /><small></small> </h3>
    <label class="radio-inline">
      <input type="radio" name="usebingo" value="1" <?php print ($usebingo)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="usebingo" value="2" <?php print (!$usebingo)?'checked':' ' ?> /> 使用しない
    </label>
    <a href ="bingo_input.php" class="btn btn-secondary" > ビンゴ項目入力画面 </a>
  </div>

  <!---- xampp自動再起動の設定 ----->
  <div class="mb-3">
  <?php
      $xamppautorestart=true;
      if(array_key_exists("xamppautorestart",$config_ini)){
          if($config_ini["xamppautorestart"]==1 ){
             $xamppautorestart=true;
          }else {
             $xamppautorestart=false;
          }
      }
  ?>
    <h3 class="radio form-label"> xampp自動再起動  </h3>
    <label class="form-label">  <small>ブラウザのボタンから自動再生が起動できない環境では「使用しない」にしてください</small> </label>
    <label class="radio-inline">
      <input type="radio" name="xamppautorestart" value="1" <?php print ($xamppautorestart)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="xamppautorestart" value="2" <?php print (!$xamppautorestart)?'checked':' ' ?> /> 使用しない
    </label>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <div class="mb-3">
    <h3 id="pfwd" class="form-label menulink">オンライン接続設定</h3>
    <p class="small text-muted">pfwd フォルダ設定、オンライン接続用ホスト名、自動再起動、DDNS 登録などは専用ページで管理します。</p>
    <a href="pfwd_settings.php" class="btn btn-secondary">オンライン接続設定ページへ</a>
  </div>

  <!---- 簡易認証設定 ----->
  <?php
      $useeasyauth=false;
      if(array_key_exists("useeasyauth",$config_ini)){
          if($config_ini["useeasyauth"]==1 ){
             $useeasyauth=true;
          }
      }
  ?>
  <div class="mb-3">
    <h3 class="radio form-label"> 簡易認証 </h3>
    <label class="radio-inline">
      <input type="radio" name="useeasyauth" value="1" <?php print ($useeasyauth)?'checked':' ' ?> /> 使用する
    </label>
    <label class="radio-inline">
      <input type="radio" name="useeasyauth" value="2" <?php print (!$useeasyauth)?'checked':' ' ?> /> 使用しない
    </label>
      <div class="mb-3">
      <h4 class="form-label"> 簡易認証キーワード </h4>
      <input type="text" name="useeasyauth_word" class="form-control" id="useeasyauth_word" 
<?php
if(array_key_exists("useeasyauth_word",$config_ini)) {
    print 'value="'.urldecode($config_ini["useeasyauth_word"]).'"';
}else {
    $newvalue= "";
    echo 'value="'.$newvalue.'"';
}
?>
/>
<script language="javascript" type="text/javascript">
    function GenRandomKeyword() {
        var rand = Math.floor( Math.random() * 9999 ) ;
        target = document.getElementById("useeasyauth_word");
        target.value = ( '0000' + rand ).slice( -4 );
        
    }
</script>
    <button type="button" class="btn btn-secondary" onclick="GenRandomKeyword();" > キーワードランダム生成 </button>
    </div>
  </div>

</div></div>

<div class="card cfg-card mb-4"><div class="card-body">
  <!---- Google同期設定 ----->
  <div class="mb-3">
    <h3 id="googlesync" class="radio form-label menulink"> Google同期設定 </h3>
    <label class="form-label">
      <small>マイページデータをGoogle Driveに保存し、複数サーバー間で共有する機能です。</small>
    </label>

    <h4 class="form-label"> Google Client ID </h4>
    <input type="text" name="google_client_id" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['google_client_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="例: 123456789-xxxx.apps.googleusercontent.com" />

    <h4 class="form-label"> Google Client Secret </h4>
    <small class="text-muted">中継サーバー（ykr.moe）側に設定する場合は空欄にしてください。</small>
    <input type="text" name="google_client_secret" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['google_client_secret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="中継サーバーを使う場合は空欄" />

    <h4 class="form-label"> 中継サーバーURL </h4>
    <input type="text" name="google_relay_url" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['google_relay_url'] ?? 'https://ykr.moe/mypage_google_callback.php'), ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="https://ykr.moe/mypage_google_callback.php" />

    <h4 class="form-label"> 中継シークレット </h4>
    <small class="text-muted">中継サーバーとこのサーバー間の共有秘密鍵です。中継サーバー管理者から入手してください。</small>
    <input type="text" name="google_relay_secret" class="form-control"
      value="<?php echo htmlspecialchars(urldecode($config_ini['google_relay_secret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="中継サーバー管理者から入手したシークレット" />
  </div>
</div></div>

  <input type="submit" class="btn btn-secondary btn-lg" value="設定" />
  </form>
  <hr />

<div class="card cfg-card mb-4"><div class="card-body">
  <h1 id="myiplist" class="menulink"> 自IP一覧 </h1>
  <pre>
  <?php
  require_once("ipconfig.php");
  $result_ipconfig=getiplist();
  
  //var_dump($result_ipconfig);
  foreach($result_ipconfig as $ifinfo){
     $count= 0;
     foreach($ifinfo as $ips){
        if($count != 0){
        if(strchr($ips,':') !== false){
          $ips = '['.strchr($ips,'%',$before_needle=true).']';
        }
        if(!empty($ips)){
           $link = 'http://'.$ips.'/';
           if(array_key_exists('useeasyauth_word', $config_ini)){
               if(!empty($config_ini['useeasyauth_word'])){
                  $link = $link.'?easypass='.$config_ini['useeasyauth_word'];
               }
           }
           print '<a href='.$link.' > '.$link.'</a><br />';
        }
        }
        $count ++;
     }
     
  }
  
  ?>
  </pre>

  </div></div>
</div><!-- col-lg-9 -->
</div><!-- row -->

</div><!-- container -->

<hr />

<script>
(function() {
    var container = document.getElementById('searchitem-sortable');
    if (!container) return;
    Sortable.create(container, {
        handle: '.searchitem-drag-handle',
        animation: 150,
        onEnd: function() {
            var rows = container.querySelectorAll('.searchitem-row');
            for (var i = 0; i < rows.length; i++) {
                rows[i].querySelector('.searchitem-order-input').value = i + 1;
            }
        }
    });
})();

// 別部屋URL ドラッグ並び替え
(function() {
    var tbody = document.getElementById('roomurl-sortable');
    if (!tbody) return;
    Sortable.create(tbody, {
        handle: 'td:first-child',
        animation: 150,
        onEnd: function() {
            // 並び替え後、チェックボックスの value を行番号（0始まり）に更新する
            var rows = tbody.querySelectorAll('.roomurl-row');
            for (var i = 0; i < rows.length; i++) {
                var cb = rows[i].querySelector('input[name="roomurlshow[]"]');
                if (cb) cb.value = i;
            }
        }
    });
})();
</script>

</body>
</html>

