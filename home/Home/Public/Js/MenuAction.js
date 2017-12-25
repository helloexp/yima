$(function(){
	var hasCurrent = $(".current").length;
	if(hasCurrent < 1){
		$(".actionTip").removeClass("dn");
		$(".menu_form_area").hide();
	}
	var hasMenu = $("#menuList").children(".jslevel1").length;
	if(hasMenu <1){
		$(".actionTip").removeClass("dn");
		$(".menu_form_area").hide();
	}
	var menunum = $(".jslevel1").length;
	var submenunum = $(".jslevel1 .sub_pre_menu_box li").length;
	if(menunum >= 3){
		//$(".js_addMenuBox").remove();
		$("#orderDis").hide();
		$("#orderBt").show();
	}
	if(submenunum>=5){
		//$(".js_addSubMenuBox").remove();
		$("#orderDis").hide();
		$("#orderBt").show();
	}
	$(".jslevel1").addClass("size1of" + menunum);
	show_menu(true);
	
	//开启菜单
	//var is_menu_open = 1; //1开启， 0关闭
//	is_menu_open?($(".js_editBox").show(),$(".js_startMenuBox").remove()):($(".js_editBox").hide(),$(".js_startMenuBox").show());
//	$("body").on("click",".js_openMenu",function(){
//		$(".js_editBox").show(),$(".js_startMenuBox").remove()
//	})
	//检查多行文本框字数
	$("#editTxt").keyup(function(){
	   var len = $(this).val().length;
	   $("#inputcounter").text(600-len);
	   var num = 600 - len;
	   $("#inputcounter").text(num);
	   if(len > 599){
		   Diaerror("输入字数超过600个字，请做适当调整！")
		   $("#inputcounter").text(num);
		   alert(num);
		   //$(this).focus(); 
	   }
	});
	//添加一级菜单
	$("body").on("click",".js_addL1Btn",function(){
		$(".actionTip").addClass("dn");
		$(".menu_form_area").show();
		var L1num = $("#menuList").find(".jslevel1").length;
		if(L1num>0){
			$("#orderDis").hide();
			$("#orderBt").show();	
		}
		if(L1num>= 3){
			Diaerror("最多只能添加三个主菜单，当前已达设置上限");
			return false;
		}
		var menuCount = $("#menuList").children("li").length;
		var data ={};
		var html = template('viewTpl', data);
		$(".current").removeClass("current");
		if(L1num>3){
			$(this).closest("li.js_addMenuBox").detach();
			return false;
		}else
		{
			$(this).closest("li.js_addMenuBox").before(html);
			$(".pre_menu_item").removeClass("size1of1").removeClass("size1of2").removeClass("size1of3").removeClass("size1of4");
			$(".pre_menu_item").addClass("size1of"+(L1num+2));
			var title = $(".current").find(".js_addMenuTips").html();
			var id = "menu_" + (new Date).getTime();
			
			$(".current").attr("id", id);
			$(".js_menu_name").val(title);
			set_new_sort_id();
			if(L1num==2){
				$(".pre_menu_item").removeClass("size1of1").removeClass("size1of2").removeClass("size1of3").removeClass("size1of4");
				$(".pre_menu_item").addClass("size1of3");
				$(this).closest("li.js_addMenuBox").detach();
			}
			if(L1num>2){
				$("#orderDis").hide();
				$("#orderBt").show();
			}
			send_add_upt_msg();
		}
	})
	
	//删除菜单
	$("body").on("click","#jsDelBt",function(){
		var data={};
		var confrimcontent = template("confirmdel",data);
		art.dialog({
			content:confrimcontent,
			title:"删除菜单",
			width:400,
			ok:function(){
				// 1. 向服务器端发送消息
				send_del_msg();
				// 2. 在界面删除菜单
				var level = $(".current .form_appmsgItem:eq(0)").children("input[name=level]").val();
				if (level == "1") {
					$(".current").detach();
					show_menu(true);
				} else if (level == "2") {
					var parentobj = $(".current").closest("ul.sub_pre_menu_list");
					// 删除子菜单
					$(".current").detach();
					show_sub_menu(parentobj, true);
				}
			},
			okVal:"确定",
			cancel:function(){
				art.dialog.close();
			},
			cancelVal:"取消"
		})
	})
	
	//显示子菜单
	$("body").on("click",".pre_menu_link",function(){
		$(".actionTip").addClass("dn");
		$(".menu_form_area").show();
		$("#menuList li").removeClass("current");
		$(this).closest("li").addClass("current");
		var title = $(".current").find(".js_addMenuTips").html();
		$(".js_menu_name").val(title);
		$(".sub_pre_menu_box").hide();
		var t = $(this);
		t.closest("li").find("div.sub_pre_menu_box").css("display","block");
		var submenuNum = t.closest("li").find("li.jslevel2").length;
		if(submenuNum<1){
			$(".contents").show();
		}else{
			$(".contents").hide();
		}
		$(".txtTips").html("字数不超过5个汉字或16个字母");
		var parentobj = $(".current").find("ul.sub_pre_menu_list");
		show_sub_menu(parentobj, true);
		if(submenuNum == 0) {
			show_menu_content();
		}
	})
	
	//点击子菜单
	$("body").on("click",".jslevel2",function(){
		$(".actionTip").addClass("dn");
		$(".menu_form_area").show();
		$(".contents").show();
		var title = $(this).find(".js_addMenuTips").html();
		$(".js_menu_name").val(title);
		$("#menuList li").removeClass("current");
		$(this).closest("li").addClass("current");
		$(".txtTips").html("字数不超过13个汉字或40个字母");
		show_menu_content();
	})
	//添加子菜单
	$("body").on("click",".js_addL2Btn",function(){
		var _this = $(this);
		$("#menuList li").removeClass("current");
		var menuCount = _this.closest("ul.sub_pre_menu_list").children("li.jslevel2").length;
		if(menuCount<=0){
			var data={};
			var confrimcontent = template("confirm",data);
			art.dialog({
				content:confrimcontent,
				title:"添加子菜单",
				width:400,
				ok:function(){
					var data = {};
					var html = template('viewsubTpl', data);
					if(menuCount>5){
						$(this).closest("li").detach();
						return false;
					}else
					{
						_this.closest(".js_addSubMenuBox").before(html);
						$(".js_menu_name").val("子菜单名称");
						var id = "submenu_" + (new Date).getTime();
						$(".current").attr("id", id);
						var parent_id = _this.closest("li.jslevel1").children(".form_appmsgItem:eq(0)").children("input[name=itemid]").val();
						$(".current").find("input[name=parent_id]").val(parent_id);
						set_new_sort_id();
						if(menuCount==5){
							_this.closest("li").detach();
						}
						send_add_upt_msg();
					}	
				},
				okVal:"确定",
				cancel:function(){
					art.dialog.close();	
				},
				cancelVal:"取消"
			});
		}else{
			var data = {};
			var html = template('viewsubTpl', data);
			if(menuCount>4){
				$(this).closest("li").detach();
				return false;
			}else
			{
				_this.closest(".js_addSubMenuBox").before(html);
				$(".js_menu_name").val("子菜单名称");
				var id = "submenu_" + (new Date).getTime();
				$(".current").attr("id", id);
				var parent_id = _this.closest("li.jslevel1").children(".form_appmsgItem:eq(0)").children("input[name=itemid]").val();
				$(".current").find("input[name=parent_id]").val(parent_id);
				set_new_sort_id();
				if(menuCount==5){
					_this.closest("li").detach();
				}
				send_add_upt_msg();
			}	
		}
		
	})
	
	//改变菜单名称
	$("body").on("input propertychange",".js_menu_name",function(){
		var thisTitle = $(this).val();
		var level = $(".current .form_appmsgItem:eq(0)").children("input[name=level]").val();
		$(".current .js_addMenuTips:eq(0)").html(thisTitle);
		$(".current").children(".form_appmsgItem").children("input[name=title]").val(thisTitle);
		send_add_upt_msg();
	})
	
	
	$("body").on("click",".js_l2Title",function(){
		$(this).closest("li").addClass("current");
	})
	
	$(".msg_sender").click(function(e) {
        $(".msg_sender").removeClass("error");
    });
	$("#pubBt").click(function(e) {
		// 更新内容到input
		var response_class = $("#response_class").val();
		var response_info = $("#response_info").val();
		//alert(response_info);
		var type = $("#type").val();
		$(".current .form_appmsgItem:eq(0)").children("input[name=type]").val(type);
		$(".current .form_appmsgItem:eq(0)").children("input[name=response_info]").val(response_info);
		$(".current .form_appmsgItem:eq(0)").children("input[name=response_class]").val(response_class);
		// 从input中获取数据发送到服务器端，保存子菜单内容
		send_add_upt_msg();
		// 发布
		menu_pub();
	});	

	
	$("body").on("click","#finishBt",function(){
		show_menu(true);
		var level = $(".current .form_appmsgItem:eq(0)").children("input[name=level]").val();
		var parentobj;
		if (level == "1") {
			parentobj = $(".current").find("ul.sub_pre_menu_list");
		} else {
			parentobj = $(".current").closest("ul.sub_pre_menu_list");
		}
		show_sub_menu(parentobj, true);
		$("#finishBt").hide();
		$("#orderBt").show();
		$(".pre_menu_link").attr("draggable",false);
		$(".icon_menu_dot").css("display","inline-block");
		$(".sort_gray").css("display","none");
		$("#menuList").sortable({disabled: true});
		parentobj.sortable({
			disabled: true
		});
	})
})

//菜单内容-文字
function addTxt(){
	$("#editTxt").show();
	$("#editTxt").focus();
	$("#type").val(0);
	$("#response_class").val(0);
	$("#editTxt").change(function(e) {
        var response_info = $("#editTxt").val();
		$("#response_info").val(response_info);
    });
}
//菜单内容-图文
function addMaterial(url){
	art.dialog.open(url,{
		title:"选择图文信息",
		width:800
	});
}
//菜单内容-图片
function addImg(){
	var opt = {
		cropPresets:'720x400',
		callback:function(data){
			$(".edit_area").hide();
			$("#reply_content_2").show().find("img").show().attr("src",data.src);
			$("#type").val(0);
			$("#response_class").val(5);
			$("#response_info").val(data.src);
		}
	};
	open_img_uploader(opt);
}
//菜单内容-卡券
function addCard(url){
	var isopen = 1; //0：未开通微信卡包业务，1：已开通微信卡包业务
	if(isopen == 0)
	{
		art.dialog.msg({
			content:"您的微信公众号未开通微信卡包业务",
			ok:function(){
				window.open("https://mp.weixin.qq.com");
			},
			okVal:"去开通",
			width:400
		})
	}
	else
	{
	art.dialog.open(url,{
		title: '添加卡券',
		width:800
	});
	}
}
//菜单内容-活动
function addActivity(url){
	art.dialog.open(url,{
		title: '添加互动模块',
		width:800
	});
}
//选择卡券的回调
var cardresp = function(d){
	$("#material_id_selected").val("card");
	var html2 = template('cardresp', d);
	$(".edit_area").hide();
	$("#reply_content_3").html(html2);
	$("#reply_content_3").show();
	$(".cardInfo").css("background",d.cardbg);
	$(".cardInfo span").html(d.shopname);
	$('.adShow_l img').attr("src",d.logo_url);
	$('.cardtitle').html(d.goods_name);
    if(d.date_type == 1){
        var da = new Date(d.date_begin_timestamp*1000);
        var year = da.getFullYear();
        var month = da.getMonth()+1;
        var date = da.getDate();
        var da2 = new Date(d.date_end_timestamp*1000);
        var year2 = da2.getFullYear();
        var month2 = da2.getMonth()+1;
        var date2 = da2.getDate();
        var html = '有效期：'+[year,month,date].join('-')+'至'+[year2,month2,date2].join('-')
    }else{
        var html = '发送卡券后'+d.date_fixed_begin_timestamp+'天开始使用-发送卡券后'+d.date_fixed_timestamp+'天结束使用'
    }
	$('.AstaticDate').html(html);
	$("#cardid").val(d.card_id);
	$("#respid").val(d.id);
	$(".totalNum").html(d.quantity);
	$(".remainNum").html(d.quantity - d.card_get_num);
	var card_num = parseInt(d.quantity - d.card_get_num);
	var cid = d.id;
	
	$("#type").val(1);
	$("#response_class").val(4);
	$("#response_info").val(d.card_id);
}

//选择互动模块的回调
var selectActivityCallback = function(d){
	//console.dir(d);
	var html3 = template('activityresp', d);
	$(".edit_area").hide();
	$("#reply_content_4").show();
	$("#reply_content_4").html(html3);
	
	$(".actTitle").html(d.name);
	$("#material_id_selected").val("activity");
	$("#activityid").val(d.batch_id);
	
	$("#type").val(1);
	$("#response_class").val(6);
	$("#response_info").val(d.batch_id);

}

//菜单内容-链接
function addLink(){
	$(".edit_area").hide();
	$("#reply_content_5").show();
	$("#type").val(1);
	$("#response_class").val(2);
	$("input[name=urlText]").change(function(e) {
		var urlText = $("input[name=urlText]").val();      
		$("#response_info").val(urlText);
    });
	
}

//检查字符数（汉字和字符区别统计）
function check_input_length(value, byteLength, title, attribute) {
       var newvalue = value.replace(/[^\x00-\xff]/g, "**"); 
       var length = newvalue.length; 
  
       //当填写的字节数小于设置的字节数 
      if (length * 1 <=byteLength * 1){ 
            return; 
      } 
      var limitDate = newvalue.substr(0, byteLength); 
      var count = 0; 
      var limitvalue = ""; 
     for (var i = 0; i < limitDate.length; i++) { 
		var flat = limitDate.substr(i, 1); 
		if (flat == "*") { 
			  count++; 
		} 
     } 
     var size = 0; 
     var istar = newvalue.substr(byteLength * 1 - 1, 1); 
     if (count % 2 == 0) { 
		//当为偶数时 
		size = count / 2 + (byteLength * 1 - count); 
		limitvalue = value.substr(0, size); 
    } else { 
		//当为奇数时 
		size = (count - 1) / 2 + (byteLength * 1 - count); 
		limitvalue = value.substr(0, size); 
    } 
   Diaerror(title + "不能超过" + byteLength + "个字节（"+byteLength /2+"个汉字）！"); 
   document.getElementById(attribute).value = limitvalue; 
   return; 	
}

function getUrlParam(name)
{
var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
var r = window.location.search.substr(1).match(reg);  //匹配目标参数
if (r!=null) return unescape(r[2]); return null; //返回参数值
} 
function show_menu(is_show_add_menu) {
	var L1num = parseInt($("#menuList").find(".jslevel1").length);
	$(".pre_menu_item").removeClass("size1of1").removeClass("size1of2").removeClass("size1of3").removeClass("size1of4");
	$("#menuList").children(".js_addMenuBox").remove();
	if (is_show_add_menu) {
		if (L1num < 3) {
		  var add_menu_btn = '<li class="js_addMenuBox pre_menu_item grid_line no_extra"><a href="javascript:void(0);" class="js_addL1Btn" draggable="false"><i class="icon14_menu_add"></i><span class="js_addMenuTips">添加菜单</span></a></li>';
		  $("#menuList").append(add_menu_btn);
		}
		switch(L1num)
		{
		case 0:
		  $(".pre_menu_item").addClass("size1of1");
		  break;
		case 1:
		  $(".pre_menu_item").addClass("size1of2");
		  break;
		case 2:
		  $(".pre_menu_item").addClass("size1of3");
		  break;
		case 3:
		  $(".pre_menu_item").addClass("size1of3");
		  break;
		default:
		  alert("菜单出错");
		}
	} else {
		switch(L1num)
		{
		case 0:
		  break;
		case 1:
		  $(".pre_menu_item").addClass("size1of1");
		  break;
		case 2:
		  $(".pre_menu_item").addClass("size1of2");
		  break;
		case 3:
		  $(".pre_menu_item").addClass("size1of3");
		  break;
		default:
		  alert("菜单出错");
		}
	}
}

function show_sub_menu(parentobj, is_show_add_menu) {
	var id=getUrlParam('id');
	$(".jslevel2").each(function(index, element) {
	  var _thisVal = $(this).attr("id");               
	  if(_thisVal == id){
		    $(this).addClass("current");
			$(this).trigger("click")
			//show_menu_content();
			return false;
	  }
	  return false;
	});
	
	var menuCount = parentobj.children("li.jslevel2").length;
	parentobj.children(".js_addSubMenuBox").detach();
	if (is_show_add_menu && menuCount < 5) {
		// 增加添加子菜单按钮
		var add_sub_menu_btn = '<li class="js_addSubMenuBox"><a href="javascript:void(0);" class="jsSubView js_addL2Btn" draggable="false"><span class="sub_pre_menu_inner js_sub_pre_menu_inner"><i class="icon14_menu_add"></i><span class="js_l2Title"></span></span></a></li>'
		parentobj.append(add_sub_menu_btn);
	}
}

function set_new_sort_id() {
	var max_sort_id = 0;
	$("input[name=sort_id]").each(function(index, element) {
        var sort_id = $(element).val();
		if (sort_id && parseInt(sort_id) > max_sort_id) {
			max_sort_id = parseInt(sort_id);
		}
    });
	$(".current .form_appmsgItem:eq(0)").children("input[name=sort_id]").val(max_sort_id + 1);
}

function show_menu_content() {
	var response_class = $(".current .form_appmsgItem:eq(0)").children("input[name=response_class]").val();
	var response_info = $(".current .form_appmsgItem:eq(0)").children("input[name=response_info]").val();
	//alert("response_class:" + response_class);
	//alert("response_info:" + response_info);
	var type = $(".current .form_appmsgItem:eq(0)").children("input[name=type]").val();
	$("#response_class").val(response_class);
	$("#response_info").val(response_info);
	$("#type").val(type);
	// 根据response_class显示类型及内容
	switch(parseInt(response_class))
	{
		
		case 0:
		$("#type").val(0);
		$(".Gform .Ginput .switch .newRadio span:first").click();
		$(".tab_nav").removeClass("selected");
		$(".tab_text").addClass("selected");
		$(".edit_area").hide();
		$("#reply_content_0").show();
		$("#editTxt").val(response_info);
		break;
		
		case 1:
		$("#type").val(0);
		$(".Gform .Ginput .switch .newRadio span:first").click();
		$(".tab_nav").removeClass("selected");
		$(".tab_appmsg").addClass("selected");
		$(".edit_area").hide();
		$("#reply_content_1").show();
		//$("#reply_content_1").html(response_info);
		var data = {material_id:response_info};
		var dialog = art.dialog({title:false,lock:true});
		$.post("http://test.wangcaio2o.com/index.php?g=Weixin&m=WeixinResp&a=showMaterialById",data,function(d){
			dialog.close();
			$(".edit_area").hide();
			$("#reply_content_1").show();
			$("#reply_content_1").html(d);
			$("#type").val(0);
			$("#response_class").val(1);
			$("#response_info").val(response_info);
			windowheight();
		});
		break;
		
		case 2:
		$("#type").val(1);
		$(".Gform .Ginput .switch .newRadio span:last").click();
		$(".edit_area").hide();
		$(".tab_nav").removeClass("selected");
		$(".tab_link").addClass("selected");
		$("#reply_content_5").show();
		$("#urlText").val(response_info);
		break;
		
		case 4:
		$("#type").val(1);
		$(".Gform .Ginput .switch .newRadio span:last").click();
		$(".edit_area").hide();
		$("#reply_content_3").show();
		$(".tab_nav").removeClass("selected");
		$(".tab_card").addClass("selected");
		var datas = {card_id:response_info};
		var dialog = art.dialog({title:false,lock:true});
		$.post("http://test.wangcaio2o.com/index.php?g=Weixin&m=WeixinMenu&a=getCardInfo",datas,function(d){
			dialog.close();
			$(".cardtitle").html(d.goods_name);
			$(".adShow_l shinfo img").attr("src",d.logo_url);
			$(".totalNum").html(d.quantity);
			$(".remainNum").html(parseInt(d.quantity - d.card_get_num));
			$(".AstaticDate ").html("有效期：" + d.time);
		},'json');
		break;
		
		case 5:
		$("#type").val(0);
		$(".Gform .Ginput .switch .newRadio span:first").click();
		$(".edit_area").hide();
		$("#reply_content_2").show();
		$(".tab_nav").removeClass("selected");
		$(".tab_img").addClass("selected");
		$("#reply_content_2 img").attr("src",response_info);
		break;
		
		case 6:
		$("#type").val(1);
		$(".Gform .Ginput .switch .newRadio span:last").click();
		$("#reply_content_4").show();
		$(".tab_nav").removeClass("selected");
		$(".tab_activity").addClass("selected");
		//$("#urlText").val(response_info);
		var datas = {batch_id:response_info};
		var dialog = art.dialog({title:false,lock:true});
		$.post("http://test.wangcaio2o.com/index.php?g=Weixin&m=WeixinMenu&a=getBatchInfo",datas,function(d){
			console.log(d);
			dialog.close();
			$(".actTitle").html(d.name);
		},'json');
		break;
		
		default:
		//$("#type").val(0);
//		$(".Gform .Ginput .switch .newRadio span:first").click();
//		$(".tab_nav").removeClass("selected");
//		$(".tab_text").addClass("selected");
//		$(".edit_area").hide();
//		$("#reply_content_0").show();
//		$("#editTxt").empty().show();
		break;
	}
}

