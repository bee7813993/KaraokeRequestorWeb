<?php
require_once 'commonfunc.php';

$config_file = 'csv_export_columns.json';

$all_columns_def = [
    ['id' => 'num',          'label' => '順番'],
    ['id' => 'songfile',     'label' => '曲名（ファイル名）'],
    ['id' => 'keychange',    'label' => 'キー'],
    ['id' => 'program_name', 'label' => '作品名'],
    ['id' => 'artist',       'label' => '歌手名'],
    ['id' => 'singer',       'label' => '歌った人'],
    ['id' => 'comment',      'label' => 'コメント'],
    ['id' => 'worker',       'label' => '動画制作者'],
];

function load_csv_columns($config_file, $all_columns_def) {
    if (file_exists($config_file)) {
        $json = file_get_contents($config_file);
        $saved = json_decode($json, true);
        if ($saved !== null) {
            $saved_ids = array_column($saved, 'id');
            foreach ($all_columns_def as $col) {
                if (!in_array($col['id'], $saved_ids)) {
                    $saved[] = ['id' => $col['id'], 'label' => $col['label'], 'enabled' => true];
                }
            }
            return $saved;
        }
    }
    return array_map(function($col) {
        return ['id' => $col['id'], 'label' => $col['label'], 'enabled' => true];
    }, $all_columns_def);
}

$saved_message = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['columns'])) {
    $columns_order = $_POST['columns'];
    $enabled_set = isset($_POST['enabled']) ? $_POST['enabled'] : [];
    $all_ids = array_column($all_columns_def, 'id');
    $label_map = array_column($all_columns_def, 'label', 'id');

    $new_config = [];
    foreach ($columns_order as $col_id) {
        if (!in_array($col_id, $all_ids)) continue;
        $new_config[] = [
            'id'      => $col_id,
            'label'   => $label_map[$col_id],
            'enabled' => in_array($col_id, $enabled_set),
        ];
    }
    file_put_contents($config_file, json_encode($new_config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $saved_message = true;
}

$columns = load_csv_columns($config_file, $all_columns_def);
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<title>CSV出力列設定</title>
<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css" />
<link type="text/css" rel="stylesheet" href="css/style.css" />
<style>
.column-list { list-style: none; padding: 0; max-width: 480px; }
.column-item {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    margin: 5px 0;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: grab;
    user-select: none;
}
.column-item.dragging { opacity: 0.4; background: #e0e0e0; }
.column-item.drag-over-top    { border-top: 3px solid #337ab7; }
.column-item.drag-over-bottom { border-bottom: 3px solid #337ab7; }
.drag-handle { margin-right: 12px; color: #aaa; font-size: 20px; line-height: 1; }
.column-item label { margin: 0; flex: 1; cursor: pointer; font-weight: normal; }
.column-item input[type=checkbox] { margin-right: 8px; transform: scale(1.2); }
</style>
</head>
<body style="padding-top: 20px;">

<?php if ($saved_message): ?>
<div class="container">
  <div class="alert alert-success">設定を保存しました。</div>
</div>
<?php endif; ?>

<div class="container">
  <h3>CSV出力列設定</h3>
  <p>出力する列のチェックを入れ、ドラッグ＆ドロップで順番を変更してください。</p>

  <form method="post" action="edit_csv_columns.php" id="columns-form">
    <ul class="column-list" id="column-list">
      <?php foreach ($columns as $col): ?>
      <li class="column-item" draggable="true" data-id="<?= htmlspecialchars($col['id']) ?>">
        <span class="drag-handle">&#9776;</span>
        <input type="checkbox"
               name="enabled[]"
               value="<?= htmlspecialchars($col['id']) ?>"
               id="cb_<?= htmlspecialchars($col['id']) ?>"
               <?= $col['enabled'] ? 'checked' : '' ?>>
        <label for="cb_<?= htmlspecialchars($col['id']) ?>">
          <?= htmlspecialchars($col['label']) ?>
        </label>
        <input type="hidden" name="columns[]" value="<?= htmlspecialchars($col['id']) ?>">
      </li>
      <?php endforeach; ?>
    </ul>

    <div style="margin-top: 16px;">
      <button type="submit" class="btn btn-primary">保存</button>
      &nbsp;
      <a href="init.php" class="btn btn-default">設定画面に戻る</a>
      &nbsp;
      <a href="simplelistexport_utf8.php" class="btn btn-default">CSVエクスポート</a>
    </div>
  </form>
</div>

<script>
(function() {
    var list = document.getElementById('column-list');
    var dragging = null;

    list.addEventListener('dragstart', function(e) {
        dragging = e.target.closest('.column-item');
        setTimeout(function() { dragging.classList.add('dragging'); }, 0);
    });

    list.addEventListener('dragend', function() {
        if (dragging) dragging.classList.remove('dragging');
        document.querySelectorAll('.column-item.drag-over-top, .column-item.drag-over-bottom')
            .forEach(function(el) {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
        dragging = null;
        syncHiddenInputs();
    });

    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('.column-item');
        if (!target || target === dragging) return;

        document.querySelectorAll('.column-item.drag-over-top, .column-item.drag-over-bottom')
            .forEach(function(el) {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });

        var rect = target.getBoundingClientRect();
        if (e.clientY < rect.top + rect.height / 2) {
            target.classList.add('drag-over-top');
            list.insertBefore(dragging, target);
        } else {
            target.classList.add('drag-over-bottom');
            list.insertBefore(dragging, target.nextSibling);
        }
    });

    function syncHiddenInputs() {
        var items = list.querySelectorAll('.column-item');
        items.forEach(function(item) {
            var id = item.getAttribute('data-id');
            item.querySelector('input[type=hidden]').value = id;
        });
    }
})();
</script>
</body>
</html>
