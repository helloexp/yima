<?php

/**
 * 会员管理
 */
class MemberAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    private $node_weixin_code;

    private $node_wx_id;

    public $node_id_wc = '00000000';
    // 旺财运营平台机构号
    // DF调研活动类型
    public $BATCH_TYPE = '1004';

    const CHANNEL_TYPE_WX = '4';
    // 微信公众平台
    const CHANNEL_SNS_TYPE_WX = '41';
    // 微信公众平台发布类型
    private $status_arr = array(
        '0' => '未处理', 
        '1' => '处理中', 
        '2' => '处理成功', 
        '3' => '部分成功');

    public function _initialize() {
        parent::_initialize();
        $info = D('tweixin_info')->where(
            "node_id = '{$this->node_id}' and status = '0'")->find();
        $this->node_weixin_code = $info['weixin_code'];
        $this->node_wx_id = $info['node_wx_id'];
        $this->assign('status_arr', $this->status_arr);
        C(include (CONF_PATH . 'Weixin/config.php'));
        C(include (CONF_PATH . 'Label/config.php'));
    }

    /**
     * 会员列表
     */
    public function index() {
        $post = I('request.');
        $map = array();
        $id = I('id', '', 'trim,mysql_real_escape_string');
        $mobile = I('mobile', '', 'trim,mysql_real_escape_string');
        $name = I('name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('begin_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = $id;
        }
        if ($mobile != '') {
            $map['a.mobile'] = $mobile;
        }
        if ($name != '') {
            $map['a.name'] = $name;
        }
        
        if ($begin_time != '') {
            $map['a.birthday'][] = array(
                'EGT', 
                $begin_time);
        }
        if ($begin_time != '') {
            $map['a.birthday'][] = array(
                'ELT', 
                $end_time);
        }
        $map['_string'] = " ifnull(a.mobile,'')!='' ";
        if (! empty($post['horoscope_list'])) {
            $map['a.horoscope'] = array(
                'in', 
                $post['horoscope_list']);
        }
        $sql1 = M()->table("tfb_df_member a")
            ->where($map)
            ->field(
            "a.id,a.name,a.mobile,a.point,a.birthday,a.source,a.horoscope,'0' as tablename")
            ->buildSql();
        $sql2 = M()->table("tfb_df_member_import a")
            ->where(array_merge(array(
            'a.status' => 0), $map))
            ->field(
            "a.id,a.name,a.mobile,a.point,a.birthday,a.source,a.horoscope,'1' as tablename")
            ->buildSql();
        
        if ($id != '') {
            $union_sql = "($sql1) a";
        } else {
            $union_sql = "($sql1 union all $sql2) a";
        }
        
        import("ORG.Util.Page");
        $count = M()->table($union_sql)
            ->where($map)
            ->count();
        
        import("ORG.Util.Page");
        $p = new Page($count, 50);
        $page = $p->show();
        $list = M()->table($union_sql)
            ->join("tchannel b ON b.id=a.source and b.type=8 and sns_type=81")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->field(
            'a.id,a.name,a.mobile,a.point,a.birthday,a.source,a.tablename,b.name as source_name')
            ->select();
        $horoscope = getConstellation(null, null, 2);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('horoscope', $horoscope);
        $this->assign('post', $post);
        
        $this->display('Member/index');
    }

    /**
     * 会员查询下载
     */
    public function download() {
        $post = I('request.');
        $map = array();
        $id = I('id', '', 'trim,mysql_real_escape_string');
        $mobile = I('mobile', '', 'trim,mysql_real_escape_string');
        $name = I('name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('begin_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = $id;
        }
        if ($mobile != '') {
            $map['a.mobile'] = $mobile;
        }
        if ($name != '') {
            $map['a.name'] = $name;
        }
        
        if ($begin_time != '') {
            $map['a.birthday'][] = array(
                'EGT', 
                $begin_time);
        }
        if ($begin_time != '') {
            $map['a.birthday'][] = array(
                'ELT', 
                $end_time);
        }
        $map['_string'] = " ifnull(a.mobile,'')!='' ";
        if (! empty($post['horoscope_list'])) {
            $map['a.horoscope'] = array(
                'in', 
                $post['horoscope_list']);
        }
        $sql1 = M()->table("tfb_df_member a")
            ->where($map)
            ->field(
            'a.id,a.name,a.mobile,a.point,a.birthday,a.source,a.sex,a.horoscope')
            ->buildSql();
        $sql2 = M()->table("tfb_df_member_import a")
            ->where(array_merge(array(
            'a.status' => 0), $map))
            ->field(
            'a.id,a.name,a.mobile,a.point,a.birthday,a.source,a.sex,a.horoscope')
            ->buildSql();
        $count = M()->table("($sql1 union all $sql2) a")
            ->where($map)
            ->count();
        
        if ($count == 0) {
            $this->error('无查寻结果下载！');
        }
        
        $fileName = date("YmdHis") . "-会员列表.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "会员ID,姓名,性别,年龄,手机号码,积分,职业,收入,生日,会员来源\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        $maxlen = ceil($count / 5000);
        $papa = 5000;
        for ($j = 1; $j <= $maxlen; $j ++) {
            $pege = ($j - 1) * $papa;
            $list = M()->table("($sql1 union all $sql2) a")
                ->where($map)
                ->limit($pege, $papa)
                ->order('a.id desc')
                ->field('a.*')
                ->select();
            
            if ($list) {
                foreach ($list as $vo) {
                    $vo['age'] = date("Y") - (int) substr($vo['birthday'], 0, 4);
                    /* -1未知不明0男1女 */
                    if ($vo['sex'] == '0') {
                        $vo['sex'] = "男";
                    } elseif ($vo['sex'] == '1') {
                        $vo['sex'] = "女";
                    } else {
                        $vo['sex'] = "未知";
                    }
                    iconv_arr('utf-8', 'gbk', $vo);
                    echo "=\"{$vo['id']}\",=\"{$vo['name']}\",=\"{$vo['sex']}\",=\"{$vo['age']}\",=\"{$vo['mobile']}\",=\"{$vo['point']}\",=\"{$vo['career']}\",=\"{$vo['income']}\",=\"{$vo['birthday']}\",=\"{$vo['source']}\"" .
                         "\r\n";
                }
            }
        }
    }

    /**
     * 会员导入失败日志下载
     */
    public function download_log() {
        $post = I('request.');
        $map = array();
        $id = I('id', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.batch_id'] = $id;
        }
        
        $count = M()->table("tfb_df_member_import_log a")
            ->where($map)
            ->count();
        
        if ($count == 0) {
            $this->error('无查寻结果下载！');
        }
        
        $fileName = date("YmdHis") . "-会员导入失败日志.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "会员ID,姓名,性别,年龄,手机号码,积分,职业,收入,生日,日志备注\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        $maxlen = ceil($count / 5000);
        $papa = 5000;
        for ($j = 1; $j <= $maxlen; $j ++) {
            $pege = ($j - 1) * $papa;
            $list = M()->table("tfb_df_member_import_log a")
                ->where($map)
                ->limit($pege, $papa)
                ->order('a.id desc')
                ->field('a.*')
                ->select();
            if ($list) {
                foreach ($list as $vo) {
                    $vo['age'] = date("Y") - (int) substr($vo['birthday'], 0, 4);
                    /* -1未知不明0男1女 */
                    if ($vo['sex'] == '0') {
                        $vo['sex'] = "男";
                    } elseif ($vo['sex'] == '1') {
                        $vo['sex'] = "女";
                    } else {
                        $vo['sex'] = "未知";
                    }
                    iconv_arr('utf-8', 'gbk', $vo);
                    echo "=\"{$vo['id']}\",=\"{$vo['name']}\",=\"{$vo['sex']}\",=\"{$vo['age']}\",=\"{$vo['mobile']}\",=\"{$vo['point']}\",=\"{$vo['career']}\",=\"{$vo['income']}\",=\"{$vo['birthday']}\",=\"{$vo['remark']}\"" .
                         "\r\n";
                }
            }
        }
    }

    /**
     * 会员批量导入
     */
    public function member_import() {
        $model = M('tfb_df_member_import_file');
        
        $map = array(
            '_string' => '1=1');
        $begin_time = I('begin_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        
        if ($begin_time != '') {
            $map['_string'] .= " and substr(add_time, 1, 8) >= '{$begin_time}'";
        }
        if ($end_time != '') {
            $map['_string'] .= " and substr(add_time, 1, 8) <= '{$end_time}'";
        }
        
        import("ORG.Util.Page");
        $count = $model->where($map)->count();
        
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        $list = $model->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display('Member/member_import');
    }

    public function import() {
        $this->display('Member/import');
    }

    /**
     * 批量导入文件上传
     */
    public function import_upload() {
        set_time_limit(0);
        $maxSize = 1024 * 1024 * 100;
        $allowExts = array(
            'csv', 
            'txt');
        $m_ = M('tfb_df_member_import_file');
        
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $maxSize;
        $upload->allowExts = $allowExts;
        $upload->savePath = APP_PATH . '/Upload/import/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        $t1 = time();
        
        // 上传错误提示错误信息
        if (! $upload->upload()) {
            $this->ajaxReturn(
                array(
                    'info' => $upload->getErrorMsg(), 
                    'status' => 1), 'json');
            exit();
        } // 上传成功 获取上传文件信息
        
        $info = $upload->getUploadFileInfo();
        $md5 = $info[0]['hash'];
        $query = $m_->where(array(
            'file_md5' => $md5))->find();
        if ($query) {
            @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
            exit(
                json_encode(
                    array(
                        'info' => '已上传过同样的文件，为了防止您提交错误的文件，请改动后再重新上传', 
                        'status' => 0)));
        }
        
        // 创建批次表==开始
        $data = array(
            'path' => '/Upload/import/' . $info[0]['savename'], 
            'total_num' => 0, 
            'succ_num' => 0, 
            'fail_num' => 0, 
            'status' => '-', 
            'add_time' => date('YmdHis'), 
            'file_md5' => $md5, 
            'remark' => I('remark', '', 'mysql_real_escape_string'));
        $batch_id = $m_->add($data);
        if ($batch_id === false) {
            exit(
                json_encode(
                    array(
                        'info' => '文件上传失败，请重试！', 
                        'status' => 0)));
        }
        // 创建批次表==结束
        // 行数是否大于1000
        $handle = fopen(APP_PATH . '/Upload/import/' . $info[0]['savename'], 
            'r');
        // $handle = fopen(APP_PATH . '/Upload/import/14309718500955.csv', 'r');
        $sum_arr = array();
        $row = 0;
        $pathinfo = pathinfo($info[0]['savename']);
        $split = $pathinfo['extension'] == 'txt' ? "\t" : ",";
        
        $i = 0;
        $success_i = 0;
        $false_i = 0;
        $model_d = M('tfb_df_member_import');
        $model_l = M('tfb_df_member_import_log');
        $msg = array();
        
        while ($data = fgetcsv($handle, 5000, $split)) {
            $i ++;
            $str = print_r($data, true);
            iconv_arr('gbk', 'utf-8', $data);
            $num = count($data);
            if ($num != 8) {
                if ($i == 1) {
                    $msg[] = '表头不对！' . $str;
                } else {
                    $msg[] = "第{$i}行数据格式不对。";
                }
                break;
            }
            if ($i == 1) {
                continue;
            }
            
            $count = M('tfb_df_member_import')->where(
                array(
                    'mobile' => $data[3]))->count();
            $count0 = M('tfb_df_member')->where(
                array(
                    'mobile' => $data[3]))->count();
            
            $t_data = array();
            $t_data["batch_id"] = $data[0];
            $t_data["openid"] = $data[1];
            $t_data["name"] = $data[2];
            $t_data["mobile"] = $data[3];
            $t_data["birthday"] = $data[4];
            $t_data["city_code"] = $data[5];
            $t_data["point"] = $data[6];
            $t_data["status"] = ((int) $count0 > 0) ? 1 : 0;
            
            $is_date = strtotime($t_data['birthday']) ? true : false;
            if ($is_date === false) {
                // $msg[] = "第{$i}行数据-日期格式不对.";
                $t_data['remark'] = "第{$i}行数据-日期格式不对.";
                $t_data["batch_id"] = $batch_id;
                $log = $model_l->add($t_data);
                $false_i ++;
                continue;
            }
            $t_data['source'] = "手动导入";
            $t_data['horoscope'] = getConstellation(
                substr($t_data['birthday'], 4, 2), 
                substr($t_data['birthday'], 6, 2), 1);
            $t_data['age'] = date("Y") - (int) substr($t_data['birthday'], 0, 4);
            /* 手机号已经注册，则不能导入 手机号已经导入，则不能导入 */
            if ((int) $count > 0) {
                $t_data['remark'] = "手机号已经导入";
                $t_data["batch_id"] = $batch_id;
                $log = $model_l->add($t_data);
                $false_i ++;
                continue;
            }
            $flag = $model_d->add($t_data);
            if ($flag === false) {
                $t_data['remark'] = "sql语句执行失败";
                $t_data["batch_id"] = $batch_id;
                $log = $model_l->add($t_data);
                $false_i ++;
                // $msg[] = "第{$i}行的手机号存在重复的记录！";
                break;
            } else {
                $success_i ++;
                /* 用户已经注册， 第一次导入 积分需要加上 */
                if ((int) $count == 0 and (int) $count0 > 0) {
                    $member_info = M('tfb_df_member')->where(
                        array(
                            'mobile' => $data[3]))->find();
                    $member_data = array();
                    $member_data['point'] = (int) $member_info['point'] +
                         (int) $data[6];
                    $flag0 = M()->table("tfb_df_member")
                        ->where("mobile = '{$data[3]}'")
                        ->save($member_data);
                    if ($flag0 === false) {} else {
                        /* 记录积分变化log */
                        $import_save_new_4 = array(
                            'openid' => $member_info['openid'], 
                            'type' => 4,  // 会员增加积分
                            'before_num' => $member_info['point'], 
                            'change_num' => $data[6], 
                            'after_num' => $data[6] +
                                 intval($member_info['point']), 
                                'relation_id' => $flag, 
                                'trace_time' => date('YmdHis', time()), 
                                'remark' => "原始会员积分同步（原手机号" . $data[3] . "）");
                        $res_import_a = M("tfb_df_point_trace")->add(
                            $import_save_new_4);
                    }
                }
            }
        }
        fclose($handle);
        if ($i == 1) {
            $msg[] = "一条数据都没有！";
        }
        if ($msg) {
            /*
             * @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
             * $m_->delete($batch_id); $model_d->where(array('batch_id' =>
             * $batch_id))->delete();
             */
            // exit(json_encode(array('info' => implode('<br>', $msg), 'status'
            // => 0)));
        }
        $status = 0;
        if ($i - 1 != $success_i) {
            $status = 3;
        }
        
        $map = array(
            'id' => $batch_id);
        $m_->where($map)->save(
            array(
                'total_num' => $i - 1, 
                'status' => $status, 
                'succ_num' => $success_i, 
                'fail_num' => $false_i));
        exit(
            json_encode(
                array(
                    'info' => '导入成功，等待后台处理！', 
                    'status' => 1)));
    }

    /**
     * 会员添加
     */
    public function add() {
        if (IS_POST) {
            $req = I('post.');
            
            $member_data = array(
                'name' => $req['name'], 
                'mobile' => $req['mobile'], 
                'birthday' => $req['birthday'], 
                'point' => $req['point'], 
                'add_time' => date("YmdHis"));
            $member_data['horoscope'] = getConstellation(
                substr($member_data['birthday'], 4, 2), 
                substr($member_data['birthday'], 6, 2), 1);
            try {
                $count = M('tfb_df_member_import')->where(
                    array(
                        'mobile' => $member_data['mobile']))->count();
                $count0 = M('tfb_df_member')->where(
                    array(
                        'mobile' => $member_data['mobile']))->count();
                if ($count + $count0 > 0) {
                    throw new Exception("手机号码已存在！");
                }
                if ($member_data['name'] != '' and $member_data['mobile'] != '') {
                    $b_id = M('tfb_df_member_import')->add($member_data);
                    if ($b_id === false) {
                        throw new Exception("手机号码已存在！");
                    } else {
                        $return = array(
                            'info' => '会员添加成功！', 
                            'list_url' => U('index'), 
                            'status' => 1);
                        $this->ajaxReturn($return, 'json');
                    }
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $this->display('Member/add');
        }
    }

    /**
     * 会员编辑
     */
    public function edit() {
        $id = I('id', 0, 'intval');
        if ($id == 0) {
            $this->error('参数错误0！');
        }
        
        $map = array(
            'id' => $id);
        if (I('tablename') == '0') {
            $tablename = 'tfb_df_member';
        }
        if (I('tablename') == '1') {
            $tablename = 'tfb_df_member_import';
        }
        
        $info = M()->table($tablename)
            ->where($map)
            ->field('*')
            ->find();
        if (! $info) {
            $this->error('参数错误1！');
        }
        if (IS_POST) {
            $req = I('post.');
            $member_data = array(
                'name' => $req['name'], 
                'sex' => (string) $req['sex'], 
                'age' => date("Y") - (int) substr($req['birthday'], 0, 4), 
                // 'mobile' => (string) $req['mobile'],
                'birthday' => $req['birthday'], 
                'horoscope' => getConstellation(substr($req['birthday'], 4, 2), 
                    substr($req['birthday'], 6, 2), 1), 
                'city_code' => $req['city_code']);
            if ((int) $req['tablename'] == 0) {
                $tablename = 'tfb_df_member';
            }
            if ((int) $req['tablename'] == 1) {
                $tablename = 'tfb_df_member_import';
            }
            
            $flag = M()->table($tablename)
                ->where("id = {$info['id']}")
                ->save($member_data);
            // echo M()->table($tablename)->getLastSql();
            if ($flag === false) {
                $this->error('会员编辑失败！');
            }
            node_log('DF手动编辑会员', print_r($member_data, true));
            $this->success('会员编辑成功！');
        }
        $qsmodel = M('tfb_df_question_answer');
        $qslist = $qsmodel->where("mobile ='" . $info['mobile'] . " '")->select();
        $info['age'] = date("Y") - (int) substr($info['birthday'], 0, 4);
        $this->assign('info', $info);
        $this->assign('qslist', $qslist);
        $horoscope = getConstellation(null, null, 2);
        $this->assign('horoscope', $horoscope);
        $this->display('Member/edit');
    }

    /**
     *
     * @param $data = array('insure_years', 'last_year_acc_num')
     * @return int
     */
    public function _member_level($data) {
        $level = 0;
        if ($data['insure_years'] >= 3) {
            $level = 3;
        } else if ($data['insure_years'] >= 1) {
            if ($data['last_year_acc_num'] > 0) {
                $level = 1;
            } else {
                $level = 2;
            }
        }
        return $level;
    }

    /**
     *
     * @param $data = array('c01_start_date')
     * @return int
     */
    public function _member_c01_flag($data) {
        return $data != '' ? '1' : '0';
        return substr($data['c01_start_date'], 0, 4) == date('Y') ? 1 : 0;
    }

    /**
     *
     * @param $data = array('c51_start_date')
     * @return int
     */
    public function _member_c51_flag($data) {
        return $data != '' ? '1' : '0';
        return substr($data['c51_start_date'], 0, 4) == date('Y') ? 1 : 0;
    }

    /**
     * 批量变更积分
     */
    public function wechatinfo() {
        $type = M('tweixin_info')->field('account_type')
            ->where("node_id = '{$this->node_id}'")
            ->find();
        $type = $type['account_type'];
        
        // 查询服务号本月是否已经发满
        if (4 == $type || 3 == $type) {
            $mass_premonth = C('WEIXIN_MASS_PREMONTH');
            $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
            $count = M('twx_msgbatch')->where(
                "node_id = '{$this->node_id}' and node_wx_id = '{$this->node_wx_id}' and add_time like '" .
                     date('Ym') . "%'")->count();
        }
        
        // 查询订阅号今日是否已经发满
        if (1 == $type || 2 == $type) {
            $map = array(
                'node_wx_id' => $this->node_wx_id, 
                'add_time' => array(
                    'like', 
                    date('Ymd') . '%'));
            $flag = M('twx_msgbatch')->field('add_time')
                ->where($map)
                ->find();
            $count = $flag ? 0 : 1;
        }
        $horoscope = getConstellation(null, null, 2);
        $this->assign('horoscope', $horoscope);
        $this->assign('type', $type);
        $this->assign('mass_premonth', $mass_premonth);
        $this->assign('count', $count);
        $this->assign('sent_num', $count);
        $this->display('Member/wechatinfo');
    }

    /* 积分兑换设置 */
    public function pointset() {
        $sync_flag = M('tsystem_param')->where(
            "param_name ='DF_MONEY_TO_POINT'")->find();
        if (! $sync_flag) {
            $map = array(
                'param_name' => 'DF_MONEY_TO_POINT', 
                'param_value' => 0, 
                'comment' => 'DF消费1元获取多少个积分');
            $rs = M('tsystem_param')->add($map);
            $sync_flag = M('tsystem_param')->where(
                "param_name ='DF_MONEY_TO_POINT'")->find();
        }
        if ($this->isPost()) {
            $post = I('post.');
            $sync_flag_save['param_value'] = $post[param_value];
            // var_dump($sync_flag_save);
            $rs = M('tsystem_param')->where("param_name ='DF_MONEY_TO_POINT'")->save(
                $sync_flag_save);
            if (! $rs) {
                // die(M()->_sql());
                $return = array(
                    'info' => '保存失败！可能是数据没有改变。', 
                    'list_url' => U('pointset'), 
                    'status' => 0);
                echo json_encode($return);
                exit();
            } else {
                $return = array(
                    'info' => '设置成功！', 
                    'list_url' => U('pointset'), 
                    'status' => 1);
                echo json_encode($return);
                exit();
            }
            $sync_flag = M('tsystem_param')->where(
                "param_name ='DF_MONEY_TO_POINT'")->find();
        }
        $this->assign('row', $sync_flag);
        $this->display('Member/pointset');
    }

    /**
     * 查询批量发送用户
     */
    public function batch_send_count() {
        $post = I('request.');
        $type = M('tweixin_info')->field('account_type')
            ->where("node_id = '{$this->node_id}'")
            ->find();
        $type = $type['account_type'];
        
        // 查询服务号本月是否已经发满
        if (4 == (int) $type || 3 == (int) $type) {
            $mass_premonth = C('WEIXIN_MASS_PREMONTH');
            $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
            $count = M('twx_msgbatch')->where(
                "node_id = '{$this->node_id}' and node_wx_id = '{$this->node_wx_id}' and add_time like '" .
                     date('Ym') . "%'")->count();
            
            if ((int) $count >= $mass_premonth) {
                $this->error('当月已达到群发次数限制！');
            }
        }
        
        // 查询订阅号今日是否已经发满
        if (1 == (int) $type || 2 == (int) $type) {
            $time = M('twx_msgbatch')->field('add_time')
                ->where(
                array(
                    'node_wx_id' => $this->node_wx_id))
                ->order('add_time Desc')
                ->find();
            $time = dateformat($time['add_time'], 'Ymd');
            if (date('Ymd') == $time) {
                $this->error('今日已达到群发次数限制！');
            }
        }
        // 根据条件查询需要批量发送的粉丝
        $model = M("tfb_df_member");
        if ($post['select_type'] == 0) {
            $count0 = $model->alias("t")->where(
                "date_format(t.birthday,'%m') ='" . sprintf('%02s', I('month')) .
                     " ' and date_format(t.birthday,'%d')>='" .
                     sprintf('%02s', I('start_day')) .
                     "' and date_format(t.birthday,'%d')<='" .
                     sprintf('%02s', I('end_day')) . "'   ")->count();

            $count1 = M('tfb_df_member_import t')->where(
                "date_format(t.birthday,'%m') ='" . sprintf('%02s', I('month')) .
                     " ' and date_format(t.birthday,'%d')>='" .
                     sprintf('%02s', I('start_day')) .
                     "' and date_format(t.birthday,'%d')<='" .
                     sprintf('%02s', I('end_day')) . "'   ")->count();
            $count = $count0 + $count1;
        }
        if ($post['select_type'] == 1) {
            $map = array();
            if (! empty($post['horoscope_list'])) {
                $map['t.horoscope'] = array(
                    'in', 
                    $post['horoscope_list']);
            }
            $count = $model->alias("t")->where($map)->count();
        }
        
        if ($count == 0 || ! $count) {
            $this->ajaxReturn($count, '没有找到对应的用户！', 0);
        }  /*
           * elseif($send_count > $mass_maxnum){
           * $this->ajaxReturn($count,'群发用户不能超过'.$mass_maxnum,2); }
           */
else {
            $this->ajaxReturn($count, "查找成功！", 1);
        }
    }

    /**
     * 批量发送
     */
    public function batch_send() {
        $post = I('request.');
        $type = M('tweixin_info')->field('account_type')
            ->where("node_id = '{$this->node_id}'")
            ->find();
        $type = $type['account_type'];
        // 查询服务号本月是否已经发满
        if (4 == (int) $type || 3 == (int) $type) {
            $mass_premonth = C('WEIXIN_MASS_PREMONTH');
            $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
            $count = M('twx_msgbatch')->where(
                "node_id = '{$this->node_id}' and node_wx_id = '{$this->node_wx_id}' and add_time like '" .
                     date('Ym') . "%'")->count();
        }
        
        // 查询订阅号今日是否已经发满
        if (1 == (int) $type || 2 == (int) $type) {
            $map = array(
                'node_wx_id' => $this->node_wx_id, 
                'add_time' => array(
                    'like', 
                    date('Ymd') . '%'));
            $flag = M('twx_msgbatch')->field('add_time')
                ->where($map)
                ->find();
            $count = $flag ? 0 : 1;
        }
        
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id, name', 
            true);
        if ($this->isPost()) {
            set_time_limit(0);
            if (4 == (int) $type || 3 == $type) {
                if ((int) $count >= $mass_premonth) {
                    $this->error('当月已达到群发次数限制！');
                }
            }
            
            if (1 == (int) $type || 2 == (int) $type) {
                if ((int) $count == 0) {
                    $this->error('今日已达到群发次数限制！');
                }
            }
            // 数据校验
            $reply_type = I('reply_type', 0, 'trim,intval');
            $reply_text = I('reply_text', 0, 'trim');
            $material_id = I('material_id', 0, 'intval');
            
            // 根据条件查询需要批量发送的粉丝
            $where = array(
                't.node_id' => $this->node_id, 
                't.node_wx_id' => $this->node_wx_id, 
                't.subscribe' => '1');
            
            $model = M("tfb_df_member");
            if ($post['select_type'] == 0) {
                $fans_count = $model->alias("t")->where(
                    "date_format(t.birthday,'%m') ='" .
                         sprintf('%02s', I('month')) .
                         " ' and date_format(t.birthday,'%d')>='" .
                         sprintf('%02s', I('start_day')) .
                         "' and date_format(t.birthday,'%d')<='" .
                         sprintf('%02s', I('end_day')) . "'  ")->count();
            }
            if ($post['select_type'] == 1) {
                $map = array();
                if (! empty($post['horoscope_list'])) {
                    $map['t.horoscope'] = array(
                        'in', 
                        $post['horoscope_list']);
                }
                $fans_count = $model->alias("t")->where($map)->count();
            }
            $fans_list = array();
            // if(!$is_to_all) {
            if ($post['select_type'] == 0) {
                $fans_list = $model->alias("t")->where(
                    "date_format(t.birthday,'%m') ='" .
                         sprintf('%02s', I('month')) .
                         " ' and date_format(t.birthday,'%d')>='" .
                         sprintf('%02s', I('start_day')) .
                         "' and date_format(t.birthday,'%d')<='" .
                         sprintf('%02s', I('end_day')) . "'  ")->getField(
                    't.openid id, t.openid', true);
            }
            if ($post['select_type'] == 1) {
                $map = array();
                if (! empty($post['horoscope_list'])) {
                    $map['t.horoscope'] = array(
                        'in', 
                        $post['horoscope_list']);
                }
                $fans_list = $model->alias("t")->where($map)->getField(
                    't.openid id, t.openid', true);
            }
            
            $fans_list = array_values($fans_list);
            
            if ($fans_count == 0) {
                $this->error('没有找到查询条件对应的用户！');
            }
            /*
             * if($fans_count > $mass_maxnum){
             * $this->error('群发用户不能超过'.$mass_maxnum); }
             */
            
            // 图文素材
            if ($reply_type == 2) {
                $material = M('twx_material')->find($material_id);
                if (! $material || $material['node_id'] != $this->node_id)
                    $this->error('素材错误');
            }
            
            if ($reply_type == 2) {
                // 多图文处理
                if ($material['material_type'] == 2) {
                    $list = M('twx_material')->where(
                        "id= '{$material_id}' or parent_id = '{$material_id}'")->select();
                } else {
                    $list = array(
                        $material);
                }
                
                $arr = array();
                foreach ($list as $info) {
                    $arr[] = array(
                        "title" => $info['material_title'], 
                        "description" => $info['material_summary'], 
                        "url" => $info['material_link'], 
                        "picurl" => $info['material_img']);
                }
                $content = array(
                    'Articles' => $arr);
                $reply_text = unicodeDecode(json_encode($content));
            }
            
            M()->startTrans();
            // 插入批量发送主表
            $data = array(
                'node_wx_id' => $this->node_wx_id, 
                'user_id' => $this->user_id, 
                'node_id' => $this->node_id, 
                'total_count' => $fans_count, 
                'add_time' => date('YmdHis'), 
                'msg_type' => $reply_type, 
                'msg_info' => $reply_text);
            
            $batch_msg_id = M('twx_msgbatch')->add($data);
            if ($batch_msg_id === false) {
                M()->rollback();
                log::write("插入批量发送表错误！语句：" . M()->_sql());
                $this->error('批量发送初始化失败！');
            }
            
            /* 保存发送历史记录 */
            $data_tfb_df_wechatinfo_log = array(
                'batch_msg_id' => $batch_msg_id, 
                'select_type' => I('select_type'), 
                'horoscope' => implode(",", $post['horoscope_list']), 
                'month' => I('month'), 
                'msg_type' => $reply_type, 
                'msg_info' => $reply_text, 
                'add_time' => date('YmdHis'), 
                'user_id' => $this->user_id);
            $tfb_df_wechatinfo_log_id = M('tfb_df_wechatinfo_log')->add(
                $data_tfb_df_wechatinfo_log);
            
            // 保存记录，如果发送失败，则删除批次记录！
            M()->commit();
            
            $wx_send = D('WeiXinSend', 'Service');
            try {
                $wx_send->init($this->node_id);
                if (0 == $reply_type) {
                    $result = $wx_send->batch_send_text(
                        array(
                            'openids' => $fans_list, 
                            'content' => $reply_text, 
                            'is_to_all' => $is_to_all));
                } else {
                    $result = $wx_send->batch_send(
                        array(
                            'material_id' => $material_id, 
                            'openids' => $fans_list, 
                            'is_to_all' => $is_to_all));
                }
                
                if (! $result) {
                    throw_exception('群发失败!');
                }
            } catch (Exception $e) {
                M('twx_msgbatch')->where("batch_id = '{$batch_msg_id}'")->delete();
                M('tfb_df_wechatinfo_log')->where(
                    "batch_msg_id = '{$batch_msg_id}'")->delete();
                $this->error('回复失败：' . $e->getMessage());
            }
            
            foreach ($result as $wx_msg_id) {
                $data = array(
                    'batch_id' => $batch_msg_id, 
                    'wx_batch_id' => $wx_msg_id);
                $flag = M('twx_msgbatch_resp')->add($data);
                if ($flag === false) {
                    log_write('DF会员群发消息批次号入表失败！' . print_r($data, true));
                }
            }
            node_log("【DF会员群发消息】批量发送录入成功！"); // 记录日志
            $this->success('发送成功！');
        } else {
            redirect(U('wechatinfo'));
        }
    }

    public function batch_send_his() {
        $dao = M("tfb_df_wechatinfo_log");
        import('ORG.Util.Page'); // 导入分页类
        $count = $dao->alias("a")->order('add_time desc')->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = $dao->alias("a")->join('tuser_info b on b.user_id = a.user_id')
            ->field('a.*, b.true_name')
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $this->assign('list', $list);
        $this->assign('page', $pageShow);
        $horoscope = getConstellation(null, null, 2);
        $this->assign('horoscope', $horoscope);
        $this->display('Member/batch_send_his');
    }

    public function point_log() {
        $id = I('id', 0, 'intval');
        if ($id == 0) {
            $this->error('参数错误0！');
        }
        
        $map = array(
            'id' => $id);
        if (I('tablename') == '0') {
            $tablename = 'tfb_df_member';
        }
        if (I('tablename') == '1') {
            $tablename = 'tfb_df_member_import';
        }
        $info = M()->table($tablename)
            ->where($map)
            ->field('*')
            ->find();
        if (! $info) {
            $this->error('参数错误1！');
        }
        $dao = M('tfb_df_point_trace');
        $list = $dao->where(
            array(
                'openid' => $info['mobile']))
            ->field('*')
            ->order('trace_time desc')
            ->select();
        if (I('tablename') == '0') {
            $list = $dao->where(
                array(
                    'openid' => $info['openid'], 
                    '_string' => "type !='3'"))
                ->field('*')
                ->order('trace_time desc')
                ->select();
        }
        if (I('tablename') == '1') {
            $list = $dao->where(
                array(
                    'openid' => $info['mobile']))
                ->field('*')
                ->order('trace_time desc')
                ->select();
        }
        $this->assign('list', $list);
        $this->assign('info', $info);
        $this->display('Member/point_log');
    }

    public function infocollection() {
        $model = M('tmarketing_info');
        $map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = I('request.');
        
        if (! empty($data['node_id']))
            $map['node_id '] = $data['node_id'];
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['status'] = $data['status'];
        }
        if ($data['start_time'] != '' && $data['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
        
        import('ORG.Util.Page');
        // 导入分页类
        $mapcount = $model->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10);
        // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show();
        // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['is_mem_batch'] = 'N';
                if ($v['is_cj'] == '1') {
                    $rule_id = M('tcj_rule')->where(
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = M('tcj_batch')->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
                $list[$k]['is_map'] = M('tquestion')->where(
                    array(
                        'label_id' => $v['id'], 
                        'type' => '4'))
                    ->limit(1)
                    ->getField('id');
            }
        }
        
        node_log("首页+市场调研");
        $arr_ = C('CHANNEL_TYPE');
        
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        
        $sync_flag = M('tfb_df_system_param')->where(
            "param_name ='DF_INFO_COLLECTION'")->find();
        $this->assign('row', $sync_flag);
        $this->display('Member/infocollection');
    }

    public function infocollection_save() {
        $data = I('request.');
        
        $sync_flag = M('tfb_df_system_param')->where(
            "param_name ='DF_INFO_COLLECTION'")->find();
        if (! $sync_flag) {
            $map = array(
                'param_name' => 'DF_INFO_COLLECTION', 
                'param_value' => '[]', 
                'comment' => 'DF采集信息配置');
            $rs = M('tfb_df_system_param')->add($map);
            $sync_flag = M('tfb_df_system_param')->where(
                "param_name ='DF_INFO_COLLECTION'")->find();
        }
        
        $sync_flag_save['param_value'] = $data['data'];
        $rs = M('tfb_df_system_param')->where(
            "param_name ='DF_INFO_COLLECTION'")->save($sync_flag_save);
        if ($rs) {
            $this->success('更新成功');
        } else {
            $this->success('更新失败');
        }
    }
}
