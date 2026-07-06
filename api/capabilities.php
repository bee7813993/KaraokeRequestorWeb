<?php
/**
 * /api/capabilities.php
 *
 * アプリ起動時に呼び出す機能フラグ取得エンドポイント。
 * モバイルアプリ側はこのレスポンスを基に UI の出し分けを行う。
 *
 * - config.ini の機微情報 (パスワード・クライアントシークレット等) は一切含まない
 * - configbool() で安全に読んだブール値と、UI 判定に必要な最小限の値のみ返す
 *
 * 応答:
 * {
 *   "ok": true,
 *   "data": {
 *     "features": {
 *       "mypage":         bool,   // マイページ機能 (履歴・お気に入り等)
 *       "bingo":          bool,   // カラオケビンゴ
 *       "keychange":      bool,   // キー変更
 *       "secret":         bool,   // シークレットリクエスト
 *       "bgv":            bool,   // BGVモード (ループ再生)
 *       "userpause":      bool,   // ユーザーによる小休止挿入
 *       "haishin":        bool,   // 配信向け機能 (映像なしリクエスト等)
 *       "nonamerequest":  bool,   // 匿名リクエスト
 *       "google_sync":    bool,   // Google Drive 同期
 *       "easyauth":       bool,   // 簡易認証の有効/無効
 *       "new_request_list": bool, // スワイプ版リクエストリスト
 *       "new_search_ui":  bool    // BS5 検索UI
 *     },
 *     "player": {
 *       "mode":   int,    // 1=MPC-BE, 2=foobar2000, 3=自動, 4=その他
 *       "autoplay": bool  // 自動再生
 *     },
 *     "request": {
 *       "noname_username": string  // 匿名リクエスト時のデフォルト名
 *     }
 *   }
 * }
 */
require_once __DIR__ . '/_common.php';

$features = [
    'mypage'           => configbool('usemypage',           true),
    'bingo'            => configbool('usebingo',            false),
    'keychange'        => configbool('usekeychange',        false),
    'secret'           => configbool('usesecret',           true),
    'bgv'              => configbool('usebgv',              false),
    'userpause'        => configbool('useuserpause',        false),
    'haishin'          => configbool('usehaishin',          true),
    'nonamerequest'    => configbool('nonamerequest',       false),
    'otherplayer'      => configbool('useotherplayer',      false),
    'google_sync'      => !empty($config_ini['google_client_id']),
    'easyauth'         => configbool('useeasyauth',         false),
    'new_request_list' => configbool('usenewrequestlist',   false),
    'new_search_ui'    => configbool('usenewsearchui',      false),
];

$player = [
    'mode'     => (int)($config_ini['playmode'] ?? 3),
    'autoplay' => configbool('autoplay_exec', false),
];

$otherplayer_disc = urldecode($config_ini['otherplayer_disc'] ?? '');
$request = [
    'noname_username'  => urldecode($config_ini['nonameusername'] ?? '名無しさん'),
    // 別プレイヤー再生チェックボックスの表示名 (運用者がカスタムできる)
    'otherplayer_disc' => $otherplayer_disc !== '' ? $otherplayer_disc : '別プレイヤー再生',
];

api_ok([
    'features' => $features,
    'player'   => $player,
    'request'  => $request,
]);
