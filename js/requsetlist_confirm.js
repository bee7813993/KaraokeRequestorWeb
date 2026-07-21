$(document).ready(function()
{
    $("form#requestconfirm").submit(function(event)
    {
        var newid = "none";
        // HTMLでの送信をキャンセル
        event.preventDefault();

        // 操作対象のフォーム要素を取得
        var $form = $(this);
        var $script     = $('#nanasycheck');

        // 送信ボタンを取得
        var $button = $form.find('input[type="submit"]');
        var newname = $('form input[name="freesinger"]').val();
        var existname = $('#singer').val();
        var existname_setting = JSON.parse($script.attr('data-nanasy'));
        var nanasyflg = JSON.parse($script.attr('data-nanasyflg'));

        if (newname == "" && existname == existname_setting ) {
            if(nanasyflg != 1) {
                return false;
            }
        }
        if (newname == "" ){
            newname = existname;
        }

        // タイムアウト時の受理確認中フラグ (complete でのボタン再有効化を抑止する)
        var verifying = false;
        // 受理確認の照合キー (exec.php で songfile は加工されるため fullpath で照合する)
        var fullpath = $form.find('[name="fullpath"]').val() || '';

        function goComplete(showid) {
            var url = 'requestlist_top.php?username=' + newname;
            if (showid) url += '&showid=' + showid;
            window.location.href = url;
        }

        // タイムアウト時: サーバー側では処理が続いていて予約が登録されていることが
        // 多い (そのまま再送させると二重予約になる)。予約一覧で「同じファイル・
        // 同じ名前の未再生予約」を数回確認し、受理済みなら完了画面へ遷移する
        function verifyAccepted(attemptsLeft) {
            $.ajax({
                url: 'api/requests.php',
                type: 'GET',
                data: { limit: 30 },
                dataType: 'json',
                timeout: 5000
            }).always(function(res) {
                var foundid = 0;
                try {
                    var items = (res && res.data && res.data.items) ? res.data.items : [];
                    for (var i = 0; i < items.length; i++) {
                        var it = items[i];
                        if ((it.nowplaying == '未再生' || it.nowplaying == '1')
                            && it.singer == newname
                            && fullpath !== '' && it.fullpath == fullpath) {
                            foundid = it.id;
                            break;
                        }
                    }
                } catch (e) { /* 応答が壊れていたら未確認扱い */ }
                if (foundid) {
                    goComplete(foundid);
                    return;
                }
                if (attemptsLeft > 1) {
                    setTimeout(function(){ verifyAccepted(attemptsLeft - 1); }, 3000);
                    return;
                }
                // 受理を確認できなかった: 盲目的な再送をさせないため、一覧の確認を促す
                verifying = false;
                $button.attr('disabled', false);
                if ($button.data('orig-label')) {
                    $button.val($button.data('orig-label'));
                }
                alert('サーバーの応答がありませんでした。予約が入っている場合があるため、予約一覧で受け付け状況を確認してから、必要ならもう一度お試しください。');
            });
        }

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
            timeout: 30000,  // 単位はミリ秒 (混雑時のサーバー処理を待つ。旧: 10秒)
            //dataType: 'json',

            // 送信前
            beforeSend: function(xhr, settings) {
                // ボタンを無効化し、二重送信を防止
                $button.attr('disabled', true);
                if (!$button.data('orig-label')) {
                    $button.data('orig-label', $button.val());
                }
            },
            success : function( data ) {
                try{
                  newid = JSON.parse(data);
                  window.location.href = 'requestlist_top.php?username=' + newname + '&showid=' + newid.newid;
                } catch(e){
                  window.location.href = 'requestlist_top.php?username=' + newname;
                }
            },
            // 応答後
            complete: function(data, xhr, textStatus) {
                if (verifying) {
                    // タイムアウト後の受理確認中はボタンを無効のまま維持する
                    return;
                }
                // ボタンを有効化し、再送信を許可
                $button.attr('disabled', false);
                try{
                  newid = JSON.parse(data);
//                  window.location.href = 'requestlist_only.php?username=' + newname + '&showid=' + newid.newid;
                } catch(e){
                  // window.location.href = 'requestlist_only.php?username=' + newname;
                }
            },
            /**
            * Ajax通信が失敗した場合に呼び出されるメソッド
            */
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                if (textStatus === 'timeout') {
                    verifying = true;
                    $button.val('予約の受け付けを確認しています...');
                    verifyAccepted(3);
                    return;
                }
                alert('Error : ' + errorThrown);
            }
        });

        //サブミット後、ページをリロードしないようにする
        return false;
    });
});
