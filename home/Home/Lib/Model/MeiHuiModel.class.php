<?php
class MeiHuiModel extends Model {
    protected $tableName = '__NONE__';
    public $title='会员等级变化';
    public function monthlySetSave($dataArr){
        $saveData=array(
            'param_name'=>'SALER_REWARD_CONFIG',
            'param_value'=>json_encode($dataArr),
            'comment'=>'月度排行设置'
        );
        $map=array('param_name'=>'SALER_REWARD_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        if($res){
             $configStatus=M("tfb_mh_wfx_config")->where($map)->save($saveData);
             $currentTime=$this->GetMonth();
             $lastPerMonth=M("tfb_mh_wfx_config_permonth")->where(array('trace_month'=>$currentTime))->find();
             if($lastPerMonth){
                $res=M("tfb_mh_wfx_config_permonth")->where(array('trace_month'=>array('ELT',$currentTime)))->save(array('reward_flag'=>'1'));
                if($res===false){
                    return false;
                }
             }
        }else{
              $configStatus=M("tfb_mh_wfx_config")->add($saveData);
        }
        if($configStatus===false){
            return false;
        }
        return true;
    }
    public function GetMonth(){
        //得到系统的年月
        $tmp_date=date("Ym");
        //切割出年份
        $tmp_year=substr($tmp_date,0,4);
        //切割出月份
        $tmp_mon =substr($tmp_date,4,2);
        $tmp_nextmonth=mktime(0,0,0,$tmp_mon+1,1,$tmp_year);
        $tmp_forwardmonth=mktime(0,0,0,$tmp_mon-1,1,$tmp_year);
        //得到当前月的上一个月
        return $fm_forward_month=date("Ym",$tmp_forwardmonth);
    }
    public function getMonthlySetValue(){
        $map=array('param_name'=>'SALER_REWARD_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        return $res;
    }
    public function getTeamSetValue(){
        $map=array('param_name'=>'TEAM_REWARD_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        if($res){
            return array(
                    'param_value'=>json_decode($res['param_value'],true),
                    'set_ratio'=>json_decode($res['set_ratio'],true));
        }
    }
    public function teamSetSave($dataArr,$setArr){
        $saveData=array(
                'param_name'=>'TEAM_REWARD_CONFIG',
                'param_value'=>json_encode($dataArr),
                 'set_ratio'=>json_encode($setArr),
                'comment'=>'团队奖励设置'
        );
        $map=array('param_name'=>'TEAM_REWARD_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        if($res){
            $configStatus=M("tfb_mh_wfx_config")->where($map)->save($saveData);
        }else{
            $configStatus=M("tfb_mh_wfx_config")->add($saveData);
        }
        if($configStatus===false){
            return false;
        }
        return true;
    }
    public function IncentivePaymentStatistics($data){
        $countSql=M()->table('tfb_mh_wfx_trace a')
                ->Field('a.trace_month,SUM(a.amount) AS score')
                ->join("tfb_mh_wfx_config_permonth b on a.trace_month=b.trace_month")
                ->group('a.trace_month')
                ->select(false);
        $count= M()->query("select count(*) as total from".$countSql."as aa");
        $mapcount=$count[0]['total'];
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_trace a')
                ->Field('a.trace_month,SUM(a.amount) AS score,b.send_flag')
                ->join("tfb_mh_wfx_config_permonth b on a.trace_month=b.trace_month")
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('a.trace_month desc')
                ->group('a.trace_month')
                ->select();
        $listArr=array();
        if($list){
            foreach($list as $key=>$val){
                $val['trace_month1']=$this->getCurMonthFirstDay(substr($val['trace_month'],0,4)."-".substr($val['trace_month'],4,2));
                $listArr[]=$val;
            }
        }
        return $goodsInfo=array(
                'list'=>$listArr,
                'show'=>$show
        );
    }
    function getCurMonthFirstDay($date){
        $firstday = date("Y-m-01",strtotime($date));
        $lastday = date("Y-m-d",strtotime("$firstday +1 month -1 day"));
        return $firstday."至".$lastday;
    }
    public function recruitTrace($data,$map=''){
        $mapcount=M()->table('tfb_mh_wfx_recruit_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->join('twfx_saler c on a.referee_id=c.id')
                ->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_recruit_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->join('twfx_saler c on a.referee_id=c.id')
                ->Field('b.name as downName,b.phone_no as down_phone_no,c.name,c.phone_no,a.amount,c.alipay_account,c.bank_account')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->where($map)
                ->order('a.id desc')
                ->select();
        if(empty($list)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function settlementTrace($data,$map=''){
        $mapcount=M()->table('tfb_mh_wfx_settlement_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_settlement_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->Field('b.name,a.order_amt,a.member_price,a.amount,b.alipay_account,b.bank_account')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->where($map)
                ->order('a.id desc')
                ->select();
        if(empty($list)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function settlementTraceCount($map){
        $list =M()->table('tfb_mh_wfx_settlement_trace a')
                ->where($map)
                ->sum('a.amount');
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function recruitTraceCount($map){
        $list =M()->table('tfb_mh_wfx_recruit_trace a')
                ->where($map)
                ->sum('a.amount');
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function teamTraceCount($map){
        $list =M()->table('tfb_mh_wfx_team_trace a')
                ->where($map)
                ->sum('a.amount');
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function rankingTraceCount($map){
        $list =M()->table('tfb_mh_wfx_ranking_trace a')
                ->where($map)
                ->sum('a.amount');
        if(empty($list)){
            return false;
        }
        return $list;
    }

    public function teamTrace($data,$map=''){
        $mapcount=M()->table('tfb_mh_wfx_team_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_team_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->Field('c.name,a.amount,c.alipay_account,c.bank_account')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->where($map)
                ->order('a.id desc')
                ->select();
        if(empty($list)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function teamTraceList($traceMonth){
        $map=array('a.trace_month'=>$traceMonth);
        $list =M()->table('tfb_mh_wfx_team_trace a')
                ->join('twfx_saler c on a.referee_id=c.id')
                ->Field('c.name,a.amount,c.alipay_account')
                ->where($map)
                ->order('a.id desc')
                ->select();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public  function recruitTraceList($traceMonth){
        $map=array('a.trace_month'=>$traceMonth);
        $list =M()->table('tfb_mh_wfx_recruit_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->join('twfx_saler c on a.referee_id=c.id')
                ->Field('b.name as downName,b.phone_no as down_phone_no,c.name,c.phone_no,a.amount,c.alipay_account')
                ->where($map)
                ->order('a.id desc')
                ->select();
        return $list;
    }
    public  function settlementTraceList($traceMonth){
        $map=array('a.trace_month'=>$traceMonth);
        $list =M()->table('tfb_mh_wfx_settlement_trace a')
                ->join('twfx_saler b on a.saler_id=b.id')
                ->Field('b.name,a.order_amt,a.member_price,a.amount,b.alipay_account,b.bank_account')
                ->where($map)
                ->order('a.id desc')
                ->select();
        return $list;
    }
    public function  rankingTraceList($traceMonth){
        $map=array('a.trace_month'=>$traceMonth);
        $list =M()->table('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->Field('c.name,a.amount,a.ranking,c.alipay_account')
                ->where($map)
                ->order('a.ranking desc')
                ->select();
        return $list;
    }
    public function rankingTrace($data,$map=''){
        $mapcount=M()->table('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_ranking_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->Field('c.name,a.amount,a.ranking,c.alipay_account,c.bank_account')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->where($map)
                ->order('a.ranking desc')
                ->select();
        if(empty($list)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function changeConfirmSend($traceMonth){
        $res=M("tfb_mh_wfx_trace")->where(array('trace_month'=>$traceMonth))->save(array('user_get_flag'=>1));
        if($res===false){
            return false;
        }
        $res2=M("tfb_mh_wfx_config_permonth")->where(array('trace_month'=>$traceMonth))->save(array('send_flag'=>1));
        if($res2===false){
            return false;
        }
        return true;
    }
    public function getBelongAgency($nodeId) {
        $agencyLevelInfo = M('twfx_saler')->field(
                'id,parent_id,phone_no,level,parent_path,name')
                ->where(
                        array(
                                'role' => '2',
                                'node_id' => $nodeId,
                                'status' => '3',
                                'level' =>1))
                ->select();
        return $agencyLevelInfo;
    }
    /*
     * 获取钻石和门店
     */
    public function getMemberLevel1($nodeId){
        $map=array(
             'node_id'=>$nodeId,
             'status'=>'3',
             'level'=>array('ELT',2)
        );
        $list=M("twfx_saler")->where($map)->select();
        return $list;
    }
    /*
     * 获取门店金银
     */
    public function getMemberLevel2($nodeId){
        $map=array(
                'node_id'=>$nodeId,
                'status'=>'3',
                'level'=>array('ELT',3)
        );
        $list=M("twfx_saler")->where($map)->select();
        return $list;
    }
    public function getMhMemberByLevel($nodeId,$level,$data,$name='',$phone='',$status){
        $map=array(
                'node_id'=>$nodeId,
                'level'=>$level,
//                'status'=>'3'
        );
        if($name){
            $map['name']=array('like',"%".$name."%");
        }
        if($phone){
            $map['phone_no']=array('like',"%".$phone."%");
        }
        if($status){
            $map['status']=$status;
        }
        $mapcount=M('twfx_saler')->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M('twfx_saler')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->Field('id,name,level,node_id,add_time,add_user_name,status,phone_no,parent_id,parent_path')
                ->where($map)
                ->order('id desc')
                ->select();
        if (! empty($list)) {
            foreach ($list as $ka => $va) {
                $mapCount=array(
                        "node_id"=>$nodeId,
                        "parent_path"=>array('like',$va['parent_path'].$va['id'].",%"),
                        'level'=>array('GT',$level)
                );
                $mapCountStatus=$mapCount;
                $mapCountStatus['status']=3;
                $list[$ka]['count'] = M('twfx_saler')->where($mapCountStatus)->count();
                $list[$ka]['add_time'] =date('Ymd',strtotime($va['add_time'])) ;
                //上级
                if($level>=2 && $level<5){
                    $list[$ka]['parentName'] = M('twfx_saler')->where(array('node_id'=>$nodeId,'id'=>$va['parent_id']))->getField('name');
                }
                //门店
                if($level>=3  && $level<5){
                    $arr = explode(",",$va['parent_path']);
                    $list[$ka]['storeName'] = M('twfx_saler')->where(array('node_id'=>$nodeId,'id'=>$arr[1]))->getField('name');
                }
            }
        }
        if(empty($list)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function getMemberCountByLevelAndId($nodeId,$saler_id,$level,$parentPath){
        $map=array(
                'node_id'=>$nodeId,
                'status'=>'3',
                'level'=>array('GT',$level),
                "parent_path"=>array('like',$parentPath.$saler_id."%")
        );
        $count=M("twfx_saler")->where($map)->count();
        return $count;
    }
    public function addMhMsg($phoneNo,$nodeId,$title,$content){
        $memberInfo=M("tmember_info")->where(array('node_id'=>$nodeId,'phone_no'=>$phoneNo))->find();
        if(empty($memberInfo)){
            return true;
        }
        $data=array(
             'msg_id'=>'0',
             'member_id'=>$memberInfo['id'],
             'msg_status'=>'1',
             'add_time'=>date('Ymd'),
             'content'=>$content,
             'title'=>$title
         );
        $res=M("tmember_msg_list")->add($data);
        if($res===false){
            return false;
        }
        return true;
    }
    public function gradeChange($map,$data){
        $mapcount=M()->table('tfb_mh_wfx_saler_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show1();// 分页显示输出
        $list =M()->table('tfb_mh_wfx_saler_trace a')
                ->join('twfx_saler c on a.saler_id=c.id')
                ->Field('c.name,c.phone_no,a.*')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->where($map)
                ->order('id desc')
                ->select();
        $gardList=array();
        foreach($list as $key=>$val){
            if($val['new_value']){
                $arr=explode(":",$val['new_value']);
                $val['new_value']=$arr[0];
            }
            $gardList[$key]=$val;
        }
        if(empty($gardList)){
            return false;
        }
        return $goodsInfo=array(
                'list'=>$gardList,
                'show'=>$show
        );
    }
    //================================
    /**
     * 添加/修改 商品的会员优惠
     */
    public function addByVipDiscount($options)
    {
        $where = array(
            'node_id' => $options['node_id'],
            'goods_id' => $options['goods_id'],
            'vip_type' => $options['vip_type']
            );
        $d_id = M()->table('tgoods_vip_discount')
            ->where($where)
            ->getField('id');
        if($d_id) {
            $result = M()->table('tgoods_vip_discount')->where(array('id'=>$d_id))->data($options)->save();
        } else {
            $result = M()->table('tgoods_vip_discount')->data($options)->add();
        }
        return $result;
    }

    /**
     * 删除 商品会员优惠
     */
    public function delByVipDiscount($id)
    {
        $result = M()->table('tgoods_vip_discount')->where(array('id'=>$id))->delete();

        return $result;
    }

    /**
     * 获取商品 对应的会员优惠
     */
    public function getByVipDiscount($node_id, $goods_id, $level='')
    {
        $where = array(
            'node_id'=>$node_id,
            'goods_id'=>$goods_id
            );
        if($level) {
            $where['vip_type'] = $level;
        }

        $list = M()->table('tgoods_vip_discount')
            ->where($where)
            ->select();
        return $list;
    }

    public function getBySalerInfo($phone, $node_id)
    {
        $result = M()->table("twfx_saler")
            ->where(array('phone_no'=>$phone, 'node_id'=>$node_id))
            ->find();

        return $result;
    }

    //获取金银牌的数量
    public function goldAndSilverCount($nodeId,$salerId,$level){
        $gold = M('twfx_saler')->where(array('parent_id' => $salerId, 'node_id' => $nodeId))->select();
        $count=array();
        if($gold){
            if($level==2){
                $where=array();
                foreach($gold as $key=>$val){
                    $where[]=$val['id'];
                }
                $silver = M('twfx_saler')->where(array('parent_id' => array('in',$where), 'node_id' => $nodeId))->count();
                $count['gold']=count($gold);
                $count['silver']=$silver;
            }elseif($level==3){
                $count['gold']=0;
                $count['silver']=count($gold);
            }
        }else{
            $count['gold']=0;
            $count['silver']=0;
        }
        return $count;
    }
    public function mHStop($level,$salerId,$nodeId,$user_id,$salerMoveInfo){
            $map=array(
                    'id'=>$salerId,
                    'node_id'=>$nodeId,
                    'level'=>$level
            );
            $salInfo=M("twfx_saler")->where($map)->find();
            if($salInfo){
                //将钻下面的金和银，转移直接挂在门店下
                $mapData=array(
                        'node_id'=>$nodeId,
                        'level'=>array('gt',$level),
                        "parent_path"=>array('like',"%".$salInfo['id']."%")
                );
                $goldSilver=M("twfx_saler")->where($mapData)->select();
                if($goldSilver){
                    foreach ($goldSilver as $key=>$val){
                        if($salerMoveInfo){
                            $val['parent_path']=$salerMoveInfo['parent_path'].','.$salerMoveInfo['id'];
                            $val['parent_id']=$salerMoveInfo['id'];
                        }else{
                            $val['parent_path']=str_replace(",".$salInfo['id'],"",$val['parent_path']);
                            $val['parent_id']=$salInfo['parent_id'];
                        }
                        $res=M("twfx_saler")->where(array('id'=>$val['id']))->save($val);
                        if($res===false){
                            return false;
                        }
                    }
                }
                //停用直接挂门店，
                 $pathArr=explode(',',$salInfo['parent_path']);
                $data=array(
                        'parent_id'=>$pathArr[1],
                        'parent_path'=>'0,'.$pathArr[1].",",
                        'level'=>4,
                        'status'=>'4'
                );
                //钻变成门店下的银
                $salStatus=M("twfx_saler")->where(array('id'=>$salerId))->save($data);
                if($salStatus===false){
                    return false;
                }
                if($level<4){
                    $new_level=4;
                    $dateGrade=array(
                            'saler_id'=>$salerId,
                            'trace_time'=>date('YmdHis'),
                            'change_type'=>1,
                            'old_value'=>$level.":".$salInfo['parent_id'],
                            'new_value'=>$new_level.":".$salInfo['parent_id'],
                            'user_id'=>$user_id,
                            'change_flag'=>1
                    );
                    $resStatus=$this->gradeChangeTrace($dateGrade);
                    if($resStatus===false){
                        return false;
                    }
                }
                return true;
            }
    }
    public function checkStatus($where,$data){
        $res=M('twfx_saler')->where($where)->data($data)->save();
        if (false ===$res) {
           return false;
        }
        return true;
    }
    public function editLog($data){
        $res=M('twfx_edit_log')->add($data);
        if (false ===$res) {
            return false;
        }
        return true;
    }
    public function downgrade($level,$salerId,$nodeId){
        $map=array(
                'id'=>$salerId,
                'node_id'=>$nodeId,
                'level'=>$level
        );
        $salInfo=M("twfx_saler")->where($map)->find();
        if($salInfo){
            //将钻下面的金和银，转移直接挂在门店下
            $mapData=array(
                    'node_id'=>$nodeId,
                    'level'=>array('gt',$level),
                    "parent_path"=>array('like',"%".$salInfo['id']."%")
            );
            $goldSilver=M("twfx_saler")->where($mapData)->select();
            if($goldSilver){
                foreach ($goldSilver as $key=>$val){
                    $val['parent_path']=str_replace(",".$salInfo['id'],"",$val['parent_path']);
                    $val['parent_id']=$salInfo['parent_id'];
                    $res=M("twfx_saler")->where(array('id'=>$val['id']))->save($val);
                    if($res===false){
                        return false;
                    }
                }
            }
            $pathArr=explode(',',$salInfo['parent_path']);
            if($level==2){
                $memberName="金牌";
                //钻降金，挂门店
                $downArr['parent_id']=$pathArr[1];
                $downArr['parent_path']='0,'.$pathArr[1].',';
            }else if($level==3){
                $memberName="银牌";
                //独立出来挂门店
                $downArr['parent_id']=$pathArr[1];
                $downArr['parent_path']='0,'.$pathArr[1].',';
                //金降银，挂门店或钻
//                if($pathArr[1] && $pathArr[2]){
//                    $upData['parent_id']=$pathArr[2];
//                    $upData['parent_path']='0,'.$pathArr[1].','.$pathArr[2].',';
//                }elseif($pathArr[1]){
//                    $upData['parent_id']=$pathArr[1];
//                    $upData['parent_path']='0,'.$pathArr[1].',';
//                }
            }
            $downArr['level']=$level+1;
            $salStatus=M("twfx_saler")->where(array('id'=>$salerId))->save($downArr);
            $content="您的会员帐号于".date("y年m月d日",time()).",降级为".$memberName."会员！并从原有团队中脱离。";
            $this->addMhMsg($salInfo['phone_no'],$nodeId,$this->title,$content);
            if($salStatus===false){
                return false;
            }
            return true;
        }
    }
    public function upgrade($level,$salerId,$nodeId,$user_id){
        $map=array(
                'id'=>$salerId,
                'node_id'=>$nodeId,
                'level'=>$level
        );
        $salInfo=M("twfx_saler")->where($map)->find();
        if($salInfo){
            //将钻下面的金和银，转移直接挂在门店下
            $mapData=array(
                    'node_id'=>$nodeId,
                    'level'=>array('gt',$level),
                    "parent_path"=>array('like',"%".$salInfo['id']."%")
            );
            $goldSilver=M("twfx_saler")->where($mapData)->select();
            if($goldSilver){
                foreach ($goldSilver as $key=>$val){
                    if($level==3){
                        //金升钻，银挂钻(原来的金)
//                      $val['parent_path']=str_replace(",".$salInfo['id'],"",$val['parent_path']);
                        $pathArr1=explode(',',$salInfo['parent_path']);
                        $val['parent_path']="0,".$pathArr1[1].','.$salInfo['id'].',';
                        $val['parent_id']=$salInfo['id'];
                        $res=M("twfx_saler")->where(array('id'=>$val['id']))->save($val);
                        if($res===false){
                            return false;
                        }
                    }
                }
            }
            $upData=array('level'=>$level-1);
            $pathArr=explode(',',$salInfo['parent_path']);
            if($level==3){
                $memberName="钻石";
                $upData['parent_id']=$pathArr[1];
                $upData['parent_path']='0,'.$pathArr[1].',';
            }else if($level==4){
                $memberName="金牌";
                //挂钻
                if($pathArr[1] && $pathArr[2] && $pathArr[3]){
                    $upData['parent_id']=$pathArr[2];
                    $upData['parent_path']='0,'.$pathArr[1].','.$pathArr[2].',';
                }
//                elseif($pathArr[1] && $pathArr[2]){
//                    //挂金
//                    $upData['parent_id']=$pathArr[1];
//                    $upData['parent_path']='0,'.$pathArr[1].',';
//                } else
                else{
                    $upData['parent_id']=$pathArr[1];
                    $upData['parent_path']='0,'.$pathArr[1].',';
                }
            }
            $content="您的会员帐号于".date("y年m月d日",time()).",升级为".$memberName."会员！现在您可以招募下级会员，建立自己的团队！";
            $this->addMhMsg($salInfo['phone_no'],$nodeId,$this->title,$content);
            $salStatus=M("twfx_saler")->where(array('id'=>$salerId))->save($upData);
            if($salStatus===false){
                return false;
            }
            /*添加升级记录*/
            $new_level=$level-1;
            $dateGrade=array(
                    'saler_id'=>$salerId,
                    'trace_time'=>date('YmdHis'),
                    'change_type'=>1,
                    'old_value'=>$level.":".$salInfo['parent_id'],
                    'new_value'=>$new_level.":".$salInfo['parent_id'],
                    'user_id'=>$user_id,
                    'change_flag'=>2
            );
            $resStatus=$this->gradeChangeTrace($dateGrade);
            if($resStatus===false){
                return false;
            }
            return true;
        }
    }
    public function getSalers($nodeId,$level){
        $map=array(
                'node_id'=>$nodeId,
                'level'=>array('LT',$level),
                'status'=>3
        );
        $list=M("twfx_saler")->where($map)->select();
        return $list;
    }
    public function getSalerInfoByPhone($nodeId,$phoneNo){
       $list= M("twfx_saler")->where(array('node_id'=>$nodeId,'phone_no'=>$phoneNo))->find();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function getSalerInfoById($nodeId,$id){
        $list= M("twfx_saler")->where(array('node_id'=>$nodeId,'id'=>$id))->find();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function checkSalerId($nodeId,$salerId){
        $list=M("twfx_saler")->where(array('node_id'=>$nodeId,'id'=>$salerId))->find();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function mHTransfer($salerInfo, $targetInfo){
        $data=array(
                'parent_id'=>$targetInfo['id'],
                'parent_path'=>$targetInfo['parent_path'].$targetInfo['id'].','
        );
        $res=M("twfx_saler")->where(array('id'=>$salerInfo['id']))->save($data);
        if($res===false){
            return false;
        }
        //查询当前机构的下级，全部转移到上级
        if($salerInfo){
            //将钻下面的金和银，转移直接挂在门店下
            $mapData=array(
                    'node_id'=>$salerInfo['node_id'],
                    'level'=>array('gt',$salerInfo['level']),
                    "parent_path"=>array('like',"%".$salerInfo['id']."%")
            );
            $goldSilver=M("twfx_saler")->where($mapData)->select();
            if($goldSilver){
                foreach ($goldSilver as $key=>$val){
                    $val['parent_path']=str_replace(",".$salerInfo['id'],"",$val['parent_path']);
                    $val['parent_id']=$salerInfo['parent_id'];
                    $res=M("twfx_saler")->where(array('id'=>$val['id']))->save($val);
                    if($res===false){
                        return false;
                    }
                }
            }
            return true;
        }
        return true;
    }
    public function mHFreeMove($salerInfo, $targetInfo){
        if($targetInfo['level']>=$salerInfo['level']){
            if($targetInfo['level']-$salerInfo['level']==0){
                //等级降级
                $data=array(
                        'parent_id'=>$targetInfo['id'],
                        'parent_path'=>$targetInfo['parent_path'].','.$targetInfo['id'].',',
                        'level'=>$salerInfo['level']+1
                );
            }else if($targetInfo['level']-$salerInfo['level']==1){
                //等级不变
                $data=array(
                        'parent_id'=>$targetInfo['parent_id'],
                        'parent_path'=>$targetInfo['parent_path']
                );
            }elseif($targetInfo['level']-$salerInfo['level']==2){
               //升级处理，升级为金
                $data=array(
                        'parent_id'=>$targetInfo['id'],
                        'parent_path'=>$targetInfo['parent_path'].','.$targetInfo['id'].',',
                        'level'=>$salerInfo['level']-1
                );
            }
        }else{

        }
        $res=M("twfx_saler")->where(array('id'=>$salerInfo['id']))->save($data);
        if($res===false){
            return false;
        }
        //查询当前机构的下级，全部转移到上级
        if($salerInfo){
            //将钻下面的金和银，转移直接挂在门店下
            $mapData=array(
                    'node_id'=>$salerInfo['node_id'],
                    'level'=>array('gt',$salerInfo['level']),
                    "parent_path"=>array('like',"%".$salerInfo['id']."%")
            );
            $goldSilver=M("twfx_saler")->where($mapData)->select();
            if($goldSilver){
                foreach ($goldSilver as $key=>$val){
                    $val['parent_path']=str_replace(",".$salerInfo['id'],"",$val['parent_path']);
                    $val['parent_id']=$salerInfo['parent_id'];
                    $res=M("twfx_saler")->where(array('id'=>$val['id']))->save($val);
                    if($res===false){
                        return false;
                    }
                }
            }
            return true;
        }
        return true;
    }
    public function gradeChangeTrace($data){
        $res=M("tfb_mh_wfx_saler_trace")->add($data);
        if($res===false){
            return false;
        }
        return true;
    }
    public function getTotalCount($map){
        $total=M("twfx_saler")
                ->where($map)
                ->Field("level,count(*) as total")
                ->group('level')
                ->select();
        return $total;
    }
    public function totalDownDetail($traceMonth){
        $list=M()->table("tfb_mh_wfx_trace a")
                ->join('twfx_saler b on a.saler_id=b.id')
                ->join('twfx_saler c on b.parent_id=c.id')
                ->Field('a.trace_month,b.name,b.phone_no,b.bank_name,b.bank_account,b.alipay_account,c.name as upname,SUM(a.amount) AS total')
                ->where(array('a.trace_month'=>$traceMonth))
                ->group('a.saler_id')
                ->select();
        if($list){
            return $list;
        }
        return false;
    }
    public function getAgencyExportData($nodeId, $filterStr) {
        if (strrchr($filterStr, 'sale_down') != false) {
            $filterStr = str_replace("sale_down", "parent_path as sale_down", $filterStr);
        }
        $whereMap['node_id'] = $nodeId; // nodeID
        $whereMap['role'] = 2; // 经销商
        $whereMap['level'] = array('GT','1');
        //$whereMap['status'] = 3;
        $saler = M('twfx_saler');
        $dataArr = $saler->field($filterStr)
                ->where($whereMap)
                ->select(); // 查询结果集
        foreach ($dataArr as $k => &$v) {
            if ($v['parent_path'] != '') {
                $v['parent_path'] = $v['parent_path'] . $v['id'];
                $map['parent_path'] = array('like', $v['parent_path'] . "%");
                $map['role'] = 2;
                $map['node_id'] = $nodeId;
                $map['level'] = array('GT',$v['level']);
                $upStore = $saler->where($map)->getField('name', true); // 下级经销商
                $str = implode(',', $upStore);
                $v['parent_down'] = $str;
                $v['parent_path'] = $v['parent_down'];
                if($v['parent_path']==''){
                    $v['parent_path']="无";
                }
                unset($v['parent_down']);
            }
            if ($v['parent_id'] != '') {
                if ($v['parent_id'] > 0) { // 上级经销商
                    $tempmap['id'] = $v['parent_id'];
                    $tempmap['node_id'] = $nodeId;
                    $tempmap['stauts'] = 3;
                    $v['parent_id'] = $saler->field('name')
                            ->where($tempmap)
                            ->getField('name');
                } else {
                    $v['parent_id'] = "无";
                }
            }
            if ($v['status'] == 1) { // 是否审核
                $v['status'] = "未审核";
            } elseif ($v['status'] == 2) {
                $v['status'] = "审核不通过";
            } elseif ($v['status'] == 3) {
                $v['status'] = "审核通过";
            } elseif ($v['status'] == 4) {
                $v['status'] = "停用";
            }
            if ($v['level'] == 1) { // 是否审核
                $v['level'] = "门店";
            } elseif ($v['level'] == 2) {
                $v['level'] = "钻石";
            } elseif ($v['level'] == 3) {
                $v['level'] = "金牌";
            } elseif ($v['level'] == 4) {
                $v['level'] = "银牌";
            }
            if ($v['add_time'] != '') { // 格式化时间
                $v['add_time'] = date("Y-m-d", strtotime($v['add_time']));
            }
        }
        return $dataArr;
    }
    public function selectParentPathById($id,$nodeId,$level){
        $map=array(
                "node_id"=>$nodeId,
                "parent_id"=>$id,
                'status'=>'3',
                'level'=>array('GT',$level)
        );
        $res=M("twfx_saler")->where($map)->select();
        if(!$res){
            return false;
        }
        return $res;
    }
    /*
     * 获取钻石和门店
     */
    public function getMemberByLevel($nodeId,$level){
        $map=array(
                'node_id'=>$nodeId,
                'status'=>'3',
                'level'=>array('ELT',$level)
        );
        $list=M("twfx_saler")->where($map)->select();
        return $list;
    }
    public function getByPhone_no($nodeId,$phone){
        $map=array(
                'node_id'=>$nodeId,
                'status'=>'3',
                'phone_no'=>$phone
        );
        $list=M("twfx_saler")->where($map)->find();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    public function mHstatBonus($node_id,
            $saler_id,
            $order_count,
            $amount,
            $bonus_amount){
        $date = date('Ymd');
        // 判断是否有商品是否有配置提成
        $where   = "node_id = '" . $node_id . "' and order_date = '" . $date . "' and saler_id =" . $saler_id;
        $daystat = M()->table('tfb_mhsaler_daystat')->where($where)->find();
        if (!$daystat) { // 新增
            $daystat['node_id']      = $node_id;
            $daystat['saler_id']     = $saler_id;
            $daystat['order_date']   = $date;
            $daystat['order_count']  = $order_count;
            $daystat['amount']       = $amount;
            $daystat['bonus_amount'] = $bonus_amount;
            $rs                      = M()->table('tfb_mhsaler_daystat')->add($daystat);
            // 添加成功
            if ($rs !== false) {
                return true;
            }
        }
        // 更新
        $rs = M('tfb_mhsaler_daystat')->where($where)->setInc('order_count', $order_count);
        if ($rs === false) {
            $this->_log("mHstatBonus find tfb_mhsaler_daystat update error " . M()->_sql());

            return false;
        }
        $rs = M('tfb_mhsaler_daystat')->where($where)->setInc('amount', $amount);
        if ($rs === false) {
            $this->_log("mHstatBonus find tfb_mhsaler_daystat update error " . M()->_sql());

            return false;
        }
        $rs = M('tfb_mhsaler_daystat')->where($where)->setInc('bonus_amount', $bonus_amount);
        if ($rs === false) {
            $this->_log("mHstatBonus find tfb_mhsaler_daystat update error " . M()->_sql());

            return false;
        }

        return true;
    }
    //美惠下发短信通知方法
    public function MhSendMsg($nodeId){
        $sendFlag=M("twfx_mh_config")->find();
        if($sendFlag['jx_notice_flag']==1){
            $map=array(
                    'node_id'=>$nodeId,
                    'status'=>'3'
            );
            $salerList=M("twfx_saler")->where($map)->select();
            $recruitmentReward=0;
            $teamAward=0;
            $rankAward=0;
            $time = getdate(strtotime("-1 month"));
            if($time['mon']<10){
                $traceMonth=$time['year']."0".$time['mon'];
            }else{
                $traceMonth=$time['year'].$time['mon'];
            }
            foreach($salerList as $key=>$val){
//            $sendMap=array(
//                    'node_id'=>$nodeId,
//                    'saler_id'=>$val['id'],
//                    'trace_month'=>$traceMonth
//            );
                $sendMap=array(
                        'node_id'=>$nodeId,
                        'saler_id'=>$val['id'],
                        'trace_month'=>201605
                );
                $rewardList=M("tfb_mh_wfx_trace")->where($sendMap)->select();
                if($rewardList){
                    foreach($rewardList as $key=>$reward){
                        //招募奖励
                        if($reward['trace_type']=='A'){
                            $recruitmentReward=$reward['amount'];
                        }elseif($reward['trace_type']=='B'){
                            $teamAward=$reward['amount'];
                        }elseif($reward['trace_type']=='C'){
                            $rankAward=$reward['amount'];
                        }
                    }
                    if($recruitmentReward>0 ||$teamAward>0 || $rankAward>0){
                        $total=$recruitmentReward+$teamAward+$rankAward;
                        $list=M("twfx_node_info")->where(array('node_id'=>$nodeId))->find();
                        $text=$val['name']."，".$time['year']."年".$time['mon']."月你获得了".$total."元奖励金额。包含招募奖励".$recruitmentReward."元;团队收益".$teamAward."元;排行奖励".$rankAward."元。访问".$list['shop_short_url'].".查看详情";
                        echo "<pre>";
                        echo $text;
                        echo "</pre>";
//                        $data=array(
//                            'node_id'=>$nodeId,
//                            'trans_date'=>$time['year'].$time['mon'],
//                            'name'=>$val['id'],
//                            'phone_no'=>$val['phone_no'],
//                             'bonus_amount'=>$total,
//                             'status'=>'0',
//                             'add_time'=>date('YmdHis'),
//                              'notes'=>$text,
//                               'short_url'=>$list['shop_short_url']
//                        );
//                        $res=M("twfx_notify")->add($data);
//                        $sendStatus=send_SMS($nodeId, $val['phone_no'], $text);
//                        if($sendStatus===false){
//                            M("twfx_notify")->where(array('id'=>$res,'node_id'=>$nodeId))->save(array('status'=>'2','send_time'=>date('YmdHis')));
//                            log_write("saler_id:".$val['id']."月份：".$traceMonth);
//                        }else{
//                            $res=M("twfx_notify")->where(array('id'=>$res,'node_id'=>$nodeId))->save(array('status'=>'1','send_time'=>date('YmdHis')));
//                            if($res===false){
//                                log_write("插入twfx_notify失败:".M()->getLastSql());
//                            }
//                            echo $text;
//                        }
                    }
                }
            }
            exit;
        }
    }
    public function getotherSetValue(){
        $map=array('param_name'=>'OTHER_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        if($res){
            return array(
                    'param_value'=>json_decode($res['param_value'],true));
        }
    }
    public function otherSetSave($dataArr){
        $saveData=array(
                'param_name'=>'OTHER_CONFIG',
                'param_value'=>json_encode($dataArr),
                'comment'=>'其他设置'
        );
        $map=array('param_name'=>'OTHER_CONFIG');
        $res=M("tfb_mh_wfx_config")->where($map)->find();
        if($res){
            $configStatus=M("tfb_mh_wfx_config")->where($map)->save($saveData);
        }else{
            $configStatus=M("tfb_mh_wfx_config")->add($saveData);
        }
        if($configStatus===false){
            return false;
        }
        return true;
    }
    /*
    * 美惠商品结算差价
    */
    public function getGoodsMemberPrice($orderId,$salerId,$nodeId){
        $level=M("twfx_saler")->where(array('node_id'=>$nodeId,id=>$salerId))->getField("level");
        if(!$level){
            log_write("查询不到会员等级！");
            return false;
        }
        $map=array(
                'c.order_id'=>$orderId,
                'a.vip_type'=>$level
        );
        $list=M()->table("ttg_order_info_ex c")
                ->join("tbatch_info b on b.id=c.b_id")
                ->join(" tgoods_vip_discount a on a.goods_id=b.goods_id")
                ->field("a.vip_type,a.vip_discount,b.goods_id,c.price,c.goods_num,c.receiver_type,c.order_id")
                ->where($map)
                ->select();
        log_write("YYYY".M()->getLastSql());
        $orderAmt=M("ttg_order_info")->where(array('order_id'=>$orderId))->getField('order_amt');
        if($orderAmt<=0){
            log_write("该笔订单实付金额为0！");
            return false;
        }
        if(!$list){
            log_write("该商品无会员价！");
            return false;
        }
        $memberAmt='';
        foreach($list as $key=>$val){
            if($val['receiver_type']==0){
                $memberAmt=$memberAmt+($val['price']-$val['vip_discount'])*$val['goods_num'];
            }else{
                $cuLevel=M()->table("ttg_order_info a")
                        ->join('twfx_saler b on b.phone_no=a.order_phone')
                        ->where(array('a.order_id'=>$val['order_id'],'b.node_id'=>$nodeId))->getField('level');
                log_write("获取下单用户的当前级别".M()->getLastSql());
                if(!$cuLevel){
                    $memberAmt=$memberAmt+($val['price']*$val['goods_num']);
                }else{
                    $vip_discount=M("tgoods_vip_discount")->where(array('goods_id'=>$val['goods_id'],'vip_type'=>$cuLevel))->getField('vip_discount');
                    log_write("获取下单用户的当前级别金额：".M()->getLastSql());
                    $memberAmt=$memberAmt+($val['price']-$vip_discount)*$val['goods_num'];
                    log_write("获取下单用户的当前级别金额：".$memberAmt);
                }
            }
        }
        if($orderAmt<=$memberAmt){
             log_write("实付金额小于或者等于会员结算金额！");
             return false;
        }
        //实付金额大于会员价，则有提成
        foreach($list as $key=>$val){
            if($val['receiver_type']==0) {
                $salerData = array(
                        'order_id' => $orderId,
                        'goods_id' => $val['goods_id'],
                        'goods_price' => $val['price'],
                        'saler_id' => $salerId,
                        'goods_num'=>$val['goods_num'],
                        'vip_discount'=>$val['vip_discount'],
                        'member_price' => $val['price'] - $val['vip_discount'],
                        'add_time' => date('YmdHis')
                );
                $res = M("tfb_mh_saler_order_trace")->add($salerData);
                if ($res === false) {
                    log_write("记录会员价差价流水失败！");
                    return false;
                }
            }
        }
//        记录商品流水成功
        $salerData=array(
                'order_id'=>$orderId,
                'saler_id'=>$salerId,
                'order_amt'=>$orderAmt,
                'price'=>$memberAmt,
                'commission_amount'=>$orderAmt-$memberAmt,
                'add_time'=>date('YmdHis')
        );
        $res=M("tfb_mh_saler_order")->add($salerData);
        if($res===false){
            log_write("记录会员价失败！");
            return false;
        }
    }
}