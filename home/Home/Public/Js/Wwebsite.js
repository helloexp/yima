
$(document).ready(function(e){
	$(".MicroWeb-style-ul").width($(".set_main").length*245)
	$(".set_main").bind('setMain',function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");
		var showpreview=$(this).attr("data-rel");
			$("input[name='page_style']").val(showpreview);
			$("input[name='tpl_id']").val(showpreview);
	}).click(function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");
		var showpreview=$(this).attr("data-rel");
			$("input[name='page_style']").val(showpreview);
			$("input[name='tpl_id']").val(showpreview);
		$('#tpl_sub_button').click();
	});
	
	//初始化
	
	var _name=$("input[name='name']").val();
	var _logo=$("#hidden_img_tag").attr("value");
	var _pageStyle=$(".MicroWeb-style .set_main_hover").index();
	var _viewStyle=$("input[name='page_style']").val();
	var _modulelength=$(".add_module_item").length;
	var _bglength=$(".add_bg_item").length;
	var _bgcolor=$("input[name='page_color']").val();

	if(_name==""){
		$(".Preview-top-title").text("商户名称");
	}else{
		$(".Preview-top-title").text(_name);
	}
	if(_logo==""){
		$(".Preview-logo-con img").attr("src","./Home/Public/Label/Image/defaultlogo-sOne.png");
	}else{
		$(".Preview-logo-con img").attr("src",_logo);
	}
	if(_viewStyle=="1"){
		$('.set_main:eq(2)').trigger('setMain');
	}else if(_viewStyle=="2"){
		$('.set_main:eq(3)').trigger('setMain');
		$(".add_module_item_preview img").hide();
		$(".add_module_item_preview").css("background","#ffffff");
	}else if(_viewStyle=="8"){
		$('.set_main:eq(0)').trigger('setMain');
		$(".add_module_item_preview img").hide();
		$(".add_module_item_preview").css("background","#ffffff");
	}else if(_viewStyle=="3"){
		$('.set_main:eq(4)').trigger('setMain');
	}
	$(".MicroWeb-style-ul").animate({marginLeft:-(_pageStyle-1)*241});
	if(_bglength){
		$(".Preview-first-view ul").html("")
		$(".add_bg_item").each(function(){
			var _src=$(".add_bg_item_preview img").attr("src");
			$(".Preview-first-view ul").append("<li><img src='"+_src+"'/></li>");			
		});
	}
	if(_modulelength,_viewStyle){
		if(_viewStyle=="7"){
			_viewStyle=4;
		}
		$(".iphonePreview-1").removeClass("iphonePreview-1").addClass("iphonePreview-"+_viewStyle);
		if(_viewStyle!=4 && _viewStyle!=5 && _viewStyle!=10 && _viewStyle!=11 && _viewStyle!=12 && _viewStyle!=13){
			if(_modulelength<="2"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","60px").css("padding-top","60px");
			}else if(_modulelength<="4"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","20px").css("padding-top","20px");
			}
		}else if(_viewStyle==4){
			if(_modulelength<="3"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","60px").css("padding-top","60px");
			}else if(_modulelength<="6"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","20px").css("padding-top","20px");
			}
		}else if(_viewStyle==5){
			if(_modulelength<="4"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","60px").css("padding-top","60px");
			}else if(_modulelength<="8"){
				$(".iphonePreview-"+_viewStyle).find(" .Preview-mainCon-contenter-bg").css("padding-bottom","30px").css("padding-top","30px");
			}
		}
		if($("input[name='phone']").val()!=""){
                    if(_viewStyle!=15){
                        $(".Preview-subnav").show();
                    }
			
		}
		if($(".sns_type").attr("checked")){
			if(_viewStyle!=15){
                        $(".Preview-subnav").show();
                    }
		}
		$(".Preview-nav ul").html("");
		$(".add_module_item").each(function(){
			var _index=$(this).attr("data-id");
			var _src=$(this).find(".add_module_item_preview img").attr("src");
			var _srcarray=_src.replace(/[&\\\();,\/]/g," ").split(" ");
			var _backgroundsize="background-size:100% auto;-webkit-background-size:100% auto;";
			for (var jj=0;jj<=_srcarray.length;jj++){
				if(_srcarray[jj]=="iconVal"){
					_src="./Home/Public/Image/wapimg/iconVal/"+_srcarray[_srcarray.length-1];
					var _backgroundsize="background-size:inherit;-webkit-background-size:inherit;";
				}
			}
			var _p=$(this).find(".add_module_item_title").text();
			_bgcolor = $(this).find(".add_module_item_preview").css('backgroundColor');
			if(_viewStyle==11 || _viewStyle==13){
				$(".Preview-nav ul").append("<li id='Previe"+_index+"'><div class='nav-img nav-color' style='background-image: url("+_src+");"+_backgroundsize+"'></div><div class='nav-con'><p>"+_p+"</p></div></li>");
			}else{
				$(".Preview-nav ul").append("<li id='Previe"+_index+"'><div class='nav-img nav-color' style='background-color:"+_bgcolor+";background-image: url("+_src+");"+_backgroundsize+"'></div><div class='nav-con'><p>"+_p+"</p></div></li>");
			}
		});
		
		//全景图模板
		if(_viewStyle==10){
			$(".Preview-first-view ul").html("<li><img src="+$(".set_bg_img.set_main_hover").attr("src")+"></li>");
			$(".Preview-nav li:eq(4)").addClass("special");
			$(".Preview-nav li:eq(5)").addClass("special");
			$(".Preview-nav li:eq(10)").addClass("special");
			$(".Preview-nav li:eq(11)").addClass("special");
		}
		
		setTimeout("$('#iphonePreview-loading').fadeOut(1000)",500);
	}
	MicroWebslide();
	
	
	//全景模板专用
	$(".set_bg_update").click(function(){
		var url = $(this).attr("src");
		var val_url = $(this).attr("data-src");
		var rel = $(this).attr("data-rel");
		var id = $(this).attr("data-id");
		$("#picsrc").val(url);
		$("#resp_bg_img").val(val_url);
		$("#bg_style").val(id);
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$(this).addClass("set_bg_img_hover");
		$('#bg_sub_button').click();
	})
});
$(window).scroll(function(){
	var serviceTop = $(window).scrollTop();
	var winwidth=$(window).width();
		right=(winwidth-1100)/2;
	if (serviceTop>300){
		$(".activityread_iphone").css("position","fixed");
		$(".activityread_iphone").css("top","-20px");
		$(".activityread_iphone").css("right",right);
	}else{
		$(".activityread_iphone").css("position","static");
	}
});


function MicroWebslide(){
	window.hoverpic=$(".MicroWeb-style .set_main_hover").index();
	if(!window.hoverpic){window.hoverpic=0};
	var g={running:false};
	$(".MicroWeb-style .pre").click(function(){
		if(g.running)return false;
		g.running=true;
		if(window.hoverpic>0){
			window.hoverpic=window.hoverpic-1;
			var marginLeft=-window.hoverpic*241;
			$(this).closest(".MicroWeb-style").find(".MicroWeb-style-ul").animate({marginLeft:marginLeft},400,'easeInQuint',function(){g.running=false;});
		}else{
			window.hoverpic=$(".MicroWeb-style-ul img").length-2;
			var marginLeft=-window.hoverpic*241;
			$(this).closest(".MicroWeb-style").find(".MicroWeb-style-ul").animate({marginLeft:marginLeft},400,'easeInQuint',function(){g.running=false;});
		}
	});
	$(".MicroWeb-style .next").click(function(){
		if(g.running)return false;
		g.running=true;
		if(window.hoverpic<$(".MicroWeb-style-ul img").length-2){
			window.hoverpic=window.hoverpic+1;
			var marginLeft=-window.hoverpic*241;
			$(this).closest(".MicroWeb-style").find(".MicroWeb-style-ul").animate({marginLeft:marginLeft},400,'easeInQuint',function(){g.running=false;});
		}else{
			window.hoverpic=0;
			var marginLeft=-window.hoverpic*241;
			$(this).closest(".MicroWeb-style").find(".MicroWeb-style-ul").animate({marginLeft:marginLeft},400,'easeInQuint',function(){g.running=false;});
		}
	});
}
