<?php
/**
 * /api/lister_index.php
 *
 * ListerDB (アニソンDB) の期別インデックスと完全一致検索。
 * モバイルアプリの「期別リスト」(年 → 期 → 作品 → 曲) と、検索結果からの
 * 再検索 (作品名 / 歌手名 / シリーズ名 / 動画制作者の完全一致) が使う。
 *
 * 期の判別は t_found.song_release_date (修正ユリウス日)。この値はゆかりすたーが
 * タイアップのリリース日を優先して書き込むマージ値 (無ければ曲の発売日) のため、
 * ゆかりすたー本家の期別リストと同じ基準になる。
 * 既定は一般向けのみ対象 (tie_up_age_limit < 18)。include_agelimit=1 を付けると
 * 年齢制限のあるタイアップ曲も対象になる (検索画面で有効化した利用者ごとのオプトイン)。
 *
 * mode:
 *   years                        → 年ごとの曲数 (降順)
 *   quarters&year=YYYY           → 指定年の期ごとの曲数・作品数 (q: 1=1〜3月:冬 ... 4=10〜12月:秋)
 *   programs&year=YYYY&quarter=N → 期内の作品一覧 (作品名 / シリーズ名 / 曲数)。
 *                                  quarter=0 で年全体 (年代別ビュー)
 *   programs&group=シリーズ名     → シリーズ内の作品一覧 (シリーズ再検索用)
 *   songs&program=|artist=|group=|worker= → 完全一致の曲一覧 (複数指定は AND)
 *   songs&anyword=キーワード      → あいまい検索 (スペース区切り AND、読み仮名対応。ゆかりすたーの
 *                                  検索ワード [found_comment の ",//" 以降の別名・略称] も対象)
 *   initials&target=program|artist|group  → 頭文字 (ひらがな清音 + その他) ごとの名前数
 *   names&target=...&initial=あ   → 頭文字が一致する名前一覧 (作品名 / 歌手名 / シリーズ名)
 *   names&target=...&keyword=...  → 名前の部分一致一覧 (読み仮名対応)
 *   rules                        → ゆかりすたーのフォルダー設定 (YukaLister.json 等) のファイル命名規則を
 *                                  設定フォルダーごとに (クライアントの検索精度向上用。フォルダーが読めない環境では空)
 *
 * songs の応答は曲単位にグルーピングされ、同じ曲の複数ファイル (別動画) が files に並ぶ。
 * songs&flat=1 でグルーピングせずファイル単位 (1アイテム=1ファイル) で返す (応答構造は同じ)。
 * songs の order: date_desc (既定。ファイル更新日の新しい順) / date_asc / name (曲名順)。
 *
 * 応答例:
 *   years:    { "ok":true, "data":{ "years":[{"year":2026,"songs":492},...], "no_date":28260 } }
 *   quarters: { "ok":true, "data":{ "year":2026, "quarters":[{"q":2,"label":"4月〜6月：春","songs":196,"programs":45},...] } }
 *   programs: { "ok":true, "data":{ "year":2026, "quarter":2, "label":"4月〜6月：春",
 *               "programs":[{"program":"...","group":"シリーズ名 or null","songs":5},...] } }
 *   songs:    { "ok":true, "data":{ "total":2, "files_total":3, "agelimit_hidden":0, "items":[
 *               { "song_name":"...", "song_artist":"...", "program_name":"...",
 *                 "tie_up_group_name":"...", "song_op_ed":"3rdシングル",
 *                 "song_ruby":"...", "artist_ruby":"...", "tie_up_ruby":"...", "tie_up_group_ruby":"...",
 *                 "program_category":"アニメ", "song_release_date":"2024-04-01",
 *                 "files":[{ "found_path":"...", "found_comment":"リリックビデオ",
 *                            "found_keyword":["ハガレン","ハカレン"],
 *                            "found_worker":"...", "found_file_size":123,
 *                            "song_id":"...", "tie_up_id":"...",
 *                            "found_last_write_time":"2026-08-12T20:48:00",
 *                            "found_track":"On-Off", "found_smart_track_on":true, "found_smart_track_off":true },...] },...] } }
 *   items の読み仮名 4 つ (song_ruby / artist_ruby / tie_up_ruby / tie_up_group_ruby) はゆかりすたーの
 *   検索用フリガナ (全角カタカナ・濁点なし・小書きなし・長音なし。設定により ",長音あり版" が付く。
 *   複数歌手は "," 区切り。読みの無い項目は表記そのまま)。song_release_date はリリース日 (無ければ null)。
 *   files の found_keyword: ゆかりすたーが found_comment の ",//" 以降に付ける検索ワード
 *   (作品・シリーズ・曲に登録された別名や略称) を分けたもの。表示用の found_comment には含めない。
 *   files の song_id が "!" で始まる行は楽曲情報 DB にヒットせず、ファイル名・フォルダー設定から
 *   登録された行 (tie_up_id も同様)。found_last_write_time はファイルの更新日時 (ゆかりすたーの
 *   PC の現地時刻)。found_smart_track_on/off はオン/オフボーカルのトラックがあるか。
 *   これらはクライアント (ゆかナビ) が検索精度の向上と表示に使う。
 *   songs の agelimit_hidden: 年齢制限フィルタで隠れた曲数 (include_agelimit=1 のときは常に 0)。
 *   まだ有効化していない利用者へ「年齢制限の曲が N 曲あります」の案内を出すのに使う。
 *   rules:    { "ok":true, "data":{ "count":2, "folders":[
 *               { "folder":"D:\動画\AAA", "file_name_rules":["[<Artist>]<Title>_<Program>"],
 *                 "folder_name_rules":["<Worker>=AAA"], "source":"YukaLister.json" },...] } }
 *   規則の書式はゆかりすたーのフォルダー設定そのまま (<Title> <Artist> <Program> <Oped> <Track> <Comment>
 *   等の変数と、任意の文字列 *)。親フォルダーの設定が下にも効くので、クライアントは最も深い
 *   一致フォルダーの規則を使う。
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_lister_rules.php';

// ---- ListerDB を開く ----
$lister_dbpath = 'list\List.sqlite3';
if (array_key_exists('listerDBPATH', $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
}
if (empty($lister_dbpath) || !file_exists($lister_dbpath)) {
    api_error('ListerDB が見つかりません (ゆかりすたー連携が未設定です)', 503);
}
try {
    $ldb = new PDO('sqlite:' . $lister_dbpath);
    $ldb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    api_error('ListerDB を開けません', 503);
}

// 共通の対象条件: 既定は一般向けのみ (ゆかりすたー本家は 18 歳以上対象を成人向けとして分離する)。
// include_agelimit=1 のとき (検索画面のチェックで有効化した利用者) は年齢制限曲も含める
if (api_param('include_agelimit', '') == 1) {
    $base_where = '1=1';
} else {
    $base_where = '(tie_up_age_limit IS NULL OR tie_up_age_limit < 18)';
}
// 期別系はタイアップ (作品) が付いている曲のみ (本家の期別リストと同じ)
$program_where = "program_name IS NOT NULL AND program_name != ''";

// 修正ユリウス日 → 年・月の抽出式 (MJD 40587 = 1970-01-01)
$year_expr  = "CAST(strftime('%Y', (song_release_date - 40587) * 86400, 'unixepoch') AS INTEGER)";
$month_expr = "CAST(strftime('%m', (song_release_date - 40587) * 86400, 'unixepoch') AS INTEGER)";

/** 指定年月の 1 日 0:00 (UTC) の修正ユリウス日を返す。期の範囲比較に使う。 */
function mjd_of_month($year, $month)
{
    if ($month > 12) {
        $year++;
        $month -= 12;
    }
    return gmmktime(0, 0, 0, $month, 1, $year) / 86400 + 40587;
}

/** 期番号 (1〜4) → 表示ラベル */
function quarter_label($q)
{
    static $labels = [1 => '1月〜3月：冬', 2 => '4月〜6月：春', 3 => '7月〜9月：夏', 4 => '10月〜12月：秋'];
    return $labels[$q] ?? '';
}

/**
 * 修正ユリウス日 → 日付/日時文字列 (0 以下・未設定は null)。
 * ゆかりすたーは現地時刻をそのまま日数に換算して書き込むため、UTC として戻せば現地の日時になる。
 */
function lister_mjd_to_string($mjd, $format)
{
    if ($mjd === null || (float)$mjd <= 0) {
        return null;
    }
    return gmdate($format, (int)floor(((float)$mjd - 40587) * 86400));
}

// ---- あいまい検索 (search_listerdb_songlist_json.php の anyword と同じ規則) ----

/** 濁点外し・小文字大文字化・ひらがな→カタカナ (読み仮名カラム検索用)。 */
function lister_kanabuild($str)
{
    $from = 'ガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポァィゥェォャュョッ';
    $to   = 'カキクケコサシスセソタチツテトハヒフヘホハヒフヘホアイウエオヤユヨツ';
    $temp = mb_convert_kana($str, 'C'); // ひらがな→カタカナ
    $result = '';
    $len = mb_strlen($temp);
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($temp, $i, 1);
        $pos = mb_strpos($from, $ch);
        $result .= ($pos !== false) ? mb_substr($to, $pos, 1) : $ch;
    }
    return $result;
}

/** 作品一覧クエリの行を応答形式に変換する。 */
function lister_program_rows($rows)
{
    $programs = [];
    foreach ($rows as $row) {
        $programs[] = [
            'program' => $row['program_name'],
            'group'   => ($row['group_name'] !== '' && $row['group_name'] !== null)
                ? $row['group_name'] : null,
            'songs'   => (int)$row['songs'],
        ];
    }
    return $programs;
}

/**
 * 1カラム分のあいまい条件 (スペース区切りの語をすべて含む。読み仮名カラムも OR で見る)。
 */
function lister_like_cond(PDO $db, $column, $rubyColumn, $word)
{
    $words = explode(' ', mb_convert_kana($word, 's'));
    $conds = [];
    foreach ($words as $w) {
        if ($w === '') {
            continue;
        }
        $cond = "($column LIKE " . $db->quote('%' . $w . '%');
        if ($rubyColumn !== null) {
            $cond .= " OR $rubyColumn LIKE " . $db->quote('%' . lister_kanabuild($w) . '%');
        }
        $conds[] = $cond . ')';
    }
    return implode(' AND ', $conds);
}

// ---- 頭文字インデックス (作品名 / 歌手名 / シリーズ名で探す) ----

/** 頭文字インデックスの対象定義。head は頭文字の式、name は一覧に出す名前カラム。 */
function lister_index_target($target)
{
    global $base_where, $program_where;
    switch ($target) {
        case 'program':
            return [
                'head'  => 'found_head',
                'name'  => 'program_name',
                'ruby'  => 'tie_up_ruby',
                'where' => "$program_where AND $base_where",
            ];
        case 'artist':
            return [
                'head'  => 'substr(found_artist_ruby, 1, 1)',
                'name'  => 'song_artist',
                'ruby'  => 'found_artist_ruby',
                'where' => "song_artist IS NOT NULL AND song_artist != '' AND $base_where",
            ];
        case 'group':
            return [
                'head'  => 'substr(tie_up_group_ruby, 1, 1)',
                'name'  => 'tie_up_group_name',
                'ruby'  => 'tie_up_group_ruby',
                'where' => "tie_up_group_name IS NOT NULL AND tie_up_group_name != '' AND $base_where",
            ];
    }
    api_error('invalid target (program|artist|group)');
}

/** 頭文字をひらがな清音1文字に正規化する (かな以外は「その他」)。 */
function lister_normalize_initial($head)
{
    if ($head === null || $head === '' || $head === 'その他') {
        return 'その他';
    }
    $ch = mb_substr($head, 0, 1);
    $ch = mb_convert_kana($ch, 'cH'); // カタカナ (半角含む) → ひらがな
    $from = 'がぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽぁぃぅぇぉゃゅょっゎゔ';
    $to   = 'かきくけこさしすせそたちつてとはひふへほはひふへほあいうえおやゆよつわう';
    $pos = mb_strpos($from, $ch);
    if ($pos !== false) {
        $ch = mb_substr($to, $pos, 1);
    }
    return preg_match('/^[ぁ-ん]$/u', $ch) ? $ch : 'その他';
}

$mode = api_param('mode', '');

if ($mode === 'initials') {
    $targetKey = (string)api_param('target', '');
    $t = lister_index_target($targetKey);
    $rows = $ldb->query(
        "SELECT {$t['head']} AS h, COUNT(DISTINCT {$t['name']}) AS names FROM t_found"
        . " WHERE {$t['where']} GROUP BY h"
    )->fetchAll(PDO::FETCH_ASSOC);
    // 濁音・小文字・カタカナ表記を清音ひらがなに畳んで集計する
    $counts = [];
    foreach ($rows as $row) {
        $key = lister_normalize_initial($row['h']);
        $counts[$key] = ($counts[$key] ?? 0) + (int)$row['names'];
    }
    $initials = [];
    foreach ($counts as $head => $names) {
        $initials[] = ['head' => $head, 'names' => $names];
    }
    api_ok(['target' => $targetKey, 'initials' => $initials]);
}

if ($mode === 'names') {
    $targetKey = (string)api_param('target', '');
    $t = lister_index_target($targetKey);
    $initial = (string)api_param('initial', '');
    $keyword = (string)api_param('keyword', '');

    $where = $t['where'];
    $params = [];
    if ($initial !== '') {
        // 正規化後の頭文字が一致する生の頭文字を集めて IN で絞る
        $rawRows = $ldb->query(
            "SELECT DISTINCT {$t['head']} AS h FROM t_found WHERE {$t['where']}"
        )->fetchAll(PDO::FETCH_COLUMN);
        $ph = [];
        foreach ($rawRows as $i => $raw) {
            if (lister_normalize_initial($raw) !== $initial) {
                continue;
            }
            $key = ':h' . $i;
            $ph[] = $key;
            $params[$key] = $raw;
        }
        if (count($ph) === 0) {
            api_ok(['target' => $targetKey, 'names' => []]);
        }
        $where .= " AND {$t['head']} IN (" . implode(',', $ph) . ')';
    } elseif ($keyword !== '') {
        $cond = lister_like_cond($ldb, $t['name'], $t['ruby'], $keyword);
        if ($cond === '') {
            api_error('keyword が不正です');
        }
        $where .= " AND ($cond)";
    } else {
        api_error('initial か keyword を指定してください');
    }

    // program は所属シリーズ、artist / group は参加作品数を添える
    $extra = ($targetKey === 'program')
        ? ', MAX(tie_up_group_name) AS group_name'
        : ', COUNT(DISTINCT program_name) AS programs';
    $stmt = $ldb->prepare(
        "SELECT {$t['name']} AS name, COUNT(*) AS songs, MIN({$t['ruby']}) AS ruby$extra FROM t_found"
        . " WHERE $where GROUP BY {$t['name']} ORDER BY ruby, name LIMIT 500"
    );
    $stmt->execute($params);
    $names = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entry = ['name' => $row['name'], 'songs' => (int)$row['songs']];
        if ($targetKey === 'program') {
            $entry['group'] = ($row['group_name'] !== '' && $row['group_name'] !== null)
                ? $row['group_name'] : null;
        } else {
            $entry['programs'] = (int)$row['programs'];
        }
        $names[] = $entry;
    }
    api_ok(['target' => $targetKey, 'names' => $names]);
}

if ($mode === 'years') {
    $rows = $ldb->query(
        "SELECT $year_expr AS y, COUNT(*) AS songs FROM t_found"
        . " WHERE song_release_date > 0 AND $program_where AND $base_where"
        . ' GROUP BY y ORDER BY y DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $years = [];
    foreach ($rows as $row) {
        $years[] = ['year' => (int)$row['y'], 'songs' => (int)$row['songs']];
    }
    $noDate = (int)$ldb->query(
        "SELECT COUNT(*) FROM t_found WHERE song_release_date <= 0 AND $program_where AND $base_where"
    )->fetchColumn();
    api_ok(['years' => $years, 'no_date' => $noDate]);
}

if ($mode === 'quarters') {
    $year = (int)api_param('year', 0);
    if ($year < 1900 || $year > 2200) {
        api_error('year が不正です');
    }
    $rows = $ldb->query(
        "SELECT (($month_expr - 1) / 3 + 1) AS q, COUNT(*) AS songs,"
        . ' COUNT(DISTINCT program_name) AS programs FROM t_found'
        . " WHERE song_release_date > 0 AND $year_expr = $year AND $program_where AND $base_where"
        . ' GROUP BY q ORDER BY q'
    )->fetchAll(PDO::FETCH_ASSOC);
    $quarters = [];
    foreach ($rows as $row) {
        $q = (int)$row['q'];
        $quarters[] = [
            'q'        => $q,
            'label'    => quarter_label($q),
            'songs'    => (int)$row['songs'],
            'programs' => (int)$row['programs'],
        ];
    }
    api_ok(['year' => $year, 'quarters' => $quarters]);
}

if ($mode === 'programs') {
    $group = api_param('group', '');
    if ($group !== '' && $group !== null) {
        // シリーズ内の作品一覧 (シリーズ再検索用。リリース日は不問)
        $stmt = $ldb->prepare(
            'SELECT program_name, MAX(tie_up_group_name) AS group_name, COUNT(*) AS songs,'
            . ' MIN(found_head) AS head, MIN(tie_up_ruby) AS ruby FROM t_found'
            . " WHERE tie_up_group_name = :group AND $program_where AND $base_where"
            . ' GROUP BY program_name ORDER BY head, ruby, program_name'
        );
        $stmt->execute([':group' => $group]);
        api_ok([
            'group'    => $group,
            'programs' => lister_program_rows($stmt->fetchAll(PDO::FETCH_ASSOC)),
        ]);
    }

    $year = (int)api_param('year', 0);
    $quarter = (int)api_param('quarter', 0);
    if ($year < 1900 || $year > 2200 || $quarter < 0 || $quarter > 4) {
        api_error('year / quarter が不正です');
    }
    // 期の範囲は「開始月 1 日 <= リリース日 < 翌期の 1 日」(本家と同じ半開区間)。
    // quarter=0 は年全体 (1月1日 〜 翌年1月1日)
    if ($quarter === 0) {
        $mjdStart = mjd_of_month($year, 1);
        $mjdEnd   = mjd_of_month($year, 13);
    } else {
        $mjdStart = mjd_of_month($year, ($quarter - 1) * 3 + 1);
        $mjdEnd   = mjd_of_month($year, $quarter * 3 + 1);
    }
    $stmt = $ldb->prepare(
        'SELECT program_name, MAX(tie_up_group_name) AS group_name, COUNT(*) AS songs,'
        . ' MIN(found_head) AS head, MIN(tie_up_ruby) AS ruby FROM t_found'
        . " WHERE song_release_date >= :mjd_start AND song_release_date < :mjd_end"
        . " AND $program_where AND $base_where"
        . ' GROUP BY program_name ORDER BY head, ruby, program_name'
    );
    $stmt->execute([':mjd_start' => $mjdStart, ':mjd_end' => $mjdEnd]);
    api_ok([
        'year'     => $year,
        'quarter'  => $quarter,
        'label'    => $quarter === 0 ? '1月〜12月' : quarter_label($quarter),
        'programs' => lister_program_rows($stmt->fetchAll(PDO::FETCH_ASSOC)),
    ]);
}

if ($mode === 'songs') {
    $where = [$base_where];
    $params = [];

    // 完全一致フィルタ (複数指定は AND)。再検索と「作品の曲一覧」が使う
    $filters = [
        'program' => 'program_name',
        'artist'  => 'song_artist',
        'group'   => 'tie_up_group_name',
        'worker'  => 'found_worker',
    ];
    foreach ($filters as $key => $column) {
        $value = api_param($key, '');
        if ($value !== '' && $value !== null) {
            $where[] = "$column = :$key";
            $params[":$key"] = $value;
        }
    }

    // あいまい検索 (検索トップのキーワード検索用)
    $anyword = api_param('anyword', '');
    if ($anyword !== '' && $anyword !== null) {
        // 空白のみの anyword では各条件が空文字になる。そのまま連結すると
        // "( OR  OR ...)" の不正な SQL になるため、空の条件を除いてから組み立てる
        $orConds = array_filter([
            lister_like_cond($ldb, 'song_name', 'song_ruby', $anyword),
            lister_like_cond($ldb, 'song_artist', 'found_artist_ruby', $anyword),
            lister_like_cond($ldb, 'program_name', 'tie_up_ruby', $anyword),
            lister_like_cond($ldb, 'tie_up_group_name', 'tie_up_group_ruby', $anyword),
            lister_like_cond($ldb, 'maker_name', 'maker_ruby', $anyword),
            lister_like_cond($ldb, 'found_path', null, $anyword),
            // ゆかりすたーの検索ワード (found_comment の ",//" 以降にある作品・シリーズ・曲の別名や略称と
            // その読み)。Web 版のファイル検索 (search_listerdb_filelist_json.php) と同じく対象にする。
            // 「ハガレン」で鋼の錬金術師の曲が出るのはこれによる。読み (濁点なしカタカナ) も並んでいるので、
            // かな入力は kanabuild した形でも当てる
            lister_like_cond($ldb, 'found_comment', 'found_comment', $anyword),
        ], function ($cond) { return $cond !== ''; });
        if (count($orConds) > 0) {
            $where[] = '(' . implode(' OR ', $orConds) . ')';
        } else {
            $anyword = ''; // 空白のみは未指定として扱う (下の必須チェックへ)
        }
    }

    if (count($params) === 0 && ($anyword === '' || $anyword === null)) {
        api_error('anyword または program / artist / group / worker を指定してください');
    }
    $whereSql = implode(' AND ', $where);

    // 年齢制限フィルタで隠れた曲数 (曲単位)。まだ有効化していない利用者の検索結果に
    // 「年齢制限の曲が N 曲あります」の案内を出すために返す (フィルタ適用時のみ計算)
    $agelimitHidden = 0;
    if (api_param('include_agelimit', '') != 1) {
        $hiddenWhere = $where;
        $hiddenWhere[0] = 'tie_up_age_limit >= 18'; // 先頭要素 ($base_where) を反転
        $stmt = $ldb->prepare(
            'SELECT COUNT(*) FROM (SELECT 1 FROM t_found WHERE ' . implode(' AND ', $hiddenWhere)
            . ' GROUP BY song_name, program_name, song_artist)'
        );
        $stmt->execute($params);
        $agelimitHidden = (int)$stmt->fetchColumn();
    }

    // 並び順 (曲の順序はファイル行の出現順で決まる)
    switch (api_param('order', 'date_desc')) {
        case 'date_asc':
            $orderSql = 'found_last_write_time ASC';
            break;
        case 'name':
            $orderSql = 'song_ruby, song_name, program_name, found_path';
            break;
        default: // date_desc: ファイル更新日の新しい順 (既定)
            $orderSql = 'found_last_write_time DESC';
            break;
    }

    // ファイル単位で引いて (重複行は found_path でまとめる)、PHP 側で曲単位にグルーピングする
    $stmt = $ldb->prepare(
        'SELECT song_name, song_ruby, song_artist, program_name, tie_up_group_name,'
        . ' song_op_ed, found_worker, found_path, found_file_size, found_comment,'
        . ' song_id, tie_up_id, found_artist_ruby, tie_up_ruby, tie_up_group_ruby,'
        . ' program_category, song_release_date, found_last_write_time,'
        . ' found_track, found_smart_track_on, found_smart_track_off'
        . " FROM t_found WHERE $whereSql"
        . " GROUP BY found_path ORDER BY $orderSql LIMIT 600"
    );
    $stmt->execute($params);

    // flat=1: グルーピングせずファイル単位で返す (キーを found_path にするだけで応答構造は同じ)
    $flat = api_param('flat', '') === '1';

    $items = [];
    $index = [];
    $filesTotal = 0;
    $maxSongs = 150;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $flat
            ? $row['found_path']
            : ($row['song_name'] ?? '') . "\x1f" . ($row['program_name'] ?? '')
                . "\x1f" . ($row['song_artist'] ?? '');
        if (!array_key_exists($key, $index)) {
            if (count($items) >= $maxSongs) {
                continue;
            }
            $index[$key] = count($items);
            $items[] = [
                'song_name'         => $row['song_name'],
                'song_artist'       => $row['song_artist'],
                'program_name'      => $row['program_name'],
                'tie_up_group_name' => $row['tie_up_group_name'],
                'song_op_ed'        => $row['song_op_ed'],
                // 読み仮名 (ゆかりすたーの検索用フリガナ) とリリース日・カテゴリー
                // (クライアントの検索精度向上・表示用)
                'song_ruby'         => $row['song_ruby'],
                'artist_ruby'       => $row['found_artist_ruby'],
                'tie_up_ruby'       => $row['tie_up_ruby'],
                'tie_up_group_ruby' => $row['tie_up_group_ruby'],
                'program_category'  => $row['program_category'],
                'song_release_date' => lister_mjd_to_string($row['song_release_date'], 'Y-m-d'),
                'files'             => [],
            ];
        }
        // コメントの ",//" 以降は内部メモのため表示用からは除去 (listerdb_lookup_songinfo と同じ)。
        // 内部メモの実体はゆかりすたーの検索ワード (作品・シリーズ・曲の別名・略称とその読み。
        // ",//語,読み,,//語,..." の形) なので、分解して found_keyword として別に返す
        $comment = '';
        $keywords = [];
        if (!empty($row['found_comment'])) {
            $parts = explode(',//', $row['found_comment'], 2);
            $comment = trim($parts[0]);
            if (count($parts) > 1) {
                foreach (preg_split('/,(?:\/\/)?/', $parts[1]) as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '' && !in_array($keyword, $keywords, true)) {
                        $keywords[] = $keyword;
                    }
                }
            }
        }
        $items[$index[$key]]['files'][] = [
            'found_path'      => $row['found_path'],
            'found_comment'   => $comment,
            'found_keyword'   => $keywords,
            'found_worker'    => $row['found_worker'],
            'found_file_size' => (int)$row['found_file_size'],
            // "!" で始まる song_id は楽曲情報 DB 未ヒット (ファイル名・フォルダー設定からの登録)
            'song_id'         => $row['song_id'],
            'tie_up_id'       => $row['tie_up_id'],
            'found_last_write_time' => lister_mjd_to_string($row['found_last_write_time'], 'Y-m-d\TH:i:s'),
            'found_track'     => $row['found_track'],
            'found_smart_track_on'  => (bool)$row['found_smart_track_on'],
            'found_smart_track_off' => (bool)$row['found_smart_track_off'],
        ];
        $filesTotal++;
    }
    api_ok([
        'total' => count($items),
        'files_total' => $filesTotal,
        'agelimit_hidden' => $agelimitHidden,
        'items' => $items,
    ]);
}

if ($mode === 'rules') {
    api_ok(lister_rules_collect($ldb));
}

api_error('mode が不正です (years / quarters / programs / songs / initials / names / rules)');
