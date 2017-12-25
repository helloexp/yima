
var emotions = new Array();
var categorys = new Array();// 分组
var uSinaEmotionsHt = new Hashtable();

$(document).ready(function(){
	//图片上传
	$('.sina-img').unbind().bind("click",function(event){
		event.stopPropagation();
		$('#emotions').hide();
		$('#Wlayer').remove();
		var WlayerTop = $(this).offset().top+35;
		var WlayerLeft = $(this).offset().left -5;
		var sinaimg=[
			'<div class="title">图片上传<span>(建议大小:3M以内)</span></div>',
			'<div class="sinaUp-img">',
        	'<li><a href="javascript:void(0)" class="bd-r"><span><i class="icon-sinaimg1"></i>添加图片<input type="file" name="up_pic" id="up_pic" value=""/></span></a></li>',
        	'<li class="last"><a href="javascript:void(0)" class="sinaxiuxiu"><span><i class="icon-sinaimg2"></i>拼图上传</span></a></li>',
			'</div>'].join('');
		$('body').append('<div id="Wlayer"><div class="Wlayer-icon"></div><div class="Wlayer-con">'+sinaimg+'</div></div>');
		$('#Wlayer').css({top: WlayerTop, left: WlayerLeft});
	});
	
	//视频上传
	$('.sina-movie').unbind().bind("click",function(event){
		event.stopPropagation();
		$('#emotions').hide();
		$('#Wlayer').remove();
		var WlayerTop = $(this).offset().top+35;
		var WlayerLeft = $(this).offset().left -5;
		var sina=[
			'<div class="title">视频上传</div>',
			'<div class="fn plr10 pt5"><p>目前已支持<a target="_blank" href="http://video.sina.com.cn">新浪播客</a>、<a target="_blank" href="http://www.youku.com">优酷网</a>、<a target="_blank" href="http://www.tudou.com">土豆网</a>、<a target="_blank" href="http://www.ku6.com/">酷6网</a>、<a target="_blank" href="http://www.56.com/">56网</a>等网站</p></div>',
			'<div class="fn ptb5 plr10"><input type=text id="sina-movie-val" class="textbox w213 vm" value="http://"/><a href="javascript:void(0)" class="btn-all w60 ml10 btn-movie">确认</a></div>'].join('');
		$('body').append('<div id="Wlayer"><div class="Wlayer-icon"></div><div class="Wlayer-con">'+sina+'</div></div>');
		$('#Wlayer').css({top: WlayerTop, left: WlayerLeft});
	});
	
	//话题上传
	$('.sina-qing').unbind().bind("click",function(event){
		event.stopPropagation();
		$('#emotions').hide();
		$('#Wlayer').remove();
		var WlayerTop = $(this).offset().top+35;
		var WlayerLeft = $(this).offset().left -5;
		var sina=[
			'<div class="title">添加话题</div>',
			'<div class="fn ptb5 plr10"><input type=text id="sina-qing-val" class="textbox w213 vm" value="#话题#"/><a href="javascript:void(0)" class="btn-all w60 ml10 btn-qing">确认</a></div>'].join('');
		$('body').append("<div id='Wlayer'><div class='Wlayer-icon'></div><div class='Wlayer-con'>"+sina+"</div></div>");
		$('#Wlayer').css({top: WlayerTop, left: WlayerLeft});
	});
	
	
	$('#Wlayer').unbind().bind("click",function(event){
		event.stopPropagation();
	});
	
	//表情
	$('.sina-face').SinaEmotion($('.sinaSend-input'));
	var app_id = '1362404091';
	$.ajax( {
		dataType : 'jsonp',
		url : 'https://api.weibo.com/2/emotions.json?source=' + app_id,
		success : function(response) {
			var data = response.data;
			for ( var i in data) {
				if (data[i].category == '') {
					data[i].category = '默认';
				}
				if (emotions[data[i].category] == undefined) {
					emotions[data[i].category] = new Array();
					categorys.push(data[i].category);
				}
				emotions[data[i].category].push( {
					name : data[i].phrase,
					icon : data[i].icon
				});
				uSinaEmotionsHt.put(data[i].phrase, data[i].icon);				
				sinaHistory();
			}
		}
	});
	
	$('#wrapper').click(function(){
		$('#emotions').hide();
		$('#Wlayer').hide();
	});
	
});
(function($){
	$.fn.SinaEmotion = function(target){
		var cat_current;
		var cat_page;
		$(this).click(function(event){
			event.stopPropagation();
			$('#Wlayer').remove();
			var eTop = $(this).offset().top + 35;
			var eLeft = $(this).offset().left -5;
			
			if($('#emotions .container a')[0]){
				$('#emotions').css({top: eTop, left: eLeft});
				$('#emotions').toggle();
				return;
			}
			$('#emotions').remove();
			$('body').append('<div id="emotions"></div>');
			$('#emotions').css({top: eTop, left: eLeft});
			$('#emotions').html('<div>正在加载，请稍候...</div>');
			$('#emotions').click(function(event){
				event.stopPropagation();
			});
			
			$('#emotions').html('<div class="emotions-icon"></div><div class="emotions-con"><div style="float:right;"><a href="javascript:void(0);" id="prev"><span><i></i></span></a><a href="javascript:void(0);" id="next"><span><i></i></span></a></div><div class="categorys"></div><div class="container"></div><div class="page"></div></div>');
			$('#emotions #prev').click(function(){
				showCategorys(cat_page - 1);
			});
			$('#emotions #next').click(function(){
				showCategorys(cat_page + 1);
			});
			showCategorys();
			showEmotions();
			
		});
		$.fn.insertText = function(text){
			this.each(function() {
				if(this.tagName !== 'INPUT' && this.tagName !== 'TEXTAREA') {return;}
				if (document.selection) {
					this.focus();
					var cr = document.selection.createRange();
					cr.text = text;
					cr.collapse();
					cr.select();
				}else if (this.selectionStart || this.selectionStart == '0') {
					var 
					start = this.selectionStart,
					end = this.selectionEnd;
					this.value = this.value.substring(0, start)+ text+ this.value.substring(end, this.value.length);
					this.selectionStart = this.selectionEnd = start+text.length;
					check_lenght_weibo(140,'sinaSend-num','.sinaSend-input');
				}else {
					this.value += text;
					check_lenght_weibo(140,'sinaSend-num','.sinaSend-input');
				}
			});        
			return this;
		}
		function showCategorys(){
			var page = arguments[0]?arguments[0]:0;
			if(page < 0 || page >= categorys.length / 5){
				return;
			}
			$('#emotions .categorys').html('');
			cat_page = page;
			for(var i = page * 5; i < (page + 1) * 5 && i < categorys.length; ++i){
				$('#emotions .categorys').append($('<a href="javascript:void(0);">' + categorys[i] + '</a>'));
			}
			$('#emotions .categorys a').click(function(){
				showEmotions($(this).text());
			});
			$('#emotions .categorys a').each(function(){
				if($(this).text() == cat_current){
					$(this).addClass('current');
				}
			});
		}
		function showEmotions(){
			var category = arguments[0]?arguments[0]:'默认';
			var page = arguments[1]?arguments[1] - 1:0;
			$('#emotions .container').html('');
			$('#emotions .page').html('');
			cat_current = category;
			for(var i = page * 72; i < (page + 1) * 72 && i < emotions[category].length; ++i){
				$('#emotions .container').append($('<a href="javascript:void(0);" title="' + emotions[category][i].name + '"><img src="' + emotions[category][i].icon + '" alt="' + emotions[category][i].name + '" width="22" height="22" /></a>'));
			}
			$('#emotions .container a').click(function(){
				target.insertText($(this).attr('title'));
				$('#emotions').remove();
			});
			for(var i = 1; i < emotions[category].length / 72 + 1; ++i){
				$('#emotions .page').append($('<a href="javascript:void(0);"' + (i == page + 1?' class="current"':'') + '>' + i + '</a>'));
			}
			$('#emotions .page a').click(function(){
				showEmotions(category, $(this).text());
			});
			$('#emotions .categorys a.current').removeClass('current');
			$('#emotions .categorys a').each(function(){
				if($(this).text() == category){
					$(this).addClass('current');
				}
			});
		}
	}
})(jQuery);

function Hashtable() {
    this._hash = new Object();
    this.put = function(key, value) {
        if (typeof (key) != "undefined") {
            if (this.containsKey(key) == false) {
                this._hash[key] = typeof (value) == "undefined" ? null : value;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    this.remove = function(key) { delete this._hash[key]; }
    this.size = function() { var i = 0; for (var k in this._hash) { i++; } return i; }
    this.get = function(key) { return this._hash[key]; }
    this.containsKey = function(key) { return typeof (this._hash[key]) != "undefined"; }
    this.clear = function() { for (var k in this._hash) { delete this._hash[k]; } }
}


//替换
function AnalyticEmotion(s) {
	if(typeof (s) != "undefined") {
		var sArr = s.match(/\[.*?\]/g);
		if(!sArr){
			return "1";
		}else{
			for(var i = 0; i < sArr.length; i++){
				if(uSinaEmotionsHt.containsKey(sArr[i])) {
					var reStr = "<img src=\"" + uSinaEmotionsHt.get(sArr[i]) + "\" height=\"22\" width=\"22\" />";
					s = s.replace(sArr[i], reStr);
				}
			}
			return s;
		}
	}
}

	
//文字转表情
function sinaHistory(){
	$(".sinaHistory-con").each(function(){
		var sinaHistory = $(this).html();
		var sinaHistory2 = AnalyticEmotion(sinaHistory);
		if(sinaHistory2==1){
			$(this).html(sinaHistory);
		}else{
			$(this).html(sinaHistory2);
		}
	});
}

//校验字数
function check_lenght_weibo(total,id,obj){
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
		$(".btn-updata").removeClass("disabled");
		$(".btn-savedata").removeClass("disabled");
		return "1";
    }else{
        $("#"+id).attr("style","color:red;").html("已经超出<span style='color:red'>"+(text-total)+"</span>个字");
		$("#"+id).show();
		$(".btn-updata").addClass("disabled");
		$(".btn-savedata").addClass("disabled");
		return "2";
    }
}