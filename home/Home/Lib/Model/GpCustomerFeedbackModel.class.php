<?php

/*北京光平客户评价model*/
class GpCustomerFeedbackModel extends GpBaseModel
{
    protected $tableName = 'tfb_gp_customer_feedback';
    protected $_map = array();
    public function _initialize()
    {
        parent::_initialize();
        if ($this->limit) {
            $this->_map =array('a.merchant_id' => $this->merchant_id);
        }
    }

    public function customerFeedbackInfo($map){
    	$feed=M('tfb_gp_customer_feedback')->where($map)->find();
    	return $feed;
    }

    public function getCommentsInfo($map,$data)
    {
        $mapcount= M()
            ->table("tfb_gp_customer_feedback a")
            ->join("tfb_gp_merchant b on a.merchant_id=b.id")
            ->join("tfb_gp_technician d on a.technician_id=d.id")
            ->join("tfb_gp_customer e on a.customer_id=e.id")
            ->where(array_merge($this->_map, $map))
            ->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();// 分页显示输出
        $commentsList= M()
            ->table("tfb_gp_customer_feedback a")
            ->join("tfb_gp_merchant b on a.merchant_id=b.id")
            ->join("tfb_gp_technician d on a.technician_id=d.id")
            ->join("tfb_gp_customer e on a.customer_id=e.id")
            ->field("a.*,b.store_short_name storename,d.name,d.id techid")
            ->where(array_merge($this->_map, $map))
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('add_time desc')
            ->select();
        return $commentsInfo=array(
            'list'=>$commentsList,
            'show'=>$show
        );
    }



    function downLoadCommentsInfo($map)
{
    $list=M()
        ->table("tfb_gp_customer_feedback a")
        ->join("tfb_gp_merchant b on a.merchant_id=b.id")
        ->join("tfb_gp_customer e on a.customer_id=e.id")
        ->join("tfb_gp_treatment_record c on c.id=a.record_id")
        ->join("tfb_gp_technician d on a.technician_id=d.id")
        ->field("a.*,b.store_short_name,d.name,d.id techid,c.treatment_num")
        ->where(array_merge($this->_map, $map))
        ->order('add_time desc')
        ->select();
    return $list;
}
}
