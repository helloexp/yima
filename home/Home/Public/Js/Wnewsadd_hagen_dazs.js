
$(document).ready(function(e){
	var num=parseInt($(".global-input2-choose").length)+1;
	if(!num){
		num=2;
	}
	form_change(num);
		var showpreview=$(".set_main_hover").attr("data-val");
		if(showpreview==1){
			$("#iphonePreview-con").animate({marginLeft:'0px'},300);
			$(".Preview-input_button").css("background","#BF0600");
		}else if(showpreview==2){
			$("#iphonePreview-con").animate({marginLeft:'-250px'},300);
			$(".Preview-input_button").css("background","#BF0600");
		}else if(showpreview==3){
			$("#iphonePreview-con").animate({marginLeft:'-500px'},300);
			$(".Preview-input_button").css("background","#015406");
		};
		
	$(".set_main").click(function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");
		$("#page_style").val($(this).attr("data-val"));
		var showpreview=$(this).index();
		if(showpreview==1){
			$("#iphonePreview-con").animate({marginLeft:'0px'},300);
			$(".Preview-input_button").css("background","#BF0600");
		}else if(showpreview==2){
			$("#iphonePreview-con").animate({marginLeft:'-250px'},300);
			$(".Preview-input_button").css("background","#BF0600");
		}else if(showpreview==3){
			$("#iphonePreview-con").animate({marginLeft:'-500px'},300);
			$(".Preview-input_button").css("background","#015406");
			$(".set_bg_img:eq(2)").click();
		}
	});
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

function form_change(num){
	$("#logo-true").click(function(){
		$("#logo-upload").css("display","block");
	});
	$("#logo-false").click(function(){
		$("#logo-upload").css("display","none");
	});
	
	$("#addmul1").click(function(){
		var muladnum=$(".addmulad1").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmulada'+num+'" class="global-input2-choose addmulad1">',
				'<div class="title"><p>单<br />选<br />题</p></div>',
				'<div class="input">',
				'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10" /></p>',
				'<p class="mul1 mt10"><input name="a_'+thisid+'[]" id="a_1_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
				'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_2_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260"/></p>',
				'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_3_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
				'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_4_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
				'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_5_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
				'<a href="javascript:void(0)" class="input-addone fn" id="addone_'+thisid+'" data-type="addmu11" data-num="5" data-rel="'+thisid+'"><i></i>增加1条答案</span></a>',	
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmulada'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a onclick="downchoose('+num+')" class="btn-down a-hide">下移</a>',
				'<input type="hidden" name="t_'+thisid+'" value="1" /><input type="hidden" class="qx" name="sort_'+thisid+'" value="'+thisid+'" />',
				'</div>',
				
				'</div>'].join('');

			if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,1);
			}else{
				muladnum=muladnum-1
				$("#addmulad").after(addcon);
				PreviewbmAddpage(muladnum,num,thisid,1);
			}
			$(".global-input2-choose").fadeIn(600);
			num=num+1;
	});
	

	
	$("#addmul2").click(function(){
		var muladnum=$(".addmulad2").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmuladb'+num+'" class="global-input2-choose addmulad2">',
				'<div class="title"><p>多<br />选<br />题</p></div>',
				'<div class="input">',
				'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10" /></p>',
				'<p class="mul2 mt10"><input name="a_'+thisid+'[]" id="a_1_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
				'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_2_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
				'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_3_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
				'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_4_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
				'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_5_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
				'<a href="javascript:void(0)" class="input-addone fn" data-type="addmu12" data-num="5" data-rel="'+thisid+'"><i></i>增加1条答案</span></a>',	
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmuladb'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a class="btn-down a-hide" onclick="downchoose('+num+')">下移</a>',
				'<input type="hidden" name="t_'+thisid+'" value="2" /><input type="hidden" class="qx" name="sort_'+thisid+'" value="'+thisid+'" />',
				'</div>',
				
				'</div>'].join('');
			
			if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,2);
			}else{
				muladnum=muladnum-1
				$("#addmulad").after(addcon);
				PreviewbmAddpage(muladnum,num,thisid,2);
			}
			$(".global-input2-choose").fadeIn(600);
			num=num+1;
	});
	
	$("body").on("click",".input-addone",function(){
		var thisid = $(this).attr("data-rel");
		var datanum = $(this).attr("data-num");
		var datatype = $(this).attr("data-type");
		datanum = ++datanum;
		if(datatype == 'addmu11')
			var addone = '<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"></p>',
				addli = '<li><input type="radio" class="PreviewBm-sOne-Radio" name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'"><label class="chosea_a_'+datanum+'_'+thisid+'">为空则不显示</label></li>';
		else if(datatype == 'addmu12')
			var addone = '<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'" type="text" class="textbox previewbmtext w260"></p>',
				addli = '<li><input type="checkbox" class="PreviewBm-sOne-Radio" name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'"><label class="chosea_a_'+datanum+'_'+thisid+'">为空则不显示</label></li>';
		$(this).before(addone);
		$("#choseq_"+thisid).append(addli);
		$(this).attr('data-num',datanum);
	});
		
	$("#addmul3").click(function(){
		var muladnum=$(".addmulad3").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmuladc'+num+'" class="global-input2-choose addmulad3">',
				'<div class="title"><p>问<br />答<br />题</p></div>',
				'<div class="input">',
				'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10"/></p>',
				'<p class="mul3 mt5 mb5"><textarea readonly class="texttextarea w260"></textarea></p>',
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmuladc'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a class="btn-down a-hide" onclick="downchoose('+num+')">下移</a>',
				'<input type="hidden" name="t_'+thisid+'" value="3" /><input type="hidden" class="qx" name="sort_'+thisid+'" value="'+thisid+'" />',
				'</div>',
				'</div>'].join('');
			if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,3);
			}else{
				muladnum=muladnum-1
				$("#addmulad").after(addcon);
				PreviewbmAddpage(muladnum,num,thisid,3);
			}
			$(".global-input2-choose").fadeIn(600);
			
			num=num+1;
	});
	
	$("#addmul4_1").click(function(){
		var muladnum=$(".addmulad4").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmuladd'+num+'" class="global-input2-choose addmulad3">',
				'<div class="title"><p>地<br />图<br />调<br />研</p></div>',
				'<div class="input">',
				'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10"/></p>',
				'<p class="mul3 mt5 mb5"><img src="./Home/Public/Image/dm_map_thumb.png"  class="w260" /></p>',
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmuladd'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a class="btn-down a-hide" onclick="downchoose('+num+')">下移</a>',
				'<input type="hidden" name="t_'+thisid+'" value="4" /><input type="hidden" class="qx" name="sort_'+thisid+'" value="'+thisid+'" />',
				'</div>',
				'</div>'].join('');
			if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,4);
			}else{
				muladnum=muladnum-1
				$("#addmulad").after(addcon);
				PreviewbmAddpage(muladnum,num,thisid,4);
			}
			$(".global-input2-choose").fadeIn(600);
			
			num=num+1;
	});
	
	
	$("body").on("click",".btn-down",function(){
		if($(this).closest(".global-input2-choose").index()>=$(".global-input2-choose").length+3){
			alert("已经置底");
		}else{
			$(this).closest(".global-input2-choose").before($(this).closest(".global-input2-choose").next());
			var _this = $(this).closest(".global-input2-choose").attr("id");
				_this = _this.replace(/[^0-9]/ig,"")*1+1;
				console.log(_this)
			var obja = $("#iphonePreview-one #choseq_"+_this);
			var objb = $("#iphonePreview-two #choseq_"+_this);
			var objc = $("#iphonePreview-three #choseq_"+_this);
				obja.after(obja.prev());
				objb.after(objb.prev());
				objc.after(objc.prev());
			$(".global-input2-choose").each(function(index, element) {
				$(this).find("[name^='sort_']").val($(".global-input2-choose").length-index);
			});
		}
	});
	$("body").on("click",".btn-up",function(){
		if($(this).closest(".global-input2-choose").index()<=4){
			alert("已经置顶");
		}else{
			$(this).closest(".global-input2-choose").after($(this).closest(".global-input2-choose").prev());
			var _this = $(this).closest(".global-input2-choose").attr("id");
				_this = _this.replace(/[^0-9]/ig,"")*1+1;
				console.log()
			var obja = $("#iphonePreview-one #choseq_"+_this);
			var objb = $("#iphonePreview-two #choseq_"+_this);
			var objc = $("#iphonePreview-three #choseq_"+_this);
				obja.before(obja.next());
				objb.before(objb.next());
				objc.before(objc.next());
			$(".global-input2-choose").each(function(index, element) {
				$(this).find("[name^='sort_']").val($(".global-input2-choose").length-index);
			});
		}
	});
	
	//投票
	$("#addmul4").click(function(){
		var muladnum=$(".addmulad1").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmulada'+num+'" class="global-input2-choose addmulad1">',
				'<div class="title"><p>单<br />选<br />投<br />票</p></div>',
				'<div class="input">',
					'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10" /></p>',
					'<p class="mul1 mt10"><input name="a_'+thisid+'[]" id="a_1_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_2_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_3_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_4_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_5_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<div class="dn input-add1">',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_6_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_7_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_8_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_9_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_10_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'</div>',
					'<div class="dn input-add2">',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_11_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_12_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_13_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_14_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_15_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'</div>',
					'<div class="dn input-add3">',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_16_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_17_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_18_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_19_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_20_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'</div>',
					'<div class="dn input-add4">',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_21_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_22_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_23_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_24_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'<p class="mul1 mt5"><input name="a_'+thisid+'[]" id="a_25_'+thisid+'" type="text" class="validate[] previewbmtext textbox w260"/></p>',
					'</div>',
					'<a href="javascript:void(0)" class="input-addfive fn" data-rel="'+thisid+'"><i></i>增加5条<span>(投票内容为空则不显示)</span></a>',				
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmulada'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a onclick="downchoose('+num+')" class="btn-down a-hide">下移</a>',
				'<input type="hidden" name="t_'+thisid+'" value="1"/><input type="hidden" class="qx name="sort_'+thisid+'" value="'+thisid+'"/>',
				'</div>',
				'</div>'].join('');
			if(num<=30){
				if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewvoteAddpage(muladnum,num,thisid,1);
				}else{
					muladnum=muladnum-1;
					$("#addmulad").after(addcon);
					PreviewvoteAddpage(muladnum,num,thisid,1);
				}
				$(".global-input2-choose").fadeIn(600);
			}else{
				alert("最多提30个问题")	
			};
			num=num+1;
	});
	$("#addmul5").click(function(){
		var muladnum=$(".addmulad2").length;
		var thisid =num+1;
		var	addcon=[
				'<div id="addmuladb'+num+'" class="global-input2-choose addmulad2">',
				'<div class="title"><p>多<br />选<br />投<br />票</p></div>',
				'<div class="input">',
					'<p><input name="q_'+thisid+'" type="text" id="page_title1"  class="validate[required] previewbmtext textbox w280 mt10" /></p>',
					'<p class="mul2 mt10"><input name="a_'+thisid+'[]" id="a_1_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_2_'+thisid+'" type="text" class="validate[required] previewbmtext textbox w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_3_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_4_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_5_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<div class="dn input-add1">',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_6_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_7_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_8_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_9_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_10_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'</div>',
					'<div class="dn input-add2">',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_11_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_12_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_13_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_14_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_15_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'</div>',
					'<div class="dn input-add3">',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_16_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_17_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_18_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_19_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_20_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'</div>',
					'<div class="dn input-add4">',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_21_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_22_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_23_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_24_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'<p class="mul2 mt5"><input name="a_'+thisid+'[]" id="a_25_'+thisid+'" type="text" class="textbox previewbmtext w260" /></p>',
					'</div>',
					'<a href="javascript:void(0)" class="input-addfive fn" data-rel="'+thisid+'"><i></i>增加5条<span>(投票内容为空则不显示)</span></a>',
				'</div>',
				'<div class="del">',
				'<a class="btn-up a-hide" onclick="upchoose('+num+')">上移</a><a onclick="delchoose(addmuladb'+num+','+num+')" class="btn-del a-hide" title="删除"></a><a class="btn-down a-hide" onclick="downchoose('+num+')">下移</a>',
				'<input type="hidden"  name="t_'+thisid+'" value="2" /><input type="hidden" class="qx" name="sort_'+thisid+'" value="'+thisid+'" />',
				'</div>',
				'</div>'].join('');
			if(num<=30){
				if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewvoteAddpage(muladnum,num,thisid,2);
				}else{
					muladnum=muladnum-1
					$("#addmulad").after(addcon);
					PreviewvoteAddpage(muladnum,num,thisid,2);
				}
				$(".global-input2-choose").fadeIn(600);
			}else{
				alert("最多提30个问题")	
			};
			num=num+1;
	});
	$("body").on("click",".input-addfive",function(){
		$(this).closest(".input").find(".input-add1").show();
		$(this).removeClass("input-addfive").addClass("input-addfive-again");
		$("#choseq_"+$(this).attr("data-rel")).find(".preview-add1").show();
	});
	$("body").on("click",".input-addfive-again",function(){
		$(this).closest(".input").find(".input-add2").show();
		$(this).removeClass("input-addfive-again").addClass("input-addfive-again1");
		$("#choseq_"+$(this).attr("data-rel")).find(".preview-add2").show();
	});
	$("body").on("click",".input-addfive-again1",function(){
		$(this).closest(".input").find(".input-add3").show();
		$(this).removeClass("input-addfive-again1").addClass("input-addfive-again2");
		$("#choseq_"+$(this).attr("data-rel")).find(".preview-add3").show();
	});
	$("body").on("click",".input-addfive-again2",function(){
		$(this).closest(".input").find(".input-add4").show();
		$(this).removeClass("input-addfive-again2").addClass("input-addfive-none");
		$("#choseq_"+$(this).attr("data-rel")).find(".preview-add4").show();
	});
}

function delchoose(id,num){
	var num=num+1;
		$(id).slideUp(300,function(){$(id).remove();});
		$("#iphonePreview-one #choseq_"+num).slideUp(300,function(){$("#iphonePreview-one #choseq_"+num).remove();
		$("#iphonePreview-one hr").show();
		$("#iphonePreview-one hr:eq(0)").hide();
		});
		$("#iphonePreview-two #choseq_"+num).slideUp(300,function(){$("#iphonePreview-two #choseq_"+num).remove();
		$("#iphonePreview-two hr").show();
		$("#iphonePreview-two hr:eq(0)").hide();
		});
		$("#iphonePreview-three #choseq_"+num).slideUp(300,function(){$("#iphonePreview-three #choseq_"+num).remove();
		$("#iphonePreview-three hr").show();
		$("#iphonePreview-three hr:eq(0)").hide();
		});
}

function downchoose(id){
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$("#iphonePreview-three hr").show();
	$("#iphonePreview-three hr:eq(0)").hide();
}
function upchoose(id){
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$("#iphonePreview-three hr").show();
	$("#iphonePreview-three hr:eq(0)").hide();
}

function logoimgchange(url) {
	$(".iphone-logo-show").attr("src",url);
}


//活动创建-抽奖活动
function PreviewnewsAdd(){
	$("#is_music").click(function(){
		$(".Preview-voice_button").show();
	});
	$("#no_music").click(function(){
		$(".Preview-voice_button").hide();
	});
	$("input[name='sns_type[]']").click(function(){
		var sns_type=$(this).val();
			sns_type2=sns_type+4
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-share").show();
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").show();
			$("#iphonePreview-two .Preview-share").show();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_bg_img").click(function(){
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$(this).addClass("set_bg_img_hover");
		$("#set_bg").val($(this).attr("src"));
		var	showimgurl=$(this).attr("src");
			$(".Preview-vip-img").hide();
			$(".Preview-vip-img").attr("src",showimgurl);
			$(".Preview-vip-img").fadeIn(200);
	});
	$("#video_url").change(function(){
		if($("#video_url").val==""){
			$(".Preview-video_button").hide();
		}else{
			$(".Preview-video_button").show();
		}
	});
	$("#wap_title").keyup(function(){
		$(".Preview-mainCon-title").text($("#wap_title").val());
	});
	$("#node_name").keyup(function(){
		$(".Preview-top-title").text($("#node_name").val());
	});
	$("#cj_button_text").keyup(function(){
		$(".Preview-input_button").val($("#cj_button_text").val());
	});	
}

//活动创建-调研活动
function PreviewbmAdd(){
	$("#is_music").click(function(){
		$(".Preview-voice_button").show();
	});
	$("#no_music").click(function(){
		$(".Preview-voice_button").hide();
	});
	$("input[name='sns_type[]']").click(function(){
		var sns_type=$(this).val();
			sns_type2=sns_type+4
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-share").show();
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").show();
			$("#iphonePreview-two .Preview-share").show();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").show();
			$("#iphonePreview-three .Preview-share").show();
			$("#iphonePreview-three .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-three .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_bg_img").click(function(){
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$(this).addClass("set_bg_img_hover");
		$("#set_bg").val($(this).attr("src"));
		var	showimgurl=$(this).attr("src");
			$(".Preview-vip-img").hide();
			$(".Preview-vip-img").attr("src",showimgurl);
			$(".Preview-vip-img").fadeIn(200);
	});
	$("#video_url").change(function(){
		if($("#video_url").val==""){
			$(".Preview-video_button").hide();
		}else{
			$(".Preview-video_button").show();
		}
	});
	$("#wap_title").keyup(function(){
		$(".Preview-mainCon-title").text($("#wap_title").val());
	});
	$("textarea[name='wap_info']").keyup(function(){
		$(".Preview-mainCon-text").text($(this).val());
	});
	$("#node_name").keyup(function(){
		$(".Preview-top-title").text($("#node_name").val());
		var fData=$(".Preview-top-title").text();
	});
	$(".wap_check").click(function(){
		var wapcheck=$(this).attr("value");
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-base-"+wapcheck).show();
			$("#iphonePreview-two .Preview-base-"+wapcheck).show();
		}else{
			$("#iphonePreview-one .Preview-base-"+wapcheck).hide();
			$("#iphonePreview-two .Preview-base-"+wapcheck).hide();
		}
	});
	$(".wap_check_title").keyup(function(){
		var name=$(this).attr("name");
		if(name.substr(0, 7) == 'defined'){
			$i = name.substr(7);
			$sel = '.Preview-base-'+$i+' p';

			$("#iphonePreview-three "+$sel).text($(this).val()+"：");
			$("#iphonePreview-two "+$sel).text($(this).val()+"：");
			$("#iphonePreview-one "+$sel).text($(this).val()+"：");
		}
	});
	$("body").on("keyup",".previewbmtext",function(){
		var val=$(this).val();
		var nameid=$(this).attr("name");
			chooseid=$(this).attr("id");
		$("#chose"+nameid+" h3 span").text(val);
		$("#iphonePreview-one .chosea_"+chooseid).text(val);
		$("#iphonePreview-two .chosea_"+chooseid).text(val);
		$("#iphonePreview-three .chosea_"+chooseid).text(val);
	});
};
function PreviewbmAddpage(muladnum,num,thisid,id){
	var previewnum=$(".global-input2-choose").length;
	var previeid=num+1;
	var htmlone =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li>',
			'<hr><h3><b>单选题:</b><span>单选题</span></h3>',
			'</li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-1"><label for="id-'+num+'-1" class="chosea_a_1_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-2"><label for="id-'+num+'-2" class="chosea_a_2_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-3"><label for="id-'+num+'-3" class="chosea_a_3_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-4"><label for="id-'+num+'-4" class="chosea_a_4_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-5"><label for="id-'+num+'-5" class="chosea_a_5_'+previeid+'">为空则不显示</label></li>',
			'</div>'].join('');
	var htmltwo =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li><hr><h3><b>问答题:</b><span>问答题</span></h3></li>',
			'<li><textarea name="previewtextarea'+num+'" class="PreviewBm-sOne-Textarea"></textarea></li>',
			'</div>'].join('');
	var htmlthree =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li><hr><h3><b>多选题:</b><span>多选题</span></h3></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-1"><label for="id-'+num+'-1" class="chosea_a_1_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-2"><label for="id-'+num+'-2" class="chosea_a_2_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-3"><label for="id-'+num+'-3" class="chosea_a_3_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-4"><label for="id-'+num+'-4" class="chosea_a_4_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-5"><label for="id-'+num+'-5" class="chosea_a_5_'+previeid+'">为空则不显示</label></li>',
			'</div>'].join('');
	var htmlfour =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li><hr><h3><b>地图调研:</b><span>地图调研</span></h3></li>',
			'<li><img class="w260" src="./Home/Public/Image/dm_map_thumb.png"></li>',
			'</div>'].join('');
	if(id==1){
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmlone);
	}else if(id==3){
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmltwo);
	}else if(id==4){
        $(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmlfour);
    }else{
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmlthree);
	}
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$(".PreviewBm-question").slideDown(500);
	jsScroll(document.getElementById('iphonePreview'),11, 27, 'divSrollBar');
}

//活动创建-会员招募活动
function PreviewmemAdd(){
	$("#is_music").click(function(){
		$(".Preview-voice_button").show();
	});
	$("#no_music").click(function(){
		$(".Preview-voice_button").hide();
	});
	$("input[name='sns_type[]']").click(function(){
		var sns_type=$(this).val();
			sns_type2=sns_type+4
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-share").show();
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").show();
			$("#iphonePreview-two .Preview-share").show();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_bg_img").click(function(){
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$(this).addClass("set_bg_img_hover");
		$("#set_bg").val($(this).attr("src"));
		var	showimgurl=$(this).attr("src");
			$(".Preview-vip-img").hide();
			$(".Preview-vip-img").attr("src",showimgurl);
			$(".Preview-vip-img").fadeIn(200);
	});
	$("#video_url").change(function(){
		if($("#video_url").val==""){
			$(".Preview-video_button").hide();
		}else{
			$(".Preview-video_button").show();
		}
	});
	$("textarea[name='wap_info']").keyup(function(){
		$(".Preview-mainCon-text").text($(this).val());
		jsScroll(document.getElementById('iphonePreview'),11, 27, 'divSrollBar');
	});
	$("#node_name").keyup(function(){
		$(".Preview-top-title").text($("#node_name").val());
	});
	
	$("input[name='field_name']").click(function(){
		if($(this).attr("checked")){
			$("#iphonePreview-one #PreviewBm-field1").show();
			$("#iphonePreview-two #PreviewBm-field1").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field1").hide();
			$("#iphonePreview-two #PreviewBm-field1").hide();
		}
	});
	$("input[name='field_birthday']").click(function(){
		if($(this).attr("checked")){
			$("#iphonePreview-one #PreviewBm-field2").show();
			$("#iphonePreview-two #PreviewBm-field2").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field2").hide();
			$("#iphonePreview-two #PreviewBm-field2").hide();
		}
	});
	$("input[name='field_sex']").click(function(){
		if($(this).attr("checked")){
			$("#iphonePreview-one #PreviewBm-field3").show();
			$("#iphonePreview-two #PreviewBm-field3").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field3").hide();
			$("#iphonePreview-two #PreviewBm-field3").hide();
		}
	});
	$("input[name='field_name_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s1").show();
			$("#iphonePreview-two #PreviewBm-field-s1").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s1").hide();
			$("#iphonePreview-two #PreviewBm-field-s1").hide();
		}
	});
	$("input[name='field_birthday_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s2").show();
			$("#iphonePreview-two #PreviewBm-field-s2").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s2").hide();
			$("#iphonePreview-two #PreviewBm-field-s2").hide();
		}
	});
	$("input[name='field_sex_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s3").show();
			$("#iphonePreview-two #PreviewBm-field-s3").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s3").hide();
			$("#iphonePreview-two #PreviewBm-field-s3").hide();
		}
	});
}
function previewuploadimg(url,type){
	if(type=="logo"){
		$(".Preview-logo-con img").hide();
		$(".Preview-logo-con img").attr("src",url);
		$(".Preview-logo-con img").fadeIn(200);
	}else if(type=="background"){
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$("#add_set_bg_img").show();
		$("#add_set_bg_img").attr("src",url);
		$("#add_set_bg_img").addClass("set_bg_img_hover");
		$(".add_set_bg").removeClass("ml10");
		$("#set_bg").val(url);
		$(".Preview-vip-img").hide();
		$(".Preview-vip-img").attr("src",url);
		$(".Preview-vip-img").fadeIn(200);
	};
}



//活动创建-投票活动
function PreviewvoteAdd(){
	$("#is_music").click(function(){
		$(".Preview-voice_button").show();
	});
	$("#no_music").click(function(){
		$(".Preview-voice_button").hide();
	});
	$("input[name='sns_type[]']").click(function(){
		var sns_type=$(this).val();
			sns_type2=sns_type+4
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-share").show();
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").show();
			$("#iphonePreview-two .Preview-share").show();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_bg_img").click(function(){
		$(".set_bg_img").removeClass("set_bg_img_hover");
		$(this).addClass("set_bg_img_hover");
		$("#set_bg").val($(this).attr("src"));
		var	showimgurl=$(this).attr("src");
			$(".Preview-vip-img").hide();
			$(".Preview-vip-img").attr("src",showimgurl);
			$(".Preview-vip-img").fadeIn(200);
	});
	$("#video_url").change(function(){
		if($("#video_url").val==""){
			$(".Preview-video_button").hide();
		}else{
			$(".Preview-video_button").show();
		}
	});
	$("#wap_title").keyup(function(){
		$(".Preview-mainCon-title").text($("#wap_title").val());
	});
	$("textarea[name='wap_info']").keyup(function(){
		$(".Preview-mainCon-text").text($(this).val());
	});
	$("#node_name").keyup(function(){
		$(".Preview-top-title").text($("#node_name").val());
		var fData=$(".Preview-top-title").text();
	});
	$(".wap_check").click(function(){
		var wapcheck=$(this).attr("value");
		if($(this).attr("checked")){
			$("#iphonePreview-one .Preview-base-"+wapcheck).show();
			$("#iphonePreview-two .Preview-base-"+wapcheck).show();
		}else{
			$("#iphonePreview-one .Preview-base-"+wapcheck).hide();
			$("#iphonePreview-two .Preview-base-"+wapcheck).hide();
		}
	});
	$(".wap_check_title").keyup(function(){
		var name=$(this).attr("name");
		if(name=="defined10"){
			$("#iphonePreview-two .Preview-base-10 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-10 p").text($(this).val()+"：");
		}else if(name=="defined11"){
			$("#iphonePreview-two .Preview-base-11 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-11 p").text($(this).val()+"：");
		}else{
			$("#iphonePreview-two .Preview-base-12 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-12 p").text($(this).val()+"：");			
		}
	});
	$(".previewbmtext").live("keyup",function(){
		var val=$(this).val();
		var nameid=$(this).attr("name");
			chooseid=$(this).attr("id");
		$("#chose"+nameid+" h3 span").text(val);
		$("#iphonePreview-one .chosea_"+chooseid).text(val);
		$("#iphonePreview-two .chosea_"+chooseid).text(val);
	});
};
function PreviewvoteAddpage(muladnum,num,thisid,id){
	var previewnum=$(".global-input2-choose").length;
	var previeid=num+1;
	var htmlone =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li>',
			'<hr><h3><b>单选题:</b><span>单选题</span></h3>',
			'</li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-1"><label for="id-'+num+'-1" class="chosea_a_1_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-2"><label for="id-'+num+'-2" class="chosea_a_2_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-3"><label for="id-'+num+'-3" class="chosea_a_3_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-4"><label for="id-'+num+'-4" class="chosea_a_4_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-5"><label for="id-'+num+'-5" class="chosea_a_5_'+previeid+'">为空则不显示</label></li>',
			'<div class="dn preview-add1">',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-6"><label for="id-'+num+'-5" class="chosea_a_6_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-7"><label for="id-'+num+'-5" class="chosea_a_7_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-8"><label for="id-'+num+'-5" class="chosea_a_8_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-9"><label for="id-'+num+'-5" class="chosea_a_9_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-10"><label for="id-'+num+'-5" class="chosea_a_10_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add2">',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-11"><label for="id-'+num+'-5" class="chosea_a_11_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-12"><label for="id-'+num+'-5" class="chosea_a_12_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-13"><label for="id-'+num+'-5" class="chosea_a_13_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-14"><label for="id-'+num+'-5" class="chosea_a_14_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-15"><label for="id-'+num+'-5" class="chosea_a_15_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add3">',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-16"><label for="id-'+num+'-5" class="chosea_a_16_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-17"><label for="id-'+num+'-5" class="chosea_a_17_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-18"><label for="id-'+num+'-5" class="chosea_a_18_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-19"><label for="id-'+num+'-5" class="chosea_a_19_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-120"><label for="id-'+num+'-5" class="chosea_a_20_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add4">',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-21"><label for="id-'+num+'-5" class="chosea_a_21_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-22"><label for="id-'+num+'-5" class="chosea_a_22_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-23"><label for="id-'+num+'-5" class="chosea_a_23_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-24"><label for="id-'+num+'-5" class="chosea_a_24_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="radio" class="PreviewBm-sOne-Radio" name="previewradio'+num+'" id="id-'+num+'-25"><label for="id-'+num+'-5" class="chosea_a_25_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'</div>'].join('');
	var htmltwo =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li><hr><h3><b>问答题:</b><span>问答题</span></h3></li>',
			'<li><textarea name="previewtextarea'+num+'" class="PreviewBm-sOne-Textarea"></textarea></li>',
			'</div>'].join('');
	var htmlthree =
			['<div id="choseq_'+previeid+'" class="PreviewBm-question dn">',
			'<li><hr><h3><b>多选题:</b><span>多选题</span></h3></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-1"><label for="id-'+num+'-1" class="chosea_a_1_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-2"><label for="id-'+num+'-2" class="chosea_a_2_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-3"><label for="id-'+num+'-3" class="chosea_a_3_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-4"><label for="id-'+num+'-4" class="chosea_a_4_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-5"><label for="id-'+num+'-5" class="chosea_a_5_'+previeid+'">为空则不显示</label></li>',
			'<div class="dn preview-add1">',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-6"><label for="id-'+num+'-5" class="chosea_a_6_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-7"><label for="id-'+num+'-5" class="chosea_a_7_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-8"><label for="id-'+num+'-5" class="chosea_a_8_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-9"><label for="id-'+num+'-5" class="chosea_a_9_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-10"><label for="id-'+num+'-5" class="chosea_a_10_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add2">',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-11"><label for="id-'+num+'-5" class="chosea_a_11_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-12"><label for="id-'+num+'-5" class="chosea_a_12_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-13"><label for="id-'+num+'-5" class="chosea_a_13_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-14"><label for="id-'+num+'-5" class="chosea_a_14_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-15"><label for="id-'+num+'-5" class="chosea_a_15_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add3">',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-16"><label for="id-'+num+'-5" class="chosea_a_16_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-17"><label for="id-'+num+'-5" class="chosea_a_17_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-18"><label for="id-'+num+'-5" class="chosea_a_18_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-19"><label for="id-'+num+'-5" class="chosea_a_19_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-120"><label for="id-'+num+'-5" class="chosea_a_20_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'<div class="dn preview-add4">',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-21"><label for="id-'+num+'-5" class="chosea_a_21_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-22"><label for="id-'+num+'-5" class="chosea_a_22_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-23"><label for="id-'+num+'-5" class="chosea_a_23_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-24"><label for="id-'+num+'-5" class="chosea_a_24_'+previeid+'">为空则不显示</label></li>',
			'<li><input type="checkbox" class="PreviewBm-sOne-checkbox" name="previewcheckbox'+num+'" id="id-'+num+'-25"><label for="id-'+num+'-5" class="chosea_a_25_'+previeid+'">为空则不显示</label></li>',
			'</div>',
			'</div>'].join('');
	if(id==1){
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmlone);
	}else if(id==3){
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmltwo);
	}else{
		$(".PreviewBm-bmForm #PreviewBm-bmForm-bigin").before(htmlthree);
	}
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$(".PreviewBm-question").slideDown(500);
	jsScroll(document.getElementById('iphonePreview'),11, 27, 'divSrollBar');
}