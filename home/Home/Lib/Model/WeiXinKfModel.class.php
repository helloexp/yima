<?php

/**
 * 微信多客服相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/11/17
 */
class WeiXinKfModel extends BaseModel {

    protected $tableName = 'twx_kfaccount';

    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('node_id', 'require', '机构号不能空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('nickname', 'require', '请输入客服昵称', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('tag_id', 'require', '请选择客服标签', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );


    /**
     * 添加客服
     * @param [type] $info [description]
     */
    public function addKf($info)
    {
      $_validate=array(
        array('account', 'require', '请输入客服账号', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
       // array("account","unique","客服账号已存在",self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('password', 'require', '请输入客服登入密码', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('password', 'repass', '两次密码不一致！', self::MUST_VALIDATE, 'confirm',self::MODEL_BOTH),
        );
      $this->_validate=array_merge($this->_validate,$_validate);
      $data=$this->create($info);
      if(!$data){
        return false;
      }

      $check_where=array(
        "node_id"=>$data['node_id'],
        "account"=>$data['account'],
        );
      $check_account=$this->where($check_where)->count('id');
      if($check_account){
        $this->error="该客服账号不可用";
        return false;
      }
      $data['password']=md5($data['password']);
      $data['add_time']=date("YmdHis");
      $this->startTrans();
      $res=$this->add($data);
      if(!$res){
        $this->rollback();
        $this->error="添加客服失败";
        $this->_log("添加客服失败",$data);
        return false;
      }
      $kf=D('WeiXinKf','Service')->setAppIdByNodeId($info["node_id"]);
      if($kf->erron){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info['node_id']);
        return false;
      }
      $res=$kf->KfAdd($data['account'],$data['nickname'],$data['password']);
      if(!$res){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$data);
        return false;
      }
      $this->commit();
      return true;
    }

    /**
     * 改变客服昵称
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function edit($info)
    {
      $data=$this->create($info);
      if(!$data){
        return false;
      }
      if(!$info['id']){
        $this->error="参数错误";
        return false;
      }

      $where=array(
        "node_id"=>$data["node_id"],
        "id"=>$data['id'],
        "status"=>0,
        );
      $field=array_keys($data);
      $field[]='password';
      $field[]='account';
      $old_info=$this->field($field)->where($where)->find();
      //无更数据
      $check=array_diff($data, $old_info);
      if(!$check){
        return true;
      }
      $check['update_time']=date('YmdHis');
      $this->startTrans();
      $res=$this->where($where)->save($check);
      if(!$res){
        $this->rollback();
        $this->error="编辑客服昵称失败";
        $this->_log($this->error,$data);
        return false;
      }

      //更新昵称
      if($check['nickname']){
        $kf=D('WeiXinKf','Service')->setAppIdByNodeId($info["node_id"]);
        if($kf->erron){
          $this->rollback();
          $this->error=$kf->getError();
          $this->_log(var_export($this->error,true),$info);
          return false;
        }
        $res=$kf->KfEdit($old_info['account'],$check['nickname'],$old_info['password']);
        if(!$res){
          $this->rollback();
          $this->error=$kf->getError();
          $this->_log(var_export($this->error,true),$info);
          return false;
        }
      }

      $this->commit();
      return true;
    }

     /**
     * 改变客服密码
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function editPass($info)
    {

      $this->_validate=array(
        array('password', 'require', '请输入客服登入密码', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('repass', 'password', '两次密码不一致！', self::MUST_VALIDATE, 'confirm',self::MODEL_BOTH),
        );
      $data=$this->create($info);
      if(!$data){
        return false;
      }

      if(!$info['id']){
        $this->error="参数错误";
        return false;
      }
      $where=array(
        "node_id"=>$info["node_id"],
        "id"=>$info['id'],
        "status"=>0,
        );
      $old_info=$this->field("nickname,account,password")->where($where)->find();
      $password=md5($info['password']);
      if($password == $old_info["password"]){
          return true;
      }
      $this->startTrans();
      $res=$this->where($where)->save(array('password'=>$password,"update_time"=>date('YmdHis')));
      if(!$res){
        $this->rollback();
        $this->error="修改客服密码失败";
        $this->_log($this->error,$info);
        return false;
      }
      $kf=D('WeiXinKf','Service')->setAppIdByNodeId($info["node_id"]);
      if($kf->erron){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $res=$kf->KfEdit($old_info['account'],$old_info['nickname'],$password);
      if(!$res){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $this->commit();
      return true;
    }

    /**
     * 上传客服头像
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function uploadAvatar($info)
    {
      if(!file_exists($info['avatar'])){
        $this->error="图片不存在";
        return false;
      }
      if(!$info['id']){
        $this->error="参数错误";
        return false;
      }
      if(!$info['node_id']){
        $this->error="机构号不能空";
        return false;
      }
      $where=array(
        "node_id"=>$info["node_id"],
        "id"=>$info['id'],
        "status"=>0,
        );
      $old_info=$this->field("account,avatar")->where($where)->find();
      if($info['avatar'] == $old_info['account']){
          return true;
      }
      $this->startTrans();
      $res=$this->where($where)->save(array('avatar'=>$info['avatar'],"update_time"=>date('YmdHis')));
      if(!$res){
        $this->rollback();
        $this->error="上传客服头像失败";
        $this->_log($this->error,$info);
        return false;
      }
      $kf=D('WeiXinKf','Service')->setAppIdByNodeId($info["node_id"]);
      if($kf->erron){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $res=$kf->KfAvatar($old_info['account'],$info['avatar']);
      if(!$res){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $this->commit();
      return true;
    }

    /**
     * 删除客服
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function delKf($info)
    {
      if(!$info['node_id']){
        $this->error="机构号不能空";
        return false;
      }
      if(!$info['id']){
        $this->error="参数错误";
        return false;
      }
      $where=array(
        'node_id'=>$info['node_id'],
        'id'=>$info['id'],
        'status'=>0,
        );
      $account=$this->where($where)->getField('account');
      if(!$account){
        return true;
      }
      $this->startTrans();

      $res=$this->where($where)->save(array('status'=>1,'update_time'=>date('YmdHis')));
      if(!$res){
        $this->rollback();
        $this->error="删除客服失败";
        $this->_log($this->error,$info);
        return false;
      }
      $kf=D('WeiXinKf','Service')->setAppIdByNodeId($info["node_id"]);
      if($kf->erron){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $res=$kf->KfDel($account);
      if(!$res){
        $this->rollback();
        $this->error=$kf->getError();
        $this->_log(var_export($this->error,true),$info);
        return false;
      }
      $this->commit();
      return true;
    }

    /**
     * 获取客服聊天记录列表  分页
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function listWithPagingByKfRecord($where=array())
    { 
      //同步
      $this->KfMsgRecordByNode_id($where['node_id']);
      $count=M('twx_kfmsgrecord')->where($where)->count("id");
      if($count <= 0){
        return false;
      }
      import('ORG.Util.Page');// 导入分页类
      $Page = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
      $limit=$Page->firstRow.','.$Page->listRows;
      $page  = $Page->show();
      $field=array(
        'twx_kfmsgrecord.*',
        '(select a.nickname as kfname from twx_kfaccount a where a.nickname <> "" and a.account = twx_kfmsgrecord.account) kfname',
        );
      $list=M('twx_kfmsgrecord')->field($field)->where($where)->order("id")->select();
      if(empty($list)){
        return false;
      }

      foreach($list as &$row){
        $row['add_time']=dateformat($row['add_time']);
      }
      return array("list"=>$list,"page"=>$page);
    }

    /**
     * 获取客服列表分页
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function listWithPagingByKfWhere($where=array())
    {
      $where=array($where,array('status'=>0));
      $count=$this->where($where)->count("id");
      if($count <= 0){
        return false;
      }
      import('ORG.Util.Page');// 导入分页类
      $Page = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
      $limit=$Page->firstRow.','.$Page->listRows;
      $page  = $Page->show();
      $field=array(
        $this->tableName.'.*',
        '(select t.name as tag_name from twx_kftags t where t.tag_id = '.$this->tableName.'.tag_id and name <> "" ) tag_name',
        );
      $list=$this->field($field)->where($where)->select();
      if(empty($list)){
        return false;
      }
      foreach($list as &$row){
        $row['add_time']=dateformat($row['add_time']);
        $row['update_time']=dateformat($row['update_time']);
        $row['lass_call_time']=dateformat($row['lass_call_time']);
      }
      return array("list"=>$list,"page"=>$page);
    }

    /**
     * 获取客服信息
     * @param  [type] $id      [description]
     * @param  [type] $node_id [description]
     * @return [type]          [description]
     */
    public function getKfInfoById($id,$node_id)
    {
      if(empty($id)
      || empty($node_Id)
        ){
        return false;
      }
      $where=array(
          'id'=>$id,
          "node_id"=>$node_id,
          );
      $info=$this->where($where)->find();
      if(empty($info)){
        return false;
      }
      $info['add_time']=dateformat($info['add_time']);
      $info['update_time']=dateformat($info['update_time']);
      $info['lass_call_time']=dateformat($info['lass_call_time']);
      return $info;
    }

    /**
     * 同步客服聊天记录
     * @param [type] $node_id [description]
     */
    public function KfMsgRecordByNode_id($node_id)
    {
      if(!$node_id){
        return false;
      }
      $now=date('YmdHis');
      $where=array(
        "node_id"=>$node_id,
        );
      $start=M("twx_kfmsgrecord")->where($where)->order("add_time desc")->getField("add_time");
      $start=$start?$start:'20160101000000';
      $kf=D('WeiXinKf','Service')->setAppIdByNodeId($node_id);
      if($kf->erron){
        $this->error=$kf->getError();
        $this->_log($this->error,$info);
        return false;
      }
      $list=$kf->KfListByMsgRecord($start,$now);
      if(!$list){
        return false;
      }
      $status_list=array(
          1000 => "创建未接入会话",
          1001 => "接入会话",
          1002 => "主动发起会话",
          1003 => "转接会话",
          1004 => "关闭会话",
          1005 => "抢接会话",
          2001 => "公众号收到消息",
          2002 => "客服发送消息",
          2003 => "客服收到消息",
      );
      $data=array();
      foreach($list as $row){
        $data[]=array(
          'node_id'=>$node_id,
          "openid"=>$row['openid'],
          "state"=>$row['opercode'],
          "content"=>$row['text'],
          "account"=>trim($row['worker']),
          "add_time"=>$row['time'],
          "state_text"=>$status_list[$row['opercode']],
          );
      }
      $res=M("twx_kfmsgrecord")->addAll($data);
      if(!$res){
        $this->_log("同步客服聊天记录失败",$data);
        return false;
      }
      return true;
    }


    /**
     * 创建会话
     * @param [type] $node_id [description]
     * @param [type] $tag_id  标签id
     * @param [type] $openid  用户openid
     */
    public function KfCreateCall($node_id,$tag_id,$openid)
    {
      do{
        $result=true;
        if(empty($node_id)
        || empty($tag_id)
        || empty($openid)
        ){
         // $this->error="非常抱歉，创建会话失败，参数错误";
          $this->_log('创建会话失败，参数错误',array('node_id'=>$node_id,'tag_id'=>$tag_id,'openid'=>$openid));
          $result= false;
          break;
        }
        //设置微信客服token
        $kf=D('WeiXinKf','Service')->setAppIdByNodeId($node_id);
        if($kf->erron){
          $error=$kf->getError();
          $this->_log(var_export($error,true),$info);
          $result= false;
          break;
        }
        //获取在线客服信息
        $online_list=$kf->KfListByOnline();
        if(!$online_list){
          $result=false;
          break;
        }
        //$list=array();
        $account=array();
        foreach($online_list as $row){
        //  $list[$row['kf_account']]=$row;
          $account[]=$row['kf_account'];
        }
        $where=array(
          'tag_id'=>$tag_id,
          "node_id"=>$node_id,
          'status'=>0,
          'account'=>array('in',$account),
          );
        $info=$this->field('id,account')->where($where)->order("current_call_num desc")->find();
        if(!$info){
          $this->_log('创建客服失败，暂无客服',$where);
          $result= false;
          break;
        }

        if($info['current_call_num'] >= 10){
          $this->_log('创建客服失败，客服繁忙',$info);
           $result= false;
          break;
        }
        $where['id']=$info['id'];
        
        $res=$kf->KfCreateCall($info['account'],$openid);
        if(!$res){
          $error=$kf->getError();
          $this->_log(var_export($error,true),$info);
          $result= false;
          break;
        }
      } while (0);
      if(!$result){
         $this->error="非常抱歉，客服繁忙，请稍后在试。";
      }
      return $result;

    }



    /**
     * 客服通知事件处理
     * @param [type] $resp    [description]
     * @param [type] $node_id [description]
     */
    public function KfEvent($resp,$node_id){
        $weixin_kf_config=C('weixinKf');
  
        if(!in_array($node_id,$weixin_kf_config['node_id'])
        || empty($resp)
        || !in_array($resp['Event'],array('kf_close_session','kf_create_session'))
            ){
            return false;
        }
        $type=$resp['Event'];
        $openid=$resp['fromUserName'];
        $account=$resp['KfAccount'];
        $time=date("YmdHis");
        if(is_object($account)){
          $account=(array)$account;
          $account=$account[0];
        }
        $trace_data=array(
          "node_id"=>$node_id,
          "account"=>$account,
          "openid"=>$openid,
          "type"=>0,
          "add_time"=>$time,
          );
        $exp='';
        if(strtolower($type) == "kf_close_session"){
          $trace_data['type']=1;
          $num=$this->where(array('node_id'=>$node_id,'account'=>$account,'status'=>0))->getField('current_call_num');
          if($num > 0){
           $exp="current_call_num=current_call_num-1 ,";
          }
        }else{
          $exp="call_total=call_total+1 , current_call_num=current_call_num+1 , ";
        }
        $sql="update twx_kfaccount set ".$exp." last_call_time='".$time."'";
        $sql.=" where node_id='".$node_id."' and account ='".$account."' and `status` = 0;"; 
        $res=$this->execute($sql);  
        if(!$res){
          $this->_log("更新客服状态失败",$resp);
        }
        $res=M('twx_kfcalltrace')->add($trace_data);
        if(!$res){
          $this->_log("记录流水信息失败【twx_kfcalltrace】",$info);
        }
        return true;
    }

    /**
     * 日志
     * @param  [type] $mome [description]
     * @param  [type] $info 其他参数
     * @return [type]       [description]
     */
    public function _log($mome,$info)
    {
      $msg="微信客服：";
      if(!empty($mome)){
          if(!empty($info)){
              $info=var_export($info,true);
          }
          $msg.=$mome;
          $info and $msg.=$info;
      }
      $msg.="最后操作SQL：".M()->_sql();
      log_write($msg);
    }

    /**
     * 添加标签
     * @param [type] $info [description]
     */
    public function addTag($info)
    {
      if(empty($info['node_id'])){
        $this->error="机构号不能空";
        return false;
      }
      if(empty($info['name'])){
        $this->error="请输入标签名称";
        return false;
      }
      $data=array(
        'node_id'=>$info['node_id'],
        "name"=>trim($info['name']),
        'add_time'=>date('YmdHis'),
        );
      $res=M('twx_kftags')->add($data);
      if(!$res){
        $this->error="添加标签失败";
        $this->_log($this->error,$data);
        return false;
      }
      return true;
    }

    /**
     * 编制标签
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function editTag($info)
    {
      if(empty($info['node_id'])){
        $this->error="机构号不能空";
        return false;
      }

      if(empty($info['name'])){
        $this->error="请输入标签名称";
        return false;
      }
      if(empty($info['tag_id'])){
        $this->error="参数错误";
        return false;
      }
      $where=array(
        'tag_id'=>$info['tag_id'],
        "node_id"=>$info['node_id'],
        );
      $model=M('twx_kftags');
      $new_name=trim($info['name']);
      $old_name=$model->where($where)->getField('name');
      if($old_name == $new_name){
        return true;
      }
      $res=$model->where($where)->setField('name',$new_name);
      if(!$res){
        $this->error="修改标题失败";
        $this->_log($this->error,$info);
        return false;
      }
      return true;
    }

    /**
     * 删除标签
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    public function delTag($info)
    {
      if(empty($info['node_id'])){
        $this->error="机构号不能空";
        return false;
      }

      if(empty($info['tag_id'])){
        $this->error="参数错误";
        return false;
      }
      $where=array(
        'node_id'=>$info["node_id"],
        'tag_id'=>$info['tag_id'],
        "status"=>0,
        );
      $check=$this->where($where)->count('id');
      //check2 TODO:: 自动回复表的使用个情况
      if($check){
        $this->error="请先删除当前标签下的客服";
        return false;
      }
      unset($where['status']);
      $res=M('twx_kftags')->where($where)->delete();
      if($res === false){
        $this->error="删除标签失败";
        return false;
      }
      return true;
    }

    /**
     * 标签列表 不分页
     * @param  array  $where [description]
     * @return [type]        [description]
     */
    public function listByTagWhere($where=array())
    {
      $list=M('twx_kftags')->where($where)->select();
      return $list;
    }
}
