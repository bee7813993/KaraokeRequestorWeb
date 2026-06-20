<?php
function getiplist() {
  $result_ipconfig = array();
  
//  exec("C:\\Windows\\System32\\calc.exe",$result_ipconfig);
  exec("chcp 437&ipconfig&chcp 932",$result_ipconfig);
//  exec("chcp 932");
  //var_dump($result_ipconfig);
  
  $kind = 0;
  $iplist = array();
  $oneip = array();
  
  foreach($result_ipconfig as $l){
     //print "len:".strlen($l)."\n";
     if(strlen($l) == 0) continue;
     $match_res=stristr($l,"Windows IP Configuration") ;
     //print "match:".$match_res."\n";
     if($match_res !== false ) continue;
     $match_res=stristr($l,"Active code page") ;
     if($match_res !== false ) continue;
     $match_res=stristr($l,"ipconfig.exe") ;
     if($match_res !== false ) continue;
     $match_res=stristr($l,"現在のコード ページ") ;
     if($match_res !== false ) continue;
     //print $l."\n";
     
         
         if( preg_match('/^ +/',$l ) === 1 ){
             $match_res_4=stristr($l,"IPv4 Address");
             $match_res_6=stristr($l,"IPv6 Address");
             if($match_res_4 === false && $match_res_6 === false) continue;
             
             $pos = strrchr($l,' ');
             if($pos !== false){
                 $oneip[]=trim($pos);
             }
         }else{
             if(count($oneip) > 0) {
                 $iplist[] = $oneip;
                 $oneip = array();
             }
             $oneip[]=$l;
         }
  }
  if(count($oneip) > 0) {
      $iplist[] = $oneip;
  }
  // var_dump($iplist);
  return $iplist;
}

/**
 * ipconfig の結果からURLリストを生成する。
 * ループバック・重複・ゾーンIDを除去し IPv4/IPv6 に分類して返す。
 *
 * @param array $config_ini  useeasyauth_word を参照してURLにeasypassを付与する
 * @return array{v4: string[], v6: string[]}
 */
function getiplinks(array $config_ini): array {
    $result = getiplist();
    $v4 = []; $v6 = []; $seen = [];
    foreach ($result as $ifinfo) {
        foreach ($ifinfo as $idx => $ip_str) {
            if ($idx === 0) continue;
            $ip = trim($ip_str);
            if (empty($ip)) continue;
            if (strpos($ip, '%') !== false) $ip = substr($ip, 0, strpos($ip, '%'));
            if ($ip === '127.0.0.1' || $ip === '::1') continue;
            if (in_array($ip, $seen, true)) continue;
            $seen[] = $ip;
            $is_ipv6 = (strpos($ip, ':') !== false);
            $url_ip  = $is_ipv6 ? '[' . $ip . ']' : $ip;
            $link    = 'http://' . $url_ip . '/';
            if (!empty($config_ini['useeasyauth_word'])) {
                $link .= '?easypass=' . urlencode($config_ini['useeasyauth_word']);
            }
            if ($is_ipv6) { $v6[] = $link; } else { $v4[] = $link; }
        }
    }
    return ['v4' => $v4, 'v6' => $v6];
}

/**
 * 自IP一覧をBootstrap 5スタイルで出力する。
 * IPv4は常時表示、IPv6は折りたたみ（初期: 非表示）。
 *
 * @param array  $config_ini   getiplinks() に渡す設定
 * @param string $collapse_id  IPv6折りたたみ要素のid（ページ内重複しないよう指定）
 */
function print_iplist(array $config_ini, string $collapse_id = 'ipv6-list'): void {
    $ips = getiplinks($config_ini);
    $cid = htmlspecialchars($collapse_id, ENT_QUOTES, 'UTF-8');
    echo '<div style="font-family:monospace; font-size:0.875rem; word-break:break-all">' . "\n";
    foreach ($ips['v4'] as $link) {
        $h = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $h . '">' . $h . '</a><br>' . "\n";
    }
    echo '</div>' . "\n";
    if (!empty($ips['v6'])) {
        echo '<div class="mt-2">' . "\n";
        echo '<button class="btn btn-sm btn-outline-secondary" type="button"'
           . ' data-bs-toggle="collapse" data-bs-target="#' . $cid . '">'
           . 'IPv6アドレスを表示 (' . count($ips['v6']) . '件)'
           . '</button>' . "\n";
        echo '<div class="collapse mt-1" id="' . $cid . '">' . "\n";
        echo '<div style="font-family:monospace; font-size:0.875rem; word-break:break-all">' . "\n";
        foreach ($ips['v6'] as $link) {
            $h = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            echo '<a href="' . $h . '">' . $h . '</a><br>' . "\n";
        }
        echo '</div></div></div>' . "\n";
    }
}

?>

