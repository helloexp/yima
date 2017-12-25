<?php

/*北京光平客户预约model*/
class GpAppointmentModel extends GpBaseModel
{
    protected $tableName = 'tfb_gp_appointment';
    protected $_map = array();
	protected $_map1=array();
    public function _initialize()
    {	 parent::_initialize();
        if ($this->limit) {
            $this->_map =array('merchant_id' => $this->merchant_id);
			$this->_map1=array('a.merchant_id'=>$this->merchant_id);


        }
    }

    public function getAppInfo($map){
    	$count=M()->table('tfb_gp_appointment ta')
    			->join('tfb_gp_merchant tm on ta.merchant_id=tm.id')
    			->field('ta.*,tm.store_name')
    			->where(array_merge($this->_map, $map))
    			->count();
    	import('ORG.Util.Page');// 导入分页类
		$Page = new Page($mapcount,5);// 实例化分页类 传入总记录数和每页显示的记录数
		$show = $Page->show();// 分页显示输出
    	$appInfo=M()->table('tfb_gp_appointment ta')
    			->join('tfb_gp_merchant tm on ta.merchant_id=tm.id')
    			->field('ta.*,tm.store_name')
    			->where(array_merge($this->_map, $map))
    			->limit(5)
    			->select();
    	return $appInfo;
    }
    public function getAppInfoById($map){
    	$appInfo=M()->table('tfb_gp_appointment')
    			->where($map)
    			->find();
    			return $appInfo;
    }
    public function invitationCardInfo(){
    	$cardInfo=M('tfb_gp_invite_config')
    			->find();
    	return $cardInfo;
    }



	public function getAppointmentList($map,$data)
	{	$where=[];
		if(count($map)>0)
		{
			$where=array_merge($this->_map1, $map);
		}
		else{
			$where=$this->_map1;
		}
		$mapcount= M()
			->table("tfb_gp_appointment a")
			->join("tfb_gp_merchant b on a.merchant_id=b.id")
			->where($where)->count();
		import('ORG.Util.Page');// 导入分页类
		$Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
		foreach ($data as $key => $val) {
			$Page->parameter .= "&$key=" . urlencode($val) . '&';
		}
		$show = $Page->show();// 分页显示输出
		$list= M()
			->table("tfb_gp_appointment a")
			->join("tfb_gp_merchant b on a.merchant_id=b.id")
			->field("a.*,b.store_short_name")
			->where($where)
			->order('a.add_time desc')
			->limit($Page->firstRow . ',' . $Page->listRows)
			->select();

		return $appointmentInfo=array(
			'list'=>$list,
			'show'=>$show
		);



	}
	//创建档案中预约客户列表ajax数据返回
	public function appointInfoReturn($map,$data){
		$count=M()->table('tfb_gp_appointment ta')
				  ->join('tfb_gp_merchant tm on tm.id=ta.merchant_id')
				  ->where(array_merge($this->_map, $map))
				  ->count();
		import('ORG.Util.Page');// 导入分页类
		$Page = new Page($count, 5);// 实例化分页类 传入总记录数和每页显示的记录数
		foreach ($data as $key => $val) {
			$Page->parameter .= "&$key=" . urlencode($val) . '&';
		}
		$show = $Page->show1();// 分页显示输出
		$list=M()->table('tfb_gp_appointment ta')
				  ->join('tfb_gp_merchant tm on tm.id=ta.merchant_id')
				  ->field('ta.*,tm.store_short_name')
				  ->where(array_merge($this->_map, $map))
				  ->limit($Page->firstRow . ',' . $Page->listRows)
				  ->select();
		return $return=array('list'=>$list,'show'=>$show);
	}
	public function downloadAppointmentList($map)
	{	$where=[];
		if(count($map)>0)
		{
			$where=array_merge($this->_map1, $map);
		}
		else{
			$where=$this->_map1;
		}
		$list= M()

			->table("tfb_gp_appointment a")
			->join("tfb_gp_merchant b on a.merchant_id=b.id")
			->field("a.*,b.store_short_name")
			->where($where)
			->order('a.add_time desc')
			->select();

		return $list;
	}

}
