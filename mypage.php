<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);
$displayname = $mypage->getDisplayName();

// 表示名変更処理
$msg = '';
if (isset($_POST['action']) && $_POST['action'] === 'update_name') {
    $newname = isset($_POST['displayname']) ? $_POST['displayname'] : '';
    if ($newname !== '') {
        $mypage->updateDisplayName($newname);
        $displayname = htmlspecialchars(mb_substr(trim($newname), 0, 64), ENT_QUOTES, 'UTF-8');
        $msg = '表示名を更新しました。';
    }
}
?>
<div class="container" style="margin-top:80px;">
  <h2>マイページ</h2>

  <?php if ($msg): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">あなたの情報</h4></div>
    <div class="panel-body">
      <form method="POST" action="mypage.php" class="form-inline">
        <input type="hidden" name="action" value="update_name" />
        <div class="form-group">
          <label>表示名: </label>&nbsp;
          <input type="text" name="displayname" class="form-control"
                 value="<?php echo htmlspecialchars($displayname, ENT_QUOTES, 'UTF-8'); ?>"
                 maxlength="64" placeholder="名前を入力" />
        </div>
        &nbsp;
        <button type="submit" class="btn btn-default">変更</button>
      </form>
      <p class="text-muted" style="margin-top:8px;font-size:small;">
        ユーザーID: <?php echo htmlspecialchars($mypage->getUserId(), ENT_QUOTES, 'UTF-8'); ?>
      </p>
    </div>
  </div>

  <div class="row">
    <div class="col-xs-12 col-sm-6 col-md-3">
      <a href="mypage_history.php" class="btn btn-primary btn-lg btn-block" style="margin-bottom:10px;">
        選曲履歴
      </a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3">
      <a href="mypage_later.php" class="btn btn-success btn-lg btn-block" style="margin-bottom:10px;">
        後で歌う
      </a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3">
      <a href="mypage_favorite_song.php" class="btn btn-warning btn-lg btn-block" style="margin-bottom:10px;">
        お気に入り曲
      </a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3">
      <a href="mypage_favorite_keyword.php" class="btn btn-info btn-lg btn-block" style="margin-bottom:10px;">
        お気に入り検索ワード
      </a>
    </div>
  </div>

  <hr>
  <p>
    <a href="mypage_link_device.php">別の端末でも同じマイページを使う（デバイスリンク）</a>
  </p>
</div>
</body>
</html>
