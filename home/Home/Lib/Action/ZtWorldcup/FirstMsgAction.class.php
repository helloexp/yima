<?php
// ��Ϣ��֪��
class FirstMsgAction extends BaseAction {

    public function index() {
        $node_id = $this->nodeId;
        $worldCup_flag = M('tnode_info')->where("node_id='" . $node_id . "'")
            ->limit('1')
            ->getField('world_cup_flag');
        
        $this->assign('worldCup_flag', $worldCup_flag);
        $this->display(); // ���ģ��
    }

    public function status_change() {
        $node_id = $this->nodeId;
        $status = I('status');
        
        // ����������������
        if ($status == 1) {
            // ����״̬����
            $data = array(
                'world_cup_flag' => 1);
            $result = M('tnode_info')->where("node_id='" . $node_id . "'")->save(
                $data);
            // �ر�����
        } else {
            // ����״̬����
            $data = array(
                'world_cup_flag' => 0);
            $result = M('tnode_info')->where("node_id='" . $node_id . "'")->save(
                $data);
        }
        
        if ($result) {
            echo "1";
        } else {
            echo "2";
        }
        exit();
    }
}