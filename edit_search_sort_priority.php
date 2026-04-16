<?php
require_once 'commonfunc.php';

$priority_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'search_sort_priority.json';

function load_sort_priorities($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return [];
}

function save_sort_priorities($file, $priorities) {
    file_put_contents($file, json_encode($priorities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $priorities = load_sort_priorities($priority_file);

    if ($action === 'add') {
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
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
    } elseif ($action === 'clear') {
        save_sort_priorities($priority_file, []);
        $message = '全ルールを削除しました';
    }
}

$priorities = load_sort_priorities($priority_file);
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
        <th class="col-xs-9">動画制作者名</th>
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
        <td colspan="3" class="text-center text-muted">ルールが設定されていません</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>ルール追加</h3>
  <form method="post" action="edit_search_sort_priority.php" class="form-inline">
    <input type="hidden" name="action" value="add">
    <div class="form-group">
      <label for="keyword">動画制作者名&nbsp;</label>
      <input type="text" name="keyword" id="keyword" class="form-control" placeholder="動画制作者名" style="width:300px;">
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

  <div class="panel panel-default">
    <div class="panel-heading"><strong>この画面の使い方</strong></div>
    <div class="panel-body">
      <ol>
        <li>「ルール追加」フォームに <strong>動画制作者名</strong> と <strong>優先度</strong>（1以上の整数）を入力して「追加」ボタンを押します。</li>
        <li>動画制作者名は<strong>完全一致</strong>で判定されます。例えば「こな」と登録しても「ここな」には一致しません。「こな」と「こな（ゲーム）」を同じ扱いにしたい場合は、同じ優先度で両方を登録してください。</li>
        <li>ルールを削除するには一覧の「削除」ボタンを押します。</li>
      </ol>
    </div>
  </div>

  <div class="panel panel-info">
    <div class="panel-heading"><strong>「おすすめ順」の動作について</strong></div>
    <div class="panel-body">
      <p>検索結果画面の「おすすめ順」を有効にすると、このページで設定したルールに従って並べ替えます。</p>
      <table class="table table-condensed table-bordered" style="background:#fff;">
        <thead><tr><th>条件</th><th>表示位置</th></tr></thead>
        <tbody>
          <tr><td>ルールに一致する動画制作者</td><td>設定した優先度順（数値が小さいほど上位）</td></tr>
          <tr><td>いずれのルールにも一致しない動画制作者</td><td>一致した制作者の後ろ</td></tr>
          <tr><td>動画制作者が未設定（空欄）</td><td>最後尾</td></tr>
        </tbody>
      </table>
      <ul class="list-unstyled" style="margin-bottom:0;">
        <li>・同じ優先度が複数ある場合、その中では検索画面の「項目」「順番」設定の順で表示されます。</li>
        <li>・ルールが1件も設定されていない場合、「おすすめ順」を有効にしても効果はありません。</li>
      </ul>
    </div>
  </div>

  <form method="post" action="edit_search_sort_priority.php"
        onsubmit="return confirm('全ルールを削除しますか？');">
    <input type="hidden" name="action" value="clear">
    <button type="submit" class="btn btn-warning">全ルールを削除</button>
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
