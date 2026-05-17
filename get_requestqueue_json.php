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

echo json_encode(['playing' => $playing, 'queue' => $queue, 'total' => count($queue)]);
