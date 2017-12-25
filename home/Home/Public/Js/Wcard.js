$(document).ready(function(e){
	$(".choose").click(function(){
		var type = parseInt($(this).attr("data-rel"));
		var data = {
			type:type,
			user:"商户名称",
			name:"卡券标题",
			price1:"10",//代金券-金额
			price2:"100",//代金券-抵扣条件
			discount:"10",//折扣券-折扣幅度
			preferentialcon:"凭本券您可以到店领取精美礼品一份",//优惠券-优惠详情
			time1:"20140101",
			time2:"20140108",
			shoptype:2,
			shoplist:[
			{
				name:"门店名称",
				address:"门店地址"	
			},{
				name:"门店名称",
				address:"门店地址"	
			}]
		}
		cardType(data);
	})
	//判断浏览器
	var ua = navigator.userAgent;
	ua = ua.toLowerCase();
	var match = /(webkit)[ \/]([\w.]+)/.exec(ua) ||
	/(opera)(?:.*version)?[ \/]([\w.]+)/.exec(ua) ||
	/(msie) ([\w.]+)/.exec(ua) ||
	!/compatible/.test(ua) && /(mozilla)(?:.*? rv:([\w.]+))?/.exec(ua) ||
	[];
    switch(match[1]){
		case "msie":      //ie
			$(".IEmsgerro").append("您的浏览器无法使用该模块功能<br>请使用<span>Chrome谷歌浏览器</span>或切换至<span>极速模式</span>/<span>高速模式</span>进行操作").show();
		break;
	}
	
	//可用门店
	$("[name='shop']").change(function(){
		var val = $(this).val() ;
		if(val==2){
			$(this).closest(".shopFormI").find(".shopFormMore").show();
		}else{
			$(this).closest(".shopFormI").find(".shopFormMore").hide();
		}
	});
	//添加门店
	$(".shopBoxFormCon").on("click",".add-shopAddress",function(){
		var shoplist =  {
				shoplist:[
				{
					name:"门店名称",
					address:"门店地址"	
				},{
					name:"门店名称",
					address:"门店地址"	
				}]
			};
		shopAdd(shoplist);
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
	
	//大部分修改预览
	$(".shopBoxFormCon").on("change keyup click","[class*='view-']",function(){
		var _this = $(this);
		var _thisclosest = _this.closest(".shopForm");
		var type = $(this).attr("class").substring($(this).attr("class").indexOf("view-"));
			if(type.indexOf(" ")!=-1){
				var type = type.substring(0,type.indexOf(" "));
			}
		var val = _this.val();
		var _shopMod = $("#"+_this.closest(".editshopBoxForm").attr("data-id"));
		switch(type){
			case ("view-user"):
				_shopMod.find(".card_user").text(val);
				break;
			case ("view-name"):
				_shopMod.find(".card_name").text(val);
				_shopMod.find(".viewshop-name").html(val);
				break;
			case ("view-subname"):
				_shopMod.find(".card_subname").text(val);
				break;
			case ("view-time1"):
				_shopMod.find("#date_str").text("有效期:"+val+"-"+$(".view-time2").val());
				break;
			case ("view-time2"):
				_shopMod.find("#date_str").text("有效期:"+$(".view-time1").val()+"-"+val);
				break;
			case ("view-price1"):
				_shopMod.find(".viewshop-con").html("减免金额:" + val + "元<br>抵扣条件:" + $(".view-price2").val() + "元");
				break;
			case ("view-price2"):
				_shopMod.find(".viewshop-con").html("减免金额:" + $(".view-price1").val() + "元<br>抵扣条件:" + val + "元");
				break;
			case ("view-discount"):
				_shopMod.find(".viewshop-con").html(val);
				break;
			case ("view-gift"):
				_shopMod.find(".viewshop-con").html(val);
				break;
			case ("view-preferentialcon"):
				_shopMod.find(".viewshop-con").html(val);
				break;
			case ("view-color"):
				_shopMod.find(".cardHead").css("background-color",val);
				break;
			case ("view-sn"):
				if(val == 1){
					_shopMod.find(".cardCode").removeClass("barcode").removeClass("qrcode").addClass("sncode");
				}else if(val == 3){
					_shopMod.find(".cardCode").removeClass("sncode").removeClass("barcode").addClass("qrcode");
				}
				break;
			case ("view-msg"):
					$(".cardCode p").html(val);
				break;
			case ("view-servicetel"):
				if(val != ""){
					$(".view-showtel").show();
					$(".view-showtel p").text("客服电话："+val);
				}else{
					$(".view-showtel").hide();
				}
				break;
			case ("view-con"):
					val = val.replace(/\n/g,"<br>");
					$(".view-showcon").html(val);
				break;
			case ("view-btn"):
				$(".viewshop-btn").html(val);
			break;
			default:return false;
		}
	});
	
	//删除门店
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

function nextMain(id){
	if(id==0){
		$("#nextMain").animate({left:"100%"},200);
		return false;
	}
	var _this = $(".nextMainCon"+id);
	$(".nextMainCon").hide();
	_this.show();
	$("#nextMain").animate({left:0},200);
}


function cardType(data){
	var _shopMod = $("#shopMod_0");
	var Modhtml = template("dataShop", data);
	var Formhtml = template("editShop", data);
	$(".view-time1").val(data.time1);
	$(".view-time2").val(data.time2);
	$(".view-user").val(data.user);
	$(".view-name").val(data.name);
	$("input[name='shop'][value=2]").click();
	$(".cardShop dl").html(Modhtml);
	$(".shopFormMore").html(Formhtml);
	if(data.shoptype==2){
		$(".shopFormMore").show();
	}else{
		$("input[name='shop'][value=1]").click();
		$(".shopFormMore").hide();
	}
	_shopMod.find(".cardHead h3 p").text(data.user);
	_shopMod.find(".cardHead h1").text(data.name);
	_shopMod.find(".cardHead>p").text("有效期:"+data.time1+"-"+data.time2);
	_shopMod.find(".viewshop-name").html(data.name);
	$(".editshopBoxForm").find(".btn-upCard").prev("span").text(data.name);
	
	$(".view-price1").closest(".shopFormU").hide();
	$(".view-price2").closest(".shopFormU").hide();
	$(".view-discount").closest(".shopFormU").hide();
	$(".view-gift").closest(".shopFormU").hide();
	$(".view-preferentialcon").closest(".shopFormU").hide();
	switch(data.type){
		case (1):
			$("em.cardtype").text("代金券");
			$(".view-price1").closest(".shopFormU").show();
			$(".view-price2").closest(".shopFormU").show();
			$(".view-price1").val(data.price1);
			$(".view-price2").val(data.price2);
			_shopMod.find(".viewshop-con").html("减免金额:" + data.price1 + "元<br>抵扣条件:" + data.price2 + "元");
			break;
		case (2):
			$("em.cardtype").text("折扣券");
			$(".view-discount").closest(".shopFormU").show();
			$(".view-discount").val(data.discount);
			_shopMod.find(".viewshop-con").html("折扣额度:" + data.discount + "%");
			break;
		case (3):
			$("em.cardtype").text("礼品券");
			$(".view-gift").closest(".shopFormU").show();
			$(".view-gift").val(data.preferentialcon);
			_shopMod.find(".viewshop-con").html(data.preferentialcon);
			break;
		case (4):
			$("em.cardtype").text("优惠券");
			$(".view-preferentialcon").closest(".shopFormU").show();
			$(".view-preferentialcon").val(data.preferentialcon);
			_shopMod.find(".viewshop-con").html(data.preferentialcon);
			break;
		default:return false;
	}
}

function shopAdd(data){
	var Modhtml = template("dataIncludeShop", data);
	var Formhtml = template("editIncludeShop", data);
	$(".cardShop dl").append(Modhtml);
	$(".add-shopAddress").before(Formhtml);
}


function EditImg(url,_this,type){
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
	}else{
		return false;
	}
}