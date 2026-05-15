<?php
function headerlistcheck($oneheaderlist,$headerlist){
   $headercount = 0;
   foreach($oneheaderlist as $oneheader ){
       foreach($headerlist as $header ){
           if($oneheader == $header ) $headercount ++;
       }
   }
   return $headercount;
}

function headerlistcheck_column($oneheaderlist,$headerlist,$key){
   $headercount = 0;
   foreach($oneheaderlist as $oneheader ){
       foreach($headerlist as $header ){
           if($oneheader == $header[$key] ) $headercount ++;
       }
   }
   return $headercount;
}


function showuppermenu($target, $linkoption) {
    $items = [
        ['key' => 'filename',         'label' => '詳細検索（キーワード）', 'href' => 'search_listerdb_anysearch_index.php?' . $linkoption],
        ['key' => 'program_name',     'label' => '作品名',               'href' => 'search_listerdb_program_index.php?' . $linkoption],
        ['key' => 'song_artist',      'label' => '歌手名',               'href' => 'search_listerdb_column_index.php?target=song_artist&' . $linkoption],
        ['key' => 'song_name',        'label' => '曲名',                 'href' => 'search_listerdb_column_index.php?target=song_name&' . $linkoption],
        ['key' => 'tie_up_group_name','label' => 'シリーズ',             'href' => 'search_listerdb_column_index.php?target=tie_up_group_name&' . $linkoption],
        ['key' => 'maker_name',       'label' => '制作会社',             'href' => 'search_listerdb_column_index.php?target=maker_name&' . $linkoption],
    ];
    print '<div class="search-mode-nav-wrap">';
    print '<div class="container">';
    print '<div class="search-mode-nav">';
    foreach ($items as $item) {
        $active = ($item['key'] === $target) ? ' active' : '';
        print '<a href="' . htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') . '"'
            . ' class="search-mode-btn' . $active . '">'
            . htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
            . '</a>';
    }
    print '</div>';
    print '</div>';
    print '</div>';
}

function buildgetquery($queries){
   $result = "";
   foreach($queries as $key => $value ){
       if(strlen($result) > 0 ) {
          $result .= '&';
       }
       $result .= $key.'='.urlencode($value);
   }
   return $result;
}


function mb_strtr() {
    $args = func_get_args();
    if (!is_array($args[1])) {
        list($str, $from, $to) = $args;
        $encoding = isset($args[3]) ? $args[3] : mb_internal_encoding(); 
        $replace_pairs = array();
        $len = mb_strlen($from, $encoding);
        for ($i =0; $i < $len; $i++) {
            $k = mb_substr($from, $i, 1, $encoding);
            $v = mb_substr($to, $i, 1, $encoding);
            $replace_pairs[$k] = $v;
        }
        return $replace_pairs ? mb_strtr($str, $replace_pairs, $encoding) : $str;
    }
    list($str, $replace_pairs) = $args;
    $tmp = mb_regex_encoding();
    mb_regex_encoding(isset($args[2]) ? $args[2] : mb_internal_encoding());
    uksort($replace_pairs, function ($a, $b) {
        return strlen($b) - strlen($a);
    });
    $from = $to = array();
    foreach ($replace_pairs as $f => $t) {
        if ($f !== '') {
            $from[] = '(' . mb_ereg_replace('[.\\\\+*?\\[^$(){}|]', '\\\\0', $f) . ')';
            $to[] = $t;
        }
    }
    $pattern = implode('|', $from);
    $ret = mb_ereg_replace_callback($pattern, function ($from) use ($to) {
        foreach ($to as $i => $t) {
            if ($from[$i + 1] !== '') {
                return $t;
            }
        }
    }, $str);
    mb_regex_encoding($tmp);
    return $ret;
}


// 濁点外し＆小文字大文字化
function kanabuild ($str) {
   $from = 'ガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポァィゥェォャュョッ';
   $to   = 'カキクケコサシスセソタチツテトハヒフヘホハヒフヘホアイウエオヤユヨツ';
   
   //ひらがなをカタカナに
   $temp = mb_convert_kana($str,"C");
   //濁点、小文字をカタカナに
   $temp = mb_strtr($temp,$from,$to);
   return $temp;
}



function build_breadcrumbs_bs5($crumbs) {
    if (count($crumbs) <= 1) return;
    echo '<nav aria-label="breadcrumb" class="mb-2">';
    echo '<ol class="breadcrumb-themed">';
    $last = count($crumbs) - 1;
    foreach ($crumbs as $i => $c) {
        $label = htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8');
        if ($i === $last) {
            echo '<li class="breadcrumb-themed-item active" aria-current="page">' . $label . '</li>';
        } else {
            $url = htmlspecialchars(isset($c['url']) ? $c['url'] : '#', ENT_QUOTES, 'UTF-8');
            echo '<li class="breadcrumb-themed-item"><a href="' . $url . '">' . $label . '</a></li>';
        }
    }
    echo '</ol>';
    echo '</nav>';
}

function build_pagination_bs5($displayfrom, $displaynum, $total, $myrequestarray, $page_url) {
    $total_pages = (int)ceil($total / $displaynum);
    if ($total_pages <= 1) return;

    $current_page = (int)floor($displayfrom / $displaynum);
    $window = 2;

    echo '<nav class="pagination-themed" aria-label="ページ">';
    echo '<div class="d-flex flex-wrap justify-content-center align-items-center gap-1">';

    if ($current_page > 0) {
        $req = $myrequestarray; $req['start'] = ($current_page - 1) * $displaynum; $req['length'] = $displaynum;
        echo '<a href="' . htmlspecialchars($page_url . '?' . buildgetquery($req), ENT_QUOTES, 'UTF-8') . '" class="page-btn" aria-label="前のページ">‹</a>';
    } else {
        echo '<span class="page-btn disabled">‹</span>';
    }

    $prev_shown = -1;
    for ($p = 0; $p < $total_pages; $p++) {
        if (!($p === 0 || $p === $total_pages - 1 || abs($p - $current_page) <= $window)) continue;
        if ($p > $prev_shown + 1) echo '<span class="page-btn ellipsis" aria-hidden="true">…</span>';
        $req = $myrequestarray; $req['start'] = $p * $displaynum; $req['length'] = $displaynum;
        $cls = 'page-btn' . ($p === $current_page ? ' active' : '');
        $aria = ($p === $current_page) ? ' aria-current="page"' : '';
        echo '<a href="' . htmlspecialchars($page_url . '?' . buildgetquery($req), ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '"' . $aria . '>' . ($p + 1) . '</a>';
        $prev_shown = $p;
    }

    if ($current_page < $total_pages - 1) {
        $req = $myrequestarray; $req['start'] = ($current_page + 1) * $displaynum; $req['length'] = $displaynum;
        echo '<a href="' . htmlspecialchars($page_url . '?' . buildgetquery($req), ENT_QUOTES, 'UTF-8') . '" class="page-btn" aria-label="次のページ">›</a>';
    } else {
        echo '<span class="page-btn disabled">›</span>';
    }

    echo '</div>';
    echo '</nav>';
}

?>