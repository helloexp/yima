<?php

/*
 * 粉丝中心 @author zs
 */
class MemberAction extends BaseAction {

    public $BATCH_TYPE = '52';
    // 原来的为4，由于4的这种batchType取goodsInfo的短彩信的数据，不然永远不会中奖，所以新增一个batchType
    public $_authAccessMap = '*';

    public $cjSetModel;

    /**
     * 自定义采集用
     * @var collectModel
     */
    public $collectModel;
    /**
     * @var MemberInstallModel;
     */
    public $mInsModel;

    public function _initialize() {
        parent::_initialize();
        $this->collectModel = D('Collect'); //自定义采集用
        $this->mInsModel = D("MemberInstall", "Model");

        $allArticle = D('TymNews')->getArticleTitle(61, false, 0, 4);
        $this->assign("allArticle", $allArticle);
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->cjSetModel = D('CjSet');
    }

    /**
     * [beforeCheckAuth 提前校验权限]
     *
     * @return [type] [description]
     */
    public function beforeCheckAuth() {
        if ($this->wc_version == 'v0') {
            $this->error('尊敬的旺财用户，您没有使用该功能的权限。需要您完成下面的认证信息！', 
                array(
                    '立即认证' => U('Home/AccountInfo/index')));
        }
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
        redirect(U('infoCenter'));
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
        $res = $model->where($map)->field('batch_no,phone_no,ret_desc')->select();
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

    /**
     * 招募会员
     */
    public function recruit() {

        $batch_status = I('batch_status',0);//搜索的活动状态
        $sortName = I('sortName',null);//排序内容
        $state = I('state',0);//升序降序


        $mInfoModel = M('tmarketing_info');

        $map = array(
            'm.node_id' => $this->node_id,
            // 're_type' => '0',//0普通渠道会员招募,1微信渠道会员招募(不知道还用不用) todo
            'm.batch_type' => $this->BATCH_TYPE);

        $name = I('market_name', '');//搜索的活动名称
        if ($name != '') {
            $map['m.name'] = array('like', '%' . $name . '%');
            $this->assign('jg_name', $name);
        }

        switch($batch_status){
            case 1:
                $map['start_time'] = array('gt', date('Ymd').'235959');
                $map['status'] = '1';
                break;
            case 2:
                $map['m.start_time'] = array('elt', date('Ymd').'000000');
                $map['m.end_time'] = array('egt', date('Ymd').'235959');
                $map['m.status'] = '1';
                break;
            case 3:
                $map['m.status'] = '1';
                $map['m.end_time'] = array('LT',date('Ymd').'000000');
                break;
            case 4:
                $map['m.status'] = '2';
                break;
            default:
                break;
        }

        $this->assign('batch_status', $batch_status);
        // 按活动创建日期进行搜索 先注释掉 不删 万一产品抽风又要加上呢
        /*$beginDate = I('badd_time', null, 'mysql_real_escape_string,trim');
        $beginTime = '';
        if (! empty($beginDate)) {
            $beginTime = array(
                'egt', 
                $beginDate . '000000');
            $map['add_time'] = $beginTime;
            $this->assign('badd_time', $beginDate);
        }
        $endDate = I('eadd_time', null, 'mysql_real_escape_string,trim');
        $endTime = '';
        if (! empty($endDate)) {
            $endTime = array(
                'elt', 
                $endDate . '235959');
            $map['add_time'] = $endTime;
            $this->assign('eadd_time', $endDate);
        }
        if ($beginTime && $endTime) {
            $map['add_time'] = array(
                $beginTime, 
                $endTime);
        }*/
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $mInfoModel->alias('m')->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出

        $orderName = 'start_time';
        $order = array( $orderName => 'desc');


        switch($sortName){
            case 'pv':
                //访问量
                $orderName = 'click_count';
                break;
            case 'sv';
                //奖品发放数
                $orderName = 'send_count';
                break;
            case 'prnum';
                //剩余奖品
                $orderName = 'remainamt';
                break;
            case 'iv':
                //验证数
                $orderName = 'tp_sum';
                break;
        }
        switch($state) {
            case 1:
                $order = array( $orderName => 'desc');
                break;
            case 2:
                $order = array( $orderName => 'asc');
                break;
            default:
                break;
        }

        $this->assign('sortName',$sortName);
        $this->assign('state',$state);

        $list = $mInfoModel->alias('m')
                ->join('LEFT JOIN (SELECT SUM(tb.remain_num) AS remainAmt,tb.m_id FROM tbatch_info tb LEFT JOIN tcj_batch tcj ON tcj.`b_id` = tb.`id` WHERE tb.node_id = '.$this->node_id.' AND tcj.`status` = 1 GROUP BY tb.`m_id`) b ON b.m_id=m.id ')
                ->join('LEFT JOIN ( SELECT a.m_id,SUM(verify_num) AS tp_sum FROM tbatch_info a LEFT JOIN tpos_day_count b ON a.id = b.b_id WHERE  b.node_id = '.$this->node_id.' GROUP BY a.m_id) X ON m.id = x.m_id')
                ->where($map)
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->getField(
            'm.id,m.add_time,m.start_time,m.end_time,m.name,m.status,m.send_count,m.click_count,m.member_sum,m.batch_type,b.remainAmt,IFNULL(x.tp_sum,0) tp_sum',
            true);
        //$mIdArr = array_keys($list);
        // 查找活动有没有被发布
/*        $publishedMIdArr = M('tbatch_channel')->where(
            array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => array(
                    'in', 
                    $mIdArr)))
            ->distinct(true)
            ->getField('batch_id', true);
        // 可能会没有值或者只有一个单独的值
        if (! $publishedMIdArr) {
            $publishedMIdArr = array();
        } elseif (! is_array($publishedMIdArr)) {
            $publishedMIdArr = array(
                $publishedMIdArr);
        }*/
        $daystatModel = M('tdaystat');
        $posDayCountModel = M('tpos_day_count');
        $batchInfoModel = M('tbatch_info');

        $batchStatusArr = array(
                '0' => '全部',
                '1' => '未开始',
                '2' => '进行中',
                '3' => '已过期',
                '4' => '未开启'
        );

        /*elseif(! in_array($key, $publishedMIdArr)){
            // 活动id不在tbatch_channel的batch_id的表示未发布
            $list[$key]['left_days_txt2'] = '未发布';
        }*/

        foreach ($list as $key => $val) {
            // 剩余天数
            //$left_days = ceil(((strtotime($val['end_time']) + 1) - time()) / 86400);

            if ($val['status'] == '2') {
                $list[$key]['left_days_txt2'] = '未开启';
            }elseif($val['start_time'] <= date('Ymd').'000000' && $val['end_time'] >= date('Ymd').'235959'){
                $list[$key]['left_days_txt2'] = '进行中';
            }elseif($val['start_time'] > date('Ymd').'235959'){
                $list[$key]['left_days_txt2'] = '未开始';
            }elseif($val['end_time'] < date('Ymd').'000000'){
                $list[$key]['left_days_txt2'] = '已过期';
            }

            /*$verify_count = $posDayCountModel->alias('a')
                ->join('tbatch_info as b on b.id = a.b_id')
                ->where(array(
                'b.m_id' => $val['id']))
                ->sum('verify_num');
            $list[$key]['verify_count'] = $verify_count ? $verify_count : 0;*/
            //是否有发送奖品失败的记录
            $failedRecord = D('SendAwardTrace')->getFailedRecord($this->node_id, $val['id']);
            $list[$key]['failedRecordFlag'] = $failedRecord ? 1 : 0;
        }


        $this->assign('batch_status_arr',$batchStatusArr);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display('recruit');
    }


    /**
     * @return bool 停用启用修改
     */
    public function recruitStop()
    {
        $id = I('m_id',null);
        $status = I('status',null);
        $status_name = I('status_name',null);

        if(!$id || !$status ){
            $this->ajaxReturn(0);
        }
        $whe['id'] = $id;
        $data['status'] = $status;
        M('tmarketing_info')->where($whe)->save($data);
        $this->ajaxReturn(1);
    }

    /**
     * 设置活动基础信息
     */
    public function setActBasicInfo() {
        cookie('temporaryMemberCustomFields', ' ', -1);
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $node_name = $nodeInfo['node_name'];
        $node_logo = get_upload_url($nodeInfo['head_photo']);
        $m_id = I('m_id', '', 'trim');
        $join_mode = 0;
        if ($m_id) {
            $basicInfo = M('tmarketing_info')->where(array('node_id' => $this->node_id, 'id' => $m_id))->find();
            $node_name = $basicInfo['node_name'];
            $node_logo = $basicInfo['log_img'];
            $join_mode = $basicInfo['join_mode'];
            // 查询 点击“微信号”是否需要弹框//window_id为12表示这里要用到的弹窗号
            $wx_bd = M('tpop_window_control')->where(array('node_id' => $this->node_id, 'window_id' => 12))->find() ? 1 : 0;
            $this->assign('wx_bd', $wx_bd);
            $this->assign('act_name', $basicInfo['name']); // 活动名称
            $this->assign('act_time_from', substr($basicInfo['start_time'], 0, 8)); // 活动开始时间
            $this->assign('act_time_to', substr($basicInfo['end_time'], 0, 8)); // 活动结束时间
            $this->assign('introduce', $basicInfo['wap_info']); // 活动说明
            $this->assign('m_id', $basicInfo['id']);
            $this->assign('bg_style', $basicInfo['bg_style']); // 背景图的第几个,第一第二个是供选择的,第三个为自定义的
            $this->assign('bg_img', $basicInfo['bg_pic']); // 背景图
            $this->assign('template', $basicInfo['page_style']); // 模板样式
            $this->assign('memberCard_id', $basicInfo['member_card_id']);
            $this->assign('configData', unserialize($basicInfo['config_data']));

            $setedSustomFields = M()->table("tcollect_question_new tcqn")
                ->join('tcollect_question_field tcqf ON tcqn.field_id = tcqf.id')
                ->field('tcqn.is_required, tcqf.text, tcqf.name')
                ->where(array('tcqn.m_id'=>$m_id, 'tcqn.node_id'=>$this->node_id, 'tcqn.is_delete'=>'0'))
                ->order('tcqn.sort ASC')->select();
            foreach($setedSustomFields as $key=>$val){
                $setedSustomFields[$key]['name'] = $this->_getJsName($val['name']);
            }
            $this->assign('setedSustomFields', $setedSustomFields);
        }
        
        $mIns = D('MemberInstall', 'Model');
        $list = $mIns->getMemberCards($this->node_id); //查询机构下的所有会员卡

        $this->assign('cardList', $list);
        
        $customFields = $mIns->existCustomFields($this->node_id);
        $this->assign('createdCustomNum', (count($customFields)-5));
        $this->assign('customFields', json_encode($customFields));
        $isReEdit = I('isReEdit', ($m_id ? '1' : '0'));
        $this->assign('isReEdit', $isReEdit);
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $this->assign('join_mode', $join_mode); // 参与方式0手机 1微信
        $stepBar = D('CjSet')->getActStepsBar(ACTION_NAME, $m_id, '', $isReEdit, array('setActBasicInfo' => '基础信息', 'setPrize' => '奖项设定','publish'=>'活动发布'));
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        $this->display();
    }
    
    /**
     * 创建自定义字段暂存 cookie,临时存储
     */
    public function createCustomFields(){
        $key = I('post.key', '', 'string');
        
        $temporaryData = cookie('temporaryMemberCustomFields');
        if($temporaryData != ''){
            $temporaryData = json_decode($temporaryData, true);
        }else{
            $temporaryData = array();
        }
        if($key == ''){
            $fielsCount = M('tcollect_question_field')->where(array('node_id'=>$this->node_id))->count();
            $totalCount = count($temporaryData) + $fielsCount; //表中的条数+cookie中的条数
            if($totalCount > 14){
                $this->ajaxReturn(array('error'=>'1001', 'msg'=>'自定义采集项已创建满15个，请选择已创建的采集项'));
                exit;
            }
            $lastCount = M('tcollect_question_field')
                ->where(array('node_id'=>$this->node_id))
                ->order('add_time DESC')->getField('name');
            $lastCount = array_pop(explode('_',$lastCount));
            $totalCount = count($temporaryData) + (int) $lastCount;
            $name = 'member_'.($totalCount+1);
        }else{
            $name = $key;
        }
        $saveData = array();
        $text = I('post.collect_name','', 'string');//选项名
        $valueList = '';//'值:名|值:名  的格式，别被表注释忽悠了'
        $content = I('post.setopt');//选项值

        foreach($content as $key=>$val){
            if($val != ''){
                $valueList .= $key.':'.$val.'|';
            }
        }
        $saveData['text'] = $text;
        $saveData['name'] = $name;
        $saveData['value_list'] = substr($valueList, 0 , strlen($valueList)-1); //-1是为了去掉最后一个竖杠

        $temporaryData[$name] = $saveData;

        cookie('temporaryMemberCustomFields', json_encode($temporaryData), 15*60);
        $this->ajaxReturn(array('error'=>'0', 'name'=>$name, 'text'=>array('text'=>$text, 'value_list'=>$content)));
    }

    /**
     * 设置第二步
     */
    public function setActConfig() {
        if (IS_POST) {
            $configData = array();
            $m_id = I('post.m_id', '', 'trim');
            if($m_id != ''){
                $type = 'edit';
                $memberSum = M('tmarketing_info')->where(array('id'=>$m_id))->getField('member_sum');
            }else{
                $memberSum = 0;
                $type = 'new';
            }
            $join_mode = I('post.join_mode', '', 'trim');
            if($join_mode == '1'){
                $wxAuthType = I('post.WeChat', '0', 'string');
                $configData['wx_auth_type'] = $wxAuthType;
                $configData['wx_auth_url'] = I('post.care_guide', '0', 'string');
            }
            $act_name = I('post.act_name', '', 'trim');
            $act_time_from = I('post.act_time_from', '', 'trim');
            $bg_style = I('post.bg_style', '', 'trim'); // 第几张背景图,如果是第三张是自定义的
            $bg_img = I('post.bg_img', '', 'trim'); // 背景图片路径
            $page_style = I('post.template', '', 'trim'); // 页面风格
            $buttonName = I('post.button_name', '', 'string')  != '' ? I('post.button_name', '', 'string') : '提交';
            $configData['button_name'] = $buttonName;
            if (! check_str($act_time_from, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $act_time_to = I('post.act_time_to', '', 'trim');
            if (! check_str($act_time_to, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $introduce = I('post.introduce', '', 'trim');
            if (! check_str($introduce, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $node_name = I('post.node_name', '', 'trim');
            if (! check_str($node_name, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $node_logo = I('post.node_logo', '', 'trim');
            
            $member_card_id = I('belong_card', '', 'trim'); // 招募到指定会员卡分类
            
            $basicInfo = array(
                'join_mode' => $join_mode, 
                'act_name' => $act_name, 
                'act_time_from' => $act_time_from, 
                'act_time_to' => $act_time_to, 
                'introduce' => $introduce, 
                'node_name' => $node_name, 
                'node_logo' => get_upload_url($node_logo), 
                'bg_style' => $bg_style, 
                'bg_pic' => get_upload_url($bg_img),
                'page_style' => $page_style, 
                'member_card_id' => $member_card_id,
                'config_data'=>$configData
                );
            $baseActivityService = D('BaseActivity', 'Service');
            $baseActivityService->checkisactivitynamesame($basicInfo['act_name'], $this->BATCH_TYPE, $m_id);
            $m_id = $this->_editMarketInfo($basicInfo, $m_id);
            
            //自定义字段入库
            $customFields = array();
            foreach($_POST as $key=>$val){
                if(strstr($key, 'member')){
                    $customFields[$key] = $val;
                }
            }
            if($memberSum <= 0){
                $this->setCustomFields($customFields, $m_id, $type);
                $this->success(array('m_id' => $m_id, 'isReEdit' => I('isReEdit', 0)), '', true);
            }else{
                $this->error('由于已经存在招募会员，因此无法更改信息采集项！其余修改已保存', U('Wmember/Member/recruit'));
            }
        }
        $m_id = I('get.m_id', '');
        $isReEdit = I('get.isReEdit', '1');
        $question = M('tcollect_question_new')->where(array('m_id' => $m_id))->order('sort asc')->select();
        $defaultQuestion = M('tcollect_question_field')->where(array('is_base_field' => '1'))->getField('id,id,name', true);
        // 转换成js里面设定的名字
        foreach ($defaultQuestion as $key => $val) {
            $defaultQuestion[$key]['name'] = $this->_getJsName($val['name']);
        }
        if ($question) { // 如果能取到,说明之前编辑过问题了
            $templateData = array();
            foreach ($question as $k => $v) {
                $templateData[] = array(
                    'is_neccessary' => $v['is_required'], 
                    'name' => $defaultQuestion[$v['field_id']]['name']);
            }
            $templateData = json_encode($templateData);
            $this->assign('module', $templateData); // 给前端模板用的数据
        }
        // 读取背景图，图标
        $marketInfoModel = M('tmarketingInfo');
        $basicInfo = $marketInfoModel->where( array('node_id' => $this->node_id,'id' => $m_id))->find();
        $this->assign('bg_pic', $basicInfo['bg_pic']); // 背景图
        $this->assign('log_img', $basicInfo['log_img']); // 活动用的企业logo
        $this->assign('act_name', $basicInfo['name']); // 活动名称
        $this->assign('node_name', $basicInfo['node_name']); // 活动用的企业名称
        $this->assign('m_id', $m_id);
        $this->assign('isReEdit', $isReEdit);
        $stepBar = D('CjSet')->getActStepsBar(ACTION_NAME, $m_id, '', $isReEdit, array('setActBasicInfo' => '基础信息', 'setPrize' => '奖项设定','publish'=>'活动发布'));
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        $this->display();
    }
    
    /**
     * 自定义字段入库
     * @param array $data
     * @param int $mId
     * @param string $type
     */
    private function setCustomFields($data = array(), $mId, $type='new'){
        if($type != 'new'){
            M('tcollect_question_new')->where(array('m_id'=>$mId, 'node_id'=>$this->node_id))->save(array('is_delete'=>'1'));
        }
        $temporaryCustomFields = cookie('temporaryMemberCustomFields');
        if($temporaryCustomFields != ''){
            $temporaryCustomFields = json_decode($temporaryCustomFields, true);
            cookie('temporaryMemberCustomFields', '', -1);
        }else{
            unset($temporaryCustomFields);
        }
        $dateTime = date('Y-m-d H:i:s');
        $sort = 0;
        foreach($data as $key=>$val){
            if($temporaryCustomFields[$key] != ''){
                $saveData = array();
                $saveData['text'] = $temporaryCustomFields[$key]['text'];
                $saveData['name'] = $temporaryCustomFields[$key]['name'];
                $saveData['value_list'] = $temporaryCustomFields[$key]['value_list'];
                $saveData['type'] = '1';
                $saveData['is_base_field'] = '0';
                $saveData['node_id'] = $this->node_id;
                $saveData['add_time'] = $dateTime;
                $newQuestionFieldID = M('tcollect_question_field')->add($saveData);

                //添加到tmember_attribute_stat表中
                $attributeArr = explode('|',$temporaryCustomFields[$key]['value_list']);
                foreach($attributeArr as $key2 => $value2){
                    $fieldValue = explode(':',$value2);
                    $dataArr = array(
                        'node_id'       => $this->node_id,
                        'field_id'      => $newQuestionFieldID,
                        'field_value'   => $fieldValue[0],
                        'field_name'    => $fieldValue[1],
                        'member_cnt'    => 0
                    );
                    M('tmember_attribute_stat')->add($dataArr);
                }

            }else{
                switch($key){
                    case 'member_name':
                        $newQuestionFieldID = 1;
                        break;
                    case 'member_sex':
                        $newQuestionFieldID = 4;
                        break;
                    case 'member_area':
                        $newQuestionFieldID = 5;
                        break;
                    case 'member_birthday':
                        $newQuestionFieldID = 3;
                        break;
                    default:
                        $newQuestionFieldID = M('tcollect_question_field')
                            ->where(array('name'=>$key, 'node_id'=>$this->node_id))
                            ->getfield('id');
                        break;
                }
                
            }
            
            if($newQuestionFieldID){
                $questionNewSaveData = array();
                $questionNewSaveData['field_id'] = $newQuestionFieldID;
                $questionNewSaveData['is_required'] = $data[$key];
                $questionNewSaveData['m_id'] = $mId;
                $questionNewSaveData['sort'] = $sort;
                $questionNewSaveData['node_id'] = $this->node_id;
                $questionNewSaveData['add_time'] = $dateTime;
                $newQuestionID = M('tcollect_question_new')->add($questionNewSaveData);
            }
            
            log_write(M()->_sql());
            $sort += 1;
        }
    }

    /**
     * [member_static_data description] 会员数据统计
     */
    function mdata() {
        $ctype = $_REQUEST['ctype'] ? $_REQUEST['ctype'] : 1;
        $mtype = $_REQUEST['mtype'] ? $_REQUEST['mtype'] : 1;
        $ndate = $_REQUEST['ndate'];
        $sdate = $_REQUEST['sdate'];
        // 会员整体数据统计
        if ($mtype == 1) {
            $date = '"' . date('Ymd', strtotime('-8 day')) . '"';
            $where = $date . '< add_time and add_time < "' . date('Ymd') . '"';
            // $where2=$date.'< trans_date and trans_date < "'.date('Ymd').'"';
        }
        if ($mtype == 2) {
            $date = '"' . date('Ymd', strtotime('-31 day')) . '"';
            $where = $date . '< add_time and add_time < "' . date('Ymd') . '"';
            // $where2=$date.'< trans_date and trans_date < "'.date('Ymd').'"';
        }
        // $fields1=array('sum(join_total)'=>'join_cnt','trans_date'=>'date');
        $fields2 = array(
            'count(*)' => 'count', 
            'DATE_FORMAT(add_time,"%Y%m%d")' => 'date');
        if ($mtype == 3) {
            $date = '"' . date('Ymd', strtotime('-1 year')) . '"';
            $where = $date . '<= add_time and add_time < "' . date('Ymd') . '"';
            // $where2=$date.'< trans_date and trans_date < "'.date('Ymd').'"';
            $fields2 = array(
                'count(*)' => 'count', 
                'DATE_FORMAT(add_time,"%Y%m")' => 'date');
            // $fields1=array('sum(join_total)'=>'join_cnt','DATE_FORMAT(trans_date,"%Y%m")'=>'date');
        }
        if ($mtype == 4) {
            $where = '"' . date('Ymd', strtotime($sdate[0])) . '"' .
                 ' <= add_time and add_time <= ' . '"' .
                 date('Ymd', strtotime($ndate[0])) . '"';
            // $where2='"'.date('Ymd',strtotime($sdate[0])).'"< trans_date and
            // trans_date < "'.date('Ymd',strtotime($ndate[0])).'"';
            if (strtotime($ndate[0]) > (strtotime($sdate[0])) + 31 * 24 * 3600) {
                // $fields1=array('sum(join_total)'=>'join_cnt','DATE_FORMAT(trans_date,"%Y%m")'=>'date');
                $fields2 = array(
                    'count(*)' => 'count', 
                    'DATE_FORMAT(add_time,"%Y%m")' => 'date');
            }
        }
        if ($ctype == 1) {
            $date = '"' . date('Ymd', strtotime('-8 day')) . '"';
            $where1 = $date . '< i.add_time and i.add_time < "' . date('Ymd') .
                 '"';
        }
        if ($ctype == 2) {
            $date = '"' . date('Ymd', strtotime('-31 day')) . '"';
            $where1 = $date . '< i.add_time and i.add_time < "' . date('Ymd') .
                 '"';
        }
        if ($ctype == 3) {
            $date = '"' . date('Ymd', strtotime('-1 year')) . '"';
            $where1 = $date . '<= i.add_time and i.add_time < "' . date('Ymd') .
                 '"';
        }
        if ($ctype == 4) {
            $where1 = '"' . date('Ymd', strtotime($sdate[1])) . '"' .
                 ' <= i.add_time and i.add_time <= ' . '"' .
                 date('Ymd', strtotime($ndate[1])) . '"';
        }
        // 活动总数
        // $mactive=M('tmember_activity_stat')->field($fields1)
        // ->where(array('node_id'=>$this->node_id))
        // ->where($where2)->group('date')->order('date asc')->select();
        // $mactive = M("tmember_activity_total")->field($fields1)
        // ->where(array('node_id'=>$this->node_id))
        // ->where($where2)->group('date')->order('date asc')->select();
        // 新增会员数
        $mdata = M('tmember_info')->field($fields2)
            ->where(array(
            'node_id' => $this->node_id))
            ->where($where)
            ->group('date')
            ->order('date asc')
            ->select();
        $dataarray = array();
        foreach ($mdata as $v) {
            $mdata_bak[$v['date']] = $v['count'];
            $dataarray[] = $v['date'];
        }
        // foreach ($mactive as $v){
        // $newactive[$v['date']] = $v['join_cnt'];
        // $dataarray[]=$v['date'];
        // }
        $dataarray = array_unique($dataarray);
        asort($dataarray);
        foreach ($dataarray as $v) {
            // $xdata11.=$newactive[$v]>0?$newactive[$v].',':'0,';
            $xdata12 .= $mdata_bak[$v] > 0 ? $mdata_bak[$v] . ',' : '0,';
            $ydata .= "'" . $v . "',";
            // $xdata13.=$v['add_num']>0 ? $v['add_num'].',':'0,';
        }
        $xydata = array(
            // 'xdata11'=>trim($xdata11,','),
            'ydata' => trim($ydata, ','), 
            'xdata12' => trim($xdata12, ','));
        // 'xdata13'=>trim($xdata13,',')
        
        $this->assign('xydata', $xydata);
        $data = array(
            'mtype' => $mtype, 
            'ctype' => $ctype, 
            'date' => array(
                'msdate' => $sdate[0], 
                'mndate' => $ndate[0], 
                'csdate' => $sdate[1], 
                'cndate' => $ndate[1]));
        // $this->assign('mactive',$mactive);
        $this->assign('mdata', $mdata);
        // 会员总数
        // 会员属性分析昨天的数据
        // $sex_data=M()->table(array('tmember_nature_stat'=>'i'))
        // ->field(array('sum(i.cnt)'=>'count','sex'))
        // ->group('i.sex')
        // ->where(array('i.node_id'=>$this->node_id))->select();
        // foreach ($sex_data as $v){
        // $sextotal+=$v['count'];
        // if($v['sex']!=2) $msex+=$v['count'];
        // }
        // $this->assign('sex_data',array('wsex'=>1-round($msex/$sextotal,4),'msex'=>round($msex/$sextotal,4)));
        
        $sex_data = M("tmember_info")->field('count(sex) s_num, sex')
            ->where(array(
            'node_id' => $this->node_id))
            ->group('sex')
            ->order('sex desc')
            ->select();
        foreach ($sex_data as $v) {
            $sextotal += $v['s_num'];
            if ($v['sex'] != 2) {
                $msex += $v['s_num'];
            }
        }
        $this->assign('sex_data', 
            array(
                'wsex' => 1 - round($msex / $sextotal, 4), 
                'msex' => round($msex / $sextotal, 4)));
        
        // 会员地域分析
        // 所有会员
        // $sql='select sum(cnt) allsum,province_code from tmember_nature_stat
        // where node_id="'.$this->node_id.'" group by province_code order by
        // allsum desc';
        $sql = 'select count(phone_no) allsum,province_code from tmember_info where node_id="' .
             $this->node_id . '" group by province_code  order by allsum desc';
        $memberactivy = M()->query($sql);
        $citylist = M('tcity_code')->where(
            array(
                'city_level' => 1))
            ->field('province,province_code')
            ->select();
        foreach ($citylist as $mv) {
            $citylist_bak[$mv['province']] = $mv['province_code'];
        }
        foreach ($citylist as $mv) {
            $citylist_bak1[$mv['province_code']] = $mv['province'];
        }
        $asum = 0;
        foreach ($memberactivy as $k => $mv) {
            $asum += $mv['allsum'];
            if (empty($mv['province_code'])) {
                $shiftk = $k;
                $memberactivy[count($memberactivy)] = $mv;
                continue;
            }
            $memberactivy_bak[$mv['province_code']] = $mv;
        }
        unset($memberactivy[$shiftk]);
        $this->assign('firstactivy', 
            $memberactivy[0] ? $memberactivy[0] : $memberactivy[1]);
        $this->assign('member_sum', $asum);
        $citydata = array(
            'heilongjiang' => array(
                'value' => $memberactivy_bak[$citylist_bak['黑龙江']]), 
            'jilin' => array(
                'value' => $memberactivy_bak[$citylist_bak['吉林']]), 
            'liaoning' => array(
                'value' => $memberactivy_bak[$citylist_bak['辽宁']]), 
            'hebei' => array(
                'value' => $memberactivy_bak[$citylist_bak['河北']]), 
            'shandong' => array(
                'value' => $memberactivy_bak[$citylist_bak['山东']]), 
            'jiangsu' => array(
                'value' => $memberactivy_bak[$citylist_bak['江苏']]), 
            'zhejiang' => array(
                'value' => $memberactivy_bak[$citylist_bak['浙江']]), 
            'anhui' => array(
                'value' => $memberactivy_bak[$citylist_bak['安徽']]), 
            'henan' => array(
                'value' => $memberactivy_bak[$citylist_bak['河南']]), 
            'shanxi' => array(
                'value' => $memberactivy_bak[$citylist_bak['山西']]), 
            'shaanxi' => array(
                'value' => $memberactivy_bak[$citylist_bak['陕西']]), 
            'gansu' => array(
                'value' => $memberactivy_bak[$citylist_bak['甘肃']]), 
            'hubei' => array(
                'value' => $memberactivy_bak[$citylist_bak['湖北']]), 
            'jiangxi' => array(
                'value' => $memberactivy_bak[$citylist_bak['江西']]), 
            'fujian' => array(
                'value' => $memberactivy_bak[$citylist_bak['福建']]), 
            'hunan' => array(
                'value' => $memberactivy_bak[$citylist_bak['湖南']]), 
            'guizhou' => array(
                'value' => $memberactivy_bak[$citylist_bak['贵州']]), 
            'sichuan' => array(
                'value' => $memberactivy_bak[$citylist_bak['四川']]), 
            'yunnan' => array(
                'value' => $memberactivy_bak[$citylist_bak['云南']]), 
            'qinghai' => array(
                'value' => $memberactivy_bak[$citylist_bak['青海']]), 
            'hainan' => array(
                'value' => $memberactivy_bak[$citylist_bak['海南']]), 
            'shanghai' => array(
                'value' => $memberactivy_bak[$citylist_bak['上海']]), 
            'chongqing' => array(
                'value' => $memberactivy_bak[$citylist_bak['重庆']]), 
            'tianjin' => array(
                'value' => $memberactivy_bak[$citylist_bak['天津']]), 
            'beijing' => array(
                'value' => $memberactivy_bak[$citylist_bak['北京']]), 
            'ningxia' => array(
                'value' => $memberactivy_bak[$citylist_bak['宁夏']]), 
            'neimongol' => array(
                'value' => $memberactivy_bak[$citylist_bak['内蒙古']]), 
            'guangxi' => array(
                'value' => $memberactivy_bak[$citylist_bak['广西']]), 
            'xinjiang' => array(
                'value' => $memberactivy_bak[$citylist_bak['新疆']]), 
            'xizang' => array(
                'value' => $memberactivy_bak[$citylist_bak['西藏']]), 
            'guangdong' => array(
                'value' => $memberactivy_bak[$citylist_bak['广东']]), 
            'hongkong' => array(
                'value' => $memberactivy_bak[$citylist_bak['香港']]), 
            'taiwan' => array(
                'value' => $memberactivy_bak[$citylist_bak['台湾']]), 
            'macau' => array(
                'value' => $memberactivy_bak[$citylist_bak['澳门']]));
        $this->assign('citydata', json_encode($citydata));
        $this->assign('citylist', $citylist_bak1);
        $this->assign('memberactivy', $memberactivy);
        // 会员总数，昨日新增
        $result = M('tmember_stat')->field(
            array(
                'trans_date', 
                'add_num', 
                'total_num'))
            ->where(
            array(
                'node_id' => $this->node_id, 
                'trans_date' => array(
                    'elt', 
                    date('Ymd'))))
            ->order('trans_date desc')
            ->find();
        if ($result['trans_date'] != date('Ymd', strtotime('-1 day'))) {
            $result['add_num'] = 0;
        }
        $this->assign('member_stat', $result);
        // 今日新增
        $sql = "SELECT  sum(join_cnt) join_cnt,sum(verify_cnt) verify_cnt,sum(send_cnt) send_cnt FROM 
		   `tmember_activity_stat` WHERE ( `node_id` = '" .
             $this->node_id . "' ) AND 
		   (`trans_date` ='" .
             date('Ymd', strtotime('-1 day')) . "') ";
        $newdata = M()->query($sql);
        $this->assign('newdata', $newdata[0]);
        // 总的
        $totaldata = M("tmember_activity_total")->field(
            "sum(join_total) join_cnt, sum(send_total) send_cnt, sum(verify_total) verify_cnt")
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        $this->assign('totaldata', $totaldata);
        $this->assign('data', $data);
        // 会员渠道分析
        $chnallist = M()->table(array(
            'tmember_info' => 'i'))
            ->join('tchannel t on i.channel_id = t.id')
            ->where(array(
            'i.node_id' => $this->node_id))
            ->field(
            array(
                'IFNULl(t.name,"其他")' => 'tname', 
                'count(*)' => 'c'))
            ->order('c desc,i.channel_id desc')
            ->where($where1)
            ->group('tname')
            ->select();
        $count = count($chnallist);
        foreach ($chnallist as $k => $v) {
            if ($v['tname'] == '其他') {
                $shift = $k;
                $chnallist[count($chnallist)] = $v;
            }
            if ($v['tname'] == '旺分销-群发消息') {
                $wKey = $k;
                $wNum = $v['c'];
            }
            $totalnum += $v['c'];
        }
        unset($chnallist[$shift]);
        unset($chnallist[$wKey]);
        if ($shift > 0 && $wKey > 0) {
            $chnallist[$count]['c'] += $wNum;
        }
        
        $this->assign('chainal', 
            array(
                $totalnum, 
                $chnallist));
        $this->display();
    }

    /**
     * [member_static_data description] 会员活动数据统计
     */
    function activitydata() {
        // 活动总数
        $count = M()->table('tmarketing_info')
            ->where(array(
            'node_id' => $this->node_id))
            ->count();
        $this->assign('allactivy', $count);
        // 当前正在活动de
        $lifeactivy = M()->table(
            array(
                'tmarketing_info' => 'i', 
                'tbatch_channel' => 'tc'))
            ->where('i.id = tc.batch_id')
            ->where(
            array(
                'i.node_id' => $this->node_id, 
                'i.status' => 0, 
                'tc.status' => 1, 
                'tc.add_time' => array(
                    'gt', 
                    date('YmdHis')), 
                'tc.end_time' => array(
                    'lt', 
                    date('YmdHis'))))
            ->count();
        $this->assign('lifeactivy', $lifeactivy);
        // 活动参与次数
        $sum = M('tmember_activity_stat')->where(
            array(
                'node_id' => $this->node_id, 
                'trans_date' => array(
                    'lt', 
                    date('Ymd'))))
            ->limit(1)
            ->order('trans_date desc')
            ->find();
        $this->assign('sum', $sum);
        $this->display();
    }

    /**
     * 未开通（ 非4880会员） Enter description here .
     * ..
     */
    function promotionn4880() {
        // $this->assign('ispowermember',$this->_checkUserAuth('Wmember/Member/index'));

        $node_id = $this->node_id;
        $sql = "SELECT COUNT(*) AS tp_count FROM tmarketing_info m WHERE ( m.node_id = '".$node_id."' ) AND ( m.batch_type = '52' ) LIMIT 1 ";
        $recruitArr = M()->query($sql);
        $recruitCount = $recruitArr[0]['tp_count'];//招募活动数量

        $sql2 = "SELECT COUNT(*) AS tp_count FROM tmember_info a LEFT JOIN tmember_activity_total m ON m.member_id = a.id LEFT JOIN tchannel n ON n.id=a.channel_id WHERE ( a.node_id = '".$node_id."' ) AND ( phone_no IS NOT NULL ) LIMIT 1 ";
        $memberArr = M()->query($sql2);
        $memberCount = $memberArr[0]['tp_count'];//会员数量

        $sql3 = "SELECT COUNT(*) AS tp_count FROM tmember_cards WHERE ( node_id = '".$node_id."' ) ";
        $CardArr = M()->query($sql3);
        $CardCount = $CardArr[0]['tp_count'];//会员卡数量

        $det = session("promotionn");
        if ($det == 1 || $recruitCount || $memberCount > 49 || $CardCount > 1) {
            redirect(U('infoCenter'));
        } else {
            session("promotionn", 1);
        }
        $this->display();
    }

    /**
     * 已开通未绑定微信 Enter description here .
     * ..
     */
    function wunbind() {
        $this->display();
    }

    /**
     * 已开通绑定微信 Enter description here .
     * ..
     */
    function wbind() {
        $this->display();
    }

    /**
     * 会员卡 Enter description here .
     * ..
     */
    function card() {
        $this->display();
    }

    /**
     * 会员标签 Enter description here .
     * ..
     */
    function label() {
        $this->display();
    }

    /**
     * 已开通绑定微信 Enter description here .
     * ..
     */
    function openagreement() {
        $this->display();
    }

    /**
     * 会员基本信息 Enter description here .
     * ..
     */
    public function infoCenter() {
        // 基本信息字段
        $where = array(
            'a.node_id' => $this->node_id, 
            '_string' => "phone_no is not NULL");
        
        $memberName = I('member_name', null);
        if ($memberName) {
            $where['a.name'] = $memberName;
            $this->assign('member_name', $memberName);
        }
        
        $memberPhone = I('member_phone', null);
        if ($memberPhone) {
            $matchResult = preg_match('/1[34578]\d{9}$/',$memberPhone);
            if($matchResult){
                $where['a.phone_no'] = $memberPhone;
            }else{
                $where['a.phone_no'] = array('like',"$memberPhone%");
            }
            $this->assign('member_phone', $memberPhone);
        }
        
        $memberSex = I('member_sex', null);
        if ($memberSex) {
            $where['a.sex'] = $memberSex;
            $this->assign('member_sex', $memberSex);
        }
        
        $nickName = I('nickname', null); // 微信昵称
        if ($nickName) {
            $where['a.nickname'] = $nickName;
            $this->assign('nickname', $nickName);
        }
        
        $bStart = I('birthday_start', null);
        if ($bStart) {
            $where['a.birthday'][] = array(
                "EGT", 
                $bStart);
            $this->assign('birthday_start', $bStart);
        }
        
        $bEnd = I('birthday_end', null);
        if ($bEnd) {
            $where['a.birthday'][] = array(
                "ELT", 
                $bEnd);
            $this->assign('birthday_end', $bEnd);
        }
        
        $channel_name = I('channel_name', null);
        if ($channel_name) {
            $channelName = $channel_name;
            if ($channel_name == '旺分销消息') {
                $channelName = '旺分销-群发消息';
            }
            $where['n.name'] = array(
                'like', 
                '%' . $channelName . '%');
            $this->assign('channel_name', $channel_name);
        }
        $label_name = I('bqValue', null);
        if ($label_name) {
            $arr = explode(",", $label_name);
            array_pop($arr);
            $this->assign('arr', $arr);
            $where['_string'] .= " and EXISTS(select * from tmember_label_ex l, tmember_label s where a.id = l.member_id and s.id = l.label_id and s.label_name in(";
            foreach ($arr as $k => $v) {
                $where['_string'] .= "'" . $v . "'";
                if ($k != (count($arr) - 1)) {
                    $where['_string'] .= ",";
                }
            }
            $where['_string'] .= "))";
            $this->assign('label_name', $label_name);
        }
        
        $member_cards = I('member_cards', null);
        if ($member_cards) {
            $where['a.card_id'] = $member_cards;
            $this->assign('member_cards', $member_cards);
        }
        
        $integral_point1 = I('integral_point1', null);
        if ($integral_point1) {
            $where['a.point'][] = array(
                "EGT", 
                $integral_point1);
            $this->assign('integral_point1', $integral_point1);
        }
        $integral_point2 = I('integral_point2', null);
        if ($integral_point2) {
            $where['a.point'][] = array(
                "ELT", 
                $integral_point2);
            $this->assign('integral_point2', $integral_point2);
        }
        
        // 所属门店
        if ($this->node_id == C('adb.node_id')) {
            $store_id = trim(I("store_id", ''));
            if ($store_id) {
                $where['ti.store_short_name'] = array(
                    'like', 
                    '%' . $store_id . '%');
            }
        }
        // 城市信息
        $province = I('province', null);
        if ($province) {
            $where['a.citycode'] = array(
                'like', 
                $province . "%");
            $this->assign('province', $province);
        }
        $city = I('city', null);
        if ($city) {
            $where['a.citycode'] = array(
                'like', 
                $province . $city . "%");
            $this->assign('city', $city);
        }
        $town = I('town', null);
        if ($town) {
            $where['a.citycode'] = $province . $city . $town;
            $this->assign('town', $town);
        }
        
        // 特殊信息字段
        $join_cnt1 = I('join_cnt1', null);
        if ($join_cnt1) {
            $where['m.join_total'][] = array(
                "EGT", 
                $join_cnt1);
            $this->assign("join_cnt1", $join_cnt1);
        }
        $join_cnt2 = I('join_cnt2', null);
        if ($join_cnt2) {
            $where['m.join_total'][] = array(
                "ELT", 
                $join_cnt2);
            $this->assign("join_cnt2", $join_cnt2);
        }
        
        $send_cnt1 = I('send_cnt1', null);
        if ($send_cnt1) {
            $where['m.send_total'][] = array(
                "EGT", 
                $send_cnt1);
            $this->assign("send_cnt1", $send_cnt1);
        }
        $send_cnt2 = I('send_cnt2', null);
        if ($send_cnt2) {
            $where['m.send_total'][] = array(
                "ELT", 
                $send_cnt2);
            $this->assign("send_cnt2", $send_cnt2);
        }
        
        $verify_cnt1 = I('verify_cnt1', null);
        if ($verify_cnt1) {
            $where['m.verify_total'][] = array(
                "EGT", 
                $verify_cnt1);
            $this->assign("verify_cnt1", $verify_cnt1);
        }
        $verify_cnt2 = I('verify_cnt2', null);
        if ($verify_cnt2) {
            $where['m.verify_total'][] = array(
                "ELT", 
                $verify_cnt2);
            $this->assign("verify_cnt2", $verify_cnt2);
        }
        
        $shop_line1 = I('shop_line1', null);
        if ($shop_line1) {
            $where['m.shop_total'][] = array(
                "EGT", 
                $shop_line1);
            $this->assign("shop_line1", $shop_line1);
        }
        $shop_line2 = I('shop_line2', null);
        if ($shop_line2) {
            $where['m.shop_total'][] = array(
                "ELT", 
                $shop_line2);
            $this->assign("shop_line2", $shop_line2);
        }
        
        $shop_down1 = I('shop_down1', null);
        if ($shop_down1) {
            $where['m.shopline_total'][] = array(
                "EGT", 
                $shop_down1);
            $this->assign("shop_down1", $shop_down1);
        }
        $shop_down2 = I('shop_down2', null);
        if ($shop_down2) {
            $where['m.shopline_total'][] = array(
                "ELT", 
                $shop_down2);
            $this->assign("shop_down2", $shop_down2);
        }
        
        $data = $_REQUEST;
        import('ORG.Util.Page'); // 导入分页类
        $member = M("tmember_info");
        // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            $count_member = $member->alias("a")->join(
                'tmember_activity_total m on m.member_id = a.id')
                ->join('tchannel n on n.id=a.channel_id')
                ->join('tfb_adb_user_store taus on taus.openid=a.mwx_openid')
                ->join('tstore_info ti on ti.store_id=taus.store_id')
                ->where($where)
                ->count();
        } else {
            $count_member = $member->alias("a")->join(
                'tmember_activity_total m on m.member_id = a.id')
                ->join('tchannel n on n.id=a.channel_id')
                ->where($where)
                ->count();
        }

        //选择的每页显示条数
        $paging = array('10' => '10', '50' => '50', '100' => '100');

        $optNumber = I('optNumber', null);

        if ($optNumber) {
            $num = $optNumber;
            $cfgData = session('cfgData');
            $cfgData['pagingNumber'] = $num;
            $datas['cfg_data'] = serialize($cfgData);
            M('tnode_info')->where("node_id = $this->nodeId")->save($datas);
        }else{
            $result = M('tnode_info')->where("node_id = $this->nodeId")->getField('cfg_data');

                if($result){
                    $cfgData = unserialize($result);
                    session('cfgData',$cfgData);
                    if(isset($cfgData['pagingNumber']) && $cfgData['pagingNumber']){
                        $pagingNumber = $cfgData['pagingNumber'];
                    }else{
                        $pagingNumber = 10;
                    }
                }else{
                    session('cfgData','');
                    $pagingNumber = 10;
                }

            $num = $pagingNumber;
        }
        $this->assign('optNumber', $num);

        $Page = new Page($count_member, $num);
        $show = $Page->show(); // 分页显示输出
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $list = $member->alias("a")->field(
                'a.*,m.join_total,m.send_total,m.verify_total,m.shop_total,m.shopline_total,n.name channel_name,ti.store_short_name,taus.openid')
                ->join('tmember_activity_total m on m.member_id = a.id')
                ->join('tchannel n on n.id=a.channel_id')
                ->join('tfb_adb_user_store taus on taus.openid=a.mwx_openid')
                ->join('tstore_info ti on ti.store_id=taus.store_id')
                ->where($where)
                ->order('a.add_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $list = $member->alias("a")->field(
                'a.*,m.join_total,m.send_total,m.verify_total,m.shop_total,m.shopline_total,n.name channel_name')
                ->join('tmember_activity_total m on m.member_id = a.id')
                ->join('tchannel n on n.id=a.channel_id')
                ->where($where)
                ->order('a.add_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        $member = new MemberInstallModel();
        $member_labels = $member->getListLabels($this->node_id);
        $member_lgroup = array();
        foreach ($member_labels as $key => $val) {
            $member_lgroup[$val['id']] = $val['label_name'];
        }
        $this->assign("member_lgroup", $member_lgroup);
        
        $member_cards = $member->getMemberCards($this->node_id);
        $member_cgroup = array();
        foreach ($member_cards as $key => $val) {
            $member_cgroup[$val['id']] = $val['card_name'];
        }
        $this->assign("member_cgroup", $member_cgroup);





        $this->assign('adb_flag', $adb_flag);
        $this->assign('memberData', $list);
        $this->assign('sex_list', 
            $sex_list = array(
                '1' => '男', 
                '2' => '女'));

        $this->assign('paging', $paging);
        $this->assign('page', $show);
        $this->display();
    }
    
    // 判断搜索条件添加
    public function selLabelFlag() {
        $label_name = I("label_name", '');
        
        $result = D("MemberInstall")->judgedLabelFlag($this->node_id, 
            $label_name);
        if (! $result) {
            $this->error("标签不存在");
        } else {
            $this->success("标签存在");
        }
    }
    
    // 给指定会员添加单个标签
    public function addOneLabel() {
        $m_id = I("m_id", null);
        $type = I("channelType", null);
        $ot_type = I("ot_type", null);
        
        $mIns = D("MemberInstall", "Model");
        if ($type) {
            $data = array();
            $label_id = I("lab_sel", null);
            if ($type == 22) {
                $label_name = I("label_name", '');
                $nflag = $mIns->judgedLabelFlag($this->node_id, $label_name);
                if (! $nflag) {
                    $nflag = $mIns->addLabel($this->node_id, $label_name);
                    if (! $nflag) {
                        $this->error("添加失败！");
                    }
                }
                $label_id = $nflag;
            }
            
            if ($ot_type == 1) {
                $eflag = $mIns->judgedLabelExFlag($this->node_id, $m_id, 
                    $label_id);
                if ($eflag) {
                    $this->error("会员已存在该标签！");
                } else {
                    $flag = $mIns->member_label_ex($this->node_id, $m_id, 
                        $label_id);
                    if ($flag) {
                        $this->success("添加成功！");
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $mIdArr = explode(',', $m_id);
                $retFlag = false;
                $errNum = 0;
                foreach ($mIdArr as $val) {
                    $eflag = $mIns->judgedLabelExFlag($this->node_id, $val, 
                        $label_id);
                    if ($eflag) {
                        $errNum ++;
                    } else {
                        $flag = $mIns->member_label_ex($this->node_id, $val, 
                            $label_id);
                        if ($flag) {
                            $retFlag = true;
                        } else {
                            $errNum ++;
                        }
                    }
                }
                if ($retFlag) {
                    $this->success("批量添加成功！");
                } else {
                    $this->error("批量添加,有{$errNum}会员添加失败！");
                }
            }
        }
        
        $list = $mIns->getListLabels($this->node_id);
        
        $this->assign('labels', $list);
        $this->assign("m_id", $m_id);
        $this->assign("ot_type", $ot_type);
        $this->display();
    }
    
    // 手动改变会员的所属会员卡
    public function updateMemberCard() {
        $m_id = I("m_id", null);
        $card_id = I("card_id", null);
        
        $mIns = D("MemberInstall", "Model");
        $mIdArr = explode(',', $m_id);
        if ($card_id) {
            $retFlag = false;
            $errNum = 0;
            foreach ($mIdArr as $val) {
                $mData = $mIns->getMemberMsg($val);
                if ($mData) {
                    if ($mData['card_id'] == $card_id) {
                        $errNum ++;
                    } else {
                        $flag = M("tmember_info")->where(
                            array(
                                'id' => $val))->save(
                            array(
                                'card_id' => $card_id,'card_update_time'=>date('YmdHis')));
                        if ($flag) {
                        	//会员卡变更加入到消息中去
                        	$memberCardChangeInfo[] = array(
                        			'nowIntegral' => $mData['point'],
                        			'memberId'    => $val,
                        			'oldCard'     => $mData['card_id'],
                        			'newCard'     => $card_id,
                        			'name'        => $mData['name'],
                        			'phone_no'    => $mData['phone_no'],
                        			'openId'      => $mData['mwx_openid'],
                        			'member_num'  => $mData['member_num'],
                                    'nodeId'      => $this->node_id,
                        	);
                            $retFlag = true;
                        } else {
                            $errNum ++;
                        }
                    }
                }
            }
            $integralPointTraceModel = D('IntegralPointTrace');
            $integralPointTraceModel->memberCardChange($memberCardChangeInfo);
            if ($retFlag) {
                $this->success("批量修改成功！");
            } else {
                $this->error("批量修改，有{$errNum}会员修改失败，错误原因：会员已是改会员卡或保存失败！");
            }
        }
        
        $cardData = $mIns->getMemberCards($this->node_id);
        
        $this->assign('memberNum', count($mIdArr));
        $this->assign('m_id', $m_id);
        $this->assign('cards', $cardData);
        $this->display();
    }

    // 查看会员详情
    public function detail() {
        $m_id = I('m_id', null);
        
        $mIns = D("MemberInstall", "Model");
        $memberData = $mIns->getMemberDetailMsg($m_id); // 获取会员信息

        if ($memberData['sex'] == '1') {
            $memberData['sex'] = '男';
        } else {
            $memberData['sex'] = '女';
        }
        if (! $memberData['update_time']) {
            $memberData['update_time'] = $memberData['add_time'];
        }
        if($memberData['custom_field_data'] != ''){
            $customFieldInfo = $mIns->getCustomFieldInfo(json_decode($memberData['custom_field_data'], TRUE), $this->node_id, 1);
        }else{
            $customFieldInfo = $mIns->getCustomFieldInfo(array(), $this->node_id, 1);
        }
        $mcData = $mIns->getMemberIdToCard($this->node_id, $m_id); // 获取会员卡信息
        $channel_name = M("tchannel")->where(array('id' => $memberData['channel_id']))->getField('name'); // 来源渠道
        $aData = M("tmember_activity_total")->where(
            array(
                'member_id' => $m_id))
            ->field(
            "join_total, send_total, verify_total,trans_date,shop_total,shopline_total")
            ->find();
        
        // 获取线上购物总额
        $map = array(
            'node_id' => $this->node_id, 
            'order_phone' => $memberData['phone_no'], 
            'pay_status' => '2');
        $shopData = M("ttg_order_info")->field("sum(order_amt) amt")
            ->where($map)
            ->group("order_phone")
            ->select();
        // 获取线下购物总额
        // 付满送微信支付总额
        $wfshopLineData = M("tintegral_weixin_order")->field("sum(amt) amt")
            ->where(
            array(
                'node_id' => $this->node_id, 
                'member_phone' => $memberData['phone_no']))
            ->group("member_phone")
            ->find();
        $labels = $mIns->getMemberLabels($this->node_id, $m_id); // 获取会员标签
        $this->assign("memberData", $memberData);
        $this->assign('customFieldInfo', $customFieldInfo);
        $this->assign("mcData", $mcData);
        $this->assign("channel_name", $channel_name);
        $this->assign("aData", $aData);
        $this->assign("labels", $labels);
        $this->assign('shopAmt', $shopData[0]['amt']);
        $this->assign('shopLineAmt', $wfshopLineData['amt']);
        $this->display();
    }
    
    // 删除会员标签
    public function delMemberLabel() {
        $m_id = I("m_id", null);
        $label_id = I("label_id", null);
        
        $result = M("tmember_label_ex")->where(
            array(
                'node_id' => $this->node_id, 
                'member_id' => $m_id, 
                'label_id' => $label_id))->delete();
        if (! $result) {
            $this->error("删除失败");
        }
        $this->success("删除成功!");
    }
    
    // 查看会员行为记录
    public function behaviorRecord() {
        $m_id = I('m_id', null);
        
        import('ORG.Util.Page'); // 导入分页类
        $count = M("tintegral_behavior_trace")->where(
            array(
                "node_id" => $this->node_id, 
                "member_id" => $m_id))->count();
        $Page = new Page($count, 10);
        $result = M("tintegral_behavior_trace")->where(
            array(
                "node_id" => $this->node_id, 
                "member_id" => $m_id))
            ->order("trace_time desc")
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $behavior = array(
            '1' => '参与活动', 
            '2' => '收到翼码卡券', 
            '3' => '到店核销', 
            '4' => '在线购物', 
            '5' => '手动增加积分', 
            '6' => '手动减少积分', 
            '7' => '每日签到', 
            '8' => '连续签到7天', 
            '9' => '积分商城兑换', 
            '10' => '微信支付', 
            '11' => '线下消费', 
            '12' => '线下兑换', 
            '13' => '关注微信公众号', 
            '14' => '红包', 
            '15' => '积分卡券', 
            '16' => '浏览图文编辑页面', 
            '17' => '线上购物抵扣',
            '19' => '支付宝支付');
        $this->assign("behavior", $behavior);
        
        $this->assign("traceData", $result);
        $this->assign("m_id", $m_id);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 会员基本信息编辑 Enter description here .
     * ..
     */
    public function editinfo() {
        if (IS_AJAX) {
            $name = $_REQUEST['name'];
            $gender = $_REQUEST['gender'];
            $phone = $_REQUEST['phone'];
            $id = $_REQUEST['id'];
            // 查询手机号码
            $result = M('tmember_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'phone_no' => $phone, 
                    'id' => array(
                        'neq', 
                        $id)))->count();
            if ($result > 0)
                $this->error('手机号码已经存在');
            $result = M('tmember_info')->where(
                array(
                    'id' => $id))->save(
                array(
                    'phone_no' => $phone, 
                    'sex' => $gender, 
                    'name' => $name));
            if ($result)
                echo $this->success('编辑会员信息成功');
            else {
                $rr = M('tmember_info')->where(
                    array(
                        'id' => $id, 
                        'phone_no' => $phone, 
                        'sex' => $gender, 
                        'name' => $name))->count();
                if ($rr)
                    $this->error('1');
                else
                    $this->error('编辑会员信息失败');
            }
        } else {
            $memberid = I('id');
            $memberinfo = M('tmember_info')->alias(i)
                ->join('tchannel t on i.channel_id = t.id')
                ->join('tmember_activity_stat s on s.member_id = i.id')
                ->where(array(
                'i.id' => $memberid))
                ->field(
                'i.address, i.id, i.birthday,t.name tname,i.add_time,i.name,i.sex,i.phone_no,sum(join_cnt)join_cnt,sum(send_cnt)send_cnt,sum(verify_cnt)verify_cnt')
                ->find();
            $this->assign('memberinfo', $memberinfo);
            $this->display();
        }
    }

    /*
     * 会员专项活动
     */
    public function activity() {
        $this->display();
    }

    /**
     * 会员更多专项活动 Enter description here .
     * ..
     */
    public function mactivity() {
        // 查询案例活动
        import('@.ORG.Util.Page'); // 导入分页类
        $where = array(
            'c.node_type' => array(
                'in', 
                '0,1'));
        $count = M()->table('tmarketing_info a')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->where($where)
            ->count();
        $Page = new Page($count, 3);
        $Page->setConfig('theme', 
            '<li><a>%totalRow% 条 %nowPage%/%totalPage% 页</a></li>%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $list = M()->table('tmarketing_info a')
            ->field(
            'a.wap_info,a.id,c.node_id,a.node_name,a.status,a.name,a.bg_pic,
					   a.batch_type,a.start_time,a.end_time,a.wap_info,c.node_short_name,a.push_img')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->where($where)
            ->limit($Page->firstRow, $Page->listRows)
            ->order('a.batch_o2o_top_time DESC')
            ->select();
        if ($list) {
            $imgName = C('O2O_DEFULT_IMG');
            foreach ($list as $k => $v) {
                // 查询渠道访问ID
                $chanwhere = array(
                    'batch_id' => $v['id'], 
                    'batch_type' => $v['batch_type']);
                $labelArr = M()->table('tbatch_channel')
                    ->field('id')
                    ->where($chanwhere)
                    ->order('click_count desc ')
                    ->find();
                $list[$k]['label_id'] = $labelArr['id'];
                // $pregResult =
                // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['wap_info'],$matches);
                if (! empty($v['push_img'])) {
                    $list[$k]['img'] = 'Home/Upload/oto/' . $v['push_img'];
                } elseif (! empty($v['bg_pic'])) {
                    $list[$k]['img'] = './Home/Upload/' . $v['bg_pic'];
                } else { // 默认图片
                    $list[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                         '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
                }
            }
            $this->assign('list', $list);
            $this->assign('page', $Page->show());
        }
        $this->display('activity_more');
    }

    /**
     * 设置奖项
     */
    public function setPrize() {
        $m_id = I('m_id', '');
        $result = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $m_id))->find();
        if (! $result) {
            $this->error('参数错误');
        }
        $cjConfig = $this->cjSetModel->getCjConfig($this->node_id, $m_id);
        $cjConfig['cj_rule_arr']['total_chance'] = cookie('rate') ? cookie('rate') : $cjConfig['cj_rule_arr']['total_chance'];
        $this->assign('is_cj', $result['is_cj']);
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $stepBar = D('CjSet')->getActStepsBar(ACTION_NAME, $m_id, '', $isReEdit,array('setActBasicInfo' => '基础信息', 'setPrize' => '奖项设定','publish'=>'活动发布'));
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        $this->display();
    }

    /**
     * 更新是否有抽奖这个字段，如果新增奖项的话，应该和新增奖品一样把is_cj字段设置为1，因为界面上要有区分
     */
    public function changeIsCj() {
        $m_id = I('m_id', '');
        $result = M('tmarketing_info')->where(
            array(
                'id' => $m_id, 
                'node_id' => $this->node_id))->save(
            array(
                'is_cj' => 1));
        if (false === $result) {
            $this->error('更新是否有抽奖错误');
        }
        $this->success();
    }

    /**
     * 添加奖品第一步选择奖品
     */
    public function addAward() {
        $m_id = I('m_id', '');
        $availableSourceType = '0,1';
        $prizeCateId = I('prizeCateId', '');
        $b_id = I('b_id', '');
        if (! $b_id) { // 如果没有b_id表示添加奖品
                       // 添加奖品的第一步，选择卡券（或者红包）//具体产品自己还没设计好，先不管让他选择卡券
            $this->redirect('Common/SelectJp/indexNew', 
                array('action'=>'member','availableSourceType'=>$availableSourceType,'availableTab'=>'1,2,3','next_step' => urlencode(U('Common/SelectJp/addToPrizeItem', array('m_id' => $m_id, 'prizeCateId' => $prizeCateId))))); // 给个参数让按钮显示成下一步
        }
    }
    
    // cvs格式输出
    function downloadCsvData($csv_data = array(), $filename, $arrayhead = array()) {
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header(
            "Content-Disposition: attachment; filename=" .
                 iconv("UTF-8", "gbk", $filename) . ".csv");
        header('Expires:0');
        header('Pragma:public');
        $csv_string = null;
        $csv_row = array();
        if (! empty($arrayhead)) {
            $current = array();
            foreach ($arrayhead as $item) {
                
                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
        foreach ($csv_data as $key => $csv_item) {
            $current = array();
            foreach ($csv_item as $item) {
                
                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
        $csv_string = implode("\r\n", $csv_row);
        echo $csv_string;
    }
    // 分类别下载
    function download() {
        $downtype = $_REQUEST['downtype'];
        $ctype = $_REQUEST['ctype'] ? $_REQUEST['ctype'] : 1;
        $mtype = $_REQUEST['mtype'] ? $_REQUEST['mtype'] : 1;
        $ndate = $_REQUEST['ndate'];
        $sdate = $_REQUEST['sdate'];
        if ($downtype == 1) {
            if ($mtype == 1) {
                $date = '"' . date('Ymd', strtotime('-8 day')) . '"';
                $where = $date . '< add_time and add_time < "' . date('Ymd') .
                     '"';
                $where2 = $date . '< trans_date and trans_date < "' . date(
                    'Ymd') . '"';
            }
            if ($mtype == 2) {
                $date = '"' . date('Ymd', strtotime('-31 day')) . '"';
                $where = $date . '< add_time and add_time < "' . date('Ymd') .
                     '"';
                $where2 = $date . '< trans_date and trans_date < "' . date(
                    'Ymd') . '"';
            }
            $fields1 = array(
                'count(join_cnt)' => 'join_cnt', 
                'trans_date' => 'date');
            $fields2 = array(
                'count(*)' => 'count', 
                'DATE_FORMAT(add_time,"%Y%m%d")' => 'date');
            if ($mtype == 3) {
                $date = '"' . date('Ymd', strtotime('-1 year')) . '"';
                $where = $date . '<= add_time and add_time < "' . date('Ymd') .
                     '"';
                $where2 = $date . '< trans_date and trans_date < "' . date(
                    'Ymd') . '"';
                $fields2 = array(
                    'count(*)' => 'count', 
                    'DATE_FORMAT(add_time,"%Y%m")' => 'date');
                $fields1 = array(
                    'sum(join_cnt)' => 'join_cnt', 
                    'DATE_FORMAT(trans_date,"%Y%m")' => 'date');
            }
            if ($mtype == 4) {
                $where = '"' . date('Ymd', strtotime($sdate[0])) . '"' .
                     ' <= add_time and add_time <= ' . '"' .
                     date('Ymd', strtotime($ndate[0])) . '"';
                $where2 = '"' . date('Ymd', strtotime($sdate[0])) .
                     '"< trans_date and trans_date < "' .
                     date('Ymd', strtotime($ndate[0])) . '"';
                if (strtotime($ndate[0]) >
                     (strtotime($sdate[0])) + 31 * 24 * 3600) {
                    $fields1 = array(
                        'sum(join_cnt)' => 'join_cnt', 
                        'DATE_FORMAT(trans_date,"%Y%m")' => 'date');
                    $fields2 = array(
                        'count(*)' => 'count', 
                        'DATE_FORMAT(add_time,"%Y%m")' => 'date');
                }
            }
            $mactive = M('tmember_activity_stat')->field($fields1)
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->where($where2)
                ->group('date')
                ->order('date asc')
                ->select();
            // 会员总数
            $mdata = M('tmember_info')->field($fields2)
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->where($where)
                ->group('date')
                ->order('date asc')
                ->select();
            $dataarray = array();
            foreach ($mdata as $v) {
                $mdata_bak[$v['date']] = $v['count'];
                $dataarray[] = $v['date'];
            }
            foreach ($mactive as $v) {
                $newactive[$v['date']] = $v['join_cnt'];
                $dataarray[] = $v['date'];
            }
            $dataarray = array_unique($dataarray);
            asort($dataarray);
            $downloaddata[] = array(
                '时间', 
                '活动参与次数', 
                '新增会员数');
            foreach ($dataarray as $v) {
                $xdata11 = $newactive[$v] > 0 ? $newactive[$v] : '0';
                $xdata12 = $mdata_bak[$v] > 0 ? $mdata_bak[$v] : '0';
                // $xdata13.=$v['add_num']>0 ? $v['add_num'].',':'0,';
                $downloaddata[] = array(
                    $v, 
                    $xdata11, 
                    $xdata12);
            }
            $this->downloadCsvData($downloaddata, '会员整体数据统计');
        } else if ($downtype == 2) {
            $sex_data = M()->table(
                array(
                    'tmember_nature_stat' => 'i'))
                ->field(
                array(
                    'sum(i.cnt)' => 'count', 
                    'sex'))
                ->group('i.sex')
                ->where(
                array(
                    'i.node_id' => $this->node_id))
                ->select();
            $downloaddata[] = array(
                '统计时间', 
                '会员总数', 
                '男比例', 
                '女比列');
            foreach ($sex_data as $v) {
                $sextotal += $v['count'];
                if ($v['sex'] == 1)
                    $msex = $v['count'];
            }
            $downloaddata[] = array(
                date('Y-m-d H:i:s'), 
                $sextotal, 
                ($msex * 100 / $sextotal) . '%', 
                (($sextotal - $msex) * 100 / $sextotal) . '%');
            $this->downloadCsvData($downloaddata, '会员属性分析');
        } else if ($downtype == 3) {
            $sql = 'select sum(cnt) allsum,province_code from tmember_nature_stat where node_id="' .
                 $this->node_id .
                 '" group by province_code  order by allsum desc';
            $memberactivy = M()->query($sql);
            foreach ($memberactivy as $k => $mv) {
                if (empty($mv['province_code'])) {
                    $shiftk = $k;
                    $memberactivy[count($memberactivy)] = $mv;
                    continue;
                }
                $array[] = $mv['province_code'];
            }
            unset($memberactivy[$shiftk]);
            $citylist = M('tcity_code')->where(
                array(
                    'city_level' => 1, 
                    'province_code' => array(
                        'in', 
                        $array)))
                ->field('province,province_code')
                ->select();
            foreach ($citylist as $mv) {
                $citylist_bak[$mv['province_code']] = $mv['province'];
            }
            $downloaddata[] = array(
                '省份', 
                '现有会员');
            foreach ($memberactivy as &$mv) {
                if ($mv['province_code'] != null)
                    $mv['province_code'] = $citylist_bak[$mv['province_code']];
                else
                    $mv['province_code'] = "其他";
                $downloaddata[] = $mv;
            }
            $this->downloadCsvData($downloaddata, '会员地域分布图');
        } else if ($downtype == 4) {
            if ($mtype == 1) {
                $date = '"' . date('Ymd', strtotime('-8 day')) . '"';
                $where1 = $date . '< i.add_time and i.add_time < "' . date(
                    'Ymd') . '"';
            }
            if ($mtype == 2) {
                $date = '"' . date('Ymd', strtotime('-31 day')) . '"';
                $where1 = $date . '< i.add_time and i.add_time < "' . date(
                    'Ymd') . '"';
            }
            if ($mtype == 3) {
                $date = '"' . date('Ymd', strtotime('-1 year')) . '"';
                $where1 = $date . '<= i.add_time and i.add_time < "' .
                     date('Ymd') . '"';
            }
            if ($mtype == 4) {
                $where1 = '"' . date('Ymd', strtotime($sdate)) . '"' .
                     ' <= i.add_time and i.add_time <= ' . '"' .
                     date('Ymd', strtotime($ndate)) . '"';
            }
            $chnallist = M()->table(
                array(
                    'tmember_info' => 'i'))
                ->join('tchannel t on i.channel_id = t.id')
                ->where(
                array(
                    'i.node_id' => $this->node_id))
                ->field(
                array(
                    'IFNULl(t.name,"其他")' => 'tname', 
                    'count(*)' => 'c'))
                ->order('i.channel_id  desc', 'c desc')
                ->where($where1)
                ->group('tname')
                ->select();
            global $totalnum;
            array_walk($chnallist, "MemberAction::sum");
            foreach ($chnallist as &$v) {
                $v['c'] = ($v['c'] * 100 / $totalnum) . '%';
            }
            $this->downloadCsvData($chnallist, '会员渠道数据', 
                array(
                    '渠道类别', 
                    '渠道比列'));
        }
    }

    static function sum($v, $k) {
        global $totalnum;
        $totalnum += $v['c'];
    }

    /**
     * 发布活动
     */
    public function publish() {
        cookie('rate', ' ', -1);
        // 查询活动
        $marketingModel = M('tmarketing_info');
        $m_id = I('m_id');
        $marketInfo = $marketingModel->where(
            array(
                'id' => $m_id, 
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        if (IS_POST) {
            $is_cj = I('is_cj', '0');
            $cj_resp_text = I('cj_resp_text', '', '');
            $no_award_notice = I('no_award_notice', '', '');
            $total_chance = I('total_chance', '', 'trim');
            $sort = I('get.cj_cate_to_sort');
            M()->startTrans();
            // 存是否抽奖
            $marketingModel->where(array(
                'id' => $m_id))->save(
                array(
                    'is_cj' => $is_cj));
            
            $ruleList = M('tcj_rule')->where(
                "batch_id = '{$m_id}' and status = '1'")->select();
            if (! $ruleList) {
                // 理论上不会进入这个逻辑里
                // 在创建活动的时候设置了默认值,参考_editMarketInfo
                log_write('系统异常：存抽奖表错误');
                $this->error('系统异常：存抽奖表错误');
            } else {
                if (count($ruleList) > 1) {
                    M()->rollback();
                    log_write('系统异常：存在多条启用的抽奖规则记录！');
                    $this->error('系统异常：存在多条启用的抽奖规则记录！');
                }
                $ruleInfo = $ruleList[0];
                // 编辑
                $data = array(
                    'total_chance' => $total_chance, 
                    'cj_resp_text' => $cj_resp_text, 
                    'no_award_notice' => $no_award_notice);
                $flag = M('tcj_rule')->where("id = '{$ruleInfo['id']}'")->save(
                    $data);
                if ($flag === false) {
                    M()->rollback();
                    log_write('保存失败！');
                    $this->error('保存失败！');
                }
                // 保存奖项排序
                $cjCateModel = M('tcj_cate');
                foreach ($sort as $cj_cate_id => $sortNum) {
                    $sortRe = $cjCateModel->where(
                        array(
                            'id' => $cj_cate_id, 
                            'batch_id' => $m_id, 
                            'node_id' => $this->node_id))->save(
                        array(
                            'sort' => $sortNum));
                    if (false === $sortRe) {
                        M()->rollback();
                        log_write('更新招募活动奖项排序失败!');
                        $this->error('更新招募活动奖项排序失败!');
                    }
                }
            }
            M()->commit();
            $this->success();
        }
        $isReEdit = I('isReEdit', '1');
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => $this->BATCH_TYPE, 
                'isReEdit' => $isReEdit, 
                'publishGroupModule' => GROUP_NAME . '/' . MODULE_NAME));
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batchId))->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        $result = M('tmarketing_info')->where(
            array(
                'id' => $batchId))->save(
            array(
                'status' => $status));
        if (false !== $result) {
            node_log(
                "会员招募活动" . $status == 1 ? '开启' : '停用' . "。名称：" . $result['name']);
            $this->success();
        } else {
            $this->error('更新失败');
        }
    }

    private function _editMarketInfo($data, $m_id = '') {
        $configData = serialize($data['config_data']);
        $data = array(
            'name' => $data['act_name'], 
            'node_id' => $this->nodeId, 
            'node_name' => $data['node_name'], 
            'wap_info' => $data['introduce'], 
            'log_img' => $data['node_logo'], 
            'start_time' => $data['act_time_from'] . '000000', 
            'end_time' => $data['act_time_to'] . '235959', 
            'sns_type' => $data['sns_type'], 
            'add_time' => date('YmdHis'), 
            'join_mode' => $data['join_mode'], 
            'status' => '1', 
            'pay_status' => '1', 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['bg_pic'], 
            'page_style' => $data['page_style'], 
            'member_card_id' => $data['member_card_id'],
            'config_data'=>$configData
            );
        M()->startTrans();
        $marketInfoModel = M('tmarketingInfo');
        if (! $m_id) { // 如果没有m_id表示增加
            $m_id = $marketInfoModel->add($data);
            if (! $m_id) {
                M()->rollback();
                log_write('新增活动失败!');
                $this->error('新增活动失败!');
            }
            // 如果是新增把默认的抽奖配置填上
            $ruleParam = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $m_id, 
                'jp_set_type' => 2,  // 1单奖品2多奖品
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                // 'total_chance' => 'default',
                'phone_total_count' => 1, 
                'phone_day_count' => 1, 
                'phone_total_part' => 1, 
                'phone_day_part' => 1, 
                'cj_button_text' => '开始抽奖', 
                'cj_resp_text' => '恭喜您！中奖了',  // 中奖提示信息
                'param1' => '', 
                'no_award_notice' => '很遗憾！未中奖');
            $flag = M('tcj_rule')->add($ruleParam);
            if (! $flag) {
                M()->rollback();
                log_write('新增默认抽奖失败!');
                $this->error('新增默认抽奖失败!');
            }else{
                $cjCateData = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $m_id, 
                    'node_id' => $this->node_id, 
                    'cj_rule_id' => $flag, 
                    'name' => '一等奖', 
                    'add_time' => date('YmdHis'), 
                    'status' => '1', 
                    'sort' => 1);
                $cat_id = M('tcj_cate')->add($cjCateData);
                if(!$cat_id){
                    M()->rollback();
                    log_write(print_r($cjCateData, TRUE));
                    $this->error('创建默认项失败！');
                }
            }
        } else {
            $flag = $marketInfoModel->where(array('id' => $m_id))->save($data);
            if (false === $flag) {
                M()->rollback();
                log_write('保存活动失败!');
                $this->error('保存活动失败!');
            }
        }
        M()->commit();
        return $m_id;
    }

    /**
     *
     * @param $name(tcollect_question_field的名字)
     * @return string(js里面设定的名字)
     */
    private function _getJsName($name) {
        $jsName = '';
        switch ($name) {
            case 'phone_no':
                $jsName = 'member_tel';
                break;
            case 'name':
                $jsName = 'member_name';
                break;
            case 'sex':
                $jsName = 'member_sex';
                break;
            case 'birthday':
                $jsName = 'member_birthday';
                break;
            case 'area':
                $jsName = 'member_area';
                break;
            default:
                $jsName = $name;
                break;
        }
        return $jsName;
    }
    
    // 会员中心设置 展示
    public function personCenterSet() {
        // 登陆页面配置
        $marketInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '29'))->find();
        $logoInfo = M('tecshop_banner')->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 2))->find();
        
        $centerConf = D("MemberInstall")->getMemberInstallShow($this->node_id);
        $linkUrl = self::memberCenterUrl($centerConf);
        $logoInfo['link_url'] = $linkUrl;
        
        $nodeAccount = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        $nodeAccountInfo = array_valtokey($nodeAccount, 'account_type');
        
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        $biaoti = $logoInfo['biaoti'];
        if ($biaoti == null) {
            $biaoti = $nodeInfo['node_short_name'];
        }
        
        $this->assign('marketInfo', $marketInfo);
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('biaoti', $biaoti);
        $this->display();
    }
    
    // 判断进入会员中心链接
    public function memberCenterUrl($config) {
        if ($config['member_center_url']) {
            return $config['member_center_url'];
        } else {
            $url = '';
            if (! session('?groupPhone')) {
                $surl = urlencode(
                    U('Label/Member/index', 
                        array(
                            'node_id' => $this->node_id), '', '', true));
                $link = U('Label/O2OLogin/index', 
                    array(
                        'node_id' => $this->node_id, 
                        'backcall' => 'bclick', 
                        'surl' => $surl), '', '', true);
                $url = make_short_url($link);
            } else {
                $url = make_short_url(
                    U('Label/Member/index', 
                        array(
                            'node_id' => $this->node_id), '', '', true));
            }
            $data = array(
                'member_center_url' => $url);
            if ($config) {
                $data['update_time'] = date("YmdHis");
                $res = M("tmember_center_config")->where(
                    array(
                        'node_id' => $this->node_id))->save($data);
            } else {
                $data['node_id'] = $this->node_id;
                $data['add_time'] = date("YmdHis");
                if ($this->_hasEcshop($this->node_id)) {
                    $data['phone_shop_flag'] = 1;
                    $data['shop_cart_flag'] = 1;
                    $data['my_order_flag'] = 1;
                    $data['my_red_flag'] = 1;
                    $data['my_addr_flag'] = 1;
                }
                $res = M("tmember_center_config")->add($data);
            }
            
            return $url;
        }
    }


    public function getQRcodeUrl($node_id)
    {
        //检测要存入二维码中的url是否是全路径 不是全路径则拼接成全路径返回
        $url = U('Label/O2OLogin/index',
                array(
                        'node_id' => $node_id), false, false, true); // 要存入二维码的URL全路径

        $location = strpos($url, 'ttp://');
        if ($location != 1) {
            // 查找http在不在里面 在里面则为全路径 不在里面则拼接路径
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        return $url;
    }
    
    // 会员中心设置 展示
    public function personCenterShowSet() {

        //查询该商户有没有会员中心渠道 如果没有 则添加渠道
        $sns_type = CommonConst::SNS_TYPE_MEMSTORENAV;
        $memberChannel = M('tchannel')->where(array('node_id'=>$this->nodeId,'type'=>'5','sns_type'=>$sns_type))->count();
        if(!$memberChannel){
            $channel_data = array(
                    'name' => '会员中心',
                    'type' => 5,
                    'sns_type' => $sns_type,
                    'status' => '1',
                    'node_id' => $this->nodeId,
                    'start_time' => date('YmdHis'),
                    'end_time' => date('YmdHis', strtotime("+1 year")),
                    'add_time' => date('YmdHis'),
                    'click_count' => 0,
                    'cj_count' => 0,
                    'send_count' => 0);
            M("tchannel")->add($channel_data);
        }

        $marketInfo = M('tmarketing_info')->where(array('node_id' => $this->node_id, 'batch_type' => '29'))->find();
        $customFieldsCondition = "node_id = {$this->node_id} OR node_id is null";
        $customFields = M('tcollect_question_field')->where($customFieldsCondition)->select();
        $logoInfo = M('tecshop_banner')->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 2))->find();
        // wap会员中心展示设置

        $list = $this->mInsModel->getMemberInstallShow($this->node_id);
        $linkUrl = $this->memberCenterUrl($list);
        $logoInfo['link_url'] = $linkUrl;

        $node_id = $this->nodeId;
        $url = $this->getQRcodeUrl($node_id);
        // 生成二维码存放在服务器
        $path = APP_PATH . 'Upload/MemberCode/';
        $name = $this->nodeId;
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }
        $filename = $path . $name . '.png';//这个是存放二维码的路径

        import('@.Vendor.phpqrcode.qrcode') or die('include file fail.');
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";

        //在服务器生成二维码
        QRcode::png($url, $filename, $errorCorrectionLevel,
                $matrixPointSize, 2);
        $mc_str = base64_encode(file_get_contents($filename));

        if ($mc_str) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        $this->assign('mcstr',$mc_str);


        // 获取商户信息(商户热线)
        $hotline = M("tnode_info")->where(
            array(
                'node_id' => $this->node_id))->getField("node_service_hotline");

        if (! $list) {
            $list = array(
                'qcode' => 0, 
                'user_data_name' => '个人资料', 
                'user_data_flag' => 1, 
                'my_privilege_name' => '我的特权', 
                'my_privilege_flag' => 0, 
                'my_dzq_name' => '我的卡券', 
                'my_dzq_flag' => 1, 
                'sign_name' => '每日签到', 
                'sign_flag' => 0, 
                'popular_activity_name' => '热门活动', 
                'popular_activity_flag' => 0, 
                'popular_activity' => '', 
                'receive_bonus_name' => '领取红包', 
                'receive_bonus_flag' => 0, 
                'receive_bonus' => '', 
                'msg_box_name' => '消息盒子', 
                'msg_box_flag' => 1, 
                'phone_shop_name' => '手机商城', 
                'phone_shop_flag' => 0, 
                'shop_cart_name' => '购物车', 
                'shop_cart_flag' => 0, 
                'integral_shop_name' => '积分商城', 
                'integral_shop_flag' => 0, 
                'my_integral_name' => '我的积分', 
                'my_integral_flag' => 0, 
                'my_order_name' => '我的订单', 
                'my_order_flag' => 0, 
                'my_red_name' => '我的红包', 
                'my_red_flag' => 0, 
                'my_addr_name' => '我的收货地址', 
                'my_addr_flag' => 0, 
                'nearby_store_name' => '附近门店', 
                'nearby_store_flag' => 0, 
                'tel_name' => '拨打客服电话', 
                'tel' => $hotline, 
                'tel_flag' => 1);
            
            if ($this->_hasEcshop($this->node_id)) {
                $list['phone_shop_flag'] = 1;
                $list['shop_cart_flag'] = 1;
                $list['my_order_flag'] = 1;
                $list['my_red_flag'] = 1;
                $list['my_addr_flag'] = 1;
            }
        } else {
            $customFieldsConfig = json_decode($list['custom_field_config'], TRUE);

            foreach($customFields as $key=>$val){
                if($customFieldsConfig[$val['name']] == '' && !strstr($val['name'], 'member')){
                    $customFields[$key]['is_show'] = '1';
                }else if($customFieldsConfig[$val['name']] != ''){
                   $customFields[$key]['is_show'] = $customFieldsConfig[$val['name']]; 
                }else{
                   $customFields[$key]['is_show'] = '0'; 
                }
            }
            if (! $list['tel']) {
                $list['tel'] = $hotline;
            }
        }

        $this->mInsModel->ini_custom_field_config($this->node_id);

        if ($this->_hasEcshop($this->node_id)) {
            $this->assign("pay_m2", 1);
        } else {
            $this->assign("pay_m2", 0);
        }
        if ($this->_hasIntegral($this->node_id)) {
            $this->assign("pay_m4", 1);
        } else {
            $this->assign("pay_m4", 0);
        }
        
        $batchArr = $this->mInsModel->getBatchData($list['popular_activity']);
        $batch_count = 0;
        if ($batchArr) {
            $batch_count = count($batchArr);
        }
        $bonusArr = $this->mInsModel->getRedData($list['receive_bonus']);
        $bonus_count = 0;
        if ($bonusArr) {
            $bonus_count = count($bonusArr);
        }
        if($list['navigation_id']>0)
        {
            $navigation=M("tmarketing_info")->where(array('id'=>$list['navigation_id']))->find();
        }else{
            $navigation=array('name'=>'未设置店铺导航');
        }


        $this->assign('mCenterIns', $list);
        $this->assign('batchData', $batchArr);
        $this->assign('batch_count', $batch_count);
        $this->assign('bonusData', $bonusArr);
        $this->assign('bonus_count', $bonus_count);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('customFields', $customFields);
        /*获取门店导航数量*/
        $navigation_count = M('tmarketing_info')->where(array('node_id' => $this->node_id, 'batch_type' => 17))->count(); // 查询满足要求的总记录数
        $this->assign('navigation_count', $navigation_count);
        $this->assign('navigation', $navigation);
        $this->display();
    }

    /**
     * 检查活动是否存在label_id 没有生成labelId
     */
    public function selGetLabelId() {
        $batch_type = I('batch_type', null);
        $batch_id = I('batch_id', null);
        
        $label_id = D("MemberInstall")->getLabelId($this->node_id, $batch_id, 
            $batch_type);
        
        $this->success($label_id);
    }
    
    // 根据tgoods_info id获取红包tbonus_info 信息
    public function getBonusData() {
        $g_id = I("g_id", '');

        $result = M()->table("tbonus_info b")->join('tgoods_info g on b.id = g.bonus_id')
            ->join("tbonus_detail d on b.id = d.bonus_id")
            ->field('b.id, b.bonus_page_name, b.bonus_num, d.get_num')
            ->where(array(
            'g.id' => $g_id))
            ->find();
        
        if (! $result) {
            $this->error("获取红包信息失败！");
        }
        $this->success($result);
    }
    
    // 会员中心设置 更改
    public function editMemberCenter() {
        $type = I('type', 0); // 需要改变展示/隐藏的 控件
        $pay_m2 = I('pay_m2', 0); // 判断是否开通多宝电商 0否 1 是
                                  
        // 获取商户权限
        $o2o = $this->_hasEcshop($this->node_id);
        $integ = $this->_hasIntegral($this->node_id);
        
        $data = array();
        if ($type == 0) { // 个人资料
            $user_data_name = I('user_data_name', '个人资料');
            $data['user_data_name'] = $user_data_name;
            $user_data = I('user_data', 0);
            $data['user_data_flag'] = $user_data;
            $customFields = I('post.customFields', '', 'string');
            if($customFields != ''){
                $customFields = explode('&', $customFields);
                $customFieldsData = array();
                foreach($customFields as $val){
                    $tempArray = explode('=', $val);
                    $customFieldsData[$tempArray[0]] = $tempArray[1];
                }
                $data['custom_field_config'] = json_encode($customFieldsData);
            }
        }
        
        if ($type == 1) { // 我的特权
            $my_privilege_name = I('my_privilege_name', '我的特权');
            $data['my_privilege_name'] = $my_privilege_name;
            $my_privilege = I('privilege', 0);
            $data['my_privilege_flag'] = $my_privilege;
        }
        
        if ($type == 2) { // 我的卡券
            $my_dzq_name = I('my_dzq_name', '我的卡券');
            $data['my_dzq_name'] = $my_dzq_name;
            $dzq_flag = I('dzq_flag', 0);
            $data['my_dzq_flag'] = $dzq_flag;
        }
        
        if ($type == 3) { // 每日签到
            $sign_name = I('sign_name', '每日签到');
            $data['sign_name'] = $sign_name;
            $sign_flag = I('sign_flag', 0);
            if ($sign_flag == 1) {
                if (! $integ) {
                    $this->error("无法使用，请开通积分营销功能");
                }
            }
            $data['sign_flag'] = $sign_flag;
        }
        
        if ($type == 4) { // 热门活动
            $popular_activity_name = I('popular_activity_name', '热门活动');
            $data['popular_activity_name'] = $popular_activity_name;
            $activity_flag = I('activity_flag', 0);
            $data['popular_activity_flag'] = $activity_flag;
            $popular_str = I('popular_str', '');
            $data['popular_activity'] = $popular_str;
        }
        
        if ($type == 5) { // 领取红包
            $receive_bonus_name = I('receive_bonus_name', '领取红包');
            $data['receive_bonus_name'] = $receive_bonus_name;
            $bonus_flag = I('bonus_flag', 0);
            $data['receive_bonus_flag'] = $bonus_flag;
            $bonus_str = I('bonus_str', '');
            $data['receive_bonus'] = $bonus_str;
        }
        
        if ($type == 6) { // 手机商城
            $phone_shop_name = I('phone_shop_name', '手机商城');
            $data['phone_shop_name'] = $phone_shop_name;
            $shop_flag = I('shop_flag', 0);
            if ($shop_flag == 1) {
                if (! $o2o) {
                    $this->error("无法使用，立即去装修旺财小店");
                }
            }
            $data['phone_shop_flag'] = $shop_flag;
        }
        
        if ($type == 7) { // 我的订单
            $my_order_name = I('my_order_name', '我的订单');
            $data['my_order_name'] = $my_order_name;
            $order_flag = I('order_flag', 0);
            if ($order_flag == 1) {
                if (! $o2o) {
                    $this->error("无法使用，立即去装修旺财小店");
                }
            }
            $data['my_order_flag'] = $order_flag;
        }
        
        if ($type == 8) { // 我的红包
            $my_red_name = I('my_red_name', '我的红包');
            $data['my_red_name'] = $my_red_name;
            $red_flag = I('red_flag', 0);
            if ($red_flag == 1) {
                if (! $o2o) {
                    $this->error("无法使用，立即去装修旺财小店");
                }
            }
            $data['my_red_flag'] = $red_flag;
        }
        
        if ($type == 9) { // 我的收货地址
            $my_addr_name = I('my_addr_name', '我的收货地址');
            $data['my_addr_name'] = $my_addr_name;
            $addr_flag = I('addr_flag', 0);
            if ($addr_flag == 1) {
                if (! $o2o) {
                    $this->error("无法使用，立即去装修旺财小店");
                }
            }
            $data['my_addr_flag'] = $addr_flag;
        }
        
        if ($type == 10) { // 附近门店
            $nearby_store_name = I('nearby_store_name', '附近门店');
            $data['nearby_store_name'] = $nearby_store_name;
            $store_flag = I('store_flag', 0);
            $data['nearby_store_flag'] = $store_flag;
            if ($store_flag == 1) {
                $batch_type = CommonConst::BATCH_TYPE_STORE_LBS;
                $m_id = I('navigation');
                if (!$m_id) {
                    $this->error("没有创建门店导航，请添加后再设置~~");
                }
                
                $label_id = D("MemberInstall")->getLabelId($this->node_id, 
                    $m_id, $batch_type);
                if ($label_id == - 2) {
                    $this->error("创建失败！");
                }
                
                if ($label_id == - 3) {
                    $this->error("启用失败！");
                }
                $data['navigation_id']=$m_id;
                
                $data['url'] = U('Label/ListShop/index', 
                    array(
                        'id' => $label_id), '', '', true);
            }
        }
        
        if ($type == 11) { // 消息盒子
            $msg_box_name = I('msg_box_name', '消息盒子');
            $data['msg_box_name'] = $msg_box_name;
            $msg_box_flag = I('msg_box_flag', 0);
            $data['msg_box_flag'] = $msg_box_flag;
        }
        
        if ($type == 12) { // 购物车
            $shop_cart_name = I('shop_cart_name', '购物车');
            $data['shop_cart_name'] = $shop_cart_name;
            $shop_cart_flag = I('shop_cart_flag', 0);
            if ($shop_cart_flag == 1) {
                if (! $o2o) {
                    $this->error("无法使用，立即去装修旺财小店");
                }
            }
            $data['shop_cart_flag'] = $shop_cart_flag;
        }
        
        if ($type == 13) { // 积分商城
            $integral_shop_name = I('integral_shop_name', '积分商城');
            $data['integral_shop_name'] = $integral_shop_name;
            $integral_shop_flag = I('integral_shop_flag', 0);
            if ($integral_shop_flag == 1) {
                if (! $integ) {
                    $this->error("无法使用，请开通积分营销功能");
                }
            }
            $data['integral_shop_flag'] = $integral_shop_flag;
        }
        
        if ($type == 14) { // 二维码
            $qcode_flag = I('qcode_flag', 0);
            $vipname_flag = I('vipname_flag', 0);
            $data['qcode_flag'] = $qcode_flag;
            $data['vipname_flag'] = $vipname_flag;
        }
        
        if ($type == 15) { // 客服电话
            $tel_name = I('tel_name', '客服电话');
            $data['tel_name'] = $tel_name;
            $tel = I('tel', '');
            $data['tel'] = $tel;
            $tel_flag = I('tel_flag', 0);
            $data['tel_flag'] = $tel_flag;
        }
        
        if ($type == 16) { // 我的积分
            $my_integral_name = I('my_integral_name', '我的积分');
            $data['my_integral_name'] = $my_integral_name;
            $my_integral_flag = I('my_integral_flag', 0);
            if ($my_integral_flag == 1) {
                if (! $integ) {
                    $this->error("无法使用，请开通积分营销功能");
                }
            }
            $data['my_integral_flag'] = $my_integral_flag;
        }
        
        $result = array();
        $res = M('tmember_center_config')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $res) {
            $data['node_id'] = $this->node_id;
            $data['add_time'] = date('YmdHis');
            
            if ($pay_m2 == 1 && strstr(",6,12,7,8,9", "," . $type) == false) {
                $data['phone_shop_flag'] = 1;
                $data['shop_cart_flag'] = 1;
                $data['my_order_flag'] = 1;
                $data['my_red_flag'] = 1;
                $data['my_addr_flag'] = 1;
            }
            
            if ($type != 15) {
                $tel = M("tnode_info")->where(
                    array(
                        'node_id' => $this->node_id))->getField(
                    "node_service_hotline");
                $data['tel'] = $tel;
            }
            
            $result = M('tmember_center_config')->add($data);
        } else {
            $data['update_time'] = date('YmdHis');
            
            $result = M('tmember_center_config')->where(
                array(
                    'node_id' => $this->node_id))->save($data);
        }
        if ($result) {
            $this->success("修改成功！");
        } else {
            $this->error("修改失败！");
        }
    }
    
    // logo
    public function logo_add() {
        if ($this->isPost()) {
            $m_id = I('post.m_id', null);
            $node_name = I('post.node_name', null);
            $logo_id = I('post.logo_id', null);
            if (! $m_id || ! $node_name) {
                $this->error('数据错误');
            }
            $data = array(
                'biaoti' => $node_name, 
                'link_url' => C('CURRENT_HOST') .
                     'index.php?g=Label&m=MyOrder&a=index&node_id=' .
                     $this->node_id);
            $img = I('post.e_logo_img', null);
            if ($img) {
                $img = str_replace('..', '', $img);
                $data['img_url'] = $img;
            }
            
            if ($logo_id) {
                $ret = M('tecshop_banner')->where(
                    array(
                        'id' => $logo_id))->save($data);
            } else {
                $ret = M('tecshop_banner')->add(
                    array(
                        'm_id' => $m_id, 
                        'node_id' => $this->node_id, 
                        'ban_type' => 2, 
                        'biaoti' => $node_name, 
                        'img_url' => $img, 
                        'link_url' => C('CURRENT_HOST') .
                             'index.php?g=Label&m=MyOrder&a=index&node_id=' .
                             $this->node_id, 
                            'add_time' => date('YmdHis')));
            }
            if ($ret === false) {
                $this->error("LOGO保存失败");
            }
        }
        redirect(U('personCenterSet'));
    }
    
    // 会员卡
    public function memberCard() {
        $list = $this->mInsModel->getMemberCards($this->node_id);
        $nodeInfo = get_node_info($this->node_id);
        // $node_logo = get_upload_url($nodeInfo['head_photo']);
        
        for ($i = 0; $i < count($list); $i ++) {
            $list[$i]['order'] = $i + 1;
            // if(empty($list[$i]['node_logo'])) {
            // $list[$i]['node_logo'] = $node_logo;
            // } else {
            // $list[$i]['node_logo'] = get_upload_url($list[$i]['node_logo']);
            // }
            $list[$i]['node_logo'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                 '/Image/Whygl/card_logo.png';
            
            if (empty($list[$i]['bg_pic'])) {
                $list[$i]['bg_pic'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/Whygl/mb_top_bg1.png';
            }
        }
        
        $this->assign('list', $list);
        $this->assign('card_num', count($list)); // 用于判断是否达到会员卡制作上限(5个)
        $this->assign("card_code", "pic4.jpg");
        $this->display();
    }
    
    // 添加新会员卡
    public function addMemberCard() {
        $nodeInfo = get_node_info($this->node_id);
        // $node_name = $nodeInfo['node_name'];
        // $node_logo = get_upload_url($nodeInfo['head_photo']);
        $node_logo = C('TMPL_PARSE_STRING.__PUBLIC__') .
             '/Image/Whygl/card_logo.png';
        
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $this->assign("card_code", "pic4.jpg");
        $this->display();
    }
    
    // 添加/修改 会员卡
    public function mCardAdd() {
        $sub_type = I("sub_type", 0); // 提交类型 0新增 1更新
        $card_name = I("card_name", '');
        // $node_name = I("node_name", '');
        // $node_logo = I("node_logo", '');
        $page_style = I("template", 1);
        $bg_style = I("bg_style", 1);
        $bg_pic = I("bg_img", '');
        $equity_type = I("equity_type", 1); // 判断是第几张会员卡的提交
        $equity = I("equity" . $equity_type, '');
        $receipt = I("receipt", '');
        
        $data = array(
            'node_id' => $this->node_id, 
            'card_name' => $card_name, 
            // 'node_name' => $node_name,
            // 'node_logo' => $node_logo,
            'page_style' => $page_style, 
            'bg_style' => $bg_style, 
            'bg_pic' => $bg_pic, 
            'equity' => $equity, 
            'receipt' => $receipt);
        if ($sub_type == 0) {
            $cardFlag = M('tmember_cards')->where(
                array(
                    'node_id' => $this->node_id, 
                    'card_name' => $card_name))->count();
            if ($cardFlag > 0) {
                $this->error('该会员卡名称已经存在，请更换');
            }
            
            $data['add_time'] = date('YmdHis');
            $data['status'] = 0;
            
            $result = M('tmember_cards')->add($data);
            if ($result) {
                $this->success('会员添加成功');
            } else {
                $this->error('会员添加失败！');
            }
        } else {
            $card_id = I("card_id", '');
            $data['update_time'] = date('YmdHis');
            $result = M('tmember_cards')->where(
                array(
                    'id' => $card_id))->save($data);
            if ($result) {
                $this->success('会员修改成功');
            } else {
                $this->error('会员修改失败！');
            }
        }
    }
    
    // 批量(csv)导入会员
    public function uploadMembers() {
        $card_type = I('card_id', null); // 会员卡id
        
        if ($card_type) {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 1024 * 1024;
            $upload->allowExts = array(
                "csv");
            $upload->savePath = APP_PATH . '/Upload/MemberUsers/'; // 设置附件
            $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
            if (! $upload->upload()) { // 上传错误提示错误信息
                $errormsg = $upload->getErrorMsg();
                $this->error($errormsg);
            } else { // 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                $fileUrl = $info[0]['savepath'] . $info[0]['savename'];
            }
            
            $fileName = $info[0]['savename'];
            
            $row = 0; // 导入文件的总条数包括表头
            $error = '';
            $erroName = C('DOWN_TEMP') . date('YmdHis') . '.csv'; // 上传失败的会员数据
            
            $erroFileHandle = fopen($erroName, "wb");
            fwrite($erroFileHandle, chr(0XEF) . chr(0xBB) . chr(0XBF)); // 输出BOM头防止微软软件打开文件乱码,该方法对未升级的office2007无效
            if (! $erroFileHandle) {
                log::write('批量添加会员错误：会员信息文件打开失败');
                unlink($fileName);
                $this->error('系统出错');
            }
            $fileField = array(
                '姓名', 
                '手机号', 
                '性别', 
                '生日', 
                '省', 
                '市', 
                '区');
            // $fileField[] = '错误原因';
            fputcsv($erroFileHandle, $fileField);

            $label = $this->mInsModel->judgedLabelFlag($this->node_id, "批量导入");
            if (! $label) {
                $label = $this->mInsModel->addLabel($this->node_id, "批量导入");
            }
            
            $importMember = array(); // 导入成功会员集合
            $importErrM = ""; // 手机号存在导入失败
            $importErrS = ""; // 入库失败
            $importDataErr = ""; // 数据有误
            if (($handle = fopen($fileUrl, "rw")) !== FALSE) {
                while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { // 读取csv文件
                    ++ $row;
                    $data = utf8Array($data);
                    if ($row == 1) {
                        $arrDiff = array_diff_assoc($data, $fileField);
                        if (count($data) != count($fileField) ||
                             ! empty($arrDiff)) {
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
                    if (empty($data[1])) {
                        break;
                    }
                }
                fclose($handle);
                fclose($erroFileHandle);
                
                if ($row < 2) {
                    unlink($erroName);
                    unlink($fileUrl);
                    $this->error('您上传的文件中没有找到会员数据!');
                }
                $totalNum = $row - 1; // 一共上传的会员数量
                if ($totalNum > 1001) {
                    $this->error("每次导入数据限1000条以内！");
                }
            }
            
            if (($handle = fopen($fileUrl, "rw")) !== FALSE) {
                $row = 0;
                $succNum = 0;
                M()->startTrans();
                while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { // 读取csv文件
                    ++ $row;
                    if ($row == 1) {
                        continue;
                    }
                    $data = utf8Array($data);
                    
                    // 校验各个字段
                    $name = $data[0];
                    $phone = $data[1];
                    if (empty($phone)) {
                        if (empty($importDataErr)) {
                            $importDataErr = $row;
                        } else {
                            $importDataErr .= "、" . $row;
                        }
                        continue;
                    }
                    $sex = ($data[2] == '男') ? 1 : 2;
                    $birthday = date('Ymd', strtotime($data[3]));
                    $citycode = '';
                    $pro = trim($data[4]);
                    if (! empty($pro)) {
                        $citycode = $this->mInsModel->cacheCityData($data[4], $data[5],
                            $data[6]);
                        if (! $citycode) {
                            if (empty($importDataErr)) {
                                $importDataErr = $row;
                            } else {
                                $importDataErr .= "、" . $row;
                            }
                            continue;
                        }
                    }
                    
                    $erroInfo = '';
                    if (! check_str($phone, 
                        array(
                            'null' => false, 
                            'strtype' => 'mobile'), $error)) {
                        $erroInfo .= "手机号码{$error}";
                    }
                    if (! check_str($name, 
                        array(
                            'null' => false, 
                            'maxlen_cn' => '7'), $error)) {
                        $erroInfo .= "会员名称{$error}";
                    }
                    
                    if (! empty($erroInfo)) {
                        $erroArr = array(
                            $phone, 
                            $name, 
                            $sex, 
                            $birthday, 
                            $erroInfo);
                        fputcsv($erroFileHandle, $erroArr);
                        continue;
                    }
                    
                    // 判断会员是否存在
                    $mflag = M("tmember_info")->where(
                        array(
                            'node_id' => $this->node_id, 
                            'phone_no' => $phone, 
                            'status' => '0'))->find();
                    if ($mflag) {
                        if (empty($importErrM)) {
                            $importErrM = $row;
                        } else {
                            $importErrM .= "、" . $row;
                        }
                        continue;
                    }
                    
                    $member_num = $this->mInsModel->makeMemberNumber($this->node_id);
                    
                    // 符合条件的员工信息
                    $importMember = array(
                        'node_id' => $this->node_id, 
                        'phone_no' => $phone, 
                        'name' => $name, 
                        'sex' => $sex, 
                        'years' => date('Y', strtotime($birthday)), 
                        'month_days' => date('m', strtotime($birthday)) .
                             date('d', strtotime($birthday)), 
                            'birthday' => $birthday, 
                            'citycode' => $citycode, 
                            'add_time' => date('YmdHis'), 
                            'channel_id' => 0, 
                            'status' => '0', 
                            'card_id' => $card_type, 
                            'member_num' => $member_num);
                    $result = M('tmember_info')->add($importMember);
                    if ($result) {
                        $this->mInsModel->makeMemberCode($this->node_id, $result);
                        
                        $this->mInsModel->member_label_ex($this->node_id, $result, $label);
                        
                        $succNum ++;
                    } else {
                        if (empty($importErrS)) {
                            $importErrS = $row;
                        } else {
                            $importErrS .= "、" . $row;
                        }
                        M()->rollback();
                        continue;
                    }
                }
                fclose($handle);
                fclose($erroFileHandle);
                
                M()->commit();
                $errStr = '';
                if (! empty($importErrS)) {
                    if (empty($errStr)) {
                        $errStr = '文件中的';
                    }
                    $errStr .= $importErrS . "行数据导入失败，错误原因：保存失败；";
                }
                if (! empty($importErrM)) {
                    if (empty($errStr)) {
                        $errStr = '文件中的';
                    }
                    $errStr .= $importErrM . "行数据无法导入，错误原因：手机号码已存在；";
                }
                if (! empty($importDataErr)) {
                    if (empty($errStr)) {
                        $errStr = '文件中的';
                    }
                    $errStr .= $importDataErr . "行数据有误，错误原因：手机号或者城市数据有误；";
                }
                $this->ajaxReturn($data, "导入成功，共导入{$succNum}个会员！" . $errStr, 1);
            }
            log::write('批量添加员工：上传文件打开失败');
            fclose($erroFileHandle);
            fclose($handle);
            unlink($erroName);
            unlink($fileUrl);
            $this->error('系统出错');
        }

        $cardsData = $this->mInsModel->getMemberCards($this->node_id);
        
        $this->assign("cardsData", $cardsData);
        $this->display();
    }
    
    // 下载会员(csv)
    public function downloadMember() {
        $cj_title = I("field_title", '');
        $s_field = I("field", '');
        // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $members = M()->table("tmember_info i")->join(
                "tmember_cards c on i.card_id = c.id")
                ->join('tchannel n on n.id = i.channel_id')
                ->join('tmember_activity_stat s on s.member_id = i.id')
                ->join('tfb_adb_user_store taus on taus.openid=i.mwx_openid')
                ->join('tstore_info ti on ti.store_id=taus.store_id')
                ->field(
                "i.name,i.phone_no,i.sex,i.birthday,i.address,i.add_time,i.nickname,i.member_num,i.update_time,i.point,c.card_name,n.name channel_name,sum(s.join_cnt) join_cnt,sum(s.send_cnt) send_cnt,sum(s.verify_cnt) verify_cnt,ti.store_short_name store_short_name")
                ->where(
                array(
                    'i.node_id' => $this->node_id))
                ->group('i.id,DATE_FORMAT(i.add_time,"%Y%m%d")')
                ->select();
        } else {
            $members = M()->table("tmember_info i")->join(
                "tmember_cards c on i.card_id = c.id")
                ->join('tchannel n on n.id = i.channel_id')
                ->join('tmember_activity_stat s on s.member_id = i.id')
                ->field(
                "i.name,i.phone_no,i.sex,i.birthday,i.address,i.add_time,i.nickname,i.member_num,i.update_time,i.point,c.card_name,n.name channel_name,sum(s.join_cnt) join_cnt,sum(s.send_cnt) send_cnt,sum(s.verify_cnt) verify_cnt")
                ->where(
                array(
                    'i.node_id' => $this->node_id))
                ->group('i.id,DATE_FORMAT(i.add_time,"%Y%m%d")')
                ->select();
        }
        if ($cj_title) {
            if (! $members) {
                exit();
            } else {
                $fileName = '会员数据导出.csv';
                $fileName = iconv('utf-8', 'gb2312', $fileName);
                header("Content-type: text/plain");
                header("Accept-Ranges: bytes");
                header("Content-Disposition: attachment; filename=" . $fileName);
                header(
                    "Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                header("Expires: 0");
                
                echo $cj_title = iconv('utf-8', 'gbk', $cj_title . "\r\n");
                
                $fieldArr = explode(',', $s_field);
                foreach ($members as $k => $v) {
                    $line_data = "";
                    foreach ($fieldArr as $val) {
                        if (empty($line_data)) {
                            $line_data = $v[$val];
                        } else {
                            if ($val == 'sex') {
                                $line_data .= ',' . ($v[$val] == 1 ? '男' : '女');
                            } else {
                                $line_data .= ',' . $v[$val];
                            }
                        }
                    }
                    $line_data .= "\r\n";
                    
                    echo iconv('utf-8', 'gbk', $line_data);
                }
                
                exit();
            }
        }
        
        $this->assign("adb_flag", $adb_flag);
        $this->assign("member_num", count($members));
        $this->display();
    }

    public function removeRelation() {
        $openid = I("openid");
        $where = array();
        $where['openid'] = $openid;
        $re = M("tfb_adb_user_store")->where($where)->delete();
        if ($re) {
            $this->ajaxReturn(
                array(
                    'status' => 1, 
                    'info' => '门店解绑成功!'));
        } else {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '门店解绑失败!'));
        }
    }


    //zhaobl
    public function msgcollect()
    {

        import('ORG.Util.Page'); // 导入分页类

        $totalCount = $this->collectModel->totalCount($this->nodeId);

        $Page = new Page($totalCount, 5);//数据总条数

        $result = $this->collectModel->collectInfo($this->nodeId, $Page->firstRow, $Page->listRows);

        // 分页显示输出
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('collectCount',$totalCount);
        $this->assign('residueCount',15-$totalCount);
        $this->assign('collect',$result);
        $this->display();

    }

    /**
     * 采集信息初始化
     */
    public function InfoInit()
    {
                $sql = "SELECT node_id FROM `tmember_info` GROUP BY node_id";
                $nodeList = M('tmember_info')->query($sql);

                foreach($nodeList as $key=>$val){
                    if($val['node_id']){
                        //$result = $this->collectModel->collectInfo($val['node_id']);

                        $model = M('tcollect_question_field');
                        $result = $model->where("node_id = ".$val['node_id'])->select();
                        $list =array();
                        foreach($result as $k1=>&$v1){
                            if($v1['value_list'] != ''){
                                $list[] = explode('|',$v1['value_list']);
                                foreach(explode('|',$v1['value_list']) as $kkk=>$vvv){
                                    $arr = explode(':',$vvv);
                                    $v1['value_lists'][$arr[0]] = $arr[1];
                                }
                            }
                        }

                        foreach($result as $k=>$v){
                            foreach($v['value_lists'] as $kk=>$vv){
                                $whe =  '"'.$v['name'].'":"'.$kk.'"';
                                $likeSql = "SELECT count(*) as 'co' FROM `tmember_info` WHERE node_id=".$val['node_id']." AND custom_field_data LIKE '%$whe%'";
                                $countInfo = M()->query($likeSql);
                                $data['member_cnt'] = $countInfo[0]['co'];
                                $data['node_id'] = $val['node_id'];
                                $data['field_id'] = $v['id'];
                                $data['field_value'] = $kk;
                                $data['field_name'] = $vv;
                                $addResult = M('tmember_attribute_stat')->add($data);
                            }
                        }
                    }
                }
        $this->success('初始化成功');

    }

    /**
     * 增加自定义采集项信息
     */
    public function addCollect()
    {
        $fielsCount = $this->collectModel->getCount($this->nodeId);

            if($fielsCount > 14){
                $this->ajaxReturn(array('error'=>'1001', 'msg'=>'自定义采集项已创建满15个'));
                exit;
            }
        $lastCount = $this->collectModel->lastCount($this->node_id);

        $name = 'member_'.($lastCount+1);

        $arr = I('post.');

        $text = $arr['collect_name'];//选项名
        $content = $arr['setopt'];//选项内容

        if(!$text || !$content){
            $this->error('选项参数不能为空');
        }
        $content = array_unique($content); // 删除重复值

        $result = $this->collectModel->addCollectField($name,$text,$content,$this->node_id);

        if($result){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    /**
     * 修改采集选项走这里
     */
    public function editCollect()
    {
        $arr = I('post.');
        $id = $arr['id'];//选项id
        $content = $arr['setopt'];//选项内容

        $content = array_unique($content); // 删除重复值

        if(!$id || !$content){
            $this->error('选项参数不能为空');
        }
        $result = $this->collectModel->editCollectField($id,$content,$this->node_id);

        if($result){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    /**
     * 点击会员数量进来  显示会员信息
     */
    public function msginfo()
    {
        import('ORG.Util.Page'); // 导入分页类

        $text = I('param.text');
        $opt = I('param.opt');
        $name = I('param.name');
        $value = I('param.value');
        $count = I('param.count');
        $node_id = $this->node_id;
        $whe = '"'.$name.'":"'.$value.'"';

        $Page = new Page($count, 10);//数据总条数

        $sql = "SELECT a.*,m.join_total,m.send_total,m.verify_total,m.shop_total,m.shopline_total,n.name channel_name FROM tmember_info a
LEFT JOIN tmember_activity_total m ON m.member_id = a.id
LEFT JOIN tchannel n ON n.id=a.channel_id WHERE ( a.node_id = '".$node_id."' ) AND ( phone_no IN (SELECT phone_no FROM `tmember_info` WHERE node_id= '".$node_id."' AND custom_field_data LIKE '%$whe%') )
ORDER BY a.add_time DESC LIMIT $Page->firstRow, $Page->listRows ";
        $result = M()->query($sql);

        // 分页显示输出
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('text',$text);
        $this->assign('opt',$opt);
        $this->assign('msginfo',$result);
        $this->display();
    }
}