<?php
class EasyKeychanger {
    public $KeychangerURL = 'http://localhost:13580/';
    public $Keychangertimeout = 1;
    
    public function getstatus() {
        require_once 'commonfunc.php';
        
        $url = $this->KeychangerURL.'command.html?help=ver';
        
        for ($i=0; $i<10; $i++) {
            $res = @file_get_html_with_retry($url,2,$this->Keychangertimeout,4);
            if($res !== false) break;
        }
        if($i == 10 ) return false;
        return $this->build_result($res);
        
    }
    
    public function keyup($token = "") {
        if(empty($token))
            $url = $this->KeychangerURL.'command.html?key=up';
        else    
            $url = $this->KeychangerURL.'command.html?key=up&token='.$token;
            
        for ($i=0; $i<10; $i++) {
            $res = file_get_contents($url);
            if($res !== false) break;
        }
    }
    
    public function keydown($token = "") {
        if(empty($token))
            $url = $this->KeychangerURL.'command.html?key=down';
        else    
            $url = $this->KeychangerURL.'command.html?key=down&token='.$token;

        for ($i=0; $i<10; $i++) {
            $res = file_get_contents($url);
            if($res !== false) break;
        }
    }

    public function keyset($key = 0, $token = "") {
        if(empty($token))
            $url = $this->KeychangerURL.'command.html?key='.$key;
        else    
            $url = $this->KeychangerURL.'command.html?key='.$key.'&token='.$token;
        for ($i=0; $i<10; $i++) {
            $res = file_get_contents($url);
            if($res !== false) break;
        }
    }

    public function build_result($str) {
        $statusbase = explode("\r\n",$str);
        
        $modulename = "";
        $version = "";
        $copyright = "";
        $commandresult = "";
        $currentkey = "NON";
        
        $flg = 0;
        
        for($i = 0 ; $i< count($statusbase); $i++){
            //print $statusbase[$i]."\n";
            if($flg === 0 ){
                if( strpos( $statusbase[$i], 'Ver') !== false){
                   $flg = 1;
                } else {
                    if(strlen($modulename) === 0){
                        $modulename = $statusbase[$i];
                    }else
                        $modulename = $modulename." ".$statusbase[$i];
                }
            }
            if($flg === 1 ){
                if( strpos( $statusbase[$i], 'Copyright') !== false){
                    $flg = 2;
                }else{                
                  $versionstring = explode(" ",$statusbase[$i]);
                  foreach($versionstring as $value){
                    if( strpos( $value, 'Ver')!== false) continue;
                    if(strlen($version) === 0){
                        $version = $value;
                    }else{
                        $version = $version." ".$value;
                    }
                  }
                }
            }
            if($flg === 2 ){
                if( strpos( $statusbase[$i] , 'key')!== false) {
                    $flg = 3;
                }else {
                    if(strlen($copyright) === 0){
                        $copyright = $statusbase[$i];
                    }else
                    $copyright = $copyright." ".$statusbase[$i];
                }
            }
            if($flg === 3 ){
                if( strpos( $statusbase[$i] , 'key')!== false) {
                    if(strlen($commandresult) > 0){
                        $flg = 4;
                    }else {
                        $commandresult = $statusbase[$i];
                    }
                }else {
                    if(strlen($copyright) === 0){
                        $commandresult = $statusbase[$i];
                    }else
                    $commandresult = $commandresult." ".$statusbase[$i];
                }
            }
            if($flg === 4 ){
                  $keystring = explode(":",$statusbase[$i]);
                  foreach($keystring as $value){
                    if( strpos( $value, 'key')!== false) continue;
                    if( strpos($currentkey, 'NON' ) !== false){
                        $currentkey = $value;
                    }else{
                        $currentkey = $currentkey." ".$value;
                    }
                  }
            }
        } // for loop
        
        if(strpos( $currentkey , "NON" )!== false){
            $currentkey = $statusbase[count($statusbase)-1];
        }
        $result_array = array("modulename" => ($modulename), 
                              "version" => $version, 
                              "copyright" => $copyright, 
                              "currentkey" => (int)$currentkey );
        return    $result_array;
        
    }

}
?>