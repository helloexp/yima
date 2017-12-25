<?php

/**
 * 订单号生成
 *
 * @return string
 */
function get_sn() {
    return date('ymd') . substr(time(), -5) . substr(microtime(), 2, 5);
}

/**
 * Description of SkuService 积分名称获取
 *
 * @param string $nodeId //唯一商户ID
 *
 * @return string
 * @author john_zeng
 */
function integralSetName($nodeId) {
    // 积分名称获取
    $integralName = session('userSessIntegralName');
    if (!$integralName) {
        $integralName = M("tintegral_node_config")->where(array(
                'node_id' => $nodeId,
        ))->getField('integral_name');
        session('userSessIntegralName', $integralName);
    }
    if ($integralName) {
        L('INTEGRAL_NAME', $integralName);
    } else {
        L('INTEGRAL_NAME', '积分');
    }
}

/**
 * 发码获取request_id
 */
function get_request_id() {
    return get_sn();
}

/**
 * 字符串截取，支持中文和其他编码
 *
 * @static
 *
 * @access public
 *
 * @param string      $str     需要转换的字符串
 * @param int|string  $start   开始位置
 * @param string      $length  截取长度
 * @param string      $charset 编码格式
 * @param bool|string $suffix  截断显示字符
 *
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if (mb_strlen($slice, $charset) >= mb_strlen($str, $charset)) {
        return $str;
    }

    return $suffix ? $slice . '...' : $slice;
}

/**
 * 时间格式化处理
 * @author John Zeng<zengc@imageco.com.cn>
 *
 * @param $time    时间
 * @param $format  格式
 * @param $other   时分秒格式
 *
 * @return $date
 */
function dateformat($str, $format = 'Y-m-d H:i:s', $other = '') {
    $date = trim($str);
    if (!$date) {
        return false;
    }
    $date = strtotime($date);
    if (!$date) {
        return $str;
    }
    if ('' != $other) {
        $date = strtotime(date('Y-m-d', $date) . $other);
    }
    if ($format == 'defined1') {
        // 今天
        $day  = date('Ymd', $date);
        $time = date('H:i', $date);
        if ($day == date('Ymd', strtotime("-1 day"))) {
            $time = '昨天&nbsp;&nbsp;&nbsp;' . $time;
        } else if ($day != date('Ymd')) {
            $week = array(
                    '星期日',
                    '星期一',
                    '星期二',
                    '星期三',
                    '星期四',
                    '星期五',
                    '星期六',
            );
            $time = $week[date('w', $date)] . $time;
        }
        $date = $time;
    } else {
        if ('' == $format) {
            $format = 'YmdHis';
        }
        $date = date($format, $date);
        if ($other != '') {
            $date .= $other;
        }
        if (strpos($date, '1970') === 1) {
            return $str;
        }
    }

    return $date;
}

/**
 * 发送 http post请求
 *
 * @param       $url
 * @param null  $data
 * @param null  $error
 * @param array $opt
 *
 * @return mixed
 */
function httpPost($url, $data = null, &$error = null, $opt = array()) {
    $opt = array_merge(array(
            'TIMEOUT'    => 30,
            'METHOD'     => 'POST',
            'LOG_RECORD' => true,
    ), $opt);
    // 创建post请求参数
    import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
    $socket = new FineCurl();
    $url = trim($url);
    $socket->setopt('URL', $url);
    $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
    $socket->setopt('HEADER_TYPE', $opt['METHOD']);
    if (is_array($data)) {
        $data = http_build_query($data);
    }
    if ($opt['METHOD'] == 'GET') {
        log_write('request:' . $url . '&' . $data, 'REMOTE');
    } else {
        log_write('request:' . $url . ' POST:' . $data, 'REMOTE');
    }
    $result = $socket->send($data);
    $error  = $socket->error();

    //log_write('查看微信响应结果:'.var_export($result));

    // 记录日志
    if ($error) {
        log_write($error, 'ERROR');
    }
    // 如果记录日志
    if (!empty($opt['LOG_RECORD'])) {
        log_write('response:' . (function_exists('mb_convert_encoding') ? mb_convert_encoding($result, 'utf-8',
                        'utf-8,gbk') : $result), 'REMOTE');
    }

    return $result;
}

/**
 * 发送 http get请求
 *
 * @param       $url
 * @param null  $data
 * @param null  $error
 * @param array $opt
 *
 * @return mixed
 */
function httpGet($url, $data = null, &$error = null, $opt = array()) {
    $opt = array_merge(array(
            'TIMEOUT' => 30,
            'METHOD'  => 'GET',
    ), $opt);
    // 创建post请求参数
    import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
    $socket = new FineCurl();
    $socket->setopt('URL', $url);
    $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
    $socket->setopt('HEADER_TYPE', $opt['METHOD']);
    if (is_array($data)) {
        $data = http_build_query($data);
    }
    Log::write('请求：' . $url . '参数：' . $data, 'REMOTE');
    $result = $socket->send($data);
    $error  = $socket->error();
    // 记录日志
    if ($error) {
        Log::write($error, 'ERROR');
    }

    return $result;
}

// 下载数据
function querydata_download(
        $sql,
        $cols_arr,
        $mysql,
        $filename = null,
        $xlsFilename = null,
        $isReturn = false
) {
    import('@.ORG.Net.querydata') or die('[@.ORG.Net.querydata]导入包失败');

    return QueryData::downloadData($sql, $cols_arr, $mysql, $filename, $xlsFilename, $isReturn);
}

/**
 * 提取数组中值为键值 比如 : $array=array(0=>array('a'=>1,'b'=>2),1=>array('a'=>2,'b'=>3));
 * $arr = array_valtokey($array,'a'); //$arr 值为
 * array(1=>array('a'=>1,'b'=>2),2=>array('a'=>2,'b'=>3)) $arr =
 * array_valtokey($array,'a','b');//$arr值为 array(1=>2,2=>3);
 *
 * @param array  $array 目标数组
 * @param string $key   键名
 * @param string $val   值名
 *
 * @return array
 */
function array_valtokey($array, $key = '', $val = null) {
    $arr = array();
    if(is_array($array)){
        foreach ($array as $value) {
            if ($key) {
                if (is_string($val)) {
                    $arr[$value[$key]] = $value[$val];
                } else {
                    $arr[$value[$key]] = $value;
                }
            } else { // 默认下标
                if (is_string($val)) {
                    $arr[] = $value[$val];
                } else {
                    $arr[] = $value;
                }
            }
        }
    }
    return $arr;
}

/**
 * 根据键名对二维数组排序
 *
 * @param        $arr
 * @param        $keys
 * @param string $type
 *
 * @return array
 */
function array_sort($arr, $keys, $type = 'desc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[$k] = $arr[$k];
    }

    return $new_array;
}

/**
 * 数组生成OPTION
 *
 * @param array   $arr    显示列表
 * @param string  $val    选中项的值
 * @param boolean $return 是否返回
 *
 * @return mixed
 */
function show_arr_opt($arr, $val = null, $return = false) {
    if (!is_array($arr)) {
        return false;
    }
    $re_str = '';
    foreach ($arr as $key => $var) {
        $re_str .= '<option value="' . $key . '"';
        if (in_array(strval($key), (array)$val, true)) {
            $re_str .= ' selected="selected" ';
        }
        $re_str .= '>';
        $re_str .= $var;
        $re_str .= '</option>';
    }
    if ($return) {
        return $re_str;
    }
    echo $re_str;
}

function show_arr_opt1($arr, $val = null, $return = false) {
    if (!is_array($arr)) {
        return false;
    }
    $re_str = '';
    foreach ($arr as $key => $var) {
        $re_str .= '<option value="' . $key . '"';
        if (in_array(strval($key), (array)$val, true)) {
            $re_str .= ' selected="selected" ';
        }
        else
            $re_str .= 'disabled';
        $re_str .= '>';
        $re_str .= $var;
        $re_str .= '</option>';
    }
    if ($return) {
        return $re_str;
    }
    echo $re_str;
}
/**
 * 获取smil配置文件，参数：图片绝对路径，可为数组
 *
 * @param $imgs
 *
 * @return bool|string
 */
function create_smil_cfg($imgs) {
    $tmp_path = C('DOWN_TEMP');
    $img_xml  = '';
    if (is_array($imgs)) {
        foreach ($imgs as $img) {
            $info = pathinfo($img);
            $ex   = $info['extension'];
            $img_xml .= '<par dur="4s"><img src="1.' . $ex . '" region="Image" /></par>';
        }
    } else if (is_string($imgs)) {
        $info    = pathinfo($imgs);
        $ex      = $info['extension'];
        $img_xml = '<par dur="4s"><img src="1.' . $ex . '" region="Image" /></par>';
    } else {
        return false;
    }

    $smil_content = '<smil><head><layout><region id="Image" left="20" top="10" fit="hidden" /><region id="Text" left="10" top="60" fit="hidden"/></layout></head><body>' . $img_xml . '<par dur="10s"><text src="notes.txt" region="Text"/></par><par dur="90s"><img src="dm.wbmp" region="Image" /></par></body></smil>';

    $smil_cfg_name = $tmp_path . md5(time() . rand(0, 99999)) . '.smil';
    file_put_contents($smil_cfg_name, $smil_content);

    return $smil_cfg_name;
}

/**
 * 商户操作日志
 *
 * @param string $log_info   商户操作详情
 * @param string $log_detail 日志详请
 * @param array  $option     日志类型
 *
 * @return mixed
 */
function node_log($log_info, $log_detail = null, $option = array()) {
    $act_path    = GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME;
    $option      = array_merge(array(
            'log_type' => $act_path,
    ), $option);
    $log_type    = $option['log_type'];
    $userService = D('UserSess', 'Service');
    $userInfo    = $userService->getUserInfo();
    $role_info   = M()->table('tuser_info as u')->field('n.node_name,r.role_id,r.role_name,u.user_name')->join("trole_info as r on u.role_id = r.role_id")->join("tnode_info as n on n.node_id = u.node_id")->where("u.user_id ='{$userInfo['user_id']}' and n.node_id = '{$userInfo['node_id']}'")->find();
    if (!$userInfo) {
        return false;
    }
    if ($log_type == 'LOGIN') {
        $p_log_id = 0;
    } else {
        $p_log_id = session('USER_LOGIN_P_LOG_ID');
        // 更新用户操作次数
        M('tweb_log_info')->where(array(
                'log_id'  => $p_log_id,
                'user_id' => $userInfo['user_id'],
        ))->save(array(
                'act_count' => array(
                        'exp',
                        'act_count+1',
                ),
        ));
    }
    $dataArr = array(
            "user_id"    => $userInfo['user_id'],
            "node_id"    => $userInfo['node_id'],
            "role_id"    => $role_info['role_id'],
            "role_name"  => $role_info['role_name'],
            "user_name"  => $role_info['user_name'],
            "node_name"  => $role_info['node_name'],
            "log_info"   => $log_info,
            "log_detail" => $log_detail,
            "add_time"   => date('YmdHis'),
            "last_ip"    => get_client_ip(),
        // tr 新增用户行为分析字段
            "p_log_id"   => $p_log_id,
            "act_path"   => $act_path,
            "log_type"   => $log_type,
    );
    $result  = M('tweb_log_info')->add($dataArr);
    if ($result == false) {
        return false;
    }
    if ($log_type == 'LOGIN') {
        session('USER_LOGIN_P_LOG_ID', $result);
    }

    return $result;
}

// 汉字转拼音开始
function Pinyin($_String, $_Code = 'gb2312') {
    $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" . "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" . "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" . "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" . "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" . "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" . "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" . "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" . "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" . "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" . "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" . "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" . "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" . "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" . "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" . "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";

    $_DataValue  = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" . "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" . "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" . "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" . "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" . "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" . "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" . "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" . "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" . "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" . "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" . "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" . "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" . "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" . "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" . "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" . "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" . "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" . "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" . "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" . "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" . "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" . "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" . "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" . "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" . "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" . "|-10270|-10262|-10260|-10256|-10254";
    $_TDataKey   = explode('|', $_DataKey);
    $_TDataValue = explode('|', $_DataValue);

    $_Data = (PHP_VERSION >= '5.0') ? array_combine($_TDataKey, $_TDataValue) : _Array_Combine($_TDataKey,
            $_TDataValue);
    arsort($_Data);
    reset($_Data);

    if ($_Code != 'gb2312') {
        $_String = _U2_Utf8_Gb($_String);
    }
    $_Res = '';
    for ($i = 0; $i < strlen($_String); $i++) {
        $_P = ord(substr($_String, $i, 1));
        if ($_P > 160) {
            $_Q = ord(substr($_String, ++$i, 1));
            $_P = $_P * 256 + $_Q - 65536;
        }
        $_Res .= _Pinyin($_P, $_Data);
    }

    return preg_replace("/[^a-z0-9]*/", '', $_Res);
}

function _Pinyin($_Num, $_Data) {
    if ($_Num > 0 && $_Num < 160) {
        return chr($_Num);
    } elseif ($_Num < -20319 || $_Num > -10247) {
        return '';
    } else {
        $k = '';
        foreach ($_Data as $k => $v) {
            if ($v <= $_Num) {
                break;
            }
        }

        return $k;
    }
}

function _U2_Utf8_Gb($_C) {
    $_String = '';
    if ($_C < 0x80) {
        $_String .= $_C;
    } elseif ($_C < 0x800) {
        $_String .= chr(0xC0 | $_C >> 6);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x10000) {
        $_String .= chr(0xE0 | $_C >> 12);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x200000) {
        $_String .= chr(0xF0 | $_C >> 18);
        $_String .= chr(0x80 | $_C >> 12 & 0x3F);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    }

    return iconv('UTF-8', 'GB2312', $_String);
}

function _Array_Combine($_Arr1, $_Arr2) {
    $_Res = array();
    for ($i = 0; $i < count($_Arr1); $i++) {
        $_Res[$_Arr1[$i]] = $_Arr2[$i];
    }

    return $_Res;
}

// 汉字转拼音结束

// unicode字符转可见
function unicodeDecode($name) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
            create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'),
            $name);
}

/* ----闭合上面的引号用" */

// 敏感词检查
function has_fuck_word() {
    // 用|线隔开
    // 先查有没有缓存
    $fullminGanCi = cache('fuckword_cache');
    if (!$fullminGanCi) {
        $fullminGanCi = file_get_contents(dirname(__FILE__) . '/fuckword.txt');
        // $fullminGanCi =
        // '阿扁推翻|阿宾|阿賓|挨了一炮|爱液横流|安街逆|安局办公楼|安局豪华|安门事|安眠藥|案的准确|八九民|八九学|八九政治|把病人整|把邓小平|把学生整|';

        if (!$fullminGanCi) {
            return false;
        }

        $fullminGanCi = Addcslashes($fullminGanCi, '^$().[]*?+-\{}\'"/');
        // 记缓存，以免下回再做替换
        cache('fuckword_cache', $fullminGanCi);
    }
    $str = serialize(array_values($_POST));

    $from  = 0;
    $len   = strlen($fullminGanCi);
    $break = 0;
    $cut   = 1024;
    do {
        $cutStr = substr($fullminGanCi, $from, $cut);
        if ($from >= $len) {
            $break = 1;
        } else {
            $arr = explode("|", $cutStr);
            if (count($arr) > 1) {
                unset($arr[count($arr) - 1]);
            }
            if (!$arr) {
                break;
            }
            $cutStr = implode("|", $arr);
            $from   = $from + strlen($cutStr) + 1;
            // 这儿处理敏感词
            preg_match_all("/" . $cutStr . "/isU", $str, $out);
            if (@count($out[0]) > 0) {
                return array_unique($out[0]);
            }
        }
    } while (!$break);

    return false;
}

/**
 * 数组生成deliveryOPTION
 *
 *
 * @param array   $arr    显示字符串 0-1
 * @param string  $val    选中项的值
 * @param boolean $return 是否返回
 *
 * @return string 0 消费者自提 1 物流配送
 */
function show_delivery_opt($arr, $val = null, $return = false) {
    if ($arr == null) {
        return false;
    }
    $array   = explode('-', $arr);
    $tmp_arr = array();
    foreach ($array as $v) {
        if ($v == 0) {
            $tmp_arr[$v] = "消费者自提";
        }
        if ($v == 1) {
            $tmp_arr[$v] = "物流配送";
        }
    }
    show_arr_opt($tmp_arr);
}

/*
 * 获取活动发布渠道的id batch_id tmarketing_info id channel_id 渠道号 batch_type 活动类型
 * node_id 机构号
 */
function get_batch_channel($batch_id, $channel_id, $batch_type, $node_id) {
    if (!$channel_id) {
        return false;
    }

    $batch_channel_model = M('tbatch_channel');
    $map                 = array(
            'node_id'    => $node_id,
            'batch_id'   => $batch_id,
            'channel_id' => $channel_id,
            'batch_type' => $batch_type,
    );
    $label_id            = $batch_channel_model->where($map)->getField('id');
    if (!$label_id) {
        $data_arr = array(
                'batch_type'  => $batch_type,
                'batch_id'    => $batch_id,
                'channel_id'  => $channel_id,
                'status'      => '1',
                'add_time'    => date('YmdHis'),
                'click_count' => 0,
                'cj_count'    => 0,
                'send_count'  => 0,
                'node_id'     => $node_id,
        );
        $query    = $batch_channel_model->add($data_arr);
        if ($query) {
            node_log('创建标签成功');

            return $query;
        } else {
            node_log('创建标签失败');
            $this->error('创建标签失败');

            return false;
        }
    } else {
        return $label_id;
    }
}

/**
 * 获取旺财版本 v0 免费版 v1 标准版 v2 电商版 v3 全民营销版 v4 演示版 v5 微博版 v6 凭证活动版 v7 凭证版
 */
function get_wc_version($nodeId = null) {
    // 获取登陆机构
    if ($nodeId === null) {
        $userService = D('UserSess', 'Service');
        $userInfo    = $userService->getUserInfo();
        if (empty($userInfo['node_id'])) {
            return false;
        }
        $nodeId = $userInfo['node_id'];
    }
    // $wcVersion = M('tnode_info')->where("node_id =
    // '{$nodeId}'")->getField('wc_version');
    $wcVersion = get_node_info($nodeId, 'wc_version');
    if (!$wcVersion) {
        $wcVersion = 'v0';
    }

    return $wcVersion;
}

/**
 * 获取旺财版本名称
 */
function get_wc_version_name($v = null) {
    $version_arr = array(
            'v0'   => '旺财免费版',
            'v0.5' => '旺财认证版',
            'v1'   => '旺财标准版',
            'v2'   => '旺财电商版',
            'v3'   => '旺财全民营销版',
            'v4'   => '旺财演示版',
            'v5'   => '旺财微博版',
            'v6'   => '旺财凭证活动版',
            'v7'   => '旺财凭证版',
    );

    return $version_arr[$v ? $v : get_wc_version()];
}

/**
 * 将字符串或数字转成2位小数的值
 */
function decimal($val, $precision = 0) {
    $val = floatval($val);
    if ((float)$val) :
        $val = round((float)$val, (int)$precision);
        list ($a, $b) = explode('.', $val);
        if (strlen($b) < $precision) {
            $b = str_pad($b, $precision, '0', STR_PAD_RIGHT);
        }

        return $precision ? "$a.$b" : $a;
    else :
        return $val;
    endif;
}

/**
 * 记录文本日志
 *
 * @author zengc
 *
 * @param string $content  内容
 * @param string $level    级别
 * @param string $log_name 日志文件名（不包括日期）
 */
function log_write($content, $level = '', $log_name = '') {
    static $__APP_LOG_PID__; // 进程号
    $first = '[PAGE:' . GROUP_NAME . DIRECTORY_SEPARATOR . MODULE_NAME . DIRECTORY_SEPARATOR . ACTION_NAME . ']';
    if (!$__APP_LOG_PID__) {
        $__APP_LOG_PID__ = '[PID:' . getmypid() . ']';
    }
    $first .= '[IP:' . get_client_ip() . '][GET:' . $_SERVER['REQUEST_URI'] . '][ACTION:' . ACTION_NAME . ']';
    if (!C('SERVER_LOG_FILE_PATH')) {
        C('SERVER_LOG_FILE_PAT', C('CUSTOM_LOG_PATH'));
    }
    if ($log_name == '') {
        $log_name = MODULE_NAME;
    }
    // if(!is_dir(C('SERVER_LOG_FILE_PATH') . DIRECTORY_SEPARATOR
    // .GROUP_NAME.DIRECTORY_SEPARATOR))
    // mkdir (C('SERVER_LOG_FILE_PATH') . DIRECTORY_SEPARATOR
    // .GROUP_NAME.DIRECTORY_SEPARATOR, 0777, true);;
    $destination = 'WC_' . GROUP_NAME . '_' . $log_name;
    $hostName    = function_exists('gethostname') ? gethostname() : php_uname('n');
    Log::write($content, '[' . $hostName . ']' . $__APP_LOG_PID__ . $first . $level, '', $destination);
        }

/**
 * BD-09转换GCJ-02
 *
 * @author by tr
 *
 * @param int $lat 纬度
 * @param int $lng 经度
 *
 * @return array
 *
 */
function map_baidu_to_GCJ($lat, $lng) {
    $v = M_PI * 3000.0 / 180.0;
    $x = $lng - 0.0065;
    $y = $lat - 0.006;

    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $v);
    $t = atan2($y, $x) - 0.000003 * cos($x * $v);

    return array(
            'lat' => $z * sin($t),
            'lng' => $z * cos($t),
    );
}

/**
 * GCJ-02转换BD-09
 *
 * @author tr
 *
 * @param int $lat 纬度
 * @param int $lng 经度
 *
 * @return array()
 *
 */
function map_GCJ_to_baidu($lat, $lng) {
    $v = M_PI * 3000.0 / 180.0;
    $x = $lng;
    $y = $lat;

    $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $v);
    $t = atan2($y, $x) + 0.000003 * cos($x * $v);

    return array(
            'lat' => $z * sin($t) + 0.006,
            'lng' => $z * cos($t) + 0.0065,
    );
}

/**
 * 获取上传文件路径
 *
 * @param        $url
 * @param string $path
 * @param string $ext
 *
 * @return string
 */
function get_upload_url($url, $path = '', $ext = '') {
    if (strpos($url, 'http') === 0 || !$url) {
        return $url;
    }
    if (strpos($url, './Home/Upload') === 0) {
        $url = str_replace('./Home/Upload', C('TMPL_PARSE_STRING.__URL_UPLOAD__'), $url);

        return $url;
    }
    if (strpos($url, './Home/Public') === 0) {
        $url = str_replace('./Home/Public', C('TMPL_PARSE_STRING.__PUBLIC__'), $url);

        return $url;
    }
    if (strpos($url, './Home/') === 0) {
        return $url;
    }
    $path = $path ? $path . '/' : '';

    return C('TMPL_PARSE_STRING.__URL_UPLOAD__') . '/' . $path . $url . $ext;
}

/**
 * 获取商户下级商户node_id
 *
 * @param $nodeId
 * @param $sqlStr 是否返回sql语句
 * @return $in true返回用于in条件查询的格式  false返回数组 , 只有$sqlStr为false才起作用
 */
function nodeIn($nodeId,$sqlStr=true,$in=true) {
    static $_node_full_id = array();
    if (isset($_node_full_id[$nodeId])) {
        $path = $_node_full_id[$nodeId];
    } else {
        // $path =
        // M('tnode_info')->where(array('node_id'=>$nodeId))->getField('full_id');
        $path                   = get_node_info($nodeId, 'full_id');
        $_node_full_id[$nodeId] = $path;
    }
    $sql = "select node_id from tnode_info where full_id like '" . $path . "%'";
	if($sqlStr === true){
		return $sql;
	}else{
		$infos = M()->query($sql);
		$tmp_array = array();
		foreach ($infos as $val) {
			$tmp_array[] = $val['node_id'];
		}
		if($in){
			return "'" . implode("','", $tmp_array) . "'";
		}else{
			return $tmp_array;
		}
	}

}

/*
 * 查询机构信息，并缓存 @param $node_id 机构号 @param $column 要查的字段 @param $cache false 取缓存
 * true取数据库
 */
function get_node_info($node_id, $column = '', $cache = false) {
    static $__get_node_info_arr;
    if (!$cache && !empty($__get_node_info_arr[$node_id])) {
        $node_info = $__get_node_info_arr[$node_id];
    } else {
        $node_info = M('tnode_info')->where(array(
                'node_id' => $node_id,
        ))->find();
        // 解析配置字段
        $node_info['cfg_data'] = unserialize($node_info['cfg_data']) or $node_info['cfg_data'] = array();
        $__get_node_info_arr[$node_id] = $node_info;
    }
    if (!$column) {
        return $node_info;
    }

    return $node_info[$column];
}

/**
 *
 * @param string $pbody 存储过程名称
 * @param array  $binds 参数
 *
 * @return bool 执行失败，返回false，否则返回数组
 */
function execute_proc($pbody, $binds) {
    $sql      = '';
    $args_sql = '';
    $out_sql  = '';
    $out_arr  = array();
    $allnum   = count($binds);

    for ($i = 0; $i < $allnum; $i++) {
        $item = $binds[$i];
        if ($item === null) {
            $args_sql .= "NULL,";
        } else {
            if (is_array($item)) {
                if (isset($item['OUT'])) {
                    $out_arr[] = $item['OUT'];
                    $args_sql .= "@{$item['OUT']},";
                    $out_sql .= "@{$item['OUT']} as {$item['OUT']},";
                } else if (isset($item['IN_OUT'])) {
                    $t_sql     = is_string($item[0]) ? "set @{$item['IN_OUT']}='{$item[0]}'" : "set @{$item['IN_OUT']}={$item[0]}";
                    $out_arr[] = $item['IN_OUT'];
                    $args_sql .= "@{$item['IN_OUT']},";
                    $out_sql .= "@{$item['IN_OUT']} as {$item['IN_OUT']},";
                } else {
                    $item_type = strtoupper($item[1]);
                    if ($item_type == 'BLOB' || $item_type == 'BLOB_FILE') {
                        if ($item_type == 'BLOB_FILE') {
                            $value = $this->read_file($item[0]);
                        }
                    }
                    $args_sql .= "'$value',";
                }
            } else {
                $args_sql .= is_string($item) ? "'$item'," : "$item,";
            }
        }
    }
    $args_sql = substr($args_sql, 0, -1);
    $out_sql  = substr($out_sql, 0, -1);

    $sql     = "call $pbody($args_sql)";
    $out_sql = $out_sql == '' ? '' : "select $out_sql";

    $flag = D()->execute($sql);
    if ($flag === false) {
        return false;
    }

    $arr = true;
    if ($out_sql) {
        $arr = D()->table("($out_sql) a")->find();
    }

    return $arr;
}

function city_text($code, $exp = '-') {
    static $city_arr = null;
    if ($city_arr === null) {
        $city_arr = S('all_city');
        if (!$city_arr) {
            $city_arr = M('tcity_code')->getField('path, province, city, town, business_circle');
            S('all_city', $city_arr);
        }
    }
    $info = $city_arr[$code];
    $str  = $info['province'];
    if ($info['city']) {
        $str .= $exp . $info['city'];
    }
    if ($info['town']) {
        $str .= $exp . $info['town'];
    }
    if ($info['business_circle']) {
        $str .= $exp . $info['business_circle'];
    }

    return $str;
}

function is_production() {
    return C('PRODUCTION_FLAG') == 1 ? true : false;
}

function isTestCheckCode($checkCode) {
    return $checkCode == '1111';
}

// 取纯文本短信发送的batch_no
function get_notes_batch_no($node_id) {
    $nodeInfo = get_node_info($node_id);
    if ($nodeInfo['notes_batch_no']) {
        return $nodeInfo['notes_batch_no'];
    }

    // 支撑创建终端组
    M()->startTrans();
    $groupId = M('tpos_group')->WHERE(array(
            'node_id'    => $nodeInfo['node_id'],
            'group_type' => 0,
    ))->getField('group_id');
    if ($groupId == '') {
        M('tnode_info')->where("node_id='{$node_id}'")->setInc('posgroup_seq'); // posgroup_seq
        // +1;

        $req_array     = array(
                'CreatePosGroupReq' => array(
                        'NodeId'    => $nodeInfo['node_id'],
                        'GroupType' => 0,  // 全门店
                        'GroupName' => str_pad($nodeInfo['client_id'], 6, '0',
                                        STR_PAD_LEFT) . $nodeInfo['posgroup_seq'],
                        'GroupDesc' => '',
                        'DataList'  => $node_id,
                ),
        );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array    = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg       = $resp_array['CreatePosGroupRes']['Status'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            M()->rollback();
            log_write("创建终端组失败，原因：{$ret_msg['StatusText']}");

            return false;
        }
        $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
        // 插入终端组信息

        $groupData = array( // tpos_group
                'node_id'    => $nodeInfo['node_id'],
                'group_id'   => $groupId,
                'group_name' => $req_array['CreatePosGroupReq']['GroupName'],
                'group_type' => 0,
                'status'     => '0',
        );
        $result    = M('tpos_group')->add($groupData);
        if (!$result) {
            M()->rollback();
            log_write("创建终端组失败，原因：入库异常001");

            return false;
        }
    }
    // 支撑创建活动
    $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
    $req_array     = array(
            'ActivityCreateReq' => array(
                    'SystemID'      => C('ISS_SYSTEM_ID'),
                    'ISSPID'        => $node_id,
                    'RelationID'    => $node_id,
                    'TransactionID' => $TransactionID,
                    'SmilID'        => '',
                    'ActivityInfo'  => array(
                            'CustomNo'          => '',
                            'ActivityName'      => '纯文本短信发送' . $node_id,
                            'ActivityShortName' => '纯文本短信发送' . $node_id,
                            'BeginTime'         => date('YmdHis'),
                            'EndTime'           => '20301231235959',
                            'UseRangeID'        => $groupId,
                    ),
                    'VerifyMode'    => array(
                            'UseTimesLimit' => !empty($validateType) && $validateType == 1 ? 0 : 1,
                            'UseAmtLimit'   => !empty($validateType) && $validateType == 1 ? 1 : 0,
                    ),
                    'GoodsInfo'     => array(
                            'GoodsName'      => '纯文本短信发送' . $node_id,
                            'GoodsShortName' => '纯文本短信发送' . $node_id,
                    ),
                    'DefaultParam'  => array(
                            'PasswordTryTimes' => 3,
                            'PasswordType'     => '',
                            'PrintText'        => '不打印',
                    ),
            ),
    );
    $RemoteRequest = D('RemoteRequest', 'Service');
    $resp_array    = $RemoteRequest->requestIssForImageco($req_array);
    $ret_msg       = $resp_array['ActivityCreateRes']['Status'];
    if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
        M()->rollback();
        log_write("活动创建失败:{$ret_msg['StatusText']}");

        return false;
    }
    $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
    $fruit   = M('tnode_info')->where(array(
            'node_id' => $node_id,
    ))->save(array(
            'notes_batch_no' => $batchNo,
    ));
    if ($fruit == false) {
        M()->rollback();
        log_write("活动号创建失败:入库异常002");

        return false;
    }
    M()->commit();
    log_write("活动创建成功:$node_id / $batchNo");

    return $batchNo;
}

/**
 * 发送纯文本短信 参数机构号，手机号，短信内容 [注：手机号请在外检验] 成功返回 TRUE
 */
function send_SMS($nodeid, $iphone, $content, $batch_no = null, $sendClass = 'MMS') {
    // 三个参数均不可为空
    if (empty($nodeid) || empty($iphone) || empty($content)) {
        return false;
    }
    // 组参数
    $TransactionID = date("YmdHis") . mt_rand(100000, 999999);
    if (empty($batch_no)) {
        $batch_no = get_notes_batch_no($nodeid);
    }
    if ($batch_no === false) {
        log_write('发送纯文本短信失败：取不到batch_no');

        return false;
    }
    $req_array = array(
            'NotifyReq' => array(
                    'TransactionID' => $TransactionID,
                    'ISSPID'        => $nodeid,
                    'SystemID'      => C('ISS_SYSTEM_ID'),
                    'SendLevel'     => '1',
                    'Recipients'    => array(
                            'Number' => $iphone,
                    ),
                    'SendClass'     => $sendClass,
                    'MessageText'   => $content,
                    'Subject'       => '',
                    'ActivityID'    => $batch_no,
                    'ChannelID'     => '',
                    'ExtentCode'    => '',
            ),
    );

    $RemoteRequest = D('RemoteRequest', 'Service');
    $resp_array    = $RemoteRequest->requestIssServ($req_array);
    $ret_msg       = $resp_array['NotifyRes']['Status'];

    return $ret_msg['StatusCode'] == '0000' ? true : false;
}

/*
 * 字符串隐藏 $m 字符串开头第几位开始隐藏为* $n 字符串末尾n位不隐藏 例 mark_str('abcdegf',1,1)=>a*****f
 */
function mark_str($str, $m = 0, $n = 0) {
    if (!$str) {
        return "";
    }
    $len   = strlen($str);
    $m_len = $len - $m - $n;
    if ($m_len <= 0) {
        return $str;
    }
    $fro   = substr($str, 0, $m);
    $end   = substr($str, $len - $n, $len);
    $m_str = str_repeat("*", $m_len);

    return $fro . $m_str . $end;
}

/*
 * 生成短链 $long_url 需要生成短链的url地址 返回短链接
 */
function make_short_url($long_url) {
    $apiUrl  = C('ISS_SERV_FOR_IMAGECO');
    $req_arr = array(
            'CreateShortUrlReq' => array(
                    'SystemID'      => C('ISS_SYSTEM_ID'),
                    'TransactionID' => time() . rand(10000, 99999),
                    'OriginUrl'     => "<![CDATA[$long_url]]>",
            ),
    );

    import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
    $xml        = new Xml();
    $str        = $xml->getXMLFromArray($req_arr, 'gbk');
    $error      = '';
    $result_str = httpPost($apiUrl, $str, $error);
    if ($error) {
        echo $error;

        return '';
    }

    $arr = $xml->parse($result_str);
    $arr = $xml->getArrayNoRoot();

    return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
}

/**
 * 将xml转化为数组
 *
 * @author miao yijin
 *
 * @param string $xmlstring
 *
 * @return mixed
 */
function simplest_xml_to_array($xmlstring) {
    return json_decode(json_encode((array)simplexml_load_string($xmlstring)), true);
}

/**
 * 判断是否来自微信
 *
 * @author zhaochao
 * @return bool
 */
function isFromWechat() {
    return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
}

/**
 * 获取唯一流水号
 *
 * @author zhengxh
 *
 * @param string $type 序列类型,该值定义在tsequence.name
 *
 * @return string 14位年月日时分秒 + 6位递增序列号 $type
 *         不传值或者该值未在tsequence.name进行定义，返回14位年月日时分秒 + 6位随机数。并发时不保证全局唯一 @Time
 *         2015-11-02 15:02:29
 */
function get_reqseq($type = '') {
    if (empty($type)) {
        $req = rand(1, 999999);
    } else {
        $data = M()->query("SELECT _nextval('$type') as reqid FROM DUAL");
        if (!$data) {
            log_write('get_reqseq fail! ' . M()->_sql());
            $req = rand(1, 999999);
        } else {
            $req = $data[0]['reqid'];
        }
    }

    return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
}

/**
 * 验证身份证
 *
 * @author wang pan
 *
 * @param string $number
 *
 * @return bool
 */
function verify_IDCard($number) {
    if (empty($number)) {
        return false;
    }
    $idCard = $number;
    $City   = array(
            11 => "北京",
            12 => "天津",
            13 => "河北",
            14 => "山西",
            15 => "内蒙古",
            21 => "辽宁",
            22 => "吉林",
            23 => "黑龙江",
            31 => "上海",
            32 => "江苏",
            33 => "浙江",
            34 => "安徽",
            35 => "福建",
            36 => "江西",
            37 => "山东",
            41 => "河南",
            42 => "湖北",
            43 => "湖南",
            44 => "广东",
            45 => "广西",
            46 => "海南",
            50 => "重庆",
            51 => "四川",
            52 => "贵州",
            53 => "云南",
            54 => "西藏",
            61 => "陕西",
            62 => "甘肃",
            63 => "青海",
            64 => "宁夏",
            65 => "新疆",
            71 => "台湾",
            81 => "香港",
            82 => "澳门",
            91 => "国外",
    );

    // 长度验证
    if (!preg_match('/^\d{17}(\d|X)$/', $idCard) and !preg_match('/^\d{15}$/', $idCard)) {
        return false;
    }
    // 地区验证
    if (!array_key_exists(intval(substr($idCard, 0, 2)), $City)) {
        return false;
    }
    $idCardLength = strlen($idCard);
    // 15位身份证转成18位
    if ($idCardLength == 15) {
        $idCard = substr($idCard, 0, 6) . "19" . substr($idCard, 6, 9); // 15to18
    }
    // 判断是否小于1900年
    $year = substr($idCard, 6, 4);
    if ($year < 1900) {
        return false;
    }

    // 18位身份证处理
    $isBirthday = substr($idCard, 6, 4) . '-' . substr($idCard, 10, 2) . '-' . substr($idCard, 12, 2);
    if ($isBirthday != date('Y-m-d', strtotime($isBirthday)) && $isBirthday != '1970-01-01') {
        return false;
    }

    // 身份证编码规范验证
    $idCardBase = substr($idCard, 0, 17);
    // 加权因子
    $factor = array(
            7,
            9,
            10,
            5,
            8,
            4,
            2,
            1,
            6,
            3,
            7,
            9,
            10,
            5,
            8,
            4,
            2,
    );
    // 校验码对应值
    $verifyNumberList = array(
            '1',
            '0',
            'X',
            '9',
            '8',
            '7',
            '6',
            '5',
            '4',
            '3',
            '2',
    );
    $checksum         = 0;

    for ($i = 0; $i < strlen($idCardBase); $i++) {
        $checksum += (int)substr($idCardBase, $i, 1) * $factor[$i];
    }
    $mod = $checksum % 11;
    if ($number != $idCardBase . $verifyNumberList[$mod]) {
        return false;
    }

    return true;
}

/**
 * 替换php中的换行为html中的换行
 *
 * @author john zeng
 *
 * @param string $s
 *
 * @return string
 */
function my_nl2br($s) {
    return str_replace("\n", '<br>', str_replace("\r", '<br>', str_replace("\r\n", '<br>', $s)));
}
/**
 * 替换字符串中的一部分字节为*号
 *
 * @author john zeng
 *
 * @param string $str
 *
 * @return string
 */
function replaceString($str){
    $len = strlen($str)/2;
    return substr_replace($str, str_repeat('*', $len), floor(($len)/2), $len);
}

/**
 * 翼蕙宝短信接口
 *
 * @param number $number 目标号码
 * @param string $text   短信内容
 *
 * @return bool 成功与否
 */
function Yhb_sms($number, $text) {
    if (empty($number) || empty($text)) {
        $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n发送状态：发送参数错误";
        log_write($msg);

        return false;
    }
    // 获取配置
    $conf     = C('yhb.yhb_sms');
    $username = $conf["username"];
    $password = $conf["password"];
    $subid    = $conf["subid"];
    $msgtype = $conf['msgtype'] or 1;
    $url = $conf['url'];

    if (empty($username) || empty($password) || empty($url)) {
        $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n" . var_export($conf, true) . "\n发送状态：配置参数错误";
        log_write($msg);

        return false;
    }
    // 数据处理
    $data = array(
            "username" => $username,
            'password' => $password,
            'to'       => $number,
            'text'     => urlencode(iconv("UTF-8", "gbk", $text)),
            'subid'    => $subid,
            'msgtype'  => $msgtype,
    );
    foreach ($data as $key => $val) {
        $url .= $key . '=' . $val . '&';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $out = curl_exec($ch);
    curl_close($ch);
    $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n发送状态：" . var_export($out);
    log_write($msg);
    if ($out !== false and $out == '0') {
        return true;
    } else {
        $content = array(
                'petname'      => "yangch@imageco.com.cn",
                'test_title'   => "翼蕙宝短信接口",
                'text_content' => "翼蕙宝短信发送出错" . $msg,
                'CC'           => "翼蕙宝",
        );
        to_email($content);

        return false;
    }
}

/**
 * *cvs 下载函数
 */
function csv_h($filename) {
    header("Content-type:text/csv");
    // go to
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=" . iconv("UTF-8", "gbk", $filename) . ".csv");
    header('Expires:0');
    header('Pragma:public');
}

function downloadCsvData($csv_data = array(), $arrayhead = array()) {
    $csv_string = null;
    $csv_row    = array();
    if (!empty($arrayhead)) {
        $current = array();
        foreach ($arrayhead as $item) {

            $current[] = iconv("UTF-8", "gbk", $item);
        }
        $csv_row[] = trim(implode(",", $current), ',');
    }
    if (!empty($csv_data)) {
        foreach ($csv_data as $key => $csv_item) {
            $current = array();
            foreach ($csv_item as $item) {

                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
    }
    $csv_string = implode("\r\n", $csv_row);
    echo $csv_string;
}


/**
 * 功能切换
 * @author Jeff Liu<liuwy@imageco.com.cn>
 *
 * @param $functionName
 *
 * @return bool
 */
function functionSwitchResult($functionName) {
    $result         = true;
    $functionSwitch = C('FUNCTION_SWITCH');
    if (isset($functionSwitch[$functionName])) {
        $currentTime = time();
        $startTime   = $functionSwitch[$functionName]['startTime'];
        $endTime     = $functionSwitch[$functionName]['endTime'];
        if ($currentTime < $startTime || $currentTime > $endTime) {
            $result = false;
        }
    }

    return $result;
}
/**
 * 尝试获得$originValue中制定的key的值 如果不存在，返回默认值
 *
 * 使用方式
 * 数组
 * $originValue = array('key1' => 'value1', 'key2' => 'value2');
 * $value3 = get_val($originValue, 'key3'); //返回结果为 null
 * $value3 = get_val($originValue, 'key3', 'value3'); //返回结果为 'value3'
 * 字符串：
 * $originValue = 'adbc';
 * $value = get_val($originValue, '', 'c'); //此时返回 'abc'  如果$originValue没有初始化 返回 c
 * @param        $originValue
 * @param string $key
 *
 * @param null   $default
 *
 * @return null
 */
function get_val(&$originValue, $key = '', $default = null) {
    if ($key !== '') {
        return isset($originValue[$key]) ? $originValue[$key] : $default;
    } else {
        return isset($originValue) ? $originValue : $default;
    }
}

/**
 * 获得字符串的值
 * @param      $originValue
 * @param null $default
 *
 * @return null
 */
function get_scalar_val(&$originValue, $default = null) {
    return get_val($originValue, '', $default);
}

function get_val2(&$originValue, $key = '', $default = null) {
    if (is_array($originValue)) {
        return get_val($originValue, $key, $default);
    } else if (is_scalar($originValue)) {
        return get_scalar_val($originValue, $default);
    }
    return $default;
}

/**
 * @param        $originValue
 * @param        $key
 * @param        $except
 * @param string $option
 *
 * @return bool
 */
function verify_val(&$originValue, $key, $except, $option = '==') {
    if (!is_scalar($originValue)) {
        $value =  get_val($originValue, $key);
    } else {
        $value = $originValue;
    }
    return verify_scalar_val($value, $except, $option);
}


function issetAndNotEmpty(&$originValue, $key) {
    return isset($originValue[$key]) && $originValue[$key];
}

function unsetOrIsEmpty(&$originValue, $key) {
    return !isset($originValue[$key]) or empty($originValue[$key]);
}

/**
 *
 * @param        $value
 * @param        $except
 * @param string $option
 *
 * @return bool
 */
function verify_scalar_val(&$value, $except, $option = '==') {
    $option = trim($option);
    switch ($option) {
        case '==':
            $return = $value == $except;
            break;
        case '===':
            $return = $value === $except;
            break;
        case '>=':
            $return = $value >= $except;
            break;
        case '<=':
            $return = $value <= $except;
            break;
        case '>':
            $return = $value > $except;
            break;
        case '<':
            $return = $value < $except;
            break;
        case '!=':
            $return = $value != $except;
            break;
        case '!==':
            $return = $value < $except;
            break;
        default:
            $return = eval("{$value} {$option} $except");

            return $return;
    }

    return $return;
}

/**
 * 可以在控制台输出信息不会影响正常数据显示,需要安装浏览器插件firephp for chrome或firephp
 * @author miaoyijin<liuwy@imageco.com.cn>
 * @param  $array string
 */
function fb($message){
      import('@.Vendor.FirePHP');
      $fphp=FirePHP::getInstance(true);
      $fphp->fb($message);
}


/**
 * 发送系统消息方法
 * @param $data   //可为数组 也可为sql语句
 * @param $node_id
 *
 * @return bool
 */
function sendMsg($data,$node_id)
{
    if(!$data || !$node_id){
        $this->error("参数有误");
        exit;
    }

    M()->startTrans();
    $result = '';
    $err = '';

    if(!is_array($data) && strpos($data, 'tmessage_news')){
        $result = M()->query($data);
    }else{
        $result = M('tmessage_news')->add($data);
    }

    if(!$result){
        $err = 'message table Add failure';
    }else{
        $recoredData = array(
                "message_id"=>$result,
                "node_id"=>$node_id,
                "send_status"=>'0',
                "status"=>'0',
                "add_time"=>date('YmdHis')
        );
        $recoredResult = M('tmessage_recored')->add($recoredData);

        if(!$recoredResult){
            $err = 'recored table Add failure';
        }else{
            $statResult = M()->query('update tmessage_stat set total_cnt =total_cnt +1,
				     new_message_cnt=new_message_cnt+1,last_time="'.date("YmdHis").'" where   message_type=1 and   node_id = "'.$node_id.'"');

            if($statResult != 1) {
                $sql='insert into  tmessage_stat(total_cnt,new_message_cnt,last_time,message_type,node_id)
								values(1,1,"'.date("YmdHis").'",1,"'.$node_id.'")';
                $lastResult = M()->query($sql);
                if(!$lastResult){
                    $err = 'stat table Add failure';
                }
            }
        }
    }

    if($err){
        log_write($err);
        M()->rollback();
        return false;
    }else{
        M()->commit();
        return true;
    }

}

/**
 * 根据手机号添加会员信息
 *
 * @param $phoneNo
 * @param $channelId
 * @param $batchId
 * @param $nodeId
 * @return int|mixed
 */
function addMemberByO2o($phoneNo, $nodeId, $channelId=0, $batchId = '')
{
    $memberTmpModel = M('tmember_info');
    $userId = $memberTmpModel->where(
        array(
            'phone_no' => $phoneNo,
            'node_id' => $nodeId))->getField('id');
    $mIns = D('MemberInstall', 'Model');
    if (! $userId) {
        $card_id = M('tmember_cards')->where(
            array(
                'node_id' => $nodeId,
                'acquiesce_flag' => 1))->getField('id');
        $data = array(
            'node_id' => $nodeId,
            'batch_no' => $batchId,
            'name' => '',
            'phone_no' => $phoneNo,
            'sex' => '1',
            'years' => date('Y'),
            'month_days' => date('md'),
            'status' => '0',
            'add_time' => date('YmdHis'),
            'request_id' => '',
            'channel_id' => $channelId,
            'batch_id' => $batchId,
            'card_id' => $card_id);

        $result = $memberTmpModel->add($data);
        log_write('【'.__LINE__.'】添加会员：'.M()->_sql());
        $userId = $result;
    }

    // 生成会员卡号
    $mIns->makeMemberCardNum($nodeId, $userId);
    // 生成会员二维码 (取消PHP生成，改为JQuery插件动态生成)
    // $mIns->makeMemberCode($nodeId, $userId);

    if (! $userId) {
        $userId = 0;
    }
    return $userId;
}

/**
 * @param bool   $static
 * @param string $url
 * @param string $vars
 * @param bool   $suffix
 * @param bool   $redirect
 * @param bool   $domain
 *
 * @return string
 */
function makeUrl($static = true, $url = '', $vars = '', $suffix = true, $redirect = false, $domain = false)
{
    if ($static) {
        $host = './';
        if ($domain) {
            $host = $_SERVER['HTTP_HOST'];
        }
        $finalUrl = $host . '/Home/Html/' . makeStaticRule($url, $vars, $suffix);
        if ($redirect) {
            redirect($url);
        }
    } else {
        $finalUrl = U($url, $vars, $suffix, $redirect, $domain);
    }

    return $finalUrl;
}

function makeStaticRule($url = '', $vars = '', $suffix = true)
{
    $str = '';
    $str .= str_replace('/', '-', $url) . '-' . implode('-', $vars);
    if ($suffix === true) {
        $str .= '.html';
    } else {
        $str .= '.' . rtrim($suffix, '.');
    }

    return $str;
}

function makeStaticPath($url = '', $vars = '', $suffix = true)
{
    return  HTML_PATH . makeStaticRule($url, $vars, $suffix);
}

//厦门银行非标 start
function isXmyhFb($nodeId, $mId) {
    $re = false;
    if ($nodeId == C('XMYH.node_id') && in_array($mId, C('XMYH.m_id'))) {
        $re = true;
    }
    return $re;
}
//厦门银行非标

/**
 * uuid v4
 */
if (!function_exists('com_create_guid')) {
    function com_create_guid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}

/**
 * 创建流水号
 */
function genreateSerialNumber($name)
{
    import('@.Vendor.uuid.UUID');
    $r = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING2);
    if (is_null($r)) {
        $r = get_reqseq($name);
    }
    return $r;
}
/**
 * 检查数组中是否存在某一值或某一键值
 *
 * @author john zeng
 *
 * @param string $value     //数组值
 * @param string $key       //数组键
 * @param array  $array     //目标数组
 *
 * @return boolen
 */
function in_array_value_or_key($value = '', $key = '', $array)
{
    if('' == $value && '' == $key){
        return false;
    }
    $return = false;
    if('' == $value){
        $return = array_key_exists($key, $array);
        if(false === $return){
           $return = check_array($key, $array);
        }
    }else{
        $return = in_array($value, $array);
        if(false === $return){
           $return = check_array($value, $array);
        }
    }
    return $return;
}

function check_array($key, $array){
    $return = false;
    foreach ($array as $value){
        if(is_array($value)){
            $return = array_key_exists($key, $value);
            if(true === $return){
                break;
            }
        }
        if(false === $return){
            check_array($key, $value);
        }
    }
    return $return;
}

/**
     * Description of SkuService 将多维数组转换为一维数组
     *
     * @param array $array
     *
     * @return array  $array
     * @author john_zeng
     */
function array_multi2single($array)
{
    static $result_array=array();
    foreach($array as $value)
    {
        if(is_array($value))
        {
            array_multi2single($value);
        }
        else
            $result_array[]=$value;
    }
    return $result_array;
}

/**
     * Description of SkuService 获取分组信息
     *
     * @param string $classify 商品分组信息
     * @param array $classifyInfo 商户所有分组信息
     *
     * @return array  $array
     * @author john_zeng
     */
function get_goods_classify($classify, $classifyInfo)
{
    $result_string = '';
    $classifyArr = array();
    $tmpArray = array();
    $classifyArr = explode(',', $classify);
    foreach($classifyArr as $value)
    {
        if(!empty($value))
        {
            $tmpArray[] = $classifyInfo[$value];
        }
    }
    $result_string = implode(',', $tmpArray);
    return $result_string;
}

function file_debug($params, $label = '', $fileName = '', $filePath = '')
{
    if (empty($filePath)) {
        if (defined('PHP_OS') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $filePath = '/tmp/';
        } else {
            $filePath = 'd:/';
        }
    }

    if (empty($fileName)) {
        if (empty($label)) {
            $fileName = 'file_debug.log';
        } else {
            $fileName = $label;
        }
    }

    $file = $filePath . $fileName;
    if (!is_scalar($params)) {
        $params = var_export($params,1);
    }
    error_log('[date:]'.date('Y-m-d H:i:s').'|'.$label . ':' . $params . PHP_EOL, 3, $file);
}

/**
 * @param        $code
 * @param        $url
 * @param int    $time
 * @param string $msg */
function redirectWithCode($code, $url, $time=0, $msg='')
{
    if (!headers_sent()) {
        http_response_code($code);
    }
    redirect($url, $time, $msg);
}

if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {
        $prev_code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

        if ($code === NULL) {
            return $prev_code;
        }

        switch ($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                trigger_error('Unknown http status code ' . $code, E_USER_ERROR); // exit('Unknown http status code "' . htmlentities($code) . '"');
                return $prev_code;
        }

        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        $GLOBALS['http_response_code'] = $code;

        // original function always returns the previous or current code
        return $prev_code;
    }
}

function triggerBehaviorWithResult($name, &$params=NULL)
{
    if(strpos($name,'/')){
        list($name,$method) = explode('/',$name);
    }else{
        $method     =   'run';
    }
    $class      = $name.'Behavior';
    if(APP_DEBUG) {
        G('behaviorStart');
    }
    $behavior   = new $class();
    $return = $behavior->$method($params);
    if(APP_DEBUG) { // 记录行为的执行日志
        G('behaviorEnd');
        trace($name.' Behavior ::'.$method.' [ RunTime:'.G('behaviorStart','behaviorEnd',6).'s ]','','INFO');
    }

    return $return;
}

function BR($name, &$params=NULL)
{
    return triggerBehaviorWithResult($name, $params);
}

/**
 * 获取当前页
 * @return int
 */
function getCurrentPage()
{
    $pageKey = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
    return !empty($_GET[$pageKey]) ? intval($_GET[$pageKey]) : 1;
}

function asyncRequest($url, $type = 'get', $data = '', $cookie = '', $mstimeout = 3, $block = 0)
{
    $ch       = curl_init();
    $curl_opt = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT_MS     => $mstimeout,
            CURLOPT_NOSIGNAL       => 1
    ];
    if ($type == 'post') {
        $curl_opt[CURLOPT_POST]       = 1;
        $curl_opt[CURLOPT_POSTFIELDS] = http_build_query($data);
    }

    if ($cookie) {
        $curl_opt[CURLOPT_COOKIE] = $cookie;
    }

    curl_setopt_array($ch, $curl_opt);
    $s = curl_exec($ch);
    curl_close($ch);
}