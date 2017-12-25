<?php

class WserviceAction extends BaseAction {

    protected $WcEndTime;

    public function _initialize() {
        parent::_initialize();
        self::initOnlineTreaty();
    }

    /**
     * [buywc 跳转到新的在线签约]
     *
     * @return [type] [description]
     */
    public function buywc() {
        $this->display('basicVersion');
    }

    /* -----------新版在线签约(start)----------- */
    
    /**
     * [initOnlineTreaty 初始化在线签约]
     *
     * @return [type] [NONE]
     */
    private function initOnlineTreaty() {
        $arrAccountInfo = $this->getAccountInfo();
        $this->WcEndTime = $arrAccountInfo['WcEndTime']; // 合同到期时间
        if ($this->WcEndTime < date('Ymd')) {
            $this->WcEndTime = null; // 合同有效期已过，置零
        }
        if (! empty($this->WcEndTime)) {
            $WcUseDate = date('Y-m-d', 
                strtotime($this->WcEndTime . '000000 -1 year +1 day')) . ' 至 ' .
                 date('Y-m-d', strtotime($this->WcEndTime . '000000'));
        } else {
            $WcUseDate = "";
        }
        $this->assign('WcUseDate', $WcUseDate);
    }

    /**
     * [basicVersion 旺财基础平台]
     */
    public function basicVersion() {
        $this->display();
    }

    /**
     * [marketToolVersion 营销工具礼包-最新称谓：多乐互动]
     */
    public function marketToolVersion() {
        $this->error('该功能暂时无法使用');
        $this->assign('hasMarketTool', $this->hasPayModule('m1'));
        $this->display();
    }

    /**
     * [alipayVersion 多米收单]
     */
    public function alipayVersion() {
        $this->assign('hasAlipay', $this->nodeInfo['sale_flag']);
        $this->display();
    }

    /**
     * [ecommerceVersion 电商平台-最新称谓：多宝电商]
     */
    public function ecommerceVersion() {
        $this->assign('hasO2O', $this->hasPayModule('m2'));
        $this->display();
    }

    /**
     * [customizedVersion 高级定制平台]
     */
    public function customizedVersion() {
        $this->display();
    }

    /**
     * [agreementDoc 合同内容]
     */
    public function agreementDoc() {
        /*多乐互动暂时不能签约*/
        $version = I('get.version');
        in_array($version,['1','3','4']) or $this->error("参数有误");

        if($version == '4' && $this->nodeInfo['pay_type'] == '0')
        {
            $this->error('尊敬的用户，系统检测到您是后付费用户，请拨打热线400-882-7770，联系客服协助开通。');
        }
        
        $this->assign('node_name', $this->nodeInfo['node_name']);
        $this->assign('version', $version);
        $this->assign('todayDate', date('YmdHis'));
        $this->assign('hasMarketTool', $this->hasPayModule('m1')?1:0);
        $this->assign('hasO2O', $this->hasPayModule('m2')?1:0);
        $this->assign('hasAlipay', $this->nodeInfo['sale_flag'] ?1:0);
        // 多乐互动与
        $this->display();
    }

    /**
     * [prevPayDetail 支付详情显示]
     */
    public function prevPayDetail() {
        $version = I('get.version');
        in_array($version, ['1','3','4']) or $this->error("参数有误");
        
        if ($version == '2' && $this->hasPayModule('m1')) {
            $this->error('您已经开通了多乐互动礼包！');
        }
        if ($version == '3' && $this->hasPayModule('m2')) {
            $this->error('您已经开通了多宝电商！');
        }
        if ($version == '4' && $this->nodeInfo['sale_flag']) {
            $this->error('您已经开通了多米收单！');
        }
        $arrRes = M('tactivity_pay_config')->getFieldById('6', 'model');
        $priceList = json_decode($arrRes, true); // 签约价格表
        
        $WcEndTime = $this->WcEndTime; // 合同到期时间
        
        $bAccountRet  = self::checkAccountBalance(0, $version, $priceList); // 余额是否足够
        $bAptitudeRet = self::checkStatusFromYz(); // 是否验证资质
        $arrPayInfo   = self::getPayInfo($version, $priceList, $WcEndTime); // 获取支付详情
        
        session('arrPayInfo', $arrPayInfo); // 将支付详情存入session中
        $this->assign('version', $version);
        $this->assign('bAccountRet', $bAccountRet);
        $this->assign('bAptitudeRet', $bAptitudeRet);
        $this->assign('isTreaty', ! empty($WcEndTime));
        $this->assign('arrPayInfo', $arrPayInfo);
        $this->display();
    }

    /**
     * [treatyOrder 生成在线签约订单]
     */
    public function treatyOrder() {
        $arrPayInfo = session('arrPayInfo'); // 从session中获取支付详情
        
        if (empty($arrPayInfo)) {
            $this->error("数据有误，请联系客服！");
        }
        
        $nOrderNumber = date('YmdHis') . mt_rand('100000', '999999'); // 订单号
        
        $arrOrderData = array();
        $arrOrderData['order_number'] = $nOrderNumber;
        $arrOrderData['pay_status'] = 0;
        $arrOrderData['add_time'] = date('YmdHis');
        $arrOrderData['pay_time'] = 0;
        $arrOrderData['node_id'] = $this->nodeId;
        $arrOrderData['order_type'] = 5;
        $arrOrderData['pay_way'] = 1;
        
        if ($arrPayInfo['version'] == '2') // 多乐互动
{
            $arrOrderData['amount'] = $arrPayInfo['marketToolPay'];
            $arrOrderData['detail'] = json_encode($arrPayInfo);
        } elseif ($arrPayInfo['version'] == '3') { // 多宝电商
            $arrOrderData['amount'] = $arrPayInfo['ecommercePay'];
            $arrOrderData['detail'] = json_encode($arrPayInfo);
        } elseif ($arrPayInfo['version'] == '4') { // 多米收单
            $arrOrderData['order_type'] = 6;
            $arrOrderData['amount'] = $arrPayInfo['alipayPay'];
            $arrOrderData['detail'] = json_encode($arrPayInfo);
        }
        $nRet = M('tactivity_order')->add($arrOrderData);
        
        if ($nRet) {
            $map['id'] = array(
                'NEQ', 
                $nRet);
            $map['node_id'] = array(
                'EQ', 
                $this->nodeId);
            $map['pay_status'] = array(
                'EQ', 
                '0');
            $map['order_type'] = array(
                'EQ', 
                $arrOrderData['order_type']);
            M('tactivity_order')->where($map)->delete();
            session('arrPayInfo', null); // 清空支付详情的session
            $this->success("订单生成成功", '', 
                array(
                    'orderId' => $nRet));
        } else {
            $this->error("订单生成失败");
        }
    }

    /**
     * [checkAccountBalance 检查账户余额是否足以满足签约]
     *
     * @param [type] $nAccountType [0，总额；1表示实际余额；2表示旺币余额]
     * @param [type] $version [版本类别，1，2，3,4]
     * @param [type] $priceList [数据库中的价格表]
     * @return [type] [BOOL]
     */
    private function checkAccountBalance($nAccountType, $version, $priceList) {
        in_array($nAccountType,['0','1', '2'])  or die("参数有误");
        in_array($version,['1','2','3','4'])    or die("参数有误");
        
        $AccountInfo = parent::getAccountInfo();
        $realMoney   = $AccountInfo['AccountPrice']; // 金钱余额
        $WbMoney     = $AccountInfo['WbPrice']; // 旺币余额
        
        $nPayPrice = 0;
        switch ($version) {
            case '1':
                $nPayPrice = $priceList['basic']['price'] - $priceList['basic']['discount'];
                break;
            case '2':
                $nPayPrice = $priceList['marketTool']['price'] - $priceList['marketTool']['discount'];
                break;
            case '3':
                $nPayPrice = $priceList['ecommerce']['price'] - $priceList['ecommerce']['discount'];
                break;
            case '4':
                $nPayPrice = $priceList['alipay']['price'] - $priceList['alipay']['discount'];
                break;
            default:
                break;
        }
        
        if ($nAccountType == 0) {
            return ($realMoney + $WbMoney > $nPayPrice) ? true : false;
        } elseif ($nAccountType == 1) {
            return ($realMoney > $nPayPrice) ? true : false;
        } else {
            return ($WbMoney > $nPayPrice) ? true : false;
        }
    }

    /**
     * [checkStatusFromYz 获取资质认证信息]
     *
     * @return [type] [BOOL]
     */
    private function checkStatusFromYz() {
        // 创建接口对象
        $RemoteRequest = D('RemoteRequest', 'Service');
        // 获取商户服务信息
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户服务信息报文参数
        $req_array = array(
            'QueryJsIDReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'TransactionID' => $TransactionID, 
                'ContractID' => $this->contractId));
        $checkStatusInfo = $RemoteRequest->requestYzServ($req_array);
        if (! $checkStatusInfo || ($checkStatusInfo['Status']['StatusCode'] !=
             '0000' && $checkStatusInfo['Status']['StatusCode'] != '0001')) {
            $checkStatusInfo = array();
        }
        if (empty($checkStatusInfo['CertifDate'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * [getPayInfo 支付价格详情]
     *
     * @param [type] $version [版本]
     * @param [type] $priceList [价格表]
     * @param [type] $WcEndTime [合同到期时间]
     * @return [type] [array]
     */
    private function getPayInfo($version, $priceList, $WcEndTime) {
        in_array($version,['1','2','3','4']) or die("参数错误");
        
        $arrPayInfo = array();

        // 如果是多米收单，则单独判断收费信息
        if($version == '4')
        {
            $curRemainDay = date('d', strtotime(date('Ym01') . ' +1 month -1 day'))-date('d')+1;
            $map = array(
                'node_id'      =>$this->nodeId,
                'is_activated' =>'1',
                'pos_status'   =>'0',
                'pay_type'     =>'1',
                );
            $eposCount = M('tpos_info')->where($map)->count();
            if(date('d') == '1')
            {
                $alipayPay = $priceList['alipay']['price']*$eposCount;
            }else{
                $alipayPay = ceil($curRemainDay*$eposCount*$priceList['alipay']['price']/30);
            }
            
            $arrPayInfo = array(
                'version'          => 4,
                'alipayName'       => "多米收单",
                'alipayPrice'      => $priceList['alipay']['price'],
                'alipayRemainDay'  => $curRemainDay,
                'alipayPay'        => $alipayPay,
                'alipayPosCount'   => $eposCount
                );
            $arrPayInfo['setOrderUserName'] = M('tuser_info')->getFieldByUser_id(
            $this->userInfo['user_id'], 'true_name');
            return $arrPayInfo;
        }
        // 如果是多乐互动和多宝电商
        if (empty($WcEndTime)) {
            $WcEndTime = date('Y-m-d', strtotime(date('Ym01') . ' +13 month -1 day'));
            if ($version == 2) {
                $arrPayInfo['version'] = 2;
                $arrPayInfo['hasBuy'] = 0;
                $arrPayInfo['basicName'] = "旺财平台";
                $arrPayInfo['basicPrice'] = $priceList['basic']['price'];
                $arrPayInfo['basicEndTime'] = $WcEndTime;
                $arrPayInfo['basicPay'] = 0;
                $arrPayInfo['marketToolName'] = "多乐互动礼包";
                $arrPayInfo['marketToolPrice'] = $priceList['marketTool']['price'];
                $arrPayInfo['marketToolEndTime'] = $WcEndTime;
                $arrPayInfo['marketToolPay'] = $priceList['marketTool']['price'];
                $arrPayInfo['expiryDate'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000 -1 year +1 day')) . ' 至 ' .
                     date('Y-m-d', strtotime($WcEndTime . '000000'));
            } elseif ($version == 3) {
                $arrPayInfo['version'] = 3;
                $arrPayInfo['hasBuy'] = 0;
                $arrPayInfo['basicName'] = "旺财平台";
                $arrPayInfo['basicPrice'] = $priceList['basic']['price'];
                $arrPayInfo['basicEndTime'] = $WcEndTime;
                $arrPayInfo['basicPay'] = 0;
                $arrPayInfo['ecommerceName'] = "多宝电商";
                $arrPayInfo['ecommercePrice'] = $priceList['ecommerce']['price'];
                $arrPayInfo['ecommerceEndTime'] = $WcEndTime;
                $arrPayInfo['ecommercePay'] = $priceList['ecommerce']['price'];
                $arrPayInfo['expiryDate'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000 -1 year +1 day')) . ' 至 ' .
                     date('Y-m-d', strtotime($WcEndTime . '000000'));
            }
        } else {
            $remainDate = (date('Y', strtotime($WcEndTime . ' +1 day')) -
                 date('Y')) * 12 +
                 (date('m', strtotime($WcEndTime . ' +1 day')) - date('m')) - 1;
            if ($version == 2) {
                $arrPayInfo['version'] = 2;
                $arrPayInfo['hasBuy'] = 1;
                $arrPayInfo['marketToolName'] = "多乐互动礼包";
                $arrPayInfo['marketToolPrice'] = $priceList['marketTool']['price'];
                $arrPayInfo['marketToolRemainTime'] = $remainDate . ' 月';
                $arrPayInfo['marketToolEndTime'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000'));
                $arrPayInfo['marketToolPay'] = ceil(
                    $priceList['marketTool']['price'] * $remainDate / 12);
                $arrPayInfo['expiryDate'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000 -1 year +1 day')) . ' 至 ' .
                     date('Y-m-d', strtotime($WcEndTime . '000000'));
            } elseif ($version == 3) {
                $arrPayInfo['version'] = 3;
                $arrPayInfo['hasBuy'] = 1;
                $arrPayInfo['ecommerceName'] = "多宝电商";
                $arrPayInfo['ecommercePrice'] = $priceList['ecommerce']['price'];
                $arrPayInfo['ecommerceRemainTime'] = $remainDate . ' 月';
                $arrPayInfo['ecommerceEndTime'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000'));
                $arrPayInfo['ecommercePay'] = ceil(
                    $priceList['ecommerce']['price'] * $remainDate / 12);
                $arrPayInfo['expiryDate'] = date('Y-m-d', 
                    strtotime($WcEndTime . '000000 -1 year +1 day')) . ' 至 ' .
                     date('Y-m-d', strtotime($WcEndTime . '000000'));
            }
        }
        $arrPayInfo['setOrderUserName'] = M('tuser_info')->getFieldByUser_id(
            $this->userInfo['user_id'], 'true_name');
        return $arrPayInfo;
    }

    /* -----------新版在线签约(end)----------- */
    public function index() {
        $row = M('tmessage_tmp')->find();
        $this->assign('row', $row);
        $this->display();
    }
    
    // 课程预约
    public function applay() {
        if ($this->isPost()) {
            $need_time = I('need_time');
            $need_time = date('YmdHis', strtotime($need_time));
            
            $query = M('tmessage_apply')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'type' => '1'))->find();
            if ($query)
                $this->error('请勿重复预约!');
            
            $array = array(
                'node_id' => $this->node_id, 
                'need_time' => $need_time, 
                'add_time' => date('YmdHis'), 
                'type' => '1', 
                'status' => '1');
            $query = M('tmessage_apply')->add($array);
            if ($query) {
                $node_arr = M('tnode_info')->where(
                    "node_id='" . $this->node_id . "'")->find();
                $content = "商户名：" . $node_arr['node_name'] . '<br />商户号：' .
                     $node_arr['node_id'] . '<br />联系邮箱：' .
                     $node_arr['contact_eml'] . '<br />联系手机：' .
                     $node_arr['contact_phone'] . '<br />预约日期：' .
                     date('Y-m-d', strtotime($need_time));
                $ps = array(
                    "subject" => "张波课程预约提醒", 
                    "content" => $content, 
                    "email" => "bp@imageco.com.cn");
                send_mail($ps);
                $this->success(
                    '预约成功,我们的市场人员将会与您联系!<br />邮箱：bp@imageco.com.cn<br />电话：021-51970529<br />微信号：O2OPark');
            } else
                $this->error('系统错误!');
        }
    }
    
    // 实操培训
    public function training() {
        if ($this->isPost()) {
            
            $query = M('tmessage_apply')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'type' => '2'))->find();
            if ($query)
                $this->error('请勿重复报名!');
            
            $qq = I('qq');
            $qq_arr = array_filter($qq);
            if (empty($qq_arr))
                $this->error('请填写qq号码！');
            $qq_str = implode('|', $qq_arr);
            $array = array(
                'node_id' => $this->node_id, 
                'type' => '2', 
                'qq' => $qq_str, 
                'add_time' => date('YmdHis'));
            $query = M('tmessage_apply')->add($array);
            if ($query) {
                $node_arr = M('tnode_info')->where(
                    "node_id='" . $this->node_id . "'")->find();
                $content = "商户名：" . $node_arr['node_name'] . '<br />商户号：' .
                     $node_arr['node_id'] . '<br />联系邮箱：' .
                     $node_arr['contact_eml'] . '<br />联系手机：' .
                     $node_arr['contact_phone'] . '<br />QQ：' . $qq_str;
                $ps = array(
                    "subject" => "旺财周四培训报名提醒", 
                    "content" => $content, 
                    "email" => "xufm@imageco.com.cn"); // xufm
                
                send_mail($ps);
                $this->success('报名成功，您将在24小时内收到好友添加消息，记得登录QQ，查看消息！');
            } else
                $this->error('系统错误!');
        }
        
        $this->dislay();
    }
    
    // 上传文件接收
    Public function refile() {
        $wserver_id = I('wserver_id', 'null', 'trim');
        $suit_id = I('suit_id', 'null', 'trim');
        $wphone = I('wphone', 'null', 'trim');
        if ($wphone == "" || $suit_id == "" || $wserver_id == "") {
            exit(
                json_encode(
                    array(
                        'info' => '参数错误！', 
                        'status' => 0)));
        }
        if ($wserver_id == "5") {
            // 验证用户是否为旺财电商用户
            if ($this->wc_version == 'v4' || $this->hasPayModule('m2')) {
                exit(
                    json_encode(
                        array(
                            'info' => '对不起，您暂时无法提交需求，如有疑问，请联系客服电话：400-8827770，谢谢配合！', 
                            'status' => 0)));
            }
        }
        // $wserver_id不为1和5，判断是否为标准版旺财小店
        if ($wserver_id != "1" && $wserver_id != "5") {
            // 验证用户是否为旺财标准用户
            if ($this->wc_version == 'v4' || $this->hasPayModule('m1')) {
                exit(
                    json_encode(
                        array(
                            'info' => '对不起，您暂时无法提交需求，如有疑问，请联系客服电话：400-8827770，谢谢配合！', 
                            'status' => 0)));
            }
        }
        // 判断用户上传次数，如果有10次未处理，将提示用户无法上传
        $model = M("twserver_apply");
        $count = $model->where(
            array(
                "node_id" => $this->node_id, 
                "status" => 0))->count();
        if ($count > 10) {
            exit(
                json_encode(
                    array(
                        'info' => '您提交次数已经超过10次，请等待审核！', 
                        'status' => 0)));
        }
        // 判断文件是否为空
        if (! empty($_FILES['file']['tmp_name'])) {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 5242880; // 设置附件上传大小
            $upload->allowExts = array(
                'zip', 
                'rar'); // 设置附件上传类型
            $upload->savePath = APP_PATH . 'Upload/UploadWserver/' .
                 $this->node_id . '/'; // 设置附件上传目录
            if (! $upload->upload()) { // 上传错误提示错误信息
                exit(
                    json_encode(
                        array(
                            'info' => $upload->getErrorMsg(), 
                            'status' => 0)));
            } else {
                $uploadList = $upload->getUploadFileInfo();
                $date = array(
                    'node_id' => $this->node_id, 
                    'user_id' => $this->user_id, 
                    'wserver_id' => $wserver_id, 
                    'suit_id' => $suit_id, 
                    'att_path' => $uploadList[0]['savename'], 
                    'link_man' => $this->user_name, 
                    'link_phone' => $wphone, 
                    'add_time' => date('YmdHis', time()));
            }
        } else {
            $date = array(
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'wserver_id' => $wserver_id, 
                'suit_id' => $suit_id, 
                'link_man' => $this->user_name, 
                'link_phone' => $wphone, 
                'add_time' => date('YmdHis', time()));
        }
        $res = $model->add($date);
        if ($res) {
            exit(
                json_encode(
                    array(
                        'info' => '提交需求成功！', 
                        'status' => 1, 
                        'return_url' => U('Home/Wservice/wsuccess/', 
                            array(
                                'addtime' => $date['add_time'])))));
        } else {
            exit(
                json_encode(
                    array(
                        'info' => '数据插入失败！', 
                        'status' => 0)));
        }
    }
    // 酷炫电子海报显示
    public function windex() {
        $weserver_id = I('wserver_id');
        $count = M('twserver')->count();
        $where = array();
        if ($weserver_id > $count) {
            $this->error('非法操作！');
        }
        if (! $weserver_id) {
            // $where="wserver_id=1 and status=0";
            $where = array(
                "wserver_id" => 1, 
                "status" => 0);
        } else {
            $where = array(
                "wserver_id" => $weserver_id, 
                "status" => 0);
        }
        $model = M("twserver_suit");
        $list = $model->where($where)->select();
        $list1 = M('twserver')->select();
        $this->assign('list1', $list1);
        $this->assign('list', $list);
        $this->display();
    }
    // 申请成功跳转页面
    public function wsuccess() {
        $addtime = I('addtime');
        if ($addtime == '') {
            $this->error('参数错误！');
        }
        $model = M("twserver_apply");
        $id = $model->where(array(
            "add_time" => $addtime))->getField('id');
        $this->assign('id', $id);
        $this->display("wsuccess");
    }
}
