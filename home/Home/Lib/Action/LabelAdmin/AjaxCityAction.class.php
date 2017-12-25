<?php
// 选择城市
class AjaxCityAction extends Action {

    /**
     *
     * @var StoresModel
     */
    private $StoresModel;

    public function index() {
        $query_arr = $model = M('tcity_code');
        $field_str = '';
        $map = array();
        if ($_POST['city_type'] == 'province') {
            $map = array('city_level' => '1',);
				if (isset($_REQUEST['province_code_list']) && $_REQUEST['province_code_list']) {
					$map['province_code'] = array('in', $_REQUEST['province_code_list']);
				}
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
        
        $nodeIn = I('get.nodeIn');
        $type = I('get.type');
        $nodeIn = str_replace("'", '', $nodeIn);
        if ($nodeIn != '' && $type != '') {
            $this->StoresModel = D('Stores');
            $query_arr = $this->StoresModel->relationTable($nodeIn, $map, $type) or
                 $query_arr = array();
        } else {
            $query_arr = $model->field($field_str)
                ->where($map)
                ->select() or $query_arr = array();
        }
        
        $this->ajaxReturn($query_arr, "查询成功", 0);
    }
}