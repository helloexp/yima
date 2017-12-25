<?php

/**
 * Use: g=Alipay&m=PaysendAction&a=salesmng Author: Zhaobl Date: 2015/12/2
 */
class PayGiveClerkModel extends BaseModel {

    protected $tableName = 'tpay_give_clerk'; // 默认对应数据库名称

    public function index($nodeId, $name, $shop, $statusOption, $customizeNo, 
        $limitStart = '', $limitEnd = '') {
        $model = M('tpay_give_clerk');
        $sql = "SELECT * FROM `tpay_give_clerk` WHERE `node_id` = $nodeId ";
        $data['node_id'] = $nodeId;
        if ($name) {
            $data['clerk_name'] = $name;
            $where['t.clerk_name'] = $name;
        }
        if ($shop && $shop != '无') {
            $storeName = $this->getStore(1, $shop, $nodeId);
            $data['store_id'] = $storeName;
            $where['t.store_id'] = $storeName;
        }
        if ($statusOption == '1') {
            $data['status'] = 0;
            $where['t.status'] = 0;
        }
        if ($statusOption == '2') {
            $data['status'] = 1;
            $where['t.status'] = 1;
        }
        if ($customizeNo) {
            $data['custom_no'] = $customizeNo;
            $where['t.custom_no'] = $customizeNo;
        }
        
        if ($limitStart == '' && $limitEnd == '') {
            $result = $model->where($data)
                ->order('clerk_id desc')
                ->select();
        } else {
            $where['t.node_id'] = $nodeId;
            $result = $model->alias('t')
                ->where($where)
                ->join('tstore_info on t.store_id = tstore_info.id ')
                ->limit($limitStart, $limitEnd)
                ->field(
                't.clerk_id,t.node_id,t.clerk_name,tstore_info.store_name,t.phone_no,t.email,t.custom_no,t.print_cnt,t.cj_cnt,t.status')
                ->order('clerk_id desc')
                ->select();
        }
        
        return $result ? $result : '';
    }

    /**
     *
     * @param null $type 为空查询所有门店名称 为1根据名称查id 为2根据id查名称
     * @param string $shop
     *
     * @return array|string
     */
    public function getStore($type = null, $shop = "", $nodeId = '') {
        $model = M('tstore_info');
        
        switch ($type) {
            case null:
                $data['node_id'] = $nodeId;
                $data['type'] = array(
                    'not in', 
                    '3,4');
                $result = $model->where($data)
                    ->field('store_name')
                    ->select();
                // 将二维数组转为一维数组
                $resultNew = array();
                foreach ($result as $k => $v) {
                    $resultNew[] = $v['store_name'];
                }
                $resultNew = array_unique($resultNew); // 删除重复值
                foreach ($resultNew as $k => $v) {
                    if (! $v)
                        unset($resultNew[$k]); // 删除空值
                }
                return $resultNew;
                break;
            
            case 1:
                $data['store_name'] = $shop;
                $data['node_id'] = $nodeId;
                $result = $model->where($data)
                    ->field('id')
                    ->find();
                return $result ? $result['id'] : '';
                break;
            
            case 2:
                $data['id'] = $shop;
                $result = $model->where($data)
                    ->field('store_name')
                    ->find();
                return $result ? $result['store_name'] : '无';
                break;
            
            default:
                return '无';
        }
    }

    /**
     *
     * @param $nodeId
     * @return mixed|string 6位旺号 不足前面补0
     */
    public function getClientId($nodeId) {
        $model = M('tnode_info');
        $data['node_id'] = $nodeId;
        $clientId = $model->where($data)
            ->field('client_id')
            ->select();
        $clientId = sprintf("%06d", $clientId[0]['client_id']);
        return $clientId;
    }

    /**
     *
     * @return string 10位sys_uni_seq 不足前面补0
     */
    public function getsysUniSeq() {
        $model = new Model();
        $sql = "SELECT wangcai._nextval('sys_uni_seq')  FROM DUAL";
        $num = $model->query($sql);
        $sysUniSeq = sprintf("%010d", 
            $num[0]["wangcai._nextval('sys_uni_seq')"]);
        return $sysUniSeq;
    }

    /**
     *
     * @param $clerk_id //营业员ID
     * @param $nodeId //node_id
     * @param $name //营业员姓名
     * @param $shop //门店名称
     * @param $phone //手机号码
     * @param $email //邮箱
     * @param $customizeNo //自定义编号 新增营业员单个添加操作
     */
    public function singleAddInfo($clerk_id, $nodeId, $name, $shop, $phone, 
        $email, $customizeNo) {
        $model = M('tpay_give_clerk');
        $data['clerk_name'] = $name;
        $data['node_id'] = $nodeId;
        $salesCount = $model->where($data)->count();
        if ($salesCount) {
            return false;
        }
        if ($shop != '无' && $shop != '') {
            $data['store_id'] = $this->getStore(1, $shop, $nodeId);
            if ($data['store_id'] == '') {
                return false;
            }
        }
        
        $data['clerk_id'] = $clerk_id;
        $data['phone_no'] = $phone;
        $data['email'] = $email;
        $data['custom_no'] = $customizeNo;
        $data['status'] = 0;
        
        $result = $model->add($data);
        return $result;
    }

    public function updateStatus($clerk_id, $type) {
        $model = M('tpay_give_clerk');
        $data['clerk_id'] = $clerk_id;
        $model->status = $type;
        $row = $model->where($data)->save();
        return $row;
    }

    public function editSingleInfo($id, $nodeId) {
        $model = M('tpay_give_clerk');
        $data['clerk_id'] = $id;
        $data['node_id'] = $nodeId;
        $result = $model->where($data)->find();
        return $result;
    }

    public function doEditSalesInfo($nodeId, $name, $shop, $phone, $email, 
        $customizeNo, $id) {
        $model = M('tpay_give_clerk');
        if ($shop == '无') {
            $model->store_id = '';
        } else {
            $storename = $this->getStore(1, $shop, $nodeId);
            $model->store_id = $storename ? $storename : '';
        }
        
        $data['clerk_id'] = $id;
        $data['node_id'] = $nodeId;
        $model->clerk_name = $name;
        $model->phone_no = $phone;
        $model->email = $email;
        $model->custom_no = $customizeNo;
        $row = $model->where($data)->save();
        return $row;
    }

    public function getSalesName($clerk_id) {
        $model = M('tpay_give_clerk');
        $data['clerk_id'] = $clerk_id;
        $name = $model->where($data)
            ->field('clerk_name')
            ->find();
        return $name['clerk_name'] ? $name['clerk_name'] : '';
    }
}