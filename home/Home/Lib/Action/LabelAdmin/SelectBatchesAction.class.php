<?php
import('@.Action.MarketActive.MarketBaseAction');
class SelectBatchesAction extends MarketBaseAction {

    protected $festival  = array();//主题创意
    protected $normarket = array();//常用模版
    protected $freetool  = array();// 免费工具
    protected $o2o       = array();//多宝电商
    protected $weixin    = array();//微信卡券投放
    protected $shop      = array();//门店导航
    protected $vip       = array();//会员招募
    // 给活动类型大类命名
    protected $m_index = [
        'festival'  => '主题创意',
        'normarket' => '常用模版',
        'freetool'  => '免费工具',
        'o2o'       => '多宝电商',
        'weixin'    => '微信卡券投放',
        'shop'      => '门店导航',
        'vip'       => '会员招募'
    ];

    private $batchALL = array();

    private $batch_type1 = "";

    private $batch_type2 = "";

    private $hasM1 = "";

    private $hasM2 = "";
    // config配置文件中的BATCH_TYPE_NAME数组
    private $batches = array();

    public function _initialize() {
        parent::_initialize();
        // 初始化活动分类的类型
        self::Init();
        // 特殊页面调用筛选
        $filterType = I('filterType', '');
        empty($filterType) or self::filterBatch($filterType);
        $this->assign('filterType', $filterType);
        // 接受参数
        $this->batch_type1 = I('batch_type1', '', "htmlspecialchars,trim");
        $this->batch_type2 = I('batch_type2', '', "htmlspecialchars,trim");
        // 获取创建URL
        $this->assign('createUrl',self::getCreateUrl());
        // 判断当前用户购买了哪些模块
        $this->hasM1 = $this->hasPayModule('m1'); // 营销活动打包
        $this->hasM2 = $this->hasPayModule('m2'); // 多宝电商
        // 二级导航搜索栏
        $this->assign('arrSelect', self::_menuList($filterType));
    }

    /**
     * [beforeCheckAuth 系统权限之前判断权限]
     *
     * @return [type] [description]
     */
    protected function beforeCheckAuth() {
        $wc_version = get_wc_version();
        if (!in_array($wc_version, ['v0', 'v0.5', 'v4', 'v9']))
            $this->error("您没有该活动的发布权限！");
        $this->_authAccessMap = '*'; // 让系统免校验
    }
    /**
     * 初始化活动类型分类
     * [Init description]
     */
    private function Init()
    {
        // 根据配置文件，获取batches数组
        $this->batches = C('BATCH_TYPE_NAME');
        // 获取营销活动所有类型
        $batchType = parent::getBatchType(1);
        $this->festival  = array_keys($batchType[1]);
        $this->normarket = array_keys($batchType[2]);
        $this->freetool  = [58,19,13];
        $this->o2o       = [29,41];
        $this->weixin    = [40];
        $this->shop      = [17];
        $this->vip       = [52];
        // 所有互动模版的类型合集
        $this->batchALL = array_merge($this->festival, $this->normarket, $this->freetool, 
            $this->o2o, $this->weixin, $this->shop, $this->vip);
    }
    /**
     * [index 首页]
     *
     * @return [type] [description]
     */
    public function index() {
        // 初始化局部变量
        $pageShow = '';
        $today = date("Ymd000000", time());
        // 搜索参数设置
        $search = array();
        
        $batch_type = "";
        if (empty($this->batch_type2)) {
            $batch_type = $this->batch_type1;
        } else {
            $batch_type = $this->batch_type2;
        }
        $filterType = I('filterType', '');
        $name = I('batch_name', '', "htmlspecialchars,trim");
        $batch_status = I('batch_status', '', "htmlspecialchars,trim");
        empty($name) or $search['name'] = array(
            'like', 
            '%' . $name . '%');
        // 1表示未开始，2表示进行中，3表示已过期
        if (! empty($batch_status)) {
            if ($batch_status == "1") {
                $search["start_time"] = array(
                    "GT", 
                    $today);
            } elseif ($batch_status == "2") {
                $search["start_time"] = array(
                    "ELT", 
                    $today);
                $search["end_time"] = array(
                    "EGT", 
                    $today);
            } elseif ($batch_status == "3") {
                $search["end_time"] = array(
                    "LT", 
                    $today);
            }
        }
        // 分销渠道调用此页面的id传参
        $id = I('id');
        if (! empty($id)) {
            $mod = M('tchannel');
            $query = $mod->where(
                array(
                    'node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"), 
                    'id' => $id))->find();
            if (! $query)
                $this->error('错误参数');
            
            $this->assign('id', $id);
            $this->assign('go_url', $query['go_url']);
        }
        // 部分页面设置的参数
        $rid = I('rid');
        if (! $rid)
            $rid = 1;
            // 根据活动类型，获取活动内容
        if ($filterType == 'fms') {
            $batch_type = '2';
            $batchList = self::_getBatchList($batch_type, $search, $pageShow);
        } else {
            $batchList = self::_getBatchList($batch_type, $search, $pageShow);
            // 获取所有类型对应的一个内容
            $batchListAll = self::_getBatchList("", array(), $pageShow, true);
        }
        $batchListAllNew = array();
        if (!empty($batchListAll)) {
            foreach ($batchListAll as $k => $v) {
                $batchListAllNew[$v['batch_type']] = $v;
            }
        }
        $this->assign('batch_status', $batch_status);
        $this->assign('batch_name', $name);
        $this->assign('batchList', $batchList);
        $this->assign('rid', $rid);
        $this->assign('batches', $this->batches);
        $this->assign('page', $pageShow);
        
        if ($filterType == 'fms') {
            $this->assign('filterType', $filterType);
            $callback = I('call_back');
            $this->assign('callback', $callback);
            $this->display();
            exit();
        } else {
            $this->display();
        }
    }
    // 选择活动之后的页面
    public function finish() {
        $this->display();
    }
    // 顶部搜索二级导航栏
    private function _menuList($filterType) {
        $batches = $this->batches;
        $menuList = array();
        if ($filterType == 'fms') {
            $menuList['抽奖'] = array(
                "group"      => 'cj', 
                "batch_type" => 2, 
                "batch_name" => '抽奖');
        } else {
            foreach ($this->m_index as $key => $val) {
                // 如果分类中有多个活动类型,则使其有二级数组
                if(count($this->$key) > 1) 
                {
                    foreach ($this->$key as $batch_type) {
                        $menuList[$val][] = array(
                            "group"      => $key, 
                            "batch_type" => $batch_type, 
                            "batch_name" => $batches[$batch_type]
                            );
                    }
                }else{
                    foreach ($this->$key as $batch_type) {
                        $menuList[$val] = array(
                            "group"      => $key, 
                            "batch_type" => $batch_type, 
                            "batch_name" => $batches[$batch_type]
                            );
                    }
                }
            }
        }
        // 二级菜单制作
        $arrSelect = array();
        $i = 0;
        foreach ($menuList as $menuKey => $menuValue) {
            if (!empty($menuValue[0]) && is_array($menuValue[0])) {
                $arrSelect[$i]['name'] = $menuKey;
                $arrSelect[$i]['value'] = implode(',', 
                    $this->$menuValue[0]['group']);
                $j = 0;
                foreach ($menuValue as $menuSonKey => $menuSonValue) {
                    $arrSelect[$i]['list'][$j]['name'] = $menuSonValue['batch_name'];
                    $arrSelect[$i]['list'][$j]['value'] = $menuSonValue['batch_type'];
                    if ($arrSelect[$i]['list'][$j]['value'] == $this->batch_type2) {
                        $arrSelect[$i]['list'][$j]['check'] = true;
                    }
                    $j ++;
                }
                if ($arrSelect[$i]['value'] == $this->batch_type1) {
                    $arrSelect[$i]['check'] = true;
                }
                $i ++;
            } elseif ($filterType == 'fms') {
                $arrSelect[$i]['name'] = $menuValue['batch_name'];
                $arrSelect[$i]['value'] = $menuValue['batch_type'];
                if ($arrSelect[$i]['value'] == $this->batch_type1) {
                    $arrSelect[$i]['check'] = true;
                }
                $i ++;
            } else {
                $arrSelect[$i]['name'] = $menuValue['batch_name'];
                $arrSelect[$i]['value'] = $menuValue['batch_type'];
                if ($arrSelect[$i]['value'] == $this->batch_type1) {
                    $arrSelect[$i]['check'] = true;
                }
                $i ++;
            }
        }
        
        return json_encode($arrSelect);
    }
    // 获取活动内容列表
    private function _getBatchList($batch_type, $search = array(), &$pageShow = null, 
        $batch_flag = false) {
        // 初始化参数
        $today = date("Ymd", time());
        $dao = M('tmarketing_info');
        $wc_version = get_wc_version();
        
        // 获取全部活动类型数组
        $batch_type_all = $this->batchALL;
        // 查询条件
        $batches = $this->batches;
        $search['node_id'] = $this->nodeId;
        // 新的电子海报没有时间区域，所以不判断时间
        $search['end_time'] = array(array('EGT',$today.'000000'),array('exp','IS NULL'), 'or') ;
        
        if (! empty($batch_type)) {
            $search['batch_type'] = array(
                'in', 
                $batch_type);
        } else {
            $search['batch_type'] = array(
                'in', 
                $batch_type_all);
        }
        $totalNum = $dao->where($search)->count();
        if (! $batch_flag) {
            import("ORG.Util.Page"); // 导入分页类
            $Page = new Page($totalNum, 8);
            $Page->setConfig('theme', 
                '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
            $pageShow = $Page->show();
            $queryData = $dao->field(
                "id batch_id,batch_type,name,pay_status,IFNULL(start_time,add_time) AS start_time,IFNULL(end_time,20260101000000) AS end_time,click_count")
                ->where($search)
                ->order("pay_status desc,id desc")
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $queryData = $dao->field(
                "id batch_id,batch_type,name,pay_status,IFNULL(start_time,add_time) AS start_time,IFNULL(end_time,20260101000000) AS end_time,click_count")
                ->where($search)
                ->group("batch_type")
                ->order("pay_status desc,id desc")
                ->select();
        }
        if (! $queryData)
            $queryData = array();
        foreach ($queryData as $k => &$v) {
            // 显示的名称不得大于13个字符
            if (mb_strlen($v['name'], 'UTF-8') > 13) {
                $v['smallname'] = mb_substr($v['name'], 0, 13, 'UTF-8') . "...";
            } else {
                $v['smallname'] = $v['name'];
            }
            // 页面回馈的字段，以及显示属于哪个活动
            $v['info'] = $batches[$v['batch_type']] . ' ＞ ' . $v['name'];
            // 1表示未开始，2表示进行中，3表示已过期
            if ($v['start_time'] > $today . '235959') {
                $v['batch_status'] = '1';
            } elseif ($v['end_time'] < $today . '000000') {
                $v['batch_status'] = '3';
            } else {
                $v['batch_status'] = '2';
            }
            // 权限判断 start
            if ($wc_version == 'v0') {
                if (in_array($v['batch_type'], 
                    array(
                        '40', 
                        '52'))) {
                    $v['batch_pay_status'] = '2'; // (粉丝招募，微信卡券投放)未认证
                } elseif (in_array($v['batch_type'], $this->o2o)) {
                    $v['batch_pay_status'] = '3'; // (免费版，没有O2O的权限)
                } elseif (! in_array($v['batch_type'], 
                    array(
                        '17', 
                        '13', 
                        '19'))) {
                    // 门店导航。微官网。图文编辑 是大家都有的权限，不做限制
                    if ($v['pay_status'] === '0') {
                        $v['batch_pay_status'] = '1'; // (未付费)
                    }
                }
            }
            if ($wc_version == 'v0.5') {
                if (in_array($v['batch_type'], $this->o2o)) {
                    $v['batch_pay_status'] = '3'; // (认证版，没有O2O的权限)
                } elseif (! in_array($v['batch_type'], 
                    array(
                        '17', 
                        '13', 
                        '19', 
                        '40', 
                        '52'))) {
                    // 门店导航。微官网。图文编辑,粉丝招募，微信卡券投放 是v0.5以后都有的权限，不做限制
                    if ($v['pay_status'] === '0') {
                        $v['batch_pay_status'] = '1'; // (未付费)
                    }
                }
            }
            if ($wc_version == 'v9') {
                if (! $this->hasM2) {
                    if (in_array($v['batch_type'], $this->o2o)) {
                        $v['batch_pay_status'] = '3'; // (非O2O版，没有O2O的权限)
                    } elseif (! in_array($v['batch_type'], 
                        array(
                            '17', 
                            '13', 
                            '19', 
                            '40', 
                            '52'))) {
                        // 门店导航。微官网。图文编辑,粉丝招募，微信卡券投放 是v0.5以后都有的权限，不做限制
                        if ($v['pay_status'] === '0' && ! $this->hasM1) {
                            $v['batch_pay_status'] = '1'; // (未付费)
                        }
                    }
                } else {
                    if (! in_array($v['batch_type'], 
                        array(
                            '17', 
                            '13', 
                            '19', 
                            '40', 
                            '52')) && ! in_array($v['batch_type'], $this->o2o)) {
                        // 门店导航。微官网。图文编辑,粉丝招募，微信卡券投放 是v0.5以后都有的权限，不做限制
                        // 多宝电商权限不做限制
                        if ($v['pay_status'] === '0' && ! $this->hasM1) {
                            $v['batch_pay_status'] = '1'; // (未付费)
                        }
                    }
                }
            }
            /*对特殊活动进行过滤*/
            if($v['batch_type'] == 58)
            {
                $v['batch_pay_status'] = null;
            }
            // 权限判断 end
        }
        unset($v);
        return $queryData;
    }
    /**
     * [getCreateUrl 获取快速创建的地址]
     * @return [type] [description]
     */
    private function getCreateUrl(){
        $urlTemplate = array(
            'default'   => U('MarketActive/Activity/index'), // 默认地址
            'festival'  => U('MarketActive/Activity/createFestival'),
            'normarket' => U('MarketActive/Activity/createMarket'),
            'freetool'  => U('MarketActive/NewPoster/index'),
            'o2o'       => U('Home/Index/marketingShow5'),
            'weixin'    => U('Weixin/WeixinCard/cardSendIndex'),
            'shop'      => U('Home/Store/navigation'),
            'vip'       => U('Wmember/Member/recruit'),
            '13'        => U('MarketActive/Tool/website'),
            '58'        => U('MarketActive/NewPoster/index'),
            '19'        => U('MarketActive/Tool/pictext'),
            '26'        => U('Ecshop/O2OHot/index',['batch_type'=>26]),
            '27'        => U('Ecshop/O2OHot/index',['batch_type'=>27]),
            '29'        => U('Ecshop/Index/preview'),
            '41'        => U('Ecshop/SalePro/index'),
            );
        // 默认是营销活动的首页
        $url = $urlTemplate['default'];

        $m_index_key = array_keys($this->m_index);

        if(!empty($this->batch_type1))
        {
            foreach ($m_index_key as $key) {
                $tmpArray = array_diff($this->$key,explode(',', $this->batch_type1));
                if(count($tmpArray) == 0)
                {
                    if(!empty($this->batch_type2) && in_array($this->batch_type2,array_keys($urlTemplate)))
                    {
                        $url = $urlTemplate[$this->batch_type2];
                    }else{
                        $url = $urlTemplate[$key];
                    }
                }
            }
        }
        return $url;
    }
    // 筛选活动
    private function filterBatch($filterType) {
        /*
         * 微官网不能插活动属性为微官网的活动 电子海报不能插活动属性为电子海报的活动
         * 旺财小店里面不能插多宝电商下面的旺财小店的，但是可以插单品销售的活动
         */
        /*if ($filterType == 'wgw') {
            array_pop($this->freetool);
        } elseif ($filterType == 'dzhb') {
            array_unshift($this->freetool);
        } elseif ($filterType == 'wcxd') {
            foreach ($this->o2o as $kk => $vv) {
                if ($vv == '29')
                    unset($this->o2o[$kk]);
            }
        }*/
    }
    // 二维码渠道绑定的ajax提交
    public function submit() {
        $is_true = getNodeTypePower($this->nodeType, '二维码渠道绑定');
        if ($is_true)
            $this->error('该功能未开通！');
        
        $channel_id = I('id');
        $batch_type = I('batchType');
        $batch_id = I('ckid');
        $marketModel = M('tmarketing_info');
        $selectMarketinfo = $marketModel->where(
            array(
                'id' => $batch_id, 
                'batch_type' => $batch_type))->field(
            array(
                'start_time', 
                'end_time'))->find();
        // 开始时间和结束时间默认取活动的开始时间和结束时间
        $defaultStartTime = $selectMarketinfo['start_time'];
        $defaultendTime = $selectMarketinfo['end_time'];
        $startTime = I('start_time', $defaultStartTime);
        $endTime = I('end_time', $defaultendTime);
        $startTime = date('YmdHis', strtotime($startTime));
        $endTime = date('YmdHis', strtotime($endTime));
        
        if (empty($channel_id) || empty($batch_type) || empty($batch_id)) {
            $this->error('错误参数！');
        }
        if ($_REQUEST['callback'] == 1) {
            $name = M('tmarketing_info')->where(
                array(
                    'id' => $batch_id))->field('name')->find();
            echo $this->success(
                array(
                    'name' => $name['name'], 
                    'batch_id' => $batch_id));
            exit();
        }
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $mod = M('tbatch_channel');
        // 如果是添加新的活动的时候
        // 是否已绑定过该活动
        $where = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id);
        $is_bind = $mod->where($where)->find();
        $isSeperate = false; // 是否和其他绑定的活动时间有冲突，没有冲突:true
                             // 如果没绑定过就是添加
        if (! $is_bind) {
            $data = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'add_time' => Date('YmdHis'), 
                'node_id' => $this->nodeId, 
                'start_time' => $startTime, 
                'end_time' => $endTime, 
                'status' => '1');
            // 判断是否和之前绑定的活动时间有冲突
            $isSeperate = $this->checkIsSeperate($batch_id, $batch_type, 
                $channel_id, true, strtotime($startTime), strtotime($endTime));
            if ($isSeperate) {
                $query = $mod->add($data);
                if (! $query) {
                    $tranDb->rollback();
                    $this->error('绑定失败！');
                }
            }
        } else {
            // 绑定过的就是修改绑定时间
            $data = array(
                'start_time' => $startTime, 
                'end_time' => $endTime, 
                'status' => '1');
            // 判断是否和之前绑定的活动时间有冲突
            $isSeperate = $this->checkIsSeperate($batch_id, $batch_type, 
                $channel_id, false, strtotime($startTime), strtotime($endTime));
            if ($isSeperate) {
                $where = array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'channel_id' => $channel_id, 
                    'node_id' => $this->nodeId);
                $query = $mod->where($where)->save($data);
                if (false === $query) {
                    $tranDb->rollback();
                    $this->error('绑定失败！');
                }
            }
        }
        if (! $isSeperate) { // 有时间冲突的,不能绑定
            $tranDb->rollback();
            $this->error('绑定失败！');
        }
        // 更新channel表的有效时间和活动id
        // 考虑到效率问题channel表里需要有batch_id，访问活动的时候需要判断是不是在有效期之内，不再的话那里再更新活动编号
        $model = M('tchannel');
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => $channel_id);
        $channelData = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'begin_time' => $startTime, 
            'end_time' => $endTime, 
            'go_url' => ''); // 绑定互动模块的时候把go_url（外链）清除，因为访问的时候是按照这个来判断的，在解绑的地方已经有这个逻辑，这里再加一下，防止以后出问题
        
        $res = $model->where($wh)->save($channelData);
        if (false === $res) {
            $tranDb->rollback();
            $this->error('绑定失败！');
        }
        $tranDb->commit();
        node_log('绑定互动模块|渠道编号:' . $channel_id . '|活动编号:' . $batch_id);
        $this->success('绑定成功！');
    }

    public function checkAddedActivityTime() {
        $channel_id = I('id');
        $batch_type = I('batchType');
        $batch_id = I('ckid');
        $re = D('SelectBatches')->getBindedActivityAndNewActivityTimeStamp(
            $this->node_id, $batch_id, $batch_type, $channel_id);
        // 判断新绑定的活动的时间范围,是否完全从属于以前绑定的任何一个活动的时间范围内，如果是就不能绑定
        $include = false;
        $len = count($re['binded']);
        $totalSeperate = false; // 是否完全和原来的时间没有交集
        if (empty($re['binded'])) {
            $totalSeperate = true;
        }
        foreach ($re['binded'] as $key => $v) {
            if ($re['newBind']['start_time'] >= $v['start_time'] &&
                 $re['newBind']['end_time'] <= $v['end_time']) {
                $include = true; // 完全从属于以前绑定的任何一个活动的时间范围内
                break;
            }
            if ($key == 0) { // key是按照时间顺序排序过的,小的在前大的在后
                if ($re['newBind']['end_time'] < $v['start_time']) {
                    $totalSeperate = true;
                }
            } elseif ($key == $len) {
                if ($re['newBind']['start_time'] > $v['end_time']) {
                    $totalSeperate = true;
                }
            } else {
                if ($re['newBind']['start_time'] > $v['end_time']) {
                    if ($re['newBind']['end_time'] <
                         $re['binded'][($key + 1)]['start_time']) {
                        $totalSeperate = true;
                    }
                }
            }
        }
        if ($include) {
            $this->error('该活动有效期时间段，已有其他活动！');
        } else {
            if ($totalSeperate == true) {
                $this->success(
                    array(
                        'code' => '-1', 
                        'msg' => '没有交集,可直接选'));
            } else {
                $url = D('MarketActive')->getEditUrl($batch_id, $batch_type);
                $this->success(
                    array(
                        'code' => '-2', 
                        'msg' => '有交集,需要编辑活动时间', 
                        'm_name' => $re['m_name'], 
                        'editUrl' => $url));
            }
        }
    }

    public function editShowTime() {
        $channelId = I('channel_id');
        $mId = I('m_id');
        $isChange = I('isChange'); // 用于查询时的条件不一样,如果有表示修改,要查询除传过来的mId之外绑定的活动
        if ($isChange) {
            $binded = D('SelectBatches')->getBindedChannelTimeNotIncludeGivedMid(
                $this->node_id, $channelId, $mId);
        } else {
            $binded = D('SelectBatches')->getBindedChannelTime($this->node_id, 
                $channelId);
        }
        // $this->assign('isChange', $isChange);
        $this->assign('binded', $binded);
        $activityInfo = M('tmarketing_info')->where(
            array(
                'id' => $mId))->field(
            array(
                'start_time', 
                'end_time', 
                'batch_type'))->find();
        $this->assign('activityInfo', $activityInfo);
        $this->assign('length', count($binded));
        $this->assign('channelId', $channelId);
        $this->assign('mId', $mId);
        $this->display();
    }

    /**
     * 判断有没有和渠道绑定的时间有重叠
     *
     * @param int $batch_id 新增活动的活动号
     * @param int $batch_type 活动类型
     * @param int $channel_id 渠道号
     * @param string $isAdd 新增true|修改时间false
     * @param timestamp $newStartTime
     * @param timestamp $newEndTime
     * @return boolean
     */
    private function checkIsSeperate($batch_id, $batch_type, $channel_id, 
        $isAdd = true, $newStartTime, $newEndTime) {
        $re = D('SelectBatches')->getBindedActivityAndNewActivityTimeStamp(
            $this->node_id, $batch_id, $batch_type, $channel_id, $isAdd);
        // 如果没有绑定过,返回true
        if (empty($re['binded'])) {
            return true;
        }
        // 判断新绑定的活动的时间范围,是否完全从属于以前绑定的任何一个活动的时间范围内，如果是就不能绑定
        $len = count($re['binded']);
        $totalSeperate = false; // 是否完全和原来的时间没有交集
        foreach ($re['binded'] as $key => $v) {
            if ($len == 1) { // key是按照时间顺序排序过的,小的在前大的在后
                if ($newEndTime < $v['start_time']) {
                    $totalSeperate = true;
                }
                if ($newStartTime > $v['end_time']) {
                    $totalSeperate = true;
                }
            } else {
                if ($key == 0) {
                    if ($newEndTime < $v['start_time']) {
                        $totalSeperate = true;
                    }
                } elseif (($key + 1) < $len) {
                    if ($newStartTime > $re['binded'][($key - 1)]['end_time'] &&
                         $newEndTime < $re['binded'][$key]['start_time']) {
                        $totalSeperate = true;
                    }
                } else {
                    if ($newStartTime > $v['end_time']) {
                        $totalSeperate = true;
                    }
                    if ($newStartTime > $re['binded'][($key - 1)]['end_time'] &&
                         $newEndTime < $re['binded'][$key]['start_time']) {
                        $totalSeperate = true;
                    }
                }
            }
        }
        return $totalSeperate;
    }

    /**
     * 编辑渠道外链地址
     */
    public function editUrl() {
        $channelId = I('id');
        $url = I('go_url', '', 'trim');
        if (IS_POST) {
            try {
                D('SelectBatches')->editGoUrl($this->node_id, $channelId, $url);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success();
        }
        
        $this->assign('channelId', $channelId);
        $this->display();
    }
    public function SelectOk() {
        // 初始化局部变量
        $pageShow = '';
        $today = date("Ymd000000", time());
        // 搜索参数设置
        $search = array();

        $batch_type = "";
        if (empty($this->batch_type2)) {
            $batch_type = $this->batch_type1;
        } else {
            $batch_type = $this->batch_type2;
        }
        $filterType = I('filterType', '');
        $name = I('batch_name', '', "htmlspecialchars,trim");
        $batch_status = I('batch_status', '', "htmlspecialchars,trim");
        empty($name) or $search['name'] = array(
            'like',
            '%' . $name . '%');
        // 1表示未开始，2表示进行中，3表示已过期
        /*if (! empty($batch_status)) {
            if ($batch_status == "1") {
                $search["start_time"] = array(
                    "GT",
                    $today);
            } elseif ($batch_status == "2") {
                $search["start_time"] = array(
                    "ELT",
                    $today);
                $search["end_time"] = array(
                    "EGT",
                    $today);
            } elseif ($batch_status == "3") {
                $search["end_time"] = array(
                    "LT",
                    $today);
            }
        }*/
        // 分销渠道调用此页面的id传参
        $id = I('id');
        if (! empty($id)) {
            $mod = M('tchannel');
            $query = $mod->where(
                array(
                    'node_id' => array(
                        'exp',
                        "in (" . $this->nodeIn() . ")"),
                    'id' => $id))->find();
            if (! $query)
                $this->error('错误参数');

            $this->assign('id', $id);
            $this->assign('go_url', $query['go_url']);
        }
        // 部分页面设置的参数
        $rid = I('rid');
        if (! $rid)
            $rid = 1;
            // 根据活动类型，获取活动内容
        if ($filterType == 'fms') {
            $batch_type = '2';
            $batchList = self::_getBatchList($batch_type, $search, $pageShow);
        } else {
            $batchList = self::_getBatchList($batch_type, $search, $pageShow);
            // 获取所有类型对应的一个内容
            $batchListAll = self::_getBatchList("", array(), $pageShow, true);
        }
        $batchListAllNew = array();
        if (!empty($batchListAll)) {
            foreach ($batchListAll as $k => $v) {
                $batchListAllNew[$v['batch_type']] = $v;
            }
        }
        $this->assign('batch_status', $batch_status);
        $this->assign('batch_name', $name);
        $this->assign('batchList', $batchList);
        $this->assign('rid', $rid);
        $this->assign('batches', $this->batches);
        $this->assign('page', $pageShow);
        $this->assign('hide_batchsel', I('hide_batchsel'));


        $this->assign('filterType', $filterType);
        $callback = I('callback');
        $this->assign('callback', $callback);
        $this->display();

    }
}


