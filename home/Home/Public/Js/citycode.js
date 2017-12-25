/** 选择省市区商圈
* 2014.05.08 新增商圈功能 by tr
city_obj = {
province:$("#province_code"),//省
city:$("#city_code"),//市
town:$("#town_code"),//区
business:null,//商圈
selected:"01021001",//默认选中的省市区代号
url:"quest/questCity.php"//数据查询页
};
CityCode(city_obj);
*/
function CityCode(city_obj)
{
city_obj = (city_obj || {});
var province_code_obj = city_obj.province || $("#province_code");
var city_code_obj = city_obj.city || $("#city_code");
var town_code_obj = city_obj.town || $("#town_code");
var business_code_obj = city_obj.business ? city_obj.business : null;
//计算被选中的城市
var city_id_selected = city_obj.selected || '';
var province_selected = city_id_selected.substr(0,2);
var city_selected = city_id_selected.substr(2,3);
var town_selected = city_id_selected.substr(5,3);
var business_selected = city_id_selected.substr(8,3);
var url = city_obj.url || "../quest/quest_city.php";
var callback = city_obj.callback || false;
var isauto = city_obj.isauto || false;
var city_cache = new Array;
var init_flag = false;
//设置新option
var set_opt = function(obj,opt_data,sel_data,keyset)
{
	keyset = keyset || ['0','1'];
	//重新给select赋值
	if (typeof obj != 'undefined') {
		obj.length = 0;
		$.each(opt_data, function(key,val){
			obj.options[obj.length] = new Option(val[keyset[1]], val[keyset[0]]);
			if(sel_data == val[keyset[0]])
				obj.options[obj.length - 1].selected = true;
		});
	}
};
//选择新option
var sel_opt = function(obj,sel_data)
{
	for(var i=0;i<obj.options.length;i++)
	{
		if(sel_data == obj.options[i].value)
			obj.options[i].selected = true;
	}
};
var set_loading = function(obj){
	set_opt(obj.get(0),[{key:'',val:'loading...'}],'',['key','val']);
	obj.attr("disabled",true);
}
province_code_obj.change(function(){
	if(!city_code_obj.length) return;
	if(!init_flag) sel_opt(this,province_selected);
	init_flag = true;
	var province_code = this.value;
	var qdata = city_cache[province_code];
	if(!province_code) qdata = [{'city_code':'','city':'选择市'}];
	if(qdata) 
	{
		//默认选中
		var temp_selected = province_selected == province_code ? city_selected : '';
		set_opt(city_code_obj.get(0),qdata,temp_selected,['city_code','city']);
		city_code_obj.attr("disabled",false).change();
		return;
	}
	province_code_obj.attr('disabled',true);
	set_opt(city_code_obj.get(0),[{key:'',val:'loading...'}],'',['key','val']);
	city_code_obj.attr("disabled",true);
	$.post(url,{city_type:'city',province_code:province_code},function(d)
	{
		var qdata = d.data;
		qdata.unshift({'city_code':'','city':'选择市'});
		city_cache[province_code] = qdata;
		province_code_obj.attr('disabled',false);
		//默认选中
		var temp_selected = province_selected == province_code ? city_selected : '';
			set_opt(city_code_obj.get(0),qdata,temp_selected,['city_code','city']);
			city_code_obj.attr("disabled",false).change();
			if(callback){
				window[callback].call(this,false);
			}
	},'json');
});
var town_cache = new Array;
city_code_obj.change(function(){
	if(!town_code_obj.length) return;
	var province_code = province_code_obj.val();
	var city_code = this.value;
	var qdata = town_cache[''+province_code+city_code];
	if(!province_code || !city_code) qdata = [{'town_code':'','town':'选择区'}];
	if(business_code_obj){
		town_code_obj.change();
	}
	if(qdata) 
	{
		//默认选中
		var temp_selected = (province_selected == province_code && city_selected == city_code) ? town_selected : '';
		set_opt(town_code_obj.get(0),qdata,temp_selected,['town_code','town']);
		town_code_obj.attr("disabled",false);
		return;
	}
	province_code_obj.attr('disabled',true);
	city_code_obj.attr('disabled',true);
	set_loading(town_code_obj);
	$.post(url,{city_type:'town',province_code:province_code,city_code:city_code},function(d)
	{
		var qdata = d.data;
		qdata.unshift({'town_code':'','town':'选择区'});
		town_cache[''+province_code+city_code] = qdata;
		province_code_obj.attr('disabled',false);
		city_code_obj.attr('disabled',false);
		//默认选中
		var temp_selected = (province_selected == province_code && city_selected == city_code) ? town_selected : '';
		    if(isauto){
		    	if(!(province_selected == province_code && city_selected == city_code)){
		    		return false;
		    	}
		    }
			set_opt(town_code_obj.get(0),qdata,temp_selected,['town_code','town']);
			town_code_obj.attr("disabled",false);;
			if(business_code_obj){
				town_code_obj.change();
			}
			if(callback){
				window[callback].call(this,false);
			}
	},'json');
});
//如果有商城，则选区事件
if(business_code_obj){
	town_code_obj.change(function(){
		if(!business_code_obj.length) return;
		var province_code = province_code_obj.val();
		var city_code = city_code_obj.val();
		var town_code = this.value;
		var qdata = town_cache[''+province_code+city_code+town_code];
		if(!province_code || !city_code || !town_code) qdata = [{'business_code':'','business':'选择商圈'}];
		if(qdata) 
		{
			//默认选中
			var temp_selected = (province_selected == province_code && city_selected == city_code && town_selected == town_code) ? business_selected : '';
			set_opt(business_code_obj.get(0),qdata,temp_selected,['business_code','business']);
			business_code_obj.attr("disabled",false);;
			return;
		}
		province_code_obj.attr('disabled',true);
		city_code_obj.attr('disabled',true);
		town_code_obj.attr('disabled',true);
		set_loading(business_code_obj);
		business_code_obj.attr("disabled",true);
		$.post(url,{city_type:'business',province_code:province_code,city_code:city_code,town_code:town_code},function(d)
		{
			var qdata = d.data;
			qdata.unshift({'business_code':'','business':'选择商圈'});
			town_cache[''+province_code+city_code+town_code] = qdata;
			province_code_obj.attr('disabled',false);
			city_code_obj.attr('disabled',false);
			town_code_obj.attr('disabled',false);
			//默认选中
			var temp_selected = (province_selected == province_code && city_selected == city_code && town_selected == town_code) ? business_selected : '';
			set_opt(business_code_obj.get(0),qdata,temp_selected,['business_code','business']);
			business_code_obj.attr("disabled",false);
			if(callback){
				window[callback].call(this,false);
			}
		},'json');
	});
}
//预载入省
$.post(url,{city_type:'province'},function(d){
	d.data.unshift({province_code:'',province:'选择省'});
	set_opt(province_code_obj.get(0),d.data,'',['province_code','province']);
	//载入市
	province_code_obj.attr('disabled',false).change();
	if(callback){
		window[callback].call(this,false);
	}
},'json');
}