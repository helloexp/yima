<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> 2016-04-13
 */
class BaseService
{
    const ERROR_RESPONSE_ID = 9999;

    /**
     * @var WeiXinCardService
     */
    protected $WeiXinCardService;

    /**
     *
     * @param        $respStr
     * @param null   $respData
     *
     * @param string $type
     * @param bool   $return
     *
     * @return string
     */
    protected function returnSuccess($respStr, $respData = null, $type = 'JSON', $return = false)
    {
        $respId = '0000';
        if (!isset($message) || !is_array($message)) {
            $message              = array(
                    'resp_id'   => $respId,
                    'resp_desc' => $respStr,
            );
            $message['resp_data'] = $respData;
        }
        return $this->returnAjax($message, $type, $return);
    }

    /**
     *
     * @param            $message
     * @param int|string $respId
     * @param string     $type
     * @param bool       $return
     *
     * @return bool
     */
    protected function returnError($message, $respId = self::ERROR_RESPONSE_ID, $type = 'EVAL', $return = true)
    {
        if (!is_array($message)) {
            $message = array(
                    'resp_id'   => $respId,
                    'resp_desc' => $message,
            );
        }
        return $this->returnAjax($message, $type, $return);
    }

    /**
     * @param        $errno
     * @param array  $other
     *
     * @param string $type
     * @param bool   $return
     *
     * @return string
     */
    protected function returnErrorByErrno($errno, $other = [], $type = 'EVAL', $return = true)
    {
        switch ($errno) {
            case 1000:
                $msg = '未中奖';
                break;
            case 1001:
                $msg = '所有奖品已发完';
                break;
            case 1002:
                $msg = '当日奖品已发完';
                break;
            case 1003:
                $msg = '当日此号码[' . $other['phone_no'] . ']已达中奖上限';
                break;
            case 1005:
                $msg = '当日此号码[' . $other['phone_no'] . ']已达抽奖上限';
                break;
            case 1006:
                $msg = '[' . $other['id'] . ']当日奖品已发完';
                break;
            case 1007:
                $msg = '该会员不能中该奖品';
                break;
            case 1009:
                $msg = '该会员不能中该奖品';
                break;
            case 1010:
                $msg = '该微信用户非关注会员';
                break;
            case 1012:
                $msg = '不能参与该活动';
                break;
            case 1014:
                $msg = '此号码[' . $other['phone_no'] . ']已达中奖上限';
                break;
            case 1016:
                $msg = '此号码[' . $other['phone_no'] . ']已达抽奖上限';
                break;
            case 1017:
                $msg = '[' . $other['id'] . ']奖品已发完';
                break;
            case 1018:
                $msg = '[' . $other['id'] . ']奖品日中奖次数达到上限';
                break;
            case 1019:
                $msg = '[' . $other['id'] . ']奖品总中奖次数达到上限';
                break;
            case 1020:
                $msg = '[' . $other['id'] . ']奖品库存不足';
                break;
            case 1030:
            case 1031:
                $msg = '您未中奖';
                break;
            case 1032:
                $msg = '该小票已超过参与次数限制';
                break;
            case 1051:
                $msg = '该正常订单[' . $other['pay_token'] . ']不存在';
                break;
            case 1053:
                $msg = '该正常订单[' . $other['pay_token'] . ']已使用过';
                break;
            case 1054:
                $msg = '该正常订单[' . $other['pay_token'] . ']系统错误';
                break;
            case 1055:
                $msg = '该正常订单[' . $other['pay_token'] . ']系统错误';
                break;
            case 1060:
                $msg = '该电子券[' . $other['goods_id'] . ']不存在';
                break;
            case 1061:
                $msg = '该bonus_detail[' . $other['bonus_id'] . ']不存在';;
                break;
            case 1062:
                $msg = 'bonus_detail 更新失败[' . $other['bonus_id'] . ']不存在';
                break;
            case 1063:
                $msg = 'tbonus_use_detail 插入[' . $other['bonus_id'] . ']不存在';
                break;
            case 1201:
                $msg = '库存更新失败';
                break;
            case 1301:
                $msg = '旺财发码失败-微信卡券发码没有open_id';
                break;
            case 9901:
                $msg = '抽奖活动[' . $other['id'] . ']不存在';
                break;
            case 9902:
            case 9903:
                $msg = '非常抱歉，您访问的活动只有微信粉丝才能参加，敬请谅解^_^!';//-1025
                break;
            case 9904:
                $msg = '非常抱歉，手机号格式有误!';//-1025
                break;
            case 9905:
                $msg = '活动不再有效期!';//-1005
                break;
            case 9906:
                $msg = '无效活动!';//-1027
                break;
            case 9907:
                $msg = '不是抽奖活动!';//-1028
                break;
            case 9908:
                $msg = '抽奖类型不正确!';//-1029
                break;
            case 9909: //7001
                $msg = '抽奖规则[' . $other['cj_rule_id'] . ']不存在';//原来为7001
                break;
            case 9910: //7002
                $msg = '抽奖规则[' . $other['cj_rule_id'] . ']不存在';//原来为7002
                break;
            case 9911: //8001
                $msg = '非常抱歉，该手机号暂不能参与此次抽奖!';//-1030  8001
                break;
            case 9912: //7003
                $msg = '抽奖活动[' . $other['cj_rule_id'] . ']不存在';//7003
                break;

            default:
                $msg = $errno;
        }
        return $this->returnError($msg, $errno, $type, $return);
    }

    /**
     * @param        $msg
     * @param string $level
     */
    protected function log($msg, $level = Log::INFO)
    {
        log_write($msg, $level);
    }

    /**
     * 返回AJAX信息
     *
     * @param        $data
     * @param string $type JSON:json格式 EVAL:原样输出
     * @param bool   $return
     *
     * @return bool
     */
    protected function returnAjax($data, $type = 'JSON', $return = true)
    {
        file_debug(__METHOD__ . '$data:' . var_export($data, 1) . PHP_EOL, 'returnAjax', 'returnAjax.log');
        if ($return) {
            if (empty($type)) {
                $type = C('DEFAULT_AJAX_RETURN');
            }
            switch (strtoupper($type)) {
                case 'JSON' :// 返回JSON数据格式到客户端 包含状态信息
                    return json_encode($data);
                case 'XML'  :// 返回xml格式数据
                    return xml_encode($data);
                case 'JSONP':// 返回JSON数据格式到客户端 包含状态信息
                    $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C(
                            'DEFAULT_JSONP_HANDLER'
                    );
                    return $handler . '(' . json_encode($data) . ');';
                case 'EVAL' :// 返回可执行的js脚本
                    return $data;
                default     :// 用于扩展其他返回格式数据
                    tag('ajax_return', $data);
            }
            return false;
        } else {
            $this->ajaxReturn($data, $type);
            return true;
        }
    }


    /**
     * Ajax方式返回数据到客户端
     * @access protected
     *
     * @param mixed  $data 要返回的数据
     * @param String $type AJAX返回数据格式
     *
     * @return void
     */
    protected function ajaxReturn($data, $type = '')
    {
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }
        switch (strtoupper($type)) {
            case 'JSON' :// 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:text/html; charset=utf-8');
                exit(json_encode($data));
            case 'XML'  :// 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':// 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C(
                        'DEFAULT_JSONP_HANDLER'
                );
                exit($handler . '(' . json_encode($data) . ');');
            case 'EVAL' :// 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :// 用于扩展其他返回格式数据
                tag('ajax_return', $data);
        }
    }

    /**
     * node_id=00004488&open_id=adfadsf124s5o&batch_info_id=12941&data_from=54&request_id=6c335f35df8c40acd7e3710a75c6eb16
     * 微信卡券发送
     *
     * @param        $node_id
     * @param        $open_id
     * @param        $batch_info_id
     * @param        $data_from
     * @param        $request_id
     * @param string $type
     *
     * @return bool|string
     */
    public function requestWcWeiXinServ($node_id, $open_id, $batch_info_id, $data_from, $request_id, $type = 'EVAL')
    {
        if (empty($this->WeiXinCardService)) {
            $this->WeiXinCardService = D('WeiXinCard', 'Service');
        }

        $this->WeiXinCardService->init_by_node_id($node_id);

        $rs = $this->WeiXinCardService->add_assist_number_nostore_for_award(
                $open_id,
                $batch_info_id,
                $data_from,
                $request_id
        );

        if ($rs === false) {
            return $this->returnError($this->WeiXinCardService->error, self::ERROR_RESPONSE_ID, $type);
        } else {
            return $this->returnSuccess('ok', ["card_info" => $rs], $type, true);
        }
    }
}