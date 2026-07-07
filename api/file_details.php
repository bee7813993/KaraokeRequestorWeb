<?php
/**
 * /api/file_details.php
 *
 * 動画/音声ファイルの詳細情報 (音声トラック一覧 + 動画詳細)。
 * モバイルアプリの予約確認画面の「音声トラック」選択と「動画詳細情報」表示に使う。
 * getID3 の解析結果はサーバー側でディスクキャッシュされる (getfileinfo)。
 *
 * パラメータ:
 *   fullpath (必須) ファイルのフルパス (検索結果の fullpath)
 *   filename (任意) 表示ファイル名 (fullpath の解決補助)
 *
 * 応答:
 *   { "ok":true, "data":{
 *       "tracks": ["2ch AAC ...", ...],   // 音声トラックのラベル一覧 (判別できない場合は [])
 *       "details": {                       // 解析できない場合は null
 *           "duration":"4:15", "duration_seconds":255,
 *           "resolution":"1920x1080", "frame_rate":29.97,
 *           "video_codec":"...", "audio_codec":"...",
 *           "audio_channels":"ステレオ", "audio_sample_rate":"48,000 Hz",
 *           "bitrate":"5000 kbps" } } }
 */
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../func_audiotracklist.php';

$fullpath = (string)api_param('fullpath', '');
if ($fullpath === '') {
    api_error('fullpath is required');
}
$filename = (string)api_param('filename', '');
if ($filename === '') {
    $filename = basename_jp($fullpath);
}

$lister_dbpath = urldecode($config_ini['listerDBPATH'] ?? '');
$fullpath_utf8 = '';
get_fullfilename($fullpath, $filename, $fullpath_utf8, $lister_dbpath);
if (empty($fullpath_utf8)) {
    api_ok(['tracks' => [], 'details' => null]);
}

$info = getfileinfo($fullpath_utf8, true);

// audiotracklist は [トラック種別, ラベル] の配列 → ラベルの一覧にする
$tracks = [];
foreach ($info['audiotracklist'] as $track) {
    if (is_array($track) && isset($track[1])) {
        $tracks[] = (string)$track[1];
    }
}

$details = $info['videodetails'];
api_ok([
    'tracks'  => $tracks,
    'details' => (is_array($details) && count($details) > 0) ? $details : null,
]);
