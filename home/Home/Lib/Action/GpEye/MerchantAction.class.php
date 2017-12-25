<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class MerchantAction extends GpBaseAction
{
    //public $_authAccessMap = '*';
    private $filePath;
    public function _initialize()
    {
        parent::_initialize();

        $this->filePath = APP_PATH.'Upload/GpEye/';
        if (!file_exists($this->filePath)) {
            $this->createFolder($this->filePath);
        }
    }
    private function createFolder($path)
    {
        if (!file_exists($path)) {
            $this->createFolder(dirname($path));
            mkdir($path, 0777);
        }
    }
    public function index()
    {
        $map = array();
        $data = I('get.');
        if (!empty($data['id'])) {
            $map['id'] = (int) $data['id'];
        }
        if (!isset($data['status'])) {
            $data['status'] = -1;
        }
        if ($data['status'] != '-1') {
            $map['status'] = $data['status'];
        }
        if (!empty($data['store_name'])) {
            $map['_string'] = " ((store_name like '%".$data['store_name']."%') or (store_short_name like '%".$data['store_name']."%')) ";
        }

        $mapcount = D('GpMerchant')->getMerchantListCount($map);
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = D('GpMerchant')->getMerchantList($map, 'id desc', $Page->firstRow.','.$Page->listRows);
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->display('Merchant/index');
    }
    public function down_load()
    {
        $map = array();
        $data = I('get.');
        if (!empty($data['id'])) {
            $map['id'] = (int) $data['id'];
        }
        if (!isset($data['status'])) {
            $data['status'] = -1;
        }
        if ($data['status'] != '-1') {
            $map['status'] = $data['status'];
        }
        if (!empty($data['store_name'])) {
            $map['_string'] = " ((store_name like '%".$data['store_name']."%') or (store_short_name like '%".$data['store_name']."%')) ";
        }

        $mapcount = D('GpMerchant')->getMerchantListCount($map);
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, $mapcount); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = D('GpMerchant')->getMerchantList($map, 'id desc', $Page->firstRow.','.$Page->listRows);
        if (!count($list)) {
            $this->error('没有合适的下载数据');
        }
        $fileName = '加盟商列表 '.'.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header('Content-type: text/plain');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $cj_title = "加盟商id,添加时间,加盟商名称,负责人,手机号,邮箱,门店简称,省,市,区,详细地址,联系电话,所属商圈,门店简介,技师数,客户数,状态\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);

        foreach ($list as $vo) {
            $vo['province_name'] = M('tcity_code')->where(array(
                'city_level' => 1,
                'province_code' => $vo['province_code'],
            ))->getField('province');

            $vo['city_name'] = M('tcity_code')->where(array(
                'city_level' => 2,
                'province_code' => $vo['province_code'],
                'city_code' => $vo['city_code'],
            ))->getField('city');

            $vo['town_name'] = M('tcity_code')->where(array(
                'city_level' => 3,
                'province_code' => $vo['province_code'],
                'city_code' => $vo['city_code'],
                'town_code' => $vo['town_code'],
            ))->getField('town');
            $vo['business_circle_name'] = M('tcity_code')->where(array(
                'city_level' => 4,
                'province_code' => $vo['province_code'],
                'city_code' => $vo['city_code'],
                'town_code' => $vo['town_code'],
                'business_circle_code' => $vo['business_code'],
            ))->getField('business_circle');
            $find_str = array(
                ',',
                "\r\n",
                "\n",
                "\r", );
            $replace = '，';
            $vo['store_desc'] = str_replace($find_str, $replace,
                trim($vo['store_desc']));
            $line = sprintf('%06s', $vo['id']).','.
                "{$vo['add_time']},".
                "{$vo['store_name']},".
                "{$vo['principal_name']},".
                "{$vo['principal_phone']},".
                "{$vo['principal_email']},".
                "{$vo['store_short_name']},".
                "{$vo['province_name']},".
                "{$vo['city_name']},".
                "{$vo['town_name']},".
                "{$vo['address']},".
                "{$vo['store_phone']},".
                "{$vo['business_circle_name']},".
                "{$vo['store_desc']},".
                "{$vo['technician_count']},".
                "{$vo['customer_count']},".
                "{$vo['status_text']}\r\n";
            echo iconv('utf-8', 'gbk', $line);
        }
        exit;
    }
    public function add()
    {
        //$this->assign('m_id', $m_id);
        $this->display('Merchant/add');
    }
    public function edit($id)
    {
        $info = D('GpMerchant')->getMerchantById($id);
        $this->assign('info', $info);
        $this->display('Merchant/edit');
    }
    public function add_save()
    {
        // 必须传值机构号
        $node_id = I('node_id', $this->nodeId);
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => array(
                    'exp',
                    'in ('.$this->nodeIn().") and node_id = '".$node_id.
                    "'", ), ))->find();
        if (!$nodeInfo) {
            $this->error('您没有操作该商户的权限。');
        }

        // 判断是否允许添加该机构
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');

            // 门店简称
            if (mb_strlen($getPost['store_short_name'], 'utf8') > 10) {
                $this->error('门店简称不得大于10个字');
            }

            // 门店详情地址
            if (empty($getPost['address'])) {
                $this->error('门店地址不能为空');
            }

            // 门店联系电话
            if ($getPost['store_phone'] != '') {
                if (!is_numeric(str_replace('-', '', $getPost['store_phone']))) {
                    $this->error('门店电话不是纯数字或者-,有非法字符');
                }
            }

            // 姓名
            if (empty($getPost['principal_name'])) {
                $this->error('请输入姓名');
            }

            // 手机
            if (empty($getPost['principal_phone'])) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/",
                    $getPost['principal_phone']) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }

            // 邮箱
            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                $getPost['principal_email'])) {
                $this->error('邮箱格式不对');
            }
            $storeimg = json_encode($getPost['store_pic'], true);
            // 开始记录到门店表
            $data = array(
                'store_id' => $store_id,
                'node_id' => $node_id,
                'store_name' => $getPost['store_name'],
                'store_short_name' => $getPost['store_short_name'],
                'store_desc' => get_val($getPost, 'store_desc'),
                'province_code' => $getPost['province_code'],
                'city_code' => $getPost['city_code'],
                'town_code' => $getPost['town_code'],
                'address' => $getPost['address'],
                'post_code' => get_val($getPost, 'post_code'),
                'principal_name' => $getPost['principal_name'],
                'principal_position' => get_val($getPost, 'principal_position'),
                'principal_phone' => $getPost['principal_phone'],
                'principal_email' => $getPost['principal_email'],
                'status' => 1,
                'add_time' => date('YmdHis'),
                'store_phone' => $getPost['store_phone'],
                'store_email' => get_val($getPost, 'store_email'),
                'store_pic' => $storeimg,
                'business_code' => $getPost['business_code'],
            );
            $result = M('tfb_gp_merchant')->add($data);
            if (!$result) {
                Log::write(print_r($data, true).M()->getDbError(), 'DB ERROR');
                $this->error('创建门店失败');
            }
            node_log("【门店管理】门店添加，门店号：{$result}"); // 记录日志
            $this->success('门店添加成功', array('返回列表页' => U('index')));
            exit();
        }
    }
    public function edit_save()
    {
        $id = I('id');
        $info = M('tfb_gp_merchant')->where('node_id in ('.$this->nodeIn().") and id='$id'")->find();
        // 必须传值机构号
        $node_id = $info['node_id'];
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => array(
                    'exp',
                    'in ('.$this->nodeIn().") and node_id = '".$node_id.
                    "'", ), ))->find();
        if (!$nodeInfo) {
            $this->error('您没有操作该商户的权限。');
        }
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');
            // 门店简称
            if (mb_strlen($getPost['store_short_name'], 'utf8') > 10) {
                $this->error('门店简称不得大于10个字');
            }

            // 门店详情地址
            if (empty($getPost['address'])) {
                $this->error('门店地址不能为空');
            }

            // 门店联系电话
            if ($getPost['store_phone'] != '') {
                if (!is_numeric(str_replace('-', '', $getPost['store_phone']))) {
                    $this->error('门店电话不是纯数字或者-,有非法字符');
                }
            }

            // 姓名
            if (empty($getPost['principal_name'])) {
                $this->error('请输入姓名');
            }

            // 手机
            if (empty($getPost['principal_phone'])) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/", I('principal_phone')) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }

            // 邮箱
            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                $getPost['principal_email'])) {
                $this->error('邮箱格式不对');
            }
            $storeimg = json_encode($getPost['store_pic'], true);

            // 开始记录到门店表
            $data = array(
                'store_name' => $getPost['store_name'],
                'store_short_name' => $getPost['store_short_name'],
                'store_desc' => $getPost['store_desc'],
                'province_code' => $getPost['province_code'],
                'city_code' => $getPost['city_code'],
                'town_code' => $getPost['town_code'],
                'address' => $getPost['address'],
                'post_code' => $getPost['post_code'],
                'principal_name' => $getPost['principal_name'],
                'principal_position' => $getPost['principal_position'],
                'principal_phone' => $getPost['principal_phone'],
                'principal_email' => $getPost['principal_email'],
                'store_phone' => $getPost['store_phone'],
                'store_email' => $getPost['store_email'],
                'store_pic' => $storeimg,
                'business_code' => $getPost['business_code'],
            );

            $result = M('tfb_gp_merchant')->where("id='".$info['id']."'")->save(
                $data);

            if ($result === false) {
                Log::write(print_r($data, true).M()->getDbError(), 'DB ERROR');
                $this->error('修改门店失败');
            }
            node_log("【门店管理】门店修改，ID:{$info['id']}"); // 记录日志
            $this->success('门店修改成功', array('href' => 'javascript:parent.location.reload();'));
            exit();
        }
    }
    public function change_status()
    {
        if (I('id')) {
            $res = M('tfb_gp_merchant')->where(array('id' => I('id')))->data(array('status' => (int) I('status')))->save();
            if ($res !== false) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }
        }
    }
    private function make_qrcode($id, $filename)
    {
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $color = '000000';
        $ap_arr = array();
        $saveandprint = false;
        $size = 8;
        $filename = $this->filePath ? $this->filePath.$filename : $filename;
        $url = U('GpEye/EyeWap/storeInfoWap', array('id' => $id), true, false, str_replace('/', '', str_replace('http://', '', C('CURRENT_DOMAIN'))));
        $code = QRcode::png($url, $filename, '0', $size, 2, $saveandprint, '', $color, $ap_arr);
        chmod($this->filePath, 0777);
        chmod($filename, 0777);
    }
    public function viewcode()
    {
        $info = M('tfb_gp_merchant')->where(array('id' => I('id')))->find();
        $fileName = trim($info['id']).'.png';
        if (!file_exists($this->filePath.$fileName)) {
            $this->make_qrcode($info['id'], $fileName);
        }
        $image_url = C('CURRENT_DOMAIN').$this->filePath.$fileName;
        $this->assign('image_url', $image_url);
        $url = U('GpEye/EyeWap/storeInfoWap', array('id' => I('id')), true, false, str_replace('/', '', str_replace('http://', '', C('CURRENT_DOMAIN'))));
        $this->assign('url', $url);
        $this->assign('id', I('id'));
        $this->display('Merchant/viewcode');
    }
    public function downcode($id)
    {
        $fileName = trim($id).'.png';
        $file = $this->filePath.$fileName;
        header('Content-type: octet/stream');
        header('Content-disposition: attachment; filename='.$file.';');
        header('Content-Length: '.filesize($file));
        readfile($file);
        exit;
    }
    public function user()
    {
        $map = array('node_id' => $this->nodeId);
        $data = I('get.');
        if (!empty($data['merchant_id'])) {
            $map['merchant_id'] = (int) $data['merchant_id'];
        }
        if (!isset($data['status'])) {
            $data['status'] = -1;
        }
        if ($data['status'] != '-1') {
            $map['status'] = $data['status'];
        }
        if (!empty($data['user_name'])) {
            $map['_string'] = " ((user_name like '%".$data['user_name']."%') or (true_name like '%".$data['user_name']."%')) ";
        }

        $mapcount = D('GpMerchant')->getUserListCount($map);
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = D('GpMerchant')->getUserList($map, 'id desc', $Page->firstRow.','.$Page->listRows);
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('role_list', C('GpEye.roles'));
        $map = array('status' => 0);
        $merchant_list = D('GpMerchant')->getOptionList($map);
        $this->assign('merchant_list', $merchant_list);
        $this->display('Merchant/user');
    }
    public function add_user()
    {
        $map = array('status' => 0);
        $list = D('GpMerchant')->getOptionList($map);
        $technician_list = D('GpTechnician')->getOptionList($map);
        $this->assign('list', $list);
        $this->assign('technician_list', $technician_list);
        $this->assign('role_list', C('GpEye.roles'));
        $this->display('Merchant/add_user');
    }
    public function add_user_save()
    {
        if ($this->isPost()) {
            /* 信息校验 */
            if (M('tfb_gp_technician')->where(array('id' => I('technician_id')))->getField('user_id') > 0) {
                $this->error('该技师已经下挂了用户。');
            }
            $user_name = I('post.user_name', null, 'mysql_escape_string');
            $true_name = I('name', null, 'mysql_escape_string');
            $merchant_id = I('merchant_id');
            if (!check_str($user_name, array(
                'null' => false,
                'strtype' => 'email', ), $error)) {
                $this->error("用户名称{$error}");
            }
            $count = M('tuser_info')->where("user_name='{$user_name}'")->count();
            if ($count > 0) {
                $this->error('该用户名邮箱已被占用');
            }
            $pws1 = I('post.user_password1');
            if (!check_str($pws1, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.user_password2');
            if (!check_str($pws2, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2) {
                $this->error('两次密码不一致');
            }
            $roleId = I('post.user_role');
            /* 创建sso账号信息 */

            // 新增用户sso接口请求
            $reqArray = array(
                'UserAdd' => array(
                    'AppId' => C('SSO_SYSID'),
                    'NodeId' => $this->nodeId,
                    'Name' => $user_name,
                    'UserName' => $true_name,
                    'Password' => $pws1,
                    'Email' => $user_name,
                    'Telephone' => '',
                    'Notes' => date('Y-m-d H:i:s').
                    ' Interface registration with lower user',
                    'Position' => '',
                    'Address' => '',
                    'PosCode' => '',
                    'CustomNo' => '', ), );
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestSsoUserCreate($reqArray);
            if ($reqResult['UserAdd']['Status']['StatusCode'] != '0000') {
                $this->error('商户新增失败：'.$reqResult['UserAdd']['Status']['StatusText']);
            }
            $data = array(
                'node_id' => $this->nodeId,
                'user_id' => $reqResult['UserAdd']['Info']['UserId'],
                'new_role_id' => $roleId,
                'user_name' => $user_name,
                'true_name' => $true_name,
                'add_time' => date('YmdHis'),
                'merchant_id' => $merchant_id,
                'mail_addr' => $user_name,
            );
            $result = M('tuser_info')->add($data);
            if (!$result) {
                $this->error('系统出错，添加失败');
            } else {
                $data = array('user_id' => $reqResult['UserAdd']['Info']['UserId']);
                M('tfb_gp_technician')->where(array('id' => I('technician_id')))->data($data)->save();
            }

            $this->success('用户添加成功');
        }
    }
    public function edit_user($id)
    {
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND id='{$id}'")->find();
        if (!$userInfo) {
            $this->error('未找到该用户信息'.M()->_sql());
        }
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        $map = array('status' => 0);
        $list = D('GpMerchant')->getOptionList($map);
        $technician_list = D('GpTechnician')->getOptionList($map);
        $this->assign('list', $list);
        $this->assign('technician_list', $technician_list);
        $this->assign('role_list', C('GpEye.roles'));
        $this->assign('info', $userInfo);
        $this->display('Merchant/edit_user');
    }
    public function edit_user_save()
    {
        if ($this->isPost()) {
            $id = I('id');
            $userInfo = M('tuser_info')->where(
                "node_id='{$this->nodeId}' AND id='{$id}'")->find();
            if (!$userInfo) {
                $this->error('未找到该用户信息'.M()->_sql());
            }
            // 更新密码
            $pws1 = I('post.user_password1');
            if (!check_str($pws1, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.user_password2');
            if (!check_str($pws2, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2) {
                $this->error('两次密码不一致');
            }
            $password = I('post.user_password1');
            if ($password != '') {
                // 更新密码
                $result = $this->_modifySsoPwd(array('user_name' => $userInfo['user_name'], 'password' => md5($password)));
                $result = $result['Status'];
                if ($result['StatusCode'] != '0000') {
                    $this->error($result['StatusText']);
                }
            }
            $true_name = I('name', null, 'mysql_escape_string');
            $data = array(
                'true_name' => $true_name,
            );

            // 数据更新
            $where = array('id' => $id);
            $result = M('tuser_info')->where($where)->save($data);
            if ($result === false) {
                $this->error('系统出错，更新失败');
            }
            $this->success('用户更新成功');
            exit();
        }
    }
    // 修改密码
    protected function _modifySsoPwd($arr)
    {
        // 请求用户修改接口
        // 请求SSO用户密码修改
        $reqData = array(
            'ResetChildPassword' => array(
                'AppId' => C('SSO_SYSID'),
                'Token' => $this->userInfo['token'],
                'UserName' => $arr['user_name'],
                'Password' => $arr['password'], ), );
        $requestServ = D('RemoteRequest', 'Service');
        $resp_arr = $requestServ->requestSsoInterface('ResetChildPassword',
            $reqData);
        $statusArr = $resp_arr['Status'];
        if ($statusArr['StatusCode'] != '0000') {
            $resp_desc = $statusArr['StatusText'];
            log_write($resp_desc);
        }

        return $resp_arr;
    }
    public function change_user_status()
    {
        $id = I('id');
        $userinfo = M('tuser_info')->where('id='.$id)->find();
        $status = I('status', null);

        /*
         * if (!$this->checkUserLevel()) $this->error('您不是管理员没有该操作权限');
         */
        $userId = $userinfo['user_id'];
        if (!check_str($userId, array(
            'null' => false, ), $error)) {
            $this->error('参数错误');
        }
        $userInfo = M('tuser_info')->where("node_id='{$this->nodeId}' AND user_id='{$userId}'")->find();
        if (!$userInfo) {
            $this->error('未找到该用户信息');
        }
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }

        // 接口请求
        $req_array = array(
            'UserStatusReq' => array(
                'UserId' => $userId,
                'Status' => $status, ), );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestSsoUserStatus($req_array);
        $ret_msg = $reqResult['UserStatusRes']['Status'];
        if (!$reqResult || ($ret_msg['StatusCode'] != '0000' &&
            $ret_msg['StatusCode'] != '0001')) {
            $this->error("更新失败:{$ret_msg['StatusText']}");
        }
        $result = M('tuser_info')->where("user_id='{$userId}'")->save(array('status' => $status));
        if ($result) {
            $this->ajaxReturn(1, '操作成功！', 1);
        } else {
            $this->ajaxReturn(0, '操作失败6！', 0);
        }
    }
    public function getuser($merchant_id)
    {
        $map = array('status' => 0, 'merchant_id' => $merchant_id);
        $technician_list = D('GpTechnician')->getOptionList($map);
        $technician = array();
        foreach ($technician_list as $key => $v) {
            $technician[] = array('id' => $key, 'name' => $v);
        }
        echo json_encode($technician, true);
    }
}
