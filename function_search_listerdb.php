<?php

class ListerDB {
    public $listerdbfile = 'list\List.sqlite3';
    public $ListerDBFD;
    
    public function initdb(){
        // DB初期化
        try {
            $this->ListerDBFD = new PDO('sqlite:'. $this->listerdbfile);
        }catch (PDOException $e) {
            print 'DB初期化エラー : '.$e->getMessage() . "<br/>";
            return false;
        }
        return $this->ListerDBFD;
    }
    
    public function closedb(){
        $this->ListerDBFD = null;
    }
    
    public function select($sql) {
        if( !$this->ListerDBFD ){
           print 'DB未初期化 : '.$this->ListerDBFD . "<br/>";
        }
        $select = $this->ListerDBFD->query($sql);
        if($select === false){
            print_r($this->ListerDBFD->errorInfo());
            print $sql;
            return false;
        }
        $alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        if($alldbdata === false){
            print_r($this->ListerDBFD->errorInfo());
            print $sql;
            return $alldbdata;
        }
        return $alldbdata;
    }
    public function startyklistercmd() {
        $yukalisterpath= 'YukaLister\YukaLister.exe';
        if (file_exist_check_japanese_cf($yukalisterpath) ){
            $cmd = 'start "" '.$yukalisterpath;
            $fp = popen($cmd,'r');
            pclose($fp);
        }
    }
    public function stopyklistercmd() {
        $yukalistercmd= 'YukaLister.exe';
        $cmd = 'taskkill /im "'.$yukalistercmd.'" -f';
    }

    // Windows Store版ゆかりすたーがインストール済みか確認
    public function isInstalledYkListerStore() {
        $output = [];
        $retval = -1;
        exec('powershell -NoProfile -NonInteractive -Command "if (Get-AppxPackage -Name \'*YukaLister*\' -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }" 2>NUL', $output, $retval);
        return $retval === 0;
    }

    // Windows Store版ゆかりすたーを起動（Task Scheduler経由でユーザーセッションで起動）
    public function startyklistercmd_store() {
        return $this->launchStoreApp('*YukaLister*');
    }

    // Windows Store版ゆっこビュー2がインストール済みか確認
    public function isInstalledYukkoView2() {
        $output = [];
        $retval = -1;
        exec('powershell -NoProfile -NonInteractive -Command "if (Get-AppxPackage -Name \'*YukkoView*\' -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }" 2>NUL', $output, $retval);
        return $retval === 0;
    }

    // Windows Store版ゆっこビュー2を起動（Task Scheduler経由でユーザーセッションで起動）
    public function startYukkoView2cmd() {
        return $this->launchStoreApp('*YukkoView*');
    }

    /**
     * Windows Store アプリを起動する。
     * Apache がユーザーセッションで動作している前提で cmd /c start 経由で非同期起動。
     * @return array ['ok'=>bool, 'msg'=>string]
     */
    private function launchStoreApp($namePattern) {
        if (!function_exists('exec')) {
            return ['ok' => false, 'msg' => 'exec() が無効です (php.ini の disable_functions を確認)'];
        }

        // パッケージファミリー名を取得
        $pfnOut = [];
        $pfnRet = -1;
        exec(
            'powershell -NoProfile -NonInteractive -Command "(Get-AppxPackage -Name \'' . $namePattern . '\' -ErrorAction SilentlyContinue | Select-Object -First 1).PackageFamilyName" 2>&1',
            $pfnOut,
            $pfnRet
        );
        $pfn = trim(implode('', $pfnOut));

        if (empty($pfn)) {
            return ['ok' => false, 'msg' => 'アプリが見つかりません (pattern: ' . $namePattern . ', exit: ' . $pfnRet . ', out: ' . implode('|', $pfnOut) . ')'];
        }

        // Start-Process で非同期起動。
        // popen+pclose は孫プロセスのハンドル継承でブロックするため使わない。
        // exec(PowerShell Start-Process) はPowerShell終了で即リターンする。
        $uri = 'shell:AppsFolder\\' . $pfn . '!App';
        $psCmd = 'powershell -NoProfile -NonInteractive -Command "Start-Process \'' . str_replace("'", "''", $uri) . '\'" 2>NUL';
        $launchOut = [];
        $launchRet = -1;
        exec($psCmd, $launchOut, $launchRet);

        return ['ok' => true, 'msg' => $pfn, 'launch_ret' => $launchRet];
    }
}

/**
 * fullpath から t_found を検索し、requesttable 保存用の連想配列を返す。
 * 見つからない場合は null。
 * キー: song_name, lister_artist, lister_work, lister_op_ed, lister_comment
 * $with_ruby を true にすると読み仮名 (song_ruby, lister_artist_ruby,
 * lister_work_ruby) も含める (曲情報の修正 API 用)。
 */
function listerdb_lookup_songinfo($fullpath, $lister_dbpath, $with_ruby = false) {
    if (empty($fullpath) || !file_exists($lister_dbpath)) return null;
    $lister = new ListerDB();
    $lister->listerdbfile = $lister_dbpath;
    $lfd = $lister->initdb();
    if (!$lfd) return null;

    $columns = 'song_name, song_artist, program_name, song_op_ed, found_comment';
    if ($with_ruby) {
        $columns .= ', song_ruby, found_artist_ruby, tie_up_ruby';
    }
    $row = null;
    foreach ([$fullpath, basename($fullpath)] as $search) {
        $res = $lister->select(
            'SELECT ' . $columns
            . ' FROM t_found WHERE found_path LIKE ' . $lfd->quote('%' . $search . '%')
            . " AND song_name != '' LIMIT 1"
        );
        if ($res) { $row = $res[0]; break; }
    }
    if (!$row) return null;

    $fc = '';
    if (!empty($row['found_comment'])) {
        $fc = trim(preg_replace('/\,\/\/.*/', '', $row['found_comment']));
    }

    $has_work = !empty($row['program_name']) && $row['program_name'] !== 'その他';
    $info = [
        'song_name'      => $row['song_name']    ?? '',
        'lister_artist'  => $row['song_artist']  ?? '',
        'lister_work'    => $has_work ? $row['program_name'] : '',
        'lister_op_ed'   => $row['song_op_ed']   ?? '',
        'lister_comment' => $fc,
    ];
    if ($with_ruby) {
        $info['song_ruby']          = $row['song_ruby'] ?? '';
        $info['lister_artist_ruby'] = $row['found_artist_ruby'] ?? '';
        $info['lister_work_ruby']   = $has_work ? ($row['tie_up_ruby'] ?? '') : '';
    }
    return $info;
}

function exceltime2unixtime ($exceltime){
    return round( ($exceltime - 25569) * 60 * 60 * 24) ;
    return round( $exceltime * (86400 + 25569) - 32400);
}
function unixtime2exceltime ($unixtime){
    return ($unixtime + 32400) / 86400 + 25569 ;
}

?>