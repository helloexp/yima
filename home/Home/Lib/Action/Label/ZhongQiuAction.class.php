<?php

class ZhongQiuAction extends MyBaseAction {

    public function _initialize() {
        // edit by tr
        $this->error("该活动已下线");
        exit();
        parent::_initialize();
    }

    public function callback() {
        $type = I('type');
        $phone = I('phone');
        $conf = $this->snsconfig($type, $phone);
        $code = I('code');
        
        // 分享二维码图片地址
        $img_url = U('LabelAdmin/ShowCode/index', 'id=' . $this->id, '', '', 
            true);
        
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type, $conf);
        
        // 腾讯微博还需其他参数
        $extend = null;
        if ($type == 'tencent') {
            $extend = array(
                'openid' => I('openid'), 
                'openkey' => I('openkey'));
        }
        
        // 获取token
        $token = $sns->getAccessToken($code, $extend, $conf);
        
        // 发布内容
        $sns = ThinkOauth::getInstance($type, $token, $conf);
        if ($type == 'sina') {
            $data = array(
                'status' => '中秋节快乐！我刚刚参加了一个好玩的活动，你也来看看吧！', 
                'url' => $img_url);
            $data = $sns->call('statuses/upload_url_text', $data, 'POST');
            if ($data['error_code'] == 0) {
                $this->snsLog($type, $phone);
                redirect(
                    U('Label/ZhongQiu/index', 
                        array(
                            'id' => $this->id, 
                            'phone' => $phone, 
                            'type' => $type)));
            } else {
                $this->error("分享失败:{$data['error']}");
            }
        } elseif ($type == 'tencent') {
            $data = array(
                'content' => '中秋节快乐！我刚刚参加了一个好玩的活动，你也来看看吧！', 
                'pic_url' => $img_url);
            $data = $sns->call('t/add_pic_url', $data, 'POST');
            if ($data['ret'] == 0) {
                $this->snsLog($type, $phone);
                redirect(
                    U('Label/ZhongQiu/index', 
                        array(
                            'id' => $this->id, 
                            'phone' => $phone, 
                            'type' => $type)));
            } else {
                $this->error("分享失败:{$data['msg']}");
            }
        }
    }
    
    // 开始授权
    public function authorize() {
        $type = I('type');
        $phone = I('phone');
        if ($type != 'tencent' && $type != 'sina')
            $this->error('错误的授权参数！' . $type);
        if (empty($phone))
            $this->error('手机号错误！');
            
            // 是否已经发布过
        $query = M('tsns_log')->where(
            array(
                'type' => $type, 
                'phone' => $phone, 
                'batch_id' => $this->batch_id))->find();
        if ($query)
            $this->error('已经分享过！');
        
        $conf = $this->snsconfig($type, $phone);
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type, $conf);
        
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL($conf));
    }
    
    // 配置参数
    public function snsconfig($type, $phone) {
        $backurl = 'http://www.wangcaio2o.com/index.php?g=Label&m=ZhongQiu&a=callback&id=' .
             $this->id . '&phone=' . $phone;
        
        $config = C("THINK_SDK_{$type}");
        $conf = array(
            'app_key' => $config['APP_KEY'],  // 应用注册成功后分配的 APP ID
            'app_secret' => $config['APP_SECRET'],  // 应用注册成功后分配的KEY
            'callback' => $backurl . '&type=' . $type);
        return $conf;
    }
    
    // 记录分享日志
    public function snsLog($type, $phone) {
        $query = M('tsns_log')->where(
            array(
                'type' => $type, 
                'phone' => $phone, 
                'batch_id' => $this->batch_id))->find();
        if (! $query) {
            $data = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'type' => $type, 
                'phone' => $phone);
            $query = M('tsns_log')->add($data);
        }
    }
    
    // 是否分享过
    public function issns($type, $phone) {
        $phone = I('phone');
        $type = I('type');
        $query = M('tsns_log')->where(
            array(
                'type' => $type, 
                'phone' => $phone, 
                'batch_id' => $this->batch_id))->find();
        if ($query)
            $this->ajaxReturn('success', "已分享过", 1);
        else
            $this->ajaxReturn('error', "未分享", 0);
    }
    
    // 获取中奖信息
    public function getPrize() {
        $mobile = I('mobile', NULL);
        if (! $mobile)
            $this->ajaxReturn('error', "手机好错误", 0);
        $rule_arr = M('tcj_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'mobile' => $mobile, 
                'status' => '2'))
            ->order('id desc')
            ->find();
        
        if ($rule_arr) {
            $cj_arr = M('tcj_batch')->where(
                array(
                    'batch_id' => $this->batch_id, 
                    'id' => $rule_arr['rule_id']))->find();
            if ($cj_arr) {
                $prize_arr = M('tbatch_info')->field('batch_name,batch_img')
                    ->where(
                    array(
                        'id' => $cj_arr['b_id']))
                    ->find();
                if ($prize_arr) {
                    $this->ajaxReturn($prize_arr, "奖品详情", 1);
                }
            }
        }
        $this->ajaxReturn('error', "未查询到中奖信息", 0);
    }
    
    // 是否参与过
    public function iscj() {
        $mobile = I('mobile');
        $iscj = M('tcj_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'mobile' => $mobile))->find();
        if ($iscj)
            $this->ajaxReturn('success', "参与过", 1);
        else
            $this->ajaxReturn('error', "未参与过", 0);
    }
    // 校验有效期
    public function checkday() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        else
            $this->ajaxReturn('success', '成功', 1);
    }
    
    // 首页
    public function index() {
        // redirect(U('Label/ZhongQiu/authorize',array('id'=>$this->id,'phone'=>'13764737738','type'=>'sina'),'','',TRUE));
        $type = I('type', NULL);
        $phone = I('phone', NULL);
        
        if ($this->batch_type != '30') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 访问量
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 奖品信息
        $jp_sql = "SELECT b.name ,c.batch_name,c.batch_img FROM tcj_batch a 
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id 
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('phone', $phone);
        $this->assign('type', $type);
        $this->assign('row', $row);
        $this->assign('jp_arr', $jp_arr);
        $this->display(); // 输出模板
    }
}