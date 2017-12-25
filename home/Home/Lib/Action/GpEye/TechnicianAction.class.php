<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class TechnicianAction extends GpBaseAction
{
    public function _initialize()
    {
        parent::_initialize();


    }

    public function index()
    {
        $data=I('request','');
        $id=I('id','');
        $name=trim(I("name",''));
        $store=I("storeid",'');
        $status=I("status");
    if($id)
        {
            $map['a.id']=$id;
        }
     if($name)
     {

         $map['a.name']=['like', "%{$name}%"];

     }
      if($store)
      {
          $map['a.merchant_id']=$store;
      }
      if($status==='0') $map['a.status']=0;
      else if($status)
      {

          $map['a.status']=$status;
      }
        $statuslist=['正常','待审核','已下岗','已拒绝'];
        $result=D('GpTechnician')->technicianInfoList($map,$data);
        $storeList=D('GpTechnician')->getStoreList();
        $this->assign('technicianlist',isset($result['list']) ? $result['list'] : []);
        $this->assign('page',isset($result['show']) ? $result['show'] : '');
        $this->assign('storelist',$storeList);
        $this->assign('statuslist',$statuslist);
        $this->assign('isadmin',$this->isSuperAdmin);
        $this->display('Technician/index');



    }

    public function editTechnician()
    {
        $id=I('id','');
        if(!$id)
            $this->error('参数错误');
        if($this->isPost()){
            $technicianImg       = I("img");
            $name      = I("name");
            $mobile          = I("mobile");
            $cardNo  = I("cardid");
            $storeId=I("merchant_id");
            $memo=I("memo");
            if (empty($technicianImg)) {
                $this->error('无图片');
            }
            if (empty($name)) {
                $this->error('请输入技师姓名');
            }
            if (mb_strlen($name, 'utf8') > 10) {
                $this->error('技师姓名不得大于10个字');
            }
            if (empty($mobile)) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/", $mobile) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }
            if (empty($cardNo)) {
                $this->error('请输入身份证号');
            } else {
                // 正则验证手机号
                if (preg_match("/^\d{15}$|^\d{17}([0-9]|X)$/",$cardNo)!=1) {
                    $this->error('请输入正确的身份证号');
                }
            }
            if (empty($storeId)) {
                $this->error('参数错误');
            }
            $data['id']=$id;
            $data['name']=$name;
            $data['mobile']=$mobile;
            $data['card_no']=$cardNo;
            $data['photo']=$technicianImg;
            $data['merchant_id']=$storeId;
            $data['memo']=$memo;
            $result=M('tfb_gp_technician')->save($data);
            $url=U('GpEye/Technician/index');
                if($result){
                $this->ajaxReturn(array('status'=>1,'info'=>'修改成功','url'=>$url));
            }else{
                $this->ajaxReturn(array('status'=>0,'info'=>'修改失败'));
            }
        }
        $info=D('GpTechnician')->getTechnicianInfo(array('a.id'=>$id));
        $storeList=D('GpTechnician')->getStoreList();
        $this->assign('storelist',$storeList);
        $this->assign('info',$info);
        $this->assign('id',$id);
        $this->display('Technician/edit');

    }
    public function addTechnician()
    {
        if($this->isPost())
        {
            $technicianImg       = I("img");
            $name      = I("name");
            $mobile          = I("mobile");
            $cardNo  = I("cardid");
            $storeId=I("storeid");
            $memo=I("memo");

            if (empty($technicianImg)) {
                $this->error('无图片');
            }
            if (empty($name)) {
                $this->error('请输入技师姓名');
            }
            if (mb_strlen($name, 'utf8') > 10) {
                $this->error('技师姓名不得大于10个字');
            }
            if (empty($mobile)) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/", $mobile) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }
            if (empty($cardNo)) {
                $this->error('请输入身份证号');
            } else {
                // 正则验证身份证号
                if (preg_match("/^\d{15}$|^\d{17}([0-9]|X)$/",$cardNo)!=1) {
                    $this->error('请输入正确的身份证号');
                }
            }
            if (empty($storeId)) {
                $this->error('参数错误');
            }

            $data['name']=$name;
            $data['mobile']=$mobile;
            $data['card_no']=$cardNo;
            $data['photo']=$technicianImg;
            $data['merchant_id']=$storeId;
            $data['memo']=$memo;
            $data['add_time']=date('YmdHis');
            $data['status']=1;
            $result=D('GpTechnician')->addTechnician($data);
            $url=U('GpEye/Technician/index');

            if($result){
                $this->ajaxReturn(array('status'=>1,'info'=>'添加成功','url'=>$url));
            }else{
                $this->ajaxReturn(array('status'=>0,'info'=>'添加失败'));
        }}
        $storeList=D('GpTechnician')->getStoreList();
        $this->assign('storelist',$storeList);
        $this->display('Technician/add');
}

        public function technicianChangeStatus(){

        $technicianId=I('id','');
        $status=I('status','');
        $data['id']=$technicianId;
        $data['status']=$status;
        if(!$technicianId){
            $this->error("缺少必要的参数！");
        }
        $res=D('GpTechnician')->changeTechnicianStatus($data);
        if($res===false){$this->error("更新失败！");

        }   $this->success("更新成功！");


    }

        public function downLoadTechnician()
        {
            $id=I('name','');
            $name=trim(I("name",''));
            $store=I("storeid",'');
            $status=I("status");
            if($id)
            {
                $map['a.id']=$id;
            }
            if($name)
            {

                $map['a.name']=['like', "%{$name}%"];

            }
            if($store)
            {
                $map['a.store']=$store;
            }
            if($status==='0') $map['a.status']=0;
            else if($status)
            {

                $map['a.status']=$status;
            }

            $list=D('GpTechnician')->downloadTech($map);
            if ( ! $list) {
                $this->error('未查询到记录！');
            }
            foreach($list as $k=>$v)
                foreach($v as $key=>$value)
                {if($key=='status')
                { if($value==0)
                    $list[$k][$key]='正常';
                else if($value==1)
                    $list[$k][$key]='待审核';
                else if($value==2)
                    $list[$k][$key]='已下岗';
                else if($value==3)
                    $list[$k][$key]='已拒绝';
                }
                else if($key='memo')
                {
                    $list[$k][$key]=str_replace(",","，",$list[$k][$key]);
                    $list[$k][$key]= str_replace(array("\r\n", "\r", "\n"), "",$list[$k][$key]);
                }
                }



            $fileName = date('Y-m-d') . '-技师列表.csv';
            $fileName = iconv('utf-8', 'gb2312', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $cj_title = "技师id,添加时间,技师姓名,所属小店,手机号码,身份证号,状态,评语内容\r\n";
            $cj_title = iconv('utf-8', 'gbk', $cj_title);
            echo $cj_title;
            foreach($list as $v)
            {
                $line = $v['id'] . ',' . dateformat($v['add_time'],'Y-m-d') . ',' . $v['name'] . ',' . $v['store_short_name'] . ',' . $v['mobile'] . ',' ."'". $v['card_no'] . ',' . $v['status'] . ',' . $v['memo']."\r\n";
                $line = iconv('utf-8', 'gbk', $line);
                echo $line;}




        }




}