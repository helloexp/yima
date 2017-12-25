<?php

/**
 *
 * @author lwb Time 20150813
 */
class SelectJpModel extends Model {
    protected $tableName = '__NONE__';
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     *
     * @param int $nodeId
     * @param int $show_source 第一个下拉框传过来值
     * @param int $show_type 第二个下拉框传过来值
     * @param string $goodsName 搜索栏输入的商品名
     * @param int $wxAuthType 微信授权方式 0翼码 1自有
     * @return multitype:Page 分页，multitype: 查询结果
     */
    public function getSourceAndType($nodeId, $show_source, $show_type, 
        $goodsName, $wxAuthType = 0, $wxGoods = 0) {
        $map = array(
            'node_id' => $nodeId, 
            'status' => '0');
        if ($goodsName) {
            $map['goods_name'] = array(
                'like', 
                array(
                    "%{$goodsName}%"));
        }
        $result = array();
        $count = 0;
        import("ORG.Util.Page");
        switch ($show_source) {
            case '0':
                $map['source'] = CommonConst::GOODS_SOURCE_SELF_CREATE;
                if ($show_type === '') {
                    $show_type = array(
                        CommonConst::GOODS_TYPE_YHQ, 
                        CommonConst::GOODS_TYPE_DJQ, 
                        CommonConst::GOODS_TYPE_TLQ, 
                        CommonConst::GOODS_TYPE_ZKQ);
                }
                $map['goods_type'] = array(
                    'in', 
                    $show_type);
                break;
            case '1':
                if ($show_type === '') {
                    $show_type = array(
                        CommonConst::GOODS_TYPE_YHQ, 
                        CommonConst::GOODS_TYPE_DJQ, 
                        CommonConst::GOODS_TYPE_TLQ, 
                        CommonConst::GOODS_TYPE_ZKQ, 
                        CommonConst::GOODS_TYPE_HF, 
                        CommonConst::GOODS_TYPE_QB, 
                        CommonConst::GOODS_TYPE_HGDSDZQ, 
                        CommonConst::GOODS_TYPE_LLB
                    );
                }
                $map['source'] = array(
                    'in', 
                    array(
                        CommonConst::GOODS_SOURCE_BUY, 
                        CommonConst::GOODS_SOURCE_DISTRIBUTION));
                $map['goods_type'] = array(
                    'in', 
                    $show_type);
                break;
            case '2':
                $map['source'] = CommonConst::GOODS_SOURCE_SELF_CREATE;
                $map['goods_type'] = CommonConst::GOODS_TYPE_HB;
                break;
            case '3':
                $goodsSelected = M('tgoods_info')->where(
                    array(
                        'node_id' => $nodeId, 
                        'status' => 0, 
                        'source' => array(
                            'in', 
                            array(
                                CommonConst::GOODS_TYPE_YHQ, 
                                CommonConst::GOODS_TYPE_DJQ, 
                                CommonConst::GOODS_TYPE_TLQ, 
                                CommonConst::GOODS_TYPE_ZKQ))))->select(false);
                $card_map = array(
                    'c.auth_flag' => array(
                        'in', 
                        '1,2'), 
                    'c.node_id' => $nodeId, 
                    'c.store_mode' => 1);
                if ($goodsName) {
                    $card_map['c.title'] = array(
                        'like', 
                        array(
                            "%{$goodsName}%"));
                }
                $count = M('twx_card_type')->alias('c')
                    ->where($card_map)
                    ->join(
                    'right join ' . $goodsSelected .
                         ' g on c.goods_id = g.goods_id')
                    ->count();
                $map['node_id'] = $nodeId;
                if($show_type != ''){
                    if($show_type == '3'){
                        $show_type = '0';
                    }elseif($show_type == '0'){
                        $show_type = '3';
                    }
                    $map['card_type'] = $show_type;
                }
                break;
            case '4':
                //如果活动是用的自有公众号，或者筛选条件是需要微信商品（微信红包和微信卡券），那就只筛选自建微信红包
                $map['source'] = ($wxAuthType || $wxGoods) ? CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB : CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB;
                break;
            case '':
            default:
                $map['source'] = array(
                    'in', 
                    array(
                        CommonConst::GOODS_SOURCE_BUY, 
                        CommonConst::GOODS_SOURCE_DISTRIBUTION, 
                        CommonConst::GOODS_SOURCE_SELF_CREATE));
                if ($show_type === '') {
                    $show_type = array(
                        CommonConst::GOODS_TYPE_YHQ, 
                        CommonConst::GOODS_TYPE_DJQ, 
                        CommonConst::GOODS_TYPE_TLQ, 
                        CommonConst::GOODS_TYPE_ZKQ, 
                        CommonConst::GOODS_TYPE_HF, 
                        CommonConst::GOODS_TYPE_QB, 
                        CommonConst::GOODS_TYPE_HGDSDZQ, 
                        CommonConst::GOODS_TYPE_LLB
                    );
                }
                $map['goods_type'] = array(
                    'in', 
                    $show_type);
                break;
        }
        if (!$wxGoods || $show_source == 4) {
            $count = M('tgoods_info')->where($map)->count();
            $p = new Page($count, 8);
            $result = M('tgoods_info')->where($map)
            ->limit($p->firstRow, $p->listRows)
            ->order('add_time desc')
            ->select();
        } else {
            $selectGoods = M('tgoods_info')->where($map)->select(false);
            $distinctWxCard = M('twx_card_type')
            ->distinct(true)
            ->where($map)
            ->field('goods_id')->select(false);
            $count = M()
            ->table($distinctWxCard. ' a')
            ->join('inner join ' . $selectGoods . ' as select_goods on select_goods.goods_id = a.goods_id')
            ->count();
            $p = new Page($count, 8);
            $result = M()
            ->table($distinctWxCard. ' a')
            ->join('inner join ' . $selectGoods . ' as select_goods on select_goods.goods_id = a.goods_id')
            ->limit($p->firstRow, $p->listRows)
            ->order('select_goods.add_time desc')
            ->select();
        }
        
        return array(
            'result' => $result, 
            'p' => $p);
    }

    /**
     *
     * @param int $postSourceType 下拉框的第一个传过来的值
     * @return array 第二个下拉框可选的值数组
     */
    public function getProvidedGoodsType($postSourceType, $availableGoodsType) {
        if (empty($availableGoodsType)) {
            $availableGoodsType = '';
        }
        // 现在先简单的这样写（因为现在只有自建和采购两种情况），比较好的写法是对每个用“,”分割的option求合集
        switch ($postSourceType) {
            case '':
            case '0,1':
            case '1':
                $option = array(
                    '' => '卡券类型', 
                    CommonConst::GOODS_TYPE_YHQ => '优惠券', 
                    CommonConst::GOODS_TYPE_DJQ => '代金券', 
                    CommonConst::GOODS_TYPE_TLQ => '提领券', 
                    CommonConst::GOODS_TYPE_ZKQ => '折扣券', 
                    CommonConst::GOODS_TYPE_HF => '话费', 
                    CommonConst::GOODS_TYPE_QB => 'Q币', 
                    CommonConst::GOODS_TYPE_HGDSDZQ => '哈根达斯卡券', 
                    CommonConst::GOODS_TYPE_LLB => '流量包'
                );
                break;
            case '0':
                $option = array(
                    '' => '卡券类型', 
                    CommonConst::GOODS_TYPE_YHQ => '优惠券', 
                    CommonConst::GOODS_TYPE_DJQ => '代金券', 
                    CommonConst::GOODS_TYPE_TLQ => '提领券', 
                    CommonConst::GOODS_TYPE_ZKQ => '折扣券');
                break;
            default:
                $option = array(
                    '' => '卡券类型', 
                    CommonConst::GOODS_TYPE_YHQ => '优惠券', 
                    CommonConst::GOODS_TYPE_DJQ => '代金券', 
                    CommonConst::GOODS_TYPE_TLQ => '提领券', 
                    CommonConst::GOODS_TYPE_ZKQ => '折扣券');
                
        }
        $option = $this->getAvailable($option, $availableGoodsType);
        return $option;
    }

    public function getAllProvidedGoodsType() {
        return array(
            CommonConst::GOODS_TYPE_YHQ => '优惠券', 
            CommonConst::GOODS_TYPE_DJQ => '代金券', 
            CommonConst::GOODS_TYPE_TLQ => '提领券', 
            CommonConst::GOODS_TYPE_ZKQ => '折扣券', 
            CommonConst::GOODS_TYPE_HF => '话费', 
            CommonConst::GOODS_TYPE_QB => 'Q币', 
            CommonConst::GOODS_TYPE_HGDSDZQ => '哈根达斯卡券', 
            CommonConst::GOODS_TYPE_HB => '红包', 
            CommonConst::GOODS_TYPE_LLB
        );
    }

    public function getSourceColor($show_source) {
        $color = array( // 来源颜色
            '0' => 'tp1', 
            '1' => 'tp2', 
            '2' => 'tp3', 
            '3' => 'tp4');
        $exist = array_key_exists($show_source, $color);
        $oneColor = $color['0'];
        if ($exist) {
            $oneColor = $color[$show_source];
        }
        return $oneColor;
    }
    
    // public function openFreeEpos($nodeId) {
    // $map = array(
    // 'node_id' => $nodeId,
    // 'pos_type' => CommonConst::POS_TYPE_EPOS,
    // 'pos_pay_type' => CommonConst::POS_PAY_TYPE_FREE
    // );
    // $re = M('tpos_info')->where($map)->find();
    // //如果以前有免费的门店
    // if ($re) {
    // //向支撑发送修改请求
    // } else {
    // //向支撑发送创建请求
    
    // }
    // }
    
    /**
     * 是否需要提示创建门店和eops,目前大转盘活动需要去看看有没有生成免费的订单,如果没有免费的订单就不需要提示创建门店和epos(因为大转盘会给用户创建epos)
     *
     * @param int $nodeId
     * @param array $batch_type
     * @return boolean
     */
    public function remindCreate($nodeId, $nextStep) {
        $mId = $this->getMIdByUrl($nextStep);
        $batchType = M('tmarketing_info')->where(
            array(
                'id' => $mId))->getField('batch_type');
        $needRemindTypeArr = array(
            CommonConst::BATCH_TYPE_WEEL);
        $needRemind = true;
        if (in_array($batchType, $needRemindTypeArr)) {
            $map = array(
                'order_type' => '2', 
                'node_id' => $nodeId, 
                'batch_type' => array(
                    'in', 
                    $needRemindTypeArr));
            $isFreeUser = D('node')->getNodeVersion($nodeId);
            if ($isFreeUser) {
                $result = M('tactivity_order')->where($map)->select();
                if (! $result) {
                    $needRemind = false;
                }
            }
        }
        return $needRemind;
    }

    /**
     * 是否能启用(微信卡券作为奖品不能启用,微信红包能否启用（翼码公众号授权，只能启用翼码待发的微信红包，自有公众号授权的只能启用自建的微信红包）)
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @param unknown $cjBatchId
     * @return boolean
     */
    public function canStart($nodeId, $mId, $cjBatchId) {
        $re = M('tmarketing_info')->where(array(
            'id' => $mId))
            ->field('join_mode,config_data')
            ->find();
        $canStart = true;
        $msg = '';
        $info = M('tcj_batch')->alias('cj')
            ->where(array(
            'cj.id' => $cjBatchId))
            ->join('left join tbatch_info as b on cj.b_id = b.id')
            ->join('left join tgoods_info as g on g.goods_id = b.goods_id')
            ->field('b.card_id, g.source')
            ->select();
        if (! empty($info[0]['card_id'])) {
            if (!$re) {
                $result = array('errorCode' => '1001', 'msg' => '数据有误');
                log_write(json_encode($result));
                return $result;
            }
            if ($re['join_mode'] == 0) {
                $canStart = false;
                $msg = '以“手机号码”作为参与方式时，微信卡券不可作为奖品';
            }
        }
        $config = unserialize($re['config_data']);
        if (!isset($config['wx_auth_type'])) {
            $result = array('errorCode' => '1002', 'msg' => '数据有误');
            log_write(json_encode($result));
            return $result;
        }
        if ($config['wx_auth_type'] == '0') {//翼码授权
            if ($info[0]['source'] == '6') {
                $msg = '配置项为翼码授权时，不能启用自建微信红包';
                $canStart = false;
            }
        } else {
            if ($info[0]['source'] == '7') {
                $msg = '配置项为自有认证服务号时，不能启用翼码代发微信红包';
                $canStart = false;
            }
        }
        return array(
            'errorCode' => '0000', 
            'canStart' => $canStart, 
            'msg' => $msg
        );
    }

    /**
     * 分解下一步的参数,取到m_id
     *
     * @param unknown $next_step
     * @return string
     */
    public function getMIdByUrl($next_step) {
        $mId = '';
        $paramsArr = explode('&', $next_step);
        foreach ($paramsArr as $v) {
            $paramVal = explode('=', $v);
            if ($paramVal[0] == 'm_id') {
                $mId = $paramVal[1];
                break;
            }
        }
        return $mId;
    }

    public function getCateIdByUrl($next_step) {
        $cateId = '';
        $paramsArr = explode('&', $next_step);
        foreach ($paramsArr as $v) {
            $paramVal = explode('=', $v);
            if ($paramVal[0] == 'prizeCateId') {
                $cateId = $paramVal[1];
                break;
            }
        }
        return $cateId;
    }

    /**
     * 是否能用微信卡券
     *
     * @param unknown $nodeId
     * @param unknown $next_step
     * @return boolean
     */
    public function canUseCard($nodeId, $next_step) {
        $mId = $this->getMIdByUrl($next_step);
        $canUseCard = D('DrawLotteryAdmin')->canUseCard($nodeId, $mId);
        return $canUseCard;
    }

    /**
     * 重构下拉菜单或tab数组（因为有些地方对可选的范围有控制，比如有些地方不能选红包）
     *
     * @param 默认显示的下拉菜单或tab数组 $origin
     * @param 可选的下拉菜单或tab数组 $available
     * @return array 组装后的下拉菜单
     */
    public function getAvailable($origin, $available) {
        if ($available === '') {
            return $origin;
        }
        $availableArr = explode(',', $available);
        $mergeArr = array();
        foreach ($origin as $k => $v) {
            if ($k === '') {
                $mergeArr = array(
                    $available => $v);
            }
            if (! in_array($k, $availableArr)) {
                unset($origin[$k]);
            }
        }
        foreach ($origin as $key => $value) {
            $mergeArr[$key] = $value;
        }
        return $mergeArr;
    }

    /**
     * 查询该商品有没有微信卡券
     *
     * @param unknown $goodsId
     * @return boolean
     */
    public function getWxFlag($goodsId, $mEndTime) {
        $re = M('twx_card_type')->where(
            array(
                'goods_id' => $goodsId))->order('id desc')->find();
        $hasCard = false;
        $overTime = false;
        if ($re) {
            $hasCard = true;
            if ($re['date_end_timestamp'] < strtotime($mEndTime)) {
                $overTime = true;
            }
        }
        return array(
            'hasCard' => $hasCard, 
            'overTime' => $overTime
        );
    }

    /**
     * 根据goodsId获取最新添加的那条goodsId一致的微信卡券
     *
     * @param string $goodsId
     * @return array() 最新添加的那条goodsId一致的微信卡券
     */
    public function getWxCardInfoByGoodsId($goodsId) {
        $re = M('twx_card_type')->where(
            array(
                'goods_id' => $goodsId))
            ->limit(1)
            ->order('id desc')
            ->select();
        return $re[0];
    }

    public function getSendTypeArr($goodsId, $availableType = false, $mId) {
        $arr = array(
            '0' => '短信');
        $hasWxCard = $this->getWxCardInfoByGoodsId($goodsId);
        if ($hasWxCard) {
            $arr['1'] = '微信卡券';
            $mInfo = M('tmarketing_info')->where(array('id' => $mId))->field('end_time')->find();
            $mEndTime = strtotime($mInfo['end_time']);
            if ($mEndTime > $hasWxCard['date_end_timestamp']) {
                $hasWxCard['overTime'] = true;
            } else {
                $hasWxCard['overTime'] = false;
            }
        }
        if ($availableType !== false && ! is_string($availableType)) {
            throw_exception('获取可选发送形式参数错误');
        }
        if ($availableType === false) {
            $availableTypeArr = array_keys($arr); // 如果是false就全都留着
        } else {
            $availableTypeArr = explode(',', $availableType);
        }
        $new = array();
        foreach ($arr as $key => $value) {
            if (in_array($key, $availableTypeArr)) {
                $new[$key] = $value;
            }
        }
        return array(
            'cardInfo' => $hasWxCard, 
            'sendTypeArr' => $new);
    }
    
    /**
     * 是否显示微信红包
     * @param unknown $nodeId
     * @param unknown $mId
     * @return boolean
     */
    public function getJoinModeAndWxAuthType($nodeId, $mId = null) {
        if (!$mId) {
            return false;
        }
        $basicInfo = M('tmarketing_info')
        ->field('join_mode,config_data,end_time,batch_type')
        ->where(array('node_id' => $nodeId, 'id' => $mId))->find();
        //是否是微信参与方式
        if ($basicInfo['join_mode'] == 1) {
            //微信授权方式：（翼码授权=>只能用代发红包，自有公众号授权=>只能用自建微信红包）
            $configData = unserialize($basicInfo['config_data']);
        }
        return array(
            'join_mode' => $basicInfo['join_mode'], 
            'wx_auth_type' => get_val($configData['wx_auth_type']), 
            'endTime' => $basicInfo['end_time'], 
            'batch_type' => $basicInfo['batch_type']
        );
    }
    
    public function getFreindCard($nodeId, $goodsName) {
        import("ORG.Util.Page");
        $map = array(
            'node_id' => $nodeId, 
            'card_class' => '2', //2表示朋友的券
        );
        if ($goodsName) {
            $map['title'] = array(
                'like',
                array(
                    "%{$goodsName}%"));
        }
        $map['date_end_timestamp'] = array(
            'gt',
            time()
        );
        $count = M('twx_card_type')->where($map)->count();
        $p = new Page($count, 8);
        $result = M('twx_card_type')->where($map)
        ->field('*,logo_url as goods_image,title as goods_name')
        ->limit($p->firstRow, $p->listRows)
        ->order('id desc')
        ->select();
        return array(
            'result' => $result,
            'p' => $p);
    }
}