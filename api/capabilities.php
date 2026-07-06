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
 *     },
 *     "server": {
 *       "room_name": string,  // 部屋名 (別部屋URL設定の先頭の部屋番号)。未設定時は空文字
 *       "rooms": [            // 移動できる部屋の一覧 (Web 版の部屋ドロップダウンと同じ条件)
 *         { "name": string, "url": string }
 *       ]
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

// 部屋名: 別部屋URL設定 (roomurl) の先頭エントリの部屋番号を Web 版の「〇〇部屋」と同じ規則で返す。
// roomurl 未設定時は kara_config.php がアクセス URL からキー 0 で自動生成するため、
// キーが 0 または空のときは「名前なし」として空文字を返す (アプリ側は接続先 URL を表示する)
$room_name = '';
if (!empty($config_ini['roomurl']) && is_array($config_ini['roomurl'])) {
    foreach ($config_ini['roomurl'] as $key => $value) {
        if ((string)$key !== '' && (string)$key !== '0') {
            $room_name = (string)$key;
        }
        break;
    }
}

// 移動できる部屋の一覧: Web 版の部屋ドロップダウンと同じ条件
// (URL が設定されていて、かつ「表示する」(roomurlshow=1) のものだけ)
$rooms = [];
if (!empty($config_ini['roomurl']) && is_array($config_ini['roomurl'])) {
    foreach ($config_ini['roomurl'] as $key => $value) {
        if (empty($value)) {
            continue;
        }
        if (!(array_key_exists('roomurlshow', $config_ini)
            && is_array($config_ini['roomurlshow'])
            && array_key_exists($key, $config_ini['roomurlshow'])
            && $config_ini['roomurlshow'][$key] == 1)) {
            continue;
        }
        $rooms[] = [
            'name' => (string)$key,
            'url'  => urldecode($value),
        ];
    }
}

api_ok([
    'features' => $features,
    'player'   => $player,
    'request'  => $request,
    'server'   => [
        'room_name' => $room_name,
        'rooms'     => $rooms,
    ],
]);
