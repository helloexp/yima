$(document).ready(function(e){
	/*
	if(parseInt($(".userbar-name").height())>30){
		$(".userbar-money").css("margin-top",0);
		$(".userbar-id").css("margin-top",0);	
	}
	*/
	/*
	//二维码
	$(".msgbar-side dd").click(function(){
		if($(this).height()>70){
			$(this).find("img").animate({top:-120},500,function(){$(".msgbar-side dd").animate({height:64},100)});
		}else{
			$(this).animate({height:116},100,function(){$(this).find("img").animate({top:0},500)});
		}
	});
	*/

	//获取是否有新消息
	var onlinecontant=$("#onlinecontant").text() ? true : false;
	if($("#onlinecontant").text()=="0"){onlinecontant=false};
    if(onlinecontant){
		$(".msgbarTitle span").show();
		$(".msgbarTitle span").html(""+$("#onlinecontant").text()+"");
	}else{
		$(".msgbarTitle span").hide();
	};

	/*
	$(".icon-vip").hoverDelay(function(){ 
		$(".userbar-name-power").show();
		$(".userbar-name-power").animate({left:33,opacity:1},300);
	},function(){ 
		$(".userbar-name-power").animate({left:43,opacity:0},300,function(){$(".userbar-name-power").hide();});
	});
	$(".userbar-name-power").click(function(){ 
		$(this).animate({left:43,opacity:0},300,function(){$(this).hide();});
	})
	setTimeout("$('.userbar-name-power').click();",3000);
	*/
	/*
	$(".close-wcTrain").on("click",function(){
		art.dialog({id:'wcTrain'}).close()
	});
	*/
	/*
	$(".IndVoice .list a").on("click",function(){
		var t = $(this);
		var url = t.attr("data-url");
		var title = t.text();
		art.dialog.open(url,{id:'syslistview',title:title,lock:true,width:810,height:'auto'});
	});
	*/
	var IndVoice = $(".IndVoice").length>0?$(".IndVoice"):$(".ind-voice");
	var voice = IndVoice.find(".list li").length;
	var voiceul = IndVoice.find(".list ul");
	var voicet = 1;
	var g = false;
	var set = setInterval(function(){
				if(g){return false;}
				g = true ;
				if(voicet > voice-1){
					var li = voiceul.find("li:eq(0)");
					voiceul.append("<li class='loop'>"+li.html()+"</li>");
					voiceul.animate({marginTop:-40*voicet},500,function(){
						voiceul.find(".loop").remove();
						voiceul.css({marginTop:0});
						g = false ;
					});
					voicet = 0 ;
				}else{
					voiceul.animate({marginTop:-40*voicet},500,function(){
						g = false ;
						voicet++
					});
				}
			},5000)
	set;
	IndVoice.find(".opr .next").on("click",function(){
		if(g){return false;}
		g = true ;
		voiceul.stop();
		if(voicet >= voice){
			var li = voiceul.find("li:eq(0)");
			voiceul.append("<li class='loop'>"+li.html()+"</li>");
			voiceul.animate({marginTop:-40*voicet},500,function(){
				voiceul.find(".loop").remove();
				voiceul.css({marginTop:0});
				voicet = 1 ;
				g = false ;
			});
		}else{
			voiceul.animate({marginTop:-40*voicet},500,function(){
				voicet++ ;
				g = false ;
			});
		}
	});
	IndVoice.find(".opr .pre").on("click",function(){
		if(g){return false;}
		g = true ;
		voiceul.stop();
		if(voicet<=1){
			var li = voiceul.find("li:last");
			voiceul.css({marginTop:-40});
			voiceul.find("li:eq(0)").before("<li class='loop'>"+li.html()+"</li>");
			voiceul.animate({marginTop:0},500,function(){
				voiceul.find(".loop").remove();
				voiceul.css({marginTop:-40*voice+40});
				voicet=voice ;
				g = false ;
			});
		}else{
			voiceul.animate({marginTop:-40*voicet+80},500,function(){
				voicet-- ;
				g = false ;
			});
		}
	});
   /*旺币是什么*/
   $("#js_spm_wb").on('click',function(){
	   var html=template("wangbi",{});
			art.dialog({
				title: '旺币是什么',
				content: html,
				width:800,
				ok:function(){

				},
				okVal:"我知道了"
			});


	});
   /*关于账户余额*/
   $("#js_spm_ye").on('click',function(){
	   var html=template("yue",{});
			art.dialog({
				title: '关于账户余额',
				content: html,
				width:800,
				ok:function(){

				},
				okVal:"我知道了"
			});


	});


});

//信息弹窗
function artTip(url){
	var lenght=parseInt(url.length);
    var urlInfo;
	for(var i=0; i<lenght; i++){
        urlInfo = typeof(url[i]) == 'string' ? {
            url:url[i]
        }:url[i];
		art.dialog.open(urlInfo['url'],{
				id:i+"tip",
				title:false,
				lock: true,
				fixed:false,
				background: '#000', // 背景色
				opacity: 0.5,	// 透明度
				width:urlInfo['width']||620,
				height:urlInfo['height']||600,
				auiclose:false
		});
	}
}

//白云彩云
function memApply(type){
	if($(".onlinecontant").length<1){
		var showother="if($(this).val()==6){$('.onlinecontant-other').show()}else{$('.onlinecontant-other').hide()}"
		if(type==1){typename="申请白云运营商"};
		if(type==2){typename="申请彩云运营商"};
		if(type==3){typename="申请开通旺财直通车"};
		if(type==4){typename="申请代运营"};
		var html=[
			'<div class="onlinecontant_bg"></div>',
				'<div class="onlinecontant">',
			'<form id="feedback_form" method="post" action="index.php?g=Home&m=AgentMerchants&a=apply">',
			'<input type="hidden" name="apply_type" value="'+type+'"  />',
					'<div class="onlinecontant-title fn">'+typename+'<i onclick="closeonlinecontant()"></i></div>',
					'<div class="onlinecontant-con">',
						'<div class="fn">',
						'<p class="l">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;*姓名:</p><div><input type="text"  name="user_name" class="onlinecontant-input" maxlength="6" value="" /></div>',
						'</div>',
						'<div class="fn mt20">',
						'<p class="l">*联系电话:</p><div><input type="text"  name="mobile" class="onlinecontant-input" maxlength="11" value="" /></div>',
						'</div>',
						'<div class="fn mt20">',
						'<p class="l">*渠道类型:</p><div>',
							'<select name="channel_type" class="onlinecontant-selectbox" onchange="'+showother+'">',
							'<option value="1">平面媒体</option>',
							'<option value="2">电视媒体</option>',
							'<option value="3">广播媒体</option>',
							'<option value="4">户外媒体</option>',
							'<option value="5">网络媒体</option>',
							'<option value="6">其他</option>',
							'</select>',
							'<input type="text" class="onlinecontant-other dn" value="" name="channel_other" maxlength="12"/>',
							'</div>',
						'</div>',
						'<div class="fn mt20">',
						'<p class="l">*渠道描述:</p><div><textarea name="channel_text" class="onlinecontant-textarea" maxlength="100"></textarea></div>',
						'</div>',
						'<a href="javascript:void(0);" class="btn-all w150 mt10" onclick="apply_sub()">'+typename+'</a>',
					'</div>',
			'</form>',
				'</div>'].join('');
		$("body").append(html);
		$(".onlinecontant,.onlinecontant_bg").fadeIn();
		$(".onlinecontant_bg").show().css("opacity","0.3");
	}else{
		$(".onlinecontant,.onlinecontant_bg").fadeIn();
	};
}

//视频
function vedioView(){
	var html =
			['<div class="userhelpBg dn">',
				'<div class="userhelpBg2">',
					'<div class="userhelpflash">',
						'<div id="cpys">',
							'<object type="application/x-shockwave-flash" data="./Home/Public/Image/vcastr3.swf" width="980" height="588" id="vcastr3">',
								'<param name="movie" value="./Home/Public/Image/vcastr3.swf"/>',
								'<param name="allowFullScreen" value="true" />',
								'<param name="bgColor" value="#000000" />',
								'<param name="FlashVars" value="xml=./Home/Public/Image/vcastr.xml" />',
								'<param name="wmode" value="transparent">',
								'<embed wmode="transparent">',
							'</object>',
						'</div>',
					'</div>',
				'</div>',
        		'<div class="userhelpheader"><i class="userhelpclose" onclick="userhelpclose()"></i></div>',
			'</div>'].join('');
	$("body").append(html);
	$(".userhelpBg").fadeIn();
}
function userhelpclose(){
	$(".userhelpBg").fadeOut();
	$(".userhelpBg").detach();
}

