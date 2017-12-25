$(document).ready(function(e){
	welcomeIn();
	welcomeshowbg();
}); 
$(window).resize(function(e) {
	welcomeshowbg();
}); 

function welcomeIn(){
	var welcomeHtml =
		['<div class="wel-bg"></div>',
		'<div class="welCon">',
		'<div class="wel-con wel-con-1"><div class="wel-con-btn wel-con-btn1"><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-2"><div class="wel-con-btn wel-con-btn2"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-3"><div class="wel-con-btn wel-con-btn3"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-4"><div class="wel-con-btn wel-con-btn4"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-5"><div class="wel-con-btn wel-con-btn5"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-6"><div class="wel-con-btn wel-con-btn6"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-7"><div class="wel-con-btn wel-con-btn7"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-8"><div class="wel-con-btn wel-con-btn8"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-9"><div class="wel-con-btn wel-con-btn9"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-10"><div class="wel-con-btn wel-con-btn10"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-11"><div class="wel-con-btn wel-con-btn11"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-con wel-con-12"><div class="wel-con-btn wel-con-btn12"><a href="javascript:void(0)" class="wel-pre"></a><a href="javascript:void(0)" class="wel-next"></a></div></div>',
		'<div class="wel-close"><a href="javascript:void(0)" class="wel-btn-close"></a></div>',
		'</div>'].join('');
		$("body").append(welcomeHtml);
		welcomeshow();
}

function welcomeshow(){
	$(".wel-con-1").show();
	$(".wel-next").click(function(){
		var next=$(this).closest(".wel-con").index()+2;
		$(".wel-con").hide();
		$(".wel-con-"+next).fadeIn();
		if(next=="13"){
			$(".welCon").fadeOut();
			$(".wel-bg").fadeOut();
		}
	});
	$(".wel-pre").click(function(){
		var pre=$(this).closest(".wel-con").index();
		$(".wel-con").hide();
		$(".wel-con-"+pre).fadeIn();
	});
	$(".wel-btn-close").click(function(){
		$(".welCon").fadeOut();
		$(".wel-bg").fadeOut();
	});
}

function welcomeshowbg(){
	$(".wel-bg").css("height",$("#wrapper").height());
}
