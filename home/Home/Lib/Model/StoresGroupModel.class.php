<?php

/**
 * 门店分组相关操作
 *
 * @author wang pan
 */
class StoresGroupModel extends Model {

    protected $tableName = 'tstore_group';

    protected $updateFields = array(
        'group_name');

    protected $_validate = array(
        array(
            'group_name', 
            'require', 
            '分组名称不能为空！', 
            '1', 
            'regex', 
            '3'));

    const DEL_GROUP_STATUS_NON = 1;

    const DEL_GROUP_STATUS_SUCCESS = 2;

    const DEL_GROUP_STATUS_FAILURE = 3;

    /**
     * 添加分组 (分组名称)
     *
     * @param $node_id string 商户编号
     * @param $groupName string 分组名称
     * @return mixed
     */
    public function addGroupName($node_id, $groupName = '') {
        $data['node_id'] = $node_id;
        $data['group_name'] = $groupName;
        $data['add_time'] = date('Ymd') . '100000';
        $result = $this->add($data);
        return $result;
    }

    /**
     * 修改分组名称
     *
     * @param $nodeIn string 商户编号
     * @param $post array 表单数据
     * @return mixed
     */
    public function modGroupName($nodeIn, $post) {
        $nodeIn = str_replace("'", '', $nodeIn);
        $where = array(
            'node_id' => array('in',$nodeIn),
            'id' => $post['gid']);
        
        $result = $this->where($where)->setField('group_name', 
            $post['groupName']);
        
        return $result;
    }

    /**
     * 获取分组内的门店 (store_id)
     *
     * @param $node_id number 商户编号
     * @param $groupId array 门店ID
     * @return mixed
     */
    public function getGroupStoreId($node_id, $groupId) {
        $field = 'i.store_id';
        $where = array(
            'g.node_id' => $node_id, 
            'g.id' => $groupId);
        $join = 'g inner join tgroup_store_relation r on g.id=r.store_group_id inner join tstore_info i on r.store_id=i.store_id';
        
        $hasData = $this->field($field)
            ->where($where)
            ->join($join)
            ->select();
        return $hasData;
    }

    /**
     * 过滤当前商户可操作的门店
     *
     * @param $node_id number 商户编号
     * @param $infoId array 门店ID
     * @return mixed
     */
    public function filterStore($node_id, $infoId) {
        $storeInfoModel = M('tstore_info');
        
        $join = 'g inner join tgroup_store_relation r on g.id=r.store_group_id inner join tstore_info i on r.store_id=i.store_id';
        $field = 'i.store_name,g.group_name';
        $where = array(
            'g.node_id' => $node_id, 
            'r.store_id' => array(
                'in', 
                $infoId));
        
        $count = $storeInfoModel->where(
            array(
                'node_id' => $node_id, 
                'store_id' => array(
                    'in', 
                    $infoId)))->count();
        
        if ($count == count($infoId)) {
            $hasData = $this->field($field)
                ->where($where)
                ->join($join)
                ->find();
            return $hasData;
        }
        return '非法操作';
    }

    /**
     * 添加组内门店
     *
     * @param $infoId array 门店ID
     * @param $groupId string 分组ID
     * @return mixed
     */
    public function addGroupStore($infoId, $groupId) {
        $store_relation = M('tgroup_store_relation');
        
        $storeId = explode(',', $infoId);
        
        $this->startTrans();
        foreach ($storeId as $value) {
            $gurAdd = $store_relation->add(
                array(
                    'store_group_id' => $groupId, 
                    'store_id' => $value));
            
            if (! $gurAdd) {
                $this->rollback();
                return false;
            }
        }
        $this->commit();
        return true;
    }

    /**
     * 删除组内门店
     *
     * @param $infoId array 门店ID
     * @return bool
     */
    public function delGroupStore($infoId) {
        $store_relation = M('tgroup_store_relation');
        
        $result = $store_relation->where(
            array(
                'store_id' => array(
                    'in', 
                    $infoId)))->delete();
        
        return $result;
    }

    /**
     * 删除分组
     *
     * @param $nodeIn string 商户编号
     * @param $groupId number 分组ID
     * @return mixed
     */
    public function delGroup($nodeIn, $groupId) {
        $nodeIn = str_replace("'", '', $nodeIn);
        $where = array(
            'g.node_id' => array('in',$nodeIn),
            'g.id' => $groupId);
        $join = 'g inner join tgroup_store_relation r on g.id=r.store_group_id';
        
        $resultCount = $this->where($where)
            ->join($join)
            ->count();
        
        if ($resultCount > 0) { // 不能删除
            return self::DEL_GROUP_STATUS_NON;
        } else {
            $result = $this->where("id = $groupId")->delete();
            return $result ? self::DEL_GROUP_STATUS_SUCCESS : self::DEL_GROUP_STATUS_FAILURE;
        }
    }

    /**
     * 获取弹窗里的分组数据
     *
     * @param $nodeIn string 机构号
     * @param $where mixed 获取分组的额外条件 默认是取所有分组
     * @param $joinTable mixed 所要关联的表
     * @return mixed
     */
    public function getPopGroupStoreId($nodeIn, $where = false, $joinTable = false) {
        $join = ' a left join tgroup_store_relation b on a.id = b.store_group_id ';
        if ($where) {
            $where = ' a.node_id IN ( ' . $nodeIn .
                 ') and (c.type not in (3,4) or ISNULL(c.type)) and ' . $where;
            $join = ' a LEFT JOIN tgroup_store_relation b on a.id = b.store_group_id LEFT JOIN tstore_info c on b.store_id = c.store_id ';
        } else {
            $where = array(
                'a.node_id' => array('in',$nodeIn),
                'c.type' => array(
                    'not in', 
                    '3,4'));
        }
        if ($joinTable) {
            $join = $join . ' left join ' . $joinTable .
                 ' userTable on c.store_id=userTable.store_id';
        }
        $field = 'a.id,a.group_name,GROUP_CONCAT(DISTINCT c.store_id) storeid,GROUP_CONCAT(DISTINCT c.store_name) storename,GROUP_CONCAT(DISTINCT CONCAT(c.province,c.city,c.town,c.store_name)) search,COUNT(DISTINCT c.store_id) num';
        
        $result = $this->field($field)
            ->where($where)
            ->join($join)
            ->group('a.id')
            ->select();

        return (array) array_filter($result);
    }

    /**
     * 修改指定门店所属分组
     *
     * @param $groupId string
     * @param $storeId string
     * @return mixed
     */
    public function editStoreInGroup($groupId, $storeId) {
        $inGroup = $this->where(
            array(
                'b.store_id' => $storeId))
            ->join(
            ' a left join tgroup_store_relation b on a.id = b.store_group_id')
            ->getField('a.id,a.group_name');
        $result = true;
        if (empty($inGroup)) {
            $result = $this->addGroupStore($storeId, $groupId);
        } else {
            if (empty($inGroup[$groupId])) {
                M('tgroup_store_relation')->where(
                    array(
                        'store_id' => $storeId))->setField('store_group_id', 
                    $groupId);
            }
        }
        return $result;
    }

    /**
     * 获取指定门店所属的分组名称
     *
     * @param $nodeId string 商户编号
     * @param $storeId string 门店Id
     * @return string
     */
    public function groupName($nodeId, $storeId) {
        $groupName = $this->where(
            array(
                'a.node_id' => $nodeId, 
                'b.store_id' => $storeId))
            ->join(
            ' a left join tgroup_store_relation b on a.id = b.store_group_id')
            ->getField('a.group_name');
        return $groupName;
    }

    /**
     * 分配门店到指定分组，如果分组不存在就创建后分配
     *
     * @param $nodeId string
     * @param $groupName string
     * @param $storeId string
     * @return mixed
     */
    public function setGroup($nodeId, $groupName, $storeId) {
        $getGroupName = $this->where(
            " node_id=" . $nodeId . " and group_name = '" . $groupName . "' ")->find();
        $result = array(
            'status' => 1, 
            'info' => '分组设置成功');
        if (empty($getGroupName)) {
            $groupId = $this->addGroupName($nodeId, $groupName);
            if ($groupId) {
                $addData = array(
                    'store_group_id' => $groupId, 
                    'store_id' => $storeId);
                $isOk = M('tgroup_store_relation')->add($addData);
                if (! $isOk) {
                    $result['status'] = 0;
                    $result['info'] = '分配门店到组失败';
                }
            } else {
                $result['status'] = 0;
                $result['info'] = '创建分组失败';
            }
        } else {
            $saveData = array(
                'store_group_id' => $getGroupName['id'], 
                'store_id' => $storeId);
            $isOk = M('tgroup_store_relation')->add($saveData);
            if (! $isOk) {
                $result['status'] = 0;
                $result['info'] = '分配门店到组失败';
            }
        }
        return $result;
    }

    /**
     * @param $where
     *
     * @return array 获取所有分组信息
     */
    public function getGroupInfo($where)
    {
        $allGroup = M('tgroup_store_relation')->alias('r')
                ->join("LEFT JOIN tstore_group g ON g.id=r.store_group_id ")
                ->join("LEFT JOIN tstore_info i ON i.store_id=r.store_id ")
                ->where($where)
                ->field('r.store_group_id as id,g.group_name,GROUP_CONCAT(DISTINCT r.store_id) as storeid,GROUP_CONCAT(DISTINCT i.store_name) storename,GROUP_CONCAT(DISTINCT CONCAT(i.province,i.city,i.town,i.store_name)) search,COUNT(r.store_group_id) AS num ')
                ->group('r.store_group_id')->select();
        return $allGroup? $allGroup: array();
    }


    /**
     * @param $where
     *
     * @return array 获取所有已经分组的门店
     */
    public function getIsGroup($where)
    {
        $allGroupStore = M('tgroup_store_relation')->alias('r')
                ->join("LEFT JOIN tstore_group g ON g.id=r.store_group_id ")
                ->join("LEFT JOIN tstore_info i ON i.store_id=r.store_id ")
                ->where($where)
                ->field('r.store_id ')->select();
        return $allGroupStore? $allGroupStore: array();
    }


}

