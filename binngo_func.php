<?php
class SongBingo {
    public $bingodb = null;
    public $bingomax = 75;
    public $bingotable = array();
    public $bingoconfigfile = 'bingoconfig.ini';
    public $bingoconfig = array();

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

    public function readbingoconfig(){
        if(file_exists($this->bingoconfigfile)){
            $this->bingoconfig = parse_ini_file($this->bingoconfigfile);
            return $this->bingoconfig;
        }else{
           // print 'ファイルがない:'.$this->bingoconfigfile;
        }
        return array();
    }

    public function writebingoconfig($config_array){
        $fp = fopen($this->bingoconfigfile, 'w');
        foreach ($config_array as $k => $i){
                fputs($fp, "$k=$i\n");
        }
        fclose($fp);
    }

    
    /* bingo DB登録関数 */
    /* 1行に1つの解放条件を書いたtextファイルを読み込む */
    
    public function registbingodb($textarray){
        $result_array = array();
        $finished = true;
        $firstnum = true;
        $arraynum = count($textarray);
        if($arraynum <= $this->bingomax){
        // print 'come lower';
          $finished = true;
          $firstnum = true;
          $numbers = range(1, $this->bingomax);
          shuffle($numbers);
          while($finished){
            // bingoの数75個割り当て
            foreach($textarray as $condtext ){
            print '<hr>';
            // var_dump($numbers);
                $word = trim($condtext);
                if(strlen($word) == 0) continue;
                $b_num = array_pop($numbers);
                $result_array[] = array( 'num' => $b_num, 'word' => $word);
                if(count($numbers) <= 0 )$b_num = NULL;
                if($b_num === NULL) break;
                if(count($result_array) >= $this->bingomax && $firstnum === false) break;
                // 
            }
            $firstnum = false;
            print count($result_array).'個登録 ';
            if(count($result_array) >= $this->bingomax ) $finished = false;
          }
        }else{
          $finished = true;
          $firstnum = true;
          while($finished){
            $numbers = range(1, $this->bingomax);
            shuffle($numbers);
            // bingoの数75個割り当て
            foreach($textarray as $condtext ){
                $word = trim($condtext);
                if(strlen($word) == 0) continue;
                $b_num = array_pop($numbers);
                $result_array[] = array( 'num' => $b_num, 'word' => $word);
                if(count($result_array) >= $arraynum ) break;
                if(count($numbers) <= 0 )$b_num = NULL;
                if($b_num === NULL){
                    $numbers = range(1, $this->bingomax);
                    shuffle($numbers);
                    $b_num = array_pop($numbers);
                }
            }
            print count($result_array).'個登録 ';
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

    /* ランダム開放用文字列生成 */
    public function makerandamopenarray(){
        $arrnew = array();
        for($i = 0 ; $i < $this->bingomax ; ){
            $i++;
            $arrnew[] = $i.'個め';
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
            $idlist = array();
            $lastdata = null;
            while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
                $numberlist[] = $data['number'];
                $idlist[] = $data['reqid'];
                $lastdata = $data;
            }
            $result[] = array($lastdata['requirement'],$numberlist,$lastdata['opened'],$idlist);
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
            $idlist = array();
            $opednum = 0;
            while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
                $requirementlist[] = $data['requirement'];
                $opednum +=  $data['opened'];
                $idlist[] = $data['reqid'];
            }
            $result[] = array($onerequirement["number"],$requirementlist,$opednum,$idlist);
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
        // print '<pre>'.$sql.'</pre>';
        $retval = $this->bingodb->exec($sql);
        // print '<pre> Update '.$retval.' lines</pre>';
        if (! $retval ) {
            echo "\nPDO::errorInfo():\n";
            print_r($this->bingodb->errorInfo());
            //print "<br>dbname : $dbname \n<br>";
        }
    }
    
    public function getnumfromid( $id ){
        $sql = 'select number,opened FROM bingotable WHERE reqid = '.$id.' ORDER BY number';
        $select = $this->bingodb->query($sql);
        
        $result=array();

        while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
             if($data['opened'] == 1) {
                $result[] = $data['number'];
            }
        }
        $select->closeCursor();
        return $result;
    }

    public function autoopen( $id ){
        $mode = $this->readbingoconfig();
        $mode = $mode['bingoautoopen'];
        if($mode == 1){
          // ランダム開放 
             // すでに同じIDでオープンされているかチェック
             $openedfromid = $this->getnumfromid($id);
             if(count($openedfromid) > 0 ) return true;
          // とりあえず、空いていない一番若いOPEN条件を取得する
              $openrequirement = '';
              $bingodata = $this->readbingodatatoarray();
              foreach($bingodata as $onebingodata){

                  if($onebingodata['opened'] == 0 ) {
                      $openrequirement = $onebingodata['requirement'];
                      break;
                  }
              }
              if(empty($openrequirement)) return true;
              
              // print 'Debug 解放: '.$openrequirement;
              $this->updateopened($openrequirement,1,$id);
        } else if($mode == 2){
            //部分一致解放
              $openrequirement = '';
              $bingodata = $this->readbingodatatoarray();
              foreach($bingodata as $onebingodata){
                  if($onebingodata['opened'] == 0 ) {
                      require_once 'kara_config.php';
                      global $db;
                      $sql = 'SELECT songfile from requesttable where id = '.$id;
                      $select = $db->query($sql);
                      $reqlist = $select->fetchAll(PDO::FETCH_ASSOC);
                      $select->closeCursor();
                      // print $onebingodata['requirement'].$reqlist[0]['songfile'];
                      $existscount = mb_strpos($reqlist[0]['songfile'],$onebingodata['requirement'] );
                      // print $existscount.'<br />';
                      if($existscount !== FALSE) {
                          $openrequirement = $onebingodata['requirement'];
                          break;
                      }
                  }
              }
              if(empty($openrequirement)) return true;
              // print 'Debug 解放: '.$openrequirement;
              $this->updateopened($openrequirement,1,$id);
        } else if($mode == 3){
            //完全一致解放
              $openrequirement = '';
              $bingodata = $this->readbingodatatoarray();
              foreach($bingodata as $onebingodata){
                  if($onebingodata['opened'] == 0 ) {
                      require_once 'kara_config.php';
                      global $db;
                      $sql = 'SELECT songfile from requesttable where id = '.$id;
                      $select = $db->query($sql);
                      $reqlist = $select->fetchAll(PDO::FETCH_ASSOC);
                      $select->closeCursor();
                      if( mb_strlen($onebingodata['requirement']) != mb_strlen($reqlist[0]['songfile'])) continue;
                      $existscount = mb_strpos($onebingodata['requirement'],$reqlist[0]['songfile'] );
                      if($existscount !== FALSE) {
                          $openrequirement = $onebingodata['requirement'];
                          break;
                      }
                  }
              }
              if(empty($openrequirement)) return true;
              // print 'Debug 解放: '.$openrequirement;
              $this->updateopened($openrequirement,1,$id);
        }
    }


}

?>
