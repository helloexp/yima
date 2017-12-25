$(document).ready(function(e){
	
	 
	//可拖动的模块
	$( ".shopBoxList li" ).draggable({
		cursorAt: { cursor:'move', top:19, left:44},
		appendTo: '.shopBoxList ul',
		helper: "clone",
		opacity:0.8,
		connectToSortable: ".shopBoxView"
	});
	
	$( ".shopBoxList" ).on("click","li",function(){
		var dataPro = {initialise:false,id:shopMod};
		var n = parseInt($(this).attr("data-add"));
		var addHtml = "data"+gethtml(n);
		var editHtml = "edit"+gethtml(n);
		var Modhtml = template(addHtml, dataPro);
		var Formhtml = template(editHtml, dataPro);
		if(Formhtml=="{Template Error}"){return false;}
		if(n==17 && $(".shopModBox-17").length>=1){art.dialog.msg({content:"您已经有一个商品菜单了，不能重复~"});return false;}
			
		if(n!=17){
			$(".shopBoxView").append(Modhtml);
			$(".shopBoxFormCon").append(Formhtml);
			editclick($("#shopMod_"+shopMod));
			checkeditHtml(editHtml);//判断是否需要绑定排序
			shopMod++;
		}else{
			$('body,html').animate({scrollTop : 0}, 800);
			$(".shopBoxView").prepend(Modhtml);
			$(".shopBoxFormCon").append(Formhtml);
			editclick($("#shopMod_"+shopMod));
			shopMod++;
		}
	});
	//接受拖动
	$( ".shopBoxView" ).droppable({
		accept: ":not(.ui-sortable-helper)",//放置对象
		handle:".module-move",
		deactivate: function(event, ui) {
			var dataPro = {initialise:false,id:shopMod};
			var n = parseInt($(this).find("li.shopBoxListLi").attr("data-add"));
			var addHtml = "data"+gethtml(n);
			var editHtml = "edit"+gethtml(n);
			var Modhtml = template(addHtml, dataPro);
			var Formhtml = template(editHtml, dataPro);
			if(Formhtml=="{Template Error}"){return false;}
			if(n==17 && $(".shopModBox-17").length>=1){art.dialog.msg({content:"您已经有一个商品菜单了，不能重复~"});$(this).find("li.shopBoxListLi").remove();return false;}
			if(n!=17){
				$(this).find(".module-temp").before(Modhtml);
				$(".shopBoxFormCon").append(Formhtml);
				editclick($("#shopMod_"+shopMod));
				$(this).find("li.shopBoxListLi").remove();
				checkeditHtml(editHtml);//判断是否需要绑定排序
				shopMod++;
			}else{
				$('body,html').animate({scrollTop : 0}, 800);
				$(this).prepend(Modhtml);
				$(".shopBoxFormCon").append(Formhtml);
				editclick($("#shopMod_"+shopMod));
				$(this).find("li.shopBoxListLi").remove();
				shopMod++;
			}
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
	$(".shopBoxFormCon").on("click",".Sselect>div",function(){
        $(this).closest(".Sselect").toggleClass("hover");
    })
	$(".shopBoxFormCon").on("click",".Sselect>ul li",function(){
		var _this = $(this);
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find(".Modbgdiv");
		var val = $(this).attr("data-val");
        $(this).closest(".Sselect").find("span i").attr("class",val);
        $(this).closest(".Sselect").find("input").val(val);
		_shopMod.each(function(index, element) {
			var ModbgColor = $(this).attr("class").toString().split(/\b/);
			var change = false;
			for(var i=0;i<ModbgColor.length;i++){
				if(ModbgColor[i].toString().indexOf("ModbgColor")!=-1){
					ModbgColor[i] = val;
					var change = false;
					break;
				}else{
					var change = true;
				}
			}
			if(change){ModbgColor.push(val)}
			var ModbgColor = ModbgColor.toString().replace(/\,/g," ");
			$(this).attr("class",ModbgColor);
        });
        $(this).closest(".Sselect").toggleClass("hover");
    });
});

function begin(data){
	
	for(var i=0;i<data.module.length;i++){
		var dataPro = data.module[i];
			data.module[i].id = i+1 ;
		var addHtml = "data"+dataPro.name;
		var editHtml = "edit"+dataPro.name;
		var Modhtml = template(addHtml, dataPro);
		var Formhtml = template(editHtml, dataPro);
		if(Formhtml=="{Template Error}"){return false;}
			$(".shopBoxView").append(Modhtml);
			$(".shopBoxFormCon").append(Formhtml);

			checkeditHtml(editHtml);//判断是否需要绑定排序
			
		shopMod=$(".shopMod").length+1;
	}
	editclick($("#shopMod_0"));
}

function updataJson(){
		var length = $(".shopBoxView .shopMod").length;
		if(length==0){
			alert("请先配置页面信息！");
			return false;
		}		

		var pic_str='';
		

		var data,list,dataPro ;
		var dataModule = "[";
		$(".shopBoxView .shopMod").each(function(){
			if(dataModule!="["){			
				dataModule+=",";
			}
			var _this = $(this);
			var id = _this.attr("id");
			var _edit = _this.attr("data-special");
			var _thisEdit = $("[data-id='"+id+"']");
			
			
			//页面信息
				if(_edit == 1 ){
						list = "[";
					var checkproPic = _thisEdit.find("[name^='checkproPic']:checked").val();
					var checkproBtn = _thisEdit.find("[name='checkproBtn']:checked").prop("checked") ? _thisEdit.find("[name='checkproBtn']:checked").prop("checked") : false ;
					var checkproName = _thisEdit.find("[name='checkproName']:checked").prop("checked") ? _thisEdit.find("[name='checkproName']:checked").prop("checked") : false ;
					var checkproPrice = _thisEdit.find("[name='checkproPrice']:checked").prop("checked") ? _thisEdit.find("[name='checkproPrice']:checked").prop("checked") : false ;

					_thisEdit.find(".view-upPro").each(function(){
						var url = $(this).attr("data-url");
						var img = $(this).attr("data-img");
						var id = $(this).attr("data-id");
						var price = $(this).attr("data-price");
						var title = $(this).attr("data-title");
						var size = $(this).attr("data-size");
						if(list!="["){
							list+=",";
						}
						list+='{"id":"'+id+'","img":"'+img+'","url":"'+url+'","title":"'+title+'","price":"'+price+'","checkproPic":"'+checkproPic+'","checkproBtn":"'+checkproBtn+'","checkproName":"'+checkproName+'","checkproPrice":"'+checkproPrice+'","size":"'+size+'"}';
					})
					list += "]";
					dataPro = '{"initialise":"true","name":"Pro","list":'+list+',"checkproPic":"'+checkproPic+'","checkproBtn":"'+checkproBtn+'","checkproName": "'+checkproName+'","checkproPrice":"'+checkproPrice+'"}';
				}
				
				if(_edit == 2 ){
						list = "[";
					var checkproPic = _thisEdit.find("[name^='checkproPic']:checked").val();
					var checkproBtn = _thisEdit.find("[name='checkproBtn']:checked").prop("checked") ? _thisEdit.find("[name='checkproBtn']:checked").prop("checked") : false ;
					var checkproName = _thisEdit.find("[name='checkproName']:checked").prop("checked") ? _thisEdit.find("[name='checkproName']:checked").prop("checked") : false ;
					var checkproPrice = _thisEdit.find("[name='checkproPrice']:checked").prop("checked") ? _thisEdit.find("[name='checkproPrice']:checked").prop("checked") : false ;
					var checkProListNum = _thisEdit.find("[name^='checkProListNum']:checked").val();
					
					_thisEdit.find(".view-upPro").each(function(){
						var url = $(this).attr("data-url");
						var img = $(this).attr("data-img");
						var id = $(this).attr("data-id");
						var price = $(this).attr("data-price");
						var title = $(this).attr("data-title");
						var size = $(this).attr("data-size");
						if(list!="["){
							list+=",";
						}
						list+='{"id":"'+id+'","img":"'+img+'","url":"'+url+'","title":"'+title+'","price":"'+price+'","checkproPic":"'+checkproPic+'","checkproBtn":"'+checkproBtn+'","checkproName":"'+checkproName+'","checkproPrice":"'+checkproPrice+'","size":"'+size+'"}';
					})
					list += "]";
					dataPro = '{"initialise":"true","name":"ProList","list":'+list+',"proid":"'+id+'","checkProListNum":"'+checkProListNum+'","checkproPic":"'+checkproPic+'","checkproBtn": "'+checkproBtn+'","checkproName":"'+checkproName+'","checkproPrice":"'+checkproPrice+'"}';
				}

				if(_edit == 3 ){ //多乐互动
					var title = _thisEdit.find(".inputClass").attr("data-title");
					var url = _thisEdit.find(".inputClass").attr("data-url");
					var time = _thisEdit.find(".inputClass").attr("data-time");
					var img = _thisEdit.find(".img").attr("src");				
					//拼接图片地址
					if(pic_str!=''){
						pic_str+=',';						
					}
					pic_str+=img;

					var checkActTitle = _thisEdit.find("[name='checkActTitle']:checked").prop("checked") ? _thisEdit.find("[name='checkActTitle']:checked").prop("checked") : false;
					var checkActTime = _thisEdit.find("[name='checkActTime']:checked").prop("checked") ? _thisEdit.find("[name='checkActTime']:checked").prop("checked") : false ;

					dataPro = '{"initialise":"true","name":"Act","title":"'+title+'","url":"'+url+'","time":"'+time+'","img":"'+img+'","checkActTitle": "'+checkActTitle+'","checkActTime":"'+checkActTime+'"}';
				}
				if(_edit == 4 ){ //图片广告
					var checkPicType = _thisEdit.find("[name^='checkPicType']:checked").val();
						list = "[";
					_thisEdit.find(".shopFormBanner").each(function(){
						var img = $(this).find(".img").find("img").attr("src");
						var title = $(this).find("[name='BannerTitle']").val();
						var chooseUrl = $(this).find(".chooseUrl").val();
						var url = $(this).find(".chooseUrlInput").val();
						var urlTitle = $(this).find(".chooseUrlInput").next("a").text();

						//拼接图片地址
						if(pic_str!=''){
							pic_str+=',';						
						}
						pic_str+=img;

						if(list!="["){
							list+=",";
						}
						list+='{"title":"'+title+'","img":"'+img+'","chooseUrl":"'+chooseUrl+'","url":"'+url+'","urlTitle":"'+urlTitle+'"}';
					})
					list += "]";
					dataPro = '{"initialise":"true","name":"Pic","list":'+list+',"checkPicType":"'+checkPicType+'"}';
				}
				if(_edit == 5 ){ //图片导航
					var checkNavPicNum = _thisEdit.find("[name^='checkNavPicNum']:checked").val();
					var bgcolor = _thisEdit.find("[name^='card_color']").val();
						list = "[";
					_thisEdit.find(".shopFormBanner").each(function(){
						var img = $(this).find(".img").find("img").attr("src");
						var title = $(this).find("[name='BannerTitle']").val();
						var chooseUrl = $(this).find(".chooseUrl").val();
						var url = $(this).find(".chooseUrlInput").val();
						var urlTitle = $(this).find(".chooseUrlInput").next("a").text();
						//拼接图片地址
						if(pic_str!=''){
							pic_str+=',';						
						}
						pic_str+=img;

						if(list!="["){
							list+=",";
						}
						list+='{"title":"'+title+'","img":"'+img+'","chooseUrl":"'+chooseUrl+'","url":"'+url+'","urlTitle":"'+urlTitle+'"}';
					})
					list += "]";
					dataPro = '{"initialise":"true","name":"NavPic","list":'+list+',"checkNavPicNum":"'+checkNavPicNum+'","bgcolor":"'+bgcolor+'"}';
				}
				if(_edit == 6 ){
					var bgcolor = _thisEdit.find("[name^='card_color']").val();
						list = "[";
					_thisEdit.find(".shopFormBanner").each(function(){
						var title = $(this).find("[name='NavTxtTitle']").val();
						var chooseUrl = $(this).find(".chooseUrl").val();
						var url = $(this).find(".chooseUrlInput").val();
						var urlTitle = $(this).find(".chooseUrlInput").next("a").text();
						if(list!="["){
							list+=",";
						}
						list+='{"title":"'+title+'","chooseUrl":"'+chooseUrl+'","url":"'+url+'","urlTitle":"'+urlTitle+'"}';
					})
					list += "]";
					dataPro = '{"initialise":"true","name":"NavTxt","list":'+list+',"bgcolor":"'+bgcolor+'"}';
				}

				if(_edit == 7 ){
					var title = _thisEdit.find("[name='TitleTitle']").val();
					var description = _thisEdit.find("[name='TitleDescription']").val();
					var checkTextalign = _thisEdit.find("[name^='checkTextalign']:checked").val();
					var checkTextLine = _thisEdit.find("[name^='checkTextLine']:checked").val();
					var chooseUrl = _thisEdit.find(".chooseUrl").val();
					var url = _thisEdit.find(".chooseUrlInput").val();
					var urlTitle = _thisEdit.find(".chooseUrlInput").next("a").text();

					dataPro = '{"initialise":"true","name":"Title","title":"'+title+'","description":"'+description+'","checkTextalign":"'+checkTextalign+'","checkTextLine":"'+checkTextLine+'","chooseUrl":"'+chooseUrl+'","url":"'+url+'","urlTitle":"'+urlTitle+'"}';
				}
				if(_edit == 8 ){
					var UeditorClass = _thisEdit.find("textarea").attr("id");
					var content = UE.getEditor(UeditorClass).getContent();					
					dataPro = '{"initialise":"true","name":"Ueditor","Ueditor":"'+content+'"}';
				}
				if(_edit == 9 ){
					dataPro = '{"initialise":"true","name":"Search"}';
				}
				if(_edit == 10 ){
					var checkLine = _thisEdit.find("[name^='checkLine']:checked").val();
					dataPro = '{"initialise":"true","name":"Line","checkLine":"'+checkLine+'"}';
				}
				if(_edit == 11 ){
					var checkMargin = _thisEdit.find("[name^='checkMargin']:checked").val();
					dataPro = '{"initialise":"true","name":"Margin","checkMargin":"'+checkMargin+'"}';
				}
				if(_edit == 12 ){
					var bgimg = _thisEdit.find(".btn-upMod1Bg").next("img").attr("src");
					var url = _thisEdit.find("#chooseUrlInput1").val();
					var urlTitle = _thisEdit.find("#chooseUrlInput1").next("a").text();
					
					if(pic_str!=''){
						pic_str+=',';						
					}
					pic_str+=bgimg;

					var checkModuleNav = _thisEdit.find("[name='checkModuleNav']:checked").val();
						list = "[";
						_thisEdit.find(".shopFormBanner").each(function(){
							var title = $(this).find("[name='BannerTitle']").val();
							var chooseUrl = $(this).find(".chooseUrl").val();
							var url = $(this).find(".chooseUrlInput").val();
							var urlTitle = $(this).find(".chooseUrlInput").next("a").text();
							var img = $(this).find(".img img").attr("src");
							
							//拼接图片地址
							if(pic_str!=''){
								pic_str+=',';						
							}
							pic_str+=img;


							if(list!="["){
								list+=",";
							}
							list+='{"title":"'+title+'","img":"'+img+'","chooseUrl":"'+chooseUrl+'","url":"'+url+'","urlTitle":"'+urlTitle+'"}';
						})
						list += "]";
					dataPro = '{"initialise":"true","name":"Module1","bgimg":"'+bgimg+'","url":"'+url+'","urlTitle":"'+urlTitle+'","checkModuleNav":"'+checkModuleNav+'","list":'+list+'}';
				}
				if(_edit == 13 ){
						var music = _thisEdit.find("[name='resp_music']").val();
						list = "[";
						_thisEdit.find(".shopFormBanner").each(function(){
							var title = $(this).find("[name='BannerTitle']").val();
							var BannerDescription = $(this).find("[name='BannerDescription']").val();
								BannerDescription = BannerDescription.replace(/\n/g,"<br>");
							var checkRadius = $(this).find("[name^='checkRadius']:checked").val();
							var checkPositionX = $(this).find("[name='checkPositionX']").val();
							var checkPositionY = $(this).find("[name='checkPositionY']").val();
							var img = $(this).find(".img img").attr("src");
							
							//拼接图片地址
							if(pic_str!=''){
								pic_str+=',';						
							}
							pic_str+=img;


							if(list!="["){
								list+=",";
							}
							list+='{"title":"'+title+'","img":"'+img+'","BannerDescription":"'+BannerDescription+'","checkRadius":"'+checkRadius+'","checkPositionX":"'+checkPositionX+'","checkPositionY":"'+checkPositionY+'"}';
						})
						list += "]";
					dataPro = '{"initialise":"true","name":"Hotrecommend","list":'+list+',"music":"'+music+'"}';
				}

				if(_edit == 15 ){ //全民营销
					var checkMarket = _thisEdit.find("[name^='checkMarket']:checked").val();
					var img = _thisEdit.find(".img").attr("src");				
					//拼接图片地址
					if(pic_str!=''){
						pic_str+=',';						
					}
					pic_str+=img;
					
					dataPro = '{"initialise":"true","name":"Market","img":"'+img+'","checkMarket": "'+checkMarket+'"}';
				}
				if(_edit == 16 ){ //电商导航
					var checkShopNav = _thisEdit.find("[name^='checkShopNav']:checked").prop("checked") ? true : false;
					dataPro = '{"initialise":"true","name":"ShopNav","checkShopNav": "'+checkShopNav+'"}';
				}
				
				if(_edit == 17 ){ //商品菜单
					var bgcolor = _thisEdit.find("[name^='card_color']").val();
					var img = _thisEdit.find(".img").attr("src");
					dataPro = '{"initialise":"true","name":"ProNav","bgimg": "'+img+'","bgcolor": "'+bgcolor+'"}';
				}
				if(_edit == 18 ){ //和商品
					var title = _thisEdit.find("[name='TitleTitle']").val();
					var description = _thisEdit.find("[name='TitleDescription']").val();
					var imgsrc = $(".andbox .img").attr("src");

					list = "[";				
					_thisEdit.find(".view-upPro").each(function(){
						var url = $(this).attr("data-url");
						var id = $(this).attr("data-id");
						var img = $(this).attr("data-img");
						var price = $(this).attr("data-price");
						var title = $(this).attr("data-title");
						var size = $(this).attr("data-size");
						if(list!="["){
							list+=",";
						}
						list+='{"id":"'+id+'","url":"'+url+'","title":"'+title+'","price":"'+price+'","size":"'+size+'","img":"'+img+'"}';
					})
					list += "]";

					dataPro = '{"initialise":"true","name":"ProAnd","title":"'+title+'","description":"'+description+'","img":"'+imgsrc+'","list":'+list+'}';
					
				}
				
			dataModule += dataPro ;
		});
		dataModule += "]";
		datastr = '{"module":'+dataModule+'}';	
		newreturn =new Array(datastr,pic_str);

		return newreturn;
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
	}else if(type=="upProNavLogo"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find(".ProNavlogo img");
		_this.next("img").attr("src",url).show();
		_shopMod.attr("src",url).show();
	}else if(type=="upProNavLogo"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find(".ProNavlogo img");
		_this.next("img").attr("src",url).show();
		_shopMod.attr("src",url).show();
	}else if(type=="andbox"){
		var _thisclosest = _this.closest(".shopForm");
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id")).find(".andbox img");
		_thisclosest.attr("src",url).show();
		_shopMod.attr("src",url).show();
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
		$(".shopBoxForm-"+edit).show();
		if(special==11 || special==10 || special==9){$(".shopBoxFormCon").addClass("none")};
		if(edit==0){
			$(".shopBoxFormCon").animate({marginTop:70},500);
		}else{
			var top = _this.position().top;
			if(top<=0){
				$('.shopBoxFormCon').animate(stop);
				$(".shopBoxFormCon").animate({marginTop:135},500);
			}else{
				top = top+135;
				$('.shopBoxFormCon').animate(stop);
				$(".shopBoxFormCon").animate({marginTop:top},500);
			}
		};
}
function gethtml(n){
	switch(n){
		case 1:
		  return "Pro" ;
		  break;
		case 2:
		  return "ProList" ;
		  break;
		case 3:
		  return "Act" ;
		  break;
		case 4:
		  return "Pic" ;
		  break;
		case 5:
		  return "NavPic" ;
		  break;
		case 6:
		  return "NavTxt" ;
		  break;
		case 7:
		  return "Title" ;
		  break;
		case 8:
		  return "Ueditor" ;
		  break;
		case 9:
		  return "Search" ;
		  break;
		case 10:
		  return "Line" ;
		  break;
		case 11:
		  return "Margin" ;
		  break;
		case 12:
		  return "Market" ;
		  break;
		case 16:
		  return "ShopNav" ;
		  break;
		case 17:
		  return "ProNav" ;
		  break;
		case 18:
		  return "ProAnd" ;
		  break;
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
	$(".shopBoxFormCon").on("change keyup","[name='page_title'],[name^='checkproPic'],[name='checkproBtn'],[name='checkproName'],[name='checkproPrice'],[name^='checkProListNum'],[name='checkActTitle'],[name='checkActTime'],[name^='checkPicType'],[name^='checkNavPicNum'],[name='NavTxtTitle'],[name='BannerTitle'],[name='TitleTitle'],[name='TitleDescription'],[name^='checkTextalign'],[name^='checkTextLine'],[name^='checkLine'],[name^='checkMargin'],[name^='checkMarket'],[name^='BannerDescription'],[name^='checkPositionX'],[name^='checkPositionY'],[name^='checkRadius']",function(){
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
			_shopMod.find("li").attr("class",ArryTextLine[val]);
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
			_shopMod.find("li").hide();
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
			_shopMod.find("li").attr("class",ArryproPic[val]);
			var nowproLength = _shopMod.find("li").length;
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
			_shopMod.find("li").hide();
			for (var i=0;i<val;i++) {
				_shopMod.find("li:eq("+i+")").show();
			};
			if(_shopMod.find("li").length>val && _shopMod.find(".getMore").length==0){
				_shopMod.append('<div class="cl"></div><div class="getMore"><a href="javascript:void(0)">查看更多</a></div></li>');
			}else if(_shopMod.find("li").length<=val && _shopMod.find(".getMore").length>0){
				_shopMod.find(".getMore").remove();
			}
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
	$(".shopBoxCon").on("click",".module-del",function(e){
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
		_allgoods_list[$(this).closest(".editshopBoxForm").attr("data-id")].splice(index,1);
		$(this).closest(".view-upPro").remove();
		_shopMod.find("li:eq("+index+")").remove();
		var nowproLength = _shopMod.find("li").length;
		for(var i=0;i<=nowproLength;i++){
			if( i%3 == 0){
				_shopMod.find("li:eq("+i+")").removeClass("big").removeClass("small").addClass("big");
			}else{
				_shopMod.find("li:eq("+i+")").removeClass("big").removeClass("small").addClass("small");				
			}
		}
		if(_shopMod.find("li").length==0){
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