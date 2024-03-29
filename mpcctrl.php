
<?php
      require_once 'mpcctrl_func.php';
  if( !empty($_REQUEST['songnext']) ){
      songnext();
      die();
  }

  if(  array_key_exists('songstart',$_REQUEST) ){
      songstart();
      die();
  }  
  
  if(  array_key_exists('fadeout',$_REQUEST) ){
      $v=volume_fadeout();
      die();
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
          $r = command_mpc($l_cmd);
          print $r;
      }
      die();
  }
  if(array_key_exists("key", $_REQUEST)) {
      $l_keycmd = $_REQUEST["key"];
      keychange($l_keycmd);
      die();
  }

?>
<script type="text/javascript" src="mpcctrl.js"></script>

<p align="center" style="margin-bottom: 0;"> 動画再生用(Media Player Classic) </p>
<div class="container">
    <div align="center" class="playercontrol" id="playercontrol">
        <div class="row" id="playerprogress" >
        <div id="proglessbase" class="bg-info" >
        <?php
          require_once 'func_playerprogress.php';
          $playstat = new PlayerProgress;
          if($playstat->getstatus()){
              $playstat->show_progress_text();
          }
          
          
        ?>
        </div>
        </div>
        <div class="row">
            <div class="col-xs-6">
            <button type="submit" value="曲の最初から" class="pcbuttom btn btn-default" onClick="song_startfirst()" >曲の最初から</button>
            </div >
            <div class="col-xs-6">
            <button type="submit" value="一時停止"  class="pcbuttom btn btn-default" onClick="song_pause()" >一時停止／再開</button>
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
            <div class="col-xs-4">
            <button type="submit" value="ボリュームDOWN" class=" pcvolume btn btn-default" onClick="song_vdown()" >ボリュームDOWN</button>
            </div >
            <div class="col-xs-4">
            <button type="submit" value="フェードアウト" class=" pcvolume btn btn-default" onClick="exec_fadeout()" >フェードアウト</button>
            </div >
            <div class="col-xs-4">
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
print '</div>';

/* 設定にてキーチェンジ機能が有効になっているかどうか */
$usekeychange=false;
if(array_key_exists("usekeychange",$config_ini)){
    if($config_ini["usekeychange"]==1 ){
       $usekeychange=true;
    }
}
if($usekeychange == true){
print '<div class="row" id="currentkey">';
print '</div >';
print '<div class="row">';
print '<div class="col-xs-4">';
print '<button type="submit" value="キーダウン" class=" pcmorefunc btn btn-default" onClick="keychange(\'down\')" >キーダウン</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="原曲キー" class=" pcmorefunc btn btn-default" onClick="keychange(0)" >原曲キー</button>';
print '</div >';
print '<div class="col-xs-4">';
print '<button type="submit" value="キーアップ" class=" pcmorefunc btn btn-default" onClick="keychange(\'up\')" >キーアップ</button>';
print '</div >';
print '</div >';
}
?>
        </div>
    </div>
            
<!-- ここからアコーディオン（Collapse） -->
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
          <h4  align="center" class="panel-title">
              
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
              拡張機能
            </a>
          </h4>
        </div>
        <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
          <div class="panel-body">
            <div class="row">
                <div class="col-xs-4 text-right"> ←映像を遅く←
                </div >
                <div class="col-xs-4 text-center"> 音ズレ修正
                </div >
                <div class="col-xs-4 text-left"> →映像を早く→
                </div >
            </div>
            <div class="row">
                <div class="col-xs-3">
                <button type="submit" value="(-100ms)" class=" pcdelay btn btn-default" onClick="song_audiodelay_m100()" >(-100ms)</button>
                </div >
                <div class="col-xs-3">
                <button type="submit" value="(-10ms)" class=" pcdelay btn btn-default" onClick="song_audiodelay_m10()" >(-10ms)</button>
                </div >
                <div class="col-xs-3">
                <button type="submit" value="(+10ms)" class=" pcdelay btn btn-default" onClick="song_audiodelay_p10()" >(+10ms)</button>
                </div >
                <div class="col-xs-3">
                <button type="submit" value="(+100ms)" class=" pcdelay btn btn-default" onClick="song_audiodelay_p100()" >(+100ms)</button>
                </div >
            </div>
            <div class="row">
                <div class="col-xs-4">
                <button type="submit" value="スピードダウン" class=" pcdelay btn btn-default" onClick="mpccmd_num('894')" >スピードダウン</button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="標準スピード" class=" pcdelay btn btn-default" onClick="mpccmd_num('896')" >標準スピード</button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="スピードアップ" class=" pcdelay btn btn-default" onClick="mpccmd_num('895')" >スピードアップ</button>
                </div >
            </div>
            <div class="row">
                <div class="col-xs-4">
                <button type="submit" value="画面サイズ縮小" class=" pcdelay btn btn-default" onClick="mpccmd_num('863')" >画面サイズ縮小</button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="画面サイズ標準" class=" pcdelay btn btn-default" onClick="mpccmd_num('861')" >画面サイズ標準</button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="画面サイズ拡大" class=" pcdelay btn btn-default" onClick="mpccmd_num('862')" >画面サイズ拡大</button>
                </div >
            </div>
            <div class="row">
                <div class="col-xs-4">
                <button type="submit" value="D3Dフルスクリーン" class=" pcdelay btn btn-default" onClick="mpccmd_num('1023')" >D3Dフルスクリーン <small>再生が重い場合</small></button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="ミュートOn/Off" class=" pcdelay btn btn-default" onClick="mpccmd_num('909')" >ミュートOn/Off</button>
                </div >
                <div class="col-xs-4">
                <button type="submit" value="左右反転" class=" pcdelay btn btn-default" onClick="mpccmd_num(880)" >左右反転</button>
                </div >
            </div>
            <div class="row">
                <div class="col-xs-2">
                <input type="text" id="MPCCODE">
                </div >
                <div class="col-xs-2">
                <button type="submit" value="任意コード送出" class=" pcdelay btn btn-default" onClick="mpccmd_num()" >任意コード送出</button>
                </div >
            </div>
            <div class="row">
                <div class="col-xs-6">
                <form method="post"action="playerctrl_portal.php" style="display: inline" >
                    <input type="submit" name="songstart" value="再生開始" class="playstart btn btn-default"  /> 
                </form>
                </div >
                <div class="col-xs-6">
                    <form method="post"action="playerctrl_portal.php" style="display: inline" >
                        <input type="submit" value="曲終了" name="songnext" class=" pcbuttom btn btn-default" onClick="song_next()" />
                    </form>
                </div >
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

