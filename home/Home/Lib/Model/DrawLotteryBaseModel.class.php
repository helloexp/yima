<?php

/**
 * 抽奖相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2016-04-13
 */
class DrawLotteryBaseModel extends BaseModel
{
    protected $tableName = '__NONE__';

    public $labelId = '';
    // 标签id
    public $mobile = '';
    // 手机号
    public $today = '';
    // 当前日期
    public $time = '';
    // 当前时间
    public $respMsg;
    // 返回信息
    public $cjWhiteBlack;
    // 黑白名单
    public $cj_rule_id;
    // 抽奖规则
    public $fullId;

    public $optType;

    public $param1;
    // 非标流水号参与次数
    public $ticketSeq;
    // 非标流水号
    public $otherParam;
    // 其他参数
    private $batchChannelInfo = array();

    private $marketInfo = array();

    private $cjRuleInfo = array();

    /**
     *
     * @param string $labelId    标签id
     * @param string $mobile     手机号
     * @param null   $fullId     访问路径
     * @param null   $ticketSeq
     * @param array  $otherParam 其他参数
     */
    public function initParam($labelId, $mobile, $fullId = null, $ticketSeq = null, $otherParam = [])
    {
        $this->labelId   = $labelId;
        $this->mobile    = $mobile;
        $this->today     = date('Ymd');
        $this->time      = date('His');
        $this->fullId    = $fullId;
        $this->ticketSeq = $ticketSeq;
        if (empty($this->fullId)) {
            $this->fullId = $labelId;
        }

        if ($otherParam && is_array($otherParam)) {
            $this->otherParam = $otherParam;
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $labelId
     */
    public function setLabelId($labelId)
    {
        $this->labelId = $labelId;
    }

    /**
     * 获取标签详情
     *
     * @return bool
     */
    public function getLabel()
    {
        $res = array(
                'code' => '',  // 结果 1:正常， 非1：出现错误
                'data' => '',  // 相关数据
                'msg'  => ''
        );

        if (empty($this->labelId)) {
            $res['code'] = -1023;
            return $res;
        }
        $where                  = ['id' => $this->labelId];
        $this->batchChannelInfo = $this->getBatchChannel($where, BaseModel::SELECT_TYPE_ONE);
        if (!$this->batchChannelInfo) {
            $res['code'] = -1024;
            return $res;
        }

        $this->cjWhiteBlack = $this->getNodeInfo(
                ['node_id' => $this->batchChannelInfo['node_id']],
                BaseModel::SELECT_TYPE_FIELD,
                'cj_white_black'
        );

        $res['code'] = 1;
        $res['data'] = $this->batchChannelInfo;

        return $res;
    }

    /**
     *
     * @param        $where
     * @param int    $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getCjBatch($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '')
    {
        return $this->getData('tcj_batch', $where, $selectType, $field);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param mixed  $where
     * @param int    $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getCjCate($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '')
    {
        return $this->getData('tcj_cate', $where, $selectType, $field);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param mixed  $where
     * @param int    $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getCodeVerify(
            $where,
            $selectType = BaseModel::SELECT_TYPE_ONE,
            $field = ''
    ) {
        return $this->getData('tcode_verify', $where, $selectType, $field);
    }

    /**
     * 更新参与码
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $where
     * @param $update
     *
     * @return bool
     */
    public function updateCodeVerifyData($where, $update)
    {
        return M('tcode_verify')->where($where)->save($update);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param mixed  $where
     * @param string $join
     * @param string $field
     * @param int    $selectType
     *
     * @return mixed
     */
    public function getCjBatchJoin($where, $join, $field = '', $selectType = BaseModel::SELECT_TYPE_ALL)
    {
        if ($selectType == BaseModel::SELECT_TYPE_ONE) {
            return M('tcj_batch a')->field($field)->join($join)->where($where)->find();
        } else if ($selectType == BaseModel::SELECT_TYPE_ALL) {
            return M('tcj_batch a')->field($field)->join($join)->where($where)->select();
        } else {
            return M('tcj_batch a')->field($field)->join($join)->where($where)->getField($field);
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $where
     *
     * @return mixed
     */
    public function getGoodsInfoAndBounsInfo($where)
    {
        $return = M('tcj_batch a')->field(
                'g.goods_name,g.goods_id,g.id,g.goods_type,g.bonus_id,b.link_url,i.node_name,a.b_id'
        )->join('tgoods_info g ON a.goods_id=g.goods_id')->join('tbonus_info b ON b.id=g.bonus_id')->join(
                'tnode_info i ON i.node_id=g.node_id'
        )->where($where)->find();
        return $return;
    }

    /**
     * 获得剩余抽奖次数
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $labelId
     * @param $batchType
     * @param $batchId
     * @param $mobile
     * @param $totalPart
     *
     * @return int
     */
    public function getDrawLotteryLeftChances($labelId, $batchType, $batchId, $mobile, $totalPart)
    {
        if (empty($totalPart)) { // 不限次数
            $leftTime = 99999999;
        } else if (empty($mobile)) {
            $leftTime = $totalPart;
        } else {
            $joinedTimes = M('tcj_trace')->where(
                    array(
                            'label_id'   => $labelId,
                            'batch_id'   => $batchId,
                            'batch_type' => $batchType,
                            'mobile'     => $mobile
                    )
            )->field('COUNT(1) AS joinedTimes')->find();
            $leftTime    = $totalPart - (int)$joinedTimes;
            if ($leftTime < 0) {
                $leftTime = 0;
            }
        }

        return $leftTime;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $labelId
     * @param $batchType
     * @param $batchId
     * @param $drawLotteryId
     * @param $totalPartPerDay
     *
     * @return int
     */
    public function getDrawLotteryLeftChancesPerDay($labelId, $batchType, $batchId, $drawLotteryId, $totalPartPerDay)
    {
        if (empty($totalPartPerDay)) { // 不限次数
            $leftTime = 9999999;
        } else if (empty($drawLotteryId)) {
            $leftTime = $totalPartPerDay;
        } else {
            $joinedTimes = M('tcj_trace')->where(
                    array(
                            'label_id'   => $labelId,
                            'batch_id'   => $batchId,
                            'batch_type' => $batchType,
                            'mobile'     => $drawLotteryId,
                            'add_time'   => array(
                                    'like',
                                    date('Ymd', time()) . '%'
                            )
                    )
            )->field('COUNT(1) AS joinedTimes')->find();
            if (isset($joinedTimes['joinedTimes'])) {
                $joinedTimes = $joinedTimes['joinedTimes'];
            }
            $leftTime = $totalPartPerDay - (int)$joinedTimes;
            if ($leftTime < 0) {
                $leftTime = 0;
            }
        }

        return $leftTime;
    }

    /**
     * 获取抽奖信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $marketInfo
     *
     * @return array
     */
    public function getCjInfo($marketInfo = array())
    {
        // 抽奖配置表
        if ($marketInfo['is_cj'] != '1') {
            return array(
                    'code' => '1',
                    'msg'  => '未设置抽奖'
            );
        }
        $cjRuleWhere = array(
                'batch_type' => $marketInfo['batch_type'],
                'batch_id'   => $marketInfo['id'],
                'status'     => '1'
        );
        $cjRule      = $this->getCjRule(
                $cjRuleWhere,
                BaseModel::SELECT_TYPE_ONE,
                'id,total_chance,cj_button_text,cj_check_flag,phone_total_part,phone_day_part'
        );
        // 抽奖文字配置
        $cjText = $cjRule['cj_button_text'];
        // 奖品
        $where     = "a.batch_id='" . $marketInfo['id'] . "' and a.status='1'";
        $join      = 'tbatch_info b on a.b_id=b.id';
        $field     = 'a.cj_cate_id,b.batch_name,b.batch_img';
        $prizeList = $this->getCjBatchJoin($where, $join, $field);
        if (empty($prizeList)) {
            return ['code' => '1', 'msg' => '未设置奖品'];
        }
        foreach ($prizeList as &$v) {
            $v['batch_img'] = get_upload_url($v['batch_img']);
        }
        unset($v);
        // 获取奖品中的cate_id
        $cjCateIds = array_valtokey($prizeList, 'cj_cate_id', 'cj_cate_id');

        // 分类
        $cjCateList = array();
        if ($cjRule) {
            $cjCateList = $this->getCjCate(
                    array(
                            'node_id'    => $marketInfo['node_id'],
                            'batch_id'   => $marketInfo['id'],
                            'cj_rule_id' => $cjRule['id'],
                            'id'         => array(
                                    'in',
                                    $cjCateIds
                            )
                    ),
                    BaseModel::SELECT_TYPE_ALL,
                    'id,name'
            );
        }

        // 处理页面奖项奖项
        $cjCateId   = array();
        $cjCateName = '';
        foreach ($cjCateList as $v) {
            $cjCateId[] = $v['id'];
            $cjCateName .= '"' . $v['name'] . '",';
        }
        return array(
                'code' => 0,
                'msg'  => 'success',
                'data' => array(
                        'cj_cate_id'   => implode(',', $cjCateId),
                        'cj_cate_name' => trim($cjCateName, ','),
                        'total_chance' => $cjRule['total_chance'],
                        'cj_text'      => $cjText,
                        'cj_rule'      => $cjRule,
                        'prizeList'    => $prizeList,
                        'cjCateList'   => $cjCateList
                )
        );
    }

    /**
     * 获得 node info 相关信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array  $where
     * @param int    $selectType
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getWeixinInfo($where, $selectType = BaseModel::SELECT_TYPE_ONE, $field = '', $order = '')
    {
        return $this->getData('tweixin_info', $where, $selectType, $field, $order);
    }

    /**
     * @param        $where
     * @param int    $selectType
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getWxUser($where, $selectType = BaseModel::SELECT_TYPE_ONE, $field = '', $order = '')
    {
        return $this->getData('twx_user', $where, $selectType, $field, $order);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $where
     * @param $update
     *
     * @return bool
     */
    public function updatetWeixinInfo($where, $update)
    {
        return M('tweixin_info')->where($where)->save($update);
    }

    /**
     * 获得 node info 相关信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array  $where
     * @param int    $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getNodeInfo($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '')
    {
        return $this->getData('tnode_info', $where, $selectType, $field);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $where
     * @param int    $selectType
     * @param string $field
     *
     * @return mixed
     */
    public function getCjTrace($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '')
    {
        return $this->getData('tcj_trace', $where, $selectType, $field);
    }

    /**
     * 获取所有的中奖记录列表
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $condition
     *
     * @return mixed
     */
    public function getAwardList($condition)
    {
        $whereFormat = 't.mobile="%s" AND t.status=2 AND t.batch_id=%d and t.batch_type=%d';
        $mobile      = isset($condition['mobile']) ? $condition['mobile'] : '';
        $batch_id    = isset($condition['batch_id']) ? $condition['batch_id'] : '';
        $batch_type  = isset($condition['batch_type']) ? $condition['batch_type'] : '';
        $where       = sprintf($whereFormat, $mobile, $batch_id, $batch_type);

        $normalAwardList = M('tcj_trace t')->field(
                't.id as cj_trace_id,t.mobile,t.send_mobile,t.request_id,gi.goods_name,gi.node_id,gi.goods_type,gi.bonus_id,gi.source,t.batch_id,bi.id as card_batch_id,
                bi.card_id,bi.batch_short_name,ti.link_url,ni.node_name,ti.bonus_end_time,ti.bonus_start_time,
                bud.id as bonus_use_detail_id,bud.bonus_detail_id,bud.phone,bud.bonus_num,bud.bonus_use_num,bud.use_time,wan.id as twx_assist_number_id,wan.status as wx_status,
                wan.assist_number,integal.id as integal_get_id,integal.status as integal_status, integal.node_id as integal_node_id,integal.integral_num,ct.end_time as userpoint_end_time'
        )->join('tbatch_info bi ON bi.id=t.b_id')->join('tgoods_info gi ON bi.goods_id=gi.goods_id')->join(
                'tbonus_info ti ON ti.id=gi.bonus_id'
        )->join('tnode_info ni ON ni.node_id=t.node_id')->join(
                'tbonus_use_detail bud ON bud.request_id=t.request_id'
        )->join('twx_assist_number wan ON wan.request_id=t.request_id')->join(
                'tbarcode_trace ct ON ct.request_id=t.request_id'
        )->join('tintegal_get_detail integal ON integal.request_id=t.request_id')->where($where)->select();
        return $normalAwardList;
    }

    /**
     * 获取所有的中奖记录列表
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $condition
     *
     * @return mixed
     */
    public function getAwardListold($condition)
    {
        $whereFormat = 't.mobile="%s" AND t.status=2 AND t.batch_id=%d and t.batch_type=%d';
        $mobile      = isset($condition['mobile']) ? $condition['mobile'] : '';
        $batch_id    = isset($condition['batch_id']) ? $condition['batch_id'] : '';
        $batch_type  = isset($condition['batch_type']) ? $condition['batch_type'] : '';
        $where       = sprintf($whereFormat, $mobile, $batch_id, $batch_type);

        $normalAwardList = M('tcj_trace t')->field(
                't.mobile,i.goods_name,i.node_id,i.goods_type,i.bonus_id,t.batch_id,c.id as card_batch_id,
                c.card_id,c.batch_short_name,ti.link_url,ni.node_name,ti.bonus_end_time,ti.bonus_start_time'
        )->join('tcj_batch b ON t.rule_id=b.id')->join('tgoods_info i ON b.goods_id=i.goods_id')->join(
                'tbatch_info c ON c.id=b.b_id'
        )->join('tbonus_info ti ON ti.id=i.bonus_id')->join('tnode_info ni ON ni.node_id=t.node_id')->where(
                $where
        )->select();

        $bonusCondList      = [];
        $wechatCardCondList = [];
        $bonusList          = [];
        $wechatCardList     = [];

        if ($normalAwardList) {
            foreach ($normalAwardList as $idx => $award) {
                if ($award['bonus_id']) { // 红包
                    $tmpBonusCond = array(
                            'phone'    => $award['mobile'],
                            'bonus_id' => $award['bonus_id']
                    );
                    if (!in_array($tmpBonusCond, $bonusCondList)) {
                        $bonusCondList[] = $tmpBonusCond;
                    }
                    unset($tmpBonusCond);
                } else if ($award['card_id']) { // 微信卡券
                    $tmpWechatCardCond = ['open_id' => $award['mobile'], 'card_batch_id' => $award['card_batch_id']];
                    if (!in_array($tmpWechatCardCond, $wechatCardCondList)) {
                        $wechatCardCondList[] = $tmpWechatCardCond;
                    }
                    unset($tmpBonusCond);
                }
            }
        }

        if ($bonusCondList) { // 获取红包
            $bonusCondList['_logic'] = 'OR';
            $bonusList               = M('tbonus_use_detail')->where($bonusCondList)->select();
        }

        if ($wechatCardCondList) { // 获取微信卡券
            $wechatCardCondList['_logic'] = 'OR';

            $wechatCardList       = M('twx_assist_number')->where($wechatCardCondList)->order('id desc')->select();
            $formatWechatCardList = array();
            if (is_array($wechatCardList)) {
                $uniqueData = array();
                foreach ($wechatCardList as $widx => $wechatCard) {
                    $key = md5($wechatCard['open_id'] . ':' . $wechatCard['card_batch_id']);
                    if (!isset($uniqueData[$key])) {
                        $uniqueData[$key]       = 1;
                        $formatWechatCardList[] = $wechatCard;
                    }
                }
            }
            unset($wechatCardList);
            $wechatCardList = $formatWechatCardList;
        }
        foreach ($normalAwardList as $idx => $award) {
            if ($award['bonus_id']) { // 红包
                if ($bonusList && is_array($bonusList)) {
                    foreach ($bonusList as $bidx => $bonus) {
                        if ($bonus['phone'] == $award['mobile'] && $award['bonus_id'] == $bonus['bonus_id']) {
                            $normalAwardList[$idx]['bonus_num']     = $bonus['bonus_num'];
                            $normalAwardList[$idx]['bonus_use_num'] = $bonus['bonus_use_num'];
                            $normalAwardList[$idx]['use_time']      = $bonus['use_time'];
                            unset($bonusList[$bidx]);
                            break;
                        }
                    }
                }
            } else if ($award['card_id']) { // 微信卡券
                foreach ($wechatCardList as $tidx => $wechatCard) {
                    if ($wechatCard['open_id'] == $award['mobile'] && $award['card_batch_id'] == $wechatCard['card_batch_id']) {
                        $normalAwardList[$idx]['twx_assist_number_id'] = $wechatCard['id'];
                        $normalAwardList[$idx]['wx_status']            = $wechatCard['status'];
                        $normalAwardList[$idx]['assist_number']        = $wechatCard['assist_number'];
                        $normalAwardList[$idx]['card_batch_id']        = $wechatCard['card_batch_id'];
                        unset($wechatCardList[$tidx]);
                        break;
                    }
                }
            }
        }

        return $normalAwardList;
    }

    /**
     * 获得batch_channel 相关数据
     *
     * @author Jeff liu<liuwy@imageco.com.cn>
     *
     * @param array  $where
     * @param int    $selectType 0：获得所有满足条件的记录 1：获得一条满足条件的记录 2:获得一条满足条件的记录中的某些字段
     * @param string $field      只有在 $selectType为2的时候才有效
     * @param string $order
     *
     * @return mixed
     */
    public function getBatchChannel($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '', $order = '')
    {
        return $this->getData('tbatch_channel', $where, $selectType, $field, $order);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $where
     * @param int    $selectType
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getChannel($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '', $order = '')
    {
        return $this->getData('tchannel', $where, $selectType, $field, $order);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $where
     * @param int    $selectType
     * @param string $field
     * @param string $order
     *
     * @return mixed
     */
    public function getBatchInfo($where, $selectType = BaseModel::SELECT_TYPE_ALL, $field = '', $order = '')
    {
        return $this->getData('tbatch_info', $where, $selectType, $field, $order);
    }

    /**
     * 获得marketing_info 相关数据
     *
     * @author Jeff Liu<liuwy@imageo.com.cn>
     *
     * @param array $where
     *
     * @return mixed
     */
    public function getMarketInfo($where)
    {
        return $this->getData('tmarketing_info', $where, BaseModel::SELECT_TYPE_ONE);
    }

    /**
     * 获得tcj_rule相关数据
     *
     * @author Jeff Liu<liuwy@imageo.com.cn>
     *
     * @param array  $where
     * @param int    $selectType
     * @param string $fields
     *
     * @return mixed
     */
    public function getCjRule($where, $selectType = BaseModel::SELECT_TYPE_ONE, $fields = '')
    {
        return $this->getData('tcj_rule', $where, $selectType, $fields);
    }

    /**
     * 获得tcj_white_blacklist相关数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $where
     *
     * @return mixed
     */
    public function getCjWhiteBlackList($where)
    {
        return $this->getData('tcj_white_blacklist', $where, BaseModel::SELECT_TYPE_ONE);
    }

    /**
     * 获取活动信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    public function getBatch()
    {
        $res = array(
                'code' => '',  // 结果 1:正常， 非1：出现错误
                'data' => '',  // 相关数据
                'msg'  => ''
        );

        $labelResult = $this->getLabel();
        if ($labelResult['code'] !== 1) {
            return $labelResult;
        }
        $labelResult      = $labelResult['data']; // batch_id
        $where            = array(
                'id'         => $labelResult['batch_id'],
                'batch_type' => $labelResult['batch_type']
        );
        $this->marketInfo = $this->getMarketInfo($where);
        if (!$this->marketInfo) {
            $res['code'] = -1024;
            // $this->respMsg = "活动不存在";

            return $res;
        }

        if ($this->marketInfo['join_mode']) { // 微信号参与
            $otherarr = $this->otherParam;
            if (empty($otherarr['wx_open_id'])) {
                $res['code'] = -1025;
                return $res;
            }
            if (empty($otherarr['wx_nick'])) {
                $res['code'] = -1025;
                return $res;
            }
        } else { // 手机号参与 校验手机号
            if (empty($this->mobile) || !is_numeric($this->mobile) || strlen($this->mobile) != '11') {
                $res['code'] = -1026;
                return $res;
            }
        }

        if ($this->marketInfo['status'] != '1') {
            $res['code'] = -1027;
            return $res;
        }
        if ($this->today . $this->time > $this->marketInfo['end_time'] || $this->today . $this->time < $this->marketInfo['start_time']) {
            $this->respMsg = '活动不在有效期！';
            $res['code']   = -1005;
            return $res;
        }
        if ($this->marketInfo['is_cj'] == '0') {
            $res['code'] = -1028;
            return $res;
        }
        // 抽奖规则配置
        $cjWhere = ['node_id'    => $labelResult['node_id'],
                    'batch_type' => $labelResult['batch_type'],
                    'batch_id'   => $labelResult['batch_id'],
                    'status'     => '1'
        ];

        if ($this->cj_rule_id) {
            $cjWhere['id'] = $this->cj_rule_id;
        }
        $cjRuleInfo = $this->getCjRule($cjWhere);
        if ($cjRuleInfo['jp_set_type'] != '') {
            $this->cjRuleInfo = $cjRuleInfo;
            $this->param1     = $cjRuleInfo['param1'];
        } else {
            $res['code'] = -1029;
            return $res;
        }

        $res['code']                     = 1;
        $res['data']['batchChannelInfo'] = $this->batchChannelInfo;
        $res['data']['cjRuleInfo']       = $this->cjRuleInfo;
        return $res;
    }

    /**
     * 检验是否符合抽奖条件
     *
     * @return bool
     */
    public function verifyDrawLotteryCondition()
    {
        $res   = array(
                'code' => '',  // 结果 1:正常， 非1：出现错误
                'data' => '',  // 相关数据
                'msg'  => ''
        );
        $query = $this->getBatch();
        if ($query['code'] === 1) {
            return $query;
        }
        // 黑白名单
        $cjWhiteBlackListWhere = array(
                'node_id'  => $this->batchChannelInfo['node_id'],
                'phone_no' => $this->mobile
        );
        $cjWhiteBlackList      = $this->getCjWhiteBlackList($cjWhiteBlackListWhere);

        if ($this->cjWhiteBlack == '2') {
            if ($cjWhiteBlackList) {
                $res['code'] = -1030;
                return $res;
            }
        } elseif ($this->cjWhiteBlack == '1') {
            if (!$cjWhiteBlackList) {
                $res['code'] = -1030;
                return $res;
            }
        }
        $res['code'] = 1;
        return $res;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    public function getDrawLotteryLogCondition()
    {
        return array(
                'label_id' => $this->batchChannelInfo['id'],
                'node_id'  => $this->batchChannelInfo['node_id'],
                'full_id'  => $this->marketInfo['full_id'],
                'batch_id' => $this->marketInfo['id']
        );
    }

    /**
     * 构建发送请求参数
     *
     * @author Jeff Liu @date 2015-06-30
     * @return array
     */
    public function buildSendData()
    {
        $sendData = array(
                'node_id'          => $this->batchChannelInfo['node_id'],
                'batch_id'         => $this->batchChannelInfo['batch_id'],  // 活动id
                'channel_id'       => $this->batchChannelInfo['channel_id'],
                'batch_type'       => $this->batchChannelInfo['batch_type'],
                'award_type'       => $this->cjRuleInfo['jp_set_type'],  // 单，多类型
                'award_times'      => $this->cjRuleInfo['phone_day_count'],  // 每日限制次数
                'award_count'      => $this->cjRuleInfo['phone_total_count'],  // 总总将次数
                'day_part'         => $this->cjRuleInfo['phone_day_part'],  // 日参与次数
                'total_part'       => $this->cjRuleInfo['phone_total_part'],  // 中参与次数
                'phone_no'         => $this->mobile,  // 当前手机号（或者open_id）
                'label_id'         => $this->labelId,  // phone_total_part当前渠道号
                'total_rate'       => $this->cjRuleInfo['total_chance'],  // 中奖概率
                'ip'               => get_client_ip(),
                'cj_rule_id'       => $this->cjRuleInfo['id'],
                'participation_id' => $this->otherParam['participation_id'],  // 参与流水id
                'full_id'          => $this->fullId
        );
        if ($this->param1 != '' && !empty($this->ticketSeq)) {
            $sendData['ticket_limit_num'] = $this->param1;
            $sendData['ticket_seq']       = $this->ticketSeq;
        }
        if ($this->otherParam) {
            if (issetAndNotEmpty($this->otherParam, 'wx_wap_ranking_id')) {
                $sendData['wx_wap_ranking_id'] = $this->otherParam['wx_wap_ranking_id'];
            }
            if (issetAndNotEmpty($this->otherParam, 'wx_cjcate_id')) {
                $sendData['wx_cjcate_id'] = $this->otherParam['wx_cjcate_id'];
            }
            if (issetAndNotEmpty($this->otherParam, 'open_id')) {
                $sendData['open_id'] = $this->otherParam['open_id'];
            }
            if (issetAndNotEmpty($this->otherParam, 'wx_open_id')) {
                $sendData['wx_open_id'] = $this->otherParam['wx_open_id'];
            }
            if (issetAndNotEmpty($this->otherParam, 'wx_nick')) {
                $sendData['wx_nick'] = $this->otherParam['wx_nick'];
            }
            // 补充付满送的pay_token字段
            if (issetAndNotEmpty($this->otherParam, 'pay_token')) {
                $sendData['pay_token'] = $this->otherParam['pay_token'];
            }
        }

        return $sendData;
    }

    /**
     * 根据batch_info的id查出活动设置的机构名称
     *
     * @param int $bId
     *
     * @return mixed
     */
    public function getMarketActivityNodeNameByBid($bId)
    {
        $result = M('tbatch_info b')->field('m.node_name')->join(
                'inner join tmarketing_info m on b.m_id = m.id'
        )->where(array('b.id' => $bId))->select();
        return $result;
    }

    /**
     * 根据b_id合并之前的数据
     *
     * @param array $gInfo
     *
     * @return mixed
     */
    public function mergeGoodsInfoByBid($gInfo)
    {
        if (isset($gInfo['b_id']) && !empty($gInfo['b_id'])) {
            $result = $this->getMarketActivityNodeNameByBid($gInfo['b_id']);
            if ($result && isset($result[0]['node_name'])) {
                $gInfo['node_name'] = $result[0]['node_name'];
            }
        }
        return $gInfo;
    }
}