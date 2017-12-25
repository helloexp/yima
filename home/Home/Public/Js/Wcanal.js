$(document).ready(function(e){
	tchange();
});
function go_pagetwo(){
	
}

function tchange(){
	$(".t-change-span").mouseover(function(){
		$(".t-change-span").closest("td").find(".icon-change").css("visibility","hidden");
		$(this).closest("td").find(".icon-change").css("visibility","visible");
    });
	$(".icon-change").bind('click',function(){
		$(this).closest("td").find(".t-change-span").hide();
		$(this).closest("td").find(".t-change-input").show();
		$(this).closest("td").find(".icon-change").css("visibility","hidden");
		$(this).closest("td").find(".icon-change-ok").css("visibility","visible");
    });
}