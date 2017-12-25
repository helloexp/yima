<?php

/* 旺分销 */

class WfxService {

    public $opt = array();

    public $errcode = 0;

    public function __construct() {
        C('LOG_PATH', LOG_PATH . 'WFX_'); // 重新定义目志目录
    }

    /* 设置参数 */
    public function setopt() {
    }

    /*
     * $showSalerName //是否显示销售员
     */
    public function get_saler_info($node_id, $saler_id, $showSalerName) {
        $salerInfo = $this->getUseSaler($saler_id, $showSalerName, $node_id);

        return $salerInfo;
    }

    /**
     * Description of SkuService 查询未停用分销商信息
     *
     * @param int $salerId 分销商ID int $nodeId 商户标识 int $showSalerName 是否显示分销商
     *
     * @return array $salerInfo
     * @author john_zeng
     */
    public function getUseSaler($salerId, $showSalerName, $node_id = '') {
        $where = "(status = '3' or status = '4') and id = " . $salerId;
        if ('' != $node_id) {
            $where = $where . " and node_id = '" . $node_id . "'";
        }
        $salerInfo = M()->table('twfx_saler')->where($where)->find(); // 没有绑定关系
        if (false === $salerInfo) {
            return null;
        } else {
            if ('4' === $salerInfo['status']) {
                return self::getUseSaler($salerInfo['parent_id'], $showSalerName);
            } else {
                $salerInfo['showSalerName'] = $showSalerName;

                return $salerInfo;
            }
        }
    }

    public function get_saler_info_by_phone($node_id, $phone_no) {
        $where      = "status = '3' and node_id = '" . $node_id . "' and phone_no = '" . $phone_no . "'";
        $saler_info = M()->table('twfx_saler')->where($where)->find(); // 没有绑定关系
        if (!$saler_info) {
            $this->_log("get_bind_saler find twfx_saler error " . M()->_sql());

            return null;
        } else {
            return $saler_info;
        }
    }

    public function get_saler_info_by_custom_no($node_id, $custom_no) {
        $where      = "status = '3' and node_id = '" . $node_id . "' and custom_no = '" . $custom_no . "'";
        $saler_info = M()->table('twfx_saler')->where($where)->find(); // 没有绑定关系
        if (!$saler_info) {
            $this->_log("get_bind_saler find twfx_saler error " . M()->_sql());

            return null;
        } else {
            return $saler_info;
        }
    }

    private function get_saler_info_by_salerid($node_id, $saler_id) {
        $where      = "status = '3' and node_id = '" . $node_id . "' and id = " . $saler_id;
        $saler_info = M()->table('twfx_saler')->where($where)->find(); // 没有绑定关系
        if (!$saler_info) {
            $this->_log("get_bind_saler find twfx_saler error " . M()->_sql());

            return null;
        } else {
            // 是否显示销售员
            $saler_info['showSalerName'] = $showSalerName;

            return $saler_info;
        }
    }

    /*
     * $node_id 机构号 必填 $phone_no 客户手机号码 必填 $saler_id 销售员ID 可选 $m_id 营销活动ＩＤ
     * $this->errcode 不等于0的情况下，不展示销售顾问　 return null or twfx_saler array;
     */
    public function get_bind_saler($node_id, $phone_no, $m_id, $saler_id = null) {
        $this->errcode = -1;

        // 判断机构是否有权限
        $where     = "status = '0' and node_id = '" . $node_id . "'";
        $node_info = M()->table('tnode_info')->where($where)->find();
        if (!$node_info) {
            $this->_log("get_bind_saler find tnode_info error " . M()->_sql());

            return null;
        }
        // 判断用户组权限wc_version

        // 判断商品配置是否存在
        // 判断是否有商品是否有配置提成
        $where      = "m_id = " . $m_id;
        $goods_info = M()->table('twfx_goods_config')->where($where)->find();
        if (!$goods_info) {
            $this->_log("get_bind_saler find twfx_goods_config nothing " . M()->_sql());

            return null;
        }
        $this->errcode = 0;
        // 查找机构是否开启了保护
        $where     = "node_id = '" . $node_id . "'";
        $node_info = M()->table('twfx_node_info')->where($where)->find();
        if (!$node_info) {
            $this->_log("get_bind_saler find twfx_node_info error " . M()->_sql());

            return null;
        }
        if ($node_info['customer_bind_flag'] == '2' || $node_info['customer_bind_flag'] == '3') {

            $where         = "status = '1' and node_id = '" . $node_id . "' and phone_no = '" . $phone_no . "'";
            $relation_info = M()->table('twfx_customer_relation')->where($where)->find();
            if (!$relation_info) {
                if ($saler_id != null) {
                    return $this->get_saler_info($node_id, $saler_id, $node_info['show_saler_name']);
                } else {
                    return null;
                }
            } else { // 有绑定关系
                return $this->get_saler_info($node_id, $relation_info['saler_id'], $node_info['show_saler_name']);
            }
        } else {
            if ($saler_id != null) {
                return $this->get_saler_info($node_id, $saler_id, $node_info['show_saler_name']);
            } else {
                return null;
            }
        }
    }

    private function get_marketing_info($m_id) {
        $marketing_info = M()->table('tmarketing_info')->where('id = ' . $m_id)->find();
        if (!$marketing_info) {
            return null;
        } else {
            return $marketing_info;
        }
    }

    private function get_batch_info($b_id) {
        $batch_info = M()->table('tbatch_info')->where('id = ' . $b_id)->find();
        if (!$batch_info) {
            return null;
        } else {
            return $batch_info;
        }
    }

    private function stat_bonus(
            $node_id,
            $saler_id,
            $order_count,
            $amount,
            $bonus_amount
    ) {
        $date = date('Ymd');
        // 判断是否有商品是否有配置提成
        $where   = "node_id = '" . $node_id . "' and order_date = '" . $date . "' and saler_id =" . $saler_id;
        $daystat = M()->table('twfx_saler_daystat')->where($where)->find();
        if (!$daystat) { // 新增
            $daystat['node_id']      = $node_id;
            $daystat['saler_id']     = $saler_id;
            $daystat['order_date']   = $date;
            $daystat['order_count']  = $order_count;
            $daystat['amount']       = $amount;
            $daystat['bonus_amount'] = $bonus_amount;
            $rs                      = M()->table('twfx_saler_daystat')->add($daystat);
            // 添加成功
            if ($rs !== false) {
                return true;
            }
        }
        // 更新
        $rs = M('twfx_saler_daystat')->where($where)->setInc('order_count', $order_count);
        if ($rs === false) {
            $this->_log("stat_bonus find twfx_saler_daystat update error " . M()->_sql());

            return false;
        }
        $rs = M('twfx_saler_daystat')->where($where)->setInc('amount', $amount);
        log_write("twfx_saler_daystat".M()->getLastSql());
        if ($rs === false) {
            $this->_log("stat_bonus find twfx_saler_daystat update error " . M()->_sql());

            return false;
        }
        $rs = M('twfx_saler_daystat')->where($where)->setInc('bonus_amount', $bonus_amount);
        if ($rs === false) {
            $this->_log("stat_bonus find twfx_saler_daystat update error " . M()->_sql());

            return false;
        }

        return true;
    }

    // 获取根据
    public function get_bonus_config($node_id, $saler_id, $m_id) {
        $resp_arr['saler_percent']  = 0;
        $resp_arr['manage_percent'] = 0;
        // 判断是否有商品是否有配置提成
        $where      = "m_id = " . $m_id;
        $goods_info = M()->table('twfx_goods_config')->where($where)->find();
        if (!$goods_info) {
            $this->_log("get_bonus_config find twfx_goods_config nothing " . M()->_sql());

            return $resp_arr;
        }
        if ($goods_info['bonus_flag'] != '1') { // 无提成
            $this->_log("get_bonus_config find twfx_goods_config no bonus" . M()->_sql());

            return $resp_arr;
        }
        $saler_info = $this->get_saler_info_by_salerid($node_id, $saler_id);
        if (!$saler_info) {
            $this->_log("get_bonus_config  get_saler_info_by_salerid error" . M()->_sql());

            return $resp_arr;
        }
        // 解析配置
        $bonus_config = json_decode($goods_info['bonus_config_json'], true);
        // 根据角色处理当前层级提成
        if ($saler_info['role'] == '1') { // 销售员
            if ($bonus_config['salers_config']['default_flag'] == '0') { // 等级指定提成
                $resp_arr['saler_percent']  = $bonus_config['salers_config']['default_saler_percent'];
                $resp_arr['manage_percent'] = 0;
            } else if ($bonus_config['salers_config']['nodes']['id_' . $saler_info['id']] != null) { // 是否指定提成
                $resp_arr['saler_percent']  = $bonus_config['salers_config']['nodes']['id_' . $saler_info['id']]['saler_percent'];
                $resp_arr['manage_percent'] = 0;
            } else {
                $resp_arr['saler_percent']  = $saler_info['default_sale_percent'];
                $resp_arr['manage_percent'] = 0;
            }
            $saler_info['level'] = $saler_info['level'] + 1; // 处理成和下级经销商一致的级别，方便循环计算
        } else { // 经销商
            if ($bonus_config['level_' . $saler_info['level']]['default_flag'] == '0') { // 等级指定提成
                $resp_arr['saler_percent']  = $bonus_config['level_' . $saler_info['level']]['default_saler_percent'];
                $resp_arr['manage_percent'] = $bonus_config['level_' . $saler_info['level']]['default_manage_percent'];
            } else if ($bonus_config['agency_config']['nodes']['id_' . $saler_info['id']] != null) { // 是否指定提成
                $resp_arr['saler_percent']  = $bonus_config['agency_config']['nodes']['id_' . $saler_info['id']]['saler_percent'];
                $resp_arr['manage_percent'] = $bonus_config['agency_config']['nodes']['id_' . $saler_info['id']]['manage_percent'];
            } else {
                $resp_arr['saler_percent']  = $saler_info['default_sale_percent'];
                $resp_arr['manage_percent'] = $saler_info['default_manage_percent'];
            }
        }

        return $resp_arr;
    }

    // 获取订单商品信息和金额 需开启事务
    private function return_bonus_by_goods(
            $node_id,
            $saler_info,
            $phone_no,
            $customer_name,
            $m_id,
            $order_id,
            $price,
            $num,
            $amount,
            $goods_name
    ) {
        // 判断是否有商品是否有配置提成
        $where      = "m_id = " . $m_id;
        $goods_info = M()->table('twfx_goods_config')->where($where)->find();
        if (!$goods_info) {
            $this->_log("return_bonus_by_goods find twfx_goods_config nothing " . M()->_sql());

            return true;
        }
        if ($goods_info['bonus_flag'] != '1') { // 无提成
            $this->_log("return_bonus_by_goods find twfx_goods_config no bonus" . M()->_sql());

            return true;
        }

        $wfx_trace['node_id']       = $node_id;
        $wfx_trace['order_id']      = $order_id;
        $wfx_trace['saler_id']      = $saler_info['id'];
        $wfx_trace['wfx_config_id'] = $goods_info['id'];
        $wfx_trace['m_id']          = $m_id;
        $wfx_trace['num']           = $num;
        $wfx_trace['price']         = $price;
        $wfx_trace['amount']        = $amount;
        $wfx_trace['goods_name']    = $goods_name;
        $wfx_trace['phone_no']      = $phone_no;
        $wfx_trace['customer_name'] = $customer_name;
        $wfx_trace['add_time']      = date('YmdHis');
        // 解析配置
        $bonus_config = json_decode($goods_info['bonus_config_json'], true);
        // 根据角色处理当前层级提成
        if ($saler_info['role'] == '1') { // 销售员
            if ($bonus_config['salers_config']['default_flag'] == '0') { // 等级指定提成
                $wfx_trace['bonus_amount'] = $amount * $bonus_config['salers_config']['default_saler_percent'] / 100;
            } else if ($bonus_config['salers_config']['nodes']['id_' . $saler_info['id']] != null) { // 是否指定提成
                $wfx_trace['bonus_amount'] = $amount * $bonus_config['salers_config']['nodes']['id_' . $saler_info['id']]['saler_percent'] / 100;
            } else {
                $wfx_trace['bonus_amount'] = $amount * $saler_info['default_sale_percent'] / 100;
            }
            $saler_info['level'] = $saler_info['level'] + 1; // 处理成和下级经销商一致的级别，方便循环计算
        } else { // 经销商
            if ($bonus_config['level_' . $saler_info['level']]['default_flag'] == '0') { // 等级指定提成
                $wfx_trace['bonus_amount'] = $amount * $bonus_config['level_' . $saler_info['level']]['default_saler_percent'] / 100;
            } else if ($bonus_config['agency_config']['nodes']['id_' . $saler_info['id']] != null) { // 是否指定提成
                $wfx_trace['bonus_amount'] = $amount * $bonus_config['agency_config']['nodes']['id_' . $saler_info['id']]['saler_percent'] / 100;
            } else {
                $wfx_trace['bonus_amount'] = $amount * $saler_info['default_sale_percent'] / 100;
            }
        }
        $rs = M()->table('twfx_trace ')->add($wfx_trace);
        if (!$rs) {
            $this->_log("return_bonus_by_goods add twfx_trace saler error" . M()->_sql());
            M()->rollback();

            return false;
        }

        // 微信模板消息链接页
        $url = U("Label/MyOrder/index", array(
                "node_id" => $node_id,
        ), '', '', true);

        // weixin Notify
        $this->weixin_notify($saler_info, $wfx_trace, $url);
        // 累加本级提成，销售额，订单数
        $bonus_amount_stat = $wfx_trace['bonus_amount'];
        $this->stat_bonus($node_id, $saler_info['id'], 1, $amount, $bonus_amount_stat);
        /*
         * 美惠非标处理
         */
        if($node_id==C('meihui.node_id')){
            $amount1=M("ttg_order_info")->where(array('order_id'=>$order_id,'node_id'=>$node_id))->getField('order_amt');
            D('MeiHui','Model')->mHstatBonus($node_id, $saler_info['id'], 1, $amount1, $bonus_amount_stat);
        }
// 循环处理上层的管理提成
            $parent_saler_info = $this->get_saler_info_by_salerid($node_id, $saler_info['parent_id']);
            for ($i = $saler_info['level'] - 1; $i > 0; $i--) {
                // 取上级机构的信息
                $wfx_trace['saler_id'] = $parent_saler_info['id'];
                if ($parent_saler_info == null) {
                    $this->_log("return_bonus_by_goods get parent management error" . M()->_sql());
                    M()->rollback();

                    return false;
                }
                if ($bonus_config['level_' . $i]['default_flag'] == '0') { // 等级指定提成
                    $wfx_trace['bonus_amount'] = $amount * $bonus_config['level_' . $i]['default_manage_percent'] / 100;
                    log_write($i."amount".$amount."default_manage_percent".$bonus_config['level_' . $i]['default_manage_percent']);
                } else if ($bonus_config['agency_config']['nodes']['id_' . $parent_saler_info['id']] != null) { // 是否指定提成
                    $wfx_trace['bonus_amount'] = $amount * $bonus_config['agency_config']['nodes']['id_' . $parent_saler_info['id']]['manage_percent'] / 100;
                } else {
                    $wfx_trace['bonus_amount'] = $amount * $parent_saler_info['default_manage_percent'] / 100;
                }
                log_write($i."wfx_trace".print_r($wfx_trace,true));
                $rs = M()->table('twfx_trace ')->add($wfx_trace);
                if (!$rs) {
                    $this->_log("return_bonus_by_goods add twfx_trace management error" . M()->_sql());
                    M()->rollback();
                    return false;
                }
                // TODO weixin Notify
                $this->weixin_notify($parent_saler_info, $wfx_trace, $url);
                // 累加本级提成，销售额，订单数
                $bonus_amount_stat =$wfx_trace['bonus_amount'];
                $this->stat_bonus($node_id, $parent_saler_info['id'], 1, $amount, $bonus_amount_stat);

                if ($parent_saler_info['parent_id'] != 0) {
                    $parent_saler_info = $this->get_saler_info_by_salerid($node_id, $parent_saler_info['parent_id']);
                }
            }
    }
    public function bind_customer($node_id, $phone_no, $saler_id, $bindFrom = 1) {
        // 查找机构是否开启了保护
        $where     = "node_id = '" . $node_id . "'";
        $node_info = M()->table('twfx_node_info')->where($where)->find();
        if (!$node_info) {
            $this->_log("get_bind_saler find twfx_node_info error " . M()->_sql());

            return;
        }
        if (in_array($node_info['customer_bind_flag'], array(
                '2',
                '3',
        ))) {
            // 查找是否有已绑定过的销售
            $where         = "status = '1' and node_id = '" . $node_id . "' and phone_no = '" . $phone_no . "'";
            $relation_info = M()->table('twfx_customer_relation')->where($where)->find(); // 没有绑定关系
            if (!$relation_info) {
                // 进行客户绑定
                $twfx_customer_relation['node_id']   = $node_id;
                $twfx_customer_relation['phone_no']  = $phone_no;
                $twfx_customer_relation['saler_id']  = $saler_id;
                $twfx_customer_relation['status']    = '1';
                $twfx_customer_relation['bind_from'] = $bindFrom;
                $twfx_customer_relation['add_time']  = date('YmdHis');
                $rs                                  = M()->table('twfx_customer_relation ')->add($twfx_customer_relation);
                if (!$rs) {
                    $this->_log("bind_customer add twfx_customer_relation  error" . M()->_sql());

                    return;
                }
            }
        }
    }

    /*
     * 提成 $node_id 机构号 $order_id 订单号 $saler_id 销售员ID $phone_no 客户手机号码 $customer_name
     * 客户姓名
     */
    public function return_bonus($node_id, $order_id, $saler_id) {
        $this->_log("return_bonus inparm node_id:" . $node_id . " order_id:" . $order_id . " saler_id:" . $saler_id);
        // 检查 twfx_saler 状态
        $saler_info = $this->get_saler_info_by_salerid($node_id, $saler_id);
        if (!$saler_info) {
            // 尝试级联获取上级的正常销售员信息
            $saler_info = $this->getUseSaler($saler_id, $showSalerName, $node_id);
            if (!$saler_info) {
                return array(
                        'code' => '1001',
                        'desc' => '未找到状态正常的销售员',
                );
            }
        }
        // 获取订单信息
        $where      = "pay_status = '2' and node_id = '" . $node_id . "' and order_id = '" . $order_id . "'";
        $order_info = M()->table('ttg_order_info')->where($where)->find();
        if (!$order_info) {
            return array(
                    'code' => '1002',
                    'desc' => '未找到状态正常的订单',
            );
        }
        $phone_no = $order_info['order_phone'];
        if ($saler_info['id'] != $saler_id) {
            // 修改订单里面的销售员ID
            $order_info_change['saler_id'] = $saler_info['id'];
            $rs                            = M()->table('ttg_order_info')->where($where)->save($order_info_change);
            if ($rs === false) {
                return array(
                        'code' => '1008',
                        'desc' => '修改订单里面的销售员ID 失败',
                );
            }
        }

        // 客户绑定处理
        $this->bind_customer($node_id, $phone_no, $saler_info['id']);
        // 分单品 还是订单进行处理
        // 开启事务
        M()->startTrans();
        if ($order_info['order_type'] == '0') { // 单品
            $marketing_info = $this->get_marketing_info($order_info['batch_no']);
            if (!$marketing_info) {
                $this->_log("return_bonus get marketing_info  error" . M()->_sql());
                M()->rollback();

                return array(
                        'code' => '1003',
                        'desc' => '未找到状态正常的营销活动',
                );
            }

            $rs = $this->return_bonus_by_goods($node_id, $saler_info, $phone_no, $customer_name,
                    $order_info['batch_no'], $order_id, $order_info['price'], $order_info['buy_num'],
                    $order_info['price'] * $order_info['buy_num'], $marketing_info['name']);
            if ($rs === false) {
                $this->_log("return_bonus get return_bonus_by_goods  error" . M()->_sql());
                M()->rollback();

                return array(
                        'code' => '1004',
                        'desc' => '提成处理失败',
                );
            }
        } else if ($order_info['order_type'] == '2') { // 小店订单
            $where           = "order_id = '" . $order_id . "'";
            $order_info_list = M()->table('ttg_order_info_ex')->where($where)->select();
            if (!$order_info_list) {
                $this->_log("return_bonus query ttg_order_info_ex  error" . M()->_sql());
                M()->rollback();

                return array(
                        'code' => '1005',
                        'desc' => '查询子订单失败',
                );
            }
            foreach ($order_info_list as $order_info_ex) {
                // 查找m_id
                $batch_info = $this->get_batch_info($order_info_ex['b_id']);
                if (!$batch_info) {
                    $this->_log("return_bonus get batch_info  error" . M()->_sql());
                    M()->rollback();

                    return array(
                            'code' => '1006',
                            'desc' => '未找到状态正常的batch_info',
                    );
                }
                $rs = $this->return_bonus_by_goods($node_id, $saler_info, $phone_no, $customer_name,
                        $batch_info['m_id'], $order_id, $order_info_ex['price'], $order_info_ex['goods_num'],
                        $order_info_ex['amount'], $order_info_ex['b_name']);
                if ($rs === false) {
                    $this->_log("return_bonus get return_bonus_by_goods  error" . M()->_sql());
                    M()->rollback();

                    return array(
                            'code' => '1007',
                            'desc' => '提成处理失败',
                    );
                }
            }
        }

        M()->commit(); // 提交事务
        return true;
    }

    // 销售员提领 $month_flag 月处理标志 1 为不控制月份 2-按月 不再使用这个方法
    public function saler_get_bonus($node_id, $saler_id, $month_flag = 1) {
        $add_time = date("YmdHis");
        $this->_log("处理 saler_id:" . $saler_id);
        // 批量更新当前数据 为处理中
        if ($month_flag == 1) {
            $month = date("Ym");
            // 当月的开始时间和结束时间
            $start_get_date = date("Ym01");
            $end_get_date   = date("Ymd", strtotime(date("Ym01000000", strtotime("+1 month"))) - 1);
            $sql            = "update twfx_trace set user_get_flag = '1'  where user_get_flag = '0' and  saler_id = " . $saler_id;
        } else if ($month_flag == 2) { // 按月
            $month = date("Ym", strtotime("-1 month")); // TODO
            // 当月的开始时间和结束时间
            $start_get_date = date("Ym01", strtotime("-1 month"));
            $end_get_date   = date("Ymd", strtotime(date("Ym01000000")) - 1);
            $sql            = "update twfx_trace set user_get_flag = '1'  where user_get_flag = '0' and  add_time like '" . $month . "%' and   saler_id = " . $saler_id;
        }
        $rs = M()->execute($sql);
        if ($rs === false) {
            $this->_log("批量更新twfx_trace表失败 error" . M()->_sql());

            return false;
        } else if ($rs == 0) {
            $this->_log("没有要处理的数据 no data" . M()->_sql());

            return true;
        }
        $saler_info = $this->get_saler_info_by_salerid($node_id, $saler_id);
        $where      = "user_get_flag = '1' and  saler_id = " . $saler_id;
        $rs         = M()->table('twfx_trace')->where($where)->field('SUM(bonus_amount) as bonus_amount_sum, SUM(amount) as amount_sum')->find();
        if (!$rs) {
            $this->_log("统计twfx_trace表失败 " . M()->_sql());

            return false;
        }
        $wfx_get_trace['node_id']        = $saler_info['node_id'];
        $wfx_get_trace['saler_id']       = $saler_info['id'];
        $wfx_get_trace['bonus_amount']   = $rs['bonus_amount_sum'];
        $wfx_get_trace['sale_amount']    = $rs['amount_sum'];
        $wfx_get_trace['alipay_acount']  = $saler_info['alipay_account'];
        $wfx_get_trace['add_time']       = $add_time;
        $wfx_get_trace['month']          = $month;
        $wfx_get_trace['deal_flag']      = '0';
        $wfx_get_trace['start_get_date'] = $start_get_date;
        $wfx_get_trace['end_get_date']   = $end_get_date;
        $rs                              = M('twfx_get_trace')->add($wfx_get_trace);
        if ($rs === false) {
            $this->_log("插入twfx_get_trace表失败 " . M()->_sql());

            return $log;
        }
        $wfx_get_trace_id = $rs;
        // 批量更新当前数据 为提领请求已提交
        if ($month_flag == 1) {
            $sql = "update twfx_trace set user_get_flag = '2' ,user_get_trace_id = " . $wfx_get_trace_id . ", user_get_time = '" . $add_time . "'  where user_get_flag = '1' and  saler_id = " . $saler_id;
        } else {
            $sql = "update twfx_trace set user_get_flag = '2' ,user_get_trace_id = " . $wfx_get_trace_id . ", user_get_time = '" . $add_time . "'  where user_get_flag = '1' and  add_time like '" . $month . "%'  and saler_id = " . $saler_id;
        }
        $rs = M()->execute($sql);
        if ($rs === false) {
            $this->_log("批量更新twfx_trace表失败  " . M()->_sql());

            return false;
        }

        return true;
    }

    // 销售员按月提领
    public function saler_get_bonus_by_month(
            $saler_info,
            $first_start_time,
            $first_end_time
    ) {
        $add_time = date("YmdHis");
        $saler_id = $saler_info['id'];
        $this->_log("处理 saler_id:" . $saler_id);
        $month = date("Ym");
        $now   = time();

        $month_start = $first_start_time;
        $month_end   = $first_end_time;
        // 按周为周期进行循环处理
        while (true) {
            if ($month_end > $now) {
                break; // 退出循环
            }
            $start_add_time = date('YmdHis', $month_start);
            $end_add_time   = date('YmdHis', $month_end);
            // 批量更新当前数据 为处理中
            $sql = "update twfx_trace set user_get_flag = '1'  where user_get_flag = '0' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";
            $rs  = M()->execute($sql);

            if ($rs === false) {
                $this->_log("批量更新twfx_trace表失败 error" . M()->_sql());

                return false;
            } else if ($rs == 0) {
                $this->_log("没有要处理的数据 no data" . M()->_sql());
                $month_start = $month_end;
                $month_end   = $month_end + 3600 * 24 * date('t', $month_end); // 月底时间
                continue;
            }
            $where = "user_get_flag = '1' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";
            $rs    = M()->table('twfx_trace')->where($where)->field('SUM(bonus_amount) as bonus_amount_sum, SUM(amount) as amount_sum')->find();
            if (!$rs) {
                $this->_log("统计twfx_trace表失败 " . M()->_sql());

                return false;
            }
            $wfx_get_trace['node_id']        = $saler_info['node_id'];
            $wfx_get_trace['saler_id']       = $saler_info['id'];
            $wfx_get_trace['bonus_amount']   = $rs['bonus_amount_sum'];
            $wfx_get_trace['sale_amount']    = $rs['amount_sum'];
            $wfx_get_trace['alipay_acount']  = $saler_info['alipay_account'];
            $wfx_get_trace['add_time']       = $add_time;
            $wfx_get_trace['month']          = $month;
            $wfx_get_trace['deal_flag']      = '0';
            $wfx_get_trace['start_get_date'] = date('Ymd', $month_start);
            $wfx_get_trace['end_get_date']   = date('Ymd', $month_end - 1);
            $rs                              = M('twfx_get_trace')->add($wfx_get_trace);
            if ($rs === false) {
                $this->_log("插入twfx_get_trace表失败 " . M()->_sql());

                return $log;
            }
            $wfx_get_trace_id = $rs;
            // 批量更新当前数据 为提领请求已提交
            $sql = "update twfx_trace set user_get_flag = '2' ,user_get_trace_id = " . $wfx_get_trace_id . ", user_get_time = '" . $add_time . "'  where user_get_flag = '1' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";

            $rs = M()->execute($sql);
            if ($rs === false) {
                $this->_log("批量更新twfx_trace表失败  " . M()->_sql());

                return false;
            }
            $month_start = $month_end;
            $month_end   = $month_end + 3600 * 24 * date('t', $month_end); // 月底时间
        }

        return true;
    }

    // 月末自动提领
    public function month_get_bonus() {
        // 遍历所有需要计算的机构
        $where     = "settle_type = '1' ";
        $node_list = M()->table('twfx_node_info')->where($where)->select();
        if ($node_list === false) {
            $this->_log("查询twfx_node_info 失败  " . M()->_sql());

            return false;
        } else if ($node_list == null) {
            $this->_log("没有要处理的数据  " . M()->_sql());

            return false;
        }
        // 开启事务
        M()->startTrans();
        foreach ($node_list as $node_info) {
            $this->_log("处理机构  " . $node_info['node_id']);
            $where      = "node_id = '" . $node_info['node_id'] . "'";
            $saler_list = M()->table('twfx_saler')->where($where)->select();
            if ($saler_list === false) {
                $this->_log("查询twfx_saler 失败 " . M()->_sql());
                M()->rollback();

                return false;
            } else if ($saler_list == null) {
                $this->_log("没有要处理的数据 " . M()->_sql());
                continue;
            }
            // 获取这个机构最后一次提领结算时间
            $where = "node_id = '" . $node_info['node_id'] . "'";
            $rs    = M()->table('twfx_get_trace')->where($where)->field('IFNULL(MAX(end_get_date), 0) as end_get_date')->find();
            if (!$rs) {
                $this->_log("获取这个机构最后一次提领结算时间失败 month_get_bonus " . M()->_sql());
                M()->rollback();

                return false;
            }
            if ($rs['end_get_date'] == 0) {
                $this->_log("获取这个机构最后一次提领结算时间 没有数据 month_get_bonus " . M()->_sql());
                // 如果没有时间，则是第一次运行结算，取这个机构第一次提成发生时间作为开始时间
                $where = "user_get_flag = '0' and  node_id = '" . $node_info['node_id'] . "'";
                $rs    = M()->table('twfx_trace')->where($where)->field('IFNULL(MIN(add_time), 0) as add_time')->find();
                if (!$rs) {
                    $this->_log("获取最早的一个时间失败 month_get_bonus " . M()->_sql());
                    M()->rollback();

                    return false;
                }

                if ($rs['add_time'] == 0) {
                    $this->_log("获取最早的一个时间 没有数据 month_get_bonus " . M()->_sql());
                    continue;
                }
                // 获取第一条数据的第一个月开始时间
                $first_start_time = strtotime(date('Ym01000000', strtotime($rs['add_time'])));
                $first_end_time   = $first_start_time + 3600 * 24 * date('t', $first_start_time); // 月底时间
            } else { // 如果有时间，则以最后一次提领结算时间加一天作为开始结算时间
                $start_time       = strtotime($rs['end_get_date'] . '000000') + 3600 * 24;
                $first_start_time = $start_time;
                $month_start_time = strtotime(date('Ym01000000', strtotime($rs['end_get_date'])));
                $first_end_time   = $month_start_time + 3600 * 24 * date('t', $month_start_time); // 月底时间
            }
            // 遍历所有的销售员
            foreach ($saler_list as $saler_info) {
                $rs = $this->saler_get_bonus_by_month($saler_info, $first_start_time, $first_end_time);
                if ($rs === false) {
                    M()->rollback();
                }
            }
            $this->_log("处理机构  " . $node_info['node_id'] . "处理结束");
        }
        M()->commit(); // 提交事务
        return true;
    }

    // 销售员按周提领
    public function saler_get_bonus_by_week($saler_info, $first_start_time, $first_end_time) {
        $add_time = date("YmdHis");
        $saler_id = $saler_info['id'];
        $this->_log("处理 saler_id:" . $saler_id);
        $month = date("Ym");
        $now   = time();

        $week_start = $first_start_time;
        $week_end   = $first_end_time;

        // 按周为周期进行循环处理
        while (true) {

            if ($week_end > $now) {
                break; // 退出循环
            }
            $start_add_time = date('YmdHis', $week_start);
            $end_add_time   = date('YmdHis', $week_end);
            // 批量更新当前数据 为处理中
            $sql = "update twfx_trace set user_get_flag = '1'  where user_get_flag = '0' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";

            $rs = M()->execute($sql);
            if ($rs === false) {
                $this->_log("批量更新twfx_trace表失败 error" . M()->_sql());

                return false;
            } else if ($rs == 0) {
                $this->_log("没有要处理的数据 no data" . M()->_sql());
                $week_start = $week_end;
                $week_end   = $week_end + 3600 * 24 * 7;
                continue;
            }
            $where = "user_get_flag = '1' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";
            $rs    = M()->table('twfx_trace')->where($where)->field('SUM(bonus_amount) as bonus_amount_sum, SUM(amount) as amount_sum')->find();
            if (!$rs) {
                $this->_log("统计twfx_trace表失败 " . M()->_sql());

                return false;
            }
            $this->_log("统计twfx_trace表失败 " . M()->_sql());
            $wfx_get_trace['node_id']        = $saler_info['node_id'];
            $wfx_get_trace['saler_id']       = $saler_info['id'];
            $wfx_get_trace['bonus_amount']   = $rs['bonus_amount_sum'];
            $wfx_get_trace['sale_amount']    = $rs['amount_sum'];
            $wfx_get_trace['alipay_acount']  = $saler_info['alipay_account'];
            $wfx_get_trace['add_time']       = $add_time;
            $wfx_get_trace['month']          = $month;
            $wfx_get_trace['deal_flag']      = '0';
            $wfx_get_trace['start_get_date'] = date('Ymd', $week_start);
            $wfx_get_trace['end_get_date']   = date('Ymd', $week_end - 1);
            $rs                              = M('twfx_get_trace')->add($wfx_get_trace);
            if ($rs === false) {
                $this->_log("插入twfx_get_trace表失败 " . M()->_sql());

                return $log;
            }
            $wfx_get_trace_id = $rs;
            // 批量更新当前数据 为提领请求已提交
            $sql = "update twfx_trace set user_get_flag = '2' ,user_get_trace_id = " . $wfx_get_trace_id . ", user_get_time = '" . $add_time . "'  where user_get_flag = '1' and  saler_id = " . $saler_id . " and add_time > '" . $start_add_time . "' and add_time < '" . $end_add_time . "'";

            $rs = M()->execute($sql);
            if ($rs === false) {
                $this->_log("批量更新twfx_trace表失败  " . M()->_sql());

                return false;
            }
            $week_start = $week_end;
            $week_end   = $week_end + 3600 * 24 * 7;
        }

        return true;
    }

    // 每周一处理按周提领
    public function week_get_bonus() {
        // 遍历所有需要计算的机构
        $add_time  = date('YmdHis');
        $now       = time();
        $where     = "settle_type = '3' ";
        $node_list = M()->table('twfx_node_info')->where($where)->select();
        if ($node_list === false) {
            $this->_log("查询twfx_node_info 失败  " . M()->_sql());

            return false;
        } else if ($node_list == null) {
            $this->_log("没有要处理的数据  " . M()->_sql());

            return false;
        }
        // 开启事务
        M()->startTrans();
        foreach ($node_list as $node_info) {
            $this->_log("处理机构  " . $node_info['node_id']);
            $where      = "node_id = '" . $node_info['node_id'] . "'  ";
            $saler_list = M()->table('twfx_saler')->where($where)->select();
            if ($saler_list === false) {
                $this->_log("查询twfx_saler 失败 " . M()->_sql());
                M()->rollback();

                return false;
            } else if ($saler_list == null) {
                $this->_log("没有要处理的数据 " . M()->_sql());
                continue;
            }
            // 获取这个机构最后一次提领结算时间
            $where = "node_id = '" . $node_info['node_id'] . "'";
            $rs    = M()->table('twfx_get_trace')->where($where)->field('IFNULL(MAX(end_get_date), 0) as end_get_date')->find();
            if (!$rs) {
                $this->_log("获取这个机构最后一次提领结算时间失败 week_get_bonus " . M()->_sql());
                M()->rollback();

                return false;
            }

            if ($rs['end_get_date'] == 0) {
                $this->_log("获取这个机构最后一次提领结算时间 没有数据 week_get_bonus " . M()->_sql());
                // 如果没有时间，则是第一次运行结算，取这个机构第一次提成发生时间作为开始时间
                $where = "user_get_flag = '0' and  node_id = '" . $node_info['node_id'] . "'";
                $rs    = M()->table('twfx_trace')->where($where)->field('IFNULL(MIN(add_time), 0) as add_time')->find();
                if (!$rs) {
                    $this->_log("获取最早的一个时间失败 week_get_bonus " . M()->_sql());
                    M()->rollback();

                    return false;
                }
                if ($rs['add_time'] == 0) {
                    $this->_log("获取最早的一个时间 没有数据 week_get_bonus " . M()->_sql());
                    continue;
                }
                // 获取第一条数据的第一周开始时间
                $w                = date("N", strtotime($rs['add_time'])) - 1;
                $first_start_time = strtotime(date('Ymd000000', strtotime($rs['add_time']))) - 3600 * 24 * $w;
                $first_end_time   = $first_start_time + 3600 * 24 * 7;
            } else { // 如果有时间，则以最后一次提领结算时间加一天作为开始结算时间
                $start_time       = strtotime($rs['end_get_date'] . '000000') + 3600 * 24;
                $w                = 7 - (date("N", $start_time) - 1);
                $first_start_time = $start_time;
                $first_end_time   = strtotime(date('Ymd000000', $first_start_time + 3600 * 24 * $w));
            }
            // 非法时间
            if ($first_start_time > $now) {
                $this->_log("非法时间 " . $first_start_time . $first_end_time);
                continue;
            }
            // 遍历所有的销售员
            foreach ($saler_list as $saler_info) {
                $rs = $this->saler_get_bonus_by_week($saler_info, $first_start_time, $first_end_time);
                if ($rs === false) {
                    M()->rollback();
                }
            }

            // 遍历时间区段，没有的话插一条0的数据
            $week_start = $first_start_time;
            $week_end   = $first_end_time;

            while (true) {
                if ($week_end > $now) {
                    break; // 退出循环
                }
                // 查找是否有统计记录，没有的话插入一条
                $where = "node_id = '" . $node_id . "' and start_get_date = '" . date('Ymd',
                                $week_start) . "' and end_get_date = '" . date('Ymd', $week_end - 1) . "'";
                $rs    = M()->table('twfx_get_trace')->where($where)->find();
                if (!$rs) {
                    $wfx_get_trace['node_id']        = $saler_info['node_id'];
                    $wfx_get_trace['saler_id']       = $saler_info['id'];
                    $wfx_get_trace['bonus_amount']   = 0;
                    $wfx_get_trace['sale_amount']    = 0;
                    $wfx_get_trace['alipay_acount']  = '';
                    $wfx_get_trace['add_time']       = $add_time;
                    $wfx_get_trace['month']          = date('Ym');
                    $wfx_get_trace['deal_flag']      = '0';
                    $wfx_get_trace['start_get_date'] = date('Ymd', $week_start);
                    $wfx_get_trace['end_get_date']   = date('Ymd', $week_end - 1);
                    $rs                              = M('twfx_get_trace')->add($wfx_get_trace);
                    if ($rs === false) {
                        $this->_log("插入twfx_get_trace表失败 " . M()->_sql());
                    }
                }
                $week_start = $week_end;
                $week_end   = $week_end + 3600 * 24 * 7;
            }
            $this->_log("处理机构  " . $node_info['node_id'] . "处理结束");
        }
        M()->commit(); // 提交事务
        return true;
    }

    // 商户小店个人中心 短链处理
    public function short_url_gen() {
        $where = "shop_short_url is null";
        $list  = M()->table('twfx_node_info')->where($where)->select();
        if ($list) {
            foreach ($list as $value) {
                $shop_url      = 'http://test.wangcaio2o.com/index.php?g=Label&m=MyOrder&a=index&node_id=' . $value['node_id'];
                $RemoteRequest = D('RemoteRequest', 'Service');
                $arr           = array(
                        'CreateShortUrlReq' => array(
                                'SystemID'      => C('ISS_SYSTEM_ID'),
                                'TransactionID' => time() . rand(10000, 99999),
                                'OriginUrl'     => "<![CDATA[$shop_url]]>",
                        ),
                );
                $short_url_arr = $RemoteRequest->GetShortUrl($arr);
                if ($short_url_arr['Status']['StatusCode'] !== '0000') {
                } else {
                    $short_url = $short_url_arr['ShortUrl'];
                }
                // 保存
                $where                 = "node_id = '" . $value['node_id'] . "'";
                $arr['shop_url']       = $shop_url;
                $arr['shop_short_url'] = $short_url;
                M()->table('twfx_node_info')->where($where)->save($arr);
            }
        }
    }

    // 日通知生成
    public function notify_gen($date) {
        $this->short_url_gen();
        $this->_log("开始处理提成日通知统计生成" . M()->_sql());
        $add_time = date("YmdHis");
        // 获取短信模板
        $where      = "param_name = 'BONUS_NOTIFY_NOTE'";
        $param_info = M()->table('tsystem_param t')->where($where)->find();
        if (!$param_info) {
            $this->_log("取短信模板失败 " . M()->_sql());

            return false;
        }
        $content = $param_info['param_value'];
        // #NAME#,#DATE#您获得了#BONUS_AMOUNT#元销售提成，访问http://t.ddfij.com 查看全部提成。

        // 插入通知数据
        $sql = "INSERT INTO twfx_notify (trans_date, name, phone_no, bonus_count, bonus_amount, add_time, status, node_id, short_url) SELECT '" . $date . "', s.`name`, s.`phone_no`, COUNT(*), SUM(t.`bonus_amount`),  '" . $add_time . "', '0' , t.node_id, n.shop_short_url
		 from	twfx_trace t LEFT JOIN twfx_saler s ON t.`saler_id` = s.`id`  LEFT JOIN twfx_node_info n ON t.node_id = n.node_id
			WHERE t.add_time like '" . $date . "%'  and n.charge_notice_flag = '1' GROUP BY t.node_id , t.saler_id";
        $rs  = M()->execute($sql);
        if ($rs === false) {
            $this->_log("插入twfx_notify表失败 " . M()->_sql());

            return false;
        }
        $log .= "插入twfx_notify表" . $rs . "条记录 sql[" . M()->_sql() . "]";
        // 检测是否有需要处理的数据
        $where = "status = '0' and trans_date ='" . $date . "'";
        $list  = M()->table('twfx_notify t')->where($where)->select();
        if ($list === false) {
            $this->_log("查询twfx_notify 失败 " . M()->_sql());

            return false;
        } else if ($list == null) {
            $this->_log("没有要处理的数据" . M()->_sql());

            return false;
        }
        foreach ($list as $value) {
            // 获取活动号
            $where      = "status = '0' and batch_no is not null and node_id = '" . $value['node_id'] . "'";
            $goods_info = M()->table('tgoods_info t')->order("id")->limit(1)->where($where)->find();
            if (!$goods_info) {
                $this->_log("取活动号失败 " . M()->_sql());
                continue;
            }
            // #NAME#,#DATE#您获得了#BONUS_AMOUNT#元销售提成，访问http://t.ddfij.com 查看全部提成。
            // 处理文本
            $content = $param_info['param_value'];
            $content = str_replace("#NAME#", $value['name'], $content);
            $content = str_replace("#DATE#", date("Y年m月d日", strtotime($date)), $content);
            $content = str_replace("#BONUS_AMOUNT#", $value['bonus_amount'], $content);
            $content = str_replace("#SHORT_URL#", $value['short_url'], $content);
            // 发送支撑
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            // $ret =
            // $this->send_notify($value['node_id'],
            // $goods_info['batch_no'],
            // $value['phone_no'],
            // $content,
            // $TransactionID);
            // 更新处理结果
            $trace_update              = array();
            $trace_update['notes']     = $content;
            $trace_update['send_time'] = date("YmdHis");
            $trace_update['send_seq']  = $TransactionID;
            if ($ret) {
                $trace_update['status'] = '1';
            } else {
                $trace_update['status'] = '2';
            }
            $where = "id = " . $value['id'];
            $rs    = M()->table('twfx_notify')->where($where)->save($trace_update);
            if (!$rs) {
                $this->_log("更新twfx_notify失败" . M()->_sql());

                return false;
            }
        }
        $this->_log("交易处理成功" . M()->_sql());
    }

    private function send_notify(
            $node_id,
            $batch_no,
            $phoneNo,
            $text,
            $TransactionID
    ) {
        return true;
        // 通知支撑
        // 请求参数
        $req_array     = array(
                'NotifyReq' => array(
                        'TransactionID' => $TransactionID,
                        'ISSPID'        => $node_id,
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'SendLevel'     => '1',
                        'Recipients'    => array(
                                'Number' => $phoneNo,
                        ),  // 手机号
                        'SendClass'     => 'SMS',
                        'MessageText'   => $text,  // 短信内容
                        'Subject'       => '',
                        'ActivityID'    => $batch_no,
                        'ChannelID'     => '',
                        'ExtentCode'    => '',
                ),
        );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array    = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            return false;
        }

        return true;
    }

    private function weixin_notify($saler_info, $wfx_trace, $url) {
        // 没有绑定open_id 退出
        if ($saler_info['open_id'] == null || $saler_info['open_id'] == '') {
            return false;
        }

        // 计算提成总额
        $where = 'saler_id = ' . $saler_info['id'];
        $rs    = M()->table('twfx_trace')->where($where)->field('SUM(bonus_amount) as bonus_amount_sum')->find();
        if (!$rs) {
            $this->_log("统计twfx_trace表失败 " . M()->_sql());

            return false;
        }

        // create data
        $data_array['keyword1']['value'] = $saler_info['name']; // 姓名
        $data_array['keyword2']['value'] = $saler_info['phone_no']; // 帐号
        $data_array['keyword3']['value'] = round($wfx_trace['bonus_amount'], 2) . "元"; // 提成金额
        $data_array['keyword4']['value'] = $rs['bonus_amount_sum'] . "元"; // 提成总额 ？TODO
        $data_array['keyword5']['value'] = date('Y-m-d H:i', strtotime($wfx_trace['add_time'])); // 时间

        $data_array['keyword1']['color'] = '#173177';
        $data_array['keyword2']['color'] = '#173177';
        $data_array['keyword3']['color'] = '#173177';
        $data_array['keyword4']['color'] = '#173177';
        $data_array['keyword5']['color'] = '#173177';

        $this->_weixin_notify($saler_info['open_id'], $saler_info['node_id'], $url, $data_array);
    }

    /*
     * 发送微信通知
     */
    private function _weixin_notify($open_id, $node_id, $url, $data_array) {
        $weixinSendService = D('WeiXinSend', 'Service');
        $weixinSendService->init($node_id);
        $weixinSendService->templateSend($open_id, $node_id, '1', $url, $data_array);
    }

    public function _log($msg, $level = Log::INFO) {
        Log::write($msg, $level);
    }

    /**
     *
     * @param string $url
     *
     * @return string
     */
    function createShortUrl($url) {
        $apiUrl  = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
                'CreateShortUrlReq' => array(
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'TransactionID' => time() . rand(10000, 99999),
                        'OriginUrl'     => "<![CDATA[$url]]>",
                ),
        );

        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($req_arr, 'gbk');
        $error      = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;

            return '';
        }

        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();

        return $arr['ShortUrl'];
    }

    /**
     * @param $memberInfo      array 当前会员信息
     * @param $memberCardTime  string 会员卡变更时间
     *
     * @return array $result array('unreadCount'=>int, 'length'=>int)
     */
    function getUnreadWfxMsg($memberInfo, $memberCardTime) {
        $result = array();
        //在旺分销中的未读消息
        if ($_SESSION['twfxRole'] == '1') {
            $sql        = "SELECT count(*) FROM twfx_msg WHERE status in ('2','4') AND node_id = '" . $_SESSION['node_id'] . "' AND (reader in ('1','3') OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%'))";
            $assigntSql = "SELECT count(*) FROM twfx_msg WHERE status = '4' AND node_id = '" . $_SESSION['node_id'] . "' AND (reader in ('1','3') OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%'))";
        } elseif ($_SESSION['twfxRole'] == '2') {
            $sql        = "SELECT count(*) FROM twfx_msg WHERE status in ('2','4') AND node_id = '" . $_SESSION['node_id'] . "' AND (reader in ('1','2') OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%'))";
            $assigntSql = "SELECT count(*) FROM twfx_msg WHERE status = '4' AND node_id = '" . $_SESSION['node_id'] . "' AND (reader in ('1','2') OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%'))";
        } else {
            $sql        = "SELECT count(*) FROM twfx_msg WHERE status in('2','4') AND node_id = '{$_SESSION['node_id']}' AND (reader = 1 OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%')) ";
            $assigntSql = "SELECT count(*) FROM twfx_msg WHERE status in('4') AND node_id = '{$_SESSION['node_id']}' AND (reader = 1 OR (reader = '4' AND reader_list LIKE '%" . $_SESSION['twfxSalerID'] . ",%')) ";
        }
        $newsMsgCount       = M()->query($sql);
        $assistantNumber    = M()->query($assigntSql);
        $readNewsCount      = M('twfx_msg_read_list')->where(array('saler_id' => $_SESSION['twfxSalerID']))->count();
        $delReadCount       = M('twfx_msg_read_list')->where(array(
                'saler_id'   => $_SESSION['twfxSalerID'],
                'msg_status' => '4',
        ))->count();
        $unReadNewsMsgCount = $newsMsgCount[0]['count(*)'] - $assistantNumber[0]['count(*)'] - $readNewsCount + $delReadCount;

        //在商户群发消息中未读的消息数量
        $sql = " SELECT count(*) FROM tmember_msg a LEFT JOIN tmember_msg_list b ON a.id = b.msg_id ";
        $sql .= " WHERE a.node_id = '{$_SESSION['node_id']}' AND a.msg_type = 1 AND a.reader = 2 AND FIND_IN_SET({$memberInfo['card_id']},a.reader_list) AND b.member_id = '{$memberInfo['id']}' AND a.add_time>={$memberCardTime}";
        //已读
        $isRead = M()->query($sql);
        //可读总数
        $sql = " SELECT count(*) FROM tmember_msg ";
        $sql .= " WHERE node_id = '{$_SESSION['node_id']}' AND msg_type = 1 AND reader = 2 AND FIND_IN_SET({$memberInfo['card_id']},reader_list)  AND add_time>={$memberCardTime}";
        $allRead = M()->query($sql);

        //商户发布后但尚未被会员读取的消息
        $sql = "SELECT count(*) FROM tmember_msg a LEFT JOIN tmember_msg_list b ON a.id = b.msg_id ";
        $sql .= " WHERE a.node_id = '{$_SESSION['node_id']}' AND a.msg_type = 1 AND a.reader = 2 AND a.`status` = 4 AND FIND_IN_SET({$memberInfo['card_id']}, a.reader_list) AND ISNULL(b.id) AND a.add_time>={$memberCardTime}";
        $unReadDel         = M()->query($sql);
        $unReadMassMessage = $allRead[0]['count(*)'] - $isRead[0]['count(*)'] - $unReadDel[0]['count(*)'];

        //在会员自身触发的未读消息数量   直接拿
        $sql = " SELECT count(*) FROM tmember_msg_list ";
        $sql .= " WHERE msg_id = 0 AND member_id = '{$memberInfo['id']}' AND msg_status = 1";
        $unRead               = M()->query($sql);
        $unReadPassiveMessage = $unRead['0']['count(*)'];

        //合计
        $unReadNewsMsgCount = $unReadNewsMsgCount + $unReadMassMessage + $unReadPassiveMessage;

        $unReadNewsMsgCountLength = strlen($unReadNewsMsgCount);
        if ($unReadNewsMsgCountLength > 1) {
            $newsReadNumber = $unReadNewsMsgCountLength * 7;
        } else {
            $newsReadNumber = 10;
        }
        $result['unreadCount'] = $unReadNewsMsgCount;
        $result['length']      = $newsReadNumber;

        return $result;
    }
}