smms = {
    init:function () {
        $.fn.extend({
            insertAtCaret: function (myValue) {
                var $t = $(this)[0];
                //IE
                if (document.selection) {
                    this.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                    this.focus();
                    $(this).focus();
                } else{
                    //!IE
                    if ($t.selectionStart || $t.selectionStart === "0" ) {
                        var startPos = $t.selectionStart;
                        var endPos = $t.selectionEnd;
                        var scrollTop = $t.scrollTop;
                        // $t.value = $t.value.substring(0, startPos) + myValue + $t.value.substring(endPos, $t.value.length);
                        var tval = $t.value.substring(0, startPos) + myValue + $t.value.substring(endPos, $t.value.length);
                        $(this).val(tval)
                        this.focus();
                        $t.selectionStart = startPos + myValue.length;
                        $t.selectionEnd = startPos + myValue.length;
                        $t.scrollTop = scrollTop;
                    } else {
                        $(this).val(this.val()+myValue)
                        this.focus();
                    }
                }

            }
        })

        $("#zz-img-add").unbind('click').bind('click',function (e){

            $('#zz-img-file').click();
        })
        $('#zz-img-file').change(function () {
            for (var i = 0; i < this.files.length; i++) {
                var f = this.files[i];
                var formData = new FormData();
                formData.append('smfile', f);
                $.ajax({
                    url: smms_url+'/action/multi-upload?do=upload',
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    dataType:'json',
                    beforeSend: function (xhr) {
                        $('#zz-img-add .chevereto-pup-button-icon').hide()
                        $('#zz-img-add .chevereto-pup-button-text').text('upload...')
                    },
                    success: function (res) {
                        if(res.status_code===400){ // hello 图床
                            alert(res.error.message + " ps：第三方网站的锅，开发者不背锅" )
                            $('#zz-img-add .chevereto-pup-button-text').text('上传')
                            return
                        }
                        let imgUrl = res.data && res.data.url?res.data.url:res.image.display_url // hello 图床

                        $('#zz-img-add .chevereto-pup-button-icon').show()
                        $("#zz-img-add .chevereto-pup-button-text").text('上传');
                        $('#zz-img-show').append('<img src="' + imgUrl + '" />');
                        //$('textarea[name="comment"]').val($('textarea[name="comment"]').val() + '<img src="' + res.data.url + '" />').focus();
                        const onecircle_avatar = $("#personal-userAvatar input[name='userAvatar']");
                        if(onecircle_avatar.length > 0){
                            onecircle_avatar.val(imgUrl)
                        }


                        if (typeof comment_selector_!="undefined" && $(comment_selector_).length > 0){
                            $(comment_selector_).insertAtCaret('<img src="' + imgUrl + '" />');
                        }
                        var onecircleIndexInput = $('input[data-addarea]')
                        if (onecircleIndexInput.length > 0){
                            onecircleIndexInput.insertAtCaret(imgUrl)
                            onecircleIndexInput.siblings("button").click()
                        }
                        $('#zz-img-add .chevereto-pup-button-text').text('上传')
                    }
                })
            }
        });
    }
}

window.onload = smms.init()
