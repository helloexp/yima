<?php

class MicroWebAction extends MyBaseAction {

    public $upload_path;

    const BATCH_TYPE_MICROWEB = 13;

    const BATCH_CHANNEL_TYPE_MICROWEB = 4;

    const BATCH_SNS_TYPE_MICRFOWEB = 43;

    const LABEL_AIPAI_TYPE_MICROWEB = 3;

    const LABEL_AIPAI_SNS_TYPE_MICROWEB = 36;

    public function _initialize() {
        parent::_initialize();
        $this->upload_path = C('TMPL_PARSE_STRING.__URL_UPLOAD__') .
             '/MicroWebImg/' . $this->node_id . '/';
    }

    public function index() {
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        // 非标渠道跳转url
        $channelInfo = $this->channelInfo;
        $go_url = $channelInfo['go_url'];
        if ($go_url) {
            redirect($go_url);
            exit();
        }
        // 如果当前机构为DF机构，判断用户是否已经关注过门店，如果已经关注过，跳转到指定门店
        if ($this->node_id == C('df.node_id')) {
            $df_openid = session('node_wxid_' . $this->node_id);
            if ($df_openid != "") {
                $openid = $df_openid['openid'];
                $defined_one_name = M("tfb_df_member")->where(
                    array(
                        "openid" => $openid))->getField('fav_store_id');
                if (! empty($defined_one_name)) {
                    $this->location_wxweb($defined_one_name);
                }
            }
        }
        // 访问量
        $channel_type = $channelInfo['sns_type'];
        $number_no = M('tbd_wail')->where(
            array(
                'node_id' => $this->node_id))->getfield('status');
        if ($channel_type != '52' || $number_no == '2') {
            import('@.Vendor.DataStat');
            $opt = new DataStat($this->id, $this->full_id);
            $opt->UpdateRecord();
        }
        
        $batch_model = M('tmarketing_info');
        $model = M('tmicroweb_tpl_cfg');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB, 
            'id' => $batch_id);
        
        $batch_info = $batch_model->where($map)->find();
        if (! $batch_info) {
            $this->error('获取默认营销活动失败');
        }
        
        // 判断模版表中模版是否存在
        $count = 0;
        $tpl_map = array(
            'id' => $batch_info['tpl_id']);
        $count = M('tmicroweb_tpl')->where($tpl_map)->count();
        if ($count < 1) {
            $this->error('商户默认模版错误' . $count);
        }
        
        $tpl_cfg_map = array(
            'node_id' => $this->node_id, 
            'tpl_id' => $batch_info['tpl_id'], 
            'mw_batch_id' => $batch_info['id']);
        $list = $model->where($tpl_cfg_map)
            ->order('order_id')
            ->select();
        
        // $label_id=$this->getaipaichannel();
        // 获取爱拍微官网的跳转地址
        // $aipai_url =
        // 'http://222.44.51.34/gCenter/index.php?label_id='.$label_id;
        $node_level = $this->getNodeLevel();
        
        $node_data = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))
            ->Field('node_short_name,world_cup_flag')
            ->find();
        $node_name = $node_data['node_short_name'];
        $worldCup_flag = $node_data['world_cup_flag'];
        // 直达号渠道
        $number = '';
        if ($channel_type == '52') {
            $number = M('tbd_wail')->where(
                array(
                    'node_id' => $this->node_id, 
                    '_string' => "status='1' or status='2'"))->getfield('app_id');
        }
        $list =  $this->get_cfg_array($list);
        $this->assign('number', $number);
        $this->assign('list',$list);
        $this->assign('aipai_url', '');
        $this->assign('batch_name', $batch_info['name']);
        $this->assign('node_name_for_title', $batch_info['node_name']);
        $this->assign('tpl_id', $batch_info['tpl_id']);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_name', $node_name);
        $this->assign('worldCup_flag', $worldCup_flag);
        $this->assign('node_level', $node_level);


        if ($batch_info['tpl_id'] == '15') {
            $this->display('index_15'); // 输出模板
        } else if ($batch_info['tpl_id'] == '16') {
            $this->display('index_16');
        } else if ($this->node_id==C('fjjh.node_id')) {
            $this->display('fjjh');
        } else {
            $this->display(); // 输出模板
        }
    }
    // 微信关注后跳转到门店微官网
    public function location_wxweb($defined_one_name) {
        // 获取门店的门店号
        // 接收微官网关注的跳转的fav_store_id的值
        if (empty($defined_one_name)) {
            $this->error("门店标号为空！");
        }
        $m_id = M("tmarketing_info")->where(
            array(
                'defined_one_name' => $defined_one_name))->getField('id');
        $model = M('tbatch_channel');
        $map = array(
            'batch_id' => $m_id);
        $tbatch_id = $model->where($map)->getField('id');
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr['1003'];
        // 标签
        // 所有字段
        $_GET['wechat_card_js'] = 1;
        $tz_arr = array(
            'id' => $tbatch_id);
        if (! $url)
            $this->error('error url ！');
        $this->redirect(U($url, $tz_arr));
    }

    public function view() {
        $id = I("get.cfg_id");
        if (! $id) {
            $this->error('未找到记录');
        }
        $info = M('tmicroweb_tpl_cfg')->where(
            array(
                'id' => $id))->find();
        $this->assign('info', $info);
        
        $this->display(); // 输出模板
    }
    
    // tpl_cfg结果集转成json报文
    private function get_cfg_array($data) {
        $result = array();
        foreach ($data as $v) {
            if ($v['field_type'] != '3') {
                if ($v['tpl_id'] == '1' || $v['tpl_id'] == '5' ||
                     $v['tpl_id'] == '6' || $v['tpl_id'] == '7' ||
                     $v['tpl_id'] == '13' || $v['tpl_id'] == '14' ||
                     $v['tpl_id'] == '15' || $v['tpl_id'] == '16') {
                    if ($v['image_name']) {
                        $image_url = $this->_getUploadUrl($v['image_name']);
                    } else {
                        $image_url = '';
                    }
                    $v['field_img_color'] = '';
                } elseif ($v['tpl_id'] == '2' || $v['tpl_id'] == '8') {
                    if ($v['field_type'] == '1' || $v['field_type'] == '0') {
                        $image_url = $this->_getUploadUrl($v['image_name']);
                    } else {
                        $image_url = '';
                    }
                } elseif ($v['tpl_id'] == '3' || $v['tpl_id'] == '4' ||
                     $v['tpl_id'] == '9' || $v['tpl_id'] == '10' ||
                     $v['tpl_id'] == '11' || $v['tpl_id'] == '12') {
                    if ($v['field_type'] == '1' || $v['field_type'] == '0') {
                        $image_url = $this->_getUploadUrl($v['image_name']);
                    } elseif ($v['field_type'] == '4') {
                        //因此段代码影响全景图显示，故注释
                        /*if ($v['link_type'] == '4') {
                            $image_url = $this->_getUploadUrl($v['image_name']);
                        } else {
                            $image_url = C('TMPL_PARSE_STRING.__PUBLIC__') .
                                    '/Image/wapimg/' . $v['image_name'];
                        }*/
                        $image_url = $this->_getUploadUrl($v['image_name']);
                    } else {
                        if ($v['field_img_name']) {
                            $image_url = C('TMPL_PARSE_STRING.__PUBLIC__') .
                                 '/Label/Image/iconVal/' . $v['field_img_name'];
                        } else {
                            $image_url = '';
                        }
                    }
                } else {
                    $image_url = '';
                }
                
                if ($v['link_type'] == 2) {
                    $v['link_url'] = U('Label/MicroWeb/view', 
                        array(
                            'id' => $this->id, 
                            'cfg_id' => $v['id']));
                }
                $result[$v['field_type']][] = array(
                    'id' => $v['id'], 
                    'title' => $v['title'], 
                    'field_id' => $v['field_id'], 
                    'image_name' => $v['image_name'], 
                    'image_url' => $image_url, 
                    'link_url' => $v['link_url'], 
                    'sumary' => $v['sumary'], 
                    'mw_batch_id' => $v['mw_batch_id'], 
                    'tpl_id' => $v['tpl_id'], 
                    'field_img_color' => $v['field_img_color']);
            } else {
                if ($v['field_type'] == '3') {
                    $v['content'] = json_decode($v['content'], true);
                }
                $result['phone'] = $v['content']['sns']['phone'];
                $result['sns_arr']['sns_1'] = $v['content']['sns']['sns_1'];
                $result['sns_arr']['sns_2'] = $v['content']['sns']['sns_2'];
                $result['sns_arr']['sns_3'] = $v['content']['sns']['sns_3'];
                $result['sns_arr']['sns_4'] = $v['content']['sns']['sns_4'];
            }
        }
        return $result;
    }

    private function getaipaichannel() {
        $channel_model = M('tchannel');
        $onemap = array(
            'node_id' => $this->node_id, 
            'type' => '3', 
            'sns_type' => '36');
        $label_id = $channel_model->where($onemap)
            ->limit('1')
            ->getField('label_id');
        if (! $label_id) {
            $this->error('获取爱拍默认微官网渠道号失败');
        } else {
            return $label_id;
        }
    }

    /*
     * 根据机构信息表获取当前机构的等级 C0(基础模版，保留建站和爱拍) C1(所有模版，有悬浮窗分享，去除建站，保留爱拍)
     * C2(所有模版，有悬浮窗分享，去除建站和爱拍) CY(翼码和演示机构,具有所有权限，且前台页面保留建站和爱拍)
     */
    private function getNodeLevel() {
        $nodeinfoModel = M('tnode_info');
        $where = array(
            'node_id' => $this->node_id);
        $node_level = 'C0';
        
        $node_info = $nodeinfoModel->where($where)->find();
        if (! $node_info) {
            $this->error('机构信息错误');
        } else {
            if ($node_info['node_type'] == '0' || $node_info['node_type'] == '1')
                $node_level = 'C2';
            elseif ($node_info['node_type'] == '3' ||
                 $node_info['node_type'] == '4')
                $node_level = 'CY';
            elseif ($node_info['node_type'] == '2' &&
                 $node_info['check_status'] == '2')
                $node_level = 'C1';
            else
                $node_level = 'C0';
        }
        return $node_level;
    }
    
    // 获取图片路径
    protected function _getUploadUrl($imgname) {
        $img_upload_path = $this->upload_path;
        // 旧版
        if (basename($imgname) == $imgname) {
            $return =$img_upload_path . $imgname;
        } else {
            $return =get_upload_url($imgname);
        }
        return $return;
    }
}
