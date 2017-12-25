$(document).ready(function(e){
	Indonlinecontant();
	$(".gologin").click(function(){ 
		$(".loginBg").fadeIn(); 
	}); 
	$(".loginFormclose").click(function(){ 
		$(".loginBg").fadeOut(); 
	});
	var intanimate=setInterval(function(){
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",80);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #30ff00')",160);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",240);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #30ff00')",320);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",400);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #30ff00')",480);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",560);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #30ff00')",640);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",720);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #30ff00')",800);
		setTimeout("$('.Indtitle .detl').css('border','solid 1px #ff9c00')",880);
	},3000);

	//合作伙伴动画
    $("#first_dot").click(function(){
		if($("#part").is(":animated")){
			return false;
		}else {
			$(".partnerNav i").removeClass("hover");
			$(".partnerNav i").eq(0).addClass("hover");
			$("#part").animate({
			 left:0
			 })
		}
     })
    $("#second_dot").click(function(){
		if($("#part").is(":animated")){
			return false;
		}else{
			$(".partnerNav i").removeClass("hover");
			$(".partnerNav i").eq(1).addClass("hover");
			$("#part").animate({
				left:"-1100px"
			})
		}
    })
    $("#third_dot").click(function(){
		if($("#part").is(":animated")){
			return false;
		}else{
			$(".partnerNav i").removeClass("hover");
			$(".partnerNav i").eq(2).addClass("hover");
			$("#part").animate({
				left:"-2200px"
			})
		}
    })

    //翼码简介页效果
    $("#slices .slice").hover(function(){
    	var s_index = $(this).index();
    	$(this).css("background","#128ed7");
    	$(this).find("i").addClass('bg'+s_index+'_hov');
    	$(this).find("span").css("color","#fff");
    },function(){
    	var s_index = $(this).index();
    	$(this).css("background","#f8f8f8");
    	$(this).find("i").removeClass('bg'+s_index+'_hov');
    	$(this).find("span").css("color","#717171");
    })

    //代运营商
   $("#Operate_ser li").hover(function(){
   		var arr_tit = new Array("品牌支持","授权支持","营销支持","售后支持","物料支持","技术支持","业务支持","培训支持","定制化服务","营销渠道合作共赢","营销创意合作共赢","更多定制支持");
   		var arr_str = new Array(
   				"翼码公司每年在媒体上投入资源宣传推广旺财品牌，塑造产品的影响力，增加公司和产品曝光度。",
   				"公司在官网、微博、微信等自媒体渠道对代运营商进行公示，并举行授权仪式，为代运营商正名和背书。",
   				"公司遍布全国31个省市和地区的销售团队和运营中心，随时待命，为代运营商提供营销支持和服务。",
   				"远程支持服务，产品售后支撑服务，不定期寻访，不定期组织代运营商学习。",
   				"公司精美画册、产品宣传彩页、其他宣传物料支持。",
   				"以各地区运营专员、400电话、QQ、微信等多种方式，24小时解答代运营商遇到的技术问题。",
   				"定期发布行业产品白皮书，定期培训销售话术，针对营销问题答疑解惑，打造成功案例样板。",
   				"公司定期通过在线视频和线下活动的形式开展O2O理念和实战培训。",
   				"旺财平台提供非标准营销模块定制化开发服务，为个性化营销提供全面平台支持。",
   				"代运营商向旺财平台提供营销推广渠道资源，通过旺财平台实现渠道资源销售，获得收入后平台和代运营商按照约定的分佣比例进行收入分成。",
   				"代运营提供营销创意，旺财平台实现营销创意模块的技术开发，然后通过旺财平台进行营销模块销售，获得收入后平台和代运营商按照约定的分佣比例进行收入分成。",
   				""
   			);
   		var s_index=$(this).index();
   		$("#Operate_ser li").removeClass("hov");
   		$(this).addClass("hov");
   		$("#dlog *").text("");
   		$("#dlog h2").text(arr_tit[s_index]);
   		$("#dlog p").text(arr_str[s_index]);
   })


	//O2O学院
	$(".oto_c i").click(function(){
		var m_open="+ 展开全部大纲";
		var m_close="- 收起大纲";
		var detl=$(".oto_c .detl");
		var detl_h=parseInt($(".oto_c .detl").css("height"));
		if(detl_h>65&&!(detl.is(":animated"))){
			detl.animate({
				height:"65px"
			})
			$(".oto_c i:first").hide();
			$(".oto_c i").text(m_open);
		}
		if (detl_h==65&&!(detl.is(":animated"))) {
			detl.animate({
				height:"1600px"
			})
			$(".oto_c i:first").show();
			$(".oto_c i").text(m_close);
		}
	})


	
	$(".oto_ask .next").click(function(){
		var count=$(".oto_ask li:last").index()+1;
		var pages=Math.ceil(count/5);
		var uleft=parseInt($(".oto_ask ul").css("marginLeft"));
		if($(".oto_ask ul").is(":animated")){
			return false;
		}else if(uleft>(pages-1)*(-1050)){
			var indexNum=Math.floor(parseInt($(".oto_ask ul").css("margin-left"))/1050)+1;
			$(".srolNav a").removeClass("hover");
			$(".srolNav a:eq("+indexNum+")").addClass("hover");
			$(".oto_ask ul").animate({
				marginLeft:"-=1050px"
			});
		}
	})
	$(".oto_ask .pre").click(function(){
		var uleft=parseInt($(".oto_ask ul").css("marginLeft"));
		if($(".oto_ask ul").is(":animated")){
			return false;
		}else if(uleft<0){
			var indexNum=Math.floor(parseInt($(".oto_ask ul").css("margin-left"))/1050)+1;
			$(".srolNav a").removeClass("hover");
			$(".srolNav a:eq("+indexNum+")").addClass("hover");
			$(".oto_ask ul").animate({
				marginLeft:"+=1050px"
			})
		}
	})


});

function Indonlinecontant(){
	var sessionname=$(".gologin").length;
	var html=
		[
		 '<div class="ui-sidebar" id="tbox">',
		 '<a class="ui-sidebar-block app" href="javascript:void(0)"><i></i></a>',
		 '<a class="ui-sidebar-block calltel" href="javascript:void(0)"><i></i></a>',
		 '<a class="ui-sidebar-block callqq" href="http://wpa.b.qq.com/cgi/wpa.php?ln=1&key=XzkzODA2Njc3MF8zNzA4NjdfNDAwODgwNzAwNV8yXw" target="_blank"></a>',
		 '<a class="ui-sidebar-block feedback" href="javascript:void(0)"></a>',
		 '<a class="ui-sidebar-block backtop"   href="javascript:void(0)" id="gotop" style="display: none;"></a>',
		 '</div>'].join('');
			/*['<div class="Indonlinecontant">',
            '<div class="Indonlinecontant-openclose" onclick="onlinecontant()"></div>',
            '</form>',
        	'</div>']*/
	if($("#tbox").length<1){
	$("#wrapper").append(html);
	};
} 
$(function() { 
	$('#gotop').click(function(){ 
		$('body,html').animate({
			scrollTop: 0
		},
		800);//点击回到顶部按钮，缓懂回到顶部,数字越小越快
		return false;  
	})
$(".feedback").click(function(e) {
    onlinecontant();
}); 

$(window).scroll(function(){
t = $(document).scrollTop();

if(t > 50){
	$('#gotop').fadeIn('slow');
}else{
	$('#gotop').fadeOut('slow');
}       
})  
});