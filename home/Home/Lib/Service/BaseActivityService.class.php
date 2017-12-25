<?php

class BaseActivityService extends Action {

    /*
     * $batchType int 活动类型 @return $activityArray Array 活动描述
     */
    function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $this->userInfo = $userService->getUserInfo();
        $this->node_id = $userInfo['node_id'];
    }

    function checkactivityexist($id, $batchType, $nodeArray) {
        if ($id == '') {
            $this->error('参数错误');
            exit();
        }
        
        $marketingInfoModel = M('TmarketingInfo');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $nodeArray . ")"), 
            'id' => $id, 
            'batch_type' => $batchType);
        
        $activityArray = $marketingInfoModel->where($map)->find();
        
        if (empty($activityArray)) {
            $this->error('未查询到数据！');
            exit();
        } else {
            return $activityArray;
        }
    }

    function activityAddDataBaseVirefy($data, $batchType, $imgPath) {
        if (! is_array($data) || empty($data)) {
            $this->error('参数有误！');
            exit();
        }
        
        $result = array();
        $this->checkisactivityheadinfowrite($data);
        $this->checkisactivitynamesame($data['name'], $batchType);
        
        if (! empty($data['sns_type'])) {
            $result['sns_type'] = implode('-', $data['sns_type']);
        } else {
            $result['sns_type'] = '';
        }
        
        // logo
        if ($data['resp_log_img'] != '' && $data['is_log_img'] == 1) {
            $result['log_img'] = $data['resp_log_img'];
        } else {
            $result['log_img'] = '';
        }
        
        // 背景
        if (get_val($data['resp_bg_img']) != '') {
            $result['bg_img'] = $data['resp_bg_img'];
        } else {
            $result['bg_img'] = '';
        }
        return $result;
    }
    
    function changeTheActivityStatus($data, $batchType, $nodeArray) {
        if (! is_array($data) || empty($data)) {
            $this->error('传入数据有误');
            exit();
        }
        $this->checkactivityexist($data['batch_id'], $batchType, $nodeArray);
        $result = M('tmarketing_info')->where(
            array(
                'id' => $data['batch_id']))->save(
            array(
                'status' => $data['status']));
        return $result;
    }

    function implodearray($data, $symbol = '-') {
        if (is_array($data)) {
            return implode($symbol, $data);
        }
    }
    
    // 传入提交表单
    function checkisactivityheadinfowrite($data) {
        if (empty($data['name'])) {
            $this->error('请填写活动名称！');
        }
        
        if (empty($data['start_time'])) {
            $this->error('请填写标签可用开始时间！');
        }
        
        if (empty($data['end_time'])) {
            $this->error('请填写标签可用结束时间！');
        }
        
        if (empty($data['wap_title'])) {
            $this->error('请填写wap页面标题！');
        }
        
        if (empty($data['wap_info'])) {
            $this->error('活动页面内容不能为空');
        }
    }
    
    // 传入活动名称
    function checkisactivitynamesame($name, $batchType, $activityID = '') {
        if ($name == '' || $name == 'null') {
            $this->error('请传入正确的值！');
        } else {
            $MarketingInfoModel = M('TmarketingInfo');
            $condition = array(
                'name' => $name, 
                'batch_type' => $batchType, 
                'node_id' => $this->node_id);
            if ($activityID != '') {
                $condition['id'] = array(
                    'neq', 
                    $activityID);
            }
            $info = $MarketingInfoModel->where($condition)->count('id');
            if ($info > 0) {
                $this->error("活动名称重复");
            }
        }
    }
}