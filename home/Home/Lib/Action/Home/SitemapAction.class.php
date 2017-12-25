<?php

/**
 * 旺财网站地图 SEO
 *
 * @author bao
 */
class SitemapAction extends Action {

    public function index() {
        $Cache = Cache::getInstance('File', 
            array(
                'expire' => '3600'));
        if ($Cache->get('name')) {
            $outcome = $Cache->get('name');
            // echo '2';
        } else {
            $sql = "SELECT CONCAT('http://www.wangcaio2o.com/index.php?g=Home&m=Case&a=activityView&show_id=',a.`id`) AS url,c.`name` AS url_name FROM tbatch_channel a LEFT JOIN tchannel b ON a.`channel_id` = b.id 
     LEFT JOIN tmarketing_info c ON a.`batch_id` = c.`id`
     WHERE b.type = '1' AND b.sns_type = '12' AND c.name NOT LIKE '%测试%' LIMIT 5000
     UNION ALL 
     SELECT CONCAT('http://www.wangcaio2o.com/index.php?g=Home&m=Contactus&a=view&news_id=',news_id,'&class_id=',class_id) AS url,news_name AS url_name FROM tym_news WHERE class_id IN ('1','2') LIMIT 5000";
            $outcome = M()->query($sql);
            $Cache->set('name', $outcome);
        }
        $this->assign('list', $outcome);
        $this->display();
    }
}
