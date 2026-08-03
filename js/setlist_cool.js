(function () {
  'use strict';

  var state = {
    data: null,
    activeTab: 'cool',
    filter: '',
    category: '',
    sort: 'count'
  };

  var coolPanel = document.getElementById('setlistCoolPanel');
  var rankingPanel = document.getElementById('setlistRankingPanel');
  var statusEl = document.getElementById('setlistStatus');
  var filterEl = document.getElementById('setlistFilter');
  var categorySelect = document.getElementById('setlistCoolSelect');
  var sortSelect = document.getElementById('setlistSortSelect');
  var searchForm = document.getElementById('setlistSearchForm');
  var searchTarget = document.getElementById('setlistSearchTarget');
  var endpoint = coolPanel ? coolPanel.getAttribute('data-endpoint') : 'setlist_stats_json.php';
  var selectIdInput = searchForm ? searchForm.querySelector('input[name="selectid"]') : null;

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch];
    });
  }

  function query(params) {
    var out = [];
    Object.keys(params).forEach(function (key) {
      if (params[key] !== '') out.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
    });
    return out.join('&');
  }

  function cleanSearchKeyword(value) {
    if (!value) return '';
    return String(value)
      .replace(/\([^)]*\)/g, ' ')
      .replace(/（[^）]*）/g, ' ')
      .replace(/[～〜~／\/]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function searchUrl(anime, song) {
    var word = cleanSearchKeyword((anime ? anime + ' ' : '') + (song || ''));
    var params = {};
    if (selectIdInput && selectIdInput.value !== '') params.selectid = selectIdInput.value;
    if (state.data && state.data.search_backend === 'everything') {
      params.searchword = word;
      return 'search_bs5.php?' + query(params);
    }
    params.anyword = word;
    return 'search_listerdb_filelist.php?' + query(params);
  }

  function matchesText(text) {
    if (!state.filter) return true;
    return String(text || '').toLowerCase().indexOf(state.filter.toLowerCase()) !== -1;
  }

  function typeKey(type) {
    var text = String(type || '').toUpperCase();
    if (text.indexOf('OP') !== -1) return 'op';
    if (text.indexOf('ED') !== -1) return 'ed';
    if (text.indexOf('IN') !== -1 || text.indexOf('IM') !== -1) return 'in';
    if (text.indexOf('TM') !== -1) return 'tm';
    return 'none';
  }

  function typePill(type, count) {
    var key = typeKey(type);
    var suffix = typeof count === 'number' ? '<b>' + esc(count) + '</b>' : '';
    return '<span class="setlist-type-pill setlist-type-' + key + '">' + esc(type || '-') + suffix + '</span>';
  }

  function typeChip(type) {
    var key = typeKey(type);
    return '<span class="setlist-type-chip setlist-type-' + key + '">' + esc(type || '-') + '</span>';
  }

  function metric(label, value, tone) {
    return '<span class="setlist-metric setlist-metric-' + esc(tone || 'song') + '">'
      + '<span class="setlist-metric-dot" aria-hidden="true"></span>'
      + '<span><b>' + esc(value || 0) + '</b><small>' + esc(label) + '</small></span>'
      + '</span>';
  }

  function categories() {
    if (state.data && Array.isArray(state.data.categories) && state.data.categories.length) {
      return state.data.categories;
    }
    if (state.data && state.data.cool_data) {
      return Object.keys(state.data.cool_data);
    }
    return [];
  }

  function currentCategory() {
    var cats = categories();
    if (state.category && cats.indexOf(state.category) !== -1) return state.category;
    return cats[0] || '';
  }

  function currentCoolWorks() {
    var cat = currentCategory();
    var group = state.data && state.data.cool_data ? state.data.cool_data[cat] : null;
    return group && Array.isArray(group.works) ? group.works.slice() : [];
  }

  function rankingRows(category, mode) {
    var rows = state.data && state.data.rank_data && Array.isArray(state.data.rank_data[category])
      ? state.data.rank_data[category].slice()
      : [];
    rows.sort(function (a, b) {
      var av = mode === 'user' ? (a.user_count || 0) : (a.count || 0);
      var bv = mode === 'user' ? (b.user_count || 0) : (b.count || 0);
      if (bv !== av) return bv - av;
      return String(a.song || '').localeCompare(String(b.song || ''), 'ja');
    });

    var ranked = [];
    var prev = null;
    var rank = 0;
    rows.forEach(function (row, idx) {
      var value = mode === 'user' ? (row.user_count || 0) : (row.count || 0);
      if (prev === null || value !== prev) rank = idx + 1;
      prev = value;
      if (rank <= 20) {
        row._rank = rank;
        ranked.push(row);
      }
    });
    return ranked;
  }

  function sortWorks(rows) {
    rows.sort(function (a, b) {
      if (state.sort === 'name') return String(a.anime || '').localeCompare(String(b.anime || ''), 'ja');
      if (state.sort === 'user') return (b.total_user || 0) - (a.total_user || 0);
      return (b.total_count || 0) - (a.total_count || 0);
    });
    return rows;
  }

  function renderCool() {
    if (!coolPanel) return;
    var rows = sortWorks(currentCoolWorks()).filter(function (item) {
      var songs = Array.isArray(item.songs) ? item.songs : [];
      var haystack = [item.anime, item.op_n, item.ed_n, item.in_n].concat(songs.map(function (song) {
        return [song.song, song.artist, song.type].join(' ');
      })).join(' ');
      return matchesText(haystack);
    });

    if (!rows.length) {
      coolPanel.innerHTML = '<div class="setlist-empty">表示できるクール集計がありません。</div>';
      return;
    }

    coolPanel.innerHTML = '<div class="setlist-grid">' + rows.map(function (item, idx) {
      var songs = Array.isArray(item.songs) ? item.songs : [];
      var typeSummary = [
        item.op_n ? typePill('OP', item.op_n) : '',
        item.ed_n ? typePill('ED', item.ed_n) : '',
        item.in_n ? typePill('IN', item.in_n) : ''
      ].join('');
      var songHtml = songs.map(function (song) {
        return '<a class="setlist-song-row text-decoration-none" href="' + esc(searchUrl(item.anime, song.song)) + '">'
          + typeChip(song.type)
          + '<span class="setlist-song-info">'
          + '<span class="setlist-song-name">' + esc(song.song || '曲名未設定') + '</span>'
          + '<span class="setlist-song-artist">' + esc(song.artist || '') + '</span>'
          + '</span>'
          + '<span class="setlist-song-metrics">'
          + metric('歌唱', song.count, 'song')
          + metric('人数', song.user_count, 'user')
          + '</span>'
          + '</a>';
      }).join('');
      return '<article class="setlist-card is-collapsed">'
        + '<button type="button" class="setlist-card-head" aria-expanded="false">'
        + '<span class="setlist-badge">' + esc(idx + 1) + '</span>'
        + '<span class="setlist-card-main">'
        + '<span class="setlist-title">' + esc(item.anime || '作品未設定') + '</span>'
        + '<span class="setlist-sub">' + esc(currentCategory()) + '</span>'
        + '<span class="setlist-card-summary">'
        + '<span class="setlist-type-line">' + typeSummary + '</span>'
        + '<span class="setlist-metrics setlist-card-metrics">'
        + metric('歌唱', item.total_count, 'song')
        + metric('人数', item.total_user, 'user')
        + metric('曲', songs.length, 'work')
        + '</span>'
        + '</span>'
        + '</span>'
        + '<span class="setlist-chev" aria-hidden="true"></span>'
        + '</button>'
        + (songHtml ? '<div class="setlist-song-list">' + songHtml + '</div>' : '')
        + '</article>';
    }).join('') + '</div>';
  }

  function renderRanking() {
    if (!rankingPanel) return;
    var cat = currentCategory();
    var mode = state.sort === 'user' ? 'user' : 'count';
    var rows = rankingRows(cat, mode).filter(function (item) {
      return matchesText([item.anime, item.song, item.artist, item.type].join(' '));
    });

    if (!rows.length) {
      rankingPanel.innerHTML = '<div class="setlist-empty">表示できるランキングがありません。</div>';
      return;
    }

    rankingPanel.innerHTML = rows.map(function (item) {
      return '<a class="setlist-rank-row text-decoration-none" href="' + esc(searchUrl(item.anime, item.song)) + '">'
        + '<span class="setlist-badge">' + esc(item._rank || '') + '</span>'
        + '<span class="setlist-rank-info">'
        + '<span class="setlist-title">' + esc(item.song || '曲名未設定') + '</span>'
        + '<span class="setlist-sub">' + esc(item.anime || '') + (item.artist ? ' / ' + esc(item.artist) : '') + '</span>'
        + '<span class="setlist-type-line">' + typePill(item.type) + '</span>'
        + '</span>'
        + '<span class="setlist-metrics">'
        + metric('歌唱', item.count, 'song')
        + metric('人数', item.user_count, 'user')
        + '</span>'
        + '</a>';
    }).join('');
  }

  function fillCategorySelect() {
    if (!categorySelect) return;
    var values = categories();
    categorySelect.innerHTML = values.map(function (value) {
      return '<option value="' + esc(value) + '">' + esc(value) + '</option>';
    }).join('');
    categorySelect.value = currentCategory();
  }

  function fillSortSelect() {
    if (!sortSelect) return;
    var options = state.activeTab === 'ranking'
      ? [['count', '歌唱数順'], ['user', '人数順']]
      : [['count', '歌唱数順'], ['user', '人数順'], ['name', '作品名順']];
    if (!options.some(function (row) { return row[0] === state.sort; })) state.sort = options[0][0];
    sortSelect.innerHTML = options.map(function (row) {
      return '<option value="' + row[0] + '">' + esc(row[1]) + '</option>';
    }).join('');
    sortSelect.value = state.sort;
  }

  function render() {
    fillSortSelect();
    renderCool();
    renderRanking();
    if (statusEl && state.data) {
      statusEl.textContent = state.data.updated_at || '';
    }
  }

  function setActiveTab(name) {
    state.activeTab = name;
    document.querySelectorAll('.setlist-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-setlist-tab') === name);
    });
    coolPanel.classList.toggle('active', name === 'cool');
    rankingPanel.classList.toggle('active', name === 'ranking');
    render();
  }

  function load(refresh) {
    if (statusEl) statusEl.textContent = '読み込み中...';
    fetch(endpoint + (refresh ? '?refresh=1' : ''), {credentials: 'same-origin'})
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        state.data = data || {};
        fillCategorySelect();
        render();
      })
      .catch(function () {
        if (statusEl) statusEl.textContent = '集計データを読み込めませんでした。';
        coolPanel.innerHTML = '<div class="setlist-empty">集計データを読み込めませんでした。</div>';
      });
  }

  document.querySelectorAll('.setlist-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setActiveTab(btn.getAttribute('data-setlist-tab') || 'cool');
    });
  });

  document.addEventListener('click', function (event) {
    var head = event.target.closest('.setlist-card-head');
    if (!head) return;
    var card = head.closest('.setlist-card');
    var collapsed = !card.classList.contains('is-collapsed');
    card.classList.toggle('is-collapsed', collapsed);
    head.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  });

  if (filterEl) {
    filterEl.addEventListener('input', function () {
      state.filter = filterEl.value.trim();
      render();
    });
  }

  if (categorySelect) {
    categorySelect.addEventListener('change', function () {
      state.category = categorySelect.value;
      render();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      state.sort = sortSelect.value;
      render();
    });
  }

  if (searchForm && searchTarget) {
    searchForm.addEventListener('submit', function () {
      var input = document.getElementById('setlistSearchWord');
      var name = searchTarget.value || 'anyword';
      if (input) {
        input.value = cleanSearchKeyword(input.value);
        input.setAttribute('name', name);
      }
      if (state.data && state.data.search_backend === 'everything') {
        if (input) input.setAttribute('name', 'searchword');
        searchForm.setAttribute('action', 'search_bs5.php');
        return;
      }
      if (name === 'program_name') {
        searchForm.setAttribute('action', 'search_listerdb_songlist_bs5.php');
      } else {
        searchForm.setAttribute('action', 'search_listerdb_filelist.php');
      }
    });
  }

  load(false);
})();
