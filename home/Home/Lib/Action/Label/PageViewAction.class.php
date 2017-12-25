<?php

class PageViewAction extends BaseAction {

    public $upload_path;

    const BATCH_TYPE_STORE = 29;

    public $node_short_name = '';
    
    public $cmPayId = 0;  //和包商品
    
    public $goodsInfoModel = '';

    public function _initialize() {
        
        // parent::_initialize();
        /*
         * $node_info =
         * M('tnode_info')->where(array('node_id'=>$this->node_id))->find();
         * $this->node_short_name = $node_info['node_short_name'];
         * $this->upload_path = './Home/Upload/MicroWebImg/'.$this->node_id.'/';
         * //购物车SESSION名 $this->session_cart_name = 'session_cart_products_' .
         * $this->node_id; //商品收货地址SESISON名 $this->session_ship_name =
         * 'session_ship_products_' . $this->node_id;
         */
        //实例化
        $this->goodsInfoModel = D('GoodsInfo');
        $this->goodsInfoModel->nodeId = $this->node_id;
        
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机验证码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('node_short_name', $this->node_short_name);
    }
    
    // 单页显示
    public function index() {
        $page_id = I("page_id");
        if ($page_id == "") {
            $this->error("参数错误");
        }
        $catWhere = array(
            'page_type' => '2', 
            'id' => $page_id);
        $pageInfo = M('tecshop_page_sort')->where($catWhere)->find();
        if ($pageInfo['id'] == "") {
            $this->error("页面不存在");
        }
        
        // 即时更新商品信息
        $changeContentObject = D('O2O', 'Service');
        $pageInfo['page_content'] = $this->goodsInfoModel->getPageInfoNew($pageInfo['page_content']);
        //和包支付
        if(C('CMPAY.nodeId') == $pageInfo['node_id']){
            $this->cmPayId = $pageInfo['node_id'];
            $this->assign('cmPayId', $pageInfo['node_id']);
        }else{
            $this->cmPayId = 0;
            $this->assign('cmPayId', 0);
        }
        //和包处理
        if($this->cmPayId > 0){
            $tmpPageContent = json_decode($pageInfo['page_content'], true);
            //和包替换链接
            if(is_array($tmpPageContent['module'][0]['list'])){
                foreach ($tmpPageContent['module'][0]['list'] as $key => &$val){
                    $val['url'] = preg_replace('/m=Label&a=index/i','m=PayCM&a=payInfo',$val['url']);
                    $val['url'] = $val['url'] . '&pageId=' . $page_id;
                }
            }
            $pageInfo['page_content'] = json_encode($tmpPageContent);
        }
        // 增加pv
        M('tecshop_page_sort')->where("id='" . $page_id . "'")->setInc(
            'page_view'); // page_view
                          // +1;
        
        $currhost = C('TMPL_PARSE_STRING.__UPLOAD__') . DIRECTORY_SEPARATOR;        
        $tmp = $pageInfo['share_pic'];

        $share_pic = $currhost . $tmp;

        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // 查询店铺默认渠道访问
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $pageInfo['node_id'], 
                'batch_type' => '29'))->find();
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $pageInfo['node_id'], 
                'type' => '4', 
                'sns_type' => '46'))->getField('id');
        $label_id = get_batch_channel($m_info['id'], $channel_id, '29', 
            $pageInfo['node_id']);
        if ($label_id == "") {
            $this->error("默认渠道不存在！");
        }
        //初始化变量
        $wxShareConfig = array();
        //读取微信配置
        $wxShareConfig['config'] = D('WeiXin','Service')->getWxShareConfig();
        session("id", $label_id);

        $this->assign('shareData', $wxShareConfig);
        $this->assign('pageInfo', $pageInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('node_id', $this->node_id);
        $this->assign('share_pic', $share_pic);
        $this->display();
    }
}
