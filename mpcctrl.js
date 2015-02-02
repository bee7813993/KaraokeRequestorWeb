
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
url="http://localhost:13579/command.html?wm_command=887";
request.open("GET", url, true);
request.send("");
}

//Pause 888
function song_pause(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=888";
request.open("GET", url, true);
request.send("");
}

//Volume Up 907
function song_vup(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=907";
request.open("GET", url, true);
request.send("");
}

//Volume Down 908
function song_vdown(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=908";
request.open("GET", url, true);
request.send("");
}

//Stop & next = Exit 816
function song_next(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=816";
request.open("GET", url, true);
request.send("");
}

//change audio track 952
function song_changeaudio(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=952";
request.open("GET", url, true);
request.send("");
}

//On/Off Subtitle 955
function song_subtitleonnoff(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=955";
request.open("GET", url, true);
request.send("");
}

// Stop 890
function song_stop(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=890";
request.open("GET", url, true);
request.send("");
}

// Audio Delay -10ms 906
function song_audiodelay_m10(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=906";
request.open("GET", url, true);
request.send("");
}

// Audio Delay +10ms 905
function song_audiodelay_p10(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=905";
request.open("GET", url, true);
request.send("");
}


// restart this song = Stop and Play
function song_startfirst(){

song_stop();
sleep(500, song_play());

}

// fullscreen 830
function song_fullscreen(){
var request = createXMLHttpRequest();
url="http://localhost:13579/command.html?wm_command=830";
request.open("GET", url, true);
request.send("");
}
