<?php

require_once 'commonfunc.php';

?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<title>コメントポスト</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('comment.php'); ?>

<div class="container py-3">

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="card-title mb-0">現在の動作モード</h5>
    </div>
    <div class="card-body">
      <?php
      if ($playmode == 1) {
          echo '自動再生開始モード: 自動で次の曲の再生を開始します。';
      } elseif ($playmode == 2) {
          echo '手動再生開始モード: 再生開始を押すと、次の曲が始まります。(歌う人が押してね)';
      } elseif ($playmode == 4) {
          echo 'BGMモード: 自動で次の曲の再生を開始します。すべての再生が終わると再生済みの曲をランダムに流します。';
      } elseif ($playmode == 5) {
          echo 'BGMモード(ランダムモード): 順番は関係なくリストの中からランダムで再生します。';
      } else {
          echo '手動プレイリスト登録モード: 機材係が手動でプレイリストに登録しています。';
      }
      ?>
    </div>
  </div>

  <?php if (commentenabledcheck()): ?>
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">こちらから画面にコメントを出せます(ニコ生風に)</h5>
    </div>
    <div class="card-body">
      <form name="forms" action="commentpost.php" class="sendcomment" method="post">

        <div class="mb-3">
          <label class="form-label fw-bold">文字色</label>
          <div class="d-flex flex-wrap gap-1 align-items-center">
            <div style="background-color:white;width:2rem;height:2rem;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="FFFFFF" checked></div>
            <div style="background-color:gray;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="808080"></div>
            <div style="background-color:pink;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="FFC0CB"></div>
            <div style="background-color:red;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="FF0000"></div>
            <div style="background-color:orange;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="FFA500"></div>
            <div style="background-color:yellow;width:2rem;height:2rem;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="FFFF00"></div>
            <div style="background-color:lime;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="00FF00"></div>
            <div style="background-color:aqua;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="00FFFF"></div>
            <div style="background-color:blue;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="0000FF"></div>
            <div style="background-color:purple;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="800080"></div>
            <div style="background-color:black;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;"><input type="radio" name="col" value="111111"></div>
            <div class="ms-2 d-flex align-items-center gap-1">
              その他 <input type="radio" name="col" value="CUSTOM"> <input type="color" name="c_col" value="#808080">
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">文字サイズ</label>
          <div class="d-flex gap-3">
            <label><input type="radio" name="sz" value="0"> 小</label>
            <label><input type="radio" name="sz" value="3" checked> 中</label>
            <label><input type="radio" name="sz" value="6"> 大</label>
            <label><input type="radio" name="sz" value="9"> 特大</label>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">名前</label>
          <input type="text" name="nm" class="form-control" maxlength="32"
                 title="匿名にしたいときは名前を空欄"
                 value="<?php echo htmlspecialchars(returnusername_self(), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">コメント</label>
          <input type="text" name="msg" class="form-control" maxlength="256" tabindex="1">
        </div>

        <button type="submit" name="SUBMIT" class="btn btn-secondary w-100">コメント送信</button>

      </form>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<script>
$(document).ready(function(){
    $(".sendcomment").submit(function(event){
        event.preventDefault();
        var $form = $(this);
        var $button = $form.find('button');
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            timeout: 10000,
            beforeSend: function(){
                $button.attr('disabled', true);
            },
            complete: function(){
                $button.attr('disabled', false);
            },
            error: function(){
                alert('NG...');
            }
        });
    });
});
</script>

</body>
</html>
