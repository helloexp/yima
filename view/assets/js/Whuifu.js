//1.计算日期
function GetDateStr(AddDayCount) 
{ 
var dd = new Date(); 
dd.setDate(dd.getDate()+AddDayCount);//获取AddDayCount天后的日期 
var y = dd.getFullYear(); 
var m = dd.getMonth()+1;//获取当前月份的日期 
var d = dd.getDate(); 
return y+"-"+m+"-"+d; 
}

//获取url参数
function GetRequest() {
   var url = location.search; //获取url中"?"符后的字串
   if (url.indexOf("?") != -1) {    //判断是否有参数
      var str = url.substr(1); //从第一个字符开始 因为第0个是?号 获取所有除问号的所有符串
      strs = str.split("=");   //用等号进行分隔 （因为知道只有一个参数 所以直接用等号进分隔 如果有多个参数 要用&号分隔 再用等号进行分隔）
      return(strs[1]);          //直接弹出第一个参数 （如果有多个参数 还要进行循环的）
   }
}

var h=window.location.href,i=h.indexOf('.com'),ip=h.substr(0,i+4);
var domain = "http://" + window.location.host;
//收款二维码
var hfIndex = angular.module('hfIndex', []);
hfIndex.controller('hfIndex',function ($scope,$http) {
    var getUser=domain + "/index.php?g=Alipay&m=HuifuApi&a=getUser";
	$http.get(getUser).success(function(data){
		if(data.code=="0"){
			var nodeId=data.data.node_id;          //商户ID
			$scope.node_id=data.data.node_id;          //商户ID
			$scope.node_short_name = data.data.node_short_name;          //商户ID
			$scope.user_name = data.data.user_name;          //商户名称
			$('#qrcode').qrcode({width:100,height:100,text:"http://test.wangcaio2o.com/view/wap/pay/wangfu/index.html?node_id="+$scope.node_id});
			var url=domain + "/index.php?g=Alipay&m=HuifuApi&a=info&node_id="+nodeId; //页面加载时的请求
			$http.get(url).success(function(data){
				console.log(data);
				if(data.code=="0"){
					$scope.node_id=data.data.node_id;          //商户ID
					$scope.pay_type=data.data.pay_type;        // 商户绑定类型() 0未绑定 1
					$scope.appid =data.data.appid; //
					$scope.mch_id =data.data.mch_id; //商户号
					$scope.openid =data.data.openid;
					$scope.fee_rate =data.data.fee_rate;       //手续费率
					$scope.account_type = data.data.account_type;  //账号类型：1未认证订阅号2已认证订阅号3未认证服务号4已认证服务号(旺付可用类型)
					$scope.is_set_pay =data.data.is_set_pay;   // 是否设置收款账号(条码支付账号)
					$scope.is_sale =data.data.is_sale;         //是否设置优惠
					$scope.now_sale =data.data.now_sale;       // 当前优惠信息
					$scope.qr_code_url =data.data.qr_code_url; // 二维码地址
					$scope.door_code_url = data.data.door_code_url; //门贴地址
					//$scope.wrapper=true;
					if($scope.pay_type==0 || $scope.pay_type==null){
						$scope.showType = 0;
					}else{
						$scope.showType = 1;
					}
					if($scope.account_type != 4){
						Diaerror("您绑定的微信公众号类型不可用于旺付的业务开展，请重新绑定！2秒后将自动跳转到绑定界面",function(){
								window.location.href="/index.php?g=Weixin&m=Weixin&a=index";
							}
						);

					}
				}else{
					art.dialog({
						title:"设置收款账户",
						content:"<div class='loadTip'><div class='loadStatus tip'><dl><dt>使用旺付业务，需要您先将微信公众号绑定到旺财平台，以便您可以查看付款消费者信息。</dt></dl></div></div>",
						width:400,
						ok:function(){
							window.location.href="/index.php?g=Weixin&m=Weixin&a=index"
						},
						okVal:"前去绑定",
						cancel:function(){
							var win = art.dialog.open.origin;
		                    win.location.reload(); 
						},
						cancelVal:"刷新"
					})
					
				}
			});

			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id+"&start="+GetDateStr(0)+"&end="+GetDateStr(0);
			$http.get(getList).success(function(data){
				if(data.code=="0"){
					//1.获取当前页面数据
					var data=data.data.stats;
					if(data.length == 0){
						$scope.total_cnt = 0;
						$scope.total_amt = 0;
						$scope.total_sale_amt = 0;
						$scope.total_fee_amt = 0;
						$scope.total_real_amt = 0;
					}else{
						$scope.total_cnt = data[0].total_cnt;
						$scope.total_amt = data[0].total_amt;
						$scope.total_sale_amt = data[0].total_sale_amt;
						$scope.total_fee_amt = data[0].total_fee_amt;
						$scope.total_real_amt = data[0].total_real_amt;
					}
				}else{
					Diaerror(data.msg);
				}
			});
		}else{
			var url=window.location.href;
	        window.location.href=domain+'/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+url;
		}
	});

	$scope.showStatic = function(type){
		if(type==undefined){
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id;
			$http.get(getList).success(function(data){
				console.log("累计");
				console.log(data);
				if(data.code=="0"){
					//1.获取当前页面数据
					$scope.total_cnt3 = data.data.total_cnt;
					$scope.total_amt3 = data.data.total_amt;
					$scope.total_sale_amt3 = data.data.total_sale_amt;
					$scope.total_fee_amt3 = data.data.total_fee_amt;
					$scope.total_real_amt3 = data.data.total_real_amt;
				}else{
					Diaerror(data.msg);
				}
			});
		}
		if(type==0){
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id+"&start="+GetDateStr(0)+"&end="+GetDateStr(0);
			$http.get(getList).success(function(data){
				if(data.code=="0"){
					//1.获取当前页面数据
					var data=data.data.stats;
					if(data.length == 0){
						$scope.total_cnt = 0;
						$scope.total_amt = 0;
						$scope.total_sale_amt = 0;
						$scope.total_fee_amt = 0;
						$scope.total_real_amt = 0;
					}else{
						$scope.total_cnt = data[0].total_cnt;
						$scope.total_amt = data[0].total_amt;
						$scope.total_sale_amt = data[0].total_sale_amt;
						$scope.total_fee_amt = data[0].total_fee_amt;
						$scope.total_real_amt = data[0].total_real_amt;
					}
				}else{
					Diaerror(data.msg);
				}
			});
		}
		if(type==-1){
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id+"&start="+GetDateStr(-1)+"&end="+GetDateStr(-1);
			$http.get(getList).success(function(data){
				console.log("昨日");
				console.log(data);
				if(data.code=="0"){
					//1.获取当前页面数据
					var data = data.data.stats;
					if(data.length == 0){
						$scope.total_cnt2 = 0;
						$scope.total_amt2 = 0;
						$scope.total_sale_amt2 = 0;
						$scope.total_fee_amt2 = 0;
						$scope.total_real_amt2 = 0;
					}else{
						$scope.total_cnt2 = data[0].total_cnt;
						$scope.total_amt2 = data[0].total_amt;
						$scope.total_sale_amt2 = data[0].total_sale_amt;
						$scope.total_fee_amt2 = data[0].total_fee_amt;
						$scope.total_real_amt2 = data[0].total_real_amt;
					}
					
				}else{
					Diaerror(data.msg);
				}
			});
		}
	}
});
hfIndex.controller('formController',function ($scope,$http) {
	$("body").on("click",".js_settingActivity",function(e) {
		var is_set_pay = $scope.is_set_pay;
		var nodeId = $(this).attr("data-nodeid");
        var payType = $(this).attr("data-payType"); //1 表示绑定，0表示未绑定
        if(is_set_pay == 1){
        	art.dialog({
	            title: '设置收款账户',
	            id:"setAlready",
	            content:$(".tosetting2").html(),
	            ok:function(){
			        $("#theform").ajaxSubmit({
		            beforeSubmit:function(){
				      Diasucceed("正在保存，请稍后...");
		            },
		            success:function(data){
						if(data.code == 0){
		                    Diasucceed("保存成功");
		                    art.dialog.close();
		                    var win = art.dialog.open.origin;
		                    win.location.reload(); 
		                } else {
		                    Diaerror(data.msg);
		                }
		            },
		            error:function(data) {
		                Diaerror("保存失败");
		                var win = art.dialog.open.origin;
		                win.location.reload(); 
		            },
		            dataType:'json',
		        });
	            },
	            okVal:"确认",
	            button:[{name: '设置其他账户', callback: function () {
	            	art.dialog.list['setAlready'].close();
	            	art.dialog({
			            id:"setaccount",
			            title: '设置收款账户',
			            content:$(".tosetting").html(),
			            ok:function(){
			            	if($("#aaaaa").validationEngine('validate')){
							  $("#aaaaa").ajaxSubmit({
					                beforeSubmit:function(){
					                    dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
					                },
					                success:function(data){
					                    dialog.close();
					                    if(data.status==0){
					                        Diaerror(data.info);
					                    }
					                    else {
					                        Diasucceed('提交成功');
					                        art.dialog.list['setaccount'].close();
					                        var win = art.dialog.open.origin;
					                        win.location.reload(); 
					                    }
					                },
					                dataType:'json'
					            });
							    return false;
			        		}
			        		return false;
			            },
			            okVal:"确认",
			            cancel:true,
			            cancelVal:"取消",
			            width:500
			        })
			        return false;
	            }}],
	            width:500
	        })
    	}else{
    		art.dialog({
            id:"setaccount",
            title: '设置收款账户',
            content:$(".tosetting").html(),
            ok:function(){
            	if($("#aaaaa").validationEngine('validate')){
				  $("#aaaaa").ajaxSubmit({
		                beforeSubmit:function(){
		                    dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
		                },
		                success:function(data){
		                	//console.log("shezhi");
		                	//console.log(data);
		                    dialog.close();
		                    if(data.code == 0){
		                    	Diasucceed('提交成功');
		                    	var win = art.dialog.open.origin;
		                    	win.location.reload();  
		                        art.dialog.list['setaccount'].close();
		                    }else{
		                       Diaerror(data.msg);
		                       return false; 
		                    }


		                },
		                dataType:'json'
		            });
				    return false;
        		}
        		return false;
            },
            okVal:"确认",
            cancel:true,
            cancelVal:"取消",
            width:500
            })
    	};
})
})

//收款明细
var hfStatic = angular.module('hfStatic', []);
hfStatic.controller('hfStatic', ["$scope","$http",function ($scope,$http) {
    var getUser = domain + "/index.php?g=Alipay&m=HuifuApi&a=getUser";
	$http.get(getUser).success(function(data){
		if(data.code=="0"){
			var nodeId=data.data.node_id;          //商户ID
			$scope.nodeId = data.data.node_id;
			$scope.node_short_name = data.data.node_short_name;          //商户ID
			$scope.user_name = data.data.user_name;          //商户名称
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId;
			$scope.search = {
				keyword:"",
				actTitle:"",
				start:"",
				end:"",
				page:1
			}
			$scope.gotoPageFn = function(value){
				if(value=="..." || value =="...."){
					return false;
				}
				var searchUrl;
				if(value=="search"){
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&page=1&keyword="+$scope.search.keyword+"&sale_id="+$scope.search.actTitle+"&start="+$scope.search.start+"&end="+$scope.search.end;
				}else{
					$scope.search.page = value;
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&page="+$scope.search.page+"&keyword="+$scope.search.keyword+"&sale_id="+$scope.search.actTitle+"&start="+$scope.search.start+"&end="+$scope.search.end;
				}
				$scope.getPageFn(searchUrl);
			}
			$scope.getPageFn = function(getList){
				$http.get(getList).success(function(data){
					$scope.page = data.data;
						var pageFn = function(){
							if($scope.page.last_page<12){
								var newp = [];
								for(var i=1;i<=$scope.page.last_page;i++){
									newp.push(i)
								}
								return newp;
							}else if($scope.page.last_page>=12){
								var newp = [];
								if($scope.page.current_page!=1){newp = [1,'....'];}
								var allpages = ($scope.page.current_page+5)<$scope.page.last_page ? ($scope.page.current_page+5) : $scope.page.last_page;
								for(var i=$scope.page.current_page;i<=allpages;i++){
									newp.push(i)
								}
								if(allpages!=$scope.page.last_page){
									newp.push('...');
									newp.push($scope.page.last_page)
								}
								return newp;
							}
						}
						$scope.page["pages"] = pageFn();
						if(data.code=="0"){
							$scope.totleNumber = data.data.total;
							$scope.list = data.data.orders;
						}else{
							Diaerror(data.msg);
						}
				});
			};
			$scope.getPageFn(getList);

			var actList = domain + "/index.php?g=Alipay&m=HuifuApi&a=saleMenu&node_id="+nodeId;
			$http.get(actList).success(function(data){
				if(data.code=="0"){
					$scope.lists = data.data;
				}else{
					Diaerror(data.msg);
				}
			});

			$scope.startDate = GetDateStr(0);
			$scope.endDate = GetDateStr(0);
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId;
			$http.get(getList).success(function(data){
				if(data.code=="0"){
					var data = data.data.stats;
					//1.获取当前页面数据
					if(data.length!=0){
						$scope.total_cnt = data[0].total_cnt;
						$scope.total_amt = data[0].total_amt;
						$scope.total_sale_amt = data[0].total_sale_amt;
						$scope.total_fee_amt = data[0].total_fee_amt;
						$scope.total_real_amt = data[0].total_real_amt;
					}else{
						$scope.total_cnt = 0;
						$scope.total_amt = 0;
						$scope.total_sale_amt = 0;
						$scope.total_fee_amt = 0;
						$scope.total_real_amt = 0;
					}
			}
			});

			
		}else{
			var url=window.location.href;
			window.location.href=domain+'/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+url;
		}
	});

	
}]);

//每日统计
var hfStaticDaily = angular.module('hfStaticDaily', []);
hfStaticDaily.controller('hfStaticDaily',function ($scope,$http) {
    var getUser=domain + "/index.php?g=Alipay&m=HuifuApi&a=getUser";
	$http.get(getUser).success(function(data){
		if(data.code=="0"){
			var nodeId=data.data.node_id;          //商户ID
			$scope.node_id=data.data.node_id;          //商户ID
			$scope.node_short_name = data.data.node_short_name;          //商户ID
			$scope.user_name = data.data.user_name;          //商户名称
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id;
			$http.get(getList).success(function(data){
				if(data.code=="0"){
					var data=data.data;
					$scope.total_cnt = data.total_cnt;
					$scope.total_amt = data.total_amt;
					$scope.total_sale_amt = data.total_sale_amt;
					$scope.total_fee_amt = data.total_fee_amt;
					$scope.total_real_amt = data.total_real_amt;
					// var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId;
					// $scope.getPageFn(getList);
				}else{
					Diaerror(data.msg);
				}
			});

			$scope.search = {
				start:"",
				end:"",
				page:1
			}
			$scope.gotoPageFn = function(value){
				if(value=="..." || value =="...."){
					return false;
				}
				var searchUrl;
				var getStaticUlr;
				$scope.search.start = $("input[name=startDate]").val();
				$scope.search.end = $("input[name=endDate]").val();
				if(value=="search"){
					
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&page=1"+"&start="+$scope.search.start+"&end="+$scope.search.end;

				}else{
					$scope.search.page = value;
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&page="+$scope.search.page+"&start="+$scope.search.start+"&end="+$scope.search.end;
				}
				var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+$scope.node_id+"&start="+$scope.search.start+"&end="+$scope.search.end;
				$http.get(getList).success(function(data){
					if(data.code=="0"){
						var data=data.data;
						$scope.total_cnt = data.total_cnt;
						$scope.total_amt = data.total_amt;
						$scope.total_sale_amt = data.total_sale_amt;
						$scope.total_fee_amt = data.total_fee_amt;
						$scope.total_real_amt = data.total_real_amt;
						$scope.getPageFn(searchUrl);
					}else{
						Diaerror(data.msg);
					}
				});
			}
			$scope.getPageFn = function(getList){
				$http.get(getList).success(function(data){
					if(data.code=="0"){
						$scope.page = data.data;
						var pageFn = function(){
							if($scope.page.last_page<12){
								var newp = [];
								for(var i=1;i<=$scope.page.last_page;i++){
									newp.push(i)
								}
								return newp;
							}else if($scope.page.last_page>=12){
								var newp = [];
								if($scope.page.current_page!=1){newp = [1,'....'];}
								var allpages = ($scope.page.current_page+5)<$scope.page.last_page ? ($scope.page.current_page+5) : $scope.page.last_page;
								for(var i=$scope.page.current_page;i<=allpages;i++){
									newp.push(i)
								}
								if(allpages!=$scope.page.last_page){
									newp.push('...');
									newp.push($scope.page.last_page)
								}
								return newp;
							}
						}
						$scope.page["pages"] = pageFn();
						$scope.totleNumber = data.data.total;
						$scope.list = data.data.orders;
					}else{
						Diaerror(data.msg);
					}
				});
			}
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId;
			$scope.getPageFn(getList);

			$scope.showQuery = function(type){
				$scope.type = type;
				if(type == undefined){ //默认值
					$scope.search.start = $("input[name=startDate]").val();
					$scope.search.end = $("input[name=endDate]").val();
					var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId+"&start="+$scope.search.start+"&end="+$scope.search.end;
					$http.get(getList).success(function(data){
						if(data.code=="0"){
							var data = data.data;
							//1.获取当前页面数据
							if(data.length!=0){
								$scope.total_cnt = data.total_cnt;
								$scope.total_amt = data.total_amt;
								$scope.total_sale_amt = data.total_sale_amt;
								$scope.total_fee_amt = data.total_fee_amt;
								$scope.total_real_amt = data.total_real_amt;
								
							}else{
								$scope.total_cnt = 0;
								$scope.total_amt = 0;
								$scope.total_sale_amt = 0;
								$scope.total_fee_amt = 0;
								$scope.total_real_amt = 0;
							}
							var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId;
							$scope.getPageFn(getList);
							
						}else{
							Diaerror(data.msg);
						}
					});

				};

				if(type == 1){ //今日
					$scope.search.startDate = GetDateStr(0);
					$scope.search.endDate = GetDateStr(0);
					var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId+"&start="+GetDateStr(0)+"&end="+GetDateStr(0);
					$http.get(getList).success(function(data){
						if(data.code=="0"){
							var data = data.data.stats;
							//1.获取当前页面数据
							if(data.length!=0){
								$scope.total_cnt = data[0].total_cnt;
								$scope.total_amt = data[0].total_amt;
								$scope.total_sale_amt = data[0].total_sale_amt;
								$scope.total_fee_amt = data[0].total_fee_amt;
								$scope.total_real_amt = data[0].total_real_amt;
							}else{
								$scope.total_cnt = 0;
								$scope.total_amt = 0;
								$scope.total_sale_amt = 0;
								$scope.total_fee_amt = 0;
								$scope.total_real_amt = 0;
							}
							var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&start="+GetDateStr(0)+"&end="+GetDateStr(0);
							$scope.getPageFn(getList);
							
						}else{
							Diaerror(data.msg);
						}
					});

				};

				if(type == -1){ //昨日
					$scope.search.startDate = GetDateStr(-1);
					$scope.search.endDate = GetDateStr(-1);
					var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId+"&start="+GetDateStr(-1)+"&end="+GetDateStr(-1);
					$http.get(getList).success(function(data){
						if(data.code=="0"){
							//1.获取当前页面数据
							var data = data.data.stats;
							//1.获取当前页面数据
							if(data.length!=0){
								$scope.total_cnt = data[0].total_cnt;
								$scope.total_amt = data[0].total_amt;
								$scope.total_sale_amt = data[0].total_sale_amt;
								$scope.total_fee_amt = data[0].total_fee_amt;
								$scope.total_real_amt = data[0].total_real_amt;
							}else{
								$scope.total_cnt = 0;
								$scope.total_amt = 0;
								$scope.total_sale_amt = 0;
								$scope.total_fee_amt = 0;
								$scope.total_real_amt = 0;
								$scope.totleNumber = 0;
							}
							var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&start="+GetDateStr(-1)+"&end="+GetDateStr(-1);
							$scope.getPageFn(getList);
						}else{
							Diaerror(data.msg);
						}
					});
				};

				if(type == 7){ //7日
					$scope.search.startDate = GetDateStr(-7);
					$scope.search.endDate = GetDateStr(0);
					var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId+"&start="+GetDateStr(-7)+"&end="+GetDateStr(0);
					$http.get(getList).success(function(data){
						if(data.code=="0"){
							var data = data.data;
							//1.获取当前页面数据
							if(data.length!=0){
								$scope.total_cnt = data.total_cnt;
								$scope.total_amt = data.total_amt;
								$scope.total_sale_amt = data.total_sale_amt;
								$scope.total_fee_amt = data.total_fee_amt;
								$scope.total_real_amt = data.total_real_amt;
							}else{
								$scope.total_cnt = 0;
								$scope.total_amt = 0;
								$scope.total_sale_amt = 0;
								$scope.total_fee_amt = 0;
								$scope.total_real_amt = 0;
							}
							var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&start="+GetDateStr(-7)+"&end="+GetDateStr(0);
							$scope.getPageFn(getList);
						}else{
							Diaerror(data.msg);
						}
					});
				};

				if(type == 30){ //30日
					$scope.search.startDate = GetDateStr(-30);
					$scope.search.endDate = GetDateStr(0);
					var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=stats&node_id="+nodeId+"&start="+GetDateStr(-30)+"&end="+GetDateStr(0);
					$http.get(getList).success(function(data){
						if(data.code=="0"){
							var data = data.data;
							//1.获取当前页面数据
							if(data.length!=0){
								$scope.total_cnt = data.total_cnt;
								$scope.total_amt = data.total_amt;
								$scope.total_sale_amt = data.total_sale_amt;
								$scope.total_fee_amt = data.total_fee_amt;
								$scope.total_real_amt = data.total_real_amt;
								
							}else{
								$scope.total_cnt = 0;
								$scope.total_amt = 0;
								$scope.total_sale_amt = 0;
								$scope.total_fee_amt = 0;
								$scope.total_real_amt = 0;
							}
							var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=orders&node_id="+nodeId+"&start="+GetDateStr(-30)+"&end="+GetDateStr(0);
								$scope.getPageFn(getList);
						}else{
							Diaerror(data.msg);
						}
					});
				};
			}
		}
	})
});

//优惠设置
var ConfigList = angular.module('ConfigList', []);
ConfigList.controller('ConfigList', ["$scope","$http",function ($scope,$http) {
	var getUser = domain + "/index.php?g=Alipay&m=HuifuApi&a=getUser";
	$http.get(getUser).success(function(data){
		if(data.code=="0"){
			var nodeId=data.data.node_id;          //商户ID
			$scope.node_short_name = data.data.node_short_name;          //商户ID
			$scope.user_name = data.data.user_name;          //商户名称
			$scope.search = {
				actTitle:"",
				act_status:"",
				page:1
			}
			var getList=domain + "/index.php?g=Alipay&m=HuifuApi&a=saleList&node_id="+nodeId;
			$scope.gotoPageFn = function(value){
				if(value=="..." || value =="...."){
					return false;
				}
				var searchUrl;
				if(value=="search"){
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=saleList&node_id="+nodeId+"&page=1&title="+$scope.search.actTitle+"&status="+$scope.search.act_status;
				}else{
					$scope.search.page = value;
					searchUrl = domain + "/index.php?g=Alipay&m=HuifuApi&a=saleList&node_id="+nodeId+"&page="+$scope.search.page+"&title="+$scope.search.actTitle+"&status="+$scope.search.act_status;
				}
				$scope.getPageFn(searchUrl);
			}
			$scope.getPageFn = function(getList){
				$http.get(getList).success(function(data){
					//console.log(data);
					if(data.code=="0"){
						$scope.page = data.data;
						var pageFn = function(){
							if($scope.page.last_page<12){
								var newp = [];
								for(var i=1;i<=$scope.page.last_page;i++){
									newp.push(i)
								}
								return newp;
							}else if($scope.page.last_page>=12){
								var newp = [];
								if($scope.page.current_page!=1){newp = [1,'....'];}
								var allpages = ($scope.page.current_page+5)<$scope.page.last_page ? ($scope.page.current_page+5) : $scope.page.last_page;
								for(var i=$scope.page.current_page;i<=allpages;i++){
									newp.push(i)
								}
								if(allpages!=$scope.page.last_page){
									newp.push('...');
									newp.push($scope.page.last_page)
								}
								return newp;
							}
						}
						$scope.page["pages"] = pageFn();
						$scope.totleNumber = data.data.total;
						$scope.list = data.data.data;
						$scope.current_page = data.data.current_page;
						$scope.totalItems = data.data.total;
						$scope.itemsPerPage = data.data.per_page;
						$scope.pagesLength = data.data.per_page;
					}else{
						Diaerror(data.msg);
					}
				});
			}
			$scope.getPageFn(getList);
		}else{
			var url=window.location.href;
			window.location.href=domain+'/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+url;
		}
	});
}]);

//新建优惠活动
var ConfigEdit = angular.module('ConfigEdit', []);
ConfigEdit.controller('ConfigEdit', ["$scope","$http",function ($scope,$http) {
    var getUser=domain + "/index.php?g=Alipay&m=HuifuApi&a=getUser";
	$http.get(getUser).success(function(data){
		if(data.code=="0"){
			$scope.node_id=data.data.node_id;          //商户ID
			$scope.node_short_name = data.data.node_short_name;          //商户ID
			$scope.user_name = data.data.user_name;          //商户名称
			var id=GetRequest(h);
			if(id!=undefined){
				$scope.firstTitle=  "编辑";
				var getEdit=domain + "/index.php?g=Alipay&m=HuifuApi&a=saleList&node_id="+$scope.node_id+"&id="+id;
				$http.get(getEdit).success(function(data){
					console.log(data);
					if(data.code=="0"){
						info = data.data.data;
						$scope.info=info[0];
						$scope.inputuse_date = [];
						if($scope.info.use_date == 1){
							$scope.inputuse_date = [1,0,0,0];
						}else if($scope.info.use_date == 2){
							$scope.inputuse_date = [0,1,0,0];
						}else if($scope.info.use_date == 3){
							$scope.inputuse_date = [1,1,0,0];
						}else if($scope.info.use_date == 4){
							$scope.inputuse_date = [0,0,1,0];
						}else if($scope.info.use_date == 5){
							$scope.inputuse_date = [1,0,1,0];
						}else if($scope.info.use_date == 6){
							$scope.inputuse_date = [0,1,1,0];
						}else if($scope.info.use_date == 7){
							$scope.inputuse_date = [1,1,1,0];
						}else if($scope.info.use_date == 8){
							$scope.inputuse_date = [0,0,0,1];
						}else if($scope.info.use_date == 9){
							$scope.inputuse_date = [1,0,0,1];
						}else if($scope.info.use_date == 10){
							$scope.inputuse_date = [0,1,0,1];
						}else if($scope.info.use_date == 11){
							$scope.inputuse_date = [1,1,0,1];
						}else if($scope.info.use_date == 12){
							$scope.inputuse_date = [0,0,1,1];
						}else if($scope.info.use_date == 13){
							$scope.inputuse_date = [1,0,1,1];
						}else if($scope.info.use_date == 14){
							$scope.inputuse_date = [0,1,1,1];
						}else if($scope.info.use_date == 15){
							$scope.inputuse_date = [1,1,1,1];
						}else{
							$scope.inputuse_date = [1,1,1,1];
						}

						if($scope.info.use_date == 1){
							$(".useDate input[name=usedate]:eq(0)").attr("checked","checked");
							$(".useDate .newRadio span:eq(0)").addClass("hover");
						}

						if($scope.info.sale_type == 1){
							$(".saleType input[name='sale_type']").attr("checked","checked").val("1");
							$("#show_custom1").css("display","block");
							$("#show_custom1,#show_custom2,#show_custom13,#show_custom4").removeClass("ischeck");
							$("#show_custom1").addClass("ischeck")
						}
						if($scope.info.sale_type == 2){
							$(".saleType input[name=sale_type]").attr("checked","checked").val("2");
							$("#show_custom2").css("display","block");
							$("#show_custom1,#show_custom2,#show_custom13,#show_custom4").removeClass("ischeck");
							$("#show_custom2").addClass("ischeck")
						}
						if($scope.info.sale_type == 3){
							$(".saleType input[name=sale_type]").attr("checked","checked").val("3");
							$("#show_custom3").css("display","block");
							$("#show_custom1,#show_custom2,#show_custom13,#show_custom4").removeClass("ischeck");
							$("#show_custom3").addClass("ischeck")
						}
						if($scope.info.sale_type == 4){
							$(".saleType input[name=sale_type]").attr("checked","checked").val("4");
							$("#show_custom4").css("display","block");
							$("#show_custom1,#show_custom2,#show_custom13,#show_custom4").removeClass("ischeck");
							$("#show_custom4").addClass("ischeck")
						}
					}else{
						Diaerror(data.msg);
					}
				});
			}else{
				$scope.firstTitle=  "新建";
				$scope.info = [{
					  "title":"", 
					  "start_time":"", 
					  "end_time":"",    
					  "sale_type":1,           
					  "sale_data":  
					  [
					    {
					      "full":"",
					      "reduce":"",
					      "max":""
					    }
					  ],
					  "use_date":15,  
					  "use_time":    
					  [
					    {
					      "start":"00:00",
					      "end":"23:59"
					    }
					  ],
					  "use_user":0, 
					  "attention_url":null,
					  "use_amount":0 
					}];
					if($scope.info.sale_type == undefined){
						$("input[name='sale_type']").val(1);
						$("#show_custom1,#show_custom2,#show_custom3,#show_custom4").css("display", "none");
						$("#show_custom1,#show_custom2,#show_custom13,#show_custom4").removeClass("ischeck");
						$("#show_custom1").show().addClass("ischeck");
					}
					if($scope.info.sale_type == 1){
						$("input[name='sale_type']").val(1);
						$("#show_custom1,#show_custom2,#show_custom3,#show_custom4").css("display", "none");
						$("#show_custom1").css("display", "block");

					}
					if($scope.info.sale_type == 2){
						$(".saleType input[name=sale_type]").attr("checked","checked").val("2");
						$("#show_custom1,#show_custom2,#show_custom3,#show_custom4").css("display", "none");
						$("#show_custom2").css("display", "block");
					}
					if($scope.info.sale_type == 3){
						$(".saleType input[name=sale_type])").attr("checked","checked").val("3");
						$("#show_custom1,#show_custom2,#show_custom3,#show_custom4").css("display", "none");
						$("#show_custom3").css("display", "block");
					}
					if($scope.info.sale_type == 4){
						$(".saleType input[name=sale_type])").attr("checked","checked").val("4");
						$("#show_custom1,#show_custom2,#show_custom3,#show_custom4").css("display", "none");
						$("#show_custom4").css("display", "block");
					}


					// if($scope.info.use_date == 15){
					// 	$(".useDate input[name=usedate]:eq(0)").attr("checked","checked");
					// 	$(".useDate input[name=usedate]:eq(1)").attr("checked","checked");
					// 	$(".useDate input[name=usedate]:eq(2)").attr("checked","checked");
					// 	$(".useDate input[name=usedate]:eq(3)").attr("checked","checked");
					// 	$(".useDate .newRadio span").addClass("hover");
					// }
					$scope.inputuse_date = [1,1,1,1];
					$scope.info.use_user = 0;
					$scope.info.use_amount = 0;
					
			}
			
			$("#smb").click(function(){
				var useDate = 0;
				var tVal = '';
				$("input[name='usedate']").each(function() {
					var t = $(this);
					var isChecked = t.attr("checked");
					if(isChecked == "checked"){
						useDate= parseInt(useDate) + parseInt(t.val());	
					}
					return;
				});
				
				var saleConfig = [];
				var useTime = [];
				$(".ischeck .RuleItem li").each(function(i,t){
					var data = {
						"full":$(this).find("[name='full']").val() ? $(this).find("[name='full']").val():0,
						"reduce":$(this).find("[name='reduce']").val() ? $(this).find("[name='reduce']").val():0
					}
					if(typeof($(this).find("[name='max']").val())!="undefined"){
						data["max"] = $(this).find("[name='max']").val()
					}
					saleConfig.push(data);
				})

				$(".useTime .Gtime2").each(function(){
					var data = {
						"start":$(this).find("[name='start']").val(),
						"end":$(this).find("[name='end']").val()
					}
					useTime.push(data);
				})
				var nodeId = $scope.node_id; //商户号
				var id=$scope.info.id; //当前编辑活动的id
				var title=$("input[name='title']").val(); //活动标题
				var startTime = $("input[name='start_time']").val(); //开始时间
				var endTime = $("input[name='end_time']").val(); //结束时间
				var saleType = $("input[name='sale_type']").val();  
				var usedate=useDate;      //使用日期 请用位运算 如选周一至周四和周五 就是1+2 传3
				var userUser=$("input[name='use_user']").val();  	 //使用对象 0所有 1微信粉丝
				var attentionUrl=$("input[name='attention_url']").val(); //关注页地址 使用对象为1时
				var useAmount=$("input[name='use_amount']").val(); 	 //优惠金额限制 0 不限制 1部分金额优惠
				if(id!=undefined)
				{
					var data = {
						"id":id,
						"node_id":nodeId,
					    "title":title,
					    "start_time":startTime,
					    "end_time":endTime,
					    "sale_type":saleType,
					    "sale_data":saleConfig,
					    "use_date":usedate,
					    "use_time":useTime,
					    "use_user":userUser,
					    "attention_url":attentionUrl,
					    "use_amount":useAmount
					}
				}else{
					var data = {
						"node_id":nodeId,
					    "title":title,
					    "start_time":startTime,
					    "end_time":endTime,
					    "sale_type":saleType,
					    "sale_data":saleConfig,
					    "use_date":usedate,
					    "use_time":useTime,
					    "use_user":userUser,
					    "attention_url":attentionUrl,
					    "use_amount":useAmount
					};
				};
				data = JSON.stringify(data);
				console.log("data")
				console.log(data);
			    if ($("form").validationEngine('validate')) {
			    	Diasucceed("正在保存您配置的优惠活动...");
			    	
				    $.post("/index.php?g=Alipay&m=HuifuApi&a=saleSave","data="+data,function(result){
				    	if(result.code==40007){
				    		Diaerror(result.msg);
				    		return false;
				    	}else if(result.code==0){
				    		Diasucceed("优惠活动保存成功！");
				    		setTimeout(function() {
				    			window.location.href="/view/pay/wangfu/Config.html";
				    		}, 1000);
				    	}
					});
				}

			})
		}else{
			var url=window.location.href;
			window.location.href=domain+'/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+url;
		}

	});

}])


