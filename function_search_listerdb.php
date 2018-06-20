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
        if(!$select){
            print_r($this->ListerDBFD->errorInfo());
            print $sql;
            return false;
        }
        $alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        if(!$alldbdata){
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

function exceltime2unixtime ($exceltime){
    return round( ($exceltime - 25569) * 60 * 60 * 24) ;
    return round( $exceltime * (86400 + 25569) - 32400);
}
function unixtime2exceltime ($unixtime){
    return ($unixtime + 32400) / 86400 + 25569 ;
}

?>