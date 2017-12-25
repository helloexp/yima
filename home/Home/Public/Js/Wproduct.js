function productIntroduce(power,type,url){
    switch(type){
    case "ap":
      var list=[
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>选择城市</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>爱拍赢大奖-宝贝大厅</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>奖品详情</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-4.png" class="pro-case-img"/><p>会员精品区</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-5.png" class="pro-case-img"/><p>凭证查询登录</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-6.png" class="pro-case-img"/><p>我的凭证</p></li>',
          '</ul></div>'].join('');
      break;
    case "fsk":
      var list=[
          '<div class="proInt-case-fsk fn">',
          '<div class="fsk-left pt30" style="height:310px;"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>',
          '<div class="fsk-right" style="height:340px;"><div class="proInt-case-fsk-p mr50 mt50 pt50"><i></i><h3>营销活动中自动沉淀粉丝</h3><p>旺财平台将参与您营销活动并在活动中提供过手机号的所有用户记录下来。可对其分组，筛选，运营等一站式管理，为营销业务助力 。</p></div></div>',
          '<div class="fsk-left" style="height:317px;"><div class="proInt-case-fsk-p mr50 pt50"><i></i><h3>粉丝招募</h3><p>为您实现快速、便捷的粉丝招募！</p><p>5分钟便可完成粉丝招募活动的创建，可一键发布至线下（如门店海报、DM单等）、线上（如微信关注、微博等）全渠道。</p><p>您还可以为招募活动配置营销奖品，从而有效提高消费者参与热情。</p></div></div>',
          '<div class="fsk-right" style="height:317px;"><img src="./Home/Public/Image/product/pro-'+type+'-2.png"/></div>',
          '<div class="fsk-left pt30"><img src="./Home/Public/Image/product/pro-'+type+'-3.png"/></div>',
          '<div class="fsk-right" style="height:414px;"><div class="proInt-case-fsk-p mr50 mt50 pt50"><i></i><h3>粉丝专属电子权益卡</h3><p>我们采用二维码电子权益卡，并为您提供手机POS（ER-1100）、网页POS（E-POS)、专业受理POS（ER-6800）等多类型电子权益卡受理端。</p><p>二维码电子权益卡可通过短彩信、微信等多种渠道发送给您的粉丝。</p></div></div>',
          '<div class="fsk-left pt30" style="height:317px;"><div class="proInt-case-fsk-p mr50 pt50"><i></i><h3>粉丝回馈</h3><p>粉丝生日回馈？沉默粉丝回馈？轻松实现！</p><p>更多丰富的自定义筛选条件，想回馈谁就回馈谁!</p><p>让粉丝分组管理，助力你的回馈活动。</p></div></div>',
          '<div class="fsk-right pt30" style="height:317px;"><img src="./Home/Public/Image/product/pro-'+type+'-4.png"/></div>',
          '<div class="fsk-left pt30" style="height:384px;"><img src="./Home/Public/Image/product/pro-'+type+'-5.png"/></div>',
          '<div class="fsk-right" style="height:414px;"><div class="proInt-case-fsk-p mr50 mt50"><i></i><h3>粉丝数据统计</h3><p>丰富的维度向您展示粉丝的各类分析图表：刷卡高峰时段、年龄与性别占比、每日新增会员曲线、每日活跃粉丝、每日发卡及刷卡数据......</p></div></div>',
          '</div>'].join('');
      break;
    case "scdy":
      var list=[
          '<div class="proInt-case-title">经典活动案例</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>平安万瓶玻璃水免费送</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>检车服务申请</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>图文奥特莱斯</p></li>',
          '</ul></div>'].join('');
      break;
    case "wcj":
      var list=[
          '<div class="proInt-case-title">经典案例</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>大丰收</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>华润万家生活超市</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>圣杰士阳光餐厅</p></li>',
          '</ul></div>'].join('');
      break;
    case "wgw":
      var list=[
          '<div class="proInt-case-title">优秀微网站欣赏</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>喜临门</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>美可微</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>上海翼码</p></li>',
          '</ul></div>'].join('');
      break;
    case "wx":
      var list=[
          '<div class="proInt-case-title">经典案例</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>美林天府</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>美林天府</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>美林天府</p></li>',
          '</ul></div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-4.jpg"/></div>'].join('');
      break;
    case "yxp":
      var list=[
          '<div class="proInt-case-title">在平台上卡券的用途</div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>',
          '<div class="proInt-case-yxp">',
          '<div class="proInt-case-yxp-p"><i>1</i><h3>企业交易：</h3><p>公布采购或者供货需求，线下谈判并达成协议，在旺财平台管理交易。<br>可以加快产品自我销售的速度和拓宽市场营销宣传。</p></div>',
          '<div class="proInt-case-yxp-p mt40"><i>2</i><h3>销售给消费者：</h3><p>通过您在旺财平台建立的销售渠道，销售产品给消费者。部分销售渠道途径展示，如图：</p><p><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="mt30"/></p></div>',
          '<div class="proInt-case-yxp-p mt40"><i>3</i><h3>营销活动中使用：</h3><p>在旺财平台上开展的各种营销业务中，您可以选择已定义好的二维码卡券作为吸引用户参与营销活动的奖品。</p><p><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="mt30"/></p></div>',
          '</div>'].join('');
      break;
    case "yxqd":
      var btn1="<a href='index.php?g=LabelAdmin&m=Channel&a=add' class='proInt-btn-yxqd'>马上体验</a>";
      var btn2="<a href='index.php?g=LabelAdmin&m=Channel&a=onlineAdd' class='proInt-btn-yxqd'>马上体验</a>";
      var list=[
          '<div class="proInt-case-yxqd">',
          '<div class="proInt-case-yxqd-p"><i></i><p>二维码标签渠道，让您的门店、包装盒等快速转变为您的营销推广渠道。'+btn1+'</p></div>',
          '<div class="proInt-banner mt30"><img src="./Home/Public/Image/product/pro-'+type+'-1.jpg"/></div>',
          '<div class="proInt-case-yxqd-p mt40"><i></i><p>互联网渠道，轻松实现一键发布。'+btn2+'</p></div>',
          '<div class="proInt-banner mt30"><img src="./Home/Public/Image/product/pro-'+type+'-2.png"/></div>',
          '<div class="proInt-case-yxqd-p mt40"><i></i><p>营销效果 一目了然。</p></div>',
          '<div class="proInt-banner mt30"><img src="./Home/Public/Image/product/pro-'+type+'-3.png"/></div>',
          '</div>'].join('');
      break;
    case "zhyx":
      var list=[
          '<div class="proInt-case-title">经典活动案例</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>美可微</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>法诗曼</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>锐科</p></li>',
          '</ul></div>'].join('');
      break;
    case "qr":
      var list=[
          '<div class="proInt-case-title">爱拍二维码</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>爱拍二维码</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>爱拍二维码</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>爱拍二维码</p></li>',
          '</ul></div>'].join('');
      break;
    case "lppf":
      var list=[
          '<div class="proInt-case-title">礼品派发</div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>'].join('');
      break;
    case "fshk":
      var list=[
          '<div class="proInt-case-title">粉丝回馈</div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>'].join('');
      break;
    case "yjdt":
      var list=[
          '<div class="proInt-case-title">热门有奖活动</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>圣杰士9周年—拉丝达人秀</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>码上有财&nbsp;码上有才&nbsp;码上更精彩</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-3.png" class="pro-case-img"/><p>锐科公司“有奖答题”活动</p></li>',
          '</ul></div>'].join('');
      break;
    case "yhq":
      var list=[
          '<div class="proInt-case-title">优惠券活动</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>通用优惠券</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p>锐科“优惠券发放”活动</p></li>',
          '</ul></div>'].join('');
      break;
    case "hyzm":
      var list=[
          '<div class="proInt-case-title">粉丝招募活动</div>',
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/><p>会员招募</p></li>',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-2.png" class="pro-case-img"/><p> 阳光美视眼镜校园俱乐部(武汉区）</p></li>',
          '</ul></div>'].join('');
      break;
    case "sjzx":
      var list=[
          '<div class="proInt-case-title">数据中心</div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-2.png"/></div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-3.png"/></div>',
          '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-4.png"/></div>'].join('');
      break;
    case "msm":
      var list=[
          '<div class="proInt-case-list"><ul class="fn">',
          '<li><img src="./Home/Public/Image/product/pro-'+type+'-1.png" class="pro-case-img"/></li>',
          '</ul></div>'].join('');
      break;
    case "djq":
          var list=[
              '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-1.png"/></div>',
              '<div class="proInt-case-title">代金券分销业务构建流程</div>',
              '<div class="proInt-banner"><img src="./Home/Public/Image/product/pro-'+type+'-2.png"/></div>'].join('');
          break;
    case "spgl":
      var list="";
      break;

    default:
        var list = '';
        if(typeof(type) != 'string'){
            list = type.list;
            type = type.type;
        }
    }
	if(power==1){
		var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="javascript:void(0)" class="proInt-btn1-'+type+'">马上开通</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		if (type=="yxp"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  ''+list+'',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="javascript:void(0)" class="proInt-btn1-'+type+'">马上开通</a></div></div>',
			  '<div class="proInt-case-title">卡券的诞生</div>',
			  '<div class="proInt-banner mt30"><img src="./Home/Public/Image/product/pro-'+type+'-4.png"/></div>',
			'</div>'].join('');
		}
		if (type=="yxqd"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		if (type=="wx"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="index.php?g=Weixin&m=Weixin&a=bind&is_show=0" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		if (type=="wgw"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="'+url+'" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		if (type=="qr"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		if (type=="djq"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		$(".subcon").html(html);
		
		$("body").on("click",".proInt-btn1-"+type,function(){
			var tel="<div class='proInt-callus'>您尚未开通此项服务，欢迎拨打业务咨询热线：400-882-7770</div>"
			art.dialog({
				title:"马上开通",
				content:tel,
				id:"pro",
				width:"830px",
				padding:0
			});
		});
		
	}
	if(power==2){
		var windowhtml=['<div id="goto_proInt" class="goto_proInt"></div>'].join('');
		var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="'+url+'" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		if (type=="yxp"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  ''+list+'',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="javascript:void(0)" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  '<div class="proInt-case-title">卡券的诞生</div>',
			  '<div class="proInt-banner mt30"><img src="./Home/Public/Image/product/pro-'+type+'-4.png"/></div>',
			'</div>'].join('');
		}
		if (type=="yxqd"){
			html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"></div>',
			  ''+list+'',
			'</div>'].join('');
		}
		if(type=="zhyx"){
			var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="'+url+'" class="proInt-btn2-'+type+'" onclick="addactivity()">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');	
		}
		$("body").append(windowhtml);
		$("body").on("click","#goto_proInt",function(){
			art.dialog({
				title:"业务介绍",
				content:html,
				id:"pro",
				width:"830px",
				padding:0
			});
			if(type=="yxp"){
				$(".proInt-img").find("img").css("margin-top","-95px");
			}
			if(type=="wgw"){
				$(".proInt-img").find("img").css("margin-top","-64px");
			}
		});
		$(".proInt-btn2-"+type).live("click",function(){
			art.dialog({id:'pro'}).close();
		});
	}
	if(power==3){
		var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="'+url+'" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		if(type=="zhyx"){
			
			var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="'+url+'" class="proInt-btn2-'+type+'" onclick="addactivity()">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');	
		}
		$(".subcon").html(html);
	}
	
	if(power==4){
		$(".subcon1").hide();
		var html=['<div class="proInt">',
			  '<div class="proInt-title"><div class="proInt-icon proInt-'+type+'"></div></div>',
			  '<div class="proInt-img"><img src="./Home/Public/Image/product/pro-'+type+'-0.png" class="pro-case-img"/></div>',
			  '<div class="proInt-btn-con"><div class="proInt-btn-bg"><a href="javascript:void(0)" class="proInt-btn2-'+type+'">马上体验</a></div></div>',
			  ''+list+'',
			'</div>'].join('');
		$(".subcon2").html(html);
		$(".subcon2").show();
		
		$(".proInt-btn2-"+type).live("click",function(){
			$(".subcon2").hide();
			$(".subcon1").show();
		});
	}
	
	if(power==5){
		switch(type){
			case "qd-10":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty offl-sty-ewm"><div class="up"><h1 class="tit tit-ewm">二维码标签渠道</h1><p class="mt10">让您的门店、包装盒等</p><p>快速转变为您的营销推广渠道</p></div></div><div class="exmp-ewm1"><h1 class="tit">丰富的二维码样式设计</h1><p class="mt15">可以将设置的二维码标签设置</p><p>成丰富的样式</p></div><div class="exmp-ewm2"><div class="mt40"style="margin-left:540px;"><h1 class="tit">便捷灵活的渠道管理</h1><p class="mt15">可以随时切换标签渠道所绑定的活动</p></div></div></div>'].join('');
			  break;
			case "qd-11":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img" src="./Home/Public/Image/new_pic/dm1.png" /><div class="up up-dm"><h1 class="tit tit-dm">DM单</h1><p class="dm mt10">每个DM单都能独一无二</p><p class="dm">让您轻松掌握每个DM单投放的效果</p></div><div class="down down-dm"></div><div class="data"><h1 class="tit">让您轻松掌握每个DM单投放效果</h1><p class="mt40"><i class="c1"></i>浏览量</p><p><i class="c2"></i>中奖数</p><div class="pic"></div></div></div></div>'].join('');
			  break;
			case "qd-12":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img" src="./Home/Public/Image/new_pic/hb1.png" /><div class="up up-hb"><h1 class="tit tit-hb">海报</h1><p class="mt20 hb">让海报不在生硬，更多互动，更多欢乐</p></div><div class="down down-hb"></div></div></div>'].join('');
			  break;
			case "qd-13":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img2" src="./Home/Public/Image/new_pic/pack.png"/><div class="up up-pack"><h1 class="tit tit-pack">产品包装</h1><p>让老客户与您轻松触及，从而增加更多购买以及</p><p>获取更多增值服务</p></div><div class="down-pack"></div></div></div>'].join('');
			  break;
			case "qd-14":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img2" src="./Home/Public/Image/new_pic/card.png"/><div class="up up-card"><h1 class="tit tit-card">企业名片</h1><p>名片功能更加多元，通过名片也能触及您的营销业务</p></div><div class="down-card"></div></div></div>'].join('');
			  break;
			case "qd-15":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img2" src="./Home/Public/Image/new_pic/tlist.png"/><div class="up up-tlist"><h1 class="tit tit-tlist">桌(台)卡</h1><p>让老客户在您的门店里，也能体验更多的趣味活动</p></div><div class="down-tlist"></div></div></div>'].join('');
			  break;
			case "qd-16":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img2" src="./Home/Public/Image/new_pic/others.png"/><div class="up up-others"><h1 class="tit tit-others">其他</h1><p>您还可以创建其他的二维码标签渠道，如您的软硬件产</p><p style="margin-top:0;">品，宣传册，以及一些其他宣传物料</p></div><div class="down-others"></div></div></div>'].join('');
			  break;
			case "qd-21":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="online-sty"><img src="./Home/Public/Image/new_pic/intnet.png" class="img-intnet"/><div class="up"><div class="up-sns"><h1 class="tit tit-sns">互联网渠道</h1><p>互联网渠道，轻松实现一键发布</p></div></div><div class="down"></div></div></div>'].join('');
			  break;
			case "qd-24":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="online-sty"><img src="./Home/Public/Image/new_pic/other-can.png" class="img-other-can"/><div class="up"><div class="up-other-can"><h1 class="tit tit-ocan">其他互联网渠道</h1><p>接入开放API，让营销活动一键发布到您的企业官网</p><p>以及其他互联网渠道</p></div></div><div class="down"></div></div></div>'].join('');
			  break;
			case "qd-30":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img-emp-share" src="./Home/Public/Image/new_pic/emp-share.png"/><div class="up up-share"><h1 class="tit tit-share">员工推广</h1><p>让您的员工也成为您的推广渠道，还可以给予</p><p>员工相应奖励</p></div><div class="down-share"></div></div></div>'].join('');
			  break;
			case "qd-32":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="offl-sty"><img class="img-emp-share" src="./Home/Public/Image/new_pic/emp-share.png"/><div class="up up-share"><h1 class="tit tit-share">员工推广</h1><p>让您的员工也成为您的推广渠道，还可以给予</p><p>员工相应奖励</p></div><div class="down-share"></div></div></div>'].join('');
			  break;
			case "qd-23":
			  var html=['<div class="Wcanal-tab-list" style="border:none;width:830px;"><div class="online-sty"><img src="./Home/Public/Image/new_pic/sns.png" class="img-sns" /><div class="up"><div class="up-sns"><h1 class="tit tit-sns">SNS渠道</h1><p>互联网渠道，轻松实现一键发布</p></div></div><div class="down"></div><div class="down-sns"><img src="./Home/Public/Image/new_pic/sns1.png" class="img1 l" /><p class="tit tit1">创建SNS渠道</p></div><div class="down-sns"><img src="./Home/Public/Image/new_pic/sns2.png" class="img2 r" /><p class="tit tit2">访问授权</p></div><div class="down-sns" style="border:none;"><img src="./Home/Public/Image/new_pic/sns3.png" class="img3 l" /><p class="tit tit3">快速分享到sns渠道</p></div></div></div>'].join('');
			  break;
		}
		var windowhtml=['<div id="goto_proInt" class="goto_proInt"></div>'].join('');
		$("body").append(windowhtml);
		$("body").on("click","#goto_proInt",function(){
			art.dialog({
				title:"业务介绍",
				content:html,
				id:"pro",
				width:"830px",
				padding:0
			});
		});
	}
  if(power==10){
    var windowhtml=[
                    '<style>#goto_proInt{width: 36px;',
                    'height: 36px;',
                    'display: inline-block;',
                    'background: #9ca4ad url(Home/Public/Image/sidebar_icon.png) no-repeat;',
                    'border-radius: 2px;',
                    'position: fixed;',
                    'top: 213px;',
                    'right: 7px;',
                    'z-index:9999;',
                    '}',
                  '#goto_proInt:hover{width: 36px;',
                    'height: 36px;',
                    'display: inline-block;',
                    'background-color:#ed3f41;',
                    'border-radius: 2px;',
                    'position: fixed;',
                    'top: 213px;',
                    'right: 7px;',
                    'z-index:9999;',
                    '}',
                  '#goto_proInt:after,#goto_proInt:hover:after{ ',
                    'content:"业务介绍";',
                    'width: 36px;',
                    'height: 36px;',
                    'position:fixed;',
                    'top:152px;',
                    'color:#999;',
                    'z-index:9999;',
                    'font-size:13px;',
                    'text-align:center;',
                    'display:inline-block;',
                    'font-family:Microsoft Yahei;',
                    '}</style>',
                    '<div id="goto_proInt" class="goto_proInt"></div>'].join('');
    if(type=="wfx"){
      
      var html=['<style>',
      '.Wcanal-tab .Wcanal-tab-title p a{ color:#000; font-weight:bold}',
      '.Wcanal-tab-list{ padding:15px;}',
      '.Wcanal-tab .Wcanal-tab-title p{ height:42px;}',
      '.Wcanal-tab .Wcanal-tab-title p{ background-color: transparent; background-image:url(./Home/Public/Image/eTicket/line.png) center right no-repeat; border:0}',
      '.Wcanal-tab .Wcanal-tab-title p:first-child{ border-left:0}',
      '.Wcanal-tab .Wcanal-tab-title .Wcanal-tab-hover{ margin-top:-2px;}',
      '.Wcanal-tab .Wcanal-tab-title .Wcanal-tab-hover a{ color:#ed1c24}',
      '.Wcanal-tab .Wcanal-tab-title p em:first-child{ border-left:0}',
      '.Wcanal-tab .Wcanal-tab-title p.Wcanal-tab-hover span{z-index:400;display:block;width:0;height:0;border-width:0 5px 5px;border-style:solid; border-color:transparent transparent #dfdfdf ;position:absolute;top:36px;left:50%;margin-left:-5px;}',
      '.Wcanal-tab .Wcanal-tab-title p.Wcanal-tab-hover em{display:block;width:0;height:0;border-width:0 5px 5px;border-style:solid;border-color:transparent transparent #fff;position:absolute;top:1px;left:-5px;}',
      '.body_area{ margin:10px;}',
      '</style>',
      '<div class="yw_index" style="z-index: 9999;">',
                  '<div class="header_area">',
                      '<div class="intro_area" data-scroll-reveal="enter left">',
                          '<h1>什么是旺分销？</h1>',
                            '<p>旺分销是生产商基于移动互联网的商品多级分销管理解决方案。</p>',
                            '<ul>',
                              '<li> 支持多级分销体系，不同级别的分销商享有不同的提成比例</li>',
                                '<li> 独有客户保护机制，保障分销商的利益</li>',
                                '<li> 自动核算提成，提供清晰的汇总报表</li>',
                                '<li> 充分发挥分销商积极性</li>',
                                '<li> 与消费者建立紧密联系</li>',
                            '</ul>',
                            '<a class="btn_all js_form" href="javascript:void(0);"></a>',
                        '</div>',
                        '<div class="extra_area" data-scroll-reveal="enter left">',
                          '<ul>',
                                '<li class="tel">全国服务热线：400-882-7770</li>',
                                '<li class="qq">在线QQ咨询</li>',
                            '</ul>',
                        '</div>',
                        '<div class="right_area" data-scroll-reveal="enter right"></div>',
                    '</div>',
                    
                    '<div class="body_area">',
                      '<div class="Wcanal-tab" id="Wcanal-tabon">',
                        '<div class="Wcanal-tab-title fn">',
                            '<p class="Wcanal-tab-hover"><a href="javascript:void(0);">如何使用旺分销？</a><span><em></em></span></p>',
                            '<p><a href="javascript:void(0);">消费者如何购买？</a><span><em></em></span></p>',
                            '<p><a href="javascript:void(0);">业务实施流程</a><span><em></em></span></p>',
                        '</div>',
                        '<div class="Wcanal-tab-list" style="display:block">',
                          '<img src="./Home/Public/Image/Wfx/wfx-lc1.png">',
                        '</div>',
                        '<div class="Wcanal-tab-list" style="display:none">',
                          '<p style="font-size:14px;">1、消费者购买成功后，提成归属消费者第一次接触的购买入口所属经销商/销售员，在系统中，买卖（提成归属方为卖方）双方自动形成绑定关系。</p>',
                            '<p style="font-size:14px;">2、客户保护机制：不论消费者从哪个入口进行二次或多次购买，提成都归属于系统中绑定的经销商/销售员。</p>',
                          '<img src="./Home/Public/Image/Wfx/wfx-lc2.png">',
                        '</div>',
                        '<div class="Wcanal-tab-list" style="display:none">',
                          '<img src="./Home/Public/Image/Wfx/wfx-lc4.png">',
                        '</div>',
                        '</div>',
                    '</div>',
               ' </div>',
         '<script type="text/javascript" src="./Home/Public/Js/global.js?v=__VR__"></script>',
         '<script src="./Home/Public/Js/an.js"></script>',
         '<script>',
        'if (!(/msie [6|7|8|9]/i.test(navigator.userAgent))){',
        ' (function(){',
        ' window.scrollReveal = new scrollReveal({reset: true});',
        '})();',
        '};',
        '</script>'].join('');  
    }
    $("body").append(windowhtml);
	$("body").on("click","#goto_proInt",function(){
      art.dialog({
        title:"业务介绍",
        content:html,
        id:"pro",
        width:"1080px",
        padding:0
      });
    });
	$("body").on("click",".proInt-btn2-"+type,function(){
      art.dialog({id:'pro'}).close();
    });
  }
}
function proInt(html){
	var windowhtml='<div id="goto_proInt" class="goto_proInt"></div>';
	$("body").append(windowhtml);
	$("body").on("click","#goto_proInt",function(){
		art.dialog({
			title:"业务介绍",
			content:html,
			id:"pro",
			top:0,
			width:1080,
			padding:0
		});
	});
}