<?php
/**
 * song_metadata_edit_bs5.php
 *
 * 予約された曲のメタデータ (曲名・歌手名・作品名・読み仮名・使われ方・補足説明) を
 * Web UI から修正するページ (Bootstrap 5)。
 * アプリ (ゆかナビ) の「曲の情報を修正する」機能の Web 版で、
 * 保存は同じ /api/song_metadata.php (action=correct) を使う。
 * 修正は予約一覧の表示に反映され、変更した項目だけが修正ログ
 * (metadata_correction テーブル) に記録される。
 *
 * GET ?id=<予約ID>
 */
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
require_once 'function_search_listerdb.php';

$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

$l_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($l_id === false || $l_id === null || $l_id <= 0) {
    http_response_code(400);
    die('wrong id');
}

$stmt = $db->prepare('SELECT id, songfile, fullpath, singer, kind, nowplaying, secret,'
    . ' song_name, lister_artist, lister_work, lister_op_ed, lister_comment'
    . ' FROM requesttable WHERE id = :id');
$stmt->bindValue(':id', $l_id, PDO::PARAM_INT);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
if (!$request) {
    http_response_code(404);
    die('nodata');
}

// シークレット予約 (未再生) は本人と管理者以外には曲情報を見せない
$nowplaying_val = !empty($request['nowplaying']) ? $request['nowplaying'] : '未再生';
$is_hidden_secret = ((int)$request['secret'] === 1
    && ($nowplaying_val === '未再生' || $nowplaying_val === '1'));
$is_owner = ($request['singer'] !== '' && $request['singer'] === returnusername_self());
$is_admin = (isset($user) && $user === 'admin');
$secret_blocked = ($is_hidden_secret && !$is_owner && !$is_admin);

// 読み仮名の現在値は ListerDB から (requesttable に列が無い。API の修正前値と同じ基準)
$ruby = ['song_ruby' => '', 'lister_artist_ruby' => '', 'lister_work_ruby' => ''];
if (!$secret_blocked && !empty($request['fullpath']) && array_key_exists('listerDBPATH', $config_ini)) {
    $lister_dbpath = urldecode($config_ini['listerDBPATH']);
    if (file_exists($lister_dbpath)) {
        $info = listerdb_lookup_songinfo($request['fullpath'], $lister_dbpath, true);
        if ($info) {
            foreach (array_keys($ruby) as $k) {
                $ruby[$k] = (string)($info[$k] ?? '');
            }
        }
    }
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php
if (!empty($config_ini['roomurl'])) {
    $roomnames = array_keys($config_ini['roomurl']);
    echo h($roomnames[0]) . '：';
}
?>曲情報の修正</title>
<?php print_bs5_search_head(); ?>
<style>
.metaedit-ref {
  font-size: 0.9rem;
  word-break: break-all;
}
.metaedit-ruby-note {
  font-size: 0.8rem;
  color: var(--color-text-muted, #6c757d);
}
</style>
</head>
<body>

<?php shownavigatioinbar_bs5(); ?>

<div class="container py-3" style="max-width: 720px;">

<h4 class="mb-3">&#9998; 曲情報の修正</h4>

<?php if ($secret_blocked): ?>

<div class="alert alert-warning" role="alert">
シークレットリクエストのため、再生されるまで曲情報は修正できません。
</div>
<a href="requestlist_top.php" class="btn btn-secondary">予約一覧へ戻る</a>

<?php else: ?>

<div class="card mb-3">
  <div class="card-body metaedit-ref">
    <div><strong>予約 #<?php echo (int)$request['id']; ?></strong>　登録者：<?php echo h($request['singer']); ?></div>
    <div>ファイル：<?php echo h($request['songfile']); ?></div>
    <?php if (!empty($request['fullpath']) && $request['fullpath'] !== $request['songfile']): ?>
    <div class="text-muted small"><?php echo h($request['fullpath']); ?></div>
    <?php endif; ?>
  </div>
</div>

<p class="small text-muted mb-3">
修正した内容は予約一覧の曲情報表示に反映されます。変更した項目は修正ログに記録され、
ゆかりすたー (ListerDB) の登録情報を直す材料として管理画面から CSV でダウンロードできます。
</p>

<div id="metaedit-result"></div>

<form id="metaedit-form">
  <div class="card mb-3">
    <div class="card-body">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="song_name">曲名</label>
          <input type="text" class="form-control" id="song_name" name="song_name"
                 value="<?php echo h($request['song_name']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="song_ruby">曲名の読み</label>
          <input type="text" class="form-control" id="song_ruby" name="song_ruby"
                 value="<?php echo h($ruby['song_ruby']); ?>" placeholder="キョクメイノヨミ">
        </div>

        <div class="col-md-6">
          <label class="form-label" for="lister_artist">歌手名</label>
          <input type="text" class="form-control" id="lister_artist" name="lister_artist"
                 value="<?php echo h($request['lister_artist']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="lister_artist_ruby">歌手名の読み</label>
          <input type="text" class="form-control" id="lister_artist_ruby" name="lister_artist_ruby"
                 value="<?php echo h($ruby['lister_artist_ruby']); ?>" placeholder="カシュメイノヨミ">
        </div>

        <div class="col-md-6">
          <label class="form-label" for="lister_work">作品名</label>
          <input type="text" class="form-control" id="lister_work" name="lister_work"
                 value="<?php echo h($request['lister_work']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="lister_work_ruby">作品名の読み</label>
          <input type="text" class="form-control" id="lister_work_ruby" name="lister_work_ruby"
                 value="<?php echo h($ruby['lister_work_ruby']); ?>" placeholder="サクヒンメイノヨミ">
        </div>

        <div class="col-md-6">
          <label class="form-label" for="lister_op_ed">使われ方</label>
          <input type="text" class="form-control" id="lister_op_ed" name="lister_op_ed"
                 value="<?php echo h($request['lister_op_ed']); ?>" placeholder="OP1 / ED2 / 挿入歌 など">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="lister_comment">補足説明</label>
          <input type="text" class="form-control" id="lister_comment" name="lister_comment"
                 value="<?php echo h($request['lister_comment']); ?>">
        </div>
      </div>

      <p class="metaedit-ruby-note mt-3 mb-0">
      ※ 読みは ひらがな・カタカナ で入力してください。保存時にゆかりすたーの登録規則
      (全角カタカナ・濁点/半濁点なし) へ自動変換されます。
      </p>

    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary" id="metaedit-submit">修正を保存</button>
    <a href="requestlist_top.php" class="btn btn-secondary">予約一覧へ戻る</a>
  </div>
</form>

<script>
(function () {
    var form      = document.getElementById('metaedit-form');
    var submitBtn = document.getElementById('metaedit-submit');
    var resultEl  = document.getElementById('metaedit-result');
    var requestId = <?php echo (int)$request['id']; ?>;

    // 変更した項目だけ API へ送る (送らない項目は「変更なし」扱い。
    // 読み仮名の修正前値は ListerDB 基準のため、毎回全項目を送ると
    // 同じ修正が保存のたびに重複記録されてしまう)
    var initial = {};
    Array.prototype.forEach.call(form.querySelectorAll('input[name]'), function (el) {
        initial[el.name] = el.value;
    });

    function showResult(type, message) {
        resultEl.innerHTML = '<div class="alert alert-' + type + '" role="alert"></div>';
        resultEl.firstChild.textContent = message;
        resultEl.firstChild.scrollIntoView({ block: 'nearest' });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var params  = new URLSearchParams();
        var changed = 0;
        Array.prototype.forEach.call(form.querySelectorAll('input[name]'), function (el) {
            if (el.value !== initial[el.name]) {
                params.append(el.name, el.value);
                changed++;
            }
        });
        if (changed === 0) {
            showResult('info', '変更された項目はありません。');
            return;
        }
        params.append('action', 'correct');
        params.append('id', requestId);

        submitBtn.disabled = true;
        fetch('api/song_metadata.php', { method: 'POST', body: params })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.ok) {
                    // 保存済みの値を新しい基準にする (再保存時の重複記録防止)
                    Array.prototype.forEach.call(form.querySelectorAll('input[name]'), function (el) {
                        initial[el.name] = el.value;
                    });
                    showResult('success', '修正を保存しました。予約一覧の表示に反映されます。');
                } else {
                    showResult('danger', '保存に失敗しました：' + (json && json.error ? json.error : '不明なエラー'));
                }
            })
            .catch(function () {
                showResult('danger', '保存に失敗しました。通信状態を確認してください。');
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    });
})();
</script>

<?php endif; ?>

</div>

<?php print_bg_style_block(true); ?>
</body>
</html>
