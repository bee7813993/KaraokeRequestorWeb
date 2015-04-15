<?php
require_once 'commonfunc.php';
//setlocale(LC_ALL, 'ja_JP.Shift_JIS');
date_default_timezone_set('Asia/Tokyo');
require_once 'kara_config.php';
$FOOBARSTATURL='http://'.$_SERVER["HTTP_HOST"].':82/karaokectrl/';

$sql = "SELECT * FROM requesttable  WHERE nowplaying = \"再生中\" ORDER BY reqorder ASC ";
$select = $db->query($sql);
$currentsong = $select->fetchAll(PDO::FETCH_ASSOC);
$select->closeCursor();
//var_dump($currentsong);
if(count($currentsong) == 0){
    print '{ "count": "none"}';
}else {   
$extension = pathinfo($currentsong[0]['songfile'], PATHINFO_EXTENSION);
if( strcasecmp($extension,"mp3") == 0 
    || strcasecmp($extension,"m4a") == 0 
    || strcasecmp($extension,"wav") == 0 ){
    $player="foobar";
    global $FOOBARSTATURL;
    $playerstat = file_get_html_with_retry($FOOBARSTATURL, 5);
    if( $playerstat === FALSE) {
    print("maybe stop player\n");
    }else{
        $statusarray = json_decode(mb_convert_encoding($playerstat,"UTF-8"),true,4096);
        if( $statusarray === null ){
            print json_last_error_msg();
        }else{
            $volume = $statusarray["VOLUME"];
        }
    }
    
    
}else {
    $player="mpc";
}

print '{"songfile": "'.$currentsong[0]['songfile'].'"';
print ', "player": "'.$player.'"';
if(isset($volume)){
    print ', "volume": "'.$volume.'"';
}
print '}';
}

?>
