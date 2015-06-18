<?php

$configfile = 'config.ini';
$config_ini = array ();

if(file_exists($configfile)){
    $config_ini = parse_ini_file($configfile);
    $xmlstr = "<?xml version=\"1.0\" ?><root></root>";
    $xml = new SimpleXMLElement($xmlstr);
    
    //var_dump($config_ini);


    foreach($config_ini as $key => $value){
        $xmlitem = $xml -> addChild($key, $value);
    }
    print $xml -> asXML();
}else{
    print "no $configfile";
}

?>
