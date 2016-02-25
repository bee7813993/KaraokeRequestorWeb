$(document).ready(function()
{
    $("form#requestconfirm").submit(function()
    {
        // HTMLでの送信をキャンセル
        event.preventDefault();
        
        // 操作対象のフォーム要素を取得
        var $form = $(this);
        
        // 送信ボタンを取得
        var $button = $form.find('input[type="submit"]');
        
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
            
            // 送信前
            beforeSend: function(xhr, settings) {
                // ボタンを無効化し、二重送信を防止
                $button.attr('disabled', true);
            },
            // 応答後
            complete: function(xhr, textStatus) {
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

