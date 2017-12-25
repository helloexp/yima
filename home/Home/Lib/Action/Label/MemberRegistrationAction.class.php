<?php

class MemberRegistrationAction extends MyBaseAction {

    public $expiresTime = 120;
    // 手机验证码过期时间
    public function index() {
        
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $result['batch_id'], 
                'batch_type' => $this->batch_type))->find();
        // 用于提交表单
        $saveInfo = array(
            'id' => $id, 
            'batch_type' => $this->batch_type, 
            'batch_id' => $row['id'],  // 活动id
            'node_id' => $row['node_id'], 
            'select_type' => $row['select_type'], 
            'is_cj' => $row['is_cj'], 
            'is_send' => $row['is_send'], 
            'member_level' => $row['member_level'], 
            'channel_id' => $result['channel_id'], 
            'batch_id' => $result['batch_id']);
        // 微信会员判断
        $sId = I('s_id', null, 'mysql_real_escape_string');
        $loginTime = I('login_time', null);
        if (isset($sId) && isset($loginTime)) {
            // 获取password
            $loginInfo = M('tmember_login')->where(
                "node_id={$row['node_id']} AND s_id='{$sId}'")->find();
            if (! $loginInfo) {
                $this->error(
                    array(
                        'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => '你的访问地址出错啦~'));
            }
            // 校验sid
            if ($sId != md5($loginTime . $loginInfo['password'])) {
                $this->error(
                    array(
                        'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => '你的访问地址出错啦~'));
            }
            // 过期校验
            C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
            $expired = C('WeixinServ.SID_TIME');
            if (time() - strtotime($loginTime) > ($expired + 5) * 60)
                $this->error('该链接已过期,请重新获取');
                // 获取会员卡信息
            $memberCardsInfo = M()->table("tmember_info i")->field(
                'i.name,i.phone_no,i.request_id,b.level_name,b.print_info,b.valid_day')
                ->join(
                "tmember_batch b ON i.node_id=b.node_id AND i.batch_no=b.batch_no")
                ->where(
                "i.node_id='{$row['node_id']}' AND i.phone_no='{$loginInfo['phone_no']}'")
                ->find();
            $saveInfo['s_id'] = $sId;
            if ($memberCardsInfo) {
                // 处理有效期
                if (empty($memberCardsInfo['request_id'])) {
                    $memberCardsInfo['request_id'] = '商家还没有下发该等级的电子粉丝卡给您!';
                } else {
                    // 获取二维码和辅助码
                    $codeInfo = M('tbarcode_trace')->field(
                        'assist_number,barcode_bmp')
                        ->where(
                        "request_id={$memberCardsInfo['request_id']} AND data_from='5'")
                        ->find();
                    $beginData = dateformat(
                        substr($memberCardsInfo['request_id'], 0, 8), 'Y-m-d');
                    $endData = date('Y-m-d', 
                        strtotime($beginData) +
                             $memberCardsInfo['valid_day'] * 24 * 3600);
                    $memberCardsInfo['request_id'] = "有效期：{$beginData}&nbsp;-&nbsp;{$endData}";
                }
                $this->assign('code_img', 
                    base64_encode(
                        $this->wbmp2other(
                            base64_decode($codeInfo['barcode_bmp']), 'png')));
                $this->assign('memberCardsInfo', $memberCardsInfo);
                $this->assign('is_show', 1);
            }
        }
        
        // 字段类型处理
        if (! empty($row['select_type'])) {
            $field = explode(',', $row['select_type']);
            $type_1 = array_flip(explode('-', $field[0]));
            $type_2 = explode('-', $field[1]);
            $this->assign('type_1', $type_1);
            $this->assign('type_2', $type_2);
            if (isset($type_1[4])) {
                $selectQ = M('tanswers_question')->where(
                    "label_id='{$row['id']}'")->find();
                $selectA = M('tanswers_question_info')->where(
                    "question_id='{$selectQ['id']}'")->select();
                $this->assign('selectQ', $selectQ);
                $this->assign('selectA', $selectA);
            }
        }
        // 更新点击数
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        session('saveInfo', $saveInfo);
        
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('id', $id);
        $this->assign('row', $row);
        $this->display(); // 输出模板
    }

    public function add() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        
        $saveInfo = session('saveInfo');
        if (empty($saveInfo))
            $this->error('参数错误');
        $id = I('id', null);
        if ($saveInfo['id'] != $id)
            $this->error('参数错误');
            
            // 字段类型处理
        if (! empty($saveInfo['select_type'])) {
            $field = explode(',', $saveInfo['select_type']);
            $type_1 = array_flip(explode('-', $field[0]));
            $type_2 = explode('-', $field[1]);
        }
        $error = '';
        
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        // 手机验证码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->error("验证码{$error}");
        }
        $phoneCheckCode = session('checkCode');
        if (empty($phoneCheckCode) || $phoneCheckCode['number'] != $checkCode)
            $this->error('手机验证码不正确');
        if (time() - $phoneCheckCode['add_time'] > $this->expiresTime)
            $this->error('手机验证码已经过期');
        $memberInfo = M('tmember_info')->where(
            "node_id ='{$saveInfo['node_id']}' AND phone_no ='{$phoneNo}'")->find();
        if (! empty($memberInfo) && ! empty($memberInfo['request_id'])) {
            $this->error("您已经是我们的粉丝了");
        }
        $name = I('post.name', null);
        if (isset($type_1[1]) && $type_2[$type_1[1]] == '1') { // 姓名
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("姓名{$error}");
            }
        }
        
        $year = I('post.year', null);
        $month = I('post.month', null);
        $day = I('post.day', null);
        if (isset($type_1[2]) && $type_2[$type_1[2]] == '1') { // 生日
            if (! check_str($year, array(
                'null' => false), $error)) {
                $this->error("生日年份{$error}");
            }
            if (! check_str($month, 
                array(
                    'null' => false), $error)) {
                $this->error("生日月份{$error}");
            }
            if (! check_str($day, array(
                'null' => false), $error)) {
                $this->error("生日天数{$error}");
            }
        }
        
        $birthday = $year . $month . $day;
        if (! check_str($birthday, 
            array(
                'null' => true, 
                'strtype' => 'datetime'), $error)) {
            $this->error("请正确填写生日信息");
        }
        
        $sex = I('post.sex', null);
        if (isset($type_1[3]) && $type_2[$type_1[3]] == '1') { // 性别
            if (! check_str($sex, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'maxval' => '3'), $error)) {
                $this->error("性别{$error}");
            }
        }
        
        $selectQ = I('post.select_q', null);
        $selectA = I('post.select_a', null);
        if (isset($type_1[4]) && $type_2[$type_1[4]] == '1') {
            if (! check_str($selectA, 
                array(
                    'null' => false), $error)) {
                $this->error($selectQ . $error);
            }
        }
        session('saveInfo', null);
        session('checkCode', null);
        $data = array(
            'node_id' => $saveInfo['node_id'], 
            'name' => $name, 
            'phone_no' => $phoneNo, 
            'batch_no' => $saveInfo['member_level'], 
            'sex' => $sex, 
            'birthday' => $birthday, 
            'years' => $year, 
            'month_days' => $month . $day, 
            'age' => $this->age($birthday), 
            'channel_id' => $saveInfo['channel_id'], 
            'batch_id' => $saveInfo['batch_id'], 
            'add_time' => date('Ymdhis'), 
            'select_q' => $selectQ, 
            'select_a' => $selectA);
        M()->startTrans();
        if (empty($memberInfo)) {
            $resultId = M('tmember_info')->add($data);
            if (! $resultId) {
                M()->rollback();
                $this->error('系统出错,注册失败');
            }
            $updateId = $resultId;
            // 更新招募的会员数量
            M('tmarketing_info')->where(
                "id={$saveInfo['batch_id']} and batch_type='{$this->batch_type}'")->setInc(
                'member_sum');
        } else { // 更新粉丝权益类型
            $data = array(
                'name' => $name, 
                'sex' => $sex, 
                'birthday' => $birthday, 
                'years' => $year, 
                'month_days' => $month . $day, 
                'age' => $this->age($birthday), 
                'batch_no' => $saveInfo['member_level'], 
                'update_time' => date('YmdHis'));
            $res = M('tmember_info')->where("id={$memberInfo['id']}")->save(
                $data);
            if (! $res) {
                M()->rollback();
                $this->error('系统出错,注册失败');
            }
            $updateId = $memberInfo['id'];
        }
        
        // 微信会员更新手机号
        if (! empty($saveInfo['s_id'])) {
            $result = M('tmember_login')->where("s_id='{$saveInfo['s_id']}'")->save(
                array(
                    'phone_no' => $phoneNo));
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,注册失败');
            }
        }
        M()->commit();
        // 发码
        if ($saveInfo['is_send'] == '1') {
            // 更新彩信标题内容
            $sql = "UPDATE tgoods_info SET mms_title = goods_name,mms_text=print_text WHERE batch_no={$saveInfo['member_level']} AND source=3";
            $result = M()->execute($sql);
            if ($result === false) {
                log::write(
                    "粉丝招募发码失败,原因:短信标题和内容更新失败 活动号:{$saveInfo['member_level']}");
            } else {
                $transId = get_request_id();
                import("@.Vendor.SendCode");
                $req = new SendCode();
                $result = $req->wc_send($saveInfo['node_id'], '', 
                    $saveInfo['member_level'], $phoneNo, '5', $transId);
                if ($result === true) {
                    $data = array(
                        'request_id' => $transId, 
                        'update_time' => date('YmdHis'));
                    $res = M('tmember_info')->where("id={$updateId}")->save(
                        $data);
                    if ($res === false) {
                        log::write(
                            "粉丝招募发码成功,更新request_id失败;tmember_info表id:{$updateId},request_id:{$transId}");
                    }
                } else {
                    log::write("粉丝招募发码失败,原因:{$result}");
                }
                $sendStr = ",系统将以短彩信形式下发给您对应等级的电子粉丝卡";
            }
        }
        // 是否抽奖
        if ($saveInfo['is_cj'] == '1') {
            // 去抽奖
            import('@.Vendor.ChouJiang');
            $choujiang = new ChouJiang($id, $phoneNo);
            $resp = $choujiang->send_code();
            if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
                Log::write('中奖了');
                $cj_msg = "恭喜您中奖了!";
                if ($resp['award_level'] == '1') { // 一等奖
                    $cj_msg = "恭喜您获得一等奖！";
                } elseif ($resp['award_level'] == '2') { // 二等奖
                    $cj_msg = "恭喜您获得二等奖！";
                } elseif ($resp['award_level'] == '3') { // 三等奖
                    $cj_msg = "恭喜您获得三等奖！";
                }
                $this->success("粉丝卡申领成功{$sendStr}!<br/>{$cj_msg}");
            } else {
                Log::write($resp);
                $this->success("粉丝卡申领成功{$sendStr}!<br/>很抱歉,您没有中奖!");
            }
            exit();
        }
        $this->success("粉丝卡申领成功{$sendStr}!");
    }
    
    // 手机发送验证码
    public function sendCheckCode() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        $saveInfo = session('saveInfo');
        if (empty($saveInfo))
            $this->error('参数错误');
            
            // 发送频率验证
        $checkCode = session('checkCode');
        if (! empty($checkCode) &&
             (time() - $checkCode['add_time']) < $this->expiresTime) {
            $this->error('验证码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        $nodeName = M('tnode_info')->where("node_id={$saveInfo['node_id']}")->getField(
            'node_short_name');
        $codeInfo = "【{$nodeName}】 粉丝注册验证码：{$num}；如非本人操作请忽略！";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $codeInfo,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $checkCode = array(
            'number' => $num, 
            'add_time' => time());
        session('checkCode', $checkCode);
        $this->success('验证码已发送');
    }
    
    // 通过生日计算年龄
    // 计算年龄
    public function age($mydate) {
        if (empty($mydate))
            return '0';
        $birth = date('Y-m-d', strtotime($mydate));
        list ($by, $bm, $bd) = explode('-', $birth);
        $cm = date('n');
        $cd = date('j');
        $age = date('Y') - $by - 1;
        if ($cm > $bm || $cm == $bm && $cd > $bd)
            $age ++;
        return $age;
    }
    
    // 将base64加密wbmp格式文件转换成png格式
    public function showPng() {
        $str = I('b_str');
        $im = imagecreatefromstring(base64_decode($str));
        header("content-type:image/png");
        imagepng($im);
        exit();
    }

    /**
     * wbmp转bmp,gif,jpg
     */
    public function wbmp2other($data, $other) {
        $im = $this->img_resize($data, 2);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            $newImg = ob_get_contents();
            ob_end_clean();
            return $newImg;
        } else {
            return false;
        }
    }

    /**
     * 放大图片
     */
    public function img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
                                 // 画布长宽
        $new_img_width = $s_w * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        // $d_white_x = ($new_img_width - $s_w*$fdbs)/2;
        // $d_white_y = $d_white_x;
        $d_white_x = 0;
        $d_white_y = 0;
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
}