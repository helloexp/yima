$(document).ready(function(e){
	
	
	//可拖动的模块
	$( ".config_menu dd" ).draggable({
		cursorAt: { cursor:'move', top:19, left:44},
		appendTo: '.m-bind',
		helper: "clone",
		opacity:0.8,
		connectToSortable: ".shopBoxView"
	});
	
	$( ".config_menu" ).on("click","dd",function(){
		var shopMod = $(this).attr("data-add");
		var needAdd = true;
		$('.m-bind .shopMod').each(function(){
			if ($(this).attr('data-special') == shopMod) {
				needAdd = false;
			}
		});
		if (needAdd) {
			var dataPro = {initialise:true,id:shopMod,is_neccessary:1};
			//alert(JSON.stringify(dataPro));
			var n = parseInt(shopMod);
			var addHtml = "data"+gethtml(n);
			var editHtml = "edit"+gethtml(n);
			var Modhtml = template(addHtml, dataPro);
			var Formhtml = template(editHtml, dataPro);
			if(Formhtml=="{Template Error}"){return false;}
			$(".m-bind").append(Modhtml);
			$(".shopBoxFormCon").append(Formhtml);
			editclick($("#shopMod_"+shopMod));
			checkeditHtml(editHtml);//判断是否需要绑定排序
		}
		
	});
	//接受拖动
	$( ".shopBoxView" ).droppable({
		accept: ":not(.ui-sortable-helper)",//放置对象
		handle:".module-move",
		deactivate: function(event, ui) {
			var dataPro = {initialise:false,id:shopMod};
			var n = parseInt($(this).find("dd.config_menudd").attr("data-add"));
			var addHtml = "data"+gethtml(n);
			var editHtml = "edit"+gethtml(n);
			var Modhtml = template(addHtml, dataPro);
			var Formhtml = template(editHtml, dataPro);
			if(Formhtml=="{Template Error}"){return false;}
				$(this).find(".module-temp").before(Modhtml);
				$(".shopBoxFormCon").append(Formhtml);
				editclick($("#shopMod_"+shopMod));
				$(this).find("dd.config_menudd").remove();
				checkeditHtml(editHtml);//判断是否需要绑定排序
				
			shopMod++;
		}  
	}).sortable({
		items: ".shopMod:not(.disabled)",
		cursorAt: { cursor:'move', top:10, left:160},
		revert:200,
		distance: 5,
		opacity: 0.7,
		axis:"y",
		handle:".module-move",
		appendTo: ".shopBoxView",
		placeholder: "module-temp",
		tolerance:"pointer"
	});
	
	//新校验字符长度
	$(".shopBoxFormCon").on("keyup","input,textarea",function(){
		var maxLength = $(this).next("span.maxTips").attr("data-max");
		var text = $(this).is('div') ? $(this).html() : $(this).val();
		if(text==""){text = $(this).text()};
		if(!maxLength){return false;}
		if(text.length <= maxLength){
			$(this).next("span.maxTips").removeClass("erro").html(text.length+"/"+maxLength);
		}else{
			$(this).next("span.maxTips").addClass("erro").html(text.length+"/"+maxLength);
		}
	})
	
});

function begin(data, need){
	for(var i=0;i<data.module.length;i++){
		var dataPro = data.module[i];
			//data.module[i].id = i+1 ;
		$.each(dataPro,function(x,item){
			if (x != "list") {
				var dataHtml = "data" + x;
				dataHtml = template(dataHtml, item);
				var editHtml = "edit" + x;
				editHtml = template(editHtml, item);
				if (dataHtml=="{Template Error}") {
					return false;
				} else {
					$(".shopBoxFormCon").append(editHtml);
					$(".m-bind").append(dataHtml);
				}
			}
		});
//		var Modhtml = template(addHtml, dataPro);
//		var Formhtml = template(editHtml, dataPro);
//		if(Formhtml=="{Template Error}"){return false;}
//			$(".shopBoxView").append(Modhtml);
//			$(".shopBoxFormCon").append(Formhtml);

//			checkeditHtml(editHtml);//判断是否需要绑定排序
			
//		shopMod=$(".shopMod").length+1;
	}
	//editclick($("#shopMod_0"));
	editclick($("#shopMod_" + need));
}

function updataJson(){
		var length = $(".shopBoxView .shopMod").length;
		if(length==0){
			alert("请先配置页面信息！");
			return false;
		}		
		var pic_str='';
		var serNum = 0;//显示的顺序号
		var data,list,dataPro ;
		var list = "[";
		$(".shopBoxView .shopMod").each(function(){
			var _this = $(this);
			var id = _this.attr("id");
			var _edit = _this.attr("data-special");
			var _thisEdit = $("[data-id='"+id+"']");
			//页面信息
			if(_edit == 1 ){
				if(list!="["){
					list+=",";
				}
				list+='{"cellPhone":"1","serNum":"'+serNum+'"}';
			}
			if(_edit == 2 ){
				var memberName = _thisEdit.find("[name^='memberName']:checked").val();
				if(list!="["){
					list+=",";
				}
				list+='{"memberName":"'+memberName+'","serNum":"'+serNum+'"}';
			}
			if(_edit == 3 ){
				var gender = _thisEdit.find("[name^='gender']:checked").val();
				if(list!="["){
					list+=",";
				}
				list+='{"gender":"'+gender+'","serNum":"'+serNum+'"}';
			}
			if(_edit == 4 ){
				var birthday = _thisEdit.find("[name^='birthday']:checked").val();
				if(list!="["){
					list+=",";
				}
				list+='{"birthday":"'+birthday+'","serNum":"'+serNum+'"}';
			}
			if(_edit == 5){
				var area = _thisEdit.find("[name^='area']:checked").val();
				if(list!="["){
					list+=",";
				}
				list+='{"area":"'+area+'","serNum":"'+serNum+'"}';
			}
			serNum++;
		});
		list += "]";
		return list;
}

function EditImg(opt){
	var type = opt.type;
	var _this = opt.obj;
	var url = opt.src;
	var savename = opt.savename;
	if(type=="upActImg"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find("ul");
		_this.next("img").attr("src",url).show();
		_shopMod.find("img").attr("src",url).show();
	}else if(type=="upBannerImg"){
		var index = _this.closest(".shopFormBanner").index();
		var _thisclosest = _this.find("img");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find("ul");
		_thisclosest.attr("src",url).show();
		_shopMod.find("li:eq("+index+")").find("img").attr("src",url).show();
	}else if(type=="upMod1Bg"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find(".bg");
		_this.next("img").attr("src",url).show();
		_shopMod.css("background-image","url("+url+")");
	}else if(type=="upMarketImg"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find("ul");
		_this.next("img").attr("src",url).show();
		_shopMod.find("img").attr("src",url).show();
	}else if(type=="upShare"){
		$(".share_pic_val").val(savename);
        $(".share_pic_show").attr("src", url).show();
	}else if(type=="upLogo"){
		$(".logo_pic_val").val(savename);
        $(".logo_pic_show").attr("src", url).show();
		$(".model_pic_show").attr("src", url).show();
	}else{
		alert("未找到类型");
		return false;
	}
}
function editclick(_this){
	if(_this.hasClass("disbaled")){return false;}
	var edit = _this.attr("data-edit") ? _this.attr("data-edit") :"";
	var special = _this.attr("data-special") ? _this.attr("data-special") :"";
		$(".shopMod.hover").removeClass("hover");
		_this.addClass("hover");
		$(".editshopBoxForm").hide();
		if (edit == "") {
			$(".shopBoxFormCon").hide();
		} else {
			$(".shopBoxFormCon").show();
		}
		$(".shopBoxForm-"+edit).show();
		if(edit==0){
			$(".shopBoxFormCon").animate({marginTop:348},500);
		}else{
			var top = _this.position().top;
			if(top<=0){
				$('.shopBoxFormCon').animate(stop);
				$(".shopBoxFormCon").animate({marginTop:295},500);
			}else{
				top = top+295;
				$('.shopBoxFormCon').animate(stop);
				$(".shopBoxFormCon").animate({marginTop:top},500);
			}
		};
}
function getModId(name) {
	var n = 1;
	if (name == "cellPhone") {
		n = 1;
	} else if (name == "memberName") {
		n = 2;
	} else if (name == "gender") {
		n = 3;
	} else if (name == "birthday") {
		n = 4;
	} else if (name == "area") {
		n = 5;
	}
	return n;
}

function gethtml(n){
	switch(n){
		case 1:
		  return "cellPhone" ;
		  break;
		case 2:
		  return "memberName" ;
		  break;
		case 3:
		  return "gender" ;
		  break;
		case 4:
		  return "birthday" ;
		  break;
		case 5:
		  return "area" ;
		  break;
//		case 6:
//		  return "Radio" ;
//		  break;
//		case 7:
//		  return "CheckBox" ;
//		  break;
//		case 8:
//		  return "QQ" ;
//		  break;
//		case 9:
//		  return "Email" ;
//		  break;
//		case 10:
//		  return "Line" ;
//		  break;
//		case 11:
//		  return "Margin" ;
//		  break;
//		case 12:
//		  return "Market" ;
//		  break;
	}
}
function checkeditHtml(editHtml){
	if(editHtml=="editUeditor"){
	  var ue = UE.getEditor('wap_info'+shopMod,{
			imageUrl:"{:U('LabelAdmin/Upfile/editoImageSave')}",
			imagePath:"<?php echo C('UPLOAD')?>",
			catcherUrl:"{:U('LabelAdmin/Upfile/getRemoteImage')}",
			catcherPath:"<?php echo C('UPLOAD')?>",
			initialStyle:'p{line-height:20px; font-size: 14px; }',
			initialFrameWidth:410,
			initialFrameHeight:280
		});
		//富文本
		ue.addListener("contentChange",function(){
			var ueid = this.textarea.name;
			$(".shopMod").find("."+ueid).html(this.getContent());			
		});
	}
	
	if(editHtml=="editPic"){
		$(".sortablePic").sortable({
			items: ".shopFormBanner",
			tolerance:"pointer"
		});
	}
	if(editHtml=="editPro"){
		$(".sortablePro").sortable({
			items: ".view-upPro",
			tolerance:"pointer"
		});
	}
	if(editHtml=="editNavPic"){
		$(".sortableNavPic").sortable({
			items: ".shopFormBanner"
		});
	}
	if(editHtml=="editNavTxt"){
		$(".sortableNavTxt").sortable({
			items: ".shopFormBanner"
		});
	}
	if(editHtml=="editHotrecommend"){
		$(".sortableHotRecommend").sortable({
			items: ".shopFormBanner"
		});
	}
}

$(document).ready(function(e){
	
	//链接编辑与预览
	$(".shopBoxCon").on("click",".shopBoxCon-title,.shopMod",function(){
		var _this = $(this);
		editclick(_this);
	})
	
	//轮播添加
	$(".shopBoxFormCon").on("click",".add-shopFormBanner",function(){
		var _shopMod = $("#"+$(this).closest(".editshopBoxForm").attr("data-id")).find("ul");
		var upProhtml = template("editIncludePic");
		var shopModhtml = template("dataIncludePic");
			$(this).before(upProhtml);
			_shopMod.append(shopModhtml);
	});
	
	//文本添加
	$(".shopBoxFormCon").on("click",".add-shopFormTxt",function(){
		var _shopMod = $("#"+$(this).closest(".editshopBoxForm").attr("data-id")).find("ul");
		var upProhtml = template("editIncludeNavTxt");
		var shopModhtml = template("dataIncludeNavTxt");
			$(this).before(upProhtml);
			_shopMod.append(shopModhtml);
	});
	
	//爆款销售-新品推荐
	$(".shopBoxFormCon").on("click",".add-HotRecommend",function(){
		var _shopMod = $("#"+$(this).closest(".editshopBoxForm").attr("data-id")).find("ul");
		var checkNum = {list:checkRadiuslength+1}
		var upProhtml = template("editIncludeHotrecommend",checkNum);
		var shopModhtml = template("dataIncludeHotrecommend",checkNum);
			$(this).before(upProhtml);
			_shopMod.append(shopModhtml);
			Hotrecommendt.reInit();
			checkRadiuslength++;
	});
	
	
	//大部分修改预览
	$(".shopBoxFormCon").on("change keyup","[name='page_title'],[name^='checkproPic'],[name='checkproBtn'],[name='checkproName'],[name='checkproPrice'],[name^='checkProListNum'],[name='checkActTitle'],[name='checkActTime'],[name^='checkPicType'],[name^='checkNavPicNum'],[name='NavTxtTitle'],[name='BannerTitle'],[name='TitleTitle'],[name='Name'],[name='TitleDescription'],[name^='checkTextalign'],[name^='checkTextLine'],[name^='checkLine'],[name^='checkMargin'],[name^='checkMarket'],[name^='BannerDescription'],[name^='checkPositionX'],[name^='checkPositionY'],[name^='checkRadius']",function(){
		var _this = $(this);
		var _thisclosest = _this.closest(".shopForm");
		var type = _this.attr("name").replace(/[^A-Za-z]/g,"");
		var val = _this.val();
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find("ul");
		var ArryTextalign = ["0","tl","tc","tr"];
		var ArryTextLine = ["0","","ln1","ln2","ln3"];
		var ArryLine = ["0","shopModBorder-1","shopModBorder-2","shopModBorder-3"];
		var ArryMargin = ["0","shopModNone-1","shopModNone-2","shopModNone-3","shopModNone-4"];
		var ArryMarket = ["0","shopModNone-1","shopModNone-2","shopModNone-3","shopModNone-4"];
		var ArryNavPicNum = ["0","0","0","shopMod-3","shopMod-4","shopMod-5"];
		var ArryPicType = ["0","shopModFlash","","shopModSmall"];
		var ArryproPic = ["0","big","small","","list"];
		
		if(type=="TitleTitle"){
			_shopMod = _shopMod.find("h2").text(val);
		}else if (type=="TitleDescription"){
			_shopMod = _shopMod.find("h3").text(val);
		}else if(type=="checkTextalign"){
			_shopMod.find("li a").attr("class",ArryTextalign[val]);
		}else if (type=="checkTextLine"){
			_shopMod.find("dd").attr("class",ArryTextLine[val]);
		}else if(type=="checkLine"){
			_shopMod.attr("class",ArryLine[val]);
		}else if (type=="checkMargin"){
			_shopMod.attr("class",ArryMargin[val]);
		}else if (type=="checkMarket"){
			_shopMod.attr("class",ArryMarket[val]);
			var Marketimg = _thisclosest.find("[name='img']").attr("src").indexOf("icon-Marketing");
			if(val==1){
				if(Marketimg!=-1){
					_thisclosest.find("[name='img']").attr("src","./Home/Public/Label/Image/icon-Marketing"+val+".png");
					_shopMod.find("img").attr("src","./Home/Public/Label/Image/icon-Marketing"+val+".png");
				}
			}else{
				if(Marketimg!=-1){
					_thisclosest.find("[name='img']").attr("src","./Home/Public/Label/Image/icon-Marketing"+val+".png");
					_shopMod.find("img").attr("src","./Home/Public/Label/Image/icon-Marketing"+val+".png");
				}
			}
		}else if (type=="NavTxtTitle"){
			var index = $(this).closest(".shopFormBanner").index();
			if(val==""){
				_shopMod.find("li:eq("+index+") h2").text("导航名称");
			}else{
				_shopMod.find("li:eq("+index+") h2").text(val);
			}
		}else if (type=="BannerTitle"){
			var index = $(this).closest(".shopFormBanner").index();
			if(val==""){
				_shopMod.find("li:eq("+index+") h2").text("");
			}else{
				_shopMod.find("li:eq("+index+") h2").text(val);
			}
		}else if(type=="checkNavPicNum"){
			var upProhtml = template("editIncludePic");
			var shopModhtml = template("dataIncludeNavPic");
			var nowNum =parseInt(_this.val()) - _thisclosest.find(".shopFormBanner").length;
			if(nowNum>0){
				for(var i=0;i<nowNum;i++){
					_thisclosest.find(".shopFormI.noName").append(upProhtml);
					_shopMod.append(shopModhtml);
				}
			}
			_thisclosest.find(".shopFormBanner").hide();
			_shopMod.find("dd").hide();
			_shopMod.removeClass().addClass("shopMod-"+_this.val());
			for(var i=0;i<_this.val();i++){
				_thisclosest.find(".shopFormBanner:eq("+i+")").show();
				_shopMod.find("li:eq("+i+")").show();
			}
		}else if(type=="checkPicType"){
			_shopMod.closest(".shopModFlashDiv").attr("class","shopModFlashDiv "+ArryPicType[val]);
		}else if(type=="checkActTitle" || type=="checkActTime" || type=="checkproBtn" || type=="checkproName" || type=="checkproPrice" ){
			var val = $(this).prop("checked");
			if(type=="checkActTitle"){
				var timeval = $(this).closest(".shopFormI").find("[name^='checkActTime']").prop("checked");
				_h = _shopMod.find("h2");
			}else if(type=="checkActTime"){
				var timeval = $(this).prop("checked");
				_h = _shopMod.find("h3");
			}else if(type=="checkproBtn"){
				_h = _shopMod.find("h6");
			}else if(type=="checkproName"){
				_h = _shopMod.find("h3");
			}else if(type=="checkproPrice"){
				_h = _shopMod.find("h4");
			};
			if(val){
				_shopMod.find("h2").removeClass("no");
				_h.show();
			}else{
				_shopMod.find("h2").addClass("no");
				_h.hide();
			}
			if(timeval){
				_shopMod.find("h2").removeClass("no");
			}else{
				_shopMod.find("h2").addClass("no");
			}
		}else if(type=="checkproPic"){
			_thisclosest.find(".con a").attr("data-size",ArryproPic[val]);
			_shopMod.find("dd").attr("class",ArryproPic[val]);
			var nowproLength = _shopMod.find("dd").length;
			if (val == 3){
				for(var i=0;i<=nowproLength;i++){
					if( i%3 == 0){
						_thisclosest.find(".con a:eq("+i+")").attr("data-size",ArryproPic[1]);
						_shopMod.find("li:eq("+i+")").attr("class",ArryproPic[1]);
					}else{
						_thisclosest.find(".con a:eq("+i+")").attr("data-size",ArryproPic[2]);
						_shopMod.find("li:eq("+i+")").attr("class",ArryproPic[2]);
					}
				}
			}
		}else if(type=="pagetitle"){
			if(val!=""){
				$(".shopBoxCon-title span").text(val);
			}else{
				$(".shopBoxCon-title span").text("单页设计");
			}
		}else if(type=="checkProListNum"){
			_shopMod.find("dd").hide();
			for (var i=0;i<val;i++) {
				_shopMod.find("li:eq("+i+")").show();
			};
		}else if(type=="BannerDescription"){
			var index = $(this).closest(".shopFormBanner").index();
				if(val==""){
					_shopMod.find("li:eq("+index+") h3").hide();
				}else{
					val = val.replace(/\n/g,"<br>");
					_shopMod.find("li:eq("+index+") h3").html(val);
					_shopMod.find("li:eq("+index+") h3").show();
					if(_this.closest(".shopFormBanner").find("[name='checkRadius']:checked").val()==1){
						var width = parseInt(_shopMod.find("li:eq("+index+") .radio").width());
						_shopMod.find("li:eq("+index+") .radio").height(width)
					}
				}
		}else if(type=="checkPositionX"){
			var index = $(this).closest(".shopFormBanner").index();
				if(val>=50){
					var width = parseInt(_shopMod.find("li:eq("+index+") div").width());
					_shopMod.find("li:eq("+index+") div").css("left",val+"%");
					_shopMod.find("li:eq("+index+") div").css("margin-left",-width);
				}else{
					_shopMod.find("li:eq("+index+") div").css("margin-left",0);
					_shopMod.find("li:eq("+index+") div").css("left",val+"%");
				}
		}else if(type=="checkPositionY"){
			var index = $(this).closest(".shopFormBanner").index();
				if(val>=50){
					var height = parseInt(_shopMod.find("li:eq("+index+") div").height());
					_shopMod.find("li:eq("+index+") div").css("top",val+"%");
					_shopMod.find("li:eq("+index+") div").css("margin-top",-height);
				}else{
					_shopMod.find("li:eq("+index+") div").css("margin-top",0);
					_shopMod.find("li:eq("+index+") div").css("top",val+"%");
				}
		}else if(type=="checkRadius"){
			var index = $(this).closest(".shopFormBanner").index();
			var classArea = ["text","radio","radius"];
			_shopMod.find("li:eq("+index+") div").attr("class",classArea[val]);
			if(val==1){
				var width = parseInt(_shopMod.find("li:eq("+index+") .radio").width());
					_shopMod.find("li:eq("+index+") .radio").height(width);
			}
		}
	});
	
	//大部分删除
	//删除模块
	$(".shopBoxCon").on("click",".module-del",function(){
		var previd = $(this).closest(".shopMod").prev(".shopMod").attr("id");
		var id = $(this).closest(".shopMod").attr("id");
		if(!previd){ previd = "shopMod_0";};
		editclick($("#"+previd));
		$("[data-id='"+id+"']").remove();
		$(this).closest(".shopMod").remove();
		e.stopPropagation();
	});
	//商品删除
	$(".shopBoxFormCon").on("click",".view-upPro i",function(){
		var index = $(this).closest(".view-upPro").index();
		var _shopMod = $("#"+$(this).closest(".editshopBoxForm").attr("data-id")).find("ul");
		$(this).closest(".view-upPro").remove();
		_shopMod.find("li:eq("+index+")").remove();
		var nowproLength = _shopMod.find("dd").length;
		for(var i=0;i<=nowproLength;i++){
			if( i%3 == 0){
				_shopMod.find("li:eq("+i+")").removeClass("big").removeClass("small").addClass("big");
			}else{
				_shopMod.find("li:eq("+i+")").removeClass("big").removeClass("small").addClass("small");				
			}
		}
		if(_shopMod.find("dd").length==0){
			var upPro = {list:[{id:"",img:"",title:"此处显示商品名称",price:"100.00",checkproPic:3,checkproBtn:1,checkproName:1,checkproPrice:1,size:"big"},{id:"",img:"",title:"此处显示商品名称",price:"100.00",checkproPic:3,checkproBtn:1,checkproName:1,checkproPrice:1,size:"small"},{id:"",img:"",title:"此处显示商品名称",price:"100.00",checkproPic:3,checkproBtn:1,checkproName:1,checkproPrice:1,size:"small"}]};
			var shopModhtml = template("dataIncludePro", upPro);
			_shopMod.append(shopModhtml);
			_shopMod.attr("data-begin","");
		}
	});
	//轮播删除
	$(".shopBoxFormCon").on("click",".del-shopFormBanner",function(){
		var index = $(this).closest(".shopFormBanner").index();
		var _shopMod = $("#"+$(this).closest(".editshopBoxForm").attr("data-id")).find("ul");
		var nowproLength = $(this).closest("li").find(".shopFormBanner").length;
		if(nowproLength>1){
			$(this).closest(".shopFormBanner").remove();
			_shopMod.find("li:eq("+index+")").remove();
			if($(this).hasClass("del-HotRecommend")){
				$(".shopModFlashDiv ").find("ul").css("-webkit-transform","translate3d(0, 0, 0)");
				Hotrecommendt.reInit();
			}
		}else{
			$(this).closest(".shopFormBanner").addClass("shopFormBannerAn");
			setTimeout(function(){
				$(".shopFormBannerAn").removeClass("shopFormBannerAn")
			},600);
		}
	});
});