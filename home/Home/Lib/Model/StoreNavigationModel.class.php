<?php

/**
 * 门店操作相关
 *
 * @author bao
 */
class StoreNavigationModel extends Model
{
    private $node_id = "";

    public function setByNodeId($node_id)
    {
    	$this->node_id = $node_id;
    }

    /**
     * 获取门店活动
     */
    public function getByStoreActivitys($option = false, $other_field = '')
    {
        $where = array(
            'node_id' => $this->node_id,
            'status' => '0',
            );
        if($option['id']) {
            $where['id'] = $option['id'];
        }

        if($option['store_join_flag']) {
            $where['store_join_flag'] = $option['store_join_flag'];
        }

        $field = "id, activity_title";
        if(!empty($other_field)) {
            $field .= ",".$other_field;
        }
        $list = M()->table("tstore_activity_info")
            ->where($where)
            ->field($field)
            ->select();

        return $list;
    }

    /**
     * 获取有活动的门店城市
     */
    public function getByStoreCitys()
    {
        $dataCity = S('noStandardJhActivityCity');
        if($dataCity)
            return $dataCity;

        //市
        $city_sql = "SELECT c.ccode, d.city FROM (
            SELECT SUBSTR(t.area_code, 1, 5) AS ccode
            FROM tstore_info t 
            WHERE t.node_id = '{$this->node_id}' AND t.status = '0' AND IFNULL(t.province_code, '') != ''
            AND EXISTS (SELECT * FROM tstore_activity_relation a, tstore_activity_info b WHERE a.store_id = t.store_id AND a.activity_id = b.id AND b.status='0')
            GROUP BY SUBSTR(t.area_code, 1, 5)
            ) c , tcity_code d
            WHERE c.ccode = d.path AND d.city_level = 2";
        $city_list = M()->query($city_sql);
        $arr = array();
        if(count($city_list) > 0) {
            $definedArr = ['衢'=>'Q', '亳'=>'B', '濮'=>'P', '漯'=>'L', '儋'=>'D', '泸'=>'L'];

            foreach ($city_list as $info) {
                $info['city'] = str_replace(['市','地区'], '', $info['city']);
                $first = substr($info['city'], 0, 3);
                if(isset($definedArr[$first])){
                    $info['py_first'] = $definedArr[$first];
                }else{
                    $py = Pinyin($first, 1);
                    $info['pinyin'] = $py;
                    $info['py_first'] = strtoupper(substr($py, 0, 1));
                }
                $arr[] = $info;
            }

            $sortArr = [];
            foreach($arr as $c=>$key) {
                $sortArr[] = $key['py_first'];
            }
            array_multisort($sortArr, SORT_ASC, $arr);
            S('noStandardJhActivityCity', $arr, 3600);
        }

        return $arr;
    }

    public function getByStoreTowns($area_city)
    {
        $town_list = S("town_list");
        if(!$town_list) {
            //区
            $town_sql = "SELECT c.ccode, c.area_code, d.town FROM (
                SELECT t.id, t.area_code, SUBSTR(t.area_code, 1, 5) AS ccode
                FROM tstore_info t 
                WHERE t.node_id = '{$this->node_id}' AND IFNULL(t.province_code, '') != '' AND IFNULL(t.town_code, '') != ''
                AND EXISTS (SELECT * FROM tstore_activity_relation a, tstore_activity_info b WHERE a.store_id = t.store_id AND a.activity_id = b.id AND b.status='0')
                GROUP BY t.area_code
                ) c , tcity_code d
                WHERE c.area_code = d.path AND d.city_level = 3";
            $town_list = M()->query($town_sql);
            S("town_list", $town_list, 3600);
        }

        $sel_city_town = array();
        foreach($town_list as $v) {
            if($v['ccode'] == $area_city) {
                $sel_city_town[] = array(
                    'area_code' => $v['area_code'],
                    'area_name' => $v['town']
                    );
            }
        }

        return $sel_city_town;
    }

    /**
     * 获取指定条件门店
     *
     * @param $options array | string 查询门店的指定条件
     * @return mixed
     */
    public function getByStoresInfo($option)
    {
        $where = array(
        	'a.node_id' => $this->node_id,
            'a.status' => '0'
        	);

        if(!empty($option['keywork'])) {
            $where['_string'] = "a.store_name like '%".$option['keywork']."%'";
        }

        if(!empty($option['area_code'])) {
            $where['_string'] .= "a.area_code like '".$option['area_code']."%'";
        }

        $userField = "activity_type, start_time, end_time, activity_desc";
        $activity_infos = $this->getByStoreActivitys(array('store_join_flag'=>'1'), $userField);
        $activity_num = count($activity_infos);

        if($option['activity_id']) {
            $activity_data = $this->getByStoreActivitys(array('id'=>$option['activity_id'], 'store_join_flag'=>'1'));
            if($activity_data == null) {
                $activity_num = 0;
                $where['b.activity_id'] = $option['activity_id'];
            }
        }

        $lat = cookie('lat'); // 纬度
        $lng = cookie('lng'); // 经度
        $field = "a.*";
        if($activity_num < 1) {
            $activity_infos = false;
            $field .= ", b.id as b_id";
        }
        $orderBy = "a.id desc";
        if($lat) {
            $field .= ", IFNULL(((a.lbs_x - {$lat}) * (a.lbs_x - {$lat}) + (a.lbs_y - {$lng}) * (a.lbs_y - {$lng})), 10000) lbs";
            $orderBy = "lbs";
        }

    	$tableObj = M()->table("tstore_info a");
        $count = 0;
        if($activity_num < 1) {
            $count = $tableObj->join("tstore_activity_relation b on a.store_id = b.store_id");
        }
	    $count = $tableObj->where($where);
        if($activity_num < 1) {
            $count = $tableObj->group("b.activity_id")
                ->having("b_id > 0");
        }
		$count = $tableObj->count();

		$totalPages = ceil($count/10);
		if($option['p'] > $totalPages) {
			return false;
		}

		$next_page = false;
		$p = 1;
		if(!empty($option['p'])) {
			$p = $option['p'];
		}
		if($p < $totalPages) {
			$next_page = true;
		}
		import('ORG.Util.Page');// 导入分页类
        $Page = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($option as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $page  = $Page->show();

        $list = array();
        $tableObj = M()->table("tstore_info a");
        if($activity_num < 1) {
            $list = $tableObj->join("tstore_activity_relation b on a.store_id = b.store_id");
        }
        $list = $tableObj->field($field)
            ->where($where);
        if($activity_num < 1) {
            $list = $tableObj->group("b.activity_id")
                ->having("b_id > 0");
        }
        $list = $tableObj->order($orderBy)
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        if($list === false) {
            return false;
        }

        foreach($list as $k=>$v) {
        	$this->checkByStoreActivityTitles($list[$k], $activity_infos);
        }

        if($lat) {
			$this->checkByStoreDistance($list, $lat, $lng);
		}

        return array('lists'=>$list, 'next_page'=>$next_page, 'page'=>$page);
    }

    /**
     * 获取单个门店信息
     */
    public function getByStoreDetail($option)
    {
        $userField = "activity_type, start_time, end_time, activity_desc";
        $activity_infos = $this->getByStoreActivitys(array('store_join_flag'=>'1'), $userField);
        $activity_num = count($activity_infos); //全部门店活动数量

        if($activity_num < 1) {
            $sa_flag = $this->_checkByStoreExistAct($option['store_id']); //判断门店是否存在活动
            if(!$sa_flag) {
                return false;
            }
            $activity_infos = false;
        }

    	$where = array(
    		"a.node_id" => $this->node_id,
    		"a.store_id" => $option['store_id'],
            "a.status" => '0'
    		);
    	$info = M()->table("tstore_info a")
    		->join("tgroup_store_relation b on a.store_id = b.store_id")
    		->where($where)
    		->field("a.*, b.store_group_id")
    		->find();
    	if($info === false) {
    		return false;
    	}

    	$sort_flag = false;
    	if($option['activity_id']) {
    		$sort_flag = $option['activity_id'];
    	}

    	$this->checkByStoreActivityTitles($info, $activity_infos, true, $sort_flag);

    	return $info;
    }

    /**
     * 判断门店是否存在活动
     */
    private function _checkByStoreExistAct($store_id)
    {
        $count = M()->table("tstore_activity_relation")
            ->where(array('store_id'=>$store_id))
            ->count();

        if($count < 1) {
            return false;
        }
        return true;
    }

    /**
     * 其它门店(详情页中点击链接)
     */
    public function getByOtherStores($option)
    {
    	$where = array(
            'a.node_id' => $this->node_id,
            'a.status' => '0',
    		'b.store_group_id' => $option['group_id'],
    		);

        $userField = "activity_type, start_time, end_time, activity_desc";
        $activity_infos = $this->getByStoreActivitys(array('store_join_flag'=>'1'), $userField);
        $activity_num = count($activity_infos); //全部门店活动数量
        if($activity_num < 1) {
            $activity_infos = false;
            
        }

    	$lat = cookie('lat'); // 纬度
    	$lng = cookie('lng'); // 经度
        $field = "a.*";
        if($activity_num < 1) {
            $activity_infos = false;
            $act_id_str = session("act_id_str_".$this->node_id);
            if($act_id_str) {
                $where['c.activity_id'] = array('in', explode(',', rtrim($act_id_str, ",")));
            }
            $field .= ", c.id as c_id";
        }
    	$orderBy = "a.id desc";
    	if($lat) {
    		$field .= ", IFNULL(((a.lbs_x - {$lat}) * (a.lbs_x - {$lat}) + (a.lbs_y - {$lng}) * (a.lbs_y - {$lng})), 10000) lbs";
			$orderBy = "lbs";
    	}

        $tableObj = M()->table("tstore_info a");
        $count = $tableObj->join("tgroup_store_relation b on a.store_id = b.store_id");
        if($activity_num < 1) {
            $count = $tableObj->join("tstore_activity_relation c on a.store_id = c.store_id");
        }
        $count = $tableObj->where($where);
        if($activity_num < 1) {
            $count = $tableObj->group("c.activity_id")
                ->having("c_id > 0");
        }
        $count = $tableObj->count();

		$totalPages = ceil($count/10);
		if($option['p'] > $totalPages) {
			return false;
		}

		$next_page = false;
		$p = 1;
		if(!empty($option['p'])) {
			$p = $option['p'];
		}
		if($p < $totalPages) {
			$next_page = true;
		}
		import('ORG.Util.Page');// 导入分页类
        $Page = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($option as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $page  = $Page->show();

        $list = array();
        $tableObj = M()->table("tstore_info a");
        $list = $tableObj->join("tgroup_store_relation b on a.store_id = b.store_id");
        if($activity_num < 1) {
            $list = $tableObj->join("tstore_activity_relation c on a.store_id = c.store_id");
        }
        $list = $tableObj->field($field)
            ->where($where);
        if($activity_num < 1) {
            $list = $tableObj->group("c.activity_id")
                ->having("c_id > 0");
        }
        $list = $tableObj->order($orderBy)
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        if($list === false) {
            return false;
        }

    	foreach($list as $k=>$v) {
        	$this->checkByStoreActivityTitles($list[$k], $activity_infos);
        }

        if($lat) {
			$this->checkByStoreDistance($list, $lat, $lng);
		}

    	return array('lists'=>$list, 'next_page'=>$next_page, 'page'=>$page);
    }

    /**
     * 获取单个门店的活动标题
     * data array 单个门店信息
     * wholeStoreAct array | blue 全门店活动数据
     * is_detail blue 活动组装的数据中是否需要有效时间和描述
     * activity_sort blue | int 整型(门店活动id)代表需要排序(门店详情使用)
     */
    public function checkByStoreActivityTitles(&$data, $wholeStoreAct = false, $is_detail = false, $activity_sort = false)
    {
    	$where = array(
			'a.store_id'=>$data['store_id'],
            'b.status' => '0'
    		);
    	$result = M()->table("tstore_activity_relation a")
    		->join("tstore_activity_info b on a.activity_id = b.id")
    		->where($where)
    		->field("b.*")
    		->select();
        if($wholeStoreAct) {
            if($result != null && $result !== false) {
                $result = array_merge($result, $wholeStoreAct);
            } else {
                $result = $wholeStoreAct;
            }
        }

    	foreach($result as $k=>$v) {
    		$key = $k;
    		if($v['id'] == $activity_sort) {
    			$key = 0;
    			$unshift = array('0' => array());
    			array_unshift($data['act'], $unshift);
    			$data['act'][$key] = array(
	    			'activity_type' => $v['activity_type'],
	    			'activity_title' => $v['activity_title']
	    			);
	    		if($is_detail) {
	    			$data['act_id_str'] .= $v['id'].",";
		    		$data['act'][$key]['activity_date'] = date('Y.m.d', strtotime($v['start_time']))."—".date('Y.m.d', strtotime($v['end_time']));
		    		$data['act'][$key]['activity_desc'] = $v['activity_desc'];
	    		}

	    		continue;
    		}

    		if($activity_sort) {
    			$key = ++$k;
    		}

    		$data['act'][$key] = array(
    			'activity_type' => $v['activity_type'],
    			'activity_title' => $v['activity_title']
    			);
    		if($is_detail) {
    			$data['act_id_str'] .= $v['id'].",";
	    		$data['act'][$key]['activity_date'] = date('Y.m.d', strtotime($v['start_time']))."—".date('Y.m.d', strtotime($v['end_time']));
	    		$data['act'][$key]['activity_desc'] = $v['activity_desc'];
    		}
    	}
    }

    /**
	 * 计算两点之间距离
	 */
	private function checkByStoreDistance(&$list, $lat, $lng)
	{
		foreach ($list as $k => $v) {
            $distance = $this->getDistance($lat, $lng, $v['lbs_x'], $v['lbs_y']);
            $distance = round($distance);
            $km = '';
            if ($distance < 1000) {
                if ($distance < 100) {
                    $km = '<100m';
                } else {
                    $km = $distance.'m';
                }
            }
            if ($distance >= 1000 && $distance <= 10000) {
                $dis_re = $distance / 1000;
                $dis_re = round($dis_re, 2);
                $km = $dis_re.'km';
            }
            if ($distance > 10000) {
                $km = '>10km';
            }

            // dump("distance====".$km);
            $list[$k]['distance'] = $km;
        }
	}

	/**
     *  @desc 根据两点间的经纬度计算距离
     *
     *  @param float $lat 纬度值
     *  @param float $lng 经度值
     */
    private function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000;
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    public function _getUserCity(){
        $cityPath = cookie('city_area_code');

        if($cityPath == null || $cityPath == '') {
            $ip   = get_client_ip();
            $url  = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=" . $ip;
            $data = json_decode(file_get_contents($url));
            if ((string)$data->ret == 0) {
                cookie('city_area_code', "13591");
                cookie('city_area_name', "福州");
                return ;
            }

            $city = $data->city;

            $map = [
                'city'=>array('like', $city.'%'),
                'city_level' => '2'
            ];
            $cityInfo = M('tcity_info')->where($map)->field('path as code, city')->find();
            if(!$cityInfo) {
                cookie('city_area_code', "13591");
                cookie('city_area_name', "福州");
                return ;
            }

            $cityInfo['city'] = str_replace('市', '', $cityInfo['city']);
            cookie('city_area_code', $cityInfo['code']);
            cookie('city_area_name', $cityInfo['city']);
        }
    }
}