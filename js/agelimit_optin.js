// 年齢制限のあるタイアップ曲を検索結果に含めるかのオプトイン制御。
// 状態は Cookie (YkariIncludeAgelimit=1) に保存され、その利用者の
// すべてのリスターDB検索 (BS5/BS3 共通) に効く。初回オン時のみ確認を出し、
// 承諾済みかどうかは localStorage に記録する (以降は確認なしで切り替え可能)。
(function () {
    var COOKIE_NAME = 'YkariIncludeAgelimit';
    var CONSENT_KEY = 'ykari-agelimit-consent';

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }
    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = name + '=' + encodeURIComponent(value)
            + '; expires=' + d.toUTCString() + '; path=/';
    }
    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }
    function hasConsent() {
        try { return localStorage.getItem(CONSENT_KEY) === '1'; } catch (e) { return false; }
    }
    function saveConsent() {
        try { localStorage.setItem(CONSENT_KEY, '1'); } catch (e) {}
    }

    function init() {
        var checks = document.querySelectorAll('.include-agelimit-check');
        if (!checks.length) return;
        var enabled = getCookie(COOKIE_NAME) === '1';
        Array.prototype.forEach.call(checks, function (check) {
            check.checked = enabled;
            check.addEventListener('change', function () {
                if (check.checked) {
                    if (!hasConsent()) {
                        var ok = window.confirm(
                            '年齢制限のある作品のタイアップ曲を検索結果に表示します。\n'
                            + '18歳以上の方のみ有効にしてください。よろしいですか？');
                        if (!ok) { check.checked = false; return; }
                        saveConsent();
                    }
                    setCookie(COOKIE_NAME, '1', 365);
                } else {
                    deleteCookie(COOKIE_NAME);
                }
                // 同一ページ内に複数のチェックがある場合は状態をそろえる
                Array.prototype.forEach.call(checks, function (c) { c.checked = check.checked; });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
