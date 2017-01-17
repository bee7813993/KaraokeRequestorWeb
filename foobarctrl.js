var foobarctrlurl_old = "http://" + location.hostname + ":82/karaokectrl/"
var nowplayingurl = "http://" + location.hostname + "/playingsong.php"
var foobarctrlurl = "http://" + location.hostname + "/foobarctl.php"

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
url= foobarctrlurl + "?cmd=Start&param1=0";
request.open("GET", url, true);
request.send("");
}

//Pause 888
function song_pause(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=PlayOrPause&param1=0";
request.open("GET", url, true);
request.send("");
}

//Volume Up 907
function song_vup(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=VolumeUP&param1=0";
request.open("GET", url, true);
request.send("");
}

//Volume Down 908
function song_vdown(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=VolumeDown&param1=0";
request.open("GET", url, true);
request.send("");
}

//Stop & next = Exit 816
function song_next(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=Stop&param1=0";
request.open("GET", url, true);
request.send("");
}


// Stop 890
function song_stop(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=Stop&param1=0";
request.open("GET", url, true);
request.send("");
}


// restart this song = Seek to 0 sec
function song_startfirst(){
var request = createXMLHttpRequest();
url=foobarctrlurl + "?cmd=StartFirst&param1=0";
request.open("GET", url, true);
request.send("");

}


