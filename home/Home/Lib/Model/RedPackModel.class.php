<?php

class RedPackModel extends Model {

    protected $tableName = 'tnode_wxpay_config';

    /**
     * 校验红包参数有没有配置
     *
     * @param unknown $nodeId
     */
    public function redPackCheck($nodeId) {
        $map = ['node_id'=>$nodeId,'status'=>'1','bonus_flag'=>'1'];
        $dataInfo = $this->where($map)->find();
        return !empty($dataInfo);
    }
    
    /**
     * 获取商户创建翼码代理红包所剩余额
     */
    public function getYmRedPackNodePrice($nodeId){
        $remainAmt = M('twx_bonus_account_info')->where("node_id='{$nodeId}'")->getField('remain_amt');
        return empty($remainAmt) ? '0' : $remainAmt;
    }
    
    /**
     * 翼码代发微信红包扣减余额
     * @param $amt 扣减金额  正数扣减  负数返还
     */
    public function YmRedPackDeductionBalance($nodeId,$amt){
        $dataInfo = M('twx_bonus_account_info')->where("node_id='{$nodeId}'")->lock(true)->find();
        if(!$dataInfo){
            $this->error = '未找到有效数据';
            return false;
        }
        if(($amt-$dataInfo['remain_amt']) > 0 ){
            $this->error = '余额不足';
            return false;
        }
        $uData = array(
            'remain_amt' => bcsub($dataInfo['remain_amt'],$amt,2)
        );
        $result = M('twx_bonus_account_info')->where("node_id='{$nodeId}'")->save($uData);
        if(!$result){
            $this->error = '余额扣减失败';
            return false;
        }
        return true;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
}