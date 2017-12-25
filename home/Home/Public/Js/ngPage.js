angular.module('ngPage', []).directive('ngPage', function() {
  return {
    restrict : 'EA',
    replace : true,
    transclude : true,
    templateUrl : 'Home/Tpl/Public/ngPage.html',
    controller: function ($scope,$http,$timeout){
        $scope.page=1;//初始页数 设置为1;  
        /*设置分页类型 常规页面 or 弹窗*/
        if($scope.PageData.type===undefined){
            $scope.page_dialog=false;
        }else{
            if($scope.PageData.type == "default"){
                $scope.page_dialog=false;
            }else if($scope.PageData.type == "dialog"){
                $scope.page_dialog=true;
            }else{
                console.log("没有此类型");
            }

        }

        /*设置显示个数*/
        if($scope.PageData.rowsPage===undefined){
            $scope.rowsPage=20;
        }else{
            $scope.rowsPage=$scope.PageData.rowsPage;
        }
        /*设置显示分页展示个数*/
        if($scope.PageData.showPage===undefined){
            $scope.showPage=10;
        }else{
            $scope.showPage=$scope.PageData.showPage;
        }  
        /*点击执行分页请求*/
        $scope.Page=function(target){
            $scope.that=angular.element(target);
            var p=$scope.that.html();
            var t=$scope.that.attr("data-type");
            if(t == "page"){/*点击当前页数执行的请求*/
                if($scope.page==p) return;
                $scope.page=p;
            }else if(t=="next"){/*点击上一页与下一页数执行的请求*/
                if($scope.that.hasClass('next')){//下一页
                   if($scope.page==$scope.totlePage) return;
                   $scope.page++;
                }else{//上一页
                   if(($scope.page-1)==0) return;
                   $scope.page--;
                }
            }else if(t == "gogo" || ($(target.target).attr("data-type") == "enter" && target.keyCode == 13)){/*到指定页数执行的请求*/
                $scope.pageNum=angular.element(".page_angular dl dd span.gogo input.number").val();
                if($scope.pageNum>$scope.totlePage || $scope.pageNum<1 ) return;
                $scope.page = $scope.pageNum;
            }else if(t == "search"){
                $scope.page=1;
            }
           
           //执行公共请求方法
           $scope.httpPage();
        }
        /*定义公共发送请求方法****换页*/
        $scope.httpPage=function(){
            $scope.url=$scope.PageData.url+"&"+$("form").serialize()+"&page="+$scope.page; 
            $http.get('').success(function(data,success){
                var data={
                        code:0,
                        list:[{name:'sdfdf',price:'sdfdf',limit:'sdfdf',sendNum:'sdfsdf',totle:'sdfdf',userNum:'sdfdf',status:'sdfdf',pageId:'2153'}],
                        totleNumber:1,
                        msg:'错误信息'
                    } 
               
                   console.log(data);

                if(data.code=="0"){           
                    $scope.list=data.list;
                    $scope.totleNumber=Number(data.totleNumber);
                    $scope.pageChange();   
                }else{
                    Diaerror("没有找到数据");
                }
            });
        }
        $scope.pageChange=function(){ 
            var d=$scope.page-6;
            $scope.totlePage=Math.ceil($scope.totleNumber/$scope.rowsPage);//总共页数
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
            if(1<=$scope.page<=$scope.totlePage){
                if($scope.totlePage<=$scope.showPage){
                    for(var i=1;i<=$scope.totlePage;i++){
                        $scope.showpage.push(i);
                    }
                }else if($scope.totlePage>$scope.showPage){
                    if($scope.page<=$scope.startChange){
                        for(var i=1;i<=$scope.showPage;i++){
                            $scope.showpage.push(i);
                        }
                    }else if($scope.page>$scope.startChange && $scope.page<=$scope.totlePage-$scope.startChange){
                        d++;
                        for(var i=d;i<d+$scope.showPage;i++){
                            $scope.showpage.push(i);
                        }
                    }else if($scope.page>$scope.startChange && $scope.page>$scope.totlePage-$scope.startChange){
                        for(var i=$scope.totlePage-$scope.showPage;i<=$scope.totlePage;i++){
                            $scope.showpage.push(i);
                        }
                    }                       
                }
            }
        };
        /*城市执行请求*/
        $scope.httpPage();
    },   
    link:function(scope,element,attrs){
        
        
    }
  }
});