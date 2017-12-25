<?php

/**
 * @@@ mamajie【他妈预览】 @@@ add dongdong @@@ time 2015/04/10 10:41
 */
class MamaLookAction extends MyBaseAction {
    // 活动类型
    const mamaJie = 46;
    // 活动配置
    public $marketConf;

    public function _initialize() {
        parent::_initialize();
        if ($this->batch_type != self::mamaJie)
            $this->error('错误访问！');
        $marketInfo = $this->marketInfo;
        $this->assign('marketInfo', $marketInfo);
    }

    public function index() {
        import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入包失败');
        $cardId = I('cardId', '', 'trim');
        $cardId = str_replace(' ', '+', $cardId);
        $trace_id = Des::decrypt(base64_decode($cardId), 
            C('BATCH_MAMA.DES_KEY'));
        $info = M('tmama_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $trace_id))->find();
        
        if ($info == false)
            $this->error('错误访问！');
        
        $this->assign('row', $info);
        $this->assign('mid', $mid);
        $this->display();
    }
}