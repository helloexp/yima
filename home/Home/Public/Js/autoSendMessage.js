// 选项卡调用
WcanalTabonNew(".WcanalNew-tab",".Wcanal-tab-title",".Wcanal-tab-list");
var app = angular.module('myApp', []);
app.controller('myCtrl', function($scope,$http){
//初始化页面
var begindata;
var url="./index.php?g=Integral&m=Integral&a=cardingStr";
$http.get('./index.php?g=Integral&m=Integral&a=assignJsonData').success(success);
/*请求完成之后回调函数*/
function success(data,success){  
    begindata=data;
    /*获取是否如是第一次设置状态 1为第一次设置 0为修改数据*/       
    var m_status= data.info.memberCardData.isFirstSet;
    var g_status= data.info.plusIntegralData.isFirstSet;
    var b_status= data.info.reduceIntegralData.isFirstSet;
    /*初始内容设置--会员*/
    $scope.member_isFirstSet=m_status;
    $scope.member_system_msg=data.info.memberCardData.system_msg;/*系统消息开关*/
    $scope.member_sms_msg=data.info.memberCardData.sms_msg;/*短信通知开关*/
    $scope.member_wx_msg=data.info.memberCardData.wx_msg;/*微信模板开关*/
    $scope.member_wx_msg_open=!!data.info.memberCardData.wx_msg;/*微信模板开关*/
    $scope.member_wx_msg_phone=!!data.info.memberCardData.wx_msg;/*微信模板开关*/
    var m_template_info=data.info.memberCardData.wx_msg_templet;
    var m_s_t=data.info.memberCardData.system_msg_templet;/*系统消息模板内容*/
    /*如果是第一次设置模板信息为空 无信息可显示 修改则显示以下信息*/
    /*第一种情况,用户都设置了 系统消息 短息消息 微信模板消息*/
    /*都不为空显示以下信息*/
    if(!!m_s_t){
        $scope.member_system_msg_templet=m_s_t;
    }
    if(!!m_template_info){ 
        $scope.tempId=m_template_info.weChatTemplateId;
         /*改装数据 生成模板列表数据  start*/
        $scope.m_t_info=m_template_info.content;
        $scope.member_content_name=$scope.m_t_info.allName;
        $scope.member_temp_title=$scope.m_t_info.title;
        $scope.temp_info_r=$scope.m_t_info.contentKeyVal;
        $scope.temp_info_l=$scope.m_t_info.leftData;
        var list_r = [];
        var list_l = [];
        var list=[];
        /*改装数据*/
        angular.forEach($scope.temp_info_r, function(value, key) {
          this.push({"right":key,"value":value});
        }, list_r);
        angular.forEach($scope.temp_info_l, function(value, key) {
          this.push({"left":value});
        }, list_l);
        
        var rl=list_r.length;/*列表右边数据长度*/
        var ll=list_l.length;/*列表左边数据长度*/
        var differ=rl-ll;
        if(differ==2){
            $scope.m_welcome=list_r[0];
            $scope.m_disabled=false;/*用户自己输入的 可以再次修改*/
            $scope.m_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });
        }else if(differ==1){
            $scope.m_welcome={"right":"fixed","value":$scope.temp_info.fixed};
            $scope.m_disabled=true; /*系统自带的 不可以再次修改*/
            $scope.m_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });

        }


        $scope.m_list=list;
    }
     /*改装数据 生成模板列表数据  end*/

    /*初始内容设置--获得积分*/
    $scope.getNum_isFirstSet=g_status;
    $scope.get_system_msg=data.info.plusIntegralData.system_msg;/*系统消息开关*/
    $scope.get_sms_msg=data.info.plusIntegralData.sms_msg;/*短信通知开关*/
    $scope.get_wx_msg=data.info.plusIntegralData.wx_msg;/*微信模板开关*/
    $scope.get_wx_msg_open=!!data.info.plusIntegralData.wx_msg;/*微信模板开关*/
    $scope.get_wx_msg_phone=!!data.info.plusIntegralData.wx_msg;/*微信模板开关*/
    var g_template_info=data.info.plusIntegralData.wx_msg_templet;
    var g_s_t=data.info.plusIntegralData.system_msg_templet
    if(!!g_s_t){
        $scope.get_system_msg_templet=g_s_t;
    }

    if(!!g_template_info){
        $scope.tempIdGet=g_template_info.weChatTemplateId;
        /*改装数据 生成模板列表数据  start*/
        $scope.temp_info=g_template_info.content;
        $scope.get_content_name=$scope.temp_info.allName;
        $scope.get_temp_title=$scope.temp_info.title;      
        $scope.temp_info_r=$scope.temp_info.contentKeyVal;
        $scope.temp_info_l=$scope.temp_info.leftData;
        var list_r = [];
        var list_l = [];
        angular.forEach($scope.temp_info_r, function(value, key) {
          this.push({"right":key,"value":value});
        }, list_r);
        angular.forEach($scope.temp_info_l, function(value, key) {
          this.push({"left":value});
        }, list_l);
        
        var rl=list_r.length;/*列表右边数据长度*/
        var ll=list_l.length;/*列表左边数据长度*/
        var differ=rl-ll;
        if(differ==2){
            $scope.g_welcome=list_r[0];
            $scope.g_disabled=false;/*用户自己输入的 可以再次修改*/
            $scope.g_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });
        }else if(differ==1){
            $scope.g_welcome={"right":"fixed","value":$scope.temp_info.fixed};
            $scope.g_disabled=true; /*系统自带的 不可以再次修改*/
            $scope.g_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });

        }
        $scope.g_list=list;
    }

    /*改装数据 生成模板列表数据  end*/

    /*初始内容设置--消耗积分*/
    $scope.blurNum_isFirstSet=b_status;
    $scope.blur_system_msg=data.info.reduceIntegralData.system_msg;/*系统消息开关*/
    $scope.blur_sms_msg=data.info.reduceIntegralData.sms_msg;/*短信通知开关*/
    $scope.blur_wx_msg=data.info.reduceIntegralData.wx_msg;/*微信模板开关*/
    $scope.blur_wx_msg_open=!!data.info.reduceIntegralData.wx_msg;/*微信模板开关*/
    $scope.blur_wx_msg_phone=!!data.info.reduceIntegralData.wx_msg;/*微信模板开关*/
    var b_template_info=data.info.reduceIntegralData.wx_msg_templet;
    var b_s_t=data.info.reduceIntegralData.system_msg_templet;
    if(!!b_s_t){
        $scope.blur_system_msg_templet=b_s_t;
    }
    if(!!b_template_info){
        $scope.tempIdBlur=b_template_info.weChatTemplateId;
        $scope.temp_info=b_template_info.content;
        $scope.blur_content_name=$scope.temp_info.allName;
        $scope.blur_temp_title=$scope.temp_info.title;
        $scope.temp_info_r=$scope.temp_info.contentKeyVal;
        $scope.temp_info_l=$scope.temp_info.leftData;
        var list_r = [];
        var list_l = [];
        /*改装数据 生成模板列表数据  start*/
        angular.forEach($scope.temp_info_r, function(value, key) {
          this.push({"right":key,"value":value});
        }, list_r);
        angular.forEach($scope.temp_info_l, function(value, key) {
          this.push({"left":value});
        }, list_l);
        
        var rl=list_r.length;/*列表右边数据长度*/
        var ll=list_l.length;/*列表左边数据长度*/
        var differ=rl-ll;
        if(differ==2){
            $scope.b_welcome=list_r[0];
            $scope.b_disabled=false;/*用户自己输入的 可以再次修改*/
            $scope.b_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });
        }else if(differ==1){
            $scope.b_welcome={"right":"fixed","value":$scope.temp_info.fixed};
            $scope.b_disabled=true; /*系统自带的 不可以再次修改*/
            $scope.b_end=list_r[rl-1];
            var list=list_r.splice(1,rl-2)
            $.each(list,function(i,n){
              n["left"]= list_l[i].left;           
            });

        }
        $scope.b_list=list;

     /*改装数据 生成模板列表数据  end*/
     }

    setTimeout(function(){
        Gformbegin();
    },1);      
} 
    // 字符串插入 字段选择
    $scope.inputStr=function(target){
        var _this=$(target);
        var oldStr = _this.closest(".newRadio-input").find('textarea[name="sysMsgContent"]').val();
        _this.closest(".newRadio-input").find('textarea[name="sysMsgContent"]').val(oldStr+'<-'+_this.text()+'->');
     }; 
    /*会员卡变更通知 数据请求*/
     $scope.nextMember=function(target){
        var _this=angular.element(target);
        var value=_this.parent().parent('.Ginput').find('input').val();
        if(value==""){
            Diaerror("模板不可为空");
            return false;
        }else if(value==begindata.info.memberCardData.wx_msg_templet.weChatTemplateId){
            Diaerror("您未对模板进行修改，请修改后再提交");
            return false;
        }
        $scope.temp='templateId='+$.trim(value);
        $http.post(url,$scope.temp,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data,success){
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
            if(success=="200"){
                 dialog.close();
                /*开始处理数据*/
                if(data.status==1){
                    if(_this.attr("data-phone")=="show"){
                        _this.attr("data-phone","");
                        $scope.member_wx_msg_phone=!$scope.member_wx_msg_phone;/*微信模板开关*/
                        $scope.member_wx_msg_open=!$scope.member_wx_msg_open;
                    }
                     
                        list=data.info.list;
                        $.each(list,function(i,n){n["value"]="--请选择--";});
                        $scope.m_list=list;
                        var welcome=data.info.welcome;
                        var last=data.info.last;
                        if(welcome.value==""){/*等于空 用户可以输入*/
                            welcome['right']=welcome.name;
                           $scope.m_disabled=false;
                        }else{/*不等于空 用户不可以输入*/
                           welcome['right']="fixed";                     
                           $scope.m_disabled=true;
                        } 
                        last['right']=last.name;
                        $scope.m_welcome=welcome; 
                        $scope.m_end=last;            
                        $scope.member_content_name=data.allName;
                        $scope.member_temp_title=data.title;
                        setTimeout(function(){
                            windowheight();
                        },1);
                }else{
                    Diaerror(data.info);
                }
            }
        }); 
    };
     /*获得积分通知*/
    $scope.nextGet=function(target){
        var _this=angular.element(target);
        var value=_this.parent().parent('.Ginput').find('input').val();
        if(value==""){
            Diaerror("模板不可为空");
            return false;
        }else if(value==begindata.info.plusIntegralData.wx_msg_templet.weChatTemplateId){
            Diaerror("您未对模板进行修改，请修改后再提交");
            return false;
        }
        $scope.temp='templateId='+$.trim(value);
        $http.post(url,$scope.temp,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data,success){
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
            if(success=="200"){
                 dialog.close();
                /*开始处理数据*/
                if(data.status==1){
                    if(_this.attr("data-phone")=="show"){
                        _this.attr("data-phone","");
                        $scope.get_wx_msg_phone=!$scope.get_wx_msg_phone;/*微信模板开关*/
                        $scope.get_wx_msg_open=!$scope.get_wx_msg_open;
                    }
                    var list=data.info.list;
                    $.each(list,function(i,n){n["value"]="--请选择--";});
                    $scope.g_list=list;
                    var welcome=data.info.welcome;
                    var last=data.info.last;
                    if(welcome.value==""){/*等于空 用户可以输入*/
                        welcome['right']=welcome.name;
                       $scope.g_disabled=false;
                    }else{/*不等于空 用户不可以输入*/
                       welcome['right']="fixed";                     
                       $scope.g_disabled=true;
                    } 
                    last['right']=last.name;
                    $scope.g_welcome=welcome;
                    $scope.get_temp_title=data.title;
                    $scope.g_end=last;
                    $scope.get_content_name=data.allName;
                    setTimeout(function(){
                        windowheight();
                    },1);
               
                }else{
                    Diaerror(data.info);
                } 
            }      
        });       
    };
    /*消耗积分通知*/
    $scope.nextblur=function(target){
        var _this=angular.element(target);
        var value=_this.parent().parent('.Ginput').find('input').val();
        if(value==""){
            Diaerror("模板不可为空");
            return false;
        }else if(value==begindata.info.reduceIntegralData.wx_msg_templet.weChatTemplateId){
            Diaerror("您未对模板进行修改，请修改后再提交");
            return false;
        }
        $scope.temp='templateId='+$.trim(value);
        $http.post(url,$scope.temp,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
         }).success(function(data,success){
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
            if(success=="200"){
                 dialog.close();
                /*开始处理数据*/
                if(data.status==1){
                    if(_this.attr("data-phone")=="show"){
                        _this.attr("data-phone","");
                        $scope.blur_wx_msg_phone=!$scope.blur_wx_msg_phone;/*微信模板开关*/
                        $scope.blur_wx_msg_open=!$scope.blur_wx_msg_open;
                    }
                        var list=data.info.list;
                        $.each(list,function(i,n){n["value"]="--请选择--";});
                        $scope.b_list=list;
                        var welcome=data.info.welcome;
                        var last=data.info.last;
                        if(welcome.value==""){/*等于空 用户可以输入*/
                            welcome['right']=welcome.name;
                           $scope.b_disabled=false;
                        }else{/*不等于空 用户不可以输入*/
                           welcome['right']="fixed";                     
                           $scope.b_disabled=true;
                        } 
                        last['right']=last.name;
                        $scope.b_welcome=welcome;
                        $scope.b_end=last;
                        $scope.blur_content_name=data.allName;
                        $scope.blur_temp_title=data.title;
                        setTimeout(function(){
                            windowheight();
                        },1);
                
                }else{
                    Diaerror(data.info);
                } 
            }       
        });        
    };

    /*保存表单提交数据*/
    $scope.goSubmit=function(){
        var formVal=$('.WcanalNew-tab>input[type="hidden"]').val();
        var formActive=$('form[name='+ formVal +']'); //获取当前编辑后发送form表单
        var sys_statas=formActive.find("input[name='sysMsgStatus']").val();
        var sys_content=formActive.find("textarea[name='sysMsgContent']").val();
        var sms_statas=formActive.find("input[name='smsMessageStatus']").val();
        var wx_statas=formActive.find("input[name='weChatTemplateStatus']").val();
        var wx_welcome=formActive.find(".phoneCon").find(".welcome input").val();
        var wx_end=formActive.find(".phoneCon").find(".end input").val();    
        //开始发送
        formActive.validationEngine({
            promptPosition:"topLeft:5,0",
            scroll:false,
            focusFirstField: false
        });
        var t = formActive.validationEngine("validate");
        if(!t) return false;
        var ThisData=begindata.info[formVal];
        // if(sys_statas==ThisData.system_msg && sms_statas==ThisData.sms_msg && wx_statas==ThisData.wx_msg && sys_content==ThisData.system_msg_templet){
        //     Diaerror("您没有进行任何操作，请确认修改后再点击保存");
        //     return false;
        // }
        var data=formActive.serialize();
        $http.post('./index.php?g=Integral&m=Integral&a=autoSendMessageSubmit',data,{
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
        }).success(function(data,stats){   
            dialog = art.dialog({title:false,content:"<div class='msg-all-succeed'>正在提交...</div>",fixed: true,padding:0});
            if(stats=="200"){
                dialog.close();
                if(data.status==1){
                    if(formActive.find('input[name="isFirstSet"]').val()=="1"){
                      Diasucceed(data.info);
                      formActive.find('input[name="isFirstSet"]').val('0');   
                    }else{
                      Diasucceed(data.info);  
                    }   
                }else{    
                    if(formActive.find('input[name="isFirstSet"]').val()=="1"){
                      Diaerror(data.info);  
                    }else{
                     Diaerror(data.info);
                    }
                } 
             }   
          });        
    };
    /*设置默认时间*/
    ! function getData(){
        var date=new Date();
        var mouth=date.getMonth()+1;
        var days=date.getDate();
        var nowDay=mouth+'月'+days+'日';
        $('span.date').html(nowDay); 
    }();
});
/*自定义模板修改指令*/
app.directive("a", function() {
    return {
        restrict:"AE",
        link:function(scope,element,attrs){
            element.bind('click', function(event) {
                scope.$apply(attrs.howtoload);
            });
        }
    } 
});