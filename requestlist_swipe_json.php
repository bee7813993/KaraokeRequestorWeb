<?php
require_once 'commonfunc.php';
require_once 'easyauth_class.php';
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();

header('Content-Type: application/json; charset=utf-8');

$limit  = isset($_GET['limit'])  && ctype_digit($_GET['limit'])  ? (int)$_GET['limit']  : 0;
$offset = isset($_GET['offset']) && ctype_digit($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $total = (int)$db->query("SELECT COUNT(*) FROM requesttable")->fetchColumn();

    $remaining_count = (int)$db->query(
        "SELECT COUNT(*) FROM requesttable WHERE nowplaying IN ('未再生', '1')"
    )->fetchColumn();

    $remaining_seconds = (int)$db->query(
        "SELECT COALESCE(SUM(duration), 0) FROM requesttable WHERE nowplaying IN ('未再生', '1') AND duration > 0"
    )->fetchColumn();

    $sql = "SELECT id, songfile, singer, comment, kind, reqorder, nowplaying, secret, track, keychange, audiodelay, duration FROM requesttable ORDER BY reqorder DESC";
    if ($limit > 0) {
        $stmt = $db->prepare($sql . " LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare($sql);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$items = [];
foreach ($rows as $idx => $row) {
    $songfile     = $row['songfile'];
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
        'nowplaying'   => !empty($row['nowplaying']) ? $row['nowplaying'] : '1',
        'track'        => (int)($row['track']      ?? 0),
        'keychange'    => (int)($row['keychange']   ?? 0),
        'audiodelay'   => (int)($row['audiodelay']  ?? 0),
        'duration'     => (int)($row['duration']    ?? 0),
        'position'     => $total - $offset - $idx,
    ];
}

$has_more = ($limit > 0) && (($offset + count($items)) < $total);

echo json_encode([
    'items'             => $items,
    'total'             => $total,
    'has_more'          => $has_more,
    'remaining_count'   => $remaining_count,
    'remaining_seconds' => $remaining_seconds,
], JSON_UNESCAPED_UNICODE);
