<?php
/**
 * /api/search.php
 *
 * ローカルファイル検索の「生データ版」。
 * 既存の searchfilefromkeyword_json.php は表示用に HTML (リクエストボタンやプレビュー
 * モーダル) を JSON 値へ埋め込んでいるが、本エンドポイントはモバイルアプリ等の
 * Web UI 以外からの利用を想定し、純粋なデータ (name / path / fullpath / size) のみ返す。
 *
 * 主用途: 予約の「もう一度リクエスト」で、保存済み songfile を再検索して
 *         現在の fullpath を解決する (動画フォルダ移動に強くするため)。
 *
 * パラメータ:
 *   keyword (必須) 検索ワード
 *   order   (任意) Everything 用ソート指定 (例: sort=size&ascending=0)
 *   path    (任意) 検索対象パス絞り込み
 *
 * 応答: { "ok":true, "data": { keyword, total, count, items:[ {name,path,fullpath,size,priority} ] } }
 */
require_once __DIR__ . '/_common.php';

$keyword = trim((string)api_param('keyword', ''));
if ($keyword === '') {
    api_error('keyword is required');
}

$order = api_param('order', null);
$path  = api_param('path', null);

$result_a = array();
searchlocalfilename($keyword, $result_a, $order, $path);

$items = array();
$total = 0;

if (is_array($result_a) && isset($result_a['totalResults']) && $result_a['totalResults'] >= 1) {
    // $priority_db は prioritydb_func.php (commonfunc 経由) が定義するグローバル。
    // 本ファイルのトップレベルはグローバルスコープなのでそのまま参照できる。
    $result_withp = sortpriority($priority_db, $result_a);
    $total = (int)$result_withp['totalResults'];

    foreach ($result_withp['results'] as $v) {
        // サイズ 1 以下 (フォルダ等) は除外。既存 searchfilefromkeyword_json.php と同じ扱い。
        if ($v['size'] <= 1) {
            continue;
        }
        $items[] = array(
            'name'     => $v['name'],
            'path'     => $v['path'],
            'fullpath' => $v['path'] . '\\' . $v['name'],
            'size'     => (int)$v['size'],
            'priority' => isset($v['priority']) ? (int)$v['priority'] : null,
        );
    }
}

api_ok(array(
    'keyword' => $keyword,
    'total'   => $total,
    'count'   => count($items),
    'items'   => $items,
));
