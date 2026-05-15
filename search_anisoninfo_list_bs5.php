<?php
require_once 'modules/simple_html_dom.php';
require_once 'search_anisoninfo_common.php';
require_once 'commonfunc.php';
require_once 'search_listerdb_commonfunc_bs5.php';

$l_m = array_key_exists("m", $_REQUEST) ? $_REQUEST["m"] : null;
$l_q = null;
if (array_key_exists("q", $_REQUEST)) {
    $l_q = $_REQUEST["q"];
    if ($historylog == 1) {
        searchwordhistory('anisoninfo:' . $l_q);
    }
}
$l_fullparam = array_key_exists("fullparam", $_REQUEST) ? urldecode($_REQUEST["fullparam"]) : null;
$l_order     = array_key_exists("order",     $_REQUEST) ? urldecode($_REQUEST["order"])     : null;
$selectid    = array_key_exists("selectid",  $_REQUEST) ? $_REQUEST["selectid"]             : '';
$year        = array_key_exists("year",      $_REQUEST) ? $_REQUEST["year"]                 : '';
$genre       = array_key_exists("genre",     $_REQUEST) ? $_REQUEST["genre"]                : '';

$linkoption = !empty($selectid) ? 'selectid=' . rawurlencode($selectid) : '';

function ansoninfo_gettitlelist_list_bs5($url, $l_m) {
    for ($checktimes = 0; $checktimes < 3; $checktimes++) {
        $results = array();
        $html = file_get_html_with_retry($url);
        if ($html === FALSE) continue;
        $result_dom = str_get_html($html);

        if (strcmp('pro', $l_m) == 0) {
            foreach ($result_dom->find('table.sorted') as $list) {
                foreach ($list->find('tr') as $tr) {
                    $genre_td = $tr->find('td[headers=genre]', 0);
                    if (empty($genre_td)) $genre_td = "";
                    foreach ($tr->find('a') as $list_a) {
                        $linkpath  = $list_a->href;
                        $foundword = $list_a->plaintext;
                    }
                    $onair = $tr->find('td[headers=onair]', 0);
                    if (!isset($foundword)) continue;
                    $results[] = array('genre' => is_object($genre_td) ? $genre_td->plaintext : '', 'word' => $foundword, 'link' => $linkpath, 'onair' => $onair->plaintext);
                }
            }
            $prev = $result_dom->find('td.seekPrev', 0);
            if (isset($prev)) $results['prevlink'] = $prev->find('a', 0)->href;
            $next = $result_dom->find('td.seekNext', 0);
            if (isset($next)) $results['nextlink'] = $next->find('a', 0)->href;
        } elseif (strcmp('person', $l_m) == 0) {
            foreach ($result_dom->find('table.list') as $list) {
                foreach ($list->find('td.list') as $p) {
                    $results[] = array('word' => $p->plaintext, 'link' => $p->find('a', 0)->href);
                }
            }
            $prev = $result_dom->find('td.seekPrev', 0);
            if (isset($prev)) $results['prevlink'] = $prev->find('a', 0)->href;
            $next = $result_dom->find('td.seekNext', 0);
            if (isset($next)) $results['nextlink'] = $next->find('a', 0)->href;
        } elseif (strcmp('mkr', $l_m) == 0) {
            foreach ($result_dom->find('table.list') as $list) {
                foreach ($list->find('tr') as $row) {
                    $a = $row->find('a', 0);
                    if (!isset($a)) continue;
                    $results[] = array('word' => $a->plaintext, 'link' => $a->href);
                }
            }
            $prev = $result_dom->find('td.seekPrev', 0);
            if (isset($prev)) $results['prevlink'] = $prev->find('a', 0)->href;
            $next = $result_dom->find('td.seekNext', 0);
            if (isset($next)) $results['nextlink'] = $next->find('a', 0)->href;
        } elseif (strcmp('song', $l_m) == 0) {
            foreach ($result_dom->find('table.list') as $list) {
                if (count($list->find('tr th')) != 5) continue;
                foreach ($list->find('tr') as $row) {
                    $td0 = $row->find('td', 0);
                    if ($td0 === null) continue;
                    $a0 = $td0->find('a', 0);
                    if (!isset($a0)) continue;
                    $songlink  = $a0->href;
                    $songtitle = $a0->plaintext;
                    $td1 = $row->find('td', 1);
                    $a1  = $td1->find('a', 0);
                    if (!isset($a1)) {
                        $results[] = array('songtitle' => $songtitle, 'songlink' => $songlink, 'artist' => null, 'artistlink' => null, 'titlelink' => null, 'title' => null, 'oped' => null);
                        continue;
                    }
                    $artistlink = $a1->href;
                    $artist     = $a1->plaintext;
                    $td3 = $row->find('td', 3);
                    $a3  = $td3->find('a', 0);
                    $titlelink  = is_null($a3) ? null : $a3->href;
                    $title      = is_null($a3) ? $td3->plaintext : $a3->plaintext;
                    $kind       = $row->find('td', 4)->plaintext;
                    $results[]  = array('songtitle' => $songtitle, 'songlink' => $songlink, 'artist' => $artist, 'artistlink' => $artistlink, 'titlelink' => $titlelink, 'title' => $title, 'oped' => $kind);
                }
            }
            $prev = $result_dom->find('td.seekPrev', 0);
            if (isset($prev)) $results['prevlink'] = $prev->find('a', 0)->href;
            $next = $result_dom->find('td.seekNext', 0);
            if (isset($next)) $results['nextlink'] = $next->find('a', 0)->href;
        }
        if (count($results) > 0) break;
        usleep(300000);
    }
    return $results;
}

mypage_action_script();
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>anison.info検索</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('searchreserve.php'); ?>
<?php showuppermenu('', $linkoption); ?>

<div class="container py-3">

<div class="search-hero mb-4">
  <form action="search_anisoninfo_list.php" method="GET">
    <?php if (!empty($selectid)): ?>
      <input type="hidden" name="selectid" value="<?php echo htmlspecialchars($selectid, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-3 mb-2">
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
        <input type="radio" name="m" value="song" <?php echo (!isset($l_m) || $l_m === 'song' ? 'checked' : ''); ?>> 曲（よみがなの一部でOK）
      </label>
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
        <input type="radio" name="m" value="pro" <?php echo ($l_m === 'pro' ? 'checked' : ''); ?>> 作品
      </label>
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
        <input type="radio" name="m" value="person" <?php echo ($l_m === 'person' ? 'checked' : ''); ?>> 人物
      </label>
      <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
        <input type="radio" name="m" value="mkr" <?php echo ($l_m === 'mkr' ? 'checked' : ''); ?>> 制作（ブランド）
      </label>
    </div>
    <div class="search-input-wrap">
      <input type="text" name="q" class="form-control-themed"
             value="<?php echo htmlspecialchars($l_q ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             placeholder="検索キーワードを入力" autocomplete="off">
      <button type="submit" class="btn-search-submit">検索</button>
    </div>
  </form>
</div>

<?php if (!empty($l_q)): ?>
  <?php echo mypage_save_keyword_link($l_q, 'anisoninfo'); ?>
<?php endif; ?>

<?php
if (!isset($l_fullparam) && (!isset($l_m) || !isset($l_q))) {
    echo '<div class="notice-box">検索ワードと検索種類が指定されていません</div>';
} else {
    $list = ansoninfo_gettitlelist_list_bs5(
        ansoninfo_gettitlelisturl($l_m, $l_q, $l_fullparam, $year, $genre),
        $l_m
    );
    anisoninfo_display_middlelist($list, $l_m, $l_q, $l_order, $selectid, $year, $genre);
}
?>

</div>
</body>
</html>
