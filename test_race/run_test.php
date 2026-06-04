<?php
/**
 * 競合状態テストランナー（PHP版・バリア同期）
 */

define('PARALLEL',  20);
define('WORKER',    __DIR__ . '/worker.php');
define('DB_BEFORE', '/tmp/race_before.db');
define('DB_AFTER',  '/tmp/race_after.db');

function init_db(string $path): void {
    foreach ([$path, "$path-wal", "$path-shm"] as $f) {
        if (file_exists($f)) unlink($f);
    }
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $db->exec("CREATE TABLE IF NOT EXISTS requesttable (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        songfile TEXT,
        singer   TEXT,
        reqorder INTEGER DEFAULT 0
    )");
}

function clean_barrier(string $mode): void {
    $dir = "/tmp/race_barrier_{$mode}";
    if (is_dir($dir)) {
        foreach (glob("{$dir}/*") as $f) unlink($f);
        rmdir($dir);
    }
}

function check_result(string $path, int $fail_count): void {
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $total     = (int)$db->query("SELECT COUNT(*) FROM requesttable")->fetchColumn();
    $dup_rows  = $db->query(
        "SELECT reqorder, COUNT(*) AS cnt
           FROM requesttable
          GROUP BY reqorder
         HAVING cnt > 1
          ORDER BY reqorder"
    )->fetchAll(PDO::FETCH_ASSOC);
    $dup_count = count($dup_rows);
    $min_req   = $db->query("SELECT MIN(reqorder) FROM requesttable")->fetchColumn();
    $max_req   = $db->query("SELECT MAX(reqorder) FROM requesttable")->fetchColumn();

    echo "  登録件数               : {$total} / " . PARALLEL . "\n";
    echo "  reqorder 重複グループ数: {$dup_count}  ← 0 なら正常\n";
    echo "  reqorder 範囲          : {$min_req} 〜 {$max_req}\n";
    echo "  プロセス失敗数         : {$fail_count}\n";

    if ($dup_count > 0) {
        echo "  ⚠ 重複詳細:\n";
        foreach ($dup_rows as $r) {
            echo "    reqorder={$r['reqorder']}  件数={$r['cnt']}\n";
        }
    }

    $status = ($dup_count === 0 && $fail_count === 0) ? '✅ PASS' : '❌ FAIL（重複あり）';
    echo "  結果: {$status}\n";
}

function run_parallel(string $mode, string $dbfile): int {
    $procs = [];
    for ($i = 1; $i <= PARALLEL; $i++) {
        $cmd  = "php " . WORKER . " {$mode} {$dbfile} {$i} " . PARALLEL;
        $desc = [['pipe','r'],['pipe','w'],['pipe','w']];
        $procs[] = ['proc' => proc_open($cmd, $desc, $pipes), 'pipes' => $pipes];
    }

    $fail = 0;
    foreach ($procs as &$p) {
        $out = stream_get_contents($p['pipes'][1]);
        fclose($p['pipes'][0]);
        fclose($p['pipes'][1]);
        fclose($p['pipes'][2]);
        $code = proc_close($p['proc']);
        echo $out;
        if ($code !== 0) $fail++;
    }
    return $fail;
}

// ── メイン ──────────────────────────────────────────────────
echo str_repeat('=', 52) . "\n";
echo "  競合状態テスト (並列数: " . PARALLEL . ")\n";
echo str_repeat('=', 52) . "\n\n";

echo "── 修正前 (ERRMODE_SILENT / トランザクションなし) ──\n";
init_db(DB_BEFORE);
clean_barrier('before');
echo "▶ 全" . PARALLEL . "プロセスが INSERT 後に一斉に MAX+1 採番...\n";
$fail_before = run_parallel('before', DB_BEFORE);
echo "\n";
check_result(DB_BEFORE, $fail_before);

echo "\n";
echo "── 修正後 (ERRMODE_EXCEPTION / WAL / busy_timeout / BEGIN IMMEDIATE) ──\n";
init_db(DB_AFTER);
clean_barrier('after');
echo "▶ 全" . PARALLEL . "プロセスが BEGIN IMMEDIATE 内で INSERT+採番...\n";
$fail_after = run_parallel('after', DB_AFTER);
echo "\n";
check_result(DB_AFTER, $fail_after);

echo "\n" . str_repeat('=', 52) . "\n";
echo "テスト完了\n";
