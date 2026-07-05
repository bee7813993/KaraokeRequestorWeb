<?php
/**
 * /api/_common.php
 *
 * /api/ 配下の薄い JSON ファサード層の共通処理。
 * 各エンドポイントは先頭で require_once __DIR__.'/_common.php' すること。
 *
 * 役割:
 *   - CWD をプロジェクトルートへ移し、commonfunc.php 等が使う相対パス
 *     (config.ini / request.db / prioritydb 等) を正しく解決させる
 *   - commonfunc.php を読み込み DB ($db) と各種ヘルパーを利用可能にする
 *   - easyauth による簡易認証 (localhost は素通り)
 *   - 応答エンベロープ ( {"ok":true,"data":...} / {"ok":false,"error":...} ) を統一
 *
 * 注意: easyauth は将来的にトークン認証へ置き換える想定。現状は既存ページと同じ作法。
 */

// /api/ 配下はサブディレクトリのため、Web SAPI では CWD が /api/ になる。
// config.ini や request.db を相対パスで参照する既存コードのために、ルートへ移す。
chdir(dirname(__DIR__));

// 同様に、REQUEST_URI を基準にした相対 URL 解決 (command_mpc() の
// update_playerprogress.php 通知や kara_config.php の roomurl 生成) も
// /api/ 基準にならないよう、URI をルート直下相当へ正規化する。
// ※ commonfunc.php (→ kara_config.php) の読み込みより前に行うこと。
if (isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('#^/api/#', '/', $_SERVER['REQUEST_URI'], 1);
}

require_once __DIR__ . '/../commonfunc.php';
require_once __DIR__ . '/../easyauth_class.php';

// 認証 (setcookie を内部で呼ぶため、いかなる出力よりも前に実行する)
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

header('Content-Type: application/json; charset=utf-8');

/**
 * 成功応答を出力して終了する。
 * @param mixed $data レスポンスのペイロード
 */
function api_ok($data)
{
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * エラー応答を出力して終了する。
 * @param string $message エラーメッセージ
 * @param int    $httpcode HTTP ステータスコード (既定 400)
 */
function api_error($message, $httpcode = 400)
{
    http_response_code($httpcode);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * リクエストパラメータを取得する小ヘルパー (GET/POST 両対応)。
 */
function api_param($key, $default = null)
{
    if (array_key_exists($key, $_REQUEST)) {
        return $_REQUEST[$key];
    }
    return $default;
}
