<?php
require_once 'commonfunc.php';
require_once 'prioritydb_func.php';

// アクション処理（出力前）
if (array_key_exists("action", $_REQUEST)) {
    if ($_REQUEST["action"] === 'delete' && array_key_exists("id", $_REQUEST)) {
        prioritydb_delete($priority_db, (int)$_REQUEST["id"]);
    }
}

if (array_key_exists("kind", $_REQUEST)
    && array_key_exists("priorityword", $_REQUEST)
    && array_key_exists("prioritynum", $_REQUEST)
    && $_REQUEST["kind"] !== 'none'
    && $_REQUEST["priorityword"] !== ''
    && is_numeric($_REQUEST["prioritynum"])) {
    prioritydb_add($priority_db, $_REQUEST["kind"], $_REQUEST["priorityword"], (int)$_REQUEST["prioritynum"]);
}

$prioritylist = prioritydb_get($priority_db);
?>
<!doctype html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<title>表示優先度設定（Everything）</title>
<?php print_bs5_search_head(); ?>
</head>
<body>
<?php shownavigatioinbar_bs5('init.php'); ?>

<div class="container py-3" style="max-width:860px;">

  <h4 class="mb-3">表示優先度設定（Everything）</h4>

  <!-- 優先度の説明 -->
  <div class="search-section mb-4">
    <div class="search-section-body">
      <h6 class="mb-2">優先度（prioritynum）のしくみ</h6>
      <p class="mb-2" style="font-size:0.9rem;">
        ファイル名検索（Everything）の結果を、キーワードに一致するフォルダ・ファイル名ごとに並び順を制御します。<br>
        <strong>数字が大きいほど上に表示されます。</strong>
      </p>
      <table class="table table-sm table-bordered mb-2" style="font-size:0.85rem;max-width:480px;">
        <thead class="table-light">
          <tr><th>優先度（例）</th><th>意味</th></tr>
        </thead>
        <tbody>
          <tr><td><strong>51 以上</strong></td><td>設定なしのファイルより <strong>優先して上に</strong> 表示</td></tr>
          <tr><td><strong>50</strong></td><td>設定なしのファイルのデフォルト値（通常表示）</td></tr>
          <tr><td><strong>49 以下</strong></td><td>おすすめ順「有効」時は <strong>検索結果に表示しない</strong>（非表示・除外）</td></tr>
        </tbody>
      </table>
      <p class="mb-0" style="font-size:0.82rem;color:var(--color-text-muted);">
        「ディレクトリ」はパス文字列に一致、「ファイル」はファイル名に一致で判定します。<br>
        おすすめ順「無効」に切り替えると優先度を無視して Everything の直接ソート順で表示します。
      </p>
    </div>
  </div>

  <!-- 現在の登録一覧 -->
  <div class="search-section mb-4">
    <div class="search-section-body">
      <h6 class="mb-3">登録済み一覧</h6>
      <?php if (empty($prioritylist)): ?>
        <p class="text-muted" style="font-size:0.9rem;">登録されていません。</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
              <tr>
                <th style="width:3em;">No.</th>
                <th style="width:6em;">種別</th>
                <th style="width:5em;">優先度</th>
                <th>キーワード</th>
                <th style="width:5em;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($prioritylist as $row): ?>
              <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo $row['kind'] == 1 ? 'ディレクトリ' : 'ファイル'; ?></td>
                <td>
                  <?php
                  $pn = (int)$row['prioritynum'];
                  if ($pn >= 51) {
                      echo '<span class="badge bg-success">' . $pn . '</span>';
                  } elseif ($pn == 50) {
                      echo '<span class="badge bg-secondary">' . $pn . '</span>';
                  } else {
                      echo '<span class="badge bg-danger">' . $pn . '</span>';
                  }
                  ?>
                </td>
                <td style="word-break:break-all;"><?php echo htmlspecialchars($row['priorityword'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <a href="edit_priority.php?action=delete&id=<?php echo (int)$row['id']; ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('削除しますか？');">削除</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 追加フォーム -->
  <div class="search-section mb-4">
    <div class="search-section-body">
      <h6 class="mb-3">項目を追加</h6>
      <form method="get" action="edit_priority.php" class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label-sm">種別</label>
          <div class="d-flex gap-3">
            <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
              <input type="radio" name="kind" value="dir" checked> ディレクトリ
            </label>
            <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
              <input type="radio" name="kind" value="file"> ファイル
            </label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label-sm" for="pword">キーワード</label>
          <input type="text" id="pword" name="priorityword" class="form-control-themed" placeholder="例: ゆーふうりん" required>
        </div>
        <div class="col-md-2">
          <label class="form-label-sm" for="pnum">優先度</label>
          <input type="number" id="pnum" name="prioritynum" class="form-control-themed" placeholder="51" value="51" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn-secondary-themed">登録</button>
        </div>
      </form>
    </div>
  </div>

  <div class="d-flex gap-3">
    <a href="init.php" class="btn-secondary-themed">設定画面に戻る</a>
    <a href="requestlist_top.php" class="btn-secondary-themed">トップに戻る</a>
  </div>

</div>

<?php print_bg_style_block(true); ?>
</body>
</html>
