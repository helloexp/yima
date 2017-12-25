<?php

/* 微信服务扩展行为 */
class WeixinServBehavior extends Behavior {

    /*
     * 扩展两个参数 0：tagname:要处理的位置 1 action类
     */
    public $feibiaoConfig;

    public $node_id;

    public $obj;

    public $feibiaoAction;

    public function _initialize(&$params) {
        $this->feibiaoConfig = include (CONF_PATH . 'WeixinServ/Feibiao.php');
        $this->obj = $params[1];
        $this->node_id = $this->obj->node_id;
        if ($this->feibiaoConfig && isset($this->feibiaoConfig[$this->node_id])) {
            $config = $this->feibiaoConfig[$this->node_id];
            include_once (LIB_PATH . 'Action/WeixinServ/Feibiao/' .
                 $config['class'] . '.class.php');
            $this->feibiaoAction = new $config['class']();
            if (! empty($config['config'])) {
                $this->feibiaoAction->config = $config['config'];
            }
        }
    }

    public function run(&$params) {
        $this->_initialize($params);
        if ($this->feibiaoAction) {
            $this->feibiaoAction->run($params);
        }
    }
}