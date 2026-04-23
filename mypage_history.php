<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>選曲履歴 - マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage_history.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);

// 削除処理
if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['fullpath'])) {
    $mypage->deleteHistoryByFullpath($_POST['fullpath']);
    header('Location: mypage_history.php?sort=' . urlencode($_GET['sort'] ?? 'date') . '&order=' . urlencode($_GET['order'] ?? 'desc'));
    exit;
}

$sort  = isset($_GET['sort'])  && $_GET['sort']  === 'count' ? 'count' : 'date';
$order = isset($_GET['order']) && $_GET['order'] === 'asc'   ? 'asc'   : 'desc';

$history = $mypage->getHistory($sort, $order);

function sort_link($label, $sort_key, $cur_sort, $cur_order) {
    $next_order = ($cur_sort === $sort_key && $cur_order === 'desc') ? 'asc' : 'desc';
    $active = ($cur_sort === $sort_key) ? ' class="active"' : '';
    $arrow  = '';
    if ($cur_sort === $sort_key) {
        $arrow = $cur_order === 'desc' ? ' ▼' : ' ▲';
    }
    return '<a href="mypage_history.php?sort=' . $sort_key . '&order=' . $next_order . '"' . $active . '>'
         . htmlspecialchars($label . $arrow, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<div class="container" style="margin-top:80px;">
  <h2>選曲履歴</h2>
  <p><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <p>
    並び替え: <?php echo sort_link('日付順', 'date', $sort, $order); ?>
    &nbsp;|&nbsp;
    <?php echo sort_link('回数順', 'count', $sort, $order); ?>
  </p>

  <?php if (empty($history)): ?>
  <p class="text-muted">まだ選曲履歴がありません。</p>
  <?php else: ?>
  <table class="table table-striped table-condensed">
    <thead>
      <tr>
        <th>曲名</th>
        <th>リクエスト回数</th>
        <th>最終リクエスト日時</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($history as $row):
        $songfile = $row['songfile'];
        $fullpath = $row['fullpath'];
        $kind     = $row['kind'];
        $times    = (int)$row['times'];
        $last_dt  = date('Y/m/d H:i', $row['last_requested_at']);

        $status = MypageUser::checkFileStatus($fullpath, $songfile);
        $req_url = MypageUser::makeRequestConfirmUrl($fullpath, $songfile, $kind);
        $search_url = MypageUser::makeSearchFallbackUrl($songfile);
    ?>
      <tr>
        <td>
          <?php echo htmlspecialchars($songfile, ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($status['status'] === 'notfound'): ?>
            <br><span class="text-danger" style="font-size:small;">[!] ファイルが見つかりません</span>
          <?php endif; ?>
        </td>
        <td><?php echo $times; ?>回</td>
        <td><?php echo htmlspecialchars($last_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if ($status['status'] === 'ok'): ?>
            <a href="<?php echo htmlspecialchars($req_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-xs">再選曲</a>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-xs">キーワードで検索</a>
          <?php endif; ?>
          &nbsp;
          <form method="POST" action="mypage_history.php?sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>"
                style="display:inline;"
                onsubmit="return confirm('この曲の履歴をすべて削除しますか？');">
            <input type="hidden" name="action" value="delete" />
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
