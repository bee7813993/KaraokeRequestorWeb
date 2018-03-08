<?php

$lister_dbpath = "List.sqlite3";
if(array_key_exists("lister_dbpath", $_REQUEST)) {
    $lister_dbpath = $_REQUEST["lister_dbpath"];
}

$program_category = "";
if(array_key_exists("program_category", $_REQUEST)) {
    $program_category = $_REQUEST["program_category"];
}

$list = "";
if(array_key_exists("list", $_REQUEST)) {
    $list = $_REQUEST["list"];
}

// DB初期化
$listerdbfile = $lister_dbpath;
$listerdb = new PDO('sqlite:'. $listerdbfile);


if(!empty($list)) {

  $sql = 'select DISTINCT program_category from t_found ;';
  $select = $listerdb->query($sql);
  if(!$select){
       print_r($listerdb->errorInfo());
  }

  $alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
  $select->closeCursor();
  if(!$alldbdata){
       print_r($db->errorInfo());
  }

  $returnarray = $alldbdata;
  $json = json_encode($returnarray,JSON_PRETTY_PRINT);

}else {
  // 検索条件
  $select_where = "";
  if( !empty($program_category )  ) {
      if($program_category === 'ISNULL' ) {
          $select_where = $select_where . ' WHERE program_category IS NULL ORDER BY found_head ASC';
      }else{
          $select_where = $select_where . ' WHERE program_category ='. $listerdb->quote($program_category) .'ORDER BY found_head ASC';
      }
  }

  $sql = 'select DISTINCT found_head from t_found '. $select_where.';';
  $select = $listerdb->query($sql);
  if(!$select){
       print_r($listerdb->errorInfo());
  }
  $alldbdata = $select->fetchAll(PDO::FETCH_ASSOC);
  $select->closeCursor();
  if(!$alldbdata){
       print_r($db->errorInfo());
  }

  $returnarray = array( "program_category" => $program_category, "data" => $alldbdata);
  $json = json_encode($returnarray,JSON_PRETTY_PRINT);
}

// print '<pre>';
print $json;
// print '</pre>';
?>