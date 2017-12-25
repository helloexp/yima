<?php

class IntegralFbSendPointAction extends Action {
    public $nodeId='00004488';
    public $isPhonePoint='10';
    public $isWeiXinPoint='5';
    public $sendPoint='';
    public function index()
    {
        $pointMemberInfo=$this->selectMerberInfoNoPoint();
        if($pointMemberInfo){
            M()->startTrans();
            $IntegralPointTrace = new IntegralPointTraceModel();
            if($pointMemberInfo['phone_no']){
               //有手机号码给10分
               $integralRes = $IntegralPointTrace->integralPointChange(
                       '15', $this->isPhonePoint,
                       $pointMemberInfo['id'], $this->nodeId);
                if ($integralRes === false) {
                   M()->rollback();
                   log_write("给10分失败！");
               }
               $this->sendPoint=$this->isPhonePoint;
           }else{
               //给5分
               $integralRes = $IntegralPointTrace->integralPointChange(
                       '15', $this->isWeiXinPoint,
                       $pointMemberInfo['id'], $this->nodeId);
               if ($integralRes === false) {
                   M()->rollback();
                   log_write("给5分失败！");
               }
               $this->sendPoint=$this->isWeiXinPoint;
           }
            $res = D("MemberBehavior")->addBehaviorType($pointMemberInfo['id'],
                    $this->nodeId, 13,
                    $this->sendPoint);
            if ($res === false) {
                M()->rollback();
                $this->error("行为增加失败!");
            }
            M()->commit();
        }
    }
    public function selectMerberInfoNoPoint(){
        $sql="SELECT b.member_id FROM tintegral_point_trace b WHERE b.type=15 and b.node_id =".$this->nodeId;
        $map=array(
                'a.node_id'=>$this->nodeId,
                '_string'=>'a.mwx_openid !="" and a.id not in ('.$sql.')',
        );
        $pointMemberInfo=M()->table("tmember_info a")
                ->field('a.*')
                ->where($map)->find();
        if($pointMemberInfo){
            return  $pointMemberInfo;
            exit;
        }
        log_write('无记录');
    }
}