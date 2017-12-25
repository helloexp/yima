<?php

class Spring2015Action extends MyBaseAction {
    // 微信用户id
    public $wxid;

    public $js_global = array();

    const BATCH_TYPE_SPRING = 42;

    const DEFAULT_GAME_NUMBER = 3;
    // 默认次数
    const MAX_SCORE = 200;

    const MAX_SHARE_NUM = 1000;
    // 最多分享数
    public function _initialize() {
        
        // edit by tr
        $this->error(
            array(
                'errorImg' => '__PUBLIC__/Label/Image/waperro1.png', 
                'errorTxt' => '该活动已结束！', 
                'errorSoftTxt' => '活动已经结束啦~'));
        exit();
        
        // 特殊判断
        if (ACTION_NAME == 'info') {
            return;
        }
        // todo debug
        if (I('_sid_') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
            session('wxid', 1);
        }
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请使用微信扫码二维码进入活动');
        }
        
        // 重置数据
        if (I('_sid_') == 'clean') {
            session('wxid', null);
            session('from_user_id', null);
            redirect(U('', array(
                'id' => $this->id)));
            exit();
        }
        parent::_initialize();
        
        if ($this->batch_type != self::BATCH_TYPE_SPRING) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        // 设置语言
        $this->_setLang();
        
        if (! session('?wxid')) {
            $from_user_id = I('from_user_id', NULL);
            $this->redirect(
                U('Label/WeixinLogin/index', 
                    array(
                        'id' => $this->id, 
                        'from_user_id' => $from_user_id)));
        }
        
        $this->wxid = session('wxid');
        
        $from_user_id = I('from_user_id');
        if ($from_user_id != '' && $this->wxid != $from_user_id) {
            session('from_user_id', $from_user_id);
        }
        if (session('?from_user_id')) {
            $this->getFriend();
        }
        
        // 活动信息
        $marketInfo = $this->marketInfo;
        
        // 分享信息
        $uparr = C('BATCH_IMG_PATH_NAME');
        $shareUrl = U('index', 
            array(
                'id' => $this->id, 
                'from_user_id' => $this->wxid), '', '', TRUE);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => $marketInfo['wap_title'], 
            // 'shareNote'=>$row['wap_info'],
            'desc' => '就赐我几次机会吧，我要去赚金币啊！', 
            'imgUrl' => C('CURRENT_DOMAIN') . 'Home/Public/Label/Image/20150218/' .
                 L('IMAGE_ICON'));
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
    }
    
    // 首页
    public function index() {
        if ($this->batch_type != self::BATCH_TYPE_SPRING)
            $this->error('错误访问！');
            
            // 初始化小秋
        $info = M('twx_firecrackers_score')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid))->find();
        if (! $info) {
            $data = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid, 
                'game_number' => self::DEFAULT_GAME_NUMBER);
            $query = M('twx_firecrackers_score')->add($data);
            if (! $query) {
                $this->error('初始化活动失败！');
            }
            $info = array_merge($data, 
                array(
                    'id' => $query));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 奖品显示
        $jp_arr = $this->searchJp();
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        M('twx_firecrackers_score')->where($data)->setInc('first_count', 1);
        $first_count = M('twx_firecrackers_score')->where($data)->getField(
            'first_count');
        // $index_arr = $this->playRate(false);
        // $zong = $this->allRankingCount();
        
        // 弹窗
        // $send_open = $this->myOpen();
        // $this->assign('send_open',$send_open);
        
        // 其他人赠送给我
        // $from_open = $this->otherOpen();
        // $this->assign('from_open',$from_open);
        
        // $this->assign('index_arr',$index_arr);
        // $this->assign('zong',$zong);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('gameInfo', $info);
        $this->assign('first_count', $first_count);
        $this->assign('jp_arr', $jp_arr);
        $this->display(); // 输出模板
    }
    
    // 游戏
    public function playGame() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        $myscore = $this->searchMyInfo();
        // 设置js
        $this->_setGlobalJs(
            array(
                'nowcode' => $myscore['score'],  // 分数
                'game_number' => $myscore['game_number'],  // 剩下次数
                'nowball' => $myscore['game_number'] ? 5 : 0)); // 炮数
        
        $this->assign('info', $myscore);
        $this->display();
    }
    
    // ajax计算机击败率
    public function playRate($isAjax = true) {
        $array = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id);
        // 总共多少个
        $max_count = M('twx_firecrackers_score')->where($array)->count();
        // 个人现在分数
        $array['wx_user_id'] = $this->wxid;
        /*
         * $mycount =
         * M('twx_firecrackers_score')->where($array)->getField('ranking_count');
         * $myscore =
         * M('twx_firecrackers_score')->where($array)->getField('score');
         */
        $scoreInfo = M('twx_firecrackers_score')->where($array)
            ->field('ranking_count,score')
            ->find();
        $mycount = $scoreInfo['ranking_count'];
        $myscore = $scoreInfo['score'];
        // 总共大于我的数量
        $array = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'ranking_count' => array(
                'exp', 
                ' >' . $mycount));
        $cycount = M('twx_firecrackers_score')->where($array)->count();
        $max_count = $max_count - 1;
        if ($max_count == 0)
            $rate = 0;
        else
            $rate = 100 - round($cycount / $max_count, 2) * 100;
            
            // 差多少可以获得分值最近的奖品
        
        $jp_sql = "SELECT b.* FROM tcj_batch a
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id
				WHERE
				    a.status =1
				    AND a.batch_id ='" . $this->batch_id . "'
				    and b.score > $myscore
				    order by b.score
				    limit 1
		";
        
        $jp_cate = M()->query($jp_sql);
        if ($jp_cate && $jp_cate[0]) {
            $jp_cate = $jp_cate[0];
            $other_score = $jp_cate['score'] - $myscore;
            $showmsg = '<p class="msg-p">' . $jp_cate['name'] . '还差' .
                 $other_score . '金币</p>';
        } else {
            $other_score = 0;
            $showmsg = '';
        }
        
        $resp_arr = array(
            'rate' => $rate, 
            'showmsg' => $showmsg);
        if ($isAjax)
            $this->success($resp_arr);
        else
            return array(
                'rate' => $rate, 
                'showmsg' => '领取' . $jp_cate['name'] . '还差' . $other_score . '金币');
    }
    
    // 强制刷新
    public function getInfo() {
        $myscore = $this->searchMyInfo();
        $this->success($myscore);
    }
    
    // 官方排行
    public function ranking() {
        
        // 个人总统计
        $myranking = $this->allRankingCount($this->node_id);
        
        // 商户排行榜
        // $noderanking = $this->allRankingList($this->node_id);
        
        // 奖品显示
        $jp_arr = $this->searchJp();
        
        // 总排行
        // $result = $this->allRankingList();
        $where = array(
            'a.node_id' => $this->node_id);
        // 是否炮友
        $_GET['friend'] = I('friend', 0, 'intval');
        if ($_GET['friend'] == 1) {
            $where['_string'] = "
                (
                exists
                    (select 1 from twx_firecrackers_friend_trace where
                        node_id='" . $this->node_id . "'
                        and batch_id='" .
                 $this->batch_id . "'
                        and from_user_id=a.wx_user_id and send_user_id='" .
                 $this->wxid . "'
                    )
                )
                or
                (
                exists
                    (select 1 from twx_firecrackers_friend_trace where
                        node_id='" . $this->node_id . "'
                        and batch_id='" .
                 $this->batch_id . "'
                        and send_user_id=a.wx_user_id and from_user_id='" .
                 $this->wxid . "'
                    )
                )
            ";
            $frow = $this->MyCount();
            $this->assign('frow', $frow);
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = 100;
        $Page = new Page($mapcount, 10);
        $_GET['p'] = I('get.p', 1);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        
        $result = M()->table('twx_firecrackers_score a')
            ->field('b.nickname,b.headimgurl,sum(a.ranking_count) as score')
            ->join('twx_wap_user b on a.wx_user_id=b.id')
            ->group('a.wx_user_id')
            ->order('score desc,a.wx_user_id asc')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M()->_sql();exit;
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('ranking', 
            array(
                'id' => $this->id, 
                'friend' => $_GET['friend']), '', '', true) . '&p=' .
             ($nowPage + 1);
        $this->assign('nowpage', $nowPage);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('result', $result);
        $this->assign('myranking', $myranking);
        /* $this->assign('noderanking',$noderanking); */
        $this->assign('jp_arr', $jp_arr);
        $this->display();
    }
    
    // 领取奖品
    public function getPrize() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        $myscore = $this->searchMyInfo();
        $jp_arr = $this->searchJp();
        // 已领奖项
        $user_id = M('twx_firecrackers_score')->where(
            array(
                'wx_user_id' => $this->wxid, 
                'batch_id' => $this->batch_id))->getField('id');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'status' => '2', 
            'user_id' => $user_id);
        $cjcate_arr = M('tcj_trace')->field('cate_id')
            ->where($map)
            ->select();
        $zj_cate_arr = array();
        if ($cjcate_arr) {
            foreach ($cjcate_arr as $cjcate) {
                $zj_cate_arr[] = $cjcate['cate_id'];
            }
        }
        
        $this->assign('zj_cate_arr', $zj_cate_arr);
        $this->assign('myscore', $myscore);
        $this->assign('jp_arr', $jp_arr);
        $this->display();
    }
    
    // 获赠小球列表
    public function getList() {
        $frow = $this->MyCount();
        
        import('ORG.Util.Page'); // 导入分页类
        
        $mapcount = M()->table('twx_firecrackers_friend_trace a')
            ->join('twx_wap_user b on a.send_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.from_user_id='" . $this->wxid . "'")
            ->count();
        
        $Page = new Page($mapcount, 10);
        $_GET['p'] = I('get.p', 1);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        
        $friend_arr = M()->table('twx_firecrackers_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.add_time')
            ->join('twx_wap_user b on a.send_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.from_user_id='" . $this->wxid . "'")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('getList', array(
            'id' => $this->id), '', '', true) . '&p=' . ($nowPage + 1);
        // $friend_arr = $this->searchFriend();
        $this->assign('friend_arr', $friend_arr);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('frow', $frow);
        $this->display();
    }
    
    // 赠送给别人列表
    public function giveList() {
        import('ORG.Util.Page'); // 导入分页类
        
        $mapcount = M()->table('twx_firecrackers_friend_trace a')
            ->join('twx_wap_user b on a.from_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.send_user_id='" . $this->wxid . "'")
            ->count();
        
        $Page = new Page($mapcount, 10);
        $_GET['p'] = I('get.p', 1);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        
        $friend_arr = M()->table('twx_firecrackers_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.add_time')
            ->join('twx_wap_user b on a.from_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.send_user_id='" . $this->wxid . "'")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('giveList', array(
            'id' => $this->id), '', '', true) . '&p=' . ($nowPage + 1);
        // $friend_arr = $this->searchFriend();
        $this->assign('friend_arr', $friend_arr);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('frow', $mapcount);
        $this->display();
    }
    
    // 查询奖品
    protected function searchJp($data = array()) {
        $jp_sql = "SELECT b.id as cid, b.name,b.score ,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id 
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        return $jp_arr;
    }
    
    // 查询当前活动排行
    protected function searchRanking() {
        $result = M()->table('twx_firecrackers_score a')
            ->field('b.nickname,b.headimgurl,a.score')
            ->join('twx_wap_user b on a.wx_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' ")
            ->order('a.score desc,b.id asc')
            ->limit(10)
            ->select();
        return $result;
    }
    
    // 当前活动个人统计查询
    protected function searchMyInfo() {
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $myranking = M('twx_firecrackers_score')->where($map)->find();
        if (! $myranking) {
            redirect(U('index', array(
                'id' => $this->id)));
            exit();
        }
        // 当前排名按分数计算再按先后参与计算
        $sort_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'score' => array(
                'gt', 
                $myranking['score']));
        
        $ranking1 = M('twx_firecrackers_score')->where($sort_arr)->count();
        
        $sort_arr['score'] = $myranking['score'];
        $sort_arr['wx_user_id'] = array(
            'elt', 
            $myranking['wx_user_id']);
        
        $ranking2 = M('twx_firecrackers_score')->where($sort_arr)->count();
        
        $myranking['ranking'] = (int) $ranking1 + (int) $ranking2;
        return $myranking;
    }
    
    // 个人总排行查询
    protected function allRankingCount($node_id = '') {
        $where = array(
            'wx_user_id' => $this->wxid);
        if ($node_id) {
            $where['node_id'] = $node_id;
        }
        $myranking = M('twx_firecrackers_score')->field(
            'sum(ranking_count) as ranking_count,wx_user_id')
            ->where($where)
            ->group('wx_user_id')
            ->find();
        
        $query = M()->query(
            "select count(*) as count from
   		        (SELECT id FROM twx_firecrackers_score
   		            where node_id='" . $node_id . "'
   		           GROUP BY wx_user_id HAVING sum(ranking_count)>'" .
                 $myranking['ranking_count'] . "') tab");
        $ranking1 = $query[0]['count'];
        
        $query = M()->query(
            "select count(*) as count from (SELECT id FROM twx_firecrackers_score
   		    WHERE ( wx_user_id<='" .
                 $this->wxid . "' and node_id='" . $node_id . "')
   		     GROUP BY wx_user_id HAVING sum(ranking_count)='" .
                 $myranking['ranking_count'] . "') tab");
        $ranking2 = $query[0]['count'];
        $myranking['ranking'] = (int) $ranking1 + (int) $ranking2;
        
        $myranking['score'] = $myranking['ranking_count'];
        return $myranking;
    }
    
    // 总排行前十名
    protected function allRankingList($node_id = NULL) {
        $wh = '1=1';
        if (! empty($node_id)) {
            $wh .= " and a.node_id = '" . $node_id . "' ";
        }
        $result = M()->table('twx_firecrackers_score a')
            ->field('b.nickname,b.headimgurl,sum(a.ranking_count) as score')
            ->join('twx_wap_user b on a.wx_user_id=b.id')
            ->where($wh)
            ->group('a.wx_user_id')
            ->order('score desc,a.wx_user_id asc')
            ->limit(10)
            ->select();
        return $result;
    }
    
    // 好友赠送记录
    protected function searchFriend() {
        $result = M()->table('twx_firecrackers_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.add_time')
            ->join('twx_wap_user b on a.send_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.from_user_id='" . $this->wxid . "'")
            ->order('a.id desc')
            ->select();
        return $result;
    }
    
    // 邀请好友
    protected function getFriend() {
        $overdue = $this->checkDate();
        // 如果活动失效了
        if ($overdue === false) {
            return false;
        }
        $from_user_id = session('from_user_id');
        $query = M('twx_wap_user')->where(
            array(
                'id' => $from_user_id))->find();
        if (! $query) {
            session('from_user_id', NULL);
            return false;
        }
        // 分享好友不能超过最大值
        $farr = array(
            'from_user_id' => $from_user_id, 
            'batch_id' => $this->batch_id);
        
        $maxsnow = M('twx_firecrackers_friend_trace')->where($farr)->count();
        log_write(
            'from_user_id=' . $from_user_id . ', 分享数=' . $maxsnow . ', wxid=' .
                 $this->wxid . ', id=' . $this->id);
        if ($maxsnow > self::MAX_SHARE_NUM) {
            log_write(
                "分享好友超过:" . self::MAX_SHARE_NUM . ' from_user_id:' .
                     $from_user_id, '异常数据');
            session('from_user_id', NULL);
            return false;
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $from_user_id, 
            'send_user_id' => $this->wxid);
        $query = M('twx_firecrackers_friend_trace')->where($map)->find();
        if ($query) {
            session('from_user_id', NULL);
            return false;
        }
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $from_user_id, 
            'send_user_id' => $this->wxid, 
            'add_time' => date('YmdHis'));
        M()->startTrans();
        $result = M('twx_firecrackers_friend_trace')->add($data);
        if (! $result) {
            M()->rollback();
            return false;
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $from_user_id);
        $data_arr = array(
            'game_number' => array(
                'exp', 
                'game_number+1'));
        $query = M('twx_firecrackers_score')->where($map)->save($data_arr);
        if (! $query) {
            M()->rollback();
            return false;
        }
        
        M()->commit();
        session('from_user_id', NULL);
        return true;
    }
    
    // 当前用户的好友，总分，总小球数
    protected function MyCount() {
        $map = array(
            'node_id' => $this->node_id, 
            'from_user_id' => $this->wxid, 
            'batch_id' => $this->batch_id);
        $count = M('twx_firecrackers_friend_trace')->where($map)->count();
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $sum_arr = M('twx_firecrackers_score')->where($map)->find();
        
        $all_game_number = $sum_arr['game_number'];
        $all_score = $sum_arr['score'];
        $array = array(
            'fcount' => $count, 
            'all_game_number' => $all_game_number, 
            'all_score' => $all_score);
        return $array;
    }
    
    // 发送游戏分数
    public function gameScore() {
        $score = I('score', '', 'intval');
        $success_num = I('success_num', '', 'intval');
        if ($score < 0) {
            $this->responseJson(- 1, '参数错误');
        }
        if ($score > self::MAX_SCORE) {
            $this->responseJson(- 1, '非法数值!');
        }
        M()->startTrans();
        // 能不能玩
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $info = M('twx_firecrackers_score')->where($map)->find();
        if ($info['game_number'] <= 0) {
            M()->rollback();
            $this->responseJson(- 1, '不能再玩了');
        }
        
        // 记录游戏流水
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid, 
            'score' => $score, 
            'success_num' => $success_num, 
            'add_time' => date('YmdHis'));
        
        $query = M('twx_firecrackers_trace')->add($data);
        if (! $query) {
            M()->rollback();
            $this->responseJson(- 1, '流水记录失败');
        }
        
        // 减去已经掉的小球
        $opt_arr = array(
            'game_number' => array(
                'exp', 
                'game_number-1'), 
            'score' => array(
                'exp', 
                'score+' . $score), 
            'ranking_count' => array(
                'exp', 
                'ranking_count+' . $score));
        $query = M('twx_firecrackers_score')->where($map)->save($opt_arr);
        if (! $query) {
            M()->rollback();
            $this->responseJson(- 1, '统计失败');
        }
        
        // 统计到总分中
        $query = $this->_updateTotalScore($this->node_id, $score);
        if (! $query) {
            M()->rollback();
            log_write("统计总分失败 [sql]" . M()->_sql()) . '[error]' .
                 M()->getDbError();
            $this->responseJson(- 1, '统计总分失败');
        }
        M()->commit();
        log_write('wxid:' . $this->wxid . ',score:' . $score);
        $this->responseJson(0, '成功啦');
    }
    
    // 领取奖品
    public function submitCj() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        $mobile = I('mobile', NULL);
        $cate_id = I('cate_id', NULL);
        
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
            $this->ajaxReturn("error", "请正确填写手机号！", 0);
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $wx_wap_ranking_id = M('twx_firecrackers_score')->where($map)->getField(
            'id');
        
        if (empty($cate_id) || empty($wx_wap_ranking_id)) {
            $this->ajaxReturn("error", "参数错误！" . $cate_id . $wx_wap_ranking_id, 
                0);
        }
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'id' => $cate_id);
        $score = M('tcj_cate')->where($map)->getField('score');
        $mycount = $this->MyCount();
        if ((int) $score > (int) $mycount['all_score']) {
            $this->ajaxReturn("error", "未达到该奖项的金币！", 0);
        }
        
        // 每个奖项只能领取一次
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'cate_id' => $cate_id, 
            'user_id' => $wx_wap_ranking_id, 
            'status' => '2');
        $cjcount = M('tcj_trace')->where($map)->count();
        if ($cjcount > 0) {
            $this->ajaxReturn("error", "该奖项只能领取一次", 0);
        }
        
        // 减去领奖的积分
        M()->startTrans();
        $save = array(
            'score' => array(
                'exp', 
                'score-' . $score));
        $query = M('twx_firecrackers_score')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid))->save($save);
        if ($query === false) {
            M()->rollback();
            $this->ajaxReturn("error", "金币扣减失败！", 0);
        }
        
        $other_arr = array(
            'wx_wap_ranking_id' => $wx_wap_ranking_id, 
            'wx_cjcate_id' => $cate_id);
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id, '', 
            $other_arr);
        $resp = $choujiang->send_code();
        if ($resp['resp_id'] == '0000') {
            M()->commit();
            $this->ajaxReturn("sucess", "领取成功", 1);
        } else {
            M()->rollback();
            $fail_arr = array(
                '1001', 
                '1002', 
                '1003', 
                '1006', 
                '1005', 
                '1016', 
                '1014');
            if (in_array($resp['resp_id'], $fail_arr))
                $resp_msg = "您来晚了，该奖品已经被抢光了";
            else
                $resp_msg = "很遗憾领取失败";
            
            $this->ajaxReturn("error", $resp_msg, 0);
        }
    }
    
    // 自己赠送弹窗(限制第一次弹)
    protected function myOpen() {
        // 判断赠送的次数
        $send_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'send_user_id' => $this->wxid);
        $mycount = M('twx_firecrackers_friend_trace')->where($send_arr)->count();
        if ($mycount != 1)
            return false;
        
        $from_user_id = M('twx_firecrackers_friend_trace')->where($send_arr)->getField(
            'from_user_id');
        $nickname = M('twx_wap_user')->where(
            array(
                'id' => $from_user_id))->getField('nickname');
        
        return $nickname;
    }
    
    // 其他人赠送给我的记录
    protected function otherOpen() {
        $send_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $this->wxid, 
            'from_flag' => '1');
        $mycount = M('twx_firecrackers_friend_trace')->where($send_arr)->count();
        $result = M('twx_firecrackers_friend_trace')->where($send_arr)->find();
        $mycount = (int) $mycount;
        if ($mycount == 0)
            return false;
        
        $nickname = M('twx_wap_user')->where(
            array(
                'id' => $result['send_user_id']))->getField('nickname');
        
        $resp_arr = array(
            'count' => $mycount, 
            'nickname' => $nickname);
        
        // 更新成已读
        M('twx_firecrackers_friend_trace')->where($send_arr)->save(
            array(
                'from_flag' => '2'));
        return $resp_arr;
    }
    
    // 更新状态
    public function updateStatus() {
        $from_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $this->wxid, 
            'from_flag' => '1');
        
        $send_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'send_user_id' => $this->wxid, 
            'send_flag' => '1');
        $data_arr = array(
            'status' => '2');
        M('twx_firecrackers_friend_trace')->where($from_arr)->save($data_arr);
        M('twx_firecrackers_friend_trace')->where($send_arr)->save($data_arr);
        $this->success('更新成功');
    }

    protected function _setGlobalJs($arr = array()) {
        $this->js_global = array_merge($this->js_global, $arr);
        $this->assign('js_global', json_encode($this->js_global));
    }
    
    // 响应json
    protected function responseJson($code, $msg, $data = array(), $debug = array()) {
        if (IS_AJAX) {
            header('Content-type:text/json;charset=utf-8');
        }
        $resp = array(
            'code' => $code, 
            'msg' => $msg, 
            'data' => $data);
        if (C('SHOW_PAGE_TRACE')) {
            $resp['debug'] = $debug;
        }
        log_write(print_r($resp, true), 'RESPONSE');
        echo json_encode($resp);
        tag('view_end');
        exit();
    }
    
    // 更新机构总分数
    protected function _updateTotalScore($node_id, $score) {
        if ($score <= 0)
            return true;
        $dao = M('twx_firecrackers_total_score');
        $result = $dao->where(array(
            'node_id' => $node_id))->save(
            array(
                'total_score' => array(
                    'exp', 
                    'total_score+' . $score)));
        if (! $result) {
            $result = $dao->add(
                array(
                    'node_id' => $node_id, 
                    'm_id' => $this->batch_id, 
                    'total_score' => $score));
            return $result;
        }
        return true;
    }

    /**
     * 企业排行 @auther tr
     */
    public function pkRanking() {
        $node_id = $this->node_id;
        // 计算加过的炮区
        $community_arr = M('twx_firecrackers_community_relation')->alias('a')
            ->field('a.join_node_id,a.community_id,b.*')
            ->join(
            'left join twx_firecrackers_community b on b.id=a.community_id')
            ->where(array(
            'a.join_node_id' => $node_id))
            ->order('b.id asc')
            ->select() or $community_arr = array();
        if (! $community_arr) {
            $this->assign('nextPageUrl', '');
            $this->assign('ranking_list', array());
            $this->assign('community_arr', $community_arr);
            $this->assign('my_rank', '-');
            $this->display();
            return;
        }
        if ($community_arr) {
            $community_arr = array_valtokey($community_arr, 'community_id');
        }
        $communityInfo = current($community_arr);
        // 计算排名
        $community_id = $_GET['community_id'] = I('community_id', 
            $communityInfo['community_id']);
        $communityInfo = ! empty($community_arr[$community_id]) ? $community_arr[$community_id] : array();
        $nodeRank = $this->_getNodeRank($node_id, $community_id, 
            $communityInfo['match_end_time'] < date('Ymd'));
        $my_rank = $nodeRank['rn'];
        /*
         * //过期 if($communityInfo['match_end_time'] < date('Ymd')){ //查询出分数
         * $total_score = M('twx_firecrackers_community_score')->where(array(
         * 'community_id'=>$community_id, 'join_node_id'=>$node_id,
         * ))->getField('total_score'); $my_rank =
         * M('twx_firecrackers_community_relation')->alias('a') ->join('left
         * join twx_firecrackers_community_score b on
         * b.community_id=a.community_id and b.node_id=a.join_node_id')
         * ->where(array( 'b.total_score'=>array('gt',$total_score),
         * 'a.community_id'=>$community_id ))->count(); } else{ //查询出分数
         * $total_score = M('twx_firecrackers_total_score')->where(array(
         * 'join_node_id'=>$node_id, ))->getField('total_score'); $my_rank =
         * M('twx_firecrackers_community_relation')->alias('a') ->join('left
         * join twx_firecrackers_total_score b on b.node_id=a.join_node_id')
         * ->where(array( 'b.total_score'=>array('gt',$total_score),
         * 'a.community_id'=>$community_id ))->count(); }
         */
        // 查询出排名并分页
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = 100;
        $Page = new Page($mapcount, 10);
        $_GET['p'] = I('get.p', 1);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        $nextPageUrl = U('', 
            array_merge($_GET, 
                array(
                    'p' => $_GET['p'] + 1)));
        if ($communityInfo['match_end_time'] < date('Ymd')) {
            $ranking_list = M('twx_firecrackers_community_relation')->alias('a')
                ->field('a.join_node_id,c.head_photo,c.node_name,b.total_score')
                ->join(
                'left join twx_firecrackers_community_score b on b.community_id=a.community_id and b.node_id=a.join_node_id')
                ->join('left join tnode_info c on c.node_id=a.join_node_id')
                ->where(
                array(
                    'a.community_id' => $community_id))
                ->order("b.total_score desc")
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        } else {
            $ranking_list = M('twx_firecrackers_community_relation')->alias('a')
                ->field('a.join_node_id,c.head_photo,c.node_name,b.total_score')
                ->join(
                'left join twx_firecrackers_total_score b on b.node_id=a.join_node_id')
                ->join('left join tnode_info c on c.node_id=a.join_node_id')
                ->where(
                array(
                    'a.community_id' => $community_id))
                ->order("b.total_score desc")
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        }
        foreach ($ranking_list as &$v) {
            $v['total_score'] = $v['total_score'] * 1;
            $v['head_photo'] = get_upload_url($v['head_photo'], 
                '' . $v['join_node_id']);
        }
        unset($v);
        $this->assign('community_arr', $community_arr);
        $this->assign('ranking_list', $ranking_list);
        $this->assign('my_rank', $my_rank);
        $this->assign('nextPageUrl', $nextPageUrl);
        $this->display();
    }
    
    // 获取排名和分数
    protected function _getNodeRank($node_id, $community_id, $end) {
        $sql = "SELECT rn,total_score FROM
            (
                SELECT t1.*, (@num1:=@num1+1) AS rn
                FROM (
                SELECT
                  a.`join_node_id`,a.`community_id`,b.*
                FROM
                  twx_firecrackers_community_relation a
                  " .
             ($end ? 
            // 过期数所
            " LEFT JOIN twx_firecrackers_community_score b ON b.node_id = a.join_node_id and b.community_id=a.community_id" : 
            // 未过期
            " LEFT JOIN twx_firecrackers_total_score b ON b.node_id = a.join_node_id") . "
                    WHERE a.community_id='{$community_id}'
                ORDER BY b.`total_score` desc
                ) AS t1  CROSS JOIN (SELECT @num1 := 0) t2
            ) t3
            WHERE t3.join_node_id='{$node_id}'";
        $result = M()->query($sql);
        return $result ? current($result) : array();
    }

    /*
     * 根据不同机构显示不同的名称
     */
    protected function _setLang() {
        L('DA_PAO', '打炮');
        L('PAO_YOU', '炮友');
        L('IMAGE_TITLE', 'title.png');
        L('IMAGE_GIRL', 'girl.png');
        L('IMAGE_ICON', 'icon.png');
        L('CLASS_QUN_PI', ''); // 群p
        
        if (in_array($this->node_id, 
            array(
                '00043873',  // 033508 海南光大信用卡
                '00043876',  // 033515 新闻网
                            // 以下是测试的
                '00004488',  // 91@7005.com.cn
                '00004506'))) // 93@7005.com.cn
{
            L('DA_PAO', '放炮');
            L('PAO_YOU', '好友');
            L('IMAGE_TITLE', 'title-2.png'); // 改放炮图
            L('IMAGE_ICON', 'icon-2.png'); // 改放炮图
            L('CLASS_QUN_PI', 'dn');
        }
        
        if (in_array($this->node_id, 
            array(
                '00043873',  // 033508 海南光大信用卡
                '00004488'))) {
            L('IMAGE_GIRL', 'girl-2.png'); // 福娃
        }
        
        if (in_array($this->node_id, 
            array(
                '00043876',  // 033515 新闻网
                '00004506'))) {
            L('IMAGE_GIRL', 'girl-3.png'); // 不要美女
        }
        
        if (in_array($this->node_id, 
            array(
                '00000370',  // 033508 中国移动通信集团湖南有限公司长沙分公司
                '00004488'))) {
            L('DA_PAO', '放烟火');
            L('PAO_YOU', '好友');
            L('IMAGE_TITLE', 'title-3.png');
            L('IMAGE_GIRL', 'girl-4.png'); // 元宵节
            L('IMAGE_ICON', 'icon-3.png'); // 元宵节
            $this->assign('goto_button', 
                array(
                    'name' => '关注赢取iPhone6', 
                    'url' => 'http://mp.weixin.qq.com/s?__biz=MjM5ODI5NzgzNA==&mid=206820240&idx=1&sn=cda69501f0bf954813d014a2417352b9#rd'));
        }
        
        if (in_array($this->node_id, 
            array(
                '00043263',  // 32912 长沙银行股份有限公司（信用卡部）
                '00004488'))) // 测试
{
            L('DA_PAO', '放烟火');
            L('PAO_YOU', '好友');
            L('IMAGE_TITLE', 'title-3.png');
            L('IMAGE_GIRL', 'girl-5.png'); // 元宵节
            L('IMAGE_ICON', 'icon-3.png'); // 烟火
        }
    }
}