
/*************现金抵用券******列表页****开始******/
var app = angular.module('CashVoucher', ["ngPage"]);
app.controller('CashVoucher',function($scope,$http,$templateCache,$timeout){

$scope.syns_wechat_card=!0;
$scope.url="./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherGet";
$scope.PageData={
                type:'default',
                rowsPage:"20",
                showPage:"10",
                url:$scope.url
                };

$scope.clickMore=function(target){
    $scope.that=$(target);
    var attr=$scope.that.attr("data-type");
    var pageId=$scope.that.closest('td').find("input[name=pageId]");
    if(attr=="phone"){
       window.open("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherPhoneSend");
    }else if(attr=="card"){
        Diamsg({
            content:"您未在旺财绑定您的公众号！<br><span>请绑定公众号并确认公众号已开通了微信卡包业务</span>",
            ok:function(){
                window.open("https://mp.weixin.qq.com/cgi-bin/loginpage?t=wxm2-login&lang=zh_CN");
                this.close();
                Diamsg({
                    content:"请在新页面绑定微信公众号！<br><span>是否已绑定成功？</span>",
                    ok:function(){
                        this.close();
                        window.open("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherWeChatSyncEdit&pageId="+pageId);
                    },
                    okVal:"绑定成功",
                    cancelVal:"取消",
                    cancel:function(){}
                });

                
            },
            okVal:"前去绑定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }else if(attr=="addstock"){
        var _this=$scope.that.closest("tr").find("td .card_totle");
        var html=template('add_stock',{});
        art.dialog({
            title:"增加库存",
            content:html,
            width:400,
            ok:function(){
                var num=_this.html();
                var addnum=$("#addstock").val()
                var totle=parseInt(num)+parseInt(addnum);
                var data="page_id="+pageId;
                $http.post("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucher",data,{
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
                }).success(function(data){
                    //库存添加成功
                    if (data.status == 1) {
                         _this.html(totle);
                         Diasucceed(data.info);  
                    }else{
                         Diaerror(data.info);  
                    }
                });
                
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }else if(attr=="detailed"){
        window.open("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherDetailed&pageId="+pageId);
    }else if(attr=="delete"){
        var html=$scope.that.html();
        Diamsg({
            content:"请确认是否"+html+"？",
            ok:function(){
                this.close();
                Diasucceed("已"+html);
                var url=" ";
                var data="pageId="+pageId;
                $http.post(url,data,{
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
                }).success(function(){

                });
                $scope.that.closest('tr').remove();
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }
	
};
//*同步微信卡券*
$scope.syns_wechat_card=function(target){
    var _this=angular.element(target);
	var status=_this.attr("sync-status");//同步状态
	var pageId=_this.attr("page-id");//页面id
	if(status==0){
		

	}else{
		window.open("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherEdit&pageId="+pageId);
	}
	Gform();
};


});
/*************现金抵用券******列表页****结束******/
/*************创建--编辑---抵用券**********开始******/
var app = angular.module('CashVoucherEdit', []);
app.controller('CashVoucherEdit',["$scope","$http","$cacheFactory",function($scope,$http,$cacheFactory){
    $scope.status=0;//0为创建 1位修改 编辑 初始设置 
    $scope.syns_wechat_card=0;// 0为未同步 1为已同步 初始设置
    $scope.available_goods=0;//选择参与商品  0为全部 1为部分商品
    $scope.shoplist_tr=false;//默认无商品 添加商品操作
    $scope.use_way=0;//使用方式，0、多乐互动发放 ，1、微信公众号发放
    $scope.for_friends=0;//是否可以转增好友 0、可以 1、不可以
    var url="index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherEdit";
    var href=window.location.href
    var index=href.indexOf("pageId=");
    var pageid=href.substring(index-1);
    if(index=="-1"){//等于-1为创建抵用券
        $scope.status=0;
        $scope.syns_wechat_card=1;  
        $http.get(url).success(function(data){
            if(data.resultCode==0){
                $scope.syns_wechat_card=data.info.syns_wechat_card;           
            }else{
                //Diaerror(data.info);
            }    
        }); 
       
    }else if(index!="-1"){//编辑抵用券页面
        $http.get(url+pageid).success(function(data){
            $scope.status=1;
            $scope.syns_wechat_card=1;
            if(data.resultCode==0){
                $scope.syns_wechat_card=data.info.syns_wechat_card; 
                $scope.content=data.info.content; 
                $scope.card_status=false;           
            }else{
                 Diaerror(data.info);
            }    
        });    
    }
//添加商品
//再次执行


var key=10;
var value=10;
$scope.keys = [];
$scope.cache = $cacheFactory('cacheId');
$scope.addCommodity=function(){
    art.dialog({
        title:"增加商品",
        content:"增加商品"+value,
        width:400,
        ok:function(){
            $scope.shoplist_tr=true;//默认无商品
            $scope.cache.put(key, value);
            $scope.keys.push(key);
            $scope.$apply();//手动更新scope数据
            key++; 
            value++;
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });
    // art.dialog.open('http://www.baidu.com',{
    //     title:"增加商品",
    //     width:400,
    //     ok:function(){
    //         $scope.shoplist_tr=true;//默认无商品
    //         var value="222";
    //         $scope.cache.put(key, value);
    //         $scope.keys.push(key);
    //         key++;  
    //     },
    //     okVal:"确定",
    //     cancelVal:"取消",
    //     cancel:function(){}
    // });
}



// 默认隐藏可选商品
var date=new Date();
var nowDay=date.getFullYear()+'-'+(date.getMonth()+1)+'-'+date.getDate();
$scope.start_time=nowDay;
$scope.end_time=nowDay;
//请求背景数据
$http.get("./Home/Public/Js/wechat_card_bg.json").success(success);
function success(data,success){
    if(data.status==1){
        var card_bg=data.info;
        $scope.card_bg=card_bg;
        $scope.card_color=card_bg[0].id;
    }
}
//切换背景
$(".Sselect>div").click(function(){
    $(this).closest(".Sselect").toggleClass("hover");
})
$("body").on("click",".Sselect>ul li",function(){
    alert();
    $(this).closest(".Sselect").find("span i").attr("class",$(this).attr("data-val"));
    $(this).closest(".Sselect").find("input").val($(this).attr("data-val"));
    $("#firstMain").css("background",$(this).find("i").css("background"));
    $(".Interface .InterfaceContent a.btn").css("background",$(this).find("i").css("background"));
    $(this).closest(".Sselect").toggleClass("hover");
});

//创建抵用券----可选商品-------删除操作
$scope.delete=function(target){
	var _this=angular.element(target);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		_this.parent().parent().remove();
    		if(_this.closest('tr').length<2){			
		    	$scope.shoplist_tr=false;
		    }
		    		
    		Diasucceed("已"+thishtml);  
    		
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });   
}

// sumbit提交数据
$scope.submit=function(){
	var formActive=$("form[name='CashVoucherEdit']");
	formActive.validationEngine({
	    promptPosition:"topLeft:5,0",
	    scroll:false,
	    focusFirstField: false
	});
	var t = formActive.validationEngine("validate");
	if(!t) return false;
	var data=formActive.serialize();
	$http.post("./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucherEdit",data,{
	        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
	}).success(function(data,success){
        dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
        if(success=="200"){
             dialog.close();
            /*开始处理数据*/
            if(data.status==1){
                
            }else{
                Diaerror(data.info);
            }
        }
    }); 

}
// 取消
$scope.cancel=function(){
    Diamsg({
		content:"您确定取消吗",
    	ok:function(){
    		window.location.href="./index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucher";
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
	});
}
//再次执行
setTimeout(function(){
        Gformbegin();
},1);


}]);
/*************创建抵用券**********结束******/


/*************抵用券明细**********开始******/
var app = angular.module('CashVoucherDetailed', []);
app.controller('CashVoucherDetailed',["$scope","$http","$cacheFactory",function($scope,$http,$cacheFactory){



// 选项卡调用
WcanalTabonNew(".WcanalNew-tab",".Wcanal-tab-title",".Wcanal-tab-list");
}]);
/*************抵用券明细**********结束******/


/*************编辑抵用券--微信同步**********结束******/


/*************现金抵用券******单条发放****开始******/
var app = angular.module('CashVoucherPhoneSend', []);
app.controller('CashVoucherPhoneSend',["$scope","$http","$templateCache",function($scope,$http,$templateCache){

    $("#shortMsg_config").focus(function(){
        $(".prizeConfig_usage").removeClass("dn");
    })
    $("#shortMsg_config").blur(function(){
        $(".prizeConfig_usage").addClass("dn");
    })

    $("#usage_config").focus(function(){
		$("#phone").hide();
        $(".prizeConfig_usage").removeClass("dn");
    })
    $("#usage_config").blur(function(){
        $(".prizeConfig_usage").addClass("dn");
		$("#phone").show();
    })

}]);

/*************现金抵用券******单条发放****结束******/

/*************现金抵用券******批量发放****开始******/
var app = angular.module('CashVoucherPhoneSendMore', []);
app.controller('CashVoucherPhoneSendMore',["$scope","$http","$templateCache",function($scope,$http,$templateCache){

    
    // sumbit提交数据
$scope.submit=function(){
    var formActive=$("form[name='PhoneSendMore']");
    formActive.validationEngine({
        promptPosition:"topLeft:5,0",
        scroll:false,
        focusFirstField: false
    });
    var t = formActive.validationEngine("validate");
    if(!t) return false;
    var data=formActive.serialize();
    $http.post("",data,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
    }).success(function(data,success){
        dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
        if(success=="200"){
             dialog.close();
            /*开始处理数据*/
            if(data.status==1){
                
            }else{
                Diaerror(data.info);
            }
        }
    }); 

}
// 取消
$scope.cancel=function(){
    Diamsg({
        content:"您确定取消吗?<br/><span>取消后编辑内容将丢失!</span>",
        ok:function(){
            window.history.go(-1);
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });
}


//再次执行
setTimeout(function(){
        Gformbegin();
},1);

}]);

/*************现金抵用券******批量发放****结束******/

/*************粉丝回馈******列表页****开始******/
var app = angular.module('fans', []);
app.controller('fans',["$scope","$http","$templateCache",function($scope,$http,$templateCache){

//设置列表数据显示规则
$scope.tr_lg=angular.element(".FanFeedback").find("tbody tr").length;
if($scope.tr_lg>0){
  $scope.nonedata=false;	
}else{
  $scope.nonedata=true;
}

//编辑
$scope.edit=function(target){
	var _this=angular.element(target);
	var pageId=_this.attr("page-id");//页面id
    window.open("./index.php?g=Ecshop&m=SalePro&a=FansEdit&pageId="+pageId);
}
	
//明细
$scope.detailed=function(){
	var html=$templateCache.get('fansbackdetailed');
	art.dialog({
    	title:"明细",
    	content:html,
    	width:500,
    	cancelVal:"下载领取明细",
    	cancel:function(){}
    });
};
//链接
$scope.cardlink=function(){
	var html=$templateCache.get('cardlink');
	art.dialog({
    	title:"链接",
    	content:html,
    	width:500,
    	cancelVal:"关闭",
    	cancel:function(){}
    });
   var cvalue;
    $(".clippy").zclip({
        path: "Home/Public/Js/ZeroClipboard.swf",
        copy: function() {
            cvalue = $(".urldetail").val();
            return cvalue;
        },
        afterCopy: function() {
            Diasucceed("已复制，内容为"+cvalue); 
        }
    });  

   
};

        
//*删除操作*
$scope.delete=function(target){
	var _this=angular.element(target);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		_this.closest('tr').remove();
    		Diasucceed("已"+thishtml); 
    		if($(".tbody").find("tr").length<1){
                $scope.nonedata=true;
                $scope.$apply();
    		} 
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });


}
//*开启关闭*
$scope.switch=function(target){
	var _this=angular.element(target);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		Diasucceed("已"+thishtml);
    		if(thishtml=="启用"){
    		  _this.html("停用"); 
    		}else{
    		  _this.html("启用"); 
    		}
    		 
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    	
    });


};


}]);
/*************粉丝回馈******列表页********结束******/

/*************粉丝回馈******创建****开始******/
var app = angular.module('CreatFans', []);
app.controller('CreatFans',["$scope","$http","$templateCache",function($scope,$http,$templateCache){

$scope.guide=1;//引导关注 0关闭 1开启
$scope.available_goods=1;//选择参与商品 0全部 1 部分
// 设置短信提示
$scope.notice=function(){
    var html=$templateCache.get("notice");
    art.dialog({
        title:"短信提示",
        content:html,
        ok:function(){
            
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}



    });
}
	// sumbit提交数据
$scope.submit=function(){
	var formActive=$("form[name='creatFans']");
	formActive.validationEngine({
	    promptPosition:"topLeft:5,0",
	    scroll:false,
	    focusFirstField: false
	});
	var t = formActive.validationEngine("validate");
	if(!t) return false;
	var data=formActive.serialize();
	$http.post("",data,{
	        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
	}).success(function(data,success){
        dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
        if(success=="200"){
             dialog.close();
            /*开始处理数据*/
            if(data.status==1){
                
            }else{
                Diaerror(data.info);
            }
        }
    }); 

}
// 取消
$scope.cancel=function(){
    Diamsg({
		content:"您确定取消吗",
    	ok:function(){
    		window.history.go(-1);
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
	});
}

//再次执行
setTimeout(function(){
        Gformbegin();
},1);


}]);
/*************粉丝回馈******创建********结束******/



/*************粉丝回馈******编辑****开始******/
var app = angular.module('FansEdit', []);
app.controller('FansEdit',["$scope","$http","$templateCache",function($scope,$http,$templateCache){
var url="";
var href=window.location.href
var index=href.indexOf("pageId=");
var pageId=href.substring(index);
$http.post(url,pageId,{
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
}).success(function(data,success){
   //dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
   var data={"status":"1",
              "info":{
                    "node_name":"五一劳动节",
                    "start_time":"2016-15-16",
                    "end_time":"2016-15-16",
                    "guide":"1",
                    "available_goods":"1",
                    "keys":[
                        {"key":"111","value":"2222"},
                        {"key":"222","value":"2222"}
                    ]
                    
                }
            };
    if(success!="200"){
         dialog.close();
        /*开始处理数据*/
        if(data.status==1){
            var info=data.info;
            
            $scope.node_name=info.node_name;
            $scope.start_time=info.start_time;
            $scope.end_time=info.end_time;
            $scope.guide=info.guide;
            $scope.available_goods=info.available_goods;
            $scope.keys=info.keys;

           
       
        }else{
            
        } 
    }      
}); 


// 设置短信提示
$scope.notice=function(){
    var html=$templateCache.get("notice");
    art.dialog({
        title:"短信提示",
        content:html,
        ok:function(){
            
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}



    });
}

	// sumbit提交数据
$scope.submit=function(){
	var formActive=$("form[name='fans']");
	formActive.validationEngine({
	    promptPosition:"topLeft:5,0",
	    scroll:false,
	    focusFirstField: false
	});
	var t = formActive.validationEngine("validate");
	if(!t) return false;
	var data=formActive.serialize();
	$http.post("",data,{
	        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
	}).success(function(data,success){
        dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
        if(success=="200"){
             dialog.close();
            /*开始处理数据*/
            if(data.status==1){
                
            }else{
                Diaerror(data.info);
            }
        }
    }); 

}
// 取消
$scope.cancel=function(){
    Diamsg({
		content:"您确定取消吗",
    	ok:function(){
    		window.history.go(-1);
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
	});
}
	//再次执行
setTimeout(function(){
        Gformbegin();
},1);

}]);
/*************粉丝回馈******编辑********结束******/


/*************满送减页面******列表****开始******/
var app = angular.module('Full', []);
app.controller('Full',["$scope","$http","$templateCache",function($scope,$http,$templateCache){

//设置列表数据显示规则
$scope.tr_lg=angular.element(".full").find("tbody tr").length;
if($scope.tr_lg>0){
  $scope.nonedata=false;	
}else{
  $scope.nonedata=true;
}

$scope.edit=function(target){
    var _this=angular.element(target);
    var pageId=_this.attr("page-id");
	window.open("./index.php?g=Ecshop&m=SalePro&a=FullEdit&pageId="+pageId);
}

//*删除操作  
$scope.delete=function(target){
    var _this=angular.element(target);
	var thishtml=_this.html();
	//console.log(_this.closest('tbody').find("tr"));
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();

    		_this.closest('tr').remove();
    		Diasucceed("已"+thishtml); 
  
    		if($(".tbody").find("tr").length<1){
                $scope.nonedata=true;
                $scope.$apply();
    		}
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });

};

//*开启关闭
$scope.switch=function(target){
    var _this=angular.element(target);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		Diasucceed("已"+thishtml);
    		if(thishtml=="启用"){
    		  _this.html("停用"); 
    		}else{
    		  _this.html("启用"); 
    		}
    		 
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    	
    });
};

//*订单信息
$scope.order_info=function(target){
    var _this=$(this);
    //var html=template("OrderInfo",{});
    var html=$templateCache.get("OrderInfo");
    art.dialog({
        title:"订单信息",
        content:html,
        id:"testID2",
        width:800,
        ok:function(){  
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });
}

	
	


//*订单信息*
$("body").on("click",".print",function(){
	art.dialog.list["testID2"].close();
	var html=template("printOrder",{});
	art.dialog({
    	title:"打印订单",
    	content:html,
    	height:"500px",
    	width:800,
    	ok:function(){  		
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });
	
});




}]);
/*************满送减页面******列表********结束******/

/*************满减规则******创建****开始******/
var app = angular.module('CreatFull', []);
app.controller('CreatFull',["$scope","$http","$templateCache",function($scope,$http,$templateCache){


$scope.shoplist_tr=false;

$scope.AddRule=function(){
    var RulList=$(".W-table.RulList");
    $scope.RulList=true;
    var RulList_tr=$(".W-table.RulList").find("tr.list");
    var index=RulList_tr.length+1;
    var html=template("rulelist",{index:index});
    
    if(RulList_tr.length<=4){
       RulList.append(html);
    }else{
        Diasucceed("最多可添加五条规则");
    }
};

//满减规则----删除操作-----返现条件
$("body").on("click",".newFull.Gform ul li .RulList.W-table tr td .backCashCard .shoplist.W-table tr td .delete",function(){
    var _this=$(this);
    var thishtml=_this.html();
    Diamsg({
        content:"请确认是否"+thishtml+"？",
        ok:function(){
            this.close();
            _this.closest('tr').remove();
            if(_this.closest('tr').length<2){
                
                $(".backCashCard .shoplist.W-table").addClass('dn');
            }
                    
            Diasucceed("已"+thishtml);  
            
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });

    
});

//满减规则----删除操作-----删除规则
$("body").on("click",".newFull.Gform>ul>li>.RulList.W-table>tbody>tr>td>.delete",function(){
    var _this=$(this);
    var thishtml=_this.html();
    Diamsg({
        content:"请确认是否"+thishtml+"？",
        ok:function(){
            this.close();
            _this.closest('tr').remove();
            if(_this.closest('tr').length<2){
                
                $(".RulList.W-table").addClass('dn');
            }
                    
            Diasucceed("已"+thishtml);  
            
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });

    
});

//满减规则----选择参与商品删除操作
$("body").on("click",".newFull.Gform>ul>li .switch .shoplist.W-table tr td .delete",function(){
    var _this=$(this);
    var thishtml=_this.html();
    Diamsg({
        content:"请确认是否"+thishtml+"？",
        ok:function(){
            this.close();
            _this.closest('tr').remove();
            if(_this.closest('tr').length<2){
                
                $(".shoplist.W-table").addClass('dn');
            }
                    
            Diasucceed("已"+thishtml);  
            
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });

    
});

//满减规则----返现条件----添加商品

$("body").on("click",".newFull .W-table.RulList .backadd",function(){
    art.dialog.open("http://www.wangcaio2o.com",{
        title:"添加商品",
        width:800,
        height:500,
        ok:function(){},
        okVal:"确定",
        cancel:function(){},
        cancelVal:"取消"
    });
});
//满减规则----选择参与商品----添加商品

$scope.add_Commodity=function(){
    var that=$(this);
    var html=template("listshop",{});
    
    art.dialog.open("http://www.wangcaio2o.com",{
        title:"添加商品",
        width:800,
        height:500,
        ok:function(){
            $scope.shoplist_tr=true;

        },
        okVal:"确定",
        cancel:function(){},
        cancelVal:"取消"
    });


};


//满减规则----设置返现条件
$("body").on("click",".W-table.RulList input[name^=mode]",function(){
    var that=$(this);
    var value=that.attr("checked");
    var dd=that.closest('dd')
    var dd_all=that.closest('div').find("dl dd");
    dd_all.removeClass("hover");
    dd.addClass('hover')    
});

    // sumbit提交数据
$scope.submit=function(){
    var formActive=$("form[name='fans']");
    formActive.validationEngine({
        promptPosition:"topLeft:5,0",
        scroll:false,
        focusFirstField: false
    });
    var t = formActive.validationEngine("validate");
    if(!t) return false;
    var data=formActive.serialize();
    $http.post("",data,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
    }).success(function(data,success){
        dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
        if(success=="200"){
             dialog.close();
            /*开始处理数据*/
            if(data.status==1){
                
            }else{
                Diaerror(data.info);
            }
        }
    }); 

}
// 取消
$scope.cancel=function(){
    Diamsg({
        content:"您确定取消吗",
        ok:function(){
            window.history.go(-1);
        },
        okVal:"确定",
        cancelVal:"取消",
        cancel:function(){}
    });
}
    //再次执行
setTimeout(function(){
        Gformbegin();
},1);




}]);
/*************满减规则******创建********结束******/
/*************满减规则******编辑****开始******/
var app = angular.module('FullEdit', []);
app.controller('FullEdit',["$scope","$http","$templateCache",function($scope,$http,$templateCache){
var url="";
var href=window.location.href
var index=href.indexOf("pageId=");
var pageId=href.substring(index);
$http.post(url,pageId,{
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
}).success(function(data,success){
   // dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
    if(success=="200"){
         //dialog.close();
        /*开始处理数据*/
        if(data.status==1){

           
       
        }else{
            
        } 
    }      
});       



$scope.shoplist_tr=false;

$scope.AddRule=function(){
    var RulList=$(".W-table.RulList");
    RulList.removeClass("dn");
    var RulList_tr=$(".W-table.RulList").find("tr.list");
    var index=RulList_tr.length+1;
    var html=template("rulelist",{index:index});
    
    if(RulList_tr.length<=4){
       RulList.append(html);
    }else{
        Diasucceed("最多可添加五条规则");
    }
};

//满减规则----删除操作-----返现条件
$("body").on("click",".newFull.Gform ul li .RulList.W-table tr td .backCashCard .shoplist.W-table tr td .delete",function(){
	var _this=$(this);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		_this.closest('tr').remove();
    		if(_this.closest('tr').length<2){
    			
		    	$(".backCashCard .shoplist.W-table").addClass('dn');
		    }
		    		
    		Diasucceed("已"+thishtml);  
    		
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });

    
});

//满减规则----删除操作-----删除规则
$("body").on("click",".newFull.Gform>ul>li>.RulList.W-table>tbody>tr>td>.delete",function(){
	var _this=$(this);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		_this.closest('tr').remove();
    		if(_this.closest('tr').length<2){
    			
		    	$(".RulList.W-table").addClass('dn');
		    }
		    		
    		Diasucceed("已"+thishtml);  
    		
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });

    
});

//满减规则----选择参与商品删除操作
$("body").on("click",".newFull.Gform>ul>li .switch .shoplist.W-table tr td .delete",function(){
	var _this=$(this);
	var thishtml=_this.html();
	Diamsg({
		content:"请确认是否"+thishtml+"？",
    	ok:function(){
    		this.close();
    		_this.closest('tr').remove();
    		if(_this.closest('tr').length<2){
    			
		    	$(".shoplist.W-table").addClass('dn');
		    }
		    		
    		Diasucceed("已"+thishtml);  
    		
    	},
    	okVal:"确定",
    	cancelVal:"取消",
    	cancel:function(){}
    });

    
});

//满减规则----返现条件----添加商品

$("body").on("click",".newFull .W-table.RulList .backadd",function(){
	art.dialog.open("http://www.wangcaio2o.com",{
		title:"添加商品",
		width:800,
		height:500,
		ok:function(){},
		okVal:"确定",
		cancel:function(){},
		cancelVal:"取消"
	});
});
//满减规则----选择参与商品----添加商品

$scope.add_Commodity=function(){
    var that=$(this);
    var html=template("listshop",{});
    
    art.dialog.open("http://www.wangcaio2o.com",{
        title:"添加商品",
        width:800,
        height:500,
        ok:function(){
            $scope.shoplist_tr=true;

        },
        okVal:"确定",
        cancel:function(){},
        cancelVal:"取消"
    });


};


//满减规则----设置返现条件
$("body").on("click",".W-table.RulList input[name^=mode]",function(){
	var that=$(this);
	var value=that.attr("checked");
	var dd=that.closest('dd')
    var dd_all=that.closest('div').find("dl dd");
    dd_all.removeClass("hover");
	dd.addClass('hover')	
});




}]);
/*************满减规则******编辑********结束******/

