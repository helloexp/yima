<?php
// 判断是否有 定义 _APP_PID_
if (!defined('_APP_PID_')) {
    exit('_APP_PID_未定义，非法进入');
}

// 接口基类，只能继承
abstract class BaseAction extends Action
{

    public $node_id;// 商户号
    public $pos_id;// 终端号
    public $pos_serial;// 机身号
    public $session_id;// session_id
    public $session;// 用户SESSION（类）
    const SESSIONTIMEOUT_RESPONSE_ID = '5555';// 登录超时响应码
    const ERROR_RESPONSE_ID = '9999';// 一般错误响应码
    const ERROR_NET_CODE = '-1';// 网络超时响应码
    const ERROR_DB_CODE = '-2';// 数据库异常响应码
    const ERROR_EXPTION_CODE = '-1';

    // 其它异常响应码
    public function _initialize()
    {
        // 记录日志开始
        Log::$format = '[Y-m-d H:i:s]';
        $this->log('ip:[' . $_SERVER['REMOTE_ADDR'] . ']:' . $_SERVER['QUERY_STRING'], 'REQUEST');

        $this->node_id    = I('node_id'); // 商户号
        $this->pos_id     = I('pos_id'); // 终端号
        $this->pos_serial = I('pos_serial'); // 机身号
        $this->session_id = I('session_id', session_id()); // session_id
        // 重新设置 session 开始
        session_write_close();
        session_id($this->session_id);
        session_start();
        // 重新设置 session结束
    }

    // 返回AJAX信息
    protected function returnAjax($arr)
    {
        // $this->log(print_r($arr,true),'INFO');
        array_walk_recursive($arr, 'BaseAction::Utf8');
        $return = json_encode($arr);
        $this->log($return, 'RESPONSE_INFO');
        if ($this->_get('debug')) {
            tag('view_end');
        }
        G('BeginTime', $GLOBALS['_beginTime']); // 设置项目开始时间
        G('EndTime');
        echo $return;
        $this->log('RUNTIME:' . G('BeginTime', 'EndTime') . ' s');
        exit();
    }

    protected function returnError($message, $respId = self::ERROR_RESPONSE_ID)
    {
        if (!is_array($message)) {
            $message = array(
                    'resp_id'   => $respId,
                    'resp_desc' => $message,
            );
        }
        $this->returnAjax($message);
    }

    protected function returnSuccess($respStr, $respData = null)
    {
        $respId = '0000';
        if (!isset($message) || !is_array($message)) {
            $message = array(
                    'resp_id'   => $respId,
                    'resp_desc' => $respStr,
            );
            // 响应信息
            $message['resp_data'] = $respData;
        }
        $this->returnAjax($message);
    }

    // 创建post请求参数
    protected function httpPost($url, $data = '', &$error = '', $opt = array())
    {
        import('@.ORG.Net.FineCurl') or die('导入包失败');
        $socket = new FineCurl();
        $socket->setopt('URL', $url);
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $this->log('请求：' . $url . '参数：' . $data);

        $result = $socket->send($data);
        $error  = $socket->error();
        // 记录日志
        if ($error) {
            $this->log($error, 'ERROR');
        }
        $this->log(
                '响应：' . (function_exists('mb_convert_encoding') ? mb_convert_encoding(
                        $result,
                        'utf-8',
                        'gbk,utf-8'
                ) : $result)
        );

        return $result;
    }

    // 记录日志
    protected function log($msg, $level = Log::INFO)
    {
        // trace('Log.'.$level.':'.$msg);
        log_write($msg);
    }

    // 获取最后错误
    protected function lastError($err = null)
    {
        static $__lastError;
        $__lastError = $err;
        if ($__lastError) {
            $this->log($err, LOG::ERR);
        }
        if ($err == null) {
            return $__lastError;
        }
    }

    // 自动转换为utf-8
    static function Utf8(&$str)
    {
        $str = mb_convert_encoding($str, 'utf-8', 'utf-8,GBK');
    }

    // 校验是否登录
    protected function checkLogin()
    {
        $this->session = $session = D('UserSess', 'Service')->getSession();
        // 校验是否登录
        if (!$session->isLogin()) {
            $this->returnError('未登录或者已经超时', self::SESSIONTIMEOUT_RESPONSE_ID);
        }
        // 校验请求的终端号是否当前终端
        if ($session->getPosId() != $this->pos_id || // 用户机构号
                $session->getNodeId() != $this->node_id
        ) {
            $this->returnError('终端号或者用户不匹配', self::SESSIONTIMEOUT_RESPONSE_ID);
        }
        // 重新给当前终端，用户，机构 赋值
        $this->pos_id  = $session->getPosId();
        $this->user_id = $session->getUserId();
        $this->node_id = $session->getNodeId();
    }
}

