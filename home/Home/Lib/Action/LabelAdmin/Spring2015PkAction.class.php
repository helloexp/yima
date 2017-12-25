<?php

/**
 * 春节打炮活动 特殊说明 defined_one_name 字段为 旺币赠送状态 0不送 1失败 2成功 @auther tr
 */
class Spring2015PkAction extends BaseAction {

    const MAX_JOIN_NUM = 3;
    // 最大加入数
    public $_authAccessMap = '*';

    const BATCH_TYPE_SPRING = 42;
    // 活动类型
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    // 图片路径
    public $img_path;

    public $now_time;
    
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE_SPRING;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE_SPRING);
        $this->now_time = date('YmdHis');
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('batch_name', $this->BATCH_NAME);
    }

    /**
     * 上传图片
     */
    public function uploadImg() {
        import('@.ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1000; // 设置附件上传大小 1兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $upload->savePath = C('UPLOAD'); // 设置附件上传目录
        $upload->autoSub = true; // 自动子目录
        $upload->subType = 'custom'; // 自动子目录
        $upload->subDir = $this->node_id . '/' . date('Y/m/d/'); // 自动子目录
        if (! $upload->upload()) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'info' => $upload->getErrorMsg(), 
                        'status' => 1)));
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
            exit(
                json_encode(
                    array(
                        'info' => array(
                            'fileId' => 0, 
                            'imgName' => $info['savename'], 
                            'imgUrl' => get_upload_url($info['savename'])), 
                        'status' => 0)));
        }
    }

    /**
     * 首页
     */
    public function index() {
        $dao = M('twx_firecrackers_community'); // 炮区
        $count = $dao->order("id asc")->count();
        $perpage = 10;
        $p = $_GET['p'] = I('get.p', 1);
        if (ceil($count / $perpage) >= $p) {
            $_query = array_merge($_GET, 
                array(
                    'p' => $p + 1));
            $nextUrl = U('', $_query);
        } else {
            $nextUrl = '';
        }
        $this->assign('nextUrl', $nextUrl);
        $where = array();
        // 查询是否已加入
        $is_joined = $_GET['is_joined'] = I('get.is_joined');
        if ($is_joined) {
            $where['_string'] = " exists (select 1 from twx_firecrackers_community_relation where
                join_node_id='" . $this->node_id . "'
                and community_id=a.id
            )";
        }
        $list = $dao->alias('a')
            ->field('a.*,b.node_name')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->where($where)
            ->order('a.id asc')
            ->limit(($p - 1) * $perpage, $perpage)
            ->select() or $list = array();
        // 查询所有加入过的炮区
        $joinedArr = M('twx_firecrackers_community_relation')->where(
            array(
                'join_node_id' => $this->node_id))->getField('id k,community_id');
        // 处理一下数据
        $today = date('Ymd');
        foreach ($list as &$v) {
            // 炮区统计
            if ($v['match_end_time'] < $today) {
                $countArr = M('twx_firecrackers_community_relation')->alias('a')
                    ->join(
                    'left join twx_firecrackers_community_score b on b.node_id=a.join_node_id and b.community_id=a.community_id')
                    ->join('left join tnode_info c on c.node_id=a.join_node_id')
                    ->where(
                    array(
                        'a.community_id' => $v['id'], 
                        'c.wc_version' => array(
                            'neq', 
                            'v4')))
                    ->
                // 排除v4演示版
                
                field("count(*) friend_count,sum(b.total_score) score_count")
                    ->find();
            } else {
                $countArr = M('twx_firecrackers_community_relation')->alias('a')
                    ->join(
                    'left join twx_firecrackers_total_score b on b.node_id=a.join_node_id')
                    ->join('left join tnode_info c on c.node_id=a.join_node_id')
                    ->where(
                    array(
                        'a.community_id' => $v['id'], 
                        'c.wc_version' => array(
                            'neq', 
                            'v4')))
                    ->
                // 排除v4演示版
                
                field("count(*) friend_count,sum(b.total_score) score_count")
                    ->find();
            }
            $friend_count = $countArr['friend_count'] * 1;
            $score_count = $countArr['score_count'] * 1;
            // 查询是否加入过
            $is_joined = ($joinedArr && in_array($v['id'], $joinedArr)) ? 1 : 0;
            $v['is_joined'] = $is_joined;
            $v['friend_count'] = $friend_count;
            $v['score_count'] = $score_count;
            $v['community_pic'] = $v['community_pic_flag'] ? $v['community_pic'] : '';
        }
        unset($v);
        
        // 计算自已的炮区
        $myCommunity = M('twx_firecrackers_community')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('myCommunity', $myCommunity);
        
        $this->assign('node_id', $this->node_id);
        $this->assign('queryList', $list);
        $this->assign('nextUrl', $nextUrl);
        $this->display(); // 输出模板
    }

    /*
     * 创建炮区提交 .json
     */
    public function createRoomSubmit() {
        // 判断当前版本号是否演示版
        if ($this->wc_version == 'v4') {
            $this->error("演示版账户不能创建炮区");
        }
        $community_title = I('community_title'); // 口号
        $enroll_end_time = I('enroll_end_time'); // 参炮截止日期
        $match_end_time = I('match_end_time'); // 比赛截止日期
        $community_pic = I('community_pic'); // 炮区图片
        $community_pic_flag = I('community_pic_flag', '0'); // 炮区图片标识
        $friend_limit_flag = I('friend_limit_flag', '0'); // 是否限制炮友
        $friend_limit = I('friend_limit', '0'); // 炮友个数
        if (! $friend_limit_flag) {
            $friend_limit = 0;
        }
        $id = I('id');
        if ($id) {
            $info = M('twx_firecrackers_community')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $id))->find();
            if (! $info) {
                $this->error("你无权编辑此炮区");
            }
        } else {
            // 只能创建一个炮区
            $info = M('twx_firecrackers_community')->where(
                array(
                    'node_id' => $this->node_id))->find();
            // to-do debug
            if ($info) {
                $this->error('你只能创建一个炮区', 
                    array(
                        '返回' => "javascript:history.back();"));
            }
        }
        
        $award_flag = I('award_flag', '0'); // 是否设置奖励
        $award_content = I('award_content'); // 奖励说明
        $secret_flag = I('secret_flag', '0'); // 是否设置暗号
        $secret_question = I('secret_question'); // 暗号问
        $secret_answer = I('secret_answer'); // 暗号答
        if ($secret_flag) {
            // to--do 校验问题 和暗号只能是数字，字母，或中文
            if (! $this->_checkWord($secret_question)) {
                $this->error("暗号问题只允许中文,字母或数字", 
                    array(
                        '返回' => "javascript:history.back();"));
            }
            if (! $this->_checkWord($secret_answer)) {
                $this->error("暗号答案只允许中文,字母或数字", 
                    array(
                        '返回' => "javascript:history.back();"));
            }
        } else {
            $secret_question = '';
            $secret_answer = '';
        }
        
        // 判断截止日期
        $today = date('Ymd');
        if (dateformat($enroll_end_time, 'Ymd') < $today) {
            $this->error("参炮截止日期不能少于当前日期", 
                array(
                    '返回' => "javascript:history.back();"));
        }
        if (dateformat($match_end_time, 'Ymd') < $today) {
            $this->error("比赛截止日期不能少于当前日期", 
                array(
                    '返回' => "javascript:history.back();"));
        }
        if (dateformat($match_end_time, 'Ymd') <
             dateformat($match_end_time, 'Ymd')) {
            $this->error("比赛截止日期不能少于参炮截止日期", 
                array(
                    '返回' => "javascript:history.back();"));
        }
        
        // 编辑
        if ($id) {
            $data = array(
                'community_title' => $community_title, 
                'enroll_end_time' => $enroll_end_time, 
                'match_end_time' => $match_end_time, 
                'community_pic' => $community_pic, 
                'community_pic_flag' => $community_pic_flag, 
                'friend_limit' => $friend_limit, 
                'award_flag' => $award_flag, 
                'award_content' => $award_content, 
                'secret_flag' => $secret_flag, 
                'secret_question' => $secret_question, 
                'secret_answer' => $secret_answer);
            $result = M('twx_firecrackers_community')->where(
                array(
                    'id' => $id, 
                    'node_id' => $this->node_id))->save($data);
            if ($result === false) {
                $this->error("系统正忙01");
            }
            $this->success("编辑成功", 
                array(
                    '返回' => U('index')));
        }  // 添加
else {
            // 计算当前炮区数
            $count = M('twx_firecrackers_community')->count();
            $count = $count + 1;
            $data = array(
                'community_name' => $count . '炮区', 
                'community_title' => $community_title, 
                'enroll_end_time' => $enroll_end_time, 
                'match_end_time' => $match_end_time, 
                'community_pic' => $community_pic, 
                'community_pic_flag' => $community_pic_flag, 
                'friend_limit' => $friend_limit, 
                'award_flag' => $award_flag, 
                'award_content' => $award_content, 
                'secret_flag' => $secret_flag, 
                'secret_question' => $secret_question, 
                'secret_answer' => $secret_answer, 
                'node_id' => $this->node_id, 
                'created_time' => $this->now_time);
            $result = M('twx_firecrackers_community')->add($data);
            if ($result === false) {
                $this->error("系统正忙01");
            }
            $community_id = $result;
            
            /*
             * //默认加到自已的炮区 $result =
             * M('twx_firecrackers_community_relation')->add(array(
             * 'community_id'=>$community_id, 'node_id'=>$this->node_id,
             * 'add_time'=>date('YmdHis'), 'join_node_id' => $this->node_id,
             * 'contact_name' => '炮主', 'contact_mobile'=> '' )); if($result ===
             * false ){ $this->error("系统正忙01"); }
             */
            
            $this->success("添加成功", 
                array(
                    '返回' => U('index')));
        }
    }

    /**
     * 加入炮区
     */
    public function joinRoomSubmit() {
        // 判断当前版本号是否演示版
        if ($this->wc_version == 'v4') {
            $this->error("演示版账户不能加入炮区");
        }
        $community_id = I('community_id');
        $secret_answer = I('secret_answer');
        $contact_name = I('contact_name');
        $contact_mobile = I('contact_mobile');
        $join_node_id = $this->node_id;
        // 查询一下炮区有效性
        $communityInfo = M('twx_firecrackers_community')->where(
            array(
                'id' => $community_id))->find();
        if (! $communityInfo) {
            $this->error("无此炮区", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        $relation_dao = M('twx_firecrackers_community_relation');
        // 查询一下是否加入过
        $result = $relation_dao->where(
            array(
                'community_id' => $community_id, 
                'join_node_id' => $join_node_id))->find();
        if ($result) {
            $this->error("你已经加入过此炮区", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        // 查询暗号
        if ($communityInfo['secret_flag'] &&
             $communityInfo['secret_answer'] != $secret_answer) {
            $this->error("暗号不对", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        // 校验加入时间
        if (trim($communityInfo['enroll_end_time']) < date('Ymd')) {
            $this->error("参赛时间已经结束", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        // 校验参赛时间
        if (trim($communityInfo['match_end_time']) < date('Ymd')) {
            $this->error("比赛时间已经结束", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        // 校验人数限制
        if ($communityInfo['friend_limit']) {
            // 计算已参加的人数
            $count = M('twx_firecrackers_community_relation')->where(
                array(
                    'community_id' => $communityInfo['id']))->count();
            if ($count >= $communityInfo['friend_limit']) {
                $this->error("已达到最大人数" . $communityInfo['friend_limit']);
            }
        }
        // 炮友在线加入任何一个赛区的前提条件是必须开展了打炮总动员营销活动；
        $exists = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_SPRING))->find();
        if (! $exists) {
            $this->error("您未创建春节打炮总动员", 
                array(
                    '立即创建' => U('LabelAdmin/Spring2015/index')));
        }
        // 炮友最多可加入5个不同赛区，确认加入后不可退赛；
        $count = M('twx_firecrackers_community_relation')->where(
            array(
                'join_node_id' => $this->node_id))->count();
        if ($count >= self::MAX_JOIN_NUM) {
            $this->error("你最多只能加入" . self::MAX_JOIN_NUM . '个炮区');
        }
        // 加入
        $result = M('twx_firecrackers_community_relation')->add(
            array(
                'community_id' => $community_id, 
                'node_id' => $communityInfo['node_id'], 
                'add_time' => date('YmdHis'), 
                'join_node_id' => $join_node_id, 
                'contact_name' => $contact_name, 
                'contact_mobile' => $contact_mobile));
        if ($result === false) {
            $this->error("加入失败");
        }
        $this->success("加入成功", 
            array(
                "返回" => U('index'), 
                "查看排名" => U('rank', 
                    array(
                        'community_id' => $community_id))));
    }

    /*
     * 炮区详情
     */
    public function detail() {
        $id = I('get.id');
        $info = M('twx_firecrackers_community')->alias('a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where(array(
            'a.id' => $id))
            ->find();
        if (! $info) {
            $this->error("数据不存在");
        }
        $info['community_pic_url'] = get_upload_url($info['community_pic']) or $info['community_pic_url'] = C(
            'TMPL_PARSE_STRING.__PUBLIC__') . '/Image/edm/20150126-16.png';
        $info['award_content'] = html_entity_decode($info['award_content']);
        $info['community_pic_flag'] = intval($info['community_pic_flag']);
        
        // 炮区统计
        $today = date('Ymd');
        if ($info['match_end_time'] < $today) {
            $countArr = M('twx_firecrackers_community_relation')->alias('a')
                ->join(
                'left join twx_firecrackers_community_score b on b.node_id=a.join_node_id and b.community_id=a.community_id')
                ->join('left join tnode_info c on c.node_id=a.join_node_id')
                ->where(
                array(
                    'a.community_id' => $id, 
                    'c.wc_version' => array(
                        'neq', 
                        'v4')))
                ->
            // 排除v4演示版
            
            field("count(*) friend_count,sum(b.total_score) score_count")
                ->find();
        } else {
            $countArr = M('twx_firecrackers_community_relation')->alias('a')
                ->join(
                'left join twx_firecrackers_total_score b on b.node_id=a.join_node_id')
                ->join('left join tnode_info c on c.node_id=a.join_node_id')
                ->where(
                array(
                    'a.community_id' => $id, 
                    'c.wc_version' => array(
                        'neq', 
                        'v4')))
                ->
            // 排除v4演示版
            
            field("count(*) friend_count,sum(b.total_score) score_count")
                ->find();
        }
        $friend_count = $countArr['friend_count'] * 1;
        $score_count = $countArr['score_count'] * 1;
        $info['friend_count'] = $friend_count;
        $info['score_count'] = $score_count;
        if ($info['node_id'] != $this->node_id) {
            unset($info['secret_answer']); // 不能让别人看到答案
        }
        $this->success($info);
    }

    /**
     * 打炮排名
     */
    public function rank() {
        $community_id = I('community_id');
        // 查询一下炮区有效性
        $communityInfo = M('twx_firecrackers_community')->where(
            array(
                'id' => $community_id))->find();
        if (! $communityInfo) {
            $this->error("无此炮区", 
                array(
                    '返回' => 'javascript:history.back();'));
        }
        $today = date('Ymd');
        $relation_dao = M('twx_firecrackers_community_relation');
        // 过期的话查历史分数表
        if ($communityInfo['match_end_time'] < $today) {
            $list = $relation_dao->alias('a')
                ->field(
                "a.join_node_id,a.node_id,b.total_score,c.node_name,c.head_photo")
                ->join(
                "left join twx_firecrackers_community_score b on b.node_id=a.join_node_id and b.community_id=a.community_id")
                ->join("left join tnode_info c on c.node_id=a.join_node_id")
                ->order("b.total_score desc")
                ->where(
                array(
                    'a.community_id' => $community_id, 
                    'c.wc_version' => array(
                        'neq', 
                        'v4')))
                ->select();
        }  // 否则查当前排行表
else {
            $list = $relation_dao->alias('a')
                ->field(
                "a.join_node_id,a.node_id,b.total_score,c.node_name,c.head_photo")
                ->join(
                "left join twx_firecrackers_total_score b on b.node_id=a.join_node_id")
                ->join("left join tnode_info c on c.node_id=a.join_node_id")
                ->order("b.total_score desc")
                ->where(
                array(
                    'a.community_id' => $community_id, 
                    'c.wc_version' => array(
                        'neq', 
                        'v4')))
                ->select();
        }
        // 计算排行
        $my_rank = 1;
        $my_score = 0;
        $is_joined = false;
        foreach ($list as &$v) {
            $v['total_score'] = $v['total_score'] * 1;
            $v['head_photo'] = get_upload_url($v['head_photo'], 
                '' . $v['join_node_id']);
        }
        unset($v);
        // 计算排名
        foreach ($list as &$v) {
            if ($v['join_node_id'] == $this->node_id) {
                $is_joined = true;
                $my_score = $v['total_score'] * 1;
                break;
            }
            $my_rank ++;
        }
        unset($v);
        
        // 计算自已的炮区
        $myCommunity = M('twx_firecrackers_community')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('myCommunity', $myCommunity);
        
        $this->assign('is_joined', $is_joined);
        $this->assign('my_rank', $my_rank);
        $this->assign('my_score', $my_score);
        $this->assign('info', $communityInfo);
        $this->assign('queryList', $list);
        $this->display();
    }

    protected function _checkWord($str) {
        return (preg_match("/^([\x{4e00}-\x{9fa5}]|[a-zA-Z0-9])*$/u", $str));
    
    }
}