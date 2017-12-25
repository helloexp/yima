<?php

class FbMyQrCodeModel extends BaseModel
{
    protected $tableName="tfb_myqrcode";

    /**
     * 获取我的二维码信息
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function getMyQrCodeByWhere($where)
    {
        if(empty($where['node_id'])
        || empty($where['openid'])){
            return false;;
        }
        $map=array(
            'q.node_id'=>$where['node_id'],
            'q.openid'=>$where['openid'],
            's.apply_time'=>array('gt',strtotime("-721 hours")),//有效期最长 30天。提前小时
            );
        $info=$this->alias('q')
                   ->field('q.code_id,s.*,s.id as code_id')
                   ->join('tfb_myqrcode_trace s on s.id=q.code_id')
                   ->where($map)
                   ->find();
        if($info){
            if($info['media_updated'] < strtotime('-73 hours')){
                //重置过期media_id
                $info['media_id']=$this->getMedioIdByCodeId($info['code_id']);
            }
        }
        return $info;
    }

    /**
     * 通过 code_id 获取MedioId
     * @param  [type] $code_id [description]
     * @param  [type] $node_id [description]
     * @return [type]          [description]
     */
    public function getMedioIdByCodeId($code_id,$node_id)
    {
        if(empty($code_id)
        || empty($node_id)){
            return false;
        }
        $where=array(
            'id'=>$code_id,
            );
        $model=M('tfb_myqrcode_trace');
        $info=$model->where($where)->field('media_updated,media_id,img_info,scene_id,apply_time')->find();
        if(empty($info)){
            return false;
        }
        $media_id=$info['media_id'];
        //三天的有效期 
        if($info['media_updated'] < strtotime('-73 hours')){
            $img_info=$info['img_info'];
            if($img_info){
                //获取accessToken
                $serv=D('FbLiaoNing','Service');
                $wx=D('FbLiaoNing','Service')->setWeiXinServByNodeId($node_id);
                //图片处理 TODO ::
                $path=C('Upload')."lnsy/";
                if(!is_dir($path)){
                    if (! mkdir($path, 0777, true)) {
                        return false;
                    } 
                }
                $temp_img=$path.md5(uniqid(microtime(true))).".jpg";
                $res=$serv->createCodeByString($info,$temp_img);
                if(!$res){
                    @unlink($temp_img);
                    return false;
                }
                //上传图片
                $temp_img=get_upload_url($temp_img);
                $media_id=$wx->uploadMediaFile2($temp_img);
                @unlink($temp_img);
                if($media_id){
                    $save=array(
                    'media_id'=>$media_id,
                    'media_updated'=>time(),
                    );
                    //更新 media_id
                    $res=$model->where($where)->save($save);
                    if($res == false){
                        $media_id = false;
                    }

                }
            }else{
                $media_id=false;
            }
        }
        return $media_id;
    }

    /**
     * 申请二维码
     * @param $info['openid'] 微信openid  $info['node_id'] 机构号  $info['phone'] 手机号
     */
    public function applyMyQrCode($info)
    {
        if(empty($info['openid'])
        || empty($info['node_id']))
        {
            $this->error="非常抱歉，重要参数丢失";
            return false;
        }
        if(!check_str($info['phone'],array('type'=>'moblie'))){
            $this->error="非常抱歉，请输入正确的手机号";
            return false;
        }
        $node_id=$info['node_id'];
        $openid=$info['openid'];
        $phone=$info['phone'];
        $where=array(
            'openid'=>$openid,
            'node_id'=>$node_id,
            );
        //开启事物
        $this->startTrans();    
        $model=M('tfb_myqrcode_trace');
        $serv=D('FbLiaoNing','Service');
        $old_info=$this->where($where)->field('id,code_id')->find();
        $id=$old_info['id'];

        if(empty($old_info)){
            //自动添加
            $data=$where;
            $data['add_time']=date('YmdHis');
            $res=$this->add($data);
            if($res == false){
                $serv->_log("添加二维码错误",$data);
                $this->error="非常抱歉，系统出错，请稍后再试";
                $this->rollback();
                return false;
            }
            $id=$res;
        }else{
            //校验已有的二维码是否过期
            if($old_info['code_id'] > 0){
                $where=array(
                    'id'=>$old_info['code_id'],
                    'apply_time'=>array('gt',strtotime("-721 hours")),//有效期最长 30天。提前小时
                    );
                $check=$model->where($where)->count('id');
                if($check){
                    $serv->_log("二维码未过期",$where);
                    $this->error="非常抱歉，您已经申请过推广二维码";
                    $this->rollback();
                    return false;
                }
            }
        }

        //获取 scene_id 
        $scene_id= D('TweixinInfo')->getSceneId($node_id);
        //添加渠道, sns_type 为 99 
        $channel_data['name'] =str_repeat(0, (8-strlen($scene_id))).$scene_id;
        $channel_data['node_id'] = $node_id;
        $channel_data['add_time'] = date('YmdHis');
        $channel_data['status'] = '1';
        $channel_data['type'] = '8';
        $channel_data['sns_type'] = '81';
        $channel_id = M('tchannel')->add($channel_data);
        if($channel_id == false){
            $serv->_log("添加渠道失败",$channel_data);
            $this->error="非常抱歉，系统出错，请稍后再试";
            $this->rollback();
            return false;
        }
        $resp=array(
            'node_id'=>$node_id,
            'scene_id'=>$scene_id,
            );
        //创建临时二维码 
        $img_info=$serv->createQrcodeImg($resp);
        if(empty($img_info)){
            $serv->_log("创建二维码失败",$resp);
            $this->error="非常抱歉，创建二维码失败";
            $this->rollback();
            return false;
        }

        $data = array(
                'scene_id' => $scene_id, 
                'img_info' => $img_info, 
                'channel_id' => $channel_id, 
                'add_time' => date('YmdHis'), 
                'node_id' => $node_id);
        $qr_id = M('twx_qrchannel')->add($data);
         if($qr_id == false){
            $serv->_log("添加场景码数据失败",$data);
            $this->error="非常抱歉，系统错误，请稍后再试";
            $this->rollback();
            return false;
        }
        //添加二维码数据
        $trace_data=array(
            'qr_id'=>$qr_id,
            'phone'=>$phone,
            'apply_time'=>time(),
            'scene_id'=>$scene_id,
            'img_info'=>$img_info,
            'media_updated'=>0,
            'mq_id'=>$id,
            );
        $code_id=$model->add($trace_data);
        if($code_id == false){
            $serv->_log("添加二维码数据失败",$resp);
            $this->error="非常抱歉，系统错误，请稍后再试";
            $this->rollback();
            return false;
        }
        $res=$this->where(array('id'=>$id))->save(array(
            'code_id'=>$code_id,
            'apply_total'=>array("exp","apply_total + 1"),
            ));
        //设置medioid
        $media_id=$this->getMedioIdByCodeId($code_id,$node_id);
        if(empty($media_id)){
            $serv->_log("获取MedioId失败",$resp);
            $this->error="非常抱歉，系统错误，请稍后再试";
            $this->rollback();
            return false;
        }
        $this->commit();
        //发送消息
        $wx_serv=$serv->setWeiXinServByNodeId($node_id);
        $resp=array(
            'touser'=>$openid,
            'msgtype'=>'image',
            'image'=>array(
                    'media_id'=>$media_id,
                ),
            );
        $res=$wx_serv->sendMsg($resp);
        if($res == false){

        }
        return ture;
    }



}