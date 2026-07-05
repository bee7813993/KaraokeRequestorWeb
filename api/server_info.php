<?php
/**
 * /api/server_info.php
 *
 * サーバー情報の取得。モバイルアプリの接続設定画面が
 * 「接続先がゆかりかどうか」「easyauth が必要かどうか」を判定するために使う。
 *
 * このエンドポイントだけは認証前に呼べる必要があるため、_common.php
 * (easyauth チェック込み) を使わず、機微情報を一切含まない最小限のみ返す。
 *
 * 応答: { "ok":true, "data": { "app":"yukari", "version":"...", "easyauth_required":bool } }
 */

// _common.php と同じ CWD / REQUEST_URI 正規化 (認証は行わない)
chdir(dirname(__DIR__));
if (isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('#^/api/#', '/', $_SERVER['REQUEST_URI'], 1);
}
require_once __DIR__ . '/../commonfunc.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'ok'   => true,
    'data' => [
        'app'               => 'yukari',
        'version'           => get_version(),
        'easyauth_required' => configbool('useeasyauth', false),
    ],
], JSON_UNESCAPED_UNICODE);
