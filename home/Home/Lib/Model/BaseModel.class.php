<?php

/**
 * base model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/7/13 Time: 11:12
 */
class BaseModel extends Model
{

    protected $tableName; // 默认对应数据库名称
    const SELECT_TYPE_ALL = 0; // 0：获得所有满足条件的记录
    const SELECT_TYPE_ONE = 1; // 1：获得一条满足条件的记录
    const SELECT_TYPE_FIELD = 2; // 2:获得一条满足条件的记录中的某些字段
    protected $_pk;

    protected $_sk;

    /**
     * @var Redis
     */
    protected $_redisLink;

    // 该值不能为null
    public function _initialize()
    {
        parent::_initialize();
        import('@.Vendor.ModelConst');
    }

    /**
     * 通用获得数据的方法
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $table
     * @param array  $where
     * @param int    $selectType
     * @param string $field
     * @param string $order
     * @param string $limit
     *
     * @return mixed
     */
    public function getData(
            $table = '',
            $where = array(),
            $selectType = self::SELECT_TYPE_ALL,
            $field = '',
            $order = '',
            $limit = ''
    ) {
        $this->table($table)->where($where);
        if ($field) {
            $this->field($field);
        }
        if ($order) {
            $this->order($order);
        }
        if ($limit) {
            $this->limit($limit);
        }
        if ($selectType === self::SELECT_TYPE_ALL) {
            return $this->select();
        } elseif ($selectType === self::SELECT_TYPE_ONE) {
            return $this->find();
        } else {
            return $this->getField($field);
        }
    }

    protected function _parseTable($options)
    {
        $table = '';
        if (!isset($options['table'])) { // 自动获取表名
            $table = $this->getTableName();
        }

        if (!empty($options['alias'])) {
            $table .= ' ' . $options['alias'];
        }

        return $table;
    }

    protected function _parseField($options)
    {
        if (!isset($options['table'])) { // 自动获取表名
            $fields = $this->fields;
        } else { // 指定数据表 则重新获取字段列表 但不支持类型检测
            $fields = $this->getDbFields();
        }

        return $fields;
    }

    public function token($token)
    {
        return $this->__call(__FUNCTION__, [$token,]);
    }

    public function bind($bind)
    {
        return $this->__call(__FUNCTION__, [$bind,]);
    }

    public function result($result)
    {
        return $this->__call(__FUNCTION__, [$result,]);
    }

    public function validate($validate)
    {
        return $this->__call(__FUNCTION__, [$validate,]);
    }

    /**
     *
     * @param $filter
     *
     * @return $this
     */
    public function filter($filter)
    {
        return $this->__call(__FUNCTION__, [$filter,]);
    }

    /**
     *
     * @param $auto
     *
     * @return $this
     */
    public function auto($auto)
    {
        return $this->__call(__FUNCTION__, [$auto,]);
    }

    /**
     *
     * @param $distinct
     *
     * @return $this
     */
    public function distinct($distinct)
    {
        return $this->__call(__FUNCTION__, [$distinct,]);
    }

    /**
     *
     * @param $table
     *
     * @return $this
     */
    public function table($table)
    {
        return $this->__call(__FUNCTION__, [$table,]);
    }

    /**
     *
     * @param $orderBy
     *
     * @return $this
     */
    public function order($orderBy)
    {
        return $this->__call(__FUNCTION__, [$orderBy,]);
    }

    /**
     *
     * @param $alias
     *
     * @return $this
     */
    public function alias($alias)
    {
        return $this->__call(__FUNCTION__, [$alias,]);
    }

    /**
     *
     * @param $having
     *
     * @return $this
     */
    public function having($having)
    {
        return $this->__call(__FUNCTION__, [$having,]);
    }

    /**
     *
     * @param $lock
     *
     * @return $this
     */
    public function lock($lock)
    {
        return $this->__call(__FUNCTION__, [$lock,]);
    }

    /**
     *
     * @param $group
     *
     * @return $this
     */
    public function group($group)
    {
        return $this->__call(__FUNCTION__, [$group,]);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     *
     * @access public
     *
     * @param string $method 方法名称
     * @param array  $args   调用参数
     *
     * @return mixed $this
     */
    public function __call($method, $args)
    {
        $options                = array();
        $options['type']        = 0; // 将java风格的修改为 c风格
        $options['contentType'] = 'value';
        $this->_modifierKeyStyle($args, $options);

        return parent::__call($method, $args);
    }

    /**
     * 查询数据集
     *
     * @access public
     *
     * @param array $options 表达式参数
     *
     * @return mixed
     */
    public function select($options = array())
    {
        $modifierOptions         = array();
        $modifierOptions['type'] = 0; // 将java风格的修改为 c风格
        $this->_modifierKeyStyle($options, $modifierOptions);

        return parent::select($options);
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
        $options         = array();
        $options['type'] = 0; // 将java风格的修改为 c风格
        $this->_modifierKeyStyle($where, $options);
        $this->_modifierKeyStyle($parse, $options);

        return parent::where($where, $parse);
    }

    /**
     * 查询数据
     *
     * @access public
     *
     * @param mixed $options 表达式参数
     *
     * @return mixed
     */
    public function find($options = array())
    {
        return parent::find($options);
    }

    /**
     * 查询成功的回调方法
     *
     * @param $result
     * @param $options
     */
    protected function _after_find(&$result, $options)
    {
        $this->_modifierKeyStyle($result, $options);
    }

    /**
     * 查询成功后的回调方法
     *
     * @param $result
     * @param $options
     */
    protected function _after_select(&$result, $options)
    {
        $this->_modifierKeyStyle($result, $options);
    }

    protected function _modifierKeyStyle(&$resultSet, $options = array())
    {
        return true; // 下面的逻辑暂时不用
        // $key = isset($options['contentType']) ?
        // $options['contentType'] : 'key';
        // $type = isset($options['type']) ? $options['type'] : 1;
        // $ucfirst = isset($options['ucfirst']) ?
        // $options['ucfirst'] : 0;
        // if (function_exists('parse_name_recursive')) {
        // $resultSet = parse_name_recursive($resultSet, $key,
        // $type, $ucfirst);
        // } else {
        // $resultSet = $this->parseNameRecursive($resultSet, $key,
        // $type, $ucfirst);
        // }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array  $originData  源数据
     * @param string $contentType 修改类型 key为 数组的key 其他为value
     * @param int    $type        0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param int    $ucfirst     1: 首字符大写 0:不需要首字母大写
     *
     * @return array|string
     */
    private function parseNameRecursive($originData, $contentType = 'key', $type = 0, $ucfirst = 0)
    {
        $finalData = array();
        if (is_array($originData)) {
            foreach ($originData as $key => $value) {
                unset($originData[$key]);
                if ($contentType === 'key') {
                    $key = parse_name($key, $type, $ucfirst);
                } else {
                    $value = parse_name($value, $type, $ucfirst);
                }
                if (is_array($value)) {
                    $value = $this->parseNameRecursive($value, $contentType, $type, $ucfirst);
                }
                $finalData[$key] = $value;
            }
        } else if (is_string($originData)) {
            $finalData = parse_name($originData, $type, $ucfirst);
        } else {
            $finalData = $originData;
        }

        return $finalData;
    }

    /**
     * 用户获取某些字段
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $where
     * @param null   $field
     * @param string $table
     *
     * @return mixed
     */
    public function getSpecialField($where, $field, $table = '')
    {
        if (empty($table)) {
            $table = $this->getTableName();
        }

        return $this->getData($table, $where, BaseModel::SELECT_TYPE_FIELD, $field);
    }

    /**
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function update($data, $where)
    {
        return $this->table($this->getTableName())->where($where)->save($data);
    }

    // add for RedisDBBaseMobel start

    /**
     * 是否为一张表用户存有多条记录
     *
     * @return bool
     */
    protected function isMulit()
    {
        return null !== $this->_sk;
    }

    /**
     * 通过主键获取数据。
     *
     * @param mixed $pk 主键信息。
     *
     * @return array
     */
    public function getByPk($pk)
    {
        $finalData = array();
        if ($this->isMulit()) {
            $selectType = BaseModel::SELECT_TYPE_ALL;
            $originData = $this->getData($this->tableName, [$this->_pk => $pk,], $selectType);
            foreach ($originData as $k => $data) {
                $key             = isset($data[$this->_sk]) ? $data[$this->_sk] : '';
                $finalData[$key] = $data;
                unset($originData[$k]);
            }
        } else {
            $selectType     = BaseModel::SELECT_TYPE_ONE;
            $originData     = $this->getData($this->tableName, [$this->_pk => $pk,], $selectType);
            $finalData[$pk] = $originData;
        }

        return $finalData;
    }

    /**
     * @param $pk
     *
     * @return array|null
     */
    public function getByPkWithoutKey($pk)
    {
        $res = $this->getByPk($pk);
        if ($this->isMulit()) {
            return $res;
        } else if (isset($res[$pk])) {
            return $res[$pk];
        }

        return $res;
    }

    /**
     * 通过主键获取数据。
     *
     * @param string $pk 主键信息。
     * @param string $sk
     *
     * @return array
     */
    public function getBySk($pk, $sk)
    {
        if ($this->isMulit()) {
            $selectType = BaseModel::SELECT_TYPE_ONE;
            $originData = $this->getData($this->tableName, [$this->_pk => $pk, $this->_sk => $sk,], $selectType);
            return $originData;
        }

        return false;
    }
    // add for RedisDBBaseMobel end

    /**
     * @param $where
     *
     * @return mixed
     */
    public function deleteData($where, $useOldStyle = true)
    {
        if ($useOldStyle) {
            $tableName = $this->tableName;
            if (empty($tableName)) {
                $tableName = $this->getTableName();
            }
            return M($tableName)->where($where)->delete();
        } else {
            return $this->where($where)->delete();
        }
    }

    /**
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function saveData($data, $where)
    {
        $tableName = $this->tableName;
        if (empty($tableName)) {
            $tableName = $this->getTableName();
        }
        return M($tableName)->where($where)->save($data);
    }

    public function getList($where, $field = '')
    {
        return $this->getData($this->tableName, $where, BaseModel::SELECT_TYPE_ALL, $field);
    }

    public function getListWithLimit($where, $limit, $field = '')
    {
        return $this->getData($this->tableName, $where, BaseModel::SELECT_TYPE_ALL, $field, '', $limit);
    }

    public function getOne($where)
    {
        return $this->getData($this->tableName, $where, BaseModel::SELECT_TYPE_ONE);
    }


    /**
     * @return Redis
     */
    public function getRedis()
    {
        $this->_redisLink = new Redis();
        $config = C('REDIS') or die('CONFIG.REDIS is undefined');
        $this->_redisLink->connect($config['host'], $config['port']);
        return $this->_redisLink;
    }
}