<?php

class pfwd {
    public $pfwdini = array();
    public $pfwdpath='pfwd_forykr\\';
    public $pfwdinifile='pfwd.ini';
    public $pfwdcmd='pfwd.exe';
    
    public function readpfwdcfg(){
        if(!file_exists($this->pfwdpath.'\\pfwd.exe')){
            print 'pfwd.exeインストールフォルダ「'.$this->pfwdpath.'」が間違っています';
            return false;
        }
        $this->pfwdinifile = $this->pfwdpath.'\\pfwd.ini';
        if(!file_exists($this->pfwdinifile)){
            $file=fopen($this->pfwdinifile,"w");
            $initpfwdini = <<<EOT
[SSH]
Host=ykr.moe
Port=10090
Compression=1
ProtocolVersion=2
PrivateKey=ykrnkkr.ppk
User=nkkr
Password=bgVuHC4bKRw=
[FORWARD]
01=R11080:localhost:80
EOT;
            fwrite($file,$initpfwdini);
            fclose($file);
        }
        $this->pfwdini = array();
        $file=fopen($this->pfwdinifile,"r");
        if($file){
            while($line=fgets($file)){
                $line = trim($line);
                if(substr($line,0,1) === ';' ) continue;
                if(strlen($line) === 0 ) continue;
                $this->pfwdini[]=$line;
            }
            fclose($file);
        }
        return true;
    }

    public function save_pfwdconfig($filename = 'none'){
      if($filename === 'none'){
          $filename = $this->pfwdpath.'\\pfwd.ini';
      }
      $file=fopen($filename,"w");
      if($file){
          foreach($this->pfwdini as $line){
              fwrite($file,$line."\r\n");
          }
      }else{
          return false;
      }
      return true;
    }



    public function showpfwdcfg(){
        var_dump($this->pfwdini);
    }
    
    public function get_pfwdhost(){
        foreach($this->pfwdini as $line){
            if(substr($line,0,5) === 'Host=' ) {
                return substr($line,5);
            }
        }
        return false;
    }
    public function get_pfwdport(){
        foreach($this->pfwdini as $line){
            if(substr($line,0,5) === 'Port=' ) {
                return substr($line,5);
            }
        }
        return false;
    }

    public function get_pfwdopenport(){
        foreach($this->pfwdini as $line){
            if(substr($line,0,4) === '01=R' ) {
                preg_match('/R(.+?):/',$line,$str_match);
                return $str_match[1];
            }
        }
        return false;
    }


    public function set_pfwdhost($hoststring){
        $replaceline = false;
        foreach($this->pfwdini as $key => $line){
            if(substr($line,0,5) === 'Host=' ) {
                $replaceline = $line;
                $this->pfwdini[$key] = str_replace($replaceline,'Host='.$hoststring,$line);
                break;
            }
        }
        return $this->pfwdini;
    }
    public function set_pfwdport($portstring){
        $replaceline = false;
        foreach($this->pfwdini as $key => $line){
            if(substr($line,0,5) === 'Port=' ) {
                $replaceline = $line;
                $this->pfwdini[$key] = str_replace($replaceline,'Port='.$portstring,$line);
                break;
            }
        }
        return $this->pfwdini;
    }

    public function set_pfwdopenport($portstring){
        $replaceline = false;
        foreach($this->pfwdini as $key => $line){
            if(substr($line,0,4) === '01=R' ) {
                $replaceline = $line;
                $this->pfwdini[$key] = str_replace($replaceline,'01=R'.$portstring.':localhost:80',$line);
                break;
            }
        }
        return $this->pfwdini;
    }


    public function startpfwdcmd(){
        $cmd = 'start "" '.$this->pfwdpath.$this->pfwdcmd;
        $fp = popen($cmd,'r');
        pclose($fp);
    }
    public function stoppfwdcmd(){
        $cmd = 'taskkill /im "'.$this->pfwdcmd.'" -f';
        exec($cmd);
    }
    public function statpfwdcmd(){
        $cmd='tasklist /fi "imagename eq '.$this->pfwdcmd.'"';
        exec($cmd,$psresult);
        $process_found = false;
        foreach( $psresult as $psline ){
          $pos = strpos($psline,$this->pfwdcmd);
          if ( $pos !== FALSE) {
            $process_found = true;
          }
        }
        return $process_found;
    }

}

/*** for test
$pfwdinfo = new pfwd();
$pfwdinfo->readpfwdcfg();

print $pfwdinfo->get_pfwdhost();
print '<br>';
print $pfwdinfo->get_pfwdport();
print '<br>';
print $pfwdinfo->get_pfwdopenport();
print '<br>';
$pfwdinfo->showpfwdcfg();
$pfwdinfo->set_pfwdopenport(11020);
$pfwdinfo->set_pfwdhost('ykr.moe');
$pfwdinfo->set_pfwdport(10090);
$pfwdinfo->showpfwdcfg();
$pfwdinfo->save_pfwdconfig($pfwdinfo->pfwdpath.'\\pfwd.ini');
//$pfwdinfo->startpfwdcmd();
//$pfwdinfo->stoppfwdcmd();
print $pfwdinfo->statpfwdcmd();
***/

?>