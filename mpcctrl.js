var playerurl  = "http://" + location.host + ":13579/command.html"
var playerurl2 = "http://" + location.host + "/mpcctrl.php"

//window.onload = function () {
//    document.body.onclick  = setiframe();
//    stop();
//}

function setiframe(){
    var parentDocument = window.parent.document;
    var myframe = parentDocument.getElementById( 'parentplayerarea' );
    myframe.src ="foobarctl.php";
}
function sleep(time, callback){
  setTimeout(callback, time);
}

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
    var xmlhttp = createXMLHttpRequest();

//play 887
function song_play(){
var request = createXMLHttpRequest();
url= playerurl + "?wm_command=887";
url=playerurl2 + "?cmd=887";
request.open("GET", url, true);
request.send("");
}

//Pause 888
function song_pause(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=889";
url=playerurl2 + "?cmd=889";
request.open("GET", url, true);
request.send("");
}

//Volume Up 907
function song_vup(){
var request = createXMLHttpRequest();
//url=playerurl + "?wm_command=907";
url=playerurl2 + "?cmd=907";
request.open("GET", url, true);
request.send("");
}

//Volume Down 908
function song_vdown(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=908";
url=playerurl2 + "?cmd=908";
request.open("GET", url, true);
request.send("");
}

//Stop & next = Exit 816 => Stop 890
function song_next(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=890";
url=playerurl2 + "?cmd=890";
//request.open("GET", url, true);
//request.send("");
}

//change audio track 952
function song_changeaudio(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=952";
url=playerurl2 + "?cmd=952";
request.open("GET", url, true);
request.send("");
}

//On/Off Subtitle 956
function song_subtitleonnoff(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=956";
url=playerurl2 + "?cmd=956";
request.open("GET", url, true);
request.send("");
}

// Stop 890
function song_stop(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=890";
url=playerurl2 + "?cmd=890";
request.open("GET", url, true);
request.send("");
}

// Audio Delay -10ms 906
function song_audiodelay_m10(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=906";
url=playerurl2 + "?cmd=906";
request.open("GET", url, true);
request.send("");
}

function song_audiodelay_m100(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=906";
url=playerurl2 + "?cmd=delaym100";
  request.open("GET", url, true);
  request.send("");
}

// Audio Delay +10ms 905
function song_audiodelay_p10(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=905";
url=playerurl2 + "?cmd=905";
  request.open("GET", url, true);
  request.send("");
}

function song_audiodelay_p100(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=905";
url=playerurl2 + "?cmd=delayp100";
  request.open("GET", url, true);
  request.send("");
}


// restart this song = Stop and Play
function song_startfirst(){

var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=start_first";
request.open("GET", url, true);
request.send("");

}

// fullscreen 830
function song_fullscreen(){
var request = createXMLHttpRequest();
url=playerurl + "?wm_command=830";
url=playerurl2 + "?cmd=830";
request.open("GET", url, true);
request.send("");
}


// jump_later(large) 904
function jump_later_large(){
var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=904";
request.open("GET", url, true);
request.send("");
}


// jump_before(large) 901
function jump_before_large(){
var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=903";
request.open("GET", url, true);
request.send("");
}

// jump_later(mid) 902
function jump_later(){
var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=902";
request.open("GET", url, true);
request.send("");
}


// jump_before(mid) 901
function jump_before(){
var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=901";
request.open("GET", url, true);
request.send("");
}

// fadeout
function exec_fadeout(){
var request = createXMLHttpRequest();
url=playerurl2 + "?fadeout=1";
request.open("GET", url, true);
request.send("");
}

// common command
function mpccmd_num(cmdnum){
var request = createXMLHttpRequest();
url=playerurl2 + "?cmd=" + cmdnum;
request.open("GET", url, true);
request.send("");
}

// keychange command
function keychange(keycmd){
var request = createXMLHttpRequest();
url=playerurl2 + "?key=" + keycmd;
request.open("GET", url, true);
request.send("");
}
