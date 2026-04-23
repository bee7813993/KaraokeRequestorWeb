<?php
/**
 * MypageUser - マイページ機能の中核クラス
 * Cookie UUID でユーザーを識別し、履歴・後で歌う・お気に入りを管理する
 */
class MypageUser {
    private $db;
    private $userid;

    const COOKIE_NAME    = 'YkariUserID';
    const COOKIE_DAYS    = 365;
    const PAIR_CODE_TTL  = 300; // ペアリングコード有効秒数

    public function __construct($db) {
        $this->db = $db;
        $this->initTables();
        $this->userid = $this->resolveUserId();
    }

    // ---- テーブル初期化 ----

    private function initTables() {
        $sqls = [
            "CREATE TABLE IF NOT EXISTS mypage_user (
                userid      TEXT PRIMARY KEY,
                displayname TEXT DEFAULT '',
                created_at  INTEGER NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS mypage_history (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                userid      TEXT NOT NULL,
                fullpath    TEXT NOT NULL,
                songfile    TEXT NOT NULL,
                kind        TEXT NOT NULL DEFAULT '',
                requested_at INTEGER NOT NULL
            )",
            "CREATE INDEX IF NOT EXISTS idx_mypage_history_user
                ON mypage_history(userid, requested_at)",
            "CREATE TABLE IF NOT EXISTS mypage_later (
                userid      TEXT NOT NULL,
                fullpath    TEXT NOT NULL,
                songfile    TEXT NOT NULL,
                kind        TEXT NOT NULL DEFAULT '',
                added_at    INTEGER NOT NULL,
                PRIMARY KEY(userid, fullpath)
            )",
            "CREATE TABLE IF NOT EXISTS mypage_favorite_song (
                userid      TEXT NOT NULL,
                fullpath    TEXT NOT NULL,
                songfile    TEXT NOT NULL,
                kind        TEXT NOT NULL DEFAULT '',
                added_at    INTEGER NOT NULL,
                PRIMARY KEY(userid, fullpath)
            )",
            "CREATE TABLE IF NOT EXISTS mypage_favorite_keyword (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                userid      TEXT NOT NULL,
                keyword     TEXT NOT NULL,
                search_type TEXT NOT NULL DEFAULT '',
                search_params TEXT NOT NULL DEFAULT '',
                added_at    INTEGER NOT NULL,
                UNIQUE(userid, keyword, search_type)
            )",
            "CREATE TABLE IF NOT EXISTS mypage_pair_code (
                code        TEXT PRIMARY KEY,
                userid      TEXT NOT NULL,
                expires_at  INTEGER NOT NULL
            )",
        ];
        foreach ($sqls as $sql) {
            $this->db->exec($sql);
        }
    }

    // ---- ユーザーID管理 ----

    private function resolveUserId() {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $uid = $_COOKIE[self::COOKIE_NAME];
            if ($this->isValidUuid($uid)) {
                $this->upsertUser($uid);
                return $uid;
            }
        }
        $uid = $this->generateUuid();
        setcookie(self::COOKIE_NAME, $uid, time() + 86400 * self::COOKIE_DAYS, '/');
        $_COOKIE[self::COOKIE_NAME] = $uid;
        $this->upsertUser($uid);
        return $uid;
    }

    private function upsertUser($uid) {
        $sql = "INSERT OR IGNORE INTO mypage_user (userid, displayname, created_at)
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$uid, '', time()]);

        // YkariUsername Cookie があれば displayname が空のときだけ同期
        if (!empty($_COOKIE['YkariUsername'])) {
            $name = mb_substr($_COOKIE['YkariUsername'], 0, 64);
            $sql2 = "UPDATE mypage_user SET displayname = ?
                     WHERE userid = ? AND (displayname = '' OR displayname IS NULL)";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([$name, $uid]);
        }
    }

    private function generateUuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function isValidUuid($str) {
        return (bool)preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $str
        );
    }

    public function getUserId() {
        return $this->userid;
    }

    public function getDisplayName() {
        $stmt = $this->db->prepare(
            "SELECT displayname FROM mypage_user WHERE userid = ?"
        );
        $stmt->execute([$this->userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['displayname'] : '';
    }

    public function updateDisplayName($name) {
        $name = mb_substr(trim($name), 0, 64);
        $stmt = $this->db->prepare(
            "UPDATE mypage_user SET displayname = ? WHERE userid = ?"
        );
        $stmt->execute([$name, $this->userid]);
        // YkariUsername Cookie も同期
        setcookie('YkariUsername', $name, time() + 86400 * 60, '/');
    }

    // ---- 選曲履歴 ----

    public function addHistory($fullpath, $songfile, $kind) {
        $stmt = $this->db->prepare(
            "INSERT INTO mypage_history (userid, fullpath, songfile, kind, requested_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$this->userid, $fullpath, $songfile, $kind, time()]);
    }

    /**
     * @param string $sort  'date'|'count'
     * @param string $order 'desc'|'asc'
     */
    public function getHistory($sort = 'date', $order = 'desc', $limit = 200) {
        $order = ($order === 'asc') ? 'ASC' : 'DESC';
        if ($sort === 'count') {
            $orderby = "times $order, last_requested_at DESC";
        } else {
            $orderby = "last_requested_at $order";
        }
        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind,
                    COUNT(*) AS times,
                    MAX(requested_at) AS last_requested_at,
                    MIN(id) AS first_id
             FROM mypage_history
             WHERE userid = ?
             GROUP BY fullpath
             ORDER BY $orderby
             LIMIT ?"
        );
        $stmt->execute([$this->userid, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteHistoryByFullpath($fullpath) {
        $stmt = $this->db->prepare(
            "DELETE FROM mypage_history WHERE userid = ? AND fullpath = ?"
        );
        $stmt->execute([$this->userid, $fullpath]);
    }

    // ---- 後で歌う ----

    public function addLater($fullpath, $songfile, $kind) {
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO mypage_later (userid, fullpath, songfile, kind, added_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$this->userid, $fullpath, $songfile, $kind, time()]);
    }

    public function removeLater($fullpath) {
        $stmt = $this->db->prepare(
            "DELETE FROM mypage_later WHERE userid = ? AND fullpath = ?"
        );
        $stmt->execute([$this->userid, $fullpath]);
    }

    public function getLaterList() {
        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind, added_at
             FROM mypage_later WHERE userid = ?
             ORDER BY added_at DESC"
        );
        $stmt->execute([$this->userid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isInLater($fullpath) {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM mypage_later WHERE userid = ? AND fullpath = ?"
        );
        $stmt->execute([$this->userid, $fullpath]);
        return (bool)$stmt->fetch();
    }

    // ---- お気に入り曲 ----

    public function addFavoriteSong($fullpath, $songfile, $kind) {
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO mypage_favorite_song (userid, fullpath, songfile, kind, added_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$this->userid, $fullpath, $songfile, $kind, time()]);
    }

    public function removeFavoriteSong($fullpath) {
        $stmt = $this->db->prepare(
            "DELETE FROM mypage_favorite_song WHERE userid = ? AND fullpath = ?"
        );
        $stmt->execute([$this->userid, $fullpath]);
    }

    public function getFavoriteSongs() {
        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind, added_at
             FROM mypage_favorite_song WHERE userid = ?
             ORDER BY added_at DESC"
        );
        $stmt->execute([$this->userid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isInFavoriteSong($fullpath) {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM mypage_favorite_song WHERE userid = ? AND fullpath = ?"
        );
        $stmt->execute([$this->userid, $fullpath]);
        return (bool)$stmt->fetch();
    }

    // ---- お気に入り検索ワード ----

    public function addFavoriteKeyword($keyword, $search_type, $search_params = '') {
        $keyword = mb_substr(trim($keyword), 0, 256);
        if (empty($keyword)) return false;
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO mypage_favorite_keyword
             (userid, keyword, search_type, search_params, added_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$this->userid, $keyword, $search_type, $search_params, time()]);
        return true;
    }

    public function removeFavoriteKeyword($id) {
        $stmt = $this->db->prepare(
            "DELETE FROM mypage_favorite_keyword WHERE id = ? AND userid = ?"
        );
        $stmt->execute([(int)$id, $this->userid]);
    }

    public function getFavoriteKeywords() {
        $stmt = $this->db->prepare(
            "SELECT id, keyword, search_type, search_params, added_at
             FROM mypage_favorite_keyword WHERE userid = ?
             ORDER BY added_at DESC"
        );
        $stmt->execute([$this->userid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---- デバイスリンク (ペアリングコード) ----

    public function generatePairingCode() {
        // 期限切れコードを掃除
        $this->db->exec(
            "DELETE FROM mypage_pair_code WHERE expires_at < " . time()
        );
        // 6文字英数大文字コード
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while ($this->pairingCodeExists($code));

        $stmt = $this->db->prepare(
            "INSERT INTO mypage_pair_code (code, userid, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$code, $this->userid, time() + self::PAIR_CODE_TTL]);
        return $code;
    }

    private function pairingCodeExists($code) {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM mypage_pair_code WHERE code = ?"
        );
        $stmt->execute([$code]);
        return (bool)$stmt->fetch();
    }

    /**
     * コードを検証して自分のCookieを相手のuseridに切り替える
     * @return string|false 成功時は元のuserid、失敗時はfalse
     */
    public function applyPairingCode($code) {
        $code = strtoupper(trim($code));
        $stmt = $this->db->prepare(
            "SELECT userid FROM mypage_pair_code
             WHERE code = ? AND expires_at >= ?"
        );
        $stmt->execute([$code, time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $target_userid = $row['userid'];
        if ($target_userid === $this->userid) return false; // 自分自身には不可

        // コードを使用済みにする
        $this->db->prepare("DELETE FROM mypage_pair_code WHERE code = ?")
                 ->execute([$code]);

        // CookieをtargetのuseridにセットしてIDを切り替える
        setcookie(self::COOKIE_NAME, $target_userid, time() + 86400 * self::COOKIE_DAYS, '/');
        $this->userid = $target_userid;
        return $target_userid;
    }

    // ---- ファイル存在確認 + フォールバック検索 ----

    /**
     * ファイルの存在を確認し、見つからない場合は同じファイル名を
     * Everything HTTP API で検索して別フォルダのパスを返す。
     *
     * @return array [
     *   'status'   => 'ok'|'relocated'|'notfound',
     *   'fullpath' => string,   // 有効なパス（relocatedの場合は新パス）
     *   'songfile' => string,
     * ]
     */
    public static function checkFileStatus($fullpath, $songfile) {
        if (file_exists($fullpath)) {
            return ['status' => 'ok', 'fullpath' => $fullpath, 'songfile' => $songfile];
        }

        // 元のファイルが見つからない場合、同名ファイルを Everything で検索
        $basename = basename($fullpath);
        if (!empty($basename)) {
            $everythinghost = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'localhost';
            // IPv6 アドレスはブラケットで囲む
            if (substr_count($everythinghost, ':') > 0 && strpos($everythinghost, '[') === false) {
                $everythinghost = '[' . $everythinghost . ']';
            }
            $jsonurl = 'http://' . $everythinghost . ':81/?search='
                     . urlencode($basename) . '&json=1&count=1&path=1&path_column=3&size_column=4';
            $json = @file_get_contents($jsonurl);
            if ($json !== false) {
                $data = json_decode($json, true);
                if (!empty($data['results'][0]['name'])) {
                    $newpath = $data['results'][0]['path'] . '\\' . $data['results'][0]['name'];
                    return ['status' => 'relocated', 'fullpath' => $newpath, 'songfile' => $songfile];
                }
            }
        }

        return ['status' => 'notfound', 'fullpath' => $fullpath, 'songfile' => $songfile];
    }

    /**
     * リクエスト確認画面へのリンクURLを生成する
     */
    public static function makeRequestConfirmUrl($fullpath, $songfile, $kind = '') {
        if (!empty($kind) && $kind === 'カラオケ配信') {
            return 'request_confirm.php?shop_karaoke=1&filename=' . urlencode($songfile);
        }
        $filename = basename($fullpath);
        return 'request_confirm.php?filename=' . urlencode($filename)
             . '&fullpath=' . urlencode($fullpath);
    }

    /**
     * 曲名で再検索するURLを生成する (ファイルが見つからない場合のフォールバック)
     * anyword 検索 (search_listerdb_filelist) を使う
     */
    public static function makeSearchFallbackUrl($songfile) {
        // 拡張子を除いた表示名をキーワードに
        $keyword = pathinfo($songfile, PATHINFO_FILENAME);
        return 'search_listerdb_filelist.php?anyword=' . urlencode($keyword);
    }
}
