<?php
// ⑫⑬ 不具合再現用テストセットアップ（テスト後に削除すること）
require_once 'commonfunc.php';

$action = $_GET['action'] ?? 'setup';

if ($action === 'setup') {
    $db->exec("DELETE FROM requesttable");

    $stmt = $db->prepare(
        "INSERT INTO requesttable (songfile, singer, comment, kind, fullpath, nowplaying, status, reqorder, clientip, clientua, playtimes, secret, loop) " .
        "VALUES (:fn, :sg, '', :kind, '', :np, 'OK', :rq, '127.0.0.1', 'test', 0, 0, 0)"
    );

    // reqorder 1: 小休止(再生済)  ← ⑬ポイント: 旧コードはこれを境界と認識しない
    // reqorder 2: テスト角(再生済) ← 小休止後の1周目1番手
    // reqorder 3: テスト丸(再生済) ← 小休止後の1周目2番手
    // reqorder 4: テスト角(未再生) ← 2周目のテスト角予約
    $entries = [
        [1, '小休止テスト', '小休止', '小休止', '再生済'],
        [2, 'テスト用曲A',  'テスト角', '動画',  '再生済'],
        [3, 'テスト用曲B',  'テスト丸', '動画',  '再生済'],
        [4, 'テスト用曲C',  'テスト角', '動画',  '未再生'],
    ];
    foreach ($entries as [$rq, $fn, $sg, $kind_val, $np]) {
        $stmt->execute([':fn'=>$fn, ':sg'=>$sg, ':kind'=>$kind_val, ':np'=>$np, ':rq'=>$rq]);
    }

    echo "<pre>テストデータ セットアップ完了\n\n";
    echo "現在の状態:\n";
    echo "  reqorder=1: 小休止テスト (小休止, 再生済)   ← ⑬ポイント\n";
    echo "  reqorder=2: テスト角     (動画,  再生済)   ← 1周目1番手\n";
    echo "  reqorder=3: テスト丸     (動画,  再生済)   ← 1周目2番手\n";
    echo "  reqorder=4: テスト角     (動画,  未再生)   ← 2周目テスト角予約済み\n\n";
    echo "次のステップ:\n";
    echo "  → 「テスト丸」という名前で任意の曲を予約追加してください\n\n";
    echo "期待される挿入位置:\n";
    echo "  ✗ バグあり: reqorder=4 (テスト角より前に割り込み)\n";
    echo "  ✓ バグなし: reqorder=5 (テスト角の後に正しく配置)\n";
    echo "</pre>";
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?action=status">現在のDB状態を確認</a>';

} elseif ($action === 'status') {
    $rows = $db->query("SELECT id, reqorder, singer, kind, nowplaying, songfile FROM requesttable ORDER BY reqorder ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>現在のDB状態 (reqorder昇順):\n\n";
    echo sprintf("%-4s %-8s %-14s %-8s %-8s %s\n", "id", "reqorder", "singer", "kind", "nowplaying", "songfile");
    echo str_repeat("-", 80) . "\n";
    foreach ($rows as $r) {
        echo sprintf("%-4s %-8s %-14s %-8s %-8s %s\n",
            $r['id'], $r['reqorder'], $r['singer'], $r['kind'], $r['nowplaying'], mb_substr($r['songfile'], 0, 20));
    }
    $pm = 0;
    foreach ($rows as $r) { if ($r['nowplaying'] !== '未再生') $pm = max($pm, (int)$r['reqorder']); }
    echo "\n played_max (非未再生の最大reqorder): " . $pm . "\n";
    echo "</pre>";
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?action=setup">再セットアップ</a>';
}
?>
