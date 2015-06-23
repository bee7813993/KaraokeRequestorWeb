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
});       

function RequsetListIP(id,filename){
    var data = {id : id, songfile : filename, up : "up" };
    return false;
}