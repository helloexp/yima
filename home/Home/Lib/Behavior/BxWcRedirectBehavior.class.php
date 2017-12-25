<?php

/**
 * User: kk
 * Date: 2016/1/25
 * Time: 13:24
 * 平安行业版独立，对于旺财上的业务模块做跳转处理
 */
class BxWcRedirectBehavior extends Behavior
{

    protected $options = [];

    public function run(&$params)
    {
        if ($params['node_id'] == C('pingan.node_id')) {
            //升级未开始
            if ($this->_getSystemParam('HBPINGAN_UPGRADE') === '0') {
                return ;
            }

            //升级中
            if ($this->_getSystemParam('HBPINGAN_UPGRADE') === '1') {
                $now = time();
                if ($now > strtotime($this->_getSystemParam('HBPINGAN_UPGRADE_BEGINTIME'))
                    && $now < strtotime($this->_getSystemParam('HBPINGAN_UPGRADE_ENDTIME'))) {
                    die('系统升级中…………');
                }
            }

            $redirectUrl = U('', I(), '', '', C('WCBX_DOMAIN'));

            //营销活动跳转
            if (GROUP_NAME == 'Label') {
                log_write('pingan batch redirect!' . $redirectUrl);
                redirect($redirectUrl, 0);
            }

            //平安非标模块访问
            if(GROUP_NAME == 'Fb' && substr(MODULE_NAME, 0, 6) == 'Pingan'){
                log_write('pingan  redirect!' . $redirectUrl);
                redirect($redirectUrl, 0);
            }
        }
    }


    /**
     * 获取系统配置参与
     *
     * @param $name
     *
     * @return null
     */
    public function _getSystemParam($name)
    {
        static $paramArr = null;
        if ($paramArr === null) {
            $paramArr = M('tsystem_param')->getField('param_name, param_value');
        }

        return get_val($paramArr, $name, null);
    }
}