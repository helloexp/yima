<?php

/**
 *
 * @author wangy 20160612
 */
class PosPayPriceModel extends Model {
    public $epos_price;
    public $er6800_price;
    public $er1100_price;
    public $gprs_price;
    public $duomi_price;
    public $er6800_deposit;
    public $er6800_install;
    public $er1100_terminal;
    public function _initialize(){
        $this->epos_price      = C('POS_PRICEINFO')['EPOS_SERV_PRICE'];
        $this->er6800_price    = C('POS_PRICEINFO')['ER6800_SERV_PRICE'];
        $this->er1100_price    = C('POS_PRICEINFO')['ER1100_SERV_PRICE'];
        $this->gprs_price      = C('POS_PRICEINFO')['GPRS_PRICE'];
        $this->duomi_price     = C('POS_PRICEINFO')['DUOMI_SERV_PRICE'];
        $this->er6800_deposit  = C('POS_PRICEINFO')['ER6800_DEPOSIT_PRICE'];
        $this->er6800_install  = C('POS_PRICEINFO')['ER6800_INSTALL_PRICE'];
        $this->er1100_terminal = C('POS_PRICEINFO')['ER1100_TERMINAL_PRICE'];
    }
    public function SetSpecialPrice($nodeId){
        $map = [
            'node_id'   => $nodeId,
            'charge_id' => ['in',['28','9']]
        ];
        $synPrice = D('ActivityPayConfig')->getPriceConfig($map);
        if ($synPrice) {
            foreach ($synPrice as $price) {
                if ($price['charge_id'] == '28') {
                    $this->epos_price   = intval($price['price']);
                    $this->er1100_price = intval($price['price']);
                } elseif ($price['charge_id'] == '9') {
                    $this->er6800_price = intval($price['price']);
                }
            }
        }
    }

}