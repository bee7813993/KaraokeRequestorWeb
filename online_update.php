<?php
require_once 'commonfunc.php';
print_meta_header();

$res    = 2;    // 2=何もしていない, true=成功, false=失敗
$errmsg = '';

$req_version = array_key_exists('UPDATEVERSION', $_REQUEST) ? urldecode($_REQUEST['UPDATEVERSION']) : null;
$req_method  = (array_key_exists('METHOD', $_REQUEST) && $_REQUEST['METHOD'] === 'git') ? 'git' : 'zip';

if ($req_version !== null && $req_version !== '') {
    if ($req_method === 'git') {
        $res = update_fromgit($req_version, $errmsg);
    } else {
        $res = update_fromarchive($req_version, $errmsg);
    }
}

// git が利用可能か判定
$git_available = false;
if (array_key_exists('gitcommandpath', $config_ini)) {
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    $git_available = file_exists($gitcmd);
}

$active_tab = $git_available ? $req_method : 'zip';
?>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Style-Type" content="text/css" />

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

  <title>オンラインアップデート</title>
  <link type="text/css" rel="stylesheet" href="css/style.css" />
  <script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php shownavigatioinbar(); ?>

<div class="container" style="margin-top:20px;">
  <h3>オンラインアップデート</h3>

<?php
// 更新結果表示
if ($res === false) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($errmsg) . '</div>';
} elseif ($res === true) {
    echo '<div class="alert alert-success">アップデートに成功しました</div>';
}

// 現在バージョン表示
$curver = get_version();
if (!empty($curver)) {
    echo '<p><strong>現在のバージョン:</strong> ' . htmlspecialchars($curver) . '</p>';
}
?>

<?php if ($git_available): ?>
<!-- タブ（git が設定されている場合のみ表示） -->
<ul class="nav nav-tabs" id="updateTabs">
  <li role="presentation" <?php if ($active_tab === 'zip') echo 'class="active"'; ?>>
    <a href="#tab-zip" data-toggle="tab">ZIP方式（推奨）</a>
  </li>
  <li role="presentation" <?php if ($active_tab === 'git') echo 'class="active"'; ?>>
    <a href="#tab-git" data-toggle="tab">Git方式</a>
  </li>
</ul>
<div class="tab-content" style="margin-top:15px;">

  <!-- ZIP 方式タブ -->
  <div role="tabpanel" class="tab-pane <?php echo ($active_tab === 'zip') ? 'active' : ''; ?>" id="tab-zip">
<?php else: ?>
<!-- git 未設定: ZIP方式のみ -->
<div>
  <div>
<?php endif; ?>

<?php
$zip_check = check_zip_update_available();
if ($zip_check !== true):
?>
    <div class="alert alert-warning">
      ZIP方式は現在利用できません: <?php echo htmlspecialchars($zip_check); ?>
    </div>
<?php else:
    $zip_errmsg = '';
    $zip_taglist = get_archive_taglist($zip_errmsg);
    if (!empty($zip_errmsg)):
?>
    <div class="alert alert-warning">タグ一覧の取得に失敗しました: <?php echo htmlspecialchars($zip_errmsg); ?></div>
<?php elseif (count($zip_taglist) === 0): ?>
    <div class="alert alert-info">タグ情報を取得できませんでした（ネットワークを確認してください）</div>
<?php else: ?>
    <dl class="dl-horizontal">
      <dt>最新版 (master)</dt>
      <dd>
        <a href="online_update.php?UPDATEVERSION=<?php echo urlencode('master'); ?>&METHOD=zip"
           class="btn btn-primary btn-sm"
           onclick="return confirm('master ブランチの最新版に更新します。よろしいですか？');">更新</a>
      </dd>
<?php   foreach ($zip_taglist as $tag):
          if (strcmp($tag, 'v0.09.5-alpha') === 0): ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>これ以前のバージョンはコマンドプロンプトでのコマンド実行が必要です</dd>
<?php       break;
          endif; ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>
        <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($tag); ?>&METHOD=zip"
           class="btn btn-default btn-sm"
           onclick="return confirm('<?php echo htmlspecialchars($tag, ENT_QUOTES); ?> に更新します。よろしいですか？');">更新</a>
      </dd>
<?php   endforeach; ?>
    </dl>

    <div class="panel panel-default" style="margin-top:10px;">
      <div class="panel-body">
        <form method="GET" class="form-inline">
          <input type="hidden" name="METHOD" value="zip" />
          <div class="form-group">
            <label>任意タグ/コミットハッシュ&nbsp;</label>
            <input type="text" name="UPDATEVERSION" class="form-control" placeholder="例: v0.09.9-alpha" />
          </div>
          &nbsp;<input type="submit" value="実行" class="btn btn-warning"
                        onclick="return confirm('指定バージョンで更新します。よろしいですか？');" />
        </form>
      </div>
    </div>
<?php endif; endif; // zip_check / taglist ?>

  </div><!-- /zip tab pane -->

<?php if ($git_available): ?>

  <!-- Git 方式タブ -->
  <div role="tabpanel" class="tab-pane <?php echo ($active_tab === 'git') ? 'active' : ''; ?>" id="tab-git">
    <div class="alert alert-info">
      Git 方式: <code>git fetch</code> + <code>git reset --hard</code> でアップデートします。
      <code>.git</code> フォルダを保持するためディスク容量が多くなります。
    </div>
<?php
    $git_errmsg  = '';
    $git_taglist = get_gittaglist($git_errmsg);
    if (!empty($git_errmsg) && $git_errmsg !== 'none'):
?>
    <div class="alert alert-warning">タグ一覧の取得に失敗しました: <?php echo htmlspecialchars($git_errmsg); ?></div>
<?php elseif (count($git_taglist) === 0): ?>
    <div class="alert alert-info">タグが見つかりませんでした</div>
<?php else: ?>
    <dl class="dl-horizontal">
      <dt>最新版 (リリース前)</dt>
      <dd>
        <a href="online_update.php?UPDATEVERSION=<?php echo urlencode('origin/master'); ?>&METHOD=git"
           class="btn btn-primary btn-sm"
           onclick="return confirm('origin/master の最新版に更新します。よろしいですか？');">更新</a>
      </dd>
<?php   foreach (array_reverse($git_taglist) as $tag):
          if (strcmp($tag, 'v0.09.5-alpha') === 0): ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>これ以前のバージョンはコマンドプロンプトでのコマンド実行が必要です</dd>
<?php       break;
          endif; ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>
        <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($tag); ?>&METHOD=git"
           class="btn btn-default btn-sm"
           onclick="return confirm('<?php echo htmlspecialchars($tag, ENT_QUOTES); ?> に更新します。よろしいですか？');">更新</a>
      </dd>
<?php   endforeach; ?>
    </dl>

    <div class="panel panel-default" style="margin-top:10px;">
      <div class="panel-body">
        <form method="GET" class="form-inline">
          <input type="hidden" name="METHOD" value="git" />
          <div class="form-group">
            <label>任意バージョンハッシュ&nbsp;</label>
            <input type="text" name="UPDATEVERSION" class="form-control" />
          </div>
          &nbsp;<input type="submit" value="実行" class="btn btn-warning" />
        </form>
      </div>
    </div>
<?php endif; ?>
  </div><!-- /git tab pane -->

</div><!-- /tab-content -->
<?php else: ?>
  </div>
<?php endif; ?>

<hr/>
<p class="text-muted small">
  バージョン情報:
  <a href="https://github.com/bee7813993/KaraokeRequestorWeb/commits/master" target="_blank">
    GitHub コミット履歴
  </a>
</p>
</div><!-- /container -->

</body>
</html>
