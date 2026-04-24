<?php
/**
 * mypage_google_relay_config.php
 * 中継サーバー（ykr.moe）の設定ファイル
 *
 * このファイルは Web から直接アクセスできない場所に置くか、
 * .htaccess で拒否することを推奨します。
 *
 * 配置例:
 *   /var/www/html/mypage_google_callback.php   ← mypage_google_relay_server.php をリネーム
 *   /var/www/html/mypage_google_relay_config.php ← このファイル
 */

// Google Cloud Console で作成した OAuth 2.0 クライアントの情報
// リダイレクト URI: https://ykr.moe/mypage_google_callback.php
$RELAY_CLIENT_ID     = 'YOUR_CLIENT_ID.apps.googleusercontent.com';
$RELAY_CLIENT_SECRET = 'YOUR_CLIENT_SECRET';

// ローカルサーバーと共有する HMAC シークレット
// openssl rand -hex 32 などで生成してください
// 各ローカルサーバーの config.ini の google_relay_secret に同じ値を設定します
$RELAY_SECRET = 'YOUR_RELAY_SECRET_CHANGE_THIS';
