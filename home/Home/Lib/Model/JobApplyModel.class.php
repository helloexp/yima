<?php

/**
 * �����յ����û�������Ϣ & Q�����룬 �û�������Ϣ author: wang pan Date: 2015/11/18
 * Time: 11:37
 */
class JobApplyModel extends Model {

    protected $tableName = 'tjob_apply';

    /**
     * ����û�������Ϣ
     *
     * @param array() $info
     * @return number
     */
    public function addUserApply($info) {
        $data = array(
            'node_id' => $info['nodeId'], 
            'job_type' => $info['openType'], 
            'apply_user_id' => $info['user_id'], 
            'apply_content' => $info['content'], 
            'apply_time' => $info['time']);
        $result = $this->add($data);
        return $result;
    }

    /**
     * ��ȡ�̻��ύ����Ϣ
     *
     * @param string $nodeId �̻�id
     * @return mixed
     */
    public function getApplyInfo($nodeId) {
        $result = $this->where(array(
            'node_id' => $nodeId))->getField('job_type,node_id');
        return (array) $result;
    }

    /**
     * Q�����룬���
     *
     * @param array $userInfo �û�������Ϣ
     * @return string
     */
    public function addQqCode($userInfo) {
        $result = $this->add($userInfo);
        return $result;
    }

    /**
     * Q�����룬�޸�
     *
     * @param array $userInfo �û�������Ϣ
     * @param string $nodeId �̻�ID
     * @return string
     */
    public function modQqCode($userInfo, $nodeId) {
        $result = $this->where(
            array(
                'node_id' => $nodeId, 
                'job_type' => 'e'))->save($userInfo);
        
        return $result;
    }

    /**
     * ��ȡQ��������Ϣ
     *
     * @param string $nodeId �̻�ID
     * @return mixed
     */
    public function getQqCodeInfo($nodeId) {
        $result = $this->where(
            array(
                'node_id' => $nodeId, 
                'job_type' => 'e'))->find();
        return $result;
    }
}