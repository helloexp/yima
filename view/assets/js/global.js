(function($) {
	$.fn.hoverDelay = function(fnOver, fnOut, timeIn, timeOut) {

		var timeIn = timeIn || 100, timeOut = timeOut || 500, fnOut = fnOut || fnOver;

		var inTimer = [], outTimer = [];

		return this.each(function(i) {
			$(this).mouseenter(function() {
				var that = this;
				clearTimeout(outTimer[i]);
				inTimer[i] = setTimeout(function() {
					fnOver.apply(that);
				}, timeIn);
			}).mouseleave(function() {
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
$(document).ready(function(e) {//页面加载时载入
	leftMenuAction();
	uicontent();
	//标签切换
	go_history();
	Wcanaltabon();
	Wcanaltabon2();
	Wmaintabon();
	Service();
	integralService();
	Msgtabon();
	header();
	newMsg();
	windowheight();
	pageload();
	$("body").addClass("ready");
	if ($(".Gform").length >= 1) {
		Gform();
	}

	$('.artLoad').click(function() {
		if ($('#artLoadDiv').length == 0) {
			$('<div id="artLoadDiv"></div>').appendTo('body');
		}
		$("#artLoadDiv").load($(this).attr('href'));
		return false;
	});

	$('.artD').click(function() {
		var t = $(this), url = t.data('href'), title = t.data('title'), width = t.data('width') || 800;
		art.dialog.open(url, {
			title : title,
			width : width
		});
	});
	
	var tooltips = {
		show:function(that){
			$(".tooltip").remove();
			var tip = that.attr("data-gettitle")?that.attr("data-gettitle"):that.attr("title"),
				tip = tip?tip:that.attr("alt"),
				h = that.height()+parseInt(that.css("padding-top"))+parseInt(that.css("padding-bottom")),
				w = that.width()+parseInt(that.css("padding-left"))+parseInt(that.css("padding-right")),
				t = that.offset().top,
				l = that.offset().left,
				p = that.attr("data-placement") ? that.attr("data-placement") : "top";
				that.attr("data-gettitle",tip);
				if(!tip){return false;}
				tooltips.title = tip;
				tooltips.that = that;
				tooltips.that.attr("title","");
				tooltips.that.attr("alt","");
			var tooltip = '<div class="tooltip '+p+'"><div class="arrow"><i class="line9"></i><i class="line7"></i><i class="line5"></i><i class="line3"></i><i class="line1"></i><i class="line0"></i></div><div class="inner">'+tip+'</div></div>';
			$("body").append(tooltip);
			var tw = $(".tooltip").width();
			var th = $(".tooltip").height();
			p == "top" && (l = l-tw/2+w/2,t = t-th-7);
			p == "bottom" && (l = l-tw/2+w/2,t = t+h);
			p == "left" && (l = l-tw-7,t = t-th/2+h/2);
			p == "right" && (l = l+w,t = t-th/2+h/2);
			$(".tooltip").css({left:l,top:t})
		},
		hide:function(that){
			if(this.ishover){
				$(".tooltip").remove();
				tooltips.that = false;
				tooltips.title = false;
			}
		},
		ishover:true,
		title:false,
		that:false
	};
	$("body").on({
		mouseover:function(e){
			tooltips.ishover = false;
		},
		mouseout:function(){
			tooltips.ishover = true;
			setTimeout(function(){
				tooltips.hide();
			},500)
		}
	},".tooltip");
	$("body").on({
		mouseover:function(e){
			if($(this).closest(".edui-body-container").length>=1){return false;}
			tooltips.show($(this));
			tooltips.ishover = false;
		},
		mouseout:function(){
			if($(this).closest(".edui-body-container").length>=1){return false;}
			tooltips.ishover = true;
			setTimeout(function(){
				tooltips.hide();
			},300)
		}
	},"#main [title],#main [data-gettitle],#main [alt],.Diabody [title],.Diabody [data-gettitle],.Diabody [alt],#footer [title],#footer [data-gettitle]");
	$(".Gform .Gnmae,.Gform .Gname").each(function(){
		$(this).addClass("Gname");
		if($(this).text().indexOf("*")>=0){
			var text = $(this).text().replace("*","");
			$(this).html("<span>*</span>"+text)
		}
	})
});
$(window).resize(function(e) {//浏览器窗口变化自动载入
	windowheight();
});
function pageload(){
	var pageload = {
		t:null,
		config:function(){
			var _that = this;
			var loaddiv = function(){
					var obj = _that.t.closest(".page").attr("data-load");
					if($(obj).length<=0){
						if($("#"+obj).length>=1){
							obj = "#"+obj;
						}else if($("."+obj).length>=1){
							obj = "."+obj;
						}
					}
					return obj;
				}
			var loadcon = function(){
					var obj = _that.t.closest(".page").attr("data-load");
					return obj;
				}
			var _loaddiv = function(){
					return $(loaddiv());
				}
			return {
				url:_that.t.attr("data-href"),
				_pagediv:_that.t.closest(".newPage"),
				loadcon:loadcon(),
				loaddiv:loaddiv(),
				_loaddiv:_loaddiv(),
				_repeatdiv:_that.t.closest(".page").attr("data-repeat"),
				gopage:_that.t.closest(".newPage").find("input").val(),
				callback:_that.t.closest(".page").attr("data-callback"),
				loadtime:Date.now()
			}
		},
		callback:function(callback){
			var callback = callback || 0;
			if (callback) {
				window[callback].call(this, this.t)
			}
		},
		laodtime:true,
		loadfn:function(url){
			var _that = this;
			var eleTarget = _that.config();
			var historyUrl = {title:document.title,url:window.location.href};
			_that.t.addClass("current");
			eleTarget._pagediv.find("a").removeClass("current");
			eleTarget._loaddiv.addClass("pageLoading");
			eleTarget._pagediv.addClass("pageLoadinglock");
			$("body").append("<div id='loadNewCon' class='dn'></div>");
			$("body").append("<div id='loadNewPage' class='dn'></div>");
			//$(document).scrollTop(0);
			var ajaxLoad = function(url){
					$("#loadNewCon").load(url + '&loadcon='+eleTarget.loadcon + '&loadtime='+eleTarget.loadtime+' #wrapper', null, function(data,status){
					var loadHtml = $(data).find(eleTarget.loaddiv).html();
					var pageHtml = $(data).find(".newPage:eq("+eleTarget._pagediv.index()+")").html();
					if(eleTarget._repeatdiv){
						var endlist = $("#loadNewCon").find(eleTarget._repeatdiv).length;
						var end = $("#loadNewCon").find(".Dianodate,.nonedata").length;
						if(endlist<=0 && end<=0){
							eleTarget._loaddiv.html('<div class="Dianodate"><img src="__PUBLIC__/Image/member/nodata.gif" /><p>没有更多数据了~~</p></div>').removeClass("pageLoading");
						}else{
							eleTarget._loaddiv.html(loadHtml).removeClass("pageLoading");
						}
					}else{
						eleTarget._loaddiv.html(loadHtml).removeClass("pageLoading");
					}
					eleTarget._pagediv.html(pageHtml).removeClass("pageLoadinglock");
					$("#loadNewCon").remove();
					$("#loadNewPage").remove();
					_that.callback(eleTarget.callback);
					pageload.t = eleTarget._pagediv.find('.current');
				});
			}
			if(url){
				ajaxLoad(url);
				/*
				if(history.pushState){
					history.pushState(historyUrl,document.title, url.split('&loadtime=')[0]);
					history.replaceState(historyUrl,document.title, url.split('&loadtime=')[0]);
				}*/
			}else{
				var url = window.location.href;
				if(window.location.href.indexOf("&p=")<0){
					url = url+"&p=1";
				}
				ajaxLoad(url);
			}
		}
	}
	$("body").on("click",".page a",function(){
		pageload.t = $(this);
        var t = pageload.t;
        var config = pageload.config();
		if(config.loaddiv && !t.hasClass("next5")){
			if(config._pagediv.hasClass("pageLoadinglock") || config._loaddiv.hasClass("pageLoading") || t.hasClass("current")){return false;}
			var url = config.url;
			if(pageload.t.hasClass("gobtnp")){
				$(".gobtnp").unbind("click");
                url = config._pagediv.find("a:eq(0)").attr("data-href");
                url = url.split("&p=")[0]+"&p="+config.gopage+"&"+url.split("&p=")[1].split("&")[1];
			}
            pageload.loadfn(url);
			return false;
		}
	});
	/*
	$(window).bind("popstate", function(e) {
	    if(history.state){
			var state = history.state;
			var url = history.state.url;
			var title = history.state.title;
			var loadcon = parseURL(window.location.href).params.loadcon;
			pageload.t = $(".page[data-load='"+loadcon+"']").find("a:eq(0)");
        	pageload.loadfn();
		}
		if(pageload.laodtime && history.pushState){
			pageload.laodtime = false;
			history.pushState(window.location.href,document.title,window.location.href);
			history.replaceState(window.location.href,document.title,window.location.href);
		}
	});
	*/
}
function windowheight() {
	//$("#main").height("auto");
	var windowwidth = $(window).width();
	var windowheight = $(window).height();
	var hasnav = $(".sidenav,.new_sidenav").length;
	var heightfn = function (){
			/*
			if(hasnav==0){return false;}
			if($(".sidenav,.new_sidenav")[0].scrollHeight>windowheight){
				$(".sidenav,.new_sidenav").height($(".sidenav,.new_sidenav")[0].scrollHeight);
				$("#headerTwo").css({ position:"absolute"});
				$(".sidenav,.new_sidenav").css({ position:"absolute",top:-20});
				$(".shopNav + #container .sidenav,.shopNav + #container .new_sidenav,.shopNav + * + #container .sidenav,.shopNav + * + #container .new_sidenav").css({ position:"absolute",top:-84});
				$(".subcon").css({marginLeft:250,marginRight:80});
			}else{
				$("#headerTwo").css({ position:"fixed"});
				$(".sidenav,.new_sidenav").css({ position:"fixed",top:50});
				$(".shopNav + #container .sidenav,.shopNav + #container .new_sidenav,.shopNav + * + #container .sidenav,.shopNav + * + #container .new_sidenav").css({ position:"fixed",top:50});
				$(".subcon").css({marginLeft:260,marginRight:90});
			}
			var sidenavheight = $(".sidenav,.new_sidenav").height()-84;
			if(sidenavheight>$("#main").height()){
				$("#main").height($(".shopNav").length>=1?($(".sidenav,.new_sidenav").height()-80):$(".sidenav,.new_sidenav").height())
			}
			*/
		}
	var widthfn = function (){
			if(windowwidth<1200){
				$("#headerTwo").css({ position:"absolute"});
				$(".sidenav,.new_sidenav").css({ position:"absolute",top:-20});
				$(".shopNav + #container .sidenav,.shopNav + #container .new_sidenav,.shopNav + * + #container .sidenav,.shopNav + * + #container .new_sidenav").css({ position:"absolute",top:-84});
				$(".subcon").css({marginLeft:250,marginRight:80});
				//heightfn();
			}else{
				$("#headerTwo").css({ position:"fixed"});
				$(".sidenav,.new_sidenav").css({ position:"fixed",top:50});
				$(".shopNav + #container .sidenav,.shopNav + #container .new_sidenav,.shopNav + * + #container .sidenav,.shopNav + * + #container .new_sidenav").css({ position:"fixed",top:50});
				$(".subcon").css({marginLeft:260,marginRight:90});
				//heightfn();
			}
		}
	if($("#wrapper .header-cont").length>0 || $("#wrapper").hasClass("no")){
		return false;
	}
	if(hasnav==0){
		if($("#wrapper .IndHeader").length==0){
			$("#wrapper").addClass("nomalCon");
		}else{
			widthfn();
		}
	}else{
		widthfn();
	}
}


function header() {
	$(".useropr li span:last").css("border", "none");
	$(".usercenter").hoverDelay(function() {
		$(".useropr").fadeIn();
	}, function() {
		$(".useropr").fadeOut();
	});
}

//判断是否有更新公告
function newMsg() {
	var onlinecontant = $("#onlinecontant").text() ? true : false;
	if ($("#onlinecontant").text() == "0") {
		onlinecontant = false
	};
	if (onlinecontant) {
		$(".care i").addClass("newMsg")
	}
}

function uicontent() {
	$("div#global-libs p").not("#global-libs-content p").click(function() {
		$("div#global-libs p").removeClass("hover");
		for ( i = 0; i < $("div#global-libs p").not("#global-libs-content p").length; i++) {
			$("div#global-libs-content #globallibscontent:eq(" + i + ")").removeClass("hover");
			$("div#global-libs-content #globallibscontent:eq(" + i + ")").addClass("hide");
		}
		$(this).addClass("hover");
		$("div#global-libs-content #globallibscontent:eq(" + $(this).index() + ")").addClass("hover");
		windowheight();
	});
}
/*
 * 选项卡操作html
 * 
*<div class='Tab'>
* 	<div class='objTitle'>
* 		<p>标题一</p>
* 		<p>标题二</p>
* 		<p>标题三</p>
*   </div>
* 	<div class='objList'>
* 		内容一
*   </div>
*   <div class='objList'>
* 		内容二
*   </div>
*   <div class='objList'>
* 		内容三
*   </div>
*</div>
* 以下是调用
* WcanalTabonNew(Tab,objTitle,objList)
*/

function WcanalTabonNew(Tab,objTitle,objList) {	
	$('body').on('click',Tab+'>'+objTitle+'>*',function(){
		var _this=$(this);
		var p_a=_this.children('a').attr('data-val');
		$(Tab).children('input[type="hidden"]').val(p_a);
		var objTab = $(Tab+'>'+objTitle+'>*');
		var list=$(Tab +' '+objList);
		var activeClass = "active";
		objTab.removeClass(activeClass);
		list.hide();
		_this.addClass(activeClass);
		list.eq(_this.index()).show();
		windowheight();
	});	
}
function Wcanaltabon() {
	var objTab = $("#Wcanal-tabon>.Wcanal-tab-title>p");
	var objTabItem = $("#Wcanal-tabon>.Wcanal-tab-list");
	var activeClass = "Wcanal-tab-hover";
	objTab.click(function() {
		objTab.removeClass(activeClass);
		objTabItem.hide();
		$(this).addClass(activeClass);
		objTabItem.eq($(this).index()).show();
		windowheight();
        var callback = $(this).attr('callback');
        var elementId = $(this).attr('element-id');
        if (callback) {
            eval(callback);
        }
	});
}
function Wcanaltabon2() {
	var objTab = $("#Wcanal-tabon2>.Wcanal-tab-title>p");
	var objTabItem = $("#Wcanal-tabon2>.Wcanal-tab-list");
	var activeClass = "Wcanal-tab-hover";
	objTab.click(function() {
		objTab.removeClass(activeClass);
		objTabItem.hide();
		$(this).addClass(activeClass);
		objTabItem.eq($(this).index()).show();
		windowheight();
	});
}

function Msgtabon() {
	$("#Msg-tabon .Wcanal-tab-title p").click(function() {
		$("#Msg-tabon .Wcanal-tab-title p").removeClass("hover");
		$(".Wcanal-tab-list").hide();
		$(this).addClass("hover");
		$(".Wcanal-tab-list:eq(" + $(this).index() + ")").show();
	});
}

function Wmaintabon() {
	$("#Wmain-tabon .sidenav a").click(function() {
		$("#Wmain-tabon .sidenav li").removeClass("hover");
		$(".subcon").hide();
		$(this).closest("li").addClass("hover");
		$(".subcon:eq(" + $(this).closest("#Wmain-tabon .sidenav li").index() + ")").show();
	});
}

function go_history() {
	$(".ind-bread").click(function() {
		history.go(-1);
	});
}

//以下是常用处理函数
//从表单中获取 Input元素形成 json报文
function getFormData(f) {
	var $form = $(f);
	/**
	 * 此方法代码参考：http://css-tricks.com/snippets/jquery/serialize-form-to-json/
	 */
	var o = {};
	var a = $("input,textarea,select", $form).serializeArray();
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
if(!JSON){
	var JSON = {};
	JSON["stringify"]=function(o){var r=[];if(typeof o=="string"||o==null){return o}if(typeof o=="object"){if(!o.sort){r[0]="{";for(var i in o){r[r.length]='"'+i+'"';r[r.length]=":";r[r.length]='"'+this.stringify(o[i])+'"';r[r.length]=","}r[r.length-1]="}"}else{r[0]="[";for(var i=0;i<o.length;i++){r[r.length]=this.stringify(o[i]);r[r.length]=","}r[r.length==1?r.length:r.length-1]="]"}return r.join("")}return o.toString()};
}

//转换json为字符串
function JsonToStr(o) {
	if (JSON.stringify) {
		return JSON.stringify(o);
	}
	var r = [];
	if ( typeof o == "string" || o == null) {
		return o;
	}
	if ( typeof o == "object") {
		if (!o.sort) {
			r[0] = "{"
			for (var i in o) {
				r[r.length] = "\"" + i + "\"";
				r[r.length] = ":";
				r[r.length] = "\"" + JsonToStr(o[i]) + "\"";
				r[r.length] = ",";
			}
			r[r.length - 1] = "}"
		} else {
			r[0] = "[";
			for (var i = 0; i < o.length; i++) {
				r[r.length] = JsonToStr(o[i]);
				r[r.length] = ",";
			}
			r[r.length == 1 ? r.length : r.length - 1] = "]";
		}
		return r.join("");
	}
	return o.toString();
}

function Service() {
    //商旅文测试环境演示。。。。过后删除
    if(typeof global != "undefined" && typeof global.hideService != "undefined" && global.hideService == true){
        return ;
    }

	var html =['<div class="slidetoolbarContainr" monkey="slidetoolbar" id="__elm_0_5">', '<div class="slidetoolbar" style="right:0px">', '<div class="applist">', '<div class="sppitemwrap">', '<div class="appitem appitem-hook">', '<a href="/index.php?g=Home&m=Index&a=index" class="icon icon-2 icon-hook"><i class="sidebar_icon no1"></i>首页</a>', '</div>', '</div>', '<div class="sppitemwrap">', '<div class="appitem appitem-hook">', '<a href="/index.php?g=Home&m=Help&a=helpConter&type=1&leftId=zxwt" target="_blank" class="icon icon-3 icon-hook"><i class="sidebar_icon no2"></i>帮助<br>中心</a>', '</div>', '</div>', '<div class="sppitemwrap zxfk_tc">', '<div class="appitem appitem-hook">', '  <a href="javascript: void(0)" class="icon icon-4 icon-hook" onclick="onlinecontant()"><i class="sidebar_icon no3"></i>在线<br>反馈</a>', '</div>', ' </div>', ' <div class="sppitemwrap" id="gotop" style="display:none;">', ' <div class="appitem appitem-hook">', '<a href="javascript: void(0);"  class="icon icon-4 icon-hook"><i class="sidebar_icon no4"></i>返回<br>顶部</a>', '  </div>', ' </div>', '   </div>', ' </div>', ' <a href="javascript:;" hidefocus="true" class="slidetoolbar-closebtn slideclosebtn-open" title="收起" style="display: inline;"></a>', ' </div>'].join('');
	$(".service").append(html);
};
function integralService() {
	var html =['<div class="slidetoolbarContainr" monkey="slidetoolbar" id="__elm_0_5">', '<div class="slidetoolbar" style="right:0px">', '<div class="applist">', '<div class="sppitemwrap">', '<div class="appitem appitem-hook">', '<a  href="/index.php?g=Home&m=Index&a=index" class="icon icon-2 icon-hook"><i class="sidebar_icon no1"></i>首页</a>', '</div>', '</div>','<div class="sppitemwrap">', '<div class="appitem appitem-hook">', '<a  href="/index.php?g=Integral&m=IntegralIntroduce&a=index" class="icon icon-2 icon-hook"><i class="sidebar_icon no6"></i>业务<br/>介绍</a>', '</div>', '</div>', '<div class="sppitemwrap">', '<div class="appitem appitem-hook">', '<a href="/index.php?g=Home&m=Help&a=helpConter&type=1&leftId=zxwt" target="_blank" class="icon icon-3 icon-hook"><i class="sidebar_icon no2"></i>帮助<br>中心</a>', '</div>', '</div>', '<div class="sppitemwrap zxfk_tc">', '<div class="appitem appitem-hook">', '  <a href="javascript: void(0)" class="icon icon-4 icon-hook" onclick="onlinecontant()"><i class="sidebar_icon no3"></i>在线<br>反馈</a>', '</div>', ' </div>', ' <div class="sppitemwrap" id="gotop" style="display:none;">', ' <div class="appitem appitem-hook">', '<a href="javascript: void(0);"  class="icon icon-4 icon-hook"><i class="sidebar_icon no4"></i>返回<br>顶部</a>', '  </div>', ' </div>', '   </div>', ' </div>', ' <a href="javascript:;" hidefocus="true" class="slidetoolbar-closebtn slideclosebtn-open" title="收起" style="display: inline;"></a>', ' </div>'].join('');
	$(".integralService").append(html);
};
$(function() {
	$('#gotop').click(function() {
		$('body,html').animate({
			scrollTop : 0
		}, 800);
		return false;
	})
	$(".slidetoolbar-closebtn").click(function(e) {
		if (!$(this).hasClass("slideclosebtn-close")) {
			$(".slidetoolbar").css("right", "-50px");
			$(this).addClass("slideclosebtn-close");
			$(this).attr('title', '展开');
		} else {
			$(".slidetoolbar").css("right", "0");

			$(this).removeClass("slideclosebtn-close");
			$(this).attr('title', '收起');
		}
	});
	$(window).scroll(function() {
		t = $(document).scrollTop();
		if (t > 50) {
			$('#gotop').fadeIn('slow');
		} else {
			$('#gotop').fadeOut('slow');
		}
	})
})

function closeonlinecontant() {
	$(".onlinecontant,.onlinecontant_bg").remove();
}

//关闭指定弹出框
function art_close(id) {
	art.dialog.list[id].close();
}

//关闭所有弹出框
function all_art_close() {
	var list = art.dialog.list;
	for (var i in list) {
		list[i].close();
	};
}

//检查字符串长度
function check_lenght(total, id, obj) {
	var text = $(obj).is('div') ? $(obj).html() : $(obj).val();
	if (text == "") {
		text = $(obj).text()
	}
	if (text.length <= total) {
		$("#" + id).attr("style", "").html("还可以输入" + (total - text.length) + "个字");
	} else {
		$("#" + id).attr("style", "color:red;").html("已经超出" + (text.length - total) + "个字");
		//$(this).val(text.substring(0, total));
	}
}

//校验字数返回值
function check_lenght_btn(total, id, obj, btn) {
	var text = $(obj).val();
	var intLength = 0;
	if (text == "") {
		text = $(obj).text()
	};
	if (text == "") {
		return "3"
	};
	for (var i = 0; i < text.length; i++) {
		if ((text.charCodeAt(i) < 0) || (text.charCodeAt(i) > 255)) {
			intLength = intLength + 2;
		} else {
			intLength = intLength + 1;
		}
	}
	text = Math.ceil(intLength / 2);
	if (text <= total) {
		$("#" + id).attr("style", "").html("还可以输入<span>" + (total - text) + "</span>个字");
		$("#" + id).show();
		$("." + btn).removeClass("disabled");
		return "1";
	} else {
		$("#" + id).attr("style", "color:red;").html("已经超出<span style='color:red'>" + (text - total) + "</span>个字");
		$("#" + id).show();
		$("." + btn).addClass("disabled");
		return "2";
	}
}

function managercard() {
	var name = $("#managercardName").text();
	var position = $("#managercardPosition").text();
	var company = $("#managercardCompany").text();
	var qq = $("#managercardQQ").text();
	var weibo = $("#managercardWeibo").text();
	var weixin = $("#managercardWeixin").text();
	var mphone = $("#managercardMphone").text();
	var tphone = $("#managercardTphone").text();
	var mail = $("#managercardMail").text();
	var address = $("#managercardAddress").text();
	var img = $("#managercardImg").text();
	if (name != "") {
		name = "<div class='managercard-card-name'>" + name + "</div>";
	};
	if (position != "") {
		position = "<div class='managercard-card-position'>" + position + "</div>";
	};
	if (company != "") {
		company = "<div class='managercard-card-company'>" + company + "</div>";
	};
	if (qq != "") {
		qq = "<div class='managercard-card-qq'><i class='icon-cardqq'></i>" + qq + "</div>";
	};
	if (weibo != "") {
		weibo = "<div class='managercard-card-qq'><i class='icon-cardweibo'></i>" + weibo + "</div>";
	};
	if (weixin != "") {
		weixin = "<div class='managercard-card-weixin'><i class='icon-cardweixin'></i>" + weixin + "</div>";
	};
	if (mphone != "") {
		mphone = "<div class='managercard-card-mphone'><i class='icon-cardmphone'></i>" + mphone + "</div>";
	};
	if (tphone != "") {
		tphone = "<div class='managercard-card-tphone'><i class='icon-cardtphone'></i>" + tphone + "</div>";
	};
	if (mail != "") {
		mail = "<div class='managercard-card-mail'><i class='icon-cardmail'></i>" + mail + "</div>";
	};
	if (address != "") {
		address = "<div class='managercard-card-address'><i class='icon-cardaddress'></i>" + address + "</div>";
	};
	if (img != "") {
		img = "<img src='" + img + "' />";
	};
	var html = ['<div class="mySecretary"><div style="margin-left:50px;">', '<h2 style="display:inline-block;">翼码旺财客服</h2><span style="font-size:12px;color:#aaa;">　　竭诚为您服务</span>',  '<p>邮箱：7005@imageco.com.cn</p>', '<p>服务时间：8:00-22:00</p>', '<p>电话：400-882-7770</p>', '<p>QQ：400-880-7005</p>', '<p class="cl pb20"></p>','<a href="javascript:void(0)" class="close-mySec yellow" onclick="managercardclose()"></a>', '</div>'].join('');
	art.dialog({
		id : 'mySec',
		title : false,
		content : html,
		padding : "0",
		top : "50%",
		lock : true
	});
}

function oClass() {
	var teacher = $("#oClassTeacher").text() != "" ? $("#oClassTeacher").text() : "";
	var name = $("#oClassName").text() != "" ? $("#oClassName").text() : "";
	var time = $("#oClassTime").text() != "" ? $("#oClassTime").text() : "";
	var order = $("#oClassOrder").text() != "" ? $("#oClassOrder").text() : "";
	var url = $("#oClassUrl").text() != "" ? $("#oClassUrl").text() : "";
	var html = ['<div class="oClass">', '<div class="img"><img src="' + url + '" /><p>' + teacher + '</p></div>', '<div class="oClass-con">', '<p><strong>主题</strong>' + name + '</p>', '<p><strong>日期</strong>' + time + '</p>', '<p><strong>方式</strong>' + order + '</p>', '<p class="cl pb20"></p>', '<a href="javascript:void(0)" class="btn-join" onclick="windowBg()">我要报名</a>', '</div>', '<a href="javascript:void(0)" class="close-mySec blue" onclick="managercardclose()"></a>', '</div>'].join('');
	art.dialog({
		id : 'mySec',
		title : false,
		content : html,
		padding : "0",
		top : "50%",
		lock : true
	});
}

function managercardclose() {
	art.dialog({
		id : 'mySec'
	}).close();
}

function reload() {
	location.href = location.href;
}

function urlencode(str) {
	str = (str + '').toString();
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}


$(document).ready(function(e) {
	$(".new_sidenav dl dd").toggle(function(e) {
		$(this).next("div.new_subnav").css("display", "none");
		$(this).next("div.new_subnav").slideToggle("slow");
		$(this).addClass("active");
	}, function(e) {
		$(this).next("div.new_subnav").css("display", "block");
		$(this).next("div.new_subnav").slideToggle("slow");
		$(this).removeClass("active");
	});
});

function Gform() {
	if (Gformfn) {
		Gformfn = false;
	} else {
		return false;
	}
	Gformbegin();

	//绑定事件
	$("body").on("click", ".Gform .switch .newRadio span", function() {
		var t = $(this), s = t.closest(".switch"), type = t.closest(".newRadio").prev("input").attr("type"), n = s.find(".newRadio span"), a = s.hasClass("disabled"), b = s.hasClass("hover"), c = t.hasClass("hover"), i = s.find("input:eq(0)"), ii = s.find("input[name='checkswitch']");
		if (a) {
			return false;
		};
		if ( typeof (t.attr("data-checkbefor")) == "undefined" || t.attr("data-checkbefor") == "") {
			if (s.attr("data-before")) {
				if (s.attr("data-before") != "") {
					var callbefore = "undefined";
					var Gformbefore = s.attr("data-before");
					var callbefore = window[Gformbefore].call(this, t);
					var callbefore = typeof (callbefore) == "undefined" ? true : callbefore;
					if (!callbefore) {
						return false;
					};
					return false;
				}
			};
		};
		t.attr("data-checkbefor", "");
		if (type == "radio") {
			if (c) {
				return false;
			};
			var val = t.attr("data-val");
			if (val == 1)
				$('.key_pid').show();
			if (val == 0)
				$('.key_pid').hide();
			n.removeClass("hover");
			t.addClass("hover");
			b ? (s.removeClass("hover"), i.val(val), ii ? ii.attr("checked", false) : ii) : ($(this).addClass("hover"), s.addClass("hover"), i.val(val), ii ? ii.attr("checked", true) : ii);
		} else if (type == "checkbox") {
			c ? (t.removeClass("hover")) : (t.addClass("hover"));
			var check = s.find(">input[type='checkbox']");
			check.attr("checked", false);
			$(n).each(function(index) {
				if ($(this).hasClass("hover")) {
					var name = $(this).attr("data-name");
					var val = $(this).attr("data-val");
					if (name) {
						var check = s.find(">input[name='" + name + "']");
					} else {
						var check = s.find(">input[type='checkbox']:eq(" + index + ")");
					}
					check.attr("checked", true);
				}
			});
		}
		if (s.attr("data-show") != "") {
			var showid = s.attr("data-show");
			if ($("[data-show='" + showid + "']").length > 1) {
				var isshow = false;
				$("[data-show='" + showid + "']").each(function() {
					if ($(this).hasClass("hover")) {
						isshow = true
					}
				});
				isshow ? $("#" + showid).show() : $("#" + showid).hide();
			} else {
				s.find(".newRadio span:eq(0)").hasClass("hover") ? $("#" + showid).hide() : $("#" + showid).show();
			}
		}
		if (s.attr("data-callback")) {
			if (s.attr("data-callback") != "") {
				var Gformcallback = s.attr("data-callback");
				window[Gformcallback].call(this, t)
			}
		}
	})
	$("body").on("click", ".Gform .switch input", function(event) {
		event.stopPropagation();
	})
	$("body").on("click", ".Gform .Gadd .Gbtn-add", function() {
		var t = $(this), l = t.closest("li").find(".Gadd").length, s = t.closest(".Gadd"), n = s.attr("data-max"), m = s.attr("data-min");
		s.find("input").attr("value", s.find("input").val());
		var h = "<div class='Gadd Gadd-begin' data-min='" + m + "' data-max='" + n + "'>" + s.html() + "</div>";
		if (l < n) {
			if (s.attr("data-callback")) {
				if (s.attr("data-callback") != "") {
					var Gformcallback = s.attr("data-callback");
					h = "<div class='Gadd Gadd-begin' data-min='" + m + "' data-max='" + n + "' data-callback='" + Gformcallback + "'>" + s.html() + "</div>";
				}
			}
			s.after(h);
			if (s.attr("data-callback")) {
				if (s.attr("data-callback") != "") {
					var Gformcallback = s.attr("data-callback");
					window[Gformcallback].call(this, t);
				}
			}
			setTimeout(function() {
				$(".Gadd.Gadd-begin").removeClass("Gadd-begin");
			}, 100)
		} else {
			s.addClass("Gadd-erro");
			setTimeout(function() {
				s.removeClass("Gadd-erro");
			}, 300)
		};
	})
	$("body").on("click", ".Gform .Gadd .Gbtn-del", function() {
		var t = $(this), l = t.closest("li").find(".Gadd").length, s = t.closest(".Gadd"), n = s.attr("data-max"), m = s.attr("data-min");
		if (l > m) {
			s.addClass("Gadd-end");
			setTimeout(function() {
				s.remove();
				var str = "";
				if(t.attr("data-b") == 0) {
					var id = t.attr("data-id");
					var id_str = $("#batch_str").val();
					if(id_str.indexOf(id+",") != -1) {
						var str = id_str.replace(id+",", '');
					} else if(id_str.indexOf(","+id) != -1) {
						var str = id_str.replace(","+id, '');
					}
					
					batch_num--;

					$("#batch_str").val(str);
				} else if(t.attr("data-b") == 1) {
					var id = t.attr("data-id");
					var id_str = $("#bonus_str").val();
					if(id_str.indexOf(id+",") != -1) {
						var str = id_str.replace(id+",", '');
					} else if(id_str.indexOf(","+id) != -1) {
						var str = id_str.replace(","+id, '');
					}

					bonus_num--;

					$("#bonus_str").val(str);
				}

				if (s.attr("data-callback")) {
					if (s.attr("data-callback") != "") {
						var Gformcallback = s.attr("data-callback");
						window[Gformcallback].call(this, t);
					}
				};
			}, 300)
		} else {
			s.addClass("Gadd-erro");
			setTimeout(function() {
				s.removeClass("Gadd-erro");
			}, 300);
		};
	})
	$("body").on("click", ".Gform .Gbtn-more span", function() {
		var t = $(this).closest(".Gbtn-more"), s = t.closest(".Gmore"), d = s.find(".GmoreForm");
		if (s.hasClass("open")) {
			d.animate({
				height : 0
			}, 300, function() {
				s.removeClass("open");
				d.height("auto");
			});
			t.find("span").html("更多设置：<i></i>");
		} else {
			s.addClass("open");
			var h = d.height();
			d.height(0);
			d.animate({
				height : h
			}, 500, function() {
				d.height("auto");
			});
			t.find("span").html("收起设置：<i></i>");
		}
		if (s.attr("data-callback")) {
			if (s.attr("data-callback") != "") {
				var Gformcallback = s.attr("data-callback");
				window[Gformcallback].call(this, t)
			}
		}
	})

	$("body").on("click", ".Gform .Gchoose>img,.Gform .Gchoose a[data-type='list']", function() {
		var t = $(this), opr = t.closest(".Gchoose").find(">.Gchoose-opr")[0] ? t.closest(".Gchoose").find(">.Gchoose-opr") : t.closest(".Gchoose").find(">.Gchoose-list");
		opr.show().animate({
			bottom : 30,
			opacity : 1
		}, 200).removeClass("an");
	})
	$("body").on("click", ".Gform .Gchoose .Gchoose-oprbg,.Gform .Gchoose .Gchoose-listbg", function() {
		$(".Gchoose-opr,.Gchoose-list").animate({
			bottom : 0,
			opacity : 0
		}, 200).delay(20).fadeOut(20).addClass("an");
	})
	//新校验字符长度
	$("body").on("keyup", ".Gform input,.Gform textarea", function() {
		var t = $(this);
		var maxLength = t.next("span.maxTips").attr("data-max");
		var text = t.is('textarea') ? t.val() : t.val();
		var otherlength = text.split("\n").length - 1;
		var view = t.attr("class") ? t.attr("class").indexOf("Gview-") : -1;
		if (text == "") {
			text = t.text()
		};
		if (view != -1) {
			var type = t.attr("class").substring(view);
			if (type.indexOf(" ") != -1) {
				var type = type.substring(0, type.indexOf(" "));
			};
			type = type.replace("Gview-", "");
			$(".Gshow-" + type).html(text);
		};
		if (!maxLength) {
			return false;
		}
		if (t.is('textarea')) {
			var alllength = text.length + otherlength;
		} else {
			var alllength = text.length;
		}
		if (alllength <= maxLength) {
			t.next("span.maxTips").removeClass("erro").html(alllength + "/" + maxLength);
		} else {
			t.next("span.maxTips").addClass("erro").html(alllength + "/" + maxLength);
		}
	});

	$("body").on("click", ".Gform .Gbtn-pic,.Gform .Gbtn-picmore,.Gform .Gchoosemore-edit", function() {
		var data = $(this).closest(".Gchoosemore").find(".Gbtn-picmore").attr("data-rel") || $(this).attr("data-rel");
		var obj, maxlength, upload;
		if ($(this).hasClass("Gbtn-pic")) {
			obj = $(this).closest(".Gchoose");
			maxlength = false;
			upload = "upload";
		} else if ($(this).hasClass("Gbtn-picmore")) {
			obj = $(this).closest(".Gchoosemore");
			maxlength = 21;
			upload = "uploadmore";
		} else if ($(this).hasClass("Gchoosemore-edit")) {
			obj = $(this).closest(".Gchoosemore-list");
			maxlength = false;
			upload = "editmore";
		}
		var defaults = {
			obj : obj, //对象
			Gform : true, //是否Gform
			type : 0, //图片用处类型，用于删选
			width : 640, //建议宽度
			height : 320, //建议高度
			//uploadUrl:"http:\\192.168.0.35:8080\index.php?g=ImgResize&m=Resize&a=uploadFile1",//上传地址
			menuType : 1, //美图秀秀版本
			animate : 0, //是否动画
			resizeFlag : "", //是否动画
			txtmsg : "", //备注
			callback : "GdataImg", //callback
			maxlength : maxlength, //是否多张
			GdataImg : upload,//上传类型(upload:单张,uploadmore:多张,editmore:多张修改)
			hallFlag : 0 //是否来源电子券交易大厅(二级域名问题)
		}
		var imguploadData = $.extend(true, {}, defaults, eval('(' + data + ')'));
		if(typeof(imguploadData.maxlength)=="number"){
			var haslength = $(".Gchoosemore .Gchoosemore-list").length;
			imguploadData.maxlength = imguploadData.maxlength-haslength;
			if ($(this).hasClass("Gchoosemore-edit")) {
				imguploadData.maxlength = false;
			}
		}
		open_img_uploader(imguploadData);
		return;
	})
	$("body").on("click", ".Gform .Gbtn-music", function() {
		var data = $(this).attr("data-rel");
		var obj = $(this).closest(".Gchoose");
		var defaults = {
			obj : obj, //对象
			Gform : true, //是否Gform
			txtmsg : "", //备注
			callback : "GdataMusic"//callback
		}
		var musicuploadData = $.extend(true, {}, defaults, eval('(' + data + ')'));
		open_music_uploader(musicuploadData);
		return;
	})

	$("body").on("click", ".Gform .Gchoose-opr .Gchoose-opr-edit", function() {
		var t = $(this);
		t.closest(".Gchoose").find(".Gbtn-pic").click();
	})
	$("body").on("click", ".Gform .Gchoosemore-del,.Gform .Gchoose-opr-del", function() {
		var t = $(this);
		if(t.closest(".Gchoose").length==1){
			t.closest(".Gchoose").find(".Gchoose-opr,.Gchoose-oprbg,>img").remove();
			t.closest(".Gchoose").find(">input").val("")
		}else{
			var opts = t.closest(".Gchoosemore").find(".Gbtn-picmore").attr("data-rel");
			var defaults = {
				maxlength : false
			}
			var opts = $.extend(true, {}, defaults, eval('(' + opts + ')'));
			var haslength = t.closest(".Gchoosemore").find(".Gchoosemore-list").length-1;
			if(opts.maxlength<=haslength){
				t.closest(".Gchoosemore").find(".Gchoosemore-add").hide();
			}else{
				t.closest(".Gchoosemore").find(".Gchoosemore-add").show();
			}
			t.closest(".Gchoosemore-list").remove();
		}
		var type = t.attr("class").substring(t.attr("class").indexOf("Gview-"));
		if (type.indexOf(" ") != -1) {
			var type = type.substring(0, type.indexOf(" "));
		};
		type = type.replace("Gview-", "");
		$(".Gshow-" + type).is("img") ? $(".Gshow-" + type).attr("src","") : $(".Gshow-" + type).css("background-image", "url('')");
	})
	$("body").on("change", ".Gform .Gchoose .Gbtn-papers input[type=file]", function() {
		var t = $(this).closest(".Gchoose");
		var v = $(this).val();
		var a = t.find(">a:eq(0)");
		Math.max(v.lastIndexOf('/'), v.lastIndexOf('\\')) < 0 ? v = v : v = v.substring(Math.max(v.lastIndexOf('/'), v.lastIndexOf('\\')) + 1);
		a.text(v);
	})
	$("body").on("click", ".Gform .Gchoose .Gbtn-shop", function() {
		var t = $(this).closest(".Gchoose");
		var a = t.find(">a");
		var name = a.attr("data-name");
		var href = $(this).attr("data-url")
		art.dialog.open(href, {
			lock : true,
			title : name,
			width : 800,
			height : '80%'
		});
		/**
		 GdataList({
		 obj:t,
		 data:data,
		 name:name
		 });
		 **/
	});
	/**
	 $("body").on("click",".Gform .Gchoose-list .Gchoose-li a",function(){
	 var t = $(this),
	 r = t.closest(".Gchoose-li"),
	 l = t.closest(".Gchoose"),
	 id = r.find(">input").val(),
	 span = t.closest(".Gchoose").find(">a[data-type='list']").find(">span[data-id='"+id+"']");
	 l.find(".Gchoose-li").length==1 ? (l.find(".Gchoose-list,.Gchoose-listbg").remove(),span.remove()) : (r.remove(),span.remove());
	 })
	 **/

    $("body").on("change keyup click",".GsearchInput .input input",function(e){
        var t = $(this),
            i = t.closest(".input").find(".icon-searchOk"),
            v = t.val(),
            g = t.closest(".input").find(".GsearchVal"),
            l = t.closest(".input").find(".GsearchVal dl"),
            erro = 1;
        v=="" ? i.addClass("erro") : i.removeClass("erro");
        g.find(".GsearchValFixed").length==1 ? g.show() : g.show().append("<div class='GsearchValFixed'></div>") ;
        if(e.keyCode != 13){
            l.find("dd:not('.nosearchVal')").each(function(index, element) {
                var text = $(this).text();
                text.indexOf(v)>=0 ? ($(this).removeClass("dn"),erro++) : $(this).addClass("dn");
            });
            erro==1 ? l.find(".nosearchVal").removeClass("dn") : l.find(".nosearchVal").addClass("dn");
        }
        if(e.keyCode == 13){
            i.removeClass("erro").click();
            return false;
        }
    });
    $("body").on("click",".GsearchInput .input .GsearchVal dd:not('.nosearchVal')",function(e){
        var t = $(this),
            i = t.closest(".input").find("input"),
            v = t.text();
            i.val(v);
            t.closest(".GsearchVal").hide();
            t.closest(".input").find(".icon-searchOk").removeClass("erro").click();
    });
    $("body").on("click",".GsearchInput .input .GsearchVal .GsearchValFixed",function(e){
        $(this).closest(".GsearchVal").hide();
    });
    $("body").on("click",".GsearchInput .input .icon-searchOk",function(e){
		var t = $(this),
			i = t.closest(".input").find("input"),
			v = i.val(),
			g = t.closest(".input").find(".GsearchVal"),
			l = t.closest(".input").find(".GsearchVal dl");
        if(t.hasClass("erro")){return false;}
        g.hide();
        i.blur();
        t.addClass("erro");
		if (t.attr("data-callback")) {
			if (t.attr("data-callback") != "") {
				var Gformcallback = t.attr("data-callback");
				window[Gformcallback].call(this, t)
			}
		}
    });
}

function GdataImg(options) {
	var GuploadImg = {
		upload : function(opts) {
			if (opts.maxlength) {
				alert("请使用多张图片样式");
				return false;
			}
			var w = opts.width;
			var h = opts.height;
			if (w >= h) {
				w > 150 ? w = 150 : w;
				var size = "width:" + w + "px";
			} else {
				h > 150 ? h = 150 : h;
				var size = "height:" + h + "px";
			}
			var img = '<img src="' + src + '">';
			var html = ['<div class="Gchoose-opr an">', '<div class="Gchoose-opr-img"><img src="' + src + '" style="' + size + ';"></div>', '<div class="Gchoose-opr-opr">', '<a href="javascript:void(0)" class="Gchoose-opr-edit"></a>', '<a href="javascript:void(0)" class="Gchoose-opr-del"></a>', '</div>', '<span class="Gchoose-opr-jt"></span>', '</div>', '<div class="Gchoose-oprbg"></div>'].join('');
			t.find(".Gchoose-opr,.Gchoose-oprbg").remove();
			t.find(">img").remove();
			t.append(html);
			t.find(">input").after(img);
			t.find(">input").val(savename);
			if (opts.animate == 0) {
				var opr = t.find(".Gchoose-opr");
				opr.css({
					bottom : 30,
					opacity : 1,
					display : "block"
				});
				opr.delay(300).animate({
					bottom : 0,
					opacity : 0
				}, 200).delay(20).fadeOut(20);
			} else {
				var opr = t.find(".Gchoose-opr");
				opr.hide();
			}
			var type = t.attr("class").substring(t.attr("class").indexOf("Gview-"));
			if (type.indexOf(" ") != -1) {
				var type = type.substring(0, type.indexOf(" "));
			};
			type = type.replace("Gview-", "");
			$(".Gshow-" + type).is("img") ? $(".Gshow-" + type).attr("src", src) : $(".Gshow-" + type).css("background-image", "url(" + src + ")");
		},
		uploadmore : function(opts) {
			if ($(this).hasClass("Gchoosemore-edit")) {
				imguploadData.maxlength = false;
			}
			if (!opts.smallsrc) {
				alert("缺少smallsrc");
				return false;
			}
			if(typeof(opts.src)!="string"){
				for (var i = 0; i < opts.src.length; i++) {
					var html = ['<div class="Gchoosemore-list an">', '<input type="text" name="' + opts.inputname + '" value="' + opts.savename[i] + '" />', '<div class="Gchoosemore-img" style="background-image:url(' + opts.smallsrc[i] + ')"></div>', '<div class="Gchoosemore-opr">', '<a href="javascript:void(0)" class="Gchoosemore-edit"></a>', '<a href="javascript:void(0)" class="Gchoosemore-del"></a>', '</div>', '</div>'].join('');
					t.find(".Gchoosemore-add").before(html);
					
				}
			}else{
				var html = ['<div class="Gchoosemore-list an">', '<input type="text" name="' + opts.inputname + '" value="' + opts.savename+ '" />', '<div class="Gchoosemore-img" style="background-image:url(' + opts.smallsrc + ')"></div>', '<div class="Gchoosemore-opr">', '<a href="javascript:void(0)" class="Gchoosemore-edit"></a>', '<a href="javascript:void(0)" class="Gchoosemore-del"></a>', '</div>', '</div>'].join('');
				t.find(".Gchoosemore-add").before(html);
			}
			var haslength = t.find(".Gchoosemore-list").length;
			var maxdata = t.closest(".Gchoosemore").find(".Gbtn-picmore").attr("data-rel");
			var defaults = {
				maxlength : false
			}
			var maxdata = $.extend(true, {}, defaults, eval('(' + maxdata + ')'));
			if(maxdata.maxlength<=haslength){
				t.find(".Gchoosemore-add").hide();
			}else{
				t.find(".Gchoosemore-add").show();
			}
			if (opts.animate == 0) {
				var opr = t.find(".Gchoosemore-list.an");
				opr.css({
					bottom : 50,
					opacity : 0,
					display : "block"
				});
				opr.animate({
					bottom : 0,
					opacity : 1
				}, 500).delay(200).removeClass("an");
			}
		},
		editmore : function(opts) {
			if (!opts.smallsrc) {
				alert("缺少smallsrc");
				return false;
			}
			t.find(".Gchoosemore-img").css("background-image", "url(" + opts.smallsrc + ")");
			t.find(">input").val(savename);
		}
	}
	var defaults = {
		obj : false,
		src : false,
		animate : 0,
		width : 100,
		height : 100,
		maxlength : false,
		GdataImg : "upload"
	}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj");
	var src = opts.src ? opts.src : alert("缺少src"), savename = opts.savename;
	switch(opts.GdataImg) {
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

function GdataMusic(options) {
	var defaults = {
		obj : false,
		src : false
	}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj");
	var src = opts.src ? opts.src : alert("缺少src");
	var savename = opts.savename ? opts.savename : alert("缺少名称");
	t.find(">input").val(src);
	t.find(">a:not('.Gbtn-music')").attr("href", src);
	t.find(">a:not('.Gbtn-music')").attr("target", "_blank");
	t.find(">a:not('.Gbtn-music')").html(savename);
}

function GdataList(options) {
	var defaults = {
		obj : false,
		src : false,
		animate : 0,
		width : 100,
		height : 100
	}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj");
	var data = opts.data ? opts.data : alert("缺少data");
	var name = opts.name ? opts.name : alert("缺少name");
	var animate = opts.animate;
	var datahtml = "";
	var spanhtml = "";
	var h;
	for (var i = 0; i < data.length; i++) {
		datahtml += '<div class="Gchoose-li"><a href="javascript:void(0)"></a><p>' + data[i].title + '</p><input value="' + data[i].id + '" name="' + name + '"></div>';
		spanhtml += '<span data-id="' + data[i].id + '">' + data[i].title + '&nbsp;|&nbsp;</span>';
	}
	if (data.length >= 10) {
		h = 300;
	} else {
		h = data.length * 30;
	}
	var html = '<div class="Gchoose-list an"><div style="height:' + h + 'px;">' + datahtml + '</div><span class="Gchoose-list-jt"></span></div><div class="Gchoose-listbg"></div>'
	t.find(".Gchoose-list,.Gchoose-listbg").remove();
	t.find(">a[data-type='list']").html(spanhtml);
	t.append(html);
	if (animate == 0) {
		var opr = t.find(".Gchoose-list");
		opr.css({
			bottom : 30,
			opacity : 1,
			display : "block"
		});
		opr.delay(300).animate({
			bottom : 0,
			opacity : 0
		}, 200).delay(20).fadeOut(20);
	} else {
		var opr = t.find(".Gchoose-list");
		opr.hide();
	}
}

function Gformbegin() {
	//初始化
	$("[class^='Gview-']").each(function(index, element) {
		var t = $(this);
		var type = t.attr("class").substring(t.attr("class").indexOf("Gview-"));
		if (type.indexOf(" ") != -1) {
			var type = type.substring(0, type.indexOf(" "));
		};
		type = type.replace("Gview-", "");
		if (t.find("a").length > 0) {
			var t = $(this).closest(".Gchoose");
			var src = t.find(">input").attr('data-src'), val = t.find(">input").val();
			src = src || get_upload_url(val);
			$(".Gshow-" + type).is("img") ? $(".Gshow-" + type).attr("src", src) : $(".Gshow-" + type).css("background-image", "url(" + src + ")");
		} else {
			if (t.val() != '' && t.val() != undefined)
				$(".Gshow-" + type).html(t.val());
		}
	});
	$(".forInput[data-max!=''],.forArea[data-max!='']").each(function(index, element) {
		var maxLength = $(this).attr("data-max");
		if (maxLength) {
			var textlength = $(this).hasClass("forInput") ? $(this).prev("input").val().length : $(this).prev("textarea").val().length;
			$(this).html(textlength + "/" + maxLength);
		}
	});
	$(".Gchoose .Gbtn-pic").each(function(index, element) {
		var t = $(this).closest(".Gchoose");
		var src = t.find(">input").attr('data-src'), val = t.find(">input").val();
		src = src || get_upload_url(val);
		if (val != "") {
			GdataImg({
				obj : t,
				src : src,
				animate : 1,
				savename : val
			});
		}
	});
	$(".Gchoose .Gbtn-shop").each(function(index, element) {
		var t = $(this).closest(".Gchoose");
		var a = t.find(">a");
		var name = a.attr("data-name");
		var data = [];
		a.find("span").each(function(index, element) {
			data.push({
				"id" : $(this).attr("data-id"),
				"title" : $(this).text()
			});
		}); 
		if (data.length > 0) {
			GdataList({
				obj : t,
				data : data,
				name : name,
				animate : 1
			});
		}
	});
	$(".Gform .switch").each(function(index, element) {
		var s = $(this), type = s.find(">input").attr("type");
		if (s.hasClass("hover")) {
			return;
		}
		if (s.find(">.newRadio span.hover").length >= 1) {
			return;
		}
		if (type == "radio") {
			var val = s.find(">input").val(), h = s.find(">.newRadio span[data-val='" + val + "']");
			if (val == "") {
				console.log("第" + index + "个switch缺少value")
			}
			s.find(">.newRadio span").removeClass("hover");
			if (h.index() > 0)
				s.addClass("hover");
			h.addClass("hover");
			if (s.attr("data-callback")) {
				if (s.attr("data-callback") == "") {
					return false;
				}
				var Gformcallback = s.attr("data-callback");
				window[Gformcallback].call(this, h)
			}
		} else if (type == "checkbox") {
			s.find(">.newRadio span").removeClass("hover");
			if (s.find(">.newRadio span").attr("data-name")) {
				s.find(">input[type='checkbox']:checked").each(function(index) {
					var val = $(this).val(), name = $(this).attr("name"), h = s.find(">.newRadio span[data-name='" + name + "'][data-val='" + val + "']");
					if (val == "") {
						console.log("switch缺少value")
					}
					h ? h.addClass("hover") : console.log("初始化错误");
					s.addClass("hover");
					if (s.attr("data-callback")) {
						if (s.attr("data-callback") == "") {
							return;
						}
						var Gformcallback = s.attr("data-callback");
						window[Gformcallback].call(this, h)
					}
				});
			} else {
				s.find(">input[type='checkbox']:checked").each(function(index) {
					var val = $(this).val(), h = s.find(">.newRadio span:eq("+index+")");
					if (val == "") {
						console.log("switch缺少value")
					}
					h.addClass("hover");
					s.addClass("hover");
					if (s.attr("data-callback")) {
						if (s.attr("data-callback") == "") {
							return;
						}
						var Gformcallback = s.attr("data-callback");
						window[Gformcallback].call(this, h)
					}
				});
			};
		}
		if (s.attr("data-show") != "") {
			var showid = s.attr("data-show");
			if ($("[data-show='" + showid + "']").length > 1) {
				var isshow = false;
				$("[data-show='" + showid + "']").each(function() {
					if ($(this).hasClass("hover")) {
						isshow = true
					}
				});
				isshow ? $("#" + showid).show() : $("#" + showid).hide();
			} else {
				s.find(".newRadio span:eq(0)").hasClass("hover") ? $("#" + showid).hide() : $("#" + showid).show();
			}
		}
	});
}

/**
 * 图片上传
 *
 *
 */
function open_img_uploader(opt, ue) {
	var defaults = {
		obj : '', //对象
		Gform : true, //是否Gform
		type : 0, //图片用处类型，用于删选
		width : 640, //建议宽度
		height : 320, //建议高度
		cropPresets : false, //裁切比例320x320
		//uploadUrl:"",//上传地址
		menuType : 1, //美图秀秀版本
		animate : 0, //是否动画
		resizeFlag : "", //是否动画
		txtmsg : "", //备注
		callback : 'open_img_callback',
		maxlength : false, //是否多张
		inputname : '',
		GdataImg : 'upload',//上传类型(upload:单张,uploadmore:多张,editmore:多张修改)
		hallFlag : 0, //是否来源电子券交易大厅(二级域名问题)
		thumb:false
	};
	var imguploadData = $.extend(true, {}, defaults, opt);
	var url,height;
	typeof(imguploadData.maxlength)=="number" ? height = 670 : height = 520;
	art.dialog.data('imguploadData', imguploadData);
	if(imguploadData.user==0){
		if(imguploadData.hallFlag == 1){
			url = 'index.php?g=Hall&m=HallBase&a=meitulogin';
		}else{
			url = 'index.php?g=ImgResize&m=Meitulogin&a=index';
		}
		art.dialog.open(url, {
			id : 'art_upload',
			title : "上传图片",
			width : 860,
			height : height,
			close : function() {
				if (ue) {//百度编辑器
					$("#edui1_iframeholder").show();
					$(".Preview-mainCon-contenter-bg").show();
				}
				$("#helpMsg").removeClass("fixed");
			}
		});
	}else{
		var userid = "";
		if(imguploadData.userid){
			userid = "&userid="+imguploadData.userid;
			imguploadData["user"] = 0;
		}
		if(imguploadData.hallFlag == 1){
			url = 'index.php?g=Hall&m=HallBase&a=upload'+userid;
		}else{
			url = 'index.php?g=ImgResize&m=Upload&a=index'+userid;
		}
		art.dialog.open(url, {
			id : 'art_upload',
			title : "上传图片",
			width : 860,
			height : height,
			close : function() {
				if (ue) {//百度编辑器
					$("#edui1_iframeholder").show();
					$(".Preview-mainCon-contenter-bg").show();
				}
				$("#helpMsg").removeClass("fixed");
			}
		});
	};
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

function open_img_callback(options) {
	options = $.extend({}, {
		obj : null,
		inputname : '',
		savename : null
	}, options);
	if (options.obj) {
		$(options.obj).attr('src', options.src);
	}
	if (options.inputname) {
		$(options.inputname).val(options.savename);
	}
}

function open_music_uploader(opt) {
	var defaults = {
		obj : '', //对象
		Gform : true, //是否Gform
		txtmsg : "", //备注
		callback : 'open_music_callback',
		inputname : ''
	}
	var musicuploadData = $.extend(true, {}, defaults, opt);
	art.dialog.data('musicuploadData', musicuploadData);
	art.dialog.open('index.php?g=ImgResize&m=Upload&a=musicList', {
		id : 'art_upload',
		title : "上传音乐",
		lock : true,
		width : 700,
		height : 500
	});
}

/*
 * 默认图片上传回调函数
 * options:{
 *
 *
 * }
 * */
function open_music_callback(options) {
	if (options.obj) {
		$(options.obj).attr('src', options.src);
	}
	if (options.inputname) {
		$(options.inputname).val(options.savename);
	}
}

/*获取图片路径*/
function get_upload_url(img) {
	var img_path = typeof (_global_url_upload) == 'undefined' ? './Home/Upload' : _global_url_upload;
	if (!img)
		return img;
	if (img.indexOf('http://') != -1)
		return img;
	if (img.indexOf('./Home/Upload/') != -1 && typeof (_global_url_upload) != 'undefined')
		return img.replace('./Home/Upload/', _global_url_upload + '/');
	return img_path + '/' + img;
}

/*上传视频*/
function get_video_url(opt, ue) {
	art.dialog.data('videouploadData', opt);
	if(opt.hallFlag===1){
		var url = 'index.php?g=Hall&m=HallBase&a=video';
	}else{
		var url = 'index.php?g=ImgResize&m=Upload&a=video';
	}
	art.dialog.open(url, {
		id : 'art_upload',
		title : "添加视频",
		lock : true,
		width : 700,
		height : 320,
		close : function() {
			if (ue) {//百度编辑器
				$("#edui1_iframeholder").show();
				$(".Preview-mainCon-contenter-bg").show();
			}
		}
	});
}

function parse_str(str, array) {
	var strArr = String(str).replace(/^&/, "").replace(/&$/, "").split("&"), sal = strArr.length, i, j, ct, p, lastObj, obj, lastIter, undef, chr, tmp, key, value, postLeftBracketPos, keys, keysLen, fixStr = function(str) {
		return decodeURIComponent(str.replace(/\+/g, "%20"))
	};
	if (!array) {
		array = this.window
	}
	for ( i = 0; i < sal; i++) {
		tmp = strArr[i].split("=");
		key = fixStr(tmp[0]);
		value = (tmp.length < 2) ? "" : fixStr(tmp[1]);
		while (key.charAt(0) === " ") {
			key = key.slice(1)
		}
		if (key.indexOf("\x00") > -1) {
			key = key.slice(0, key.indexOf("\x00"))
		}
		if (key && key.charAt(0) !== "[") {
			keys = [];
			postLeftBracketPos = 0;
			for ( j = 0; j < key.length; j++) {
				if (key.charAt(j) === "[" && !postLeftBracketPos) {
					postLeftBracketPos = j + 1
				} else {
					if (key.charAt(j) === "]") {
						if (postLeftBracketPos) {
							if (!keys.length) {
								keys.push(key.slice(0, postLeftBracketPos - 1))
							}
							keys.push(key.substr(postLeftBracketPos, j - postLeftBracketPos));
							postLeftBracketPos = 0;
							if (key.charAt(j + 1) !== "[") {
								break
							}
						}
					}
				}
			}
			if (!keys.length) {
				keys = [key]
			}
			for ( j = 0; j < keys[0].length; j++) {
				chr = keys[0].charAt(j);
				if (chr === " " || chr === "." || chr === "[") {
					keys[0] = keys[0].substr(0, j) + "_" + keys[0].substr(j + 1)
				}
				if (chr === "[") {
					break
				}
			}
			obj = array;
			for ( j = 0, keysLen = keys.length; j < keysLen; j++) {
				key = keys[j].replace(/^['"]/, "").replace(/['"]$/, "");
				lastIter = j !== keys.length - 1;
				lastObj = obj;
				if ((key !== "" && key !== " ") || j === 0) {
					if (obj[key] === undef) {
						obj[key] = {}
					}
					obj = obj[key]
				} else {
					ct = -1;
					for (p in obj) {
						if (obj.hasOwnProperty(p)) {
							if (+p > ct && p.match(/^\d+$/g)) {
								ct = +p
							}
						}
					}
					key = ct + 1
				}
			}
			lastObj[key] = value
		}
	}
};

$(function() {

	function opensearch() {
		$(".more-filter").addClass("active");
		$(".more-filter").html("收起筛选");
		$(".SearchArea").addClass("extendMode");
	}
	function closesearch() {
		$(".more-filter").removeClass("active");
		$(".SearchArea").removeClass("extendMode");
		$(".more-filter").html("更多筛选");
	}
	
	$("body").on("click",".more-filter",function(e) {
		if($(".SearchArea").hasClass("extendMode")){
			closesearch()
		}else{
			opensearch()
		}
	});

	var label = function(){
			var width = 0;
			$('.SearchAreaLeft>label').each(function(index, element) {
                width = width+$(this).outerWidth(true);
            });
			return width;
		};
	if(label() > $(".SearchAreaLeft").width()){
		if($('.SearchAreaRight .more-filter').length==0){
			$(".SearchAreaRight").append('<a href="javascript:void(0);" class="more-filter">更多筛选</a>');
			if($(".SearchArea").hasClass("extendMode")){
				$(".more-filter").html("收起筛选");
			}
		}else{
			$('.more-filter').show();
			if($(".SearchArea").hasClass("extendMode")){
				$(".more-filter").html("收起筛选");
			}
		}
	};
})

//检测门店弹窗是否为多选门店
function isMultiselect(){
//单选门店标识
	var listArray = ['g=WangPaiPai&m=Index','g=LabelAdmin&m=Channel'];
	var ParentUrl= window.location.href;
	var selectArray=ParentUrl.split("?");
	var queryStr1 = selectArray[1];
	queryStr1 =  selectArray[1].split("&a=")[0];
	if ($.inArray(queryStr1,listArray) == -1){
		//alert("多选")
		$("#shoplist li").click(function(){
			if($(this).hasClass("selected"))
			{
				$(this).removeClass("selected");
				$(this).children(":checkbox").attr("checked",'checked');
				var storeNum = parseInt($('#number').text());
				$('#number').text(storeNum-1);
				var snm = $(this).find('input:hidden').val();
				$.each(shopArray, function(key, val) {
					if(snm == val){
						shopArray.splice(key, 1);
					}
				})
			
			}
			else
			{
				if(groupArray.length > 0){
					$('#number').text('0');
					shopArray = [];
					groupArray = [];
					$('#groupList li').removeClass("selected");
                	$('#groupList li').children(":checkbox").attr("checked",'checked');
				}
				var storeNum = parseInt($('#number').text());
				$('#number').text(storeNum+1);

				$(this).addClass("selected");
				$(this).children(":checkbox").attr("checked",'checked');
				shopArray.push($(this).find('input').val());
			}
			
			var selctlng = $("#shoplist li.selected").length; //选中的li
			var alllng = $("#shoplist li").length; //所有的li
			if(selctlng != alllng)
			{
				//如果不是全选
				$(".frm_checkbox_label").removeClass("selected");
			    $(".frm_checkbox").attr("checked",false);
				
			}
			else
			{
				//如果是全选
				$(".frm_checkbox_label").removeClass("selected").addClass("selected");
			    $(".frm_checkbox").attr("checked",true);
				
			}

		}); 
	}
	else{
		$('.sweet_tips2').addClass('dn');
		//alert("单选")
		$(".frm_checkbox_label").hide();
		$("#shoplist").css("padding-top","0");
		$("#frm_checkbox_label").parent("p").hide();
		$("#shoplist li").click(function(){
			if($(this).hasClass("selected"))
			{
				$("#shoplist li").removeClass("selected");
				$(this).children(":checkbox").attr("checked",'checked');
				$(".frm_checkbox_label").find(".frm_checkbox").attr("checked",false);
				$(".frm_checkbox_label").removeClass("selected");
				shopArray = [];
			}
			else
			{
				$("#shoplist li").removeClass("selected");
				$(this).addClass("selected");
				$(".frm_checkbox_label").find(".frm_checkbox").attr("checked",false);
				$(this).children(":checkbox").attr("checked",'checked');
				shopArray = [];
				shopArray.push($(this).find('input').val());
			}
		});
	}
}
function leftMenuAction() {
	var Accordion = function(el, multiple) {
		this.el = el || {};
		this.multiple = multiple || false;

		var links = this.el.find('.link');
		links.on('click', {
			el : this.el,
			multiple : this.multiple
		}, this.dropdown)
	}

	Accordion.prototype.dropdown = function(e) {
		var $el = e.data.el;
		$this = $(this), $next = $this.next();

		$next.slideToggle();
		if ($next.children().length > 0) {
			$this.parent().toggleClass('open');

			if (!e.data.multiple) {
				$el.find('.submenu').not($next).slideUp().parent().removeClass('open');
				$el.find('.submenu').not($next).slideUp().parent().removeClass('open2');
			};
		} else {
			$this.parent().toggleClass('open2');

			if (!e.data.multiple) {
				$el.find('.submenu').not($next).slideUp().parent().removeClass('open2');
			};
		}

	}
	var accordion = new Accordion($('#accordion'), false);

	$(".submenu li").click(function(e) {
		$(".submenu li").removeClass("hover");
		$(this).addClass("hover");
	});

}


/**
 * @classDescription 模拟Marquee，无间断滚动内容
 * @author Aken Li(www.kxbd.com)
 * @DOM
 *  	<div id="marquee">
 *  		<ul>
 *   			<li></li>
 *   			<li></li>
 *  		</ul>
 *  	</div>
 * @CSS
 *  	#marquee {overflow:hidden;width:200px;height:50px;}
 * @Usage
 *  	$("#marquee").kxbdMarquee(options);
 * @options
 *		isEqual:true,		//所有滚动的元素长宽是否相等,true,false
 *  	loop:0,				//循环滚动次数，0时无限
 *		direction:"left",	//滚动方向，"left","right","up","down"
 *		scrollAmount:1,		//步长
 *		scrollDelay:20		//时长
 */! function(a) {
	a.fn.kxbdMarquee = function(b) {
		var c = a.extend({}, a.fn.kxbdMarquee.defaults, b);
		return this.each(function() {
			function l() {
				var b, a = "left" == c.direction || "right" == c.direction ? "scrollLeft" : "scrollTop";
				return c.loop > 0 && (k += c.scrollAmount, k > i * c.loop) ? (d[a] = 0, clearInterval(m)) : ("left" == c.direction || "up" == c.direction ? ( b = d[a] + c.scrollAmount, b >= i && (b -= i), d[a] = b) : ( b = d[a] - c.scrollAmount, 0 >= b && (b += i), d[a] = b),
				void 0)
			}

			var k, m, b = a(this), d = b.get(0), e = b.width(), f = b.height(), g = b.children(), h = g.children(), i = 0, j = "left" == c.direction || "right" == c.direction ? 1 : 0;
			g.css( j ? "width" : "height", 1e4), c.isEqual ? i = h[j?"outerWidth":"outerHeight"]() * h.length : h.each(function() {
				i += a(this)[j?"outerWidth":"outerHeight"]()
			}), ( j ? e : f) > i || (g.append(h.clone()).css( j ? "width" : "height", 2 * i), k = 0, m = setInterval(l, c.scrollDelay), b.hover(function() {
				clearInterval(m)
			}, function() {
				clearInterval(m), m = setInterval(l, c.scrollDelay)
			}))
		})
	}, a.fn.kxbdMarquee.defaults = {
		isEqual : !0,
		loop : 0,
		direction : "left",
		scrollAmount : 1,
		scrollDelay : 20
	}, a.fn.kxbdMarquee.setDefaults = function(b) {
		a.extend(a.fn.kxbdMarquee.defaults, b)
	}
}(jQuery); 

    
/*
 * 在线签约变单自动补齐功能
 * 
 */
function autoAdd(id){			
/* 统一宽度 自动居中*/
	var num = 0;
	var id=$(id);
	id.find('ul').each(function(){
		var ul_span=$(this).find('li span');
		var li_num = $(this).find('li').length;
		var data=[];
		for(i=0;i<ul_span.length;i++){	
			var width=ul_span.eq(i).width();
			    data.push(width);
			if(data.length==ul_span.length){
				var max_w=Math.max.apply(null, data);//最大值
			}
		}
		ul_span.width(max_w);
		if(li_num>num){
			num=li_num;//取出最多的一个元素
		}
	});
	
/*自动补齐操作 */	
	id.children('ul').each(function(index){
		var _this=$(this);
		var li_len=_this.find('li').length;
		if(num>li_len){
			for(i=0;i<num-li_len;i++){
				$(this).append("<li>&nbsp;</li>");
			}
		}
     _this.parent().children('ul').last().addClass('last');
     this_dl=_this.parent().children('.title').find('dl').last().addClass('last');    
	});

}

function parseURL(url) {
	var a = document.createElement('a');
	a.href = url;
	return {
		source: url,
		protocol: a.protocol.replace(':', ''),
		host: a.hostname,
		port: a.port,
		query: a.search,
		params: (function () {
			var ret = {},
				seg = a.search.replace('?', '').split('&'),
				len = seg.length, i = 0, s;
			for (; i < len; i++) {
				if (!seg[i]) {
					continue;
				}
				s = seg[i].split('=');
				ret[s[0]] = s[1];
			}
			return ret;
		})(),
		file: (a.pathname.match(/\/([^\/?#]+)$/i) || [, ''])[1],
		hash: a.hash.replace('#', ''),
		path: a.pathname.replace(/^([^\/])/, '/$1'),
		relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [, ''])[1],
		segments: a.pathname.replace(/^\//, '').split('/')
	};
}

/**
 *
 * @param batch_id
 * @param batch_type
 * @param callback
 * @returns {boolean}
 */
function show(batch_id, batch_type, callback){
	var url = 'index.php?g=LabelAdmin&m=CjSetShow&a=preview&batch_id=' + batch_id + '&batch_type=' + batch_type;
	$.get(url,function(d){
		if (typeof d.status && d.status == 0) {
			art.dialog({
				content: d.info
			});
		} else {
			art.dialog({
				content: "<img src='"+ d.info+"' border='0'>"
			});
		}
		if (arguments.length == 3 && typeof  callback === 'function') {
			callback();
		}
	},'json');
	return false;
}






