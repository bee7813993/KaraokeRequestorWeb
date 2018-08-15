<?php
function headerlistcheck($oneheaderlist,$headerlist){
   $headercount = 0;
   foreach($oneheaderlist as $oneheader ){
       foreach($headerlist as $header ){
           if($oneheader == $header ) $headercount ++;
       }
   }
   return $headercount;
}

function headerlistcheck_column($oneheaderlist,$headerlist,$key){
   $headercount = 0;
   foreach($oneheaderlist as $oneheader ){
       foreach($headerlist as $header ){
           if($oneheader == $header[$key] ) $headercount ++;
       }
   }
   return $headercount;
}


function showuppermenu($target,$linkoption){
print '<div class="container  ">';
print '  <div class="row ">';
print '    <div class="col-xs-3 col-md-3  ">';
print '      <a href="search_listerdb_program_index.php?'.$linkoption.'" class="btn ';
if($target == 'program_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >作品名 </a>';

print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
//print '      <a href="search_listerdb_artist.php?'.$linkoption.'" class="btn ';
print '      <a href="search_listerdb_column_index.php?target=song_artist&'.$linkoption.'" class="btn ';
if($target == 'song_artist' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >歌手名 </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3">';
print '      <a href="search_listerdb_column_index.php?target=maker_name&'.$linkoption.'" class="btn ';
if($target == 'maker_name' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >制作会社 </a>';
print '    </div>';
print '    <div class="col-xs-3 col-md-3 " >';
print '      <a style="white-space: normal;" href="search_listerdb_filename_index.php?'.$linkoption.'" class="btn ';
if($target == 'filename' ) {
print 'btn-primary';
}else {
print 'btn-default';
}
print ' center-block" >検索 （ファイル名など） </a>';
print '    </div>';
print '  </div>';
print '</div>';

    
}

function buildgetquery($queries){
   $result = "";
   foreach($queries as $key => $value ){
       if(strlen($result) > 0 ) {
          $result .= '&';
       }
       $result .= $key.'='.urlencode($value);
   }
   return $result;
}
?>