var domain_name='http://'+location.hostname;
/*判断是否是微信浏览器*/
var ua = window.navigator.userAgent.toLowerCase();
if(ua.match(/MicroMessenger/i) != 'micromessenger'){
  window.location.href = "/view/wap/pay/wangfu/error.html";
}
/*商户绑定页*/
var huifuIndex=angular.module('index', ['ionic','ngCookies'])
.controller('index',function ($scope,$http,$cookieStore) {
	var h=window.location.href;/*获取当前url*/
	var a=h.split('?');
	var b=a[1];
	var c=b.indexOf('&');
	if(c>0){
		c=b.split('&')[0];
	}else{
		c=b;
	}
	var node_id=c.split('=')[1];
	$cookieStore.put("node_id",node_id);
	$http.get("/new/public/huifu/info?node_id="+node_id).success(function(data){
		if(data.code=='0'){	
			$scope.title=data.data.node_name;				
			$scope.account_type=Number(data.data.account_type);/*账号类型：1未认证订阅号2已认证订阅号3未认证服务号4已认证服务号*/
			if($scope.account_type !=4 ){
				$scope.index=2;
				return false;
			}									
			var appid=data.data.app_id;/*获取appid*/	
            var component_appid=data.data.component_appid;
			window.location.href = "https://open.weixin.qq.com/connect/oauth2/authorize?appid="+appid
					              +"&redirect_uri="+domain_name+"/view/wap/pay/wangfu/pay.html"
					              +"&component_appid="+component_appid
					              +"&response_type=code&scope=snsapi_base&state=537198#wechat_redirect";				
			
		}else if(data.code=='40010'){
			$scope.index=0;
		}else{
			$scope.index=1;
			$scope.msg=data.msg;
		}
		
	});	
});

/*支付页*/
var huifuPay=angular.module('huifuPay', ['ionic','ngCookies'])
.controller('pay',function ($scope,$http,$cookieStore) {
	var h=window.location.href;	
	var i=h.indexOf("?");	
	var b=[];
	a=h.substr(i+1,h.length);
	if(a.indexOf("code=")!=-1){
		b=a.split('&');			
		$scope.code=b[0].substr(5,b[0].length);
	}								
	$scope.totle="";
	$scope.paid="";
	$scope.totleP=false;
	$scope.paidP=true;
	$scope.paidI=false;
	$scope.totleI=true;
	$scope.money=0;
	$scope.bodyShow=false;
	$scope.submitOk=true;
	$scope.submit_text='确认付款';
	var node_id=$cookieStore.get("node_id");
	var ua = navigator.userAgent.toLowerCase();
    if(ua.indexOf('android') != -1){
		$scope.isAndroid=true;
    }else{
		$scope.isAndroid=false;
    } 
    $scope.follows=true;
    $scope.sales=true; 
    $scope.iSuse_amount=true;
	/*获取商户配置信息*/
	$http.get('/new/public/huifu/info?node_id='+node_id)
	.success(function(data){
		if(data.code==0){
		    if(data.data.head_photo){
		   	   $scope.logo=data.data.head_photo;
		    }else{
		  	   $scope.logo='/view/assets/wap/image/pay/wangfu/logo.png';
		    }	
		    $cookieStore.put('logo',$scope.logo);

			$scope.title=data.data.node_name;
			$scope.name=data.data.node_short_name;
			$cookieStore.put('name',$scope.name);
			$scope.short_name=data.data.node_short_name;/*商户名称短的*/
			$scope.is_sale=data.data.is_sale;/*是否设置优惠*/
			if(data.data.now_sale){
				$scope.attention_url=data.data.now_sale.attention_url;
				$scope.use_amount=data.data.now_sale.use_amount;
				$scope.use_user=data.data.now_sale.use_user;
				$scope.sale_id=data.data.now_sale.id;
				$scope.sale_type=data.data.now_sale.sale_type;/*优惠类型*/			
				$scope.sale_data=data.data.now_sale.sale_data;
				$scope.full=$scope.sale_data[0].full;
				$scope.reduce=$scope.sale_data[0].reduce;
				if($scope.sale_type==3||$scope.sale_type==4){
					$scope.reduce=$scope.sale_data[0].reduce/10;
				}
				$scope.max=$scope.sale_data[0].max;
			}
			/*获取openid*/	
			$http.get("/new/public/get_openid?node_id="+node_id+"&mode=huifu_mobile&code="+$scope.code)
			.success(function(data){
				if(data.code==0){
					$scope.openid=data.data.openid;
					$cookieStore.put("openid",$scope.openid);
					$http.get('/new/public/check_follow?node_id='+node_id+'&openid='+$scope.openid).success(function(data){
						if(data.code==0){
							$scope.subscribe=data.data.subscribe;
							$scope.bodyShow=true;
							$scope.numberKey=true;/*初始数字键盘是否显示 初始不显示*/	
							if($scope.is_sale!=1){
								$scope.follows=false;
								$scope.sales=false;
								$scope.iSuse_amount=false;
								return false;
							};
							if($scope.use_amount ==0){
								$scope.iSuse_amount=false;								
							}
							if($scope.use_user ==1){
								if($scope.subscribe==0){
									$scope.iSuse_amount=false;
									$scope.sales=false;
									return false;
								}else{
								   $scope.follows=false;
								}								
							}else{
								$scope.follows=false;
							}							
						}else{
							console.log(data.msg);
						}
					});
				}else{
					window.location.href="/view/wap/pay/wangfu/index.html?node_id="+$cookieStore.get("node_id");	
				}
					
			});			
		}else{			
			console.log(data.msg);
		}
	});	

    var wxjdk={
    	node_id:$cookieStore.get('node_id'),
    	url:window.location.href
    }
    $.post('/new/public/wxjdk', wxjdk, function(data) {
    	wx.config({
		    debug: false, 
		    appId: data.data.appId, 
		    timestamp: data.data.timestamp, 
		    nonceStr: data.data.nonceStr,  
		    signature: data.data.signature, 
		    jsApiList: ['onMenuShareTimeline','onMenuShareAppMessage'] 
		});

		wx.ready(function(){
			/*分享到朋友圈*/
		    wx.onMenuShareTimeline({
			    title: $scope.name+'正在向你收款', 
			    link: domain_name+'/view/wap/pay/wangfu/index.html?node_id='+$cookieStore.get('node_id'), 
			    imgUrl: $scope.logo
			});
			/*分享给朋友*/
		    wx.onMenuShareAppMessage({
			   title: $scope.name+'正在向你收款', 
			    desc: '输入付款金额后，即可付款', // 分享描述
			    link: domain_name+'/view/wap/pay/wangfu/index.html?node_id='+$cookieStore.get('node_id'),
			    imgUrl: $scope.logo
			});
		});  	
    });
  
	/*弹出数字键盘输入*/
	$scope.that='totle';
	$scope.input=function(target){
		$scope.that=target;
		$scope.numberKey=true;
		if(target=="totle"){
			$scope.totleP=false;
			$scope.totleI=true;
			$scope.paidI=false;			
		}else{
			$scope.paidP=false;
			$scope.totleI=false;
			$scope.paidI=true;
		}	
	}
	/*数字键盘输入*/
	$scope.Key=function(target,h){
		$scope.submitOk=false;
		if(target=="number"){	       
		    var obj=[];
		    var objs=[];
			if($scope.that=="totle"){
				if($scope.totle>10000){
					return false;
				}
				$scope.totleP=false;
				if(h=="."){
					obj=$scope.totle.split('.')
					if(obj.length>1) return;
				}				
				$scope.totle=$scope.totle+h;
			}else{
				if($scope.paid>10000){
					return false;
				}
				$scope.paidP=false;
				if(h=="."){
					objs=$scope.paid.split('.')
					if(objs.length>1) return;
				}				
				$scope.paid=$scope.paid+h;
			}		
		}else if(target=="delete"){			
			if($scope.that=="totle"){
				var l=$scope.totle.length;
				$scope.totle=$scope.totle.substring(0,l-1);
				$scope.money=$scope.totle;
				if($scope.totle==''){
					$scope.totleP=true;
					$scope.money=0;
					$scope.paidP=true;	
					$scope.paid='';	
					$scope.salesNum='';							
				}
			}else{
				var s=$scope.paid;
				var ss=s.toString();
				var l=ss.length;
				$scope.paid=ss.substring(0,l-1);
				var money=$scope.totle-$scope.paid;
				$scope.money=money.toFixed(1);
				if($scope.paid==""){
					$scope.paidP=true;					
				}
			}

		}else if(target=="ok"){
			var saleFn = function(money){
				money=$scope.totle-$scope.paid;
				//优惠类型 1：叠加满减优惠2：单次满减优惠3：叠加满折优惠4：单次满折优惠
				if($scope.sale_type==1){//1：叠加满减优惠每满100减10元;
					var full=$scope.sale_data[0].full*1;
					var reduce=$scope.sale_data[0].reduce*1;
					var j=Math.floor(money/full);
					var r=j*reduce;
					$scope.salesNum=r.toFixed(2);
					money=money-r;
				}else if($scope.sale_type==2){//单次满减优惠		
					var array=$scope.sale_data;
					var l=array.length;	
					/*计算优惠*/
					for(i=l-1;i>=0;i--){
						if(money>=array[i].full){
							money=money-array[i].reduce*1;
							$scope.full=array[i].full*1;
							$scope.reduce=array[i].reduce*1;
							var r=array[i].reduce*1;
							$scope.salesNum=r.toFixed(2);
							break;
						}
					}
				}else if($scope.sale_type==3){//叠加满折优惠
					var full=$scope.sale_data[0].full*1;
					var reduce=$scope.sale_data[0].reduce*1;
					if(money>=full){
						var zhe=1-reduce/100;
						var r=money*zhe;
						if(r>$scope.max){
							r=$scope.max*1;
						}
						$scope.salesNum=r.toFixed(2);
						money=money-r;
					}
				}else if($scope.sale_type==4){//单次满折优惠
									
					var array=$scope.sale_data;
					var l=array.length;
					/*计算优惠*/
					for(i=l-1;i>=0;i--){
						if(money>=array[i].full){
							var zhe=1-(array[i].reduce/100);
							var r=money*zhe;
							$scope.full=array[i].full;
							$scope.reduce=array[i].reduce/10;
							money=money-r;
							$scope.salesNum=r.toFixed(2);
							break;
						}
					}
				}
				var xyz=Number(money)+Number($scope.paid);
				var zzz=Number(xyz);
				return zzz.toFixed(2);
			}
	
			$scope.numberKey=false;
		
			$scope.mbkey=false;
			$scope.totleI=false;
			$scope.paidI=false;

			var money=$scope.totle;
			if($scope.use_amount==1){
				money=$scope.totle;
			};
			if($scope.is_sale!=1){
				$scope.money=money;
				return false;
			};
			if($scope.use_user !=1){
				$scope.money = saleFn(money);
				return false;
			}

			if($scope.subscribe ==0){
				$scope.money=money;
				return false;
			}

			if($scope.subscribe ==1){
				$scope.money = saleFn(money);
				return false;
			}

			
		}else if(target=="empty"){
			if($scope.that=="totle"){
				$scope.totle="";
				$scope.money=0;
			}else{
				$scope.paid="";
				$scope.money=$scope.totle-$scope.paid;
			}
		}
	}

	/*创建订单*/
	$scope.payMoney=function(){
		if(!$scope.totle){
	    	return false;
	    }
	    $scope.submitOk=true;
	    $scope.mask=true;
	    $scope.submit_text='付款中...';
	    $scope.Key('ok');
	    var postData='node_id='+$cookieStore.get('node_id')+'&sale_id='+$scope.sale_id+'&price='+$scope.totle+'&sale_out_price='+$scope.paid+'&openid='+$scope.openid;
		$http.post("/new/public/huifu/order/create",postData,{
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
		}).success(function(data){
			if(data.code==0){
				var order_id=data.data.order_id;//订单号
				$cookieStore.put("order_id",order_id);
				var data='node_id='+node_id +'&order_id='+ order_id;
				$http.get("/new/public/huifu/pay?"+data).success(function(data){
					if(data.code==0){
						function onBridgeReady(){
							var timeStamp=data.data.timeStamp;																	
								timeStamp=timeStamp.toString();
						   WeixinJSBridge.invoke('getBrandWCPayRequest', {
						           "appId":data.data.appId,          
						           "timeStamp":timeStamp,           
						           "nonceStr":data.data.nonceStr,      
						           "package":data.data.package,     
						           "signType":data.data.signType,           
						           "paySign":data.data.paySign  
						       },function(res){ 
						           window.location.href = "/view/wap/pay/wangfu/paystatus.html"; 
						       }
						   ); 
						}

						if (typeof WeixinJSBridge == "undefined"){
						   if( document.addEventListener ){
						       document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
						   }else if (document.attachEvent){
						       document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
						       document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
						   }
						}else{
						   onBridgeReady();
						} 
						

					}else{
						alert(data.msg);
					}

				});
								
			}else{
				alert(data.msg);
			}
			
		});
	}		
	
});

/*支付页成功页*/
var payok=angular.module('payok', ['ionic','ngCookies'])
.controller('payok', function ($scope,$http,$cookieStore) {

    var node_id=$cookieStore.get("node_id");
	var openid=$cookieStore.get("openid");
	var order_id=$cookieStore.get("order_id");
	var data="node_id="+node_id+"&openid="+openid+'&order_id='+order_id;		
    $http.get("/new/public/huifu/orders?"+data).success(function(data){
    	if(data.code==0){
			$scope.data=data.data.orders[0];
			if($scope.data.status==1){
		    	$scope.title="支付失败";

		    }else{
		    	$scope.title="支付成功"; 
			}
    	}else{
    		alert(data.msg);
    	}
    	
    });	
	
	$scope.ok=function(){
		wx.closeWindow();
	}
	$scope.again=function(){
		window.location.href="/view/wap/pay/wangfu/index.html?node_id="+node_id;
	}
	var wxjdk={
    	node_id:$cookieStore.get('node_id'),
    	url:window.location.href
    }
    $.post('/new/public/wxjdk', wxjdk, function(data) {
    	wx.config({
		    debug: false, 
		    appId: data.data.appId, 
		    timestamp: data.data.timestamp, 
		    nonceStr: data.data.nonceStr,  
		    signature: data.data.signature, 
		    jsApiList: ['onMenuShareTimeline','onMenuShareAppMessage']  
		});
        $scope.logo=$cookieStore.get('logo');
        $scope.name=$cookieStore.get('name');
		wx.ready(function(){
			/*分享到朋友圈*/
		    wx.onMenuShareTimeline({
			    title: $scope.name+'正在向你收款', 
			    link: domain_name+'/view/wap/pay/wangfu/index.html?node_id='+$cookieStore.get('node_id'), 
			    imgUrl: $scope.logo
			});
			/*分享给朋友*/
		    wx.onMenuShareAppMessage({
			   title: $scope.name+'正在向你收款', 
			    desc: '输入付款金额后，即可付款', // 分享描述
			    link: domain_name+'/view/wap/pay/wangfu/index.html?node_id='+$cookieStore.get('node_id'),
			    imgUrl: $scope.logo
			});
		});  	
    });

})









































