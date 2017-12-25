<?php 
/**
 * 多乐互动首页
 */
class ActivityAction extends MarketBaseAction{
	const FESTIVAL_TYPE = '1'; // 节日列表
	const MARKET_TYPE   = '2'; // 常规列表

	/**
	 * @var MarketingInfoRedisModel
	 */
	protected $MarketingInfoRedisModel;

	/**
	 * @var DrawLotteryBaseService
	 */
	protected $DrawLotteryBaseService;

	public function _initialize() {
        parent::_initialize();
        $this->assign('has4880',$this->hasPayModule('m1')?2:1);
    }

    /**
     * [beforeCheckAuth 提前校验权限]
     */
    public function beforeCheckAuth(){
    	// 跳过系统权限校验
    	$this->_authAccessMap = '*';
    }

    /**
     * [index 首页]
     */
    public function index(){
        self::getIndexList();
	    self::duoleBanner();
    	$this->display();
    }
	public function MarketList(){
		$requestInfo = I('get.');
		self::getMarketList($requestInfo);
    	$this->display();
    }
    public function createFestival(){
        self::getCreateList(self::FESTIVAL_TYPE);
    	$this->display();
    }
    public function createMarket(){
        self::getCreateList(self::MARKET_TYPE);
    	$this->display();
    }
    public function changeBatchStatus(){
    	$requestInfo = I('post.');
    	if(!self::changeStatus($requestInfo)){
    		$this->error('修改失败');
    	}
    	$this->success('修改成功');
    }
	public function edit(){
		$requestInfo = I('get.');
		self::editMarketInfo($requestInfo);
	}
	public function create(){
		$requestInfo = I('get.');
		self::createMarketInfo($requestInfo);
	}
    /**********************私有方法******************/

	private function duoleBanner(){
		$banner_arr = M('tym_news')->where('class_id=80 and status=1 and check_status=2')->order('sort')->getField('news_id,small_img,news_img,go_url');
		foreach($banner_arr as $k=>&$v){
			foreach($v as $kk=>&$vv){
				if($kk == 'small_img'){
					$str = explode('!',$v[$kk]);
					if(substr_count($str[0],'"') == 1){
						$vv = $str[0].'"';
					}else{
						$vv = $str[0];
					}
				}
			}
		}
		$this->assign('banner_arr',$banner_arr);
	}

    private function getIndexList(){
        $batchTypeArr = parent::getBatchType(1);
        $this->assign('festival_arr',self::orderByStrLen($batchTypeArr[1]));
        $this->assign('common_arr',self::orderByStrLen($batchTypeArr[2]));
    }
    private function getMarketList($requestInfo){
    	$batchBelongArr = array(
			'0' => '全部',
			'1' => '主题创意',
			'2' => '常用模版'
			);
    	$batchStatusArr = array(
    		'0' => '全部',
			'1' => '未开始',
			'2' => '进行中',
			'3' => '已过期',
			'4' => '未开启',
			'5' => '已支付',
			'6' => '未支付'
    		);
    	// m1用户，全部为已支付
    	if($this->hasPayModule('m1')){
	    	unset($batchStatusArr['5']);
	    	unset($batchStatusArr['6']);
    	}
    	$batchBelong  = get_val($requestInfo,'batch_belong','0');
    	$batchTypeArr = parent::getBatchType(1);
    	// 查询条件
    	$map = array();
    	$map['m.node_id'] = $this->nodeId;
    	$map['m.batch_type'] = get_val($requestInfo,'batch_type',
    		array('in',implode(array_keys($batchTypeArr[$batchBelong]),',')));
    	$marketName = get_val($requestInfo,'market_name');
    	if($marketName){
    		$map['name'] = array('like','%'.$marketName.'%');
    	}
    	switch (get_val($requestInfo,'batch_status','0')) {
    		case '1':
			    $map['m.status'] = '1';
    			$map['m.start_time'] = array('GT',date('Ymd').'235959');
    			break;
    		case '2':
			    $map['m.status'] = '1';
    			$map['m.end_time'] = array('EGT',date('Ymd').'235959');
    			$map['m.start_time'] = array('ELT',date('Ymd').'000000');
    			break;
    		case '3':
			    $map['m.status'] = '1';
    			$map['m.end_time'] = array('LT',date('Ymd').'000000');
    			break;
    		case '4':
    			$map['m.status'] = '2';
    			break;
    		case '5':
    			$map['m.pay_status'] = array('NEQ','0');
    			break;
    		case '6':
    			$map['m.pay_status'] = array('EQ','0');
    			break;
    		default:
    			break;
    	}
    	// 分页
    	import('ORG.Util.Page'); 
    	$Page = new Page(M()->table("tmarketing_info m")->where($map)->count(), 8);
        $show = $Page->show();
        // 获取结果
        $channelId = parent::getDefaultCId();
        $joinPart = $channelId ? " AND c.channel_id=".$channelId : "";
	    $click_num = get_val($requestInfo,'click_num');
	    $orderInfo = "add_time desc";
	    switch($click_num % 3){
		    case 1:
		    {
			    $orderInfo = get_val($requestInfo,'batch_order','add_time').' desc';
			    break;
		    }
		    case 2:
		    {
			    $orderInfo = get_val($requestInfo,'batch_order','add_time').' asc';
			    break;
		    }
		    default:
			    break;
	    }

	    $sonSelect = 'SELECT SUM(tb.remain_num) AS remainAmt,tb.m_id FROM tbatch_info tb 
  						LEFT JOIN tcj_batch tcj ON tcj.`b_id` = tb.`id` 
  						WHERE tb.node_id = '.$this->nodeId.' AND tcj.`status` = 1 GROUP BY tb.`m_id`';
        $list = M()->table("tmarketing_info m")
        	->field('m.*,m.click_count AS pv,SUM(d.uv_count) AS uv,m.send_count AS sv,SUM(d.verify_count) AS iv,b.remainAmt AS prize_num,c.id AS bc_id')
        	->join('tdaystat d ON d.batch_type=m.batch_type AND d.batch_id=m.id')
        	->join("LEFT JOIN ({$sonSelect}) b ON b.m_id=m.id")
        	->join('tbatch_channel c ON c.batch_id=m.id'.$joinPart)
        	->where($map)->group('m.id')->order($orderInfo)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as $k => $v) {
            //是否有发送奖品失败的记录
            $failedRecord = D('SendAwardTrace')->getFailedRecord($this->node_id, $v['id']);
            $list[$k]['failedRecordFlag'] = $failedRecord ? 1 : 0;
        }
        $this->assign('batch_belong_arr',$batchBelongArr);
        $this->assign('batch_belong',$batchBelong);
        $this->assign('batch_type_arr',$batchTypeArr);
        $this->assign('batch_type',get_val($requestInfo,'batch_type'));
        $this->assign('batch_status_arr',$batchStatusArr);
        $this->assign('batch_status',get_val($requestInfo,'batch_status'));
        $this->assign('batch_name_arr',htmlspecialchars(implode(parent::getMarketName()," ")));
	    $this->assign('market_name',$marketName);
	    $this->assign('click_num',$click_num);
        $this->assign('batch_order',get_val($requestInfo,'batch_order'));
        $this->assign('hasBuyMarketTool',$this->hasPayModule('m1') ? '1' : '0');
        $this->assign('show_status_arr',array('1'=>'停用','2'=>'启用'));
        $this->assign('list',$list);
        $this->assign('page',$show);
    }

    private function changeStatus($requestInfo){
    	$status = get_val($requestInfo,'status');
    	$mId    = get_val($requestInfo,'m_id');
    	if(!in_array($status,['1','2']) || !is_numeric($mId)) {
    		return false;
    	}
	    $finalStatus = (3-$status);
    	$ret = M('tmarketing_info')->where(['node_id'=>$this->nodeId,'id'=>$mId])
    			->save(['status'=>$finalStatus]);
	    if (empty($this->MarketingInfoRedisModel)) {
		    $this->MarketingInfoRedisModel = D('MarketingInfoRedis');
	    }
	    if ($ret) {
		    $marketingInfo = M('tmarketing_info')->where(['node_id'=>$this->nodeId,'id'=>$mId])->find();
		    if (isset($marketingInfo['batch_type']) && $marketingInfo['batch_type'] == CommonConst::BATCH_TYPE_WEEL) {
			    if (empty($this->DrawLotteryBaseService)) {
				    $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
			    }
			    $this->DrawLotteryBaseService->modifyMarkertingInfo($mId);
		    }
	    }


    	return $ret === false ? false : true;
    }

    private function getCreateList($type){
        $batchTypeArr = parent::getBatchType(2);
        $this->assign('list',$batchTypeArr[$type]);
    }
	private function createMarketInfo($requestInfo){
		$batch_type = get_val($requestInfo,'batch_type');
		$marketActiveInfo = M('tmarketing_active')->where(['batch_type'=>$batch_type])->find();
		if(empty($marketActiveInfo)){
			$this->error('活动类型不存在！');
		}
		self::createUrl($marketActiveInfo['batch_create_url']);
	}
	private function editMarketInfo($requestInfo){
		$m_id = get_val($requestInfo,'m_id');
		if(empty($m_id) || !($marketInfo = D('MarketCommon')->getMarketInfo($this->nodeId,$m_id))) {
			$this->error("活动不存在！");
		}
		$marketActiveInfo = M('tmarketing_active')->where(['batch_type'=>$marketInfo['batch_type']])->find();
		self::editUrl($marketActiveInfo['batch_create_url'],$m_id);
	}
	/**
	 * [orderByStrLen 根据活动名称的文字长度排序]
	 * @param  [type]  $pArr  [description]
	 * @param  boolean $order [默认从小到大排序]
	 * @return [type]         [description]
	 */
	private function orderByStrLen($pArr, $order = false){
		$firstTwo = self::array_myslice($pArr,0,2);
		$tempArr = array();
		$pArr = self::array_myslice($pArr,2);
		foreach ($pArr as $key => $val) {
			$tempArr[] = ['key'=>$key,'val'=>$val];
		}
		$n = count($tempArr);
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $n - $i - 1; ++$j) {
                if($order){
                    if (strlen($tempArr[$j]['val']) < strlen($tempArr[$j+1]['val'])) {
                        $temp         = $tempArr[$j];
                        $tempArr[$j]  = $tempArr[$j+1];
                        $tempArr[$j + 1] = $temp;
                    }
                }else{
                    if (strlen($tempArr[$j]['val']) > strlen($tempArr[$j+1]['val'])) {
                        $temp         = $tempArr[$j];
                        $tempArr[$j]  = $tempArr[$j+1];
                        $tempArr[$j + 1] = $temp;
                    }
                }
            }
        }
        $retArr = array();
        foreach ($tempArr as $key => $val) {
			$retArr[$val['key']] = $val['val'];
		}
        return array($firstTwo,self::array_myslice($retArr,0,6));
	}
	/**
	 * [array_myslice 自定义分割数组，不会重置key]
	 * @param  [type] $array  [数组]
	 * @param  [type] $start  [开始]
	 * @param  [type] $length [长度]
	 * @return [type]         [数组]
	 */
	private function array_myslice($array,$start,$length = 0){
		$keyArr = array_keys($array); // 取出所有的key
		$valArr = array_values($array); // 取出所有的值
		$retArr = array();
		if($length > 0)
		{
			for ($i=$start; $i < $start+$length; $i++) { 
				$retArr[$keyArr[$i]] = $valArr[$i];
			}
		}else{
			for ($i=$start; $i < count($array); $i++) { 
				$retArr[$keyArr[$i]] = $valArr[$i];
			}
		}
		return $retArr;
	}
	private function createUrl($actionName) {
		switch ($actionName) {
			// 抽奖
			case 'News':
				$addUrl = U('LabelAdmin/News/add') .
					"&model=event&type=draw&action=create&customer=" .
					$this->node_type_name;
				break;
			// 市场调研
			case 'Bm':
				$addUrl = U('LabelAdmin/Bm/add') .
					"&model=event&type=survey&action=create&customer=" .
					$this->node_type_name;
				break;
			// 有奖答题
			case 'Answers':
				$addUrl = U('LabelAdmin/Answers/add') .
					"&model=event&type=question&action=create&customer=" .
					$this->node_type_name;
				break;
			// 投票
			case 'Vote':
				$addUrl = U('LabelAdmin/Vote/add') .
					"&model=event&type=survey&action=create&customer=" .
					$this->node_type_name;
				break;
			// 爱拼才会赢
			case 'Spelling':
				$addUrl = U('LabelAdmin/Spelling/add') .
					"&model=event&type=draw&action=create&customer=" .
					$this->node_type_name;
				break;
			// 礼品派发
			case 'Feedback':
				$addUrl = U('LabelAdmin/Feedback/add') .
					"&model=event&type=gift&action=create&customer=" .
					$this->node_type_name;
				break;
			// 优惠券
			case 'Coupon':
				$addUrl = U('LabelAdmin/Coupon/add') .
					"&model=event&type=coupon&action=create&customer=" .
					$this->node_type_name;
				break;
			// 注册有礼
			case 'Registration':
				$addUrl = U('LabelAdmin/Registration/add') .
					"&model=event&type=Registration&customer=" .
					$this->node_type_name;
				break;
			// 列表模板
			case 'List':
				$addUrl = U('LabelAdmin/List/setActBasicInfo');
				break;
			// 电子海报
			case 'Poster':
				$addUrl = U('LabelAdmin/Poster/add');
				break;
			// 谁是大腕儿
			case 'Dawan':
				$addUrl = U('LabelAdmin/Dawan/add');
				break;
			// 妈妈我爱你
			case 'MamaSjb':
				$addUrl = U('LabelAdmin/MamaSjb/add');
				break;
			// 劳动最光荣
			case 'LaborDay':
				$addUrl = U('LabelAdmin/LaborDay/add');
				break;
			// 打炮总动员
			case 'Spring2015':
				$addUrl = U('LabelAdmin/Spring2015/add');
				break;
			// 端午节
			case 'DuanWu':
				$addUrl = U('LabelAdmin/DuanWu/add');
				break;
			// 圣诞节
			case 'SnowBall':
				$addUrl = U('LabelAdmin/SnowBall/add');
				break;
			// 中秋节
			case 'ZhongQiu':
				$addUrl = U('LabelAdmin/ZhongQiu/add');
				break;
			// 七夕节
			case 'Qixi':
				$addUrl = U('LabelAdmin/Qixi/setActBasicInfo');
				break;
			// 母亲节
			case 'Mama':
				$addUrl = U('LabelAdmin/Mama/add');
				break;
			// 真假大冒险
			case 'LogoGuess':
				$addUrl = U('LabelAdmin/LogoGuess/add') .
					"&model=event&type=315theme&action=create&customer=" .
					$this->node_type_name;
				break;
			// 女人我最大
			case 'Women':
				$addUrl = U('LabelAdmin/Women/add') .
					"&model=event&type=38theme&action=create&customer=" .
					$this->node_type_name;
				break;
			// 天生一对
			case 'Valentine':
				$addUrl = U('LabelAdmin/Valentine/add') .
					"&model=event&type=valentine&action=create&customer=" .
					$this->node_type_name;
				break;
			// 码上有红包
			case 'Special':
				$addUrl = U('LabelAdmin/Special/add') .
					"&model=event&type=envelope&action=create&customer=" .
					$this->node_type_name;
				break;
			// 冠军竞猜
			case 'ChampionGuess':
				$addUrl = U('ZtWorldcup/ChampionGuess/add') .
					"&model=event&type=recruiting&action=create&customer=" .
					$this->node_type_name;
				break;
			// 赛事竞猜
			case 'MatchGuess':
				$addUrl = U('ZtWorldcup/MatchGuess/add');
				break;
			// 签到有礼
			case 'DakaHasGift':
				$addUrl = U('ZtWorldcup/DakaHasGift/add');
				break;
			// 进球有礼
			case 'GoalHasGift':
				$addUrl = U('ZtWorldcup/GoalHasGift/add') .
					"&model=event&type=draw&action=create&customer=" .
					$this->node_type_name;
				break;
			// 粉丝招募
			case 'MemberRegistration':
				$addUrl = U('LabelAdmin/MemberRegistration/add') .
					"&model=event&type=recruiting&action=create&customer=" .
					$this->node_type_name;
				break;
			// 粉丝回馈
			case 'MemberFeedback':
				$addUrl = U('Member/MemberFeedback/add') .
					"&model=event&type=feedback&action=create&customer=" .
					$this->node_type_name;
				break;
			// 图文编辑
			case 'Med':
				$addUrl = U('LabelAdmin/Med/add') .
					"&model=event&type=question&action=create&customer=" .
					$this->node_type_name;
				break;
			// 粽礼寻Ta
			case 'ZongZi':
				$addUrl = U('LabelAdmin/ZongZi/add') .
					"&model=event&type=question&action=create&customer=" .
					$this->node_type_name;
				break;
			// 大转盘抽奖
			case 'DrawLotteryAdmin':
				$addUrl = U('LabelAdmin/DrawLotteryAdmin/setActBasicInfo');
				break;
			// 我是升旗手
			case 'RaiseFlag':
				$addUrl = U('LabelAdmin/RaiseFlag/setActBasicInfo');
				break;
			// 双旦祝福
			case 'TwoFestivalAdmin':
				$addUrl = U('LabelAdmin/TwoFestivalAdmin/setActBasicInfo');
				break;
			// 金猴闹春
			case 'SpringMonkey':
				$addUrl = U('LabelAdmin/SpringMonkey/setActBasicInfo');
				break;
			//决战欧陆之巅
			case 'EuroCup':
			    $addUrl = U('ZtWorldcup/MatchGuess/add');
			    break;
			default:
				break;
		}
		redirect($addUrl);
	}

	private function editUrl($actionName,$id) {
		! empty($actionName) or $this->error("非法操作!");
		! empty($id) or $this->error("非法操作!");
		switch ($actionName) {
			// 抽奖
			case 'News':
				$editUrl = U('LabelAdmin/News/edit');
				break;
			// 市场调研
			case 'Bm':
				$editUrl = U('LabelAdmin/Bm/edit');
				break;
			// 有奖答题
			case 'Answers':
				$editUrl = U('LabelAdmin/Answers/edit');
				break;
			// 投票
			case 'Vote':
				$editUrl = U('LabelAdmin/Vote/edit');
				break;
			// 爱拼才会赢
			case 'Spelling':
				$editUrl = U('LabelAdmin/Spelling/edit');
				break;
			// 礼品派发
			case 'Feedback':
				$editUrl = U('LabelAdmin/Feedback/edit');
				break;
			// 优惠券
			case 'Coupon':
				$editUrl = U('LabelAdmin/Coupon/edit');
				break;
			// 注册有礼
			case 'Registration':
				$editUrl = U('LabelAdmin/Registration/edit');
				break;
			// 列表模板
			case 'List':
				$editUrl = U('LabelAdmin/List/setActBasicInfo');
				break;
			// 电子海报
			case 'Poster':
				$editUrl = U('LabelAdmin/Poster/add');
				break;
			// 谁是大腕儿
			case 'Dawan':
				$editUrl = U('LabelAdmin/Dawan/edit');
				break;
			// 妈妈我爱你
			case 'MamaSjb':
				$editUrl = U('LabelAdmin/MamaSjb/edit');
				break;
			// 劳动最光荣
			case 'LaborDay':
				$editUrl = U('LabelAdmin/LaborDay/edit');
				break;
			// 打炮总动员
			case 'Spring2015':
				$editUrl = U('LabelAdmin/Spring2015/edit');
				break;
			// 端午节
			case 'DuanWu':
				$editUrl = U('LabelAdmin/DuanWu/edit');
				break;
			// 圣诞节
			case 'SnowBall':
				$editUrl = U('LabelAdmin/SnowBall/edit');
				break;
			// 中秋节
			case 'ZhongQiu':
				$editUrl = U('LabelAdmin/ZhongQiu/edit');
				break;
			// 七夕节
			case 'Qixi':
				$editUrl = U('LabelAdmin/Qixi/setActBasicInfo');
				break;
			// 母亲节
			case 'Mama':
				$editUrl = U('LabelAdmin/Mama/edit');
				break;
			// 真假大冒险
			case 'LogoGuess':
				$editUrl = U('LabelAdmin/LogoGuess/edit');
				break;
			// 女人我最大
			case 'Women':
				$editUrl = U('LabelAdmin/Women/edit');
				break;
			// 天生一对
			case 'Valentine':
				$editUrl = U('LabelAdmin/Valentine/edit');
				break;
			// 码上有红包
			case 'Special':
				$editUrl = U('LabelAdmin/Special/edit');
				break;
			// 冠军竞猜
			case 'ChampionGuess':
				$editUrl = U('ZtWorldcup/ChampionGuess/edit');
				break;
			// 赛事竞猜
			case 'MatchGuess':
				$editUrl = U('ZtWorldcup/MatchGuess/edit');
				break;
			// 签到有礼
			case 'DakaHasGift':
				$editUrl = U('ZtWorldcup/DakaHasGift/edit');
				break;
			// 进球有礼
			case 'GoalHasGift':
				$editUrl = U('ZtWorldcup/GoalHasGift/edit');
				break;
			// 粉丝招募
			case 'MemberRegistration':
				$editUrl = U('LabelAdmin/MemberRegistration/edit');
				break;
			// 粉丝回馈
			case 'MemberFeedback':
				$editUrl = U('Member/MemberFeedback/edit');
				break;
			// 图文编辑
			case 'Med':
				$editUrl = U('LabelAdmin/Med/edit');
				break;
			// 粽礼寻Ta
			case 'ZongZi':
				$editUrl = U('LabelAdmin/ZongZi/edit');
				break;
			// 大转盘抽奖
			case 'DrawLotteryAdmin':
				$editUrl = U('LabelAdmin/DrawLotteryAdmin/setActBasicInfo');
				break;
			// 我是升旗手
			case 'RaiseFlag':
				$editUrl = U('LabelAdmin/RaiseFlag/setActBasicInfo');
				break;
			// 双旦祝福
			case 'TwoFestivalAdmin':
				$editUrl = U('LabelAdmin/TwoFestivalAdmin/setActBasicInfo');
				break;
			// 金猴闹春
			case 'SpringMonkey':
				$editUrl = U('LabelAdmin/SpringMonkey/setActBasicInfo');
				break;
			// 微官网
			case 'MicroWeb':
				$editUrl = U('MicroWeb/Index/add', array('mw_batch_id' => $id));
				redirect($editUrl);
				break;
			// 闪购
			case 'GoodsSale':
				$editUrl = U('Ecshop/GoodsSale/edit');
				break;
			//会员招募
			case 'Member':
				$editUrl = U('Wmember/Member/setActBasicInfo');
				break;
			// 门店导航
			case 'Navigate':
				$editUrl = U('Home/Store/navigation');
				redirect($editUrl);
				break;
			// 马上买
			case 'MaShangMai':
				$editUrl = U('Ecshop/MaShangMai/edit');
				break;
		    //决战欧陆之巅
			case 'EuroCup':
			    $editUrl = U('ZtWorldcup/MatchGuess/edit');
			    break;
			default:
				$this->error("此活动无法编辑!");
				break;
		}
		if (in_array($actionName,
			array(
				'Member',
				'Qixi',
				'DrawLotteryAdmin',
				'RaiseFlag',
				'List',
				'TwoFestivalAdmin',
				'SpringMonkey'))) {
			redirect($editUrl . '&m_id=' . $id);
		}
		redirect($editUrl . '&id=' . $id);
	}
}














