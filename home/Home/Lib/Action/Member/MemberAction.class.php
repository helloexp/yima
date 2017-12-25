<?php

/*
 * 粉丝中心 @author zs
 */
class MemberAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    /*
     * json返回前端页面
     */
    public function jsonMsg($code, $msg, $other) {
        $arr = array(
            'code' => $code, 
            'info' => $msg, 
            'other' => $other);
        return json_encode($arr);
        // exit;
    }

    /*
     * 内嵌错误提示
     */
    public function inset_error($message, $jumpUrl) {
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
        }
        $this->assign('error', $message);
        $this->assign('jumpUrlList', $jumpUrl);
        $this->assign('jumpUrl', $jumpUrl);
        $this->display('Inset_msg');
        exit();
    }

    /*
     * 内嵌提示
     */
    public function inset_success($message, $jumpUrl) {
        // $this->assign('message',$message);
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
        }
        $this->assign('message', $message);
        $this->assign('jumpUrlList', $jumpUrl);
        $this->assign('jumpUrl', $jumpUrl);
        $this->display('Inset_msg');
        exit();
    }
    // 获取有效的粉丝权益信息
    public function getBatch() {
        $levelData = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND status=1")->select();
        if ($levelData) {
            foreach ($levelData as $k => $v) {
                if ($v['date_type'] == '1') { // 判断是否过期
                    if (strtotime($v['verify_end_date'] <= time())) {
                        unset($levelData[$k]);
                    }
                }
            }
        }
        return $levelData;
    }
    // 判断粉丝权益是否是有效的粉丝权益
    public function checkLevel($batchNo) {
        $levelInfo = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND status=1 AND batch_no='{$batchNo}'")->find();
        if ($levelInfo) {
            if ($levelInfo['date_type'] == '1') { // 判断是否过期
                if (strtotime($levelInfo['verify_end_date'] <= time())) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    /*
     * 获取卡配置数组
     */
    public function getBatcharr() {
        $arr = $this->getBatch();
        return array_valtokey($arr, 'batch_no', 'level_name');
    }

    /*
     * 粉丝卡发送，重发，撤销
     */
    public function code_handle() {
        $id = I('member_id', null, 'mysql_real_escape_string');
        $rev = I('rev');
        if (empty($id) || empty($rev)) {
            $this->error('参数错误！');
        }
        $model = M('tmember_info');
        $res = $model->where("node_id='{$this->nodeId}' AND id='{$id}'")->find();
        if (! $res)
            $this->error('未找到粉丝信息！');
        if (empty($res['batch_no']))
            $this->error('该粉丝还没有绑定粉丝权益卡');

        // 发送粉丝卡
        if ($rev == 'send') {
            $error = '';
            $mmsTitle = I('mms_title', null);
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            $mmsInfo = I('mms_info');
            if (! check_str($mmsInfo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }

            // 更新彩信标题内容
            $result = $this->updateMms($mmsTitle, $mmsInfo, $res['batch_no']);
            if (! $result)
                $this->error('短信内容更新失败');
            $transId = get_request_id();
            $sendcode_res = $this->sendCode($res['phone_no'], $res['batch_no'], 
                $transId);
            if ($sendcode_res !== true) {
                $this->error($sendcode_res);
            } else {
                // 更新数据;
                $update_res = $model->where("id='{$id}'")->save(
                    array(
                        'request_id' => $transId, 
                        'update_time' => date('YmdHis')));
                if ($update_res) {
                    node_log(
                        "粉丝框:粉丝卡发送，粉丝姓名：" . $res['name'] . "，电话：" .
                             $res['phone_no']);
                    $this->success('发送粉丝权益卡，更新数据成功!');
                    exit();
                } else {
                    echo $this->error('发送粉丝权益卡成功，更新数据失败!');
                }
            }
        }
        // 撤销粉丝卡
        if ($rev == 'repeal') {
            // 撤销
            if (! empty($res['request_id'])) {
                $result = $this->cancelCode($res['request_id']);
                if ($result !== true) {
                    $this->error("撤销粉丝权益卡失败:{$result}");
                }
            }
            $arr = array(
                'request_id' => '', 
                'update_time' => date('YmdHis'));
            $res_code = $model->where(array(
                'id' => $id))->save($arr);
            if ($res_code === false) {
                $this->error("撤销粉丝权益卡失败!");
            } else {
                node_log(
                    "粉丝框:粉丝卡撤消，粉丝姓名：" . $res['name'] . "，电话：" . $res['phone_no']);
                $this->success("撤销粉丝成功！");
                exit();
            }
        }
        // 重发粉丝卡
        if ($rev == 'resend') {
            if (! empty($res['request_id'])) {
                $result = $this->resendCode($res['request_id']);
                if ($result !== true) {
                    $this->error("重发粉丝权益卡失败{$result}");
                } else {
                    node_log(
                        "粉丝框:粉丝卡重发，粉丝姓名：" . $res['name'] . "，电话：" .
                             $res['phone_no']);
                    $this->success("重发粉丝成功！");
                    exit();
                }
            }
        }
    }

    /*
     * 发送粉丝卡 成功返回true,失败返回错误信息
     */
    public function sendCode($phone_no, $batch_no, $transId = null) {
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->wc_send($this->node_id, $this->userId, $batch_no, 
            $phone_no, '5', $transId);
        if ($resp === true) {
            node_log("粉丝框:粉丝卡发送，手机：" . $phone_no . ",活动号:" . $batch_no);
            return true;
        } else {
            return $resp;
        }
    }

    /*
     * 撤销粉丝卡 成功返回true,失败返回错误信息
     */
    public function cancelCode($request_id) {
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->cancelcode($this->node_id, $this->user_id, $request_id, 
            $cancel_flag = '1');
        if ($resp === true) {
            node_log("粉丝框:粉丝卡撤销：" . $request_id);
            return true;
        } else {
            return $resp;
        }
    }

    /*
     * 重发粉丝卡
     */
    public function resendCode($request_id) {
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->resend_send($this->node_id, $this->user_id, $request_id);
        if ($resp === true) {
            node_log("粉丝框:粉丝卡重发：" . $request_id);
            return true;
        } else {
            return $resp;
        }
    }

    public function index() {
        $where = $where2 = '1=1';
        // 添加日期
        $beginAddDate = I('begin_add_date', null, 'mysql_real_escape_string');
        if (! empty($beginAddDate)) {
            $where .= " AND i.add_time>={$beginAddDate}000000";
        }
        $endAddDate = I('end_add_date', null, 'mysql_real_escape_string');
        if (! empty($endAddDate)) {
            $where .= " AND i.add_time<={$endAddDate}235959";
        }
        // 发码数
        $beginSendNum = I('begin_send_num', null, 'mysql_real_escape_string');
        if (isset($beginSendNum) && $beginSendNum != '') {
            $where2 .= " AND IFNULL(x.send_count,0)>={$beginSendNum}";
        }
        $endSendNum = I('end_send_num', null, 'mysql_real_escape_string');
        if (isset($endSendNum) && $endSendNum != '') {
            $where2 .= " AND IFNULL(x.send_count,0)<={$endSendNum}";
        }
        // 验码数
        $beginVerifyNum = I('begin_verify_num', null, 
            'mysql_real_escape_string');
        if (isset($beginVerifyNum) && $beginVerifyNum != '') {
            $where2 .= " AND IFNULL(x.verify_count,0)>={$beginVerifyNum}";
        }
        $endVerifyNum = I('end_verify_num', null, 'mysql_real_escape_string');
        if (isset($endVerifyNum) && $endVerifyNum != '') {
            $where2 .= " AND IFNULL(x.verify_count,0)<={$endVerifyNum}";
        }
        // 活动参与次数
        $beginJoinNum = I('begin_join_num', null, 'mysql_real_escape_string');
        if ($beginJoinNum != '') {
            $where .= " AND i.join_num>={$beginJoinNum}";
        }
        $endJoinNum = I('end_join_num', null, 'mysql_real_escape_string');
        if ($endJoinNum != '') {
            $where .= " AND i.join_num<={$endJoinNum}";
        }
        // 首次来源渠道
        $channelId = I('channel_id', null, 'mysql_real_escape_string');
        if ($channelId != '') {
            if ($channelId == '-1') {
                $where .= " AND i.channel_id IN(SELECT id FROM tchannel WHERE node_id='{$this->nodeId}' AND sns_type=42)";
            } else {
                $where .= " AND i.channel_id={$channelId}";
            }
        }
        // 分组
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        if (! empty($groupId)) {
            $where .= " AND i.group_id='{$groupId}'";
        }
        // 手机号
        $phoneNo = I('phone_no', null, 'mysql_real_escape_string');
        if (! empty($phoneNo)) {
            $where .= " AND i.phone_no={$phoneNo}";
        }
        // 权益
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (! empty($batchNo)) {
            $where .= " AND i.batch_no='{$batchNo}'";
        } elseif ($batchNo === '0') {
            $where .= " AND (i.batch_no = 0)";
        }
        
        $countSql = "SELECT COUNT(*) as count FROM (
                    SELECT
                      i.id,
                      i.name,
                      i.node_id,
                      i.phone_no,
                      i.join_num,
                      i.add_time
                    FROM tmember_info i
                    WHERE i.node_id = '{$this->node_id}' and {$where}
                        AND 1 = 1
                        ) AS X where {$where2}";
        // echo($countSql);
        import("ORG.Util.Page");
        $countInfo = M()->query($countSql);
        $p = new Page($countInfo['0']['count'], 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        
        $sql = "SELECT * FROM (
                SELECT
                  i.id,
                  i.name,
                  i.node_id,
                  i.phone_no,
                  i.join_num,
                  i.add_time,
                  n.sns_type,
                  n.name     AS channel_name,
                  b.name     AS group_name
                  
                FROM tmember_info i
                  LEFT JOIN tchannel n
                    ON i.channel_id = n.id
                  LEFT JOIN tmember_group b
                    ON i.group_id = b.id
                WHERE i.node_id = '{$this->node_id}' and {$where}
                    ) AS X
                WHERE {$where2}
                    ORDER BY X.add_time DESC LIMIT {$p->firstRow},{$p->listRows}";
        $memberList = M()->query($sql);
        $page = $p->show();
        // 获取所有渠道列表
        $channelList = M('tchannel')->field('id,name')
            ->where("node_id='{$this->nodeId}'")
            ->order("name")
            ->select();
        // 获取分组信息
        $groupList = M()->table('tmember_group g')
            ->field(
            "g.*,(SELECT COUNT(*) FROM tmember_info WHERE group_id=g.id AND node_id='{$this->nodeId}') as num")
            ->where("g.node_id='{$this->nodeId}' OR g.id=1")
            ->order('g.add_time')
            ->select();
        // 获取权益数量信息
        $batchList = M()->table('tmember_batch b')
            ->field(
            "b.batch_no,b.level_name,(SELECT COUNT(*) FROM tmember_info WHERE batch_no=b.batch_no AND node_id='{$this->nodeId}') as num")
            ->where("b.node_id='{$this->nodeId}' AND b.status=1")
            ->select();
        // dump($memberList);exit;
        // 粉丝数量
        $memberNum = 0;
        foreach ($groupList as $v) {
            $memberNum += $v['num'];
        }
        // 权益数量
        $batchNum = 0;
        foreach ($batchList as $v) {
            $batchNum += $v['num'];
        }
        node_log("首页+粉丝框");
        $this->assign('query_list', $memberList);
        $this->assign('channelList', $channelList);
        $this->assign('groupList', $groupList);
        $this->assign('memberNum', $memberNum);
        $this->assign('batchNum', $batchNum);
        $this->assign('batchList', $batchList);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $page); // 赋值分页输出
        $this->display();
    }

    /**
     * 下载发送错误
     */
    public function downSenddError() {
        $batch_id = I('batch_id');
        $model = M('tbatch_importdetail');
        $map = array(
            'batch_id' => $batch_id, 
            'node_id' => $this->node_id, 
            'status' => '2');
        $res = $model->where($map)
            ->field('batch_no,phone_no,ret_desc')
            ->select();
        header('Content-Type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;name=error_log.csv');
        header('Cache-Control:max-age=0');
        $header = array(
            '活动号', 
            '手机号', 
            '描述');
        $handle = fopen("php://output", 'a+');
        
        foreach ($header as $k => $v) {
            $header[$k] = function_exists('mb_convert_encoding') ? mb_convert_encoding(
                $v, 'gbk', 'utf-8') : $v;
        }
        
        $h = fputcsv($handle, $header);
        
        foreach ($res as $k => $v) {
            
            $res[$k]['ret_desc'] = function_exists('mb_convert_encoding') ? mb_convert_encoding(
                $v['ret_desc'], 'gbk', 'utf-8') : $v['ret_desc'];
            fputcsv($handle, $res[$k]);
        }
        
        fclose($handle);
    }

    /*
     * 批量导入
     */
    public function importAdd() {
        $arr = $this->getBatch();
        $this->assign('groupList', $this->getAllGroup());
        $this->assign('batch_list', $arr);
        $this->display();
    }

    /*
     * 上传文件，添加粉丝
     */
    public function importInsert() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (! empty($batchNo)) {
            $cardsInfo = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$batchNo}' AND status='1'")->find();
            if (! $cardsInfo)
                $this->error('未找到您选择的粉丝权益');
        }
        $isSendCode = I('is_send_code');
        if ($isSendCode == '1' && empty($batchNo))
            $this->error('您还没有选择粉丝权益,无法同时发送电子权益');
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        if (! empty($groupId) && $groupId !== '1') {
            $groupInfo = M('tmember_group')->where(
                "node_id='{$this->nodeId}' AND id='{$groupId}'")->find();
            if (! $groupInfo)
                $this->error('未找到您选择的分组信息');
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = C('UPLOAD_FILE.SIZE');
        $upload->allowExts = C('UPLOAD_FILE.TYPE');
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $errormsg = $upload->getErrorMsg();
            $this->error($errormsg);
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $fileUrl = $info[0]['savepath'] . $info[0]['savename'];
        }
        
        $fileName = $info[0]['savename'];
        $memberModel = M('tmember_info');
        // 获取所有粉丝信息
        $memberData = $memberModel->field('phone_no')
            ->where("node_id='{$this->nodeId}'")
            ->select();
        $existingPhone = array(); // 所有粉丝的手机，一维数组
        $impotPhone = array(); // 符合条件粉丝的手机号码，用于发码
        $importMember = array(); // 符合条件的粉丝信息
        if ($memberData) {
            foreach ($memberData as $v) {
                $existingPhone[] = $v['phone_no'];
            }
        }
        
        $row = 0; // 导入文件的总条数包括表头
        $error = '';
        $erroName = C('DOWN_TEMP') . date('YmdHis') . '.csv'; // 上传失败的用户数据
        
        $erroFileHandle = fopen($erroName, "wb");
        fwrite($erroFileHandle, chr(0XEF) . chr(0xBB) . chr(0XBF)); // 输出BOM头防止微软软件打开文件乱码,该方法对未升级的office2007无效
        if (! $erroFileHandle) {
            log::write('批量添加粉丝：错误粉丝信息文件打开失败');
            unlink($fileName);
            $this->error('系统出错');
        }
        $fileField = C('FILE_FIELD'); // 错误信息表头字段
        $fileField[] = '错误原因';
        fputcsv($erroFileHandle, $fileField);
        
        if (($handle = fopen($fileUrl, "rw")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { // 读取csv文件
                ++ $row;
                $data = utf8Array($data);
                if ($row == 1) {
                    $fileField = C('FILE_FIELD');
                    $arrDiff = array_diff_assoc($data, $fileField);
                    if (count($data) != count($fileField) || ! empty($arrDiff)) {
                        fclose($erroFileHandle);
                        fclose($handle);
                        unlink($erroName);
                        unlink($fileUrl);
                        $this->error(
                            '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                    }
                    continue;
                }
                
                // 校验各个字段
                $memberName = $data[0];
                $phoneNo = $data[1];
                $sex = $data[2];
                $birthday = $data[3];
                $address = $data[4];
                $erroInfo = '';
                if (! check_str($memberName, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '7'), $error)) {
                    $erroInfo .= "粉丝姓名{$error}";
                }
                if (! check_str($phoneNo, 
                    array(
                        'null' => false, 
                        'strtype' => 'mobile'), $error)) {
                    $erroInfo .= "手机号{$error}";
                }
                // 性别
                $sexArr = array_flip(C('SEX_ARR'));
                if ($sex != '' && ! in_array($sexArr[$sex], 
                    array(
                        '1', 
                        '2'))) {
                    $erroInfo .= '性别信息有误,请填写"男"或"女"或留空';
                }
                // 生日
                if (strtotime($birthday) === false) {
                    $erroInfo .= '生日信息有误';
                }
                
                // 是否已存在
                if (in_array($phoneNo, $existingPhone)) {
                    $erroInfo .= '手机号已经存在';
                }
                // 保证$importMember,$impotPhone中手机号不重复
                if (in_array($phoneNo, $impotPhone)) {
                    $erroInfo .= '与文件中其他手机号重复';
                }
                
                if (! empty($erroInfo)) {
                    $erroArr = array(
                        $memberName, 
                        $phoneNo, 
                        $sex, 
                        $birthday, 
                        $address, 
                        $erroInfo);
                    fputcsv($erroFileHandle, $erroArr);
                    continue;
                }
                
                // 符合条件的粉丝信息
                $importMember[] = array(
                    'node_id' => $this->nodeId, 
                    'name' => $memberName, 
                    'phone_no' => $phoneNo, 
                    'batch_no' => $batchNo, 
                    'sex' => $sexArr[$sex], 
                    'address' => $address, 
                    'birthday' => date('YmdHis', strtotime($birthday)), 
                    'years' => substr($birthday, 0, 4), 
                    'month_days' => substr(date('YmdHis', strtotime($birthday)), 
                        4, 4), 
                    'add_time' => date('YmdHis'), 
                    'age' => $this->age($birthday), 
                    'group_id' => empty($groupId) ? 1 : $groupId);
                
                // 符合条件粉丝的手机号码
                $impotPhone[] = $phoneNo;
            }
            fclose($handle);
            fclose($erroFileHandle);
            if ($row < 2) {
                unlink($erroName);
                unlink($fileUrl);
                $this->error('您上传的文件中没有找到粉丝数据!');
            }
            // 数据插入
            if (! $importMember) {
                unlink($fileUrl);
                $data = array(
                    'fail_num' => 1, 
                    'error_name' => basename($erroName));
                $this->ajaxReturn($data, '添加失败!', 1);
            }
            $totalNum = $row - 1; // 一共上传的粉丝数量
            $succNum = count($importMember); // 符合条件的粉丝
            $failNum = $totalNum - $succNum; // 失败的粉丝
                                             
            // 粉丝批次表插入
            $batchModel = M('tmember_batch_import');
            $data = array(
                'node_id' => $this->nodeId, 
                'batch_no' => $batchNo, 
                'file_name' => '', 
                'total_num' => $totalNum, 
                'succ_num' => $succNum, 
                'fail_num' => $failNum, 
                'add_time' => date('YmdHis'));
            if ($failNum != 0) {
                $data['file_name'] = basename($erroName);
            }
            // 开启事物
            $batchModel->startTrans();
            $batchId = $batchModel->add($data);
            if (! $batchId) {
                $batchModel->rollback();
                log::write('批量添加粉丝：tmember_batch_import表数据插入失败');
                unlink($erroName);
                unlink($fileUrl);
                $this->error('系统出错,添加失败');
            }
            
            // 粉丝表数据插入
            foreach ($importMember as $k => $v) {
                $result = $memberModel->add($v);
                if (! $result) {
                    $batchModel->rollback();
                    log::write('批量添加粉丝：tmember_info表数据插入失败');
                    unlink($erroName);
                    unlink($fileUrl);
                    $this->error('系统出错,添加失败');
                }
            }
            
            // 发码
            if ($isSendCode == '1') { // 发码
                                                           // 批次表
                switch ($succNum) {
                    case $succNum < 100:
                        $sendLevel = 2;
                        break;
                    case $succNum < 1000:
                        $sendLevel = 3;
                        break;
                    case $succNum < 10000:
                        $sendLevel = 4;
                        break;
                    default:
                        $sendLevel = 5;
                }
                $typeChange = C('TYPE_CHANGE');
                $data = array(
                    'batch_no' => $batchNo, 
                    'node_id' => $this->nodeId, 
                    'user_id' => $this->userId, 
                    'total_count' => $succNum, 
                    'send_level' => $sendLevel, 
                    'add_time' => date('YmdHis'), 
                    'file_md5' => md5($fileName), 
                    'file_name' => $fileName, 
                    'data_from' => '5', 
                    'info_title' => $cardsInfo['level_name'], 
                    'mms_notes' => $cardsInfo['print_info'], 
                    'print_text' => $cardsInfo['print_info'], 
                    'validate_times' => '99999', 
                    'validate_amt' => '0', 
                    'verify_begin_time' => D('Goods')->dayToDate(
                        $cardsInfo['verify_begin_date'], 
                        $typeChange[$cardsInfo['date_type']]), 
                    'verify_end_time' => D('Goods')->dayToDate(
                        $cardsInfo['verify_end_date'], 
                        $typeChange[$cardsInfo['date_type']]));
                $importModel = M('tbatch_import');
                $importId = $importModel->add($data);
                if (! $importId) {
                    $batchModel->rollback();
                    log::write('批量添加粉丝：tbatch_import表数据插入失败');
                    unlink($erroName);
                    unlink($fileUrl);
                    $this->error('系统出错,添加失败');
                }
                
                // 详情表数据插入
                $importdetailModel = M('tbatch_importdetail');
                foreach ($impotPhone as $v) {
                    $requestId = get_request_id();
                    $data = array(
                        'batch_no' => $batchNo, 
                        'batch_id' => $importId, 
                        'node_id' => $this->nodeId, 
                        'phone_no' => $v, 
                        'add_time' => date('YmdHis'), 
                        'request_id' => $requestId);
                    $result = $importdetailModel->add($data);
                    if (! $importId) {
                        $batchModel->rollback();
                        log::write('批量添加粉丝：tbatch_importdetail表数据插入失败');
                        unlink($erroName);
                        unlink($fileUrl);
                        $this->error('系统出错,添加失败');
                    }
                }
                $sendInfo = "<br/>正在发送粉丝权益卡，你可以查看发卡结果..";
            }
            $batchModel->commit();
            unlink($fileUrl);
            if ($failNum == 0) {
                unlink($erroName);
            }
            $data = array(
                'is_send_code' => $isSendCode, 
                'batch_id' => $importId, 
                'fail_num' => $failNum, 
                'succNum' => $succNum, 
                'error_name' => basename($erroName));
            node_log("粉丝框:批量粉丝添加，添加数量：" . $succNum . "个");
            $this->ajaxReturn($data, 
                "总计导入{$totalNum}个粉丝,成功{$succNum}个,失败{$failNum}个{$sendInfo}", 1);
        }
        log::write('批量添加粉丝：上传文件打开失败');
        fclose($erroFileHandle);
        fclose($handle);
        unlink($erroName);
        unlink($fileUrl);
        $this->error('系统出错');
    }

    /**
     * 粉丝批量添加记录
     */
    public function batchInfo() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string,trim');
        if (! empty($batchNo)) {
            $map['i.batch_no'] = $batchNo;
        }
        // 处理特殊查询字段
        $beginDate = I('begin_date', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['i.add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_date', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' i.add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['i.node_id'] = $this->nodeId;
        import("ORG.Util.Page");
        $count = M()->table("tmember_batch_import i")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table("tmember_batch_import i")->field('b.level_name,i.*')
            ->join(
            'tmember_batch b ON i.batch_no=b.batch_no AND i.node_id=b.node_id ')
            ->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // echo M()->getLastSql();exit;
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="8">无数据</td></span>');
        $this->assign('batch_list', $this->getBatch());
        $this->display();
    }

    /**
     * 批量发码功能开始
     */
    public function batchSendCode() {
        $searchData = array();
        $memerCards = $this->getBatch();
        $batchNo = I('batch_no', null, 'mysql_real_escape_string,trim');
        if (! empty($batchNo)) {
            $map['i.batch_no'] = $batchNo;
            $searchData['batch_no'] = $batchNo;
        }
        $sendStatus = I('send_status', null, 'mysql_real_escape_string,trim');
        if ($sendStatus == '1') {
            $map['i.request_id'] = array(
                'neq', 
                '');
            $searchData['request_id'] = array(
                'neq', 
                '');
        } elseif ($sendStatus == '2') {
            $map['i.request_id'] = '';
            $searchData['request_id'] = '';
        }
        // 处理特殊查询字段
        $beginDate = I('begin_date', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['i.add_time'] = array(
                'egt', 
                $beginDate . '000000');
            $searchData['add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_date', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' i.add_time'] = array(
                'elt', 
                $endDate . '235959');
            $searchData[' add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $groupId = I('group_id', null, 'mysql_real_escape_string,trim');
        if (! empty($groupId)) {
            $map['i.group_id'] = $groupId;
            $searchData['group_id'] = $groupId;
        }
        $map['i.node_id'] = $this->nodeId;
        $searchData['node_id'] = $this->nodeId;
        $searchData['status'] = '0';
        
        $sendCode = I('send_code', null);
        if ($sendCode == 1) {
            $count = M('tmember_info')->where($searchData)->count();
            if ($count < 1)
                $this->error('没有找到符合查询条件的粉丝');
            session('searchData', $searchData);
            $data = array(
                'count' => $count, 
                'batch_no' => $searchData['batch_no']);
            $this->ajaxReturn($data, '', 1);
            exit();
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tmember_info i')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        $list = M()->table('tmember_info i')
            ->field('i.*,g.name as group_name')
            ->join("tmember_group g ON i.group_id=g.id")
            ->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M()->getLastSql();
        $this->assign('batch_arr', $this->getBatcharr());
        $this->assign('batch_list', $memerCards);
        $this->assign('sex_arr', C('SEX_ARR'));
        $this->assign('query_list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign('groupList', $this->getAllGroup());
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('mapcount', $mapcount);
        $this->display();
    }

    /**
     * 批量发卡第2步
     */
    public function batchSendCodeStarts() {
        $selectBatchNo = I('batch_no', null, 'mysql_real_escape_string'); // 要下发的粉丝卡
        if (empty($selectBatchNo))
            $this->error('请选择要发放的粉丝权益等级!');
        $cardsInfo = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND batch_no='{$selectBatchNo}' AND status='1'")->find();
        if (! $cardsInfo)
            $this->error('未找到您选择的粉丝权益卡名称');
        if (! $this->checkLevel($selectBatchNo))
            $this->error('无效的粉丝权益或该粉丝权益已过期');
            // 短彩信内容信息
        $error = '';
        $mmsTitle = I('mms_title', null);
        if (! check_str($mmsTitle, 
            array(
                'null' => false, 
                'maxlen_cn' => '10'), $error)) {
            $this->error("彩信标题{$error}");
        }
        $mmsInfo = I('mms_info');
        if (! check_str($mmsInfo, 
            array(
                'null' => false, 
                'maxlen_cn' => '100'), $error)) {
            $this->error("彩信内容{$error}");
        }
        
        $searchData = session('searchData');
        session('searchData', null);
        if (empty($searchData))
            $this->error('参数错误!');
            // 获取符合条件的粉丝
        $memberModel = M('tmember_info');
        $memberData = $memberModel->where($searchData)->select();
        
        // echo M()->getLastSql();exit;
        $updateMember = array(); // 需要更新等级的粉丝
        $sendPhone = array(); // 发码的粉丝
        foreach ($memberData as $k => $v) {
            // 粉丝当前是否和当前发卡的等级一致
            if ($v['batch_no'] != $selectBatchNo) { // 不相等更新粉丝等级
                $updateMember[] = array(
                    'id' => $v['id'], 
                    'batch_no' => $selectBatchNo, 
                    'update_time' => date('YmdHis'));
            }
            
            // 是否已经发过码
            if (! empty($v['request_id'])) { // 已经发过码,要先撤消
                $sendPhone[] = array(
                    'batch_no' => $selectBatchNo, 
                    'node_id' => $this->nodeId, 
                    'request_id' => get_request_id(), 
                    'orirequest_id' => $v['request_id'], 
                    'phone_no' => $v['phone_no'], 
                    'add_time' => date('YmdHis'));
            } else { // 没发过码
                $sendPhone[] = array(
                    'batch_no' => $selectBatchNo, 
                    'node_id' => $this->nodeId, 
                    'request_id' => get_request_id(), 
                    'phone_no' => $v['phone_no'], 
                    'add_time' => date('YmdHis'));
            }
        }
        // 数据插入
        $importModel = M('tbatch_import');
        $importdetailModel = M('tbatch_importdetail');
        $importModel->startTrans();
        // 更新粉丝等级
        if (! empty($updateMember)) {
            foreach ($updateMember as $v) {
                $result = $memberModel->save($v);
                if ($result === false) {
                    $importModel->rollback();
                    log::write('批量发码：更新粉丝等级数据失败');
                    $this->error('系统出错,发送失败');
                }
            }
        }
        // 发码批次表
        // 批次表
        $succNum = count($sendPhone);
        switch ($succNum) {
            case $succNum < 100:
                $sendLevel = 2;
                break;
            case $succNum < 1000:
                $sendLevel = 3;
                break;
            case $succNum < 10000:
                $sendLevel = 4;
                break;
            default:
                $sendLevel = 5;
        }
        $typeChange = C('TYPE_CHANGE');
        $data = array(
            'batch_no' => $selectBatchNo, 
            'user_id' => $this->userId, 
            'node_id' => $this->nodeId, 
            'total_count' => $succNum, 
            'send_level' => $sendLevel, 
            'add_time' => date('YmdHis'), 
            'data_from' => '5', 
            'info_title' => $mmsTitle, 
            'mms_notes' => $mmsInfo, 
            'print_text' => $cardsInfo['print_info'], 
            'validate_times' => '99999', 
            'validate_amt' => '0', 
            'verify_begin_time' => D('Goods')->dayToDate(
                $cardsInfo['verify_begin_date'], 
                $typeChange[$cardsInfo['date_type']]), 
            'verify_end_time' => D('Goods')->dayToDate(
                $cardsInfo['verify_end_date'], 
                $typeChange[$cardsInfo['date_type']]));
        $batchId = $importModel->add($data);
        if ($result === false) {
            $importModel->rollback();
            log::write('批量发码：tbatch_import表数据插入失败');
            $this->error('系统出错,发送失败');
        }
        // 详情表数据插入
        foreach ($sendPhone as $v) {
            $v['batch_id'] = $batchId;
            $result = $importdetailModel->add($v);
            if ($result === false) {
                $importModel->rollback();
                log::write('批量发码：tbatch_importdetail表数据插入失败');
                $this->error('系统出错,发送失败');
            }
        }
        $importModel->commit();
        node_log("粉丝框:批量发送粉丝权益卡，名称：" . $cardsInfo['level_name'] . "个");
        $this->ajaxReturn(array(
            'batch_id' => $batchId), '', 1);
        exit();
    }

    public function add() {
        $arr = $this->getBatch();
        $this->assign('groupList', $this->getAllGroup());
        $this->assign('batch_list', $arr);
        $this->display();
    }

    public function insert() {
        $error = '';
        $name = I('name');
        if (! check_str($name, 
            array(
                'null' => true, 
                'maxlen_cn' => '7'), $error)) {
            $erroInfo .= "粉丝姓名{$error}";
        }
        $phone_no = I('phone_no');
        if (! check_str($phone_no, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $erroInfo .= "手机号{$error}";
        }
        $model = M('tmember_info');
        $wh_arr = array(
            'node_id' => $this->node_id, 
            'phone_no' => $phone_no);
        $id = $model->where($wh_arr)->getField('id');
        if (! empty($id)) {
            $this->error("手机号已经存在！");
        }
        $batch_no = I('batch_no', null, 'mysql_real_escape_string');
        if (! empty($batch_no)) {
            $cardsInfo = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$batch_no}' AND status='1'")->find();
            if (! $cardsInfo)
                $this->error('未找到您选择的粉丝权益卡等级');
        }
        $group_id = I('group_id', null, 'intval');
        if (! empty($group_id) && $group_id !== '1') {
            $groupInfo = M('tmember_group')->where(
                "node_id='{$this->nodeId}' AND id={$group_id}")->find();
            if (! $groupInfo)
                $this->error('未找到您选择的粉丝分组');
        }
        $address = I('address');
        $sex = I('sex');
        $birthday = I('birthday');
        if (! check_str($birthday, 
            array(
                'null' => true, 
                'strtype' => 'datetime', 
                'format' => 'Ymd'), $error)) {
            $this->error("粉丝生日{$error}");
        }
        $birthday = (strlen($birthday) == '8' && ! empty($birthday)) ? $birthday .
             '000000' : $birthday;
        $isSendCode = I('is_send_code', null);
        if ($isSendCode == '1' && empty($batch_no))
            $this->error('您还没有选择粉丝权益,无法同时发送电子权益');
        $data = array(
            'node_id' => $this->node_id, 
            'name' => $name, 
            'phone_no' => $phone_no, 
            'batch_no' => $batch_no, 
            'sex' => $sex, 
            'birthday' => $birthday, 
            'years' => substr($birthday, 0, 4), 
            'month_days' => substr($birthday, 4, 4), 
            'age' => $this->age($birthday), 
            'group_id' => empty($group_id) ? 1 : $group_id, 
            'address' => $address, 
            'add_time' => date('Ymdhis'));
        // dump($data);exit;
        $res = $model->add($data);
        if (! $res) {
            $this->error("系统错误,添加失败");
        }
        if ($isSendCode == '1') {
            $transId = get_request_id();
            $result = $this->sendCode($phone_no, $batch_no, $transId);
            if ($result === true) {
                $res = $model->where("id={$res}")->save(
                    array(
                        'request_id' => $transId));
                if ($res === false)
                    $this->error('系统错误,添加失败');
                node_log("粉丝框:粉丝添加，姓名：" . $name . ",手机号：" . $phone_no);
                $this->success("粉丝添加成功,发码成功!");
                exit();
            } else {
                node_log("粉丝框:粉丝添加，姓名：" . $name . ",手机号：" . $phone_no);
                $this->success('粉丝添加成功,发码失败!原因：' . $result);
                exit();
            }
        }
        node_log("粉丝框:粉丝添加，姓名：" . $name . ",手机号：" . $phone_no);
        $this->success("粉丝添加成功！");
    }

    /**
     * 粉丝权益卡编辑
     */
    public function save() {
        $id = I('id', null, 'mysql_real_escape_string');
        if (empty($id)) {
            $this->error("参数错误!");
        }
        $erroInfo = '';
        $name = I('name', null);
        if (! check_str($name, 
            array(
                'null' => true, 
                'maxlen_cn' => '7'), $error)) {
            $erroInfo .= "粉丝姓名{$error}";
        }
        $phoneNo = I('phone_no', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $erroInfo .= "手机号{$error}";
        }
        $birthday = I('birthday');
        if (! check_str($birthday, 
            array(
                'null' => true, 
                'strtype' => 'datetime', 
                'format' => 'Ymd'), $error)) {
            $this->error("生日日期{$error}");
        }
        $sex = I('sex', null);
        if (! check_str($sex, 
            array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '3'), $error)) {
            $this->error("性别{$error}");
        }
        $batchNo = I('batch_no', null);
        if (! check_str($batchNo, array(
            'null' => true), $error)) {
            $this->error("粉丝权益{$error}");
        }
        $model = M('tmember_info');
        $memberInfo = $model->where(
            "node_id='{$this->nodeId}' AND id='{$id}' AND status=0")->find();
        if (! $memberInfo) {
            $this->error('未找到该粉丝信息或粉丝应经停用!');
        }
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        if (! empty($groupId) && $groupId !== '1') {
            $groupInfo = M('tmember_group')->where(
                "id='{$groupId}' AND node_id='{$this->nodeId}'")->find();
            if (! $groupInfo)
                $this->error('未找到该分组信息');
        }
        
        $where = array(
            'node_id' => $this->node_id, 
            'phone_no' => $phoneNo, 
            'id' => array(
                'neq', 
                $id));
        $res = $model->where($where)->find();
        if (! empty($res)) {
            $this->error("手机号重复！");
        }
        $data = array(
            'name' => $name, 
            'phone_no' => $phoneNo, 
            'batch_no' => $batchNo, 
            'sex' => $sex, 
            'birthday' => $birthday . '000000', 
            'years' => substr($birthday, 0, 4), 
            'month_days' => substr($birthday, 4, 4), 
            'age' => $this->age($birthday), 
            'group_id' => $groupId, 
            'update_time' => date('Ymdhis'), 
            'address' => I('address'));
        // 修改了等级并且已经发过码
        if ($batchNo != $memberInfo['batch_no'] &&
             ! empty($memberInfo['request_id'])) {
            // 撤消以前发的码
            $result = $this->cancelCode($memberInfo['request_id']);
            if ($result !== true)
                $this->error("粉丝信息修改失败:{$result}");
            $data['request_id'] = '';
            // 重新发码
            if (! empty($batchNo)) {
                // 检查是否有新等级的粉丝权益卡
                $result = M('tmember_batch')->where(
                    "node_id='{$this->nodeId}' AND batch_no='{$batchNo}' AND status=1")->find();
                if ($result) {
                    $transId = get_request_id();
                    $result = $this->sendCode($phoneNo, $batchNo, $transId);
                    if ($result !== true) {
                        log::write("粉丝编辑：{$phoneNo}发卡失败!");
                        $codeErr = "发码失败{$result}";
                    } else {
                        $data['request_id'] = $transId;
                        $codeErr = "发卡成功!";
                    }
                }
            }
        }
        $resutl = $model->where("id='{$id}'")->save($data);
        if ($resutl === false) {
            $this->error('系统错误,粉丝信息编辑失败');
        } else {
            node_log("粉丝框:粉丝编辑，姓名：" . $name . ",手机号：" . $phoneNo);
            $this->success("粉丝信息更新成功！{$codeErr}");
        }
    }

    public function view() {
        $id = I('id');
        if (empty($id)) {
            $this->error("关键参数ID为空");
        }
        $model = M('tmember_info');
        $map = array(
            'id' => $id);
        $all = $model->where($map)->select();
        if (count($all) == 1) {
            $info = $all['0'];
        } else {
            $this->error("查询详情错误！");
        }
        $barcode_map = array(
            'node_id' => $this->node_id, 
            'phone_no' => $info['phone_no'], 
            'trans_type' => '0001', 
            'status' => '0');
        $barcode_model = M('tbarcode_trace');
        $send_count = $barcode_model->where($barcode_map)->count();
        $pos_map = array(
            'node_id' => $this->node_id, 
            'phone_no' => $info['phone_no'], 
            'trans_type' => '0', 
            'status' => '0');
        $pos_model = M('tpos_trace');
        $verify_count = $pos_model->where($pos_map)->count();
        // 当前分组
        $groupInfo = M('tmember_group')->where("id={$info['group_id']}")->find();
        // 当前渠道
        if (empty($info['channel_id'])) {
            $channelName = '手动添加';
        } else {
            $channelName = M('tchannel')->where("id={$info['channel_id']}")->getField(
                'name');
        }
        // 粉丝卡特权
        $printInfo = M('tmember_batch')->where(
            "batch_no={$info['batch_no']} AND node_id='{$this->nodeId}'")->getField(
            'print_info');
        
        // $info['add_time'] = dateformat('Y-m-d',$info['add_time']);
        $batch_arr = $this->getBatch();
        $this->assign('info', $info);
        $this->assign('send_count', $send_count);
        $this->assign('verify_count', $verify_count);
        $this->assign('batch_arr', $this->getBatcharr());
        $this->assign('batch_list', $batch_arr);
        $this->assign('groupInfo', $groupInfo);
        $this->assign('channelName', $channelName);
        $this->assign('printInfo', $printInfo);
        $this->assign('sex_arr', C('SEX_ARR'));
        $this->assign('groupList', $this->getAllGroup());
        $this->display();
    }
    
    // 发码流水(已作废)
    public function sendCodeDetail() {
        $map = array(
            't.node_id' => $this->nodeId, 
            't.data_from' => array(
                'in', 
                '5,7'));
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['t.phone_no'] = $mobile;
        }
        $batchName = I('batch_name', '', 'mysql_real_escape_string');
        if ($batchName != '') {
            $map['b.batch_name'] = array(
                'like', 
                "%{$batchName}%");
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tbarcode_trace t')
            ->join(
            'tbatch_info b ON t.batch_no = b.batch_no AND t.node_id=b.node_id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        $list = M()->table('tbarcode_trace t')
            ->field('t.*,b.batch_name')
            ->join(
            'tbatch_info b ON t.batch_no = b.batch_no AND t.node_id=b.node_id')
            ->where($map)
            ->order('t.trans_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M()->getLastSql();exit;
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销', 
            '0003' => '重发');
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    // 粉丝权益卡批量发码记录
    public function membeSendBatch() {
        $map = array(
            'i.node_id' => $this->nodeId, 
            'i.data_from' => '5');
        $batchid = I('batch_id', null, 'mysql_real_escape_string');
        if (! empty($batchid)) {
            $map['i.batch_id'] = $batchid;
        }
        $batchName = I('batch_name', '', 'mysql_real_escape_string');
        if ($batchName != '') {
            $map['b.batch_name'] = array(
                'like', 
                "%{$batchName}%");
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tbatch_import i')
            ->join(
            'tgoods_info b ON i.batch_no = b.batch_no AND i.node_id=b.node_id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table('tbatch_import i')
            ->field('i.*,b.goods_name')
            ->join(
            'tgoods_info b ON i.batch_no = b.batch_no AND i.node_id=b.node_id')
            ->where($map)
            ->order('i.batch_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // dump($list);exit;
        $status = array(
            '0' => '未发卡', 
            '1' => '发卡中', 
            '2' => '已发卡', 
            '3' => '已发卡', 
            '9' => '发卡失败');
        
        $this->assign('batch_list', $this->getBatch());
        $this->assign('status', $status);
        $this->assign('check_status', 
            array(
                '0' => '未审核', 
                '1' => '审核通过', 
                '2' => '审核拒绝'));
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    /**
     * 阻止重复发送粉丝权益卡
     */
    public function memberSendStatus() {
        $dataInfo = M('tbatch_import')->where(
            "node_id='{$this->node_id}' AND data_from='5' AND status<'2'")->find();
        if ($dataInfo) {
            return false; // 系统检测到有一批次粉丝权益卡正在发送中,为了确保不重复发送粉丝权益卡，请您稍后再操作!
        }
        return true;
    }
    
    // 通过生日计算年龄
    // 计算年龄
    public function age($mydate) {
        $birth = date('Y-m-d', strtotime($mydate));
        if (! check_str($birth, 
            array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => 'Y-m-d'))) {
            return 0;
        }
        list ($by, $bm, $bd) = explode('-', $birth);
        $cm = date('n');
        $cd = date('j');
        $age = date('Y') - $by - 1;
        if ($cm > $bm || $cm == $bm && $cd > $bd)
            $age ++;
        return $age;
    }
    
    // 分组添加和编辑
    public function groupSave() {
        $groupId = I('group_id', null, 'intval');
        $groupName = I('group_name', null, 'mysql_real_escape_string');
        $error = "";
        if (! check_str($groupName, 
            array(
                'null' => false, 
                'maxlen_cn' => '8'), $error)) {
            $this->error("分组名称{$error}");
        }
        $groupModel = M('tmember_group');
        if (empty($groupId)) { // 添加
                               // 名称重复
            $count = $groupModel->where(
                "name='{$groupName}' AND node_id='{$this->nodeId}'")->count();
            if ($count > 0)
                $this->error('分组名称重复');
            $data = array(
                'name' => $groupName, 
                'node_id' => $this->nodeId, 
                'add_time' => date('YmdHis'));
            $result = $groupModel->add($data);
            if ($result) {
                node_log("粉丝框:分组添加，分组名：" . $groupName);
                $this->success('创建成功');
            } else {
                $this->error('系统出错,创建失败');
            }
        } else { // 编辑
            $groupInfo = $groupModel->where(
                "id='{$groupId}' AND node_id='{$this->nodeId}' AND id<>1")->find();
            if (! $groupInfo)
                $this->error('未找到该分组信息');
            $count = $groupModel->where(
                "name='{$groupName}' AND node_id='{$this->nodeId}' AND id<>{$groupInfo['id']}")->count();
            if ($count > 0)
                $this->error('分组名称重复');
            $data = array(
                'name' => $groupName);
            $result = $groupModel->where("id='{$groupId}'")->save($data);
            if ($result === false)
                $this->error('系统出错,修改失败');
            node_log("粉丝框:分组修改，分组名：" . $groupName);
            $this->success('修改成功');
        }
    }
    
    // ajax获取分组
    public function ajaxGroup() {
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        $groupInfo = M('tmember_group')->where(
            "id='{$groupId}' AND node_id='{$this->nodeId}' AND id<>1")->find();
        if (! $groupInfo)
            $this->error('未找到该分组信息');
        $this->ajaxReturn($groupInfo, '', 1);
    }
    
    // 获取商户的所有分组信息
    public function getAllGroup() {
        $groupInfo = M('tmember_group')->where(
            "node_id='{$this->nodeId}' OR id=1")->select();
        return $groupInfo;
    }
    // 分组删除
    public function delGroup() {
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        $groupModel = M('tmember_group');
        $groupInfo = $groupModel->where(
            "id='{$groupId}' AND node_id='{$this->nodeId}' AND id<>1")->find();
        if (! $groupInfo)
            $this->error('未找到该分组信息');
            // 分组下有会员无法删除
        $count = M('tmember_info')->where(
            "group_id='{$groupId}' AND node_id='{$this->nodeId}'")->count();
        if ($count > 0)
            $this->error('该分组下有粉丝信息,无法删除');
        $result = $groupModel->where(
            "id='{$groupId}' AND node_id='{$this->nodeId}'")->delete();
        if ($result === false)
            $this->error('系统出错,删除失败');
        node_log("粉丝框:分组删除，分组id：" . $groupId);
        $this->success('删除成功');
    }
    
    // 给分组添加会员
    public function memberGroupAdd() {
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        $memberStr = I('member_str', null);
        if ($groupId !== '1') {
            $groupInfo = M('tmember_group')->where(
                "id='{$groupId}' AND node_id='{$this->nodeId}'")->find();
            if (! $groupInfo)
                $this->error('未找到该分组信息');
        }
        $memberArr = explode(',', $memberStr);
        array_pop($memberArr);
        if (! is_array($memberArr) || empty($memberArr))
            $this->error('数据信息有误');
        foreach ($memberArr as $v) {
            M('tmember_info')->where("id={$v} AND node_id='{$this->nodeId}'")->save(
                array(
                    'group_id' => $groupId));
        }
        node_log("粉丝框:添加分组会员，分组id：" . $groupId . "会员:" . $memberStr);
        $this->success('添加成功');
    }
    
    // 更细权益卡短信标题和内容
    public function updateMms($mmsTitle, $mmsInfo, $batchNo) {
        $data = array(
            'info_title' => $mmsTitle, 
            'using_rules' => $mmsInfo);
        $result = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND batch_no='{$batchNo}'")->save($data);
        if ($result === false) {
            M()->rollback();
            return false;
        }
        // 更新batch_info表数据
        $data = array(
            'mms_title' => $mmsTitle, 
            'mms_text' => $mmsInfo, 
            'update_time' => date('YmdHis'));
        $result = M('tgoods_info')->where(
            "node_id='{$this->nodeId}' AND batch_no='{$batchNo}'")->save($data);
        if ($result === false) {
            M()->rollback();
            return false;
        }
        return true;
    }
}