var isfontfamily = true ;
var iscommand = false;
$(document).ready(function(e) {
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
	// $("body").on("contextmenu",".swrapperCon",function(e){
	// 	return false;
	// });
	$("body").on("click",".editClassList dl",function(e){
		$(this).closest(".editClassList").find("dl").removeClass("hover");
		$(this).addClass("hover");
	});
	$("body").on("dblclick",".mypageList .tit span",function(e){
		e.stopPropagation();
		$(".mypageList .tit").removeClass("titEdit");
		$(this).closest(".tit").addClass("titEdit");
		$(this).closest(".tit").find("input").focus();
	});
	$("body").on("click",".mypageList .tit .bgfixed",function(e){
		$(this).closest(".tit").find("input").blur();
		$(this).closest(".tit").removeClass("titEdit");
	});
	$("body").on("click",".editTxt dd>a",function(e){
		e.stopPropagation();
		var alength = $(this).closest("dd").find(".editTxtlist").length;
		$(this).closest("dl").find(".editTxtlist").hide();
		if(alength){
			$(this).closest("dd").find(".editTxtlist").show();
		}
	});
	$("body").on("click",".editTxtlist input,.editTxtlist .Gform",function(e){
		e.stopPropagation();
	});
	$("body").on("click",function(e){
		$(".editTxtlist").hide();
	});
	$("body").on("click",".editTxtlist.fontfamily li",function(e){
		var fontfamily = $(this).text();
		var fontfamilyarray = ['方正静蕾简体','方正正黑简体','汉仪菱心简体','宋体','Arial','BRADHITC','BRUSHSCI','CASTELAR','IMPACT','KUNSTLER'];
		if(isfontfamily){
			isfontfamily=false;
			$.each(fontfamilyarray,function(i,n){
				if(fontfamily == n){
					Diamsg({
	                    content:'使用特殊字体会影响海报制作及打开速度哦。',
	                    okVal:"我知道了"
	                });
				}
			})
		}
	});

	for(var i=1;i<11;i++){
		$("#guideC").append('<div class="guidec guidec-v" style="left:'+29*i+'px;"></div>');
	}
	for(var i=1;i<17;i++){
		$("#guideC").append('<div class="guidec guidec-h" style="top:'+29*i+'px;"></div>');
	}
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
					$(".Mcon").click();
					return false;
				}
			}).bind('keydown', 'up', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] -= 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'right', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] += 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'left', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] -= 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'down', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] += 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'Shift+up', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] -= 10;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'Shift+right', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] += 10;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'Shift+left', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] -= 10;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'Shift+down', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] += 10;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'alt+up', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] -= 1;
							n["height"] += 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'alt+right', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] += 1;
							n["width"] -= 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'alt+left', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["left"] -= 1;
							n["width"] += 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'alt+down', function(){
				if(!scope.isfouce){
					$.each(ngModel.$modelValue,function(i,n){
						scope.$apply(function(){
							n["top"] += 1;
							n["height"] -= 1;
						});
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'del', function(){
				if(!scope.isfouce){
					var newarr = new Array(),index=0;
					$.each(scope.myPage.mod,function(i,n){
						var isdel = true;
						$.each(ngModel.$modelValue,function(j,m){
							if(n == m){
								isdel = false;
							}
						});
						isdel && (newarr[index++] = n);
					});
					scope.$apply(function(){
						scope.myPage.mod = newarr;
						scope.ModChecked = [];
						scope.myMod = [];
						scope.display = 0;
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'backspace', function(){
				if(!scope.isfouce){
					var newarr = new Array(),index=0;
					$.each(scope.myPage.mod,function(i,n){
						var isdel = true;
						$.each(ngModel.$modelValue,function(j,m){
							if(n == m){
								isdel = false;
							}
						});
						isdel && (newarr[index++] = n);
					});
					scope.$apply(function(){
						scope.myPage.mod = newarr;
						scope.ModChecked = [];
						scope.myMod = [];
						scope.display = 0;
					});
					scope.goHistory();
					return false;
				}
			}).bind('keydown', 'ctrl+a', function(){
				if(!scope.isfouce){
					scope.$apply(function(){
						scope.ModChecked = [];
						scope.ModChecked = scope.myPage.mod;
					});
					$.each(scope.ModChecked,function(i,n){
						if(!n.lock || n.lock == ""){
							scope.$apply(function(){
								n["ModChecked"] = true;
							});
						}
					});
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
			}).bind('keydown', 'ctrl+c', function(){
				if(!scope.isfouce){
					scope.fnpaste();
					return false;
				}
			}).bind('keydown', 'ctrl+v', function(){
				if(!scope.isfouce){
					scope.fncopy();
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
			}).bind('keydown', 'ctrl+j', function(){
				if(!scope.isfouce){
					scope.fnpaste();
					scope.fncopy();
					scope.goHistory();
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
				}else if(e.keyCode==65){//a
					if(!scope.isfouce){
						if(iscommand){
							scope.$apply(function(){
								scope.ModChecked = [];
								scope.ModChecked = scope.myPage.mod;
							});
							$.each(scope.ModChecked,function(i,n){
								if(!n.lock || n.lock == ""){
									scope.$apply(function(){
										n["ModChecked"] = true;
									});
								}
							});
							return false;
						}
					}
				}else if(e.keyCode==67){//c
					if(!scope.isfouce){
						if(iscommand){
							scope.fnpaste();
							return false;
						}
					}
				}else if(e.keyCode==86){//v
					if(!scope.isfouce){
						if(iscommand){
							scope.fncopy();
							return false;
						}
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
				}else if(e.keyCode==74){//j
					if(!scope.isfouce){
						if(iscommand){
							scope.fnpaste();
							scope.fncopy();
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
			$("body").on('keydown',"input",function(e){
				if(e.keyCode == 13){
					$(".bgfixed").click();
				}
			})
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
angular.module('ui.flash',[]).directive('uiFlash',['$timeout',function($timeout){
	return{
		require: '?ngModel',
		scope:{
			uiFlash: '@',
			uiTime: '@'
		},
		link: function(scope, element, ngModel) {
			$timeout(function() {
				if(!scope.uiTime){
					scope.uiTime = 5
				}
				if(scope.uiFlash=="fadeIn"){
					var index = 0;
					var setflash = function () {
						var max = $(element).attr("data-length");
					     if(index>=max){index= 0;}
					     element.find("ul").find("li").removeClass("show");
					     element.find("ul").find("li:eq("+index+")").addClass("show");
					     index++;
					     if(index>=max){index= 0;}
					}
					$timeout(function uiFlash() {
						setflash();
						$timeout(uiFlash,scope.uiTime*1000);
					},scope.uiTime*1000);
				}else{
					var index = 0;
					var setflash = function () {
						var max = $(element).attr("data-length");
					     element.find("ul").css("margin-left","-"+index+"00%");
					     index++;
					     if(index>=max){index= 0;}
					}
						$timeout(function uiFlash() {
							setflash();
							$timeout(uiFlash,scope.uiTime*1000);
						},scope.uiTime*1000);
				}
	        },20);
		}
	};
}]);
angular.module('my.svg',[]).directive('mySvg',function($timeout){
	return{
		restrict: "A",
		replace: true,
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs,ngModel) {
			$timeout(function() {
				element.load(ngModel.$modelValue.svg+" svg", null, function(data,status){
					ngModel.$modelValue.color = [];
					var svg = element.find("svg");
					svg.attr("width","100%").attr("height","100%");
					var svgindex = 0;
					svg.find("*").each(function(){
						if($(this).attr("fill")){
							ngModel.$modelValue.color.push([$(this).attr("fill")]);
							$(this).attr("ng-style","{'fill':"+ngModel.$modelValue.color[svgindex][0]+"}");
							svgindex++
						}
					})
				});
	        },20);
		}
	};
});
/*
angular.module('my.svg',[]).directive('mySvg',['$timeout',function($timeout){
	return{
		restrict: "EA",
		replace: true,
		template: '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100%" height="100%" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve" preserveAspectRatio="none"></svg>',
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs,ngModel) {
			$timeout(function() {
				console.log(ngModel.$modelValue)
				element.load(ngModel.$modelValue.svg, null, function(data,status){
					console.log(data)
				});
	        },20);
		}
	};
}]);

angular.module('my.svg',[]).directive('mySvg',['$timeout',function($timeout){
	return{
		restrict: "EA",
		replace: true,
		template: '<embed ng-src="{{value.svg.svg}}" width="300" height="100" type="image/svg+xml" pluginspage="http://www.adobe.com/svg/viewer/install/" />',
		require: '?ngModel',
		scope:false,
		link: function(scope, element, attrs,ngModel) {
			$timeout(function() {
				var init = function() {

						};
				if(element[0].getSVGDocument()) {
					console.log(1)
					init();
				}else{
					console.log(2)
					element.on("load", init);
				}
				//element.attr("src",ngModel.$modelValue.svg)
	        },20);
		}
	};
}]);
*/
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

          var opts = {distance:20};
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
function getGuides(elem,pos,w,h){
    if(elem!=null){
        var $t = $(elem), 
        	pos = $t.position(),
        	w = $t.width(),
        	h = $t.height(),
        	c = $t.hasClass("guidec") ? "guidec" : "modlist";
    }
    return [
        { type: "1", left: pos.left, top: pos.top ,name:c}, 
        { type: "3", left: pos.left, top: pos.top + h ,name:c}, 
        { type: "2", left: pos.left + w, top: pos.top ,name:c}, 
        { type: "4", left: pos.left + w, top: pos.top + h,name:c},
        { type: "5", left: pos.left, top: pos.top + h/2,name:c},
        { type: "6", left: pos.left + w/2, top: pos.top,name:c} 
    ]; 
}
var moddata = [{
		id:1,
		type:"用途",
		list:[{
			id:0,
			type:"全部",
			list:[]
		},{
			id:1,
			type:"招聘",
			list:[]
		},{
			id:2,
			type:"节日",
			list:[]
		},{
			id:3,
			type:"照片",
			list:[]
		},{
			id:4,
			type:"互动",
			list:[]
		},{
			id:5,
			type:"图文",
			list:[]
		},{
			id:6,
			type:"名片",
			list:[]
		}]
	},
	{
		id:2,
		type:"风格",
		list:[{
			id:0,
			type:"全部",
			list:[]
		},{
			id:1,
			type:"黑白",
			list:[]
		},{
			id:2,
			type:"绚丽",
			list:[]
		},{
			id:3,
			type:"小清新",
			list:[]
		},{
			id:4,
			type:"现代",
			list:[]
		},{
			id:5,
			type:"扁平化",
			list:[]
		},{
			id:6,
			type:"中国风",
			list:[]
		},{
			id:7,
			type:"欧美风",
			list:[]
		}]
	},
	{
		id:3,
		type:"整套",
		list:[{
			id:0,
			type:"全部",
			list:[]
		},{
			id:1,
			type:"企业招聘",
			list:[]
		},{
			id:2,
			type:"品牌促销",
			list:[]
		},{
			id:3,
			type:"新品上市",
			list:[]
		},{
			id:4,
			type:"会议邀请",
			list:[]
		},{
			id:5,
			type:"求职简历",
			list:[]
		},{
			id:6,
			type:"企业宣传",
			list:[]
		},{
			id:7,
			type:"清明踏青",
			list:[]
		},{
			id:8,
			type:"欧洲杯",
			list:[]
		}]
	}];

helpMsg = {
    stop:[["请从上边控件中添加一个元素（拖动或点击），也可以直接选择左侧模板","左侧小工具可撤销或清除您已编辑的内容，右侧小工具可编辑背景图片及音乐"],10],
    hasmod:[["单击控件进行排版，双击控件编辑文字或图片，右击控件可看到更多操作项","选择多个控件的方法：Ctrl+鼠标左键；鼠标左键拖一块区域；Ctrl+a全选"],10],
    checkedmod:[["拖动控件可调整位置，PS:超出手机框的内容将隐藏","拖动控件周围的8个手柄可调整大小，(Shift+拖动)可固定控件比例","右侧操作面板中可直接输入具体数值,调整边框阴影等样式","右侧操作面板可编辑动画，发挥想象尝试下不同的动画吧","键盘方向键(↑→↓←)进行微调，(Shift+↑)增加移动距离，(Alt+↑)微调大小"],10],
    checkedmods:[["右侧操作面板中可对已选中的多个控件进行对齐及复制"],8],
    hasmodall:[["图层太多被挡掉?左侧面板可以管理你的图层,试试拖动排序和锁定功能吧","单击控件进行排版，双击控件编辑文字或图片，右击控件可看到更多操作项","选择多个控件的方法：Ctrl+鼠标左键；鼠标左键拖一块区域；Ctrl+a全选"],8],
    text:[["您现在可以对文本内容进行编辑了，先熟悉下新的操作区吧","使用特殊字体会影响海报制作及打开速度，请谨慎使用","调整文字大小后出现被切割现象，调整行高可避免错位","您可以撤销(Ctrl+z)或恢复(Ctrl+y)内容","添加超链接时请确认是否有选中的文字"],8],
    img:[["图片尺寸建议您使用2倍像素，保持图片大小不大于200K以免影响浏览体验"],10],
    imglist:[["图集建议您先上传需要的图片，并保持图片尺寸一致"],8],
    button:[["链接地址'http://www.wangcaio2o.com'，调用电话功能'tel:400-882-7770'","调用短信功能'sms:400-800-000'(部分手机支持)"],10],
    tel:[["直接输入电话，如:400-882-7770"],10],
    form:[["您可以添加多个输入框完善您收集的信息","Ctrl+鼠标左键实现多选，可方便您的排版"],8]
}