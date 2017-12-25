<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * Mysqli数据库驱动类
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbMysqli extends Db{

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config=''){
        if ( !extension_loaded('mysqli') ) {
            throw_exception(L('_NOT_SUPPERT_').':mysqli');
        }
        if(!empty($config)) {
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   '';
            }
        }
    }

    /**
     * 连接数据库方法
     * @access public
     * @throws ThinkExecption
     */
    public function connect($config='',$linkNum=0) {
        if ( !isset($this->linkInfo[$linkNum]) || $this->linkInfo[$linkNum]['config'] != $config) {
            if(empty($config))  $config =   $this->config;
            $mysqli = new mysqli($config['hostname'],$config['username'],$config['password'],$config['database'],$config['hostport']?intval($config['hostport']):3306);
            if (mysqli_connect_errno()) {
                throw_exception(mysqli_connect_error());
            }
            $dbVersion = $mysqli->server_version;

            // 设置数据库编码
            $mysqli->query("SET NAMES '".C('DB_CHARSET')."'");
            $mysqli->query("SET character_set_client=".C('DB_CHARSET'));
            $mysqli->query("SET character_set_results=".C('DB_CHARSET'));

            //设置 sql_model
            if($dbVersion >'5.0.1'){
                $mysqli->query("SET sql_mode=''");
            }
            // 标记连接成功
            $this->connected    =   true;
            //注销数据库安全信息
            if(1 != C('DB_DEPLOY_TYPE')) {
                unset($this->config);
            }
            $this->linkInfo[$linkNum]['link'] = $mysqli;
            $this->linkInfo[$linkNum]['config'] = $config;
        }
        $this->setLinkNum($linkNum);
        return $this->linkInfo[$linkNum]['link'];
    }

    public function connectOld($config='',$linkNum=0) {
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))  $config =   $this->config;
            //$config['hostname'] = '10.10.1.34';
            $this->linkID[$linkNum] = new mysqli($config['hostname'],$config['username'],$config['password'],$config['database'],$config['hostport']?intval($config['hostport']):3306);
            $this->setLinkConf(
                    array(
                            'host' => $config['hostname'],
                            'user' => $config['username'],
                            'pass' => $config['password'],
                            'db'   => $config['database'],
                            'port' => $config['hostport'] ? intval($config['hostport']) : 3306
                    ),
                    $linkNum
            );
            if (mysqli_connect_errno()) throw_exception(mysqli_connect_error());
            $dbVersion = $this->linkID[$linkNum]->server_version;

            // 设置数据库编码
            $this->linkID[$linkNum]->query("SET NAMES '".C('DB_CHARSET')."'");
            $this->linkID[$linkNum]->query("SET character_set_client=".C('DB_CHARSET'));
            $this->linkID[$linkNum]->query("SET character_set_results=".C('DB_CHARSET'));
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                $this->linkID[$linkNum]->query("SET sql_mode=''");
            }
            // 标记连接成功
            $this->connected    =   true;
            //注销数据库安全信息
            if(1 != C('DB_DEPLOY_TYPE')) unset($this->config);

        }
        $this->setLinkNum($linkNum);
        return $this->linkID[$linkNum];
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free() {
        $this->queryID->free_result();
        $this->queryID = null;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     *
     * @param string $str sql指令
     * @param array  $bind
     * @param bool   $forceQueryMaster
     *
     * @return mixed
     */
    public function query($str, $bind = array(), $forceQueryMaster = true) {
        $master = $this->needQueryFromMaster($str, $forceQueryMaster);
        $this->initConnect($master);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        N('db_query',1);
        // 记录开始执行时间
        G('queryStartTime');
        $this->queryID = $this->_linkID->query($str);
        // 对存储过程改进
        if( $this->_linkID->more_results() ){
            while (($res = $this->_linkID->next_result()) != NULL) {
                $res->free_result();
            }
        }
        $error = null;
        $result = null;
        if ( false === $this->queryID ) {
            $this->error();
            $result = false;
            $numRows = -1;
            $error = $this->error;
        } else {
            $this->numRows  = $this->queryID->num_rows;
            $this->numCols    = $this->queryID->field_count;
            $result = $this->getAll();
            $numRows = $this->numRows;
        }
        $this->debug($numRows, $error);
        return $result;
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @return integer
     */
    public function execute($str) {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        N('db_write',1);
        // 记录开始执行时间
        G('queryStartTime');
        $error = null;
        $ret = null;
        $numRows = null;
        $result =   $this->_linkID->query($str);
        if ( false === $result ) {
            $error = $this->error();
            $ret = false;
            $numRows = -1;
        } else {
            $this->numRows = $this->_linkID->affected_rows;
            $this->lastInsID = $this->_linkID->insert_id;
            $ret = $this->numRows;
            $numRows = $this->numRows;
        }

        $this->debug($numRows, $error);
        return $ret;
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {
        $this->initConnect(true);
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->_linkID->autocommit(false);
        }
        $this->transTimes++;

        $this->queryStr = 'START TRANSACTION';
        $this->debug();

        return ;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->_linkID->autocommit( true);
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }

            $this->queryStr = 'COMMIT';
            $this->debug();
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @param string $sql  sql语句
     * @return array
     */
    private function getAll() {
        //返回数据集
        $result = array();
        if($this->numRows>0) {
            //返回数据集
            for($i=0;$i<$this->numRows ;$i++ ){
                $d = $this->queryID->fetch_assoc();
                $changeKeyCase = function_exists('C') && C('CHANGE_KEY_CASE');
                if (is_array($d) && $changeKeyCase && !isset($d['Field'])) { //排除 show column from 语句结果
                    foreach ($d as $index => $item) {
                        if ($this->needChangeCase($index)) {
                            $d[strtolower($index)] = $item;
                        }
                    }
                }
                $result[$i] = $d;
            }
            $this->queryID->data_seek(0);
        }
        return $result;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getFields($tableName) {
        $info   =   array();
        if (stripos($tableName, '__none__') === false) {
            $tableName = $this->parseKey($tableName);
            preg_match('|\w+|', $tableName, $tableNameList); //去掉表别名
            if (isset($tableNameList[0])) {
                $tableName = $tableNameList[0];
            }
            $result =   $this->query('SHOW COLUMNS FROM '.$tableName, array(), false);
            if($result) {
                foreach ($result as $key => $val) {
                    $needChangeCase = $this->needChangeCase($val['Field']);
                    if ($needChangeCase) {//首字母大写转成小写 因为mycat会将field转为大写，这里转成小写
                        $field = strtolower($val['Field']);
                    } else {
                        $field = $val['Field'];
                    }

                    $info[$field] = array(
                            'name'    => $field,
                            'type'    => $val['Type'],
                            'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                            'default' => $val['Default'],
                            'primary' => (strtolower($val['Key']) == 'pri'),
                            'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                    );
                }
            }
        }

        return $info;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getTables($dbName='') {
        $sql    = !empty($dbName)?'SHOW TABLES FROM '.$dbName:'SHOW TABLES ';
        $result =   $this->query($sql, array(), false);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$key] = current($val);
            }
        }
        return $info;
    }

    /**
     * 替换记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @return false | integer
     */
    public function replace($data,$options=array()) {
        foreach ($data as $key=>$val){
            $value   =  $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[]   =  $value;
                $fields[]   =  $this->parseKey($key);
            }
        }
        $sql   =  'REPLACE INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        return $this->execute($sql);
    }

    /**
     * 插入记录
     * @access public
     * @param mixed $datas 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll($datas,$options=array(),$replace=false) {
        if(!is_array($datas[0])) return false;
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'parseKey'));
        $values  =  array();
        foreach ($datas as $data){
            $value   =  array();
            foreach ($data as $key=>$val){
                $val   =  $this->parseValue($val);
                if(is_scalar($val)) { // 过滤非标量数据
                    $value[]   =  $val;
                }
            }
            $values[]    = '('.implode(',', $value).')';
        }
        $sql   =  ($replace?'REPLACE':'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES '.implode(',',$values);
        return $this->execute($sql);
    }

    /**
     * 关闭数据库
     * @access public
     * @return volid
     */
    public function close() {
        if ($this->_linkID){
            $this->_linkID->close();
        }
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @static
     * @access public
     * @return string
     */
    public function error() {
        $this->error = $this->_linkID->errno.':'.$this->_linkID->error;
        if('' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        trace($this->error,'','ERR');
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @static
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        if($this->_linkID) {
            return  $this->_linkID->real_escape_string($str);
        }else{
            return addslashes($str);
        }
    }

    /**
     * 字段和表名处理添加`
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        $key   =  trim($key);
        if(!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
           $key = '`'.$key.'`';
        }
        return $key;
    }
}