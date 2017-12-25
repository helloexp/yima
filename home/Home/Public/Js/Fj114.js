/************福建号码百事通---入口页面*************/
var app = angular.module('index', []);
app.controller('index',["$scope",function($scope){

    var mySwiper = new Swiper('.swiper-container',{
        autoplay : 4000,//可选选项，自动滑动
        loop : true,//可选选项，开启循环
        pagination : '.pagination',
        paginationClickable :true,
    })

}]);
/************福建号码百事通---创建卡券页面*************/
var app = angular.module('CreatCard', []);
app.controller('CreatCard',["$scope","$http","$rootScope",function($scope,$http,$rootScope){

    var href=window.location.href
    var index=href.indexOf("id=");
	var pageid=href.substring(index-1);
    if(index=="-1"){//等于-1为创建卡券
        $scope.goodsid =0;   
        $rootScope.mymodel = {"creaType":"0","name":"","details":"该内容将显示在验证后的打印小票上，营业员根据打印小票内容提供服务","notice":"例：收到卡券后30天内可使用","days":"30","img":"Home/Public/Image/Onhook/default.png"};
        setTimeout(function(){
                Gformbegin();
        },1);
    }else if(index!="-1"){//编辑页面
        var url="./index.php?g=Fj114&m=UserCode&a=createCode_data"+pageid;
        $http.get(url).success(function(data){
            $scope.goodsid =1;
            if(data.resultCode==0){
                $rootScope.mymodel =data.resultTxt;         
            }
            setTimeout(function(){
                    Gformbegin();
            },1);
        }); 
          
    }
   $scope.phoneCon=false;
  //修改显示内容
  $scope.Wtab_phone=function(target){
    $rootScope.data();
    var that=angular.element(target);
    var thatAttr=that.attr("data-tab");
    var tab=angular.element(".phoneCon").find(".wx-show");
    if(thatAttr==0){
       $scope.phoneCon=false; 
    }else if(thatAttr==1){
       $scope.phoneCon=true; 
    }
    
  }
  // 设置有效期
 
  $rootScope.data=function(){
    var date=new Date();
    var nowDay=date.getFullYear()+'-'+(date.getMonth()+1)+'-'+(date.getDate()+1);
    var limit=new Date(date.getTime()+$rootScope.mymodel.days*24*60*60*1000);
    $rootScope.start_time=nowDay;//生成现在时间
    $rootScope.end_time=limit.getFullYear()+'-'+(limit.getMonth()+1)+'-'+limit.getDate();//生成有效期之后时间

  }
  
$(".card_view").attr('src',$('.Gchoose>img').attr('src'));
//上传卡券图片
  $(".Gchoose .Gbtn-picN i input[name='img']").on("change",function() {
     //var url="./index.php?g=Fj114&m=UserCode&a=uploadImg";
    var fileName=$(this).val();
    $("#CreatCard").ajaxSubmit({ 
        success: function (data) {
            data = eval("("+data+")");
            if(data['resultCode'] == '0'){
                $('.Gchoose .Gchoose-opr .Gchoose-opr-img img').attr('src',data['url']);
                $('.Gchoose>img').attr('src',data['url']);
                $('.Gchoose>input[name=imgPath]').val(data['path']);
                $(".card_view").attr('src',data['url']);
                Diasucceed("卡券图片更换成功");
            }else{
                Diaerror(data.resultTxt);
            }
        },
    });


      
  });

//设置默认模板
$scope.change = function(type){

   
        var creatCode={
                    "default": {
                        "name":"",
                        "details":"该内容将显示在验证后的打印小票上，营业员根据打印小票内容提供服务",
                        "notice":"例：收到卡券后30天内可使用",
                        "days":"30",
                        "img":"Home/Public/Image/Onhook/default.png"
                    },
                    "vouchers": {
                        "name": "10元代金券",
                        "details": "凭此券消费抵10元",
                        "notice": "10元代金券一张，收到后30天内到店出示此券即可抵用",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "voucher": {
                        "name": "商品抵用券",
                        "details": "凭此券可兑换指定商品一份",
                        "notice": "收到后30天内到店出示此券，即可抵用指定商品一份",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "discount": {
                        "name": "八折券",
                        "details": "凭此券享8折优惠",
                        "notice": "收到后30天内到店出示此券，即可享八折优惠",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "beverage": {
                        "name": "饮料一扎",
                        "details": "凭此券获赠指定饮料一扎",
                        "notice": "收到后30天内到店出示此券，即可获赠指定饮料一扎",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "fruit": {
                        "name": "果盘一份",
                        "details": "凭此券获赠果盘一份",
                        "notice": "收到后30天内到店出示此券，即可获赠果盘一份用",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "parking": {
                        "name": "停车券",
                        "details": "凭此券免费停车两小时",
                        "notice": "收到后30天内到店出示此券，即可免费停车两小时",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    },
                    "wine": {
                        "name": "特色酒品",
                        "details": "凭此券获赠指定酒水一瓶",
                        "notice": "收到后30天内到店出示此券，即可获赠指定酒水一瓶",
                        "days": "30",
                        "img": "Home/Public/Image/Onhook/default.png"
                    }
                };
        $rootScope.mymodel = creatCode[type];
        $rootScope.data();

   
}

// 创建卡券下一步
$scope.submit=function(target){
    var url='./index.php?g=Fj114&m=UserCode&a=createCard';
    var formActive=$("#CreatCard");
    formActive.validationEngine({
        promptPosition:"topLeft:5,0",
        scroll:false,
        focusFirstField: false
    });
    // 验证表单操作
    var t = formActive.validationEngine("validate");
    if(!t) return false;
    if($scope.goodsid==0){
        var list={"list":$scope.mymodel.name};
        var html=template("modifySet",list);
        art.dialog({
            title:$scope.mymodel.name+"卡券-发送时间",
            content:html,
            ok:function(){
                var postData = formActive.serialize()+'&'+$("#SendSet").serialize();
                $http.post(url,postData,{
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
                }).success(function(data){
					if(data.resultCode=='0'){
						Diasucceed("卡券创建成功"); 
                        window.location.href="./index.php?g=Fj114&m=UserCode&a=codeSendSetedList";       
					}else{	
						Diaerror(data.resultTxt);
					}
                });
            },
            okVal:"确定",
            cancelVal:"跳过",
            cancel:function(){
                var postData = formActive.serialize()+"&codeSendSet=0";     
                $http.post(url,postData,{
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
                }).success(function(data){
                    if(data.resultCode==0){
						Diasucceed("卡券创建成功"); 
                        window.location.href="./index.php?g=Fj114&m=UserCode&a=codeList";    
					}else{ 
                        Diaerror(data.resultTxt);
                    }              
                });
            }
        });
    }else{
        var postData = formActive.serialize()+"&codeSendSet=0";        
        $http.post(url,postData,{
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data){
            if(data.resultCode==0){
				Diasucceed("卡券修改成功");  
                window.location.href="./index.php?g=Fj114&m=UserCode&a=codeList";   
			}else{   
                Diaerror(data.resultTxt);
            }            
        });
    }
    
    
};

$scope.cancel=function(){
    window.history.go(-1);
}


}]);
var app = angular.module('package', []);
app.controller('package',function($scope,$http){
     var url="./index.php?g=Fj114&m=UserCode&a=Packages";
    $http.get(url).success(function(data){
        $scope.packages=data.info;
    });

    
 
});
var app = angular.module('eposset', []);
app.controller('eposset',function($scope,$http){
    $scope.EposSet=true;
    var url='./index.php?g=Fj114&m=UserCode&a=EposSet';
    $scope.submit=function(){
        var formActive=angular.element("form[name='EposSet']");
       formActive.validationEngine({
            promptPosition: "topLeft:5,0",
            scroll: false,
            focusFirstField: false
        });
        var t = formActive.validationEngine("validate");
        if (!t){return false;}

        var postData = formActive.serialize();
        $http.post(url,postData,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data,success){
           dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
            if(success=="200"){
                 dialog.close();
                if(data['resultCode'] == '0'){
                    $scope.EposSet=false; 
                }else{
                    Diaerror("EPOS创建失败原因："+data['resultTxt']);
                }
            }      
        });     
     }
     $scope.ud_email=function(){
       $scope.EposSet=true; 
     }
   
 
});






    