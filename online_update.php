<?php
require_once 'commonfunc.php';

$res    = 2;
$errmsg = '';

$req_version = isset($_REQUEST['UPDATEVERSION']) ? urldecode($_REQUEST['UPDATEVERSION']) : null;
$req_method  = (isset($_REQUEST['METHOD']) && $_REQUEST['METHOD'] === 'git') ? 'git' : 'zip';
$req_action  = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';

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
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>オンラインアップデート</title>
<?php print_bs5_head_core(); ?>
<style>
/* 非アクティブのタブ文字色をテーマカラーに合わせる */
#updateTabs .nav-link:not(.active) { color: var(--color-text); }
</style>
</head>
<body>
<?php shownavigatioinbar_bs5('online_update.php'); ?>

<div class="container py-3">
  <h4 class="mb-3">オンラインアップデート</h4>

<?php
if ($res === false) {
    echo '<div class="alert alert-danger alert-dismissible">'
       . htmlspecialchars($errmsg)
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
} elseif ($res === true) {
    if ($req_action === 'git_gc' || $req_action === 'git_gc_aggressive') {
        $label = 'リポジトリの最適化';
    } elseif ($req_action === 'git_init') {
        $label = 'Git リポジトリの初期化';
    } else {
        $label = 'アップデート';
    }
    echo '<div class="alert alert-success alert-dismissible">'
       . htmlspecialchars($label) . 'に成功しました'
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$curver = get_version();
if (!empty($curver)) {
    echo '<p class="text-muted mb-1"><small>現在のバージョン: <strong>' . htmlspecialchars($curver) . '</strong></small></p>';
}
echo '<p class="text-muted"><small>取得元リポジトリ: <code>' . htmlspecialchars(get_update_repo()) . '</code>'
   . ' <span class="text-muted">（config.ini の <code>update_repo</code> で変更可能）</span></small></p>';
?>

<?php if ($git_available): ?>
<!-- タブ -->
<ul class="nav nav-tabs mb-3" id="updateTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo ($active_tab === 'zip') ? 'active' : ''; ?>"
            id="zip-tab" data-bs-toggle="tab" data-bs-target="#tab-zip"
            type="button" role="tab">ZIP方式（推奨）</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo ($active_tab === 'git') ? 'active' : ''; ?>"
            id="git-tab" data-bs-toggle="tab" data-bs-target="#tab-git"
            type="button" role="tab">Git方式</button>
  </li>
</ul>
<div class="tab-content" id="updateTabsContent">

<div class="tab-pane fade <?php echo ($active_tab === 'zip') ? 'show active' : ''; ?>" id="tab-zip" role="tabpanel">
<?php else: ?>
<div>
<?php endif; ?>

<?php /* ========== ZIP 方式 ========== */ ?>
<?php
$zip_check = check_zip_update_available();
if ($zip_check !== true):
?>
  <div class="alert alert-warning"><?php echo htmlspecialchars($zip_check); ?></div>
<?php else:
    $zip_errmsg  = '';
    $zip_taglist = get_archive_taglist($zip_errmsg);
    if (!empty($zip_errmsg)):
?>
  <div class="alert alert-warning">タグ一覧の取得に失敗しました: <?php echo htmlspecialchars($zip_errmsg); ?></div>
<?php elseif (count($zip_taglist) === 0): ?>
  <div class="alert alert-info">タグ情報を取得できませんでした（ネットワークを確認してください）</div>
<?php else: ?>
  <div class="card mb-3">
    <div class="card-header fw-bold">バージョン選択</div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <tbody>
          <tr>
            <td class="align-middle"><strong>最新版 (master)</strong></td>
            <td class="text-end">
              <a href="online_update.php?UPDATEVERSION=<?php echo urlencode('master'); ?>&METHOD=zip"
                 class="btn btn-primary btn-sm"
                 onclick="return confirm('master ブランチの最新版に更新します。よろしいですか？');">更新</a>
            </td>
          </tr>
<?php   foreach ($zip_taglist as $tag):
          if (strcmp($tag, 'v0.09.5-alpha') === 0): ?>
          <tr>
            <td colspan="2" class="text-muted small"><?php echo htmlspecialchars($tag); ?> 以前はコマンドプロンプトでの操作が必要です</td>
          </tr>
<?php       break; endif; ?>
          <tr>
            <td class="align-middle"><?php echo htmlspecialchars($tag); ?></td>
            <td class="text-end">
              <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($tag); ?>&METHOD=zip"
                 class="btn btn-outline-secondary btn-sm"
                 onclick="return confirm('<?php echo htmlspecialchars($tag, ENT_QUOTES); ?> を反映します。よろしいですか？');">反映</a>
            </td>
          </tr>
<?php   endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header fw-bold">任意タグ / ブランチ / コミットハッシュ</div>
    <div class="card-body">
      <form method="GET" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="METHOD" value="zip" />
        <input type="text" name="UPDATEVERSION" class="form-control form-control-sm" style="max-width:280px;"
               placeholder="例: v0.09.9-alpha / 3a4b5c6" />
        <button type="submit" class="btn btn-warning btn-sm"
                onclick="return confirm('指定バージョンで更新します。よろしいですか？');">実行</button>
      </form>
    </div>
  </div>
<?php endif; endif; // zip_check / taglist ?>

<?php if ($git_available): ?>
</div><!-- /tab-zip -->

<?php /* ========== Git 方式 ========== */ ?>
<div class="tab-pane fade <?php echo ($active_tab === 'git') ? 'show active' : ''; ?>" id="tab-git" role="tabpanel">

<?php if (!$git_repo_exists): ?>
  <div class="card border-warning mb-3">
    <div class="card-header bg-warning bg-opacity-25 fw-bold">.git フォルダが見つかりません</div>
    <div class="card-body">
      <p class="mb-2">Git 方式でアップデートするには、まずリポジトリを初期化してください。<br>
         <small class="text-muted">初期化後は <code>.git</code> フォルダが作成されます（数 MB、shallow clone）。</small></p>
      <a href="online_update.php?ACTION=git_init&METHOD=git" class="btn btn-warning"
         onclick="return confirm('リポジトリを初期化します（git init + fetch --depth=1 + reset --hard）。\nconfig.ini・request.db などのユーザーデータは保護されます。よろしいですか？');">
        Git リポジトリを初期化する
      </a>
    </div>
  </div>
<?php else:
    $git_errmsg     = '';
    $git_taglist    = get_gittaglist($git_errmsg);
    $git_branches   = get_gitbranchlist($git_errmsg, false);
    $fetch_failed   = !empty($git_errmsg) && $git_errmsg !== 'none';
    $current_branch = get_current_git_branch();

    // master を先頭に、残りは新しい順を維持
    $master_key = array_search('master', $git_branches, true);
    if ($master_key !== false) {
        array_splice($git_branches, $master_key, 1);
        array_unshift($git_branches, 'master');
    }
?>

  <!-- (1) 現在のブランチ + アップデートボタン -->
  <div class="card border-primary mb-3">
    <div class="card-header bg-primary bg-opacity-10 fw-bold">現在のブランチ</div>
    <div class="card-body">
<?php if ($current_branch !== null): ?>
      <p class="mb-2">
        <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($current_branch); ?></span>
      </p>
      <a href="online_update.php?UPDATEVERSION=<?php echo urlencode('origin/' . $current_branch); ?>&METHOD=git"
         class="btn btn-primary"
         onclick="return confirm('<?php echo htmlspecialchars($current_branch, ENT_QUOTES); ?> ブランチを最新に更新します。よろしいですか？');">
        このブランチの最新に更新
      </a>
<?php else: ?>
      <p class="text-muted mb-0">ブランチ情報を取得できませんでした</p>
<?php endif; ?>
    </div>
  </div>

<?php if ($fetch_failed): ?>
  <div class="alert alert-warning">リモート情報の取得に失敗しました: <?php echo htmlspecialchars($git_errmsg); ?></div>
<?php else: ?>

  <!-- (2) ブランチ選択（折りたたみ） -->
<?php if (count($git_branches) > 0): ?>
  <div class="card mb-3">
    <div class="card-header p-0">
      <button class="btn btn-link text-decoration-none text-start w-100 px-3 py-2 fw-bold" style="color: var(--color-text);"
              type="button" data-bs-toggle="collapse" data-bs-target="#branchList">
        ブランチ選択
        <span class="badge bg-secondary ms-1"><?php echo count($git_branches); ?></span>
      </button>
    </div>
    <div id="branchList" class="collapse">
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <tbody>
<?php   foreach ($git_branches as $branch):
          $is_current = ($branch === $current_branch); ?>
            <tr class="<?php echo $is_current ? 'table-active' : ''; ?>">
              <td class="align-middle">
                <?php echo htmlspecialchars($branch); ?>
                <?php if ($is_current): ?><span class="badge bg-primary ms-1">現在</span><?php endif; ?>
              </td>
              <td class="text-end align-middle">
<?php       if (!$is_current): ?>
                <a href="online_update.php?UPDATEVERSION=<?php echo urlencode('origin/' . $branch); ?>&METHOD=git"
                   class="btn btn-outline-secondary btn-sm"
                   onclick="return confirm('<?php echo htmlspecialchars($branch, ENT_QUOTES); ?> ブランチに切り替えます。よろしいですか？');">切替</a>
<?php       else: ?>
                <span class="text-muted small">選択中</span>
<?php       endif; ?>
              </td>
            </tr>
<?php   endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

  <!-- (3) タグ一覧（折りたたみ） -->
<?php if (count($git_taglist) > 0): ?>
  <div class="card mb-3">
    <div class="card-header p-0">
      <button class="btn btn-link text-decoration-none text-start w-100 px-3 py-2 fw-bold" style="color: var(--color-text);"
              type="button" data-bs-toggle="collapse" data-bs-target="#tagList">
        タグ一覧（リリース版）
        <span class="badge bg-secondary ms-1"><?php echo count($git_taglist); ?></span>
      </button>
    </div>
    <div id="tagList" class="collapse">
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <tbody>
<?php   foreach (array_reverse($git_taglist) as $tag):
          if (strcmp($tag, 'v0.09.5-alpha') === 0): ?>
            <tr>
              <td colspan="2" class="text-muted small"><?php echo htmlspecialchars($tag); ?> 以前はコマンドプロンプトでの操作が必要です</td>
            </tr>
<?php       break; endif; ?>
            <tr>
              <td class="align-middle"><?php echo htmlspecialchars($tag); ?></td>
              <td class="text-end align-middle">
                <a href="online_update.php?UPDATEVERSION=<?php echo urlencode($tag); ?>&METHOD=git"
                   class="btn btn-outline-secondary btn-sm"
                   onclick="return confirm('<?php echo htmlspecialchars($tag, ENT_QUOTES); ?> を反映します。よろしいですか？');">反映</a>
              </td>
            </tr>
<?php   endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

  <!-- (4) 任意ブランチ / タグ / ハッシュ -->
  <div class="card mb-3">
    <div class="card-header fw-bold">任意ブランチ / タグ / ハッシュ</div>
    <div class="card-body">
      <form method="GET" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="METHOD" value="git" />
        <input type="text" name="UPDATEVERSION" class="form-control form-control-sm" style="max-width:280px;"
               placeholder="例: origin/my-branch" />
        <button type="submit" class="btn btn-warning btn-sm"
                onclick="return confirm('指定のブランチ/タグ/ハッシュに切り替えます。よろしいですか？');">実行</button>
      </form>
    </div>
  </div>

<?php endif; // fetch_failed ?>

  <!-- (5) リポジトリ最適化 -->
<?php
    $git_size     = get_git_dir_size();
    $git_size_str = $git_size !== null ? format_filesize($git_size) : '取得失敗';
    $git_cmd_ver  = get_git_command_version();
?>
  <div class="card mb-3">
    <div class="card-header fw-bold">リポジトリ最適化</div>
    <div class="card-body">
      <p class="mb-1">
        <small class="text-muted">git バージョン: </small>
        <?php if ($git_cmd_ver !== null): ?>
          <code class="small"><?php echo htmlspecialchars($git_cmd_ver); ?></code>
          <small class="text-muted">（gitcmd フォルダの git.exe を差し替えると更新できます）</small>
        <?php else: ?>
          <span class="text-muted small">取得失敗</span>
        <?php endif; ?>
      </p>
      <p class="mb-2">
        <small class="text-muted">.git フォルダ サイズ: </small>
        <strong><?php echo htmlspecialchars($git_size_str); ?></strong>
        <small class="text-muted">（アップデート後に git gc を実行すると削減できます）</small>
      </p>
      <div class="d-flex gap-2">
        <a href="online_update.php?ACTION=git_gc&METHOD=git" class="btn btn-outline-secondary btn-sm"
           onclick="return confirm('git gc --prune=all を実行します。通常数十秒かかります。よろしいですか？');">
          最適化（git gc）
        </a>
        <a href="online_update.php?ACTION=git_gc_aggressive&METHOD=git" class="btn btn-outline-secondary btn-sm"
           onclick="return confirm('git gc --aggressive --prune=all を実行します。通常数分かかります。よろしいですか？');">
          徹底最適化（--aggressive）
        </a>
      </div>
      <p class="text-muted mt-2 mb-0"><small>通常最適化で大半の不要データを除去できます。徹底最適化はより小さくなりますが時間がかかります。</small></p>
    </div>
  </div>

<?php endif; // git_repo_exists ?>
</div><!-- /tab-git -->
</div><!-- /tab-content -->

<?php else: // git_available が false ?>
</div>

<!-- Git 方式を有効にする手順 -->
<div class="accordion mt-4" id="gitSetupAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button"
              data-bs-toggle="collapse" data-bs-target="#collapseGitSetup">
        Git 方式を有効にする手順（任意）
      </button>
    </h2>
    <div id="collapseGitSetup" class="accordion-collapse collapse">
      <div class="accordion-body">
        <div class="alert alert-info">
          Git 方式を使うと過去の任意バージョンへの巻き戻しが可能になります。
          ただし <code>.git</code> フォルダのぶんディスクを多く使います。通常は ZIP 方式で十分です。
        </div>

        <h6>Step 1 &mdash; Portable Git を配置する</h6>
        <ol>
          <li><a href="https://git-scm.com/download/win" target="_blank">git-scm.com</a> から <strong>Portable ("thumbdrive edition")</strong> 版をダウンロード</li>
          <li>ダウンロードした EXE を実行し、アプリフォルダ直下の <code>gitcmd</code> フォルダへ展開<br>
              <code class="small">例: C:\xampp\htdocs\gitcmd\</code></li>
          <li><code>gitcmd\cmd\git.exe</code> が存在することを確認</li>
        </ol>

        <h6>Step 2 &mdash; 管理画面でパスを設定する</h6>
        <ol>
          <li><a href="init.php" target="_blank">管理画面 (init.php)</a> を開く</li>
          <li><strong>gitcommandpath</strong> に <code>git.exe</code> の絶対パスを入力して保存<br>
              <code class="small">例: C:\xampp\htdocs\gitcmd\cmd\git.exe</code></li>
          <li>このページをリロードすると「Git 方式」タブが追加されます</li>
        </ol>

<?php if ($git_configured && !$git_available): ?>
        <div class="alert alert-warning">
          <strong>注意:</strong> <code>gitcommandpath</code> は設定されていますが、ファイルが見つかりません。パスを確認してください。<br>
          設定値: <code><?php echo htmlspecialchars(urldecode($config_ini['gitcommandpath'])); ?></code>
        </div>
<?php endif; ?>

<?php if ($git_configured && $git_available && !$git_repo_exists): ?>
        <h6>Step 3 &mdash; リポジトリを初期化する</h6>
        <p>git コマンドが利用可能です。下のボタンでリポジトリを初期化できます。</p>
        <a href="online_update.php?ACTION=git_init&METHOD=git" class="btn btn-warning"
           onclick="return confirm('リポジトリを初期化します（git init + fetch --depth=1）。\nconfig.ini・request.db などのユーザーデータは保護されます。よろしいですか？');">
          Git リポジトリを初期化する
        </a>
<?php else: ?>
        <h6>Step 3 &mdash; リポジトリを初期化する</h6>
        <p class="text-muted">Step 2 完了後にこのページをリロードすると「Git リポジトリを初期化する」ボタンが表示されます。</p>
<?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php endif; // git_available ?>

<hr class="mt-4"/>
<p class="text-muted small">
  バージョン情報:
  <a href="https://github.com/<?php echo htmlspecialchars(get_update_repo(), ENT_QUOTES); ?>/commits/master" target="_blank">
    GitHub コミット履歴
  </a>
</p>
</div><!-- /container -->
<?php print_bg_style_block(true); ?>
</body>
</html>
