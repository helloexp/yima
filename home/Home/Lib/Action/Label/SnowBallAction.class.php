<?php

class SnowBallAction extends MyBaseAction {
    // 微信用户id
    public $wxid;

    public $maxnum = 2000;

    public function _initialize() {
        // edit by tr
        $this->error("该活动已下线");
        exit();
        
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请使用微信扫码二维码进入活动');
        }
        // session('wxid',48);
        parent::_initialize();
        
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
        if (session('?from_user_id'))
            $this->getFriend();
            
            // 活动信息
        $marketInfo = $this->marketInfo;
        
        // 分享信息
        /*
         * $uparr = C('BATCH_IMG_PATH_NAME'); $shareArr = array(
         * 'shareUrl'=>U('Label/SnowBall/index',array('id'=>$this->id,'from_user_id'=>$this->wxid),'','',TRUE),
         * 'shareTitle'=>$row['name'], //'shareNote'=>$row['wap_info'],
         * 'shareNote'=>'就赐我几个圣诞雪球吧，我要去赚金币啊！',
         * 'shareImg'=>C('CURRENT_DOMAIN').'Home/Public/Label/Image/20141224/icon.png'
         * );
         */
        $shareUrl = U('index', array(
            'id' => $this->id), '', '', TRUE);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => $marketInfo['name'], 
            // 'shareNote'=>$row['wap_info'],
            'desc' => '就赐我几个圣诞雪球吧，我要去赚金币啊！', 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/20141224/icon.png');
        
        $this->assign('row', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
    }
    
    // 首页
    public function index() {
        if ($this->batch_type != '35') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 初始化小秋
        $query = M('twx_wap_ranking')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid))->find();
        if (! $query) {
            $data = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid, 
                'ball_count' => 10);
            $query = M('twx_wap_ranking')->add($data);
            if (! $query)
                $this->error('初始化小球失败！');
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 分享好友不能超过2000
        $farr = array(
            'from_user_id' => $this->wxid);
        $maxsnow = M('twx_wap_friend_trace')->where($farr)->count();
        if ($maxsnow > $this->maxnum) {
            $this->assign('maxsnow', true);
        }
        
        // 雪球数量
        $snow_arr = $this->searchMyInfo();
        // 奖品显示
        $jp_arr = $this->searchJp();
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        M('twx_wap_ranking')->where($data)->setInc('first_count', 1);
        $first_count = M('twx_wap_ranking')->where($data)->getField(
            'first_count');
        
        $index_arr = $this->playRate(false);
        $zong = $this->allRankingCount($this->node_id);
        
        // 弹窗
        $send_open = $this->myOpen();
        $this->assign('send_open', $send_open);
        
        // 其他人赠送给我
        $from_open = $this->otherOpen();
        $this->assign('from_open', $from_open);
        
        $this->assign('index_arr', $index_arr);
        $this->assign('zong', $zong);
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('snow_arr', $snow_arr);
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
        $this->assign('info', $myscore);
        $this->display();
    }
    
    // ajax计算机击败率
    public function playRate($isAjax = true) {
        $array = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id);
        // 总共多少个
        $max_count = M('twx_wap_ranking')->where($array)->count();
        
        // 个人现在分数
        $array['wx_user_id'] = $this->wxid;
        $mycount = M('twx_wap_ranking')->where($array)->getField(
            'ranking_count');
        $myscore = M('twx_wap_ranking')->where($array)->getField('score');
        // 总共大于我的数量
        $array = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'ranking_count' => array(
                'exp', 
                ' >' . $mycount));
        $cycount = M('twx_wap_ranking')->where($array)->count();
        $max_count = $max_count - 1;
        if ($max_count == 0)
            $rate = 0;
        else
            $rate = 100 - round($cycount / $max_count, 2) * 100;
            
            // 差多少可以获得分值最近的奖品
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'score' => array(
                'exp', 
                '>' . $myscore));
        $jp_cate = M('tcj_cate')->where($map)
            ->order('score')
            ->find();
        if ($jp_cate) {
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
        $this_time = date('YmdHis');
        $gettime = '20151231235959';
        $overtime = '20150118235959';
        
        // 个人总统计
        if ($this_time > $gettime) {
            $myranking = M('twx_wap_user_getprize')->where(
                array(
                    'id' => $this->wxid))->find();
            $this->assign('isGetPrize', true);
        } else {
            $myranking = $this->allRankingCount($this->node_id);
        }
        if ($this_time > $overtime) {
            $this->assign('overtime', true);
        }
        
        // 商户排行榜
        $noderanking = $this->allRankingList($this->node_id);
        
        // 奖品显示
        $jp_arr = $this->searchJp();
        
        // 总排行
        // $result = $this->allRankingList();
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = 100;
        $Page = new Page($mapcount, 10);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        if ($this_time > $gettime) {
            $result = M('twx_wap_user_getprize')->field(
                'nickname,headimgurl,score,zj_level,mobile,true_name')
                ->order('ranking asc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $result = M()->table('twx_wap_ranking a')
                ->field('b.nickname,b.headimgurl,a.ranking_count as score')
                ->join('twx_wap_user b on a.wx_user_id=b.id')
                ->where("a.node_id='" . $this->node_id . "'")
                ->order('score desc,a.wx_user_id asc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('Label/SnowBall/ranking', 
            array(
                'id' => $this->id), '', '', true) . '&p=' . ($nowPage + 1);
        $this->assign('nowpage', $nowPage);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('result', $result);
        $this->assign('myranking', $myranking);
        $this->assign('noderanking', $noderanking);
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
        $user_id = M('twx_wap_ranking')->where(
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
    public function snowList() {
        $frow = $this->MyCount();
        
        import('ORG.Util.Page'); // 导入分页类
        
        $mapcount = M()->table('twx_wap_friend_trace a')
            ->join('twx_wap_user b on a.send_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.from_user_id='" . $this->wxid . "'")
            ->count();
        
        $Page = new Page($mapcount, 10);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        
        $friend_arr = M()->table('twx_wap_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.ball_count,a.add_time')
            ->join('twx_wap_user b on a.send_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.from_user_id='" . $this->wxid . "'")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('Label/SnowBall/snowList', 
            array(
                'id' => $this->id), '', '', true) . '&p=' . ($nowPage + 1);
        // $friend_arr = $this->searchFriend();
        $this->assign('friend_arr', $friend_arr);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('frow', $frow);
        $this->display();
    }
    
    // 赠送给别人列表
    public function snowGiveList() {
        import('ORG.Util.Page'); // 导入分页类
        
        $mapcount = M()->table('twx_wap_friend_trace a')
            ->join('twx_wap_user b on a.from_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.send_user_id='" . $this->wxid . "'")
            ->count();
        
        $Page = new Page($mapcount, 10);
        if ($_GET['p'] > ceil($mapcount / 10) && $this->isAjax())
            return;
        
        $friend_arr = M()->table('twx_wap_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.ball_count,a.add_time')
            ->join('twx_wap_user b on a.from_user_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->batch_id . "' and a.send_user_id='" . $this->wxid . "'")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nextUrl = U('Label/SnowBall/snowList', 
            array(
                'id' => $this->id), '', '', true) . '&p=' . ($nowPage + 1);
        // $friend_arr = $this->searchFriend();
        $this->assign('friend_arr', $friend_arr);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('frow', $mapcount);
        $this->display();
    }
    
    // 查询奖品
    protected function searchJp() {
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
        $result = M()->table('twx_wap_ranking a')
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
        $myranking = M('twx_wap_ranking')->where($map)->find();
        
        // 当前排名按分数计算再按先后参与计算
        $sort_arr = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'score' => array(
                'exp', 
                '> ' . $myranking['score']));
        
        $ranking1 = M('twx_wap_ranking')->where($sort_arr)->count();
        
        $sort_arr['score'] = $myranking['score'];
        $sort_arr['wx_user_id'] = array(
            'exp', 
            '<= ' . $myranking['wx_user_id']);
        
        $ranking2 = M('twx_wap_ranking')->where($sort_arr)->count();
        
        $myranking['ranking'] = (int) $ranking1 + (int) $ranking2;
        return $myranking;
    }
    
    // 个人总排行查询
    protected function allRankingCount($node_id = NULL) {
        // 全局排行
        if (empty($node_id)) {
            $myranking = M('twx_wap_ranking')->field(
                'sum(ranking_count) as ranking_count,wx_user_id')
                ->where(
                array(
                    'wx_user_id' => $this->wxid))
                ->group('wx_user_id')
                ->find();
            
            $query = M()->query(
                "select count(*) as count from (SELECT id FROM twx_wap_ranking 
					   GROUP BY wx_user_id HAVING sum(ranking_count)>'" .
                     $myranking['ranking_count'] . "') tab");
            $ranking1 = $query[0]['count'];
            
            $query = M()->query(
                "select count(*) as count from (SELECT id FROM twx_wap_ranking WHERE ( wx_user_id<='" .
                     $this->wxid .
                     "' ) GROUP BY wx_user_id HAVING sum(ranking_count)='" .
                     $myranking['ranking_count'] . "') tab");
            $ranking2 = $query[0]['count'];
            $myranking['ranking'] = (int) $ranking1 + (int) $ranking2;
            
            $myranking['score'] = $myranking['ranking_count'];
            return $myranking;
        } else // 商户中排行
{
            $myranking = M('twx_wap_ranking')->field(
                'ranking_count as ranking_count,wx_user_id')
                ->where(
                array(
                    'node_id' => $node_id, 
                    'wx_user_id' => $this->wxid))
                ->find();
            
            $query = M()->query(
                "select count(*) as count from (SELECT id FROM twx_wap_ranking where node_id ='" .
                     $node_id . "' and ranking_count >'" .
                     $myranking['ranking_count'] . "') tab");
            $ranking1 = $query[0]['count'];
            
            $query = M()->query(
                "select count(*) as count from (SELECT id FROM twx_wap_ranking WHERE node_id ='" .
                     $node_id . "' and wx_user_id<='" . $this->wxid .
                     "' and ranking_count='" . $myranking['ranking_count'] .
                     "') tab");
            $ranking2 = $query[0]['count'];
            $myranking['ranking'] = (int) $ranking1 + (int) $ranking2;
            
            $myranking['score'] = $myranking['ranking_count'];
            return $myranking;
        }
    }
    
    // 总排行前十名
    protected function allRankingList($node_id = NULL) {
        $wh = '1=1';
        if (! empty($node_id)) {
            $wh .= " and a.node_id = '" . $node_id . "' ";
        }
        $result = M()->table('twx_wap_ranking a')
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
        $result = M()->table('twx_wap_friend_trace a')
            ->field('b.nickname,b.headimgurl,a.ball_count,a.add_time')
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
        if ($overdue === false)
            return false;
        
        $from_user_id = session('from_user_id');
        $query = M('twx_wap_user')->where(
            array(
                'id' => $from_user_id))->find();
        if (! $query) {
            session('from_user_id', NULL);
            return false;
        }
        
        // 分享好友不能超过2000
        $farr = array(
            'from_user_id' => $from_user_id);
        $maxsnow = M('twx_wap_friend_trace')->where($farr)->count();
        log_write(
            'from_user_id=' . $from_user_id . ', 分享数=' . $maxsnow . ', wxid=' .
                 $this->wxid . ', id=' . $this->id);
        if ($maxsnow > $this->maxnum) {
            log_write(
                "分享好友超过:" . $this->maxnum . ' from_user_id:' . $from_user_id, 
                '异常数据');
            session('from_user_id', NULL);
            return false;
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $from_user_id, 
            'send_user_id' => $this->wxid);
        $query = M('twx_wap_friend_trace')->where($map)->find();
        if ($query) {
            session('from_user_id', NULL);
            return false;
        }
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'from_user_id' => $from_user_id, 
            'send_user_id' => $this->wxid, 
            'ball_count' => 5, 
            'add_time' => date('YmdHis'));
        M()->startTrans();
        $result = M('twx_wap_friend_trace')->add($data);
        if (! $result) {
            M()->rollback();
            return false;
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $from_user_id);
        $data_arr = array(
            'ball_count' => array(
                'exp', 
                'ball_count+5'));
        $query = M('twx_wap_ranking')->where($map)->save($data_arr);
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
        $count = M('twx_wap_friend_trace')->where($map)->count();
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $sum_arr = M('twx_wap_ranking')->where($map)->find();
        
        $all_ball_count = $sum_arr['ball_count'];
        $all_score = $sum_arr['score'];
        $array = array(
            'fcount' => $count, 
            'all_ball_count' => $all_ball_count, 
            'all_score' => $all_score);
        return $array;
    }
    
    // 玩游戏
    public function game() {
        $score = I('score');
        $score = (int) $score;
        if ($score < 0) {
            $this->ajaxReturn("error", "参数错误！", 0);
        }
        if ($score > 99)
            $this->ajaxReturn("error", "非法数值！", 0);
        M()->startTrans();
        // 能不能玩
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $info = M('twx_wap_ranking')->where($map)->find();
        if ($info['ball_count'] <= 0) {
            M()->rollback();
            $this->ajaxReturn("error", "没有小球了" . $this->node_id, 0);
        }
        
        // 记录游戏流水
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid, 
            'score' => $score, 
            'type' => $type, 
            'add_time' => date('YmdHis'));
        $query = M('twx_wap_game_trace')->add($data);
        if (! $query) {
            M()->rollback();
            $this->ajaxReturn("error", "流水记录失败", 0);
        }
        
        // 减去已经掉的小球
        $opt_arr = array(
            'ball_count' => array(
                'exp', 
                'ball_count-1'), 
            'score' => array(
                'exp', 
                'score+' . $score), 
            'ranking_count' => array(
                'exp', 
                'ranking_count+' . $score));
        $query = M('twx_wap_ranking')->where($map)->save($opt_arr);
        if (! $query) {
            M()->rollback();
            $this->ajaxReturn("error", "统计失败", 0);
        }
        M()->commit();
        $this->ajaxReturn("success", "成功啦", 1);
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
        $wx_wap_ranking_id = M('twx_wap_ranking')->where($map)->getField('id');
        
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
        $query = M('twx_wap_ranking')->where(
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
        $mycount = M('twx_wap_friend_trace')->where($send_arr)->count();
        if ($mycount != 1)
            return false;
        
        $from_user_id = M('twx_wap_friend_trace')->where($send_arr)->getField(
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
        $mycount = M('twx_wap_friend_trace')->where($send_arr)->count();
        $result = M('twx_wap_friend_trace')->where($send_arr)->find();
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
        M('twx_wap_friend_trace')->where($send_arr)->save(
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
        M('twx_wap_friend_trace')->where($from_arr)->save($data_arr);
        M('twx_wap_friend_trace')->where($send_arr)->save($data_arr);
        $this->success('更新成功');
    }
    
    // 领奖
    public function updateUser() {
        $true_name = I('true_name', '', 'trim');
        $mobile = I('mobile', '', 'trim');
        if (! $true_name || ! $mobile) {
            $this->error('请提交信息');
        }
        if (strlen($mobile) != '11' || ! is_numeric($mobile)) {
            $this->error('手机号格式错误！');
        }
        $update_arr = array(
            'true_name' => $true_name, 
            'mobile' => $mobile);
        $query = M('twx_wap_user_getprize')->where(
            array(
                'id' => $this->wxid))->save($update_arr);
        if ($query)
            $this->success('成功');
        else
            $this->error('操作失败请重试！');
    }
}