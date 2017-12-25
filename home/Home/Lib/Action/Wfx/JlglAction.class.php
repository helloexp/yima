<?php

class JlglAction extends BaseAction
{
    public $_authAccessMap = '*';
    public $meiHuiModel='';
    public function _initialize()
    {
        parent::_initialize();
        $this->meiHuiModel=D('MeiHui','Model');
        if($this->node_id!=C('meihui.node_id')){
            $this->error("对不起，此功能仅限美惠使用！");
        }
    }
    public function index()
    {
        $teamSetValue=$this->meiHuiModel->getTeamSetValue();
        $this->assign('teamSetValue',$teamSetValue['param_value']);
        $this->assign('set_ratio',$teamSetValue['set_ratio']);
        $this->assign('tearFlag','open2');
        $this->display('meihui/Jlgl_index');
    }
    public function ruleDelete(){
        $id=I('id');
        if(!$id){
            $this->error("缺少必要参数！");
        }
        $this->meiHuiModel->ruleDelete($id);
        $this->assign('monthlySetFlag','open2');
        $this->display('meihui/Jlgl_monthly_index');
    }
    public function monthlySet(){
        $monthlySetValue=$this->meiHuiModel->getMonthlySetValue();
        if($monthlySetValue){
            $monthlySetValue=json_decode($monthlySetValue['param_value'],true);
            $this->assign('monthValue',$monthlySetValue['month']);
            $this->assign('championValue',$monthlySetValue['reward']);
        }
        //$this->assign('monthlySetFlag','open2');
        $this->assign('tearFlag','open2');
        $this->display('meihui/Jlgl_monthly_index');
    }
    public function monthlySetSave(){
        $postData=I('post.');
        if(!$postData){
            $this->error('缺少必要参数！');
        }
        $postData=json_decode($postData['info'],true);
        $dataArr=array();
        foreach($postData as $key=>$value){
            if($key<12){
                $dataArr['month'][]=$value['value'];
            }else{
                $dataArr['reward'][]=$value['value'];
            }
        }
        $saveStatus=$this->meiHuiModel->monthlySetSave($dataArr);
        if($saveStatus===false){
            $this->error("保存失败！");
        }
        $this->success("保存成功！");
    }
    public function teamSetSave(){
        $postData=I('post.');
        if(!$postData){
            $this->error('缺少必要参数！');
        }
        $postData=json_decode($postData['info'],true);
        $dataArr=array();
        foreach($postData as $key=>$value){
            $dataArr[]=array(
                    'month_price'=>$value['orderAmount'],
                    'reward_rate'=>$value['orderDiscount']
            );
        }
        $setArr=array(
                "silver"=>I('silver'),
                "gold_sale"=>I('gold_sale'),
                "gold_manage"=>I('gold_guanli'),
                "diamond_sale"=> I('zuan_sale'),
                "diamond_manage"=>I('zuan_guanli'));
        $saveStatus=$this->meiHuiModel->teamSetSave($dataArr,$setArr);
        if($saveStatus===false){
            $this->error("保存失败！");
        }
        $this->success("保存成功！");
    }
    public function jlSend(){
        $data = $_REQUEST;
        $incentivePayment=$this->meiHuiModel->IncentivePaymentStatistics($data);
        $this->assign('Page',$incentivePayment['show']);
        $this->assign('list',$incentivePayment['list']);
        $this->assign('user_get_flag',array('未发放','已发放'));
        $this->assign('jlSendSetFlag','open2');
        $this->display('meihui/Jlgl_send');
    }
    public function jiSendDetail(){
        $this->assign('startMonth',date('Ym', strtotime('last month')));
        $this->assign('jiSendDetailFlag','open2');
        $this->display('meihui/Jlgl_send_Detail');
    }
    public function printDetails(){
        $traceMonth=I('traceMonth');
        if(!$traceMonth){
            $this->error("缺少必要参数！");
        }
        $this->assign('traceMonth',$traceMonth);
        $this->display('meihui/Jlgl_viewDetails');
    }
    public function getRecruitTrace(){
        $map=array();
        $id=I('id');
        if($id){
            $map=array('a.referee_id'=>$id);
        }
        $startMonth=I('startMonth');
        if($startMonth){
            $map=array('a.trace_month'=>$startMonth);
        }
        $jlName=I('jlName');
        if($jlName){
            $map['c.name']=array('like',"%".$jlName."%");
        }
        $data = $_REQUEST;
        $recruitTrace=$this->meiHuiModel->recruitTrace($data,$map);
        if($recruitTrace===false){
            $this->success(array('hasData'=>$recruitTrace['list'],'page'=>$recruitTrace['show'],'hasData'=>'0','type'=>'1','total'=>'0'));
        }
        $total=$this->meiHuiModel->recruitTraceCount($map);
        $this->success(array('info'=>$recruitTrace['list'],'page'=>$recruitTrace['show'],'hasData'=>'1','type'=>'1','total'=>$total));
    }
    public function getTeamTrace(){
        $map=array();
        $id=I('id');
        if($id){
            $map=array('a.referee_id'=>$id);
        }
        $startMonth=I('startMonth');
        if($startMonth){
            $map=array('a.trace_month'=>$startMonth);
        }
        $jlName=I('jlName');
        if($jlName){
            $map['c.name']=array('like',"%".$jlName."%");
        }
        $data = $_REQUEST;
        $teamTrace=$this->meiHuiModel->teamTrace($data,$map);
        if($teamTrace===false){
            $this->success(array('hasData'=>$teamTrace['list'],'page'=>$teamTrace['show'],'hasData'=>'0','type'=>'2','total'=>'0'));
        }
        $total=$this->meiHuiModel->teamTraceCount($map);
        $this->success(array('info'=>$teamTrace['list'],'page'=>$teamTrace['show'],'hasData'=>'1','type'=>'2','total'=>$total));
    }
    public function getRankingTrace(){
        $map=array();
        $id=I('id');
        if($id){
            $map=array('a.saler_id'=>$id);
        }
        $startMonth=I('startMonth');
        if($startMonth){
            $map=array('a.trace_month'=>$startMonth);
        }
        $jlName=I('jlName');
        if($jlName){
            $map['c.name']=array('like',"%".$jlName."%");
        }
        $data = $_REQUEST;
        $teamTrace=$this->meiHuiModel->rankingTrace($data,$map);
        if($teamTrace===false){
            $this->success(array('hasData'=>$teamTrace['list'],'page'=>$teamTrace['show'],'hasData'=>'0','type'=>'3','total'=>'0'));
        }
        $total=$this->meiHuiModel->rankingTraceCount($map);
        $this->success(array('info'=>$teamTrace['list'],'page'=>$teamTrace['show'],'hasData'=>'1','type'=>'3','total'=>$total));
    }
    public function getSettlementTrace(){
        $map=array();
        $id=I('id');
        if($id){
            $map=array('a.saler_id'=>$id);
        }
        $startMonth=I('startMonth');
        if($startMonth){
            $map=array('a.trace_month'=>$startMonth);
        }
        $jlName=I('jlName');
        if($jlName){
            $map['b.name']=array('like',"%".$jlName."%");
        }
        $data = $_REQUEST;
        $recruitTrace=$this->meiHuiModel->settlementTrace($data,$map);
        if($recruitTrace===false){
            $this->success(array('hasData'=>$recruitTrace['list'],'page'=>$recruitTrace['show'],'hasData'=>'0','type'=>'4','total'=>'0'));
        }
        $total=$this->meiHuiModel->settlementTraceCount($map);
        $this->success(array('info'=>$recruitTrace['list'],'page'=>$recruitTrace['show'],'hasData'=>'1','type'=>'4','total'=>$total));
    }
    public function confirmSend(){
        $traceMonth=I('traceMonth');
        if(!$traceMonth){
            $this->error("缺少必要参数！");
        }
        M()->startTrans();
        $res=$this->meiHuiModel->changeConfirmSend($traceMonth);
        if($res===false){
            M()->rollback();
            $this->error("发放失败！");
        }
        M()->commit();
        $this->success("发放成功！");
    }
    public function downDetail(){
        $traceMonth=I('traceMonth');
        if(!$traceMonth){
            $this->error("缺少必要参数！");
        }
        $dataList=$this->meiHuiModel->totalDownDetail($traceMonth);
        if($dataList===false){
            $this->error("无数据可下载！");
        }
        $fileName = date('Y-m-d') . '-奖励总计.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "奖励获取人,手机号码,所属层级,合计总额,银行名称,银行帐号,支付宝帐号,月份\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($dataList as $v) {
            $line="{$v['name']}"."\t".','.$v['phone_no'].','.$v['upname'].','.$v['total'].','.$v['bank_name'].','.$v['bank_account'].','.$v['alipay_account'].','.$v['trace_month']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function teamTraceListDown($traceMonth){
        $list=$this->meiHuiModel->teamTraceList($traceMonth);
        $fileName = date('Y-m-d') . '-团队奖励明细.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "奖励获取人,奖励金额,支付宝帐号\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($list as $v) {
            $line="{$v['name']}"."\t".','.$v['amount'].','.$v['alipay_account']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function rankingTraceListDown($traceMonth){
        $list=$this->meiHuiModel->rankingTraceList($traceMonth);
        $fileName = date('Y-m-d') . '-排名奖励.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "奖励获取人,月度排名,奖励金额,支付宝帐号\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($list as $v) {
            $line="{$v['name']}"."\t".','.$v['ranking'].','.$v['amount'].','.$v['alipay_account']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function recruitTraceListDown($traceMonth){
        $list=$this->meiHuiModel->recruitTraceList($traceMonth);
        $fileName = date('Y-m-d') . '-招募奖励.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "直接下线,直接下线手机号,奖励获取人,获取人手机号,奖励金额,支付宝帐号\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($list as $v) {
            $line="{$v['downName']}"."\t".','.$v['down_phone_no'].','.$v['name'].','.$v['phone_no'].','.$v['amount'].','.$v['alipay_account']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function settlementTraceListDown($traceMonth){
        $list=$this->meiHuiModel->settlementTraceList($traceMonth);
        $fileName = date('Y-m-d') . '-结算奖励.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "奖励获取人,订单金额,会员价,提成金额,支付宝帐号,银行卡号\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($list as $v) {
            $line="{$v['name']}"."\t".','.$v['order_amt'].','.$v['member_price'].','.$v['amount'].','.$v['alipay_account'].','.$v['bank_account']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function downDetailType(){
        $traceMonth=I('traceMonth');
        $type=I('type');
        if(!$traceMonth || !$type){
            $this->error("缺少必要参数！");
        }
        if($type==1){
            $this->teamTraceListDown($traceMonth);
        }elseif($type==2){
            $this->rankingTraceListDown($traceMonth);
        }elseif($type==3){
            $this->recruitTraceListDown($traceMonth);
        }elseif($type==4){
            $this->settlementTraceListDown($traceMonth);
        }
    }
    public function sendMsgJlMh(){
        $this->meiHuiModel->MhSendMsg($this->node_id);
    }
    public function otherSet()
    {
        $otherSetValue=$this->meiHuiModel->getotherSetValue();
        $this->assign('otherSet',$otherSetValue['param_value']);
        $this->assign('tearFlag','open2');
        $this->display('meihui/Jlgl_otherSet_index');
    }
    public function otherSetSave(){
        $postData=I('post.');
        if(!$postData){
            $this->error('缺少必要参数！');
        }
        $postData=json_decode($postData['info'],true);
        $dataArr=array();
        
        foreach($postData as $value){
            $dataArr[$value['newid']]=$value['value'];
        }
        $saveStatus=$this->meiHuiModel->otherSetSave($dataArr);
        if($saveStatus===false){
            $this->error("保存失败！");
        }
        $this->success("保存成功！");
    }
}