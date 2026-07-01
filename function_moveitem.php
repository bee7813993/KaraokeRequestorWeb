<?php

function getallrequest_fromdb($db) {
    $sql = "SELECT * FROM requesttable ORDER BY reqorder ASC";
    $select = $db->query($sql);
    $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    return $allrequest;
}

/*
 * リクエスト順番自動調整クラス
 *
 * 仕様:
 *  - 未再生アイテムのみ移動対象（再生中・再生済みより前には入らない）
 *  - 1周目: 入れた順番（ターン[0]末尾に追加）
 *  - 同一人物が2つ入れていても別の人は1周目扱い（ターン[0]末尾）
 *  - 2周目以降: 1つ前の周と同じ順番
 *  - 2周目以降の新規参加者: 未再生先頭へ
 *  - reset_on_pause=true時: 最後の小休止以降を1周目扱いに
 */
class MoveItem {
    public $turnlist = array();
    public $allrequest = array();
    public $allrequest_new = array();
    public $max_reqorder = 0;
    public $db = null;
    private $reset_on_pause = false;

    public function getturnlist($db) {
        $this->db = $db;
        global $config_ini;
        if (array_key_exists('request_automove_reset', $config_ini)) {
            $this->reset_on_pause = ($config_ini['request_automove_reset'] == 1);
        }
        $this->allrequest = getallrequest_fromdb($db);
        $this->allrequest_new = $this->allrequest;
        $this->_rebuild_turnlist();
    }

    private function _rebuild_turnlist() {
        $this->turnlist = array();
        $this->max_reqorder = 0;
        $cur_turn = array();
        $cur_singers = array();
        foreach ($this->allrequest_new as $r) {
            if ($this->max_reqorder < $r['reqorder']) {
                $this->max_reqorder = $r['reqorder'];
            }
            if (in_array($r['singer'], $cur_singers)) {
                $this->turnlist[] = $cur_turn;
                $cur_turn = array();
                $cur_singers = array();
            }
            $cur_turn[] = $r;
            $cur_singers[] = $r['singer'];
        }
        if (!empty($cur_turn)) {
            $this->turnlist[] = $cur_turn;
        }
    }

    public function get_new_reqorder($newid) {
        $this->allrequest = getallrequest_fromdb($this->db);
        $this->allrequest_new = $this->allrequest;

        $newsinger = null;
        $newkind = null;
        foreach ($this->allrequest as $r) {
            if ($r['id'] == $newid) {
                $newsinger = $r['singer'];
                $newkind = $r['kind'];
                break;
            }
        }
        if ($newsinger === null) return false;

        // 再生済み・再生中の最大reqorder（この位置より前には挿入不可）
        $played_max = 0;
        foreach ($this->allrequest as $r) {
            if ($r['id'] == $newid) continue;
            if ($r['nowplaying'] !== '未再生') {
                $played_max = max($played_max, $r['reqorder']);
            }
        }

        // 小休止は常に未再生の末尾へ
        if ($newkind === '小休止') {
            $last_req = $played_max;
            foreach ($this->allrequest as $r) {
                if ($r['id'] == $newid) continue;
                if ($r['nowplaying'] === '未再生') {
                    $last_req = max($last_req, $r['reqorder']);
                }
            }
            return $last_req + 1;
        }

        // ⑬ fix: 再生状態を問わず最後の小休止のreqorderを起算点にする
        $memory_start = 0;
        if ($this->reset_on_pause) {
            foreach ($this->allrequest as $r) {
                if ($r['id'] == $newid) continue;
                if ($r['kind'] === '小休止') {
                    $memory_start = max($memory_start, $r['reqorder']);
                }
            }
        }

        $insert_floor = max($played_max, $memory_start);

        // ⑫ fix: 再生済も含む履歴から周を構築（memory_startより後、新アイテム・小休止除外）
        $history = array();
        foreach ($this->allrequest as $r) {
            if ($r['id'] == $newid) continue;
            if ($r['reqorder'] <= $memory_start) continue;
            if ($r['kind'] === '小休止') continue;
            $history[] = $r;
        }

        if (empty($history)) {
            return $insert_floor + 1;
        }

        // historyからラウンド構築（同じ歌い手が再登場 → 次のラウンド開始）
        $rounds = array();
        $cur_round = array();
        $cur_singers = array();
        foreach ($history as $r) {
            if (in_array($r['singer'], $cur_singers)) {
                $rounds[] = $cur_round;
                $cur_round = array();
                $cur_singers = array();
            }
            $cur_round[] = $r;
            $cur_singers[] = $r['singer'];
        }
        if (!empty($cur_round)) {
            $rounds[] = $cur_round;
        }

// 挿入ラウンド: 歌い手が最後に登場したラウンドの次
// 初回参加は last=-1+1=0、途中参加者も含め
// 最後の出現ラウンド+1を正しく参照する
$last_singer_idx = -1;
foreach ($rounds as $idx => $round) {
    foreach ($round as $r) {
        if ($r['singer'] === $newsinger) {
            $last_singer_idx = $idx; break;
        }
    }
}
$target_idx = $last_singer_idx + 1;

        // 全ラウンドに既に存在 → 末尾に追加
        if ($target_idx >= count($rounds)) {
            return $this->_end_of_unplayed($history, $insert_floor) + 1;
        }

        $target_round = $rounds[$target_idx];

        if ($target_idx == 0) {
            // 新歌い手（どのラウンドにも存在しない）
            if (!$this->reset_on_pause && $this->_turn_has_veteran($rounds[0])) {
                // rule5: 2周目以降の新規参加者 → 未再生先頭へ
                return $this->_first_unplayed_reqorder_after($history, $insert_floor);
            }
            // rule2/3: 1周目 → ラウンド[0]末尾へ
            return $this->_end_of_round_unplayed($target_round, $insert_floor) + 1;
        }

        // target_idx >= 1: rule4 - 1つ前のラウンドと同じ順番
        $prev_round = $rounds[$target_idx - 1];

        // 前ラウンドで自分より後にいる歌い手リスト
        $after_singers = array();
        $found_self = false;
        foreach ($prev_round as $r) {
            if ($r['singer'] === $newsinger) {
                $found_self = true;
                continue;
            }
            if ($found_self) {
                $after_singers[] = $r['singer'];
            }
        }

        // 挿入ラウンドの未再生アイテムで、insert_floor後かつ最初に「後の人」が見つかった位置に挿入
        foreach ($target_round as $r) {
            if ($r['reqorder'] <= $insert_floor) continue;
            if ($r['nowplaying'] !== '未再生') continue;
            if (in_array($r['singer'], $after_singers)) {
                return $r['reqorder'];
            }
        }

        // 「後の人」が見つからなければラウンド末尾
        return $this->_end_of_round_unplayed($target_round, $insert_floor) + 1;
    }

    // ラウンド内の歌い手が再生済み等に存在するか判定
    private function _turn_has_veteran($turn) {
        foreach ($turn as $t) {
            foreach ($this->allrequest as $r) {
                if ($r['singer'] === $t['singer'] && $r['nowplaying'] !== '未再生') {
                    return true;
                }
            }
        }
        return false;
    }

    // ラウンド内のinsert_floor後の最後の未再生reqorderを返す（未再生がなければ最後の任意アイテム）
    private function _end_of_round_unplayed($round, $insert_floor) {
        $last_unplayed = -1;
        $last_any = -1;
        foreach ($round as $r) {
            if ($r['reqorder'] <= $insert_floor) continue;
            if ($r['reqorder'] > $last_any) $last_any = $r['reqorder'];
            if ($r['nowplaying'] === '未再生' && $r['reqorder'] > $last_unplayed) {
                $last_unplayed = $r['reqorder'];
            }
        }
        if ($last_unplayed >= 0) return $last_unplayed;
        if ($last_any >= 0) return $last_any;
        return $insert_floor;
    }

    // history内のinsert_floor後の最後の未再生reqorderを返す
    private function _end_of_unplayed($history, $insert_floor) {
        $last = $insert_floor;
        foreach ($history as $r) {
            if ($r['reqorder'] <= $insert_floor) continue;
            if ($r['nowplaying'] === '未再生') {
                $last = max($last, $r['reqorder']);
            }
        }
        return $last;
    }

    // history内のinsert_floor後の最初の未再生reqorderを返す
    private function _first_unplayed_reqorder_after($history, $insert_floor) {
        foreach ($history as $r) {
            if ($r['reqorder'] <= $insert_floor) continue;
            if ($r['nowplaying'] === '未再生') {
                return $r['reqorder'];
            }
        }
        return $this->_end_of_unplayed($history, $insert_floor) + 1;
    }

    public function insertreqorder($id, $reqorder) {
        // allrequest_newはreqorder昇順ソート済みの前提
        // target_reqorderより小さいreqorderを持つアイテム数 = 新アイテムの前に来るアイテム数
        $items_before = 0;
        foreach ($this->allrequest_new as $r) {
            if ($r['id'] == $id) continue;
            if ($r['reqorder'] < $reqorder) $items_before++;
        }
        $new_position = $items_before + 1;

        // 全アイテムを連番で振り直す（隙間も吸収）
        // 新アイテムはnew_positionに配置し、それ以外は前後に詰める
        $pos = 1;
        for ($i = 0; $i < count($this->allrequest_new); $i++) {
            if ($this->allrequest_new[$i]['id'] == $id) {
                $this->allrequest_new[$i]['reqorder'] = $new_position;
                continue;
            }
            if ($pos == $new_position) $pos++;
            $this->allrequest_new[$i]['reqorder'] = $pos;
            $pos++;
        }
    }

    public function save_allrequest($db) {
        $old_orders = array();
        foreach ($this->allrequest as $r) {
            $old_orders[$r['id']] = $r['reqorder'];
        }
        foreach ($this->allrequest_new as $r) {
            $id = (int)$r['id'];
            $new_req = (int)$r['reqorder'];
            if (!array_key_exists($id, $old_orders) || $old_orders[$id] !== $new_req) {
                $db->exec('UPDATE requesttable SET reqorder=' . $new_req . ' WHERE id=' . $id);
            }
        }
    }

    // 互換性のために残す
    public function check_exists_mymember($oneturn, $singer, $id = 'none') {
        foreach ($oneturn as $value) {
            if ($value['id'] == $id) continue;
            if ($value['singer'] === $singer) return true;
        }
        return false;
    }

    public function get_singer_fromid($id) {
        foreach ($this->allrequest_new as $value) {
            if ($value['id'] == $id) return $value['singer'];
        }
        return false;
    }

    public function recountreqorder() {
        $currentreqorder = 1;
        for ($i = 0; $i < count($this->allrequest_new); $i++) {
            $this->allrequest_new[$i]['reqorder'] = $currentreqorder;
            $currentreqorder++;
        }
    }
}

?>
