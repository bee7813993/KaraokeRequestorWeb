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
}

/**
 * fullpath から t_found を検索し、requesttable 保存用の連想配列を返す。
 * 見つからない場合は null。
 * キー: song_name, lister_artist, lister_work, lister_op_ed, lister_comment
 */
function listerdb_lookup_songinfo($fullpath, $lister_dbpath) {
    if (empty($fullpath) || !file_exists($lister_dbpath)) return null;
    $lister = new ListerDB();
    $lister->listerdbfile = $lister_dbpath;
    $lfd = $lister->initdb();
    if (!$lfd) return null;

    $row = null;
    foreach ([$fullpath, basename($fullpath)] as $search) {
        $res = $lister->select(
            'SELECT song_name, song_artist, program_name, song_op_ed, found_comment'
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

    return [
        'song_name'      => $row['song_name']    ?? '',
        'lister_artist'  => $row['song_artist']  ?? '',
        'lister_work'    => (!empty($row['program_name']) && $row['program_name'] !== 'その他') ? $row['program_name'] : '',
        'lister_op_ed'   => $row['song_op_ed']   ?? '',
        'lister_comment' => $fc,
    ];
}

function exceltime2unixtime ($exceltime){
    return round( ($exceltime - 25569) * 60 * 60 * 24) ;
    return round( $exceltime * (86400 + 25569) - 32400);
}
function unixtime2exceltime ($unixtime){
    return ($unixtime + 32400) / 86400 + 25569 ;
}

?>