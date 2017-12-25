
agent.controller('home',function ($scope,$http) {
  $http.get('/index.php?g=Agent&m=Agent&a=nodeAccount')
  .success(function(data){
    if(data.code==0){
        $scope.balance=data.data.balance;
        $scope.node_name=data.data.node_name;

    }else{
        alert(data.msg);
    }
  }); 
  $http.get('/index.php?g=Agent&m=Agent&a=achievement').success(function(data){
    if(data.code==0){
        $scope.thisMonth=data.data.thisMonth;
        $scope.totle=data.data.totle;
    }else{
        alert(data.msg);
    }
  });


    var voice = $(".IndVoice .list li").length;
    var voiceul = $(".IndVoice .list ul");
    var voicet = 1;
    var g = false;
    var set = setInterval(function(){
                if(g){return false;}
                g = true ;
                if(voicet > voice-1){
                    var li = voiceul.find("li:eq(0)");
                    voiceul.append("<li class='loop'>"+li.html()+"</li>");
                    voiceul.animate({marginTop:-40*voicet},500,function(){
                        voiceul.find(".loop").remove();
                        voiceul.css({marginTop:0});
                        g = false ;
                    });
                    voicet = 0 ;
                }else{
                    voiceul.animate({marginTop:-40*voicet},500,function(){
                        g = false ;
                        voicet++
                    });
                }
            },5000)
    set;
    $(".IndVoice .opr .next").on("click",function(){
        if(g){return false;}
        g = true ;
        voiceul.stop();
        if(voicet >= voice){
            var li = voiceul.find("li:eq(0)");
            voiceul.append("<li class='loop'>"+li.html()+"</li>");
            voiceul.animate({marginTop:-40*voicet},500,function(){
                voiceul.find(".loop").remove();
                voiceul.css({marginTop:0});
                voicet = 1 ;
                g = false ;
            });
        }else{
            voiceul.animate({marginTop:-40*voicet},500,function(){
                voicet++ ;
                g = false ;
            });
        }
    });
    $(".IndVoice .opr .pre").on("click",function(){
        if(g){return false;}
        g = true ;
        voiceul.stop();
        if(voicet<=1){
            var li = voiceul.find("li:last");
            voiceul.css({marginTop:-40});
            voiceul.find("li:eq(0)").before("<li class='loop'>"+li.html()+"</li>");
            voiceul.animate({marginTop:0},500,function(){
                voiceul.find(".loop").remove();
                voiceul.css({marginTop:-40*voice+40});
                voicet=voice ;
                g = false ;
            });
        }else{
            voiceul.animate({marginTop:-40*voicet+80},500,function(){
                voicet-- ;
                g = false ;
            });
        }
    });

})
/*我的商户*/
agent.controller('myShop', function ($scope,$state) {
	


	$scope.PageData={
                type:'big',
                url:'/index.php?g=Agent&m=Agent&a=myMerchants'
            };

    $scope.permis=function(){
    	var html=template('promise',{});
        art.dialog({
        	title:'新增授权管理',
        	content:html,
        	width:500,
        	ok:function(){

        	},
        	okVal:'确定',
        	cancel:function(){

        	},
        	cancelVal:'取消'
        });


    }            
	 	
})
/*新增商户*/
agent.controller('addShop', function ($scope,$cookieStore,$http) {
    $scope.viewImage='/Home/Public/Image/Onhook/default.png';
    $scope.imgPath='/Home/Public/Image/Onhook/default.png';
    $('input[type=file]').change(function(){
        $("form[name=addShop]").ajaxSubmit({ 
            success: function (data) {
                if(data.code == '0'){
                    $scope.viewImage=data.data.url;
                    $scope.imgPath=data.data.url;
                    Diasucceed("上传成功");
                }else{
                    Diaerror(data.msg);
                }
            },
        });
        


    });
})

/*新增商户*/
agent.controller('openService', function ($scope,$cookieStore) {
	
		
})



agent.controller('total_shop', function ($scope) {

    $scope.PageData={
                type:'big',
                url:'/index.php?g=Agent&m=Trade&a=statistics'
                };
    
}).controller('total_date', function ($scope) {

    $scope.PageData={
                type:'big',
                url:'/index.php?g=Agent&m=Trade&a=statistics'
                };
    
}).controller('total_type', function ($scope) {

    $scope.PageData={
                type:'big',
                url:'/index.php?g=Agent&m=Trade&a=statistics'
                };
    
})

/*交易明细*/
agent.controller('detail', function ($scope) {
	
	$scope.PageData={
                type:'big',
                url:'/index.php?g=Agent&m=Trade&a=detail'
                };           
                	
})