var app = angular.module('app', ['ngSanitize','ui.sortable','ui.draggable','ui.move','ui.selectable','ui.adddraggable','spectrum',"ng.ueditor","ng.contextmenu","ui.hotkey","ui.temp","ui.style","ui.edit"]).config(["$sceProvider",function($sceProvider){
    $sceProvider.enabled(false);
}]);
app.controller('pictxt',['$scope','$timeout','$templateCache', function($scope,$timeout,$templateCache) {
    $scope.FontConfig = {
    	defaultColor:DefaultData.pages.color || "#00b0f0",
    	prevdefaultColor:DefaultData.pages.color || "#00b0f0",
    	color:[
                ["#ffffff","#000000","#eeece1","#1f497d","#4f81bd","#c0504d","#9bbb59","#8064a2","#4bacc6","#f79646"],
                ["#f2f2f2","#7f7f7f","#ddd9c3","#c6d9f0","#dbe5f1","#f2dcdb","#ebf1dd","#e5e0ec","#dbeef3","#fdeada"],
                ["#d8d8d8","#595959","#c4bd97","#8db3e2","#b8cce4","#e5b9b7","#d7e3bc","#ccc1d9","#b7dde8","#fbd5b5"],
                ["#bfbfbf","#3f3f3f","#938953","#548dd4","#95b3d7","#d99694","#c3d69b","#b2a2c7","#92cddc","#fac08f"],
                ["#a5a5a5","#262626","#494429","#17365d","#366092","#953734","#76923c","#5f497a","#31859b","#e36c09"],
                ["#7f7f7f","#0c0c0c","#1d1b10","#0f243e","#244061","#632423","#4f6128","#3f3151","#205867","#974806"],
                ["#c00000","#ff0000","#ffc000","#ffff00","#92d050","#00b050","#00b0f0","#0070c0","#002060","#7030a0"]
            ],
        fontFamily:["微软雅黑","宋体","方正静蕾简体","方正正黑简体","汉仪菱心简体","ArialArial","BRADHITC","BRUSHSCI","CASTELAR","KUNSTLER"],
        fontSize:["12px","16px","18px","24px","32px","48px","62px","78px","86px","92px"],
        lineHeight:["0.75em","1em","1.25em","1.5em","1.75em","2em","3em","4em","5em"],
        textDecoration:["","overline","line-through","underline"],
        isfontfamily:true,
        colorFn:function(){
			$.each($scope.moddata,function(i,n){
				$.each(n.list,function(i,m){
					$.each(m.list,function(i,o){
						o["defaultColor"] = $scope.FontConfig.defaultColor;
					})
				})
			})
		},
        bodyFn:function(){
			$scope.quickAdd=false;
			$scope.FontConfig.Ue=0
		}
    };
    $scope.isfouce = false;
	$scope.moddata = moddata;
    $scope.mymoddata = $scope.moddata[0];
    $scope.mymoddatalist = $scope.mymoddata.list[0];
    $scope.pages = {
    	color:$scope.FontConfig.defaultColor,
    	temp:DefaultData.pages.temp
    };
	$scope.myPage = $scope.pages.temp[0];
	$scope.myPageEdit = "";
	$scope.myValueEdit = "";
	$scope.issavenew = false;
	//保存
	$scope.save = function(){
		if($scope.issavenew){return false;}
		$scope.issavenew = true;
        if($scope.pages.temp.length == 0){
            Diamsg({content:"您没有编辑任何内容~",okVal:"返回编辑"});
			$scope.issavenew = false;
            return false;
        }
        var autoSaveVal = $('#autoSave').val();
        var preview = $('#isPreview').val();
        var currentAction = DefaultData.saveUrl;
        if (autoSaveVal == 1) {
            currentAction = DefaultData.autosaveUrl;
        }
        if (preview == 1) {
            currentAction = DefaultData.autosaveUrl;
        }
        $.each($scope.pages.temp,function(i,n){
            delete n.$$hashKey;
        });
        $("#memo").val(JSON.stringify($scope.pages));
        $("#theform").ajaxSubmit({
            beforeSubmit:function(){
		        if(autoSaveVal == 1 && preview == 1){
		            Diasucceed("正在保存并生成预览，请稍后...","",10,false);
		        }else if(autoSaveVal == 0){
		            Diasucceed("正在保存，请稍后...");
		        }
            },
            success:function(data){
				$scope.issavenew = false;
                $('#autoSave').val(0);
                $('#isPreview').val(0);
                if(autoSaveVal == 1) {
                    if(data.status == '1'){
                        if (preview == 1) {
                            if (typeof data.url != 'undefined') {
		            			Diasucceed("保存成功","",0.1);
                                art.dialog.open(data.url,{
                                    width:1000,
                                    height:690,
                                    title:'预览'
                                });
                                return false;
                            }else{
                            	Diaerror("data.url未找到");
                            }
                        }
                        Dianotice("自动保存成功");
                    }else{
                        Dianotice("自动保存失败");
                        $scope.oldPages = $scope.pages;
                    }
                    return false;
                }
                if(data.status == '1'){
                    Diasucceed("保存成功,正在跳转...");
                    window.location.href= "/index.php?g=MarketActive&m=NewPictext&a=saveSuccess&id="+data.m_id
                } else {
                    Diaerror(data.info);
                }
            },
            error:function(data) {
				$scope.issavenew = false;
                $('#autoSave').val(0);
                $('#isPreview').val(0);
                Diaerror("保存失败,请检查您的网络...");
            },
            url:currentAction,
            dataType:'json',
            type:'post'
        });
	};
	//预览
    $scope.saveview = function() {
        $('#autoSave').val(1);
        $('#isPreview').val(1);
        $scope.save();
    }
    //保存我的模板
    $scope.savenew = function(value){
		if(value.length==0){Diaerror('请先添加模板内容',"","",false);return false;}
		if($scope.issavenew){return false;}
        Diasucceed('正在保存中，请稍后',"","",false);
        $scope.issavenew = true;
        var newmod = angular.copy(value);
        $.each(newmod,function(i,n){
        	n["uiMyTemp"] = true;
            delete n.$$hashKey;
            delete n.uiEdit;
        });
        $.post(DefaultData.mytempUrl,{"memo":JSON.stringify(newmod)},function(data){
            $scope.issavenew = false;
            if (typeof data.status != 'undefined' && data.status == 1) {
                Diasucceed('保存成功',"","",false);
                $scope.$apply(function() {
                	$scope.moddata[1].list[0].list.splice(0, 0, {id:data.data,data:newmod});
                });
            } else {
                Diaerror('模板已存在',"","",false);
            }
        },"json");

    };
	//删除我的模板
	$scope.delmoddata = function(value){
		event.stopPropagation();
		Diamsg({
			content:'确定要删除您保存的模板么？',
			ok:function(){
				$.each($scope.moddata[1].list[0].list,function(i,n){
					if(n==value){
						$scope.$apply(function(){
							var page_id = n.id || "";
                            if (page_id) {
                                $.post(DefaultData.delmytempUrl,{ id:page_id},function(data){
                                    if (typeof data.status != 'undefined' && data.status == 1) {
                                        $scope.$apply(function() {
                                            $scope.moddata[1].list[0].list.splice(i,1);
                                        });
                                        Diasucceed(data.info,"","",false);
                                    } else {
                                        Diamsg({
                                            content:data.info,
                                            ok:true
                                        });
                                    }
                                },"json");
                            }
						});
                        return false;
					}
				});
			},
			cancel:true
		});
	};
	$scope.marketNav = function(value,type){
		if(type==1){
			$scope.mymoddata = value;
    		$scope.mymoddatalist = $scope.mymoddata.list[0];
		}else if(type==2){
    		$scope.mymoddatalist = value;
		}
	};
	$scope.FontConfig.colorFn();
	$scope.changeColor = function(v,a){
		if(v==""){return false;}
		if(v!="defaultColor" && v!="transparent"){
			if(v.indexOf("#")>=0){
				if(v.length!=4&&v.length!=7){
					Diamsg({content:'您输入的颜色有误，已帮你取消操作'});
					$scope.FontConfig.defaultColor = $scope.FontConfig.prevdefaultColor;
					return false;
				}
			}else if(v.indexOf("rgb")>=0){
				var checkv = v.split(",");
				var ischeckv = false;
				$.each(checkv,function(i,n){
					if(!tonumber(n) && tonumber(n)!==0){
						ischeckv = true;
					};
				});
				if(ischeckv){
					Diamsg({content:'您输入的颜色有误，已帮你取消操作'});
					$scope.FontConfig.defaultColor = $scope.FontConfig.prevdefaultColor;
					return false;
				}
			}else if(v.indexOf("transparent")<0){
				Diamsg({content:'您输入的颜色有误，已帮你取消操作'});
				$scope.FontConfig.defaultColor = $scope.FontConfig.prevdefaultColor;
				return false;
			};
		}
		if(a){
			var newColor = true;
			$.each($scope.FontConfig.color,function(i,n){
				$.each(n,function(i,m){
					if(m==v){
						newColor = false;
						return false;
					}
				})
			})
			if(newColor){
				if(v!="defaultColor" && v!="transparent"){
					$scope.FontConfig.color[7] ? $scope.FontConfig.color[7].push(v) : $scope.FontConfig.color.push([v]);
				}
			}
			$("body").click();
		}
		if(a!=2){
			$scope.FontConfig.defaultColor = v ? v : "";
			$scope.FontConfig.prevdefaultColor = v ? v : "";
			$scope.pages.color = $scope.FontConfig.defaultColor;
			$scope.FontConfig.colorFn();
		}
	}
	$scope.changeTempColor = function(s,v,a){
		$scope.myPageEdit.edit.color = v ? v : "";
		if(a){
			var newColor = true;
			$.each($scope.FontConfig.color,function(i,n){
				$.each(n,function(i,m){
					if(m==v){
						newColor = false;
						return false;
					}
				})
			})
			if(newColor){
				$scope.FontConfig.color[7] ? $scope.FontConfig.color[7].push(v) : $scope.FontConfig.color.push([v]);
			}
		}
	}

	$scope.history = {nowhistory:0,allhistory:0,history:[angular.copy($scope.pages.temp)]};
    $scope.goHistory = function(type){
        if(type=="prev"){
            if($scope.history.nowhistory==0){ return false;}
            $scope.history.nowhistory --;
            $scope.pages.temp = angular.copy($scope.history.history[$scope.history.nowhistory]);
        }else if(type=="next"){
            if($scope.history.nowhistory==$scope.history.allhistory){return false;}
            $scope.history.nowhistory ++;
            $scope.pages.temp = angular.copy($scope.history.history[$scope.history.nowhistory]);
        }else{
			$scope.ischange = false;
            $scope.history.allhistory = $scope.history.nowhistory;
            $scope.history.history.splice($scope.history.allhistory+1,$scope.history.history.length-$scope.history.allhistory);
            $scope.history.nowhistory++;
            $scope.history.allhistory = $scope.history.nowhistory;
            $scope.history.history.push(angular.copy($scope.pages.temp));
            $.each($scope.history.history[$scope.history.history.length-1],function(i,n){
                n.add = false;
            });
        }
    };
	$scope.addPage = function(v,index,ismy){
		if($scope.iscopyHtml){$scope.iscopyHtml = false;$("#editingListCopy").html("");}
		var tempFn = function(temp,index){
			var index = index+1 || (index===0?index:$scope.pages.temp.length);
			$scope.pages.temp.splice(index,0,angular.copy(temp));
			$scope.pages.temp[index]["uiEdit"] = true;
			$scope.pages.temp[index]["add"] = true;
			if($scope.pages.temp[index].text){
				$.each($scope.pages.temp[index].text,function(i,n){
					n["beginEdit"] = angular.copy(n.edit);
        			delete n.uiMyTemp;
				});
			}
			if($scope.pages.temp[index].img){
				$.each($scope.pages.temp[index].img,function(i,n){
					n["beginEdit"] = angular.copy(n.edit);
        			delete n.uiMyTemp;
				});
			}
		}
		if(v.data){
			$.each(v.data,function(i,n){
				tempFn(n,index,true);
			})
		}else{
			tempFn(v,index);
		}
		$('.subcon').stop(true,true)
		$('.subcon').animate({
			scrollTop : $('.subcon')[0].scrollHeight
		},600);
		$timeout(function() {
			$.each($scope.pages.temp,function(i,n){
				delete n.add;
			});
        },200);
        $scope.goHistory();
	};
    $scope.clearpage = function(){
		if($scope.iscopyHtml){$scope.iscopyHtml = false;$("#editingListCopy").html("");}
        event.stopPropagation();
        $scope.pages.temp = [];
        $scope.goHistory();
    };
    $scope.clearv = function(v){
        $scope.myValueEdit = v;
    };
	$scope.delmyPage = function(v){
		var delindex = "";
		$.each($scope.pages.temp,function(i,n){
			if(n==v){delindex = i;return false;}
		});
		$scope.pages.temp[delindex]['del'] = true;
		$timeout(function() {
			$scope.pages.temp.splice(delindex,1);
        	$scope.goHistory();
        },200);
	}
	$scope.edui = function(type,v){
		if(!type){
			$.each($scope.myPageEdit.edit,function(i,n){
				if(typeof($scope.myPageEdit.beginEdit[i])!="undefined"){
					$scope.myPageEdit.edit[i] = angular.copy($scope.myPageEdit.beginEdit[i]);
				}
			});
			$timeout(function() {
				editTxtFixingFn();
			},100);
			return false;
		};
		if(type=="fontWeight"){
			$scope.myPageEdit.edit[type] == "bold" ? $scope.myPageEdit.edit[type] = "normal" : $scope.myPageEdit.edit[type] = "bold";
		}else if(type=="fontStyle"){
			$scope.myPageEdit.edit[type] == "italic" ? $scope.myPageEdit.edit[type] = "normal" : $scope.myPageEdit.edit[type] = "italic";
		}else if(type=="ngUeditor"){
			
		}else{
			if($scope.myPageEdit.edit[type]=="defaultColor"){
				$scope.myPageEdit.edit["defaultColor"] = v;
				$.each($scope.pages.temp,function(i,n){
					if(n.text){
						$.each(n.text,function(j,m){
							if($scope.myPageEdit==m){
								n.defaultColor = v;
							}
						})
					}
				});
			}else if(type=="fontFamily"){
				if(v != "微软雅黑" && $scope.FontConfig.isfontfamily){
					$scope.FontConfig.isfontfamily = false;
					$timeout(function() {
						Diamsg({
		                    content:'使用特殊字体会影响图文打开速度，并且通过复制到微信的图文将丢失字体哦。',
		                    okVal:"我知道了"
		                });
			        },30);
				}
				$scope.myPageEdit.edit[type] = v;
			}else{
				$scope.myPageEdit.edit[type] = v;
			}
		};
		$timeout(function() {
			editTxtFixingFn();
		},100);
	}
	$scope.iscopyHtml = false;
	$scope.copyHtml = function(){
		if($scope.iscopyHtml){$scope.iscopyHtml = false;$("#editingListCopy").html("");return false;}
		$scope.iscopyHtml = true;
		var html = $(".editingListEdit").html();
		$(html).find(".includeDiv").each(function(){
			$("#editingListCopy").append($(this).html())
		})
		$("#editingListCopy").find("[contenteditable]","[ng-style]","[ng-bind-html]","[ui-temp]","[ui-edit]","[ng-model]").each(function(){
			$(this).removeAttr("contenteditable");
			$(this).removeAttr("ng-style");
			$(this).removeAttr("ng-bind-html");
			$(this).removeAttr("ui-temp");
			$(this).removeAttr("ui-edit");
			$(this).removeAttr("ng-model");
		})
		$('.subcon').scrollTop(0);
	}
}]);
app.filter('toHtml', ['$sce', function ($sce) {
    return function (text) {
        return $sce.trustAsHtml(text);
    };
}]);
angular.module('ui.temp',[]).directive('uiTemp',['$timeout','$compile',function($timeout,compile){
	return{
		restrict: "A",
		require: '?^ngModel',
		compile:function(element,attr,transclude){
			if(attr.uiTemp.indexOf("img")!=-1){
				var index = attr.uiTempIndex ? attr.uiTempIndex : 0;
				var uiType = attr.uiStyle;
				if(element.is("img")){
					element.attr("ng-src","{{value.img["+index+"].img}}");
				}else{
					element.attr("ng-style","{'background-image':'url('+value.img["+index+"].img+')','background-position':'center center','background-repeat':'no-repeat','background-size':'cover'}");
				}
			}
			element.removeAttr("ui-temp");
			return function(scope, element, attr,ngModel){
				$timeout(function() {
					var index = attr.uiTempIndex ? attr.uiTempIndex : 0;
					var newelement = element.find("[ui-text]").length==0 ? element : element.find("[ui-text]");
					if(ngModel.$viewValue.uiEdit && !element.is("img")){
						if(typeof(element.attr("data-backimg"))=="undefined"){
							newelement.attr("ui-edit","");
							newelement.attr("contenteditable",false);
							newelement.attr("ng-model","value.text["+index+"]");
							//newelement.attr("ng-ueditor","value.text["+index+"]");
						}else{
							newelement.attr("ui-edit","");
							newelement.attr("ng-model","value.img["+index+"]");
						}
					}else if(ngModel.$viewValue.uiEdit && element.is("img")){
						newelement.attr("ui-edit","");
						newelement.attr("ng-model","value.img["+index+"]");
					}
					if(!element.is("img") && typeof(element.attr("data-backimg"))=="undefined"){
						newelement.attr("ng-bind-html","value.text["+index+"].text | toHtml");
					}
					compile(element)(scope)
		        },10);
			}
		}
	};
}]);
angular.module('ui.style',[]).directive('uiStyle',['$timeout','$compile',function($timeout,compile){
	return{
		restrict: "A",
		require: '?^ngModel',
		compile:function(element,attr,transclude){
			var index = attr.uiTempIndex ? attr.uiTempIndex : 0;
			var uiType = attr.uiStyle;
			element.removeAttr("ui-style");
			return function(scope, element, attr,ngModel){
				$timeout(function() {
					var defaultColor = ngModel.$modelValue.text[index].edit.defaultColor ? ngModel.$modelValue.text[index].edit.defaultColor : ngModel.$modelValue.defaultColor;
					var edit = ngModel.$modelValue.text[index].edit || [];
					var text = ngModel.$modelValue.text[index].text || [];
					var ngStyle = "";
					$.each(edit,function(i,n){
						var newname = i.replace(/[A-Z]/,function($1,inx){return "-"+$1.toLowerCase();});
						var islast = i!=edit.length-1 ? "," : "";
						if(n=="defaultColor"){
							if(ngModel.$modelValue.uiEdit || ngModel.$modelValue.uiMyTemp){
								edit["defaultColor"] = defaultColor || angular.copy(scope.$parent.$parent.FontConfig.defaultColor);
								ngStyle += "'"+newname+"'"+":value.text["+index+"].edit.defaultColor || 'inherit'" + islast
							}else{
								ngStyle += "'"+newname+"'"+":"+"value.defaultColor" + islast
							}
						}else{
							if(newname!="background-image"){
								ngStyle += "'"+newname+"'"+":value.text["+index+"].edit."+i+" || 'inherit'" + islast
							}else{
								ngStyle += "'"+newname+"'"+":'url('+value.text["+index+"].edit."+i+"+')' || 'none','background-position':'center center','background-repeat':'no-repeat','background-size':'cover'" + islast
							}
						}
					})
					var newelement = element.find("[ui-text]").length==0 ? element : element.find("[ui-text]");
					newelement.attr("ng-style","{"+ngStyle+"}");
					///newelement.attr("ng-ueditor","");
					scope.$apply(function() {
						ngModel.$modelValue;
					});
					compile(element)(scope)
		        },0);
			}
		}
	};
}]);
angular.module('ui.edit',[]).directive('uiEdit',['$timeout','$compile',function($timeout,compile){
	return{
		restrict: "A",
		scope:false,
		require: '?^ngModel',
		link: function(scope, element, attrs, ngModel) {
			var isbackimg = typeof(element.attr("data-backimg"))=="string";
			element.on("dblclick",function(){
				event.stopPropagation();
				var callbackimg = function(opt){
							if(typeof(ngModel.$modelValue.img)!="undefined"){
		                    	ngModel.$modelValue.img = opt.src;
		                    }else if(typeof(ngModel.$modelValue.edit.backgroundImage)=="string"){
		                    	ngModel.$modelValue.edit.backgroundImage = opt.src;
		                    }
		                    scope.$apply(function() {
		                    	scope.goHistory();
		                    });
		                }
				if(typeof(ngModel.$modelValue.img)!="undefined" || typeof(ngModel.$modelValue.edit.backgroundImage)=="string"){
					open_img_uploader({
	                    width:true,
	                    height:true,
						callback:function(opt){
	                        callbackimg(opt);
	                    }
					});
				}
			});
			element.on('mouseover',function(){
        		if($(this).is("img") || isbackimg){
					if($(".editTxtFixing").hasClass("working")){return false;}
					editTxtFixing = element;
        			var text=element.width()<170 ?"双击更换":"双击图片，在图片库中选择更换";
    				editTxtFixingFn();
    				$(".editImgFix").show().addClass("working").find(".editImgP").text(text);
        		}else{
					editTxtFixingFn();
        			$(".editTxtFix").show();
        			element.attr("contenteditable",true);
        		}
			});
			element.on('mouseout',function(){
				$(".editImgFix").hide();
				$(".editTxtFix").hide();
			});
			element.on('keydown keyup',function(){
    			editTxtFixingFn();
			});
			var newHtml = "";
			element.on('focus', function() {
				if(element.is("img") || isbackimg){return false;}
				editTxtFixing = element;
				editTxtFixingFn();
    			$(".editTxtFixing").show().addClass("working");
				element.addClass("editing");
				newHtml = element.text();
				scope.$apply(function() {
					scope.isfouce = true;
					scope.$parent.$parent.myPageEdit = ngModel.$modelValue;
					if(scope.$parent.$parent.myPageEdit.edit){
						var myPageEditSize = 0;
						var iscolor = [];
						var tempColor = [];
						$.each(scope.$parent.$parent.myPageEdit.edit,function(i,n){
							if(i!="myPageEditSize" && i!="iscolor" && i!="tempColor" && i!="backgroundImage"){
								if(i=="textAlign"){
									myPageEditSize+=3;
								}else{
									myPageEditSize++;
								}
							}
							if(i=="color" || i=="backgroundColor" || i=="borderColor"){
								if(n=="defaultColor"){
									tempColor.push(i);
								}else{
									iscolor.push(i);
								}
							}
						})
						scope.$parent.$parent.myPageEdit.edit["myPageEditSize"] = myPageEditSize;
						scope.$parent.$parent.myPageEdit.edit["iscolor"] = iscolor;
						scope.$parent.$parent.myPageEdit.edit["tempColor"] = tempColor;
						if(scope.$parent.$parent.myPageEdit.edit["tempColor"].length==0){
							scope.$parent.$parent.myPageEdit.edit["myPageEditSize"] += 1;
						}
					}
				});
			});
			element.on('blur', function() {
				var html = element.html().replace(/<([\/]?)(div)((:?\s*)(:?[^>]*)(:?\s*))>/g, '<$1p$3>');
				ngModel.$modelValue.text = html;
    			$(".editTxtFixing").hide().removeClass("working");
				scope.$apply(function() {
					scope.isfouce = false;
					scope.$parent.$parent.myPageEdit='';
					if(element.text()!=newHtml){
        				scope.goHistory();
					}
				});
				element.attr("contenteditable",false);
				element.removeClass("editing");
			});
		}
	};
}])

setInterval("autoSave()", 1000*60*50);
function autoSave(){
    $('#autoSave').val(1);
    $('#isPreview').val(0);
    $('#btn-save').click();
}

var iscommand = false;
var clip = null;
var editTxtFixing = null;
var position = function(element){
	return {
			top:element.offset().top,
			left:element.offset().left,
			width:element.width()+tonumber(element.css("padding-left"))+tonumber(element.css("padding-right"))+tonumber(element.css("border-left-width"))+tonumber(element.css("border-right-width")),
			height:element.height()+tonumber(element.css("padding-top"))+tonumber(element.css("padding-bottom"))+tonumber(element.css("border-top-width"))+tonumber(element.css("border-bottom-width"))
	}
}
var editTxtFixingFn = function(obj){
		var obj = obj ? obj : editTxtFixing;
		if(obj){
			$(".editTxtFixing,.editTxtFix,.editImgFix").css({
				top:position(obj).top,
				left:position(obj).left,
				width:position(obj).width,
				height:position(obj).height
			});
		}
	}
$(document).ready(function(e) {
    ZeroClipboard.config({swfPath:'./Home/Public/Js/jquery-ui-1.11.2.custom/ZeroClipboard.swf'});
	clip = new ZeroClipboard($("#iconCopy"));
	clip.on("beforecopy", function(e){
		clip.setHtml($("#editingListCopy").html());
	});
	clip.on("copy", function(e){
		clip = new ZeroClipboard($("#iconCopy"));
	});
	$("#btn-music").click(function(){
		var data = $(this).attr("data-rel");
		var obj = $(this).closest(".Gchoose");
		var defaults = {
				obj:obj,//对象
				Gform:true,//是否Gform
				txtmsg:"",//备注
				callback:"Music"//callback
			}
		var musicuploadData = $.extend(true, {}, defaults, eval('('+data+')'));
        open_music_uploader(musicuploadData);
        return;
	});
	$("#delbtn-music").click(function(){
		basicData.music = "";
		updateMusic();
        return;
	});
	$("body").on("mousedown",".editingItem .icon-move",function(e){
		var editTextObj = $(this).closest(".editingItem").find(".editText");
		editTextObj.addClass("ui-begin-move");
	}).on("mouseup",function(e){
		$(".editText").removeClass("ui-begin-move")
	});
	$("body").on("click",".btn-close", function() {
	    artDialog.confirm('离开当前页面，如果没有保存，所做的修改会丢失，您确定要离开?',function () {
            location.href = "/index.php?g=MarketActive&m=NewPictext&a=index";
        });
	});

	$(".subcon").on("scroll",function(){
		editTxtFixingFn();
	})
});
function Music(options){
	var defaults = {obj:false,src:false}
	var opts = $.extend(true, {}, defaults, options);
	var t = opts.obj ? opts.obj : alert("缺少obj") ;
	var src = opts.src ? opts.src : alert("缺少src") ;
	var savename = opts.savename ? opts.savename : alert("缺少名称") ;
	t.find(">input").val(src);
	basicData.music = src;
	updateMusic();
}
angular.module('ng.contextmenu',[]).directive('ngContextmenu', function($parse) {  
	return function(scope, element, attrs){
		var fn = $parse(attrs.ngContextmenu);
		element.on('contextmenu', function(){
			scope.$apply(function(){
				event.preventDefault();
				fn(scope, {$event:event});
			});
		});
	};
});
angular.module('ui.move',[]).directive('uiMove',[function(){
	return{
		require: '?ngModel',
		scope: {
			ngModel: '='
		},
		link: function(scope, element, attrs, ngModel) {
			var draggableopts = {
					cursorAt: { cursor:'move'},
					appendTo: '#main',
					opacity:0.5,
					scroll:false,
					distance:10,
					containment:"#wrapper"
				};
			element.draggable(draggableopts);
		}
	};
}]);
angular.module('ui.draggable',[]).directive('uiDraggable',[function(){
	return{
		require: "?ngModel",
		scope: false,
		link: function(scope, element, attrs, ngModel) {
			var startLeft = 0,startTop = 0,startWidth = 0,startHeight = 0,guides = [],innerOffsetX,innerOffsetY,snapTolerance = 0;
			var draggableopts = {
				opacity:0.5,
				delay:20,
				snap:".guidec,.Modlist",
				snapTolerance:3,
				distance:5,
				containment:".Mcon",
				grid: [1,1],
				start: function(event,ui){
					startLeft = ui.position.left;
					startTop = ui.position.top;
					guides = $.map($(".Modlist,.guidec").not( this ),getGuides);
				},
				stack:".Modlist",
				drag : function(event, ui){
			        var guideV, guideH, offsetV, offsetH; 
			        var $t = $(this);
			        var pos = { top:ui.position.top, left:ui.position.left}; 
			        var w = $t.width();
			        var h = $t.height();
			        var elemGuides = getGuides( null, pos, w, h );
			        $("#guide").html("");
			        $.each( elemGuides, function( i, elemGuide ){
						var isguide = false,isguideH=0,isguideW=0,isguideTop=480,isguideLeft=480,isguideHeight=0,isguideWidth=0;
			            $.each( guides, function( i, guide ){
			                if( guide.type == elemGuide.type ){
		                		var guideHeight = Math.abs(guide.top-elemGuide.top);
		                		var guideWidth = Math.abs(guide.left-elemGuide.left);
		                		var snapLeft = Math.abs(guide.left-elemGuide.left);
		                		var snapTop = Math.abs(guide.top-elemGuide.top);
		                		var guideTop = Math.min(guide.top,elemGuide.top);
		                		var guideLeft = Math.min(guide.left,elemGuide.left);
		                		if( snapLeft <= snapTolerance ){
		                			isguide = "v";
		                			isguideHeight = Math.max(isguideHeight,guideHeight);
		                			isguideTop = Math.min(isguideTop,guideTop);
		                		}
		                		if( snapTop <= snapTolerance ){
		                			isguide = "h";
		                			isguideWidth = Math.max(isguideWidth,guideWidth);
		                			isguideLeft = Math.min(isguideLeft,guideLeft);
		                		}
			                }
			            });
						if(isguide == "v"){
		                	$("#guide").append('<div class="guide guide-v" style="top:'+isguideTop+'px;left:'+elemGuide.left+'px;height:'+isguideHeight+'px;"></div>');
						}else if(isguide == "h"){
		                	$("#guide").append('<div class="guide guide-h" style="left:'+isguideLeft+'px;top:'+elemGuide.top+'px;width:'+isguideWidth+'px;"></div>');
						}
			        });

					var moveLeft = ui.position.left - startLeft;
					var moveTop = ui.position.top - startTop;
					$.each(scope.$parent.ModChecked,function(i,n){
						scope.$apply(function(){
							n.left += moveLeft;
							n.top += moveTop;
						});
					});
					startLeft = ui.position.left;
					startTop = ui.position.top;
		
				},
				stop : function(e, ui){
					scope.$apply(function(){
						ngModel.$modelValue.left = ui.position.left;
						ngModel.$modelValue.top = ui.position.top;
					});
					scope.goHistory();
        			$("#guide").html("");
				}
			};
			var resizableopts = {
				handles: "rt,n, e, s, w, ne, se, sw, nw",
				minHeight:1,
				minWidth:1,
				start: function(event,ui){
					startLeft = ui.position.left;
					startTop = ui.position.top;
					startWidth = ui.size.width;
					startHeight = ui.size.height;
					guides = $.map($(".Modlist,.guidec").not( this ),getGuides);
				},
				resize : function(e, ui){
					var guideV, guideH, offsetV, offsetH; 
			        var $t = $(this);
			        var pos = { top:ui.position.top, left:ui.position.left}; 
			        var w = $t.width();
			        var h = $t.height();
			        var elemGuides = getGuides( null, pos, w, h );
			        $("#guide").html("");
			        $.each( elemGuides, function( i, elemGuide ){
						var isguide = false,isguideH=0,isguideW=0,isguideTop=480,isguideLeft=480,isguideHeight=0,isguideWidth=0;
			            $.each( guides, function( i, guide ){
			                if( guide.type == elemGuide.type ){
		                		var guideHeight = Math.abs(guide.top-elemGuide.top);
		                		var guideWidth = Math.abs(guide.left-elemGuide.left);
		                		var snapLeft = Math.abs(guide.left-elemGuide.left);
		                		var snapTop = Math.abs(guide.top-elemGuide.top);
		                		var guideTop = Math.min(guide.top,elemGuide.top);
		                		var guideLeft = Math.min(guide.left,elemGuide.left);
		                		if( snapLeft <= snapTolerance ){
		                			isguide = "v";
		                			isguideHeight = Math.max(isguideHeight,guideHeight);
		                			isguideTop = Math.min(isguideTop,guideTop);
		                		}
		                		if( snapTop <= snapTolerance ){
		                			isguide = "h";
		                			isguideWidth = Math.max(isguideWidth,guideWidth);
		                			isguideLeft = Math.min(isguideLeft,guideLeft);
		                		}
			                }
			            });
						if(isguide == "v"){
		                	$("#guide").append('<div class="guide guide-v" style="top:'+isguideTop+'px;left:'+elemGuide.left+'px;height:'+isguideHeight+'px;"></div>');
						}else if(isguide == "h"){
		                	$("#guide").append('<div class="guide guide-h" style="left:'+isguideLeft+'px;top:'+elemGuide.top+'px;width:'+isguideWidth+'px;"></div>');
						}
			        });


					var moveLeft = ui.position.left - startLeft;
					var moveTop = ui.position.top - startTop;
					var moveWidth = ui.size.width - startWidth;
					var moveHeight = ui.size.height - startHeight;
					$.each(scope.$parent.ModChecked,function(i,n){
						scope.$apply(function(){
							n.left += moveLeft;
							n.top += moveTop;
							n.width += moveWidth;
							n.height += moveHeight;
						});
					});
					startLeft = ui.position.left;
					startTop = ui.position.top;
					startWidth = ui.size.width;
					startHeight = ui.size.height;
				},
				stop : function(e, ui) {
					scope.$apply(function () {
						ngModel.$modelValue.width = ui.size.width;
						ngModel.$modelValue.height = ui.size.height;
						ngModel.$modelValue.top = ui.position.top;
						ngModel.$modelValue.left = ui.position.left;
						var newrotate = ui.rotate.toString().split(".");
							newrotate = newrotate[0];
						var rotateRule = [[49,41,45],[94,86,90],[139,131,135],[-86,-94,-90],[-41,-49,-45],[-131,-139,-135],[-176,-180,-180],[0,-4,0],[4,0,0]];
						ngModel.$modelValue.rotate = newrotate;
						$.each(rotateRule,function(i,n){
							if(newrotate<=n[0] && newrotate>=n[1]){
								ngModel.$modelValue.rotate = n[2];
							}
						});
					});
					$("#guide").html("");
					scope.goHistory();
				}
			};
			element.draggable(draggableopts);
			element.resizable(resizableopts);
		}
	};
}]);
angular.module('ui.hotkey',[]).directive('uiHotkey',[function(){
	return{
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs, ngModel) {
			$(document).bind('keydown', 'esc', function(){
				if(!scope.isfouce){
					$("body,.editTxtlistLinkfixed").click();
					return false;
				}
			}).bind('keydown', 'ctrl', function(){
				if(!scope.isfouce){
					scope.$apply(function(){
						scope.Ctrl = true;
					});
					return false;
				}
			}).bind('keyup', 'ctrl', function(){
				if(!scope.isfouce){
					scope.$apply(function(){
						scope.Ctrl = false;
					});
					return false;
				}
			}).bind('keydown', 'ctrl+z', function(){
				if(!scope.isfouce){
					$(".font-icon.icon-reply.ok").click();
					return false;
				}
			}).bind('keydown', 'ctrl+y', function(){
				if(!scope.isfouce){
					$(".font-icon.icon-forward.ok").click();
					return false;
				}
			});
			$(document).bind('keydown',function(e){
				if(!scope.isfouce){
					if(e.keyCode==91){
						iscommand = true;
						scope.$apply(function(){
							scope.Ctrl = true;
						});
						return false;
					}
				}else if(e.keyCode==90){//z
					if(!scope.isfouce){
						if(iscommand){
							$(".font-icon.icon-reply.ok").click();
							return false;
						}
					}
				}else if(e.keyCode==89){//y
					if(!scope.isfouce){
						if(iscommand){
							$(".font-icon.icon-forward.ok").click();
							return false;
						}
					}
				}
			}).bind('keyup', function(e){
				if(!scope.isfouce){
					if(e.keyCode==91){
						if(iscommand){
							iscommand = false;
							scope.$apply(function(){
								scope.Ctrl = false;
							});
							return false;
						}
					}
				}
			});
		}
	};
}]);
angular.module('ui.selectable',[]).directive('uiSelectable',[function(){
	return{
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs, ngModel) {
			var selectable = {
				filter:".Modlist",
				distance:10,
				selected: function() {
					scope.ModChecked = [];
					$.each(ngModel.$modelValue.mod,function(i,n){
						n["ModChecked"] = false;
					});
					$("#phoneCon .Modlist").each(function(index) {
						if($(this).hasClass("ui-selected") && !$(this).hasClass("lock")){
							scope.ModChecked.push(ngModel.$modelValue.mod[index])
							ngModel.$modelValue.mod[index]["ModChecked"] = true;
						}
					});
					scope.$apply(function(){
						scope.ModChecked;
					});
				}
			};
			element.selectable(selectable);
		}
	};
}]);
angular.module('ui.adddraggable',[]).directive('uiAdddraggable',[function(){
	return{
		require: '?ngModel',
		scope: {
			ngModel: '='
		},
		link: function(scope, element, attrs, ngModel) {
			element.draggable({
				cursorAt: { cursor:'move', top:30, left:30},
				cancel:".disabled",
				appendTo: '#main',
				helper: "clone",
				opacity:0.6,
				scroll:false,
				containment:"#wrapper",
				distance:10,
				connectToSortable: ".swrapperCon"
			});
			$(".swrapperCon").droppable({
				accept: ".GheaderAdd .GheaderAddItem",
				greedy:true,
				handle:".modMove",
				drop: function(event, ui){
					ui.draggable.click();
				}
			});
		}
	};
}]);
angular.module('spectrum',[]).directive('uiSpectrum',[function(){
	return{
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs,ngModel) {
			element.attr("type","text");
			scope.$watch('myMod', function(newValue, oldValue) {
				element.spectrum({
					color:element.val(),
					showSelectionPalette: true,
					showAlpha: true,
					preferredFormat: "rgb",
					move: function (color) {
						element.val(color).trigger("change");
					}
				});
			});
		}
	};
}]);

angular.module('ui.sortable',[]).value('uiSortableConfig',{}).directive('uiSortable',['uiSortableConfig','$timeout','$log',function(uiSortableConfig,$timeout,$log){
	return {
		require: '?ngModel',
        scope: {
          ngModel: '=',
          uiSortable: '='
        },
        link: function(scope, element, attrs, ngModel) {
          var savedNodes;
		  function combineCallbacks(first,second){
            if(second && (typeof second === 'function')) {
              return function() {
                first.apply(this, arguments);
                second.apply(this, arguments);
              };
            }
            return first;
          }

          function getSortableWidgetInstance(element) {
            var data = element.data('ui-sortable');
            if (data && typeof data === 'object' && data.widgetFullName === 'ui-sortable') {
              return data;
            }
            return null;
          }

          function hasSortingHelper (element, ui) {
            var helperOption = element.sortable('option','helper');
            return helperOption === 'clone' || (typeof helperOption === 'function' && ui.item.sortable.isCustomHelperUsed());
          }

          function isFloating (item) {
            return (/left|right/).test(item.css('float')) || (/inline|table-cell/).test(item.css('display'));
          }

          function getElementScope(elementScopes, element) {
            var result = null;
            for (var i = 0; i < elementScopes.length; i++) {
              var x = elementScopes[i];
              if (x.element[0] === element[0]) {
                result = x.scope;
                break;
              }
            }
            return result;
          }

          function afterStop(e, ui) {
            ui.item.sortable._destroy();
          }

          var opts = {distance:20,axis:"y",handle:".icon-move",placeholder: "editingItemHelp",opacity:0.9};
          var directiveOpts = {
            'ui-floating': undefined
          };

          var callbacks = {
            receive: null,
            remove:null,
            start:null,
            stop:null,
            update:null
          };

          var wrappers = {
            helper: null
          };

          angular.extend(opts, directiveOpts, uiSortableConfig, scope.uiSortable);

          if (!angular.element.fn || !angular.element.fn.jquery) {
            $log.error('ui.sortable: jQuery should be included before AngularJS!');
            return;
          }

          if (ngModel) {

            scope.$watch('ngModel.length', function() {
              $timeout(function() {
                if (!!getSortableWidgetInstance(element)) {
                  element.sortable('refresh');
                }
              }, 0, false);
            });

            callbacks.start = function(e, ui) {
              if (opts['ui-floating'] === 'auto') {
                var siblings = ui.item.siblings();
                var sortableWidgetInstance = getSortableWidgetInstance(angular.element(e.target));
                sortableWidgetInstance.floating = isFloating(siblings);
              }

              ui.item.sortable = {
                model: ngModel.$modelValue[ui.item.index()],
                index: ui.item.index(),
                source: ui.item.parent(),
                sourceModel: ngModel.$modelValue,
                cancel: function () {
                  ui.item.sortable._isCanceled = true;
                },
                isCanceled: function () {
                  return ui.item.sortable._isCanceled;
                },
                isCustomHelperUsed: function () {
                  return !!ui.item.sortable._isCustomHelperUsed;
                },
                _isCanceled: false,
                _isCustomHelperUsed: ui.item.sortable._isCustomHelperUsed,
                _destroy: function () {
                  angular.forEach(ui.item.sortable, function(value, key) {
                    ui.item.sortable[key] = undefined;
                  });
                }
              };
            };

            callbacks.activate = function(e, ui) {
              savedNodes = element.contents();

              var placeholder = element.sortable('option','placeholder');
              if (placeholder && placeholder.element && typeof placeholder.element === 'function') {
                var phElement = placeholder.element();
                phElement = angular.element(phElement);

                var excludes = element.find('[class="' + phElement.attr('class') + '"]:not([ng-repeat], [data-ng-repeat])');

                savedNodes = savedNodes.not(excludes);
              }

              var connectedSortables = ui.item.sortable._connectedSortables || [];

              connectedSortables.push({
                element: element,
                scope: scope
              });

              ui.item.sortable._connectedSortables = connectedSortables;
            };

            callbacks.update = function(e, ui) {
              if(!ui.item.sortable.received) {
                ui.item.sortable.dropindex = ui.item.index();
                var droptarget = ui.item.parent();
                ui.item.sortable.droptarget = droptarget;

                var droptargetScope = getElementScope(ui.item.sortable._connectedSortables, droptarget);
                ui.item.sortable.droptargetModel = droptargetScope.ngModel;
                element.sortable('cancel');
              }

              if (hasSortingHelper(element, ui) && !ui.item.sortable.received &&
                  element.sortable( 'option', 'appendTo' ) === 'parent') {
                savedNodes = savedNodes.not(savedNodes.last());
              }
              savedNodes.appendTo(element);
              if(ui.item.sortable.received) {
                savedNodes = null;
              }

              if(ui.item.sortable.received && !ui.item.sortable.isCanceled()) {
                scope.$apply(function () {
                  ngModel.$modelValue.splice(ui.item.sortable.dropindex, 0,
                                             ui.item.sortable.moved);
                });
              }
            };

            callbacks.stop = function(e, ui) {
              if(!ui.item.sortable.received &&
                 ('dropindex' in ui.item.sortable) &&
                 !ui.item.sortable.isCanceled()) {

                scope.$apply(function () {
                  ngModel.$modelValue.splice(
                    ui.item.sortable.dropindex, 0,
                    ngModel.$modelValue.splice(ui.item.sortable.index, 1)[0]);
                });
              } else {
                if ((!('dropindex' in ui.item.sortable) || ui.item.sortable.isCanceled()) &&
                    !hasSortingHelper(element, ui)) {
                  savedNodes.appendTo(element);
                }
              }

              savedNodes = null;
            };

            callbacks.receive = function(e, ui) {
              ui.item.sortable.received = true;
            };

            callbacks.remove = function(e, ui) {
              if (!('dropindex' in ui.item.sortable)) {
                element.sortable('cancel');
                ui.item.sortable.cancel();
              }
              if (!ui.item.sortable.isCanceled()) {
                scope.$apply(function () {
                  ui.item.sortable.moved = ngModel.$modelValue.splice(
                    ui.item.sortable.index, 1)[0];
                });
              }
            };

            wrappers.helper = function (inner) {
              if (inner && typeof inner === 'function') {
                return function (e, item) {
                  var innerResult = inner.apply(this, arguments);
                  item.sortable._isCustomHelperUsed = item !== innerResult;
                  return innerResult;
                };
              }
              return inner;
            };

            scope.$watch('uiSortable', function(newVal) {
              var sortableWidgetInstance = getSortableWidgetInstance(element);
              if (!!sortableWidgetInstance) {
                angular.forEach(newVal, function(value, key) {
                  if (key in directiveOpts) {
                    if (key === 'ui-floating' && (value === false || value === true)) {
                      sortableWidgetInstance.floating = value;
                    }

                    opts[key] = value;
                    return;
                  }
                  if (callbacks[key]) {
                    if( key === 'stop' ){
                      value = combineCallbacks(value,function(){scope.$apply();});
                      value = combineCallbacks(value, afterStop);
                    }
                    value = combineCallbacks(callbacks[key], value);
                  } else if (wrappers[key]) {
                    value = wrappers[key](value);
                  }
                  opts[key] = value;
                  element.sortable('option', key, value);
                });
              }
            }, true);
            angular.forEach(callbacks, function(value, key) {
              opts[key] = combineCallbacks(value, opts[key]);
              if( key === 'stop' ){
                opts[key] = combineCallbacks(opts[key], afterStop);
              }
            });
          } else {
            $log.info('ui.sortable: ngModel not provided!', element);
          }
          element.sortable(opts);
        }
      };
    }
  ]);

angular.module("ng.ueditor", []).directive("ngUeditor",['$timeout',function($timeout) {
	return {
		restrict: "AE",
		require: "?ngModel",
		scope:false,
		link: function(scope, element, attr, ngModel) {
				var _simpleConfig={toolbar:['fontfamily','fontsize','forecolor','backcolor','bold','justifyleft','justifyright','justifycenter','lineheight','link','unlink','removeformat'],topOffset:0,initialFrameWidth:"100%",initialFrameHeight:"100%",autoHeightEnabled:true,lineheight:[1,2,3,4,5],fontsize:[12,14,16,18,20,24,32,40,48,64,96],fontfamily:[{label:'默认字体',name:'yahei',val:'微软雅黑,Microsoft YaHei'},{label:'',name:'songti',val:'宋体,SimSun'},{label:'方正静蕾简体',name:'方正静蕾简体',val:'方正静蕾简体'},{label:'方正正黑简体',name:'方正正黑简体',val:'方正正黑简体'},{label:'汉仪菱心简体',name:'汉仪菱心简体',val:'汉仪菱心简体'},{label:'Arial',name:'Arial',val:'Arial'},{label:'BRADHITC',name:'BRADHITC',val:'BRADHITC'},{label:'BRUSHSCI',name:'BRUSHSCI',val:'BRUSHSCI'},{label:'CASTELAR',name:'CASTELAR',val:'CASTELAR'},{label:'IMPACT',name:'IMPACT',val:'IMPACT'},{label:'KUNSTLER',name:'KUNSTLER',val:'KUNSTLER'}]};

			$timeout(function() {
				var myedui = ngModel.$modelValue.edui;
				var mytext = ngModel.$modelValue.text;
				var _editorId = myedui.ue ? myedui.ue : '_' + Math.floor(Math.random() * 100).toString() + new Date().getTime().toString();
				var uedit = new UM.Editor(_simpleConfig || {});
				var edui = {
						ue:_editorId,
						fontfamily:function(size){
							uedit.execCommand('fontfamily',size);
						},
						fontsize:function(size){
							uedit.execCommand('fontSize',size);
						},
						forecolor:function(size){
							uedit.execCommand('forecolor',size);
						},
						backcolor:function(size){
							size=="" ? uedit.execCommand('removeformat','','background-color'):uedit.execCommand('backcolor',size);
						},
						bold:function(){
							uedit.execCommand('bold');
						},
						justifyleft:function(){
							uedit.execCommand('justifyleft');
						},
						justifycenter:function(){
							uedit.execCommand('justifycenter');
						},
						justifyright:function(){
							uedit.execCommand('justifyright');
						},
						lineheight:function(size){
							$(uedit.body).find("p").css("line-height",size+"em");
						},
						link:function(size){
							uedit.execCommand('link',{'href':size.href,'title':size.title,'target':'_blank'});
						},
						unlink:function(size){
							uedit.execCommand('unlink');
						},
						removeformat:function(size){
							var text = uedit.getContentTxt();
							uedit.setContent(text);
						},
						umiput:{
					        backcolor:uedit.queryCommandValue('backcolor'),
					        forecolor:uedit.queryCommandValue('forecolor'),
					        link:{
					            href:$(uedit.queryCommandValue('link')).attr("href") || "http://",
					            title:""
					        }
					    }
					};
					scope.$apply(function(){
						ngModel.$modelValue["edui"] = edui;
					});
				$timeout(function() {
					//var uedit =  UM.getEditor(_editorId,_simpleConfig || {});
					uedit.render(_editorId);
		            uedit.ready(function () {
		            	uedit.setContent(mytext);
	                    uedit.addListener('contentChange',function(){
		            		if(ngModel.$modelValue.height<uedit.body.clientHeight){
		            			ngModel.$modelValue.height = uedit.body.clientHeight;
		            		}
	                    	$($(uedit.body).find("p")).each(function(){
	                    		if($(this).text().replace(/\s/g,"")==""){
	                    			$(this).find("span").css("font-family","microsoft yahei");
	                    			$(this).find("font").attr("face","microsoft yahei")
	                    		}
	                    	})
			                ngModel.$modelValue.text = $(uedit.body).html();
			                $timeout(function() {
								scope.$apply(function(){
								    ngModel.$modelValue;
			            		})
							},0)
	                    });
	                    uedit.addListener('blur',function(){
	                    	$($(uedit.body).find("p")).each(function(){
	                    		if($(this).text().replace(/\s/g,"")==""){
	                    			$(this).find("span").css("font-family","microsoft yahei");
	                    			$(this).find("font").attr("face","microsoft yahei")
	                    		}
	                    	})
			                ngModel.$modelValue.text = $(uedit.body).html();
			                $timeout(function() {
								scope.$apply(function(){
								    ngModel.$modelValue;
			            		})
							},0)
	                    });
	                    uedit.addListener('selectionchange',function(){
	                    	$timeout(function() {
	                    		var umiput = {
							        backcolor:uedit.queryCommandValue('backcolor'),
							        forecolor:uedit.queryCommandValue('forecolor'),
							        link:{
							            href:$(uedit.queryCommandValue('link')).attr("href") || "http://",
							            title:$(uedit.queryCommandValue('link')).attr("title") || ""
							        }
							    }
								ngModel.$modelValue.edui.umiput = umiput;
								scope.$apply(function(){
									ngModel.$modelValue;
			            		})
							},0)
	                    });
		            });
				},200);
			},100);
		}
	};
}]);

function tonumber(num){
	if(num == undefined ){
		num = 0;
		return num;
	}else{
		return Number(parseFloat(num.toString().replace(/[^0-9,.]/g,"").replace(/\,/g,"")).toFixed(4));
	}
}