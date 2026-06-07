<?php
require_once 'commonfunc.php';
require_once 'mypage_class.php';
$mypage = null;
if (configbool("usemypage", true)) {
    $mypage = new MypageUser($db);
    // 削除処理（header() を呼ぶため HTML 出力前に処理）
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && !empty($_POST['kw_id'])) {
        $mypage->removeFavoriteKeyword((int)$_POST['kw_id']);
        header('Location: mypage_favorite_keyword.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>お気に入り検索ワード - マイページ</title>
<script>(function(){if(window.__ykThemeInit)return;window.__ykThemeInit=true;try{var t=localStorage.getItem("ykari-theme")||"light",f=localStorage.getItem("ykari-fontsize")||"normal";document.documentElement.setAttribute("data-theme",t);document.documentElement.setAttribute("data-fontsize",f);}catch(e){}})();</script>
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet">
<link href="css/themes/_variables.css" rel="stylesheet">
<link rel="stylesheet" href="css/themes/theme-toggle.css">
<style>
body { background-color: var(--bg-page); background-image: var(--bg-page-image); background-size: cover; background-attachment: fixed; padding-top: 70px; }
/* 検索ワードは長い作品名でも自然に折り返す */
.fav-kw-table td.fav-kw-word { white-space: normal; word-break: break-word; }
/* スマホ幅ではテーブルを行ごとのカード表示に切り替える */
@media (max-width: 767.98px) {
  .fav-kw-table thead { display: none; }
  .fav-kw-table, .fav-kw-table tbody, .fav-kw-table tr, .fav-kw-table td { display: block; width: 100%; }
  .fav-kw-table tr {
    margin-bottom: .75rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: .5rem;
    padding: .6rem .85rem;
    background-color: rgba(var(--bg-card-rgb, 255,255,255), var(--bg-card-alpha, 1));
  }
  /* table-striped の縞模様はカード表示では無効化 */
  .fav-kw-table.table-striped > tbody > tr:nth-of-type(odd) > td { background-color: transparent; }
  .fav-kw-table td {
    border: none !important;
    padding: .2rem 0;
    text-align: left !important;
    white-space: normal !important;
  }
  /* 各セルの前に項目名ラベルを表示 */
  .fav-kw-table td::before {
    content: attr(data-label);
    display: block;
    font-size: .75rem;
    font-weight: 600;
    color: var(--color-text-muted, #6c757d);
    margin-bottom: .1rem;
  }
  .fav-kw-table td.fav-kw-word { font-size: 1.05rem; font-weight: 600; }
  .fav-kw-table td.fav-kw-actions { padding-top: .5rem; }
  .fav-kw-table td.fav-kw-actions .btn { min-width: 4.5rem; }
}
</style>
<script src="js/bootstrap5/bootstrap.bundle.min.js"></script>
<script src="js/theme-toggle.js"></script>
</head>
<body>
<?php
shownavigatioinbar_bs5('mypage_favorite_keyword.php');

if (!configbool("usemypage", true)) {
    print '<div class="container py-3"><p>マイページ機能は無効です。</p></div>';
    print '</body></html>';
    exit;
}
// $mypage は冒頭の PHP ブロックで初期化済み

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
  <table class="table table-striped table-sm table-hover align-middle fav-kw-table">
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
        <td class="fav-kw-word" data-label="検索ワード"><?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap" data-label="検索種別"><?php echo htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap" data-label="登録日時"><?php echo htmlspecialchars($added_dt, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-nowrap fav-kw-actions" data-label="操作">
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
