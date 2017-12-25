<?php
// 投票
class VoteAction extends MyBaseAction
{
    // 初始化
    public function _initialize()
    {
        parent::_initialize();
        // 校验用户参与方式
        $this->_checkUser(true);
        // 判断是否登录
        $islogin = session('cjUserInfo') ? 1 : 0;
        $this->assign('islogin', $islogin);
    }

    public function index()
    {
        $id = $this->id;
        
        if ($this->batch_type != '20') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 判断预览时间是否过期
        if (false === $this->checkCjEndtime()) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro6.png', 
                    'errorTxt' => '预览时间已过期！', 
                    'errorSoftTxt' => '您访问的预览地址30分钟内有效，现已超时啦~'));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $row = $this->marketInfo;
        
        // 问题表
        $tmodel = M('tvote_question');
        $qarr = $tmodel->where(
            array(
                'label_id' => $this->batch_id))
            ->order('sort')
            ->select();
        
        // 答案表
        $amodel = M('tvote_question_info');
        
        if ($qarr) {
            $resp_arr = array();
            foreach ($qarr as $k => $v) {
                $resp_arr[$k]['info'] = $v;
                $resp_arr[$k]['info']['sum'] = $this->GetAnswerAllNumber(
                    $v['label_id'], $v['id']); // 投票选项总数
                $resp_arr[$k]['list'] = array();
                $aarr = $amodel->where(
                    array(
                        'question_id' => $v['id']))->select();
                foreach ($aarr as $kk => $vv) {
                    $resp_arr[$k]['list'][$kk] = $vv;
                    $resp_arr[$k]['list'][$kk]['num'] = $this->GetAnswerAnswerNumber(
                        $v['label_id'], $v['id'], $vv['value']);
                }
            }
        }
        // 获取是否投过票
        /*
         * $ip = GetIP(); $votetrace = M('tvote_trace'); $result=
         * $votetrace->where(array('ip'=>$ip,'label_id'=>$this->batch_id))->select();
         * if($result){ $result_flag=1; } else $result_flag=0;
         */
        
        $vote_flag = cookie('vote_' . $this->id);
        if ($vote_flag == 1)
            $result_flag = 1;
        else
            $result_flag = 0;
            
            // 抽奖配置表
        if ($row['is_cj'] == '1') {
            $model_c = M('tcj_rule');
            $map_c = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'status' => '1');
            $cj_rule_query = $model_c->field('cj_button_text,cj_check_flag')
                ->where($map_c)
                ->find();
            // 抽奖文字配置
            $cj_text = $cj_rule_query['cj_button_text'];
            // 判断是否显示参与码
            $cj_check_flag = $cj_rule_query['cj_check_flag'];
        }
        // 唐山平安非标
        if ($this->node_id == C("tangshan.node_id")) {
            $tangshan_arr = M("tfb_tangshan_pingan")->where(
                array(
                    "m_id" => $row['id'], 
                    "node_id" => $this->node_id))->find();
            if ($tangshan_arr) {
                $this->assign("tangshan_arr", $tangshan_arr);
            }
        }
        $this->assign("node_id", $this->node_id);
        $this->assign('id', $this->id);
        $this->assign('resp_arr', $resp_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('result_flag', $result_flag); // 是否显示结果 0 不显示 1显示
                                                    
        // 天王表
        if ($this->batch_id == '2810') {
            $this->assign('cj_label_id', 5021);
            $this->display('Balco');
            exit();
        }
        //for 中奖记录 start
        $DLCommonMobile = $this->getMobileForAwardList($id);
        if ($DLCommonMobile) {
            log_write('if $DLCommonMobile:'.var_export($DLCommonMobile,1));
            $this->assign('showAwardList', 'block');
        } else {
            log_write('else $DLCommonMobile:'.var_export($DLCommonMobile,1));
            $this->assign('showAwardList', 'none');
        }
        //for 中奖记录 end
        // $this->assign('node_name',$nodeName);
        $this->display(); // 输出模板
    }

    /*
     * 获取投票问题所有的投票总数
     */
    private function GetAnswerAllNumber($label_id, $question_id)
    {
        $model = M('tvote_question_stat');
        // $sum = $model->query("SELECT
        // IFNULL(TRUNCATE(SUM((LENGTH(answer_list)+1)/2),0),0) AS SUM FROM
        // tvote_question_stat WHERE label_id='".$label_id."' AND
        // question_id='".$question_id."'");
        $map = array(
            'label_id' => $label_id, 
            'question_id' => $question_id);
        $sum = $model->field(
            "IFNULL(TRUNCATE(SUM((LENGTH(answer_list)+1)/2),0),0) sum")
            ->where($map)
            ->find();
        return $sum['sum'];
    }

    /*
     * 获取投票问题单个投票选项的总数
     */
    private function GetAnswerAnswerNumber($label_id, $question_id, $answer_id)
    {
        $model = M('tvote_question_stat');
        $v_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E', 
            '6' => 'F', 
            '7' => 'G', 
            '8' => 'H', 
            '9' => 'I', 
            '10' => 'J', 
            '11' => 'K', 
            '12' => 'L', 
            '13' => 'M', 
            '14' => 'N', 
            '15' => 'O', 
            '16' => 'P', 
            '17' => 'Q', 
            '18' => 'R', 
            '19' => 'S', 
            '20' => 'T', 
            '21' => 'U', 
            '22' => 'V', 
            '23' => 'W', 
            '24' => 'X', 
            '25' => 'Y', 
            '26' => 'Z');
        // $sum = $model->query("SELECT COUNT(*) AS num FROM tvote_question_stat
        // WHERE label_id='".$label_id."' AND question_id='".$question_id."' AND
        // answer_list like '%".$v_arr['answer_id']."%'");
        $sum = $model->where(
            "label_id='" . $label_id . "' AND question_id='" . $question_id .
                 "' AND answer_list like '%" . $v_arr[$answer_id] . "%'")->count();
        return $sum;
    }

    public function Submit()
    {
        // $this->error(I('true_name'));
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
            
            // 翼蕙宝微信会员效验
        if ($this->node_id == C('Yhb.node_id')) {
            $this->assign('is_yhb', true);
            $yhb_user_info = $this->checkYhbMember();
            if (! $yhb_user_info['is_member']) {
                $return = array(
                    'status' => 5, 
                    'info' => "请先注册！");
                $this->ajaxReturn($return);
                exit();
            }
        }
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        $batch_type = $this->batch_type;
        $node_id = $this->node_id;
        
        $ip = GetIP();
        // 活动
        // $batchModel = M('tmarketing_info');
        // $batch_arr =
        // $batchModel->field('is_cj,start_time,end_time')->where(array('id'=>$batch_id,'batch_type'=>$batch_type))->find();
        // $is_cj = $batch_arr['is_cj'];
        $is_cj = $this->marketInfo['is_cj'];
        
        // 配置
        $get_bm_arr = array(
            '1' => 'true_name', 
            '2' => 'mobile');
        
        $bm_arr = array(
            '1' => '姓名', 
            '2' => '联系方式');
        $bm_len_arr = array(
            '1' => '10', 
            '2' => '11');
        /*
         * $select_type = I('post.select_type') ; $query_arr =
         * explode('-',$select_type); $column_arr = array(); foreach($query_arr
         * as $v){ switch($get_bm_arr[$v]){ case 'mobile':
         * if(!check_str(I('post.'.$get_bm_arr[$v],'null','trim'),
         * array('null'=>false,'strtype'=>'mobile')))
         * $this->ajaxReturn("error","联系方式请填写正确的手机号！",0); break; default:
         * //if(!check_str(I('post.'.$get_bm_arr[$v],'null','trim'),
         * array('null'=>false)))
         * $this->ajaxReturn("error","请完善页面所有信息！".I('post.'.$get_bm_arr[$v],'null','trim'),0);
         * break; } $column_arr[$get_bm_arr[$v]] = I($get_bm_arr[$v]); }
         */
        $votetrace = M('tvote_trace');
        /*
         * $result=
         * $votetrace->where(array('ip'=>$ip,'label_id'=>$batch_id))->select();
         * if($result){ $error_msg = '谢谢您的参与，请勿重复投票！'; $this->error($error_msg);
         * }
         */
        $vote_flag = cookie('vote_' . $this->id);
        if ($vote_flag == 1) {
            $error_msg = '谢谢您的参与，请勿重复投票！';
            $this->error($error_msg);
        }
        
        $column_arr['add_time'] = date('YmdHis');
        $column_arr['label_id'] = $batch_id;
        $column_arr['node_id'] = $node_id;
        $column_arr['ip'] = $ip;
        $column_arr['channel_id'] = $this->channel_id;
        $column_arr['channel_name'] = M('tchannel')->where(
            array(
                'id' => $this->channel_id))->getField('name');
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $bm_seq_id = $votetrace->add($column_arr);
        
        /*
         * //若开启了上传 if(in_array('13', $query_arr, true)){
         * import('ORG.Net.UploadFile'); $upload = new UploadFile();// 实例化上传类
         * $upload->maxSize = 1024*1024*3; $upload->savePath =
         * C('UPLOAD').'/bm_upload/'.$this->batch_id.'/';// 设置附件
         * $upload->supportMulti=false;
         * $upload->allowExts=array('gif','jpg','jpeg','bmp','png');
         * //$upload->thumb=true; //$upload->thumbPrefix='m';
         * //$upload->thumbMaxWidth=150; //$upload->thumbMaxHeight=150;
         * //$upload->thumbRemoveOrigin=true; if(!$upload->upload()) {//
         * 上传错误提示错误信息 //$this->errormsg = $upload->getErrorMsg();
         * $tranDb->rollback(); $this->ajaxReturn("error","图片上传失败！",0); exit;
         * }else{// 上传成功 获取上传文件信息 $info = $upload->getUploadFileInfo();
         * //以报名序号重命名 rename($info[0]['savepath'].$info[0]['savename'],
         * $info[0]['savepath'].$bm_seq_id.'.'.$info[0]['extension']); } }
         */
        // 问题表
        $tmodel = M('tvote_question');
        $row_q = $tmodel->where(
            array(
                'label_id' => $batch_id))->select();
        $v_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E', 
            '6' => 'F', 
            '7' => 'G', 
            '8' => 'H', 
            '9' => 'I', 
            '10' => 'J', 
            '11' => 'K', 
            '12' => 'L', 
            '13' => 'M', 
            '14' => 'N', 
            '15' => 'O', 
            '16' => 'P', 
            '17' => 'Q', 
            '18' => 'R', 
            '19' => 'S', 
            '20' => 'T', 
            '21' => 'U', 
            '22' => 'V', 
            '23' => 'W', 
            '24' => 'X', 
            '25' => 'Y', 
            '26' => 'Z');
        
        if ($row_q) {
            foreach ($row_q as $q_v) {
                
                $r = I('radio-' . $q_v['id']); // 单选框
                $c = I('checkbox-' . $q_v['id']); // 多选框
                $t = I('textarea-' . $q_v['id']); // 文本
                if ($r) {
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => $v_arr[$r], 
                        'add_time' => date('Ymd'));
                } elseif ($c) {
                    $in_arr = array();
                    foreach ($c as $in_v) {
                        $in_arr[] = $v_arr[$in_v];
                    }
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => implode('-', $in_arr), 
                        'add_time' => date('Ymd'));
                } elseif ($t != '') {
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => $t, 
                        'add_time' => date('Ymd'));
                } else {
                    $tranDb->rollback();
                    $this->ajaxReturn("error", "请完善页面所有信息！", 0);
                }
                $qsmodel = M('tvote_question_stat');
                $query = $qsmodel->add($q_arr);
            }
        }
        
        // 粉丝添加
        if (! empty($column_arr['mobile'])) {
            
            $data = array(
                'name' => $column_arr['true_name'], 
                'phone_no' => $column_arr['mobile'], 
                'age' => $column_arr['mobile']);
            if ($column_arr['sex'] == '男') {
                $data['sex'] = '1';
            } elseif ($column_arr['sex'] == '女') {
                $data['sex'] = '2';
            }
        }
        if ($bm_seq_id !== false && $query !== false) {
            $tranDb->commit();
            cookie('vote_' . $this->id, '1', 2592000); // 指定cookie保存时间 1月
            if ($is_cj == '1') {
                $this->ajaxReturn("success_cj", "投票成功！", 1);
            } else {
                // 唐山非标信息
                if ($this->node_id == C("tangshan.node_id")) {
                    $tangshan_data = array(
                        "m_id" => $this->batch_id, 
                        "node_id" => $this->node_id);
                    $res_tangshan = M("tfb_tangshan_pingan")->where(
                        $tangshan_data)->find();
                    if ($res_tangshan['tangshan_url']) {
                        $map = array(
                            "tangshan_url" => $res_tangshan['tangshan_url'], 
                            "info" => "投票成功！");
                        $this->ajaxReturn("success", $map, 1);
                    }
                }
                $this->ajaxReturn("success", "投票成功！", 1);
            }
        } else {
            $tranDb->rollback();
            $this->ajaxReturn("error", "投票失败！", 0);
        }
    }
}