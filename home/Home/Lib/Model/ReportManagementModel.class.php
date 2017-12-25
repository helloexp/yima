<?php

class ReportManagementModel extends Model {
    protected $tableName = '__NONE__';
    public function getMemberInfo($map,$data){
        $mapcount=M('tfb_gansu_member')->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $list =M('tfb_gansu_member')
                ->where($map)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('id desc')
                ->select();
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function getReportName(){
        $list=M("tfb_gansu_report")->select();
        return $list;
    }
    public function reportSetValue($year){
        $list=M("tfb_gansu_company a")
                ->join("tfb_gansu_reportset b on a.code=b.company_id")
                ->where(array('b.year'=>$year))
                ->select();
        return $list;
    }
    public function getReportSetTotal($year){
        $list=M("tfb_gansu_company a")
                ->join("tfb_gansu_reportset b on a.code=b.company_id")
                ->where(array('b.year'=>$year))
                ->field('SUM(b.total_sales) AS total1,SUM(b.retail_sales) AS total2 ')
                ->select();
        return $list[0];
    }
    public function companyName(){
        $list=M("tfb_gansu_company")->select();
        if($list){
            $companyNameList=array();
            foreach($list as $key=>$val){
                $companyNameList[$val['code']]=$val['company_name'];
            }
            return $companyNameList;
        }
    }
    public function companyNameArr(){
        $list=M("tfb_gansu_company")->select();
        return $list;
    }
    /*
     * 删除会员，并删除会员关系
     */
    public function memberDel($id){
        $res=M("tfb_gansu_member")->where(array('id'=>$id))->delete();
        if($res===false){
            return false;
        }
            $res=M("tfb_gansu_memberauth")->where(array('member_id'=>$id))->delete();
        if($res===false){
            return false;
        }
        return true;
    }
    /*
     * 通过ID查询员工信息
     */
    public function getMemberOneById($id){
        $list=M("tfb_gansu_member")->where(array('id'=>$id))->find();
        if(empty($list)){
            return false;
        }
        return $list;
    }
    /*
    * 修改会员
    */
    public function memberEdit($data){
        $memberData=array(
            'custom_number'=>$data['custom_number'],
            'name'=>$data['name'],
            'mobile'=>$data['mobile'],
            'company_id'=>$data['company_id']
        );
        $res=M("tfb_gansu_member")->where(array('id'=>$data['id']))->save($memberData);
        if($res===false){
            return false;
        }
        return true;
    }
    /*
  * 修改会员
  */
    public function memberAdd($memberData){
        $res=M("tfb_gansu_member")->add($memberData);
        if($res===false){
            return false;
        }
        return true;
    }
    /*
     * 获取报表类型
     */
    public function getReportType(){
        $list=M("tfb_gansu_report")->select();
        return $list;
//        if($list){
//            $map=array();
//            foreach($list as $key=>$val){
//                $map[$val['id']]=$val['report_name'];
//            }
//        }
//        return $map;
    }
    /*
     * 校验是否为会员
     */
    public function isMember($memberId){
        $res=M("tfb_gansu_member")->where(array('id'=>$memberId))->find();
        if(!$res){
            return false;
        }
        return $res;
    }
    /*
     * 校验是否为会员
     */
    public function checkMemberByMobile($mobile){
        $res=M("tfb_gansu_member")->where(array('mobile'=>$mobile))->find();
        if($res){
            return false;
        }
        return true;
    }
    /*
 * 校验是否为会员
 */
    public function getMemberIdByMobile($mobile){
        $res=M("tfb_gansu_member")->where(array('mobile'=>$mobile))->find();
        if($res){
            return $res['id'];
        }
        return false;
    }
    public function getMemberInfoByMobile($mobile){
        $res=M("tfb_gansu_member")->where(array('mobile'=>$mobile))->find();
        if($res){
            return $res;
        }
        return false;
    }
    public function getMemberIdByOpenId($openid){
        $res=M("tfb_gansu_member")->where(array('openid'=>$openid))->find();
        if($res){
            return $res;
        }
        return false;
    }
    public function memberAuthSave($memberId,$data){
        $res=M("tfb_gansu_memberauth")->where(array('member_id'=>$memberId))->find();
        if($res){
            $data['last_update_time']=date('YmdHis');
            $updateFlag=M("tfb_gansu_memberauth")->where(array('member_id'=>$memberId))->save($data);
            if($updateFlag===false){
                return false;
            }
        }else{
            $data['member_id']=$memberId;
            $data['add_time']=date('YmdHis');
            $addFlag=M("tfb_gansu_memberauth")->add($data);
            if($addFlag===false){
                return false;
            }
        }
        return true;
    }
    public function getMemberAuthById($id){
        $res=M("tfb_gansu_memberauth")->where(array('member_id'=>$id))->find();
        if(!$res){
            return false;
        }
        return $res;
    }
    public function checkCompanyId($companyId){
        $res=M("tfb_gansu_company")->where(array('code'=>$companyId))->find();
        if(!$res){
            return false;
        }
        return true;
    }
    public function addCompanyReport($reportData){
        $res=M("tfb_gansu_day_company_report")->add($reportData);
        if($res===false){
            return false;
        }
        return $res;
    }
    public function addCompanyReportDown($addCompanyReportDown){
        $res=M("tfb_gansu_report_down")->add($addCompanyReportDown);
        if($res===false){
            return false;
        }
        return true;
    }
    public function getSalesReportImportTrace($map,$data){
        $mapcount=M('tfb_gansu_report_down')->where($map)->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $list =M('tfb_gansu_report_down')
                ->where($map)->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('id desc')
                ->select();
        return $goodsInfo=array(
                'list'=>$list,
                'show'=>$show
        );
    }
    public function downReport($id){
        $reportInfo=M("tfb_gansu_report_down")->where(array('id'=>$id))->find();
        if(!$reportInfo){
            return false;
        }
        if($reportInfo['report_id']){
            $map['id']=array('in',json_decode($reportInfo['report_id'],true));
            $list=M("tfb_gansu_day_company_report")->where($map)->select();
            if($list){
                return $list;
            }
        }
    }
    public function bindMemberOpenid($memberId,$openid){
         $res=M("tfb_gansu_member")->where(array('id'=>$memberId))->save(array('openid'=>$openid));
        if($res===false){
            return false;
        }
        return true;
    }
    public function checkReportId($reportId){
        $res=M("tfb_gansu_report")->where(array('report_id'=>$reportId))->find();
        if(!$res){
            return false;
        }
        return true;
    }
    public function companyList(){
        $list=M("tfb_gansu_company")->select();
        return $list;
    }
    public function addCompanySetByDate($companyId,$date,$reportId){
        $res=M("tfb_gansu_reportset")->where(array('company_id'=>$companyId,'year'=>$date,'report_id'=>$reportId))->find();
        if(!$res){
            $addRes=M("tfb_gansu_reportset")->add(array('company_id'=>$companyId,'year'=>$date,'report_id'=>$reportId));
            if($addRes===false){
                return false;
            }
        }
        return ture;
    }
    public function findCompanySetDate($reportId){
        $list=M("tfb_gansu_reportset")->where(array('report_id'=>$reportId))->select();
        if($list){
            return false;
        }
    }
    public function getMemberAuthTotalByOpenId($where,$year)
    {
        $list = M()->table("tfb_gansu_member a")
                ->join("tfb_gansu_memberauth b on a.id=b.member_id")
                ->where($where)
                ->Field('b.company_id')
                ->find();
        if (!$list) {
            return false;
        }
        $jsonArr=json_decode($list['company_id'],true);
        if(empty($jsonArr)){
            return false;
        }
        $map=array(
                'company_id'=>array('in',json_decode($list['company_id'],true)),
        );
        $map['year']=$year;
        $list=M("tfb_gansu_reportset")
                ->where($map)
                ->field('SUM(total_sales) AS total1,SUM(retail_sales) AS total2 ')
                ->select();
        return $list[0];
    }
    public function getReportListByOpenId($where,$time){
        $list=M()->table("tfb_gansu_member a")
                  ->join("tfb_gansu_memberauth b on a.id=b.member_id")
                  ->where($where)
                   ->Field('b.company_id')
                  ->find();
        if(!$list){
            return false;
        }
        $jsonArr=json_decode($list['company_id'],true);
        if(empty($jsonArr)){
            return false;
        }
        $map=array(
                'd.company_id'=>array('in',json_decode($list['company_id'],true)),
        );
        $map['d.date']=str_replace("-","",$time);
        $totalInfo=M()->table("tfb_gansu_day_company_item d")
            ->join('tfb_gansu_company s on s.code=d.company_id')
            ->where($map)
            ->Field('d.*')
            ->order('s.`order_sort`')
            ->select();
        return $totalInfo;
    }
    public function addCompanyItems($reportData){
        //插入导入数据
        $map=array(
                'company_id'=>$reportData['company_id'],
                'date'=>$reportData['date']
        );
        $itemInfo=M("tfb_gansu_day_company_item")->where($map)->find();
        if($itemInfo){
            $status=M("tfb_gansu_day_company_item")->where($map)->save($reportData);
        }else{
            $status=M("tfb_gansu_day_company_item")->add($reportData);
        }
        if($status===false){
            return false;
        }
        return true;
    }
    public function reportSetSave($companyId,$totalSales,$retailSales,$year,$reportId){
        $data=array(
            'total_sales'=>$totalSales,
            'retail_sales'=>$retailSales,
            'report_id'=>$reportId
        );
        $res=M("tfb_gansu_reportset")->where(array('company_id'=>$companyId,'year'=>$year))->find();
        if($res){
            $status=M("tfb_gansu_reportset")->where(array('company_id'=>$companyId,'year'=>$year))->save($data);
        }else{
            $data['company_id']=$companyId;
            $data['year']=$year;
            $status=M("tfb_gansu_reportset")->add($data);
        }
        if($status===false){
            return false;
        }
        return true;
    }
    public function WhiteList($phone)
    {
        $res=M('tfb_phone')->where(array('batch_id'=>C('gssy.batch_id'),'mobile'=>$phone))->find();
        if($res)
            return true;
        else return false;
    }
}