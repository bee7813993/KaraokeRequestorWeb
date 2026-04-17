<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $db->prepare(
        "SELECT id, songfile, singer, comment, kind, reqorder, nowplaying, secret " .
        "FROM requesttable ORDER BY reqorder DESC"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$items = [];
foreach ($rows as $row) {
    $songfile = $row['songfile'];
    $display_name = $songfile;
    if (!empty($row['secret']) && $row['secret'] == 1) {
        $display_name = mb_substr($songfile, 0, 1) . '***';
    }
    $items[] = [
        'id'           => (int)$row['id'],
        'reqorder'     => (int)$row['reqorder'],
        'songfile'     => $songfile,
        'display_name' => $display_name,
        'singer'       => $row['singer'] ?? '',
        'comment'      => $row['comment'] ?? '',
        'kind'         => $row['kind'] ?? '',
        'nowplaying'   => $row['nowplaying'] ?? '1',
    ];
}

echo json_encode($items, JSON_UNESCAPED_UNICODE);
