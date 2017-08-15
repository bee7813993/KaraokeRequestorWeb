<?php
require_once 'commonfunc.php';

class PlayerProgress {
    public $MPCSTATURL='http://localhost:13579/info.html';
    public $MPCSTATUSURL='http://localhost:13579/status.html';
    public $MPCVARIABLESURL='http://localhost:13579/variables.html';
    public $PLAYERSTATUS='get_playingstatus_json.php';
    public $playtime_txt = "";
    public $totaltime_txt = "";
    public $playtime = "";
    public $totaltime = "";
    public $status = 0;
    public $playingtitle = "";
    
    
    public function show_progress_text(){
/*
        print '<script language="javascript" type="text/javascript">';
        print 'var curpos = '.$this->playtime.";\n";
        print 'var length = '.$this->totaltime.";\n";
        print 'var state = '.$this->status.";\n";
        print 'var pbr = 1;'."\n";
        print '</script>';
*/
        print '<div id="proglessbase" class="bg-info" >';

        if(!empty ($this->playingtitle) ){
            print '<div id="songtitle" > <p>Now Playing... </p>'. $this->playingtitle .'</div>';
        }
        print '<div class="progress" style="margin-bottom: 2px;">';
        print '<div class="progress-bar" role="progressbar" style="width: '.$this->playtime*100/$this->totaltime .'%;" id = "divprogress" ></div>';
        print '</div>';

        print '<div id="progresstime"><span id="time"> '. $this->playtime_txt . ' </span>／<span id="total"> ' . $this->totaltime_txt.' </span></div>';
        print '</div>';
        print '<script language="javascript" type="text/javascript">progresstime_init();</script>';
    }
    public function getstatus() {
        $result = array();
    
        $status = file_get_html_with_retry($this->MPCVARIABLESURL);
        if($status === FALSE) return $status;
        $status_array = preg_match_all("/\<p .*?>(.*?)<\/p>/", $status, $result);
        //var_dump($result);
        $this->status = $result[1][4];
        $this->playtime_txt = $result[1][7];
        $this->totaltime_txt = $result[1][9];
        $this->playtime = $result[1][6];
        $this->totaltime = $result[1][8];
        
        $this->gettitle();
        
        return true;
    }
    public function gettitle() {
        global $db;
        for($i = 0 ; $i < 10 ; $i++) {
          $sql = "SELECT * FROM requesttable  WHERE nowplaying = '再生中' ORDER BY reqorder ASC ";
          $select = $db->query($sql);
          $rowall = $select->fetchAll(PDO::FETCH_ASSOC);
          $select->closeCursor();
          if(count($rowall) == 0){
              $this->playingtitle = "";
              usleep(100000);
          }else {
              
              $this->playingtitle = $rowall[0]['songfile'];
              break;
          }
        }
        
    }
    
    public function getplaystatus_json() {
        if( $this->getstatus() ){
        $ret = array();
        $ret += array('playtime_txt'=>$this->playtime_txt);
        $ret += array('totaltime_txt'=>$this->totaltime_txt);
        $ret += array('playtime'=>$this->playtime);
        $ret += array('totaltime'=>$this->totaltime);
        $ret += array('status'=>$this->status);
        $ret += array('playingtitle'=>$this->playingtitle);
        
        return json_encode($ret);
        }else {
          return false;
        }
    }
}

?>