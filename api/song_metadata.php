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
 *   読み仮名はゆかりすたーの登録規則 (全角カタカナ・濁点なし) へ保存時に正規化する。
 *   かな以外 (英数字・漢字等) が含まれる読みは 400 エラー。
 *
 * 応答: { "ok":true, "data":{...} } / { "ok":false, "error":"..." }
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../function_search_listerdb.php';

/**
 * 読み仮名をゆかりすたーの登録規則へ正規化する。
 * ひらがな・カタカナ・半角カタカナのどれで入力されても
 * 「全角カタカナ・濁点/半濁点なし」にそろえる。
 * かな以外 (英数字・漢字・記号) が含まれる場合は null (呼び出し元でエラー)。
 */
function normalize_ruby($value)
{
    // 半角カナ→全角 (K) + 濁点合成 (V)、ひらがな→カタカナ (C)
    $value = mb_convert_kana(trim((string)$value), 'KVC', 'UTF-8');
    // 濁点・半濁点を除いた清音へ
    $value = strtr($value, [
        'ガ' => 'カ', 'ギ' => 'キ', 'グ' => 'ク', 'ゲ' => 'ケ', 'ゴ' => 'コ',
        'ザ' => 'サ', 'ジ' => 'シ', 'ズ' => 'ス', 'ゼ' => 'セ', 'ゾ' => 'ソ',
        'ダ' => 'タ', 'ヂ' => 'チ', 'ヅ' => 'ツ', 'デ' => 'テ', 'ド' => 'ト',
        'バ' => 'ハ', 'ビ' => 'ヒ', 'ブ' => 'フ', 'ベ' => 'ヘ', 'ボ' => 'ホ',
        'パ' => 'ハ', 'ピ' => 'ヒ', 'プ' => 'フ', 'ペ' => 'ヘ', 'ポ' => 'ホ',
        'ヴ' => 'ウ', 'ヷ' => 'ワ', 'ヸ' => 'ヰ', 'ヹ' => 'ヱ', 'ヺ' => 'ヲ',
        '゛' => '', '゜' => '',
    ]);
    // 全角カタカナ・長音・空白だけになっていることを確認
    if (!preg_match('/\A[ァ-ヶー \x{3000}]*\z/u', $value)) {
        return null;
    }
    return $value;
}

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

    $ruby_labels = ['song_ruby' => '曲名の読み',
                    'lister_artist_ruby' => '歌手名の読み',
                    'lister_work_ruby' => '作品名の読み'];

    $updates = [];  // requesttable へ反映する項目
    $changes = [];  // metadata_correction へ記録する項目 [field, old, new]
    foreach ($before as $field => $old) {
        $value = api_param($field);
        if ($value === null) {
            continue; // 送られていない項目は変更なし
        }
        $value = trim((string)$value);
        if ($value === $old) {
            continue; // 変更なし (既存の登録が規則外 [英字など] でもそのまま)
        }
        if (isset($ruby_labels[$field])) {
            // 変更された読み仮名はゆかりすたーの登録規則 (全角カタカナ・濁点なし) へそろえる
            $value = normalize_ruby($value);
            if ($value === null) {
                api_error($ruby_labels[$field] . 'に使えるのは ひらがな・カタカナ だけです');
            }
            if ($value === $old) {
                continue; // 正規化したら既存の登録と同じになった
            }
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
