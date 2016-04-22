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
                   //print "このターンに未再生がありました<br>\n";
                   break;
               }
           }
           // 現在のターンに1つも未再生がない → 次のターンへ
           if($startcheck == false) {
               $beforeturn = $oneturn;
               continue;
           }
           // 現在のターンに名前がある → 次のターンへ
           if($this->check_exists_mymember($oneturn,$newsinger) === true){
               $beforeturn = $oneturn;
               continue;
           }
           // 1つ前のターンの順番に従いreqorderを決める

           // 1つ前のターンで自分より前の人の名前のリストを取得
           $mynamefound = false;
           $beforesinger = array();
           foreach( array_reverse($beforeturn) as $b_onerequest ){
               if($mynamefound === false) {
                   if($b_onerequest['singer'] === $newsinger ){
                       $mynamefound = true;
                       continue;
                   }else{
                       // print $b_onerequest['singer'].$newsinger."<br>\n";
                       array_push($beforesinger,$b_onerequest);
                   }
               }else {
               }
           }
           // とりあえず現ターンの先頭の順番にしておく
           $newreqorder = $oneturn[0]['reqorder'];
           // print " $newreqorder";
           //print "<pre>";
           // var_dump($beforesinger);print "</pre>";
           if(!empty($beforesinger)){
               // 前ターンの自分の前の人がいたらその前の人にする
               foreach( array_reverse ($oneturn) as $onerequest){
                   foreach ($beforesinger as $beforeorder){
                       if($onerequest['singer'] == $beforeorder['singer']){
                           $newreqorder = $onerequest['reqorder'] ;
                           break;
                       }
                   }
                   if($newreqorder != $oneturn[0]['reqorder']){
                       break;
                   }
               }
           }else{
             // そのターンの一番後ろにする
             $newreqorder = $oneturn[count($oneturn)-1]['reqorder']+1;
           }
           return $newreqorder;
       }
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
       print $this->allrequest_new[$i]['id'].':'.$id.':'.$reqorder."<br />";;
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
              if($count !== 1) 
                  print "reqorderを$count 件修正しました。".$this->allrequest[$i]['reqorder'].' to '.$this->allrequest_new[$i]['reqorder']."\n";
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
   public function check_exists_mymember($oneturn,$singer){
       foreach($oneturn as $value){
           //print "check_exists_mymember :".$value['singer'].':'.$singer."<br>\n";
           if($value['singer'] === $singer){
               // exists same turn
               return true;
           }
       }
       return false;
   }
}

?>
