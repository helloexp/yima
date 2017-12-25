//设置 地图 事件
var GoogleMap = function(cfg){
	var geocoder;
	var map;
	var markersArray = [];
	var arrInfoWindows = [];
	var i=0;
	var opt = {
		lat:39.90453,
		lng:116.40735,
		callback:function(lat,lng){
			alert("lat:"+lat+",lng:"+lng);
		},
		zoom:10};
	function mapInit(lat,lng,zoom){
		geocoder = new google.maps.Geocoder();
		var centerCoord = new google.maps.LatLng(lat,lng); 
		var mapOptions = {
		  zoom: zoom?zoom:13,
		  center: centerCoord,
		  mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
	};
	//放置marker
	function placeMarker(location) {
		marker = new google.maps.Marker({
		  position: location,
		  map: map
		});
		markersArray.push(marker);
		//这里根据用户操作，将经纬度取出来，并复制到input中。
		//$("#lat").val(marker.getPosition().lat());
		//$("#lng").val(marker.getPosition().lng());
		if(opt.callback && typeof(opt.callback == 'function')){
			opt.callback(marker.getPosition().lat(),marker.getPosition().lng());
		}
	};
	//清除marker，这个函数需要使用，不然你的鼠标点击之处，都有标记，而你只需要一个。
	function clearMarkersBefore() {
		if (markersArray) {
		  for (i in markersArray) {
			markersArray[i].setMap(null);
		  }
		  markersArray.length = 0;
		}
	};
	//这以下是载入页面要做的事情：初始化，同时在地图上增加一个事件；
	$(function(){
		opt = $.extend(opt,cfg);
		mapInit(opt.lat,opt.lng,opt.zoom);

		google.maps.event.addListener(map, 'click', function(event) {
			clearMarkersBefore();
			placeMarker(event.latLng);
		});

		/*
		if(opt){
		if(type=='edit' || type=='info' || type == 'AddStore' || type == 'EditStore'){
			if(lat == '' || lng == ''){
				mark_lation=new google.maps.LatLng(lat,lng);
				lat=39.90453;
				lng=116.40735;
				zoom=3;
				mapInit(lat,lng,zoom);
			}else{
				mark_lation=new google.maps.LatLng(lat,lng);
				mapInit(lat,lng,zoom);
				placeMarker(mark_lation);
			}
		}else{
			lat=39.90453;
			lng=116.40735;
			zoom=3;
			mapInit(lat,lng,zoom);
		}
		google.maps.event.addListener(map, 'click', function(event) {
			clearMarkersBefore();
			placeMarker(event.latLng);
		});
		}
		*/
	});
	/**
	 * 根据选项卡更换地图中心
	 * @param value 地图中心的名称
	 */
	function change_city(value,type){
		//i++;
		var center_province	= $("#province_code").find("option:selected").text();
		var center_city 	= $("#city_code").find("option:selected").text();
		var center_town 	= $("#town_code").find("option:selected").text();
		var lat				= $("#lat").val();
		var lng				= $("#lng").val();
		var zoom 			= 13;
		if(type == 'AddStore' && $("#province_code ").val() == 'all'){
			lat = 39.90479336733392;
			lng = 116.39172881469722;
		}else{
			if(value == 'province_code' &&  $("#province_code ").val() != 'all'){
				center_map = center_province;
			}else if(value=='city_code'){
				if($("#city_code ").val() != '' || $("#city_code ").val() != ''){
					center_map 		= center_province+center_city;
				}else{
					center_map = center_province;
				}
			}else if(value=='town_code'){
				if($("#town_code ").val() != '' || $("#town_code ").val() != 'all'){
					center_map = center_province+center_city+center_town;
				}else{
					center_map 		= center_province+center_city;
				}
			}else{
				center_map="北京市";
			}
			
			if(lat == '' || lng == ''){
				lat = 39.90479336733392;
				lng = 116.39172881469722;
				mapInit(lat,lng,zoom);
			}else{
				zoom=13;
				mapInit(lat,lng,zoom);
				mark_lation=new google.maps.LatLng(lat,lng);
				placeMarker(mark_lation);
			}
		//	if(i>2){
				geocoder.geocode( { 'address': center_map}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					map.setCenter(results[0].geometry.location);
					marker = new google.maps.Marker({
						map: map
					});
				} else {
					alert("Geocode was not successful for the following reason: " + status);
				}
				});
			//}
		}
		google.maps.event.addListener(map, 'click', function(event) {
			clearMarkersBefore();
			placeMarker(event.latLng);
		});
	}
	//返回实例
	var re = {};
	re.init = function(opt){
		opt = $.extend({
			lat:39.90453,
			lng:116.40735,
			zoom:13,
			onPlaceMaker:false
		},opt);
		mapInit(opt.lat,opt.lan,opt.zoom);
	};
	
	//获取地图信息
	re.getMap = function(){
		return map;
	}
		//根据地址查询坐标
	re.codeAddress=function(address,callback) {
		if(address != ''){
			geocoder.geocode( { 'address': address}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					centerCoord=results[0].geometry.location;
					mapOptions = {
							  zoom: 13,
							  center: centerCoord,
							  mapTypeId: google.maps.MapTypeId.ROADMAP
							};
					
					map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);//新地图
					placeMarker(results[0].geometry.location);//标位置
					google.maps.event.addListener(map, 'click', function(event) {
						clearMarkersBefore();
						placeMarker(event.latLng);
					});
				} else {
					alert("你输入的地址搜索不到");
				}
			});
		}else{
			return false;
		}
	}
	return re;
}