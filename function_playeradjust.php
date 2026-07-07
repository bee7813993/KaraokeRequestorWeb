<?php
/**
 * function_playeradjust.php
 *
 * プレイヤー調整系 (音量初期値リセット / 字幕補正) の共通実装。
 * mpcctrl_bs5.php と /api/player.php で共用する。
 *
 * 注意: command_mpc() / set_volume() / save_applied_volume_offset() を使うため、
 *       mpcctrl_func.php を require した後に読み込むこと。
 *       (foobar_func.php と同名関数が衝突するため、ここでは require しない)
 */

/* ボリュームを再生開始時の初期値に戻す
   manage-mpc.php の曲開始時ロジックと同じ: startvolume + 制作者別オフセット */
function reset_initial_volume_bs5() {
    global $db, $config_ini;
    $offset = 0;
    try {
        $sql = "SELECT volume FROM requesttable WHERE nowplaying = '再生中' ORDER BY reqorder ASC LIMIT 1";
        $select = $db->query($sql);
        if ($select) {
            $row = $select->fetch(PDO::FETCH_ASSOC);
            $select->closeCursor();
            if ($row && isset($row['volume']) && $row['volume'] !== '' && $row['volume'] !== null) {
                $v = intval($row['volume']);
                if ($v >= -100 && $v <= 100) $offset = $v;
            }
        }
    } catch (Exception $e) { /* silent */ }
    $base = 50;
    if (array_key_exists('startvolume', $config_ini) && is_numeric(trim(urldecode((string)$config_ini['startvolume'])))) {
        $base = max(0, min(100, intval(urldecode((string)$config_ini['startvolume']))));
    }
    $applied = max(0, min(100, $base + $offset));
    set_volume($applied);
    // 手動リセット後も曲終了時の差し戻しが正しく効くよう適用量を記録する
    save_applied_volume_offset($applied - $base);
    return $applied;
}

/* ---- 映像補正（字幕の白飛び対策） ---- */
/* レベルを player_compensation.json に永続化し、MPC の
   明るさ/コントラスト/彩度 を一括調整する。
   +1 = 明るさDOWN(985) + コントラストDOWN(987) + 彩度UP(990)
   -1 = 反対方向: 984 + 986 + 991
   範囲: -COMP_MAX .. +COMP_MAX (各 MPC ステップ分の累積)
   ボタン1クリックで COMP_STEP ステップ動かす。
   ※ MPC-BE のコマンドID。MPC-HC とは番号が異なる。 */
define('COMP_MAX',  50);
define('COMP_STEP', 5);
function _player_compensation_file() {
    return __DIR__ . '/player_compensation.json';
}
function get_player_compensation_level() {
    $f = _player_compensation_file();
    if (!file_exists($f)) return 0;
    $d = @json_decode(@file_get_contents($f), true);
    if (!is_array($d) || !isset($d['level'])) return 0;
    return max(-COMP_MAX, min(COMP_MAX, intval($d['level'])));
}
function save_player_compensation_level($level) {
    $level = max(-COMP_MAX, min(COMP_MAX, intval($level)));
    @file_put_contents(_player_compensation_file(), json_encode(['level' => $level]));
    return $level;
}
function _compensation_step_stronger() {
    command_mpc(985); // 明るさを下げる
    command_mpc(987); // コントラストを下げる
    command_mpc(990); // 彩度を上げる
}
function _compensation_step_weaker() {
    command_mpc(984); // 明るさを上げる
    command_mpc(986); // コントラストを上げる
    command_mpc(991); // 彩度を下げる
}
/** 補正レベルを COMP_STEP だけ強める。新しいレベルを返す */
function player_compensation_inc() {
    $cur = get_player_compensation_level();
    $new = save_player_compensation_level($cur + COMP_STEP);
    $diff = $new - $cur;
    for ($i = 0; $i < $diff; $i++) _compensation_step_stronger();
    return $new;
}
/** 補正レベルを COMP_STEP だけ弱める。新しいレベルを返す */
function player_compensation_dec() {
    $cur = get_player_compensation_level();
    $new = save_player_compensation_level($cur - COMP_STEP);
    $diff = $cur - $new;
    for ($i = 0; $i < $diff; $i++) _compensation_step_weaker();
    return $new;
}
/** 補正レベルを 0 に戻し、プレイヤーのカラー設定もリセットする */
function player_compensation_reset() {
    save_player_compensation_level(0);
    command_mpc(992); // カラー設定をリセット
    return 0;
}
function apply_player_compensation_full() {
    $level = get_player_compensation_level();
    command_mpc(992); // カラー設定をリセット
    if ($level > 0) {
        for ($i = 0; $i < $level; $i++) _compensation_step_stronger();
    } elseif ($level < 0) {
        for ($i = 0; $i < abs($level); $i++) _compensation_step_weaker();
    }
    return $level;
}
