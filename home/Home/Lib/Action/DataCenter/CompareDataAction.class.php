<?php

/**
 * 横向数据比较
 */
class CompareDataAction extends BaseAction {

    public function index() {
        $select_type = I('post.select_type', '1');
        $channel_arr = C('CHANNEL_TYPE_ARR');
        
        $data_sum = ''; // 视图数据
        $xcate_str = ''; // 柱状图分类
        if ($select_type == '1') {
            // 营销渠道数量类型占比饼图
            $query_arr = M('tchannel')->field(
                'name,type,sns_type,count(*) as count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->group('sns_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    $data_sum .= '[' . "'" .
                         $channel_arr[$v['type']][$v['sns_type']] . "'" . ',' .
                         $v['count'] . '],';
                }
            }
        } elseif ($select_type == '2') {
            // 获得粉丝数量渠道类型占比饼图
            $mem_r = M()->table('tmember_info a')
                ->field(
                'a.channel_id,b.type,b.sns_type,b.name,count(*) as counts')
                ->join('tchannel b on a.channel_id=b.id')
                ->where(
                "a.channel_id!=0 and a.node_id='" . $this->node_id .
                     "' and b.type in('1','2','3')")
                ->group('a.channel_id')
                ->select();
            
            $sql = M()->getLastSql();
            $query_arr = M()->table('(' . $sql . ' ) t')
                ->group('t.sns_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    $data_sum .= '[' . "'" .
                         $channel_arr[$v['type']][$v['sns_type']] . "'" . ',' .
                         $v['counts'] . '],';
                }
            }
        } elseif ($select_type == '3') {
            // 获得粉丝数量TOP10渠道占比柱状图
            $query_arr = M()->table('tmember_info a')
                ->field('a.channel_id,b.name,count(*) as counts')
                ->join('tchannel b on a.channel_id=b.id')
                ->where(
                "a.channel_id!=0 and a.node_id='" . $this->node_id .
                     "' and b.type in('1','2','3')")
                ->group('a.channel_id')
                ->order('counts desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    $data_sum .= '[' . "'" . $v['name'] . "'" . ',' .
                         $v['counts'] . '],';
                    $xcate_str .= "'" . $v['name'] . "'" . ',';
                }
            }
        } elseif ($select_type == '4') {
            // 粉丝互动量渠道类型占比饼图
            $query_arr = M('tchannel')->field(
                'name,type,sns_type,sum(cj_count) as count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->group('sns_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" .
                         $channel_arr[$v['type']][$v['sns_type']] . "'" . ',' .
                         $v['count'] . '],';
                }
            }
        } elseif ($select_type == '5') {
            // 粉丝互动量TOP10渠道占比柱状图
            $query_arr = M('tchannel')->field('name,cj_count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->order('cj_count desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['cj_count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" . $v['name'] . "'" . ',' .
                         $v['cj_count'] . '],';
                    $xcate_str .= "'" . $v['name'] . "'" . ',';
                }
            }
        } elseif ($select_type == '6') {
            // 营销活动访问量渠道类型占比饼图
            $query_arr = M('tchannel')->field(
                'name,type,sns_type,sum(click_count) as count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->group('sns_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" .
                         $channel_arr[$v['type']][$v['sns_type']] . "'" . ',' .
                         $v['count'] . '],';
                }
            }
        } elseif ($select_type == '7') {
            // 营销活动访问量TOP10渠道类型占比饼图
            $query_arr = M('tchannel')->field('name,type,sns_type,click_count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->order('click_count desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['click_count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" . $v['name'] . "'" . ',' .
                         $v['click_count'] . '],';
                    $xcate_str .= "'" . $v['name'] . "'" . ',';
                }
            }
        } elseif ($select_type == '8') {
            // 营销活动发码量渠道类型占比饼图
            $query_arr = M('tchannel')->field(
                'name,type,sns_type,sum(send_count) as count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->group('sns_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" .
                         $channel_arr[$v['type']][$v['sns_type']] . "'" . ',' .
                         $v['count'] . '],';
                }
            }
        } elseif ($select_type == '9') {
            // 营销活动发码量TOP10渠道类型占比饼图
            $query_arr = M('tchannel')->field('name,type,sns_type,send_count')
                ->where(
                "node_id='" . $this->node_id . "' and type in('1','2','3')")
                ->order('send_count desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    if ($v['send_count'] == 0)
                        continue;
                    $data_sum .= '[' . "'" . $v['name'] . "'" . ',' .
                         $v['send_count'] . '],';
                    $xcate_str .= "'" . $v['name'] . "'" . ',';
                }
            }
        }
        $xcate_str = '[' . $xcate_str . ']';
        $this->assign('xcate_str', $xcate_str);
        $this->assign('query_stat', $data_sum);
        $this->assign('select_type', $select_type);
        $this->display();
    }
    // 卡券
    public function goodsData() {
        $select_type = I('post.select_type', '1');
        // 卡券类型
        $goods_type_arr = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '储值卡');
        
        if ($select_type == '1') {
            // 卡券数量类型占比饼图
            $query_arr = M('tgoods_info')->field('goods_type,count(*) as count')
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->group('goods_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    $data_sum .= '[' . "'" . $goods_type_arr[$v['goods_type']] .
                         "'" . ',' . $v['count'] . '],';
                }
                $this->assign('query_stat', $data_sum);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '2') {
            
            // 卡券发放量、核销量类型柱状图
            $sql = "select goods_type,sum(sum1) as send_sum,sum(sum2) as send_sum2 from (SELECT c.goods_type,a.goods_id,a.batch_no,c.goods_name,SUM(a.send_num) AS sum1 , SUM(a.verify_num) AS sum2 FROM tpos_day_count a 
					LEFT JOIN tgoods_info c ON a.goods_id=c.goods_id 
					WHERE ( a.node_id = '" . $this->node_id .
                 "' AND a.batch_no != '' AND a.goods_id != '' ) GROUP BY a.goods_id) t group by t.goods_type ";
            $query_arr = M()->query($sql);
            if ($query_arr) {
                $send_str = '';
                $verify_str = '';
                foreach ($query_arr as $v) {
                    $send_str .= $v['send_sum'] . ',';
                    $verify_str .= $v['send_sum2'] . ',';
                }
                $cate_str = '';
                foreach ($goods_type_arr as $t) {
                    $cate_str .= "'" . $t . "'" . ',';
                }
                $this->assign('cate_str', $cate_str);
                $this->assign('send_str', $send_str);
                $this->assign('verify_str', $verify_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '3') {
            // 卡券发放量
            $query_arr = M()->table('tpos_day_count a')
                ->field('a.goods_id,c.goods_name,sum(a.send_num)as sums ')
                ->join('tgoods_info c on a.goods_id=c.goods_id')
                ->where(
                "a.node_id = '" . $this->node_id .
                     "' and a.batch_no != '' and a.goods_id != '' ")
                ->group('a.goods_id')
                ->order('sums desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $send_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $send_str .= $v['sums'] . ',';
                    $cate_str .= "'" . $v['goods_name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('send_str', $send_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '4') {
            $query_arr = M()->table('tpos_day_count a')
                ->field('a.goods_id,c.goods_name,sum(a.verify_num)as sums ')
                ->join('tgoods_info c on a.goods_id=c.goods_id')
                ->where(
                "a.node_id = '" . $this->node_id .
                     "' and a.batch_no != '' and a.goods_id != '' ")
                ->group('a.goods_id')
                ->order('sums desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $send_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $send_str .= $v['sums'] . ',';
                    $cate_str .= "'" . $v['goods_name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('send_str', $send_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '5') {
            // 卡券核销量TOP10门店柱状图
            $query_arr = M()->table('tpos_day_count a')
                ->field('b.pos_name,count(*) as counts')
                ->join('inner join tpos_info b on a.pos_id=b.pos_id')
                ->where("a.node_id='" . $this->node_id . "'")
                ->group('a.pos_id')
                ->order('counts desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $send_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $send_str .= $v['counts'] . ',';
                    $cate_str .= "'" . $v['pos_name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('send_str', $send_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        }
    }
    
    // 活动
    public function batchData() {
        $select_type = I('post.select_type', '1');
        $batch_type_arr = C('BATCH_TYPE_NAME');
        if ($select_type == '1') {
            // 卡券活动类型占比饼图
            $query_arr = M('tmarketing_info')->field(
                'batch_type,count(*) as count')
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->group('batch_type')
                ->select();
            if ($query_arr) {
                foreach ($query_arr as $v) {
                    $data_sum .= '[' . "'" . $batch_type_arr[$v['batch_type']] .
                         "'" . ',' . $v['count'] . '],';
                }
                $this->assign('query_stat', $data_sum);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '2') {
            // 营销活动访问量、粉丝互动人次、吸收粉丝数量类型柱状图
            
            $sql = "SELECT i.`batch_type`,SUM(i.click_count) AS click_count,SUM(i.cj_count) AS cj_count,IFNULL(e.m_count,0) AS m_count 
			 	    FROM tmarketing_info i LEFT JOIN (
					SELECT SUM(counts) AS m_count,batch_type FROM 
					( SELECT a.batch_id,b.batch_type,COUNT(*) AS counts FROM tmember_info a 
					INNER JOIN tmarketing_info b ON a.batch_id = b.id WHERE a.node_id='" .
                 $this->node_id . "' GROUP BY a.batch_id ) t 
					GROUP BY batch_type) e ON i.batch_type = e.batch_type 
					WHERE i.node_id='" .
                 $this->node_id . "' GROUP BY i.batch_type ";
            
            $query_arr = M()->query($sql);
            if ($query_arr) {
                $click_str = '';
                $cj_str = '';
                $member_str = '';
                foreach ($query_arr as $v) {
                    $click_str .= $v['click_count'] . ',';
                    $cj_str .= $v['cj_count'] . ',';
                    // $v['m_count'] = '0';
                    $member_str .= $v['m_count'] . ',';
                }
                $cate_str = '';
                foreach ($batch_type_arr as $t) {
                    $cate_str .= "'" . $t . "'" . ',';
                }
                $this->assign('click_str', $click_str);
                $this->assign('cj_str', $cj_str);
                $this->assign('member_str', $member_str);
                $this->assign('cate_str', $cate_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '3') {
            $query_arr = M('tmarketing_info')->field('name,click_count')
                ->where("node_id='" . $this->node_id . "'")
                ->order('click_count desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $data_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $data_str .= $v['click_count'] . ',';
                    $cate_str .= "'" . $v['name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('data_str', $data_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '4') {
            $query_arr = M('tmarketing_info')->field('name,cj_count')
                ->where("node_id='" . $this->node_id . "'")
                ->order('cj_count desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $data_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $data_str .= $v['cj_count'] . ',';
                    $cate_str .= "'" . $v['name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('data_str', $data_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        } elseif ($select_type == '5') {
            $query_arr = M()->table('tmember_info a')
                ->field('b.name,count(*) as counts')
                ->join('inner join tmarketing_info b on a.batch_id=b.id')
                ->where("a.node_id='" . $this->node_id . "'")
                ->group('a.batch_id')
                ->order('counts desc')
                ->limit(10)
                ->select();
            if ($query_arr) {
                $data_str = '';
                $cate_str = '';
                foreach ($query_arr as $v) {
                    $data_str .= $v['counts'] . ',';
                    $cate_str .= "'" . $v['name'] . "'" . ',';
                }
                
                $this->assign('cate_str', $cate_str);
                $this->assign('data_str', $data_str);
                $this->assign('select_type', $select_type);
            }
            $this->display();
        }
    }
}
