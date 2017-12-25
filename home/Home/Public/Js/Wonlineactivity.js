$(document).ready(function(e){
});
function search_btn(){
	$(".c-place").click(function(e){
		e.stopPropagation ?e.stopPropagation() : (e.cancelBubble=true);
		$(".meau1").hide();
		$(this).find(".meau1").toggle("show");
	});
	$(".meau1 a,.meau1 .meau1_topbg").click(function(e){
		e.stopPropagation ?e.stopPropagation() : (e.cancelBubble=true);
	});
	$("#wrapper").click(function(){
		$(".meau1").hide();
	});	
}
function oprchange(id,viewnum){
	var $slide=$("."+id);
	$("."+id+"-operate").css({top:$slide.offset().top});
	//$("."+id+"-con").css("width",length*conwidth);
}
function defaultsolide(id,viewnum){
	var $slide=$("."+id);
	var length=$slide.find("."+id+"-con li").length;
	var conwidth=$slide.find("."+id+"-con li").width()+parseInt($slide.find("."+id+"-con li").css("margin-left"))+parseInt($slide.find("."+id+"-con li").css("margin-right"));
	var $opr="<li class='hover'></li>";
	for(var i=1;i<length;i++){
		$opr=$opr+'<li></li>';
	}
	$opr=['<div class="'+id+'-opr">'+$opr+'</div><div class="'+id+'-operate"><a href="javascript:void(0)" class="pre"></a><a href="javascript:void(0)" class="next"></a></div>'].join('');
	$slide.append($opr);
	$("."+id+"-operate").css({top:$slide.offset().top});
	$("."+id+"-con").css("width",length*conwidth);
	
	window.hoverpic=0;
	var picindex=length-viewnum;
	var g={running:false};
	var time=1;
	setInterval(function(){
		if(time>=7){
			if(g.running)return false;
			g.running=true;
			if(window.hoverpic>=picindex){
				window.hoverpic=0;
				}else{
				window.hoverpic=window.hoverpic+1;
			}
			var listindex=window.hoverpic;
				$("."+id+"-opr li").removeClass("hover");
				$("."+id+"-opr li:eq("+listindex+")").addClass("hover");
				$("."+id+"-con").animate({marginLeft:-conwidth*listindex},300,function(){g.running=false;time=1;});
		}else{
			time=time+1;	
		}
	},1000);
	$("."+id+"-opr li").live("click",function(){
		if(g.running)return false;
			g.running=true;
		var listindex=$(this).index();
			window.hoverpic=$(this).index();
			$("."+id+"-opr li").removeClass("hover");
			$(this).addClass("hover");
			$("."+id+"-con").animate({marginLeft:-conwidth*listindex},300,function(){g.running=false;time=1;});
	});
	$("."+id+"-operate .next").live("click",function(){
		if(g.running)return false;
			g.running=true;
		if(window.hoverpic>=picindex){
			window.hoverpic=0;
			}else{
			window.hoverpic=window.hoverpic+1;
		}
		var listindex=window.hoverpic;
			$("."+id+"-opr li").removeClass("hover");
			$(this).addClass("hover");
			$("."+id+"-con").animate({marginLeft:-conwidth*listindex},300,function(){g.running=false;time=1;});
	});
	$("."+id+"-operate .pre").live("click",function(){
		if(g.running)return false;
			g.running=true;
		if(window.hoverpic<=0){
			window.hoverpic=picindex;
			}else{
			window.hoverpic=window.hoverpic-1;
		}
		var listindex=window.hoverpic;
			$("."+id+"-opr li").removeClass("hover");
			$(this).addClass("hover");
			$("."+id+"-con").animate({marginLeft:-conwidth*listindex},300,function(){g.running=false;time=1;});
	});
}

//提示获得旺币
function msgmoneym(info,flag){
	var html=
	['<div class="o2o-art">',
            '<div class="img"><img src="./Home/Public/Image/tip/o2o-onl-ban3.png" /></div>',
            '<div class="mySecretary-con">'+info+'</div>',
			'<a href="javascript:void(0)" class="close-mySec yellow" onclick="msgmoneymclose('+flag+')"></a>',
        '</div>'].join('');
	art.dialog({
		id:'msgmoneym',
		title:false,
		content:html,
		padding:"0",
		top:"50%",
		fixed:true,
		lock:true
	});	
}
function msgmoneymclose(flag){
	var flag=parseInt(flag);
	art.dialog({id:'msgmoneym'}).close();
	if(flag==1){
		window.location.reload();
	}
}
