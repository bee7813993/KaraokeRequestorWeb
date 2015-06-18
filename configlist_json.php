<?php
// 設定一覧をjsonで返す。
$configfile = 'config.ini';
$config_ini = array ();

if(file_exists($configfile)){
    $config_ini = parse_ini_file($configfile);
    //var_dump($config_ini);

    $json = json_encode($config_ini,JSON_PRETTY_PRINT);

    print $json;

}else{
    print "no $configfile";
}

?>
