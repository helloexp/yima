<?php
// 支付宝申请 - wap端
class PaywapAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => C('CP_URL') . "/xxzf.html", 
            'title' => "招募“民星代言人”啦！门店必备“O2O收款+营销”双双送给你！", 
            'desc' => "我刚刚参加翼码旺财民星代言人，“条码支付”+“付满送”已收入麾下啦，分享给你，快来参加吧···", 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/fms/fms-banner.jpg');
        
        $this->assign('shareData', $shareArr);
    }
    
    
    // 保存注册
    public function submit2() {
        $row = M('tzfb_offline_pay_info')->where(
            array(
                'node_id' => $this->node_id, 
                'pay_type' => '0'))->getfield('status');
        if ($row && ($row['status'] == '1' || $row['status'] == '2')) {
            $this->error('操作非法tdraft');
        }
        if ($row['status'] == '3' && $row['check_status'] == '0') {
            $this->error('操作非法的tdraft');
        }
        if ($row['status'] == '3' && $row['check_status'] == '1') {
            $this->error('操作非法的tdraft');
        }
        
        $reqData = I();
        $data = array(
            'industry' => $reqData['trade1'], 
            'province' => $reqData['province'], 
            'city' => $reqData['city'], 
            'town' => $reqData['town'], 
            'address' => $reqData['address'], 
            'images_1' => $reqData['images_1'], 
            'images_2' => $reqData['images_2'], 
            'images_3' => $reqData['images_3'], 
            'zfb_account' => $reqData['account'], 
            'tg_client_str' => $reqData['tg_client_str']);
        
        $data = array(
            'node_name' => $this->nodeInfo['node_name'], 
            'industry' => $reqData['trade1'], 
            'province' => $reqData['province'], 
            'city' => $reqData['city'], 
            'town' => $reqData['town'], 
            'address' => $reqData['Detposition'], 
            'store_pic1' => $reqData['resp_img1'], 
            'store_pic2' => $reqData['resp_img2'], 
            'store_pic3' => $reqData['resp_img3'], 
            'contact_name' => $this->nodeInfo['contact_name'], 
            'contact_phone' => $this->nodeInfo['contact_phone'], 
            'zfb_account' => $reqData['account'], 
            'tg_client_str' => $reqData['tg_client_str']);
        $map = array(
            'node_id' => $this->node_id, 
            'type' => '2');
        $tdraftInfo = M('tdraft')->where($map)->find();
        if ($tdraftInfo) {
            $tdraftInfo['content'] = json_encode($data);
            $flag = M('tdraft')->where($map)->save($tdraftInfo);
            if ($flag === false) {
                exit(
                    json_encode(
                        array(
                            'info' => '保存失败', 
                            'status' => 2)));
            }
        } else {
            $tdraftInfo = array(
                'node_id' => $this->node_id, 
                'content' => json_encode($data), 
                'add_time' => date('YmdHis'), 
                'type' => '2');
            $flag = M('tdraft')->add($tdraftInfo);
            if ($flag === false) {
                exit(
                    json_encode(
                        array(
                            'info' => '保存失败', 
                            'status' => 2)));
            }
        }
        
        exit(
            json_encode(
                array(
                    'info' => '保存成功', 
                    'status' => 1)));
    }

    public function accountMsg() {
        $this->error('此功能已关闭');exit;
        $applyInfo = M('tzfb_offline_pay_info')->where(
            array(
                'node_id' => $this->node_id, 
                'pay_type' => '0'))->find();
        $draftInfo = M('tdraft')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '2'))->find();
        if ($draftInfo)
            $draftInfo = json_decode($draftInfo['content'], true);
        
        $data = array();
        if ($applyInfo) {
            if ($applyInfo['check_status'] == '2') {
                $this->assign('errmsg', 
                    '您的上次申请被拒绝,请重新提交！<br>原因：' . $applyInfo['check_memo']);
                $data = $draftInfo ? $draftInfo : $applyInfo;
            } else {
                $this->redirect(U('success_page'));
                exit();
            }
        } else {
            $data = $draftInfo ? $draftInfo : $applyInfo;
            if (! $draftInfo) {
                // $this->assign('node_citycode',
                // $this->nodeInfo['node_citycode']);
                $citycode = $this->nodeInfo['node_citycode'];
                $data['province'] = substr($citycode, 0, 2);
                $data['city'] = substr($citycode, 2, 3);
                $data['town'] = substr($citycode, 5, 3);
            }
        }
        $this->assign('applyInfo', $data);
        
        $tindustry_info = M('tindustry_info')->field('industry_name')->select();
        
        $this->assign('tindustry_info', $tindustry_info);
        
        $this->display();
        exit();
        // $s = I("");
        
        $type = I('type');
        if ($type == 'login') {
            $res = M('tzfb_offline_pay_info')->where(
                array(
                    'node_id' => $this->node_id))
                ->order('add_time desc')
                ->select();
            if (! empty($res)) {
                if ($res[0]['status'] == '1') {
                    $this->redirect("index.php?g=Alipay&m=Paywap&a=success");
                }
                if ($res[0]['status'] == '0' || $res[0]['status'] == '2') {
                    $this->assign('errmsg', '您还未开通或者已停用,请申请！');
                }
                if ($res[0]['status'] == '3' && $res[0]['check_status'] == '0') {
                    $this->redirect("index.php?g=Alipay&m=Paywap&a=success");
                }
                if ($res[0]['status'] == '2' && $res[0]['check_status'] == '1') {
                    $this->redirect("index.php?g=Alipay&m=Paywap&a=success");
                }
                if ($res[0]['status'] == '3' && $res[0]['check_status'] == '2') {
                    $this->assign('errmsg', '您的审核被拒绝,请再次申请！');
                }
            }
            
            $tdraft_info = M('tdraft')->where(
                array(
                    'node_id' => $this->node_id))->find();
            if ($tdraft_info != false && $tdraft_info != null) {
                $tdraftData = json_decode($tdraft_info['content'], true);
                $this->assign('tdraftData', $tdraftData);
            }
        }
        
        $this->display();
    }
    
    // 注册
    public function submit1() {
        $row = M('tzfb_offline_pay_info')->where(
            array(
                'node_id' => $this->node_id, 
                'pay_type' => '0'))->getfield('status');
        if ($row && ($row['status'] == '1' || $row['status'] == '2')) {
            $this->error('操作非法tdraft');
        }
        $data = I();
        
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        $map = array(
            'node_id' => $this->node_id, 
            'node_name' => $node_info['node_name'], 
            'node_mail' => $node_info['contact_email'], 
            'contact_name' => $node_info['contact_name'], 
            'contact_phone' => $node_info['contact_phone'], 
            'province' => $data['province'], 
            'city' => $data['city'], 
            'town' => $data['town'], 
            'address' => $data['Detposition'], 
            'zfb_account' => $data['account'], 
            'pay_type' => '0', 
            'tg_client_str' => $data['tg_client_str'], 
            'add_time' => date('YmdHis'), 
            'check_status' => '0', 
            'status' => '3', 
            'store_pic1' => $data['resp_img1'], 
            'store_pic2' => $data['resp_img2'], 
            'store_pic3' => $data['resp_img3'], 
            'industry' => $data['trade1']);
        $info = M('tzfb_offline_pay_info')->where(
            array(
                'node_id' => $this->node_id, 
                'pay_type' => '0'))->find();
        if ($info) {
            // 判断是否是拒绝状态
            if ($info['check_status'] == '2') {
                $flag = M('tzfb_offline_pay_info')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'pay_type' => '0'))->save($map); // 要改status
                if ($flag === false) {
                    exit(
                        json_encode(
                            array(
                                'info' => '提交失败', 
                                'status' => 2)));
                }
            }
        } else {
            $flag = M('tzfb_offline_pay_info')->add($map);
            if ($flag === false) {
                exit(
                    json_encode(
                        array(
                            'info' => '提交失败', 
                            'status' => 2)));
            }
            
            M('tdraft')->where(
                array(
                    'node_id' => $this->node_id))->delete();
            $this->_apply_sendmail($node_info);
            
            $map = array(
                'log_day' => date('Ymd'), 
                'apply_type' => 1);
            $tmzfModel = M('ttmzf_apply_stat');
            $info = $tmzfModel->where($map)->find();
            if ($info) {
                $tmzfModel->where($map)->setInc('apply_num');
            } else {
                $data = $map;
                $data['apply_num'] = 1;
                $tmzfModel->add($data);
            }
        }
        
        exit(
            json_encode(
                array(
                    'info' => '提交成功', 
                    'status' => 1)));
    }

    public function uploadfile() {
        if (! empty($_FILES)) {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 1024 * 1024 * 10;
            $upload->savePath = APP_PATH . 'Upload/' . $this->node_id . '/' .
                 date('Y') . "/" . date("m") . "/" . date("d") . "/"; // 设置附件
            $upload->saveRule = time() . sprintf('%06s', mt_rand(0, 100000));
            $upload->supportMulti = false;
            $upload->allowExts = array(
                'gif', 
                'jpg', 
                'jpeg', 
                'bmp', 
                'png');
            $upload->thumb = true;
            // 设置引用图片类库包路径
            // 设置需要生成缩略图的文件后缀
            $upload->thumbPrefix = 'm_'; // 生产2张缩略图
                                         // 设置缩略图最大宽度
            $upload->thumbMaxWidth = '100,100';
            // 设置缩略图最大高度
            $upload->thumbMaxHeight = '100,100';
            // 设置上传文件规则
            $upload->thumbRemoveOrigin = true; // 删除原图
            ini_set('memory_limit', '256M');
            
            // echo $upload->savePath;
            // exit;
            if (! is_dir($upload->savePath)) {
                mkdir($upload->savePath, 0777, true);
            }
            
            if (! $upload->upload()) {
                // 上传错误提示错误信息
                echo "图片上传失败！";
                exit();
            } else {
                // 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                $up_img = "m_" . $info[0]['savename'];
            }
        }
        $arr = array(
            'input_name' => $info[0]['key'], 
            'name' => $info[0]['name'], 
            'savepath' => $this->node_id . '/' . date('Y') . "/" . date("m") .
                 "/" . date("d") . "/" . $up_img, 
                'pic_url' => $upload->savePath . $up_img, 
                'size' => round($info[0]['size'] / 1024, 2));
        echo json_encode($arr);
    }

    public function success_page() {
        /*
         * $row=M('tzfb_offline_pay_info')->where(array('node_id'=>$this->node_id,'pay_type'=>'0'))->find();
         * if(!$row || ($row['status'] != '1' || $row['status'] != '2' )){
         * $this->redirect(U('accountMsg')); exit; }
         */
        $client_id = str_pad($this->clientId, 6, '0', STR_PAD_LEFT);
        $this->assign('client_id', $client_id);
        $this->display('success');
    }

    public function login() {
        $this->display();
    }

    /**
     * 条码支付提交申请，发送邮件通知 每个申请成功的数据发送至以下邮箱地址，内容如下： 申请时间、旺号、商户名称、联系方式、商户来源（pc/wap）
     */
    function _apply_sendmail($info) {
        $content = '<BR/>申请商户: ' . $info['node_name'];
        $content .= '<BR/>旺号: ' . $info['client_id'];
        $content .= '<BR/>联系人: ' . $info['contact_name'];
        $content .= '<BR/>联系电话: ' . $info['contact_phone'];
        $content .= '<BR/>商户来源: wap';
        $arr = array(
            'subject' => $info['node_name'] . '提交条码支付申请', 
            'content' => $content, 
            'email' => C('tmzf_apply_mailto'));
        send_mail($arr);
    }
}