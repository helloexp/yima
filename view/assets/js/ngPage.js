// 公用模块头部
angular.module('ngHead', []).directive('ngHead', function () {
    return {
        restrict : 'EA',
        replace : true,
        transclude : true,
        templateUrl : '/view/base/ngHeader.html',
        controller: function ($scope,$http) {
              $http.get('/index.php?g=Alipay&m=HuifuApi&a=getUser')
              .success(function(data){
                if(data.code==0){
                    $scope.user_name=data.data.user_name;
                    $scope.node_id=data.data.node_id;
                    $scope.node_name=data.data.node_short_name;
                    $scope.token=data.data.token;
                    $scope.user_id=data.data.user_id;
                }else{
                    window.location.href='/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+window.location.href;
                }
            });      
            
        }
    };
})
// 代理商管理系统头部
angular.module('ngHeadAgent', []).directive('ngHead', function () {
    return {
        restrict : 'EA',
        replace : true,
        transclude : true,
        templateUrl : '/view/agent/ngHeader.html',
        controller: function ($scope,$http) {
            $http.get('/index.php?g=Alipay&m=HuifuApi&a=getUser')
              .success(function(data){
                if(data.code==0){
                    $scope.user_name=data.data.user_name;
                    $scope.node_id=data.data.node_id;
                    $scope.node_name=data.data.node_short_name;
                    $scope.token=data.data.token;
                    $scope.user_id=data.data.user_id;
                }else{
                    window.location.href='/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+window.location.href;
                }
            });   
            
        }
    };
})
// 公用模块帮助
angular.module('ngAside', []).directive('ngAside', function () {
    return {
        restrict : 'EA',
        replace : true,
        transclude : true,
        templateUrl : '/view/base/ngAside.html',
        controller: function ($scope) {
            /*在线反馈*/
            $scope.onlinecontant=function(){
                art.dialog.open('/index.php?g=Home&m=Help&a=popupFeedback',{
                    id:'editordialog',
                    title:'在线反馈',
                    width:500
                });

            }
            /**/
            $('#gotop').click(function() {
                $('body,html').animate({
                    scrollTop : 0
                }, 800);
                return false;
            });


            $(".slidetoolbar-closebtn").click(function(e) {
                if (!$(this).hasClass("slideclosebtn-close")) {
                    $(".slidetoolbar").css("right", "-50px");
                    $(this).addClass("slideclosebtn-close");
                    $(this).attr('title', '展开');
                } else {
                    $(".slidetoolbar").css("right", "0");

                    $(this).removeClass("slideclosebtn-close");
                    $(this).attr('title', '收起');
                }
            });

            $(window).scroll(function() {
                t = $(document).scrollTop();
                if (t > 50) {
                    $('#gotop').fadeIn('slow');
                } else {
                    $('#gotop').fadeOut('slow');
                }
            })

            
        }
    };
})
// 公用模块底部
angular.module('ngFoot', []).directive('ngFoot', function () {
    return {
        restrict : 'EA',
        replace : true,
        transclude : true,
        templateUrl : '/view/base/ngFooter.html',
        controller: function ($scope,$timeout,$location) {
            $scope.footerFixed=function(){
                $timeout(function(){
                    if($(window).height()>$(document.body).height()){
                        $scope.ind_footer=true;
                    }else{
                        $scope.ind_footer=false;
                    }
                },300);
            }
            $scope.footerFixed();   
    
        }
    };
})
// 分页
angular.module('pagination', ['ui.setspectime']).directive('pagination', function() {
  return {
    restrict : 'EA',
    replace : true,
    transclude : true,
    templateUrl : '/view/base/pagination.html',
    controller: function ($scope,$http,$cookieStore,$log,$timeout,setspectime){
        $scope.page=1;//初始页数 设置为1;  
        /*设置分页类型 常规页面 or 弹窗*/

        // $scope.PageData={
        //     type:'big',//big表示常规页面
        //     showPage:''//分页地方显示个数
        //     rowsPage:''//显示条数 对应开发参数limit
        //     url:'/index.php?g=Ecshop&m=ElectronicCommerce&a=cardList' //请求地址
        // }; 

        if($scope.PageData.type == "big"){
            $scope.pagination_type=true;
        }else{
            $scope.pagination_type=false;
        }
        /*设置显示个数*/
        if($scope.PageData.showPage===undefined){
            $scope.showPage=10;
        }else{
            $scope.showPage=$scope.PageData.showPage;
        } 

        /*设置显示条数*/
        if($scope.PageData.rowsPage===undefined){
            $scope.rowsPage=10;
        }else{
            $scope.rowsPage=$scope.PageData.rowsPage;
        } 
        /*点击执行分页请求*/
        $scope.Page=function(target){
            var that=angular.element(target);
            var p=that.html();
            var t=that.attr("data-type");
            var id=that.attr("data-id");
            var tm=1;
            if(t == "page"){/*点击当前页数执行的请求*/
                if($scope.page==p) return;
                $scope.page=p;

            }else if(t=="next"){/*点击上一页与下一页数执行的请求*/
                if(that.hasClass('next')){//下一页
                   if($scope.page==$scope.totalPage) return;
                   $scope.page++;
                }else{//上一页
                   if(($scope.page-1)==0) return;
                   $scope.page--;
                }
            }else if(t == "gogo" || ($(target.target).attr("data-type") == "enter" && target.keyCode == 13)){/*到指定页数执行的请求*/
                $scope.pageNum=angular.element(".page_angular dl dd span.gogo input.number").val();
                if($scope.pageNum>$scope.totalPage || $scope.pageNum<1 ) return;
                $scope.page = $scope.pageNum;
            }else if(t == "search"){
                $scope.page=1;
            }else if(t == 'day'){
                $scope.lately=id;
                $scope.page=1;
                t=300;
                if(id=='today'){
                    $scope.badd_time=setspectime(0);
                    $scope.eadd_time=setspectime(0);
                }else if(id=='yesterday'){
                    $scope.badd_time=setspectime(-1);
                    $scope.eadd_time=setspectime(-1);
                }else if(id=='seven'){
                    $scope.badd_time=setspectime(-7);
                    $scope.eadd_time=setspectime(0);
                }else if(id=='thirty'){
                    $scope.badd_time=setspectime(-30);
                    $scope.eadd_time=setspectime(0);
                }

            }
           //执行公共请求方法
           $timeout(function(){
             $scope.httpPage();
           },tm);
           

        }
        /*定义公共发送请求方法****换页*/
        $scope.httpPageB=function(){ 
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>数据加载中...</div>",fixed: true,padding:0});
            $http.get('/index.php?g=Alipay&m=HuifuApi&a=getUser').success(function(data){
                if(data.code==0){
                    $scope.node_id=data.data.node_id;
                    $cookieStore.put('node_id',data.data.node_id)
                    var url=$scope.PageData.url+"&"+$("form").serialize()+"&page="+$scope.page+'&node_id='+$scope.node_id; 
                    $http.get(url).success(function(data){                        
                        if(data.code=='0'){ 
                            dialog.close();          
                            $scope.list=data.data;
                            $scope.totalNumber=Number(data.data.total);
                            $scope.pageChange(); 
                            $scope.footerFixed();  
                        }else{
                            Diaerror("没有找到数据");
                        }
                    });

                }else{
                    window.location.href='/index.php?g=Home&m=Login&a=showLogin&newRedirectUrl='+window.location.href;
                }
            });    
           
        }

        /*定义公共发送请求方法****换页*/
        $scope.httpPage=function(){
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>数据加载中...</div>",fixed: true,padding:0});
            var url=$scope.PageData.url+"&"+$("form").serialize()+"&page="+$scope.page+'&node_id='+$cookieStore.get('node_id'); 
            $http.get(url).success(function(data){
                if(data.code=="0"){ 
                    dialog.close();          
                    $scope.list=data.data;
                    $scope.totalNumber=Number(data.data.total);
                    $scope.pageChange();  
                    $scope.footerFixed(); 
                }else{
                    Diaerror("没有找到数据");
                }
            });
        }
        $scope.pageChange=function(){ 
            var d=$scope.page-6;
            $scope.totalPage=Math.ceil($scope.totalNumber/$scope.rowsPage);//总共页数
            $scope.showpage=[];
            // 设置默认显示页码个数
            if($scope.showPage==null){
               $scope.showPage=10;
            }
            //设置显示条数    
            if($scope.rowsPage==null){
               $scope.rowsPage=20;   
            }
            $scope.startChange=Math.ceil($scope.showPage/2);
            if(1<=$scope.page<=$scope.totalPage){
                if($scope.totalPage<=$scope.showPage){
                    for(var i=1;i<=$scope.totalPage;i++){
                        $scope.showpage.push(i);
                    }
                }else if($scope.totalPage>$scope.showPage){
                    if($scope.page<=$scope.startChange){
                        for(var i=1;i<=$scope.showPage;i++){
                            $scope.showpage.push(i);
                        }
                    }else if($scope.page>$scope.startChange && $scope.page<=$scope.totalPage-$scope.startChange){
                        d++;
                        for(var i=d;i<d+$scope.showPage;i++){
                            $scope.showpage.push(i);
                        }
                    }else if($scope.page>$scope.startChange && $scope.page>$scope.totalPage-$scope.startChange){
                        for(var i=$scope.totalPage-$scope.showPage;i<=$scope.totalPage;i++){
                            $scope.showpage.push(i);
                        }
                    }                       
                }
            }
        };
        /*初始执行请求*/
        if($cookieStore.get('node_id')){
           $scope.httpPage();  
        }else{
          $scope.httpPageB();  
        }
        
    }
  }
});
// 表单长度验证
var inputMax=function(){
    $('.Gform ul li input[maxlength],.Gform ul li textarea[maxlength]').each(function(index, el) {
        var _this=$(this);          
        var max=_this.attr('maxlength');

        _this.keyup(function(event) {
           var val=_this.val().length;
           if(val>=max){
             return ;
           }          
           var span=_this.next('span.maxTips');
           span.html(val+'/'+max); 
        });
        _this.keydown(function(event) {
           var val=_this.val().length;
                  
           var span=_this.next('span.maxTips');
           span.html(val+'/'+max); 
        });
    });
    
}
/*输入框长度验证*/
angular.module('input.maxlength',[])
.directive('maxlength',function(){
    return function(scope, element, attrs){
        var max=attrs.maxlength;
        $(element).keyup(function(event) {
           var val=element.val().length;
           if(val>=max){
             return ;
           }          
           var span=element.next('span.maxTips');
           span.html(val+'/'+max); 
        });
        $(element).keydown(function(event) {
           var val=element.val().length;                  
           var span=element.next('span.maxTips');
           span.html(val+'/'+max); 
        });
    };
})
/*设置指定时间*/
angular.module('ui.setspectime', []).factory('setspectime',function ($location) {

    var time=function(t){
        var now=new Date();       
        var tm=now.getTime()+t*24*60*60*1000;
        var lm=new Date(tm);
        var y=lm.getFullYear();
        var m=lm.getMonth()+1;
        var d=lm.getDate();
        return y + '-' + m + '-' +d;   
    }   
    return time;
});     


/*日期指令*/
angular.module('ui.time',[]).directive('uiTime',function(){
    return{
        require: '?ngModel',
        scope: {
            ngModel: '='
        },
        link: function(scope, element, attrs, ngModel) {
            element.on("click",function(){
                WdatePicker({
                    dateFmt:'yyyy-MM-dd',
                    dchanged:function(){
                        scope.$apply(function(){
                            scope.ngModel = $dp.cal.newdate["y"]+"-"+$dp.cal.newdate["M"]+"-"+$dp.cal.newdate["d"];
                        })
                    }
                })
            });
        }
    };
});
/*日期分隔指令*/
angular.module('string.substr',[])
.directive('substrStr',function(){
    return function(scope, element, attrs){
        if(attrs.substrStr){
          element.html(attrs.substrStr.substr(0,4)+'-'+attrs.substrStr.substr(4,2)+'-'+attrs.substrStr.substr(6,2));
        };
    };
});

/*初始化左侧导航服务*/
angular.module('nav.left', []).factory('sidenav',function ($location) {
    var array={};
    var path = $location.path().split('/');
    var list=[];
    if(path.length<=2){
        list=path[1];
    }else{
        var arr=path[2];;
        list=arr.split('_');                            
    }
    array['title']=list[0];
    array['name']=list[1];   
    return array;
});


// <!doctype html>
// <html>
// <head>
// <meta charset="utf-8">
// <script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
// <script type="text/javascript" src="js/angular.min.js"></script>
// <script type="text/javascript" src="js/ZeroClipboard.js"></script>
// </head>
// <body ng-app="app" ng-controller="appcopy">
//     <div ui-copy="{{ha}}" ui-copyback="callback()">点我复制链接{{ha}}</div>
//     <br>
//     <br>
//     <div ui-copy="{{haa}}" ui-copyback="callback()">点我复制指定div内容，比如{{haa}}</div>

//     <div id="main">
//         <p style="color:red;font-size:20px;">我是被复制的html</p>
//     </div>
//     <p contenteditable style="border:solid 1px #333;height:80px; background:#f4f4f4"></p>
// </body>
// </html>
// var app = angular.module('app', ["ui.copy"]);
// app.controller('appcopy',['$scope', function($scope) {
//     $scope.ha = "http://www.baidu.com";
//     $scope.haa = "#main";
//     $scope.callback = function(){
//         alert("复制成功")
//     }
// }]);

angular.module('ui.copy',[]).directive('uiCopy',[function(){
    return{
        scope:{
            uiCopy:"@",
            uiCopyback:"&"
        },
        link: function(scope, element) {
            ZeroClipboard.config({swfPath:'js/ZeroClipboard.swf',forceHandCursor:true});
            var newclip = new ZeroClipboard(element);
            newclip.on("copy", function(e){
                if(scope.uiCopy.indexOf("#")==0||scope.uiCopy.indexOf(".")==0){
                    newclip.setHtml($(scope.uiCopy).html());
                }else{
                    newclip.setText(scope.uiCopy);
                };
                scope.uiCopyback();
            });
        }
    };
}])