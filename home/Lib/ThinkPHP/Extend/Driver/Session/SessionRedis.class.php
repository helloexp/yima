<?php

/**
 * 自定义Redis来保存session
 */
Class SessionRedis
{

    /**
     * @var Redis
     */
    private $Redis;

    //SESSION有效时间
    private $expire;

    //外部调用的函数
    public function execute()
    {
        session_set_save_handler(
                array(&$this, 'open'),
                array(&$this, 'close'),
                array(&$this, 'read'),
                array(&$this, 'write'),
                array(&$this, 'destroy'),
                array(&$this, 'gc')
        );
    }

    //连接memcached和初始化一些数据
    public function open($path, $name)
    {
        $this->expire = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxlifetime');
        $config       = C('REDIS');
        $this->Redis  = new Redis();
        return $this->Redis->connect($config['host'], $config['port']);
    }

    //关闭memcache服务器
    public function close()
    {
        return $this->Redis && $this->Redis->close();
    }

    /**
     * 读取数据
     *
     * @param $id
     *
     * @return bool|string
     */
    public function read($id)
    {
        $id   = C('SESSION_PREFIX') . $id;
        $data = $this->Redis->get($id);
        return $data ? $data : '';
    }

    /**
     * 存入数据
     *
     * @param $id
     * @param $data
     *
     * @return bool
     */
    public function write($id, $data)
    {
        $id = C('SESSION_PREFIX') . $id;
        //$data = addslashes($data);
        return $this->Redis->set($id, $data, $this->expire);
    }

    /**
     * 销毁数据
     *
     * @param $id
     *
     * @return int
     */
    public function destroy($id)
    {
        $id = C('SESSION_PREFIX') . $id;
        return $this->Redis->del($id);
    }

    /**
     * 垃圾销毁
     * @return bool
     */
    public function gc()
    {
        return true;
    }
}