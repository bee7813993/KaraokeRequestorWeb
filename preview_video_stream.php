<?php
require_once 'commonfunc.php';

if (check_access_from_online() && !configbool('online_preview', false)) {
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

// ゆかりすたー(13582)への候補URL。バックスラッシュをスラッシュ変換版とURLエンコード版を両方試みる
$filepath_fwd = str_replace('\\', '/', $filepath);
$candidate_urls = [
    'http://127.0.0.1:13582/' . str_replace('%2F', '/', rawurlencode($filepath_fwd)),
    'http://127.0.0.1:13582/' . urlencode($filepath),
];

// HEADリクエストでファイル存在確認とContent-Length取得
$filesize = 0;
$chosen_url = null;
foreach ($candidate_urls as $url) {
    $hctx = stream_context_create(['http' => [
        'method'          => 'HEAD',
        'ignore_errors'   => true,
        'timeout'         => 3,
    ]]);
    $hfp = @fopen($url, 'rb', false, $hctx);
    if (!$hfp) continue;
    $meta    = stream_get_meta_data($hfp);
    $headers = $meta['wrapper_data'] ?? [];
    fclose($hfp);

    $status = 0;
    foreach ($headers as $h) {
        if (preg_match('#^HTTP/[\d.]+ (\d+)#i', $h, $m)) { $status = (int)$m[1]; }
        if (preg_match('/^Content-Length:\s*(\d+)/i', $h, $m)) { $filesize = (int)$m[1]; }
    }
    if ($status === 200 && $filesize > 0) {
        $chosen_url = $url;
        break;
    }
}

if ($chosen_url === null || $filesize === 0) {
    http_response_code(404);
    exit;
}

// Rangeリクエスト解析
$start = 0;
$end   = $filesize - 1;

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

// GETストリームを開いて$startまで読み捨て、$length分を転送
$gctx = stream_context_create(['http' => [
    'method'        => 'GET',
    'ignore_errors' => true,
    'timeout'       => 30,
]]);
$fp = @fopen($chosen_url, 'rb', false, $gctx);
if (!$fp) {
    http_response_code(502);
    exit;
}

// $startバイト分を読み捨て（ローカルホストなので許容範囲内）
$skip = $start;
while ($skip > 0 && !feof($fp)) {
    $chunk = fread($fp, min(65536, $skip));
    if ($chunk === false) break;
    $skip -= strlen($chunk);
}

$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, min(65536, $remaining));
    if ($chunk === false) break;
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($fp);
