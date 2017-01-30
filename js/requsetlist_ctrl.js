$(document).ready(function()
{
    $("input.requestid").click(function()
    {
        var data = {id : $('input.requestid').val(), songfile : $('#requestsongfile').val(), up : "up" };
        
        alert('come ue');
                /**
                 * Ajax通信メソッド
                 * @param type  : HTTP通信の種類
                 * @param url   : リクエスト送信先のURL
                 * @param data  : サーバに送信する値
                 */
        $.ajax({
            type: "POST",
            url: "delete.php",
            data: data,
            
            /**
            * Ajax通信が成功した場合に呼び出されるメソッド
            */
            success: function(data, dataType)
            {
                requestTable.ajax.reload();
            },
            /**
            * Ajax通信が失敗した場合に呼び出されるメソッド
            */
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                alert('Error : ' + errorThrown);
            }
        });
        
        //サブミット後、ページをリロードしないようにする
        return false;
    });
    
    
    // from http://ginpen.com/2013/05/07/jquery-ajax-form/
    $(".sendcomment").submit(function(event){
        // HTMLでの送信をキャンセル
        event.preventDefault();
        
        // 操作対象のフォーム要素を取得
        var $form = $(this);
        
        // 送信ボタンを取得
        var $button = $form.find('button');
        
        // 送信
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            timeout: 10000,  // 単位はミリ秒
            
            // 送信前
            beforeSend: function(xhr, settings) {
                // ボタンを無効化し、二重送信を防止
                $button.attr('disabled', true);
            },
            
            // 応答後
            complete: function(xhr, textStatus) {
                // ボタンを有効化し、再送信を許可
                $button.attr('disabled', false);
            },
            
            // 通信失敗時の処理
            error: function(xhr, textStatus, error) {
                alert('NG...');
            }
        });
    });



});       


function RequsetListIP(id,filename){
    var data = {id : id, songfile : filename, up : "up" };
    return false;
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

function moverequestlist(myel,id,kind,songfile){

  var table = $('#request_table').DataTable();
  var request = createXMLHttpRequest();
  myel.setAttribute('disabled', true);
  url="delete.php?id=" + id + "&" + kind + "=" + kind + "&songfile=" + songfile;
  request.open("GET", url, true);
  request.onreadystatechange = function() {
      if (request.readyState == 4 && request.status == 200) {
          table.ajax.reload();
          myel.setAttribute('disabled', false);
      }
  }
  request.send("");
  
}


var xmlhttp = createXMLHttpRequest();

function changerequeststatus(myel){

  var id = myel.form.id.value;
  var songfile = myel.form.songfile.value;
  var nowplaying = myel.form.nowplaying.value;
  var url;
  var table = $('#request_table').DataTable();
  
  myel.setAttribute('disabled', true);
  url = "changeplaystatus.php?id=" + id + "&songfile=" + songfile + "&nowplaying=" +nowplaying;
  var request = createXMLHttpRequest();
  request.open("GET", url, true);
  request.onreadystatechange = function() {
      if (request.readyState == 4 && request.status == 200) {
          table.ajax.reload();
          myel.setAttribute('disabled', false);
      }
  }
  request.send("");
  return false;
  
}

function song_end(myel, id){
  var url;
  var table = $('#request_table').DataTable();
  
  myel.setAttribute('disabled', true);
  url = "playerctrl_portal.php?songnext=1";
  var request = createXMLHttpRequest();
  request.open("GET", url, true);
  request.onreadystatechange = function() {
      if (request.readyState == 4 && request.status == 200) {
          table.ajax.reload();
          myel.setAttribute('disabled', false);
      }
  }
  request.send("");
  return false;
}

// コメント編集および削除modal表示時のリロード抑制
$(function () {
        $('#request_table').on('show.bs.modal', '.modal', function (event) {
            storeautoreloadcheck = $('#autoreload').prop('checked');
            $('#autoreload').prop('checked',false);
        });
        $('#request_table').on('hide.bs.modal', '.modal', function (event) {
            $('#autoreload').prop('checked',storeautoreloadcheck);
        });
});

$(function () {
        
        $('#request_table').on('submit', '.sendnomove',  function (event){
            event.preventDefault();
            var $form = $(this);
            
            var $button = $form.find('input[type="submit"]');
            
            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $form.serialize(),
                timeout: 10000,  // 単位はミリ秒
                
                // 送信前
                beforeSend: function(xhr, settings) {
                    // ボタンを無効化し、二重送信を防止
                    $button.attr('disabled', true);
                },
                // 応答後
                complete: function(data, xhr, textStatus) {
                    // ボタンを有効化し、再送信を許可
                    var table = $('#request_table').DataTable();
                    $button.attr('disabled', false);
                    $('div.modal').modal('hide');
                    table.ajax.reload();
                },
                /**
                * Ajax通信が失敗した場合に呼び出されるメソッド
                */
                error: function(XMLHttpRequest, textStatus, errorThrown)
                {
                    alert('Error : ' + errorThrown);
                }
                
        });
        //サブミット後、ページをリロードしないようにする
        return false;
    });
});



