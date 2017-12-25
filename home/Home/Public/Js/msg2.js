function addAppmsg(){
	$("#addAppmsg").hoverDelay(
	function(){
		$("#addAppmsg").addClass("add-btn-show");
    },
	function(){
		$("#addAppmsg").removeClass("add-btn-show");
	}
	);
}


function fileNameEditing(){
	$("a.iconEdit").click(function(){
		$(this).closest("li").find(".fileNameArea").toggleClass("fileNameEditing");
    });
}

function editAreaTxt(){
	var maxl=($(".editArea-textarea").attr("maxLength")*1 || 500)*1+1;
	document.onkeyup=function(){
		var s=$(".editArea-textarea").val().length+1;
		if(s>maxl){
			var textarea=$(".editArea-textarea").val().substring(0,maxl-1);
				$(".editArea-textarea").val(textarea);
		}else{
			s=maxl-s
			$(".tip").text("");
			$(".tip").append("还可以输入<span>"+s+"</span>字");
		}
	}
}

function editAreaimg(){
	$(".editArea-choose-list").click(function(){
		$(".editArea-choose-add").removeClass("editArea-choose-add");
		$(this).addClass("editArea-choose-add");
		var name=$(this).find(".editArea-choose-name").text();
			size=$(this).find(".editArea-choose-size").text();
			date=$(this).find(".editArea-choose-date").text();
			src=$(this).find(".editArea-choose-src").attr("src");
			$(".editArea-img-name").removeClass("hide").text(name);
			$(".editArea-img-size").removeClass("hide").text(size);
			$(".editArea-img-date").removeClass("hide").text(date);
			$(".editArea-img-src").removeClass("hide").attr("src",src);
			$(".editArea-input-name").val(name);
			$(".editArea-input-size").val(size);
			$(".editArea-input-date").val(date);
			$(".editArea-input-src").val(src);
    });
}
function showAreaimg(){
	$(".libraryArea-add").click(function(){
		$(".txtImg .editArea").removeClass("hide");
		windowheight();
    });
}


function unfold(){
	$("a.unfold").click(function(){
		$(this).closest("li").addClass("ruleItemEditing");
		windowheight();
    });
}
function pickup(){
	$("a.pickup").click(function(){
		$(this).closest("li").removeClass("ruleItemEditing");
		windowheight();
    });
}
function listItemNew(){
	$(".listItemNew").click(function(){
		$("#listItemNew").toggleClass("none");
		windowheight();
    });
}
function matchMode(){
	$(".matchMode").live("click",function(){
		var matchInput = $(this).closest("li").find(".matchMode-input");
		var matchModelVal = matchInput.val() || '0';
		if(matchModelVal == '0'){
			$(this).addClass("matchMode1");
			$(this).text("已全匹配");
			matchInput.val("1");
		}else{
			$(this).removeClass("matchMode1");
			$(this).text("全匹配");
			matchInput.val("0");
		}
    }).each(function(){
		var matchInput = $(this).closest("li").find(".matchMode-input");
		var matchModelVal = matchInput.val() || '0';
		if(matchModelVal == '1'){
			$(this).addClass("matchMode1");
			$(this).text("已全匹配");
		}else{
			$(this).removeClass("matchMode1");
			$(this).text("全匹配");
		}
	});
}
function Editor(){
	$(".keywordEditor").click(function(){
		$(this).closest("li").find(".val").css("display","none");
		$(this).closest("li").find(".val-input").css("display","block");
    });
	$(".replyEditor").click(function(){
		$(this).closest("li").find(".wordContent").css("display","none");
		$(this).closest("li").find(".wordContent-input").css("display","block");
    });
}

function change(){
	//计算图文数量
	var countInfo = function(obj){
		var txtNum = $(obj).find(".replyItems li.replyWords").length;
		var imgNum = $(obj).find(".replyItems li.replyImages").length;
		$(".wordsCnt",obj).text(txtNum);
		$(".appMsgCnt",obj).text(imgNum);
	}

	$(".delKeyword").live("click",function(){
		var delbox=$(this).closest(".keywordsList").find(".keywordItems").find("li")
			delsize=delbox.length;
		for(i=0;i<delsize;i++){
			if(delbox.eq(i).find(".keywordcheckbox").attr("checked")=="checked"){
				delbox.eq(i).detach();
			}
		}
    });
	$(".delReply").live("click",function(){
		var delbox=$(this).closest(".replyList").find(".replyItems").find("li")
			delsize=delbox.length;
		for(i=0;i<delsize;i++){
			if(delbox.eq(i).find(".replycheckbox").attr("checked")=="checked"){
				delbox.eq(i).detach();
			}
		}
		countInfo($(this).closest('form'));
    });
	$(".addKeyword").click(function(){
		var addid=$(this).closest(".keywordsList").find(".keywordItems").find("li").length;  //写入 name=name+addid
			addbox="<li class='item float-p'><input type='checkBox' class='l keywordcheckbox'><input type='hidden' name='kwdId[]'><input type='text' class='val-input l' value='' name='keywordStr[]'  maxlength='20' jscheckrule='null=0' jschecktitle='关键词'><label class='r c-gA matchMode'>全匹配</label><input type='hidden' class='matchMode-input' value='0'  name='matchMode[]'></li>";
			$(this).closest(".keywordsList").find(".keywordItems").append(addbox);
    });
	$(".addWords").click(function(){
		
		var addid=$(this).closest(".replyList").find(".replyItems").find("li").length;  //写入 name=name+addid
		//if(addid<1){
			addbox="<li class='item float-p replyWords'><input type='checkBox' class='l replycheckbox' name='replycheckbox'><input type='hidden' name='respId[]'><div class='contentWrap l'><div class='wordContent-input'><input type='text' value='' name='wordContent[]'  jscheckrule='null=0' jschecktitle='文字回复'><input type='hidden' name='respClass[]' value='0'><span>添加您需要的文字</span></div></div></li>";
			$(this).closest(".replyList").find(".replyItems").append(addbox);
			countInfo($(this).closest('form'));
		//}else{
		//	alert("最多添加1条信息")
		//}
    });
}

function preview(){
	$(".sub-add-btn").click(function(){
		var addid=$(this).closest(".msg-item").find(".sub-msg-item").length;  //写入 name=name+addid
		if(addid < 7){
			var addbox=$('<div class="rel sub-msg-item appmsgItem"><span class="thumb"> <span style="display: none;" class="default-tip">缩略图</span> <img class="i-img" style="display: block;" src=""> </span> <h5 class="msg-t"> <span class="i-title">默认标题</span> </h5> <div class="form_appmsgItem" style="display:none" ><!--表单  时间根据当前时间载入--> <input value="" id="input_i-id" name="input_i-id" type="text"><!--ID号--> <input value="默认标题" id="input_i-title" name="input_i-title" type="text"><!--标题--> <input value="" id="input_i-material_img" name="input_i-material_img" type="text"><!--图片名--><input type="text" id="input_i-material_imgInsert" name="input_i-material_imgInsert" /><input value="" id="input_i-url" name="input_i-url" jscheckrule="call={checkrule_url:[this]}" jschecktitle="原文链接" type="text"><!--原文链接--> <input value="" id="input_i-summary" name="input_i-summary" type="text"><!--摘要--> <input value="" id="input_i-batch_type" name="input_i-batch_type" type="text"><!--活动类型--> <input value="" id="input_i-batch_id" name="input_i-batch_id" type="text"><!--活动ID--> <input value="" id="input_i-material_desc" name="input_i-material_desc" type="text"><!--活动详情--><input type="text" value="" id="input_i-material_desc_richtxt" name="input_i-material_desc_richtxt" /><!--富文本详情--> <input value=""  name="input_i-url_type" class="input_i-url_type" type="text"><!--类型 0 网址 1 多乐互动 --> </div> <ul class="abs tc sub-msg-opr"> <li class="b-dib sub-msg-opr-item"> <a href="javascript:;" class="icon18 iconEdit">编辑</a> </li> <li class="b-dib sub-msg-opr-item"> <a href="javascript:;" class="icon18 iconDel">删除</a> </li> </ul></div>');
			//var addbox = $(".firstAppmsgItem").eq(0).clone();
			$(this).closest(".sub-add").before(addbox);
			$(".iconEdit",addbox).trigger("click");
		}else{
			alert("最多添加8条信息");
		}
		windowheight();
    });
	$("body").on("click",".iconDel",function(){
		var addid=$(this).closest(".msg-item").find(".sub-msg-item").length;
		//if(addid<=1){
		//	alert("多图文信息必须留一条");
		//}else{
			$(this).closest("div.appmsgItem").prev("div.appmsgItem").find("a.iconEdit").trigger("click");
			$(this).closest(".sub-msg-item").detach();
		//	}
		windowheight();
    });
	$("body").on("click",".iconEdit",function(){
		var addid=$(this).closest(".msg-item").find(".sub-msg-item").length;
			Editid=$(".iconEdit").index(this)+1;
			if(Editid==1){
				$("#msgEditArea").css("margin-top","23px");
			}else{
				$("#msgEditArea").css("margin-top",20+Editid*97+"px");
			}
			iconEdit(Editid);
			windowheight();
    });
	$("body").on("click",".url-link",function(){
		$("#url-block").toggleClass("none");
		$(".url-link").css("display","none");
    });
}
var item_parent;
function iconEdit(Editid){
	g_cur_edit_id = Editid;
	$("#msgEditArea").attr("data-item-index",Editid);
	item_parent = $(".appmsgItem").eq(Editid-1);
	var url_type = item_parent.find("input[name='input_i-url_type']").val();
	//alert(url_type);
	if(url_type==""){
		item_parent.find("input[name='input_i-url_type']").val(1);
	};
	if(url_type.indexOf("FuWenText")>=0){
		item_parent.find("input[name='input_i-url_type']").val(2);
	};
	if(item_parent.find("[name='input_i-id']").val()!=""){
		if(url_type.indexOf("Label&m=Label&a=index&id=")>=0){
			item_parent.find("input[name='input_i-url_type']").val(1);
		};
	}
	
	//初始化标题
	$("#msg-input-title").val(item_parent.find("#input_i-title").val());
	$("#msg-input-url").val(item_parent.find("#input_i-url").val());
	$("#msg-input-imgfile").val(item_parent.find("#input_i-material_img").val())
	$("#msg-input-summary").val(item_parent.find("#input_i-summary").val());
	var coverInsert = item_parent.find("#input_i-material_imgInsert").val();
	if (coverInsert == "1"){
		 $("#appendCover").attr("checked",true); 
	}
	else
	{
		$("#appendCover").attr("checked",false); 
	}

	$("#url-block-choose").html(item_parent.find("#input_i-material_desc").val());
	$("#url-block-href").html(item_parent.find("#input_i-url").val());
	var desc = item_parent.find("input[name='input_i-material_desc_richtxt']").val();
	if (typeof UE !='undefined') {
		if (desc!="") {
			UE.getEditor('wap_info').setContent(desc);
		} else {
			UE.getEditor('wap_info').setContent("");
		}
	}
	
	//初始化事件
	var begintype = item_parent.find("input[name='input_i-url_type']").val()
	if(begintype == '0'){
		$('#add-url-linkText').click();
	}else if(begintype == '1'){
		$('#add-url-linkAct').click();
	}else if(begintype == '2') {
		$('#add-url-linkRichTxt').click();
	}

}
$(document).ready(function(e) {
	$("body").on("change","#appendCover",function(){
		var appendCover = $("#appendCover").attr("checked")?true:false;
		//console.log(appendCover)
		if(appendCover)
		{
			item_parent.find("#input_i-material_imgInsert").val("1");
			//alert(item_parent.find("#input_i-material_imgInsert").val())
		}else
		{
			item_parent.find("#input_i-material_imgInsert").val("0");
			//alert(item_parent.find("#input_i-material_imgInsert").val())
		}
	});
	
	$("body").on("keyup","#msg-input-title",function(){
		item_parent.find(".i-title").text($("#msg-input-title").val());
		item_parent.find("#input_i-title").val($("#msg-input-title").val());
	});
	
	$("body").on("keyup","#msg-input-summary",function(){
		item_parent.find(".msg-text").text($("#msg-input-summary").val());
		item_parent.find("#input_i-summary").val($("#msg-input-summary").val());
	});
	
	$("body").on("change","#msg-input-imgfile",function(){
		item_parent.find(".i-img").attr("src",$("#msg-input-imgfile").val());
		item_parent.find(".i-img").css("display","block");
		item_parent.find(".default-tip").css("display","none");
	});
	
	$("body").on("keyup","#msg-input-url",function(){
		item_parent.find("#input_i-url").val($("#msg-input-url").val());
	});
	
    $('body').on("click","#add-url-linkAct",function(){
		$(".url-text").removeClass("url-hover");
		$(".url-richtxt").removeClass("url-hover");
		$(".url-choose").addClass("url-hover");
		$(".url-block-con2").hide();
		$(".url-block-con1").show();
		$(".url-block-con3").hide();
		item_parent.find("input[name='input_i-url_type']").val(1);
		
		$("#url-block-choose").html(item_parent.find("input[name='input_i-material_desc']").val()+'<input type="hidden" value="'+item_parent.find("input[name='input_i-batch_id']").val()+'" name="batch_id" id="batch_id"/>');
		$("#msg-input-url").val("");
		if(!isMass){
			if (typeof UE != "undefined") {
				UE.getEditor('wap_info').execCommand('cleardoc');
			}
		}
	});
	
    $('body').on("click","#add-url-linkText",function(){
		$(".url-choose").removeClass("url-hover");
		$(".url-richtxt").removeClass("url-hover");
		$(".url-text").addClass("url-hover");
		$(".url-block-con2").show();
		$(".url-block-con1").hide();
		$(".url-block-con3").hide();
		item_parent.find("input[name='input_i-url_type']").val(0);
		
		$("#url-block-choose").text("");
		$("#msg-input-url").val(item_parent.find("input[name='input_i-url']").val());
		if(!isMass){
			if (typeof UE != "undefined") {
				UE.getEditor('wap_info').execCommand('cleardoc');
			}
		}
	});
	
    $('body').on("click","#add-url-linkRichTxt",function(){
		$(".url-choose").removeClass("url-hover");
		$(".url-text").removeClass("url-hover");
		$(".url-richtxt").addClass("url-hover");
		$(".url-block-con3").show();
		$(".url-block-con2").hide();
		$(".url-block-con1").hide();
		item_parent.find("input[name='input_i-url_type']").val(2);
		
		$("#url-block-choose").text("");
		$("#msg-input-url").val("");
		if (typeof UE != 'undefined') {
			UE.getEditor('wap_info').setContent(item_parent.find("input[name='input_i-material_desc_richtxt']").val());
		}
	});

    $('body').on("click","#add-url-link",function(){
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

});

function editArea(){
	$('.libraryArea-add').click(function(){
		var openUrl = $(this).attr("data-url") || 'about:blank';
		art.dialog.open(openUrl,{
			lock: true,
			fixed:true,
			background: '#000', // 背景色
			opacity: 0.5,	// 透明度
			title:"选择图文信息",
			width:740,
			height: '80%'
			});
	});
	$('.addAppMsg').click(function(){
		var openUrl = $(this).attr("data-url") || 'about:blank';
		art.dialog.open(openUrl,{
			id:"addAppMsg",
			lock: true,
			fixed:true,
			background: '#000', // 背景色
			opacity: 0.5,	// 透明度
			title:"选择图文信息",
			width:740,
			height: '80%'
			});
		//给一个全局参数，表示当前打开的编辑窗口是哪个表单
		art.dialog.data("currentForm",$(this).closest("form"));
	});
}
function msglistchoose(){
	$(".msg-item-wrapper").mouseover(function(){
		$(this).find(".msg-hover-mask").css("display","block");
	});
	$(".msg-item-wrapper").click(function(){
		$(".msg-item-wrapper").find(".msg-mask").detach();
		$("#material_id_selected").val($(this).attr("data-material-id"));
		$(this).find(".msg-hover-mask").after("<div class='msg-mask'><span class='msg-selected-tip'></span></div>");
	});
	$(".msg-item-wrapper").mouseout(function(){
		$(this).find(".msg-hover-mask").css("display","none");
	});
}


function msgNavList(){
	$(".add-oneList").click(function(){
		if($(".msg-nav-list-item").length >=3){
			alert("最多只能添加3个一级菜单");
			return false;
		}
    });
	$(".msg-nav-list-one .add").click(function(){
		if($(this).closest(".msg-nav-list-item").find(".msg-nav-list-two li").length >=5){
			alert("最多只能添加5个二级菜单");
			return false;
		}
    });
	$(".icon-order-up").click(function(){
		if($(this).closest(".msg-order-one").index()<0){
			if($(this).closest("li").index()>0){
				$(this).closest("li").after($(this).closest("li").prev());
			}
		}else{
			if($(this).closest(".msg-number").index()>2){
				$(this).closest(".msg-number").after($(this).closest(".msg-number").prev());
			}
		}
    });
	$(".icon-order-down").click(function(){
		if($(this).closest(".msg-order-one").index()<0){
			$(this).closest("li").before($(this).closest("li").next());
		}else{
			if($(this).closest(".msg-number").index()<=$(".msg-order-one").length){
				$(this).closest(".msg-number").before($(this).closest(".msg-number").next());
			}
		}
    });
}

//检查字符串长度
function check_lenght_weixin(total,id,obj){
    var text = $(obj).text();
    var imglength = $(obj).find("img");	
    if(imglength.length*3+text.length <= total){
        $("#"+id).attr("style","").html("还可以输入"+(total-(imglength.length*3+text.length))+"个字");
    }else{
        $("#"+id).attr("style","color:red;").html("已经超出"+(imglength.length*3+text.length-total)+"个字");
    }
}
//检查是否复制，是否回车
function check_lenght_onkeydown(obj){
	if(event.keyCode==13){
		event.preventDefault();
		var faceImg = "<br><br>"
		insertimg(faceImg);
		return false;
	}
	if(event.keyCode==86){
		pasteHandler();
		return false;
	}
}


function insertimg(str){
	var selection= window.getSelection ? window.getSelection() : document.selection;
	var range= selection.createRange ? selection.createRange() : selection.getRangeAt(0);
	if (!window.getSelection){
		$("#reply_content_0").focus();
		var selection= window.getSelection ? window.getSelection() : document.selection;
		var range= selection.createRange ? selection.createRange() : selection.getRangeAt(0);
		range.pasteHTML(str);
		range.collapse(false);
		range.select();
	}else{
		$("#reply_content_0").focus();
		range.collapse(false);
		var hasR = range.createContextualFragment(str);
		var hasR_lastChild = hasR.lastChild;
		/*
		while (hasR_lastChild && hasR_lastChild.nodeName.toLowerCase() == "br" && hasR_lastChild.previousSibling && hasR_lastChild.previousSibling.nodeName.toLowerCase() == "br") {
			var e = hasR_lastChild;
			hasR_lastChild = hasR_lastChild.previousSibling;
			hasR.removeChild(e+e);
		}
		*/
		range.insertNode(hasR);
		if (hasR_lastChild) {
			range.setEndAfter(hasR_lastChild);
			range.setStartAfter(hasR_lastChild);
		}
		selection.removeAllRanges();
		selection.addRange(range)
	}
}
//监控粘贴(ctrl+v),如果是粘贴过来的东东，则替换多余的html代码，只保留<br>
function pasteHandler(){
	setTimeout(function(){
		var content = $("#reply_content_0").html();
		var img = [];
			for(var i=0; i<$("#reply_content_0").find("img").length;i++){
				img.push($("#reply_content_0").find("img:eq("+i+")").attr("src"));
			};
			content = content.replace(/<br?[^>]*>/g,"[[br]]").replace(/<img?[^>]*>/g,"[[img]]").replace(/<\/?[^>]*>/g,"").replace(/\[\[img]]/g,"<img>").replace(/\[\[br]]/g,"<br>");
			$("#reply_content_0").html(content);
			setTimeout(function(){
				for(var i=0; i<$("#reply_content_0").find("img").length;i++){
					$("#reply_content_0").find("img:eq("+i+")").attr("src",img[i]);
				};
				$("#reply_content_0").find("img").each(function() {
                    var src = $(this).attr("src");
					if(src.indexOf("Home/Public/Image/weixin2/emotion/")==-1){
						$(this).remove();
					}
                });
			},10);
	},1)
}

function getHeight(){
	var lh = $('.left').height();
	var rh = $('.right').height();
	
	lh > rh ? $('#main-help').height(lh+120):$('#main-help').height(rh+120);
	
}
