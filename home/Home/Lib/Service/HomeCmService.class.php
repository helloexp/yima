<?php

/**
 * Home公用函数 model
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2016/01/06
 */
class HomeCmService {

    public function __construct() {
        
    }
    /**
     * Description 取得可提现金额
     *
     * @param string $nodeId 商户唯一标识
     * @return string $allowMoney 可提现金额
     */
    public function getAllowCash($nodeId){
        $cashId = M('tnode_cash_trace')->where(
            array(
                'trans_type' => '2', 
                'node_id' => $nodeId))
            ->order('id desc')
            ->limit(1)
            ->getField('id');
        if ($cashId) {
            $cashTrace = M('tnode_cash_trace')->field(
                'trans_type,sum(cash_money) as cash_money')
                ->where(
                array(
                    'id' => array(
                        'gt', 
                        $cashId), 
                    'node_id' => $nodeId))
                ->group('trans_type')
                ->select();
        } else {
            $cashTrace = M('tnode_cash_trace')->field(
                'trans_type,sum(cash_money) as cash_money')
                ->where(
                array(
                    'node_id' => $nodeId))
                ->group('trans_type')
                ->select();
        }
        $cashTrace = array_valtokey($cashTrace, 'trans_type');
        $allowMoney = isset($cashTrace['1']['cash_money']) ? $cashTrace['1']['cash_money'] : 0;
        
        return $allowMoney;
    }


    public function __destruct() {
        
    }
}
