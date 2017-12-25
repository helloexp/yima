<?php

/**
 * 会员招募SEREVICE
 *
 * @author Jeff Liu Class MemberRecruitService
 */
class MemberRecruitService {

    private static $default_award_msg = '恭喜您中奖了!';

    private static $default_no_award_notice = '很抱歉,您没有中奖!再接再厉吧！';

    /**
     * 0 正常 1 游戏 2 批量 3 拍码阅读 4 拍码调研 5 会员卡 7 会员礼品 9 爱拍
     */
    const DATA_FROM_MEMBER_CARD = 5;

    /**
     * 手机验证码过期时间
     */
    const VERIFY_CODE_EXPIRE_TIME = 120;

    /**
     * 自定义field 前缀
     */
    const CUSTMOM_HTML_PREFIX = 'member_';

    /**
     * 1:3级联动 0：mobile专用模式（该模式目前还有问题,）
     */
    const AREA_SELECT_STYLE = 1;

    /**
     *
     * @param $id
     * @param $batch_type
     * @return mixed
     */
    public static function getBatchChannelData($id, $batch_type) {
        $model = M('tbatch_channel');
        $map = array(
            'id' => $id, 
            'batch_type' => $batch_type);
        
        return $model->where($map)->find();
    }

    /**
     *
     * @param $m_id
     * @param $batch_type
     * @return mixed
     */
    public static function getMarketingInfo($m_id, $batch_type) {
        $batch_model = M('tmarketing_info');
        
        return $batch_model->where(
            array(
                'id' => $m_id, 
                'batch_type' => $batch_type))->find();
    }

    /**
     *
     * @param $node_id
     * @param $phone_no
     * @return mixed
     */
    public static function getMemberCardInfo($node_id, $phone_no) {
        // 获取会员卡信息
        $member_cards_info = M()->table("tmember_info i")->field(
            'i.name,i.phone_no,i.request_id,b.level_name,b.print_info,b.valid_day')
            ->join(
            "tmember_batch b ON i.node_id=b.node_id AND i.batch_no=b.batch_no")
            ->where("i.node_id='{$node_id}' AND i.phone_no='{$phone_no}'")
            ->find();
        
        return $member_cards_info;
    }

    /**
     *
     * @param $node_id
     * @return mixed
     */
    public static function getNodeInfo($node_id) {
        return M('tnode_info')->where("node_id={$node_id}")->getField(
            'node_short_name');
    }

    /**
     *
     * @param $node_id
     * @param $phone_no
     * @return mixed
     */
    public static function getMemberInfo($node_id, $phone_no) {
        return M('tmember_info')->where(
            "node_id ='{$node_id}' AND phone_no ='{$phone_no}'")->find();
    }

    /**
     *
     * @param $market_info
     * @return mixed
     */
    public static function getCollectQuestionInfo($m_id) {
        $question_list = M()->table("tcollect_question_new q")->field(
            'q.*,f.text,f.name,f.type,f.value_list,f.is_base_field')
            ->join('tcollect_question_field f ON f.id=q.field_id')
            ->where(array('q.m_id'=>$m_id, 'q.is_delete'=>'0'))
            ->order('sort asc')
            ->select();
        return $question_list;
    }

    /**
     *
     * @param $member_cards_info
     * @return mixed
     */
    public static function getBarcodeTrace($request_id) {
        return M('tbarcode_trace')->field('assist_number,barcode_bmp')
            ->where(
            "request_id={$request_id} AND data_from='" .
                 self::DATA_FROM_MEMBER_CARD . "'")
            ->find();
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    public static function addTmemberInfo($data) {
        return M('tmember_info')->add($data);
    }

    /**
     *
     * @param $batch_id
     * @param $batch_type
     * @param $field
     * @internal param $save_info
     */
    public static function marketingInfoIncr($batch_id, $batch_type, $field) {
        $result = M('tmarketing_info')->where(
            "id={$batch_id} and batch_type='{$batch_type}'")->setInc($field);
        return $result;

    }

    /**
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public static function saveMemberInfoById($id, $data) {
        return M('tmember_info')->where("id={$id}")->save($data);
    }

    /**
     *
     * @param $node_id
     * @param $sId
     * @return mixed
     */
    public static function getMemberLogin($node_id, $sId) {
        return M('tmember_login')->where("node_id={$node_id} AND s_id='{$sId}'")->find();
    }

    /**
     * 保持member_info 相关的数据
     *
     * @author Jeff Liu
     * @param string $where sql中的where条件
     * @param array $saveData 需要保持的数据
     * @return bool
     */
    public static function saveMemberLogin($where, $saveData) {
        return M('tmember_login')->where($where)->save($saveData);
    }

    /**
     * 发送微信验证码
     *
     * @author Jeff Liu
     * @param $saveInfo
     * @param $phoneNo
     * @param $updateId
     * @return string
     */
    public static function sendWeChatCode($saveInfo, $phoneNo, $updateId) {
        $send_str = '';
        // 更新彩信标题内容
        $sql = "UPDATE tgoods_info SET mms_title = goods_name,mms_text=print_text WHERE batch_no={$saveInfo['member_level']} AND source=3";
        $result = M()->execute($sql);
        if ($result === false) {
            log::write(
                "会员招募发码失败,原因:短信标题和内容更新失败 活动号:{$saveInfo['member_level']}");
        } else {
            $trans_id = get_request_id();
            import("@.Vendor.SendCode");
            $req = new SendCode();
            $result = $req->wc_send($saveInfo['node_id'], '', 
                $saveInfo['member_level'], $phoneNo, '5', $trans_id);
            if ($result === true) {
                $data = array(
                    'request_id' => $trans_id, 
                    'update_time' => date('YmdHis'));
                $res = self::saveMemberInfoById($updateId, $data);
                if ($res === false) {
                    log::write(
                        "会员招募发码成功,更新request_id失败;tmember_info表id:{$updateId},request_id:{$trans_id}");
                }
            } else {
                log::write("会员招募发码失败,原因:{$result}");
            }
            $send_str = ",系统将以短彩信形式下发给您对应等级的电子会员卡";
        }
        
        return $send_str;
    }

    /**
     * 中奖抽奖相关提示
     *
     * @author Jeff Liu
     * @param string $node_id
     * @param string $batch_type
     * @param int $batch_id
     *
     * @return array
     */
    public static function getAwardLevelMsg($node_id, $batch_type, $batch_id) {
        $cj_rule = M('tcj_rule');
        $cj_map = array(
            'node_id' => $node_id, 
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'status' => '1');
        $result = $cj_rule->where($cj_map)->find();
        if ($result && isset($result['cj_resp_text'])) {
            $cj_resp_text = $result['cj_resp_text'];
            $award_level_msg = explode('|', $cj_resp_text);
        } else {
            $award_level_msg = array(
                self::$default_award_msg);
        }
        
        if ($result && isset($result['no_award_notice'])) {
            $no_award_notice = $result['no_award_notice'];
            $no_award_notice = explode('|', $no_award_notice);
        } else {
            $no_award_notice = array(
                self::$default_no_award_notice);
        }
        
        return array(
            $award_level_msg, 
            $no_award_notice);
    }

    /**
     * 抽奖
     *
     * @author Jeff Liu
     * @param $id
     * @param $phoneNo
     * @param $sendStr
     * @param $awardLevelMsg
     * @param null $other
     * @param string $joinMode
     * @param string $nodeId
     *
     * @return string
     */
    public static function drawLottery($id, $phoneNo, $sendStr='', $awardLevelMsg, $other = null, $joinMode = '', $nodeId = '') {
        $result = array();
        // 去抽奖
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($id, $phoneNo, '', '', $other);
        $resp = $choujiang->send_code();
        log_write('会员招募抽奖结果：' . var_export($resp, true));
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $drawLotteryMsg = $awardLevelMsg[0][array_rand($awardLevelMsg[0])];
            if ($joinMode) { // 微信参与 直接重新发码
                $cjTraceId = isset($resp['cj_trace_id']) ? $resp['cj_trace_id'] : '';
                $requestId = isset($resp['request_id']) ? $resp['request_id'] : '';
                self::drawLotteryResend($cjTraceId, $phoneNo, $requestId, $nodeId);
            }
            $result['cjResult'] = '1';
            $result['goodsName'] = $resp['batch_name'];
            $result['prizeType'] = $resp['prize_type'];
            $result['batchClass'] = $resp['batch_class'];
            if($resp['prize_type'] == '4'){
               $result['integralNum'] = M('tintegal_get_detail')->where(array('id'=>$resp['integral_get_id']))->getField('integral_num'); 
               $result['node_id'] = $nodeId;
            }elseif($resp['prize_type'] == '3'){
                $result['bonusNum'] = M()->table("tbonus_use_detail tbud")
                    ->join('tbonus_detail tbd ON tbd.id = tbud.bonus_detail_id')
                    ->where(array('tbud.id'=>$resp['bonus_use_detail_id']))->getfield('tbd.amount');
                
                $mInfoId = M('tmarketing_info')->where(array('node_id' => $nodeId, 'batch_type' => '29'))->getfield('id');
                $channel_id = M('tchannel')->where(array('node_id' => $nodeId, 'type' => '4','sns_type' => '46'))->getField('id');
                $result['shopid'] = get_batch_channel($mInfoId, $channel_id, '29', $nodeId);
            }elseif($resp['prize_type'] == '2'){
                $result['card_ext'] = $resp['card_ext'];
                $result['card_id'] = $resp['card_id'];
            }
        } else {
            $drawLotteryMsg = $awardLevelMsg[1][array_rand($awardLevelMsg[1])];
            $result['cjResult'] = '0';
        }
        
        $msg = $sendStr == '' ? "{$drawLotteryMsg}!" : "{$sendStr}!<br/>{$drawLotteryMsg}!";
        $result['cjMsg'] = $msg;
        return $result;
    }

    /**
     * 重新发送
     *
     * @author Jeff Liu
     * @param $cjTraceId
     * @param $phone
     * @param $requestId
     * @param $nodeId
     */
    public static function drawLotteryResend($cjTraceId, $phone, $requestId, 
        $nodeId) {
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(
            array(
                'id' => $cjTraceId))->save(
            array(
                'send_mobile' => $phone));
        // 修改发码表的字段
        $result = M('tbarcode_trace')->where(
            array(
                'request_id' => $requestId))->save(
            array(
                'phone_no' => $phone));
        
        // 然后调用重发接口
        import("@.Vendor.CjInterface");
        $req = new CjInterface();
        $result = $req->cj_resend(
            array(
                'request_id' => $requestId, 
                'node_id' => $nodeId, 
                'user_id' => '00000000'));
    }

    /**
     * 保存相关信息
     *
     * @param $verifiedData
     * @return mixed
     */
    public static function saveRecruitData($verifiedData, $batch_type) {
        $save_info = $verifiedData['save_info'];
        $name = $verifiedData['name'];
        $phone_no = $verifiedData['phone_no'];
        $sex = $verifiedData['sex'];
        $member_info = $verifiedData['member_info'];
        $birthday = $verifiedData['birthday'];
        $nickname = $verifiedData['nickname'];
        $openid = $verifiedData['openid'];
        $custom_feld_data = $verifiedData['custom_feld_data'];
        $citycode = $verifiedData['citycode'];
        $address = $verifiedData['address'];
        $province_code = $verifiedData['province_code'];
        
        if ($openid) {
            $mData = D("MemberInstall")->wxTermMemberFlag($save_info['node_id'], 
                $openid, 2, false);
            if ($mData['phone_no'] != $phone_no) {
                $user_openid = array(
                    'wx_openid' => array(
                        'exp', 
                        ' NULL'), 
                    'mwx_openid' => array(
                        'exp', 
                        ' NULL'), 
                    'nickname' => array(
                        'exp', 
                        ' NULL'), 
                    'nickLogo' => array(
                        'exp', 
                        ' NULL'));
                $re = M('tmember_info')->where(
                    array(
                        'id' => $mData['id']))->save($user_openid);
            }
        }
        
        $year = '';
        $month = '';
        $day = '';
        if ($birthday) {
            $birthday_time = strtotime($birthday);
            $year = date('Y', $birthday_time);
            $month = date('m', $birthday_time);
            $day = date('d', $birthday_time);
        }
        
        // 查询招募活动招募会员成功后的会员卡类型
        $card_id = $verifiedData['mc_id'];
        $addTime = date('YmdHis');
        $data = array(
            'node_id' => $save_info['node_id'], 
            'name' => $name, 
            'phone_no' => $phone_no, 
            'batch_no' => $save_info['member_level'], 
            'sex' => $sex, 
            'birthday' => $birthday, 
            'years' => $year, 
            'month_days' => $month . $day, 
            'age' => self::cacluateAge($birthday), 
            'channel_id' => $save_info['channel_id'], 
            'batch_id' => $save_info['m_id'], 
            'wx_openid' => $openid, 
            'nickname' => $nickname, 
            'add_time' => $addTime,
            'citycode' => $citycode, 
            'address' => $address, 
            'province_code' => $province_code, 
            'card_id' => $card_id, 
            'card_update_time' => date('YmdHis'),
            'custom_field_data'=>json_encode($verifiedData['custom_feld_data'])
            );


        $customFieldData = $verifiedData['custom_feld_data'];

        M()->startTrans();

        if (empty($member_info)) { // 新用户注册

            if($customFieldData){
                foreach($customFieldData as $kk=>$vv){
                    $sql = "
UPDATE `tmember_attribute_stat` SET member_cnt=member_cnt+1 WHERE node_id= '".$save_info['node_id']."' AND
field_id IN (SELECT id FROM `tcollect_question_field` WHERE node_id= '".$save_info['node_id']."' AND NAME='$kk')
AND field_value=$vv";
                    $res = M()->execute($sql);
                    if(!$res){
                        M()->rollback();
                        return - 1015;
                    }
                }
            }

            $resultId = self::addTmemberInfo($data);
            if (! $resultId) {
                M()->rollback();
                
                return - 1015;
            }
            
            $updateId = $resultId;
            
            $mem_num = D("MemberInstall")->makeMemberCardNum(
                $save_info['node_id'], $updateId);
            if (! $mem_num) {
                M()->rollback();
                
                return - 1015;
            }

        } else { // 老用户更新 更新会员权益类型
            $customFieldData = M('tmember_info')->where(array('id'=>$member_info['id']))->getfield('custom_field_data');
            $customFieldData = json_decode($customFieldData, TRUE);

            $addFieldData = array();
            foreach($verifiedData['custom_feld_data'] as $key=>$val){
                if(strstr($key, 'member_') && $key != 'member_id'){
                    $addFieldData[$key] = $verifiedData['custom_feld_data'][$key];
                }
            }

            $saveFieldData = $addFieldData;

            //相同数据视为未修改 删除
            foreach ($customFieldData as $key=>$v1) {
                foreach($addFieldData as $key2=>$v2){
                    if($v1==$v2 && $key==$key2){
                        unset($customFieldData[$key]);
                        unset($addFieldData[$key2]);
                    }
                }
            }

            if($customFieldData){
                foreach($customFieldData as $kk=>$vv){
                    $sql = "
UPDATE `tmember_attribute_stat` SET member_cnt=member_cnt-1 WHERE node_id= '".$save_info['node_id']."' AND
field_id IN (SELECT id FROM `tcollect_question_field` WHERE node_id= '".$save_info['node_id']."' AND NAME='$kk')
AND field_value=$vv";
                    $res = M()->execute($sql);
                    if(!$res){
                        M()->rollback();
                        return - 1015;
                    }
                }
            }

            //取出当前用户的会员卡名称 将其保存在org_card_id中
            $orgCardId = M('tmember_info')->where('id ='.$member_info['id'])->getField('card_id');

            $data = array(
                    'name' => $name,
                    'sex' => $sex,
                    'birthday' => $birthday,
                    'years' => $year,
                    'month_days' => $month . $day,
                    'age' => self::cacluateAge($birthday),
                    'citycode' => $citycode,
                    'address' => $address,
                    'province_code' => $province_code,
                    'batch_no' => $save_info['member_level'],
                    'update_time' => date('YmdHis'),
                    'card_id' => $card_id,
                    'org_card_id' => $orgCardId,
                    'card_update_time' => date('YmdHis'),
                    'custom_field_data'=>json_encode($saveFieldData)
            );

            $res = self::saveMemberInfoById($member_info['id'], $data);
            if (! $res) {
                M()->rollback();
                
                return - 1015;
            }
            $resultId = $member_info['id'];

            foreach($addFieldData as $k=>$v){
                $sql = "
UPDATE `tmember_attribute_stat` SET member_cnt=member_cnt+1 WHERE node_id='".$save_info['node_id']."' AND
field_id IN (SELECT id FROM `tcollect_question_field` WHERE node_id='".$save_info['node_id']."' AND NAME='$k')
AND field_value=$v";
                $res = M()->execute($sql);
                if(!$res){
                    M()->rollback();
                    return - 1015;
                }
            }

            $updateId = $member_info['id'];
            
            if (empty($member_info['member_num'])) {
                $mem_num = D("MemberInstall")->makeMemberCardNum(
                    $save_info['node_id'], $resultId);
                if (! $mem_num) {
                    M()->rollback();
                    return - 1015;
                }
            }
        }

        //更新会员招募流水表tmember_join_trace
        //招募会员活动id tmarketing_info id 对应 tbatch_channel 的batch_id
        $traceData = array(
                'member_id'=>$resultId,
                'node_id'=>$save_info['node_id'],
                'm_id'=>$save_info['m_id'],
                'card_id' => $card_id,
                'add_time' => $addTime
        );

        $result = M('tmember_join_trace')->add($traceData);


        // 更新招募的会员数量
        $result = self::marketingInfoIncr($save_info['m_id'], $batch_type, 'member_sum');
        if(!$result){
            M()->rollback();
            return - 1015;
        }

        // 微信会员更新手机号
        if (! empty($save_info['s_id'])) {
            $result = self::saveMemberLogin("s_id='{$save_info['s_id']}'", 
                array(
                    'phone_no' => $phone_no));
            if ($result === false) {
                M()->rollback();
                return - 1015;
            }
        }
        M()->commit();
        //发送消息
        if($res){
            D('IntegralPointTrace')->rowMemberCardChange($member_info,$card_id);
        }

        return $updateId;
    }

    /**
     * 通过生日计算年龄
     *
     * @param $mydate
     * @return bool|string
     */
    public static function cacluateAge($mydate) {
        if (empty($mydate)) {
            return '0';
        }
        $birth = date('Y-m-d', strtotime($mydate));
        list ($by, $bm, $bd) = explode('-', $birth);
        $cm = date('n');
        $cd = date('j');
        $age = date('Y') - $by - 1;
        if ($cm > $bm || $cm == $bm && $cd > $bd) {
            $age ++;
        }
        
        return $age;
    }

    /**
     * 验证微信会员
     *
     * @author Jeff Liu
     * @param $node_id
     * @param $sId
     * @param $login_time
     * @return int|mixed
     */
    public static function verifyWeChatMember($node_id, $sId, $login_time) {
        // 获取password
        $login_Info = self::getMemberLogin($node_id, $sId);
        if (! $login_Info) {
            return - 1001;
        }
        // 校验sid
        if ($sId != md5($login_time . $login_Info['password'])) {
            return - 1002;
        }
        // 过期校验
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        $expired = C('WeixinServ.SID_TIME');
        if (time() - strtotime($login_time) > ($expired + 5) * 60) {
            return - 1004;
        }
        
        return $login_Info;
    }

    /**
     * 会员卡信息处理
     *
     * @author Jeff Liu
     * @param $member_cards_info
     * @return array
     */
    public static function memberCardsInfoProcess($member_cards_info) {
        $ret = array();
        if ($member_cards_info) {
            // 处理有效期
            if (empty($member_cards_info['request_id'])) {
                $member_cards_info['request_id'] = '商家还没有下发该等级的电子会员卡给您!';
                $code_info = array();
            } else { // 获取二维码和辅助码
                $code_info = self::getBarcodeTrace(
                    $member_cards_info['request_id']);
                $begin_data = dateformat(
                    substr($member_cards_info['request_id'], 0, 8), 'Y-m-d');
                $end_data = date('Y-m-d', 
                    strtotime($begin_data) +
                         $member_cards_info['valid_day'] * 24 * 3600);
                $member_cards_info['request_id'] = "有效期：{$begin_data}&nbsp;-&nbsp;{$end_data}";
            }
            
            $code_img = '';
            if (isset($code_info['barcode_bmp'])) {
                import('@.Vendor.ImageHelper');
                $code_img = base64_encode(
                    ImageHelper::wbmp2other(
                        base64_decode($code_info['barcode_bmp']), 'png'));
            }
            
            $ret['code_img'] = $code_img;
            $ret['member_cards_info'] = $member_cards_info;
            $ret['is_show'] = 1;
        }
        
        return $ret;
    }

    /**
     * 验证提交表单
     *
     * @author Jeff Liu
     * @return mixed
     */
    public static function verifyRecruit() {
        $save_info = session('saveInfo');
        if (empty($save_info)) { // saveInfo 信息丢失
            return array('errno' => - 1022);
        }
        $id = I('id', null);
        
        if ($save_info['id'] != $id) { // 参数错误
            return array(
                'errno' => - 1006);
        }
        
        $question_field_list = self::getCollectQuestionInfo($save_info['m_id']);
        $field_requied_mapping = array();
        foreach ($question_field_list as $question_id => $question_field) {
            $name = $question_field['name'];
            $is_required = $question_field['is_required'];
            
            $field_requied_mapping[$name] = $is_required;
            $question_field_list[$name] = $question_field;
            unset($question_field_list[$question_id]);
        }
        
        $phone_no = I('post.phone_no', null, 'mysql_real_escape_string');
        if ($phone_no && ! check_str($phone_no,array('null' => false, 'strtype' => 'mobile'), $error)) {
            return array('errno' => - 1008,'errmsg' => $error);
        }
        
        // 手机验证码
        $check_code = I('post.check_code', null);
        if ($check_code && ! check_str($check_code, 
            array(
                'null' => false), $error)) {
            return array(
                'errno' => - 1007, 
                'errmsg' => $error);
        }
        $phone_check_code = session('checkCode');
        if (! is_production() && $check_code == '1111') {
            // 为 测试环境 1111视为正确
        } else {
            if (empty($phone_check_code) ||
                 $phone_check_code['number'] != $check_code) { // 手机验证码不正确
                return array(
                    'errno' => - 1009);
            }
            if (time() - $phone_check_code['add_time'] >
                 self::VERIFY_CODE_EXPIRE_TIME) { // 手机验证码已经过期
                return array(
                    'errno' => - 1010);
            }
        }
        
        foreach($field_requied_mapping as $key=>$val){
            if($val == '1'){
                switch($key){
                    case 'name':
                        $name = I('post.name', null);
                        if (! check_str($name, array('null' => false, 'maxlen_cn' => '20'), $error)) {
                            return array('errno' => - 1012, 'errmsg' => $error);
                        }
                        break;
                        
                    case 'sex':
                        $sex = I('post.sex', null);
                        $sex = (int) $sex ? $sex : null;
                        if (! check_str($sex, 
                            array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'minval' => '0', 
                                'maxval' => '3'), $error)) {
                            return array('errno' => - 1014,'errmsg' => $error);
                        }
                        break;
                        
                    case 'area':
                        if (self::AREA_SELECT_STYLE) {
                            $province = I('post.province');
                            $city = I('post.city');
                            $town = I('post.town');
                        } else {
                            $hidden_address = I('post.hidden_address');
                            list ($province, $city, $town) = explode(',', $hidden_address);
                        }

                        if (! check_str($province, 
                            array(
                                'null' => false, 
                                'minlen_cn' => '2'), $error)) {
                            return array(
                                'errno' => - 1019, 
                                'errmsg' => $error);
                        }
                        if (! check_str($city, 
                            array(
                                'null' => false, 
                                'minlen_cn' => '2'), $error)) {
                            return array(
                                'errno' => - 1020, 
                                'errmsg' => $error);
                        }
                        if (! check_str($town, 
                            array(
                                'null' => false, 
                                'minlen_cn' => '2'), $error)) {
                            return array(
                                'errno' => - 1021, 
                                'errmsg' => $error);
                        }
                        break;
                        
                    case 'birthday':
                        $birthday = I('post.birthday', null);
                        if ($birthday) { // 用户传递了日期
                            $partern = '|\d+|';
                            preg_match_all($partern, $birthday, $match);
                            if (isset($match[0]) && count($match[0]) == 3) { 
                                // 输入了 年月日 比如 2015年12月11日 或者 2015-12-11 ios andriod 不一样
                                $birthday = $match[0][0] . $match[0][1] . $match[0][2];
                                $_POST['birthday'] = $birthday;
                            }
                        }
                        if (! check_str($birthday,array('null' => false,'strtype' => 'datetime'), $error)) {
                            return array('errno' => - 1013);
                        }
                        break;
                    default:
                        $postString = I("post.{$key}", 'null', 'string');
                        if($postString == ''){
                            return array('errno'=>-1075, 'errmsg'=>$question_field_list[$key]['text']);
                        }
                        break;
                }
            }
        }
        return true;
    }

    /**
     *
     * @author Jeff Liu
     * @param $wxSess
     * @return array
     */
    public static function buildRecruitData($wxSess) {
        $save_info = session('saveInfo');
        $custom_feld_data = self::getCustomFieldData(); // todo 自定义字段还没有实现
        $id = I('id', null);
        $mc_id = I('mc_id', null); // 招募会员到指定会员卡id(类型)
        
        $question_field_list = self::getCollectQuestionInfo($save_info['m_id']);
        $field_requied_mapping = array();
        foreach ($question_field_list as $question_id => $question_field) {
            $name = $question_field['name'];
            $is_required = $question_field['is_required'];
            
            $field_requied_mapping[$name] = $is_required;
            $question_field_list[$name] = $question_field;
            unset($question_field_list[$question_id]);
        }
        
        $phone_no = I('post.phone_no', null, 'mysql_real_escape_string');
        $name = I('post.name', null);
        $sex = I('post.sex', null);
        $sex = (int) $sex ? $sex : null;
        
        if (isset($question_field_list['area'])) {
            if (self::AREA_SELECT_STYLE) {
                $province = I('post.province');
                $city = I('post.city');
                $town = I('post.town');
                $address = '';
            } else {
                $hidden_address = I('post.hidden_address');
                list ($province, $city, $town) = explode(',', $hidden_address);
                $address = I('post.address');
            }
            
            $address_info = M('tcity_code')->where(
                array(
                    'province_code' => $province, 
                    'city_code' => $city, 
                    'town_code' => $town))->getField('province,city,town');
            if (is_array($address_info)) {
                list ($province_name, $final_address) = each($address_info);
                $address = $final_address['province'] . $final_address['city'] .
                     $final_address['town'];
            }
            $citycode = $province . $city . $town;
        } else {
            $address = '';
            $province = '';
        }
        $member_info = self::getMemberInfo($save_info['node_id'], $phone_no);
        $birthday = I('post.birthday', null);
        $openid = null;
        $nickname = '';
        if ($wxSess) {
            $openid = isset($wxSess['openid']) ? $wxSess['openid'] : null;
            $nickname = isset($wxSess['nickname']) ? $wxSess['nickname'] : '';
        }
        
        return array(
            'save_info' => $save_info, 
            'name' => $name, 
            'birthday' => $birthday, 
            'phone_no' => $phone_no, 
            'sex' => $sex, 
            'citycode' => $citycode, 
            'province_code' => $province, 
            'address' => $address, 
            'member_info' => $member_info, 
            'id' => $id, 
            'openid' => $openid, 
            'nickname' => $nickname, 
            'custom_feld_data' => $custom_feld_data, 
            'mc_id' => $mc_id);
    }

    /**
     * 获得自定义字段相关数据
     *
     * @author Jeff Liu @date 2015-07-08
     * @return array
     */
    private static function getCustomFieldData() {
        $post = I('post.');

        $origin_recruit = array();
        foreach ($post as $post_key => $post_value) {
            if (strpos($post_key, self::CUSTMOM_HTML_PREFIX) === 0) {
                $origin_recruit[$post_key] = $post_value;
            }
        }
        return $origin_recruit;
    }
    
    /**
     * 会员招募自定义字段返回数组
     * @param string $fieldVal
     * @return array
     */
    public function formatCustomFieldArray($fieldVal){
        $result = array();
        $value_list = explode('|',$fieldVal);
        foreach($value_list as $val){
            $tempArray = explode(':', $val);
            $result[$tempArray[0]] = $tempArray[1];
        }
        return $result;
    }
    
}