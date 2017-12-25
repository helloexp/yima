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
		$(".url-choose").addClass("url-hover");
		$(".url-block-con2").hide();
		$(".url-block-con1").show();
		$("#input_i-url_type").val(1);
	});
	$('#add-url-linkText').click(function(){
		$(".url-choose").removeClass("url-hover");
		$(".url-text").addClass("url-hover");
		$(".url-block-con2").show();
		$(".url-block-con1").hide();
		$("#input_i-url_type").val(0);
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

	//初始化事件
	if($("#input_i-url_type").val() == '0'){
		$('#add-url-linkText').click();
	}
	else{
		$('#add-url-linkAct').click();
	}
}
