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

// ゆかりすたー(13582)への候補URL
// バックスラッシュをスラッシュ変換版とそのまま urlencode 版を両方試みる
$filepath_fwd = str_replace('\\', '/', $filepath);
$candidate_urls = [
    'http://localhost:13582/' . str_replace('%2F', '/', rawurlencode($filepath_fwd)),
    'http://localhost:13582/' . urlencode($filepath),
];

// HEADリクエストでファイル存在確認とContent-Length取得
$filesize = 0;
$chosen_url = null;
foreach ($candidate_urls as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $clen   = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);

    if ($status === 200 && $clen > 0) {
        $filesize   = (int)$clen;
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

// curlコールバックで $start バイト分を読み捨て、$length 分だけ転送
$skip      = $start;
$remaining = $length;
$ch = curl_init($chosen_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$skip, &$remaining) {
        $len = strlen($data);
        if ($skip >= $len) {
            $skip -= $len;
            return $len;
        }
        if ($skip > 0) {
            $data = substr($data, $skip);
            $skip = 0;
        }
        if ($remaining <= 0) {
            return $len;
        }
        if (strlen($data) > $remaining) {
            $data = substr($data, 0, $remaining);
        }
        echo $data;
        flush();
        $remaining -= strlen($data);
        return $len;
    },
]);
curl_exec($ch);
curl_close($ch);
