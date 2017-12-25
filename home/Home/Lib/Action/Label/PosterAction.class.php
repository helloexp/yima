<?php
// 电子海报wap
class PosterAction extends MyBaseAction {

    public $channel_type = 4;

    public $channel_sns_type = 48;

    public $shop_type = 29;

    public $node_short_name = '';

    public function _initialize() {
        parent::_initialize();
        
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->node_short_name = $node_info['node_short_name'];
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '37') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 非标挂载===========开始
        if ($this->batch_id == C('dlh5.mid')) {
            $this->_dlh5_index();
        } else if ($this->batch_id == C('dlh5.cert_mid')) {
            $this->_dlh5_cert();
        }
        // 非标挂载===========结束
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        if (! $row) {
            $this->error('未找到该码上买活动信息');
        }
        $content = $this->_constructContent($row);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareImg = get_upload_url($content['arr']['share_img']);
        $coverImg = get_upload_url($content['arr']['cover_img']);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => U('index', array(
                'id' => $this->id), '', '', TRUE), 
            'title' => $row['name'], 
            'desc' => $content['arr']['share_descript'], 
            'imgUrl' => $shareImg ? $shareImg : $coverImg);
        
        $this->assign('row', $row);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $id);
        $this->assign('content', $content['json']);
        $this->display();
    }

    /**
     * 组装海报主数据和各个模板的数据
     *
     * @param array $row 包含tmarketing_info的id和memo
     * @return array $content['json'] 用于模板的module的json,$content['arr']
     *         用于模板的module的数组
     */
    private function _constructContent($row) {
        $memo = json_decode($row['memo'], true);
        $model = M('tmarketing_templet_ext');
        $result = $model->field(array(
            'page_content'))
            ->where(array(
            'm_id' => $row['id']))
            ->order('page_number asc')
            ->select();
        $data = array();
        foreach ($result as $value) {
            $data[] = json_decode($value['page_content'], true);
        }
        $memo['list'] = $data;
        $content['json'] = json_encode($memo);
        $content['arr'] = $memo;
        return $content;
    }

    /**
     * 大连H5，证书界面
     */
    public function _dlh5_cert() {
        $cookieName = 'dlh5_cert_id';
        $visitId = cookie($cookieName);
        if (! $visitId) {
            $visitId = $this->marketInfo['click_count'] + 1;
            cookie($cookieName, $visitId, 3600 * 24 * 365);
        }
        $this->assign('visitId', $visitId);
        $this->display('dlh5_cert');
        exit();
    }

    /**
     * 大连H5，手机滑动界面
     */
    public function _dlh5_index() {
        $cookieName = 'dlh5_index_id';
        $act = I('get.act');
        $visitId = cookie($cookieName);
        if (! $visitId) {
            $visitId = $this->visit($cookieName);
        }
        $shareArr = $this->shareArr($visitId);
        if ($act == "sec") {
            $this->_dlh5_main();
        }
        $id = $this->id;
        $this->assign('id', $id);
        $this->assign('shareArr', $shareArr);
        $this->display('dlh5_index');
        exit();
    }

    /**
     * 大连H5，主界面
     */
    public function _dlh5_main() {
        $cookieName = 'dlh5_index_id';
        $visitId = cookie($cookieName);
        if (! $visitId) {
            $visitId = $this->visit($cookieName);
        }
        $joiner = $this->marketInfo['click_count'];
        $shareArr = $this->shareArr($visitId);
        $this->assign('joiner', $joiner);
        $this->assign('shareArr', $shareArr);
        $this->display('dlh5_main');
        exit();
    }

    /**
     * 分享次数
     */
    public function visit($cookieName) {
        $visitId = $this->marketInfo['click_count'] + 1;
        cookie($cookieName, $visitId, 3600 * 24 * 365);
        return $visitId;
    }

    public function shareArr($visitId) {
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        if (! $row) {
            $this->error('未找到该码上买活动信息');
        }
        $content = $this->_constructContent($row);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareImg = get_upload_url($content['arr']['share_img']);
        $coverImg = get_upload_url($content['arr']['cover_img']);
        $row['name'] = str_replace("#N#", $visitId, $row['name']);
        $content['arr']['share_descript'] = str_replace("#N#", $visitId, 
            $content['arr']['share_descript']);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => U('index', array(
                'id' => $this->id), '', '', TRUE), 
            'title' => $row['name'], 
            'desc' => $content['arr']['share_descript'], 
            'imgUrl' => $shareImg ? $shareImg : $coverImg);
        return $shareArr;
    }
}