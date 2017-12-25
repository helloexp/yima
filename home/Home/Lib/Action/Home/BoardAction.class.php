<?php

/**
 * @2015/01/16
 */
class BoardAction extends Action {

    public $user_Info;

    const BOARD_IMG_URL = "./Home/Upload/upload_img/";

    public function _initialize() {
        if ($this->isPost() && C('CHECK_FUCK_WORD')) {
            $result = has_fuck_word();
            if ($result) {
                // $this->error("输入内容含有敏感词【".implode(',',$result)."】");
                exit(
                    json_encode(
                        array(
                            'code' => '0', 
                            'codeText' => "输入内容含有敏感词【" . implode(',', $result) .
                                 "】")));
            }
        }
        
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->user_Info = $userInfo;
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $model = M('twc_board');
        
        $where = array(
            'B.screen' => '0');
        $type = I('type');
        if ($type != '')
            $where['B.liuyan_type'] = $type;
        
        import('ORG.Util.Page');
        
        $count = $model->alias('B')
            ->field('B.*,N.node_name')
            ->where($where)
            ->join('tnode_info N on B.node_id=N.node_id')
            ->count();
        
        $Page = new Page($count, 15);
        $show = $Page->show();
        
        $list = $model->alias('B')
            ->field('B.*,N.node_name')
            ->where($where)
            ->join('tnode_info N on B.node_id=N.node_id')
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $star = $this->twc_star();
        $this->assign("star", $star);
        $this->assign("type", $type);
        $this->assign("list", $list);
        $this->assign("show", $show);
        $this->display();
    }
    
    // 发表留言
    public function wicket() {
        $wicketType = array(
            '0' => '寻求合作', 
            '1' => '在线咨询');
        
        if ($this->isPost()) {
            
            if (empty($this->user_Info)) {
                exit(
                    json_encode(
                        array(
                            'code' => '0', 
                            'codeText' => '请登陆后，再提交！！！')));
            }
            
            $data = I('post.');
            
            $liuyan_type = $data['wicketType'];
            $liuyan_title = $data['wicketTitle'];
            $liuyan_content = $data['wicketContent'];
            $liuyan_img = $data['img_url'];
            $lianxifangshi = $data['wicketIphone'];
            $userdata = $this->user_Info;
            $node_id = $userdata['node_id'];
            $screen = M('tnode_info')->where(
                array(
                    'node_id' => $node_id))->getField('screen');
            if ($screen == '1') {
                exit(
                    json_encode(
                        array(
                            'code' => '0', 
                            'codeText' => '您已经被禁言了！！！')));
            }
            $array = array(
                'node_id' => $node_id, 
                'liuyan_type' => $liuyan_type, 
                'liuyan_content' => $liuyan_content, 
                'liuyan_title' => $liuyan_title, 
                'liuyan_img' => $liuyan_img, 
                'lianxifangshi' => $lianxifangshi, 
                'add_time' => date('YmdHis'));
            
            $outcome = M('twc_board')->add($array);
            
            if ($outcome) {
                exit(
                    json_encode(
                        array(
                            'code' => "$outcome", 
                            'codeText' => '发表成功！！！')));
            } else {
                exit(
                    json_encode(
                        array(
                            'code' => '0', 
                            'codeText' => '发表失败！！！')));
            }
        }
        $this->assign('wicketType', $wicketType);
        $this->display();
    }
    
    // 详情页
    public function detail() {
        $BoardID = I('Boardid');
        $seqid = $_REQUEST['seqid'];
        $this->assign('seqid', $seqid);
        if ($BoardID == '')
            exit('参数错误');
        
        $this->lookup($BoardID, $fid = 0);
        
        $model = M('twc_board');
        
        $row = $model->alias('B')
            ->field('B.*,N.node_name')
            ->where(array(
            'B.id' => $BoardID))
            ->join('tnode_info N on B.node_id=N.node_id')
            ->find();
        
        import('ORG.Util.Page');

        $count =M()->table("twc_restoer a")->where(
            array(
                'a.BoardID' => $BoardID, 
                'a.rank' => '1', 
                'a.peID' => '0', 
                'a.screen' => '0'))->count();
        
        $Page = new Page($count, 5);
        $show = $Page->show();
        
        $list = M()->table("twc_restoer a")->field('a.*,b.node_name,b.head_photo')
            ->where(
            array(
                'a.BoardID' => $BoardID, 
                'a.rank' => '1', 
                'a.peID' => '0', 
                'a.screen' => '0'))
            ->join('tnode_info b on a.node_id=b.node_id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $hfList = M()->table("twc_restoer a")->field(
                    'a.*,b.node_name,b.head_photo')
                    ->where(
                    array(
                        'a.BoardID' => $BoardID, 
                        'a.rank' => '2', 
                        'a.peID' => $v['id'], 
                        'a.screen' => '0'))
                    ->join('tnode_info b on a.node_id=b.node_id')
                    ->select();
                $list[$k]['huifu'] = $hfList;
            }
        }
        $isready = M('twc_restoer')->where(
            array(
                'status' => '0', 
                'id' => $seqid, 
                'quilt_user' => $this->userInfo['node_id']))->count();
        if ($isready > 0) {
            M('twc_restoer')->where(array(
                'id' => $seqid))->save(
                array(
                    'status' => '1'));
            $sysmsgcunt = M('tmessage_stat')->field('new_message_cnt')
                ->where(
                array(
                    'node_id' => $this->userInfo['node_id'], 
                    'message_type' => 3))
                ->find();
            $sysmsgcunt = $sysmsgcunt['new_message_cnt'];
            if ($sysmsgcunt > 0) {
                M()->query(
                    'UPDATE `tmessage_stat` 
				SET `last_time`="' .
                         date('YmdHis') . '",`new_message_cnt`=`new_message_cnt`-1 WHERE 
				( `node_id` = "' .
                         $this->userInfo['node_id'] .
                         '" ) AND ( `message_type` = 3 )');
            }
        }
        
        $star = $this->twc_star();
        $this->assign("show", $show);
        $this->assign("star", $star);
        $this->assign('row', $row);
        $this->assign('list', $list);
        $this->assign('board_img_url', self::BOARD_IMG_URL);
        $this->display();
    }

    public function oneAddSubmit() {
        if (empty($this->user_Info)) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '请登陆后，再提交！！！')));
        }
        
        $data = I('post.');
        if (empty($data['boardId'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        
        if (empty($data['Bcontent'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '内容为空！！！')));
        }
        
        $userdata = $this->user_Info;
        $node_id = $userdata['node_id'];
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $node_id))->getField('screen');
        if ($screen == '1') {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '您已经被禁言了！！！')));
        }
        $backuid = M('twc_board')->field('node_id')
            ->where(array(
            'id' => $data['boardId']))
            ->find();
        $backuid = $backuid['node_id'];
        $array = array(
            'BoardID' => $data['boardId'], 
            'node_id' => $node_id, 
            'peID' => '0', 
            'contents' => $data['Bcontent'], 
            'rank' => '1', 
            'status' => 0, 
            'add_time' => date('YmdHis'), 
            'quilt_user' => $backuid);
        $outcome = M('twc_restoer')->add($array);
        $bei = M('twc_board')->where(
            array(
                'id' => $data['boardId']))->getField('node_id');
        if ($outcome) {
            add_msgstat(
                array(
                    'node_id' => $backuid, 
                    'message_type' => 3));
            
            // $this->send_news($bei,$data['boardId'],$data['Bcontent']);
            $this->reply($data['boardId']);
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'codeText' => '发表回复成功！！！')));
        } else {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '发表回复失败！！！')));
        }
    }

    public function twoAddSubmit() {
        if (empty($this->user_Info)) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '请登录后，再提交！！！')));
        }
        
        $data = I('post.');
        if (empty($data['boardId'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        
        if ($this->judge($data['contents']) === false) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '内容不能为空，并且不能超出1000字！！！')));
        }
        
        if (empty($data['fromID'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        $userdata = $this->user_Info;
        $node_id = $userdata['node_id'];
        
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $node_id))->getField('screen');
        if ($screen == '1') {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '您已经被禁言了！！！')));
        }
        
        $array = array(
            'BoardID' => $data['boardId'], 
            'node_id' => $node_id, 
            'peID' => $data['fromID'], 
            'contents' => $data['contents'], 
            'rank' => '2', 
            'status' => 0, 
            'quilt_user' => $data['node_id'], 
            'add_time' => date('YmdHis'));
        
        $outcome = M('twc_restoer')->add($array);
        
        $bei1 = M('twc_board')->where(
            array(
                'id' => $data['boardId']))->getField('node_id');
        $bei2 = M('twc_restoer')->where(
            array(
                'id' => $data['fromID']))->getField('node_id');
        if ($outcome) {
            
            // 帖子
            $backuid = M('twc_board')->field('node_id')
                ->where(array(
                'id' => $data['boardId']))
                ->find();
            $backnodeid = M('twc_restoer')->where(
                array(
                    'id' => $data['fromID']))
                ->field('node_id')
                ->find();
            $backuid = $backuid['node_id'];
            /*
             * //帖子数+1 if($node_id!=$backuid && $backuid !=$backnodeid)
             * add_msgstat(array('node_id'=>$backuid,'message_type'=>3));
             */
            // 评论+1
            add_msgstat(
                array(
                    'node_id' => $backnodeid['node_id'], 
                    'message_type' => 3));
            // $this->send_news($bei1,$data['boardId'],$data['contents']);
            // $this->send_news($bei2,$data['boardId'],$data['contents']);
            $this->reply($data['boardId']);
            $fruit = M('twc_restoer')->where(
                array(
                    'id' => $data['fromID']))->setInc('reply');
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'codeText' => '发表回复成功！！！')));
        } else {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '发表回复失败！！！')));
        }
    }

    public function minAddSubmit() {
        if (empty($this->user_Info)) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '请登录后，再提交！！！')));
        }
        
        $data = I('post.');
        if (empty($data['boardId'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        
        if ($this->judge($data['contents']) === false) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '内容不能为空，并且不能超出1000字！！！')));
        }
        
        if (empty($data['fromID'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        
        if (empty($data['node_id'])) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        }
        
        $userdata = $this->user_Info;
        $node_id = $userdata['node_id'];
        
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $node_id))->getField('screen');
        if ($screen == '1') {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '您已经被禁言了！！！')));
        }
        
        $array = array(
            'BoardID' => $data['boardId'], 
            'node_id' => $node_id, 
            'peID' => $data['fromID'], 
            'contents' => $data['contents'], 
            'rank' => '2', 
            'status' => 0, 
            'quilt_user' => $data['node_id'], 
            'add_time' => date('YmdHis'));
        
        $outcome = M('twc_restoer')->add($array);
        $bei1 = M('twc_board')->where(
            array(
                'id' => $data['boardId']))->getField('node_id');
        $bei2 = M('twc_restoer')->where(
            array(
                'id' => $data['fromID']))->getField('node_id');
        if ($outcome) {
            
            $backuid = M('twc_board')->field('node_id')
                ->where(array(
                'id' => $data['boardId']))
                ->find();
            $backnodeid = M('twc_restoer')->where(
                array(
                    'id' => $data['fromID']))
                ->field('node_id')
                ->find();
            
            $backuid = $backuid['node_id'];
            /*
             * if($node_id!=$backuid && $backuid !=$backnodeid)
             * add_msgstat(array('node_id'=>$backuid,'message_type'=>3));
             */
            // 回复的node_id
            add_msgstat(
                array(
                    'node_id' => $data['node_id'], 
                    'message_type' => 3));
            
            // $this->send_news($bei1,$data['boardId'],$data['contents']);
            // $this->send_news($bei2,$data['boardId'],$data['contents']);
            // $this->send_news($data['node_id'],$data['boardId'],$data['contents']);
            $this->reply($data['boardId']);
            $fruit = M('twc_restoer')->where(
                array(
                    'id' => $data['fromID']))->setInc('reply');
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'codeText' => '发表回复成功！！！')));
        } else {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '发表回复失败！！！')));
        }
    }
    // 查看次数
    protected function lookup($id, $fid) {
        if ($id == '')
            return false;
        
        $fid = I('fid');
        
        if ($fid == '1')
            return false;
        
        $fruit = M('twc_board')->where(array(
            'id' => $id))->setInc('lookup');
        
        if ($fruit) {
            return true;
        } else {
            return false;
        }
    }

    protected function reply($id) {
        if ($id == '')
            return false;
        
        $fruit = M('twc_board')->where(array(
            'id' => $id))->setInc('reply');
        
        if ($fruit) {
            return true;
        } else {
            return false;
        }
    }

    public function praise() {
        if (empty($this->user_Info)) {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '请登录后，再点赞！！！')));
        }
        
        $id = I('resID');
        
        if (empty($id))
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '参数错误！！！')));
        
        $fruit = M('twc_restoer')->where(array(
            'id' => $id))->setInc('help');
        
        if ($fruit) {
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'codeText' => '点赞成功！！！')));
        } else {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '点赞失败！！！')));
        }
    }

    private function twc_star() {
        $model = M('twc_star');
        $list = array();
        $star = $model->order('id DESC')->select();
        
        if ($star) {
            foreach ($star as $k => $v) {
                if ($v['trade'] == '0') {
                    $list['0'][] = $v;
                } else if ($v['trade'] == '1') {
                    $list['1'][] = $v;
                } else if ($v['trade'] == '2') {
                    $list['2'][] = $v;
                }
            }
        }
        
        return $list;
    }

    private function judge($string, $max = 1000, $min = 1) {
        $res = check_str($string, 
            array(
                'maxlen_cn' => $max, 
                'minlen_cn' => $min));
        return $res;
    }

    public function submit_contactnode() {
        // 被联系机构号
        $contact_node = I("contact_node");
        $current_node = $this->user_Info['node_id'];
        $contact_msg = I("contact_msg");
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $current_node))->getField('screen');
        if ($screen == '1') {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '您已经被禁言了！！！')));
        }
        if (empty($this->user_Info)) {
            // $this->error("请先登录后提交留言！");
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '请先登录后提交留言！')));
        }
        
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $current_node))->getField('screen');
        if ($screen == '1') {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '您已经被禁言了！！！')));
        }
        
        if ($contact_node == "") {
            // $this->error("机构参数错误！");
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '机构参数错误！')));
        }
        
        if ($current_node == "") {
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '机构参数错误！')));
        }
        
        // 查询被联系机构用户ID
        $userID = M()->table('tuser_info n ')
            ->field("n.user_id")
            ->where("node_id='" . $contact_node . "'")
            ->find();
        
        $data = array(
            "message_text" => $contact_msg, 
            "send_node_id" => $this->userInfo['node_id'], 
            "send_user_id" => $this->userInfo['user_id'], 
            "receive_node_id" => $contact_node, 
            "receive_user_id" => $userID['user_id'], 
            "ck_status" => '2', 
            "status" => '4', 
            "reply_id" => time(), 
            "laiyuan_type" => '2', 
            "add_time" => date('YmdHis'));
        $insertok = M('tmessage_info')->add($data);
        if ($insertok) {
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'codeText' => '发送成功！！！')));
        } else {
            // $this->error("发送消息失败，入库错误！");
            exit(
                json_encode(
                    array(
                        'code' => '0', 
                        'codeText' => '发送消息失败，入库错误！')));
        }
    }

    public function add_news() {
        $data = array(
            'title' => "您的留言板有新的回复了！快去查看吧！！！", 
            'content' => "<pre>您的留言板有新的回复了！<b><a href='' target='_blank'>快去查看吧！！！</a></b>。
     <br/>
          测试！测试！测试！</pre>", 
            'add_time' => date('YmdHis'), 
            'msg_type' => '0');
        
        $add_ = M("tmessage_news")->add($data);
        
        if ($add_) {
            return $add_;
        } else {
            return false;
        }
    }

    public function send_news($node_id, $id, $contents) {
        $url = "index.php?g=Home&m=Board&a=detail&fid=1&Boardid=$id";
        $arr = array(
            'title' => "您的留言板有新的回复了！快去查看吧！！！", 
            'content' => "<pre>您的留言板有新的回复了！<b><a href='$url' target='_blank'>快去查看吧！！！</a></b>。
     <br/>
         回复内容：$contents</pre>", 
            'add_time' => date('YmdHis'), 
            'msg_type' => '0');
        
        $add_ = M("tmessage_news")->add($arr);
        
        $data = array(
            "message_id" => $add_, 
            "node_id" => $node_id, 
            "send_status" => '0', 
            "status" => '0', 
            "add_time" => date('YmdHis'));
        
        $send_ = M("tmessage_recored")->add($data);
    }
}