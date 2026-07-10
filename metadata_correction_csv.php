<?php
/**
 * metadata_correction_csv.php
 *
 * 曲情報の修正ログ (metadata_correction テーブル) の CSV ダウンロード。
 * ユーザーが予約時に修正した曲名・歌手・作品・使われ方・補足・読み仮名の
 * 変更前後を出力する。ListerDB (ゆかりすたー) の登録情報を後で直す材料。
 *
 * init.php と同じ管理者 Basic 認証で保護。UTF-8 BOM 付き (Excel でそのまま開ける)。
 */

require_once 'commonfunc.php';
require_once 'configauth_class.php';
$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])){
    header('WWW-Authenticate: Basic realm="Please use username admin to open Configuration page. "');
    die('修正ログのダウンロードにはログインが必要です');
}

if ($_SERVER['PHP_AUTH_USER'] !== 'admin'){
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('修正ログのダウンロードにはログインが必要です');
}

if (! $configauth -> check_auth($_SERVER['PHP_AUTH_PW']) ){
    header('WWW-Authenticate: Basic realm="Configuration page authorization."');
    die('修正ログのダウンロードにはログインが必要です');
}

// テーブルが無ければ空で作る (修正が一度も無いサーバーでもヘッダーだけの CSV を返す)
$db->exec('CREATE TABLE IF NOT EXISTS metadata_correction ('
    . 'id INTEGER PRIMARY KEY, fullpath TEXT, field TEXT,'
    . ' old_value TEXT, new_value TEXT, corrected_at INTEGER)');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="metadata_correction_' . date('Ymd_His') . '.csv"');

echo "\xEF\xBB\xBF"; // BOM (Excel の文字化け防止)
$out = fopen('php://output', 'w');
fputcsv($out, ['日時', 'ファイル', '項目', '修正前', '修正後']);

$rows = $db->query('SELECT corrected_at, fullpath, field, old_value, new_value'
    . ' FROM metadata_correction ORDER BY corrected_at, id');
foreach ($rows as $row) {
    fputcsv($out, [
        date('Y-m-d H:i:s', (int)$row['corrected_at']),
        $row['fullpath'],
        $row['field'],
        $row['old_value'],
        $row['new_value'],
    ]);
}
fclose($out);
