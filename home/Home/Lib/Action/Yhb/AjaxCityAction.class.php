<?php
// 选择城市
class AjaxCityAction extends Action {

    public function townCode() {
        // 默认是上海，上海市
        $province_code = "09";
        $city_code = "021";
        $model = M('tcity_code');
        $map = array(
            'city_level' => '3', 
            'province_code' => $province_code, 
            'city_code' => $city_code);
        $query_arr = $model->where($map)->select();
        return $query_arr;
    }
}