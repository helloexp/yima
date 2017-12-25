<?php

/**
 * 功能：启动查询
 *
 * @author wangtr 时间：2013-06-26
 */
class StartSearchAction extends BaseAction {

    public $version_code;

    public $pos_serial;

    public function _initialize() {
        parent::_initialize();
        // 动态载入配置文件
        C('WC', require(CONF_PATH . 'configVersion.php'));
        // 初始化请求参数
        $this->version_code = I('version_code');
        // 初始化请求参数
        $this->pos_serial = I('pos_serial');
    }

    public function run() {
        if (!$this->pos_serial) {
            $resp_desc = "终端机身号不能为空";
            $this->returnAjax(array(
                    'resp_id'  => '0001',
                    'resp_str' => $resp_desc,
            ));
            exit();
        } else {
            $pos_serial = $this->pos_serial;
            // 以下是本地查询
            $pos_info  = array();
            $dao       = M('TposInfo');
            $posResult = $dao->where("pos_serialno='" . $pos_serial . "'")->find();
            if (!$posResult) {
                $posResult = array();
                $resp_desc = '机身号[' . $pos_serial . ']未绑定';
                $resp_id   = '9999';
            } else {
                $resp_desc = "请求启动搜索成功";
                $resp_id   = '0000';
            }
            $pos_info['node_id']      = $posResult['node_id']; // 商户号
            $pos_info['pos_id']       = $posResult['pos_id']; // 终端号
            $pos_info['pos_name']     = $posResult['pos_name']; // 终端名
            $pos_info['store_name']   = $posResult['store_name']; // 门店名
            $pos_info['is_activated'] = ($posResult['is_activated'] && !empty($posResult['store_name'])) ? '1' : '0'; // 是否首次激活（是否门店为空）
            $pos_info['android_ver']  = C('WC.ANDROID_VER'); // 版本号
            $pos_info['android_url']  = C('WC.ANDROID_URL'); // Andriod APP地址
            $pos_info['upgrade_flag'] = $this->version_code == C('WC.ANDROID_VER') ? 0 : C('WC.UPGRADE_FLAG'); // 强制升级标识,0-不需要升级,1-需要升级,2-强制升级
            $pos_info['upgrade_text'] = C('WC.UPGRADE_TEXT'); // 升级版本说明
            // 帮助文档地址
            $pos_info['help_url'] = C('WEB_HELP_URL');

            // 从这儿开始处理特殊IMEI号逻辑
            $test_version = array();
            $test_version = $this->getTestVersion($this->pos_serial);
            if ($test_version && is_array($test_version)) {
                $pos_info = array_merge($pos_info, $test_version);
            }
            // 结束特殊IMEI号逻辑
            if ($resp_id == '0000' || $pos_info['upgrade_flag'] != '0') {
                $this->returnSuccess($resp_desc, $pos_info);
            } else {
                $this->returnError($resp_desc);
            }

            exit();
        }
    }

    // 这儿是获取特殊IMEI号逻辑
    private function getTestVersion($imei) {
        $pos_info = false;
        // 动态载入配置文件测试IMEI号
        $cfg = include(CONF_PATH . 'configVersionTestImei.php');
        if (!isset($cfg['TEST_IMEI'])) {
            return false;
        }
        foreach ($cfg['TEST_IMEI'] as $k => $test_imei_list) {
            if (!isset($cfg[$k])) {
                return false;
            } else {
                $test_version = $cfg[$k];
            }
            if (in_array($imei, $test_imei_list)) {
                $pos_info                = array();
                $pos_info['android_ver'] = $test_version['ANDROID_VER']; // 版本号
                $pos_info['android_url'] = $test_version['ANDROID_URL']; // Andriod
                // APP地址
                $pos_info['upgrade_flag'] = $test_version['UPGRADE_FLAG']; // 强制升级标识,0-不需要升级,1-需要升级,2-强制升级
                $pos_info['upgrade_text'] = $test_version['UPGRADE_TEXT']; // 升级版本说明
                return $pos_info;
            }
        }

        return false;
    }
}

?>