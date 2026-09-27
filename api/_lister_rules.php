<?php
/**
 * /api/_lister_rules.php
 *
 * ゆかりすたーのフォルダー設定 (動画フォルダーに置かれた YukaLister.json、旧形式の
 * YukaLister.config / NicoKaraLister.config) からファイル命名規則を読む。
 * lister_index.php の mode=rules が使う。クライアント (ゆかナビ) の検索精度向上のための情報。
 *
 * フォルダー設定は親フォルダーのものが下のフォルダーにも効く (ゆかりすたーと同じく、
 * 設定ファイルが見つかるまで親をたどる)。リスト DB の登録フォルダーがこのサーバーから
 * 読めない環境 (別 PC のリスト DB を参照している等) では空になる。
 */

/**
 * フォルダー設定ファイルがあるフォルダー (自身または祖先) を返す。無ければ ''。
 * @param string $dir   調べるフォルダー
 * @param array  $cache フォルダー → 結果 のメモ (呼び出し側で共有)
 */
function lister_rules_find_settings_folder($dir, array &$cache)
{
    $dir = rtrim($dir, "/\\");
    if ($dir === '') {
        return '';
    }
    if (array_key_exists($dir, $cache)) {
        return $cache[$dir];
    }
    if (!is_dir($dir)) {
        // このサーバーから見えないフォルダー (別 PC のパスなど) はたどらない
        $cache[$dir] = '';
        return '';
    }
    $found = '';
    foreach (['YukaLister.json', 'YukaLister.config', 'NicoKaraLister.config'] as $name) {
        if (is_file($dir . DIRECTORY_SEPARATOR . $name)) {
            $found = $dir;
            break;
        }
    }
    if ($found === '') {
        $parent = rtrim(dirname($dir), "/\\");
        if ($parent !== '' && $parent !== $dir && $parent !== '.') {
            $found = lister_rules_find_settings_folder($parent, $cache);
        }
    }
    $cache[$dir] = $found;
    return $found;
}

/**
 * フォルダー設定ファイルから命名規則を読む。
 * @return array|null [ 'folder', 'file_name_rules' => string[], 'folder_name_rules' => string[], 'source' ]。読めなければ null
 */
function lister_rules_load($dir)
{
    $json = $dir . DIRECTORY_SEPARATOR . 'YukaLister.json';
    if (is_file($json)) {
        $text = @file_get_contents($json);
        if ($text === false) {
            return null;
        }
        // UTF-8 BOM 付きで保存されていることがある
        if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
            $text = substr($text, 3);
        }
        $data = json_decode($text, true);
        if (!is_array($data)) {
            return null;
        }
        return [
            'folder' => $dir,
            'file_name_rules' => lister_rules_strings($data['FileNameRules'] ?? []),
            'folder_name_rules' => lister_rules_strings($data['FolderNameRules'] ?? []),
            'source' => 'YukaLister.json',
        ];
    }
    foreach (['YukaLister.config', 'NicoKaraLister.config'] as $name) {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            continue;
        }
        // 旧形式は XML (FolderSettingsInDisk のシリアライズ)
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        libxml_use_internal_errors($prev);
        if ($xml === false) {
            return null;
        }
        $file = [];
        $folder = [];
        if (isset($xml->FileNameRules->string)) {
            foreach ($xml->FileNameRules->string as $s) {
                $file[] = (string)$s;
            }
        }
        if (isset($xml->FolderNameRules->string)) {
            foreach ($xml->FolderNameRules->string as $s) {
                $folder[] = (string)$s;
            }
        }
        return [
            'folder' => $dir,
            'file_name_rules' => lister_rules_strings($file),
            'folder_name_rules' => lister_rules_strings($folder),
            'source' => $name,
        ];
    }
    return null;
}

/** 空でない文字列だけの配列にする。 */
function lister_rules_strings($values)
{
    $out = [];
    if (is_array($values)) {
        foreach ($values as $v) {
            if (is_string($v) && trim($v) !== '') {
                $out[] = $v;
            }
        }
    }
    return $out;
}

/**
 * リスト DB に登録されている全フォルダーについてフォルダー設定を探し、設定フォルダーごとに規則を集める。
 * フォルダーを全部たどるので、結果は一時フォルダーに $ttl 秒キャッシュする ($cacheKey が空ならしない)。
 * @param string $cacheKey キャッシュの識別子 (リスト DB のパスなど)
 * @param int    $ttl      キャッシュの有効秒数
 * @return array [ 'count' => N, 'folders' => [ {folder, file_name_rules, folder_name_rules, source}, ... ] ]
 */
function lister_rules_collect(PDO $ldb, $cacheKey = '', $ttl = 600)
{
    $cacheFile = '';
    if ($cacheKey !== '') {
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yukari_lister_rules_' . md5($cacheKey) . '.json';
        if (is_file($cacheFile) && filemtime($cacheFile) >= time() - $ttl) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['folders']) && is_array($cached['folders'])) {
                return $cached;
            }
        }
    }
    $cache = [];
    $folders = [];
    $stmt = $ldb->query("SELECT DISTINCT found_folder FROM t_found WHERE found_folder IS NOT NULL AND found_folder != ''");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $dir) {
        $settingsDir = lister_rules_find_settings_folder($dir, $cache);
        if ($settingsDir === '' || array_key_exists($settingsDir, $folders)) {
            continue;
        }
        $rules = lister_rules_load($settingsDir);
        if ($rules !== null) {
            $folders[$settingsDir] = $rules;
        }
    }
    ksort($folders, SORT_STRING);
    $result = ['count' => count($folders), 'folders' => array_values($folders)];
    if ($cacheFile !== '') {
        @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    return $result;
}
