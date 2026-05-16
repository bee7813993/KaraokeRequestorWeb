<?php
require_once 'foobar_func.php';

/* ---- AJAX / コマンドリクエスト処理 ---- */
if (!empty($_REQUEST['songnext'])) {
    songnext();
    die();
}
if (array_key_exists('songstart', $_REQUEST)) {
    foobar_songstart();
    die();
}
if (array_key_exists('cmd', $_REQUEST)) {
    $l_cmd = $_REQUEST['cmd'];
    if ($l_cmd === 'Start')         { foobar_songstart(); }
    elseif ($l_cmd === 'PlayOrPause'){ foobar_song_pause(); }
    elseif ($l_cmd === 'VolumeUP')   { foobar_song_vup(); }
    elseif ($l_cmd === 'VolumeDown') { foobar_song_vdown(); }
    elseif ($l_cmd === 'Stop')       { foobar_song_stop(); }
    elseif ($l_cmd === 'StartFirst') { foobar_song_restart(); }
    die();
}

/* ---- SVGアイコン定義 ---- */
function _fb_svg($path_d, $w = 20, $h = 20) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '"'
         . ' fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">' . $path_d . '</svg>';
}
$ic_play   = _fb_svg('<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>');
$ic_pause  = _fb_svg('<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>');
$ic_skip_s = _fb_svg('<path d="M4 4a.5.5 0 0 1 1 0v3.248l6.267-3.636c.54-.313 1.233.066 1.233.696v7.384c0 .63-.693 1.01-1.233.697L5 8.753V12a.5.5 0 0 1-1 0V4z"/>');
$ic_skip_e = _fb_svg('<path d="M12.5 4a.5.5 0 0 0-1 0v3.248L5.233 3.612C4.693 3.3 4 3.678 4 4.308v7.384c0 .63.693 1.01 1.233.697L11.5 8.753V12a.5.5 0 0 0 1 0V4z"/>');
$ic_vol_d  = _fb_svg('<path d="M9 1a.5.5 0 0 0-.812-.39L4.825 3.5H2.5A.5.5 0 0 0 2 4v8a.5.5 0 0 0 .5.5h2.325l3.363 2.89A.5.5 0 0 0 9 15V1zm-4.5 4.5h.325l.5-.5V4h1V3.39L9 2.028V13.97L6.325 12.5H6v-.5H5.5L4.5 11V5.5zM11.5 8a3.5 3.5 0 0 1-.5 1.774v-3.55A3.5 3.5 0 0 1 11.5 8z"/>');
$ic_vol_u  = _fb_svg('<path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/><path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z"/><path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 0 9.025 8 3.49 3.49 0 0 0 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/>');
?>

<script src="foobarctrl.js"></script>

<!-- === Now Playing カード (foobar) === -->
<div class="card player-nowplaying mb-3">
  <div class="card-body p-3">
    <div class="d-flex align-items-start gap-2 mb-2">
      <span class="badge bg-secondary player-status-badge">停止中</span>
      <span class="player-kind-badge">foobar2000</span>
    </div>
    <div class="player-title text-muted" style="opacity:.6;">音楽再生中</div>
  </div>
</div>

<!-- === コントロール === -->
<div class="card player-controls-card mb-3">
  <div class="card-body p-3">

    <!-- 行1: 曲頭へ / 一時停止・再開 / 曲終了 -->
    <div class="row g-2 mb-2">
      <div class="col-4">
        <button class="btn btn-outline-secondary player-btn player-btn-main w-100"
                onclick="song_startfirst()" aria-label="曲の最初から">
          <?= $ic_skip_s ?>
          <span>曲頭へ</span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn player-btn player-btn-main player-btn-playpause w-100"
                onclick="song_pause()" aria-label="一時停止・再開">
          <?= $ic_pause ?>
          <span>一時停止/再開</span>
        </button>
      </div>
      <div class="col-4">
        <button class="btn btn-danger player-btn player-btn-main w-100"
                onclick="foobar_cmd_songnext()" aria-label="曲終了">
          <?= $ic_skip_e ?>
          <span>曲終了</span>
        </button>
      </div>
    </div>

    <!-- 行2: ボリューム -->
    <div class="player-section-label">ボリューム</div>
    <div class="row g-2">
      <div class="col-6">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_vdown()" aria-label="ボリュームDOWN">
          <?= $ic_vol_d ?>
          <span class="small">Vol−</span>
        </button>
      </div>
      <div class="col-6">
        <button class="btn btn-outline-secondary player-btn w-100"
                onclick="song_vup()" aria-label="ボリュームUP">
          <?= $ic_vol_u ?>
          <span class="small">Vol＋</span>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- === 再生開始 / 更新 === -->
<div class="row g-2 mb-3">
  <div class="col-6">
    <button class="btn btn-success w-100 player-btn player-btn-main"
            onclick="foobar_cmd_songstart()" aria-label="再生開始">
      <?= $ic_play ?>
      <span>再生開始</span>
    </button>
  </div>
  <div class="col-6">
    <a href="playerctrl_portal_bs5.php"
       class="btn btn-outline-secondary w-100 player-btn player-btn-main"
       aria-label="画面を更新">
      <?= _fb_svg('<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>') ?>
      <span>更新</span>
    </a>
  </div>
</div>

<script src="js/player_bs5.js"></script>
