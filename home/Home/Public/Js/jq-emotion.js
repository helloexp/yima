/**
* jQuery's jqfaceedit Plugin
*
*  jquery face html edit
*  
*
* @author yuan lin
* @version 0.1
* @copyright Copyright(c) 2010.  
* @date 2010-10-11       
*/
 
(function($) {
    $.fn.jqfaceedit = function(options) {
 
        var defaults = {
            txtAreaObj: options //TextArea对象
        }
        var options = $.extend(defaults, options);
 
        this.each(function(){
            var Obj = $(this);
            $(Obj).bind("click", function(e) {
                var faceHtml = '<div id="message_face_menu" class="facebox" style="position: absolute; display:none"><ul>';
                for (i = 1; i < 105; i++) {
                    faceHtml += '<li Other=' + i + '><img src="/Home/Public/Image/weixin2/emotion/' + i + '.gif"  style="cursor:pointer; position:relative;"   /></li>';
                }
                faceHtml += '</ul></div>';
                var height = $("body").height();
 
                var width = $("body").width();
                faceHtml += '<div id="uchome_face_bg" style="position: absolute; top: 0px; left: 0px; width:' + width + 'px; height: ' + height + 'px; background-color: rgb(255, 255, 255); z-index: 10000; opacity: 0;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0,finishOpacity=100,style=0)"></div>';
                $("body").append(faceHtml);
                $("#uchome_face_bg").bind("click", function(e) {
                    $('#uchome_face_bg').remove();
                    $("#message_face_menu").css("display", "none");
 
                });
                $("#message_face_menu ul >li").bind("click", function() {
                    var id = $(this).attr("Other");
                    var faceText = '[em:' + id + ']';
					var faceImg = "\<\img src='/Home/Public/Image/weixin2/emotion/'" + id + "'.gif' \/>";
                    var TextContent= $("#" + options.txtAreaObj).text($("#" + options.txtAreaObj).val() + faceImg);
					$("#reply_content_0").text() + faceImg;
					//$("editArea").append(TextContent);
					//$(faceImg).appendTo("textarea");
                    $("#message_face_menu").remove();
                    $("#uchome_face_bg").remove();
 
                    var setFocusText = $("#" + options.txtAreaObj);
                    var setFocusTextLeg = setFocusText.val().length;
                    setFocusText.focus(); // 默认使用focus方法聚焦
                    // 判断是否为Ie浏览器
                    if ($.browser.msie) {
                        var txt = setFocusText[0].createTextRange(); // 将传入的控件对象转换为Dom对象，并创建一个TextRange对象
                        txt.moveStart('character', setFocusTextLeg);   // 设置光标显示的位置
                        txt.collapse(true);
                        txt.select();
                    }
                });
                var offset = $(e.target).offset();
                offset.top += $(this).height();
                $("#message_face_menu").css(offset).show();
            });
 
 
            $("#" + options.buttonObj).bind("click", function(e) {
                var rContent = $("#" + options.txtAreaObj).val();
                rContent = rContent.replace(/\[em:/g, '<img src="/Home/Public/Image/weixin2/emotion/');
                rContent = rContent.replace(/\]/g, ".gif />");
                return rContent;
            });
        });
   };
    //私有函数 用于html替换 
    function RepHtml(str) {
        str = str.replace(/\[em:/g, '<img src="/public/images/emotions/');
        str = str.replace(/\]/g, ".gif />");
        return str;
    };
 
    // 定义暴露get html函数   
    $.fn.jqfaceedit.Html = function(obj) {
        var rContent = $("#" + obj).val();
        rContent = RepHtml(rContent);
        return rContent;
    }
})(jQuery);