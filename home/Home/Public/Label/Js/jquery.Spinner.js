/* jQuery.Spinner V1.0 CopyRight (c) 2014 by:Loyaoo Taobao:http://isseven.taobao.com */

(function($) {
	
	$.fn.Spinner = function (opts) {
		var max_limit = window._g.buy_limit;
		var def_val = window._g.def_val;
		if(!def_val)
			def_val = 1;
		var defaults = {value:def_val, min:1, len:3, max:max_limit}
		var options = $.extend(defaults, opts)
		var keyCodes = {up:38, down:40}
		return this.each(function() {
		
			var a = $('<a></a>'); f(a,0,"Decrease","-");	//减
			var c = $('<a></a>'); f(c,0,"Increase","+");	//加
			var b = $('<input/>');f(b,1,"Amount");cv(0);	//值
			
			$(this).append(a).append(b).append(c);
			a.click(function(){cv(-1)});
			b.keyup(function(){cv(0)});
			c.click(function(){cv(+1)});
			b.bind('keyup paste change',function(e){
				e.keyCode==keyCodes.up&&cv(+1);
				e.keyCode==keyCodes.down&&cv(-1);
			});
			
			function cv(n){
				b.val(b.val().replace(/[^\d]/g,''));
				bv=parseInt(b.val()||options.min)+n;
				bv>=options.min&&bv<=options.max&&b.val(bv);
				if(bv<=options.min){b.val(options.min);f(a,2,"DisDe","Decrease");}else{f(a,2,"Decrease","DisDe");}
				if(bv>=options.max){b.val(options.max);f(c,2,"DisIn","Increase");}else{f(c,2,"Increase","DisIn");}
			}
			
		});

		function f(o,t,c,s){
			t==0&&o.addClass(c).attr("href","javascript:void(0)").append("<i></i>").find("i").append(s);
			t==1&&o.addClass(c).attr({"value":options.value,"autocomplete":"off","maxlength":options.len,"name":"gift_num"});
			t==2&&o.addClass(c).removeClass(s);
		}
	}
	
})(jQuery);