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

/* progresstime_init のコールバックをフックして UI 同期 */
(function () {
    var _origInit = typeof progresstime_init !== 'undefined' ? progresstime_init : null;
    if (!_origInit) return;

    /* オリジナルの progresstime_autoplay をラップして状態バッジを更新 */
    var _origAutoplay = typeof progresstime_autoplay !== 'undefined' ? progresstime_autoplay : null;

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
});
