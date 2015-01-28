<?php
include 'kara_config.php';

$MPCPATH="C:\Program Files (x86)\MPC-BE\mpc-be.exe";

while(1){
    $sql = "SELECT * FROM requesttable  WHERE nowplaying = '未再生' ORDER BY id ASC ";
    $select = $db->query($sql);
    
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
     $word=$row['songfile'];
     $l_id=$row['id'];
     $jsonurl = "http://" . "localhost" . ":81/?search=" . $word . "&sort=size&ascending=0&path=1&path_column=3&size_column=4&json=1";
     $json = file_get_contents($jsonurl);
     $decode = json_decode($json, true);
     $filepath = $decode{'results'}{'0'}{'path'} . "\\" . $decode{'results'}{'0'}{'name'};
     $filepath = mb_convert_encoding($filepath,"SJIS");
     $execcmd="\"".$MPCPATH."\"" . " /play \"$filepath\"\n";
     print(" Debug : execcmd : $execcmd\n");
     $sql = "UPDATE requesttable set nowplaying = \"再生中\" WHERE id = $l_id ";
     $ret = $db->query($sql);
if (! $ret ) {
	print("再生中 への変更にしっぱいしました。<br>");
}
     exec($execcmd);
     $sql = "UPDATE requesttable set nowplaying = \"再生済\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
//     $sql = "UPDATE requesttable set nowplaying = \"未再生\" WHERE nowplaying = \"再生中\" AND songfile = '$word' ";
     $ret = $db->query($sql);
 if (! $ret ) {
	print("再生済への変更にしっぱいしました。<br>");
 }
     $sql = "UPDATE requesttable set nowplaying = \"再生済？\" WHERE nowplaying = \"再生中\" ";
     $ret = $db->query($sql);
if (! $ret ) {
	print("再生済？ への変更にしっぱいしました。<br>");
}
     break;
    }
    


    sleep(5);
//     break;

}
?>