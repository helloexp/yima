<?php

/*
 * 分享活动工具类
 */

class QqzfService
{

    const pay_type = 5;
    // qq 钱包

    private $check_error = '';

    /**
     * QQ钱包支付主页
     *
     * @param $nodeId
     *
     * @return array
     */
    public function qqIndex($nodeId)
    {
        //获得基本信息

        $qqInfo = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $nodeId,
                        'pay_type' => self::pay_type
                )
        )->find();

        $apply_data = array();
        if (!empty($qqInfo['apply_data'])) {
            $apply_data = (array)json_decode($qqInfo['apply_data']);
        }
        unset($qqInfo['apply_data']);
        $qqInfo_bak = $qqInfo;
        if (!empty($apply_data)) {
            $qqInfo_bak = array_merge($qqInfo, $apply_data);
        }
        $qqInfo_bak['province_name'] = M('tcity_code')->where(
                array(
                        'city_level'    => 1,
                        'province_code' => $qqInfo_bak['province']
                )
        )->getField('province');
        $qqInfo_bak['city_name']     = M('tcity_code')->where(
                array(
                        'city_level' => 2,
                        'city_code'  => $qqInfo_bak['city']
                )
        )->getField('city');

        return array($qqInfo_bak, $qqInfo);
    }

    /**
     * 验证字段
     *
     * @param $data
     *
     * @return bool
     */
    function checkinfo(&$data)
    {
        // 第一部分校验
        if ($data['node_name'] == '') {
            $this->check_error = '商户名称不能为空';
            return false;
        }
        if ($data['register_address'] == '') {
            $this->check_error = '注册地址不能为空';
            return false;
        }
        if ($data['business_license'] == '') {
            $this->check_error = '营业执照注册号不能为空';
            return false;
        }
        if (empty($data['business_date'][0]) || empty($data['business_date'][1])) {
            $this->check_error = '营业期限不能为空';
            return false;
        }
        if ($data['business_scope'] == '') {
            $this->check_error = '营业执照范围不能为空';
            return false;
        }
        if ($data['business_img'] == '') {
            $this->check_error = '营业执照影印件不能为空';
            return false;
        }
        if ($data['kf_phone'] == ''){
            $this->check_error = '客服电话不能为空';
            return false;
        }

        // 第二部分校验

        if ($data['contact_type'] == '') {
            $this->check_error = '联系人类型不能为空';
            return false;
        }
        if ($data['contact_name'] == '') {
            $this->check_error = '联系人姓名不能为空';
            return false;
        }
        if ($data['contact_phone'] == '') {
            $this->check_error = '手机号码不能为空';
            return false;
        }
        if ($data['contact_email'] == '') {
            $this->check_error = '联系邮箱不能为空';
            return false;
        }
        if ($data['contact_idnum'] == '') {
            $this->check_error = '身份证号码不能为空';
            return false;
        }
        if (empty($data['contact_idnum_date'][0]) || empty($data['contact_idnum_date'][1])) {
            $this->check_error = '有效期不能为空';
            return false;
        }
        if (empty($data['contact_idmun_img1']) || empty($data['contact_idmun_img2'])) {
            $this->check_error = '身份证影印件不能为空';
            return false;
        }
        // 第三部分校验
        if ($data['short_node_name'] == '') {
            $this->check_error = '商家简称不能为空';
            return false;
        }
        if ($data['operate_category1'] == '' || $data['operate_category2'] == '' || $data['operate_category3'] == '') {
            $this->check_error = '经营类目不能为空';
            return false;
        }
        // 第四部分校验
        if ($data['bank_name'] == '') {
            $this->check_error = '开户名称不能为空';
            return false;
        }
        if ($data['bank_code'] == '') {
            $this->check_error = '开户银行不能为空';
            return false;
        }
        if (empty($data['bank_province']) || empty($data['bank_city'])) {
            $this->check_error = '开户银行城市不能为空';
            return false;
        }
        if ($data['bank_adress'] == '') {
            $this->check_error = '开户支行不能为空';
            return false;
        }
        if ($data['bank_num'] == '') {
            $this->check_error = '银行账户不能为空';
            return false;
        }

        if ($data['shop_type'] == 0) {
            if ($data['organization_code'] == '') {
                $this->check_error = '组织机构代码不能为空';
                return false;
            }
            if ($data['organization_img'] == '') {
                $this->check_error = '组织机构代码证影印件不能为空';
                return false;
            }
            if (empty($data['organization_date'][0]) || empty($data['organization_date'][1])) {
                $this->check_error = '有效期不能为空';
                return false;
            }
        } else {
            $data['organization_code'] = '';
        }
        return true;
    }

    /**
     * QQ钱包支付epos
     *
     * @param $nodeId
     *
     * @return bool
     */
    function qqzfepos($nodeId)
    {
        if (I('post.appid') != '') {
            $arr_save = array(
                    'link_type'   => $_REQUEST['type'],
                    'status'      => 3,
                    'account_pid' => I('post.appid')
            );
        } else {
            $arr_save = array(
                    'link_type' => $_REQUEST['type'],
                    'status'    => 3
            );
        }
        $rs = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $nodeId,
                        'pay_type' => self::pay_type
                )
        )->save($arr_save);

        return $rs;

    }
    /**
     * QQ钱包支付epos
     *
     * @param $nodeId
     *
     * @return bool
     */
    function qqzfeposCount($nodeId)
    {
        $eposcnt = M('tpos_info')->where(array(
                'node_id' => $nodeId,
                'is_activated' => 1,
                'pos_type' => array(
                        'not  in',
                        '0'
                ),
                'pos_status' => array(
                        'not in',
                        '3,4'
                )
        ))->count();

        return $eposcnt;

    }



    /**
     *
     * QQ钱包支付申请添加
     *
     * @param $nodeId
     *
     * @return array
     */
    public function addQqSubmit($nodeId)
    {
        $data = I('post.');
        $this->checkinfo($data);
        if($data['add_method']=='sub'){
            $pay_type='55';
        }else{
            $pay_type='5';
        }

        if ($pay_type == '5') {
            $row = M('tzfb_offline_pay_info')->where(['node_id'  => $nodeId, 'pay_type' => 5])->count();
            if ($row > 0) {
                return ['code'    => '0', 'message' => '操作非法！！！'];
            }
        }

        if ($this->check_error != '') {
            return array(
                    'code'    => '1',          //验证错误
                    'message' => $this->check_error
            );
        }

        $node_info           = M('tnode_info')->where(
                array(
                        'node_id' => $nodeId
                )
        )->find();
        $selectTindustryInfo = M('tindustry_info')->where(
                array(
                        'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                )
        )->getField('industry_name');
        $arr_                = array(
                'node_id'          => $nodeId,
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['contact_email'],
                'industry'         => $selectTindustryInfo,
                'province'         => $data['bank_province'],
                'city'             => $data['bank_city'],
                'address'          => $data['bank_adress'],
                'contact_name'     => $data['contact_name'],
                'contact_position' => $data['contact_type'],
                'contact_phone'    => $data['contact_phone'],
                'contact_tel'      => $data['contact_phone'],
                'contact_eml'      => $data['contact_email'],
                'zfb_account'      => '0',
                'add_time'         => date('YmdHis'),
                'pay_type'         => $pay_type,
                'check_status'     => '0',
                'status'           => '0',
                'tg_client_str'    => $_REQUEST['fclient_id'],
                'shop_type'        => $data['shop_type']
        );
        unset($data['node_name']);
        unset($data['contact_email']);
        unset($data['bank_province']);
        unset($data['bank_city']);
        unset($data['bank_adress']);
        unset($data['contact_name']);
        unset($data['contact_type']);
        unset($data['contact_phone']);
        unset($data['contact_email']);
        unset($data['shop_type']);

        $apply_data         = json_encode($data);
        $arr_['apply_data'] = $apply_data;
        $outcome            = M('tzfb_offline_pay_info')->add($arr_);
        if ($outcome) {
            return array(
                    'code' => '2'                 //提交成功
            );
        } else {
            return array(
                    'code' => '3'           //提交失败
            );
        }
    }

    /**
     * QQ钱包支付申请编辑
     *
     * @param $nodeId
     *
     * @return array
     */
    public function editQqSubmit($nodeId,$id)
    {
        // 判断当前商户类型
        $row = M('tzfb_offline_pay_info')->field('check_status')->where(
                        array(
                                'node_id'  => $nodeId,
                                'id' => $id
                        )
                )->find();

        if ($row['check_status'] == '1') {
            return array(
                    'code' => '0'               //申请已通过，无法编辑申请信息
            );
        }
        $data = I('post.');
        $this->checkinfo($data);

        if ($this->check_error != '') {
            return array(
                    'code'    => '1',          //验证错误
                    'message' => $this->check_error
            );
        }

        $node_info           = M('tnode_info')->where(
                array(
                        'node_id' => $nodeId
                )
        )->find();
        $selectTindustryInfo = M('tindustry_info')->where(
                array(
                        'industry_code' => str_pad($node_info['trade_type'], 3, "0", STR_PAD_LEFT)
                )
        )->getField('industry_name');
        $arr_                = array(
                'node_id'          => $nodeId,
                'node_name'        => $data['node_name'],
                'node_mail'        => $data['contact_email'],
                'industry'         => $selectTindustryInfo,
                'province'         => $data['bank_province'],
                'city'             => $data['bank_city'],
                'address'          => $data['bank_adress'],
                'contact_name'     => $data['contact_name'],
                'contact_position' => $data['contact_type'],
                'contact_phone'    => $data['contact_phone'],
                'contact_tel'      => $data['contact_phone'],
                'contact_eml'      => $data['contact_email'],
                'zfb_account'      => '0',
            // 'add_time' => date('YmdHis'),
//                'pay_type'         => self::pay_type,
                'check_status'     => '0',
                'status'           => '0',
                'tg_client_str'    => $_REQUEST['fclient_id'],
                'shop_type'        => $data['shop_type']
        );
        unset($data['node_name']);
        unset($data['contact_email']);
        unset($data['bank_province']);
        unset($data['bank_city']);
        unset($data['bank_adress']);
        unset($data['contact_name']);
        unset($data['contact_type']);
        unset($data['contact_phone']);
        unset($data['contact_email']);
        unset($data['shop_type']);
        $str_replace                  = '/(\d{4})-(\d{2})-(\d{2})(.*)/';
        $data['business_date'][0]     = preg_replace($str_replace, '$1$2$3', $data['business_date'][0]);
        $data['business_date'][1]     = preg_replace($str_replace, '$1$2$3', $data['business_date'][1]);
        $data['organization_date'][0] = preg_replace($str_replace, '$1$2$3', $data['organization_date'][0]);
        $data['organization_date'][1] = preg_replace($str_replace, '$1$2$3', $data['organization_date'][1]);

        $data['contact_idnum_date'][0] = preg_replace($str_replace, '$1$2$3', $data['contact_idnum_date'][0]);
        $data['contact_idnum_date'][1] = preg_replace($str_replace, '$1$2$3', $data['contact_idnum_date'][1]);

        $apply_data         = json_encode($data);
        $arr_['apply_data'] = $apply_data;
        $outcome            = M('tzfb_offline_pay_info')->where(
                array(
                        'node_id'  => $nodeId,
                        'id' => $id
                )
        )->save($arr_);
        if ($outcome) {
            return array(
                    'code' => '2'                //提交成功
            );
        } else {
            return array(
                    'code' => '3'                //提交失败
            );
        }
    }
}
