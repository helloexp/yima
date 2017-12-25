<?php
/**
 * author   wang pan
 */
class ImportModel extends Model{
    protected $tableName = 'tbatch_import';

    /**
     * 获取批量发送给个人的批次列表
     * @param $nodeId      string  商户号
     * @param $startTime   string  时间范围（开始）
     * @param $endTime     string  时间范围（结束）
     * @return mixed
     */
    public function getSendPersonalList($nodeId, $startTime = '', $endTime = ''){

        //分页
        if(empty($startTime) || empty($endTime)){
            $cont = $this->join(' a LEFT JOIN tgoods_info b ON a.batch_no=b.batch_no')->where(array('a.node_id'=>$nodeId))->count();
        }else{
            $cont = $this->join(' a LEFT JOIN tgoods_info b ON a.batch_no=b.batch_no')
                    ->where(array('a.node_id'=>$nodeId,'a.add_time'=>array('BETWEEN',array($startTime,$endTime))))
                    ->count();
        }
        import("ORG.Util.Page");
        $page = new Page($cont,10);
        $p = $page->show();

        //数据
        if(empty($startTime) || empty($endTime)){
            $data = $this->field(' a.*,b.goods_name ')
                    ->join(' a LEFT JOIN tgoods_info b ON a.batch_no=b.batch_no')
                    ->where(array('a.node_id'=>$nodeId))
                    ->order('a.add_time DESC')
                    ->limit($page->firstRow,$page->listRows)
                    ->select();
        }else{
            $data = $this->field(' a.*,b.goods_name ')
                    ->join(' a LEFT JOIN tgoods_info b ON a.batch_no=b.batch_no')
                    ->where(array('a.node_id'=>$nodeId,'a.add_time'=>array('BETWEEN',array($startTime,$endTime))))
                    ->order(' a.add_time DESC')
                    ->limit($page->firstRow,$page->listRows)
                    ->select();
        }

        return array('list'=>(array)$data,'page'=>$p);

    }

    /**获取发送失败的记录
     * @param   string  $nodeId    商户号
     * @param   string  $batchId   批次号
     * @return  mixed
     */
    public function getFailList($nodeId,$batchId){

        $result = M('tbatch_importdetail')->field("DATE_FORMAT(add_time,'%Y-%m-%d %H:%i:%s') as tim,phone_no as tel,ret_desc as failReason,request_id,node_id")->where(array('node_id'=>$nodeId,'batch_id'=>$batchId,'status'=>array('IN','2,3,9')))->select();
        return $result;
    }

    /**
     * 批量发送给个人的详情数据
     * @param  string    $nodeId
     * @param  string    $batchId
     * @param  string    $phone
     * @param  string    $status
     * @return mixed
     */
    public function batchSendCardData($nodeId, $batchId, $phone='', $status='0'){
        $where = array(
            'node_id'       => $nodeId,
            'batch_id'      => $batchId,
        );
        //当有搜索条件时
        if(!empty($phone)){
            $where['phone_no'] = $phone;
        }
        if($status == 1){               //成功
            $where['status'] = 1;
        }elseif($status == 2){          //失败
            $where['status'] = array('IN','2,3,9');
        }elseif($status == 3){          //正常
            $where['status'] = 0;
        }else{}                         //所有的

        $count = M('tbatch_importdetail')->where($where)->count();
        import("ORG.Util.Page");
        $page = new Page($count,10);

        $result['list'] = M('tbatch_importdetail')->where($where)->select();

        $result['page'] = $page->show();


        return $result;
    }

    /**
     * 批量发送卡券的，卡券相关配置信息，供批量发送卡券中的详情使用，建议做到redis中去
     * @param  string    $nodeId
     * @param  string    $batchId
     * @return mixed
     */
    public function batchSendCardConfig($nodeId,$batchId){
        $field = " a.batch_id,a.total_count,a.succ_num,a.fail_num,DATE_FORMAT(a.add_time,'%Y-%m-%d %H:%i:%s') AS add_time,a.mms_notes,DATE_FORMAT(a.verify_begin_time,'%Y-%m-%d %H:%i:%s') AS verify_begin_time,DATE_FORMAT(a.verify_end_time,'%Y-%m-%d %H:%i:%s') AS verify_end_time,a.batch_desc,";
        $field .= "b.goods_name ";
        $result = $this->field($field)->join(' a LEFT JOIN tgoods_info b ON a.batch_no=b.batch_no ')->where(array('a.node_id'=>$nodeId,'a.batch_id'=>$batchId))->find();
        return $result;
    }
    /**
     * 批量发送卡券失败后的重发
     * @param string     $nodeId
     * @param string     $batchId
     * @return mixed
     */
    public function reBatchSend($nodeId, $batchId)
    {

        $result = $this->where(array('batch_id'=>$batchId,'node_id'=>$nodeId))->save(array('fail_num'=>0,'status'=>0));
        if(!$result){
            return false;
        }
        $where   = array('node_id' => $nodeId, 'batch_id' => $batchId,'status'=>array('IN','2,3,9'));
        $result  = M('tbatch_importdetail')->where($where)->save(array('status'=>0));

        return $result;
    }


}