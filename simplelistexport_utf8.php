<?php
require_once('commonfunc.php');
require_once('function_search_listerdb.php');

function arr2csv($arr) {
    $fp = fopen('php://temp', 'r+b');
    foreach ($arr as $fields) {
        fputcsv($fp, $fields);
    }
    rewind($fp);
    $tmp = str_replace("\n", "\r\n", stream_get_contents($fp));
    return $tmp;
}

if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}
date_default_timezone_set('Asia/Tokyo');

$sql = "SELECT id, songfile, singer, comment, kind, fullpath, keychange, nowplaying, song_name, lister_artist, lister_work, lister_op_ed, lister_comment FROM requesttable ORDER BY reqorder ASC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

if(empty($dbname)){
  $dbname = 'data';
}

// 列定義（id => ヘッダーラベル）
$all_columns_def = [
    'num'          => '順番',
    'songfile'     => '曲名（ファイル名）',
    'keychange'    => 'キー',
    'program_name' => '作品名',
    'artist'       => '歌手名',
    'singer'       => '歌った人',
    'comment'      => 'コメント',
    'worker'       => '動画制作者',
];

// 列設定ファイル読み込み
function load_export_columns($config_file, $all_columns_def) {
    if (file_exists($config_file)) {
        $json = file_get_contents($config_file);
        $saved = json_decode($json, true);
        if ($saved !== null) {
            $active = [];
            foreach ($saved as $col) {
                if (!empty($col['enabled']) && isset($all_columns_def[$col['id']])) {
                    $active[] = $col['id'];
                }
            }
            if (!empty($active)) return $active;
        }
    }
    return array_keys($all_columns_def);
}

$active_columns = load_export_columns('csv_export_columns.json', $all_columns_def);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=list_'.$dbname.'.csv');

$num = 1;
$csvarray = array();

// ヘッダー行
$header = [];
foreach ($active_columns as $col_id) {
    $header[] = $all_columns_def[$col_id];
}
$csvarray[] = $header;

foreach ($allrequest as $row) {
    $rid = (int)$row["id"];
    $song_name = $row["song_name"] ?? '';
    $lister_artist = $row["lister_artist"] ?? '';
    $lister_work = $row["lister_work"] ?? '';
    $lister_op_ed = $row["lister_op_ed"] ?? '';
    $lister_comment = $row["lister_comment"] ?? '';

    // Display-time fallback: check requesttable for saved data, otherwise query ListerDB
    if (empty($song_name)
        && !empty($row['fullpath'])
        && array_key_exists('listerDBPATH', $config_ini)) {
        $lister_dbpath = urldecode($config_ini['listerDBPATH']);
        if (file_exists($lister_dbpath)) {
            $info = listerdb_lookup_songinfo($row['fullpath'], $lister_dbpath);
            if ($info && !empty($info['song_name'])) {
                $song_name = $info['song_name'];
                $lister_artist = $info['lister_artist'];
                $lister_work = $info['lister_work'];
                $lister_op_ed = $info['lister_op_ed'];
                $lister_comment = $info['lister_comment'];
                $db->exec(
                    'UPDATE requesttable SET '
                    . 'song_name='      . $db->quote($info['song_name'])      . ','
                    . 'lister_artist='  . $db->quote($info['lister_artist'])  . ','
                    . 'lister_work='    . $db->quote($info['lister_work'])    . ','
                    . 'lister_op_ed='   . $db->quote($info['lister_op_ed'])   . ','
                    . 'lister_comment=' . $db->quote($info['lister_comment'])
                    . ' WHERE id=' . $rid
                );
            }
        }
    }

    // 曲名
    $songname = !empty($song_name) ? $song_name : $row["songfile"];
    if (!empty($lister_comment)) {
        $showcomment = preg_replace('/\,\/\/.*/', "", $lister_comment);
        if (!empty($showcomment))
            $songname = $songname . '【' . $showcomment . '】';
    }

    // 作品名
    $program_name = '';
    if (!empty($lister_work)) {
        $program_name = $lister_work;
        if (!empty($lister_op_ed)) {
            $program_name = $program_name . ' ' . $lister_op_ed;
        }
    }

    // 歌手名
    $artist = $lister_artist;

    // 動画制作者 (ListerDB から取得、requesttable に保存していないため常に空)
    $worker = '';

    // 列設定に従って行データを生成
    $row_data = [];
    foreach ($active_columns as $col_id) {
        switch ($col_id) {
            case 'num':          $row_data[] = $num; break;
            case 'songfile':     $row_data[] = $songname; break;
            case 'keychange':    $row_data[] = $row["keychange"]; break;
            case 'program_name': $row_data[] = $program_name; break;
            case 'artist':       $row_data[] = $artist; break;
            case 'singer':       $row_data[] = $row["singer"]; break;
            case 'comment':      $row_data[] = $row["comment"]; break;
            case 'worker':       $row_data[] = $worker; break;
        }
    }
    $csvarray[] = $row_data;
    $num++;
}

$stream = fopen('php://output', 'wb');
fwrite($stream, pack('C*', 0xEF, 0xBB, 0xBF)); // BOM
fwrite($stream, arr2csv($csvarray));
fclose($stream);
?>
