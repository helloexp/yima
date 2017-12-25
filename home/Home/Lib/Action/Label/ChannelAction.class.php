<?php
// 渠道访问入口
class ChannelAction extends BaseAction {

    public $channelModel;

    public function _initialize() {
        $this->channelModel = D('Channel');
    }

    public function index() {
        // 渠道
        $id = I('get.id', '', 'trim');
        $result = $this->channelModel->getChannelInfo(
            array(
                'id' => $id));
        
        if (! $result)
            $this->error('错误参数！');
        if ($result['type'] == '1')
            $this->error('渠道类型错误！');
        if ($result['status'] == '2')
            $this->error('暂时没有进行的活动哦！');
        
        if (empty($result['go_url'])&&
             (empty($result['batch_id']) || empty($result['batch_type'])))
            $this->error('暂时没有进行的活动哦！');
            // 根据当前时间判断进哪个活动，并且如果channel表的活动不是当前的更新channel表
        $labelId = $this->channelModel->getBatchChannelIdByCurrentTime($result);
        if (! $labelId)
            $this->error('暂时没有进行的活动哦！');
            
            // 跳转
        $this->redirect(
            U('Label/Label/index', 
                array(
                    'id' => $labelId)));
    }
}
