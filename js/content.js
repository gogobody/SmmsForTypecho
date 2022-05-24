var pages = 2;
jQuery(document).ready(function ($) {

    $('#admin-img-file').change(function () {
        var label = $(".admin-upload-img label")
        label.text("上传中...")
        var len = this.files.length
        var cnt = 0;
        var ajaxbg = $("#background,#progressBar");
        if (ajaxbg){
            ajaxbg.show()
        }
        for (var i = 0; i < len; i++) {
            var f = this.files[i];
            var formData = new FormData();
            formData.append('smfile', f);
            $.ajax({
                url: smms_url+'/action/multi-upload?do=upload',
                type: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                dataType: 'json',
                success: function (res) {

                    if (res.data && res.data.url){
                        cnt++;
                        $('textarea[name="content"]').insertAtCaret('<img class="aligncenter" src="' + res.data.url + '" />');
                        $("html").find("iframe").contents().find("body").append('<img class="aligncenter" src="' + res.data.url + '" />');
                        if (cnt === len){
                            alert("上传成功！")
                            label.text("上传")
                            location.reload()
                        }

                    }else if (res.error && res.error){
                        alert("接口返回："+ res.error.message)
                    }else if (res.success && res.image.url){
                        cnt++;
                        $('textarea[name="content"]').insertAtCaret('<img class="aligncenter" src="' + res.image.url + '" />');
                        $("html").find("iframe").contents().find("body").append('<img class="aligncenter" src="' + res.image.url + '" />');
                        if (cnt === len){
                            alert("上传成功！")
                            label.text("上传")
                            location.reload()
                        }
                    }else{
                        if (res.code === "unauthorized"){
                            alert('smms现在必须登录才能上传')
                        }
                        console.log(res)
                    }
                    label.text("上传")
                    ajaxbg.hide()

                },
                error:function (e) {
                    label.text("上传")
                    ajaxbg.hide()


                }
            })
        }
    });

    //加载图片
    $("#toggleModal").click(function () {
        pages = 2
        $("#img_list > li").remove()
        $.ajax({
            url: smms_url+'/action/multi-upload?do=list',
            type: 'GET',
            cache: false,
            dataType: 'json',
            success: function (data) {
                for (var x in data) {
                    $("#img_list").append('<li><img id="modal-image-' + x + '" class="modal-image" src="' + data[x].url + '" /></li>')
                }
                if (data.length < 10) {
                    $('#upload-btn').css('display', 'none');
                } else {
                    $('#upload-btn').css('display', 'inline-block');
                }
            }
        })
    })
    $("#upload-btn").click(function () {

        $.ajax({
            url: smms_url+'/action/multi-upload?do=list',
            type: 'GET',
            cache: false,
            dataType: 'json',
            data: {
                pages: pages
            },
            success: function (data) {
                if (data.length !== 0) {
                    for (var x in data) {
                        $("#img_list").append('<li><img id="modal-image-' + x + '" class="modal-image" src="' + data[x].url + '" /></li>')

                    }
                    pages++
                } else {
                    $('#upload-btn').css('display', 'none');
                    alert("已加载完全部图片")
                }
            }
        })
    })

    $(document).mouseup(function (e) {
        var _con = $('.modal');   // 设置目标区域
        if (!_con.is(e.target) && _con.has(e.target).length === 0) { // Mark 1
            $(".modal").css('display', 'none')
        }
    });


    //插入图片
    $("#img_list").on('click', 'img', function (e) {
        $('textarea[name="text"]').insertAtCaret('<img class="aligncenter" src="' + e.target.src + '" />');
        $("html").find("iframe").contents().find("body").append('<img class="aligncenter" src="' + e.target.src + '" />');
    })

    $.fn.extend({
        insertAtCaret: function (myValue) {
            var $t = $(this)[0];
            if(!$t) return ;
            //IE  
            if (document.selection) {
                this.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                this.focus();
            } else
                //!IE  
                if ('selectionStart' in $t || $t.selectionStart || $t.selectionStart === "0") {
                    var startPos = $t.selectionStart;
                    var endPos = $t.selectionEnd;
                    var scrollTop = $t.scrollTop;
                    $t.value = $t.value.substring(0, startPos) + myValue + $t.value.substring(endPos, $t.value.length);
                    this.focus();
                    $t.selectionStart = startPos + myValue.length;
                    $t.selectionEnd = startPos + myValue.length;
                    $t.scrollTop = scrollTop;
                } else {
                    this.value += myValue;
                    this.focus();
                }
        }
    });
})
