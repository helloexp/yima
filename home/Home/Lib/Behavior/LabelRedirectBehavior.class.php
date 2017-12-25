<?php

/**
 * 标签非标处理；用于对某些指定的id做跳转
 */
class LabelRedirectBehavior extends Behavior {

    public function run(&$params) {
        $id = $params['id'];

        /**
         * 利川活动配置错误！程序处理正确跳转
         * zhoukai
         * 2016年4月28日23:32:51
         */
        if($id == '166103'){
            redirect('http://www.wangcaio2o.com/index.php?g=Label&m=Channel&a=index&id=258404');
        }
    }
}