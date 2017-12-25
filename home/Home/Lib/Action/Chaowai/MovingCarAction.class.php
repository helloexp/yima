<?php

class MovingCarAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Store/'; // 设置附件上传目录
    }

    public function index() {
        import('ORG.Util.Page');
        $companydata['attribute'] = 0;
        $count = M()->table('tfb_movingcar_carinfo a')
            ->join('tfb_movingcar_company b ON a.company_id = b.id')
            ->field(
            'a.id,a.plate_number,a.mobile,a.attribute,a.driver_name,b.company_name,a.add_time')
            ->order('a.id desc')
            ->where($companydata)
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $carinfo = M()->table("tfb_movingcar_carinfo a")->join(
            'tfb_movingcar_company b ON a.company_id = b.id')
            ->field(
            'a.id,a.plate_number,a.mobile,a.attribute,a.driver_name,b.company_name,a.add_time')
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->where($companydata)
            ->select();
        foreach ($carinfo as $key => $value) {
            $time = $carinfo[$key]['add_time'];
            $time = strtotime($time);
            $carinfo[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        $this->assign('page', $pageShow);
        $this->assign('carinfo', $carinfo);
        $this->display();
    }

    public function indexsq() {
        import('ORG.Util.Page');
        $count = M('tfb_movingcar_carinfo')->where("attribute = '1'")
            ->order('id desc')
            ->count();
        $Page = new Page($count, 10);
        $pageShowsq = $Page->show();
        $carinfosq = M('tfb_movingcar_carinfo')->where("attribute = '1'")
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($carinfosq as $key => $value) {
            $time = $carinfosq[$key]['add_time'];
            $time = strtotime($time);
            $carinfosq[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        $this->assign('pagesq', $pageShowsq);
        $this->assign('carinfosq', $carinfosq);
        $this->display();
    }
    // 企业车辆
    public function editcom() {
        $id = I('get.id');
        $re = M('tfb_movingcar_carinfo')->where(" id = '$id' ")->find();
        $company_id = $re['company_id'];
        $company = M('tfb_movingcar_company')->where("id = '$company_id'")->find();
        $re['company_name'] = $company['company_name'];
        $this->assign('carinfo', $re);
        $this->display();
    }
    // 社会车辆
    public function editcomsq() {
        $id = I('get.id');
        $re = M('tfb_movingcar_carinfo')->where(" id = '$id' ")->find();
        $this->assign('carinfo', $re);
        $this->display();
    }

    public function doeditcomsq() {
        $id = I('post.id');
        $driver_name = I('post.driver_name');
        $plate_number = I('post.plate_number');
        $mobile = I('post.mobile');
        $data = array(
            'driver_name' => $driver_name, 
            'plate_number' => $plate_number, 
            'mobile' => $mobile);
        $plate = M('tfb_movingcar_carinfo')->where(
            "plate_number = '$plate_number'")->find();
        if ($plate) {
            if ($plate['id'] != $id) {
                $mess = array(
                    'status' => 0, 
                    'info' => '车牌号已存在！');
                echo json_encode($mess);
                exit();
            }
        }
        if (is_numeric($mobile) == false) {
            $mess = array(
                'status' => 0, 
                'info' => '无效手机号码！');
            exit(json_encode($mess));
        }
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->save($data);
        if ($re === false) {
            $mess = array(
                'status' => 0, 
                'info' => '修改失败！');
            exit(json_encode($mess));
        } else {
            $mess = array(
                'status' => 1, 
                'info' => '修改成功！');
            exit(json_encode($mess));
        }
    }

    public function addcompany() {
        $this->display();
    }

    public function delcomsq() {
        $id = I('get.id');
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->delete();
        $this->redirect('Chaowai/MovingCar/index');
    }

    public function edit() {
        $id = I('get.id');
        $re = M('tfb_movingcar_company')->where("id = '$id'")->find();
        $this->assign('carinfo', $re);
        $this->display();
    }

    public function doedit() {
        $id = I('post.id');
        $company_name = I('post.company_name');
        $company_number = I('post.company_number');
        $corporation = I('post.corporation');
        $corporation_number = I('post.corporation_number');
        $contact = I('post.contact');
        $mobile = I('post.mobile');
        $people_count = I('post.people_count');
        $note = I('post.note');
        $data = array(
            'company_name' => $company_name, 
            'company_number' => $company_number, 
            'corporation' => $corporation, 
            'corporation_number' => $corporation_number, 
            'contact' => $contact, 
            'mobile' => $mobile, 
            'people_count' => $people_count, 
            'note' => $note);
        $com_re = M('tfb_movingcar_company')->where(
            "company_name = '$company_name'")->getField('id');
        
        if ($com_re) {
            if ($com_re != $id) {
                $mess = array(
                    'status' => 0, 
                    'info' => '企业已存在！');
                exit(json_encode($mess));
            }
        }
        
        if (is_numeric($mobile) == false) {
            $mess = array(
                'status' => 0, 
                'info' => '无效手机号码！');
            exit(json_encode($mess));
        }
        $re = M('tfb_movingcar_company')->where("id = '$id'")->save($data);
        if ($re === false) {
            $mess = array(
                'status' => 0, 
                'info' => '修改失败！');
            exit(json_encode($mess));
        } else {
            $mess = array(
                'status' => 1, 
                'info' => '修改成功！');
            exit(json_encode($mess));
        }
    }

    public function doeditcom() {
        $id = I('post.id');
        $plate_number = I('post.plate_number');
        $driver_name = I('post.driver_name');
        $mobile = I('post.mobile');
        $company_name = I('post.company_name');
        $old_name = I('post.old_name');
        $carddata['plate_number'] = $plate_number;
        $carddata['status'] = array(
            'neq', 
            2);
        if (is_numeric($mobile) == false) {
            $mess = array(
                'status' => 0, 
                'info' => '无效手机号码！');
            exit(json_encode($mess));
        }
        $cardre = M('tfb_movingcar_card')->where($carddata)->select();
        if ($cardre) {
            $mess = array(
                'status' => 0, 
                'info' => '该车辆已经在社会车辆审核中或者已经审核！');
            exit(json_encode($mess));
        }
        $name_re = M('tfb_movingcar_carinfo')->where("mobile = $mobile")->getField(
            'driver_name');
        if ($name_re) {
            if ($name_re != $driver_name) {
                $mess = array(
                    'status' => 0, 
                    'info' => '该手机号对应的司机姓名错误！');
                exit(json_encode($mess));
            }
        }
        $plate = M('tfb_movingcar_carinfo')->where(
            "plate_number = '$plate_number'")->find();
        if ($plate) {
            if ($plate['id'] != $id) {
                $mess = array(
                    'status' => 0, 
                    'info' => '车牌号已存在！');
                echo json_encode($mess);
                exit();
            }
        }
        $data = array(
            'plate_number' => $plate_number, 
            'driver_name' => $driver_name, 
            'mobile' => $mobile);
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->save($data);
        $old_company = M('tfb_movingcar_company')->where(
            "company_name = '$old_name'")->find();
        $old_id = $old_company['id'];
        $old_mobile = M('tfb_movingcar_carinfo')->where(
            "company_id = '$old_id'")->count('distinct(mobile)');
        $old_car = M('tfb_movingcar_carinfo')->where("company_id = '$old_id'")->count(
            'plate_number');
        $old_data = array(
            'car_count' => $old_car, 
            'driver_count' => $old_mobile);
        $count = M('tfb_movingcar_company')->where("company_name = '$old_name'")->save(
            $old_data);
        $mobile_count = M('tfb_movingcar_carinfo')->where(
            "company_id = '$company_id'")->count('distinct(mobile)');
        $car_count = M('tfb_movingcar_carinfo')->where(
            "company_id = '$company_id'")->count('plate_number');
        $data = array(
            'car_count' => $car_count, 
            'driver_count' => $mobile_count);
        $count = M('tfb_movingcar_company')->where(
            "company_name = '$company_name'")->save($data);
        $mess = array(
            'status' => 1, 
            'info' => '修改成功！');
        echo json_encode($mess);
        exit();
    }

    public function seachCar() {
        import('ORG.Util.Page');
        $company_name = I('get.company_name');
        $mobile = I('get.mobile');
        $plate_number = I('get.plate_number');
        $intval = array(
            'company_name' => $company_name, 
            'mobile' => $mobile, 
            'plate_number' => $plate_number);
        if ($company_name != '') {
            $companydata['tfb_movingcar_company.company_name'] = array(
                'like', 
                '%' . $company_name . '%');
        }
        if ($mobile != '') {
            $companydata['tfb_movingcar_carinfo.mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($plate_number != '') {
            $companydata['tfb_movingcar_carinfo.plate_number'] = array(
                'like', 
                '%' . $plate_number . '%');
        }
        $companydata['attribute'] = 0;
        $companyRe = M('tfb_movingcar_company')->select();
        $count = M()->table('tfb_movingcar_carinfo a')
            ->join('tfb_movingcar_company b ON a.company_id = b.id')
            ->field(
            'a.id,a.plate_number,a.mobile,a.attribute,a.driver_name,b.company_name,a.add_time')
            ->order('a.id desc')
            ->where($companydata)
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $carinfo = M()->table('tfb_movingcar_carinfo a')
            ->join('tfb_movingcar_company b ON a.company_id = b.id')
            ->field(
            'a.id,a.plate_number,a.mobile,a.attribute,a.driver_name,b.company_name,a.add_time')
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->where($companydata)
            ->select();
        foreach ($carinfo as $key => $value) {
            $time = $carinfo[$key]['add_time'];
            $time = strtotime($time);
            $carinfo[$key]['add_time'] = date('Y-m-d H:s:i', $time);
        }
        $this->assign('intval', $intval);
        $this->assign('page', $pageShow);
        $this->assign('carinfo', $carinfo);
        $this->display('index');
    }

    public function searchsq() {
        $driver_name = I("get.driver_name");
        $plate_number = I("get.plate_number");
        $mobile = I("get.mobile");
        import('ORG.Util.Page');
        $company = M('tfb_movingcar_company')->select();
        if ($driver_name != "") {
            $map['driver_name'] = array(
                'like', 
                '%' . $driver_name . '%', 
                'AND');
        }
        if ($plate_number != "") {
            $map['plate_number'] = array(
                'like', 
                '%' . $plate_number . '%', 
                'AND');
        }
        if ($mobile != "") {
            $map['mobile'] = array(
                'like', 
                '%' . $mobile . '%', 
                'AND');
        }
        $map['attribute'] = 1;
        $count = M('tfb_movingcar_carinfo')->where($map)
            ->order('id desc')
            ->count();
        $Page = new Page($count, 10);
        $pageShowsq = $Page->show();
        $carinfosq = M('tfb_movingcar_carinfo')->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        echo M()->getLastSql();
        foreach ($carinfosq as $key => $value) {
            foreach ($company as $key1 => $value1) {
                if ($carinfosq[$key]['company_id'] == $company[$key1]['id']) {
                    $carinfosq[$key]['company_id'] = $company[$key1]['company_name'];
                }
            }
            $time = $carinfosq[$key]['add_time'];
            $time = strtotime($time);
            $carinfosq[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        $this->assign('plate_numbersq', $plate_number);
        $this->assign('mobilesq', $mobile);
        $this->assign('driver_namesq', $driver_name);
        $this->assign('pagesq', $pageShowsq);
        $this->assign('carinfosq', $carinfosq);
        $this->display('indexsq');
    }
    
    // 单条车辆信息添加
    public function doaddcompany() {
        $plate_number = I('post.plate_number');
        $mobile = I('post.mobile');
        $attribute = 0;
        $driver_name = I('post.driver_name');
        $company = I('post.company_name');
        $add_time = Date('YmdHis');
        $carddata['plate_number'] = $plate_number;
        $carddata['status'] = array(
            'neq', 
            2);
        $cardre = M('tfb_movingcar_card')->where($carddata)->select();
        if ($cardre) {
            $mess = array(
                'status' => 0, 
                'info' => '该车辆已经在社会车辆审核中或者已经审核！');
            exit(json_encode($mess));
        }
        if (is_numeric($mobile) == false) {
            $mess = array(
                'status' => 0, 
                'info' => '无效手机号码！');
            exit(json_encode($mess));
        }
        $name_re = M('tfb_movingcar_carinfo')->where("mobile = $mobile")->getField(
            'driver_name');
        if ($name_re) {
            if ($name_re != $driver_name) {
                $mess = array(
                    'status' => 0, 
                    'info' => '该手机号对应的司机姓名错误！');
                exit(json_encode($mess));
            }
        }
        $id = M('tfb_movingcar_company')->where("company_name = '$company'")->getField(
            'id');
        if (! $id) {
            $mess = array(
                'status' => 0, 
                'info' => '该企业信息尚未登记！');
            exit(json_encode($mess));
        }
        $recom = M('tfb_movingcar_carinfo')->where(
            "plate_number = '$plate_number'")->find();
        if ($recom) {
            $mess = array(
                'status' => 0, 
                'info' => '该车牌号已存在！');
            exit(json_encode($mess));
        }
        $remobile = M('tfb_movingcar_carinfo')->where("mobile ='$mobile'")->count();
        $data = array(
            'plate_number' => $plate_number, 
            'mobile' => $mobile, 
            'attribute' => $attribute, 
            'driver_name' => $driver_name, 
            'company_id' => $id, 
            'add_time' => $add_time);
        
        $re = M('tfb_movingcar_carinfo')->add($data);
        $ewmdata['plate_number'] = $plate_number;
        $ewmdata['id'] = $re;
        $img = $this->ewm($ewmdata);
        if ($img == false) {
            $flag = M('tfb_movingcar_carinfo')->where("id = $re")->delete();
            $mess = array(
                'status' => 0, 
                'info' => '二维码生成失败！');
            exit(json_encode($mess));
        }
        if ($re) {
            // 车辆数、司机数+1
            $carre = M('tfb_movingcar_company')->where(
                " company_name = '$company' ")->setInc('car_count');
            $remobile = M('tfb_movingcar_carinfo')->where("mobile ='$mobile'")->count();
            if ($remobile <= 1) {
                $driverre = M('tfb_movingcar_company')->where(
                    " company_name = '$company' ")->setInc('driver_count');
            }
            $mess = array(
                'status' => 1, 
                'info' => '成功添加！');
            exit(json_encode($mess));
        } else {
            $mess = array(
                'status' => 0, 
                'info' => '添加失败！');
            exit(json_encode($mess));
        }
    }
    
    // 批量生成二维码
    public function img($ewmdata) {
        foreach ($ewmdata as $key => $value) {
            $path = APP_PATH . 'Upload/MovingCar/ewm/';
            $name = $ewmdata[$key]['plate_number'];
            $id = $ewmdata[$key]['id'];
            $key = $id + 100;
            $keys = md5($key);
            $file = substr($name, - 1);
            $path = APP_PATH . 'Upload/MovingCar/ewm/' . $file . '/';
            if (! file_exists($path)) {
                mkdir($path, 0777);
            }
            $url = "http://test.wangcaio2o.com" . U('Chaowai/Wap/scan', 
                array(
                    'key' => $keys));
            // 纠错级别：L、M、Q、H
            $level = 'L';
            // 点的大小：1到10,用于手机端4就可以了
            $size = 4;
            $data = make_short_url($url);
            $ecc = 'H';
            $size = 10;
            $filename = $path . $name . '.png';
            $keyarray = array(
                'key' => $keys, 
                'qrcode' => $filename);
            QRcode::png($data, $filename, $ecc, $size, 2, false);
            $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->save(
                $keyarray);
        }
    }
    // 单个生成二维码
    public function ewm($ewmdata) {
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $path = APP_PATH . 'Upload/MovingCar/ewm/';
        $name = $ewmdata['plate_number'];
        $id = $ewmdata['id'];
        $key = $id + 100;
        $keys = md5($key);
        $file = substr($name, - 1);
        $path = APP_PATH . 'Upload/MovingCar/ewm/' . $file . '/';
        $url = "http://test.wangcaio2o.com" . U('Chaowai/Wap/scan', 
            array(
                'key' => $keys));
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 4;
        $data = make_short_url($url);
        $ecc = 'H';
        $size = 10;
        $filename = $path . $name . '.png';
        $keyarray = array(
            'key' => $keys, 
            'qrcode' => $filename);
        QRcode::png($data, $filename, $ecc, $size, 0, false);
        M('tfb_movingcar_carinfo')->where("id = " . $id)->save($keyarray);
    }
    
    // 处理AJAX
    public function search() {
        $val = I('post.search_word');
        $data['company_name'] = array(
            'like', 
            '%' . $val . '%');
        $re = M('tfb_movingcar_company')->where($data)
            ->limit('10')
            ->select();
        echo "<ul>";
        foreach ($re as $key => $value) {
            echo "<li style='margin:3px;font-size:14px;background:#FFF;'>";
            echo $value['company_name'];
            echo "</li>";
        }
        echo "</ul>";
    }
    
    // 批量添加车辆信息
    public function batchApply() {
        C('STORE_TPL_APPLY', APP_PATH . 'Upload/MovingCar/');
        if ($this->isPost()) {
            import('ORG.Net.UploadFile') or die('导入上传包失败');
            import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
            $key = 1;
            $upload = new UploadFile();
            $upload->maxSize = 3145728;
            $upload->savePath = C('STORE_TPL_APPLY');
            $info = $upload->uploadOne($_FILES['staff']);
            $flieWay = $upload->savePath . $info['savepath'] .
                 $info[0]['savename'];
            $row = 0;
            $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
            if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
                @unlink($flieWay);
                $this->setError('文件类型不符合');
            }
            if (($handle = fopen($flieWay, "rw")) !== FALSE) {
                $model = M('tfb_movingcar_carinfo');
                $model->startTrans();
                while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    ++ $row;
                    $arr = utf8Array($arr);
                    if ($row == 1) {
                        $fileField = array(
                            '企业车牌号', 
                            '司机', 
                            '司机手机号', 
                            '所属企业');
                        $arrDiff = array_diff_assoc($arr, $fileField);
                        if (count($arr) != count($fileField) || ! empty(
                            $arrDiff)) {
                            fclose($handle);
                            @unlink($flieWay);
                            $this->setError(
                                '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                        }
                        continue;
                    }
                    $array = array();
                    $array['plate_number'] = trim($arr[0]);
                    $array['driver_name'] = trim($arr[1]);
                    $array['mobile'] = trim($arr[2]);
                    $array['attribute'] = 0;
                    $array['add_time'] = date('YmdHis');
                    $company = trim($arr[3]);
                    $plate_number = trim($arr[0]);
                    $mobile = trim($arr[2]);
                    $driver_name = $array['driver_name'];
                    // 字段判断
                    $carddata['plate_number'] = $plate_number;
                    $carddata['status'] = array(
                        'neq', 
                        2);
                    $carddata['card_attribute'] = '1';
                    $cardre = M('tfb_movingcar_card')->where($carddata)->select();
                    if ($cardre) {
                        $this->setError(
                            '模板文件中第' . $row . '行有错误：该车辆已经在社会车辆审核中或者已经审核！');
                        break;
                    }
                    $name_re = M('tfb_movingcar_carinfo')->where(
                        "mobile = $mobile")->getField('driver_name');
                    if ($name_re) {
                        if ($name_re != $driver_name) {
                            $this->setError(
                                '模板文件中第' . $row . '行有错误：该手机号对应的司机姓名错误！');
                            break;
                        }
                    }
                    if (mb_strlen($company, 'UTF8') > 25) {
                        $this->setError('模板文件中第' . $row . '行有错误：企业名字不能超过25位！');
                        break;
                    }
                    if (mb_strlen($driver_name, 'UTF8') > 11) {
                        $this->setError('模板文件中第' . $row . '行有错误：司机名字不能超过11位！');
                        break;
                    }
                    if (mb_strlen($mobile, 'UTF8') > 11) {
                        $this->setError('模板文件中第' . $row . '行有错误：司机手机号码不能超过11位！');
                        break;
                    }
                    if (is_numeric($mobile) == false) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：无效手机号码！');
                        break;
                    }
                    if (mb_strlen($plate_number, 'UTF8') > 7) {
                        $this->setError('模板文件中第' . $row . '行有错误：车牌号不能超过7位！');
                        break;
                    }
                    $comre = M('tfb_movingcar_company')->where(
                        "company_name = '$company'")->getField('id');
                    if ($comre) {
                        $array['company_id'] = $comre;
                    } else {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：该企业信息尚未登记！');
                        $key = 0;
                        break;
                    }
                    if (strlen($array['mobile']) != "11") {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：手机号码长度不正确！');
                        $key = 0;
                        break;
                    }
                    $platere = M('tfb_movingcar_carinfo')->where(
                        "plate_number = '$plate_number'")->find();
                    if ($platere) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：车牌号重复！');
                        $key = 0;
                        break;
                    }
                    if (empty($array['plate_number'])) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：企业车牌号不能为空！');
                        $key = 0;
                        break;
                    }
                    $platezz = preg_match(
                        "/^[\x{4e00}-\x{9fa5}]{1}[A-Za-z]{1}[\dA-Za-z]{5}$/u", 
                        $plate_number);
                    if ($platezz == false) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：企业车牌号有错误！');
                        $key = 0;
                        break;
                    }
                    if (empty($array['driver_name'])) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：驾驶人姓名不能为空！');
                        $key = 0;
                        break;
                    }
                    // 录入carinfo
                    $carinfore = $model->add($array);
                    // 车辆数、司机数添加
                    $mobile_count = M('tfb_movingcar_carinfo')->where(
                        "company_id = '$comre'")->count('distinct(mobile)');
                    $car_count = M('tfb_movingcar_carinfo')->where(
                        "company_id = '$comre'")->count('plate_number');
                    $data = array(
                        'car_count' => $car_count, 
                        'driver_count' => $mobile_count);
                    $count = M('tfb_movingcar_company')->where(
                        "company_name = '$company'")->save($data);
                    if (! $carinfore) {
                        $model->rollback();
                        $this->setError('数据录入失败！');
                    }
                    $ewmdata[$row]['plate_number'] = $array['plate_number'];
                    $ewmdata[$row]['id'] = $carinfore;
                }
                if ($key == 1) {
                    $record = $this->img($ewmdata);
                }
                $model->commit();
                $this->setError('导入成功！');
            }
            @fclose($handle);
        }
        $this->display();
    }

    public function setError($str, $batchNo = '') {
        if ($batchNo != '') {
            $data = array(
                'filename' => $batchNo, 
                'status' => 0);
            $sbNumber = M('tfb_movingcar_mess')->where($data)->count();
            if ($sbNumber > 0)
                $str .= ",失败" . $sbNumber .
                     "条，<a href='index.php?g=Chaowai&m=MovingCar&a=getError&batchNo=" .
                     $batchNo . "'>查看详细</a>";
        }
        $this->assign('str', $str);
        $this->display('setError');
        exit();
    }

    public function company() {
        import('ORG.Util.Page');
        $count = M('tfb_movingcar_company')->order('id desc')->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $company_list = M('tfb_movingcar_company')->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('page', $pageShow);
        $this->assign('company_list', $company_list);
        $this->display();
    }

    public function selectCompany() {
        import('ORG.Util.Page');
        $company_name = I('get.company_name');
        $data['company_name'] = array(
            'like', 
            '%' . $company_name . '%');
        $count = M('tfb_movingcar_company')->where($data)
            ->order('id desc')
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $company_list = M('tfb_movingcar_company')->where($data)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('page', $pageShow);
        $this->assign('company_name', $company_name);
        $this->assign('company_list', $company_list);
        $this->display('company');
    }
    
    // 批量企业辆信息
    public function companyApply() {
        C('STORE_TPL_APPLY', APP_PATH . 'Upload/MovingCar/');
        if ($this->isPost()) {
            import('ORG.Net.UploadFile') or die('导入上传包失败');
            $upload = new UploadFile();
            $upload->maxSize = 3145728;
            $upload->savePath = C('STORE_TPL_APPLY');
            $info = $upload->uploadOne($_FILES['staff']);
            $flieWay = $upload->savePath . $info['savepath'] .
                 $info[0]['savename'];
            $row = 0;
            $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
            
            if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
                @unlink($flieWay);
                $this->setError('文件类型不符合');
            }
            if (($handle = fopen($flieWay, "rw")) !== FALSE) {
                $model = M('tfb_movingcar_company');
                $model->startTrans();
                while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    ++ $row;
                    $arr = utf8Array($arr);
                    if ($row == 1) {
                        $fileField = array(
                            '企业编号', 
                            '企业名称', 
                            '企业法人', 
                            '法人码', 
                            '联系人', 
                            '联系方式', 
                            '人数', 
                            '备注信息');
                        $arrDiff = array_diff_assoc($arr, $fileField);
                        if (count($arr) != count($fileField) || ! empty(
                            $arrDiff)) {
                            fclose($handle);
                            @unlink($flieWay);
                            $this->setError(
                                '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                        }
                        continue;
                    }
                    $data = array();
                    $data['company_number'] = trim($arr[0]);
                    $data['company_name'] = trim($arr[1]);
                    $data['corporation'] = trim($arr[2]);
                    $data['corporation_number'] = trim($arr[3]);
                    $data['contact'] = trim($arr[4]);
                    $data['mobile'] = trim($arr[5]);
                    $data['people_count'] = trim($arr[6]);
                    $data['note'] = trim($arr[7]);
                    $company = trim($arr[1]);
                    $company_number = $arr[0];
                    $contact = trim($arr[4]);
                    $corporation = trim($arr[2]);
                    $corporation_number = trim($arr[3]);
                    $note = trim($arr[7]);
                    $people_count = trim($arr[6]);
                    
                    // 字段判断
                    if (mb_strlen($people_count, 'UTF8') > 6) {
                        $this->setError('模板文件中第' . $row . '行有错误：企业人数不能超过6位！');
                        break;
                    }
                    if (mb_strlen($note, 'UTF8') > 50) {
                        $this->setError('模板文件中第' . $row . '行有错误：备注消息不能超过50位！');
                        break;
                    }
                    if (mb_strlen($corporation, 'UTF8') > 6) {
                        $this->setError('模板文件中第' . $row . '行有错误：法人不能超过6位！');
                        break;
                    }
                    if (mb_strlen($company, 'UTF8') > 25) {
                        $num = mb_strlen($company, 'UTF8');
                        $this->setError('模板文件中第' . $row . '行有错误：企业名字不能超过25位！');
                        break;
                    }
                    if (mb_strlen($company_number, 'UTF8') > 12) {
                        $this->setError('模板文件中第' . $row . '行有错误：企业编号不能超过12位！');
                        break;
                    }
                    if (mb_strlen($corporation_number, 'UTF8') > 6) {
                        $this->setError('模板文件中第' . $row . '行有错误：法人码不能超过6位！');
                        break;
                    }
                    if (mb_strlen($contact, 'UTF8') > 11) {
                        $this->setError('模板文件中第' . $row . '行有错误：联系人不能超过11位！');
                        break;
                    }
                    
                    $comre = $model->where("company_name = '$company'")->find();
                    if ($comre) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：企业已存在！');
                        break;
                    }
                    if (empty($data['company_name'])) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：企业名称不能为空！');
                        break;
                    }
                    if (strlen($data['mobile']) != "11") {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：手机号码长度不正确！');
                        break;
                    }
                    if (is_numeric($data['mobile']) == false) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：无效手机号码！');
                        break;
                    }
                    if (! is_numeric($data['people_count'])) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行有错误：企业人数填写不正确！');
                        break;
                    }
                    // 录入company
                    $data['add_time'] = date(YmdHis);
                    $carinfore = M('tfb_movingcar_company')->add($data);
                    if (! $carinfore) {
                        $model->rollback();
                        $this->setError('模板文件中第' . $row . '行入库失败');
                    }
                }
                $model->commit();
            }
            @fclose($handle);
            // $this->setComError('导入门店成功',$filename[0]);
            $this->setError('导入成功！');
        }
        $this->display();
    }
    
    // 下载企业信息
    public function getCompany() {
        $company_name = I('get.company_name');
        if ($company_name != NULL) {
            $data['company_name'] = array(
                'like', 
                '%' . $company_name . '%');
        }
        $list = M('tfb_movingcar_company')->where($data)
            ->order('id desc')
            ->select();
        $filename = 'company.csv';
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "企业编号,企业名称,企业法人,法人码,联系人,联系方式,人数,车辆数,司机数,备注信息\r\n";
        echo $title_ = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $v) {
                echo iconv('utf-8', 'gbk', $v['company_number']) . ",";
                echo iconv('utf-8', 'gbk', $v['company_name']) . ",";
                echo iconv('utf-8', 'gbk', $v['corporation']) . ",";
                echo iconv('utf-8', 'gbk', $v['corporation_number']) . ",";
                echo iconv('utf-8', 'gbk', $v['contact']) . ",";
                echo iconv('utf-8', 'gbk', $v['mobile']) . ",";
                echo iconv('utf-8', 'gbk', $v['people_count']) . ",";
                echo iconv('utf-8', 'gbk', $v['car_count']) . ",";
                echo iconv('utf-8', 'gbk', $v['driver_count']) . ",";
                echo iconv('utf-8', 'gbk', $v['note']) . "\r\n";
            }
        }
    }
    
    // 下载审核信息
    public function getCommunity() {
        $mobile = I('get.mobile');
        $goods_type = I('get.goods_type');
        $status = I('get.status');
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $filename = 'Community.csv';
        
        $stime = strtotime($start_time);
        $stime = date('YmdHis', $stime);
        $etime = strtotime($end_time);
        $etime = date('YmdHis', strtotime("+1 days", $etime));
        if ($mobile != "") {
            $data['mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($goods_type != "") {
            $data['shipping_method'] = $goods_type;
        }
        if ($status != "") {
            $data['status'] = $status;
        }
        if ($start_time != "" && $end_time == "") {
            $data['add_time'] = array(
                'egt', 
                $stime);
        }
        if ($start_time == "" && $end_time != "") {
            $data['add_time'] = array(
                'elt', 
                $etime);
        }
        if ($start_time != "" && $end_time != "") {
            $data['add_time'] = array(
                'between', 
                array(
                    $stime, 
                    $etime));
        }
        $list = M('tfb_movingcar_card')->where($data)
            ->order('id desc')
            ->select();
        foreach ($list as $key => $value) {
            if ($value['card_attribute'] == 0) {
                $list[$key]['card_attribute'] = '企业';
            }
            if ($value['card_attribute'] == 1) {
                $list[$key]['card_attribute'] = '个人';
            }
            if ($value['shipping_method'] == 0) {
                $list[$key]['shipping_method'] = '自取';
            }
            if ($value['shipping_method'] == 1) {
                $list[$key]['shipping_method'] = '物流配送';
            }
            if ($value['status'] == 0) {
                $list[$key]['status'] = '未审核';
            }
            if ($value['status'] == 1) {
                $list[$key]['status'] = '审核通过';
            }
            if ($value['status'] == 2) {
                $list[$key]['status'] = '拒绝';
            }
            if ($value['status'] == 3) {
                $list[$key]['status'] = '制卡中';
            }
            if ($value['status'] == 4) {
                $list[$key]['status'] = '已发送';
            }
            $time = $value['add_time'];
            $time = strtotime($time);
            $list[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "类型,申请人,申请人手机号,申请时间,领卡方式,配送地址,申请状态,审核信息,发送信息\r\n";
        echo $title_ = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $v) {
                echo iconv('utf-8', 'gbk', $v['card_attribute']) . ",";
                echo iconv('utf-8', 'gbk', $v['proposer']) . ",";
                echo iconv('utf-8', 'gbk', $v['mobile']) . ",";
                echo iconv('utf-8', 'gbk', $v['add_time']) . ",";
                echo iconv('utf-8', 'gbk', $v['shipping_method']) . ",";
                echo iconv('utf-8', 'gbk', $v['shipping_address']) . ",";
                echo iconv('utf-8', 'gbk', $v['status']) . ",";
                echo iconv('utf-8', 'gbk', $v['examine_note']) . ",";
                echo iconv('utf-8', 'gbk', $v['send_note']) . "\r\n";
            }
        }
    }

    public function delCarinfo() {
        $id = I('post.id');
        $company_id = M('tfb_movingcar_carinfo')->where("id = '$id'")->getField(
            'company_id');
        $plate_number = M('tfb_movingcar_carinfo')->where("id ='$id'")->getField(
            'plate_number');
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->delete();
        $card_re = M('tfb_movingcar_card')->where(
            "plate_number = '$plate_number'")->delete();
        if ($re) {
            $mobile_count = M('tfb_movingcar_carinfo')->where(
                "company_id = '$company_id'")->count('distinct(mobile)');
            $car_count = M('tfb_movingcar_carinfo')->where(
                "company_id = '$company_id'")->count('plate_number');
            $data = array(
                'car_count' => $car_count, 
                'driver_count' => $mobile_count);
            $count = M('tfb_movingcar_company')->where("id = '$company_id'")->save(
                $data);
            
            $mess = array(
                "stauts" => 1, 
                "info" => '删除成功');
            echo json_encode($mess);
            exit();
        } else {
            $mess = array(
                "stauts" => 0, 
                "info" => '删除失败！');
            echo json_encode($mess);
            exit();
        }
    }

    public function delCompany() {
        $id = I('post.id');
        $re = M('tfb_movingcar_carinfo')->where("company_id = '$id'")->find();
        if ($re) {
            $mess = array(
                "stauts" => 0, 
                "info" => '该企业下存在车辆或者驾驶人员信息，无法删除');
            echo json_encode($mess);
            exit();
        } else {
            $delre = M('tfb_movingcar_company')->where("id = '$id'")->delete();
            if ($delre) {
                $mess = array(
                    "stauts" => 1, 
                    "info" => '删除成功！');
                echo json_encode($mess);
                exit();
            } else {
                $mess = array(
                    "stauts" => 0, 
                    "info" => '删除失败！');
                echo json_encode($mess);
                exit();
            }
        }
    }
    
    // 压缩文件
    public function load() {
        $company_name = I('get.company_name');
        $plate_number = I('get.plate_number');
        $mobile = I('get.mobile');
        if ($company_name != '') {
            $companydata['tfb_movingcar_company.company_name'] = array(
                'like', 
                '%' . $company_name . '%');
        }
        if ($mobile != '') {
            $companydata['tfb_movingcar_carinfo.mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($plate_number != '') {
            $companydata['tfb_movingcar_carinfo.plate_number'] = array(
                'like', 
                '%' . $plate_number . '%');
        }
        $companydata['attribute'] = 0;
        $carinfo = M()->table('tfb_movingcar_carinfo a')
            ->join('tfb_movingcar_company b ON a.company_id = b.id')
            ->field('a.qrcode')
            ->order('a.id desc')
            ->where($companydata)
            ->select();
        $filename = APP_PATH . 'Upload/MovingCar/' . date('YmdHis') . ".zip"; // 最终生成的文件名（含路径）
                                                                              // 生成文件
        $zip = new ZipArchive(); // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
            exit('无法打开文件，或者文件创建失败');
        }
        // $fileNameArr 就是一个存储文件路径的数组 比如 array('/a/1.jpg,/a/2.jpg....');
        foreach ($carinfo as $key => $value) {
            $fileNameArr[] = $value['qrcode'];
        }
        foreach ($fileNameArr as $val) {
            $data = iconv("UTF-8", "GB2312//IGNORE", $val);
            $zip->addFile($val, basename($data)); // 第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
        $zip->close(); // 关闭
                       // 下面是输出下载;
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header(
            'Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($filename)); // 告诉浏览器，文件大小
        @readfile($filename); // 输出文件;
    }

    public function loadsq() {
        $driver_name = I('get.driver_name');
        $plate_number = I('get.plate_number');
        $mobile = I('get.mobile');
        if ($driver_name != '') {
            $companydata['driver_name'] = array(
                'like', 
                '%' . $driver_name . '%');
        }
        if ($mobile != '') {
            $companydata['mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($plate_number != '') {
            $companydata['plate_number'] = array(
                'like', 
                '%' . $plate_number . '%');
        }
        $companydata['attribute'] = 1;
        $carinfo = M('tfb_movingcar_carinfo')->order('id desc')
            ->where($companydata)
            ->select();
        $filename = APP_PATH . 'Upload/MovingCar/' . date('YmdHis') . ".zip"; // 最终生成的文件名（含路径）
                                                                              // 生成文件
        $zip = new ZipArchive(); // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
            exit('无法打开文件，或者文件创建失败');
        }
        // $fileNameArr 就是一个存储文件路径的数组 比如 array('/a/1.jpg,/a/2.jpg....');
        foreach ($carinfo as $key => $value) {
            $fileNameArr[] = $value['qrcode'];
        }
        foreach ($fileNameArr as $val) {
            $data = iconv("UTF-8", "GB2312//IGNORE", $val);
            $zip->addFile($val, basename($data)); // 第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
        $zip->close(); // 关闭
                       // 下面是输出下载;
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header(
            'Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($filename)); // 告诉浏览器，文件大小
        @readfile($filename); // 输出文件;
    }

    public function loadcom() {
        $driver_name = I('get.driver_name');
        $plate_number = I('get.plate_number');
        $mobile = I('get.mobile');
        if ($driver_name != '') {
            $companydata['driver_name'] = array(
                'like', 
                '%' . $driver_name . '%');
        }
        if ($mobile != '') {
            $companydata['mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($plate_number != '') {
            $companydata['plate_number'] = array(
                'like', 
                '%' . $plate_number . '%');
        }
        $companydata['attribute'] = 1;
        $carinfo = M('tfb_movingcar_carinfo')->order('id desc')
            ->where($companydata)
            ->limit(10)
            ->select();
        $filename = APP_PATH . 'Upload/MovingCar/' . date('YmdHis') . ".zip"; // 最终生成的文件名（含路径）
                                                                              // 生成文件
        $zip = new ZipArchive(); // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
            exit('无法打开文件，或者文件创建失败');
        }
        // $fileNameArr 就是一个存储文件路径的数组 比如 array('/a/1.jpg,/a/2.jpg....');
        foreach ($carinfo as $key => $value) {
            $fileNameArr[] = $value['qrcode'];
        }
        foreach ($fileNameArr as $val) {
            $data = iconv("UTF-8", "GB2312//IGNORE", $val);
            $zip->addFile($val, basename($data)); // 第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
        $zip->close(); // 关闭
                       // 下面是输出下载;
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header(
            'Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($filename)); // 告诉浏览器，文件大小
        @readfile($filename); // 输出文件;
    }

    public function showcarinfo() {
        $id = I('get.id');
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->find();
        $company_id = $re['company_id'];
        $re['company_id'] = M('tfb_movingcar_company')->where(
            "id = '$company_id'")->getField('company_name');
        $time = $re['add_time'];
        $time = strtotime($time);
        $re['add_time'] = date('Y-m-d H:i:s', $time);
        $this->assign('carinfo', $re);
        $this->display();
    }

    public function showcarinfosq() {
        $id = I('get.id');
        $re = M('tfb_movingcar_carinfo')->where("id = '$id'")->find();
        $time = $re['add_time'];
        $time = strtotime($time);
        $re['add_time'] = date('Y-m-d H:i:s', $time);
        $this->assign('carinfo', $re);
        $this->display();
    }

    public function showcompany() {
        $company_name = I('get.id');
        $re = M('tfb_movingcar_company')->where(
            "company_name = '$company_name'")->find();
        $this->assign('company', $re);
        $this->display();
    }

    public function loadcode() {
        $code = I('get.qrcode');
        $pieces = explode("/", $code);
        $number = count($pieces) - 1;
        $filename = $pieces[$number];
        $this->fileDown($code, $filename);
    }

    /**
     *
     * @abstract 下载
     * @param string $filepath
     * @param string $filename
     * @param string $type
     */
    public function fileDown($filepath, $filename = '', $type = false) {
        if (! $filename)
            $filename = basename($filepath);
        $extensionAll = explode('.', $filename);
        $ex_count = count($extensionAll) - 1;
        $filetype = strtolower($extensionAll[$ex_count]);
        $filesize = sprintf("%u", filesize($filepath));
        if (ob_get_length() !== false)
            @ob_end_clean();
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: none');
        header('Content-type: ' . $type ? $type : $filetype);
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-length: ' . $filesize);
        readfile($filepath);
        exit();
    }

    public function down1() {
        $id = I('get.id');
        $url_re = M('tfb_movingcar_card')->where("id = '$id'")->find();
        $company_name = $url_re['company_name'];
        
        $path = $url_re['record'];
        $name = $company_name . "_单位备案信息表.doc";
        $this->fileDown($path, $name);
    }

    public function down2() {
        $id = I('get.id');
        $url_re = M('tfb_movingcar_card')->where("id = '$id'")->find();
        $company_name = $url_re['company_name'];
        $url = $url_re['carinfo'];
        $extend = explode(".", $url);
        $va = count($extend) - 1;
        $path = $url_re['carinfo'];
        $name = $company_name . "_车辆相关信息表." . $extend[$va];
        $this->fileDown($path, $name);
    }

    public function down3() {
        $id = I('get.id');
        $url_re = M('tfb_movingcar_card')->where("id = '$id'")->find();
        $company_name = $url_re['company_name'];
        $url = $url_re['security'];
        $extend = explode(".", $url);
        $va = count($extend) - 1;
        $path = $url_re['security'];
        $name = $company_name . "_安全协议书." . $extend[$va];
        $this->fileDown($path, $name);
    }

    public function mocar() {
        import('ORG.Util.Page');
        $count = M()->table('tfb_movingcar_log a')
            ->join('tfb_movingcar_customers b ON a.cusyomer_id = b.id')
            ->join('tfb_movingcar_carinfo c ON a.carinfo_id = c.id')
            ->join('tfb_movingcar_reason d ON a.reason_id = d.id')
            ->field(
            'c.plate_number,c.mobile as dmobile,b.mobile,a.add_time,c.plate_number,d.desc,a.notice_status,c.driver_name')
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $company_list = M()->table('tfb_movingcar_log a')
            ->join('tfb_movingcar_customers b ON a.cusyomer_id = b.id')
            ->join('tfb_movingcar_carinfo c ON a.carinfo_id = c.id')
            ->join('tfb_movingcar_reason d ON a.reason_id = d.id')
            ->field(
            'c.plate_number,c.mobile as dmobile,b.mobile,a.add_time,c.plate_number,d.desc,a.notice_status,c.driver_name')
            ->order('a.add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($company_list as $key => $value) {
            $mobile = $value['mobile'];
            $driver_name = M('tfb_movingcar_carinfo')->where(
                "mobile = '$mobile'")->getField('driver_name');
            $company_list[$key]['name'] = $driver_name;
            $time = $value['add_time'];
            $time = strtotime($time);
            $company_list[$key]['add_time'] = date('Y-m-d H:i:s', $time);
            if ($value['notice_status'] == 0) {
                $company_list[$key]['notice_status'] = '未发送';
            }
            if ($value['notice_status'] == 1) {
                $company_list[$key]['notice_status'] = '发送成功';
            }
            if ($value['notice_status'] == 2) {
                $company_list[$key]['notice_status'] = '发送失败';
            }
        }
        $this->assign('page', $pageShow);
        $this->assign('list', $company_list);
        $this->display();
    }

    public function seachMocar() {
        import('ORG.Util.Page');
        $mobile = I('get.mobile');
        $dmobile = I('get.dmobile');
        $descType = I('get.descType');
        if ($mobile != "") {
            $data['tfb_movingcar_customers.mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($dmobile != "") {
            $data['tfb_movingcar_carinfo.mobile'] = array(
                'like', 
                '%' . $dmobile . '%');
        }
        if ($descType != "") {
            $data['tfb_movingcar_reason.id'] = $descType;
        }
        $count = M()->table('tfb_movingcar_log a')
            ->join('tfb_movingcar_customers b ON a.cusyomer_id = b.id')
            ->join('tfb_movingcar_carinfo c ON a.carinfo_id = c.id')
            ->join('tfb_movingcar_reason d ON a.reason_id = d.id')
            ->field(
            'c.plate_number,c.mobile as dmobile,b.mobile,a.add_time,c.plate_number,d.desc,a.notice_status,c.driver_name')
            ->where($data)
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $company_list = M()->table('tfb_movingcar_log a')
            ->join('tfb_movingcar_customers b ON a.cusyomer_id = b.id')
            ->join('tfb_movingcar_carinfo c ON a.carinfo_id = c.id')
            ->join('tfb_movingcar_reason d ON a.reason_id = d.id')
            ->field(
            'c.plate_number,c.mobile as dmobile,b.mobile,a.add_time,c.plate_number,d.desc,a.notice_status,c.driver_name')
            ->order('a.add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->where($data)
            ->select();
        foreach ($company_list as $key => $value) {
            $mobile_data = $value['mobile'];
            $driver_name = M('tfb_movingcar_carinfo')->where(
                "mobile = '$mobile_data'")->getField('driver_name');
            $company_list[$key]['name'] = $driver_name;
            $time = $value['add_time'];
            $time = strtotime($time);
            $company_list[$key]['add_time'] = date('Y-m-d H:i:s', $time);
            if ($value['notice_status'] == 0) {
                $company_list[$key]['notice_status'] = '未发送';
            }
            if ($value['notice_status'] == 1) {
                $company_list[$key]['notice_status'] = '发送成功';
            }
            if ($value['notice_status'] == 2) {
                $company_list[$key]['notice_status'] = '发送失败';
            }
        }
        $this->assign('mobile', $mobile);
        $this->assign('dmobile', $dmobile);
        $this->assign('desc', $descType);
        $this->assign('page', $pageShow);
        $this->assign('list', $company_list);
        $this->display('mocar');
    }

    public function community() {
        import('ORG.Util.Page');
        $count = M('tfb_movingcar_card')->order('id desc')->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $list = M('tfb_movingcar_card')->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as $key => $value) {
            if ($value['card_attribute'] == 0) {
                $list[$key]['card_attribute'] = '企业';
            }
            if ($value['card_attribute'] == 1) {
                $list[$key]['card_attribute'] = '个人';
            }
            if ($value['shipping_method'] == 0) {
                $list[$key]['shipping_method'] = '自取';
            }
            if ($value['shipping_method'] == 1) {
                $list[$key]['shipping_method'] = '物流配送';
            }
            if ($value['status'] == 0) {
                $list[$key]['status'] = '未审核';
            }
            if ($value['status'] == 1) {
                $list[$key]['status'] = '审核通过';
            }
            if ($value['status'] == 2) {
                $list[$key]['status'] = '拒绝';
            }
            if ($value['status'] == 3) {
                $list[$key]['status'] = '制卡中';
            }
            if ($value['status'] == 4) {
                $list[$key]['status'] = '已发送';
            }
            $time = $value['add_time'];
            $time = strtotime($time);
            $list[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        $this->assign('page', $pageShow);
        $this->assign('list', $list);
        $this->assign('end_time', date('Ymd'));
        $this->display();
    }

    public function serCommunity() {
        import('ORG.Util.Page');
        $mobile = I('get.mobile');
        $goods_type = I('get.goods_type');
        $status = I('get.status');
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $stime = strtotime($start_time);
        $stime = date('YmdHis', $stime);
        $etime = strtotime($end_time);
        $etime = date('YmdHis', strtotime("+1 days", $etime));
        if ($mobile != "") {
            $data['mobile'] = array(
                'like', 
                '%' . $mobile . '%');
        }
        if ($goods_type != "") {
            $data['shipping_method'] = $goods_type;
        }
        if ($status != "") {
            $data['status'] = $status;
        }
        if ($start_time != "" && $end_time == "") {
            $data['add_time'] = array(
                'egt', 
                $stime);
        }
        if ($start_time == "" && $end_time != "") {
            $data['add_time'] = array(
                'elt', 
                $etime);
        }
        if ($start_time != "" && $end_time != "") {
            $data['add_time'] = array(
                'between', 
                array(
                    $stime, 
                    $etime));
        }
        $count = M('tfb_movingcar_card')->where($data)
            ->order('id desc')
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();
        $list = M('tfb_movingcar_card')->where($data)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as $key => $value) {
            if ($value['card_attribute'] == 0) {
                $list[$key]['card_attribute'] = '企业';
            }
            if ($value['card_attribute'] == 1) {
                $list[$key]['card_attribute'] = '个人';
            }
            if ($value['shipping_method'] == 0) {
                $list[$key]['shipping_method'] = '自取';
            }
            if ($value['shipping_method'] == 1) {
                $list[$key]['shipping_method'] = '物流配送';
            }
            if ($value['status'] == 0) {
                $list[$key]['status'] = '未审核';
            }
            if ($value['status'] == 1) {
                $list[$key]['status'] = '审核通过';
            }
            if ($value['status'] == 2) {
                $list[$key]['status'] = '拒绝';
            }
            if ($value['status'] == 3) {
                $list[$key]['status'] = '制卡中';
            }
            if ($value['status'] == 4) {
                $list[$key]['status'] = '已发送';
            }
            $time = $value['add_time'];
            $time = strtotime($time);
            $list[$key]['add_time'] = date('Y-m-d H:i:s', $time);
        }
        $this->assign('mobile', $mobile);
        $this->assign('goods_type', $goods_type);
        $this->assign('status', $status);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('list', $list);
        $this->assign('page', $pageShow);
        $this->display('community');
    }

    public function pcCommunity() {
        $this->display();
    }

    public function dopcCommunity() {
        $company_name = I('post.company_name');
        $proposer = I('post.proposer');
        $mobile = I('post.mobile');
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->maxSize = 2090000;
        $upload->savePath = APP_PATH . 'Upload/MovingCar/';
        $info = $upload->uploadOne($_FILES['staff']);
        if ($info) {
            $flieWay = $upload->savePath . $info['savepath'] .
                 $info[0]['savename'];
            $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
            if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'docx' &&
                 pathinfo($flieWay, PATHINFO_EXTENSION) != 'doc') {
                @unlink($flieWay);
                $mess = array(
                    'status' => 0, 
                    'info' => '单位备案信息表类型不符合');
                exit(json_encode($mess));
            }
        } else {
            $mess = array(
                'status' => 0, 
                'info' => '请上传单位备案信息表！');
            exit(json_encode($mess));
        }
        $upload->maxSize = 10090000;
        $info1 = $upload->uploadOne($_FILES['staff1']);
        if ($info1) {
            $flieWay1 = $upload->savePath . $info1['savepath'] .
                 $info1[0]['savename'];
            $filename = explode('.', pathinfo($flieWay1, PATHINFO_BASENAME));
            if (pathinfo($flieWay1, PATHINFO_EXTENSION) != 'docx' &&
                 pathinfo($flieWay1, PATHINFO_EXTENSION) != 'doc' &&
                 pathinfo($flieWay1, PATHINFO_EXTENSION) != 'zip' &&
                 pathinfo($flieWay1, PATHINFO_EXTENSION) != 'rar') {
                @unlink($flieWay1);
                $mess = array(
                    'status' => 0, 
                    'info' => '车辆信息表类型不符合');
                exit(json_encode($mess));
            }
        } else {
            $mess = array(
                'status' => 0, 
                'info' => '请上传车辆信息表！');
            exit(json_encode($mess));
        }
        $info2 = $upload->uploadOne($_FILES['staff2']);
        $upload->maxSize = 2090000;
        if ($info2) {
            $flieWay2 = $upload->savePath . $info2['savepath'] .
                 $info2[0]['savename'];
            $row = 0;
            $filename = explode('.', pathinfo($flieWay2, PATHINFO_BASENAME));
            if (pathinfo($flieWay2, PATHINFO_EXTENSION) != 'docx' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'doc' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'bmp' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'png' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'jpeg' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'jpg' &&
                 pathinfo($flieWay2, PATHINFO_EXTENSION) != 'gif') {
                @unlink($flieWay2);
                $mess = array(
                    'status' => 0, 
                    'info' => '安全协议书类型不符合');
                exit(json_encode($mess));
            }
        } else {
            $mess = array(
                'status' => 0, 
                'info' => '请上传安全协议书！');
            exit(json_encode($mess));
        }
        $cityInfo = M('tcity_code')->where(
            array(
                'city_level' => 3, 
                'province_code' => I('province_code'), 
                'city_code' => I('city_code'), 
                'town_code' => I('town_code')))->find();
        $province = $cityInfo['province'];
        $city = $cityInfo['city'];
        $town = $cityInfo['town'];
        $address = I('post.address');
        $shipping_address = $cityInfo['province'] . ' ' . $cityInfo['city'] . ' ' .
             $town = $cityInfo['town'] . ' ' . $address;
        $shipping_method = I('post.shipping');
        $data = array(
            'card_attribute' => 0, 
            'proposer' => $proposer, 
            'company_name' => $company_name, 
            'mobile' => $mobile, 
            'add_time' => date(YmdHis), 
            'shipping_method' => $shipping_method, 
            'shipping_address' => $shipping_address, 
            'status' => 0, 
            'record' => $flieWay, 
            'carinfo' => $flieWay1, 
            'security' => $flieWay2);
        $com = M('tfb_movingcar_company')->where(
            "company_name = '$company_name'")->select();
        if (! $com) {
            $mess = array(
                'status' => 0, 
                'info' => '该企业信息尚未登记');
            exit(json_encode($mess));
        }
        if (is_numeric($mobile) == false) {
            $mess = array(
                'status' => 0, 
                'info' => '无效手机号码！');
            exit(json_encode($mess));
        }
        $re = M('tfb_movingcar_card')->add($data);
        if ($re) {
            $mess = array(
                'status' => 1, 
                'info' => '申请成功！');
            exit(json_encode($mess));
        } else {
            $mess = array(
                'status' => 0, 
                'info' => '保存失败');
            exit(json_encode($mess));
        }
    }

    public function editsoci() {
        $this->display();
    }
    
    // 审核通过
    public function doexamine() {
        $id = I('post.id');
        $driver_name = I('post.driver_name');
        $mobile = I('post.mobile');
        $card_attribute = I('post.attribute');
        // 个人审核
        if ($card_attribute == '个人') {
            $card_attribute = 1;
            $status = 1;
            $exnote = I('post.exnote');
            $data = array(
                'status' => $status, 
                'examine_note' => $exnote);
            $examine_re = M('tfb_movingcar_card')->where("id = '$id'")->save(
                $data);
            if ($examine_re) {
                $plate_number = M('tfb_movingcar_card')->where("id = '$id'")->getField(
                    'plate_number');
                $carinfo_data = array(
                    'driver_name' => $driver_name, 
                    'mobile' => $mobile, 
                    'attribute' => $card_attribute, 
                    'plate_number' => $plate_number, 
                    'add_time' => date('YmdHis'));
                $id = M('tfb_movingcar_carinfo')->add($carinfo_data);
                $ewm_data = array(
                    'id' => $id, 
                    'plate_number' => $plate_number);
                $this->ewm($ewm_data);
                $mess = array(
                    'status' => 1, 
                    'info' => '审核成功！');
                exit(json_encode($mess));
            } else {
                $mess = array(
                    'status' => 0, 
                    'info' => '审核失败！');
                exit(json_encode($mess));
            }
        } else {
            $status = 1;
            $exnote = I('post.exnote');
            $data = array(
                'status' => $status, 
                'examine_note' => $exnote);
            $examine_re = M('tfb_movingcar_card')->where("id = '$id'")->save(
                $data);
        }
    }
    
    // 拒绝审核
    public function donoexamine() {
        $id = I('post.id');
        $status = I('post.status');
        $exnote = I('post.exnote');
        if ($status == '未审核') {
            $status = 2;
        }
        $data = array(
            'status' => $status, 
            'examine_note' => $exnote);
        $re = M('tfb_movingcar_card')->where(" id = '$id' ")->save($data);
    }
    // 发送
    public function editsend() {
        $id = I('get.id');
        $this->assign("id", $id);
        $this->display();
    }
    
    // 已发送
    public function doeditsend() {
        $id = I('post.id');
        $send_note = I('post.senote');
        $status = 4;
        $data = array(
            'status' => 4, 
            'send_note' => $send_note);
        $re = M('tfb_movingcar_card')->where("id = '$id'")->save($data);
    }
    // 审核
    public function editexamine() {
        $id = I('get.id');
        $re = M('tfb_movingcar_card')->where("id = '$id'")->find();
        if ($re['card_attribute'] == 0) {
            $re['card_attribute'] = '企业';
        }
        if ($re['card_attribute'] == 1) {
            $re['card_attribute'] = '个人';
        }
        if ($re['shipping_method'] == 0) {
            $re['shipping_method'] = '自取';
        }
        if ($re['shipping_method'] == 1) {
            $re['shipping_method'] = '物流配送';
        }
        if ($re['status'] == 0) {
            $re['status'] = '未审核';
        }
        if ($re['status'] == 1) {
            $re['status'] = '审核通过';
        }
        if ($re['status'] == 2) {
            $re['status'] = '拒绝';
        }
        if ($re['status'] == 3) {
            $re['status'] = '制卡中';
        }
        if ($re['status'] == 4) {
            $re['status'] = '已发送';
        }
        $time = $re['add_time'];
        $time = strtotime($time);
        $re['add_time'] = date('Y-m-d H:i:s', $time);
        $this->assign('re', $re);
        $this->display();
    }

    public function showexamine() {
        $id = I('get.id');
        $re = M('tfb_movingcar_card')->where("id = '$id'")->find();
        if ($re['card_attribute'] == 0) {
            $re['card_attribute'] = '企业';
        }
        if ($re['card_attribute'] == 1) {
            $re['card_attribute'] = '个人';
        }
        if ($re['shipping_method'] == 0) {
            $re['shipping_method'] = '自取';
        }
        if ($re['shipping_method'] == 1) {
            $re['shipping_method'] = '物流配送';
        }
        if ($re['status'] == 0) {
            $re['status'] = '未审核';
        }
        if ($re['status'] == 1) {
            $re['status'] = '审核通过';
        }
        if ($re['status'] == 2) {
            $re['status'] = '拒绝';
        }
        if ($re['status'] == 3) {
            $re['status'] = '制卡中';
        }
        if ($re['status'] == 4) {
            $re['status'] = '已发送';
        }
        $time = $re['add_time'];
        $time = strtotime($time);
        $re['add_time'] = date('Y-m-d H:i:s', $time);
        $this->assign('re', $re);
        $this->display();
    }
}