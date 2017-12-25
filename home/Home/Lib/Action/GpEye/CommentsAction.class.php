<?php
class CommentsAction extends GpBaseAction
{
//    public $_authAccessMap = '*';
    public function index()
    { $post = I('request.');
        if ($post['start_time'])
        {
            $starttime=$post['start_time'];
            $map['a.add_time'][] = ['egt', $starttime . '000000'];
        }
        if ($post['end_time'])
        {
            $endtime=$post['end_time'];
            $map['a.add_time'][] = ['elt', $endtime . '235959'];
        }
        $service_attitude = I('service_attitude');
        if($service_attitude==='0')
            $map['a.service_attitude']=0;
        else if($service_attitude)
        $map['a.service_attitude']=$service_attitude;
        $service_result = I('service_result');
        if($service_result==='0')
            $map['a.service_result']=0;
        else if($service_result)
            $map['a.service_result']=$service_result;
        $store_id = I('storeid');
        if ($store_id)
            $map['a.merchant_id']=$store_id;
        $technicianId=I('technicianid');
        if($technicianId)
            $map['a.technician_id']=$technicianId;
        $technicianName=I('technicianname');
        if($technicianName)
            $map['d.name'] = ['like', "%{$technicianName}%"];
        $mobile=I('mobile');
        if($mobile)
            $map['a.mobile']=$mobile;


        $result=D('GpCustomerFeedback')->getCommentsInfo($map,$post);
        $serviceAttitude=['优','良','差'];
        $serviceResult=['优','良','差'];
        $storeList=D('GpTechnician')->getStoreList();
        $this->assign('commentslist',isset($result['list']) ? $result['list'] : []);
        $this->assign('page',isset($result['show']) ? $result['show'] : '');
        $this->assign('serviceattitude',$serviceAttitude);
        $this->assign('serviceresult',$serviceResult);
        $this->assign('storelist',$storeList);

        $this->display('Comments/index');
    }
    public function downloadCommentsList()
    {
        $post = I('request.');
        if ($post['start_time'])
        {
            $starttime=$post['start_time'];
            $map['a.add_time'][] = ['egt', $starttime . '000000'];
        }
        if ($post['end_time'])
        {
            $endtime=$post['end_time'];
            $map['a.add_time'][] = ['elt', $endtime . '235959'];
        }
        $service_attitude = I('service_attitude');
        if($service_attitude)
            $map['a.service_attitude']=$service_attitude;
        $service_result = I('service_result');
        if($service_result)
            $map['a.service_result']=$service_result;
        $store_id = I('store_id');
        if ($store_id)
            $map['a.merchant_id']=$store_id;
        $technicianId=I('technicianId');
        if($technicianId)
            $map['a.technician_id']=$technicianId;
        $technicianName=I('technicianName');
        if($technicianName)
            $map['d.name'] = ['like', "%{$technicianName}%"];
        $mobile=I('mobile');
        if($mobile)
            $map['a.mobile']=$mobile;

        $list=D('GpCustomerFeedback')->downloadCommentsInfo($map);




        if (!$list) {
            $this->error('没有数据');
        }
        foreach($list as $k=>$v)
            foreach($v as $key=>$value)
            {if($key=='service_attitude')
            { if($value==0)
                $list[$k][$key]='优';
            else if($value==1)
                $list[$k][$key]='良';
            else if($value==2)
                $list[$k][$key]='差';

            }
            else if($key=="service_result")
            {
                if($value==0)
                    $list[$k][$key]='优';
                else if($value==1)
                    $list[$k][$key]='良';
                else if($value==2)
                    $list[$k][$key]='差';
            }
            else if($key='memo')
            {
                $list[$k][$key]=str_replace(",","，",$list[$k][$key]);
                $list[$k][$key]= str_replace(array("\r\n", "\r", "\n"), "",$list[$k][$key]);
            }
            }

        $fileName = date('Y-m-d') . '-评价查询.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "评价时间,客户手机号,技师Id,技师姓名,所属门店,服务态度,恢复效果,评价内容,恢复流水号\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach($list as $v)
        {
            $line = dateformat($v['add_time'],'Y-m-d') . ',' . $v['mobile'] . ',' . $v['techid'] . ',' . $v['name'] . ','. $v['store_short_name'] . ',' . $v['service_attitude'] . ',' . $v['service_result'] . ',' . $v['memo'] . ',' . "'". $v['treatment_num'] ."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;}




    }


}