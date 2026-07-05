'use strict';
/**
 * /v2/ 共通処理 — API クライアント + ナビバー
 *
 * /v2/ は「サーバーサイドレンダリングを一切使わない純粋な API クライアント」。
 * ここで動く機能 = モバイルアプリから実現できる機能、の検証を兼ねる。
 * 使用エンドポイント: /api/* と既存 JSON (requestlist_swipe_json.php,
 * get_playingstatus_json.php, exec.php[XHR], change.php?format=json)
 */

var V2_BASE = '..';

/* /api/ エンベロープ ({ok, data|error}) を解釈して data を返す */
function apiCall(path, params) {
    var url = V2_BASE + '/' + path;
    if (params) {
        var q = new URLSearchParams(params).toString();
        url += (url.indexOf('?') === -1 ? '?' : '&') + q;
    }
    return fetch(url).then(function (r) {
        return r.json().then(function (j) {
            if (j && j.ok) return j.data;
            var msg = (j && j.error) ? j.error : ('HTTP ' + r.status);
            throw new Error(msg);
        });
    });
}

/* エンベロープなしの既存 JSON エンドポイント用 */
function jsonCall(path, params) {
    var url = V2_BASE + '/' + path;
    if (params) {
        var q = new URLSearchParams(params).toString();
        url += (url.indexOf('?') === -1 ? '?' : '&') + q;
    }
    return fetch(url).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
    }).then(function (t) {
        if (t.trim() === '') return null; // get_playingstatus_json は停止中に空を返す
        return JSON.parse(t);
    });
}

/* exec.php へのリクエスト投稿 (XHR モード → {"newid":N}) */
function postRequest(fields) {
    var body = new URLSearchParams(fields);
    return fetch(V2_BASE + '/exec.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
    }).then(function (r) { return r.text(); }).then(function (t) {
        return JSON.parse(t.trim());
    });
}

/* ナビバー描画 (BS5 構成に合わせた 56px navbar) */
function renderNavbar(active) {
    var pages = [
        { id: 'list',   href: 'index.html',  label: '予約一覧' },
        { id: 'search', href: 'search.html', label: '検索' },
        { id: 'player', href: 'player.html', label: 'Player' }
    ];
    var links = pages.map(function (p) {
        var cls = 'nav-link' + (p.id === active ? ' active' : '');
        return '<li class="nav-item"><a class="' + cls + '" href="' + p.href + '">' + p.label + '</a></li>';
    }).join('');
    var html =
        '<nav class="navbar navbar-expand navbar-dark bg-dark sticky-top" style="min-height:56px">' +
        '<div class="container-fluid">' +
        '<span class="navbar-brand">ゆかり <span class="badge bg-info">API v2</span></span>' +
        '<ul class="navbar-nav me-auto">' + links + '</ul>' +
        '</div></nav>';
    document.body.insertAdjacentHTML('afterbegin', html);
}

/* 画面上部への結果トースト表示 */
function showToast(message, isError) {
    var el = document.getElementById('v2toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'v2toast';
        el.style.cssText = 'position:fixed;top:64px;right:12px;z-index:2000;max-width:320px;';
        document.body.appendChild(el);
    }
    var item = document.createElement('div');
    item.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success') + ' py-2 px-3 mb-2';
    item.textContent = message;
    el.appendChild(item);
    setTimeout(function () { item.remove(); }, isError ? 6000 : 2500);
}

function esc(s) {
    var d = document.createElement('div');
    d.textContent = (s === null || s === undefined) ? '' : String(s);
    return d.innerHTML;
}
