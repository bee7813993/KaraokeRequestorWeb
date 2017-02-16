<?php
class SongBingo {
    public $bingodb = null;
    public $bingomax = 75;
    public $bingotable = array();

    public function initbingodb($dbname){

        try {
            $this->bingodb = new PDO('sqlite:'. $dbname);
        } catch(PDOException $e) {
            printf("new PDO Error: %s\n", $e->getMessage());
            die();
        } 
        $sql = "create table IF NOT EXISTS bingotable (
         id INTEGER PRIMARY KEY AUTOINCREMENT, 
         requirement  varchar(1024), 
         number INTEGER, 
         opened INTEGER,
         reqid INTEGER
        )";
        $stmt = $this->bingodb->query($sql);
        if ($stmt === false ){
            print("Create table 失敗しました。<br>");
            die();
        }
        return($this->bingodb);
    }
    
    /* bingo DB登録関数 */
    /* 1行に1つの解放条件を書いたtextファイルを読み込む */
    
    public function registbingodb($textarray){
        $result_array = array();
        $finished = true;
        $firstnum = true;
        $arraynum = count($textarray);
        if($arraynum <= $this->bingomax){
        print 'come lower';
          $finished = true;
          $firstnum = true;
          $numbers = range(1, $this->bingomax);
          shuffle($numbers);
          while($finished){
            $b_num = array_pop($numbers);
            // bingoの数75個割り当て
            foreach($textarray as $condtext ){
            print '<hr>';
            var_dump($numbers);
                $word = trim($condtext);
                if(strlen($word) == 0) continue;
                $result_array[] = array( 'num' => $b_num, 'word' => $word);
                $b_num = array_pop($numbers);
                if($b_num === NULL) break;
                if(count($result_array) >= $this->bingomax && $firstnum === false) break;
                // 
            }
            $firstnum = false;
            print count($result_array).' ';
            if(count($result_array) >= $this->bingomax ) $finished = false;
          }
        }else{
          $finished = true;
          $firstnum = true;
          while($finished){
            $numbers = range(1, $this->bingomax);
            shuffle($numbers);
            $b_num = array_pop($numbers);
            // bingoの数75個割り当て
            foreach($textarray as $condtext ){
                $word = trim($condtext);
                if(strlen($word) == 0) continue;
                $result_array[] = array( 'num' => $b_num, 'word' => $word);
                if(count($result_array) >= $arraynum ) break;
                $b_num = array_pop($numbers);
                if($b_num === NULL){
                    $numbers = range(1, $this->bingomax);
                    shuffle($numbers);
                    $b_num = array_pop($numbers);
                }
            }
            print count($result_array).' ';
            if(count($result_array) >= $arraynum  ) $finished = false;
          }
        }
//var_dump($result_array);
        if(count($result_array) > 0) $this->bingotable = $result_array;
        return $result_array;
        
    }
    
    public function savearray2newdb(){
        /* まず削除 */
        $sql = "DELETE FROM bingotable;";
        $retval = $this->bingodb->exec($sql);
        if (! $retval ) {
            echo "\nPDO::errorInfo():\n";
            print_r($this->bingodb->errorInfo());
            //print "<br>dbname : $dbname \n<br>";
        }
        foreach( $this->bingotable as $value ){
            $sql = 'insert into bingotable (requirement, number, opened, reqid ) VALUES ("'. $value['word'].'",'.$value['num'].', 0, 99999);';
            $retval = $this->bingodb->exec($sql);
            if (! $retval ) {
                echo "\nPDO::errorInfo():\n";
                print_r($this->bingodb->errorInfo());
                print "<br>Insert : $sql \n<br>";
            }
        }
    }
    
    /* 行区切りテキストを配列に */
    public function text2array($listtext){
        $arr = mb_split("/\\\r\\\n|\\\r|\\\n/", $listtext);
        array_walk_recursive($arr, 'trim');
        $arrnew = array();
        foreach($arr as $onearr){
            $arrnew[] = trim($onearr);
        }
        $arr = array_filter($arrnew, 'strlen');
        $arr = array_merge($arr);
        return $arr;
    }
    
    public function readbingodatatoarray(){
        $sql = "select * FROM bingotable";
        $select = $this->bingodb->query($sql);
        $alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        $this->bingotable = $alldbdata;
        return $alldbdata;
    }


    public function wordkey_readdata(){  
        $sql = "select DISTINCT requirement FROM bingotable";
        $select = $this->bingodb->query($sql);
        $requirementlist = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        
        $result=array();
        
        foreach($requirementlist as $onerequirement){
        //var_dump($onerequirement);
            $sql = 'select * FROM bingotable WHERE requirement="'.$onerequirement["requirement"].'";';
            $select = $this->bingodb->query($sql);
            if($select === false){
                print_r($this->bingodb->errorInfo());
                break;
            }
            // fetch loop
            $numberlist = array();
            $lastdata = null;
            while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
                $numberlist[] = $data['number'];
                $lastdata = $data;
            }
            $result[] = array($lastdata['requirement'],$numberlist,$lastdata['opened']);
        }
        return $result;
    }
    
    public function numkey_readdata(){  
        $sql = "select DISTINCT number FROM bingotable ORDER BY number";
        $select = $this->bingodb->query($sql);
        $numberlist = $select->fetchAll(PDO::FETCH_ASSOC);
        $select->closeCursor();
        
        $result=array();
        
        foreach($numberlist as $onerequirement){
            $sql = 'select * FROM bingotable WHERE number="'.$onerequirement["number"].'";';
            $select = $this->bingodb->query($sql);
            if($select === false){
                print_r($this->bingodb->errorInfo());
                break;
            }
            // fetch loop
            $requirementlist = array();
            $opednum = 0;
            while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
                $requirementlist[] = $data['requirement'];
                $opednum +=  $data['opened'];
            }
            $result[] = array($onerequirement["number"],$requirementlist,$opednum);
        }
        return $result;
    }

    public function showalldbtable(){
        $this->bingotable = $this->readbingodatatoarray();
        print '<table border=1 >';
        print '<tbody>';
        foreach($this->bingotable as $onebingo ){
        // var_dump($onebingo);
            print '<tr>';
            print '<td>';
            print $onebingo['requirement'];
            print '</td>';
            print '<td>';
            print $onebingo['number'];
            print '</td>';
            print '<td>';
            print $onebingo['opened'];
            print '</td>';
            print '</tr>';
        }
        print '</tbody>';
        print '</table>';
    }
    
    public function updateopened($requirement, $toopened = 1 , $id = 99999){
        $sql = 'UPDATE bingotable SET opened = '.$toopened.', reqid = '.$id.'  WHERE requirement ="'.$requirement.'";';
        print '<pre>'.$sql.'</pre>';
        $retval = $this->bingodb->exec($sql);
        print '<pre> Update '.$retval.' lines</pre>';
        if (! $retval ) {
            echo "\nPDO::errorInfo():\n";
            print_r($this->bingodb->errorInfo());
            //print "<br>dbname : $dbname \n<br>";
        }
    }
    
    public function getnumfromid( $id ){
        $sql = 'select number FROM bingotable WHERE reqid = '.$id.' ORDER BY number';
        $select = $this->bingodb->query($sql);
        
        $result=array();

        while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $data['number'];
        }
        $select->closeCursor();
        return $result;
    }


}

?>
