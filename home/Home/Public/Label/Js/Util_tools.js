/*公共工具类库*/
(function(){
	String.prototype.len=function(){return this.replace(/[^\x00-\xff]/g,"aa").length;};
	String.prototype.trim=function(){return this.replace(/(^ *)|( *$)/g, "");};
	String.prototype.htmlencode=function(){return this.replace(/[<>"']+/g,"");};
	String.prototype.encode=function(){return escape(encodeURIComponent(this));};
	String.prototype.left=function(length){
	  if(this.len()>length){
		  var _temp=this;
		  _temp=_temp.replace(/([^\x00-\xff])/g,"$1>");
		  _temp=_temp.substring(0,length-2)+"..";
		  return _temp.replace(/>/g,"");
	  }else{
		  return this.toString();
	  }
	};
	Element.prototype.show=function(){this.style.display="block";};
	Element.prototype.hide=function(){this.style.display="none";};
	Element.prototype.center=function(top){
	   this.style.left=(_system.scroll().x+_system._zero(_system.client().bw-this.offsetWidth)/2)+"px";
	   this.style.top=(top?top:(_system.scroll().y+_system._zero(_system.client().bh-this.offsetHeight)/2))+"px";
	};

	//创建元素
	var _system = (function(){
		var _cssStyle = [
		 '/* toolsCss start */',
			'#J_cover{display:none;position:absolute;left:0;top:0;z-index:8888;background-color:#000000;opacity:0.5;width:100%;height:100%}',
			'#J_loading{display:none;position:fixed;z-index:9999;width:100px;height:100px;top:50%;left:50%;margin: -60px auto auto -50px;color:#ffffff;font-size:16px;text-align:center;border-radius:10px;}',
			'#J_loading i{position:absolute;z-index:9;top:60%;left:50%;margin:-15px 0 0 -15px;display:inline-block;width:20px;height:20px;border:4px dotted #000;border-color:rgba(255,255,255,.1) rgba(255,255,255,.2) rgba(255,255,255,.4) rgba(255,255,255,.5);border-radius:300px;-webkit-animation:loadforever 1.5s infinite linear;}',
			'@-webkit-keyframes loadforever{0%{-webkit-transform:rotate(0deg);}100%{-webkit-transform:rotate(360deg);}}',
			'#J_loading{background-color:#2e2e2e;}',
			'#J_loading_text{margin-top:10px;font-size:14px;}',
			'#J_ok{display:none;position:fixed;z-index:9999;width:180px;padding:36px 0 26px 0;color:#ffffff;font-size:16px;text-align:center;border-radius:6px;}',
			'#J_ok{background-color:#656564;}',
			'#J_loading img{width:60px;height:60px;margin-bottom:20px;}',
			'#J_ok img{width:119px;height:90px;margin-bottom:20px;}',
			'#J_toast{display:none;position:absolute;padding:8px 25px;color:#ffffff;font-size:16px;background-color: rgba(30,30,30,0.9);box-shadow:0 0 5px #666666;z-index:9999;}',
            '/*弹出小窗*/',

		 '/* toolsCss end */'].join('');
		function _addCssByStyle(cssString){  
			var doc=document;  
			var style=doc.createElement("style");  
			style.setAttribute("type", "text/css");  
			if(style.styleSheet){// IE  
				style.styleSheet.cssText = cssString;  
			} else {// w3c  
				var cssText = doc.createTextNode(cssString);  
				style.appendChild(cssText);  
			}  
			var heads = doc.getElementsByTagName("head");  
			if(heads.length)  
				heads[0].appendChild(style);  
			else  
				doc.documentElement.appendChild(style); 
		}
		//创建CSS
		_addCssByStyle(_cssStyle);
		var _el = function(id,html){
			var elm = document.getElementById(id);
			if(elm) return elm;
			if(html === undefined) return false;
			elm = document.createElement("div");
			elm.innerHTML = html;
			elm.id=id;
			document.body.appendChild(elm);
			return elm;
		};
		var _timeOut = {};

		var _extend = function(des, src, override){
			if(src instanceof Array){
				for(var i = 0, len = src.length; i < len; i++)
					 extend(des, src[i], override);
			}
			for( var i in src){
				if(override || !(i in des)){
					des[i] = src[i];
				}
			}
			return des;
		};

		var _system={
		   _config:{
			
		   },
		   init:function(cfg){
			cfg = cfg||{};
			_extend(this._config,cfg);
		   },
		   client:function(){
			  return {w:document.documentElement.scrollWidth,h:document.documentElement.scrollHeight,bw:document.documentElement.clientWidth,bh:document.documentElement.clientHeight};
		   },
		   scroll:function(){
			  return {x:document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft,y:document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop};
		   },
		   cover:function(show){
			  var J_cover = _el("J_cover","");
			  if(show){
				 J_cover.show();
				 J_cover.style.width=(this.client().bw>this.client().w?this.client().bw:this.client().w)+"px";
				 J_cover.style.height=(this.client().bh>this.client().h?this.client().bh:this.client().h)+"px";
			  }else{
				 J_cover.hide();
			  }
		   },
		   loading:function(text){
			  var J_loading = _el("J_loading",'<i></i><div id="J_loading_text"></div>');
			  if(text !== false){
				 this.cover(true);
				 J_loading.show();
				 _el("J_loading_text").innerHTML=text;
				 window.onresize=function(){_system.cover(true);};
			  }else{
				 this.cover(false);
				 J_loading.hide();
				 window.onresize=null;
			  }
		   },
		   toast:function(text,fun){
			  var J_toast = _el("J_toast","");
			  J_toast.show();
			  J_toast.innerHTML=text;
			  J_toast.center();
			  _timeOut['_toast'] = setTimeout(function(){
				 J_toast.hide();
				 if(fun){(fun)();}
			  },3*1000);
		   },
		   ok:function(text,fun){
			  var J_ok = _el("J_ok",'<div id="J_ok_text"></div>');
			  J_ok.show();
			  _el("J_ok_text").innerHTML=text;
			  J_ok.center();
			  window.onresize=function(){J_ok.center();};
			  setTimeout(function(){
				 window.onresize=null;
				 J_ok.hide();
				 if(fun) (fun)();
			  },2*1000);
		   },
		   linkTo:function(url){
			setTimeout(function(){
				_system.loading("加载中...");
			},1);
			setTimeout(function(){
				if(typeof url == 'string'){
					location.href=url;
				}
				else if(typeof url == 'function'){
					url();
				}
                _system.loading(false);
			},300);
			//setTimeout(function(){_system.loading(false)},6000);
		   },
		   _guide:function(show){
			  this._cover(true);
			  _el("guide").show();
			  window.onresize=function(){_system._cover(true);};
			  if(show){
				 _el("cover").onclick=_system._guideHide;
				 //_el("guide_button").hide();_el("guide_button2").show();
			  }else{
				 //_el("guide_button").show();_el("guide_button2").hide();
			  }
		   },
		   _guideHide:function(){
			  _system.cover();
			  _el("guide").hide();
			  _el("cover").onclick=null;
			  window.onresize=null;
		   },
		   _guide2:function(){
			  if(_cookie._get("GUIDE2")!=""){return;}
			  this._cover(true);
			  _el("guide2").show();
			  window.onresize=function(){_system._cover(true);};
			  setTimeout(function(){
				 _system.cover();
				 _el("guide2").hide();
				 _el("cover").onclick=null;
				 window.onresize=null;
				 _cookie._set("GUIDE2","1",60*60*24*30);
			  },5*1000);
		   },
		   _zero:function(n){
				return n<0?0:n;
			},
		   ajax:function(url,parameters,callback,loadingMessage){
			   if(loadingMessage!=""){_system._loading(loadingMessage);}
				var request=new XMLHttpRequest();
				if(loadingMessage!=""){_system._loading(loadingMessage);}
				var method="POST";
				if(parameters==""){method="GET";parameters=null;}
				request.open(method,url,true);
				request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				request.onreadystatechange=function(){
				if(request.readyState==4){
					 if(loadingMessage != ""){_system.loading();}
					 if(request.status==200){
						if(functionName){
						   try{
							  var json = eval("("+ request.responseText+")");
							  functionName(json);
							}catch(e){}
						}
					 }else{
						 if(loadingMessage != ""){_system._toast("发生意外错误，请稍候再试");}
					 }
				}
				};
				request.send(parameters);
			}
		};//-------end _system;
		return _system;
	})();


	var _cookie={
		set:function(name,value,expires){
		   if(expires){
			  var _end = new Date();
			  _end.setTime(_end.getTime()+(expires*1000));
			}
			document.cookie=name+"="+escape(value)+(expires ? ";expires="+_end.toGMTString() : "")+"; path=/";
		},
		get:function(name){
		   var _cookie=document.cookie;
		   var _start=_cookie.indexOf(name+"=");
		   if(_start!=-1){
			  _start+=name.length+1;
			  var _end=_cookie.indexOf(";",_start);
			  if(_end==-1){_end = _cookie.length;}
			  return unescape(_cookie.substring(_start,_end));
			}
			return "";
		}
	};
    var _form = {
        toJson:function(f){
            var $form = $(f);
            var o = {};
            var a = $form.serializeArray();
            $.each(a, function() {
                if (o[this.name]) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        },
        toUrl:function(f,url){
            var json = _form.toJson(f);
            if (!(json)) {
                return '';
            }
            var tmps = [];
            for (var key in json) {
                //处理数组类型的
                if(typeof(json[key])=='string'){
                    json[key] = [json[key]];
                }
                for(var k2 in json[key]){
                    tmps.push(key + '=' + json[key][k2]);
                }
            }
            var param = tmps.join('&');
            if(!url) return param;
            if(url.indexOf('?')!= -1){
                return url+'&'+param;
            }
            else{
                return url+'?'+param;
            }
        }
    };

    var returnObj = {
        ui:_system,
        cookie:_cookie,
        form:_form
    };
	//支持seajs的载入
	if('function' === typeof(define)){
		define(function(require,exports,modlues){
			return returnObj;
		});
	}
    else{
        window['Util_tools'] = returnObj;
    }
})();