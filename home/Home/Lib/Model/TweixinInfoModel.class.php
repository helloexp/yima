<?php

class TweixinInfoModel extends Model {
    protected $tableName = '__NONE__';

    /**
     * 获取微信场景ID，并且自增 node_id：机构号
     */
    public function getSceneId($node_id) {
        // $sql = "UPDATE %TABLE% SET `scene_id_seq` = LAST_INSERT_ID(
          // CASE 
          // WHEN `scene_id_seq` >= 100000 THEN 1
          // ELSE
          // `scene_id_seq`+ 1
          // END )
          // WHERE node_id='$node_id'";
        // $result = $this->execute($sql, true);
        // if ($result === false)
            // return false;
        
        // $row = M()->table("(SELECT LAST_INSERT_ID() as id FROM DUAL) a")->find();
        // return $row['id'];
        // return $result !== false ? $this->getLastInsID() : false;
        $scene_id = M('twx_qrchannel')->where("node_id = '".$node_id."'")->order('scene_id desc')->getField('scene_id');
        if(!$scene_id || $scene_id >= 100000){
            $scene_id = 1;
        }else{
            $scene_id = $scene_id + 1;
        }
        return $scene_id;
    }

    /**
     * 获取微信设定的引导url
     *
     * @param string $nodeId
     * @return string guide_url or bool false
     */
    public function getGuidUrl($nodeId) {
        $guideUrl = M('tweixin_info')
            ->where(array(
            'node_id' => $nodeId))
            ->getField('guide_url');
        return $guideUrl;
    }

    /**
     * 获取微信信息
     *
     * @param string $nodeId
     */
    public function getWeixinInfo($nodeId) {
        $weixinInfo = M('tweixin_info')
            ->where(array(
            'node_id' => $nodeId))
            ->find();
        return $weixinInfo;
    }
    
    // 获取活动内容
    public function getBatchInfo($batch_id,$nodeId){
        $batch_info = M('tmarketing_info')->where(
            "node_id='" . $nodeId . "' and id='" . $batch_id . "'")->find();
        return $batch_info;
    }

    /**
     * 设置微信模板消息
     *
     * @param string $nodeId
     * @param int $templateType 模板类型
     * @param string $templateId 模板Id
     * @param string $startMake
     * @param string $endMake
     * @param string $topcolor
     * @param string $url
     * @return string $result or bool false
     */
    public function templateJson($nodeId, $templateType, $templateId, 
        $startMake = '', $endMake = '', $topcolor = '#FF0000', $url = '', $channel_name = '') {
        $mode = M('twx_templatemsg_config');
        $info = $mode->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->find();
        
        $data = array(
            'node_id' => $nodeId, 
            'template_type' => $templateType, 
            'template_id' => $templateId, 
            'topcolor' => $topcolor, 
            'url' => $url, 
            'status' => 0, 
            'add_time' => date("YmdHis"), 
            'channel_name' => $channel_name);
        
        $configData = array(
            'first' => array(
                'value' => $startMake, 
                'color' => '#173177'), 
            "remark" => array(
                'value' => $endMake, 
                'color' => '#173177'));
        $data['data_config'] = json_encode($configData);
        
        if ($info) {
            $result = $mode->where(
                array(
                    'node_id' => $nodeId, 
                    'template_type' => $templateType))->save($data);
        } else {
            $result = $mode->add($data);
        }
        
        return $result;
    }

    /**
     * 模板消息展示
     *
     * @param string $nodeId
     * @param int $templateType
     * @return string $info or bool false
     */
    public function templateShow($nodeId, $templateType = 1) {
        $info = M('twx_templatemsg_config')
            ->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->find();
        
        return $info;
    }

    /**
     * 模板消息开启关闭
     *
     * @param string $nodeId
     * @param int $templateType
     * @param int $flag 开关标记
     * @return string $info or bool false
     */
    public function templateAjax($nodeId, $templateType, $flag) {
        $status = M('twx_templatemsg_config')
            ->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->getField('status');
        
        $status ? $status = 0 : $status = 1;
        $info = M('twx_templatemsg_config')->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))->save(
            array(
                'status' => $status));
        
        return $info;
    }

    /**
     * 查询模板消息状态
     *
     * @param string $nodeId
     * @param int $templateType
     * @return string status
     */
    public function templateStatue($nodeId, $templateType) {
        $status = M('twx_templatemsg_config')
            ->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->getField('status');
        
        return $status;
    }

    /**
     * 模板消息状态
     *
     * @param string $nodeId
     * @param int $templateType 模板类型
     * @param int $status 状态
     * @return int or bool false
     */
    public function templateStatus($nodeId, $templateType, $status) {
        $info = M('twx_templatemsg_config');
        $isSend = $info->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))->setField('status', $status);
        
        return $isSend;
    }

    /**
     * 模板消息是否设置
     *
     * @param $nodeId
     * @param $templateType
     * @return bool
     */
    public function templateFlag($nodeId, $templateType) {
        $info = M('twx_templatemsg_config');
        $result = $info->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->find();
        $isFlag = $result ? true : false;
        
        return $isFlag;
    }

    /**
     * 条码支付微信模板url引流统计
     *
     * @param $nodeId
     * @param $templateType
     * @return bool|array
     */
    public function channelCount($nodeId, $templateType) {
        $info = M('twx_templatemsg_config');
        $result = $info->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))
            ->getField('url');
        if ($result) {
            $batch_channel_id = preg_replace('/.*id=([\d]{0,})/iUs', '', 
                $result);
            $channel_arr = M()->table("tbatch_channel bc")
                ->join('tchannel c on c.id=bc.channel_id')
                ->where("bc.id=$batch_channel_id")
                ->field('c.click_count,c.cj_count,c.send_count')
                ->find();
            return $channel_arr;
        } else {
            return false;
        }
    }

    /**
     * 统计微信模板消息总数
     *
     * @param $nodeId
     * @param $templateType
     * @return mixed
     */
    public function templateSendcount($nodeId, $templateType) {
        $sentCount = M()->table("twx_templatemsg_config wtc")
            ->join("twx_template_msg_trace wtmt on wtc.node_id=wtmt.node_id")
            ->where(
            "wtmt.node_id = $nodeId and wtc.template_type = $templateType and wtmt.send_status=1")
            ->count();
        
        return $sentCount;
    }

    /**
     * 在关键词自动回复中修改message_name为内容预览的数据
     *
     * @param $id 素材id
     * @param $nodeId
     */
    public function preview($nodeId, $id) {
        $where['node_id'] = $nodeId;
        $where['message_name'] = '素材管理内容预览';
        $twx_message = M('twx_message');
        $message_id = $twx_message
            ->where($where)
            ->getField('id');
        if (! $message_id) {
            $data = array(
                'node_id' => $nodeId, 
                'message_name' => '素材管理内容预览', 
                'response_type' => 3, 
                'status' => 0, 
                'add_time' => date('YmdHis'), 
                'update_time' => date('YmdHis'));
            
            $result = $twx_message->add($data);
            // 得到添加的message_id
            $message_id = $result;
        }
        $twx_msgkeywords = M('twx_msgkeywords');
        $result = $twx_msgkeywords
            ->where("message_id = " . $message_id)
            ->find();
        if (! $result) {
            $data2 = array(
                'node_id' => $nodeId, 
                'message_id' => $message_id, 
                'key_words' => '内容预览', 
                'match_type' => 1, 
                'add_time' => date('YmdHis'), 
                'update_time' => date('YmdHis'));
            $twx_msgkeywords->add($data2);
        }
        $twx_msgresponse = M('twx_msgresponse');
        $msgresponse_id = $twx_msgresponse
            ->where("message_id = " . $message_id)
            ->getField();
        if (! $msgresponse_id) {
            $data3 = array(
                'node_id' => $nodeId, 
                'message_id' => $message_id, 
                'response_info' => $id, 
                'response_class' => 1, 
                'add_time' => date('YmdHis'), 
                'update_time' => date('YmdHis'));
            $result = $twx_msgresponse->add($data3);
        } else {
            $data4 = array(
                'response_info' => $id, 
                'update_time' => date('YmdHis'));
            $result = $twx_msgresponse->where("message_id = " . $message_id)->save(
                $data4);
        }
        
        return $result;
    }

    public function getSendNum($node_wx_id, $account_type) {
        // 查询服务号本月向全部粉丝群发条数
        if (4 == $account_type) {
            $count1 = M('twx_msgbatch')
                ->where(
                "is_to_all = '1' and node_wx_id = '" . $node_wx_id .
                     "' and send_mode = '1' and add_time like '" . date('Ym') .
                     "%'")
                ->count();
            $count2 = M('twx_msgbatch')
                ->where(
                "is_to_all = '1' and node_wx_id = '" . $node_wx_id .
                     "' and send_mode = '2' and send_on_time like '" . date(
                        'Ym') . "%'")
                ->count();
            $count = $count1 + $count2;
        }
        
        // 查询服务号当日群发条数
        if (2 == $account_type) {
            $count1 = M('twx_msgbatch')
                ->where(
                "node_id = '" . $node_id . "' and node_wx_id = '" . $node_wx_id .
                     "' and send_mode = '1' and add_time like '" . date('Ymd') .
                     "%'")
                ->count();
            $count2 = M('twx_msgbatch')
                ->where(
                "node_id = '" . $node_id . "' and node_wx_id = '" . $node_wx_id .
                     "' and send_mode = '2' and send_on_time like '" .
                     date('Ymd') . "%'")
                ->count();
            $count = $count1 + $count2;
        }
        
        return $count;
    }
    
    // 获取微信分组
    public function getWxGroupList($node_id, $node_wx_id) {
        $where = "(node_id = '' or node_id = '{$node_id}') and (node_wx_id = '' or node_wx_id = '{$node_wx_id}')";
        $group_list = M('twx_user_group')
            ->where($where)
            ->getField('id, name', true);
        return $group_list;
    }
    
    // 获取微信粉丝信息
    public function getFansInfo($node_id, $node_wx_id) {
        $group_id = I('group_id', '');
        $sex = I('sex', 0, 'intval');
        $keywords = I('keywords', '', 'trim');
        $nickname = I('nickname', '', 'trim');
        $pid = I('province', 0, 'trim');
        $cid = I('city', 0, 'trim');
        $scene = I('scene', '', 'trim');
        
        // 根据条件查询粉丝
        $where = array(
            't.node_id' => $node_id, 
            't.node_wx_id' => $node_wx_id, 
            't.subscribe' => '1');
        
        if (in_array($sex, array(
            1, 
            2), true))
            $where['t.sex'] = $sex;
        if ($group_id != '')
            $where['t.group_id'] = array('in',$group_id);
        
        if ('' != $pid) {
            $province = M('tcity_code')
                ->where("city_level=1 and province_code=$pid")
                ->getfield('province');
            
            $where['t.province'] = $province;
        }
        
        if ('' != $cid) {
            $city = M('tcity_code')
                ->where("city_level=2 and city_code=$cid")
                ->getfield('city');
            $city = str_replace('市', '', $city);
            $where['t.city'] = $city;
        }
        
        if ($scene != '') {
            if ('' != $scene) {
                $fsfromId = M()->table("twx_qrchannel wq")
                    ->join('tchannel c on c.id=wq.channel_id')
                    ->where(
                    array(
                        'c.name' => array(
                            'like', 
                            '%' . $scene . '%')))
                    ->field('wq.scene_id')
                    ->select();
                $scenc_id = array();
                foreach ($fsfromId as &$v) {
                    $scenc_id[] = $v['scene_id'];
                }
                $where['t.scene_id'] = array(
                    'in', 
                    $scenc_id);
            }
        }
        
        if ($keywords)
            $where['t.remarkname'] = array(
                'like', 
                '%' . $keywords . '%');
        
        if($nickname) $where['t.nickname'] = $nickname;

        $model = M("twx_user");
        $fansInfo = $model->alias("t")->where($where)->select();
        return $fansInfo;
    }
    //老版的
    public function interact2($nodeId) {
        $goodsModel = D('Goods');
        $actType = I('actType');
        // 关键词
        $keywordStr = I('keywordStr', array()); // 关键词
        $matchMode = I('matchMode', array()); // 匹配模式 0 模糊，1精确
        $kwdId = I('kwdId', array()); // 关键字列表
        $respId = I('respId', array()); // 回复列表
        $ruleName = I('ruleName'); // 规则名
        $message_id = I('msgId'); // 请求的message_id
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        $respClass = I('respClass', array()); // 回复类型 6卡券 8红包
        $hb_num = I('hb_num', array());
        $remain_num = I('remain_num', array());
        $response_info = I('cardid', array()); // 卡券card_id 红包goods_id
        $congratulations_info = I('congratulations_info', array()); // 中奖
        $explain_info = I('explain_info'); // 已领奖品
        $regret_info = I('regret_info'); // 没有领到奖品
        $response_type = 6;
        $nowtime = date('YmdHis');
        // if(!$congratulations_info[$k]) $congratulations_info[$k] = '恭喜你中奖啦！';
        if(!$explain_info) $explain_info = '您已领过奖品，邀请更多好友一起参加吧！';
        if(!$regret_info) $regret_info = '很遗憾奖品都被领完啦！';
        // 如果提交方式是添加
        $msgDao = M('twx_message');
        $msgDao->startTrans();
        if ($actType == 'add' || $actType == 'edit') {
            if ($actType == 'add' && $message_id == '') {
                // 去除重复
                foreach ($keywordStr as $val) {
                    $count = M('twx_msgkeywords')
                            ->where(
                                    array(
                                            'node_id' => $nodeId,
                                            'key_words' => $val))
                            ->count();
                    if ($count) {
                        return array(
                                'status' => 1,
                                'info' => "保存失败，关键词" . $val . "已存在");
                    }
                }
                $m_data = array(
                        'name' => $ruleName,
                        'batch_type' => 3003,
                        'node_id' => $nodeId,
                        'defined_one_name' => $explain_info,
                        'defined_two_name' => $regret_info,
                        'is_cj' => '0',
                        'add_time' => date('YmdHis')
                );
                $m_id = M('tmarketing_info')->add($m_data);
                if (! $m_id) {
                    return array(
                            'status' => 1,
                            'info' => "保存失败");
                }
                // 先加到 twx_message表
                $data = array(
                        'node_id' => $nodeId,
                        'message_name' => $ruleName,
                        'response_type' => $response_type,
                        'status' => 0,
                        'begin_time' => dateformat($begin_time, 'YmdHis'),
                        'end_time' => dateformat($end_time, 'YmdHis'),
                        'add_time' => $nowtime,
                        'update_time' => $nowtime,
                        'm_id' => $m_id,
                );
                $result = $msgDao->add($data);
                if (! $result) {
                    $msgDao->rollback();
                    return array(
                            'status' => 1,
                            'info' => "保存失败01");
                }
                // 得到添加的message_id
                $message_id = $result;
            } else {
                // 去除重复
                foreach ($keywordStr as $val) {
                    $count = M('twx_msgkeywords')
                            ->where(
                                    array(
                                            'node_id' => $nodeId,
                                            'key_words' => $val,
                                            'message_id' => array(
                                                    'NEQ',
                                                    $message_id)))
                            ->count();
                    if ($count) {
                        return array(
                                'status' => 1,
                                'info' => "保存失败，关键词" . $val . "已存在");
                    }
                }

                // 校验一下是否允许编辑
                $where = "id='" . $message_id . "' and response_type='" .
                        $response_type . "' and node_id='" . $nodeId . "'";
                $result = $msgDao->where($where)->find();
                $m_id = $result['m_id'];
                if (! $result) {
                    $msgDao->rollback();
                    return array(
                            'status' => 1,
                            'info' => "保存失败，要编辑的消息不存在");
                }
                // 更新时间
                $result = $msgDao->where($where)->save(
                        array(
                                "message_name" => $ruleName,
                                "update_time" => $nowtime,
                                "begin_time" => dateformat($begin_time, 'YmdHis'),
                                "end_time" => dateformat($end_time, 'YmdHis'),
                        ));
                // 删除不在列表中的关键字
                $where_base = "message_id='$message_id'";
                $notInIds = implode("','", $kwdId);
                $where = $where_base . " and id not in('" . $notInIds . "')";
                $result = M('twx_msgkeywords')->where($where)->delete();
                // 删除不在列表中的回复 并将剩余红包数返回
                $notInIds = implode("','", $respId);
                $where = $where_base . "and status = 0 and id not in('" . $notInIds . "')";
                $b_id = M('twx_msgresponse')->where($where)->getField('b_id',true);
                if($b_id){
                    $result = M('tbatch_info')->where(array('id'=>array('in',$b_id)))->select();
                    foreach($result as $val){
                        $goodsModel->storagenum_reduc($val['goods_id'], -1 * $val['remain_num'], $val['id'], 1,'微信助手-互动有礼');
                    }
                    $batch_data = array(
                            'remain_num' => 0,
                            'status'=> 1
                    );
                    $result = M('tbatch_info')->where(array('id'=>array('in',$b_id)))->save($batch_data);
                }
                $result = M('twx_msgresponse')->where($where)->save(array('status'=>1));
            }
            // 添加或者编辑回复关键字
            foreach ($keywordStr as $k => $v) {
                // 如果没有 kwd 添加
                if (! $kwdId[$k]) {
                    $data = array(
                            'node_id' => $nodeId,
                            'message_id' => $message_id,
                            'key_words' => $keywordStr[$k],
                            'match_type' => $matchMode[$k],
                            'add_time' => $nowtime,
                            'update_time' => $nowtime);
                    $result = M('twx_msgkeywords')->add($data);
                } else {
                    $data = array(
                            'key_words' => $keywordStr[$k],
                            'match_type' => $matchMode[$k],
                            'update_time' => $nowtime);
                    $where = "message_id='" . $message_id . "' and id='" .
                            $kwdId[$k] . "'";
                    $result = M('twx_msgkeywords')->where($where)->save($data);
                }
                if ($result === false) {
                    $msgDao->rollback();
                    return array(
                            'status' => 1,
                            'info' => "保存失败02");
                }
            }
            // 添加回复内容表
            foreach ($response_info as $k => $v) {

                $goodsInfo = M('tgoods_info')->where(array('node_id'=>$nodeId,'source'=>'6','goods_id'=>$v))->find();
                if (! $respId[$k]) {                //msgresponse里的id
                    $data2 = array(
                            'batch_no' => $goodsInfo['batch_no'],
                            'batch_short_name' => $goodsInfo['goods_name'],
                            'batch_name' => $goodsInfo['goods_name'],
                            'node_id' => $nodeId,
                            'batch_class' => $goodsInfo['goods_type'],
                            'batch_type' => $goodsInfo['source'],
                            'batch_img' => $goodsInfo['goods_image'],
                            'batch_amt' => $goodsInfo['goods_amt'],
                            'add_time' => date('YmdHis'),
                            'batch_desc' => $goodsInfo['goods_desc'],
                            'goods_id' => $v,
                            'storage_num' => $hb_num[$k],
                            'remain_num' => $hb_num[$k],
                            'material_code' => $goodsInfo['customer_no'],
                            'print_text' => $goodsInfo['print_text'],
                            'm_id' => $m_id,
                            'validate_type' => $goodsInfo['validate_type']
                    );
                    $b_id = M('tbatch_info')->add($data2);
                    $goodsModel->storagenum_reduc($v, $hb_num[$k], $b_id, 0);

                    $data = array(
                            'node_id' => $nodeId,
                            'message_id' => $message_id,
                            'response_info' => $response_info[$k],
                            'response_class' => $respClass[$k],
                            'add_time' => $nowtime,
                            'batch_id' => '',
                            'congratulations_info' => $congratulations_info[$k],
                            'explain_info' => $explain_info,
                            'regret_info' => $regret_info,
                            'update_time' => $nowtime,
                            'b_id' => $b_id
                    );
                    $result = M('twx_msgresponse')->add($data);
                } else {
                    $data_edit = array(
                            'response_info' => $response_info[$k],
                            'congratulations_info' => $congratulations_info[$k],
                            'explain_info' => $explain_info,
                            'regret_info' => $regret_info,
                            'update_time' => $nowtime
                    );
                    $result = M('twx_msgresponse')->where("id = ".$respId[$k])->save($data_edit);

                    $b_id = M('twx_msgresponse')->where('id='.$respId[$k])->getField('b_id');
                    $batch_info = M('tbatch_info')->where('id='.$b_id)->find();
                    $num = $hb_num[$k] - $batch_info['remain_num'];
                    if($hb_num[$k] > $batch_info['remain_num']){
                        $goodsModel->storagenum_reduc($v, $num, $val['id'], 0,'微信助手-互动有礼');
                    }elseif($hb_num[$k] < $batch_info['remain_num']){
                        $goodsModel->storagenum_reduc($v, $num, $val['id'], 1,'微信助手-互动有礼');
                    }
                    $batch_data['storage_num'] = $batch_info['storage_num'] + $hb_num[$k] - $batch_info['remain_num'];
                    $batch_data['remain_num'] = $hb_num[$k];
                    M('tbatch_info')->where('id='.$b_id)->save($batch_data);
                }

                if ($result === false) {
                    $msgDao->rollback();
                    return array(
                            'status' => 1,
                            'info' => "保存失败03");
                }
            }
            $msgDao->commit();
            return array(
                    'status' => 0,
                    'info' => $message_id);
        }
    }
    // 获取互动有礼活动内容
    public function getInteractInfo($message_id) {
        $info['message_info'] = M('twx_message')
            ->where("id=" . $message_id." and status = 0")
            ->find();
        $info['keywords_info'] = M('twx_msgkeywords')
            ->where("message_id=" . $message_id)
            ->select();
        $info['card_info'] = $this->getSendTicket($info['message_info']['m_id']);
        return $info;
    }
    
    // 互动有礼活动删除
    public function interactDelete($message_id, $nodeId) {
        //获取活动对应的奖品
        $batchInfo = M('tbatch_info')->field('a.*')->join(' a inner join twx_message b on a.m_id=b.m_id')->where(array('a.node_id'=>$nodeId,'b.id'=>$message_id))->select();
        //开始退还库存
        $batchIds = array();
        M()->startTrans();
        foreach($batchInfo as $key => $value){
            if($value['batch_type'] == 6){
                $isOk = M('tgoods_info')->where(array('goods_id' => $value['goods_id']))->setInc('remain_num',$value['remain_num']);//save(array('remain_num' => ($value['remain_num']+$value2['remain_num'])));
            }else{
                $isOk = M('twx_card_type')->where(array('goods_id' => $value['goods_id']))->setDec('card_get_num',$value['remain_num']);
            }
            if($isOk === false){
                log_write('卡券或红包的库存退还失败:'.M()->getLastSql());
                M()->rollback();
                return false;
            }
            $batchIds[] = $value['id'];
        }
        $saveBatch = M('tcj_batch')->where(array('node_id'=>$nodeId,'b_id'=>array('in',implode(',',$batchIds))))->save(array('status'=>2));
        $saveMessage = M('twx_message')->where("id=" . $message_id)->save(array('status'=>1));
        $saveKeyWords = M('twx_msgkeywords')->where("message_id=" . $message_id)->delete();
        if(!$saveMessage || !$saveKeyWords || !$saveBatch){
            log_write('[saveMessage] || [saveKeyWords] || [saveBatch]的更新结果:'.$saveMessage.'||'.$saveKeyWords.'||'.$saveBatch);
            return false;
        }
        M()->commit();
        return true;
    }

    /**
     * 互动有礼的修改
     */
    public function interactEdit($postData, $nodeId){
        $result = array('hasCard'=>true,'return'=>true,'cardNum'=>true,'SQL'=>'');
        $explain_info   = '您已领过奖品，邀请更多好友一起参加吧！';
        if(!empty($postData['explain_info'])){
            $explain_info = $postData['explain_info'];
        }
        $regret_info    = '很遗憾奖品都被领完啦！';
        if(!empty($postData['regret_info'])){
            $regret_info = $postData['regret_info'];
        }
        $day_limit_info = '今天的奖品都领完了，明天再来吧！';
        if(!empty($postData['day_limit_info'])){
            $day_limit_info = $postData['day_limit_info'];
        }
        $messageInfo = M('twx_message')->where(array('id'=>$postData['messageId'],'node_id'=>$nodeId))->find();
        M()->startTrans();
        //修改时间和活动名称
        $messageData = array(
                'node_id' => $nodeId,
                'message_name' => $postData['ruleName'],
                'begin_time' => dateformat($postData['begin_time'], 'YmdHis'),
                'end_time' => dateformat($postData['end_time'], 'YmdHis'),
                'update_time' => date('YmdHis'),
                'explain_info' => $explain_info,
                'regret_info'  => $regret_info,
                'day_limit_info' => $day_limit_info
        );
        $messageResult = M('twx_message')->where(array('node_id'=>$nodeId,'response_type'=>6,'status'=>0,'id'=>$postData['messageId']))->save($messageData);
        if ($messageResult === false) {
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            M()->rollback();
            return $result;
        }
        //修改抽奖接口那所对应的活动时间
        $time = array(
            'start_time' => dateformat($postData['begin_time'], 'YmdHis'),
            'end_time'   => dateformat($postData['end_time'], 'YmdHis')
        );
        $editTime = M('tmarketing_info')->where(array('node_id'=>$nodeId,'id'=>$messageInfo['m_id']))->save($time);
        if ($editTime === false) {
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            M()->rollback();
            return $result;
        }

    /*************************关键词处理**************/
        $oldKeyWords =  M('twx_msgkeywords')->where(array('node_id'=>$nodeId,'message_id'=>$postData['messageId']))->getField('id',',');
        $oldKeyWords = explode(',',$oldKeyWords);
        $deleteId =array_diff($oldKeyWords,$postData['kwdId']);          //返回要删掉的ID数组
        //删掉原来的关键词
        if(!empty($deleteId)) {
            $deleteId = implode(',',$deleteId);
            M('twx_msgkeywords')->where(
                    array('node_id' => $nodeId, 'message_id' => $postData['messageId'], 'id' => array('in', $deleteId))
            )->delete();
        }

        //更新关键词
        $saveKeyWords = array_intersect($postData['kwdId'],$oldKeyWords);         //key被保留下来了
        foreach($saveKeyWords as $key => $value){

            //检测是否有重复的关键词
            foreach ($postData['keywordStr'] as $val) {
                $count = M('twx_msgkeywords')->where(array('node_id' => $nodeId,'key_words' => $postData['keywordStr'][$key]))->count();
                if ($count > 1) {
                    M()->rollback();
                    return array('status' => 0,'info' => "保存失败，关键词" . $val . "已存在");
                }else{
                    $data = array(
                            'key_words'     => $postData['keywordStr'][$key],
                            'match_type'    => $postData['matchMode'][$key],
                            'update_time'   => date('YmdHis')
                    );
                    M('twx_msgkeywords')->where(array('node_id'=>$nodeId,'message_id'=>$postData['messageId'],'id'=>$value))->save($data);
                }
            }
        }

        //添加关键词
        foreach($postData['keywordStr'] as $key => $value){
            if(empty($postData['kwdId'][$key])){

                $count = M('twx_msgkeywords')->where(array('node_id' => $nodeId,'key_words' => $value))->count();
                if ($count >= 1) {
                    M()->rollback();
                    return array('status' => 0,'info' => "保存失败，关键词" . $value . "已存在");
                }else{
                    $data = array(
                        'node_id'       => $nodeId,
                        'message_id'    => $postData['messageId'],
                        'key_words'     => $value,
                        'match_type'    => $postData['matchMode'][$key],
                        'add_time'      => date('YmdHis'),
                        'update_time'   => date('YmdHis')
                    );
                    M('twx_msgkeywords')->add($data);
                }
            }
        }
    /******************卡券处理******************/
        //如果有多张相同的卡券的时候 (要添加的卡券)
        $repeatCard = array();
        $newCard = array();
        foreach($postData['goodsId'] as $key => $value){
            if($postData['batchId'][$key] == 2){
                $newCard[$key] = $value;
            }
        }
        $countCard = array_count_values($newCard);
        if(!empty($countCard)) {
            foreach ($newCard as $key => $value) {
                if ($countCard[$value] > 1) {
                    $repeatCard[$value] += $postData['cardCount'][$key];
                } else {
                    $repeatCard[$value] = $postData['cardCount'][$key];
                }
            }
        }
        //所有卡券的数组数据
        $allCard = array();
        if(!empty($repeatCard)){
            foreach($repeatCard as $key => $value){         //开始检测数量及库存的扣减(有新增时)
                $cardInfo = M('tgoods_info')->lock(true)->field(' a.*,(b.quantity-b.card_get_num) remainnum,b.card_get_num,b.date_type,b.date_begin_timestamp,b.date_end_timestamp,b.date_fixed_timestamp,b.date_fixed_begin_timestamp ')->join(' a left join twx_card_type b on a.goods_id=b.goods_id ')->where(array('a.node_id'=>$nodeId,'a.goods_id'=>$key))->find();
                if($cardInfo['source'] == 6){       //红包
                    if($value>$cardInfo['remain_num']){
                        $result['cardNum'] = false;
                        $result['SQL'] = array('goodsId'=>$key,'tipStr'=>'红包:'.$postData['goodsId'].'剩余数量:'.$cardInfo['remainnum'].'将要投放数量:'.$value);
                        M()->rollback();
                        return $result;
                    }else{                         //库存扣减
                        $isOk = M('tgoods_info')->where(array('node_id' => $nodeId, 'goods_id' => $key))->save(array('remain_num'=>($cardInfo['remain_num']-$value)));
                        if(!$isOk){
                            $result['return'] = false;
                            $result['SQL']    = '卡券或红包库存扣减失败:'.M()->getLastSql();
                            M()->rollback();
                            return $result;
                        }
                    }
                }else {                             //卡券
                    if ($value > $cardInfo['remainnum']) {
                        $result['cardNum'] = false;
                        $result['SQL']     = array(
                                'goodsId' => $key,
                                'tipStr'  => '卡券:' . $postData['goodsId'] . '剩余数量:' . $cardInfo['remainnum'] . '将要投放数量:' . $value
                        );
                        M()->rollback();
                        return $result;
                    } else {                        //库存扣减
                        $isOk = M('twx_card_type')->where(array('node_id' => $nodeId, 'goods_id' => $key))->save(array('card_get_num'=>($cardInfo['card_get_num']+$value)));
                        if(!$isOk){
                            $result['return'] = false;
                            $result['SQL']    = '卡券或红包库存扣减失败:'.M()->getLastSql();
                            M()->rollback();
                            return $result;
                        }
                    }
                }
                $allCard[] = $cardInfo;
            }
        }
        //关闭不使用的卡券
        $cards = M('tbatch_info')->field('a.id')->join(' a left join twx_message b on a.m_id=b.m_id ')->where(array('b.node_id'=>$nodeId,'b.status'=>0,'b.response_type'=>6,'a.status'=>0,'b.id'=>$postData['messageId']))->select();
        $oldBatchIds = array();
        foreach($cards as $key => $value){
            $oldBatchIds[] = $value['id'];
        }
        $deleteId = array_diff($oldBatchIds,$postData['batchId']);
        if(!empty($deleteId)){            //并且库存返还
            $backNumList = M('tbatch_info')->where(array('id'=>array('in',implode(',',$deleteId)),'node_id'=>$nodeId))->select();
            foreach($backNumList as $key => $value){
                if($value['batch_type'] == 6){              //红包
                    $goodsNum = M('tgoods_info')->where(array('goods_id'=>$value['goods_id']))->getField('remain_num');
                    $isOk = M('tgoods_info')->where(array('node_id'=>$nodeId,'goods_id'=>$value['goods_id']))->save(array('remain_num'=>($goodsNum+$value['remain_num'])));//setInc('remain_num',$value['remain_num']);//save(array('remain_num'=>$goodsInfo['remain_num']+$value['remain_num']));
                }else{
                    $goodsNum = M('twx_card_type')->where(array('goods_id'=>$value['goods_id']))->getField('card_get_num');//卡券
                    $isOk = M('twx_card_type')->where(array('node_id'=>$nodeId,'goods_id'=>$value['goods_id']))->save(array('card_get_num'=>($goodsNum-$value['remain_num'])));//setDec('card_get_num',$value['remain_num']);
                }
                if(!$isOk){
                    $result['return'] = false;
                    $result['SQL'] = '库存回退失败'.M()->getLastSql();
                    M()->rollback();
                    return $result;
                }
            }
            M('tcj_batch')->where(array('node_id'=>$nodeId,'b_id'=>array('in',implode(',',$deleteId))))->save(array('status'=>2));
        }
        //要修改的卡券
        $editCard = array_intersect($postData['batchId'],$oldBatchIds);
        foreach($editCard as $key => $value){
            $isOk = M('tbatch_info')->where(array('id'=>$value,'node_id'=>$nodeId))->save(array('join_rule'=>$postData['respContent'][$key]));
            if($isOk === false){
                $result['return'] = false;
                $result['SQL'] = '修改回复内容失败[1]'.M()->getLastSql();
                M()->rollback();
                return $result;
            }
        }
        //开始添加卡券
        $cjRuleId = M('tcj_rule')->where(array('node_id'=>$nodeId,'batch_id'=>$postData['m_id']))->getField('id');
        foreach($postData['goodsId'] as $key => $value){
            if($postData['batchId'][$key] == 2) {
                $data = array(
                        'cardCount'   => $postData['cardCount'][$key],
                        'source'      => $postData['source'][$key],
                        'dayLimit'    => $postData['dayLimit'][$key],
                        'cardId'      => $postData['cardId'][$key],
                        'sendType'    => $postData['sendType'][$key],
                        'respContent' => $postData['respContent'][$key],
                        'marketId'    => $postData['m_id'],
                        'active'      => '互动有礼',
                        'userId'      => $postData['userId'],
                        'goodsId'     => $value,
                        'cjRuleId'    => $cjRuleId
                );
                foreach($allCard as $key2 => $value2){
                    if($value == $value2['goods_id']){
                        $data = array_merge($data,$value2);
                    }
                }
                $this->onePrizeAdd($data,array(),$result);
                if(!$result['return']){
                    M()->rollback();
                    return $result;
                }
            }
        }
        M()->commit();
        return $result;
    }

    /**
     * 互动有礼的添加
     * @param  array    $postData       表单提交过来的数据
     * @param  string   $nodeId         商户编号
     * @return mixed
     */
    public function interactAdd($postData, $nodeId){
        $result = array('hasCard'=>true,'return'=>true,'cardNum'=>true,'SQL'=>'');
        $explain_info   = '您已领过奖品，邀请更多好友一起参加吧！';
        if(!empty($postData['explain_info'])){
            $explain_info = $postData['explain_info'];
        }
        $regret_info    = '很遗憾奖品都被领完啦！';
        if(!empty($postData['regret_info'])){
            $regret_info = $postData['regret_info'];
        }
        $day_limit_info = '今天的奖品都领完了，明天再来吧！';
        if(!empty($postData['day_limit_info'])){
            $day_limit_info = $postData['day_limit_info'];
        }

        //检测是否有重复的关键词
        foreach ($postData['keywordStr'] as $val) {

            $count = M('twx_msgkeywords')->where(array('node_id' => $nodeId,'key_words' => $val))->count();
            if ($count) {
                return array('status' => 0,'info' => "保存失败，关键词" . $val . "已存在");
            }
        }
        M()->startTrans();
        // 先加到 twx_message表
        $messageData = array(
                'node_id' => $nodeId,
                'message_name' => $postData['ruleName'],
                'response_type' => 6,
                'status' => 0,
                'begin_time' => dateformat($postData['begin_time'], 'YmdHis'),
                'end_time' => dateformat($postData['end_time'], 'YmdHis'),
                'add_time' => date('YmdHis'),
                'update_time' => date('YmdHis'),
                'explain_info' => $explain_info,
                'regret_info'  => $regret_info,
                'day_limit_info' => $day_limit_info
        );
        $messageId = M('twx_message')->add($messageData);
        if (!$messageId) {
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            M()->rollback();
            return $result;
        }

        // 添加关键词
        foreach ($postData['keywordStr'] as $k => $v) {
            $keyWordsData = array(
                    'node_id' => $nodeId,
                    'message_id' => $messageId,
                    'key_words' => $v,
                    'match_type' => $postData['matchMode'][$k],
                    'add_time' => date('YmdHis'),
                    'update_time' => date('YmdHis')
            );
            $msgKeyWordsResult = M('twx_msgkeywords')->add($keyWordsData);

            if (!$msgKeyWordsResult) {
                M()->rollback();
                $result['return'] = false;
                $result['SQL'] = M()->getLastSql();
                return $result;
            }
        }
        //如果有多张相同的卡券的时候
        $repeatCard = array();
        $countCard = array_count_values($postData['goodsId']);
        foreach($postData['goodsId'] as $key => $value){
            if($countCard[$value] >1) {
                $repeatCard[$value] +=$postData['cardCount'][$key];
            }
        }
        //组合成奖品arr  ，如有重复的奖品就把数量合并
        foreach($postData['goodsId'] as $key => $value){
            if(empty($repeatCard[$value])){
                $repeatCard[$value] = $postData['cardCount'][$key];
            }
        }
//        if(!empty($repeatCard)){
        //为避开额外多余的多查询所以要把下面查到的卡券都存起来
        $allCards = array();
        //库存量的检测及扣减
            foreach($repeatCard as $key => $value){
                $cardInfo = M('tgoods_info')->lock(true)->field(' a.*,(b.quantity-b.card_get_num) remainnum,b.card_get_num,b.date_type,b.date_begin_timestamp,b.date_end_timestamp,b.date_fixed_timestamp,b.date_fixed_begin_timestamp ')->join(' a left join twx_card_type b on a.goods_id=b.goods_id ')->where(array('a.node_id'=>$nodeId,'a.goods_id'=>$key))->find();
                if($cardInfo['source'] == 6){       //红包
                    if($value>$cardInfo['remain_num']){      //数量检测
                        $result['cardNum'] = false;
                        $result['SQL'] = array('goodsId'=>$key,'tipStr'=>'红包:'.$key.'剩余数量:'.$cardInfo['remainnum'].'将要投放数量:'.$value);
                        M()->rollback();
                        return $result;
                    }else{              //红包的库存扣减
                        $isOk = M('tgoods_info')->where(array('node_id' => $nodeId, 'goods_id' => $key))->save(array('remain_num'=>($cardInfo['remain_num']-$value)));
                        if(!$isOk){
                            $result['return'] = false;
                            $result['SQL']    = '卡券或红包库存扣减失败:'.M()->getLastSql();
                            M()->rollback();
                            return $result;
                        }
                    }
                }else{                           //卡券
                    if($value>$cardInfo['remainnum']){
                        $result['cardNum'] = false;
                        $result['SQL'] = array('goodsId'=>$key,'tipStr'=>'卡券:'.$key.'剩余数量:'.$cardInfo['remainnum'].'将要投放数量:'.$value);
                        M()->rollback();
                        return $result;
                    }else{                       //卡券的库存扣减
                        $isOk = M('twx_card_type')->where(array('node_id' => $nodeId, 'goods_id' => $key))->save(array('card_get_num'=>($cardInfo['card_get_num']+$value)));
                        if(!$isOk){
                            $result['return'] = false;
                            $result['SQL']    = '卡券或红包库存扣减失败:'.M()->getLastSql();
                            M()->rollback();
                            return $result;
                        }
                    }
                }
                $allCards[] = $cardInfo;
            }
//        }
        //获取batchId
        $start_time = dateformat($postData['begin_time'], 'YmdHis');
        $end_time = dateformat($postData['end_time'], 'YmdHis');
        $marketRet = $this->addReplyMessage(array('start_time'=>$start_time,'end_time'=>$end_time), $nodeId, '互动有礼', array(), '2');
        // 添加回复内容表
        foreach ($postData['goodsId'] as $k => $v) {
            $dataAdd = array(
                'cardCount'     => $postData['cardCount'][$k],
                'source'        => $postData['source'][$k],
                'dayLimit'      => $postData['dayLimit'][$k],
                'cardId'        => $postData['cardId'][$k],
                'sendType'      => $postData['sendType'][$k],
                'respContent'   => $postData['respContent'][$k],
                'marketId'      => $marketRet['marketId'],
                'active'        => '互动有礼',
                'userId'        => $postData['userId'],
                'cjRuleId'        => $marketRet['cjRuleId'],
            );
            foreach($allCards as $key => $value){
                if($value['goods_id'] == $v){
                    $dataAdd = array_merge($dataAdd,$value);
                }
            }
            $this->onePrizeAdd($dataAdd,array(),$result);
            if(!$result['return']){
                M()->rollback();
                return $result;
            }
        }
        $upMarketId = M('twx_message')->where(array('id'=>$messageId))->save(array('m_id'=>$marketRet['marketId']));
        if (!$upMarketId) {
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            M()->rollback();
            return $result;
        }

        M()->commit();
        return $result;

    }
    /**
     * 给活动添加一个奖品
     * @param  array     $everyDefault     所需存储数据，含(tgoods_info、twx_card_type、页面中传进来的、)
     * @param  array     $extraData        可由外部来完全控制所要存储的字段值，格式：array('tbatch_info'=>array('node_id'=>$this->node_id),'tcj_rule'=>array('node_id'=>$this->node_id))
     * @param  array     $result           引用外部数组
     * @return array
     */
    public function onePrizeAdd($everyDefault, $extraData = array('tbatch_info'=>array(),'tcj_rule'=>array(),'tcj_batch'=>array()), &$result = array()){
        //存入tbatch_info表
        $batchInfoData = array(
                'batch_no'         => $everyDefault['batch_no'],
                'batch_short_name' => $everyDefault['active'],
                'node_id'          => $everyDefault['node_id'],
                'user_id'          => $everyDefault['userId'],
                'batch_class'      => $everyDefault['goods_type'],
                'begin_time'       => date('YmdHis'),
                'end_time'         => date('YmdHis', strtotime('+10 year')),
                'add_time'         => date('YmdHis'),
                'batch_type'       => 6,
                'status'           => 0,
                'm_id'             => $everyDefault['marketId'],
                'remain_num'       => $everyDefault['cardCount'],
                'goods_id'         => $everyDefault['goods_id'],
                'storage_num'      => $everyDefault['cardCount'],
                'card_id'          => '',
                'join_rule'        => $everyDefault['respContent'],
                'print_text'       => $everyDefault['print_text']
        );
        if ($everyDefault['source'] != 6) {         //卡券
            $batchInfoData['batch_type'] = 0;
            $batchInfoData['card_id']    = $everyDefault['cardId'];
            if ($everyDefault['date_type'] == 1) {      //日期格式的
                $batchInfoData['verify_begin_date'] = $everyDefault['date_begin_timestamp'];
                $batchInfoData['verify_end_date']   = $everyDefault['date_end_timestamp'];
                $batchInfoData['verify_begin_type'] = 0;
                $batchInfoData['verify_end_type']   = 0;
            } else {                                 //天数格式的
                $batchInfoData['verify_begin_date'] = $everyDefault['date_fixed_timestamp'];         //共有多少天有效
                $batchInfoData['verify_end_date']   = $everyDefault['date_fixed_begin_timestamp'];     //领取后第几天开始有效
                $batchInfoData['verify_begin_type'] = 1;
                $batchInfoData['verify_end_type']   = 1;
            }
        } else {                                   //红包
            $batchInfoData['batch_type'] = 6;
            //设置微信红包的活动时间
            $batchInfoData['verify_begin_date'] = date('YmdHis');
            $batchInfoData['verify_end_date']   = date('YmdHis', strtotime('+10 year'));
            $batchInfoData['material_code'] = $everyDefault['customer_no'];
            $batchInfoData['batch_amt'] = $everyDefault['goods_amt'];
            $batchInfoData['batch_name'] = $everyDefault['goods_name'];
            $batchInfoData['batch_desc'] = $everyDefault['goods_desc'];
            $batchInfoData['batch_no'] = '';
            $batchInfoData['verify_begin_type'] = '0';
            $batchInfoData['verify_end_type'] = '0';

        }
        //补充外部传来的字段值
        if(!empty($extraData['tbatch_info'])){
            $batchInfoData = array_merge($extraData['tbatch_info'],$batchInfoData);
        }
        $batchId = M('tbatch_info')->add($batchInfoData);
        if (!$batchId) {
            $result['return'] = false;
            $result['SQL']    = M()->getLastSql();
            return $result;
        }
        //存入tcj_batch表
        $cjBatchData = array(
                'batch_id'     => $everyDefault['marketId'],
                'node_id'      => $everyDefault['node_id'],
                'activity_no'  => $everyDefault['batch_no'],
                'award_origin' => 2,
                'award_rate'   => 1,
                'status'       => 1,
                'total_count'  => $everyDefault['cardCount'],
                'cj_rule_id'   => $everyDefault['cjRuleId'],
                'goods_id'     => $everyDefault['goodsId'],
                'add_time'     => date('YmdHis'),
                'b_id'         => $batchId,
                'day_count'    => $everyDefault['cardCount'],
                'send_type'    => 0
        );
        //每日发放奖品的量
        if ($everyDefault['sendType'] == 1) {       //有上限
            $cjBatchData['day_count'] = $everyDefault['dayLimit'];
        }
        //补充外部传来的字段值
        if(!empty($extraData['tcj_batch'])){
            $cjBatchData = array_merge($extraData['tcj_batch'],$cjBatchData);
        }
        $cjBatchResult = M('tcj_batch')->add($cjBatchData);
        if (!$cjBatchResult) {
            $result['return'] = false;
            $result['SQL']    = M()->getLastSql();
            return $result;
        }
        return array('batchId'=>$batchId,'cjBatchId'=>$cjBatchResult);

    }

    /*
     * 编辑呼朋引伴
     * @param   array   $postData       POST提交过来的表单数据
     * @param   array   $configData     呼朋引伴专有的分享内容
     * @param   string  $from           调用的来源   1-呼朋引伴   2-互动有礼
     * @return  mixed
     */
    public function editReplyMessage($postData, $nodeId, $configData=array(),$from = 1){
        $result = array('hasCard'=>true,'return'=>true,'cardNum'=>true,'SQL'=>'');
        //商品
        $goodsInfo = M('tgoods_info')->field(' a.*,(b.quantity-b.card_get_num) remainnum,b.card_get_num,b.date_type,b.date_begin_timestamp,b.date_end_timestamp,b.date_fixed_timestamp,b.date_fixed_begin_timestamp ')->join(' a left join twx_card_type b on a.goods_id=b.goods_id ')->where(array('a.node_id'=>$nodeId,'a.goods_id'=>$postData['goodsId']))->find();
        if(empty($goodsInfo)){
            $result['hasCard'] = false;
            $result['SQL']     = M()->getLastSql();
            return $result;
        }
        //原消息
        $where       = array(
                'a.node_id' => $nodeId,
                'a.response_type' => 7,
                'a.status' => 0,
                'a.id' => $postData['messageId'],
                'f.status' => 1
        );
        //红包
        $join = ' a right join tbatch_info b on a.m_id=b.m_id inner join tcj_batch f on b.id=f.b_id inner join tgoods_info c on b.goods_id=c.goods_id ';
        $batchData = M('twx_message')->field('b.*,c.remain_num as g_remain_num')->join($join)->where($where)->find();
        if($batchData['batch_type'] != 6){          //卡券
            $join = ' a right join tbatch_info b on a.m_id=b.m_id inner join tcj_batch f on b.id=f.b_id inner join tgoods_info c on b.goods_id=c.goods_id inner join twx_card_type d on c.goods_id=d.goods_id ';
            $batchData = M('twx_message')->field('b.*,c.source,c.remain_num as g_remain_num,d.card_get_num')->join($join)->where($where)->find();
        }
        //奖品信息
        $cjBatchInfo = M('tcj_batch')->where(array('node_id'=>$nodeId,'id'=>$batchData['id']))->find();

        //检测库存量是否可按变动
        if($postData['source'] == 6){            //红包
            log_write('原库存:'.$goodsInfo['remain_num'].'需要变动的值:'.$postData['cardCount']);
            if($postData['cardCount'] > $goodsInfo['remain_num']){
                $result['cardNum'] = false;
                $result['SQL'] = array('goodsId'=>$postData['goodsId'],'tipStr'=>'卡券:'.$postData['goodsId'].'剩余数量:'.$goodsInfo['remainnum'].'将要投放数量:'.$postData['cardCount']);
                return $result;
            }
        }else{                                   //卡券
            log_write('原库存:'.$goodsInfo['remainnum'].'需要变动的值:'.$postData['cardCount']);
            if($postData['cardCount'] > $goodsInfo['remainnum']){
                $result['cardNum'] = false;
                $result['SQL'] = array('goodsId'=>$postData['goodsId'],'tipStr'=>'卡券:'.$postData['goodsId'].'剩余数量:'.$goodsInfo['remainnum'].'将要投放数量:'.$postData['cardCount']);
                return $result;
            }
        }
        if($from == 1) {
            M()->startTrans();
            if($batchData['goods_id'] != $postData['goodsId']){           //换卡了
                //旧卡库存退换
                if($batchData['batch_type'] == 6){    //红包
                    $isOk = M('tgoods_info')->where(array('node_id'=>$nodeId,'goods_id'=>$batchData['goods_id']))->save(array('remain_num'=>($batchData['g_remain_num']+$batchData['remain_num'])));

                }else{                           //卡券
                    $isOk = M('twx_card_type')->where(array('node_id'=>$nodeId,'goods_id'=>$batchData['goods_id']))->save(array('card_get_num'=>($batchData['card_get_num']-$batchData['remain_num'])));

                }
                if($isOk === false){
                    $result['return'] = false;
                    $result['SQL']    = '卡券或红包库存扣减失败'.M()->getLastSql();
                    M()->rollback();
                    return $result;
                }
                //修改被删掉的卡券的状态
                $banCard = M('tcj_batch')->where(array('b_id'=>$batchData['id'],'node_id'=>$nodeId))->save(array('status'=>2));
                if($banCard === false){
                    $result['return'] = false;
                    $result['SQL']    = '卡券状态修改失败'.M()->getLastSql();
                    M()->rollback();
                    return $result;
                }
                //新卡扣减
                if($goodsInfo['source'] == 6){
                    $isOk = M('tgoods_info')->where(array('node_id'=>$nodeId,'goods_id'=>$postData['goodsId']))->save(array('remain_num'=>$goodsInfo['remain_num']-$postData['cardCount']));
                }else{
                    $isOk = M('twx_card_type')->where(array('node_id'=>$nodeId,'goods_id'=>$postData['goodsId']))->save(array('card_get_num'=>$goodsInfo['card_get_num']+$postData['cardCount']));
                }
                if($isOk === false){
                    $result['return'] = false;
                    $result['SQL']    = '卡券或红包库存扣减失败'.M()->getLastSql();
                    M()->rollback();
                    return $result;
                }
                //新卡的添加
                $cjRule = M('tcj_rule')->where(array('batch_id'=>$batchData['m_id'],'node_id'=>$nodeId))->getField('id');
                $goodsInfo['marketId'] = $batchData['m_id'];
                $goodsInfo['active'] = '呼朋引伴';
                $goodsInfo['cjRuleId'] = $cjRule;
                $this->onePrizeAdd(array_merge($postData,array(),$goodsInfo),array(),$result);
                if($result['return'] == false){
                    M()->rollback();
                    return $result;
                }
            }else{                                       //原活动原卡券的变动
                if($postData['batchId'] == 2) {
                    //需要变更的库存量
                    $editNum = (int)($batchData['remain_num'] - $postData['cardCount']);
                    if ($editNum != 0) {    //当添加的卡券为原卡券并且库存有变动时
                        if ($batchData['batch_type'] == 6) {    //红包
                            $isOk = M('tgoods_info')->where(
                                    array('node_id' => $nodeId, 'goods_id' => $batchData['goods_id'])
                            )->save(array('remain_num' => ($batchData['g_remain_num'] + $editNum)));

                        } else {                           //卡券
                            $isOk = M('twx_card_type')->where(
                                    array('node_id' => $nodeId, 'goods_id' => $batchData['goods_id'])
                            )->save(array('card_get_num' => ($batchData['card_get_num'] - $editNum)));

                        }
                        if ($isOk === false) {
                            $result['return'] = false;
                            $result['SQL']    = '原来的卡券或红包库存扣减失败' . M()->getLastSql();
                            M()->rollback();
                            return $result;
                        }
                    }
                    //总数
                    $cjBatchData = array('total_count' => $postData['cardCount'],'day_count'=>$postData['cardCount']);
                    //有日上限
                    if ($postData['sendType'] == 1) {       //有上限
                        $cjBatchData['day_count'] = $postData['dayLimit'];
                    }
                    $editCjBatch  = M('tcj_batch')->where(array('node_id' => $nodeId, 'b_id' => $batchData['id']))->save($cjBatchData);
                    if($editCjBatch === false){
                        $result['return'] = false;
                        $result['SQL']    = '原来的卡券或红包库存的奖品总数或日上限修改失败'.M()->getLastSql();
                        M()->rollback();
                        return $result;
                    }
                }
                $saveBatchInfo = M('tbatch_info')->where(array('status'=>0,'node_id'=>$nodeId,'id'=>$batchData['id']))->save(array('remain_num'=>$postData['cardCount'],'join_rule'=>$postData['respContent']));
                if($saveBatchInfo === false){
                    $result['return'] = false;
                    $result['SQL']    = '奖品回复内容修改失败'.M()->getLastSql();
                    M()->rollback();
                    return $result;
                }
            }
        }
        try {
            //修改tmarketing_info表
            $marketingInfoData = array('config_data' => serialize($configData));
            $marketingResult   = M('tmarketing_info')->where(array('id' => $batchData['m_id']))->save($marketingInfoData);
            if ($marketingResult === false) {
                $result['return'] = false;
                $result['SQL']    = M()->getLastSql();
                M()->rollback();
                throw new Exception();
            }
            //呼朋引伴与互动有礼成为分支
            if ($from == '1') {
                $this->hpybEdit($postData, $nodeId, $result);
                if($result['return'] == false){
                    M()->rollback();
                    throw new Exception();
                }
                M()->commit();
            }
        }catch(Exception $e){
            return $result;
        }

        return $result;
    }

    /**
    * 呼朋引伴的修改
     * @param return mixed
    */
    public function hpybEdit($postData,$nodeId,&$result){
        //修改twx_message表
        $messageData = array(
                'update_time'   => date('YmdHis'),
                'congratulations_info' => $postData['respContent'],         //关注回复的文本消息
                'regret_info'   => $postData['color'],                      //分享页面的颜色
                'explain_info'  => $postData['respDesc']                    //分享的描述
        );
        $messageResult = M('twx_message')->where(array('id'=>$postData['messageId'],'node_id'=>$nodeId))->save($messageData);
        if($messageResult === false){
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            return $result;
        }
        //修改关注引导页
        $weiXinResult = M('tweixin_info')->where(array('node_id' => $nodeId))->save(array('guide_url' => $postData['guide_url']));
        if($weiXinResult === false){
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            return $result;
        }
    }

    /**
     * 添加呼朋引伴
     * @param   array   $postData       POST提交过来的表单数据
     * @param   string  $nodeId         商户编号
     * @param   string  $active         活动名称
     * @param   array   $configData     呼朋引伴专有的分享内容
     * @return  mixed
     */
    public function addReplyMessage($postData, $nodeId, $active, $configData=array(), $from = '1'){
        $result = array('hasCard'=>true,'return'=>true,'cardNum'=>true,'SQL'=>'','batchChannelId'=>'');
        try {
            $goodsInfo = array();
            if ($from == '1') {
                $goodsInfo = M('tgoods_info')->lock(true)->field(
                        ' a.*,(b.quantity-b.card_get_num) remainnum,b.card_get_num,b.date_type,b.date_begin_timestamp,b.date_end_timestamp,b.date_fixed_timestamp,b.date_fixed_begin_timestamp '
                )->join(' a left join twx_card_type b on a.goods_id=b.goods_id ')->where(
                        array('a.node_id' => $nodeId, 'a.goods_id' => $postData['goodsId'])
                )->find();
                if (empty($goodsInfo)) {
                    $result['hasCard'] = false;
                    $result['SQL']     = M()->getLastSql();
                    throw new Exception();
                }

                if($postData['source'] == 6){            //红包
                    if ($postData['cardCount'] > $goodsInfo['remain_num']) {
                        $result['cardNum'] = false;
                        //除此以外均有SQL语句
                        $result['SQL'] = array(
                                'goodsId' => $postData['goodsId'],
                                'tipStr'  => '卡券:' . $postData['goodsId'] . '剩余数量:' . $goodsInfo['remainnum'] . '将要投放数量:' . $postData['cardCount']
                        );
                        throw new Exception();
                    }
                }else{                                  //卡券
                    if ($postData['cardCount'] > $goodsInfo['remainnum']) {
                        $result['cardNum'] = false;
                        //除此以外均有SQL语句
                        $result['SQL'] = array(
                                'goodsId' => $postData['goodsId'],
                                'tipStr'  => '卡券:' . $postData['goodsId'] . '剩余数量:' . $goodsInfo['remainnum'] . '将要投放数量:' . $postData['cardCount']
                        );
                        throw new Exception();
                    }
                }

                $isOpenMessage = M('twx_message')->where(array('node_id' => $nodeId,'response_type' => 0,'status' => 0 ))->find();
                M()->startTrans();
                //先把自动回复的关掉
                if($isOpenMessage){
                    $updateMessage = M('twx_message')->where(array('node_id' => $nodeId,'response_type' => 0,'status' => 0 ))->save(array('status' => 1));
                    if (!$updateMessage) {
                        $result['return'] = false;
                        $result['SQL']    = M()->getLastSql();
                        M()->rollback();
                        throw new Exception();
                    }
                }
                //卡券或红包的库存扣减
                if ($postData['source'] == 6) {       //红包
                    $isOk = M('tgoods_info')->where(
                            array('node_id' => $nodeId, 'goods_id' => $postData['goodsId'])
                    )->save(array('remain_num' => ($goodsInfo['remain_num'] - $postData['cardCount'])));
                } else {                              //卡券
                    $isOk = M('twx_card_type')->where(
                            array('node_id' => $nodeId, 'goods_id' => $postData['goodsId'])
                    )->save(array('card_get_num' => ($goodsInfo['card_get_num'] + $postData['cardCount'])));
                }

                if (!$isOk) {
                    $result['return'] = false;
                    $result['SQL']    = '卡券或红包库存扣减失败:' . M()->getLastSql();
                    M()->rollback();
                    throw new Exception();
                }
            }
            //存入tmarketing_info表
            $marketingInfoData = array(
                    'memo'        => $active,
                    'wap_info'    => $active,
                    'config_data' => serialize($configData),
                    'join_mode'   => 1,
                    'batch_type'  => 9,
                    'start_time'  => date('YmdHis'),
                    'end_time'    => date('YmdHis', strtotime('+10 year')),
                    'is_cj'       => 1,
                    'node_id'     => $nodeId,
                    'name'        => $active
            );
            if($from == '2'){
                $marketingInfoData['start_time'] = $postData['start_time'];
                $marketingInfoData['end_time'] = $postData['end_time'];
            }
            $marketingId = M('tmarketing_info')->add($marketingInfoData);
            if (!$marketingId) {
                $result['return'] = false;
                $result['SQL']    = M()->getLastSql();
                M()->rollback();
                throw new Exception();
            }
            //存入tbatch_channel表
            $batchChannelData = array(
                    'batch_type' => '9',
                    'batch_id'   => $marketingId,
                    'node_id'    => $nodeId,
                    'channel_id' => '0',
                    'add_time'   => date('YmdHis'),
                    'code_img'   => 0
            );
            $batchChannelId   = M('tbatch_channel')->add($batchChannelData);
            if (!$batchChannelId) {
                $result['return'] = false;
                $result['SQL']    = M()->getLastSql();
                M()->rollback();
                throw new Exception();
            }
            //存入tcj_rule表          获奖概率
            $cjRuleData = array(
                    'batch_id'          => $marketingId,
                    'jp_set_type'       => 1,
                    'day_count'         => 1,
                    'total_chance'      => 100,
                    'cj_check_flag'     => 0,
                    'node_id'           => $nodeId,
                    'add_time'          => date('YmdHis'),
                    'status'            => 1,
                    'phone_total_count' => 1,
                    'phone_day_count'   => 1,
                    'phone_total_part'  => 999,
                    'phone_day_part'    => 999,
                    'batch_type'        => 9
            );
            $cjRuleId   = M('tcj_rule')->add($cjRuleData);
            if (!$cjRuleId) {
                $result['return'] = false;
                $result['SQL']    = M()->getLastSql();
                M()->rollback();
                throw new Exception();
            }
            //呼朋引伴与互动有礼成为分支
            if ($from == '1') {
                $postData['marketId'] = $marketingId;
                $postData['active'] = $active;
                $postData['cjRuleId'] = $cjRuleId;

                $this->onePrizeAdd(array_merge($postData,array(),$goodsInfo), array(), $result);
                if($result['return']){
                    M()->commit();
                }else{
                    M()->rollback();
                    throw new Exception();
                }

                $this->hpybAdd($result, $postData, $nodeId);
                if($result['return']){
                    M()->commit();          //互动有礼在调用的地方提交
                }else{
                    M()->rollback();
                    throw new Exception();
                }
            }
            if ($from == '2') {
                return array('marketId'=>$marketingId,'cjRuleId'=>$cjRuleId);
            }

        }catch(Exception $e){
            return $result;
        }
        return $result;
    }
    /**
     * 呼朋引伴的添加
     * @return mixed
     */
    public function hpybAdd(&$result, $postData, $nodeId){
        //保存twx_message表
        $messageData = array(
                'node_id'       => $nodeId,
                'response_type' => 7,
                'message_name'  => $postData['active'],
                'status'        => 0,
                'add_time'      => date('YmdHis'),
                'update_time'   => date('YmdHis'),
                'begin_time'    => date('YmdHis'),
                'm_id'          => $postData['marketId'],
                'congratulations_info' => $postData['respContent'],
                'explain_info'  => $postData['respDesc'],
                'regret_info'   => $postData['color']
        );
        $messageId = M('twx_message')->add($messageData);
        if(!$messageId){
            $result['return'] = false;
            $result['SQL'] = M()->getLastSql();
            return $result;
        }
        //保存关注引导页
        if(!empty($postData['guide_url'])){
            $weiXinResult = M('tweixin_info')->where(array('node_id' => $nodeId))->save(array('guide_url' => $postData['guide_url']));
            if($weiXinResult === false){
                $result['return'] = false;
                $result['SQL'] = M()->getLastSql();
                return $result;
            }
        }
        return array('twx_message'=>$messageId);

    }
    // 获取呼朋引伴活动内容
    public function getHpybInfo($node_id) {
        $info['message_info'] = M('twx_message')
            ->where(
            array(
                'node_id' => $node_id, 
                'response_type' => 7, 
                'status' => 0))
            ->find();
        $info['weixin_info'] = $this->getWeixinInfo($node_id);
        $info['card_info'] = $this->getSendTicket($info['message_info']['m_id']);
        return $info;
    }
    
    // 获取呼朋引伴活动数据
    public function getHpybList($node_id) {
        import('ORG.Util.Page');
        $where = array(
                    'node_id' => $node_id, 
                    'response_type' => 7);
        $mapcount = M('twx_message')->where($where)->count();
        $Page = new Page($mapcount, 10);
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $list['page'] = $Page->show(); // 分页显示输出
        $list['list'] = M('twx_message')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id desc')
            ->select();
        return $list;
    }
    
    // 呼朋引伴活动关闭
    public function hpybDel($node_id) {
        $hpyb = M('twx_message')->where(array('node_id'=>$node_id,'response_type'=>7,'status'=>0))->find();
        //关闭呼朋引伴
        $end_time = date('YmdHis');
        $data['status'] = 1;
        $data['end_time'] = $end_time;
        M()->startTrans();
        $result = M('twx_message')->where(
            array(
                'node_id' => $node_id, 
                'response_type' => 7, 
                'status' => 0))->save($data);
        if(!$result){
            log_write('活动关闭失败[twx_message]:'.M()->getLastSql());
            M()->rollback();
            return array('status'=>0,'info'=>'关闭失败');
        }

        $batchIds = array();
        //退还卡券库存
        $goodsList = M('tbatch_info')->where(array('node_id'=>$node_id,'m_id'=>$hpyb['m_id']))->select();
        foreach($goodsList as $key => $value){
            if($value['batch_type'] == 6){       //红包
                $goodsNum = M('tgoods_info')->where(array('goods_id'=>$value['goods_id']))->getField('remain_num');
                $isOk = M('tgoods_info')->where(array('node_id'=>$node_id,'goods_id'=>$value['goods_id']))->save(array('remain_num'=>($goodsNum+$value['remain_num'])));
            }else{
                $goodsNum = M('twx_card_type')->where(array('goods_id'=>$value['goods_id']))->getField('card_get_num');
                $isOk = M('twx_card_type')->where(array('node_id'=>$node_id,'goods_id'=>$value['goods_id']))->save(array('card_get_num'=>($goodsNum-$value['remain_num'])));
            }
            if($isOk === false){
                log_write('活动关闭失败[tgoods_info || twx_card_type]:'.M()->getLastSql());
                M()->rollback();
                return array('status'=>0,'info'=>'关闭失败');
            }
            $batchIds[] = $value['id'];
        }
        $isClose = M('tbatch_info')->where(array('node_id'=>$node_id,'id'=>array('in',implode(',',$batchIds))))->save(array('status'=>1));
        if(!$isClose){
            log_write('活动关闭失败[tbatch_info]:'.M()->getLastSql());
            M()->rollback();
            return array('status'=>0,'info'=>'关闭失败');
        }
        //开启自动回复
        $result = M('twx_message')->where(
            array(
                'node_id' => $node_id, 
                'response_type' => 0, 
                'status' => 1))->save(array(
            'status' => 0));

        if(!$result){
            log_write('自动回复开启失败[twx_message]:'.M()->getLastSql());
            M()->rollback();
            return array('status'=>0,'info'=>'关闭失败');
        }
        M()->commit();

        return array('status'=>1,'info'=>'关闭成功');
    }

    // 根据根据活动号获取发送的卡券或者红包
    public function getSendTicket($batchId){
        $field = ' a.config_data,b.id,b.goods_id,b.card_id,b.remain_num,b.batch_type,b.join_rule,c.total_count,c.day_count ';
        $where = array('a.id'=>$batchId,'c.status'=>1);
        $join = ' a right join tbatch_info b on a.id=b.m_id inner join tcj_batch c on b.id=c.b_id';
        $goods = M('tmarketing_info')->field($field)->join($join)->where($where)->select();
        $retCards = array();
        foreach($goods as $key => $value) {
            if (empty($value['card_id'])) {            //是个红包
                $card_info = M()->table('tgoods_info')->where(
                        array('goods_id' => $value['goods_id'], 'source' => 6)
                )->find();
                $card_info = array(
                        'goods_id'    => $card_info['goods_id'],
                        'goods_name'  => $card_info['goods_name'],
                        'time'        => '',
                        'goods_image' => $card_info['goods_image']
                );
            } else {                                   //是个卡券
                $card_id                 = $value['card_id'];
                $card_info               = M()->table("twx_card_type t1")->join(
                        'tgoods_info t2 on t1.goods_id=t2.goods_id'
                )->where(array('t1.card_id' => $card_id))->field(
                        't1.id,t1.goods_id,logo_url,title,date_type,date_begin_timestamp,date_end_timestamp,date_fixed_timestamp,date_fixed_begin_timestamp,card_id,quantity,card_get_num,goods_image'
                )->find();
                $card_info['goods_name'] = $card_info['title'];
                if ($card_info['date_type'] == 1) {
                    $card_info['time'] = '有效期：' . date(
                                    'Y-m-d',
                                    $card_info['date_begin_timestamp']
                            ) . '至' . date('Y-m-d', $card_info['date_end_timestamp']);
                } else {
                    $card_info['time'] = '有效期：领取后' . $card_info['date_fixed_begin_timestamp'] . '天开始使用，' . $card_info['date_fixed_timestamp'] . '天结束使用';
                }
            }
            $serializeData = unserialize($value['config_data']);
            $card_info['shareTitle'] = $serializeData['share_title'];
            $card_info['shareDesc'] = $serializeData['share_descript'];
            $card_info['img_resp'] = $serializeData['share_logo'];
            $card_info['source'] = $value['batch_type'];
            $card_info['totalCount'] = $value['total_count'];
            $card_info['surplus'] = $value['remain_num'];
            $card_info['card_id'] = $value['card_id'];
            $card_info['goods_id'] = $value['goods_id'];
            $card_info['dayLimit'] = $value['day_count'];
            $card_info['batchId'] = $value['id'];
            $card_info['sendType'] = 1;
            $card_info['respContent'] = $value['join_rule'];
            $card_info['m_id'] = $batchId;
            if($value['total_count'] == $value['day_count']){        //与日上限相等的话为不限
                $card_info['sendType'] = 0;
            }
            $retCards[] = $card_info;
        }
        return $retCards;
    }
    // 根据card_id获取卡券信息
    public function getCardInfo($card_id) {
        $card_info = M()->table("twx_card_type t1")->join(
                'tgoods_info t2 on t1.goods_id=t2.goods_id')
                ->where(array(
                        't1.card_id' => $card_id))
                ->field('t1.id,logo_url,title,date_type,date_begin_timestamp,date_end_timestamp,date_fixed_timestamp,date_fixed_begin_timestamp,card_id,quantity,card_get_num,goods_image')
                ->find();
        $card_info['goods_name'] = $card_info['title'];
        if ($card_info['date_type'] == 1) {
            $card_info['time'] = date('Y-m-d',
                            $card_info['date_begin_timestamp']) . '至' .
                    date('Y-m-d', $card_info['date_end_timestamp']);
        } else {
            $card_info['time'] = '领取后' . $card_info['date_fixed_begin_timestamp'] .
                    '天开始使用，' . $card_info['date_fixed_timestamp'] . '天结束使用';
        }
        return $card_info;
    }
    //获取红包
    public function getHongbaoInfo($b_id){
        $goodsInfo = M()->table("tbatch_info t2")
                ->join('tgoods_info t1 on t1.goods_id = t2.goods_id')
                ->where("t2.id = '".$b_id."' and source = 6 and t2.status = 0")
                ->field('t1.id,t1.goods_id,t1.goods_name,t1.goods_amt,t1.remain_num + t2.remain_num as total,t1.print_text,t2.remain_num,t2.id as b_id')
                ->find();
        return $goodsInfo;
    }
    // 呼朋引伴活动保存
    public function hpyb($nodeId) {
        $cardid = I('cardId', ''); // 卡券card_id
        $color = I('color', ''); // 背景色
        $respContent = I('respContent'); // 回复内容
        $respDesc = I('respDesc'); // 公众号描述
        $guide_url = I('guide_url'); // 引导关注页
        
        $info = M('twx_message')
            ->where(
            array(
                'node_id' => $nodeId, 
                'response_type' => 7, 
                'status' => 0))
            ->find();
        // return array('status'=>1,'info'=>$info['id']);
        M()->startTrans();
        $result = M('twx_message')->where(
            array(
                'node_id' => $nodeId, 
                'response_type' => 0, 
                'status' => 0))->save(array(
            'status' => 1));
        if ($result === false) {
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "保存失败");
        }
        $result = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))->save(
            array(
                'guide_url' => $guide_url));
        if ($result === false) {
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "保存失败01");
        }
        if ($info) {
            $data['update_time'] = date('YmdHis');
            $result = M('twx_message')->where(
                array(
                    'node_id' => $nodeId, 
                    'response_type' => 7, 
                    'status' => 0))->save($data);
            if ($result === false) {
                M()->rollback();
                return array(
                    'status' => 1, 
                    'info' => "保存失败01");
            }
            $data2['response_info'] = $cardid;
            $data2['congratulations_info'] = $respContent;
            $data2['explain_info'] = $respDesc;
            $data2['regret_info'] = $color;
            $result = M('twx_msgresponse')->where('message_id=' . $info['id'])->save(
                $data2);
            if ($result === false) {
                M()->rollback();
                return array(
                    'status' => 1, 
                    'info' => "保存失败02");
            }
            M()->commit();
            return array(
                'status' => 0, 
                'info' => $info['id']);
        } else {
            $data = array(
                'node_id' => $nodeId, 
                'add_time' => date('YmdHis'), 
                'update_time' => date('YmdHis'), 
                'begin_time' => date('YmdHis'), 
                'response_type' => 7, 
                'message_name' => '呼朋引伴');
            $message_id = M('twx_message')->add($data);
            if (! $message_id) {
                M()->rollback();
                return array(
                    'status' => 1, 
                    'info' => "保存失败03");
            }
            
            $data2 = array(
                'message_id' => $message_id, 
                'node_id' => $nodeId, 
                'response_info' => $cardid, 
                'response_class' => 7, 
                'add_time' => date('YmdHis'), 
                'update_time' => date('YmdHis'), 
                'congratulations_info' => $respContent, 
                'explain_info' => $respDesc, 
                'regret_info' => $color);
            $result = M('twx_msgresponse')->add($data2);
            if (! $result) {
                M()->rollback();
                return array(
                    'status' => 1, 
                    'info' => "保存失败04");
            }
            M()->commit();
            return array(
                'status' => 0, 
                'info' => $message_id);
        }
    }
    
    // 发红包活动保存
    public function hongbao($nodeId,$userId,$fans_info,$name,$goodsId,$remain_num,$send_timing) {
        $number = count($fans_info);
        M()->startTrans();
        $send_timing = $send_timing ? dateformat($send_timing, 'YmdHis') : date('YmdHis');
        $goodsInfo = M('tgoods_info')->where(array('node_id'=>$nodeId,'source'=>'6','goods_id'=>$goodsId))->find();
        $data = array(
            'name' => $name, 
            'batch_type' => 3002,
            'node_id' => $nodeId,
            'is_cj' => '0',
            'add_time' => date('YmdHis'));
        $marketId = M('tmarketing_info')->add($data);
        if($marketId === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动保存失败1");
        }
        $data2 = array(
            'batch_no' => $goodsInfo['batch_no'], 
            'batch_short_name' => $goodsInfo['goods_name'], 
            'batch_name' => $goodsInfo['goods_name'], 
            'node_id' => $nodeId, 
            'user_id' => $userId, 
            'batch_class' => $goodsInfo['goods_type'], 
            'batch_type' => $goodsInfo['source'], 
            'batch_img' => $goodsInfo['goods_image'],  
            'batch_amt' => $goodsInfo['goods_amt'],  
            'add_time' => date('YmdHis'), 
            'batch_desc' => $goodsInfo['goods_desc'], 
            'goods_id' => $goodsId, 
            'storage_num' => $number, 
            'remain_num' => $number, 
            'material_code' => $goodsInfo['customer_no'], 
            'print_text' => $goodsInfo['print_text'], 
            'm_id' => $marketId, 
            'validate_type' => $goodsInfo['validate_type']);
        $batch_id = M('tbatch_info')->add($data2);
        if($batch_id === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动保存失败2");
        }
        $data3 = array(
            'node_id' => $nodeId, 
            'total_count' => $number, 
            'add_time' => date('YmdHis'), 
            'm_id' => $marketId, 
            'b_id' => $batch_id, 
            'bonus_amt' => $goodsInfo['goods_amt'],
            'status' => 0,
            'send_time' => $send_timing);
        $bonus_send_id = M('twx_bonus_send')->add($data3);
        if($bonus_send_id === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动保存失败3");
        }
        foreach($fans_info as $val){
            $data4 = array(
                'bonus_send_id' => $bonus_send_id,
                'node_id' => $nodeId,
                'open_id' => $val['openid'],
                'status' => 0
            );
            $result = M('twx_bonus_send_detail')->add($data4);
            if($result === false){
                M()->rollback();
                return array(
                'status' => 1, 
                'info' => "活动保存失败4");
            }
        }
        M()->commit();
        return array(
            'status' => 0, 
            'batch_id' => $batch_id, 
            'info' => "保存成功");
    }
    
    public function hongbaoToSendInfo($node_id){
        import('ORG.Util.Page');
        $where = array(
                    't1.node_id' => $node_id, 
                    't1.batch_type' => 3002,
                    't3.status' => 0,
                    // 't3.send_time' => array('gt',date('YmdHis'))
                );
        $mapcount = M()->table("tmarketing_info t1")
            ->join('tbatch_info t2 on t1.id = t2.m_id')
            ->join('twx_bonus_send t3 on t1.id = t3.m_id')
            ->where($where)->count();
        $Page = new Page($mapcount, 10);
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $list['page'] = $Page->show(); // 分页显示输出
        $list['list'] = M()->table("tmarketing_info t1")
            ->join('tbatch_info t2 on t1.id = t2.m_id')
            ->join('twx_bonus_send t3 on t1.id = t3.m_id')
            ->field('t1.id,t1.name,t2.id as batch_id,t1.add_time,t2.batch_name,t3.send_time,t3.id as bonus_send_id,t3.total_count')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('t1.id desc')
            ->select();
        return $list;
    }
    
    public function hongbaoDel($id,$bonus_send_id){
        M()->startTrans();
        $result = M('tmarketing_info')->where("id=".$id)->delete();
        if($result === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动删除失败1");
        }
        $result = M('tbatch_info')->where("m_id=".$id)->delete();
        if($result === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动删除失败2");
        }
        $result = M('twx_bonus_send')->where("m_id=".$id)->delete();
        if($result === false){
            M()->rollback();
            return array(
                'status' => 1, 
                'info' => "活动删除失败3");
        }
        $result = M('twx_bonus_send_detail')->where("bonus_send_id=".$bonus_send_id)->delete();
        if($result === false){
            M()->rollback();
            return array(
            'status' => 1, 
            'info' => "活动删除失败4");
        }
        M()->commit();
        return array(
            'status' => 0, 
            'info' => "删除成功");
    }
    
    public function hongbaoStaticInfo($node_id){
        import('ORG.Util.Page');
        $where = array(
                    't1.node_id' => $node_id, 
                    't1.batch_type' => 3002,
                    't3.status' => array('in','2,3'));
        $mapcount = M()->table("tmarketing_info t1")
            ->join('tbatch_info t2 on t1.id = t2.m_id')
            ->join('twx_bonus_send t3 on t1.id = t3.m_id')
            ->where($where)->count();
        $Page = new Page($mapcount, 10);
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $page = $Page->show(); // 分页显示输出
        $list = M()->table("tmarketing_info t1")
            ->join('tbatch_info t2 on t1.id = t2.m_id')
            ->join('twx_bonus_send t3 on t1.id = t3.m_id')
            ->field('t1.id,t1.name,t2.batch_name,t3.send_time,t3.id as bonus_send_id,t3.total_count')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('t1.id desc')
            ->select();
        $result = array('page'=>$page,'list'=>$list);
        return $result;
    }
    
    public function hongbaoStaticDel($id){
        $result = M('twx_bonus_send')->where("m_id=".$id)->save(array("status" => '4'));
        if($result === false){
            M()->rollback();
            return array(
            'status' => 1, 
            'info' => M()->_sql());
        }
        return array(
            'status' => 0, 
            'info' => "删除成功");
    }
    
    public function hongbaoStaticDetail($id,$down = ''){
        $info = M()->table("tmarketing_info t1")
            ->join('tbatch_info t2 on t1.id = t2.m_id')
            ->join('twx_bonus_send t3 on t1.id = t3.m_id')
            ->field('t1.id,t1.name,t2.batch_name,t3.send_time,t3.id as bonus_send_id,t3.total_count')
            ->where("t1.id=".$id)
            ->find();
        $info['send_time'] = dateformat($info['send_time'],'Y-m-d H:i:s');
        $result = array('info'=>$info);
        if($down){
            $list = M()->table("twx_bonus_send_detail t1")
            ->where("bonus_send_id = ".$info['bonus_send_id'])
            ->join("twx_user t2 on t1.open_id = t2.openid and t1.node_id = t2.node_id")
            ->field('t1.*,t2.nickname')
            ->select();
            $result['list'] = $list;
        }
        return $result;
    }
    
    /**
     * 根据openid查昵称
     * @param unknown $openid
     * @param string $field
     * @return mixed|boolean|NULL|string|unknown
     */
    public function getWxUser($openid, $field = '*') {
        $result = M('twx_user')
        ->field($field)
        ->where(array('openid' => $openid))
        ->find();
        return $result;
    }
}
