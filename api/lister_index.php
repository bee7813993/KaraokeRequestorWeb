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
 * 現状は一般向けのみ対象 (tie_up_age_limit < 18。成人向けは対象外)。
 *
 * mode:
 *   years                        → 年ごとの曲数 (降順)
 *   quarters&year=YYYY           → 指定年の期ごとの曲数・作品数 (q: 1=1〜3月:冬 ... 4=10〜12月:秋)
 *   programs&year=YYYY&quarter=N → 期内の作品一覧 (作品名 / シリーズ名 / 曲数)
 *   songs&program=|artist=|group=|worker= → 完全一致の曲一覧 (複数指定は AND)
 *
 * 応答例:
 *   years:    { "ok":true, "data":{ "years":[{"year":2026,"songs":492},...], "no_date":28260 } }
 *   quarters: { "ok":true, "data":{ "year":2026, "quarters":[{"q":2,"label":"4月〜6月：春","songs":196,"programs":45},...] } }
 *   programs: { "ok":true, "data":{ "year":2026, "quarter":2, "label":"4月〜6月：春",
 *               "programs":[{"program":"...","group":"シリーズ名 or null","songs":5},...] } }
 *   songs:    { "ok":true, "data":{ "total":5, "items":[{ song_name, song_ruby, song_artist,
 *               program_name, tie_up_group_name, song_op_ed, found_worker, found_path,
 *               found_file_size, found_comment },...] } }
 */
require_once __DIR__ . '/_common.php';

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

// 共通の対象条件: 一般向けのみ (ゆかりすたー本家は 18 歳以上対象を成人向けとして分離する)
$base_where = '(tie_up_age_limit IS NULL OR tie_up_age_limit < 18)';
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

$mode = api_param('mode', '');

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
    $year = (int)api_param('year', 0);
    $quarter = (int)api_param('quarter', 0);
    if ($year < 1900 || $year > 2200 || $quarter < 1 || $quarter > 4) {
        api_error('year / quarter が不正です');
    }
    // 期の範囲は「開始月 1 日 <= リリース日 < 翌期の 1 日」(本家と同じ半開区間)
    $mjdStart = mjd_of_month($year, ($quarter - 1) * 3 + 1);
    $mjdEnd   = mjd_of_month($year, $quarter * 3 + 1);
    $stmt = $ldb->prepare(
        'SELECT program_name, MAX(tie_up_group_name) AS group_name, COUNT(*) AS songs,'
        . ' MIN(found_head) AS head, MIN(tie_up_ruby) AS ruby FROM t_found'
        . " WHERE song_release_date >= :mjd_start AND song_release_date < :mjd_end"
        . " AND $program_where AND $base_where"
        . ' GROUP BY program_name ORDER BY head, ruby, program_name'
    );
    $stmt->execute([':mjd_start' => $mjdStart, ':mjd_end' => $mjdEnd]);
    $programs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $programs[] = [
            'program' => $row['program_name'],
            'group'   => $row['group_name'] !== '' ? $row['group_name'] : null,
            'songs'   => (int)$row['songs'],
        ];
    }
    api_ok([
        'year'     => $year,
        'quarter'  => $quarter,
        'label'    => quarter_label($quarter),
        'programs' => $programs,
    ]);
}

if ($mode === 'songs') {
    // 完全一致フィルタ (複数指定は AND)。再検索と「作品の曲一覧」の両方が使う
    $filters = [
        'program' => 'program_name',
        'artist'  => 'song_artist',
        'group'   => 'tie_up_group_name',
        'worker'  => 'found_worker',
    ];
    $where = [$base_where];
    $params = [];
    foreach ($filters as $key => $column) {
        $value = api_param($key, '');
        if ($value !== '' && $value !== null) {
            $where[] = "$column = :$key";
            $params[":$key"] = $value;
        }
    }
    if (count($params) === 0) {
        api_error('program / artist / group / worker のいずれかを指定してください');
    }
    $whereSql = implode(' AND ', $where);

    $countStmt = $ldb->prepare("SELECT COUNT(DISTINCT found_path) FROM t_found WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // 同一ファイルの重複行 (タグ違い等) は GROUP BY found_path でまとめる
    $stmt = $ldb->prepare(
        'SELECT song_name, song_ruby, song_artist, program_name, tie_up_group_name,'
        . ' song_op_ed, found_worker, found_path, found_file_size, found_comment'
        . " FROM t_found WHERE $whereSql"
        . ' GROUP BY found_path ORDER BY song_ruby, song_name, found_path LIMIT 300'
    );
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    api_ok(['total' => $total, 'items' => $items]);
}

api_error('mode が不正です (years / quarters / programs / songs)');
