/*!
 * artDialog iframeTools
 * Date: 2011-11-25 13:54
 * http://code.google.com/p/artdialog/
 * (c) 2009-2011 TangBin, http://www.planeArt.cn
 *
 * This is licensed under the GNU LGPL, version 2.1 or later.
 * For details, see: http://creativecommons.org/licenses/LGPL/2.1/
 */
 
;(function ($, window, artDialog, undefined) {

var _topDialog, _proxyDialog, _zIndex,
	_data = '@ARTDIALOG.DATA',
	_open = '@ARTDIALOG.OPEN',
	_opener = '@ARTDIALOG.OPENER',
	_winName = window.name = window.name
	|| '@ARTDIALOG.WINNAME' + + new Date,
	_isIE6 = window.VBArray && !window.XMLHttpRequest;

$(function () {
	!window.jQuery && document.compatMode === 'BackCompat'
	// 不支持怪异模式，请用主流的XHTML1.0或者HTML5的DOCTYPE申明
	&& alert('artDialog Error: document.compatMode === "BackCompat"');
});
	
	
/** 获取 artDialog 可跨级调用的最高层的 window 对象 */
var _top = artDialog.top = function () {
	var top = window,
	test = function (name) {
		try {
			var doc = window[name].document;	// 跨域|无权限
			doc.getElementsByTagName; 			// chrome 本地安全限制
		} catch (e) {
			return false;
		};
		
		return window[name].artDialog
		// 框架集无法显示第三方元素
		&& doc.getElementsByTagName('frameset').length === 0;
	};
	
	if (test('top')) {
		top = window.top;
	} else if (test('parent')) {
		top = window.parent;
	};
	
	return top;
}();
artDialog.parent = _top; // 兼容v4.1之前版本，未来版本将删除此


_topDialog = _top.artDialog;


// 获取顶层页面对话框叠加值
_zIndex = function () {
	return _topDialog.defaults.zIndex;
};



/**
 * 跨框架数据共享接口
 * @see		http://www.planeart.cn/?p=1554
 * @param	{String}	存储的数据名
 * @param	{Any}		将要存储的任意数据(无此项则返回被查询的数据)
 */
artDialog.data = function (name, value) {
	var top = artDialog.top,
		cache = top[_data] || {};
	top[_data] = cache;
	
	if (value !== undefined) {
		cache[name] = value;
	} else {
		return cache[name];
	};
	return cache;
};


/**
 * 数据共享删除接口
 * @param	{String}	删除的数据名
 */
artDialog.removeData = function (name) {
	var cache = artDialog.top[_data];
	if (cache && cache[name]) delete cache[name];
};


/** 跨框架普通对话框 */
artDialog.through = _proxyDialog = function () {
	var api = _topDialog.apply(this, arguments);
		
	// 缓存从当前 window（可能为iframe）调出所有跨框架对话框，
	// 以便让当前 window 卸载前去关闭这些对话框。
	// 因为iframe注销后也会从内存中删除其创建的对象，这样可以防止回调函数报错
	if (_top !== window) artDialog.list[api.config.id] = api;
	return api;
};

// 框架页面卸载前关闭所有穿越的对话框
_top !== window && $(window).bind('unload', function () {
	var list = artDialog.list, config;
	for (var i in list) {
		if (list[i]) {
			config = list[i].config;
			if (config) config.duration = 0; // 取消动画
			list[i].close();
			//delete list[i];
		};
	};
});


/**
 * 弹窗 (iframe)
 * @param	{String}	地址
 * @param	{Object}	配置参数. 这里传入的回调函数接收的第1个参数为iframe内部window对象
 * @param	{Boolean}	是否允许缓存. 默认true
 */
artDialog.open = function (url, options, cache) {
	options = options || {};
	
	var api, DOM,
		$content, $main, iframe, $iframe, $idoc, iwin, ibody,Diabody,Diabtn,DiaHeight,
		top = artDialog.top,
		initCss = 'position:absolute;left:-9999em;top:-9999em;border:none 0;background:transparent',
		loadCss = 'width:100%;height:100%;border:none 0';
		
	if (cache === false) {
		var ts = + new Date,
			ret = url.replace(/([?&])_=[^&]*/, "$1_=" + ts );
		url = ret + ((ret === url) ? (/\?/.test(url) ? "&" : "?") + "_=" + ts : "");
	};
		
	var load = function () {
		var iWidth, iHeight,
			loading = DOM.content.find('.aui_loading'),
			aConfig = api.config
			
		$content.addClass('aui_state_full');
		
		loading && loading.hide();
		
		try {
			iwin = iframe.contentWindow;
			iWidth = aConfig.width === 'auto'
			? $idoc.width() + (_isIE6 ? 0 : parseInt($(ibody).css('marginLeft')))
			: aConfig.width;
			Diabody = $(iwin.document).find("body");
			Diabtn = $(iwin.document).find(".btn-all,.btn-all-del");
			
			if(iWidth.toString().indexOf("%")>0){
				var tiNumiWidth = parseInt(iWidth)/100;
				iWidth = $(window.top).width()*tiNumiWidth
			}
			if(Diabody.find("#chooseImg").length==0){
				Diabody.css({paddingTop:15,paddingLeft:30,paddingRight:30,paddingBottom:30});
				Diabody.width(parseInt(iWidth)-60);
				if(Diabtn.length>=1){
					Diabtn.addClass("Diabtnauto");
				}
				if($(iwin.document).find(".DiabtnCon").length>=1){
					Diabody.css({paddingTop:15,paddingLeft:30,paddingRight:30,paddingBottom:70});
				}
			}
			Diabody.addClass("Diabody");
			$idoc = $(iwin.document);
			ibody = iwin.document.body;
		} catch (e) {// 跨域
			iframe.style.cssText = loadCss;
			
			aConfig.follow
			? api.follow(aConfig.follow)
			: api.position(aConfig.left, aConfig.top);
			
			options.init && options.init.call(api, iwin, top);
			options.init = null;
			return;
		};
		// 获取iframe内部尺寸
		iWidth = aConfig.width === 'auto'
		? $idoc.width() + (_isIE6 ? 0 : parseInt($(ibody).css('marginLeft')))
		: aConfig.width;
		iHeight = aConfig.height === 'auto'
		? "85%"
		: aConfig.height;
		var winHeight = $(window.top).height()*0.85;
		DiaHeight = Diabody.height();
		Diabody.css("min-height","0px");
		if(iWidth.toString().indexOf("%")>0){
			var tiNumiWidth = parseInt(iWidth)/100;
			iWidth = $(window.top).width()*tiNumiWidth
		}
		if($idoc.height()>Diabody.height()){
			Diabody.height("auto");
		}
		if($idoc.height()<winHeight){
			iHeight = parseInt($idoc.height());
		}
		if(iHeight.toString().indexOf("%")>0){
			var tiNumiHeight = parseInt(iHeight)/100;
			iHeight = $(window.top).height()*tiNumiHeight
		}
		if($idoc.height()>parseInt(iHeight)){
			if(Diabody.find("#chooseImg").length==0){
				iWidth = parseInt(iWidth)+18;
			}
		}
		if(Diabody.find("#chooseImg").length==1){
			iHeight = aConfig.height === 'auto'? 500 : aConfig.height;
		}
		// 适应iframe尺寸
		setTimeout(function () {
			iframe.style.cssText = loadCss;
		}, 10);// setTimeout: 防止IE6~7对话框样式渲染异常
		api.size(iWidth, iHeight);
		
		// 调整对话框位置
		aConfig.follow
		? api.follow(aConfig.follow)
		: api.position(aConfig.left, aConfig.top);
		
		options.init && options.init.call(api, iwin, top);
		options.init = null;
	};
		
	var config = {
		zIndex: _zIndex(),
		init: function () {
			api = this;
			DOM = api.DOM;
			$main = DOM.main;
			$content = DOM.content;
			
			iframe = api.iframe = top.document.createElement('iframe');
			iframe.src = url;
			iframe.name = 'Open' + api.config.id;
			iframe.style.cssText = initCss;
			iframe.setAttribute('frameborder', 0, 0);
			iframe.setAttribute('allowTransparency', true);
			
			$iframe = $(iframe);
			api.content().appendChild(iframe);
			iwin = iframe.contentWindow;
			
			try {
				iwin.name = iframe.name;
				artDialog.data(iframe.name + _open, api);
				artDialog.data(iframe.name + _opener, window);
			} catch (e) {};
			
			$iframe.bind('load', load);
		},
		close: function () {
			$iframe.css('display', 'none').unbind('load', load);
			
			if (options.close && options.close.call(this, iframe.contentWindow, top) === false) {
				return false;
			};
			$content.removeClass('aui_state_full');
			
			// 重要！需要重置iframe地址，否则下次出现的对话框在IE6、7无法聚焦input
			// IE删除iframe后，iframe仍然会留在内存中出现上述问题，置换src是最容易解决的方法
			$iframe[0].src = 'about:blank';
			$iframe.remove();
			
			try {
				artDialog.removeData(iframe.name + _open);
				artDialog.removeData(iframe.name + _opener);
			} catch (e) {};
		}
	};
	
	// 回调函数第一个参数指向iframe内部window对象
	if (typeof options.ok === 'function') config.ok = function () {
		return options.ok.call(api, iframe.contentWindow, top);
	};
	if (typeof options.cancel === 'function') config.cancel = function () {
		return options.cancel.call(api, iframe.contentWindow, top);
	};
	
	delete options.content;

	for (var i in options) {
		if (config[i] === undefined) config[i] = options[i];
	};
	
	return _proxyDialog(config);
};


/** 引用open方法扩展方法(在open打开的iframe内部私有方法) */
artDialog.open.api = artDialog.data(_winName + _open);


/** 引用open方法触发来源页面window(在open打开的iframe内部私有方法) */
artDialog.opener = artDialog.data(_winName + _opener) || window;
artDialog.open.origin = artDialog.opener; // 兼容v4.1之前版本，未来版本将删除此

/** artDialog.open 打开的iframe页面里关闭对话框快捷方法 */
artDialog.close = function () {
	var api = artDialog.data(_winName + _open);
	api && api.close();
	return false;
};

// 点击iframe内容切换叠加高度
_top != window && $(document).bind('mousedown', function () {
	var api = artDialog.open.api;
	api && api.zIndex();	
	changebodyHeight = $(this).find("body").height();
});
var pwinHeight = $(window.parent).height();
var changebodyHeight;
_top != window && $(document).bind('click', function () {
	var newchangebodyHeight = $(this).find("body").height();
	if($(this).find("body").hasClass("Diabody") && newchangebodyHeight != changebodyHeight && $(this).find("#chooseImg").length==0){
		var api = artDialog.open.api;
		api && api.zIndex();
		var Diabody = $(this).find("body");
		Diabody.height("auto");
		
		var iWidth, iHeight,
			aConfig = api.config,
			Diabody = $(this).find("body");
		
		// 获取iframe内部尺寸
		iWidth = Diabody.width()+60;
		iHeight = aConfig.height === 'auto'
		? "85%"
		: aConfig.height;
		var winHeight = pwinHeight*0.85;
		DiaHeight = Diabody.height();
		
		if(iHeight.toString().indexOf("%")>0){
			var tiNumiHeight = parseInt(iHeight)/100;
			iHeight = pwinHeight*tiNumiHeight
		}
		if(Diabody.height()<winHeight){
			iHeight = parseInt(Diabody.height()+parseInt(Diabody.css("padding-top"))+parseInt(Diabody.css("padding-bottom")));
		}
		if(Diabody.height()>parseInt(iHeight)){
			iWidth = parseInt(iWidth)+18;
		}
		api.size(iWidth, iHeight);
		
		// 调整对话框位置
		aConfig.follow
		? api.follow(aConfig.follow)
		: api.position(aConfig.left, aConfig.top);
	}
});

/**
 * Ajax填充内容
 * @param	{String}			地址
 * @param	{Object}			配置参数
 * @param	{Boolean}			是否允许缓存. 默认true
 */
artDialog.load = function(url, options, cache){
	cache = cache || false;
	var opt = options || {};
		
	var config = {
		zIndex: _zIndex(),
		init: function(here){
			var api = this,
				aConfig = api.config;
			
			$.ajax({
				url: url,
				success: function (content) {
					api.content(content);
					opt.init && opt.init.call(api, here);		
				},
				cache: cache
			});
			
		}
	};
	
	delete options.content;
	
	for (var i in opt) {
		if (config[i] === undefined) config[i] = opt[i];
	};
	
	return _proxyDialog(config);
};


/**
 * 警告
 * @param	{String}	消息内容
 */
artDialog.alert = function (content, callback) {
	return _proxyDialog({
		id: 'Alert',
		zIndex: _zIndex(),
		fixed: true,
		lock: true,
		minHeight:50,
		content: content,
		ok: true,
		close: callback
	});
};


/**
 * 确认
 * @param	{String}	消息内容
 * @param	{Function}	确定按钮回调函数
 * @param	{Function}	取消按钮回调函数
 */
artDialog.confirm = function (content, yes, no) {
	return _proxyDialog({
		id: 'Confirm',
		zIndex: _zIndex(),
		fixed: true,
		lock: true,
		minHeight:100,
		width:400,
		content:'<div class="Diacheck"><p>' + content + '</p></div>',
		ok: function (here) {
			return yes.call(this, here);
		},
		cancel: function (here) {
			return no && no.call(this, here);
		}
	});
};


/**
 * 提问
 * @param	{String}	提问内容
 * @param	{Function}	回调函数. 接收参数：输入值
 * @param	{String}	默认值
 */
artDialog.prompt = function (content, yes, value) {
	value = value || '';
	var input;
	
	return _proxyDialog({
		id: 'Prompt',
		zIndex: _zIndex(),
		icon: 'question',
		fixed: true,
		lock: true,
		opacity: .1,
		minHeight:100,
		content: [
			'<div style="margin-bottom:5px;font-size:12px">',
				content,
			'</div>',
			'<div>',
				'<input value="',
					value,
				'" style="width:18em;padding:6px 4px" />',
			'</div>'
			].join(''),
		init: function () {
			input = this.DOM.content.find('input')[0];
			input.select();
			input.focus();
		},
		ok: function (here) {
			return yes && yes.call(this, input.value, here);
		},
		cancel: true
	});
};


/**
 * 短暂提示
 * @param	{String}	提示内容
 * @param	{Number}	显示时间 (默认1.5秒)
 */
artDialog.tips = function (content, time,fn) {
	return _proxyDialog({
		id: 'Tips',
		zIndex: _zIndex(),
		title: false,
		cancel: false,
		fixed: true,
		minHeight:100,
		lock: false
	})
	.content('<div style="padding: 0 1em;">' + content + '</div>')
	.time(time || 1.5);
};
Dialoading = function (content,fn,time,lock) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			zIndex: _zIndex(),
			content:content,
			fixed: true,
			padding:0,
			minHeight:20,
			lock: lock==false ? false :true,
			top: lock==false ? '115px' :'38.2%',
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-succeed">' + content + '</div>').time(time || 20);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};
Diasucceed = function (content,fn,time,lock) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			zIndex: _zIndex(),
			content:content,
			fixed: true,
			padding:0,
			minHeight:20,
			lock: lock==false ? false :true,
			top: lock==false ? '115px' :'38.2%',
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-succeed">' + content + '</div>').time(time || 2);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};
Diaerror = function (content,fn,time,lock) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			content:content,
			fixed: true,
			padding:0,
			minHeight:20,
			lock: lock==false ? false :true,
			top: lock==false ? '115px' :'38.2%',
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-error">' + content + '</div>').time(time || 2);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};
Diawarning = function (content,fn,time) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			content:content,
			fixed: true,
			padding:0,
			minHeight:20,
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-warning">' + content + '</div>').time(time || 2);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};

DiasucceedNew = function (content,fn,time) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			zIndex: _zIndex(),
			content:content,
			fixed: true,
			lock:false,
			padding:0,
			minHeight:20,
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-succeed">' + content + '</div>').time(time || 0.5);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};
DiaerrorNew = function (content,fn,time) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			content:content,
			fixed: true,
			lock:false,
			padding:0,
			minHeight:20,
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-error">' + content + '</div>').time(time || 0.5);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};
DiawarningNew = function (content,fn,time) {
	if(content){
		return _proxyDialog({
			id:"succeed",
			title:false,
			content:content,
			fixed: true,
			lock:false,
			padding:0,
			minHeight:20,
			close:function(here){
				return fn && fn.call(this, here);
			}
		}).content('<div class="msg-all-warning">' + content + '</div>').time(time || 0.5);
	}else{
		art.dialog.list['succeed'].close();
		return fn && fn.call(this);
	};
};

Diamsg = function (opt) {
	var content = opt.content;
	var yes = opt.ok;
	var no = opt.cancel;
	var newopt = {
		width:400,
		minHeight:100,
		zIndex: _zIndex(),
		title: "提示",
		content:'<div class="Diawarning"><p>' + content + '</p></div>'
	}
	if(typeof(okVal) == "undefined"){
		newopt.okVal = opt.okVal;
	}
	if(typeof(cancelVal) == "undefined"){
		newopt.cancelVal = opt.cancelVal;
	}
	if(typeof(yes) == "function"){
		newopt.ok = function (here) {
			return yes.call(this, here);
		}
	}else{
		newopt.ok = true;
	}
	if(no){
		newopt.cancel = true;
	}
	if(typeof(no) == "function"){
		newopt.cancel = function (here) {
			return no && no.call(this, here);
		}
	}
	return _proxyDialog(newopt);
};
artDialog.msg = function (opt) {
	var content = opt.content;
	var yes = opt.ok;
	var no = opt.cancel;
	var newopt = {
		width:400,
		minHeight:100,
		zIndex: _zIndex(),
		title: "提示",
		content:'<div class="Diawarning"><p>' + content + '</p></div>'
	}
	if(typeof(okVal) == "undefined"){
		newopt.okVal = opt.okVal;
	}
	if(typeof(cancelVal) == "undefined"){
		newopt.cancelVal = opt.cancelVal;
	}
	if(typeof(yes) == "function"){
		newopt.ok = function (here) {
			return yes.call(this, here);
		}
	}else{
		newopt.ok = true;
	}
	if(no){
		newopt.cancel = true;
	}
	if(typeof(no) == "function"){
		newopt.cancel = function (here) {
			return no && no.call(this, here);
		}
	}
	return _proxyDialog(newopt);
};
Dianotice = function (content) {
    var opt = {},
        api, aConfig, hide, wrap, top,
        duration = 800;
        
    var config = {
        id: 'Notice',
        left: '100%',
        top: '100%',
        width:300,
        height:50,
        minHeight:50,
        fixed: true,
        drag: false,
        resize: false,
        follow: null,
        lock: false,
        time:4,
        content:'<div class="Diasucceed"><p>' + content + '</p></div>',
        init: function(here){
            api = this;
            aConfig = api.config;
            wrap = api.DOM.wrap;
            top = parseInt(wrap[0].style.top);
            hide = top + wrap[0].offsetHeight;
            
            wrap.css('top', hide + 'px')
                .animate({top: top + 'px'}, duration, function () {
                    opt.init && opt.init.call(api, here);
                });
        },
        close: function(here){
            wrap.animate({top: hide + 'px'}, duration, function () {
                opt.close && opt.close.call(this, here);
                aConfig.close = $.noop;
                api.close();
            });
            
            return false;
        }
    };	
    
    for (var i in opt) {
        if (config[i] === undefined) config[i] = opt[i];
    };
    
    return artDialog(config);
};

// 增强artDialog拖拽体验
// - 防止鼠标落入iframe导致不流畅
// - 对超大对话框拖动优化
$(function () {
	var event = artDialog.dragEvent;
	if (!event) return;

	var $window = $(window),
		$document = $(document),
		positionType = _isIE6 ? 'absolute' : 'fixed',
		dragEvent = event.prototype,
		mask = document.createElement('div'),
		style = mask.style;
		
	style.cssText = 'display:none;position:' + positionType + ';left:0;top:0;width:100%;height:100%;'
	+ 'cursor:move;filter:alpha(opacity=0);opacity:0;background:#FFF';
		
	document.body.appendChild(mask);
	dragEvent._start = dragEvent.start;
	dragEvent._end = dragEvent.end;
	
	dragEvent.start = function () {
		var DOM = artDialog.focus.DOM,
			main = DOM.main[0],
			iframe = DOM.content[0].getElementsByTagName('iframe')[0];
		
		dragEvent._start.apply(this, arguments);
		style.display = 'block';
		style.zIndex = artDialog.defaults.zIndex + 3;
		
		if (positionType === 'absolute') {
			style.width = $window.width() + 'px';
			style.height = $window.height() + 'px';
			style.left = $document.scrollLeft() + 'px';
			style.top = $document.scrollTop() + 'px';
		};
		
		if (iframe && main.offsetWidth * main.offsetHeight > 307200) {
			main.style.visibility = 'hidden';
		};
	};
	
	dragEvent.end = function () {
		var dialog = artDialog.focus;
		dragEvent._end.apply(this, arguments);
		style.display = 'none';
		if (dialog) dialog.DOM.main[0].style.visibility = 'visible';
	};
});

})(this.art || this.jQuery, this, this.artDialog);

