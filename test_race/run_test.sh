#!/bin/bash
# 競合状態テストランナー
# 修正前 (before) と修正後 (after) をそれぞれ PARALLEL プロセス並列で実行し、
# reqorder の重複数を比較する

PARALLEL=20       # 同時並列数
WORKER="$(dirname "$0")/worker.php"
DBFILE_BEFORE="/tmp/race_test_before.db"
DBFILE_AFTER="/tmp/race_test_after.db"
SCHEMA="CREATE TABLE IF NOT EXISTS requesttable (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  songfile TEXT,
  singer TEXT,
  reqorder INTEGER DEFAULT 0
);"

run_test() {
    local mode="$1"
    local dbfile="$2"

    # DB を毎回クリーン作成
    rm -f "$dbfile" "${dbfile}-wal" "${dbfile}-shm"
    sqlite3 "$dbfile" "$SCHEMA"

    echo "▶ [${mode}] ${PARALLEL}プロセス並列で INSERT+採番..."
    local pids=()
    for i in $(seq 1 $PARALLEL); do
        php "$WORKER" "$mode" "$dbfile" "$i" &
        pids+=($!)
    done

    # 全プロセス終了を待つ
    local fail=0
    for pid in "${pids[@]}"; do
        wait "$pid" || ((fail++))
    done

    # 結果検証
    local total dup_count min_req max_req
    total=$(sqlite3 "$dbfile" "SELECT COUNT(*) FROM requesttable;")
    dup_count=$(sqlite3 "$dbfile" \
        "SELECT COUNT(*) FROM (
           SELECT reqorder, COUNT(*) AS c
             FROM requesttable
            GROUP BY reqorder
           HAVING c > 1
         );")
    min_req=$(sqlite3 "$dbfile" "SELECT MIN(reqorder) FROM requesttable;")
    max_req=$(sqlite3 "$dbfile" "SELECT MAX(reqorder) FROM requesttable;")

    echo ""
    echo "  登録件数          : ${total} / ${PARALLEL}"
    echo "  reqorder 重複グループ数: ${dup_count}  ← 0なら正常"
    echo "  reqorder 範囲     : ${min_req} 〜 ${max_req}"
    echo "  プロセス失敗数    : ${fail}"

    if [ "$dup_count" -gt 0 ]; then
        echo "  ⚠ 重複詳細:"
        sqlite3 "$dbfile" \
            "SELECT reqorder, COUNT(*) AS cnt
               FROM requesttable
              GROUP BY reqorder
             HAVING cnt > 1
             ORDER BY reqorder;"
    fi
    echo ""
}

echo "=========================================="
echo "  競合状態テスト (並列数: ${PARALLEL})"
echo "=========================================="
echo ""
echo "── 修正前 (ERRMODE_SILENT / busy_timeout=0) ──"
run_test "before" "$DBFILE_BEFORE"

echo "── 修正後 (ERRMODE_EXCEPTION / WAL / busy_timeout=5000) ──"
run_test "after" "$DBFILE_AFTER"

echo "=========================================="
echo "テスト完了"
