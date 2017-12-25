<?php

/**
 * 支付宝
 */
class IndexAction extends BaseAction
{

    private $onePayDir;//翼支付业务协议上传路径

    public $img_path;

    private $fmscharge_id = 3091;
    // 付满送模块id
    private $isOpeningStr;
    // 已开通的支付方式
    private $noOpenStr;
    // 未开通的支付方式
    private $duoMiUserInfo;
    // 用户信息
    private $openType;

    /**
     * @var QqzfService
     */
    private $QqzfService;
    // 已开通的类型
    // public $_authAccessMap='*';
    private $noauth = array(
            'store_group_name'
    );
    //团购外卖申请开通
    private static $applyEmail = 'yangyang@imageco.com.cn,qiuzd@imageco.com.cn';

    /**
     * @var GroupShopModel;
     */
    public $GroupShopModel;

    private $groupToArr = array('1' => '百度糯米', '2' => '美团/大众点评', '3' => '淘宝', '4' => '掌上生活');
    private $shopStatusArr = array('0' => '正常');
    private $storageDir;
    private $storeInfo;//储存门店信息
    const PAGE_COUNT = 10;


    // 不需要权限记得节点
    public function _initialize()
    {
        parent::_initialize();


        $this->GroupShopModel = D('GroupShop');
        $this->storeInfo      = $this->GroupShopModel->getStoreInfo('', '', $this->node_id);
        if (defined('RUNTIME_PATH')) {
            $this->storageDir = realpath(RUNTIME_PATH . 'Temp') . '/' . $this->node_id;
        } else {
            $this->storageDir = realpath(APP_PATH . 'Runtime/Temp') . '/' . $this->node_id;
        }

        $rs             = M('tuser_info')->where(
                array(
                        'user_id' => $this->userInfo['user_id']
                )
        )->find();
        $this->userInfo = array_merge($this->userInfo, $rs);
        $fms_chagre     = M('tnode_charge')->where(
                array(
                        'status'       => 0,
                        'node_id'      => $this->node_id,
                        'charge_id'    => $this->fmscharge_id,
                        'charge_level' => 1
                )
        )->find();
        // 根据是否设置模板消息不同url链接
        $yizfInfo = D('TweixinInfo');
        $flag     = $yizfInfo->templateFlag($this->nodeId, 2);
        $url      = $flag ? U('Alipay/Index/AlipayAdvancedMb_index') : U('Alipay/Index/AlipayAdvancedMb');
        $this->assign('templateUrl', $url);

        $this->assign('fms_chagre', $fms_chagre);
        $this->img_path = C('BATCH_IMG_PATH_NAME.999') . '/' . $this->node_id . '/';
        $this->assign('node_type', $this->node_type_name);

        $this->duoMiSendMail();
        $this->assign('isOpeningStr', $this->isOpeningStr);
        $this->assign('noOpenStr', $this->noOpenStr);
        $this->assign('duoMiUserInfo', $this->duoMiUserInfo);
        // 查询是否是服务商
        $c = M('tzfb_offline_shop_relation')->where(
                array(
                        'node_id' => $this->node_id
                )
        )->selectSlave()->count();
        $this->assign('isBserver', $c);


        // 翼支付业务协议上传路径
        if (!is_dir(APP_PATH . 'Upload/onePayDir/' . $this->nodeId)) {
            mkdir(APP_PATH . 'Upload/onePayDir/' . $this->nodeId, 0777, true);
        }

        $this->onePayDir = $this->nodeId . date('/Y/m/d/');

    }

    /**
     *
     * @return JobApplyModel
     */
    private function getJobApplyModel()
    {
        if (empty($this->jobApplyModel)) {
            $this->jobApplyModel = D('JobApply');
        }
        return $this->jobApplyModel;
    }

    /**
     * QQ钱包支付主页
     */
    public function info_qqzf()
    {
        $this->assign('node_info', $this->nodeInfo);
        $this->assign('node_id', $this->node_id);

        //==========================QQ数据Begin==================
        $list = $this->getPayInfoListWithPagerAndMap(5, 55);
        //====================QQ数据End=========================

        /**
         * 查看是否已经绑定QQ账号
         */
        $where  = ['node_id' => $this->node_id, 'pay_type' => 5,];
        $qqFlag = M('tzfb_offline_pay_info')->where($where)->find();
        $this->assign('qqflag', $qqFlag);
        $this->assign('list', $list);
        $this->display('info_qqzf');
    }

    public function info_qqzf_details()
    {
        $id   = I('id');
        $list = M('tzfb_offline_pay_info')->where(array('id' => $id))->find();
        $city = M('tcity_code')->field('province,city')->where(
                array('province_code' => $list['province'], 'city_code' => $list['city'])
        )->find();

        $arr                           = json_decode($list['apply_data'], true);
        $arr['node_name']              = $list['node_name'];
        $arr['bank_city']              = $city['province'] . '-' . $city['city'];
        $arr['shop_type']              = $list['shop_type'];
        $arr['bank_address']           = $list['address'];
        $arr['contact_position']       = $list['contact_position'];
        $arr['contact_name']           = $list['contact_name'];
        $arr['contact_phone']          = $list['contact_phone'];
        $arr['node_mail']              = $list['node_mail'];
        $arr['business_date']['0']     = dateformat($arr['business_date']['0'], 'Y-m-d');
        $arr['business_date']['1']     = dateformat($arr['business_date']['1'], 'Y-m-d');
        $arr['organization_date']['0'] = dateformat($arr['organization_date']['0'], 'Y-m-d');
        $arr['organization_date']['1'] = dateformat($arr['organization_date']['0'], 'Y-m-d');

        $arr['contact_idnum_date']['0'] = dateformat($arr['contact_idnum_date']['0'], 'Y-m-d');
        $arr['contact_idnum_date']['1'] = dateformat($arr['contact_idnum_date']['1'], 'Y-m-d');
        $this->assign('qqInfo', $arr);
        $this->display();
    }

    /**
     * QQ钱包支付申请新增
     */
    public function addQqSubmit()
    {
        $this->QqzfService = D('Qqzf', 'Service');

        $addqq = $this->QqzfService->addQqSubmit($this->node_id);
        switch ($addqq['code']) {
            case '0':
                $this->error('操作非法！！！');
                break;
            case '1':
                $this->error($addqq['message']);
                break;
            case '2':
                $this->success(1);
                break;
            case '3':
                $this->error('提交失败！');
                break;
        }
    }

    /**
     * QQ钱包支付申请编辑
     */
    public function editQqSubmit()
    {
        $this->QqzfService = D('Qqzf', 'Service');
        $id                = I('post.id');
        $editqq            = $this->QqzfService->editQqSubmit($this->node_id, $id);
        switch ($editqq['code']) {
            case '0':
                $this->error('申请已通过，无法编辑申请信息');
                break;
            case '1':
                $this->error($editqq['message']);
                break;
            case '2':
                $this->success(1);
                break;
            case '3':
                $this->error('提交失败！');
                break;
        }
    }

    /**
     * QQ钱包支付epos申请
     */
    function qqzfepos()
    {
        $this->QqzfService = D('Qqzf', 'Service');
        $rs                = $this->QqzfService->qqzfepos($this->node_id);
        if ($rs) {
            $this->senemail();
            $this->success(1);
        } else {
            $this->error('申请失败，请重新提交');
        }
    }

    public function error($message = '', $jumpUrl = '', $ajax = false)
    {
        parent::error($message, $jumpUrl, $ajax);
    }

    public function success($message = '', $jumpUrl = '', $ajax = false)
    {
        parent::success($message, $jumpUrl, $ajax);
    }

    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        parent::display($templateFile);
    }

    public function assign($k, $v = '')
    {
        parent::assign($k, $v);
    }

    /**
     * 获取当前商户 以邮件形式开通支付方式的信息
     */
    public function duoMiSendMail()
    {
        // 多米收单所有页面的 头部信息的商户信息
        $nodeInfo = M('tnode_info')->field('contact_name, contact_phone, contact_tel')->where(
                array(
                        'node_id' => $this->node_id
                )
        )->find();
        $userInfo = array(
                'userName' => $nodeInfo['contact_name'],
                'userTel'  => $nodeInfo['contact_phone']
        );
        if (empty($userInfo['userTel'])) {
            $userInfo['userTel'] = $nodeInfo['contact_tel'];
        }

        $this->duoMiUserInfo = $userInfo;

        // 除付满送的其他4种类型，用来控制页面弹窗的
        $typeArr = array(
                'a' => '.tghx',
                'b' => '.shfw',
                'c' => '.yykq',
                'd' => '.yhkzf'
        );

        $jobApplyModel      = $this->getJobApplyModel();
        $beenOpenType       = $jobApplyModel->getApplyInfo($this->node_id);
        $noOpenType         = array_diff_key($typeArr, $beenOpenType);
        $noOpenStr          = implode(',', $noOpenType);
        $isOpeningStr       = implode(',', array_diff_key($typeArr, $noOpenType));
        $this->isOpeningStr = $isOpeningStr;
        $this->noOpenStr    = $noOpenStr;
        $this->openType     = $beenOpenType;
    }

    public function otherTypes()
    {
        $applyInfo = I('post.', array());
        $nodeId    = $this->node_id;
        $userSess  = session('userSessInfo');

        $typeArr = array(
                'a' => '卡券核销',
                'b' => '订单核销',
                'c' => '',
                'd' => '银行卡支付'
        );

        if (empty($typeArr[$applyInfo['openType']])) {
            $this->ajaxReturn(['status' => 0, 'info' => '类型错误'], 'JSON');
        }
        if (empty($applyInfo['userName'])) {
            $this->ajaxReturn(['status' => 0, 'info' => '请输入联系人姓名'], 'JSON');
        }
        if (!is_numeric($applyInfo['userTel'])) {
            $isEmail = preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $applyInfo['userTel']);
            if ($isEmail != 1) {
                $this->ajaxReturn(['status' => 0, 'info' => '联系方式错误'], 'JSON');
            }
        }

        $content = array(
                'userName' => $applyInfo['userName'],
                'userTel'  => $applyInfo['userTel']
        );
        $time    = date('YmdHis');
        $addData = [
                'nodeId'   => $nodeId,
                'openType' => $applyInfo['openType'],
                'user_id'  => $userSess['user_id'],
                'content'  => json_encode($content),
                'time'     => $time
        ];

        $isOpenType = $this->openType;
        if ($isOpenType[$applyInfo['openType']]) {
            $this->ajaxReturn(['status' => 0, 'info' => '您已申请过“' . $typeArr[$applyInfo['openType']] . '”'], 'JSON');
        }
        $jobApplyModel = $this->getJobApplyModel();
        // 入库
        $response = $jobApplyModel->addUserApply($addData);
        if ($response === false) {
            $this->ajaxReturn(['status' => 0, 'info' => '申请失败'], 'JSON');
        }

        // 获取商户名称
        $nodeName = M('tnode_info')->field('node_name')->where("node_id='" . $nodeId . "'")->find();
        // 邮箱发送
        $contents = "商户【" . $nodeName['node_name'] . "】<br/>现申请开通多米收单，支撑“" . $typeArr[$applyInfo['openType']] . "”类型的业务；请尽快联系客户，完成开通。
        联系电话：" . $applyInfo['userTel'] . "<br/>日期：" . date('Y-m-d H:i:s');
        $ps       = array(
                "subject" => "“多米收单开通申请”",
                "content" => $contents,
                "email"   => "yangyang@imageco.com.cn"
        ) // 原邮箱wuqx@imageco.com.cn
        ;
        $resp     = send_mail($ps);
        if ($resp['sucess']) {
            $this->ajaxReturn(['status' => 1, 'info' => '已申请'], 'JSON');
        }
    }

    public function index()
    {
        $this->assign('hasAlipay', $this->nodeInfo['sale_flag']);
        // 支付信息表
        $payInfo = M('tzfb_offline_pay_info')->field('pay_type,check_status,status,open_time,tg_client_id')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => array(
                                'in',
                                '0,1,2,5'
                        )
                )
        )->order('id desc')->group('pay_type')->select();

        // 设置默认权限 0未开通 1开通
        $flag_arr = array(
                'zfb_flag'   => '0', // 是否开通 0未开通 1已开通 2停用
                'zfb_check'  => '9', // 是否为审核拒绝 0未审核 1审核通过 2审核拒绝 9 未申请
                'wx_flag'    => '0',
                'wx_check'   => '9',
                'yizf_flag'  => '0',
                'yizf_check' => '9', // 翼支付
                'first_flag' => '1', // 0 首次开通 1 单开通zfb 2 单开通wx 3 zfb,wx都开通
                'qqzf_flag'  => '0', // 翼支付
                'qqzf_check' => '9'
        );
        foreach ($payInfo as $v) {
            // 支付宝
            if ($v['pay_type'] == '0') {
                $flag_arr['zfb_flag']  = $v['status'];
                $flag_arr['zfb_check'] = $v['check_status'];
                $flag_arr['open_time'] = $v['open_time'];
            }
            // 微信
            if ($v['pay_type'] == '1') {
                $flag_arr['wx_flag']   = $v['status'];
                $flag_arr['wx_check']  = $v['check_status'];
                $flag_arr['open_time'] = $v['open_time'];
            }
            if ($v['pay_type'] == '2') {
                $flag_arr['yizf_flag']  = $v['status'];
                $flag_arr['yizf_check'] = $v['check_status'];
                $flag_arr['open_time']  = $v['open_time'];
            }
            if ($v['pay_type'] == '5') {
                $flag_arr['qqzf_flag']  = $v['status'];
                $flag_arr['qqzf_check'] = $v['check_status'];
                $flag_arr['open_time']  = $v['open_time'];
            }
        }
        // 未开通 | 开通申请中 is true
        //        if (!in_array(
        //                        $flag_arr['qqzf_flag'],
        //                        array(
        //                                1,
        //                                2
        //                        )
        //                ) && !in_array(
        //                        $flag_arr['yizf_flag'],
        //                        array(
        //                                1,
        //                                2
        //                        )
        //                ) && !in_array(
        //                        $flag_arr['zfb_flag'],
        //                        array(
        //                                1,
        //                                2
        //                        )
        //                ) && !in_array(
        //                        $flag_arr['wx_flag'],
        //                        array(
        //                                1,
        //                                2
        //                        )
        //                )
        //        ) {
        //            $flag_arr['first_flag'] = '0';
        //        }
        //
        //多米收单台权限-是否交服务费
        $saleFlag = M('tnode_info')->where('node_id=' . $this->node_id)->getfield('sale_flag');
        //多米收单台权限-是否为老用户
        $userAuthority = $this->isDuomiOldUser($payInfo);

        if (!($saleFlag || $userAuthority) && I('get.type') == 'alipay') {
            $this->redirect('Alipay/Index/info_alipay');
        }


        $this->assign('flag_arr', $flag_arr);
        $this->assign('type_name', $this->node_type_name);
        $this->assign('zfb_account', get_val($res, 'zfb_account'));
        $this->assign('contact_name', get_val($res, 'contact_name'));
        $this->assign('contact_phone', get_val($res, 'contact_phone'));

        if (empty($payInfo)) {
            $flag_arr['first_flag'] = '0';
        }

        if ($flag_arr['first_flag'] != '0') { // 改成 !=
            // 查询终端数
            $posCount = M('tpos_info')->where(
                    array(
                            'node_id'      => $this->node_id,
                            'is_activated' => 1,
                            'pos_type'     => array(
                                    'not  in',
                                    '0'
                            )
                    )
            )->count();
            $this->assign('posCount', $posCount);
            $stat_arr       = M('tzfb_day_stat')->where(
                    array(
                            'node_id' => $this->node_id
                    )
            )->Field(
                    'sum(verify_cnt) count,sum(verify_amt) count_,sum(cancel_cnt) cancel_cnt,sum(cancel_amt) cancel_amt'
            )->find();
            $count          = $stat_arr['count'] ? $stat_arr['count'] : '0';
            $count_         = $stat_arr['count_'] ? $stat_arr['count_'] : '0';
            $cancel_cnt     = $stat_arr['cancel_cnt'] ? $stat_arr['cancel_cnt'] : '0';
            $cancel_amt     = $stat_arr['cancel_amt'] ? $stat_arr['cancel_amt'] : '0';
            $begin          = date('Ymd', strtotime('-7 days'));
            $end            = date('Ymd');
            $type           = 0;
            $jsChartDataAmt = $this->_getChartData($begin, $end, $type);

            $x_arr = array(
                    '0' => '支付宝和微信',
                    '1' => '支付宝',
                    '2' => '微信',
                    '3' => '翼支付'
            );

            $this->assign('jsChartDataAmt', json_encode($jsChartDataAmt));
            $this->assign('begin', $begin);
            $this->assign('end', $end);
            $this->assign('x_arr', $x_arr);
            $this->assign('type', $type);
            $this->assign('count', $count);
            $this->assign('count_', $count_);
            $this->assign('cancel_cnt', $cancel_cnt);
            $this->assign('cancel_amt', $cancel_amt);
            $this->display('index_off');
        } else {

            $count = count($payInfo);

            if (empty($payInfo) || $count < 1) {
                $this->display('index_ago');
            } else {
                if (count($payInfo) < 2) {
                    if ($payInfo[0]['pay_type'] == 0) {
                        //$this->info_alipay();这种写法真坑爹，我不想多说，去年买了个表
                        $this->redirect('Alipay/Index/info_alipay');
                    }
                    if ($payInfo[0]['pay_type'] == 1) {
                        // $this->info_weixin();
                        $this->redirect('Alipay/Index/info_weixin');
                    }
                    if ($payInfo[0]['pay_type'] == 2) {
                        // $this->info_yizf();
                        $this->redirect('Alipay/Index/info_yizf');
                    }
                    if ($payInfo[0]['pay_type'] == 5) {
                        // $this->info_qqzf();
                        $this->redirect('Alipay/Index/info_qqzf');
                    }
                } else {
                    // $this->info_alipay();
                    $this->display('info_alipay');
                }
            }
        }
    }

    public function notice()
    {
        $this->assign('goto', $_REQUEST['goto']);
        $this->display();
    }

    public function info_ago()
    {
        redirect(U('Alipay/Index/info_alipay'));
        exit;
        // $row=M('tzfb_offline_pay_info')->where(array('node_id'=>$this->node_id))->getfield('check_status');
        $zfb_offline = $row = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->find();
        $row         = $zfb_offline['check_status'];

        $tindustry_info      = M('tindustry_info')->field('industry_name')->select();
        $node_info           = M('tnode_info')->where(
                array(
                        'node_id' => $this->node_id
                )
        )->find();
        $province            = substr($node_info['node_citycode'], 0, 2);
        $city                = substr($node_info['node_citycode'], 2, 3);
        $selectTindustryInfo = M('tindustry_info')->where(
                array(
                        'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                )
        )->getField('industry_name');

        $this->assign('tindustry_info', $tindustry_info);
        $this->assign('row', $row);
        $this->assign('selectTindustryInfo', $selectTindustryInfo);
        $this->assign('node_info', $node_info);
        $this->assign('zfb_offline', $zfb_offline);
        $this->display();
    }

    /**
     * 保存申请信息
     */
    public function _tdraft_save()
    {
        $tdraftFlag = I('tdraft_flag', 0, 'intval');
        if ($tdraftFlag == 0) {
            return;
        }

        $row = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->getfield('status');
        if ($row && ($row['status'] == '1' || $row['status'] == '2')) {
            $this->error('操作非法tdraft');
        }

        $reqData    = I();
        $data       = array(
                'node_name'     => $reqData['node_name'],
                'industry'      => $reqData['trade1'],
                'province'      => $reqData['province'],
                'city'          => $reqData['city'],
                'town'          => $reqData['town'],
                'address'       => $reqData['Detposition'],
                'store_pic1'    => $reqData['resp_img1'],
                'store_pic2'    => $reqData['resp_img2'],
                'store_pic3'    => $reqData['resp_img3'],
                'contact_name'  => $reqData['petname'],
                'contact_phone' => $reqData['iphone'],
                'zfb_account'   => $reqData['account'],
                'account_pid'   => $reqData['account_pid'],
                'account_key'   => $reqData['account_key'],
                'tg_client_str' => $reqData['fclient_id'],
                'business_img'  => $reqData['business_img'],
                'shop_type'     => $reqData['shop_type']
        );
        $map        = array(
                'node_id' => $this->node_id,
                'type'    => '2'
        );
        $tdraftInfo = M('tdraft')->where($map)->find();
        if ($tdraftInfo) {
            $tdraftInfo['content'] = json_encode($data);
            $flag                  = M('tdraft')->where($map)->save($tdraftInfo);
            if ($flag === false) {
                $this->error('草稿保存失败！');
            }
        } else {
            $tdraftInfo = array(
                    'node_id'  => $this->node_id,
                    'content'  => json_encode($data),
                    'add_time' => date('YmdHis'),
                    'type'     => '2'
            );
            $flag       = M('tdraft')->add($tdraftInfo);
            if ($flag === false) {
                $this->error('草稿保存失败！');
            }
        }
        $this->success('草稿保存成功！');
    }

    public function addSubmit()
    {
        $this->_tdraft_save();
        // 判断当前商户类型
        $row = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->count();
        if ($row > 1) {
            $this->error('操作非法！！！');
        }
        $data = I();

        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['trade1'] == '') {
            $this->error('行业不能为空');
        }
        if ($data['province'] == '' || $data['city'] == '' || $data['town'] == '') {
            $this->error('联系地址不能为空');
        }
        if ($data['Detposition'] == '') {
            $this->error('具体位置不能为空');
        }
        if ($data['petname'] == '') {
            $this->error('姓名不能为空');
        }
        if ($data['iphone'] == '') {
            $this->error('手机号不能为空');
        }
        if ($data['account'] == '') {
            $this->error('支付宝帐号不能为空');
        }
        /*
         * if($data['whatis'] =='')
         * $this->error('注册资本不能为空');
         * if($data['newyear'] =='')
         * $this->error('预计年收入不能为空');
         * if($data['renfu'] =='')
         * $this->error('员工人数不能为空');
         * if($data['area'] =='')
         * $this->error('营业场所面积不能为空');
         */
        if ($data['resp_img1'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($data['resp_img2'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($data['resp_img3'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($_REQUEST['pid_key'] == 1) {
            if ($data['account_pid'] == '') {
                $this->error('支付宝PID不能为空');
            }
            if ($data['account_key'] == '') {
                $this->error('支付宝KEY不能为空');
            }
        }
        if ($data['shop_type'] == 1 && $this->node_type_name == 'c0') {
            if ($data['business_img'] == '') {
                $this->error('营业执照扫描件不能为空');
            }
        }

        /*
         * //移动图片
         * $flag = move_batch_image($data['resp_img1'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片1路径非法');
         * else
         * $data['resp_img1'] = $this->img_path.basename($data['resp_img1']);
         * $flag = move_batch_image($data['resp_img2'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片2路径非法');
         * else
         * $data['resp_img2'] = $this->img_path.basename($data['resp_img2']);
         * $flag = move_batch_image($data['resp_img3'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片3路径非法');
         * else
         * $data['resp_img3'] = $this->img_path.basename($data['resp_img3']);
         */

        $arr_ = array(
                'node_id'          => $this->node_id,
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['node_mail'],
                'province'         => $data['province'],
                'city'             => $data['city'],
                'town'             => $data['town'],
                'contact_name'     => $data['petname'],
                'contact_position' => $data['contact_position'],
                'contact_phone'    => $data['iphone'],
                'contact_tel'      => $data['contact_tel'],
                'contact_eml'      => $data['contact_eml'],
                'zfb_account'      => $data['account'],
                'address'          => $data['Detposition'],
            // 'reg_money'=>$data['whatis'],
            // 'year_income'=>$data['newyear'],
            // 'staff_count'=>$data['renfu'],
            // 'store_area'=>$data['area'],
                'add_time'         => date('YmdHis'),
                'industry'         => $data['trade1'],
                'check_status'     => '0',
                'store_pic1'       => $data['resp_img1'],
                'store_pic2'       => $data['resp_img2'],
                'store_pic3'       => $data['resp_img3'],
                'account_pid'      => $data['account_pid'],
                'account_key'      => $data['account_key'],
                'tg_client_str'    => $data['fclient_id'],
                'apply_data'       => json_encode(
                        array(
                                'business_img' => $data['business_img']
                        )
                ),
                'shop_type'        => $data['shop_type']
        );

        $outcome = M('tzfb_offline_pay_info')->add($arr_);
        // echo M()->getLastSql();exit;
        $this->assign('strtype', '支付宝');
        if ($outcome) {
            // 删除草稿
            M('tdraft')->where(
                    array(
                            'node_id' => $this->node_id,
                            'type'    => 2
                    )
            )->delete();
            // 发邮件
            $this->_apply_sendmail($data);
            // 记录申请数
            $map       = array(
                    'log_day'    => date('Ymd'),
                    'apply_type' => 0
            );
            $tmzfModel = M('ttmzf_apply_stat');
            $info      = $tmzfModel->where($map)->find();
            if ($info) {
                $tmzfModel->where($map)->setInc('apply_num');
            } else {
                $data              = $map;
                $data['apply_num'] = 1;
                $tmzfModel->add($data);
            }

            $this->success(1);
        } else {
            $this->error('提交失败');
        }
    }

    public function etidSubmit()
    {
        $this->_tdraft_save();
        // 判断当前商户类型
        $row = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->field('check_status,apply_data')->find();
        // if($row=='0' || $row=='1' || $row=='3' || $row=='4')$this->error('操作非法2！！！');
        if ($row['check_status'] == '1') {
            $this->error('申请已通过，无法编辑申请信息');
        }
        $data = I();
        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['trade1'] == '') {
            $this->error('行业不能为空');
        }
        if ($data['province'] == '' || $data['city'] == '' || $data['town'] == '') {
            $this->error('联系地址不能为空');
        }
        if ($data['Detposition'] == '') {
            $this->error('具体位置不能为空');
        }

        if ($data['petname'] == '') {
            $this->error('姓名不能为空');
        }
        if ($data['iphone'] == '') {
            $this->error('手机号不能为空');
        }
        if ($data['account'] == '') {
            $this->error('支付宝帐号不能为空');
        }
        /*
         * if($data['whatis'] =='')
         * $this->error('注册资本不能为空');
         * if($data['newyear'] =='')
         * $this->error('预计年收入不能为空');
         * if($data['renfu'] =='')
         * $this->error('员工人数不能为空');
         * if($data['area'] =='')
         * $this->error('营业场所面积不能为空');
         */
        if ($data['resp_img1'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($data['resp_img2'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($data['resp_img3'] == '') {
            $this->error('必须上传3张门店图片');
        }
        if ($_REQUEST['pid_key'] == 1) {
            if ($data['account_pid'] == '') {
                $this->error('支付宝PID不能为空');
            }
            if ($data['account_key'] == '') {
                $this->error('支付宝KEY不能为空');
            }
        }
        if ($data['shop_type'] == 1 && $this->node_type_name == 'c0') {
            if ($data['business_img'] == '') {
                $this->error('营业执照扫描件不能为空');
            }
        }
        /*
         * //移动图片
         * $flag = move_batch_image($data['resp_img1'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片1路径非法');
         * else
         * $data['resp_img1'] = $this->img_path.basename($data['resp_img1']);
         * $flag = move_batch_image($data['resp_img2'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片2路径非法');
         * else
         * $data['resp_img2'] = $this->img_path.basename($data['resp_img2']);
         * $flag = move_batch_image($data['resp_img3'],'999',$this->node_id);
         * if($flag !== true)
         * $this->error('门店图片3路径非法');
         * else
         * $data['resp_img3'] = $this->img_path.basename($data['resp_img3']);
         */

        $arr_ = array(
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['node_mail'],
                'province'         => $data['province'],
                'city'             => $data['city'],
                'town'             => $data['town'],
                'contact_name'     => $data['petname'],
                'contact_position' => $data['contact_position'],
                'contact_phone'    => $data['iphone'],
                'contact_tel'      => $data['contact_tel'],
                'contact_eml'      => $data['contact_eml'],
                'zfb_account'      => $data['account'],
            // 'reg_money'=>$data['whatis'],
            // 'year_income'=>$data['newyear'],
            // 'staff_count'=>$data['renfu'],
            // 'store_area'=>$data['area'],
                'address'          => $data['Detposition'],
            // 'add_time' => date('YmdHis'),
                'industry'         => $data['trade1'],
                'check_status'     => '0',
                'store_pic1'       => $data['resp_img1'],
                'store_pic2'       => $data['resp_img2'],
                'store_pic3'       => $data['resp_img3'],
                'account_pid'      => $data['account_pid'],
                'account_key'      => $data['account_key'],
                'tg_client_str'    => $data['fclient_id'],
                'shop_type'        => $data['shop_type'],
                'apply_data'       => json_encode(
                        array_merge(
                                (array)json_decode($row['apply_data']),
                                array(
                                        'business_img' => $data['business_img']
                                )
                        )
                )
        );

        $outcome = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->save($arr_);
        $this->assign('strtype', '支付宝');
        if ($outcome) {
            // 删除草稿
            M('tdraft')->where(
                    array(
                            'node_id' => $this->node_id,
                            'type'    => 2
                    )
            )->delete();
            $this->success(1);
        } else {
            $this->error('提交失败');
        }
    }

    public function info()
    {
        $info     = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => '0'
                )
        )->find();
        $model    = M('tcity_code');
        $province = $model->where(
                array(
                        'province_code' => $info['province'],
                        'city_level'    => '1'
                )
        )->getfield('province');
        $city     = $model->where(
                array(
                        'city_code'  => $info['city'],
                        'city_level' => '2'
                )
        )->getfield('city');
        $town     = $model->where(
                array(
                        'town_code'  => $info['town'],
                        'city_code'  => $info['city'],
                        'city_level' => '3'
                )
        )->getfield('town');
        // echo M()->getLastSql();
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('town', $town);
        $this->assign('info', $info);
        $this->display();
    }

    function group_type()
    {
        $arr_id = $this->filtergroud();
        M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->field('DISTINCT tg.id,tg.group_name')->where('tr.store_id=ti.store_id and tr.store_group_id = tg.id')->where(
                array(
                        'tg.node_id' => $this->node_id
                )
        );

        if (empty($this->userInfo['role_id']) && $this->userInfo['new_role_id'] == 200 && $arr_id != array(
                        '0'
                )
        ) {
            M()->where(
                    array(
                            'tg.id' => array(
                                    'in',
                                    $arr_id
                            )
                    )
            );
        }
        $list = M()->select();
        while (list ($k, $v) = each($list)) {
            $group_type_bak[$v['id']] = $v['group_name'];
        }
        return $group_type_bak;
    }

    public function pos_trace()
    {
        $arr_  = array(
                '0' => '成功',
                '1' => '失败',
                '2' => '成功',
                '6' => '待确认'
        );
        $arr2_ = array(
                '0' => '已撤销',
                '1' => '撤销失败',
                '2' => '已撤销',
                '6' => '已撤销'
        );
        $arr3_ = array(
                '0' => '成功',
                '1' => '失败',
                '2' => '已撤销'
        );
        $type_ = array(
                'T' => '支付',
                'C' => '撤销',
                'R' => '退款'
        );
        // $from_type_arr = array('1' => '支付宝', '2' => '微信');
        $fromType = array(
                '100' => '支付宝',
                '101' => '微信',
                '102' => '翼支付',
                '105' => 'QQ支付'
        );
        $money    = $_REQUEST['money'];
        $this->assign('money', $money);
        $trade_no = $_REQUEST['trade_no'];
        $this->assign('trade_no', $trade_no);
        $trade_type = $_REQUEST['trade_type'];
        $this->assign('trade_type', $trade_type);
        $trade_group = $_REQUEST['trade_group'];
        $this->assign('trade_group', $trade_group);
        $zfb_out_pos_seq = $_REQUEST['zfb_out_pos_seq'];
        $this->assign('zfb_out_pos_seq', $zfb_out_pos_seq);
        $group_type_bak = $this->group_type();
        $arr_id         = $this->filtergroud();
        $this->assign('group_type_arr', $group_type_bak);
        $model = M();
        $map   = array(
                'T.node_id'      => $this->node_id,
                'T.zfb_trade_no' => array(
                        array(
                                'neq',
                                ''
                        ),
                        array(
                                'exp',
                                'is not null'
                        )
                ),
            // '_string'=>"T.trans_type='T' OR T.trans_type='R'",
                'T.trans_type'   => array(
                        'in',
                        array(
                                'C',
                                'T',
                                'R'
                        )
                )
        );
        $name  = I('name');
        $this->assign('name', $name);
        if ($name != '') {
            $map['ti1.store_name'] = array(
                    'LIKE',
                    "%$name%"
            );
        }
        $posnumber = I('posnumber');
        $this->assign('posnumber', $posnumber);
        if ($posnumber != '') {
            $map['P.pos_id'] = $posnumber;
        }
        $arr_name = I('arr_name');
        $this->assign('arr_name', $arr_name);
        if ($arr_name != '') {
            $map['T.zfb_buyer_logon_id'] = $arr_name;
        }

        $status = I('status');
        $this->assign('status', $status);
        if ($status != '') {
            if ($status == '0') {
                $map['T.status'] = array(
                        'in',
                        '0,2,6'
                );
            }
            if ($status == '1') {
                $map['T.status'] = array(
                        'in',
                        '1'
                );
            }
        }
        if ($trade_type != '') {
            if (($status == '2' || $status == '3') && $trade_type == 'T') {
                $map['T.is_canceled'] = array('in', '2');
                if ($status == 2) {
                    $map['T.status'] = array(
                            'in',
                            '0,2,6'
                    );
                } else {
                    $map['T.status'] = array(
                            'in',
                            '1'
                    );
                }
            } elseif ($trade_type == 'T' && $status == 4) {
                $map['T.is_canceled'] = array('in', '1');
            } elseif ($trade_type == 'T' && ($status == '0' || $status == 1)) {
                $map['T.is_canceled'] = array('in', '0');
            }
            $map['_string'] = 'T.trans_type = "' . $trade_type . '"';
        }
        $from_type = I('from_type');
        if ($from_type != '') {
            $map['T.code_type'] = $from_type;
        }
        // if($status != '') $map['T.status']=$status;
        if (!empty($trade_no)) {
            $map['zfb_trade_no'] = array(
                    'like',
                    '%' . $trade_no . '%'
            );
        }
        if (!empty($zfb_out_pos_seq)) {
            $map['zfb_out_trade_no'] = array(
                    'like',
                    '%' . $zfb_out_pos_seq . '%'
            );
        }
        if ($money[0] != '' && $money[1] != '') {
            $map['exchange_amt'] = array(
                    array(
                            'egt',
                            $money[0]
                    ),
                    array(
                            'elt',
                            $money[1]
                    )
            );
        } else if ($money[0] != '') {
            $map['exchange_amt'] = array(
                    'egt',
                    $money[0]
            );
        } else if ($money[1] != '') {
            $map['exchange_amt'] = array(
                    'elt',
                    $money[1]
            );
        }
        if (I('badd_time')) {
            $badd_time = date('YmdHis', strtotime(I('badd_time')));
        }
        if (I('eadd_time')) {
            $eadd_time = date('YmdHis', strtotime(I('eadd_time')));
        }
        if ($badd_time != '' || $eadd_time != '') {
            $map['T.trans_time'] = array(
                    'between',
                    array(
                            $badd_time,
                            $eadd_time
                    )
            );
        } else {
            $eadd_time = $badd_time = date('Ymd');
            $eadd_time .= '235959';
            $map['T.trans_time'] = array(
                    'between',
                    array(
                            $badd_time,
                            $eadd_time
                    )
            );
        }
        $this->assign('badd_time', date('Y-m-d H:i:s', strtotime($badd_time)));
        // if($eadd_time != '') $map['T.trans_time']=array('elt',);
        $this->assign('eadd_time', date('Y-m-d H:i:s', strtotime($eadd_time)));

        import('ORG.Util.Page');
        $dataurl = I('request.');
        $sql_bak = 'select  ti.store_id from `tstore_group` `tg`, `tstore_info` `ti`, `tgroup_store_relation` `tr` where tr.store_id = ti.store_id   AND tr.store_group_id = tg.id';
        if (!empty($trade_group)) {
            if (empty($this->userInfo['role_id']) && $this->userInfo['new_role_id'] == 200) {
                if ($arr_id != array(
                                '0'
                        )
                ) {
                    $sql_bak .= ' and tg.id in  (' . implode(',', $arr_id) . ') and tg.id = ' . $trade_group;
                } else {
                    $sql_bak .= ' and tg.id = ' . $trade_group;
                }
            } else {
                $sql_bak .= ' and tg.id = ' . $trade_group;
            }
            $map['_string'] = 'P.store_id in (' . $sql_bak . ')';
        } else {
            if (empty($this->userInfo['role_id']) && $this->userInfo['new_role_id'] == 200 && array(
                            '0'
                    ) != $arr_id
            ) {
                $sql_bak .= ' and tg.id in  (' . implode(',', $arr_id) . ')';
                $map['_string'] = 'P.store_id in (' . $sql_bak . ')';
            }
        }
        $model->table(
                array(
                        'tzfb_offline_pay_trace' => 'T'
                )
        )->join("tpos_info P ON T.pos_id=P.pos_id")->join("tnode_info N ON T.node_id=N.node_id")->join(
                "tstore_info ti1 ON P.store_id=ti1.store_id"
        )->where($map)->field('T.*,p.store_name pstorename,N.node_name,ti1.store_name');
        $mapcount  = $model->count();
        $Page      = new Page($mapcount, 10);
        $listmodel = $model->table(
                array(
                        'tzfb_offline_pay_trace' => 'T'
                )
        )->join("tpos_info P ON T.pos_id=P.pos_id")->join("tnode_info N ON T.node_id=N.node_id")->join(
                "tstore_info ti1 ON P.store_id=ti1.store_id"
        )->where($map)->field('T.*,p.store_name pstorename,N.node_name,ti1.store_name');
        if ($_REQUEST['down'] == 1) {
            $DOWNLOAD_MAX_COUNT = C('DOWNLOAD_MAX_COUNT') ? C('DOWNLOAD_MAX_COUNT') : 10;
            if ($mapcount > $DOWNLOAD_MAX_COUNT) {
                $this->error('数据量太大,请选择时间区间分批次下载');
            }
            ini_set("max_execution_time", "120");
            $list = $listmodel->selectSlave()->select();
            log_write('多米收单-交易明细-下载：' . $listmodel->getLastSql());
            $this->csv_h('条码支付/交易明细数据');
            $this->downloadCsvData(
                    array(
                            array(
                                    "交易明细查询",
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "查询起始时间:",
                                    $badd_time ? $badd_time : '无',
                                    "查询结束时间:",
                                    $eadd_time ? $eadd_time : '无',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "交易方式:",
                                    ($fromType[$from_type] ? $fromType[$from_type] : '不限'),
                                    "\r\n\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    null,
                    array(
                            '支付流水号',
                            '商户订单号',
                            '门店详称',
                            '终端号',
                            '终端流水号',
                            '交易时间',
                            '交易金额',
                            '商家优惠金额',
                            '支付平台优惠金额',
                            '优惠总金额',
                            '交易账户',
                            '支付方式',
                            '交易类型',
                            '交易状态',
                            '手续费/退手续费',
                            '实收金额',
                            "\r\n"
                    )
            );
            foreach ($list as $k => $vo) {
                $list_bak['zfb_trade_no']     = $vo['zfb_trade_no'] ? $vo['zfb_trade_no'] . "\t" : '未知';
                $list_bak['zfb_trade_no']     = iconv("UTF-8", "gbk", $list_bak['zfb_trade_no']);
                $list_bak['zfb_out_trade_no'] = $vo['zfb_out_trade_no'] ? $vo['zfb_out_trade_no'] . "\t" : '未知';
                $list_bak['zfb_out_trade_no'] = iconv("UTF-8", "gbk", $list_bak['zfb_out_trade_no']);
                $list_bak['store_name']       = ($vo['store_name'] != '') ? $vo['store_name'] : '未知';
                $list_bak['store_name']       = iconv("UTF-8", "gbk", $list_bak['store_name']);
                $list_bak['pos_id']           = $vo['pos_id'] ? $vo['pos_id'] . "\t" : '未知';
                $list_bak['pos_id']           = iconv("UTF-8", "gbk", $list_bak['pos_id']);

                $list_bak['pos_seq'] = $vo['pos_seq'] ? $vo['pos_seq'] . "\t" : '未知';
                $list_bak['pos_seq'] = iconv("UTF-8", "gbk", $list_bak['pos_seq']);

                $list_bak['trans_date'] = date('Y-m-d H:i', strtotime($vo['trans_time'])) . "\t";
                $list_bak['trans_date'] = iconv("UTF-8", "gbk", $list_bak['trans_date']);

                $list_bak['exchange_amt'] = ($vo['exchange_amt'] > 0) ? $vo['exchange_amt'] : "0.00\t";
                $list_bak['exchange_amt'] = iconv("UTF-8", "gbk", $list_bak['exchange_amt']);

                $type         = $vo['code_type'];
                $merchant_fee = (($vo['mcard_fee'] > 0) ? $vo['mcard_fee'] : 0.00) + (($vo['mdiscount_fee'] > 0) ? $vo['mdiscount_fee'] : 0.00) + (($vo['mcoupon_fee'] > 0) ? $vo['mcoupon_fee'] : 0.00);
                $platform_fee = (($vo['zfb_coupon_fee'] > 0) ? $vo['zfb_coupon_fee'] : 0.00) + (($vo['point_fee'] > 0) ? $vo['point_fee'] : 0.00) + (($vo['discount_fee'] > 0) ? $vo['discount_fee'] : 0.00);
                $all_free     = $merchant_fee + $platform_fee;
                switch ($type) {
                    case '100':
                        $list_bak['merchant_fee'] = $merchant_fee . "\t";
                        $list_bak['platform_fee'] = $platform_fee . "\t";
                        $list_bak['all_free']     = $all_free . "\t";
                        break;
                    case '101':
                        $list_bak['merchant_fee'] = "/" . "\t";
                        $list_bak['platform_fee'] = "/" . "\t";
                        $list_bak['all_free']     = $all_free . "\t";
                        break;
                    case '102':
                        $list_bak['merchant_fee'] = "/" . "\t";
                        $list_bak['platform_fee'] = '/' . "\t";
                        $list_bak['all_free']     = '/' . "\t";
                        break;
                    case '105':
                        $list_bak['merchant_fee'] = '/' . "\t";
                        $list_bak['platform_fee'] = '/' . "\t";
                        $list_bak['all_free']     = $all_free . "\t";
                        break;
                }

                $list_bak['zfb_buyer_logon_id'] = $vo['zfb_buyer_logon_id'] ? $vo['zfb_buyer_logon_id'] : '未知';
                $list_bak['zfb_buyer_logon_id'] = iconv("UTF-8", "gbk", $list_bak['zfb_buyer_logon_id']);

                $list_bak['code_type'] = $fromType[$vo['code_type']];
                $list_bak['code_type'] = iconv("UTF-8", "gbk", $list_bak['code_type']);

                $list_bak['trans_type'] = $type_[$vo['trans_type']];
                $list_bak['trans_type'] = iconv("UTF-8", "gbk", $list_bak['trans_type']);

                if (in_array($vo['status'], array(0)) && in_array(
                                $vo['is_canceled'],
                                array(1)
                        ) && $vo['trans_type'] == 'T'
                ) {
                    $list_bak['status'] = '成功已退款';
                } elseif (in_array($vo['status'], array(0, 2, 6)) && in_array(
                                $vo['is_canceled'],
                                array(1, 2)
                        ) && $vo['trans_type'] == 'T'
                ) {
                    $list_bak['status'] = '成功已撤销';
                } elseif (in_array($vo['status'], array(1)) && in_array(
                                $vo['is_canceled'],
                                array(1, 2)
                        ) && $vo['trans_type'] == 'T'
                ) {
                    $list_bak['status'] = '失败已撤销';
                } else {
                    $list_bak['status'] = $arr_[$vo['status']];
                }

                $list_bak['status'] = iconv("UTF-8", "gbk", $list_bak['status']);

                $list_bak['fee_amt'] = (!empty($vo['fee_amt']) && $vo['fee_amt'] != 'NULL') ? abs(
                        $vo['fee_amt']
                ) : "0.00\t";
                $list_bak['fee_amt'] = iconv("UTF-8", "gbk", $list_bak['fee_amt']);

                //                $settle_amt = @($vo['exchange_amt'] - abs($vo['fee_amt']));
                $settle_amt               = $vo['real_amt'];
                $list_bak['exchange_amt'] = iconv("UTF-8", "gbk", $list_bak['exchange_amt']);

                $list_bak['settle_amt'] = (!empty($settle_amt) && $settle_amt != 'NULL') ? $settle_amt : "0.00\t";
                $list_bak['settle_amt'] = iconv("UTF-8", "gbk", $list_bak['settle_amt']);

                $csv_row = trim(implode(",", $list_bak), ',');
                echo $csv_row . "\r\n";
            }
        } else {
            $list = $listmodel->limit($Page->firstRow . ',' . $Page->listRows)->order('T.trans_time desc')->select();
            log_write('多米收单-交易明细-查询：' . $listmodel->getLastSql());
            $show = $Page->show();
            $this->assign('arr_', $arr_);
            $this->assign('arr2_', $arr2_);
            $this->assign('arr3_', $arr3_);
            $this->assign('from_type', $from_type);
            $this->assign('fromType', $fromType);
            // $this->assign('from_type_arr',$from_type_arr);
            $this->assign('type_', $type_);
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->assign(
                    'parmes',
                    $_REQUEST + array(
                            'down' => 1
                    )
            );
            $this->display();
        }
    }

    function filtergroud()
    {
        $role_id = 200;
        if (empty($this->userInfo['role_id']) && $this->userInfo['new_role_id'] == 200) {
            $groupids = M()->table(
                    array(
                            'tuser_role_power' => 'trp',
                            'tuser_info'       => 'ti'
                    )
            )->where('trp.user_id = ti.user_id')->where(
                    array(
                            'ti.user_id'       => $this->userInfo['user_id'],
                            'trp.user_role_id' => $role_id
                    )
            )->field('trp.power_list')->find();

            if (empty($groupids['power_list'])) {
                return array(
                        '0'
                );
            }
            $arr_id = explode(',', $groupids['power_list']);
            if (empty($arr_id)) {
                return array(
                        '0'
                );
            } else {
                return $arr_id;
            }
        } else {
            return array(
                    '0'
            );
        }
    }

    public function day_stat()
    {
        $arr_id   = $this->filtergroud();
        $fromType = array(
                '0' => '支付宝',
                '1' => '微信',
                '2' => '翼支付',
                '4' => '通联支付',
                '5' => 'QQ支付'
        );
        // $this->assign('group_type_arr',$this->group_type());
        $trade_group = $_REQUEST['trade_group'];
        $this->assign('trade_group', $trade_group);
        $sttype = $_REQUEST['sttype'];
        $model  = M();
        $map    = array(
                'ts.node_id' => $this->node_id
        );
        $ttype  = 1;
        if ($_REQUEST['ttype'] != '') {
            $ttype = $_REQUEST['ttype'];
        }
        $tvalue = 3;
        if ($_REQUEST['tvalue'] != '') {
            $tvalue = $_REQUEST['tvalue'];
        }
        $name = I('name');
        $this->assign('name', $name);
        $sql_bak = 'left join (select DISTINCT ti.store_id,tg.*,ti.store_name from  `tstore_info` `ti`
		   	    left join `tgroup_store_relation` `tr` on  tr.store_id = ti.store_id
				left join `tstore_group` `tg` on tr.store_group_id = tg.id where ti.node_id = \'' . $this->node_id . '\')';
        $model->table(
                array(
                        'tzfb_day_stat' => 'ts',
                        'tpos_info'     => 'tp'

                )
        )->join($sql_bak . 'tbak ON  tbak.store_id = tp.store_id')->where('ts.pos_id = tp.pos_id');
        if ($trade_group == 2) {
            if (!empty($name)) {
                $map['tbak.group_name'] = array(
                        'LIKE',
                        "%$name%"
                );
            }
        } else {
            if (!empty($name)) {
                $map['tbak.store_name'] = array(
                        'LIKE',
                        "%$name%"
                );
            }
        }
        if (empty($this->userInfo['role_id']) && $this->userInfo['new_role_id'] == 200 && array(
                        '0'
                ) != $arr_id
        ) {
            $map['tbak.id'] = array(
                    'in',
                    $arr_id
            );
        }
        $from_type = I('from_type', null);
        if ($from_type != '') {
            $map['ts.from_type'] = $from_type;
        }
        if ($ttype == 1) {
            switch ($tvalue) {
                case 1:
                    $badd_time            = date('Ymd');
                    $map['ts.trans_date'] = array(
                            'egt',
                            $badd_time
                    );
                    break;
                case 3:
                    $eadd_time            = date('Ymd');
                    $badd_time            = date('Ymd', strtotime("-7 day"));
                    $map['ts.trans_date'] = array(
                            array(
                                    'elt',
                                    $eadd_time
                            ),
                            array(
                                    'gt',
                                    $badd_time
                            )
                    );
                    break;
                case 4:
                    $eadd_time            = date('Ymd');
                    $badd_time            = date('Ymd', strtotime("-30 day"));
                    $map['ts.trans_date'] = array(
                            array(
                                    'elt',
                                    $eadd_time
                            ),
                            array(
                                    'gt',
                                    $badd_time
                            )
                    );
                    break;
                case 2:
                default:
                    $badd_time            = date('Ymd', strtotime("-1 day"));
                    $eadd_time            = date('Ymd');
                    $map['ts.trans_date'] = array(
                            array(
                                    'egt',
                                    $badd_time
                            ),
                            array(
                                    'lt',
                                    $eadd_time
                            )
                    );
                    break;
            }
            $this->assign('badd_time', date('Y-m-d', strtotime($badd_time)));
            $this->assign('eadd_time', date('Y-m-d', strtotime($eadd_time)));
        } else {
            $badd_time = I('badd_time', null);
            $eadd_time = I('eadd_time');
            if ($badd_time != '' || $eadd_time != '') {
                if ($badd_time == '') {
                    $badd_time = '00000000';
                } else {
                    $badd_time = str_replace('-', '', $badd_time);
                }
                if ($eadd_time == '') {
                    $eadd_time = '99999999';
                } else {
                    $eadd_time = str_replace('-', '', $eadd_time);
                }
                $map['ts.trans_date'] = array(
                        'between',
                        array(
                                $badd_time,
                                $eadd_time
                        )
                );
            }
            $this->assign('badd_time', date('Y-m-d', strtotime($badd_time)));
            $this->assign('eadd_time', date('Y-m-d', strtotime($eadd_time)));
        }
        import('ORG.Util.Page');
        $dataurl = I('request.');
        $model->field(
                array(
                        'sum(cancel_fee_amt+revoke_fee_amt)' => 'cancel_fee_amt',
                        'sum(verify_fee_amt)'                => 'verify_fee_amt',
                        'sum(verify_cnt)'                    => 'verify_cnt',
                        'sum(verify_amt)'                    => 'verify_amt',
                        'sum(ts.fee_amt)'                    => 'fee_amt',
                        'sum(cancel_cnt)'                    => 'cancel_cnt',
                        'sum(cancel_amt)'                    => 'cancel_amt',
                        'sum(revoke_cnt)'                    => 'revoke_cnt',
                        'sum(revoke_amt)'                    => 'revoke_amt',
                        'sum(real_amt)'                      => 'real_amt',
                        'sum(ts.shop_coupon_amt)'            => 'shop_coupon_amt',
                        'sum(ts.platform_coupon_amt)'        => 'platform_coupon_amt',
                        'tbak.store_name',
                        'tbak.group_name',
                        'ts.trans_date',
                        'ts.from_type'
                )
        )->where($map);
        if ($sttype == 2) {
            $model->group('ts.trans_date');
        } elseif ($sttype == 3) {
            $model->group('tbak.id');
        } else if ($sttype == 4) {
            $model->group('ts.from_type');
        } else {
            $model->group('tp.store_id');
        }
        $modelbak = clone $model;
        $mapcount = M()->query('select count(*) c from (' . $model->select(false) . ')t');
        $mapcount = $mapcount[0][c];
        if (!empty($_REQUEST['down']) && $_REQUEST['down'] == 1) {
            $DOWNLOAD_MAX_COUNT = C('DOWNLOAD_MAX_COUNT') ? C('DOWNLOAD_MAX_COUNT') : 10;
            if ($mapcount > $DOWNLOAD_MAX_COUNT) {
                $this->error('数据量太大,请选择时间区间分批次下载');
            }
            ini_set("max_execution_time", "120");
            $list = $modelbak->order('ts.trans_date desc')->select();
            log_write('多米收单-交易统计-下载：' . $modelbak->getLastSql());
            $count           = 0;
            $count_          = 0;
            $fee_amt         = 0;
            $cancel_cnt      = 0;
            $cancel_amt      = 0;
            $revoke_cnt      = 0;
            $revoke_amt      = 0;
            $cancel_fee_amt  = 0;
            $verify_fee_amt  = 0;
            $real_amt        = 0;
            $shop_coupon_amt = 0;
            foreach ($list as $k => $vo) {
                $count += $vo['verify_cnt'];
                $count_ += $vo['verify_amt'];
                $fee_amt += $vo['fee_amt'];
                $cancel_cnt += $vo['cancel_cnt'];
                $cancel_amt += $vo['cancel_amt'];
                $revoke_cnt += $vo['revoke_cnt'];
                $revoke_amt += $vo['revoke_amt'];
                $real_amt += $vo['real_amt'];
                $cancel_fee_amt += $vo['cancel_fee_amt'];
                $verify_fee_amt += $vo['verify_fee_amt'];
                $shop_coupon_amt += $vo['shop_coupon_amt'];
                if ($sttype == 2) {
                    $list_bak[$k]['store_name'] = $vo['trans_date'];
                } else if ($sttype == 3) {
                    $list_bak[$k]['store_name'] = $vo['group_name'];
                } else if ($sttype == 4) {
                    $list_bak[$k]['store_name'] = $fromType[$vo['from_type']];
                } else {
                    $list_bak[$k]['store_name'] = $vo['store_name'];
                }
                if (empty($list_bak[$k]['store_name'])) {
                    $list_bak[$k]['store_name'] = '空';
                }
                $list_bak[$k]['verify_cnt'] = $vo['verify_cnt'];
                $list_bak[$k]['verify_amt'] = $vo['verify_amt'] > 0 ? $vo['verify_amt'] : "0.00";
                $list_bak[$k]['cancel_cnt'] = $vo['cancel_cnt'];
                $list_bak[$k]['cancel_amt'] = $vo['cancel_amt'] > 0 ? $vo['cancel_amt'] : "0.00";
                $list_bak[$k]['revoke_cnt'] = $vo['revoke_cnt'];
                $list_bak[$k]['revoke_amt'] = $vo['revoke_amt'] > 0 ? $vo['revoke_amt'] : "0.00";

                $merchant_fee                 = (($vo['shop_coupon_amt'] > 0) ? $vo['shop_coupon_amt'] : 0.00);
                $platform_fee                 = (($vo['platform_coupon_amt'] > 0) ? $vo['platform_coupon_amt'] : 0.00);
                $all_free                     = $merchant_fee + $platform_fee;
                $list_bak[$k]['merchant_fee'] = $merchant_fee;
                $list_bak[$k]['platform_fee'] = $platform_fee;
                $list_bak[$k]['all_free']     = $all_free;

                $list_bak[$k]['v1'] = round($vo['verify_fee_amt'], 2) > 0 ? round($vo['verify_fee_amt'], 2) : "0.00";
                $list_bak[$k]['v2'] = round($vo['cancel_fee_amt'], 2) > 0 ? round($vo['cancel_fee_amt'], 2) : "0.00";
                $list_bak[$k]['v3'] = round($vo['real_amt'], 2);
            }
            if ($sttype == 2) {
                $this->csv_h('条码支付/交易按日期统计数据');
            } elseif ($sttype == 1) {
                $this->csv_h('条码支付/交易按门店统计数据');
            } elseif ($sttype == 3) {
                $this->csv_h('条码支付/交易按门店分组统计');
            } elseif ($sttype == 4) {
                $this->csv_h('条码支付/交易按交易方式统计');
            }
            $this->downloadCsvData(
                    array(
                            array(
                                    '交易统计查询',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "查询起始时间:",
                                    $badd_time ? $badd_time : '无',
                                    "查询结束时间:",
                                    $eadd_time ? $eadd_time : '无',
                                    "\r\n"
                            )
                    )
            );
            /*
             * $this->downloadCsvData(array(
             * array($count.'/'.$count_,$cancel_cnt.'/'.$cancel_amt,round($count_*6/1000,2),
             * $count_-$cancel_amt-round($count_*6/1000,2)."\r\n")
             * ),
             * array('交易笔数/金额','退款笔数/金额','通道费用','实际收入'));
             */

            $this->downloadCsvData(
                    array(
                            array(
                                    "交易方式:",
                                    ($fromType[$from_type] ? $fromType[$from_type] : '不限'),
                                    "\r\n\r\n"
                            )
                    )
            );

            if ($sttype == 2) {
                $this->downloadCsvData(
                        $list_bak,
                        array(
                                "日期",
                                '支付笔数',
                                '支付金额',
                                '退款笔数',
                                '退款金额',
                                '撤销笔数',
                                '撤销金额',
                                '商家优惠金额',
                                '支付平台优惠金额',
                                '总优惠金额',
                                '交易手续费',
                                '退手续费',
                                '实收金额'
                        )
                );
            } elseif ($sttype == 3) {
                $this->downloadCsvData(
                        $list_bak,
                        array(
                                "门店分组",
                                '支付笔数',
                                '支付金额',
                                '退款笔数',
                                '退款金额',
                                '撤销笔数',
                                '撤销金额',
                                '商家优惠金额',
                                '支付平台优惠金额',
                                '总优惠金额',
                                '交易手续费',
                                '退手续费',
                                '实收金额'
                        )
                );
            } elseif ($sttype == 4) {
                $this->downloadCsvData(
                        $list_bak,
                        array(
                                "支付方式",
                                '支付笔数',
                                '支付金额',
                                '退款笔数',
                                '退款金额',
                                '撤销笔数',
                                '撤销金额',
                                '商家优惠金额',
                                '支付平台优惠金额',
                                '总优惠金额',
                                '交易手续费',
                                '退手续费',
                                '实收金额'
                        )
                );
            } else {
                $this->downloadCsvData(
                        $list_bak,
                        array(
                                "门店详称",
                                '支付笔数',
                                '支付金额',
                                '退款笔数',
                                '退款金额',
                                '撤销笔数',
                                '撤销金额',
                                '商家优惠金额',
                                '支付平台优惠金额',
                                '总优惠金额',
                                '交易手续费',
                                '退手续费',
                                '实收金额'
                        )
                );
            }
            $this->downloadCsvData(
                    array(
                            array(
                                    "\r\n\r\n支付笔数:",
                                    $count . '笔',
                                    '共' . ($count_ > 0 ? $count_ : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "退款笔数:",
                                    $cancel_cnt . '笔',
                                    '共' . ($cancel_amt > 0 ? $cancel_amt : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "撤销笔数:",
                                    $revoke_cnt . '笔',
                                    '共' . ($revoke_amt > 0 ? $revoke_amt : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "交易手续费:",
                                    (round($verify_fee_amt, 2) > 0 ? round($verify_fee_amt, 2) : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "退手续费:",
                                    (round($cancel_fee_amt, 2) > 0 ? round($cancel_fee_amt, 2) : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );
            $this->downloadCsvData(
                    array(
                            array(
                                    "商家优惠金额:",
                                    (round($shop_coupon_amt, 2) > 0 ? round($shop_coupon_amt, 2) : "0.00\t") . '元',
                                    "\r\n"
                            )
                    )
            );

            $this->downloadCsvData(
                    array(
                            array(
                                    "实收金额:",
                                    (round($real_amt, 2)) . '元',
                                    "\r\n"
                            )
                    )
            );
        } else {
            $Page = new Page($mapcount, 10);
            $list = $modelbak->limit($Page->firstRow . ',' . $Page->listRows)->order('ts.trans_date desc')->select();
            log_write('多米收单-交易统计-查询：' . $modelbak->getLastSql());

            // 交易统计查询
            $count           = 0;
            $count_          = 0;
            $fee_amt         = 0;
            $cancel_cnt      = 0;
            $cancel_amt      = 0;
            $revoke_cnt      = 0;
            $revoke_amt      = 0;
            $cancel_fee_amt  = 0;
            $verify_fee_amt  = 0;
            $real_amt        = 0;
            $shop_coupon_amt = 0;
            foreach ($list as $v) {
                $count += $v['verify_cnt'];
                $count_ += $v['verify_amt'];
                $fee_amt += $v['fee_amt'];
                $cancel_cnt += $v['cancel_cnt'];
                $cancel_amt += $v['cancel_amt'];
                $revoke_cnt += $v['revoke_cnt'];
                $revoke_amt += $v['revoke_amt'];
                $real_amt += $v['real_amt'];
                $cancel_fee_amt += $v['cancel_fee_amt'];
                $verify_fee_amt += $v['verify_fee_amt'];
                $shop_coupon_amt += $v['shop_coupon_amt'];

            }

            $this->assign('count', $count);
            $this->assign('count_', $count_);
            $this->assign('cancel_cnt', $cancel_cnt);
            $this->assign('shop_coupon_amt', $shop_coupon_amt);

            $this->assign('cancel_amt', $cancel_amt);
            $this->assign('revoke_cnt', $revoke_cnt);
            $this->assign('revoke_amt', $revoke_amt);
            $this->assign('real_amt', $real_amt);
            $this->assign('fee_amt', $fee_amt);
            $this->assign('cancel_fee_amt', $cancel_fee_amt);
            $this->assign('verify_fee_amt', $verify_fee_amt);
            $show = $Page->show();
            $this->assign(
                    'parmes',
                    $_REQUEST + array(
                            'down' => 1
                    )
            );
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->assign('fromType', $fromType);
            $this->assign('from_type', $from_type);
            $this->assign('ttype', $ttype);
            $this->assign('tvalue', $tvalue);
            if ($sttype == 2) {
                $this->display('day_stat2');
            } elseif ($sttype == 3) {
                $this->display('day_stat3');
            } elseif ($sttype == 4) {
                $this->display('day_stat4');
            } else {
                $this->display('day_stat1');
            }
        }
    }

    public function csv_h($filename)
    {
        header("Content-type:text/csv");
        // go to
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . iconv("UTF-8", "gbk", $filename) . ".csv");
        header('Expires:0');
        header('Pragma:public');
    }

    public function downloadCsvData($csv_data = array(), $arrayhead = array())
    {
        $csv_string = null;
        $csv_row    = array();
        if (!empty($arrayhead)) {
            $current = array();
            foreach ($arrayhead as $item) {

                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
        if (!empty($csv_data)) {
            foreach ($csv_data as $key => $csv_item) {
                $current = array();
                foreach ($csv_item as $item) {

                    $current[] = iconv("UTF-8", "gbk", $item);
                }
                $csv_row[] = trim(implode(",", $current), ',');
            }
        }
        $csv_string = implode("\r\n", $csv_row);
        echo $csv_string;
    }

    public function download()
    {
        $id = I('get.id');
        if ($id != '1') {
            $this->error('非法操作1！！！');
        }
        if ($this->node_id == '') {
            $this->error('非法操作2！！！');
        }

        $fileName = date('Y-m-d') . '-' . rand(1000, 9999) . '-结算数据.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title_ = "门店详称,终端号,交易时间,交易金额,通道费用\r\n";
        echo $title_ = iconv('utf-8', 'gbk', $title_);
        $model = M();
        $map   = array(
                'T.node_id' => $this->node_id
        );
        $list  = $model->table('tzfb_day_stat T')->field('T.*,P.store_name')->join(
                "tpos_info P ON T.pos_id=P.pos_id"
        )->where($map)->select();
        if ($list) {
            foreach ($list as $v) {
                $store_name = iconv('utf-8', 'gbk', $v['store_name']);
                echo "{$store_name}," . "\t" . "{$v['pos_id']}," . date(
                                'Y-m-d',
                                strtotime($v['trans_date'])
                        ) . ",{$v['verify_amt']},{$v['fee_amt']}\r\n";
            }
        }
    }

    public function node_open()
    {
        $model = M();

        $id = I('id');

        if ($id == '') {
            $this->error('参数错误！！！');
        }

        $arr_  = array(
                '0' => '成功',
                '1' => '失败',
                '2' => '冲正',
                '6' => '待确认'
        );
        $type_ = array(
                'T' => '支付',
                'R' => '退款',
                'C' => '撤销'
        );

        $map         = array(
                'T.node_id' => $this->node_id,
                '_string'   => "T.trans_type='T' OR T.trans_type='R' OR T.trans_type='C'"
        );
        $map['T.id'] = $id;
        $fromType    = array(
                '100' => '支付宝',
                '101' => '微信',
                '102' => '翼支付',
                '105' => 'QQ支付'
        );
        // 查询
        $viewsql = M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->field('DISTINCT tg.id,tg.account_name, tg.group_name,ti.store_id')->where(
                'tr.store_id=ti.store_id and tr.store_group_id = tg.id'
        )->where(
                array(
                        'tg.node_id' => $this->node_id
                )
        )->select(false);

        $info                 = $model->table('tzfb_offline_pay_trace T')->field(
                'T.*,p.store_name,N.node_name,g.group_name,g.account_name'
        )->join("tpos_info P ON T.pos_id=P.pos_id")->join("($viewsql) g on g.store_id = P.store_id")->join(
                "tnode_info N ON T.node_id=N.node_id"
        )->where($map)->find();
        $info['account_name'] = $info['account_name'] ? $info['account_name'] : $this->nodeInfo['account_number'];
        $this->assign('account_name', $info['account_name']);
        $this->assign('arr_', $arr_);
        $this->assign('type_', $type_);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 获取chart数据 供页面ajax提交
     * $begin 开始时间
     * $end 结束时间
     * $type 类型 0 zfb+wx 1 zfb 2 wx
     */
    public function getChartInfo()
    {
        $days = I('days', 7);
        $type = I('type', 0);

        $begin  = date('Ymd', strtotime('-' . $days . ' days'));
        $end    = date('Ymd');
        $return = array(
                'info'  => $this->_getChartData($begin, $end, $type),
                'begin' => $begin,
                'end'   => $end
        );
        $this->ajaxReturn($return, 'json');
    }

    /**
     * 获取chart数据
     * $begin 开始时间
     * $end 结束时间
     * $type 类型 0 zfb+wx 1 zfb 2 wx
     */
    protected function _getChartData($begin, $end, $type = 0)
    {
        $chartData         = array();
        $map               = array(
                'node_id' => $this->node_id
        );
        $map['trans_date'] = array();
        if ($begin != '') {
            $map['trans_date'][] = array(
                    'EGT',
                    $begin
            );
        }
        if ($end != '') {
            $map['trans_date'][] = array(
                    'ELT',
                    $end
            );
        }
        if ($type == '1') {
            $map['from_type'] = '0';
        } elseif ($type == '2') {
            $map['from_type'] = '1';
        }

        $statInfo  = M('tzfb_day_stat')->where($map)->field('trans_date,sum(verify_amt-cancel_amt) verify_amt')->group(
                "trans_date"
        )->select();
        $chartData = array();
        if (!empty($statInfo)) {
            foreach ($statInfo as $v) {
                $chartData[] = array(
                        $v['trans_date'],
                        $v['verify_amt'] * 1
                );
            }
        }

        return $chartData;
    }

    /**
     * 支付宝申请配置页面
     * 收款账户
     */
    public function info_alipay()
    {
        //支付宝2.0智能授权
        $list = $this->getPayInfoListWithPagerAndMap(0, 50);

        $this->assign('userid', $this->userInfo['user_id']);
        $this->assign('list', $list);

        $this->assign('node_id', $this->node_id);
        $this->display();

    }

    /**
     * APP条码支付 目前只有支付宝
     * 将来应该会增加QQ支付微信支付等 都写在这
     */
    public function  wap_alipay()
    {
        $zfb = $this->getPayInfoListWithPagerAndMap(0, 50);
        $this->assign('list', $zfb);


        $this->assign('node_id', $this->node_id);
        $this->assign('position',$this->position);
        //$this->display();
    }


    /**
     * (此乃旧代码，单独提出来，如果以后不用旧的了，直接删除此代码)
     *
     * @param $alipayInfo [旧的支付宝信息]
     */
    private function get_old_alipay_info($alipayInfo)
    {
        $model          = M('tcity_code');
        $province       = $model->where(
                array(
                        'province_code' => $alipayInfo['province'],
                        'city_level'    => '1'
                )
        )->getfield('province');
        $city           = $model->where(
                array(
                        'city_code'  => $alipayInfo['city'],
                        'city_level' => '2'
                )
        )->getfield('city');
        $town           = $model->where(
                array(
                        'town_code'  => $alipayInfo['town'],
                        'city_code'  => $alipayInfo['city'],
                        'city_level' => '3'
                )
        )->getfield('town');
        $tindustry_info = M('tindustry_info')->field('industry_name')->select();
        $tdraftInfo     = M('tdraft')->where(
                array(
                        'node_id' => $this->node_id,
                        'type'    => 2
                )
        )->find();
        $apply_data     = (array)json_decode($alipayInfo['apply_data']);
        $alipayInfo     = array_merge($alipayInfo, $apply_data);
        // 如果没有申请信息或者有草稿，都需要展示申请页面
        if (empty($alipayInfo)) {
            $node_info           = M('tnode_info')->where(
                    array(
                            'node_id' => $this->node_id
                    )
            )->find();
            $selectTindustryInfo = M('tindustry_info')->where(
                    array(
                            'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                    )
            )->getField('industry_name');
            $alipayInfo          = array();
            if (empty($tdraftInfo)) {
                $alipayInfo['node_name']     = $node_info['node_name'];
                $alipayInfo['node_mail']     = $node_info['contact_eml'];
                $alipayInfo['industry']      = $selectTindustryInfo;
                $alipayInfo['province']      = substr($node_info['node_citycode'], 0, 2);
                $alipayInfo['city']          = substr($node_info['node_citycode'], 2, 3);
                $alipayInfo['town']          = substr($node_info['node_citycode'], 5, 3);
                $alipayInfo['contact_name']  = $node_info['contact_name'];
                $alipayInfo['contact_phone'] = $node_info['contact_phone'];
                $alipayInfo['contact_tel']   = $node_info['contact_tel'];
                $alipayInfo['contact_eml']   = $node_info['contact_eml'];
            } else {
                $alipayInfo = json_decode($tdraftInfo['content'], true);
            }
        }
        $this->assign('alipayInfo', $alipayInfo);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('town', $town);
        $this->assign('tindustry_info', $tindustry_info);
        return $alipayInfo;
    }

    public function alterLinkType()
    {

        $map        = array(
                'node_id'      => $this->nodeId,
                'pay_type'     => '0',
                'check_status' => '1',
                'sign_status'  => '1',
                'status'       => '1',
                'auth_flag'    => '2'
        );
        $alipayInfo = M('tzfb_offline_pay_info')->where($map)->find();
        empty($alipayInfo) && $this->error('支付宝信息有误');
        // 有cancel参数，表示清空link_type字段，方便重设开通方式
        if (I('post.cancel') == '1') {
            $saveData = ['link_type' => array('exp', 'NULL')];
            $ret      = M('tzfb_offline_pay_info')->where($map)->save($saveData);
            if ($ret === false) {
                $this->error('提交失败');
            }
            $this->success('提交成功');
        }
        $linkType = I('post.link_type');
        in_array($linkType, ['0', '1']) or $this->error('参数错误');
        $saveData = ['link_type' => $linkType];
        if ($linkType == '1') {
            $saveData['contact_name']  = I('post.contact_name');
            $saveData['contact_phone'] = I('post.contact_phone');
        }
        $ret = M('tzfb_offline_pay_info')->where($map)->save($saveData);
        if ($ret === false) {
            $this->error('提交失败');
        }
        $this->success('提交成功');
    }

    /**
     * 条码支付提交申请，发送邮件通知
     * 每个申请成功的数据发送至以下邮箱地址，内容如下：
     * 申请时间、旺号、商户名称、联系方式、商户来源（pc/wap）
     */
    function _apply_sendmail($info)
    {
        $content = '<BR/>申请商户: ' . $this->nodeInfo['node_name'];
        $content .= '<BR/>旺号: ' . $this->nodeInfo['client_id'];
        $content .= '<BR/>联系人: ' . $info['petname'];
        $content .= '<BR/>联系电话: ' . $info['iphone'];
        $content .= '<BR/>商户来源: pc';
        $emailstr = C('tmzf_apply_mailto');
        if (is_array($emailstr)) {
            foreach ($emailstr as $value) {
                $arr = array(
                        'subject' => $this->nodeInfo['node_name'] . '提交条码支付申请',
                        'content' => $content,
                        'email'   => $value
                );
                send_mail($arr);
            }
        } else {

            $arr = array(
                    'subject' => $this->nodeInfo['node_name'] . '提交条码支付申请',
                    'content' => $content,
                    'email'   => $emailstr
            );
            send_mail($arr);
        }
    }

    function senemail()
    {
        $where   = array(
                'jk_type'   => 0,
                'node_id'   => $this->node_id,
                'ym_status' => 0
        );
        $terInfo = M('twc_interface')->where($where)->order('id desc')->find();
        $data    = I();
        if ($data['type'] == 1) {
            if (empty($terInfo)) {
                $array = array(
                        'node_id'        => $this->node_id,
                        'jk_type'        => 0,
                        'ym_status'      => 0,
                        'servicer'       => $data['contact_name'],
                        'servicernumber' => $data['contact_phone'],
                        'skill'          => $data['contact_name'],
                        'skillnumber'    => $data['contact_phone'],
                        'allege'         => '',
                        'add_time'       => date('YmdHis')
                );
                M('twc_interface')->add($array);
            } else {
                $array = array(
                        'servicer'       => $data['contact_name'],
                        'servicernumber' => $data['contact_phone'],
                        'skill'          => $data['contact_name'],
                        'skillnumber'    => $data['contact_phone'],
                        'add_time'       => date('YmdHis')
                );
                M('twc_interface')->where($where)->save($array);
            }
        }
        // 发邮件
        $nodeInfo = get_node_info($this->node_id);
        $content  = '翼码运营，你好！ 有新的商户申请开通条码支付了，申请信息如下：';
        $content .= '<BR/>商户名称: ' . $nodeInfo['node_name'];
        $content .= '<BR/>旺号: ' . $nodeInfo['client_id'];
        $content .= '<BR/>联系人: ' . $data['contact_name'];
        $content .= '<BR/>联系电话: ' . $data['contact_phone'];
        if (!empty($data['for_mail_type'])) {
            $content .= '<BR/>开通支付类型:  QQ支付';
        } else {
            if (!empty($data['zfb_account'])) {
                $content .= '<BR/>开通支付类型:  翼支付';
            } else {
                $content .= '<BR/>开通支付类型: ' . (!empty($data['appid'])) ? '微信' : '支付宝';
            }
        }
        $_str = '';
        if (!empty($data['zfb_account'])) {
            $_str .= '<BR/>account:' . $data['zfb_account'];
        }
        if (!empty($data['pid'])) {
            $_str .= '<BR/>pid:' . $data['pid'];
        }
        if (!empty($data['key'])) {
            $_str .= '<BR/>key:' . $data['key'];
        }
        if (!empty($data['appid'])) {
            $_str .= 'appid:' . $data['appid'];
        }
        $content .= '<BR/> 开通参数: ' . $_str . '<BR/> 开通方式: ';
        $content .= ($data['type'] == 0) ? '普通接入' : '收银接入';
        $content .= "<BR/>请尽快完成开通！";
        $arr = array(
                'subject' => '商户' . $nodeInfo['node_name'] . '申请开通条码支付了',
                'content' => $content,
                'email'   => 'order@imageco.com.cn'
        );
        send_mail($arr);
    }

    function alipyepos()
    {
        $rs = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => 0
                )
        )->save(
                array(
                        'link_type'   => $_REQUEST['type'],
                        'status'      => 3,
                        'account_pid' => I('pid'),
                        'account_key' => I('key')
                )
        );
        if ($rs) {
            $this->senemail();
            $this->success(1);
        } else {
            $rs = M('tzfb_offline_pay_info')->where(
                    array(
                            'status'      => 3,
                            'account_pid' => I('pid'),
                            'account_key' => I('key'),
                            'node_id'     => $this->node_id,
                            'pay_type'    => 0
                    )
            )->count();
            if ($rs) {
                $this->senemail();
                $this->success(1);
            } else {
                $this->error('申请失败，请重新提交');
            }
        }
    }

    function yizfepos()
    {
        $rs = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => 2
                )
        )->save(
                array(
                        'zfb_account' => I('zfb_account'),
                        'link_type'   => $_REQUEST['type'],
                        'status'      => 3,
                        'account_pid' => I('pid'),
                        'account_key' => I('key')
                )
        );
        if ($rs) {
            $this->senemail();
            $this->success(1);
        } else {
            $rs = M('tzfb_offline_pay_info')->where(
                    array(
                            'status'      => 3,
                            'account_key' => I('key'),
                            'zfb_account' => I('zfb_account'),
                            'account_pid' => I('pid'),
                            'node_id'     => $this->node_id,
                            'pay_type'    => 2
                    )
            )->count();
            if ($rs) {
                $this->senemail();
                $this->success(1);
            } else {
                $this->error('申请失败，请重新提交');
            }
        }
    }

    function wxepos()
    {
        $rs = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => 1
                )
        )->save(
                array(
                        'link_type'   => $_REQUEST['type'],
                        'status'      => 3,
                        'account_pid' => I('appid')
                )
        );
        if ($rs) {
            $this->senemail();
            $this->success(1);
        } else {
            $rs = M('tzfb_offline_pay_info')->where(
                    array(
                            'status'      => 3,
                            'account_pid' => I('appid'),
                            'node_id'     => $this->node_id,
                            'pay_type'    => 1
                    )
            )->count();
            if ($rs) {
                $this->senemail();
                $this->success(1);
            } else {
                $this->error('申请失败，请重新提交');
            }
        }
    }

    /* 翼支付配置 */
    function info_yizf()
    {
        $list = $this->getPayInfoListWithPagerAndMap(2, 52);

        $map    = ['node_id' => $this->nodeId, 'pay_type' => 2,];
        $yiFlag = M('tzfb_offline_pay_info')->where($map)->find();
        $this->assign('yiflag', $yiFlag);
        $this->assign('list', $list);
        $this->display();
    }

    public function info_yizf_details()
    {
        $id   = I('id');
        $list = M('tzfb_offline_pay_info')->where(array('id' => $id))->find();
        $city = M('tcity_code')->field('province,city')->where(
                array('province_code' => $list['province'], 'city_code' => $list['city'])
        )->find();

        $arr                           = json_decode($list['apply_data'], true);
        $arr['node_name']              = $list['node_name'];
        $arr['bank_city']              = $city['province'] . '-' . $city['city'];
        $arr['shop_type']              = $list['shop_type'];
        $arr['bank_address']           = $list['address'];
        $arr['contact_position']       = $list['contact_position'];
        $arr['contact_name']           = $list['contact_name'];
        $arr['contact_phone']          = $list['contact_phone'];
        $arr['node_mail']              = $list['node_mail'];
        $arr['business_date']['0']     = dateformat($arr['business_date']['0'], 'Y-m-d');
        $arr['business_date']['1']     = dateformat($arr['business_date']['1'], 'Y-m-d');
        $arr['organization_date']['0'] = dateformat($arr['organization_date']['0'], 'Y-m-d');
        $arr['organization_date']['1'] = dateformat($arr['organization_date']['0'], 'Y-m-d');

        $arr['contact_idnum_date']['0'] = dateformat($arr['contact_idnum_date']['0'], 'Y-m-d');
        $arr['contact_idnum_date']['1'] = dateformat($arr['contact_idnum_date']['1'], 'Y-m-d');

        $this->assign('yizf', $arr);
        $this->display();
    }

    public function uploadFile()
    {
        if ($_GET['act'] == 'upload') {

            $fileName = APP_PATH . 'Upload/' . $_GET['filename'];
            if ($_GET['filename'] == 'xieyi') {
                //如果是下载协议模板
                $fileName = APP_PATH . 'Upload/downTemp/' . $_GET['filename'] . '.docx';
            }

            $name     = $_GET['name'];
            $fileSize = filesize($fileName);
            $baseName = $name . ".docx";

            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . $baseName);
            header('content-length:' . $fileSize);
            readfile($fileName);
        }

    }

    public function editYfSubmit()
    {
        $id = I('post.id');
        // 判断当前商户类型
        $where = ['node_id' => $this->node_id, 'id' => $id];
        $row   = M('tzfb_offline_pay_info')->field('check_status')->where($where)->find();
        if ($row['check_status'] == '1') {
            $this->error('申请已通过，无法编辑申请信息');
        }
        $data = I();
        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['contact_name'] == '') {
            $this->error('姓名不能为空');
        }
        if ($data['contact_phone'] == '') {
            $this->error('联系方式不能为空');
        }
        if ($data['business_img'] == '') {
            $this->error('营业执照影印件不能为空');
        }
        if ($data['organization_img'] == '') {
            $this->error('组织机构代码证不能为空');
        }
        if ($data['kaihuxk_img'] == '') {
            $this->error('开户许可证不能为空');
        }
        if (empty($data['contact_idmun_img1']) || empty($data['contact_idmun_img2'])) {
            $this->error('法人身份证不能为空');
        }
        if ($data['guosuisw_img'] == '') {
            $this->error('国税税务登记证不能为空');
        }
        if ($data['disuisw_img'] == '') {
            $this->error('地税税务登记证不能为空');
        }

        // 上传文件处理
        import('@.ORG.Net.UploadFile');
        $upload           = new UploadFile(); // 实例化上传类
        $upload->savePath = APP_PATH . 'Upload/' . $this->onePayDir;


        $info    = $upload->uploadOne($_FILES['apiclient_cert']); // 调用上传方法生成信息
        $fileWay = $this->onePayDir . $info[0]['savename']; // 获取存放在服务端的文件路径

        $arr_               = array(
                'node_id'       => $this->node_id,
                'node_name'     => $data['node_name'],
                'contact_name'  => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_tel'   => $data['contact_phone'],
            // 'add_time' => date('YmdHis'),
                'check_status'  => '0',
                'status'        => '0'
        );
        $arr_['apply_data'] = json_encode(
                array(
                        'business_img'       => $data['business_img'],
                        'organization_img'   => $data['organization_img'],
                        'kaihuxk_img'        => $data['kaihuxk_img'],
                        'contact_idmun_img1' => $data['contact_idmun_img1'],
                        'contact_idmun_img2' => $data['contact_idmun_img2'],
                        'guosuisw_img'       => $data['guosuisw_img'],
                        'disuisw_img'        => $data['disuisw_img'],
                        'onePayFile'         => $fileWay
                )
        );
        $outcome            = M('tzfb_offline_pay_info')->where($where)->save($arr_);
        if ($outcome) {
            $this->success(1);
        } else {
            $this->error('提交失败！');
        }
    }

    public function addYfSubmit()
    {
        if (I('add_method') == 'sub') {
            $pay_type = '52';
        } else {
            $pay_type = '2';
            $row      = M('tzfb_offline_pay_info')->where(['node_id' => $this->node_id, 'pay_type' => $pay_type])->find(
            );
            if ($row) {
                $this->error('操作非法！！！');
            }
        }

        $data = I();

        // 上传文件处理
        import('@.ORG.Net.UploadFile');
        $upload           = new UploadFile(); // 实例化上传类
        $upload->savePath = APP_PATH . 'Upload/' . $this->onePayDir;


        $info    = $upload->uploadOne($_FILES['apiclient_cert']); // 调用上传方法生成信息
        $fileWay = $this->onePayDir . $info[0]['savename']; // 获取存放在服务端的文件路径


        // 第一部分校验
        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['contact_name'] == '') {
            $this->error('姓名不能为空');
        }
        if ($data['contact_phone'] == '') {
            $this->error('联系方式不能为空');
        }
        if ($data['business_img'] == '') {
            $this->error('营业执照影印件不能为空');
        }
        if ($data['organization_img'] == '') {
            $this->error('组织机构代码证不能为空');
        }
        if ($data['kaihuxk_img'] == '') {
            $this->error('开户许可证不能为空');
        }
        if (empty($data['contact_idmun_img1']) || empty($data['contact_idmun_img2'])) {
            $this->error('法人身份证不能为空');
        }
        if ($data['guosuisw_img'] == '') {
            $this->error('国税税务登记证不能为空');
        }
        if ($data['disuisw_img'] == '') {
            $this->error('地税税务登记证不能为空');
        }
        $arr_               = array(
                'node_id'       => $this->node_id,
                'node_name'     => $data['node_name'],
                'contact_name'  => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'add_time'      => date('YmdHis'),
                'pay_type'      => $pay_type,
                'check_status'  => '0',
                'status'        => '0'
        );
        $arr_['apply_data'] = json_encode(
                array(
                        'business_img'       => $data['business_img'],
                        'organization_img'   => $data['organization_img'],
                        'kaihuxk_img'        => $data['kaihuxk_img'],
                        'contact_idmun_img1' => $data['contact_idmun_img1'],
                        'contact_idmun_img2' => $data['contact_idmun_img2'],
                        'guosuisw_img'       => $data['guosuisw_img'],
                        'disuisw_img'        => $data['disuisw_img'],
                        'onePayFile'         => $fileWay
                )
        );
        //将翼支付业务协议的路径添加进表中
        $outcome = M('tzfb_offline_pay_info')->add($arr_);

        if ($outcome) {
            $this->success(1);
        } else {
            $this->error('提交失败！');
        }
    }

    private function getMainPayInfo($payType)
    {
        return M('tzfb_offline_pay_info')->where(['node_id' => $this->node_id, 'pay_type' => $payType])->find();
    }

    private function getPayInfoListWithPager($map, $mainPayType)
    {
        $currentPage = getCurrentPage();
        if ($currentPage == 1) {
            $pageCount = self::PAGE_COUNT - 1;
        } else {
            $pageCount = self::PAGE_COUNT;
        }

        import('ORG.Util.Page'); // 导入分页类
        $count        = M("tzfb_offline_pay_info")->where($map)->count();
        if (empty($count)) { //没有子帐号
            $payInfoList = [];
        } else {
            $count += 1;
            $Page         = new Page($count, $pageCount);
            $payInfoList = M('tzfb_offline_pay_info')->where($map)->limit($Page->firstRow, $Page->listRows)->select();
            $this->assign('pages', $Page->show());
            if (empty($payInfoList)) {
                $payInfoList = [];
            }
        }

        if ($currentPage == 1) {
            $mainPayInfo = $this->getMainPayInfo($mainPayType);
            $this->listGetStoreCountAndPosCountByPayInfoList($payInfoList, $mainPayInfo);
            $list = [];
            if ($mainPayInfo && $payInfoList) {
                $list[] = $mainPayInfo;
                $list   = array_merge($list, $payInfoList);
            } else if ($mainPayInfo){
                $list[] = $mainPayInfo;
                $Page         = new Page(1, $pageCount);
                $this->assign('pages', $Page->show());
            }
        } else {
            $list = $payInfoList;
        }
        return $list;
    }

    private function listGetStoreCountAndPosCountByPayInfoList(&$payInfoList, &$mainPayInfo = [])
    {
        if ($payInfoList) {
            $payInfoCount = count($payInfoList);
            $sub_shop_sum = 0;
            $sub_ter_sum  = 0;
            for ($i = 0; $i < $payInfoCount; $i++) {
                $sub_store_count         = 0;
                $sub_pos_count           = 0;
                $groupIdList             = [];
                $store_group_id_list_str = $payInfoList[$i]['store_group_id_list'];
                if ($store_group_id_list_str) {
                    $store_group_id_list = explode(',', $store_group_id_list_str);
                    foreach ($store_group_id_list as $index => $group_id) {
                        if ($group_id) {
                            $groupIdList[] = $group_id;
                        }
                    }
                }
                if ($groupIdList) {
                    $sub_store_count = $this->getStoreCount($groupIdList);//子账号门店数
                    $sub_pos_count   = $this->getPosCount($groupIdList);//子账号终端数
                }

                $payInfoList[$i]['shop']      = $sub_store_count;
                $payInfoList[$i]['terminal']  = $sub_pos_count;
                $payInfoList[$i]['open_time'] = dateformat($payInfoList[$i]['open_time'], 'Y-m-d H:i');
                $sub_shop_sum += $sub_store_count;
                $sub_ter_sum += $sub_pos_count;
            }

            if ($mainPayInfo) {
                //主帐号门店数
                $main_sum1 = M()->query(
                        "SELECT COUNT(*) as SUM FROM tstore_info WHERE node_id = '" . $this->node_id . "' AND TYPE NOT IN ('3','4')"
                );
                //主帐号终端数
                $main_terminal1           = M()->query(
                        "SELECT COUNT(pos_id) AS NUM FROM tpos_info WHERE store_id IN( SELECT store_id  FROM tstore_info WHERE node_id = '" . $this->node_id . "' AND TYPE NOT IN ('3','4'))"
                );
                $mainPayInfo['shop']      = $main_sum1[0]['SUM'] - $sub_shop_sum;
                $mainPayInfo['terminal']  = $main_terminal1[0]['NUM'] - $sub_ter_sum;
                $mainPayInfo['open_time'] = dateformat($mainPayInfo['open_time'], 'Y-m-d H:i');
            }
        }
    }


    private function getPayInfoListWithPagerAndMap($mainPayType, $subPayType)
    {
        $accountName               = I('name', '');
        $condition['node_id']      = $this->node_id;
        $condition['check_status'] = '1';
        $condition['sign_status']  = '1';
        $condition['status']       = '1';
        $condition['pay_type']     = $subPayType;
        $condition['_logic']       = 'and';
        if ($accountName) {
            preg_match('|[a-zA-Z0-9]+|', $accountName, $matches);
            if (isset($matches[0]) && $matches[0]) {
                $accountName = $matches[0];
                $this->assign('name', $accountName);
            }
            $condition['zfb_account'] = array('like', '%' . $accountName . '%');
        }
        $map['_complex'] = $condition;
        $payInfoList = $this->getPayInfoListWithPager($map, $mainPayType);

        if ($payInfoList) {
            foreach ($payInfoList as $index => $item) {
                $openTime = isset($item['open_time']) && is_numeric($item['open_time']) ? $item['open_time'] : 0;
                if ($openTime) {
                    $pattern = '|(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})|';
                    preg_match_all($pattern, $openTime, $matches);
                    if (count($matches) == 7) {
                        $i                         = 1;
                        $payInfoList[$index]['open_time'] = sprintf(
                                '%s-%s-%s %s:%s:%s',
                                $matches[$i++][0],
                                $matches[$i++][0],
                                $matches[$i++][0],
                                $matches[$i++][0],
                                $matches[$i++][0],
                                $matches[$i++][0]
                        );
                    }
                }
            }
        }

        return $payInfoList;
    }

    /**
     * 微信申请配置页面
     * 微信支付
     */
    public function info_weixin()
    {
        $this->assign('node_id', $this->node_id);
        $this->assign('node_info', $this->nodeInfo);

        //==========================数据 start==================
        $list = $this->getPayInfoListWithPagerAndMap('1', '51');
        //====================微信数据 end=========================

        /**
         * 查看是否已经绑定微信账号
         */
        $where  = ['node_id' => $this->node_id, 'pay_type' => 1,];
        $wxFlag = M('tzfb_offline_pay_info')->where($where)->find();
        $this->assign('wxflag', $wxFlag);
        $this->assign('list', $list);

        $this->display('info_weixin');
    }


    /**
     * 添加/编辑微信子账户
     */
    public function info_weixin_open()
    {
        $data      = array(
                'm' => I('add_method')
        );
        $id        = I('id');
        $wxInfo    = M('tzfb_offline_pay_info')->where(['id' => $id, 'node_id' => $this->node_id])->find();
        $applyData = json_decode($wxInfo['apply_data'], true);
        $wxInfo    = $wxInfo + $applyData;
        $this->assign('wxInfo', $wxInfo);
        $this->assign('list', $data);
        $this->display();
    }


    /**
     * 添加QQ支付子账户
     */
    public function info_qqzf_open()
    {
        $data      = array(
                'm' => I('add_method')
        );
        $id        = I('id');
        $qqInfo    = M('tzfb_offline_pay_info')->where(['id' => $id, 'node_id' => $this->node_id])->find();
        $applyData = json_decode($qqInfo['apply_data'], true);
        $qqInfo    = $qqInfo + $applyData;
        $this->assign('qqInfo', $qqInfo);
        $this->assign('list', $data);
        $this->display();
    }

    /**
     * 添加翼支付子账户
     * @return [type] [description]
     */
    public function info_yizf_open()
    {
        $data = array(
                'm' => I('add_method')
        );

        $id        = I('id');
        $yizf      = M('tzfb_offline_pay_info')->where(['id' => $id, 'node_id' => $this->node_id])->find();
        $applyData = json_decode($yizf['apply_data'], true);
        $yizf      = $yizf + $applyData;
        $this->assign('yizf', $yizf);

        $this->assign('list', $data);
        $this->display();
    }


    /**
     * 微信待审核页面
     * @return [type] [description]
     */
    public function info_weixin_toOpen()
    {
        $info  = $this->getToOpenList('1,51');
        $count = get_val($info, 'count');
        $list  = get_val($info, 'list');
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    protected function sendMailAfterSetAppId($info, $otherParam)
    {
        // 邮箱发送
        $compayName   = $info['node_name'];
        $contactName  = $info['contact_name'];
        $contactPhone = $info['contact_phone'];
        $payTypeStr   = '';//支付类型 0-支付宝 1-微信 2翼支付4-通联支付5--qq钱包 21-百度糯米 22-美团/点评 50-支付宝子账号 51-微信子账号 52-翼支付子账号 55-QQ钱包子账号
        switch ($info['pay_type']) {
            case 0:
            case 50:
                $payTypeStr = '支付宝';
                break;
            case 1:
            case 51:
                $payTypeStr = '微信支付';
                break;
            case 2:
            case 52:
                $payTypeStr = '翼支付';
                break;
            case 5:
            case 55:
                $payTypeStr = 'QQ钱包';
                break;
        }
        $date     = date('Y-m-d H:i:s');
        $nodeInfo = M('tnode_info')->where(['node_id' => $this->node_id])->field('node_id, client_id')->find();
        $clientId = isset($nodeInfo['client_id']) ? $nodeInfo['client_id'] : '';
        if (empty($clientId)) {
            if ($nodeInfo && isset($nodeInfo['node_id'])) {
                $clientId = 'node_id:' . $nodeInfo['node_id'];
            } else if (isset($info['id'])) {
                $clientId = 'zfb_id:' . $info['id'];
            }
        }
        unset($otherParam['status']);
        $otherParamStr = '';
        foreach ($otherParam as $index => $item) {
            if ($index == 'account_pid') {
                if ($info['pay_type'] == '2' || $info['pay_type'] == '52') {
                    $index = '交易KEY';
                } else {
                    $index = 'appid';
                }
            } else if ($index == 'zfb_account') {
                $index = '商户代码';
            } else if ($index == 'account_key') {
                $index = '数据KEY';
            }
            $otherParamStr .= $index . ':&nbsp;' . $item . '&nbsp;&n bsp;';
        }
        $contents = <<<CONTENT
        "翼码运营，你好！ 有新的商户申请开通多米收单了，申请信息如下：<br>
        日期: {$date}<br>
        商户名称: {$compayName}<br>
        旺号: {$clientId}<br>
        联系人: {$contactName}<br>
        联系电话: {$contactPhone}<br>
        开通支付类型: {$payTypeStr}<br>
        开通参数: {$otherParamStr}<br>
        请尽快完成开通。^_^ <br>
CONTENT;

        $sendMail = [
                'subject' => "“{$compayName}”申请开通多米收单",
                "content" => $contents,
                "email"   => "order@imageco.com.cn"
        ];
        $resp     = send_mail($sendMail);
        if ($resp['sucess']) {
            //$this->ajaxReturn(['status' => 1, 'info' => '已申请'], 'JSON');
        } else {
            file_debug($resp, 'sendmail', 'request.log');
        }
    }

    public function info_yizf_setAppId()
    {
        $id          = I('id');
        $account_pid = I('account_pid');
        $zfb_account = I('zfb_account');
        $account_key = I('account_key');
        $where       = ['id' => $id, 'node_id' => $this->node_id];
        $payInfo     = M('tzfb_offline_pay_info')->where($where)->find();
        if (empty($payInfo)) {
            $data = array(
                    'status' => '0000',
                    'msg'    => 'Add Appid Error'
            );
        } else {
            $data = ['account_pid' => $account_pid, 'zfb_account' => $zfb_account, 'account_key' => $account_key];
            if ($payInfo['pay_type'] == 2) {//主帐户
                $data['status'] = '3';
            } else {
                $data['status']    = '1';
                $data['open_time'] = date('YmdHis');
            }
            $updateResult = M('tzfb_offline_pay_info')->where($where)->save($data);
            if ($updateResult) {
                $this->sendMailAfterSetAppId($payInfo, $data);
                $data = ['status' => '1111', 'msg' => 'Add Appid Success'];

            } else {
                $data = ['status' => '0000', 'msg' => 'Add Appid Error'];
            }
        }
        echo json_encode($data);
    }


    public function info_qqzf_setAppId()
    {
        $id      = I('id');
        $appid   = I('appid');
        $where   = ['id' => $id, 'node_id' => $this->node_id];
        $payInfo = M('tzfb_offline_pay_info')->where($where)->find();
        if (empty($payInfo)) {
            $data = array(
                    'status' => '0000',
                    'msg'    => 'Add Appid Error'
            );
        } else {
            $data = ['account_pid' => $appid];
            if ($payInfo['pay_type'] == 5) {//主帐户
                $data['status'] = '3';
            } else {
                $data['status']    = '1';
                $data['open_time'] = date('YmdHis');
            }
            $updateResult = M('tzfb_offline_pay_info')->where($where)->save($data);
            if ($updateResult) {
                $this->sendMailAfterSetAppId($payInfo, $data);
                $data = ['status' => '1111', 'msg' => 'Add Appid Success'];
            } else {
                $data = ['status' => '0000', 'msg' => 'Add Appid Error'];
            }
        }
        echo json_encode($data);
    }

    public function info_wexin_setAppId()
    {
        $id      = I('id');
        $appid   = I('appid');
        $where   = ['id' => $id, 'node_id' => $this->node_id];
        $payInfo = M('tzfb_offline_pay_info')->where($where)->find();
        if (empty($payInfo)) {
            $data = array(
                    'status' => '0000',
                    'msg'    => 'Add Appid Error'
            );
        } else {
            $data = ['account_pid' => $appid];
            if ($payInfo['pay_type'] == 1) {//主帐户
                $data['status'] = '3';
            } else {
                $data['status']    = '1';
                $data['open_time'] = date('YmdHis');
            }
            $updateResult = M('tzfb_offline_pay_info')->where($where)->save($data);
            if ($updateResult) {
                $this->sendMailAfterSetAppId($payInfo, $data);
                $data = ['status' => '1111', 'msg' => 'Add Appid Success'];
            } else {
                $data = ['status' => '0000', 'msg' => 'Add Appid Error'];
            }
        }
        echo json_encode($data);
    }

    /**
     * 翼支付待审核页面
     * @return [type] [description]
     */
    public function info_yizf_toOpen()
    {
        $info  = $this->getToOpenList('2,52');
        $count = get_val($info, 'count');
        $list  = get_val($info, 'list');
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    private function getToOpenList($payTypeStr)
    {
        $condition['node_id']  = $this->node_id;
        $condition['pay_type'] = array('in', $payTypeStr);
        $condition['status']   = array('neq', '1');
        $list                  = M('tzfb_offline_pay_info')->where($condition)->select();
        $list['add_time']      = dateformat($list['add_time'], 'Y-m-d H:i:s');
        $list['check_time']    = dateformat($list['check_time'], 'Y-m-d H:i:s');
        $count                 = M('tzfb_offline_pay_info')->where($condition)->count();

        for ($i = 0; $i < $count; $i++) {
            $list[$i]['add_time']   = dateformat($list[$i]['add_time'], 'Y-m-d H:i:s');
            $list[$i]['check_time'] = dateformat($list[$i]['check_time'], 'Y-m-d H:i:s');
            $list[$i]['open_time']  = dateformat($list[$i]['open_time'], 'Y-m-d H:i:s');
        }
        return ['list' => $list, 'count' => $count];
    }


    /**
     * QQ支付待审核页面
     */
    public function info_qqzf_toOpen()
    {

        $info  = $this->getToOpenList('5,55');
        $count = get_val($info, 'count');
        $list  = get_val($info, 'list');
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    /**
     * 微信详情页
     */
    public function info_weixin_details()
    {
        $id = I('id');

        $list = M('tzfb_offline_pay_info')->where(array('id' => $id))->find();
        $city = M('tcity_code')->field('province,city')->where(
                array('province_code' => $list['province'], 'city_code' => $list['city'])
        )->find();

        $arr                           = json_decode($list['apply_data'], true);
        $arr['node_name']              = $list['node_name'];
        $arr['bank_city']              = $city['province'] . '-' . $city['city'];
        $arr['shop_type']              = $list['shop_type'];
        $arr['bank_address']           = $list['address'];
        $arr['contact_position']       = $list['contact_position'];
        $arr['contact_name']           = $list['contact_name'];
        $arr['contact_phone']          = $list['contact_phone'];
        $arr['node_mail']              = $list['node_mail'];
        $arr['business_date']['0']     = dateformat($arr['business_date']['0'], 'Y-m-d');
        $arr['business_date']['1']     = dateformat($arr['business_date']['1'], 'Y-m-d');
        $arr['organization_date']['0'] = dateformat($arr['organization_date']['0'], 'Y-m-d');
        $arr['organization_date']['1'] = dateformat($arr['organization_date']['0'], 'Y-m-d');

        $arr['contact_idnum_date']['0'] = dateformat($arr['contact_idnum_date']['0'], 'Y-m-d');
        $arr['contact_idnum_date']['1'] = dateformat($arr['contact_idnum_date']['1'], 'Y-m-d');
        $this->assign('wxInfo', $arr);
        $this->display();
    }


    // 经营类目联动
    public function operate_category()
    {
        $level  = $_REQUEST['level'];
        $field  = $_REQUEST['field'];
        $search = $_REQUEST['search'];
        if (!empty($search)) {
            $where = array(
                    $level => $search
            );
        }
        M('twx_pay_operate_category')->field($field);
        if (!empty($where)) {
            M('twx_pay_operate_category')->where($where);
        }
        $list = M('twx_pay_operate_category')->group($field)->select();
        echo json_encode($list);
    }

    /**
     * 微信申请新增
     */
    public function addWxSubmit()
    {
        $id  = I('post.id');
        $row = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id' => $this->node_id,
                        'id'      => $id
                )
        )->count();
        if ($row > 0) {
            $this->error('操作非法！！！');
        }

        $data = I();
        // 第一部分校验
        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['register_address'] == '') {
            $this->error('注册地址不能为空');
        }
        if ($data['business_license'] == '') {
            $this->error('营业执照注册号不能为空');
        }
        if (empty($data['business_date'][0]) || empty($data['business_date'][1])) {
            $this->error('营业期限不能为空');
        }
        if ($data['business_scope'] == '') {
            $this->error('营业执照范围不能为空');
        }
        if ($data['business_img'] == '') {
            $this->error('营业执照影印件不能为空');
        }
        // 第二部分校验

        if ($data['contact_type'] == '') {
            $this->error('联系人类型不能为空');
        }
        if ($data['contact_name'] == '') {
            $this->error('联系人姓名不能为空');
        }
        if ($data['contact_phone'] == '') {
            $this->error('手机号码不能为空');
        }
        if ($data['contact_email'] == '') {
            $this->error('联系邮箱不能为空');
        }
        if ($data['contact_idnum'] == '') {
            $this->error('身份证号码不能为空');
        }
        if (empty($data['contact_idnum_date'][0]) || empty($data['contact_idnum_date'][1])) {
            $this->error('有效期不能为空');
        }
        if (empty($data['contact_idmun_img1']) || empty($data['contact_idmun_img2'])) {
            $this->error('身份证影印件不能为空');
        }
        // 第三部分校验
        if ($data['short_node_name'] == '') {
            $this->error('商家简称不能为空');
        }
        if ($data['operate_category1'] == '' || $data['operate_category2'] == '' || $data['operate_category3'] == '') {
            $this->error('经营类目不能为空');
        }
        if ($data['description'] == '') {
            $this->error('简述售卖的商品不能为空');
        }
        // 第四部分校验
        if ($data['bank_name'] == '') {
            $this->error('开户名称不能为空');
        }
        if ($data['bank_code'] == '') {
            $this->error('开户银行不能为空');
        }
        if (empty($data['bank_province']) || empty($data['bank_city'])) {
            $this->error('开户银行城市不能为空');
        }
        if ($data['bank_adress'] == '') {
            $this->error('开户支行不能为空');
        }
        if ($data['bank_num'] == '') {
            $this->error('银行账户不能为空');
        }
        if ($data['shop_type'] == 0) {
            if ($data['organization_code'] == '') {
                $this->error('组织机构代码不能为空');
            }
            if ($data['organization_img'] == '') {
                $this->error('组织机构代码证影印件不能为空');
            }
            if (empty($data['organization_date'][0]) || empty($data['organization_date'][1])) {
                $this->error('有效期不能为空');
            }
        } else {
            $data['organization_code'] = '';
        }

        //判断是主帐号还是子账号
        $add_method = I('add_method');
        if ($add_method == 'sub') {
            $pay_type = '51';
        } else {
            $pay_type = '1';
        }

        $node_info           = M('tnode_info')->where(
                array(
                        'node_id' => $this->node_id
                )
        )->find();
        $selectTindustryInfo = M('tindustry_info')->where(
                array(
                        'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                )
        )->getField('industry_name');
        $arr_                = array(
                'node_id'          => $this->node_id,
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['contact_email'],
                'industry'         => $selectTindustryInfo,
                'province'         => $data['bank_province'],
                'city'             => $data['bank_city'],
                'address'          => $data['bank_adress'],
                'contact_name'     => $data['contact_name'],
                'contact_position' => $data['contact_type'],
                'contact_phone'    => $data['contact_phone'],
                'contact_tel'      => $data['contact_phone'],
                'contact_eml'      => $data['contact_email'],
                'zfb_account'      => '0',
                'add_time'         => date('YmdHis'),
                'pay_type'         => $pay_type,
                'check_status'     => '0',
                'status'           => '0',
                'tg_client_str'    => $_REQUEST['fclient_id']
        );
        unset($data['node_name']);
        unset($data['contact_email']);
        unset($data['bank_province']);
        unset($data['bank_city']);
        unset($data['bank_adress']);
        unset($data['contact_name']);
        unset($data['contact_type']);
        unset($data['contact_phone']);
        unset($data['contact_email']);
        unset($data['shop_type']);
        $apply_data         = json_encode($data);
        $arr_['apply_data'] = $apply_data;
        $outcome            = M('tzfb_offline_pay_info')->add($arr_);
        if ($outcome !== false) {
            $this->success(1);
        } else {
            $this->error('提交失败！');
        }
    }

    /**
     * 微信申请编辑
     */
    public function editWxSubmit()
    {
        // 判断当前商户类型
        $id  = I('post.id');
        $row = M('tzfb_offline_pay_info')->field('check_status')->where(
                array(
                        'node_id' => $this->node_id,
                        'id'      => $id
                )
        )->find();
        if ($row['check_status'] == '1') {
            $this->error('申请通过，无法编辑申请信息');
        }
        $data = I();
        // 第一部分校验
        if ($data['node_name'] == '') {
            $this->error('商户名称不能为空');
        }
        if ($data['register_address'] == '') {
            $this->error('注册地址不能为空');
        }
        if ($data['business_license'] == '') {
            $this->error('营业执照注册号不能为空');
        }
        if (empty($data['business_date'][0]) || empty($data['business_date'][1])) {
            $this->error('营业期限不能为空');
        }
        if ($data['business_scope'] == '') {
            $this->error('营业执照范围不能为空');
        }
        if ($data['business_img'] == '') {
            $this->error('营业执照影印件不能为空');
        }
        // 第二部分校验

        if ($data['contact_type'] == '') {
            $this->error('联系人类型不能为空');
        }
        if ($data['contact_name'] == '') {
            $this->error('联系人姓名不能为空');
        }
        if ($data['contact_phone'] == '') {
            $this->error('手机号码不能为空');
        }
        if ($data['contact_email'] == '') {
            $this->error('联系邮箱不能为空');
        }
        if ($data['contact_idnum'] == '') {
            $this->error('身份证号码不能为空');
        }
        if (empty($data['contact_idnum_date'][0]) || empty($data['contact_idnum_date'][1])) {
            $this->error('有效期不能为空');
        }
        if (empty($data['contact_idmun_img1']) || empty($data['contact_idmun_img2'])) {
            $this->error('身份证影印件不能为空');
        }
        // 第三部分校验
        if ($data['short_node_name'] == '') {
            $this->error('商家简称不能为空');
        }
        if ($data['operate_category1'] == '' || $data['operate_category2'] == '' || $data['operate_category3'] == '') {
            $this->error('经营类目不能为空');
        }
        if ($data['description'] == '') {
            $this->error('简述售卖的商品不能为空');
        }
        // 第四部分校验
        if ($data['bank_name'] == '') {
            $this->error('开户名称不能为空');
        }
        if ($data['bank_code'] == '') {
            $this->error('开户银行不能为空');
        }
        if (empty($data['bank_province']) || empty($data['bank_city'])) {
            $this->error('开户银行城市不能为空');
        }
        if ($data['bank_adress'] == '') {
            $this->error('开户支行不能为空');
        }
        if ($data['bank_num'] == '') {
            $this->error('银行账户不能为空');
        }

        if ($data['shop_type'] == 0) {
            if ($data['organization_code'] == '') {
                $this->error('组织机构代码不能为空');
            }
            if ($data['organization_img'] == '') {
                $this->error('组织机构代码证影印件不能为空');
            }
            if (empty($data['organization_date'][0]) || empty($data['organization_date'][1])) {
                $this->error('有效期不能为空');
            }
        } else {
            $data['organization_code'] = '';
        }

        $node_info           = M('tnode_info')->where(
                array(
                        'node_id' => $this->node_id
                )
        )->find();
        $selectTindustryInfo = M('tindustry_info')->where(
                array(
                        'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                )
        )->getField('industry_name');
        $arr_                = array(
                'node_id'          => $this->node_id,
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['contact_email'],
                'industry'         => $selectTindustryInfo,
                'province'         => $data['bank_province'],
                'city'             => $data['bank_city'],
                'address'          => $data['bank_adress'],
                'contact_name'     => $data['contact_name'],
                'contact_position' => $data['contact_type'],
                'contact_phone'    => $data['contact_phone'],
                'contact_tel'      => $data['contact_phone'],
                'contact_eml'      => $data['contact_email'],
                'zfb_account'      => '0',
            // 'add_time' => date('YmdHis'),
            //                'pay_type'         => '1',
                'check_status'     => '0',
                'status'           => '0',
                'tg_client_str'    => $_REQUEST['fclient_id'],
                'shop_type'        => $data['shop_type']
        );
        unset($data['node_name']);
        unset($data['contact_email']);
        unset($data['bank_province']);
        unset($data['bank_city']);
        unset($data['bank_adress']);
        unset($data['contact_name']);
        unset($data['contact_type']);
        unset($data['contact_phone']);
        unset($data['contact_email']);
        unset($data['shop_type']);
        $data['business_date'][0]     = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['business_date'][0]
        );
        $data['business_date'][1]     = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['business_date'][1]
        );
        $data['organization_date'][0] = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['organization_date'][0]
        );
        $data['organization_date'][1] = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['organization_date'][1]
        );

        $data['contact_idnum_date'][0] = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['contact_idnum_date'][0]
        );
        $data['contact_idnum_date'][1] = preg_replace(
                '/(\d{4})-(\d{2})-(\d{2})(.*)/',
                '$1$2$3',
                $data['contact_idnum_date'][1]
        );
        $apply_data                    = json_encode($data);
        $arr_['apply_data']            = $apply_data;
        $outcome                       = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id' => $this->node_id,
                        'id'      => $id
                )
        )->save($arr_);

        if ($outcome !== false) {
            $this->success(1);
        } else {
            $this->error('提交失败！');
        }
    }

    public function myopen()
    {
        if ($this->wc_version == 'v0' || $this->wc_version == 'v0.5') {
            $this->error(0);
        } else {
            $this->success(1);
        }
        // $type = I('type','zfb');
        // $this->assign('type',$type);
        // $this->display();
    }

    // 分级账户
    function outline()
    {
        import('ORG.Util.Page');
        $rs = M('tzfb_offline_pay_info')->field(
                array(
                        'zfb_account'
                )
        )->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => 0
                )
        )->find();
        $this->assign('zfb_account', $rs['zfb_account']);
        $this->assign('change_store_act_pwd', $this->nodeInfo['change_store_act_pwd']);
        $mapcount = M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->where('tg.id =  tr.store_group_id and ti.store_id = tr.store_id')->where(
                array(
                        'tg.node_id'      => $this->node_id,
                        'tg.account_name' => array(
                                array(
                                        'exp',
                                        'is not null'
                                ),
                                array(
                                        'neq',
                                        ''
                                )
                        )
                )
        )->field(
                array(
                        'count(ti.id) num_store,tg.*'
                )
        )->group('tg.id')->count();
        $Page     = new Page($mapcount, 10);
        $list     = M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->where('tg.id =  tr.store_group_id and ti.store_id = tr.store_id')->where(
                array(
                        'tg.node_id'      => $this->node_id,
                        'tg.account_name' => array(
                                array(
                                        'exp',
                                        'is not null'
                                ),
                                array(
                                        'neq',
                                        ''
                                )
                        )
                )
        )->field(
                array(
                        'count(ti.id) num_store,tg.*'
                )
        )->limit($Page->firstRow . ',' . $Page->listRows)->group('tg.id')->select();
        if (empty($list)) {
            $this->display();
        } else {
            $show = $Page->show();
            $this->assign('page', $show);
            $this->assign('list', $list);
            $this->display('outline_index');
        }
    }

    function outline_add()
    {
        $groupid = I('meun_id');
        if (!empty($groupid)) {
            $rowcont = M('tstore_group')->where(
                    array(
                            'id' => $groupid
                    )
            )->find();
            $this->assign('rowcont', $rowcont);
            $this->assign('groupid', $groupid);
        }
        if (empty($this->nodeInfo['change_store_act_pwd'])) {
            $ps = implode('', $_REQUEST['repwd']);
            if (empty($ps)) {
                $this->error('操作有误');
            }
            M('tnode_info')->where(
                    array(
                            'node_id' => $this->node_id
                    )
            )->save(
                    array(
                            'change_store_act_pwd' => md5($_REQUEST['repwd'][0])
                    )
            );
        }
        $this->display();
    }

    // 这个是账户group
    function Outline_group()
    {
        $groupid = I('menu_id');
        M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->field(
                array(
                        'tg.group_name',
                        'tg.id'
                )
        )->where('tg.id =  tr.store_group_id and ti.store_id = tr.store_id')->group('tg.id');
        if (!empty($groupid)) {
            M()->where(
                    '(tg.node_id = ' . $this->node_id . ' and (tg.account_name  is null or tg.account_name = \'\')) or tg.id = ' . $groupid
            );
        } else {
            M()->where(
                    array(
                            'tg.node_id'      => $this->node_id,
                            'tg.account_name' => array(
                                    array(
                                            'exp',
                                            'is null'
                                    ),
                                    array(
                                            'eq',
                                            ''
                                    ),
                                    'or'
                            )
                    )
            );
        }
        $list = M()->select();
        $this->assign('groupid', $groupid);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 设置分组信息
     */
    public function groupSet()
    {
        $main_id = I('userid');
        $ids     = I('ids');

        if (empty($ids)) {
            $ids = [];
        }
        //通知支撑 ===============start=======================
        $params            = [];
        $params['operate'] = 'groupSet';
        $params['userId']  = $main_id;
        $params['idList']  = $ids;
        $behaviorReturn    = BR('UpdateStoreGroup', $params);
        //通知支撑   ===============end=======================

        if (isset($behaviorReturn['status']) && $behaviorReturn['status'] == 1) {
            if (empty($ids)) {
                $idsStr = '';
            } else {
                $idsStr = ',' . implode(',', $ids) . ',';
            }
            $list = M('tzfb_offline_pay_info')->where(array('id' => $main_id))->save(
                    ['store_group_id_list' => $idsStr]
            );
            if ($list !== false) {
                $this->success('设置成功');
            } else {
                log_write(M('tzfb_offline_pay_info')->_sql(), 'ERR', 'tzfb_offline_pay_info');
                $this->error('设置失败');
            }
        } else {
            $msg = isset($behaviorReturn['msg']) ? $behaviorReturn['msg'] : '通知支撑失败';
            $this->error($msg);
        }
    }

    // ==== start
    private function getStoreListByGroupList($groupIdList)
    {
        foreach ($groupIdList as $index => $item) {
            $groupIdList[$index] = (int)$item;
        }
        $sql         = sprintf(
                'SELECT store_id FROM `tgroup_store_relation` a 
                WHERE a.store_group_id in(%s)',
                implode(',', $groupIdList)
        );
        $list        = M()->query($sql);
        $storeIdList = [];
        if (is_array($list)) {
            foreach ($list as $item) {
                $store_id = $item['store_id'];
                if ($store_id) {
                    $storeIdList[] = $store_id;
                }

            }
        }
        return $storeIdList;
    }

    private function getStoreCount($groupIdList)
    {
        $storeIdList = $this->getStoreListByGroupList($groupIdList);
        return count($storeIdList);
    }

    //==== end

    private function getPosListByGroupList($groupIdList)
    {
        foreach ($groupIdList as $index => $item) {
            $groupIdList[$index] = (int)$item;
        }
        $sql       = sprintf(
                'SELECT pos_id FROM `tgroup_store_relation` a 
                LEFT JOIN tpos_info b ON a.`store_id`  = b.`store_id`
                WHERE a.store_group_id in(%s)',
                implode(',', $groupIdList)
        );
        $list      = M()->query($sql);
        $posIdList = [];
        if (is_array($list)) {
            foreach ($list as $item) {
                $pos_id = $item['pos_id'];
                if ($pos_id) {
                    $posIdList[] = $pos_id;
                }
            }
        }
        return $posIdList;
    }

    private function getPosCount($groupIdList)
    {
        $posIdList = $this->getPosListByGroupList($groupIdList);
        return count($posIdList);
    }

    /**
     * 获取分组
     * @return [type] [description]
     */
    public function groupGet()
    {
        $payType   = I('pay_type');
        $currentId = I('userid');
        $node_id   = I('node_id');
        $groupList = M('tstore_group')->field('id,group_name')->where(['node_id' => $node_id])->select();

        //查询已经绑定的分组
        $bindedGroupList      = M('tzfb_offline_pay_info')->where(
                ['node_id' => $node_id, 'pay_type' => $payType]
        )->field('id,store_group_id_list')->select();
        $currentGropList      = [];
        $otherBindedGroupList = [];
        if (is_array($bindedGroupList)) {
            foreach ($bindedGroupList as $tmpGroupList) {
                $id               = get_val($tmpGroupList, 'id');
                $groupStr         = get_val($tmpGroupList, 'store_group_id_list');
                $tmpGroupListInfo = explode(',', $groupStr);
                if ($id == $currentId) {
                    $currentGropList = $tmpGroupListInfo;
                } else {
                    $otherBindedGroupList = array_merge($otherBindedGroupList, $tmpGroupListInfo);
                }
            }
        }

        foreach ($groupList as $index => $item) {
            $groupId = get_val($item, 'id');
            if (in_array($groupId, $otherBindedGroupList, true)) { //被其他帐号绑定过了，不能再选中 直接不显示
                unset($groupList[$index]);
            } else if (in_array($groupId, $currentGropList, true)) { //当前帐号
                $groupList[$index]['selected'] = 1;
            } else {
                $groupList[$index]['selected'] = 0;
            }
        }
        echo json_encode($groupList);

    }

    function addchild()
    {
        if (empty($_POST['pwd'])) {
            $this->error('保护密码不能为空');
        }
        if (empty($_POST['id'])) {
            $this->error('门店分组不能为空');
        }
        if (empty($_POST['account'])) {
            $this->error('收款账户不能为空');
        }
        if (empty($_POST['accountPid'])) {
            $this->error('收款账户Pid不能为空');
        }
        $rs = M('tnode_info')->where(
                array(
                        'node_id'              => $this->node_id,
                        'change_store_act_pwd' => md5($_REQUEST['pwd'])
                )
        )->count();
        if ($rs < 1) {
            $this->error('保护密码输入有误，请重新输入');
        }
        $rs = M('tstore_group')->where(
                array(
                        'node_id' => $this->node_id,
                        'id'      => $_POST['id']
                )
        )->count();
        if ($rs < 1) {
            $this->error('门店分组有误，请重新选择');
        }
        $_url          = C('TiaoMZFfenjizhanghu');
        $SystemID      = C('TiaoMZFfenjizhanghu_SYSTEM_ID'); // 测试
        $TransactionID = date('YmdHis') . rand(100, 1000);
        // 获取所有的pos机
        $list     = M()->table(
                array(
                        'tpos_info'             => 'tp',
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->where('tg.id =  tr.store_group_id and ti.store_id = tr.store_id and tp.store_id = ti.store_id')->where(
                array(
                        'tg.node_id' => $this->node_id,
                        'tg.id'      => $_POST['id']
                )
        )->field('tp.pos_id')->select();
        $DataList = '';
        foreach ($list as $value) {
            $DataList .= $value['pos_id'] . ',' . $_POST['accountPid'] . '|';
        }
        $DataList = trim($DataList, '|');
        if (!empty($DataList)) {
            $data   = '<ModifyMutiSellerIdReq>
                       <SystemID>' . $SystemID . '</SystemID>
                       <TransactionID>' . $TransactionID . '</TransactionID>
                       <DataList>' . $DataList . '</DataList>
                       </ModifyMutiSellerIdReq>';
            $httprs = httpPost($_url, $data);
            import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
            $xml     = new Xml();
            $xml_arr = $xml->parse($httprs);
            if ($xml_arr['PosGroupModifyRes']['Status']['StatusCode'] == '0000') {
                M('tstore_group')->where(
                        array(
                                'node_id' => $this->node_id,
                                'id'      => $_POST['id']
                        )
                )->save(
                        array(
                                'account_name' => $_POST['account'],
                                'account_no'   => $_POST['accountPid']
                        )
                );
                log_write("分级账户操作成功，" . print_r($xml_arr, true));
                $this->success('分级账户操作成功');
            } else {
                log_write("分级账户操作失败，" . print_r($xml_arr, true));
                $this->error('分级账户操作失败，请确认收款账号和PID');
            }
        } else {
            $rs = M('tstore_group')->where(
                    array(
                            'node_id' => $this->node_id,
                            'id'      => $_POST['id']
                    )
            )->save(
                    array(
                            'account_name' => $_POST['account'],
                            'account_no'   => $_POST['accountPid']
                    )
            );
            if ($rs) {
                $this->success('分级账户操作成功');
            } else {
                $this->error('分级账户操作失败，请确认收款账号和PID');
            }
        }
    }

    function AlipayAdvanced()
    {
        import('ORG.Util.Page');
        $name    = I('name');
        $role_id = 200;
        $map     = array(
                'ti.node_id'     => $this->node_id,
            // 'ti.role_id'=>array('not in','1,2'),
                'ti.new_role_id' => $role_id,
                'ti.status'      => 0
        );
        if (!empty($name)) {
            $map['ti.user_name'] = array(
                    'like',
                    "%$name%"
            );
            $this->assign('name', $name);
        }
        $mapcount = M()->table(
                array(
                        'tuser_info' => 'ti'
                )
        )->where($map)->join(
                'tuser_role_power trp on trp.user_id = ti.user_id and trp.user_role_id = 200'
        )-> // ->field(array('ti.id'=>'tid','ti.user_name','ti.true_name','ti.user_id','trp.*'))
        count();
        $Page     = new Page($mapcount, 10);

        $list = M()->table(
                array(
                        'tuser_info' => 'ti'
                )
        )->where($map)->join('tuser_role_power trp on trp.user_id = ti.user_id and trp.user_role_id = 200')->field(
                array(
                        'ti.id'      => 'tid',
                        'ti.user_name',
                        'ti.true_name',
                        'ti.user_id' => 'tiuser_id',
                        'trp.*'
                )
        )->limit($Page->firstRow . ',' . $Page->listRows)->order('ti.id desc')->select();
        // 查询是否有分组
        $rs = M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->where('tg.id =  tr.store_group_id  and ti.store_id = tr.store_id')->where(
                array(
                        'tg.node_id' => $this->node_id
                )
        )->count();
        if (empty($rs)) {
            $this->assign('groupNum', 1);
        } else {
            $this->assign('groupNum', 2);
        }
        foreach ($list as $k => $v) {
            // 数据用，分割
            $arr = explode(",", $v['power_list']);
            if (!empty($arr) && !empty($v['power_list'])) {
                $wh = array(
                        'tg.node_id' => $this->node_id,
                        'tg.id'      => array(
                                'in',
                                $arr
                        )
                );
            } else {
                $wh = array(
                        'tg.node_id' => $this->node_id
                );
            }
            $rs                    = M()->table(
                    array(
                            'tstore_group'          => 'tg',
                            'tstore_info'           => 'ti',
                            'tgroup_store_relation' => 'tr'
                    )
            )->where('tg.id =  tr.store_group_id  and ti.store_id = tr.store_id')->field(
                    array(
                            'count(ti.id)' => 'num_store'
                    )
            )->where($wh)->find();
            $list[$k]['num_store'] = $rs['num_store'];
        }
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('list', $list);
        $this->display();
    }

    function listgroup()
    {
        M()->table(
                array(
                        'tstore_group'          => 'tg',
                        'tstore_info'           => 'ti',
                        'tgroup_store_relation' => 'tr'
                )
        )->field(
                array(
                        'tg.group_name',
                        'tg.id'
                )
        )->where('tg.id =  tr.store_group_id and ti.store_id = tr.store_id')->where(
                array(
                        'tg.node_id' => $this->node_id
                )
        )->group('tg.id');
        $list = M()->select();
        echo json_encode($list);
    }


    // 修改查询权限
    function powerset()
    {
        $role_id = 200;
        $userid  = I('userid');
        $ids     = I('ids');
        $count   = M('tuser_role_power')->where(
                array(
                        'user_role_id' => $role_id,
                        'user_id'      => $userid
                )
        )->count();
        if ($count < 1) {
            $rs = M('tuser_role_power')->add(
                    array(
                            'user_role_id' => $role_id,
                            'user_id'      => $userid,
                            'power_list'   => implode(',', $ids)
                    )
            );
        } else {
            $rs = M('tuser_role_power')->where(
                    array(
                            'user_role_id' => $role_id,
                            'user_id'      => $userid
                    )
            )->save(
                    array(
                            'power_list' => implode(',', $ids)
                    )
            );
        }
        if ($rs) {
            $this->success('查询权限设置成功');
        } else {
            $count = M('tuser_role_power')->where(
                    array(
                            'power_list'   => implode(',', $ids),
                            'user_role_id' => $role_id,
                            'user_id'      => $userid
                    )
            )->count();
            if ($count > 0) {
                $this->success('查询权限设置成功');
            } else {
                $this->success('查询权限设置失败');
            }
        }
    }

    // 微信模板展示
    public function AlipayAdvancedMb()
    {
        $this->display();
    }

    // 微信消息模板状态展示
    public function AlipayAdvancedMb_index()
    {
        $yizfInfo = D('TweixinInfo');

        $status          = $yizfInfo->templateStatue($this->nodeId, 2);
        $info            = $yizfInfo->templateShow($this->node_id, 2);
        $info['send']    = $yizfInfo->templateSendcount($this->node_id, 2);
        $info['channel'] = $yizfInfo->channelCount($this->nodeId, 2);
        $info['welInfo'] = json_decode($info['data_config'], true);

        $this->assign('info', $info);
        $this->assign('status', $status);
        $this->display();
    }

    // 开启关闭微信模板消息
    public function AlipayAdvancedMbAjax()
    {
        $yizfInfo = D('TweixinInfo');
        $status   = I('status', 0);

        $result = $yizfInfo->templateAjax($this->nodeId, 2, $status);

        if ($result) {
            $this->success('变更成功');
        } else {
            $this->error("变更失败");
        }
        exit();
    }

    // 关闭微信模板消息(不应用)
    public function AlipayClosedStatus()
    {
        $yizfInfo = D('TweixinInfo');
        $stauts   = $yizfInfo->templateStatue($this->nodeId, 2);
        if ('0' === $stauts) {
            $yizfInfo->templateStatus($this->nodeId, 2, 1);
        }
    }

    // 微信模板消息设置
    public function AlipayAdvancedMb_Setting()
    {
        $yizfInfo = D('TweixinInfo');

        if (IS_POST) {
            $data = I("post.");

            if (empty($data['id'])) {
                $this->error("模块ID不得为空！");
            }
            if (empty($data['start'])) {
                $this->error("欢迎语不得为空！");
            }
            if (empty($data['end'])) {
                $this->error("结束语不得为空！");
            }
            // if(empty($data['url'])) $this->error("模板链接不得为空！");

            $channelId = preg_replace('/.*id=/', '', $data['url']);

            if ($data['url']) {
                // 绑定到新活动渠道
                $newBindUrl  = D('NewBindChannel');
                $batchNewUrl = $newBindUrl->getNewChannelUrl($this->nodeId, $channelId, '微信模板消息', 8, 82);
                $data['url'] = $batchNewUrl;

                $result = $yizfInfo->templateJson(
                        $this->nodeId,
                        2,
                        $data['id'],
                        $data['start'],
                        $data['end'],
                        $topcolor = '#FF0000',
                        $data['url'],
                        $data['channel_name']
                );
            } else {
                $result = $yizfInfo->templateJson(
                        $this->nodeId,
                        2,
                        $data['id'],
                        $data['start'],
                        $data['end'],
                        $topcolor = '#FF0000'
                );
            }

            if ($result) {
                $this->success('保存成功');
            } else {
                $this->error("保存失败,请修改后再保存");
            }
            exit();
        }

        $info            = $yizfInfo->templateShow($this->node_id, 2);
        $info['welInfo'] = json_decode($info['data_config'], true);
        $this->assign('info', $info);
        $this->display();
    }

    function beforeCheckAuth()
    {
        if (in_array(strtolower(ACTION_NAME), $this->noauth)) {
            $this->_authAccessMap = '*';
        }
    }

    function store_group_name()
    {
        $trade_group = I('trade_group');
        $term        = I('term');
        $grouids     = $this->filtergroud(); // tgroup_store_relation
        if ($trade_group == 1) {

            M('tstore_info')->where(
                    array(
                            'type'    => array(
                                    'not in',
                                    '3,4'
                            ),
                            'node_id' => $this->node_id
                    )
            );
            if (!empty($term)) {
                M('tstore_info')->where(
                        array(
                                'store_name' => array(
                                        'like',
                                        '%' . $term . '%'
                                )
                        )
                );
            }
            if ($grouids != array(
                            '0'
                    )
            ) {
                M('tstore_info')->join(
                        'tgroup_store_relation on tgroup_store_relation.store_id=tstore_info.store_id'
                )->where(
                        array(
                                'tgroup_store_relation.store_group_id' => array(
                                        'in',
                                        $grouids
                                )
                        )
                );
            }
            $rs = M('tstore_info')->field(
                    array(
                            'GROUP_CONCAT(\'"\',store_name,\'"\')' => 'name'
                    )
            )->find();
            echo '[' . $rs['name'] . ']';
        } else if ($trade_group == 2) {
            M('tstore_group')->where(
                    array(
                            'node_id' => $this->node_id
                    )
            );
            if (!empty($term)) {
                M('tstore_group')->where(
                        array(
                                'group_name' => array(
                                        'like',
                                        '%' . $term . '%'
                                )
                        )
                );
            }
            if ($grouids != array(
                            '0'
                    )
            ) {
                M('tstore_group')->where(
                        array(
                                'id' => array(
                                        'in',
                                        $grouids
                                )
                        )
                );
            }
            $rs = M('tstore_group')->field(
                    array(
                            'GROUP_CONCAT(\'"\',group_name,\'"\')' => 'name'
                    )
            )->find();
            echo '[' . $rs['name'] . ']';
        }
    }

    //申请开通卡券和外卖服务  暂时只开发发送信息服务
    public function apply()
    {

        if (IS_POST) {
            $data = I("post.");

            if (empty($data['name'])) {
                $this->error('姓名不得为空');
            }
            if (empty($data['tel'])) {
                $this->error('电话不得为空');
            }
            if (empty($data['title'])) {
                $this->error('业务简述不得为空');
            }

            //发送邮件
            $nodeInfo = get_node_info($this->node_id);
            $contents = "<BR/>申请接入平台：" . $data['platformType'];
            if ($data['isconect'] == 1) {
                $contents .= "<BR/>是否接入过该平台：是";
            } else if ($data['isconect'] == 0) {
                $contents .= "<BR/>是否接入过该平台：否";
            }
            $contents .= "<BR/>业务联系人姓名：" . $data['name'];
            $contents .= "<BR/>业务联系人电话：" . $data['tel'];
            $contents .= "<BR/>业务简述：" . $data['title'];


            if ($_REQUEST['type'] == 'groupGoods') {
                $type = '团购卡券';
            } else if ($_REQUEST['type'] == 'takeOut') {
                $type = '外卖服务';
            }

            $ps   = array(
                    'subject' => '商户' . $nodeInfo['node_name'] . '申请开通' . $type,
                    'content' => $contents,
                    'email'   => self::$applyEmail,
            );
            $resp = send_mail($ps);

            if ($resp['sucess']) {
                $this->ajaxReturn(
                        array(
                                'status' => 1,
                                'info'   => '申请成功'
                        ),
                        'JSON'
                );
            }
        }
    }

    public function getUserAuthority()
    {
        // 支付信息表
        $payInfo = M('tzfb_offline_pay_info')->field('pay_type,check_status,status,open_time,tg_client_id')->where(
                array(
                        'node_id'  => $this->node_id,
                        'pay_type' => array(
                                'in',
                                '0,1,2,5'
                        )
                )
        )->order('id desc')->group('pay_type')->select();

        // 设置默认权限 0未开通 1开通
        $flag_arr = array(
                'zfb_flag'   => '0', // 是否开通 0未开通 1已开通 2停用
                'zfb_check'  => '9', // 是否为审核拒绝 0未审核 1审核通过 2审核拒绝 9 未申请
                'wx_flag'    => '0',
                'wx_check'   => '9',
                'yizf_flag'  => '0',
                'yizf_check' => '9', // 翼支付
                'first_flag' => '1', // 0 首次开通 1 单开通zfb 2 单开通wx 3 zfb,wx都开通
                'qqzf_flag'  => '0', // 翼支付
                'qqzf_check' => '9'
        );
        foreach ($payInfo as $v) {
            // 支付宝
            if ($v['pay_type'] == '0') {
                $flag_arr['zfb_flag']      = $v['status'];
                $flag_arr['zfb_check']     = $v['check_status'];
                $flag_arr['zfb_open_time'] = $v['open_time'];
            }
            // 微信
            if ($v['pay_type'] == '1') {
                $flag_arr['wx_flag']      = $v['status'];
                $flag_arr['wx_check']     = $v['check_status'];
                $flag_arr['wx_open_time'] = $v['open_time'];
            }
            if ($v['pay_type'] == '2') {
                $flag_arr['yizf_flag']      = $v['status'];
                $flag_arr['yizf_check']     = $v['check_status'];
                $flag_arr['yizf_open_time'] = $v['open_time'];
            }
            if ($v['pay_type'] == '5') {
                $flag_arr['qqzf_flag']      = $v['status'];
                $flag_arr['qqzf_check']     = $v['check_status'];
                $flag_arr['qqzf_open_time'] = $v['open_time'];
            }
        }
        // 未开通 | 开通申请中 is true
        if (!in_array(
                        $flag_arr['qqzf_flag'],
                        array(
                                1,
                                2
                        )
                ) && !in_array(
                        $flag_arr['yizf_flag'],
                        array(
                                1,
                                2
                        )
                ) && !in_array(
                        $flag_arr['zfb_flag'],
                        array(
                                1,
                                2
                        )
                ) && !in_array(
                        $flag_arr['wx_flag'],
                        array(
                                1,
                                2
                        )
                )
        ) {
            $flag_arr['first_flag'] = '0';
        }
        //
        //多米收单台权限-是否交服务费
        $saleFlag = M('tnode_info')->where('node_id=' . $this->node_id)->getfield('sale_flag');
        //多米收单台权限-是否为老用户
        $userAuthority = $this->isDuomiOldUser($payInfo);

        if ($saleFlag || $userAuthority) {
            echo 1;
        } else {
            echo 0;
        }
    }

    private function isDuomiOldUser($payInfoList)
    {
        foreach ($payInfoList as $item) {
            if (isset($item['tg_client_id']) && $item['tg_client_id'] == '888888') {
                return 1;
            }
        }
        return 0;
    }



    /**
     * 团购弹窗
     */
    public function checked()
    {
        $check = I('check');
        if($check){
            $whe['task_id'] = '7';
            $whe['task_status'] = '1';
            $whe['node_id'] = $this->node_id;
            M('ttask_progress')->add($whe);
            $this->ajaxReturn(true);
        }
    }

    public function groupOrder()
    {
        //统计明细
        $node_id = $this->node_id;

        $task['task_id'] = '7';
        $task['task_status'] = '1';
        $task['node_id'] = $node_id;
        $res = M('ttask_progress')->where($task)->find();
        $this->assign('groupHave',$res);//团购弹窗

        // 为避免文件冲突,则根据各自的order_id创建目录
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir);
        }

        $groupTo = I('groupTo');//所属团购平台

        if (I('verify') == 1) {
            //如果是从统计跳过来的
            $this->assign('verify', 1);
            $groupTo = 1;
        }
        $shopName   = I('shopName');//商品名称
        $shopStatus = I('shopStatus') ? '1' : '0';//商品状态
        if (I('badd_time')) {
            $I_badd_time = I('badd_time');
            $badd_time = date('YmdHis', strtotime(I('badd_time')));//验证开始时间
        }
        if (I('eadd_time')) {
            $I_eadd_time = I('eadd_time');
            $eadd_time = date('YmdHis', strtotime(I('eadd_time')));//验证结束时间
        }
        if(!I('badd_time') && !I('eadd_time')){
            //默认显示当天的数据
            $badd_time = date('YmdHis',strtotime(date('Y-m-d')));
            $eadd_time = getTime(1);
            $I_badd_time = getTime(0,$badd_time);
            $I_eadd_time = getTime(0,$eadd_time);
        }

        $storeOpt = I('storeName');

        $this->assign('storeOpt_', $storeOpt);
        $this->assign('groupTo_', $groupTo);
        $this->assign('shopName_', $shopName);
        $this->assign('shopStatus_', $shopStatus);
        $this->assign('badd_time_', $I_badd_time);
        $this->assign('eadd_time_', $I_eadd_time);

        if (I('trans_date')) {
            $this->assign('badd_time_', I('trans_date') . ' 00:00:00');
            $this->assign('eadd_time_', I('trans_date') . ' 23:59:59');
            $badd_time = date('YmdHis', strtotime(I('trans_date')));//验证开始时间
            $eadd_time = date('YmdHis', (strtotime(I('trans_date')) + 86399));//验证结束时间
        }


        $storeId = '';
        if ($storeOpt != '0') {
            $storeName = $this->storeInfo[$storeOpt];//门店名称
            $storeId   = $this->GroupShopModel->getStoreInfo(1, $storeName, $node_id);//查门店信息
        }

        import('ORG.Util.Page'); // 导入分页类

        $countArr = $this->GroupShopModel->groupDetail(false, $node_id, $groupTo, $shopName, $shopStatus, $badd_time, $eadd_time, $storeId);
        $count = count($countArr);

        $Page = new Page($count, 10);

        $info = $this->GroupShopModel->groupDetail(true, $node_id, $groupTo, $shopName, $shopStatus, $badd_time, $eadd_time, $storeId, $Page->firstRow, $Page->listRows);

        // 分页显示输出
        $show = $Page->show();

        $info = $this->foreachArr($info,1);

        $countArr = $this->foreachArr($countArr,1);
        session('groupOrder_' . $node_id, $countArr);

        $this->assign('storeInfo', $this->storeInfo);
        $this->assign('page', $show);
        $this->assign('shopStatus', $this->shopStatusArr);
        $this->assign('groupToArr', $this->groupToArr);
        $this->assign('info', $info);

        $this->display();
    }

    public function foreachArr($info,$type = '')
    {
        foreach ($info as $k => &$v) {
            foreach ($v as $kk => $vv) {
                if($type == 1){
                    if ($kk == 'trans_time') {
                        //验证时间
                        $transTime = getTime(0, $vv);
                        $v[$kk]    = $transTime;
                    }
                    if ($kk == 'code_type') {
                        //所属团购平台
                        $v[$kk] = $this->groupToArr[$vv];
                    }
                }elseif($type == 2){
                    if ($kk == 'code_type') {
                        //所属团购平台
                        $v[$kk]  = $this->groupToArr[$vv];
                        $v['to'] = $vv;//附加一个key 页面用
                    }
                }elseif($type == 3){
                    if ($kk == 'trans_date') {
                        //验证时间
                        $transTime = getTime(2, $vv);
                        $v[$kk]    = $transTime;
                    }
                }elseif($type == 4){
                    if ($kk == 'store_id') {
                        //验证门店
                        $storeName = $this->GroupShopModel->getStoreInfo(2, $vv, $this->node_id);
                        $v[$kk]    = $storeName;
                    }
                }
            }
        }
        return $info;
    }

    /**
     * @return array 获取某天的时间 0点到24点
     */
    public function yesterday($sum)
    {
        $yesterday = array();
        $yesterday['dd_time'] = date('Ymd',strtotime('-'.$sum.' day'));
        $badd_time = $yesterday['dd_time'].'000000';
        $eadd_time = $yesterday['dd_time'].'235959';
        $yesterday['badd_time'] = date('Y-m-d H:i:s',strtotime($badd_time));
        $yesterday['eadd_time'] = date('Y-m-d H:i:s',strtotime($eadd_time));
        return $yesterday;
    }

    public function groupShop()
    {
        //按商品统计
        $node_id = $this->node_id;

        $data    = I('post.');
        $groupTo = 1;//所属团购平台 百度糯米

        $shopName   = $data['shopName'];//商品名称
        $shopStatus = $data['shopStatus'] ? '1' : '0';//商品状态

        if ($data['badd_time']) {
            $I_badd_time = $data['badd_time'];
            $badd_time = date('Ymd', strtotime($data['badd_time']));//验证开始时间
        }
        if ($data['eadd_time']) {
            $I_eadd_time = $data['eadd_time'];
            $eadd_time = date('Ymd', strtotime($data['eadd_time']));//验证结束时间
        }

        if(!$data['badd_time'] && !$data['eadd_time']){
            //默认显示昨天的数据
            $yesterday = $this->yesterday('1');

            $I_dd_time = $yesterday['dd_time'];
            $I_badd_time = $yesterday['badd_time'];//昨天0点
            $I_eadd_time = $yesterday['eadd_time'];
            $badd_time = $I_dd_time;
            $eadd_time = '-1';
        }



        $storeOpt = $data['storeName'];

        $this->assign('storeOpt_', $storeOpt);
        $this->assign('shopName_', $shopName);
        $this->assign('shopStatus_', $shopStatus);
        $this->assign('badd_time_', $I_badd_time);
        $this->assign('eadd_time_', $I_eadd_time);
        $this->assign('storeName_', $storeName);

        $storeId = '';
        if ($storeOpt != '0') {
            $storeName = $this->storeInfo[$storeOpt];//门店名称
            $storeId   = $this->GroupShopModel->getStoreInfo(1, $storeName, $node_id);//查门店信息
        }

        import('ORG.Util.Page'); // 导入分页类

        $countArr = $this->GroupShopModel->groupShop(false, $node_id, $groupTo, $shopName, $shopStatus, $badd_time, $eadd_time, $storeId);

        $count = count($countArr);
        $Page = new Page($count, 10);

        $info = $this->GroupShopModel->groupShop(true, $node_id, $groupTo, $shopName, $shopStatus, $badd_time, $eadd_time, $storeId, $Page->firstRow, $Page->listRows);

        $show = $Page->show();


        $info = $this->foreachArr($info,2);
        $countArr = $this->foreachArr($countArr,2);
        session('groupShop_' . $node_id, $countArr);
        $this->assign('storeInfo', $this->storeInfo);
        $this->assign('page', $show);
        $this->assign('shopStatus', $this->shopStatusArr);
        $this->assign('groupToArr', $this->groupToArr);
        $this->assign('info', $info);

        $this->display();
    }

    public function groupDate()
    {
        //按日期统计
        $node_id = $this->node_id;

        $data    = I('post.');
        $groupTo = $data['groupTo']?$data['groupTo']:'1' ;//所属团购平台

        if ($data['badd_time']) {
            $I_badd_time = $data['badd_time'];
            $badd_time = date('Ymd', strtotime($data['badd_time']));//验证开始时间
        }
        if ($data['eadd_time']) {
            $I_eadd_time = $data['eadd_time'];
            $eadd_time = date('Ymd', strtotime($data['eadd_time']));//验证结束时间
        }

        if(!$data['badd_time'] && !$data['eadd_time']){
            //默认显示10天的数据
            $yesterday = $this->yesterday('11');

            $I_dd_time = $yesterday['dd_time'];
            $I_badd_time = $yesterday['badd_time'];
            $I_eadd_time = date('Y-m-d H:i:s',strtotime(date('Ymd',time()))-1);//昨晚235959
            $badd_time = $I_dd_time;
            $eadd_time = '-1';

        }


        $storeOpt = $data['storeName'];
        $this->assign('storeOpt_', $storeOpt);
        $this->assign('groupTo_', $groupTo);
        $this->assign('badd_time_', $I_badd_time);
        $this->assign('eadd_time_', $I_eadd_time);
        $this->assign('storeName_', $storeName);

        $storeId = '';
        if ($storeOpt != 0) {
            $storeName = $this->storeInfo[$storeOpt];//门店名称
            $storeId   = $this->GroupShopModel->getStoreInfo(1, $storeName, $node_id);//查门店信息
        }

        import('ORG.Util.Page'); // 导入分页类

        $countArr = $this->GroupShopModel->groupDate(false, $node_id, $groupTo, $badd_time, $eadd_time, $storeId);

        $count = count($countArr);
        $Page = new Page($count, 10);

        $info = $this->GroupShopModel->groupDate(true, $node_id, $groupTo, $badd_time, $eadd_time, $storeId, $Page->firstRow, $Page->listRows);

        $info = $this->foreachArr($info,3);

        $countArr = $this->foreachArr($countArr,3);
        session('groupDate_' . $node_id, $countArr);

        $show = $Page->show();

        $this->assign('storeInfo', $this->storeInfo);
        $this->assign('page', $show);
        $this->assign('shopStatus', $this->shopStatusArr);
        $this->assign('groupToArr', $this->groupToArr);
        $this->assign('info', $info);

        $this->display();
    }

    public function groupStore()
    {
        //按门店统计
        $node_id = $this->node_id;

        $data = I('post.');

        $groupTo = $data['groupTo']?$data['groupTo']:'1';//所属团购平台

        if ($data['badd_time']) {
            $I_badd_time = $data['badd_time'];
            $badd_time = date('Ymd', strtotime($data['badd_time']));//验证开始时间
        }
        if ($data['eadd_time']) {
            $I_eadd_time = $data['eadd_time'];
            $eadd_time = date('Ymd', strtotime($data['eadd_time']));//验证结束时间
        }


        if(!$data['badd_time'] && !$data['eadd_time']){
            //默认显示昨天的数据
            $yesterday = $this->yesterday('1');

            $I_dd_time = $yesterday['dd_time'];
            $I_badd_time = $yesterday['badd_time'];//昨天0点
            $I_eadd_time = $yesterday['eadd_time'];
            $badd_time = $I_dd_time;
            $eadd_time = '-1';
        }



        $storeOpt = $data['storeName'];

        $this->assign('storeOpt_', $storeOpt);
        $this->assign('groupTo_', $groupTo);
        $this->assign('badd_time_', $I_badd_time);
        $this->assign('eadd_time_', $I_eadd_time);

        $storeId = '';
        if ($storeOpt != 0) {
            $storeName = $this->storeInfo[$storeOpt];//门店名称
            $storeId   = $this->GroupShopModel->getStoreInfo(1, $storeName, $node_id);//查门店信息
        }


        import('ORG.Util.Page'); // 导入分页类

        $countArr = $this->GroupShopModel->groupStore(false, $node_id, $groupTo, $badd_time, $eadd_time, $storeId);

        $count = count($countArr);

        $Page = new Page($count, 10);

        $info = $this->GroupShopModel->groupStore(true, $node_id, $groupTo, $badd_time, $eadd_time, $storeId, $Page->firstRow, $Page->listRows);

        $info = $this->foreachArr($info,4);

        foreach($info as $k=>&$v){
            //取门店下标
            $sto = array_intersect($this->storeInfo,$v);
            foreach($sto as $kk=>$vv){
                $v['store_id'] = $kk;
            }
        }
        $show = $Page->show();
        $countArr = $this->foreachArr($countArr,4);
        session('groupStore_' . $node_id, $countArr);

        $this->assign('storeInfo', $this->storeInfo);
        $this->assign('page', $show);
        $this->assign('shopStatus', $this->shopStatusArr);
        $this->assign('groupToArr', $this->groupToArr);
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 验证明细下载
     */
    public function downLoadGroupOrder()
    {
        $node_id      = $this->nodeId;
        $downLoadData = session('groupOrder_' . $node_id);

        $basename = '验证明细.csv';
        $fileName = $this->storageDir . '/1.csv';

        // 若文件存在,则删掉.
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $fp = fopen($fileName, 'a+');

        $line = "券号,商品名称,所属团购平台,验证时间,验证门店";

        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($downLoadData as $k => $v) {

            $line = "\t" . $v['ticket_number'] . "\t" . ',' . "\t" . $v['goods_name'] . ',' . "\t" . $v['code_type'] . ',' . "\t" . $v['trans_time'] . ',' . "\t" . $v['store_name'];

            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);

        $this->down($fileName, $basename);
    }

    /**
     * 按商品统计下载
     */
    public function downLoadGroupShop()
    {
        $node_id      = $this->nodeId;
        $downLoadData = session('groupShop_' . $node_id);

        $basename = '按商品统计.csv';
        $fileName = $this->storageDir . '/2.csv';

        // 若文件存在,则删掉.
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $fp = fopen($fileName, 'a+');

        $line = "商品名称,商品ID,所属平台,验证总数量,可结算总金额";

        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($downLoadData as $k => $v) {
            $amt = $v['amt']? $v['amt'] : '0.00';
            $line = "\t" . $v['goods_name'] . "\t" . ',' . "\t" . $v['goods_id'] . ',' . "\t" . $v['code_type'] . ',' . "\t" . $v['count'] . ',' . "\t" . $amt;

            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);

        $this->down($fileName, $basename);
    }

    /**
     * 按日期统计下载
     */
    public function downLoadGroupDate()
    {
        $node_id      = $this->nodeId;
        $downLoadData = session('groupDate_' . $node_id);

        $basename = '按日期统计.csv';
        $fileName = $this->storageDir . '/3.csv';

        // 若文件存在,则删掉.
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $fp = fopen($fileName, 'a+');

        $line = "日期,验证总数量,可结算总金额";

        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($downLoadData as $k => $v) {
            $amt = $v['amt']? $v['amt'] : '0.00';
            $line = "\t" . $v['trans_date'] . "\t" . ',' . "\t" . $v['cnt'] . ',' . "\t" . $amt;

            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);

        $this->down($fileName, $basename);
    }

    /**
     * 按门店统计下载
     */
    public function downLoadGroupStore()
    {
        $node_id      = $this->nodeId;
        $downLoadData = session('groupStore_' . $node_id);

        $basename = '按门店统计.csv';
        $fileName = $this->storageDir . '/4.csv';

        // 若文件存在,则删掉.
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $fp = fopen($fileName, 'a+');

        $line = "门店名称,验证总数量,可结算总金额";

        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($downLoadData as $k => $v) {
            $amt = $v['amt']? $v['amt'] : '0.00';
            $line = "\t" . $v['store_name'] . "\t" . ',' . "\t" . $v['cnt'] . ',' . "\t" . $amt;

            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);

        $this->down($fileName, $basename);
    }

    public function down($fileName, $basename)
    {
        $fileSize = filesize($fileName);

        header("Content-type: application/octet-stream");
        header(
                "Content-Disposition: attachment; filename=" . iconv('utf-8', 'gb2312', $basename)
        );
        header('content-length:' . $fileSize);
        readfile($fileName);
    }


    /**
     * APP团购收单
     */
    public function wap_GroupShop()
    {
        $version = $this->version;
        $position = $this->position;

        if($version){
            $info = $this->getGroupData();
        }

        $this->assign('position',$position);
        //$this->display();
    }

    /**
     *  我的团购商品
     */
    public function myGroupShop()
    {
        $data = I('post.');

        $info = $this->getGroupData();

        $this->assign('info', $info);
        $this->display();
    }


    /**
     * @return mixed 组装数据 团购平台开通情况
     */
    public function getGroupData()
    {
        //tzfb_offline_pay_info表里的pay_type字段和tgroup_buy_goods_info表里的code_type字段记录的有点不一样
        // 一个1 2 一个21 22
        $node_id = $this->nodeId;
        $nuomi = "百度糯米";
        $meituan = "美团/点评";
        $binding = "已绑定";
        $bindingRemove = "解除绑定";
        $bindingNo = '未绑定';
        $bindingGo = '去绑定';
        $shopManage = "商品管理";

        $result = $this->GroupShopModel->myGroupAccount($node_id, '21');//查糯米
        if ($result) {
            $info[0]['code_type'] = $nuomi;
            $info[0]['status']    = $binding;
            $count                = $this->GroupShopModel->myGroupShop(false, $node_id, '1', '', '');
            $info[0]['count']     = $count ? $count : '0';
            $info[0]['handle']    = $bindingRemove;
            $info[0]['shop']      = $shopManage;
            $info[0]['terrace']   = '1';//1是百度糯米 0是美团点评
            $info[0]['type']      = 0;//1是需要绑定 0是需要解绑
        } else {

            $info[0]['code_type'] = $nuomi;
            $info[0]['status']    = $bindingNo;
            $info[0]['count']     = '/';
            $info[0]['handle']    = $bindingGo;
            $info[0]['terrace']   = '1';
            $info[0]['type']      = 1;
        }

        $result2 = $this->GroupShopModel->myGroupAccount($node_id, '22');//查美团点评
        if ($result2) {
            $info[1]['code_type'] = $meituan;
            $info[1]['status']    = $binding;
            $count                = $this->GroupShopModel->myGroupShop(false, $node_id, '2', '', '');
            $info[1]['count']     = $count ? $count : '0';
            $info[1]['handle']    = $bindingRemove;
            $info[1]['terrace']   = '0';
            $info[1]['type']      = 0;
        } else {
            $info[1]['code_type'] = $meituan;
            $info[1]['status']    = $bindingNo;
            $info[1]['count']     = '/';
            $info[1]['handle']    = $bindingGo;
            $info[1]['terrace']   = '0';
            $info[1]['type']      = 1;
        }

        return $info;
    }

    /**
     * 商品管理
     */
    public function returnShopInfo()
    {
        $node_id = $this->nodeId;
        $data    = I('post.');
        $terrace = $data['terrace'];
        if ($terrace == 0) {
            $terrace = 2;
        }
        $info = $this->GroupShopModel->groupShopData($node_id, $terrace);
        foreach ($info as $k => &$v) {
            if($v['end_time']){
                $v['end_time'] = getTime(0, $v['end_time']);
            }
        }
        $result['list'] = $info;

        $this->ajaxReturn($result);

    }



    /**
     * 调用接口 绑定解绑都走这里
     */
    public function Binding()
    {
        $data = I('post.');

        $node_id = $this->nodeId;
        $email   = $data['email'];
        $type    = $data['type'];//1是绑定 0是解绑
        $terrace = $data['terrace'];//1是百度糯米 0是美团点评
        if ($terrace == 1 && $type == 1) {
            if (!$email) {
                $this->error('非法操作');
            }
        }
        if ($terrace == 1 && $type == 0) {
            $result = $this->GroupShopModel->myGroupAccount($node_id, '21');//查糯米
            $email  = $result['zfb_account'];
        }

        if ($type != 0 && $type != 1) {
            $this->error('非法操作');
        }
        if ($terrace != 0 && $terrace != 1) {
            $this->error('非法操作');
        }

        //$transactionId = get_reqseq();//获取唯一流水号

        $TransactionID = date('YmdHis') . mt_rand(100000, 999999); // 请求流水号

        $ContractNo = $this->GroupShopModel->getNodeInfo($node_id, 'contract_no');

        $data = array(
                'ShopCouponsSellerAccountSetReq' => array(
                        'TransactionID'   => $TransactionID,
                        'ValidContractID' => $ContractNo,
                        'UserEmail'       => $email,
                        'EffectFlag'      => $type,
                        'VerifyType'      => $terrace
                )
        );

        $result = $this->GroupShopModel->groupBinding($data);

        if ($result) {
            if ($type == 1) {
                //拿商户名称
                $node_name = $this->GroupShopModel->getNodeInfo($node_id, 'node_name');
                //添加表数据
                $this->GroupShopModel->bindingEdit($type, $terrace, $node_id, $email, $node_name);
            }
            if ($type == 0) {
                $this->GroupShopModel->bindingEdit($type, $terrace, $node_id, $email);
            }

        }

        $this->ajaxReturn($result);
    }


    /**
     * 页面
     */
    public function myGroupShop_add()
    {
        $this->display();
    }


    /**
     * 我的团购商品 修改
     */
    public function myGroupShopEdit()
    {
        $data = I('post.');
        $this->GroupShopModel->myGroupShopEdit($data);

        $this->ajaxReturn('1');
    }

    /**
     * @return bool 我的团购商品 删除
     */
    public function myGroupShopDelete()
    {
        $node_id = $this->nodeId;
        $id      = I('id');
        if (!$id || !IS_AJAX) {
            return false;
        }
        $this->GroupShopModel->myGroupShopDelete($id);
        $this->ajaxReturn('1');
    }
}