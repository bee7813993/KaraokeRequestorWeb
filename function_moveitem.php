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
   public $db = null;
   
   /* 各ターン一覧を作成。同じ人が次に現れたところで次のターンが始まるということにする */
   public function getturnlist($db){
       $this->db = $db;
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
   
   /* そのIDの新しい再生順を返す */
   public function get_new_reqorder($id,$notsingstart = 0){
       $beforeturn = array();
       $beforeturn_1 = array();
       $newsinger = $this->get_singer_fromid($id);
       if($newsinger === false) {
           return false;
       }
       
       // 1番最初のリクエスト
       if(count($this->allrequest) == 0){
           return 1;
       }
       //現在の再生中の順番を探す
       $playingorder = false;
       if(!empty ($this->db ) ) {
       $this->allrequest = getallrequest_fromdb($this->db);
       foreach ($this->allrequest as $onerequest ){
           if($onerequest['nowplaying'] ==='再生中' ){
               $playingorder = $onerequest['reqorder'];
               break;
           }
           // 再生中がない場合、最初に見つかる未再生の項目にする
           if($onerequest['nowplaying'] !=='未再生' ){
               $playingorder = $onerequest['reqorder'];
               break;
           }
       }
       }
       
/**** 作りかけの新しい処理      
       $currentturn = $this->get_insert_turn($this->turnlist,$newsinger,$id);
       $beforesinger = array(); //前のターンリスト
       $findmynameflg = false;
       // 1つ前のターンで自分より前の人の名前のリストを取得
       if($currentturn > 0 ){
           foreach( $this->turnlist[$currentturn-1] as $b_onerequest ){
               if($b_onerequest['singer'] === $newsinger ){
                 // 自分の名前が見つかる以降リストアップ
                 $findmynameflg = true;
               }else{
                 if($findmynameflg === false) 
                 array_push($beforesinger,$b_onerequest);
               }
           }
       }
       // 最初のターン(前のターンがない)→現ターンの一番後ろに追加
       if(count($beforesinger) == 0){
           $turnlast = end($this->turnlist[$currentturn]);
           reset($this->turnlist[$currentturn]);
           return $turnlast['reqorder'] + 1;
       }
       
       //array_unshift($beforesinger,
       
       $insertreqest_key = false;
       // 前ターンリスト最優先から順番にチェック
       foreach ($beforesinger as $beforeorder){
           //今ターンの中をチェック
           foreach( $this->turnlist[$currentturn] as $key => $onerequest){
               // 名前が見つかる
               if($onerequest['singer'] === $beforeorder['singer']){
                  $insertreqest_key = $key;
                  break;
               }
           }
           if($insertreqest_key !== false ) break;
       }

       print '<pre>';
       var_dump($beforesinger );
       print 'insertreqest_key'.$insertreqest_key;
       print '</pre>';

       //もし見つからなかったら、今ターンの最初
       if($insertreqest_key === false ){
         $insertreqest_key = 0;
       }

       print '<pre>';
        print 'insertreqest_key'.$insertreqest_key;
        print '</pre>';
   
       //挿入した場所が未再生でなかったら後ろにずらす
       foreach( $this->turnlist[$currentturn] as $key => $onerequest){

       print '<pre>';
       var_dump($onerequest );
        print '</pre>';

         if($key < $insertreqest_key ) continue;
         $neworder = $onerequest['reqorder'] ;
         if($onerequest['nowplaying'] ==='未再生' ){
            break;
         }
       }
       
       return $neworder;
       
****/  
       $beforeturn = array();
       $beforeturn_1 = array();
       $beforeturn_2 = array();
       ///以下古い処理
       // 今まで自分の名前の入った一番新しいリクエストのorder番号を探す
       $maxmyorder = $this->get_max_reqorder_from_name($newsinger,$id);
       
       foreach($this->turnlist as $turnkey => $oneturn){
       //print "<pre> dump oneturn\n";
       //    var_dump($oneturn);
       //print "</pre>";
           // 1番最初のリクエスト
           //print "ターンに入ります<br>\n";
           if(count($oneturn) == 0){
              //print "このターンは0でした<br>\n";
              return 1;
           }
           $beforeturn_2 = $beforeturn_1;
           $beforeturn_1 = $beforeturn;
           $beforeturn = $oneturn;
           // 自分の最新リクエストより前 -> 次のターン
           if(count($oneturn) > 0 ){
             if(array_key_exists('reqorder', $oneturn[0]) ){
               // print '$oneturn[0][reqorder]:'.$oneturn[0]['reqorder'].'$maxmyorder:'.$maxmyorder.'<br>';
               if($oneturn[0]['reqorder'] < $maxmyorder) continue;
             }
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
               continue;
           }
           // 現在のターンに名前がある → 次のターンへ
           if($this->check_exists_mymember($oneturn,$newsinger,$id) === true){
               // print "このターンに自分の名前がありました".$newsinger." <br>\n";
               // var_dump($oneturn);
               // print " <br>\n";
               continue;
           }
           // 1つ前のターンの順番に従いreqorderを決める

           // 1つ前のターンで自分より前の人の名前のリストを取得
           $mynamefound = false;
           $beforesinger = array(); 
/**
       print "<pre>\n-------- beforeturn_1 list:\n";
       var_dump($beforeturn_1);print "</pre>";
           if(!empty($beforeturn_1)){
               foreach( ($beforeturn_1) as $b_onerequest ){
                print $b_onerequest['songfile'].$b_onerequest['singer'].$newsinger."<br>\n";
                   if($mynamefound === false) {
                       if($b_onerequest['singer'] === $newsinger ){
                           $mynamefound = false;
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
           }
**/
// var_dump($beforeturn_1);
           if( $turnkey > 0 ) {
           //  for( $i = $turnkey - 1 ; $i >= 0 ; $i-- ){
               foreach( ($this->turnlist[$turnkey - 1]) as $b_onerequest ){
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
           //  }
           }
           if($mynamefound === false) {
               $beforesinger = null;
           }
           // とりあえず現ターンの未再生の中の最後の順番にしておく
           $newreqorder = false;
           if(empty($this->turnlist[$turnkey - 1])){
               $newreqorder = $oneturn[count($oneturn)-1]['reqorder']+1;
               //print 'now set'.$newreqorder;
           }else{
           print '<pre>';
           var_dump($oneturn);
           print '</pre>';
           for($i = 0 ; $i < count($oneturn) ; $i++){
             // print  $oneturn[$i]['id'].$oneturn[$i]['reqorder'].'<br>';
               if($oneturn[$i]['nowplaying'] == '未再生' ){
                   $newreqorder = $oneturn[$i]['reqorder'];
                   // print 'now set'.$newreqorder;
                   break;
               }
           }
           }
           if($newreqorder === false) break;
           // print " $newreqorder";
//           print "<pre>\n-------- before singer list:\n";
//           var_dump($beforesinger);print "</pre>";
//           print "<pre>\n-------- oneturn list:\n";
//           var_dump($oneturn);print "</pre>";
           if(!empty($beforesinger)){
               // 前ターンの自分の前の人がいたらその人の次にする
               $cheekedreqorder= $oneturn[count($oneturn)-1]['reqorder']+1;;
               foreach( $oneturn as $onerequest){
                 if($onerequest['nowplaying']==='未再生'){
                     $cheekedreqorder= $onerequest['reqorder'];
                     break;
                 }
               }
               $setvalue = false;
               // 差し込むべきIDを探す。
               $insertid = null;
               foreach( array_reverse ($oneturn) as $key => $onerequest){
                   $cheekedreqorder = $onerequest['reqorder'] + 1;
                   foreach (($beforesinger) as $beforeorder){
                       // print $cheekedreqorder.'onerequest:'.$onerequest['singer'].$onerequest['reqorder'].' beforeorder:'.$beforeorder['singer'].$beforeorder['reqorder']."<br>\n";
                       if($onerequest['singer'] == $beforeorder['singer']){
                           if( $cheekedreqorder == $onerequest['reqorder']){
                               $newreqorder = $cheekedreqorder + 1 ;
                           }else {
                               $newreqorder = $cheekedreqorder ;
                           }
                           
                           // 再生中or再生済みチェック
                           for($i=$key ;$i<count($oneturn); $i++){
                          // print "<pre>".$newreqorder;var_dump($oneturn[$i]);print "</pre>";
                             if(array_key_exists($i,$oneturn) && $oneturn[$i]['nowplaying'] !=='未再生' ){
                                   if($newreqorder <= $oneturn[$i]['reqorder']){
                            //   print "<pre>".$newreqorder;print $oneturn[$i]['reqorder'];print "</pre>";
                                       $newreqorder = $oneturn[$i]['reqorder'] + 1;
                                   }
                             }
                           }
                          //  print "reqorderを".$newreqorder.'にしました';
                           $setvalue = true;
                           break;
                       }
                       
                       //print $cheekedreqorder;
                   }
                   // print $oneturn[0]['reqorder'];
                   if($setvalue === true){
                       break;
                   }
                   if($onerequest['nowplaying']==='未再生'){
                       $cheekedreqorder=$onerequest['reqorder'] ;
                   }
               }
           }else{
             // 最初のターンの場合そのターンの一番後ろにする
             if($newreqorder == 1 ){
               // print "ターンの一番後ろにしました";
               // そのターンの先頭にするときはコメントアウト
               $newreqorder = $oneturn[count($oneturn)-1]['reqorder']+1;
             }else {
                        $newreqorder = $newreqorder;
             }
             
           }
           // 再生中より下にないかチェック
           if( $playingorder !== false ){
              if( $playingorder > $newreqorder ) {
                  return ($playingorder + 1);
              }
           }
           return $newreqorder;
       }
       // print "come max_reqorder".$this->max_reqorder;
       return $this->max_reqorder + 1;
   }


   
   //ターン一覧から、挿入すべきターン番号を返す 
   public function get_insert_turn($turnlist,$newsinger,$id){
       foreach($turnlist as $turnkey => $oneturn){
           foreach($oneturn as $onerequest){
               if($onerequest['nowplaying'] == '未再生' ){
                   // print "このターンに未再生がありました".$onerequest['reqorder']." <br>\n";
                   // このターンに自分の名前があるかどうかのチェック
                   // 現在のターンに名前がある → 次のターンへ
                   if($this->check_exists_mymember($oneturn,$newsinger,$id) === true){
                     continue;
                   }                   
                   return $turnkey;
                   break;
               }
           }
       }
       return false;
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
   
   /* ターンの中に$singer で与えられた名前があるかどうか。$idを指定するとそのIDは除外する */
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
   
   /* $singerのリクエストした一番新しいreqourderを探す */
   /* $woid : 対象としないid値  */
   /* -1 : user not found  */
   public function get_max_reqorder_from_name($newsinger,$woid) {
       $maxreqorder = -1;
       foreach($this->allrequest as $key => $row ){
           if($row['id'] == $woid ) continue;
           if($row['singer'] === $newsinger ) {
               // print $newsinger . ' as '. $row['reqorder'] .'<br>';
               $maxreqorder = max ( $maxreqorder , $row['reqorder'] );
           }
       }
       return $maxreqorder;
   }
}

?>
