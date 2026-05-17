<!doctype html>
<html lang="ja">
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>選曲履歴 - マイページ</title>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/themes/_variables.css" rel="stylesheet">
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }</style>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage_history.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);

// 削除処理
if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['fullpath'])) {
    $mypage->deleteHistoryByFullpath($_POST['fullpath']);
    $qs = http_build_query(['sort' => $_GET['sort'] ?? 'date', 'order' => $_GET['order'] ?? 'desc']);
    header('Location: mypage_history.php?' . $qs);
    exit;
}

$valid_sorts = ['date', 'count', 'filedate'];
$sort  = in_array($_GET['sort'] ?? '', $valid_sorts, true) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

$history = $mypage->getHistory($sort, $order);

function sort_link_h($label, $sort_key, $cur_sort, $cur_order) {
    $next_order = ($cur_sort === $sort_key && $cur_order === 'desc') ? 'asc' : 'desc';
    $arrow = '';
    $active_class = '';
    if ($cur_sort === $sort_key) {
        $arrow = $cur_order === 'desc' ? ' ▼' : ' ▲';
        $active_class = ' fw-bold';
    }
    $url = 'mypage_history.php?sort=' . urlencode($sort_key) . '&order=' . urlencode($next_order);
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="link-secondary' . $active_class . '">'
         . htmlspecialchars($label . $arrow, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<div class="container py-3">
  <h2 class="mb-2">選曲履歴</h2>
  <p class="mb-2"><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small">並び替え:</span>
    <?php echo sort_link_h('リクエスト日順', 'date', $sort, $order); ?>
    <span class="text-muted">|</span>
    <?php echo sort_link_h('回数順', 'count', $sort, $order); ?>
    <span class="text-muted">|</span>
    <?php echo sort_link_h('動画更新日順', 'filedate', $sort, $order); ?>
  </div>

  <?php if (empty($history)): ?>
  <p class="text-muted">まだ選曲履歴がありません。</p>
  <?php else: ?>
  <div class="table-responsive">
  <table class="table table-striped table-sm table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <th>曲名</th>
        <th class="text-nowrap">回数</th>
        <th class="text-nowrap">最終リクエスト日時</th>
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
        $basename  = !empty($fullpath) ? basename_jp($fullpath) : $songfile;
        $status = MypageUser::checkFileStatus($fullpath, $songfile);
        $songname  = !empty($status['song_name']) ? $status['song_name'] : makesongnamefromfilename($basename);
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
            <br><span class="text-danger small">[!] ファイルが見つかりません</span>
          <?php elseif ($status['status'] === 'relocated'): ?>
            <br><span class="text-warning small">[!] 別フォルダで見つかりました</span>
          <?php endif; ?>
        </td>
        <td class="text-nowrap"><?php echo $times; ?>回</td>
        <td class="text-nowrap"><?php echo htmlspecialchars($last_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap">
          <?php if ($status['status'] === 'ok' || $status['status'] === 'relocated'): ?>
            <a href="<?php echo htmlspecialchars($req_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">再選曲</a>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">曲名で再検索</a>
          <?php endif; ?>
          <form method="POST" action="mypage_history.php?sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>"
                class="d-inline"
                onsubmit="return confirm('この曲の履歴をすべて削除しますか？');">
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="fullpath" value="<?php echo htmlspecialchars($fullpath, ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
