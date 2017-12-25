<?php

class WheelModel extends BaseModel {
    protected $tableName = '__NONE__';
    public function __construct() {
        parent::__construct();
        import("@.Vendor.CommonConst");
    }

    /**
     * [sendVerifyCode 向手机发送验证码]
     *
     * @param [int] $bindPhoneNo [手机号码]
     * @param [int] $expiresTime [有效时间]
     * @param [string] $text [短信文本]
     * @param integer $codeNum [验证码位数]
     * @return [int] [0] 短信文本例子:您的动态密码是：verifyCode，有效期expiresTime秒。如非本人操作请忽略。
     */
    public function sendVerifyCode($bindPhoneNo, $expiresTime, $text, 
        $codeNum = 4) {
        if (! check_str($bindPhoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            throw_exception("手机号{$error}");
        }
        // 测试环境不下发，验证码直接为111111
        /*
         * $verifyCode = array('number' => 111111, 'add_time' => time(),
         * 'bindPhoneNo' => $bindPhoneNo); session('verifyCode', $verifyCode);
         * return 0;
         */
        // 发送频率验证
        $verifyCode = session('verifyCode');
        if (! empty($verifyCode) &&
             (time() - $verifyCode['add_time']) < $expiresTime) {
            throw_exception('动态密码发送过于频繁!');
        }
        if ($codeNum <= 0) {
            throw_exception("验证码系统设置错误");
        }
        $first = "1";
        $last = "9";
        for ($i = 0; $i < $codeNum - 1; $i ++) {
            $first .= "0";
            $last .= "9";
        }
        $num = mt_rand($first, $last);
        $text = str_replace('verifyCode', $num, $text);
        $text = str_replace('expiresTime', $expiresTime, $text);
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $reqToService = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $bindPhoneNo),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $respData = $RemoteRequest->requestIssServ($reqToService);
        $respMessage = $respData['NotifyRes']['Status'];
        if (! $respData || ($respMessage['StatusCode'] != '0000' &&
             $respMessage['StatusCode'] != '0001')) {
            throw_exception('发送失败' . $respMessage['StatusText']);
        } else {
            $verifyCode = array(
                'number' => $num, 
                'add_time' => time(), 
                'bindPhoneNo' => $bindPhoneNo);
            session('verifyCode', $verifyCode);
            return 0;
        }
    }

    /**
     * [getUserType 获取手机号码是否存在于旺财平台]
     *
     * @param [type] $bindPhoneNo [手机号码]
     * @return [type] [integer] 返回１，表示直接去ＰＣ端 返回２，提示已经有活动，未过期 返回３，提示活动已过期
     *         返回４，表示正常，可以在本地创建活动
     */
    public function getUserType($bindPhoneNo, $verifyCode) {
        $wheelInfo = self::getWheelInfo($bindPhoneNo);
        // 验证手机号码的正确性
        if (! check_str($bindPhoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            throw_exception("手机号{$error}");
        }
        // 根据手机号码查询机构信息
        $nodeInfo = M('tnode_info')->where(
            array(
                'contact_phone' => $bindPhoneNo))->select();
        // 存在此手机号码相关的机构
        if (! empty($nodeInfo)) {
            return 1;
        } else {
            // 没有此用户，在临时表中创建该用户,若临时表有该手机号，则更新数据
            $userTmpInfo = M('twheel_tmp')->where(
                array(
                    'phone_no' => $bindPhoneNo))->select();
            if (empty($userTmpInfo)) {
                if (! M('twheel_tmp')->add(
                    array(
                        'phone_no' => $bindPhoneNo, 
                        'tmp_password' => $verifyCode, 
                        'add_time' => date('YmdHis')))) {
                    $throw_exception("未知错误，请与客服联系");
                }
            } else {
                if (false === M('twheel_tmp')->where(
                    array(
                        'phone_no' => $bindPhoneNo))->save(
                    array(
                        'tmp_password' => $verifyCode))) {
                    $throw_exception("未知错误，请与客服联系");
                }
            }
            // 直接创建活动
            if (empty($wheelInfo['is_order'])) {
                return 4;
            } else {
                return 1;
            }
        }
    }

    /**
     * [getValidityPeriod 获取相关有效期的信息]
     *
     * @return [type] [description]
     */
    public function getValidityPeriod($wheelInfo) {
        $eventData = json_decode($wheelInfo['event_data'], true);
        $ticketData = json_decode($wheelInfo['ticket_data'], true);
        $tmpArr = array(
            'batchEndTime' => $eventData['endTime'], 
            'prizeEndTime' => $ticketData['endTime'], 
            'eposEndTime' => $ticketData['endTime']);
        return $tmpArr;
    }

    /**
     * [checkCreateEvent 检查表单提交的数据]
     *
     * @param [type] $data [表单数据]
     * @return [type] [返回数组]
     */
    public function checkCreateEvent($data) {
        if (! check_str($data['name'], 
            array(
                'null' => false, 
                'maxlen_cn' => '15'), $error)) {
            throw_exception("举办方名称{$error}");
        }
        // 查询举办方是否重复
        $nodeName = M('tnode_info')->where(
            array(
                'node_name' => $data['name']))->select();
        if (! empty($nodeName)) {
            throw_exception("举办方名称已存在");
        }
        if (! check_str($data['batchName'], 
            array(
                'null' => false, 
                'maxlen_cn' => '15'), $error)) {
            throw_exception("活动名称{$error}");
        }
        if (! check_str($data['startTime'], 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Y-m-d'), $error)) {
            throw_exception("活动开始时间{$error}");
        }
        // 开始时间必须是当天
        if ($data['startTime'] != date('Y-m-d')) {
            throw_exception("活动开始时间必须为今天");
        }
        if (! check_str($data['endTime'], 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Y-m-d'), $error)) {
            throw_exception("活动结束时间{$error}");
        }
        // 活动结束时间不能大于开始时间的45天
        if ((strtotime($data['endTime']) - strtotime($data['startTime'])) >
             45 * 24 * 60 * 60) {
            throw_exception("活动结束时间必须在45天以内");
        }
        if (! check_str($data['serveTelphone'], 
            array(
                'null' => false), $error)) {
            throw_exception("服务热线{$error}");
        }
        if (! check_str($data['batchIntroduce'], 
            array(
                'null' => false), $error)) {
            throw_exception("活动说明{$error}");
        }
        if (strtotime($data['startTime']) > strtotime($data['endTime']) ||
             strtotime($data['endTime']) < time()) {
            throw_exception("活动时间设置有误");
        }
        if (! empty($data['logo'])) {
            $pic = $data['logo'];
            if ($pic['error'] !== 0) {
                throw_exception("图片上传失败，请重试！");
            }
            if ($pic['size'] === 0) {
                throw_exception("图片无效，请重新选择！");
            }
            if ($pic['size'] > 1024 * 1024 * 3) {
                throw_exception("图片超过3M，请重新选择！");
            }
            $pic_ex = strtolower(
                substr($pic['name'], strripos($pic['name'], '.') + 1));
            if (! in_array($pic_ex, 
                array(
                    'gif', 
                    'jpg', 
                    'jpeg', 
                    'bmp', 
                    'png'), true)) {
                throw_exception("图片格式不对！");
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 1024 * 1024 * 3;
            $upload->savePath = C('UPLOAD') . 'WheelUpload/' . date('Y/m/d/'); // 设置附件
            if (! is_dir(C('UPLOAD') . 'WheelUpload/')) {
                mkdir(C('UPLOAD') . 'WheelUpload/');
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y'));
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y/m'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y/m'));
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y/m/d'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y/m/d'));
            }
            $upload->saveRule = time() . sprintf('%06s', mt_rand(0, 100000));
            $upload->supportMulti = false;
            $upload->allowExts = array(
                'gif', 
                'jpg', 
                'jpeg', 
                'bmp', 
                'png');
            
            if (! $upload->upload()) { // 上传错误提示错误信息
                throw_exception("图片上传失败！");
            } else { // 上传成功 获取上传文件信息
                $successInfo = $upload->getUploadFileInfo();
                $imgSavePath = C('TMPL_PARSE_STRING.__URL_UPLOAD__') .
                     '/WheelUpload/' . date('Y/m/d/') .
                     $successInfo[0]['savename'];
                $data['logoUrl'] = $imgSavePath;
                unset($data['logo']);
                unset($data['haslogo']);
                return $data;
            }
        } else {
            // 已经有的logo的不用再上传，直接存进去
            if (! empty($data['haslogo'])) {
                $data['logoUrl'] = $data['haslogo'];
                unset($data['logo']);
                unset($data['haslogo']);
                return $data;
            } else {
                $data['logoUrl'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     "/Label/Image/Item/dzp/dzp_logo.png";
                unset($data['logo']);
                return $data;
            }
        }
    }

    /**
     * [createEvent 创建活动]
     *
     * @param [type] $data [数组参数]
     * @return [type] [true]
     */
    public function createEvent($data) {
        $jsonData = json_encode($data);
        $loginSuccess = session('loginSuccess');
        if (false === M('twheel_tmp')->where(
            array(
                'phone_no' => $loginSuccess['bindPhoneNo']))->save(
            array(
                'event_data' => $jsonData))) {
            throw_exception("创建活动失败");
        } else {
            return true;
        }
    }

    /**
     * [getEventData 查询创建的数据]
     *
     * @return [type] [array()]
     */
    public function getEventData() {
        $loginSuccess = session('loginSuccess');
        // 根据手机号码获取创建的数据
        $res = M('twheel_tmp')->getFieldByPhone_no($loginSuccess['bindPhoneNo'], 
            'event_data');
        if (! empty($res)) {
            return json_decode($res, true);
        } else {
            return array();
        }
    }

    /**
     * [hasEvent 判断是否已经创建过活动]
     *
     * @param [type] $data [条件数组参数]
     * @return boolean [没有为0，有的话为数组]
     */
    public function hasEvent($data) {
        $result = M('tmarketing_info')->where($data)->select();
        return empty($result) ? false : $result[0];
    }

    /**
     * [checkCreateTicket 检查创建卡券的各项数据]
     *
     * @param [type] $data [表单提交的数据]
     * @return [type] [数组]
     */
    public function checkCreateTicket($data) {
        if (! check_str($data['name'], 
            array(
                'null' => false, 
                'maxlen_cn' => '15'), $error)) {
            throw_exception("奖品名称{$error}");
        }
        if (! check_str($data['number'], 
            array(
                'null' => false, 
                'strtype' => 'number'), $error)) {
            throw_exception("奖品数量{$error}");
        }
        if ($data['number'] < 1) {
            throw_exception("奖品数量不得小于1");
        }
        if ($data['number'] > 100) {
            throw_exception("奖品数量不得大于100");
        }
        if (! check_str($data['startTime'], 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Y-m-d'), $error)) {
            throw_exception("奖品开始时间{$error}");
        }
        if (! check_str($data['endTime'], 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Y-m-d'), $error)) {
            throw_exception("奖品结束时间{$error}");
        }
        if ($data['startTime'] != date('Y-m-d')) {
            throw_exception("奖品开始时间必须为今天");
        }
        if (strtotime($data['startTime']) > strtotime($data['endTime']) ||
             strtotime($data['endTime']) < time()) {
            throw_exception("奖品有效期设置有误");
        }
        // 奖品结束时间不能大于开始时间的75天
        if ((strtotime($data['endTime']) - strtotime($data['startTime'])) >
             75 * 24 * 60 * 60) {
            throw_exception("奖品结束时间必须在75天以内");
        }
        if (! empty($data['logo'])) {
            $pic = $data['logo'];
            if ($pic['error'] !== 0) {
                throw_exception("图片上传失败，请重试！");
            }
            if ($pic['size'] === 0) {
                throw_exception("图片无效，请重新选择！");
            }
            if ($pic['size'] > 1024 * 1024 * 3) {
                throw_exception("图片超过3M，请重新选择！");
            }
            $pic_ex = strtolower(
                substr($pic['name'], strripos($pic['name'], '.') + 1));
            if (! in_array($pic_ex, 
                array(
                    'gif', 
                    'jpg', 
                    'jpeg', 
                    'bmp', 
                    'png'), true)) {
                throw_exception("图片格式不对！");
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 1024 * 1024 * 3;
            $upload->savePath = C('UPLOAD') . 'WheelUpload/' . date('Y/m/d/'); // 设置附件
            if (! is_dir(C('UPLOAD') . 'WheelUpload/')) {
                mkdir(C('UPLOAD') . 'WheelUpload/');
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y'));
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y/m'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y/m'));
            }
            if (! is_dir(C('UPLOAD') . 'WheelUpload/' . date('Y/m/d'))) {
                mkdir(C('UPLOAD') . 'WheelUpload/' . date('Y/m/d'));
            }
            $upload->saveRule = time() . sprintf('%06s', mt_rand(0, 100000));
            $upload->supportMulti = false;
            $upload->allowExts = array(
                'gif', 
                'jpg', 
                'jpeg', 
                'bmp', 
                'png');
            
            if (! $upload->upload()) { // 上传错误提示错误信息
                throw_exception("图片上传失败！");
            } else { // 上传成功 获取上传文件信息
                $successInfo = $upload->getUploadFileInfo();
                $imgSavePath = C('__UPLOAD__') . '/WheelUpload/' . date(
                    'Y/m/d/') . $successInfo[0]['savename'];
                $data['logoUrl'] = $imgSavePath;
                unset($data['logo']);
                unset($data['haslogo']);
                return $data;
            }
        } else {
            // 已经有的logo的不用再上传，直接存进去
            if (! empty($data['haslogo'])) {
                $data['logoUrl'] = $data['haslogo'];
                unset($data['logo']);
                unset($data['haslogo']);
                return $data;
            } else {
                $data['logoUrl'] = C("__UPLOAD__") . "/upload_img/dzp_logo.png";
                unset($data['logo']);
                return $data;
            }
        }
    }

    /**
     * [createTicket 创建卡券]
     *
     * @param [type] $data [表单审核过的数据]
     * @return [type] [true]
     */
    public function createTicket($data) {
        $jsonData = json_encode($data);
        // 从session获取手机号码
        $loginSuccess = session('loginSuccess');
        if (false === M('twheel_tmp')->where(
            array(
                'phone_no' => $loginSuccess['bindPhoneNo']))->save(
            array(
                'ticket_data' => $jsonData))) {
            throw_exception("创建奖项失败");
        } else {
            return true;
        }
    }

    /**
     * [getTicketData 获取卡券数据]
     *
     * @return [type] [数组]
     */
    public function getTicketData() {
        // 从session获取手机号码
        $loginSuccess = session('loginSuccess');
        // 根据手机号码获取卡券数据
        $res = M('twheel_tmp')->getFieldByPhone_no($loginSuccess['bindPhoneNo'], 
            'ticket_data');
        if (! empty($res)) {
            return json_decode($res, true);
        } else {
            return array();
        }
    }

    /**
     * [checkLuckDraw 检查抽奖的表单提交的数据]
     *
     * @param [type] $data [表单数据]
     * @return [type] [无误的表单数据]
     */
    public function checkLuckDraw($data) {
        // 抽奖概率
        ! empty($data['luckProbability']) or throw_exception("抽奖概率不得为空");
        if ($data['luckProbability'] > 100 || $data['luckProbability'] < 0 ||
             ! is_numeric($data['luckProbability'])) {
            throw_exception("抽奖概率必须为0-100之间的数字");
        }
        // 每日奖品限量
        ! empty($data['dayLimit']) or throw_exception("每日奖品限量不得为空");
        if ($data['dayLimit'] < 1)
            throw_exception("每日奖品限量不得小于1");
        $loginSuccess = session('loginSuccess');
        $ticketData = M('twheel_tmp')->getFieldByPhone_no(
            $loginSuccess['bindPhoneNo'], 'ticket_data');
        $ticketJsonData = json_decode($ticketData, true);
        if ($data['dayLimit'] > $ticketJsonData['number']) {
            throw_exception("每日奖品限量不得大于奖项数量");
        }
        // 中奖彩信标题
        ! empty($data['msgTitle']) or throw_exception("中奖彩信标题不得为空");
        if (mb_strlen($data['msgTitle'], 'UTF-8') > 10)
            throw_exception("中奖彩信标题必须在10个字符以内");
            // 中奖彩信内容
        ! empty($data['msgContent']) or throw_exception("中奖彩信内容不得为空");
        if (mb_strlen($data['msgContent'], 'UTF-8') > 100)
            throw_exception("中奖彩信内容必须在100个字符以内");
        return $data;
    }

    /**
     * [setLuckDraw 插入抽奖数据]
     *
     * @param [type] $data [验证过的抽奖数据]
     */
    public function setLuckDraw($data) {
        $jsonData = json_encode($data);
        $loginSuccess = session('loginSuccess');
        if (false === M('twheel_tmp')->where(
            array(
                'phone_no' => $loginSuccess['bindPhoneNo']))->save(
            array(
                'luckdraw_data' => $jsonData))) {
            throw_exception("设置抽奖失败");
        } else {
            return true;
        }
    }

    /**
     * [getLuckDrawData 获取抽奖临时数据]
     *
     * @return [type] [数组]
     */
    public function getLuckDrawData() {
        // 从session获取手机号码
        $loginSuccess = session('loginSuccess');
        // 根据手机号码获取奖项数据
        $res = M('twheel_tmp')->getFieldByPhone_no($loginSuccess['bindPhoneNo'], 
            'luckdraw_data');
        if (! empty($res)) {
            return json_decode($res, true);
        } else {
            return array();
        }
    }

    /**
     * [createRealData 最终的处理，注册，创建免费活动]
     *
     * @return [type] [description]
     */
    public function createRealData() {
        // 根据手机号码获取所有之前存储的数据
        $loginSuccess = session('loginSuccess');
        $res = M('twheel_tmp')->getByPhone_no($loginSuccess['bindPhoneNo']);
        empty($res) && throw_exception("发布失败");
        empty($res['event_data']) && throw_exception("发布失败");
        empty($res['ticket_data']) && throw_exception("发布失败");
        empty($res['luckdraw_data']) && throw_exception("发布失败");
        $phoneNo = $res['phone_no'];
        $passwd = $res['tmp_password'];
        $eventData = json_decode($res['event_data'], true);
        $ticketData = json_decode($res['ticket_data'], true);
        $luckdrawData = json_decode($res['luckdraw_data'], true);
        
        /**
         * ***用于注册的数据****
         */
        $nodeAddData = array(
            'node_name' => $eventData['name'], 
            'node_short_name' => mb_substr($eventData['name'], 0, 9, 'UTF-8'), 
            'regemail' => $phoneNo . '@7005.com.cn', 
            'contact_name' => $phoneNo, 
            'contact_phone' => $phoneNo, 
            'user_password' => md5(trim($passwd)), 
            'province_code' => '09', 
            'city_code' => '021', 
            'town_code' => "", 
            'client_manager' => "", 
            'tg_id' => $_COOKIE["reg_tgxt_id"], 
            'third_uid' => "", 
            'third_token' => "", 
            'reg_from' => '6', 
            'channel_id' => "", 
            'user_name' => $phoneNo);
        $nodeInfo = M('tnode_info')->where(
            array(
                'contact_eml' => $phoneNo . '@7005.com.cn', 
                'reg_from' => '6'))->select();
        $isRegister = M('twheel_tmp')->getFieldByPhone_no($phoneNo, 
            'is_register');
        // 判断是否注册，如果已经注册了，则跳过注册阶段
        if ($isRegister != '0' || ! empty($nodeInfo)) {
            // 已注册情况，获取用户信息
            $nodeId = $nodeInfo[0]['node_id'];
            $clientId = $nodeInfo[0]['client_id'];
            $userId = M('tuser_info')->getFieldByNode_id($nodeId, 'user_id');
        } else {
            $serviceNodeReg = D('NodeReg', 'Service');
            $resultNodeAdd = $serviceNodeReg->nodeAdd($nodeAddData);
            $resultNodeAdd['status'] == 0 && throw_exception("发布失败(注册01)");
            // 获取用户信息
            $nodeId = $resultNodeAdd['data']['node_id'];
            $clientId = $resultNodeAdd['data']['client_id'];
            $userId = $resultNodeAdd['data']['user_id'];
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $phoneNo))->save(
                array(
                    'is_register' => $nodeId));
        }
        
        // 用于创建活动基本信息的数据
        $marketingData = array(
            'name' => $eventData['batchName'], 
            'node_id' => $nodeId, 
            'node_name' => $eventData['name'], 
            'wap_info' => $eventData['batchIntroduce'], 
            'log_img' => $eventData['logoUrl'], 
            'start_time' => date('YmdHis', strtotime($eventData['startTime'])), 
            'end_time' => date('YmdHis', strtotime($eventData['endTime'])), 
            'sns_type' => "", 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
            'is_show' => '1', 
            'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
            'share_pic' => $eventData['logoUrl'], 
            'pay_status' => 1, 
            // 免费用户初始值填一个0,付费用户不用记录付费状态
            'defined_one_name' => 'wap'); // 表示该活动来自wap端
        
        $marketingInfo = M('tmarketing_info')->where(
            array(
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($marketingInfo)) {
            $marketingData['new_cj_flag'] = '1';//2016-04-19添加，新建活动走新抽奖模式
            $m_id = M('tmarketing_info')->add($marketingData);
            if ($m_id) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_marketing' => $m_id));
            } else {
                throw_exception("发布失败(市场01)");
            }
        } else {
            // 不存在的话，就添加
            $m_id = $marketingInfo[0]['id'];
            M('tmarketing_info')->where(
                array(
                    'id' => $m_id))->save($marketingData);
        }
        
        // 抽奖规则的数据
        $ruleData = array(
            'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
            'batch_id' => $m_id, 
            'jp_set_type' => 1,  // 1单奖品2多奖品 ??????
            'node_id' => $nodeId, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'phone_total_count' => '', 
            'phone_day_count' => $luckdrawData['dayLimit'], 
            'phone_total_part' => '', 
            'phone_day_part' => '', 
            'day_count' => '', 
            'total_chance' => $luckdrawData['luckProbability'], 
            'cj_button_text' => '开始抽奖', 
            'cj_resp_text' => '恭喜您！中奖了',  // 中奖提示信息
            'param1' => '', 
            'no_award_notice' => '很遗憾！未中奖');
        $ruleInfo = M('tcj_rule')->where(
            array(
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($ruleInfo)) {
            $ruleId = M('tcj_rule')->add($ruleData);
            if ($ruleId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_rule' => $ruleId));
            } else {
                throw_exception("发布失败(抽奖01)");
            }
        } else {
            // 不存在的话，就添加
            $ruleId = $ruleInfo[0]['id'];
            M('tcj_rule')->where(array(
                'id' => $ruleId))->save($ruleData);
        }
        
        // 抽奖详情数据
        $cjCateData = array(
            'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
            'batch_id' => $m_id, 
            'node_id' => $nodeId, 
            'cj_rule_id' => $ruleId, 
            'name' => $ticketData['name'], 
            'member_batch_id' => "", 
            'add_time' => date('YmdHis'), 
            'status' => 1, 
            'score' => "0", 
            'min_rank' => "0", 
            'max_rank' => "0", 
            'sort' => "1");
        $cjCateInfo = M('tcj_cate')->where(
            array(
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($cjCateInfo)) {
            $cjCateId = M('tcj_cate')->add($cjCateData);
            if ($cjCateId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_cjcate' => $cjCateId));
            } else {
                throw_exception("发布失败(抽奖详情01)");
            }
        } else {
            // 不存在的话，就添加
            $cjCateId = $cjCateInfo[0]['id'];
            M('tcj_cate')->where(array(
                'id' => $cjCateId))->save($cjCateData);
        }
        
        // 创建支撑终端组
        $PosGroupData = array(
            'node_id' => $nodeId, 
            'group_type' => 0,  // 0表示全门店，1表示子门店
            'client_id' => $clientId, 
            'phone_no' => $phoneNo);
        $groupId = self::createPosGroup($PosGroupData);
        if ($groupId == 'error') {
            throw_exception('发布失败(支撑终端组01)');
        }
        
        // 创建支撑合约
        $TreatyData = array(
            'node_id' => $nodeId, 
            'name' => $ticketData['name'], 
            'groupId' => $groupId,  // 终端组号
            'phone_no' => $phoneNo);
        $treatyId = self::createTreaty($TreatyData); // 获取支撑合约号
        if ($groupId == 'error') {
            throw_exception('发布失败(支撑合约01)');
        }
        
        // 创建支撑活动
        $ActivityData = array(
            'node_id' => $nodeId, 
            'name' => $ticketData['name'], 
            'groupId' => $groupId,  // 终端组号
            'treaty_id' => $treatyId, 
            'logoUrl' => $ticketData['logoUrl'], 
            'print_text' => '请填写优惠券详情', 
            'phone_no' => $phoneNo);
        $batchNo = self::createActivity($ActivityData); // 获取支撑活动号
        if ($batchNo == 'error') {
            throw_exception('发布失败(支撑活动01)');
        }
        
        // 创建卡券
        $goodsData = array(
            'goods_id' => get_goods_id(), 
            'batch_no' => $batchNo, 
            'goods_name' => $ticketData['name'], 
            'goods_desc' => "",  // 使用须知，可以为空
            'goods_image' => $ticketData['logoUrl'], 
            'node_id' => $nodeId, 
            'user_id' => $userId, 
            'goods_type' => 0,  // 0表示优惠券
            'market_price' => 0, 
            'storage_type' => 1,  // 1表示限制
            'storage_num' => $ticketData['number'], 
            'remain_num' => $ticketData['number'], 
            'print_text' => '请填写优惠券详情', 
            'begin_time' => date('YmdHis', strtotime($ticketData['startTime'])), 
            'end_time' => date('YmdHis', strtotime($ticketData['endTime'])), 
            'verify_begin_date' => date('YmdHis', 
                strtotime($ticketData['startTime'])), 
            'verify_end_date' => date('YmdHis', 
                strtotime($ticketData['endTime'])), 
            'verify_begin_type' => 0, 
            'verify_end_type' => 0, 
            'add_time' => date('YmdHis'), 
            'goods_cat' => '999001',  // 表示类目是‘其他/其他’
            'p_goods_id' => $treatyId, 
            'pos_group' => $groupId, 
            'pos_group_type' => 1); // 1表示全门店
        
        $goodsInfo = M('tgoods_info')->where(
            array(
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($goodsInfo)) {
            $goodsInfoId = M('tgoods_info')->add($goodsData);
            if ($goodsInfoId) {
                $goodsId = $goodsData['goods_id'];
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_goods' => $goodsId));
            } else {
                throw_exception("发布失败(卡券01)");
            }
        } else {
            // 不存在的话，就添加
            $goodsId = $goodsInfo[0]['goods_id'];
            M('tgoods_info')->where(
                array(
                    'id' => $goodsInfo[0]['id']))->save($goodsData);
        }
        
        // 创建活动相关信息
        $batchData = array(
            'batch_no' => $batchNo, 
            'batch_short_name' => $ticketData['name'], 
            'batch_name' => $ticketData['name'], 
            'node_id' => $nodeId, 
            'user_id' => $userId, 
            'batch_class' => 0, 
            'batch_type' => 0, 
            'info_title' => $luckdrawData['msgTitle'], 
            'use_rule' => $luckdrawData['msgContent'], 
            'sms_text' => $luckdrawData['msgContent'], 
            'batch_img' => $ticketData['logoUrl'], 
            'batch_amt' => 0, 
            'begin_time' => date('YmdHis', strtotime($eventData['startTime'])), 
            'end_time' => date('YmdHis', strtotime($eventData['endTime'])), 
            'send_begin_date' => date('YmdHis', 
                strtotime($eventData['startTime'])), 
            'send_end_date' => date('YmdHis', strtotime($eventData['endTime'])), 
            'verify_begin_date' => date('YmdHis', 
                strtotime($ticketData['startTime'])), 
            'verify_end_date' => date('YmdHis', 
                strtotime($ticketData['endTime'])), 
            'verify_begin_type' => 0, 
            'verify_end_type' => 0, 
            'storage_num' => $ticketData['number'], 
            'add_time' => date('YmdHis'), 
            'node_pos_group' => $groupId, 
            'node_pos_type' => 1, 
            'batch_desc' => "", 
            'node_service_hotline' => $eventData['serveTelphone'], 
            'goods_id' => $goodsId, 
            'remain_num' => $ticketData['number'], 
            'material_code' => "", 
            'print_text' => "请填写优惠券详情", 
            'm_id' => $m_id, 
            'validate_type' => 0);
        $batchInfo = M('tbatch_info')->where(
            array(
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($batchInfo)) {
            $batchId = M('tbatch_info')->add($batchData);
            if ($batchId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_batch' => $batchId));
            } else {
                throw_exception("发布失败(batch01)");
            }
        } else {
            // 不存在的话，就添加
            $batchId = $batchInfo[0]['id'];
            M('tbatch_info')->where(
                array(
                    'id' => $batchInfo[0]['id']))->save($batchData);
        }
        
        // cj_batch奖品处理
        $cjBatchData = array(
            'batch_id' => $m_id,  // '抽奖活动id'
            'node_id' => $nodeId,  // '商户号'
            'activity_no' => $batchNo,  // '奖品活动号'
            'award_origin' => '2',  // '奖品来源 1支撑 2旺财'
            'award_rate' => $luckdrawData['luckProbability'],  // '中奖率'
            'total_count' => $ticketData['number'],  // '奖品总数'
            'day_count' => $luckdrawData['dayLimit'],  // '每日奖品数'
            'batch_type' => CommonConst::BATCH_TYPE_WEEL,  // ,
            'cj_rule_id' => $ruleId,  // '抽奖规则id'
            'send_type' => '0',  // '0-下发，1-不下发'
            'status' => '1',  // '1正常 2停用'
            'cj_cate_id' => $cjCateId,  // '抽奖类别id对应tcj_cate主键id'
            'add_time' => date('YmdHis'),  // '奖品添加时间'
            'goods_id' => $goodsId, 
            'b_id' => $batchId);
        $cjBatchInfo = M('tcj_batch')->where(
            array(
                'node_id' => $nodeId))->select();
        // 检查是否存在活动信息
        if (empty($cjBatchInfo)) {
            $cjBatchId = M('tcj_batch')->add($cjBatchData);
            if ($cjBatchId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_cjbatch' => $cjBatchId));
            } else {
                throw_exception("发布失败(cjbatch01)");
            }
        } else {
            // 不存在的话，就添加
            $cjBatchId = $cjBatchInfo[0]['id'];
            M('tcj_batch')->where(
                array(
                    'id' => $cjBatchInfo[0]['id']))->save($cjBatchData);
        }
        
        // 创建大转盘手机专用渠道
        $snsType = 101;
        $type = 1;
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'sns_type' => $snsType))->select();
        if (empty($channelInfo)) {
            $channelArr = array(
                'name' => '大转盘手机专用渠道', 
                'type' => $type, 
                'sns_type' => $snsType, 
                'status' => '1', 
                'start_time' => date('YmdHis', 
                    strtotime($eventData['startTime'])), 
                'end_time' => date('YmdHis', strtotime($eventData['endTime'])), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $nodeId);
            $channelId = M('tchannel')->add($channelArr);
            if ($channelId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_channel' => $channelId));
            } else {
                throw_exception('发布失败(渠道)');
            }
        } else {
            $channelId = $channelInfo[0]['id'];
            M('tchannel')->where(
                array(
                    'id' => $channelId))->save($channelArr);
        }
        
        // 插入batch——channel表
        $batchChannelInfo = M('tbatch_channel')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => CommonConst::BATCH_TYPE_WEEL))->select();
        if (empty($batchChannelInfo)) {
            $batchChannelArr = array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $nodeId, 
                'status' => 1, 
                'end_time' => date('YmdHis', strtotime($eventData['endTime'])));
            $batchChannelId = M('tbatch_channel')->add($batchChannelArr);
            if ($batchChannelId) {
                M('twheel_tmp')->where(
                    array(
                        'phone_no' => $phoneNo))->save(
                    array(
                        'is_batchchannel' => $batchChannelId));
            } else {
                throw_exception('发布失败(活动渠道)');
            }
        } else {
            $batchChannelId = $batchChannelInfo[0]['id'];
            M('tbatch_channel')->where(
                array(
                    'id' => $batchChannelId))->save($batchChannelArr);
        }
        
        // 创建免费订单
        $isOrder = M('twheel_tmp')->getFieldByPhone_no($phoneNo, 'is_order');
        if (empty($isOrder)) {
            $orderId = D('BindChannel')->createOrder($nodeId, $m_id, 
                CommonConst::BATCH_TYPE_WEEL);
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $phoneNo))->save(
                array(
                    'is_order' => $orderId));
        } else {
            $orderId = $isOrder;
        }
        
        // 所有任务创建完毕，哦也！！！！！！
        return $batchChannelId;
    }

    /**
     * [createPosGroup 创建终端组] [return 终端组号，groupId]
     */
    public function createPosGroup($PosGroupData) {
        $isPos = M('twheel_tmp')->getFieldByPhone_no($PosGroupData['phone_no'], 
            'is_pos');
        if ($isPos == '0') {
            $reqArr = array(
                'CreatePosGroupReq' => array(
                    'NodeId' => $PosGroupData['node_id'], 
                    'GroupType' => $PosGroupData['group_type'], 
                    'GroupName' => str_pad($PosGroupData['client_id'], 6, '0', 
                        STR_PAD_LEFT), 
                    'GroupDesc' => '', 
                    'DataList' => $PosGroupData['node_id'])); // 手机端，刚注册完，node_id只有一个
            
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestIssForImageco($reqArr);
            $respStatus = $reqResult['CreatePosGroupRes']['Status'];
            if (! $reqResult || ($respStatus['StatusCode'] != '0000' &&
                 $respStatus['StatusCode'] != '0001')) {
                return "error";
            }
            $groupId = $reqResult['CreatePosGroupRes']['GroupID'];
            // 插入标志
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $PosGroupData['phone_no']))->save(
                array(
                    'is_pos' => $groupId));
        } else {
            $groupId = $isPos;
        }
        
        // 在旺财创建终端组,不存在此终端组，就创建一个
        $posGroupInfo = M('tpos_group')->where(
            array(
                'node_id' => $PosGroupData['node_id'], 
                'group_id' => $groupId))->select();
        if (empty($posGroupInfo)) {
            $groupData = array(
                'node_id' => $PosGroupData['node_id'], 
                'group_id' => $groupId, 
                'group_name' => $reqResult['CreatePosGroupReq']['GroupName'], 
                'group_type' => $PosGroupData['group_type'], 
                'status' => '0');
            $relationData = array(
                'group_id' => $groupId, 
                'node_id' => $PosGroupData['node_id']);
            M()->startTrans();
            // 同时插入到终端组数据库，和关系表
            if (M()->table('tpos_group')->add($groupData) &&
                 M()->table('tgroup_pos_relation')->add($relationData)) {
                M()->commit();
            } else {
                M()->rollback();
                return "error";
            }
        }
        return $groupId;
    }

    /**
     * [CreateTreaty 创建合约] [return 合约号 TreatyId]
     */
    public function createTreaty($TreatyData) {
        $isTreaty = M('twheel_tmp')->getFieldByPhone_no($TreatyData['phone_no'], 
            'is_treaty');
        if ($isTreaty == '0') {
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
            $reqArr = array(
                'CreateTreatyReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'RequestSeq' => $TransactionID, 
                    'ShopNodeId' => $TreatyData['node_id'], 
                    'BussNodeId' => $TreatyData['node_id'], 
                    'TreatyName' => $TreatyData['name'], 
                    'TreatyShortName' => $TreatyData['name'], 
                    'StartTime' => date('YmdHis'), 
                    'EndTime' => '20301231235959', 
                    'GroupId' => $TreatyData['groupId'], 
                    'GoodsName' => $TreatyData['name'], 
                    'GoodsShortName' => $TreatyData['name'], 
                    'SalePrice' => 0)); // 优惠券没有此项，默认填空
            
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestIssForImageco($reqArr);
            $respStatus = $reqResult['CreateTreatyRes']['Status'];
            if (! $reqResult || ($respStatus['StatusCode'] != '0000' &&
                 $respStatus['StatusCode'] != '0001')) {
                return "error";
            }
            $treatyId = $reqResult['CreateTreatyRes']['TreatyId']; // 合约号
                                                                   // 插入标志
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $TreatyData['phone_no']))->save(
                array(
                    'is_treaty' => $treatyId));
        } else {
            $treatyId = $isTreaty; // 合约号
        }
        return $treatyId;
    }

    /**
     * [CreateTreaty 创建支撑活动] [return 活动号 batchNo]
     */
    public function createActivity($ActivityData) {
        $isActivity = M('twheel_tmp')->getFieldByPhone_no(
            $ActivityData['phone_no'], 'is_activity');
        if ($isActivity == '0') {
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
            $smilInfo = self::getSmilId($ActivityData['logoUrl'], 
                $ActivityData['name'], $ActivityData['node_id']);
            if ($smilInfo['status'] = 1) {
                $smilId = $smilInfo['success'];
            } else {
                return "error";
            }
            $reqArr = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $ActivityData['node_id'], 
                    'RelationID' => $ActivityData['node_id'], 
                    'TransactionID' => $TransactionID, 
                    'SmilID' => $smilId, 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => $ActivityData['name'], 
                        'ActivityShortName' => $ActivityData['name'], 
                        'BeginTime' => date('YmdHis'), 
                        'EndTime' => '20301231235959', 
                        'UseRangeID' => $ActivityData['group_id']), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => 1, 
                        'UseAmtLimit' => 0), 
                    'GoodsInfo' => array(
                        'pGoodsId' => $ActivityData['treaty_id']), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '', 
                        'PrintText' => $ActivityData['print_text'])));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestIssForImageco($reqArr);
            $respStatus = $reqResult['ActivityCreateRes']['Status'];
            if (! $reqResult || ($respStatus['StatusCode'] != '0000' &&
                 $respStatus['StatusCode'] != '0001')) {
                return "error";
            }
            $batchNo = $reqResult['ActivityCreateRes']['Info']['ActivityID'];
            // 插入标志
            M('twheel_tmp')->where(
                array(
                    'phone_no' => $ActivityData['phone_no']))->save(
                array(
                    'is_activity' => $batchNo));
        } else {
            $batchNo = $isActivity;
        }
        
        return $batchNo;
    }

    /**
     * [createStore 创建门店]
     *
     * @return [type] [门店号]
     */
    public function createStore($nodeId, $userId, $phoneNo, $email) {
        // 默认数据创建门店
        $storeData = array(
            'node_id' => $nodeId, 
            'user_id' => $userId, 
            'phone_no' => $phoneNo, 
            'custom_no' => '', 
            'store_name' => '请填写门店简称', 
            'contact_name' => '请填写姓名', 
            'contact_tel' => $phoneNo, 
            'contact_email' => $email, 
            'province_code' => '09', 
            'city_code' => '021', 
            'town_code' => '012', 
            'address' => '门店详细地址为空，请填写');
        // 创建支撑门店请求参数
        $TransactionID = time() . mt_rand('1000', '9999'); // 流水号
        $reqArr = array(
            'SystemID' => C('ISS_SYSTEM_ID'), 
            'TransactionID' => $TransactionID, 
            'ISSPID' => $storeData['node_id'], 
            'UserId' => $storeData['user_id'], 
            'Url' => '<![CDATA[旺财会员账户中心]]>', 
            'CustomNo' => $storeData['custom_no'], 
            'StoreName' => $storeData['store_name'], 
            'StoreShortName' => $storeData['store_name'], 
            'ContactName' => $storeData['contact_name'], 
            'ContactTel' => $storeData['contact_tel'], 
            'ContactEmail' => $storeData['contact_email'], 
            'RegionInfo' => array(
                'Province' => $storeData['province_code'], 
                'City' => $storeData['city_code'], 
                'Town' => $storeData['town_code'], 
                'Address' => $storeData['address']));
        // 创建支撑门店,请求返回
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestIssServ(
            array(
                'CreateStoreReq' => $reqArr));
        // 返回状态
        $respStatus = isset($reqResult['CreateStoreRes']) ? $reqResult['CreateStoreRes']['Status'] : $reqResult['Status'];
        // 返回是否成功
        if ($respStatus['StatusCode'] != '0000') {
            return "error";
        }
        // 成功时，返回的门店数据
        $respData = $reqResult['CreateStoreRes'];
        $storeId = $respData['StoreId'];
        $addStoreData = array(
            'store_id' => $storeId, 
            'node_id' => $storeData['node_id'], 
            'store_name' => $storeData['store_name'], 
            'store_short_name' => $storeData['store_name'], 
            'store_desc' => "", 
            'province_code' => $storeData['province_code'], 
            'city_code' => $storeData['city_code'], 
            'town_code' => $storeData['town_code'], 
            'address' => $storeData['address'], 
            'post_code' => "", 
            'principal_name' => $storeData['contact_name'], 
            'principal_position' => "", 
            'principal_tel' => $storeData['contact_tel'], 
            'principal_phone' => $storeData['phone_no'], 
            'principal_email' => $storeData['contact_email'], 
            'custom_no' => $storeData['custom_no'], 
            'memo' => "", 
            'status' => 0, 
            'add_time' => date('YmdHis'), 
            'store_phone' => "", 
            'store_email' => "", 
            'busi_time' => "", 
            'store_pic' => "", 
            'business_code' => "", 
            'type' => 2, 
            'store_introduce' => "默认门店");
        // 听人说,支撑同步先到了，旺财门店入库（主键重复）异常，故先delete(这里也是人家原话)
        M('tstore_info')->where(array(
            'store_id' => $storeId))->delete();
        $result = M('tstore_info')->add($addStoreData);
        if ($result === false) {
            return "error";
        }
        
        // 成功则返回门店号
        return $storeId;
    }

    /**
     * [getSmilId 获取小图标，可以不要]
     *
     * @param [type] $imageName [图片地址]
     * @param [type] $name [活动名称]
     * @param [type] $nodeId [机构号]
     * @return [type] [小图标地址]
     */
    public function getSmilId($imageName, $name, $nodeId) {
        $imagePath = realpath(C('UPLOAD'));
        $zipFileName = date('YmdHis') . '.zip';
        import('@.ORG.Net.Zip', '', '.php');
        $test = new zip_file($zipFileName);
        $test->set_options(
            array(
                'basedir' => $imagePath . '/template/', 
                'inmemory' => 0, 
                'recurse' => 1, 
                'storepaths' => 0, 
                'overwrite' => 1, 
                'level' => 5, 
                'name' => $zipFileName));
        $parseInfo = explode('/Upload', $imageName);
        $imageUrl = $imagePath . $parseInfo[1];
        if (! is_file($imageUrl)) {
            return array(
                'error' => '获取SmilId接口图片文件不存在' . $imageUrl, 
                'status' => 0);
        }
        // 缩放图片大小要小于60k
        import('ORG.Util.Image');
        $smilUrl = Image::thumb($imageUrl, 
            dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
            true);
        if (! $smilUrl) {
            return array(
                'error' => 'SmilId接口图片压缩失败', 
                'status' => 0);
        }
        $imageUrl = $smilUrl;
        $smil_cfg = create_smil_cfg($imageUrl);
        if ($smil_cfg === false) {
            return array(
                'error' => '创建smil_cfg失败', 
                'status' => 0);
        }
        $info = pathinfo($imageUrl);
        $ex = $info['extension'];
        $files = array(
            '1.' . $ex => $imageUrl, 
            'default.smil' => realpath($smil_cfg));
        $test->add_files($files);
        $test->create_archive();
        $zipPath = $imagePath . '/template/' . $zipFileName;
        $SmilZip = base64_encode(file_get_contents($zipPath));
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $reqArr = array(
            'SmilAddEditReq' => array(
                'ISSPID' => $nodeId, 
                'PlatformID' => C('ISS_PLATFORM_ID'), 
                'TransactionID' => $TransactionID, 
                'Username' => C('ISS_SEND_USER'), 
                'Password' => C('ISS_SEND_USER_PASS'), 
                'SmilInfo' => array(
                    'SmilId' => "", 
                    'SmilName' => time(), 
                    'SmilDesc' => $name, 
                    'SmilZip' => $SmilZip)));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->smilAddEditReq($reqArr);
        @unlink($zipPath);
        @unlink($smil_cfg);
        @unlink($smilUrl);
        $respStatus = $reqResult['SmilAddEditRes']['Status'];
        if (! $reqResult || ($respStatus['StatusCode'] != '0000' &&
             $respStatus['StatusCode'] != '0001')) {
            return array(
                'error' => "获取SmilId失败 原因：{$respStatus['StatusText']}", 
                'status' => 0);
        }
        return array(
            'success' => $reqResult['SmilAddEditRes']['SmilId'], 
            'status' => 1);
    }

    /**
     * [getWheelInfo 获取临时表信息]
     *
     * @return [type] [description]
     */
    public function getWheelInfo($phoneNo = null) {
        if (! $phoneNo) {
            $loginSuccess = session('loginSuccess');
            $phoneNo = $loginSuccess['bindPhoneNo'];
        }
        $res = M('twheel_tmp')->where(
            array(
                'phone_no' => $phoneNo))->select();
        return $res[0];
    }

    /**
     * [setWb 充值旺币-赠送]
     *
     * @param [type] $nodeId [机构号]
     * @param [type] $wbnumber [旺币数量]
     * @param [type] $endTime [旺币有效期结尾时间]
     * @param [type] $ReasonID [充值的类型]
     */
    public function setWb($nodeId, $wbnumber, $endTime, $ReasonID = 1) {
        // 结算号
        $contractNo = M('tnode_info')->getFieldByNode_id($nodeId, 'contract_no');
        // 请求营帐充值旺币接口
        $reqArr = array(
            'SetWbReq' => array(
                'SystemID' => 1000, 
                'TransactionID' => time() . rand(10000, 99999), 
                'ContractID' => $contractNo, 
                'WbType' => 1, 
                'BeginTime' => date('Ymd'), 
                'EndTime' => $endTime, 
                'ReasonID' => $ReasonID, 
                'Amount' => 0.00, 
                'WbNumber' => $wbnumber, 
                'Remark' => "FreeGive"));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $WcInfo = $RemoteRequest->requestYzServ($reqArr);
        if ($WcInfo['Status']['StatusCode'] == '0000') {
            return true;
        } else {
            return false;
        }
    }
}