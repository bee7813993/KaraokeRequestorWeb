<?php
require_once 'commonfunc.php';
print_meta_header();

$res    = 2;   // 2=何もしていない, true=成功, false=失敗
$errmsg = '';

$req_version = isset($_REQUEST['UPDATEVERSION']) ? urldecode($_REQUEST['UPDATEVERSION']) : null;
$req_method  = (isset($_REQUEST['METHOD']) && $_REQUEST['METHOD'] === 'git') ? 'git' : 'zip';
$req_action  = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';

// アクション処理
if ($req_action === 'git_gc') {
    $res = run_git_gc($errmsg, false);
} elseif ($req_action === 'git_gc_aggressive') {
    $res = run_git_gc($errmsg, true);
} elseif ($req_action === 'git_init') {
    $res = init_git_repo($errmsg);
} elseif ($req_version !== null && $req_version !== '') {
    if ($req_method === 'git') {
        $res = update_fromgit($req_version, $errmsg);
    } else {
        $res = update_fromarchive($req_version, $errmsg);
    }
}

// git 状態判定
$git_configured  = false;
$git_available   = false;
$git_repo_exists = is_dir(realpath(__DIR__) . DIRECTORY_SEPARATOR . '.git');

if (array_key_exists('gitcommandpath', $config_ini)) {
    $gitcmd = urldecode($config_ini['gitcommandpath']);
    $git_configured = ($gitcmd !== '');
    $git_available  = $git_configured && file_exists($gitcmd);
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
// 実行結果表示
if ($res === false) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($errmsg) . '</div>';
} elseif ($res === true) {
    if ($req_action === 'git_gc' || $req_action === 'git_gc_aggressive') {
        $action_label = 'リポジトリの最適化';
    } elseif ($req_action === 'git_init') {
        $action_label = 'Git リポジトリの初期化';
    } else {
        $action_label = 'アップデート';
    }
    echo '<div class="alert alert-success">' . htmlspecialchars($action_label) . 'に成功しました</div>';
}

// 現在バージョン表示
$curver = get_version();
if (!empty($curver)) {
    echo '<p><strong>現在のバージョン:</strong> ' . htmlspecialchars($curver) . '</p>';
}
?>

<?php if ($git_available): ?>
<!-- タブ（git が設定されている場合のみ） -->
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
<div><div>
<?php endif; ?>

<?php /* ========== ZIP 方式コンテンツ ========== */ ?>
<?php
$zip_check = check_zip_update_available();
if ($zip_check !== true):
?>
    <div class="alert alert-warning">
      ZIP方式は現在利用できません: <?php echo htmlspecialchars($zip_check); ?>
    </div>
<?php else:
    $zip_errmsg  = '';
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
<?php       break; endif; ?>
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
            <label>任意タグ / コミットハッシュ&nbsp;</label>
            <input type="text" name="UPDATEVERSION" class="form-control" placeholder="例: v0.09.9-alpha" />
          </div>
          &nbsp;<input type="submit" value="実行" class="btn btn-warning"
                       onclick="return confirm('指定バージョンで更新します。よろしいですか？');" />
        </form>
      </div>
    </div>
<?php endif; endif; // zip_check / taglist ?>

  </div><!-- /zip tab pane -->

<?php if ($git_available): /* ========== Git 方式タブ ========== */ ?>

  <div role="tabpanel" class="tab-pane <?php echo ($active_tab === 'git') ? 'active' : ''; ?>" id="tab-git">

<?php if (!$git_repo_exists): ?>
    <!-- .git フォルダなし → 初期化案内 -->
    <div class="panel panel-warning">
      <div class="panel-heading"><strong>.git フォルダが見つかりません</strong></div>
      <div class="panel-body">
        <p>Git 方式でアップデートするには、まずリポジトリを初期化してください。<br>
           <small class="text-muted">初期化後は <code>.git</code> フォルダが作成されます（数 MB、shallow clone）。</small></p>
        <a href="online_update.php?ACTION=git_init&METHOD=git"
           class="btn btn-warning"
           onclick="return confirm('リポジトリを初期化します（git init + fetch --depth=1 + reset --hard）。\nconfig.ini・request.db などのユーザーデータは保護されます。よろしいですか？');">
          Git リポジトリを初期化する
        </a>
      </div>
    </div>
<?php else: ?>
    <!-- .git サイズ表示 + 最適化ボタン -->
<?php
    $git_size = get_git_dir_size();
    $git_size_str = $git_size !== null ? format_filesize($git_size) : '取得失敗';
?>
    <div class="panel panel-default">
      <div class="panel-heading"><strong>リポジトリ最適化</strong></div>
      <div class="panel-body">
        <p><strong>.git フォルダ サイズ:</strong> <?php echo htmlspecialchars($git_size_str); ?>
           &nbsp;<small class="text-muted">（アップデート後に <code>git gc</code> を実行すると削減できます）</small></p>
        <a href="online_update.php?ACTION=git_gc&METHOD=git"
           class="btn btn-default btn-sm"
           onclick="return confirm('git gc --prune=all を実行します。通常数十秒かかります。よろしいですか？');">
          最適化（git gc）
        </a>
        &nbsp;
        <a href="online_update.php?ACTION=git_gc_aggressive&METHOD=git"
           class="btn btn-default btn-sm"
           onclick="return confirm('git gc --aggressive --prune=all を実行します。通常数分かかります。よろしいですか？');">
          徹底最適化（--aggressive）
        </a>
        <p class="text-muted" style="margin-top:8px; margin-bottom:0;">
          <small>通常最適化で大半の不要データを除去できます。徹底最適化はより小さくなりますが時間がかかります。</small>
        </p>
      </div>
    </div>

    <div class="alert alert-info" style="margin-top:0;">
      Git 方式: <code>git fetch</code> + <code>git reset --hard</code> でアップデートします。
    </div>
<?php
    $git_errmsg   = '';
    $git_taglist  = get_gittaglist($git_errmsg);               // fetch もここで実行
    $git_branches = get_gitbranchlist($git_errmsg, false);     // fetch 済みなのでスキップ
    $fetch_failed = !empty($git_errmsg) && $git_errmsg !== 'none';
?>
<?php if ($fetch_failed): ?>
    <div class="alert alert-warning">取得に失敗しました: <?php echo htmlspecialchars($git_errmsg); ?></div>
<?php else: ?>

    <!-- ブランチ一覧（折りたたみ） -->
<?php if (count($git_branches) > 0): ?>
    <div class="panel panel-default">
      <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#branchList">
        <strong>ブランチ一覧</strong>
        <span class="text-muted small">&nbsp;（<?php echo count($git_branches); ?> 件）&nbsp;▼</span>
      </div>
      <div id="branchList" class="panel-collapse collapse">
        <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
          <dl class="dl-horizontal" style="margin-bottom:0;">
<?php   foreach ($git_branches as $branch):
          $ver = 'origin/' . $branch; ?>
            <dt style="overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($branch); ?></dt>
            <dd>
              <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($ver); ?>&METHOD=git"
                 class="btn btn-<?php echo ($branch === 'master') ? 'primary' : 'default'; ?> btn-sm"
                 onclick="return confirm('<?php echo htmlspecialchars($branch, ENT_QUOTES); ?> ブランチに切り替えます。よろしいですか？');">更新</a>
            </dd>
<?php   endforeach; ?>
          </dl>
        </div>
      </div>
    </div>
<?php endif; ?>

    <!-- タグ一覧 -->
<?php if (count($git_taglist) === 0): ?>
    <div class="alert alert-info">タグが見つかりませんでした</div>
<?php else: ?>
    <h5><strong>タグ（リリース版）</strong></h5>
    <dl class="dl-horizontal">
<?php   foreach (array_reverse($git_taglist) as $tag):
          if (strcmp($tag, 'v0.09.5-alpha') === 0): ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>これ以前のバージョンはコマンドプロンプトでのコマンド実行が必要です</dd>
<?php       break; endif; ?>
      <dt><?php echo htmlspecialchars($tag); ?></dt>
      <dd>
        <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($tag); ?>&METHOD=git"
           class="btn btn-default btn-sm"
           onclick="return confirm('<?php echo htmlspecialchars($tag, ENT_QUOTES); ?> に更新します。よろしいですか？');">更新</a>
      </dd>
<?php   endforeach; ?>
    </dl>
<?php endif; // git_taglist ?>

    <div class="panel panel-default" style="margin-top:10px;">
      <div class="panel-body">
        <form method="GET" class="form-inline">
          <input type="hidden" name="METHOD" value="git" />
          <div class="form-group">
            <label>任意ブランチ / タグ / ハッシュ&nbsp;</label>
            <input type="text" name="UPDATEVERSION" class="form-control" placeholder="例: origin/my-branch" />
          </div>
          &nbsp;<input type="submit" value="実行" class="btn btn-warning" />
        </form>
      </div>
    </div>

<?php endif; // fetch_failed ?>
<?php endif; // git_repo_exists ?>

  </div><!-- /git tab pane -->

</div><!-- /tab-content -->

<?php else: /* git_available が false → ZIP のみ表示 + セットアップ案内 */
    echo '</div></div>';
?>

<!-- ============================================================ -->
<!-- Git 方式を有効にする手順（git 未設定時に表示）              -->
<!-- ============================================================ -->
<div class="panel-group" id="gitSetupAccordion" style="margin-top:20px;">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#gitSetupAccordion" href="#collapseGitSetup">
          Git 方式を有効にする手順（任意）
        </a>
      </h4>
    </div>
    <div id="collapseGitSetup" class="panel-collapse collapse">
      <div class="panel-body">

        <div class="alert alert-info">
          Git 方式を使うと過去の任意バージョンへの巻き戻しが可能になります。
          ただし <code>.git</code> フォルダのぶんディスクを多く使います（初期数 MB、以後増加）。
          通常は ZIP 方式で十分です。
        </div>

        <!-- Step 1 -->
        <h4>Step 1 &mdash; Portable Git を配置する</h4>
        <ol>
          <li>
            <a href="https://git-scm.com/download/win" target="_blank">git-scm.com</a> から
            <strong>Portable ("thumbdrive edition")</strong> 版をダウンロードします。
          </li>
          <li>
            ダウンロードした EXE を実行し、アプリフォルダ直下の <code>gitcmd</code> フォルダへ展開します。<br>
            <code>（例: C:\xampp\htdocs\gitcmd\）</code>
          </li>
          <li>
            展開後に <code>gitcmd\cmd\git.exe</code> が存在することを確認します。
          </li>
        </ol>

        <!-- Step 2 -->
        <h4>Step 2 &mdash; 管理画面でパスを設定する</h4>
        <ol>
          <li><a href="init.php" target="_blank">管理画面 (init.php)</a> を開きます。</li>
          <li>
            <strong>gitcommandpath</strong> の項目に <code>git.exe</code> の絶対パスを入力して保存します。<br>
            <code>（例: C:\xampp\htdocs\gitcmd\cmd\git.exe）</code>
          </li>
          <li>このページをリロードすると「Git 方式」タブが追加されます。</li>
        </ol>

<?php if ($git_configured && !$git_available): ?>
        <div class="alert alert-warning">
          <strong>注意:</strong> <code>gitcommandpath</code> は設定されていますが、
          ファイルが見つかりません。パスを確認してください。<br>
          設定値: <code><?php echo htmlspecialchars(urldecode($config_ini['gitcommandpath'])); ?></code>
        </div>
<?php endif; ?>

<?php if ($git_configured && $git_available && !$git_repo_exists): ?>
        <!-- git コマンドは使えるが .git がない → 初期化ボタンを表示 -->
        <h4>Step 3 &mdash; リポジトリを初期化する</h4>
        <p>git コマンドが利用可能です。下のボタンでリポジトリを初期化できます。</p>
        <a href="online_update.php?ACTION=git_init&METHOD=git"
           class="btn btn-warning"
           onclick="return confirm('リポジトリを初期化します（git init + fetch --depth=1）。\nconfig.ini・request.db などのユーザーデータは保護されます。よろしいですか？');">
          Git リポジトリを初期化する
        </a>
<?php else: ?>
        <!-- Step 3 (generic) -->
        <h4>Step 3 &mdash; リポジトリを初期化する</h4>
        <p>Step 2 完了後にこのページをリロードすると、「Git リポジトリを初期化する」ボタンが表示されます。
           ボタンを押すと <code>git init + fetch --depth=1</code> が自動実行されます。</p>
<?php endif; ?>

      </div><!-- /panel-body -->
    </div><!-- /collapse -->
  </div><!-- /panel -->
</div><!-- /accordion -->

<?php endif; // git_available ?>

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
