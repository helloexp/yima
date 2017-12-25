<?php

/**
 * 会员中心控制 model
 *
 * @author : kongyan<kongyan@imageco.com.cn> Date: 2015/11/02
 */
class MemberInstallModel extends BaseModel {
    // 单个会员增加
    protected $tableName = '__NONE__';
    public function MemberAddOne($memberArr) {
        $memberNum = $this->makeMemberNumber($memberArr['node_id']);
        $mc_str = $this->makeMemberNumberCode($memberArr['node_id'], $memberNum);
        $qrArray = array(
            'node_id' => $memberArr['node_id'], 
            'membercard_num' => $memberNum,
            'qr_code' => $mc_str, 
            'add_time' => date('YmdHis'));
        $result = M('tmember_qrcode')->add($qrArray);
        if ($result === false) {
            return false;
        }
        $list = M("tmember_cards")->where(
            array(
                'node_id' => $memberArr['node_id'], 
                'acquiesce_flag' => 1))->find();
        if (empty($list)) {
            log_write("无默认会员卡:" . $memberArr['node_id']."sql:".M()->getLastSql());
            return false;
        }
        $data = array(
            'node_id' => $memberArr['node_id'], 
            'phone_no' => $memberArr['MemberPhone'], 
            'status' => '0', 
            'add_time' => date('YmdHis'), 
            'channel_id' => $memberArr['channel_id'],
            'batch_id'   => $memberArr['batch_id'], 
            'card_id' => $list['id'],
            'member_num' => $memberNum);
        if ($memberArr['type'] == '1') {
            $data['mwx_openid'] = $memberArr['openid'];
        } elseif ($memberArr['type'] == '2') {
            $data['wx_openid'] = $memberArr['openid'];
        }
        if ($memberArr['nickname']) {
            $data['nickname'] = $memberArr['nickname'];
        }
        if ($memberArr['nickLogo']) {
            $data['nickLogo'] = $memberArr['nickLogo'];
        }
        $result = M('tmember_info')->add($data);
        if ($result === false) {
            return false;
        }
        return $result;
    }

    public function MemberAddOpenIdOne($memberArr) {
        if ($memberArr) {
            $memberId = M("tmember_info")->add($memberArr);
            if ($memberId === false) {
                log_write("微信关注新增会员失败" . print_r($memberArr, true));
                return false;
            }
            return $memberId;
        }
    }
    // 查询会员是否领取过富文本活动积分
    public function receiveTextPoint($openId, $nodeId, $mId, $channelId) {
        log_write("Openid：".$openId);
        if(empty($openId)){
            log_write("openid不能为空！");
            return false;
        }
        $integralNum = $this->configurationIntegralValue($nodeId, $mId);
        if ($integralNum == '' || $integralNum <= 0) {
            log_write("营销活动送积分数小于0！");
            return false;
        }
        $memberInfo = M("tmember_info")->where(
            array(
                'node_id' => $nodeId, 
                'wx_openid' => $openId))->find();
        if ($memberInfo) {
            $map = array(
                'node_id' => $nodeId, 
                "member_id" => $memberInfo['id'], 
                "relation_id" => $mId, 
                "type" => '14');
            $res = M("tintegral_point_trace")->where($map)->find();
            if ($res) {
                log_write("该用户已经过该活动积分！");
                return false;
            }
        }
        if (empty($memberInfo)) {
            $memberData = array(
                'node_id' => $nodeId, 
                'wx_openid' => $openId, 
                'channel_id' => $channelId,
                    'batch_id' => $mId,
                    'add_time' => date('YmdHis'));
            $res = M("tmember_info")->add($memberData);
            log_write("addmember,新增会员".print_r($memberData,true));
            $memberInfo['id'] = $res;
            if ($res === false) {
                log_write("新增会员失败！");
                return false;
            }
        }
        $integralPoint = new IntegralPointTraceModel();
        $integralStatus = $integralPoint->integralPointChange('14', 
            $integralNum, $memberInfo['id'], $nodeId, $mId, '');
        if ($integralStatus === false) {
            log_write("积分领取失败！" . print_r($memberInfo, true));
            return false;
        }
        // 新增行为数据
        $res = D("MemberBehavior")->addBehaviorType($memberInfo['id'], $nodeId, 
            16, $integralNum, $mId);
        if ($res === false) {
            log_write("新增行为数据失败！" . print_r($memberInfo, true));
            return false;
        }
        return true;
    }
    // 校验是否送积分
    public function configurationIntegralValue($nodeId, $mId) {
        // 标准校验营销活动
        if (get_wc_version($nodeId) != 'v4') {
            $powers = M("tnode_info")->where(
                array(
                    'node_id' => $nodeId))->getField('pay_module');
            if (empty($powers)) {
                log_write("未开通积分营销模块，无法领取！");
                return false;
            }
            $powers = explode(",", $powers);
            if (! in_array("m4", $powers)) {
                log_write("未开通积分营销模块，无法领取！");
                return false;
            }
        }
        $configData = M("tmarketing_info")->where(
            array(
                'node_id' => $nodeId, 
                'id' => $mId))->find();
        if (empty($configData)) {
            log_write("该活动未配置！");
            return false;
        }
        $configData = unserialize($configData['config_data']);
        if ($configData['integral_sign'] == 0 || $configData['integral_num'] == 0) {
            log_write("活动已经结束，不赠送积分！");
            return false;
        }
        return $configData['integral_num'];
    }
    // 生成会员卡号
    public function makeMemberNumber($node_id) {
        $sql = "select _nextval('member_{$node_id}') AS member_num FROM DUAL";
        $seq = M()->query($sql);
        if ($seq[0]['member_num'] == 0) {
            $result = M('tsequence')->add(
                array(
                    'name' => 'member_' . $node_id, 
                    'current_value' => 1000000000, 
                    '_increment' => 1));
            $sql = "select _nextval('member_{$node_id}') AS member_num FROM DUAL";
            $seq = M()->query($sql);
        }
        return $seq[0]['member_num'];
    }
    
    // 生成会员码
    public function makeMemberNumberCode($node_id, $memberNum) {
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $path = APP_PATH . 'Upload/MemberCode/';
        $name = $node_id . $memberNum;
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $ecc = 'H';
        $size = 10;
        $filename = $path . $name . '.png';
        QRcode::png($memberNum, $filename, $ecc, $size, 0, false);
        $mc_str = base64_encode(file_get_contents($filename));
        if ($mc_str) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        return $mc_str;
    }
    
    // 获取机构下所有会员
    public function getMembers($node_id) {
        $result = M('tmember_info')->where(
            array(
                'node_id' => $node_id, 
                'status' => 0))->select();
        if (! $result) {
            return false;
        }
        
        return $result;
    }
    
    // 获取会员基本信息 同 getMemberDetailMsg 相同，可以优化下
    public function getMemberMsg($member_id) {
        $result = M('tmember_info')->where(
            array(
                'id' => $member_id, 
                'status' => 0))->find();
        return $result;
    }

    public function getMemberDetailMsg($member_id) {
        $result = M()->table("tmember_info i")
            ->join('tcity_code c ON i.citycode = c.path')
            ->field('i.*, c.province, c.city, c.town')
            ->where(array('i.id' => $member_id, 'i.status' => 0))
            ->find();
        return $result;
    }
    
    /**
     * 获取会员招募自定义字段信息
     * @param array $condition  后台传过来的 用户已填写的信息
     * @param string $node_id
     * @param int $isAll        是否显示所有自定义字段
     */
    public function getCustomFieldInfo($condition = array(),$node_id, $isAll = 0,$sta = ''){
        $memberRecruitService = D('MemberRecruit','Service');
        
        $customFieldSql = "SELECT `text`,`value_list`,`name` FROM `tcollect_question_field` WHERE ( `node_id` = '{$node_id}' OR `node_id` is null )";
        $customField = M()->query($customFieldSql);
        
        if($isAll == 0){
            $customFieldConfig = M('tmember_center_config')
                ->where(array('node_id'=>$node_id))
                ->getfield('custom_field_config');
            $customFieldConfig = json_decode($customFieldConfig, TRUE);
        }
        
        foreach($customField as $key=>$val){
            if(isset($condition[$val['name']])){
                $customFieldVal = $memberRecruitService->formatCustomFieldArray($val['value_list']);
                $customField[$key]['value'] = $customFieldVal[$condition[$val['name']]];
            }

            if($sta == 1){
                //原本姓名性别生日地区都是基础属性 固定显示 现需求有变 除了手机号其他均可控制可见度
                //但是会员详细里面显示的自定义字段信息必须把这个基础属性去掉 因此添加一个$sta变量
                if(in_array($val['name'], array( 'phone_no')) && $isAll != 0){
                    unset($customField[$key]);
                }
            }else{
                if(in_array($val['name'], array('name','birthday', 'area', 'phone_no', 'sex')) && $isAll != 0){
                    unset($customField[$key]);
                }
            }

            
            if($isAll == 0){
                if(!in_array($val['name'], array('name','birthday', 'area', 'phone_no')) 
                        && $customFieldConfig[$val['name']] == 1){
                    $customFieldVal = $memberRecruitService->formatCustomFieldArray($val['value_list']);
                    $customField[$key]['value_list'] = $customFieldVal;
                }elseif($customFieldConfig[$val['name']] == 0){
                    unset($customField[$key]);
                }
            }
        }
        return $customField;
    }
    
    // 判断会员是否已有二维码图片
    public function getMemberCodeFlag($node_id, $member_num) {
        $result = M("tmember_qrcode")->where(
            array(
                'membercard_num' => $member_num, 
                'node_id' => $node_id))->find();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    // 根据会员id二维码生成
    public function makeMemberCode($node_id, $member_id) {
        $member = $this->getMemberMsg($member_id);
        $codeFlag = $this->getMemberCodeFlag($node_id, $member['member_num']);
        if ($codeFlag) {
            return $codeFlag['id'];
        }
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $path = APP_PATH . 'Upload/MemberCode/';
        $name = $node_id . $member['member_num'];
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $ecc = 'H';
        $size = 10;
        $filename = $path . $name . '.png';
        QRcode::png($member['member_num'], $filename, $ecc, $size, 0, false);
        
        $mc_str = base64_encode(file_get_contents($filename));
        $qrArray = array(
            'node_id' => $node_id, 
            'membercard_num' => $member['member_num'], 
            'qr_code' => $mc_str, 
            'add_time' => date('YmdHis'));
        $result = M('tmember_qrcode')->add($qrArray);
        if ($result) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        } else {
            return false;
        }
        return $result;
    }
    
    // 根据手机号判断会员是否存在
    public function telTermMemberFlag($node_id, $phone) {
        $result = M('tmember_info')->where(
            array(
                'node_id' => $node_id, 
                'phone_no' => $phone, 
                'status' => 0))->find();
        if (! $result) {
            return false;
        }
        
        return $result;
    }
    
    // 根据 (翼码) 或者 (商户) 授权的openid 判断会员是否存在，不存在新增
    // Condition查询的条件(手机号或者openid) nom 1手机 2翼码 3商户 isAdd true 会员不存在就新增
    public function wxTermMemberFlag($node_id, $condition, $nom, $isAdd = false, 
        $option = array(),$payType = 0) {

        $str = 'mwx_openid';
        switch($payType){
            case 1:
                $str = 'mwx_openid';
                break;
            case 2:
                $str = 'alipay_acount';
                break;
            case 3:
                $str = 'pay_openid';
                break;
        }

        $where = array(
            'node_id' => $node_id);
        if ($nom == 1) {
            $where['phone_no'] = $condition;
        } else if ($nom == 2) {
            $where['wx_openid'] = $condition;
        } else if ($nom == 3) {
            $where['mwx_openid'] = $condition;
        }
        
        $result = M('tmember_info')->where($where)->find();
        if (! $result) {
            if ($isAdd) {
                $data = array(
                    'node_id' => $node_id, 
                    'name' => '', 
                    'sex' => '1', 
                    'years' => date('Y'), 
                    'month_days' => date('md'), 
                    'status' => '0', 
                    'add_time' => date('YmdHis'), 
                    'request_id' => '', 
                    'channel_id' => isset($option['channel_id']) ? $option['channel_id'] : $payType,
                    'batch_id' => isset($option['batch_id']) ? $option['batch_id'] : 0
                );
                if ($nom == 1) {
                    $data['phone_no'] = $condition;
                    $data[$str] = $option['openid'];
                    $list = M("tmember_cards")->where(
                        array(
                            'node_id' => $node_id, 
                            'acquiesce_flag' => 1))->find();
                    if (empty($list)) {
                        log_write("无默认会员卡:" . $node_id."sql:".M()->getLastSql());
                        return false;
                    }
                    $data['card_id'] = $list['id'];
                    
                    $member_num = $this->makeMemberNumber($node_id);
                    $data['member_num'] = $member_num;
                } else if ($nom == 2) {
                    $data['wx_openid'] = $condition;
                } else if ($nom == 3) {
                    $data['mwx_openid'] = $condition;
                }
                
                $member_id = M('tmember_info')->add($data);
                if ($member_id === false) {
                    log_write("新增会员失败!机构=" . $node_id . "，参数=" . $condition);
                    return false;
                }
                $memberData = $this->getMemberMsg($member_id);
                return $memberData;
            }
            return false;
        }
        
        return $result;
    }
    // 根据 (翼码) 或者 (商户) 授权的openid 判断会员是否存在，不存在新增
    // Condition查询的条件(手机号或者openid) nom 1手机 2翼码 3商户 isAdd true 会员不存在就新增
    public function wxTermMemberFlagByIntegral($node_id, $condition, $nom, $isAdd = false,
            $option = array(),$payType = 0) {
        //2为支付宝，3为微信
        $map=array(
                'node_id'=>$node_id,
                'account'=>$option['openid']
        );
        if($payType==2){
            $map['type']='1';
        }elseif($payType==1){
            $map['type']='0';
        }
        $where = array(
                'node_id' => $node_id);
        if ($nom == 1) {
            $where['phone_no'] = $condition;
        }
        $result = M('tmember_info')->where($where)->find();
        if (! $result) {
            if ($isAdd) {
                $data = array(
                        'node_id' => $node_id,
                        'name' => '',
                        'sex' => '1',
                        'years' => date('Y'),
                        'month_days' => date('md'),
                        'status' => '0',
                        'add_time' => date('YmdHis'),
                        'request_id' => '',
                        'channel_id' =>$payType,
                        'batch_id' => isset($option['batch_id']) ? $option['batch_id'] : 0
                );
                if ($nom == 1) {
                    $data['phone_no'] = $condition;
                    $list = M("tmember_cards")->where(
                            array(
                                    'node_id' => $node_id,
                                    'acquiesce_flag' => 1))->find();
                    if (empty($list)) {
                        log_write("无默认会员卡:" . $node_id."sql:".M()->getLastSql());
                        return false;
                    }
                    $data['card_id'] = $list['id'];

                    $member_num = $this->makeMemberNumber($node_id);
                    $data['member_num'] = $member_num;
                }
                $member_id = M('tmember_info')->add($data);
                if ($member_id === false) {
                    log_write("新增会员失败!机构=" . $node_id . "，参数=" . $condition);
                    return false;
                }
                //新增完在新增
                $hsdAccount=M("tmember_account")->where($map)->find();
                if(!$hsdAccount){
                    $saveData=$map;
                    $saveData['member_id']=$member_id;
                    $saveData['add_time']=date('YmdHis');
                    $resStatus=M("tmember_account")->add($saveData);
                    if($resStatus===false){
                        log_write("新增支付表失败!机构=" . $node_id . "，参数=" . $condition);
                        return false;
                    }
                }
                $memberData = $this->getMemberMsg($member_id);
                return $memberData;
            }
            return false;
        }

        return $result;
    }
    // 获取会员卡信息(查询机构下所有会员卡)
    public function getMemberCards($node_id) {
        $memCard = M('tmember_cards')->where(
            array(
                'node_id' => $node_id))->select();
        if (! $memCard) {
            return false;
        }
        
        return $memCard;
    }
    
    // 获取机构默认会员卡信息
    public function getDefMemberCards($node_id) {
        $memCard = M('tmember_cards')->where(
            array(
                'node_id' => $node_id, 
                'acquiesce_flag' => 1))->find();
        if (! $memCard) {
            return false;
        }
        
        return $memCard;
    }
    
    // 获取会员卡信息(查询指定会员id 会员卡)
    public function getMemberCardsId($node_id, $card_id) {
        $memCard = M('tmember_cards')->where(
            array(
                'node_id' => $node_id, 
                'id' => $card_id))->find();
        if (! $memCard) {
            return false;
        }
        
        return $memCard;
    }
    
    // 获取会员卡信息(查询指定会员卡名称会员)
    public function getMemberCardsName($node_id, $card_name) {
        $memCard = M('tmember_cards')->where(
            array(
                'node_id' => $node_id, 
                'card_name' => $card_name))->find();
        if (! $memCard) {
            return false;
        }
        
        return $memCard;
    }
    
    // 查询会员所属的会员卡类型id
    public function getMemberIdToCard($node_id, $member_id) {
        $memberInfo = $this->getMemberMsg($member_id);
        if (! $memberInfo) {
            return false;
        }
        
        $result = M('tmember_cards')->where(
            array(
                'id' => $memberInfo['card_id']))->find();
        if (! $result) {
            return false;
        }
        
        return $result;
    }
    
    // 会员卡号生成
    public function makeMemberCardNum($node_id, $member_id) {
        $mem_num = M("tmember_info")->where(
            array(
                'id' => $member_id))->getField("member_num");
        if ($mem_num) {
            return false;
        }
        
        $result = M('tsequence')->where(
            array(
                'name' => 'member_' . $node_id))->count();
        if ($result == 0) {
            $result = M('tsequence')->add(
                array(
                    'name' => 'member_' . $node_id, 
                    'current_value' => 1000000000, 
                    '_increment' => 1));
            if (! $result) {
                return false;
            }
        }
        $sql = "select _nextval('member_{$node_id}') AS m_num FROM DUAL";
        $seq = M()->query($sql);
        
        $res = M('tmember_info')->where(
            array(
                'id' => $member_id))->save(
            array(
                'member_num' => $seq[0]['m_num']));
        if (! $res) {
            return false;
        }
        
        return true;
    }
    
    // 获取商户设置的会员中心的功能展示
    public function getMemberInstallShow($node_id) {
        $result = M('tmember_center_config')->where(
            array(
                'node_id' => $node_id))->find();
        if (! $result) {
            return false;
        }
        
        return $result;
    }

    //基础属性初始化
    public function ini_custom_field_config($node_id)
    {
        $model = M('tmember_center_config');
        $whe['node_id'] = $node_id;
        $res = $model->where($whe)->getField('custom_field_config');
        if(!$res){
            $data = array();
            $customFieldsData = array();
            $customFieldsData['name'] = 1;
            $customFieldsData['birthday'] = 1;
            $customFieldsData['sex'] = 1;
            $customFieldsData['area'] = 1;
            $data['custom_field_config'] = json_encode($customFieldsData);
            $model->where($whe)->save($data);
        }
    }
    
    // 获取活动信息
    public function getBatchData($batchIdStr) {
        $result = M('tmarketing_info')->where(
            array(
                '_string' => 'id in (' . $batchIdStr . ')'))
            ->field('id, name')
            ->select();
        
        if (! $result) {
            return false;
        }
        
        return $result;
    }

    public function getBatchDataWap($batchIdStr) {
        $result = M('tmarketing_info')->where(
            array(
                '_string' => 'id in (' . $batchIdStr . ')'))
            ->field('id, batch_type, name, wap_title, wap_info')
            ->select();
        if (! $result) {
            return false;
        }
        
        foreach ($result as $k => $v) {
            $ret = M("tbatch_channel")->where(
                array(
                    'batch_type' => $v['batch_type'], 
                    'batch_id' => $v['id'], 
                    'status' => '1'))
                ->field('id label_id')
                ->order('add_time desc')
                ->select();
            $result[$k]['label_id'] = $ret[0]['label_id'];
        }
        
        return $result;
    }
    
    // 获取红包信息
    public function getRedData($redIdStr) {
        $result = M()->table("tbonus_info b")->join(
            "tbonus_detail d on b.id = d.bonus_id")
            ->join('tgoods_info g on b.id = g.bonus_id')
            ->where(
            array(
                '_string' => 'b.id in (' . $redIdStr . ')'))
            ->field(
            'b.id,b.bonus_page_name, b.bonus_num, d.get_num ,d.amount , g.goods_name,g.goods_image')
            ->select();
        
        if (! $result) {
            return false;
        }
        
        return $result;
    }
    
    // 获取机构下所有标签
    public function getListLabels($node_id) {
        $result = M("tmember_label")->where(
            array(
                'node_id' => $node_id))->select();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    
    // 获取会员的所有标签
    public function getMemberLabels($node_id, $member_id) {
        $result = M()->table("tmember_label_ex e")->join(
            "tmember_label l on e.label_id = l.id")
            ->field("l.*")
            ->where(
            array(
                "e.node_id" => $node_id, 
                "e.member_id" => $member_id))
            ->select();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    
    // 判断标签是否存在
    public function judgedLabelFlag($node_id, $label_name) {
        $lFlag = M("tmember_label")->where(
            array(
                'node_id' => $node_id, 
                'label_name' => $label_name))->getField('id');
        if ($lFlag) {
            return $lFlag;
        } else {
            return false;
        }
    }
    
    // 添加标签处理
    public function addLabel($node_id, $label_name) {
        $data = array(
            'node_id' => $node_id, 
            'label_name' => $label_name, 
            'add_time' => date('YmdHis'));
        $result = M("tmember_label")->add($data);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    
    // 判断会员和标签是否关联
    public function judgedLabelExFlag($node_id, $member_id, $label_id) {
        $data = array(
            'node_id' => $node_id, 
            'member_id' => $member_id, 
            'label_id' => $label_id);
        $eFlag = M('tmember_label_ex')->where($data)->find();
        if ($eFlag) {
            return true;
        } else {
            return false;
        }
    }
    
    // 会员关联标签处理
    public function member_label_ex($node_id, $member_id, $label_id) {
        $data = array(
            'node_id' => $node_id, 
            'member_id' => $member_id, 
            'label_id' => $label_id);
        
        $result = M('tmember_label_ex')->add($data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function getLabelId($node_id, $batch_id, $batch_type)
    {
        $preview_type = CommonConst::SNS_TYPE_PREVIEW; // 预览渠道
        $batchchannel = M()->table("tbatch_channel b")
            ->join('tchannel c on b.channel_id=c.id')
            ->where(array(
                    'b.batch_id' => $batch_id, 
                    'b.node_id' => $node_id,
                    'c.sns_type' => array('neq', $preview_type)
                    )
                )
            ->order('b.add_time desc')
            ->field("b.id, b.status")
            ->find();

        if (! $batchchannel) {
            $sns_type = CommonConst::SNS_TYPE_MEMSTORENAV;
            $channel_id = 0;
            $channel = M("tchannel")
                ->where(array(
                        "sns_type" => $sns_type, 
                        "node_id" => $node_id)
                    )
                ->field("id, status")
                ->find();
            if (! $channel) {
                $channel_data = array(
                    'name' => '会员中心',
                    'type' => 5, 
                    'sns_type' => $sns_type, 
                    'status' => '1', 
                    'node_id' => $node_id, 
                    'start_time' => date('YmdHis'), 
                    'end_time' => date('YmdHis', strtotime("+1 year")), 
                    'add_time' => date('YmdHis'), 
                    'click_count' => 0, 
                    'cj_count' => 0, 
                    'send_count' => 0);
                $channel_id = M("tchannel")->add($channel_data);
                if (! $channel_id) {
                    log_write("默认渠道：门店导航创建失败！");
                    return - 2;
                }
            } else {
                if ($channel['status'] != 1) {
                    $ret = M("tchannel")->where(
                        array(
                            'id' => $channel['id']))->save(
                        array(
                            'status' => 1));
                    if (! $ret) {
                        log_write("默认渠道id=" . $ret . "：门店导航启用失败！");
                        return - 3;
                    }
                }
                $channel_id = $channel['id'];
            }

            $data = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'add_time' => date("YmdHis"), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'node_id' => $node_id, 
                'status' => '1',
                'start_time' => date("YmdHis")
                );
            $label_id = M("tbatch_channel")->add($data);
            if ($label_id) {
                return $label_id;
            }
        } else {
            $ret = M("tbatch_channel")
                ->where(array(
                    'id' => $batchchannel['id'])
                )
                ->save(array(
                    'end_time' => date('YmdHis', strtotime("+1 year")), 
                    'status' => 1)
                );

            return $batchchannel['id'];
        }
    }

    /**
     *
     * @param $memberId
     * @return int 获取会员连续签到天数
     */
    public function getCheckinInfo($memberId) {
        $return = array();
        $memberInfo = $this->getMemberMsg($memberId);
        $lastCheckInDay = $memberInfo['sign_lasttime'];
        if ($lastCheckInDay != '') {
            if (date('Ymd', strtotime('+1 day', strtotime($lastCheckInDay))) ==
                 date('Ymd') ||
                 date('Ymd', strtotime($lastCheckInDay)) == date('Ymd')) {
                $return['conDays'] = $memberInfo['sign_count'];
            }
        } else {
            $return['conDays'] = 0;
        }
        
        $return['todayChecked'] = date('Ymd', strtotime($lastCheckInDay)) ==
             date('Ymd');
        return $return;
    }

    /**
     *
     * @param $nodeId
     * @param $memberId
     * @return bool
     */
    public function checkIn($nodeId, $memberId) {
        // 判断是否签到是否开启
        $integralConfig = M('tintegral_node_config')->where(
            "node_id = '$nodeId'")->find();
        if ($integralConfig['day_sign_flag'] != 1) {
            $this->error = '签到功能关闭!';
            return false;
        }
        
        $this->startTrans();
        // 判断用户是否连续签到
        $memberInfo = M('tmember_info')->lock(true)->find($memberId);
        $lastCheckInDay = $memberInfo['sign_lasttime'];
        
        $sign_count = $memberInfo['sign_count'];
        
        if (date('Ymd', strtotime($lastCheckInDay)) == date('Ymd')) {
            $this->error = '今天已经签到！';
            $this->rollback();
            return false;
        }
        
        // 连续签到
        if (date('Ymd', strtotime('+1 day', strtotime($lastCheckInDay))) ==
             date('Ymd')) {
            // todo:确认连续签到逻辑。。。。
            $sign_count = $sign_count + 1;
            if ($sign_count == 7) {
                $point = $integralConfig['7day_sign_rate'];
                $con_sign = '1';
                $res = D("MemberBehavior")->addBehaviorType($memberId, $nodeId, 
                    8, $point);
                if ($res === false) {
                    M()->rollback();
                    $this->error("行为增加失败!");
                }
            } else {
                if ($sign_count >= 8) {
                    $sign_count = '1';
                    $con_sign = '1';
                }
                $point = $integralConfig['day_sign_rate'];
                $res = D("MemberBehavior")->addBehaviorType($memberId, $nodeId, 
                    7, $point);
                if ($res === false) {
                    M()->rollback();
                    $this->error("行为增加失败!");
                }
            }
        } else {
            // 非连续
            $sign_count = 1;
            $point = $integralConfig['day_sign_rate'];
            $res = D("MemberBehavior")->addBehaviorType($memberId, $nodeId, 7, 
                $point);
            if ($res === false) {
                M()->rollback();
                $this->error("行为增加失败!");
            }
        }
        $pointModel = D('IntegralPointTrace', 'Model');
        
        // 赠送积分
        $flag = $pointModel->integralPointChange(CommonConst::INTEGRAL_TYPE5, 
            $point, $memberId, $nodeId, $con_sign);
        if ($flag === false) {
            $this->rollback();
            $this->error = $pointModel->getError();
            return false;
        }
        
        // 变更最后签到时间
        $data = array(
            'sign_lasttime' => date('YmdHis'), 
            'sign_count' => $sign_count);
        $flag = M('tmember_info')->where("id = '{$memberId}'")->save($data);
        if ($flag === false) {
            log_write("会员签到，最后登录时间变更失败！" . $this->_sql());
            $this->rollback();
            $this->error = '变更最后签到日期失败！';
            return false;
        }
        $this->commit();
        return $point;
    }

    /**
     *
     * @param $memberId
     * @param $month
     * @return array 获取制定月份的签到明细
     */
    public function getCheckDaysByMonth($memberId, $month) {
        $map = array(
            'member_id' => $memberId, 
            'type' => CommonConst::INTEGRAL_TYPE5, 
            'trace_time' => array(
                array(
                    'gt', 
                    $month . '000000'), 
                array(
                    'lt', 
                    $month . '235959')));
        $result = M('tintegral_point_trace')->where($map)
            ->field('trace_time')
            ->select();
        foreach ($result as &$val) {
            $val = substr($val['trace_time'], 0, 8);
        }
        $result = array_unique($result);
        return $result;
    }

    /**
     *
     * @param $memberId
     * @param $where 获取制定月份的签到明细
     */
    public function getCheckInTrace($memberId, $where) {
    }

    /**
     *
     * @param $requestId
     * @param $newPhone
     * @return bool
     */
    public function resendBarcode($requestId, $newPhone) {
        $barcodeInfo = M('tbarcode_trace')->where(
            array(
                'request_id' => $requestId))
            ->field('node_id, phone_no')
            ->find();
        if (! $barcodeInfo)
            return false;
        
        if ($barcodeInfo['phone_no'] != $newPhone &&
             $barcodeInfo['phone_no'] == '13900000000') {
            $flag = M('tbarcode_trace')->where(
                array(
                    'request_id' => $requestId))->save(
                array(
                    'phone_no' => $newPhone));
            if ($flag === false) {
                log_write('resendBarcode fail!' . M()->_sql());
                return false;
            }
            
            $result = $this->wxTermMemberFlag($barcodeInfo['node_id'], 
                $newPhone, 1, true, 
                array(
                    'channel_id' => $barcodeInfo['channel_id']));
            
            if ($result === false) {
                log_write(
                    "getMemberInfo fail! node_id[{$barcodeInfo['node_id']}] condition[{$newPhone}]");
                return true;
            }
            
            $behaviorModel = D('MemberBehavior', 'Model');
            $behaviorModel->addBehaviorData($result['id'], 
                $barcodeInfo['node_id'], 1, 1);
            log_write(
                "===MEM_DEBUG===记录会员行为数据member_id[{$result['id']}],node_id[{$barcodeInfo['node_id']}],1,1");
            
            return true;
        }
        
        return false;
    }

    /**
     * 抽奖领取积分（增加行为数据）
     *
     * @param $nodeId
     * @param $integalGetId
     * @param $newPhone
     * @return bool
     */
    public function receiveIntegal($nodeId, $integalGetId, $newPhone) {
        $integalInfo = M('tintegal_get_detail')->where(
            array(
                'id' => $integalGetId, 
                'node_id' => $nodeId))->find();
        if (! $integalInfo)
            return false;
        
        if ($integalInfo['member_id'] || $integalInfo['status'] == '1')
            return false;
        
        $result = $this->wxTermMemberFlag($nodeId, $newPhone, 1, true, 
            array(
                'channel_id' => $integalInfo['channel_id'],
                'batch_id' => $integalInfo['m_id']
            ));
        
        if ($result === false) {
            log_write(
                "getMemberInfo fail! node_id[{$nodeId}] condition[{$newPhone}]");
            return false;
        }
        
        $flag = M('tintegal_get_detail')->where(
            array(
                'id' => $integalGetId, 
                'node_id' => $nodeId))->save(
            array(
                'member_id' => $result['id'],
                'status'=>'1'));
        if ($flag === false) {
            log_write('receiveIntegal fail!' . M()->_sql());
            return false;
        }
        
        $rs = D('IntegralPointTrace')->integralPointChange(12, 
            $integalInfo['integral_num'], $result['id'], $nodeId, $integalGetId);
        if ($rs === false) {
            log_write(
                "===MEM_DEBUG===增加积分失败：" . print_r(
                    array(
                        12, 
                        $integalInfo['integral_num'], 
                        $result['id'], 
                        $nodeId, 
                        $integalGetId), true));
        }
        
        $bflag = D('MemberBehavior', 'Model')->addBehaviorType($result['id'], 
            $nodeId, 15, $integalInfo['integral_num'], $integalInfo['m_id']);
        if ($bflag === false)
            $this->log(
                "===MEM_DEBUG===记录会员行为数据失败[抽奖领取积分]member_id[{$result['id']}],node_id[{$nodeId}],1");
        
        return true;
    }

    /**
     * 抽奖领取红包（增加行为数据）
     *
     * @param $nodeId
     * @param $bonsUseDetailId
     * @param $newPhone
     * @return bool
     */
    public function receiveBonus($nodeId, $bonsUseDetailId, $newPhone) {
        $bonsInfo = M('tbonus_use_detail')->where(
            array(
                'id' => $bonsUseDetailId, 
                'node_id' => $nodeId))->find();
        if (! $bonsInfo)
            return false;
        
        if (strlen($bonsInfo['phone']) == 11)
            return false;
        
        $channelId = M('tcj_trace')->where(
            array(
                'request_id' => $bonsInfo['request_id']))->getField('channel_id');
        $result = $this->wxTermMemberFlag($nodeId, $newPhone, 1, true, 
            array(
                'channel_id' => $channelId,
                'batch_id' => $bonsInfo['m_id']
                ));
        
        if ($result === false) {
            log_write(
                "receiveBonus fail! node_id[{$nodeId}] condition[{$newPhone}]");
            return false;
        }
        
        $flag = M('tbonus_use_detail')->where(
            array(
                'id' => $bonsUseDetailId, 
                'node_id' => $nodeId))->save(
            array(
                'phone' => $newPhone));
        if ($flag === false) {
            log_write('receiveBonus fail!' . M()->_sql());
            return false;
        }
        
        $bflag = D('MemberBehavior', 'Model')->addBehaviorType($result['id'], 
            $nodeId, 14, '', $bonsUseDetailId);
        if ($bflag === false)
            $this->log(
                "===MEM_DEBUG===记录会员行为数据失败[抽奖领取红包]member_id[{$result['id']}],node_id[{$nodeId}],1");
        
        return true;
    }
    // 通过小店订单号查询订单金额和用户积分
    public function orderInfoSelectPointOne($orderId, $nodeId) {
        if (empty($orderId)) {
            log_write("订单号为空");
            return false;
        }
        $map = array(
            'a.node_id' => $nodeId, 
            'a.order_id' => $orderId);
        $orderInfo = M()->table("ttg_order_info a")->field(
            'a.order_amt,a.order_phone,a.receiver_type,b.change_num,b.type')
            ->join('tintegral_point_trace b on b.relation_id=a.order_id')
            ->where($map)
            ->find();
        if (! $orderInfo) {
            return false;
        }
        return $orderInfo;
    }
    // 订单号，商户号，渠道号
    public function orderPay($orderId, $nodeId, $labelId) {
        log_write(
            "访问进来了,orderId" . $orderId . "nodeId" . $nodeId . "labelId" .
                 $labelId);
        if (empty($orderId)) {
            log_write("订单号为空");
            return false;
        }
        $orderInfo = $this->orderInfoSelectPointOne($orderId, $nodeId);
        if ($orderInfo['type'] == 1) {
            log_write(
                "该订单号已经赠送过积分,订单号：" . $orderId . "商户号" . $nodeId . "改变积分：" .
                     $orderInfo['change_num']);
            return false;
        }
        if (! $orderInfo['order_phone']) {
            log_write("该订单号手机号为空,订单号：" . $orderId . "商户号" . $nodeId);
            return false;
        }
        $memberInfo = $this->telTermMemberFlag($nodeId, 
            $orderInfo['order_phone']);
        if (! $memberInfo) {
            // 新增会员
            $res = $this->wxTermMemberFlag($nodeId, $orderInfo['order_phone'], 
                1, true, array(
                    'channel_id' => $labelId));
            if ($res === false) {
                log_write("新增会员失败！会员手机号码：" . $orderInfo['order_phone']);
                return false;
            }
            $memberInfo = $res;
        }
        //初始化参数
        $changeNum = 0;
        $integralConfig = array();
            // 开始算积分，查询是否开设积分营销
            $IntegralConfigNodeModel = new IntegralConfigNodeModel();
            $integralConfig = $IntegralConfigNodeModel->checkIntegralConfig(
                $nodeId);
            if ($integralConfig === false) {
                log_write("无权限或未配置相关参数！");
            }
            if ($integralConfig) {
                if ($integralConfig['shop_online_flag'] == '1') {
                    $changeNum = intval($orderInfo['order_amt'] * $integralConfig['shop_online_rate']);
                    if($changeNum>0){
                        if ($integralConfig['one_online_rate_flag'] == 1) {
                            if ($changeNum > $integralConfig['one_online_rate']) {
                                $changeNum = $integralConfig['one_online_rate'];
                            }
                        }
                        // 调用积分
                        $IntegralPointTrace = new IntegralPointTraceModel();
                        $pointStatus = $IntegralPointTrace->integralPointChange('1',
                                $changeNum, $memberInfo['id'], $nodeId, $orderId, '');
                        if ($pointStatus === false) {
                            log_write("增加积分失败,订单号：" . $orderId);
                            return false;
                        }
                    }
                }
            }
        // 增加行为数据
        $behaviorRes = D("MemberBehavior")->addBehaviorType($memberInfo['id'], $nodeId, 4, $changeNum, $orderId, $orderInfo['order_amt']);
        if ($behaviorRes === false) {
            log_write("添加行为数据失败！");
            return false;
        }
        // 更新最后一次购物时间
        $res = M("tmember_info")->where(
            array(
                'node_id' => $nodeId, 
                'id' => $memberInfo['id']))->save(
            array(
                'shop_time' => date('YmdHis')));
        if ($res === false) {
            log_write("添加行为数据失败！" . $memberInfo['id']);
            return false;
        }
        isset($integralConfig['integral_name']) ? true : $integralConfig['integral_name'] = null;
        return array($changeNum, $integralConfig['integral_name']);
    }
    // 根据省、市、区 中文名字查询出 cityCode
    // 如：湖北,武汉,武昌 cityCode:17027005
    public function cacheCityData($province, $city = '', $town = '') {
        $cityCode = '';
        $mp = S('mem_province'); // 判断省是否存在缓存
        if (! $mp) {
            $pList = M('tcity_code')->field("province_code, province")
                ->where(array(
                'city_level' => '1'))
                ->select();
            if (! $pList || $province == '') {
                return $cityCode;
            }
            S('mem_province', $pList);
            $mp = S('mem_province');
        }
        foreach ($mp as $mk => $mv) {
            if (stristr($mv['province'], $province)) {
                $cityCode .= $mv['province_code'];
                break;
            }
        }
        
        $mc = S('mem_city'); // 判断市是否存在缓存
        if (! $mc) {
            $cList = M('tcity_code')->field("province_code, city_code, city")
                ->where(array(
                'city_level' => '2'))
                ->select();
            if (! $cList || $city == '') {
                return $cityCode;
            }
            S('mem_city', $cList);
            $mc = S('mem_city');
        }
        foreach ($mc as $kc => $vc) {
            if ($cityCode == $vc['province_code'] &&
                 stristr($vc['city'], $city) !== false) {
                $cityCode .= $vc['city_code'];
                $mt = S('mem_town_' . $vc['city_code']);
                if (! $mt) {
                    $tList = M('tcity_code')->field(
                        "province_code, city_code, town_code, town")
                        ->where(
                        array(
                            'city_level' => '3', 
                            'province_code' => $vc['province_code'], 
                            'city_code' => $vc['city_code'], 
                            '' => ''))
                        ->select();
                    if (! $tList || $town == '') {
                        return $cityCode;
                    }
                    S('mem_town_' . $vc['city_code'], $tList);
                    $mt = S('mem_town_' . $vc['city_code']);
                }
                foreach ($mt as $kt => $vt) {
                    if ($cityCode == $vt['province_code'] . $vt['city_code'] &&
                         stristr($vt['town'], $town) !== false) {
                        $cityCode .= $vt['town_code'];
                        break;
                    }
                }
                break;
            }
        }
        
        return $cityCode;
    }

    /*
     * 扣减指定会员会员 积分 参数：机构号，手机号，扣减积分，订单号 返回 ture 或者false
     */
    public function deductionMemberPoint($node_id, $phone, $point, $relation_id) {
        log_write("node_id:".$node_id."phone:".$phone."point:".$point."relation_id".$relation_id);
        if ($point <= 0) {
            log_write(
                "传入积分小于0，不记录流水" . $phone . "关联ID" . $relation_id . "node_id" .
                     $node_id);
            return true;
        }
        $member = $this->telTermMemberFlag($node_id, $phone);
        if (! $member) {
            log_write("积分扣减，未找到会员,手机号:" . $phone);
            return false;
        }
        
        if ($member['point'] < $point) {
            log_write("积分扣减，会员积分不足,会员id:" . $member['id']);
            return false;
        }
        
        $integral = D('IntegralPointTrace', 'Model');
        $flag = $integral->integralPointChange(17, $point, $member['id'], 
            $node_id, $relation_id);
        if ($flag === false) {
            log_write(
                "积分扣减，积分扣减失败,会员id:" . $member['id'] . "，订单号:" . $relation_id .
                     "，返还积分:" . $point);
            return false;
        }
        // 新增行为数据
        $res = D('MemberBehavior', 'Model')->addBehaviorType($member['id'], 
            $node_id, 17, $point, $relation_id);
        if ($res === false) {
            log_write(
                "新增行为数据失败,会员id:" . $member['id'] . "，订单号:" . $relation_id .
                     "，返还积分:" . $point);
            return false;
        }
        return true;
    }

    public function backMemberPoint($node_id, $phone, $relation_id) {
        $member = $this->telTermMemberFlag($node_id, $phone);
        if (! $member) {
            log_write("积分退还，未找到会员,手机号:" . $phone);
            return false;
        }
        
        $ptDFlag = M('tintegral_point_trace')->where(
            array(
                'type' => 17, 
                'member_id' => $member['id'], 
                'relation_id' => $relation_id))->find();
        if (count($ptDFlag) < 1) {
            log_write(
                "积分退还，未找到原始扣减流水,会员id:" . $member['id'] . "，订单号:" . $relation_id);
            return false;
        }
        
        $ptBFlag = M('tintegral_point_trace')->where(
            array(
                'type' => 18, 
                'member_id' => $member['id'], 
                'relation_id' => $relation_id))->find();
        if ($ptBFlag) {
            log_write(
                "积分退还，【重复调用】扣减积分已经返还,会员id:" . $member['id'] . "，订单号:" .
                     $relation_id);
            return false;
        }
        
        $integral = D('IntegralPointTrace', 'Model');
        $flag = $integral->integralPointChange(18, $ptDFlag['change_num'], 
            $member['id'], $node_id, $relation_id);
        if ($flag === false) {
            log_write(
                "积分退还，积分返还失败,会员id:" . $member['id'] . "，订单号:" . $relation_id .
                     "，返还积分:" . $point);
            return false;
        }
        
        return true;
    }
    
    /**
     * 默认及已经存在的会员招募自定义字段
     * @param type $node_id
     */
    public function existCustomFields($node_id){
        $result = array('member_tel'=>array('text'=>'手机号码'), 'member_name'=>array('text'=>'姓名'), 'member_sex'=>array('text'=>'性别'), 'member_area'=>array('text'=>'所在区域'), 'member_birthday'=>array('text'=>'出生日期'));
        $setCustomFields = M('tcollect_question_field')->where(array('node_id'=>$node_id))->field('text, name, value_list')->select();
        if(isset($setCustomFields)){
            foreach($setCustomFields as $val){
                $valueTempArray = explode('|', $val['value_list']);
                foreach($valueTempArray as $valueVal){
                    $temp = explode(':', $valueVal);
                    $valueList[$temp[0]] = $temp[1];
                }
                $result[$val['name']] = array('text'=>$val['text'], 'value_list'=>$valueList);
            }
        }
        return $result;
    }
}