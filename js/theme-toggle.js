/* テーマ・文字サイズ切り替えスクリプト */
(function () {
  if (window.__ykThemeToggle) return;
  window.__ykThemeToggle = true;

  var THEME_KEY = 'ykari-theme';
  var FONT_KEY  = 'ykari-fontsize';
  var TRANSITION_MS = 350;
  var _transTimer = null;

  function load(key, def) {
    try { return localStorage.getItem(key) || def; } catch (e) { return def; }
  }
  function save(key, val) {
    try { localStorage.setItem(key, val); } catch (e) {}
  }

  var theme    = load(THEME_KEY, 'light');
  var fontsize = load(FONT_KEY,  'normal');

  document.documentElement.setAttribute('data-theme',    theme);
  document.documentElement.setAttribute('data-fontsize', fontsize);

  // テーマ切り替え時に一時的に transition クラスを付与してアニメーションを有効化
  function withTransition(fn) {
    if (_transTimer) clearTimeout(_transTimer);
    document.documentElement.classList.add('theme-changing');
    fn();
    _transTimer = setTimeout(function () {
      document.documentElement.classList.remove('theme-changing');
    }, TRANSITION_MS + 50);
  }

  var MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/></svg>';
  var SUN  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707z"/></svg>';

  function syncThemeBtn(btn) {
    if (!btn) return;
    var isDark = (theme === 'dark');
    btn.innerHTML = isDark ? SUN : MOON;
    btn.title     = isDark ? 'ライトモードに切り替え' : 'ダークモードに切り替え';
    btn.setAttribute('aria-label',   btn.title);
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }

  function syncFontBtn(btn) {
    if (!btn) return;
    var isLarge = (fontsize === 'large');
    btn.classList.toggle('active', isLarge);
    btn.title = isLarge ? '標準文字サイズに戻す' : '文字を大きくする';
    btn.setAttribute('aria-label',   btn.title);
    btn.setAttribute('aria-pressed', isLarge ? 'true' : 'false');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var themeBtn    = document.getElementById('yk-theme-btn');
    var fontsizeBtn = document.getElementById('yk-fontsize-btn');

    syncThemeBtn(themeBtn);
    syncFontBtn(fontsizeBtn);

    if (themeBtn) {
      themeBtn.addEventListener('click', function () {
        theme = (theme === 'dark') ? 'light' : 'dark';
        save(THEME_KEY, theme);
        withTransition(function () {
          document.documentElement.setAttribute('data-theme', theme);
        });
        syncThemeBtn(themeBtn);
      });
    }

    if (fontsizeBtn) {
      fontsizeBtn.addEventListener('click', function () {
        fontsize = (fontsize === 'large') ? 'normal' : 'large';
        save(FONT_KEY, fontsize);
        document.documentElement.setAttribute('data-fontsize', fontsize);
        syncFontBtn(fontsizeBtn);
      });
    }
  });
})();
