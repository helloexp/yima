
prom.controller('home', function ($scope,$cookieStore,$http,$rootScope,$log) {
   $scope.PageData={
            type:'big',
            url:'/index.php?g=Ecshop&m=ElectronicCommerce&a=cardList'
        }; 
    /*手机号发放*/    
    $scope.phone=function(t){
        var id=$(t).parent('td').find('input[name=pageId]').val();
        window.location.href='/view/ecs/prom/#/index/index_phone?id='+id;
    }
    /*增加库存*/
    $scope.addstock=function(t){
        var that=$(t);
        var _this=that.closest("tr").find("td .totle");
        var pageId=that.closest('td').find("input[name=pageId]");
        var html=template('stock',{});
        art.dialog({
            title:"增加库存",
            content:html,
            width:400,
            ok:function(){
                var num=_this.html();
                var addnum=$("#addstock").val()
                var totle=parseInt(num)+parseInt(addnum);
                var data="page_id="+pageId;
                $http.post("/index.php?g=Ecshop&m=ElectronicCommerce&a=CashVoucher",data,{
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
                }).success(function(data){
                    //库存添加成功
                    if (data.status == 1) {
                         _this.html(totle);
                         Diasucceed(data.msg);  
                    }else{
                         Diaerror(data.msg);  
                    }
                });
                
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });
    }
    /*明细详情*/
    $scope.detiled=function(t){
        var id=$(t).parent('td').find('input[name=pageId]').val();
        window.location.href='/view/ecs/prom/#/index/index_detiled/base?id='+id;
    }
    /*删除*/
    $scope.delete=function(t){
        var that=$(t);
        var pageId=that.closest('td').find("input[name=pageId]");
        var html=that.html();
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
                that.closest('tr').remove();
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });
    }
})
/*电商优惠券编辑*/
prom.controller('cashEdit', function ($scope,$http,$state,$log) {
    var h=window.location.href; 
    var i=h.indexOf('id=');
    $scope.cashData={
        id:null,
        bonus_name:null,
        bonus_num:null,
        bonus_amt:null,
        use_amt:null,
        notice:null,
        type:null, //0全部 1部分
        goods_list:[],
        datatype:null,//0按日期1按天数
        start_time:null,
        end_time:null,
        later_start_time:null,
        later_end_time:null
    } 
    if(i>0){
        // 编辑
        $scope.edit=1;
        $scope.cashData={
            id:'169',
            bonus_name:'124222',
            bonus_num:'100',
            bonus_amt:'1234',
            use_amt:'15154',
            notice:'该方法不会改变现有的数组，而仅仅会返回被连接数组的一个',
            type:1, 
            goods_list:[{id:'125',goods_name:'而仅仅会'},{id:'1425',goods_name:'而仅副本仅会'}],
            datatype:0,
            start_time:'2016-06-25',
            end_time:'2016-07-25',
            later_start_time:null,
            later_end_time:null
        } 

        cashEdit_lista=$scope.cashData.goods_list;
        $scope.goods_list =cashEdit_lista;


        var id=h.substr(i);
        // $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=getCardInfo&'+id)
        // .success(function(data){
        //     if(data.code==0){              
        //        $scope.cashData=data.data.info; 
        //     }else{
        //        alert(data.msg);
        //     }
        // });
    }else{
        // 创建
        $scope.edit=0;
        if(cashEdit_lista.length>0){cashEdit_lista=[]}
        $scope.goods_list =cashEdit_lista;
    } 
    $scope.card_name='电商优惠券'; 
    /*添加商品*/
    $scope.addCommodity=function(){
        art.dialog.open("/index.php?g=Ecshop&m=GoodsPutOn&a=SelectRgoods&cb_init=cb_rgoodsinit&cb_rgoodsadd=cashEdit&cb_rgoodsdel=cashEdit",{
            title:"添加商品",
            width:800,
            height:500
        });
    };
    /*删除商品*/
    $scope.deleteShop=function(t){
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){
                $scope.$apply(function(){
                    $.each($scope.goods_list,function(i,n){
                        if(t == n){
                            $scope.goods_list.splice(t,1);
                        }
                    });
                })                      
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }
    /*提交数据*/
    $scope.submit=function(){
        var formActive=$("form");
        formActive.validationEngine({
            promptPosition:"topLeft:5,0",
            scroll:false,
            focusFirstField: false
        });
        var t = formActive.validationEngine("validate");
        if(!t) return false;
        var goods_list=null;
        for(var i=0;i<$scope.list.length;i++){
            goods_list.push($scope.list[i].id);
        }
        
        var data={
                bonus_name:$scope.cashData.bonus_name,
                bonus_num:$scope.cashData.bonus_num,
                bonus_amt:$scope.cashData.bonus_amt,
                use_amt:$scope.cashData.use_amt,
                notice:$scope.cashData.notice,
                goods_type:$scope.cashData.type, 
                goods_list:goods_list,
                datetype:$scope.cashData.datetype,
                begin_time:$scope.cashData.begin_time,
                end_time:$scope.cashData.end_time,
                later_start_time:$scope.cashData.later_start_time,
                later_end_time:$scope.cashData.later_end_time
            };
        $http.post('/index.php?g=Ecshop&m=ElectronicCommerce&a=saveCard',data,{
           headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data){
            console.log(data);
            if(data.code==0){
              Diasucceed("创建成功");
              $state.go('index');
            }else{
                alert(data.msg);
            }

        });
    }           
})

/*手机发放*/
prom.controller('phone', function ($scope,$http) {
    /*获取信息*/
    var h=window.location.href; 
    var i=h.indexOf('id=');
    var id=h.substr(i);
    $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=getCardInfo&'+id)
        .success(function(data){
            if(data.code==0){              
               $scope.phoneData=data.data.info; 
            }else{
               alert(data.msg);
            }
        });

     /*提交数据*/
    $scope.submit=function(){
        var formActive=$("form");
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
             
        
})
/*明细基本信息*/
prom.controller('detiled_base', function ($scope,$http,$rootScope) {
    var h=window.location.href; 
    var i=h.indexOf('id=');
    var id=h.substr(i);
    $rootScope.detiled_pageid=id;
    /*获取信息*/
    $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=getCardInfo&'+id)
    .success(function(data){
        if(data.code==0){              
           $scope.phoneData=data.data.info; 
        }else{
           alert(data.msg);
        }
    });


})  

/*明细 库存变动*/
prom.controller('detiled_move', function ($scope,$state,$http,$cookieStore,$rootScope) {
    /*获取信息*/
    $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=getCardInfo&'+$rootScope.detiled_pageid)
    .success(function(data){
        if(data.code==0){              
           $scope.phoneData=data.data.info; 
        }else{
           alert(data.msg);
        }
    });
    $scope.moveInfo=function(t){
        var id=$(t).parent('td').find('input[name=pageId]').val();
        $rootScope.movepageId=id;
        $state.go('index.detiled.info');
    }
}) 

/*明细 使用记录*/
prom.controller('detiled_info', function ($scope,$rootScope,$http) {
    /*获取信息*/
    $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=getCardInfo&'+$rootScope.detiled_pageid)
    .success(function(data){
        if(data.code==0){              
           $scope.phoneData=data.data.info; 
        }else{
           alert(data.msg);
        }
    });

}) 



/*优惠券发放*/
prom.controller('fans', function ($scope,$http) {

   /*定义请求参数*/
   $scope.PageData={
        type:'big',
        url:'/index.php?g=Ecshop&m=ElectronicCommerce&a=fullList'
    };

    //明细
    $scope.detailed=function(){
        var html=$('#fansbackdetailed').html();
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
        var html=$('#cardlink').html();
        art.dialog({
            title:"链接",
            content:html,
            width:500,
            cancelVal:"关闭",
            cancel:function(){}
        });
       var cvalue=null;
        $(".clippy").zclip({
            path: "/view/assets/js/ZeroClipboard.swf",
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
    $scope.delete=function(t){
        var _this=angular.element(t);
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){
                _this.closest('tr').remove();
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
    $scope.switch=function(t){
        var _this=$(t);
        var id=_this.parent('td').find('input[name=pageId]').val();
        var html=_this.html();
        Diamsg({
            content:"请确认是否"+html+"？",
            ok:function(){
                this.close();
                Diasucceed("已"+html);
                if(html=="启用"){
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
     

            
})
/*优惠券发放编辑*/
prom.controller('fansEdit', function ($scope,$http) {
    var h=window.location.href; 
    var i=h.indexOf('id=');
     
    if(i){
        var id=h.substr(i); 
      //   $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=cardEdit&'+id).success(function(data){
      //       if(data.code==0){
      //          $scope.edit=1;
      //          $scope.cashData=data.data; 
      //       }else{
      //          alert(data.msg);
      //       }
      // });
    }else{
      $scope.edit=0;
      if(listb.length>0){listb=[]}
      $scope.list = listb;
    } 
    $scope.activename='活动名称'; 
  
    $scope.addCommodity=function(){
        art.dialog.open("/index.php?g=Ecshop&m=GoodsPutOn&a=SelectRgoods&cb_init=cb_rgoodsinit&cb_rgoodsadd=fansEdit&cb_rgoodsdel=fansEdit",{
            title:"添加商品",
            width:800,
            height:500
        });
    };
    /*删除*/
    $scope.deleteShop=function(t){
        var _this=$(t);
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){
               $scope.$apply(function(){
                    $.each($scope.goods_list,function(i,n){
                        if(t == n){
                            $scope.goods_list.splice(t,1);
                        }
                    });
                })  
                
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }
    /*提交*/
    $scope.submit=function(){
        var data=$('form').serialize();
        $http.post('',data,{
           headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data){

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
})



/*满送/减*/
prom.controller('full', function ($scope,$state) {

    $scope.PageData={
        type:'big',
        url:'/index.php?g=Ecshop&m=ElectronicCommerce&a=fullList'
    };

    //*删除操作  
    $scope.delete=function(t){
        var _this=$(t);
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){

            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    };

    //*开启关闭
    $scope.switch=function(t){
        var _this=angular.element(t);
        var h=_this.html();
        Diamsg({
            content:"请确认是否"+h+"？",
            ok:function(){
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
        var html=template("OrderInfo",{});
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
            height:500,
            width:800,
            ok:function(){          
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });
        
    });       
	 	
})
/*满送/减编辑*/
prom.controller('fullEdit', function ($scope,$cookieStore,$templateCache,$timeout,$location) {
    var h=window.location.href; 
    var i=h.indexOf('id=');
    var id=h.substr(i);   
    if(i){
        $http.get('/index.php?g=Ecshop&m=ElectronicCommerce&a=fullEdit&'+id).success(function(data){
            if(data.code==0){
               $scope.edit=1;
               $scope.cashData=data.data; 
            }else{
               alert(data.msg);
            }
      });
    }else{
      $scope.edit=0;
      if(listc.length>0){listc=[]}
      $scope.list = listc;
    } 
    $scope.activename='电商优惠券'; 
    $scope.rules = [{
                price:null,
                reduceCash:{
                    checked:false,
                    reduce:null
                },
                freeShipping:{
                    checked:false
                },
                giftsCard:{
                    checked:false,
                    name:null,
                    id:null
                },
                giftsGoods:{
                    checked:false,
                    name:null,
                    id:null
                }
            }];

    $scope.addRule=function(){
        if($scope.rules.length>4){
           Diasucceed("最多可添加五条规则");
           return false;
        }
        
        var newrule = {
                price:null,
                reduceCash:{
                    checked:false,
                    reduce:null
                },
                freeShipping:{
                    checked:false
                },
                giftsCard:{
                    checked:false,
                    name:null,
                    id:null
                },
                giftsGoods:{
                    checked:false,
                    name:null,
                    id:null
                }
            };
        $scope.rules.push(newrule)

       
     
    };

    //满减规则----删除操作-----返现条件
    $scope.deleteShop=function(t,type){
        var _this=$(t);
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){
                $scope.$apply(function(){
                    t.name=null;
                    t.id=null;
                })
                           
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });

    }
    //满减规则----删除操作-----删除规则
    $scope.deleteRule=function(t){
        Diamsg({
            content:"请确认是否删除？",
            ok:function(){
                $scope.$apply(function(){
                    $.each($scope.rules,function(i,n){
                        if(t == n){
                            $scope.rules.splice(t,1);
                        }
                    });
                })                          
            },
            okVal:"确定",
            cancelVal:"取消",
            cancel:function(){}
        });
        
    }

    $scope.list=listc;/*可选商品*/
    //满减规则----返现条件----添加商品
    $scope.addcard=function(t,type){
        art.dialog.open("/index.php?g=Common&m=SelectJp&a=index&callback=addcard_call_back&show_source=0&show_type=1,2",{
                title:"添加优惠券",
                width:800,
                height:500,
                close:function(){
                    $timeout(function() {
                        $scope.$apply(function(){
                            t['id']=addcard_call_back_data.goods_id;
                            t['name']=addcard_call_back_data.goods_name;
                        });
                    },300);
                }
            });

    }


     //满减规则----返现条件----添加卡券
    $scope.addShop=function(t,type){
        art.dialog.open("/index.php?g=Ecshop&m=GoodsPutOn&a=SelectRgoods&cb_init=cb_rgoodsinit&cb_rgoodsadd=addShop_call_back&cb_rgoodsdel=addShop_call_back",{
            title:"添加商品",
            width:800,
            height:500,
            close:function(){
                $timeout(function() {
                    $scope.$apply(function(){
                        t['id']=addShop_call_back_data.goods_id;
                        t['name']=addShop_call_back_data.goods_name;
                    });
                },300);
            }
            
        });
    }
    //满减规则----选择参与商品----添加商品
    $scope.addCommodity=function(){   
        art.dialog.open("/index.php?g=Ecshop&m=GoodsPutOn&a=SelectRgoods&cb_init=cb_rgoodsinit&cb_rgoodsadd=fullEdit&cb_rgoodsdel=fullEdit",{
            title:"添加商品",
            width:800,
            height:500
        });


    };

            
})
/*选择商品之后的回调函数列表*/

var addcard_call_back_data = null;
function addcard_call_back(t){
    addcard_call_back_data=t;
}
var addShop_call_back_data = null;
function addShop_call_back(t){
  addShop_call_back_data=t;
}
var listc = [];
function fullEdit(t){
    listc.push(t);
    $(".newFull .partShop").click();
}
var listb = [];
function fansEdit(t){
    listb.push(t);
    $(".fansEdit .partShop").click();
}
var cashEdit_lista = [];
function cashEdit(t){
    cashEdit_lista.push(t);
    $(".NewVoucher .partShop").click();
}

