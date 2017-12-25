$(function(){
	var fxzshtml = "<div id='fxzs_goto_proInt' class='goto_proInt'></div>";
	$("body").append(fxzshtml);
	$("#fxzs_goto_proInt").click(function(e) {
		location.href = "index.php?g=Hall&m=Mypartner&a=introduction&type=1";
	});
})
		
