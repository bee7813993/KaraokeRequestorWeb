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
                icon_path   TEXT DEFAULT '',
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
                UNIQUE(userid, keyword, search_type, search_params)
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
        // Migrate existing tables: add icon_path if missing
        try {
            $this->db->exec("ALTER TABLE mypage_user ADD COLUMN icon_path TEXT DEFAULT ''");
        } catch (Exception $e) {
            // Column already exists; ignore
        }
        // Migrate: expand UNIQUE constraint on mypage_favorite_keyword to include search_params
        $row = $this->db->query(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name='mypage_favorite_keyword'"
        )->fetchColumn();
        if ($row !== false && strpos($row, 'UNIQUE(userid, keyword, search_type, search_params)') === false) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS mypage_favorite_keyword_v2 (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    userid      TEXT NOT NULL,
                    keyword     TEXT NOT NULL,
                    search_type TEXT NOT NULL DEFAULT '',
                    search_params TEXT NOT NULL DEFAULT '',
                    added_at    INTEGER NOT NULL,
                    UNIQUE(userid, keyword, search_type, search_params)
                )
            ");
            $this->db->exec("
                INSERT OR IGNORE INTO mypage_favorite_keyword_v2
                    (id, userid, keyword, search_type, search_params, added_at)
                SELECT id, userid, keyword, search_type, search_params, added_at
                FROM mypage_favorite_keyword
            ");
            $this->db->exec("DROP TABLE mypage_favorite_keyword");
            $this->db->exec("ALTER TABLE mypage_favorite_keyword_v2 RENAME TO mypage_favorite_keyword");
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

    // ---- アイコン ----

    public function getIconPath() {
        $stmt = $this->db->prepare(
            "SELECT icon_path FROM mypage_user WHERE userid = ?"
        );
        $stmt->execute([$this->userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['icon_path']) && @file_exists($row['icon_path'])) {
            return $row['icon_path'];
        }
        return 'images/mypage_icon_default.svg';
    }

    public function updateIconPath($file_array) {
        if (empty($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        $mime = mime_content_type($file_array['tmp_name']);
        if (!in_array($mime, $allowed_mime)) {
            return false;
        }
        $ext_map = [
            'image/jpeg'   => 'jpg',
            'image/png'    => 'png',
            'image/gif'    => 'gif',
            'image/svg+xml'=> 'svg',
            'image/webp'   => 'webp',
        ];
        $ext = $ext_map[$mime] ?? 'jpg';

        $dir = 'images/mypage_icons/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // 既存アイコンを削除
        foreach (glob($dir . $this->userid . '.*') as $old) {
            @unlink($old);
        }
        $dest = $dir . $this->userid . '.' . $ext;
        if (!move_uploaded_file($file_array['tmp_name'], $dest)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE mypage_user SET icon_path = ? WHERE userid = ?"
        );
        $stmt->execute([$dest, $this->userid]);
        setcookie('YkariUserIcon', $dest, time() + 86400 * 365, '/');
        $_COOKIE['YkariUserIcon'] = $dest;
        return $dest;
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

    // ---- インポート / エクスポート ----

    public function exportData() {
        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind, requested_at
             FROM mypage_history WHERE userid = ? ORDER BY requested_at ASC"
        );
        $stmt->execute([$this->userid]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind, added_at
             FROM mypage_later WHERE userid = ? ORDER BY added_at ASC"
        );
        $stmt->execute([$this->userid]);
        $later = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT fullpath, songfile, kind, added_at
             FROM mypage_favorite_song WHERE userid = ? ORDER BY added_at ASC"
        );
        $stmt->execute([$this->userid]);
        $fav_songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT keyword, search_type, search_params, added_at
             FROM mypage_favorite_keyword WHERE userid = ? ORDER BY added_at ASC"
        );
        $stmt->execute([$this->userid]);
        $fav_keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'version'           => 1,
            'exported_at'       => time(),
            'displayname'       => $this->getDisplayName(),
            'history'           => $history,
            'later'             => $later,
            'favorite_songs'    => $fav_songs,
            'favorite_keywords' => $fav_keywords,
        ];
    }

    /**
     * @param array $data     exportData() と同じ構造のデータ
     * @param bool  $overwrite true=既存データを削除してから挿入、false=追加（重複はスキップ）
     * @return array ['ok'=>bool, 'counts'=>array|null, 'error'=>string|null]
     */
    public function importData(array $data, bool $overwrite = false) {
        if (empty($data['version']) || (int)$data['version'] !== 1) {
            return ['ok' => false, 'error' => '対応していないフォーマットです（version フィールドを確認してください）。'];
        }
        $counts = ['history' => 0, 'later' => 0, 'favorite_songs' => 0, 'favorite_keywords' => 0];
        $this->db->beginTransaction();
        try {
            if ($overwrite) {
                foreach (['mypage_history', 'mypage_later', 'mypage_favorite_song', 'mypage_favorite_keyword'] as $tbl) {
                    $this->db->prepare("DELETE FROM $tbl WHERE userid=?")->execute([$this->userid]);
                }
            }

            // 選曲履歴
            if (!empty($data['history']) && is_array($data['history'])) {
                $ins = $this->db->prepare(
                    "INSERT INTO mypage_history (userid, fullpath, songfile, kind, requested_at) VALUES (?, ?, ?, ?, ?)"
                );
                $chk = $this->db->prepare(
                    "SELECT 1 FROM mypage_history WHERE userid=? AND fullpath=? AND requested_at=? LIMIT 1"
                );
                foreach ($data['history'] as $row) {
                    if (empty($row['fullpath'])) continue;
                    if (!$overwrite) {
                        $chk->execute([$this->userid, $row['fullpath'], (int)($row['requested_at'] ?? 0)]);
                        if ($chk->fetch()) continue;
                    }
                    $ins->execute([$this->userid, $row['fullpath'], $row['songfile'] ?? '', $row['kind'] ?? '', (int)($row['requested_at'] ?? time())]);
                    $counts['history']++;
                }
            }

            // 後で歌う
            if (!empty($data['later']) && is_array($data['later'])) {
                $ins = $this->db->prepare(
                    "INSERT OR IGNORE INTO mypage_later (userid, fullpath, songfile, kind, added_at) VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($data['later'] as $row) {
                    if (empty($row['fullpath'])) continue;
                    $ins->execute([$this->userid, $row['fullpath'], $row['songfile'] ?? '', $row['kind'] ?? '', (int)($row['added_at'] ?? time())]);
                    $counts['later']++;
                }
            }

            // お気に入り曲
            if (!empty($data['favorite_songs']) && is_array($data['favorite_songs'])) {
                $ins = $this->db->prepare(
                    "INSERT OR IGNORE INTO mypage_favorite_song (userid, fullpath, songfile, kind, added_at) VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($data['favorite_songs'] as $row) {
                    if (empty($row['fullpath'])) continue;
                    $ins->execute([$this->userid, $row['fullpath'], $row['songfile'] ?? '', $row['kind'] ?? '', (int)($row['added_at'] ?? time())]);
                    $counts['favorite_songs']++;
                }
            }

            // お気に入り検索ワード
            if (!empty($data['favorite_keywords']) && is_array($data['favorite_keywords'])) {
                $ins = $this->db->prepare(
                    "INSERT OR IGNORE INTO mypage_favorite_keyword (userid, keyword, search_type, search_params, added_at) VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($data['favorite_keywords'] as $row) {
                    if (empty($row['keyword'])) continue;
                    $ins->execute([$this->userid, mb_substr(trim($row['keyword']), 0, 256), $row['search_type'] ?? '', $row['search_params'] ?? '', (int)($row['added_at'] ?? time())]);
                    $counts['favorite_keywords']++;
                }
            }

            // 表示名（上書きモードのみ）
            if ($overwrite && !empty($data['displayname'])) {
                $this->updateDisplayName($data['displayname']);
            }

            $this->db->commit();
            return ['ok' => true, 'counts' => $counts];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
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
        // ListerDB接続を先に準備（song_name取得とrelocated検索に共用）
        $listerdb = null;
        global $config_ini;
        if (!empty($config_ini['listerDBPATH'])) {
            $decoded = urldecode($config_ini['listerDBPATH']);
            $win_dbpath = mb_convert_encoding($decoded, 'SJIS-win', 'UTF-8');
            if (function_exists('file_exist_check_japanese_cf')) {
                $db_ok = @file_exist_check_japanese_cf($win_dbpath);
            } else {
                $db_ok = @file_exists($decoded);
            }
            if ($db_ok !== false) {
                try {
                    require_once 'function_search_listerdb.php';
                    $lister = new ListerDB();
                    $lister->listerdbfile = $decoded;
                    $listerdb = $lister->initdb();
                } catch (Exception $e) {
                    $listerdb = null;
                }
            }
        }

        // ListerDB から song_name を exact match で取得
        $song_name = '';
        if ($listerdb) {
            try {
                $stmt = $listerdb->prepare(
                    "SELECT song_name FROM t_found WHERE found_path = ? LIMIT 1"
                );
                $stmt->execute([$fullpath]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $song_name = $row['song_name'];
            } catch (Exception $e) {}
        }

        // ファイル存在確認 (SJISパスにも対応)
        $winfullpath = mb_convert_encoding($fullpath, 'SJIS-win', 'UTF-8');
        if (function_exists('file_exist_check_japanese_cf')) {
            $found = @file_exist_check_japanese_cf($winfullpath);
        } else {
            $found = @file_exists($winfullpath) ? $winfullpath : (@file_exists($fullpath) ? $fullpath : false);
        }
        if ($found !== false) {
            return ['status' => 'ok', 'fullpath' => $fullpath, 'songfile' => $songfile, 'song_name' => $song_name];
        }

        // 同じファイル名を ListerDB で検索（別フォルダ対応）
        $basename = basename($fullpath);
        if (!empty($basename) && $listerdb) {
            try {
                $stmt = $listerdb->prepare(
                    "SELECT found_path, song_name FROM t_found WHERE found_path LIKE ? LIMIT 1"
                );
                $stmt->execute(['%' . $basename . '%']);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return ['status' => 'relocated', 'fullpath' => $row['found_path'], 'songfile' => $songfile, 'song_name' => $row['song_name']];
                }
            } catch (Exception $e) {}
        }

        return ['status' => 'notfound', 'fullpath' => $fullpath, 'songfile' => $songfile, 'song_name' => ''];
    }

    /**
     * リクエスト確認画面へのリンクURLを生成する
     */
    public static function makeRequestConfirmUrl($fullpath, $songfile, $kind = '') {
        if (!empty($kind) && $kind === 'カラオケ配信') {
            return 'request_confirm.php?shop_karaoke=1&filename=' . urlencode($songfile);
        }
        // Windows パス対応の basename (Linuxでは basename() が \ を認識しないため)
        $filename = function_exists('basename_jp') ? basename_jp($fullpath) : basename(str_replace('\\', '/', $fullpath));
        return 'request_confirm.php?filename=' . urlencode($filename)
             . '&fullpath=' . urlencode($fullpath);
    }

    /**
     * 曲名で再検索するURLを生成する (ファイルが見つからない場合のフォールバック)
     * anyword 検索 (search_listerdb_filelist) を使う
     */
    public static function makeSearchFallbackUrl($songfile) {
        // Windows パス対応: パス区切りを除いてファイル名のみ取り出し、拡張子も除く
        $basename = function_exists('basename_jp') ? basename_jp($songfile) : basename(str_replace('\\', '/', $songfile));
        $keyword  = pathinfo($basename, PATHINFO_FILENAME);
        return 'search_listerdb_filelist.php?anyword=' . urlencode($keyword);
    }
}
