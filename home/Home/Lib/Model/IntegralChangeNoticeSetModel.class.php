<?php
/**
 * author 王盼
 * Date: 2015/12/29
 * Time: 20:27
 */
class IntegralChangeNoticeSetModel extends Model{
    protected $tableName = 'tintegral_change_notice_set';

    /**
     * 获取商户的所有消息模板
     * @param $nodeId     string  商户编号
     * @return mixed
     */
    public function allTemplate($nodeId){
        $result = $this->where(array('node_id'=>$nodeId))->select();
        return (array)$result;
    }

    /**
     * 获取模板
     * @param $nodeId string  商户编号
     * @param $type   string  消息分类
     * @return mixed
     */
    public function messageTemplate($nodeId,$type){
        $result = $this->where(array('node_id'=>$nodeId,'notice_type'=>$type))->find();
        return $result;
    }
    /**
     * 添加模板消息
     * @param $data  mixed
     * @return mixed
     */
    public function addMessageTemplate($data){
        $result = $this->add($data);
        return $result;
    }
    /**
     * 修改模板消息
     * @param $nodeId  string  商户编号
     * @param $type    string  修改的消息分类
     * @return mixed
     */
    public function modMessageTemplate($nodeId, $type, $data){
        $result = $this->where(array('node_id'=>$nodeId,'notice_type'=>$type))->save($data);
        return $result;
    }

}