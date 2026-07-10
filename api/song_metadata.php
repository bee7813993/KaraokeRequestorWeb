<?php
/**
 * /api/song_metadata.php
 *
 * 曲メタデータ (曲名・歌手・作品・使われ方・補足説明) の取得と修正。
 * アプリ (ゆかナビ) の「曲の情報を修正する」画面が使う。
 *
 * GET ?fullpath=
 *   ListerDB から現在の曲情報 + 読み仮名を返す。
 *   { song_name, song_ruby, lister_artist, lister_artist_ruby,
 *     lister_work, lister_work_ruby, lister_op_ed, lister_comment }
 *   ListerDB 未設定・未ヒットの項目は "" (エラーにしない)
 *
 * POST action=correct id=<予約ID>
 *   + song_name= lister_artist= lister_work= lister_op_ed= lister_comment=
 *   + song_ruby= lister_artist_ruby= lister_work_ruby=
 *   予約行の song_name / lister_* を更新し (予約一覧の表示に反映)、
 *   変更があった項目だけ metadata_correction テーブルへ記録する。
 *   読み仮名は requesttable に列が無いため記録のみ
 *   (後で ListerDB の登録情報を直す材料。CSV 出力は metadata_correction_csv.php)。
 *   送られなかった項目は変更なしとして扱う。
 *
 * 応答: { "ok":true, "data":{...} } / { "ok":false, "error":"..." }
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../function_search_listerdb.php';

/** ListerDB から現在の曲情報 + 読み仮名を引く (未設定・未ヒットは全項目 "")。 */
function song_metadata_lookup($fullpath)
{
    global $config_ini;
    $empty = [
        'song_name' => '', 'song_ruby' => '',
        'lister_artist' => '', 'lister_artist_ruby' => '',
        'lister_work' => '', 'lister_work_ruby' => '',
        'lister_op_ed' => '', 'lister_comment' => '',
    ];
    if (empty($fullpath) || !array_key_exists('listerDBPATH', $config_ini)) {
        return $empty;
    }
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
    if (!file_exists($lister_dbpath)) {
        return $empty;
    }
    $info = listerdb_lookup_songinfo($fullpath, $lister_dbpath, true);
    return $info ? array_merge($empty, $info) : $empty;
}

// ---- POST: 修正の適用 + 記録 ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)api_param('action', '') === 'correct') {
    $id = (int)api_param('id', 0);
    if ($id <= 0) {
        api_error('id is required');
    }
    $stmt = $db->prepare('SELECT fullpath, song_name, lister_artist, lister_work,'
        . ' lister_op_ed, lister_comment FROM requesttable WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$request) {
        api_error('request not found', 404);
    }
    $fullpath = (string)($request['fullpath'] ?? '');

    // 修正前の値: requesttable 対象カラムは予約行、読み仮名は ListerDB から
    $ruby_before = song_metadata_lookup($fullpath);
    $before = [
        'song_name'          => (string)($request['song_name'] ?? ''),
        'lister_artist'      => (string)($request['lister_artist'] ?? ''),
        'lister_work'        => (string)($request['lister_work'] ?? ''),
        'lister_op_ed'       => (string)($request['lister_op_ed'] ?? ''),
        'lister_comment'     => (string)($request['lister_comment'] ?? ''),
        'song_ruby'          => $ruby_before['song_ruby'],
        'lister_artist_ruby' => $ruby_before['lister_artist_ruby'],
        'lister_work_ruby'   => $ruby_before['lister_work_ruby'],
    ];
    $request_columns = ['song_name', 'lister_artist', 'lister_work',
                        'lister_op_ed', 'lister_comment'];

    $updates = [];  // requesttable へ反映する項目
    $changes = [];  // metadata_correction へ記録する項目 [field, old, new]
    foreach ($before as $field => $old) {
        $value = api_param($field);
        if ($value === null) {
            continue; // 送られていない項目は変更なし
        }
        $value = trim((string)$value);
        if ($value === $old) {
            continue;
        }
        $changes[] = [$field, $old, $value];
        if (in_array($field, $request_columns, true)) {
            $updates[$field] = $value;
        }
    }

    if ($updates) {
        $set = [];
        foreach ($updates as $field => $value) {
            $set[] = $field . ' = :' . $field;
        }
        $stmt = $db->prepare('UPDATE requesttable SET ' . implode(', ', $set)
            . ' WHERE id = :id');
        foreach ($updates as $field => $value) {
            $stmt->bindValue(':' . $field, $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    if ($changes) {
        $db->exec('CREATE TABLE IF NOT EXISTS metadata_correction ('
            . 'id INTEGER PRIMARY KEY, fullpath TEXT, field TEXT,'
            . ' old_value TEXT, new_value TEXT, corrected_at INTEGER)');
        $stmt = $db->prepare('INSERT INTO metadata_correction'
            . ' (fullpath, field, old_value, new_value, corrected_at)'
            . ' VALUES (:fullpath, :field, :old_value, :new_value, :corrected_at)');
        $now = time();
        foreach ($changes as $change) {
            $stmt->bindValue(':fullpath', $fullpath);
            $stmt->bindValue(':field', $change[0]);
            $stmt->bindValue(':old_value', $change[1]);
            $stmt->bindValue(':new_value', $change[2]);
            $stmt->bindValue(':corrected_at', $now, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    api_ok(['updated' => count($updates), 'logged' => count($changes)]);
}

// ---- GET: 現在のメタデータ ----
$fullpath = (string)api_param('fullpath', '');
if ($fullpath === '') {
    api_error('fullpath is required');
}
api_ok(song_metadata_lookup($fullpath));
