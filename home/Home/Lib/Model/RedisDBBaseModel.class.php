<?php

/**
 * 1、redis 缓存开关 (关闭了之后，可以直接使用tp自带的model) 开关在 configRedis.php配置文件中
 * 2、redis 缓存支持列表
 * Redis DB 数据存储model
 *
 * @author  Jeff Liu<liuwy@imageco.com.cn>
 * @version 0.1
 */
class RedisDBBaseModel extends BaseModel
{
    /**
     *
     * @var LocalCache
     */
    protected $LocalCache;

    /**
     *
     * @var RedisHelper
     */
    protected $redis;

    protected $tableName;
    // 这个必须唯一 否则存储会有问题
    protected $_pk;
    // 主键（不同于db的pk，这个主要用来存储的时候 作为 key的一部分。)
    protected $_sk;
    // 外键(和$_pk组合 可以唯一定位一条数据)
    protected $defaultValue = array();
    // 默认数据
    protected $useRedisCacheFlag = null;
    // 这个变量不能自己设置 会根据配置项 自己设定好的

    protected $useCache = 'redis'; // 1:redis 2:other

    /**
     * 构造函数
     *
     * @param string $name
     * @param string $tablePrefix
     * @param string $connection
     */
    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        if ($this->needUseCache()) {
            if (empty($this->tableName)) {
                $this->errorInfo(
                        __METHOD__,
                        func_get_args(),
                        '$this->tableName is empty!!'
                );
            }
            if (empty($this->_pk)) {
                $this->errorInfo(
                        __METHOD__,
                        func_get_args(),
                        '$this->_pk is empty!!'
                );
            }

            import('@.Vendor.LocalCache');
            import('@.Vendor.DebugHelper');
            $this->LocalCache = new LocalCache();
        }
        parent::__construct($name, $tablePrefix, $connection);
    }

    /**
     * 是否使用redis缓存
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    public function needUseCache()
    {
        if (is_null($this->useRedisCacheFlag)) {
            $useRedisCache           = C('useRedisCache');
            $this->useRedisCacheFlag = false;
            if ($useRedisCache) {
                $useRedisCacheControl = C('useRedisCacheControl');
                if ($useRedisCacheControl == 'config') {
                    $tableName           = $this->getTableName();
                    $redisCacheTableList = C('redisCacheTableList');
                    if (isset($redisCacheTableList[$tableName]) && $redisCacheTableList[$tableName]) {
                        $this->useRedisCacheFlag = true;
                    } else {
                        $this->useRedisCacheFlag = false;
                    }
                } else if ($useRedisCacheControl == 'model') {
                    if ($this->useCache == 'redis') {
                        $this->useRedisCacheFlag = true;
                    }
                }
            }
        }
        return $this->useRedisCacheFlag;
    }

    /**
     * todo 以后可以实现添加更加指定的id或者redis的index 初始化系统, 分表等
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $pk
     */
    protected function init($pk)
    {
        if ($this->needUseCache()) { // 需要使用redis 做为db缓存
            $redisindex = 0;
            if ($pk) {
                $redisindex = 0;
            }
            $this->redis = RedisHelper::getInstance($redisindex);
        }
    }

    /**
     * 获得cache主键
     *
     * @param $pk
     *
     * @return string
     */
    public function getStorageKey($pk)
    {
        return $this->tableName . ':' . $pk;
    }

    /**
     * 添加数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $data
     *
     * @return mixed
     */
    public function add($data)
    {
        if ($this->needUseCache()) { // 需要使用redis 缓存
            if (isset($data[$this->_pk])) {
                $pk = $data[$this->_pk];
                $this->init($pk);

                $data = array_merge($this->defaultValue, $data);
                $ret  = parent::add($data);

                if ($ret > 0) { // 成功
                    $key = $this->getStorageKey($pk);

                    if ($this->isMulit()) {
                        $sk = isset($data[$this->_sk]) ? $data[$this->_sk] : $ret;
                        if (isset($this->LocalCache[$key])) {
                            $tempData               = $this->LocalCache[$key];
                            $tempData[$sk]          = $data;
                            $this->LocalCache[$key] = $tempData;
                        }
                        $this->redis->hSet($key, $sk, $data);
                    } else {
                        $this->LocalCache[$key][$pk] = $data;
                        $this->redis->hSet($key, $pk, $data);
                    }
                } else { // 失败
                    $this->errorInfo(__METHOD__, func_get_args(), 'add failure');
                }
                return $ret;
            } else {
                $this->errorInfo(__METHOD__, func_get_args(), 'pk is empty');
                return false;
            }
        } else { // 不需要使用redis 直接调用父类的add方法
            return parent::add($data);
        }
    }

    /**
     * 格式化 where 以前where写的乱七八糟的， 有的是 array 有的是字符串
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $options
     *
     * @return mixed
     */
    public function formatWhere($options = array())
    {
        if (empty($options)) {
            $options = $this->options;
        }
        $where = array();
        if (isset($options['where'])) {
            $where = $options['where'];
            if (isset($where['_string'])) {
                $whereStr = trim($where['_string']);
                $whereStr = str_replace('and', 'AND', $whereStr);
                $tmp      = explode(' AND ', $whereStr);
                $where    = [];
                foreach ($tmp as $k => $v) {
                    $v     = trim($v);
                    $vPair = explode('=', $v);
                    if (count($vPair) == 2) { // 只有 xx=yy 的方式才行
                        $key         = strtolower($vPair[0]);
                        $key         = trim($key, "'\"\t\x0B\r\n\0\x20");
                        $value       = trim($vPair[1], "'\"\t\x0B\r\n\0\x20"); // 去除单引号、双引号、\t\v\r\n\0\space
                        $where[$key] = $value;
                    }
                }
            }
        }
        return $where;
    }

    /**
     * 获得 pk sk的值
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $data
     * @param array $options
     *
     * @return array
     */
    private function getPkAndSk($data = array(), $options = array())
    {
        $pk = null;
        if (isset($data[$this->_pk])) {
            $pk = $data[$this->_pk];
        } else if (isset($options[$this->_pk])) {
            $pk = $options[$this->_pk];
        }
        $sk = null;
        if (isset($data[$this->_sk])) {
            $sk = $data[$this->_sk];
        } else if (isset($options[$this->_sk])) {
            $sk = $options[$this->_sk];
        }

        if (is_null($pk)) {
            $this->errorInfo(__METHOD__, func_get_args(), 'pk is null');
        }
        if ($this->isMulit() && is_null($sk)) {
            $this->errorInfo(__METHOD__, func_get_args(), 'sk is null');
        }

        return ['pk' => $pk, 'sk' => $sk];

    }

    /**
     * 保存数据 Usage: $model = D('XXXModel'); $where = array( 'node_id' =>
     * $node_id, 'id' => $id, ); $model->save($data, $where); //where只能包含pk和sk
     * PS： 请不要使用 字符串形式的。 格式要统一
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $data
     * @param array  $options
     *
     * @return bool|int
     */
    public function save($data = '', $options = array())
    {
        if ($this->useRedisCacheFlag) { // 使用redis 缓存
            return $this->updateByPk($data, $options);
        } else { // 不是用redis 缓存 直接调用父类方法
            return parent::save($data, $options);
        }
    }

    /**
     * 更新数据 Usage: $model = D('XXXModel'); $where = array( 'node_id' =>
     * $node_id, 'id' => $id, ); $model->updateByPk($data, $where);
     * //where只能包含pk和sk PS： 请不要使用 字符串形式的。 格式要统一
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $data
     * @param array $options
     *
     * @return int
     */
    public function updateByPk($data, $options = array())
    {
        $pkAndSk = $this->getPkAndSk($data, $options);
        $pk      = $pkAndSk['pk'];
        $sk      = $pkAndSk['sk'];
        $this->init($pk);

        if (count($this->defaultValue) !== count($data)) { // 不完整完整字段, 合并更新
            if ($this->isMulit()) {
                $odatas = $this->get($pk, $sk);
            } else {
                $odatas = $this->getByPk($pk);
            }
            if (empty($odatas)) {
                $data = array_merge($this->defaultValue, $data);
            } else {
                $data = array_merge($this->defaultValue, $odatas, $data);
                unset($odatas);
            }
        }

        $ret = parent::where($options)->save($data); // 保存数据到db
        if ($ret) { // 保存成功
            $key = $this->getStorageKey($pk);
            if ($this->isMulit()) {
                $tmp                    = $this->LocalCache[$key];
                $tmp[$sk]               = $data;
                $this->LocalCache[$key] = $tmp;
                $this->redis->hSet($key, $sk, $data);
                unset($sk, $tmp);
            } else {
                $this->LocalCache[$key][$pk] = $data;
                $this->redis->hSet($key, $pk, $data);
            }
            unset($data, $key);
        }

        return $ret;
    }

    /**
     * 按照主键获取数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $pk
     *
     * @return array|mixed
     */
    public function getByPk($pk)
    {
        $key = $this->getStorageKey($pk);
        if (isset($this->LocalCache[$key])) { // 本地缓存数据
            return $this->LocalCache[$key];
        } else { // 尝试从redis 或者 db 中读取数据
            if ($this->needUseCache()) {
                $this->init($pk);
                $datas = $this->redis->hGetAll($key);
                if (!isset($datas[$pk])) { // 尝试从mysql中读取数据
                    $datas = parent::getByPk($pk); // 数据结构已经处理好了
                    if ($datas) { // 将mysql中的数据缓存到redis和本地缓存中
                        $this->LocalCache[$key] = $datas;
                        $this->redis->hMset($key, $datas);
                    }
                }
                return $datas;
            } else {
                return $this->getOne([$this->_pk => $pk]);
            }
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $pk
     * @param string $sk
     *
     * @return array|mixed|null
     */
    public function getBySk($pk, $sk)
    {
        $key = $this->getStorageKey($pk);
        if (isset($this->LocalCache[$key][$sk])) { // 本地缓存数据
            return $this->LocalCache[$key][$sk];
        } else { // 尝试从redis 或者 mysql 中读取数据
            if ($this->needUseCache()) {
                $this->init($pk);
                $datas = $this->redis->hGet($key, $sk);
                if (empty($datas)) { // 尝试从mysql中读取数据
                    $where = [$this->_pk => $pk, $this->_sk => $sk];
                    $datas = $this->getOne($where);
                    if ($datas) { // 将mysql中的数据缓存到redis中
                        $this->LocalCache[$key] = $datas;
                        $this->redis->hSet($key, $sk, $datas);
                    }
                }
                return $datas;
            } else {
                $where = [$this->_pk => $pk, $this->_sk => $sk];
                return $this->getOne($where);
            }
        }
    }

    /**
     * 删除数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $pk
     * @param $sk
     *
     * @return bool
     */
    public function deleteBySk($pk, $sk)
    {
        $this->init($pk);
        $key = $this->getStorageKey($pk);
        if ($sk) {
            $ret = parent::where([$this->_pk => $pk, $this->_sk => $sk])->delete();
            if ($ret !== false) { // 删除成功 清除缓存
                $this->redis->hdel($key, $sk);
                if (isset($this->LocalCache[$key][$sk])) { // 删除本地缓存
                    unset($this->LocalCache[$key][$sk]);
                }
            } else { // 删除报错
                $this->errorInfo(__METHOD__, func_get_args(), 'delete failure');
            }
        } else {
            $ret = parent::where([$this->_pk => $pk])->delete();

            if ($ret !== false) { // 删除成功 删除redis相关数据
                $this->redis->delete($key);
                unset($this->LocalCache[$key]);
            } else {
                $this->errorInfo(__METHOD__, func_get_args(), 'delete failure');
            }
        }

        return $ret;
    }

    /**
     * 根据给定的主键值或由主键值组成的数组，删除相应的记录。
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $pk
     * @param string $sk
     *
     * @return bool
     *
     */
    public function deleteByPk($pk, $sk = '')
    {
        return $this->deleteBySk($pk, $sk);
    }

    /**
     * todo 错误信息记录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $method
     * @param $params
     * @param $msg
     */
    private function errorInfo($method, $params, $msg)
    {
        $class = get_called_class();
        list (, $method) = explode('::', $method);
        $methodInfo = $class . '->' . $method . '(' . var_export($params, 1) . '); msg:' . $msg;
        if (defined('PHP_OS') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            error_log($methodInfo . PHP_EOL, 3, '/tmp/redis.op.log');
        } else {
            error_log($methodInfo . PHP_EOL, 3, 'd:/redis.op.log');
        }
    }

    /**
     * 根据联合主键查询数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param number $pk
     * @param number $sk
     *
     * @return array
     */
    public function get($pk, $sk = null)
    {
        if (empty($sk)) {
            return $this->getByPk($pk);
        } else {
            return $this->getBySk($pk, $sk);
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param number $pk
     *
     * @return int
     */
    protected function getCountBySk($pk)
    {
        return count($this->getByPk($pk));
    }

    /**
     * 删除指定的数据 Usage: $model = D('XXXModel'); 'node_id' => $node_id, 'id' =>
     * $id, ); $model->delete($data, $where); //where只能包含pk和sk PS： 请不要使用 字符串形式的。
     * 格式要统一
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $options
     *
     * @return bool|mixed
     */
    public function delete($options = [])
    {
        if ($this->useRedisCacheFlag) { // 使用redis缓存
            $pkAndSk = $this->getPkAndSk([], $options);
            $pk      = $pkAndSk['pk'];
            $sk      = $pkAndSk['sk'];
            if ($sk) {
                return $this->deleteBySk($pk, $sk);
            } else {
                return $this->deleteByPk($pk);
            }
        } else { // 不使用redis缓存
            return parent::delete($options);
        }
    }

    /**
     * todo 如果名字为 selelct 会造成死循环
     * 查询数据集 Usage: $model = D('XXXModel'); $where = array( 'node_id' =>
     * $node_id, 'id' => $id, ); $model->select($where); //where只能包含pk和sk PS：
     * 请不要使用 字符串形式的。 格式要统一
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $options 表达式参数
     *
     * @return mixed
     */
    public function select_redis($options = array())
    {
        if ($this->useRedisCacheFlag) { // 使用redis缓存
            if (empty($options)) {
                $options = $this->formatWhere();
            }
            $pkAndSk = $this->getPkAndSk([], $options);
            $pk      = $pkAndSk['pk'];
            $sk      = $pkAndSk['sk'];
            if ($sk) {
                return $this->getBySk($pk, $sk);
            } else {
                return $this->getByPk($pk);
            }
        } else { // 不使用redis缓存
            return parent::select($options);
        }
    }

    /**
     * 指定查询条件 支持安全过滤
     *
     * @access public
     *
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     *
     * @return Model
     */
    public function where($where, $parse = null)
    {
        if ($this->useRedisCacheFlag) { // 使用redis缓存
            if (is_array($where)) {
                return parent::where($where, $parse);
            } else {
                $this->errorInfo(
                        __METHOD__,
                        func_get_args(),
                        'where param except array get ' . gettype($where)
                );
                return false;
            }
        } else { // 不使用redis 缓存 直接调用父类的方法
            return parent::where($where, $parse);
        }
    }
}