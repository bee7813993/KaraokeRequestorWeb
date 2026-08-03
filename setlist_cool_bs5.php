<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';

$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

$selectid = '';
if (array_key_exists('id', $_REQUEST)) $selectid = $_REQUEST['id'];
if (array_key_exists('selectid', $_REQUEST)) $selectid = $_REQUEST['selectid'];
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>クール一覧</title>
<?php print_bs5_search_head('css/themes/setlist.css'); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>

<div class="container py-3 setlist-page">
<?php echo build_reservation_tabs($selectid, 'setlist'); ?>

  <section class="setlist-hero" aria-label="ゆかりすたー検索">
    <form id="setlistSearchForm" class="setlist-search" action="search_listerdb_filelist.php" method="get">
      <?php if ($selectid !== ''): ?>
      <input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <input type="text" name="anyword" id="setlistSearchWord" placeholder="作品名・曲名・歌手名でゆかりすたー検索" autocomplete="off">
      <select id="setlistSearchTarget" class="form-select" aria-label="検索対象">
        <option value="anyword">キーワード</option>
        <option value="program_name">作品名</option>
        <option value="song_name">曲名</option>
        <option value="song_artist">歌手名</option>
      </select>
      <button type="submit">検索</button>
    </form>
  </section>

  <section class="setlist-content" aria-label="クール集計とランキング">
    <div class="setlist-tabs" role="tablist" aria-label="集計表示">
      <button type="button" class="setlist-tab active" data-setlist-tab="cool">クール集計</button>
      <button type="button" class="setlist-tab" data-setlist-tab="ranking">ランキング</button>
    </div>

    <div class="setlist-toolbar">
      <div class="setlist-filter">
        <input type="search" id="setlistFilter" placeholder="集計内を絞り込み">
      </div>
      <select id="setlistCoolSelect" class="form-select" aria-label="カテゴリ">
        <option value="">読み込み中</option>
      </select>
      <select id="setlistSortSelect" class="form-select" aria-label="並び順">
        <option value="count">歌唱数順</option>
      </select>
    </div>

    <div id="setlistStatus" class="setlist-status" role="status">読み込み中...</div>
    <div id="setlistCoolPanel" class="setlist-panel active" data-endpoint="setlist_stats_json.php"></div>
    <div id="setlistRankingPanel" class="setlist-panel"></div>
  </section>
</div>

<script src="js/setlist_cool.js"></script>
</body>
</html>
