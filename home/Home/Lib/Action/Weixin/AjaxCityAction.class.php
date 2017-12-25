<?php
// 选择城市
class AjaxCityAction extends Action {

    public function index() {
        $query_arr = $model = M('tcity_code');
        $field_str = '';
        if ($_POST['city_type'] == 'province') {
            $map = array(
                'city_level' => '1');
            $field_str = 'province_code,province';
        } elseif ($_POST['city_type'] == 'city') {
            $map = array(
                'city_level' => '2', 
                'province_code' => I('province_code'));
            $field_str = 'city_code,city';
        } elseif ($_POST['city_type'] == 'town') {
            $map = array(
                'city_level' => '3', 
                'province_code' => I('province_code'), 
                'city_code' => I('city_code'));
            $field_str = 'town_code,town';
        } elseif ($_POST['city_type'] == 'business') {
            $map = array(
                'city_level' => '4', 
                'province_code' => I('province_code'), 
                'city_code' => I('city_code'), 
                'town_code' => I('town_code'));
            $field_str = 'business_circle_code business_code,business_circle business';
        } else {
            $this->ajaxReturn(array(), "参数不足", '1');
        }
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        $this->ajaxReturn($query_arr, "查询成功", 0);
    }
}