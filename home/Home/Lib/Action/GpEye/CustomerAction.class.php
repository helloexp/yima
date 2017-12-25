<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class CustomerAction extends GpBaseAction
{
    public $arr = array('2.0', '1.5', '1.2', '1.0', '0.8', '0.6', '0.5', '0.4', '0.3', '0.25', '0.2', '0.15', '0.12', '0.1');
    public $arr1 = array('50', '100', '150', '200', '250', '300', '350', '400', '450', '500');
    public function _initialize()
    {
        parent::_initialize();
    }
    //客户档案列表
    public function customerList()
    {
        $data = I('post.', '');
        $data = array_filter($data);
        $name = trim(I('name', ''));
        $merchants = trim(I('merchant_id', ''));
        $visual_level = I('visual_level', '');
        $type = I('type', '');
        $treatment_process = I('treatment_process', '');
        $technician = I('technician_id', '');
        $source=I('source','');
        $household_pro=I('household_pro','');
        $household_city=I('household_city','');
        $map = array('tc.city_level'=>2);
        if($source!=''){
            $map['c.source'] =$source;
            $this->assign('source', $source);
        }
        if($household_pro!='' && $household_city!=''){
            $map['c.household_reg'] =$household_pro.$household_city;
            $this->assign('household_pro', $household_pro);
            $this->assign('household_city', $household_city);
        }
        if($household_pro!='' && $household_city==''){
            $paths=M('tcity_code')->where(array('city_level'=>2,'province_code'=>$household_pro))->getField('GROUP_CONCAT(path)');
            $map['c.household_reg'] =array('in',$paths);
            $this->assign('household_pro', $household_pro);
            $this->assign('household_city', $household_city);
        }
        if ($name != '') {
            $map['c.name'] = array('like' ,'%'.$name.'%');
            $this->assign('name', $name);
        }
        if ($technician != '') {
            $technicians = D('GpTechnician')->getTechnicianIds(array('name' => array('like', '%'.$technician.'%'), 'status' => 0));
            $map['c.technician_id'] = array('in', $technicians);
            $this->assign('technician', $technician);
        }
        if ($visual_level != '') {
            $map['c.visual_level'] = $visual_level;
            $this->assign('visual_level', $visual_level);
        }
        if ($type != '') {
            $map['c.type'] = $type;
            $this->assign('type', $type);
        }
        if ($treatment_process != '') {
            $map['c.treatment_process'] = $treatment_process;
            $this->assign('treatment_process', $treatment_process);
        }
        if ($merchants != '') {
            $map['c.merchant_id'] = $merchants;
            $this->assign('merchants', $merchants);
        }
        $merchant = D('GpMerchant')->merchantList(array('status' => 0));
        $info = D('GpCustomer')->customerInfoList($map, $data);
        $VISUAL_arr = array('0' => '正常','1' => '轻度','2' => '中度','3' => '重度','4' =>'重度一级','5' =>'重度二级');
        $TREATMENT_arr = array('体验期', '恢复期', '保养期');
        $TYPE_arr = array('体验客户', '付费客户');
        $source_arr=C('sources');
        $this->assign('source_arr', $source_arr);
        $this->assign('VISUAL_arr', $VISUAL_arr);
        $this->assign('TREATMENT_arr', $TREATMENT_arr);
        $this->assign('TYPE_arr', $TYPE_arr);
        $this->assign('data', $data);
        $this->assign('merchant', $merchant);
        $this->assign('list', $info['list']);
        $this->assign('page', $info['show']);
        $this->display('Customer/customerList');
    }
    //下载客户档案列表
    public function down_load()
    {
        $name = trim(I('name', ''));
        $merchants = trim(I('merchant_id', ''));
        $visual_level = I('visual_level', '');
        $type = I('type', '');
        $treatment_process = I('treatment_process', '');
        $technician = I('technician_id', '');
        $source=I('source','');
        $household_pro=I('household_pro','');
        $household_city=I('household_city','');
        $map = array('tc.city_level'=>2);
        if($source!=''){
            $map['c.source'] =$source;
        }
        if($household_pro!='' && $household_city!=''){
            $map['c.household_reg'] =$household_pro.$household_city;
        }
        if($household_pro!='' && $household_city==''){
            $paths=M('tcity_code')->where(array('city_level'=>2,'province_code'=>$household_pro))->getField('GROUP_CONCAT(path)');
            $map['c.household_reg'] =array('in',$paths);
        }
        if ($name != '') {
            $map['c.name'] = array('like' ,'%'.$name.'%');
        }
        if ($technician != '') {
            $technicians = D('GpTechnician')->getTechnicianIds(array('name' => array('like', '%'.$technician.'%'), 'status' => 0));
            $map['c.technician_id'] = array('in', $technicians);
        }
        if ($visual_level != '') {
            $map['c.visual_level'] = $visual_level;
        }
        if ($type != '') {
            $map['c.type'] = $type;
        }
        if ($treatment_process != '') {
            $map['c.treatment_process'] = $treatment_process;
        }
        if ($merchants != '') {
            $map['c.merchant_id'] = $merchants;
        }
        $info = D('GpCustomer')->downloadcustomerInfoList($map);
        $result = $info['list'];
        if (!$result) {
            $this->error('没有合适的下载数据',array('返回'=>'javascript:history.go(-1)'));
        }
        $source_arr=C('sources');
        $fileName = '客户档案列表 '.'.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header('Content-type: text/plain');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        $cj_title = "客户ID,姓名,性别,年龄,所属门店,负责技师,视力级别,眼睛状况,佩戴眼眼镜状况,监护人姓名1,联系方式,监护人姓名2,联系方式,客户类型,恢复进程,登录手机号,客户来源,户籍\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        $VISUAL_arr = array('0' => '正常','1' => '轻度','2' => '中度','3' => '重度','4' =>'重度一级','5' =>'重度二级');
        $TREATMENT_arr = array('体验期', '恢复期', '保养期');
        $TYPE_arr = array('体验客户', '付费客户');
        $glass_arr = array('是', '否');
        $eye_type = array('远视', '斜视', '近视', '散光', '弱视');
        $sex_arr = array('男', '女');
        $s_types = array();
        $s_type = '';
        foreach ($result as $v) {
            $json = json_decode($v['customer_info'], true);
            $json_1 = json_decode($v['supervisor_info_1'], true);
            $json_2 = json_decode($v['supervisor_info_2'], true);
            $json_3=json_decode($v['other_source'], true);
            $s_type = $this->eyeOperator($json['s_type']);
            $flag='';
            if(in_array($v['province'],array('北京','天津','上海','重庆'))){
                $flag='';
            }elseif(in_array($v['province'],array('新疆','内蒙古','西藏','广西','宁夏'))){
                $flag=$v['province'].'自治区';
            }else{
                $flag=$v['province'].'省';
            }
            $line = "{$v['id']},{$v['name']},{$sex_arr[$v['sex']]},{$v['age']},{$v['store_short_name']},{$v['t_name']},{$VISUAL_arr[$v['visual_level']]},{$s_type},{$glass_arr[$json['class_que']]},{$json_1['name_1']},{$json_1['phone_1']},{$json_2['name_2']},{$json_2['phone_2']},{$TYPE_arr[$v['type']]},{$TREATMENT_arr[$v['treatment_process']]},{$v['mobile']},{$source_arr[$v['source']]}、{$json_3['so']},{$flag}{$v['city']}\r\n";
            echo iconv('utf-8', 'gbk', $line);
        }
        exit;
    }
    //客户变更操作
    public function changeCustomerInfo()
    {
        if ($this->isPost()) {
            $id = I('id', '');
            $type = I('type', '');
            $treatment_process = I('treatment_process', '');
            $item_no = I('item_no', '');
            $item_d = I('item_d', '');
            $info = D('GpCustomer')->getCustomerInfoById(array('id' => $id));
            $json = json_decode($info['customer_info'], true);
            $item_noo = $json['item_no'];
            $data = array();
            $data1 = array();
            $data2 = array();
            $data2 = array(
                'customer_id' => $id,
                'user_id' => $this->userId,
                'add_time' => date('YmdHis'),
                );
            if ($info['type'] == $type && $type == 0) {
                if ($type != '') {
                    $data['type'] = $type;
                    $data2['customer_type'] = $type;
                }
                if ($treatment_process != '') {
                    $data['treatment_process'] = $treatment_process;
                }
            } elseif ($info['type'] == $type && $type == 1) {
                if ($type != '') {
                    $data['type'] = $type;
                    $data2['customer_type'] = $type;
                }
                if ($treatment_process != '') {
                    $data['treatment_process'] = $treatment_process;
                }
                if ($item_d != '') {
                    $data1['item_no'] = $json['item_no'] + $item_d;
                    $data['customer_info'] = json_encode(array_merge($json, $data1));
                    $data2['before_amount'] = $item_noo;
                    $data2['change_amount'] = $item_d;
                    $data2['after_amount'] = $item_noo + $item_d;
                }
            } else {
                if ($type != '') {
                    $data['type'] = $type;
                    $data2['customer_type'] = $type;
                }
                if ($treatment_process != '') {
                    $data['treatment_process'] = $treatment_process;
                }
                if ($item_no != '') {
                    $data1['item_no'] = $item_no;
                    $data['customer_info'] = json_encode(array_merge($json, $data1));
                    $data2['before_amount'] = $item_noo;
                    $data2['change_amount'] = $item_no;
                    $data2['after_amount'] = $item_noo + $item_no;
                }
            }
            M()->startTrans();
            M('tfb_gp_customer')->where("id=$id")->lock(true)->find();
            $result = M('tfb_gp_customer')->where(array('id' => $id))->save($data);
            $trace_result = M('tfb_gp_customer_trace')->add($data2);
            $url = U('GpEye/Customer/customerList');
            if ($result && $trace_result) {
                M()->commit();
                $this->ajaxReturn(array('status' => 1, 'info' => '变更成功', 'url' => $url));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('status' => 0, 'info' => '变更失败', 'url' => $url));
            }
        } else {
            $id = I('id', '');
            if ($id == '' || $id == 0) {
                $this->error('参数错误');
            }
            $info = D('GpCustomer')->getCustomerInfoById(array('id' => $id));
            $json = json_decode($info['customer_info'], true);
            $item_no = $json['item_no'];
            $gp_arr = array('体验期', '恢复期', '保养期');
            $treatment_process = $info['treatment_process'];
            $this->assign('treatment_process', $treatment_process);
            $this->assign('item_no', $item_no);
            $this->assign('info', $info);
            $this->assign('gp_arr', $gp_arr);
            $this->assign('id', $id);
            $this->display('Customer/changeCustomerInfo');
        }
    }
    //客户档案查看
    public function customerInfoShow()
    {
        $id = I('id', '');
        if ($id == '' || $id == 0) {
            $this->error('参数错误');
        }
        if ($this->isPost()) {
            $info_1 = array();
            $info_2 = array();
            $customer_info = array();
            $other_source=array();
            $other_source=array(
                'so'=>I('source1','','mysql_escape_string'),
                'fuqin1'=>I('fuqin1','','mysql_escape_string'),
                'muqin1'=>I('muqin1','','mysql_escape_string'),
                'fuqin2'=>I('fuqin2','','mysql_escape_string'),
                'muqin2'=>I('muqin2','','mysql_escape_string'),
                'qita2'=>I('qita2','','mysql_escape_string'), 
                'du'=>I('look','','mysql_escape_string')
                );
            $info_1 = array(
                'name_1' => I('name_1', '','mysql_escape_string'),
                'phone_1' => I('phone_1', '','mysql_escape_string'),
                'eml_1' => I('eml_1', '','mysql_escape_string'),
                'rela_1' => I('rela_1', '','mysql_escape_string'),
                );
            $info_2 = array(
                'name_2' => I('name_2', '','mysql_escape_string'),
                'phone_2' => I('phone_2', '','mysql_escape_string'),
                'eml_2' => I('eml_2', '','mysql_escape_string'),
                'rela_2' => I('rela_2', '','mysql_escape_string'),
                );
            $customer_info = array(
                'eye_type' => I('eye_type', '','mysql_escape_string'),
                's_type' => I('s_type', '','mysql_escape_string'),
                'class_type' => I('class_type', '','mysql_escape_string'),
                'class_que' => I('class_que', '','mysql_escape_string'),
                'class_time' => I('class_time', '','mysql_escape_string'),
                'eye_degree1' => I('eye_degree1', '','mysql_escape_string'),
                'eye_degree2' => I('eye_degree2', '','mysql_escape_string'),
                'eye_degree3' => I('eye_degree3', '','mysql_escape_string'),
                'eye_degree4' => I('eye_degree4', '','mysql_escape_string'),
                'sns_type' => I('sns_type', '','mysql_escape_string'),
                'qita' => I('qita', '','mysql_escape_string'),
                'item_no'=>I('item_no','','mysql_escape_string'),
                'length1'=>I('length1','','mysql_escape_string'),
                'length2'=>I('length2','','mysql_escape_string')
                );
            $info_1 = json_encode($info_1);
            $info_2 = json_encode($info_2);
            $customer_info = json_encode($customer_info);
            $other_source=json_encode($other_source);
            $data = array();
            $data = array(
                'merchant_id' => I('merchant_id', '','mysql_escape_string'),
                'technician_id' => I('technician_id', '','mysql_escape_string'),
                'name' => I('s_name', '','mysql_escape_string'),
                'type' => I('type', 0),
                'visual_level' => I('visual_level', 0),
                'treatment_process' => I('treatment_process', 0),
                'age' => I('age', '','mysql_escape_string'),
                'sex' => I('sex', '','mysql_escape_string'),
                'mobile' => I('mobile', '','mysql_escape_string'),
                'school' => I('school', '','mysql_escape_string'),
                'classes' => I('classes', '','mysql_escape_string'),
                'address' => I('address', '','mysql_escape_string'),
                'customer_info' => $customer_info,
                'supervisor_info_1' => $info_1,
                'supervisor_info_2' => $info_2,
                'add_time' => date('YmdHis'),
                'come_time' => dateFormat(I('come_time'),'YmdHis'),
                'source'=>I('source',''), 
                'household_reg'=>I('household_pro','','mysql_escape_string').I('household_city','','mysql_escape_string'), 
                'other_source'=>$other_source,
                );
            $result = M('tfb_gp_customer')->where(array('id' => $id))->save($data);
            $url = U('GpEye/Customer/customerInfoShow', array('id' => $id));
            if ($result) {
                $this->ajaxReturn(array('status' => 1, 'info' => '修改成功', 'url' => $url));
            } else {
                $this->ajaxReturn(array('status' => 0, 'info' => '修改失败', 'url' => $url));
            }
        }
        $treatment_info = D('GpTreatmentRecord')->myTreatmentRecordAll(array('tr.customer_id' => $id));
        $count = D('GpTreatmentRecord')->myTreatmentCount(array('tr.customer_id' => $id));
        $info = D('GpCustomer')->getCustomerInfoById(array('id' => $id));
        $json = json_decode($info['customer_info'], true);
        $json_source=json_decode($info['other_source'],true);
        $qita=$json['qita'];
        $path=$info['household_reg'];
        $pro=substr($path,0,2);
        $city=substr($path,2);
        $province=M('tcity_code')->field('province_code,province,city_code,city')->where(array('city_level'=>2,'province_code'=>$pro,'city_code'=>$city))->find();
        $this->assign('province',$province);
        $s_type = json_encode($json['s_type']);
        $sns_type = json_encode($json['sns_type']);
        $length1=$json['length1'];
        $length2=$json['length2'];
        $merchant = D('GpMerchant')->merchantList(array('status' => 0));
        $technician = D('GpTechnician')->technicianInfo(array('a.merchant_id' => $info['merchant_id'], 'a.status' => 0));
        $relation_arr = array('父亲', '母亲', '其他亲戚', '非亲戚');
        $provinces=M('tcity_code')->field('province,province_code')->where(array('city_level'=>1))->select();
        $this->assign('json_source',$json_source);
        $this->assign('dushu',C('du'));
        $this->assign('provinces',$provinces);
        $this->assign('sources',C('sources'));
        $this->assign('length1', $length1);
        $this->assign('length2', $length2);
        $this->assign('qita', $qita);
        $this->assign('relation_arr', $relation_arr);
        $this->assign('count', $count);
        $this->assign('technician', $technician['list']);
        $this->assign('merchant', $merchant);
        $this->assign('treatment_info', $treatment_info);
        $this->assign('arr', $this->arr);
        $this->assign('s_type', $s_type);
        $this->assign('sns_type', $sns_type);
        $this->assign('arr1', $this->arr1);
        $this->assign('info', $info);
        $this->assign('id', $id);
        $this->display('Customer/customerInfoShow');
    }
    //预约客户列表
    public function appointmentList()
    {
        $merchant = D('GpMerchant')->merchantList(array('status' => 0));
        $appInfos = D('GpAppointment')->appointInfoReturn(array('ta.status' => 0), '');
        $this->assign('appInfo', $appInfos['list']);
        $this->assign('page', $appInfos['show']);
        $this->assign('merchant', $merchant);
        $this->display('Customer/appointmentList');
    }
    //添加客户
    public function customerAdd()
    {
        if ($this->isPost()) {
            $id = I('id', '','mysql_escape_string');
            $info_1 = array();
            $info_2 = array();
            $customer_info = array();
            $other_source=array();
            $other_source=array(
                'so'=>I('source1','','mysql_escape_string'),
                'fuqin1'=>I('fuqin1','','mysql_escape_string'),
                'muqin1'=>I('muqin1','','mysql_escape_string'),
                'fuqin2'=>I('fuqin2','','mysql_escape_string'),
                'muqin2'=>I('muqin2','','mysql_escape_string'),
                'qita2'=>I('qita2','','mysql_escape_string'), 
                'du'=>I('look','','mysql_escape_string')
                );
            $info_1 = array(
                'name_1' => I('name_1', '','mysql_escape_string'),
                'phone_1' => I('phone_1', '','mysql_escape_string'),
                'eml_1' => I('eml_1', '','mysql_escape_string'),
                'rela_1' => I('rela_1', '','mysql_escape_string'),
                );
            $info_2 = array(
                'name_2' => I('name_2', '','mysql_escape_string'),
                'phone_2' => I('phone_2', '','mysql_escape_string'),
                'eml_2' => I('eml_2', '','mysql_escape_string'),
                'rela_2' => I('rela_2', '','mysql_escape_string'),
                );
            $customer_info = array(
                'eye_type' => I('eye_type', '','mysql_escape_string'),
                's_type' => I('s_type', '','mysql_escape_string'),
                'class_type' => I('class_type', '','mysql_escape_string'),
                'class_que' => I('class_que', '','mysql_escape_string'),
                'class_time' => I('class_time', '','mysql_escape_string'),
                'eye_degree1' => I('eye_degree1', '','mysql_escape_string'),
                'eye_degree2' => I('eye_degree2', '','mysql_escape_string'),
                'eye_degree3' => I('eye_degree3', '','mysql_escape_string'),
                'eye_degree4' => I('eye_degree4', '','mysql_escape_string'),
                'sns_type' => I('sns_type', '','mysql_escape_string'),
                'qita' => I('qita', '','mysql_escape_string'),
                'item_no' => I('item_no', '','mysql_escape_string'),
                'length1'=>I('length1','','mysql_escape_string'),
                'length2'=>I('length2','','mysql_escape_string')
                );
            $info_1 = json_encode($info_1);
            $info_2 = json_encode($info_2);
            $customer_info = json_encode($customer_info);
            $other_source=json_encode($other_source);
            $data = array();
            $type = I('type', '','mysql_escape_string');
            if ($type == '') {
                $type = 0;
            }
            $data = array(
                'merchant_id' => I('merchant_id', '','mysql_escape_string'),
                'technician_id' => I('technician_id', ''),'mysql_escape_string',
                'name' => I('s_name', '','mysql_escape_string'),
                'type' => $type,
                'visual_level' => I('visual_level', '','mysql_escape_string'),
                'treatment_process' => I('treatment_process', '','mysql_escape_string'),
                'come_time' => dateFormat(I('come_time'),'YmdHis'),
                'age' => I('age', '','mysql_escape_string'),
                'sex' => I('sex', '','mysql_escape_string'),
                'mobile' => I('mobile', '','mysql_escape_string'),
                'school' => I('school', '','mysql_escape_string'),
                'classes' => I('classes', '','mysql_escape_string'),
                'address' => I('address', '','mysql_escape_string'),
                'customer_info' => $customer_info,
                'supervisor_info_1' => $info_1,
                'supervisor_info_2' => $info_2,
                'add_time' => date('YmdHis'),
                'source'=>I('source','','mysql_escape_string'), 
                'household_reg'=>I('household_pro','','mysql_escape_string').I('household_city','','mysql_escape_string'), 
                'other_source'=>$other_source,
                );
            $result = M('tfb_gp_customer')->add($data);
            $url = U('GpEye/Customer/customerList');
            M()->startTrans();
            if ($result) {
                if ($id) {
                    $re = M('tfb_gp_appointment')->where(array('id' => $id))->save(array('status' => 1));
                    if (!$re) {
                        M()->rollback();
                        $this->ajaxReturn(array('status' => 0, 'info' => '客户转化失败', 'url' => $url));
                    }
                }
                M()->commit();
                $this->ajaxReturn(array('status' => 1, 'info' => '客户添加成功', 'url' => $url));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('status' => 0, 'info' => '客户添加失败', 'url' => $url));
            }
        } else {
            $id = I('id', '');//预约转化成客户
            if ($id) {
                $info = D('GpAppointment')->getAppInfoById(array('status' => 0, 'id' => $id));
                if (!$info) {
                    $this->error('该客户不存在或者异常');
                }
                $technicians = D('GpTechnician')->technicianInfo(array('a.merchant_id' => $info['merchant_id'], 'a.status' => 0));
                $this->assign('technicians', $technicians['list']);
                $this->assign('info', $info);
            }
            $this->assign('sources',C('sources'));
            $this->assign('dushu',C('du'));
            $merchant = D('GpMerchant')->merchantList(array('status' => 0));
            $time = date('Y-m-d H:i:s');
            $this->assign('id', $id);
            $this->assign('arr', $this->arr);
            $this->assign('arr1', $this->arr1);
            $this->assign('time', $time);
            $this->assign('merchant', $merchant);
            $this->display('Customer/customerAdd');
        }
    }
    //通过门店查找技师
    public function selectTechnician()
    {
        $id = I('id', '','mysql_escape_string');
        if ($id == '' || $id == 0) {
            $this->error('参数错误');
        }
        $technician = D('GpTechnician')->technicianInfo(array('a.merchant_id' => $id, 'a.status' => 0));
        if (!$technician['list']) {
            $this->error('该门店没有技师或者异常');
        }
        $this->ajaxReturn(array('data' => $technician['list'], 'status' => 1));
    }
    //恢复记录下载
    public function treamentDownload()
    {
        $id = I('id', '','mysql_escape_string');
        if ($id == '' || $id == 0) {
            $this->error('参数错误');
        }
        $map['tr.customer_id'] = $id;
        $map['l.city_level']=2;
        $result = D('GpTreatmentRecord')->TreatmentRecord($map);
        $source_arr=C('sources');
        if (!count($result)) {
            $this->error('没有合适的下载数据',array('返回'=>'javascript:history.go(-1)'));
        }
        $fileName = '客户恢复记录 '.'.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header('Content-type: text/plain');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        $cj_title = "客户id,客户姓名,所属门店,恢复次序,恢复时间,恢复技师,操作人,恢复说明,裸眼左眼视力,裸眼右眼视力,裸眼双眼视力,矫正左眼视力,矫正右眼视力,矫正双眼视力,客户来源,户籍\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        $VISUAL_arr = array('0' => '正常','1' => '轻度','2' => '中度','3' => '重度','4' =>'重度一级','5' =>'重度二级');
        $TREATMENT_arr = array('体验期', '恢复期', '保养期');
        $TYPE_arr = array('体验客户', '付费客户');
        $i = 1;
        foreach ($result as $v) {
            $json = json_decode($v['vision_info'], true);
            $json_3=json_decode($v['other_source'], true);
            $com_time = dateFormat($v['treatment_time'],'Y-m-d H:i:s');
            if ($v['treatment_time'] == '') {
                $com_time = '';
            }
            $flag='';
            if(in_array($v['province'],array('北京','天津','上海','重庆'))){
                $flag='';
            }elseif(in_array($v['province'],array('新疆','内蒙古','西藏','广西','宁夏'))){
                $flag=$v['province'].'自治区';
            }else{
                $flag=$v['province'].'省';
            }
            $line = "{$v['customer_id']},{$v['name']},{$v['store_short_name']},{$i},{$com_time},{$v['t_name']},{$v['true_name']},{$v['memo']},{$json['eye_degree'][0]},{$json['eye_degree'][1]},{$json['eye_degree'][2]},{$json['eye_degree'][3]},{$json['eye_degree'][4]},{$json['eye_degree'][5]},{$source_arr[$v['source']]}、{$json_3['so']},{$flag}{$v['city']}\r\n";
            echo iconv('utf-8', 'gbk', $line);
            ++$i;
        }
        exit;
    }
    //获取预约客户信息
    public function treatmentCustomerInfo()
    {
        $id = I('id', '','mysql_escape_string');
        if ($id == '' || $id == 0) {
            $this->error('参数错误');
        }
        $info = D('GpAppointment')->getAppInfoById(array('id' => $id, 'status' => 0));
        if (!$info) {
            $this->error('该预约客户信息异常或者不存在');
        }
        $url = U('GpEye/Customer/customerAdd', array('id' => $id));
        $this->ajaxReturn(array('data' => $info, 'status' => 1, 'url' => $url));
    }
    //眼睛状况调整
    public function eyeOperator($arr)
    {
        $eye_type = array('远视', '斜视', '近视', '散光', '弱视');
        $s_types = array();
        $s_type = '';
        foreach ($arr as $vv => $val) {
            $s_types[] = $eye_type[$val];
        }
        foreach ($s_types as $k => $kk) {
            if($k==0){
                $s_type .=$kk;
            }else{
                $s_type .='、'.$kk;
            }    
        }
        return $s_type;
    }
    //预约客户页面查询
    public function appointmentReturn()
    {
        $data = I('post.', '','mysql_escape_string');
        $name = I('s_name', '','mysql_escape_string');
        $mobile = I('mobile', '','mysql_escape_string');
        $merchant_id = I('merchant_id', '','mysql_escape_string');
        $map = array();
        $map['ta.status'] = 0;
        if ($name != '') {
            $map['ta.name'] = array('like', '%'.$name.'%');
        }
        if ($mobile != '') {
            $map['ta.mobile'] = $mobile;
        }
        if ($merchant_id != '') {
            $map['ta.merchant_id'] = $merchant_id;
        }
        $info = D('GpAppointment')->appointInfoReturn($map, $data);
        $infos = array('list' => $info['list'], 'page' => $info['show']);
        $result = json_encode($infos);
        echo $result;
    }
}
