<?php
// 选择城市
class AjaxCityAction extends Action {

    public function index() {
        $query_arr = $model = M('tcity_code');
        $field_str = '';
        $node_id = I('get.node_id');
        
        if ($_POST['city_type'] == 'province') {
            $map = array(
                'city_level' => '1');
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'province_code,province';
        } elseif ($_POST['city_type'] == 'city') {
            $map = array(
                'city_level' => '2', 
                'province_code' => $_POST['province_code']);
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and tcity_code.city_code =a.city_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'city_code,city';
        } elseif ($_POST['city_type'] == 'town') {
            $map = array(
                'city_level' => '3', 
                'city_code' => $_POST['city_code'], 
                'province_code' => $_POST['province_code']);
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and tcity_code.city_code =a.city_code and tcity_code.town_code =a.town_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'town_code,town';
        } else {
            $this->ajaxReturn(array(), '非法参数');
            exit();
        }
        
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        $this->ajaxReturn($query_arr, "查询成功！" . M()->getDbError(), 0);
    }
    // 有店铺的城市才显示
    public function index2() {
        $query_arr = $model = M('tcity_code');
        $field_str = '';
        $node_id = I('get.node_id');
        $crs = M('tstore_info')->field(
            array(
                'GROUP_CONCAT(province_code)' => 'province_code', 
                'GROUP_CONCAT(city_code)' => 'city_code', 
                'GROUP_CONCAT(town_code)' => 'town_code'))
            ->where(
            array(
                'node_id' => $node_id, 
                'gps_flag' => 1))
            ->find();
        
        if ($_POST['city_type'] == 'province') {
            $map = array(
                'city_level' => '1');
            $map['tcity_code.province_code'] = array(
                'in', 
                '' . $crs['province_code']);
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'province_code,province';
        } elseif ($_POST['city_type'] == 'city') {
            $map = array(
                'city_level' => '2', 
                'province_code' => $_POST['province_code']);
            $map['tcity_code.city_code'] = array(
                'in', 
                '' . $crs['city_code']);
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and tcity_code.city_code =a.city_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'city_code,city';
        } elseif ($_POST['city_type'] == 'town') {
            $map = array(
                'city_level' => '3', 
                'city_code' => $_POST['city_code'], 
                'province_code' => $_POST['province_code']);
            $map['tcity_code.town_code'] = array(
                'in', 
                '' . $crs['town_code']);
            $map['_string'] = " exists (select 1 from tstore_info a where tcity_code.province_code =a.province_code and tcity_code.city_code =a.city_code and tcity_code.town_code =a.town_code and a.node_id in (" .
                 $this->nodeIn($node_id) . ") )";
            $field_str = 'town_code,town';
        } else {
            $this->ajaxReturn(array(), '非法参数');
            exit();
        }
        
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        $this->ajaxReturn($query_arr, "查询成功！" . M()->getDbError(), 0);
    }

    public function index3() {
        $query_arr = $model = M('tcity_code');
        $field_str = '';
        if ($_POST['city_type'] == 'province') {
            $map = array(
                'city_level' => '1');
            $field_str = 'province_code,province';
        } elseif ($_POST['city_type'] == 'city') {
            $map = array(
                'city_level' => '2', 
                'province_code' => $_POST['province_code']);
            $field_str = 'city_code,city';
        } elseif ($_POST['city_type'] == 'town') {
            $map = array(
                'city_level' => '3', 
                'city_code' => $_POST['city_code'], 
                'province_code' => $_POST['province_code']);
            $field_str = 'town_code,town';
        } else {
            $this->ajaxReturn(array(), '非法参数');
            exit();
        }
        
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        $this->ajaxReturn($query_arr, "查询成功！" . M()->getDbError(), 0);
    }

    private function nodeIn($nodeId = null) {
        static $_node_full_id = array();
        if ($nodeId) {
            if (isset($_node_full_id[$nodeId])) {
                $path = $_node_full_id[$nodeId];
            } else {
                $path = M('tnode_info')->where(
                    array(
                        'node_id' => $nodeId))->getField('full_id');
                $_node_full_id[$nodeId] = $path;
            }
        } else {
            $path = $this->nodePath;
        }
        if (! $path) {
            return "'" . $this->nodeId . "'";
        }
        return "select node_id from tnode_info where full_id like '" . $path .
             "%'";
    }
}