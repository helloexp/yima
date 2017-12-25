<?php

/**
 * Created by ys. User: Administrator Date: 2015/10/29 Time: 16:46
 */
class IntegralPointTraceModel extends BaseModel {
    protected $tableName = '__NONE__';
    // 积分扣减
    // type=4 手动积分增加 type=3 手动积分扣减 member_id 会员id type=5
    // conSign:1连续签到7天;0非连续签到7天
    public function integralPointChange($type, $point, $member_id, $node_id, 
        $order_id = '', $con_sign = '') {
        $model = M("tmember_info");
        $res = $model->where(
            array(
                'node_id' => $node_id, 
                'id' => $member_id))
            ->lock(true)
            ->find();
        if (empty($res)) {
            log_write("查询不到会员" . $member_id);
            return false;
        }
        // 如果会员存在
        $data = array(
            'before_num' => $res['point'], 
            'change_num' => $point, 
            'trace_time' => date('YmdHis'), 
            'member_id' => $member_id, 
            'node_id' => $node_id, 
            'relation_id' => $order_id);
        $member_data = array();
        if ($type == CommonConst::INTEGRAL_TYPE1) {
            if (empty($order_id)) {
                log_write("订单号不能为空" . $order_id);
                return false;
            }
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "在线购物";
            $data['relation_id'] = $order_id;
            $data['type'] = CommonConst::INTEGRAL_TYPE1;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE2) {
            if (empty($order_id)) {
                log_write("订单号不能为空" . $order_id);
                return false;
            }
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够2" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "积分商城兑换";
            $data['relation_id'] = $order_id;
            $data['type'] = CommonConst::INTEGRAL_TYPE2;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE3) {
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够3" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "手动减少积分";
            $data['type'] = CommonConst::INTEGRAL_TYPE3;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE4) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "手动增加积分";
            $data['type'] = CommonConst::INTEGRAL_TYPE4;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE5) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "每日签到";
            if ($con_sign == '1') {
                $data['remark'] = "连续7日签到";
            }
            $data['type'] = CommonConst::INTEGRAL_TYPE5;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE8) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "线下消费";
            $data['type'] = CommonConst::INTEGRAL_TYPE8;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE9) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "会员绑定合并流水";
            $data['type'] = CommonConst::INTEGRAL_TYPE9;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE10) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "微信支付";
            $data['type'] = CommonConst::INTEGRAL_TYPE10;
            $member_data['point'] = $data['after_num'];
        }

        if ($type == CommonConst::INTEGRAL_TYPE19) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "支付宝支付";
            $data['type'] = CommonConst::INTEGRAL_TYPE19;
            $member_data['point'] = $data['after_num'];
        }

        if ($type == CommonConst::INTEGRAL_TYPE11) {
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够11" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "线下积分兑换";
            $data['type'] = CommonConst::INTEGRAL_TYPE11;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE12) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "参与活动中奖";
            $data['type'] = CommonConst::INTEGRAL_TYPE12;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE13) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "参与活动中奖";
            $data['type'] = CommonConst::INTEGRAL_TYPE13;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE14) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "浏览图文编辑页面";
            $data['type'] = CommonConst::INTEGRAL_TYPE14;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE15) {
            $mapType=array(
                    'node_id' => $node_id,
                    'member_id' => $member_id,
                    'type'=>CommonConst::INTEGRAL_TYPE15
            );
            $resultPoint=M("tintegral_point_trace")->where($mapType)->find();
            if($resultPoint){
                log_write("用户已经存在关注送积分记录".print_r($mapType,true));
                return false;
            }
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "关注微信公众号";
            $data['type'] = CommonConst::INTEGRAL_TYPE15;
            $member_data['point'] = $data['after_num'];
        }
        // type=16暂时不用
        if ($type == CommonConst::INTEGRAL_TYPE16) {
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "暂时不用";
            $data['type'] = CommonConst::INTEGRAL_TYPE16;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE17) {
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "线上购物抵扣";
            $data['type'] = CommonConst::INTEGRAL_TYPE17;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE18) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "订单撤销";
            $data['type'] = CommonConst::INTEGRAL_TYPE18;
            $member_data['point'] = $data['after_num'];
        }
        $result = $model->where(
            array(
                'node_id' => $node_id, 
                'id' => $member_id))->save($member_data);
        if ($result === false) {
            log_write("更新会员积分失败!参数：" . print_r($member_data, true));
            return false;
        }
        // 保存积分流水
        $result_trace = M("tintegral_point_trace")->add($data);
        if ($result_trace === false) {
            log_write("积分流水失败!参数：" . print_r($data, true));
            return false;
        }
        $this->messageNotice($node_id, $res['id'], $data['after_num'], $point, $data['remark'], $type);
        return $data;
    }
    /**
     * @return IntegralChangeNoticeSetModel
     */
    private function getIntegralChangeNoticeSetModel(){
    	return D('IntegralChangeNoticeSet');
    }
    /**
     * 积分变动时调用的消息处理方法     单个的
     *
     * @param $nodeId           string   商户编号
     * @param $memberId         string   会员ID
     * @param $nowIntegral      string   当前积分
     * @param $changeIntegral   string   变更的积分
     * @param $event            string   触发变更的源信息
     * @param $type             string   变更类型  (用于分辨加或减)
     * @return void
     */
    function messageNotice($nodeId, $memberId, $nowIntegral, $changeIntegral, $event, $type){
        //检测不发消息的类型
        $noMessage = array(9,99);
        if(in_array($type,$noMessage)){
            return;
        }
        $changeType = array(1,4,5,8,10,12,13,14,15,18);
    	//  3：消耗积分    2：获得积分
    	$msgTemplateType = 3;
    	if(in_array($type,$changeType)){
    		$msgTemplateType = 2;
    	}
    	//模板
    	$nodeTemplateModel = $this->getIntegralChangeNoticeSetModel();
    	$integralTemplateInfo = $nodeTemplateModel->messageTemplate($nodeId,$msgTemplateType);
        //如果未配置就直接退出方法了
        if(empty($integralTemplateInfo)){
            return;
        }
        //会员
        $memberInfo = M('tmember_info')->where(array('id'=>$memberId))->find();
        //没有手机号的都视为不是会员，且不发消息
        if(empty($memberInfo['phone_no'])){
            return;
        }
        //获取会员等级列表
        $cardLevel = M('tmember_cards')->where(array('node_id'=>$nodeId))->getField('id,card_name');

    	//个人中心
    	$cont = array();
    	if($integralTemplateInfo['system_msg'] == 1){
    		$useTemplate = json_decode($integralTemplateInfo['system_msg_templet'],true);
    		$cont['cont'] = str_replace('<-姓名->',$memberInfo['name'],$useTemplate);
    		$cont['cont'] = str_replace('<-剩余积分->',$nowIntegral,$cont['cont']);
    		if($msgTemplateType == 3){
    			$cont['type'] = 3;
    			$cont['cont'] = str_replace('<-消耗积分值->',$changeIntegral,$cont['cont']);
    		}else{
    			$cont['type'] = 2;
    			$cont['cont'] = str_replace('<-获得积分值->',$changeIntegral,$cont['cont']);
    		}
            $cont['title'] = '积分变动';
    		$addData = array(
    				'node_id'       => $nodeId,
    				'msg_type'      => 1,
    				'content'       => json_encode($cont),
    				'status'        => 0,
    				'member_id'     => $memberInfo['id'],
    				'add_time'      => date('YmdHis')
    		);
    		$isOk = M('tintegral_change_notice')->add($addData);
    		if(!$isOk){
    			log_write("积分变动添加个人中心消息失败！".print_r($addData,true));
    		}
    	}

    	//短信
    	if($integralTemplateInfo['sms_msg'] == 1){
    		$useTemplate = json_decode($integralTemplateInfo['sms_msg_templet'],true);
    		$cont = array('phone_no'=>$memberInfo['phone_no']);
    		$cont['content'] = str_replace('<-姓名->',$memberInfo['name'],$useTemplate);
    		if($msgTemplateType == 3){
    			$cont['content'] = str_replace('<-使用积分值->',$changeIntegral,$cont['content']);
    		}else{
    			$cont['content'] = str_replace('<-获得积分值->',$changeIntegral,$cont['content']);
    		}
    		$addData = array(
    				'node_id'       => $nodeId,
    				'msg_type'      => 2,
    				'content'       => json_encode($cont),
    				'status'        => 0,
    				'member_id'     => $memberInfo['id'],
    				'add_time'      => date('YmdHis')
    		);
    		$isOk = M('tintegral_change_notice')->add($addData);
    		if(!$isOk){
    			log_write("积分变动发送短信失败！".print_r($addData,true));
    		}
    	}

    	//微信
    	if($integralTemplateInfo['wx_msg'] == 1){
    		$useTemplate = json_decode($integralTemplateInfo['wx_msg_templet'],true);
    		if($msgTemplateType == 3){
    			$changeStr = -$changeIntegral;
    		}else{
    			$changeStr = $changeIntegral;
    		}
            foreach($useTemplate['content']['contentKeyVal'] as $key => $value){
                if($value == '会员姓名'){
                    $useTemplate['content']['contentKeyVal'][$key] = $memberInfo['name'];
                }elseif($value == '会员手机号'){
                    $useTemplate['content']['contentKeyVal'][$key] = $memberInfo['phone_no'];
                }elseif($value == '会员等级'){
                    $useTemplate['content']['contentKeyVal'][$key] = $cardLevel[$memberInfo['card_id']];
                }elseif($value == '积分增减值'){
                    $useTemplate['content']['contentKeyVal'][$key] = $changeStr;
                }elseif($value == '积分总额(变更后)'){
                    $useTemplate['content']['contentKeyVal'][$key] = $nowIntegral;
                }elseif($value == '积分变动原因'){
                    $useTemplate['content']['contentKeyVal'][$key] = $event;
                }elseif($value == '时间'){
                    $useTemplate['content']['contentKeyVal'][$key] = date('Y-m-d H:i:s');
                }elseif($value == '--请选择--'){
                    $useTemplate['content']['contentKeyVal'][$key] = '';
                }
            }
            $useTemplate['openId'] = $memberInfo['mwx_openid'];
            if(empty($memberInfo['mwx_openid'])){
                $useTemplate['openId'] = $memberInfo['wx_openid'];
            }
            $useTemplate['content'] = $useTemplate['content']['contentKeyVal'];

            $addData = array(
                    'node_id'       => $nodeId,
                    'msg_type'      => 3,
                    'content'       => json_encode($useTemplate),
                    'status'        => 0,
                    'member_id'     => $memberInfo['id'],
                    'add_time'      => date('YmdHis')
            );
            $isOk = M('tintegral_change_notice')->add($addData);
            if (!$isOk) {
                log_write("积分变动发送微信模板消息失败！" . print_r($addData, true));
            }
        }
    }
    /**
     * 积分变动时调用的消息处理方法     批量的
     *
     * @param $memberStr   string   会员ID
     * @param $nodeId      string   商户编号
     * @param $type        string   6是批量增加积分  7是批量减少积分
     * @param $point       string   作用的积分值
     * @return void
     */
    function allMessageNotice($memberStr, $nodeId, $type, $point){
        // 3 是减积分   2 是加积分
        $msgTemplateType = 3;
        if($type == 6){
            $msgTemplateType = 2;
        }

        //模板
        $nodeTemplateModel = $this->getIntegralChangeNoticeSetModel();
        $integralTemplateInfo = $nodeTemplateModel->messageTemplate($nodeId,$msgTemplateType);
        //如果未配置就直接退出方法了
        if(empty($integralTemplateInfo)){
            return;
        }
        //获取会员等级列表
        $cardLevel = M('tmember_cards')->where(array('node_id'=>$nodeId))->getField('id,card_name');
        //会员
        $memberInfoArr = M('tmember_info')->where(array('id'=>array('in',$memberStr)))->select();

        $addData = array();
        foreach($memberInfoArr as $key => $value){
            //个人中心
            $cont = array();
            if($integralTemplateInfo['system_msg'] == 1){
                $useTemplate = json_decode($integralTemplateInfo['system_msg_templet'],true);
                $cont['cont'] = str_replace('<-姓名->',$value['name'],$useTemplate);
                $cont['cont'] = str_replace('<-剩余积分->',$value['point'],$cont['cont']);
                if($msgTemplateType == 3){
                    $cont['cont'] = str_replace('<-消耗积分值->',$point,$cont['cont']);
                }else{
                    $cont['cont'] = str_replace('<-获得积分值->',$point,$cont['cont']);
                }
                $cont['title'] = '积分变动';
                $addData[] = array(
                        'node_id'       => $nodeId,
                        'msg_type'      => 1,
                        'content'       => json_encode($cont),
                        'status'        => 0,
                        'member_id'     => $value['id'],
                        'add_time'      => date('YmdHis')
                );
            }
            //短信
            if($integralTemplateInfo['sms_msg'] == 1){
                $useTemplate = json_decode($integralTemplateInfo['sms_msg_templet'],true);
                $cont = array('phone_no'=>$value['phone_no']);
                $cont['content'] = str_replace('<-姓名->',$value['name'],$useTemplate);
                if($msgTemplateType == 3){
                    $cont['content'] = str_replace('<-使用积分值->',$point,$cont['content']);
                }else{
                    $cont['content'] = str_replace('<-获得积分值->',$point,$cont['content']);
                }
                $addData[] = array(
                        'node_id'       => $nodeId,
                        'msg_type'      => 2,
                        'content'       => json_encode($cont),
                        'status'        => 0,
                        'member_id'     => $value['id'],
                        'add_time'      => date('YmdHis')
                );
            }
            //微信
            if($integralTemplateInfo['wx_msg'] == 1){
                $useTemplate = json_decode($integralTemplateInfo['wx_msg_templet'],true);
                if($msgTemplateType == 3){
                    $changeStr = -$point;
                    $eventStr = '手动减少积分';
                }else{
                    $changeStr = $point;
                    $eventStr = '手动增加积分';
                }
                foreach($useTemplate['content']['contentKeyVal'] as $key1 => $value1){
                    if($value1 == '会员姓名'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $value['name'];
                    }elseif($value1 == '会员手机号'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $value['phone_no'];
                    }elseif($value1 == '会员等级'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $cardLevel[$value['card_id']];
                    }elseif($value1 == '积分增减值'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $changeStr;
                    }elseif($value1 == '积分总额(变更后)'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $value['point'];
                    }elseif($value1 == '积分变动原因'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $eventStr;
                    }elseif($value1 == '时间'){
                        $useTemplate['content']['contentKeyVal'][$key1] = date('Y-m-d H:i:s');
                    }elseif($value1 == '--请选择--'){
                        $useTemplate['content']['contentKeyVal'][$key1] = '';
                    }
                }
                $useTemplate['openId'] =  $value['mwx_openid'];
                if(empty($value['mwx_openid'])){
                    $useTemplate['openId'] = $value['wx_openid'];
                }
                $useTemplate['content'] = $useTemplate['content']['contentKeyVal'];
                $addData[] = array(
                        'node_id'       => $nodeId,
                        'msg_type'      => 3,
                        'content'       => json_encode($useTemplate),
                        'status'        => 0,
                        'member_id'     => $value['id'],
                        'add_time'      => date('YmdHis')
                );
            }
        }
        //开始入库
        M()->startTrans();
        foreach($addData as $key => $value){
            $isOk = M('tintegral_change_notice')->add($value);
            if(!$isOk){
                log_write("批量增减积分失败：".print_r($value,true));
            }
        }
        M()->commit();
        return;
    }

    /**
     * 会员卡变动发送消息    单个会员
     *
     * @param  $memberInfo  array   会员卡变更相关信息
     * @param  $newCard     string  新会员卡
     *
     * @return void
     */
    public function rowMemberCardChange($memberInfo,$newCard)
    {
        
        //模板
        $nodeTemplateModel = $this->getIntegralChangeNoticeSetModel();
        $integralTemplateInfo = $nodeTemplateModel->messageTemplate($memberInfo['node_id'],1);
        //如果未配置就直接退出方法了
        if(empty($integralTemplateInfo)){
            return;
        }
        //会员卡
        $cardName = M('tmember_cards')->where(array('node_id'=>$memberInfo['node_id']))->getField('id,card_name');

        $addData = array();
        //会员中心的消息
        $cont = array();
        if($integralTemplateInfo['system_msg'] == 1){
            $useTemplate  = json_decode($integralTemplateInfo['system_msg_templet'], true);
            $cont['cont'] = str_replace('<-姓名->', $memberInfo['name'], $useTemplate);
            $cont['cont'] = str_replace('<-剩余积分->', $memberInfo['point'], $cont['cont']);
            $cont['cont'] = str_replace('<-会员卡名称->', $cardName[$newCard], $cont['cont']);
            $cont['title'] = '会员卡变动';
            $addData[] = array(
                    'node_id'       => $memberInfo['node_id'],
                    'msg_type'      => 1,
                    'content'       => json_encode($cont),
                    'add_time'      => date('YmdHis'),
                    'status'        => 0,
                    'member_id'     => $memberInfo['id']
            );
        }
        //短信消息
        if($integralTemplateInfo['sms_msg'] == 1){
            $useTemplate = json_decode($integralTemplateInfo['sms_msg_templet'],true);
            $data = array('phone_no'=>$memberInfo['phone_no']);
            $data['content'] = str_replace('<-姓名->', $memberInfo['name'], $useTemplate);
            $data['content'] = str_replace('<-会员卡名称->', $cardName[$newCard], $data);
            $addData[] = array(
                    'node_id'       => $memberInfo['node_id'],
                    'msg_type'      => 2,
                    'content'       => json_encode($data),
                    'add_time'      => date('YmdHis'),
                    'status'        => 0,
                    'member_id'     => $memberInfo['id']
            );
        }
        //微信模板消息
        if($integralTemplateInfo['wx_msg'] == 1){
            $useTemplate  = json_decode($integralTemplateInfo['wx_msg_templet'], true);
            foreach($useTemplate['content']['contentKeyVal'] as $key => $value){
                if($value == '会员姓名'){
                    $useTemplate['content']['contentKeyVal'][$key] = $memberInfo['name'];
                }elseif($value == '会员手机号'){
                    $useTemplate['content']['contentKeyVal'][$key] = $memberInfo['phone_no'];
                }elseif($value == '会员等级(变更前)'){
                    $useTemplate['content']['contentKeyVal'][$key] = $cardName[$memberInfo['card_id']];
                }elseif($value == '会员等级(变更后)'){
                    $useTemplate['content']['contentKeyVal'][$key] = $cardName[$newCard];
                }elseif($value == '时间'){
                    $useTemplate['content']['contentKeyVal'][$key] = date('Y-m-d H:i:s');
                }
            }
            $useTemplate['openId'] =  $memberInfo['mwx_openid'];
            if(empty($memberInfo['mwx_openid'])){
                $useTemplate['openId'] = $memberInfo['wx_openid'];
            }
            $useTemplate['content'] = $useTemplate['content']['contentKeyVal'];
            $addData[] = array(
                    'node_id'       => $memberInfo['node_id'],
                    'msg_type'      => 3,
                    'content'       => json_encode($useTemplate),
                    'add_time'      => date('YmdHis'),
                    'status'        => 0,
                    'member_id'     => $memberInfo['id']
            );
        }

        //开始入库
        M()->startTrans();
        foreach($addData as $key => $memberInfo){
            $isOk = M('tintegral_change_notice')->add($memberInfo);
            if(!$isOk){
                log_write("会员卡变更更新数据至tintegral_change_notice表失败！".print_r($memberInfo,true));
            }
        }
        M()->commit();

    }
    /**
     * 会员卡变动发送消息          批量的
     *
     * @param  $memberInfo  array   会员卡变更相关信息
     *
     * @return void
     */
    public function memberCardChange($memberInfo)
    {

        //模板
        $nodeTemplateModel = $this->getIntegralChangeNoticeSetModel();
        $integralTemplateInfo = $nodeTemplateModel->messageTemplate($memberInfo[0]['nodeId'],1);
        //如果未配置就直接退出方法了
        if(empty($integralTemplateInfo)){
            return;
        }
        //会员卡
        $cardName = M('tmember_cards')->where(array('node_id'=>$memberInfo[0]['nodeId']))->getField('id,card_name');
        $addData = array();
        foreach($memberInfo as $key => $value){
            //会员中心的消息
            $cont = array();
            if($integralTemplateInfo['system_msg'] == 1){
                $useTemplate  = json_decode($integralTemplateInfo['system_msg_templet'], true);
                $cont['cont'] = str_replace('<-姓名->', $value['name'], $useTemplate);
                $cont['cont'] = str_replace('<-剩余积分->', $value['nowIntegral'], $cont['cont']);
                $cont['cont'] = str_replace('<-会员卡名称->', $cardName[$value['newCard']], $cont['cont']);
                $cont['title'] = '会员卡变动';
                $addData[] = array(
                        'node_id'       => $value['nodeId'],
                        'msg_type'      => 1,
                        'content'       => json_encode($cont),
                        'add_time'      => date('YmdHis'),
                        'status'        => 0,
                        'member_id'     => $value['memberId']
                );
            }
            //短信消息
            if($integralTemplateInfo['sms_msg'] == 1){
                $useTemplate = json_decode($integralTemplateInfo['sms_msg_templet'],true);
                $data = array('phone_no'=>$value['phone_no']);
                $data['content'] = str_replace('<-姓名->', $value['name'], $useTemplate);
                $data['content'] = str_replace('<-会员卡名称->', $cardName[$value['newCard']], $data);
                $addData[] = array(
                        'node_id'       => $value['nodeId'],
                        'msg_type'      => 2,
                        'content'       => json_encode($data),
                        'add_time'      => date('YmdHis'),
                        'status'        => 0,
                        'member_id'     => $value['memberId']
                );
            }
            //微信模板消息
            if($integralTemplateInfo['wx_msg'] == 1){
                $useTemplate  = json_decode($integralTemplateInfo['wx_msg_templet'], true);
                foreach($useTemplate['content']['contentKeyVal'] as $key1 => $value1){
                    if($value1 == '会员姓名'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $value['name'];
                    }elseif($value1 == '会员手机号'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $value['phone_no'];
                    }elseif($value1 == '会员等级(变更前)'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $cardName[$value['oldCard']];
                    }elseif($value1 == '会员等级(变更后)'){
                        $useTemplate['content']['contentKeyVal'][$key1] = $cardName[$value['newCard']];
                    }elseif($value1 == '时间'){
                        $useTemplate['content']['contentKeyVal'][$key1] = date('Y-m-d H:i:s');
                    }
                }
                $useTemplate['openId'] =  $value['mwx_openid'];
                if(empty($value['mwx_openid'])){
                    $useTemplate['openId'] = $value['wx_openid'];
                }
                $useTemplate['content'] = $useTemplate['content']['contentKeyVal'];
                $addData[] = array(
                        'node_id'       => $value['nodeId'],
                        'msg_type'      => 3,
                        'content'       => json_encode($useTemplate),
                        'add_time'      => date('YmdHis'),
                        'status'        => 0,
                        'member_id'     => $value['memberId']
                );
            }
        }
        //开始入库
        M()->startTrans();
        foreach($addData as $key => $value){
            $isOk = M('tintegral_change_notice')->add($value);
            if(!$isOk){
                log_write("会员卡变更更新数据至tintegral_change_notice表失败！".print_r($value,true));
            }
        }
        M()->commit();
    }
    // 批量增减积分type=6 增加 type=7 减少
    public function integralBatchPointChange($type, $point, $member_id, $node_id) {
        // 增加
        if (empty($point) || empty($member_id)) {
            return false;
        }
        $member_arr = explode(',', $member_id);
        $member_count = count($member_arr);
        if ($type) {
            for ($i = 0; $i < $member_count; $i ++) {
                $res = $this->integralPointChangeOne($type, $point, 
                    $member_arr[$i], $node_id);
                if ($res === false) {
                    log_write("批量操作积分失败" . $member_id);
                    return false;
                }
            }
            $this->allMessageNotice($member_arr,$node_id,$type,$point);
            return true;
        }
    }

    public function integralPointChangeOne($type, $point, $member_id, $node_id) {
        $model = M("tmember_info");
        $res = $model->where(
            array(
                'node_id' => $node_id, 
                'id' => $member_id))->find();
        if (empty($res)) {
            log_write("查询不到会员" . $member_id);
            return false;
        }
        // 如果会员存在
        $data = array(
            'before_num' => $res['point'], 
            'change_num' => $point, 
            'trace_time' => date('YmdHis'), 
            'member_id' => $member_id, 
            'node_id' => $node_id);
        $member_data = array();
        if ($type == CommonConst::INTEGRAL_TYPE6) {
            $data['after_num'] = $res['point'] + $point;
            $data['remark'] = "批量增加";
            $data['type'] = CommonConst::INTEGRAL_TYPE6;
            $member_data['point'] = $data['after_num'];
        }
        if ($type == CommonConst::INTEGRAL_TYPE7) {
            if ((intval($res['point']) - intval($point)) < 0) {
                log_write("积分不够7" . $member_id);
                return false;
            }
            $data['after_num'] = $res['point'] - $point;
            $data['remark'] = "批量扣减";
            $data['type'] = CommonConst::INTEGRAL_TYPE7;
            $member_data['point'] = $data['after_num'];
        }
        $result = $model->where(
            array(
                'node_id' => $node_id, 
                'id' => $member_id))->save($member_data);
        if ($result === false) {
            log_write("更新会员积分失败!参数：" . print_r($member_data, true));
            return false;
        }
        // 保存积分流水
        $result_trace = M("tintegral_point_trace")->add($data);
        if ($result_trace === false) {
            log_write("积分流水失败!参数：" . print_r($data, true));
            return false;
        }
        return true;
    }
    // 关注送积分
    public function attentionPoints($nodeId, $openId, $channelId, 
        $weiXinAttentionRate) {
        // 新增会员
        $memberAdd = D('MemberInstall', 'Model');
        // 生成会员卡编号
        $option = array(
            'channel_id' => $channelId, 
            'openid' => $openId, 
            'point' => $weiXinAttentionRate);
        $MemberInfo = $memberAdd->wxTermMemberFlag($nodeId, $openId, 3, true, 
            $option);
        if ($MemberInfo === false) {
            log_write("通过openid新增会员失败！" . $openId);
            return false;
        }
        $integralRes = $this->integralPointChange('15', $weiXinAttentionRate, 
            $MemberInfo['id'], $nodeId, $openId, '');
        if ($integralRes === false) {
            log_write("微信关注送积分失败3！" . $openId);
            return false;
        }
        return $MemberInfo;
    }
}