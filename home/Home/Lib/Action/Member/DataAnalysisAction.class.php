<?php

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor.
 */

/**
 * Description of DataAnalysisAction 数据统计
 *
 * @author zs
 */
class DataAnalysisAction extends MemberAction {

    public function index3() {
        $model = M('tmember_info');
        $wh = "AND t.node_id ='" . $this->node_id . "'";
        $date_wh = '';
        $begin_time = I('begin_time', null, 'mysql_real_escape_string');
        if (empty($begin_time))
            if (empty($begin_time))
                $begin_time = date('Ym01', 
                    strtotime(
                        date('Y', time()) . '-' . (date('m', time()) - 1) . '-01'));
        $end_time = I('end_time', null, 'mysql_real_escape_string');
        if (empty($end_time))
            $end_time = date('Ymd');
        if (! empty($begin_time)) {
            $date_wh .= " and t.trans_time >='" . $begin_time . "000000'";
        }
        if (! empty($end_time)) {
            $date_wh .= " and t.trans_time <='" . $end_time . "235959'";
        }
        if (! empty($end_time) && ! empty($begin_time)) {
            
            $member_wh = " and add_time >='" . $begin_time .
                 "000000' and add_time <='" . $end_time . "235959'";
            $send_wh = "  and b.trans_time >='" . $begin_time .
                 "000000' and b.trans_time <='" . $end_time . "235959'";
            $verify_wh = " and  p.trans_time >='" . $begin_time .
                 "000000' and p.trans_time <='" . $end_time . "235959'";
        }
        
        // 活跃用户
        $trans_sql = "select count(b.date) num,date from 
(SELECT
	count(*) num,DATE_FORMAT(trans_time, '%Y%m%d') date
FROM
	tpos_trace t
WHERE
        t.status='0' and t.trans_type = '0' and t.node_id = '" .
             $this->node_id . "'
	and t.phone_no IN (
		SELECT
			phone_no
		FROM
			tmember_info  where node_id = '" . $this->node_id . "' 
	) " .
             $date_wh . " GROUP BY date,t.phone_no) b GROUP BY b.date limit 25";
        
        $add_sql = "select count(*) num ,DATE_FORMAT(t.add_time, '%Y%m%d') date from tmember_info t where 1  and t.node_id ='" .
             $this->node_id . "' " . $member_wh . " group by date limit 25";
        
        // 发码量，验码量
        $send_sql = "SELECT
	count(*) num,DATE_FORMAT(trans_time, '%Y%m%d') date
FROM
	tbarcode_trace b,
	(SELECT
		phone_no
	FROM
		tmember_info t where 1 " . $wh .
             ") i
WHERE
	b.phone_no = i.phone_no AND b.node_id='{$this->node_id}' AND b.trans_type='0001' AND b.status='0' " .
             $send_wh . "
group  by date";
        
        $verify_sql = "SELECT
	count(*) num,DATE_FORMAT(trans_time, '%Y%m%d') date
FROM
	tpos_trace p,
	(SELECT
		phone_no
	FROM
		tmember_info t where  t.node_id = '" .
             $this->node_id . "') i
WHERE
	p.phone_no = i.phone_no 
        and p.node_id = '" . $this->node_id . "'
        AND p.status = '0' AND p.trans_type ='0' " .
             $verify_wh . "
group  by date";
        
        // var_dump($trans_list);
        $age_sql = "SELECT
                CASE 
                WHEN t.age<=18 THEN '0~18'
                WHEN t.age<=23 THEN '19~23'
                WHEN t.age<=28 THEN '24~28'
                WHEN t.age<=35 THEN '29~35'
                WHEN t.age>35 THEN '大于35'
                ELSE
                '未知'
                END AS level,
                sex,
                COUNT(*) as num
              FROM tmember_info t
              WHERE 1 " . $wh .
             $member_wh . " GROUP BY LEVEL";
        
        $sex_sql = "SELECT
                CASE 
                WHEN t.sex='1' THEN '男'
                WHEN t.sex='2' THEN '女'
                else
                '未知' 
                end as level, 
                count(*) as num          
                 from tmember_info t WHERE 1 " . $wh .
             $member_wh . " group by level";
        
        $age_info = $model->query($age_sql);
        $sex_info = $model->query($sex_sql);
        $add_list = $model->query($add_sql); // 新增
        $trans_list = $model->query($trans_sql); // 活跃
        $send_list = $model->query($send_sql); // 发码
        $verify_list = $model->query($verify_sql); // 验码
        
        $show = I('show', null);
        if (empty($show))
            $show = '1';
        $this->assign('send_list', $send_list);
        $this->assign('verify_list', $verify_list);
        $this->assign('add_list', $add_list);
        $this->assign('trans_list', $trans_list);
        $this->assign('sex_list', $sex_info);
        $this->assign('age_list', $age_info);
        $this->assign('begin_time', $begin_time);
        $this->assign('end_time', $end_time);
        $this->assign('show', $show);
        $this->display();
    }
    // put your code here
    public function index() {
        $begin_time = I('begin_time', null, 'mysql_real_escape_string');
        if (empty($begin_time))
            $begin_time = date('Y-m-01', 
                strtotime(
                    date('Y', time()) . '-' . (date('m', time()) - 1) . '-01'));
        $end_time = I('end_time', null, 'mysql_real_escape_string');
        if (empty($end_time))
            $end_time = date('Ymd');
        
        $wh = "AND p.node_id ='" . $this->node_id . "'";
        if (! empty($begin_time)) {
            $wh .= " and p.trans_time >='" . $begin_time . "000000'";
        }
        if (! empty($end_time)) {
            $wh .= " and p.trans_time <='" . $end_time . "235959'";
        }
        
        $model = M('tmember_info');
        
        $sql = "SELECT
                count(*) num, DATE_FORMAT(p.trans_time, '%H') date
        FROM
                tpos_trace p
        WHERE
             p.status = '0' and p.trans_type = '0'
             and 
                p.phone_no IN (
                        SELECT
                                t.phone_no
                        FROM
                                tmember_info t where t.node_id ='" .
             $this->node_id . "'
                )" . $wh . " group by date";
        $list = $model->query($sql);
        $this->assign('query_list', $list);
        $this->assign('begin_time', $begin_time);
        $this->assign('end_time', $end_time);
        $this->display();
    }
}

?>
