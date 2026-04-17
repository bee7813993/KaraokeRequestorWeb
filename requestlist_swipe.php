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
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"         rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
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
  background: #fff;
  -webkit-user-select: none;
  user-select: none;
}

/* スワイプで現れるアクションボタン群 */
.card-actions {
  position: absolute;
  top: 0; right: 0; bottom: 0;
  display: flex;
  z-index: 1;
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
.action-next   { background: #e67e22; }
.action-delete { background: #e74c3c; }
.action-icon   { font-size: 18px; }

/* カード本体（スワイプで左にスライド） */
.card-main {
  position: relative;
  z-index: 2;
  background: #fff;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  min-width: 0;
  transition: transform 0.2s ease;
  cursor: default;
}
.request-card.swipe-open .card-main {
  transform: translateX(-160px);
}

/* ドラッグハンドル */
.drag-handle {
  flex-shrink: 0;
  font-size: 20px;
  color: #bbb;
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
  font-size: 15px;
  font-weight: bold;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-meta {
  font-size: 12px;
  color: #888;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

/* 再生状況バッジ */
.card-status {
  flex-shrink: 0;
}

/* SortableJS */
.sortable-ghost {
  opacity: 0.4;
  background: #d9eaf7 !important;
}
.sortable-chosen {
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}

/* ヘッダ行 */
.list-header {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
}
.list-header h4 { margin: 0; }

#empty-msg {
  text-align: center;
  color: #aaa;
  padding: 30px 0;
  font-size: 15px;
}
</style>
</head>
<body>
<div class="container">
<?php
shownavigatioinbar();
showmode();

if (!empty($config_ini['noticeof_listpage'])) {
    echo '<div class="well">';
    echo str_replace('#yukarihost#', $_SERVER['HTTP_HOST'], urldecode($config_ini['noticeof_listpage']));
    echo '</div>';
}
?>

<?php if ($reloadInterval != 0): ?>
<div class="checkbox">
  <label class="checkbox-inline" data-toggle="tooltip" data-placement="top"
         title="コピペとかする時はチェックを外してください">
    <input type="checkbox" id="autoreload" checked> 自動リロード
  </label>
</div>
<?php endif; ?>

<hr>

<div class="list-header">
  <h4>現在の登録状況</h4>
  &nbsp;
  <button class="btn btn-default btn-xs" id="refresh-btn">更新</button>
</div>

<div id="request-list"></div>
<div id="empty-msg" style="display:none">リクエストはありません</div>

</div><!-- /.container -->

<script>
$(function () { $('[data-toggle="tooltip"]').tooltip(); });

// ---- 設定値（PHP から埋め込み） ----
var USE_ACTIVE_RELOAD = <?php echo $useActiveReload ? 'true' : 'false'; ?>;
var RELOAD_INTERVAL   = <?php echo $reloadInterval; ?> * 1000;

// ---- 状態 ----
var openCard   = null;
var isDragging = false;
var sortable   = null;

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
    '未再生':       'default',
    '再生中':       'success',
    '停止中':       'warning',
    '再生済':       'info',
    '再生済？':     'info',
    '再生開始待ち': 'warning',
    '変更中':       'danger'
};

function statusBadge(nowplaying) {
    if (!nowplaying) return '';
    var cls = STATUS_CLASS[nowplaying] || 'default';
    return '<span class="label label-' + cls + '">' + esc(nowplaying) + '</span>';
}

// ---- カード HTML 生成 ----
function createCardHTML(item) {
    var meta = esc(item.singer);
    if (item.comment) meta += ' &middot; ' + esc(item.comment);
    return [
        '<div class="request-card" data-id="' + item.id + '" data-songfile="' + esc(item.songfile) + '">',
        '  <div class="card-actions">',
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
        '  </div>',
        '  <div class="card-main">',
        '    <span class="drag-handle">&#8942;</span>',
        '    <div class="card-info">',
        '      <div class="card-title">' + esc(item.display_name) + '</div>',
        '      <div class="card-meta">'  + meta                   + '</div>',
        '    </div>',
        '    <div class="card-status">' + statusBadge(item.nowplaying) + '</div>',
        '  </div>',
        '</div>'
    ].join('\n');
}

// ---- データ読み込みと描画 ----
function loadList() {
    fetch('requestlist_swipe_json.php')
        .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
        .then(function (items) { renderList(items); })
        .catch(function (e)   { console.error('loadList error:', e); });
}

function renderList(items) {
    var container = document.getElementById('request-list');
    var emptyMsg  = document.getElementById('empty-msg');

    if (items.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = '';
        if (sortable) { sortable.destroy(); sortable = null; }
        return;
    }

    emptyMsg.style.display = 'none';
    container.innerHTML = items.map(createCardHTML).join('');
    initSortable();
    initSwipe();
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
    var main = card.querySelector('.card-main');
    if (main) main.style.transform = '';
    if (openCard === card) openCard = null;
}

function initSwipe() {
    document.querySelectorAll('.request-card').forEach(function (card) {
        var main = card.querySelector('.card-main');
        if (!main) return;

        var OPEN_WIDTH = 160; // px：アクション幅
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

// ---- イベント委譲（アクションボタン） ----
document.getElementById('request-list').addEventListener('click', function (e) {
    var btn = e.target.closest('.action-btn');
    if (!btn) return;
    var id       = parseInt(btn.dataset.id, 10);
    var songfile = btn.dataset.songfile;
    if (btn.classList.contains('action-next'))   playNext(id, songfile);
    if (btn.classList.contains('action-delete')) deleteItem(id, songfile);
});

// ---- 更新ボタン ----
document.getElementById('refresh-btn').addEventListener('click', loadList);

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
                    if (!isDragging && shouldAutoReload()) loadList();
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
                if (!isDragging && shouldAutoReload()) loadList();
                lastkey = nowkey;
            }
        };
    } else {
        setInterval(function () {
            if (!isDragging && shouldAutoReload()) loadList();
        }, RELOAD_INTERVAL);
    }
}

// ---- 初期化 ----
loadList();
initAutoReload();
</script>
</body>
</html>
