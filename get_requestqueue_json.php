<?php
require_once 'commonfunc.php';
header('Content-Type: application/json');

$playing = null;
$queue   = [];

try {
    /* 再生中 */
    $sql = "SELECT id, songfile, song_name, singer, secret, kind
            FROM requesttable WHERE nowplaying = '再生中'
            ORDER BY reqorder ASC LIMIT 1";
    $sel = $db->query($sql);
    if ($sel) {
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        $sel->closeCursor();
        if ($row) {
            $is_secret   = (int)$row['secret'] === 1;
            $secret_text = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
            $sn = $row['song_name'] ?: '';
            $sf = $row['songfile']  ?: '';
            $playing = [
                'title'  => $is_secret ? $secret_text : ($sn ?: $sf),
                'singer' => $is_secret ? '' : ($row['singer'] ?: ''),
                'kind'   => $row['kind'] ?: '',
            ];
        }
    }

    /* 未再生キューの残総時間 (duration = 秒、0は未取得) */
    $sel_dur = $db->query(
        "SELECT COALESCE(SUM(duration), 0) AS total_sec,
                COUNT(*) AS total_count,
                SUM(CASE WHEN duration > 0 THEN 1 ELSE 0 END) AS known_count
         FROM requesttable WHERE nowplaying = '未再生'"
    );
    $total_sec   = 0;
    $total_count = 0;
    $known_count = 0;
    if ($sel_dur) {
        $dur_row = $sel_dur->fetch(PDO::FETCH_ASSOC);
        $sel_dur->closeCursor();
        if ($dur_row) {
            $total_sec   = (int)$dur_row['total_sec'];
            $total_count = (int)$dur_row['total_count'];
            $known_count = (int)$dur_row['known_count'];
        }
    }

    /* 未再生キュー (最大30件) */
    $sql = "SELECT id, songfile, song_name, singer, secret, kind
            FROM requesttable WHERE nowplaying = '未再生'
            ORDER BY reqorder ASC LIMIT 30";
    $sel = $db->query($sql);
    if ($sel) {
        while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
            $is_secret   = (int)$row['secret'] === 1;
            $secret_text = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
            $sn = $row['song_name'] ?: '';
            $sf = $row['songfile']  ?: '';
            $queue[] = [
                'title'  => $is_secret ? $secret_text : ($sn ?: $sf),
                'singer' => $is_secret ? '' : ($row['singer'] ?: ''),
                'kind'   => $row['kind'] ?: '',
            ];
        }
        $sel->closeCursor();
    }
} catch (Exception $e) { /* silent */ }

echo json_encode([
    'playing'      => $playing,
    'queue'        => $queue,
    'total'        => count($queue),
    'queue_sec'    => $total_sec,   /* 未再生の合計秒数 (duration既知分のみ) */
    'known_count'  => $known_count, /* duration取得済み曲数 */
]);
