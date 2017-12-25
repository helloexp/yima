<?php

// 首页
class HelpAction extends Action {

    const WANGCAIQITA_ID = 54;

    const WANGQUDAO_ID = 62;

    const WANGYEWU_ID = 63;

    const WANGGUANLI_ID = 64;

    const WANGGONGJU_ID = 65;

    const NUMBER_PER_PAGE = 10;
    // 每页显示10条
    public $userInfo;

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $this->userInfo = $userService->getUserInfo();
        $this->assign('userInfo', $userInfo);
        if ($userInfo) {
            $node_id = $userInfo['node_id'];
            // 查询各种新消息的数量
            $msgArrcount = M()->table('tmessage_stat a')
                ->field('new_message_cnt,message_type')
                ->where(array(
                'a.node_id' => $node_id))
                ->where('a.new_message_cnt > 0')
                ->order('message_type asc')
                ->select();
            $this->assign('msgArrcount', $msgArrcount);
            $unReadMsg = M()->table('tmessage_stat a')
                ->field('sum(new_message_cnt) c')
                ->where(array(
                'a.node_id' => $node_id))
                ->find();
            
            // 判断是否有未读消息
            $unReadMsg = $unReadMsg['c'];
            $this->unReadMsg = $unReadMsg;
            $this->assign('unReadMsg', $unReadMsg);
            
            // 检查快捷菜单子菜单多宝电商，条码支付
            $this->assign('ispowero2o', 
                $this->_checkUserAuth('Ecshop/Index/preview'));
            $this->assign('ispoweralipy', 
                $this->_checkUserAuth('Alipay/Index/index'));
            $this->assign('iswfx', $this->_checkUserAuth('Wfx/Static/index'));
        }
    }
    // 校验当前权限
    protected function _checkUserAuth($name) {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        // 如果没有账户中心权限，则跳转到修改密码界面
        if (! $userService->checkAuth($name, $userInfo['user_id'], 
            $this->nodeInfo)) {
            return false;
        }
        return true;
    }
    // 其他页面跳转至帮助中心
    public function helpArt() {
        $newsId = I('get.news_id') ? I('get.news_id') : I('get.newsId', '');
        $this->redirect('helpDetails', 
            array(
                'newsId' => $newsId));
        exit();
    }

    /**
     * 分类栏目表
     *
     * @return TymClassColumnModel
     */
    private function getTymClassColumnModel() {
        if (empty($this->TymClassColumnModel)) {
            $this->TymClassColumnModel = D('TymClassColumn');
        }
        return $this->TymClassColumnModel;
    }

    /**
     * 文章内容表
     *
     * @return TymNewsModel
     */
    private function getTymNewsModel() {
        if (empty($this->TymNewsModel)) {
            $this->TymNewsModel = D('TymNews');
        }
        return $this->TymNewsModel;
    }

    /**
     * 左侧栏目分类
     *
     * @param $where array 查询条件
     * @param $isXinShou bool 是否为查询新手入门下的分类
     * @return mixed
     */
    public function helpLeft($where = array('_complex'=>array('parent_class_id'=>array('in','44,62'),'_logic'=>'or')), $isXinShou = false) {
        $tymClassColumnModel = $this->getTymClassColumnModel();
        
        if ($isXinShou) {
            // 拿到分类栏目
            $classColumnInfo = $tymClassColumnModel->wangCaiHelp($where, 
                $isXinShou);
            foreach ($classColumnInfo as $key => $value) {
                $classNameNum = strrpos($value['class_name'], '-');
                if ($classNameNum) {
                    $classColumnInfo[$key]['class_name'] = substr(
                        $value['class_name'], $classNameNum + 1);
                    $classColumnInfo[$key]['urlName'] = urlencode(
                        substr($value['class_name'], $classNameNum + 1));
                }
            }
            
            return $classColumnInfo;
        }

        // 拿到分类栏目
        $isXinShou = true;
        $classColumnInfo = $tymClassColumnModel->wangCaiHelp($where, $isXinShou);
        $classColumnArr = [];
        foreach ($classColumnInfo as $key => $value){
            $pathName = explode('-',$value['class_name']);
            if($value['parent_class_id'] == '44' && $value['id'] != '54' && $value['id'] != '62'){
                $classColumnArr[$key] = array(
                    'urlName'           => urlencode($pathName[1]),
                    'urlParName'        => urlencode('帮助中心'),
                    'id'                => $value['id'],
                    'class_name'        => $pathName[1],
                    'parent_class_id'   => $value['parent_class_id'],
                );
            }
            if($value['parent_class_id'] == '62'){
                $classColumnArr['旺渠道'][] = array(
                        'urlName'           => urlencode($pathName[2]),
                        'urlParName'        => urlencode('旺渠道'),
                        'id'                => $value['id'],
                        'class_name'        => $pathName[2],
                        'parent_class_id'   => $value['parent_class_id'],
                );
            }
        }
        return $classColumnArr;
    }

    /**
     * 初始化帮助中心首页
     */
    public function helpConter() {
        $tymNewsModel = $this->getTymNewsModel();
        
        $classColumnArr = $this->helpLeft();
        $newProblemTen = $tymNewsModel->newProblemTitleTen();
        
        foreach ($newProblemTen as $key => $value) {
            $classNameNum = strrpos($value['class_name'], '-');
            if ($classNameNum) {
                $newProblemTen[$key]['class_name'] = substr(
                    $value['class_name'], $classNameNum + 1);
            }
        }
        
        $this->assign('classColumnArr', $classColumnArr); // 左侧栏目(分级)
        $this->assign('newProblemTen', array_filter($newProblemTen)); // 最新的10个问题
        $this->display();
    }

    /**
     * 查询第三级分类下的所有帮助
     */
    public function helpList() {
        $tymNewsModel = $this->getTymNewsModel();
        $classId = I('get.class_id');
        $pathName = I('get.pathName');
        $firstPathName = I('get.firstPathName');
        
        $classColumnArr = $this->helpLeft();
        
        $count = $tymNewsModel->getArticleTitle($classId, true);
        import("ORG.Util.Page");
        $p = new Page($count, self::NUMBER_PER_PAGE);
        $p->parameter = array(
            'class_id' => $classId, 
            'firstPathName' => urlencode($firstPathName), 
            'pathName' => urlencode($pathName));
        $allArticle = $tymNewsModel->getArticleTitle($classId, false, 
            $p->firstRow, $p->listRows);
        $page = $p->show();
        
        $this->assign('firstPathName', urldecode($firstPathName));
        $this->assign('pathName', urldecode($pathName));
        $this->assign('classColumnArr', $classColumnArr); // 左侧栏目(分级)
        $this->assign('count', $count);
        $this->assign('classId', $classId); // 被选中的分类
        $this->assign('page', $page);
        $this->assign('allArticle', $allArticle); // 文章标题列
        $this->display();
    }

    /**
     * 搜索
     *
     * @param $searchContent string 查询词
     * @return mixed
     */
    public function goSearch($searchContent) {
        $tymNewsModel = $this->getTymNewsModel();
        
        $count = $tymNewsModel->queryArticle(urldecode($searchContent), true);
        import("ORG.Util.Page");
        
        $p = new Page($count, self::NUMBER_PER_PAGE);
        $p->setConfig('theme', 
            ' %nowPage%/%totalPage% 页 %upPage% %first%  %prePage%  %linkPage%   %nextPage% %end%  %downPage%');
        $allArticle = $tymNewsModel->queryArticle(urldecode($searchContent), 
            false, $p->firstRow, $p->listRows);
        $search = I('likeSea', '');
        $p->parameter = array(
            'likeSea' => urlencode($search));
        $page = $p->show();
        
        $resultFocus = array(
            'count' => $count, 
            'allArticle' => $allArticle, 
            'page' => $page);
        
        return $resultFocus;
    }

    /**
     * 旺财帮助页面的搜索
     */
    public function searchProblem() {
        $searchContent = I('likeSea', '');
        $classColumnArr = $this->helpLeft();
        if (! empty($searchContent)) {
            $resultFocus = $this->goSearch($searchContent);
            $this->assign('allArticle', 
                array_filter($resultFocus['allArticle']));
            $this->assign('count', $resultFocus['count']);
            $this->assign('page', $resultFocus['page']);
        }
        $this->assign('searchContent', urldecode($searchContent));
        $this->assign('classColumnArr', $classColumnArr);
        $this->display();
    }

    /**
     * 详情页面
     */
    public function helpDetails() {
        $tymNewsModel = $this->getTymNewsModel();
        $classColumnArr = $this->helpLeft();
        $classId = I('get.classId', '');
        $newsId = I('get.newsId') ? I('get.newsId') : I('get.news_id');
        $firstPathName = I('get.firstPathName', '');
        $pathName = I('get.pathName', '');
        
        $responseArticle = $tymNewsModel->getDetailsArticle($newsId);
        if (empty($firstPathName) && empty($pathName)) {
            $splitStr = explode('-', $responseArticle['class_name']);
            $firstPathName = $splitStr['1'];
            $pathName = $splitStr['2'];
        }
        
        $this->assign('firstPathName', urldecode($firstPathName));
        $this->assign('pathName', urldecode($pathName));
        $this->assign('newsId', $newsId);
        $this->assign('classId', $classId);
        $this->assign('responseArticle', $responseArticle);
        $this->assign('classColumnArr', $classColumnArr); // 左侧栏目(分级)
        $this->display();
    }

    /**
     * 新手入门
     */
    public function noviceEntry() {
        $tymNewsModel = $this->getTymNewsModel();
        $classColumnArr = $this->helpLeft(
            array(
                'parent_class_id' => 66), true);
        
        $newProblemTen = $tymNewsModel->newProblemTitleTen();
        
        foreach ($newProblemTen as $key => $value) {
            $classNameNum = strrpos($value['class_name'], '-');
            
            if ($classNameNum) {
                $newProblemTen[$key]['class_name'] = substr(
                    $value['class_name'], $classNameNum + 1);
            }
        }
        
        $this->assign('classColumnArr', $classColumnArr);
        $this->assign('newProblemTen', $newProblemTen);
        $this->display();
    }

    /**
     * 新手入门的二级分类下的文章(标题)
     */
    public function noviceList() {
        $tymNewsModel = $this->getTymNewsModel();
        $classId = I('get.class_id');
        $pathName = I('get.pathName');
        
        $classColumnArr = $this->helpLeft(
            array(
                'parent_class_id' => 66), true);
        
        $count = $tymNewsModel->getArticleTitle($classId, true);
        import("ORG.Util.Page");
        $p = new Page($count, self::NUMBER_PER_PAGE);
        
        $allArticle = $tymNewsModel->getArticleTitle($classId, false, 
            $p->firstRow, $p->listRows);
        $p->parameter = array(
            'pathName' => urlencode($pathName), 
            'class_id' => urlencode($classId));
        $page = $p->show();
        
        $this->assign('allArticle', $allArticle);
        $this->assign('classColumnArr', $classColumnArr);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('classId', $classId);
        $this->assign('pathName', urldecode($pathName));
        
        $this->display();
    }

    /**
     * 新手入门的详情页
     */
    public function noviceDetails() {
        $tymNewsModel = $this->getTymNewsModel();
        $newsId = I('get.newsId');
        $classColumnArr = $this->helpLeft(
            array(
                'parent_class_id' => 66), true);
        
        $responseArticle = $tymNewsModel->getDetailsArticle($newsId);
        
        $splitStr = explode('-', $responseArticle['class_name']);
        $pathName = $splitStr['1'];
        
        $this->assign('pathName', urldecode($pathName));
        $this->assign('newsId', $newsId);
        $this->assign('responseArticle', $responseArticle);
        $this->assign('classColumnArr', $classColumnArr); // 左侧栏目(分级)
        
        $this->display();
    }

    /**
     * 新手入门的搜索
     */
    public function noviceSearchProblem() {
        $searchContent = I('likeSea', '');
        $classColumnArr = $this->helpLeft(
            array(
                'parent_class_id' => 66), true);
        
        if (! empty($searchContent)) {
            $resultFocus = $this->goSearch($searchContent);
            $this->assign('allArticle', 
                array_filter($resultFocus['allArticle']));
            $this->assign('count', $resultFocus['count']);
            $this->assign('page', $resultFocus['page']);
        }
        $this->assign('searchContent', urldecode($searchContent));
        $this->assign('classColumnArr', $classColumnArr);
        $this->display();
    }

    /**
     * 设置 有帮助的 或 无效帮助
     */
    public function isHelp() {
        $isUseful = I('post.');
        
        $tymNewsModel = $this->getTymNewsModel();
        
        $Result = $tymNewsModel->recordNum($isUseful['newsId'], 
            $isUseful['isHelp']);
        
        $this->ajaxReturn($Result, 'JSON');
    }

    /**
     * 在线反馈 （弹窗）
     */
    public function popupFeedback() {
        $this->display();
    }

    /**
     * 在帮助中心跳转的在线反馈
     */
    public function feedbackInHelp() {
        if (IS_POST) {
            
            $allFeedback = I('post.');
            
            if (empty($allFeedback['questType'])) {
                $this->error('请选择问题类型');
            }
            
            if (empty($allFeedback['questTile'])) {
                $this->error('请填写问题标题');
            }
            
            if (empty($allFeedback['questDescription'])) {
                $this->error('问题描述不能为空');
            }
            
            if (empty($allFeedback['telephone'])) {
                $this->error('联系方式不能为空');
            }
            $isTelephone = preg_match("/^1[3458]{1}\d{9}$/", 
                $allFeedback['telephone']);
            $isEmail = preg_match(
                '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
                $allFeedback['telephone']);
            
            if ($isTelephone != 1 && $isEmail != 1) {
                $this->error("请输入正确的联系方式");
            }
            
            $title = '【' . $allFeedback['questType'] . '】' .
                 $allFeedback['questTile'];
            
            // 图片上传
            $img_node = str_replace('..', '', I('post.img_node')); // 安全过滤一下
            
            $error = '';
            if (! check_str($allFeedback['questDescription'], 
                array(
                    'null' => false, 
                    'maxlen_cn' => '1000'), $error)) {
                $this->error("内容{$error}");
            }
            
            $getNodeInfo = $this->userInfo;
            $data = array(
                'node_id' => '000000', 
                'leave_title' => $title,  // 【 类型 】标题
                'leave_content' => htmlspecialchars(
                    addslashes($allFeedback['questDescription'])), 
                'leave_phone' => $allFeedback['telephone'], 
                'leave_time' => date('YmdHis'), 
                'upload_img' => $img_node, 
                'type' => '1');
            $userName = '游客';
            // 用户已登录的情况下
            if (isset($getNodeInfo['node_id'])) {
                $nodeInfo = M('tnode_info')->field('node_name,contact_phone')
                    ->where("node_id='" . $getNodeInfo['node_id'] . "'")
                    ->find();
                $data['node_id'] = $getNodeInfo['node_id'];
                $data['type'] = '0';
                $userName = $nodeInfo['node_name'];
            }
            // 入库
            $result = M('tmessage_feedback')->data($data)->add();
            
            // 邮箱发送
            $content = "商户名：{$userName}<br/>联系方式：{$allFeedback['telephone']}<br/>留言标题：{$title}<br/>留言内容：{$allFeedback['questDescription']}<br/>图片：{$img_node}<br/>日期：" .
                 date('Y-m-d H:i:s');
            $ps = array(
                "subject" => "旺财营销平台-商户留言", 
                "content" => $content, 
                "email" => "7005@imageco.com.cn"); // 原邮箱wuqx@imageco.com.cn
            
            $resp = send_mail($ps);
            if ($result && $resp['sucess'] == '1') {
                $this->success('提交成功！');
            } else {
                $this->error('系统错误,您的留言添加失败');
            }
        }
        $this->display();
    }
}