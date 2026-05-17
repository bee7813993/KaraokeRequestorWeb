<?php
require_once 'mpcctrl_func.php';

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
    $base = isset($config_ini['startvolume']) ? intval($config_ini['startvolume']) : 50;
    $applied = max(0, min(100, $base + $offset));
    set_volume($applied);
    print $applied;
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

/* ---- AJAX / コマンドリクエスト処理 ---- */
if (!empty($_REQUEST['songnext'])) {
    songnext();
    die();
}
if (array_key_exists('songstart', $_REQUEST)) {
    songstart();
    die();
}
if (array_key_exists('fadeout', $_REQUEST)) {
    volume_fadeout();
    die();
}
if (array_key_exists('cmd', $_REQUEST)) {
    $l_cmd = $_REQUEST['cmd'];
    if ($l_cmd === 'delayp100')        { delay_plus100_mpc(); }
    elseif ($l_cmd === 'delaym100')    { delay_minus100_mpc(); }
    elseif ($l_cmd === 'start_first')  { start_first_mpc(); }
    elseif ($l_cmd === 'reset_volume') { reset_initial_volume_bs5(); }
    elseif ($l_cmd === 'comp_inc') {
        $cur = get_player_compensation_level();
        $new = save_player_compensation_level($cur + COMP_STEP);
        $diff = $new - $cur;
        for ($i = 0; $i < $diff; $i++) _compensation_step_stronger();
        header('Content-Type: application/json');
        echo json_encode(['level' => $new]);
    }
    elseif ($l_cmd === 'comp_dec') {
        $cur = get_player_compensation_level();
        $new = save_player_compensation_level($cur - COMP_STEP);
        $diff = $cur - $new;
        for ($i = 0; $i < $diff; $i++) _compensation_step_weaker();
        header('Content-Type: application/json');
        echo json_encode(['level' => $new]);
    }
    elseif ($l_cmd === 'comp_reset') {
        save_player_compensation_level(0);
        command_mpc(992); // カラー設定をリセット
        header('Content-Type: application/json');
        echo json_encode(['level' => 0]);
    }
    elseif ($l_cmd === 'comp_apply') {
        $level = apply_player_compensation_full();
        header('Content-Type: application/json');
        echo json_encode(['level' => $level]);
    }
    elseif ($l_cmd === 'comp_get') {
        header('Content-Type: application/json');
        echo json_encode(['level' => get_player_compensation_level()]);
    }
    elseif ($l_cmd === 'get_volume') {
        $vol = get_volume();
        header('Content-Type: application/json');
        echo json_encode(['volume' => (int)$vol]);
    }
    elseif ($l_cmd === 'set_volume') {
        $val = isset($_REQUEST['val']) ? max(0, min(100, intval($_REQUEST['val']))) : 50;
        set_volume($val);
        header('Content-Type: application/json');
        echo json_encode(['volume' => $val]);
    }
    else { $r = command_mpc($l_cmd); print $r; }
    die();
}
if (array_key_exists('key', $_REQUEST)) {
    keychange($_REQUEST['key']);
    die();
}

/* ---- 再生状態取得 ---- */
require_once 'func_playerprogress.php';
$playstat = new PlayerProgress;
$has_song = $playstat->getstatus();

$prog_pct    = ($has_song && $playstat->totaltime > 0)
               ? (float)$playstat->playtime / (float)$playstat->totaltime * 100
               : 0;
$time_cur    = $has_song ? htmlspecialchars($playstat->playtime_txt, ENT_QUOTES, 'UTF-8') : '--:--:--';
$time_total  = $has_song ? htmlspecialchars($playstat->totaltime_txt, ENT_QUOTES, 'UTF-8') : '--:--:--';
$song_title  = ($has_song && !empty($playstat->playingtitle))
               ? htmlspecialchars($playstat->playingtitle, ENT_QUOTES, 'UTF-8')
               : '';
// state: 2=再生中, 1=一時停止
$state_num   = $has_song ? (int)$playstat->status : 0;

/* ---- 設定フラグ ---- */
$usekeychange    = isset($config_ini['usekeychange']) && $config_ini['usekeychange'] == 1;
$moviefullscreen = isset($moviefullscreen) ? (int)$moviefullscreen : 0;

/* ---- 次の曲 ---- */
$next_song = null;
try {
    $sql_next = "SELECT id, songfile, song_name, singer, secret, kind, fullpath FROM requesttable WHERE nowplaying = '未再生' ORDER BY reqorder ASC LIMIT 1";
    $sel_next = $db->query($sql_next);
    if ($sel_next) {
        $row_next = $sel_next->fetch(PDO::FETCH_ASSOC);
        $sel_next->closeCursor();
        if ($row_next) {
            $is_secret_next = (int)$row_next['secret'] === 1;
            $sn_raw = $row_next['song_name'] ?? '';

            // 未保存なら表示時に ListerDB を参照し、見つかれば requesttable も更新
            if (empty($sn_raw)
                && !$is_secret_next
                && !empty($row_next['fullpath'])
                && array_key_exists('listerDBPATH', $config_ini)) {
                $lister_dbpath = urldecode($config_ini['listerDBPATH']);
                if (file_exists($lister_dbpath)) {
                    require_once 'function_search_listerdb.php';
                    $info = listerdb_lookup_songinfo($row_next['fullpath'], $lister_dbpath);
                    if ($info && !empty($info['song_name'])) {
                        $sn_raw = $info['song_name'];
                        $rid = (int)$row_next['id'];
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

            $sf = htmlspecialchars($row_next['songfile'], ENT_QUOTES, 'UTF-8');
            $sn = $sn_raw !== '' ? htmlspecialchars($sn_raw, ENT_QUOTES, 'UTF-8') : '';
            $next_song = [
                'title'    => $is_secret_next ? 'ヒ・ミ・ツ♪' : ($sn ?: $sf),
                'songfile' => $is_secret_next ? '' : $sf,
                'show_file'=> !$is_secret_next && $sn !== '' && $sn !== $sf,
                'singer'   => $is_secret_next ? '' : htmlspecialchars($row_next['singer'], ENT_QUOTES, 'UTF-8'),
                'kind'     => htmlspecialchars($row_next['kind'], ENT_QUOTES, 'UTF-8'),
            ];
        }
    }
} catch (Exception $e) { /* silent */ }

/* ---- SVGアイコン定義 ---- */
function _svg($path_d, $w = 20, $h = 20) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '"'
         . ' fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">' . $path_d . '</svg>';
}
$ic_play      = _svg('<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>');
$ic_pause     = _svg('<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>');
$ic_skip_s    = _svg('<path d="M4 4a.5.5 0 0 1 1 0v3.248l6.267-3.636c.54-.313 1.233.066 1.233.696v7.384c0 .63-.693 1.01-1.233.697L5 8.753V12a.5.5 0 0 1-1 0V4z"/>');
$ic_skip_e    = _svg('<path d="M12.5 4a.5.5 0 0 0-1 0v3.248L5.233 3.612C4.693 3.3 4 3.678 4 4.308v7.384c0 .63.693 1.01 1.233.697L11.5 8.753V12a.5.5 0 0 0 1 0V4z"/>');
$ic_rwd       = _svg('<path d="M8.404 7.304a.802.802 0 0 0 0 1.392l6.363 3.692c.52.302 1.233-.043 1.233-.696V4.308c0-.653-.713-.998-1.233-.696L8.404 7.304Z"/><path d="M.404 7.304a.802.802 0 0 0 0 1.392l6.363 3.692c.52.302 1.233-.043 1.233-.696V4.308c0-.653-.713-.998-1.233-.696L.404 7.304Z"/>');
$ic_fwd       = _svg('<path d="M7.596 7.304a.802.802 0 0 1 0 1.392l-6.363 3.692C.713 12.69 0 12.345 0 11.692V4.308c0-.653.713-.998 1.233-.696l6.363 3.692Z"/><path d="M15.596 7.304a.802.802 0 0 1 0 1.392l-6.363 3.692c-.52.302-1.233-.043-1.233-.696V4.308c0-.653.713-.998 1.233-.696l6.363 3.692Z"/>');
$ic_chev_l    = _svg('<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>');
$ic_chev_r    = _svg('<path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>');
$ic_vol_d     = _svg('<path d="M9 1a.5.5 0 0 0-.812-.39L4.825 3.5H2.5A.5.5 0 0 0 2 4v8a.5.5 0 0 0 .5.5h2.325l3.363 2.89A.5.5 0 0 0 9 15V1zm-4.5 4.5h.325l.5-.5V4h1V3.39L9 2.028V13.97L6.325 12.5H6v-.5H5.5L4.5 11V5.5zM11.5 8a3.5 3.5 0 0 1-.5 1.774v-3.55A3.5 3.5 0 0 1 11.5 8z"/>');
$ic_vol_u     = _svg('<path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/><path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z"/><path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 0 9.025 8 3.49 3.49 0 0 0 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/>');
$ic_fade      = _svg('<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>');
$ic_vol_reset = _svg('<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>');
$ic_music     = _svg('<path d="M9 3a1 1 0 0 1 1-1h6v10.5a1.5 1.5 0 0 1-3 0v-.5a1.5 1.5 0 0 0-3 0v.5a1.5 1.5 0 0 1-3 0V1a1 1 0 0 1 1-1h.5v7h1V0H9v3z"/>');
$ic_arrow_u   = _svg('<path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 0 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>');
$ic_arrow_d   = _svg('<path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>');

/* 再生/一時停止ボタンの初期状態 */
$paypause_icon  = ($state_num == 2) ? $ic_pause : $ic_play;
$playpause_lbl  = ($state_num == 2) ? '一時停止'  : '再開';
$playpause_cls  = ($state_num == 2) ? 'player-btn-playpause' : 'btn-outline-primary';
?>

<script src="mpcctrl.js"></script>
<script src="js/player_bs5.js"></script>

<!-- === Now Playing カード === -->
<div class="card player-nowplaying mb-3" id="proglessbase">
  <div class="card-body p-3">
    <div class="d-flex align-items-start gap-2 mb-2">
      <span class="player-status-badge badge <?= $state_num == 2 ? 'bg-success' : ($state_num == 1 ? 'bg-warning text-dark' : 'bg-secondary') ?>"
            id="player-status-badge">
        <?= $state_num == 2 ? '再生中' : ($state_num == 1 ? '一時停止' : '停止中') ?>
      </span>
      <span class="player-kind-badge">MPC</span>
    </div>
    <!-- mpcctrl.js 互換用（非表示）: mpcctrl.js がここを書き換えるため残す -->
    <div id="songtitle" style="display:none;" aria-hidden="true"></div>
    <!-- BS5 表示用: player_bs5.js が song_name で更新する -->
    <div id="player-title-display">
      <?php if ($song_title): ?>
        <div class="player-label">Now Playing</div>
        <div class="player-title"><?= $song_title ?></div>
      <?php else: ?>
        <div class="player-title text-muted" style="opacity:.5;">曲が選択されていません</div>
      <?php endif; ?>
    </div>
    <div class="progress mt-3 mb-2" style="height:6px;" role="progressbar"
         aria-valuenow="<?= round($prog_pct) ?>" aria-valuemin="0" aria-valuemax="100">
      <div class="progress-bar" id="divprogress" style="width:<?= $prog_pct ?>%;"></div>
    </div>
    <div class="d-flex justify-content-between player-time">
      <span id="time"><?= $time_cur ?></span>
      <span id="total"><?= $time_total ?></span>
    </div>
  </div>
</div>

<?php if ($next_song): ?>
<!-- === 次の曲カード === -->
<div class="card player-nextsong mb-3">
  <div class="card-body py-2 px-3">
    <div class="player-label">Next</div>
    <div class="player-nextsong-title"><?= $next_song['title'] ?></div>
    <?php if ($next_song['singer']): ?>
    <div class="player-nextsong-singer"><?= $next_song['singer'] ?></div>
    <?php endif; ?>
    <?php if ($next_song['show_file']): ?>
    <div class="player-nextsong-file"><?= $next_song['songfile'] ?></div>
    <?php endif; ?>
    <?php if (!empty($next_song['kind'])): ?>
    <div class="player-nextsong-kind"><?= $next_song['kind'] ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- === メインコントロール === -->
<div class="card player-controls-card mb-3">
  <div class="card-body p-3">

    <!-- 行1: 曲頭へ / 再生・一時停止 / 曲終了 -->
    <div class="row g-2 mb-2">
      <div class="col-4">
        <button class="btn btn-outline-secondary player-btn player-btn-main w-100"
                onclick="song_startfirst()" aria-label="曲の最初から">
          <?= $ic_skip_s ?>
          <span>曲の最初から</span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn player-btn player-btn-main player-btn-playpause w-100 <?= $playpause_cls ?>"
                onclick="song_pause()" id="btn-playpause" aria-label="一時停止・再開">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
               fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" id="icon-playpause">
            <?= $state_num == 2
              ? '<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>'
              : '<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>'
            ?>
          </svg>
          <span id="lbl-playpause"><?= $playpause_lbl ?></span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn btn-danger player-btn player-btn-main w-100"
                onclick="cmd_songnext()" aria-label="曲終了">
          <?= $ic_skip_e ?>
          <span>曲終了</span>
        </button>
      </div>
    </div>

    <!-- 行2: シーク -->
    <div class="player-section-label">シーク</div>
    <div class="row g-2 mb-2">
      <div class="col-3">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="jump_before_large()" aria-label="−20秒">
          <?= $ic_rwd ?>
          <span class="small">−20s</span>
        </button>
      </div>
      <div class="col-3">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="jump_before()" aria-label="−5秒">
          <?= $ic_chev_l ?>
          <span class="small">−5s</span>
        </button>
      </div>
      <div class="col-3">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="jump_later()" aria-label="+5秒">
          <?= $ic_chev_r ?>
          <span class="small">+5s</span>
        </button>
      </div>
      <div class="col-3">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="jump_later_large()" aria-label="+20秒">
          <?= $ic_fwd ?>
          <span class="small">+20s</span>
        </button>
      </div>
    </div>

    <!-- 行3: ボリューム -->
    <div class="player-section-label">ボリューム</div>
    <!-- スライダー行 -->
    <div class="d-flex align-items-center gap-2 mb-2">
      <button class="btn btn-outline-secondary player-btn flex-shrink-0"
              style="min-width:var(--tap-target);padding:8px;"
              onclick="vol_btn_down()" aria-label="ボリュームDOWN">
        <?= $ic_vol_d ?>
      </button>
      <input type="range" class="form-range flex-grow-1" id="volume-slider"
             min="0" max="100" value="50" aria-label="ボリューム">
      <button class="btn btn-outline-secondary player-btn flex-shrink-0"
              style="min-width:var(--tap-target);padding:8px;"
              onclick="vol_btn_up()" aria-label="ボリュームUP">
        <?= $ic_vol_u ?>
      </button>
      <span class="player-vol-display" id="vol-display" aria-live="polite">－</span>
    </div>
    <!-- 初期値・フェードアウト行 -->
    <div class="row g-2 mb-2">
      <div class="col-6">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_vreset_sync()" aria-label="ボリュームを再生開始時の値に戻す">
          <?= $ic_vol_reset ?>
          <span class="small">初期値</span>
        </button>
      </div>
      <div class="col-6">
        <button class="btn btn-warning player-btn w-100"
                onclick="exec_fadeout()" aria-label="フェードアウト">
          <?= $ic_fade ?>
          <span class="small">フェードアウト</span>
        </button>
      </div>
    </div>

    <?php if ($moviefullscreen == 1): ?>
    <!-- 行4: 映像操作 (フルスクリーンON時) -->
    <div class="player-section-label">映像</div>
    <div class="row g-2 mb-2">
      <div class="col-4">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_subtitleonnoff()" aria-label="字幕ON/OFF">
          <?= _svg('<path d="M0 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm2-1a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H2z"/><path d="M2.5 8.5a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>') ?>
          <span class="small">字幕</span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_changeaudio()" aria-label="音声トラック変更">
          <?= _svg('<path d="M6 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM3.5 6.5a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 1 0v1a.5.5 0 0 1-.5.5zM16 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-2.5 3.5a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 1 0v1a.5.5 0 0 1-.5.5z"/><path d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm2-1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H2z"/><path d="M3 10.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>') ?>
          <span class="small">音声切替</span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_fullscreen()" aria-label="フルスクリーン">
          <?= _svg('<path d="M1.5 1h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 0-.5.5v4a.5.5 0 0 1-1 0v-4A1.5 1.5 0 0 1 1.5 1zm9 0h4A1.5 1.5 0 0 1 16 2.5v4a.5.5 0 0 1-1 0v-4a.5.5 0 0 0-.5-.5h-4a.5.5 0 0 1 0-1zm-9 9a.5.5 0 0 1 .5.5v4a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 1 13.5v-4a.5.5 0 0 1 .5-.5zm13 0a.5.5 0 0 1 .5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a.5.5 0 0 1 0-1h4a.5.5 0 0 0 .5-.5v-4a.5.5 0 0 1 .5-.5z"/>') ?>
          <span class="small">全画面</span>
        </button>
      </div>
    </div>
    <?php elseif ($moviefullscreen != 1): ?>
    <!-- 行4: 映像操作 (フルスクリーンOFF時) -->
    <div class="player-section-label">映像</div>
    <div class="row g-2 mb-2">
      <div class="col-6">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_subtitleonnoff()" aria-label="字幕ON/OFF">
          <?= _svg('<path d="M0 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm2-1a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H2z"/><path d="M2.5 8.5a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>') ?>
          <span class="small">字幕ON/OFF</span>
        </button>
      </div>
      <div class="col-6">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_changeaudio()" aria-label="音声トラック変更">
          <?= _svg('<path d="M6 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM3.5 6.5a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 1 0v1a.5.5 0 0 1-.5.5zM16 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-2.5 3.5a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 1 0v1a.5.5 0 0 1-.5.5z"/><path d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm2-1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H2z"/>') ?>
          <span class="small">音声トラック変更</span>
        </button>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($usekeychange): ?>
    <!-- 行5: キーチェンジ -->
    <div class="player-section-label">キー変更</div>
    <div class="d-flex align-items-center gap-2 mb-2">
      <button class="btn btn-outline-secondary player-btn flex-fill"
              onclick="keychange('down')" aria-label="キーダウン">
        <?= $ic_arrow_d ?>
        <span class="small">キーダウン</span>
      </button>
      <div class="player-key-display" id="currentkey" aria-live="polite">
        ♩ 原曲
      </div>
      <button class="btn btn-outline-secondary player-btn flex-fill"
              onclick="keychange('up')" aria-label="キーアップ">
        <?= $ic_arrow_u ?>
        <span class="small">キーアップ</span>
      </button>
    </div>
    <div class="d-grid mb-1">
      <button class="btn btn-outline-secondary btn-sm"
              onclick="keychange(0)" aria-label="原曲キー">
        ♩ 原曲キー
      </button>
    </div>
    <?php else: ?>
    <div id="currentkey" style="display:none;"></div>
    <?php endif; ?>

  </div><!-- /card-body -->
</div><!-- /controls-card -->

<!-- === 再生開始 / 更新 === -->
<div class="row g-2 mb-3">
  <div class="col-6">
    <button class="btn btn-success w-100 player-btn player-btn-main"
            onclick="cmd_songstart()" aria-label="再生開始">
      <?= $ic_play ?>
      <span>再生開始</span>
    </button>
  </div>
  <div class="col-6">
    <a href="playerctrl_portal_bs5.php"
       class="btn btn-outline-secondary w-100 player-btn player-btn-main"
       aria-label="画面を更新">
      <?= _svg('<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>') ?>
      <span>更新</span>
    </a>
  </div>
</div>

<!-- === 詳細設定アコーディオン === -->
<div class="accordion mb-3" id="playerAdvanced">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button"
              data-bs-toggle="collapse" data-bs-target="#advBody"
              aria-expanded="false" aria-controls="advBody">
        詳細設定
      </button>
    </h2>
    <div id="advBody" class="accordion-collapse collapse" data-bs-parent="#playerAdvanced">
      <div class="accordion-body">

        <!-- 音ズレ修正 -->
        <div class="adv-section-title">音ズレ修正 ←映像を遅く／映像を早く→</div>
        <div class="row g-2 mb-3">
          <div class="col-3">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="song_audiodelay_m100()">−100ms</button>
          </div>
          <div class="col-3">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="song_audiodelay_m10()">−10ms</button>
          </div>
          <div class="col-3">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="song_audiodelay_p10()">+10ms</button>
          </div>
          <div class="col-3">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="song_audiodelay_p100()">+100ms</button>
          </div>
        </div>

        <!-- スピード -->
        <div class="adv-section-title">再生スピード</div>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('894')">スピードダウン</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('896')">標準スピード</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('895')">スピードアップ</button>
          </div>
        </div>

        <!-- 映像オプション -->
        <div class="adv-section-title">映像オプション</div>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('863')">サイズ縮小</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('861')">サイズ標準</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('862')">サイズ拡大</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('1023')">
              D3Dフルスクリーン<small class="d-block">重い場合</small>
            </button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num('909')">ミュートON/OFF</button>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="mpccmd_num(880)">左右反転</button>
          </div>
        </div>

        <!-- 字幕補正（白飛び対策） -->
        <?php
            $comp_level = get_player_compensation_level();
            $comp_disp  = ($comp_level > 0 ? '+' : '') . $comp_level;
        ?>
        <div class="adv-section-title">字幕補正（白飛び対策）</div>
        <div class="small text-muted mb-2">
          明るさ・コントラスト・彩度を一括で調整します。TVごとに最適値が異なるため、設定値は永続化され次の曲にも引き継がれます。
        </div>
        <div class="row g-2 mb-2 align-items-center">
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="comp_dec()" aria-label="補正を弱める">− 弱める</button>
          </div>
          <div class="col-4">
            <div class="player-comp-level" id="comp-level" aria-live="polite"><?= $comp_disp ?></div>
          </div>
          <div class="col-4">
            <button class="btn btn-outline-secondary btn-sm w-100"
                    onclick="comp_inc()" aria-label="補正を強める">強める ＋</button>
          </div>
        </div>
        <div class="d-grid mb-3">
          <button class="btn btn-outline-secondary btn-sm"
                  onclick="comp_reset()" aria-label="補正をリセット">リセット（0 に戻す）</button>
        </div>

        <!-- 任意コード -->
        <div class="adv-section-title">任意コード送出</div>
        <div class="d-flex gap-2 align-items-center">
          <input type="text" id="MPCCODE" class="player-code-input"
                 placeholder="コード" aria-label="MPCコマンドコード" />
          <button class="btn btn-outline-secondary btn-sm"
                  onclick="mpccmd_num()">送出</button>
        </div>

      </div><!-- /accordion-body -->
    </div>
  </div>
</div>
