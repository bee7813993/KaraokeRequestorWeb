<!doctype html>
<html lang="ja">
<head>
<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
print_meta_header();
?>
<title>お気に入り検索ワード - マイページ</title>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/themes/_variables.css" rel="stylesheet">
<style>body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }</style>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage_favorite_keyword.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
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
$param_labels = [
    'anyword'      => 'なんでも検索',
    'song_name'    => '曲名検索',
    'filename'     => '曲名検索',
    'artist'       => '歌手名検索',
    'program_name' => '作品名検索',
    'maker_name'   => '製作会社検索',
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
            if (!empty($params['match'])) {
                $url .= '&match=' . urlencode($params['match']);
            }
            return $url;
        case 'listerdb_songlist':
            $param = !empty($params['param']) ? $params['param'] : 'song_name';
            $url = 'search_listerdb_songlist.php?' . $param . '=' . urlencode($keyword);
            if (!empty($params['lister_dbpath'])) {
                $url .= '&lister_dbpath=' . urlencode($params['lister_dbpath']);
            }
            if (!empty($params['match'])) {
                $url .= '&match=' . urlencode($params['match']);
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
<div class="container py-3">
  <h2 class="mb-2">お気に入り検索ワード</h2>
  <p class="mb-2"><a href="mypage.php">&laquo; マイページへ戻る</a></p>

  <?php if (empty($list)): ?>
  <p class="text-muted">お気に入り検索ワードが登録されていません。<br>
    検索結果の画面で「検索ワードを保存」リンクを押すと追加できます。
  </p>
  <?php else: ?>
  <div class="table-responsive">
  <table class="table table-striped table-sm table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <th>検索ワード</th>
        <th class="text-nowrap">検索種別</th>
        <th class="text-nowrap">登録日時</th>
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
        $row_params  = [];
        if (!empty($search_params)) parse_str($search_params, $row_params);
        $row_param   = !empty($row_params['param']) ? $row_params['param'] : '';
        if (!empty($row_param) && isset($param_labels[$row_param])) {
            $type_label = $param_labels[$row_param];
        } else {
            $type_label = isset($search_type_labels[$search_type]) ? $search_type_labels[$search_type] : $search_type;
        }
        $search_url  = build_search_url($kw, $search_type, $search_params);
    ?>
      <tr>
        <td><?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap"><?php echo htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap"><?php echo htmlspecialchars($added_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap">
          <a href="<?php echo htmlspecialchars($search_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">再検索</a>
          <form method="POST" action="mypage_favorite_keyword.php" class="d-inline"
                onsubmit="return confirm('削除しますか？');">
            <input type="hidden" name="action" value="remove" />
            <input type="hidden" name="kw_id" value="<?php echo $kw_id; ?>" />
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
