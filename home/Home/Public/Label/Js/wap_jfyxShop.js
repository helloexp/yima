$(document).ready(function(e) {
	loginForm();
	$('body,html').animate({scrollTop:0},17);
	//back
	$(".goback").click(function(){
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

function link_to(url,fun){
	if($('#loadingBox').length==0){$("body").append('<section class="fullHtml loadingBox" id="loadingBox"><i></i><span>加载中</span></section>');}
    setTimeout(function(){
        $('#loadingBox').show();
    },1);
    setTimeout(function(){
    if(typeof url == 'string'){
    location.href=url;
    }
    if(typeof url == 'function'){
    url();
    }
    },500);
    setTimeout(function(){$('#loadingBox').hide();},6000);
    if(typeof fun == 'string'){
    var jscode = new Function('return function(){'+fun+'}')();
        jscode();
    }else if(typeof fun == 'function'){
        fun();
    }
}

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
	if($(".fixTrolley").length==0){
		$("body").append('<div class="fixTrolley"><span>'+num+'</span></div>')
	}
	$(".fixTrolley").addClass("add");
	setTimeout(function(){
		$(".fixTrolley span").text(num);
		$(".fixTrolley").removeClass("add");
	},2000)
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
	$(document.body).css({
   "overflow-x":"auto",
   "overflow-y":"auto"
 });
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