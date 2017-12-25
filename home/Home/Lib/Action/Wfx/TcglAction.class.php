<?php

class TcglAction extends BaseAction
{
    public $meihuiFlag = '';
    public function _initialize()
    {
        parent::_initialize();
    }

    public function beforeCheckAuth()
    {
        if ($this->wc_version == 'v4') {
            $this->_authAccessMap = '*';
        } elseif (!$this->hasPayModule('m3')) {
            redirect(U('Wfx/Index/index'));
        }
    }

    public function index()
    {
        $search = array();
        $search['m.node_id'] = $this->node_id;
        // 获取条件
        $bonus_flag = I('bonus_flag', '');
        $select_type = I('select_type', '');
        $name = I('name', '', 'trim,htmlspecialchars');
        // 根据select_type来判断是什么batch_type
        switch ($select_type) {
            case '1':
                $search['m.batch_type'] = 26;
                break;
            case '2':
                $search['m.batch_type'] = 27;
                $search['m.is_new'] = 1;
                break;
            case '3':
                $search['m.batch_type'] = 31;
                break;
            case '4':
                $search['m.batch_type'] = 27;
                $search['m.is_new'] = 2;
                break;
            default:
                $search['m.batch_type'] = array(
                    'in',
                    '26,27,31', );
                break;
        }
        if (!empty($bonus_flag)) {
            if ($bonus_flag == '1') {
                $search['w.bonus_flag'] = '1';
            }

            if ($bonus_flag == '2') {
                $search['_string'] = 'w.bonus_flag = 0 OR w.bonus_flag is NULL';
            }
        }
        empty($name) or $search['m.name'] = array(
            'like',
            '%'.$name.'%', );
        $batchTypeArray = array(
            '26' => '闪购',
            '27' => '码上买',
            '31' => '小店商品', );
        // 单品销售和旺财小店，总数量
        $marketingAmount = M()->table('tmarketing_info m')->where($search)
            ->join('twfx_goods_config w on w.m_id = m.id')
            ->count();
        // 引入分页类
        import('ORG.Util.Page');
        // 分页取数据
        $CPage = new Page($marketingAmount, 10);
        $marketingInfo = M()->table('tmarketing_info m')->field(
            'm.id AS m_id,m.is_new,m.batch_type AS batch_type,m.name AS name,w.bonus_flag AS bonus_flag,m.status AS m_status,b.status AS batch_status,m.start_time,m.end_time')
            ->where($search)
            ->join('twfx_goods_config w on w.m_id = m.id')
            ->join('tbatch_info b ON b.m_id = m.id')
            ->order('m.status asc,b.status asc,m.end_time desc')
            ->limit($CPage->firstRow.','.$CPage->listRows)
            ->select();
        $page = $CPage->show();
        $this->assign('bonus_flag', $bonus_flag);
        $this->assign('select_type', $select_type);
        $this->assign('name', $name);
        $this->assign('page', $page);
        $this->assign('batchTypeArray', $batchTypeArray);
        $this->assign('marketingInfo', $marketingInfo);
        $this->display();
    }

    public function set_tcbl()
    {
        if ($this->node_id == C('meihui.node_id')) {
            L('SALE_NAME_ONE', '门店');
            L('SALE_NAME_TWO', '钻石');
            L('SALE_NAME_TREE', '金牌');
            L('SALE_NAME_FOUR', '银牌');
        } else {
            L('SALE_NAME_ONE', '一级经销商');
            L('SALE_NAME_TWO', '二级经销商');
            L('SALE_NAME_TREE', '三级经销商');
            L('SALE_NAME_FOUR', '四级经销商');
        }
        // 只读取一次数据库，由程序处理
        $levelInfo = M('twfx_saler')->where(
            array(
                'node_id' => $this->node_id,
                'level' => array(
                    'ELT',
                    '5', ),
                'status' => array(
                    'in',
                    '3,4', ), ))->select();
        $levelOneInfo = $levelTwoInfo = $levelThreeInfo = $levelFourInfo = $levelFiveInfo = array();
        $levelSalerInfo = array();
        if (is_array($levelInfo)) {
            foreach ($levelInfo as $k => $v) {
                if ($v['role'] == '2' && $v['level'] == '1') {
                    $levelOneInfo[] = $v;
                } elseif ($v['role'] == '2' && $v['level'] == '2') {
                    $levelTwoInfo[] = $v;
                } elseif ($v['role'] == '2' && $v['level'] == '3') {
                    $levelThreeInfo[] = $v;
                } elseif ($v['role'] == '2' && $v['level'] == '4') {
                    $levelFourInfo[] = $v;
                } elseif ($v['role'] == '2' && $v['level'] == '5') {
                    $levelFiveInfo[] = $v;
                } elseif ($v['role'] == '1') {
                    $levelSalerInfo[] = $v;
                }
            }
        }
        // 获取json格式的提成设置，转化成数组
        $bonus_config_json = M('twfx_goods_config')->getFieldByM_id(I('id'),
            'bonus_config_json');
        $bonus_config_arr = json_decode($bonus_config_json, true);
        // 经销商的提成信息提取
        $agncyBonusInfo = $bonus_config_arr['agency_config']['nodes'] or
        $agncyBonusInfo = array();
        // 销售员的提成信息提取
        $salerBonusInfo = $bonus_config_arr['salers_config']['nodes'] or
        $salerBonusInfo = array();
        // 判断是否显示分别设置
        empty($bonus_config_json) or $this->assign('ishas_display', '1');
        $bonus_config_arr['level_1']['default_flag'] == '0' &&
        $this->assign('level_1_display', '1');
        $bonus_config_arr['level_2']['default_flag'] == '0' &&
        $this->assign('level_2_display', '1');
        $bonus_config_arr['level_3']['default_flag'] == '0' &&
        $this->assign('level_3_display', '1');
        $bonus_config_arr['level_4']['default_flag'] == '0' &&
        $this->assign('level_4_display', '1');
        $bonus_config_arr['level_5']['default_flag'] == '0' &&
        $this->assign('level_5_display', '1');
        $bonus_config_arr['salers_config']['default_flag'] == '0' &&
        $this->assign('saler_display', '1');
        // 统一设置的提成的值传递过去
        $this->assign('default_level_1_saler',
            $bonus_config_arr['level_1']['default_saler_percent']);
        $this->assign('default_level_1_manage',
            $bonus_config_arr['level_1']['default_manage_percent']);
        $this->assign('default_level_2_saler',
            $bonus_config_arr['level_2']['default_saler_percent']);
        $this->assign('default_level_2_manage',
            $bonus_config_arr['level_2']['default_manage_percent']);
        $this->assign('default_level_3_saler',
            $bonus_config_arr['level_3']['default_saler_percent']);
        $this->assign('default_level_3_manage',
            $bonus_config_arr['level_3']['default_manage_percent']);
        $this->assign('default_level_4_saler',
            $bonus_config_arr['level_4']['default_saler_percent']);
        $this->assign('default_level_4_manage',
            $bonus_config_arr['level_4']['default_manage_percent']);
        $this->assign('default_level_5_saler',
            $bonus_config_arr['level_5']['default_saler_percent']);
        $this->assign('default_level_5_manage',
            $bonus_config_arr['level_5']['default_manage_percent']);
        $this->assign('default_saler',
            $bonus_config_arr['salers_config']['default_saler_percent']);
        // 组合json
        $level_1_info = array();
        if (!empty($levelOneInfo)) {
            foreach ($levelOneInfo as $k1 => $v1) {
                $level_1_info['id_'.$v1['id']]['saler_percent'] = $v1['default_sale_percent'];
                $level_1_info['id_'.$v1['id']]['manage_percent'] = $v1['default_manage_percent'];
                $level_1_info['id_'.$v1['id']]['id'] = $v1['id'];
                $level_1_info['id_'.$v1['id']]['status'] = $v1['status'];
            }
        }
        $level_2_info = array();
        if (!empty($levelTwoInfo)) {
            foreach ($levelTwoInfo as $k2 => $v2) {
                $level_2_info['id_'.$v2['id']]['saler_percent'] = $v2['default_sale_percent'];
                $level_2_info['id_'.$v2['id']]['manage_percent'] = $v2['default_manage_percent'];
                $level_2_info['id_'.$v2['id']]['id'] = $v2['id'];
                $level_2_info['id_'.$v2['id']]['status'] = $v2['status'];
            }
        }
        $level_3_info = array();
        if (!empty($levelThreeInfo)) {
            foreach ($levelThreeInfo as $k3 => $v3) {
                $level_3_info['id_'.$v3['id']]['saler_percent'] = $v3['default_sale_percent'];
                $level_3_info['id_'.$v3['id']]['manage_percent'] = $v3['default_manage_percent'];
                $level_3_info['id_'.$v3['id']]['id'] = $v3['id'];
                $level_3_info['id_'.$v3['id']]['status'] = $v3['status'];
            }
        }
        $level_4_info = array();
        if (!empty($levelFourInfo)) {
            foreach ($levelFourInfo as $k4 => $v4) {
                $level_4_info['id_'.$v4['id']]['saler_percent'] = $v4['default_sale_percent'];
                $level_4_info['id_'.$v4['id']]['manage_percent'] = $v4['default_manage_percent'];
                $level_4_info['id_'.$v4['id']]['id'] = $v4['id'];
                $level_4_info['id_'.$v4['id']]['status'] = $v4['status'];
            }
        }
        $level_5_info = array();
        if (!empty($levelFiveInfo)) {
            foreach ($levelFiveInfo as $k5 => $v5) {
                $level_5_info['id_'.$v5['id']]['saler_percent'] = $v5['default_sale_percent'];
                $level_5_info['id_'.$v5['id']]['manage_percent'] = $v5['default_manage_percent'];
                $level_5_info['id_'.$v5['id']]['id'] = $v5['id'];
                $level_5_info['id_'.$v5['id']]['status'] = $v5['status'];
            }
        }
        $saler_info = array();
        if (!empty($levelSalerInfo)) {
            foreach ($levelSalerInfo as $kk => $vv) {
                $saler_info['id_'.$vv['id']]['saler_percent'] = $vv['default_sale_percent'];
                $saler_info['id_'.$vv['id']]['id'] = $vv['id'];
                $saler_info['id_'.$vv['id']]['status'] = $vv['status'];
            }
        }
        // 将默认的提成设置与后面设置的提成进行比较
        $levelOneInfoTc = array();
        if (!empty($level_1_info)) {
            foreach ($level_1_info as $kk1 => $vv1) {
                $levelOneInfoTc[$vv1['id']]['saler_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk1]['saler_percent']) ? $agncyBonusInfo[$kk1]['saler_percent'] : $vv1['saler_percent'];
                $levelOneInfoTc[$vv1['id']]['manage_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk1]['manage_percent']) ? $agncyBonusInfo[$kk1]['manage_percent'] : $vv1['manage_percent'];
                $levelOneInfoTc[$vv1['id']]['status'] = $vv1['status'];
            }
        }
        $levelTwoInfoTc = array();
        if (!empty($level_2_info)) {
            foreach ($level_2_info as $kk2 => $vv2) {
                $levelTwoInfoTc[$vv2['id']]['saler_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk2]['saler_percent']) ? $agncyBonusInfo[$kk2]['saler_percent'] : $vv2['saler_percent'];
                $levelTwoInfoTc[$vv2['id']]['manage_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk2]['manage_percent']) ? $agncyBonusInfo[$kk2]['manage_percent'] : $vv2['manage_percent'];
                $levelTwoInfoTc[$vv2['id']]['status'] = $vv2['status'];
            }
        }
        $levelThreeInfoTc = array();
        if (!empty($level_3_info)) {
            foreach ($level_3_info as $kk3 => $vv3) {
                $levelThreeInfoTc[$vv3['id']]['saler_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk3]['saler_percent']) ? $agncyBonusInfo[$kk3]['saler_percent'] : $vv3['saler_percent'];
                $levelThreeInfoTc[$vv3['id']]['manage_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk3]['manage_percent']) ? $agncyBonusInfo[$kk3]['manage_percent'] : $vv3['manage_percent'];
                $levelThreeInfoTc[$vv3['id']]['status'] = $vv3['status'];
            }
        }
        $levelFourInfoTc = array();
        if (!empty($level_4_info)) {
            foreach ($level_4_info as $kk4 => $vv4) {
                $levelFourInfoTc[$vv4['id']]['saler_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk4]['saler_percent']) ? $agncyBonusInfo[$kk4]['saler_percent'] : $vv4['saler_percent'];
                $levelFourInfoTc[$vv4['id']]['manage_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk4]['manage_percent']) ? $agncyBonusInfo[$kk4]['manage_percent'] : $vv4['manage_percent'];
                $levelFourInfoTc[$vv4['id']]['status'] = $vv4['status'];
            }
        }
        $levelFiveInfoTc = array();
        if (!empty($level_5_info)) {
            foreach ($level_5_info as $kk5 => $vv5) {
                $levelFiveInfoTc[$vv5['id']]['saler_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk5]['saler_percent']) ? $agncyBonusInfo[$kk5]['saler_percent'] : $vv5['saler_percent'];
                $levelFiveInfoTc[$vv5['id']]['manage_percent'] = self::_not_null_zero(
                    $agncyBonusInfo[$kk5]['manage_percent']) ? $agncyBonusInfo[$kk5]['manage_percent'] : $vv5['manage_percent'];
                $levelFiveInfoTc[$vv5['id']]['status'] = $vv5['status'];
            }
        }
        $levelSalerInfoTc = array();
        if (!empty($saler_info)) {
            foreach ($saler_info as $kk6 => $vv6) {
                $levelSalerInfoTc[$vv6['id']]['saler_percent'] = self::_not_null_zero(
                    $salerBonusInfo[$kk6]['saler_percent']) ? $salerBonusInfo[$kk6]['saler_percent'] : $vv6['saler_percent'];
                $levelSalerInfoTc[$vv6['id']]['manage_percent'] = self::_not_null_zero(
                    $salerBonusInfo[$kk6]['manage_percent']) ? $salerBonusInfo[$kk6]['manage_percent'] : $vv6['manage_percent'];
                $levelSalerInfoTc[$vv6['id']]['status'] = $vv6['status'];
            }
        }
        $this->assign('levelOneInfo', $levelOneInfo);
        $this->assign('levelTwoInfo', $levelTwoInfo);
        $this->assign('levelThreeInfo', $levelThreeInfo);
        $this->assign('levelFourInfo', $levelFourInfo);
        $this->assign('levelFiveInfo', $levelFiveInfo);
        $this->assign('levelSalerInfo', $levelSalerInfo);
        $this->assign('levelOneInfoTc', $levelOneInfoTc);
        $this->assign('levelTwoInfoTc', $levelTwoInfoTc);
        $this->assign('levelThreeInfoTc', $levelThreeInfoTc);
        $this->assign('levelFourInfoTc', $levelFourInfoTc);
        $this->assign('levelFiveInfoTc', $levelFiveInfoTc);
        $this->assign('levelSalerInfoTc', $levelSalerInfoTc);
        $this->assign('m_id', I('id'));
        $this->assign('target', I('target'));
        $this->display();
    }

    public function set_tcbl_post()
    {
        ini_set('memory_limit', '1024M');
        $insertInfo = array();
        $insertInfo['node_id'] = $this->node_id;
        $insertInfo['m_id'] = I('m_id');
        $insertInfo['add_time'] = date('YmdHis');
        if (I('get.post_ishas') == '1') {
            $agencyConfigInfo = array(); // 经销商的配置信息
            // 一级经销商信息
            $level_1_info = array();
            if (I('level_1') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_1_saleid = I('level_1_saleid', array());
                $level_1_sale = I('level_1_sale', array());
                $level_1_agency = I('level_1_agency', array());
                // 默认的值设置
                $level_1_info['default_flag'] = '1';
                $level_1_info['default_saler_percent'] = '';
                $level_1_info['default_manage_percent'] = '';
                // 循环赋值
                if (!empty($level_1_saleid)) {
                    foreach ($level_1_saleid as $k1 => $v1) {
                        $is_num = self::_is_num($level_1_sale[$k1]);
                        ($is_num == 1) && $this->error('一级经销中的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('一级经销中的分别设置-销售提成必须是数字');
                        $is_num = self::_is_num($level_1_agency[$k1]);
                        ($is_num == 1) && $this->error('一级经销中的分别设置-管理提成不得为空');
                        ($is_num == 2) && $this->error('一级经销中的分别设置-管理提成必须是数字');
                        $agencyConfigInfo['nodes']['id_'.$v1]['saler_percent'] = $level_1_sale[$k1];
                        $agencyConfigInfo['nodes']['id_'.$v1]['manage_percent'] = $level_1_agency[$k1];
                    }
                    $level_1_saleid = null; //todo 新增
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $level_1_info['default_flag'] = '0';
                $default_level_1_sale = I('default_level_1_sale', '',
                    'trim,htmlspecialchars');
                $default_level_1_agency = I('default_level_1_agency', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_1_sale);
                ($is_num == 1) && $this->error('一级经销中的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('一级经销中的统一设置-销售提成必须是数字');
                $is_num = self::_is_num($default_level_1_agency);
                ($is_num == 1) && $this->error('一级经销中的统一设置-管理提成不得为空');
                ($is_num == 2) && $this->error('一级经销中的统一设置-管理提成必须是数字');
                $level_1_info['default_saler_percent'] = $default_level_1_sale;
                $level_1_info['default_manage_percent'] = $default_level_1_agency;
            }
            // 二级经销商信息
            $level_2_info = array();
            if (I('level_2') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_2_saleid = I('level_2_saleid', array());
                $level_2_sale = I('level_2_sale', array());
                $level_2_agency = I('level_2_agency', array());
                // 默认的值设置
                $level_2_info['default_flag'] = '1';
                $level_2_info['default_saler_percent'] = '';
                $level_2_info['default_manage_percent'] = '';
                // 循环赋值
                if (!empty($level_2_saleid)) {
                    foreach ($level_2_saleid as $k2 => $v2) {
                        $is_num = self::_is_num($level_2_sale[$k2]);
                        ($is_num == 1) && $this->error('二级经销中的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('二级经销中的分别设置-销售提成必须是数字');
                        $is_num = self::_is_num($level_2_agency[$k2]);
                        ($is_num == 1) && $this->error('二级经销中的分别设置-管理提成不得为空');
                        ($is_num == 2) && $this->error('二级经销中的分别设置-管理提成必须是数字');
                        $agencyConfigInfo['nodes']['id_'.$v2]['saler_percent'] = $level_2_sale[$k2];
                        $agencyConfigInfo['nodes']['id_'.$v2]['manage_percent'] = $level_2_agency[$k2];
                    }

                    $level_2_saleid = null; //todo 新增
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $level_2_info['default_flag'] = '0';
                $default_level_2_sale = I('default_level_2_sale', '',
                    'trim,htmlspecialchars');
                $default_level_2_agency = I('default_level_2_agency', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_2_sale);
                ($is_num == 1) && $this->error('二级经销中的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('二级经销中的统一设置-销售提成必须是数字');
                $is_num = self::_is_num($default_level_2_agency);
                ($is_num == 1) && $this->error('二级经销中的统一设置-管理提成不得为空');
                ($is_num == 2) && $this->error('二级经销中的统一设置-管理提成必须是数字');
                $level_2_info['default_saler_percent'] = $default_level_2_sale;
                $level_2_info['default_manage_percent'] = $default_level_2_agency;
            }
            // 三级经销商信息
            $level_3_info = array();
            if (I('level_3') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_3_saleid = I('level_3_saleid', array());
                $level_3_sale = I('level_3_sale', array());
                $level_3_agency = I('level_3_agency', array());
                // 默认的值设置
                $level_3_info['default_flag'] = '1';
                $level_3_info['default_saler_percent'] = '';
                $level_3_info['default_manage_percent'] = '';
                // 循环赋值
                if (!empty($level_3_saleid)) {
                    foreach ($level_3_saleid as $k3 => $v3) {
                        $is_num = self::_is_num($level_3_sale[$k3]);
                        ($is_num == 1) && $this->error('三级经销中的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('三级经销中的分别设置-销售提成必须是数字');
                        $is_num = self::_is_num($level_3_agency[$k3]);
                        ($is_num == 1) && $this->error('三级经销中的分别设置-管理提成不得为空');
                        ($is_num == 2) && $this->error('三级经销中的分别设置-管理提成必须是数字');
                        $agencyConfigInfo['nodes']['id_'.$v3]['saler_percent'] = $level_3_sale[$k3];
                        $agencyConfigInfo['nodes']['id_'.$v3]['manage_percent'] = $level_3_agency[$k3];
                    }
                    $level_3_saleid = null; //todo 新增
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $level_3_info['default_flag'] = '0';
                $default_level_3_sale = I('default_level_3_sale', '',
                    'trim,htmlspecialchars');
                $default_level_3_agency = I('default_level_3_agency', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_3_sale);
                ($is_num == 1) && $this->error('三级经销中的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('三级经销中的统一设置-销售提成必须是数字');
                $is_num = self::_is_num($default_level_3_agency);
                ($is_num == 1) && $this->error('三级经销中的统一设置-管理提成不得为空');
                ($is_num == 2) && $this->error('三级经销中的统一设置-管理提成必须是数字');
                $level_3_info['default_saler_percent'] = $default_level_3_sale;
                $level_3_info['default_manage_percent'] = $default_level_3_agency;
            }
            // 四级经销商信息
            $level_4_info = array();
            if (I('level_4') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_4_saleid = I('level_4_saleid', array());
                $level_4_sale = I('level_4_sale', array());
                $level_4_agency = I('level_4_agency', array());
                // 默认的值设置
                $level_4_info['default_flag'] = '1';
                $level_4_info['default_saler_percent'] = '';
                $level_4_info['default_manage_percent'] = '';
                // 循环赋值
                if (!empty($level_4_saleid)) {
                    foreach ($level_4_saleid as $k4 => $v4) {
                        $is_num = self::_is_num($level_4_sale[$k4]);
                        ($is_num == 1) && $this->error('四级经销中的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('四级经销中的分别设置-销售提成必须是数字');
                        $is_num = self::_is_num($level_4_agency[$k4]);
                        ($is_num == 1) && $this->error('四级经销中的分别设置-管理提成不得为空');
                        ($is_num == 2) && $this->error('四级经销中的分别设置-管理提成必须是数字');
                        $agencyConfigInfo['nodes']['id_'.$v4]['saler_percent'] = $level_4_sale[$k4];
                        $agencyConfigInfo['nodes']['id_'.$v4]['manage_percent'] = $level_4_agency[$k4];
                    }
                    $level_4_saleid = null; //todo 新增
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $level_4_info['default_flag'] = '0';
                $default_level_4_sale = I('default_level_4_sale', '',
                    'trim,htmlspecialchars');
                $default_level_4_agency = I('default_level_4_agency', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_4_sale);
                ($is_num == 1) && $this->error('四级经销中的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('四级经销中的统一设置-销售提成必须是数字');
                $is_num = self::_is_num($default_level_4_agency);
                ($is_num == 1) && $this->error('四级经销中的统一设置-管理提成不得为空');
                ($is_num == 2) && $this->error('四级经销中的统一设置-管理提成必须是数字');
                $level_4_info['default_saler_percent'] = $default_level_4_sale;
                $level_4_info['default_manage_percent'] = $default_level_4_agency;
            }
            // 五级经销商信息
            $level_5_info = array();
            if (I('level_5') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_5_saleid = I('level_5_saleid', array());
                $level_5_sale = I('level_5_sale', array());
                $level_5_agency = I('level_5_agency', array());
                // 默认的值设置
                $level_5_info['default_flag'] = '1';
                $level_5_info['default_saler_percent'] = '';
                $level_5_info['default_manage_percent'] = '';
                // 循环赋值
                if (!empty($level_5_saleid)) {
                    foreach ($level_5_saleid as $k5 => $v5) {
                        $is_num = self::_is_num($level_5_sale[$k5]);
                        ($is_num == 1) && $this->error('五级经销中的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('五级经销中的分别设置-销售提成必须是数字');
                        $is_num = self::_is_num($level_5_agency[$k5]);
                        ($is_num == 1) && $this->error('五级经销中的分别设置-管理提成不得为空');
                        ($is_num == 2) && $this->error('五级经销中的分别设置-管理提成必须是数字');
                        $agencyConfigInfo['nodes']['id_'.$v5]['saler_percent'] = $level_5_sale[$k5];
                        $agencyConfigInfo['nodes']['id_'.$v5]['manage_percent'] = $level_5_agency[$k5];
                    }
                    $level_5_saleid = null; //todo 新增
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $level_5_info['default_flag'] = '0';
                $default_level_5_sale = I('default_level_5_sale', '',
                    'trim,htmlspecialchars');
                $default_level_5_agency = I('default_level_5_agency', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_5_sale);
                ($is_num == 1) && $this->error('五级经销中的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('五级经销中的统一设置-销售提成必须是数字');
                $is_num = self::_is_num($default_level_5_agency);
                ($is_num == 1) && $this->error('五级经销中的统一设置-管理提成不得为空');
                ($is_num == 2) && $this->error('五级经销中的统一设置-管理提成必须是数字');
                $level_5_info['default_saler_percent'] = $default_level_5_sale;
                $level_5_info['default_manage_percent'] = $default_level_5_agency;
            }
            // 销售员信息
            $salers_config = array();
            if (I('level_saler') == '1') {
                /*
                 * 分别设置 *
                 */
                // 获取表单数据,此数据为数组
                $level_saleid = I('level_saleid', array());
                $level_sale = I('level_sale', array());
                // 默认的值设置
                $salers_config['default_flag'] = '1';
                $salers_config['default_saler_percent'] = '';
                // 循环赋值
                if (!empty($level_saleid)) {
                    foreach ($level_saleid as $kk => $vv) {
                        $is_num = self::_is_num($level_sale[$kk]);
                        ($is_num == 1) && $this->error('销售员的分别设置-销售提成不得为空');
                        ($is_num == 2) && $this->error('销售员的分别设置-销售提成必须是数字');
                        $salers_config['nodes']['id_'.$vv]['saler_percent'] = $level_sale[$kk];
                    }
                    $level_saleid = null; //todo 新增
                } else {
                    $salers_config['nodes'] = '';
                }
            } else {
                /*
                 * 统一设置 *
                 */
                $salers_config['default_flag'] = '0';
                $default_level_saler = I('default_level_saler', '',
                    'trim,htmlspecialchars');
                $is_num = self::_is_num($default_level_saler);
                ($is_num == 1) && $this->error('销售员的统一设置-销售提成不得为空');
                ($is_num == 2) && $this->error('销售员的统一设置-销售提成必须是数字');
                $salers_config['default_saler_percent'] = $default_level_saler;
            }
            $result['level_1'] = $level_1_info;
            $result['level_2'] = $level_2_info;
            $result['level_3'] = $level_3_info;
            $result['level_4'] = $level_4_info;
            $result['level_5'] = $level_5_info;
            $result['agency_config'] = $agencyConfigInfo;
            $result['salers_config'] = $salers_config;
            $insertInfo['bonus_config_json'] = json_encode($result);
            $insertInfo['bonus_flag'] = '1';

            $result = null;
            $level_1_info = null;
            $level_2_info = null;
            $level_3_info = null;
            $level_4_info = null;
            $level_5_info = null;
        } else {
            $insertInfo['bonus_config_json'] = '';
            $insertInfo['bonus_flag'] = '0';
        }
        $temp_arr = M('twfx_goods_config')->where(
            array(
                'm_id' => I('m_id'), ))->select();
        if (empty($temp_arr)) {
            if (M('twfx_goods_config')->data($insertInfo)->add()) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            unset($insertInfo['m_id']);
            unset($insertInfo['node_id']);
            if (false === M('twfx_goods_config')->where(
                array(
                    'id' => $temp_arr[0]['id'], ))
                ->data($insertInfo)
                ->save()) {
                $this->error('设置失败');
            } else {
                $this->success('设置成功');
            }
        }
    }

    public function sendTc()
    {
        $map = array();
        $map['g.node_id'] = $this->node_id;
        // 查询结算方式
        $settleType = M('twfx_node_info')->getFieldByNode_id($this->node_id,
            'settle_type');
        if ($settleType == '3') {
            $settleName = '每周计算';
        } elseif ($settleType == '2') {
            $settleName = '按提领结算';
        } else {
            $settleName = '月结算';
        }

        import('ORG.Util.Page');
        $forCount = M('twfx_get_trace')->field(
            'node_id,sum(bonus_amount) as bonus_amt,start_get_date,end_get_date,deal_flag')
            ->where($map)
            ->group('start_get_date,end_get_date')
            ->order('end_get_date desc')
            ->select();
        $CPage = new Page(count($forCount), 10);
        $sendTcArr = M()->table('twfx_get_trace g')->field(
            'g.node_id,(SELECT SUM(bonus_amount) FROM twfx_get_trace
		WHERE node_id = "'.$this->node_id.
            '" AND  start_get_date= g.start_get_date AND  end_get_date = g.end_get_date ) AS bonus_amt,group_concat(g.id) as get_trace_id_arr,group_concat(t.id) as trace_id_arr,group_concat(DISTINCT(t.order_id)) as order_arr,g.start_get_date,g.end_get_date,g.deal_flag')
            ->where($map)
            ->group('g.start_get_date,g.end_get_date')
            ->join('twfx_trace t ON t.user_get_trace_id = g.id')
            ->order('g.end_get_date desc')
            ->limit($CPage->firstRow.','.$CPage->listRows)
            ->select();
        if (!empty($sendTcArr)) {
            foreach ($sendTcArr as $k => $v) {
                $sendTcArr[$k]['time'] = date('Y-m-d',
                    strtotime($v['start_get_date'])).' 至 '.
                date('Y-m-d', strtotime($v['end_get_date']));
                if ($settleType == 2) {
                    if (date('Ym', strtotime($v['start_get_date'])) == date(
                        'Ym')) {
                        $sendTcArr[$k]['display'] = 0;
                    } else {
                        $sendTcArr[$k]['display'] = 1;
                    }
                } else {
                    $sendTcArr[$k]['display'] = 1;
                }
                $orderIdArr = explode(',', $v['order_arr']); // 取出订单id的数组
                // 订单总金额
                $orderAmt = '';
                foreach ($orderIdArr as $kk => $vv) {
                    $orderAmt += M('twfx_trace')->where(
                        array(
                            'node_id' => $this->node_id, ))->getFieldByOrder_id($vv,
                        'amount');
                }
                $sendTcArr[$k]['sale_amt'] = $orderAmt;
                if ($v['deal_flag'] == '1') {
                    $sendTcArr[$k]['status'] = '已发放';
                } else {
                    $sendTcArr[$k]['status'] = '未发放';
                }
            }
        }

        $page = $CPage->show();
        $this->assign('page', $page);
        $this->assign('sendTcList', $sendTcArr);
        $this->assign('settleName', $settleName);
        $this->display();
    }

    public function ishas()
    {
        // 此处仅查看是否有数据，对sql别太认真,此处sql无任何用户
        $map = array();
        $map['node_id'] = $this->node_id;
        $result = M('twfx_get_trace')->field(
            'sum(bonus_amount) as bonus_amt,sum(sale_amount) as sale_amt,CASE WHEN deal_flag = "1" THEN "已发放" ELSE "未发放" END deal_flag')
            ->where($map)
            ->group('start_get_date,end_get_date')
            ->order('end_get_date desc')
            ->limit(1)
            ->select();
        if (empty($result)) {
            $this->error('下载失败');
        } else {
            $this->success('即将下载');
        }
    }

    public function loadTrace()
    {
        $map = array();
        $map['g.node_id'] = $this->node_id;
        $traceArr = M()->table('twfx_get_trace g')->field(
            'concat(DATE_FORMAT(g.start_get_date,"%Y-%m-%d")," 至 ",DATE_FORMAT(g.end_get_date,"%Y-%m-%d")) AS time,
				(SELECT SUM(bonus_amount) FROM twfx_get_trace WHERE node_id = "'.
            $this->node_id.'" AND  start_get_date= g.start_get_date AND  end_get_date = g.end_get_date ) AS bonus_amt,
				group_concat(DISTINCT(t.order_id)) as order_arr,
				CASE WHEN g.deal_flag = "1" THEN "已发放" ELSE "未发放" END deal_flag')
            ->where($map)
            ->group('g.start_get_date,g.end_get_date')
            ->join('twfx_trace t ON t.user_get_trace_id = g.id')
            ->order('g.end_get_date desc')
            ->select();
        if (!empty($traceArr)) {
            foreach ($traceArr as $k => $v) {
                $orderIdArr = explode(',', $v['order_arr']); // 取出订单id的数组
                // 订单总金额
                $orderAmt = '';
                foreach ($orderIdArr as $kk => $vv) {
                    $orderAmt += M('twfx_trace')->where(
                        array(
                            'node_id' => $this->node_id, ))->getFieldByOrder_id($vv,
                        'amount');
                }
                $traceArr[$k]['sale_amt'] = $orderAmt;
            }
        }
        $cols_arr = array(
            'time' => '时间',
            'bonus_amt' => '提成合计',
            'sale_amt' => '销售额合计',
            'deal_flag' => '发放状态', );
        import('@.ORG.Net.querydata') or die('[@.ORG.Net.querydata]导入包失败');
        QueryData::downloadData($traceArr, $cols_arr);
    }

    public function loadTraceXr()
    {
        $traceid = I('get.traceid');
        $tracetime = I('get.tracetime');
        $map['t.node_id'] = $this->node_id;
        $map['t.id'] = array(
            'in',
            $traceid, );
        $traceSql = M()->table('twfx_trace t')->field(
            't.order_id,t.amount,t.phone_no,DATE_FORMAT(t.add_time,"%Y-%m-%d") AS add_time,t.bonus_amount,s.name AS saler_name,s.phone_no AS saler_phone,s.alipay_account AS alipay_account,s.bank_account AS bank_account,s.bank_name AS bank_name')
            ->where($map)
            ->join('twfx_saler s ON s.id = t.saler_id')
            ->buildSql();
        $cols_arr = array(
            'order_id' => '订单号',
            'amount' => '订单金额',
            'phone_no' => '客户手机号码',
            'add_time' => '订单产生时间',
            'bonus_amount' => '提成金额',
            'saler_name' => '提成获取人',
            'saler_phone' => '提成获取人手机号码', );
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->nodeId,
            'account_type');
        if ($accountType == 1) {
            $cols_arr['alipay_account'] = '支付宝账号';
        } else {
            $cols_arr['bank_name'] = '银行名称';
            $cols_arr['bank_account'] = '银行账号';
        }
        $filename = iconv('utf-8', 'gbk', '提成发放明细（'.$tracetime.'）');
        querydata_download($traceSql, $cols_arr, M(), '提成发放明细', $filename);
    }

    public function viewDetails()
    {
        $traceid = I('get.traceid');
        $tracetime = I('get.tracetime');
        $map['t.node_id'] = $this->node_id;
        $map['t.id'] = array(
            'in',
            $traceid, );
        import('ORG.Util.Page');
        $count = M()->table('twfx_trace t')->where($map)->count();
        $CPage = new Page($count, 8);
        $traceList = M()->table('twfx_trace t')->field(
            't.*,s.name AS saler_name,s.phone_no AS saler_phone,s.alipay_account AS alipay_account,s.bank_account AS bank_account')
            ->where($map)
            ->join('twfx_saler s ON s.id = t.saler_id')
            ->limit($CPage->firstRow.','.$CPage->listRows)
            ->select();
        $page = $CPage->show();
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->nodeId,
            'account_type');
        $this->assign('traceList', $traceList);
        $this->assign('traceid', $traceid);
        $this->assign('tracetime', $tracetime);
        $this->assign('accountType', $accountType);
        $this->assign('page', $page);
        $this->display();
    }

    public function printDetails()
    {
        $traceid = I('get.traceid');
        $tracetime = I('get.tracetime');
        $map['t.node_id'] = $this->node_id;
        $map['t.id'] = array('in', $traceid);
        $count = M()->table('twfx_trace t')->where($map)->field(
            'CASE WHEN s.role=1 THEN "销售员" WHEN s.role=2 AND s.level=1 THEN "一级经销商" WHEN s.role=2 AND s.level=2 THEN "二级经销商" WHEN s.role=2 AND s.level=3 THEN "三级经销商" WHEN s.role=2 AND s.level=4 THEN "四级经销商" WHEN s.role=2 AND s.level=5 THEN "五级经销商"  END level_info,sum(t.bonus_amount) AS bonus_amount,s.name AS saler_name,s.phone_no AS saler_phone,s.alipay_account AS alipay_account,s.bank_account AS bank_account,s.bank_name AS bank_name')
            ->join('twfx_saler s ON s.id = t.saler_id')
            ->group('t.saler_id')->count();
        echo $count;

        if ((int) $count == 0) {
            echo "<script>alert('下载失败，没有相关数据可供下载！');window.history.back(-1);</script>";
            exit;
        }
        if($this->node_id==C('meihui.node_id')){
            $traceSql = M()->table('twfx_trace t')->field(
                    'CASE WHEN s.role=1 THEN "销售员" WHEN s.role=2 AND s.level=1 THEN "门店" WHEN s.role=2 AND s.level=2 THEN "钻石会员" WHEN s.role=2 AND s.level=3 THEN "金牌会员" WHEN s.role=2 AND s.level=4 THEN "银牌会员" WHEN s.role=2 AND s.level=5 THEN "五级经销商"  END level_info,sum(t.bonus_amount) AS bonus_amount,s.name AS saler_name,s.phone_no AS saler_phone,s.alipay_account AS alipay_account,s.bank_account AS bank_account,s.bank_name AS bank_name')
                    ->where($map)
                    ->join('twfx_saler s ON s.id = t.saler_id')
                    ->group('t.saler_id')
                    ->buildSql();
        }else{
            $traceSql = M()->table('twfx_trace t')->field(
                    'CASE WHEN s.role=1 THEN "销售员" WHEN s.role=2 AND s.level=1 THEN "一级经销商" WHEN s.role=2 AND s.level=2 THEN "二级经销商" WHEN s.role=2 AND s.level=3 THEN "三级经销商" WHEN s.role=2 AND s.level=4 THEN "四级经销商" WHEN s.role=2 AND s.level=5 THEN "五级经销商"  END level_info,sum(t.bonus_amount) AS bonus_amount,s.name AS saler_name,s.phone_no AS saler_phone,s.alipay_account AS alipay_account,s.bank_account AS bank_account,s.bank_name AS bank_name')
                    ->where($map)
                    ->join('twfx_saler s ON s.id = t.saler_id')
                    ->group('t.saler_id')
                    ->buildSql();
        }
        $cols_arr = array(
            'saler_name' => '提成获取人',
            'saler_phone' => '提成获取人手机号码',
            'level_info' => '所属层级',
            'bonus_amount' => '提成总额', );
        $accountType = M('twfx_node_info')->getFieldByNode_id($this->nodeId,
            'account_type');
        if ($accountType == 1) {
            $cols_arr['alipay_account'] = '支付宝账号';
        } else {
            $cols_arr['bank_name'] = '银行名称';
            $cols_arr['bank_account'] = '银行账号';
        }
        $filename = iconv('utf-8', 'gbk', '提成发放报表（'.$tracetime.'）');
        if (querydata_download($traceSql, $cols_arr, M(), '提成发放报表', $filename) ==
            false) {
            // $this->error('下载失败，没有相关数据可供下载！');
            echo "<script>alert('下载失败，没有相关数据可供下载！');window.history.back(-1);</script>";
        }
    }

    public function confirmSend()
    {
        $traceid = I('get.traceid');
        $gettraceid = I('get.gettraceid');
        $map = array(
            'id' => array(
                'in',
                $gettraceid, ),
            'node_id' => $this->node_id, );
        $mapEx = array(
            'id' => array(
                'in',
                $traceid, ),
            'node_id' => $this->node_id,
            'user_get_flag' => 2, );
        M()->startTrans();
        $traceFlag = M('twfx_get_trace')->where($map)->save(
            array(
                'deal_flag' => '1', ));
        $traceFlagEx = M('twfx_trace')->where($mapEx)->save(
            array(
                'user_get_flag' => '3', ));
        if (false === $traceFlag || false === $traceFlagEx) {
            M()->rollback();
            $this->error('操作失败');
        } else {
            M()->commit();
            redirect(U('Wfx/Tcgl/sendTc'));
        }
    }

    public function dostatic()
    {
        $map = array();
        $map['t.node_id'] = $this->node_id;
        $start_time = I('start_time', date('Ymd', strtotime('-7 days')));
        $end_time = I('end_time', date('Ymd'));
        $order_id = I('order_id', '');
        if (!empty($start_time)) {
            $map['t.add_time'][0] = array(
                'EGT',
                $start_time.'000000', );
        }
        if (!empty($end_time)) {
            $map['t.add_time'][1] = array(
                'ELT',
                $end_time.'235959', );
        }
        if (!empty($order_id)) {
            $map['t.order_id'] = array(
                'like',
                '%'.$order_id.'%', );
        }
        if ($this->node_id == C('meihui.node_id')) {
            $this->meihuiFlag = true;
            $map['b.level'] = array('GT', 1);
            $orderInfo = M()->table('twfx_trace t')
                ->join('twfx_saler b on b.id=t.saler_id')
                ->field('t.order_id')
                ->where($map)
                ->group('t.order_id')
                ->order('t.add_time desc')
                ->select();
        } else {
            $orderInfo = M()->table('twfx_trace t')->field('order_id')
                ->where($map)
                ->group('order_id')
                ->order('add_time desc')
                ->select();
        }
        $traceList = array();
        if (!empty($orderInfo)) {
            foreach ($orderInfo as $k1 => $v1) {
                if ($this->meihuiFlag == true) {
                    $order_about = M()->table('twfx_trace a')
                        ->join('twfx_saler b ON b.id=a.saler_id')
                        ->field('a.order_id,a.saler_id,count(DISTINCT a.saler_id) AS colspan')
                        ->where(array(
                            'a.order_id' => $v1['order_id'],
                            'a.node_id' => $this->node_id,
                            'b.level' => array('GT', 1),
                        ))
                        ->select();
                } else {
                    $order_about = M()->table('twfx_trace')
                        ->field('order_id,saler_id,count(DISTINCT saler_id) AS colspan')
                        ->where(array(
                            'order_id' => $v1['order_id'],
                            'node_id' => $this->node_id,
                        ))
                        ->select();
                }
                $one_salerid = $order_about[0]['saler_id'];
                if ($this->meihuiFlag == true) {
                    $tempResult = M()->table('twfx_trace t')
                        ->join('twfx_saler s ON s.id = t.saler_id')
                        ->field('t.order_id,t.saler_id,t.customer_name,s.name AS saler_name,sum(t.bonus_amount) AS bonus_amount,t.phone_no,sum(t.amount) AS amount,group_concat(concat("<p>",t.goods_name," x ",t.num,"</p>") separator "<br/>") AS goods_name')
                        ->where(
                            array(
                                't.order_id' => $v1['order_id'],
                                't.saler_id' => $one_salerid,
                                's.level' => array('GT', 1),
                            )
                        )
                        ->select();
                } else {
                    $tempResult = M()->table('twfx_trace t')->field(
                        't.order_id,t.saler_id,t.customer_name,s.name AS saler_name,sum(t.bonus_amount) AS bonus_amount,t.phone_no,sum(t.amount) AS amount,group_concat(concat("<p>",t.goods_name," x ",t.num,"</p>") separator "<br/>") AS goods_name')
                        ->where(
                            array(
                                't.order_id' => $v1['order_id'],
                                't.saler_id' => $one_salerid, ))
                        ->join('twfx_saler s ON s.id = t.saler_id')
                        ->select();
                }
                $tempResult[0]['colspan'] = $order_about[0]['colspan'];
                $traceList[] = $tempResult[0];
            }
        }
        import('ORG.Util.Page');
        $CPage = new Page(count($traceList), 5);
        $traceList = array_slice($traceList, $CPage->firstRow, $CPage->listRows);
        // 对结果数组处理
        $sonTraceList = array();
        if (!empty($traceList)) {
            foreach ($traceList as $k => $v) {
                if ($this->meihuiFlag == true) {
                    $map = array(
                        't.node_id' => $this->node_id,
                        't.order_id' => $v['order_id'],
                        't.saler_id' => array('neq', $v['saler_id']),
                        's.level' => array('GT', 1),
                    );
                } else {
                    $map = array(
                        't.node_id' => $this->node_id,
                        't.order_id' => $v['order_id'],
                        't.saler_id' => array('neq', $v['saler_id']),
                    );
                }
                $sonTraceList[$v['order_id']] = M()->table('twfx_trace t')->field(
                    's.name AS saler_name,sum(t.bonus_amount) AS bonus_amount')
                    ->join('twfx_saler s ON s.id = t.saler_id')
                    ->where($map)
                    ->group('t.saler_id')
                    ->order('t.id desc')
                    ->select();
            }
        }
        $page = $CPage->show();
        $this->assign('traceList', $traceList);
        $this->assign('sonTraceList', $sonTraceList);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('order_id', $order_id);
        $this->assign('page', $page);
        $this->display();
    }

    private function _is_num($param)
    {
        if (empty($param) && $param != '0') {
            return 1;
        }
        if (!is_numeric($param) && !is_float($param)) {
            return 2;
        }
        if ($param < 0 || $param > 100) {
            $this->error('提成范围设置必须在0~100之间');

            return 3;
        }
    }

    private function _not_null_zero($param)
    {
        if (empty($param) && $param != '0') {
            return false;
        } else {
            return true;
        }
    }
}
