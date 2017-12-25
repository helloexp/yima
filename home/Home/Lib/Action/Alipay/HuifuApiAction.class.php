<?php

/**
 * 惠付模块
 * Class HuifuAction
 */
class HuifuApiAction extends BaseAction
{
    public $_authAccessMap = '*';
    public function __construct()
    {
        parent::__construct();
        $this-> setApi();
    }
    public function getUser()
    {
        $return = $_SESSION['userSessInfo'];
        $return['name'] = iconv("GBK", "UTF-8//IGNORE", $return['name']);
        Api::jsonReturn( $return );
    }
    public function info()
    {
        //$nodeId = I('get.node_id');
        $data = [
            'node_id'=> $this-> node_id,
        ];
        $return = Api::get( 'api/huifu/info', $data );
        Api::apiReturn( $return );
    }
    public function setPay()
    {
        //$nodeId  = I('post.node_id');
        $payType = I('post.pay_type');
        $mchId   = I('post.mch_id');
        $key     = I('post.key');

        $data = [
            'node_id' => $this-> node_id,
            'pay_type'=> $payType,
            'mch_id'  => $mchId,
            'key'     => $key,
        ];

        if( !$_FILES['sslcert']['error'] > 0 && !$_FILES['sslkey']['error'] > 0 )
        {
            move_uploaded_file( $_FILES["sslcert"]["tmp_name"], './Home/Upload/wxsslTemp/sslcert.pem');
            move_uploaded_file( $_FILES["sslkey"]["tmp_name"], './Home/Upload/wxsslTemp/sslkey.pem');

            $sslcertPath = realpath('./Home/Upload/wxsslTemp/sslcert.pem');
            $sslkeyPath  = realpath('./Home/Upload/wxsslTemp/sslkey.pem');
            
            //$data['sslcert'] = new CURLFile($sslcertPath);
            //$data['sslkey']  = new CURLFile($sslkeyPath);
            $data['sslcert'] = '@'.$sslcertPath;
            $data['sslkey']  = '@'.$sslkeyPath;
        }
        elseif( $payType != 1 )
        {
            $arr = [
                'code'=> '40001',
                'msg' => '缺少必要参数',
                'data'=> '',
            ];
            exit(json_encode( $arr, JSON_UNESCAPED_UNICODE ));
        }

        $return = Api::post( 'api/huifu/set_pay', $data );
        Api::apiReturn( $return );
    }
    public function saleSave()
    {
        $json = htmlspecialchars_decode(I('post.data'));
        $data = [
            'data'=> $json,
        ];
        $return = Api::post( 'api/huifu/sale/save', $data );
        Api::apiReturn( $return );
    }
    public function saleList()
    {
        //$nodeId  = I('get.node_id');
        $id      = I('get.id');
        $page    = I('get.page');
        $limit   = I('get.limit');
        $title   = I('get.title');
        $status  = I('get.status');
        $data = [
            'node_id'=> $this-> node_id,
            'id'     => $id,
            'page'   => $page,
            'limit'  => $limit,
            'title'  => $title,
            'status' => $status,
        ];
        $return = Api::get( 'api/huifu/sales', $data );
        Api::apiReturn( $return );
    }
    public function orders()
    {
        //$nodeId  = I('get.node_id');
        $page    = I('get.page');
        $limit   = I('get.limit');
        $keyword = I('get.keyword');
        $saleId  = I('get.sale_id');
        $start   = I('get.start');
        $end     = I('get.end');
        $data = [
            'node_id'=> $this-> node_id,
            'page'   => $page,
            'limit'  => $limit,
            'keyword'=> $keyword,
            'sale_id'=> $saleId,
            'start'  => $start,
            'end'    => $end,
        ];
        $return = Api::get( 'api/huifu/orders', $data );
        Api::apiReturn( $return );
    }
    public function ordersOutCsv()
    {
        //$nodeId  = I('get.node_id');

        $page    = I('get.page');
        $limit   = I('get.limit');
        $keyword = I('get.keyword');
        $saleId  = I('get.sale_id');
        $start   = I('get.start');
        $end     = I('get.end');
        $data = [
            'node_id'=> $this-> node_id,
            'page'   => $page,
            'limit'  => $limit,
            'keyword'=> $keyword,
            'sale_id'=> $saleId,
            'start'  => $start,
            'end'    => $end,
            'mode'   => 'csv'
        ];
        $return = Api::get( 'api/huifu/orders', $data );
        $arr    = json_decode( $return, true );
        if( $arr['code'] != 0 )
        {
            Api::apiReturn( $return );
        }
        else
        {
            $result = $arr['data'];
            Api::csv_h( '收款记录' );
            Api::downloadCsvData( $result, [ '旺财流水号','昵称','openid','优惠标题','应付(元)','实付(元)','优惠(元)','手续费(元)','实收(元)','优惠外金额(元)','支付平台订单号','支付时间' ]);
        }
    }
    public function saleMenu()
    {
        //$nodeId  = I('get.node_id');
        $data = [
            'node_id'=> $this-> node_id,
        ];
        $return = Api::get( 'api/huifu/sale/menu', $data );
        Api::apiReturn( $return );
    }
    public function stats()
    {
        //$nodeId = I('get.node_id');
        $page   = I('get.page');
        $limit  = I('get.limit');
        $start  = I('get.start');
        $end    = I('get.end');
        $data = [
            'node_id'=> $this-> node_id,
            'page'   => $page,
            'limit'  => $limit,
            'start'  => $start,
            'end'    => $end,
        ];
        $return = Api::get( 'api/huifu/stat', $data );
        Api::apiReturn( $return );
    }
    public function test()
    {
        $data = [
            'node_id'=> '00029760',
            'url'    => 'http://test.wangcaio2o.com/index.php?g=Alipay&m=HuifuApi&a=test',
        ];
        $return = Api::get( 'public/wxjdk', $data );
        $aa = json_decode( $return, true );
        $this->assign( 'data',$aa['data'] );
        $this->display();
    }
}