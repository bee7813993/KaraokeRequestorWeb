<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();
$playerkind = getcurrentplayer();

/* 初期プレイヤーステータス */
require_once 'func_playerprogress.php';
$playstat = new PlayerProgress;
$has_song = $playstat->getstatus();

$prog_pct   = ($has_song && $playstat->totaltime > 0)
              ? (float)$playstat->playtime / (float)$playstat->totaltime * 100 : 0;
$time_cur   = $has_song ? htmlspecialchars($playstat->playtime_txt,  ENT_QUOTES, 'UTF-8') : '--:--';
$time_total = $has_song ? htmlspecialchars($playstat->totaltime_txt, ENT_QUOTES, 'UTF-8') : '--:--';
$song_title = ($has_song && !empty($playstat->playingtitle))
              ? htmlspecialchars($playstat->playingtitle, ENT_QUOTES, 'UTF-8') : '';
$song_file  = ($has_song && !empty($playstat->playingfile))
              ? htmlspecialchars($playstat->playingfile, ENT_QUOTES, 'UTF-8') : '';
$state_num  = $has_song ? (int)$playstat->status : 0;

/* 設定フラグ */
$usekeychange    = isset($config_ini['usekeychange'])    && $config_ini['usekeychange']    == 1;
$moviefullscreen = isset($config_ini['moviefullscreen']) && $config_ini['moviefullscreen'] == 1;

/* 初期キュー */
$init_playing = null;
$init_queue   = [];
try {
    $sql = "SELECT id, songfile, song_name, singer, secret, kind
            FROM requesttable WHERE nowplaying = '再生中'
            ORDER BY reqorder ASC LIMIT 1";
    $sel = $db->query($sql);
    if ($sel) {
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        $sel->closeCursor();
        if ($row) {
            $is_s = (int)$row['secret'] === 1;
            $st   = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
            $sn   = $row['song_name'] ?: '';
            $sf   = $row['songfile']  ?: '';
            $init_playing = [
                'title'  => $is_s ? $st : ($sn ?: $sf),
                'singer' => $is_s ? '' : ($row['singer'] ?: ''),
                'kind'   => $row['kind'] ?: '',
            ];
        }
    }
    $sql = "SELECT id, songfile, song_name, singer, secret, kind
            FROM requesttable WHERE nowplaying = '未再生'
            ORDER BY reqorder ASC LIMIT 30";
    $sel = $db->query($sql);
    if ($sel) {
        while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
            $is_s = (int)$row['secret'] === 1;
            $st   = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
            $sn   = $row['song_name'] ?: '';
            $sf   = $row['songfile']  ?: '';
            $init_queue[] = [
                'title'  => $is_s ? $st : ($sn ?: $sf),
                'singer' => $is_s ? '' : ($row['singer'] ?: ''),
                'kind'   => $row['kind'] ?: '',
            ];
        }
        $sel->closeCursor();
    }
} catch (Exception $e) { /* silent */ }

/* SVGヘルパー */
function _dsvg($path_d, $w = 18, $h = 18) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'"'
         . ' fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">'.$path_d.'</svg>';
}
$ic_play    = _dsvg('<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>');
$ic_pause   = _dsvg('<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>');
$ic_skip_s  = _dsvg('<path d="M4 4a.5.5 0 0 1 1 0v3.248l6.267-3.636c.54-.313 1.233.066 1.233.696v7.384c0 .63-.693 1.01-1.233.697L5 8.753V12a.5.5 0 0 1-1 0V4z"/>');
$ic_skip_e  = _dsvg('<path d="M12.5 4a.5.5 0 0 0-1 0v3.248L5.233 3.612C4.693 3.3 4 3.678 4 4.308v7.384c0 .63.693 1.01 1.233.697L11.5 8.753V12a.5.5 0 0 0 1 0V4z"/>');
$ic_rwd     = _dsvg('<path d="M8.404 7.304a.802.802 0 0 0 0 1.392l6.363 3.692c.52.302 1.233-.043 1.233-.696V4.308c0-.653-.713-.998-1.233-.696L8.404 7.304Z"/><path d="M.404 7.304a.802.802 0 0 0 0 1.392l6.363 3.692c.52.302 1.233-.043 1.233-.696V4.308c0-.653-.713-.998-1.233-.696L.404 7.304Z"/>');
$ic_fwd     = _dsvg('<path d="M7.596 7.304a.802.802 0 0 1 0 1.392l-6.363 3.692C.713 12.69 0 12.345 0 11.692V4.308c0-.653.713-.998 1.233-.696l6.363 3.692Z"/><path d="M15.596 7.304a.802.802 0 0 1 0 1.392l-6.363 3.692c-.52.302-1.233-.043-1.233-.696V4.308c0-.653.713-.998 1.233-.696l6.363 3.692Z"/>');
$ic_chev_l  = _dsvg('<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>');
$ic_chev_r  = _dsvg('<path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>');
$ic_vol_d   = _dsvg('<path d="M9 1a.5.5 0 0 0-.812-.39L4.825 3.5H2.5A.5.5 0 0 0 2 4v8a.5.5 0 0 0 .5.5h2.325l3.363 2.89A.5.5 0 0 0 9 15V1zm-4.5 4.5h.325l.5-.5V4h1V3.39L9 2.028V13.97L6.325 12.5H6v-.5H5.5L4.5 11V5.5zM11.5 8a3.5 3.5 0 0 1-.5 1.774v-3.55A3.5 3.5 0 0 1 11.5 8z"/>');
$ic_vol_u   = _dsvg('<path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/><path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z"/><path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 0 9.025 8 3.49 3.49 0 0 0 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/>');
$ic_fade    = _dsvg('<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>');
$ic_reset   = _dsvg('<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>');
$ic_arr_u   = _dsvg('<path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 0 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>');
$ic_arr_d   = _dsvg('<path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>');

/* 初期ステータス値 */
$pp_icon = $state_num == 2 ? $ic_pause : $ic_play;
$pp_lbl  = $state_num == 2 ? '一時停止' : '再開';

/* ページタイトル用ルーム名 */
$roomname = '';
if (!empty($config_ini['roomurl'])) {
    $rk = array_keys($config_ini['roomurl']);
    $roomname = htmlspecialchars($rk[0], ENT_QUOTES, 'UTF-8') . '：';
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=2.0">
<title><?= $roomname ?>ライブダッシュボード</title>
<link rel="stylesheet" href="css/bootstrap5/bootstrap.min.css">
<link rel="stylesheet" href="css/themes/_variables.css">
<link rel="stylesheet" href="css/themes/player_dashboard.css">
<script src="js/jquery.js"></script>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
</head>
<body class="dashboard-body">
<?php shownavigatioinbar_bs5('player_live_dashboard.php'); ?>

<div class="container-xl px-3">
<div class="db-layout">

  <!-- ============================================================
       左: プレイヤーパネル
       ============================================================ -->
  <div class="db-player-panel">

    <!-- Now Playing カード -->
    <div class="db-nowplaying">
      <!-- ステータス行 -->
      <div class="db-status-row">
        <div class="db-pulse-dot<?= $state_num == 2 ? ' is-playing' : ($state_num == 1 ? ' is-paused' : '') ?>"
             id="db-pulse-dot"></div>
        <span class="db-status-badge<?= $state_num == 2 ? ' is-playing' : ($state_num == 1 ? ' is-paused' : '') ?>"
              id="db-status-badge">
          <?= $state_num == 2 ? '再生中' : ($state_num == 1 ? '一時停止' : '停止中') ?>
        </span>
        <span class="db-kind-badge"><?= htmlspecialchars(strtoupper($playerkind), ENT_QUOTES, 'UTF-8') ?></span>
      </div>

      <!-- タイトル用data属性ホルダー (player_dashboard.js が参照) -->
      <div id="db-title-display"
           data-song-title="<?= $song_title ?>"
           data-song-file="<?= $song_file ?>">
      </div>

      <!-- Now Playing ラベル -->
      <div class="db-np-label">Now Playing</div>
      <!-- 曲名 -->
      <?php if ($song_title): ?>
        <div class="db-song-title" id="db-song-title"><?= $song_title ?></div>
      <?php else: ?>
        <div class="db-song-title is-empty" id="db-song-title">曲が選択されていません</div>
      <?php endif; ?>

      <!-- プログレスバー -->
      <div class="db-progress-wrap mt-3">
        <div class="db-progress" role="progressbar"
             aria-valuenow="<?= round($prog_pct) ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="db-progress-bar" id="db-progress-bar" style="width:<?= $prog_pct ?>%;"></div>
        </div>
        <div class="db-time-row">
          <span id="db-time-cur"><?= $time_cur ?></span>
          <span class="db-time-remaining" id="db-time-remaining"></span>
          <span id="db-time-total"><?= $time_total ?></span>
        </div>
      </div>
    </div><!-- /db-nowplaying -->

    <!-- 再生開始ボタン -->
    <button class="btn db-start-btn" onclick="db_cmd_songstart()" aria-label="再生開始">
      <?= $ic_play ?> 再生開始
    </button>

    <!-- メインコントロール -->
    <div class="db-controls">
      <!-- 行1: 曲頭 / 再生一時停止 / 曲終了 -->
      <div class="row g-2 mb-2">
        <div class="col-4">
          <button class="btn btn-outline-secondary db-btn db-btn-main w-100"
                  onclick="db_startfirst()" aria-label="曲の最初から">
            <?= $ic_skip_s ?>
            <span>最初から</span>
          </button>
        </div>
        <div class="col-4">
          <button class="btn db-btn db-btn-main db-btn-playpause w-100<?= $state_num == 2 ? ' is-playing' : '' ?>"
                  onclick="db_cmd_pause()" id="db-btn-playpause" aria-label="一時停止・再開">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                 fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" id="db-icon-playpause">
              <?= $state_num == 2
                ? '<path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>'
                : '<path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>'
              ?>
            </svg>
            <span id="db-lbl-playpause"><?= $pp_lbl ?></span>
          </button>
        </div>
        <div class="col-4">
          <button class="btn btn-danger db-btn db-btn-main w-100"
                  onclick="db_cmd_songnext()" aria-label="曲終了">
            <?= $ic_skip_e ?>
            <span>曲終了</span>
          </button>
        </div>
      </div>

      <!-- 行2: シーク -->
      <div class="db-section-label">シーク</div>
      <div class="row g-2 mb-2">
        <div class="col-3">
          <button class="btn btn-outline-secondary db-btn w-100"
                  onclick="db_seek('903')" aria-label="−20秒">
            <?= $ic_rwd ?><span class="small">−20s</span>
          </button>
        </div>
        <div class="col-3">
          <button class="btn btn-outline-secondary db-btn w-100"
                  onclick="db_seek('901')" aria-label="−5秒">
            <?= $ic_chev_l ?><span class="small">−5s</span>
          </button>
        </div>
        <div class="col-3">
          <button class="btn btn-outline-secondary db-btn w-100"
                  onclick="db_seek('902')" aria-label="+5秒">
            <?= $ic_chev_r ?><span class="small">+5s</span>
          </button>
        </div>
        <div class="col-3">
          <button class="btn btn-outline-secondary db-btn w-100"
                  onclick="db_seek('904')" aria-label="+20秒">
            <?= $ic_fwd ?><span class="small">+20s</span>
          </button>
        </div>
      </div>

      <!-- 行3: ボリューム -->
      <div class="db-section-label">ボリューム</div>
      <div class="db-vol-row mb-2">
        <button class="btn btn-outline-secondary db-btn flex-shrink-0"
                style="min-width:44px;padding:8px;"
                onclick="db_vol_down()" aria-label="ボリュームDOWN">
          <?= $ic_vol_d ?>
        </button>
        <input type="range" class="form-range flex-grow-1" id="db-vol-slider"
               min="0" max="100" value="50" aria-label="ボリューム">
        <button class="btn btn-outline-secondary db-btn flex-shrink-0"
                style="min-width:44px;padding:8px;"
                onclick="db_vol_up()" aria-label="ボリュームUP">
          <?= $ic_vol_u ?>
        </button>
        <span class="db-vol-display" id="db-vol-display" aria-live="polite">－</span>
      </div>
      <div class="row g-2 mb-2">
        <div class="col-6">
          <button class="btn btn-outline-secondary db-btn w-100"
                  onclick="db_vol_reset()" aria-label="ボリューム初期値">
            <?= $ic_reset ?><span class="small">初期値</span>
          </button>
        </div>
        <div class="col-6">
          <button class="btn btn-warning db-btn w-100"
                  onclick="db_fadeout()" aria-label="フェードアウト">
            <?= $ic_fade ?><span class="small">フェードアウト</span>
          </button>
        </div>
      </div>

      <?php if ($usekeychange): ?>
      <!-- キーチェンジ -->
      <div class="db-section-label">キー変更</div>
      <div class="d-flex align-items-center gap-2 mb-2">
        <button class="btn btn-outline-secondary db-btn flex-fill"
                onclick="db_keychange('down')" aria-label="キーダウン">
          <?= $ic_arr_d ?><span class="small">キーダウン</span>
        </button>
        <div class="db-key-display" id="currentkey" aria-live="polite">♩ 原曲</div>
        <button class="btn btn-outline-secondary db-btn flex-fill"
                onclick="db_keychange('up')" aria-label="キーアップ">
          <?= $ic_arr_u ?><span class="small">キーアップ</span>
        </button>
      </div>
      <div class="d-grid mb-1">
        <button class="btn btn-outline-secondary btn-sm"
                onclick="db_keychange(0)" aria-label="原曲キー">♩ 原曲キー</button>
      </div>
      <?php else: ?>
      <div id="currentkey" style="display:none;"></div>
      <?php endif; ?>

    </div><!-- /db-controls -->

    <!-- 詳細設定アコーディオン -->
    <div class="accordion db-accordion mb-3" id="dbAdvanced">
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#dbAdvBody"
                  aria-expanded="false" aria-controls="dbAdvBody">
            詳細設定
          </button>
        </h2>
        <div id="dbAdvBody" class="accordion-collapse collapse" data-bs-parent="#dbAdvanced">
          <div class="accordion-body">

            <!-- 音ズレ修正 -->
            <div class="db-adv-title">音ズレ修正 ←映像を遅く／映像を早く→</div>
            <div class="row g-2 mb-3">
              <div class="col-3">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('delaym100')">−100ms</button>
              </div>
              <div class="col-3">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('delayp100')">+100ms</button>
              </div>
            </div>

            <!-- スピード -->
            <div class="db-adv-title">再生スピード</div>
            <div class="row g-2 mb-3">
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('894')">スピードダウン</button>
              </div>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('896')">標準スピード</button>
              </div>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('895')">スピードアップ</button>
              </div>
            </div>

            <!-- 映像オプション -->
            <div class="db-adv-title">映像オプション</div>
            <div class="row g-2 mb-3">
              <?php if ($moviefullscreen): ?>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('909')">ミュートON/OFF</button>
              </div>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('856')">字幕ON/OFF</button>
              </div>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('881')">フルスクリーン</button>
              </div>
              <?php else: ?>
              <div class="col-6">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('856')">字幕ON/OFF</button>
              </div>
              <div class="col-6">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('909')">ミュートON/OFF</button>
              </div>
              <?php endif; ?>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_seek('880')">左右反転</button>
              </div>
            </div>

            <!-- 字幕補正 -->
            <div class="db-adv-title">字幕補正（白飛び対策）</div>
            <div class="small mb-2" style="color:#484f58;">明るさ・コントラスト・彩度を一括調整します。設定値は永続化されます。</div>
            <div class="row g-2 mb-2 align-items-center">
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_comp_dec()">− 弱める</button>
              </div>
              <div class="col-4">
                <div class="db-comp-level" id="db-comp-level" aria-live="polite">0</div>
              </div>
              <div class="col-4">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick="db_comp_inc()">強める ＋</button>
              </div>
            </div>
            <div class="d-grid mb-3">
              <button class="btn btn-outline-secondary btn-sm"
                      onclick="db_comp_reset()">リセット（0 に戻す）</button>
            </div>

            <!-- 任意コード -->
            <div class="db-adv-title">任意コード送出</div>
            <div class="d-flex gap-2 align-items-center">
              <input type="text" id="db-mpccode" class="db-code-input"
                     placeholder="コード" aria-label="MPCコマンドコード">
              <button class="btn btn-outline-secondary btn-sm"
                      onclick="db_mpccmd()">送出</button>
            </div>

          </div><!-- /accordion-body -->
        </div>
      </div>
    </div><!-- /db-accordion -->

    <!-- 既存Playerへのリンク -->
    <div class="d-grid mb-3">
      <a href="playerctrl_portal_bs5.php"
         class="btn btn-outline-secondary btn-sm">
        通常のPlayerページへ
      </a>
    </div>

  </div><!-- /db-player-panel -->

  <!-- ============================================================
       右: キューパネル
       ============================================================ -->
  <div class="db-queue-panel">
    <div class="db-queue-header">
      <span class="db-queue-title">QUEUE</span>
      <div class="db-queue-header-right">
        <span class="db-queue-duration" id="db-queue-duration" style="display:none;"></span>
        <span class="db-queue-count" id="db-queue-count"><?= count($init_queue) ?>曲待機中</span>
      </div>
    </div>
    <div class="db-queue-list" id="db-queue-list">
      <?php
      /* 初期描画 (JS が上書きするまでのフォールバック) */
      /* 再生中 */
      echo '<div class="db-queue-item is-playing">';
      echo '<div class="db-queue-num">▶</div>';
      echo '<div class="db-queue-info">';
      if ($init_playing) {
          echo '<div class="db-queue-song">' . htmlspecialchars($init_playing['title'], ENT_QUOTES, 'UTF-8') . '</div>';
          $meta = [];
          if ($init_playing['singer']) $meta[] = '<span class="db-queue-singer">' . htmlspecialchars($init_playing['singer'], ENT_QUOTES, 'UTF-8') . '</span>';
          if ($init_playing['kind'])   $meta[] = '<span class="db-queue-kind">'   . htmlspecialchars($init_playing['kind'],   ENT_QUOTES, 'UTF-8') . '</span>';
          if ($meta) echo '<div class="db-queue-meta">' . implode('', $meta) . '</div>';
      } else {
          echo '<div class="db-queue-song" style="color:#3d444d;font-style:italic;">再生中の曲なし</div>';
      }
      echo '</div></div>';

      /* 待機キュー */
      if (empty($init_queue)) {
          echo '<div class="db-queue-empty">待機中の曲はありません</div>';
      } else {
          foreach ($init_queue as $idx => $item) {
              echo '<div class="db-queue-item">';
              echo '<div class="db-queue-num">' . ($idx + 1) . '</div>';
              echo '<div class="db-queue-info">';
              echo '<div class="db-queue-song">' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</div>';
              $m = [];
              if ($item['singer']) $m[] = '<span class="db-queue-singer">' . htmlspecialchars($item['singer'], ENT_QUOTES, 'UTF-8') . '</span>';
              if ($item['kind'])   $m[] = '<span class="db-queue-kind">'   . htmlspecialchars($item['kind'],   ENT_QUOTES, 'UTF-8') . '</span>';
              if ($m) echo '<div class="db-queue-meta">' . implode('', $m) . '</div>';
              echo '</div></div>';
          }
      }
      ?>
    </div>
  </div><!-- /db-queue-panel -->

</div><!-- /db-layout -->
</div><!-- /container-xl -->

<script src="js/player_dashboard.js"></script>
</body>
</html>
