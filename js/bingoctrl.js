$(document).ready(function()
{
    $("form.bingoinput").submit(function(event)
    {
        var newid = "none";
        // HTMLでの送信をキャンセル
        event.preventDefault();
        
        // 操作対象のフォーム要素を取得
        var $form = $(this);
        var $script     = $('#nanasycheck');
        
        // 送信ボタンを取得
        var $button = $form.find('button[type="submit"]');
        var newid = $form.find('input[name="id"]').val();
                /**
                 * Ajax通信メソッド
                 * @param type  : HTTP通信の種類
                 * @param url   : リクエスト送信先のURL
                 * @param data  : サーバに送信する値
                 */
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            timeout: 10000,  // 単位はミリ秒
            //dataType: 'json',
            
            // 送信前
            beforeSend: function(xhr, settings) {
                // ボタンを無効化し、二重送信を防止
                $button.attr('disabled', true);
            },
            success : function( data ) {
                  window.location.href = 'requestlist_only.php';
            },
            // 応答後
            complete: function(data, xhr, textStatus) {
                // ボタンを有効化し、再送信を許可
                $button.attr('disabled', false);
                  window.location.href = 'requestlist_only.php';
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