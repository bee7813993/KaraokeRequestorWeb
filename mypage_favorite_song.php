<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>お気に入り曲 - マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage_favorite_song.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);

// 削除処理
if (isset($_POST['action']) && $_POST['action'] === 'remove' && !empty($_POST['fullpath'])) {
    $mypage->removeFavoriteSong($_POST['fullpath']);
    header('Location: mypage_favorite_song.php');
    exit;
}

$list = $mypage->getFavoriteSongs();
?>
<div class="container" style="margin-top:80px;">
  <h2>お気に入り曲</h2>
  <p><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if (empty($list)): ?>
  <p class="text-muted">お気に入りに曲が登録されていません。<br>
    検索結果の画面で「お気に入り」リンクを押すと追加できます。
  </p>
  <?php else: ?>
  <table class="table table-striped table-condensed">
    <thead>
      <tr>
        <th>曲名</th>
        <th>登録日時</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $row):
        $songfile = $row['songfile'];
        $fullpath = $row['fullpath'];
        $kind     = $row['kind'];
        $added_dt = date('Y/m/d H:i', $row['added_at']);
        $basename  = !empty($fullpath) ? basename_jp($fullpath) : $songfile;
        $songname  = makesongnamefromfilename($basename);

        $status     = MypageUser::checkFileStatus($fullpath, $songfile);
        $req_fullpath = ($status['status'] === 'relocated') ? $status['fullpath'] : $fullpath;
        $req_url    = MypageUser::makeRequestConfirmUrl($req_fullpath, $songfile, $kind);
        $search_url = MypageUser::makeSearchFallbackUrl($songfile);
    ?>
      <tr>
        <td>
          <?php echo htmlspecialchars($songname, ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($basename !== $songname): ?>
            <br><span class="text-muted" style="font-size:x-small;"><?php echo htmlspecialchars($basename, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if ($status['status'] === 'notfound'): ?>
            <br><span class="text-danger" style="font-size:small;">[!] ファイルが見つかりません</span>
          <?php elseif ($status['status'] === 'relocated'): ?>
            <br><span class="text-warning" style="font-size:small;">[!] 別フォルダで見つかりました</span>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($added_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if ($status['status'] === 'ok' || $status['status'] === 'relocated'): ?>
            <a href="<?php echo htmlspecialchars($req_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-xs">リクエスト</a>
            <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default btn-xs">曲名で再検索</a>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-xs">曲名で再検索</a>
          <?php endif; ?>
          &nbsp;
          <form method="POST" action="mypage_favorite_song.php" style="display:inline;"
                onsubmit="return confirm('お気に入りから削除しますか？');">
            <input type="hidden" name="action" value="remove" />
            <input type="hidden" name="fullpath" value="<?php echo htmlspecialchars($fullpath, ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="btn btn-danger btn-xs">削除</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</body>
</html>
