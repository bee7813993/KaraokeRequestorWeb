<?php
/*
 * repair_config.php - config.ini 修復スクリプト
 * secret_display_text の不正な値（括弧等の特殊文字）によって
 * parse_ini_file が失敗する状態を修復します。
 * 実行後はこのファイルを削除してください。
 */

$configfile = 'config.ini';

if (!file_exists($configfile)) {
    echo '<p>config.ini が見つかりません。設定ファイルが存在するか確認してください。</p>';
    exit;
}

// まず現在の状態を確認
$test = parse_ini_file($configfile);
if (is_array($test)) {
    echo '<p style="color:green;">✔ config.ini は正常に読み込めます。修復は不要です。</p>';
    exit;
}

// 行ごとに読み込んで問題のある値を修正
$lines = file($configfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    echo '<p style="color:red;">config.ini を読み込めませんでした。</p>';
    exit;
}

$fixed = [];
foreach ($lines as $line) {
    $trimmed = trim($line);

    // コメント・セクションはそのまま
    if ($trimmed === '' || $trimmed[0] === ';' || $trimmed[0] === '#' || $trimmed[0] === '[') {
        $fixed[] = $line;
        continue;
    }

    // key=value を分解
    $eqpos = strpos($trimmed, '=');
    if ($eqpos === false) {
        $fixed[] = $line;
        continue;
    }

    $key   = trim(substr($trimmed, 0, $eqpos));
    $value = substr($trimmed, $eqpos + 1);

    // secret_display_text が未エンコードで書き込まれていた場合、URL エンコードして修正
    if ($key === 'secret_display_text') {
        // すでに %xx 形式ならそのまま、そうでなければエンコード
        $decoded = urldecode($value);
        $fixed[] = $key . '=' . urlencode($decoded);
        continue;
    }

    $fixed[] = $line;
}

$content = implode("\n", $fixed) . "\n";
if (file_put_contents($configfile, $content) === false) {
    echo '<p style="color:red;">config.ini への書き込みに失敗しました。ファイルのパーミッションを確認してください。</p>';
    exit;
}

// 修復後に再確認
$retest = parse_ini_file($configfile);
if (is_array($retest)) {
    echo '<p style="color:green; font-size:1.2em;">✔ config.ini を修復しました。</p>';
    echo '<p>このファイル (repair_config.php) を削除してから通常のページを開いてください。</p>';
} else {
    echo '<p style="color:red;">修復しましたが、まだ問題があります。config.ini を手動で確認してください。</p>';
    echo '<p>config.ini の内容を確認するか、ファイルを削除して init.php から再設定してください。</p>';
}
