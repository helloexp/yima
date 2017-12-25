<?php

/*北京光平商户/门店model*/
class GpMerchantModel extends GpBaseModel
{
    protected $tableName = 'tfb_gp_merchant';
    public $_map = array();
    public $_map_my = array();
    public function _initialize()
    {
        parent::_initialize();

        if ($this->limit) {
            $this->_map = array('merchant_id' => $this->merchant_id);
            $this->_map_my = array('id' => $this->merchant_id);
        }
    }
    /**
     * 获取符合条件的商户数量.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     *
     * @return int
     */
    public function getMerchantListCount($where)
    {
        return M('tfb_gp_merchant')->where(array_merge($this->_map_my, $where))->count();
    }
    /**
     * 获取符合条件的商户列表.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     * @param $order = 'id desc'
     * @param $limit
     *
     * @return array()
     */
    public function getMerchantList($where, $order = 'id desc', $limit = 10)
    {
        $List = M('tfb_gp_merchant')
            ->where(array_merge($this->_map_my, $where))
            ->order($order)
            ->limit($limit)
            ->select();
        $status = array(
            '0' => '正常',
            '1' => '待审核',
            '2' => '已解约',
            '3' => '已拒绝',
        );
        foreach ($List as &$v) {
            $v['technician_count'] = M('tfb_gp_technician')->where(array('merchant_id' => $v['id'], 'status' => 0))->count();
            $v['customer_count'] = M('tfb_gp_customer')->where(array('merchant_id' => $v['id']))->count();
            $v['status_text'] = $status[$v['status']];
        }

        return $List;
    }
    /**
     * 根据id获取职位.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $id
     *
     * @return array()
     */
    public function getMerchantById($id)
    {
        $Info = M('tfb_gp_merchant')->where(array('id' => $id))->find();

        return $Info;
    }
    public function getOptionList($map)
    {
        $merchant = M('tfb_gp_merchant')
            ->where(array_merge($this->_map_my, $map))
            ->getField('id,store_short_name');

        return $merchant;
    }
    public function getmerchantIdsByName($map)
    {
        $ids = M('tfb_gp_merchant')
            ->where($map)
            ->getField('GROUP_CONCAT(id)');

        return $ids;
    }
    public function merchantList($where)
    {
        $merchant = M('tfb_gp_merchant')
            ->field('id,store_short_name')
            ->where(array_merge($this->_map_my, $where))
            ->select();

        return $merchant;
    }
    public function merchantListWap($map)
    {
        $merchant = M('tfb_gp_merchant')
            ->field('id,store_short_name')
            ->where($map)
            ->select();

        return $merchant;
    }
    public function merchantInfo($map)
    {
        $merchant = M('tfb_gp_merchant')
            ->field('address,store_phone')
            ->where($map)
            ->find();

        return $merchant;
    }
    public function merchant_info($map)
    {
        $merchant = M('tfb_gp_merchant')
            ->where($map)
            ->find();

        return $merchant;
    }

    /**
     * 获取符合条件的用户数量.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     *
     * @return int
     */
    public function getUserListCount($where)
    {
        return M('tuser_info')->where(array_merge($this->_map, $where))->count();
    }
    /**
     * 获取符合条件的用户列表.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     * @param $order = 'id desc'
     * @param $limit
     *
     * @return array()
     */
    public function getUserList($where, $order = 'id desc', $limit = 10)
    {
        $List = M('tuser_info')
            ->where(array_merge($this->_map, $where))
            ->order($order)
            ->limit($limit)
            ->select();
        foreach ($List as &$v) {
            $v['store_short_name'] = M('tfb_gp_merchant')->where(array('id' => $v['merchant_id']))->getField('store_short_name');
        }

        return $List;
    }
}
