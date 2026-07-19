<?php
/**
 * function_requestlist_json.php
 *
 * 予約一覧の JSON データ構築。
 * requestlist_swipe_json.php (素の JSON) と /api/requests.php (エンベロープ版) で共用する。
 */

/**
 * 予約一覧データを構築する。
 *
 * @param PDO   $db
 * @param array $config_ini
 * @param int   $limit  取得件数 (0 なら全件)
 * @param int   $offset 取得開始位置
 * @return array ['items'=>array, 'total'=>int, 'has_more'=>bool,
 *                'remaining_count'=>int, 'remaining_seconds'=>int]
 * @throws PDOException DB エラー時
 */
function build_requestlist_data($db, $config_ini, $limit = 0, $offset = 0)
{
    global $user;

    $total = (int)$db->query("SELECT COUNT(*) FROM requesttable")->fetchColumn();

    $remaining_count = (int)$db->query(
        "SELECT COUNT(*) FROM requesttable WHERE nowplaying IN ('未再生', '1')"
    )->fetchColumn();

    $remaining_seconds = (int)$db->query(
        "SELECT COALESCE(SUM(duration), 0) FROM requesttable WHERE nowplaying IN ('未再生', '1') AND duration > 0"
    )->fetchColumn();

    // clientip / clientua は所有者判定 (シークレットのマスク除外) にのみ使い、出力には含めない
    $sql = "SELECT id, songfile, fullpath, singer, comment, kind, reqorder, nowplaying, secret, loop, pause, track, keychange, audiodelay, duration, volume, song_name, lister_artist, lister_work, lister_op_ed, lister_comment, clientip, clientua FROM requesttable ORDER BY reqorder DESC";
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

    // シークレット予約のマスク除外判定用 (本人と管理者には曲情報を渡してよい)
    $is_admin  = (isset($user) && $user === 'admin');
    $myname    = function_exists('returnusername_self') ? returnusername_self() : '';
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $remote_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $items = [];
    foreach ($rows as $idx => $row) {
        $songfile     = $row['songfile'];
        $display_name = $songfile;
        $nowplaying_val = !empty($row['nowplaying']) ? $row['nowplaying'] : '1';
        $is_hidden_secret = (!empty($row['secret']) && $row['secret'] == 1
            && ($nowplaying_val === '未再生' || $nowplaying_val === '1'));
        if ($is_hidden_secret) {
            $display_name = urldecode($config_ini['secret_display_text'] ?? urlencode('ヒ・ミ・ツ♪(シークレットリクエスト)'));
        }
        // 未再生シークレットは display_name だけでなく曲情報そのものを非所有者へ渡さない
        // (song_name があるとカードにタイトルが出てしまう漏えいの修正)。
        // 所有者判定は既存の流儀に合わせて「リクエスト端末 (clientip+clientua) が同じ」
        // または「登録者名が自分 (returnusername_self) と同じ」
        $is_owner = (($row['clientip'] ?? '') !== '' && ($row['clientip'] ?? '') === $remote_ip
                     && ($row['clientua'] ?? '') === $remote_ua)
                 || (($row['singer'] ?? '') !== '' && ($row['singer'] ?? '') === $myname);
        $mask = ($is_hidden_secret && !$is_owner && !$is_admin);
        $items[] = [
            'id'           => (int)$row['id'],
            'reqorder'     => (int)$row['reqorder'],
            'songfile'     => $mask ? $display_name : $songfile,
            'display_name' => $display_name,
            'song_name'     => $mask ? '' : ($row['song_name']     ?? ''),
            'lister_artist' => $mask ? '' : ($row['lister_artist'] ?? ''),
            'lister_work'   => $mask ? '' : ($row['lister_work']   ?? ''),
            'lister_op_ed'  => $mask ? '' : ($row['lister_op_ed']  ?? ''),
            'lister_comment'=> $mask ? '' : ($row['lister_comment']?? ''),
            'singer'        => $row['singer'] ?? '',
            'comment'      => $row['comment'] ?? '',
            'kind'         => $row['kind'] ?? '',
            'nowplaying'   => !empty($row['nowplaying']) ? $row['nowplaying'] : '1',
            // この行の曲情報をマスクして返したか (クライアントは曲情報系 UI の出し分けに使う)
            'masked'       => $mask ? 1 : 0,
            // 予約の変更 (アプリの編集画面) 用: 現在のオプション値一式
            'fullpath'     => $mask ? '' : ($row['fullpath'] ?? ''),
            'secret'       => (int)($row['secret'] ?? 0),
            'loop'         => (int)($row['loop']   ?? 0),
            'pause'        => (int)($row['pause']  ?? 0),
            'track'        => (int)($row['track']      ?? 0),
            'keychange'    => (int)($row['keychange']   ?? 0),
            'audiodelay'   => (int)($row['audiodelay']  ?? 0),
            'duration'     => (int)($row['duration']    ?? 0),
            'volume'       => (isset($row['volume']) && $row['volume'] !== null && $row['volume'] !== '') ? (int)$row['volume'] : -1,
            'position'     => $total - $offset - $idx,
        ];
    }

    $has_more = ($limit > 0) && (($offset + count($items)) < $total);

    return [
        'items'             => $items,
        'total'             => $total,
        'has_more'          => $has_more,
        'remaining_count'   => $remaining_count,
        'remaining_seconds' => $remaining_seconds,
    ];
}
