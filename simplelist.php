<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>再生曲リスト</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* ===== 共通スタイル ===== */
    body {
      background-color: #f0f0f0;
      padding: 15px;
    }
    .simplelist-container {
      max-width: 1100px;
      margin: 0 auto;
    }
    .page-title {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 15px;
      padding: 8px 12px;
      background: #fff;
      border-left: 4px solid #555;
      border-radius: 3px;
    }
    .simplelist-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }
    .simplelist-table thead th {
      background-color: #444;
      color: #fff;
      padding: 10px 12px;
      font-weight: bold;
      white-space: nowrap;
    }
    .simplelist-table tbody tr:nth-child(odd)  { background-color: #fafafa; }
    .simplelist-table tbody tr:nth-child(even) { background-color: #fff; }
    .simplelist-table tbody tr:hover           { background-color: #eef2ff; }
    .simplelist-table tbody td {
      padding: 9px 12px;
      border-bottom: 1px solid #efefef;
      vertical-align: top;
    }
    .col-no {
      width: 50px;
      text-align: center;
      color: #888;
    }
    .col-keychange {
      font-size: 0.85em;
      color: #666;
    }

    /* PC ではアコーディオンボタンを非表示 */
    .mobile-accordion-btn { display: none; }

    /* ===== スマホスタイル (767px 以下) ===== */
    @media (max-width: 767px) {
      body { padding: 8px; }

      .simplelist-table,
      .simplelist-table tbody { display: block; }
      .simplelist-table thead { display: none; }

      /* 各行をカード表示 */
      .simplelist-table tbody tr {
        display: block;
        background: #fff !important;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-bottom: 10px;
        padding: 10px 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      }

      /* 全セル非表示にしてから個別制御 */
      .simplelist-table tbody td {
        display: none;
        padding: 2px 0;
        border: none;
        vertical-align: top;
      }

      /* 常に表示するセル：番号・曲名・作品名 */
      .simplelist-table tbody td.col-no,
      .simplelist-table tbody td.col-song,
      .simplelist-table tbody td.col-program {
        display: block;
      }

      /* 番号スタイル */
      .simplelist-table tbody td.col-no {
        font-size: 0.72em;
        color: #bbb;
        text-align: left;
        padding-bottom: 1px;
      }

      /* 曲名スタイル */
      .simplelist-table tbody td.col-song {
        font-weight: bold;
        font-size: 1.05em;
        line-height: 1.5;
        padding-bottom: 2px;
      }

      /* 作品名スタイル */
      .simplelist-table tbody td.col-program {
        color: #777;
        font-size: 0.85em;
        padding-bottom: 4px;
        min-height: 0;
      }

      /* アコーディオン展開時に表示するセル */
      .simplelist-table tbody tr.is-expanded td.col-artist,
      .simplelist-table tbody tr.is-expanded td.col-singer,
      .simplelist-table tbody tr.is-expanded td.col-comment {
        display: block;
        font-size: 0.9em;
        padding: 5px 0 5px 4px;
        border-top: 1px solid #f0f0f0;
        color: #333;
      }

      /* 展開セルのラベル */
      .simplelist-table tbody td.col-artist::before  { content: "歌手名：";  color: #aaa; font-size: 0.85em; }
      .simplelist-table tbody td.col-singer::before  { content: "歌った人："; color: #aaa; font-size: 0.85em; }
      .simplelist-table tbody td.col-comment::before { content: "コメント："; color: #aaa; font-size: 0.85em; }

      /* アコーディオンボタン */
      .mobile-accordion-btn {
        display: block;
        width: 100%;
        margin-top: 6px;
        padding: 5px 10px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 6px;
        color: #555;
        font-size: 0.82em;
        cursor: pointer;
        text-align: center;
        -webkit-tap-highlight-color: transparent;
      }
      .mobile-accordion-btn.is-open {
        background: #e8eaf6;
        color: #3949ab;
        border-color: #9fa8da;
      }
    }
    </style>
  </head>
  <body>
<?php
require_once('commonfunc.php');
require_once('function_search_listerdb.php');

$usesimplelist = false;
if(array_key_exists("usesimplelist",$config_ini)){
    if($config_ini["usesimplelist"]==1 ){
        $usesimplelist=true;
    }
}
if( $usesimplelist == false ){
    print "<p>リスト表示機能が無効になっています</p>";
    print "</body></html>";
    die();
}

function getsonginfofromfilename($filename){
  global $config_ini;

  if(empty($filename)) return false;
  $res = array_key_exists("listerDBPATH",$config_ini);
  if ($res === false ) {
     print( "config not found");
     return false;
  }
  $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
  if(!file_exists($lister_dbpath) ){
     print( "Listerdb file :". $lister_dbpath." not found");
     return false;
  }

  $lister = new ListerDB();
  $lister->listerdbfile = $lister_dbpath;
  $listerdb = $lister->initdb();
  if( !$listerdb ) {
       return false;
  }

  $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.$filename.'%');
  $sql = 'SELECT * FROM t_found '. $select_where.';';
  @$songdbdata = $lister->select($sql);
  if(!$songdbdata){
      $select_where = 'WHERE found_path LIKE '. $listerdb ->quote('%'.basename($filename).'%');
      $sql = 'SELECT * FROM t_found '. $select_where.';';
      @$songdbdata = $lister->select($sql);
      if(!$songdbdata){
         return false;
      }
  }
  return $songdbdata;
}

function listerdbfoundcheck($alldata){
   foreach($alldata as $row){
     $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
     if( $songdataarray_all === false ) continue;
     $songdataarray = $songdataarray_all[0];
     if(!empty($songdataarray["song_name"]) ) {
       return true;
     }
   }
   return false;
}

date_default_timezone_set('Asia/Tokyo');
if (setlocale(LC_ALL,  'ja_JP.UTF-8', 'Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}

$sql = "SELECT * FROM requesttable WHERE nowplaying != '未再生' ORDER BY reqorder ASC";
$select = $db->query($sql);
$allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();

$listerdbenabled = false;
if(array_key_exists("listerDBPATH",$config_ini) ) {
    $lister_dbpath = urldecode($config_ini["listerDBPATH"]);
    if(file_exists($lister_dbpath) ){
        $listerdbenabled = true;
    }
}

$use_listerdb = $listerdbenabled && listerdbfoundcheck($allrequest);
?>
<div class="simplelist-container">
  <h2 class="page-title">再生曲リスト</h2>
  <table class="simplelist-table">
<?php if($use_listerdb): ?>
    <thead>
      <tr>
        <th class="col-no">順番</th>
        <th>曲名（ファイル名）</th>
        <th>作品名</th>
        <th>歌手名</th>
        <th>歌った人</th>
        <th>コメント</th>
      </tr>
    </thead>
    <tbody>
    <?php $num = 1; foreach($allrequest as $row): ?>
      <?php
        $songdataarray = array();
        $songdataarray_all = getsonginfofromfilename($row["fullpath"]);
        if(isset($songdataarray_all[0])) $songdataarray = $songdataarray_all[0];

        $songname = !empty($songdataarray["song_name"])
            ? htmlspecialchars($songdataarray["song_name"])
            : htmlspecialchars($row["songfile"]);

        $foundcomment = '';
        if(!empty($songdataarray["found_comment"])){
            $sc = preg_replace('/\,\/\/.*/', '', $songdataarray["found_comment"]);
            if(!empty($sc)) $foundcomment = '【' . htmlspecialchars($sc) . '】';
        }

        $keychange = '';
        if($row['keychange'] > 0)      $keychange = 'キー変更：+' . $row['keychange'];
        elseif($row['keychange'] < 0)  $keychange = 'キー変更：'  . $row['keychange'];

        $programname = '';
        if(!empty($songdataarray["program_name"]) && $songdataarray["program_name"] != "その他"){
            $programname = htmlspecialchars($songdataarray["program_name"]);
            if(!empty($songdataarray["song_op_ed"]))
                $programname .= '&nbsp;' . htmlspecialchars($songdataarray["song_op_ed"]);
        }

        $artistname = !empty($songdataarray["song_artist"])
            ? htmlspecialchars($songdataarray["song_artist"]) : '';
      ?>
      <tr>
        <td class="col-no"><?= $num ?></td>
        <td class="col-song">
          <?= $songname ?><?= $foundcomment ?>
          <?php if($keychange): ?><br><span class="col-keychange"><?= htmlspecialchars($keychange) ?></span><?php endif; ?>
          <button class="mobile-accordion-btn" type="button">詳細を見る ▼</button>
        </td>
        <td class="col-program"><?= $programname ?></td>
        <td class="col-artist"><?= $artistname ?></td>
        <td class="col-singer"><?= htmlspecialchars($row["singer"]) ?></td>
        <td class="col-comment"><?= htmlspecialchars($row["comment"]) ?></td>
      </tr>
    <?php $num++; endforeach; ?>
    </tbody>
<?php else: ?>
    <thead>
      <tr>
        <th class="col-no">順番</th>
        <th>曲名（ファイル名）</th>
        <th>歌った人</th>
        <th>コメント</th>
      </tr>
    </thead>
    <tbody>
    <?php $num = 1; foreach($allrequest as $row): ?>
      <?php
        $keychange = '';
        if($row['keychange'] > 0)      $keychange = 'キー変更：+' . $row['keychange'];
        elseif($row['keychange'] < 0)  $keychange = 'キー変更：'  . $row['keychange'];
      ?>
      <tr>
        <td class="col-no"><?= $num ?></td>
        <td class="col-song">
          <?= htmlspecialchars($row["songfile"]) ?>
          <?php if($keychange): ?><br><span class="col-keychange"><?= htmlspecialchars($keychange) ?></span><?php endif; ?>
          <button class="mobile-accordion-btn" type="button">詳細を見る ▼</button>
        </td>
        <td class="col-singer"><?= htmlspecialchars($row["singer"]) ?></td>
        <td class="col-comment"><?= htmlspecialchars($row["comment"]) ?></td>
      </tr>
    <?php $num++; endforeach; ?>
    </tbody>
<?php endif; ?>
  </table>
</div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
    $(function() {
      $('.mobile-accordion-btn').on('click', function() {
        var $tr = $(this).closest('tr');
        $tr.toggleClass('is-expanded');
        var open = $tr.hasClass('is-expanded');
        $(this).text(open ? '閉じる ▲' : '詳細を見る ▼');
        $(this).toggleClass('is-open', open);
      });
    });
    </script>
  </body>
</html>
