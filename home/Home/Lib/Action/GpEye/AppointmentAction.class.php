<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class AppointmentAction extends GpBaseAction
{
//    public $_authAccessMap = '*';

    public function index()
    {
        {
            $post = I('request.');
            if ($post['name']) {
                $name = $post['name'];
                $map['a.name'] = ['like', "%{$name}%"];
            }
            if ($post['mobile']) {
                $mobile = $post['mobile'];
                $map['a.mobile'] = $mobile;
            }

            if ($post['storename']) {
                $store_id = $post['storename'];
                $map['a.merchant_id'] = $store_id;
            }
            $result = D('GpAppointment')->getAppointmentList($map, $post);
            $storeList=D('GpTechnician')->getStoreList();
            $this->assign('appointmentslist', isset($result['list']) ? $result['list'] : []);
            $this->assign('page', isset($result['show']) ? $result['show'] : '');
            $this->assign('storelist',$storeList);
            $this->display('Appointment/index');
        }
    }

    public function downloadAppointmentList()
    {
        $post = I('request.');
        if ($post['name']) {
            $name = $post['name'];
            $map['a.name'] = ['like', "%{$name}%"];
        }
        if ($post['mobile']) {
            $mobile = $post['mobile'];
            $map['a.mobile'] = $mobile;
        }

        if ($post['storename']) {
            $store_id = $post['storename'];
            $map['a.merchant_id'] = $store_id;
        }

        $list = D('GpAppointment')->downloadAppointmentList($map);
        if (!$list) {
            $this->error('未查询到记录！');
        }
        foreach ($list as $k => $v)
            foreach ($v as $key => $value) {
                if ($key == 'status') {
                    if ($value == 0)
                        $list[$k][$key] = '未转化';
                    else if ($value == 1)
                        $list[$k][$key] = '已转化';

                }
                else if($key=="sex")
                {
                    if($value==0)
                        $list[$k][$key]='男';
                    else if($value==1)
                        $list[$k][$key]='女';
                }

            }
        $fileName = date('Y-m-d') . '-预约表单.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "提交时间,姓名,年龄,性别,预约人手机号,预约门店,状态\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach($list as $v)
        {
            $line = dateformat($v['add_time'],'Y-m-d') . ',' . $v['name'] . ',' . $v['age'] . ',' . $v['sex'] . ','. $v['mobile'] . ',' . $v['store_short_name'] . ',' . $v['status'] ."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;}
    }
}