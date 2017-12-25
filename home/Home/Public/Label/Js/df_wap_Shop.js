$(document).ready(function(e) {
	loginForm();
	$('body,html').animate({scrollTop:0},17);
	//back
	$(".back").click(function(){
		history.go(-1);
	})
	
	var windowwidth=$(window).width();
	if(windowwidth>640){windowwidth=640;}
	var scale=windowwidth/320;
	
	$(".flash-img,.swiper-slide").height(scale*160);
});
$(window).resize(function(e) {
	var windowwidth=$(window).width();
	if(windowwidth>640){windowwidth=640;}
	var scale=windowwidth/320;
	
	$(".flash-img,.swiper-slide").height(scale*160);
});
function loginForm(){
	$('body,html').animate({scrollTop:0},17);
	 $(".loginForm").height($(".loginForm").height());
}
//分类
function choose(){
	event.stopPropagation();
	if($(".choose").hasClass("slideDown")){
		$(".choose").slideUp(200);
		$(".choose").removeClass("slideDown");
	}else{
		$(".choose").slideDown(200);
		$(".choose").addClass("slideDown");
	}
	$("#wrapper").click(function(){
		$(".choose").slideUp(200);
	})
}

//瀑布流
function masonry(){
	var $container = $('#masonry');
    $container.imagesLoaded(function(){
		$container.masonry({
        	itemSelector : '.box'
		});
    });
    $container.infinitescroll({
        navSelector  : '#page-nav',    // selector for the paged navigation 
        nextSelector : '#page-nav a',  // selector for the NEXT link (to page 2)
        itemSelector : '.box',     // selector for all items you'll retrieve
        loading: {
            finishedMsg: '没有更多商品了',
            img: './Home/Public/Label/Image/Item/loading.gif'
          }
        },
        // trigger Masonry as a callback
        function( newElements ) {
			// hide new items while they are loading
			var $newElems = $( newElements ).css({ opacity: 0 });
			// ensure that images load before adding to masonry layout
			$newElems.imagesLoaded(function(){
				// show elems now they're ready
				$newElems.animate({ opacity: 1 });
				$container.masonry( 'appended', $newElems, true ); 
			});
		}
	);
	var t = parseInt($("#masonry").width())%2 ==0 ? false : true ;
	if(t){
		$("#masonry").width(parseInt($("#masonry").width())+1);
	}else{
		$("#masonry").attr("style","");
	}
}


//选择相关产品
function otherpro(){
	$(".proOther-list li").click(function(){
		var checked = $(this).find("input[type='checkbox']").attr("checked");
		if(!checked){
			$(this).find("input[type='checkbox']").attr("checked",true);
			$(this).find(".img").addClass("checked");
		}else{
			$(this).find("input[type='checkbox']").attr("checked",false);
			$(this).find(".img").removeClass("checked");
		}
	})
}

//加入购物车
/*function addTrolley(){
	$(".btn-addTrolley").click(function(){
		var _i = $(this).prev("i");
		var top = $(this).closest(".proOpr").offset().top-10;
		var nownum = parseInt($(".trolley").find("span").text());
		if(!nownum){
			$(".trolley").append("<span class='dn'></span>");
			$(".trolley").find("span").fadeIn(500);
			nownum=0;
		}
		var othernum = $(".proOther-list").find("input[type='checkbox']:checked").length;
		if(!othernum){othernum=0;}
		_i.attr("style","");
		_i.fadeIn(300);
		_i.animate({top:-top,right:0,opacity:0},500,function(){
			$(".trolley").find("span").text(nownum+othernum+1);
		});
	})
}*/

//加入购物车
function addTrolley(num){
	
		var _i = $(".cart_red i");
		var top = $(".cart_red").offset().top-10;
		var nownum = parseInt($(".trolley").find("span").text());
		if(!nownum){
			$(".trolley").append("<span class='dn'></span>");
			$("#nav .icon-navTrolley").append("<span class='dn'></span>");
			$(".trolley").find("span").fadeIn(500);
			$("#nav .icon-navTrolley span").fadeIn(500);
			nownum=0;
		}
		_i.attr("style","");
		_i.fadeIn(300);
		_i.animate({top:-top,right:0,opacity:0},500,function(){
			_i.hide();
			$(".trolley").find("span").text(num);
			$("#nav .icon-navTrolley").find("span").text(num);
		});
	
}

//其他产品适应
function proOtherlist(){
	var _length = $(".proOther-list ul li").length;
	$(".proOther-list ul").width(102*_length);
}

//结算
function orderForm(){
	$(".select-address").change(function(){
		if($(this).val()==0){
			$(this).closest("li").next("li").show();
			$(".input-address").val("");
		}else{
			$(this).closest("li").next("li").hide();
			$(".input-address").val($(this).val());
		}
	})
}

//弹层
function msgPop(msg){
	var html=['<div class="msgPop">',
            '<div class="msgPopCon">',
				'<a href="javascript:void(0)" class="close" onclick="msgPopclose()">+</a>',
				''+msg+'',
            '</div>',
        '</div>'].join('');
	$("body").append(html);
	
}
function msgPopclose(){
	$(".msgPop").remove();
}
//幻灯
function flash(_div,type){
	var _div=$(_div);
	var _divw=_div.width();
	var _Num=_div.find("img").length;
	$(_div).find(".flashImg").width(_divw*_Num+"px");
	$(_div).find(".flashImg li").width(_divw);
	$(_div).find("li").show();
	var _opr="<li class='hover'></li>"
	for(var i=1;i<_Num;i++){
		_opr=_opr+'<li></li>';
	}
	_opr=['<ul class="flashNav">'+_opr+'</ul>'].join('');
	if(_div.find(".flashNav").length==0){_div.append(_opr)};
	
	if(type==1){
		return false;
	}
		
	window.hoverpic=0;
	var g={running:false};
	var auto = setInterval(function(){
		if(g.running)return false;


		g.running=true;
		if(window.hoverpic>=_Num-1){window.hoverpic=0;}else{window.hoverpic++;}
		var listindex=window.hoverpic;
		var height=_div.find("li:eq("+listindex+")").height();
			Hlanimate(listindex,height);
	},6000);
	var stopauto=function(){clearInterval(auto);}
	var starauto=function(){auto;}
		
	var starL, starX, endX, moveX;
	
	$(".flashNav li").live("click",function(){
		if(g.running)return false;
		g.running=true;
		var listindex=$(this).closest(".flashNav li").index();
		var height=_div.find("li:eq("+listindex+")").height();
			window.hoverpic=$(this).index();
			Hlanimate(listindex,height);
	});
	
	var Hlanimate = function(listindex,height){
		_divw=_div.width();
		$(".flashNav li").removeClass("hover");
		$(".flashNav li:eq("+listindex+")").addClass("hover");
		_div.animate({height:height},200)
		_div.find(".flashImg").animate({marginLeft:-listindex*_divw},200,function(){g.running=false;});
	}
	
	_div.unbind("touchstart").bind("touchstart",function(){
		_divw=_div.width();
		if(_divw<=320){
			event.preventDefault();
		}
		stopauto;
		starL = parseInt( _div.find(".flashImg").css("margin-left"));
		starX = event.targetTouches[0].clientX;
		g.running=true;
	});
	_div.unbind("touchmove").bind("touchmove",function(){
		g.running=true;
		var mx = event.targetTouches[0].clientX;
		moveX=mx-starX+starL;
		_div.find(".flashImg").css({marginLeft:moveX});
	});
	
	_div.unbind("touchend").bind("touchend",function(){
		endX = event.changedTouches[0].clientX;
		endX=endX-starX;
		if(endX>60){
			if(!window.hoverpic==0){window.hoverpic--;}
			var listindex=window.hoverpic;
			var height=_div.find("li:eq("+listindex+")").height();
			Hlanimate(listindex,height);
		}else if(endX<-60){
			if(window.hoverpic<_Num-1){window.hoverpic++;}
			var listindex=window.hoverpic;
			var height=_div.find("li:eq("+listindex+")").height();
			Hlanimate(listindex,height);
		}else{
			var listindex=window.hoverpic;
			var height=_div.find("li:eq("+listindex+")").height();
			Hlanimate(listindex,height);
		};
		starauto;
	});
}
