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
	$(".flash-img,.swiper-slide").height(scale*200);
});
$(window).resize(function(e) {
	var windowwidth=$(window).width();
	if(windowwidth>640){windowwidth=640;}
	var scale=windowwidth/320;
	$(".flash-img,.swiper-slide").height(scale*200);
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
function tonumber(num){
	if(num == undefined ){
		num = 0;
		return num;
	}else{
		return Number(parseFloat(num.toString().replace(/[^0-9,.]/g,"").replace(/\,/g,"")).toFixed(4));
	}
}
function toformat(num){
	integer = num.toFixed(2);
	return integer
	/*
	var newnum = num.toString().split(".")[0];
	var integer = "";
	var length = newnum.length;
	var maxi = Math.floor(length/3);
	var decimal = parseFloat("0."+num.toString().split(".")[1]).toFixed(2).toString().split(".")[1];
	if(newnum.length>=3){
		for(var i=0;i<maxi;i++){
			if(newnum.length>3){
			var s = ','+newnum.slice(length-3,length);
				newnum = newnum.substr(0,length-3);
				integer += s; 
				length = newnum.length;
			}
		};
	}
	integer = newnum + integer + "." + decimal; 
	integer = integer.toString();
	return integer
	*/
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


function helpOrder(){
	var html = "<p style='line-height:30px; color:#333; font-size:14px;'>什么是订购?</p><p style='line-height:20px;text-align:left;'>1、订购，就是像订杂志一样的购买商品。您可以指定配送周期和配送地址，选择好订购周期并完成在线支付，商家会按照您的设定向您配送商品；</p><p style='line-height:20px;text-align:left;padding:5px 0 10px 0;'>2、配送地址如需变更，可进入相应的订购订单进行修改；</p><p><a href='javascript:void(0)' class='btn-go' onclick='msgPopclose()'>返回</a></p>";
	msgPop(html);
}

//加法  
function FloatAdd(arg1,arg2){  
   var r1,r2,m;  
   try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}  
   try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}  
   m=Math.pow(10,Math.max(r1,r2));  
   return (arg1*m+arg2*m)/m;  
}  
  
 //减法  
function FloatSub(arg1,arg2){  
  var r1,r2,m,n;  
  try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}  
  try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}  
  m=Math.pow(10,Math.max(r1,r2));  
  //动态控制精度长度  
  n=(r1>=r2)?r1:r2;  
  return ((arg1*m-arg2*m)/m).toFixed(n);  
}  
   
 //乘法  
function FloatMul(arg1,arg2)   {   
  var m=0,s1=arg1.toString(),s2=arg2.toString();   
  try{m+=s1.split(".")[1].length}catch(e){}   
  try{m+=s2.split(".")[1].length}catch(e){}   
  return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);   
}   
  
  
//除法  
function FloatDiv(arg1,arg2){   
	var t1=0,t2=0,r1,r2;   
	try{t1=arg1.toString().split(".")[1].length}catch(e){}   
	try{t2=arg2.toString().split(".")[1].length}catch(e){}   
	with(Math){   
		r1=Number(arg1.toString().replace(".",""));

		r2=Number(arg2.toString().replace(".",""));   
		return (r1/r2)*pow(10,t2-t1);   
	}   
}  