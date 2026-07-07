<?php
/**
 * /api/player.php
 *
 * プレイヤー統一制御 API。
 * MPC-BE (mpcctrl_func.php) と foobar2000 (foobar_func.php) の差を吸収し、
 * モバイルアプリからプレイヤー種別を意識せず操作できるようにする。
 *
 * プレイヤーの決定:
 *   playerctrl_portal.php と同じく getcurrentplayer() (再生中/開始待ちの曲の
 *   拡張子から判定) を使う。foobar なら foobar、それ以外 (mpc / none) は mpc。
 *   `player=mpc|foobar` パラメータで明示上書きも可能。
 *
 * 注意: mpcctrl_func.php と foobar_func.php はどちらも songnext() を定義して
 *       いるため、判定後に該当する方だけを require する。
 *
 * パラメータ:
 *   action (必須) 下表参照
 *   player (任意) mpc | foobar  (省略時は自動判定)
 *   value  (volume_set 時) 音量 0〜100
 *   key    (keychange 時) キー変更コマンド
 *
 *   action       | mpc                     | foobar
 *   -------------|-------------------------|------------------------
 *   info         | プレイヤー種別と playmode を返す (両対応)
 *   next         | songnext()              | songnext()
 *   start        | songstart()             | foobar_songstart()
 *   play         | command_mpc(887) 再生   | foobar_song_play()
 *   pause        | command_mpc(888) 一時停止| (非対応: トグルのみ)
 *   playpause    | command_mpc(889) トグル | foobar_song_pause()
 *   stop         | command_mpc(890) 停止   | (非対応: foobar の停止は
 *                |   ※DBは触らない        |  曲終了込みのため next を使う)
 *   start_first  | start_first_mpc() 曲頭から | foobar_song_restart()
 *   seek_back    | command_mpc(901) 前へ(中) | (非対応)
 *   seek_forward | command_mpc(902) 次へ(中) | (非対応)
 *   seek_back_large    | command_mpc(903) 前へ(大) | (非対応)
 *   seek_forward_large | command_mpc(904) 次へ(大) | (非対応)
 *   audiodelay_up   | 905 音ズレ+10ms (step=100 で +100ms) | (非対応)
 *   audiodelay_down | 906 音ズレ-10ms (step=100 で -100ms) | (非対応)
 *   audiotrack_next | command_mpc(952) 音声トラック切替 | (非対応)
 *   subtitle_toggle | command_mpc(956) 字幕ON/OFF | (非対応)
 *   fullscreen   | command_mpc(830)        | (非対応)
 *   speed_down / speed_up / speed_normal | 894 / 895 / 896 再生スピード | (非対応)
 *   size_small / size_normal / size_large | 863 / 861 / 862 表示サイズ | (非対応)
 *   d3d_fullscreen | command_mpc(1023)     | (非対応)
 *   mirror       | command_mpc(880) 左右反転| (非対応)
 *   show_time    | command_mpc(1036) 時刻表示| (非対応)
 *   command      | command_mpc(value) 汎用 | (非対応)
 *                |   ※value=wm_command番号。名前付きにない操作の逃がし
 *
 *   fadeout      | volume_fadeout()        | (非対応)
 *   mute         | toggle_mute_mpc()       | (非対応)
 *   volume_get   | get_volume()            | (非対応)
 *   volume_set   | set_volume(value)       | (非対応)
 *   volume_up    | 現在音量 +5             | foobar_song_vup()
 *   volume_down  | 現在音量 -5             | foobar_song_vdown()
 *   volume_reset | 曲開始時の初期音量に戻す | (非対応)
 *                |   (startvolume + 制作者別オフセット)
 *   keychange    | keychange(key)          | (非対応)
 *   comp_get / comp_up / comp_down / comp_reset
 *                | 字幕補正 (白飛び対策)。レベルは comp_level で返す | (非対応)
 *                |   (mpcctrl_bs5.php?cmd=comp_* と同じ実装を共用)
 *
 * 応答: { "ok":true, "data": { "player":"mpc", "action":"...", ... } }
 *       非対応操作は 501 + {"ok":false,"error":"not supported ..."}
 */
require_once __DIR__ . '/_common.php';

$valid_actions = ['info', 'next', 'start', 'play', 'pause', 'playpause', 'stop', 'start_first',
                  'seek_back', 'seek_forward', 'seek_back_large', 'seek_forward_large',
                  'audiodelay_up', 'audiodelay_down', 'audiotrack_next', 'subtitle_toggle',
                  'fullscreen', 'speed_down', 'speed_up', 'speed_normal',
                  'size_small', 'size_normal', 'size_large',
                  'd3d_fullscreen', 'mirror', 'show_time',
                  'command', 'fadeout', 'mute',
                  'volume_get', 'volume_set', 'volume_up', 'volume_down', 'volume_reset',
                  'keychange', 'comp_get', 'comp_up', 'comp_down', 'comp_reset'];

// MPC 専用の名前付きアクション → wm_command 番号
// (BS3 mpcctrl.js / BS5 mpcctrl_bs5.php 詳細設定と同じ番号)
$mpc_simple_commands = [
    'stop'               => 890,
    'seek_back'          => 901,
    'seek_forward'       => 902,
    'seek_back_large'    => 903,
    'seek_forward_large' => 904,
    'audiotrack_next'    => 952,
    'subtitle_toggle'    => 956,
    'fullscreen'         => 830,
    // ---- BS5 詳細設定パネル相当 ----
    'speed_down'         => 894,   // 再生スピードダウン
    'speed_up'           => 895,   // 再生スピードアップ
    'speed_normal'       => 896,   // 標準スピード
    'size_small'         => 863,   // サイズ縮小
    'size_normal'        => 861,   // サイズ標準
    'size_large'         => 862,   // サイズ拡大
    'd3d_fullscreen'     => 1023,  // D3Dフルスクリーン (重い場合用)
    'mirror'             => 880,   // 左右反転
    'show_time'          => 1036,  // 時刻表示
];

$action = (string)api_param('action', '');
if (!in_array($action, $valid_actions, true)) {
    api_error('invalid action (' . implode('|', $valid_actions) . ')');
}

// プレイヤー決定 (playerctrl_portal.php と同じ判定 + 明示上書き)
$player = (string)api_param('player', '');
if ($player !== '' && !in_array($player, ['mpc', 'foobar'], true)) {
    api_error('invalid player (mpc|foobar)');
}
$detected = getcurrentplayer();  // 'mpc' | 'foobar' | 'none'
if ($player === '') {
    $player = ($detected === 'foobar') ? 'foobar' : 'mpc';
}

if ($action === 'info') {
    api_ok([
        'player'   => $player,
        'detected' => $detected,
        'playmode' => (int)($config_ini['playmode'] ?? 3),
    ]);
}

/** 非対応操作の統一エラー */
function api_unsupported($action, $player)
{
    api_error("not supported: action '{$action}' for player '{$player}'", 501);
}

// 同名関数の衝突を避けるため、対象プレイヤーの関数群だけ読み込む
if ($player === 'foobar') {
    require_once __DIR__ . '/../foobar_func.php';
} else {
    require_once __DIR__ . '/../mpcctrl_func.php';
    // 音量初期値リセット / 字幕補正 (mpcctrl_bs5.php と共用、command_mpc に依存)
    require_once __DIR__ . '/../function_playeradjust.php';
}

// 既存関数は結果を print するため出力を捕捉し、JSON の message に載せる
ob_start();
$extra = [];
try {
    switch ($action) {
        case 'next':
            songnext();  // mpc/foobar 双方に同名の実装がある (DB更新)
            break;

        case 'start':
            if ($player === 'foobar') {
                foobar_songstart();
            } else {
                songstart();
            }
            break;

        case 'play':
            // 887 = 再生 (非トグル)
            if ($player === 'foobar') {
                foobar_song_play();
            } else {
                command_mpc(887);
            }
            break;

        case 'pause':
            // 888 = 一時停止 (非トグル)。foobar は PlayOrPause トグルしかないため非対応
            if ($player === 'mpc') {
                command_mpc(888);
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'playpause':
            // 889 = Play/Pause トグル (BS3 UI の mpcctrl.js song_pause() と同じ)
            if ($player === 'foobar') {
                foobar_song_pause();
            } else {
                command_mpc(889);
            }
            break;

        case 'stop':
        case 'seek_back':
        case 'seek_forward':
        case 'seek_back_large':
        case 'seek_forward_large':
        case 'audiotrack_next':
        case 'subtitle_toggle':
        case 'fullscreen':
        case 'speed_down':
        case 'speed_up':
        case 'speed_normal':
        case 'size_small':
        case 'size_normal':
        case 'size_large':
        case 'd3d_fullscreen':
        case 'mirror':
        case 'show_time':
            // MPC 専用の単純コマンド (シーク系 901-904 は command_mpc() 内で
            // 再生位置の通知更新も行われる)
            if ($player === 'mpc') {
                command_mpc($mpc_simple_commands[$action]);
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'start_first':
            // 曲頭から再生し直す
            if ($player === 'foobar') {
                foobar_song_restart();
            } else {
                start_first_mpc();
            }
            break;

        case 'audiodelay_up':
        case 'audiodelay_down':
            // 音ズレ補正。既定 ±10ms、step=100 で ±100ms (mpcctrl_bs5 の delayp100/delaym100 相当)
            if ($player === 'mpc') {
                $step = (int)api_param('step', 10);
                if ($action === 'audiodelay_up') {
                    if ($step === 100) { delay_plus100_mpc(); } else { command_mpc(905); }
                } else {
                    if ($step === 100) { delay_minus100_mpc(); } else { command_mpc(906); }
                }
                $extra['step'] = ($step === 100) ? 100 : 10;
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'command':
            // 汎用パススルー: 名前付きアクションにない wm_command を直接送る (MPC 専用)
            if ($player === 'mpc') {
                $num = api_param('value');
                if ($num === null || !is_numeric($num)) {
                    ob_end_clean();
                    api_error('value (wm_command number) is required');
                }
                command_mpc((int)$num);
                $extra['command'] = (int)$num;
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'fadeout':
            if ($player === 'mpc') {
                volume_fadeout();
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'mute':
            if ($player === 'mpc') {
                toggle_mute_mpc();
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'volume_get':
            if ($player === 'mpc') {
                $vol = get_volume();
                if ($vol === false) {
                    ob_end_clean();
                    api_error('player not reachable', 502);
                }
                $extra['volume'] = (int)$vol;
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'volume_set':
            if ($player === 'mpc') {
                $val = api_param('value');
                if ($val === null || !is_numeric($val)) {
                    ob_end_clean();
                    api_error('value (0-100) is required');
                }
                $val = max(0, min(100, (int)$val));
                set_volume($val);
                $extra['volume'] = $val;
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'volume_up':
        case 'volume_down':
            $delta = ($action === 'volume_up') ? 5 : -5;
            if ($player === 'foobar') {
                if ($delta > 0) {
                    foobar_song_vup();
                } else {
                    foobar_song_vdown();
                }
            } else {
                $vol = get_volume();
                if ($vol === false) {
                    ob_end_clean();
                    api_error('player not reachable', 502);
                }
                $newvol = max(0, min(100, (int)$vol + $delta));
                set_volume($newvol);
                $extra['volume'] = $newvol;
            }
            break;

        case 'volume_reset':
            // 曲開始時の初期音量 (startvolume + 制作者別オフセット) に戻す
            if ($player === 'mpc') {
                $extra['volume'] = reset_initial_volume_bs5();
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'comp_get':
        case 'comp_up':
        case 'comp_down':
        case 'comp_reset':
            // 字幕補正 (白飛び対策)。レベルは player_compensation.json に永続化される
            if ($player === 'mpc') {
                switch ($action) {
                    case 'comp_up':    $level = player_compensation_inc();   break;
                    case 'comp_down':  $level = player_compensation_dec();   break;
                    case 'comp_reset': $level = player_compensation_reset(); break;
                    default:           $level = get_player_compensation_level();
                }
                $extra['comp_level'] = $level;
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;

        case 'keychange':
            if ($player === 'mpc') {
                $key = (string)api_param('key', '');
                if ($key === '') {
                    ob_end_clean();
                    api_error('key is required');
                }
                keychange($key);
            } else {
                ob_end_clean();
                api_unsupported($action, $player);
            }
            break;
    }
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    api_error('player control failed', 500);
}
$message = trim(strip_tags(ob_get_clean()));

api_ok(array_merge([
    'player'  => $player,
    'action'  => $action,
    'message' => $message,
], $extra));
