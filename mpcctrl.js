var playerurl  = "http://" + location.host + ":13579/command.html"
var url      = location.href.split(/#/)[0];
var playerurlbase = url.substring(0, url.lastIndexOf("/"));
var playerurl2 = playerurlbase + "/mpcctrl.php";

var EventSource = window.EventSource || window.MozEventSource;

    function event_initial(){
        if (!EventSource){
            // alert("EventSourceが利用できません。");
            return;
        }
        var source = new EventSource('getcurrentkey_event.php');
        source.onmessage = function(event){
            if (event.data == "Bye"){
                event.target.close();
                // alert('終了しました。');
            }
            temp = document.getElementById("currentkey");
            nowkey = event.data;
            if( nowkey ) {
              if( nowkey == "None" ) {
                  temp.innerHTML = "";
              }
              else {
                if( nowkey > 0 ) {
                  nowkey = "+" + nowkey;
                }
                temp.innerHTML = "現在のキー: " + nowkey;
              }
            }
        };
    }

    function event_initial_player(){
        if (!EventSource){
            // alert("EventSourceが利用できません。");
            return;
        }
        var source_player = new EventSource('player_event.php');
        source_player.onmessage = function(event){
            if (event.data == "Bye"){
                event.target.close();
                // alert('終了しました。');
            }
            var playstatitem = JSON.parse(event.data);
            if(typeof playstatitem.playerstatus !== "undefined") {
                  progresstime_init();
            }
            if(typeof playstatitem.playerprogress !== "undefined") {
                  progresstime_init();
            }
            if(typeof playstatitem.playerkind !== "undefined") {
                  //location.href='playerctrl_portal.php';
            }
        };
    }

window.onload = function () {
//    document.body.onclick  = setiframe();
//    stop();
    event_initial();
    event_initial_player();
}

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
request.onreadystatechange = function() {
    if(request.readyState == 4) {
        if (request.status === 200) {
        showkey();
        }
    }
}
request.send("");
}


// Update Current Key
function showkey(){
var request_sk = createXMLHttpRequest();
url= playerurlbase + "/getcurrentkey.php";
request_sk.open("GET", url, true);
  request_sk.onreadystatechange = function() {
    if(request_sk.readyState == 4) {
      if (request_sk.status === 200) {
        temp = document.getElementById("currentkey");
        nowkey = request_sk.responseText;
        if( nowkey ) {
          if( nowkey > 0 ) {
            nowkey = "+" + nowkey;
          }
          temp.innerHTML = "現在のキー: " + nowkey;
        }
      }
    }
  }    
request_sk.send("");
}

var starttime = (new Date()).getTime();
var curpos = 0;
var length = 0;
var state = 0;
var pbr = 1;
var playingtitle = "";
var AP = 0;
function progresstime_init(nowstate){
          
     if(typeof nowstate !== "undefined") {
        state = nowstate;
     }
     var request_playstat = createXMLHttpRequest();
     url= playerurlbase + "/get_playingstatus_json.php";
     request_playstat.open("GET", url, true);
     request_playstat.onreadystatechange = function() {
         if(request_playstat.readyState == 4) {
           if (request_playstat.status === 200) {
              // console.log('responseText:' + request_playstat.responseText);
              if(request_playstat.responseText.length === 0 ){
                  return;
              }
              var playstat = JSON.parse(request_playstat.responseText);
              starttime = (new Date()).getTime();
              curpos = playstat.playtime;
              length = playstat.totaltime;
              state = playstat.status;
              playingtitle = playstat.playingtitle;
              
              var pg = document.getElementById("proglessbase");
              // console.log('pg.innerHTML:' + pg.innerHTML.replace(/[\t\s]/g, '').length);
              if(pg.innerHTML.replace(/[\t\s]/g, '').length === 0){
                  location.href='playerctrl_portal.php';
                  return;
              }
          
              cp=document.getElementById("time");
              ttp=document.getElementById("total");
              titlename=document.getElementById("songtitle");
              if(playingtitle.length > 0 ){
                titlename.innerHTML = '<p>Now Playing... </p>' + playingtitle;
              }
              ttp.innerHTML = " "+secondsToTS(length,5,true)+" ";
              Live=(length<1);
              
              starttime = starttime-curpos;
              //  console.log('goto progresstime_autoplay state:' + state);
              if (state==2 && pbr!=0) progresstime_autoplay();
              //return true;
           }
         }
     }
     request_playstat.send("");
}

function progresstime_autoplay(a)
{
 
  if(state == 2) {
    clearTimeout(AP);
    AP=setTimeout('progresstime_autoplay()',1000);
    }
  var ct = (new Date()).getTime();
  var cap=pbr*(ct-starttime);
  cap=((cap>length && !Live)?length:(cap<0?0:cap))

  var gg = " "+secondsToTS(cap,5,true)+" ";
  cp.innerHTML = gg;
  // console.log('progresstime_autoplay updatenow gg:' + gg);
  var percent = cap / length;
  var divprogress = document.getElementById("divprogress");
  divprogress.style.width = (percent * 100) + '%'; 
  return true;
}
function pad(number, length)
{
	var str = '' + number;
	while (str.length < length) str = '0' + str;
	return str;
}        
function secondsToTS(a,b,c)
{
	var a1 = Math.floor(a/3600000);
	var a2 = Math.floor(a/60000)%60;
	var a3 = Math.floor(a/1000)%60;
	var a4 = Math.floor(a)%1000;
	var a1s = pad(a1.toString(),2);
	var a2s = pad(a2.toString(),2);
	var a3s = pad(a3.toString(),2);
	var a4s = pad(a4.toString(),3);
	switch (b){
	case 1:	return a1s;
	case 2:	return a2s;
	case 3:	return a3s;
	case 4:	return a4s;
	case 5:	//return a1s+":"+a2s+":"+a3s+"."+a4s;
	case 6:	//return ((a1>0?(a1s+":"):"")+a2s+":"+a3s+"."+a4s);
	case 7:	return a1s+":"+a2s+":"+a3s;
	default: return ((a1>0?(a1s+":"):"")+a2s+":"+a3s);
	}
	return "bahh";
}
