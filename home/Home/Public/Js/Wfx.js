$(document).ready(function(e) {
	$(".dtreeList").hoverDelay( 
		function(){ 
		    $(".dtreeCon").fadeIn(); 
		}, 
		function(){ 
		    $(".dtreeCon").fadeOut(); 
		} 
	); 
		
	$(".toggle").hoverDelay( 
		function(){ 
		    $(".help_content").fadeIn(); 
		}, 
		function(){ 
		    $(".help_content").fadeOut(); 
		} 
	); 
	
});
