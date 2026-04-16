<?php
require_once 'commonfunc.php';

$priority_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'search_sort_priority.json';

$default_priorities = [
    ['keyword' => 'つぼはち', 'priority' => 1],
    ['keyword' => 'つぼはち(Live映像)', 'priority' => 2],
    ['keyword' => 'つぼはち(アニメ公式MV)', 'priority' => 2],
    ['keyword' => 'つぼはち(公式MV-本人映像)', 'priority' => 3],
];

function load_sort_priorities($file, $defaults) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return $defaults;
}

function save_sort_priorities($file, $priorities) {
    file_put_contents($file, json_encode($priorities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $priorities = load_sort_priorities($priority_file, $default_priorities);

    if ($action === 'add') {
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 999;
        if ($keyword !== '' && $priority > 0) {
            $priorities[] = ['keyword' => $keyword, 'priority' => $priority];
            usort($priorities, function($a, $b) { return $a['priority'] - $b['priority']; });
            save_sort_priorities($priority_file, $priorities);
            $message = '追加しました';
        } else {
            $message = 'キーワードを入力し、優先度は1以上の数値を指定してください';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        if ($index >= 0 && $index < count($priorities)) {
            array_splice($priorities, $index, 1);
            save_sort_priorities($priority_file, $priorities);
            $message = '削除しました';
        }
    } elseif ($action === 'reset') {
        save_sort_priorities($priority_file, $default_priorities);
        $message = 'デフォルトに戻しました';
    }
}

$priorities = load_sort_priorities($priority_file, $default_priorities);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<title>おすすめ順 優先度設定</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php shownavigatioinbar(); ?>
<div class="container">
  <h1>おすすめ順 優先度設定</h1>
  <p class="text-muted">
    検索結果の「おすすめ順」で使用する、動画制作者名 (found_worker) の優先度ルールを設定します。<br>
    数値が小さいほど上位に表示されます。キーワードに一致しない制作者は自動的に最下位となります。
  </p>

  <?php if (!empty($message)): ?>
  <div class="alert alert-<?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <h3>現在の優先度ルール</h3>
  <table class="table table-striped table-bordered">
    <thead>
      <tr>
        <th class="col-xs-1">優先度</th>
        <th class="col-xs-9">キーワード（動画制作者名の部分一致）</th>
        <th class="col-xs-2">操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($priorities as $i => $rule): ?>
      <tr>
        <td><?php echo htmlspecialchars($rule['priority'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($rule['keyword'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <form method="post" action="edit_search_sort_priority.php" style="margin:0;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="index" value="<?php echo $i; ?>">
            <button type="submit" class="btn btn-danger btn-xs">削除</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($priorities)): ?>
      <tr>
        <td colspan="3" class="text-center text-muted">ルールがありません（全件デフォルト順で表示）</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>ルール追加</h3>
  <form method="post" action="edit_search_sort_priority.php" class="form-inline">
    <input type="hidden" name="action" value="add">
    <div class="form-group">
      <label for="keyword">キーワード&nbsp;</label>
      <input type="text" name="keyword" id="keyword" class="form-control" placeholder="例: つぼはち" style="width:300px;">
    </div>
    &nbsp;
    <div class="form-group">
      <label for="priority">優先度（小さいほど上位）&nbsp;</label>
      <input type="number" name="priority" id="priority" class="form-control" value="1" min="1" style="width:100px;">
    </div>
    &nbsp;
    <button type="submit" class="btn btn-primary">追加</button>
  </form>

  <hr>

  <form method="post" action="edit_search_sort_priority.php"
        onsubmit="return confirm('デフォルト設定に戻しますか？現在のルールは上書きされます。');">
    <input type="hidden" name="action" value="reset">
    <button type="submit" class="btn btn-warning">デフォルトに戻す</button>
  </form>

  <hr>
  <p>
    <a href="init.php" class="btn btn-default">設定画面に戻る</a>
    &nbsp;
    <a href="requestlist_only.php" class="btn btn-default">トップに戻る</a>
  </p>
</div>
</body>
</html>
