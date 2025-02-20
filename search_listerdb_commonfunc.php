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


function showuppermenu($target,$linkoption){
print '<div class="container  ">';
print '  <div class="row ">';
print '    <div class="col-xs-3 col-md-3  ">';
print '      <a href="search_listerdb_program_index.php?'.$linkoption.'" class="btn ';
if($target == 'program_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >作品名 </a>';

print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
//print '      <a href="search_listerdb_artist.php?'.$linkoption.'" class="btn ';
print '      <a href="search_listerdb_column_index.php?target=song_artist&'.$linkoption.'" class="btn ';
if($target == 'song_artist' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >歌手名 </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
print '      <a href="search_listerdb_column_index.php?target=maker_name&'.$linkoption.'" class="btn ';
if($target == 'maker_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >制作会社 </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
print '      <a href="search_listerdb_column_index.php?target=song_name&'.$linkoption.'" class="btn ';
if($target == 'song_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >曲名 </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
print '      <a href="search_listerdb_column_index.php?target=tie_up_group_name&'.$linkoption.'" class="btn ';
if($target == 'tie_up_group_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >シリーズ </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3 " >';
print '      <a style="white-space: normal;" href="search_listerdb_anysearch_index.php?'.$linkoption.'" class="btn ';
if($target == 'filename' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >詳細検索</a>';
print '    </div>';
print '  </div>';
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


?>