// JavaScript Document
$(window).load(function() {
		
});


$(document).ready(function () {
//homepage slider
	var slider = new Swipe(document.getElementById('slider'));
	
	$('.next-but-swipe').click(function(){
		slider.next();
		return false;
	});
	
	$('.prev-but-swipe').click(function(){
		slider.prev();
		return false;
	});

	// set menu to 0 height and background to transparent
	$('.menu').animate({
		height: 'toggle'
  	}, 0 );
	$('.toggle-nav-background-deco').hide(0);
	
	
	//top brodcast menu
	$('.close-brodcast').click(function(){
		 $(this).parent().parent().find('.brodcast').animate({
			marginTop: '-30px'
  		 }, 400);
	});

	//navigation button
	$('.toggle-nav').click(function(){
		$(this).parent().find('.menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.toggle-nav-background-deco').fadeToggle();
		
		$(this).parent().find('.logo').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.logo').fadeToggle();
	});
	
	$('.toggle-nav-background-deco').click(function(){
		$('.navigation .menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		 $('.toggle-nav-background-deco').fadeToggle();
	});
	
	
	//选中字效果
	$('.txt_show li').click(function(){
		$("li").each(function(){
			$(this).addClass("selectd");
		});
		});
	//actual navigation
	$('.home').click(function(){
		$(this).parent().parent().parent().parent().find('#home').show();
		$(this).parent().parent().parent().parent().find('#about').hide();
		$(this).parent().parent().parent().parent().find('#portfolio').hide();
		$(this).parent().parent().parent().parent().find('#contact').hide();
		$(this).parent().parent().parent().parent().find('.menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.toggle-nav-background-deco').fadeToggle();
	});
	
	$('.about').click(function(){
		$(this).parent().parent().parent().parent().find('#home').hide();
		$(this).parent().parent().parent().parent().find('#about').show();
		$(this).parent().parent().parent().parent().find('#portfolio').hide();
		$(this).parent().parent().parent().parent().find('#contact').hide();	
		$(this).parent().parent().parent().parent().find('.menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.toggle-nav-background-deco').fadeToggle();
	});
	
	$('.portfolio').click(function(){
		$(this).parent().parent().parent().parent().find('#home').hide();
		$(this).parent().parent().parent().parent().find('#about').hide();
		$(this).parent().parent().parent().parent().find('#portfolio').show();
		$(this).parent().parent().parent().parent().find('#contact').hide();	
		$(this).parent().parent().parent().parent().find('.menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.toggle-nav-background-deco').fadeToggle();	
	});
	
	$('.contact').click(function(){
		$(this).parent().parent().parent().parent().find('#home').hide();
		$(this).parent().parent().parent().parent().find('#about').hide();
		$(this).parent().parent().parent().parent().find('#portfolio').hide();
		$(this).parent().parent().parent().parent().find('#contact').show();	
		$(this).parent().parent().parent().parent().find('.menu').animate({
			height: 'toggle'
  		 }, 800, 'easeInOutExpo' );
		$('.toggle-nav-background-deco').fadeToggle();		
	});
	
	//notification boxes
	$(".notification-quit-red").click(function(){
	  $(".notification-box-red").hide("slow");
	  return false;
	});
	
	$(".notification-quit-green").click(function(){
	  $(".notification-box-green").hide("slow");
	  return false;
	});
	
	$(".notification-quit-yellow").click(function(){
	  $(".notification-box-yellow").hide("slow");
	  return false;
	});
	
	$(".notification-quit-blue").click(function(){
	  $(".notification-box-blue").hide("slow");
	  return false;
	});
	
	var myPhotoSwipe = $("#gallery a").photoSwipe({ 
		enableMouseWheel: false, 
		enableKeyboard: false, 
		allowUserZoom: false, 
		loop:false, 
		cacheMode:Code.PhotoSwipe.Cache.Mode.aggressive 
	});
});
	