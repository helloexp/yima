<?php

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor. author:zhongs @date: 2013.07.04
 */
class TnodeChargeModel extends Model {

    protected $tablename = 'tnode_charge';

    /*
     * 样子
     */
    public function chacking($node_id, $charge_id) {
        $map = array(
            'node_id' => $node_id, 
            'charge_id' => $charge_id, 
            'status' => '0', 
            'charge_level' => '1');
        $result = $this->where($map)->select();
        if (! $result) {
            return false;
        } else {
            return true;
        }
    }
}
?>
