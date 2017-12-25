<?php
class TradeAction extends BaseAction{
    public $_authAccessMap = '*';
    public function __construct()
    {
        parent::__construct();
        $this->setApi();
    }

    /**
     * 交易明细
     */
    public function detail()
    {
        $return = Api::get('agent/trade/detail',['node_id'=>'00026652']);
        Api::apiReturn($return);
    }
    /**
     * 交易明细
     */
    public function statistics()
    {
        $return = Api::get('agent/trade/statistics',['node_id'=>'00026652']);
        Api::apiReturn($return);
    }
}