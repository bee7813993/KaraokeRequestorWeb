<?php
/**
 * karaoke_setlist の公開viewerに埋め込まれている集計済みデータを、
 * ゆかり表示用のJSONとして中継する。
 */

define('SETLIST_VIEWER_URL', 'https://hachi515.github.io/karaoke_setlist/viewer.html');

function setlist_stats_default_result($source_url)
{
    return [
        'updated_at' => date('c'),
        'source_url' => $source_url,
        'search_backend' => setlist_stats_search_backend(),
        'categories' => [],
        'cool_data' => [],
        'rank_data' => [],
    ];
}

function setlist_stats_search_backend()
{
    global $config_ini;

    $backend = 'listerdb';
    if (!empty($config_ini['setlist_search_backend'])) {
        $backend = urldecode($config_ini['setlist_search_backend']);
    }
    return $backend === 'everything' ? 'everything' : 'listerdb';
}

function setlist_stats_apply_runtime_config($data)
{
    if (is_array($data)) {
        $data['search_backend'] = setlist_stats_search_backend();
    }
    return $data;
}

function setlist_stats_cache_file()
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'karaoke_setlist_stats.json';
}

function setlist_stats_load_cache($ttl)
{
    $file = setlist_stats_cache_file();
    if (!is_file($file)) return null;
    if ($ttl > 0 && (time() - filemtime($file)) > $ttl) return null;
    $json = @file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function setlist_stats_save_cache($data)
{
    $file = setlist_stats_cache_file();
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!is_dir($dir)) return;
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function setlist_stats_extract_js_value($html, $const_name)
{
    $needle = 'const ' . $const_name . ' = ';
    $pos = strpos($html, $needle);
    if ($pos === false) return null;

    $start = $pos + strlen($needle);
    $len = strlen($html);
    while ($start < $len && ctype_space($html[$start])) $start++;
    if ($start >= $len) return null;

    $first = $html[$start];
    if ($first === '"' || $first === "'") {
        $quote = $first;
        $escape = false;
        for ($i = $start + 1; $i < $len; $i++) {
            $ch = $html[$i];
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
                continue;
            }
            if ($ch === $quote) {
                return substr($html, $start, $i - $start + 1);
            }
        }
        return null;
    }

    if ($first !== '{' && $first !== '[') return null;
    $open = $first;
    $close = $open === '{' ? '}' : ']';
    $depth = 0;
    $in_string = false;
    $quote = '';
    $escape = false;

    for ($i = $start; $i < $len; $i++) {
        $ch = $html[$i];
        if ($in_string) {
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
                continue;
            }
            if ($ch === $quote) {
                $in_string = false;
                $quote = '';
            }
            continue;
        }

        if ($ch === '"' || $ch === "'") {
            $in_string = true;
            $quote = $ch;
            continue;
        }
        if ($ch === $open) $depth++;
        if ($ch === $close) $depth--;
        if ($depth === 0) {
            return substr($html, $start, $i - $start + 1);
        }
    }

    return null;
}

function setlist_stats_decode_const($html, $name, $fallback)
{
    $json = setlist_stats_extract_js_value($html, $name);
    if ($json === null) return $fallback;
    $data = json_decode($json, true);
    return is_array($data) ? $data : $fallback;
}

function setlist_stats_decode_string_const($html, $name, $fallback = '')
{
    $json = setlist_stats_extract_js_value($html, $name);
    if ($json === null) return $fallback;
    $data = json_decode($json, true);
    return is_string($data) ? $data : $fallback;
}

function setlist_stats_normalize_viewer($html, $source_url)
{
    $data = setlist_stats_default_result($source_url);
    if ($html === false || trim((string)$html) === '') return $data;

    $categories = setlist_stats_decode_const($html, 'CATS', []);
    $cool_data = setlist_stats_decode_const($html, 'COOL_DATA', []);
    $rank_data = setlist_stats_decode_const($html, 'RANK_DATA', []);
    if (!$categories && $cool_data) {
        $categories = array_keys($cool_data);
    }

    $data['updated_at'] = setlist_stats_decode_string_const($html, 'UPDATE_TS', date('c'));
    $data['categories'] = array_values($categories);
    $data['cool_data'] = $cool_data;
    $data['rank_data'] = $rank_data;
    $data['total_categories'] = count($data['categories']);
    return $data;
}

function setlist_stats_get_data($force_refresh = false)
{
    global $config_ini;

    $source_url = SETLIST_VIEWER_URL;
    if (!empty($config_ini['setlist_stats_url'])) {
        $source_url = urldecode($config_ini['setlist_stats_url']);
    }
    $ttl = 3600;
    if (isset($config_ini['setlist_cache_ttl']) && is_numeric($config_ini['setlist_cache_ttl'])) {
        $ttl = max(0, (int)$config_ini['setlist_cache_ttl']);
    }

    if (!$force_refresh) {
        $cached = setlist_stats_load_cache($ttl);
        if (is_array($cached)) {
            $cached['cache'] = 'hit';
            return setlist_stats_apply_runtime_config($cached);
        }
    }

    if (isset($config_ini['connectinternet']) && (int)$config_ini['connectinternet'] !== 1) {
        $cached = setlist_stats_load_cache(0);
        if (is_array($cached)) {
            $cached['cache'] = 'stale';
            return setlist_stats_apply_runtime_config($cached);
        }
        $data = setlist_stats_default_result($source_url);
        $data['error'] = 'connectinternet_disabled';
        return $data;
    }

    $html = file_get_html_with_retry($source_url, 2, 20);
    if ($html === false || $html === '') {
        $cached = setlist_stats_load_cache(0);
        if (is_array($cached)) {
            $cached['cache'] = 'stale';
            $cached['warning'] = 'fetch_failed';
            return setlist_stats_apply_runtime_config($cached);
        }
        $data = setlist_stats_default_result($source_url);
        $data['error'] = 'fetch_failed';
        return $data;
    }

    $data = setlist_stats_normalize_viewer($html, $source_url);
    if (!$data['categories'] || !$data['cool_data']) {
        $cached = setlist_stats_load_cache(0);
        if (is_array($cached)) {
            $cached['cache'] = 'stale';
            $cached['warning'] = 'viewer_parse_failed';
            return setlist_stats_apply_runtime_config($cached);
        }
        $data['error'] = 'viewer_parse_failed';
        return $data;
    }

    $data['cache'] = 'refresh';
    setlist_stats_save_cache($data);
    return setlist_stats_apply_runtime_config($data);
}
