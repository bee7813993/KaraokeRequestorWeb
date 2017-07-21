<?php
class UpdateNotice {
    public $updatedbfilename = "updatenotice.db";
    public $db = null;
    
    public function initdb(){
        try {
        	$this->db = new PDO('sqlite:'. $this->updatedbfilename);
        } catch(PDOException $e) {
        	printf("new PDO Error: %s\n", $e->getMessage());
        	return;
        } 
        $sql = "create table IF NOT EXISTS updatenoticetable (
                    requestlist INTEGER default 0,
                    playerkind  INTEGER default 0,
                    other       text
                    );";
        $stmt = $this->db->query($sql);
        if ($stmt === false ){
            print("Create table 失敗しました。<br>");
            return;
        }
        $sql ="select count(*) from updatenoticetable;";
        $stmt = $this->db->query($sql);
        $dbcount = $stmt->fetchColumn();
        $stmt->closeCursor();
        if($dbcount < 1) {
            $sql = "INSERT INTO updatenoticetable ( requestlist,playerkind,other ) VALUES (0,0,'')";
            $stmt = $this->db->query($sql);
            if ($stmt === false ){
                print("1st Insert 失敗しました。<br>");
                return;
            }
        }
    }
    
    public function updaterequestlist(){
        if($this->db === null ) return false;
        
        $sql = "update updatenoticetable set requestlist = CASE WHEN requestlist < 50000 THEN requestlist + 1
                                                                ELSE 0 END;";
        $stmt = $this->db->query($sql);
        if ($stmt === false ){
            print("update table 失敗しました。<br>");
            return;
        }
    }

    public function show_requestlist(){
        $sql ="select requestlist from updatenoticetable;";
        $stmt = $this->db->query($sql);
        $requestlist_num = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $requestlist_num;
    }

    public function updateplayerkind(){
        if($this->db === null ) return false;
        
        $sql = "update updatenoticetable set playerkind = CASE WHEN playerkind < 50000 THEN playerkind + 1
                                                                ELSE 0 END;";
        $stmt = $this->db->query($sql);
        if ($stmt === false ){
            print("update table 失敗しました。<br>");
            return;
        }
    }

    public function show_playerkind(){
        $sql ="select playerkind from updatenoticetable;";
        $stmt = $this->db->query($sql);
        $playerkind_num = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $playerkind_num;
    }

    public function closedb(){
        $this->db = null;
    }

    public function show_all(){
        $sql ="select * from updatenoticetable;";
        $stmt = $this->db->query($sql);
        $allupdatenotice = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $allupdatenotice;
    }

    public function show_all_json(){
        $sql ="select * from updatenoticetable;";
        $stmt = $this->db->query($sql);
        $allupdatenotice = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return json_encode( $allupdatenotice , JSON_PRETTY_PRINT ) ;
    }
}
?>