<?php
/**
 * 設定バックアップダウンロード
 * 管理者専用 (HTTP Basic Auth 必須)
 */
require_once 'commonfunc.php';
require_once 'configauth_class.php';

$configauth = new ConfigAuth();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Backup - admin only"');
    die('認証が必要です');
}
if ($_SERVER['PHP_AUTH_USER'] !== 'admin') {
    header('WWW-Authenticate: Basic realm="Backup - admin only"');
    die('認証が必要です');
}
if (!$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Backup - admin only"');
    die('認証が必要です');
}

$include_db     = isset($_GET['db'])    && $_GET['db']    === '1';
$include_bgimg  = isset($_GET['bgimg']) && $_GET['bgimg'] === '1';

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die('ZipArchive が利用できません。PHP zip 拡張を有効にしてください。');
}

$base = __DIR__;

// バックアップ対象ファイル (存在するもののみ)
$files = [
    'config.ini'                    => 'config.ini',
    'listerdb_config.ini'           => 'listerdb_config.ini',
    'search_sort_priority.json'     => 'search_sort_priority.json',
    'search_sort_priority_auth.json'=> 'search_sort_priority_auth.json',
];

if ($include_db) {
    $dbname = $config_ini['dbname'] ?? 'request.db';
    $files[$dbname] = $dbname;
}

$zip = new ZipArchive();
$tmpfile = tempnam(sys_get_temp_dir(), 'ykbak_');
if ($zip->open($tmpfile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die('ZIPファイルの作成に失敗しました。');
}

foreach ($files as $src => $dst) {
    $fullpath = $base . '/' . $src;
    if (file_exists($fullpath)) {
        $zip->addFile($fullpath, $dst);
    }
}

if ($include_bgimg) {
    $bgdir = $base . '/images/bg';
    if (is_dir($bgdir)) {
        foreach (glob($bgdir . '/*') as $imgfile) {
            if (is_file($imgfile)) {
                $zip->addFile($imgfile, 'images/bg/' . basename($imgfile));
            }
        }
    }
}

$zip->close();

$filename = 'ykari_backup_' . date('Ymd_His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpfile));
header('Pragma: no-cache');
readfile($tmpfile);
unlink($tmpfile);
exit;
