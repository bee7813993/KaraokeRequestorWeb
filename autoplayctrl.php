<?php

require_once 'commonfunc.php';

function stopautoplay()
{
    $execcmd='taskkill /FI "WINDOWTITLE eq karaokeautorun*"';
    exec($execcmd);
}

function startautoplay()
{
    global $config_ini;
    $execcmd='start "karaokeautorun" '.urldecode($config_ini["autoplay_exec"]);
    echo $execcmd;
    exec($execcmd);

}

function checkautoplay()
{
  $pscheck_cmd='tasklist /FI "WINDOWTITLE eq karaokeautorun*" | find /c "cmd.exe"';
  exec($pscheck_cmd, $psresult );
  return $psresult[0];
}

function stopautoplaywithcheck()
{
    $result=checkautoplay();
    if($result != 0 ){
        stopautoplay();
    }
}

$l_karaokeautorunaction = 'none';
if(array_key_exists("karaokeautorunaction", $_REQUEST)) {
    $l_karaokeautorunaction = $_REQUEST["karaokeautorunaction"];
}

$l_nextpage = null;
if(array_key_exists("nextpage", $_REQUEST)) {
    $l_nextpage = $_REQUEST["nextpage"];
}

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
if(!empty($l_nextpage)){
    print '<META http-equiv="refresh" content="1; url='.$l_nextpage.'">'."\n";
    }
?>
<?php 
print_meta_header();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
    
<title>自動起動プログラム制御</title>
</head>
<body>
<?php
shownavigatioinbar();
?>
<script type="text/javascript">
function createXMLHttpRequest() {
  if (window.XMLHttpRequest) {
    return new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    try {
      return new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        return new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e2) {
        return null;
      }
    }
  } else {
    return null;
  }
}

function start_pfwdcmd(){
var request = createXMLHttpRequest();
url="pfwd_exec.php?pfwdstart=1";
request.open("GET", url, true);
request.onreadystatechange = function() {
    if(request.readyState == 4) {
        if (request.status === 200) {
            var request2 = createXMLHttpRequest();
            request2.open("GET", "pfwdstat.php", false);
            request2.send(null);
            var statvalue = JSON.parse(request2.responseText);
            pfwdstat=document.getElementById("pfwdstatus");
            if(statvalue.pfwdstat){
                pfwdstat.innerHTML = "起動中";
                pfwdstat.className = 'bg-success';
            }else {
                pfwdstat.innerHTML = "停止中";
                pfwdstat.className = 'alert-danger';
            }
        }
    }
}
request.send("");
}

function stop_pfwdcmd(){
var request = createXMLHttpRequest();
url="pfwd_exec.php?pfwdstop=1";
request.open("GET", url, true);
request.onreadystatechange = function() {
    if(request.readyState == 4) {
        if (request.status === 200) {
            var request2 = createXMLHttpRequest();
            request2.open("GET", "pfwdstat.php", false);
            request2.send(null);
            var statvalue = JSON.parse(request2.responseText);
            pfwdstat=document.getElementById("pfwdstatus");
            if(statvalue.pfwdstat){
                pfwdstat.innerHTML = "起動中";
                pfwdstat.className = 'bg-success';
            }else {
                pfwdstat.innerHTML = "停止中";
                pfwdstat.className = 'alert-danger';
            }
        }
    }
}
request.send("");
}

</script>
<?php

if($l_karaokeautorunaction == 'start'){
    $org_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 3);
    @file_get_contents('http://localhost/autoplayctrl.php?karaokeautorunaction=start_exec');
    ini_set('default_socket_timeout', $org_timeout);
    
}
if($l_karaokeautorunaction == 'start_exec'){
    startautoplay();
}
if($l_karaokeautorunaction == 'stop'){
    stopautoplaywithcheck();
}

require_once 'pfwdctl.php';
$pfwdavailable = true;
$pfwdinfo = new pfwd();
$pfwdinfo->pfwdpath = urldecode($config_ini["pfwdplace"]);
$pfwdavailable = $pfwdinfo->readpfwdcfg();

$ap = checkautoplay();
//var_dump ($ap);
print '<div id="autoplaystatus ">';
if($ap == 0){
    print '自動再生停止中';
}else{
    print '自動再生実行中';
}
print '</div>';

?>

<form method="GET" >
<input type="hidden" name="karaokeautorunaction" id="karaokeautorunaction"  value="start" />
<input type="submit" value="Start" class="requestconfirm btn btn-default btn-lg"/>
</form>

<form method="GET" >
<input type="hidden" name="karaokeautorunaction" id="karaokeautorunaction"  value="stop" />
<input type="submit" value="Stop" class="requestconfirm btn btn-default btn-lg"/>
</form>

<?php
if($pfwdavailable){
print <<<EOT
  <div class="form-group">
      <label class="control-label" >pfwdプログラム起動停止</label>
      <button type="button" class="btn btn-default" onClick="start_pfwdcmd()" >起動</button>
      <button type="button" class="btn btn-default" onClick="stop_pfwdcmd()" >停止</button>
EOT;
          if($pfwdavailable == false){
              print '<span class="alert-danger" id="pfwdstatus" > 利用不可</span>';
          }
          else if($pfwdinfo->statpfwdcmd()){
              print '<span class="bg-success" id="pfwdstatus" > 起動中</span>';
          } else {
              print '<span class="alert-danger" id="pfwdstatus" >停止中</span>';
          }
      
print <<<EOT
  </div>
EOT;
}
?>

</body>
</html>

