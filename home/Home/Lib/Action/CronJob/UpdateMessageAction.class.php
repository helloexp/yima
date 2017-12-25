<?php
/**
 * author  wang pan
 * 更新消息数据到另张数据表去
 */
class UpdateMessageAction extends Action{
    private $memberData;      //会员中消息数据
    private $smsData;         //短信数据
    private $weChatData;      //微信消息数据
    public function __construct(){
        parent::__construct();
        //检测上一次的处理更新是否已完成
        $memcacheIp = C('SSO_MEMCACHE_IP');
        S(array(
            'type'  => 'memcache',
            'host'  => $memcacheIp,
            'port'  => '11211'
                )
        );
        $id = 0;
        //数据源头 表
        $setTableName = 'tintegral_change_notice';
        /*
        $getUpdateInfo = S($setTableName.'_INFO');
        //是否第一次执行(含memcache挂掉)
        if($getUpdateInfo !== false){
            //  0 表示上次的数据还没更新完
            if($getUpdateInfo['isRun'] == 0){
                log_write('5分钟前的数据更新尚未完成'.$getUpdateInfo['time']);
                exit('5分钟前的数据更新尚未完成！');
            }
            $id = (int)$getUpdateInfo['lastId'];
        }else{
            S($setTableName.'_INFO',array('isRun'=>0,'lastId'=>$id));
        }
        */
        S($setTableName.'_INFO',array('isRun'=>0,'lastId'=>$id));

        //数据分配
        $maxId = $this->getWellUpdataData($id);
        //开始执行数据处理
        $this->addToMemberMsgList();
//        $this->addToBatchImportdetail();
        $weChatData = $this->sendToWeChat();
        S($setTableName.'_INFO',array('isRun'=>1,'lastId'=>$maxId,'time'=>date('Y-m-d H:i:s')));
        log_write('最小ID:'.$id.'，最大ID:'.$maxId);
        exit('Success：更新完成'.$weChatData);

    }
    public function index(){

    }
    public function getWellUpdataData($id){
        $memberData = array();
        $smsData    = array();
        $weChatData = array();
        $allData    = M('tintegral_change_notice')->where(array('status' => 0, 'id'=>array('GT',$id)))->select();
//        $allData    = M('tintegral_change_notice')->where(array('status' => 0))->select();
//        $getMaxId = M('tintegral_change_notice')->field('MAX(id) AS `max`')->where(array('status' => 0, 'id'=>array('GT',$id)))->find();
        log_write('待更新消息的数量:['.count($allData).']');
        if(empty($allData)){
            exit('void Data');
        }
        $getMaxId = 0;
        foreach ($allData as $key => $value) {
            if ($value['msg_type'] == '1') {
                $memberData[] = $value;

            } elseif ($value['msg_type'] == '2') {
                $smsData[] = $value;

            } else {
                $weChatData[] = $value;

            }
            $getMaxId = $value['id'];
        }
        $this->memberData = $memberData;
        $this->smsData    = $smsData;
        $this->weChatData = $weChatData;
        //更新状态
        $isOk = M('tintegral_change_notice')->where('status = 0 and id >='.$id.' and id <= '.$getMaxId)->setField('status',1);
//        M('tintegral_change_notice')->where(array('status'=>0))->setField('status',1);
//        return (int)$getMaxId['max'];
        log_write('更新了 ['.$isOk.']条消息');
        return (int)$getMaxId;

    }
    //更新到tmember_msg_list表中
    public function addToMemberMsgList(){
        $data = $this->memberData;
        M()->startTrans();
        foreach($data as $key => $value){
            $sendMemberData = json_decode($value['content'],true);
            $addData = array(
                'msg_id'        => 0,
                'member_id'     => $value['member_id'],
                'msg_status'    => 1,
                'add_time'      => $value['add_time'],
                'content'       => $sendMemberData['cont'],
                'title'         => $sendMemberData['title']
            );
            $isOk = M('tmember_msg_list')->add($addData);
            if(!$isOk){
                log_write('数据更新到tmember_msg_list表失败：'.$value);
            }
        }
        M()->commit();
        return;
    }
    //更新到短信表中tbatch_importdetail
    public function addToBatchImportdetail(){
        $data = $this->smsData;
        M()->startTrans();
        foreach($data as $key => $value){
            $serialNumber = get_reqseq();
            $batchId = get_notes_batch_no($value['node_id']);
            $jsonData = json_decode($value['content'],true);
            $addData = array(
                    'batch_no'      => $batchId,
                    'node_id'       => $value['node_id'],
                    'request_id'    => $serialNumber,
                    'orirequest_id' => $serialNumber,
                    'phone_no'      => $jsonData['phone_no'],
                    'status'        => 0,
                    'add_time'      => date('YmdHis'),
                    'trans_type'    => 1,
                    'sms_notes'     => $jsonData['content'],
                    'send_level'    => 7
            );
            $isOk = M('tbatch_importdetail')->add($addData);
            if(!$isOk){
                log_write('数据更新到tbatch_importdetail表失败：'.$value);
            }
        }
        M()->commit();
        return;

    }
    /**
     * @return WeiXinSendService
     */
    public function getWinXinService(){
        return D('WeiXinSend', 'Service');
    }
    //发送微信模板消息
    public function sendToWeChat(){
        $data = $this->weChatData;
        $weiXinService = $this->getWinXinService();

        foreach($data as $key => $value){
            $sendData = json_decode($value['content'],true);
            $result = $weiXinService->autoSendTemplate($value['node_id'], $sendData);
            log_write('微信端返回:'.$result);
        }

        return;
    }

}