<?php
/* 設定ファイル 読み込みclass */

class ReadConfig {
    public $readconfigfile = 'config.ini';
    public $read_config_ini = array ();

    public function read_config() {
        $config_ini = array ();
    
        if(file_exists($this->readconfigfile)){
            $parsed = parse_ini_file($this->readconfigfile);
            $this->read_config_ini = is_array($parsed) ? $parsed : false;
        }else {
            $this->read_config_ini = false;
        }
        
        return  $this->read_config_ini;
    }
}