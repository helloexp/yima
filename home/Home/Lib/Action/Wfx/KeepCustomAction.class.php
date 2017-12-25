<?php

class KeepCustomAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    public function beforeCheckAuth() {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = "*";
        } elseif (! $this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index() {
        $node_info = M('twfx_node_info')->where(
            array(
                'node_id' => $this->node_id))->select();
        $saler_name = I('saler_name', '');
        $saler_phone = I('saler_phone', '');
        $customer_phone = I('customer_phone', '');
        $map['c.node_id'] = $this->node_id;
        if (! empty($saler_name)) {
            $map['s.name'] = array(
                'like', 
                '%' . $saler_name . '%');
        }
        if (! empty($saler_phone)) {
            $map['s.phone_no'] = array(
                'like', 
                '%' . $saler_phone . '%');
        }
        if (! empty($customer_phone)) {
            $map['c.phone_no'] = array(
                'like', 
                '%' . $customer_phone . '%');
        }
        import('ORG.Util.Page');
        $count = M()->table("twfx_customer_relation c")->field(
            'c.*,s.name AS saler_name,s.phone_no AS saler_phone,s.parent_id AS parent_id')
            ->join('twfx_saler s ON s.id = c.saler_id')
            ->where($map)
            ->count();
        $CPage = new Page($count, 10);
        $relation_info = M()->table("twfx_customer_relation c")->field(
            'c.*,s.name AS saler_name,s.phone_no AS saler_phone,s.parent_id AS parent_id')
            ->join('twfx_saler s ON s.id = c.saler_id')
            ->where($map)
            ->order('add_time desc')
            ->limit($CPage->firstRow . ',' . $CPage->listRows)
            ->select();
        if (! empty($relation_info)) {
            foreach ($relation_info as $ka => $va) {
                $relation_info[$ka]['parent_name'] = M('twfx_saler')->getFieldById(
                    $va['parent_id'], 'name');
            }
        }
        $page = $CPage->show();
        $this->assign('saler_name', $saler_name);
        $this->assign('saler_phone', $saler_phone);
        $this->assign('customer_phone', $customer_phone);
        $this->assign('node_info', $node_info[0]);
        $this->assign('page', $page);
        $this->assign('relation_info', $relation_info);
        $this->display();
    }

    public function allot() {
        if ($this->isPost()) {
            $checkedid = I('post.checkedid');
            $phone_no = I('get.phone_no');
            ! empty($checkedid) or $this->error("您没有选择！", null);
            ! empty($phone_no) or $this->error("手机号码不得为空！", null);
            $saler_id = M('twfx_saler')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => 3))->getFieldByPhone_no($phone_no, 'id');
            ! empty($saler_id) or $this->error("销售员手机号码不存在,或已停用！", null);
            $checkedid = array_values($checkedid);
            if (false === M('twfx_customer_relation')->where(
                array(
                    'id' => array(
                        'in', 
                        $checkedid)))->save(
                array(
                    'saler_id' => $saler_id))) {
                $this->error("批量分配失败！");
            } else {
                // redirect(U('Wfx/KeepCustom/index'));
                $this->success("分配成功!");
            }
        } else {
            $id = I('get.id');
            $phone_no = I('get.phone_no');
            ! empty($id) or $this->error("非法操作", null);
            ! empty($phone_no) or $this->error("手机号码不得为空！", null);
            $saler_id = M('twfx_saler')->getFieldByPhone_no($phone_no, 'id');
            ! empty($saler_id) or $this->error("销售员手机号码不存在！", null);
            if (false === M('twfx_customer_relation')->where(
                array(
                    'id' => $id))->save(
                array(
                    'saler_id' => $saler_id))) {
                $this->error("分配失败！");
            } else {
                // redirect(U('Wfx/KeepCustom/index'));
                $this->success("分配成功!");
            }
        }
    }

    public function loadRelation() {
        $map['c.node_id'] = $this->node_id;
        $relationSql = M()->table("twfx_customer_relation c")->field(
            'c.phone_no,DATE_FORMAT(c.add_time,"%Y-%m-%d") AS add_time,s.name AS saler_name,s.phone_no AS saler_phone,s.parent_id AS parent_id,c.saler_id,t.name AS parent_name')
            ->join('twfx_saler s ON s.id = c.saler_id')
            ->join('twfx_saler t ON t.id = s.parent_id')
            ->where($map)
            ->buildSql();
        $cols_arr = array(
            'saler_id' => '销售员ID', 
            'saler_name' => '销售员名称', 
            'saler_phone' => '销售员手机号码', 
            'parent_name' => '所属经销商', 
            'phone_no' => '消费者手机号码', 
            'add_time' => '绑定时间');
        if (querydata_download($relationSql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    /**
     * [bind 手动绑定客户关系]
     *
     * @return [type] [null]
     */
    public function bind() {
        $this->display();
    }

    /**
     * [loadTemplate 下载模板]
     *
     * @return [type] [null]
     */
    public function loadTemplate() {
        C('KC_TPL_APPLY', APP_PATH . 'Upload/keepCustomApply/');
        // 新建一个文件夹，下面的函数上传需要用到
        $rootpath = C('KC_TPL_APPLY');
        if (! file_exists($rootpath)) {
            mkdir($rootpath);
        }
        header("Content-type:text/csv");
        header("Content-Disposition: attachment;filename=custombind_tpl.csv ");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $rs = array(
            array(
                '消费者手机号码', 
                '销售员/经销商手机号码'), 
            array(
                '18818181818', 
                '16816181618'));
        $str = '';
        foreach ($rs as $row) {
            $str_arr = array();
            foreach ($row as $column) {
                $str_arr[] = '"' .
                     str_replace('"', '""', iconv('utf-8', 'gb2312', $column)) .
                     '"';
            }
            $str .= implode(',', $str_arr) . PHP_EOL;
        }
        echo $str;
    }

    /**
     * [bindAjax ajax绑定]
     *
     * @return [type] [null]
     */
    public function bindAjax() {
        C('KC_TPL_APPLY', APP_PATH . 'Upload/keepCustomApply/');
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->maxSize = 3145728;
        $upload->savePath = C('KC_TPL_APPLY');
        $info = $upload->uploadOne($_FILES['staff']);
        $flieWay = $upload->savePath . $info['savepath'] . $info[0]['savename'];
        $row = 0;
        $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
        if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
            @unlink($flieWay);
            $this->error('文件类型不符合');
        }
        $result = array();
        $insertArr = array();
        $errorLine = 0;
        if (($handle = fopen($flieWay, "rw")) !== FALSE) {
            while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                ++ $row;
                $arr = utf8Array($arr);
                if ($row == 1) {
                    $fileField = array(
                        '消费者手机号码', 
                        '销售员/经销商手机号码');
                    $arrDiff = array_diff_assoc($arr, $fileField);
                    if (count($arr) != count($fileField) || ! empty($arrDiff)) {
                        fclose($handle);
                        @unlink($flieWay);
                        $this->error('文件第' . $row . '行字段不符合要求');
                    }
                    continue;
                }
                $array = array();
                $array['customPhone'] = self::preString($arr[0]);
                $array['salerPhone'] = self::preString($arr[1]);
                $array['lineNumber'] = $row;
                
                $result[] = self::checkFileContent($array);
                $errorLine += count($result);
                
                $array['phone_no'] = $array['customPhone'];
                $array['add_time'] = date('YmdHis');
                $array['status'] = '1';
                $array['bind_from'] = '2';
                $array['node_id'] = $this->node_id;
                $array['saler_id'] = M('twfx_saler')->where(
                    array(
                        'node_id' => $this->node_id))->getFieldByPhone_no(
                    $array['salerPhone'], 'id');
                unset($array['customPhone']);
                unset($array['salerPhone']);
                unset($array['lineNumber']);
                $insertArr[] = $array;
            }
            $isempty = 0;
            foreach ($result as $k1 => $v1) {
                if (! empty($v1)) {
                    $isempty ++;
                }
            }
            if ($isempty != 0) {
                $errorStr = '';
                foreach ($result as $k => $v) {
                    if (! empty($v)) {
                        foreach ($v as $kk => $vv) {
                            $errorStr .= '' . $vv . '<br />';
                        }
                    }
                }
                @unlink($flieWay);
                $this->error($errorStr);
            }
            if (! empty($insertArr)) {
                $phoneArr = array();
                foreach ($insertArr as $kj => $vj) {
                    $phoneArr[] = $vj['phone_no'];
                }
                if (count($phoneArr) != count(array_unique($phoneArr))) {
                    @unlink($flieWay);
                    $this->error("文件中的消费者手机号码不得重复");
                }
                $User = M('twfx_customer_relation');
                $User->startTrans();
                foreach ($insertArr as $ki => $vi) {
                    if (! $User->data($vi)->add()) {
                        $User->rollback();
                        @unlink($flieWay);
                        $this->error("添加失败！");
                    }
                }
                $User->commit();
            } else {
                @unlink($flieWay);
                $this->error('您尚未填写任何绑定信息!');
            }
            @fclose($handle);
            @unlink($flieWay);
            $this->success('提交申请成功');
        }
    }

    /**
     * [checkFileContent 检查文件内容是否正确]
     *
     * @param [type] $arr [文件内容数组]
     * @return [type] [错误]
     */
    private function checkFileContent($arr) {
        $resultArr = array();
        ! empty($arr['customPhone']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的消费者手机号码不能为空！';
        (strlen($arr['customPhone']) == '11') or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的消费者手机号码必须为11位！';
        is_numeric($arr['customPhone']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的消费者手机号码不正确！';
        ! empty($arr['salerPhone']) or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的销售员手机号码不能为空！';
        (strlen($arr['salerPhone']) == '11') or
             $resultArr[] = '第' . $arr['lineNumber'] . '行的销售员手机号码必须为11位！';
        $isEmptyOfSaler = M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => $arr['salerPhone'], 
                'status' => 3))->select();
        if (empty($isEmptyOfSaler)) {
            $resultArr[] = '第' . $arr['lineNumber'] . '行的销售员/经销商手机号码不存在！';
        }
        $isEmptyOfCustom = M('twfx_customer_relation')->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => $arr['customPhone']))->select();
        if (! empty($isEmptyOfCustom)) {
            $resultArr[] = '第' . $arr['lineNumber'] . '行的消费者手机号码已绑定！';
        }
        return $resultArr;
    }

    /**
     * [preString 处理字符串]
     *
     * @param [type] $str [字符串]
     * @return [type] [处理完的字符串]
     */
    private function preString($str) {
        return htmlspecialchars(trim($str));
    }
}