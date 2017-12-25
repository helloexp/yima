<?php
// 标签访问入口
class LabelAction extends BaseAction {


    /**
     * @var GetRealUrlService
     */
    protected $GetRealUrlService;


    public function _initialize() {
        $this->GetRealUrlService = D('GetRealUrl', 'Service');
    }

    public function index() {
        // 标签
        $id = I('id');

        $param = ['id' => $id];
        B('LabelRedirect', $param);
        if ($id) {
            import('@.Vendor.RankHelper');
            $RankHelper = RankHelper::getInstance();
            $RankHelper->addOneScore($id);
        }

        $salerId = I('saler', null);
        if (empty($salerId)) {
            $salerId = I('saler_id', null);
        }

        $finalUrl = $this->GetRealUrlService->getRealUrl($id, $salerId);
        if ($finalUrl) {
            $this->redirect($finalUrl);
        } else {
            $this->error('url error');
        }


    }
    
    public function QRCode() {
        /**
         * Task : #16557 上架成功提示页面添加商品二维码，并添加下载功能；订单详情页添加商品二维码 Author: Zhaobl
         * Date: 2015/12/25
         */
        $id = I('id');
        $status = I('status'); // 如果是1 就是新上架成功的 id是真货
        $preview = I('preview'); // 若有 则是页面预览进来的
        $isdown = I('isdown'); // 若有 则是下载
        $isview = I('isview'); // 复制链接地址专用
        $filename = I('name');
        $time = I('qrcodeTime');
        
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        
        //注释掉老的逻辑，因为小店无法读取到信息
//        if ($status == 1) {
//            $qrcodeId = $id;
//        } else {
//            //获取二维码ID
//            $qrcodeId = $this->goodsModel->getQrcodeId($id);
//        }
        $qrcodeId = $id;
        $url = D('GoodsInfo')->getGoodsUrl($qrcodeId);
        if ($preview == 1) {
            header("Location: $url");
            exit();
        }
        $logourl = '';
        $color = '000000';
        if ($isdown == '1') {
            // 下载二维码
            $makecode->MakeCodeImg($url, true, '', $logourl,$filename.'_'.$time, $color);
        }if ($isview == '1') {
            // 下载二维码
            $location = strpos($url, 'ttp://');
            if ($location != 1) {
                // 查找http在不在里面 在里面则为全路径 不在里面则拼接路径
                $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
            }
            redirect($url);
        } else {
            // 展示二维码
            $makecode->MakeCodeImg($url, false, '', $logourl, '', $color);
        }
    }
}
