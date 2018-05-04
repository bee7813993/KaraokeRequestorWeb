<?php

require_once 'commonfunc.php';

?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header();?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">


    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<title>曲情報入力画面</title>
<link type="text/css" rel="stylesheet" href="css/style.css" />
<!-- <script type='text/javascript' src='jwplayer/jwplayer.js'></script> -->
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>
<script src="js/bootstrap.min.js"></script>
<script type="text/javascript" charset="utf8" src="js/requsetlist_ctrl.js"></script>
</head>
<body>
<?php
shownavigatioinbar();
?>

<form>
  <!-- 曲名 -->
  <div class="form-group">
    <label>曲名</label>
    <input type="text" class="form-control" placeholder="曲名"
      value="未来の僕らは知ってるよ" 
    />
  </div>
  <!-- 曲名よみ -->
  <div class="form-group">
    <label>曲名よみ</label>
    <input type="text" class="form-control" placeholder="曲名よみ"
      value="みらいのぼくらはしってるよ" 
    />
  </div>
<hr />
  <div class="form-group">
    <label>作品名</label>
    <input type="text" class="form-control" placeholder="作品名" 
      value="ラブライブ！サンシャイン！" 
    />
  </div>
  <div class="form-group">
    <label>作品名よみ</label>
    <input type="text" class="form-control" placeholder="作品名よみ"
      value="らぶらいぶさんしゃいん" 
    />
  </div>
<hr />
  <div class="form-group">
    <label>作品名補足</label>
    <input type="text" class="form-control" placeholder="作品名" 
      value="アニメ体2期OP" 
    />
  </div>

  <div class="form-group">
    <label>作詞</label>
    <input type="text" class="form-control" placeholder="作詞"
      value="畑亜貴" 
    />
  </div>
  <div class="form-group">
    <label>作詞よみ</label>
    <input type="text" class="form-control" placeholder="作詞よみ"
      value="はたあき" 
    />
  </div>
<hr />

  <div class="form-group">
    <label>作曲</label>
    <input type="text" class="form-control" placeholder="作曲"
      value="光増ハジメ" 
    />
  </div>
  <div class="form-group">
    <label>作曲よみ</label>
    <input type="text" class="form-control" placeholder="作曲よみ"
      value="みつますはじめ" 
    />
  </div>
<hr />

  <div class="form-group">
    <label>編曲</label>
    <input type="text" class="form-control" placeholder="編曲"
      value="EFFY" 
    />
  </div>
  <div class="form-group">
    <label>編曲よみ</label>
    <input type="text" class="form-control" placeholder="編曲よみ"
      value="えふぃー" 
    />
  </div>
<hr />


  <div class="form-group">
    <label>歌手名</label>
    <table class="table">
     <thead>
      <tr>
       <th scope="col">歌手名</th>
       <th scope="col">歌手名よみ</th>
      </tr>
     </thead>
     <tbody>
      <tr>
       <td>Aqours</td>
       <td>あくあ</td>
      </tr>
      <tr>
        <td>高海千歌</td>
        <td>たかみちか</td>
      </tr>
      <tr>
        <td>伊波杏樹</td>
        <td>いなみあんじゅ</td>
      </tr>
      <tr>
        <td>桜内梨子</td>
        <td>さくらうちりこ</td>
      </tr>
      <tr>
        <td>逢田梨香子</td>
        <td>あいだりかこ</td>
      </tr>
      <tr>
        <td>松浦果南</td>
        <td>まつうらかなん</td>
      </tr>
      <tr>
        <td>諏訪ななか</td>
        <td>すわななか</td>
      </tr>
      <tr>
        <td>黒澤ダイヤ</td>
        <td>くろさわだいや</td>
      </tr>
      <tr>
        <td>小宮有紗</td>
        <td>こみやありさ</td>
      </tr>
      <tr>
        <td>渡辺曜</td>
        <td>わたなべよう</td>
      </tr>
      <tr>
        <td>斉藤朱夏</td>
        <td>さいとうしゅか</td>
      </tr>
      <tr>
        <td>津島善子</td>
        <td>つしまよしこ</td>
      </tr>
      <tr>
        <td>小林愛香</td>
        <td>こばやしあいか</td>
      </tr>
      <tr>
        <td>国木田花丸</td>
        <td>くにきだはなまる</td>
      </tr>
      <tr>
        <td>高槻かなこ</td>
        <td>たかつきかなこ</td>
      </tr>
      <tr>
        <td>小原鞠莉</td>
        <td>おはらまり</td>
      </tr>
      <tr>
        <td>鈴木愛奈</td>
        <td>すずきあいな</td>
      </tr>
      <tr>
        <td>黒澤ルビィ</td>
        <td>くろさわるびぃ</td>
      </tr>
      <tr>
        <td>降幡愛</td>
        <td>ふりはたあい</td>
      </tr>
     </tbody>
    </table>
    <a href="#" class="btn btn-default" role="button">歌手名入力画面へ</a>
  </div>
<hr />

  <div class="form-group">
    <label>制作</label>
    <input type="text" class="form-control" placeholder="制作"
      value="サンライズ" 
    />
  </div>
  <div class="form-group">
    <label>制作よみ</label>
    <input type="text" class="form-control" placeholder="制作よみ"
      value="さんらいず" 
    />
  </div>
<hr />

  <div class="form-group">
    <label>リリース日</label>
    <input type="date" class="form-control" placeholder="リリース日"
      value="2017-10-25" 
    />
  </div>

  <!-- 送信ボタン -->
  <button type="submit" class="btn btn-default">Submit</button>
</form>

</body>
</html>
