<?php
require_once 'commonfunc.php';
header('Content-Type: application/json');

/* lister 列が存在するか確認 (古いDBへの互換) */
$has_lister = false;
try {
    $t = $db->query("SELECT lister_work FROM requesttable LIMIT 0");
    $has_lister = ($t !== false);
} catch (Exception $e) {}

$cols = 'id, songfile, song_name, singer, secret, kind, duration'
      . ($has_lister ? ', lister_work, lister_op_ed' : '');

function _qrow($row, $config_ini, $has_lister) {
    $is_secret   = (int)$row['secret'] === 1;
    $secret_text = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
    $sn = $row['song_name'] ?: '';
    $sf = $row['songfile']  ?: '';
    return [
        'title'        => $is_secret ? $secret_text : ($sn ?: $sf),
        'singer'       => $is_secret ? '' : ($row['singer'] ?: ''),
        'kind'         => $row['kind'] ?: '',
        'duration'     => (int)($row['duration'] ?? 0),
        'lister_work'  => (!$is_secret && $has_lister) ? ($row['lister_work']  ?? '') : '',
        'lister_op_ed' => (!$is_secret && $has_lister) ? ($row['lister_op_ed'] ?? '') : '',
    ];
}

$playing     = null;
$queue       = [];
$history     = [];
$total_sec   = 0;
$known_count = 0;

try {
    /* 再生中 */
    $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying = '再生中' ORDER BY reqorder ASC LIMIT 1");
    if ($sel) {
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        $sel->closeCursor();
        if ($row) $playing = _qrow($row, $config_ini, $has_lister);
    }

    /* 未再生の合計時間 */
    $sel = $db->query(
        "SELECT COALESCE(SUM(duration),0) AS ts,
                SUM(CASE WHEN duration>0 THEN 1 ELSE 0 END) AS kc
         FROM requesttable WHERE nowplaying='未再生'"
    );
    if ($sel) {
        $r = $sel->fetch(PDO::FETCH_ASSOC);
        $sel->closeCursor();
        if ($r) { $total_sec = (int)$r['ts']; $known_count = (int)$r['kc']; }
    }

    /* 未再生キュー (最大30件) */
    $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying='未再生' ORDER BY reqorder ASC LIMIT 30");
    if ($sel) {
        while ($row = $sel->fetch(PDO::FETCH_ASSOC)) $queue[] = _qrow($row, $config_ini, $has_lister);
        $sel->closeCursor();
    }

    /* 歌唱履歴 直近8件 (再生済み・停止中) */
    $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying IN ('再生済み','停止中') ORDER BY reqorder DESC LIMIT 8");
    if ($sel) {
        while ($row = $sel->fetch(PDO::FETCH_ASSOC)) $history[] = _qrow($row, $config_ini, $has_lister);
        $sel->closeCursor();
    }
} catch (Exception $e) { /* silent */ }

echo json_encode([
    'playing'     => $playing,
    'queue'       => $queue,
    'total'       => count($queue),
    'queue_sec'   => $total_sec,
    'known_count' => $known_count,
    'history'     => $history,
]);
