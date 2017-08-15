<?php
  setlocale(LC_ALL,  'ja_JP.UTF-8');
  require_once 'func_playerprogress.php';
  $playstat = new PlayerProgress;
  print $playstat->getplaystatus_json();
?>