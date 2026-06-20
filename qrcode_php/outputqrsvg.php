<?php
/*
 * QRコードをSVGで出力する。GD拡張なしで動作する。
 * パラメータ: data (URLエンコード済み文字列), qrsize (1-16, モジュールpx)
 */
require_once "./qrcode.php";

if (!array_key_exists("data", $_REQUEST)) {
    header("Content-type: image/svg+xml");
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="white"/></svg>';
    exit;
}

$l_data = $_REQUEST["data"];
$l_qrsize = 5;
if (array_key_exists("qrsize", $_REQUEST)) {
    $sz = (int)$_REQUEST["qrsize"];
    if ($sz >= 1 && $sz <= 16) {
        $l_qrsize = $sz;
    }
}

$qr  = new Qrcode();
$raw = $qr->cal_qrcode($l_data);

$rows = explode("\n", $raw);
while (!empty($rows) && end($rows) === '') {
    array_pop($rows);
}
$n     = count($rows);
$quiet = 4;
$cell  = $l_qrsize;
$total = ($n + $quiet * 2) * $cell;

header("Content-type: image/svg+xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<svg xmlns="http://www.w3.org/2000/svg"'
    . ' width="' . $total . '" height="' . $total . '"'
    . ' viewBox="0 0 ' . $total . ' ' . $total . '">';
echo '<rect width="100%" height="100%" fill="white"/>';

foreach ($rows as $y => $row) {
    $row_len = strlen($row);
    for ($x = 0; $x < $row_len; $x++) {
        if ($row[$x] === '1') {
            $px = ($x + $quiet) * $cell;
            $py = ($y + $quiet) * $cell;
            echo '<rect x="' . $px . '" y="' . $py
                . '" width="' . $cell . '" height="' . $cell . '" fill="black"/>';
        }
    }
}
echo '</svg>';
