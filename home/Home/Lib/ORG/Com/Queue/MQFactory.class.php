<?php
include_once dirname(__FILE__) . '/MQ.interface.php';

class MQFactory {

    const MQ_TYPE_ZERO = "ZeroMQ";
    
    // const MQ_TYPE_ = "ZeroMQ";
    
    /**
     * 创建队列实例
     *
     * @param string $MQType
     * @return MQ
     */
    public static function newInstance($MQType = "ZeroMQ", $config = array()) {
        $obj = null;
        if ($MQType == self::MQ_TYPE_ZERO) {
            include_once dirname(__FILE__) . '/ZeroMQ.class.php';
            $obj = new ZeroMQ($config);
        }
        $obj->connect();
        return $obj;
    }
}

?>