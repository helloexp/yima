<?php

/**
 * 内置数组过滤函数（都以_filter 开头）
 *
 * @param $val
 * @param $key
 */
function _filter_iconv(&$val, &$key)
{
    $val = iconv('gbk', 'utf-8', $val);
}

/**
 *
 * @param $in_char
 * @param $out_char
 * @param $data
 * @return string
 */
function iconv_arr($in_char, $out_char, &$data)
{
    if (! is_array($data)) {
        return iconv($in_char, $out_char, $data);
    } else {
        foreach ($data as $key => &$val) {
            if (is_array($val)) {
                iconv_arr($in_char, $out_char, $val);
            } else {
                $val = iconv($in_char, $out_char, $val);
            }
        }
        return $data;
    }
}

/**
 *
 * @param $val
 * @param $replace
 * @return mixed
 */
function default_nvl($val, $replace)
{
    if (! $val) {
        return $replace;
    }
    return $val;
}

/**
 *
 * @param $time
 * @return mixed
 */
function date_clean($time)
{
    $arr = array(
        ":", 
        "", 
        "/", 
        "-", 
        " ");
    return str_replace($arr, "", $time);
}

/**
 *
 * @param $string
 * @return string
 */
function date_str($string)
{
    return substr(dateformat($string), 0, 10);
}

/**
 *
 * @param $array
 * @param $k
 * @return string
 */
function getArr_value($array, $k)
{
    if (! is_array($array)) {
        return '';
    } else {
        return $array[$k];
    }
}

/**
 *
 * @param $item
 * @param null $val
 * @param bool|true $return
 *
 * @return null
 */
function get_defined($item, $val = null, $return = true)
{
    static $defined_arr;
    
    if (! isset($defined_arr[$item])) {
        return null;
    } else {
        $item_arr = $defined_arr[$item];
        if ($val == null) {
            return $item_arr;
        } else {
            if ($return) {
                return $item_arr[$val];
            } else {
                echo $item_arr[$val];
            }
        }
    }
}

/**
 *
 * @param $val
 * @param $item
 * @return mixed
 */
function show_defined_text($val, $item)
{
    if (is_array($item)) {
        return isset($item[$val]) ? $item[$val] : $val;
    }
    return $val;
}

/**
 *
 * @param $date1
 * @param $date2
 * @param string $unit
 *
 * @return bool|float
 */
function DateDiff($date1, $date2, $unit = "")
{ // 时间比较函数，返回两个日期相差几秒、几分钟、几小时或几天
    switch ($unit) {
        case 's':
            $dividend = 1;
            break;
        
        case 'i':
            $dividend = 60;
            break;
        
        case 'h':
            $dividend = 3600;
            break;
        
        case 'd':
            $dividend = 86400;
            break;
        
        default:
            $dividend = 86400;
    }
    
    $time1 = strtotime($date1);
    
    $time2 = strtotime($date2);
    
    if ($time1 && $time2) {
        return (float) ($time1 - $time2) / $dividend;
    }
    
    return false;
}

/**
 * 机构类型权限 str- 权限名称
 *
 * @param null $nodeType
 * @param null $str
 *
 * @return bool
 */
function getNodeTypePower($nodeType = null, $str = null)
{
    if (empty($str) || empty($nodeType)) {
        return false;
    }
    $map = array(
        'power_name' => $str, 
        'type' => $nodeType, 
        'status' => '1');
    $resp_count = M('tnode_type_power')->where($map)->count();
    if ($resp_count > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 用户行为日志
 *
 * @param string $log_info 商户操作详情
 * @param string $log_detail 日志详请
 * @param array $option 参数 act_code 行为分析代码
 * @return bool|mixed
 */
function user_act_log($log_info, $log_detail = null, $option = array())
{
    $act_path = GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME;
    $option = array_merge(array(
        'act_code' => ''), $option);
    $act_code = $option['act_code'];
    $userService = D('UserSess', 'Service');
    $userInfo = $userService->getUserInfo();
    $role_info = M()->table('tuser_info as u')
        ->field('n.node_name,r.role_id,r.role_name,u.user_name')
        ->join("trole_info as r on u.role_id = r.role_id")
        ->join("tnode_info as n on n.node_id = u.node_id")
        ->where(
        "u.user_id ='{$userInfo['user_id']}' and n.node_id = '{$userInfo['node_id']}'")
        ->find();
    if (! $userInfo) {
        return false;
    }
    $dataArr = array(
        "user_id" => $userInfo['user_id'], 
        "node_id" => $userInfo['node_id'], 
        "role_id" => $role_info['role_id'], 
        "role_name" => $role_info['role_name'], 
        "user_name" => $role_info['user_name'], 
        "node_name" => $role_info['node_name'], 
        "log_info" => $log_info, 
        "log_detail" => $log_detail, 
        "add_time" => date('YmdHis'), 
        "last_ip" => get_client_ip(), 
        // tr 新增用户行为分析字段
        "act_path" => $act_path, 
        "act_code" => $act_code);
    $result = M('tuser_act_log')->add($dataArr);
    if ($result == false) {
        return false;
    }
    return $result;
}

/**
 * 敏感词校验
 *
 * @param int|string $str 字符串或数组
 * @return bool true 无敏感词 false 含有敏感词
 */
function check_sensitive($str)
{
    $s_words = S('sensitive_words');
    if (! $s_words) {
        $s_words = D('tsensitive_word')->getField("word", true);
        $s_words = implode('|', $s_words);
        S('sensitive_words', $s_words, 60 * 60 * 24);
    }
    if (is_array($str)) {
        foreach ($str as $val) {
            if (! check_sensitive($val)) {
                return false;
            }
        }
    }
    if (is_string($str)) {
        if (preg_match("/(" . $s_words . ")/i", $str, $match) == 1) {
            return false;
            // '包含敏感词：'.$match[0]
        }
    }
    
    return true;
}

/**
 * 移动图片 tmp->Upload/活动/Node ,batch_type :活动类型
 *
 * @param $image_name
 * @param $batch_type
 * @param $node_id
 * @return bool|string
 */
function move_batch_image($image_name, $batch_type, $node_id)
{
    $msg = '方法已禁用' . MODULE_NAME . '/' . ACTION_NAME;
    log_write($msg);
    return $msg;
    if (! $image_name || ! $batch_type) {
        return "需上传图片";
    }
    $path_name_arr = C('BATCH_IMG_PATH_NAME');
    if (! isset($path_name_arr[$batch_type]) || ! $path_name_arr[$batch_type]) {
        return "未设置上传地址！";
    }
    
    if (! is_dir(
        APP_PATH . 'Upload/' . $path_name_arr[$batch_type] . '/' . $node_id)) {
        mkdir(
            APP_PATH . 'Upload/' . $path_name_arr[$batch_type] . '/' . $node_id, 
            0777);
    }
    $old_image_url = APP_PATH . 'Upload/img_tmp/' . $node_id . '/' .
         basename($image_name);
    $new_image_url = APP_PATH . 'Upload/' . $path_name_arr[$batch_type] . '/' .
         $node_id . '/' . basename($image_name);
    
    if (file_exists($new_image_url)) {
        return true;
    } else {
        $flag = rename($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法";
        }
    }
}

/**
 * 生成支撑嵌套页面链接
 *
 * @param string $backurl
 * @param string $other_param
 *
 * @return mixed|string
 */
function get_iss_page_url($backurl = '', $other_param = '')
{
    $userService = D('UserSess', 'Service');
    $sso = $userService->initSso();
    $userinfo = $userService->getUserInfo();
    $url = str_replace(
        array(
            '[token]', 
            '[node_id]', 
            '[node_name]', 
            '[user_id]', 
            '[user_name]'), 
        array(
            $userinfo['token'], 
            $userinfo['node_id'], 
            $userinfo['name'], 
            $userinfo['user_id'], 
            $userinfo['user_name']), C('ISS_TOKEN_LOGIN_URL') . $other_param);
    
    if ($backurl != '') {
        $url .= '&backurl=' . urlencode($backurl);
    }
    return $url;
}

/**
 * 传入机构号和订单金额 获取运费
 *
 * @param $node_id
 * @param $orderAmt
 * @return int
 */
function getShippingFee($node_id, $orderAmt)
{
    $shippingFee = 0;
    
    $queryMap = array(
        "node_id" => $node_id);
    $shippingConfig = M("tecshop_config")->where($queryMap)->find();
    $shippingFee = $shippingConfig['freight'];
    if ($shippingConfig['freight_free_flag'] == 1) {
        if ($orderAmt >= $shippingConfig['freight_free_limit']) {
            $shippingFee = 0;
        }
    }
    return $shippingFee;
}

/**
 * 判断是否有支撑权限
 *
 * @return bool
 */
function _hasIss()
{
    $userService = D('UserSess', 'Service');
    if (! $userService->isLogin()) {
        return false;
    }
    $userInfo = $userService->getUserInfo();
    $nodeId = $userInfo['node_id'];
    if ($nodeId == '00000000') {
        return true;
    }
    
    $map = array(
        'node_id' => $nodeId);
    // $clientType=M('tnode_info')->field('yzxt_client_type')->where($map)->find();
    $clientType = get_node_info($nodeId, 'yzxt_client_type');
    if (isset($clientType['yzxt_client_type']) && $clientType['yzxt_client_type'] === '0') {
        return true;
    }
    $v = get_wc_version();
    return in_array($v, array(
        'v1', 
        'v2', 
        'v3', 
        'v4'), true);
}

/**
 *
 * @return bool
 */
function in_wx()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
}

/**
 * ***********************交行转账调用函数**********************************
 */
/**
 * [transferAccounts 转账]
 *
 * @param [type] $bankName [对方开户银行]
 * @param [type] $acno [对方的银行账号]
 * @param [type] $acname [对方的户名]
 * @param [type] $amt [转账金额，保留2位小数]
 * @param integer $bankFlag [0是同行，1是跨行]
 * @param integer $areaFlag [0是同城，1是异地]
 * @param [type] $summary [转账说明，例如：退款100.00元]
 * @return [type] [成功返回true，失败为false]
 */
function transferAccounts($bankName, $acno, $acname, $amt, $bankFlag = 0, 
    $areaFlag = 0, $summary)
{
    if (! $bankName || ! $acno || ! $acname || ! $amt || ! $summary) {
        die("参数不得为空");
    }
    $errorInfo = "";
    $req_array = array(
        'rcv_bank_name' => $bankName, 
        'rcv_acno' => $acno, 
        'rcv_acname' => $acname, 
        'amt' => $amt, 
        'summary' => $summary, 
        'bank_flag' => $bankFlag, 
        'area_flag' => $areaFlag);
    $BankRequest = D('Bank', 'Service');
    $result = $BankRequest->transferAccounts($req_array, $errorInfo);
    return $result;
}

/**
 * [getBankAccountInfo 获取账户信息]
 *
 * @return [type] [失败返回false，成功返回数组]
 */
function getBankAccountInfo()
{
    $errorInfo = "";
    $BankRequest = D('Bank', 'Service');
    $result = $BankRequest->getAccountInfo($errorInfo);
    return $result;
}

/**
 * [getTradingInfo 查询历史交易信息]
 *
 * @param [type] $startDate [开始时间]
 * @param [type] $endDate [结束时间]
 * @return [type] [失败返回false，成功返回二维数组]
 */
function getTradingInfo($startDate, $endDate)
{
    if (! $startDate || ! $endDate) {
        die("参数不得为空");
    }
    $errorInfo = "";
    $req_array = array(
        'start_date' => $startDate, 
        'end_date' => $endDate);
    $BankRequest = D('Bank', 'Service');
    $result = $BankRequest->getTradingInfo($req_array, $errorInfo);
    return $result;
}

/**
 * [getTodayTradingInfo 获取当日交易记录]
 *
 * @return [type] [失败返回false，成功返回二维数组]
 */
function getTodayTradingInfo()
{
    $errorInfo = "";
    $BankRequest = D('Bank', 'Service');
    $result = $BankRequest->getTodayTradingInfo($errorInfo);
    return $result;
}

/**
 * [getRemainMoney 获取历史余额信息]
 *
 * @param [type] $beginDate [开始日期]
 * @param [type] $endDate [结束日期]
 * @return [type] [失败返回false，成功返回数组]
 */
function getRemainMoney($beginDate, $endDate)
{
    $errorInfo = "";
    $req_array = array(
        'beginDate' => $beginDate, 
        'endDate' => $endDate);
    $BankRequest = D('Bank', 'Service');
    $result = $BankRequest->getRemainMoney($req_array, $errorInfo);
    return $result;
}
