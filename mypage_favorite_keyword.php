<html>
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>お気に入り検索ワード - マイページ</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar('mypage_favorite_keyword.php');

if (!configbool("usemypage", true)) {
    print '<div class="container" style="margin-top:80px;"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}

$mypage = new MypageUser($db);

// 削除処理
if (isset($_POST['action']) && $_POST['action'] === 'remove' && !empty($_POST['kw_id'])) {
    $mypage->removeFavoriteKeyword((int)$_POST['kw_id']);
    header('Location: mypage_favorite_keyword.php');
    exit;
}

$list = $mypage->getFavoriteKeywords();

$search_type_labels = [
    'search'              => 'ファイル検索',
    'listerdb_filelist'   => '曲ファイル検索',
    'listerdb_songlist'   => '曲名検索',
    'anisoninfo'          => 'アニソン検索',
];

function build_search_url($keyword, $search_type, $search_params) {
    $params = [];
    if (!empty($search_params)) {
        parse_str($search_params, $params);
    }
    switch ($search_type) {
        case 'listerdb_filelist':
            $param = !empty($params['param']) ? $params['param'] : 'song_name';
            $url = 'search_listerdb_filelist.php?' . $param . '=' . urlencode($keyword);
            if (!empty($params['lister_dbpath'])) {
                $url .= '&lister_dbpath=' . urlencode($params['lister_dbpath']);
            }
            return $url;
        case 'listerdb_songlist':
            $param = !empty($params['param']) ? $params['param'] : 'song_name';
            $url = 'search_listerdb_songlist.php?' . $param . '=' . urlencode($keyword);
            if (!empty($params['lister_dbpath'])) {
                $url .= '&lister_dbpath=' . urlencode($params['lister_dbpath']);
            }
            return $url;
        case 'anisoninfo':
            return 'search_anisoninfo_common.php?searchword=' . urlencode($keyword);
        case 'search':
        default:
            return 'search.php?searchword=' . urlencode($keyword);
    }
}
?>
<div class="container" style="margin-top:80px;">
  <h2>お気に入り検索ワード</h2>
  <p><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if (empty($list)): ?>
  <p class="text-muted">お気に入り検索ワードが登録されていません。<br>
    検索結果の画面で「検索ワードを保存」リンクを押すと追加できます。
  </p>
  <?php else: ?>
  <table class="table table-striped table-condensed">
    <thead>
      <tr>
        <th>検索ワード</th>
        <th>検索種別</th>
        <th>登録日時</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $row):
        $kw          = $row['keyword'];
        $search_type = $row['search_type'];
        $search_params = $row['search_params'];
        $kw_id       = (int)$row['id'];
        $added_dt    = date('Y/m/d H:i', $row['added_at']);
        $type_label  = isset($search_type_labels[$search_type]) ? $search_type_labels[$search_type] : $search_type;
        $search_url  = build_search_url($kw, $search_type, $search_params);
    ?>
      <tr>
        <td><?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($added_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-xs">再検索</a>
          &nbsp;
          <form method="POST" action="mypage_favorite_keyword.php" style="display:inline;"
                onsubmit="return confirm('削除しますか？');">
            <input type="hidden" name="action" value="remove" />
            <input type="hidden" name="kw_id" value="<?php echo $kw_id; ?>" />
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
