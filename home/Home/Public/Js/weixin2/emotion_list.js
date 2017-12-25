/**
 * jQuery's jqfaceedit Plugin
 *
 * @author cdm
 * @version 0.1
 * @copyright Copyright(c) 2012.
 * @date 2012-02-20
 */
(function($) {
    var em = [
              {"id":0,"phrase":"[微笑]","url":"0.gif"},
				{"id":1,"phrase":"[撇嘴]","url":"1.gif"},
				{"id":2,"phrase":"[色]","url":"2.gif"},
				{"id":3,"phrase":"[发呆]","url":"3.gif"},
				{"id":4,"phrase":"[得意]","url":"4.gif"},
				{"id":5,"phrase":"[流泪]","url":"5.gif"},
				{"id":6,"phrase":"[害羞]","url":"6.gif"},
				{"id":7,"phrase":"[闭嘴]","url":"7.gif"},
				{"id":8,"phrase":"[睡]","url":"8.gif"},
				{"id":9,"phrase":"[大哭]","url":"9.gif"},
				{"id":10,"phrase":"[尴尬]","url":"10.gif"},
				{"id":11,"phrase":"[发怒]","url":"11.gif"},
				{"id":12,"phrase":"[调皮]","url":"12.gif"},
				{"id":13,"phrase":"[呲牙]","url":"13.gif"},
				{"id":14,"phrase":"[惊讶]","url":"14.gif"},
				{"id":15,"phrase":"[难过]","url":"15.gif"},
				{"id":16,"phrase":"[酷]","url":"16.gif"},
				{"id":17,"phrase":"[冷汗]","url":"17.gif"},
				{"id":18,"phrase":"[抓狂]","url":"18.gif"},
				{"id":19,"phrase":"[吐]","url":"19.gif"},
				{"id":20,"phrase":"[偷笑]","url":"20.gif"},
				{"id":21,"phrase":"[可爱]","url":"21.gif"},
				{"id":22,"phrase":"[白眼]","url":"22.gif"},
				{"id":23,"phrase":"[傲慢]","url":"23.gif"},
				{"id":24,"phrase":"[饥饿]","url":"24.gif"},
				{"id":25,"phrase":"[困]","url":"25.gif"},
				{"id":26,"phrase":"[惊恐]","url":"26.gif"},
				{"id":27,"phrase":"[流汗]","url":"27.gif"},
				{"id":28,"phrase":"[憨笑]","url":"28.gif"},
				{"id":29,"phrase":"[大兵]","url":"29.gif"},
				{"id":30,"phrase":"[奋斗]","url":"30.gif"},
				{"id":31,"phrase":"[咒骂]","url":"31.gif"},
				{"id":32,"phrase":"[疑问]","url":"32.gif"},
				{"id":33,"phrase":"[嘘]","url":"33.gif"},
				{"id":34,"phrase":"[晕]","url":"34.gif"},
				{"id":35,"phrase":"[折磨]","url":"35.gif"},
				{"id":36,"phrase":"[衰]","url":"36.gif"},
				{"id":37,"phrase":"[骷髅]","url":"37.gif"},
				{"id":38,"phrase":"[敲打]","url":"38.gif"},
				{"id":39,"phrase":"[再见]","url":"39.gif"},
				{"id":40,"phrase":"[擦汗]","url":"40.gif"},
				{"id":41,"phrase":"[抠鼻]","url":"41.gif"},
				{"id":42,"phrase":"[鼓掌]","url":"42.gif"},
				{"id":43,"phrase":"[糗大了]","url":"43.gif"},
				{"id":44,"phrase":"[坏笑]","url":"44.gif"},
				{"id":45,"phrase":"[左哼哼]","url":"45.gif"},
				{"id":46,"phrase":"[右哼哼]","url":"46.gif"},
				{"id":47,"phrase":"[哈欠]","url":"47.gif"},
				{"id":48,"phrase":"[鄙视]","url":"48.gif"},
				{"id":49,"phrase":"[委屈]","url":"49.gif"},
				{"id":50,"phrase":"[快哭了]","url":"50.gif"},
				{"id":51,"phrase":"[阴险]","url":"51.gif"},
				{"id":52,"phrase":"[亲亲]","url":"52.gif"},
				{"id":53,"phrase":"[吓]","url":"53.gif"},
				{"id":54,"phrase":"[可怜]","url":"54.gif"},
				{"id":55,"phrase":"[菜刀]","url":"55.gif"},
				{"id":56,"phrase":"[西瓜]","url":"56.gif"},
				{"id":57,"phrase":"[啤酒]","url":"57.gif"},
				{"id":58,"phrase":"[篮球]","url":"58.gif"},
				{"id":59,"phrase":"[乒乓]","url":"59.gif"},
				{"id":60,"phrase":"[咖啡]","url":"60.gif"},
				{"id":61,"phrase":"[饭]","url":"61.gif"},
				{"id":62,"phrase":"[猪头]","url":"62.gif"},
				{"id":63,"phrase":"[玫瑰]","url":"63.gif"},
				{"id":64,"phrase":"[凋谢]","url":"64.gif"},
				{"id":65,"phrase":"[示爱]","url":"65.gif"},
				{"id":66,"phrase":"[爱心]","url":"66.gif"},
				{"id":67,"phrase":"[心碎]","url":"67.gif"},
				{"id":68,"phrase":"[蛋糕]","url":"68.gif"},
				{"id":69,"phrase":"[闪电]","url":"69.gif"},
				{"id":70,"phrase":"[炸弹]","url":"70.gif"},
				{"id":71,"phrase":"[刀]","url":"71.gif"},
				{"id":72,"phrase":"[足球]","url":"72.gif"},
				{"id":73,"phrase":"[瓢虫]","url":"73.gif"},
				{"id":74,"phrase":"[便便]","url":"74.gif"},
				{"id":75,"phrase":"[月亮]","url":"75.gif"},
				{"id":76,"phrase":"[太阳]","url":"76.gif"},
				{"id":77,"phrase":"[礼物]","url":"77.gif"},
				{"id":78,"phrase":"[拥抱]","url":"78.gif"},
				{"id":79,"phrase":"[强]","url":"79.gif"},
				{"id":80,"phrase":"[弱]","url":"80.gif"},
				{"id":81,"phrase":"[握手]","url":"81.gif"},
				{"id":82,"phrase":"[胜利]","url":"82.gif"},
				{"id":83,"phrase":"[抱拳]","url":"83.gif"},
				{"id":84,"phrase":"[勾引]","url":"84.gif"},
				{"id":85,"phrase":"[拳头]","url":"85.gif"},
				{"id":86,"phrase":"[差劲]","url":"86.gif"},
				{"id":87,"phrase":"[爱你]","url":"87.gif"},
				{"id":88,"phrase":"[NO]","url":"88.gif"},
				{"id":89,"phrase":"[OK]","url":"89.gif"},
				{"id":90,"phrase":"[爱情]","url":"90.gif"},
				{"id":91,"phrase":"[飞吻]","url":"91.gif"},
				{"id":92,"phrase":"[跳跳]","url":"92.gif"},
				{"id":93,"phrase":"[发抖]","url":"93.gif"},
				{"id":94,"phrase":"[怄火]","url":"94.gif"},
				{"id":95,"phrase":"[转圈]","url":"95.gif"},
				{"id":96,"phrase":"[磕头]","url":"96.gif"},
				{"id":97,"phrase":"[回头]","url":"97.gif"},
				{"id":98,"phrase":"[跳绳]","url":"98.gif"},
				{"id":99,"phrase":"[挥手]","url":"99.gif"},
				{"id":100,"phrase":"[激动]","url":"100.gif"},
				{"id":101,"phrase":"[街舞]","url":"101.gif"},
				{"id":102,"phrase":"[献吻]","url":"102.gif"},
				{"id":103,"phrase":"[左太极]","url":"103.gif"},
				{"id":104,"phrase":"[右太极]","url":"104.gif"},
          ];
    $.fn.extend({
    jqfaceedit : function(options) {
        var defaults = {
            txtAreaObj : '', //TextArea对象
            containerObj : '', //表情框父对象
            emotions : em,//表情信息json格式，id表情排序号 phrase表情使用的替代短语url表情文件名
            imageurl: '/Home/Public/Image/weixin2/emotion/',
            top : 0, //相对偏移
            left : 0 //相对偏移
        };
        var options = $.extend(defaults, options);
         
        return this.each(function() {
 
            var Obj = $(this);
            var container = options.containerObj;
            $(Obj).bind("click", function(e) {
                e.stopPropagation();
                var faceHtml = '<div id="face">';
                faceHtml += '<div id="texttb"><a class="f_close" title="关闭" href="javascript:void(0);"></a></div>';
                faceHtml += '<div id="facebox">';
                faceHtml += '<div id="face_detail" class="facebox clearfix"><ul>';
 
                for( i = 0; i < options.emotions.length; i++) {
                    faceHtml += '<li text=' + options.emotions[i].phrase + ' type=' + i + '><img title=' + options.emotions[i].phrase + ' src="'+options.imageurl + options.emotions[i].url + '"  style="cursor:pointer; position:relative;"   /></li>';
                }
                faceHtml += '</ul></div>';
                faceHtml += '</div><div class="arrow arrow_t"></div></div>';
 
                container.find('#face').remove();
                container.append(faceHtml)
 
                container.find("#face_detail ul >li").bind("click", function(e) {
                    var txt = $(this).attr("text");
                    var faceText = txt;
 
                    options.txtAreaObj.val(options.txtAreaObj.val() + faceText);
                    container.find("#face").remove();
 
                    var setFocusText = options.txtAreaObj;
                    var setFocusTextLeg = setFocusText.val().length;
                    setFocusText.focus();
                    // 默认使用focus方法聚焦
                    // 判断是否为Ie浏览器
                    if($.browser.msie) {
                        var txt = setFocusText[0].createTextRange();
                        // 将传入的控件对象转换为Dom对象，并创建一个TextRange对象
                        txt.moveStart('character', setFocusTextLeg);
                        // 设置光标显示的位置
                        txt.collapse(true);
                        txt.select();
                    }
                });
                //关闭表情框
                container.find(".f_close").bind("click", function() {
                    container.find("#face").remove();
                });
                //处理js事件冒泡问题
                $('body').bind("click", function(e) {
                    e.stopPropagation();
                    container.find('#face').remove();
                });
                container.find('#face').bind("click",function(e){
                    e.stopPropagation();
                });
 
                var offset = $(e.target).offset();
                offset.top += options.top;
                offset.left += options.left;
                container.find("#face").css(offset).show();
            });
        });
    },
    //表情文字符号转换为html格式
    emotionsToHtml : function(options) {
        return this.each(function() {
            var msgObj = $(this);
            var rContent = msgObj.html();
 
            var regx=/(\[[\u4e00-\u9fa5]*\w*\])*/g;//正则查找“[]”格式
            var rs=rContent.match(regx);
              
            for( i = 0; i < rs.length; i++) {
                for( n=0; n< em.length; n++ ){
                    if(em[n].phrase == rs[i]){ 
                        var t = "<img src='/public/images/emotions/"+em[n].url+"' />";
                        rContent = rContent.replace(rs[i],t);
                        break;
                    }
                }
            }
            msgObj.html(rContent);
        });
    }
    })
})(jQuery);
