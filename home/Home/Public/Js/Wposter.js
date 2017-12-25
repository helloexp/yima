var modNum = 0;
var Hotrecommendt = "";
var pagedata = {
		"page1": {
					"page":"page1",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module1-bg.jpg"
				},
		"page2": {
					"page":"page2",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module1-bg.jpg",
					"imglist":[
						{
							"img":"./Home/Public/Image/poster/module2-img1.jpg",
							"title":""
						}
					]
				},
		"page3": {
					"page":"page3",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module3-bg.jpg",
					"imglist":[
						{
							"img":"./Home/Public/Image/poster/module3-img1.jpg",
							"title":""
						},{
							"img":"./Home/Public/Image/poster/module3-img2.jpg",
							"title":""
						}
					]
				},
		"page4": {
					"page":"page4",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module10-bg.jpg",
					"imglist":[
						{
							"img":"./Home/Public/Image/poster/module4-img1.jpg",
							"title":""
						},{
							"img":"./Home/Public/Image/poster/module4-img2.jpg",
							"title":""
						},{
							"img":"./Home/Public/Image/poster/module4-img3.jpg",
							"title":""
						}
					]
				},
		"page5": {
					"page":"page5",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module5-bg.jpg",
					"imglist":[
						{
							"img":"./Home/Public/Image/poster/module5-img1.jpg",
							"title":""
						},{
							"img":"./Home/Public/Image/poster/module5-img2.jpg",
							"title":""
						},{
							"img":"./Home/Public/Image/poster/module5-img3.jpg",
							"title":""
						}
					]
				},
		"page6": {
					"page":"page6",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module6-bg.jpg",
					"title1":"云想衣裳花想容",
					"title2":"春风拂槛露花浓",
					"title3":"若非群玉山头见，会向瑶台月下逢"
				},
		"page7": {
					"page":"page7",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module7-bg.jpg",
					"title1":"领导时代，驾驭未来",
					"title2":"突破科技、启迪未来"
				},
		"page8": {
					"page":"page8",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module8-bg.jpg",
					"title1":"孤独是一座花园",
					"title2":"往昔是湖泊",
					"title3":"每一个瞬间，灰烬都在证明它是未来的宫殿。夜晚拥抱起忧愁，然后解开它的门。关上门，不是为了幽禁欢乐，而是为了解放悲伤。"
				},
		"page9": {
					"page":"page9",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module9-img1.jpg",
					"title1":"面包屋",
					"title2":"霸占了一隅阳光，在一个寂寞的角落，寂寞的人来人往，些许停留、狭小的面包屋，寂寞地彷徨、彷徨"
				},
		"page10": {
					"page":"page10",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module10-bg.jpg",
					"img1": "./Home/Public/Image/poster/module10-img1.jpg",
					"title1":"似水般的年龄",
					"title2":"有着似水般的年龄旋转在风中的轻纱像极了天上飘盈的白云那些少年的闲愁"
				},
		"page11": {
					"page":"page11",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module11-bg.jpg",
					"img1": "./Home/Public/Image/poster/module11-img1.jpg",
					"title1":"平凡的人生",
					"title2":"很多的车，很多的人，拥在一起，拥挤在这个陌生的城市"
				},
		"page12": {
					"page":"page12",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module12-bg.jpg",
					"img1": "./Home/Public/Image/poster/module12-img1.jpg",
					"title1":"鹦鹉",
					"title2":"色白还应及雪衣，嘴红毛绿语乃奇。年年锁在金笼里，何以陇山闻处飞。"
				},
		"page13": {
					"page":"page13",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module13-bg.jpg",
					"imglist":[
						{
							"title":"[一个热气球]",
							"text":"如果我有一个热气球，我要飞遍这个世界，去看许多奇妙的地方和事物。"
						},{
							"title":"[一个热气球]",
							"text":"如果我有一个热气球，我要飞遍这个世界，去看许多奇妙的地方和事物。"
						},{
							"title":"[一个热气球]",
							"text":"如果我有一个热气球，我要飞遍这个世界，去看许多奇妙的地方和事物。"
						}
					]
				},
		"page14": {
					"page":"page14",
					"imgtype":"0",
					"animate":"0",
					"img": "./Home/Public/Image/poster/module14-bg.jpg",
					"imglist":[
						{
							"img":"./Home/Public/Image/poster/module14-img1.jpg"
						},{
							"img":"./Home/Public/Image/poster/module14-img2.jpg"
						},{
							"img":"./Home/Public/Image/poster/module14-img3.jpg"
						}
					]
				}
	};
$(document).ready(function(e){
	Gform();
	$(".pageList ul").sortable({
		items: "li:not('.pageList-add')",
		distance: 10,
		opacity: 0.7,
		axis:"y",
		start:function(event, ui){
			ui.item.startindex = ui.item.index();
			console.log(ui.item.startindex)
		},
		deactivate: function(event, ui) {
			console.log(ui.item.startindex+","+ui.item.index())
			pagelistnum(ui.item.startindex,ui.item.index());
		} 
	});
	$("body").on("click",".phonetitle,#btn-edit",function(){
		$(".editMform").hide();
		$("#editMform-0").show();
	})
	$("body").on("click",".pageList li:not('.pageList-add') .num,.pageList li:not('.pageList-add')>.listview",function(){
		var t = $(this).closest("li"),
			id = t.attr("data-id"),
			index = t.index();
		$(".pageList li").removeClass("hover");
		t.addClass("hover");
		Hotrecommendt.swipeTo(index);
		$("#Mform .editMform").hide();
		$("#editMform-"+id).show();
	});
	$("body").on("click",".pageList .icon-del",function(){
		var t = $(this),
			r = t.closest("li"),
			id = r.attr("data-id");
		art.dialog({
			title:"提示",
			lock: true,
			content:"您确定要删除么",
			ok:function(){
				r.fadeOut(500,function(){
					var index = r.index();
					r.remove();
					pagelistnum();
					if($("#dataMform-"+id).closest("li.Modlist").length){
						$("#dataMform-"+id).closest("li.Modlist").remove();
					}else{
						$("#dataMform-"+id).remove();
					}
					$("#editMform-"+id).remove();
					if (index== "0") {
						if ($('.pageList li').length >= 2) {
							$('.pageList li:eq(0) .listview').click();
						} else {
							$('#btn-edit').click();
						}
					}else{
						$('.pageList li:eq(0) .listview').click();
					}
				});
			},
			cancel:true
    	});
	});
	$("body").on("click",".pageList .icon-copy",function(){
		var t = $(this),
			r = t.closest("li"),
			i = r.attr("data-id"),
			page = r.attr("data-page");
			for( var key in pagedata){
				if(page==key){
					var moddata = pagedata[key];
				}
			}
			moddata.id = modNum+1;
			moddata.an = "an";
		var Modhtml = template("dataHotposter"+moddata.page, moddata),
			Formhtml = template("editHotposter"+moddata.page, moddata),
			pageHtml = template("dataHotposterList", moddata);
		$(".Modwapper #dataMform-"+i).after(Modhtml);
		$("#Mform").append(Formhtml);
		r.after(pageHtml);
		setTimeout(function(){
			$(".pageList li.an").removeClass("an");
		},1000);
		modNum++;
		Gformbegin();
		Hotrecommendt.reInit();
	});
	$("body").on("click",".pageList .icon-up",function(){
		var t = $(this),
			r = t.closest("li");
			p = r.prev("li");
		if(r.index()==0){return false;}
		r.after(r.prev());
		var a = r.index();
		var b = r.index() + 1;
		pagelistnum(a,b);
	});
	$("body").on("click",".pageList .icon-down",function(){
		var t = $(this),
			r = t.closest("li");
			p = r.prev("li");
		if(r.index()==r.closest("ul").find("li").length-2){return false;}
		r.before(r.next());
		var a = r.index() - 1;
		var b = r.index();
		pagelistnum(a,b);
	});
	$("body").on("click",".pageList-add",function(){
		var choosedata = {
				list:[
					{page:"page1",title:"单图海报",img:"./Home/Public/Image/poster/page1-s.jpg"},
					{page:"page2",title:"单图海报",img:"./Home/Public/Image/poster/page2-s.jpg"},
					{page:"page3",title:"单图海报",img:"./Home/Public/Image/poster/page3-s.jpg"},
					{page:"page4",title:"单图海报",img:"./Home/Public/Image/poster/page4-s.jpg"},
					{page:"page5",title:"单图海报",img:"./Home/Public/Image/poster/page5-s.jpg"},
					{page:"page6",title:"单图海报",img:"./Home/Public/Image/poster/page6-s.jpg"},
					{page:"page7",title:"单图海报",img:"./Home/Public/Image/poster/page7-s.jpg"},
					{page:"page8",title:"单图海报",img:"./Home/Public/Image/poster/page8-s.jpg"},
					{page:"page9",title:"单图海报",img:"./Home/Public/Image/poster/page9-s.jpg"},
					{page:"page10",title:"单图海报",img:"./Home/Public/Image/poster/page10-s.jpg"},
					{page:"page11",title:"单图海报",img:"./Home/Public/Image/poster/page11-s.jpg"},
					{page:"page12",title:"单图海报",img:"./Home/Public/Image/poster/page12-s.jpg"},
					{page:"page13",title:"单图海报",img:"./Home/Public/Image/poster/page13-s.jpg"},
					{page:"page14",title:"单图海报",img:"./Home/Public/Image/poster/page14-s.jpg"}
				]
			},
			html = template("chooseHotposter", choosedata);
		art.dialog({
			id:"chooseHotposter",
			title:"选择模板",
			lock: true,
			content:html,
			width:880,
			padding:0,
			height:470
    	});
	});
	$("body").on("click",".chooseHotposter li",function(){
		var page = $(this).attr("data-page");
			for( var key in pagedata){
				if(page==key){
					var moddata = pagedata[key];
				}
			}
			moddata.id = modNum+1;
			moddata.an = "an";
		var Modhtml = template("dataHotposter"+moddata.page, moddata),
			Formhtml = template("editHotposter"+moddata.page, moddata),
			pageHtml = template("dataHotposterList", moddata);
		Hotrecommendt.appendSlide(Modhtml);
		$("#Mform").append(Formhtml);
		$(".pageList-add").before(pageHtml);
		modNum++;
		art.dialog.list["chooseHotposter"].close();
		Gformbegin();
		pagelistnum();
		$(".pageList li:not('.pageList-add') .listview").last().click();
	});
	
	$("body").on("click","#btn-view",function(){
		var datastr = updataJson()[0];
		art.dialog.data('datastr', datastr);
		art.dialog.data('title', $("[class='Gvoew-pagetitle']").val());
		art.dialog.open("index.php?g=LabelAdmin&m=Poster&a=preview",{
			title:"预览",
			lock:true,
			width:"500px",
			height:"730px",
			ok:function(){
				art.dialog.close();
			}
		});
	});
});
function begin(data){
	for(var i=0;i<data.module.length;i++){
		var dataPro = data.module[i];
			dataPro.id = i+1 ;
		$("#Mform").append(template("editHotposter", dataPro));
		for(var ii=0;ii<dataPro.list.length;ii++){
			var dataList = dataPro.list[ii];
				dataList.id = modNum+1,
				Modhtml = template("dataHotposter"+dataList.page, dataList),
				Formhtml = template("editHotposter"+dataList.page, dataList),
				pageHtml = template("dataHotposterList", dataList);
			$(".Modwapper").append(Modhtml);
			$("#Mform").append(Formhtml);
			$(".pageList-add").before(pageHtml);
			modNum++;
		}
	};
	
	//预览
	Hotrecommendt = new Swiper('.ModCon',{
		wrapperClass:'Modwapper',
		slideClass:'Modlist',
		loop:false,
		slideElement:"li",
		mode: "vertical",
		onTouchEnd:function(swiper){
			$(".pageList ul li:eq("+swiper.activeIndex+") .listview").click();
			if(swiper.activeIndex==$(".Modwapper .Modlist").length-1){
				$(".iconHand").hide();
			}else{
				$(".iconHand").show();
			}
		},
		onFirstInit:function(){
			Gformbegin();
			pagelistnum();
			Gform();
		},
		onInit:function(swiper){
			Gformbegin();
			pagelistnum();
			Gform();
		}
	});
}
function redecodeURI(val){
	if(val == decodeURI(val)){
		return val
	}else{
		return decodeURI(val);
	}
}
function redecodeURItext(val){
	if(val == decodeURI(val)){
		return val.replace(/\n/g,"<br />")
	}else{
		return decodeURI(val).replace(/\n/g,"<br />");
	}
}
function pagelistnum(a,b){
	//从新排序后的数字排序
	$(".pageList li:not('.pageList-add')").each(function(index, element) {
		var num = index+1,
			t = $(this),
			id = t.attr("data-id");
		$("#editMform-"+id).find(".GformTitle").text("第"+num+"页");
		t.find(".num span").text(num);
    });
	$(".pageList .pageListmsg").fadeIn();
	setTimeout(function(){
		$(".pageList .pageListmsg").fadeOut();
	},1000);
	if(typeof(a)=="number"){
		if(a<=b){
			Hotrecommendt.getSlide(a).insertAfter(b);
		}else{
			Hotrecommendt.getSlide(a).insertAfter(b-1);
		}
	}
}

function updataJson(){
		var length = $(".pageList li").length;
		if(length==0){
			alert("请先配置页面信息！");
			return false;
		}
		var pic_str='';
		var data,list,dataPro ;
		var loop = $("[name='loop']").val();
		var music = $("[name='music']").val();
		var vague = $("[name='vague']").val();
		var btnTitle = $("[name='btnTitle']").val();
		var url;
		var urlTitle = $("[name='url']").next("a").text();
		var urlType = $("[name='urlType']").text();
		urlType==4 ? url = $("[name='url2']").val() : url = $("[name='url1']").val();
		
		var dataModule = "[";
		$(".pageList li:not('.pageList-add')").each(function(){
			if(dataModule!="["){dataModule+=",";}
			var _this = $(this);
			var id = _this.attr("data-id");
			var page = _this.attr("data-page");
			var _thisEdit = $("#editMform-"+id);
			
			//页面信息
				if(page == "page1" ){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-img']").val();
					dataPro = '{"page":"'+page+'","animate":"'+animate+'","imgtype":"'+imgtype+'","img": "'+img+'"}';
				}
				
				if(page == "page2" || page == "page3" || page == "page4" || page == "page5" ){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-imgbg']").val();
					
					list = "[";
					_thisEdit.find(".editHotposterpagelist").each(function(index){
						var img = _thisEdit.find("[name*='-img-"+index+"']").val();
						var title = encodeURI(_thisEdit.find("[name*='-title-"+index+"']").val());
						if(list!="["){
							list+=",";
						}
						list+='{"img":"'+img+'","title":"'+title+'"}';
					})
					list += "]";
					dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","imglist": '+list+'}';
				}

				if(page == "page6" || page == "page7" || page == "page8" || page == "page9" ){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-imgbg']").val();
					var title1 = encodeURI(_thisEdit.find("[name*='-title1']").val());
					var title2 = encodeURI(_thisEdit.find("[name*='-title2']").val());
					var title3 = encodeURI(_thisEdit.find("[name*='-title3']").val());
					if(title2==""){
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","title1": "'+title1+'"}';
					}else if(title3==""){
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","title1": "'+title1+'","title2": "'+title2+'"}';
					}else{
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","title1": "'+title1+'","title2": "'+title2+'","title3": "'+title3+'"}';
					}
				}
				
				if(page == "page10" || page == "page11" || page == "page12" ){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-imgbg']").val();
					var img1 = _thisEdit.find("[name*='-img1']").val();
					var title1 = encodeURI(_thisEdit.find("[name*='-title1']").val());
					var title2 = encodeURI(_thisEdit.find("[name*='-title2']").val());
					var title3 = encodeURI(_thisEdit.find("[name*='-title3']").val());
					if(title2==""){
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","img1": "'+img1+'","title1": "'+title1+'"}';
					}else if(title3==""){
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","img1": "'+img1+'","title1": "'+title1+'","title2": "'+title2+'"}';
					}else{
						dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","img1": "'+img1+'","title1": "'+title1+'","title2": "'+title2+'","title3": "'+title3+'"}';
					}
				}
				
				if(page == "page13"){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-imgbg']").val();
					list = "[";
					_thisEdit.find(".editHotposterpagelist").each(function(index){
						var title = encodeURI(_thisEdit.find("[name*='-title-"+index+"']").val());
						var text = encodeURI(_thisEdit.find("[name*='-text-"+index+"']").val());
						if(list!="["){
							list+=",";
						}
						list+='{"title":"'+title+'","text":"'+text+'"}';
					})
					list += "]";
					dataPro = '{"page":"'+page+'","imgtype":"'+imgtype+'","animate":"'+animate+'","img": "'+img+'","imglist": '+list+'}';
				}
				
				if(page == "page14"){
					var animate = _thisEdit.find("[name*='-animate']").val();
					var imgtype = _thisEdit.find("[name*='-imgtype']").val();
					var img = _thisEdit.find("[name*='-imgbg']").val();
					
					list = "[";
					_thisEdit.find(".editHotposterpagelist").each(function(index){
						var img1 = _thisEdit.find("[name*='-img-"+index+"']").val();
						if(list!="["){
							list+=",";
						}
						list+='{"img":"'+img1+'"}';
					})
					list += "]";
					dataPro = '{"page":"'+page+'","animate":"'+animate+'","imgtype":"'+imgtype+'","img": "'+img+'","imglist": '+list+'}';
				}
				
			dataModule += dataPro ;
		});
		dataModule += "]";
		datastr = '{"module":[{"initialise": "true","name": "Hotposter","loop": "'+loop+'","imgtype": "2","vague": "'+vague+'","url": "'+url+'","urlTitle": "'+urlTitle+'","urlType": "'+urlType+'","btnTitle": "'+btnTitle+'","music": "'+music+'","list":'+dataModule+'}]}';
		newreturn = new Array(datastr,pic_str);

		return newreturn;
}

//大部分修改预览
$(document).ready(function(e){
	$("body").on("change keyup","[class*='Gview-']",function(){
		decodeURIview($(this));
	});
});
function posterimg(options){
	GdataImg(options);
	var t = options.obj;
	var type = t.attr("class").substring(t.attr("class").indexOf("Gview-"));
		if(type.indexOf(" ")!=-1){
			var type = type.substring(0,type.indexOf(" "));
		};
		type = type.replace("Gview-","");
	var src = options.src;
	var bg = type.indexOf("bg");
	if(bg!=-1){
		$(".Gshow-"+type).css("background-image","url("+src+")");
	}else{
		$(".Gshow-"+type).attr("src",src);
	}
}
function animatedon(t){
	var id = t.closest(".editMform").attr("data-id"),
		dataMform = $("#dataMform-"+id),
		s = t.closest(".switch"),
		b = s.hasClass("hover");
	if(b){
		dataMform.find(".animated-on").removeClass("animated");
	}else{
		dataMform.find(".animated-on").addClass("animated");
	}
}
function drawon(t){
	var id = t.closest(".editMform").attr("data-id"),
		dataMform = $("#dataMform-"+id),
		v = t.attr("data-val"),
		s = t.closest(".switch"),
		b = s.hasClass("hover");
	dataMform.removeClass("draw-0");
	dataMform.removeClass("draw-1");
	dataMform.removeClass("draw-2");
	dataMform.addClass("draw-"+v);
}

function btnTitle(t){
	var	s = t.closest(".switch"),
		b = s.hasClass("hover");
	if(!b){
		$("[name='btnTitle']").val("");
	}else{
		$("[name='btnTitle']").focus();
	}
	var id = s.attr("data-show");
	if($("[data-show='"+id+"']").length>1){
		var show = false ;
		$("[data-show='"+id+"']").each(function(){
			if($(this).closest(".switch").hasClass("hover")){show = true}
		});
		show ? $("#"+id).show() : $("#"+id).hide();
	}else{
		s.hasClass("hover") ? $("#"+id).show() : $("#"+id).hide();
	}
}

function decodeURIview(t){
	var	s = t.closest(".editMform"),
		type = t.attr("class").substring(t.attr("class").indexOf("Gview-"));
	if(type.indexOf(" ")!=-1){
		var type = type.substring(0,type.indexOf(" "));
	};
	type = type.replace("Gview-","");
	var val = t.val();
	var v = $("#dataMform-"+s.attr("data-id"));
	if(v[0]){
		val = val.replace(/\n/g,"<br>");
		v.find(".Gshow-"+type).html(val);
		val=="" ? v.find(".Gshow-"+type).hide() : v.find(".Gshow-"+type).show();
	}else{
		val = val.replace(/\n/g,"<br>");
		$(".Gshow-"+type).html(val);
		val=="" ? $(".Gshow-"+type).hide() : $(".Gshow-"+type).show();
	}
}