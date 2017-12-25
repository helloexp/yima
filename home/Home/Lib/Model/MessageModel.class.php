<?php

class MessageModel extends Model {
    protected $tableName = '__NONE__';
    public function send($title, $content, $nodeId) {
        $model = new Model();
        $model->startTrans();
        $data = array(
            'title' => $title, 
            'content' => $content, 
            'add_time' => date('YmdHis'), 
            'msg_type' => '2', 
            'cuser_id' => '0', 
            'status' => '0', 
            'send_to_who' => '4');
        $messageId = $model->table('tmessage_news')->add($data);
        if ($messageId === false) {
            $model->rollback();
            return false;
        }
        $data = array(
            "message_id" => $messageId, 
            "node_id" => $nodeId, 
            "send_status" => '0', 
            "status" => '0', 
            "add_time" => date('YmdHis'));
        $flag = $model->table('tmessage_recored')->add($data);
        if ($flag === false) {
            $model->rollback();
            return false;
        }
        $nRet = $model->table('tmessage_stat')->getByNode_id($nodeId);
        if (empty($nRet)) {
            $sql = 'update tmessage_stat set total_cnt =total_cnt +1,new_message_cnt=new_message_cnt+1,last_time="' .
                 date("YmdHis") . '" where message_type=1 and node_id = "' .
                 $nodeId . '"';
        } else {
            $sql = 'insert into tmessage_stat(total_cnt,new_message_cnt,last_time,message_type,node_id) values(1,1,"' .
                 date("YmdHis") . '",1,"' . $nodeId . '")';
        }
        $nRet = $model->execute($sql);
        if ($nRet === false) {
            $model->rollback();
            return false;
        } else {
            $model->table('tmessage_news')
                ->where(array(
                'id' => $messageId))
                ->save(array(
                'msg_type' => '1'));
            $model->commit();
            return true;
        }
    }
}