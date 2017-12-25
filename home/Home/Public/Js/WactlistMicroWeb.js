function addactlist(){
	$(".iconEdit-list").live("click",function(){
		var openUrl = $(this).attr("data-url") || 'about:blank';
		art.dialog.open(openUrl,{
			id:"addAppMsg",
			lock: true,
			fixed:true,
			background: '#000', // 背景色
			opacity: 0.5,	// 透明度
			title:"增加一条列表",
			width:740,
			height: '430px'
			});
	});
	$('#add-url-linkAct').click(function(){
		$(".url-text").removeClass("url-hover");
		$(".url-context").removeClass("url-hover");
		$(".url-choose").addClass("url-hover");
		$(".url-block-con2").hide();
		$(".url-block-con3").hide();
		$(".url-block-con1").show();
		$("#input_i-url_type").val(0);
	});
	$('#add-url-linkText').click(function(){
		$(".url-choose").removeClass("url-hover");
		$(".url-context").removeClass("url-hover");
		$(".url-text").addClass("url-hover");
		$(".url-block-con2").show();
		$(".url-block-con1").hide();
		$(".url-block-con3").hide();
		$("#input_i-url_type").val(1);
	});
	$('#add-url-conText').click(function(){
		$(".url-choose").removeClass("url-hover");
		$(".url-text").removeClass("url-hover");
		$(".url-context").addClass("url-hover");
		$(".url-block-con3").show();
		$(".url-block-con2").hide();
		$(".url-block-con1").hide();
		$("#input_i-url_type").val(2);
	});
	$('#add-url-link').click(function(){
		art.dialog.open($(this).attr('href'),{
			lock: true,
			background: '#000', // 背景色
			opacity: 0.5,	// 透明度
			title:"添加已创建活动",
			width:800,
			height:600
		});
		return false;
	});
	
	$('.choose-color-val').click(function(){
		$(".choose-color-val").removeClass("color-hover");
		$(this).addClass("color-hover");
		$("#colorVal").val($(this).attr("data-val"));
		$(".choose-icon-val").css("background-color",$(this).css("background-color"));
	});

	$('.choose-icon-val').click(function(){
		$(".choose-icon-val").removeClass("icon-hover");
		$(this).addClass("icon-hover");
		$("#iconVal").val($(this).attr("data-val"));
	});
	//初始化事件
	if($("#input_i-url_type").val() == '0'){
		$('#add-url-linkAct').click();
	}
	else if($("#input_i-url_type").val() == '1'){
		$('#add-url-linkText').click();
	}
	else if($("#input_i-url_type").val() == '2'){
		$('#add-url-conText').click();
	}
	else
	{
		$('#add-url-linkText').click();
	}
	
	if($("#colorVal").val() == ''){
		$(".colorVal-1").click();
	}else{
		$('.'+$("#colorVal").val()).click();
	}
	if($("#iconVal").val() == ''){
		$(".iconVal-1").click();
	}else{
		$('.'+$("#iconVal").val()).click();
	}
}
