<?php
  setlocale(LC_ALL,  'ja_JP.UTF-8');
  require_once 'commonfunc.php';
  require_once 'function_playingstatus.php';

  $ret = build_playingstatus_data($db, $config_ini);

  /* 互換仕様: プレイヤー停止中は空応答を返す (クライアントは「空 = 停止中」として扱う) */
  if ($ret === null) {
      die();
  }

  echo json_encode($ret);
?>
