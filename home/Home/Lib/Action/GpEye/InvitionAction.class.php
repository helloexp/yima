<?php
class InvitionAction extends GpBaseAction
{
    
    function index()
    {   $list=D('GpInvite')->wethinfo();
        $this->assign('list',$list);
        $this->display('invite/index');
    }

    function changeCard()
    {

        $list=D('GpInvite')->wethinfo();
        if(!I('title')) $this->error('请设置页面标题');
        if(!I('content'))$this->error('请设置页面内容');
        $data['page_title']=I('title');
        $data['page_content']=I('content');
        $condition['id']=1;
        if($list){
        $result=D('GpInvite')->changecard($data,$condition);
            if($result===false) {$this->error('保存失败');}
            else {($this->success('保存成功'));}}

        else
        { $result=D('GpInvite')->addcard($data);
        if($result) $this->success('保存成功');
            else('保存失败');}


    }
    function preview()
    {
        $this->display('invite/preview');
    }

}