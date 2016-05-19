<?php


function getallrequest_fromdb($db) {
    $sql = "SELECT * FROM requesttable ORDER BY reqorder ASC";
    $select = $db->query($sql);
    $allrequest = $select->fetchAll(PDO::FETCH_ASSOC);
    $select->closeCursor();
    return $allrequest;
}

// 予約位置適切移動機能
// 
class MoveItem {
   // 周期リスト
   // turn = array ( 'id1' => 'name1', 'id2' => 'name2', 'id3' => 'name3' ...);
   // array_push(turnlist,turn);
   public $turnlist = array();
   public $allrequest = array();
   public $allrequest_new = array();
   public $max_reqorder = 0;
   
   
   public function getturnlist($db){
       $this->allrequest = getallrequest_fromdb($db);
       $this->allrequest_new = $this->allrequest;
       $this->recountreqorder();
       $turnlist = array();
       $oneturn = array();
       foreach($this->allrequest_new as $value ){
           if($this->max_reqorder < $value['reqorder'] ) $this->max_reqorder = $value['reqorder'] + 1;
           if($this->check_exists_mymember($oneturn,$value['singer']) === true){
             // goto next turn
             array_push($turnlist,$oneturn);
             $oneturn = array();
           }
           array_push($oneturn,$value);
       }
       if(count($oneturn) > 0 ){
           array_push($turnlist,$oneturn);
       }
       $this->turnlist = $turnlist;
   }
   
   public function get_new_reqorder($id,$notsingstart = 0){
       $beforeturn = array();
       $newsinger = $this->get_singer_fromid($id);
       if($newsinger === false) {
           return false;
       }
       foreach($this->turnlist as $oneturn){
       //print "<pre>";
       //    var_dump($oneturn);
       //print "</pre>";
           // 1番最初のリクエスト
           //print "ターンに入ります<br>\n";
           if(count($oneturn) == 0){
              //print "このターンは0でした<br>\n";
              return 1;
           }
           // 未再生の場所を探す
           $startcheck = false;
           foreach($oneturn as $onerequest){
               if($onerequest['nowplaying'] == '未再生' ){
                   $startcheck = true;
                   // print "このターンに未再生がありました".$onerequest['reqorder']." <br>\n";
                   break;
               }
           }
           // 現在のターンに1つも未再生がない → 次のターンへ
           if($startcheck == false) {
               $beforeturn = $oneturn;
               continue;
           }
           // 現在のターンに名前がある → 次のターンへ
           if($this->check_exists_mymember($oneturn,$newsinger,$id) === true){
               $beforeturn = $oneturn;
               // print "このターンに自分の名前がありました".$newsinger." <br>\n";
               // var_dump($oneturn);
               // print " <br>\n";
               continue;
           }
           // 1つ前のターンの順番に従いreqorderを決める

           // 1つ前のターンで自分より前の人の名前のリストを取得
           $mynamefound = false;
           $beforesinger = array();
           foreach( ($beforeturn) as $b_onerequest ){
           // print $b_onerequest['songfile'].$b_onerequest['singer'].$newsinger."<br>\n";
               if($mynamefound === false) {
                   if($b_onerequest['singer'] === $newsinger ){
                       $mynamefound = true;
                       //array_push($beforesinger,$b_onerequest);
                       continue;
                   }else{
                       // print $b_onerequest['singer'].$newsinger."<br>\n";
                       array_push($beforesinger,$b_onerequest);
                   }
               }else {
                   //array_push($beforesinger,$b_onerequest);
               }
           }
           if($mynamefound === false) {
               $beforesinger = null;
           }
           // とりあえず現ターンの未再生の中の最後の順番にしておく
           $newreqorder = false;
           if(empty($beforeturn)){
               $newreqorder = $oneturn[count($oneturn)-1]['reqorder']+1;
           }else{
           for($i = 0 ; $i < count($oneturn) ; $i++){
            // print  $oneturn[$i]['id'].$oneturn[$i]['reqorder'];
               if($oneturn[$i]['nowplaying'] == '未再生' ){
                   $newreqorder = $oneturn[$i]['reqorder'];
                   break;
               }
           }
           }
           if($newreqorder === false) break;
           // print " $newreqorder";
           //print "<pre>\nbefiresinger:\n";
           //var_dump($beforesinger);print "</pre>";
           if(!empty($beforesinger)){
               // 前ターンの自分の前の人がいたらその前の人にする
               $cheekedreqorder= $oneturn[0]['reqorder'];
               $setvalue = false;
               foreach( array_reverse ($oneturn) as $onerequest){
                   
                   foreach ($beforesinger as $beforeorder){
                   // print $onerequest['singer'].$onerequest['reqorder'].$beforeorder['singer'].$beforeorder['reqorder']."<br>\n";
                       if($onerequest['singer'] == $beforeorder['singer']){
                           $newreqorder = $cheekedreqorder ;
                           //print "reqorderを".$newreqorder.'にしました';
                           $setvalue = true;
                           break;
                       }
                       
                       //print $cheekedreqorder;
                   }
                   //print $oneturn[0]['reqorder'];
                   if($setvalue === true){
                       break;
                   }
                   $cheekedreqorder=$onerequest['reqorder'] ;
               }
           }else{
             // 最初のターンの場合そのターンの一番後ろにする
             if($newreqorder == 1 ){
               // print "ターンの一番後ろにしました";
               // そのターンの先頭にするときはコメントアウト
               $newreqorder = $oneturn[count($oneturn)-1]['reqorder']+1;
             }
             
           }
           return $newreqorder;
       }
       // print "come max_reqorder".$this->max_reqorder;
       return $this->max_reqorder + 1;
   }
   
   public function get_singer_fromid($id){
       foreach($this->allrequest_new as $value ){
           //print $value['id'].':'.$id."\n";
           if($value['id'] == $id){
               return $value['singer'];
           }
       }
       return false;
   }
   
   public function recountreqorder(){
       $currentreqorder = 1;
       for($i=0;$i < count($this->allrequest_new) ; $i++){
           $this->allrequest_new[$i]['reqorder']= $currentreqorder;
           $currentreqorder ++;
       }
   }
   
   public function insertreqorder($id,$reqorder){
       $currentreqorder = 1;
       for($i=0;$i < count($this->allrequest_new) ; $i++){
           if($this->allrequest_new[$i]['id'] == $id ){
       // print $this->allrequest_new[$i]['id'].':'.$id.':'.$reqorder."<br />";;
               $this->allrequest_new[$i]['reqorder'] = $reqorder;
               continue;
           }
           if($currentreqorder == $reqorder) {
               $currentreqorder ++;
           }else if($this->allrequest_new[$i]['reqorder'] == $reqorder){
               $currentreqorder ++;
           }
           $this->allrequest_new[$i]['reqorder']= $currentreqorder;
           $currentreqorder ++;
       }
       
   }
   
   public function save_allrequest($db){
       foreach($this->allrequest as $key => $row ){
           $idlist[$key] = $row['id'];
       }
       array_multisort($idlist, SORT_ASC, $this->allrequest);
       
       // table drop
       for($i=0;$i<count($this->allrequest_new);$i++){
          if($this->allrequest_new[$i]['reqorder'] !== $this->allrequest[$i]['reqorder']){
              $sql = 'UPDATE requesttable set reqorder="'.$this->allrequest_new[$i]['reqorder'].'" where id = "'.$this->allrequest_new[$i]['id'].'"';
              //print $sql."<br />\n";
              $count = $db->exec($sql);
              if($count !== 1) {
               //   print "reqorderを$count 件修正しました。".$this->allrequest[$i]['reqorder'].' to '.$this->allrequest_new[$i]['reqorder']."\n";
               }
          }
       }
   }
   


   public function addrequest_andsetreqorder($id){
       $allrequest = getallrequest_fromdb($db);
       $reqorderlist = array();
       foreach($allrequest as $value ){
           array_push($reqorderlist,array( $value['reqorder'] => $value['id']) );
       }
       return $reqorderlist;
   }
   
   public function get_current_reqorderlist($db){
       
   }
   public function check_exists_mymember($oneturn,$singer,$id='none'){
       foreach($oneturn as $value){
           //print "check_exists_mymember :".$value['singer'].':'.$singer."<br>\n";
           if($value['id'] == $id) continue;
           if($value['singer'] === $singer){
               // exists same turn
               return true;
           }
       }
       return false;
   }
}

?>
