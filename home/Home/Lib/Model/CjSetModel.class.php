<?php

/**
 *
 * @author lwb Time 20150720
 */
class CjSetModel extends Model {
    protected $tableName = '__NONE__';
    /**
     *
     * @param string $join_group_ids 数据库查出来的参加抽奖活动的分组（每个id之间用","分割）
     * @param string $zj_group_ids 数据库查出来的中奖的分组（每个id之间用","分割）
     * @param int $join_mode 0手机 1微信
     * @param array $mem_batch 所有手机分组
     * @param array $user_wx_group 所有微信分组
     */
    public function getSelectedGroup($join_group_ids, $zj_group_ids, $join_mode, 
        $mem_batch, $user_wx_group) {
        $phone_selected = array_keys($mem_batch); // 手机选择的分组,默认全选
        $phone_selected_zj = array_keys($mem_batch); // 手机中奖选择的分组，默认全选
        $wx_selected = array_keys($user_wx_group); // 微信号选择的分组,默认全选
        $wx_selected_zj = array_keys($user_wx_group); // 微信中奖选择的分组，默认全选
        $mem_batch_selected = array();
        $mem_batch_selected_zj = array();
        if ($join_group_ids != '') {
            $mem_batch_selected = explode(',', $join_group_ids);
        }
        if ($zj_group_ids != '') {
            $mem_batch_selected_zj = explode(',', $zj_group_ids);
        }
        if ($join_mode == 0 && $join_group_ids != - 1) {
            $phone_selected = $mem_batch_selected;
        } elseif ($join_mode == 1 && $join_group_ids != - 1) {
            $wx_selected = $mem_batch_selected;
        }
        if ($join_mode == 0 && $zj_group_ids != - 1) {
            $phone_selected_zj = $mem_batch_selected_zj;
        } elseif ($join_mode == 1 && $zj_group_ids != - 1) {
            $wx_selected_zj = $mem_batch_selected_zj;
        }
        return array(
            'phone_selected' => $phone_selected, 
            'phone_selected_zj' => $phone_selected_zj, 
            'wx_selected' => $wx_selected, 
            'wx_selected_zj' => $wx_selected_zj);
    }

    /**
     * 获取会员分组
     *
     * @return array 会员分组
     */
    public function getMemberBatch($node_id) {
        // $mem_batch = (array)M('tmember_batch')
        // ->where(array('node_id'=>$node_id,
        // 'status'=>'1'))->order('member_level
        // asc')->getField('id,level_name',true);
        // $undefindedGroup = array('0' => '未分组');
        // $mem_batch = $undefindedGroup + $mem_batch;
        $member = new MemberInstallModel();
        $member_cards = $member->getMemberCards($node_id);
        $mem_batch = array();
        foreach ($member_cards as $value) {
            if ($value['acquiesce_flag'] == 0) {
                $mem_batch[$value['id']] = $value['card_name'];
            }
        }
        
        return $mem_batch;
    }

    /**
     * 获取微信分组信息
     *
     * @param string $node_id
     * @return array 微信分组信息 array('{id}' => '{name}')
     */
    public function getWxGroup($node_id) {
        $weixin_info = D('tweixin_info')->where(
            "node_id = '{$node_id}' and status = '0'")->find();
        $_where = "(node_id = '' or node_id = '{$node_id}') and (node_wx_id = '' or node_wx_id = '{$weixin_info['node_wx_id']}') and id != 1";
        $user_wx_group = M('twx_user_group')->where($_where)->getField(
            'id,name', true);
        return $user_wx_group;
    }

    /**
     *
     * @param array $rule 验证规则数组
     * @param array $requestedValue 待验证的数据
     * @return array $req_data 通过验证的数据，未通过的抛异常
     */
    public function verifyReqData($rule, $requestedValue) {
        if (! is_array($rule)) {
            throw_exception('传入的参数不正确');
        }
        $req_data = array();
        foreach ($rule as $k => $v) {
            $value = $requestedValue[$k];
            if (! check_str($value, $v, $error)) {
                $msg = $v['name'] . $error;
                throw_exception($msg);
            }
            $req_data[$k] = $value;
        }
        return $req_data;
    }

    /**
     * 存奖项设定的数据
     *
     * @param string $nodeId
     * @param $requestData = array( 'cj_resp_text' => $cj_resp_text,
     *            'no_award_notice' => $no_award_notice, 'total_chance' =>
     *            $total_chance, 'sort' => $sort, 'm_id' => $m_id );
     */
    public function savePrizeConfig($nodeId, $requestData) {
        M()->startTrans();
        $ruleList = M('tcj_rule')->where(
            array(
                'batch_id' => $requestData['m_id'], 
                'status' => '1'))->select();
        if (! $ruleList) {
            // 理论上不会进入这个逻辑里
            // 在创建活动的时候设置了默认值,参考_editMarketInfo
            log_write('系统异常：存抽奖表错误');
            throw_exception('系统异常：存抽奖表错误');
        } else {
            if (count($ruleList) > 1) {
                M()->rollback();
                log_write('系统异常：存在多条启用的抽奖规则记录！');
                throw_exception('系统异常：存在多条启用的抽奖规则记录！');
            }
            $ruleInfo = $ruleList[0];
            // 编辑
            $data = array(
                'total_chance' => $requestData['total_chance'], 
                'cj_resp_text' => $requestData['cj_resp_text'], 
                'no_award_notice' => $requestData['no_award_notice']);
            $flag = M('tcj_rule')->where("id = '{$ruleInfo['id']}'")->save(
                $data);
            if ($flag === false) {
                M()->rollback();
                log_write('保存失败！');
                throw_exception('保存失败！');
            }
            // 保存奖项排序
            $cjCateModel = M('tcj_cate');
            $sort = $requestData['sort'];
            foreach ($sort as $cj_cate_id => $sortNum) {
                $sortRe = $cjCateModel->where(
                    array(
                        'id' => $cj_cate_id, 
                        'batch_id' => $requestData['m_id'], 
                        'node_id' => $nodeId))->save(
                    array(
                        'sort' => $sortNum));
                if (false === $sortRe) {
                    M()->rollback();
                    log_write('更新招募活动奖项排序失败!');
                    throw_exception('更新招募活动奖项排序失败!');
                }
            }
            // 编辑自定义短信内容
            $isOpenCustomSms = M('tnode_info')->where(array('node_id'=>$nodeId))->getField('custom_sms_flag');
            if($isOpenCustomSms == 1 && !empty($requestData['cusMsg'])){
                $isOk = M('tbatch_info')->where(array('node_id'=>$nodeId,'m_id'=>$requestData['m_id']))->save(array('sms_text'=>$requestData['cusMsg']));
                if ($isOk === false) {
                    log_write('保存自定义短信失败:'.M()->getLastSql());
                    M()->rollback();
                    throw_exception('保存失败！');
                }
            }
        }
        M()->commit();
    }

    /**
     * 获取奖项设定
     *
     * @param string $nodeId
     * @param int $m_id 活动id
     * @return array('cj_rule_arr' => {抽奖规则}, 'cj_cate_arr' => {奖项}, 'jp_array'
     *         => {奖品})
     */
    public function getCjConfig($nodeId, $m_id) {
        // 设置 中奖规则（中奖概率，中奖提示，未中奖提示）
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $nodeId, 
                'batch_id' => $m_id, 
                'status' => '1'))->find();
        $jp_array = array();
        if ($cj_rule_arr) {
            // 分类（一等奖，二等奖）
            $cj_cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $nodeId, 
                    'batch_id' => $m_id, 
                    'cj_rule_id' => $cj_rule_arr['id']))
                ->order('sort asc')
                ->select();
            // 奖品
            $jp_arr = M()->table('tcj_batch a')
                ->field(
                'a.*,b.batch_name,b.verify_begin_date,b.verify_end_date,
    		        b.verify_begin_type,b.verify_end_type,b.remain_num,b.storage_num,b.batch_img,
    		        ' . "case when b.card_id is not null then '1' else '0' end as send_type,b.card_id,
    		        g.goods_type, g.source")
                ->join('tbatch_info b on a.b_id=b.id')
                ->join('tgoods_info g on g.goods_id = a.goods_id')
                ->where(
                "a.node_id='" . $nodeId . "' and a.batch_id='" . $m_id .
                     "' and cj_rule_id = '" . $cj_rule_arr['id'] . "'")
                ->order('a.status asc')
                ->select();
            if ($jp_arr) {
                foreach ($jp_arr as $v) {
                    $v['available_time_txt'] = '';
                    if (! empty($v['verify_begin_date'])) {
                        if ($v['verify_begin_type'] == 0) { // 以准确的时间来表示有效期
                            $v['available_time_txt'] = date('Y/m/d H:i:s', 
                                strtotime($v['verify_begin_date'])) . '-' .
                                 date('Y/m/d H:i:s', 
                                    strtotime($v['verify_end_date']));
                        } elseif ($v['verify_begin_type'] == 1) { // 以发放后多少天表示有效期
                            $v['available_time_txt'] = '发送卡券后' .
                                 $v['verify_begin_date'] . '天开始使用' . '-' .
                                 '发送卡券后' . $v['verify_end_date'] . '天结束使用';
                        }
                    }
                    if ($v['send_type'] == '1') {
                        $wxCard = M('twx_card_type')->where(
                            array(
                                'node_id' => $nodeId, 
                                'card_id' => $v['card_id']))
                            ->order('id desc')
                            ->select();
                        $v['verify_begin_date'] = '0';
                        $v['available_time_txt'] = date('Y/m/d H:i:s', 
                            $wxCard[0]['date_begin_timestamp']) . '-' . date(
                            'Y/m/d H:i:s', $wxCard[0]['date_end_timestamp']);
                    }
                    $jp_array[$v['cj_cate_id']][] = $v;
                }
            }
        }
        // 组装中奖提示和未中奖提示
        $cj_rule_arr['no_award_notice'] = explode('|', 
            $cj_rule_arr['no_award_notice']); // 未中奖提示
        $cj_rule_arr['cj_resp_text'] = explode('|', 
            $cj_rule_arr['cj_resp_text']); // 中奖提示
        return $CjConfig = array(
            'cj_rule_arr' => $cj_rule_arr, 
            'cj_cate_arr' => $cj_cate_arr, 
            'jp_array' => $jp_array);
    }

    /**
     * 获取招募活动的名字
     *
     * @param int $m_id 招募活动的id
     * @param string $nodeId
     * @return string
     */
    public function getBindedRecruitName($m_id, $nodeId) {
        $result = M('tmarketing_info')->where(
            array(
                'id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_RECRUIT, 
                'node_id' => $nodeId))->getField('name');
        $result = $result ? $result : '';
        return $result;
    }

    /**
     * 获取抽奖活动各步骤的进度的html代码
     *
     * @param string $actionName
     * @param string $mId
     * @param string $publishGroupModule 发布时调用此函数时请把此参数带上,格式:GROUP_NAME . '/' .
     *            MODULE_NAME
     * @param int $isReEdit 是否是重新进行编辑（页面下一步的是第一次编辑）
     * @param array $selfDefineActionArr 自定义的action数组,例：
     * array(
            'setActBasicInfo' => '基础信息', 
            'setPrize' => '奖项设定',
            'index'=>'活动发布'
     * )
     * @return string
     */
    public function getActStepsBar($actionName, $mId = '', 
        $publishGroupModule = '', $isReEdit = '1', $selfDefineActionArr = '') {
        
        $moduleNameArr = C('ACTIVITY_MODULE');
        $spActivityArr = C('SP_ACTIVITY_GROUP_NAME');
        if ($mId) {
            $mInfo = M('tmarketing_info')->where(
                array(
                    'id' => $mId))
                    ->field('batch_type')
                    ->find();
            if (!$mInfo) {
                return '传参有误';
            }
            if (!in_array($mInfo['batch_type'], array_keys($moduleNameArr)) 
                && !in_array($mInfo['batch_type'], array_keys($spActivityArr))) {
                return '';
            }
        }
        
        $status = 'past';
        $actionArr = array(
            'setActBasicInfo' => '基础信息', 
            'setActConfig' => '活动配置', 
            'setPrize' => '奖项设定'
            );
        if (! empty($selfDefineActionArr)) {
            $actionArr = $selfDefineActionArr;
        }
        $html = '<div class="member_steps"><ul>';
        foreach ($actionArr as $key => $value) {
            if ($key == $actionName) {
                $status = 'current';
            }
            if (empty($mId)) {
                $html .= '<li class="' . $status . '">' . $value . '</li>';
            } else {
                if(isset($moduleNameArr[$mInfo['batch_type']])){
                    $moduleName = $moduleNameArr[$mInfo['batch_type']];
                }else{
                    $moduleName = '';
                }
                // 有的特殊模块不是写在LabelAdmin分组里的,如果有特殊模块调用他的分组
                $groupName = isset($spActivityArr[$mInfo['batch_type']]) ? $spActivityArr[$mInfo['batch_type']] : 'LabelAdmin';
                $key = $groupName . '/' . $moduleName . '/' . $key;
                $html .= '<li class="' . $status . '"><a href="' .
                     U($key, 
                        array(
                            'm_id' => $mId, 
                            'isReEdit' => $isReEdit)) . '">' . $value . '</a></li>';
            }
            if ($status == 'current') {
                $status = '';
            }
        }
        if ($status == 'past') {
            $status = 'current';
        }
        $html .= '<li class="' . $status . '">发布成功</li></ul></div>';
        return $html;
    }

    /**
     * 是否有访问数据
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @return boolean
     */
    public function hasClickActivity($nodeId, $mId) {
        $batchInfo = M('tmarketing_info')->where(
            array(
                'id' => $mId, 
                'node_id' => $nodeId))
            ->field('click_count')
            ->find();
        $hasClickActivity = false;
        if ($batchInfo['click_count']) {
            $hasClickActivity = true;
        }
        return $hasClickActivity;
    }
    
    
    /**
     * 停用活动奖品库存退回
     * 注:
     * @param $nodeId
     * @param $mId tmarketing_info id
     * @param $pId tcj_batch id 
     * @return 回退数量
     */
    public function storageBack($nodeId,$mId,$pId,$userId){
    	//判断活动是否过期
    	$mInfo = M('tmarketing_info')->field('batch_type,end_time')->where(array('node_id'=>$nodeId,'id'=>$mId))->find();
    	$batchTypeArr = C('PRIZE_STOREGE_BACK.BATCH_TYPE');
    	if(!in_array($mInfo['batch_type'],$batchTypeArr)){
    		$this->error = '无效的活动类型';
    		return false;
    	}
    	//查找奖品信息
    	$wh = array(
    		'c.node_id' => $nodeId,
    		'c.batch_id' => $mId,
    		'c.id' => $pId
    	);
    	$pInfo =  M('tcj_batch c')->field('c.b_id,c.total_count,c.status,b.storage_num,b.remain_num,b.goods_id')
    	->join('tbatch_info b ON c.b_id=b.id')
    	->where($wh)->find();
    	//dump($pInfo);exit;
    	if(empty($pInfo)){
    		$this->error = '未找到奖品信息';
    		return false;
    	}
    	$goodsModel = D('Goods');
    	if($pInfo['remain_num'] <= 0){
    		$this->error = '该奖品库存已为0';
    		return false;
    	}
    	if(!$this->checkStorageBack($pInfo['goods_id'])){
    		$this->error = '该奖品类型不支持回退';
    		return false;
    	}
    	//只有过期活动或停用的奖品才能回退
    	if($mInfo['end_time'] > date('Ymd000000') && $pInfo['status'] == '1'){
			$this->error = '只有过期的活动或停用的奖品才能回退库存';    	
			return false;
    	}
    	//tcj_batch total_count更新
    	$cData = array(
    		'total_count' => $pInfo['total_count'] - $pInfo['remain_num'],
    		'award_rate'  => $pInfo['total_count'] - $pInfo['remain_num']
    	);
    	$resutl = M('tcj_batch')->where("id='{$pId}'")->save($cData);
    	if($resutl === false){
    		$this->error = '数据更新失败-1';
    		return false;
    	}
    	//将tbatch_info剩余库存清零
    	$sData = array(
    		'storage_num' => $pInfo['storage_num'] - $pInfo['remain_num'],
    		'remain_num' => '0'
    	);
    	$resutl = M('tbatch_info')->where("id='{$pInfo['b_id']}'")->save($sData);
    	if($resutl === false){
    		$this->error = '数据更新失败-2';
    		return false;
    	}
    	//将库存回退到tgoods_info中
    	$resutl = $goodsModel->storagenum_reduc($pInfo['goods_id'],-1*$pInfo['remain_num'],$pId,'20','活动奖品回退',0,false,$userId);
    	if(!$resutl){
    		$this->error = $goodsModel->getError();
    		return false;
    	}
    	return $pInfo['remain_num'];
    }
    
    /**
     * 检查奖品是否是可以回退库存的类型
     * @param $goodsId 奖品对应的goods_id  tcj_batch表goods_id
     */
    public function checkStorageBack($goodsId){
    	$pInfo = M('tgoods_info')->field('goods_type,source')->where("goods_id='{$goodsId}'")->find(); 
    	if(empty($pInfo)) return false;
    	//可以回退的奖品类型(现在只针对自建和采购的电子券做回退) 规则:tgoods_info 中的source和goods_type连接组成的数组
    	$filter = C('PRIZE_STOREGE_BACK.PRIZE_TYPE');
    	if(in_array($pInfo['source'].$pInfo['goods_type'],$filter)){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    /**
     * 是否绑定了微信认证服务号
     *
     * @param string $nodeId
     * @return boolean
     */
    public function isBindWxServ($nodeId) {
        $isBind = false;
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))->find();
            // 微信已认证服务号并且状态正常的
            if ($weixinInfo &&
                $weixinInfo['account_type'] == 4 &&
                $weixinInfo['status'] == '0') {
                    $isBind = true;
                }
                return $isBind;
    }
    
}