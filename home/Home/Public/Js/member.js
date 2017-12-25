$(window).resize(function(e) {//浏览器窗口变化自动载入
	memitem();
});
function memitem(){
	$(".mem-item:first").css("border-top","none");
	$(".mem-item:last").css("border-bottom","solid 1px #ccc");
	windowbox=$(window).width();
	/*
	if(windowbox<1200){
		$(".mem-icon").css("width","50px");
		$(".mem-icon p").hide();
		$(".mem-icon div").hide();
	}else{
		$(".mem-icon").css("width","230px");
		$(".mem-icon p").show();
		$(".mem-icon div").show();
	}
	*/
	if(windowbox<1200){
		$(".mem-icon").hide();
	}else{
		$(".mem-icon").show();
	}
}
function memeditmeta(){
	$(".mem-meta-editgo").click(function(){
		$(this).closest(".mem-con").toggleClass("mem-meta-editing");
    });
}
function memeditdata(){
	$(".mem-member-data-editgo").click(function(){
		$(this).closest(".mem-member-data").toggleClass("mem-member-dataediting");
    });
}
function memlevel(){
	$(".mem-level-editgo").click(function(){
		$(this).closest(".mem-item").find(".mem-con").toggleClass("mem-level-editing");
		$(this).hide();
		$(this).closest(".mem-item").find(".mem-operation-editupdate").toggleClass("dn");
		$(this).closest(".mem-item").find(".mem-operation-editstart").toggleClass("dn");
    });
}