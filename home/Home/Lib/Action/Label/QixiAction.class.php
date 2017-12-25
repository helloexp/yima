<?php

class QixiAction extends MyBaseAction {

    public function index() {
        if ($this->batch_type != '28') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        $config_data = unserialize($row['config_data']);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $wxShareData = array(
            'config' => $wx_share_config, 
            'link' => U('index', array(
                'id' => $this->id), '', '', TRUE), 
            'title' => $row['name'], 
            'desc' => $config_data['share_descript'], 
            'imgUrl' => $row['share_pic']);
        $this->assign('wxShareData', json_encode($wxShareData));
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        $this->display(); // 输出模板
    }
}