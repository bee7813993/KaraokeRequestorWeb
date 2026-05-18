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

// ゆかりすたー(13582)へのリクエストパス候補
// urlencode: \ → %5C、: → %3A、日本語 → %XX、スペース → +
// rawurlencode + スラッシュ変換: \ → /、: → %3A、日本語 → %XX
$filepath_fwd = str_replace('\\', '/', $filepath);
$candidate_paths = [
    '/' . urlencode($filepath),
    '/' . str_replace('%2F', '/', rawurlencode($filepath_fwd)),
];

/**
 * fsockopen でゆかりすたーに生 HTTP/1.0 リクエストを送り、
 * ステータスコード・ヘッダ・本体ストリームを返す。
 * libcurl の URL 正規化を回避するために使用。
 */
function yukari_open($req_path, $method = 'HEAD') {
    foreach (['localhost', '127.0.0.1'] as $host) {
        $fp = @fsockopen($host, 13582, $errno, $errstr, 3);
        if (!$fp) continue;

        $req = "$method $req_path HTTP/1.0\r\n"
             . "Host: $host:13582\r\n"
             . "Connection: close\r\n"
             . "\r\n";
        fwrite($fp, $req);

        // ステータス行
        $status_line = fgets($fp, 512);
        if (!preg_match('#HTTP/[\d.]+ (\d+)#', $status_line, $m)) {
            fclose($fp);
            continue;
        }
        $status = (int)$m[1];

        // ヘッダ読み取り
        $headers = [];
        while (!feof($fp)) {
            $line = rtrim(fgets($fp, 4096), "\r\n");
            if ($line === '') break;
            $headers[] = $line;
        }
        return ['fp' => $fp, 'status' => $status, 'headers' => $headers];
    }
    return null;
}

function parse_content_length($headers) {
    foreach ($headers as $h) {
        if (preg_match('/^Content-Length:\s*(\d+)/i', $h, $m)) {
            return (int)$m[1];
        }
    }
    return 0;
}

// HEAD で存在確認とファイルサイズ取得
$filesize   = 0;
$chosen_path = null;
foreach ($candidate_paths as $req_path) {
    $res = yukari_open($req_path, 'HEAD');
    if (!$res) continue;
    fclose($res['fp']);
    if ($res['status'] === 200) {
        $clen = parse_content_length($res['headers']);
        if ($clen > 0) {
            $filesize    = $clen;
            $chosen_path = $req_path;
            break;
        }
        // Content-Length なしでも 200 なら候補として保持し GET で再確認
        if ($chosen_path === null) {
            $chosen_path = $req_path;
        }
    }
}

// HEAD で Content-Length が取れなかった場合 GET で再確認
if ($chosen_path !== null && $filesize === 0) {
    $res = yukari_open($chosen_path, 'GET');
    if ($res) {
        if ($res['status'] === 200) {
            $filesize = parse_content_length($res['headers']);
        }
        fclose($res['fp']);
    }
}

if ($chosen_path === null || $filesize === 0) {
    http_response_code(404);
    exit;
}

// Range リクエスト解析
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

// GET ストリームを開いて $start バイト読み捨て → $length バイト転送
$res = yukari_open($chosen_path, 'GET');
if (!$res || $res['status'] !== 200) {
    http_response_code(502);
    exit;
}
$fp = $res['fp'];

$skip = $start;
while ($skip > 0 && !feof($fp)) {
    $chunk = fread($fp, min(65536, $skip));
    if ($chunk === false || $chunk === '') break;
    $skip -= strlen($chunk);
}

$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, min(65536, $remaining));
    if ($chunk === false || $chunk === '') break;
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($fp);
