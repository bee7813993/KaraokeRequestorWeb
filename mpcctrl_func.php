<?php

require_once 'commonfunc.php';

$MPCCMDURL='http://localhost:13579/command.html';
$MPCSTATUSURL='http://localhost:13579/status.html';
$MPCVARIABLESURL='http://localhost:13579/variables.html';
$EASYKEYCHANGERURL='http://localhost:13580/command.html';

  function timestring(){
      $now = \DateTime::createFromFormat('U.u', sprintf('%6F', microtime(true)));
      return $now->format('Y-m-d H:i:s.u');
  }

  function clientipue(){
      return $_SERVER["HTTP_USER_AGENT"].$_SERVER["REMOTE_ADDR"];
  }
  
  function mkclienthash($count = 0){
      $hashbaseword = timestring().clientipue().$count;
      // print 'DEBUG:'.$hashbaseword.'<br>';
      $hashword = hash("md5", $hashbaseword, $raw_output = false);
      // print 'DEBUG:'.$hashword.'<br>';
      return substr($hashword,0,8);
  }

function songnext(){
      global  $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small> 曲終了ボタンを押されましたが、再生中の曲はありませんでした </small>'."\n";
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"停止中\" WHERE id = ".$currentsong[0]['id'];
          $db->beginTransaction();
          $ret = $db->exec($sql);
          $db->commit();
      }
      // 曲別音量オフセットが適用されたままなら現在音量から差し戻す
      revert_song_volume_offset();
}

function songstart(){
      global  $db;
      $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生開始待ち' ORDER BY reqorder ASC ";
      $select = $db->query($sql);
      if($select === false) {
          command_mpc(887);
          return;
      }
      $currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
      $select->closeCursor();
      if(count($currentsong) < 1){
          //再生中の曲がないとき
          print '<small>  </small>'."\n";
          command_mpc(887);
      }else {
          $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = ".$currentsong[0]['id'];
          $db->beginTransaction();
          $ret = $db->exec($sql);
          $db->commit();
          sleep(2);
          command_mpc(887);
      }
}

function command_mpc($num){
    
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command='.$num;
    $res = file_get_html_with_retry($requesturl,5,0,4,100);
    switch($num) {
        case 901:
        case 902:
        case 903:
        case 904:
           $requesturl = createUri((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . ':'. $_SERVER["SERVER_PORT"]. $_SERVER["REQUEST_URI"], 'update_playerprogress.php' );
           $res = file_get_html_with_retry($requesturl);
           print $requesturl ;
           print $res;
        break;
    }
    return $res;
}

function delay_plus100_mpc(){
    global $MPCCMDURL;
    
    for($i=0;$i<10;$i++){
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=905';
    $res = file_get_html_with_retry($requesturl);
    usleep(30000);
    }
}

function delay_minus100_mpc(){
    global $MPCCMDURL;
    
    for($i=0;$i<10;$i++){
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=906';
    $res = file_get_html_with_retry($requesturl);
    usleep(50000);
    }
}

function start_first_mpc(){
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=-1&percent=0';
    $res = file_get_html_with_retry($requesturl);

    $requesturl = createUri((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . ':'. $_SERVER["SERVER_PORT"]. $_SERVER["REQUEST_URI"], 'update_playerprogress.php' );
    $res = file_get_html_with_retry($requesturl);

    return $res;
}

function toggle_mute_mpc(){
    return command_mpc(909);;
}

function go_end_mpc(){
    global $MPCCMDURL;
    
    $res = TRUE;
    $requesturl=$MPCCMDURL.'?wm_command=-1&percent=99.5';
    $res = file_get_html_with_retry($requesturl);
    return $res;
}

function set_volume($c_volume){

    global $MPCCMDURL;

    $requesturl=$MPCCMDURL.'?wm_command=-2&volume='.trim($c_volume);
//    print $requesturl;
    file_get_html_with_retry($requesturl);
}

function get_volume(){

    global $MPCVARIABLESURL, $MPCSTATUSURL;

    // status.html はカンマ区切りのため、曲名にカンマを含むと位置がズレて
    // 誤った値 (muted フラグ等) を返す。id 付きで取れる variables.html を優先する
    $vars = file_get_html_with_retry($MPCVARIABLESURL);
    if($vars !== FALSE && preg_match('/<p id="volumelevel">(\d+)<\/p>/', $vars, $m)){
        return (int)$m[1];
    }

    // フォールバック (旧バージョン向け): status.html のカンマ区切りパース
    $statusformat = 'OnStatus(\'%s\', \'%s\', %d, \'%s\', %d, \'%s\', %d, %d, \'%s\')';

    $status = file_get_html_with_retry($MPCSTATUSURL);
    if($status === FALSE) return $status;
    // print $status;
    $status_array = explode(',', $status);
    // var_dump($status_array);
    return $status_array[7];
}

/* ==== 制作者別音量増減（曲別オフセット）の適用状態管理 ====
   オフセットは「適用時点の音量」への相対値として扱い、実際に適用できた
   増減量を状態ファイルへ記録する。曲終了(songnext)や次曲開始時に
   現在音量から差し戻すことで、手元で調整した音量を壊さずに元へ戻す。 */
function _volume_offset_state_file(){
    return __DIR__ . '/volume_offset_state.json';
}

function get_applied_volume_offset(){
    $f = _volume_offset_state_file();
    if(!file_exists($f)) return 0;
    $d = @json_decode(@file_get_contents($f), true);
    if(!is_array($d) || !isset($d['delta'])) return 0;
    return max(-100, min(100, intval($d['delta'])));
}

function save_applied_volume_offset($delta){
    @file_put_contents(_volume_offset_state_file(), json_encode(['delta' => intval($delta)]));
}

/* 適用済みオフセットを現在音量から差し戻す。
   MPC に到達できないときは状態を保持し、次の機会に差し戻す。 */
function revert_song_volume_offset(){
    $delta = get_applied_volume_offset();
    if($delta === 0) return;
    $cur = get_volume();
    if($cur === FALSE) return;
    set_volume(max(0, min(100, intval($cur) - $delta)));
    save_applied_volume_offset(0);
}

/* 再生開始時の音量制御。
   - startvolume50 有効時（キー未設定時も有効扱い = 設定画面の既定表示と一致）は
     基準音量（startvolume。未設定・数値以外は 50）へ戻す。
   - requesttable.volume（制作者別音量増減）は「その時点の音量」への相対オフセット
     として適用し、適用量を記録する。startvolume50 無効時でも機能する。 */
function apply_start_volume($row){
    global $config_ini;

    $sv50_on = true;
    if(array_key_exists('startvolume50', $config_ini) && intval($config_ini['startvolume50']) !== 1){
        $sv50_on = false;
    }

    $offset = 0;
    if(is_array($row) && array_key_exists('volume', $row) && $row['volume'] !== null && $row['volume'] !== ''){
        $v = intval($row['volume']);
        if($v >= -100 && $v <= 100) $offset = $v;
    }

    $cur = null;
    if($sv50_on){
        $cur = 50;
        if(array_key_exists('startvolume', $config_ini) && is_numeric(trim(urldecode((string)$config_ini['startvolume'])))){
            $cur = max(0, min(100, intval(urldecode((string)$config_ini['startvolume']))));
        }
        // 基準音量へ強制リセットするため、前曲の未返済オフセットはここで消滅する
        save_applied_volume_offset(0);
    }else{
        // 前曲のオフセットが残っていれば先に現在音量から差し戻す
        revert_song_volume_offset();
        if($offset !== 0){
            $v = get_volume();
            if($v !== FALSE) $cur = max(0, min(100, intval($v)));
        }
    }

    // sv50 無効かつオフセット無し（または MPC 不達）なら音量に触らない
    if($cur === null) return;

    $applied = max(0, min(100, $cur + $offset));
    set_volume($applied);
    save_applied_volume_offset($applied - $cur);
}

function volume_fadeout(){
    global $MPCCMDURL;

    $volume = get_volume();
    // print $volume;
    $delta_volume=round($volume/10);
    if($delta_volume < 4 ) $delta_volume = 4;
    for($c_volume = $volume ; $c_volume > 0 ; $c_volume -= $delta_volume){
    // print $c_volume;
        set_volume($c_volume);
        usleep(100000);
    }
    return $volume;
}

function keychange($keycmd){
    global $EASYKEYCHANGERURL;

    $clienttoken=mkclienthash();

    $requesturl=$EASYKEYCHANGERURL.'?key='.$keycmd.'&token='.$clienttoken;
    $res = file_get_html_with_retry($requesturl,5,1);
    /* キー送信に失敗した場合は現在キーの取得も失敗するだけなのでスキップ
       (呼び出し元は $res === false で不達を判定できる) */
    if($res !== false){
        update_requestdb_key();
    }
    return $res;
}



function update_requestdb_key(){
    global $db;

    // 自己 HTTP 呼び出し (getcurrentkey.php) はアクセス元 URL 依存で /api/ 配下から
    // 呼ぶと解決に失敗するため、EasyKeyChanger へ直接現在キーを問い合わせる
    // (キー送信成功直後にしか呼ばれず到達性は確認済みのためリトライは2回で十分)
    require_once 'func_keychange.php';
    $kc = new EasyKeychanger();
    $status = $kc->getstatus(2);

    if( $status === false ) return;
    if( !isset($status["currentkey"]) || !is_numeric($status["currentkey"]) ) return;

    $sql = 'UPDATE requesttable SET keychange='.(int)$status["currentkey"].' WHERE nowplaying="再生中";';
    $db->beginTransaction();
    $ret = $db->exec($sql);
    $db->commit();

}
?>