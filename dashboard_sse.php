<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();
require_once 'func_playerprogress.php';

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');    // Nginx unbuffered
header('Connection: keep-alive');

/* 出力バッファを完全にフラッシュ */
while (ob_get_level()) ob_end_clean();

set_time_limit(0);
ignore_user_abort(true);

/* lister 列の有無チェック */
$has_lister = false;
try {
    $t = $db->query("SELECT lister_work FROM requesttable LIMIT 0");
    $has_lister = ($t !== false);
} catch (Exception $e) {}

$cols = 'id, songfile, song_name, singer, secret, kind, duration'
      . ($has_lister ? ', lister_work, lister_op_ed' : '');

function _sse_qrow($row, $config_ini, $has_lister) {
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

function _sse_emit($event, $data, &$eid) {
    echo "id: " . (++$eid) . "\n";
    echo "event: " . $event . "\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
}

/* 接続確認用コメント */
echo ": connected\n\n";
flush();

$last_queue_hash = '';
$last_key_hash   = '';  // title/state 変化検出用 (status は毎回送信)
$event_id        = 0;
$heartbeat_tick  = 0;

while (true) {
    if (connection_aborted()) break;

    /* ---- プレイヤーステータス ---- */
    $playstat = new PlayerProgress;
    if ($playstat->getstatus()) {
        $stat = [
            'playtime_txt'  => $playstat->playtime_txt,
            'totaltime_txt' => $playstat->totaltime_txt,
            'playtime'      => $playstat->playtime,
            'totaltime'     => $playstat->totaltime,
            'status'        => $playstat->status,
            'playingtitle'  => $playstat->playingtitle,
            'playingfile'   => $playstat->playingfile,
        ];
    } else {
        $stat = [
            'playtime_txt' => '--:--', 'totaltime_txt' => '--:--',
            'playtime' => 0, 'totaltime' => 0, 'status' => 0,
            'playingtitle' => '', 'playingfile' => '',
        ];
    }
    /* status は常に送信 (プログレスバー更新のため) */
    _sse_emit('status', $stat, $event_id);

    /* ---- キュー ---- */
    $playing   = null;
    $queue     = [];
    $history   = [];
    $total_sec = 0;
    $known_cnt = 0;
    try {
        $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying='再生中' ORDER BY reqorder ASC LIMIT 1");
        if ($sel) { $row = $sel->fetch(PDO::FETCH_ASSOC); $sel->closeCursor();
                    if ($row) $playing = _sse_qrow($row, $config_ini, $has_lister); }

        $sel = $db->query("SELECT COALESCE(SUM(duration),0) AS ts, SUM(CASE WHEN duration>0 THEN 1 ELSE 0 END) AS kc FROM requesttable WHERE nowplaying='未再生'");
        if ($sel) { $r = $sel->fetch(PDO::FETCH_ASSOC); $sel->closeCursor();
                    if ($r) { $total_sec = (int)$r['ts']; $known_cnt = (int)$r['kc']; } }

        $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying='未再生' ORDER BY reqorder ASC LIMIT 30");
        if ($sel) { while ($row = $sel->fetch(PDO::FETCH_ASSOC)) $queue[] = _sse_qrow($row, $config_ini, $has_lister); $sel->closeCursor(); }

        $sel = $db->query("SELECT $cols FROM requesttable WHERE nowplaying IN ('再生済','再生済？','停止中') ORDER BY reqorder DESC LIMIT 8");
        if ($sel) { while ($row = $sel->fetch(PDO::FETCH_ASSOC)) $history[] = _sse_qrow($row, $config_ini, $has_lister); $sel->closeCursor(); }
    } catch (Exception $e) {}

    $queue_data = [
        'playing'     => $playing,
        'queue'       => $queue,
        'total'       => count($queue),
        'queue_sec'   => $total_sec,
        'known_count' => $known_cnt,
        'history'     => $history,
    ];
    $queue_hash = md5(json_encode($queue_data));
    if ($queue_hash !== $last_queue_hash) {
        $last_queue_hash = $queue_hash;
        _sse_emit('queue', $queue_data, $event_id);
    }

    /* ハートビート: 30秒ごとに接続維持コメント */
    $heartbeat_tick++;
    if ($heartbeat_tick >= 15) {
        echo ": heartbeat\n\n";
        flush();
        $heartbeat_tick = 0;
    }

    sleep(2);
}
