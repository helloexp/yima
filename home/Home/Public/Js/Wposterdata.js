

var jsonDatalist = [];
var jsonDataAll = [];
$.each(jsonData, function(i, n) {
	if (i == 53) {
		return true;
	}
	if (i == 61) {
		return true;
	}
	if (i == 104) {
		return true;
	}
	if (i == 101) {
		return true;
	}
	jsonDatalist.push({
		name: n.name,
		viewimg: "./Home/Public/Image/poster/fg/fs" + (i + 1) + ".png",
		data: n
	});
})

$.each(moddata, function(i, m) {
	var mtype = m.type;
	$.each(m.list, function(i, l) {
		var type = l.type;
		$.each(jsonDatalist, function(i, j) {
			var name = j.name.split("，");
			var ismod = false;
			$.each(name, function(i, name) {
				if (name == type) {
					if ($.inArray(j, m.list[0].list) < 0) {
						m.list[0].list.push(j);
					}
					l.list.push(j);
				}
			})
		})
	})
})
//moddata[0].list[0].list = jsonDatalist