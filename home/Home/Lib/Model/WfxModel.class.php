<?php

/**
 * author wangsong email skyshappiness@gmail.com
 */
class WfxModel extends Model {
    protected $tableName = '__NONE__';
    /**
     *
     * @param int $count 总数
     * @param int $pageSize 页面条数
     * @param int $pageStart 起始位置
     * @return array
     */
    function getSellingGoods($count, $pageSize, $pageStart = 0) {
        import('ORG.Util.Page');
        $Page = new Page($count, $pageSize);
        $search['m.batch_type'] = array(
            'in', 
            '26,27,31');
        $search['m.node_id'] = $_SESSION['node_id'];
        $search['m.end_time'] = array(
            'egt', 
            date('YmdHis'));
        $search['m.status'] = '1';
        $search['w.bonus_flag'] = '1';
        $search['b.status'] = '0';
        
        $goods = M()->table("tmarketing_info m")->field(
            "m.id AS m_id,m.is_new,m.batch_type AS batch_type,m.name AS name,m.status AS m_status, m.group_price, b.status AS batch_status, b.batch_no, b.batch_amt, b.goods_id, tgi.goods_amt, tgi.is_sku, tgi.goods_image")
            ->join("twfx_goods_config w on w.m_id = m.id")
            ->join('tbatch_info b ON b.m_id = m.id')
            ->join('tgoods_info tgi ON tgi.goods_id = b.goods_id')
            ->where($search)
            ->order('m.status asc,b.status asc,m.end_time desc')
            ->limit($pageStart . ',' . $pageSize)
            ->select();
        return $goods;
    }

    /**
     * 获取所有分销商品
     *
     * @param string $searchNameWords 商品名称
     */
    function getAllSellingGoods($searchNameWords = '') {
        $search['m.batch_type'] = array(
            'in', 
            '26,27,31');
        $search['m.node_id'] = $_SESSION['node_id'];
        $search['m.end_time'] = array(
            'egt', 
            date('YmdHis'));
        $search['m.status'] = '1';
        $search['w.bonus_flag'] = '1';
        $search['b.status'] = '0';
        if ($searchNameWords != '') {
            $search['m.name'] = array(
                'like', 
                '%' . $searchNameWords . '%');
        }
        
        $goods = M()->table("tmarketing_info m")->field(
            "m.id AS m_id,m.is_new,m.batch_type AS batch_type,m.name AS name,m.status AS m_status, m.group_price, b.status AS batch_status, b.batch_no, b.batch_amt, b.goods_id, tgi.goods_amt, tgi.is_sku, tgi.goods_image")
            ->join("twfx_goods_config w on w.m_id = m.id")
            ->join('tbatch_info b ON b.m_id = m.id')
            ->join('tgoods_info tgi ON tgi.goods_id = b.goods_id')
            ->where($search)
            ->order('m.status asc,b.status asc,m.end_time desc')
            ->select();
        return $goods;
    }

    /**
     *
     * @param string $condition 活动类型
     * @return int
     */
    function getSellingGoodsCount($condition = '26,27,31') {
        $search['m.batch_type'] = array(
            'in', 
            $condition);
        $search['m.node_id'] = $_SESSION['node_id'];
        $search['m.end_time'] = array(
            'egt', 
            date('YmdHis'));
        $search['m.status'] = '1';
        $search['w.bonus_flag'] = '1';
        $search['b.status'] = '0';
        
        $goodsCount = M()->table("tmarketing_info m")->join(
            "twfx_goods_config w on w.m_id = m.id")
            ->join('tbatch_info b ON b.m_id = m.id')
            ->where($search)
            ->count();
        return $goodsCount;
    }

    /**
     *
     * @param string $goodsId tmarketing_info 表 id
     * @param string $type 小店商品 31 或者 其他
     * @return array
     */
    function getOneSellingGoodInfo($goodsId, $type = '') {
        $marketingInfoModel = M('tmarketing_info');
        if ($type == '') {
            $type = $marketingInfoModel->where(
                array(
                    'id' => $goodsId))->getfield('batch_type');
        }
        switch ($type) {
            case '31': // 小店商品
                $goodsInfo = $marketingInfoModel->join(
                    'tbatch_info ON tbatch_info.m_id = tmarketing_info.id')
                    ->join(
                    'tgoods_info ON tgoods_info.goods_id = tbatch_info.goods_id')
                    ->where(
                    array(
                        'tmarketing_info.id' => $goodsId))
                    ->field(
                    'tmarketing_info.id, tmarketing_info.batch_type, tbatch_info.goods_id, tmarketing_info.name, tbatch_info.batch_amt as group_price, tgoods_info.goods_image as goods_img, tgoods_info.is_sku, tbatch_info.storage_num, tbatch_info.remain_num, tgoods_info.storage_type,tgoods_info.goods_id')
                    ->find();
                break;
            default: // 闪购 或者 码上买
                $goodsInfo = $marketingInfoModel->where(
                    array(
                        'tmarketing_info.id' => $goodsId))
                    ->join(
                    'tbatch_info ON tbatch_info.m_id = tmarketing_info.id')
                    ->join(
                    'tgoods_info ON tgoods_info.goods_id = tbatch_info.goods_id')
                    ->field(
                    'tmarketing_info.id, tbatch_info.goods_id, tmarketing_info.name, tmarketing_info.group_price, tmarketing_info.goods_img, tmarketing_info.batch_type, tgoods_info.is_sku, tbatch_info.storage_num, tbatch_info.remain_num,tgoods_info.storage_type,tgoods_info.goods_id')
                    ->find();
                break;
        }
        return $goodsInfo;
    }

    /**
     *
     * @param string $type 渠道类型
     * @param string $snsType 渠道类型
     * @param string $isWangCaiHouTai 默认小店前台
     * @return string $channelId
     */
    function getChannelId($type = '9', $snsType = '91', $isWangCaiHouTai = false) {
        if ($isWangCaiHouTai) {
            $nodeId = $_SESSION['userSessInfo']['node_id'];
        } else {
            $nodeId = $_SESSION['node_id'];
        }
        $channelId = M('tchannel')->where(
            array(
                'type' => $type, 
                'sns_type' => $snsType, 
                'status' => '1', 
                'node_id' => $nodeId))->getfield('id');
        if (! $channelId) {
            $data['name'] = '旺分销默认渠道';
            $data['type'] = '9';
            $data['sns_type'] = '91';
            $data['status'] = '1';
            $data['node_id'] = $nodeId;
            $data['add_time'] = date('YmdHis');
            
            $channelId = M('tchannel')->data($data)->add();
        }
        return $channelId;
    }

    /**
     *
     * @param string $batchType 活动类型
     * @return int
     */
    function getTmarketingInfoId($batchType = '29') {
        $condition = array();
        $condition['batch_type'] = $batchType;
        $condition['node_id'] = $_SESSION['node_id'];
        $marketingInfoId = M('tmarketing_info')->where($condition)->getField(
            'id');
        return $marketingInfoId;
    }

    /**
     *
     * @param string $nodeId 机构号
     * @param string $phone 手机号
     * @return int
     */
    function getSalerId($nodeId, $phone) {
        $condition = array();
        $condition['node_id'] = $nodeId;
        $condition['phone_no'] = $phone;
        $wfxSalerModel = M('twfx_saler');
        $salerID = $wfxSalerModel->where($condition)->getfield('id');
        return $salerID;
    }

    /**
     *
     * @param string $nodeId
     * @return array
     */
    function getWechatConfig($nodeId) {
        if ($nodeId == '') {
            $nodeId = $_SESSION['node_id'];
        }
        $wechatConfig = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))
            ->field('app_id, app_secret')
            ->find();
        return $wechatConfig;
    }

    /**
     *
     * @param array $condition 查询条件
     * @return array
     */
    function getTotalBonus($condition) {
        $wfxTraceModel = M('twfx_trace');
        $totalCountArray = $wfxTraceModel->join(
            'twfx_saler ON twfx_trace.saler_id = twfx_saler.id')
            ->where($condition)
            ->field(
            'SUM(twfx_trace.bonus_amount) as bonus_amount, SUM(twfx_trace.amount) as amount, twfx_saler.alipay_account')
            ->select();
        return $totalCountArray;
    }

    /**
     *
     * @param array $searchCondition 查询条件
     * @param array $addData 新增数组
     * @return array
     */
    function saveGetBonusAction($searchCondition, $addData) {
        $tranDb = new Model();
        $tranDb->startTrans();
        
        $wfxGetTraceModel = M('twfx_get_trace');
        $saveSuccess = $wfxGetTraceModel->add($addData);
        if ($saveSuccess) {
            $wfxTraceModel = M('twfx_trace');
            $wfxTraceModel->where($searchCondition)->save(
                array(
                    'user_get_flag' => '1', 
                    'user_get_trace_id' => $saveSuccess, 
                    'user_get_time' => date('YmdHis')));
            $tranDb->commit();
            $result['error'] = '0';
            $result['msg'] = '提领成功！';
        } else {
            $tranDb->rollback();
            $result['error'] = '99999';
            $result['msg'] = '无法对数据库进行操作！请尝试刷新页面重试';
        }
        return $result;
    }

    function getBelongAgency($nodeId, $whoes = false) {
        $needLevel = $whoes ? 5 : 4;
        $agencyLevelInfo = M('twfx_saler')->field(
            'id,parent_id,phone_no,level,parent_path,name')
            ->where(
            array(
                'role' => '2', 
                'node_id' => $nodeId, 
                'status' => '3', 
                'level' => array(
                    'ELT', 
                    $needLevel)))
            ->select();
        return $agencyLevelInfo;
    }

    /**
     *
     * @param string $nodeId 机构号
     * @return string
     */
    function getShopName($nodeId) {
        $marketingInfo = M('tmarketing_info');
        $shopName = $marketingInfo->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => '29'))
            ->order('id desc')
            ->getfield('name');
        return $shopName;
    }

    /**
     *
     * @param string $nodeId 机构号
     * @return string
     */
    function getShopLogo($nodeId) {
        $marketingInfo = M('tmarketing_info');
        $mid = $marketingInfo->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => '29'))
            ->order('id desc')
            ->getfield('id');
        
        $ecshopBannerModel = M('tecshop_banner');
        $logoInfo = $ecshopBannerModel->where(
            array(
                'node_id' => $nodeId, 
                'm_id' => $mid, 
                'ban_type' => 1))
            ->order("id desc")
            ->getfield('img_url');
        
        return $logoInfo;
    }

    /**
     *
     * @param string $nodeId 机构号
     * @return string
     */
    function getShopShareDesc($nodeId) {
        $marketingInfo = M('tmarketing_info');
        $mid = $marketingInfo->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => '29'))
            ->order('id desc')
            ->getfield('id');
        
        $ecshopBannerModel = M('tecshop_banner');
        $descInfo = $ecshopBannerModel->where(
            array(
                'node_id' => $nodeId, 
                'm_id' => $mid, 
                'ban_type' => 3))
            ->order("id desc")
            ->getfield('memo');
        return $descInfo;
    }

    /**
     * [getMsgList 获取旺财小店消息]
     *
     * @param [type] $condition [条件]
     * @param boolean $limit [数量]
     * @return [type] [结果]
     */
    function getMsgList($condition, $limit = false) {
        if (! $limit) {
            $list = M()->table('twfx_msg m')
                ->field('m.*,u.true_name')
                ->join("tuser_info u on u.user_id=m.user_id")
                ->where($condition)
                ->order("m.add_time desc")
                ->select();
        } else {
            $list = M()->table('twfx_msg m')
                ->field('m.*,u.true_name')
                ->join("tuser_info u on u.user_id=m.user_id")
                ->where($condition)
                ->order("m.add_time desc")
                ->limit($limit)
                ->select();
            if (! empty($list)) {
                foreach ($list as $key => $value) {
                    $list[$key]['add_time'] = date('Y-m-d H:i:s', 
                        strtotime($value['add_time']));
                    switch ($value['reader']) {
                        case '1':
                            $list[$key]['reader_list'] = "所有人";
                            break;
                        case '2':
                            $list[$key]['reader_list'] = "经销商";
                            break;
                        case '3':
                            $list[$key]['reader_list'] = "销售员";
                            break;
                        case '4':
                            // 预留
                            $salerIdArr = explode(',', $value['reader_list']);
                            $list[$key]['reader_list'] = "";
                            if (! empty($salerIdArr)) {
                                foreach ($salerIdArr as $k => $v) {
                                    $list[$key]['reader_list'] .= M(
                                        'twfx_saler')->getFieldById($v, 'name');
                                    $list[$key]['reader_list'] .= "<br/>";
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * [channelIsEmpty 检查当前商户旺分销是否创建了渠道]
     *
     * @param [type] $nodeId [商户]
     * @return [type] [渠道id]
     */
    function channelIsEmpty($nodeId) {
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'type' => 1, 
                'sns_type' => 102, 
                'status' => 1))->find();
        if (empty($channelInfo)) {
            $channelArr = array(
                'name' => '旺分销-群发消息', 
                'type' => 1, 
                'sns_type' => 102, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $nodeId);
            $channelId = M('tchannel')->add($channelArr) or
                 throw_exception('渠道创建有误');
        } else {
            $channelId = $channelInfo['id'];
        }
        return $channelId;
    }

    /**
     * [bindBatchChannel 活动与渠道绑定]
     *
     * @param [type] $nodeId [商户nodeid]
     * @param [type] $m_id [活动id]
     * @param [type] $channelId [渠道id]
     * @return [type] [无]
     */
    function bindBatchChannel($nodeId, $m_id, $channelId) {
        $marketingInfo = M('tmarketing_info')->getById($m_id);
        $batchChannelArr = array(
            'batch_id' => $m_id, 
            'batch_type' => $marketingInfo['batch_type'], 
            'channel_id' => $channelId, 
            'add_time' => date('YmdHis'), 
            'node_id' => $nodeId, 
            'status' => 1);
        $batchChannelId = M('tbatch_channel')->add($batchChannelArr) or
             throw_exception('渠道发布有误');
    }

    /**
     * 获得经销商数据
     *
     * @param $nodeId
     * @param $filterStr sql字段
     */
    function getAgencyExportData($nodeId, $filterStr) {
        if (strrchr($filterStr, 'sale_down') != false) {
            $filterStr = str_replace("sale_down", "parent_path as sale_down", 
                $filterStr);
        }
        ;
        $whereMap['node_id'] = $nodeId; // nodeID
        $whereMap['role'] = 2; // 经销商
        $saler = M('twfx_saler');
        $dataArr = $saler->field($filterStr)
            ->where($whereMap)
            ->select(); // 查询结果集
        foreach ($dataArr as $k => &$v) {
            if ($v['parent_path'] != '' || $v['sale_down'] != '') {
                if ($v['parent_path'] != '') {
                    $v['parent_path'] = $v['parent_path'] . $v['id'];
                    $map['parent_path'] = array(
                        'like', 
                        $v['parent_path'] . "%");
                    $map['role'] = 2;
                    $map['node_id'] = $nodeId;
                    $upStore = $saler->where($map)->getField('name', true); // 下级经销商
                    $str = implode(',', $upStore);
                    $v['parent_down'] = $str;
                    $v['parent_path'] = $v['parent_down'];
                    unset($v['parent_down']);
                }
                if ($v['sale_down'] != '') {
                    $tmp_path = $v['sale_down'] . $v['id'];
                    $map['parent_path'] = array(
                        'like', 
                        $tmp_path . "%");
                    $map['role'] = 1;
                    $map['node_id'] = $nodeId;
                    $upStore = $saler->where($map)->getField('name', true); // 下级销售
                    $str = implode(',', $upStore);
                    $v['sale_down'] = $str;
                }
            }
            if ($v['parent_id'] != '') {
                if ($v['parent_id'] > 0) { // 上级经销商
                    $tempmap['id'] = $v['parent_id'];
                    $tempmap['node_id'] = $nodeId;
                    $v['parent_id'] = $saler->field('name')
                        ->where($tempmap)
                        ->getField('name');
                } else {
                    $v['parent_id'] = "无";
                }
            }
            if ($v['status'] == 1) { // 是否审核
                $v['status'] = "未审核";
            } elseif ($v['status'] == 2) {
                $v['status'] = "审核不通过";
            } elseif ($v['status'] == 3) {
                $v['status'] = "审核通过";
            } elseif ($v['status'] == 4) {
                $v['status'] = "停用";
            }
            if ($v['add_time'] != '') { // 格式化时间
                $v['add_time'] = date("Y-m-d", strtotime($v['add_time']));
            }
        }
        return $dataArr;
    }

    /**
     * 获得销售员数据
     *
     * @param $nodeId
     * @param $filterStr sql字段
     * @return array
     */
    function getSaleExportData($nodeId, $filterStr) {
        define("PC", 1); // 常亮定义 1=》PC端
        define("PHONE", 2); // 2=》手机端
        define("RECRUIT", 3); // 3=>招募
                              
        // 审核相关常量
        define("NOT_AUDIT", 1);
        define("NOT_PASS", 2);
        define("VERIFIED ", 3);
        define("BLOCK", 4);
        
        // 性别常量
        define("MEN", 1);
        define("WOMEN", 2);
        
        if (strchr($filterStr, 'customer_number') != false) {
            $customer_flag = 1;
            $filterStr = str_replace("customer_number", "", $filterStr);
        }
        if (strrchr($filterStr, ',,,') != false) {
            $filterStr = str_replace(",,,", ",", $filterStr);
        }
        if (strrchr($filterStr, ',,') != false) {
            $filterStr = str_replace(",,", ",", $filterStr);
        }
        $filterStr = rtrim($filterStr, ',');
        $filterStr = $filterStr . ",id";
        $whereMap['node_id'] = $nodeId; // nodeID
        $whereMap['role'] = 1; // 销售
        $saler = M('twfx_saler');
        $dataArr = $saler->field($filterStr)
            ->where($whereMap)
            ->select(); // 查询结果集
        foreach ($dataArr as $k => &$v) {
            if ($v['parent_id'] != '') {
                // 上级经销商
                if ($v['parent_id'] > 0) {
                    $tempmap['id'] = $v['parent_id'];
                    $tempmap['node_id'] = $nodeId;
                    $v['parent_id'] = $saler->field('name')
                        ->where($tempmap)
                        ->getField('name');
                } else {
                    $v['parent_id'] = "无";
                }
            }
            // 是否审核
            if ($v['status'] == NOT_AUDIT) {
                $v['status'] = "未审核";
            } elseif ($v['status'] == NOT_PASS) {
                $v['status'] = "审核不通过";
            } elseif ($v['status'] == VERIFIED) {
                $v['status'] = "审核通过";
            } elseif ($v['status'] == BLOCK) {
                $v['status'] = "停用";
            }
            // 绑定客户数
            if ($customer_flag == 1) {
                $v['customer_number'] = M('twfx_customer_relation')->where(
                    array(
                        'saler_id' => $v['id']))->count();
            }
            // 添加时间格式修改
            if ($v['add_time'] != '') {
                $v['add_time'] = date("Y-m-d", strtotime($v['add_time']));
            }
            // 渠道
            if ($v['channel_id'] != '') {
                $v['channel_id'] = M('tchannel')->where(
                    array(
                        'id' => $v['channel_id']))->getField('name');
            }
            // 来源
            
            if ($v['add_from'] == PC) {
                $v['add_from'] = "PC端";
            } elseif ($v['add_from'] == PHONE) {
                $v['add_from'] = "手机端";
            } elseif ($v['add_from'] == RECRUIT) {
                $v['add_from'] = "招募";
            }
            
            // 性别
            if ($v['sex'] == MEN) {
                $v['sex'] = "男";
            } else if ($v['sex'] == WOMEN) {
                $v['sex'] = "女";
            }
            // 推荐人
            if ($v['referee_id'] != '') {
                $tmp['id'] = $v['referee_id'];
                $v['referee_id'] = $saler->field('name')
                    ->where($tmp)
                    ->getField('name');
            }
            // 城市
            if ($v['area'] != '') {
                $citydatap['province_code'] = substr($v['area'], 0, 2);
                $citydatap['city_code'] = substr($v['area'], 2, 3);
                $citydatap['town_code'] = substr($v['area'], 5, 3);
                $city = M('tcity_code_l4')->where($citydatap)
                    ->limit(1)
                    ->find();
                $v['area'] = $city['province'] . "-" . $city['city'] . "-" .
                     $city['town'];
            }
            unset($v['id']);
        }
        
        return $dataArr;
    }

    /**
     * 通过phone和nodeID获得下级经销商or销售员的数据
     *
     * @param $nodeId
     * @param $role 2=>经销商1=>数据
     * @param $phone 电话
     * @return array
     */
    function getAgencyData($nodeId, $role, $phone) {
        $saler = M('twfx_saler');
        if ($phone == '') {
            $dataArr = $saler->where(
                array(
                    'node_id' => $nodeId, 
                    'status' => '3'))->find();
        }
        $dataArr = $saler->where(
            array(
                'node_id' => $nodeId, 
                'status' => '3', 
                'phone_no' => $phone))->find();
        $parent_path = $dataArr['parent_path'] . $dataArr['id'];
        // 通过parent_path获得下级的数据
        $map['parent_path'] = array(
            'like', 
            $parent_path . "%");
        $map['role'] = $role;
        $map['node_id'] = $nodeId;
        $map['status'] = 3;
        $upStore = $saler->where($map)->getField('id,name,phone_no'); // 下级经销商or销售
        return $upStore;
    }

    /**
     * [setRecruitDayStat 设置招募流量统计]
     *
     * @param [type] $nodeId [机构号]
     * @param [type] $salerId [经销商的id]
     * @param [type] $countInfo [数组，click_count，apply_count，trans_count]
     */
    function setRecruitDayStat($nodeId, $salerId, $countInfo) {
        // 查询旺分销-招募活动
        $mId = M('tmarketing_info')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => 3001))->getField('id');
        if (empty($mId)) {
            throw_exception("旺分销-招募活动不存在");
        }
        // 查询渠道
        $bcId = M('tbatch_channel')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => 3001, 
                'batch_id' => $mId))->getField('id');
        if (empty($bcId)) {
            throw_exception("旺分销-招募活动发布失败");
        }
        if (empty($salerId)) {
            $salerId = 0;
        }
        M()->startTrans();
        // 查询每天的渠道点击量
        $channelDayStatInfo = M('twfx_channel_daystat')->where(
            array(
                'node_id' => $nodeId, 
                'saler_id' => $salerId, 
                'batch_channel_id' => $bcId, 
                'add_time' => date('Ymd')))->find();
        if (empty($channelDayStatInfo)) {
            $data['saler_id'] = $salerId;
            $data['node_id'] = $nodeId;
            $data['batch_channel_id'] = $bcId;
            $data['add_time'] = date('Ymd');
            $data['click_count'] = $countInfo['click_count'] ? $countInfo['click_count'] : 0;
            $data['apply_count'] = $countInfo['apply_count'] ? $countInfo['apply_count'] : 0;
            $data['trans_count'] = $countInfo['trans_count'] ? $countInfo['trans_count'] : 0;
            $dayStatId = M('twfx_channel_daystat')->add($data);
            if (empty($dayStatId)) {
                M()->rollback();
                throw_exception("招募活动流量统计失败");
            }
        } else {
            $data['click_count'] = $channelDayStatInfo['click_count'] +
                 ($countInfo['click_count'] ? $countInfo['click_count'] : 0);
            $data['apply_count'] = $channelDayStatInfo['apply_count'] +
                 ($countInfo['apply_count'] ? $countInfo['apply_count'] : 0);
            $data['trans_count'] = $channelDayStatInfo['trans_count'] +
                 ($countInfo['trans_count'] ? $countInfo['trans_count'] : 0);
            $result = M('twfx_channel_daystat')->where(
                array(
                    'id' => $channelDayStatInfo['id']))->save($data);
            if (false === $result) {
                M()->rollback();
                throw_exception("招募活动流量统计失败");
            }
        }
        M()->commit();
    }

    /**
     * [sendMsgInfo 向手机发送短信]
     *
     * @param [int] $bindPhoneNo [手机号码]
     * @param [string] $text [短信文本]
     */
    public function sendMsgInfo($bindPhoneNo, $text) {
        if (! check_str($bindPhoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            throw_exception("手机号{$error}");
        }
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $reqToService = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $bindPhoneNo),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $respData = $RemoteRequest->requestIssServ($reqToService);
        $respMessage = $respData['NotifyRes']['Status'];
        if (! $respData || ($respMessage['StatusCode'] != '0000' &&
             $respMessage['StatusCode'] != '0001')) {
            throw_exception('发送失败' . $respMessage['StatusText']);
        }
    }

    /**
     * 获取本月第一天与最后一天
     */
    public function getThisMonthFirstDayAndLastDay($thisYear = '', $thisMonth = '') {
        if ($thisYear == "")
            $thisYear = date("Y");
        if ($thisMonth == "")
            $thisMonth = date("m");
        $thisMonth = sprintf("%02d", intval($thisMonth));
        $thisYear = str_pad(intval($thisYear), 4, "0", STR_PAD_RIGHT);
        
        $thisMonth > 12 || $thisMonth < 1 ? $thisMonth = 1 : $thisMonth = $thisMonth;
        $firstday = strtotime($thisYear . $thisMonth . "01000000");
        $firstdaystr = date("Ym01", $firstday);
        $lastday = date('Ymd', strtotime("$firstdaystr +1 month -1 day"));
        return array(
            "firstday" => $firstdaystr, 
            "lastday" => $lastday);
    }

    /**
     * sku商品信息
     *
     * @param type $sku sku信息
     * @param type $marketingInfoId tmarketing_info id
     * @return type
     */
    function getOneSkuGoodsInfo($sku, $marketingInfoId) {
        $goodsInfo = M()->table("tecshop_goods_sku tegs")->join(
            'tgoods_sku_info tgsi ON tegs.skuinfo_id = tgsi.id')
            ->join('tmarketing_info tmi ON tmi.id = tegs.m_id')
            ->join('tbatch_info tbi ON tbi.m_id = tmi.id')
            ->join('tgoods_info tgi ON tgi.goods_id = tbi.goods_id')
            ->field(
            'tegs.id, tegs.sale_price, tegs.remain_num, tegs.storage_num, tegs.b_id, tbi.storage_num as tbi_storage, tgsi.sku_detail_id, tmi.name, tmi.batch_type, tgi.goods_image, tgi.storage_type, tgi.goods_id')
            ->where(
            array(
                'tgsi.sku_detail_id' => $sku, 
                'tegs.m_id' => $marketingInfoId))
            ->find();
        
        return $goodsInfo;
    }

    /**
     * 分销订货订单查询
     *
     * @param type $node_id 机构号
     * @param type $phone 下单手机
     * @return type
     */
    public function getBookOrderList($node_id, $phone = '', $additionalCon) {
        $condition = array();
        $condition['tbo.node_id'] = $node_id;
        if (! empty($additionalCon)) {
            $condition = array_merge($condition, $additionalCon);
        }
        
        if ($phone != '') {
            $condition['tbo.order_phone'] = $phone;
            $orderList = M()->table("twfx_book_order tbo")->join(
                'twfx_book_order_info tboi ON tbo.order_id = tboi.book_order')
                ->join('tcity_code tcc ON tcc.path = tbo.receiver_citycode')
                ->where($condition)
                ->order('tbo.add_time DESC')
                ->field(
                'tbo.order_id, tbo.delivery_status, tboi.sku_desc, tboi.marketing_info_id, tbo.receiver_name, tbo.receiver_addr, tbo.receiver_phone, tbo.add_time, tboi.sku_info, tboi.price, tboi.count, tcc.province, tcc.city, tcc.town')
                ->select();
        } else {
            $orderList = M()->table("twfx_book_order tbo")->join(
                'twfx_saler tws ON tws.node_id = tbo.node_id AND tws.phone_no = tbo.order_phone')
                ->where($condition)
                ->field(
                'tbo.order_id, tbo.delivery_status, tbo.add_time, tbo.receiver_name, tbo.receiver_phone,  tbo.order_phone, tws.name as tws_name')
                ->order('tbo.add_time DESC, tbo.order_phone ASC')
                ->select();
        }
        
        return $orderList;
    }
}
