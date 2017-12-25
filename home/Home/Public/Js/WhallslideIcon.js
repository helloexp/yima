// Array.prototype.removeByValue = function(val) {
//   for(var i=0; i<this.length; i++) {
//     if(this[i] == val) {
//       this.splice(i, 1);
//       break;
//     }
//   }
// }

var app = angular.module('allCard', ['ngCookies']);
app.controller('allCard',function ($scope,$http,$cookieStore,$timeout) {
/*设置请求url*/
var url="./index.php?g=Hall&m=Index&a=buildGet";//页面加载时的请求
var href=window.location.href;
var after="&";

/*设置所有城市*/
if(href.indexOf('key_words') != -1){
	var c=href.indexOf('key_words');
	url=url+href.substring(c-1,href.length)+"&";
}	

if($cookieStore.get("city_code")){
	after=after+"city_code="+$cookieStore.get("city_code")+"&";
	$scope.city_code=$cookieStore.get("city_code");
}
/*设置所有单价*/

if($cookieStore.get("amount")){
	after=after+"amount="+$cookieStore.get("amount")+"&";
	$scope.amount=$cookieStore.get("amount");
}

/*设置所有分类*/
if($cookieStore.get("type") === undefined){
	$scope.alltype=true;
}else{
	after=after+"type="+$cookieStore.get("type")+"&";
	$scope.type=$cookieStore.get("type");
	if($scope.type=="话费" || $scope.type=="Q币" || $scope.type=="流量包" || $scope.type=="微信红包"){
		$scope.alltype=false;
	}else{
		$scope.alltype=true;
	}
}

/*设置所有分大类*/
if($cookieStore.get("this_class")){
	$scope.this_class=$cookieStore.get("this_class");
}

if($cookieStore.get("send_class")){
	after=after+"oneCate="+$cookieStore.get("send_class")+"&";
	$scope.send_class=$cookieStore.get("send_class");
}

/*设置所有分小类*/
if($cookieStore.get("this_class_list")){
	$scope.this_class_list=$cookieStore.get("this_class_list");
}
if($cookieStore.get("twoCate")){
	after=after+"twoCate="+$cookieStore.get("send_class_list")+"&";
	$scope.send_class_list=$cookieStore.get("send_class_list");
}
if($cookieStore.get("p") === undefined){
	$scope.page=1;//初始页数 设置为1
}else{
	after=after+"p="+$cookieStore.get("p")+"&";
	$scope.page=$cookieStore.get("p");
}
var url_b=url+after;

/*设置缓存*/
$scope.result= ($scope.city_code!==undefined || $scope.amount!==undefined || $scope.type!==undefined || $scope.this_class!==undefined || $scope.this_class_list!==undefined ) ? true : false; 

/*开始请求数据*/

$http.get(url_b).success(function(data){
    //数据请求成功
	if(data.status=="0"){
		/*初始化数据*/
		var allType=[];
		var allPrice=[];
		var allPriceS=[];
		var allClass=data.info.allClass;
		var allCity=[];
		var allClassS=[];
		var allCityS=[];
		var allTypeS=[];
        //生成全部分类	           
		angular.forEach(data.info.allType, function(value, key) {
          this.push({"id":key,"name":value});
        }, allTypeS);
        for(var i=0;i<allTypeS.length;i++){
        	allType.push(allTypeS[i])               
        }
        //生成全部优惠
        angular.forEach(data.info.allPrice, function(value, key) {
          this.push({"id":key,"name":value});
        }, allPriceS);
        for(var i=0;i<allPriceS.length;i++){
        	allPrice.push(allPriceS[i])               
        }
        //生成全部城市
        angular.forEach(data.info.allCity, function(value, key) {
          this.push({"id":key,"name":value});
        }, allCityS);
        for(var i=0;i<allCityS.length;i++){
        	allCity.push(allCityS[i])               
        }

        $scope.list=[];
        var b=[];
        for(i=0;i<data.info.allClass.length;i++){
        	var lists=[];
        	b[i]=data.info.allClass[i].list;
        	angular.forEach(b[i], function(value, key) {
	          this.push({"name":value,"id":key});
	        }, lists);
	         $scope.list[data.info.allClass[i].id]=lists;
        }                          
        /******生成新分类******/
        var NewTyps={
        	"allType":allType,
        	"allPrice":allPrice,
        	"allClass":allClass,
        	"allCity":allCity
        };

        /******生成新数据******/
        var dataInfo={
        	"totleNumber":data.info.totleNum,
        	"cardInfo":data.info.cardInfo,
        	"cardInfo_right":data.info.cardInfo_right,
        	"types":NewTyps
        };
        /******生成页面数据******/
		$scope.types=dataInfo.types;
    	$scope.cardInfo=dataInfo.cardInfo;
    	$scope.cardInfo_right=dataInfo.cardInfo_right;			   
	    /******发送数据初始化********/
	    $scope.amtflag=data.info.amtflag;
        /****执行分页初始函数******/
	    $scope.pageChange(dataInfo.totleNumber);

	    if($cookieStore.get("send_class") !== undefined){
			$scope.childType=$scope.list[$scope.send_class]; 			
		}
    }else{
		Diaerror("没有找到数据");
    }
});	
$scope.message=function(){
	art.dialog.open("./index.php?g=Hall&m=Index&a=purchaseMessage",{title:'留言求购',width:640,height:530,lock:true});
};

$scope.pageChange=function(totalNumber){
	$scope.totalNumber=Number(totalNumber);
	$scope.totlePage=Math.ceil($scope.totalNumber/20);//总共页数
	$scope.showpage=[];
	var sp=10;
	var sb=sp/2;
	$scope.d=$scope.page-6;
    if(1<=$scope.page<=$scope.totlePage){
    	if($scope.totlePage<=sp){
			for(var i=1;i<=$scope.totlePage;i++){
				$scope.showpage.push(i);
			}
		}else if($scope.totlePage>sp){
			if($scope.page<=sb){
				for(var i=1;i<=sp;i++){
					$scope.showpage.push(i);
				}
			}else if($scope.page>sb && $scope.page<=$scope.totlePage-sb){
				$scope.d++;
				for(var i=$scope.d;i<$scope.d+sp;i++){
					$scope.showpage.push(i);
				}
			}else if($scope.page>sb && $scope.page>$scope.totlePage-sb){
				for(var i=$scope.totlePage-sp;i<=$scope.totlePage;i++){
					$scope.showpage.push(i);
				}
			}						
		}
    }
};


/*排序查询请求*/
$scope.sort_search=function(target){
 	var that=angular.element(target);
 	var type=that.attr('data-type');
 	var id=that.attr('data-id');
 	$scope.page=1;

 		
 		if(id=="hotest"){
 			that.addClass('hover');
			that.find('i').toggleClass('up');
			$scope.general='';
			$scope.sales='';$scope.time='';
			angular.element('.general').removeClass('hover');
			
			if(that.find('i').hasClass('up')){
				$scope.hotest=1;
			}else{
				$scope.hotest=0;
			}
 		}else if(id=="sales"){
 			that.addClass('hover');
 			$scope.general='';
 			$scope.hotest='';
 			$scope.time='';
 			angular.element('.general').removeClass('hover');
			that.find('i').toggleClass('up');
			if(that.find('i').hasClass('up')){
				$scope.sales=1;
			}else{
				$scope.sales=0;
			}
 		}else if(id=="time"){
 			that.toggleClass('hover');
 			$scope.general='';
 			$scope.sales='';
 			$scope.hotest='';
 			angular.element('.general').removeClass('hover');
			that.find('i').toggleClass('up');
			if(that.find('i').hasClass('up')){
				$scope.time=1;
			}else{
				$scope.time=0;
			}

			
 		}else if(id=="general"){
 			that.toggleClass('hover');
 			angular.element('.sales,.time,.hotest').removeClass('hover');
 			$scope.sales='';
 			$scope.hotest='';
 			$scope.time='';
 			if(that.hasClass('hover')){
 				$scope.general=1;
 			}else{
 				$scope.general='';
 			}
 			

 		}
 		
 	$timeout(function(){
       $scope.httpPage(); 	
    },300);
 }

 /*输入城市按enter键执行*/
$scope.inputCity=function(target){
	if(target.keyCode==13){
		$scope.city_code=angular.element(".input_city").val();
		$cookieStore.put("city_code",angular.element(".input_city").val());
		$timeout(function(){
	       $scope.httpPage(); 	
	    },300);
	}
}

$scope.thisClass=function(target){

	var that=angular.element(target);
	$scope.page=1;
	var thatType=that.attr("data-type");
	var thatId=that.attr("data-id");
	var id=that.attr("data-index");
	var html=that.html();
	if(thatType=="classB"){			
	    $scope.send_class_list=thatId;
	    $scope.this_class_list=html;
	    $cookieStore.put("this_class_list",html);
	    $cookieStore.put("send_class_list",thatId);	 	
    }else if(thatType=="classI"){
    	if (thatId=="-") {/*如果是全部分类*/
    		$scope.this_class_list=undefined;
    		$scope.send_class_list=undefined;
    		$cookieStore.remove("this_class_list");	
    		$cookieStore.remove("send_class_list");
    		$scope.childType=null;
    		
    	}else{
    		 $scope.childType=$scope.list[thatId]; 

    	}
        $scope.send_class=thatId;
        $scope.this_class=html;	
       
        $cookieStore.put("this_class",html);
        $cookieStore.put("send_class",thatId);			 
    }else if(thatType=="cityserach"){
        $scope.city_code=angular.element(".input_city").val();
        $cookieStore.put("city_code",angular.element(".input_city").val());
    }else{
    	if(thatType=="city"){
    		$scope.city_code=html;	
    		$cookieStore.put("city_code",html);	 
    				
    	}else if(thatType=="type"){   		
    		if(html=="话费" || html=="Q币" || html=="流量包" || html=="微信红包"){
				$scope.alltype=false;/*隐藏分类和城市*/
			    $scope.send_class=undefined;
			    $scope.send_class_list=undefined;
			    $scope.childType=undefined;
			    $scope.this_class=undefined;	
			    $scope.this_class_list=undefined;
			    $scope.city_code=undefined;	
			    $cookieStore.remove("this_class");	
			    $cookieStore.remove("this_class_list");	
			    $cookieStore.remove("city_code");
			    $cookieStore.remove("send_class");	
			    $cookieStore.remove("send_class_list");	
			}else{
				$scope.alltype=true;/*显示分类和城市*/
			}
			$scope.type=html; 
			$cookieStore.put("type",html);		 		
    	}else if(thatType=="price"){
    		$scope.amount=html;
    		$cookieStore.put("amount",html);		 
    	}
    }

    $scope.result= ($scope.city_code!==undefined || $scope.amount!==undefined || $scope.type!==undefined || $scope.this_class!==undefined || $scope.this_class_list!==undefined ) ? true : false;  	
    $timeout(function(){
       $scope.httpPage(); 	
    },300);
   
}

/*点击当前页执行请求*/
$scope.Page=function(target){
   var that=angular.element(target);
   var p=that.html();
   var t=that.attr("data-type");
   var page=$scope.page;
   if(t == "next"){
	   	if(that.hasClass('next')){//下一页
			if(page==$scope.totlePage) return;
			page++;	
		}else{//上一页
			if(page==1) return;	
			page--;   
		}
   }else if(t == "page"){
	   if(page==p) return;
	   page=p;
   }else if(t == "gogo" || ($(target.target).attr("data-type") == "enter" && target.keyCode == 13)){
   	   $scope.pageNum=angular.element(".number").val();
	   if($scope.pageNum>$scope.totlePage || $scope.pageNum<1 ){
	   	Diaerror("您要跳转的页数不存在，请重新选择");
	   	return;
	   } 
       page = $scope.pageNum;
   }
   $scope.page=page;
   $cookieStore.put("p",page);	
  
   //执行公共请求方法
   $scope.httpPage();
   
}

/*定义公共发送请求方法****换页*/
$scope.httpPage=function(){
    var data="&"+$("#serachList").serialize()+"&p="+$scope.page; 
    console.log(data);
    var dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>数据加载中...</div>",fixed: true,padding:0}); 
	$http.get(url+data).success(function(data){
		 
		if(data.status=="0"){			
			dialog.close();
			$scope.cardInfo=data.info.cardInfo;
			$scope.amtflag=data.info.amtflag;
			$scope.pageChange(data.info.totleNum);				
		}else{
			Diaerror("没有找到数据");
		}
	});
}	
});

/*以下为旧的文件待整理*/

$(document).ready(function(e){
	hallonlinecontant();
});

function hallonlinecontant(){
	var sessionname=$(".gologin").length;
	var html=
		[
		 '<div class="ui-sidebar" id="tbox">',
		 '<a class="ui-sidebar-block app" href="javascript:void(0)"><i></i></a>',
		 '<a class="ui-sidebar-block calltel" href="javascript:void(0)"><i></i></a>',
		 '<a class="ui-sidebar-block callqq" href="http://wpa.b.qq.com/cgi/wpa.php?ln=1&key=XzkzODA2Njc3MF8zNzA4NjdfNDAwODgwNzAwNV8yXw" target="_blank"></a>',
		 '<a class="ui-sidebar-block backtop"   href="javascript:void(0)" id="gotop" style="display: none;"></a>',
		 '</div>'].join('');
			/*['<div class="Indonlinecontant">',
            '<div class="Indonlinecontant-openclose" onclick="onlinecontant()"></div>',
            '</form>',
        	'</div>']*/
	if($("#tbox").length<1){
	$("#wrapper").append(html);
	};
} 
$(function() { 
	$('#gotop').click(function(){ 
		$('body,html').animate({
			scrollTop: 0
		},
		800);//点击回到顶部按钮，缓懂回到顶部,数字越小越快
		return false;  
	})


$(window).scroll(function(){
	t = $(document).scrollTop();
	
	if(t > 50){
			$('#gotop').fadeIn('slow');
		}else{
			$('#gotop').fadeOut('slow');
	}       
})  
});