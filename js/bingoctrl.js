$(document).ready(function()
{
    $("form.bingoinput").submit(function()
    {
        var newid = "none";
        // HTML�ł̑��M���L�����Z��
        event.preventDefault();
        
        // ����Ώۂ̃t�H�[���v�f���擾
        var $form = $(this);
        var $script     = $('#nanasycheck');
        
        // ���M�{�^�����擾
        var $button = $form.find('button[type="submit"]');
        var newid = $form.find('input[name="id"]').val();
                /**
                 * Ajax�ʐM���\�b�h
                 * @param type  : HTTP�ʐM�̎��
                 * @param url   : ���N�G�X�g���M���URL
                 * @param data  : �T�[�o�ɑ��M����l
                 */
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            timeout: 10000,  // �P�ʂ̓~���b
            //dataType: 'json',
            
            // ���M�O
            beforeSend: function(xhr, settings) {
                // �{�^���𖳌������A��d���M��h�~
                $button.attr('disabled', true);
            },
            success : function( data ) {
                  window.location.href = 'bingo_openinput.php?id=' + newid;
            },
            // ������
            complete: function(data, xhr, textStatus) {
                // �{�^����L�������A�đ��M������
                $button.attr('disabled', false);
                  window.location.href = 'bingo_openinput.php?id=' + newid;
            },
            /**
            * Ajax�ʐM�����s�����ꍇ�ɌĂяo����郁�\�b�h
            */
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                alert('Error : ' + errorThrown);
            }
        });
        
        //�T�u�~�b�g��A�y�[�W�������[�h���Ȃ��悤�ɂ���
        return false;

    });

});