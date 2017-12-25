(function($) {
    $.fn.hoverDelay = function(fnOver, fnOut,timeIn,timeOut) {

                var timeIn = timeIn || 100,
                    timeOut = timeOut || 500,
                    fnOut = fnOut || fnOver;

                var inTimer = [],outTimer=[];
                
            return this.each(function(i) {
                $(this).mouseenter(function() {
                        var that = this;
                        clearTimeout(outTimer[i]);
                        inTimer[i] = setTimeout(function() {
                            fnOver.apply(that);
                        }, timeIn);
                  }).mouseleave( function() {
                        var that = this;
                        clearTimeout(inTimer[i]);
                        outTimer[i] = setTimeout(function() {
                            fnOut.apply(that)
                        }, timeOut);
                 });
        })
    };

})(jQuery);
var Gformfn = true;
$(document).ready(function(e){//页面加载时载入
	autocontant();
	IEbrowser();
	uicontent();			//标签切换
	go_history();
	Wcanaltabon();
	Wmaintabon();
	Service();
	Msgtabon();
	scrollTop();
	header();
	newMsg();
	//intcommunity();
	if($(".Gform").length>=1){
		Gform();
	}
	window.onload=function(){
		getHeight();
		windowheight();
	};

	$('.artLoad').click(function(){
        if($('#artLoadDiv').length == 0){
            $('<div id="artLoadDiv"></div>').appendTo('body');
        }
        $("#artLoadDiv").load($(this).attr('href'));
        return false;
    });

	$('.artD').click(function(){
		var t = $(this), url = t.data('href'), title = t.data('title'), width = t.data('width')||700, height = t.data('height')||500;
		art.dialog.open(url, {
			title:title,
			lock: true,
			width:width,
			height:height
		});
	});
	
	$("body").on("change",".Gform input,textarea",function(){
		var maxLength = $(this).next("span.maxTips").attr("data-max");
		var text = $(this).is('div') ? $(this).html() : $(this).val();
		if(text==""){text = $(this).text()};
		if(!maxLength){return false;}
		if(text.length <= maxLength){
			$(this).next("span.maxTips").removeClass("erro").html(text.length+"/"+maxLength);
		}else{
			$(this).next("span.maxTips").addClass("erro").html(text.length+"/"+maxLength);
		};
	});
	
	$("#search_padded li").live("click",function(){
		var lival;
		lival = $(this).text();
		$("#search").val(lival);
		$("body").on("change",".Gform input,textarea",function(){
			var maxLength = $(this).next("span.maxTips").attr("data-max");
			var text = $(this).is('div') ? $(this).html() : $(this).val();
			if(text==""){text = $(this).text()};
			if(!maxLength){return false;}
			if(text.length <= maxLength){
				$(this).next("span.maxTips").removeClass("erro").html(text.length+"/"+maxLength);
			}else{
				$(this).next("span.maxTips").addClass("erro").html(text.length+"/"+maxLength);
			};
		});
		$("#search_padded").hide();
	});
	
}); 
$(window).resize(function(e) {//浏览器窗口变化自动载入
	windowheight();
	scrollTop();
}); 
function autocontant(){
	var contanttime=0;
	var contantInt=function(){
			contanttime=contanttime+1;
			if(contanttime>=60){
				setTimeout("$('.autoonlinecontant i').css('background-color','#f46018')",100);
				setTimeout("$('.autoonlinecontant i').css('background-color','#b3d9f7')",200);
				setTimeout("$('.autoonlinecontant i').css('background-color','#f46018')",300);
				setTimeout("$('.autoonlinecontant i').css('background-color','#b3d9f7')",400);
				setTimeout("$('.autoonlinecontant i').css('background-color','#f46018')",500);
				setTimeout("$('.autoonlinecontant i').css('background-color','#b3d9f7')",600);
				setTimeout("$('.autoonlinecontant i').css('background-color','#f46018')",700);
				setTimeout("$('.autoonlinecontant i').css('background-color','#b3d9f7')",800);
				contanttime=0;
		}
		return false;
	};
	var contant=setInterval(contantInt,1000);
}
function windowheight(){
	var windowheight=$(window).height()-140;
	$("#main").height("auto");
	$(".subcon").height("auto");
	var leftMenuheiht=$(".left-Menu").height();
	if(!leftMenuheiht){leftMenuheiht=$(".account-Menu").height();};
	if(!leftMenuheiht){leftMenuheiht=$(".Menu").height();};
	if($(".subcon").height()<leftMenuheiht){
		$(".subcon").height(leftMenuheiht);
	}
	if($("#main").height()<windowheight){
		$("#main").height(windowheight); 
	}else{
		$("#main").height("auto"); 
	}
}
function IEbrowser(){
	var ua = navigator.userAgent;
	ua = ua.toLowerCase();
	var match = /(webkit)[ \/]([\w.]+)/.exec(ua) ||
	/(opera)(?:.*version)?[ \/]([\w.]+)/.exec(ua) ||
	/(msie) ([\w.]+)/.exec(ua) ||
	!/compatible/.test(ua) && /(mozilla)(?:.*? rv:([\w.]+))?/.exec(ua) ||
	[];
    switch(match[1]){
     case "msie":      //ie
      if (parseInt(match[2]) === 6) {
		$("#wrapper").append("<div id='IEbrowser' class='dn' style='background:#f7f7f7; width:80%; position:absolute; top:0px; left:10%;height:100px;overflow:hidden;line-height:100px; font-size:20px;text-align:center;z-index:1000000; border:solid 1px #999;'>您当前浏览器版本为IE6，建议您使用:<span style='color:#006ecc'>极速模式</span>或<span style='color:#006ecc'>IE7</span>以上版本或<span style='color:#006ecc'>chrome/farefox</span>等标准浏览器</div>");
		$("#IEbrowser").slideDown("slow");
		setTimeout("$('#IEbrowser').slideUp('slow')",5000)
	}
      break;
	}
}
function header(){
	$(".useropr li span:last").css("border","none");
	$(".usercenter").hoverDelay(
		function(){
			$(".useropr").fadeIn();
		},
		function(){
			$(".useropr").fadeOut();
		}
	);
}
//判断是否有更新公告
function newMsg(){
	var onlinecontant=$("#onlinecontant").text() ? true : false;
	if($("#onlinecontant").text()=="0"){onlinecontant=false};
    if(onlinecontant){$(".care i").addClass("newMsg")}
}
function uicontent(){
		$("div#global-libs p").not("#global-libs-content p").click(function(){
			$("div#global-libs p").removeClass("hover");
			for(i=0;i<$("div#global-libs p").not("#global-libs-content p").length;i++){
				$("div#global-libs-content #globallibscontent:eq("+i+")").removeClass("hover");
				$("div#global-libs-content #globallibscontent:eq("+i+")").addClass("hide");
			}
			$(this).addClass("hover");
			$("div#global-libs-content #globallibscontent:eq("+$(this).index()+")").addClass("hover");
			windowheight();
    	});
}

function Wcanaltabon(){
		$("#Wcanal-tabon .Wcanal-tab-title p").click(function(){
			$("#Wcanal-tabon .Wcanal-tab-title p").removeClass("Wcanal-tab-hover");
			$(".Wcanal-tab-list").hide();
			$(this).addClass("Wcanal-tab-hover");
			$(".Wcanal-tab-list:eq("+$(this).index()+")").show();
			windowheight();
    	});
}
function Msgtabon(){
		$("#Msg-tabon .Wcanal-tab-title p").click(function(){
			$("#Msg-tabon .Wcanal-tab-title p").removeClass("hover");
			$(".Wcanal-tab-list").hide();
			$(this).addClass("hover");
			$(".Wcanal-tab-list:eq("+$(this).index()+")").show();
    	});
}
function Wmaintabon(){
		$("#Wmain-tabon .sidenav a").click(function(){
			$("#Wmain-tabon .sidenav li").removeClass("hover");
			$(".subcon").hide();
			$(this).closest("li").addClass("hover");
			$(".subcon:eq("+$(this).closest("#Wmain-tabon .sidenav li").index()+")").show();
    	});
}

function go_history(){
		$(".ind-bread").click(function(){
			history.go(-1);
    	});
}


//以下是常用处理函数
//从表单中获取 Input元素形成 json报文
function getFormData(f){
	var $form = $(f);
	/**
	 * 此方法代码参考：http://css-tricks.com/snippets/jquery/serialize-form-to-json/
	 */
	var o = {};
	var a = $("input,textarea,select",$form).serializeArray();
	$.each(a, function() {
	   if (o[this.name]) {
		   if (!o[this.name].push) {
			   o[this.name] = [o[this.name]];
		   }
		   o[this.name].push(this.value || '');
	   } else {
		   o[this.name] = this.value || '';
	   }
	});
	return o;
	/*
	var submitData = {};
	 $("input,textarea,select",$form).each(function(i,j){
		var $obj = $(this);
		var name = $obj.attr("name");
		if(!name){
			return true;
		}
		//checkbox,radio
		var obj_type = $obj.attr('type'),str;
		if(obj_type == 'checkbox' || obj_type == 'radio'){
			var objname = $obj.attr('name');
			str = formobj.filter(':'+obj_type+'[name='+objname+'][checked]').map(function(){
				return this.value;
			}).get().join(',');
		}
		else{
			str=$obj.val();
		}
		submitData[name] = str;
	 });
	return submitData;
	*/
}
//转换json为字符串
function JsonToStr(o) {
	if(JSON.stringify){
		return JSON.stringify(o);
	}
	var r = [];
	if (typeof o == "string" || o == null) {
		return o;
	}
	if (typeof o == "object") {
		if (!o.sort) {
			r[0] = "{"
			for (var i in o) {
				r[r.length] = "\""+i+"\"";
				r[r.length] = ":";
				r[r.length] = "\""+JsonToStr(o[i])+"\"";
				r[r.length] = ",";
			}
			r[r.length - 1] = "}"
		} else {
			r[0] = "[";
			for (var i = 0; i < o.length; i++) {
				r[r.length] = JsonToStr(o[i]);
				r[r.length] = ",";
			}
			r[r.length==1?r.length:r.length - 1] = "]";
		}
		return r.join("");
	}
	return o.toString();
}

function Service(){
	var html =
			/*['<div class="sideMenu">',
			'<ul>',
        	'<li class="dn"><a href="javascript:void(0)" id="goto_top"><i class="icon-top"></i><span class="icon-top"></a></span></li>',
        	'<li class="sideMenu-two" style="margin-top:50px;"><a href="index.php?g=Home&m=Index&a=index"><i class="icon-home"></i><span>返回<br />首页</span></a></li>',
        	'<li><a href="javascript:void(0)" class="autoonlinecontant" onclick="onlinecontant()"><i class="icon-info"></i><span>在线<br />留言</span></a></li>',
        	'<li><a href="index.php?g=Home&m=Index&a=helpConter&type=1&leftId=zxwt" target="_blank"><i class="icon-help"></i><span>帮助<br />中心</span></a></li>',
        	'<li><a href="index.php?g=Home&m=Wservice&a=windex" target="_blank"><i class="icon-wserv"></i><span class="mt10">旺服务</span></a></li>',
			'</ul>',
			'</div>']*/
			['<style style="text/css">',
			 '.slidetoolbarContainr { position: fixed;_position: absolute; top: 0; right: 0; width: 50px; height: 100%; z-index: 500}',
			 '.slidetoolbar {background: #f4f4f4; width: 50px; height: 100%; right: -50px; padding-top: 200px; position: relative; font-size: 12px}',

			 '.slidetoolbar .applist { text-align: center; width: 50px; position: relative}',

			 '.slidetoolbar .appitem { position: relative; height: 70px; margin-bottom: 10px; _margin-bottom: 0}',

			 '.slidetoolbar  #is_hide{ margin-bottom:40px; }',

			 '.slidetoolbar .appitem a:hover .sidebar_icon{ background-color:#ed3f41; }',

			 '.slidetoolbar .icon { position: relative; display: block; width: 50px; height: 20px; padding-top: 48px; color: #999; text-decoration:none}',


			' .slidetoolbar .hot { position: absolute; z-index: 5; right: 5px; top: 4px; width: 8px; height: 8px;',
			 	'background: url(Home/Public/Image/nav_ig/slidetoolbar-icon.png) no-repeat -69px -10px}',

			 '.slidetoolbar .icon-img { height: 36px; width: 36px; position: absolute; top: 8px; left: 7px}',

			 '.slidetoolbar .appitem-hover .icon { background-color: #f7f7f7; text-decoration: none }',

			 '.slidetoolbar .icon-0 { background-position: 7px 8px }',

			 '.slidetoolbar .icon-1 { background-position: 7px -74px }',

			' .slidetoolbar .icon-2 { background-position: 7px -158px }',

			' .slidetoolbar .icon-3 { background-position: 7px -243px }',

			' .slidetoolbar .icon-4 { background-position: 7px -327px }',

			' .slidetoolbar .content,.slidetoolbar .horoscope { display: none; position: absolute; top: -1px;',
			 	'left: 50px; width: 210px; height: 68px; border: 1px solid #e6e6e6; border-left: none;',
			 '	overflow: hidden; background: #f7f7f7; text-align: left; z-index: 500 }',

			' .slidetoolbar .appitem-hover .content,.slidetoolbar .appitem-hover .horoscope { display: block }',

			' .slidetoolbar .content .text,.slidetoolbar .horoscope .text { width: 140px; overflow: hidden; white-space: nowrap }',

			 '.slidetoolbar .horoscope .text { width: 145px; padding: 8px 0 0 10px }',

			 '.slidetoolbar .content .link { width: 130px; padding-left: 10px; height: 34px; display: block; font-size: 14px; line-height: 46px;',
			 	'overflow: hidden; color: #666; white-space: nowrap }',

			' .slidetoolbar .content .link:hover { color: #f30 }',

			' .slidetoolbar .content .link2 { margin-left: 5px }',

			' .slidetoolbar .content .desc,.slidetoolbar .horoscope .desc { display: block; width: 130px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; color: #999}',

			' .slidetoolbar .content .desc { height: 34px; padding-left: 10px; line-height: 18px}',

			' .slidetoolbar .horoscope .desc { height: 30px; line-height: 14px}',

			' .slidetoolbar .content .desc:hover,.slidetoolbar .horoscope .desc:hover { text-decoration: none}',

			' .slidetoolbar .horoscope .desc { width: 145px}',

			 '.slidetoolbar .content .image { position: absolute; right: 0; top: 0}',

			' .slidetoolbar .horoscope .image { position: absolute; right: 3px; top: 10px; width: 48px; height: 48px;',
			 '	background: url(Home/Public/Image/nav_ig/slidexingzuo.png) no-repeat}',

			' .slidetoolbar .horoscope .aries { background-position: 0 0}',

			 '.slidetoolbar .horoscope .taurus { background-position: 0 -48px}',

			' .slidetoolbar .horoscope .gemini { background-position: 0 -96px}',

			' .slidetoolbar .horoscope .cancer { background-position: 0 -144px}',

			' .slidetoolbar .horoscope .leo { background-position: 0 -192px}',

			' .slidetoolbar .horoscope .virgo { background-position: 0 -240px }',

			' .slidetoolbar .horoscope .libra { background-position: 0 -288px }',

			' .slidetoolbar .horoscope .scorpio { background-position: 0 -336px}',

			 '.slidetoolbar .horoscope .sagittarius { background-position: 0 -384px}',

			' .slidetoolbar .horoscope .capricorn { background-position: 0 -432px}',

			' .slidetoolbar .horoscope .aquarius { background-position: 0 -480px}',

			 '.slidetoolbar .horoscope .pisces { background-position: 0 -528px}',

			 '.slidetoolbar .horoscope .select { display: inline-block; line-height: 30px; _line-height: 34px; font-size: 14px; color: #666;',
			 '	background: url(Home/Public/Image/nav_ig/slidetoolbar-icon.png) no-repeat -32px -216px;',
			 	'cursor: pointer; vertical-align: top; padding-right: 15px }',

			' .slidetoolbar .horoscope .select-content {',
			 '	position: absolute; width: 100%; height: 100%; overflow: hidden; top: 0; left: 0; background: #f7f7f7; z-index: 5}',

			'.slidetoolbar .horoscope .content-list { padding: 4px 8px}',

			' .slidetoolbar .horoscope .content-list-item {',
			 '	width: 48px; height: 20px; text-align: center; line-height: 20px; cursor: pointer; color: #666}',

			 '.slidetoolbar .horoscope .list-item-hover { background: #e6e6e6}',

			 '.slidetoolbar .horoscope .star {',
			 	'display: inline-block; height: 30px; width: 85px;',
			 	'background: url(Home/Public/Image/nav_ig/index_icon.png) no-repeat;',
			 	'vertical-align: top; margin-left: 3px}',

			 '.slidetoolbar .horoscope .star-5 { background-position: -186px -1320px}',

			 '.slidetoolbar .horoscope .star-4 { background-position: -186px -1352px}',

			 '.slidetoolbar .horoscope .star-3 { background-position: -186px -1384px}',

			 '.slidetoolbar .horoscope .star-2 { background-position: -186px -1416px}',

			 '.slidetoolbar .horoscope .star-1 { background-position: -186px -1448px}',

			 '.slidetoolbar-closebtn {',
			 '	position: absolute; height: 50px; width: 50px; bottom: 10px;',
			 '	background:#f4f4f4 url(Home/Public/Image/nav_ig/slidetoolbar-icon.png) no-repeat;',
			 '	cursor: pointer; display: none}',

			 '.slidetoolbar-closebtn:hover { background-color: #f1f1f1}',

			 '.slideclosebtn-open {  background-position: -44px -161px	}',

			 '.slideclosebtn-close {   background-position: -49px -73px }',

			 '.slidetoolbar .sppitemwrap { position: relative; height: 70px; margin-bottom: 10px}',

			 '.slidetoolbar .flipicon { height: 54px; position: absolute; top: -10px; right: 50px}',

			 '.slidetoolbar .flipicon .flipcon { height: 54px}',
			 '.sidebar_icon{ width:36px; height:36px; display:inline-block; background:#9ca4ad url(Home/Public/Image/sidebar_icon.png) no-repeat; border-radius:2px; overflow:hidden; position: absolute;',
			 '	top: 8px;	left: 7px}',
			 '.no0{ background-position:0 0;}',
			 '.no1{ background-position:0 -118px;}',
			 '.no2{ background-position:0 -184px;}',
			 '.no3{ background-position:0 -260px}',
			 '.no4{ background-position:0 -336px} ',
			 '#goto_proInt:after,#goto_proInt:hover:after{ ',
           	'content:"业务介绍";',
           	'width: 36px;',
           	'height: 36px;',
           	'position:fixed;',
           	'top:152px;',
           	'color:#999;',
           	'z-index:9999;',
           	'font-size:13px;',
           	'text-align:center;',
           	'display:inline-block;',
            	'font-family:Microsoft Yahei;',
             '}',
             '#goto_proInt:hover:after{ ',
				'content:"业务介绍";',
				'width: 36px;',
				'height: 36px;',
				'position:relative;',
				'top:40px;',
				'color:#ed3f41 !important;',
				'z-index:9999;',
				'font-size:13px;',
				'text-align:center;',
				'display:inline-block;',
				'font-family:Microsoft Yahei;',
			'}',
			'.slidetoolbar .icon:hover {',
				'position: relative;',
				'display: block;',
				'width: 50px;',
				'height: 20px;',
				'padding-top: 48px;',
				'color:#ed3f41;',
				'text-decoration:none',
			'}',
			'.slideclosebtn-open:hover { background-position: -44px -202px } .slideclosebtn-close:hover {',
				'background-position: -49px -118px }',

			 '</style>',
			 '<div class="slidetoolbarContainr" monkey="slidetoolbar" id="__elm_0_5">',
			   '<div class="slidetoolbar" style="right:0px">',
			    '<div class="applist">',  
			     '<div class="sppitemwrap">',
			      '<div class="appitem appitem-hook">',
			       '<a href="index.php?g=Home&m=Index&a=index" class="icon icon-2 icon-hook"><i class="sidebar_icon no1"></i>首页</a>',
			       
			      '</div>',
			     '</div>',
			     '<div class="sppitemwrap">',
			      '<div class="appitem appitem-hook">',
			       '<a href="index.php?g=Home&m=Index&a=helpConter&type=1&leftId=zxwt" class="icon icon-3 icon-hook"><i class="sidebar_icon no2"></i>帮助<br>中心</a>',
			       
			      '</div>',
			     '</div>',
			     '<div class="sppitemwrap">',
			      '<div class="appitem appitem-hook">',
			     '  <a href="javascript:void(0);" onclick="onlinecontant()" class="icon icon-4 icon-hook"><i class="sidebar_icon no3"></i>我要<br>吐槽</a>',
			       
			      '</div>',
			    ' </div>',
			    ' <div class="sppitemwrap" id="gotop" style="display:none;">',
			     ' <div class="appitem appitem-hook">',
			       '<a href="javascript: void(0);"  class="icon icon-4 icon-hook"><i class="sidebar_icon no4"></i>返回<br>顶部</a>',
			       
			    '  </div>',
			    ' </div>',
			 '   </div>',
			  ' </div>',
			  ' <a href="javascript:;" hidefocus="true" class="slidetoolbar-closebtn slideclosebtn-open" title="收起" style="display: inline;"></a>',
			 ' </div>']
		.join('');
	$(".service").append(html);
};


$(function(){
	$('#gotop').click(function(){ 
		$('body,html').animate({
			scrollTop: 0
		},
		800);//点击回到顶部按钮，缓懂回到顶部,数字越小越快
		return false;  
	})
	$(".slidetoolbar-closebtn").click(function(e) { 
		if(!$(this).hasClass("slideclosebtn-close"))
		{ 
			$(".slidetoolbar").css("right","-50px");
		$(this).addClass("slideclosebtn-close");
		$(this).attr('title','展开');
		}
		else
		{ 
			 $(".slidetoolbar").css("right","0");
		
			$(this).removeClass("slideclosebtn-close");
			$(this).attr('title','收起');
		}
    });
	$(window).scroll(function(){
		t = $(document).scrollTop();

		if(t > 50){
			$('#gotop').fadeIn('slow');
		}else{
			$('#gotop').fadeOut('slow');
		}       
	 })  
})
function intcommunity(){//消息
	$.post("index.php?g=Home&m=TipsWindow&a=index",function(data){
			if(data.status==1){
				//alert(data.guestbookcount+"==="+data.messagecount);
				if(data.guestbookcount!=0||data.messagecount!=0||data.ordercount!=0){
					community(true,data.guestbookcount,data.messagecount,data.ordercount);				
				}
				
			}else{
				return false;
			}
		},"json");
	setInterval(function(){		
		$.post("index.php?g=Home&m=TipsWindow&a=index",function(data){
			if(data.status==1){
				//alert(data.guestbookcount+"==="+data.messagecount);
				if(data.guestbookcount!=0||data.messagecount!=0||data.ordercount!=0){
					community(true,data.guestbookcount,data.messagecount,data.ordercount);				
				}
				
			}else{
				return false;
			}
		},"json");
	},20000);
}

function community(login,msg_one,msg_two,msg_three){
	if(window.frames.length != parent.frames.length){  
		return false;
	}
	var login = login ? false : true ;
	if(login){return false;};
	//点击事件
	$(".O2OMsg-span,.O2OMsg-msg-close").live("click",function(){
		if($(".O2OMsg-msg").is(":animated")){return false;};
		var g = $(".O2OMsg-msg").hasClass("open");
		if(!g){
			$(".O2OMsg-msg").animate({marginTop:0},300);
			$(".O2OMsg-msg").addClass("open");
			document.cookie="O2OMsg=2";
		}else{
			$(".O2OMsg-msg").animate({marginTop:-193},300);
			$(".O2OMsg-msg").removeClass("open");
			document.cookie="O2OMsg=1";
		}
	});
	$(".O2OMsg-close,.O2OMsg-open").live("click",function(){
		if($(".O2OMsg-con").is(":animated") || $(".O2OMsg-msg").is(":animated")){return false;};
		var g = $(".O2OMsg-con").hasClass("open");
		if(!g){
			$(".O2OMsg-msg").animate({marginTop:0},300,function(){$(".O2OMsg-con").animate({marginLeft:0},300);});
			$(".O2OMsg-con").addClass("open");
			document.cookie="O2OMsg=3";
		}else{
			$(".O2OMsg-con").animate({marginLeft:-180},300);
			$(".O2OMsg-con").removeClass("open");
			document.cookie="O2OMsg=1";
		}
	});
	$(".O2OMsg-text-list .li-item1").live("click",function(){
		$(".O2OMsg-text-list li").removeClass("hover");
		$(this).addClass("hover");
		$(".O2OMsg-text-list .item1").show();
		$(".O2OMsg-text-list .item2").hide();
	});
	$(".O2OMsg-text-list .li-item2").live("click",function(){
		$(".O2OMsg-text-list li").removeClass("hover");
		$(this).addClass("hover");
		$(".O2OMsg-text-list .item2").show();
		$(".O2OMsg-text-list .item1").hide();
	});
	
	var msg_one = msg_one ? msg_one : 0;
	var msg_two = msg_two ? msg_two : 0;
	var msg_three = msg_three ? msg_three : 0;
	var msg = parseInt(msg_one)+parseInt(msg_two)+parseInt(msg_three);
	//初始化
	var length = $("#O2OMsg").length ;
	if (length>=1){
		var nowmsg = parseInt($(".O2OMsg-num0").text());
		if(msg>nowmsg){
			var addnum = msg-nowmsg;
			$(".O2OMsg-add").show();
			$(".O2OMsg-add").animate({marginTop:-40,opacity:0},400,function(){$(".O2OMsg-add").attr("style","")});
			$(".O2OMsg-add").text("+"+addnum);
			$(".O2OMsg-num0").text(msg);
		}else{
			$(".O2OMsg-num0").text(msg);
		}
		$(".O2OMsg-num1").text(msg_one);
		$(".O2OMsg-num2").text(msg_two);
		return false;
	};
	var msgcookie = document.cookie.split("; ");
	var msgcookie_g = true ;
	for(var i=0;i<msgcookie.length;i++){
		var arr=msgcookie[i].split("=");
		if(arr[0]=="O2OMsg"){
			var margin_one='',margin_two='';
			if(arr[1]==2){margin_one = 'style="margin-top:0;"';};
			if(arr[1]==3){
				margin_one = 'style="margin-top:0;"';
				margin_two = 'style="margin-left:0;"';
			};
			var html = 
			['<div id="O2OMsg">',
				'<div class="O2OMsg-con" '+margin_two+'>',
					'<div class="O2OMsg-title">',
						'<div class="O2OMsg-open"><span class="O2OMsg-add"></span></div>',
						'<div class="O2OMsg-span"><p>您有<span class="O2OMsg-num0">'+msg+'</span>条消息未读</p></div>',
						'<div class="O2OMsg-close"><i></i></div>',
					'</div>',
					'<div class="O2OMsg-msg" '+margin_one+'>',
						'<div class="O2OMsg-msg-title">旺消息<a href="javascript:void(0)" class="O2OMsg-msg-close"></a></div>',
						'<div class="O2OMsg-text">',
							'<div class="O2OMsg-text-list">',
								'<ul class="fn"><li class="li-item1 hover">社区消息</li><li class="li-item2">订单通知</li></ul>',
								'<div class="O2OMsg-text-item item1">',
									'<p><a href="index.php?m=AccountInfo&a=batch_msg">您收到<span class="O2OMsg-num1">'+msg_one+'</span>条评论</a><br />',
									'<a href="index.php?g=Home&m=AccountInfo&a=node_msg">您收到<span class="O2OMsg-num2">'+msg_two+'</span>条私信</a></p>',
								'</div>',
								'<div class="O2OMsg-text-item item2 dn">',
									'<p><a href="index.php?g=LabelAdmin&m=OrderList&a=index">您有<span class="O2OMsg-num2">'+msg_three+'</span>条订单</a></p>',
								'</div>',
							'</div>',
						'</div>',
					'</div>',
				'</div>',
			'</div>'].join('');
			msgcookie_g = false ;
		};
	}
	if(msgcookie_g){
		document.cookie="O2OMsg=1";
		var strCookie=document.cookie;
		var html = 
			['<div id="O2OMsg">',
				'<div class="O2OMsg-con">',
					'<div class="O2OMsg-title">',
						'<div class="O2OMsg-open"><span class="O2OMsg-add"></span></div>',
						'<div class="O2OMsg-span"><p>您有<span class="O2OMsg-num0">'+msg+'</span>条消息未读</p></div>',
						'<div class="O2OMsg-close"><i></i></div>',
					'</div>',
					'<div class="O2OMsg-msg">',
						'<div class="O2OMsg-msg-title">旺消息<a href="javascript:void(0)" class="O2OMsg-msg-close"></a></div>',
						'<div class="O2OMsg-text">',
							'<div class="O2OMsg-text-list">',
								'<ul class="fn"><li class="li-item1 hover">收到评论</li><li class="li-item2">社区消息</li></ul>',
								'<div class="O2OMsg-text-item item1">',
									'<p><a href="index.php?m=AccountInfo&a=batch_msg">您收到<span class="O2OMsg-num1">'+msg_one+'</span>条评论</a><br />',
									'<a href="index.php?g=Home&m=AccountInfo&a=node_msg">您收到<span class="O2OMsg-num2">'+msg_two+'</span>条私信</a></p>',
								'</div>',
								'<div class="O2OMsg-text-item item2 dn">',
									'<p><a href="index.php?g=LabelAdmin&m=OrderList&a=index">您有<span class="O2OMsg-num2">'+msg_three+'</span>条订单</a></p>',
								'</div>',
							'</div>',
						'</div>',
					'</div>',
				'</div>',
			'</div>'].join('');
	}
	$("body").append(html);
}

function scrollTop(){
	$("#goto_top").click(function(){
   		 var sc=$(window).scrollTop();
  		 $('body,html').animate({scrollTop:0},500);
    });
}

$(window).scroll(function(){
	if($(window).scrollTop()>1000){
		$("#goto_top").closest("li").show();
		$(".sideMenu-two").css("margin-top","5px");
	};
	if($(window).scrollTop()<=1000){
		$("#goto_top").closest("li").hide();
		$(".sideMenu-two").css("margin-top","50px");
	};
});



function onlinecontantfouse(type){
	if(type==1){
		if($(".onlinecontant-input").val()=="邮箱/用户名/手机都可以啦~"){
			$(".onlinecontant-input").val("");
			$(".onlinecontant-input").css("color","#444");
	}
	}else{
		if($(".onlinecontant-textarea").val()=="请输入留言内容(最多1000个字)" ||$(".onlinecontant-textarea").val()=="请填写内容哦~" ){
 			$(".onlinecontant-textarea").val("");
			$(".onlinecontant-textarea").css("color","#444");
		}
	}
}
function onlinecontantBlur(type){
	if(type==1){
		if($(".onlinecontant-input").val()==""){
			$(".onlinecontant-input").css('fontSize','12px').val("邮箱/用户名/手机都可以啦~");
			$(".onlinecontant-input").css("color","#777");
	}
	}else{
		if($(".onlinecontant-textarea").val()==""){
			$(".onlinecontant-textarea").val("请输入留言内容(最多1000个字)");
			$(".onlinecontant-textarea").css("color","#777");
		}
	}
}

function closeonlinecontant(){
	$(".onlinecontant,.onlinecontant_bg").remove();
}

function feedback(){  
	if(($(".onlinecontant-textarea").val()).replace(/[ ]/g,"")==""  || $(".onlinecontant-textarea").val()=="请填写内容哦~" || $(".onlinecontant-textarea").val()=="请输入留言内容(最多1000个字)"){
		$(".onlinecontant-textarea").val("请填写内容哦~");
		return false;
	}
	$('#onlinefeedback').unbind('click',feedback);
	$.post($("#feedback_form").attr('action'),$("#feedback_form").serialize(),function(data){
		alert(data.info); 
		if(data.status==1){
			parent.window.art.dialog.list['userback'].close();
		}
		$('#onlinefeedback').bind('click',feedback);
		
	},'json');
}
//关闭指定弹出框
function art_close(id){
	art.dialog.list[id].close();
}
//关闭所有弹出框
function all_art_close(){
	var list = art.dialog.list;
	for (var i in list) {
	    list[i].close();
	};
}
//检查字符串长度
function check_lenght(total,id,obj){
	var text = $(obj).is('div') ? $(obj).html() : $(obj).val();
	if(text==""){text=$(obj).text()}
    if(text.length <= total){
        $("#"+id).attr("style","").html("还可以输入"+(total-text.length)+"个字");
    }else{
        $("#"+id).attr("style","color:red;").html("已经超出"+(text.length-total)+"个字");
        //$(this).val(text.substring(0, total));
    }
}
//校验字数返回值
function check_lenght_btn(total,id,obj,btn){
	var text = $(obj).val();
	var intLength=0;
	if(text==""){text=$(obj).text()};
	if(text==""){return "3"};
	for (var i=0;i<text.length;i++){
        if ((text.charCodeAt(i) < 0) || (text.charCodeAt(i) > 255)){
			intLength=intLength+2;
		}else{
			intLength=intLength+1;
		}
	}
	text=Math.ceil(intLength/2);
    if(text <= total){
        $("#"+id).attr("style","").html("还可以输入<span>"+(total-text)+"</span>个字");
		$("#"+id).show();
		$("."+btn).removeClass("disabled");
		return "1";
    }else{
        $("#"+id).attr("style","color:red;").html("已经超出<span style='color:red'>"+(text-total)+"</span>个字");
		$("#"+id).show();
		$("."+btn).addClass("disabled");
		return "2";
    }
}

function managercard(){
	var name=$("#managercardName").text();
	var position=$("#managercardPosition").text();
	var company=$("#managercardCompany").text();
	var qq=$("#managercardQQ").text();
	var weibo=$("#managercardWeibo").text();
	var weixin=$("#managercardWeixin").text();
	var mphone=$("#managercardMphone").text();
	var tphone=$("#managercardTphone").text();
	var mail=$("#managercardMail").text();
	var address=$("#managercardAddress").text();
	var img=$("#managercardImg").text();
	if(name!=""){
		name="<div class='managercard-card-name'>"+name+"</div>";
	};
	if(position!=""){
		position="<div class='managercard-card-position'>"+position+"</div>";
	};
	if(company!=""){
		company="<div class='managercard-card-company'>"+company+"</div>";
	};
	if(qq!=""){
		qq="<div class='managercard-card-qq'><i class='icon-cardqq'></i>"+qq+"</div>";
	};
	if(weibo!=""){
		weibo="<div class='managercard-card-qq'><i class='icon-cardweibo'></i>"+weibo+"</div>";
	};
	if(weixin!=""){
		weixin="<div class='managercard-card-weixin'><i class='icon-cardweixin'></i>"+weixin+"</div>";
	};
	if(mphone!=""){
		mphone="<div class='managercard-card-mphone'><i class='icon-cardmphone'></i>"+mphone+"</div>";
	};
	if(tphone!=""){
		tphone="<div class='managercard-card-tphone'><i class='icon-cardtphone'></i>"+tphone+"</div>";
	};
	if(mail!=""){
		mail="<div class='managercard-card-mail'><i class='icon-cardmail'></i>"+mail+"</div>";
	};
	if(address!=""){
		address="<div class='managercard-card-address'><i class='icon-cardaddress'></i>"+address+"</div>";
	};
	if(img!=""){
		img="<img src='"+img+"' />";
	};
	var html=
	['<div class="mySecretary">',
            '<div class="img"><img src="./Home/Public/Image/wcmySecretary.png" /></div>',
            '<div class="mySecretary-con">',
				'<h2>俞铃妃</h2>',
				'<p>职位：客户运营部总监</p>',
				'<p>研究领域：旺财O2O业务合作</p>',
				'<p>邮箱：yulf@imageco.com.cn</p>',
				'<p>服务时间：工作日   9：00—17：30</p>',
				'<p class="cl pb20"></p>',
				'<a href="javascript:void(0)"><i class="dib vm tel"></i>13950284141</a>',
				'<a href="http://wpa.qq.com/msgrd?v=3&uin=2105236636&site=qq&menu=yes" target="_blank"><i class="dib vm qq"></i> 2105236636</a>',
				'<p style="font-size:12px;"><a href="javascript:void(0)" class="mr5" style="font-size:12px;"><i class="dib vm tel"></i><size-color="red">400-882-7770</a><span style="line-height:20px;">（全国服务热线）</span></p>',
            '</div>',
			'<a href="javascript:void(0)" class="close-mySec yellow" onclick="managercardclose()"></a>',
        '</div>'].join('');
	art.dialog({
		id:'mySec',
		title:false,
		content:html,
		padding:"0",
		top:"50%",
		lock:true
	});
}
function oClass(){
	var teacher = $("#oClassTeacher").text()!="" ? $("#oClassTeacher").text() : "";
	var name = $("#oClassName").text()!="" ? $("#oClassName").text() : "";
	var time=$("#oClassTime").text()!="" ? $("#oClassTime").text() : "";
	var order=$("#oClassOrder").text()!="" ? $("#oClassOrder").text() : "";
	var url=$("#oClassUrl").text()!="" ? $("#oClassUrl").text() : "";
	var html=
	['<div class="oClass">',
            '<div class="img"><img src="'+url+'" /><p>'+teacher+'</p></div>',
            '<div class="oClass-con">',
				'<p><strong>主题</strong>'+name+'</p>',
				'<p><strong>日期</strong>'+time+'</p>',
				'<p><strong>方式</strong>'+order+'</p>',
				'<p class="cl pb20"></p>',
				'<a href="javascript:void(0)" class="btn-join" onclick="windowBg()">我要报名</a>',
            '</div>',
			'<a href="javascript:void(0)" class="close-mySec blue" onclick="managercardclose()"></a>',
        '</div>'].join('');
	art.dialog({
		id:'mySec',
		title:false,
		content:html,
		padding:"0",
		top:"50%",
		lock:true
	});
}
function managercardclose(){
	art.dialog({id:'mySec'}).close();
}


function reload(){
	location.href = location.href;
}

function urlencode(str) {
  str = (str + '').toString();
  return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\*/g, '%2A')
    .replace(/%20/g, '+');
}

function getHeight(){
	var lh = $('.left').height();
	var rh = $('.right').height();
	lh > rh ? $('#main-help').height(lh+120):$('#main-help').height(rh+120);
	
}

$(document).ready(function(e) {
    $(".new_sidenav dl dd").toggle(function(e) {
		$(this).next("div.new_subnav").css("display","none");
        $(this).next("div.new_subnav").slideToggle("slow");
		$(this).addClass("active");
    },function(e){
		$(this).next("div.new_subnav").css("display","block");
        $(this).next("div.new_subnav").slideToggle("slow");
		$(this).removeClass("active");
    });
});

 
function Gform(){
	if(Gformfn){
		Gformfn = false;
	}else{
		return false;
	}
	Gformbegin();
	
	//绑定事件
	$("body").on("click",".Gform .switch .newRadio span",function(){
		var t = $(this),
			s = t.closest(".switch"),
			type = t.closest(".newRadio").prev("input").attr("type"),
			n = s.find(".newRadio span"),
			a = s.hasClass("disabled"),
			b = s.hasClass("hover"),
			c = t.hasClass("hover"),
			i = s.find("input:eq(0)"),
			ii = s.find("input[name='checkswitch']");
		if(a){return false;};
		if(type=="radio"){
			if(c){return false;};
			var val = t.attr("data-val");
			n.removeClass("hover");
			t.addClass("hover");
			b ? (s.removeClass("hover"),i.val(val),ii ? ii.attr("checked",false) : ii) : ($(this).addClass("hover"),s.addClass("hover"),i.val(val),ii ? ii.attr("checked",true) : ii);
		}else if(type=="checkbox"){
			c ? (t.removeClass("hover")) : (t.addClass("hover"));
			var check = s.find(">input[type='checkbox']");
				check.attr("checked",false);
			$(n).each(function(index) {
				if($(this).hasClass("hover")){
					var name = $(this).attr("data-name");
					var val = $(this).attr("data-val");
					if(name){
						var check = s.find(">input[name='"+name+"']");
					}else{
						var check = s.find(">input[type='checkbox']:eq("+index+")");
					}
					check.attr("checked",true);
				}
            });
		}
		if(s.attr("data-show")!=""){
			var showid = s.attr("data-show");
			if($("[data-show='"+showid+"']").length>1){
				var isshow = false ;
				$("[data-show='"+showid+"']").each(function(){
					if($(this).hasClass("hover")){isshow = true}
				});
				isshow ? $("#"+showid).show() : $("#"+showid).hide();
			}else{
				s.find(".newRadio span:eq(0)").hasClass("hover") ? $("#"+showid).hide() : $("#"+showid).show();
			}
		}
		if(s.attr("data-callback")){
			if(s.attr("data-callback")==""){
				return false;
			}
			var Gformcallback = s.attr("data-callback");
			window[Gformcallback].call(this,t)
		}
	})
	$("body").on("click",".Gform .switch input",function(event){
		event.stopPropagation();
	})
	$("body").on("click",".Gform .Gadd .Gbtn-add",function(){
		var t = $(this),
			l = t.closest("li").find(".Gadd").length,
			s = t.closest(".Gadd"),
			n = s.attr("data-max"),
			m = s.attr("data-min"),
			h = s.html();
			l<n ? (s.after("<div class='Gadd Gadd-begin' data-min='"+m+"' data-max='"+n+"'>"+h+"</div>"),setTimeout(function(){$(".Gadd.Gadd-begin").removeClass("Gadd-begin");},100)) : (s.addClass("Gadd-erro"),setTimeout(function(){s.removeClass("Gadd-erro");},300));
		if(s.attr("data-callback")){
			if(s.attr("data-callback")==""){
				return false;
			}
			var Gformcallback = s.attr("data-callback");
			window[Gformcallback].call(this,t)
		}
	})
	$("body").on("click",".Gform .Gadd .Gbtn-del",function(){
		var t = $(this),
			l = t.closest("li").find(".Gadd").length,
			s = t.closest(".Gadd"),
			n = s.attr("data-max"),
			m = s.attr("data-min");
			l>m ? (s.addClass("Gadd-end"),setTimeout(function(){s.remove();},300)) : (s.addClass("Gadd-erro"),setTimeout(function(){s.removeClass("Gadd-erro");},300));
		if(s.attr("data-callback")){
			if(s.attr("data-callback")==""){
				return false;
			}
			var Gformcallback = s.attr("data-callback");
			window[Gformcallback].call(this,t)
		}
	})
	$("body").on("click",".Gform .Gbtn-more span",function(){
		var t = $(this).closest(".Gbtn-more"),
			s = t.closest(".Gmore"),
			d = s.find(".GmoreForm");
		if(s.hasClass("open")){
			d.animate({height:0},300,function(){
				s.removeClass("open");
				d.height("auto");
			});
			t.find("span").html("更多设置：<i></i>");
		}else{
			s.addClass("open");
			var h = d.height();
			d.height(0);
			d.animate({height:h},500,function(){
				d.height("auto");
			});
			t.find("span").html("收起设置：<i></i>");
		}
		if(s.attr("data-callback")){
			if(s.attr("data-callback")==""){
				return false;
			}
			var Gformcallback = s.attr("data-callback");
			window[Gformcallback].call(this,t)
		}
	})
	
	$("body").on("click",".Gform .Gchoose>img,.Gform .Gchoose a[data-type='list']",function(){
		var t = $(this),
			opr = t.closest(".Gchoose").find(">.Gchoose-opr")[0] ? t.closest(".Gchoose").find(">.Gchoose-opr") : t.closest(".Gchoose").find(">.Gchoose-list");
			opr.show().animate({bottom:30,opacity:1},200).removeClass("an");
	})
	$("body").on("click",".Gform .Gchoose .Gchoose-oprbg,.Gform .Gchoose .Gchoose-listbg",function(){
		$(".Gchoose-opr,.Gchoose-list").animate({bottom:0,opacity:0},200).delay(20).fadeOut(20).addClass("an");
	})
	
	//新校验字符长度
	$("body").on("keyup",".Gform input,textarea",function(){
		var maxLength = $(this).next("span.maxTips").attr("data-max");
		var text = $(this).is('div') ? $(this).html() : $(this).val();
		if(text==""){text = $(this).text()};
		if(!maxLength){return false;}
		if(text.length <= maxLength){
			$(this).next("span.maxTips").removeClass("erro").html(text.length+"/"+maxLength);
		}else{
			$(this).next("span.maxTips").addClass("erro").html(text.length+"/"+maxLength);
		}
	})
	
	$("body").on("click",".Gform .Gbtn-pic,.Gform .Gbtn-picmore,.Gform .Gchoosemore-edit",function(){
		var data = $(this).attr("data-rel");
		var obj,maxlength,upload;
		if($(this).hasClass("Gbtn-pic")){
			obj = $(this).closest(".Gchoose");
			maxlength = false;
			upload = "upload";
		}else if($(this).hasClass("Gbtn-picmore")){
			obj = $(this).closest(".Gchoosemore");
			maxlength = 21;
			upload = "uploadmore";
		}else if($(this).hasClass("Gchoosemore-edit")){
			obj = $(this).closest(".Gchoosemore-list");
			maxlength = false;
			upload = "editmore";
		}
		var defaults = {
				obj:obj,//对象
				Gform:true,//是否Gform
				type:0,//图片用处类型，用于删选
				width:640,//建议宽度
				height:320,//建议高度
				//uploadUrl:"http:\\192.168.0.35:8080\index.php?g=ImgResize&m=Resize&a=uploadFile1",//上传地址
				menuType:1,//美图秀秀版本
				animate:0,//是否动画
				resizeFlag:"",//是否动画
				txtmsg:"",//备注
				callback:"GdataImg",//callback
				maxlength:maxlength,//是否多张
				GdataImg:upload//上传类型(upload:单张,uploadmore:多张,editmore:多张修改)
			}
		var imguploadData = $.extend(true, {}, defaults, eval('('+data+')'));
        open_img_uploader(imguploadData);
        return;
	})
	
	$("body").on("click",".Gform .Gbtn-music",function(){
		var data = $(this).attr("data-rel");
		var obj = $(this).closest(".Gchoose");
		var defaults = {
				obj:obj,//对象
				Gform:true,//是否Gform
				txtmsg:"",//备注
				callback:"GdataMusic"//callback
			}
		var musicuploadData = $.extend(true, {}, defaults, eval('('+data+')'));
        open_music_uploader(musicuploadData);
        return;
	})
	
	$("body").on("click",".Gform .Gchoose-opr .Gchoose-opr-edit",function(){
		var t = $(this);
		t.closest(".Gchoose").find(".Gbtn-pic").click();
	})
	$("body").on("click",".Gform .Gchoosemore-del,.Gform .Gchoose-opr-del",function(){
		$(this).closest(".Gchoose")[0] ? ($(this).closest(".Gchoose").find(".Gchoose-opr,.Gchoose-oprbg,>img").remove(),$(this).closest(".Gchoose").find(">input").val("")) : ($(this).closest(".Gchoosemore-list").remove());
	})
	$("body").on("change",".Gform .Gchoose .Gbtn-papers input[type=file]",function(){
		var t = $(this).closest(".Gchoose");
		var v = $(this).val();
		var a = t.find(">a:eq(0)");
		Math.max(v.lastIndexOf('/'), v.lastIndexOf('\\')) < 0 ? v = v : v = v.substring(Math.max(v.lastIndexOf('/'), v.lastIndexOf('\\'))+1);
		a.text(v);
	})
	$("body").on("click",".Gform .Gchoose .Gbtn-shop",function(){
		var t = $(this).closest(".Gchoose");
		var a = t.find(">a");
		var name = a.attr("data-name");
		var data = [{"id":"0001","title":"门店1"},{"id":"0002","title":"门店2"}];
		GdataList({
			obj:t,
			data:data,
			name:name
		});
	})
	$("body").on("click",".Gform .Gchoose-list .Gchoose-li a",function(){
		var t = $(this),
			r = t.closest(".Gchoose-li"),
			l = t.closest(".Gchoose"),
			id = r.find(">input").val(),
			span = t.closest(".Gchoose").find(">a[data-type='list']").find(">span[data-id='"+id+"']");
			l.find(".Gchoose-li").length==1 ? (l.find(".Gchoose-list,.Gchoose-listbg").remove(),span.remove()) : (r.remove(),span.remove());
	})
}
function GdataImg(options){
	var GuploadImg = {
		upload : function(opts){
			if(opts.maxlength){alert("请使用多张图片样式");return false;}
			var w = opts.width;
			var h = opts.height;
			if(w>=h){
				w>150 ? w=150 : w ;
				var size = "width:"+w+"px";
			}else{
				h>150 ? h=150 : h ;
				var size = "height:"+h+"px";
			}
			var img = '<img src="'+src+'">';
			var html = ['<div class="Gchoose-opr an">',
							'<div class="Gchoose-opr-img"><img src="'+src+'" style="'+size+';"></div>',
							'<div class="Gchoose-opr-opr">',
								'<a href="javascript:void(0)" class="Gchoose-opr-edit"></a>',
								'<a href="javascript:void(0)" class="Gchoose-opr-del"></a>',
							'</div>',
							'<span class="Gchoose-opr-jt"></span>',
						'</div>',
						'<div class="Gchoose-oprbg"></div>'].join('');
			t.find(".Gchoose-opr,.Gchoose-oprbg").remove();
			t.find(">img").remove();
			t.append(html);
			t.find(">input").after(img);
			t.find(">input").val(savename);
			if(opts.animate==0){
				var opr = t.find(".Gchoose-opr");
				opr.css({bottom:30,opacity:1,display:"block"});
				opr.delay(300).animate({bottom:0,opacity:0},200).delay(20).fadeOut(20);
			}else{
				var opr = t.find(".Gchoose-opr");
				opr.hide();
			}
		},
		uploadmore : function(opts){
			if(!opts.smallsrc){alert("缺少smallsrc");return false;}
			for(var i=0;i<opts.src.length;i++){
				var html = ['<div class="Gchoosemore-list an">',
							'<input type="text" name="'+opts.inputname+'" value="'+opts.savename[i]+'" />',
							'<div class="Gchoosemore-img" style="background-image:url('+opts.smallsrc[i]+')"></div>',
							'<div class="Gchoosemore-opr">',
								'<a href="javascript:void(0)" class="Gchoosemore-edit"></a>',
								'<a href="javascript:void(0)" class="Gchoosemore-del"></a>',
							'</div>',
						'</div>'].join('');
				t.find(".Gchoosemore-add").before(html);
			}
			if(opts.animate==0){
				var opr = t.find(".Gchoosemore-list.an");
				opr.css({bottom:50,opacity:0,display:"block"});
				opr.animate({bottom:0,opacity:1},500).delay(200).removeClass("an");
			}
		},
		editmore : function(opts){
			if(!opts.smallsrc){alert("缺少smallsrc");return false;}
			t.find(".Gchoosemore-img").css("background-image","url("+opts.smallsrc+")");
			t.find(">input").val(savename);
		}
	}
	var defaults = {obj:false,src:false,animate:0,width:100,height:100,maxlength:false,GdataImg:"upload"}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj") ;
	var src = opts.src ? opts.src : alert("缺少src"),
        savename=opts.savename;
	switch(opts.GdataImg){ 
		case "upload": 
			GuploadImg.upload(opts);
			break;
		case "uploadmore":
			GuploadImg.uploadmore(opts);
			break;
		case "editmore": 
			GuploadImg.editmore(opts);
			break;
	}
	return false;
}
function GdataMusic(options){
	var defaults = {obj:false,src:false}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj") ;
	var src = opts.src ? opts.src : alert("缺少src") ;
	var savename = opts.savename ? opts.savename : alert("缺少名称") ;
	t.find(">input").val(src);
	t.find(">a:not('.Gbtn-music')").attr("href",src);
	t.find(">a:not('.Gbtn-music')").attr("target","_blank");
	t.find(">a:not('.Gbtn-music')").html(savename);
}
function GdataList(options){
	var defaults = {obj:false,src:false,animate:0,width:100,height:100}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj") ;
	var data = opts.data ? opts.data : alert("缺少data") ;
	var name = opts.name ? opts.name : alert("缺少name") ;
	var animate = opts.animate ;
	var datahtml = "";
	var spanhtml = "";
	var h;
	for(var i=0;i<data.length;i++){
		datahtml+='<div class="Gchoose-li"><a href="javascript:void(0)"></a><p>'+data[i].title+'</p><input value="'+data[i].id+'" name="'+name+'"></div>';
		spanhtml+='<span data-id="'+data[i].id+'">'+data[i].title+'&nbsp;|&nbsp;</span>';
	}
	if(data.length>=10){h=300;}else{h=data.length*30;}
	var html = '<div class="Gchoose-list an"><div style="height:'+h+'px;">'+datahtml+'</div><span class="Gchoose-list-jt"></span></div><div class="Gchoose-listbg"></div>'
	t.find(".Gchoose-list,.Gchoose-listbg").remove();
	t.find(">a[data-type='list']").html(spanhtml);
	t.append(html);
	if(animate==0){
		var opr = t.find(".Gchoose-list");
		opr.css({bottom:30,opacity:1,display:"block"});
		opr.delay(300).animate({bottom:0,opacity:0},200).delay(20).fadeOut(20);
	}else{
		var opr = t.find(".Gchoose-list");
		opr.hide();
	}
}
function Gformbegin(){
	//初始化
	$(".forInput[data-max!=''],.forArea[data-max!='']").each(function(index, element) {
		var	maxLength = $(this).attr("data-max");
		if(maxLength){
			var textlength = $(this).hasClass("forInput") ? $(this).prev("input").val().length : $(this).prev("textarea").val().length;
			$(this).html(textlength+"/"+maxLength);
		}
    });
	$(".Gchoose .Gbtn-pic").each(function(index, element) {
		var t = $(this).closest(".Gchoose");
		var src = t.find(">input").attr('data-src'),
            val=t.find(">input").val();
        src = src || get_upload_url(val);
		if(val!=""){GdataImg({obj:t,src:src,animate:1,savename:val});}
    });
	$(".Gchoose .Gbtn-shop").each(function(index, element) {
		var t = $(this).closest(".Gchoose");
		var a = t.find(">a");
		var name = a.attr("data-name");
		var data = [];
		a.find("span").each(function(index, element) {
			data.push({"id":$(this).attr("data-id"),"title":$(this).text()});
		});
		if(data.length>0){GdataList({obj:t,data:data,name:name,animate:1});}
    });
	$(".Gform .switch").each(function(index, element) {
		var s = $(this),
			type = s.find(">input").attr("type");
		if(s.hasClass("hover")){return;}
		if(s.find(">.newRadio span.hover").length>=1){return;}
		if(type=="radio"){
			var val = s.find(">input").val(),
				h = s.find(">.newRadio span[data-val='"+val+"']");
			if(val==""){console.log("第"+index+"个switch缺少value")}
			s.find(">.newRadio span").removeClass("hover");
			if(h.index()>0) s.addClass("hover");
			h.addClass("hover");
			if(s.attr("data-callback")){
				if(s.attr("data-callback")==""){
					return false;
				}
				var Gformcallback = s.attr("data-callback");
				window[Gformcallback].call(this,h)
			}
		}else if(type=="checkbox"){
			s.find(">.newRadio span").removeClass("hover");
			if(s.find(">.newRadio span").attr("data-name")){
				s.find(">input[type='checkbox']:checked").each(function(index) {
					var val = $(this).val(),
						name = $(this).attr("name"),
						h = s.find(">.newRadio span[data-name='"+name+"'][data-val='"+val+"']");
					if(val==""){console.log("switch缺少value")}
					h ? h.addClass("hover") : console.log("初始化错误");
					s.addClass("hover");
					if(s.attr("data-callback")){
						if(s.attr("data-callback")==""){
							return;
						}
						var Gformcallback = s.attr("data-callback");
						window[Gformcallback].call(this,h)
					}
				});
			}else{
				s.find(">input[type='checkbox']:checked").each(function(index) {
					var val = $(this).val(),
						h = s.find(">.newRadio span[data-val='"+val+"']");
					if(val==""){console.log("switch缺少value")}
					h.addClass("hover");
					s.addClass("hover");
					if(s.attr("data-callback")){
						if(s.attr("data-callback")==""){
							return;
						}
						var Gformcallback = s.attr("data-callback");
						window[Gformcallback].call(this,h)
					}
				});					
			};
		}
		if(s.attr("data-show")!=""){
			var showid = s.attr("data-show");
			if($("[data-show='"+showid+"']").length>1){
				var isshow = false ;
				$("[data-show='"+showid+"']").each(function(){
					if($(this).hasClass("hover")){isshow = true}
				});
				isshow ? $("#"+showid).show() : $("#"+showid).hide();
			}else{
				s.find(".newRadio span:eq(0)").hasClass("hover") ? $("#"+showid).hide() : $("#"+showid).show();
			}
		}
    });
}
/**
 * 图片上传
 * 
 *
 */
function open_img_uploader(opt,ue){
	var defaults = {
			obj:'',//对象
			Gform:true,//是否Gform
			type:0,//图片用处类型，用于删选
			width:640,//建议宽度
			height:320,//建议高度
            cropPresets:false,//裁切比例320x320
			//uploadUrl:"index.php?g=ImgResize&m=Upload&a=uploadFile",//上传地址
			menuType:1,//美图秀秀版本
			animate:0,//是否动画
			resizeFlag:"",//是否动画
			txtmsg:"",//备注
            callback:'open_img_callback',
			maxlength:false,//是否多张
            inputname:'',
			GdataImg:'upload'//上传类型(upload:单张,uploadmore:多张,editmore:多张修改)
		}
	var imguploadData = $.extend(true, {}, defaults, opt);
	var height;
	imguploadData.maxlength ? height = 650 : height = 500;
	art.dialog.data('imguploadData', imguploadData);
	art.dialog.open('index.php?g=ImgResize&m=Upload&a=index',{
		id: 'art_upload',
		title:"上传图片",
		lock: true,
		width:700,
		height:height,
		close:function(){
			if(ue){//百度编辑器
				$("#edui1_iframeholder").show();
				$(".Preview-mainCon-contenter-bg").show();
			}
		}
	});
}

function in_array(stringToSearch, arrayToSearch) {
    for (var s = 0; s < arrayToSearch.length; s++) {
        var thisEntry = arrayToSearch[s];
        if (thisEntry == stringToSearch) {
            return true;
        }
    }
    return false;
}

function open_img_callback(options){
    options = $.extend({},{
        obj:null,
        inputname:'',
        savename:null
    },options);
    if(options.obj){
        $(options.obj).attr('src',options.src);
    }
    if(options.inputname){
        $(options.inputname).val(options.savename);
    }
}

function open_music_uploader(opt){
	var defaults = {
			obj:'',//对象
			Gform:true,//是否Gform
			txtmsg:"",//备注
            callback:'open_music_callback',
            inputname:''
		}
	var musicuploadData = $.extend(true, {}, defaults, opt);
	art.dialog.data('musicuploadData', musicuploadData);
	art.dialog.open('index.php?g=ImgResize&m=Upload&a=musicList',{
		id: 'art_upload',
		title:"上传音乐",
		lock: true,
		width:700,
		height:500
	});
}
/*
* 默认图片上传回调函数
* options:{
*
*
* }
* */
function open_music_callback(options){
    if(options.obj){
        $(options.obj).attr('src',options.src);
    }
    if(options.inputname){
        $(options.inputname).val(options.savename);
    }
}

/*获取图片路径*/
function get_upload_url(img){
    var img_path = typeof(_global_url_upload)=='undefined'?'./Home/Upload':_global_url_upload;
    if(!img) return img;
    if(img.indexOf('http://') != -1) return img;
	if(img.indexOf('./Home/Upload/') != -1) return img.replace('./Home/Upload/',_global_url_upload+'/');
    return img_path+'/'+img;
}
/*上传视频*/
function get_video_url(opt,ue){
	art.dialog.data('videouploadData', opt);
	art.dialog.open('index.php?g=ImgResize&m=Upload&a=video',{
		id: 'art_upload',
		title:"添加视频",
		lock: true,
		width:700,
		height:320,
		close:function(){
			if(ue){//百度编辑器
				$("#edui1_iframeholder").show();
				$(".Preview-mainCon-contenter-bg").show();
			}
		}
	});
}

function parse_str(str,array){var strArr=String(str).replace(/^&/,"").replace(/&$/,"").split("&"),sal=strArr.length,i,j,ct,p,lastObj,obj,lastIter,undef,chr,tmp,key,value,postLeftBracketPos,keys,keysLen,fixStr=function(str){return decodeURIComponent(str.replace(/\+/g,"%20"))};if(!array){array=this.window}for(i=0;i<sal;i++){tmp=strArr[i].split("=");key=fixStr(tmp[0]);value=(tmp.length<2)?"":fixStr(tmp[1]);while(key.charAt(0)===" "){key=key.slice(1)}if(key.indexOf("\x00")>-1){key=key.slice(0,key.indexOf("\x00"))}if(key&&key.charAt(0)!=="["){keys=[];postLeftBracketPos=0;for(j=0;j<key.length;j++){if(key.charAt(j)==="["&&!postLeftBracketPos){postLeftBracketPos=j+1}else{if(key.charAt(j)==="]"){if(postLeftBracketPos){if(!keys.length){keys.push(key.slice(0,postLeftBracketPos-1))}keys.push(key.substr(postLeftBracketPos,j-postLeftBracketPos));postLeftBracketPos=0;if(key.charAt(j+1)!=="["){break}}}}}if(!keys.length){keys=[key]}for(j=0;j<keys[0].length;j++){chr=keys[0].charAt(j);if(chr===" "||chr==="."||chr==="["){keys[0]=keys[0].substr(0,j)+"_"+keys[0].substr(j+1)}if(chr==="["){break}}obj=array;for(j=0,keysLen=keys.length;j<keysLen;j++){key=keys[j].replace(/^['"]/,"").replace(/['"]$/,"");lastIter=j!==keys.length-1;lastObj=obj;if((key!==""&&key!==" ")||j===0){if(obj[key]===undef){obj[key]={}}obj=obj[key]}else{ct=-1;for(p in obj){if(obj.hasOwnProperty(p)){if(+p>ct&&p.match(/^\d+$/g)){ct=+p}}}key=ct+1}}lastObj[key]=value}}};
