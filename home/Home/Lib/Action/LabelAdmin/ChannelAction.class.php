<?php

/*
 * 渠道管理 @auther 徐应松 @lastedit tr
 */
class ChannelAction extends BaseAction {


    private $ChannelService;
    //首页
    public function IndexNew()
    {


        $map                  = array(
                'tchannel.node_id' => array(
                        'exp',
                        "in (" . $this->nodeIn() . ")"
                ),
                'tchannel.type'    => CommonConst::CHANNEL_TYPE_MY_CHANNEL,
        );
        $this->ChannelService = D('Channel', 'Service');

        $map_group = $this->ChannelService->sort(I("sortByVisit"), I("sortByActivitynum"), I("clickType"));
        $map       = $this->ChannelService->filterCondition($map, $this->node_id);

        list($list, $page, $show) = $this->ChannelService->index($map, $map_group, $this->node_id);

        $this->assign('map', I('post.'));
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('page', $show);
        $this->display();
    }
    //返回获取活动列表
    public function getActivityList()
    {
        $channel_id = I ('post.channel_id');

        $model = M ('tbatch_channel');
        $list  = $model->alias ('T')->join ('tmarketing_info S ON S.id=T.batch_id')
                ->where (
                        array(
                                'T.channel_id' => $channel_id
                        ))
                ->field (
                        'S.id,S.name,S.start_time,S.end_time,S.status,S.add_time,T.click_count,T.id as batchID')
                ->order ('add_time ASC')
                ->select ();

        foreach($list as $k => $v){
            $list[$k]['start_time'] = date('Y-m-d H:i:s',strtotime($v['start_time']));
            $list[$k]['end_time'] = date('Y-m-d H:i:s',strtotime($v['end_time']));
        }
        echo json_encode ($list);
    }
    //添加/编辑渠道页面
    public function add()
    {
        $id = I ('get.id');
        if ($id && is_numeric ($id)) {
            $this->ChannelService = D ('Channel', 'Service');
            $name                 = $this->ChannelService->getChannelNameById ($id);
            $this->assign ('id', $id);
            $this->assign ('name', $name);
        }

        $this->display ();
    }

    //添加渠道处理/编辑渠道处理
    public function addSubmit()
    {

        $data = I('post.','','trim');
        if (empty($data['name'])) {
            $this->ajaxReturn(
                    array(
                            'status' => 0,
                            'info'   => '渠道参数不完整'
                    ),
                    'JSON'
            );
        }

        $this->ChannelService = D('Channel', 'Service');
        if($_REQUEST['id'] == ''){
            $execute              = $this->ChannelService->addSubmit($this->node_id);
            $info = "渠道添加成功";
        } else {
            $execute              = $this->ChannelService->editSubmit($_REQUEST['id'],$data['name']);
            $info = "渠道编辑成功";
        }

        if($execute){
            node_log('渠道添加|渠道名:' . $data['name']);
            $this->ajaxReturn(
                    array(
                            'status'     => 1,
                            'info'       => $info,
                            'channel_id' => $execute,
                            'name'       => $data['name']
                    ),
                    'JSON'
            );
        } else {
            $this->ajaxReturn(
                    array(
                            'status' => 0,
                            'info'   => '渠道添加失败！'
                    ),
                    'JSON'
            );
        }
    }
    //渠道删除
    public function deleteChannel()
    {
        $id = I('post.id');
        if (is_numeric($id)) {
            $node_id              = $this->node_id;
            $this->ChannelService = D('Channel', 'Service');
            $result               = $this->ChannelService->deleteChannel($id, $node_id);
            if ($result) {
                $this->success('渠道删除成功');
            } else {
                $this->error('渠道删除失败');
            }
        }
    }

    //添加渠道备注
    public function memoAdd()
    {
        $this->ChannelService = D('Channel', 'Service');
        $execute              = $this->ChannelService->memoAdd(I('post.channel_id'));

        switch ($execute) {
            case '1':
                $this->error('备注最多5个！！！');
            case '2':
                $this->error('备注重复');
            case '3':
                $this->success(1);
            case '4':
                $this->error('添加失败');
            case '5':
                $this->error('请输入有效备注');
        }
    }
    //删除魅族
    public function memoDelete()
    {
        $memo = I('post.memo');
        $channel_id = I('post.channel_id');
        $this->ChannelService = D('Channel', 'Service');
        $execute              = $this->ChannelService->memoDelete($memo,$channel_id);
        return $execute;

    }

    //添加渠道
    public function addLabel()
    {
        $this->assign ('channel_id', I ('get.channel_id'));
        $this->display ();
    }

    //二维码处理
    public function addSubmitQrCodes()
    {

        $data = I('post.','','mysql_real_escape_string');
        if (empty($data['name'])) {
            $this->ajaxReturn(
                    array(
                            'status' => 0,
                            'info'   => '渠道参数不完整'
                    ),
                    'JSON'
            );
        }

        $this->ChannelService = D('Channel', 'Service');

            $execute              = $this->ChannelService->addSubmitQrCodes($this->node_id);

        if($execute){
            node_log('二维码添加|二维码名称:' . $data['name']);
            $this->ajaxReturn(
                    array(
                            'status'     => 1,
                            'info'       => '二维码添加成功',
                            'channel_id' => $execute,
                            'name'       => $data['name']
                    ),
                    'JSON'
            );
        } else {
            $this->ajaxReturn(
                    array(
                            'status' => 0,
                            'info'   => '二维码添加失败！'
                    ),
                    'JSON'
            );
        }
    }


    //渠道下的活动删除
    public function deleteActivity()
    {
        $id = trim (I ('post.id'));
        if (!is_numeric ($id)) {
            $this->error ('参数错误');
        }
        $this->ChannelService = D ('Channel', 'Service');
        $result               = $this->ChannelService->deleteActivity ($id);
        if ($result) {
            $this->success ('删除成功');
        } else {
            $this->error ('删除失败');
        }
    }

    //获得图片url
    public function getActivityImage(){

        if(is_numeric(I('post.channel_id'))){

            $channel_id = I('post.channel_id');
            $this->ChannelService = D ('Channel', 'Service');
            $result               = $this->ChannelService->getActivityId ($channel_id);
            var_dump($result);die;
        }else{
            return false;
        }
    }

    //渠道二维码的页面
    public function qrCodes()
    {
        $map = array(

                'tchannel.node_id' => $this->node_id,
                'tchannel.type' => CommonConst::CHANNEL_TYPE_CODE_LABEL_CHANNEL,
                'tchannel.status' => 1
        );
        $this->ChannelService = D('Channel','Service');
        list($list,$show) = $this->ChannelService->qrCodes($map);
        node_log("首页+营销渠道库");
        $arr_ = C('CHANNEL_TYPE_ARR');
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('map', $_REQUEST);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }



    // 线上渠道添加
    public function onlineAdd() {
        $this->display();
    }

    public function onlineAdd2() {
        $this->display();
    }
    // 爱拍渠道添加
    public function aipaiAdd() {
        $result = M('tstore_info')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => '0'))
            ->field(
            'store_id,store_name,address,province_code,city_code,town_code,province,city,town ')
            ->select();
        $this->assign('result', $result);
        $this->display();
    }
    // 互联网渠道    展示
    public function onlineCancel() {
        // 控制左菜单样式
        $htmlid = I('htmlid');
        $type = I('type');
        if ($htmlid == '') {
            $htmlid = 21;
        }
        
        // 数据源
        $model = M('tchannel');
        $map = array(
            'tchannel.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'tchannel.type' => '1');
        
        // 渠道分类，入口筛选，SNS渠道和其他API类型
        $sns_type = I('sns_type');
        if ($sns_type != '' and $sns_type == '11') {
            $map['tchannel.sns_type'] = $sns_type;
        }
        if ($sns_type != '' and $sns_type == '12') {
            $map['tchannel.sns_type'] = array(
                'neq', 
                '11');
        }
        
        // 渠道名筛选，模糊匹配
        $cName = I('c   _name', null, 'mysql_real_escape_string');
        if (isset($cName) && $cName != '') {
            $map['tchannel.name'] = array(
                'like', 
                "%{$cName}%");
        }
        
        // 机构号筛选
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['tchannel.node_id'] = $nodeId;
        }
        
        // 创建时间 ，区间筛选
        $addtime = I('start_time');
        $addtime2 = I('end_time');
        if ($addtime != '') {
            $map['tchannel.add_time'] = array(
                'egt', 
                $addtime);
        }
        if ($addtime2 != '') {
            $map['tchannel.add_time'] = array(
                'elt', 
                $addtime2);
        }
        
        // 是否绑定，是否过期 1已绑定 2已过期 3未绑定
        $channel_status = I('channel_status');
        if ($channel_status != '' and $channel_status == '1')
            $map['s.channel_id'] = array(
                'EXP', 
                'is not null');
        if ($channel_status != '' and $channel_status == '2')
            $map['_string'] = 'S.expires_in<(unix_timestamp(now())-S.update_time)';
        if ($channel_status != '' and $channel_status == '3')
            $map['s.channel_id'] = array(
                'EXP', 
                'is null');
            
            // 渠道类型
        $channel_type = I('channel_type');
        if ($channel_type != '')
            $map['tchannel.sns_type'] = $channel_type;
            
            // 导入分页类
        import('ORG.Util.Page');
        $mapcount = $model->join('tsns_node S ON tchannel.id=S.channel_id')
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 10);
        $show = $Page->show();
        
        // 结果集
        $list = $model->join('tsns_node S ON tchannel.id=S.channel_id')
            ->field(
            'tchannel.id,tchannel.status,tchannel.add_time,tchannel.name,tchannel.node_id,tchannel.click_count,tchannel.sns_type,S.channel_id,tchannel.sns_type,tchannel.status,tchannel.type')
            ->where($map)
            ->order('tchannel.status asc ,tchannel.add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $click_arr = array();
        
        if ($list) {
            $bc = M('tbatch_channel');
            $tmap = array(
                'node_id' => $list[0]['node_id']);
            foreach ($list as $k => $v) {
                $tmap['channel_id'] = $v['id'];
                $click_count = $bc->where($tmap)->sum('click_count');
                $click_arr[$v['id']] = $click_count == null ? 0 : $click_count;
                $result = R('LabelAdmin/Sns/checkBinding', 
                    array(
                        $v['id']));
                $list[$k]['bing_status'] = $result;
            }
        }
        
        $bindStatus = array(
            '1' => '已绑定', 
            '2' => '已过期', 
            '3' => '未绑定');
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('htmlid', $htmlid);
        $this->assign('type', $sns_type);
        $this->assign('click_arr', $click_arr);
        $this->assign('bindStatus', $bindStatus);
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('map', $_REQUEST);
        $this->assign('nodeList', $this->getNodeTree());
        if ($list == '' and $sns_type == '11') {
            $this->display('wwwother');
            die(); // 输出模板
        }
        if ($list == '' and $sns_type == '12') {
            $this->display('wwwsns');
            die(); // 输出模板
        }
        if ($list == '' and $sns_type == '13') {
            $this->display('wwwcom');
            die(); // 输出模板
        }
        $this->display(); // 输出模板
    }

   
    
    // 其他高级渠道--员工推广  展示
    public function staffChannel() {
        // 控制样式
        $htmlid = I('htmlid');
        if ($htmlid == '')
            $htmlid = 32;
        $this->assign('htmlid', $htmlid);
        
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'type' => '5', 
            'sns_type' => '51');
        $row = M('tchannel')->where($map)->select();
        
        if (empty($row)) {
            $this->display('staff');
            die();
        }
        
        $wh = array(
            'tstaff_channel.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $sql = "select department from tstaff_channel where node_id=$this->node_id and department !='' group by department";
        $outcome = M()->query($sql);
        
        // 查询条件
        $petname = I('petname');
        $staff_status = I('staff_status');
        $dep_name = I('dep_name');
        $start_time = I('start_time');
        $end_time = I('end_time');
        if ($petname != '')
            $wh['tstaff_channel.petname'] = array(
                'like', 
                "%{$petname}%");
        if ($staff_status != '')
            $wh['tstaff_channel.estate'] = $staff_status;
        if ($dep_name != '')
            $wh['tstaff_channel.department'] = $dep_name;
        if ($start_time != '')
            $wh['tstaff_channel.add_time'] = array(
                'egt', 
                $start_time);
        if ($end_time != '')
            $wh['tstaff_channel.add_time'] = array(
                'elt', 
                $end_time);
        
        import('ORG.Util.Page') or die('导入分页包失败');
        $mapcount = M('tstaff_channel')->where($wh)->count();
        $Page = new Page($mapcount, 10);
        $list = M('tstaff_channel')->join(
            "tchannel T on tstaff_channel.channel_id=T.id")
            ->field(
            'T.name,T.click_count,T.send_count,tstaff_channel.petname,tstaff_channel.estate,tstaff_channel.id,tstaff_channel.add_time,tstaff_channel.department,tstaff_channel.channel_id')
            ->where($wh)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('tstaff_channel.id desc')
            ->select();
        
        $show = $Page->show();
        $this->assign('q_list', $list);
        $this->assign('result', $outcome);
        $this->assign('page', $show);
        $this->display();
    }
    
    // 添加通知信息
    public function addsms() {
        $row = M('tstaff_sms')->field('sms_title,sms_content')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        if (! $this->isPost()) {
            $this->assign('row', $row);
            $this->display();
        } else {
            $data = I('post.');
            M('tstaff_sms')->where(
                array(
                    'node_id' => $this->node_id))->delete();
            $data_arr = array(
                'sms_title' => $data['sms_title'], 
                'sms_content' => $data['sms_content'], 
                'sms_add_time' => date('YmdHis'), 
                'node_id' => $this->node_id);
            $fruit = M('tstaff_sms')->add($data_arr);
            if (! $fruit)
                $this->error('信息添加失败！');
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        }
    }

    public function addsmsSubmit() {
        $id1 = I('id1');
        $type1 = I('type1');
        $sid1 = I('sid');
        // $row=M('tstaff_sms')->field('sms_title,sms_content')->where(array('node_id'=>$this->node_id))->find();
        if (! $this->isPost()) {
            $this->assign('id', $id1);
            $this->assign('sid', $sid1);
            $this->assign('type1', $type1);
            // $this->assign('row',$row);
            $this->display();
        } else {
            $data = I('post.');
            $tranDb = new Model();
            $tranDb->startTrans();
            /**
             * M('tstaff_sms')->where(array('node_id'=>$this->node_id))->delete();
             * $data_arr=array( 'sms_title'=>$data['sms_title'],
             * 'sms_content'=>$data['sms_content'],
             * 'sms_add_time'=>date('YmdHis'), 'node_id'=>$this->node_id, );
             * $fruit=M('tstaff_sms')->add($data_arr); if(!$fruit){
             * $tranDb->rollback(); $this->error('短信修改失败！！！'); }
             */
            $table = M('tstaff_channel')->field(
                'id,petname,phone,sc_email,department,channel_id')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'estate' => '1'))
                ->select();
            $result = M('tchannel')->field('id,name')
                ->where(
                array(
                    'type' => 5, 
                    'sns_type' => 51, 
                    'node_id' => $this->node_id, 
                    'status' => '1'))
                ->select();
            if (empty($table) && empty($result))
                $this->error('您还没有添加员工渠道，请先添加员工渠道！');
            
            $str = '';
            foreach ($result as $v) {
                $str .= $v['id'] . ",";
            }
            $str = substr($str, 0, - 1);
            
            $query = M('tbatch_channel')->where(
                array(
                    'batch_id' => $data['id'], 
                    'batch_type' => $data['type1'], 
                    'channel_id' => array(
                        'exp', 
                        "in ($str)")))->select();
            
            // if($query) $this->error('该活动已经在此渠道发布，不能重复发布！');
            
            $batch_name = M('tmarketing_info')->field('name')
                ->where(
                array(
                    'id' => $data['id'], 
                    'batch_type' => $data['type1'], 
                    'node_id' => $this->node_id))
                ->find();
            /**
             * $batch_no=M('tbatch_info') ->field('batch_no')
             * ->where(array('m_id'=>$data['id'])) ->find();
             * if(empty($batch_no)){ $batch_no=M('tbatch_info')
             * ->field('batch_no')
             * ->where(array('node_id'=>$this->node_id,'status'=>'0','check_status'=>'1','send_end_date'=>array('gt',Date('YmdHis').'000000')))
             * ->find(); }
             */
            $filepath = "Home/Upload/staff/";
            $tmpname = rand(1000, 9999) . ".csv";
            $fp = fopen($filepath . $tmpname, "w+");
            ;
            $result = "员工姓名, 员工部门, 链接\r\n";
            $result = iconv('utf-8', 'gbk', $result);
            foreach ($table as $v) {
                $query = M('tbatch_channel')->where(
                    array(
                        'batch_id' => $data['id'], 
                        'batch_type' => $data['type1'], 
                        'channel_id' => $v['channel_id']))->find();
                if (! $query) {
                    $res_id = M('tbatch_channel')->add(
                        array(
                            'batch_type' => $data['type1'],  // 活动类型
                            'batch_id' => $data['id'],  // 活动ID
                            'channel_id' => $v['channel_id'],  // 渠道ID
                            'add_time' => date('YmdHis'), 
                            'node_id' => $this->node_id, 
                            'status' => '1'));
                    $query['id'] = $res_id;
                    /**
                     * $content1=str_replace("[活动名称]",$batch_name['name'],$data['sms_content']);
                     * $content2=str_replace("[员工名称]",$v['petname'],$content1);
                     * $content3=str_replace("[链接]",$url,$content2);
                     */
                    
                    /**
                     * $row=$this->add_tbatch_importdetail($batch_no['batch_no'],$v['phone'],$content3);
                     * if($row===false){ $tranDb->rollback();
                     * $this->error('发送失败！！！'); }
                     */
                    if (! $res_id) {
                        $tranDb->rollback();
                        $this->error('活动绑定失败！！！');
                    }
                }
                $url = $this->shorturl(
                    U('Label/Label/index@' . $_SERVER['HTTP_HOST'], 
                        array(
                            'id' => $query['id'])));
                $result .= iconv('utf-8', 'gbk', $v['petname']) . "," .
                     iconv('utf-8', 'gbk', $v['department']) . " ," . $url .
                     "\r\n";
            }
            
            fputs($fp, $result);
            fclose($fp);
            $ctxt = "尊敬的客户，您好！<br/>
    旺财平台已经为每一个员工生成了专属链接，点击附件下载即可！
顺其商颂
                                                旺财团队";
            $content = array(
                'petname' => $data['sms_title'], 
                'test_title' => '旺财营销平台-活动推广通知', 
                'text_content' => $ctxt, 
                'add_file' => $filepath . $tmpname);
            $res = to_email($content);
            if ($res != 2) {
                $tranDb->rollback();
                if ($this->isAjax()) {
                    $this->error('发送失败', '', true);
                } else {
                    $this->error('发送失败000');
                }
            }
            @unlink($filepath . $tmpname);
            $tranDb->commit();
            if ($this->isAjax()) {
                $this->success('', '', true);
            }
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
        }
    }
    // 长链换短链
    public function shorturl($long_url) {
        $apiUrl = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . rand(10000, 99999), 
                'OriginUrl' => "<![CDATA[$long_url]]>"));
        
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($req_arr, 'gbk');
        $error = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;
            return '';
        }
        
        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();
        
        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }
    // 发短信插表
    public function add_tbatch_importdetail($b_id, $cell, $content) {
        $arr = array(
            'batch_no' => $b_id, 
            'batch_id' => '0', 
            'node_id' => $this->node_id, 
            'request_id' => date("YmdHis") . mt_rand(100000, 999999), 
            'phone_no' => $cell, 
            'status' => '0', 
            'add_time' => date("YmdHis"), 
            'trans_type' => '1', 
            'send_level' => '1', 
            'sms_notes' => $content);
        $result = M('tbatch_importdetail')->add($arr);
        return $result;
    }
    
    // 添加推广员工
    public function addstaff() {
        $data = I('post.');
        $data_arr1 = array(); // 渠道表
        $data_arr2 = array(); // 员工表
        $data_arr1['node_id'] = $this->node_id;
        $data_arr1['add_time'] = date('YmdHis');
        $data_arr1['status'] = '1';
        $data_arr1['type'] = $data['type'];
        $data_arr1['sns_type'] = $data['sns_type'];
        
        $data_arr2['add_time'] = date('YmdHis');
        $data_arr2['node_id'] = $this->node_id;
        // $data['add_type'] @@添加类型 1单个添加 2批量添加
        if ($data['add_type'] == '1') {
            $data_arr1['name'] = $data['name'];
            $data_arr2['petname'] = $data['name'];
            $data_arr2['phone'] = $data['cell_phone'];
            $data_arr2['department'] = $data['department'];
            $data_arr2['sc_email'] = $data['sc_email'];
            
            if ($data['is_parkyard'] == 'Y' &&
                 $this->node_id == C('fb_boya.node_id')) {
                if ($data['name'] == '') {
                    $this->_parkYardSuccess('员工姓名不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
                if ($data['cell_phone'] == '') {
                    $this->_parkYardSuccess('员工手机不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                } else {
                    if (! check_str($data['cell_phone'], 
                        array(
                            'strtype' => 'mobile'))) {
                        $this->_parkYardSuccess('员工手机格式不正确，请填写11位数字！', 2, 
                            $_SERVER['HTTP_REFERER']);
                        exit();
                    }
                }
                if ($data['sc_email'] == '') {
                    $this->_parkYardSuccess('员工邮箱不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
                if ($data['department'] == '') {
                    $this->_parkYardSuccess('员工部门不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
            }
            // 启动事务
            $tranDb = new Model();
            $tranDb->startTrans();
            $outcome = M('tchannel')->add($data_arr1);
            if ($data['is_parkyard'] == 'Y' &&
                 $this->node_id == C('fb_boya.node_id')) {
                $data_arr2['promotion_id'] = $outcome;
                $data_arr2['status'] = '1';
                $result = $this->_parkYardQure($outcome);
                if ($result['error'] != '0') {
                    $tranDb->rollback(); // 事务回滚
                    $this->error($result['msg']);
                    exit();
                }
                $fruit = M('TecshopPromotion')->add($data_arr2);
            } elseif ($data['is_parkyard'] == 'N') {
                $data_arr2['estate'] = '1';
                $data_arr2['channel_id'] = $outcome;
                $fruit = M('tstaff_channel')->add($data_arr2);
            }
            if (! $fruit) {
                $tranDb->rollback(); // 事务回滚
                $this->error('渠道添加失败！');
            }
            $tranDb->commit(); // 事务提交
            if ($data['is_parkyard'] == 'Y' &&
                 $this->node_id == C('fb_boya.node_id')) {
                $this->_parkYardSuccess('添加成功！', 1, '', 5);
                exit();
            }
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        } elseif ($data['add_type'] == '2') {
            import('ORG.Net.UploadFile') or die('导入上传包失败');
            $upload = new UploadFile();
            $upload->maxSize = 3145728;
            $upload->allowExts = array(
                'csv');
            $upload->savePath = C('CHANNEL_TPL');
            if ($_FILES['staff']['error'] > 0 || $_FILES['staff']['size'] <= 23) {
                $this->_parkYardSuccess('请核对上传文件！', 2, $_SERVER['HTTP_REFERER']);
                exit();
            }
            $info = $upload->uploadOne($_FILES['staff']);
            $flieWay = $upload->savePath . $info['savepath'] .
                 $info[0]['savename'];
            $row = 0;
            if (($handle = fopen($flieWay, "rw")) !== FALSE) {
                $errorArray = array();
                $tranDb = new Model();
                $tranDb->startTrans();
                while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    ++ $row;
                    $arr = utf8Array($arr);
                    if ($row == 1) {
                        $fileField = array(
                            '姓名', 
                            '手机号', 
                            '邮箱', 
                            '部门');
                        $arrDiff = array_diff_assoc($arr, $fileField);
                        if (count($arr) != count($fileField) || ! empty(
                            $arrDiff)) {
                            fclose($handle);
                            @unlink($flieWay);
                            $this->error(
                                '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                        }
                        continue;
                    }
                    
                    // 员工名称
                    $data_arr2['petname'] = $arr[0];
                    $data_arr1['name'] = $arr[0];
                    // 员工手机
                    $data_arr2['phone'] = $arr[1];
                    $data_arr2['sc_email'] = $arr[2];
                    // 员工部门
                    $data_arr2['department'] = $arr[3];
                    $outcome = M('tchannel')->add($data_arr1);
                    if ($data['is_parkyard'] == 'Y' &&
                         $this->node_id == C('fb_boya.node_id')) {
                        if ($arr[0] == '') {
                            $errorArray[$row]['errorNumber'] = $row;
                            $errorArray[$row]['msg'][] = '员工姓名不能为空';
                        }
                        if ($arr[1] == '') {
                            $errorArray[$row]['errorNumber'] = $row;
                            $errorArray[$row]['msg'][] = '员工手机不能为空';
                        } else {
                            if (! check_str($data['cell_phone'], 
                                array(
                                    'strtype' => 'mobile'))) {
                                $errorArray[$row]['errorNumber'] = $row;
                                $errorArray[$row]['msg'][] = '员工手机格式不正确';
                            }
                        }
                        if ($arr[2] == '') {
                            $errorArray[$row]['errorNumber'] = $row;
                            $errorArray[$row]['msg'][] = '员工邮箱不能为空';
                        }
                        if ($arr[3] == '') {
                            $errorArray[$row]['errorNumber'] = $row;
                            $errorArray[$row]['msg'][] = '员工部门不能为空';
                        }
                        $data_arr2['promotion_id'] = $outcome;
                        $data_arr2['status'] = '1';
                        $this->_parkYardQure($outcome);
                        $batchId = M('TecshopPromotion')->add($data_arr2);
                    } elseif ($data['is_parkyard'] == 'N') {
                        $batchModel = M('tstaff_channel');
                        $data_arr2['channel_id'] = $outcome;
                        $batchId = $batchModel->add($data_arr2);
                    }
                }
            }
            @fclose($handle);
            @unlink($flieWay);
        }
        if ($data['is_parkyard'] == 'Y' && $this->node_id == C(
            'fb_boya.node_id')) {
            if (empty($errorArray)) {
                $tranDb->commit();
                $this->_parkYardSuccess('恭喜您，员工添加成功！', 1, '', 5);
                exit();
            } else {
                $tranDb->rollback();
                $this->_parkYardSuccess('对不起，批量添加员工失败！', 2, 
                    $_SERVER['HTTP_REFERER'], 10, $errorArray);
                exit();
            }
        } else {
            $tranDb->commit();
        }
        echo "<script>parent.art.dialog.list['uduf'].close()</script>";
        exit();
    }
    
    // 编辑员工状态
    public function editStatus() {
        $id = I('id');
        $status = I('status');
        if (is_null($id) || is_null($status))
            $this->error('缺少必要参数');
        $result = M('tstaff_channel')->field('channel_id')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))
            ->find();
        if (! $result)
            $this->error('未找到该员工');
        $data = array(
            'estate' => $status);
        $data1 = array(
            'status' => $status);
        $row1 = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $result['channel_id']))->save($data1);
        $row = M('tstaff_channel')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->save($data);
        
        if ($row) {
            $this->success('更新成功$row');
        } else {
            $this->error("更新失败$row");
        }
    }
    
    // 编辑员工信息
    public function edityuangong() {
        if ($this->isPost()) {
            $data = I('post.');
            if ($data['is_parkYard'] == 'Y') {
                $arr = array();
                $arr['phone'] = $data['cell_phone'];
                if ($data['cell_phone'] == '') {
                    $this->_parkYardSuccess('员工手机不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                } else {
                    if (! check_str($data['cell_phone'], 
                        array(
                            'strtype' => 'mobile'))) {
                        $this->_parkYardSuccess('员工手机格式不正确，请填写11位数字！', 2, 
                            $_SERVER['HTTP_REFERER']);
                        exit();
                    }
                }
                $arr['sc_email'] = $data['sc_email'];
                if ($data['sc_email'] == '') {
                    $this->_parkYardSuccess('员工邮箱不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
                $arr['department'] = $data['department'];
                if ($data['department'] == '') {
                    $this->_parkYardSuccess('员工部门不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
                $arr['petname'] = $data['name'];
                if ($data['department'] == '') {
                    $this->_parkYardSuccess('员工姓名不能为空！', 2, 
                        $_SERVER['HTTP_REFERER']);
                    exit();
                }
                $isExist = M('TecshopPromotion')->where(
                    array(
                        'promotion_id' => $data['stid'], 
                        'node_id' => $this->node_id))->find();
                if ($isExist) {
                    if ($data['cell_phone'] == $isExist['phone'] &&
                         $data['sc_email'] == $isExist['sc_email'] &&
                         $data['department'] == $isExist['department'] &&
                         $data['name'] == $isExist['petname']) {
                        $this->_parkYardSuccess(
                            '对不起，修改员工失败，已存在相同数据，请使用取消功能或者修改信息！', 2, 
                            $_SERVER['HTTP_REFERER']);
                        exit();
                    }
                }
                $result = M('TecshopPromotion')->where(
                    array(
                        'promotion_id' => $data['stid'], 
                        'node_id' => $this->node_id))->save($arr);
            } elseif ($data['is_parkYard'] == 'N') {
                $arr = array(
                    'petname' => $data['name'], 
                    'phone' => $data['cell_phone'], 
                    'sc_email' => $data['sc_email'], 
                    'department' => $data['department']);
                $result = M('tstaff_channel')->where(
                    array(
                        'id' => $data['stid'], 
                        'node_id' => $this->node_id))->save($arr);
            }
            if ($result === false)
                $this->error("更新失败$result");
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        } else {
            $type = I('type');
            $id = I('id');
            if ($id == '') {
                echo "参数错误！！！";
                die();
            }
            if ($type == 'parkYard' && $this->nodeId == C('fb_boya.node_id')) {
                $row = M('TecshopPromotion')->where(
                    array(
                        'promotion_id' => $id))->find();
                $parkYardIndexModel = A('/Home/Index');
                $parkYardIndexModel->_getPromotionOption();
                $this->assign('parkYard', 'Y');
            } else {
                $row = M('tstaff_channel')->where(
                    array(
                        'id' => $id))->find();
                $this->assign('parkYard', 'N');
            }
            $this->assign('outcome', $row);
            $this->display();
        }
    }
    
    // 爱拍渠道  展示
    public function aipaiCancel() {
        $model = M('tchannel');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'type' => '3');
        $cName = I('c_name', null, 'mysql_real_escape_string');
        $data = array();
        if (isset($cName) && $cName != '') {
            $map['name'] = array(
                'like', 
                "%{$cName}%");
            $data['name'] = $cName;
        }
        $cType = I('c_type', null, 'mysql_real_escape_string');
        if (! empty($cType) && $cType != '1') {
            $map['sns_type'] = $cType;
            $data['sns_type'] = $cType;
        }
        $cStatus = I('c_status');
        if (! empty($cStatus)) {
            $map['status'] = $cStatus;
            $data['status'] = $cStatus;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['node_id '] = $nodeId;
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('status asc,add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            // 爱拍活动
            $batch_info = M('tpp_batch')->where(
                array(
                    'node_id' => $this->nodeId))->find();
            $this->assign('batch_info', $batch_info);
        }
        $arr_ = C('CHANNEL_TYPE_ARR');
        // $this->assign('click_arr',$click_arr);
        $this->assign('arr_', $arr_[3]);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('map', $_REQUEST);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }

    /**
     *
     * @return StoresModel
     */
    private function getStoresModel() {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }
        return $this->storesModel;
    }
    


    public function addSubmit2() {
        $model = M('tchannel');
        $data = I('post.');
        if (empty($data['name']) || empty($data['type']) ||
             empty($data['sns_type']))
            $this->error('渠道参数不完整！');
        
        $data_arr = array();
        $data_arr['name'] = $data['name'];
        $data_arr['type'] = $data['type'];
        $data_arr['sns_type'] = $data['sns_type'];
        $data_arr['add_time'] = date('YmdHis');
        $data_arr['node_id'] = $this->node_id;
        $data_arr['status'] = '1';
        $row = $model->add($data_arr);
        $re = array(
            'channel_id' => $row, 
            'name' => $data_arr['name']
        );
        $this->ajaxReturn($re, '', 1);
    }
    // 添加提交页
    public function addSubmit_() {
        $data = array_map('trim', I('post.'));

//        if (empty($data['name']) || empty($data['type']) ||
//                empty($data['sns_type'])) {
//            $this->ajaxReturn(
//                    array(
//                            'status' => 0,
//                            'info' => '渠道参数不完整'), 'JSON');
//        }

        if (empty($data['name'])) {
            $this->ajaxReturn(
                    array(
                            'status' => 0,
                            'info' => '渠道参数不完整'), 'JSON');
        }


        $model = M('tchannel');

        $data_arr = array();
        $data_arr['name'] = $data['name'];

        $data_arr['type'] = $data['type'];
//        $data_arr['sns_type'] = $data['sns_type'];
//        $data_arr['address'] = get_val($data['address']);
        $data_arr['add_time'] = date('YmdHis');
        $data_arr['node_id'] = $this->node_id;
        $data_arr['status'] = '1';
//        $data_arr['province_code'] = get_val($data['province']);
//        $data_arr['city_code'] = get_val($data['city']);
//        $data_arr['town_code'] = get_val($data['town']);
        
        if ($data['type'] == '2') {
//            $data_arr['logo_img'] = $data['logo_img'];
//            $data_arr['batch_type'] = $data['batch_type'];
//            $data_arr['batch_id'] = $data['batch_id'];
//            $data_arr['qr_color'] = $data['color'];
//            $data_arr['qr_size'] = $data['size'];
            $shoptype = $data["shop2"];
            if ($shoptype == 1) {
                $storeid = $data['storeid'];
                $result = M('tstore_info')->where(
                    array(
                        'store_id' => $storeid, 
                        'status' => '0'))
                    ->field(
                    'store_name,address,province_code,city_code,town_code ')
                    ->select();
                $data_arr['store_id'] = $storeid;
                $data_arr['address'] = $result[0]["address"];
                $data_arr['province_code'] = $result[0]["province_code"];
                $data_arr['city_code'] = $result[0]["city_code"];
                $data_arr['town_code'] = $result[0]["town_code"];
            } else if ($shoptype == 2) {
                $data_arr['province_code'] = $data['province_code'];
                $data_arr['city_code'] = $data['city_code'];
                $data_arr['town_code'] = $data['town_code'];
                $data_arr['address'] = $data['address'];
            }
            
            if ($data_arr['province_code'] != '' || $data_arr['city_code'] != '' ||
                 $data_arr['town_code'] != '') {
                $area_type = '1';
            }
        }
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $execute = $model->add($data_arr);
        if (! $execute) {
            $tranDb->rollback();
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '渠道添加失败！'), 'JSON');
        } else {
            if ($data['type'] == '1') {
                $tranDb->commit();
                if ($data['sns_type'] != 4 && $data['sns_type'] != 8) {
                    if ($data['sns_type'] == '10') {
                        $jumpUrl = array(
                            '返回渠道管理' => U('Channel/onlineCancel'));
                    } elseif ($data['sns_type'] == '11') {
                        $callback = I('get.callback', '');
                        if ($callback) {
                            $res = array(
                                'channel_id' => $execute,
                                'name' => $data_arr['name']
                            );
                            $this->success($res);
                        }
                        $jumpUrl = array(
                            '返回渠道管理' => U('Channel/onlineCancel'));
                    } else {
                        $jumpUrl = array(
                            '返回渠道管理' => U('Channel/onlineCancel'), 
                            '绑定平台账号' => "javascript:bind('$execute')");
                    }
                    
                    $snsArr = C('SNS_URL');
                    node_log('互联网渠道添加|渠道名:' . $data['name']);
                    $this->assign('snsUrl', get_val($snsArr[$data['sns_type']]));
                    // $this->success('渠道添加成功',$jumpUrl);
                    echo "<script>parent.art.dialog.list['uduf'].close()</script>";
                    exit();
                } else {
                    node_log('互联网渠道添加|渠道名:' . $data['name']);
                    $jumpUrl = array(
                        '返回渠道管理' => U('Channel/onlineCancel'));
                    // $this->success('渠道添加成功',$jumpUrl);
                    echo "<script>parent.art.dialog.list['uduf'].close()</script>";
                    exit();
                }
            } elseif ($data['type'] == '2') {
                $jumpUrl = array(
                    '返回渠道管理' => U('offlineCancel'));
                if (! empty($data['batch_id']) && ! empty($data['batch_type'])) {
                    // 更新标签表
                    $mod = M('tbatch_channel');
                    $data = array(
                        'batch_type' => $data['batch_type'], 
                        'batch_id' => $data['batch_id'], 
                        'channel_id' => $execute, 
                        'add_time' => Date('YmdHis'), 
                        'node_id' => $this->nodeId);
                    $query = $mod->add($data);
                    if (! $query) {
                        $tranDb->rollback();
                        $this->ajaxReturn(
                            array(
                                'status' => 0, 
                                'info' => '渠道添加失败'), 'JSON');
                    }
                }
                $tranDb->commit();
                node_log('二维码标签渠道添加|渠道名:' . $data['name']);
                // $this->success('渠道添加成功',$jumpUrl);
                $this->ajaxReturn(
                    array(
                        'status' => 1, 
                        'info' => '渠道添加成功', 'channel_id' => $execute, 'name' => $data['name']), 'JSON');
                exit();
            } elseif ($data['type'] == '5') {
                $tranDb->commit();
                node_log('其他渠道添加|渠道名:' . $data['name']);
                echo "<script>window.location.href='index.php?g=LabelAdmin&m=Channel&a=staffChannel'</script>";
                exit();
            }
        }
    }
    // 添加提交页
//    public function addSubmit() {
//        $model = M('tchannel');
//        $data = array_map('trim', I('post.'));
//        if (empty($data['name']) || empty($data['type']) ||
//                empty($data['sns_type'])) {
//            $this->ajaxReturn(
//                    array(
//                            'status' => 0,
//                            'info' => '渠道参数不完整'), 'JSON');
//        }
//
//        $data_arr = array();
//        $data_arr['name'] = $data['name'];
//        // if($data['type'] == '5' || $data['sns_type'] == '51')
//        // $data_arr['name']='员工推广';
//        $data_arr['type'] = $data['type'];
//        $data_arr['sns_type'] = $data['sns_type'];
//        $data_arr['address'] = get_val($data['address']);
//        $data_arr['add_time'] = date('YmdHis');
//        $data_arr['node_id'] = $this->node_id;
//        $data_arr['status'] = '1';
//        $data_arr['province_code'] = get_val($data['province']);
//        $data_arr['city_code'] = get_val($data['city']);
//        $data_arr['town_code'] = get_val($data['town']);
//
//        if ($data['type'] == '2') {
//            $data_arr['logo_img'] = $data['logo_img'];
//            $data_arr['batch_type'] = $data['batch_type'];
//            $data_arr['batch_id'] = $data['batch_id'];
//            $data_arr['qr_color'] = $data['color'];
//            $data_arr['qr_size'] = $data['size'];
//            $shoptype = $data["shop2"];
//            if ($shoptype == 1) {
//                $storeid = $data['storeid'];
//                $result = M('tstore_info')->where(
//                        array(
//                                'store_id' => $storeid,
//                                'status' => '0'))
//                        ->field(
//                                'store_name,address,province_code,city_code,town_code ')
//                        ->select();
//                $data_arr['store_id'] = $storeid;
//                $data_arr['address'] = $result[0]["address"];
//                $data_arr['province_code'] = $result[0]["province_code"];
//                $data_arr['city_code'] = $result[0]["city_code"];
//                $data_arr['town_code'] = $result[0]["town_code"];
//            } else if ($shoptype == 2) {
//                $data_arr['province_code'] = $data['province_code'];
//                $data_arr['city_code'] = $data['city_code'];
//                $data_arr['town_code'] = $data['town_code'];
//                $data_arr['address'] = $data['address'];
//            }
//
//            if ($data_arr['province_code'] != '' || $data_arr['city_code'] != '' ||
//                    $data_arr['town_code'] != '') {
//                $area_type = '1';
//            }
//        }
//
//        // 开启事物
//        $tranDb = new Model();
//        $tranDb->startTrans();
//        $execute = $model->add($data_arr);
//        if (! $execute) {
//            $tranDb->rollback();
//            $this->ajaxReturn(
//                    array(
//                            'status' => 0,
//                            'info' => '渠道添加失败！'), 'JSON');
//        } else {
//            if ($data['type'] == '1') {
//                $tranDb->commit();
//                if ($data['sns_type'] != 4 && $data['sns_type'] != 8) {
//                    if ($data['sns_type'] == '10') {
//                        $jumpUrl = array(
//                                '返回渠道管理' => U('Channel/onlineCancel'));
//                    } elseif ($data['sns_type'] == '11') {
//                        $callback = I('get.callback', '');
//                        if ($callback) {
//                            $res = array(
//                                    'channel_id' => $execute,
//                                    'name' => $data_arr['name']
//                            );
//                            $this->success($res);
//                        }
//                        $jumpUrl = array(
//                                '返回渠道管理' => U('Channel/onlineCancel'));
//                    } else {
//                        $jumpUrl = array(
//                                '返回渠道管理' => U('Channel/onlineCancel'),
//                                '绑定平台账号' => "javascript:bind('$execute')");
//                    }
//
//                    $snsArr = C('SNS_URL');
//                    node_log('互联网渠道添加|渠道名:' . $data['name']);
//                    $this->assign('snsUrl', get_val($snsArr[$data['sns_type']]));
//                    // $this->success('渠道添加成功',$jumpUrl);
//                    echo "<script>parent.art.dialog.list['uduf'].close()</script>";
//                    exit();
//                } else {
//                    node_log('互联网渠道添加|渠道名:' . $data['name']);
//                    $jumpUrl = array(
//                            '返回渠道管理' => U('Channel/onlineCancel'));
//                    // $this->success('渠道添加成功',$jumpUrl);
//                    echo "<script>parent.art.dialog.list['uduf'].close()</script>";
//                    exit();
//                }
//            } elseif ($data['type'] == '2') {
//                $jumpUrl = array(
//                        '返回渠道管理' => U('offlineCancel'));
//                if (! empty($data['batch_id']) && ! empty($data['batch_type'])) {
//                    // 更新标签表
//                    $mod = M('tbatch_channel');
//                    $data = array(
//                            'batch_type' => $data['batch_type'],
//                            'batch_id' => $data['batch_id'],
//                            'channel_id' => $execute,
//                            'add_time' => Date('YmdHis'),
//                            'node_id' => $this->nodeId);
//                    $query = $mod->add($data);
//                    if (! $query) {
//                        $tranDb->rollback();
//                        $this->ajaxReturn(
//                                array(
//                                        'status' => 0,
//                                        'info' => '渠道添加失败'), 'JSON');
//                    }
//                }
//                $tranDb->commit();
//                node_log('二维码标签渠道添加|渠道名:' . $data['name']);
//                // $this->success('渠道添加成功',$jumpUrl);
//                $this->ajaxReturn(
//                        array(
//                                'status' => 1,
//                                'info' => '渠道添加成功', 'channel_id' => $execute, 'name' => $data['name']), 'JSON');
//                exit();
//            } elseif ($data['type'] == '5') {
//                $tranDb->commit();
//                node_log('其他渠道添加|渠道名:' . $data['name']);
//                echo "<script>window.location.href='index.php?g=LabelAdmin&m=Channel&a=staffChannel'</script>";
//                exit();
//            }
//        }
//    }

    //渠道编辑
    function edit() {
        $id = I('id', null);
        $name = I('name', null);
        if (empty($id))
            $this->error('参数错误');
        if (! isset($name) || $name == '')
            $this->error('渠道名称不能为空');
        $channelModel = M('tchannel');
        $count = $channelModel->where(
            "node_id in ({$this->nodeIn()}) AND id='{$id}'")->count();
        if ($count <= 0)
            $this->error('未找到符合条件的渠道');
        $data = array(
            'id' => $id, 
            'name' => $name);
        $result = $channelModel->data($data)->save();
        // echo M()->getLastSql();exit;
        if ($result !== false) {
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }
    
    // 爱拍激活
    public function bindAiPai() {
        $id = I('id');
        $query_arr = M('tchannel')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $id, 
                'type' => '3'))->find();
        if (! $query_arr)
            $this->error('错误参数！');
        
        $is_exits = M('tbatch_channel')->where(
            array(
                'node_id' => $this->nodeId, 
                'channel_id' => $id))->find();
        if ($is_exits)
            $this->error('已绑定!');
            
            // 是否存在活动
        $pp = M('tpp_batch')->where(
            array(
                'node_id' => $this->nodeId))->find();
        if (! $pp)
            $this->error('未查询到当前活动！');
            
            // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $data = array(
            'node_id' => $this->nodeId, 
            'channel_id' => $id, 
            'batch_id' => $pp['id'], 
            'batch_type' => '5', 
            'add_time' => date('YmdHis'), 
            'status' => '1');
        $query = M('tbatch_channel')->add($data);
        if (! $query) {
            $tranDb->rollback();
            $this->error('绑定失败！');
        }
        
        // 更新渠道
        $cdata = array(
            'batch_type' => '5', 
            'batch_id' => $pp['id']);
        $query = M('tchannel')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $id))->save($cdata);
        if ($query) {
            $tranDb->commit();
            $this->success('绑定成功！');
        } else {
            $tranDb->rollback();
            $this->error('绑定失败！');
        }
    }
    
    // 更改状态
    public function changeStatus() {
        $id = I('id');
        $status = I('status');
        if (empty($id) || empty($status))
            $this->error('非法参数！');
        if (! in_array($status, 
            array(
                '1', 
                '2'))) {
            $status = '2';
        }
        $model = M('tchannel');
        $map = array(
            'id' => $id, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $query = $model->where($map)->find();
        if (! $query)
            $this->error('错误参数!');
        $data = array(
            'status' => $status);
        M()->startTrans();
        $exec = $model->where($map)
            ->data($data)
            ->save();
        if (false === $exec) {
            M()->rollback();
            $this->error('解绑出错！');
        }
        if ($status == '2') {//停用
            //更新batch_channel,解绑渠道下的所有活动
            $changeBC = M('tbatch_channel')
            ->where(array('node_id' => $this->node_id, 'channel_id' => $id))
            ->save(array('status' => '2'));
            if (false === $changeBC) {
                M()->rollback();
                $this->error('解绑出错！');
            }
        }
        M()->commit();
        if ($exec) {
            $status_info = $status == '1' ? '启用' : '停用';
            node_log('渠道' . $status_info . '|渠道ID:' . $id);
            $this->success('更新成功！', 
                array(
                    '返回' => U('Channel/offlineCancel')));
        } else {
            $this->error('更新失败！');
        }
    }
    // 解除绑定
    public function unBind() {
        $id = I('id');
        if (empty($id))
            $this->error('非法参数！');
        $model = M('tchannel');
        $map = array(
            'id' => $id, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $query = $model->where($map)->find();
        if (! $query)
            $this->error('错误参数!');
        
        $mod = M('tbatch_channel');
        // 如果是绑定的外链
        if (! empty($query['go_url'])) {
            // channel表解绑
            $model->where($map)->save(
                array(
                    'go_url' => ''));
            // batch_channel表解绑（外链的活动号和活动类型号为0）
            $mod->where(
                array(
                    'batch_id' => 0, 
                    'batch_type' => 0, 
                    'channel_id' => $id, 
                    'node_id' => $this->node_id))->save(
                array(
                    'status' => '2'));
            $this->success('更新成功！', 
                array(
                    '返回' => U('Channel/offlineCancel')));
        }
        
        $mId = I('mId');
        $batch_type = M('tmarketing_info')->where(
            array(
                'id' => $mId))->getField('batch_type');
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        // 更新标签表信息
        $where = array(
            'node_id' => $this->node_id, 
            'batch_type' => $batch_type, 
            'batch_id' => $mId, 
            'channel_id' => $id);
        $up = array(
            'status' => '2', 
            'change_time' => date('YmdHis'));
        $query = $mod->where($where)->save($up);
        if ($query === false) {
            $tranDb->rollback();
            $this->error('更新失败！');
        }
        $tranDb->commit();
        $this->success('更新成功！', 
            array(
                '返回' => U('Channel/offlineCancel')));
    }
    
    // 高级渠道
    public function Alipay() {
        $htmlid = I('htmlid');
        if ($this->isPost()) {
            $need_time = date('YmdHis');
            $query = M('tmessage_apply')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'type' => '3'))->find();
            if ($query)
                $this->error('你好我们已经收到你的申请，会尽快给你回复!');
            if (I('act') == 'check') {
                $this->success('校验成功');
                exit();
            }
            
            $array = array(
                'node_id' => $this->node_id, 
                'need_time' => $need_time, 
                'add_time' => date('YmdHis'), 
                'type' => '3', 
                'status' => '1');
            $query = M('tmessage_apply')->add($array);
            
            if ($query) {
                $node_arr = M('tnode_info')->where(
                    "node_id='" . $this->node_id . "'")->find();
                $company_name = $_POST['company_name'];
                $company_alipay = $_POST['company_alipay'];
                $company_text = $_POST['company_text'];
                $company_service = $_POST['company_service'];
                $content = "企业名称:" . $node_arr['node_name'] . "<br />" . "旺号：" .
                     $node_arr['client_id'] . "<br />" . "姓名：" .
                     $node_arr['contact_name'] . "<br />" . "邮箱:" .
                     $node_arr['contact_eml'] . "<br />" . "手机号:" .
                     $node_arr['contact_phone'] . "<br />" . "商户名称:" .
                     $company_name . "<br/>" . "支付宝账户:" . $company_alipay .
                     "<br/>" . "商户简介:" . $company_text . "<br/>" . "计划服务内容:" .
                     $company_service . "<br/>";
                if (! empty($node_arr['trade_type'])) {
                    $trade_name = M('tindustry_info')->where(
                        array(
                            'industry_id' => $node_arr['trade_type']))->getField(
                        'industry_name');
                }
                if ($trade_name != '')
                    $content = $content . "行业类型:" . $trade_name . "<br />";
                $email = C('SEND_EMAIL');
                $ps = array(
                    "subject" => "支付宝助手申请", 
                    "content" => $content, 
                    "email" => $email);
                send_mail($ps);
                $this->success('亲，我们已收到您的申请请求，旺小二会在2个工作日内通过邮件告知您相关审核结果！');
            } else
                $this->error('申请失败!');
        } else {
            $node_arr = M('tnode_info')->where(
                "node_id='" . $this->node_id . "'")->find();
            $this->assign("noname", $node_arr['node_name']);
            $this->assign("htmlid", $htmlid);
            $this->display();
        }
    }

    public function payment() {
        if ($this->isPost()) {
            $data = I('post.');
            $content = "姓名：" . $data['linkman'] . "<br/>";
            $content .= "电话:" . $data['tel'] . "<br/>";
            $content .= "邮箱:" . $data['email'] . "<br/>";
            $content .= "渠道名称:" . $data['channel'] . "<br/>";
            $content .= "说明:" . $data['demand_memo'] . "<br/>";
            $ps = array(
                "subject" => "旺财营销平台-商户提供渠道", 
                "content" => $content, 
                "email" => "chenjianyong@imageco.com.cn"); // 原邮箱wuqx@imageco.com.cn、7005@imageco.com.cn
            
            $resp = send_mail($ps);
            
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        }
        $htmlid = I('htmlid');
        $this->assign("htmlid", $htmlid);
        $this->display();
    }

    public function joint() {
        $htmlid = I('htmlid');
        // echo $htmlid;
        $this->assign("htmlid", $htmlid);
        $this->display();
    }
    //员工
    function yuangong() {
        $type = I('type', '0', 'string');
        if ($type == 'parkYark') {
            $parkYardIndexModel = A('/Home/Index');
            $parkYardIndexModel->_getPromotionOption();
            $this->assign('parkYard', 'PY');
        }
        $this->display();
    }
    //微信
    function _parkYardQure($channel) {
        $node_id = $this->nodeId;
        $result = array();
        // 查询公众账号配置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        
        // 1.校验公众账号
        if (! $weixinInfo || ! $weixinInfo['app_id'] ||
             ! $weixinInfo['app_secret']) {
            $this->error("请先配置微信公众账号。", 
                array(
                    '确定' => U('Weixin/Weixin/index')));
            $result['error'] = '1001';
            $result['url'] = U('Weixin/Weixin/index');
            $result['msg'] = '请先配置微信公众账号!';
            return $result;
            exit();
        }
        
        // 去微信获取token
        $wxService = D('WeiXinQrcode', 'Service');
        $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
            $weixinInfo['app_access_token']);
        
        // 查看是否绑定过渠道
        $wcInfo = M('twx_qrchannel')->where(
            array(
                'node_id' => $node_id, 
                'channel_id' => $channel))
            ->field("scene_id,id")
            ->find();
        if (! $wcInfo || ! $wcInfo['scene_id']) {
            $scene_id = D('TweixinInfo')->getSceneId($node_id);
        } else {
            $scene_id = $wcInfo['scene_id'];
        }
        if (! $scene_id) {
            $result['error'] = '2001';
            $result['msg'] = '生成 scene_id 失败!';
            return $result;
            exit();
        }
        // 去微信接口获取图片内容
        $qrResult = $wxService->getQrcodeImg(
            array(
                'scene_id' => $scene_id));
        // 更新accessToken
        if ($weixinInfo['app_access_token'] != $wxService->accessToken) {
            $query = M('tweixin_info')->where(
                array(
                    'node_id' => $node_id))->save(
                array(
                    'app_access_token' => $wxService->accessToken));
        }
        
        // 如果失败
        if ($qrResult['status'] != '1') {
            $result['error'] = '3001';
            $result['msg'] = '获取推广二维码失败，原因：' . $qrResult['errcode'] . ':' .
                 $qrResult['errmsg'];
            return $result;
            exit();
        }
        
        $data[] = $qrResult['ticket_url'];
        // 更新
        if ($wcInfo) {
            $data = array(
                'img_info' => $qrResult['ticket_url'], 
                'scene_id' => $scene_id);
            $query = M('twx_qrchannel')->where(
                array(
                    'id' => $wcInfo['id']))->save($data);
        } else {
            $data = array(
                'scene_id' => $scene_id, 
                'img_info' => $qrResult['ticket_url'], 
                'channel_id' => $channel, 
                'add_time' => date('YmdHis'), 
                'node_id' => $node_id);
            $query = M('twx_qrchannel')->add($data);
        }
        if ($query === false) {
            Log::write('twx_qrchannel:' . print_r($data, true));
            $result['error'] = '4001';
            $result['msg'] = '发布失败,渠道号:' . $channel;
            return $result;
        } else {
            $result['error'] = '0';
            return $result;
        }
    }

    function _parkYardSuccess($message, $status = 1, $jumpUrl, $ajax, $error) {
        if (is_int($ajax)) {
            $this->assign('waitSecond', $ajax);
        }
        if (is_array($error) && $status == 2) {
            foreach ($error as $val) {
                $errorArray[$val['errorNumber']]['errorNumber'] = $val['errorNumber'];
                $str = '';
                foreach ($val['msg'] as $value) {
                    $str .= $value . '、';
                }
                $str = substr($str, 0, strlen($str) - 3);
                $errorArray[$val['errorNumber']]['msg'] = $str;
            }
            $this->assign('errorArray', $errorArray);
        }
        if (! empty($jumpUrl)) {
            $this->assign('jumpUrl', $jumpUrl);
        }
        $this->assign('msgTitle', 
            $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        $this->assign('status', $status);
        $this->assign('message', $message);
        if (! isset($this->waitSecond))
            $this->assign('waitSecond', '1');
        $this->display('ParkYardSuccess');
    }
}