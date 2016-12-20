<?php

$songlist[] = array( 'kind' => 'movie', 'title' => '君の心は輝いているかい？' , 'filename'=>'[Aqours]君のこころは輝いてるかい？_ラブライブ！サンシャイン！！_Live映像_Live-OffVocal-CD音源切替.mp4', 'desc' => '歌 : Aqours, ラブライブ！サンシャイン！！1stシングル' );
$songlist[] = array( 'kind' => 'movie', 'title' => 'Step Zero to One' ,'filename'=>'[Aqours]Step！ ZERO to ONE_ラブライブ！サンシャイン！！_Live映像_CD音源切替.mp4', 'desc' => '歌 : Aqours, 君の心は輝いているかい？カップリング' );
$songlist[] = array( 'kind' => 'movie', 'title' => 'Aqours☆HEROES' ,'filename'=>'（仮作成）[Aqours]Aqours☆HEROES_ラブライブ！サンシャイン！！_Live映像_Live-CD音源切替.mp4', 'desc' => '歌 : Aqours, 君の心は輝いているかい？カップリング' );
$songlist[] = array( 'kind' => 'movie', 'title' => '恋になりたいAQUARIUM' ,'filename'=>'【ニコカラHD】恋になりたいAQUARIUM【ラブライブ!サンシャイン!!】Off Vocal.mp4', 'desc' => '歌 : Aqours, ラブライブ！サンシャイン！！2ndシングル' );
$songlist[] = array( 'kind' => 'movie', 'title' => '青空Jumping Heart' , 'filename'=>'[Aqours]青空Jumping Heart_ラブライブ！サンシャイン！！アニメOP_OnOffr3.mp4','desc' => '歌 : Aqours, ラブライブ！サンシャイン！！アニメ第1期オープニング' );
$songlist[] = array( 'kind' => 'haisin', 'title' => 'ハミングフレンド' , 'filename'=>'', 'desc' => '【配信】歌 : Aqours, 青空JumpingHeartカップリング' );

$alllist = array( 'itemname' => 'ラブライブ！サンシャイン！！' , 'songs' => $songlist);


$yukarihost = 'localhost';

if(array_key_exists("yukarihost", $_REQUEST)) {
    $yukarihost = $_REQUEST["yukarihost"];
}


$yukarisearchlink='http://'.$yukarihost.'/search.php?searchword=';
$yukariconfirmlink='http://'.$yukarihost.'/request_confirm.php?shop_karaoke=1';
http://localhost/request_confirm.php?shop_karaoke=1

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ラブライブ！サンシャイン！！曲リスト</title>
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
  <!--[if lt IE 9]>
    <script src="js/html5shiv.min.js"></script>
    <script src="js/respond.min.js"></script>
  <![endif]-->
</head>
 
<body>
<?php
foreach($alllist as  $key => $value){
    if($key === 'itemname' ){
        print '<h1>'.$value.'</h1>';
    }else if($key === 'songs' ){
        foreach($value as $songinfo){
             print '<div class="bg-info">';
             if($songinfo['kind'] == 'movie'){
               print '<h2 class="bg-primary">'.$songinfo['title'].'</h2>';
               $link = $yukarisearchlink.$songinfo['filename'];
               print '<p><a href="'.$link.'" class="btn btn-default" > この曲を予約 </a></p>';
               print '<p>'.$songinfo['desc'].'</p>';
             }else if($songinfo['kind'] == 'haisin' ){
               print '<h2 class="bg-primary">'.$songinfo['title'].'</h2>';
               print '<p><a href="'. $yukariconfirmlink . '&filename='  .  $songinfo['title'] . '" class="btn btn-default"> この曲を配信で予約 </a></p>';
               print '<p>'.$songinfo['desc'].'</p>';
             }
             print '</div>';
        }
    }
}

?>

<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</body>
</html>