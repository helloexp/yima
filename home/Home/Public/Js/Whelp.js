$(document).ready(function(e){
	helpsolide();
});
function helpsolide(){
	$(".help-list p").click(function(){
		var listindex=$(this).closest(".help-list li").index();
			$(this).closest(".help-list").find("li").removeClass("help-list-hover");
			$(this).closest("li").addClass("help-list-hover");
			$(this).closest(".subcon").find(".help-con ul").animate({marginLeft:-744*listindex});
	});
	$(".pre").click(function(){
		var marginLeft=parseInt($(this).closest(".subcon").find(".help-con ul").css("marginLeft"));
		if(marginLeft<0){
			$(this).closest(".subcon").find(".help-con ul").animate({marginLeft:marginLeft+744});
			var nowlist=Math.abs(marginLeft/744)-1;
			$(this).closest(".subcon").find(".help-list li").removeClass("help-list-hover");
			$(this).closest(".subcon").find(".help-list li:eq("+nowlist+")").addClass("help-list-hover");
		}
	});
	$(".next").click(function(){
		var marginLeft=parseInt($(this).closest(".subcon").find(".help-con ul").css("marginLeft"));
		var maxleft=-$(this).closest(".subcon").find(".help-list li").length*744+744;
		if(marginLeft>maxleft){
			$(this).closest(".subcon").find(".help-con ul").animate({marginLeft:marginLeft-744});
			var nowlist=Math.abs(marginLeft/744)+1;
			$(this).closest(".subcon").find(".help-list li").removeClass("help-list-hover");
			$(this).closest(".subcon").find(".help-list li:eq("+nowlist+")").addClass("help-list-hover");
		}
	});
}

function userhelp(id){
	var html =
			['<div class="userhelpBg dn">',
        	'<div class="userhelpBg2">',
        	'<div class="userhelpbtnclose" onclick="userhelpclose()"></div>',
        	'<div class="userhelpflash">',
        	'<embed type="application/x-shockwave-flash" src="./Home/Public/Image/userhelp'+id+'.swf" width="980" height="588" id="mym" name="mym" quality="best" salign="t" scale="noscale" menu="false" loop="true" wmode="transparent"></embed>',
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
