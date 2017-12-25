/**
 * 记录log
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
var WCO2OVisitLog = {
    visit_log: function (node_id, visit_page, visit_page_title, log_info) {
        //预载入省
        if (typeof VISIT_LOG_URL == 'undefined') {
            VISIT_LOG_URL = 'index.php?g=Common&m=VisitLog&a=log';
        }

        if (node_id == '') {
            node_id = 0;
        }
        if (visit_page == '') {
            visit_page = 'unknown';
        }
        if (visit_page_title == '') {
            visit_page_title = 'unknown';
        }

        if (log_info == '') {
            log_info = 'unknown';
        }

        try {
            var param = 'node_id=' + node_id + "&visit_page=" + encodeURIComponent(visit_page) + "&visit_page_title=" + encodeURIComponent(visit_page_title) + "&log_info=" + encodeURIComponent(log_info);
            var url =  VISIT_LOG_URL + "&" + param;
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'jsonp',
                success: function (response) {
                    //alert("success" + response);
                },
                error: function (response) {
                    //alert("error:" + JSON.stringify(response) + " url:" + url);
              //      return false;
                }
            });
        } catch(e) {
            //alert("error" + e.name + " -- " + e.message);
        }
    }
};