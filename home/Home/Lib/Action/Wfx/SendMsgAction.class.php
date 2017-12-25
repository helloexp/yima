<?php

class SendMsgAction extends BaseAction {

    public $node_id;

    public function _initialize() {
        parent::_initialize();
    }

    public function beforeCheckAuth() {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = "*";
        } elseif (! $this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index() {
        $this->error("禁止访问");
        $this->display();
    }

    /**
     * [storeMsg 旺财小店消息]
     *
     * @return [type] [列出所有旺消息]
     */
    public function storeMsg() {
        $title = I('title', null);
        $reciever = I('reciever', null);
        $map['m.node_id'] = $this->nodeId;
        $map['m.status'] = 2;
        if (in_array($reciever, 
            array(
                '1', 
                '2', 
                '3'))) {
            $map['m.reader'] = $reciever;
        }
        empty($title) or $map['m.title'] = array(
            'like', 
            '%' . $title . '%');
        $count = count(D('Wfx')->getMsgList($map));
        // 导入分页类
        import('ORG.Util.Page');
        $Page = new Page($count, 10);
        $list = D('Wfx')->getMsgList($map, 
            $Page->firstRow . ',' . $Page->listRows);
        $show = $Page->show();
        
        $this->assign('list', $list);
        $this->assign('title', $title);
        $this->assign('reciever', $reciever);
        $this->assign('page', $show);
        $this->display();
    }

    /**
     * [storeMsgAdd 旺财小店消息添加]
     *
     * @return [type] [插入数据]
     */
    public function storeMsgAdd() {
        if ($this->isPost()) {
            $data['node_id'] = $this->nodeId;
            $data['title'] = I('post.title', "");
            $data['content'] = I('post.content', "", "");
            $data['reader'] = I('post.reader');
            $data['m_id'] = I('post.m_id');
            $data['add_time'] = date("YmdHis");
            $data['user_id'] = $this->userInfo['user_id'];
            if (mb_strlen($data['title'], "UTF8") > 15 || empty($data['title'])) {
                $this->error("标题不得为空，且不得大于15字");
            }
            if (! in_array($data['reader'], 
                array(
                    '1', 
                    '2', 
                    '3'))) {
                $this->error("参数有误");
            }
            if (empty($data['content'])) {
                $this->error("正文内容不得为空");
            }
            
            // 判断是否已存在旺分销-群发消息专用渠道,没有则新建一个
            if (! empty($data['m_id'])) {
                try {
                    $channelId = D('Wfx')->channelIsEmpty($this->nodeId);
                    // 绑定渠道与活动
                    D('Wfx')->bindBatchChannel($this->nodeId, $data['m_id'], 
                        $channelId);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            
            if (M('twfx_msg')->add($data)) {
                $this->success("提交成功");
            } else {
                $this->error("提交失败");
            }
        }
        $this->display();
    }

    /**
     * [storeMsgDetail 旺财小店消息详情]
     *
     * @return [type] [无]
     */
    public function storeMsgDetail() {
        // 判断参数是否有误
        $msgInfo = M('twfx_msg')->where("node_id=" . $this->nodeId)->getById(
            I("get.id")) or $this->error("参数错误");
        // 获取操作员姓名
        $userName = M('tuser_info')->getFieldByUser_id($msgInfo['user_id'], 
            'true_name');
        // 获取活动名称
        $batchName = M('tmarketing_info')->getFieldById($msgInfo['m_id'], 
            'name');
        // 导入分页类
        import('@.ORG.Util.Page');
        $CPage = new Page(
            M("twfx_msg_read_list")->where("msg_id=" . I("get.id"))->count(), 6);
        // 设置分页显示样式
        $CPage->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $CPage->setConfig('prev', '«');
        $CPage->setConfig('next', '»');
        // 获取消息读取人的信息
        $msgReadInfo =M()->table("twfx_msg_read_list m")->field("m.read_time,s.name")
            ->join("twfx_saler s ON s.id=m.saler_id")
            ->where("m.msg_id=" . I("get.id"))
            ->limit($CPage->firstRow . ',' . $CPage->listRows)
            ->select();
        // 获取活动与渠道的id
        $batchChannelInfo = M()->table("tbatch_channel b")->field("b.*")
            ->join('tchannel c ON c.id=b.channel_id')
            ->where(
            array(
                'b.batch_id' => $msgInfo['m_id'], 
                'c.sns_type' => 102, 
                'c.type' => 1, 
                'b.node_id' => $this->node_id, 
                'c.node_id' => $this->nodeId))
            ->select();
        $this->assign("msgInfo", $msgInfo);
        $this->assign("userName", $userName);
        $this->assign("batchName", $batchName);
        $this->assign("msgReadInfo", $msgReadInfo);
        $this->assign("batchChannelId", $batchChannelInfo[0]['id']);
        $this->assign("page", $CPage->show());
        $this->assign("readerIntro", 
            array(
                '1' => '所有人', 
                '2' => '经销商', 
                '3' => '销售员'));
        $this->display();
    }

    /**
     * [storeMsgModify 修改当前消息]
     *
     * @return [type] [无]
     */
    public function storeMsgModify() {
        if ($this->isPost()) {
            $data['title'] = I('post.title');
            $data['reader'] = I('post.reader');
            $data['m_id'] = I('post.m_id');
            $data['content'] = I('post.content', "", "");
            if (mb_strlen($data['title'], "UTF8") > 15 || empty($data['title'])) {
                $this->error("标题不得为空，且不得大于15字");
            }
            if (! in_array($data['reader'], 
                array(
                    '1', 
                    '2', 
                    '3'))) {
                $this->error("参数有误");
            }
            if (empty($data['content'])) {
                $this->error("正文内容不得为空");
            }
            // 查询之前的mid是多少
            $preMId = M('twfx_msg')->getFieldById(I('post.id'), 'm_id');
            // 如果绑定的活动发生变化，则重新绑定
            if (! empty($data['m_id']) && $preMId != $data['m_id']) {
                try {
                    $channelId = D('Wfx')->channelIsEmpty($this->nodeId);
                    // 绑定渠道与活动
                    D('Wfx')->bindBatchChannel($this->nodeId, $data['m_id'], 
                        $channelId);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            if (false === M('twfx_msg')->where(
                "node_id=" . $this->nodeId . " and id=" . I('post.id'))->save(
                $data)) {
                $this->error("修改失败");
            } else {
                $this->success("修改成功");
            }
        }
        $msgInfo = M()->table("twfx_msg g")->field("g.*,m.name,m.batch_type")
            ->join('tmarketing_info m ON m.id=g.m_id')
            ->where("g.node_id=" . $this->nodeId . " and g.id=" . I('get.id'))
            ->find();
        $this->assign("msgInfo", $msgInfo);
        $this->assign("batchType", C('BATCH_TYPE_NAME'));
        $this->display();
    }

    /**
     * [storeMsgDelete 删除当前消息]
     *
     * @return [type] [无]
     */
    public function storeMsgDelete() {
        $id = I('id');
        ! empty($id) or $this->error('参数错误');
        M('twfx_msg_read_list')->where(array(
            'msg_id' => $id))->save(array(
            'msg_status' => '4'));
        M('twfx_msg')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => I('id')))->setField('status', 4) == 1 ? $this->success(
            "删除成功") : $this->error("删除失败");
    }

    public function weixinMsg() {
        $wxInfo = D('TweixinInfo');
        $info = $wxInfo->templateShow($this->node_id, 1);
        if (! empty($info)) {
            redirect(U('Wfx/SendMsg/weixinMsgStart'));
        } else {
            $this->display();
        }
    }

    public function weixinMsgStart() {
        $wxInfo = D('TweixinInfo');
        
        if (IS_POST) {
            $data = I("post.");
            if (empty($data['id']))
                $this->error("模块ID不得为空！");
            if (empty($data['start']))
                $this->error("欢迎语不得为空！");
            if (empty($data['end']))
                $this->error("结束语不得为空！");
            
            $result = $wxInfo->templateJson($this->nodeId, 1, $data['id'], 
                $data['start'], $data['end']);
            if ($result) {
                $this->success('保存成功');
            } else {
                $this->error("保存失败,请修改后再保存");
            }
            exit();
        }
        
        $info = $wxInfo->templateShow($this->node_id, 1);
        $info['welInfo'] = json_decode($info['data_config'], true);
        $this->assign('info', $info);
        $this->display();
    }
}

