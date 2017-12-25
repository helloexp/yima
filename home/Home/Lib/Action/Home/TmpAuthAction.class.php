<?php

class TmpAuthAction extends BaseAction {

    public function _initialize() {
        $userInfo = D('UserSess', 'Service')->getUserInfo();
        if ($userInfo['user_name'] == 'admin') {
            $this->_authAccessMap = '*';
        }
        parent::_initialize();
    }

    public function index() {
        $parentArr = M('tauth_power')->where(
            array(
                'level' => 1))
            ->order('grouppath')
            ->select();
        
        $parentArr = array_merge(
            array(
                array(
                    'id' => '0', 
                    'grouppath' => '-未分组', 
                    'title' => '', 
                    'memo' => '', 
                    'status' => '0')), $parentArr);
        $parentIdList = array();
        foreach ($parentArr as &$v) {
            if ($v['id'] == 0) {
                $powerArr = M('tauth_power')->where(
                    array(
                        'parent_id' => $v['id'], 
                        'level' => 0))->select();
            } else {
                $powerArr = M('tauth_power')->where(
                    array(
                        'parent_id' => $v['id']))->select();
            }
            $v['sub_power'] = $powerArr;
            
            $parentIdList[$v['id']] = '[' . $v['id'] . ']' . $v['grouppath'] .
                 '-' . $v['title'] . '(' . $v['memo'] . ')';
        }
        unset($v);
        $this->assign('parentIdList', $parentIdList);
        
        // 所有权限
        $powerArr = M('tauth_power')->where(
            array(
                'level' => '0'))
            ->order('name')
            ->select();
        $this->assign('powerList', $powerArr);
        $this->assign('parentList', $parentArr);
        $this->display();
    }

    public function editSub() {
        $parent_id = I('parent_id');
        $id = I('id');
        $data = M('tauth_power')->create($_POST);
        
        // 如果是1级，是没有上级
        if ($data['level'] == '1') {
            $data['parent_id'] = '0';
        }
        
        if ($parent_id) {
            $info = M('tauth_power')->where(
                array(
                    'id' => $parent_id, 
                    'level' => 1))->find();
            if (! $info) {
                $this->error("parent_id[" . $parent_id . "]不存在");
            }
        }
        M('tauth_power')->where(array(
            'id' => $id))->save($data);
        C('SHOW_PAGE_TRACE', true);
        $this->success("修改成功");
    }

    public function addSub() {
        $this->error("先把被删除的或者父id=0的，没用的先用完，再来加,有问题也不要找tr");
        exit();
        $parent_id = I('parent_id');
        $data = M('tauth_power')->create($_POST);
        unset($data['id']);
        // 如果是1级，是没有上级
        if ($data['level'] == '1') {
            $data['parent_id'] = '0';
        }
        $info = M('tauth_power')->where(
            array(
                'id' => $parent_id, 
                'level' => 1))->find();
        if ($parent_id) {
            if (! $info) {
                $this->error("parent_id[" . $parent_id . "]不存在");
            }
        }
        $result = M('tauth_power')->add($data);
        if (! $result) {
            $this->error("添加失败");
        }
        $this->success("成功");
    }
    // 角色管理
    public function nodeRole() {
        if ($this->isPost()) {
            $powers = I('power_id');
            $role_id = I('role_id');
            if ($powers) {
                $powers = ',' . implode(',', $powers) . ',';
            }
            $data = array(
                'powers' => $powers);
            $where = array(
                'id' => $role_id);
            M('tauth_node_role')->where($where)->save($data);
            redirect(U(''));
            exit();
        }
        
        // 查询权限列表
        $_powerList = M('tauth_power')->where(
            array(
                'level' => 1))
            ->order('grouppath')
            ->select();
        // 按分组排序
        $powerList = array();
        foreach ($_powerList as $v) {
            $powerList[$v['grouppath']][] = $v;
        }
        
        $roleList = M('tauth_node_role')->order("alias")->select();
        foreach ($roleList as & $v) {
            $ids = trim($v['powers'], ',');
            if ($ids) {
                $ids = explode(',', $ids);
                $where = array(
                    'id' => array(
                        'in', 
                        $ids));
                $subPower = M('tauth_power')->where($where)->select();
                
                $v['sub_power'] = $subPower;
            } else {
                $v['sub_power'] = array();
            }
        }
        unset($v);
        $this->assign('roleList', $roleList);
        
        $this->assign('powerList', $powerList);
        $this->display();
    }

    public function publish() {
        $paramValue = M('tsystem_param')->getFieldByParam_name(
            'BATCH_CONTROL_POWER', 'param_value');
        $wcPowerArr = json_decode($paramValue, true);
        $wcPowerArrP = $wcPowerArr;
        $batchTypeName = C('BATCH_TYPE_NAME');
        // 因为v2,a5获得的权限最多，故将其作为基准
        $basicArr = array();
        $v0dTmpArr = explode('|', $wcPowerArr['v0']);
        $v05dTmpArr = explode('|', $wcPowerArr['v0.5']);
        $v1dTmpArr = explode('|', $wcPowerArr['v1']);
        $v2dTmpArr = explode('|', $wcPowerArr['v2']);
        $v3dTmpArr = explode('|', $wcPowerArr['v3']);
        $v4dTmpArr = explode('|', $wcPowerArr['v4']);
        $v5dTmpArr = explode('|', $wcPowerArr['v5']);
        $a0dTmpArr = explode('|', $wcPowerArr['a0']);
        $a1dTmpArr = explode('|', $wcPowerArr['a1']);
        $a2dTmpArr = explode('|', $wcPowerArr['a2']);
        $a3dTmpArr = explode('|', $wcPowerArr['a3']);
        $a4dTmpArr = explode('|', $wcPowerArr['a4']);
        $a5dTmpArr = explode('|', $wcPowerArr['a5']);
        $amountGroupPower = count($v2dTmpArr);
        foreach ($v2dTmpArr as $kt => $vt) {
            $v0dTmpArr[$kt] = explode(',', $v0dTmpArr[$kt]);
            $v05dTmpArr[$kt] = explode(',', $v05dTmpArr[$kt]);
            $v1dTmpArr[$kt] = explode(',', $v1dTmpArr[$kt]);
            $v2dTmpArr[$kt] = explode(',', $v2dTmpArr[$kt]);
            $v3dTmpArr[$kt] = explode(',', $v3dTmpArr[$kt]);
            $v4dTmpArr[$kt] = explode(',', $v4dTmpArr[$kt]);
            $v5dTmpArr[$kt] = explode(',', $v5dTmpArr[$kt]);
            $a0dTmpArr[$kt] = explode(',', $a0dTmpArr[$kt]);
            $a1dTmpArr[$kt] = explode(',', $a1dTmpArr[$kt]);
            $a2dTmpArr[$kt] = explode(',', $a2dTmpArr[$kt]);
            $a3dTmpArr[$kt] = explode(',', $a3dTmpArr[$kt]);
            $a4dTmpArr[$kt] = explode(',', $a4dTmpArr[$kt]);
            $a5dTmpArr[$kt] = explode(',', $a5dTmpArr[$kt]);
            $new[$kt] = array_merge($v0dTmpArr[$kt], $v05dTmpArr[$kt], 
                $v1dTmpArr[$kt], $v2dTmpArr[$kt], $v3dTmpArr[$kt], 
                $v4dTmpArr[$kt], $v5dTmpArr[$kt], $a0dTmpArr[$kt], 
                $a1dTmpArr[$kt], $a2dTmpArr[$kt], $a3dTmpArr[$kt], 
                $a4dTmpArr[$kt], $a5dTmpArr[$kt]);
            $new[$kt] = array_filter(array_unique($new[$kt]));
            if (empty($new[$kt])) {
                $basicArr[$kt] = array();
            } else {
                foreach ($new[$kt] as $ki => $vi) {
                    $basicArr[$kt][$vi] = $batchTypeName[$vi];
                }
            }
        }
        foreach ($wcPowerArr as $kw => $vw) {
            $wcPowerArr[$kw] = explode('|', $vw);
            foreach ($wcPowerArr[$kw] as $ky => $vy) {
                $wcPowerArr[$kw][$ky] = empty($vy) ? array() : explode(',', $vy);
                if (! empty($wcPowerArr[$kw][$ky])) {
                    foreach ($wcPowerArr[$kw][$ky] as $kz => $vz) {
                        $wcPowerArr[$kw][$ky][$vz] = $batchTypeName[$vz];
                    }
                }
            }
        }
        if ($this->isPost()) {
            if (I('post.subtype') == 'a1') {
                $cols = I('post.cols');
                $batchType = I('post.batch_type');
                $wcversionArr = I('post.wcversion');
                if (empty($wcversionArr))
                    $this->error("请选择旺财版本");
                if (empty($batchType))
                    $this->error("请填写活动类型");
                if (! $batchTypeName[$batchType])
                    $this->error("请先去configBatch.php中添加活动类型");
                $tmpbasicArr = array();
                foreach ($basicArr as $kb => $vb) {
                    $tmpbasicArr = array_merge($tmpbasicArr, $vb);
                }
                if (in_array($batchTypeName[$batchType], $tmpbasicArr)) {
                    $this->error("活动类型已经存在");
                }
                $newWcPowerArr = array();
                foreach ($wcPowerArrP as $kp => $vp) {
                    $tmpArr = array();
                    $tmpvp = explode('|', $vp);
                    for ($i = 0; $i < $amountGroupPower; $i ++) {
                        if (! in_array($kp, $wcversionArr) || $i != $cols) {
                            $tmpArr[$i] = $tmpvp[$i];
                        } else {
                            $tmpArr[$i] = $tmpvp[$i] . ',' . $batchType;
                        }
                    }
                    $newWcPowerArr[$kp] = implode('|', $tmpArr);
                }
                $newWcPowerArr = json_encode($newWcPowerArr);
                $res = M('tsystem_param')->where(
                    array(
                        'param_name' => 'BATCH_CONTROL_POWER'))->setField(
                    'param_value', $newWcPowerArr);
                if ($res === false) {
                    $this->error("添加失败");
                } else {
                    log_write(
                        "修改活动发布权限：原数据：" . $paramValue . ";修改后数据：" .
                             $newWcPowerArr, "", "publishPower");
                    $this->success("添加成功");
                }
            } elseif (I('post.subtype') == 'a2') {
                $startcols = I('startcols', "");
                $endcols = I('endcols', "");
                if ($startcols != "" &&
                     (! is_numeric($startcols) || floor($startcols) != $startcols)) {
                    $this->error("能不能愉快的做朋友？请填整数");
                }
                if ($endcols != "" &&
                     (! is_numeric($endcols) || floor($endcols) != $endcols)) {
                    $this->error("能不能愉快的做朋友？请填整数");
                }
                if ($startcols != "" &&
                     ($startcols < 0 || $startcols >= count($basicArr))) {
                    $this->error("列数错误");
                }
                if ($endcols != "" &&
                     ($endcols < 0 || $endcols >= count($basicArr))) {
                    $this->error("列数错误");
                }
                if ($startcols == "" && $endcols == "") {
                    $this->error("你居然一个都不填？");
                }
                if ($startcols != "" && $endcols != "") {
                    $this->error("你居然两个都填？");
                }
                $cols = "";
                if ($startcols == "" && $endcols != "") {
                    $cols = $endcols;
                }
                if ($startcols != "" && $endcols == "") {
                    $cols = $startcols + 1;
                }
                $newWcPowerArr = array();
                foreach ($wcPowerArrP as $kp => $vp) {
                    $tmpArr = array();
                    $tmpvp = explode('|', $vp);
                    $j = 0;
                    for ($i = 0; $i <= $amountGroupPower; $i ++) {
                        if ($i == $cols) {
                            $tmpArr[$i] = "";
                            $j = 1;
                        } else {
                            $tmpArr[$i] = $tmpvp[($i - $j)];
                        }
                    }
                    $newWcPowerArr[$kp] = implode('|', $tmpArr);
                }
                $newWcPowerArr = json_encode($newWcPowerArr);
                $res = M('tsystem_param')->where(
                    array(
                        'param_name' => 'BATCH_CONTROL_POWER'))->setField(
                    'param_value', $newWcPowerArr);
                if ($res === false) {
                    $this->error("添加失败");
                } else {
                    log_write(
                        "修改活动发布权限：原数据：" . $paramValue . ";修改后数据：" .
                             $newWcPowerArr, "", "publishPower");
                    $this->success("添加成功");
                }
            } elseif (I('post.subtype') == 'a3') {
                $curcols = I('curcols', "");
                if ($curcols == "")
                    $this->error("为什么不填，要报复社会？");
                if (! is_numeric($curcols) || floor($curcols) != $curcols) {
                    $this->error("能不能愉快的做朋友？请填整数");
                }
                if ($curcols < 0 || $curcols >= count($basicArr)) {
                    $this->error("请填写正确的列");
                }
                $newWcPowerArr = array();
                foreach ($wcPowerArrP as $kp => $vp) {
                    $tmpArr = array();
                    $tmpvp = explode('|', $vp);
                    $j = 0;
                    for ($i = 0; $i < ($amountGroupPower - 1); $i ++) {
                        if ($i != $curcols) {
                            $tmpArr[$i] = $tmpvp[$i + $j];
                        } else {
                            if (! empty($tmpvp[$i])) {
                                $this->error("请先清空该列活动");
                            }
                            $j = 1;
                            $tmpArr[$i] = $tmpvp[$i + $j];
                        }
                    }
                    $newWcPowerArr[$kp] = implode('|', $tmpArr);
                }
                $newWcPowerArr = json_encode($newWcPowerArr);
                $res = M('tsystem_param')->where(
                    array(
                        'param_name' => 'BATCH_CONTROL_POWER'))->setField(
                    'param_value', $newWcPowerArr);
                if ($res === false) {
                    $this->error("添加失败");
                } else {
                    log_write(
                        "修改活动发布权限：原数据：" . $paramValue . ";修改后数据：" .
                             $newWcPowerArr, "", "publishPower");
                    $this->success("添加成功");
                }
            } else {
                $newWcPowerArr = array();
                $postArr = I('post.');
                foreach ($wcPowerArrP as $kp => $vp) {
                    $tmpArr = array();
                    for ($i = 0; $i < $amountGroupPower; $i ++) {
                        if ($kp == 'v0.5') {
                            $tmpStr = 'v05_' . $i;
                        } else {
                            $tmpStr = $kp . '_' . $i;
                        }
                        
                        $tmpArr[$kp][$i] = $postArr[$tmpStr];
                        if (! empty($tmpArr[$kp])) {
                            $tmpArr[$kp][$i] = implode(',', $tmpArr[$kp][$i]);
                        } else {
                            $tmpArr[$kp][$i] = "";
                        }
                    }
                    $newWcPowerArr[$kp] = implode('|', $tmpArr[$kp]);
                }
                $newWcPowerArr = json_encode($newWcPowerArr);
                M('tsystem_param')->where(
                    array(
                        'param_name' => 'BATCH_CONTROL_POWER'))->setField(
                    'param_value', $newWcPowerArr);
                log_write(
                    "修改活动发布权限：原数据：" . $paramValue . ";修改后数据：" . $newWcPowerArr, 
                    "", "publishPower");
                redirect(U('Home/TmpAuth/publish'));
                exit();
            }
        }
        
        $this->assign('batchTypeName', $batchTypeName);
        $this->assign('wcPowerArr', $wcPowerArr);
        $this->assign('basicArr', $basicArr);
        $this->display();
    }
}