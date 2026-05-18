<?php
require_once 'commonfunc.php';

// ローカル接続のみ許可
if (check_access_from_online()) {
    http_response_code(403);
    exit;
}

$filepath = isset($_GET['path']) ? $_GET['path'] : '';
if (empty($filepath)) {
    http_response_code(400);
    exit;
}

// 拡張子チェック
$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime_map = ['mp4' => 'video/mp4', 'flv' => 'video/x-flv'];
if (!isset($mime_map[$ext])) {
    http_response_code(403);
    exit;
}
$mime = $mime_map[$ext];

// ファイル存在確認（UTF-8 → SJIS変換を試みる）
$filepath_sjis = mb_convert_encoding($filepath, 'cp932', 'utf-8');
if (file_exists($filepath_sjis)) {
    $realpath = $filepath_sjis;
} elseif (file_exists($filepath)) {
    $realpath = $filepath;
} else {
    http_response_code(404);
    exit;
}

$filesize = filesize($realpath);
$start    = 0;
$end      = $filesize - 1;

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('Cache-Control: no-store');

if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        http_response_code(416);
        header('Content-Range: bytes */' . $filesize);
        exit;
    }
    $start = $m[1] !== '' ? (int)$m[1] : $filesize - (int)$m[2];
    $end   = $m[2] !== '' ? (int)$m[2] : $filesize - 1;
    if ($start > $end || $end >= $filesize) {
        http_response_code(416);
        header('Content-Range: bytes */' . $filesize);
        exit;
    }
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $filesize);
}

$length = $end - $start + 1;
header('Content-Length: ' . $length);

$fp = fopen($realpath, 'rb');
if ($start > 0) {
    fseek($fp, $start);
}
$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $read = min(65536, $remaining);
    echo fread($fp, $read);
    $remaining -= $read;
    flush();
}
fclose($fp);
