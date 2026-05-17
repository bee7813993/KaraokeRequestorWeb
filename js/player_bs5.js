'use strict';

// BS5プレイヤー用: fetch ベースのコマンド送信 + UI 更新

var _playerCtrlUrl = (function () {
    var url = location.href.split(/#/)[0];
    var base = url.substring(0, url.lastIndexOf('/'));
    return base + '/mpcctrl_bs5.php';
})();

var _foobarCtrlUrl = (function () {
    var url = location.href.split(/#/)[0];
    var base = url.substring(0, url.lastIndexOf('/'));
    return base + '/foobarctl_bs5.php';
})();

function _sendCmd(url) {
    return fetch(url).catch(function () { /* silent */ });
}

/* 曲終了 (DB更新 + UI更新) */
function cmd_songnext() {
    _sendCmd(_playerCtrlUrl + '?songnext=1').then(function () {
        setTimeout(function () {
            var pg = document.getElementById('proglessbase');
            if (pg) pg.dataset.stopped = '1';
            location.reload();
        }, 400);
    });
}

/* 再生開始 */
function cmd_songstart() {
    _sendCmd(_playerCtrlUrl + '?songstart=1').then(function () {
        setTimeout(progresstime_init, 600);
    });
}

/* ボリュームを再生開始時の初期値に戻す (MPC) */
function song_vreset() {
    _sendCmd(_playerCtrlUrl + '?cmd=reset_volume');
}

/* 字幕補正（明るさ/コントラスト/彩度） */
function _updateCompLevel(level) {
    var el = document.getElementById('comp-level');
    if (!el || typeof level === 'undefined') return;
    var sign = level > 0 ? '+' : '';
    el.textContent = sign + level;
}
function _compFetch(cmd) {
    return fetch(_playerCtrlUrl + '?cmd=' + cmd)
        .then(function (r) { return r.json(); })
        .then(function (d) { _updateCompLevel(d && d.level); return d; })
        .catch(function () { /* silent */ });
}
function comp_inc()   { _compFetch('comp_inc'); }
function comp_dec()   { _compFetch('comp_dec'); }
function comp_reset() { _compFetch('comp_reset'); }
function comp_apply() { _compFetch('comp_apply'); }

/* foobar 曲終了 */
function foobar_cmd_songnext() {
    _sendCmd(_foobarCtrlUrl + '?songnext=1').then(function () {
        setTimeout(function () { location.reload(); }, 400);
    });
}

/* foobar 再生開始 */
function foobar_cmd_songstart() {
    _sendCmd(_foobarCtrlUrl + '?songstart=1').then(function () {
        setTimeout(function () { location.reload(); }, 400);
    });
}

/* ボリュームスライダー */
function _syncVolSlider() {
    fetch(_playerCtrlUrl + '?cmd=get_volume')
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d || typeof d.volume === 'undefined') return;
            var slider  = document.getElementById('volume-slider');
            var display = document.getElementById('vol-display');
            if (slider)  slider.value = d.volume;
            if (display) display.textContent = d.volume;
        })
        .catch(function () {});
}

function _initVolumeSlider() {
    var slider  = document.getElementById('volume-slider');
    var display = document.getElementById('vol-display');
    if (!slider) return;

    _syncVolSlider();

    var _volTimer = null;
    slider.addEventListener('input', function () {
        var val = parseInt(slider.value, 10);
        if (display) display.textContent = val;
        clearTimeout(_volTimer);
        _volTimer = setTimeout(function () {
            fetch(_playerCtrlUrl + '?cmd=set_volume&val=' + val).catch(function () {});
        }, 150);
    });
}

function vol_btn_down() {
    if (typeof song_vdown === 'function') song_vdown();
    setTimeout(_syncVolSlider, 350);
}
function vol_btn_up() {
    if (typeof song_vup === 'function') song_vup();
    setTimeout(_syncVolSlider, 350);
}
function song_vreset_sync() {
    song_vreset();
    setTimeout(_syncVolSlider, 700);
}

/* 再生状態に応じて再生/一時停止ボタンのアイコンを切り替える */
var _iconPlay  = '<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>';
var _iconPause = '<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>';

function _updatePlayPauseBtn(stateNum) {
    var btn  = document.getElementById('btn-playpause');
    var icon = document.getElementById('icon-playpause');
    var lbl  = document.getElementById('lbl-playpause');
    if (!btn || !icon) return;

    // state: 2=再生中, 1=一時停止
    if (stateNum == 2) {
        icon.innerHTML = _iconPause;
        if (lbl) lbl.textContent = '一時停止';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('player-btn-playpause');
    } else {
        icon.innerHTML = _iconPlay;
        if (lbl) lbl.textContent = '再開';
        btn.classList.remove('player-btn-playpause');
        btn.classList.add('btn-outline-primary');
    }
}

function _updateStatusBadge(stateNum) {
    var badge = document.getElementById('player-status-badge');
    if (!badge) return;
    if (stateNum == 2) {
        badge.textContent = '再生中';
        badge.className = 'player-status-badge badge bg-success';
    } else if (stateNum == 1) {
        badge.textContent = '一時停止';
        badge.className = 'player-status-badge badge bg-warning text-dark';
    } else {
        badge.textContent = '停止中';
        badge.className = 'player-status-badge badge bg-secondary';
    }
}

/* Now Playing タイトル: 曲名 ⇄ ファイル名 のタップ切り替え
   "title" を表示中か "file" を表示中かを保持。曲が変わったら "title" にリセット */
var _titleMode = 'title';

function _escapeHTML(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function _renderPlayerTitle() {
    var disp = document.getElementById('player-title-display');
    if (!disp) return;
    var t = disp.dataset.songTitle || '';
    var f = disp.dataset.songFile  || '';
    if (!t) {
        disp.innerHTML =
            '<div class="player-title text-muted" id="player-title-text" style="opacity:.5;">曲が選択されていません</div>';
        return;
    }
    var hasAlt   = (f !== '' && f !== t);
    var showText = (_titleMode === 'file' && hasAlt) ? f : t;
    var isFile   = (_titleMode === 'file' && hasAlt);
    var attrs = hasAlt
        ? ' role="button" tabindex="0"'
          + ' onclick="player_title_toggle()"'
          + ' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();player_title_toggle();}"'
          + ' title="タップでファイル名表示を切り替え"'
          + ' aria-label="タップでファイル名表示を切り替え"'
        : '';
    var cls = 'player-title' + (hasAlt ? ' player-title-toggleable' : '') + (isFile ? ' player-title-as-file' : '');
    var icon = hasAlt ? '<span class="player-title-toggle-icon" aria-hidden="true">⇄</span>' : '';
    disp.innerHTML =
        '<div class="player-label">Now Playing</div>' +
        '<div class="' + cls + '" id="player-title-text"' + attrs + '>' +
        _escapeHTML(showText) + icon + '</div>';
}

function player_title_toggle() {
    _titleMode = (_titleMode === 'file') ? 'title' : 'file';
    _renderPlayerTitle();
}

/* progresstime_init のコールバックをフックして UI 同期 + 曲変化検出 */
var _lastPlayingTitle = null;

(function () {
    var _origInit = typeof progresstime_init !== 'undefined' ? progresstime_init : null;
    if (!_origInit) return;

    /* progresstime_init をオーバーライドして state 取得後に UI 更新 */
    window.progresstime_init = function (nowstate) {
        var url = location.href.split(/#/)[0];
        var base = url.substring(0, url.lastIndexOf('/'));
        var req = new XMLHttpRequest();
        req.open('GET', base + '/get_playingstatus_json.php', true);
        req.onreadystatechange = function () {
            if (req.readyState !== 4 || req.status !== 200) return;
            if (!req.responseText) return;
            try {
                var ps = JSON.parse(req.responseText);
                _updatePlayPauseBtn(ps.status);
                _updateStatusBadge(ps.status);

                /* Now Playing タイトルを BS5 構造で更新 (data 属性に反映してから再描画) */
                var titleDisplay = document.getElementById('player-title-display');
                if (titleDisplay) {
                    var pt = ps.playingtitle || '';
                    var pf = ps.playingfile  || '';
                    /* 曲が変わったら表示モードをタイトル側にリセット */
                    if (pt !== titleDisplay.dataset.songTitle) {
                        _titleMode = 'title';
                    }
                    titleDisplay.dataset.songTitle = pt;
                    titleDisplay.dataset.songFile  = pf;
                    _renderPlayerTitle();
                }

                /* 曲タイトル変化を検出 → 字幕補正を再適用
                   初回ロード時は _lastPlayingTitle が null なので発火しない */
                var title = ps.playingtitle || '';
                if (_lastPlayingTitle !== null
                    && title !== ''
                    && title !== _lastPlayingTitle) {
                    fetch(_playerCtrlUrl + '?cmd=comp_apply')
                        .then(function (r) { return r.json(); })
                        .then(function (d) { _updateCompLevel(d && d.level); })
                        .catch(function () {});
                }
                if (title !== '') _lastPlayingTitle = title;
            } catch (e) {}
        };
        req.send('');
        _origInit(nowstate);
    };
})();

window.addEventListener('DOMContentLoaded', function () {
    if (typeof progresstime_init === 'function') {
        progresstime_init();
    }
    if (typeof event_initial === 'function') {
        event_initial();
    }
    if (typeof event_initial_player === 'function') {
        event_initial_player();
    }
    _initVolumeSlider();
});
