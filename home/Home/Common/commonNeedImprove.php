<?php

// 字符串检测
function check_str($str, $rule, &$error = '')
{
    $len = strlen($str);
    $len_cn = mb_strlen($str, 'utf8');
    if (isset($rule['null']) && !$len) {
        return true;
    }
    // 判断为空
    if (! $len) {
        $error = '不能为空';
        return false;
    }
    // 最大字节数
    if (isset($rule['maxlen'])) {
        if ($len > $rule['maxlen']) {
            $error = '不能超过 ' . $rule['maxlen'] . ' 字节';
            return false;
        }
    }
    // 最小字节数
    if (isset($rule['minlen'])) {
        if ($len < $rule['minlen']) {
            $error = '不能少于 ' . $rule['minlen'] . ' 字节';
            return false;
        }
    }
    // 必须字节数
    if (isset($rule['belen'])) {
        if ($len != $rule['belen']) {
            $error = '必须等于 ' . $rule['belen'] . ' 字节';
            return false;
        }
    }
    // 最大字数
    if (isset($rule['maxlen_cn'])) {
        if ($len_cn > $rule['maxlen_cn']) {
            $error = '不能超过 ' . $rule['maxlen_cn'] . ' 字';
            return false;
        }
    }
    // 最小字数
    if (isset($rule['minlen_cn'])) {
        if ($len_cn < $rule['minlen_cn']) {
            $error = '不能少于 ' . $rule['minlen_cn'] . ' 字';
            return false;
        }
    }
    // 必须字数
    if (isset($rule['belen_cn'])) {
        if ($len != $rule['belen_cn']) {
            $error = '必须等于 ' . $rule['belen_cn'] . ' 字';
            return false;
        }
    }
    // 最大值
    if (isset($rule['maxval'])) {
        if ($str > $rule['maxval']) {
            $error = '必须小于等于 ' . $rule['maxval'];
            return false;
        }
    }
    // 最小值
    if (isset($rule['minval'])) {
        if ($str < $rule['minval']) {
            $error = '必须大于等于 ' . $rule['minval'];
            return false;
        }
    }
    // 字符串类型设置
    if (isset($rule['strtype'])) {
        if ($rule['strtype'] == 'number') {
            if (! is_numeric($str)) {
                $error = '必须为数字型';
                return false;
            }
        } elseif ($rule['strtype'] == 'float') {
            if (! is_numeric($str)) {
                $error = '必须为浮点型';
                return false;
            }
            $str = $str / 1;
            if (! is_int($str) && ! is_float($str)) {
                $error = '必须为浮点型';
                return false;
            }
        } elseif ($rule['strtype'] == 'int') {
            if (! is_numeric($str)) {
                $error = '必须为整型';
                return false;
            }
            $str = $str / 1;
            if (! is_int($str)) {
                $error = '必须为整型';
                return false;
            }
        } elseif ($rule['strtype'] == 'alpha') {
            if (! preg_match('/^[a-z]*$/isU', $str)) {
                $error = '必须为字母';
                return false;
            }
        } // 时间类型
elseif ($rule['strtype'] == 'datetime') {
            $data_format = $rule['format'] ? $rule['format'] : 'Ymd';
            if (! check_date($str, $data_format)) {
                $error = '时间格式不对';
                return false;
            }
        } // MD5类型
elseif ($rule['strtype'] == 'md5') {
            if (! preg_match('/^[0-9a-f]{32}$/isU', $str)) {
                $error = '必须为md5';
                return false;
            }
        } // 只能是字符串
elseif ($rule['strtype'] == 'string') {
            if (! is_string($str)) {
                $error = '必须为字符串';
                return false;
            }
        } // email
elseif ($rule['strtype'] == 'email') {
            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $str)) {
                $error = '邮箱格式错误';
                return false;
            }
        } // 手机号
elseif ($rule['strtype'] == 'mobile') {
            if (! preg_match("/^1[34578][0-9]{9}$/", $str)) {
                $error = '格式错误';
                return false;
            }
        } // 价格格式验证 0.00保留2位小数
elseif ($rule['strtype'] == 'price') {
            if (! preg_match("/^(0|[1-9]\d+)\.\d{2}$/", $str)) {
                $error = '价格格式错误';
                return false;
            }
        }
    }
    // 在字符串范围内
    if (isset($rule['inarr']) && $rule['inarr']) {
        if (! in_array($str, $rule['inarr'])) {
            $error = '必须为' . implode(',', $rule['inarr']);
            return false;
        }
    }
    // 正则表达式
    if (isset($rule['regxp']) && $rule['regxp']) {
        if (! preg_match($rule['regxp'], $str)) {
            $error = '格式错误';
            return false;
        }
    }
    return true;
}

/**
 * 验证日期格式
 *
 * @param string $time 要验证的日期
 * @param string $format 要验证的格式
 *
 * @return boolean
 */
function check_date($time, $format = 'Y-m-d')
{
    $reg_arr = array(
        'Y-m-d H:i:s' => "/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", 
        'Y-m-d' => "/^(\d{4})-(\d{2})-(\d{2})$/", 
            'Ymd'         => "/^(\d{4})(\d{2})(\d{2})$/",
    );
    
    if (! isset($reg_arr[$format])) {
        return false;
    }
    
    if (preg_match($reg_arr[$format], $time, $matches)) {
        if (checkdate($matches[2], $matches[3], $matches[1])) {
            return true;
        }
    }
    
    return false;
}

/**
 * 获取各种格式的时间
 * @param number $type 【 0为'Y-m-d H:i:s' 1为'YmdHis' 2为'Y-m-d'】
 * @param string $date 【有则以该时间为准，无则以当前时间为准】
 * @return string
 */
function getTime($type = 0,$date = ''){

    if($date){
        if(!preg_match('/^\d{1,}$/',$date)){
            //不是纯数字就滚
            return '';
        }
        $date = strtotime($date);

    }else{
        $date = time();
    }

    switch($type){
        case 0:
            return date('Y-m-d H:i:s',$date);
            break;
        case 1:
            return date('YmdHis',$date);
            break;
        case 2:
            return date('Y-m-d',$date);
            break;
        default:
            exit;
    }
}

/**
 * todo 如果使用代理的话 估计获取不到真实ip 需要修改
 * @return string
 */
function GetIP()
{
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_REAL_IP") && strcasecmp(getenv("HTTP_X_REAL_IP"), "unknown")) {
        $ip = getenv("HTTP_X_REAL_IP");
    } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
        $ip = getenv("REMOTE_ADDR");
    } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp(
                    $_SERVER['REMOTE_ADDR'],
                    "unknown"
            )
    ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = "unknown";
    }
    return ($ip);
}

