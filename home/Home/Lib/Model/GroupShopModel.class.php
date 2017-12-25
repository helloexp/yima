<?php

class GroupShopModel extends BaseModel {

    public function groupDetail($type=false,$node_id,$groupTo='',$shopName='',$shopStatus='',$badd_time='',$eadd_time='',$storeId='',$limitStart = '',
            $limitEnd = '')
    {
        //验证明细

        if($groupTo){
            $data['t.code_type'] = $groupTo;
        }
        if($shopName){
            $data['i.goods_name'] = array('like',"%$shopName%");
        }
        if($storeId){
            $data['t.store_id'] = $storeId;
        }
        if($badd_time && $eadd_time){
            $data['t.trans_time'] = array(array('gt',$badd_time),array('lt',$eadd_time));
        }
        if($badd_time && !$eadd_time){
            $data['t.trans_time'] = array(array('gt',$badd_time));
        }
        if(!$badd_time && $eadd_time){
            $data['t.trans_time'] = array(array('lt',$eadd_time));
        }
        if(!$badd_time && !$eadd_time){
            $data['t.trans_time'] = array(array('gt',date('YmdHis',strtotime(date('Y-m-d')))),array('lt',getTime(1)));
        }


        $data['t.node_id'] = $node_id;
        //$data['i.node_id'] = $node_id;

        if($limitStart && $limitEnd){
            $type = true;
        }


        switch($type) {
            case false:

                /*SELECT t.ticket_number,i.goods_name,t.code_type,t.trans_time,s.store_name,t.node_id,s.store_id
FROM tgroup_buy_verify_trace t
LEFT JOIN tgroup_buy_goods_info i ON i.goods_id = t.group_goods_id AND t.node_id = i.node_id
LEFT JOIN tstore_info s ON s.store_id=t.store_id
WHERE t.node_id='00040495' AND t.trans_time > '20160601000000' AND t.trans_time < '20160613185258'*/


                $info = M('tgroup_buy_verify_trace')->alias('t')
                        ->join("left join tgroup_buy_goods_info i ON i.goods_id = t.group_goods_id AND t.node_id = i.node_id")
                        ->join("LEFT JOIN tstore_info s ON s.store_id=t.store_id ")
                        ->where($data)
                        ->field('t.ticket_number,i.goods_name,t.code_type,t.trans_time,s.store_name')
                        ->select();
                //echo $sql;
                break;
            case true:
                $info = M('tgroup_buy_verify_trace')->alias('t')
                        ->join("left join tgroup_buy_goods_info i ON i.goods_id = t.group_goods_id AND t.node_id = i.node_id")
                        ->join("LEFT JOIN tstore_info s ON s.store_id=t.store_id ")
                        ->where($data)
                        ->field('t.ticket_number,i.goods_name,t.code_type,t.trans_time,s.store_name')
                        ->limit($limitStart, $limitEnd)->select();
                break;
            default:
                $info = '';
                break;
        }
        return $info? $info: array();
    }

    public function groupShop($type=false,$node_id,$groupTo='',$shopName='',$shopStatus='',$badd_time='',$eadd_time='',$storeId='',$limitStart = '',
            $limitEnd = '')
    {
        //按商品统计
        $data['i.status'] = $shopStatus;
        if($groupTo){
            $data['s.code_type'] = $groupTo;
        }
        if($shopName){
            $data['i.goods_name'] = array('like',"%$shopName%");
        }
        if($storeId){
            $data['s.store_id'] = $storeId;
        }
//        WHERE ( i.status = '0' )  AND  (s.trans_date >= '20160601') AND (s.trans_date <= '20160613')
//        AND ( s.node_id = '00040495' ) AND s.code_type='1' GROUP BY i.goods_name

        if($eadd_time == '-1'){
            $temp = $badd_time;
            $data['s.trans_date'] = $temp;
            //            $badd_time = $temp.'000000';
            //            $eadd_time = $temp.'235959';
            //$data['t.trans_time'] = array(array('gt',$badd_time),array('lt',$eadd_time));
        }else{
            if($badd_time && $eadd_time){
                $data['s.trans_date'] = array(array('egt',$badd_time),array('elt',$eadd_time));
            }
            if($badd_time && !$eadd_time){
                $data['s.trans_date'] = array(array('egt',$badd_time));
            }
            if(!$badd_time && $eadd_time){
                $data['s.trans_date'] = array(array('elt',$eadd_time));
            }
        }

        
        $data['s.node_id'] = $node_id;

        if($limitStart && $limitEnd){
            $type = true;
        }

        switch($type) {
            case false:
                /*SELECT i.goods_name,i.goods_id,i.code_type,s.trans_date,i.node_id,SUM(verify_cnt) COUNT,SUM(verify_cnt*IFNULL(settle_price,0)) amt
FROM tgroup_buy_day_stat s
INNER JOIN tgroup_buy_goods_info i ON i.id=s.group_buy_id*/
                $info = M('tgroup_buy_day_stat')->alias('s')
                        ->join('INNER JOIN tgroup_buy_goods_info i ON i.id=s.group_buy_id ')
                        ->where($data)
                        ->field('i.goods_name,i.goods_id,i.code_type,s.trans_date,i.node_id,SUM(verify_cnt) COUNT,SUM(verify_cnt*IFNULL(settle_price,0)) amt ')
                        ->group('i.goods_name')->select();

                break;
            case true:
                $info = M('tgroup_buy_day_stat')->alias('s')
                        ->join('INNER JOIN tgroup_buy_goods_info i ON i.id=s.group_buy_id ')
                        ->where($data)
                        ->field('i.goods_name,i.goods_id,i.code_type,s.trans_date,i.node_id,SUM(verify_cnt) COUNT,SUM(verify_cnt*IFNULL(settle_price,0)) amt')
                        ->group('i.goods_name')->limit($limitStart, $limitEnd)->select();
                break;
            default:
                $info = '';
                break;
        }

        return $info? $info: array();

    }

    public function groupDate($type=false,$node_id,$groupTo='',$badd_time='',$eadd_time='',$storeId='',$limitStart = '',
            $limitEnd = '')
    {
        //按日期统计
        if($groupTo){
            $data['a.code_type'] = $groupTo;
        }
        if($storeId){
            $data['a.store_id'] = $storeId;
        }

        if($eadd_time == '-1'){
            $temp = ($badd_time+10);
            $data['a.trans_date'] = array(array('egt',$badd_time),array('elt',$temp));
        }else{
            if($badd_time && $eadd_time){
                $data['a.trans_date'] = array(array('egt',$badd_time),array('elt',$eadd_time));
            }
            if($badd_time && !$eadd_time){
                $data['a.trans_date'] = array(array('egt',$badd_time));
            }
            if(!$badd_time && $eadd_time){
                $data['a.trans_date'] = array(array('elt',$eadd_time));
            }
            if($badd_time && $badd_time == $eadd_time){
                $data['a.trans_date'] = array(array('eq',$badd_time));
            }
        }

        $data['a.node_id'] = $node_id;

        if($limitStart && $limitEnd){
            $type = true;
        }

        switch($type) {
            case false:
               /* SELECT a.trans_date,b.goods_name,SUM(verify_cnt),SUM(verify_cnt*IFNULL(settle_price,0))
                FROM tgroup_buy_day_stat  a
LEFT JOIN tgroup_buy_goods_info b ON group_buy_id = b.id WHERE a.node_id='00040495'
            AND a.trans_date >= '20160601' AND a.trans_date <= '20160613'
GROUP BY a.trans_date*/

                $info = M('tgroup_buy_day_stat')->alias('a')
                        ->join('LEFT JOIN `tgroup_buy_goods_info` b ON a.`group_buy_id` = b.`id`')
                        ->where($data)
                        ->field('a.trans_date,b.goods_name,SUM(verify_cnt) cnt,SUM(verify_cnt*IFNULL(settle_price,0)) amt')
                        ->group('a.trans_date')->select();

                break;
            case true:
                $info = M('tgroup_buy_day_stat')->alias('a')
                        ->join('LEFT JOIN `tgroup_buy_goods_info` b ON a.`group_buy_id` = b.`id`')
                        ->where($data)
                        ->field('a.trans_date,b.goods_name,SUM(verify_cnt) cnt,SUM(verify_cnt*IFNULL(settle_price,0)) amt')
                        ->group('a.trans_date')->limit($limitStart, $limitEnd)->select();
                break;
            default:
                $info = '';
                break;
        }
        return $info? $info: array();
    }

    public function groupStore($type=false,$node_id,$groupTo='',$badd_time='',$eadd_time='',$storeId='',$limitStart = '',
            $limitEnd = '')
    {
        //按门店统计
        if($groupTo){
            $data['a.code_type'] = $groupTo;
        }
        if($storeId){
            $data['a.store_id'] = $storeId;
        }

        if($eadd_time == '-1'){
            $temp = $badd_time;
            $data['a.trans_date'] = $temp;
            $badd_time = $temp.'000000';
            $eadd_time = $temp.'235959';
            //$data['t.trans_time'] = array(array('gt',$badd_time),array('lt',$eadd_time));
        }else{
            if($badd_time && $eadd_time){
                $data['a.trans_date'] = array(array('egt',$badd_time),array('elt',$eadd_time));
            }
            if($badd_time && !$eadd_time){
                $data['a.trans_date'] = array(array('egt',$badd_time));
            }
            if(!$badd_time && $eadd_time){
                $data['a.trans_date'] = array(array('elt',$eadd_time));
            }
            if($badd_time && $badd_time == $eadd_time){
                $data['a.trans_date'] = array(array('eq',$badd_time));
            }
        }





        $data['a.node_id'] = $node_id;

        if($limitStart && $limitEnd){
            $type = true;
        }

        switch($type) {
            case false:
                $info = M('tgroup_buy_day_stat')->alias('a')
                        ->join('LEFT JOIN `tgroup_buy_goods_info` b ON a.`group_buy_id` = b.`id`')
                        ->join('LEFT JOIN tstore_info s ON s.store_id=a.store_id ')
                        ->where($data)
                        ->field('trans_date,SUM(verify_cnt) cnt,SUM(verify_cnt*IFNULL(b.goods_price,0)) price,SUM(verify_cnt*IFNULL(settle_price,0)) amt,s.store_name')
                        ->group('a.store_id')->select();
                break;
            case true:
                $info = M('tgroup_buy_day_stat')->alias('a')
                        ->join('LEFT JOIN `tgroup_buy_goods_info` b ON a.`group_buy_id` = b.`id`')
                        ->join('LEFT JOIN tstore_info s ON s.store_id=a.store_id ')
                        ->where($data)
                        ->field('trans_date,SUM(verify_cnt) cnt,SUM(verify_cnt*IFNULL(b.goods_price,0)) price,SUM(verify_cnt*IFNULL(settle_price,0)) amt,s.store_name')
                        ->group('a.store_id')->limit($limitStart, $limitEnd)->select();
                break;
            default:
                $info = '';
                break;
        }
        return $info? $info: array();
    }

    /**
     * @param        $node_id
     * @param string $status
     *
     * @return mixed|string 我的团购商品
     */
    public function myGroupShop($type=false,$node_id,$groupTo='',$shopName='',$shopStatus='',$limitStart='',$limitEnd='')
    {
        $data['node_id'] = $node_id;

        if($groupTo != ''){
            $data['code_type'] = $groupTo;
        }

        if($shopName != ''){
            $data['goods_name'] = array('like',"%$shopName%");
        }

        if($shopStatus == '1'){
            $data['status'] = $shopStatus;
        }else{
            $data['status'] = '0';
        }

        if($limitStart && $limitEnd){
            $type = true;
        }
        switch($type){
            case false:
                $info = M('tgroup_buy_goods_info')->where($data)->select();
                $info = count($info);
                break;
            case true:
                $info = M('tgroup_buy_goods_info')->where($data)->limit($limitStart,$limitEnd)->select();
                break;
            default:
                $info = '';
        }

        return $info? $info: '';
    }

    /**
     * 商品管理拿数据
     * @param $node_id
     * @param $code_type
     *
     * @return mixed|string
     */
    public function groupShopData($node_id,$code_type)
    {
        $data['node_id'] = $node_id;
        $data['code_type'] = $code_type;
        $data['status'] = '0';
        $info = M('tgroup_buy_goods_info')->where($data)->select();
        return $info? $info: '';
    }

    /**
     * 查询商户是否开通糯米或是美团点评
     * @param $node_id
     * @param $pay_type
     *
     * @return bool
     */
    public function myGroupAccount($node_id,$pay_type)
    {
        $data['node_id'] = $node_id;
        $data['pay_type'] = $pay_type;
        $data['status'] = '1';
        $result = M('tzfb_offline_pay_info')->where($data)->find();
        return $result? $result : '';
    }

    /**
     * 获取商户信息
     * @param $node_id
     * @param $str //字段名
     * @return mixed
     */
    public function getNodeInfo($node_id,$str)
    {
        $data['node_id'] = $node_id;
        $contract_no = M('tnode_info')->where($data)->getField($str);
        return $contract_no;
    }

    /**
     * 绑定平台
     * @param $node_id
     * @param $node_name
     * @param $terrace
     *
     * @return bool
     */
    public function bindingEdit($type,$terrace,$node_id,$email='',$node_name='')
    {
        $payInfoModel = M('tzfb_offline_pay_info');
        switch($terrace){
            case 1:
                $pay_type = '21';
                break;
            case 0:
                $pay_type = '22';
                break;
            default:
                return false;
            break;
        }

        $data['node_id'] = $node_id;
        $data['pay_type'] = $pay_type;

        if($type == 1){
            //绑定
            $count = $payInfoModel->where($data)->count();
            if($count){
                //如果存在 证明是二次开通 只需要更改状态更新邮箱便可
                $save['status'] = '1';
                $save['zfb_account'] = $email;
                $payInfoModel->where($data)->save($save);
            }else{
                $data['add_time'] = getTime(1);
                $data['node_name'] = $node_name;
                $data['status'] = '1';
                $data['zfb_account'] = $email;
                $payInfoModel->where($data)->add($data);
            }
        }
        if($type == 0){
            //解绑
            if($terrace == 1){
                $data['zfb_account'] = $email;
            }
            $save['status'] = '2';
            $payInfoModel->where($data)->save($save);
            log_write('【'.__LINE__.'】解绑：'.M()->_sql());
        }
    }

    /* 请求支撑接口 */
    public function groupBinding($data)
    {
        $url = C('ISS_SERV_FOR_IMAGECO') or die('[ISS_SERV_FOR_IMAGECO]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');

        log_write('【'.__LINE__.'】发送报文内容：'.$str);

        $error = '';
        $result_str = httpPost($url, $str, $error);
        if ($error) {
            log_write('【'.__LINE__.'】出现错误：'.$error);
            echo $error;
            return '';
        }

        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();
        $arr = utf8Array($arr['Status']);

        log_write('【'.__LINE__.'】'."返回数组：[TransactionID]" . $arr['TransactionID'].' [Status] '.print_r($arr, true));

        return $arr['0'] == '0000' ? 1 : 0;
    }

    /**
     * 编辑操作
     * @param $datas
     *
     * @return bool
     */
    public function myGroupShopEdit($datas)
    {
        $data['id'] = $datas['id'];
        $data['goods_name'] = $datas['goods_name'];
        $data['goods_price'] = $datas['goods_price'];
        $data['settle_price'] = $datas['settle_price'];
        M('tgroup_buy_goods_info')->save($data);
    }

    /**
     * 删除操作
     * @param $id
     */
    public function myGroupShopDelete($id)
    {
        $data['id'] = $id;
        $data['status'] = '1';
        M('tgroup_buy_goods_info')->save($data);
    }


    /**
     *查询门店信息
     * @param null $type 为空查询所有门店名称 为1根据名称查id 为2根据id查名称
     * @param string $shop
     *
     * @return array|string
     */
    public function getStoreInfo($type = null, $shop = "", $nodeId = '', $needTxt = true) {
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
                if ($needTxt) {
                    $resultNew[] = '全部';
                }
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
                        ->field('store_id')
                        ->find();
                return $result ? $result['store_id'] : '';
                break;

            case 2:
                $data['store_id'] = $shop;
                $result = $model->where($data)
                        ->field('store_name')
                        ->find();
                return $result ? $result['store_name'] : '无';
                break;

            default:
                return '无';
        }
    }


}