<?php 
class ClickSumModel extends Model{
	protected $tableName = "tpopular_module";
	public function insertPopular($nodeId){
		$url = GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		$module = self::getModule();
		$urlArr = array_keys($module);
		if(in_array($url,$urlArr))
		{
			$ret = $this->where(['node_id'=>$nodeId,'url'=>$url])->find();
			if(empty($ret))
			{
				$data = [
					'name'    => $module[$url],
					'url'     => $url,
					'node_id' => $nodeId
				];
				$ret = $this->add($data);
			}else{
				$ret = $this->where(['node_id'=>$nodeId,'url'=>$url])->setInc('click_cnt');
				if($ret === false)
					$this->error('数据库错误');
			}
		}
	}
	public function getPopularModule($nodeId){
		$module = self::getModule();
        $top = self::getTop();
		$urlArr = array_keys($module);
		$map = [
			'node_id' => $nodeId,
			'url' => ['in',$urlArr]
		];
		$ret = $this->field('url,name')
			->where($map)->order('click_cnt DESC')->select();
		$result = [];
		$cnt = count($ret);
        $result[] = [
                'url'  => array_keys($top)[0],
                'name' => array_values($top)[0]
        ];
		for ($i=0; $i < $cnt; $i++) { 
			$result[] = $ret[$i];
		}
		for ($j=$cnt; $j < 4; $j++) {
			$result[] = [
				'url'  => $urlArr[$j],
				'name' => $module[$urlArr[$j]]
			];
		}
		return $result;
	}
	private function getModule(){
		return [
                'MarketActive/Activity/index'         => '多乐互动',
                'Alipay/Index/index'                  => '多米收单',
                'WangcaiPc/NumGoods/index'            => '卡券管理',
                'Home/Index/marketingShow5'           => '多宝电商',
                'Hall/Index/index'                    => '卡券商城',
                'Integral/Integral/integralMarketing' => '多赢积分',
                'Wfx/Fxgl/index'                      => '旺分销'  ,
                'Wmember/Member/infoCenter'           => '会员管理',
                'LabelAdmin/Channel/IndexNew'         => '我的渠道',
                'Home/Store/index'                    => '我的门店',
                'Home/Store/myEpos'                   => '我的终端',
                'Home/Wservice/windex'                => '旺服务'  ,
                'DataCenter/DateData/index'           => '数据中心',
                'Home/AccountInfo/index'              => '我的账户'
		];
	}
	private function getTop(){
        return [
                'Weixin/Weixin/index'                 => '微信公众号'
        ];
    }
}

