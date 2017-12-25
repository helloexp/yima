$(document).ready(function(e){//页面加载时载入
	global_initialise();    //框架
	uicontent();			//标签切换
	wrapinput();			//产品数量改变
	popo();					//弹窗
	canalcheckbox();		//渠道
}); 
$(window).resize(function(e) {//浏览器窗口变化自动载入
	global_initialise();
}); 

function global_initialise(){
	var globalcommon="";
		contentmargin="";
		windowbox=$(window).width();
	if(windowbox<1210){
		globalcommon=1000;
		contentmargin=40;
	}else{
		globalcommon=1150;
		contentmargin=40;
	}
	$("#global-common").css("width",globalcommon);
	$("#global-nav").css("height","");
	$("#global-main").css("height","");
	$("#global-main").css("width","");
	fit(globalcommon,contentmargin);
}

function fit(globalcommon,contentmargin){
	var windowheight=$(window).height();
		leftheight=$("#global-nav").height();
		rightheight=$("#global-main").height();
		rightwidth=globalcommon-$("#global-nav").width();
		
		$("#global-main").css("width",rightwidth);		//右边大小
		$("div .global-content").css("marginLeft",contentmargin);	//右边间距大小
		$("div .global-content").css("width",rightwidth-contentmargin);		//右边内容大小
		$(".package-service").css("width",rightwidth-260-contentmargin);	//package内容大小
		/*框架高度适应*/
		if(leftheight<=windowheight && rightheight<=windowheight){
			$("#global-nav").css("height",windowheight);
			$("#global-main").css("height",windowheight);
		}else{
			if(leftheight<=rightheight){
				$("#global-nav").css("height",rightheight)
			}else{
				$("#global-main").css("height",leftheight)
			}
		}
		/*框架高度适应*/
}

function uicontent(){
		$("div#global-libs p").click(function(){
			$("div#global-libs p").removeClass("hover");
			for(i=0;i<11;i++){
			$("div#global-libs-content #globallibscontent:eq("+i+")").removeClass("hover");
			$("div#global-libs-content #globallibscontent:eq("+i+")").addClass("hide");
			}
			$(this).addClass("hover");
			$("div#global-libs-content #globallibscontent:eq("+$(this).index()+")").addClass("hover");
			global_initialise();
    	});
}

function wrapinput() {        //改变数量
	$(".btn-reduce").click(function(){    //按+号
		var pirceone=parseInt($("#price-one").text());
			pricenum=parseInt($("#buy-num").val());
			priceall="";
			if(pricenum<=1){
				alert("不能为0");
			}else{
				pricenum=pricenum-1;
				priceall=pricenum*pirceone;
				$("#buy-num").val(pricenum);
				$("#price-all").text(priceall+"元");
			}
	});
	$(".btn-add").click(function(){    //按-号
		var pirceone=parseInt($("#price-one").text());
			pricenum=parseInt($("#buy-num").val());
			priceall="";
			pricenum=pricenum+1;
			priceall=pricenum*pirceone;
			$("#buy-num").val(pricenum);
			$("#price-all").text(priceall+"元");
	});
	$("#buy-num").change(function(){    //直接输入框修改
		var pirceone=parseInt($("#price-one").text());
			pricenum=parseInt($("#buy-num").val());
			if(pricenum<1){
				alert("不能为0或负数");
				pricenum=1;
				$("#buy-num").val(pricenum);
				priceall=pricenum*pirceone;
				$("#buy-num").val(pricenum);
				$("#price-all").text(priceall+"元");
			}else{
				priceall=pricenum*pirceone;
				$("#buy-num").val(pricenum);
				$("#price-all").text(priceall+"元");
			}
	});
}

function popo(){
	$("#global_change").height($("body").height());
	$(".ddtj").click(function(){
		popo_display(1);
		$("#global_change_con").load("order_view.html");
	});
	$(".global_change_close").click(function(){
		popo_display(2);
	});
}
function popo_display(block) {
	if(block==1){
		$("#global_change").css("display","block");
		$("#global_change").append("<div id='global_change_con'></div>");
		$("#global_change_con").css("top",$(window).scrollTop()+200);
	}else{
		$("#global_change_con").remove();
		$("#global_change").css("display","none");
	}
}

function canalcheckbox(){
	$(".canal_checkbox_p:eq(0)").css("border-color","#d52116");
	$("#canal-online").css("display","block");
	$(".canal_checkbox_p").click(function(){
		var eq=$(this).index();
			$(".canal_checkbox_p").css("border-color","#b0b0b0");
			$(this).css("border-color","#d52116");
			if(eq==0){
				$("#canal-offline").css("display","none");
				$("#canal-online").css("display","block");
			}else{
				$("#canal-online").css("display","none");
				$("#canal-offline").css("display","block");
			}
	});
}

/* 打开一个对话框*/
function open_dialog(url,title,width,height){
	title=title||false;
	if(art.dialog.list['art_select_dialog']){
		art.dialog.list['art_select_dialog'].close();
	}
	url += '&in_iframe=true';
    var art_win = window.art.dialog.open(url,{
        title:title,
        id:'art_select_dialog',
        width:width||800,
        height:height||500,
        lock:true
    });
}
function close_dialog(){
	if(art.dialog.list['art_select_dialog']){
		art.dialog.list['art_select_dialog'].close();
	}
}

//简易ajax提交
function confirm_ajax_link(obj,str){
	str = str||"你确定要操作吗";
	if(!confirm(str)) return false;
	dialog = art.dialog({title:false,lock:true});
	var href = $(obj).attr("href");
	$.get(href,'',function(d){
	d = new Function("return" + d)();
		if(d.status != '1'){
			alert("操作失败："+d.info);
			dialog.close();
		}
		else{
			window.location.reload();
		}
	});
	return false;
}