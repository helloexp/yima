<?php

/*北京光平技师model*/
class GpTechnicianModel extends GpBaseModel
{
    protected $tableName = 'tfb_gp_technician';
    public $_map = array();
    public $_id=array();
    public function _initialize()
    {   parent::_initialize();
        if ($this->limit) {
            $this->_map = array('a.merchant_id' => $this->merchant_id);
            $this->_id=array('id' =>$this->merchant_id);

        }
    }
    public function technicianInfo($map,$data)
    {
        if(empty($data)){
            $list = M()
            ->table("tfb_gp_technician a")
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_name')
            ->where($map)
            ->select();
            $show='';
        }else{
            $mapcount=M('tfb_gp_technician a')
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_name')
            ->where($map)
            ->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $list = M()
            ->table("tfb_gp_technician a")
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_name')
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }
        return $technicianList=array(
            'list'=>$list,
            'show'=>$show
        );}


    public function technicianInfoList($map,$data)
    {   $where=[];
        if(count($map)>0)
        {
            $where=array_merge($this->_map, $map);
        }
        else{
            $where=$this->_map;
        }
        $mapcount=M('tfb_gp_technician a')
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_short_name')
            ->where($where)
            ->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $list = M()
            ->table("tfb_gp_technician a")
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_short_name')
            ->where($where)
            ->order('a.id')
            ->limit($Page->firstRow . ',' . $Page->listRows)->select();
        return $technicianList=array(
            'list'=>$list,
            'show'=>$show
        );}
            
    public function downloadTech($map)
{   $where=[];
    if(count($map)>0)
    {
        $where=array_merge($this->_map, $map);
    }
    else{
        $where=$this->_map;
    }
    return $list = M()
    ->table("tfb_gp_technician a")
    ->join('tfb_gp_merchant b on a.merchant_id=b.id')
    ->field('a.*,b.store_short_name')
    ->where($where)->order('a.id')->select();
}

    public function getTechnicianInfo($map)
    {
        $info = M()
            ->table('tfb_gp_technician a')
            ->join('tfb_gp_merchant b on a.merchant_id=b.id')
            ->field('a.*,b.store_short_name')
            ->where($map)->find();
        return $info;
    }

    public function changeTechnicianStatus($data)
    {   if($data['status']==2){
    $userid=M('tfb_gp_technician')->field('user_id')->where("id=$data[id]")->find();
        if($userid)
        {
            $req_array = array(
                'UserStatusReq' => array(
                    'UserId' => $userid['user_id'],
                    'Status' => 1, ), );
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestSsoUserStatus($req_array);
            $ret_msg = $reqResult['UserStatusRes']['Status'];
            if (!$reqResult || ($ret_msg['StatusCode'] != '0000' &&
                    $ret_msg['StatusCode'] != '0001')) {
                $this->error("更新失败:{$ret_msg['StatusText']}");
            }
            M('tuser_info')->where("user_id={$userid['user_id']}")->save(array('status' => 1));
           
        }

    }

        $res = M('tfb_gp_technician a')->save($data);


        return $res;
    }

    public function addTechnician($data)
    {
        $res = M('tfb_gp_technician')->add($data);

        return $res;
    }

    public function editTechnician($data)
    {
        $res = M('tfb_gp_technician a')->save($data);

        return $res;
    }

    public function getStoreList()
    {
        $map['status']='0';
        $list = M()->table('tfb_gp_merchant')->where(array_merge($this->_id, $map))->getField('id,store_short_name');
        return $list;
    }
    public function getOptionList($map)
    {
        $technician = M('tfb_gp_technician')
            ->where($map)
            ->getField('id,name');

        return $technician;
    }
    public function getTechnicianIds($map){
        $technician = M('tfb_gp_technician')
            ->where($map)
            ->getField('GROUP_CONCAT(id)');

        return $technician;
    }
}
