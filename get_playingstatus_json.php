<?php
  setlocale(LC_ALL,  'ja_JP.UTF-8');
  require_once 'func_playerprogress.php';
  require_once 'commonfunc.php';

  $playstat = new PlayerProgress;
  $result = $playstat->getplaystatus_json();

  if ($result === false) {
      echo $result;
      die();
  }

  $ret = json_decode($result, true);

  /* 次の曲情報を追加 */
  $ret['nextsong'] = null;
  try {
      $sql_next = "SELECT id, songfile, song_name, singer, secret, kind, fullpath FROM requesttable WHERE nowplaying = '未再生' ORDER BY reqorder ASC LIMIT 1";
      $sel_next = $db->query($sql_next);
      if ($sel_next) {
          $row_next = $sel_next->fetch(PDO::FETCH_ASSOC);
          $sel_next->closeCursor();
          if ($row_next) {
              $is_secret_next = (int)$row_next['secret'] === 1;
              $sn_raw = $row_next['song_name'] ?? '';

              /* 未保存なら表示時に ListerDB を参照し、見つかれば requesttable も更新 */
              if (empty($sn_raw)
                  && !$is_secret_next
                  && !empty($row_next['fullpath'])
                  && array_key_exists('listerDBPATH', $config_ini)) {
                  $lister_dbpath = urldecode($config_ini['listerDBPATH']);
                  if (file_exists($lister_dbpath)) {
                      require_once 'function_search_listerdb.php';
                      $info = listerdb_lookup_songinfo($row_next['fullpath'], $lister_dbpath);
                      if ($info && !empty($info['song_name'])) {
                          $sn_raw = $info['song_name'];
                          $rid = (int)$row_next['id'];
                          $db->exec(
                              'UPDATE requesttable SET '
                              . 'song_name='      . $db->quote($info['song_name'])      . ','
                              . 'lister_artist='  . $db->quote($info['lister_artist'])  . ','
                              . 'lister_work='    . $db->quote($info['lister_work'])    . ','
                              . 'lister_op_ed='   . $db->quote($info['lister_op_ed'])   . ','
                              . 'lister_comment=' . $db->quote($info['lister_comment'])
                              . ' WHERE id=' . $rid
                          );
                      }
                  }
              }

              $sf = $row_next['songfile'];
              $sn = $sn_raw !== '' ? $sn_raw : '';
              $secret_text = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレット予約)'));
              $ret['nextsong'] = [
                  'title'    => $is_secret_next ? $secret_text : ($sn ?: $sf),
                  'songfile' => $is_secret_next ? '' : $sf,
                  'show_file'=> !$is_secret_next && $sn !== '' && $sn !== $sf,
                  'singer'   => $is_secret_next ? '' : $row_next['singer'],
                  'kind'     => $row_next['kind'],
              ];
          }
      }
  } catch (Exception $e) { /* silent */ }

  echo json_encode($ret);
?>