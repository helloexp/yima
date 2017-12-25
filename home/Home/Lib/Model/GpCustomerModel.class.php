<?php

/*北京光平客户model*/
class GpCustomerModel extends GpBaseModel
{
    protected $tableName = 'tfb_gp_customer';
    public $_map = array();
    public $_list_ex = array();
    public function _initialize()
    {
        parent::_initialize();
        if ($this->limit) {
            $this->_map = array('merchant_id' => $this->merchant_id);
            $this->_list_ex = array('c.merchant_id' => $this->merchant_id);
        }
    }
    //客户档案列表
    public function customerInfoList($map = array(), $data)
    {
        $where = array();
        import('ORG.Util.Page');
        if (count($map) > 0) {
            $where = array_merge($this->_list_ex, $map);
        } else {
            $where = $this->_list_ex;
        }
        $mapcount = M()->table('tfb_gp_customer c')
                     ->join('tfb_gp_merchant m on c.merchant_id=m.id')
                     ->join('tfb_gp_technician t on c.technician_id=t.id')
                     ->join('tcity_code tc on tc.path=c.household_reg')
                     ->where($where)
                     ->count();
        $Page = new Page($mapcount, 10);
        if ($data) {
            foreach ($data as $key => $val) {
                $Page->parameter .= "&$key=".urlencode($val).'&';
            }
        }
        $show = $Page->show();// 分页显示输出
        $list = M()->table('tfb_gp_customer c')
             ->join('tfb_gp_merchant m on c.merchant_id=m.id')
             ->join('tfb_gp_technician t on c.technician_id=t.id')
             ->join('tcity_code tc on tc.path=c.household_reg')
             ->field('c.*,m.store_short_name,t.name as t_name,tc.province,tc.city,tc.province_code')
             ->where($where)
             ->order('c.come_time desc')
             ->limit($Page->firstRow.','.$Page->listRows)
             ->select();
        return $customerInfo = array(
                'list' => $list,
                'show' => $show,
        );
    }
    //通过客户id查找客户信息
    public function getCustomerInfoById($map)
    {
        $info = M('tfb_gp_customer')
             ->where($map)
             ->find();

        return $info;
    }
    //获取客户id
    public function customerInfo($map)
    {
        $info = M('tfb_gp_customer')
             ->field('id')
             ->where($map)
             ->select();

        return $info;
    }
    //客户档案列表下载
    public function downloadcustomerInfoList($map = array())
    {
        $where = array();
        import('ORG.Util.Page');
        if (count($map) > 0) {
            $where = array_merge($this->_list_ex, $map);
        } else {
            $where = $this->_list_ex;
        }
        $mapcount = M()->table('tfb_gp_customer c')
                     ->join('tfb_gp_merchant m on c.merchant_id=m.id')
                     ->join('tfb_gp_technician t on c.technician_id=t.id')
                     ->join('tcity_code tc on tc.path=c.household_reg')
                     ->where($where)
                     ->count();
            $list = M()->table('tfb_gp_customer c')
                     ->join('tfb_gp_merchant m on c.merchant_id=m.id')
                     ->join('tfb_gp_technician t on c.technician_id=t.id')
                     ->join('tcity_code tc on tc.path=c.household_reg')
                     ->field('c.*,m.store_short_name,t.name as t_name,tc.province,tc.city,tc.province_code')
                     ->where($where)
                     ->order('c.come_time desc')
                     ->select();
        return $customerInfo = array(
                'list' => $list
        );
    }
}
