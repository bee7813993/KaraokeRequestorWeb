<?php

  require_once 'foobar_func.php';

  if( !empty($_REQUEST['songnext']) ){
      songnext();
  }

  if( array_key_exists('songstart',$_REQUEST) ){
      foobar_songstart();
  }
  if(array_key_exists("cmd", $_REQUEST)) {
      $l_cmd = $_REQUEST["cmd"];
      
      if($l_cmd == "Start"){
        foobar_songstart();
      }else if($l_cmd == "PlayOrPause"){
        foobar_song_pause();
      }else if($l_cmd == "VolumeUP"){
        foobar_song_vup();
      }else if($l_cmd == "VolumeDown"){
        foobar_song_vdown();
      }else if($l_cmd == "Stop"){
        foobar_song_stop();
      }else if($l_cmd == "StartFirst"){
        foobar_song_restart();
      }else {
          // nothing to do
      }
  }
  

?>
<script type="text/javascript" src="foobarctrl.js"></script>
<p align="center" style="margin-bottom: 0;" > 音楽再生用(foobar2000) </p>
<div class="container">
  <div align="center" class="playercontrol" >

    <div class="row">
      <div class="col-xs-6">
        <button type="submit" value="曲の最初から" class="pcbuttom btn btn-default" onClick="song_startfirst()" >曲の最初から</button>
      </div >
      <div class="col-xs-6">
        <button type="submit" value="一時停止"  class="pcbuttom btn btn-default" onClick="song_pause()" >一時停止/再開</button>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-6">
        <button type="submit" value="ボリュームDOWN" class="pcvolume btn btn-default" onClick="song_vdown()" >ボリュームDOWN</button>
      </div >
      <div class="col-xs-6">
        <button type="submit" value="ボリュームUP" class="pcvolume btn btn-default" onClick="song_vup()" >ボリュームUP</button>
      </div >
    </div>
  </div>
</div>

<div class="container"><!-- ここは小見出し -->
<div class="row">

            
<!-- ここからアコーディオン（Collapse） -->
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  <div class="panel panel-default">
    <div align="center" class="panel-heading" role="tab" id="headingOne">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
          拡張機能
        </a>
      </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
      <div class="panel-body">
        <div class="row">
            <div class="col-xs-8">
            <button type="submit" value="再生開始" name="songstart" class="pcbuttom btn btn-default" onClick="song_play()" /> 再生開始 </button>
            </div >
            <div class="col-xs-4">
            <button type="submit" value="曲終了" name="songnext" class="pcbuttom  btn btn-default" onClick="song_next()" />曲終了</button>
            </div >
        </div>
    </div>
  </div>
</div>