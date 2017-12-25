//绑定电脑与手机鼠标手势
var NewMouse = {};
var isMobile = true ;
if (navigator.platform && (navigator.platform == 'Win32' || navigator.platform == 'MacIntel')) {
	newMouse = {mouseup: 'mouseup',mousedown: 'mousedown',mousemove: 'mousemove',mouseout: 'mouseout'};
	isMobile = false ;
}else{
	newMouse = {mouseup: 'touchend',mousedown: 'touchstart',mousemove: 'touchmove',mouseout: 'touchcancel'};
	isMobile = true ;
}
//global end

$(document).ready(function(e) {
	$(".goback").click(function(){
		history.go(-1);
	});
	$(".gosearch").click(function(){
		$(".search").toggleClass("show");
		if($(".search").hasClass("show")){
			$(".search-input").focus();
		}else{
			$(".search-input").blur();
		}
		
	});
	
	$(".chooseCity").click(function(e){
		var _p = $(this).find("p");
		var _select = $(this).find("select");
		var html = '<div class="city"><div class="city-list"><div id="city-list"><div id="city_All" class="cityHeader"><div class="closeCity"><i></i></div>选择城市</div> ';
		var html2 = '<div class="F-cities"><strong class="F-ins"></strong><div class="onTouch"><div id="charNav"><a data-id="All">#</a>';
		_select.find("optgroup").each(function(index, element) {
            var id = $(this).attr("data-id");
				html = html+'<h3 id="city_'+id+'" class="cityTitle">'+id+'</h3><ul class="CityList">';
				html2 = html2+'<a data-id="'+id+'">'+id+'</a>';
			$(this).find("option").each(function(index, element) {
                html = html+'<li><p data-val='+$(this).val()+'>'+$(this).text()+'</p></li>';
            });
			html = html+"</ul>";
        });
		html = html+'</div></div>'+html2+'</div></div></div></div>';
		$("body").append(html);
		setTimeout(function(){$(".city").addClass("show")},50);
		var _this = $('#charNav');
        var _pre_height = parseInt($('#charNav a').height());
		if (_this) {
			_this.on(newMouse.mouseup+" "+newMouse.mouseout,function(e){
				$('.F-ins').hide();
				e.preventDefault();
			});
			_this.on(newMouse.mousedown+" "+newMouse.mousemove,function(e){
				scrollToNav();
				e.preventDefault();
			});
		}
		function scrollToNav() {
			var x = isMobile ? event.targetTouches[0].clientX - _this.position().left : event.pageX - _this.offset().left ;
			var y = isMobile ? event.targetTouches[0].clientY - _this.position().top : event.pageY - _this.offset().top ;
			var index = Math.floor( y / _pre_height );
			if(index>=0){
				var nowId = $('#charNav > a:eq(' + index + ')').attr("data-id");
				var _thisDiv = $("#city_"+nowId);
				var top = _thisDiv.offset().top;
				$(document).scrollTop(top);
				$('.F-ins').html(nowId).show();
			}
		}
		$(".city li").on("click",function(e){
			_p.text($(this).text());
			_select.find("[value='"+$(this).find("p").attr("data-val")+"']").attr("selected",true);
			var _closeCity = $(this).closest(".city");
			_closeCity.removeClass("show");
			updata();
			setTimeout(function(){_closeCity.remove()},500);
		});
		$(".city .closeCity").on("click",function(e){
			var _closeCity = $(this).closest(".city");
			_closeCity.removeClass("show");
			setTimeout(function(){_closeCity.remove()},500);
		});
	})
	$(".select_type,.select_order").change(function(){
		updata();
	})
});

//弹窗基础
function MsgPop(options){
	var options = options || {
			title:"消息",
			background : "#fff",
			content : ""
		};
    options = typeof(options) == 'string'?{'html':options}:options;
	if(!options.title){options.title="消息"};
	if(!options.icon){options.icon=""}else{msg.icon="<i class='"+options.icon+"'></i>"};
	var html = ['<div class="msgPop bg">',
					'<div class="msgBg">',
						'<div class="msgTitle"><span class="js-msg-title">'+options.title+'</span><a href="javascript:void(0)" class="close-msgPop"><i class="icon-cancel"><em>+</em></i></a></div>',
						'<div class="msgCon js-msg-content" style="background:'+options.background+';"></div>',
					'</div>',
				'</div>'].join('');
    html = $(html);
    $("body").append(html);
    var _title = function(content){
        return $('.js-msg-title',html).html(content);
    }
    var _content = function(content){
        return $('.js-msg-content',html).html(content);
    }
    _content(options.content);
	$('body').on("click",".close-msgPop",function(){
		$(this).closest(".msgPop").remove();
	});
}