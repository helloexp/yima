(function ($){
	
	$.fn.jSitebar = function(opts){
		var defaults = {
			pluginName:"customer",
			image:"",
			text:"",
			bottom:"20px",
			bodyWidth:"1113px",
			callback:function(){
				alert("您没有为自定义组建添加处理方法，请在初始化的时候加上参数callback，并实现自己的处理方法。");
			},
			theme:{
				width:"60px",
				height:"60px",
				borderWidth:"0px",
				borderColor:"#CCCCCC",
				bgColor:"#d9d9d9",
				font:"14px Microsoft YaHei"
			},
			other:{
				slideUp:270,
				slideDown:270,
			}
		}
		var opts = $.extend(defaults, opts);
		init(this,opts);
		initTheme(this,opts.theme);
	};

	var init=function(target,opts){
		switch(opts.pluginName){
			case "backtop":
				backtopPlugin(target,opts);
				break;
			case "backbottom":
				backbottomPlugin(target,opts);
				break;
			default:
				customerPlugin(target,opts);
		}
		var barPos=parseInt(opts.bodyWidth)+(parseInt(opts.theme.width)+parseInt(opts.theme.borderWidth))*2;
		$(window).resize(function(){
			if(parseInt($(this).width())<barPos){
				$(target).css({"opacity":0.4});
				$(target).stop(true,true).animate({right:0},"fast")
			}else{
				$(target).css({"opacity":1.0});
				$(target).stop(true,true).animate({right:0},"fast")
			}
		});
		if(parseInt($(window).width())<barPos){
			$(target).css({"right":0,"bottom":opts.bottom,"opacity":0.4});
		}else{
			$(target).css({"right":"10px","bottom":opts.bottom});
		}
	}

	var backtopPlugin=function(target,opts){
		var params=new Object();
		params.t=$(target);
		params.other=opts.other;
		params.bodyWidth=opts.bodyWidth;
		var text=opts.text;
		if(opts.image.length>0){
			params.d1=$("<div class='d1'></d1>");
			params.d1.css({"background-image":"url(\""+opts.image+"\")"});
		}else{
			params.d1=$("<div class='d1'></d1>");
			params.d1.css({"background-image":"url(\"img/arrow-top.png\")"});
		}
		params.d2=$("<div class='d2'>"+opts.text+"</d2>");
		if(text&&text.length>0){
			params.d2.text(text);
		}else{
			params.d2.text("返回顶部");
		}
		params.callback=function(){
			$("html,body").stop(true,true).animate({scrollTop:0},270);
		};
		initPlugin(params);
	}
	var backbottomPlugin=function(target,opts){
		var params=new Object();
		params.t=$(target);
		params.other=opts.other;
		params.bodyWidth=opts.bodyWidth;
		var text=opts.text;
		if(opts.image.length>0){
			params.d1=$("<div class='d1'></d1>");
			d1.css({"background-image":"url(\""+opts.image+"\")"});
		}else{
			params.d1=$("<div class='d1'></d1>");
			params.d1.css({"background-image":"url(\"img/arrow-bottom.png\")"});
		}
		params.d2=$("<div class='d2'></d2>");
		if(text&&text.length>0){
			params.d2.text(text);
		}else{
			params.d2.text("返回底部");
		}
		params.callback=function(){
			$("html,body").stop(true,true).animate({scrollTop:$(document).height()},200);
		}
		initPlugin(params);
	}
	var customerPlugin=function(target,opts){
		var params=new Object();
		params.t=$(target);
		params.other=opts.other;
		params.bodyWidth=opts.bodyWidth;
		params.callback=opts.callback;
		if(opts.image.length==0){
			params.d1=$("<div class='d1'>"+opts.text+"</d1>");
		}else{
			params.d1=$("<div class='d1'></d1>");
			params.d1.css({"background-image":"url(\""+opts.image+"\")","background-repeat":"no-repeat","background-position":"center"});
		}
		params.d2=$("<div class='d2'>"+opts.text+"</d2>");
		initPlugin(params);
	}
	var initPlugin=function(params){
		params.t.append(params.d1).append(params.d2);
		params.oldBg=params.t.css("borderColor");
		params.newBg=params.d2.css("borderColor");
		params.t.hover(function(){
			if(parseInt($(window).width())<parseInt(params.bodyWidth)+parseInt(params.other.width)*2){
					$(this).css({"opacity":1.0});
				}
				$(this).find(".d1").stop(true,true).slideUp(params.other.slideUp);
				$(this).css({borderColor:params.newBg}).find(".d2").stop(true,true).slideDown(params.other.slideDown);
			},function(){
				if(parseInt($(window).width())<parseInt(params.bodyWidth)+parseInt(params.other.width)*2){
					$(this).css({"opacity":0.4});
				}
				$(this).find(".d2").stop(true,true).slideUp(params.other.slideUp);
				$(this).css({borderColor:params.oldBg}).find(".d1").stop(true,true).slideDown(params.other.slideDown);
			}
		);
		params.t.bind("click",params.callback);
	}

	var initTheme=function(target,theme){
		$(target).css({
			width:theme.width,
			height:theme.height,
			border:theme.borderWidth+" solid "+theme.borderColor,
			backgroundColor:theme.bgColor,
			font:theme.font
		}).find("div").css({
			width:theme.width,
			height:theme.height,
			borderWidth:theme.borderWidth
		});
	}

})(jQuery); 