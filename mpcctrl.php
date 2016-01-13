
<?php
      require_once 'mpcctrl_func.php';
  if( !empty($_REQUEST['songnext']) ){
      songnext();
  }
  
  if(array_key_exists("cmd", $_REQUEST)) {
      $l_cmd = $_REQUEST["cmd"];
      
      if($l_cmd == "delayp100"){
        delay_plus100_mpc();
      }else if($l_cmd == "delaym100"){
        delay_minus100_mpc();
      }else if($l_cmd == "start_first"){
        start_first_mpc();
      }else {
          command_mpc($l_cmd);
      }
  }

?>
<script type="text/javascript" src="mpcctrl.js"></script>

<p align="center" style="margin-bottom: 0;"> 動画再生用(Media Player Classic) </p>
<div align="center" class="playercontrol" id="playercontrol">
<div class="row">
<div class="col-xs-12">
<input type="submit" value="再生開始" class="playstart btn btn-default" onClick="song_play()" />
</div >
</div>
<div class="row">
<div class="col-xs-4">
<button type="submit" value="曲の最初から" class="pcbuttom btn btn-default" onClick="song_startfirst()" >曲の最初から</button>
</div >
<div class="col-xs-4">
<button type="submit" value="一時停止"  class="pcbuttom btn btn-default" onClick="song_pause()" >一時停止</button>
</div >
<div class="col-xs-4">
<form method="post"action="playerctrl_portal.php" style="display: inline" >
<input type="submit" value="曲終了" name="songnext" class=" pcbuttom btn btn-default" onClick="song_next()" />
</form>
</div >
</div>
<div class="row">
<div class="col-xs-3">
<button type="submit" value="大きく手前にジャンプ" class=" pcdelay btn btn-default" onClick="jump_before_large()" title="少し巻き戻し" > ＜＜＜ </button>
</div >
<div class="col-xs-3">
<button type="submit" value="手前にジャンプ" class=" pcdelay btn btn-default" onClick="jump_before()" title="少し巻き戻し" > ＜＜ </button>
</div >
<div class="col-xs-3">
<button type="submit" value="後ろにジャンプ" class=" pcdelay btn btn-default" onClick="jump_later()" title="少し早送り" > ＞＞ </button>
</div >
<div class="col-xs-3">
<button type="submit" value="大きく後ろにジャンプ" class=" pcdelay btn btn-default" onClick="jump_later_large()" title="少し早送り" > ＞＞＞ </button>
</div >
</div>
<div class="row">
<div class="col-xs-6">
<button type="submit" value="ボリュームDOWN" class=" pcvolume btn btn-default" onClick="song_vdown()" >ボリュームDOWN</button>
</div >
<div class="col-xs-6">
<button type="submit" value="ボリュームUP" class="pcvolume btn btn-default" onClick="song_vup()" >ボリュームUP</button>
</div >
</div>
<div class="row">
<?php
if($moviefullscreen == 1){
print '<div class="col-xs-4">';
print '<button type="submit" value="字幕ONOFF(ソフトサブのみ)" class=" pcmorefunc btn btn-default" onClick="song_subtitleonnoff()" >字幕ONOFF(ソフトサブのみ)</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="音声トラック変更" class=" pcmorefunc btn btn-default" onClick="song_changeaudio()" >音声トラック変更</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="フルスクリーンON/OFF" class=" pcmorefunc btn btn-default" onClick="song_fullscreen()" >フルスクリーンON/OFF</button>';
print '</div >';
}else{
print '<div class="col-xs-6">';
print '<button type="submit" value="字幕ONOFF(ソフトサブのみ)" class=" pcmorefunc btn btn-default" onClick="song_subtitleonnoff()" >字幕ONOFF(ソフトサブのみ)</button>';
print '</div >';
print '<div class="col-xs-6">';
print '<button type="submit" value="音声トラック変更" class=" pcmorefunc btn btn-default" onClick="song_changeaudio()" >音声トラック変更</button>';
print '</div >';
}
?>
</div>
<!-----
<div class="row">
<div class="col-xs-3">
<button type="submit" value="(-100ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_m100()" >(-100ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(-10ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_m10()" >(-10ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(+10ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_p10()" >(+10ms)<br>音ズレ修正</button>
</div >
<div class="col-xs-3">
<button type="submit" value="(+100ms)音ズレ修正" class=" pcdelay btn btn-default" onClick="song_audiodelay_p100()" >(+100ms)<br>音ズレ修正</button>
</div >
<br>

</div>
---->

</div>

