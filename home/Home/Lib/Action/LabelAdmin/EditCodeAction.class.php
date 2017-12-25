<?php
// 设置二维码
class EditCodeAction extends BaseAction {

    public $id;
    
    // 二维码设置
    public function index() {
        $id = I('get.id');
        $type = I('get.type');
        //我的渠道
        if($type == 'a'){
            $model = M('tbatch_channel');
            list($row,$is_img) =  $this->MyChannelImage($id,$model);
            $this->assign('type', 'a');
        //二维码渠道
        } else if($type == 'b'){
            $model = M('tchannel');
            list($row,$is_img) =  $this->MyChannelImage($id,$model);
            $this->assign('type', 'b');
        } else {
            $this->error('异常链接');
        }
        if ($row['logo_img'] == '') {
            $this->assign('headLogo', get_upload_url($nodeLogo['head_photo']));
            $this->assign('originHeadLogo', $nodeLogo['head_photo']);
        } else {
            $this->assign('headLogo', get_upload_url($row['logo_img']));
            $this->assign('originHeadLogo', $row['logo_img']);
        }
//        var_dump($row);

        $this->assign('is_img', 1);
        $this->assign('row', $row);
        $this->assign('id', $id);
        $this->display();
    }

    public function MyChannelImage($id,$model)
    {


        $row   = $model->where(
                array(
                        'node_id' => array(
                                'exp',
                                "in (" . $this->nodeIn() . ")"
                        ),
                        'id'      => $id
                )
        )->find();
        if (!$row) {
            $this->error('错误参数！');
        }
        // 机构信息 LOGO
        $nodeInfoModel = M('TnodeInfo');
        $nodeLogo      = $nodeInfoModel->where(
                array(
                        'node_id' => $this->nodeId
                )
        )->field('head_photo')->find();
        //        $type = I('type');
        //        if ($type == '3') {
        //            $this->assign('type', $type);
        //        }

        $is_img = 0;
        if (isset($row['logo_img']) && $row['logo_img'] == 1) {
            $is_img = 1;
        }
        return array( $row, $is_img );
    }


    public function aiPai() {
        $id = I('id');
        $model = M('tchannel');
        $row = $model->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->find();
        if (! $row)
            $this->error('错误参数！');
        
        $type = I('type');
        $this->assign('id', $id);
        $this->assign('row', $row);
        $this->display(); // 输出模板
    }

    public function SubmitAiPai() {
        if ($this->isPost()) {
            $id = I('id');
            $this->id = $id;
            $qr_type = I('qr_type');
            
            if ($qr_type == '1') {
                $color = I('color');
                $size = I('size');
                $is_img = I('is_img');
                $resp_log_img = I('resp_log_img');
                $map = array(
                    'qr_color' => $color, 
                    'qr_size' => $size, 
                    'logo_img' => $is_img == '1' ? $resp_log_img : '', 
                    'qr_type' => '1');
            } else {
                $map = array(
                    'qr_type' => '2');
            }
            
            $model = M('tchannel');
            $row = $model->where(
                array(
                    'node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"), 
                    'id' => $id))
                ->save($map);
            if ($row === false)
                $this->resp_msg('添加失败！', '1');
            else
                $this->resp_msg('设置成功!');
        }
    }

    public function Submit() {

        $type  = I('post.type','','trim');
        if($type == 'a'){
            $model = M('tbatch_channel');
        } else if($type == 'b'){
            $model = M('tchannel');
        }

        $id = I('id');
        $this->id = $id;
        $color = I('color');
        $size = I('size');
        
        // 默认为 800 * 800 同 4 参照页面
        if ($size == 1) {
            $size = 4;
        }
        
        $is_img = I('is_img');
        $resp_log_img = I('resp_log_img');
        
        if ($is_img == 1) {
            $logo_img = $resp_log_img;
        } else {
            $logo_img = '';
        }
        
        $map = array(
            'qr_color' => $color, 
            'qr_size' => $size, 
            'logo_img' => $logo_img);
        $row = $model->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->save($map);
        if ($row === false) {
            $this->resp_msg('添加失败！', '1');
        } else {
            $this->resp_msg('设置成功!');
        }
    }

    public function resp_msg($msg, $err = '') {
        if ($err != '') {
            $jumpUrl = U('LabelAdmin/EditCode/index', 
                array(
                    'id' => $this->id));
            $this->assign('error', $err);
        } else {
            $jumpUrl = U('LabelAdmin/EditCode/index', 
                array(
                    'id' => $this->id));
        }
        
        $this->assign('jumpUrl', $jumpUrl);
        $this->assign('message', $msg);
        $this->display('RespMsg');
    }
}