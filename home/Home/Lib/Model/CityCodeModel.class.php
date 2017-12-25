<?php

class CityCodeModel extends Model {

    protected $tableName = 'tcity_code';

    public function getWhere($where) {
        return '';
    }

    /**
     * 获取省
     *
     *
     * @param string $city_code 城市编号
     * @return array 数组
     */
    function getProvince($city_code = false) {
        /*
         * $__city_arr = array( '01'=>array('name'=>'福建', 'city'=>array(
         * '591'=>array('name'=>'福州', 'town'=>array( '001'=>'鼓楼', '002'=>'南区', )
         * ), );
         */
        static $province_arr = array();
        $p_code = substr($city_code, 0, 2);
        // $c_code = substr($city_code,2,3);
        // $t_code = substr($city_code,5,3);
        if (! $province_arr) {
            $province_arr = $this->where("city_level = '1'")->getField(
                'province_code,province');
        }
        if ($city_code !== false)
            return $province_arr[$p_code] ? $province_arr[$p_code] : $p_code;
        return $province_arr;
    }

    /**
     * 获取市
     */
    function getCityTown($city_code, $type = null) {
        static $__city_arr;
        $level = 0;
        ($p_code = substr($city_code, 0, 2)) && $level ++;
        ($c_code = substr($city_code, 2, 3)) && $level ++;
        ($t_code = substr($city_code, 5, 3)) && $level ++;
        
        $temp_arr = array(
            $p_code, 
            $c_code, 
            $t_code);
        for ($i = 3; $i > 0; $i --) {
            if ($temp_arr[$i - 1] == "")
                continue;
            $key = $i == 3 ? $city_code : ($i == 2 ? $p_code . $c_code : $p_code);
            
            if ($__city_arr && isset($__city_arr[$key])) {
                $city_info = array(
                    'province' => $__city_arr[$p_code]);
                if ($level >= 2)
                    $city_info['city'] = $__city_arr[$p_code . $c_code];
                if ($level == 3)
                    $city_info['town'] = $__city_arr[$city_code];
                return $type ? $city_info[$type] : $city_info;
            }
            if ($i == 3) {
                $wh = "province_code='$p_code' and city_code='$c_code' and town_code='$t_code'";
            } elseif ($i == 2) {
                $wh = "province_code='$p_code' and city_code='$c_code'";
            } else {
                $wh = "province_code='$p_code'";
                $province = $this->getProvince($city_code);
                $__city_arr[$p_code] = $province;
                $city_info = array(
                    'province' => $province);
                return $type ? $city_info[$type] : $city_info;
            }
            $city_info = $this->where("$wh and city_level = '$i'")
                ->field('province,city,town')
                ->find();
            if (! $city_info) {
                continue;
            }
            $__city_arr[$p_code] = $city_info['province'];
            if ($level >= 2)
                $__city_arr[$p_code . $c_code] = $city_info['city'];
            if ($level == 3)
                $__city_arr[$city_code] = $city_info['town'];
            
            return $type ? $city_info[$type] : $city_info;
        }
        return null;
    }

    /**
     * 获取省市区
     */
    function getAreaText($area_code, $sep = ' ') {
        $city = $this->getCityTown($area_code);
        $area_text = $city['province'] . $sep . $city['city'] . $sep .
             $city['town'];
        return $area_text;
    }

    /**
     *
     * @param type $condition 条件
     * @param type $field 需要的字段
     * @return type
     */
    function getCityCodeAndProvince($condition, $field) {
        $cityArray = $this->group('city')
            ->where($condition)
            ->order('city_level DESC')
            ->field($field)
            ->select();
        return $cityArray;
    }
}
