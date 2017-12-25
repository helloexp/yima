<?php

/*
 * 陕西平安产险
 */
class InsuranceEmployeeAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public function _initialize() {
        parent::_initialize();
        
        C('TMPL_ACTION_ERROR', './Home/Tpl/Label/Public_error.html');
        C('TMPL_ACTION_SUCCESS', './Home/Tpl/Label/Public_msg.html');
    }

    public function index() {
        $emp_number = I("emp_number", "");
        $emp_name = I("emp_name", "");
        $groupId = I("group_id", "");
        
        $groupList = M()->table('tfb_onlinesee_group g')
            ->field(
            "g.*,(SELECT COUNT(*) FROM tfb_onlinesee_crew c, tfb_onlinesee_member m where c.staff_number=m.staff_number and c.group_id=g.id and c.crew_sta <> 0) as num")
            ->order('g.add_time')
            ->select();
        
        // 员工数量
        $empNum = M("tfb_onlinesee_member")->where("status <> 0")->count();
        
        $map = array();
        if (! empty($emp_number)) {
            $map['staff_number'] = $emp_number;
        }
        
        if (! empty($emp_name)) {
            $map['name'] = $emp_name;
        }
        
        $map['status'] = array(
            'neq', 
            "0");
        
        $map['_string'] = '1=1';
        if ($groupId != '') {
            /*
             * if($groupId == 1){ $map['_string'] = " not exists(select 1 from
             * tfb_onlinesee_crew c where c.staff_number = m.staff_number and
             * c.crew_sta <> 0)"; }else{ $map['_string'] = " exists(select 1
             * from tfb_onlinesee_crew c where c.staff_number = m.staff_number
             * and c.group_id = {$groupId} and c.crew_sta <> 0)"; }
             */
            $map['_string'] = " exists(select 1 from tfb_onlinesee_crew c where c.staff_number = m.staff_number and c.group_id = {$groupId} and c.crew_sta <> 0)";
        }
        $count = M("tfb_onlinesee_member")->alias("m")
            ->where($map)
            ->field(
            'm.*, (SELECT GROUP_CONCAT(g.group_name) 
			  			FROM tfb_onlinesee_group g, tfb_onlinesee_crew c 
			  			WHERE g.id = c.group_id AND c.staff_number = m.staff_number and c.crew_sta <> 0) AS group_name')
            ->count();
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        
        $list = M("tfb_onlinesee_member")->alias("m")
            ->where($map)
            ->field(
            'm.*, (SELECT GROUP_CONCAT(g.group_name) 
			  			FROM tfb_onlinesee_group g, tfb_onlinesee_crew c 
			  			WHERE g.id = c.group_id AND c.staff_number = m.staff_number and c.crew_sta <> 0) AS group_name')
            ->order("m.add_time desc")
            ->limit($p->firstRow, $p->listRows)
            ->select();
        
        $this->assign("groupList", $groupList);
        $this->assign("queryList", $list);
        $this->assign('empNum', $empNum);
        $this->assign('emp_number', $emp_number);
        $this->assign('emp_name', $emp_name);
        $this->assign('group_id', $groupId);
        $this->assign("page", $page);
        $this->display();
    }

    public function trash() {
        $emp_number = I("emp_number", "");
        $emp_name = I("emp_name", "");
        $groupId = I("group_id", "");
        
        $groupList = M()->table('tfb_onlinesee_group g')
            ->field(
            "g.*,(SELECT COUNT(*) FROM tfb_onlinesee_crew c, tfb_onlinesee_member m where c.staff_number=m.staff_number and c.group_id=g.id and c.crew_sta <> 0) as num")
            ->order('g.add_time')
            ->select();
        
        // 员工数量
        $empNum = M("tfb_onlinesee_member")->where("status <> 0")->count();
        
        $map = array();
        if (! empty($emp_number)) {
            $map['staff_number'] = $emp_number;
        }
        
        if (! empty($emp_name)) {
            $map['name'] = $emp_name;
        }
        
        $map['status'] = array(
            'eq', 
            "0");
        
        $map['_string'] = '1=1';
        if ($groupId != '') {
            /*
             * if($groupId == 1){ $map['_string'] = " not exists(select 1 from
             * tfb_onlinesee_crew c where c.staff_number = m.staff_number and
             * c.crew_sta <> 0)"; }else{ $map['_string'] = " exists(select 1
             * from tfb_onlinesee_crew c where c.staff_number = m.staff_number
             * and c.group_id = {$groupId} and c.crew_sta <> 0)"; }
             */
            $map['_string'] = " exists(select 1 from tfb_onlinesee_crew c where c.staff_number = m.staff_number and c.group_id = {$groupId} and c.crew_sta <> 0)";
        }
        $count = M("tfb_onlinesee_member")->alias("m")
            ->where($map)
            ->field(
            'm.*, (SELECT GROUP_CONCAT(g.group_name) 
			  			FROM tfb_onlinesee_group g, tfb_onlinesee_crew c 
			  			WHERE g.id = c.group_id AND c.staff_number = m.staff_number and c.crew_sta <> 0) AS group_name')
            ->count();
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        
        $list = M("tfb_onlinesee_member")->alias("m")
            ->where($map)
            ->field(
            'm.*, (SELECT GROUP_CONCAT(g.group_name) 
			  			FROM tfb_onlinesee_group g, tfb_onlinesee_crew c 
			  			WHERE g.id = c.group_id AND c.staff_number = m.staff_number and c.crew_sta <> 0) AS group_name')
            ->order("m.add_time desc")
            ->limit($p->firstRow, $p->listRows)
            ->select();
        
        $this->assign("groupList", $groupList);
        $this->assign("queryList", $list);
        $this->assign('empNum', $empNum);
        $this->assign('emp_number', $emp_number);
        $this->assign('emp_name', $emp_name);
        $this->assign('group_id', $groupId);
        $this->assign("page", $page);
        $this->display();
    }

    /*
     * 添加新员工信息
     */
    public function add() {
        $id = I("id", "");
        $type = I("type", "");
        
        if ($type == 2) {
            $list = M("tfb_onlinesee_member")->where("id={$id}")->select();
            
            $this->assign('type', $type);
            $this->assign('mid', $id);
            $this->assign("info", $list[0]);
        } else {
            $groupInfo = M('tfb_onlinesee_group')->select();
            $this->assign('groupList', $groupInfo);
        }
        
        $this->display();
    }
    
    // 添加单个员工
    public function insert() {
        $error = '';
        $id = I("mid", "");
        $type = I("addType", "");
        
        $group_id = I('group_id', null, 'intval');
        $name = I('name', "");
        $snum = I('staff_number', "");
        $img_src = I("store_pic", "");
        $server_dec = I("server_dec", "");
        
        $model = M('tfb_onlinesee_member');
        
        if (empty($img_src)) {
            $this->error('请添加员工照片');
        }
        if (! check_str($name, 
            array(
                'null' => true, 
                'maxlen_cn' => '7'), $error)) {
            $this->error('员工姓名' . $error);
        }
        if (! empty($group_id) && $group_id !== '0') {
            $groupInfo = M('tfb_onlinesee_group')->where("id={$group_id}")->find();
            if (! $groupInfo)
                $this->error('未找到您选择的分组');
        }
        
        $groupId = empty($group_id) ? 0 : $group_id;
        if ($type != 2) {
            if (! empty($snum)) {
                $staff_num = $model->where("staff_number='{$snum}'")->count();
                if ($staff_num > 0)
                    $this->error('您录入的员工编号已存在！');
            } else {
                $this->error('员工编号不能为空!');
            }
            
            $data = array(
                'group_id' => $groupId, 
                'name' => $name, 
                'staff_number' => $snum, 
                'staff_service_dec' => $server_dec, 
                'image_link' => $img_src, 
                'add_time' => date('Ymdhis'));
            $res = $model->add($data);
            if (! $res) {
                $this->error("系统错误,添加失败");
            }
            
            /*
             * $mid = $model->where(array('name' => $name, 'staff_number' =>
             * $snum))->field("id")->find(); //拼接短链 $link =
             * U("OnlineSee/InsuranceWap/index", array('id' => $mid['id'],
             * 'type' => 1), "", "", true); $link =
             * create_sina_short_url($link); $shortRes =
             * $model->where("id={$mid['id']}")->save(array('short_link' =>
             * $link)); if (!$shortRes) { $this->error("系统错误,短链生成失败"); }
             */
            
            // 向组员表中插入/更新数据
            if ($groupId != 0) {
                M("tfb_onlinesee_crew")->add(
                    array(
                        'group_id' => $groupId, 
                        'staff_number' => $snum, 
                        'add_time' => date('Ymdhis')));
                
                /*
                 * $cid = M("tfb_onlinesee_crew")->where(array('group_id' =>
                 * $groupId, 'staff_number' => $snum))->field("id")->find();
                 * //拼接短链 $link = U("OnlineSee/InsuranceWap/index", array('id'
                 * => $cid['id'], 'type' => 0), "", "", true); $link =
                 * create_sina_short_url($link); $shortRes =
                 * M("tfb_onlinesee_crew")->where("id={$cid['id']}")->save(array('short_link'
                 * => $link)); if (!$shortRes) { $this->error("系统错误,短链生成失败"); }
                 */
            }
            
            node_log("员工管理:员工添加，姓名：" . $name);
            $this->success("员工添加成功！");
        } else {
            $data = array(
                'name' => $name, 
                'staff_service_dec' => $server_dec, 
                'image_link' => $img_src);
            $result = M("tfb_onlinesee_member")->where("id={$id}")->save($data);
            if ($result === false)
                $this->error('系统出错,更新失败');
            node_log("员工管理:姓名：" . $name);
            $this->success("员工信息更新成功！");
        }
    }

    /*
     * 批量导入员工信息
     */
    public function importAdd() {
        $groupInfo = M('tfb_onlinesee_group')->select();
        $this->assign('groupList', $groupInfo);
        $this->display();
    }

    /*
     * 上传文件，批量添加用户
     */
    public function importInsert() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024;
        $upload->allowExts = array(
            "csv");
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
        $memberModel = M('tfb_onlinesee_member');
        // 获取所有员工信息
        $importMember = array(); // 符合条件的员工信息
        
        $row = 0; // 导入文件的总条数包括表头
        $error = '';
        $erroName = C('DOWN_TEMP') . date('YmdHis') . '.csv'; // 上传失败的用户数据
        
        $erroFileHandle = fopen($erroName, "wb");
        fwrite($erroFileHandle, chr(0XEF) . chr(0xBB) . chr(0XBF)); // 输出BOM头防止微软软件打开文件乱码,该方法对未升级的office2007无效
        if (! $erroFileHandle) {
            log::write('批量添加员工：错误员工信息文件打开失败');
            unlink($fileName);
            $this->error('系统出错');
        }
        $fileField = array(
            '编号', 
            '姓名', 
            '服务宣言');
        $fileField[] = '错误原因';
        fputcsv($erroFileHandle, $fileField);
        
        // 读取员工头像存放的指定文件夹
        $numDir = scandir(APP_PATH . '/Upload/OnlineSee');
        
        if (($handle = fopen($fileUrl, "rw")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { // 读取csv文件
                ++ $row;
                $data = utf8Array($data);
                if ($row == 1) {
                    $fileField = array(
                        '编号', 
                        '姓名', 
                        '服务宣言');
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
                $empNumber = $data[0];
                $name = $data[1];
                $decStr = $data[2];
                $erroInfo = '';
                if (! check_str($empNumber, 
                    array(
                        'null' => false), $error)) {
                    $erroInfo .= "员工编号{$error}";
                }
                if (! check_str($name, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '7'), $error)) {
                    $erroInfo .= "员工姓名{$error}";
                }
                if (! check_str($decStr, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '200'), $error)) {
                    $erroInfo .= "服务宣言{$error}";
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
                
                // 检查员工的头像是否在指定文件夹下
                $imageDir = "";
                if (in_array($empNumber . ".jpg", $numDir)) {
                    $imageDir = "OnlineSee/" . $empNumber . ".jpg";
                }
                
                // 符合条件的员工信息
                $importMember[] = array(
                    'staff_number' => $empNumber, 
                    'name' => $name, 
                    'staff_service_dec' => $decStr, 
                    'image_link' => $imageDir, 
                    'add_time' => date('YmdHis'));
            }
            fclose($handle);
            fclose($erroFileHandle);
            if ($row < 2) {
                unlink($erroName);
                unlink($fileUrl);
                $this->error('您上传的文件中没有找到员工数据!');
            }
            $totalNum = $row - 1; // 一共上传的员工数量
            $succNum = count($importMember); // 符合条件的员工
            $failNum = $totalNum - $succNum; // 失败的员工
                                             // 员工表数据插入
            foreach ($importMember as $k => $v) {
                // 判断编号是否存在
                $unsn = $memberModel->field("id")
                    ->where(
                    array(
                        'staff_number' => $v['staff_number']))
                    ->find();
                if ($unsn["id"] != "") {
                    $memberModel->where("id={$unsn['id']}")->save($v);
                } else {
                    $result = $memberModel->add($v);
                    if (! $result) {
                        $memberModel->rollback();
                        log::write('批量添加员工：tfb_onlinesee_member表数据插入失败');
                        unlink($erroName);
                        unlink($fileUrl);
                        $this->error('系统出错,添加失败');
                    }
                }
            }
            $memberModel->commit();
            
            $data = array(
                'fail_num' => $failNum, 
                'succNum' => $succNum, 
                'error_name' => basename($erroName));
            node_log("员工管理:批量员工添加，添加数量：" . $succNum . "个");
            $this->ajaxReturn($data, 
                "总计导入{$totalNum}个粉丝,成功{$succNum}个,失败{$failNum}个{$sendInfo}", 1);
        }
        log::write('批量添加员工：上传文件打开失败');
        fclose($erroFileHandle);
        fclose($handle);
        unlink($erroName);
        unlink($fileUrl);
        $this->error('系统出错');
    }

    /*
     * 查看员工信息
     */
    public function see() {
        $id = I("id");
        
        $sql = "select m.*, (SELECT GROUP_CONCAT(g.group_name) 
			  			FROM tfb_onlinesee_group g, tfb_onlinesee_crew c 
			  			WHERE g.id = c.group_id AND c.staff_number = m.staff_number) AS group_name from tfb_onlinesee_member m where m.id={$id}";
        $list = M()->query($sql);
        
        // 如果头像路径为空则到自定义头像文件夹下查找
        if ($list[0]["image_link"] == "") {
            $mArr = M("tfb_onlinesee_member")->where("id={$id}")
                ->field("staff_number")
                ->find();
            $numDir = scandir(APP_PATH . '/Upload/OnlineSee');
            $imageDir = "";
            if (in_array($mArr["staff_number"] . ".jpg", $numDir)) {
                $imageDir = "OnlineSee/" . $empNumber . ".jpg";
            }
            
            $list[0]["image_link"] == $imageDir;
        }
        
        $this->assign('info', $list[0]);
        $this->display();
    }

    /*
     * 员工信息删除
     */
    public function empDel() {
        $id = I("id");
        
        $result = M("tfb_onlinesee_member")->where("id={$id}")->save(
            array(
                'status' => 0));
        if ($result === false)
            $this->error('系统出错,删除失败.');
        
        $mArr = M("tfb_onlinesee_member")->where("id={$id}")->find();
        $res = M("tfb_onlinesee_crew")->where(
            "staff_number='{$mArr['staff_number']}'")->save(
            array(
                'crew_sta' => 0));
        if ($res === false)
            $this->error('系统出错,删除失败!');
        
        $this->success('删除成功');
    }

    public function emplife() {
        $id = I("id");
        
        $result = M("tfb_onlinesee_member")->where("id={$id}")->save(
            array(
                'status' => 1));
        if ($result === false)
            $this->error('系统出错,复活失败.');
        
        $mArr = M("tfb_onlinesee_member")->where("id={$id}")->find();
        $res = M("tfb_onlinesee_crew")->where(
            "staff_number='{$mArr['staff_number']}'")->save(
            array(
                'crew_sta' => 1));
        if ($res === false)
            $this->error('系统出错,复活失败!');
        
        $this->success('复活成功');
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
        
        $groupModel = M('tfb_onlinesee_group');
        if (empty($groupId)) { // 添加
                               // 名称重复
            $count = $groupModel->where("group_name='{$groupName}'")->count();
            if ($count > 0)
                $this->error('分组名称重复');
            
            $data = array(
                'group_name' => $groupName, 
                'add_time' => date('YmdHis'));
            $result = $groupModel->add($data);
            if ($result) {
                node_log("员工管理:分组添加，分组名：" . $groupName);
                $this->success('创建成功');
            } else {
                $this->error('系统出错,创建失败');
            }
        } else { // 编辑
            $groupInfo = $groupModel->where("id='{$groupId}' AND id<>0")->find();
            if (! $groupInfo)
                $this->error('未找到该分组信息');
            
            $count = $groupModel->where(
                "group_name='{$groupName}' AND id<>{$groupInfo['id']}")->count();
            if ($count > 0)
                $this->error('分组名称重复');
            
            $data = array(
                'group_name' => $groupName);
            $result = $groupModel->where("id='{$groupId}'")->save($data);
            if ($result === false)
                $this->error('系统出错,修改失败');
            node_log("员工管理:分组修改，分组名：" . $groupName);
            $this->success('修改成功');
        }
    }
    
    // 分组删除
    // public function delGroup(){
    // $groupId = I('group_id',null,'mysql_real_escape_string');
    // $groupModel = M('tfb_onlinesee_group');
    // $groupInfo = $groupModel->where("id='{$groupId}' AND id<>0")->find();
    // if(!$groupInfo) $this->error('未找到该分组信息');
    // //分组下有会员无法删除
    // $count = M("tfb_onlinesee_crew")->where("group_id={$groupId} AND
    // crew_sta<>0")->count();
    // if($count > 0) $this->error('该分组下有员工信息,无法删除');
    // $result = $groupModel->where("id='{$groupId}'")->delete();
    // if($result === false) $this->error('系统出错,删除失败');
    // node_log("员工管理:分组删除，分组id：".$groupId);
    // $this->success('删除成功');
    // }
    
    // ajax获取分组
    public function ajaxGroup() {
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        $groupInfo = M('tfb_onlinesee_group')->where(
            "id='{$groupId}' AND id<>0")->find();
        if (! $groupInfo)
            $this->error('未找到该分组信息');
        $this->ajaxReturn($groupInfo, '', 1);
    }
    
    // 给分组添加员工
    public function memberGroupAdd() {
        $groupId = I('group_id', null, 'mysql_real_escape_string');
        $memberStr = I('member_str', null);
        if ($groupId !== '0') {
            $groupInfo = M('tfb_onlinesee_group')->where("id='{$groupId}'")->find();
            if (! $groupInfo)
                $this->error('未找到该分组信息');
        }
        $memberArr = explode(',', $memberStr);
        array_pop($memberArr);
        if (! is_array($memberArr) || empty($memberArr))
            $this->error('数据信息有误');
        $model = M("tfb_onlinesee_crew");
        foreach ($memberArr as $v) {
            $sgdelcount = $model->where(
                "staff_number='{$v}' and group_id={$groupId} and crew_sta=0")->count();
            $sgCount = $model->where(
                "staff_number='{$v}' and group_id={$groupId} and crew_sta=1")->count();
            $result = true;
            if ($sgdelcount > 0) {
                $result = $model->where(
                    "staff_number='{$v}' and group_id={$groupId}")->save(
                    array(
                        'crew_sta' => 1));
            } elseif ($sgCount > 0) {
                $result = true;
            } else {
                $result = $model->add(
                    array(
                        'group_id' => $groupId, 
                        'staff_number' => $v, 
                        'add_time' => date("Ymdhis")));
                
                /*
                 * $cid = $model->where(array('group_id' => $groupId,
                 * 'staff_number' => $v))->field("id")->find(); //拼接短链 $link =
                 * U("OnlineSee/InsuranceWap/index", array('id' => $cid['id'],
                 * 'type' => 0), "", "", true); $link =
                 * create_sina_short_url($link); $shortRes =
                 * $model->where("id={$cid['id']}")->save(array('short_link' =>
                 * $link)); if (!$shortRes) { $this->error("系统错误,短链生成失败"); }
                 */
            }
            if (! $result) {
                $this->error('系统出错,添加分组失败');
            }
        }
        node_log("员工管理:添加分组员工，分组id：" . $groupId . "员工:" . $memberStr);
        $this->success('添加成功');
    }
    
    // 组员管理
    public function crew() {
        $groupId = I("group_id", "");
        $name = I("emp_name", "");
        $sNum = I("emp_number", "");
        $subType = I("sub_type", 1);
        
        $wh = " where m.status <> 0 ";
        $where = "where c.crew_sta <> 0";
        if (! empty($name)) {
            $wh .= " and m.name = '{$name}' ";
            $where .= " and c.staff_number in(select staff_number from tfb_onlinesee_member where name='{$name}')";
        }
        
        if (! empty($sNum)) {
            $wh .= " and m.staff_number = '{$sNum}' ";
            $where .= " and c.staff_number='{$sNum}'";
        }
        if ($subType == 2) {
            $sql = "";
            
            if (empty($groupId)) {
                $sql = "select * from tfb_onlinesee_member m $wh order by m.add_time";
            } else {
                $wh .= " and c.group_id = {$groupId} and c.crew_sta <> 0";
                $sql = "select c.*, m.name, g.group_name from tfb_onlinesee_crew c 
				left join tfb_onlinesee_member m on c.staff_number=m.staff_number 
				left join tfb_onlinesee_group g on c.group_id = g.id $wh order by c.add_time DESC";
            }
            $list = M()->query($sql);
            
            // 下载pv、uv、co数据到csv文件
            $fileName = "组员信息表.csv";
            $fileName = iconv('utf-8', 'gbk', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            $cj_title = "";
            if (! empty($groupId)) {
                $cj_title = "分组,员工编号,员工姓名,评价短链接\r\n";
            } else {
                $cj_title = "员工编号,员工姓名,评价短链接\r\n";
            }
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            foreach ($list as $v) {
                $line = "";
                if (! empty($groupId)) {
                    $line = "{$v['group_name']},{$v['staff_number']},{$v['name']},{$v['short_link']}\r\n";
                } else {
                    $line = "{$v['staff_number']},{$v['name']},{$v['short_link']}\r\n";
                }
                echo iconv('utf-8', 'gbk', $line);
            }
            exit();
        }
        
        $groupList = M("tfb_onlinesee_group")->select();
        
        // $where = "where c.crew_sta <> 0";
        if (! empty($groupId)) {
            $where .= " and c.group_id={$groupId}";
        }
        
        // if(!empty($name)) {
        // $where .= " and c.staff_number in(select staff_number from
        // tfb_onlinesee_member where name='{$name}')";
        // }
        // if(!empty($sNum)) {
        // $where .= " and c.staff_number='{$sNum}'";
        // }
        
        $crewList = array();
        $page = "";
        import("ORG.Util.Page");
        if (empty($groupId)) {
            $countSql = "select count(*) as count from tfb_onlinesee_member m {$wh}";
            
            $countInfo = M()->query($countSql);
            $p = new Page($countInfo['0']['count'], 10);
            $page = $p->show();
            
            $sql = "select m.* from tfb_onlinesee_member m {$wh} ORDER BY m.add_time DESC LIMIT {$p->firstRow}, {$p->listRows}";
            $crewList = M()->query($sql);
        } else {
            $countSql = "select COUNT(*) as count from tfb_onlinesee_crew c {$where} ";
            
            $countInfo = M()->query($countSql);
            $p = new Page($countInfo['0']['count'], 10);
            $page = $p->show();
            
            $sql = "select c.*, m.name, g.group_name from tfb_onlinesee_crew c 
			left join tfb_onlinesee_member m on c.staff_number=m.staff_number 
			left join tfb_onlinesee_group g on c.group_id = g.id {$where} ORDER BY c.add_time DESC LIMIT {$p->firstRow}, {$p->listRows}";
            $crewList = M()->query($sql);
        }
        
        $this->assign('crewList', $crewList);
        $this->assign('groupList', $groupList);
        $this->assign('emp_number', $sNum);
        $this->assign('emp_name', $name);
        $this->assign('group_id', $groupId);
        $this->assign('page', $page);
        $this->display();
    }

    public function groupCrewAdd() {
        $groupId = I("group_id", "");
        $numberStr = I("number_str", "");
        $numberArr = explode("\n", $numberStr);
        
        if (! is_array($numberArr) || empty($numberArr))
            $this->error('数据信息有误');
        $model = M("tfb_onlinesee_crew");
        $errNum = 0;
        foreach ($numberArr as $v) {
            if (M("tfb_onlinesee_member")->where("staff_number='{$v}' ")->count() ==
                 0) {
                $errNum ++;
                continue;
            }
            $sgCount = $model->where(
                "staff_number='{$v}' and group_id={$groupId}")->count();
            $result = true;
            if ($sgCount > 0) {
                $result = $model->where(
                    "staff_number='{$v}' and group_id={$groupId}")->save(
                    array(
                        'crew_sta' => 1));
            } else {
                $result = $model->add(
                    array(
                        'group_id' => $groupId, 
                        'staff_number' => $v, 
                        'add_time' => date("Ymdhis")));
                
                /*
                 * $cid = $model->where(array('group_id' => $groupId,
                 * 'staff_number' => $v))->field("id")->find(); //拼接短链 $link =
                 * U("OnlineSee/InsuranceWap/index", array('id' => $cid['id'],
                 * 'type' => 0), "", "", true); $link =
                 * create_sina_short_url($link); $shortRes =
                 * $model->where("id={$cid['id']}")->save(array('short_link' =>
                 * $link)); if (!$shortRes) { $this->error("系统错误,短链生成失败"); }
                 */
            }
            if ($result === false)
                $this->error('系统出错,删除失败');
        }
        
        $okNum = count($numberArr) - $errNum;
        node_log(
            "组员管理:批量分组，分组id：" . $groupId . "员工编号:" . $numberStr . "添加成功：" .
                 $okNum . ", 失败：" . $errNum . "个");
        
        $this->success('分组成功' . $okNum . '个！分组失败' . $errNum . '个.');
    }
    
    // 删除组员
    public function crewDel() {
        $id = I("id", "");
        
        $result = M("tfb_onlinesee_crew")->where("id={$id}")->save(
            array(
                'crew_sta' => 0));
        if ($result === false)
            $this->error('系统出错,删除失败');
        $this->success('删除成功');
    }

    public function bacth_short_url() {
        $group_id = I("group_id", "");
        $need_count = I("need_count");
        $url_useful_life = I("url_useful_life");
        $url_start_time = I("url_start_time");
        if (! is_numeric($need_count)) {
            $this->error('生成数量请填写数字！');
        }
        if ($need_count > 100) {
            $this->error('生成数量不能大于100！超过100请分多次生成！');
        }
        if (! is_numeric($url_useful_life)) {
            $this->error('有效时长请填写数字！');
        }
        $is_date = strtotime($url_start_time) ? strtotime($url_start_time) : false;
        
        if ($is_date === false) {
            $this->error('生效时间日期格式非法！');
        }
        if ($need_count > 0) {
            $crontab_type = 1;
            $add_data = array(
                'crontab_no' => date('YmdHis') . rand(10000, 99999), 
                'group_id' => implode(",", $group_id), 
                'need_count' => $need_count, 
                'status' => 0, 
                'add_time' => date('YmdHis'), 
                'crontab_type' => $crontab_type, 
                'link_type' => 2, 
                'url_start_time' => I("url_start_time"), 
                'url_useful_life' => I("url_useful_life"));
            $flag = M('tfb_onlinesee_link_crontab')->add($add_data);
            if ($flag === false) {
                log_write('添加任务失败！' . M()->_sql());
            } else {
                $this->success(
                    '申请已提交，任务序列号为' . $add_data['crontab_no'] .
                         '，请前往【短链任务管理】，待生成完成后方可进行下载。');
            }
        } else {
            $this->error('非法数据！');
        }
    }

    public function short_url() {
        $id = I("id", "");
        $need_count = I("need_count");
        $link_type = I("link_type");
        $url_useful_life = I("url_useful_life");
        $url_start_time = I("url_start_time");
        if (! is_numeric($need_count)) {
            $this->error('生成数量请填写数字！');
        }
        if ($need_count > 100) {
            $this->error('生成数量不能大于100！超过100请分多次生成！');
        }
        if (! is_numeric($url_useful_life)) {
            $this->error('有效时长请填写数字！');
        }
        $is_date = strtotime($url_start_time) ? strtotime($url_start_time) : false;
        
        if ($is_date === false) {
            $this->error('生效时间日期格式非法！');
        }
        if ($need_count > 0) {
            $crontab_type = 0;
            
            $add_data = array(
                'crontab_no' => date('YmdHis') . rand(10000, 99999), 
                'need_count' => $need_count, 
                'crew_id' => $id, 
                'status' => 0, 
                'add_time' => date('YmdHis'), 
                'crontab_type' => $crontab_type, 
                'link_type' => $link_type, 
                'url_start_time' => I("url_start_time"), 
                'url_useful_life' => I("url_useful_life"));
            $flag = M('tfb_onlinesee_link_crontab')->add($add_data);
            if ($flag === false) {
                log_write('添加任务失败！' . M()->_sql());
            } else {
                $this->success(
                    '申请已提交，任务序列号为' . $add_data['crontab_no'] .
                         '，请前往【短链任务管理】，待生成完成后方可进行下载。');
            }
        } else {
            $this->error('非法数据！');
        }
    }

    public function short_url_list() {
        $map = array();
        $count = M()->table('tfb_onlinesee_link_crontab')
            ->where($map)
            ->count();
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        $list = M()->table('tfb_onlinesee_link_crontab a')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $groupList = M()->table('tfb_onlinesee_group g')
            ->field(
            "g.*,(SELECT COUNT(*) FROM tfb_onlinesee_crew c, tfb_onlinesee_member m where c.staff_number=m.staff_number and c.group_id=g.id and c.crew_sta <> 0) as num")
            ->order('g.add_time')
            ->select();
        $this->assign('groupList', $groupList);
        $this->display();
    }

    public function short_url_down() {
        $id = I('id');
        if (! $id) {
            $this->error('参数错误');
            exit();
        }
        $map = array();
        $map['a.crontab_id'] = $id;
        $list = M()->table('tfb_onlinesee_link a')
            ->join("tfb_onlinesee_link_crontab b ON a.crontab_id=b.id")
            ->where($map)
            ->field('a.*,b.url_start_time,b.url_useful_life')
            ->select();
        $count = count($list);
        if ($count <= 0) {
            $this->error('无数据可下载');
        } else {
            $fileName = '短链接下载.csv';
            $fileName = iconv('utf-8', 'gb2312', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $start_num = 0;
            $page_count = 5000;
            $cj_title = "员工编号,员工姓名,所属分组,链接地址,开始时间,有效时长\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            foreach ($list as $v) {
                $new_url = $v['new_url'];
                $urlarr = parse_url($new_url);
                parse_str($urlarr['query'], $parr);
                $type = $parr['type'];
                $id = $parr['id'];
                if ($type == 0) {
                    $member = M()->table("tfb_onlinesee_crew c")->join(
                        "tfb_onlinesee_member m on c.staff_number=m.staff_number")
                        ->where("c.id={$id}")
                        ->field(
                        "c.group_id, c.staff_number, m.name, m.staff_service_dec, m.image_link")
                        ->find();
                    $group = M("tfb_onlinesee_group")->where(
                        "id={$member['group_id']}")->find();
                }
                if ($type == 1) {
                    $member = M("tfb_onlinesee_member")->where("id={$id}")->find();
                    $group = array();
                }
                
                $line_data = "{$member['staff_number']}," . "{$member['name']}," .
                     "{$group['group_name']}," . "{$v['short_url']}," .
                     "{$v['url_start_time']}," . "{$v['url_useful_life']}," .
                     "\r\n";
                echo iconv('utf-8', 'gbk', $line_data);
            }
        }
        $update_data = array(
            "status" => 3);
        $Where['id'] = I('id');
        $res = M('tfb_onlinesee_link_crontab')->where($Where)->save(
            $update_data);
        exit();
    }
}
