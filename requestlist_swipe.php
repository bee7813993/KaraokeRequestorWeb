<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

$useActiveReload = configbool("requestlistactivereload", true);
$reloadInterval  = isset($config_ini["reloadtime"]) ? (int)$config_ini["reloadtime"] : 20;

$titlePrefix = '';
if (!empty($config_ini['roomurl'])) {
    $roomnames   = array_keys($config_ini['roomurl']);
    $titlePrefix = $roomnames[0] . '：';
}

$bgcolor = '#F8ECE0';
if (!empty($config_ini['bgcolor'])) {
    $bgcolor = urldecode($config_ini['bgcolor']);
}

$connectinternet = isset($config_ini['connectinternet']) ? (int)$config_ini['connectinternet'] : 1;
$useposttwitter  = configbool('useposttwitter', true);
$playmode        = isset($config_ini['playmode']) ? (int)$config_ini['playmode'] : 3;
$usebgv          = isset($config_ini['usebgv']) ? (int)$config_ini['usebgv'] : 2;
$isAdmin         = ($user === 'admin');
$requestlist_num = isset($config_ini['requestlist_num']) ? (int)$config_ini['requestlist_num'] : 10;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma"        content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires"       content="0">
<title><?php echo htmlspecialchars($titlePrefix, ENT_QUOTES, 'UTF-8'); ?>リクエスト一覧</title>
<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}})();</script>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"                    rel="stylesheet">
<link href="css/themes/theme-toggle.css"      rel="stylesheet">
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
<script src="js/theme-toggle.js"></script>
<!-- SortableJS: js/Sortable.min.js に配置してください。取得先: https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js -->
<script src="js/Sortable.min.js"></script>
<style>
body { background-color: <?php echo htmlspecialchars($bgcolor, ENT_QUOTES, 'UTF-8'); ?>; }

#request-list {
  margin-top: 8px;
  padding-bottom: 20px;
}

/* ---- カード ---- */
.request-card {
  position: relative;
  overflow: hidden;
  margin-bottom: 6px;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  background: rgba(var(--bg-card-rgb, 255, 255, 255), var(--bg-card-alpha, 1));
  border-left: 4px solid #ced4da;
  -webkit-user-select: none;
  user-select: none;
}
/* 再生状況別の左ボーダー色 + 背景色(透過率は --bg-card-alpha を共用) */
.request-card[data-nowplaying="再生中"],
.request-card[data-nowplaying="2"] {
  border-left-color: var(--bs-success);
  background: rgba(246, 255, 248, var(--bg-card-alpha, 1));
}
.request-card[data-nowplaying="再生開始待ち"],
.request-card[data-nowplaying="6"] {
  border-left-color: var(--bs-warning);
  background: rgba(255, 254, 245, var(--bg-card-alpha, 1));
}
.request-card[data-nowplaying="停止中"],
.request-card[data-nowplaying="3"] {
  border-left-color: var(--bs-warning);
}
.request-card[data-nowplaying="再生済"],
.request-card[data-nowplaying="再生済？"],
.request-card[data-nowplaying="4"],
.request-card[data-nowplaying="5"] {
  border-left-color: #adb5bd;
  background: rgba(var(--bg-card-alt-rgb, 248, 244, 240), var(--bg-card-alpha, 1));
}

/* スワイプで現れるアクションボタン群 */
.card-actions {
  position: absolute;
  top: 0; right: 0; bottom: 0;
  display: flex;
  z-index: 1;
  /* 通常時は不可視。スワイプ中(.swiping)または開いた状態(.swipe-open)のみ表示。
     カード透過時にメニューが透けて見えるのを防ぐため。 */
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.15s ease;
}
.request-card.swiping .card-actions,
.request-card.swipe-open .card-actions {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

.action-btn {
  width: 80px;
  border: none;
  color: #fff;
  font-size: 12px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  line-height: 1.2;
  padding: 0;
}
.action-btn:active { opacity: 0.8; }
.action-replace { background: var(--bs-success); }
.action-next    { background: var(--bs-orange, #fd7e14); }
.action-delete  { background: var(--bs-danger); }
.action-change  { background: var(--bs-secondary); }
.action-icon    { font-size: 18px; }

/* カード本体（スワイプで左にスライド） */
.card-main {
  position: relative;
  z-index: 2;
  background: inherit;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  min-width: 0;
  transition: transform 0.2s ease;
  cursor: default;
}
.request-card.swipe-open .card-main {
  transform: translateX(-240px);
}
.request-card.admin-card.swipe-open .card-main {
  transform: translateX(-320px);
}

/* ドラッグハンドル */
.drag-handle {
  flex-shrink: 0;
  font-size: 20px;
  color: var(--color-text-muted, #999);
  cursor: grab;
  padding: 0 2px;
  touch-action: none;
  line-height: 1;
}
.drag-handle:active { cursor: grabbing; }

/* カード内テキスト */
.card-info {
  flex: 1;
  min-width: 0;
}
.card-title {
  font-size: 16px;
  font-weight: bold;
  color: var(--color-text, #212529);
  word-break: break-all;
  line-height: 1.4;
  cursor: pointer;
  transition: opacity 0.2s;
  padding: 2px 0;
  border-radius: 3px;
}
.card-title:hover {
  opacity: 0.7;
  background: rgba(0, 0, 0, 0.05);
}
.card-meta {
  font-size: 14px;
  color: var(--color-text-muted, #444);
  margin-top: 4px;
}
.card-label {
  color: var(--color-text-muted, #666);
  font-size: 12px;
  font-weight: 600;
}

/* 右カラム（バッジ＋曲終了ボタン） */
.card-right {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  min-width: 64px;
}
.status-badge-btn {
  cursor: pointer;
}
.status-badge-btn:hover .label { opacity: 0.8; }
.card-ctrl-btn {
  font-size: 12px;
  padding: 2px 8px;
}
/* コメント欄 */
.card-comment-area {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--color-text-muted, #555);
  cursor: pointer;
  padding: 4px 8px;
  min-height: 24px;
  margin-top: 5px;
  background: var(--bg-card-alt, #f8f9fa);
  border-radius: 6px;
  border: 1px solid var(--color-border, #e9ecef);
  transition: border-color 0.15s, color 0.15s;
}
.card-comment-area:hover { color: var(--bs-primary); border-color: var(--bs-primary); }
.card-comment-icon { font-size: 13px; flex-shrink: 0; opacity: 0.6; }
.card-comment-placeholder { color: #aaa; font-style: italic; }
/* Tweet リンク */
.card-tweet-link {
  font-size: 11px;
  color: #1da1f2;
  text-decoration: none;
  display: inline-block;
  margin-top: 3px;
}
.card-tweet-link:hover { text-decoration: underline; color: #0c85d0; }

/* 展開ボタン（技術設定・Tweetを表示） */
.card-expand-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 32px;
  color: var(--color-text-muted, #aaa);
  padding: 0;
  line-height: 1;
  transition: transform 0.2s ease, color 0.2s ease;
  min-width: 44px;
  min-height: 44px;
}
.card-expand-btn:hover { color: var(--bs-primary); }
.request-card.card-expanded .card-expand-btn { transform: rotate(180deg); }
/* 展開エリア（技術設定チップ＋Tweet） */
.card-details {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.25s ease;
}
.request-card.card-expanded .card-details { max-height: 200px; }

/* ---- メタ情報チップ ---- */
.meta-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 5px;
  align-items: center;
}
.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 9px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  line-height: 1.6;
  white-space: nowrap;
}
.chip-singer        { background: #e8eaf6; color: #3949ab; }
.chip-kind          { background: #e8f5e9; color: #2e7d32; } /* fallback */
.chip-kind-video    { background: #dbeafe; color: #1d4ed8; } /* 動画 */
.chip-kind-karaoke  { background: #ffe4e6; color: #be123c; } /* カラオケ配信 */
.chip-kind-pause    { background: #f1f5f9; color: #475569; } /* 小休止 */
.chip-kind-url      { background: #fef3c7; color: #b45309; } /* URL指定 */
.chip-kind-bgv      { background: #ede9fe; color: #6d28d9; } /* BGV選択 */
.chip-kind-nico     { background: #cffafe; color: #0e7490; } /* ニコニコ動画 */
.chip-filename      { background: #f3e5f5; color: #6a1b9a; font-size: 11px; white-space: normal; word-break: break-all; }
.chip-duration { background: #f1f3f5; color: #555;    }
.chip-track    { background: #f3e5f5; color: #7b1fa2; }
.chip-key-pos  { background: #e8f5e9; color: #2e7d32; font-weight: 700; }
.chip-key-neg  { background: #fce4ec; color: #c62828; font-weight: 700; }
.chip-volume   { background: #fff3e0; color: #e65100; }
.chip-delay    { background: #e3f2fd; color: #1565c0; }

/* SortableJS */
.sortable-ghost {
  opacity: 0.4;
  background: #cfe2ff !important;
}
.sortable-chosen {
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}

/* ヘッダ行 */
.list-toolbar {
  margin-bottom: 4px;
}
.toolbar-left {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}
.toolbar-left h4 { margin: 0; flex: 1; }
.toolbar-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
#count-select {
  width: auto;
  display: inline-block;
  margin-left: auto;
}

@keyframes playing-pulse {
  0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
  40%       { box-shadow: 0 0 0 4px rgba(230,126,34,0.6); }
}
.highlight-playing {
  animation: playing-pulse 0.7s ease 3;
}
@keyframes new-item-pulse {
  0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
  40%       { box-shadow: 0 0 0 4px rgba(39,174,96,0.7); }
}
.highlight-new {
  animation: new-item-pulse 0.7s ease 4;
}

#empty-msg {
  text-align: center;
  color: var(--color-text-muted, #888);
  padding: 30px 0;
  font-size: 15px;
}
#load-more-wrap {
  text-align: center;
  padding: 12px 0 4px;
}

/* 統計バー */
#stats-bar {
  font-size: 13px;
  color: var(--color-text-muted, #444);
  background: var(--bg-card, #fff);
  border-radius: 4px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
  padding: 6px 10px;
  margin-bottom: 6px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  border: 1px solid var(--color-border, #e9ecef);
}
.stats-item { white-space: nowrap; }
.stats-val   { font-weight: bold; color: var(--color-text, #212529); }

/* カード左側（番号＋ドラッグハンドル） */
.card-left {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 28px;
}
.card-num {
  font-size: 13px;
  color: var(--color-text-muted, #555);
  line-height: 1;
  font-weight: 700;
}
</style>
</head>
<body>
<div class="container">
<?php
shownavigatioinbar_bs5();
showmode();

if (!empty($config_ini['noticeof_listpage'])) {
    echo '<div class="p-3 mb-3 border rounded bg-light">';
    echo str_replace('#yukarihost#', $_SERVER['HTTP_HOST'], urldecode($config_ini['noticeof_listpage']));
    echo '</div>';
}
?>

<?php if ($reloadInterval != 0): ?>
<div class="form-check" data-bs-toggle="tooltip" data-bs-placement="top"
     title="コピペとかする時はチェックを外してください">
  <input class="form-check-input" type="checkbox" id="autoreload" checked>
  <label class="form-check-label" for="autoreload">自動リロード</label>
</div>
<?php endif; ?>

<hr>

<div class="list-toolbar">
  <div class="toolbar-left">
    <h4>現在の登録状況</h4>
    <button class="btn btn-secondary btn-sm" id="refresh-btn">更新</button>
    <button id="title-toggle-btn" class="btn btn-secondary btn-sm"></button>
    <button class="btn btn-primary btn-sm ms-auto" id="goto-playing-btn">&#9654; 再生中へ</button>
  </div>
  <div class="toolbar-right">
    <a href="simplelistexport_utf8.php" class="btn btn-secondary btn-sm">リクエストリストCSV</a>
    <a href="simplelist.php" class="btn btn-secondary btn-sm">シンプルリスト</a>
<?php if ($requestlist_num > 0): ?>
    <select id="count-select" class="form-select form-select-sm">
      <option value="<?php echo $requestlist_num; ?>"><?php echo $requestlist_num; ?>件</option>
      <option value="<?php echo $requestlist_num * 2; ?>"><?php echo $requestlist_num * 2; ?>件</option>
      <option value="0">ALL</option>
    </select>
<?php endif; ?>
  </div>
</div>

<div id="stats-bar">
  <span class="stats-item">総件数：<span class="stats-val" id="stat-total">-</span>件</span>
  <span class="stats-item">残り：<span class="stats-val" id="stat-remaining">-</span>件</span>
  <span class="stats-item">残り時間：<span class="stats-val" id="stat-remaining-time">-</span></span>
</div>

<div id="request-list"></div>
<div id="load-more-wrap" style="display:none">
  <button class="btn btn-secondary" id="load-more-btn">もっと見る</button>
</div>
<div id="empty-msg" style="display:none">リクエストはありません</div>

</div><!-- /.container -->

<!-- コメント編集モーダル -->
<div class="modal fade" id="comment-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">コメントへのレス＆編集</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="comment-edit-form">
          <div class="mb-3">
            <label>コメント修正</label>
            <textarea class="form-control" id="comment-edit-text" rows="3"></textarea>
          </div>
          <button type="submit" class="btn btn-secondary float-end">修正</button>
        </form>
        <div class="clearfix" style="margin-bottom:10px;"></div>
        <hr>
        <form id="comment-reply-form">
          <div class="mb-3">
            <label>レス <small>再生中にコメントするとその場で流れます</small></label>
            <input type="text" class="form-control" id="comment-reply-text" placeholder="レス(コメントへの)">
          </div>
          <div class="mb-3">
            <label>名前</label>
            <input type="text" class="form-control" id="comment-reply-name" placeholder="名前">
          </div>
          <button type="submit" class="btn btn-primary float-end">送信</button>
        </form>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
      </div>
    </div>
  </div>
</div>

<!-- 再生状況変更モーダル -->
<div class="modal fade" id="status-modal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">再生状況を変更</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <select class="form-select" id="status-select">
          <option value="未再生">未再生</option>
          <option value="再生済">再生済</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button type="button" class="btn btn-primary" id="status-submit">変更</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});

// ---- 設定値（PHP から埋め込み） ----
var USE_ACTIVE_RELOAD  = <?php echo $useActiveReload ? 'true' : 'false'; ?>;
var RELOAD_INTERVAL    = <?php echo $reloadInterval; ?> * 1000;
var CONNECT_INTERNET   = <?php echo $connectinternet; ?>;
var USE_POST_TWITTER   = <?php echo $useposttwitter ? 'true' : 'false'; ?>;
var PLAYMODE           = <?php echo $playmode; ?>;
var USE_BGV            = <?php echo ($usebgv == 1) ? 'true' : 'false'; ?>;
var IS_ADMIN           = <?php echo $isAdmin ? 'true' : 'false'; ?>;
var REQUESTLIST_NUM    = <?php echo $requestlist_num; ?>;

// ---- 状態 ----
var openCard        = null;
var isDragging      = false;
var sortable        = null;
var currentLimit    = REQUESTLIST_NUM; // 0 = ALL
var shownCount      = 0;
var totalCount      = 0;
var titleDisplayMode = localStorage.getItem('ykari-title-display-mode') || 'songname'; // 'songname' or 'filename'

// ---- 件数選択のcookie保存/復元 ----
function getCountCookie() {
    var m = document.cookie.match(/(?:^|; )swipe_count_limit=([^;]*)/);
    if (!m) return null;
    var v = parseInt(decodeURIComponent(m[1]), 10);
    return isNaN(v) ? null : v;
}
function setCountCookie(val) {
    var d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    document.cookie = 'swipe_count_limit=' + encodeURIComponent(val)
        + '; expires=' + d.toUTCString() + '; path=/';
}
(function applyCountCookie() {
    if (REQUESTLIST_NUM <= 0) return; // 全件設定時はスキップ
    var saved = getCountCookie();
    if (saved === null) return;
    var sel = document.getElementById('count-select');
    if (!sel) return;
    // 選択肢に存在する値のみ適用
    var opts = Array.prototype.map.call(sel.options, function (o) { return parseInt(o.value, 10); });
    if (opts.indexOf(saved) !== -1) {
        currentLimit = saved;
        sel.value = String(saved);
    }
})();

// ---- ユーティリティ ----
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

var STATUS_CLASS = {
    // 数値コード
    '1': 'secondary', '2': 'success', '3': 'warning',
    '4': 'info',      '5': 'info',   '6': 'warning',  '7': 'danger',
    // 日本語テキスト（既存DBとの互換）
    '未再生': 'secondary', '再生中': 'success',    '停止中': 'warning',
    '再生済': 'info',      '再生済？': 'info',     '再生開始待ち': 'warning',
    '変更中': 'danger'
};
var STATUS_LABEL = {
    '1': '未再生', '2': '再生中',    '3': '停止中',
    '4': '再生済', '5': '再生済？',  '6': '再生開始待ち', '7': '変更中'
};

function statusBadge(nowplaying) {
    if (!nowplaying) return '';
    var cls   = STATUS_CLASS[String(nowplaying)] || 'default';
    var label = STATUS_LABEL[String(nowplaying)] || nowplaying; // 数値なら日本語に変換、テキストはそのまま
    return '<span class="badge bg-' + cls + '">' + esc(label) + '</span>';
}

// ---- カード HTML 生成 ----
function getUsernameFromCookie() {
    var m = document.cookie.match(/(?:^|; )YkariUsername=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

function isPlaying(nowplaying) {
    return nowplaying === '再生中' || nowplaying === '2';
}
function isWaiting(nowplaying) {
    return nowplaying === '再生開始待ち' || nowplaying === '6';
}
function isUnplayed(nowplaying) {
    return nowplaying === '未再生' || nowplaying === '1';
}

function kindChipClass(kind) {
    switch (kind) {
        case '動画':        return 'chip-kind-video';
        case 'カラオケ配信': return 'chip-kind-karaoke';
        case '小休止':      return 'chip-kind-pause';
        case 'URL指定':     return 'chip-kind-url';
        case 'BGV選択':     return 'chip-kind-bgv';
        case 'ニコニコ動画': return 'chip-kind-nico';
        default:            return 'chip-kind';
    }
}

function createCardHTML(item, idx, displayMode) {
    displayMode = displayMode || titleDisplayMode; // デフォルトはグローバル設定
    var replaceLabel = (item.kind === 'カラオケ配信' && USE_BGV) ? 'BGV選択' : '曲差し替え';

    // コメント欄
    var commentHtml = '<div class="card-comment-area" data-id="' + item.id + '" data-comment="' + esc(item.comment) + '">'
        + '<span class="card-comment-icon">&#128172;</span>';
    if (item.comment) {
        commentHtml += '<span>' + esc(item.comment) + '</span><small style="margin-left:4px;opacity:.6;">&#9998;</small>';
    } else {
        commentHtml += '<span class="card-comment-placeholder">コメントを追加...</span>';
    }
    commentHtml += '</div>';

    // 曲終了 / 曲開始ボタン
    var ctrlBtn = '';
    if (isPlaying(item.nowplaying)) {
        ctrlBtn = '<button class="btn btn-warning btn-sm card-ctrl-btn song-end-btn">曲終了</button>';
    } else if (isWaiting(item.nowplaying) && item.kind === '動画') {
        ctrlBtn = '<button class="btn btn-success btn-sm card-ctrl-btn song-start-btn">曲開始</button>';
    }

    // Tweet リンク
    var tweetHtml = '';
    // 常に本来の曲名を保持（data属性用）
    var actualSongName = item.song_name || item.display_name || item.songfile;
    // displayMode に基づいて表示する曲名を決定
    var displayName = (displayMode === 'filename') ? item.songfile : actualSongName;
    if (CONNECT_INTERNET && USE_POST_TWITTER) {
        var msg;
        if (isPlaying(item.nowplaying)) {
            msg = '「' + item.singer + '」は「' + displayName + '」を歌っています';
        } else if (isUnplayed(item.nowplaying)) {
            msg = '「' + item.singer + '」は「' + displayName + '」を歌います';
        } else {
            msg = '「' + item.singer + '」は「' + displayName + '」を歌いました';
        }
        tweetHtml = '<a href="https://twitter.com/intent/tweet?text=' + encodeURIComponent(msg) + '" target="_blank" class="card-tweet-link">&#x1F426; Tweetする</a>';
    }

    var adminChangeBtnHtml = IS_ADMIN ? [
        '    <button class="action-btn action-change"',
        '            data-id="'       + item.id              + '"',
        '            data-songfile="' + esc(item.songfile)   + '">',
        '      <span class="action-icon">&#9998;</span>変更',
        '    </button>'
    ].join('\n') : '';

    // 番号
    var position = item.position != null ? item.position : (totalCount - idx);
    var numHtml = '<span class="card-num">' + position + '</span>';

    // メイン情報チップ（登録者・再生方法）
    var mainChips = '<div class="meta-chips">'
        + '<span class="meta-chip chip-singer">&#128100; 登録者：' + esc(item.singer) + '</span>'
        + '<span class="meta-chip ' + kindChipClass(item.kind) + '">&#9654; ' + esc(item.kind) + '</span>'
        + '</div>';

    // 展開時表示用チップ（ファイル名・曲の長さ・トラック・キー・音ズレ・音量）
    var track = parseInt(item.track, 10);
    var keychange = parseInt(item.keychange, 10);
    var audiodelay = parseInt(item.audiodelay, 10);
    var volume = parseInt(item.volume, 10);
    var extraChips = [];

    // ファイル名
    if (item.songfile) {
        extraChips.push('<span class="meta-chip chip-filename">&#128193; ' + esc(item.songfile) + '</span>');
    }

    // 曲の長さ
    if (item.duration && item.duration > 0) {
        var dm = Math.floor(item.duration / 60);
        var ds = item.duration % 60;
        extraChips.push('<span class="meta-chip chip-duration">&#9201; ' + dm + ':' + ('0' + ds).slice(-2) + '</span>');
    }

    if (track > 0) {
        extraChips.push('<span class="meta-chip chip-track">&#127926; トラック ' + (track + 1) + '</span>');
    }
    if (keychange !== 0) {
        var keyClass = keychange > 0 ? 'chip-key-pos' : 'chip-key-neg';
        extraChips.push('<span class="meta-chip ' + keyClass + '">&#9835; キー ' + (keychange > 0 ? '+' : '') + keychange + '</span>');
    }
    if (audiodelay !== 0) {
        extraChips.push('<span class="meta-chip chip-delay">&#8987; 音ズレ ' + (audiodelay > 0 ? '+' : '') + audiodelay + 'ms</span>');
    }
    if (!isNaN(volume) && volume !== 0 && volume !== -1) {
        extraChips.push('<span class="meta-chip chip-volume">&#128266; 音量 ' + (volume > 0 ? '+' : '') + volume + '%</span>');
    }
    var extraMetaHtml = extraChips.length > 0
        ? '<div class="meta-chips">' + extraChips.join('') + '</div>'
        : '';
    var hasDetails = extraChips.length > 0 || tweetHtml !== '';
    var cardDetailsHtml = hasDetails
        ? '<div class="card-details">' + extraMetaHtml + tweetHtml + '</div>'
        : '';
    var expandBtnHtml = hasDetails
        ? '<button class="card-expand-btn" aria-label="詳細を展開">&#9662;</button>'
        : '';

    return [
        '<div class="request-card' + (IS_ADMIN ? ' admin-card' : '') + '"',
        '     data-id="'       + item.id              + '"',
        '     data-songfile="' + esc(item.songfile)   + '"',
        '     data-kind="'     + esc(item.kind)       + '"',
        '     data-nowplaying="' + esc(item.nowplaying) + '">',
        '  <div class="card-actions">',
        '    <button class="action-btn action-replace"',
        '            data-id="'       + item.id              + '"',
        '            data-songfile="' + esc(item.songfile)   + '">',
        '      <span class="action-icon">&#8635;</span>' + replaceLabel,
        '    </button>',
        '    <button class="action-btn action-next"',
        '            data-id="'       + item.id              + '"',
        '            data-songfile="' + esc(item.songfile)   + '">',
        '      <span class="action-icon">&#9654;</span>次に再生',
        '    </button>',
        '    <button class="action-btn action-delete"',
        '            data-id="'       + item.id              + '"',
        '            data-songfile="' + esc(item.songfile)   + '">',
        '      <span class="action-icon">&#10005;</span>削除',
        '    </button>',
        adminChangeBtnHtml,
        '  </div>',
        '  <div class="card-main">',
        '    <div class="card-left">',
        '      ' + numHtml,
        '      <span class="drag-handle">&#8942;</span>',
        '    </div>',
        '    <div class="card-info">',
        '      <div class="card-title" data-songname="' + esc(actualSongName) + '" data-filename="' + esc(item.songfile) + '" data-showing="' + displayMode + '">' + esc(displayName) + '</div>',
        '      ' + mainChips,
        '      ' + commentHtml,
        '      ' + cardDetailsHtml,
        '    </div>',
        '    <div class="card-right">',
        '      <span class="status-badge-btn"',
        '            data-id="'         + item.id              + '"',
        '            data-songfile="'   + esc(item.songfile)   + '"',
        '            data-nowplaying="' + esc(item.nowplaying) + '">',
        '        ' + statusBadge(item.nowplaying),
        '      </span>',
        '      ' + ctrlBtn,
        '      ' + expandBtnHtml,
        '    </div>',
        '  </div>',
        '</div>'
    ].join('\n');
}

// ---- データ読み込みと描画 ----
function buildUrl(limit, offset) {
    return 'requestlist_swipe_json.php?limit=' + limit + '&offset=' + offset;
}

// reset=true: 最初から描画し直す / reset=false: 追加読み込み
function loadList(reset) {
    if (reset === undefined || reset === true) {
        var limit  = currentLimit;
        var offset = 0;
        fetch(buildUrl(limit, offset))
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
            .then(function (data) { renderList(data.items, data.total, data.has_more, data); })
            .catch(function (e)   { console.error('loadList error:', e); });
    } else {
        // もっと見る: 現在表示分をまとめて再取得して全置き換え
        var newLimit = (currentLimit > 0) ? shownCount + currentLimit : 0;
        fetch(buildUrl(newLimit, 0))
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
            .then(function (data) { renderList(data.items, data.total, data.has_more, data); })
            .catch(function (e)   { console.error('loadMore error:', e); });
    }
}

function formatRemainingTime(secs) {
    if (!secs || secs <= 0) return '-';
    var h = Math.floor(secs / 3600);
    var m = Math.floor((secs % 3600) / 60);
    if (h > 0) return h + '時間' + m + '分';
    return m + '分';
}

function updateStats(data) {
    var el;
    el = document.getElementById('stat-total');
    if (el) el.textContent = data.total || 0;
    el = document.getElementById('stat-remaining');
    if (el) el.textContent = data.remaining_count != null ? data.remaining_count : '-';
    el = document.getElementById('stat-remaining-time');
    if (el) el.textContent = formatRemainingTime(data.remaining_seconds);
}

function renderList(items, total, hasMore, data) {
    var container   = document.getElementById('request-list');
    var emptyMsg    = document.getElementById('empty-msg');
    var loadMoreWrap = document.getElementById('load-more-wrap');

    totalCount = total || 0;
    if (data) updateStats(data);

    if (items.length === 0 && totalCount === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = '';
        loadMoreWrap.style.display = 'none';
        if (sortable) { sortable.destroy(); sortable = null; }
        return;
    }

    emptyMsg.style.display = 'none';
    shownCount = items.length;
    container.innerHTML = items.map(function (item, idx) {
        return createCardHTML(item, idx, titleDisplayMode);
    }).join('');

    var remaining = totalCount - shownCount;
    if (hasMore && remaining > 0) {
        document.getElementById('load-more-btn').textContent = 'もっと見る（残り' + remaining + '件）';
        loadMoreWrap.style.display = '';
    } else {
        loadMoreWrap.style.display = 'none';
    }

    initSortable();
    initSwipe();

    var cb = afterRenderCallback;
    afterRenderCallback = null;
    if (cb) cb();
}

// ---- SortableJS（ドラッグ並べ替え） ----
function initSortable() {
    var container = document.getElementById('request-list');
    if (sortable) sortable.destroy();

    sortable = Sortable.create(container, {
        handle:      '.drag-handle',
        animation:    150,
        ghostClass:  'sortable-ghost',
        chosenClass: 'sortable-chosen',

        onStart: function () {
            isDragging = true;
            if (openCard) closeCard(openCard);
        },

        onEnd: function (evt) {
            isDragging = false;
            if (evt.oldIndex === evt.newIndex) return;

            var cards = container.querySelectorAll('.request-card');
            var ids   = Array.prototype.map.call(cards, function (c) {
                return parseInt(c.dataset.id, 10);
            });

            fetch('requestlist_reorder.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ ids: ids })
            }).catch(function (e) {
                console.error('reorder error:', e);
                loadList();
            });
        }
    });
}

// ---- スワイプアクション ----
function closeCard(card) {
    card.classList.remove('swipe-open');
    card.classList.remove('swiping');
    var main = card.querySelector('.card-main');
    if (main) main.style.transform = '';
    if (openCard === card) openCard = null;
}

function initSwipe() {
    document.querySelectorAll('.request-card:not([data-swipe])').forEach(function (card) {
        card.setAttribute('data-swipe', '1');
        var main = card.querySelector('.card-main');
        if (!main) return;

        var OPEN_WIDTH = card.classList.contains('admin-card') ? 320 : 240; // 管理者は4ボタン×80px
        var DIR_THR   = 10;  // px：方向判定閾値
        var SNAP_THR  = 60;  // px：スナップ閾値

        // ----- タッチ -----
        var touchStartX, touchStartY, touchTracking = false, touchSwiping = false;

        main.addEventListener('touchstart', function (e) {
            if (e.target.closest('.drag-handle')) return;
            touchStartX   = e.touches[0].clientX;
            touchStartY   = e.touches[0].clientY;
            touchTracking = true;
            touchSwiping  = false;
            main.style.transition = 'none';
        }, { passive: true });

        main.addEventListener('touchmove', function (e) {
            if (!touchTracking) return;
            var dx = e.touches[0].clientX - touchStartX;
            var dy = e.touches[0].clientY - touchStartY;

            if (!touchSwiping) {
                if (Math.abs(dx) > DIR_THR && Math.abs(dx) > Math.abs(dy)) {
                    touchSwiping = true;
                    card.classList.add('swiping');
                    if (openCard && openCard !== card) closeCard(openCard);
                } else if (Math.abs(dy) > DIR_THR) {
                    touchTracking = false; // 縦スクロール → 解除
                    main.style.transition = '';
                    return;
                }
            }

            if (touchSwiping) {
                e.preventDefault();
                var base = card.classList.contains('swipe-open') ? -OPEN_WIDTH : 0;
                var x    = Math.max(-OPEN_WIDTH, Math.min(0, base + dx));
                main.style.transform = 'translateX(' + x + 'px)';
            }
        }, { passive: false });

        main.addEventListener('touchend', function (e) {
            if (!touchTracking) return;
            touchTracking = false;
            main.style.transition = '';
            if (!touchSwiping) return;

            var dx     = e.changedTouches[0].clientX - touchStartX;
            var isOpen = card.classList.contains('swipe-open');

            main.style.transform = '';
            if (!isOpen && dx < -SNAP_THR) {
                card.classList.add('swipe-open');
                openCard = card;
            } else if (isOpen && dx > SNAP_THR) {
                closeCard(card);
            }
            card.classList.remove('swiping');
        }, { passive: true });

        // ----- マウス（デスクトップ） -----
        var mouseStartX, mouseTracking = false, mouseSwiping = false;

        main.addEventListener('mousedown', function (e) {
            if (e.target.closest('.drag-handle') || e.button !== 0) return;
            mouseStartX   = e.clientX;
            mouseTracking = true;
            mouseSwiping  = false;
            main.style.transition = 'none';

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup',   onMouseUp, { once: true });
        });

        function onMouseMove(e) {
            if (!mouseTracking) return;
            var dx = e.clientX - mouseStartX;

            if (!mouseSwiping && Math.abs(dx) > DIR_THR) {
                mouseSwiping = true;
                card.classList.add('swiping');
                if (openCard && openCard !== card) closeCard(openCard);
            }
            if (mouseSwiping) {
                var base = card.classList.contains('swipe-open') ? -OPEN_WIDTH : 0;
                var x    = Math.max(-OPEN_WIDTH, Math.min(0, base + dx));
                main.style.transform = 'translateX(' + x + 'px)';
            }
        }

        function onMouseUp(e) {
            document.removeEventListener('mousemove', onMouseMove);
            if (!mouseTracking) return;
            mouseTracking = false;
            main.style.transition = '';
            if (!mouseSwiping) return;

            var dx     = e.clientX - mouseStartX;
            var isOpen = card.classList.contains('swipe-open');

            main.style.transform = '';
            if (!isOpen && dx < -SNAP_THR) {
                card.classList.add('swipe-open');
                openCard = card;
            } else if (isOpen && dx > SNAP_THR) {
                closeCard(card);
            }
            card.classList.remove('swiping');
        }
    });

    // カード外タップでアクションを閉じる
    document.addEventListener('click', function (e) {
        if (openCard && !e.target.closest('.request-card')) {
            closeCard(openCard);
        }
    });
}

// ---- アクション実行 ----

// 曲差し替え
function replaceSong(id) {
    if (openCard) closeCard(openCard);
    location.href = 'searchreserve.php?id=' + id;
}

// 管理者用変更
function changeItem(id, songfile) {
    if (openCard) closeCard(openCard);
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'change.php';
    var fid = document.createElement('input');
    fid.type = 'hidden'; fid.name = 'id'; fid.value = id;
    var fsong = document.createElement('input');
    fsong.type = 'hidden'; fsong.name = 'songfile'; fsong.value = songfile;
    form.appendChild(fid);
    form.appendChild(fsong);
    document.body.appendChild(form);
    form.submit();
}

// 曲終了
function songEnd() {
    fetch('playerctrl_portal.php?songnext=1').then(loadList);
}

// 曲開始
function songStart() {
    fetch('playerctrl_portal.php?songstart=1').then(loadList);
}

// コメントモーダル
var currentCommentId = null;
function openCommentModal(id, comment) {
    currentCommentId = id;
    document.getElementById('comment-edit-text').value  = comment || '';
    document.getElementById('comment-reply-text').value = '';
    document.getElementById('comment-reply-name').value = getUsernameFromCookie();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('comment-modal')).show();
}

document.getElementById('comment-edit-form').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!currentCommentId) return;
    var comment = document.getElementById('comment-edit-text').value;
    fetch('update.php?id=' + currentCommentId + '&comment=' + encodeURIComponent(comment) + '&edit=edit')
        .then(function () { bootstrap.Modal.getInstance(document.getElementById('comment-modal')).hide(); loadList(); });
});

document.getElementById('comment-reply-form').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!currentCommentId) return;
    var reply = document.getElementById('comment-reply-text').value;
    var name  = document.getElementById('comment-reply-name').value;
    fetch('commentedit.php?id=' + currentCommentId + '&addcomment=' + encodeURIComponent(reply) + '&name=' + encodeURIComponent(name) + '&add=add')
        .then(function () { bootstrap.Modal.getInstance(document.getElementById('comment-modal')).hide(); loadList(); });
});

// 再生状況変更モーダル
var currentStatusId      = null;
var currentStatusSongfile = null;
function openStatusModal(id, songfile, nowplaying) {
    currentStatusId       = id;
    currentStatusSongfile = songfile;
    var sel = document.getElementById('status-select');
    // STATUS_LABEL を使って日本語ラベルに正規化してから選択
    var label = STATUS_LABEL[String(nowplaying)] || nowplaying;
    sel.value = label;
    if (!sel.value) sel.value = '未再生';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('status-modal')).show();
}

document.getElementById('status-submit').addEventListener('click', function () {
    if (!currentStatusId) return;
    var val = document.getElementById('status-select').value;
    fetch('changeplaystatus.php?id=' + currentStatusId + '&songfile=' + encodeURIComponent(currentStatusSongfile) + '&nowplaying=' + encodeURIComponent(val))
        .then(function () { bootstrap.Modal.getInstance(document.getElementById('status-modal')).hide(); loadList(); });
});

// モーダル表示中は自動リロードを抑制
var storedAutoReload = true;
['comment-modal', 'status-modal'].forEach(function (id) {
    var el = document.getElementById(id);
    el.addEventListener('show.bs.modal', function () {
        var cb = document.getElementById('autoreload');
        storedAutoReload = cb ? cb.checked : true;
        if (cb) cb.checked = false;
    });
    el.addEventListener('hide.bs.modal', function () {
        var cb = document.getElementById('autoreload');
        if (cb) cb.checked = storedAutoReload;
    });
});

function playNext(id, songfile) {
    fetch('delete.php?id=' + id + '&warikomi=warikomi&songfile=' + encodeURIComponent(songfile))
        .then(function () {
            if (openCard) closeCard(openCard);
            loadList();
        })
        .catch(function (e) { console.error('playNext error:', e); });
}

function deleteItem(id, songfile) {
    if (!confirm('削除しますか？')) return;
    var fd = new FormData();
    fd.append('id',       id);
    fd.append('songfile', songfile);
    fd.append('delete',   'delete');
    fetch('delete.php', { method: 'POST', body: fd })
        .then(function () {
            if (openCard) closeCard(openCard);
            loadList();
        })
        .catch(function (e) { console.error('deleteItem error:', e); });
}

// ---- イベント委譲 ----
document.getElementById('request-list').addEventListener('click', function (e) {
    // スワイプアクションボタン
    var btn = e.target.closest('.action-btn');
    if (btn) {
        var id       = parseInt(btn.dataset.id, 10);
        var songfile = btn.dataset.songfile;
        if (btn.classList.contains('action-replace')) replaceSong(id);
        if (btn.classList.contains('action-next'))    playNext(id, songfile);
        if (btn.classList.contains('action-delete'))  deleteItem(id, songfile);
        if (btn.classList.contains('action-change'))  changeItem(id, songfile);
        return;
    }
    // 曲名タップ（個別切り替え）
    var titleEl = e.target.closest('.card-title');
    if (titleEl) {
        var current = titleEl.dataset.showing || titleDisplayMode;
        var next = (current === 'songname') ? 'filename' : 'songname';
        titleEl.dataset.showing = next;
        titleEl.textContent = (next === 'filename') ? titleEl.dataset.filename : titleEl.dataset.songname;
        return;
    }
    // 展開ボタン
    var expandBtn = e.target.closest('.card-expand-btn');
    if (expandBtn) {
        expandBtn.closest('.request-card').classList.toggle('card-expanded');
        return;
    }
    // コメント欄タップ
    var ca = e.target.closest('.card-comment-area');
    if (ca) {
        openCommentModal(parseInt(ca.dataset.id, 10), ca.dataset.comment);
        return;
    }
    // 再生状況バッジタップ
    var sb = e.target.closest('.status-badge-btn');
    if (sb) {
        openStatusModal(parseInt(sb.dataset.id, 10), sb.dataset.songfile, sb.dataset.nowplaying);
        return;
    }
    // 曲終了ボタン
    if (e.target.closest('.song-end-btn'))   { songEnd();   return; }
    // 曲開始ボタン
    if (e.target.closest('.song-start-btn')) { songStart(); return; }
});

// ---- カードハイライト共通処理 ----
function highlightCard(card, cssClass) {
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.remove(cssClass);
    void card.offsetWidth; // アニメーションリセット
    card.classList.add(cssClass);
    card.addEventListener('animationend', function () {
        card.classList.remove(cssClass);
    }, { once: true });
}

// ---- 再生中カードを探すセレクタ ----
var PLAYING_SEL = [
    '[data-nowplaying="再生中"]',
    '[data-nowplaying="2"]',
    '[data-nowplaying="再生開始待ち"]',
    '[data-nowplaying="6"]'
].join(',');

function scrollToPlayingCard() {
    var card = document.querySelector(PLAYING_SEL);
    if (!card) return false;
    highlightCard(card, 'highlight-playing');
    return true;
}

// ---- 新規追加アイテムへのスクロール ----
var SHOW_ID = (function () {
    var m = window.location.search.match(/[?&]showid=(\d+)/);
    return m ? parseInt(m[1], 10) : null;
})();

var afterRenderCallback = null;

function scrollToShowId() {
    var card = document.querySelector('.request-card[data-id="' + SHOW_ID + '"]');
    if (card) {
        highlightCard(card, 'highlight-new');
        return;
    }
    // ページ外の場合は全件ロードして探す
    currentLimit = 0;
    var countSel = document.getElementById('count-select');
    if (countSel) countSel.value = '0';
    fetch(buildUrl(0, 0))
        .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
        .then(function (data) {
            renderList(data.items, data.total, data.has_more, data);
            var c = document.querySelector('.request-card[data-id="' + SHOW_ID + '"]');
            if (c) highlightCard(c, 'highlight-new');
        })
        .catch(function (e) { console.error('scrollToShowId error:', e); });
}

function goToPlaying() {
    if (scrollToPlayingCard()) return;
    // 表示されていない場合は全件ロードして探す
    var prevLimit = currentLimit;
    currentLimit = 0;
    var countSel = document.getElementById('count-select');
    if (countSel) countSel.value = '0';
    fetch(buildUrl(0, 0))
        .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
        .then(function (data) {
            renderList(data.items, data.total, data.has_more, data);
            if (!scrollToPlayingCard()) {
                // 再生中なし：元の件数に戻す
                currentLimit = prevLimit;
                if (countSel) countSel.value = String(prevLimit);
                loadList(true);
            }
        })
        .catch(function (e) { console.error('goToPlaying error:', e); });
}

// ---- 曲名/ファイル名一括切り替えボタン ----
function updateTitleToggleBtn() {
    var btn = document.getElementById('title-toggle-btn');
    if (!btn) return;
    btn.textContent = (titleDisplayMode === 'songname') ? '&#128193; ファイル名' : '&#127925; 曲名';
    btn.innerHTML   = (titleDisplayMode === 'songname') ? '&#128193; ファイル名' : '&#127925; 曲名';
}
document.getElementById('title-toggle-btn').addEventListener('click', function () {
    titleDisplayMode = (titleDisplayMode === 'songname') ? 'filename' : 'songname';
    localStorage.setItem('ykari-title-display-mode', titleDisplayMode);
    updateTitleToggleBtn();
    document.querySelectorAll('.card-title').forEach(function (el) {
        el.dataset.showing = titleDisplayMode;
        el.textContent = (titleDisplayMode === 'filename') ? el.dataset.filename : el.dataset.songname;
    });
});
updateTitleToggleBtn();

// ---- 更新ボタン ----
document.getElementById('refresh-btn').addEventListener('click', function () { loadList(true); });

// ---- 再生中へボタン ----
document.getElementById('goto-playing-btn').addEventListener('click', goToPlaying);

// ---- もっと見るボタン ----
document.getElementById('load-more-btn').addEventListener('click', function () { loadList(false); });

// ---- 件数選択 ----
var countSelect = document.getElementById('count-select');
if (countSelect) {
    countSelect.addEventListener('change', function () {
        currentLimit = parseInt(this.value, 10);
        setCountCookie(currentLimit);
        loadList(true);
    });
}

// ---- 自動リロード ----
function shouldAutoReload() {
    var cb = document.getElementById('autoreload');
    return !cb || cb.checked;
}

function initAutoReload() {
    if (RELOAD_INTERVAL <= 0 && !USE_ACTIVE_RELOAD) return;

    if (USE_ACTIVE_RELOAD) {
        var ES = window.EventSource || window.MozEventSource;
        if (!ES) {
            // フォールバック：タイマー
            if (RELOAD_INTERVAL > 0) {
                setInterval(function () {
                    if (!isDragging && shouldAutoReload()) reloadCurrent();
                }, RELOAD_INTERVAL);
            }
            return;
        }
        var source  = new ES('requestlist_event.php?kind=requestlist');
        var lastkey = 0;
        source.onmessage = function (e) {
            if (e.data === 'Bye') { source.close(); return; }
            var nowkey = e.data;
            if (nowkey && nowkey !== 'None' && lastkey !== nowkey) {
                if (!isDragging && shouldAutoReload()) reloadCurrent();
                lastkey = nowkey;
            }
        };
    } else {
        setInterval(function () {
            if (!isDragging && shouldAutoReload()) reloadCurrent();
        }, RELOAD_INTERVAL);
    }
}

// auto-reload: 現在表示中の件数で再取得（追加ロード分も含む）
function reloadCurrent() {
    var limit = (currentLimit > 0 && shownCount > 0) ? shownCount : currentLimit;
    fetch(buildUrl(limit, 0))
        .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
        .then(function (data) { renderList(data.items, data.total, data.has_more, data); })
        .catch(function (e)   { console.error('reload error:', e); });
}

// ---- 初期化 ----
if (SHOW_ID) afterRenderCallback = scrollToShowId;
loadList(true);
initAutoReload();
</script>
</body>
</html>
