<?php
/**
 * 競合状態テスト用ワーカー（2段階バリア版）
 *
 * 2段階バリアで「全員が同じ MAX 値を読んだ後に一斉に UPDATE」する状況を作り、
 * 修正前の reqorder 重複を確実に再現する。
 *
 * before: トランザクションなし + 2段階バリア
 *   → 全員が MAX=0 を読み → 全員が reqorder=1 を設定 → 重複 20 件
 *
 * after : BEGIN IMMEDIATE（バリア前）+ busy_timeout
 *   → 1 プロセスだけ lock 取得、残りは自動待機 → 重複なし
 */
$mode    = $argv[1] ?? 'after';
$dbfile  = $argv[2] ?? '/tmp/race_test.db';
$wid     = (int)($argv[3] ?? getmypid());
$total   = (int)($argv[4] ?? 20);

$bar = "/tmp/race_barrier_{$mode}";

function wait_barrier(string $dir, string $tag, int $total): void {
    $deadline = microtime(true) + 10.0;
    while (count(glob("{$dir}/{$tag}_*")) < $total) {
        if (microtime(true) > $deadline) break;
        usleep(300);
    }
}

// ════════════════════════════════════════════════════════════
// 修正前: トランザクションなし + 2段階バリア
//   Stage1: 全員 INSERT 完了を待つ（全員のINSERT後にMAX取得を確実に揃える）
//   Stage2: 全員 MAX 取得完了を待つ（同じ値を読んだことを確認してから一斉UPDATE）
// ════════════════════════════════════════════════════════════
if ($mode === 'before') {

    $db = new PDO('sqlite:' . $dbfile);

    // Stage1: INSERT
    @mkdir($bar, 0777, true);
    $stmt = $db->prepare(
        "INSERT INTO requesttable (songfile, singer, reqorder)
         VALUES (:fn, :sing, 0)"
    );
    $stmt->execute([':fn' => "song_{$wid}", ':sing' => "worker_{$wid}"]);
    $newid = (int)$db->lastInsertId();
    file_put_contents("{$bar}/s1_{$wid}", '1');
    wait_barrier($bar, 's1', $total);
    // ここで全員の INSERT が完了している → MAX(reqorder) はまだ全員 0

    // Stage2: MAX 取得（全員が同じ 0 を読む）
    $maxrow = $db->query(
        "SELECT COALESCE(MAX(reqorder), 0) AS maxreq
           FROM requesttable WHERE id != " . $newid
    )->fetch(PDO::FETCH_ASSOC);
    $newreqorder = (int)$maxrow['maxreq'] + 1;  // 全員が 1 になる
    file_put_contents("{$bar}/s2_{$wid}", '1');
    wait_barrier($bar, 's2', $total);
    // ここで全員が reqorder=1 を持っている状態で一斉に UPDATE

    // Stage3: UPDATE（全員が reqorder=1 を書く → 重複確定）
    $stmt_u = $db->prepare(
        'UPDATE requesttable SET reqorder = :req WHERE id = :id'
    );
    $stmt_u->bindValue(':req', $newreqorder, PDO::PARAM_INT);
    $stmt_u->bindValue(':id',  $newid,       PDO::PARAM_INT);
    $stmt_u->execute();

    echo "OK worker={$wid} id={$newid} reqorder={$newreqorder}\n";

// ════════════════════════════════════════════════════════════
// 修正後: BEGIN IMMEDIATE + busy_timeout=5000
//   同じバリア後に一斉に BEGIN IMMEDIATE → 1 プロセスずつ順番待ち
//   ロック保持中は他プロセスの MAX 取得も後になる → 重複しない
// ════════════════════════════════════════════════════════════
} else {

    $db = new PDO('sqlite:' . $dbfile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $db->exec('PRAGMA journal_mode=WAL;');
    $db->exec('PRAGMA busy_timeout=5000;');

    // 修正前と同じタイミングで BEGIN IMMEDIATE を試みる
    @mkdir($bar, 0777, true);
    file_put_contents("{$bar}/s1_{$wid}", '1');
    wait_barrier($bar, 's1', $total);

    try {
        // 全員が一斉に BEGIN IMMEDIATE を試みる。
        // busy_timeout=5000ms の間 SQLite が自動リトライするため、
        // 1 プロセスずつ順番に処理されて reqorder は重複しない。
        $db->exec("BEGIN IMMEDIATE;");

        $stmt = $db->prepare(
            "INSERT INTO requesttable (songfile, singer, reqorder)
             VALUES (:fn, :sing, 0)"
        );
        $stmt->execute([':fn' => "song_{$wid}", ':sing' => "worker_{$wid}"]);
        $newid = (int)$db->lastInsertId();

        $maxrow = $db->query(
            "SELECT COALESCE(MAX(reqorder), 0) AS maxreq
               FROM requesttable WHERE id != " . $newid
        )->fetch(PDO::FETCH_ASSOC);
        $newreqorder = (int)$maxrow['maxreq'] + 1;

        $stmt_u = $db->prepare(
            'UPDATE requesttable SET reqorder = :req WHERE id = :id'
        );
        $stmt_u->bindValue(':req', $newreqorder, PDO::PARAM_INT);
        $stmt_u->bindValue(':id',  $newid,       PDO::PARAM_INT);
        $stmt_u->execute();

        $db->exec("COMMIT;");
        echo "OK worker={$wid} id={$newid} reqorder={$newreqorder}\n";

    } catch (Exception $e) {
        try { $db->exec("ROLLBACK;"); } catch (Exception $_) {}
        echo "FAIL worker={$wid} " . $e->getMessage() . "\n";
        exit(1);
    }
}
