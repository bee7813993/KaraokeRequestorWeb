'use strict';

/* ライブダッシュボード専用JS
   mpcctrl.js / player_bs5.js とは独立して動作する */

(function () {
  var _base = (function () {
    var u = location.href.split(/#/)[0];
    return u.substring(0, u.lastIndexOf('/'));
  })();

  var _ctrlUrl  = _base + '/mpcctrl_bs5.php';
  var _statUrl  = _base + '/get_playingstatus_json.php';
  var _queueUrl = _base + '/get_requestqueue_json.php';

  /* =====================
     内部状態
     ===================== */
  var _titleMode = 'title'; // 'title' | 'file'
  var _lastTitle = null;
  var _lastQueueHash = '';
  var _statTimer  = null;
  var _queueTimer = null;
  var _progressTimer = null;
  var _playtime  = 0;
  var _totaltime = 0;
  var _isPlaying = false;

  /* =====================
     ユーティリティ
     ===================== */
  function _esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function _fmt(sec) {
    sec = Math.max(0, Math.floor(sec));
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec % 3600) / 60);
    var s = sec % 60;
    var mm = (m < 10 ? '0' : '') + m;
    var ss = (s < 10 ? '0' : '') + s;
    return h > 0 ? h + ':' + mm + ':' + ss : mm + ':' + ss;
  }
  function _el(id) { return document.getElementById(id); }
  function _cmd(url) { return fetch(url).catch(function () {}); }

  /* =====================
     プレイヤーステータス更新
     ===================== */
  function _updateStatus(ps) {
    var state = ps.status || 0; // 0=停止, 1=一時停止, 2=再生中
    _isPlaying = (state === 2);
    _playtime  = parseFloat(ps.playtime  || 0);
    _totaltime = parseFloat(ps.totaltime || 0);

    /* ドット & バッジ */
    var dot   = _el('db-pulse-dot');
    var badge = _el('db-status-badge');
    if (dot) {
      dot.className = 'db-pulse-dot' + (state === 2 ? ' is-playing' : state === 1 ? ' is-paused' : '');
    }
    if (badge) {
      var txt = state === 2 ? '再生中' : (state === 1 ? '一時停止' : '停止中');
      var cls = 'db-status-badge' + (state === 2 ? ' is-playing' : state === 1 ? ' is-paused' : '');
      badge.textContent = txt;
      badge.className = cls;
    }

    /* 再生/一時停止ボタン */
    var ppBtn  = _el('db-btn-playpause');
    var ppIcon = _el('db-icon-playpause');
    var ppLbl  = _el('db-lbl-playpause');
    if (ppBtn) {
      if (state === 2) {
        ppBtn.classList.add('is-playing');
        if (ppIcon) ppIcon.innerHTML = '<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>';
        if (ppLbl) ppLbl.textContent = '一時停止';
      } else {
        ppBtn.classList.remove('is-playing');
        if (ppIcon) ppIcon.innerHTML = '<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>';
        if (ppLbl) ppLbl.textContent = '再開';
      }
    }

    /* タイトル更新 */
    var titleDisp = _el('db-title-display');
    if (titleDisp) {
      var pt = ps.playingtitle || '';
      var pf = ps.playingfile  || '';
      if (pt !== titleDisp.dataset.songTitle) { _titleMode = 'title'; }
      titleDisp.dataset.songTitle = pt;
      titleDisp.dataset.songFile  = pf;
      _renderTitle(titleDisp);
    }

    /* 曲名 → 曲変化で字幕補正を再適用 */
    var title = ps.playingtitle || '';
    if (_lastTitle !== null && title !== '' && title !== _lastTitle) {
      _cmd(_ctrlUrl + '?cmd=comp_apply')
        .then(function (r) { return r && r.json(); })
        .then(function (d) { _updateCompLevel(d && d.level); })
        .catch(function () {});
    }
    if (title !== '') _lastTitle = title;

    /* プログレスバー更新 */
    _updateProgress();
  }

  function _updateProgress() {
    var pct = (_totaltime > 0) ? Math.min(100, _playtime / _totaltime * 100) : 0;
    var bar = _el('db-progress-bar');
    if (bar) bar.style.width = pct + '%';
    var tCur = _el('db-time-cur');
    var tTot = _el('db-time-total');
    if (tCur) tCur.textContent = _fmt(_playtime);
    if (tTot) tTot.textContent = _fmt(_totaltime);
  }

  /* 再生中に1秒ずつ経過表示 */
  function _startProgressTick() {
    _stopProgressTick();
    if (!_isPlaying) return;
    _progressTimer = setInterval(function () {
      if (!_isPlaying) { _stopProgressTick(); return; }
      _playtime = Math.min(_playtime + 1, _totaltime);
      _updateProgress();
    }, 1000);
  }
  function _stopProgressTick() {
    if (_progressTimer) { clearInterval(_progressTimer); _progressTimer = null; }
  }

  /* =====================
     タイトル描画 (曲名⇄ファイル名)
     ===================== */
  function _renderTitle(el) {
    var t = el.dataset.songTitle || '';
    var f = el.dataset.songFile  || '';
    var titleEl = _el('db-song-title');
    var singerEl = _el('db-song-singer-now'); /* 再生中のnow playingの歌手欄はキューが更新する */
    if (!titleEl) return;
    if (!t) {
      titleEl.className = 'db-song-title is-empty';
      titleEl.textContent = '曲が選択されていません';
      return;
    }
    var hasAlt  = (f !== '' && f !== t);
    var showTxt = (_titleMode === 'file' && hasAlt) ? f : t;
    titleEl.className = 'db-song-title' + (hasAlt ? ' db-title-toggleable' : '');
    if (hasAlt) {
      titleEl.style.cursor = 'pointer';
      titleEl.onclick = _toggleTitleMode;
      titleEl.title   = 'タップでファイル名表示を切り替え';
    } else {
      titleEl.onclick = null;
      titleEl.style.cursor = '';
      titleEl.title = '';
    }
    titleEl.textContent = showTxt;
    if (hasAlt) {
      var icon = document.createElement('span');
      icon.className = 'ms-1';
      icon.style.cssText = 'font-size:.7em;opacity:.4;vertical-align:.1em;';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = '⇄';
      titleEl.appendChild(icon);
    }
  }

  function _toggleTitleMode() {
    _titleMode = (_titleMode === 'file') ? 'title' : 'file';
    var el = _el('db-title-display');
    if (el) _renderTitle(el);
  }

  /* =====================
     ポーリング: プレイヤーステータス (2秒)
     ===================== */
  function _pollStatus() {
    fetch(_statUrl)
      .then(function (r) { return r.json(); })
      .then(function (ps) {
        _updateStatus(ps);
        _stopProgressTick();
        _startProgressTick();
      })
      .catch(function () {})
      .finally(function () {
        _statTimer = setTimeout(_pollStatus, 2000);
      });
  }

  /* =====================
     ポーリング: キュー (4秒)
     ===================== */
  function _pollQueue() {
    fetch(_queueUrl)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var hash = JSON.stringify(data);
        if (hash !== _lastQueueHash) {
          _lastQueueHash = hash;
          _renderQueue(data);
        }
      })
      .catch(function () {})
      .finally(function () {
        _queueTimer = setTimeout(_pollQueue, 4000);
      });
  }

  /* =====================
     キュー描画
     ===================== */
  function _renderQueue(data) {
    var list  = _el('db-queue-list');
    var count = _el('db-queue-count');
    if (!list) return;

    var playing = data.playing || null;
    var queue   = data.queue   || [];
    var total   = queue.length + (playing ? 0 : 0); /* 再生中は含めない */

    if (count) count.textContent = queue.length + '曲待機中';

    var html = '';

    /* 再生中 */
    if (playing) {
      html += '<div class="db-queue-item is-playing">';
      html += '<div class="db-queue-num">▶</div>';
      html += '<div class="db-queue-info">';
      html += '<div class="db-queue-song">' + _esc(playing.title) + '</div>';
      var meta = [];
      if (playing.singer) meta.push('<span class="db-queue-singer">' + _esc(playing.singer) + '</span>');
      if (playing.kind)   meta.push('<span class="db-queue-kind">'   + _esc(playing.kind)   + '</span>');
      if (meta.length) html += '<div class="db-queue-meta">' + meta.join('') + '</div>';
      html += '</div></div>';
    } else {
      html += '<div class="db-queue-item is-playing">';
      html += '<div class="db-queue-num">▶</div>';
      html += '<div class="db-queue-info"><div class="db-queue-song" style="color:#3d444d;font-style:italic;">再生中の曲なし</div></div>';
      html += '</div>';
    }

    /* 待機キュー */
    if (queue.length === 0) {
      html += '<div class="db-queue-empty">待機中の曲はありません</div>';
    } else {
      for (var i = 0; i < queue.length; i++) {
        var item = queue[i];
        html += '<div class="db-queue-item">';
        html += '<div class="db-queue-num">' + (i + 1) + '</div>';
        html += '<div class="db-queue-info">';
        html += '<div class="db-queue-song">' + _esc(item.title) + '</div>';
        var m = [];
        if (item.singer) m.push('<span class="db-queue-singer">' + _esc(item.singer) + '</span>');
        if (item.kind)   m.push('<span class="db-queue-kind">'   + _esc(item.kind)   + '</span>');
        if (m.length) html += '<div class="db-queue-meta">' + m.join('') + '</div>';
        html += '</div></div>';
      }
    }

    list.innerHTML = html;
  }

  /* =====================
     コントロール関数
     ===================== */
  window.db_cmd_songnext = function () {
    _cmd(_ctrlUrl + '?songnext=1').then(function () {
      setTimeout(function () { _pollStatus(); _pollQueue(); }, 500);
    });
  };

  window.db_cmd_songstart = function () {
    _cmd(_ctrlUrl + '?songstart=1').then(function () {
      setTimeout(_pollStatus, 600);
    });
  };

  window.db_cmd_pause = function () {
    /* MPC コマンド 887 = 再生/一時停止切り替え */
    _cmd(_ctrlUrl + '?cmd=887').then(function () {
      setTimeout(_pollStatus, 300);
    });
  };

  window.db_startfirst = function () {
    _cmd(_ctrlUrl + '?cmd=start_first').then(function () {
      setTimeout(_pollStatus, 300);
    });
  };

  window.db_fadeout = function () {
    _cmd(_ctrlUrl + '?fadeout=1');
  };

  /* シーク */
  window.db_seek = function (cmd) {
    _cmd(_ctrlUrl + '?cmd=' + cmd).then(function () {
      setTimeout(_pollStatus, 400);
    });
  };

  /* ボリューム */
  var _volTimer = null;
  function _syncVolSlider() {
    fetch(_ctrlUrl + '?cmd=get_volume')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || typeof d.volume === 'undefined') return;
        var sl = _el('db-vol-slider');
        var dp = _el('db-vol-display');
        if (sl) sl.value = d.volume;
        if (dp) dp.textContent = d.volume;
      })
      .catch(function () {});
  }

  window.db_vol_up = function () {
    _cmd(_ctrlUrl + '?cmd=907').then(function () { setTimeout(_syncVolSlider, 350); });
  };
  window.db_vol_down = function () {
    _cmd(_ctrlUrl + '?cmd=908').then(function () { setTimeout(_syncVolSlider, 350); });
  };
  window.db_vol_reset = function () {
    _cmd(_ctrlUrl + '?cmd=reset_volume').then(function () { setTimeout(_syncVolSlider, 700); });
  };

  function _initVolSlider() {
    var sl = _el('db-vol-slider');
    var dp = _el('db-vol-display');
    if (!sl) return;
    _syncVolSlider();
    sl.addEventListener('input', function () {
      var val = parseInt(sl.value, 10);
      if (dp) dp.textContent = val;
      clearTimeout(_volTimer);
      _volTimer = setTimeout(function () {
        _cmd(_ctrlUrl + '?cmd=set_volume&val=' + val);
      }, 150);
    });
  }

  /* 字幕補正 */
  function _updateCompLevel(level) {
    var el = _el('db-comp-level');
    if (!el || typeof level === 'undefined') return;
    el.textContent = (level > 0 ? '+' : '') + level;
  }
  function _compFetch(cmd) {
    return fetch(_ctrlUrl + '?cmd=' + cmd)
      .then(function (r) { return r.json(); })
      .then(function (d) { _updateCompLevel(d && d.level); return d; })
      .catch(function () {});
  }
  window.db_comp_inc   = function () { _compFetch('comp_inc');   };
  window.db_comp_dec   = function () { _compFetch('comp_dec');   };
  window.db_comp_reset = function () { _compFetch('comp_reset'); };

  /* キーチェンジ */
  window.db_keychange = function (cmd) {
    fetch(_base + '/mpcctrl_bs5.php?key=' + encodeURIComponent(cmd))
      .catch(function () {});
  };

  /* 任意コード */
  window.db_mpccmd = function () {
    var code = (_el('db-mpccode') || {}).value;
    if (!code) return;
    _cmd(_ctrlUrl + '?cmd=' + encodeURIComponent(code));
  };

  /* =====================
     初期化
     ===================== */
  window.addEventListener('DOMContentLoaded', function () {
    _initVolSlider();
    /* 補正レベルを初期取得 */
    fetch(_ctrlUrl + '?cmd=comp_get')
      .then(function (r) { return r.json(); })
      .then(function (d) { _updateCompLevel(d && d.level); })
      .catch(function () {});
    /* ポーリング開始 */
    _pollStatus();
    _pollQueue();
  });

})();
