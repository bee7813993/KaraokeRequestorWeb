<?php
/**
 * /api/nowplaying.php
 *
 * 再生状態の取得 (get_playingstatus_json.php のエンベロープ版)。
 * 既存版は停止中に空応答を返すが、本エンドポイントは常に JSON を返し、
 * クライアント側の「空 = 停止中」という特別扱いを不要にする。
 * データ構築は function_playingstatus.php を共用する。
 *
 * 応答:
 *   停止中: { "ok":true, "data": { "playing":false } }
 *   再生中: { "ok":true, "data": { "playing":true,
 *             "status":"...", "playtime":N, "totaltime":N,
 *             "playtime_txt":"...", "totaltime_txt":"...",
 *             "playingtitle":"...", "playingfile":"...", "playingsinger":"...",
 *             "player":"mpc|foobar|none",  // プレイヤー種別
 *             "keychange":N,               // 再生中の曲の現在キー (半音)
 *             "nextsong": { title, songfile, show_file, singer, kind } | null } }
 *   status は MPC の状態番号 (文字列): "2"=再生中 / "1"=一時停止 など
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../function_playingstatus.php';

$data = build_playingstatus_data($db, $config_ini);

if ($data === null) {
    api_ok(['playing' => false]);
}

api_ok(array_merge(['playing' => true], $data));
