
$(document).ready(function(e){
	var num=parseInt($(".global-input2-choose").length)+1;
	if(!num){
		num=2;
	}
	form_change(num);
		var shownum=$(".set_main_hover").index()-1;
		if(shownum==-2){shownum=0;}
		var moveleft = shownum*250;
		$("#iphonePreview-con").animate({marginLeft:-moveleft},300);
		$(".Preview-top-img").find("img").attr("src",$("#picsrc").val());
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
			addcon="<div id='addmulada"+num+"' class='global-input2-choose addmulad1'><div class='title'><p>单<br />选<br />题</p></div><div class='input'><p><input name='q_"+thisid+"' type='text' id='page_title1'  class='validate[required] previewbmtext textbox w280 mt10'/></p><p class='mul1 mt10 rel'><input type='radio' class='bmradio' name='c_"+thisid+"' value='1'><input name='a_"+thisid+"[]' id='a_1_"+thisid+"' type='text' class='validate[required] previewbmtext textbox w260'/></p><p class='mul1 mt5 rel'><input type='radio' name='c_"+thisid+"' value='2' class='bmradio'><input name='a_"+thisid+"[]' id='a_2_"+thisid+"' type='text' class='validate[required] previewbmtext textbox w260'/></p><p class='mul1 mt5 rel'><input type='radio' name='c_"+thisid+"' value='3' class='bmradio'><input name='a_"+thisid+"[]' id='a_3_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><p class='mul1 mt5 rel'><input type='radio' name='c_"+thisid+"' value='4' class='bmradio'><input name='a_"+thisid+"[]' id='a_4_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><p class='mul1 mt5 rel'><input type='radio' name='c_"+thisid+"' value='5' class='bmradio'><input name='a_"+thisid+"[]' id='a_5_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><a href='javascript:void(0)' class='input-addone fn' id='addone_"+thisid+"' data-type='addmu11' data-num='5' data-rel='"+thisid+"'><i></i>增加1条答案（最多50条）</span></a></div><div class='del'><a class='btn-up a-hide' onclick='upchoose("+num+")'>上移</a><a onclick='delchoose(addmulada"+num+","+num+")' class='btn-del a-hide' title='删除'></a><a onclick='downchoose("+num+")' class='btn-down a-hide'>下移</a><input type='hidden' name='t_"+thisid+"' value='1'/><input type='hidden' class='qx name='sort_"+thisid+"' value='"+thisid+"'/></div></div>";
			if(num<=50){
				if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,1);
				}else{
					muladnum=muladnum-1
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,1);
				}
				$(".global-input2-choose").slideDown(300);
			}else{
				alert("最多提50个问题")
			};
			num=num+1;
	});
	$("#addmul2").click(function(){
		var muladnum=$(".addmulad2").length;
		var thisid =num+1;
			addcon="<div id='addmuladb"+num+"' class='global-input2-choose addmulad2'><div class='title'><p>多<br />选<br />题</p></div><div class='input'><p><input name='q_"+thisid+"' type='text' id='page_title1'  class='validate[required] previewbmtext textbox w280 mt10'/></p><p class='mul2 mt10 rel'><input type='checkbox' class='bmcheckbox' name='c_"+thisid+"[]' value='1'><input name='a_"+thisid+"[]' id='a_1_"+thisid+"' type='text' class='validate[required] previewbmtext textbox w260'/></p><p class='mul2 mt5 rel'><input type='checkbox' class='bmcheckbox' name='c_"+thisid+"[]' value='2'><input name='a_"+thisid+"[]' id='a_2_"+thisid+"' type='text' class='validate[required] previewbmtext textbox w260'/></p><p class='mul2 mt5 rel'><input type='checkbox' class='bmcheckbox' name='c_"+thisid+"[]' value='3'><input name='a_"+thisid+"[]' id='a_3_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><p class='mul2 mt5 rel'><input type='checkbox' class='bmcheckbox' name='c_"+thisid+"[]' value='4'><input name='a_"+thisid+"[]' id='a_4_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><p class='mul2 mt5 rel'><input type='checkbox' class='bmcheckbox' name='c_"+thisid+"[]' value='5'><input name='a_"+thisid+"[]' id='a_5_"+thisid+"' type='text' class='textbox previewbmtext w260'/></p><a href='javascript:void(0)' class='input-addone fn' id='addone_"+thisid+"' data-type='addmu12' data-num='5' data-rel='"+thisid+"'><i></i>增加1条答案（最多50条）</span></a></div><div class='del'><a class='btn-up a-hide' onclick='upchoose("+num+")'>上移</a><a onclick='delchoose(addmuladb"+num+","+num+")' class='btn-del a-hide' title='删除'></a><a class='btn-down a-hide' onclick='downchoose("+num+")'>下移</a><input type='hidden'  name='t_"+thisid+"' value='2'/><input type='hidden' class='qx' name='sort_"+thisid+"' value='"+thisid+"'/></div></div>";
			if(num<=50){
				if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,2);
				}else{
					muladnum=muladnum-1
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,2);
				}
				$(".global-input2-choose").slideDown(300);
			}else{
				alert("最多提50个问题")
			};
			num=num+1;
	});
	
	$(".input-addone").live("click",function(){
		var thisid = $(this).attr("data-rel");
		var datanum = $(this).attr("data-num");
		var datatype = $(this).attr("data-type");
		datanum = ++datanum;
		if(datatype == 'addmu11')
			var addone = '<p class="mul1 mt5 rel"><input type="radio" name="c_'+thisid+'[]" value="'+datanum+'" class="bmradio"><input name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'" type="text" class="textbox previewbmtext w260"></p>',
				addli = '<li><input type="radio" class="PreviewBm-sOne-Radio" name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'"><label class="chosea_a_'+datanum+'_'+thisid+'">为空则不显示</label></li>';
		else if(datatype == 'addmu12')
			var addone = '<p class="mul1 mt5 rel"><input type="checkbox" name="c_'+thisid+'[]" value="'+datanum+'" class="bmcheckbox"><input name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'" type="text" class="textbox previewbmtext w260"></p>',
				addli = '<li><input type="checkbox" class="PreviewBm-sOne-Radio" name="a_'+thisid+'[]" id="a_'+datanum+'_'+thisid+'"><label class="chosea_a_'+datanum+'_'+thisid+'">为空则不显示</label></li>';
		$(this).before(addone);
		$("#choseq_"+thisid).append(addli);
		$(this).attr('data-num',datanum);
	});
	
	$("#addmul3").click(function(){
		var muladnum=$(".addmulad3").length;
		var thisid =num+1;
			addcon="<div id='addmuladc"+num+"' class='global-input2-choose addmulad3'><div class='title'><p>问<br />答<br />题</p></div><div class='input'><p><input name='q_"+thisid+"' type='text' id='page_title1'  class='validate[required] previewbmtext textbox w280 mt10'/></p><p class='mul3 mt5'><textarea readonly class='texttextarea w260 h126'></textarea></p></div><div class='del'><a class='btn-up a-hide' onclick='upchoose("+num+")'>上移</a><a onclick='delchoose(addmuladc"+num+","+num+")' class='btn-del a-hide' title='删除'></a><a class='btn-down a-hide' onclick='downchoose("+num+")'>下移</a><input type='hidden' name='t_"+thisid+"' value='3'/><input type='hidden' class='qx' name='sort_"+thisid+"' value='"+thisid+"'/></div></div>";
			if(num<=50){
				if(muladnum==0){
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,3);
				}else{
					muladnum=muladnum-1
					$("#addmulad").after(addcon);
					PreviewbmAddpage(muladnum,num,thisid,3);
				}
				$(".global-input2-choose").slideDown(300);
			}else{
				alert("最多提50个问题")
			};
			num=num+1;
	});
	$(".btn-down").live("click",function(){
		$(this).closest(".global-input2-choose").before($(this).closest(".global-input2-choose").next());
	});
	$(".btn-up").live("click",function(){
		if($(this).closest(".global-input2-choose").index()<=4){
			alert("已经置顶");
		}else{
			$(this).closest(".global-input2-choose").after($(this).closest(".global-input2-choose").prev());
		}
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
		$("#iphonePreview-four #choseq_"+num).slideUp(300,function(){$("#iphonePreview-four #choseq_"+num).remove();
		$("#iphonePreview-four hr").show();
		$("#iphonePreview-four hr:eq(0)").hide();
		});
}

function downchoose(id){
	var id=id+1;
	$("#iphonePreview-one #choseq_"+id).after($("#iphonePreview-one #choseq_"+id).prev());
	$("#iphonePreview-two #choseq_"+id).after($("#iphonePreview-two #choseq_"+id).prev());
	$("#iphonePreview-four #choseq_"+id).after($("#iphonePreview-four #choseq_"+id).prev());
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$("#iphonePreview-four hr").show();
	$("#iphonePreview-four hr:eq(0)").hide();
}
function upchoose(id){
	var id=id+1;
	$("#iphonePreview-one #choseq_"+id).before($("#iphonePreview-one #choseq_"+id).next());
	$("#iphonePreview-two #choseq_"+id).before($("#iphonePreview-two #choseq_"+id).next());
	$("#iphonePreview-four #choseq_"+id).before($("#iphonePreview-four #choseq_"+id).next());
	$("#iphonePreview-one hr").show();
	$("#iphonePreview-one hr:eq(0)").hide();
	$("#iphonePreview-two hr").show();
	$("#iphonePreview-two hr:eq(0)").hide();
	$("#iphonePreview-four hr").show();
	$("#iphonePreview-four hr:eq(0)").hide();
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
			$("#iphonePreview-four .Preview-share").show();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_main").click(function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");
		$("#page_style").val($(this).attr("title"));
		var shownum =$(this).index()-1;
		var moveleft = shownum*250;
		$("#iphonePreview-con").animate({marginLeft:-moveleft},300);
		if($(this).attr("title")==4){
			$(".set_bg_img:eq("+shownum+")").click();
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
			$("#iphonePreview-four .Preview-share").show();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_main").click(function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");
		$("#page_style").val($(this).attr("title"));
		var shownum =$(this).index()-1;
		var moveleft = shownum*250;
		$("#iphonePreview-con").animate({marginLeft:-moveleft},300);
		if($(this).attr("title")==4){
			$(".set_bg_img:eq("+shownum+")").click();
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
			$("#iphonePreview-four .Preview-base-"+wapcheck).show();
		}else{
			$("#iphonePreview-one .Preview-base-"+wapcheck).hide();
			$("#iphonePreview-two .Preview-base-"+wapcheck).hide();
			$("#iphonePreview-four .Preview-base-"+wapcheck).hide();
		}
	});
	$(".wap_check_title").keyup(function(){
		var name=$(this).attr("name");
		if(name=="defined10"){
			$("#iphonePreview-two .Preview-base-10 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-10 p").text($(this).val()+"：");
			$("#iphonePreview-four .Preview-base-10 p").text($(this).val()+"：");
		}else if(name=="defined11"){
			$("#iphonePreview-two .Preview-base-11 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-11 p").text($(this).val()+"：");
			$("#iphonePreview-four .Preview-base-11 p").text($(this).val()+"：");
		}else{
			$("#iphonePreview-two .Preview-base-12 p").text($(this).val()+"：");
			$("#iphonePreview-one .Preview-base-12 p").text($(this).val()+"：");	
			$("#iphonePreview-four .Preview-base-12 p").text($(this).val()+"：");			
		}
	});
	$(".previewbmtext").live("keyup",function(){
		var val=$(this).val();
		var nameid=$(this).attr("name");
			chooseid=$(this).attr("id");
		$("#chose"+nameid+" h3 span").text(val);
		$("#iphonePreview-one .chosea_"+chooseid).text(val);
		$("#iphonePreview-two .chosea_"+chooseid).text(val);
		$("#iphonePreview-four .chosea_"+chooseid).text(val);
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
	$("#iphonePreview-four hr").show();
	$("#iphonePreview-four hr:eq(0)").hide();
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
			$("#iphonePreview-four .Preview-share").show();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").show();
		}else{
			$("#iphonePreview-one .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-two .Preview-share li:eq("+sns_type+")").hide();
			$("#iphonePreview-four .Preview-share li:eq("+sns_type+")").hide();
		}
	});
	$(".set_main").click(function(){
		$(".set_main").removeClass("set_main_hover");
		$(this).addClass("set_main_hover");

		$("#page_style").val($(this).attr("title"));
		var shownum =$(this).index()-1;
		var moveleft = shownum*250;
		$("#iphonePreview-con").animate({marginLeft:-moveleft},300);
		if($(this).attr("title")==4){
			$(".set_bg_img:eq("+shownum+")").click();
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
			$("#iphonePreview-four #PreviewBm-field1").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field1").hide();
			$("#iphonePreview-two #PreviewBm-field1").hide();
			$("#iphonePreview-four #PreviewBm-field1").hide();
		}
	});
	$("input[name='field_birthday']").click(function(){
		if($(this).attr("checked")){
			$("#iphonePreview-one #PreviewBm-field2").show();
			$("#iphonePreview-two #PreviewBm-field2").show();
			$("#iphonePreview-four #PreviewBm-field2").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field2").hide();
			$("#iphonePreview-two #PreviewBm-field2").hide();
			$("#iphonePreview-four #PreviewBm-field2").hide();
		}
	});
	$("input[name='field_sex']").click(function(){
		if($(this).attr("checked")){
			$("#iphonePreview-one #PreviewBm-field3").show();
			$("#iphonePreview-two #PreviewBm-field3").show();
			$("#iphonePreview-four #PreviewBm-field3").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field3").hide();
			$("#iphonePreview-two #PreviewBm-field3").hide();
			$("#iphonePreview-four #PreviewBm-field3").hide();
		}
	});
	$("input[name='field_name_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s1").show();
			$("#iphonePreview-two #PreviewBm-field-s1").show();
			$("#iphonePreview-four #PreviewBm-field-s1").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s1").hide();
			$("#iphonePreview-two #PreviewBm-field-s1").hide();
			$("#iphonePreview-four #PreviewBm-field-s1").hide();
		}
	});
	$("input[name='field_birthday_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s2").show();
			$("#iphonePreview-two #PreviewBm-field-s2").show();
			$("#iphonePreview-four #PreviewBm-field-s2").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s2").hide();
			$("#iphonePreview-two #PreviewBm-field-s2").hide();
			$("#iphonePreview-four #PreviewBm-field-s2").hide();
		}
	});
	$("input[name='field_sex_p']").click(function(){
		if($(this).val()==1){
			$("#iphonePreview-one #PreviewBm-field-s3").show();
			$("#iphonePreview-two #PreviewBm-field-s3").show();
			$("#iphonePreview-four #PreviewBm-field-s3").show();
		}else{
			$("#iphonePreview-one #PreviewBm-field-s3").hide();
			$("#iphonePreview-two #PreviewBm-field-s3").hide();
			$("#iphonePreview-four #PreviewBm-field-s3").hide();
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