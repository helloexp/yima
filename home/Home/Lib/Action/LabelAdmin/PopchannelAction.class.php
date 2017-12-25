<?php

class PopchannelAction extends BaseAction {

    function open_channel() {
        $id = '65'; // ��ϢID
        $row = M('tmessage_recored')->where(
            array(
                'message_id' => $id, 
                'node_id' => $this->node_id))->getfield('seq_id');
        $this->assign('seq_id', $row);
        $this->assign('id', $id);
        $this->display();
    }

    function open_pop() {
        $row = M('tpop_window_control')->where(
            array(
                'window_id' => '100', 
                'node_id' => $this->node_id))->find();
        $res = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getfield('add_time');
        if (empty($row) && $res < '20141009235959') {
            echo '0';
        } else {
            echo '1';
        }
    }
}